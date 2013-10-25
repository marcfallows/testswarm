<?php
/**
 * "Cleanup" action (previously WipeAction)
 *
 * @author John Resig, 2008-2011
 * @since 0.1.0
 * @package TestSwarm
 */
class CleanupAction extends Action {

	/**
	 * @actionNote This action takes no parameters.
	 */
	public function doAction() {
		$context = $this->getContext();
		$browserInfo = $context->getBrowserInfo();
		$db = $context->getDB();
		$conf = $context->getConf();
		$request = $context->getRequest();

		$resetTimedoutRuns = 0;

		$now = time();
		$maxHeartbeatAge = $now - $conf->client->runHeartbeatTimeMargin;

		$rows = $db->getRows(str_queryf(
			"SELECT
				id,
				run_id
			FROM runresults
			WHERE status = %u
				AND next_heartbeat < %s;",
			ResultAction::$STATE_BUSY,
			swarmdb_dateformat( $maxHeartbeatAge )
		));

		if ( $rows ) {
			foreach ( $rows as $row ) {

				$runID = $row->run_id;
				$resultsID = $row->id;

				$ret = $db->query(str_queryf(
					'UPDATE
						run_useragent
					SET
						completed = completed + 1,
						status = IF(completed < max, 0, 2),
						updated = %s
					WHERE results_id = %u
					LIMIT 1;',
					swarmdb_dateformat( SWARM_NOW ),
					$resultsID
				));

				if ( $ret ) {
					$ret = $db->query(str_queryf(
						'UPDATE
							runresults
						SET
							status = %u,
							updated = %s
						WHERE id = %u
						LIMIT 1;',
						ResultAction::$STATE_LOST,
						swarmdb_dateformat( SWARM_NOW ),
						$resultsID
					));

					if ( $ret ) {
						$resetTimedoutRuns++;
					}
				}
			}
		}

		// Suspend user agent runs which have not updated within the auto-suspend threshold.
		$maxAutoSuspendedAge = time() - $conf->general->autoSuspendAfterTime;

		$db->query(str_queryf(
			"UPDATE run_useragent
			SET	status = %u,
				results_id = NULL
			WHERE run_useragent.status = 0
				AND run_useragent.updated < %s;",
			JobAction::$STATE_SUSPENDED,
			swarmdb_dateformat( $maxAutoSuspendedAge )
		));

		$numAutoSuspendedRunRows = $db->getAffectedRows();

		$this->setData(array(
			"resetTimedoutRuns" => $resetTimedoutRuns,
			"autoSuspendedRuns" => $numAutoSuspendedRunRows,
		));
	}
}

