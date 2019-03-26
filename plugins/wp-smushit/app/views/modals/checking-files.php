<?php
/**
 * Checking files dialog, shown after the onboarding series.
 *
 * @since 3.1.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-dialog sui-dialog-sm checking-files-dialog" aria-hidden="true" tabindex="-1" id="checking-files-dialog">
	<div class="sui-dialog-overlay sui-fade-in"></div>
	<div class="sui-dialog-content sui-bounce-in" aria-labelledby="dialogTitle" aria-describedby="dialogDescription" role="dialog">
		<div class="sui-box" role="document">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="dialogTitle">
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
					<?php esc_attr_e( 'Checking images', 'wp-smushit' ); ?>
				</h3>
			</div>

			<div class="sui-box-body">
				<p>
					<?php
					esc_html_e(
						'Great! We’re just running a check to see what images need compressing. You can configure
						more advanced settings once this image check is complete.',
						'wp-smushit'
					);
					?>
				</p>
			</div>

			<?php if ( ! $this->hide_wpmudev_branding() ) : ?>
			<img class="sui-image sui-image-center"
				src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding.png' ); ?>"
				srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding@2x.png' ); ?> 2x"
				alt="<?php esc_attr_e( 'WP Smush', 'wp-smushit' ); ?>">
			<?php endif; ?>
		</div>
	</div>
</div>
