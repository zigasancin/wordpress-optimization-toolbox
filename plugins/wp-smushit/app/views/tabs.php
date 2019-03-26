<?php
/**
 * Tabs layout
 *
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-sidenav smush-sidenav">
	<ul class="sui-vertical-tabs sui-sidenav-hide-md">
		<?php foreach ( $this->get_tabs() as $tab => $name ) : ?>
			<?php $tab_class = $is_hidden && 'bulk' !== $tab ? 'sui-hidden smush-' . $tab : 'smush-' . $tab; ?>
			<li class="sui-vertical-tab <?php echo $tab_class; ?> <?php echo ( $tab === $this->get_current_tab() ) ? 'current' : null; ?>">
				<a href="<?php echo esc_url( $this->get_tab_url( $tab ) ); ?>">
					<?php echo esc_html( $name ); ?>
				</a>
				<?php do_action( 'wp_smush_admin_after_tab_' . $this->get_slug(), $tab ); ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="sui-sidenav-hide-lg">
		<select class="sui-mobile-nav">
			<?php foreach ( $this->get_tabs() as $tab => $name ) : ?>
				<option value="<?php echo esc_url( $this->get_tab_url( $tab ) ); ?>" <?php selected( $this->get_current_tab(), $tab ); ?>><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
