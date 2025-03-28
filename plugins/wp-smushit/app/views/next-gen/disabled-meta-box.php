<?php
/**
 * WebP disabled meta box.
 *
 * @since 3.8.0
 * @package WP_Smush
 */

use Smush\Core\Next_Gen\Next_Gen_Manager;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$next_gen_manager = Next_Gen_Manager::get_instance();
?>

<div class="sui-message sui-no-padding">
	<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
		<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-next-gen-default.png' ); ?>"
		srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-next-gen-default@2x.png' ); ?> 2x" alt="<?php esc_html_e( 'Next-Gen Formats', 'wp-smushit' ); ?>" class="sui-image" />
	<?php endif; ?>
	<div class="sui-message-content">
		<p>
			<?php esc_html_e( 'Fix the "Serve images in next-gen format" Google PageSpeed recommendation with a single click! Serve WebP and AVIF images directly from your server to supported browsers, while seamlessly switching to original images for those without WebP or AVIF support. All without relying on a CDN or any server configuration.', 'wp-smushit' ); ?>
		</p>
		<?php if ( $this->settings->has_cdn_page() && $this->settings->is_cdn_active() ) : ?>
			<?php $this->view( 'next-gen/cdn-activated-notice' ); ?>
		<button class="sui-button sui-button-blue" disabled="true" id="smush-toggle-<?php echo esc_attr( $next_gen_manager->get_active_format_key() ); ?>-button" data-action="enable">
		<?php else : ?>
		<button class="sui-button sui-button-blue" id="smush-toggle-<?php echo esc_attr( $next_gen_manager->get_active_format_configuration()->get_format_key() ); ?>-button" data-action="enable">
		<?php endif; ?>
			<span class="sui-loading-text"><?php esc_html_e( 'Get started', 'wp-smushit' ); ?></span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>
	</div>
</div>
