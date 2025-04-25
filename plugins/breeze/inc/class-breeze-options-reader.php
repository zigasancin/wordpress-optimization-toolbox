<?php

// General singleton class.
class Breeze_Options_Reader {
	// Hold the class instance.
	private static $instance = null;

	private static $options = array();

	// The constructor is private
	// to prevent initiation with outer code.
	private function __construct() {
		// The expensive process (e.g.,db connection) goes here.
	}

	// The object is created from within the class itself
	// only if the class has no instance.
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Breeze_Options_Reader();
		}

		return self::$instance;
	}

	/**
	 * Provides the requested option or option group.
	 *
	 * @param string $option_name option name or group of options name if $hierarchy is true.
	 * @param bool $hierarchy If tru then it will return the whole group.
	 * @param bool $root if Multisite and $root is true, returns network level options.
	 *
	 * @return mixed|null
	 */
	public static function get_option_value( $option_name = '', $hierarchy = false, $root = false ) {

		if ( is_multisite() ) {
			if ( isset( self::$options['blog_id'] ) && (int) self::$options['blog_id'] !== get_current_blog_id() ) {
				self::$options = array();
			} elseif ( ! isset( self::$options['blog_id'] ) ) {
				self::$options = array();
			}
		}

		if ( ! empty( self::$options ) && isset( self::$options[ $option_name ] ) ) {
			return self::$options[ $option_name ];
		}

		$root_option_groups = array(
			'basic_settings',
			'file_settings',
			'preload_settings',
			'advanced_settings',
			'heartbeat_settings',
			'cdn_integration',
			'varnish_cache',
			'inherit_settings',
		);

		foreach ( $root_option_groups as $group ) {
			$read_data = self::read_the_option_data( $group );

			if ( ! empty( $read_data ) ) {
				if ( false === $hierarchy ) {
					foreach ( $read_data as $option_key => $option_value ) {
						if ( false === array_key_exists( $option_key, self::$options ) || empty( self::$options[ $option_key ] ) ) {
							self::$options[ $option_key ] = $option_value;
						}
					}
				} else {
					self::$options[ $group ] = $read_data;
				}
			}
		}

		if ( is_multisite() ) {
			self::$options['blog_id'] = get_current_blog_id();
		}

		if ( isset( self::$options[ $option_name ] ) ) {
			return self::$options[ $option_name ];
		}

		return null;

	}

	/**
	 * Retrieve site options accounting for settings inheritance.
	 *
	 * @param string $option_name
	 * @param bool $is_local
	 *
	 * @return array
	 */
	private static function read_the_option_data( $option_name, $is_local = false ) {
		$inherit = true;

		global $breeze_network_subsite_settings;

		if ( is_network_admin() && ! $breeze_network_subsite_settings ) {
			$is_local = false;
		} elseif ( ! breeze_does_inherit_settings() ) {
			$inherit = false;
		}

		if ( ! is_multisite() || $is_local || ! $inherit ) {
			$option = get_option( 'breeze_' . $option_name );
		} else {
			$option = get_site_option( 'breeze_' . $option_name );
		}

		if ( empty( $option ) || ! is_array( $option ) ) {
			$option = array();
		}

		return $option;
	}

	public static function fetch_all_saved_settings( $is_root = false ) {
		self::$options = array();

		if ( true === $is_root && ! defined( 'WP_NETWORK_ADMIN' ) ) {
			define( 'WP_NETWORK_ADMIN', true );
		}

		self::get_option_value( 'all', false, $is_root );

		return self::$options;
	}
}
