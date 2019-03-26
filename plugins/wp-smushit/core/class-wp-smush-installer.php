<?php
/**
 * Smush installer (update/upgrade procedures): WP_Smush_Installer class
 *
 * @package WP_Smush
 * @subpackage Admin
 * @since 2.8.0
 *
 * @author Anton Vanyukov <anton@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_Installer for handling updates and upgrades of the plugin.
 *
 * @since 2.8.0
 */
class WP_Smush_Installer {

	/**
	 * Triggered on Smush deactivation.
	 *
	 * @since 3.1.0
	 */
	public static function smush_deactivated() {
		WP_Smush::get_instance()->core()->mod->cdn->unschedule_cron();
	}

	/**
	 * Check if a existing install or new.
	 *
	 * @since 2.8.0  Moved to this class from wp-smush.php file.
	 */
	public static function smush_activated() {
		if ( ! defined( 'WP_SMUSH_ACTIVATING' ) ) {
			define( 'WP_SMUSH_ACTIVATING', true );
		}

		$version  = get_site_option( WP_SMUSH_PREFIX . 'version' );
		$settings = WP_Smush_Settings::get_instance()->get();
		$settings = ! empty( $settings ) ? $settings : WP_Smush_Settings::get_instance()->init();

		// If the version is not saved or if the version is not same as the current version,.
		if ( ! $version || WP_SMUSH_VERSION !== $version ) {
			global $wpdb;
			// Check if there are any existing smush stats.
			$results = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key=%s LIMIT 1",
					'wp-smpro-smush-data'
				)
			); // db call ok; no-cache ok.

			if ( $results ) {
				update_site_option( 'wp-smush-install-type', 'existing' );
			} else {
				// Check for existing settings.
				if ( false !== $settings['auto'] ) {
					update_site_option( 'wp-smush-install-type', 'existing' );
				}
			}

			// Create directory smush table.
			self::directory_smush_table();

			// Store the plugin version in db.
			update_site_option( WP_SMUSH_PREFIX . 'version', WP_SMUSH_VERSION );
		}
	}

	/**
	 * Handle plugin upgrades.
	 *
	 * @since 2.8.0
	 */
	public static function upgrade_settings() {
		// Avoid to execute this over an over in same thread.
		if ( defined( 'WP_SMUSH_ACTIVATING' ) || ( defined( 'WP_SMUSH_UPGRADING' ) && WP_SMUSH_UPGRADING ) ) {
			return;
		}

		$version = get_site_option( WP_SMUSH_PREFIX . 'version' );

		if ( false === $version ) {
			self::smush_activated();
		}

		if ( false !== $version && WP_SMUSH_VERSION !== $version ) {

			if ( ! defined( 'WP_SMUSH_UPGRADING' ) ) {
				define( 'WP_SMUSH_UPGRADING', true );
			}

			if ( version_compare( $version, '2.8.0', '<' ) ) {
				self::upgrade_2_8_0();
			}

			if ( version_compare( $version, '3.0', '<' ) ) {
				self::upgrade_3_0();
			}

			if ( version_compare( $version, '3.2.0', '<' ) ) {
				self::upgrade_3_2_0();
			}

			// Create/upgrade directory smush table.
			self::directory_smush_table();

			// Store the latest plugin version in db.
			update_site_option( WP_SMUSH_PREFIX . 'version', WP_SMUSH_VERSION );
		}
	}

	/**
	 * Upgrade old settings to new if required.
	 *
	 * We have changed exif data setting key from version 2.8
	 * Update the existing value to new one.
	 *
	 * @since 2.8.0
	 */
	private static function upgrade_2_8_0() {
		// If exif is not preserved, it will be stripped by default.
		if ( WP_Smush_Settings::get_instance()->get_setting( WP_SMUSH_PREFIX . 'keep_exif' ) ) {
			// Set not to strip exif value.
			WP_Smush_Settings::get_instance()->set_setting( WP_SMUSH_PREFIX . 'strip_exif', 0 );
			// Delete the old exif setting.
			WP_Smush_Settings::get_instance()->delete_setting( WP_SMUSH_PREFIX . 'keep_exif' );
		}
	}

	/**
	 * Create or upgrade custom table for directory Smush.
	 *
	 * After creating or upgrading the custom table, update the path_hash
	 * column value and structure if upgrading from old version.
	 *
	 * @since 2.9.0
	 */
	public static function directory_smush_table() {
		// Create a class object, if doesn't exists.
		if ( ! is_object( WP_Smush::get_instance()->core()->mod->dir ) ) {
			WP_Smush::get_instance()->core()->mod->dir = new WP_Smush_Dir();
		}

		// No need to continue on sub sites.
		if ( ! WP_Smush_Dir::should_continue() ) {
			return;
		}

		// Create/upgrade directory smush table.
		WP_Smush::get_instance()->core()->mod->dir->create_table();

		// Run the directory smush table update.
		WP_Smush::get_instance()->core()->mod->dir->update_dir_path_hash();
	}

	/**
	 * Update settings to new structure.
	 *
	 * @since 3.0
	 */
	private static function upgrade_3_0() {
		$keys = array(
			'networkwide',
			'auto',
			'lossy',
			'strip_exif',
			'resize',
			'detection',
			'original',
			'backup',
			'png_to_jpg',
			'nextgen',
			'gutenberg',
			's3',
		);

		if ( is_multisite() && ! get_site_option( WP_SMUSH_PREFIX . 'networkwide' ) ) {
			global $wpdb;

			$offset = 0;
			$limit  = 100;

			while ( $blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs} LIMIT $offset, $limit", ARRAY_A ) ) {
				if ( $blogs ) {
					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog['blog_id'] );

						$settings = get_option( WP_SMUSH_PREFIX . 'last_settings', array() );
						$settings = array_merge( WP_Smush_Settings::get_instance()->get(), $settings );
						update_option( WP_SMUSH_PREFIX . 'settings', $settings );
						// Remove previous data.
						delete_option( WP_SMUSH_PREFIX . 'last_settings' );

						foreach ( $keys as $key ) {
							delete_option( WP_SMUSH_PREFIX . $key );
						}
					}
					restore_current_blog();
				}
				$offset += $limit;
			}
		} else {
			// last_settings will be an array if user had any custom settings.
			$settings = get_site_option( WP_SMUSH_PREFIX . 'last_settings', array() );
			if ( is_array( $settings ) ) {
				$settings = array_merge( WP_Smush_Settings::get_instance()->get(), $settings );
			} else {
				// last_settings will be a string if the Smush page hasn't been visited => get the new defaults.
				$settings = WP_Smush_Settings::get_instance()->get();
			}

			update_site_option( WP_SMUSH_PREFIX . 'settings', $settings );
			// Remove previous data.
			delete_site_option( WP_SMUSH_PREFIX . 'last_settings' );

			foreach ( $keys as $key ) {
				delete_site_option( WP_SMUSH_PREFIX . $key );
			}
		}
	}

	/**
	 * Upgrade to 3.2.0.
	 *
	 * @since 3.2.0
	 */
	private static function upgrade_3_2_0() {
		// Not used.
		delete_option( 'smush_option' );
	}

}
