<?php
/**
 * WebP meta box.
 *
 * @since 3.8.0
 * @package WP_Smush
 *
 * @var Smush\App\Abstract_Page $this  Page.
 */
use Smush\Core\Next_Gen\Next_Gen_Manager;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$is_configured = Next_Gen_Manager::get_instance()->is_configured();
$header_desc   = __( 'Serve Next-Gen images directly from your server to supported browsers with one click, while seamlessly switching to original images for older browsers, all without relying on a CDN.', 'wp-smushit' );
?>

<p>
	<?php echo esc_html( $header_desc ); ?>
</p>

<?php
if ( $is_configured ) {
	$this->view( 'next-gen/configured-meta-box' );
} else {
	$this->view( 'webp/required-configuration-meta-box' );
}

do_action( 'wp_smush_next_gen_formats_settings' );
