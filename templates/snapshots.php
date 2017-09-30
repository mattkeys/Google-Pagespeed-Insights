<?php
/**
 * Template - Snapshots
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GPI_DIRECTORY . '/classes/class-GPI-List-Table.php';

$GPI_List_Table = new GPI_List_Table( 'snapshots' );
$GPI_List_Table->prepare_items();
?>

<form id="reports-filter" action="" method="get">
	<input type="hidden" name="page" value="<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>" />
	<input type="hidden" name="render" value="snapshots" />

	<?php $GPI_List_Table->display(); ?>
</form>