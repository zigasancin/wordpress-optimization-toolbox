<?php
/**
 * Tools meta box.
 *
 * @since 3.2.1
 * @package WP_Smush
 *
 * @var array $settings_data
 * @var array $grouped_settings
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<form id="wp-smush-settings-form" method="post">
	<input type="hidden" name="setting_form" id="setting_form" value="tools">
	<?php if ( is_multisite() && is_network_admin() ) : ?>
		<input type="hidden" name="setting-type" value="network">
	<?php endif; ?>

	<?php
	foreach ( $settings_data as $name => $values ) {
		// If not CDN setting - skip.
		if ( ! in_array( $name, $grouped_settings, true ) ) {
			continue;
		}

		$label = ! empty( $settings_data[ $name ]['short_label'] ) ? $settings_data[ $name ]['short_label'] : $settings_data[ $name ]['label'];

		// Show settings option.
		$this->settings_row( WP_SMUSH_PREFIX . $name, $label, $name, $settings[ $name ] );
	}
	?>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php echo esc_html( $settings_data['bulk_restore']['short_label'] ); ?></span>
			<span class="sui-description"><?php echo esc_html( $settings_data['bulk_restore']['desc'] ); ?></span>
		</div>

		<div class="sui-box-settings-col-2">
			<button type="button" class="sui-button sui-button-ghost" onclick="WP_Smush.restore.init()">
				<i class="sui-icon-undo" aria-hidden="true"></i>
				<?php esc_html_e( 'Restore Thumbnails', 'wp-smushit' ); ?>
			</button>
			<span class="sui-description">
				<?php esc_html_e( 'Note: This feature uses your original image uploads to regenerate thumbnails. If you have “Smush my original images” enabled, we can still restore your thumbnails, but the quality will reflect your compressed original image.', 'wp-smushit' ); ?>
			</span>
		</div>
	</div>
</form>

<?php $this->view( 'modals/restore-images' ); ?>
