<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Breeze_Rollback
 */
class Breeze_Rollback {


	/**
	 * Holds the class object.
	 *
	 * @access public
	 * @var object Instance of instantiated WooSupercharge class.
	 */
	public static $instance;


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @access public
	 * @return object The WooSupercharge object.
	 * @since 1.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Breeze_Rollback();
            self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Adds actions and filters for settins and menu and js.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'breeze_admin_menu' ), 20 );
        // Adds the page on multisite env.
        add_action( 'network_admin_menu', array( $this, 'breeze_admin_menu' ), 20 );
	}

    public function breeze_admin_menu() {
            // Add it in a native WP way, like WP updates do... (a dashboard page)
            add_dashboard_page(
                __( 'Breeze Rollback', 'breeze' ),
                __( 'Breeze Rollback', 'breeze' ),
                'update_plugins',
                'breeze-rollback',
                array( $this, 'breeze_rollback_html' )
            );
    }

    public function breeze_rollback_html() {
        // Permissions check
        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_die( __( 'You do not have sufficient permissions to perform rollbacks for this site.', 'breeze' ) );
        }
    
        // Get the necessary class
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    
        require_once( BREEZE_PLUGIN_DIR . 'inc/rollback/class-breeze-plugin-updater.php' );
    
        $args = $_GET;

        $args['plugin_file'] = 'breeze/breeze.php';

        check_admin_referer( 'breeze_rollback_nonce' );
    
        if ( empty( $args['breeze_version'] ) ) {
            _e( 'Your Request is missing a required query param', 'breeze' );
            return;
        }
    
        // This is a plugin rollback.
        $title   = 'Breeze';
        $url     = esc_url( 'index.php?page=breeze-rollback&plugin_file=' . $args['plugin_file'] . '&action=upgrade-plugin' );
        $plugin  = 'breeze';
        $version = sanitize_text_field( $args['breeze_version'] );
    
        $upgrader = new Breeze_Plugin_Updater( new Plugin_Upgrader_Skin( compact( "title","url","plugin","version" ) ) );
    
        $result   = $upgrader->breeze_rollback( plugin_basename( $args['plugin_file'] ) );

        _e( 'Breeze cache cleared successfully.', 'breeze' );
    }
}

Breeze_Rollback::get_instance();