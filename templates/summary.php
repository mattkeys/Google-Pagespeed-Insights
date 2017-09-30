<?php
/**
 * Template - Summary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="tablenav top">
	<div class="alignleft actions">
		<form method="get" action="" id="filter" name="filter">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
			<input type="hidden" name="render" value="summary" />
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
			<?php
				submit_button( __( 'Filter', 'gpagespeedi' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );
			?>
		</form>
	</div>
	<div class="alignleft actions">
		<form method="post" action="" id="savesnapshot" name="savesnapshot">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
			<input type="hidden" name="render" value="summary" />
			<input type="hidden" name="action" value="save-snapshot" />
			<input type="text" name="comment" placeholder="<?php _e( 'Report Description', 'gpagespeedi' ); ?>" value="" />
			<?php
				wp_nonce_field( 'gpi_save_snapshot' );
				submit_button( __( 'Save Report Snapshot', 'gpagespeedi' ), 'button', 'save-snapshot', false );
			?>
		</form>
	</div>

	<?php do_action( 'gpi_summary_tablenav' ); ?>

	<br class="clear">
</div>

<!--Div's to hold output from google charts-->
<div id="results">
	<div class="row">
		<div class="top-row boxsizing pagespeed_gauge_wrapper" id="pagespeed_gauge_wrapper">
			<div class="score_chart_div" id="score_chart_div">
				<img class="pagespeed_needle" id="pagespeed_needle" src="<?php echo GPI_PUBLIC_PATH; ?>assets/images/pagespeed_gauge_needle.png" width="204" height="204" alt="" />
				<div class="score_text" id="score_text"><span class="score"></span><span class="label"><?php _e( 'score', 'gpagespeedi' ); ?></span></div>
			</div>
		</div>
		<div class="top-row boxsizing framed pagespeed_avg_sizes_wrapper" id="pagespeed_avg_sizes_wrapper">
			<div class="boxheader">
				<span class="left"><?php _e( 'Size of Resources (in kB)', 'gpagespeedi' ); ?></span>
				<span class="right light"><span class="legend low"></span><?php _e( 'Lowest', 'gpagespeedi' ); ?></span>
				<span class="right light"><span class="legend avg"></span><?php _e( 'Average', 'gpagespeedi' ); ?></span>
				<span class="right light"><span class="legend"></span><?php _e( 'Highest', 'gpagespeedi' ); ?></span>
			</div>
			<div class="sizes_chart_div" id="sizes_chart_div"></div>
		</div>
	</div>
	<div class="row boxsizing framed largest_improvement" id="largest_improvement">
		<div class="boxheader">
			<span class="left"><?php _e( 'Largest Areas for Improvement', 'gpagespeedi' ); ?></span>
			<span class="right"><?php _e( 'Pages Impacted', 'gpagespeedi' ); ?></span>
			<span class="right"><?php _e( 'Average Impact', 'gpagespeedi' ); ?></span>
		</div>
		<table class="stats"></table>
	</div>
	<div class="row scores_div">
		<div class="halfwidth boxsizing framed left highest_scores" id="highest_scores">
			<div class="boxheader">
				<span class="left"><?php _e( 'Highest Scoring Pages', 'gpagespeedi' ); ?></span>
				<span class="right"><?php _e( 'Score', 'gpagespeedi' ); ?></span>
			</div>
			<table class="stats"></table>
		</div>
		<div class="halfwidth boxsizing framed right lowest_scores" id="lowest_scores">
			<div class="boxheader">
				<span class="left"><?php _e( 'Lowest Scoring Pages', 'gpagespeedi' ); ?></span>
				<span class="right"><?php _e( 'Score', 'gpagespeedi' ); ?></span>
			</div>
			<table class="stats"></table>
		</div>
	</div>
</div>

<div id="no_results">
	<p>
		<?php _e( 'No Pagespeed Reports Found. Google Pagespeed may still be checking your pages. If problems persist, see the following possible solutions:', 'gpagespeedi' ); ?>
	</p>
	<ol class="no-items">
		<?php if ( isset( $_GET['filter'] ) && $_GET['filter'] != 'all' ) : ?>
			<li><?php _e( 'There may not be any results for the "' . $_GET['filter'] . '" filter. Try another filter.', 'gpagespeedi' ); ?></li>
		<?php endif; ?>
		<?php if ( $this->gpi_options['strategy'] == 'both' ) : ?>
			<li><?php echo __( 'There may not be any', 'gpagespeedi' ) . ' ' . $this->gpi_ui_options['view_preference'] . ' ' . __( 'reports completed yet.', 'gpagespeedi' )  . ' ' . __( 'Try switching the report mode.', 'gpagespeedi' ); ?></li>
		<?php endif; ?>
		<li><?php _e( 'Make sure that you have entered your Google API key on the ', 'gpagespeedi' );?><a href="?page=<?php echo $_REQUEST['page']; ?>&render=options">Options</a> page.</li>
		<li><?php _e( 'Make sure that you have enabled "PageSpeed Insights API" from the Services page of the ', 'gpagespeedi' );?><a href="https://code.google.com/apis/console/">Google Console</a>.</li>
		<li><?php _e( 'Make sure that your URLs are publicly accessible', 'gpagespeedi' ); ?>.</li>
	</ol>
</div>