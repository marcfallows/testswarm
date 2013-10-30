	/**
 * JavaScript file for the "devices" page in the browser.
 *
 * @author Marc Fallows, 2013
 * @since 2.0.0
 * @package TestSwarm
 */
jQuery(function ( $ ) {
	var $savedeviceErr = $( '.swarm-savedevice-error' ),
		indicatorText,
		$indicator;

	indicatorText = document.createTextNode( '' );
	$indicator = $( '<span class="btn pull-right disabled"> <i class="icon-refresh"></i></span>' )
		.prepend( indicatorText )
		.css( 'opacity', 0 );

	function indicateAction( label ) {
		indicatorText.nodeValue = label;
		$indicator.stop( true, true ).css( 'opacity', 1 );
	}

	function actionComplete() {
		setTimeout( function () {
			$indicator.stop(true, true).animate({
				opacity: 0
			});
		}, 10 );
	}

	function savedeviceFail( data ) {
		$savedeviceErr.hide().text( data.error && data.error.info || 'Action failed.' ).slideDown();
	}

	$( 'table.swarm-device').before( $indicator );

	$('.device-name-edit').click(function(){
		$('.device-name-edit').hide();
		$('.device-name-form-input').val($('.device-name-source').text());
		$('.device-name-form').show();
	});

	$('.device-name-form-cancel').click(function(){
		$('.device-name-edit').show();
		$('.device-name-form').hide();
	});

	$('.device-name-form-submit').click(function(){

		$savedeviceErr.hide();
		var newDeviceName = $('.device-name-form-input').val();

		if(newDeviceName == $('.device-name-source').text()){
			$('.device-name-edit').show();
			$('.device-name-form').hide();
			return;
		}

		indicateAction( 'saving device' );

		$.ajax({
			url: SWARM.conf.web.contextpath + 'api.php',
			type: 'POST',
			data: {
				action: 'savedevice',
				device_id: SWARM.deviceInfo.id,
				name: newDeviceName
			},
			dataType: 'json',
			success: function ( data ) {
				actionComplete();

				if ( data.savedevice === 'ok' ) {
					$('.device-name').text(newDeviceName);
					$('.device-name-edit').show();
					$('.device-name-form').hide();
					return;
				}
				savedeviceFail( data );
			},
			error: function ( error ) {
				actionComplete();
				savedeviceFail( error );
			}
		});
	});
});
