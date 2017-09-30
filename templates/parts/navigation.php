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

	<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=snapshots" class="nav-tab <?php if ( $admin_page == 'snapshots' ) { echo 'nav-tab-active'; } ?>"><?php _e( 'Snapshots', 'gpagespeedi' ); ?></a>
	<?php if ( $admin_page == 'view-snapshot' && ! isset( $_GET['compare_id'] ) ) : ?>
		<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=view-snapshot&amp;snapshot_id=<?php echo $_GET['snapshot_id']; ?>" class="nav-tab nav-tab-active nav-tab-temp"><?php _e( 'View Snapshot', 'gpagespeedi' ); ?></a>
	<?php endif; ?>
	<?php if ( $admin_page == 'view-snapshot' && isset( $_GET['compare_id'] ) ) : ?>
		<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=view-snapshot&amp;snapshot_id=<?php echo $_GET['snapshot_id']; ?>&amp;compare_id=<?php echo $_GET['compare_id']; ?>" class="nav-tab nav-tab-active nav-tab-temp"><?php _e('Compare Snapshots', 'gpagespeedi'); ?></a>
	<?php endif; ?>
	<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=custom-urls" class="nav-tab <?php if ( $admin_page == 'custom-urls' || $admin_page == 'delete' ) { echo 'nav-tab-active'; } ?>"><?php _e( 'Custom URLs', 'gpagespeedi' ); ?></a>
	<?php if($admin_page == 'add-custom-urls') : ?>
		<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=add-custom-urls" class="nav-tab nav-tab-active nav-tab-temp"><?php _e( 'Add New URLs', 'gpagespeedi' ); ?></a>
	<?php endif ?>
	<?php if ( $admin_page == 'add-custom-urls-bulk' ) : ?>
		<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=add-custom-urls-bulk" class="nav-tab nav-tab-active nav-tab-temp"><?php _e( 'Bulk Upload New URLs', 'gpagespeedi' ); ?></a>
	<?php endif; ?>

	<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=ignored-urls" class="nav-tab <?php if ( $admin_page == 'ignored-urls' || $admin_page == 'activate' ) { echo 'nav-tab-active'; } ?>"><?php _e( 'Ignored URLs', 'gpagespeedi' ); ?></a>
	<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=options" class="nav-tab <?php if ( $admin_page == 'options' ) { echo 'nav-tab-active'; } ?>"><?php _e( 'Options', 'gpagespeedi' ); ?></a>
	<?php if ( $admin_page == 'logs' ) : ?>
		<a href="?page=<?php echo $_REQUEST['page'];?>&amp;render=logs" class="nav-tab nav-tab-active nav-tab-temp"><?php _e('Logs', 'gpagespeedi'); ?></a>
	<?php endif; ?>

	<?php do_action( 'gpi_navigation', $admin_page ); ?>

</h3>