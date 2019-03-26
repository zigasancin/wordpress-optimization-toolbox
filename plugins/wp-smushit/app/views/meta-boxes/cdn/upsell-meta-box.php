<?php
/**
 * Upsell CDN meta box.
 *
 * @since 3.0
 * @package WP_Smush
 *
 * @var string $upgrade_url  Upgrade URL.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-block-content-center">
	<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-default.png' ); ?>"
		srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-default@2x.png' ); ?> 2x"
		alt="<?php esc_html_e( 'Smush CDN', 'wp-smushit' ); ?>">

	<p>
		<?php
		esc_html_e(
			"Automatically compress and resize your images with bulk Smush, or upload them to the WPMU DEV's
			blazing-fast CDN with multi-pass lossy compression and auto resize features.. This is the ultimate tool for
			boosting your pagespeed by taking the load off your server. Try it today with a WPMU DEV Membership!",
			'wp-smushit'
		);
		?>
	</p>

	<a href="<?php echo esc_url( $upgrade_url ); ?>" class="sui-button sui-button-green" target="_blank">
		<?php esc_html_e( 'UPGRADE', 'wp-smushit' ); ?>
	</a>
</div>
