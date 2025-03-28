<?php

namespace Smush\Core\Avif;

use Smush\Core\File_System;
use Smush\Core\Media\Media_Item;
use Smush\Core\Next_Gen\Next_Gen_Helper;

class Avif_Helper {
	/**
	 * @var Avif_Dir
	 */
	private $avif_dir;
	/**
	 * @var Next_Gen_Helper
	 */
	private $next_gen_helper;
	/**
	 * @var File_System
	 */
	private $fs;

	public function __construct() {
		$this->avif_dir        = new Avif_Dir();
		$this->fs              = new File_System();
		$this->next_gen_helper = new Next_Gen_Helper(
			$this->avif_dir->get_avif_url(),
			$this->avif_dir->get_avif_path(),
			'avif',
			$this->avif_dir
		);
	}

	public function get_avif_file_url( $url ) {
		return $this->next_gen_helper->get_next_gen_file_url( $url );
	}

	public function get_avif_file_path( $file_path, $make = false ) {
		return $this->next_gen_helper->get_next_gen_file_path( $file_path, $make );
	}

	public function supported_mime_types() {
		return array(
			'image/jpg',
			'image/jpeg',
			'image/x-citrix-jpeg',
			'image/png',
			'image/x-png',
		);
	}

	public function delete_all_avif_files() {
		$avif_path = $this->avif_dir->get_avif_path();

		// Delete the whole avif directory only when on single install or network admin.
		$this->fs->get_wp_filesystem()->delete( $avif_path, true );

		do_action( 'wp_smush_after_delete_all_avif_files' );
	}

	/**
	 * @param $media_item Media_Item
	 *
	 * @return void
	 */
	public function delete_media_item_avif_versions( $media_item ) {
		foreach ( $media_item->get_sizes() as $size ) {
			$this->delete_avif_version( $size->get_file_path() );
		}
	}

	public function delete_avif_version( $original_file_path ) {
		$avif_file_path = $this->get_avif_file_path( $original_file_path );
		if ( $this->fs->file_exists( $avif_file_path ) ) {
			$this->fs->unlink( $avif_file_path );
		}
	}
}
