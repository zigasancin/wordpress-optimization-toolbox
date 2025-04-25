<?php
// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( ! defined( 'BREEZE_PLUGIN_DIR' ) ) {
	define( 'BREEZE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Helper functions.
require_once BREEZE_PLUGIN_DIR . 'inc/helpers.php';
require_once BREEZE_PLUGIN_DIR . 'inc/functions.php';
require_once( BREEZE_PLUGIN_DIR . 'inc/breeze-configuration.php' );
//config to cache
require_once( BREEZE_PLUGIN_DIR . 'inc/cache/config-cache.php' );
// Load Store Local Files class.
require_once( BREEZE_PLUGIN_DIR . 'inc/class-breeze-store-files-locally.php' );

require_once( BREEZE_PLUGIN_DIR . 'inc/breeze-admin.php' );

Breeze_Admin::plugin_uninstall_hook();
