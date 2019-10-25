<?php
/**
 * CDN disabled meta box.
 *
 * @since 3.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-block-content-center">
	<?php if ( ! $this->hide_wpmudev_branding() ) : ?>
		<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-default.png' ); ?>"
			srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-default@2x.png' ); ?> 2x"
			alt="<?php esc_html_e( 'Smush CDN', 'wp-smushit' ); ?>">
	<?php endif; ?>

	<p>
		<?php
		esc_html_e(
			"Automatically compress and resize your images with bulk Smush, or upload them to the WPMU DEV's
			blazing-fast CDN with multi-pass lossy compression and auto resize features. All you need to do is activate
			the feature and we’ll serve your images from the CDN - no coding required.",
			'wp-smushit'
		);
		?>
	</p>

	<button class="sui-button sui-button-blue" id="smush-enable-cdn">
		<span class="sui-loading-text"><?php esc_html_e( 'GET STARTED', 'wp-smushit' ); ?></span>
		<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
	</button>

</div>
