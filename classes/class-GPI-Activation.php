<?php
/**
 * =======================================
 * Google Pagespeed Insights Activation
 * =======================================
 * 
 * 
 * @author Matt Keys <https://profiles.wordpress.org/mattkeys>
 */

if ( ! defined( 'GPI_PLUGIN_FILE' ) ) {
	die();
}

class GPI_Activation
{

	static function upgrade( $gpagespeedi_options, $gpagespeedi_ui_options, $update_options = true )
	{
		// In v4.0 data structure for page/summary stats changed dramaticly and existing reports/snapshots are now longer compatible and must be removed
		if ( ! isset( $gpagespeedi_options['version'] ) || version_compare( $gpagespeedi_options['version'], '4.0', '<' ) ) {
			global $wpdb;
			$gpi_page_stats_table			= $wpdb->prefix . 'gpi_page_stats';
			$gpi_page_reports_table			= $wpdb->prefix . 'gpi_page_reports';
			$gpi_summary_snapshots_table	= $wpdb->prefix . 'gpi_summary_snapshots';

			$wpdb->query( "ALTER TABLE $gpi_page_stats_table DROP COLUMN desktop_page_stats" );
			$wpdb->query( "ALTER TABLE $gpi_page_stats_table DROP COLUMN mobile_page_stats" );
			$wpdb->query( "ALTER TABLE $gpi_page_reports_table DROP COLUMN rule_impact" );

			$wpdb->query( "TRUNCATE TABLE $gpi_page_stats_table" );
			$wpdb->query( "TRUNCATE TABLE $gpi_page_reports_table" );
			$wpdb->query( "TRUNCATE TABLE $gpi_page_reports_table" );
		}

		if ( $update_options ) {
			$update_values = array(
				'google_developer_key'		=> isset( $gpagespeedi_options['google_developer_key'] ) ? $gpagespeedi_options['google_developer_key'] : '',
				'response_language' 		=> isset( $gpagespeedi_options['response_language'] ) ? $gpagespeedi_options['response_language'] : 'en_US',
				'strategy'					=> isset( $gpagespeedi_options['strategy'] ) ? $gpagespeedi_options['strategy'] : 'desktop',
				'store_screenshots'			=> isset( $gpagespeedi_options['store_screenshots'] ) ? $gpagespeedi_options['store_screenshots'] : 0,
				'max_execution_time' 		=> isset( $gpagespeedi_options['max_execution_time'] ) ? $gpagespeedi_options['max_execution_time'] : 300,
				'max_run_time' 				=> isset( $gpagespeedi_options['max_run_time'] ) ? $gpagespeedi_options['max_run_time'] : 0,
				'sleep_time'		 		=> isset( $gpagespeedi_options['sleep_time'] ) ? $gpagespeedi_options['sleep_time'] : 0,
				'recheck_interval' 			=> isset( $gpagespeedi_options['recheck_interval'] ) ? $gpagespeedi_options['recheck_interval'] : 86400,
				'use_schedule' 				=> isset( $gpagespeedi_options['use_schedule'] ) ? $gpagespeedi_options['use_schedule'] : true,
				'check_pages' 				=> isset( $gpagespeedi_options['check_pages'] ) ? $gpagespeedi_options['check_pages'] : true,
				'check_posts' 				=> isset( $gpagespeedi_options['check_posts'] ) ? $gpagespeedi_options['check_posts'] : true,
				'cpt_whitelist'				=> isset( $gpagespeedi_options['cpt_whitelist'] ) ? $gpagespeedi_options['cpt_whitelist'] : '',
				'check_categories' 			=> isset( $gpagespeedi_options['check_categories'] ) ? $gpagespeedi_options['check_categories'] : true,
				'check_custom_urls' 		=> isset( $gpagespeedi_options['check_custom_urls'] ) ? $gpagespeedi_options['check_custom_urls'] : true,
				'first_run_complete' 		=> isset( $gpagespeedi_options['first_run_complete'] ) ? $gpagespeedi_options['first_run_complete'] : false,
				'last_run_finished' 		=> isset( $gpagespeedi_options['last_run_finished'] ) ? $gpagespeedi_options['last_run_finished'] : true,
				'bad_api_key'		 		=> isset( $gpagespeedi_options['bad_api_key'] ) ? $gpagespeedi_options['bad_api_key'] : false,
				'pagespeed_disabled' 		=> isset( $gpagespeedi_options['pagespeed_disabled'] ) ? $gpagespeedi_options['pagespeed_disabled'] : false,
				'api_restriction'			=> isset( $gpagespeedi_options['api_restriction'] ) ? $gpagespeedi_options['api_restriction'] : false,
				'new_ignored_items'		 	=> isset( $gpagespeedi_options['new_ignored_items'] ) ? $gpagespeedi_options['new_ignored_items'] : false,
				'backend_error'				=> isset( $gpagespeedi_options['backend_error'] ) ? $gpagespeedi_options['backend_error'] : false,
				'log_api_errors'			=> isset( $gpagespeedi_options['log_api_errors'] ) ? $gpagespeedi_options['log_api_errors'] : false,
				'new_activation_message'	=> false,
				'heartbeat'					=> isset( $gpagespeedi_options['heartbeat'] ) ? $gpagespeedi_options['heartbeat'] : 'fast',
				'mutex_id'					=> isset( $gpagespeedi_options['mutex_id'] ) ? $gpagespeedi_options['mutex_id'] : time() . rand(),
				'version'					=> GPI_VERSION
			);
			update_option( 'gpagespeedi_options', $update_values );

			$update_ui_values = array(
				'action_message'		 	=> isset( $gpagespeedi_ui_options['action_message'] ) ? $gpagespeedi_ui_options['action_message'] : false,
				'view_preference'			=> isset( $gpagespeedi_ui_options['view_preference'] ) ? $gpagespeedi_ui_options['view_preference'] : 'desktop',
			);
			update_option( 'gpagespeedi_ui_options', $update_ui_values );
		}

		self::db();
	}

	static function activation()
	{
		$default_values = array(
			'google_developer_key'		=> '', 				// Google API Developer Key
			'response_language' 		=> 'en_US', 		// Language for report response
			'strategy'					=> 'desktop',		// Generate reports for Desktop, Mobile, or Both
			'store_screenshots'			=> 0,				// Store loading screenshots in DB
			'max_execution_time' 		=> 300, 			// in seconds
			'max_run_time' 				=> 0,				// in seconds, 0 = no limit
			'sleep_time'		 		=> 0, 				// in seconds
			'recheck_interval' 			=> 86400, 			// in seconds
			'use_schedule' 				=> true,			// use wordpress wp_schedule_event
			'check_pages' 				=> true,			// check pages
			'check_posts' 				=> true,			// check the built in posts-type
			'cpt_whitelist'				=> '',				// whitelist of Custom Post Types to check
			'check_categories' 			=> true,			// check category indexes
			'check_custom_urls' 		=> true,			// check user entered custom URLs
			'first_run_complete' 		=> false,			// true if all pages have been checked once
			'last_run_finished' 		=> true, 			// true if the last check finished before max execution time
			'bad_api_key'		 		=> false, 			// true if API reports the API key is bad
			'pagespeed_disabled' 		=> false, 			// true if API reports the Pagespeed API not enabled		
			'api_restriction'			=> false,			// True if API reports that it cannot check pages from this IP/Hostname
			'new_ignored_items'		 	=> false, 			// true if new pages have been added to 'ignore' due to a bad request
			'backend_error'				=> false, 			// true if a 'backendErorr' is returned from the API
			'log_api_errors'			=> false,			// log uncaught API exceptions to txt files in FTP root
			'new_activation_message'	=> true, 			// display welcome messsage on first-time activation of plugin
			'heartbeat'					=> 'standard',		// Heartbeat refresh interval: fast, slow, standard, or disabled
			'mutex_id'					=> time() . rand(),	// Unique ID to prevent DB lock collision with other installations on the same MySQL server
			'version'					=> GPI_VERSION		// Internal version number used to trigger DB/Options updates in new releases
		);
		add_option( 'gpagespeedi_options', $default_values );

		$default_ui_values = array(
			'action_message'		 	=> false, 		// true if a message from an action needs to be delivered to the screen
			'view_preference'			=> 'desktop',	// Mobile or Desktop, the last report type viewed
		);
		add_option( 'gpagespeedi_ui_options', $default_ui_values );

		self::db();
	}

	static function db()
	{
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$gpi_page_stats_table			= $wpdb->prefix . 'gpi_page_stats';
		$gpi_page_reports_table			= $wpdb->prefix . 'gpi_page_reports';
		$gpi_page_blacklist_table		= $wpdb->prefix . 'gpi_page_blacklist';
		$gpi_custom_urls_table			= $wpdb->prefix . 'gpi_custom_urls';
		$gpi_summary_snapshots_table	= $wpdb->prefix . 'gpi_summary_snapshots';
		$gpi_api_error_logs_table		= $wpdb->prefix . 'gpi_api_error_logs';

		$charset_collate = $wpdb->get_charset_collate();

		$gpi_page_stats = "CREATE TABLE $gpi_page_stats_table (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			URL text NULL,
			response_code int(10) DEFAULT NULL,
			desktop_score int(10) DEFAULT NULL,
			mobile_score int(10) DEFAULT NULL,
			desktop_lab_data longtext,
			mobile_lab_data longtext,
			desktop_field_data longtext,
			mobile_field_data longtext,
			type varchar(200) DEFAULT NULL,
			object_id bigint(20) DEFAULT NULL,
			term_id bigint(20) DEFAULT NULL,
			custom_id bigint(20) DEFAULT NULL,
			desktop_last_modified varchar(20) NOT NULL,
			mobile_last_modified varchar(20) NOT NULL,
			force_recheck int(1) NOT NULL,
			PRIMARY KEY  (ID),
			KEY object_id (object_id),
			KEY term_id (term_id),
			KEY custom_id (custom_id)
		) $charset_collate;";

		$gpi_page_reports = "CREATE TABLE $gpi_page_reports_table (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			page_id bigint(20) NOT NULL,
			strategy varchar(20) NOT NULL,
			rule_key varchar(200) NOT NULL,
			rule_name varchar(200) DEFAULT NULL,
			rule_type varchar(200) DEFAULT NULL,
			rule_score decimal(5,2) DEFAULT NULL,
			rule_blocks longtext,
			PRIMARY KEY  (ID),
			KEY page_id (page_id)
		) $charset_collate;";

		$gpi_page_blacklist = "CREATE TABLE $gpi_page_blacklist_table (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			URL text NULL,
			type varchar(200) DEFAULT NULL,
			object_id bigint(20) DEFAULT NULL,
			term_id bigint(20) DEFAULT NULL,
			custom_id bigint(20) DEFAULT NULL,
			PRIMARY KEY  (ID)
		) $charset_collate;";

		$gpi_custom_urls = "CREATE TABLE $gpi_custom_urls_table (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			URL text NULL,
			type varchar(200) DEFAULT NULL,
			PRIMARY KEY  (ID)
		) $charset_collate;";

		$gpi_summary_snapshots = "CREATE TABLE $gpi_summary_snapshots_table (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			strategy varchar(20) NOT NULL,
			type varchar(200) DEFAULT NULL,
			snaptime varchar(20) NOT NULL,
			comment varchar(200) DEFAULT NULL,
			summary_stats longtext,
			summary_reports longtext,
			PRIMARY KEY  (ID)
		) $charset_collate;";

		$gpi_api_error_logs = "CREATE TABLE $gpi_api_error_logs_table (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			URL text NULL,
			strategy varchar(20) NOT NULL,
			is_update int(1) NOT NULL,
			type varchar(200) DEFAULT NULL,
			timestamp varchar(20) NOT NULL,
			error longtext,
			PRIMARY KEY  (ID)
		) $charset_collate;";

		dbDelta( $gpi_page_stats );
		dbDelta( $gpi_page_reports );
		dbDelta( $gpi_page_blacklist );
		dbDelta( $gpi_custom_urls );
		dbDelta( $gpi_summary_snapshots );
		dbDelta( $gpi_api_error_logs );
	}

}

register_activation_hook( GPI_PLUGIN_FILE, array( 'GPI_Activation', 'activation' ) );
