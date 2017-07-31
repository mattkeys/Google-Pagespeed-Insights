<?php
/**
 * Template Part - Modes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="reportmodes">
	<?php if ( 'both' == $this->gpi_options['strategy'] || 'desktop' == $this->gpi_options['strategy'] ) : ?>
		<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;action=set_view_preference&amp;strategy=desktop" class="button-gpi desktop<?php if ( $this->gpi_ui_options['view_preference'] == 'desktop' ) { echo ' active'; } ?>"><?php _e( 'Desktop Mode', 'gpagespeedi' ); ?></a>
	<?php endif; ?>
	<?php if ( 'both' == $this->gpi_options['strategy'] || 'mobile' == $this->gpi_options['strategy'] ) : ?>
		<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;action=set_view_preference&amp;strategy=mobile" class="button-gpi mobile<?php if ( $this->gpi_ui_options['view_preference'] == 'mobile' ) { echo ' active'; } ?>"><?php _e( 'Mobile Mode', 'gpagespeedi' ); ?></a>
	<?php endif; ?>
</div>