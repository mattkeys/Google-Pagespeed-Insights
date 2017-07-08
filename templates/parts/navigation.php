<?php
/**
 * Template Part - Navigation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h3 class="nav-tab-wrapper">
	<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=report-list" class="nav-tab <?php if ( $admin_page == '' || $admin_page == 'report-list' || $admin_page == 'ignore' || $admin_page == 'recheck' ) { echo 'nav-tab-active'; } ?>"><?php _e( 'Report List', 'gpagespeedi' ); ?></a>
	<?php if ( $admin_page == 'details' ) : ?>
		<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=details&amp;page_id=<?php echo $_GET['page_id']; ?>" class="nav-tab nav-tab-active nav-tab-temp"><?php _e( 'Report Details', 'gpagespeedi' ); ?></a>
	<?php endif; ?>
	<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=summary" class="nav-tab <?php if ( $admin_page == 'summary' ) { echo 'nav-tab-active'; } ?>"><?php _e( 'Report Summary', 'gpagespeedi' ); ?></a>

	<?php do_action( 'gpi_navigation', $admin_page ); ?>

	<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=ignored-urls" class="nav-tab <?php if ( $admin_page == 'ignored-urls' || $admin_page == 'activate' ) { echo 'nav-tab-active'; } ?>"><?php _e( 'Ignored URLs', 'gpagespeedi' ); ?></a>
	<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=options" class="nav-tab <?php if ( $admin_page == 'options' ) { echo 'nav-tab-active'; } ?>"><?php _e( 'Options', 'gpagespeedi' ); ?></a>
	<?php if ( $admin_page == 'logs' ) : ?>
		<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=logs" class="nav-tab nav-tab-active nav-tab-temp"><?php _e('Logs', 'gpagespeedi'); ?></a>
	<?php endif; ?>
	<?php if ( apply_filters( 'gpi_show_about', true ) ) : ?>
		<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=about" class="nav-tab <?php if ( $admin_page == 'about' ) { echo 'nav-tab-active'; } ?>"><?php _e('About', 'gpagespeedi'); ?></a>
	<?php endif; ?>

</h3>