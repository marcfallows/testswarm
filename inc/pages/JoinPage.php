<?php
/**
 * "Join" page.
 *
 * @author Marc Fallows, 2013
 * @since 2.0.0
 * @package TestSwarm
 */

class JoinPage extends Page {

	protected function initContent() {
		$browserInfo = $this->getContext()->getBrowserInfo();
		$conf = $this->getContext()->getConf();
		$request = $this->getContext()->getRequest();

		$clientName = $request->getVal('item', 'device');

		$this->setTitle( 'Join Page' );

		$this->bodyScripts[] = swarmpath( "js/require.js" );
		$this->bodyScripts[] = swarmpath( "js/device-manager.js?" . time() );
		$this->bodyScripts[] = swarmpath( "js/join.js?" . time() );

		$html = '<script>'
				. 'SWARM.client_ip = ' . json_encode( $request->getIP() ) . ';'
				. 'SWARM.client_name = ' . json_encode( $clientName ) . ';'
			. '</script>';

		$html .= '<div class="well">'
				. '<h3>Querying Device</h3>'
				. '<p id="join-status">Gathering device details. Please wait...</p>'

			. '</div>';

		return $html;
	}
}
