( function( $ ) {

	var status;

	$(document).ready( function() {
		$('.boxheader.large.toggle').on( 'click', function() {
			$(this).find('span.right').toggleClass('open');
			$(this).next('.padded').toggle();
		});
	});

	if ( 'disabled' == GPI_Global.heartbeat && GPI_Global.progress ) {
		$(document).ready( function() {
			$('#gpi_status_noajax #progress').html( GPI_Global.progress );
		});
	}

	// Give heartbeat a kick to get going
	if ( 'disabled' != GPI_Global.heartbeat ) {
		wp.heartbeat.interval( 'fast' );
	}

	$(document).tooltip({
		hide			: 500,
		show			: 200,
		position		: { my: "left+20px top", at: "left bottom", collision: "flipfit" },
		tooltipClass	: 'pos-absolute',
		content			: function () {
			return $(this).prop('title');
		},
		open: function( event, ui ) {
			if ( typeof( event.originalEvent ) === 'undefined') {
				return false;
			}

			var $id = $(ui.tooltip).attr('id');
			$('div.ui-tooltip').not('#' + $id).remove();
		},
		close: function( event, ui ) {
			ui.tooltip.hover( function() {
				$(this).stop(true).fadeTo(400, 1); 
			}, function() {
				$(this).fadeOut('400', function() {
					$(this).remove();
				});
			});
		}
	});

	$(document).ready( function() {
		$('#recheck_all_pages').on( 'change', function() {
			var start_scan = $('#start_scan');
			var href = $( start_scan ).attr('href');

			if ( this.checked ) {
				$( start_scan ).attr( 'href', href + '&recheck_all_pages' );
			} else {
				$( start_scan ).attr( 'href', href.replace( '&recheck_all_pages', '' ) );
			}
		});
	});

	$(document).on( 'heartbeat-send', function( e, data ) {
		if ( 'disabled' != GPI_Global.heartbeat && 'done' != status ) {
			wp.heartbeat.interval( GPI_Global.heartbeat );
			data['gpi_heartbeat'] = 'progress';
		}
	});

	$(document).on( 'heartbeat-tick', function( e, data ) {
		if ( ! data['gpi_progress'] ) {
			return;
		}

		status = data['gpi_progress'];

		if ( 'done' == status ) {
			$('#gpi_status_ajax').hide();
			$('#gpi_status_finished').show();
		} else if ( 'abort' == status ) {
			$('#gpi_status_ajax').hide();
			$('#gpi_status_abort').show();
		} else {
			$('#gpi_status_ajax').prop( 'title', data['gpi_progress_tooltip'] );
			$('#gpi_status_ajax').removeClass('ellipsis').html('<div class="loading_bar_shell"><div class="reportscore_outter_bar"><div class="reportscore_inner_bar" style="width:' + status + '%;"></div></div><span>' + status + '%</span></div>');
		}
	});

})( jQuery );