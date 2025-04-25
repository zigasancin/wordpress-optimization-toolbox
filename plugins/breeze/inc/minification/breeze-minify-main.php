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
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class Breeze_Minify {

	public function __construct() {

		$is_caching_active = filter_var( Breeze_Options_Reader::get_option_value( 'breeze-active' ), FILTER_VALIDATE_BOOLEAN );

		if ( defined( 'WP_CACHE' ) && false === WP_CACHE ) {
			$is_caching_active = false;
		}

		if ( true === $is_caching_active ) {

			//check disable cache for page
			$http_host_breeze = ( isset( $_SERVER['HTTP_HOST'] ) ) ? $_SERVER['HTTP_HOST'] : '';
			//$domain           = ( ( ( isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) || ( ! empty( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == 443 ) ) ? 'https://' : 'http://' ) . $http_host_breeze;
			$domain = ( ( ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) || ( isset( $_SERVER['SERVER_PORT'] ) && 443 === (int) $_SERVER['SERVER_PORT'] ) ) ? 'https://' : 'http://' ) . $http_host_breeze;
			//$current_url      = $domain . $_SERVER['REQUEST_URI'];
			$current_url = $domain . rawurldecode( $_SERVER['REQUEST_URI'] );

			$check_url = $this->check_exclude_url( $current_url );
			//load config file when redirect template
			if ( ! $check_url && self::should_cache() ) {
				//cache html
				//cache minification
				if ( Breeze_MinificationCache::create_cache_minification_folder() ) {

					if (
						! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-html' ) ) ||
						! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-css' ) ) ||
						! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-js' ) ) ||
						! empty( Breeze_Options_Reader::get_option_value( 'breeze-defer-js' ) ) ||
						! empty( Breeze_Options_Reader::get_option_value( 'breeze-move-to-footer-js' ) ) ||
						true === filter_var( Breeze_Options_Reader::get_option_value( 'breeze-delay-all-js' ), FILTER_VALIDATE_BOOLEAN ) ||
						(
							! empty( Breeze_Options_Reader::get_option_value( 'breeze-delay-js-scripts' ) ) &&
							true === filter_var( Breeze_Options_Reader::get_option_value( 'breeze-enable-js-delay' ), FILTER_VALIDATE_BOOLEAN )
						)
					) {

						if ( defined( 'breeze_INIT_EARLIER' ) ) {
							add_action( 'init', array( $this, 'breeze_start_buffering' ), - 1 );
						} else {
							add_action( 'wp_loaded', array( $this, 'breeze_start_buffering' ), 2 );
						}
					}
				}
			}
		}
	}

	/**
	 * Check whether to execute caching functions or not.
	 * Will not execute for purge cache or heartbeat actions.
	 */
	public static function should_cache() {
		if ( isset( $_GET['breeze_purge_cloudflare'] ) || isset( $_GET['breeze_purge'] ) || ( isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) ) {
			return false;
		}

		return true;
	}

	/*
	 * Start buffer
	 */
	public function breeze_start_buffering() {
		$ao_noptimize = false;

		// check for DONOTMINIFY constant as used by e.g. WooCommerce POS
		if ( defined( 'DONOTMINIFY' ) && ( constant( 'DONOTMINIFY' ) === true || constant( 'DONOTMINIFY' ) === 'true' ) ) {
			$ao_noptimize = true;
		}
		// filter you can use to block autoptimization on your own terms
		$ao_noptimize = (bool) apply_filters( 'breeze_filter_noptimize', $ao_noptimize );
		// if the link contains query string, we must ignore it from cache.
		$query_instance         = Breeze_Query_Strings_Rules::get_instance();
		$breeze_query_vars_list = $query_instance->check_query_var_group();
		if ( ! is_feed() && ! $ao_noptimize && ! is_admin() && 0 === (int) $breeze_query_vars_list['extra_query_no'] ) {
			// Load our base class
			include_once( BREEZE_PLUGIN_DIR . 'inc/minification/breeze-minification-base.php' );

			// Load extra classes and set some vars
			if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-html' ) ) ) {
				include_once( BREEZE_PLUGIN_DIR . 'inc/minification/breeze-minification-html.php' );
				// BUG: new minify-html does not support keeping HTML comments, skipping for now
				if ( ! class_exists( 'Minify_HTML' ) ) {
					@include( BREEZE_PLUGIN_DIR . 'inc/minification/minify/minify-html.php' );
				}
			}

			if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-js' ) ) ) {

				include_once( BREEZE_PLUGIN_DIR . 'inc/minification/breeze-minification-scripts.php' );

				// JS/CSS minifier library
				include_once( BREEZE_PLUGIN_DIR . 'vendor/autoload.php' );

				if ( ! defined( 'CONCATENATE_SCRIPTS' ) ) {
					define( 'CONCATENATE_SCRIPTS', false );
				}
				if ( ! defined( 'COMPRESS_SCRIPTS' ) ) {
					define( 'COMPRESS_SCRIPTS', false );
				}
			} elseif (
				! empty( Breeze_Options_Reader::get_option_value( 'breeze-defer-js' ) ) ||
				! empty( Breeze_Options_Reader::get_option_value( 'breeze-move-to-footer-js' ) ) ||
				true === filter_var( Breeze_Options_Reader::get_option_value( 'breeze-delay-all-js' ), FILTER_VALIDATE_BOOLEAN ) ||
				(
					! empty( Breeze_Options_Reader::get_option_value( 'breeze-delay-js-scripts' ) ) &&
					true === filter_var( Breeze_Options_Reader::get_option_value( 'breeze-enable-js-delay' ), FILTER_VALIDATE_BOOLEAN )
				)
			) {
				// If we have defer scripts to handle, load only the script for this action.
				include_once( BREEZE_PLUGIN_DIR . 'inc/minification/breeze-js-deferred-loading.php' );
			}

			if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-css' ) ) ) {
				// JS/CSS minifier library

				include_once( BREEZE_PLUGIN_DIR . 'vendor/autoload.php' );

				include_once( BREEZE_PLUGIN_DIR . 'inc/minification/breeze-minification-styles.php' );
				if ( defined( 'breeze_LEGACY_MINIFIERS' ) ) {
					if ( ! class_exists( 'Minify_CSS_Compressor' ) ) {
						@include( BREEZE_PLUGIN_DIR . 'inc/minification/minify/minify-css-compressor.php' );
					}
				} else {
					if ( ! class_exists( 'CSSmin' ) ) {
						@include( BREEZE_PLUGIN_DIR . 'inc/minification/minify/yui-php-cssmin-2.4.8-4_fgo.php' );
					}
				}
				if ( ! defined( 'COMPRESS_CSS' ) ) {
					define( 'COMPRESS_CSS', false );
				}
			}
			// Now, start the real thing!
			add_filter( 'breeze_minify_content_return', array( $this, 'breeze_end_buffering' ) );
		}
	}

	/*
	 * Minify css , js and optimize html when start
	 */

	public function breeze_end_buffering( $content ) {

		if ( stripos( $content, '<html' ) === false || stripos( $content, '<html amp' ) !== false || stripos( $content, '<html ⚡' ) !== false || stripos( $content, '<xsl:stylesheet' ) !== false ) {
			return $content;
		}
		// load URL constants as late as possible to allow domain mapper to kick in
		if ( function_exists( 'domain_mapping_siteurl' ) ) {
			define( 'breeze_WP_SITE_URL', domain_mapping_siteurl( get_current_blog_id() ) );
			define( 'breeze_WP_CONTENT_URL', str_replace( get_original_url( breeze_WP_SITE_URL ), breeze_WP_SITE_URL, content_url() ) );
		} else {
			define( 'breeze_WP_SITE_URL', site_url() );
			define( 'breeze_WP_CONTENT_URL', content_url() );
		}
		if ( is_multisite() && apply_filters( 'breeze_separate_blog_caches', true ) ) {
			$blog_id = get_current_blog_id();
			define( 'breeze_CACHE_URL', breeze_WP_CONTENT_URL . BREEZE_CACHE_CHILD_DIR . $blog_id . '/' );
		} else {
			define( 'breeze_CACHE_URL', breeze_WP_CONTENT_URL . BREEZE_CACHE_CHILD_DIR );
		}
		define( 'breeze_WP_ROOT_URL', str_replace( BREEZE_WP_CONTENT_NAME, '', breeze_WP_CONTENT_URL ) );

		define( 'breeze_HASH', wp_hash( breeze_CACHE_URL ) );
		// Config element
		$conf    = Breeze_Options_Reader::get_option_value( 'basic_settings', true );
		$cdn_url = '';
		if ( '1' === Breeze_Options_Reader::get_option_value( 'cdn-active' ) ) {
			$cdn_url = Breeze_Options_Reader::get_option_value( 'cdn-url' );
		}

		// Choose the classes
		$classes           = array();
		$js_include_inline = $css_include_inline = false;
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-js' ) ) ) {
			$classes[] = 'Breeze_MinificationScripts';

		} elseif (
			! empty( Breeze_Options_Reader::get_option_value( 'breeze-defer-js' ) ) ||
			! empty( Breeze_Options_Reader::get_option_value( 'breeze-move-to-footer-js' ) ) ||
			true === filter_var( Breeze_Options_Reader::get_option_value( 'breeze-delay-all-js' ), FILTER_VALIDATE_BOOLEAN ) ||
			(
				! empty( Breeze_Options_Reader::get_option_value( 'breeze-delay-js-scripts' ) ) &&
				true === filter_var( Breeze_Options_Reader::get_option_value( 'breeze-enable-js-delay' ), FILTER_VALIDATE_BOOLEAN )
			)

		) {
			$classes[] = 'Breeze_Js_Deferred_Loading';

		}

		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-css' ) ) ) {
			$classes[] = 'Breeze_MinificationStyles';
		}
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-html' ) ) ) {
			$classes[] = 'Breeze_MinificationHtml';
		}
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-include-inline-js' ) ) ) {
			$js_include_inline = true;
		}
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-include-inline-css' ) ) ) {
			$css_include_inline = true;
		}
		$groupcss = false;
		$groupjs  = false;
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-group-css' ) ) ) {
			$groupcss = true;
		}
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-group-js' ) ) ) {
			$groupjs = true;
		}

		$font_swap = filter_var( Breeze_Options_Reader::get_option_value( 'breeze-font-display-swap' ), FILTER_VALIDATE_BOOLEAN );

		// Set some options
		$no_script_delay     = Breeze_Options_Reader::get_option_value( 'no-breeze-no-delay-js' );
		$breeze_delay_all_js = filter_var( Breeze_Options_Reader::get_option_value( 'breeze-delay-all-js' ), FILTER_VALIDATE_BOOLEAN );
		$script_delay        = Breeze_Options_Reader::get_option_value( 'breeze-delay-js-scripts' );
		$is_inline_delay_on  = filter_var( Breeze_Options_Reader::get_option_value( 'breeze-enable-js-delay' ), FILTER_VALIDATE_BOOLEAN );
		$classoptions        = array(
			'Breeze_MinificationScripts' => array(
				'justhead'           => false,
				'forcehead'          => false,
				'trycatch'           => false,
				'js_exclude'         => 's_sid, smowtion_size, sc_project, WAU_, wau_add, comment-form-quicktags, edToolbar, ch_client, seal.js',
				'cdn_url'            => '',
				'include_inline'     => $js_include_inline,
				'group_js'           => $groupjs,
				'custom_js_exclude'  => Breeze_Options_Reader::get_option_value( 'breeze-exclude-js' ),
				'move_to_footer_js'  => Breeze_Options_Reader::get_option_value( 'breeze-move-to-footer-js' ),
				'defer_js'           => Breeze_Options_Reader::get_option_value( 'breeze-defer-js' ),
				'delay_inline_js'    => ( ! empty( $script_delay ) ? $script_delay : array() ),
				'no_delay_js'        => ( ! empty( $no_script_delay ) ? $no_script_delay : array() ),
				'delay_javascript'   => $breeze_delay_all_js,
				'is_inline_delay_on' => $is_inline_delay_on,
			),
			'Breeze_MinificationStyles'  => array(
				'justhead'             => false,
				'datauris'             => false,
				'defer'                => false,
				'defer_inline'         => false,
				'inline'               => false,
				'css_exclude'          => 'admin-bar.min.css, dashicons.min.css',
				'cdn_url'              => '',
				'include_inline'       => $css_include_inline,
				'font_swap'            => $font_swap,
				'nogooglefont'         => false,
				'groupcss'             => $groupcss,
				'custom_css_exclude'   => Breeze_Options_Reader::get_option_value( 'breeze-exclude-css' ),
				'include_imported_css' => false,
			),
			'Breeze_MinificationHtml'    => array(
				'keepcomments' => false,
			),
			'Breeze_Js_Deferred_Loading' => array(
				'move_to_footer_js'  => Breeze_Options_Reader::get_option_value( 'breeze-move-to-footer-js' ),
				'defer_js'           => Breeze_Options_Reader::get_option_value( 'breeze-defer-js' ),
				'delay_inline_js'    => ( ! empty( $script_delay ) ? $script_delay : array() ),
				'no_delay_js'        => ( ! empty( $no_script_delay ) ? $no_script_delay : array() ),
				'cdn_url'            => $cdn_url,
				'delay_javascript'   => $breeze_delay_all_js,
				'is_inline_delay_on' => $is_inline_delay_on,
			),
		);

		$content = apply_filters( 'breeze_filter_html_before_minify', $content );

		$is_caching_on = filter_var( Breeze_Options_Reader::get_option_value( 'breeze-active' ), FILTER_VALIDATE_BOOLEAN );
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			$current_user       = wp_get_current_user();
			$current_user_roles = (array) $current_user->roles;
			//$one_role           = reset( $current_user_roles );
			$is_found             = false;
			$breeze_disable_admin = Breeze_Options_Reader::get_option_value( 'breeze-disable-admin' );
			foreach ( $current_user_roles as $index => $one_role ) {
				if ( isset( $breeze_disable_admin[ $one_role ] ) && true === filter_var( $breeze_disable_admin[ $one_role ], FILTER_VALIDATE_BOOLEAN ) ) {
					$is_found = true;
				}
			}

			$is_caching_on = $is_found;
		}

		//TODO: Move the store files locally function here in case it won't work where it is originally

		if ( ! empty( $conf ) && false === $is_caching_on && is_user_logged_in() ) {
			$content = apply_filters( 'breeze_html_after_minify', $content );

		} else {
			// Run the classes
			foreach ( $classes as $name ) {

				$do_process = false;
				$instance   = new $name( $content );
				if ( 'Breeze_MinificationStyles' === $name ) {
					$this_path_url = $instance->get_cache_file_url( 'css' );
					$do_process    = breeze_is_process_locked( $this_path_url );
				}

				if ( 'Breeze_MinificationScripts' === $name ) {
					$this_path_url = $instance->get_cache_file_url( 'js' );
					$do_process    = breeze_is_process_locked( $this_path_url );
				}

				if ( 'Breeze_MinificationHtml' === $name ) {
					$this_path_url = $instance->get_cache_file_url( '' );
					$do_process    = breeze_is_process_locked( $this_path_url );
				}

				if ( false === $do_process ) {

					if ( $instance->read( $classoptions[ $name ] ) ) {
						$instance->minify();
						$instance->cache();
						$content = $instance->getcontent();
					}
					unset( $instance );
				}
			}
			$content = apply_filters( 'breeze_html_after_minify', $content );
		}

		return $content;
	}

	/*
	 * Remove '/' chacracter of end url
	 */
	public function rtrim_urls( $url ) {
		if ( ! is_string( $url ) ) {
			$url = '';
		}

		return rtrim( $url, '/' );
	}

	/*
	 * check url from Never cache the following pages area
	 */
	public function check_exclude_url( $current_url ) {
		$config_options = $this->read_the_config_file();
		if ( ! empty( $config_options ) ) {

			$breeze_exclude_urls = Breeze_Options_Reader::get_option_value( 'breeze-exclude-urls' );

			if ( ! isset( $breeze_exclude_urls ) || ! is_array( $breeze_exclude_urls ) ) {
				$breeze_exclude_urls = array();
			}

			$breeze_exclude_urls = array_merge( $breeze_exclude_urls, $config_options );
			$urls                = array_unique( $breeze_exclude_urls );
			$breeze_exclude_urls = array_map( array( $this, 'rtrim_urls' ), $urls );
		}

		if ( ! isset( $breeze_exclude_urls ) ) {
			$breeze_exclude_urls = array();
		}

		$is_exclude = breeze_check_for_exclude_values( $current_url, $breeze_exclude_urls );
		if ( ! empty( $is_exclude ) ) {

			return true;
		}
		//check disable cache for page
		if ( ! empty( $breeze_exclude_urls ) ) {
			foreach ( $breeze_exclude_urls as $exclude_url_item ) {
				// Clear blank character
				$exclude_url_item = trim( $exclude_url_item );
				if ( empty( $exclude_url_item ) ) {
					continue;
				}

				if ( preg_match( '/(\&?\/?\(\.?\*\)|\/\*|\*)$/', $exclude_url_item, $matches ) ) {

					if ( isset( $matches[0] ) && ! empty( $matches[0] ) ) {
						// End of rules is *, /*, [&][/](*) , [&][/](.*)
						$pattent = substr( $exclude_url_item, 0, strpos( $exclude_url_item, $matches[0] ) );
						if ( $exclude_url_item[0] == '/' ) {
							// A path of exclude url with regex
							if ( ( @preg_match( '@' . $pattent . '@', $current_url, $matches ) > 0 ) ) {
								return true;
							}
						} else {
							// Full exclude url with regex
							if ( ! empty( $pattent ) ) {
								if ( strpos( $current_url, $pattent ) !== false ) {
									return true;
								}
							}

						}
					}

				} else {

					$test_url = ltrim( $exclude_url_item, 'https:' );
					$test_url = $this->rtrim_urls( $test_url );

					$current_url = ltrim( $current_url, 'https:' );
					$current_url = $this->rtrim_urls( $current_url );

					// Whole path
					if ( mb_strtolower( $test_url ) === mb_strtolower( $current_url ) ) {
						return true;
					}
				}
			}
		}

		return false;

	}

	/*
	 * Will Return the options for the current website
	 *
	 * @since 1.1.8
	 * @access public
	 */
	public function read_the_config_file() {
		global $wpdb;
		$config_dir = trailingslashit( WP_CONTENT_DIR ) . 'breeze-config';
		$filename   = 'breeze-config';
		if ( is_multisite() && ! is_network_admin() ) {

			$blog_id_requested = isset( $GLOBALS['breeze_config']['blog_id'] ) ? $GLOBALS['breeze_config']['blog_id'] : 0;
			if ( empty( $blog_id_requested ) ) {
				$blog_id_requested = get_current_blog_id();
			}
			$filename .= '-' . $blog_id_requested;
		}

		$config_file = $config_dir . DIRECTORY_SEPARATOR . $filename . '.php';
		if ( file_exists( $config_file ) ) {
			$config = include $config_file;
			if ( empty( $config ) || ! isset( $config['exclude_url'] ) || empty( $config['exclude_url'] ) ) {
				return false;
			}

			return $config['exclude_url'];
		} else {
			return false;
		}

	}
}
