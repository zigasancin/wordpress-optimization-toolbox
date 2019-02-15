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

?>

<div class="sui-box-settings-row">
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
</form>
