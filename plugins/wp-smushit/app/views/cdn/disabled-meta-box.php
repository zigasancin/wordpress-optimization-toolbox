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

<div class="sui-block-content-center sui-message sui-no-padding">

	<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-default.png' ); ?>"
		srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-default@2x.png' ); ?> 2x"
		alt="<?php esc_html_e( 'Graphic CDN', 'wp-smushit' ); ?>">

	<div class="sui-message-content">
		<?php $this->view( 'cdn/header-description' ); ?>
		<?php $this->view( 'cdn/next-gen-activated-notice' ); ?>

		<button class="sui-button sui-button-blue" id="smush-enable-cdn">
			<span class="sui-loading-text"><?php esc_html_e( 'GET STARTED', 'wp-smushit' ); ?></span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>
	</div>
</div>
