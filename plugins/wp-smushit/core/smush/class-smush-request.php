<?php

namespace Smush\Core\Smush;

use Smush\Core\Array_Utils;
use Smush\Core\File_System;
use Smush\Core\File_Utils;
use Smush\Core\Helper;
use Smush\Core\Settings;
use WP_Smush;

/**
 * Calls the API and returns the response.
 */
abstract class Smush_Request {
	/**
	 * @var int
	 */
	private $timeout;
	/**
	 * @var int
	 */
	private $connect_timeout = 5;
	/**
	 * @var string
	 */
	private $user_agent;
	/**
	 * @var
	 */
	private $on_complete = '__return_false';
	/**
	 * @var Array_Utils
	 */
	private $array_utils;
	/**
	 * @var Settings|null
	 */
	private $settings;
	/**
	 * @var File_Utils
	 */
	private $file_utils;
	/**
	 * @var bool
	 */
	private $streaming_enabled;
	/**
	 * @var File_System
	 */
	private $fs;
	/**
	 * @var array
	 */
	private $extra_headers;

	public function __construct( $streaming_enabled = true, $extra_headers = array() ) {
		$this->streaming_enabled = $streaming_enabled;
		$this->array_utils       = new Array_Utils();
		$this->file_utils        = new File_Utils();
		$this->fs                = new File_System();
		$this->settings          = Settings::get_instance();
		$this->extra_headers     = $extra_headers;
		$this->user_agent        = WP_SMUSH_UA;
		$this->timeout           = WP_SMUSH_TIMEOUT;
	}

	public function get_on_complete() {
		return $this->on_complete;
	}

	public function set_on_complete( $on_complete ): Smush_Request {
		$this->on_complete = $on_complete;

		return $this;
	}

	public function get_connect_timeout() {
		return $this->connect_timeout;
	}

	public function get_timeout() {
		return $this->timeout;
	}

	public function get_user_agent() {
		return $this->user_agent;
	}

	public function get_url() {
		return defined( 'WP_SMUSH_API_HTTP' ) ? WP_SMUSH_API_HTTP : WP_SMUSH_API;
	}

	/**
	 * @return string[]
	 */
	public function get_api_request_headers( $file_path ) {
		$headers = array_merge(
			array(
				'accept' => 'application/json', // The API returns JSON.
				'exif'   => $this->settings->get( 'strip_exif' ) ? 'false' : 'true',
			),
			$this->get_extra_headers()
		);

		if ( $this->streaming_enabled ) {
			$headers['response'] = 'image_url';
		} else {
			$headers['response'] = 'image_full';
		}

		$headers['content-type'] = 'application/binary';

		$headers['lossy'] = $this->settings->get_lossy_level_setting();

		// Check if premium member, add API key.
		$api_key = Helper::get_wpmudev_apikey();
		if ( ! empty( $api_key ) && WP_Smush::is_pro() ) {
			$headers['apikey'] = $api_key;

			$is_large_file = $this->file_utils->is_large_file( $file_path );
			if ( $is_large_file ) {
				$headers['islarge'] = 1;
			}
		}

		return $headers;
	}

	public function get_full_file_contents( $file_path ) {
		// Temporary increase the limit because we are about to read a full file into memory.
		wp_raise_memory_limit( 'image' );

		$contents = $this->fs->file_get_contents( $file_path );
		return empty( $contents ) ? '' : $contents;
	}

	/**
	 * @param $file_data string|array
	 *
	 * @return array
	 */
	protected function get_file_path_and_url( $file_data ): array {
		if ( is_string( $file_data ) ) {
			$file_path = $file_data;
			$file_url  = '';
		} else {
			$file_path = $this->array_utils->get_array_value( $file_data, 'path' );
			$file_url  = $this->array_utils->get_array_value( $file_data, 'url' );
		}
		return array( $file_path, $file_url );
	}

	public function get_extra_headers() {
		return $this->extra_headers;
	}

	public function set_extra_headers( $extra_headers ): Smush_Request {
		$this->extra_headers = $extra_headers;
		return $this;
	}

	public function do_request( $file_data, $size_key ) {
		return false;
	}

	public function set_streaming_enabled( $streaming_enabled ) {
		$this->streaming_enabled = $streaming_enabled;
		return $this;
	}

	/**
	 * @param $files_data array
	 *
	 * @return mixed
	 */
	abstract public function do_requests( array $files_data );

	abstract public function is_supported();
}
