<?php
/**
 * Admin View Notice.
 * Notice to display possible incompatibility issues.
 *
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( ! isset( $incompatibility_list ) ) {
	$incompatibility_list = array();
}

/**
 * For variable $incompatibility_list
 * @see Breeze_Incompatibility_Plugins::notification_for_incompatibility()
 */
?>
<div class="notice notice-error is-dismissible" id="breeze-plugins-notice">
	<br/>
	<span><strong><?php esc_html_e( 'Breeze Incompatible Plugins', 'breeze' ); ?></strong></span>
	<br/>
	<span class="text-error">
		<?php esc_html_e( 'The following plugin(s) is not compatible with Breeze and could conflict with plugin options.', 'breeze' ); ?>
		<?php esc_html_e( 'We recommend deactivating these plugin(s) and installing a compatible version.', 'breeze' ); ?>
	</span>
	<ul>
		<?php
		foreach ( $incompatibility_list as $plugin ) {
			$note_message = $plugin['safe_version_message'];
			?>

			<li><?php echo esc_html( $plugin['warning_message'] ); ?>
				<?php if ( $plugin['display_deactivate_button'] ) { ?>
					( <a class="" href="<?php echo esc_url( $plugin['deactivate_url'] ); ?>">
						<?php esc_html_e( 'Deactivate', 'breeze' ); ?>
					</a> )
					<?php
				} elseif ( ! empty( trim( $plugin['is_network_only'] ) ) ) {
					echo '[ ' . esc_html( $plugin['is_network_only'] ) . ' ]';
				}
				?>

				<?php
				if ( ! empty( $note_message ) ) {
					echo '. ' . esc_html( $note_message );
				}
				?>
			</li>

		<?php } ?>
	</ul>
</div>
