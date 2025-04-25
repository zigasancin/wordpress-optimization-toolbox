<?php

/**
 * @copyright 2017  Cloudways  https://www.cloudways.com
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

function set_as_network_screen() {
	if ( isset( $_GET['is-network'] ) || isset( $_POST['is-network'] ) ) {
		$is_network = false;

		if ( isset( $_GET['is-network'] ) ) {
			$is_network = filter_var( $_GET['is-network'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( isset( $_POST['is-network'] ) ) {
			$is_network = filter_var( $_POST['is-network'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( true === $is_network && ! defined( 'WP_NETWORK_ADMIN' ) ) {
			define( 'WP_NETWORK_ADMIN', true );
		}
	}
}

/**
 * Retrieve site options accounting for settings inheritance.
 *
 * @param string $option_name
 * @param bool   $is_local
 *
 * @return array
 */
function breeze_get_option( $option_name, $is_local = false ) {
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

/**
 * Update site options accounting for multisite.
 *
 * @param string $option_name
 * @param mixed  $value
 * @param bool   $is_local
 */
function breeze_update_option( $option_name, $value, $is_local = false ) {
	if ( is_network_admin() ) {
		$is_local = false;
	}

	if ( ! is_multisite() || $is_local ) {
		update_option( 'breeze_' . $option_name, $value );
	} else {
		update_site_option( 'breeze_' . $option_name, $value );
	}
}

/**
 * Check whether current site should inherit network-level settings.
 *
 * @return bool
 */
function breeze_does_inherit_settings() {
	global $breeze_network_subsite_settings;

	if ( ! is_multisite() || ( ! $breeze_network_subsite_settings && is_network_admin() ) ) {
		return false;
	}

	$inherit_option = get_option( 'breeze_inherit_settings' );

	return '0' !== $inherit_option;
}

/**
 * Check if plugin is activated network-wide in a multisite environment.
 *
 * @return bool
 */
function breeze_is_active_for_network() {
	return is_multisite() && is_plugin_active_for_network( 'breeze/breeze.php' );
}

function breeze_is_supported( $check ) {
	switch ( $check ) {
		case 'conditional_htaccess':
			$return = isset( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'Apache/2.4' ) !== false;
			break;
	}

	return $return;
}

// Function to extract the base domain from a URL
function breeze_get_base_domain( $domain ) {

	if ( preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs ) ) {
		return $regs['domain'];
	}
	return false;
}

/**
 * If an array provided, the function will check all
 * array items to see if all of them are valid URLs.
 *
 * @param array $url_list list of URLs to check.
 * @param bool  $include_dns_check To include real DNS check and/or IP check or not.
 *
 * @return bool
 * @since 1.1.0
 */
function breeze_validate_urls( array $url_list = array(), bool $include_dns_check = false ): bool {
	if ( ! is_array( $url_list ) ) {
		return false;
	}

	$is_valid = false;
	foreach ( $url_list as $url ) {
		$url = trim( $url );
		if ( empty( $url ) ) {
			continue;
		}

		if ( false === strpos( $url, ':' ) ) {
			$url = 'https://' . $url;
		}

		$parsed_url = wp_parse_url( $url );
		if ( false === $parsed_url ) {
			return false;
		}

		// Encode the path to make it a valid URL.
		$encoded_path = '';
		if ( isset( $parsed_url['path'] ) ) {
			$encoded_path = implode( '/', array_map( 'rawurlencode', explode( '/', $parsed_url['path'] ) ) );
		}

		// Reconstruct the URL with the encoded path.
		$encoded_url  = ( isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '' ) . ( $parsed_url['host'] ?? '' ) . $encoded_path;
		$encoded_url .= ( isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '' );
		$encoded_url .= ( isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '' );
		$base_domain  = breeze_get_base_domain( $parsed_url['host'] ?? '' );

		if ( empty( $base_domain ) ) {
			return false;
		}

		if ( true === $include_dns_check ) {
			if ( ! checkdnsrr( $base_domain, 'ANY' ) ) {
				$ip = gethostbyname( $parsed_url['host'] );

				if ( $ip === $parsed_url['host'] ) {
					return false;
				}
			}
		}

		if ( ! filter_var( $encoded_url, FILTER_VALIDATE_URL ) ) {

			if ( false === $is_valid ) {
				$is_valid = breeze_validate_url_via_regexp( $url );
			}

			if ( false === $is_valid ) {
				$is_valid = breeze_string_contains_exclude_regexp( $url );
			}
		} else {
			$is_valid = true;
		}

		if ( false === $is_valid ) {
			break;
		}
	}

	return $is_valid;
}

function breeze_validate_the_right_extension( $url_list = array(), $extension = 'css' ) {
	if ( ! is_array( $url_list ) ) {
		return false;
	}

	$is_valid = true;
	foreach ( $url_list as $url ) {

		$is_regexp = breeze_string_contains_exclude_regexp( $url );

		if ( false === $is_regexp ) {
			$is_valid = breeze_validate_exclude_field_by_extension( $url, $extension );
		} else {
			$file_extension = breeze_get_file_extension_from_url( $url );

			if ( false !== $file_extension && strtolower( $extension ) !== $file_extension ) {
				$is_valid = false;
			}
		}

		if ( false === $is_valid ) {
			break;
		}
	}

	return $is_valid;
}

/**
 * Returns the extension for given file from url.
 *
 * @param string $url_given
 *
 * @return bool
 */
function breeze_get_file_extension_from_url( $url_given = '' ) {
	if ( empty( $url_given ) ) {
		return false;
	}

	$file_path = wp_parse_url( $url_given, PHP_URL_PATH );
	if ( ! empty( $file_path ) ) {
		$file_name = wp_basename( $file_path );
		if ( ! empty( $file_name ) ) {
			$bits = explode( '.', $file_name );
			if ( ! empty( $bits ) ) {
				$extension_id = count( $bits ) - 1;
				$extension    = strtolower( $bits[ $extension_id ] );
				$extension    = preg_replace( '/\s+/', ' ', $extension );
				if ( '*)' === $extension ) { // Exception when (.*) is the last statement instead of ending with an extension
					return false;
				}

				return $extension;
			}
		}
	}

	return false;
}

/**
 * Will search for given string in array values
 * if found, will result in an array with all entries found
 * if not found, an empty array will be resulted.
 *
 * @param string $needle
 * @param array  $haystack
 *
 * @return array
 * @since 1.1.0
 */
function breeze_is_string_in_array_values( $needle = '', $haystack = array() ) {
	if ( empty( $needle ) || empty( $haystack ) ) {
		return array();
	}
	$needle             = trim( $needle );
	$is_string_in_array = array_filter(
		$haystack,
		function ( $var ) use ( $needle ) {
			// return false;
			if ( breeze_string_contains_exclude_regexp( $var ) ) {
				return breeze_file_match_pattern( $needle, $var );
			} else {
				return strpos( $var, $needle ) !== false;
			}
		}
	);

	return $is_string_in_array;
}

/**
 * Used to check for regexp exclude pages
 *
 * @param string $needle
 * @param array  $haystack
 *
 * @return array
 * @since 1.1.7
 */
function breeze_check_for_exclude_values( $needle = '', $haystack = array() ) {
	if ( empty( $needle ) || empty( $haystack ) ) {
		return array();
	}
	$needle             = trim( $needle );
	$is_string_in_array = array_filter(
		$haystack,
		function ( $var ) use ( $needle ) {

			if ( breeze_string_contains_exclude_regexp( $var ) ) {
				return breeze_file_match_pattern( $needle, $var );
			} else {
				return false;
			}
		}
	);

	return $is_string_in_array;
}

/**
 * Will return true for Google fonts and other type of CDN link
 * that are missing the Scheme from the url
 *
 * @param string $url_to_be_checked
 *
 * @return bool
 */
function breeze_validate_url_via_regexp( $url_to_be_checked = '' ) {
	if ( empty( $url_to_be_checked ) ) {
		return false;
	}
	$regex = '((http:|https:?)?\/\/)?([a-z0-9+!*(),;?&=.-]+(:[a-z0-9+!*(),;?&=.-]+)?@)?([a-z0-9\-\.]*)\.(([a-z]{2,6})|([0-9]{1,3}\.([0-9]{1,3})\.([0-9]{1,3})))(:[0-9]{2,5})?(\/([a-z0-9+%-]\.?)+)*\/?(\?[a-z+&$_.-][a-z0-9;:@&%=+/.-/,/:]*)?(#[a-z_.-][a-z0-9+$%_.-]*)?';

	preg_match( "~^$regex$~i", $url_to_be_checked, $matches_found );

	if ( empty( $matches_found ) ) {
		return false;
	}

	return true;
}


/**
 * Used in Breeze settings to validate if the URL corresponds to the
 * added input/textarea
 * Exclude CSS must contain only .css files
 * Exclude JS must contain only .js files
 *
 * @param $file_url
 * @param string   $validate
 *
 * @return bool
 */
function breeze_validate_exclude_field_by_extension( $file_url, $validate = 'css' ) {
	if ( empty( $file_url ) ) {
		return true;
	}
	if ( empty( $validate ) ) {
		return false;
	}

	$valid      = true;
	$file_path  = wp_parse_url( $file_url, PHP_URL_PATH );
	$preg_match = preg_match( '#\.' . $validate . '$#', $file_path );
	if ( empty( $preg_match ) ) {
		$valid = false;
	}

	return $valid;
}


/**
 * Function used to determine if the excluded URL contains regexp
 *
 * @param $file_url
 * @param string   $validate
 *
 * @return bool
 */
function breeze_string_contains_exclude_regexp( $file_url, $validate = '(.*)' ) {
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
function breeze_file_match_pattern( $file_url, $pattern ) {
	$remove_pattern   = str_replace( '(.*)', 'REG_EXP_ALL', $pattern );
	$prepared_pattern = preg_quote( $remove_pattern, '/' );
	$pattern          = str_replace( 'REG_EXP_ALL', '(.*)', $prepared_pattern );
	$result           = preg_match( '/' . $pattern . '/', $file_url );

	return $result;
}

/**
 * Will return true/false if the cache headers exist.
 *
 * @return bool
 */
function is_varnish_cache_started() {

	if ( isset( $_SERVER['HTTP_X_VARNISH'] ) && is_numeric( $_SERVER['HTTP_X_VARNISH'] ) ) {
		return true;
	}

	// Return false early if varnish is disabled by the user.
	if ( isset( $data['HTTP_X_APPLICATION'] )
	&& ( 'varnishpass' === trim( $data['HTTP_X_APPLICATION'] ) || 'bypass' === trim( $data['HTTP_X_APPLICATION'] ) )
	) {
		return false;
	}

	$check_local_server = is_varnish_layer_started();
	if ( true === $check_local_server ) {
		return true;
	}

	$custom_varnish_active = get_transient( 'breeze_custom_varnish_server_active' );

	if ( false === $custom_varnish_active ) {
		$custom_varnish_active = (int) breeze_check_custom_varnish();
		set_transient( 'breeze_custom_varnish_server_active', $custom_varnish_active, 24 * HOUR_IN_SECONDS );
	}

	return (bool) $custom_varnish_active;
}

/**
 * Checks if the varnish is active on website."
 * x-cache header is checked to verify varnish presence.
 *
 * @return bool
 */
function breeze_check_custom_varnish() {

	$unique_string = time();

	$url_ping = trim( home_url() . '?breeze_check_cache_available=' . $unique_string );

	$headers = wp_get_http_headers( $url_ping );

	if ( empty( $headers ) ) {
		return false;
	}

	$headers = array_change_key_case( $headers->getAll(), CASE_LOWER );

	if ( isset( $headers['x-cache'] ) ) {
		return true;
	}

	return false;
}

/**
 * Determine if the Varnish server is up and running.
 *
 * CloudWays:
 * At server root level Varnish being disabled.
 * HTTP_X_VARNISH - does not exist or is NULL
 * HTTP_X_APPLICATION - contains varnishpass
 *
 * At Application level ( WP install ) - Varnish ON
 * At server level is ON
 * HTTP_X_VARNISH - has random numerical value
 * HTTP_X_APPLICATION - contains value different from varnishpass, usually application name.
 *
 * At Application level ( WP install ) - Varnish OFF
 * At server level is ON
 * HTTP_X_VARNISH - has random numerical value
 * HTTP_X_APPLICATION - contains value varnishpass
 *
 * @since 1.1.3
 */
function is_varnish_layer_started() {
	$data = $_SERVER;

	if ( ! isset( $data['HTTP_X_VARNISH'] ) ) {
		return false;
	}

	if ( isset( $data['HTTP_X_VARNISH'] ) && isset( $data['HTTP_X_APPLICATION'] ) ) {

		if ( 'varnishpass' === trim( $data['HTTP_X_APPLICATION'] ) ) {
			return false;
		} elseif ( 'bypass' === trim( $data['HTTP_X_APPLICATION'] ) ) {
			return false;
		} elseif ( is_null( $data['HTTP_X_APPLICATION'] ) ) {
			return false;
		}
	}

	if ( ! isset( $data['HTTP_X_APPLICATION'] ) ) {
		return false;
	}

	return true;
}

/**
 * Handles file writing.
 * Using fopen() si a lot faster than file_put_contents().
 *
 * @param string $file_path
 * @param string $content
 *
 * @return bool
 * @since 1.1.3
 */
function breeze_read_write_file( $file_path = '', $content = '' ) {
	if ( empty( $file_path ) ) {
		return false;
	}

	if (($handler = @fopen($file_path, 'w')) !== false) { // phpcs:ignore
		if ((@fwrite($handler, $content)) !== false) { // phpcs:ignore
			@fclose($handler); // phpcs:ignore
		}
	}
}


function breeze_lock_cache_process( $path = '' ) {
	$filename    = 'process.lock';
	$create_lock = fopen( $path . $filename, 'w' );
	if ( false === $create_lock ) {
		return false;
	}
	fclose( $create_lock );

	return true;
}

function breeze_is_process_locked( $path = '' ) {
	$filename = 'process.lock';
	if ( file_exists( $path . $filename ) ) {
		return true;
	}

	return false;
}

function breeze_unlock_process( $path = '' ) {
	$filename = 'process.lock';
	if ( file_exists( $path . $filename ) ) {
		@unlink( $path . $filename );

		return true;
	}

	return false;
}

function multisite_blog_id_config() {
	global $blog_id;

	$blog_id_requested = isset( $GLOBALS['breeze_config']['blog_id'] ) ? $GLOBALS['breeze_config']['blog_id'] : 0;
	if ( ! empty( $blog_id_requested ) ) {
		return $blog_id_requested;
	}

	if ( ! empty( $blog_id ) ) {
	}
}

/**
 * Purges the cache for a given URL.
 * Varnish cache and local cache.
 *
 * @param string $url The url for which to purge the cache.
 * @param false  $purge_varnish If the check was already done for Varnish server On/OFF set to true.
 * @param bool   $check_varnish If the check for Varnish was not done, set to true to check Varnish server status inside the function.
 *
 * @since 1.1.10
 */
function breeze_varnish_purge_cache( $url = '', $purge_varnish = false, $check_varnish = true ) {
	global $wp_filesystem;

	// Making sure the filesystem is loaded.
	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
	}

	// Clear the local cache using the product URL.
	if ( ! empty( $url ) && $wp_filesystem->exists( breeze_get_cache_base_path() . hash( 'sha512', $url ) ) ) {
		$wp_filesystem->rmdir( breeze_get_cache_base_path() . hash( 'sha512', $url ), true );
	}

	if ( false === $purge_varnish && true === $check_varnish ) {
		// Checks if the Varnish server is ON.
		$do_varnish_purge = is_varnish_cache_started();

		if ( false === $do_varnish_purge ) {
			return;
		}
	}

	if ( false === $purge_varnish && false === $check_varnish ) {
		return;
	}

	$parse_url = parse_url( $url );
	$pregex    = '';
	// Default method is URLPURGE to purge only one object, this method is specific to cloudways configuration
	$purge_method = 'URLPURGE';
	// Use PURGE method when purging all site
	if ( isset( $parse_url['query'] ) && ( 'breeze' === strtolower( $parse_url['query'] ) ) ) {
		// The regex is not needed as cloudways configuration purge all the cache of the domain when a PURGE is done
		$pregex       = '.*';
		$purge_method = 'PURGE';
	}
	// Determine the path
	$url_path = '';
	if ( isset( $parse_url['path'] ) ) {
		$url_path = $parse_url['path'];
	}
	// Determine the schema
	$schema = 'http://';
	if ( isset( $parse_url['scheme'] ) ) {
		$schema = $parse_url['scheme'] . '://';
	}
	// Determine the host
	$host = $parse_url['host'];

	$varnish_ip   = Breeze_Options_Reader::get_option_value( 'breeze-varnish-server-ip' );
	$varnish_host = isset( $varnish_ip ) ? $varnish_ip : '127.0.0.1';
	$purgeme      = $varnish_host . $url_path . $pregex;
	if ( ! empty( $parse_url['query'] ) && 'breeze' !== strtolower( $parse_url['query'] ) ) {
		$purgeme .= '?' . $parse_url['query'];
	}

	$ssl_verification = apply_filters( 'breeze_ssl_check_certificate', true );

	if ( ! is_bool( $ssl_verification ) ) {
		$ssl_verification = true;
	}

	if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
		$ssl_verification = false;
	}

	$request_args = array(
		'method'    => $purge_method,
		'headers'   => array(
			'Host'       => $host,
			'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
		),
		'sslverify' => $ssl_verification,
	);
	$response     = wp_remote_request( $schema . $purgeme, $request_args );
	if ( is_wp_error( $response ) || 200 !== (int) $response['response']['code'] ) {
		if ( 'https://' === $schema ) {
			$schema = 'http://';
		} else {
			$schema = 'https://';
		}
		wp_remote_request( $schema . $purgeme, $request_args );
	}
}

/**
 * Will ignore the files added into $minified_already array so that these files will not be minified twice.
 *
 * @param string $script_path local script path.
 *
 * @return bool
 * @since 1.1.9
 */
function breeze_libraries_already_minified( $script_path = '' ) {
	if ( empty( $script_path ) ) {
		return false;
	}

	$minified_already = array(
		'woocommerce-bookings/dist/frontend.js',
	);

	$library = explode( '/plugins/', $script_path );

	if ( empty( $library ) || ! isset( $library[1] ) ) {
		return false;
	}

	$library_path = $library[1];

	if ( in_array( $library_path, $minified_already ) ) {
		return true;
	}

	return false;
}

add_filter( 'breeze_js_ignore_minify', 'breeze_libraries_already_minified' );

/**
 * The Page is AMP so don't minifiy stuff.
 *
 * @return bool
 * @since 1.2.3
 */
function breeze_is_amp_page() {
	if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
		return true;
	}

	return false;
}

function breeze_rtrim_urls( $url ) {
	if ( empty( $url ) ) {
		$url = '';
	}

	return rtrim( $url, '/' );
}

/**
 * Check the CDN url to see if it's safe to use.
 *
 * @param $cdn_url
 *
 * @return false|string
 * @since 2.0.11
 */
function breeze_static_check_cdn_url( $cdn_url ) {
	if ( empty( trim( $cdn_url ) ) ) {
		return false;
	}

	$breeze_user_agent = 'breeze-cdn-check-help-user';

	$verify_host      = 2;
	$ssl_verification = apply_filters( 'breeze_ssl_check_certificate', true );
	if ( ! is_bool( $ssl_verification ) ) {
		$ssl_verification = true;
	}

	if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
		$ssl_verification = false;
		$verify_host      = 0;
	}

	$cdn_url = ltrim( $cdn_url, 'https:' );
	$cdn_url = 'https:' . $cdn_url;

	if ( false === filter_var( $cdn_url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	$connection = curl_init( 'https://sitecheck.sucuri.net/api/v3/?scan=' . $cdn_url );
	curl_setopt( $connection, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, $verify_host );
	curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, $ssl_verification );
	curl_setopt( $connection, CURLOPT_USERAGENT, $breeze_user_agent );
	curl_setopt( $connection, CURLOPT_REFERER, home_url() );

	/**
	 * Accept up to 3 maximum redirects before cutting the connection.
	 */
	curl_setopt( $connection, CURLOPT_MAXREDIRS, 3 );
	curl_setopt( $connection, CURLOPT_FOLLOWLOCATION, true );
	$the_json = curl_exec( $connection );
	curl_close( $connection );

	$is_json = json_decode( $the_json, true );
	if ( $is_json === null && json_last_error() !== JSON_ERROR_NONE ) {
		// incorrect data show error message
		$is_safe = false;
	} else {
		// decoded with success
		$is_safe = false;
		if ( isset( $is_json['warnings'], $is_json['warnings']['security'], $is_json['warnings']['security']['malware'] ) ) {
			$is_safe = 'warning';
		}
	}

	return $is_safe;
}

/**
 * Fetch homepage headers by cURL ping no cache.
 *
 * @param int     $retry How many retries.
 * @param int     $time_fresh If you want to use a custom number instead of time.
 * @param boolean $use_headers whether to use the function stream_context_set_default
 *
 * @return bool|array
 */
function breeze_helper_fetch_headers( int $time_fresh = 0 ) {
	// Code specific for Cloudways Server.

	// use time to get un-cached version.
	if ( empty( $time_fresh ) ) {
		$time_fresh = time();
	}
	$url_ping = trim( trailingslashit( home_url() ) . '?no-cache=' . $time_fresh );
	$url_ping = str_replace( 'http://', 'https://', $url_ping );

	$request = wp_remote_head( $url_ping );

	// Check for success.
	if (
		is_wp_error( $request ) ||
		! isset( $request['headers'] ) ||
		! ( 200 === $request['response']['code'] || 201 === $request['response']['code'] )
	) {
		return;
	}

	$headers = iterator_to_array( $request['headers'] );

	return $headers;
}
