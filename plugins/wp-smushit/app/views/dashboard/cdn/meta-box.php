<?php
/**
 * CDN meta box.
 *
 * @since 3.8.6
 * @package WP_Smush
 *
 * @var string $cdn_status         CDN status.
 * @var string $upsell_url         Upsell URL.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$cdn_next_gen_conversion_mode   = $this->settings->get_cdn_next_gen_conversion_mode();
$next_gen_conversion_mode_label = $this->settings->get_cdn_next_gen_conversion_label( $cdn_next_gen_conversion_mode );
?>

<?php
$cdn_activate_message = __( 'Your media is currently being served from the WPMU DEV CDN.', 'wp-smushit' );
$cdn_upgrade_message  = sprintf(
	__(
	/* translators: %1$s - starting a tag, %2$s - closing a tag */
		"You're almost through your CDN bandwidth limit. Please contact your administrator to upgrade your Smush CDN plan to ensure you don't lose this service. %1\$sUpgrade plan%2\$s",
		'wp-smushit'
	),
	'<a href="https://wpmudev.com/hub/account/" target="_blank">',
	'</a>'
);
$cdn_overcap_message = sprintf(
	__(
	/* translators: %1$s - starting a tag, %2$s - closing a tag */
		"You've gone through your CDN bandwidth limit, so we’ve stopped serving your images via the CDN. Contact your administrator to upgrade your Smush CDN plan to reactivate this service. %1\$sUpgrade plan%2\$s",
		'wp-smushit'
	),
	'<a href="https://wpmudev.com/hub/account/" target="_blank">',
	'</a>'
);
?>
<?php $this->view( 'cdn/header-description' ); ?>

<?php if ( ! WP_Smush::is_pro() ) : ?>
	<a href="<?php echo esc_url( $upsell_url ); ?>" target="_blank" class="sui-button sui-button-purple">
		<?php esc_html_e( 'Upgrade to Pro', 'wp-smushit' ); ?>
	</a>
<?php else : ?>
	<?php if ( 'disabled' === $cdn_status ) : ?>
		<?php $this->view( 'cdn/next-gen-activated-notice' ); ?>
		<button class="sui-button sui-button-blue" id="smush-enable-cdn">
			<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wp-smushit' ); ?></span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>
	<?php else : ?>
		<?php if ( 'overcap' === $cdn_status ) : ?>
			<div class="sui-notice sui-notice-error">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
						<p><?php echo wp_kses_post( $this->whitelabel->whitelabel_string( $cdn_overcap_message ) ); ?></p>
					</div>
				</div>
			</div>
		<?php elseif ( 'upgrade' === $cdn_status ) : ?>
			<div class="sui-notice sui-notice-warning">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
						<p><?php echo wp_kses_post( $this->whitelabel->whitelabel_string( $cdn_upgrade_message ) ); ?></p>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="sui-notice sui-notice-success">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<span class="sui-notice-icon sui-icon-check-tick sui-md" aria-hidden="true"></span>
						<p><?php echo esc_attr( $this->whitelabel->whitelabel_string( $cdn_activate_message ) ); ?></p>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="sui-box-settings-row sui-flushed sui-no-padding">
			<table class="sui-table sui-table-flushed">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Tools', 'wp-smushit' ); ?></th>
						<th colspan="2" width="50%"><?php esc_html_e( 'Status', 'wp-smushit' ); ?></th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<td class="sui-table-item-title">
							<?php esc_html_e( 'Next-Gen Conversion', 'wp-smushit' ); ?>
						</td>
						<td>
							<span class="sui-tag<?php echo $cdn_next_gen_conversion_mode ? ' sui-tag-green' : ''; ?>"><?php echo esc_html( $next_gen_conversion_mode_label ); ?></span>
						</td>
						<td>
							<a href="<?php echo esc_url( $this->get_url( 'smush-cdn#next-gen-conversion-setting' ) ); ?>" role="button" class="sui-button-icon">
								<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Configure', 'wp-smushit' ); ?></span>
							</a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<a href="<?php echo esc_url( $this->get_url( 'smush-cdn' ) ); ?>" class="sui-button sui-button-ghost">
			<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
			<?php esc_html_e( 'Configure', 'wp-smushit' ); ?>
		</a>
	<?php endif; ?>
<?php endif; ?>
