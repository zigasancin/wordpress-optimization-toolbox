<?php
/**
 * Abstract module class: WP_Smush_Module
 *
 * @since 3.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_Module
 *
 * @since 3.0
 */
abstract class WP_Smush_Module {

	/**
	 * Settings instance.
	 *
	 * @since 3.0
	 * @var WP_Smush_Settings
	 */
	protected $settings;

	/**
	 * WP_Smush_Module constructor.
	 *
	 * @since 3.0
	 */
	public function __construct() {
		$this->settings = WP_Smush_Settings::get_instance();

		$this->init();
	}

	/**
	 * Initialize the module.
	 *
	 * Do not use __construct in modules, instead use init().
	 *
	 * @since 3.0
	 */
	protected function init() {}

}
