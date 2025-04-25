<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}


class Breeze_Api_Handler {
	var $options = array();

	public function __construct( $options ) {
		add_action( 'rest_api_init', array( $this, 'register_breeze_api_route' ) );

		$this->options = $options;
	}

	public function register_breeze_api_route() {
		register_rest_route( 'breeze/v1', '/clear-all-cache', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'breeze_clear_cache' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function breeze_clear_cache( $request ) {

		if ( $this->options["breeze-secure-api"] && $this->options["breeze-api-token"] != $request->get_param( 'key' ) ) {
			// Access is denied, set status code to 403 (Forbidden)
			$status_code   = 403;
			$response_data = array(
				'message' => 'Access Denied',
			);
		} else {
			// Access is allowed, set status code to 200 (OK)
			$status_code   = 200;
			$response_data = array(
				'message' => 'Cache Cleared',
			);

			( new Breeze_Admin )->breeze_clear_all_cache();
		}

		// Set the HTTP status code in the response
		return new WP_REST_Response( $response_data, $status_code );
	}
}