<?php

namespace Smush\Core\Smush;

use Smush\Core\Server_Utils;
use WP_Error;

class Smush_Request_WP_Multiple extends Smush_Request {
	/**
	 * @var Server_Utils
	 */
	private $server_utils;

	public function __construct( $streaming_enabled = true, $webp = false ) {
		parent::__construct( $streaming_enabled, $webp );

		$this->server_utils = new Server_Utils();
	}

	public function do_requests( array $files_data ) {
		$responses = array();
		$requests  = $this->prepare_requests( $files_data );

		self::request_multiple( $requests, array(
				'timeout'         => $this->get_timeout(),
				'connect_timeout' => $this->get_connect_timeout(),
				'user-agent'      => $this->get_user_agent(),
				'complete'        => function ( $response, $size_key ) use ( $files_data, $requests, &$responses ) {
					// Convert to a response that looks like standard WP HTTP API responses
					$response = $this->multi_to_singular_response( $response );

					$request = $requests[ $size_key ];
					do_action( 'smush_http_api_debug', $response, $request );

					// Call the actual on complete callback
					$file_data              = $files_data[ $size_key ];
					$requests[ $size_key ]  = null;
					$responses[ $size_key ] = call_user_func( $this->get_on_complete(), $response, $size_key, $file_data );
				},
			)
		);

		return $responses;
	}

	private function multi_to_singular_response( $multi_response ) {
		if ( is_a( $multi_response, self::get_requests_exception_class_name() ) ) {
			return new WP_Error(
				$multi_response->getType(),
				$multi_response->getMessage()
			);
		} else {
			return array(
				'body'     => $multi_response->body,
				'response' => array( 'code' => $multi_response->status_code ),
			);
		}
	}

	/** \Requests lib are deprecated on WP 6.2.0 */

	private static function get_wp_requests_class_name() {
		return class_exists( '\WpOrg\Requests\Requests' ) ? '\WpOrg\Requests\Requests' : '\Requests';
	}

	private static function request_multiple( $requests, $options = array() ) {
		$wp_requests_class_name = self::get_wp_requests_class_name();
		return $wp_requests_class_name::request_multiple( $requests, $options );
	}

	private static function get_requests_exception_class_name() {
		return class_exists( '\WpOrg\Requests\Exception' ) ? '\WpOrg\Requests\Exception' : '\Requests_Exception';
	}

	/**
	 * @param array $files_data
	 *
	 * @return array
	 */
	private function prepare_requests( array $files_data ): array {
		$requests = array();
		foreach ( $files_data as $size_key => $file_data ) {
			list( $file_path ) = $this->get_file_path_and_url( $file_data );

			$requests[ $size_key ] = array(
				'url'     => $this->get_url(),
				'headers' => $this->get_api_request_headers( $file_path ),
				'data'    => $this->get_full_file_contents( $file_path ),
				'type'    => 'POST',
			);
		}
		return $requests;
	}

	public function is_supported() {
		$wp_requests_class_name = self::get_wp_requests_class_name();

		return $this->server_utils->is_function_supported( 'curl_multi_exec' )
		       && method_exists( $wp_requests_class_name, "request_multiple" );
	}
}
