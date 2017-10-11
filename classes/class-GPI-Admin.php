<?php
/**
 * =======================================
 * Google Pagespeed Insights Admin
 * =======================================
 * 
 * 
 * @author Matt Keys <https://profiles.wordpress.org/mattkeys>
 */

if ( ! defined( 'GPI_PLUGIN_FILE' ) ) {
	die();
}

class GPI_Admin
{
	var $gpi_options;
	var $gpi_ui_options;
	var $strategy;
	var $gpi_management_page;

	public function init()
	{
		$this->gpi_options		= get_option( 'gpagespeedi_options' );
		$this->gpi_ui_options	= get_option( 'gpagespeedi_ui_options' );
		$this->strategy			= ( isset( $_GET['strategy'] ) ) ? sanitize_text_field( $_GET['strategy'] ) : $this->gpi_ui_options['view_preference'];

		add_action( 'admin_init', array( $this, 'upgrade_check' ), 10 );
		add_action( 'pre_uninstall_plugin', array( $this, 'backup_addon_tables' ), 10, 1 );
		add_action( 'deleted_plugin', array( $this, 'restore_addon_tables' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'google_pageinsights_menu' ), 10 );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_action( 'plugins_loaded', array( $this, 'register_languages_dir' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_GPI_style' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'details_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'summary_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'snapshot_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'global_scripts' ) );
		add_action( 'admin_footer', array( $this, 'js_templates' ) );
		add_action( 'admin_init', array( $this, 'redirect' ), 9 );
		add_filter( 'heartbeat_settings', array( $this, 'heartbeat_interval' ), 10, 1 );
		add_filter( 'wpe_heartbeat_allowed_pages', array( $this, 'wpe_heartbeat' ), 10, 1 );
		add_filter( 'heartbeat_received', array( $this, 'progress_heartbeat' ), 10, 2 );
		add_filter( 'gpi_set_time_limit_disabled', array( $this, 'check_set_time_limit' ), 10, 1 );
		add_filter( 'gpi_error_logs', array( $this, 'get_error_logs' ), 10, 1 );
		add_filter( 'gpi_filter_options', array( $this, 'get_filter_options' ), 10, 2 );
		add_filter( 'gpi_custom_post_types', array( $this, 'get_custom_post_types' ), 10, 1 );
		add_filter( 'gpi_custom_urls_count', array( $this, 'get_custom_urls_count' ), 10, 1 );
		add_filter( 'gpi_custom_url_labels', array( $this, 'get_custom_url_labels' ), 10, 1 );
		add_filter( 'gpi_summary_stats', array( $this, 'get_summary_stats' ), 10, 1 );
		add_filter( 'gpi_summary_reports', array( $this, 'get_summary_reports' ), 10, 1 );
		add_filter( 'gpi_similar_snapshots', array( $this, 'get_similar_snapshots' ), 10, 2 );

		load_plugin_textdomain( 'gpagespeedi', false, 'google-pagespeed-insights/translations' );
	}

	public function upgrade_check()
	{
		if ( ! isset( $this->gpi_options['version'] ) || version_compare( $this->gpi_options['version'], GPI_VERSION, '<' ) ) {
			GPI_Activation::upgrade( $this->gpi_options, $this->gpi_ui_options );
		}

		if ( defined( 'GPIA_PLUGIN_FILE' ) ) {
			deactivate_plugins( GPIA_PLUGIN_FILE );

			add_action( 'admin_notices', array( $this, 'notify_addon_deactivate' ) );
		}

		do_action( 'gpi_addon_upgrade_check' );
	}

	public function backup_addon_tables( $plugin_file )
	{
		if ( 'google-pagespeed-insights-addon/google-pagespeed-insights-addon.php' != $plugin_file ) {
			return;
		}

		global $wpdb;

		$gpi_summary_snapshots			= $wpdb->prefix . 'gpi_summary_snapshots';
		$gpi_custom_urls				= $wpdb->prefix . 'gpi_custom_urls';
		$gpi_summary_snapshots_backup	= $wpdb->prefix . 'gpi_summary_snapshots_backup';
		$gpi_custom_urls_backup			= $wpdb->prefix . 'gpi_custom_urls_backup';

		// Rename current tables
		$wpdb->query( "RENAME TABLE $gpi_summary_snapshots TO $gpi_summary_snapshots_backup" );
		$wpdb->query( "RENAME TABLE $gpi_custom_urls TO $gpi_custom_urls_backup" );

		// Create new blank tables so uninstall hook doesn't produce errors
		$wpdb->query( "CREATE TABLE $gpi_summary_snapshots (id INT(1) )" );
		$wpdb->query( "CREATE TABLE $gpi_custom_urls (id INT(1) )" );
	}

	public function restore_addon_tables( $plugin_file, $deleted )
	{
		if ( 'google-pagespeed-insights-addon/google-pagespeed-insights-addon.php' != $plugin_file ) {
			return;
		}

		global $wpdb;

		$gpi_summary_snapshots			= $wpdb->prefix . 'gpi_summary_snapshots';
		$gpi_custom_urls				= $wpdb->prefix . 'gpi_custom_urls';
		$gpi_summary_snapshots_backup	= $wpdb->prefix . 'gpi_summary_snapshots_backup';
		$gpi_custom_urls_backup			= $wpdb->prefix . 'gpi_custom_urls_backup';

		// Rename backed up tables to restore
		$wpdb->query( "RENAME TABLE $gpi_summary_snapshots_backup TO $gpi_summary_snapshots" );
		$wpdb->query( "RENAME TABLE $gpi_custom_urls_backup TO $gpi_custom_urls" );
	}

	public function notify_addon_deactivate()
	{
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php _e( 'The plugin "Google Pagespeed Insights Addon" has automatically been deactivated. As of v3.0 Google Pagespeed Insights now includes all "addon" functionality for free. The "Google Pagespeed Insights Addon" can be uninstalled from the plugins page.', 'gpagespeedi' ); ?></p>
		</div>
		<?php
	}

	public function google_pageinsights_menu()
	{
		$this->gpi_management_page = add_management_page( 'Google Pagespeed Insights', 'Pagespeed Insights', 'manage_options', 'google-pagespeed-insights', array( $this, 'render_admin_page' ) );
	}

	public function render_admin_page()
	{
		$admin_page = ( isset( $_GET['render'] ) ) ? sanitize_text_field( $_GET['render'] ) : 'report-list';
		?>
		<div class="wrap">
			<div id="icon-gpi" class="icon32"><br/></div>
			<h2>
				<?php _e( 'Google Pagespeed Insights', 'gpagespeedi' ); ?>
				<?php
					if ( $worker_status = apply_filters( 'gpi_check_status', false ) ) :
						if ( ! get_option( 'gpi_abort_scan' ) ) :
							?>
							<a href="?page=<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>&amp;render=<?php echo $admin_page; ?>&amp;action=abort-scan" class="button-gpi abort"><?php _e( 'Abort Current Scan', 'gpagespeedi' ); ?></a>
							<?php
						else :
							?>
							<a href="?page=<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>&amp;render=<?php echo $admin_page; ?>" class="button-gpi abort" disabled><?php _e( 'Abort Current Scan', 'gpagespeedi' ); ?></a>
							<?php
						endif;
					elseif ( $this->gpi_options['google_developer_key'] ) :
						?>
							<a id="start_scan" href="?page=<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>&amp;render=<?php echo $admin_page; ?>&amp;action=start-scan" class="button-gpi run"><?php _e( 'Start Reporting', 'gpagespeedi' ); ?></a>
							<input type="checkbox" name="recheck_all_pages" id="recheck_all_pages" />
							<label for="recheck_all_pages"><?php _e( 'Recheck All', 'gpagespeedi' ); ?> <span class="tooltip" title="<?php _e( 'Ignore last checked date to generate new reports for all pages', 'gpagespeedi' ); ?>">(?)</span></label>
						<?php
					endif;
				?>
			</h2>

			<?php include GPI_DIRECTORY . '/templates/parts/modes.php'; ?>
			<?php include GPI_DIRECTORY . '/templates/parts/messages.php'; ?>
			<?php include GPI_DIRECTORY . '/templates/parts/navigation.php'; ?>
			<?php
				$template_directory = apply_filters( 'gpi_template_directory', GPI_DIRECTORY, $admin_page );
				include $template_directory . '/templates/' . $admin_page . '.php';
			?>
		</div>
		<?php
	}

	public function admin_notice()
	{
		if ( $this->gpi_options['new_activation_message'] == false ) {
			return;
		}
		?>
		<div id="message" class="updated">
			<p>
				<?php _e( 'Google Pagespeed Insights for Wordpress has been activated. It can be accessed via Tools', 'gpagespeedi' ); ?> -> <a href="<?php echo admin_url('/tools.php?page=google-pagespeed-insights&render=options'); ?>">Pagespeed Insights</a>
			</p>
		</div>
		<?php
		do_action( 'gpi_update_option', 'new_activation_message', false, 'gpagespeedi_options' );
	}

	public function register_languages_dir()
	{
		$lang_dir = dirname( plugin_basename( GPI_PLUGIN_FILE ) ) . '/languages';

		load_plugin_textdomain( 'gpagespeedi', false, $lang_dir );
	}

	public function load_GPI_style( $hook )
	{
		if ( $hook != $this->gpi_management_page ) {
			return;
		}

		wp_enqueue_style( 'gpagespeedi_css', plugins_url( '/assets/css/gpagespeedi_styles.css', GPI_PLUGIN_FILE ), false, '3.0.0' );

		wp_register_script( 'gpagespeedi_google_charts', 'https://www.gstatic.com/charts/loader.js' );
	}

	public function details_scripts( $hook )
	{
		if ( $hook != $this->gpi_management_page ) {
			return;
		}

		if ( ! isset( $_GET['render'] ) || 'details' != $_GET['render'] ) {
			return;
		}

		$recheck_url = admin_url( '/tools.php?page=google-pagespeed-insights&render=details&page_id=' . intval( $_GET['page_id'] ) . '&action=single-recheck' );

		wp_enqueue_script( 'gpagespeedi_details_js', plugins_url( '/assets/js/details.js', GPI_PLUGIN_FILE ), array( 'jquery', 'gpagespeedi_google_charts', 'wp-util' ), '2.0.2' );
		wp_localize_script( 'gpagespeedi_details_js', 'GPI_Details', array(
				'page_stats'	=> $this->get_page_stats( intval( $_GET['page_id'] ) ),
				'page_reports'	=> $this->get_page_reports( intval( $_GET['page_id'] ) ),
				'recheck_url'	=> wp_nonce_url( $recheck_url, 'gpi-single-recheck' ),
				'strings'		=> array(
					'hosts'				=> __( 'Number of Hosts', 'gpagespeedi' ),
					'total_bytes'		=> __( 'Total Request Bytes', 'gpagespeedi' ),
					'total_resources'	=> __( 'Total Resources', 'gpagespeedi' ),
					'js_resources'		=> __( 'JavaScript Resources', 'gpagespeedi' ),
					'css_resources'		=> __( 'CSS Resources', 'gpagespeedi' ),
					'score_impact'		=> __( 'Score Impact', 'gpagespeedi' ),
					'old_format_notice'	=> __( 'Google Pagespeed Insights for WordPress has detected an outdated format in this report due to an update in version 2.0 of this plugin. Some report features are unavailable. Please recheck results to resolve this problem.', 'gpagespeedi' )
				)
			)
		);
	}

	public function summary_scripts( $hook )
	{
		if ( $hook != $this->gpi_management_page ) {
			return;
		}

		if ( ! isset( $_GET['render'] ) || 'summary' != $_GET['render'] ) {
			return;
		}

		wp_enqueue_script( 'gpagespeedi_summary_js', plugins_url( '/assets/js/summary.js', GPI_PLUGIN_FILE ), array( 'jquery', 'gpagespeedi_google_charts', 'wp-util' ), '2.0.2' );
		wp_localize_script( 'gpagespeedi_summary_js', 'GPI_Summary', array(
				'summary_stats'		=> $this->get_summary_stats(),
				'summary_reports'	=> $this->get_summary_reports(),
				'strings'			=> array(
					'resource_type'		=> __( 'Resource Type', 'gpagespeedi' ),
					'high'				=> __( 'High', 'gpagespeedi' ),
					'average'			=> __( 'Average', 'gpagespeedi' ),
					'low'				=> __( 'Low', 'gpagespeedi' ),
					'view_page_report'	=> __( 'View Page Report', 'gpagespeedi' ),
					'old_format_notice'	=> __( 'Google Pagespeed Insights for WordPress has detected an outdated format in one or more reports due to an update in version 2.0 of this plugin. Some report features are unavailable. Please force recheck all reports from the options page to resolve this problem.', 'gpagespeedi' )
				)
			)
		);
	}

	public function snapshot_scripts( $hook )
	{
		if ( $hook != 'tools_page_google-pagespeed-insights' ) {
			return;
		}

		if ( ! isset( $_GET['render'] ) || 'view-snapshot' != $_GET['render'] ) {
			return;
		}

		$snapshot_data = $this->get_snapshot_data();
		$strings = array(
			'strings' => array(
				'comment'	=> __('Comment', 'gpagespeedi')
			),
			'comments'	=> array(
				'snapshot'	=> isset( $snapshot_data['snapshot']['comment'] ) ? sanitize_text_field( $snapshot_data['snapshot']['comment'] ) : false,
				'compare'	=> isset( $snapshot_data['compare']['comment'] ) ? sanitize_text_field( $snapshot_data['compare']['comment'] ) : false
			)
		);

		$localize_data = array_merge( $snapshot_data, $strings );

		wp_enqueue_script( 'gpagespeedi_view_snapshot_js', plugins_url( '/assets/js/view-snapshot.js', GPI_PLUGIN_FILE ), array( 'jquery', 'gpagespeedi_google_charts', 'wp-util' ), '3.0.0' );
		wp_localize_script( 'gpagespeedi_view_snapshot_js', 'GPI_View_Snapshot', $localize_data );
	}

	public function global_scripts( $hook )
	{
		if ( $hook != $this->gpi_management_page ) {
			return;
		}

		wp_enqueue_script( 'gpagespeedi_global_js', plugins_url( '/assets/js/global.js', GPI_PLUGIN_FILE ), array( 'jquery', 'heartbeat', 'jquery-ui-tooltip' ), '2.0.2' );
		wp_localize_script( 'gpagespeedi_global_js', 'GPI_Global', array(
				'heartbeat' => $this->gpi_options['heartbeat'],
				'progress'	=> get_option( 'gpi_progress' )
			)
		);
	}

	public function js_templates()
	{
		if ( ! isset( $_GET['page'] ) || 'google-pagespeed-insights' != $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['render'] ) ) {
			return;
		}

		$render = sanitize_text_field( $_GET['render'] );

		switch ( $render ) {
			case 'details':
				include_once GPI_DIRECTORY . '/assets/js/templates/details/statistics.php';
				include_once GPI_DIRECTORY . '/assets/js/templates/details/legend.php';
				include_once GPI_DIRECTORY . '/assets/js/templates/details/rule_blocks.php';
				break;

			case 'summary':
				include_once GPI_DIRECTORY . '/assets/js/templates/summary/areas_of_improvement.php';
				include_once GPI_DIRECTORY . '/assets/js/templates/summary/scores.php';
				break;

			case 'view-snapshot':
				include_once GPI_DIRECTORY . '/assets/js/templates/summary/areas_of_improvement.php';
				include_once GPI_DIRECTORY . '/assets/js/templates/summary/scores.php';
				include_once GPI_DIRECTORY . '/assets/js/templates/view-snapshot/comment.php';
				break;

			case apply_filters( 'gpi_custom_js_templates', $render ):
				do_action( 'gpi_load_custom_js_template', $render );
				break;
		}
	}

	public function redirect()
	{
		if ( ! isset( $_GET['page'] ) || 'google-pagespeed-insights' != $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['render'] ) ) {
			if ( empty( $this->gpi_options['google_developer_key'] ) ) {
				wp_redirect( '?page=google-pagespeed-insights&render=options' );
			} else {
				wp_redirect( '?page=google-pagespeed-insights&render=report-list' );
			}
			exit;
		}
	}

	public function heartbeat_interval( $settings )
	{
		if ( ! isset( $_GET['page'] ) || 'google-pagespeed-insights' != $_GET['page'] ) {
			return $settings;
		}

		switch ( $this->gpi_options['heartbeat'] ) {
			case 'slow':
				$settings['interval'] = 60;
				break;

			case 'disabled':
				break;

			default:
				$settings['interval'] = 15;
				break;
		}
		
		return $settings;
	}

	public function wpe_heartbeat( $allowed_pages )
	{
		if ( ! isset( $_GET['page'] ) || 'google-pagespeed-insights' != $_GET['page'] ) {
			return $allowed_pages;
		}

		$allowed_pages[] = 'tools.php';

		return $allowed_pages;
	}

	public function get_filter_options( $options, $flatlist = false )
	{
		if ( ! $flatlist ) {
			$options['all'] = __( 'All Reports', 'gpagespeedi' );
		}

		if ( $this->gpi_options['check_pages'] ) {
			$options['page'] = __( 'Pages', 'gpagespeedi' );
		}

		if ( $this->gpi_options['check_posts'] ) {
			$options['post'] = __( 'Posts', 'gpagespeedi' );
		}

		if ( $this->gpi_options['check_categories'] ) {
			$options['category'] = __( 'Categories', 'gpagespeedi' );
		}

		if ( ! empty( $this->gpi_options['cpt_whitelist'] ) ) {
			if ( ! $flatlist ) {
				$cpt_options = array( 'gpi_custom_posts' => __( 'All Custom Post Types', 'gpagespeedi' ) );
			}

			$cpt_whitelist = maybe_unserialize( $this->gpi_options['cpt_whitelist'] );
			foreach ( $cpt_whitelist as $cpt ) {
				$cpt_options[ $cpt ] = $cpt;
			}

			if ( ! $flatlist ) {
				$options['custom_post_types'] = array(
					'optgroup_label'	=> __( 'Custom Post Types', 'gpagespeedi' ),
					'options'			=> $cpt_options
				);
			} else {
				$options = array_merge( $options, $cpt_options );
			}
		}

		if ( $this->gpi_options['check_custom_urls'] ) {
			global $wpdb;

			$gpi_custom_urls = $wpdb->prefix . 'gpi_custom_urls';
			$custom_url_types = $wpdb->get_col(
				"
				SELECT DISTINCT type
				FROM $gpi_custom_urls
				"
			);

			if ( ! empty( $custom_url_types ) ) {
				if ( ! $flatlist ) {
					$custom_url_options = array( 'gpi_custom_urls' => __( 'All Custom URLs', 'gpagespeedi' ) );
				}

				foreach ( $custom_url_types as $custom_url_type ) {
					$custom_url_options[ $custom_url_type ] = $custom_url_type;
				}

				if ( ! $flatlist ) {
					$options['custom_urls'] = array(
						'optgroup_label'	=> __( 'Custom URLs', 'gpagespeedi' ),
						'options'			=> $custom_url_options
					);
				} else {
					if ( ! empty( $custom_url_options ) ) {
						$options = array_merge( $options, $custom_url_options );
					}
				}
			}
		}

		$options = apply_filters( 'gpi_custom_filter_options', $options, $flatlist, $this->gpi_options );

		if ( ! $flatlist ) {
			return $options;
		} else {
			return array_keys( $options );
		}
	}

	public function get_custom_post_types( $cpt )
	{
		$custom_post_types = get_post_types( array(
			'public' => true,
			'_builtin' => false
		) );

		$cpt_whitelist = maybe_unserialize( $this->gpi_options['cpt_whitelist'] );
		$cpt_whitelist = is_array( $cpt_whitelist ) ? $cpt_whitelist : array();

		if ( $custom_post_types ) {
			foreach ( $custom_post_types as $custom_post_type ) {
				$cpt[] = array(
					'value'		=> $custom_post_type,
					'count'		=> wp_count_posts( $custom_post_type )->publish,
					'checked'	=> in_array( $custom_post_type, $cpt_whitelist )
				);
			}
		}

		return $cpt;
	}

	public function get_custom_urls_count( $url_count )
	{
		global $wpdb;

		$gpi_custom_urls = $wpdb->prefix. 'gpi_custom_urls';
		
		return $wpdb->get_var(
			"
			SELECT COUNT(*)
			FROM $gpi_custom_urls
			"
		);
	}

	public function get_custom_url_labels( $custom_url_labels = false )
	{
		global $wpdb;

		$gpi_custom_urls = $wpdb->prefix . 'gpi_custom_urls';

		$custom_url_labels = $wpdb->get_col(
			"
				SELECT DISTINCT type
				FROM $gpi_custom_urls
			"
		);

		if ( ! empty( $custom_url_labels ) ) {
			$custom_url_labels = implode( '|', $custom_url_labels );
		}

		return $custom_url_labels;
	}

	public function get_summary_stats( $summary_stats = array() )
	{
		global $wpdb;

		$all_types = $this->get_filter_options( array(), true );

		$gpi_page_stats = $wpdb->prefix . 'gpi_page_stats';
		$filter			= isset( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all';
		$filter			= 'all' != $filter ? $filter : implode( '|', $all_types );

		if ( 'gpi_custom_urls' == $filter ) {
			$filter = apply_filters( 'gpi_custom_url_labels', false );
		}

		$all_page_stats = $wpdb->get_results( $wpdb->prepare(
			"
				SELECT ID, URL, {$this->strategy}_score as score, {$this->strategy}_page_stats as resources
				FROM $gpi_page_stats
				WHERE type REGEXP %s
				AND {$this->strategy}_score IS NOT NULL
				ORDER BY score DESC
			",
			$filter
		), ARRAY_A );

		if ( ! $all_page_stats ) {
			return array();
		}
		
		$pages = count( $all_page_stats );
		$score = 0;

		$keys = array( 'lowest' => array( 'value' => false, 'url' => false ), 'average' => false, 'highest' => array( 'value' => false, 'url' => false ) );
		$resource_sizes = array( 'HTML' => $keys, 'CSS' => $keys, 'IMAGES' => $keys, 'JS' => $keys, 'OTHER' => $keys );
		$page_scores = array();
		$old_format_detected = false;

		foreach ( $all_page_stats as $page_stats ) {
			$score		+= $page_stats['score'];
			$resources	= unserialize( $page_stats['resources'] );
			$report_url	= admin_url( '/tools.php?page=google-pagespeed-insights&render=details&page_id=' . $page_stats['ID'] );

			$page_scores[] = array(
				'score'			=> $page_stats['score'],
				'report_url'	=> $report_url,
				'page_url'		=> $page_stats['URL']
			);

			// flag if pre 2.0 data structure is detected
			if ( ! $old_format_detected && isset( $resources['numberHosts'] ) ) {
				$old_format_detected = true;
			}

			if ( isset( $resources['resource_sizes'] ) ) {
				foreach ( $resources['resource_sizes'] as $type => $value ) {
					$resource_sizes[ $type ]['average'] += $value;

					if ( false === $resource_sizes[ $type ]['highest']['value'] || $value > $resource_sizes[ $type ]['highest']['value'] ) {
						$resource_sizes[ $type ]['highest'] = array(
							'value' => $value,
							'url' => $report_url
						);
					}

					if ( false === $resource_sizes[ $type ]['lowest']['value'] || $value < $resource_sizes[ $type ]['lowest']['value'] ) {
						$resource_sizes[ $type ]['lowest'] = array(
							'value' => $value,
							'url' => $report_url
						);
					}
				}
			}
		}

		foreach ( $resource_sizes as &$values ) {
			$values['average'] = $values['average'] / $pages;
		}

		$score = round( $score / $pages );

		$high_scores = $this->sort_array( $page_scores, 'score' );
		$low_scores = array_reverse( $high_scores );

		return array(
			'page_scores'		=> array(
				'highest'	=> array_slice( $high_scores, 0, 5 ),
				'lowest'	=> array_slice( $low_scores, 0, 5 )
			),
			'resource_sizes'	=> $resource_sizes,
			'score'				=> $score,
			'odometer_rotation'	=> ( ( 3.38 * $score ) + 11 ) . 'deg',
			'data_format'		=> ( $old_format_detected ) ? '1.0' : GPI_VERSION
		);
	}

	public function get_summary_reports( $summary_reports = array() )
	{
		global $wpdb;

		$all_types = $this->get_filter_options( array(), true );

		$gpi_page_stats		= $wpdb->prefix . 'gpi_page_stats';
		$gpi_page_reports	= $wpdb->prefix . 'gpi_page_reports';
		$filter				= isset( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all';
		$filter				= 'all' != $filter ? $filter : implode( '|', $all_types );

		if ( 'gpi_custom_urls' == $filter ) {
			$filter = apply_filters( 'gpi_custom_url_labels', false );
		}

		$all_page_reports = $wpdb->get_results( $wpdb->prepare(
			"
				SELECT r.rule_key, r.rule_name, r.rule_impact
				FROM $gpi_page_stats as s
				INNER JOIN $gpi_page_reports as r
					ON r.page_id = s.ID
					AND r.strategy = %s
				WHERE type REGEXP %s
				AND {$this->strategy}_score IS NOT NULL
				ORDER BY r.rule_impact DESC
			",
			$this->strategy,
			$filter
		), ARRAY_A );

		if ( ! $all_page_reports ) {
			return array();
		}

		foreach ( $all_page_reports as $page_report ) {
			if ( isset( $summary_reports[ $page_report['rule_key'] ] ) ) {
				$summary_reports[ $page_report['rule_key'] ]['avg_impact'] += $page_report['rule_impact'];
				$summary_reports[ $page_report['rule_key'] ]['occurances']++;
			} else {
				$summary_reports[ $page_report['rule_key'] ] = array(
					'rule_name'	=> ( 'OptimizeImages' != $page_report['rule_key'] ) ? $page_report['rule_name'] : $page_report['rule_name'] . '<span class="shortpixel_blurb"> &ndash; <a href="https://shortpixel.com/h/af/PCFTWNN142247" target="_blank">' . __( 'Auto-Optimize images with ShortPixel. Sign up for 150 free credits!', 'gpagespeedi') . '</a></span>',
					'avg_impact'	=> $page_report['rule_impact'],
					'occurances'	=> 1
				);
			}
		}

		foreach ( $summary_reports as &$summary_report ) {
			$summary_report['avg_impact'] = round( $summary_report['avg_impact'] / $summary_report['occurances'], 2 );
		}

		$summary_reports = $this->sort_array( $summary_reports, 'avg_impact' );

		return array_slice( $summary_reports, 0, 5 );
	}

	public function check_set_time_limit( $disabled )
	{
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		return in_array( 'set_time_limit', $disabled );
	}

	public function progress_heartbeat( $response, $data )
	{
		if ( ! isset( $data['gpi_heartbeat'] ) || 'progress' != $data['gpi_heartbeat'] ) {
			return;
		}

		$progress = get_option( 'gpi_progress' );

		if ( 'abort' == $progress ) {
			$response['gpi_progress'] = 'abort';
		} else if ( $progress != null ) {
			$split_status = explode( ' / ', $progress );

			$percent_complete = $split_status[0] / $split_status[1];
			$percent_complete = round( $percent_complete * 100 );

			$response['gpi_progress'] = $percent_complete;
		} else {
			$response['gpi_progress'] = 'done';
		}

		return $response;
	}

	public function get_error_logs( $error_logs )
	{
		global $wpdb;

		$gpi_api_error_logs = $wpdb->prefix . 'gpi_api_error_logs';

		$error_logs = $wpdb->get_results(
			"
			SELECT URL, strategy, is_update, type, timestamp, error
			FROM $gpi_api_error_logs
			ORDER BY timestamp DESC
			", ARRAY_A
		);

		return $error_logs;
	}

	private function get_page_stats( $page_id )
	{
		global $wpdb;

		$gpi_page_stats = $wpdb->prefix . 'gpi_page_stats';

		$page_stats = $wpdb->get_row( $wpdb->prepare(
			"
				SELECT URL, {$this->strategy}_page_stats as resources, {$this->strategy}_score as score, {$this->strategy}_last_modified as last_modified
				FROM $gpi_page_stats
				WHERE ID = %d
			",
			$page_id
		), ARRAY_A );

		if ( empty( $page_stats ) ) {
			return false;
		}

		// flag if pre 2.0 data structure is detected
		if ( strpos( $page_stats['resources'], 'numberHosts' ) !== false ) {
			$page_stats['data_format'] = '1.0';
		} else {
			$page_stats['data_format'] = GPI_VERSION;
		}

		if ( ! empty( $page_stats['score'] ) ) {
			$page_stats['odometer_rotation'] = ( ( 3.38 * $page_stats['score'] ) + 11 ) . 'deg';
		} else if ( '0' == $page_stats['score'] ) {
			$page_stats['odometer_rotation'] = '12deg';
		} else {
			$page_stats['score'] = '-';
		}

		if ( ! empty( $page_stats['last_modified'] ) ) {
			$page_stats['last_modified'] = date_i18n( 'M d g:ia', $page_stats['last_modified'] );
		} else {
			$page_stats['last_modified'] = __( 'N/A', 'gpagespeedi' );
		}

		return array_map( 'maybe_unserialize', $page_stats );
	}

	private function get_page_reports( $page_id )
	{
		global $wpdb;

		$gpi_page_reports = $wpdb->prefix . 'gpi_page_reports';

		$page_reports = $wpdb->get_results( $wpdb->prepare(
			"
				SELECT rule_key, rule_name, rule_impact, rule_blocks
				FROM $gpi_page_reports
				WHERE page_id = %d
				AND strategy = %s
				ORDER BY rule_impact DESC
			",
			$page_id,
			$this->strategy
		), ARRAY_A );

		if ( $page_reports ) {
			foreach ( $page_reports as &$page_report ) {
				$page_report['rule_blocks'] = unserialize( $page_report['rule_blocks'] );

				if ( 'OptimizeImages' == $page_report['rule_key'] ) {
					$page_report['rule_blocks'] = $this->shortpixel_image_rule_blocks( $page_report['rule_blocks'] );
				}
			}
		}

		return $page_reports;
	}

	private function get_snapshot_data()
	{
		global $wpdb;

		$gpi_summary_snapshots = $wpdb->prefix . 'gpi_summary_snapshots';

		if ( isset( $_GET['snapshot_id'] ) ) {
			$snapshot = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT strategy, type, snaptime, comment, summary_stats, summary_reports
				FROM $gpi_summary_snapshots
				WHERE ID = %d
				",
				$_GET['snapshot_id']
			), ARRAY_A );
		} else {
			$snapshot = false;
		}

		if ( isset( $_GET['compare_id'] ) ) {
			$compare_snapshot = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT strategy, type, snaptime, comment, summary_stats, summary_reports
				FROM $gpi_summary_snapshots
				WHERE ID = %d
				",
				$_GET['compare_id']
			), ARRAY_A );
		} else {
			$compare_snapshot = false;
		}

		return array(
			'snapshot'	=> $snapshot,
			'compare'	=> $compare_snapshot
		);
	}

	public function get_similar_snapshots( $similar_snapshots, $current_snapshot_id )
	{
		if ( $current_snapshot_id ) {
			global $wpdb;

			$gpi_summary_snapshots = $wpdb->prefix . 'gpi_summary_snapshots';

			$current_snapshot = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT strategy, type
				FROM $gpi_summary_snapshots
				WHERE ID = %d
				",
				$current_snapshot_id
			), ARRAY_A );

			$similar_snapshots = $wpdb->get_results( $wpdb->prepare(
				"
				SELECT ID, snaptime 
				FROM $gpi_summary_snapshots
				WHERE strategy = %s
				AND type = %s
				",
				$current_snapshot['strategy'],
				$current_snapshot['type']
			), ARRAY_A );
		}

		return $similar_snapshots;
	}

	private function shortpixel_image_rule_blocks( $rule_blocks )
	{
		if ( ! isset( $rule_blocks[0]['urls'] ) ) {
			return $rule_blocks;
		}

		foreach ( $rule_blocks as $index => $rule_block ) {
			foreach ( $rule_block['urls'] as $key => $rule ) {
				$rule_blocks[ $index ]['urls'][ $key ] = preg_replace(
					'/(?:href=")(.*?)(?:")/',
					'href="https://shortpixel.com/gpi/af/PCFTWNN142247?site-url=$1"',
					$rule
				);
			}
		}

		return $rule_blocks;
	}

	private function sort_array( $array, $key )
	{
		usort( $array, function( $a, $b ) use ( $key ) {
			if ( abs( $a[ $key ] - $b[ $key ] ) < 0.00000001 ) {
				return 0; // almost equal
			} else if ( ( $a[ $key ] - $b[ $key ] ) < 0 ) {   
				return 1;
			} else {
				return -1;
			}
		});

		return $array;
	}

}

add_action( 'plugins_loaded', array( new GPI_Admin, 'init' ) );
