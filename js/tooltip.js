
/**
* JavaScript file for the bootstrap tooltip API
*
* @author Marc Fallows, 2013
* @since 2.0.0
* @package TestSwarm
*/

// For performance reasons, the tooltip and popover data-apis are opt in, 
// meaning you must initialize them yourself.
// http://getbootstrap.com/2.3.2/javascript.html#tooltips

jQuery(function ( $ ) {
	$('body').tooltip({
		selector: 'a[rel="tooltip"], [data-toggle="tooltip"]'
	});
});
