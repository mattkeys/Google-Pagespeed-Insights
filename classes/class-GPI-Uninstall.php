<?php
/**
 * =======================================
 * Google Pagespeed Insights Uninstall
 * =======================================
 * 
 * 
 * @author Matt Keys <https://profiles.wordpress.org/mattkeys>
 */

if ( ! defined( 'GPI_PLUGIN_FILE' ) ) {
	die();
}

class GPI_Uninstall
{

	static function uninstall()
	{
		global $wpdb;

		$gpi_page_stats			= $wpdb->prefix . 'gpi_page_stats';
		$gpi_page_reports		= $wpdb->prefix . 'gpi_page_reports';
		$gpi_page_blacklist		= $wpdb->prefix . 'gpi_page_blacklist';
		$gpi_api_error_logs		= $wpdb->prefix . 'gpi_api_error_logs';
	 
		$wpdb->query( "DROP TABLE $gpi_page_stats" );
		$wpdb->query( "DROP TABLE $gpi_page_reports" );
		$wpdb->query( "DROP TABLE $gpi_page_blacklist" );
		$wpdb->query( "DROP TABLE $gpi_api_error_logs" );

		delete_option('gpagespeedi_options');
		delete_option('gpagespeedi_ui_options');
		delete_option('gpagespeedi_upgrade_recheck_required');
		delete_option('gpi_progress');

		wp_clear_scheduled_hook( 'gpi_prune_logs' );
	}

}

register_uninstall_hook( GPI_PLUGIN_FILE, array( 'GPI_Uninstall', 'uninstall' ) );
