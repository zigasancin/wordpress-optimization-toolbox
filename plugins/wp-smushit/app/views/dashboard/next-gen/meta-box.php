<?php
/**
 * Local WebP meta box.
 *
 * @since 3.8.6
 * @package WP_Smush
 */

use Smush\Core\CDN\CDN_Helper;
use Smush\Core\Next_Gen\Next_Gen_Manager;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$next_gen_manager = Next_Gen_Manager::get_instance();
$cdn_helper       = CDN_Helper::get_instance();
$is_cdn_active    = $this->settings->is_cdn_active();
$upsell_url       = $this->get_utm_link( array( 'utm_campaign' => 'smush-dashboard-next-gen-upsell' ) );


if ( $next_gen_manager->is_active() && ! $is_cdn_active ) {
	/* translators: %s: Next-Gen format name */
	$next_gen_description = sprintf( __( 'Serve %1$s versions of your images to supported browsers, and gracefully fall back on JPEGs and PNGs for browsers that don\'t support %1$s.', 'wp-smushit' ), $next_gen_manager->get_active_format_name() );
} else {
	$next_gen_description = __( 'Serve WebP and AVIF images directly from your server to supported browsers, while seamlessly switching to original images for those without WebP or AVIF support. All without relying on a CDN or any server configuration.', 'wp-smushit' );
}
?>

<p>
	<?php echo esc_html( $next_gen_description ); ?>
</p>

<?php if ( ! WP_Smush::is_pro() ) : ?>
	<a href="<?php echo esc_url( $upsell_url ); ?>" target="_blank" class="sui-button sui-button-purple">
		<?php esc_html_e( 'Upgrade to Pro', 'wp-smushit' ); ?>
	</a>
<?php elseif ( $next_gen_manager->is_active() && ! $is_cdn_active ) : ?>
	<?php
	if ( $next_gen_manager->is_configured() ) {
		$this->view( 'next-gen/configured-meta-box' );
	} else {
		$this->view( 'webp/required-configuration-meta-box' );
	}
	?>
	<a href="<?php echo esc_url( $this->get_url( 'smush-next-gen' ) ); ?>" class="sui-button sui-button-ghost">
		<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
		<?php esc_html_e( 'Configure', 'wp-smushit' ); ?>
	</a>
<?php else : ?>
	<?php if ( $is_cdn_active && $this->settings->has_cdn_page() ) : ?>
		<?php $this->view( 'next-gen/cdn-activated-notice' ); ?>
		<button class="sui-button sui-button-blue disabled" id="smush-toggle-<?php echo esc_attr( $next_gen_manager->get_active_format_configuration()->get_format_key() ); ?>-button" data-action="enable">
	<?php else : ?>
		<button class="sui-button sui-button-blue" id="smush-toggle-<?php echo esc_attr( $next_gen_manager->get_active_format_configuration()->get_format_key() ); ?>-button" data-action="enable">
	<?php endif; ?>
		<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wp-smushit' ); ?></span>
		<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
	</button>
<?php endif; ?>
