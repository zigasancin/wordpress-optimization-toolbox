<?php
/**
 * Show Updated Features modal.
 *
 * @package WP_Smush
 *
 * @since 3.7.0
 *
 * @var string $cta_url URL for the modal's CTA button.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="smush-updated-dialog"
		class="sui-modal-content smush-updated-dialog wp-smush-modal-dark-background"
		aria-modal="true"
		data-esc-close="false"
		aria-labelledby="smush-title-updated-dialog"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-sides--20">
				<figure class="sui-box-banner" aria-hidden="true">
					<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/updated/updated.png' ); ?>"
						srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/updated/updated.png' ); ?> 1x, <?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/updated/updated' ); ?>@2x.png 2x"
						alt="<?php esc_attr_e( 'Smush Updated Modal', 'wp-smushit' ); ?>" class="sui-image sui-image-center">
				</figure>

				<button class="sui-button-icon sui-button-float--right sui-button-grey" style="box-shadow:none!important" onclick="WP_Smush.onboarding.hideUpgradeModal(event, this)">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
				</button>
			</div>

			<div class="sui-box-body sui-content-center sui-spacing-sides--30 sui-spacing-top--40 sui-spacing-bottom--50">
				<h3 class="sui-box-title sui-lg" id="smush-title-updated-dialog" style="white-space: normal">
					<?php esc_html_e( 'New: AVIF File Compression!', 'wp-smushit' ); ?>
				</h3>

				<p class="sui-description">
					<?php esc_html_e( 'Say hello to AVIF, the next-gen image format that outperforms WebP and JPEG in compression and quality. Enjoy faster page loads, smaller file sizes, and sharper visuals—helping improve site performance and giving your visitors a smoother browsing experience!', 'wp-smushit' ); ?>
				</p>
				<?php
				if ( $cta_url ) {
					$is_pro    = WP_Smush::is_pro();
					$cta_label = $is_pro ? __( 'Go to Next-Gen Formats', 'wp-smushit' ) : __( 'See Plans', 'wp-smushit' );
					$target    = $is_pro ? '_self' : '_blank';

					$class_names = array(
						'sui-button',
						'wp-smush-upgrade-modal-cta',
					);
					if ( $is_pro ) {
						$class_names[] = 'sui-button-grey';
					} else {
						$class_names[] = 'sui-button-blue';
					}
					?>
						<a href="<?php echo esc_js( $cta_url ); ?>"
							target="<?php echo esc_attr( $target ); ?>"
							class="<?php echo esc_attr( join( ' ', $class_names ) ); ?>"
							onclick="WP_Smush.onboarding.hideUpgradeModal(event, this)">
						<?php echo esc_html( $cta_label ); ?>
						<?php if ( ! $is_pro ) : ?>
							<span class="sui-icon-open-new-window" style="margin-left: 3px; width:auto;" aria-hidden="true"></span>
						<?php endif; ?>
						</a>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>
