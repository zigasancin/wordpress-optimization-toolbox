<?php
/**
 * Reset settings modal.
 *
 * @since 3.2.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="dialog sui-dialog sui-dialog-sm wp-smush-reset-settings-dialog" aria-hidden="true" id="wp-smush-reset-settings-dialog">
	<div class="sui-dialog-overlay" tabindex="-1" data-a11y-dialog-hide></div>
	<div class="sui-dialog-content" aria-labelledby="resetSettings" aria-describedby="dialogDescription" role="dialog">
		<div class="sui-box" role="document">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="resetSettings">
					<?php esc_html_e( 'Reset Settings', 'wp-smushit' ); ?>
				</h3>
			</div>

			<div class="sui-box-body">
				<p><?php esc_html_e( 'Are you sure you want to reset Smush’s settings back to the factory defaults?', 'wp-smushit' ); ?></p>

				<div class="sui-block-content-center">
					<a class="sui-button sui-button-ghost" data-a11y-dialog-hide>
						<?php esc_html_e( 'Cancel', 'wp-smushit' ); ?>
					</a>
					<a class="sui-button sui-button-ghost sui-button-red sui-button-icon-left" onclick="WP_Smush.helpers.resetSettings()">
						<i class="sui-icon-trash" aria-hidden="true"></i>
						<?php esc_html_e( 'Reset settings', 'wp-smushit' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
