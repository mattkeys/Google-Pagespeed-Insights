<?php
/**
 * Template - API Error Logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p><?php _e( 'Error logs are kept for up to 7 days before being erased automatically.', 'gpagespeedi' ); ?></p>

<?php
	$error_logs	= apply_filters( 'gpi_error_logs', array() );
?>

<table class="widefat error_logs">
	<thead>
		<tr>
			<td style="width: 200px;"><?php _e( 'URL', 'gpagespeedi' ); ?></td>
			<td><?php _e( 'Strategy', 'gpagespeedi' ); ?></td>
			<td><?php _e( 'Report Update?', 'gpagespeedi' ); ?></td>
			<td><?php _e( 'Type', 'gpagespeedi' ); ?></td>
			<td style="width: 100px;"><?php _e( 'Timestamp', 'gpagespeedi' ); ?></td>
			<td><?php _e( 'Error(s)', 'gpagespeedi' ); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php
			if ( ! empty( $error_logs ) ) :
				foreach( $error_logs as $error_log ) :
					$update		= $error_log['is_update'] ? 'yes' : 'no';
					$errors		= maybe_unserialize( $error_log['error'] );
					?>
					<tr>
						<td><a href="<?php echo $error_log['URL']; ?>"><?php echo $error_log['URL']; ?></a></td>
						<td><?php echo $error_log['strategy']; ?></td>
						<td><?php echo $update; ?></td>
						<td><?php echo $error_log['type']; ?></td>
						<td><?php echo date_i18n( 'M d g:ia', $error_log['timestamp'] ); ?></td>
						<td>
							<?php
								if ( isset( $errors[0] ) && is_array( $errors ) ) :
									foreach ( $errors[0] as $key => $error ) :
										?>
										<p>
											<strong><?php echo $key; ?>:</strong>
											<?php echo $error; ?>
										</p>
										<?php
									endforeach;
								else :
									echo $errors;
								endif;
							?>
						</td>
					</tr>
					<?php
				endforeach;
			else :
				?>
				<td><?php _e( 'No error logs found.', 'gpagespeedi' ); ?></td>
				<?php
			endif;
		?>
	</tbody>
</table>