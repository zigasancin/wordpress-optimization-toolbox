<?php
/**
 * Show Next-Gen Formats activated modal.
 *
 * @var string $auto_start_bulk_smush_url URL to start bulk smush.
 */

use Smush\Core\Next_Gen\Next_Gen_Manager;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$next_gen_manager = Next_Gen_Manager::get_instance();
?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="smush-next-gen-activated-modal"
		class="sui-modal-content smush-next-gen-updated-dialog wp-smush-modal-dark-background"
		aria-modal="true"
		data-esc-close="false"
		aria-labelledby="smush-title-next-gen-activated-dialog"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-sides--20">
				<figure class="sui-box-banner" aria-hidden="true">
					<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/next-gen-formats-updated.png' ); ?>"
						srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/next-gen-formats-updated.png' ); ?> 1x, <?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/next-gen-formats-updated' ); ?>@2x.png 2x"
						alt="<?php esc_attr_e( 'Next-Gen Formats activated', 'wp-smushit' ); ?>" class="sui-image sui-image-center">
				</figure>

				<button class="sui-button-icon sui-button-float--right sui-button-grey" style="box-shadow:none!important" data-modal-close="">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
				</button>
			</div>

			<div class="sui-box-body sui-content-center sui-spacing-sides--30 sui-spacing-top--30 sui-spacing-bottom--50">
				<h3 class="sui-box-title sui-lg" id="smush-title-next-gen-activated-dialog" style="white-space: normal">
					<?php esc_html_e( 'Next-Gen Formats is now active!', 'wp-smushit' ); ?>
				</h3>

				<p class="sui-description">
					<?php
					/* translators: %s: Next-Gen Format */
					printf( esc_html__( 'To serve existing images as %s, please run Bulk Smush.', 'wp-smushit' ), esc_html( $next_gen_manager->get_active_format_name() ) );
					?>
				</p>
				<?php
				if ( $auto_start_bulk_smush_url ) {
					?>
						<a href="<?php echo esc_js( $auto_start_bulk_smush_url ); ?>" class="sui-button sui-button-blue">
						<?php esc_html_e( 'Bulk Smush Now', 'wp-smushit' ); ?>
						</a>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>
