<?php

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

class Breeze_Bulk_Update {
	function __construct() {
		add_action( 'upgrader_process_complete', array( $this, 'breeze_after_plugin_bulk_upgrade' ), 10, 2 );
		add_action( 'wp_ajax_store_update_count', array( $this, 'store_update_count' ) );
		add_action( 'admin_footer', array( $this, 'plugins_js_script' ) );
	}


	function breeze_after_plugin_bulk_upgrade( $upgrader_object, $options ) {
		if ( 'plugin' === $options['type'] && 'update' === $options['action'] ) {
			// Get the total count of plugins saved at the start
			$total_plugin_count = get_option( 'plugins_to_be_updated_count', 0 );
			$total_plugin_count = intval( $total_plugin_count );

			// Get the current count
			$current_value = (int) get_option( 'breeze_updated_plugin_count', 0 );
			$current_value ++;
			update_option( 'breeze_updated_plugin_count', $current_value, 'no' );

			if ( ! empty( get_option( 'breeze_all_plugins_update_flag', false ) ) ) {
				$current_value      = 1;
				$total_plugin_count = 1;
			}
			// Check when all plugins are updated
			if ( $current_value >= $total_plugin_count ) {
				// Delete options after all plugins have been updated
				delete_option( 'plugins_to_be_updated_count' );
				delete_option( 'breeze_updated_plugin_count' );
				delete_option( 'breeze_all_plugins_update_flag' );
				do_action( 'breeze_clear_all_cache' );
			}
		}
	}

	function store_update_count() {
		if ( isset( $_POST['count'] ) ) {
			$count              = $_POST['count'];
			$total_plugin_count = get_option( 'plugins_to_be_updated_count', 0 );
			$total_plugin_count = intval( $total_plugin_count );
			if ( 0 === $total_plugin_count ) {
				update_option( 'plugins_to_be_updated_count', $count, 'no' );
			}
		}
		echo 'count stored';
		wp_die();
	}

	function plugins_js_script() {
		$screen = get_current_screen(); // Get the current screen

		if ( 'plugins' !== $screen->base && 'plugins-network' !== $screen->base ) {
			return;
		}
		?>
		<script>
		   jQuery( document ).on( 'wp-plugin-updating', function ( event, args ) {
			   var updateCount = jQuery( ".check-column input:checked" ).length;
			   jQuery.ajax( {
				   url: ajaxurl, // from wp_localize_script()
				   type: 'post',
				   data: {
					   action: 'store_update_count',
					   count: updateCount
				   },
				   success: function ( response ) {
					   console.log( "Data Saved: " + response );
				   }
			   } );
		   } );
		</script>
		<?php
	}

}

new Breeze_Bulk_Update();
