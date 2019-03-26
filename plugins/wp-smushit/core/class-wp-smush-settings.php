<?php
/**
 * Smush Settings class: WP_Smush_Settings
 *
 * @since 3.0  Migrated from old settings class.
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_Settings
 *
 * @since 3.0
 */
class WP_Smush_Settings {

	/**
	 * Plugin instance.
	 *
	 * @since 3.0
	 *
	 * @var null|WP_Smush_Settings
	 */
	private static $instance = null;

	/**
	 * Default settings array.
	 *
	 * We don't want it to be edited directly, so we use public get_*, set_* and delete_* methods.
	 *
	 * @since 3.0  Improved structure.
	 *
	 * @var array
	 */
	private $settings = array(
		'networkwide'       => false,
		'auto'              => true,  // works with CDN.
		'lossy'             => false, // works with CDN.
		'strip_exif'        => true,  // works with CDN.
		'resize'            => false,
		'detection'         => false,
		'original'          => false,
		'backup'            => false,
		'png_to_jpg'        => false, // works with CDN.
		'nextgen'           => false,
		's3'                => false,
		'gutenberg'         => false,
		'cdn'               => false,
		'auto_resize'       => false,
		'webp'              => true,
		'usage'             => true,
		'accessible_colors' => false,
		'keep_data'         => true,
		'lazy_load'         => false,
	);

	/**
	 * List of fields in bulk smush form.
	 *
	 * @used-by save()
	 *
	 * @var array
	 */
	private $bulk_fields = array( 'networkwide', 'auto', 'lossy', 'original', 'strip_exif', 'resize', 'backup', 'png_to_jpg', 'detection' );

	/**
	 * List of fields in integration form.
	 *
	 * @used-by save()
	 *
	 * @var array
	 */
	private $integration_fields = array( 'gutenberg', 'nextgen', 's3' );

	/**
	 * List of fields in CDN form.
	 *
	 * @used-by save()
	 *
	 * @var array
	 */
	private $cdn_fields = array( 'auto_resize', 'cdn', 'webp' );

	/**
	 * List of fields in Settings form.
	 *
	 * @used-by save()
	 *
	 * @var array
	 */
	private $settings_fields = array( 'accessible_colors', 'usage', 'keep_data' );

	/**
	 * List of fields in lazy loading form.
	 *
	 * @used-by save()
	 *
	 * @var array
	 */
	private $lazy_load_fields = array( 'lazy_load' );

	/**
	 * Return the plugin instance.
	 *
	 * @since 3.0
	 *
	 * @return WP_Smush_Settings
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * WP_Smush_Settings constructor.
	 */
	private function __construct() {
		// Do not initialize if not in admin area
		// wp_head runs specifically in the frontend, good check to make sure we're accidentally not loading settings on required pages.
		if ( ! is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) && did_action( 'wp_head' ) ) {
			return;
		}

		// Save Settings.
		add_action( 'wp_ajax_save_settings', array( $this, 'save' ) );
		// Reset Settings.
		add_action( 'wp_ajax_reset_settings', array( $this, 'reset' ) );

		$this->init();
	}

	/**
	 * Init settings.
	 *
	 * If there are no settings in the database, populate it with the defaults, if settings are present
	 *
	 * @return array
	 */
	public function init() {
		// See if we've got serialised settings stored already.
		$settings = $this->get_setting( WP_SMUSH_PREFIX . 'settings', array() );
		if ( empty( $settings ) ) {
			$this->set_setting( WP_SMUSH_PREFIX . 'settings', $this->settings );
		}

		// Store it in class variable.
		if ( ! empty( $settings ) && is_array( $settings ) ) {
			// Merge with the existing settings.
			$this->settings = array_merge( $this->settings, $settings );
		}

		return $this->settings;
	}

	/**
	 * Checks whether the settings are applicable for the whole network/site or sitewise (multisite).
	 */
	public function is_network_enabled() {
		// If single site return true.
		if ( ! is_multisite() ) {
			return false;
		}

		// Get directly from db.
		return get_site_option( WP_SMUSH_PREFIX . 'networkwide' );
	}

	/**
	 * Getter method for $settings.
	 *
	 * @since 3.0
	 *
	 * @param string $setting Setting to get. Default: get all settings.
	 *
	 * @return array|bool  Return either a setting value or array of settings.
	 */
	public function get( $setting = '' ) {
		$settings = $this->settings;

		if ( ! empty( $setting ) ) {
			return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
		}

		return $settings;
	}

	/**
	 * Setter method for $settings.
	 *
	 * @since 3.0
	 *
	 * @param string $setting  Setting to update.
	 * @param bool   $value    Value to set. Default: false.
	 */
	public function set( $setting = '', $value = false ) {
		if ( empty( $setting ) ) {
			return;
		}

		$this->settings[ $setting ] = $value;

		$this->set_setting( WP_SMUSH_PREFIX . 'settings', $this->settings );
	}

	/**
	 * Get all Smush settings, based on if network settings are enabled or not.
	 *
	 * @param string $name     Setting to fetch.
	 * @param mixed  $default  Default value.
	 *
	 * @return bool|mixed
	 */
	public function get_setting( $name = '', $default = false ) {
		if ( empty( $name ) ) {
			return false;
		}

		return $this->is_network_enabled() ? get_site_option( $name, $default ) : get_option( $name, $default );
	}

	/**
	 * Update value for given setting key
	 *
	 * @param string $name   Key.
	 * @param mixed  $value  Value.
	 *
	 * @return bool If the setting was updated or not
	 */
	public function set_setting( $name = '', $value = '' ) {
		if ( empty( $name ) ) {
			return false;
		}

		return $this->is_network_enabled() ? update_site_option( $name, $value ) : update_option( $name, $value );
	}

	/**
	 * Delete the given key name.
	 *
	 * @param string $name  Key.
	 *
	 * @return bool If the setting was updated or not
	 */
	public function delete_setting( $name = '' ) {
		if ( empty( $name ) ) {
			return false;
		}

		return $this->is_network_enabled() ? delete_site_option( $name ) : delete_option( $name );
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @since 3.2.0
	 */
	public function reset() {
		check_ajax_referer( 'get-smush-status' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		$this->delete_setting( WP_SMUSH_PREFIX . 'settings' );
		$this->delete_setting( WP_SMUSH_PREFIX . 'hide_smush_welcome' );
		$this->delete_setting( WP_SMUSH_PREFIX . 'image_sizes' );
		$this->delete_setting( WP_SMUSH_PREFIX . 'resize_sizes' );
		$this->delete_setting( WP_SMUSH_PREFIX . 'cdn_status' );
		$this->delete_setting( WP_SMUSH_PREFIX . 'lazy_load' );
		$this->delete_setting( 'skip-smush-setup' );
		$this->delete_setting( WP_SMUSH_PREFIX . 'hide_pagespeed_suggestion' );

		wp_send_json_success();
	}

	/**
	 * Save settings.
	 *
	 * @param bool $json_response  Send a JSON response.
	 */
	public function save( $json_response = true ) {
		check_ajax_referer( 'save_wp_smush_options', 'wp_smush_options_nonce' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		$pages_with_settings = array( 'bulk', 'integration', 'cdn', 'settings', 'lazy_load' );
		$setting_form        = isset( $_POST['setting_form'] ) ? sanitize_text_field( wp_unslash( $_POST['setting_form'] ) ) : '';

		// Continue only if form name is set.
		if ( ! in_array( $setting_form, $pages_with_settings, true ) ) {
			return;
		}

		// Store that we need not redirect again on plugin activation.
		update_site_option( WP_SMUSH_PREFIX . 'hide_smush_welcome', true );

		$settings = $this->get();

		// Save whether to use the settings networkwide or not ( Only if in network admin ).
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		if ( 'save_settings' === $action ) {
			$settings['networkwide'] = filter_input( INPUT_POST, WP_SMUSH_PREFIX . 'networkwide', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			update_site_option( WP_SMUSH_PREFIX . 'networkwide', $settings['networkwide'] );
		}

		// Delete S3 alert flag, if S3 option is disabled again.
		if ( ! isset( $_POST['wp-smush-s3'] ) && isset( $settings['integration']['s3'] ) && $settings['integration']['s3'] ) {
			delete_site_option( WP_SMUSH_PREFIX . 'hide_s3support_alert' );
		}

		$core_settings = WP_Smush::get_instance()->core()->settings;

		// Process each setting and update options.
		foreach ( $core_settings as $name => $text ) {
			// Do not update if field is not available in current form.
			if ( ! in_array( $name, $this->{$setting_form . '_fields'}, true ) ) {
				continue;
			}

			// Update the setting.
			$settings[ $name ] = filter_input( INPUT_POST, WP_SMUSH_PREFIX . $name, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		}

		// Settings that are specific to a page.
		if ( 'bulk' === $setting_form ) {
			$this->parse_bulk_settings();
		}

		if ( 'cdn' === $setting_form ) {
			$this->parse_cdn_settings();
		}

		if ( 'lazy_load' === $setting_form ) {
			$this->parse_lazy_load_settings();
		}

		// Store the option in table.
		$this->set_setting( WP_SMUSH_PREFIX . 'settings', $settings );
		$this->set_setting( WP_SMUSH_PREFIX . 'settings_updated', 1 );

		if ( $json_response ) {
			wp_send_json_success();
		}
	}

	/**
	 * Parse bulk Smush specific settings.
	 *
	 * @since 3.2.0  Moved from save method.
	 */
	private function parse_bulk_settings() {
		$image_sizes = array();

		check_ajax_referer( 'save_wp_smush_options', 'wp_smush_options_nonce' );

		// Save the selected image sizes.
		if ( ! empty( $_POST['wp-smush-image_sizes'] ) ) {
			$image_sizes = array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['wp-smush-image_sizes'] ) ) );
		}

		// Update Resize width and height settings if set.
		$resize_sizes['width']  = isset( $_POST['wp-smush-resize_width'] ) ? intval( $_POST['wp-smush-resize_width'] ) : 0; // Input var ok.
		$resize_sizes['height'] = isset( $_POST['wp-smush-resize_height'] ) ? intval( $_POST['wp-smush-resize_height'] ) : 0; // Input var ok.

		$this->set_setting( WP_SMUSH_PREFIX . 'image_sizes', $image_sizes );
		$this->set_setting( WP_SMUSH_PREFIX . 'resize_sizes', $resize_sizes );
	}

	/**
	 * Parse CDN specific settings.
	 *
	 * @since 3.2.0  Moved from save method.
	 */
	private function parse_cdn_settings() {
		// $status = connect to CDN.
		$enabled = WP_Smush::get_instance()->core()->mod->cdn->get_status();

		if ( ! $enabled ) {
			$response = WP_Smush::get_instance()->api()->enable();
			$response = json_decode( $response['body'] );

			$this->set_setting( WP_SMUSH_PREFIX . 'cdn_status', $response->data );
		}
	}

	/**
	 * Parse lazy loading specific settings.
	 *
	 * @since 3.2.0
	 */
	private function parse_lazy_load_settings() {
		$args = array(
			'format'          => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
			'output'          => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
			'animation'       => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
			'include'         => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags'  => FILTER_REQUIRE_ARRAY,
			),
			'exclude-pages'   => FILTER_SANITIZE_STRING,
			'exclude-classes' => FILTER_SANITIZE_STRING,
			'footer'          => FILTER_VALIDATE_BOOLEAN,
		);

		$settings = filter_input_array( INPUT_POST, $args );

		// Animation settings.
		$settings['animation']['fadein']   = isset( $settings['animation']['value'] ) && 'fadein' === $settings['animation']['value'] ? true : false;
		$settings['animation']['duration'] = isset( $settings['animation']['duration'] ) ? absint( $settings['animation']['duration'] ) : 0;
		$settings['animation']['delay']    = isset( $settings['animation']['delay'] ) ? absint( $settings['animation']['delay'] ) : 0;
		$settings['animation']['spinner']  = isset( $settings['animation']['value'] ) && 'spinner' === $settings['animation']['value'] ? true : false;
		$settings['animation']['disabled'] = isset( $settings['animation']['value'] ) && 'disabled' === $settings['animation']['value'] ? true : false;
		unset( $settings['animation']['value'] );

		// Convert to array.
		if ( ! empty( $settings['exclude-pages'] ) ) {
			$settings['exclude-pages'] = preg_split( '/[\r\n\t ]+/', $settings['exclude-pages'] );
		} else {
			$settings['exclude-pages'] = array();
		}
		if ( ! empty( $settings['exclude-classes'] ) ) {
			$settings['exclude-classes'] = preg_split( '/[\r\n\t ]+/', $settings['exclude-classes'] );
		} else {
			$settings['exclude-classes'] = array();
		}

		$this->set_setting( WP_SMUSH_PREFIX . 'lazy_load', $settings );
	}

	/**
	 * Apply a default configuration to lazy loading on first activation.
	 *
	 * @since 3.2.0
	 */
	public function init_lazy_load_defaults() {
		$defaults = array(
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
				'fadein'   => true,
				'duration' => 400,
				'delay'    => 0,
				'spinner'  => false,
				'disabled' => false,
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

		$this->set_setting( WP_SMUSH_PREFIX . 'lazy_load', $defaults );
	}

}
