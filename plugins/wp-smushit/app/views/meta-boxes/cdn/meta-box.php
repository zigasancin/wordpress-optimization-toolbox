<?php
/**
 * CDN meta box.
 *
 * @since 3.0
 * @package WP_Smush
 *
 * @var stdClass $cdn            CDN data object.
 * @var array    $cdn_group      CDN settings keys.
 * @var array    $settings       Settings.
 * @var array    $settings_data  Settings data (titles, descriptions, fields).
 * @var string   $status         CDN status: warning (inactive), success (active) or error (expired).
 * @var array    $status_msg     Array of CDN status messages.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<form id="wp-smush-settings-form" method="post">
	<input type="hidden" name="setting_form" id="setting_form" value="cdn">
	<?php if ( is_multisite() && is_network_admin() ) : ?>
		<input type="hidden" name="wp-smush-networkwide" id="wp-smush-networkwide" value="1">
		<input type="hidden" name="setting-type" value="network">
	<?php endif; ?>

	<p>
		<?php
		esc_html_e( 'Take load off your server by serving your images from our blazing-fast CDN.', 'wp-smushit' );
		?>
	</p>

	<div class="sui-notice sui-notice-<?php echo esc_attr( $status ); ?> smush-notice-sm">
		<p><?php echo esc_html( $status_msg[ $status ] ); ?></p>
		<?php if ( 'info' === $status ) : ?>
			<p><?php echo esc_html( 'Note: Bulk and Directory smush features are treated separately and will continue to run independently.', 'wp-smushit' ); ?></p>
		<?php endif; ?>

		<?php if ( 'error' === $status ) : ?>
			<div class="sui-notice-buttons">
				<a href="https://premium.wpmudev.org/hub/account/" target="_blank" class="sui-button">
					<?php esc_html_e( 'Upgrade Plan', 'wp-smushit' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Quota & Bandwidth', 'wp-smushit' ); ?>
			</span>
			<span class="sui-description">
				<?php
				esc_html_e(
					'Monitor how much your websites are using the CDN. If you reach a cap it’s easy to
				upgrade to grab more bandwidth.',
					'wp-smushit'
				);
				?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="smush-cdn-quota-boxes">
				<div class="sui-border-frame">
					<span>
						<?php
						echo isset( $cdn->bandwidth ) ? esc_html( WP_Smush_Helper::format_bytes( $cdn->bandwidth, 2 ) ) : 0;
						?>
					</span>
					<span class="sui-description">
						<?php esc_html_e( 'Bandwidth', 'wp-smushit' ); ?>
					</span>
				</div>

				<div class="sui-border-frame sui-left">
					<span><?php echo isset( $cdn->hits ) ? absint( $cdn->hits ) : 0; ?></span>
					<span class="sui-description">
						<?php esc_html_e( 'Requests', 'wp-smushit' ); ?>
					</span>
				</div>
			</div>

			<span class="sui-description">
				<?php
				$limit = isset( $cdn->bandwidth_plan ) ? $cdn->bandwidth_plan : 10;
				printf(
					/* translators: %1$s: GB of bandwidth, %2$s: opening A (href) tag, %3$s; closing A (href) tag. */
					esc_html__(
						'Note: Your current plan includes %1$s GB bandwidth to use over 30 days.
					%2$sUpgrade Plan%3$s for more bandwidth. Stats are updated every 24 hours.',
						'wp-smushit'
					),
					absint( $limit ),
					'<a href="https://premium.wpmudev.org/hub/account/" target="_blank">',
					'</a>'
				);
				?>
			</span>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Supported Media Types', 'wp-smushit' ); ?>
			</span>
			<span class="sui-description">
				<?php
				esc_html_e( 'Here’s a list of the media types we will serve from the CDN.', 'wp-smushit' );
				?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<span class="smush-filename-extension smush-extension-jpg">
				<?php esc_html_e( 'jpg', 'wp-smushit' ); ?>
			</span>
			<span class="smush-filename-extension smush-extension-png">
				<?php esc_html_e( 'png', 'wp-smushit' ); ?>
			</span>
			<span class="smush-filename-extension smush-extension-gif">
				<?php esc_html_e( 'gif', 'wp-smushit' ); ?>
			</span>
			<?php if ( $settings['webp'] ) : ?>
				<span class="smush-filename-extension smush-extension-webp">
					<?php esc_html_e( 'webp', 'wp-smushit' ); ?>
				</span>
			<?php endif; ?>

			<span class="sui-description">
				<?php
				esc_html_e(
					'Note: At this time we don’t support video media types. We recommend uploading media to a
				third-party provider and embedding videos into your posts/pages.',
					'wp-smushit'
				);
				?>
			</span>
		</div>
	</div>

	<?php
	if ( ! is_multisite() || ( ! $settings['networkwide'] && ! is_network_admin() ) || is_network_admin() ) {
		foreach ( $settings_data as $name => $values ) {
			// If not CDN setting - skip.
			if ( ! in_array( $name, $cdn_group, true ) ) {
				continue;
			}

			$label = ! empty( $settings_data[ $name ]['short_label'] ) ? $settings_data[ $name ]['short_label'] : $settings_data[ $name ]['label'];

			// Show settings option.
			$this->settings_row( WP_SMUSH_PREFIX . $name, $label, $name, $settings[ $name ] );
		}
	}
	?>

	<?php if ( ! is_multisite() || ( ! $settings['networkwide'] && ! is_network_admin() ) || is_network_admin() ) : ?>
		<div class="sui-box-settings-row">
			<div class="sui-box-settings-col-1">
				<span class="sui-settings-label">
					<?php esc_html_e( 'Deactivate', 'wp-smushit' ); ?>
				</span>
				<span class="sui-description">
				<?php
				esc_html_e(
					'If you no longer require your images hosted from our CDN you can disable
					this feature.',
					'wp-smushit'
				);
				?>
			</span>
			</div>
			<div class="sui-box-settings-col-2">
				<button class="sui-button sui-button-ghost" id="smush-cancel-cdn">
					<i class="sui-icon-power-on-off" aria-hidden="true"></i>
					<?php esc_html_e( 'Deactivate', 'wp-smushit' ); ?>
				</button>
				<span class="sui-description">
				<?php
				esc_html_e(
					'Note: You won’t lose any imagery by deactivating, all of your attachments are still
					stored locally on your own server.',
					'wp-smushit'
				);
				?>
				</span>
			</div>
		</div>
	<?php endif; ?>
</form>
