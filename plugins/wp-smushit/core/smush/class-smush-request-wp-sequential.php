<?php

namespace Smush\Core\Smush;

use Smush\Core\Api\Backoff;

class Smush_Request_WP_Sequential extends Smush_Request {
	/**
	 * @var Backoff
	 */
	private $backoff;
	/**
	 * @var int
	 */
	private $retry_attempts;
	/**
	 * @var int
	 */
	private $retry_wait;

	public function __construct( $streaming_enabled = true, $extra_headers = array() ) {
		$this->backoff        = new Backoff();
		$this->retry_attempts = WP_SMUSH_RETRY_ATTEMPTS;
		$this->retry_wait     = WP_SMUSH_RETRY_WAIT;

		parent::__construct( $streaming_enabled, $extra_headers );
	}

	public function do_requests( array $files_data ) {
		$responses = array();
		foreach ( $files_data as $size_key => $file_data ) {
			$responses[ $size_key ] = $this->do_request( $file_data, $size_key );
		}

		return $responses;
	}

	private function get_api_request_args( $file_path ) {
		return array(
			'headers'    => $this->get_api_request_headers( $file_path ),
			'body'       => $this->get_full_file_contents( $file_path ),
			'timeout'    => $this->get_timeout(),
			'user-agent' => $this->get_user_agent(),
		);
	}

	/**
	 * @param array $request
	 *
	 * @return array|\WP_Error
	 */
	private function make_request_with_backoff( array $request ) {
		return $this->backoff->set_wait( $this->retry_wait )
		                     ->set_max_attempts( $this->retry_attempts )
		                     ->enable_jitter()
		                     ->set_decider( array( $this, 'should_retry' ) )
		                     ->run( function () use ( $request ) {
			                     return wp_remote_post( $this->get_url(), $request );
		                     } );
	}

	public function should_retry( $response ) {
		return $this->retry_attempts > 0 && (
				is_wp_error( $response )
				|| 200 !== wp_remote_retrieve_response_code( $response )
			);
	}

	/**
	 * @param $file_data
	 * @param $size_key
	 *
	 * @return mixed
	 */
	public function do_request( $file_data, $size_key ) {
		list( $file_path ) = $this->get_file_path_and_url( $file_data );
		$request  = $this->get_api_request_args( $file_path );
		$response = $this->make_request_with_backoff( $request );

		do_action( 'smush_http_api_debug', $response, $request );

		return call_user_func( $this->get_on_complete(), $response, $size_key, $file_data );
	}

	/**
	 * @param int $retry_attempts
	 */
	public function set_retry_attempts( int $retry_attempts ) {
		$this->retry_attempts = $retry_attempts;
	}

	public function is_supported() {
		return function_exists( 'wp_remote_post' );
	}
}
