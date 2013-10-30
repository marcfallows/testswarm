	/**
 * JavaScript file for the "devices" page in the browser.
 *
 * @author Marc Fallows, 2013
 * @since 2.0.0
 * @package TestSwarm
 */
jQuery(function ( $ ) {
	var updateInterval = SWARM.conf.web.ajaxUpdateInterval * 1000,
		$targetTable = $( 'table.swarm-devices' ),
		refreshTableTimeout, indicatorText, $indicator;

	indicatorText = document.createTextNode( 'updating' );
	$indicator = $( '<span class="btn pull-right disabled"> <i class="icon-refresh"></i></span>' )
		.prepend( indicatorText )
		.css( 'opacity', 0 );

	function indicateAction( label ) {
		// Make sure any scheduled action is dequeued, we're doing something now.
		if ( refreshTableTimeout ) {
			clearTimeout( refreshTableTimeout );
		}
		// $.text() is a getter
		// $.fn.text() does empty/append, which means the reference is no meaningless
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

	function refreshTable() {

		indicateAction( 'updating' );

		$.get( window.location.href )
			.done( function ( html ) {

				var $table = $( html ).find( 'table.swarm-devices'),
					tableHtml;

				if ( $.fn.prettyDate ) {
					$table.find( '.pretty' ).prettyDate();
				}

				var tableHtml = $table.html();

				if ( tableHtml !== $targetTable.html() ) {
					$targetTable.html( tableHtml );
				}
			})
			.complete( function () {
				// Whether done or failed: Clean up and schedule next update
				actionComplete();
				refreshTableTimeout = setTimeout( refreshTable, updateInterval );
			});
	}

	// Schedule first update
	refreshTableTimeout = setTimeout( refreshTable, updateInterval );

	$targetTable.before( $indicator );
});
