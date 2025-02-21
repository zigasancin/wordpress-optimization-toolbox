<?php

namespace Smush\Core\Smush;

use Smush\Core\Array_Utils;
use Smush\Core\File_System;
use Smush\Core\Helper;
use Smush\Core\Product_Analytics;
use Smush\Core\Settings;
use Smush\Core\Threads\Thread_Safe_Options;
use Smush\Core\Timer;
use Smush\Core\Upload_Dir;
use Smush_Vendor\GuzzleHttp\Client;
use Smush_Vendor\GuzzleHttp\Exception\GuzzleException;
use WP_Error;

/**
 * Takes raw image file paths and processes them through the Smush API. Replaces originals with the optimized versions.
 */
class Smusher {
	const ERROR_SSL_CERT = 'ssl_cert_error';
	const IMAGE_NOT_SAVED_FROM_URL = 'image_not_saved_from_url';
	const DEFAULT_CHUNK_SIZE = 5 * 1024 * 1024;
	const ERROR_TIME_OUT = 'time_out';
	const ERROR_POSTING_TO_API = 'error_posting_to_api';
	const RESPONSE_CODE_NON_200 = 'response_code_non_200';
	const OPTION_ID_SMUSH_ERROR_COUNTS = 'wp_smush_error_counts';
	/**
	 * @var Settings
	 */
	private $settings;
	/**
	 * @var Smush_Request
	 */
	private $request_multiple;
	/**
	 * @var Smush_Request
	 */
	private $request_sequential;
	/**
	 * @var \WDEV_Logger|null
	 */
	private $logger;
	/**
	 * @var boolean
	 */
	private $smush_parallel;
	/**
	 * @var WP_Error
	 */
	private $errors;
	/**
	 * @var WP_Error
	 */
	private $warnings;
	/**
	 * @var File_System
	 */
	private $fs;
	/**
	 * @var Upload_Dir
	 */
	private $upload_dir;
	/**
	 * @var Array_Utils
	 */
	private $array_utils;
	/**
	 * @var bool
	 */
	private $streaming_enabled;
	/**
	 * @var Product_Analytics
	 */
	private $product_analytics;
	/**
	 * @var Thread_Safe_Options
	 */
	private $thread_safe_options;

	public function __construct() {
		$this->smush_parallel = WP_SMUSH_PARALLEL;

		$this->settings            = Settings::get_instance();
		$this->logger              = Helper::logger();
		$this->errors              = new WP_Error();
		$this->warnings            = new WP_Error();
		$this->fs                  = new File_System();
		$this->upload_dir          = new Upload_Dir();
		$this->array_utils         = new Array_Utils();
		$this->product_analytics   = Product_Analytics::get_instance();
		$this->streaming_enabled   = $this->settings->streaming_enabled();
		$this->thread_safe_options = new Thread_Safe_Options();

		$this->request_multiple   = new Smush_Request_Guzzle_Multiple( $this->streaming_enabled );
		$this->request_sequential = new Smush_Request_WP_Sequential( $this->streaming_enabled );
	}

	/**
	 * @param $files_data string[]|array[]
	 *
	 * @return boolean[]|object[]
	 */
	public function smush( $files_data ) {
		$this->set_errors( new WP_Error() );
		$this->set_warnings( new WP_Error() );

		if (
			$this->smush_parallel
			&& $this->parallel_available_on_server()
		) {
			return $this->smush_parallel( $files_data );
		} else {
			return $this->smush_sequential( $files_data );
		}
	}

	/**
	 * @param $files_data string[]|array[]
	 *
	 * @return boolean[]|object[]
	 */
	private function smush_parallel( $files_data ) {
		$timer = new Timer();
		$timer->start();
		$retry     = array();
		$responses = array();
		$this->request_multiple
			->set_on_complete( function ( $response, $response_size_key, $size_file_data ) use ( &$responses, &$retry ) {
				list( $size_file_path ) = $this->get_file_path_and_url( $size_file_data );
				$parsed_response = $this->parse_response( $response, $size_file_path );

				if ( $this->is_network_error( $parsed_response ) ) {
					$retry[ $response_size_key ] = $size_file_data;

					$this->add_warnings( $parsed_response, $response_size_key );
				} else {
					$is_success_response = $this->handle_response( $parsed_response, $response_size_key, $size_file_path );
					// If the network request was successful, there are still some cases where it's best to retry
					if ( ! $is_success_response && $this->has_error_worth_retrying() ) {
						$retry[ $response_size_key ] = $size_file_data;
					} else {
						$responses[ $response_size_key ] = $is_success_response;
					}
				}
			} )->do_requests( $files_data );

		foreach ( $retry as $retry_size_key => $retry_size_file ) {
			list( $retry_file_path ) = $this->get_file_path_and_url( $retry_size_file );
			// Note that we are not sending a file URL because we want the retry to happen using the traditional approach
			// This is designed to prevent issues when a firewall is blocking the callback
			$responses[ $retry_size_key ] = $this->smush_file( $retry_file_path, $retry_size_key );
		}

		$time_elapsed = $timer->end();
		$this->maybe_disable_streaming();
		$this->maybe_change_http_setting();
		$this->maybe_track_image_url_error( $time_elapsed );
		$this->maybe_track_network_errors( $time_elapsed );

		return $responses;
	}

	private function maybe_change_http_setting() {
		$codes = array_merge( $this->errors->get_error_codes(), $this->warnings->get_error_codes() );
		if ( in_array( self::ERROR_SSL_CERT, $codes, true ) ) {
			// Switch to http protocol.
			$this->settings->set_setting( 'wp-smush-use_http', 1 );
		}
	}

	/**
	 * @param $files_data string[]|array[]
	 *
	 * @return boolean[]|object[]
	 */
	private function smush_sequential( $files_data ) {
		return $this->request_sequential
			->set_streaming_enabled( $this->streaming_enabled )
			->set_on_complete( function ( $response, $response_size_key, $size_file_data ) {
				list( $size_file_path ) = $this->get_file_path_and_url( $size_file_data );
				$parsed_response = $this->parse_response( $response, $size_file_path );
				return $this->handle_response( $parsed_response, $response_size_key, $size_file_path );
			} )->do_requests( $files_data );
	}

	/**
	 * @param $file_path string
	 * @param $size_key string
	 *
	 * @return bool|object
	 */
	public function smush_file( $file_path, $size_key = '', $file_url = '' ) {
		return $this->request_sequential
			->set_streaming_enabled( false )
			->set_on_complete( function ( $response, $size_key, $file_data ) {
				list( $file_path ) = $this->get_file_path_and_url( $file_data );
				$parsed_response = $this->parse_response( $response, $file_path );
				return $this->handle_response( $parsed_response, $size_key, $file_path );
			} )
			->do_request( $file_path, $size_key );
	}

	public function set_request_sequential( $request_sequential ) {
		$this->request_sequential = $request_sequential;

		return $this;
	}

	public function get_request_sequential() {
		return $this->request_sequential;
	}

	/**
	 * @param $parsed_response WP_Error|object
	 * @param $size_key string
	 * @param $file_path string
	 *
	 * @return bool|object
	 */
	private function handle_response( $parsed_response, $size_key, $file_path ) {
		if ( is_wp_error( $parsed_response ) ) {
			$this->add_error( $size_key, $parsed_response->get_error_code(), $parsed_response->get_error_message(), $parsed_response->get_error_data() );

			return false;
		}

		$data = $parsed_response;
		if ( $data->bytes_saved > 0 ) {
			if ( ! empty( $data->image_url ) ) {
				$saved_from_image_url = $this->save_from_image_url( $data->image_url, $file_path, $data->image_md5 );
				if ( is_wp_error( $saved_from_image_url ) ) {
					$this->add_error(
						$size_key,
						self::IMAGE_NOT_SAVED_FROM_URL,
						/* translators: %s: Error message. */
						sprintf( __( 'Smush was successful but we were unable to save from URL: %s.', 'wp-smushit' ), $saved_from_image_url->get_error_message() ),
						array(
							'original_code'    => $saved_from_image_url->get_error_code(),
							'original_message' => $saved_from_image_url->get_error_message(),
						)
					);

					return false;
				}
			} else {
				$optimized_image_saved = $this->save_smushed_image_file( $file_path, $data->image );
				if ( ! $optimized_image_saved ) {
					$this->add_error(
						$size_key,
						'image_not_saved',
						/* translators: %s: File path. */
						sprintf( __( 'Smush was successful but we were unable to save the file due to a file system error: [%s].', 'wp-smushit' ), $this->upload_dir->get_human_readable_path( $file_path ) )
					);

					return false;
				}
			}
		}

		// No need to pass image data any further
		if ( isset( $data->image ) ) {
			$data->image = null;
		}
		if ( isset( $data->image_md5 ) ) {
			$data->image_md5 = null;
		}

		// Check for API message and store in db.
		if ( ! empty( $data->api_message ) ) {
			$this->add_api_message( (array) $data->api_message );
		}

		return $data;
	}

	/**
	 * @param $input_stream resource
	 * @param $target_file_path
	 * @param $file_md5
	 * @param $chunk_size
	 *
	 * @return true|WP_Error
	 */
	protected function save_from_resource( $input_stream, $target_file_path, $file_md5, $chunk_size ) {
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$timer = new Timer();
		$timer->start();

		$error     = false;
		$temp_name = wp_tempnam();
		do {
			if ( empty( $temp_name ) ) {
				$error = new WP_Error( 'temp-file-creation-error', 'Error creating temporary file' );
				break;
			}

			$output_stream = fopen( $temp_name, "wb" );
			do {
				$chunk_copied_successfully = stream_copy_to_stream( $input_stream, $output_stream, $chunk_size );
				if ( $chunk_copied_successfully === false ) {
					break;
				}
			} while ( ! feof( $input_stream ) );

			// Close the input and output streams
			fclose( $input_stream );
			fclose( $output_stream );

			if ( $chunk_copied_successfully === false ) {
				$error = new WP_Error( 'temp-file-save-error', 'Error saving temp file' );
				break;
			}

			$hash_equals = hash_equals( $file_md5, md5_file( $temp_name ) );
			if ( ! $hash_equals ) {
				$error = new WP_Error( 'file-hash-mismatch', 'File hash mismatch' );
				break;
			}

			$target_file_name = basename( $target_file_path );
			$type             = wp_get_image_mime( $temp_name );
			if ( ! str_starts_with( $type, 'image/' ) ) {
				$error = new WP_Error(
					'invalid-file-type',
					sprintf( 'Invalid file type. Calculated type for file named %s at %s is %s', $target_file_name, $temp_name, $type )
				);
				break;
			}

			$file_copied = copy( $temp_name, $target_file_path );
			if ( ! $file_copied ) {
				$error = new WP_Error( 'error-moving-file', 'Error moving file' );
				break;
			}

			$permissions = $this->get_permissions_for_image( $target_file_path );
			chmod( $target_file_path, $permissions );
		} while ( 0 );

		@unlink( $temp_name );

		$time = $timer->end();
		if ( $error ) {
			$this->logger->notice( sprintf( 'File could not be saved: %s', $error->get_error_message() ) );
			return $error;
		} else {
			$this->logger->notice( sprintf( 'File saved successfully in %s seconds', $time ) );
			return true;
		}
	}

	public function save_from_image_url( $image_url, $target_file_path, $file_md5, $chunk_size = self::DEFAULT_CHUNK_SIZE ) {
		try {
			$client       = new Client();
			$response     = $client->get( $image_url, [
				'stream' => true,
			] );
			$input_stream = $response->getBody()->detach();

			return $this->save_from_resource( $input_stream, $target_file_path, $file_md5, $chunk_size );
		} catch ( GuzzleException $exception ) {
			$this->logger->error( sprintf( 'Error fetching image from URL: %s', $exception->getMessage() ) );

			$code = $exception->getCode();
			$code = empty( $code ) ? 'timeout' : $code;

			return new WP_Error( $code, 'Error fetching image from URL' );
		}
	}

	protected function save_smushed_image_file( $file_path, $image ) {
		$pre = apply_filters( 'wp_smush_pre_image_write', false, $file_path, $image );
		if ( $pre !== false ) {
			$this->logger->notice( 'Another plugin/theme short circuited the image write operation using the wp_smush_pre_image_write filter.' );

			// Assume that the plugin/theme responsible took care of it
			return true;
		}

		$permissions = $this->get_permissions_for_image( $file_path );

		// Save the new file
		$success = $this->put_smushed_image_file( $file_path, $image );

		chmod( $file_path, $permissions );

		return $success;
	}

	private function put_smushed_image_file( $file_path, $image ) {
		$temp_file = $file_path . '.tmp';

		$success = $this->put_image_using_temp_file( $file_path, $image, $temp_file );

		// Clean up
		if ( $this->fs->file_exists( $temp_file ) ) {
			$this->fs->unlink( $temp_file );
		}

		return $success;
	}

	private function put_image_using_temp_file( $file_path, $image, $temp_file ) {
		$file_written = file_put_contents( $temp_file, $image );
		if ( ! $file_written ) {
			return false;
		}

		$renamed = rename( $temp_file, $file_path );
		if ( $renamed ) {
			return true;
		}

		$copied = $this->fs->copy( $temp_file, $file_path );
		if ( $copied ) {
			return true;
		}

		return false;
	}

	private function add_api_message( $api_message = array() ) {
		if ( empty( $api_message ) || ! count( $api_message ) || empty( $api_message['timestamp'] ) || empty( $api_message['message'] ) ) {
			return;
		}
		$o_api_message = get_site_option( 'wp-smush-api_message', array() );
		if ( array_key_exists( $api_message['timestamp'], $o_api_message ) ) {
			return;
		}

		$message                              = array();
		$message[ $api_message['timestamp'] ] = array(
			'message' => sanitize_text_field( $api_message['message'] ),
			'type'    => sanitize_text_field( $api_message['type'] ),
			'status'  => 'show',
		);
		update_site_option( 'wp-smush-api_message', $message );
	}

	/**
	 * @param $response
	 * @param $file_path string
	 *
	 * @return object|WP_Error
	 */
	private function parse_response( $response, $file_path ) {
		$error = new WP_Error();
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			if ( strpos( $error_message, 'SSL CA cert' ) !== false ) {
				$error->add( self::ERROR_SSL_CERT, $error_message, array(
					'original_code'    => $response->get_error_code(),
					'original_message' => $error_message,
				) );

				return $error;
			} else if ( strpos( $error_message, 'timed out' ) !== false ) {
				$error->add(
					self::ERROR_TIME_OUT,
					esc_html__( "Skipped due to a timeout error. You can increase the request timeout to make sure Smush has enough time to process larger files. define('WP_SMUSH_TIMEOUT', 150);", 'wp-smushit' ),
					array(
						'original_code'    => $response->get_error_code(),
						'original_message' => $error_message,
					)
				);

				return $error;
			} else {
				$error->add(
					self::ERROR_POSTING_TO_API,
					/* translators: %s: Error message. */
					sprintf( __( 'Error posting to API: %s', 'wp-smushit' ), $error_message ),
					array(
						'original_code'    => $response->get_error_code(),
						'original_message' => $error_message,
					)
				);

				return $error;
			}
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			$non_200_body = wp_remote_retrieve_body( $response );
			$non_200_json = $non_200_body ? json_decode( $non_200_body ) : null;
			if ( ! empty( $non_200_json->data ) ) {
				// We got a pre-formatted error from the API
				$error_message = $non_200_json->data;
			} else {
				// Make an error from the response message
				$error_message = sprintf(
				/* translators: 1: Error code, 2: Error message. */
					__( 'Error posting to API: %1$s %2$s', 'wp-smushit' ),
					$response_code,
					wp_remote_retrieve_response_message( $response )
				);
			}

			$error->add( self::RESPONSE_CODE_NON_200, $error_message, array(
				'original_code'    => $response_code,
				'original_message' => "Received response code $response_code",
			) );

			return $error;
		}

		$json = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $json->success ) ) {
			$error_message = ! empty( $json->data )
				? $json->data
				: __( "Image couldn't be smushed", 'wp-smushit' );

			$error->add( 'unsuccessful_smush', $error_message );

			return $error;
		}

		if (
			empty( $json->data )
			|| empty( $json->data->before_size )
			|| empty( $json->data->after_size )
		) {
			$error->add( 'no_data', __( 'Unknown API error', 'wp-smushit' ) );

			return $error;
		}

		$data                   = $json->data;
		$data->bytes_saved      = isset( $data->bytes_saved ) ? (int) $data->bytes_saved : 0;
		$optimized_image_larger = $data->after_size > $data->before_size;
		if ( $optimized_image_larger ) {
			$error->add(
				'optimized_image_larger',
				/* translators: 1: File path, 2: Savings bytes. */
				sprintf( 'The smushed image is larger than the original image [%s] (bytes saved %d), keep original image.', $this->upload_dir->get_human_readable_path( $file_path ), $data->bytes_saved )
			);

			return $error;
		}

		if ( empty( $data->image_url ) ) {
			$image = empty( $data->image ) ? '' : $data->image;
			if ( $data->bytes_saved > 0 ) {
				// Because of the API response structure, the following should only be done when there are some bytes_saved.

				if ( $data->image_md5 !== md5( $image ) ) {
					$error_message = __( 'Smush data corrupted, try again.', 'wp-smushit' );
					$error->add( 'data_corrupted', $error_message );

					return $error;
				}

				if ( ! empty( $image ) ) {
					$data->image = base64_decode( $data->image );
				}
			}
		}

		return $data;
	}

	/**
	 * @param $response WP_Error|object
	 *
	 * @return bool
	 */
	private function is_network_error( $response ) {
		if ( ! is_wp_error( $response ) ) {
			return false;
		}

		$network_error_codes = $this->get_network_error_codes();
		foreach ( $response->get_error_codes() as $error_code ) {
			if ( in_array( $error_code, $network_error_codes, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function parallel_available_on_server() {
		return $this->request_multiple->is_supported();
	}

	/**
	 * @param bool $smush_parallel
	 *
	 * @return Smusher
	 */
	public function set_smush_parallel( $smush_parallel ) {
		$this->smush_parallel = $smush_parallel;

		return $this;
	}

	public function get_request_multiple() {
		return $this->request_multiple;
	}

	/**
	 * @param Smush_Request $request_multiple
	 *
	 * @return Smusher
	 */
	public function set_request_multiple( $request_multiple ) {
		$this->request_multiple = $request_multiple;

		return $this;
	}

	public function get_errors() {
		return $this->errors;
	}

	/**
	 * @param $errors WP_Error
	 *
	 * @return void
	 */
	private function set_errors( $errors ) {
		$this->errors = $errors;
	}

	/**
	 * @param $size_key string
	 * @param $code string
	 * @param $message string
	 *
	 * @return void
	 */
	private function add_error( $size_key, $code, $message, $data = array() ) {
		// Log the error
		$this->logger->error( "[$size_key] $message" );
		// Add the error
		$this->errors->add( $code, "[$size_key] $message" );

		if ( ! empty( $data ) ) {
			$this->errors->add_data( $data, $code );
		}
	}

	/**
	 * @param $size_key string
	 * @param $code string
	 * @param $message string
	 *
	 * @return void
	 */
	private function add_warning( $size_key, $code, $message, $data = array() ) {
		// Log the warning
		$this->logger->warning( "[$size_key] $message" );
		// Add the warning
		$this->warnings->add( $code, "[$size_key] $message" );

		if ( ! empty( $data ) ) {
			$this->warnings->add_data( $data, $code );
		}
	}

	private function has_warning( $code ) {
		return ! empty( $this->warnings->get_error_message( $code ) );
	}

	/**
	 * @param $warnings WP_Error
	 *
	 * @return void
	 */
	private function set_warnings( $warnings ) {
		$this->warnings = $warnings;
	}

	public function get_warnings() {
		return $this->warnings;
	}

	/**
	 * @param $code string
	 *
	 * @return bool
	 */
	private function has_error( $code ) {
		return ! empty( $this->errors->get_error_message( $code ) );
	}

	/**
	 * @param $file_data string|array
	 *
	 * @return array
	 */
	private function get_file_path_and_url( $file_data ): array {
		if ( is_string( $file_data ) ) {
			$file_path = $file_data;
			$file_url  = '';
		} else {
			$file_path = $this->array_utils->get_array_value( $file_data, 'path' );
			$file_url  = $this->array_utils->get_array_value( $file_data, 'url' );
		}
		return array( $file_path, $file_url );
	}

	private function get_permissions_for_image( $file_path ) {
		clearstatcache();
		$perms = fileperms( $file_path ) & 0777;
		// Some servers are having issue with file permission, this should fix it.
		if ( empty( $perms ) ) {
			// Source: WordPress Core.
			$stat  = stat( dirname( $file_path ) );
			$perms = $stat['mode'] & 0000666; // Same permissions as parent folder, strip off the executable bits.
		}

		return $perms;
	}

	private function maybe_track_image_url_error( $time_elapsed ) {
		if ( $this->has_error( self::IMAGE_NOT_SAVED_FROM_URL ) ) {
			$this->track_error( $this->errors, self::IMAGE_NOT_SAVED_FROM_URL, $time_elapsed );
		}
	}

	private function maybe_disable_streaming() {
		// If the constant is defined or disabled, do nothing.
		if ( defined( 'WP_SMUSH_USE_STREAMS' ) || ! $this->streaming_enabled ) {
			return;
		}

		$error_counts    = $this->thread_safe_options->get_site_option( self::OPTION_ID_SMUSH_ERROR_COUNTS, array() );
		$max_occurrences = empty( $error_counts ) ? 0 : max( $error_counts );
		if ( $max_occurrences < 3 ) {
			$this->count_error_types();
		} else {
			$this->settings->set( 'disable_streams', WP_SMUSH_VERSION );
		}
	}

	/**
	 * @return bool
	 */
	private function has_error_worth_retrying() {
		$errors_that_should_be_retried = array(
			self::IMAGE_NOT_SAVED_FROM_URL,
		);

		foreach ( $errors_that_should_be_retried as $error_code ) {
			if ( $this->has_error( $error_code ) ) {
				return true;
			}
		}

		return false;
	}

	protected function get_type_label() {
		return 'Classic';
	}

	private function add_warnings( $response, $size_key ) {
		if ( is_wp_error( $response ) ) {
			/**
			 * @var WP_Error $error
			 */
			$error = $response;

			$this->add_warning( $size_key, $error->get_error_code(), $error->get_error_message(), $error->get_error_data() );
		}
	}

	private function maybe_track_network_errors( $time_elapsed ) {
		foreach ( $this->get_network_error_codes() as $error_code ) {
			if ( $this->has_warning( $error_code ) ) {
				$this->track_error( $this->warnings, $error_code, $time_elapsed );
			} elseif ( $this->has_error( $error_code ) ) {
				$this->track_error( $this->errors, $error_code, $time_elapsed );
			}
		}
	}

	/**
	 * @param $haystack WP_Error
	 * @param $error_code string
	 * @param $time_elapsed
	 *
	 * @return void
	 */
	private function track_error( $haystack, $error_code, $time_elapsed ) {
		$error_data       = $haystack->get_error_data( $error_code );
		$original_code    = $this->array_utils->get_array_value( $error_data, 'original_code' );
		$original_message = $this->array_utils->get_array_value( $error_data, 'original_message' );

		if ( $original_code && $original_message ) {
			$this->product_analytics->maybe_track_error(
				$error_code,
				$original_code,
				$original_message,
				array(
					'Smush Type'   => $this->get_type_label(),
					'Time Elapsed' => $time_elapsed,
				)
			);
		}
	}

	/**
	 * @return string[]
	 */
	private function get_network_error_codes(): array {
		return array(
			self::ERROR_POSTING_TO_API,
			self::ERROR_TIME_OUT,
			self::ERROR_SSL_CERT,
			self::RESPONSE_CODE_NON_200,
		);
	}

	/**
	 * @return void
	 */
	private function count_error_types() {
		$increment_keys      = array();
		$errors_and_warnings = array_merge( $this->errors->get_error_codes(), $this->warnings->get_error_codes() );
		if ( empty( $errors_and_warnings ) ) {
			return;
		}

		foreach ( $errors_and_warnings as $code ) {
			$error_data    = $this->warnings->get_error_data( $code );
			$original_code = $this->array_utils->get_array_value( $error_data, 'original_code' );
			$full_code     = $code;

			if ( $original_code ) {
				$full_code .= "_$original_code";
			}

			$increment_keys[ $full_code ] = $full_code;
		}

		if ( ! empty( $increment_keys ) ) {
			$this->thread_safe_options->increment_values_in_site_option( self::OPTION_ID_SMUSH_ERROR_COUNTS, array_values( $increment_keys ) );
		}
	}

	public function reset_error_counts() {
		delete_site_option( Smusher::OPTION_ID_SMUSH_ERROR_COUNTS );
	}

	/**
	 * TODO: remove deprecated errors
	 */

	public function should_retry_smush( $response ) {
		_deprecated_function( __METHOD__, '3.17.0', 'Smusher::get_request_sequential()->should_retry()' );
	}

	public function curl_multi_exec_available() {
		_deprecated_function( __METHOD__, '3.17.0', 'Smusher::get_request_multiple()->is_supported()' );
	}

	public function set_retry_attempts( $retry_attempts ) {
		_deprecated_function( __METHOD__, '3.17.0', 'Smusher::get_request_sequential()->set_retry_attempts()' );
	}

	public function set_timeout( $timeout ) {
		_deprecated_function( __METHOD__, '3.17.0' );
	}
}
