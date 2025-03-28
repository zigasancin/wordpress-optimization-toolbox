<?php
/**
 * WebP meta box header.
 *
 * @package WP_Smush
 *
 * @var boolean $is_disabled   Whether the WebP module is disabled.
 * @var boolean $is_configured Whether WebP images are being served.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<h3 class="sui-box-title">
	<?php esc_html_e( 'Next-Gen Formats', 'wp-smushit' ); ?>
</h3>

<?php if ( ! WP_Smush::is_pro() ) : ?>
	<div class="sui-actions-left">
		<span class="sui-tag sui-tag-pro sui-tooltip sui-tooltip-constrained" data-tooltip="<?php esc_attr_e( 'Join WPMU DEV to use this feature', 'wp-smushit' ); ?>">
			<?php esc_html_e( 'Pro', 'wp-smushit' ); ?>
		</span>
	</div>
<?php endif; ?>

<?php
$tooltip_message = __( 'Next-Gen Formats are WebP - which offers better compatibility, and AVIF for better compression. Unsupported browsers use the original image format.', 'wp-smushit' );
?>
<div class="sui-actions-right sui-sm">
	<span class="sui-description" style="margin: 0 5px 0 5px;">
			<?php esc_html_e( 'How Next-Gen Formats work?', 'wp-smushit' ); ?>
	</span>
	<span class="sui-tooltip sui-tooltip-constrained sui-tooltip-top-right" style="--tooltip-text-align: left;"
		data-tooltip="<?php echo esc_html( $tooltip_message ); ?>"
		aria-hidden="true"
		>
		
		<i class="sui-notice-icon sui-icon-info sui-sm"
			data-tooltip="<?php echo esc_html( $tooltip_message ); ?>"
			aria-hidden="true"
		></i>
	</span>
</div>
