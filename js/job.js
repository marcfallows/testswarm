/**
 * JavaScript file for the "job" page in the browser.
 *
 * @author John Resig, 2008-2011
 * @since 0.1.0
 * @package TestSwarm
 */
jQuery(function ( $ ) {
	var updateInterval = SWARM.conf.web.ajaxUpdateInterval * 1000,
		$wipejobErr = $( '.swarm-wipejob-error' ),
		$addbrowserstojobErr = $( '.swarm-addbrowserstojob-error' ),
		$targetTable = $( 'table.swarm-results' ),
		refreshTableTimout, indicatorText, $indicator;

	indicatorText = document.createTextNode( 'updating' );
	$indicator = $( '<span class="btn pull-right disabled"> <i class="icon-refresh"></i></span>' )
		.prepend( indicatorText )
		.css( 'opacity', 0 );

	function indicateAction( label ) {
		// Make sure any scheduled action is dequeued, we're doing something now.
		if ( refreshTableTimout ) {
			clearTimeout( refreshTableTimout );
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

		var $browserMenuDropdown = $(".swarm-browsermenu .dropdown-menu");

		if( $browserMenuDropdown.is(':visible') ) {
			refreshTableTimout = setTimeout( refreshTable, updateInterval );
			return;
		}

		indicateAction( 'updating' );

		$.get( window.location.href )
			.done( function ( html ) {
				var tableHtml;

				tableHtml = $( html ).find( 'table.swarm-results' ).html();
				if ( tableHtml !== $targetTable.html() ) {
					$targetTable.html( tableHtml );
				}
			})
			.complete( function () {
				// Wether done or failed: Clean up and schedule next update
				actionComplete();
				refreshTableTimout = setTimeout( refreshTable, updateInterval );
			});
	}

	// Schedule first update
	refreshTableTimout = setTimeout( refreshTable, updateInterval );

	function wipejobFail( data ) {
		$wipejobErr.hide().text( data.error && data.error.info || 'Action failed.' ).slideDown();
	}

	function addbrowserstojobFail( data ) {
		$addbrowserstojobErr.hide().text( data.error && data.error.info || 'Action failed.' ).slideDown();
	}

	function resetRun( $el ) {
		if ( $el.data( 'runStatus' ) !== 'new' ) {
			$.ajax({
				url: SWARM.conf.web.contextpath + 'api.php',
				type: 'POST',
				data: {
					action: 'wiperun',
					job_id: $el.data( 'jobId' ),
					run_id: $el.data( 'runId' ),
					client_id: $el.data( 'clientId' ),
					useragent_id: $el.data( 'useragentId' ),
					authID: SWARM.auth.project.id,
					authToken: SWARM.auth.sessionToken
				},
				dataType: 'json',
				success: function ( data ) {
					if ( data.wiperun && data.wiperun.result === 'ok' ) {
						$el.empty().attr( 'class', 'swarm-status swarm-status-new' );
						refreshTable();
					}
				}
			});
		}
	}

	$( 'table.swarm-results' ).prev().before( $indicator );

	if ( SWARM.auth ) {

		// This needs to bound as a delegate, because the table auto-refreshes.
		$targetTable.on( 'click', '.swarm-reset-run-single', function () {
			resetRun( $( this ).closest( 'td' ) );
		});

		$( '.swarm-add-browsers' ).click( function () {

			$addbrowserstojobErr.hide();
			indicateAction( 'adding browsers' );

			var jobData = {
				action: 'addbrowserstojob',
				job_id: SWARM.jobInfo.id,
				authID: SWARM.auth.project.id,
				authToken: SWARM.auth.sessionToken
			};

			var formData = $('.swarm-add-browsers-form').serialize();
			$('.swarm-add-browsers-form')[0].reset();

			var combinedData = formData + '&' + $.param(jobData);

			$.ajax({
				url: SWARM.conf.web.contextpath + 'api.php',
				type: 'POST',
				data: combinedData,
				dataType: 'json',
				success: function ( data ) {
					actionComplete();
					if ( data.addbrowserstojob && data.addbrowserstojob.result === 'ok' ) {
						refreshTable();
						return;
					}
					addbrowserstojobFail( data );
				},
				error: function ( error ) {
					actionComplete();
					addbrowserstojobFail( error );
				}
			});
		} );

		$targetTable.on( 'click', '.swarm-reset-browser-runs-failed', function () {

			var $th = $( this ).closest( 'th' );
			var $userAgentData = $th.data( 'useragentId' );

			var $els = $( 'td[data-run-status="failed"], td[data-run-status="error"], td[data-run-status="timedout"]' ).filter( $('td[data-useragent-id="' + $userAgentData + '"') );

			if ( !$els.length || !window.confirm( 'Are you sure you want to reset the suspended runs for this browser?' ) ) {
				return;
			}
			$els.each( function () {
				resetRun( $( this ) );
			});
		} );

		$( '.swarm-reset-runs-failed' ).on( 'click', function () {


			var $els = $( 'td[data-run-status="failed"], td[data-run-status="error"], td[data-run-status="timedout"]' );
			if ( !$els.length || !window.confirm( 'Are you sure you want to reset all failed runs?' ) ) {
				return;
			}
			$els.each( function () {
				resetRun( $( this ) );
			});
		} );

		$( '.swarm-delete-job' ).click( function () {
			if ( !window.confirm( 'Are you sure you want to delete this job?' ) ) {
				return;
			}
			$wipejobErr.hide();
			indicateAction( 'deleting' );

			$.ajax({
				url: SWARM.conf.web.contextpath + 'api.php',
				type: 'POST',
				data: {
					action: 'wipejob',
					job_id: SWARM.jobInfo.id,
					type: 'delete',
					authID: SWARM.auth.project.id,
					authToken: SWARM.auth.sessionToken
				},
				dataType: 'json',
				success: function ( data ) {
					if ( data.wipejob && data.wipejob.result === 'ok' ) {
						// Right now the only user authorized to delete a job is the creator,
						// the below code makes that assumption.
						window.location.href = SWARM.conf.web.contextpath + 'project/' + SWARM.auth.project.id;
						return;
					}
					actionComplete();
					wipejobFail( data );
				},
				error: function ( error ) {
					actionComplete();
					wipejobFail( error );
				}
			});
		} );

		$( '.swarm-reset-runs' ).click( function () {
			if ( !window.confirm( 'Are you sure you want to reset this job?' ) ) {
				return;
			}
			$wipejobErr.hide();
			indicateAction( 'resetting' );

			$.ajax({
				url: SWARM.conf.web.contextpath + 'api.php',
				type: 'POST',
				data: {
					action: 'wipejob',
					job_id: SWARM.jobInfo.id,
					type: 'reset',
					authID: SWARM.auth.project.id,
					authToken: SWARM.auth.sessionToken
				},
				dataType: 'json',
				success: function ( data ) {
					actionComplete();
					if ( data.wipejob && data.wipejob.result === 'ok' ) {
						refreshTable();
						return;
					}
					wipejobFail( data );
				},
				error: function ( error ) {
					actionComplete();
					wipejobFail( error );
				}
			});
		} );

		$( '.swarm-reset-runs-suspended' ).click( function () {
			if ( !window.confirm( 'Are you sure you want to reset this job?' ) ) {
				return;
			}
			$wipejobErr.hide();
			indicateAction( 'resetting' );

			$.ajax({
				url: SWARM.conf.web.contextpath + 'api.php',
				type: 'POST',
				data: {
					action: 'wipejob',
					job_id: SWARM.jobInfo.id,
					type: 'resetsuspended',
					authID: SWARM.auth.project.id,
					authToken: SWARM.auth.sessionToken
				},
				dataType: 'json',
				success: function ( data ) {
					actionComplete();
					if ( data.wipejob && data.wipejob.result === 'ok' ) {
						refreshTable();
						return;
					}
					wipejobFail( data );
				},
				error: function ( error ) {
					actionComplete();
					wipejobFail( error );
				}
			});
		} );

		$( '.swarm-suspend-runs' ).click( function () {
			if ( !window.confirm( 'Are you sure you want to suspend this job?' ) ) {
				return;
			}
			$wipejobErr.hide();
			indicateAction( 'suspending' );

			$.ajax({
				url: SWARM.conf.web.contextpath + 'api.php',
				type: 'POST',
				data: {
					action: 'wipejob',
					job_id: SWARM.jobInfo.id,
					type: 'suspend',
					authID: SWARM.auth.project.id,
					authToken: SWARM.auth.sessionToken
				},
				dataType: 'json',
				success: function ( data ) {
					actionComplete();
					if ( data.wipejob && data.wipejob.result === 'ok' ) {
						refreshTable();
						return;
					}
					wipejobFail( data );
				},
				error: function ( error ) {
					actionComplete();
					wipejobFail( error );
				}
			});
		} );

		$targetTable.on( 'click', '.swarm-delete-browser-runs', function () {
			var $el = $( this ).closest( 'th' );

			if ( !window.confirm( 'Are you sure you want to delete the runs for this browser?' ) ) {
				return;
			}

			$wipejobErr.hide();
			indicateAction( 'deleting' );

			wipeBrowserJob($el, 'delete');
		});

		$targetTable.on( 'click', '.swarm-reset-browser-runs', function () {
			var $el = $( this ).closest( 'th' );

			if ( !window.confirm( 'Are you sure you want to reset the runs for this browser?' ) ) {
				return;
			}

			$wipejobErr.hide();
			indicateAction( 'reset' );

			wipeBrowserJob($el, 'reset');
		});

		$targetTable.on( 'click', '.swarm-reset-browser-runs-suspended', function () {
			var $el = $( this ).closest( 'th' );

			if ( !window.confirm( 'Are you sure you want to reset the suspended runs for this browser?' ) ) {
				return;
			}

			$wipejobErr.hide();
			indicateAction( 'resetting suspended' );

			wipeBrowserJob($el, 'resetsuspended');
		});

		$targetTable.on( 'click', '.swarm-suspend-browser-runs', function () {
			var $el = $( this ).closest( 'th' );

			if ( !window.confirm( 'Are you sure you want to suspend the runs for this browser?' ) ) {
				return;
			}

			$wipejobErr.hide();
			indicateAction( 'suspending' );

			wipeBrowserJob($el, 'suspend');
		});

		function wipeBrowserJob($el, $type) {

			$wipejobErr.hide();
			indicateAction( 'resetting' );

			$.ajax({
				url: SWARM.conf.web.contextpath + 'api.php',
				type: 'POST',
				data: {
					action: 'wipebrowserjob',
					job_id: SWARM.jobInfo.id,
					useragent_id: $el.data( 'useragentId' ),
					type: $type,
					authID: SWARM.auth.project.id,
					authToken: SWARM.auth.sessionToken
				},
				dataType: 'json',
				success: function ( data ) {
					actionComplete();
					if ( data.wipebrowserjob && data.wipebrowserjob.result === 'ok' ) {
						refreshTable();
						return;
					}
					wipejobFail( data );
				},
				error: function ( error ) {
					actionComplete();
					wipejobFail( error );
				}
			});
		}
	}

});
