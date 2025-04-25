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

define( 'BREEZE_PLUGIN_FULL_PATH', dirname( __DIR__ ) . '/' );
require_once BREEZE_PLUGIN_FULL_PATH . 'inc/class-breeze-query-strings-rules.php';

/**
 * Get base path for the page cache directory.
 *
 * @param bool $is_network Whether to include the blog ID in the path on multisite.
 *
 * @param int $blog_id_requested Folder for specific blog ID.
 *
 * @return string
 */
function breeze_get_cache_base_path( $is_network = false, $blog_id_requested = 0 ) {

	if ( empty( $blog_id_requested ) ) {
		$blog_id_requested = isset( $GLOBALS['breeze_config']['blog_id'] ) ? $GLOBALS['breeze_config']['blog_id'] : 0;
	}

	if ( ! $is_network && is_multisite() ) {

		if ( empty( $blog_id_requested ) ) {
			global $blog_id;
			$path = rtrim( WP_CONTENT_DIR, '/\\' ) . '/cache/breeze/';
			if ( ! empty( $blog_id ) ) {
				$path .= abs( intval( $blog_id ) ) . DIRECTORY_SEPARATOR;
			}
		} else {
			$path  = rtrim( WP_CONTENT_DIR, '/\\' ) . '/cache/breeze/';
			$path .= abs( intval( $blog_id_requested ) ) . DIRECTORY_SEPARATOR;
		}
	} else {
		$path = rtrim( WP_CONTENT_DIR, '/\\' ) . '/cache/breeze/';
	}

	return $path;
}

/**
 * Get the total size of a directory (including subdirectories).
 *
 * @param string $dir
 * @param array $exclude
 *
 * @return int
 */
function breeze_get_directory_size( $dir, $exclude = array() ) {
	$size = 0;
	if ( empty( $dir ) ) {
		$dir = '';
	}
	foreach ( glob( rtrim( $dir, '/' ) . '/*', GLOB_NOSORT ) as $path ) {
		if ( is_file( $path ) ) {
			if ( in_array( basename( $path ), $exclude ) ) {
				continue;
			}

			$size += filesize( $path );
		} else {
			$size += breeze_get_directory_size( $path, $exclude );
		}
	}

	return $size;
}

function breeze_current_user_type( $as_dir = true ) {
	$all_roles = array();
	if ( isset( $GLOBALS['breeze_config']['wp-user-roles'] ) ) {
		$all_roles = $GLOBALS['breeze_config']['wp-user-roles'];
	}

	foreach ( $all_roles as $user_role ) {
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			if ( current_user_can( $user_role ) ) {
				return $user_role . ( true === $as_dir ? '/' : '' );
			}
		}
	}

	return '';
}


/**
 * Fetches all the current user roles in the wp install, including custom user roles.
 *
 * @return array
 * @since 1.2.5
 */
function breeze_all_wp_user_roles() {
	global $wp_roles;

	if ( empty( $wp_roles ) || is_wp_error( $wp_roles ) ) {
		return array();
	}

	$current_roles = array();
	$roles         = $wp_roles->roles;

	foreach ( $roles as $defined_user_role => $data ) {
		$current_roles[] = $defined_user_role;
	}

	return $current_roles;

}

function breeze_all_user_folders() {
	$all_roles = breeze_all_wp_user_roles();

	$roles = array(
		'',
		'administrator',
		'editor',
		'author',
		'contributor',
	);

	if ( ! empty( $all_roles ) ) {
		foreach ( $all_roles as $role ) {
			if ( ! in_array( $role, $roles, true ) ) {
				$roles[] = $role;
			}
		}
	}

	return $roles;
}

function breeze_is_feed( $url ) {

	$parse_result = parse_url( $url );

	if ( isset( $parse_result['query'] ) ) {
		if ( substr_count( $parse_result['query'], 'feed=' ) > 0 ) {
			return true;
		}
	}

	if ( isset( $parse_result['path'] ) ) {
		if ( substr_count( $parse_result['path'], '/feed' ) > 0 ) {
			return true;
		}
	}

	return false;

}


function breeze_treat_exceptions( $content ) {
	preg_match_all( '/<ins(.*)>/', $content, $matches );
	if ( ! empty( $matches ) && ( isset( $matches[0] ) && ! empty( $matches[0] ) ) ) {
		foreach ( $matches[0] as $html_tag ) {
			$decode  = html_entity_decode( $html_tag );
			$decode  = str_replace( '”', '', $decode );
			$content = str_replace( $html_tag, $decode, $content );
		}
	}

	return $content;

}

/**
 * Check for AMP based on URL.
 *
 * @param string $url Given url.
 *
 * @return bool
 */
function breeze_uri_amp_check( $url = '' ) {

	if (
		false !== strpos( $url, '/amp/' ) ||
		false !== strpos( $url, 'amp=1' ) ||
		false !== strpos( $url, '?amp' )
	) {
		return true;
	}

	return false;
}


if ( defined( 'AUTH_SALT' ) && ! empty( AUTH_SALT ) ) {
	define( 'BREEZE_WP_COOKIE_SALT', AUTH_SALT );
} else {
	define( 'BREEZE_WP_COOKIE_SALT', 'cQCDC6Z^R#FE*WpRHqfaWOfw!1baSb*NxeOP1B1^u9@X7x*%ah' );
}
define( 'BREEZE_WP_COOKIE', 'breeze_folder_name' );

add_action( 'set_auth_cookie', 'breeze_auth_cookie_set', 15, 6 );
function breeze_auth_cookie_set( $auth_cookie, $expire, $expiration, $user_id, $scheme, $token ) {

	if ( ! apply_filters( 'send_auth_cookies', true ) ) {
		return;
	}

	// get_userdata
	$current_user_roles = (array) get_userdata( $user_id )->roles;
	//$role               = reset( $current_user_roles );

	$all_roles = array();
	foreach ( $current_user_roles as $index => $one_role ) {
		$all_roles[] = sha1( BREEZE_WP_COOKIE_SALT . $one_role );
	}
	$role   = implode( '|&&&|', $all_roles );
	$secure = is_ssl();

	// Front-end cookie is secure when the auth cookie is secure and the site's home URL uses HTTPS.
	$secure_logged_in_cookie = $secure && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );
	$secure_logged_in_cookie = apply_filters( 'secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure );

	setcookie( BREEZE_WP_COOKIE, $role, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
}

add_action( 'clear_auth_cookie', 'breeze_auth_cookie_clear' );

function breeze_auth_cookie_clear() {
	/** This filter is documented in wp-includes/pluggable.php */
	if ( ! apply_filters( 'send_auth_cookies', true ) ) {
		return;
	}
	setcookie( BREEZE_WP_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );

}

add_action( 'init', 'breeze_auth_cookie_set_init', 5 );

function breeze_auth_cookie_set_init() {

	if ( is_user_logged_in() && ! isset( $_COOKIE[ BREEZE_WP_COOKIE ] ) || empty( BREEZE_WP_COOKIE ) ) {

		if ( ! apply_filters( 'send_auth_cookies', true ) ) {
			return;
		}

		$current_user       = wp_get_current_user();
		$current_user_roles = (array) $current_user->roles;
		//$role               = reset( $current_user_roles );
		$all_roles = array();
		foreach ( $current_user_roles as $index => $one_role ) {
			$all_roles[] = sha1( BREEZE_WP_COOKIE_SALT . $one_role );
		}
		$role   = implode( '|&&&|', $all_roles );
		$secure = is_ssl();

		// Front-end cookie is secure when the auth cookie is secure and the site's home URL uses HTTPS.
		$secure_logged_in_cookie = $secure && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		$secure_logged_in_cookie = apply_filters( 'secure_logged_in_cookie', $secure_logged_in_cookie, $current_user->ID, $secure );
		$expiration              = time() + apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $current_user->ID, true );
		$expire                  = $expiration + ( 12 * HOUR_IN_SECONDS );

		setcookie( BREEZE_WP_COOKIE, $role, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
	}
}

/**
 * Checks the current used data and decide to restrict certain actions
 * or allow them. ( Such as saving breeze options )
 *
 * If $bool_response is true, then you should restrict an action.
 *
 * @see https://wordpress.org/support/article/roles-and-capabilities/#administrator
 * Only administrators have manage_options capability by default.
 *
 * @param bool $bool_response If false it throws the forbidden header, if true it will respond with true/false.
 *
 * @return bool|void
 * @since 2.0.3
 */
function breeze_is_restricted_access( $bool_response = false ) {
	// User not authenticated can't change anything.
	if ( ! is_user_logged_in() ) {
		if ( false === $bool_response ) {
			header( 'Status: 403 Forbidden' );
			header( 'HTTP/1.1 403 Forbidden' );
			exit;
		} else {
			return true;//restrict the access.
		}
	}

	// $user          = wp_get_current_user();
	// $allowed_roles = array( 'administrator' );
	//  ! array_intersect( $allowed_roles, $user->roles ) ||

	// Only allow administrators to handle Breeze data.
	// Manage Options is a capability only allowed to administrators by default.
	// Can be given to other users, but they do not have it by default.
	if ( ! current_user_can( 'manage_options' ) ) {
		if ( false === $bool_response ) {
			header( 'Status: 403 Forbidden' );
			header( 'HTTP/1.1 403 Forbidden' );
			exit;
		} else {
			return true;//restrict the access.
		}
	}

	if ( true === $bool_response ) {
		return false; // Do not restrict.
	}
}

function breeze_which_role_folder( $hash = '' ) {
	if ( empty( $hash ) ) {
		return false;
	}

	if ( isset( $GLOBALS['breeze_config'] ) && isset( $GLOBALS['breeze_config']['wp-user-roles'] ) ) {
		$cache_folders = $GLOBALS['breeze_config']['wp-user-roles'];
	} else {
		return '';
	}

	$hash_roles = explode( '|&&&|', $hash );

	$user_has_roles = array();

	if ( ! empty( $cache_folders ) ) {
		foreach ( $cache_folders as $folder ) {
			$coded = sha1( BREEZE_WP_COOKIE_SALT . $folder );

			if ( in_array( $coded, $hash_roles ) ) {
				$user_has_roles[] = $folder;
			}
		}
	}

	return $user_has_roles;
}


function breeze_load_delay_script() {

	$delay_script_js = <<<SCRIPT_TEST
		<script>
var breeze_is_loading=!1,breeze_event_name="breeze-event";function Breeze_Queue(){this.breeze_elements=[]}Breeze_Queue.prototype.enqueue=function(e){this.breeze_elements.push(e)},Breeze_Queue.prototype.dequeue=function(){return this.breeze_elements.shift()},Breeze_Queue.prototype.isEmpty=function(){return 0==this.breeze_elements.length},Breeze_Queue.prototype.peek=function(){return this.isEmpty()?void 0:this.breeze_elements[0]},Breeze_Queue.prototype.length=function(){return this.breeze_elements.length},Breeze_Queue.prototype.fetch_array=function(){return this.breeze_elements},Breeze_Queue.prototype.get_current=function(){return this.breeze_elements[0]};let breeze_scripts_queue=new Breeze_Queue;function breeze_htmlspecialchars_decode(e,t){let n=0,r=0,u=!1;void 0===t&&(t=2),e=e.toString().replace(/&lt;/g,"<").replace(/&gt;/g,">");const i={ENT_NOQUOTES:0,ENT_HTML_QUOTE_SINGLE:1,ENT_HTML_QUOTE_DOUBLE:2,ENT_COMPAT:2,ENT_QUOTES:3,ENT_IGNORE:4};if(0===t&&(u=!0),"number"!=typeof t){for(t=[].concat(t),r=0;r<t.length;r++)0===i[t[r]]?u=!0:i[t[r]]&&(n|=i[t[r]]);t=n}return t&i.ENT_HTML_QUOTE_SINGLE&&(e=e.replace(/&#0*39;/g,"'")),u||(e=e.replace(/&quot;/g,'"')),e=e.replace(/&amp;/g,"&")}window.addEventListener("DOMContentLoaded",e=>{document.querySelectorAll("div.breeze-scripts-load").forEach(function(e){breeze_scripts_queue.enqueue(e)});const t=function(){var e=breeze_scripts_queue.get_current();return new Promise(function(n,r){if(void 0!==e){var u=e.dataset.file,i=parseInt(u),o=e.dataset.async,s=e.dataset.defer,c=e.dataset.locate,_=e.textContent||e.innerText;_=_.trim(),i=1===i,s="true"===s,o="true"===o;const r=document.createElement("script");if(!0===i?(!0===o&&(r.async="async"),!0===s&&(r.defer="defer"),r.async=!1,r.type="text/javascript",r.src=_):(r.type="text/javascript",r.innerHTML=breeze_htmlspecialchars_decode(_,"ENT_QUOTES")),!0===i)r.onload=function(){if(breeze_is_loading=!1,window.CustomEvent){var e=new CustomEvent(breeze_event_name,{detail:{file_script:_}});document.body.dispatchEvent(e)}breeze_scripts_queue.dequeue(),setTimeout(function(){t()},1)},r.addEventListener("load",function(){n(r)});else{if(window.CustomEvent){var a=new CustomEvent(breeze_event_name,{detail:{file_script:_}});document.body.dispatchEvent(a)}breeze_scripts_queue.dequeue(),setTimeout(function(){t()},1)}"footer"===c?e.parentNode.insertBefore(r,e):document.getElementsByTagName("head")[0].appendChild(r),e.remove()}else breeze_scripts_queue.dequeue(),breeze_scripts_queue.isEmpty()||t()})};setTimeout(function(){t()},1)});
	    </script>
SCRIPT_TEST;

	return $delay_script_js;
}

function breeze_currency_switcher_cache() {

	$currency = '';
	if ( isset( $GLOBALS['breeze_config']['curcy-wmc-type'] ) ) {
		$currency_storage = $GLOBALS['breeze_config']['curcy-wmc-type'];
		$name             = 'wmc_current_currency';

		if ( 'session' === $currency_storage ) {
			if ( ! session_id() ) {
				@session_start();
				$currency = isset( $_SESSION[ $name ] ) ? $_SESSION[ $name ] : '';
			}
		}
		if ( 'cookie' === $currency_storage ) {
			if ( empty( $currency ) ) {
				$currency = isset( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ] : '';

			}
		}
	}

	if ( empty( $currency ) ) {
		if ( isset( $GLOBALS['_SERVER'], $GLOBALS['_SERVER']['REQUEST_URI'] ) ) {
			$the_path = trim( $GLOBALS['_SERVER']['REQUEST_URI'], '/' );

			if ( ! empty( $the_path ) ) {
				$county_list = breeze_all_country_codes();
				if ( false !== strpos( $the_path, '/' ) ) {
					$e = explode( '/', $the_path );
					if ( ! empty( $e ) ) {
						$the_path = $e[0];
					}
				}

				$the_path = strtoupper( $the_path );
				if ( array_key_exists( $the_path, $county_list ) ) {
					$currency = $currency . $the_path;

				}
			}
		}
	}

	if ( function_exists( 'weglot_get_current_language' ) ) {
		$currency = $currency . weglot_get_current_language();
	}

	if ( isset( $_COOKIE['aelia_cs_selected_currency'] ) ) {
		$currency = trim( $_COOKIE['aelia_cs_selected_currency'] );
	}

	if ( is_string( $currency ) && ! empty( $currency ) ) {
		$currency = mb_strtolower( $currency );
	}

	return $currency;
}

/**
 * @return mixed|null
 */
function breeze_is_script_ignored_from_delay( $script = '' ) {
	if ( empty( $script ) ) {
		return false;
	}

	$not_delayed     = false;
	$scripts_ignored = apply_filters(
		'default_scripts_gnore_from_delay',
		array(
			'gtag(',
			'ga(',
			'google-analytics.com/analytics.js',
			'googletagmanager.com',
			'GoogleAnalyticsObject',
		)
	);

	if ( is_array( $scripts_ignored ) && ! empty( $scripts_ignored ) ) {

		foreach ( $scripts_ignored as $list_script ) {
			if ( false !== strpos( $script, $list_script ) ) {
				$not_delayed = true;
				break;
			}
		}

		return $not_delayed;
	} else {
		return false;
	}
}

function breeze_all_country_codes() {
	return array(
		'AF' => 'Afghanistan',
		'AX' => 'Åland Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AQ' => 'Antarctica',
		'AG' => 'Antigua and Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'PW' => 'Belau',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BQ' => 'Bonaire, Saint Eustatius and Saba',
		'BA' => 'Bosnia and Herzegovina',
		'BW' => 'Botswana',
		'BV' => 'Bouvet Island',
		'BR' => 'Brazil',
		'IO' => 'British Indian Ocean Territory',
		'BN' => 'Brunei',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo (Brazzaville)',
		'CD' => 'Congo (Kinshasa)',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CW' => 'Cura&ccedil;ao',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'TF' => 'French Southern Territories',
		'GA' => 'Gabon',
		'GM' => 'Gambia',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HM' => 'Heard Island and McDonald Islands',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'CI' => 'Ivory Coast',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Laos',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'KP' => 'North Korea',
		'MK' => 'North Macedonia',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PS' => 'Palestinian Territory',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RU' => 'Russia',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barth&eacute;lemy',
		'SH' => 'Saint Helena',
		'KN' => 'Saint Kitts and Nevis',
		'LC' => 'Saint Lucia',
		'SX' => 'Saint Martin (Dutch part)',
		'MF' => 'Saint Martin (French part)',
		'PM' => 'Saint Pierre and Miquelon',
		'VC' => 'Saint Vincent and the Grenadines',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'ST' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia/Sandwich Islands',
		'KR' => 'South Korea',
		'SS' => 'South Sudan',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard and Jan Mayen',
		'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syria',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TL' => 'Timor-Leste',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad and Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks and Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom (UK)',
		'US' => 'United States (US)',
		'UM' => 'United States (US) Minor Outlying Islands',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VA' => 'Vatican',
		'VE' => 'Venezuela',
		'VN' => 'Vietnam',
		'VG' => 'Virgin Islands (British)',
		'VI' => 'Virgin Islands (US)',
		'WF' => 'Wallis and Futuna',
		'EH' => 'Western Sahara',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
	);
}

/**
 * Load Mobile Detect library based on PHP version
 *
 * @return \Breeze\Detection\MobileDetect|false
 */
function breeze_mobile_detect_library() {
	$call_class     = false;
	$path_to_plugin = dirname( __FILE__, 2 ) . '/';

	if ( ! class_exists( '\Breeze\Detection\MobileDetect' ) ) {
		if ( version_compare( PHP_VERSION, '7.3.0' ) >= 0 && version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
			// Mobile detect 3.74
			require_once( $path_to_plugin . 'vendor-extra/mobiledetect/build/php7/vendor/autoload.php' );
			$call_class = true;
		}

		if ( version_compare( PHP_VERSION, '8.0.0' ) >= 0 ) {
			// Mobile detect 4.8
			require_once( $path_to_plugin . 'vendor-extra/mobiledetect/build/php8/vendor/autoload.php' );
			$call_class = true;
		}
	}

	if ( class_exists( '\Breeze\Detection\MobileDetect' ) ) {
		$call_class = true;
	}

	if ( true === $call_class ) {
		return new \Breeze\Detection\MobileDetect;
	}

	return false;

}

/**
 * Get the device type.
 *
 * @return string
 */
function breeze_mobile_detect( $as_folder = true ) {
	if ( false === is_breeze_mobile_cache() ) {
		return '';
	}
	$device_type = '';

	// Cloudways server variable.
	if ( true === breeze_is_cloudways_server() ) {
		$get_cloudways_type = breeze_cache_type_return();
		if ( 'T' === $get_cloudways_type ) {
			$device_type = 'tablet';
		} elseif ( 'M' === $get_cloudways_type ) {
			$device_type = 'mobile';
		}
	} else {
		/**
		 * \Detection\MobileDetect object
		 */
		$is_library = breeze_mobile_detect_library();

		if ( false !== $is_library ) {
			try {
				if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
					$_SERVER['HTTP_USER_AGENT'] = 'empty_agent';
				}
				$is_library->setUserAgent( $_SERVER['HTTP_USER_AGENT'] );
				//code...
				$device_type = ( $is_library->isMobile() ? ( $is_library->isTablet() ? 'tablet' : 'mobile' ) : '' ); // last one can be 'desktop'
			} catch ( \Exception $e ) {
				// handle exception
				$device_type = '';
			}
		}
	}

	if ( true === $as_folder && ! empty( $device_type ) ) {
		$device_type = $device_type . '_';
	}

	return $device_type;
}

/**
 * Detect if mobile cache is enabled.
 *
 * @param bool $just_cw_server To check for Mobile Cache option value only from CW server or also Breeze options.
 *
 * @return bool|mixed
 */
function is_breeze_mobile_cache( $just_cw_server = false ) {
	$accepted_cloudways_values = array(
		'desktop',
		'tablet',
		'mobile',
	);
	if ( isset( $_SERVER['HTTP_X_DEVICE_TYPE'] ) && in_array( $_SERVER['HTTP_X_DEVICE_TYPE'], $accepted_cloudways_values, true ) ) {
		return true;
	}

	// just return false if the server is not CLoudWays.
	if ( true === $just_cw_server ) {
		return false;
	}

	if ( isset( $GLOBALS['breeze_config']['cache_options']['breeze-mobile-separate'] ) ) {
		return filter_var( $GLOBALS['breeze_config']['cache_options']['breeze-mobile-separate'], FILTER_VALIDATE_BOOLEAN );
	}

	return false;
}

/**
 * Identify server type, Cloudways or other.
 *
 * @return bool
 */
function breeze_is_cloudways_server() {

	if (
		false !== strpos( $_SERVER['DOCUMENT_ROOT'], 'cloudwaysapps' ) ||
		false !== strpos( $_SERVER['DOCUMENT_ROOT'], 'cloudwaysstagingapps' ) ||
		! empty( getenv( 'FPC_ENV' ) )
	) {
		return true;
	}

	return false;
}

function breeze_cache_type_return() {
	$accepted_cloudways_values = array(
		'desktop',
		'tablet',
		'mobile',
	);
	/**
	 * D = Desktop
	 * T = Tablet
	 * M = Mobile phone
	 */
	$return_type = 'D';
	if ( isset( $_SERVER['HTTP_X_DEVICE_TYPE'] ) && in_array( $_SERVER['HTTP_X_DEVICE_TYPE'], $accepted_cloudways_values, true ) ) {
		$cache_type = trim( $_SERVER['HTTP_X_DEVICE_TYPE'] );

		if ( 'tablet' === $cache_type ) {
			$return_type = 'T';
		} elseif ( 'mobile' === $cache_type ) {
			$return_type = 'M';
		}
	}

	return $return_type;
}

function breeze_page_provided_headers() {
	$headers_output        = array();
	$headers_output_return = array();
	if ( ! function_exists( 'apache_request_headers' ) ) {

		foreach ( $_SERVER as $key => $value ) {
			if ( 'HTTP_' === mb_strtoupper( substr( $key, 0, 5 ) ) ) {
				$key                    = str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $key, 5 ) ) ) ) );
				$headers_output[ $key ] = $value;
			} else {
				$headers_output[ $key ] = $value;
			}
		}
	} else {
		$headers_output = apache_request_headers();
	}

	if ( ! empty( $headers_output ) ) {
		foreach ( $headers_output as $header_key => $heaver_value ) {
			$headers_output_return[ strtolower( $header_key ) ] = $heaver_value;
		}
	}

	return $headers_output_return;
}

function breeze_org_versions() {

	$url = 'https://api.wordpress.org/plugins/info/1.0/breeze.json?fields=versions';

	$response = wp_remote_get( $url );
	if ( is_wp_error( $response ) ) {
		return false;
	}

	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	// Get all versions
	$versions = array_keys( $response_body['versions'] );

	// Sort versions in descending order
	usort( $versions, 'version_compare' );

	$current_version_index = array_search( BREEZE_VERSION, $versions ) + 1;

	$prev_5_versions = array_slice( $versions, $current_version_index - 5, 5 );

	$prev_5_versions = array_reverse( $prev_5_versions );

	return $prev_5_versions;
}

/**
 * Check if WooCommerce is active.
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		if ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) || class_exists( 'WooCommerce' ) ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Check if the directory is empty or not.
 *
 * @param string $dir Directory to check string absolute path.
 *
 * @return bool
 */
function breeze_is_folder_empty( string $dir = '' ): bool {
	if ( empty( $dir ) ) {
		return false;
	}

	if ( ! is_dir( $dir ) ) {
		return false;// folder does not exist.
	}

	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
	}

	// This will return an array with the contents or empty.
	$files = $wp_filesystem->dirlist( $dir );

	return empty( $files );  // True if the dirlist is empty, false otherwise
}
