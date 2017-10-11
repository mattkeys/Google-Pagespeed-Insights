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
	var $gpi_summary_snapshots;
	var $gpi_custom_urls;

	public function init()
	{
		global $wpdb;

		$this->action				= sanitize_text_field( $_REQUEST['action'] );
		$this->gpi_options			= get_option( 'gpagespeedi_options' );
		$this->gpi_ui_options		= get_option( 'gpagespeedi_ui_options' );
		$this->page_id				= isset( $_GET['page_id'] ) ? intval( $_GET['page_id'] ) : false;
		$this->bulk_pages			= isset( $_GET['gpi_page_report'] ) ? array_map( 'intval', $_GET['gpi_page_report'] ) : false;
		$this->bulk_pages_count		= count( $this->bulk_pages );

		$this->gpi_page_stats			= $wpdb->prefix . 'gpi_page_stats';
		$this->gpi_page_reports			= $wpdb->prefix . 'gpi_page_reports';
		$this->gpi_page_blacklist		= $wpdb->prefix . 'gpi_page_blacklist';
		$this->gpi_summary_snapshots	= $wpdb->prefix . 'gpi_summary_snapshots';
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

			case 'save-snapshot':
				$action_message = $this->save_snapshot();
				break;

			case 'delete-snapshot':
				$action_message = $this->delete_snapshot();
				break;

			case 'add-custom-urls':
				$action_message = $this->add_custom_urls();
				break;

			case 'add-custom-urls-bulk':
				$action_message = $this->add_custom_urls_bulk();
				break;

			case 'delete':
				$action_message = $this->delete_page();
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
			} else if ( 'purge_everything' == $_POST['purge_all_data'] ) {
				$wpdb->query( "TRUNCATE TABLE $this->gpi_page_stats" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_page_reports" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_page_blacklist" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_api_error_logs" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_custom_urls" );
				$wpdb->query( "TRUNCATE TABLE $this->gpi_summary_snapshots" );

				do_action( 'gpi_truncate_custom_tables' );
			}
		}

		$old_options = $this->gpi_options;

		$gpagespeedi_options = array(
			'google_developer_key'		=> ! empty( $_POST['google_developer_key'] )	? sanitize_text_field( $_POST['google_developer_key'] )						: $this->gpi_options['google_developer_key'],
			'response_language'			=> ! empty( $_POST['response_language'] )		? sanitize_text_field( $_POST['response_language'] )						: $this->gpi_options['response_language'],
			'strategy'					=> ! empty( $_POST['strategy'] )				? sanitize_text_field( $_POST['strategy'] )									: $this->gpi_options['strategy'],
			'max_execution_time'		=> ! empty( $_POST['max_execution_time'] )		? intval( $_POST['max_execution_time'] )									: $this->gpi_options['max_execution_time'],
			'max_run_time'				=> ! empty( $_POST['max_run_time'] )			? intval( $_POST['max_run_time'] )											: $this->gpi_options['max_run_time'],
			'sleep_time'				=> isset( $_POST['sleep_time'] )				? intval( $_POST['sleep_time'] )											: $this->gpi_options['sleep_time'],
			'recheck_interval'			=> ! empty( $_POST['recheck_interval'] )		? intval( $_POST['recheck_interval'] )										: $this->gpi_options['recheck_interval'],
			'use_schedule'				=> isset( $_POST['use_schedule'] )				? true																		: false,
			'check_pages'				=> isset( $_POST['check_pages'] )				? true																		: false,
			'check_posts'				=> isset( $_POST['check_posts'] )				? true																		: false,
			'cpt_whitelist'				=> isset( $_POST['cpt_whitelist'] )				? serialize( array_map( 'sanitize_text_field', $_POST['cpt_whitelist'] ) )	: false,
			'check_categories'			=> isset( $_POST['check_categories'] )			? true																		: false,
			'check_custom_urls'			=> isset( $_POST['check_custom_urls'] )			? true																		: false,
			'first_run_complete'		=> $this->gpi_options['first_run_complete'],
			'last_run_finished'			=> $this->gpi_options['last_run_finished'],
			'bad_api_key'				=> false,
			'pagespeed_disabled'		=> false,
			'api_restriction'			=> false,
			'new_ignored_items'			=> false,
			'backend_error'				=> false,
			'log_api_errors'			=> isset( $_POST['log_api_errors'] )			? true																		: false,
			'new_activation_message'	=> false,
			'heartbeat'					=> isset( $_POST['heartbeat'] )					? sanitize_text_field( $_POST['heartbeat'] )								: 'standard',
			'version'					=> GPI_VERSION
		);
		update_option( 'gpagespeedi_options', $gpagespeedi_options );
		$this->gpi_options = $gpagespeedi_options;

		$gpagespeedi_ui_options = array(
			'action_message'			=> false,
			'view_preference'			=> 'both' != $_POST['strategy'] ? sanitize_text_field( $_POST['strategy'] ) : $this->gpi_ui_options['view_preference']
		);

		update_option( 'gpagespeedi_ui_options', $gpagespeedi_ui_options );
		$this->gpi_ui_options = $gpagespeedi_ui_options;

		if ( $gpagespeedi_options['use_schedule'] && ! wp_next_scheduled('googlepagespeedinsightsworker') ) {
			wp_schedule_event( time() + $gpagespeedi_options['recheck_interval'], 'gpi_scheduled_interval', 'googlepagespeedinsightsworker' );
		} else if ( $gpagespeedi_options['use_schedule'] && ( $gpagespeedi_options['recheck_interval'] != $old_options['recheck_interval'] ) ) {
			wp_clear_scheduled_hook( 'googlepagespeedinsightsworker' );
			wp_schedule_event( time() + $gpagespeedi_options['recheck_interval'], 'gpi_scheduled_interval', 'googlepagespeedinsightsworker' );
		} else if ( ! $gpagespeedi_options['use_schedule'] ) {
			wp_clear_scheduled_hook( 'googlepagespeedinsightsworker' );
		}

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

	private function save_snapshot()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'gpi_save_snapshot' ) ) {
			return array(
				'type'		=> 'error',
				'message'	=> __( 'Invalid Nonce. Please refresh the page and try again.', 'gpagespeedi' )
			);
		}

		global $wpdb;

		$snapshot_data = array(
			'strategy'			=> $this->gpi_ui_options['view_preference'],
			'type'				=> isset( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all',
			'snaptime'			=> current_time( 'timestamp' ),
			'comment'			=> isset( $_POST['comment'] ) ? sanitize_text_field( $_POST['comment'] ) : false,
			'summary_stats'		=> json_encode( apply_filters( 'gpi_summary_stats', array() ) ),
			'summary_reports'	=> json_encode( apply_filters( 'gpi_summary_reports', array() ) )
		);

		$save_snapshot = $wpdb->insert( 
			$this->gpi_summary_snapshots,
			$snapshot_data, 
			array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
			) 
		);

		if ( $save_snapshot ) {
			return __( 'Snapshot Saved Successfully', 'gpagespeedi' );
		}	
	}

	private function delete_snapshot()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-gpi_page_reports' ) ) {
			return array(
				'type'		=> 'error',
				'message'	=> __( 'Invalid Nonce. Please refresh the page and try again.', 'gpagespeedi' )
			);
		}

		if ( empty( $this->bulk_pages ) && ( isset( $_GET['snapshot_id'] ) && ! empty( intval( $_GET['snapshot_id'] ) ) ) ) {
			$this->bulk_pages = array( intval( $_GET['snapshot_id'] ) );
		}

		if ( empty( $this->bulk_pages ) ) {
			return array(
				'type'		=> 'error',
				'message'	=> __( 'No snapshot(s) selected.', 'gpagespeedi' )
			);
		}

		global $wpdb;

		foreach ( $this->bulk_pages as $bulk_page_id ) {
			$wpdb->delete( $this->gpi_summary_snapshots, array( 'ID' => $bulk_page_id ), array( '%d' ) );
		}

		$delete_count = count( $this->bulk_pages );

		return $delete_count . ' ' . __( 'Snapshots have been deleted.', 'gpagespeedi' );
	}

	private function add_custom_urls()
	{
		$urls_to_store = array();
		$inserted_urls = 0;

		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'gpi-add-custom-urls' ) ) {
			return array(
				'type'		=> 'error',
				'message'	=> __( 'Invalid Nonce. Please refresh the page and try again.', 'gpagespeedi' )
			);
		}

		$custom_url_label = $_POST['custom_url_label'];

		if ( empty( $custom_url_label ) ) {
			$custom_url_label = 'custom';
		} else {
			$custom_url_label = preg_replace( '/[^a-zA-Z0-9\s]/', '', $custom_url_label );
			$custom_url_label = str_replace( ' ', '_', $custom_url_label );
			$custom_url_label = substr( $custom_url_label, 0, 20 );
		}

		foreach ( $_POST['custom_urls'] as $custom_url ) {
			$custom_url = esc_url_raw( $custom_url, array( 'http', 'https' ) );

			if ( ! empty( $custom_url ) ) {
				$urls_to_store[] = $custom_url;
			}
		}

		if ( ! empty( $urls_to_store ) ) {
			global $wpdb;

			foreach ( $urls_to_store as $key => $url ) {
				
				$url_already_exist = $wpdb->get_var( $wpdb->prepare(
					"SELECT ( SELECT COUNT(*) FROM $this->gpi_custom_urls WHERE URL = %s ) + ( SELECT COUNT(*) FROM $this->gpi_page_stats WHERE URL = %s )",
					$url, $url
					)
				);

				if ( ! $url_already_exist ) {
					$wpdb->insert(
						$this->gpi_custom_urls,
						array(
							'URL'	=> $url,
							'type'	=> $custom_url_label
						),
						array(
							'%s',
							'%s'
						)
					);
				} else {
					unset( $urls_to_store[ $key ] );
				}
			}

			$inserted_urls = count( $urls_to_store );

			do_action( 'run_gpi', false );
		}

		return $inserted_urls . ' ' . __( 'URL(s) have been successfully added.', 'gpagespeedi' );
	}

	private function add_custom_urls_bulk()
	{
		$urls_to_store = array();
		$already_exist = array();
		$inserted_urls = 0;

		if ( ! isset( $_FILES['xml_sitemap'] ) ) {
			return array(
				'type'		=> 'error',
				'message'	=> __( 'There was a problem uploading the sitemap', 'gpagespeedi' )
			);
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$upload_overrides = array( 'test_form' => false, 'mimes' => array( 'xml' => 'application/xml' ) );
		$movefile = wp_handle_upload( $_FILES['xml_sitemap'], $upload_overrides );

		if ( isset( $movefile['file'] ) ) {
			if ( $movefile['type'] != 'application/xml' ) {
				unlink( $movefile['file'] );
				return array(
					'type'		=> 'error',
					'message'	=> __( 'File type must be "Application/XML"', 'gpagespeedi' )
				);
			}
		} else if ( isset( $movefile['error'] ) ) {
			return array(
				'type'		=> 'error',
				'message'	=> $movefile['error']
			);
		} else {
			return array(
				'type'		=> 'error',
				'message'	=> __( 'There was a problem uploading the sitemap', 'gpagespeedi' )
			);
		}

		$accepted_protocols = array( 'http', 'https' );

		// Create new document object
		$dom_object = new DOMDocument();
		
		// Load xml file
		$dom_object->load( $movefile['file'] );

		$item = $dom_object->getElementsByTagName( 'url' );

		foreach ( $item as $value ) {
			$locations = $value->getElementsByTagName( 'loc' );
			$location  = $locations->item(0)->nodeValue;
			$urls_to_store[] = esc_url_raw( $location, $accepted_protocols );      
		}

		if ( ! empty( $urls_to_store ) ) {

			$custom_url_label = isset( $_POST['custom_url_label'] ) ? $_POST['custom_url_label'] : false;

			if ( ! $custom_url_label ) {
				$custom_url_label = 'custom';
			} else {
				$custom_url_label = preg_replace( '/[^a-zA-Z0-9\s]/', '', $custom_url_label );
				$custom_url_label = str_replace( ' ', '_', $custom_url_label );
				$custom_url_label = substr( $custom_url_label, 0, 20 );
			}

			global $wpdb;

			foreach ( $urls_to_store as $key => $url ) {

				// Make sure the URL does not already exist
				$url_already_exist = $wpdb->get_var( $wpdb->prepare(
					"SELECT ( SELECT COUNT(*) FROM $this->gpi_custom_urls WHERE URL = %s ) + ( SELECT COUNT(*) FROM $this->gpi_page_stats WHERE URL = %s )",
					$url, $url
					)
				);

				// If URL does not already exist, add it
				if ( $url_already_exist == 0 ) {
					$wpdb->insert(
						$this->gpi_custom_urls, 
						array( 
							'URL'	=> $url,
							'type'	=> $custom_url_label
						),
						array(
							'%s',
							'%s'
						)
					);
				} else {
					$already_exist[] = $url;
					unset( $urls_to_store[ $key ] );
				}
			}

			$already_exist = count( $already_exist );
			$inserted_urls = count( $urls_to_store );

			if ( ! empty( $urls_to_store ) ) {
				do_action( 'run_gpi', false );
			}
		}

		return $inserted_urls . ' ' . __( 'URL(s) have been successfully added.', 'gpagespeedi' ) . ' ' . $already_exist . ' ' . __( 'URL(s) already exist and have been skipped.', 'gpagespeedi' ) ;
	}

	private function delete_page()
	{
		global $wpdb;

		if ( is_array( $this->bulk_pages ) && ! empty( $this->bulk_pages ) ) {
			$x = 1;
			$custom_id_where_clause = '';
			$select_id_where_clause = '';
			foreach ( $this->bulk_pages as $page ) {
				$page = intval( $page );

				if ( ! $page ) {
					continue;
				}

				if ( $x < $this->bulk_pages_count ) {
					$custom_id_where_clause .= 'custom_id = ' . $page . ' OR ';
					$select_id_where_clause .= 'ID = ' . $page . ' OR ';
				} else {
					$custom_id_where_clause .= 'custom_id = ' . $page;
					$select_id_where_clause .= 'ID = ' . $page;
				}
				$x++;
			}

			// Get the URLs for all pages being deleted
			$delete_array = $wpdb->get_results( "SELECT URL FROM $this->gpi_custom_urls WHERE $select_id_where_clause", ARRAY_A );
			$delete_array_count = count( $delete_array );
			if ( ! empty( $delete_array ) ) {
				$z = 1;
				$select_url_where_clause = '';
				foreach ( $delete_array as $delete_url ) {
					if ( $z < $delete_array_count ) {
						$select_url_where_clause .= 'URL = "' . $delete_url['URL'] . '" OR ';
					} else {
						$select_url_where_clause .= 'URL = "' . $delete_url['URL'] . '"';
					}
					$z++;
				}

				// Get IDs for all the pages to be deleted
				$page_stats_ids = $wpdb->get_results( "SELECT ID FROM $this->gpi_page_stats WHERE $select_url_where_clause", ARRAY_A );

				if ( ! empty( $page_stats_ids ) ) {

					$page_stats_ids_count = count( $page_stats_ids );
					$y = 1;
					$where_string = '';
					foreach ( $page_stats_ids as $id ) {
						if ( $y < $page_stats_ids_count ) {
							$where_string .= $this->gpi_page_stats . '.ID = ' . $id['ID'] . ' OR ';
						} else {
							$where_string .= $this->gpi_page_stats . '.ID = ' . $id['ID'];
						}
						$y++;
					}

					// Delete page stats and page reports if they exist
					$wpdb->query("
						DELETE $this->gpi_page_stats, $this->gpi_page_reports 
						FROM $this->gpi_page_stats, $this->gpi_page_reports
						WHERE $this->gpi_page_stats.ID = $this->gpi_page_reports.page_id
						AND ($where_string)
					");
				}

				// Delete blacklisted URLs if exist
				$wpdb->query("
					DELETE $this->gpi_page_blacklist 
					FROM $this->gpi_page_blacklist
					WHERE $custom_id_where_clause
				");

				// Delete custom URLs from gpi_custom_urls table
				$wpdb->query("
					DELETE $this->gpi_custom_urls 
					FROM $this->gpi_custom_urls
					WHERE $select_id_where_clause
				");
			}

			return $delete_array_count . ' ' . __( 'URLs have been deleted.', 'gpagespeedi' );

		} else if ( ! empty( $this->page_id ) ) {

			// Get the URL for the page being deleted
			$page_url = $wpdb->get_var( "SELECT URL FROM $this->gpi_custom_urls WHERE ID = $this->page_id" );
			
			// Get ID for the page to be deleted
			$page_stats_id = $wpdb->get_var( "SELECT ID FROM $this->gpi_page_stats WHERE URL = '$page_url'" );

			// Delete page stats and page reports if they exist
			if ( ! empty( $page_stats_id ) ) {
				$wpdb->query("
					DELETE $this->gpi_page_stats, $this->gpi_page_reports
					FROM $this->gpi_page_stats, $this->gpi_page_reports
					WHERE $this->gpi_page_stats.ID = $this->gpi_page_reports.page_id
					AND $this->gpi_page_stats.ID = $page_stats_id
				");
			}

			// Delete blacklisted URL if exist
			$wpdb->delete( $this->gpi_page_blacklist, array( 'custom_id' => $this->page_id ), array( '%d' ) );

			// Delete custom URL
			$wpdb->delete( $this->gpi_custom_urls, array( 'ID' => $this->page_id ), array( '%d' ) );

			return '1 ' . __( 'URLs have been deleted.', 'gpagespeedi' );
		}
	}
}

add_action( 'plugins_loaded', array( new GPI_Actions, 'init' ) );

