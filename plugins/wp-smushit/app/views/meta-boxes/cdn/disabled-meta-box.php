<?php
/**
 * CDN disabled meta box.
 *
 * @since 3.0
 * @package WP_Smush
 */

?>

<div class="sui-block-content-center">
	<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-default.png' ); ?>"
		srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-default@2x.png' ); ?> 2x"
		alt="<?php esc_html_e( 'Smush CDN', 'wp-smushit' ); ?>">

	<p>
		<?php
		esc_html_e(
			'Automatically compress and resize your images, then on WPMU DEV’s blazing-fast CDN with
		multi-pass lossy compression. This is the ultimate tool for boosting your pagespeed by taking the load off
		your server. All you need to do is activate the feature and we’ll serve your images from the CDN - no
		coding required.',
			'wp-smushit'
		);
		?>
	</p>

	<button class="sui-button sui-button-primary" id="smush-enable-cdn">
		<span class="sui-loading-text"><?php esc_html_e( 'GET STARTED', 'wp-smushit' ); ?></span>
		<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
	</button>

</div>
