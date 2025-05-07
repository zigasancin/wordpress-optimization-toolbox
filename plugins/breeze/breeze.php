<?php
/**
 * Plugin Name: Breeze
 * Description: Breeze is a WordPress cache plugin with extensive options to speed up your website. All the options including Varnish Cache are compatible with Cloudways hosting.
 * Version: 2.2.10
 * Text Domain: breeze
 * Domain Path: /languages
 * Author: Cloudways
 * Author URI: https://www.cloudways.com
 * License: GPL2
 * Network: true
 */

/**
 * @copyright 2017  Cloudways  https://www.cloudways.com
 *
 *  This plugin is inspired from WP Speed of Light by JoomUnited.
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

if ( ! defined( 'BREEZE_PLUGIN_DIR' ) ) {
	define( 'BREEZE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'BREEZE_VERSION' ) ) {
	define( 'BREEZE_VERSION', '2.2.10' );
}
if ( ! defined( 'BREEZE_SITEURL' ) ) {
	define( 'BREEZE_SITEURL', get_site_url() );
}
if ( ! defined( 'BREEZE_MINIFICATION_CACHE' ) ) {
	define( 'BREEZE_MINIFICATION_CACHE', WP_CONTENT_DIR . '/cache/breeze-minification/' );
}
if ( ! defined( 'BREEZE_CACHEFILE_PREFIX' ) ) {
	define( 'BREEZE_CACHEFILE_PREFIX', 'breeze_' );
}
if ( ! defined( 'BREEZE_MINIFICATION_EXTRA' ) ) {
	define( 'BREEZE_MINIFICATION_EXTRA', WP_CONTENT_DIR . '/cache/breeze-extra/' );
}
if ( ! defined( 'BREEZE_CACHE_CHILD_DIR' ) ) {
	define( 'BREEZE_CACHE_CHILD_DIR', '/cache/breeze-minification/' );
}
if ( ! defined( 'BREEZE_WP_CONTENT_NAME' ) ) {
	define( 'BREEZE_WP_CONTENT_NAME', '/' . wp_basename( WP_CONTENT_DIR ) );
}
if ( ! defined( 'BREEZE_BASENAME' ) ) {
	define( 'BREEZE_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'BREEZE_PLUGIN_URL' ) ) {
	// Usage BREEZE_PLUGIN_URL . "some_image.png" from plugin folder
	define( 'BREEZE_PLUGIN_URL', plugins_url() . '/' . dirname( plugin_basename( __FILE__ ) ) . '/' );
}

define( 'BREEZE_CACHE_DELAY', true );
define( 'BREEZE_CACHE_NOGZIP', true );
define( 'BREEZE_ROOT_DIR', str_replace( BREEZE_WP_CONTENT_NAME, '', WP_CONTENT_DIR ) );
// Options reader
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-options-reader.php';
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-cloudflare-helper.php';

// Compatibility checks
require_once BREEZE_PLUGIN_DIR . 'inc/plugin-incompatibility/class-breeze-incompatibility-plugins.php';
require_once BREEZE_PLUGIN_DIR . 'inc/plugin-incompatibility/class-breeze-woocs-compatibility.php';
// Check for if folder/files are writable.
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-file-permissions.php';
// AMP compatibility.
require_once BREEZE_PLUGIN_DIR . 'inc/plugin-incompatibility/breeze-amp-compatibility.php';

// Helper functions.
require_once BREEZE_PLUGIN_DIR . 'inc/helpers.php';
require_once BREEZE_PLUGIN_DIR . 'inc/functions.php';

// Version Upgrade routines
require_once BREEZE_PLUGIN_DIR . 'inc/upgrade.php';

// Handle Heartbeat options.
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-heartbeat-settings.php';

// action to purge cache
require_once BREEZE_PLUGIN_DIR . 'inc/cache/purge-varnish.php';
require_once BREEZE_PLUGIN_DIR . 'inc/cache/purge-cache.php';
require_once BREEZE_PLUGIN_DIR . 'inc/cache/purge-per-time.php';
require_once BREEZE_PLUGIN_DIR . 'inc/cache/class-purge-post-cache.php';
// Handle post exclude if shortcode.
require_once BREEZE_PLUGIN_DIR . 'inc/class-exclude-pages-by-shortcode.php';
// Handle the WP emoji library.
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-disable-emoji-option.php';
// Prefetch URLs.
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-dns-prefetch.php';

// Activate plugin hook
register_activation_hook( __FILE__, array( 'Breeze_Admin', 'plugin_active_hook' ) );
// Deactivate plugin hook
register_deactivation_hook( __FILE__, array( 'Breeze_Admin', 'plugin_deactive_hook' ) );

require_once BREEZE_PLUGIN_DIR . 'inc/breeze-admin.php';
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-prefetch.php';
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-preload-fonts.php';


// Load Store Local Files class.
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-store-files-locally.php';
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-bulk-update.php';

// Load Breeze Rollback Functionality.
if ( isset( $_GET['page'] ) && 'breeze-rollback' === $_GET['page'] ) {
	require_once BREEZE_PLUGIN_DIR . 'inc/rollback/class-breeze-rollback.php';
}

// Include cronjobs (Gravatars curently(
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-cache-cronjobs.php';
$gravatars_enabled = Breeze_Options_Reader::get_option_value( 'breeze-store-gravatars-locally' );
new Breeze_Cache_CronJobs( $gravatars_enabled );

if ( is_admin() || 'cli' === php_sapi_name() ) {

	require_once BREEZE_PLUGIN_DIR . 'inc/breeze-configuration.php';
	// config to cache
	require_once BREEZE_PLUGIN_DIR . 'inc/cache/config-cache.php';

	// cache when ecommerce installed
	require_once BREEZE_PLUGIN_DIR . 'inc/cache/ecommerce-cache.php';
	add_action(
		'init',
		function () {
			new Breeze_Ecommerce_Cache();
			Breeze_Query_Strings_Rules::when_woocommerce_settings_save();
		},
		0
	);

} elseif ( ! empty( Breeze_Options_Reader::get_option_value( 'cdn-active' ) )
		|| ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-js' ) )
		|| ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-css' ) )
		|| ! empty( Breeze_Options_Reader::get_option_value( 'breeze-minify-html' ) )
		|| ! empty( Breeze_Options_Reader::get_option_value( 'breeze-defer-js' ) )
		|| ! empty( Breeze_Options_Reader::get_option_value( 'breeze-move-to-footer-js' ) )
		|| ! empty( Breeze_Options_Reader::get_option_value( 'breeze-delay-all-js' ) )
		|| ! empty( Breeze_Options_Reader::get_option_value( 'breeze-enable-js-delay' ) )
	) {

		// Call back ob start
		ob_start( 'breeze_ob_start_callback' );
}
// Breeze API
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-api.php';
$api_enabled = Breeze_Options_Reader::get_option_value( 'breeze-enable-api' );
if ( $api_enabled ) {
	$options = array(
		'breeze-secure-api' => Breeze_Options_Reader::get_option_value( 'breeze-secure-api' ),
		'breeze-api-token'  => Breeze_Options_Reader::get_option_value( 'breeze-api-token' ),

	);
	new Breeze_Api_Handler( $options );
}

/**
 * Store files locally, First buffer controller to occur in this plugin
 */
add_action(
	'init',
	function () {
		ob_start( 'breeze_ob_start_localfiles_callback' );
	},
	5
);

// Compatibility with ShortPixel.
require_once BREEZE_PLUGIN_DIR . 'inc/compatibility/class-breeze-shortpixel-compatibility.php';
require_once BREEZE_PLUGIN_DIR . 'inc/compatibility/class-breeze-avada-cache.php';
require_once BREEZE_PLUGIN_DIR . 'inc/compatibility/class-breeze-elementor-template.php';

/**
 * Buffer to work with the contents before any changes occured
 *
 * @param $buffer
 *
 * @return array|false|int|mixed|string|string[]
 */
function breeze_ob_start_localfiles_callback( $buffer ) {

	// Store Files Locally
	if ( class_exists( 'Breeze_Store_Files' ) ) {

		$enabled_options = array();

		$options = array(
			'breeze-store-googlefonts-locally',
			'breeze-store-googleanalytics-locally',
			'breeze-store-facebookpixel-locally',
		);

		foreach ( $options as $option ) {
			$enabled_options[ $option ] = Breeze_Options_Reader::get_option_value( $option );
		}

		$store_locally = new \Breeze_Store_Files();
		$buffer        = $store_locally->init( $buffer, $enabled_options );
	}

	// Return content
	return $buffer;
}


// Call back ob start - stack
function breeze_ob_start_callback( $buffer ) {

	if ( ! empty( $_SERVER ) && ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'context=edit' ) ) {
		return $buffer;
	}

	// Get buffer from minify
	$buffer = apply_filters( 'breeze_minify_content_return', $buffer );

	if ( ! empty( Breeze_Options_Reader::get_option_value( 'cdn-active' ) ) ) {
		// Get buffer after remove query strings
		$buffer = apply_filters( 'breeze_cdn_content_return', $buffer );
	}

	// Return content
	return $buffer;
}

require_once BREEZE_PLUGIN_DIR . 'views/option-tabs-loader.php';
// Minify

require_once BREEZE_PLUGIN_DIR . 'inc/minification/breeze-minify-main.php';
require_once BREEZE_PLUGIN_DIR . 'inc/minification/breeze-minification-cache.php';
add_action(
	'init',
	function () {
		new Breeze_Minify();
	},
	0
);
// CDN Integration
if ( ! class_exists( 'Breeze_CDN_Integration' ) ) {
	require_once BREEZE_PLUGIN_DIR . 'inc/cdn-integration/breeze-cdn-integration.php';
	require_once BREEZE_PLUGIN_DIR . 'inc/cdn-integration/breeze-cdn-rewrite.php';
	add_action(
		'init',
		function () {
			new Breeze_CDN_Integration();
		},
		0
	);
}

// Refresh cache for ordered products.
require_once BREEZE_PLUGIN_DIR . 'inc/class-breeze-woocommerce-product-cache.php';
// WP-CLI commands
require_once BREEZE_PLUGIN_DIR . 'inc/wp-cli/class-breeze-wp-cli-core.php';


// Reset to default
add_action( 'breeze_reset_default', array( 'Breeze_Admin', 'plugin_deactive_hook' ), 80 );
