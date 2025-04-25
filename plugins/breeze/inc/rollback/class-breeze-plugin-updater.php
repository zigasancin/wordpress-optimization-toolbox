<?php
/**
 * Breeze Plugin Rollback Class.
 *
 * Class that extends the WP Core Plugin_Upgrader found in core to do rollbacks.
 *
 * @copyright  : http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Breeze_Plugin_Rollback
 */
class Breeze_Plugin_Updater extends Plugin_Upgrader {

	/**
	 * Breeze Rollback.
	 *
	 * @param       $plugin
	 * @param array $args
	 *
	 * @return array|bool|\WP_Error
	 */
	public function breeze_rollback( $plugin, $args = array() ) {

		$defaults    = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->upgrade_strings();
		
		if ( 0 ) {
			$this->skin->before();
			$this->skin->set_result( false );
			$this->skin->error( 'up_to_date' );
			$this->skin->after();

			return false;
		}

		$plugin_slug = $this->skin->plugin;

		$plugin_version = $this->skin->options['version'];

		$download_endpoint = 'https://downloads.wordpress.org/plugin/';

		$url = $download_endpoint . $plugin_slug . '.' . $plugin_version . '.zip';

		$url = esc_url( $url );

        $is_plugin_active = is_plugin_active( $plugin );

        add_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ), 10, 2 );
        add_filter( 'upgrader_pre_install', array( $this, 'active_before' ), 10, 2 );
        add_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ), 10, 4 );
        add_filter( 'upgrader_post_install', array( $this, 'active_after' ), 10, 2 );

		$this->run( array(
			'package'           => $url,
			'destination'       => WP_PLUGIN_DIR,
			'clear_destination' => true,
			'clear_working'     => true,
			'hook_extra'        => array(
				'plugin' => $plugin,
				'type'   => 'plugin',
				'action' => 'update',
				'bulk'   => 'false',
			),
		) );

        remove_action( 'upgrader_process_complete', 'wp_clean_plugins_cache', 9 );
        remove_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ) );
        remove_filter( 'upgrader_pre_install', array( $this, 'active_before' ) );
        remove_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ) );
        remove_filter( 'upgrader_post_install', array( $this, 'active_after' ) );

		if ( ! $this->result || is_wp_error( $this->result ) ) {
			return $this->result;
		}

        if( $is_plugin_active ) {
            activate_plugin( $plugin );
        }

		// Force refresh of plugin update information.
		wp_clean_plugins_cache( $parsed_args['clear_update_cache'] );

		return true;
	}

}
