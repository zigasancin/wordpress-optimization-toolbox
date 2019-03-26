<?php
/**
 * Settings meta box.
 *
 * @since 3.0
 * @package WP_Smush
 *
 * @var string $site_language     Site language.
 * @var string $translation_link  Link to plugin translation page.
 * @var array $settings           Settings values.
 * @var array $settings_data      Settings labels and descriptions.
 * @var array $settings_group     Settings group.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-box-settings-row" xmlns="http://www.w3.org/1999/html">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label "><?php esc_html_e( 'Translations', 'wp-smushit' ); ?></span>
		<span class="sui-description">
			<?php
			printf(
				/* translators: %1$s: opening a tag, %2$s: closing a tag */
				esc_html__( 'By default, Smush will use the language you’d set in your %1$sWordPress Admin Settings%2$s if a matching translation is available.', 'wp-smushit' ),
				'<a href="' . esc_html( admin_url( 'options-general.php' ) ) . '">',
				'</a>'
			);
			?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<label for="language-input" class="sui-label">
				<?php esc_html_e( 'Active Translation', 'wp-smushit' ); ?>
			</label>
			<input type="text" id="language-input" class="sui-form-control" disabled="disabled" placeholder="<?php echo esc_attr( $site_language ); ?>">
			<span class="sui-description">
				<?php
				printf(
					/* translators: %1$s: opening a tag, %2$s: closing a tag */
					esc_html__( 'Not using your language, or have improvements? Help us improve translations by providing your own improvements %1$shere%2$s.', 'wp-smushit' ),
					'<a href="' . esc_html( $translation_link ) . '" target="_blank">',
					'</a>'
				);
				?>
			</span>
		</div>
	</div>
</div>

<form id="wp-smush-settings-form" method="post">
	<input type="hidden" name="setting_form" id="setting_form" value="settings">
	<?php if ( is_multisite() && is_network_admin() ) : ?>
		<input type="hidden" name="wp-smush-networkwide" id="wp-smush-networkwide" value="1">
		<input type="hidden" name="setting-type" value="network">
	<?php endif; ?>

	<?php
	wp_nonce_field( 'save_wp_smush_options', 'wp_smush_options_nonce', '', true );
	if ( ! is_multisite() || ( ! $settings['networkwide'] && ! is_network_admin() ) || is_network_admin() ) {
		foreach ( $settings_data as $name => $values ) {
			if ( ! in_array( $name, $settings_group, true ) ) {
				continue;
			}

			$label = ! empty( $settings_data[ $name ]['short_label'] ) ? $settings_data[ $name ]['short_label'] : $settings_data[ $name ]['label'];

			// Show settings option.
			$this->settings_row( WP_SMUSH_PREFIX . $name, $label, $name, $settings[ $name ] );
		}
	}
	?>

	<div class="sui-box-settings-row smush-keep-data-form-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php echo esc_html( $settings_data['keep_data']['short_label'] ); ?></span>
			<span class="sui-description"><?php echo esc_html( $settings_data['keep_data']['desc'] ); ?></span>
		</div>

		<div class="sui-box-settings-col-2">
			<strong><?php echo esc_html( $settings_data['keep_data']['label'] ); ?></strong>
			<span class="sui-description">
				<?php esc_html_e( 'When you uninstall the plugin, what do you want to do with your settings? You can save them for next time, or wipe them back to factory settings.', 'wp-smushit' ); ?>
			</span>

			<div class="sui-side-tabs">
				<div class="sui-tabs-menu">
					<label for="keep_data-true" class="sui-tab-item <?php echo $settings['keep_data'] ? 'active' : ''; ?>">
						<input type="radio" name="<?php echo esc_attr( WP_SMUSH_PREFIX ); ?>keep_data" value="1" id="keep_data-true" <?php checked( $settings['keep_data'] ); ?>>
						<?php esc_html_e( 'Keep', 'wp-smushit' ); ?>
					</label>

					<label for="keep_data-false" class="sui-tab-item <?php echo $settings['keep_data'] ? '' : 'active'; ?>">
						<input type="radio" name="<?php echo esc_attr( WP_SMUSH_PREFIX ); ?>keep_data" value="0" id="keep_data-false" <?php checked( ! $settings['keep_data'] ); ?>>
						<?php esc_html_e( 'Delete', 'wp-smushit' ); ?>
					</label>
				</div>
			</div>

			<strong><?php esc_html_e( 'Reset Factory Settings', 'wp-smushit' ); ?></strong>
			<span class="sui-description">
				<?php esc_html_e( 'Need to revert back to the default settings? This button will instantly reset your settings to the defaults.', 'wp-smushit' ); ?>
			</span>

			<button type="button" class="sui-button sui-button-ghost" data-a11y-dialog-show="wp-smush-reset-settings-dialog">
				<i class="sui-icon-undo" aria-hidden="true"></i>
				<?php esc_html_e( 'Reset Settings', 'wp-smushit' ); ?>
			</button>
		</div>
	</div>

</form>

<?php $this->view( 'modals/reset-settings' ); ?>
