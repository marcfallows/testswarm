<?php
/**
 * Class to create or get info about a device.
 *
 * @author Marc Fallows, 2013
 * @since 2.0.0
 * @package TestSwarm
 */
class Device {
	/**
	 * @var $context TestSwarmContext
	 */
	protected $context;

	protected $deviceRow;

	protected function loadFromID( $deviceID ) {
		$db = $this->context->getDB();

		// Verify that the client exists.
		$deviceRow = $db->getRow(str_queryf(
			'SELECT
				*
			FROM
				devices
			WHERE id = %u
			LIMIT 1;',
			$deviceID
		));

		if ( !$deviceRow || !$deviceRow->id ) {
			throw new SwarmException( 'Invalid device ID.' );
		}

		$this->deviceRow = $deviceRow;
	}

	/**
	 * @throws SwarmException
	 * @return bool
	 */
	protected function loadFromContext() {
		$db = $this->context->getDB();
		$request = $this->context->getRequest();

		$deviceKey = $request->getVal('device_key', '');

		if ( empty($deviceKey) ) {
			throw new SwarmException( 'Device key is required.' );
		}

		// Verify that the client exists.
		$deviceRow = $db->getRow(str_queryf(
			'SELECT
				*
			FROM
				devices
			WHERE device_key = %s
			LIMIT 1;',
			$deviceKey
		));

		if ( !$deviceRow || !$deviceRow->id ) {
			return false;
		}

		$this->deviceRow = $deviceRow;
		return true;
	}

	protected function loadNew() {

		if( $this->loadFromContext()){
			return;
		}

		$db = $this->context->getDB();
		$request = $this->context->getRequest();

		$deviceKey = $request->getVal('device_key', '');
		$model = $request->getVal('model', '');
		$deviceType = $request->getVal('device_type', '');

		// Insert in a new record for the client and get its ID
		$db->query(str_queryf(
			'INSERT INTO devices (name, device_key, device_type, model)
			VALUES(%s, %s, %s, %s);',
			$deviceKey,
			$deviceKey,
			$deviceType,
			$model
		));

		$this->deviceRow = $db->getRow(str_queryf(
			'SELECT * FROM devices WHERE id = %u LIMIT 1;',
			$db->getInsertId()
		));
	}

	public function getDeviceRow() {
		return $this->deviceRow;
	}

	/**
	 * @param TestSwarmContext $context
	 * @param Client $client
	 * @return Device
	 */
	public static function newFromContext( TestSwarmContext $context, $deviceID = null ) {

		$device = new self();
		$device->context = $context;

		if ( $deviceID !== null ) {
			$device->loadFromID( $deviceID );
		} else {
			$device->loadNew();
		}

		return $device;
	}

	/** Don't allow direct instantiations of this class, use newFromContext instead. */
	private function __construct() {}
}
