<?php
/**
 * Template - Details
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h3 id="url" class="subTitle"></h3>
<div class="toolbar">
	<div class="left">
		<a id="view_url" class="button-gpi view" target="_blank"><?php _e( 'View Page', 'gpagespeedi' ); ?></a>
		<a id="recheck_url" class="button-gpi recheck"><?php _e( 'Recheck Results', 'gpagespeedi' ); ?></a>
	</div>
</div>

<div class="row">
	<div class="top-row boxsizing pagespeed_gauge_wrapper" id="pagespeed_gauge_wrapper">
		<div class="score_chart_div" id="score_chart_div">
			<img class="pagespeed_needle" id="pagespeed_needle" src="<?php echo GPI_PUBLIC_PATH; ?>assets/images/pagespeed_gauge_needle.png" width="204" height="204" alt="" />
			<div class="score_text" id="score_text"><span class="score"></span><span class="label"><?php _e( 'score', 'gpagespeedi' ); ?></span></div>
		</div>
	</div>
	<div class="top-row boxsizing framed pagespeed_stats_wrapper" id="pagespeed_stats_wrapper">
		<div class="boxheader">
			<span class="left"><?php _e('Page Statistics', 'gpagespeedi'); ?></span>
			<span class="right"><?php _e( 'Value', 'gpagespeedi' ); ?></span>
		</div>
		<table class="stats">
			<tr class="last_checked">
				<td class="leftcol"><?php _e( 'Last Checked', 'gpagespeedi' ); ?></td>
				<td class="rightcol"></td>
			</tr>
		</table>
	</div>
	<div class="top-row boxsizing framed pagespeed_sizes_wrapper" id="pagespeed_sizes_wrapper">
		<div class="boxheader">
			<span class="left"><?php _e( 'Total Size of Resources', 'gpagespeedi' ); ?></span>
			<span class="right light"><span class="legend"></span><?php _e( 'Size (kB)', 'gpagespeedi' ); ?></span>
		</div>
		<div id="sizes_chart_div"></div>
	</div>
</div>
<div class="row boxsizing framed">
	<div class="boxheader">
		<span class="left"><?php _e( 'Opportunities for improvement', 'gpagespeedi' ); ?></span><span class="light"><?php _e( '(Click for detailed report)', 'gpagespeedi' ); ?></span>
	</div>
	<div class="opportunities" id="opportunities">
		<div class="impact_chart_left boxsizing">
			<div class="impact_chart_div" id="impact_chart_div"></div>
			<div class="impact_rule_report images" id="optimize_images">
				<img class="shortpixel_robot" src="<?php echo GPI_PUBLIC_PATH; ?>/assets/images/shortpixel.png" alt="<?php _e( 'Short Pixel Image Optimization', 'gpagespeedi' ); ?>" />
				<h2><?php _e( 'Auto-Optimize Images', 'gpagespeedi' ); ?></h2>
				<p><?php _e( 'Unoptimized images are often one of the <strong>biggest</strong> negative factors in pagespeed scores. Google Pagespeed Insights for WordPress has partnered with ShortPixel to provide an easy and affordable solution to <em>automatically</em> optimize all images.', 'gpagespeedi' ); ?></p>
				<p><?php _e( 'Sign up using the button below and receive <strong>150 free image optimization credits</strong>.', 'gpagespeedi' ); ?></p>
				<a class="shortpixel_btn" href="https://shortpixel.com/h/af/PCFTWNN142247" target="_blank"><?php _e( 'Free Sign Up', 'gpagespeedi' ); ?></a>
			</div>
			<div class="impact_rule_report" id="impact_rule_report"></div>
		</div>
		<div id="impact_chart_legend" class="impact_chart_right">
			<table>
				<tbody>
					<th>
						<?php _e( 'Insights Key', 'gpagespeedi' ); ?>
					</th>
				</tbody>
			</table>
		</div>
	</div>
</div>