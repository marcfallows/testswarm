<?php
/**
 * "Savedevice" action.
 *
 * @author Marc Fallows, 2013
 * @since 2.0.0
 * @package TestSwarm
 */
class SavedeviceAction extends Action {

	/**
	 * @actionMethod POST: Required.
	 * @actionParam int device_id
	 * @actionParam string name
	 */
	public function doAction() {
		$conf = $this->getContext()->getConf();
		$db = $this->getContext()->getDB();
		$request = $this->getContext()->getRequest();

		if ( !$request->wasPosted() ) {
			$this->setError( 'requires-post' );
			return;
		}

		$deviceID = $request->getInt( 'device_id' );
		$name = $request->getVal( 'name' );

		if ( !$deviceID || !$name ) {
			$this->setError( 'missing-parameters' );
			return;
		}

		$db->query(str_queryf(
			'UPDATE
				devices
			SET
				name = %s
			WHERE id = %u
			LIMIT 1;',
			$name,
			$deviceID
		));

		if ( $db->getAffectedRows() !== 1 ) {
			$this->setError( 'internal-error', 'Updating of devices table failed.' );
			return;
		}

		$this->setData( 'ok' );
	}
}

