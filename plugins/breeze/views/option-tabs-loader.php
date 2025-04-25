<?php

/**
 * Handles the ajax load for tabs.
 */
class Breeze_Tab_Loader {

	function __construct() {
		add_action( 'wp_ajax_breeze_load_options_tab', array( &$this, 'breeze_option_tab_display' ) );
	}

	function breeze_option_tab_display() {
		$accepted_tabs = array(
			'basic',
			'file',
			'preload',
			'advanced',
			'database',
			'cdn',
			'tools',
			'faq',
			'varnish',
			'heartbeat',
		);

		$requested_tab = ( isset( $_GET['request_tab'] ) ? $_GET['request_tab'] : 'basic' );

		if ( ! in_array( $requested_tab, $accepted_tabs, true ) || true === breeze_is_restricted_access( true ) ) {
			die( '<h3>The requested tab does not exist</h3>' );
		}
		ob_start();
		Breeze_Admin::render( $requested_tab );
		$html_tab_data = ob_get_contents();
		ob_end_clean();

		echo $html_tab_data;
		wp_die();
	}
}

new Breeze_Tab_Loader();
