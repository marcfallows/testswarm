<?php
/**
 * "Getrun" action.
 *
 * @author John Resig, 2008-2011
 * @since 0.1.0
 * @package TestSwarm
 */
class GetrunAction extends Action {

	/**
	 * @actionMethod POST: Required.
	 * @actionParam string run_token
	 * @actionParam int client_id
	 */
	public function doAction() {
		$browserInfo = $this->getContext()->getBrowserInfo();
		$conf = $this->getContext()->getConf();
		$db = $this->getContext()->getDB();
		$request = $this->getContext()->getRequest();

		if ( !$request->wasPosted() ) {
			$this->setError( "requires-post" );
			return;
		}

		$runToken = $request->getVal( "run_token" );
		if ( $conf->client->requireRunToken && !$runToken ) {
			$this->setError( "missing-parameters", "This TestSwarm does not allow unauthorized clients to join the swarm." );
			return;
		}

		$clientID = $request->getInt( "client_id" );

		if ( !$clientID ) {
			$this->setError( "missing-parameters" );
			return;
		}

		// Create a Client object that verifies client id, user agent and run token.
		// Also updates the client 'alive' timestamp.
		// Throws exception (caught higher up) if stuff is invalid.
		$client = Client::newFromContext( $this->getContext(), $runToken, $clientID );

		// Get oldest idle (status=0) run for this user agent.

        // BLINKBOX NOTE: We DO want any available client to run the test, so remove this condition:
            // Except if it was already ran in this client in the past (client_id=%u), because
        	// in that case it must've failed. We don't want it to run in the same client again.
			// AND NOT EXISTS (SELECT 1 FROM runresults WHERE runresults.run_id = run_useragent.run_id AND runresults.client_id = %u)

		$runID = $db->getOne(str_queryf(
			'SELECT	run_useragent.run_id
			FROM jobs
				INNER JOIN runs ON jobs.id = runs.job_id
				INNER JOIN run_useragent  ON runs.id = run_useragent.run_id
				LEFT OUTER JOIN (
					/* This gives us a list of users who have run jobs and when they last updated a run */
					SELECT projects.id,
						projects.priority,
						MAX(run_useragent.updated) AS max_updated
					FROM projects
						INNER JOIN jobs ON projects.id = jobs.project_id
						INNER JOIN runs ON jobs.id = runs.job_id
						INNER JOIN run_useragent ON runs.id = run_useragent.run_id
					WHERE useragent_id = %s
					GROUP BY projects.id
				) next_project ON next_project.id = jobs.project_id
			WHERE useragent_id = %s
			AND run_useragent.status = 0
			ORDER BY next_project.priority,
				next_project.max_updated,
				next_project.id,
				run_useragent.run_id DESC
			LIMIT 1;',
			$browserInfo->getSwarmUaID(),
			$browserInfo->getSwarmUaID()
		));

		$runInfo = false;

		// A run was found for the current user_agent
		if ( $runID ) {

			$row = $db->getRow(str_queryf(
				"SELECT
					runs.url as run_url,
					jobs.name as job_name,
					runs.name as run_name
				FROM
					runs, jobs
				WHERE runs.id = %u
				AND   jobs.id = runs.job_id
				LIMIT 1;",
				$runID
			));

			if ( $row && $row->run_url && $row->job_name && $row->run_name ) {
				// Create stub runresults entry
				$storeToken = sha1( mt_rand() );
				$now = time();
				$expected_update = $now + $conf->client->expectedUpdateTimeoutMargin;
				$isInserted = $db->query(str_queryf(
					'INSERT INTO runresults
					(run_id, client_id, status, store_token, updated, created, expected_update)
					VALUES(%u, %u, 1, %s, %s, %s, %s);',
					$runID,
					$clientID,
					sha1( $storeToken ),
					swarmdb_dateformat( SWARM_NOW ),
					swarmdb_dateformat( SWARM_NOW ),
					swarmdb_dateformat( $expected_update )
				));
				$runresultsId = $db->getInsertId();
				if ( !$isInserted || !$runresultsId ) {
					$this->setError( 'internal-error', 'Creation of runresults database entry failed.' );
					return false;
				}

				// Mark as in-progress (status=1), and link runresults entry
				$db->query(str_queryf(
					'UPDATE run_useragent
					SET
						status = 1,
						updated = %s,
						results_id = %u
					WHERE run_id = %u
					AND   useragent_id = %s
					LIMIT 1;',
					swarmdb_dateformat( SWARM_NOW ),
					$runresultsId,

					$runID,
					$browserInfo->getSwarmUaID()
				));

				$runInfo = array(
					"id" => $runID,
					"url" => $row->run_url,
					"desc" => $row->job_name . ' ' . $row->run_name,
					'resultsId' => $runresultsId,
					'resultsStoreToken' => $storeToken
				);
			}
		}

		$this->setData( array(
			'runInfo' => $runInfo,
		) );
	}
}

