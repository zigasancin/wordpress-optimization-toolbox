<?php

/**
 * Class that handles the export and import of Breeze options
 *
 * Class Breeze_Settings_Import_Export
 */
class Breeze_Settings_Import_Export {

	function __construct() {

		// Logged in users only action.
		add_action( 'wp_ajax_breeze_export_json', array( &$this, 'export_json_settings' ) );
		add_action( 'wp_ajax_breeze_import_json', array( &$this, 'import_json_settings' ) );
	}

	/**
	 * Import settings using interface in back-end.
	 *
	 * @since 1.2.2
	 * @access public
	 */
	public function import_json_settings() {
		breeze_is_restricted_access();
		check_ajax_referer( '_breeze_import_settings', 'security' );

		if ( ! current_user_can( 'administrator' ) ) {
			wp_send_json_error( new WP_Error( 'authority_issue', __( 'Only administrator can import settings', 'breeze' ) ) );

		}

		set_as_network_screen();

		if ( isset( $_FILES['breeze_import_file'] ) ) {
			$allowed_extension = array( 'json' );
			$temp              = explode( '.', $_FILES['breeze_import_file']['name'] );
			$extension         = strtolower( end( $temp ) );

			if ( ! in_array( $extension, $allowed_extension, true ) ) {
				wp_send_json_error( new WP_Error( 'ext_err', __( 'The provided file is not a JSON', 'breeze' ) ) );
			}

			if ( 'application/json' !== $_FILES['breeze_import_file']['type'] ) {
				wp_send_json_error( new WP_Error( 'format_err', __( 'The provided file is not a JSON file.', 'breeze' ) ) );
			}

			$get_file_content = file_get_contents( $_FILES['breeze_import_file']['tmp_name'] );
			$json             = json_decode( trim( $get_file_content ), true );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				if (
					isset( $json['breeze_basic_settings'] ) &&
					isset( $json['breeze_advanced_settings'] ) &&
					isset( $json['breeze_cdn_integration'] )
				) {
					$level = '';
					if ( is_multisite() ) {
						$level = ( isset( $_POST['network_level'] ) ) ? trim( $_POST['network_level'] ) : '';
					}
					if ( ! isset( $json['breeze_file_settings'] ) && ! isset( $json['breeze_preload_settings'] ) ) {
						$action = self::replace_options_old_to_new( $json, $level );
					} else {
						$action = $this->replace_options( $json, $level );
					}

					if ( false === $action ) {
						wp_send_json_error( new WP_Error( 'option_read', __( 'Could not read the options from the provided JSON file', 'breeze' ) ) );
					} elseif ( true !== $action ) {
						wp_send_json_error( new WP_Error( 'error_meta', $action ) );
					}

					wp_send_json_success( __( "Settings imported successfully. \nPage will reload", 'breeze' ) );
				}
				wp_send_json_error( new WP_Error( 'incorrect_content', __( 'The JSON content is not valid', 'breeze' ) ) );

			} else {
				wp_send_json_error( new WP_Error( 'invalid_file', __( 'The JSON file is not valid', 'breeze' ) . ': ' . json_last_error_msg() ) );

			}
		} else {
			wp_send_json_error( new WP_Error( 'file_not_set', __( 'The JSON file is missing', 'breeze' ) ) );
		}
	}

	/**
	 * Export settings using interface in back-end.
	 *
	 * @since 1.2.2
	 * @access public
	 */
	public function export_json_settings() {
		breeze_is_restricted_access();
		$level = '';
		if ( is_multisite() ) {
			$level = ( isset( $_GET['network_level'] ) ) ? $_GET['network_level'] : '';
		}
		$response = self::read_options( $level );

		header( 'Content-disposition: attachment; filename=breeze-export-settings-' . date_i18n( 'd-m-Y' ) . '.json' );
		header( 'Content-type: application/json' );

		wp_send_json( $response );
	}

	/**
	 * Reading all the options and return as array.
	 *
	 * @param string $level empty for single site, network for root multisite, numeric for subside ID.
	 *
	 * @return array
	 * @since 1.2.2
	 * @access public
	 * @static
	 */
	public static function read_options( $level = '' ) {
		$export = array();
		// For multisite
		if ( is_multisite() ) {
			// If this export is made from network admin
			if ( 'network' === $level ) {
				$breeze_basic_settings     = get_site_option( 'breeze_basic_settings' );
				$breeze_advanced_settings  = get_site_option( 'breeze_advanced_settings' );
				$breeze_heartbeat_settings = get_site_option( 'breeze_heartbeat_settings' );
				$breeze_cdn_integration    = get_site_option( 'breeze_cdn_integration' );
				$breeze_varnish_cache      = get_site_option( 'breeze_varnish_cache' );
				$breeze_file_settings      = get_site_option( 'breeze_file_settings' );
				$breeze_preload_settings   = get_site_option( 'breeze_preload_settings' );

				// Extra options
				$breeze_first_install         = get_site_option( 'breeze_first_install' );
				$breeze_advanced_settings_120 = get_site_option( 'breeze_advanced_settings_120' );
			} else { // if this export is made from sub-site.
				$network_id                = (int) $level;
				$breeze_basic_settings     = get_blog_option( $network_id, 'breeze_basic_settings' );
				$breeze_advanced_settings  = get_blog_option( $network_id, 'breeze_advanced_settings' );
				$breeze_heartbeat_settings = get_blog_option( $network_id, 'breeze_heartbeat_settings' );
				$breeze_cdn_integration    = get_blog_option( $network_id, 'breeze_cdn_integration' );
				$breeze_varnish_cache      = get_blog_option( $network_id, 'breeze_varnish_cache' );
				$breeze_file_settings      = get_blog_option( $network_id, 'breeze_file_settings' );
				$breeze_preload_settings   = get_blog_option( $network_id, 'breeze_preload_settings' );

				// Extra options
				$breeze_first_install         = get_blog_option( $network_id, 'breeze_first_install' );
				$breeze_inherit_settings      = get_blog_option( $network_id, 'breeze_inherit_settings' );
				$breeze_ecommerce_detect      = get_blog_option( $network_id, 'breeze_ecommerce_detect' );
				$breeze_advanced_settings_120 = get_blog_option( $network_id, 'breeze_advanced_settings_120' );
			}
		} else { // If WP is single site.
			$breeze_basic_settings     = get_option( 'breeze_basic_settings' );
			$breeze_advanced_settings  = get_option( 'breeze_advanced_settings' );
			$breeze_heartbeat_settings = get_option( 'breeze_heartbeat_settings' );
			$breeze_cdn_integration    = get_option( 'breeze_cdn_integration' );
			$breeze_varnish_cache      = get_option( 'breeze_varnish_cache' );
			$breeze_file_settings      = get_option( 'breeze_file_settings' );
			$breeze_preload_settings   = get_option( 'breeze_preload_settings' );

			// Extra options
			$breeze_first_install         = get_option( 'breeze_first_install' );
			$breeze_ecommerce_detect      = get_option( 'breeze_ecommerce_detect' );
			$breeze_advanced_settings_120 = get_option( 'breeze_advanced_settings_120' );
		}

		$export['breeze_basic_settings']     = $breeze_basic_settings;
		$export['breeze_advanced_settings']  = $breeze_advanced_settings;
		$export['breeze_heartbeat_settings'] = $breeze_heartbeat_settings;
		$export['breeze_cdn_integration']    = $breeze_cdn_integration;
		$export['breeze_varnish_cache']      = $breeze_varnish_cache;

		// Extra options
		if ( isset( $breeze_first_install ) ) {
			$export['breeze_first_install'] = $breeze_first_install;
		}
		if ( isset( $breeze_inherit_settings ) ) {
			$export['breeze_inherit_settings'] = $breeze_inherit_settings;
		}
		if ( isset( $breeze_preload_settings ) ) {
			$export['breeze_preload_settings'] = $breeze_preload_settings;
		}
		if ( isset( $breeze_file_settings ) ) {
			$export['breeze_file_settings'] = $breeze_file_settings;
		}
		if ( isset( $breeze_ecommerce_detect ) ) {
			$export['breeze_ecommerce_detect'] = $breeze_ecommerce_detect;
		}
		if ( isset( $breeze_advanced_settings_120 ) ) {
			$export['breeze_advanced_settings_120'] = $breeze_advanced_settings_120;
		}

		return $export;
	}

	/**
	 * Import settings using interface in back-end.
	 *
	 * @param array  $options The array with options from import action.
	 * @param string $level empty for single site, network for root multisite, numeric for subside ID.
	 *
	 * @return bool|string
	 *
	 * @access public
	 * @since 1.2.2
	 */
	public function replace_options( $options = array(), $level = '' ) {
		if ( empty( $options ) ) {
			return false;
		}

		$message = '';
		// For multisite
		if ( is_multisite() ) {
			// If this export is made from network admin
			if ( 'network' === $level ) {
				foreach ( $options as $meta_key => $meta_value ) {
					if ( false !== strpos( $meta_key, 'breeze_' ) ) {
						if ( 'breeze_cdn_integration' === $meta_key ) {
							$meta_value = $this->breeze_sanitize_imported_settings( $meta_value );
						}
						update_site_option( $meta_key, $meta_value );
					} else {
						// $meta_key was not imported
						$message .= $meta_key . ' ' . __( 'was not imported', 'breeze' ) . '<br/>';
					}
				}

				Breeze_ConfigCache::factory()->write_config_cache( true );
			} else {

				$blog_id = absint( $level );
				foreach ( $options as $meta_key => $meta_value ) {

					if ( false !== strpos( $meta_key, 'breeze_' ) ) {
						if ( 'breeze_cdn_integration' === $meta_key ) {
							$meta_value = $this->breeze_sanitize_imported_settings( $meta_value );
						}
						update_blog_option( $blog_id, $meta_key, $meta_value );
					} else {
						// $meta_key was not imported
						$message .= $meta_key . ' ' . __( 'was not imported', 'breeze' ) . '<br/>';
					}
				}

				Breeze_ConfigCache::factory()->write_config_cache();
			}
		} else {

			foreach ( $options as $meta_key => $meta_value ) {
				if ( false !== strpos( $meta_key, 'breeze_' ) ) {
					if ( 'breeze_cdn_integration' === $meta_key ) {
						$meta_value = $this->breeze_sanitize_imported_settings( $meta_value );
					}
					update_option( $meta_key, $meta_value );
				} else {
					// $meta_key was not imported
					$message .= $meta_key . ' ' . __( 'was not imported', 'breeze' ) . '<br/>';
				}
			}
			Breeze_ConfigCache::factory()->write_config_cache();
		}

		do_action( 'breeze_clear_all_cache' );

		if ( ! empty( $message ) ) {
			return $message;
		}

		return true;
	}

	public function breeze_sanitize_imported_settings( $settings ) {

		foreach ( $settings as $name => $value ) {
			if ( is_array( $value ) ) {
				// If the value is an array, recursively sanitize it.
				$settings[ $name ] = $this->breeze_sanitize_imported_settings( $value );
			} else {
				// If the value is not an array, sanitize the value.
				$settings[ $name ] = sanitize_text_field( $value );
			}
		}

		return $settings;
	}

	/**
	 * Import settings using WP-CLI in terminal.
	 *
	 * @param array  $options The array with options from import action.
	 * @param string $level empty for single site, network for root multisite, numeric for subside ID.
	 *
	 * @return bool|string
	 * @static
	 * @since 1.2.2
	 */
	public static function replace_options_cli( $options = array(), $level = '' ) {
		if ( empty( $options ) ) {
			return false;
		}

		// For multisite
		if ( is_multisite() ) {
			WP_CLI::line( 'The WordPress install is multisite!' );
			// If this export is made from network admin
			if ( 'network' === $level ) {

				WP_CLI::line( WP_CLI::colorize( '%GUpdating%n %Mnetwork%n options' ) );

				foreach ( $options as $meta_key => $meta_value ) {

					// Validate options.
					$meta_value = self::validate_option_group( $meta_value, $meta_key );

					if ( false !== strpos( $meta_key, 'breeze_' ) ) {
						update_site_option( $meta_key, $meta_value );
						WP_CLI::line( $meta_key . ' - ' . WP_CLI::colorize( '%Yimported%n' ) );
					} else {
						WP_CLI::line( $meta_key . ' - ' . WP_CLI::colorize( '%Rwas not imported%n' ) );
					}
				}

				Breeze_ConfigCache::factory()->write_config_cache( true );

			} else {

				$is_blog  = get_blog_details( $level );
				$site_url = $is_blog->siteurl;

				WP_CLI::line( WP_CLI::colorize( '%GUpdating%n %M' . $site_url . '%n options' ) );
				$blog_id = $level;

				switch_to_blog( $blog_id );

				foreach ( $options as $meta_key => $meta_value ) {

					// Validate options.
					$meta_value = self::validate_option_group( $meta_value, $meta_key );

					if ( false !== strpos( $meta_key, 'breeze_' ) ) {
						self::ttl_exception( $meta_key, $meta_value );
						update_blog_option( $blog_id, $meta_key, $meta_value );
						WP_CLI::line( $meta_key . ' - ' . WP_CLI::colorize( '%Yimported%n' ) );
					} else {
						WP_CLI::line( $meta_key . ' - ' . WP_CLI::colorize( '%Rwas not imported%n' ) );
					}
				}

				Breeze_ConfigCache::factory()->write_config_cache();

				restore_current_blog();
			}
		} else {
			WP_CLI::line( WP_CLI::colorize( '%GUpdating%n %MBreeze%n options' ) );
			foreach ( $options as $meta_key => $meta_value ) {

				// Validate options.
				$meta_value = self::validate_option_group( $meta_value, $meta_key );

				if ( false !== strpos( $meta_key, 'breeze_' ) ) {
					update_option( $meta_key, $meta_value );
					WP_CLI::line( $meta_key . ' - ' . WP_CLI::colorize( '%Yimported%n' ) );
				} else {
					WP_CLI::line( $meta_key . ' - ' . WP_CLI::colorize( '%Rwas not imported%n' ) );
				}
			}

			Breeze_ConfigCache::factory()->write_config_cache();
		}

		do_action( 'breeze_clear_all_cache' );

		return true;
	}

	public static function ttl_exception( $meta_key, $meta_value ) {
		if ( 'breeze_basic_settings' === $meta_key ) {
			if ( ! array_key_exists( 'breeze-b-ttl', $meta_value ) && array_key_exists( 'breeze-ttl', $meta_value ) ) {
				$meta_value['breeze-b-ttl'] = $meta_value['breeze-ttl'];
			}
		}

		return $meta_value;
	}

	/**
	 * Import settings using interface in back-end.
	 * Migrate old settings to the new format created in v2.0.0.
	 *
	 * @param array  $options_imported The array with options from import action.
	 * @param string $level empty for single site, network for root multisite, numeric for subside ID.
	 * @param bool   $show_cli_messages Display CLI messages in the terminal when using import by WP-CLI.
	 *
	 * @return bool
	 *
	 * @access private
	 * @since 2.0.0
	 */
	public static function replace_options_old_to_new( $options_imported = array(), $level = '', $show_cli_messages = false ) {
		if ( empty( $options_imported ) ) {
			return false;
		}

		$options = array();
		if ( true === $show_cli_messages ) {
			WP_CLI::line( 'Preparing JSON options...' );
		}

		foreach ( $options_imported as $option_name => $option_values ) {
			if ( ! is_null( $option_values ) ) {
				if ( is_array( $option_values ) ) {
					foreach ( $option_values as $val_key => $val_value ) {
						$options[ $val_key ] = self::validate_json_entry( $val_value, $val_key );
					}
				} else {
					$options[ $option_name ] = self::validate_json_entry( $option_values, $option_name );
				}
			}
		}

		if ( ! empty( $options ) ) {
			$basic = array(
				'breeze-active'           => ( isset( $options['breeze-active'] ) ? $options['breeze-active'] : '1' ),
				'breeze-mobile-separate'  => ( isset( $options['breeze-mobile-separate'] ) ? $options['breeze-mobile-separate'] : '1' ),
				'breeze-cross-origin'     => ( isset( $options['breeze-cross-origin'] ) ? $options['breeze-cross-origin'] : '0' ),
				'breeze-disable-admin'    => ( isset( $options['breeze-disable-admin'] ) ? $options['breeze-disable-admin'] : array() ),
				'breeze-gzip-compression' => ( isset( $options['breeze-gzip-compression'] ) ? $options['breeze-gzip-compression'] : '1' ),
				'breeze-browser-cache'    => ( isset( $options['breeze-browser-cache'] ) ? $options['breeze-browser-cache'] : '1' ),
				'breeze-lazy-load'        => ( isset( $options['breeze-lazy-load'] ) ? $options['breeze-lazy-load'] : '0' ),
				'breeze-lazy-load-native' => ( isset( $options['breeze-lazy-load-native'] ) ? $options['breeze-lazy-load-native'] : '0' ),
				'breeze-desktop-cache'    => '1',
				'breeze-mobile-cache'     => '1',
				'breeze-display-clean'    => '1',
				'breeze-ttl'              => ( isset( $options['breeze-ttl'] ) ? $options['breeze-ttl'] : 1440 ),
			);

			$is_minification_js        = ( isset( $options['breeze-minify-js'] ) ? $options['breeze-minify-js'] : '0' );
			$is_inline_minification_js = ( isset( $options['breeze-include-inline-js'] ) ? $options['breeze-include-inline-js'] : '0' );
			$is_group_js               = ( isset( $options['breeze-group-js'] ) ? $options['breeze-group-js'] : '0' );

			if ( 0 === absint( $is_minification_js ) || 0 === absint( $is_inline_minification_js ) ) {
				// $is_group_js = '0';
			}

			$file = array(
				'breeze-minify-html'        => ( isset( $options['breeze-minify-html'] ) ? $options['breeze-minify-html'] : '0' ),
				// --
				'breeze-minify-css'         => ( isset( $options['breeze-minify-css'] ) ? $options['breeze-minify-css'] : '0' ),
				'breeze-font-display-swap'  => ( isset( $options['breeze-font-display-swap'] ) ? $options['breeze-font-display-swap'] : '0' ),
				'breeze-group-css'          => ( isset( $options['breeze-group-css'] ) ? $options['breeze-group-css'] : '0' ),
				'breeze-exclude-css'        => ( isset( $options['breeze-exclude-css'] ) ? $options['breeze-exclude-css'] : array() ),
				'breeze-include-inline-css' => ( isset( $options['breeze-include-inline-css'] ) ? $options['breeze-include-inline-css'] : '0' ),
				// --
				'breeze-minify-js'          => $is_minification_js,
				'breeze-group-js'           => $is_group_js,
				'breeze-include-inline-js'  => $is_inline_minification_js,
				'breeze-exclude-js'         => ( isset( $options['breeze-exclude-js'] ) ? $options['breeze-exclude-js'] : array() ),
				'breeze-move-to-footer-js'  => ( isset( $options['breeze-move-to-footer-js'] ) ? $options['breeze-move-to-footer-js'] : array() ),
				'breeze-defer-js'           => ( isset( $options['breeze-defer-js'] ) ? $options['breeze-defer-js'] : array() ),
				'breeze-enable-js-delay'    => ( isset( $options['breeze-enable-js-delay'] ) ? $options['breeze-enable-js-delay'] : '0' ),
				'breeze-delay-js-scripts'   => ( isset( $options['breeze-delay-js-scripts'] ) ? $options['breeze-delay-js-scripts'] : array() ),
				'no-breeze-no-delay-js'     => ( isset( $options['no-breeze-no-delay-js'] ) ? $options['no-breeze-no-delay-js'] : array() ),
				'breeze-delay-all-js'       => ( isset( $options['breeze-delay-all-js'] ) ? $options['breeze-delay-all-js'] : '0' ),

			);

			$preload = array(
				'breeze-preload-fonts' => ( isset( $options['breeze-preload-fonts'] ) ? $options['breeze-preload-fonts'] : array() ),
				'breeze-preload-links' => ( isset( $options['breeze-preload-links'] ) ? $options['breeze-preload-links'] : '1' ),
			);

			$advanced = array(
				'breeze-exclude-urls'                  => ( isset( $options['breeze-exclude-urls'] ) ? $options['breeze-exclude-urls'] : array() ),
				'cached-query-strings'                 => ( isset( $options['cached-query-strings'] ) ? $options['cached-query-strings'] : array() ),
				'breeze-wp-emoji'                      => ( isset( $options['breeze-wp-emoji'] ) ? $options['breeze-wp-emoji'] : '0' ),
				'breeze-store-googlefonts-locally'     => ( isset( $options['breeze-store-googlefonts-locally'] ) ? $options['breeze-store-googlefonts-locally'] : '0' ),
				'breeze-store-googleanalytics-locally' => ( isset( $options['breeze-store-googleanalytics-locally'] ) ? $options['breeze-store-googleanalytics-locally'] : '0' ),
				'breeze-store-facebookpixel-locally'   => ( isset( $options['breeze-store-facebookpixel-locally'] ) ? $options['breeze-store-facebookpixel-locally'] : '0' ),
				'breeze-store-gravatars-locally'       => ( isset( $options['breeze-store-gravatars-locally'] ) ? $options['breeze-store-gravatars-locally'] : '0' ),
				'breeze-enable-api'                    => ( isset( $options['breeze-enable-api'] ) ? $options['breeze-enable-api'] : '0' ),
				'breeze-secure-api'                    => ( isset( $options['breeze-secure-api'] ) ? $options['breeze-secure-api'] : '0' ),
				'breeze-api-token'                     => ( isset( $options['breeze-api-token'] ) ? $options['breeze-api-token'] : '' ),

			);

			$heartbeat = array(
				'breeze-control-heartbeat'  => ( isset( $options['breeze-control-heartbeat'] ) ? $options['breeze-control-heartbeat'] : '0' ),
				'breeze-heartbeat-front'    => ( isset( $options['breeze-heartbeat-front'] ) ? $options['breeze-heartbeat-front'] : '' ),
				'breeze-heartbeat-postedit' => ( isset( $options['breeze-heartbeat-postedit'] ) ? $options['breeze-heartbeat-postedit'] : '' ),
				'breeze-heartbeat-backend'  => ( isset( $options['breeze-heartbeat-backend'] ) ? $options['breeze-heartbeat-backend'] : '' ),
			);

			$wp_content = substr( WP_CONTENT_DIR, strlen( ABSPATH ) );
			$cdn        = array(
				'cdn-active'          => ( isset( $options['cdn-active'] ) ? $options['cdn-active'] : '0' ),
				'cdn-relative-path'   => ( isset( $options['cdn-relative-path'] ) ? $options['cdn-relative-path'] : '1' ),
				'cdn-url'             => ( isset( $options['cdn-url'] ) ? $options['cdn-url'] : '' ),
				'cdn-content'         => ( isset( $options['cdn-content'] ) ? $options['cdn-content'] : array(
					'wp-includes',
					$wp_content,
				) ),
				'cdn-exclude-content' => ( isset( $options['cdn-exclude-content'] ) ? $options['cdn-exclude-content'] : array( '.php' ) ),
			);

			$varnish = array(
				'auto-purge-varnish'       => ( isset( $options['auto-purge-varnish'] ) ? $options['auto-purge-varnish'] : '1' ),
				'breeze-varnish-server-ip' => ( isset( $options['breeze-varnish-server-ip'] ) ? $options['breeze-varnish-server-ip'] : '127.0.0.1' ),
			);

			if ( is_multisite() ) {
				if ( true === $show_cli_messages ) {
					WP_CLI::line( 'The WordPress install is multisite!' );
				}
				if ( 'network' === $level ) {
					if ( true === $show_cli_messages ) {
						WP_CLI::line( WP_CLI::colorize( '%GUpdating%n %Mnetwork%n options' ) );

						WP_CLI::line( ' breeze_basic_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_file_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_preload_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_advanced_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_cdn_integration - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_varnish_cache - ' . WP_CLI::colorize( '%Yimported%n' ) );
					}
					update_site_option( 'breeze_basic_settings', $basic );
					update_site_option( 'breeze_file_settings', $file );
					update_site_option( 'breeze_preload_settings', $preload );
					update_site_option( 'breeze_advanced_settings', $advanced );
					update_site_option( 'breeze_heartbeat_settings', $heartbeat );
					update_site_option( 'breeze_cdn_integration', $cdn );
					update_site_option( 'breeze_varnish_cache', $varnish );

					Breeze_ConfigCache::factory()->write_config_cache( true );
				} else {
					$blog_id = absint( $level );
					switch_to_blog( $blog_id );

					if ( true === $show_cli_messages ) {
						$is_blog  = get_blog_details( $level );
						$site_url = $is_blog->siteurl;
						WP_CLI::line( WP_CLI::colorize( '%GUpdating%n %M' . $site_url . '%n options' ) );

						WP_CLI::line( ' breeze_basic_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_file_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_preload_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_advanced_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_cdn_integration - ' . WP_CLI::colorize( '%Yimported%n' ) );
						WP_CLI::line( ' breeze_varnish_cache - ' . WP_CLI::colorize( '%Yimported%n' ) );
					}

					update_blog_option( $blog_id, 'breeze_basic_settings', $basic );
					update_blog_option( $blog_id, 'breeze_file_settings', $file );
					update_blog_option( $blog_id, 'breeze_preload_settings', $preload );
					update_blog_option( $blog_id, 'breeze_advanced_settings', $advanced );
					update_blog_option( $blog_id, 'breeze_heartbeat_settings', $heartbeat );
					update_blog_option( $blog_id, 'breeze_cdn_integration', $cdn );
					update_blog_option( $blog_id, 'breeze_varnish_cache', $varnish );

					Breeze_ConfigCache::factory()->write_config_cache();
					restore_current_blog();
				}
			} else {
				if ( true === $show_cli_messages ) {
					WP_CLI::line( WP_CLI::colorize( '%GUpdating%n %MBreeze%n options' ) );

					WP_CLI::line( ' breeze_basic_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
					WP_CLI::line( ' breeze_file_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
					WP_CLI::line( ' breeze_preload_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
					WP_CLI::line( ' breeze_advanced_settings - ' . WP_CLI::colorize( '%Yimported%n' ) );
					WP_CLI::line( ' breeze_cdn_integration - ' . WP_CLI::colorize( '%Yimported%n' ) );
					WP_CLI::line( ' breeze_varnish_cache - ' . WP_CLI::colorize( '%Yimported%n' ) );
				}
				update_option( 'breeze_basic_settings', $basic );
				update_option( 'breeze_file_settings', $file );
				update_option( 'breeze_preload_settings', $preload );
				update_option( 'breeze_advanced_settings', $advanced );
				update_option( 'breeze_heartbeat_settings', $heartbeat );
				update_option( 'breeze_cdn_integration', $cdn );
				update_option( 'breeze_varnish_cache', $varnish );

				Breeze_ConfigCache::factory()->write_config_cache();
			}

			do_action( 'breeze_clear_all_cache' );

			return true;
		}
		if ( true === $show_cli_messages ) {
			WP_CLI::line( WP_CLI::colorize( '%RJSON values are not valid%n' ) );
		}

		return false;
	}

	/**
	 * Validates the options making sure there values are
	 * the correct format for each option.
	 *
	 * @param mixed  $value Imported option value.
	 * @param string $option Breeze option name.
	 *
	 * @return array|mixed|string|void|null
	 * @access private
	 * @since 2.0.0
	 * @static
	 */
	private static function validate_json_entry( $value, $option = '' ) {
		$heartbeat_options = array(
			'',
			'120',
			'180',
			'240',
			'300',
			'disable',
		);
		if (
			'breeze-heartbeat-front' === $option ||
			'breeze-heartbeat-postedit' === $option ||
			'breeze-heartbeat-backend' === $option
		) {
			$value = (string) $value;
			if ( in_array( $value, $heartbeat_options, true ) ) {
				return $value;
			} else {
				return '';
			}
		}

		/**
		 * Treat options that are not checkbox or array.
		 */

		if ( 'breeze_advanced_settings_120' === $option ) {
			if ( 'no' !== $value && 'yes' !== $value ) {
				return 'no';
			}

			return $value;
		}

		if ( 'breeze_ecommerce_detect' === $option ) {
			if ( ! is_bool( $value ) ) {
				return false;
			}

			return $value;
		}

		if ( 'breeze-varnish-server-ip' === $option ) {
			if ( empty( $value ) ) {
				return '127.0.0.1';
			}

			return $value;
		}

		if ( 'cdn-url' === $option ) {
			if ( empty( $value ) ) {
				return '';
			}

			return $value;
		}

		if ( 'breeze_first_install' === $option ) {
			if ( 'no' !== $value && 'yes' !== $value ) {
				return 'no';
			}

			return $value;
		}

		if ( 'breeze-ttl' === $option ) {
			if ( ! is_numeric( $value ) ) {
				return 1440;
			}

			return $value;
		}

		if ( 'breeze-b-ttl' === $option ) {
			if ( ! is_numeric( $value ) ) {
				return 1440;
			}

			return $value;
		}

		/**
		 * Validate all the checkboxes.
		 * Include the default values.
		 */
		$checkboxes = array(
			'breeze-active'                        => '1',
			'breeze-mobile-separate'               => '1',
			'breeze-cross-origin'                  => '0',
			'breeze-gzip-compression'              => '1',
			'breeze-browser-cache'                 => '1',
			'breeze-lazy-load'                     => '0',
			'breeze-lazy-load-native'              => '0',
			'breeze-lazy-load-iframes'             => '0',
			'breeze-lazy-load-videos'              => '0',
			'breeze-desktop-cache'                 => '1',
			'breeze-mobile-cache'                  => '1',
			'breeze-display-clean'                 => '1',
			'breeze-minify-html'                   => '0',
			'breeze-minify-css'                    => '0',
			'breeze-font-display-swap'             => '0',
			'breeze-group-css'                     => '0',
			'breeze-include-inline-css'            => '0',
			'breeze-minify-js'                     => '0',
			'breeze-group-js'                      => '0',
			'breeze-include-inline-js'             => '0',
			'breeze-enable-js-delay'               => '0',
			'breeze-preload-links'                 => '1',
			'breeze-wp-emoji'                      => '0',
			'breeze-enable-api'                    => '0',
			'breeze-secure-api'                    => '0',
			'cdn-active'                           => '0',
			'cdn-relative-path'                    => '1',
			'auto-purge-varnish'                   => '1',
			'breeze_inherit_settings'              => '0',
			'breeze-control-heartbeat'             => '0',
			'breeze-delay-all-js'                  => '0',
			'breeze-store-googlefonts-locally'     => '0',
			'breeze-store-googleanalytics-locally' => '0',
			'breeze-store-facebookpixel-locally'   => '0',
			'breeze-store-gravatars-locally'       => '0',
		);

		if ( array_key_exists( $option, $checkboxes ) ) {
			if ( '1' === $value || '0' === $value ) {
				return $value;
			} else {

				return $checkboxes[ $option ];
			}
		}

		$wp_content         = substr( WP_CONTENT_DIR, strlen( ABSPATH ) );
		$all_user_roles     = breeze_all_wp_user_roles();
		$active_cache_users = array();
		foreach ( $all_user_roles as $usr_role ) {
			$active_cache_users[ $usr_role ] = 0;

		}

		/**
		 * Validate all the options that should have array values.
		 */
		$array_list = array(
			'breeze-disable-admin'     => $active_cache_users,
			'breeze-exclude-css'       => array(),
			'breeze-exclude-js'        => array(),
			'breeze-move-to-footer-js' => array(),
			'breeze-defer-js'          => array(),
			'breeze-delay-js-scripts'  => array(
				'gtag',
				'document.write',
				'html5.js',
				'show_ads.js',
				'google_ad',
				'blogcatalog.com/w',
				'tweetmeme.com/i',
				'mybloglog.com/',
				'histats.com/js',
				'ads.smowtion.com/ad.js',
				'statcounter.com/counter/counter.js',
				'widgets.amung.us',
				'ws.amazon.com/widgets',
				'media.fastclick.net',
				'/ads/',
				'comment-form-quicktags/quicktags.php',
				'edToolbar',
				'intensedebate.com',
				'scripts.chitika.net/',
				'_gaq.push',
				'jotform.com/',
				'admin-bar.min.js',
				'GoogleAnalyticsObject',
				'plupload.full.min.js',
				'syntaxhighlighter',
				'adsbygoogle',
				'gist.github.com',
				'_stq',
				'nonce',
				'post_id',
				'data-noptimize',
				'googletagmanager',
			),
			'no-breeze-no-delay-js'    => array(),
			'breeze-preload-fonts'     => array(),
			'breeze-exclude-urls'      => array(),
			'cached-query-strings'     => array(),
			'cdn-content'              => array( 'wp-includes', $wp_content ),
			'cdn-exclude-content'      => array( '.php' ),
			'breeze-prefetch-urls'     => array(),
		);

		if ( array_key_exists( $option, $array_list ) ) {
			if ( is_array( $value ) ) {
				return $value;
			} else {

				return $array_list[ $option ];
			}
		}

		return '0';
	}

	/**
	 * Validate all the options in the group given.
	 *
	 * @param $option_group
	 * @param $group_name
	 *
	 * @return array
	 * @access private
	 * @since 2.0.0
	 * @static
	 */
	private static function validate_option_group( $option_group, $group_name ) {
		if ( ! array( $option_group ) ) {
			return array();
		}

		$changed_options = $option_group;

		if ( ! empty( $option_group ) && is_array( $option_group ) ) {

			foreach ( $option_group as $option_name => $option_value ) {
				$changed_options[ $option_name ] = self::validate_json_entry( $option_value, $option_name );
			}

			return $changed_options;
		} else {

			return self::validate_json_entry( $option_group, $group_name );
		}
	}
}

new Breeze_Settings_Import_Export();
