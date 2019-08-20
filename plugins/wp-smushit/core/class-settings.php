<?php
/**
 * Settings class.
 *
 * @since 3.2.2  Refactored to support custom sub site configurations.
 * @package WP_Smush\Core
 */

namespace WP_Smush\Core;

/**
 * Class Settings
 *
 * @package WP_Smush\Core
 */
class Settings {

	/**
	 * Plugin instance.
	 *
	 * @since 3.0
	 *
	 * @var null|Settings
	 */
	private static $instance = null;

	/**
	 * Settings array.
	 *
	 * @var array $settings
	 */
	private $settings = array();

	/**
	 * Available modules.
	 *
	 * @var array $modules
	 */
	private $modules = array( 'bulk', 'integrations', 'lazy_load', 'cdn', 'tools', 'settings' );

	/**
	 * Bulk Smush options.
	 *
	 * @var array $bulk_fields
	 */
	private $bulk_fields = array(
		'auto'       => true,  // works with CDN.
		'lossy'      => false, // works with CDN.
		'strip_exif' => true,  // works with CDN.
		'resize'     => false,
		'original'   => false,
		'backup'     => false,
		'png_to_jpg' => false, // works with CDN.
	);

	/**
	 * Integration options.
	 *
	 * @var array $integrations_fields
	 */
	private $integrations_fields = array(
		'nextgen'    => false,
		's3'         => false,
		'gutenberg'  => false,
		'js_builder' => false,
	);

	/**
	 * Lazy load options.
	 *
	 * @var array $lazy_load_fields
	 */
	private $lazy_load_fields = array(
		'enabled'         => false,
		'format'          => array(
			'jpeg' => true,
			'png'  => true,
			'gif'  => true,
			'svg'  => true,
		),
		'output'          => array(
			'content'    => true,
			'widgets'    => true,
			'thumbnails' => true,
			'gravatars'  => true,
		),
		'animation'       => array(
			'selected'    => 'fadein', // Accepts: fadein, spinner, placeholder, false.
			'fadein'      => array(
				'duration' => 400,
				'delay'    => 0,
			),
			'spinner'     => array(
				'selected' => 1,
				'custom'   => array(),
			),
			'placeholder' => array(
				'selected' => 1,
				'custom'   => array(),
				'color'    => '#F3F3F3',
			),
		),
		'include'         => array(
			'frontpage' => true,
			'home'      => true,
			'page'      => true,
			'single'    => true,
			'archive'   => true,
			'category'  => true,
			'tag'       => true,
		),
		'exclude-pages'   => array(),
		'exclude-classes' => array(),
		'footer'          => true,
	);

	/**
	 * CDN options.
	 *
	 * @var array $cdn_fields
	 */
	private $cdn_fields = array(
		'enabled'           => false,
		'auto_resize'       => false,
		'webp'              => true,
		'background_images' => true,
	);

	/**
	 * Tools options.
	 *
	 * @var array $tools_fields
	 */
	private $tools_fields = array(
		'detection' => false,
	);

	/**
	 * Settings module options.
	 *
	 * @var array $settings_fields
	 */
	private $settings_fields = array(
		'usage'             => true,
		'accessible_colors' => false,
		'keep_data'         => true,
	);

	/**
	 * Global options.
	 *
	 * These options are stored/fetched via get_site_option().
	 *
	 * @var array $global_fields
	 */
	private $global_fields = array(
		'networkwide'  => false, // Accepts: true, false, array of available modules.
		'install-type' => 'new', // Accepts: new, existing.
	);

	/**
	 * Return the plugin instance.
	 *
	 * @since 3.0
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Settings constructor.
	 */
	private function __construct() {
		// See if we've got serialised settings stored already.
		$settings = $this->get_settings();

		if ( ! $settings ) {
			$this->set_settings( WP_SMUSH_PREFIX . 'settings', $this->get_defaults() );
		}

		// Store it in class variable.
		if ( ! empty( $settings ) && is_array( $settings ) ) {
			$this->settings = $settings;
		}

		// Save Settings.
		add_action( 'wp_ajax_save_settings', array( $this, 'save' ) );
		// Reset Settings.
		add_action( 'wp_ajax_reset_settings', array( $this, 'reset' ) );
	}

	/**
	 * Getter method for bulk settings option keys.
	 *
	 * @since 3.2.2
	 * @return array
	 */
	public function get_bulk_fields() {
		return array_keys( $this->bulk_fields );
	}

	/**
	 * Getter method for integration option keys.
	 *
	 * @since 3.2.2
	 * @return array
	 */
	public function get_integrations_fields() {
		return array_keys( $this->integrations_fields );
	}

	/**
	 * Getter method for lazy load option keys.
	 *
	 * @since 3.2.2
	 * @return array
	 */
	public function get_lazy_load_fields() {
		return array_keys( $this->lazy_load_fields );
	}

	/**
	 * Getter method for CDN option keys.
	 *
	 * @since 3.2.2
	 * @return array
	 */
	public function get_cdn_fields() {
		return array_keys( $this->cdn_fields );
	}

	/**
	 * Getter method for tools option keys.
	 *
	 * @since 3.2.2
	 * @return array
	 */
	public function get_tools_fields() {
		return array_keys( $this->tools_fields );
	}

	/**
	 * Getter method for settings option keys.
	 *
	 * @since 3.2.2
	 * @return array
	 */
	public function get_settings_fields() {
		return array_keys( $this->settings_fields );
	}

	/**
	 * Getter method for global option keys.
	 *
	 * @since 3.2.2
	 * @return array
	 */
	public function get_global_fields() {
		return array_keys( $this->global_fields );
	}

	/**
	 * Generate an array with default settings.
	 *
	 * @since 3.2.2
	 *
	 * @return array
	 */
	private function get_defaults() {
		$settings = array();

		foreach ( $this->modules as $module ) {
			$settings[ $module ] = $this->{$module . '_fields'};
		}

		return $settings;
	}

	/**
	 * Check if access is global.
	 *
	 * Returns TRUE on single sites and if global network settings are used.
	 * FALSE when admins can overwrite all settings.
	 * ARRAY when custom access is defined. Array contains available modules.
	 *
	 * @since 3.2.2
	 *
	 * @return bool|array
	 */
	private function is_global() {
		if ( ! is_multisite() ) {
			return false;
		}

		// Get directly from db.
		$access = get_site_option( WP_SMUSH_PREFIX . 'networkwide' );
		if ( isset( $access ) && false === (bool) $access ) {
			return true;
		}

		if ( '1' === $access ) {
			return false;
		}

		// Partial enabled.
		return $access;
	}

	/**
	 * Basically what we are doing here is making sure that all settings are always defined.
	 * If something is missing, we either take it from network settings or defaults.
	 *
	 * @since 3.2.2
	 *
	 * @return array|bool  Return false on failure.
	 */
	private function get_settings() {
		$global   = $this->is_global();
		$defaults = $this->get_defaults();

		// Always get global settings if global settings enabled or is in network admin.
		if ( true === $global || ( is_array( $global ) && is_network_admin() ) ) {
			return get_site_option( WP_SMUSH_PREFIX . 'settings', $defaults );
		}

		if ( false === $global ) {
			$site_settings = get_option( WP_SMUSH_PREFIX . 'settings', $defaults );

			if ( ! is_multisite() ) {
				return $site_settings;
			}

			// Make sure we're not missing any settings.
			$global_settings = get_site_option( WP_SMUSH_PREFIX . 'settings', $defaults );
			$undefined       = array_diff_assoc( $global_settings, $site_settings );

			return array_merge( $site_settings, $undefined );
		}

		// Custom access enabled - combine settings from network with site settings.
		if ( is_array( $global ) ) {
			$network_settings = array_diff( $this->modules, $global );
			$global_settings  = get_site_option( WP_SMUSH_PREFIX . 'settings', $defaults );
			$site_settings    = get_option( WP_SMUSH_PREFIX . 'settings', $defaults );

			foreach ( $network_settings as $key ) {
				// If set on network - take the network settings.
				if ( isset( $global_settings[ $key ] ) ) {
					$site_settings[ $key ] = $global_settings[ $key ];
				} else {
					// Else take the defaults.
					$site_settings[ $key ] = $defaults[ $key ];
				}
			}

			return $site_settings;
		}

		return false;
	}

	/**
	 * Update value for given setting key.
	 *
	 * @param string $name   Key.
	 * @param mixed  $value  Value.
	 *
	 * @return bool  If the setting was updated or not.
	 */
	public function set_settings( $name = '', $value = '' ) {
		if ( empty( $name ) ) {
			return false;
		}

		return $this->is_global() ? update_site_option( $name, $value ) : update_option( $name, $value );
	}

	/**
	 * TODO: WORK IN PROGRESS.
	 * Fetch the settings, based on WordPress install type (single/multisite) or access control settings.
	 *
	 * @since 3.2.2  Added $module parameter.
	 *
	 * @param string $name     Setting name to fetch.
	 * @param string $module   Setting is part of this module.
	 * @param bool   $default  Default setting vale.
	 *
	 * @return bool|mixed
	 */
	public function get_setting( $name = '', $module = 'bulk', $default = false ) {
		if ( empty( $name ) ) {
			return false;
		}

		$access = $this->is_global();

		// Subsite control is disabled.
		if ( false === $access ) {
			return get_option( $name, $default );
		}

		// Network admins can modify all the modules.
		if ( true === $access ) {
			return get_site_option( $name, $default );
		}

		// Just in case there's some weird error, and it's not an array.
		if ( ! is_array( $access ) ) {
			return false;
		}

		// In network admin it's possible to see only UNSELECTED modules.
		if ( is_network_admin() ) {
			return ! in_array( $module, $access, true );
		}

		// Vice versa for sub sites.
		return in_array( $module, $access, true );
	}

}
