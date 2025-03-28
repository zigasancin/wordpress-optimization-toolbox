<?php
/**
 * Show Next-Gen Formats activated modal.
 *
 * @var string $auto_start_bulk_smush_url URL to start bulk smush.
 */

use Smush\Core\Next_Gen\Next_Gen_Controller;
use Smush\Core\Next_Gen\Next_Gen_Manager;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$next_gen_manager           = Next_Gen_Manager::get_instance();
$old_next_gen_configuration = $next_gen_manager->get_previously_active_format_configuration();
$next_gen_configuration     = $next_gen_manager->get_active_format_configuration();
$old_image_retention_days   = Next_Gen_Controller::OLD_IMAGES_RETENTION_DAYS;

?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="smush-next-gen-conversion-changed-modal"
		class="sui-modal-content smush-next-gen-updated-dialog wp-smush-modal-dark-background"
		aria-modal="true"
		data-esc-close="false"
		aria-labelledby="smush-title-next-gen-conversion-changed-dialog"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-sides--20">
				<figure class="sui-box-banner" aria-hidden="true">
					<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/next-gen-formats-updated.png' ); ?>"
						srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/next-gen-formats-updated.png' ); ?> 1x, <?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/next-gen-formats-updated' ); ?>@2x.png 2x"
						alt="<?php esc_attr_e( 'Next-Gen Conversion', 'wp-smushit' ); ?>" class="sui-image sui-image-center">
				</figure>

				<button class="sui-button-icon sui-button-float--right sui-button-grey" style="box-shadow:none!important" data-modal-close="">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
				</button>
			</div>

			<div class="sui-box-body sui-content-center sui-spacing-sides--30 sui-spacing-top--30 sui-spacing-bottom--50">
				<h3 class="sui-box-title sui-lg" id="smush-title-next-gen-conversion-changed-dialog" style="white-space: normal">
					<?php esc_html_e( 'Next-Gen Conversion', 'wp-smushit' ); ?>
				</h3>

				<p class="sui-description">
					<?php
					printf(
						/* translators: 1. New Next-Gen Format 2. Old Next-Gen Format, 3. Old images retention days  */
						esc_html__( 'You’ve changed the Next-Gen Format. To serve existing images as %1$s, please run Bulk Smush. Your %2$s images will be safely stored for %3$d days in case you change your mind.', 'wp-smushit' ),
						esc_html( $next_gen_configuration->get_format_name() ),
						esc_html( $old_next_gen_configuration->get_format_name() ),
						intval( $old_image_retention_days ),
					);
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
