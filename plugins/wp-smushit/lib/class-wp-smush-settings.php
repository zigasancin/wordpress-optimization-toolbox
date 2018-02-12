<?php

if ( ! class_exists( 'WpSmushSettings' ) ) {
	class WpSmushSettings {

		/**
		 * List of all the features enabled/disabled
		 *
		 * @var array
		 */
		var $settings = array(
			'networkwide' => 0,
			'auto'        => 1,
			'lossy'       => 0,
			'original'    => 0,
			'keep_exif'   => 0,
			'resize'      => 0,
			'backup'      => 0,
			'png_to_jpg'  => 0,
			'nextgen'     => 0,
			's3'          => 0
		);

		function __construct() {

			//Save Settings
			add_action( 'wp_ajax_save_settings', array( $this, 'save_settings' ) );

			$this->init_settings();
		}

		/**
		 * Initialize settings
		 *
		 * @return array|mixed
		 */
		function init_settings() {

			$last_settings = $this->get_setting( WP_SMUSH_PREFIX . 'last_settings', array() );
			if( empty( $last_settings ) ) {
				$last_settings = $this->get_serialised_settings();
			}
			$last_settings = maybe_unserialize( $last_settings );
			//Merge with the existing settings
			$this->settings = array_merge( $this->settings, $last_settings );
			return $this->settings;

		}

		/**
		 * Save settings, Used for networkwide option
		 */
		function save_settings() {
			//Validate Ajax request
			check_ajax_referer( 'save_wp_smush_options', 'nonce' );

			//Save Settings
			$this->process_options();
			wp_send_json_success();

		}

		/**
		 * Returns a serialised string of current settings
		 *
		 * @return Serialised string of settings
		 *
		 */
		function get_serialised_settings() {
			$settings = array();
			foreach ( $this->settings as $key => $val ) {
				$value = $this->get_setting( WP_SMUSH_PREFIX . $key );;
				if ( 'auto' == $key && $value === false ) {
					$settings[ $key ] = 1;
				} else {
					$settings[ $key ] = $value;
				}
			}
			$settings = maybe_serialize( $settings );

			return $settings;
		}

		/**
		 * Stores the latest settings in serialised form in DB For the current settings
		 *
		 * No need to store the serialised settings, if network wide settings is disabled
		 * because the site would run the scan when settings are saved
		 *
		 */
		function save_serialized_settings() {
			//Return -> Single Site | If network settings page | Networkwide Settings Disabled
			if ( ! is_multisite() || is_network_admin() || ! $this->settings['networkwide'] ) {
				return;
			}
			$c_settings = $this->get_serialised_settings();
			$this->update_setting( WP_SMUSH_PREFIX . 'last_settings', $c_settings );
		}

		/**
		 * Check if form is submitted and process it
		 *
		 * @return null
		 */
		function process_options() {

			if ( ! is_user_logged_in() ) {
				return false;
			}

			global $wpsmushit_admin;

			//Store that we need not redirect again on plugin activation
			update_site_option( 'wp-smush-hide_smush_welcome', true );

			// var to temporarily assign the option value
			$setting = null;

			//Check the last settings stored in db
			$settings = $this->get_setting( WP_SMUSH_PREFIX . 'last_settings', array() );

			//If not saved earlier, get it from stored options
			if ( empty( $settings ) || 0 == sizeof( $settings ) ) {
				$settings = $this->get_serialised_settings();
			}

			$settings = !is_array( $settings ) ? maybe_unserialize( $settings ) : $settings ;

			//Save whether to use the settings networkwide or not ( Only if in network admin )
			if ( ! empty( $_POST['action'] ) && 'save_settings' == $_POST['action'] ) {
				if ( ! isset( $_POST['wp-smush-networkwide'] ) ) {
					$settings['networkwide'] = 0;
					//Save the option to disable nwtwork wide settings and return
					update_site_option( WP_SMUSH_PREFIX . 'networkwide', 0 );
				} else {
					$settings['networkwide'] = 1;
					//Save the option to disable nwtwork wide settings and return
					update_site_option( WP_SMUSH_PREFIX . 'networkwide', 1 );
				}
			}

			// Delete S3 alert flag, if S3 option is disabled again.
			if ( ! isset( $_POST['wp-smush-s3'] ) && isset( $this->settings['s3'] ) && $this->settings['s3'] ) {
				delete_site_option( 'wp-smush-hide_s3support_alert' );
			}

			// process each setting and update options
			foreach ( $wpsmushit_admin->settings as $name => $text ) {

				// formulate the index of option
				$opt_name = WP_SMUSH_PREFIX . $name;

				// get the value to be saved
				$setting = isset( $_POST[ $opt_name ] ) ? 1 : 0;

				$settings[ $name ] = $setting;

				// update the new value
				$this->update_setting( $opt_name, $setting );

				// unset the var for next loop
				unset( $setting );
			}

			//Save serialised settings
			$resp = $this->update_setting( WP_SMUSH_PREFIX . 'last_settings', $settings );

			//Update initialised settings
			$this->settings = $settings;

			//Save the selected image sizes
			$image_sizes = ! empty( $_POST['wp-smush-image_sizes'] ) ? $_POST['wp-smush-image_sizes'] : array();
			$image_sizes = array_filter( array_map( "sanitize_text_field", $image_sizes ) );
			$this->update_setting( WP_SMUSH_PREFIX . 'image_sizes', $image_sizes );

			//Update Resize width and height settings if set
			$resize_sizes['width']  = isset( $_POST['wp-smush-resize_width'] ) ? intval( $_POST['wp-smush-resize_width'] ) : 0;
			$resize_sizes['height'] = isset( $_POST['wp-smush-resize_height'] ) ? intval( $_POST['wp-smush-resize_height'] ) : 0;

			// update the resize sizes
			$this->update_setting( WP_SMUSH_PREFIX . 'resize_sizes', $resize_sizes );

			//Store the option in table
			$this->update_setting( 'wp-smush-settings_updated', 1 );

			if( $resp ) {
				//Run a re-check on next page load
				update_site_option( WP_SMUSH_PREFIX . 'run_recheck', 1 );
			}

			//Delete Show Resmush option
			if ( isset( $_POST['wp-smush-keep_exif'] ) && ! isset( $_POST['wp-smush-original'] ) && ! isset( $_POST['wp-smush-lossy'] ) ) {
				//@todo: Update Resmush ids
			}

		}

		/**
		 * Checks whether the settings are applicable for the whole network/site or Sitewise ( Multisite )
		 * @todo: Check in subdirectory installation as well
		 */
		function is_network_enabled() {
			//If Single site return true
			if ( ! is_multisite() ) {
				return true;
			}

			//Get directly from db
			return get_site_option(WP_SMUSH_PREFIX . 'networkwide' );
		}

		/**
		 * Returns the value of given setting key, based on if network settings are enabled or not
		 *
		 * @param string $name Setting to fetch
		 * @param string $default Default Value
		 *
		 * @return bool|mixed|void
		 *
		 */
		function get_setting( $name = '', $default = false ) {

			if( empty( $name ) ) {
				return false;
			}

			return $this->is_network_enabled() ? get_site_option( $name, $default ) : get_option( $name, $default );
		}

		/**
		 * Update value for given setting key
		 *
		 * @param string $name Key
		 * @param string $value Value
		 *
		 * @return bool If the setting was updated or not
		 */
		function update_setting( $name = '', $value = '' ) {

			if( empty( $name ) ) {
				return false;
			}

			return $this->is_network_enabled() ? update_site_option( $name, $value ) : update_option( $name, $value );
		}

		/**
		 * Delete the given key name
		 *
		 * @param string $name Key
		 *
		 * @return bool If the setting was updated or not
		 */
		function delete_setting( $name = '' ) {

			if( empty( $name ) ) {
				return false;
			}

			return $this->is_network_enabled() ? delete_site_option( $name ) : delete_option( $name );
		}

	}
	global $wpsmush_settings;
	$wpsmush_settings = new WpSmushSettings();
}