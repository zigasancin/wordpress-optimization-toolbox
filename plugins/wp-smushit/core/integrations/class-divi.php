<?php
/**
 * Divi integration module.
 */

namespace Smush\Core\Integrations;

use Smush\Core\Controller;
use Smush\Core\Settings;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Divi
 */
class Divi extends Controller {
	/**
	 * Constructor to initialize the Divi integration.
	 */
	public function __construct() {
		$this->register_action( 'et_builder_modules_loaded', array( $this, 'handle_divi_image_sizes_hook' ) );
	}

	/**
	 * Handles the removal of Divi's image size calculation hook.
	 *
	 * Removes the `wp_calculate_image_sizes` filter added by Divi when responsive images are disabled
	 * and Smush's CDN with auto-resize is enabled.
	 *
	 * @return void
	 */
	public function handle_divi_image_sizes_hook() {

		if ( ! function_exists( 'et_get_option' ) ) {
			return;
		}

		$smush_settings = Settings::get_instance();
		if (
			'on' !== et_get_option( 'divi_enable_responsive_images' ) &&
			$smush_settings->get( 'cdn' ) &&
			$smush_settings->get( 'auto_resize' )
		) {
			remove_filter( 'wp_calculate_image_sizes', 'et_filter_wp_calculate_image_sizes' );
		}
	}
}
