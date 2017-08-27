<?php
/**
 * Template Part - Messages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( get_option( 'gpagespeedi_upgrade_recheck_required' ) ) : ?>
	<div id="gpi-upgrade-notice">
		<div class="inner">
			<div class="gpi-icon logo">
				<img src="<?php echo GPI_PUBLIC_PATH; ?>assets/images/icon.svg" width="144px" height="100px" alt="Google Pagespeed Insights for WordPress" />
			</div>
			<div class="content">
				<h2>
					<?php _e( 'Report Updates Required', 'gpagespeedi' ); ?>
				</h2>
				<p>
					<?php _e( 'Thank you for updating to the latest version of Google Pagespeed Insights for WordPress!', 'gpagespeedi' ); ?>
				</p>
				<p>
					<?php echo __( 'Version', 'gpagespeedi' ) . ' ' . GPI_VERSION . ' ' . __( 'requires some updates to the way Pagespeed reports are stored to take advantage of the latest plugin updates. You will notice some missing report functionality until all pages have been rechecked.', 'gpagespeedi' ); ?>
				</p>
				<p>
					<a href="<?php echo admin_url( 'tools.php?page=google-pagespeed-insights&amp;render=' . $_GET['render'] . '&amp;action=reports_update' ); ?>" class="button button-primary"><?php _e( 'Recheck Pagespeed Reports Now', 'gpagespeedi' ); ?></a>
				</p>
			</div>
		</div>
	</div>
<?php endif; ?>
<?php if ( $this->gpi_options['google_developer_key'] == '' && 'options' != $admin_page ) : ?>
	<div id="message" class="error">
		<p><strong><?php _e( 'You must enter your Google API key to use this plugin! Enter your API key in the', 'gpagespeedi' ); ?> <a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=options"><?php _e( 'Options', 'gpagespeedi' ); ?></a></strong>.</p>
	</div>
<?php endif; ?>
<?php if ( $this->gpi_options['bad_api_key'] && 'options' != $admin_page ) : ?>
	<div id="message" class="error">
		<p><strong><?php _e( 'The Google Pagespeed API Key you entered appears to be invalid. Please update your API key in the', 'gpagespeedi' ); ?> <a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=options"><?php _e( 'Options', 'gpagespeedi' ); ?></a></strong>.</p>
	</div>
<?php endif; ?>
<?php if ( $this->gpi_options['pagespeed_disabled'] && 'options' != $admin_page ) : ?>
	<div id="message" class="error">
		<p><strong><?php _e( 'The "PageSpeed Insights API" service is not enabled. To enable it, please visit the', 'gpagespeedi' ); ?> <a href="https://code.google.com/apis/console/" target="_blank"><?php _e( 'Google API Console', 'gpagespeedi' ); ?></a></strong>.</p>
	</div>
<?php endif; ?>
<?php if ( $this->gpi_options['api_restriction'] ) : ?>
	<div id="message" class="error">
		<p><strong><?php _e( 'This referrer or IP address is restricted from using your API Key. To change your API Key restrictions, please visit the', 'gpagespeedi' ); ?> <a href="https://code.google.com/apis/console/" target="_blank"><?php _e( 'Google API Console', 'gpagespeedi' ); ?></a></strong>.</p>
	</div>
<?php endif; ?>
<?php if ( $action_message = $this->gpi_ui_options['action_message'] ) :
		if ( ! is_array( $action_message ) ) : ?>
			<div id="message" class="updated">
				<p><?php echo $action_message; ?></p>
			</div>
		<?php elseif ( isset( $action_message['type'] ) && isset( $action_message['message'] ) ) : ?>
			<div id="message" class="<?php echo $action_message['type']; ?>">
				<p><?php echo $action_message['message']; ?></p>
			</div>
		<?php endif; ?>
<?php endif; ?>
<?php if ( $error_message = get_option('gpi_error_message') ) : ?>
	<div id="message" class="error">
		<p><?php echo $error_message; ?></p>
	</div>
<?php endif; ?>
<?php if ( isset( $_GET['render'] ) && 'logs' == $_GET['render'] && ! $this->gpi_options['log_api_errors'] ) : ?>
	<div id="message" class="error">
		<p><strong><?php _e( 'API error logging is disabled. Enable "Log API Exceptions" to record new errors.', 'gpagespeedi' ); ?> <a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=options"><?php _e( 'Options', 'gpagespeedi' ); ?></a></strong></p>
	</div>
<?php endif; ?>
<?php if ( $this->gpi_options['new_ignored_items'] ) : ?>
	<div id="message" class="notice notice-error is-dismissible">
		<p><strong><?php _e( 'One or more URLs could not be reached by Google Pagespeed Insights and have automatically been added to the', 'gpagespeedi' ); ?> <a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=ignored-urls"><?php _e( 'Ignored URLs', 'gpagespeedi' ); ?></a></strong>.</p>
	</div>
<?php endif; ?>
<?php if ( $this->gpi_options['backend_error'] ) : ?>
	<div id="message" class="error">
		<p><strong><?php _e( 'An error has been encountered while checking one or more URLs. Possible causes: <br /><br />Daily API Limit Exceeded <a href="https://code.google.com/apis/console" target="_blank">Check API Usage</a> <br />API Key user limit exceeded <a href="https://code.google.com/apis/console" target="_blank">Check API Usage</a> <br />the URL is not publicly accessible or is bad. <br /><br />The URL(s) have been added to the', 'gpagespeedi' ); ?> <a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=ignored-urls"><?php _e( 'Ignored URLs', 'gpagespeedi' ); ?></a></strong></p>
	</div>
<?php endif; ?>
<?php if ( $worker_status = apply_filters( 'gpi_check_status', false ) ) : ?>
	<div id="message" class="updated">
		<?php if ( 'disabled' != $this->gpi_options['heartbeat'] ) : ?>
			<span>
				<p id="gpi_status_abort" style="font-size: 13px; display: none;"><?php _e( 'Google Pagespeed has successfully stopped checking pages due to a user requested abort.', 'gpagespeedi' ); ?></p>
				<p id="gpi_status_finished" style="font-size: 13px; display: none;"><?php _e( 'Google Pagespeed has finished checking pagespeed scores.', 'gpagespeedi' );?> <a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=report-list"><?php _e( 'See new results', 'gpagespeedi' ); ?>.</a></p>
				<p id="gpi_status_ajax" class="ellipsis" style="font-size: 13px;"><?php _e( 'Google Pagespeed is running in the background ', 'gpagespeedi' ); ?></p>
			</span>
		<?php else : ?>
			<span><p id="gpi_status_noajax" style="font-size: 13px;"><?php _e( 'Google Pagespeed is running in the background. Progress... ', 'gpagespeedi' ); ?><span id="progress"></span> <?php _e( 'URL(s) checked', 'gpagespeedi' ); ?>. <a href="javascript:location.reload(true);"><?php _e( 'Refresh to update progress or see new results.', 'gpagespeedi' ); ?></a></p></span>
		<?php endif; ?>
	</div>

	<?php if ( isset( $_GET['render'] ) && 'options' == $_GET['render'] ) : ?>
		<div id="message" class="notice notice-warning">
			<p><?php _e('Google Pagespeed Options cannot be changed while Pagespeed is running. Please wait until it has finished to make any changes.', 'gpagespeedi' ); ?></p>
		</div>
	<?php endif; ?>
<?php endif; ?>
<?php if ( ! $worker_status && ! $this->gpi_options['last_run_finished'] ) : ?>
	<div id="message" class="error">
		<?php if ( apply_filters( 'gpi_set_time_limit_disabled', false ) ) : ?>
			<p><strong><?php _e( 'The last pagespeed report scan failed to finish successfully. We have detected that your server may not allow the maximum execution time to be overridden by this plugin. If you continue to experience problems with pagespeed report scans failing to complete, try setting the Maximum Script Run Time in the Advanced Configuration section on the', 'gpagespeedi' ); ?>  <a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=options"><?php _e( 'Options Page', 'gpagespeedi' ); ?></a></strong>.</p>
		<?php else : ?>
			<p><strong><?php _e( 'The last pagespeed report scan failed to finish successfully. If you continue to experience problems with pagespeed report scans failing to complete, try increasing the Maximum Execution Time, or setting the Maximum Script Run Time in the Advanced Configuration section on the', 'gpagespeedi' ); ?>  <a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=options"><?php _e( 'Options Page', 'gpagespeedi' ); ?></a></strong>.</p>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php
	// Clear any one-time messages from above
	do_action( 'gpi_update_option', 'action_message', false, 'gpagespeedi_ui_options' );
	do_action( 'gpi_update_option', 'last_run_finished', true, 'gpagespeedi_options' );
	do_action( 'gpi_update_option', 'new_ignored_items', false, 'gpagespeedi_options' );
	do_action( 'gpi_update_option', 'backend_error', false, 'gpagespeedi_options' );
	delete_option( 'gpi_error_message' );
?>