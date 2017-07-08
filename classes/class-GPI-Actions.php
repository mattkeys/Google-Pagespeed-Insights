<?php
/**
 * =======================================
 * Google Pagespeed Insights Actions
 * =======================================
 * 
 * 
 * @author Matt Keys <https://profiles.wordpress.org/mattkeys>
 */

if ( ! defined( 'GPI_PLUGIN_FILE' ) ) {
	die();
}

class GPI_Actions
{
	var $action;
	var $gpi_options;
	var $gpi_ui_options;
	var $page_id;
	var $bulk_pages;
	var $bulk_pages_count;

	var $gpi_page_stats;
	var $gpi_page_reports;
	var $gpi_page_blacklist;
	var $gpi_custom_urls;

	public function init()
	{
		global $wpdb;

		$this->action				= $_REQUEST['action'];
		$this->gpi_options			= get_option( 'gpagespeedi_options' );
		$this->gpi_ui_options		= get_option( 'gpagespeedi_ui_options' );
		$this->page_id				= isset( $_GET['page_id'] ) ? $_GET['page_id'] : false;
		$this->bulk_pages			= isset( $_GET['gpi_page_report'] ) ? $_GET['gpi_page_report'] : false;
		$this->bulk_pages_count		= count( $this->bulk_pages );

		$this->gpi_page_stats			= $wpdb->prefix . 'gpi_page_stats';
		$this->gpi_page_reports			= $wpdb->prefix . 'gpi_page_reports';
		$this->gpi_page_blacklist		= $wpdb->prefix . 'gpi_page_blacklist';
		$this->gpi_custom_urls			= $wpdb->prefix . 'gpi_custom_urls';
		$this->gpi_api_error_logs		= $wpdb->prefix . 'gpi_api_error_logs';

		add_action( 'admin_init', array( $this, 'do_gpi_actions' ), 9 );
	}

	public function do_gpi_actions( $action_message = false )
	{
		switch ( $this->action ) {
			case 'start-scan':
				$action_message = $this->start_scan();
				break;

			case 'abort-scan':
				$action_message = $this->abort_scan();
				break;

			case 'save-options':
				$action_message = $this->save_options();
				break;

			case 'recheck':
				$action_message = $this->recheck_pages();
				break;

			case 'single-recheck':
				$action_message = $this->recheck_now();
				break;

			case 'reactivate':
				$action_message = $this->reactivate();
				break;

			case 'ignore':
				$action_message = $this->ignore_page();
				break;

			case 'delete_report':
				$action_message = $this->delete_report();
				break;

			case 'delete_blacklist':
				$action_message = $this->delete_blacklist();
				break;

			case 'set_view_preference':
				$new_strategy = isset( $_GET['strategy'] ) ? $_GET['strategy'] : false;
				if ( 'mobile' == $new_strategy || 'desktop' == $new_strategy ) {
					do_action( 'gpi_update_option', 'view_preference', $new_strategy, 'gpagespeedi_ui_options' );
				}
				break;

			case 'reports_update':
				delete_option( 'gpagespeedi_upgrade_recheck_required' );
				do_action( 'run_gpi', true );
				$action_message = __( 'Successfully initiated Google Pagespeed Insights to recheck all reports. Full plugin functionality will be restored after all pages have been rechecked.', 'gpagespeedi' );
				break;

			case apply_filters( 'gpi_custom_actions', $this->action ):
				$action_message = apply_filters( 'gpi_action_' . $this->action, '', $this->gpi_options, $this->gpi_ui_options, $this->page_id, $this->bulk_pages, $this->bulk_pages_count );
				break;
		}

		if ( $action_message ) {
			do_action( 'gpi_update_option', 'action_message', $action_message, 'gpagespeedi_ui_options' );		
		}

		wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'id', 'gpi_page_report', 'single-recheck', 'strategy' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}

	private function start_scan()
	{
		$recheck = isset( $_GET['recheck_all_pages'] ) ? true : false;
		do_action( 'run_gpi', $recheck );

		return __( 'Starting Reporting. Google Pagespeed will work in the background to load and report on each URL. The amount of time needed to complete all reports will vary depending on how many URLs there are to check, and how long it takes for Google to load each page on their servers. You can navigate away from this page if desired.', 'gpagespeedi' );
	}

	private function abort_scan()
	{
		add_option( 'gpi_abort_scan', true, '', false );

		return __( 'Scan abort request received. Please allow a moment for the in-progress page report to complete before the abort request can take effect.', 'gpagespeedi' );
	}

	private function save_options()
	{
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'gpi-save-options' ) ) {
			return;
		}

		global $wpdb;

		// Double check DB tables exist
		if ( $this->gpi_api_error_logs != $wpdb->get_var( "SHOW TABLES LIKE '$this->gpi_api_error_logs'" ) ) {
			GPI_Activation::upgrade( $this->gpi_options, $this->gpi_ui_options, $update_options = false );
		}

		// Check for 'purge all data' option and truncate tables if checked
		if ( isset( $_POST['purge_all_data'] ) ) {
			if ( 'purge_reports' == $_POST['purge_all_data'] ) {
				$wpdb->query( "TRUNCATE TABLE $this->gpi_page_stats" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_page_reports" );
			} else if ( $_POST['purge_all_data'] == 'purge_everything' ) {
				$wpdb->query( "TRUNCATE TABLE $this->gpi_page_stats" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_page_reports" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_page_blacklist" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_api_error_logs" );

				do_action( 'gpi_truncate_custom_tables' );
			}
		}

		$old_options = $this->gpi_options;

		$gpagespeedi_options = array(
			'google_developer_key'		=> ! empty( $_POST['google_developer_key'] )	? $_POST['google_developer_key']		: $this->gpi_options['google_developer_key'],
			'response_language'			=> ! empty( $_POST['response_language'] )		? $_POST['response_language']			: $this->gpi_options['response_language'],
			'strategy'					=> ! empty( $_POST['strategy'] )				? $_POST['strategy']					: $this->gpi_options['strategy'],
			'max_execution_time'		=> ! empty( $_POST['max_execution_time'] )		? $_POST['max_execution_time']			: $this->gpi_options['max_execution_time'],
			'max_run_time'				=> ! empty( $_POST['max_run_time'] )			? $_POST['max_run_time']				: $this->gpi_options['max_run_time'],
			'sleep_time'				=> isset( $_POST['sleep_time'] )				? $_POST['sleep_time']					: $this->gpi_options['sleep_time'],
			'recheck_interval'			=> ! empty( $_POST['recheck_interval'] )		? $_POST['recheck_interval']			: $this->gpi_options['recheck_interval'],
			'use_schedule'				=> isset( $_POST['use_schedule'] )				? true									: false,
			'check_pages'				=> isset( $_POST['check_pages'] )				? true									: false,
			'check_posts'				=> isset( $_POST['check_posts'] )				? true									: false,
			'cpt_whitelist'				=> isset( $_POST['cpt_whitelist'] )				? serialize( $_POST['cpt_whitelist'] )	: false,
			'check_categories'			=> isset( $_POST['check_categories'] )			? true									: false,
			'check_custom_urls'			=> isset( $_POST['check_custom_urls'] )			? true									: false,
			'first_run_complete'		=> $this->gpi_options['first_run_complete'],
			'last_run_finished'			=> $this->gpi_options['last_run_finished'],
			'bad_api_key'				=> false,
			'pagespeed_disabled'		=> false,
			'api_restriction'			=> false,
			'new_ignored_items'			=> false,
			'backend_error'				=> false,
			'log_api_errors'			=> isset( $_POST['log_api_errors'] )			? true									: false,
			'new_activation_message'	=> false,
			'heartbeat'					=> isset( $_POST['heartbeat'] )					? $_POST['heartbeat']					: 'standard',
			'version'					=> GPI_VERSION
		);
		update_option( 'gpagespeedi_options', $gpagespeedi_options );
		$this->gpi_options = $gpagespeedi_options;

		$gpagespeedi_ui_options = array(
			'action_message'			=> false,
			'view_preference'			=> 'both' != $_POST['strategy'] ? $_POST['strategy'] : $this->gpi_ui_options['view_preference']
		);

		update_option( 'gpagespeedi_ui_options', $gpagespeedi_ui_options );
		$this->gpi_ui_options = $gpagespeedi_ui_options;

		return __( 'Settings Saved.', 'gpagespeedi' );
	}

	private function recheck_now( $custom = false )
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'gpi-single-recheck' ) ) {
			return;
		}

		global $wpdb;

		if ( ! empty( $this->page_id ) ) {
			$page_stats = $wpdb->get_row( $wpdb->prepare(
				"
					SELECT URL, type, object_id, term_id, custom_id
					FROM $this->gpi_page_stats
					WHERE ID = %d
				",
				$this->page_id
			), ARRAY_A );

			if ( ! is_null( $page_stats['object_id'] ) ) {
				$objectid = $page_stats['object_id'];
			} else if ( ! is_null( $page_stats['term_id'] ) ) {
				$objectid = $page_stats['term_id'];
			} else {
				$objectid = $page_stats['custom_id'];
				$custom = true;
			}

			$urls_to_recheck = array(
				$page_stats['type'] => array(
					array(
						'url'		=> $page_stats['URL'],
						'objectid'	=> $objectid,
						'custom'	=> $custom
					)
				),
				'total_url_count' => 1
			);

			$checkstatus = apply_filters( 'gpi_check_status', false );

			if ( $checkstatus ) {
				$message = __( 'The API is busy checking other pages, please try again later.', 'gpagespeedi' );
			} else {
				do_action( 'googlepagespeedinsightsworker', $urls_to_recheck, true );
				$message = __( 'Recheck Complete.', 'gpagespeedi' );
			}

			return $message;
		}
	}

	private function recheck_pages()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'gpi-single-recheck' ) ) {
			return;
		}

		global $wpdb;

		if ( is_array( $this->bulk_pages ) && ! empty( $this->bulk_pages ) ) {

			$x = 1;
			$where_clause = '';
			foreach ( $this->bulk_pages as $page ) {
				if ( $x < $this->bulk_pages_count ) {
					$where_clause .= 'ID = ' . $page . ' OR ';
				} else {
					$where_clause .= 'ID = ' . $page;
				}
				$x++;
			}

			// Set Force Recheck to 1 on selected URLs
			$wpdb->query("
				UPDATE $this->gpi_page_stats SET force_recheck = 1
				WHERE $where_clause
			");

			$return_message = $this->bulk_pages_count;

		} else if ( ! empty( $this->page_id ) ) {

			// Set Force Recheck to 1 on selected URL
			$wpdb->query("
				UPDATE $this->gpi_page_stats SET force_recheck = 1
				WHERE ID = $this->page_id
			");

			$return_message = '1';

		}

		do_action( 'run_gpi', false );

		return $return_message . ' ' . __( 'URLs have been scheduled for a recheck. Depending on the number of URLs to check, this may take a while to complete.', 'gpagespeedi' );;
	}

	private function reactivate()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-gpi_page_reports' ) ) {
			return;
		}

		if ( empty( $this->bulk_pages ) && ! empty( $this->page_id ) ) {
			$this->bulk_pages = array( $this->page_id );
		}

		if ( empty( $this->bulk_pages ) ) {
			return;
		}

		global $wpdb;

		foreach ( $this->bulk_pages as $page_id ) {
			$wpdb->delete( $this->gpi_page_blacklist, array( 'ID' => $page_id ), array( '%d' ) );
		}

		do_action( 'run_gpi', false );

		$reactivate_count = count( $this->bulk_pages );

		return $reactivate_count . ' ' . __( 'URLs have been reactivated.', 'gpagespeedi' );
	}

	private function ignore_page()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-gpi_page_reports' ) ) {
			return;
		}

		if ( empty( $this->bulk_pages ) && ! empty( $this->page_id ) ) {
			$this->bulk_pages = array( $this->page_id );
		}

		if ( empty( $this->bulk_pages ) ) {
			return;
		}

		global $wpdb;

		foreach ( $this->bulk_pages as $page_id ) {
			$page_info = $wpdb->get_row( $wpdb->prepare(
				"
					SELECT ID, URL, type, object_id, term_id, custom_id
					FROM $this->gpi_page_stats
					WHERE ID = %d
				",
				$page_id
			), ARRAY_A );

			$wpdb->delete( $this->gpi_page_stats, array( 'ID' => $page_id ), array( '%d' ) );
			$wpdb->delete( $this->gpi_page_reports, array( 'page_id' => $page_id ), array( '%d' ) );

			$wpdb->insert( 
				$this->gpi_page_blacklist, 
				$page_info
			);
		}

		$ignore_count = count( $this->bulk_pages );

		return $ignore_count . ' ' . __( 'Reports have been ignored.', 'gpagespeedi' );
	}

	private function delete_report()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-gpi_page_reports' ) ) {
			return;
		}

		if ( empty( $this->bulk_pages ) && ! empty( $this->page_id ) ) {
			$this->bulk_pages = array( $this->page_id );
		}

		if ( empty( $this->bulk_pages ) ) {
			return;
		}

		global $wpdb;

		foreach ( $this->bulk_pages as $page_id ) {
			$wpdb->delete( $this->gpi_page_stats, array( 'ID' => $page_id ), array( '%d' ) );
			$wpdb->delete( $this->gpi_page_reports, array( 'page_id' => $page_id ), array( '%d' ) );
		}

		$delete_count = count( $this->bulk_pages );

		return $delete_count . ' ' . __( 'Reports have been deleted.', 'gpagespeedi' );
	}

	private function delete_blacklist()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-gpi_page_reports' ) ) {
			return;
		}

		if ( empty( $this->bulk_pages ) && ! empty( $this->page_id ) ) {
			$this->bulk_pages = array( $this->page_id );
		}

		if ( empty( $this->bulk_pages ) ) {
			return;
		}

		global $wpdb;

		foreach ( $this->bulk_pages as $page_id ) {
			$wpdb->delete( $this->gpi_page_blacklist, array( 'ID' => $page_id ), array( '%d' ) );
		}

		$delete_count = count( $this->bulk_pages );

		return $delete_count . ' ' . __( 'URLs have been deleted.', 'gpagespeedi' );
	}

}

add_action( 'plugins_loaded', array( new GPI_Actions, 'init' ) );

