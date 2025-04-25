<?php
/*
 *  Based on some work of https://github.com/tlovett1/simple-cache/blob/master/inc/dropins/file-based-page-cache.php
 */

namespace Breeze_Cache_Init;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
	return; // Skip caching for search results.
}


if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
	$_SERVER['HTTP_USER_AGENT'] = 'empty_agent';
}

if ( isset( $GLOBALS['breeze_config'], $GLOBALS['breeze_config']['cache_options'], $GLOBALS['breeze_config']['cache_options']['breeze-active'] ) ) {
	$is_caching_active = filter_var( $GLOBALS['breeze_config']['cache_options']['breeze-active'], FILTER_VALIDATE_BOOLEAN );
	if ( false === $is_caching_active ) {
		return;
	}
}

// Load helper functions.
require_once dirname( __DIR__ ) . '/functions.php';

if ( isset( $GLOBALS['breeze_config'], $GLOBALS['breeze_config']['disable_per_adminuser'] ) ) {
	$wp_cookies = array( 'wordpressuser_', 'wordpresspass_', 'wordpress_sec_', 'wordpress_logged_in_' );

	$breeze_user_logged      = false;
	$breeze_use_cache_system = filter_var( $GLOBALS['breeze_config']['cache_options']['breeze-active'], FILTER_VALIDATE_BOOLEAN );
	$folder_cache            = '';
	foreach ( $_COOKIE as $key => $value ) {
		// Logged in!
		if ( strpos( $key, 'wordpress_logged_in_' ) !== false ) {
			$breeze_user_logged = true;
		}

		if ( BREEZE_WP_COOKIE === $key ) {
			$folder_cache = breeze_which_role_folder( $value );
		}
	}

	if ( ! empty( $folder_cache ) ) {
		$is_active = false;
		foreach ( $folder_cache as $cache_role ) {
			if (
				isset( $GLOBALS['breeze_config']['disable_per_adminuser'][ $cache_role ] ) &&
				true === filter_var( $GLOBALS['breeze_config']['disable_per_adminuser'][ $cache_role ], FILTER_VALIDATE_BOOLEAN )
			) {
				$is_active = true;
			}
		}

		$breeze_use_cache_system = $is_active;
	}

	if ( true === $breeze_user_logged && false === $breeze_use_cache_system ) {
		return;
	}
}

// Load lazy Load class.
require_once dirname( __DIR__ ) . '/class-breeze-lazy-load.php';

// Include and instantiate the class.
$detect = breeze_mobile_detect_library();
if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
	$_SERVER['HTTP_USER_AGENT'] = '';
}
$detect->setUserAgent( $_SERVER['HTTP_USER_AGENT'] );
// Don't cache robots.txt or htacesss
if ( strpos( $_SERVER['REQUEST_URI'], 'robots.txt' ) !== false || strpos( $_SERVER['REQUEST_URI'], '.htaccess' ) !== false ) {
	return;
}

if ( strpos( $_SERVER['REQUEST_URI'], '/wp-json/geodir/' ) !== false ) {
	return;
}

if ( isset( $_GET['wc-api'] ) ) {
	return;
}

if (
	strpos( $_SERVER['REQUEST_URI'], 'breeze-minification' ) !== false ||
	strpos( $_SERVER['REQUEST_URI'], 'favicon.ico' ) !== false ||
	strpos( $_SERVER['REQUEST_URI'], 'wp-cron.php' ) !== false
) {
	return;
}

// Don't cache non-GET requests
if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
	return;
}

$file_extension = $_SERVER['REQUEST_URI'];
$file_extension = preg_replace( '#^(.*?)\?.*$#', '$1', $file_extension );
$file_extension = trim( preg_replace( '#^.*\.(.*)$#', '$1', $file_extension ) );

// Don't cache disallowed extensions. Prevents wp-cron.php, xmlrpc.php, etc.
if ( ! preg_match( '#index\.php$#i', $_SERVER['REQUEST_URI'] ) && in_array( $file_extension, array( 'php', 'xml', 'xsl' ) ) ) {
	return;
}

$filename_guest_suffix   = '';
$breeze_current_url_path = breeze_get_url_path();

$user_logged = false;

if ( substr_count( $breeze_current_url_path, '?' ) > 0 ) {
	$filename              = $breeze_current_url_path . '&guest';
	$filename_guest_suffix = '&guest';
} else {
	$filename              = $breeze_current_url_path . '?guest';
	$filename_guest_suffix = '?guest';
}

// Don't cache
if ( ! empty( $_COOKIE ) ) {
	$wp_cookies = array( 'wordpressuser_', 'wordpresspass_', 'wordpress_sec_', 'wordpress_logged_in_' );

	foreach ( $_COOKIE as $key => $value ) {
		// Logged in!
		if ( strpos( $key, 'wordpress_logged_in_' ) !== false ) {
			$user_logged = true;
		}
	}

	if ( $user_logged ) {
		foreach ( $_COOKIE as $k => $v ) {
			if ( strpos( $k, 'wordpress_logged_in_' ) !== false ) {
				$nameuser = substr( $v, 0, strpos( $v, '|' ) );
				if ( substr_count( $breeze_current_url_path, '?' ) > 0 ) {
					$filename = $breeze_current_url_path . '&' . strtolower( $nameuser );
				} else {
					$filename = $breeze_current_url_path . '?' . strtolower( $nameuser );
				}
			}
		}
	}

	if ( ! empty( $_COOKIE['breeze_commented_posts'] ) ) {
		foreach ( $_COOKIE['breeze_commented_posts'] as $path ) {
			if ( ! empty( $path ) ) {
				if ( rtrim( $path, '/' ) === rtrim( $_SERVER['REQUEST_URI'], '/' ) ) {
					// User commented on this post
					return;
				}
			}
		}
	}
}

//check disable cache for page
$domain = ( ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) || $_SERVER['SERVER_PORT'] == 443 ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
//decode url with russian language
$current_url   = $domain . rawurldecode( $_SERVER['REQUEST_URI'] );
$opts_config   = $GLOBALS['breeze_config'];
$check_exclude = check_exclude_page( $opts_config, $current_url );

$query_instance         = \Breeze_Query_Strings_Rules::get_instance();
$breeze_query_vars_list = $query_instance->check_query_var_group( $current_url );

if ( false === $check_exclude && 0 !== (int) $breeze_query_vars_list['extra_query_no'] ) {
	header( 'Cache-control: must-revalidate, max-age=0' );

	return;

}

//load cache
if ( ! $check_exclude ) {
	$devices = $opts_config['cache_options'];
	$X1      = '';
	// Detect devices
	if ( $detect->isMobile() && ! $detect->isTablet() ) {
		//        The first X will be D for Desktop cache
		//                            M for Mobile cache
		//                            T for Tablet cache
		if ( (int) $devices['breeze-mobile-cache'] == 1 ) {
			$filename .= '_breeze_cache_desktop';
		}
		if ( (int) $devices['breeze-mobile-cache'] == 2 ) {
			$filename .= '_breeze_cache_mobile';
		}
	} else {
		if ( (int) $devices['breeze-desktop-cache'] == 1 ) {
			$filename .= '_breeze_cache_desktop';
		}
	}

	$X1 = 'D';

	if ( true === is_breeze_mobile_cache() ) {
		if ( true === breeze_is_cloudways_server() ) {
			$X1 = breeze_cache_type_return();
		} else {
			if ( $detect->isMobile() ) {
				if ( ! $detect->isTablet() ) {
					$X1 = 'M';
				} else {
					$X1 = 'T';
				}
			} else {
				$X1 = 'D';
			}
		}
	}

	breeze_serve_cache( $filename, $breeze_current_url_path, $X1, $devices );
	\ob_start( 'Breeze_Cache_Init\breeze_cache' );
} else {

	header( 'Cache-Control: no-cache' );
}

/**
 * Cache output before it goes to the browser
 *
 * @param string $buffer
 * @param int $flags
 *
 * @return string
 * @since  1.0
 */
function breeze_cache( $buffer, $flags ) {
	global $breeze_user_logged, $breeze_use_cache_system;

	if ( constant_donotcachepage_found() ) {
		return $buffer;
	}
	// No cache for pages without 200 response status
	if ( http_response_code() !== 200 ) {
		return $buffer;
	}

	$detect = breeze_mobile_detect_library();
	if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$_SERVER['HTTP_USER_AGENT'] = '';
	}
	$detect->setUserAgent( $_SERVER['HTTP_USER_AGENT'] );
	//not cache per administrator if option disable optimization for admin users clicked
	if ( true === $breeze_user_logged && false === $breeze_use_cache_system ) {
		return $buffer;
	}

	if ( strlen( $buffer ) < 255 ) {
		return $buffer;
	}

	// Don't cache search, 404, or password protected
	if ( is_404() || is_search() || post_password_required() ) {
		return $buffer;
	}

	// Filter to modify cache buffer before caching
	$buffer = apply_filters( 'breeze_cache_buffer_before_processing', $buffer );

	global $wp_filesystem, $breeze_current_url_path;
	if ( empty( $wp_filesystem ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	$blog_id_requested = isset( $GLOBALS['breeze_config']['blog_id'] ) ? $GLOBALS['breeze_config']['blog_id'] : 0;
	$cache_base_path   = breeze_get_cache_base_path( false, $blog_id_requested );
	$path              = $cache_base_path . hash( 'sha512', $breeze_current_url_path );

	// Make sure we can read/write files and that proper folders exist
	if ( ! wp_mkdir_p( $path ) ) {
		// Can not cache!
		return $buffer;
	}
	$path .= '/';

	$modified_time = time(); // Make sure modified time is consistent

	// Lazy load implementation
	if ( class_exists( 'Breeze_Lazy_Load' ) ) {
		if ( isset( $GLOBALS['breeze_config'] ) ) {
			if ( ! isset( $GLOBALS['breeze_config']['enabled-lazy-load'] ) ) {
				$GLOBALS['breeze_config']['enabled-lazy-load'] = false;
			}

			if ( ! isset( $GLOBALS['breeze_config']['use-lazy-load-native'] ) ) {
				$GLOBALS['breeze_config']['use-lazy-load-native'] = false;
			}

			$is_lazy_load_enabled = filter_var( $GLOBALS['breeze_config']['enabled-lazy-load'], FILTER_VALIDATE_BOOLEAN );
			$is_lazy_load_native  = filter_var( $GLOBALS['breeze_config']['use-lazy-load-native'], FILTER_VALIDATE_BOOLEAN );

			$lazy_load = new \Breeze_Lazy_Load( $buffer, $is_lazy_load_enabled, $is_lazy_load_native );
			$buffer    = $lazy_load->apply_lazy_load_feature();
		}
	}

	// Cross-origin safe link functionality
	if ( isset( $GLOBALS['breeze_config']['cache_options']['breeze-cross-origin'] ) && filter_var( $GLOBALS['breeze_config']['cache_options']['breeze-cross-origin'], FILTER_VALIDATE_BOOLEAN ) ) {

		// Buffer encoding
		if ( version_compare( PHP_VERSION, '8.2.0', '<' ) ) {
			$buffer = mb_convert_encoding( $buffer, 'HTML-ENTITIES', 'UTF-8' );
		} else {
			$buffer = mb_encode_numericentity(
				$buffer,
				array( 0x80, 0x10FFFF, 0, ~0 ),
				'UTF-8'
			);
		}
		// Regular expression pattern to match anchor (a) tags
		$pattern = '/<a\s+(.*?)>/si';
		$buffer  = preg_replace_callback(
			$pattern,
			function ( $matches ) {
				return breeze_cc_process_match( $matches );
			},
			$buffer
		);

		// Buffer decoding.
		$buffer = mb_decode_numericentity( $buffer, array( 0x80, 0x10FFFF, 0, ~0 ), 'UTF-8' );
	}

	$cache_type = '';
	if ( preg_match( '#</html>#i', $buffer ) ) {

		if ( true === is_breeze_mobile_cache() ) {
			if ( true === breeze_is_cloudways_server() ) {
				$cache_type_cloudways = breeze_cache_type_return();
				if ( 'D' === $cache_type_cloudways ) {
					$cache_type = ' (Desktop)';
				} elseif ( 'T' === $cache_type_cloudways ) {
					$cache_type = ' (Tablet)';
				} elseif ( 'M' === $cache_type_cloudways ) {
					$cache_type = ' (Mobile)';
				}
			} else {
				if ( $detect->isMobile() ) {
					if ( ! $detect->isTablet() ) {
						$cache_type = ' (Mobile)';
					} else {
						$cache_type = ' (Tablet)';
					}
				} else {
					$cache_type = ' (Desktop)';
				}
			}
		}

		$buffer .= "\n<!-- Cache served by breeze CACHE{$cache_type} - Last modified: " . gmdate( 'D, d M Y H:i:s', $modified_time ) . " GMT -->\n";
	}

	$headers = array(
		array(
			'name'  => 'Content-Length',
			'value' => strlen( $buffer ),
		),
		array(
			'name'  => 'Content-Type',
			'value' => 'text/html; charset=utf-8',
		),
		array(
			'name'  => 'Last-Modified',
			'value' => gmdate( 'D, d M Y H:i:s', $modified_time ) . ' GMT',
		),
	);

	if ( isset( $GLOBALS['breeze_config']['breeze_custom_headers'] ) && is_array( $GLOBALS['breeze_config']['breeze_custom_headers'] ) ) {
		foreach ( $GLOBALS['breeze_config']['breeze_custom_headers'] as $header_name => $header_value ) {
			$headers[] = array(
				'name'  => $header_name,
				'value' => $header_value,
			);
		}
	}

	$data = serialize(
		array(
			'body'    => $buffer,
			'headers' => $headers,
		)
	);

	// Filter to modify cache buffer after caching
	$buffer = apply_filters( 'breeze_cache_buffer_after_processing', $buffer );

	//cache per users
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		if ( $current_user->user_login ) {

			if ( substr_count( $breeze_current_url_path, '?' ) > 0 ) {
				$breeze_current_url_path .= '&' . $current_user->user_login;
			} else {
				$breeze_current_url_path .= '?' . $current_user->user_login;
			}
			#$url_path .= $current_user->user_login;
		}
	} else {
		global $filename_guest_suffix;
		$breeze_current_url_path .= $filename_guest_suffix;
	}
	$devices = $GLOBALS['breeze_config']['cache_options'];
	// Detect devices
	if ( $detect->isMobile() && ! $detect->isTablet() ) {
		if ( $devices['breeze-mobile-cache'] == 1 ) {
			$breeze_current_url_path .= '_breeze_cache_desktop';
		}
		if ( $devices['breeze-mobile-cache'] == 2 ) {
			$breeze_current_url_path .= '_breeze_cache_mobile';
		}
	} else {
		if ( $devices['breeze-desktop-cache'] == 1 ) {
			$breeze_current_url_path .= '_breeze_cache_desktop';
		}
	}
	$X1 = 'D';
	if ( true === is_breeze_mobile_cache() ) {
		if ( true === breeze_is_cloudways_server() ) {
			$X1 = breeze_cache_type_return();
		} else {
			if ( $detect->isMobile() ) {
				if ( ! $detect->isTablet() ) {
					$X1 = 'M';
				} else {
					$X1 = 'T';
				}
			} else {
				$X1 = 'D';
			}
		}
	}

	$is_suffix = breeze_currency_switcher_cache();

	if ( strpos( $breeze_current_url_path, '_breeze_cache_' ) !== false ) {
		if ( ! empty( $GLOBALS['breeze_config']['cache_options']['breeze-gzip-compression'] ) && function_exists( 'gzencode' ) ) {

			$wp_filesystem->put_contents( $path . breeze_mobile_detect() . hash( 'sha512', $breeze_current_url_path . '/index.gzip.html' ) . $is_suffix . '.html', $data );
			$wp_filesystem->touch( $path . breeze_mobile_detect() . hash( 'sha512', $breeze_current_url_path . '/index.gzip.html' ) . $is_suffix . '.html', $modified_time );
		} else {

			$wp_filesystem->put_contents( $path . breeze_mobile_detect() . hash( 'sha512', $breeze_current_url_path . '/index.html' ) . $is_suffix . '.html', $data );
			$wp_filesystem->touch( $path . breeze_mobile_detect() . hash( 'sha512', $breeze_current_url_path . '/index.html' ) . $is_suffix . '.html', $modified_time );
		}
	} else {
		return $buffer;
	}

	//set cache provider header if not exists cache file
	header( 'Cache-Provider:CLOUDWAYS-CACHE-' . $X1 . 'C' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $modified_time ) . ' GMT' );

	if ( function_exists( 'ob_gzhandler' ) && ! empty( $GLOBALS['breeze_config']['cache_options']['breeze-gzip-compression'] ) ) {
		$ini_output_compression = ini_get( 'zlib.output_compression' );
		$array_values           = array( '1', 'On', 'on' );
		if ( in_array( $ini_output_compression, $array_values ) ) {
			return $buffer;
		} else {
			if ( defined( 'RedisCachePro\Version' ) ) {
				return $buffer;
			} else {
				return ob_gzhandler( $buffer, $flags );
			}
		}
	} else {
		return $buffer;
	}
}

/**
 * Check for the constant woocommerce and other plugins declares.
 */
function constant_donotcachepage_found() {
	if ( ! defined( 'DONOTCACHEPAGE' ) || ! DONOTCACHEPAGE ) {
		return false;
	}

	return ! apply_filters( 'breeze_override_donotcachepage', false );
}

/**
 * Get URL path for caching
 *
 * @return string
 * @since  1.0
 */
function breeze_get_url_path() {

	$host   = ( isset( $_SERVER['HTTP_HOST'] ) ) ? $_SERVER['HTTP_HOST'] : '';
	$domain = ( ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) || ( ! empty( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == 443 ) ) ? 'https://' : 'http://' );
	if ( empty( $host ) ) {
		$host = '';
	}
	$the_url = $domain . rtrim( $host, '/' ) . $_SERVER['REQUEST_URI'];

	$query_instance         = \Breeze_Query_Strings_Rules::get_instance();
	$breeze_query_vars_list = $query_instance->check_query_var_group( $the_url );
	if ( 0 !== (int) $breeze_query_vars_list['ignored_no'] ) {
		$the_url = $query_instance->rebuild_url( $the_url, $breeze_query_vars_list );
	}

	return $the_url;
}

/**
 * Optionally serve cache and exit
 *
 * @since 1.0
 */
function breeze_serve_cache( $filename, $breeze_current_url_path, $X1, $opts ) {
	if ( strpos( $filename, '_breeze_cache_' ) === false ) {
		return;
	}
	$is_suffix = breeze_currency_switcher_cache();
	if ( function_exists( 'gzencode' ) && ! empty( $GLOBALS['breeze_config']['cache_options']['breeze-gzip-compression'] ) ) {
		$file_name = hash( 'sha512', $filename . '/index.gzip.html' ) . $is_suffix . '.html';
	} else {
		$file_name = hash( 'sha512', $filename . '/index.html' ) . $is_suffix . '.html';
	}

	$blog_id_requested = isset( $GLOBALS['breeze_config']['blog_id'] ) ? $GLOBALS['breeze_config']['blog_id'] : 0;
	$path              = breeze_get_cache_base_path( false, $blog_id_requested ) . hash( 'sha512', $breeze_current_url_path ) . '/' . breeze_mobile_detect() . $file_name;

	if ( @file_exists( $path ) ) {

		$cacheFile = file_get_contents( $path );

		if ( $cacheFile != false ) {
			$datas = unserialize( $cacheFile );
			foreach ( $datas['headers'] as $data ) {
				header( $data['name'] . ': ' . $data['value'] );
			}
			//set cache provider header
			header( 'Cache-Provider:CLOUDWAYS-CACHE-' . $X1 . 'E' );

			$client_support_gzip = true;

			//check gzip request from client
			if ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) && ( strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) === false || strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate' ) === false ) ) {
				$client_support_gzip = false;
			}

			if ( isset( $GLOBALS['breeze_config']['breeze_custom_headers'] ) && is_array( $GLOBALS['breeze_config']['breeze_custom_headers'] ) ) {
				foreach ( $GLOBALS['breeze_config']['breeze_custom_headers'] as $header_name => $header_value ) {
					header( $header_name . ': ' . $header_value );
				}
			}

			if ( $client_support_gzip && function_exists( 'gzdecode' ) && ! empty( $GLOBALS['breeze_config']['cache_options']['breeze-gzip-compression'] ) ) {
				//if file is zip

				$content = gzencode( $datas['body'], 9 );
				header( 'Content-Encoding: gzip' );
				header( 'Content-Length: ' . strlen( $content ) );
				header( 'Vary: Accept-Encoding' );
				echo $content;
			} else {
				header( 'Content-Length: ' . strlen( $datas['body'] ) );
				//render page cache
				echo $datas['body'];
			}
			exit;
		}
	}
}

function check_exclude_page( $opts_config, $current_url ) {
	$is_feed = breeze_is_feed( $current_url );

	if ( true === $is_feed ) {
		return true;
	}

	$is_amp = breeze_uri_amp_check( $current_url );
	if ( true === $is_amp ) {
		return true;
	}

	//check disable cache for page
	if ( ! empty( $opts_config['exclude_url'] ) ) {

		$is_exclude = exec_breeze_check_for_exclude_values( $current_url, $opts_config['exclude_url'] );

		if ( ! empty( $is_exclude ) ) {
			return true;
		}

		foreach ( $opts_config['exclude_url'] as $exclude_url ) {
			// Clear blank character
			$exclude_url = trim( $exclude_url );
			if ( preg_match( '/(\&?\/?\(\.?\*\)|\/\*|\*)$/', $exclude_url, $matches ) ) {
				// End of rules is *, /*, [&][/](*) , [&][/](.*)
				$pattent = substr( $exclude_url, 0, strpos( $exclude_url, $matches[0] ) );
				if ( $exclude_url[0] == '/' ) {
					// A path of exclude url with regex
					if ( ( @preg_match( '@' . $pattent . '@', $current_url, $matches ) > 0 ) ) {
						return true;
					}
				} else {
					// Full exclude url with regex
					if ( ( ! empty( $pattent ) && ! empty( $current_url ) ) && strpos( $current_url, $pattent ) !== false ) {
						return true;
					}
				}
			} else {
				if ( $exclude_url[0] == '/' ) {

					// A path of exclude
					if ( ( @preg_match( '@' . $exclude_url . '@', $current_url, $matches ) > 0 ) ) {
						return true;
					}
				} else { // Whole path


					$exclude_url = ltrim( $exclude_url, 'https:' );
					$current_url = ltrim( $current_url, 'https:' );
					if (
						mb_strtolower( $exclude_url ) === mb_strtolower( $current_url ) ||
						br_trailingslashit( mb_strtolower( $exclude_url ) ) === br_trailingslashit( mb_strtolower( $current_url ) )
					) {
						return true;
					}
				}
			}
		}
	}

	return false;
}

function br_trailingslashit( $value ): string {
	return br_untrailingslashit( $value ) . '/';
}
function br_untrailingslashit( $value ): string {
	return rtrim( $value, '/\\' );
}
/**
 * Used to check for regexp exclude pages
 *
 * @param string $needle
 * @param array $haystack
 *
 * @return array
 * @since 1.1.7
 *
 */
function exec_breeze_check_for_exclude_values( $needle = '', $haystack = array() ) {

	if ( empty( $needle ) || empty( $haystack ) ) {
		return array();
	}
	$needle             = trim( $needle );
	$is_string_in_array = array_filter(
		$haystack,
		function ( $var ) use ( $needle ) {
			if ( exec_breeze_string_contains_exclude_regexp( $var ) ) {
				return exec_breeze_file_match_pattern( $needle, $var );
			} else {
				return false;
			}

		}
	);

	return $is_string_in_array;
}

/**
 * Function used to determine if the excluded URL contains regexp
 *
 * @param $file_url
 * @param string $validate
 *
 * @return bool
 */
function exec_breeze_string_contains_exclude_regexp( $file_url, $validate = '(.*)' ) {
	if ( empty( $file_url ) ) {
		return false;
	}
	if ( empty( $validate ) ) {
		return false;
	}

	$valid = false;

	if ( substr_count( $file_url, $validate ) !== 0 ) {
		$valid = true; // 0 or false
	}

	return $valid;
}


/**
 * Method will prepare the URLs escaped for preg_match
 * Will return the file_url matches the pattern.
 * empty array for false,
 * aray with data for true.
 *
 * @param $file_url
 * @param $pattern
 *
 * @return false|int
 */
function exec_breeze_file_match_pattern( $file_url, $pattern ) {
	$remove_pattern   = str_replace( '(.*)', 'REG_EXP_ALL', $pattern );
	$prepared_pattern = preg_quote( $remove_pattern, '/' );
	$pattern          = str_replace( 'REG_EXP_ALL', '(.*)', $prepared_pattern );
	$result           = preg_match( '/' . $pattern . '/', $file_url );

	return $result;
}

/**
 * Preg replace callback function for anchor handling
 *
 * @param $match
 *
 * @return string
 */
function breeze_cc_process_match( $match ) {
	// Get the home URL
	$home_url = $GLOBALS['breeze_config']['homepage'];
	$home_url = ltrim( $home_url, 'https:' );

	// Set the rel attribute values
	$replacement_rel_arr = array( 'noopener', 'noreferrer' );

	// Extract the href and target attributes
	$href_attr   = '';
	$target_attr = '';
	preg_match( '/href=(\'|")(.*?)\\1/si', $match[1], $href_match );
	preg_match( '/target=(\'|")(.*?)\\1/si', $match[1], $target_match );
	if ( $href_match ) {
		$href_attr = $href_match[2];
	}
	if ( $target_match ) {
		$target_attr = $target_match[2];
	}

	// Check if this is an external link
	if ( ! empty( $href_attr ) &&
		filter_var( $href_attr, FILTER_VALIDATE_URL ) &&
		strpos( $href_attr, $home_url ) === false &&
		strpos( $target_attr, '_blank' ) !== false ) {

		// Extract the rel attribute, if present
		$rel_attr = '';
		preg_match( '/rel=(\'|")(.*?)\\1/si', $match[1], $rel_match );
		if ( $rel_match ) {
			$rel_attr = $rel_match[2];
		}

		// Set or modify the rel attribute as necessary
		if ( empty( $rel_attr ) ) {
			return '<a ' . $match[1] . ' rel="noopener noreferrer">';
		} else {
			$existing_rels = explode( ' ', $rel_attr );
			$existing_rels = array_unique( array_merge( $replacement_rel_arr, $existing_rels ) );

			return '<a ' . str_replace( $rel_attr, implode( ' ', $existing_rels ), $match[1] ) . '>';
		}
	} else {
		// If this is not an external link, just return the matched string
		return '<a ' . $match[1] . '>';
	}
}

