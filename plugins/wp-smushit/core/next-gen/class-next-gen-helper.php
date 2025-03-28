<?php

namespace Smush\Core\Next_Gen;

use Smush\Core\File_System;
use Smush\Core\Upload_Dir;

class Next_Gen_Helper {
	private $next_gen_dir_url;
	private $next_gen_dir_path;
	/**
	 * @var Upload_Dir
	 */
	private $upload_dir;
	/**
	 * @var File_System
	 */
	private $fs;
	/**
	 * @var string
	 */
	private $extension;

	public function __construct( $next_gen_dir_url, $next_gen_dir_path, $extension, $upload_dir ) {
		$this->next_gen_dir_url  = $next_gen_dir_url;
		$this->next_gen_dir_path = $next_gen_dir_path;
		$this->extension         = $extension;
		$this->upload_dir        = $upload_dir;
		$this->fs                = new File_System();
	}

	public function get_next_gen_file_url( $url ) {
		$upload_dir_url  = $this->upload_dir->get_upload_url();
		$upload_dir_path = $this->upload_dir->get_upload_path();

		$is_ssl = str_starts_with( $url, 'https:' );

		// Temporarily add the same scheme to both URLs
		$url            = set_url_scheme( $url, 'http' );
		$upload_dir_url = set_url_scheme( $upload_dir_url, 'http' );

		$is_media_lib_file = strpos( $url, $upload_dir_url ) !== false;
		if ( ! $is_media_lib_file ) {
			return false;
		}

		$file_path = str_replace( $upload_dir_url, $upload_dir_path, $url );
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$next_gen_file_path = $this->get_next_gen_file_path( $file_path );
		if ( ! file_exists( $next_gen_file_path ) ) {
			return false;
		}

		$next_gen_file_url = str_replace(
			$this->next_gen_dir_path,
			$this->next_gen_dir_url,
			$next_gen_file_path
		);

		return set_url_scheme( $next_gen_file_url, $is_ssl ? 'https' : 'http' );
	}

	public function get_next_gen_file_path( $file_path, $make = false ) {
		$file_rel_path      = substr( $file_path, strlen( $this->upload_dir->get_upload_path() ) );
		$next_gen_file_path = $this->next_gen_dir_path . $file_rel_path . '.' . $this->extension;

		if ( $make ) {
			$next_gen_file_dir = dirname( $next_gen_file_path );
			if ( ! $this->fs->is_dir( $next_gen_file_dir ) ) {
				wp_mkdir_p( $next_gen_file_dir );
			}
		}

		return $next_gen_file_path;
	}
}
