<?php
/**
 * Smush Settings class: WP_Smush_Settings
 *
 * @since 3.0  Migrated from old settings class.
 * @package WP_Smush
 */

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
		'networkwide' => false,
		'auto'        => true,  // works with CDN.
		'lossy'       => false, // works with CDN.
		'strip_exif'  => true,  // works with CDN.
		'resize'      => false,
		'detection'   => false,
		'original'    => false,
		'backup'      => false,
		'png_to_jpg'  => false, // works with CDN.
		'nextgen'     => false,
		's3'          => false,
		'gutenberg'   => false,
		'cdn'         => false,
		'auto_resize' => false,
		'webp'        => true,
	);

	/**
	 * List of fields in bulk smush form.
	 *
	 * @var array
	 */
	private $bulk_fields = array(
		'networkwide',
		'auto',
		'lossy',
		'original',
		'strip_exif',
		'resize',
		'backup',
		'png_to_jpg',
		'detection',
	);

	/**
	 * List of fields in integration form.
	 *
	 * @var array
	 */
	private $integration_fields = array(
		'gutenberg',
		'nextgen',
		's3',
	);

	/**
	 * List of fields in CDN form.
	 *
	 * @var array
	 */
	private $cdn_fields = array(
		'auto_resize',
		'cdn',
		'webp',
	);

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
		add_action( 'wp_ajax_save_settings', array( $this, 'save_settings' ) );

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
	 * Save settings, used for networkwide option.
	 */
	public function save_settings() {
		// Validate ajax request.
		check_ajax_referer( 'save_wp_smush_options', 'wp_smush_options_nonce' );

		// Save settings.
		$this->process_options();
		wp_send_json_success();
	}

	/**
	 * Check if form is submitted and process it
	 */
	public function process_options() {
		check_ajax_referer( 'save_wp_smush_options', 'wp_smush_options_nonce' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		$pages_with_settings = array( 'bulk', 'integration', 'cdn' );

		// Continue only if form name is set.
		if ( ! isset( $_POST['setting_form'] ) || ! in_array( wp_unslash( $_POST['setting_form'] ), $pages_with_settings, true ) ) { // Input var ok.
			return;
		}

		$setting_form = sanitize_text_field( wp_unslash( $_POST['setting_form'] ) );

		// Store that we need not redirect again on plugin activation.
		update_site_option( WP_SMUSH_PREFIX . 'hide_smush_welcome', true );

		$settings = $this->get();

		// Save whether to use the settings networkwide or not ( Only if in network admin ).
		if ( isset( $_POST['action'] ) && 'save_settings' === wp_unslash( $_POST['action'] ) ) { // Input var ok.
			$settings['networkwide'] = (bool) wp_unslash( $_POST['wp-smush-networkwide'] );
			update_site_option( WP_SMUSH_PREFIX . 'networkwide', $settings['networkwide'] );
		}

		// Delete S3 alert flag, if S3 option is disabled again.
		if ( ! isset( $_POST['wp-smush-s3'] ) && isset( $settings['integration']['s3'] ) && $settings['integration']['s3'] ) {
			delete_site_option( WP_SMUSH_PREFIX . 'hide_s3support_alert' );
		}

		// Current form fields.
		$form_fields = $this->{$setting_form . '_fields'};

		$core_settings = WP_Smush::get_instance()->core()->settings;

		// Process each setting and update options.
		foreach ( $core_settings as $name => $text ) {
			// Do not update if field is not available in current form.
			if ( ! in_array( $name, $form_fields, true ) ) {
				continue;
			}

			// Get the value to be saved.
			$setting = isset( $_POST[ WP_SMUSH_PREFIX . $name ] ) ? true : false; // Input var ok.

			$settings[ $name ] = $setting;

			// Unset the var for next loop.
			unset( $setting );
		}

		// Update initialised settings.
		$this->settings = $settings;

		$resp = $this->set_setting( WP_SMUSH_PREFIX . 'settings', $this->settings );

		// Settings that are specific to a page.
		if ( 'bulk' === $setting_form ) {
			// Save the selected image sizes.
			$image_sizes = ! empty( $_POST['wp-smush-image_sizes'] ) ? $_POST['wp-smush-image_sizes'] : array(); // Input var ok.
			$image_sizes = array_filter( array_map( 'sanitize_text_field', $image_sizes ) );
			$this->set_setting( WP_SMUSH_PREFIX . 'image_sizes', $image_sizes );

			// Update Resize width and height settings if set.
			$resize_sizes['width']  = isset( $_POST['wp-smush-resize_width'] ) ? intval( $_POST['wp-smush-resize_width'] ) : 0; // Input var ok.
			$resize_sizes['height'] = isset( $_POST['wp-smush-resize_height'] ) ? intval( $_POST['wp-smush-resize_height'] ) : 0; // Input var ok.
			$this->set_setting( WP_SMUSH_PREFIX . 'resize_sizes', $resize_sizes );
		}

		if ( 'cdn' === $setting_form ) {
			// $status = connect to CDN.
			$enabled = WP_Smush::get_instance()->core()->mod->cdn->get_status();
			if ( ! $enabled ) {
				$response = WP_Smush::get_instance()->api()->enable();
				$response = json_decode( $response['body'] );
				$this->set_setting( WP_SMUSH_PREFIX . 'cdn_status', $response->data );
			}
		}

		// Store the option in table.
		$this->set_setting( WP_SMUSH_PREFIX . 'settings_updated', 1 );

		if ( $resp ) {
			// Run a re-check on next page load.
			update_site_option( WP_SMUSH_PREFIX . 'run_recheck', 1 );
		}
	}
}
