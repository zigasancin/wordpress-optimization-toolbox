<?php
/**
 * Abstract class for an integration module: class WP_Smush_Integration
 *
 * @since 2.9.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_Integration
 */
abstract class WP_Smush_Integration {

	/**
	 * Module slug.
	 *
	 * @var string $module
	 */
	protected $module;

	/**
	 * Module class - free module by default, can be pro.
	 *
	 * @var string $class  Accepts: 'free', 'pro'.
	 */
	protected $class = 'free';

	/**
	 * Module priority. The higher the priority, the higher the setting will be in the integration tab.
	 *
	 * @var int $priority
	 */
	protected $priority = 5;

	/**
	 * Module status.
	 *
	 * @var bool $enabled
	 */
	protected $enabled = false;

	/**
	 * Settings class instance for easier access.
	 *
	 * @since 3.0
	 *
	 * @var WP_Smush_Settings
	 */
	protected $settings;

	/**
	 * WP_Smush_Integration constructor.
	 */
	public function __construct() {
		$this->settings = WP_Smush_Settings::get_instance();

		// Filters the setting variable to add module settings in premium features.
		add_filter( 'wp_smush_integration_settings', array( $this, 'add_setting' ), $this->priority );

		// Filters the setting variable to add module setting title and description.
		add_filter( 'wp_smush_settings', array( $this, 'register' ) );

		// Disable setting.
		add_filter( 'wp_smush_integration_status_' . $this->module, array( $this, 'setting_status' ) );
	}

	/**
	 * Append module in feature list
	 *
	 * @param array $settings  Settings array.
	 *
	 * @return array
	 */
	public function add_setting( $settings ) {
		if ( ! isset( $settings[ $this->module ] ) ) {
			$settings[] = $this->module;
		}

		return $settings;
	}

	/**
	 * Update setting status - disable module functionality if not enabled.
	 *
	 * @since 2.8.1
	 *
	 * @return bool
	 */
	public function setting_status() {
		return ! $this->enabled;
	}

}
