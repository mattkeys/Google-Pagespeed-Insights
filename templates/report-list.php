<?php
/**
 * Template - Report List
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GPI_DIRECTORY . '/classes/class-GPI-List-Table.php';

$GPI_List_Table = new GPI_List_Table();
$GPI_List_Table->prepare_items();
?>

<form id="reports-filter" action="" method="get">
	<input type="hidden" name="page" value="<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>" />
	<input type="hidden" name="render" value="report-list" />

	<?php $GPI_List_Table->display(); ?>
</form>