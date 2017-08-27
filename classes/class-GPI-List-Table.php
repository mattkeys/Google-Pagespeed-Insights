<?php
/**
 * =======================================
 * Google Pagespeed Insights List Table
 * =======================================
 * 
 * 
 * @author Matt Keys <https://profiles.wordpress.org/mattkeys>
 */

if ( ! defined( 'GPI_PLUGIN_FILE' ) ) {
	die();
}

if ( ! class_exists('WP_List_Table') ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GPI_List_Table extends WP_List_Table
{	
	private $gpi_options;
	private $type;
	private $strategy;
	private $columns;
	private $sortable;
	private $per_page;
	private $table;
	private $db_columns;
	private $orderby;
	private $order;

	public function __construct( $type = 'default' )
	{
		global $wpdb;

		parent::__construct( array(
			'singular'  => 'gpi_page_report',
			'plural'    => 'gpi_page_reports',
			'ajax'      => false
		));

		$this->gpi_options		= get_option('gpagespeedi_options');
		$this->type				= $type;
		$this->strategy			= $this->gpi_options['strategy'];
		$this->columns			= $this->get_columns();
		$this->sortable			= $this->get_sortable_columns();
		$this->per_page			= isset( $_GET['post-per-page']) ? $_GET['post-per-page'] : 25;
		$this->_column_headers	= array( $this->columns, array(), $this->sortable );
		$this->orderby			= isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'ID';
		$this->order			= isset( $_GET['order'] ) ? $_GET['order'] : 'asc';

		switch ( $type ) {
			case 'ignored-urls':
				$this->table		= $wpdb->prefix . 'gpi_page_blacklist';
				$this->db_columns	= array( 'ID', 'URL', 'type' );
				break;

			case 'snapshots':
				$this->table		= $wpdb->prefix . 'gpi_summary_snapshots';
				$this->db_columns	= array( 'ID', 'strategy', 'type', 'snaptime', 'comment' );
				break;

			case 'custom-urls':
				$this->table		= $wpdb->prefix . 'gpi_custom_urls';
				$this->db_columns	= array( 'ID', 'URL', 'type' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_1_type', 'reserved_for_internal_use' ):
				$this->table		= apply_filters( 'gpi_list_table_custom_case_1_table', 'reserved_for_internal_use' );
				$this->db_columns	= apply_filters( 'gpi_list_table_custom_case_1_db_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_2_type', 'reserved_for_internal_use' ):
				$this->table		= apply_filters( 'gpi_list_table_custom_case_2_table', 'reserved_for_internal_use' );
				$this->db_columns	= apply_filters( 'gpi_list_table_custom_case_2_db_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_3_type', 'reserved_for_internal_use' ):
				$this->table		= apply_filters( 'gpi_list_table_custom_case_3_table', 'reserved_for_internal_use' );
				$this->db_columns	= apply_filters( 'gpi_list_table_custom_case_3_db_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_4_type', 'available_for_custom_integration' ):
				$this->table		= apply_filters( 'gpi_list_table_custom_case_4_table', 'available_for_custom_integration' );
				$this->db_columns	= apply_filters( 'gpi_list_table_custom_case_4_db_columns', 'available_for_custom_integration' );
				break;

			default:
				$this->table		= $wpdb->prefix . 'gpi_page_stats';
				$this->db_columns	= array( 'ID', 'URL', 'desktop_score', 'mobile_score', 'type', 'desktop_last_modified', 'mobile_last_modified' );
				break;
		}
	}

	protected function display_tablenav( $which )
	{
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'], '_wpnonce', false );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ): ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}

	public function human_timing( $time )
	{
		if ( empty( $time ) ) {
			return 'N/A';
		}
		$time = current_time( 'timestamp' ) - $time;

		$tokens = array (
			31536000 => __( 'year', 'gpagespeedi' ),
			2592000 => __( 'month', 'gpagespeedi' ),
			604800 => __( 'week', 'gpagespeedi' ),
			86400 => __( 'day', 'gpagespeedi' ),
			3600 => __( 'hour', 'gpagespeedi' ),
			60 => __( 'minute', 'gpagespeedi' ),
			1 => __( 'second', 'gpagespeedi' )
		);

		foreach ( $tokens as $unit => $text ) {
			if ( $time < $unit ) {
				continue;
			}
			$number_of_units = floor( $time / $unit );

			return $number_of_units . ' ' . $text . ( ( $number_of_units > 1 ) ? __( 's ago', 'gpagespeedi' ) : ' ' . __( 'ago', 'gpagespeedi' ) );
		}
	}

	public function no_items()
	{
		$pagetype = $_GET['render'];

		switch( $pagetype )
		{
			case 'ignored-urls':
				_e( 'No Ignored URLs found. A URL can be ignored from the <a href="?page=' . $_REQUEST['page'] . '&render=report-list">Report List</a> page if you would like to remove it from report pages', 'gpagespeedi' );
				break;

			case 'snapshots':
				_e( 'No Snapshots found. Snapshots can be created from the', 'gpagespeedi' ) . ' ' . '<a href="?page=' . $_REQUEST['page'] . '&render=summary">' . __( 'Report Summary', 'gpagespeedi' ) . '</a>' . ' ' . __( 'page', 'gpagespeedi' ) . '.';
				break;

			case 'custom-urls':
				_e( 'No Custom URLs found. Click "Add New URLs" or "Bulk Upload New URLs" above to add custom URLs.', 'gpagespeedi' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_1_type', 'reserved_for_internal_use' ):
				echo apply_filters( 'gpi_list_table_custom_case_1_no_items', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_2_type', 'reserved_for_internal_use' ):
				echo apply_filters( 'gpi_list_table_custom_case_2_no_items', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_3_type', 'reserved_for_internal_use' ):
				echo apply_filters( 'gpi_list_table_custom_case_3_no_items', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_4_type', 'available_for_custom_integration' ):
				echo apply_filters( 'gpi_list_table_custom_case_4_no_items', 'available_for_custom_integration' );
				break;

			default:
				_e( 'No Pagespeed Reports Found. Google Pagespeed may still be checking your pages. If problems persist, see the following possible solutions:', 'gpagespeedi' );
				?>
				<ol class="no-items">
					<li><?php _e( 'Make sure that you have entered your Google API key on the ', 'gpagespeedi' );?><a href="?page=<?php echo $_REQUEST['page']; ?>&amp;render=options"><?php _e( 'Options', 'gpagespeedi' ); ?></a> <?php _e( 'page', 'gpagespeedi' ); ?>.</li>
					<li><?php _e( 'Make sure that you have enabled "PageSpeed Insights API" from the Services page of the ', 'gpagespeedi' );?><a href="https://code.google.com/apis/console/">Google Console</a>.</li>
					<li><?php _e( 'Make sure that your URLs are publicly accessible', 'gpagespeedi' ); ?>.</li>
				</ol>
				<?php
				break;
		}
	}
	
	public function strip_domain( $url )
	{
		$siteurl = get_site_url();
		$siteurl_ssl = get_site_url( '', '', 'https' );

		$search_urls = array( $siteurl, $siteurl_ssl );

		$cleaned_url = str_replace( $search_urls, '', $url );

		if ( '/' == $cleaned_url ) {
			$cleaned_url .= ' (' . __( 'homepage', 'gpagespeedi' ) . ')';
		}

		return $cleaned_url;
	}

	public function column_default( $item, $column_name )
	{
		switch( $column_name )
		{
			case 'desktop_last_modified':
				$formatted_time = $this->human_timing( $item['desktop_last_modified'] );
				return $formatted_time;

			case 'mobile_last_modified':
				$formatted_time = $this->human_timing( $item['mobile_last_modified'] );
				return $formatted_time;

			case 'type':
				return $item[ $column_name ];

			case 'custom_url':
				$actions = array(
					'delete'    => sprintf( '?page=%s&render=%s&action=%s&page_id=%s', $_REQUEST['page'], 'custom-urls', 'delete', $item['ID'] ),
					'visit'     => sprintf( '<a href="%s" target="_blank">%s</a>', $item['URL'], __( 'View URL', 'gpagespeedi' ) )
				);
				
				$nonced_url = wp_nonce_url( $actions['delete'], 'bulk-gpi_page_reports' );
				$actions['delete'] = '<a href="' . $nonced_url . '">' . __( 'Delete', 'gpagespeedi') . '</a>';

				return sprintf( '%1$s %2$s',
					$item['URL'],
					$this->row_actions( $actions )
				);

			case 'snaptime':
				$date = $item['snaptime'];
				$date = date( 'M d, Y - h:i a', $date );

				$actions = array(
					'delete'    => sprintf( '?page=%s&render=%s&action=%s&snapshot_id=%s' ,$_REQUEST['page'], 'snapshots', 'delete-snapshot', $item['ID'] ),
					'view'      => sprintf( '<a href="?page=%s&render=%s&snapshot_id=%s">%s</a>' , $_REQUEST['page'], 'view-snapshot', $item['ID'], __( 'View Snapshot', 'gpagespeedi' ) )
				);
				
				$nonced_url = wp_nonce_url( $actions['delete'], 'bulk-gpi_page_reports' );
				$actions['delete'] = '<a href="' . $nonced_url . '">' . __('Delete', 'gpagespeedi') . '</a>';


				return sprintf( '<a href="?page=%1$s&render=%2$s&snapshot_id=%3$s">%4$s</a> %5$s',
					$_REQUEST['page'],
					'view-snapshot',
					$item['ID'],
					$date,
					$this->row_actions( $actions )
				);

			case 'snapfilter':
				$filter = $item['type'];
				$filter_search = array( 'all', 'page', 'post', 'category', 'gpi_custom_posts-', 'gpi_custom_urls-', 'gpi_custom_posts', 'gpi_custom_urls' );
				$filter_replace = array( __( 'All Reports', 'gpagespeedi' ), __( 'Pages', 'gpagespeedi' ), __( 'Posts', 'gpagespeedi' ), __( 'Categories', 'gpagespeedi' ), '', '', __( 'All Custom Post Types', 'gpagespeedi' ), __( 'All Custom URLs', 'gpagespeedi' ) );
				$cleaned_filter = str_replace( $filter_search, $filter_replace, $filter );

				return $cleaned_filter;

			case apply_filters( 'gpi_custom_column', $column_name ):
				return apply_filters( 'gpi_custom_column_config', $column_name, $item );

			default:
				return $item[ $column_name ];
		}
	}
	
	public function column_url( $item )
	{
		$cleaned_url = $this->strip_domain( $item['URL'] );

		$actions = array(
			'view_details'  => sprintf( '<a href="?page=%s&render=%s&page_id=%s">%s</a>', $_REQUEST['page'], 'details', $item['ID'], __( 'Details', 'gpagespeedi' ) ),
			'ignore'        => sprintf( '?page=%s&render=%s&action=%s&page_id=%s', $_REQUEST['page'], 'report-list', 'ignore', $item['ID'] ),
			'delete_report' => sprintf( '?page=%s&render=%s&action=%s&page_id=%s', $_REQUEST['page'], 'report-list', 'delete_report', $item['ID'] ),
			'visit'         => sprintf( '<a href="%s" target="_blank">%s</a>', $item['URL'], __( 'View URL', 'gpagespeedi' ) )
		);

		$actions['ignore'] = '<a href="' . wp_nonce_url( $actions['ignore'], 'bulk-gpi_page_reports' ) . '">'.__( 'Ignore', 'gpagespeedi' ) . '</a>';
		$actions['delete_report'] = '<a href="' . wp_nonce_url( $actions['delete_report'], 'bulk-gpi_page_reports' ) . '">'.__( 'Delete', 'gpagespeedi' ) . '</a>';

		return sprintf( '<a href="?page=%3$s&render=%4$s&page_id=%5$s">%1$s</a> %2$s',
			$cleaned_url,
			$this->row_actions( $actions ),
			$_REQUEST['page'],
			'details',
			$item['ID']
		);
	}

	public function column_ignored_url( $item )
	{
		$cleaned_url = $this->strip_domain( $item['URL'] );

		$actions = array(
			'reactivate'  => sprintf( '?page=%s&render=%s&action=%s&page_id=%s', $_REQUEST['page'], 'ignored-urls', 'reactivate', $item['ID'] ),
			'delete_blacklist' => sprintf( '?page=%s&render=%s&action=%s&page_id=%s', $_REQUEST['page'], 'ignored-urls', 'delete_blacklist', $item['ID'] ),
			'visit'     => sprintf( '<a href="%s" target="_blank">%s</a>', $item['URL'], __( 'View URL', 'gpagespeedi' ) )
		);

		$actions['reactivate'] = '<a href="' . wp_nonce_url( $actions['reactivate'], 'bulk-gpi_page_reports' ) . '">' . __( 'Reactivate', 'gpagespeedi' ) . '</a>';
		$actions['delete_blacklist'] = '<a href="' . wp_nonce_url( $actions['delete_blacklist'], 'bulk-gpi_page_reports' ) . '">'.__( 'Delete', 'gpagespeedi' ) . '</a>';

		return sprintf( '%1$s %2$s',
			$cleaned_url,
			$this->row_actions( $actions )
		);
	}

	public function column_mobile_score( $item )
	{
		if ( empty( $item['mobile_score'] ) && '0' != $item['mobile_score'] ) {
			return 'N/A';
		}

		$gradient = array( '', 'FF0000', 'FE0500', 'FE0A00', 'FE0F00', 'FE1400', 'FE1900', 'FE1E00', 'FE2300', 'FE2800', 'FE2D00', 'FE3300', 'FE3800', 'FE3D00', 'FE4200', 'FE4700', 'FE4C00', 'FE5100', 'FD5600', 'FD5B00', 'FD6000', 'FD6600', 'FD6B00', 'FD7000', 'FD7500', 'FD7A00', 'FD7F00', 'FD8400', 'FD8900', 'FD8E00', 'FD9300', 'FD9900', 'FD9E00', 'FDA300', 'FDA800', 'FCAD00', 'FCB200', 'FCB700', 'FCBC00', 'FCC100', 'FCC600', 'FCCC00', 'FCD100', 'FCD600', 'FCDB00', 'FCE000', 'FCE500', 'FCEA00', 'FCEF00', 'FCF400', 'FCF900', 'FCFF00', 'F7FF00', 'F2FF00', 'EEFF00', 'E9FF00', 'E4FF00', 'E0FF00', 'DBFF00', 'D6FF00', 'D2FF00', 'CDFF00', 'C8FF00', 'C4FF00', 'BFFF00', 'BAFF00', 'B6FF00', 'B1FF00', 'ACFF00', 'A8FF00', 'A3FF00', '9EFF00', '9AFF00', '95FF00', '90FF00', '8CFF00', '87FF00', '83FF00', '7EFF00', '79FF00', '75FF00', '70FF00', '6BFF00', '67FF00', '62FF00', '5DFF00', '59FF00', '54FF00', '4FFF00', '4BFF00', '46FF00', '41FF00', '3DFF00', '38FF00', '33FF00', '2FFF00', '2AFF00', '25FF00', '21FF00', '1CFF00', '18FF00' );
		$barcolor = $gradient[ $item['mobile_score'] ];
		$innerdiv_css = 'background-color:#' . $barcolor . ';width:' . $item['mobile_score'] . '%';

		return sprintf( '<span class="scorenum">%1$s</span><div class="reportscore_outter_bar"><div class="reportscore_inner_bar" style="%2$s"></div></div>', $item['mobile_score'], $innerdiv_css );
	}

	public function column_desktop_score( $item )
	{
		if ( empty( $item['desktop_score'] ) && '0' != $item['desktop_score'] ) {
			return 'N/A';
		}

		$gradient = array( '', 'FF0000', 'FE0500', 'FE0A00', 'FE0F00', 'FE1400', 'FE1900', 'FE1E00', 'FE2300', 'FE2800', 'FE2D00', 'FE3300', 'FE3800', 'FE3D00', 'FE4200', 'FE4700', 'FE4C00', 'FE5100', 'FD5600', 'FD5B00', 'FD6000', 'FD6600', 'FD6B00', 'FD7000', 'FD7500', 'FD7A00', 'FD7F00', 'FD8400', 'FD8900', 'FD8E00', 'FD9300', 'FD9900', 'FD9E00', 'FDA300', 'FDA800', 'FCAD00', 'FCB200', 'FCB700', 'FCBC00', 'FCC100', 'FCC600', 'FCCC00', 'FCD100', 'FCD600', 'FCDB00', 'FCE000', 'FCE500', 'FCEA00', 'FCEF00', 'FCF400', 'FCF900', 'FCFF00', 'F7FF00', 'F2FF00', 'EEFF00', 'E9FF00', 'E4FF00', 'E0FF00', 'DBFF00', 'D6FF00', 'D2FF00', 'CDFF00', 'C8FF00', 'C4FF00', 'BFFF00', 'BAFF00', 'B6FF00', 'B1FF00', 'ACFF00', 'A8FF00', 'A3FF00', '9EFF00', '9AFF00', '95FF00', '90FF00', '8CFF00', '87FF00', '83FF00', '7EFF00', '79FF00', '75FF00', '70FF00', '6BFF00', '67FF00', '62FF00', '5DFF00', '59FF00', '54FF00', '4FFF00', '4BFF00', '46FF00', '41FF00', '3DFF00', '38FF00', '33FF00', '2FFF00', '2AFF00', '25FF00', '21FF00', '1CFF00', '18FF00' );
		$barcolor = $gradient[ $item['desktop_score'] ];
		$innerdiv_css = 'background-color:#' . $barcolor . ';width:' . $item['desktop_score'] . '%';

		return sprintf( '<span class="scorenum">%1$s</span><div class="reportscore_outter_bar"><div class="reportscore_inner_bar" style="%2$s"></div></div>', $item['desktop_score'], $innerdiv_css );
	}

	public function column_cb( $item )
	{
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item['ID']
		);
	}

	public function get_columns()
	{
		switch ( $this->type ) {
			case 'ignored-urls':
				$columns = array(
					'cb'			=> '<input type="checkbox" />',
					'ignored_url'	=> __( 'Ignored URL', 'gpagespeedi' ),
					'type'			=> __( 'Page Type', 'gpagespeedi' )
				);
				break;

			case 'snapshots':
				$columns = array(
					'cb'			=> '<input type="checkbox" />',
					'snaptime'		=> __( 'Snapshot Date', 'gpagespeedi' ),
					'snapfilter'	=> __( 'Report Description', 'gpagespeedi' ),
					'strategy'		=> __( 'Report Type', 'gpagespeedi' ),
					'comment'		=> __( 'Comment', 'gpagespeedi' )
				);
				break;

			case 'custom-urls':
				$columns = array(
					'cb'			=> '<input type="checkbox" />',
					'custom_url'	=> __( 'Custom URL', 'gpagespeedi' ),
					'type'			=> __( 'Page Type', 'gpagespeedi' )
				);
				break;

			case apply_filters( 'gpi_list_table_custom_case_1_type', 'reserved_for_internal_use' ):
				$columns = apply_filters( 'gpi_list_table_custom_case_1_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_2_type', 'reserved_for_internal_use' ):
				$columns = apply_filters( 'gpi_list_table_custom_case_2_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_3_type', 'reserved_for_internal_use' ):
				$columns = apply_filters( 'gpi_list_table_custom_case_3_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_4_type', 'available_for_custom_integration' ):
				$columns = apply_filters( 'gpi_list_table_custom_case_4_columns', 'available_for_custom_integration' );
				break;

			default:
				if ( $this->strategy == 'desktop' ) {
					$columns = array(
						'cb'					=> '<input type="checkbox" />',
						'url'					=> __( 'URL', 'gpagespeedi' ),
						'desktop_score'	=> __( 'Score', 'gpagespeedi' ),
						'type'					=> __( 'Page Type', 'gpagespeedi' ),
						'desktop_last_modified'	=> __( 'Last Checked', 'gpagespeedi' )
					);
				} else if ( $this->strategy == 'mobile' ) {
					$columns = array(
						'cb'					=> '<input type="checkbox" />',
						'url'					=> __( 'URL', 'gpagespeedi' ),
						'mobile_score'	=> __( 'Score', 'gpagespeedi' ),
						'type'					=> __( 'Page Type', 'gpagespeedi' ),
						'mobile_last_modified'	=> __( 'Last Checked', 'gpagespeedi' )
					);
				} else {
					$columns = array(
						'cb'					=> '<input type="checkbox" />',
						'url'					=> __( 'URL', 'gpagespeedi' ),
						'desktop_score'	=> __( 'Score (Desktop)', 'gpagespeedi' ),
						'mobile_score'	=> __( 'Score (Mobile)', 'gpagespeedi' ),
						'type'					=> __( 'Page Type', 'gpagespeedi' ),
						'desktop_last_modified'	=> __( 'Last Checked (Desktop)', 'gpagespeedi' ),
						'mobile_last_modified'	=> __( 'Last Checked (Mobile)', 'gpagespeedi' )
					);
				}
				break;
		}

		return $columns;
	}
	
	public function get_sortable_columns()
	{
		$filter = ( isset( $_GET['filter'] ) ) ? $_GET['filter'] : 'all';

		switch ( $this->type ) {
			case 'ignored-urls':
				$sortable_columns = array(
					'type' => array( 'type', false )
				);
				break;

			case 'snapshots':
				$sortable_columns = array(
					'snaptime'		=> array( 'snaptime', false ),
					'snapfilter'	=> array( 'type', false ),
					'strategy'		=> array( 'strategy', false )
				);
				break;

			case 'custom-urls':
				$sortable_columns = array(
					'type' => array( 'type', false )
				);
				break;

			case apply_filters( 'gpi_list_table_custom_case_1_type', 'reserved_for_internal_use' ):
				$sortable_columns = apply_filters( 'gpi_list_table_custom_case_1_sortable_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_2_type', 'reserved_for_internal_use' ):
				$sortable_columns = apply_filters( 'gpi_list_table_custom_case_2_sortable_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_3_type', 'reserved_for_internal_use' ):
				$sortable_columns = apply_filters( 'gpi_list_table_custom_case_3_sortable_columns', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_4_type', 'available_for_custom_integration' ):
				$sortable_columns = apply_filters( 'gpi_list_table_custom_case_4_sortable_columns', 'available_for_custom_integration' );
				break;

			default:
				if ( $filter == 'all' || $filter == 'custom_posts' || $filter == 'custom_urls' ) {
					$sortable_columns = array(
						'desktop_score'	=> array( 'desktop_score', false ),
						'mobile_score'	=> array( 'mobile_score', false ),
						'type'					=> array( 'type', false )
					);
				} else {
					$sortable_columns = array(
						'desktop_score'	=> array( 'desktop_score', false ),
						'mobile_score'	=> array( 'mobile_score', false )
					);
				}
				break;
		}

		return $sortable_columns;
	}

	public function get_bulk_actions()
	{
		$render = ( isset( $_GET['render'] ) ) ? $_GET['render'] : '';

		switch ( $render ) {
			case 'ignored-urls':
				$actions = array(
					'reactivate' => __( 'Reactivate', 'gpagespeedi' ),
					'delete_blacklist' => __( 'Delete URL', 'gpagespeedi' )
				);
				break;

			case 'snapshots':
				$actions = array(
					'delete-snapshot' => __( 'Delete', 'gpagespeedi' )
				);
				break;

			case 'custom-urls':
				$actions = array(
					'delete' => __( 'Delete', 'gpagespeedi' )
				);
				break;

			case apply_filters( 'gpi_list_table_custom_case_1_type', 'reserved_for_internal_use' ):
				$actions = apply_filters( 'gpi_list_table_custom_case_1_bulk_actions', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_2_type', 'reserved_for_internal_use' ):
				$actions = apply_filters( 'gpi_list_table_custom_case_2_bulk_actions', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_3_type', 'reserved_for_internal_use' ):
				$actions = apply_filters( 'gpi_list_table_custom_case_3_bulk_actions', 'reserved_for_internal_use' );
				break;

			case apply_filters( 'gpi_list_table_custom_case_4_type', 'available_for_custom_integration' ):
				$actions = apply_filters( 'gpi_list_table_custom_case_4_bulk_actions', 'available_for_custom_integration' );
				break;

			default:
				$actions = array(
					'ignore' => __( 'Ignore Reports', 'gpagespeedi' ),
					'delete_report' => __( 'Delete Reports', 'gpagespeedi' )
				);
				break;
		}

		return $actions;
	}

	public function extra_tablenav( $which )
	{
		global $wpdb;

		$post_per_page = ( isset( $_GET['post-per-page'] ) ) ? $_GET['post-per-page'] : 25;

		if ( 'top' == $which ) {
			?>
			<div class="alignleft actions">
				<?php if ( isset( $_GET['render'] ) && ( $_GET['render'] == 'report-list' || $_GET['render'] == 'summary' ) ) : ?>
				<select name="filter" id="filter">
				<?php
					$filter_options = apply_filters( 'gpi_filter_options', array(), false );

					if ( $filter_options ) :
						foreach ( $filter_options as $value => $label ) :
							$current_filter = isset( $_GET['filter'] ) ? $_GET['filter'] : 'all';

							if ( is_array( $label ) ) :
								?>
								<optgroup label="<?php echo $label['optgroup_label']; ?>">
								<?php
									foreach ( $label['options'] as $sub_value => $sub_label ) :
										?>
										<option value="<?php echo $sub_value; ?>" <?php selected( $sub_value, $current_filter ); ?>><?php echo $sub_label; ?></option>
										<?php
									endforeach;
								?>
								</optgroup>
								<?php
							else :
								?>
								<option value="<?php echo $value; ?>" <?php selected( $value, $current_filter ); ?>><?php echo $label; ?></option>
								<?php
							endif; 
						endforeach;
					endif;
				?>
				</select>
				<?php endif; ?>
				<?php if ( isset( $_GET['render'] ) && $_GET['render'] != 'summary') : ?>
				<select name="post-per-page" id="post-per-page">
					<option value="25" <?php selected( $post_per_page, 25 ); ?>><?php _e( '25 Results/Page', 'gpagespeedi' ); ?></option>
					<option value="50" <?php selected( $post_per_page, 50 ); ?>><?php _e( '50 Results/Page', 'gpagespeedi' ); ?></option>
					<option value="100" <?php selected( $post_per_page, 100 ); ?>><?php _e( '100 Results/Page', 'gpagespeedi' ); ?></option>
					<option value="500" <?php selected( $post_per_page, 500 ); ?>><?php _e( '500 Results/Page', 'gpagespeedi' ); ?></option>
					<option value="1000" <?php selected( $post_per_page, 1000 ); ?>><?php _e( '1000 Results/Page', 'gpagespeedi' ); ?></option>
				</select>
				<?php endif; ?>
				<?php
					submit_button( __( 'Filter', 'gpagespeedi' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );
				?>

				<?php if ( 'custom-urls' == $_GET['render'] ) : ?>
					<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;render=add-custom-urls" class="button-secondary"><?php _e( 'Add New URLs', 'gpagespeedi' ); ?></a>
					<a href="?page=<?php echo $_REQUEST['page']; ?>&amp;render=add-custom-urls-bulk" class="button-secondary"><?php _e( 'Bulk Upload New URLs', 'gpagespeedi' ); ?></a>
				<?php endif; ?>

				<?php do_action( 'gpi_after_tablenav', $_GET['render'] ); ?>

			</div>
		<?php
		}
	}

	public function prepare_items()
	{
		global $wpdb;

		$db_columns = implode( ',', $this->db_columns );
		$orderby = in_array( $this->orderby, $this->db_columns ) ? $this->orderby : 'ID';
		$order = ( 'asc' == strtolower( $this->order ) || 'desc' == strtolower( $this->order ) ) ? $this->order : 'asc';

		$all_types = apply_filters( 'gpi_filter_options', array(), true );

		if ( 'default' == $this->type ) {
			$filter	= isset( $_GET['filter'] ) ? $_GET['filter'] : 'all';
			$filter	= 'all' != $filter ? $filter : implode( '|', $all_types );
			$filter	= 'gpi_custom_urls' != $filter ? $filter : apply_filters( 'gpi_custom_url_labels', $filter );

			$data = $wpdb->get_results( $wpdb->prepare(
				"
					SELECT $db_columns
					FROM $this->table
					WHERE type REGEXP %s
					ORDER BY $orderby $order
				",
				$filter
			), ARRAY_A );
		} else {
			$data = $wpdb->get_results(
				"
					SELECT $db_columns
					FROM $this->table
					ORDER BY $orderby $order
				"
			, ARRAY_A );
		}

		$current_page = $this->get_pagenum();
		$total_items = count( $data );
		
		// Slice up our data for Pagination
		$data = array_slice( $data, ( ( $current_page - 1 ) * $this->per_page ), $this->per_page );
		
		// Return sorted data to be used
		$this->items = $data;
		
		// Register pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total_items / $this->per_page )
		));
	}

}
