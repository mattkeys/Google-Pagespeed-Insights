(function($) {
  $(document).ready(function(){

  	if($('#gpi_status_ajax').length > 0) {
		$.fn.gpiCheckStatus = function() {
			$.post(
				GPI_Ajax.ajaxurl,
				{
					// wp ajax action
					action : 'gpi_check_status',

					// send the nonce along with the request
					gpiNonce : GPI_Ajax.gpiNonce
				},
				function( response ) {
					if(response == 'nonce_failure') {
						return;
					}
					if(response == 'done') {
						$('#gpi_status_ajax').hide();
						$('#gpi_status_finished').show();
						clearInterval(gpi_interval_id);
					} else {
						$('#gpi_status_ajax').html('<div class="loading_bar_shell"><div class="reportscore_outter_bar"><div class="reportscore_inner_bar" style="width:' + response + '%;"></div></div><span>' + response + '%</span></div>');
					}
				}
			);
			return false;
		}
		var gpi_interval_id = setInterval(function() {
			$('#gpi_status_ajax').gpiCheckStatus();
		}, 2000);
	}

  });
})(jQuery);