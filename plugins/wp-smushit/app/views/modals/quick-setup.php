<?php
/**
 * Prints the content of welcome screen for the new installation.
 *
 * For new installation show a quick settings form on a welcome
 * modal box to get started.
 *
 * @package WP_Smush
 */

?>

<div class="sui-dialog" aria-hidden="true" tabindex="-1" id="smush-quick-setup-dialog">
	<div class="sui-dialog-overlay sui-fade-in" data-a11y-dialog-hide=""></div>
	<div class="sui-dialog-content sui-bounce-in" aria-labelledby="smush-quick-setup-modal-title"  role="dialog">
		<div class="sui-box" role="document">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="smush-quick-setup-modal-title">
					<?php esc_html_e( 'QUICK SETUP', 'wp-smushit' ); ?>
				</h3>
				<div class="sui-actions-right">
					<button data-a11y-dialog-hide class="sui-button sui-button-ghost smush-skip-setup" aria-label="<?php esc_html_e( 'Skip this.', 'wp-smushit' ); ?>">
						<?php esc_html_e( 'SKIP', 'wp-smushit' ); ?>
					</button>
				</div>
			</div>
			<div class="sui-box-body smush-quick-setup-settings">
				<p><?php esc_html_e( 'Welcome to Smush - Winner of Torque Plugin Madness 2017 & 2018! Let\'s quickly set up the basics for you, then you can fine tune each setting as you go - our recommendations are on by default.', 'wp-smushit' ); ?></p>
				<form method="post" id="smush-quick-setup-form">
					<input type="hidden" value="smush_setup" name="action" />
					<?php wp_nonce_field( 'smush_quick_setup' ); ?>
					<?php
					// Available settings for free/pro version.
					$available = array(
						'auto',
						'lossy',
						'strip_exif',
						'resize',
						'original',
					);
					// Settings for free and pro version.
					foreach ( WP_Smush_Settings::get_instance()->get() as $name => $value ) {
						// Skip networkwide settings, we already printed it.
						if ( 'networkwide' === $name ) {
							continue;
						}

						// Only include settings listed in available array list.
						if ( ! in_array( $name, $available, true ) ) {
							continue;
						}

						// Skip premium features if not a member.
						if ( ! in_array( $name, WP_Smush_Core::$basic_features, true ) && ! WP_Smush::is_pro() ) {
							continue;
						}

						$setting_m_key = WP_SMUSH_PREFIX . $name;
						?>
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label">
									<?php echo esc_html( WP_Smush::get_instance()->core()->settings[ $name ]['label'] ); ?>
								</span>
								<span class="sui-description">
									<?php echo esc_html( WP_Smush::get_instance()->core()->settings[ $name ]['desc'] ); ?>
								</span>
							</div>
							<div class="sui-box-settings-col-2">
								<label class="sui-toggle">
									<input type="checkbox" class="toggle-checkbox" id="<?php echo esc_attr( $setting_m_key ) . '-quick-setup'; ?>" name="smush_settings[]" <?php checked( $value ); ?> value="<?php echo esc_attr( $setting_m_key ); ?>" tabindex="0">
									<span class="sui-toggle-slider"></span>
								</label>
							</div>
							<?php if ( 'resize' === $name ) { // Add resize width and height setting. ?>
								<div class="wp-smush-resize-settings-col">
									<?php $this->resize_settings( $name, 'quick-setup-' ); ?>
								</div>
							<?php } ?>
						</div>
						<?php
					}
					?>
				</form>
			</div>
			<div class="sui-box-footer">
				<div class="sui-actions-right">
					<button type="submit" class="sui-button sui-button-lg sui-button-blue" id="smush-quick-setup-submit">
						<?php esc_html_e( 'Get Started', 'wp-smushit' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
