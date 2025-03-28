<?php

namespace Smush\Core\Webp;

use Smush\Core\File_System;
use Smush\Core\Media\Media_Item;
use Smush\Core\Next_Gen\Next_Gen_Helper;
use Smush\Core\Smush\Smush_Optimization;

class Webp_Helper {
	const WEBP_FLAG = 'webp_flag';
	/**
	 * @var Webp_Dir
	 */
	private $webp_dir;
	/**
	 * @var File_System
	 */
	private $fs;
	/**
	 * @var Next_Gen_Helper
	 */
	private $next_gen_helper;

	public function __construct() {
		$this->webp_dir        = new Webp_Dir();
		$this->fs              = new File_System();
		$this->next_gen_helper = new Next_Gen_Helper(
			$this->webp_dir->get_webp_url(),
			$this->webp_dir->get_webp_path(),
			'webp',
			$this->webp_dir
		);
	}

	/**
	 * Converts the URL of a media item/media item size to a webp URL.
	 *
	 * @param $url string Original URL.
	 *
	 * @return false|string
	 */
	public function get_webp_file_url( $url ) {
		return $this->next_gen_helper->get_next_gen_file_url( $url );
	}

	public function get_webp_file_path( $file_path, $make = false ) {
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

	public function get_webp_flag( $attachment_id ) {
		_deprecated_function( __METHOD__, '3.18.0', 'Webp_Helper::get_legacy_webp_flag' );

		return $this->get_legacy_webp_flag( $attachment_id );
	}

	public function get_legacy_webp_flag( $attachment_id ) {
		$meta = $this->get_smush_meta( $attachment_id );

		return empty( $meta[ self::WEBP_FLAG ] ) ? '' : $meta[ self::WEBP_FLAG ];
	}

	public function file_path_to_webp_flag( $webp_file_path ) {
		return substr( $webp_file_path, strlen( $this->webp_dir->get_webp_path() . '/' ) );
	}

	public function webp_flag_file_exists( $attachment_id ) {
		_deprecated_function( __METHOD__, '3.18.0', 'Webp_Helper::legacy_webp_flag_file_exists' );

		return $this->legacy_webp_flag_file_exists( $attachment_id );
	}

	public function legacy_webp_flag_file_exists( $attachment_id ) {
		$webp_flag = $this->get_legacy_webp_flag( $attachment_id );
		if ( empty( $webp_flag ) ) {
			return false;
		}

		$webp_file_path = trailingslashit( $this->webp_dir->get_webp_path() ) . ltrim( $webp_flag, '/' );

		return $this->fs->file_exists( $webp_file_path );
	}

	public function update_webp_flag( $attachment_id, $value ) {
		_deprecated_function( __METHOD__, '3.18.0', 'Webp_Helper::update_legacy_webp_flag' );

		$this->update_legacy_webp_flag( $attachment_id, $value );
	}

	public function update_legacy_webp_flag( $attachment_id, $value ) {
		$meta = $this->get_smush_meta( $attachment_id );
		if ( empty( $value ) ) {
			unset( $meta[ self::WEBP_FLAG ] );
		} else {
			$meta[ self::WEBP_FLAG ] = $value;
		}
		update_post_meta( $attachment_id, Smush_Optimization::SMUSH_META_KEY, $meta );
	}

	public function unset_webp_flag( $attachment_id ) {
		_deprecated_function( __METHOD__, '3.18.0', 'Webp_Helper::unset_legacy_webp_flag' );

		$this->unset_legacy_webp_flag( $attachment_id );
	}

	public function unset_legacy_webp_flag( $attachment_id ) {
		$this->update_legacy_webp_flag( $attachment_id, false );
	}

	/**
	 * @return array|mixed
	 */
	private function get_smush_meta( $attachment_id ) {
		$meta = get_post_meta( $attachment_id, Smush_Optimization::SMUSH_META_KEY, true );

		return empty( $meta ) ? array() : $meta;
	}

	/**
	 * @param $media_item Media_Item
	 *
	 * @return void
	 */
	public function delete_media_item_webp_versions( $media_item ) {
		foreach ( $media_item->get_sizes() as $size ) {
			$this->delete_webp_version( $size->get_file_path() );
		}
	}

	public function delete_webp_version( $original_file_path ) {
		$webp_file_path = $this->get_webp_file_path( $original_file_path );
		if ( $this->fs->file_exists( $webp_file_path ) ) {
			$this->fs->unlink( $webp_file_path );
		}
	}

	public function delete_all_webp_files() {
		$webp_path = $this->webp_dir->get_webp_path();

		// Delete the whole webp directory only when on single install or network admin.
		$this->fs->get_wp_filesystem()->delete( $webp_path, true );

		do_action( 'wp_smush_after_delete_all_webp_files' );
	}
}
