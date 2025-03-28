<?php
/**
 * Next-Gen configured status meta box.
 *
 * @package WP_Smush
 */
use Smush\Core\Stats\Global_Stats;
use Smush\Core\Next_Gen\Next_Gen_Manager;
use Smush\Core\Modules\Bulk\Background_Bulk_Smush;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$required_bulk_smush    = Global_Stats::get()->is_outdated() || Global_Stats::get()->get_remaining_count() > 0;
$next_gen_manager       = Next_Gen_Manager::get_instance();
$bg_optimization        = Background_Bulk_Smush::get_instance();
$bulk_smush_in_progress = $bg_optimization->is_in_processing();

if ( is_multisite() ) {
	$notice_type    = 'success';
	$icon_name      = 'check-tick';
	$status_message = __( 'Next-Gen Formats is active.', 'wp-smushit' );
} elseif ( $required_bulk_smush ) {
	$notice_type    = 'warning';
	$icon_name      = 'warning-alert';
	$status_message = __( 'Next-Gen Formats is active.', 'wp-smushit' );

	if ( $bulk_smush_in_progress ) {
		$notice_type = 'info';
		$icon_name   = 'info';
	}
} else {
	$notice_type    = 'success';
	$icon_name      = 'check-tick';
	$status_message = __( 'Next-Gen Formats is active and working well.', 'wp-smushit' );
}
?>
<div class="sui-notice sui-notice-<?php echo esc_attr( $notice_type ); ?>">
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<i class="sui-notice-icon sui-icon-<?php echo esc_attr( $icon_name ); ?> sui-md" aria-hidden="true"></i>
			<p>
				<?php echo esc_html( $status_message ); ?>
			</p>
			<p>
				<?php
				if ( is_multisite() ) {
					/* translators: %s: Next-Gen Format */
					printf( esc_html__( 'Please run Bulk Smush on each subsite to serve existing images as %s.', 'wp-smushit' ), esc_html( $next_gen_manager->get_active_format_name() ) );
				} elseif ( $required_bulk_smush ) {
					if ( $bulk_smush_in_progress ) {
						/* translators: %s: Next-Gen Format */
						printf( esc_html__( 'Smush works its magic in the background, converting images to %s. This may take a moment, but the optimization is worth it.', 'wp-smushit' ), esc_html( $next_gen_manager->get_active_format_name() ) );
					} else {
						printf(
							/* translators: 1. opening 'a' tag, 2. closing 'a' tag, 3. Next-Gen Format */
							esc_html__( '%1$sBulk Smush%2$s now to serve existing images as %3$s.', 'wp-smushit' ),
							'<a href="' . esc_url( $this->get_url( 'smush-bulk&smush-action=start-bulk-next-gen-conversion#smush-box-bulk' ) ) . '">',
							'</a>',
							esc_html( $next_gen_manager->get_active_format_name() )
						);
					}
				} elseif ( ! $this->settings->is_automatic_compression_active() ) {
					printf(
						/* translators: 1. Next-Gen Format, 2. opening 'a' tag, 3. closing 'a' tag */
						esc_html__( 'If you wish to automatically convert all new uploads to %1$s, please enable the %2$sAutomatic Compression%3$s setting on the Bulk Smush page.', 'wp-smushit' ),
						esc_html( $next_gen_manager->get_active_format_name() ),
						'<a href="' . esc_url( $this->get_url( 'smush-bulk' ) ) . '#column-auto">',
						'</a>'
					);
				} else {
					/* translators: %s: Next-Gen Format */
					printf( esc_html__( 'Newly uploaded images will be automatically converted to %s.', 'wp-smushit' ), esc_html( $next_gen_manager->get_active_format_name() ) );
				}
				?>
			</p>
		</div>
	</div>
</div>