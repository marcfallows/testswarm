<?php
/**
 * "Device" page.
 *
 * @author Marc Fallows, 2013
 * @since 2.0.0
 * @package TestSwarm
 */

class DevicePage extends Page {

	public function execute() {
		$action = DeviceAction::newFromContext( $this->getContext() );
		$action->doAction();

		$this->setAction( $action );
		$this->content = $this->initContent();
	}

	protected function initContent() {
		$this->setTitle( 'Device' );
		$this->bodyScripts[] = swarmpath( "js/device.js" );

		$error = $this->getAction()->getError();
		$data = $this->getAction()->getData();
		$html = '';

		$html .= '<script>SWARM.deviceInfo = ' . json_encode( $data["info"] ) . ';</script>';

		if ( $error ) {
			$html .= html_tag( 'div', array( 'class' => 'alert alert-error' ), $error['info'] );
		}

		if ( !isset( $data['info'] ) ) {
			return $html;
		}

		$info = $data['info'];

		$this->setSubTitle( '<span class="device-name">' . $info['name'] . '</span>' );

		$html .= '<h3>Information</h3>'
			. '<div class="alert alert-error swarm-savedevice-error" style="display: none;"></div>'
			. '<table class="table table-striped swarm-device">'
			. '<tbody>'
			. '<tr><th>Device Name</th><td>'
			. '<span class="device-name-edit">'
				. '<span class="device-name device-name-source">' . htmlspecialchars( $info['name'] ) . '</span> '
				. '<span class="device-name-edit-icon"><span class="icon-edit"></span></span>'
			. '</span>'
			. '<div class="device-name-form form-inline" style="display: none;">'
				. '<input type="text" class="device-name-form-input" /> '
				. '<div class="device-name-form-submit btn btn-primary">Save</div> '
				. '<div class="device-name-form-cancel btn">Cancel</div> '
			. '</div>'
			. '</td></tr>'
			. '<tr><th>Device Key</th><td>'
			. '<code>' . htmlspecialchars( $info['deviceKey'] ) . '</code>'
			. '</td></tr>'
			. '<tr><th>Device Type</th><td>'
				. $info['deviceType']
			. '</td></tr>'
			. '<tr><th>Model</th><td>'
			. '<code>' . htmlspecialchars( $info['model'] ) . '</code>'
			. '</td></tr>'
			. '</tbody></table>';

		$html .= '<h3>Device Details History</h3>';
		if ( !$data['clientDetailGroups'] ) {
			$html .= '<div class="alert alert-info">Device has no clients.</div>';
		} else {
			$html .= '<table class="table table-striped">'
				. '<thead><tr><th>Clients</th><th>UA ID</th><th>Details</th></thead>'
				. '<tbody>';

			foreach ( $data['clientDetailGroups'] as $detailGroup ) {
				$html .= '<tr>'
					. '<td>';

				foreach( $detailGroup['clients'] as $client ) {
					$html .= '<div>'
							. html_tag( 'a', array( 'href' => $client['viewUrl'] ), '#' . $client['id'] )
						. '</div>';
				}

				$html .= '</td>'
					. '<td><code>' . $detailGroup['uaID'] . '</code></td>'
					. '<td>';

				foreach( $detailGroup['deviceDetails'] as $detailKey => $detail ) {
					$html .= '<div>'
						. '<strong>' . snakeCaseToTitle($detailKey) . "</strong>: " . $detail
						. '</div>';
				}

				$html .= '</td>'
					. '</tr>';
			}

			$html .= '</tbody></table>';
		}

		$html .= '<h3>Log</h3>';
		if ( !$data['results'] ) {
			$html .= '<div class="alert alert-info">Device has no run log.</div>';
		} else {
			$html .= '<table class="table table-striped">'
				. '<thead><tr><th>Result</th><th>Project</th><th>Client</th></th><th>Job</th><th>Run</th><th>Status</th>'
				. '<tbody>';

			foreach ( $data['results'] as $run ) {
				$html .= '<tr>'
					. '<td>' . html_tag( 'a', array( 'href' => $run['viewUrl'] ), '#' . $run['id'] ) . '</td>'
					. '<td>' . ( $run['project']
						? html_tag( 'a', array( 'href' => $run['project']['viewUrl'] ), $run['project']['display_title'] )
						: '-' ) . '</td>'
					. '<td>' . html_tag( 'a', array( 'href' => $run['viewClientUrl'] ), '#' . $run['clientId'] ) . '</td>'
					. '<td class="swarm-device-log-job-name">' . ( $run['job']
						? html_tag( 'a', array( 'href' => $run['job']['viewUrl'] ), $run['job']['nameText'] )
						: '<em>Job has been deleted</em>' ) . '</td>'
					. '<td class="swarm-device-log-run-name">' . ( $run['job'] && $run['run']
						? html_tag( 'a', array( 'href' => $run['job']['viewUrl'] ), $run['run']['name'] )
						: '<em>Job has been deleted</em>' ) . '</td>'
					. JobPage::getJobStatusHtmlCell( $run['status'] )
					. '</tr>';
			}

			$html .= '</tbody></table>';
		}

		return $html;
	}

}
