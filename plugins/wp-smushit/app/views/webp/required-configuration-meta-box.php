<?php
/**
 * WebP status meta box when the server configurations is not configured.
 *
 * @package WP_Smush
 */
use Smush\Core\Webp\Webp_Configuration;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$webp_configuration = Webp_Configuration::get_instance();
$error_message      = $webp_configuration->server_configuration()->get_configuration_message();
$is_next_gen_page   = 'smush-next-gen' === $this->get_slug();
?>
<div class="sui-notice sui-notice-warning">
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<i class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></i>
			<p><?php echo esc_html( $error_message ); ?></p>

			<p>
				<?php
				$direct_conversion_link = sprintf(
					'<a href="javascript:void(0);" onclick="window?.WP_Smush?.WebP && window.WP_Smush.WebP.switchMethod(\'%s\');">%s</a>',
					esc_attr( Webp_Configuration::DIRECT_CONVERSION_METHOD ),
					esc_html__( 'Direct Conversion', 'wp-smushit' )
				);

				if ( apply_filters( 'wpmudev_branding_hide_doc_link', false ) ) {
					printf(
					/* translators: %s: Direct Conversion link */
						esc_html__( 'Please try the %s method if you don\'t have server access.', 'wp-smushit' ),
						$direct_conversion_link
					);
				} else {
					$support_link = '<a href="https://wpmudev.com/hub2/support/#get-support" target="_blank">' . esc_html__( 'contact support', 'wp-smushit' ) . '</a>';

					printf(
					/* translators: 1: Direct Conversion link, 2: Premium support link */
						esc_html__( 'Please try the %1$s method if you don\'t have server access or %2$s for further assistance.', 'wp-smushit' ),
						$direct_conversion_link,
						$support_link
					);
				}
				?>
			</p>

			<?php if ( $is_next_gen_page ) : ?>
			<div style="margin-top:15px">
				<button type="button" id="smush-webp-recheck" class="sui-button" data-is-configured="0">
					<span class="sui-loading-text"><i class="sui-icon-update"></i><?php esc_html_e( 'Re-check status', 'wp-smushit' ); ?></span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>
				<button id="smush-webp-toggle-wizard" type="button" class="sui-button sui-button-ghost" style="margin-left: 0;">
					<span class="sui-loading-text">
						<?php esc_html_e( 'Reconfigure', 'wp-smushit' ); ?>
					</span>

					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
