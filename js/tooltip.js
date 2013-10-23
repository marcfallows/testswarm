
/**
* JavaScript file for the bootstrap tooltip API
*
* @author Marc Fallows, 2013
* @since 2.0.0
* @package TestSwarm
*/
jQuery(function ( $ ) {
	$('body').tooltip({
		selector: 'a[rel="tooltip"], [data-toggle="tooltip"]'
	});
});
