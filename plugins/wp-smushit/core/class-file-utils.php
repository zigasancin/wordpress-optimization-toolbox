<?php

namespace Smush\Core;

class File_Utils {
	private $file_sizes_cache = array();
	/**
	 * @var Settings|null
	 */
	private $settings;

	public function __construct() {
		$this->settings = Settings::get_instance();
	}

	public function is_large_file( $file_path ) {
		$file_size = $this->get_file_size( $file_path );
		$cut_off   = $this->settings->get_large_file_cutoff();

		return $file_size > $cut_off;
	}

	public function get_file_size( $file_path ) {
		if ( ! isset( $this->file_sizes_cache[ $file_path ] ) ) {
			$this->file_sizes_cache[ $file_path ] = file_exists( $file_path ) ? filesize( $file_path ) : 0;
		}

		return $this->file_sizes_cache[ $file_path ];
	}
}
