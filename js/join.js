/**
 * JavaScript file for the "join" page in the browser.
 *
 * @author Marc Fallows, 2013
 * @since 2.0.0
 * @package TestSwarm
 */
(function ( $, SWARM, require ) {

	$( function () {

		if ( typeof deviceManager == "undefined" ) {
			$("#join-status").html("Device manager does not exist");
			return;
		}

		deviceManager.identify().then(function (deviceDetails){

			var runQueryParams = {
				model: deviceDetails.model,
				device_key: deviceDetails.device_key,
				device_type: deviceDetails.device_type,
				details: {}
			};

			$.each(deviceDetails, function(key, detail){
				if(typeof runQueryParams[key] == "undefined"){
					runQueryParams.details[key] = detail;
				}
			});

			var runUrl = SWARM.conf.web.contextpath + "run/" + SWARM.client_name;
			var runQuery = "?" + $.param(runQueryParams);

			window.location.replace(runUrl + runQuery);
		}, function(error){
			$("#join-status").html("Error: " + error.message);
		});
	});

}( jQuery, SWARM, require ) );
