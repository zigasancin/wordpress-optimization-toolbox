<?php
/**
 * @copyright 2017  Cloudways  https://www.cloudways.com
 *
 *  Original development of this plugin by JoomUnited https://www.joomunited.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
// Based on some work of simple-cache
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Breeze_ConfigCache {

	/**
	 * Create advanced-cache file
	 */
	public function write() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$file = trailingslashit( WP_CONTENT_DIR ) . '/advanced-cache.php';

		// Create array of configuration files and their corresponding sites' URLs.
		$cache_configs = array(
			'breeze-config' => array(),
		);
		if ( is_multisite() ) {
			/**
			 * This is a multisite install, loop through all subsites.
			 * Use the given filter to define the number of subsites
			 * to fetch default is 100.
			 */
			$blogs = get_sites(
				array(
					'fields' => 'ids',
					'number' => apply_filters( 'breeze_subsites_fetch_count_modify', 100 ),
				)
			);

			foreach ( $blogs as $blog_id ) {
				switch_to_blog( $blog_id );

				// if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-active' ) ) ) {
				$inherit_option = get_blog_option( $blog_id, 'breeze_inherit_settings', '0' );
				$inherit_option = filter_var( $inherit_option, FILTER_VALIDATE_BOOLEAN );
				if ( false === $inherit_option ) {
					// Site uses own (custom) configuration.
					$cache_configs[ "breeze-config-{$blog_id}" ] = preg_replace( '(^https?://)', '', site_url() );
				} else {
					// Site uses global configuration.
					$cache_configs['breeze-config'][ $blog_id ] = preg_replace( '(^https?://)', '', site_url() );
				}
				// }
				restore_current_blog();
			}
		} elseif ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-active' ) ) ) {

				$cache_configs['breeze-config'][] = preg_replace( '(^https?://)', '', site_url() );
		}

		if ( empty( $cache_configs ) || ( 1 === count( $cache_configs ) && empty( $cache_configs['breeze-config'] ) ) ) {
			// No sites with caching enabled.
			$this->clean_config();

			return;
		} else {
			$file_string = '<?php ' .
							"\n\r" . 'defined( \'ABSPATH\' ) || exit;' .
							"\n\r" . 'define( \'BREEZE_ADVANCED_CACHE\', true );' .
							"\n\r" . 'if ( is_admin() ) { return; }' .
							"\n\r" . 'if ( ! @file_exists( \'' . BREEZE_PLUGIN_DIR . 'breeze.php\' ) ) { return; }';
		}

		if ( ! is_multisite() && 1 === count( $cache_configs ) ) {
			// Only 1 config file available.
			$blog_file    = trailingslashit( WP_CONTENT_DIR ) . 'breeze-config/breeze-config.php';
			$file_string .= "\n\$config['config_path'] = '$blog_file';";
		} else {
			// Multiple configuration files, load appropriate one by comparing URLs.
			$file_string .= "\n\r" . '$domain = strtolower( stripslashes( $_SERVER[\'HTTP_HOST\'] ) );' .
							"\n" . 'if ( substr( $domain, -3 ) == \':80\' ) {' .
							"\n" . '	$domain = substr( $domain, 0, -3 );' .
							"\n" . '} elseif ( substr( $domain, -4 ) == \':443\' ) {' .
							"\n" . '	$domain = substr( $domain, 0, -4 );' .
							"\n" . '}';
			if ( is_subdomain_install() ) {
				$file_string .= "\n" . '$site_url = $domain;';
			} else {
				$file_string .= <<<'FILE_STRING'

function breeze_get_current_url_formatted() {
	$domain = strtolower( stripslashes( $_SERVER['HTTP_HOST'] ) );
	if ( substr( $domain, -3 ) == ':80' ) {
		$domain = substr( $domain, 0, -3 );
	} elseif ( substr( $domain, -4 ) == ':443' ) {
		$domain = substr( $domain, 0, -4 );
	}
	$request_uri = stripslashes( $_SERVER['REQUEST_URI'] );
	$path        = explode( '?', $request_uri, 2 )[0];
	$full_url    = '//' . $domain . $path;
	return rtrim( $full_url, '/' );
}
$site_url = breeze_get_current_url_formatted();

FILE_STRING;

			}

			// Create conditional blocks for each site.
			$file_string .= "\n" . 'function breeze_fetch_configuration_data( $site_url ) {';
			$file_string .= "\n\t" . '$config = array();';
			$file_string .= "\n\t" . 'switch ( $site_url ) {';
			foreach ( array_reverse( $cache_configs ) as $filename => $urls ) {
				$blog_file = trailingslashit( WP_CONTENT_DIR ) . 'breeze-config/' . $filename . '.php';

				if ( ! is_array( $urls ) ) {
					$urls = array( $urls );
				}

				if ( empty( $urls ) ) {
					continue;
				}

				foreach ( $urls as $the_blog_id => $site_url ) {
					$file_string .= "\n\tcase '$site_url':";

					if ( is_multisite() ) {
						if ( empty( $the_blog_id ) ) {
							$e = explode( '-', $filename );
							if ( isset( $e[2] ) ) {
								$the_blog_id = (int) $e[2];
							}
						}

						$define_blog_identity = "\n\t\t\$config['blog_id']={$the_blog_id};";
						$file_string         .= "\n\t\t\$config['config_path'] = '$blog_file';" . $define_blog_identity . "\n\t\tbreak;";
					} else {
						$file_string .= "\n\t\t\$config['config_path'] = '$blog_file';" . "\n\t\tbreak;";
					}
				}
			}

			$file_string .= "\n\t}";
			$file_string .= "\n\t" . 'return $config;';
			$file_string .= "\n}";
			$file_string .= <<<'FILE_STRING'

function breeze_get_subsite_from_url( $url ) {
	$parsed_url    = parse_url( $url );
	$domain        = strtolower( $parsed_url['host'] );
	$path          = trim( $parsed_url['path'] ?? '', '/' );
	$path_segments = array();
	if ( ! empty( $path ) ) {
		$path_segments = explode( '/', $path );
	}
	if ( ':80' === substr( $domain, -3 ) ) {
		$domain = substr( $domain, 0, -3 );
	} elseif ( ':443' === substr( $domain, -4 ) ) {
		$domain = substr( $domain, 0, -4 );
	}
	$site_url = '';
	if ( count( $path_segments ) >= 2 ) {
		$site_url       = $domain . '/' . $path_segments[0] . '/' . $path_segments[1];
		$subsite_config = breeze_fetch_configuration_data( $site_url );

		if ( $subsite_config ) {
			return $subsite_config;
		}
	}
	if ( count( $path_segments ) >= 1 ) {
		$site_url       = $domain . '/' . $path_segments[0];
		$subsite_config = breeze_fetch_configuration_data( $site_url );
		if ( $subsite_config ) {
			return $subsite_config;
		}
	}
	$site_url       = $domain;
	$subsite_config = breeze_fetch_configuration_data( $site_url );
	if ( $subsite_config ) {
		return $subsite_config;
	}

	return '';
}

FILE_STRING;

			if ( is_subdomain_install() ) {
				$file_string .= "\n" . '$config = breeze_fetch_configuration_data( $site_url );';
			} else {
				$file_string .= <<<'FILE_STRING'

$config = breeze_get_subsite_from_url( $site_url );
				
FILE_STRING;
			}

			$file_string .= "\n" . 'if ( ';
			$file_string .= "\n" . ' empty( $config ) && ';
			$file_string .= "\n" . ' false === filter_var( SUBDOMAIN_INSTALL, FILTER_VALIDATE_BOOLEAN ) && ';
			$file_string .= "\n" . ' true === filter_var( MULTISITE, FILTER_VALIDATE_BOOLEAN ) && ';
			$file_string .= "\n" . ' false === strpos( $site_url, "robots.txt") && ';
			$file_string .= "\n" . ' false === strpos( $site_url, "favicon.ico") && ';
			$file_string .= "\n" . ' false === strpos( $site_url, "wp-cron.php")';
			$file_string .= "\n" . ' ) {';
			$file_string .= "\n\t" . '$xplode = explode( "/", $site_url);';
			$file_string .= "\n\t" . 'if(isset($xplode[0])){';
			$file_string .= "\n\t\t" . '$config   = breeze_fetch_configuration_data( $domain );';
			$file_string .= "\n\t" . '}';
			$file_string .= "\n" . '}';
		}

		$file_string .= "\nif ( empty( \$config ) || ! isset( \$config['config_path'] ) || ! @file_exists( \$config['config_path'] ) ) { return; }" .
						"\n\$breeze_temp_config = include \$config['config_path'];" .
						"\nif ( isset( \$config['blog_id'] ) ) { \$breeze_temp_config['blog_id'] = \$config['blog_id']; }" .
						"\n\$GLOBALS['breeze_config'] = \$breeze_temp_config; unset( \$breeze_temp_config );" .
						"\n" . 'if ( empty( $GLOBALS[\'breeze_config\'] ) || empty( $GLOBALS[\'breeze_config\'][\'cache_options\'][\'breeze-active\'] ) ) { return; }' .
						"\n" . 'if ( @file_exists( \'' . BREEZE_PLUGIN_DIR . 'inc/cache/execute-cache.php\' ) ) {' .
						"\n" . '	include_once \'' . BREEZE_PLUGIN_DIR . 'inc/cache/execute-cache.php\';' .
						"\n" . '}' . "\n";

		return $wp_filesystem->put_contents( $file, $file_string );
	}

	/**
	 * Function write parameter to breeze-config.
	 *
	 * @param bool $create_root_config Used in multisite, to reset/create breeze-config.php file
	 */
	public static function write_config_cache( $create_root_config = false ) {
		global $wmc_settings;
		if ( true === $create_root_config ) {
			$network_id = get_current_network_id();
			$settings   = Breeze_Options_Reader::fetch_all_saved_settings( true );
			// $settings     = get_network_option( $network_id, 'breeze_basic_settings' );
			$homepage_url = network_site_url();
		} else {
			$settings     = Breeze_Options_Reader::fetch_all_saved_settings();
			$homepage_url = get_site_url();
		}

		$ecommerce_exclude_urls = array();

		$storage = array(
			'homepage'              => $homepage_url,
			'cache_options'         => $settings,
			'disable_per_adminuser' => array(),
			'exclude_url'           => array(),
		);

		if ( is_multisite() ) {
			$storage['blog_id'] = get_current_blog_id();
		}

		$storage['wp-user-roles'] = breeze_all_wp_user_roles();

		$lazy_load         = Breeze_Options_Reader::get_option_value( 'breeze-lazy-load', false, $create_root_config );
		$lazy_load_native  = Breeze_Options_Reader::get_option_value( 'breeze-lazy-load-native', false, $create_root_config );
		$preload_links     = Breeze_Options_Reader::get_option_value( 'breeze-preload-links', false, $create_root_config );
		$lazy_load_iframes = Breeze_Options_Reader::get_option_value( 'breeze-lazy-load-iframes', false, $create_root_config );
		$lazy_load_videos  = Breeze_Options_Reader::get_option_value( 'breeze-lazy-load-videos', false, $create_root_config );

		$storage['enabled-lazy-load']        = ( isset( $lazy_load ) ? $lazy_load : 0 );
		$storage['use-lazy-load-native']     = ( isset( $lazy_load_native ) ? $lazy_load_native : 0 );
		$storage['breeze-preload-links']     = ( isset( $preload_links ) ? $preload_links : 0 );
		$storage['breeze-lazy-load-iframes'] = ( isset( $lazy_load_iframes ) ? $lazy_load_iframes : 0 );
		$storage['breeze-lazy-load-videos']  = ( isset( $lazy_load_videos ) ? $lazy_load_videos : 0 );

		// CURCY - WooCommerce Multi Currency Premium.
		if (
			class_exists( 'WOOMULTI_CURRENCY' ) ||
			class_exists( 'WOOMULTI_CURRENCY_F' )
		) {
			if ( empty( $wmc_settings ) ) {
				// if $wmc_settings is empty, we will check again.
				$wmc_settings = get_option( 'woo_multi_currency_params', array() );
			}
			// if the option exists and has values.
			if ( ! empty( $wmc_settings ) ) {
				$is_enable = filter_var( $wmc_settings['enable'], FILTER_VALIDATE_BOOLEAN );
				if ( $is_enable ) {
					$session_type = 'cookie';
					$is_session   = false;
					if ( isset( $wmc_settings['use_session'] ) ) {
						$is_session = filter_var( $wmc_settings['use_session'], FILTER_VALIDATE_BOOLEAN );
					}
					if ( $is_session ) {
						$session_type = 'session';
					}
					$storage['curcy-wmc-type'] = $session_type;
				}
			}
		}

		// WOOCS - WooCommerce Currency Switcher
		$woocs_is_active = false;
		if (
			class_exists( 'WOOCS_STARTER' )
		) {
			$woocs_is_active = true;
		}

		if ( isset( $_POST['woocommerce_default_customer_address'] ) ) {
			$storage['woocommerce_geolocation_ajax'] = ( 'geolocation_ajax' === $_POST['woocommerce_default_customer_address'] ) ? 1 : 0;
		} else {
			$storage['woocommerce_geolocation_ajax'] = ( 'geolocation_ajax' === get_option( 'woocommerce_default_customer_address', '' ) ) ? 1 : 0;
		}

		// permalink_structure
		if ( is_multisite() ) {
			if ( is_network_admin() ) {
				if ( true === $woocs_is_active ) {
					$storage['woocs-store-type'] = get_site_option( 'woocs_storage', 'transient' );
				}

				unset( $storage['woocommerce_geolocation_ajax'] );
				// network oes not have this setting.
				// we save for each sub-site.
				$blogs = get_sites();
				if ( ! empty( $blogs ) ) {
					foreach ( $blogs as $blog_data ) {
						$blog_id = $blog_data->blog_id;
						switch_to_blog( $blog_id );

						$storage['woocommerce_geolocation_ajax_inherit'][ 'subsite_' . $blog_id ] = ( 'geolocation_ajax' === get_blog_option( $blog_id, 'woocommerce_default_customer_address', '' ) ) ? 1 : 0;
						$storage['permalink_structure'][ 'blog_' . $blog_id ]                     = get_blog_option( $blog_id, 'permalink_structure', '' );

						restore_current_blog();
					}
				}
			} else {
				$network_id                     = get_current_blog_id();
				$storage['permalink_structure'] = get_blog_option( $network_id, 'permalink_structure', '' );
				if ( true === $woocs_is_active ) {
					$storage['woocs-store-type'] = get_blog_option( $network_id, 'woocs_storage', 'transient' );
				}
			}
		} else {
			$storage['permalink_structure'] = get_option( 'permalink_structure', '' );
			if ( true === $woocs_is_active ) {
				$storage['woocs-store-type'] = get_option( 'woocs_storage', 'transient' );
			}
		}

		if ( class_exists( 'WooCommerce' ) ) {
			$ecommerce_exclude_urls = Breeze_Ecommerce_Cache::factory()->ecommerce_exclude_pages();
		}

		if ( class_exists( 'BuddyPress' ) ) {
			$exclude_buddyboss_pages = Breeze_Ecommerce_Cache::factory()->buddyboss_exclude_urls();

			if ( ! empty( $exclude_buddyboss_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_buddyboss_pages, $ecommerce_exclude_urls );
			}
		}

		if ( function_exists( 'EDD' ) ) {
			$exclude_edd_pages = Breeze_Ecommerce_Cache::factory()->exclude_edd_pages();

			if ( ! empty( $exclude_edd_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_edd_pages, $ecommerce_exclude_urls );
			}

			/**
			 * Remove Easy Digital Downloads Software Licensing endpoint from cache
			 */
			if ( class_exists( 'EDD_Software_Licensing' ) && defined( 'EDD_SL_VERSION' ) ) {
				$ecommerce_exclude_urls[] = '/edd-sl/*';
			}
		}

		/**
		 * Give shop
		 */
		if ( function_exists( 'give_get_settings' ) ) {
			$exclude_give_pages = Breeze_Ecommerce_Cache::factory()->exclude_give_pages();

			if ( ! empty( $exclude_give_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_give_pages, $ecommerce_exclude_urls );
			}
		}

		/**
		 * Big Commerce
		 */
		if ( function_exists( 'bigcommerce' ) ) {
			$exclude_bigcommerce_pages = Breeze_Ecommerce_Cache::factory()->exclude_big_commerce_pages();

			if ( ! empty( $exclude_bigcommerce_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_bigcommerce_pages, $ecommerce_exclude_urls );
			}
		}

		/**
		 * CartFlows
		 */
		if ( class_exists( 'Cartflows_Loader' ) && defined( 'CARTFLOWS_FILE' ) ) {
			$exclude_cartflows_pages = Breeze_Ecommerce_Cache::factory()->exclude_cart_flows_pages();

			if ( ! empty( $exclude_cartflows_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_cartflows_pages, $ecommerce_exclude_urls );
			}
		}

		/**
		 * MemberPress
		 */
		if ( class_exists( 'MeprJobs' ) && defined( 'MEPR_OPTIONS_SLUG' ) ) {
			$exclude_memberpress_pages = Breeze_Ecommerce_Cache::factory()->exclude_member_press_pages();

			if ( ! empty( $exclude_memberpress_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_memberpress_pages, $ecommerce_exclude_urls );
			}
		}

		/**
		 * WP eCommerce
		 */
		if ( class_exists( 'WP_eCommerce' ) ) {
			$exclude_wp_ecommerce_pages = Breeze_Ecommerce_Cache::factory()->exclude_wp_e_commerce_pages();

			if ( ! empty( $exclude_wp_ecommerce_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_wp_ecommerce_pages, $ecommerce_exclude_urls );
			}
		}

		/**
		 * Ecwid Ecommerce Shopping Cart
		 */
		if ( function_exists( 'ecwid_init_integrations' ) && defined( 'ECWID_PLUGIN_DIR' ) ) {
			$exclude_ecwid_pages = Breeze_Ecommerce_Cache::factory()->exclude_ecwid_store_pages();

			if ( ! empty( $exclude_ecwid_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_ecwid_pages, $ecommerce_exclude_urls );
			}
		}

		/**
		 * WP EasyCart
		 */
		if ( defined( 'EC_PUGIN_NAME' ) && function_exists( 'wpeasycart_load_startup' ) ) {
			$exclude_wp_easy_cart_pages = Breeze_Ecommerce_Cache::factory()->exclude_easy_cart_pages();

			if ( ! empty( $exclude_wp_easy_cart_pages ) ) {
				$ecommerce_exclude_urls = array_merge( $exclude_wp_easy_cart_pages, $ecommerce_exclude_urls );
			}
		}

		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-disable-admin', false, $create_root_config ) ) ) {
			$storage['disable_per_adminuser'] = Breeze_Options_Reader::get_option_value( 'breeze-disable-admin', false, $create_root_config );
		}

		if ( ! empty( Breeze_Options_Reader::get_option_value( 'cached-query-strings', false, $create_root_config ) ) ) {
			$storage['cached-query-strings'] = Breeze_Options_Reader::get_option_value( 'cached-query-strings', false, $create_root_config );
		}

		$storage['exclude_url'] = array_merge(
			$ecommerce_exclude_urls,
			! empty( Breeze_Options_Reader::get_option_value( 'breeze-exclude-urls', false, $create_root_config ) ) ? Breeze_Options_Reader::get_option_value( 'breeze-exclude-urls', false, $create_root_config ) : array()
		);

		$saved_pages = get_option( 'breeze_exclude_url_pages', array() );

		if ( ! empty( $saved_pages ) ) {
			$saved_pages_urls = array();
			foreach ( $saved_pages as $page_id ) {
				$saved_pages_urls[] = get_permalink( $page_id );
			}

			$saved_pages_urls = array_unique( $saved_pages_urls );

			$storage['exclude_url'] = array_merge(
				$saved_pages_urls,
				! empty( Breeze_Options_Reader::get_option_value( 'breeze-exclude-urls', false, $create_root_config ) ) ? Breeze_Options_Reader::get_option_value( 'breeze-exclude-urls', false, $create_root_config ) : array(),
				$ecommerce_exclude_urls
			);
		}

		if ( class_exists( 'WC_Facebook_Loader' ) ) {
			$woocommerce_fb_feed_link = Breeze_Ecommerce_Cache::factory()->wc_facebook_feed();

			if ( ! empty( $woocommerce_fb_feed_link ) ) {
				$storage['exclude_url'] = array_merge(
					$woocommerce_fb_feed_link,
					! empty( Breeze_Options_Reader::get_option_value( 'breeze-exclude-urls', false, $create_root_config ) ) ? Breeze_Options_Reader::get_option_value( 'breeze-exclude-urls', false, $create_root_config ) : array(),
					$ecommerce_exclude_urls
				);
			}
		}

		$the_headers = breeze_helper_fetch_headers();

		$allowed_headers = apply_filters(
			'breeze_custom_headers_allow',
			array(
				'content-security-policy',
				'x-frame-options',
				'referrer-policy',
				'strict-transport-security',
				'X-Content-Type-Options',
				'Access-Control-Allow-Origin',
				'Cross-Origin-Opener-Policy',
				'Cross-Origin-Embedder-Policy',
				'Cross-Origin-Resource-Policy',
				'Permissions-Policy',
				'X-XSS-Protection',
			)
		);

		if ( is_array( $the_headers ) && ! empty( $the_headers ) ) {
			$to_save_headers = array();

			foreach ( $allowed_headers as $header_name ) {

				$header_name = strtolower( $header_name );

				if ( array_key_exists( $header_name, $the_headers ) ) {

					if ( is_array( $the_headers[ $header_name ] ) ) {
						$to_save_headers[ $header_name ] = $the_headers[ $header_name ][0];
					} else {
						$to_save_headers[ $header_name ] = $the_headers[ $header_name ];
					}
				}
			}
			if ( ! empty( $to_save_headers ) ) {
				$storage['breeze_custom_headers'] = $to_save_headers;
			}
		}

		return self::write_config( $storage, $create_root_config );
	}

	/**
	 * Create file config storage parameter used for cache.
	 *
	 * @param array $config Options array.
	 * @param bool  $create_root_config Used in multisite, to reset/create breeze-config.php file
	 */
	public static function write_config( $config, $create_root_config = false ) {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$config_dir = trailingslashit( WP_CONTENT_DIR ) . 'breeze-config';
		$filename   = 'breeze-config';
		if ( false === $create_root_config && ( is_multisite() && ! is_network_admin() ) ) {
			$filename .= '-' . get_current_blog_id();
		}

		$config_file = $config_dir . DIRECTORY_SEPARATOR . $filename . '.php';

		if ( is_multisite() && ! is_network_admin() && breeze_does_inherit_settings() ) {
			// Site inherits network-level setting, do not create separate configuration file and remove existing configuration file.
			if ( $wp_filesystem->exists( $config_file ) ) {
				$wp_filesystem->delete( $config_file, true );
			}

			if ( false === $create_root_config ) {
				return;
			}
		}

		$wp_filesystem->mkdir( $config_dir );

		$config_file_string = '<?php ' . "\n\r" . "defined( 'ABSPATH' ) || exit;" . "\n\r" . 'return ' . var_export( $config, true ) . '; ' . "\n\r";

		return $wp_filesystem->put_contents( $config_file, $config_file_string, FS_CHMOD_FILE );
	}

	/**
	 * Turn on / off wp cache.
	 *
	 * @param bool $status If WP Cache is enabled or not.
	 *
	 * @return bool|void
	 */
	public function toggle_caching( $status ) {
		$allow_cache_toggle = true;
		if ( 'cli' !== php_sapi_name() && ( is_multisite() && ! is_network_admin() ) ) {
			$allow_cache_toggle = false;
		}

		if ( false === $allow_cache_toggle ) {
			return false;
		}

		global $wp_filesystem;
		if ( defined( 'WP_CACHE' ) && WP_CACHE === $status ) {
			return;
		}

		// Lets look 4 levels deep for wp-config.php
		$levels = 4;

		$file        = '/wp-config.php';
		$config_path = false;

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( $i > 1 ) {
				$file = '/..' . $file;
			}

			if ( $wp_filesystem->exists( untrailingslashit( ABSPATH ) . $file ) ) {
				$config_path = untrailingslashit( ABSPATH ) . $file;
				break;
			}
		}

		// Couldn't find wp-config.php
		if ( ! $config_path ) {
			return false;
		}

		$config_file_string = $wp_filesystem->get_contents( $config_path );

		// Config file is empty. Maybe couldn't read it?
		if ( empty( $config_file_string ) ) {
			return false;
		}

		$config_file = preg_split( "#(\n|\r)#", $config_file_string );
		$line_key    = false;

		foreach ( $config_file as $key => $line ) {
			if ( ! preg_match( '/^\s*define\(\s*(\'|")([A-Z_]+)(\'|")(.*)/', $line, $match ) ) {
				continue;
			}

			if ( 'WP_CACHE' === $match[2] ) {
				$line_key = $key;
			}
		}

		if ( false !== $line_key ) {
			unset( $config_file[ $line_key ] );
		}

		$status_string = ( $status ) ? 'true' : 'false';

		array_shift( $config_file );
		array_unshift( $config_file, '<?php', "define( 'WP_CACHE', $status_string ); " );

		foreach ( $config_file as $key => $line ) {
			if ( '' === $line ) {
				unset( $config_file[ $key ] );
			}
		}

		if ( ! $wp_filesystem->put_contents( $config_path, implode( PHP_EOL, $config_file ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete file for clean up.
	 *
	 * @return bool
	 */
	public function clean_up() {

		global $wp_filesystem;
		$file = untrailingslashit( WP_CONTENT_DIR ) . '/advanced-cache.php';

		$ret = true;

		if ( ! $wp_filesystem->delete( $file ) ) {
			$ret = false;
		}

		$folder = untrailingslashit( breeze_get_cache_base_path() );

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			$ret = false;
		}

		$folder = untrailingslashit( WP_CONTENT_DIR ) . '/cache/breeze-minification';

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			$ret = false;
		}

		return $ret;
	}

	/**
	 * Delete config file.
	 *
	 * @return mixed
	 */
	public function clean_config() {

		global $wp_filesystem;

		$folder = untrailingslashit( WP_CONTENT_DIR ) . '/breeze-config';

		return $wp_filesystem->delete( $folder, true );

		return true;
	}

	/**
	 * Singleton instance.
	 *
	 * @return Breeze_ConfigCache
	 */
	public static function factory() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}
