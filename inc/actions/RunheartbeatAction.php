<?php
/**
 * "Runheartbeat" action
 *
 * @author Maciej Borzecki, 2012
 * @since 1.0.0
 * @package TestSwarm
 */

class RunheartbeatAction extends Action {

	/**
	 * @actionMethod GET/POST: Required.
	 * @actionParam int resultsId
	 * @actionParam string type: one of 'specStart', 'timeoutCheck'
	 */
	public function doAction() {
		$request = $this->getContext()->getRequest();

		$resultsId = $request->getInt( "resultsId" );
		$type = $request->getVal( "type" );

		if ( !$resultsId || !$type ) {
			$this->setError( "missing-parameters" );
			return;
		}

		if ( !in_array( $type, array( "stepStart", "timeoutCheck" ) ) ) {
			$this->setError( "invalid-input" );
			return;
		}

		$now = time();
		$db = $this->getContext()->getDB();
		$conf = $this->getContext()->getConf();
		$result = "";

		switch( $type ) {
			case "stepStart":
				if ( !$request->wasGetted() ) {
					$this->setError( "requires-get" );
					return;
				}

				$beatRate = $request->getInt( "beatRate" );
				$fail = $request->getInt( "fail" );
				$error = $request->getInt( "error" );
				$total = $request->getInt( "total" );
				if ( !$beatRate ) {
					$this->setError( "missing-parameters" );
					return;
				}

				$nextHeartbeat = $now + $beatRate;

				$db->query(str_queryf(
					"UPDATE runresults
					SET
						fail = %u,
						error = %u,
						total = %u,
						next_heartbeat = %s,
						updated = %s
					WHERE id = %u
					AND status = 1;",
					$fail,
					$error,
					$total,
					swarmdb_dateformat( $nextHeartbeat ),
					swarmdb_dateformat( $now ),
					$resultsId
				));

				if ( $db->getAffectedRows() !== 1 ) {
					$this->setError( 'internal-error', 'Updating of results table failed.' );
					return;
				}

				$runId = $db->getOne(str_queryf(
					"SELECT run_id
					FROM runresults
					WHERE id = %u;",
					$resultsId
				));

				$db->query(str_queryf(
					"UPDATE run_useragent
					SET
						updated = %s
					WHERE results_id = %u
					AND run_id = %u
					AND status = 1;",
					swarmdb_dateformat( $now ),

					$resultsId,
					$runId
				));

				$result = "ok";
				break;

			case "timeoutCheck":
				if ( !$request->wasPosted() ) {
					$this->setError( "requires-post" );
					return;
				}

				$maxHeartbeatAge = $now - $conf->client->runHeartbeatTimeMargin;

				$nextHeartbeat = $db->getNumRows(str_queryf(
					"SELECT next_heartbeat
					FROM runresults
					WHERE id = %u
						AND next_heartbeat < %s;",
					$resultsId,
					swarmdb_dateformat( $maxHeartbeatAge )

				));

				$result = array(
					"testTimedout" => $nextHeartbeat > 0 ? 'true' : 'false'
				);
				break;
		}

		$this->setData( $result );
	}
}