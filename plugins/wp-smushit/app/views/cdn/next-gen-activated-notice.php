<?php
/**
 * Next-Gen Formats activated notice.
 *
 * @since 3.18.0
 * @package WP_Smush
 */

use Smush\Core\Next_Gen\Next_Gen_Manager;

if ( ! defined( 'WPINC' ) ) {
	die;
}
$next_gen_manager = Next_Gen_Manager::get_instance();

if ( $next_gen_manager->is_active() && $this->settings->has_next_gen_page() ) : ?>
<div class="sui-notice sui-notice-warning" style="text-align: left" >
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
			<p>
				<?php
				esc_html_e( 'Enabling CDN will override the Next-Gen Formats settings as CDN can directly convert images to WebP and AVIF.', 'wp-smushit' );
				?>
			</p>
		</div>
	</div>
</div>
<?php endif; ?>
