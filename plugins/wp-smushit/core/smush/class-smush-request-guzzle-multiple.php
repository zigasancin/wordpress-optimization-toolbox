<?php

namespace Smush\Core\Smush;

use Smush\Core\File_System;
use Smush\Core\Server_Utils;
use Smush_Vendor\GuzzleHttp\Client;
use Smush_Vendor\GuzzleHttp\Pool;
use Smush_Vendor\GuzzleHttp\Psr7\Response;
use Smush_Vendor\GuzzleHttp\Psr7\Utils;
use Smush_Vendor\GuzzleHttp\Exception\RequestException;
use Smush_Vendor\GuzzleHttp\Exception\ConnectException;
use Smush_Vendor\GuzzleHttp\Exception\ClientException;
use Smush_Vendor\GuzzleHttp\Exception\ServerException;
use WP_Error;

class Smush_Request_Guzzle_Multiple extends Smush_Request {
	/**
	 * @var Client
	 */
	private $client;
	/**
	 * @var bool
	 */
	private $streaming_enabled;
	/**
	 * @var Server_Utils
	 */
	private $server_utils;

	public function __construct( $streaming_enabled = true, $extra_headers = array() ) {
		$this->client            = new Client();
		$this->streaming_enabled = $streaming_enabled;
		$this->server_utils      = new Server_Utils();

		parent::__construct( $streaming_enabled, $extra_headers );
	}

	public function do_requests( array $files_data ) {
		$responses         = array();
		$request_generator = $this->make_request_generator();
		$pool              = new Pool( $this->client, $request_generator( $files_data ), array(
			'concurrency' => count( $files_data ),
			'fulfilled'   => function ( $response, $size_key ) use ( $files_data, &$responses ) {
				$file_data = $files_data[ $size_key ];

				// Convert to a response that looks like standard WP HTTP API responses
				$response = $this->multi_to_singular_response( $response );

				$this->do_action( $response, $file_data );

				// Call the actual on complete callback
				$responses[ $size_key ] = call_user_func( $this->get_on_complete(), $response, $size_key, $file_data );
			},
			'rejected'    => function ( $reason, $size_key ) use ( $files_data, &$responses ) {
				list( $reason_code, $reason_message ) = $this->extract_error_details( $reason );

				$file_data = $files_data[ $size_key ];

				$response = new WP_Error( $reason_code, $reason_message );
				$this->do_action( $response, $file_data );

				// Call the actual on complete callback
				$responses[ $size_key ] = call_user_func( $this->get_on_complete(), $response, $size_key, $file_data );
			},
		) );

		$pool->promise()->wait();

		return $responses;
	}

	private function extract_error_details( $error ) {
		$error_code    = '';
		$error_message = '';
		if ( is_a( $error, '\Exception' ) ) {
			$error_code    = $error->getCode();
			$error_message = $error->getMessage();
		} elseif ( is_string( $error ) ) {
			$error_code    = $error;
			$error_message = $error;
		}

		if ( empty( $error_code ) && ! empty( $error_message ) ) {
			$error_message_lowercase = strtolower( $error_message );
			if ( $error instanceof ConnectException ) {
				$error_code = $this->map_connect_exception_error_code( $error_message_lowercase );
			} elseif ( $error instanceof ClientException ) {
				$error_code = 'client-error';
			} elseif ( $error instanceof ServerException ) {
				$error_code = 'server-error';
			} elseif ( $error instanceof RequestException ) {
				$error_code = 'request-error';
			}
		}

		if ( empty( $error_code ) ) {
			$error_code    = 'unknown-error';
			$error_message = $error_message ? $error_message : 'An unknown error occurred when trying to send the request.';
		}

		return array( $error_code, $error_message );
	}

	private function map_connect_exception_error_code( $error_message_lowercase ) {
		$error_map = array(
			'curl error 35'          => 'ssl-error',
			'ssl'                    => 'ssl-error',
			'curl error 28'          => 'timeout-error',
			'timed out'              => 'timeout-error',
			'curl error 6'           => 'host-resolution-error',
			'could not resolve host' => 'host-resolution-error',
			'curl error 7'           => 'connection-failed-error',
			'failed to connect'      => 'connection-failed-error',
		);

		foreach ( $error_map as $error_string => $code ) {
			if ( false !== strpos( $error_message_lowercase, $error_string ) ) {
				return $code;
			}
		}

		return 'connection-error';
	}

	/**
	 * @param $guzzle_response Response
	 *
	 * @return array
	 */
	private function multi_to_singular_response( $guzzle_response ) {
		return array(
			'body'     => $guzzle_response->getBody()->getContents(),
			'response' => array( 'code' => $guzzle_response->getStatusCode() ),
		);
	}

	/**
	 * @return \Closure
	 */
	private function make_request_generator(): \Closure {
		return function ( $files_data ) {
			foreach ( $files_data as $size_key => $size_file_data ) {
				yield $size_key => function () use ( $size_file_data ) {
					list( $file_path ) = $this->get_file_path_and_url( $size_file_data );

					return $this->client->postAsync( $this->get_url(), array(
						'headers'    => $this->get_api_request_headers( $file_path ),
						'body'       => $this->get_body( $file_path ),
						'timeout'    => $this->get_timeout(),
						'user-agent' => $this->get_user_agent(),
					) );
				};
			}
		};
	}

	private function get_body( $file_path ) {
		if ( $this->streaming_enabled ) {
			return Utils::streamFor( fopen( $file_path, 'rb' ) );
		} else {
			return $this->get_full_file_contents( $file_path );
		}
	}

	/**
	 * @param $response
	 * @param $file_data
	 *
	 * @return void
	 */
	private function do_action( $response, $file_data ) {
		list( $file_path ) = $this->get_file_path_and_url( $file_data );

		do_action( 'smush_http_api_debug', $response, array(
			'url'        => $this->get_url(),
			'headers'    => $this->get_api_request_headers( $file_path ),
			'type'       => 'POST',
			'data'       => "[streamed $file_path]",
			'timeout'    => $this->get_timeout(),
			'user-agent' => $this->get_user_agent(),
		) );
	}

	public function is_supported() {
		$curl_version              = function_exists( 'curl_version' ) ? curl_version() : array( 'version' => 0 );
		$curl_version_supported    = version_compare( $curl_version['version'], '7.19.4', '>=' );
		$allow_url_fopen_supported = $this->server_utils->is_function_supported( 'allow_url_fopen' );
		$php_version_supported     = version_compare( PHP_VERSION, '7.2.5', '>=' );

		return $php_version_supported && ( $allow_url_fopen_supported || $curl_version_supported );
	}
}
