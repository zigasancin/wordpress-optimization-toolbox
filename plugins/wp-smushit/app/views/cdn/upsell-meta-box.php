<?php
/**
 * Upsell CDN meta box.
 *
 * @since 3.0
 * @package WP_Smush
 */

use Smush\Core\Helper;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-block-content-center sui-message sui-no-padding">
	<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-free-tier.png' ); ?>"
		srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-cdn-free-tier@2x.png' ); ?> 2x"
		alt="<?php esc_html_e( 'Smush CDN', 'wp-smushit' ); ?>">
	<div class="sui-message-content">
		<p>
			<?php esc_html_e( 'Multiply the speed and savings! Upload huge images and the Smush CDN will perfectly resize the files, safely convert to a Next-Gen format (WebP), and delivers them directly to your visitors from our blazing-fast multi-location globe servers.', 'wp-smushit' ); ?>
		</p>

		<ol class="sui-upsell-list">
			<li>
				<span class="sui-icon-check sui-sm" aria-hidden="true"></span>
				<?php esc_html_e( "Fix the 'Properly Size Images' audit on Google PageSpeed", 'wp-smushit' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-sm" aria-hidden="true"></span>
				<?php esc_html_e( 'Automatic next-gen WebP conversion via CDN', 'wp-smushit' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-sm" aria-hidden="true"></span>
				<?php esc_html_e( 'Up to 50 GB Smush CDN included', 'wp-smushit' ); ?>
			</li>
		</ol>

		<a href="<?php echo esc_url( Helper::get_url( 'smush_cdn_upgrade_button' ) ); ?>" class="sui-button sui-button-purple sui-margin-top" target="_blank">
			<?php esc_html_e( 'UNLOCK NOW WITH PRO', 'wp-smushit' ); ?>
		</a>
	</div>
</div>
