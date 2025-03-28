<?php

namespace Smush\Core\Webp;

use Smush\Core\Controller;
use Smush\Core\File_System;
use Smush\Core\Helper;
use Smush\Core\Media\Media_Item_Cache;
use Smush\Core\Media\Media_Item_Size;
use Smush\Core\Media\Media_Item_Stats;
use Smush\Core\Smush\Smush_Optimization;

class Webp_Retrospective_Stats_Generator extends Controller {
	private $media_item_cache;
	private $logger;
	/**
	 * @var Webp_Helper
	 */
	private $webp_helper;
	/**
	 * @var File_System
	 */
	private $fs;

	public function __construct() {
		$this->media_item_cache = Media_Item_Cache::get_instance();
		$this->logger           = Helper::logger();
		$this->webp_helper      = new Webp_Helper();
		$this->fs               = new File_System();

		$this->register_filter( 'wp_smush_scan_library_slice_handle_attachment', array(
			$this,
			'save_webp_stats',
		), 10, 2 );
	}

	public function save_webp_stats( $slice_data, $attachment_id ) {
		$this->save_webp_stats_for_attachment( $attachment_id );

		return $slice_data;
	}

	public function save_webp_stats_for_attachment( $attachment_id ) {
		$media_item = $this->media_item_cache->get( $attachment_id );
		if ( ! $media_item->is_valid() ) {
			$this->logger->notice( 'Tried to set webp stats but encountered a problem with the media item' );

			return false;
		}

		$smush_optimization = new Smush_Optimization( $media_item );
		if ( ! $smush_optimization->is_optimized() ) {
			$this->logger->notice( 'Tried to set webp stats but the media item is not optimized' );

			return false;
		}

		$webp_helper = new Webp_Helper();
		if ( ! $webp_helper->legacy_webp_flag_file_exists( $attachment_id ) ) {
			$this->logger->notice( 'Tried to set webp stats but the media item is not converted to webp' );

			return false;
		}

		$webp_optimization = new Webp_Optimization( $media_item );
		$full_webp_stats   = new Media_Item_Stats();
		foreach ( $media_item->get_sizes() as $size ) {
			$size_smush_stats = $smush_optimization->get_size_stats( $size->get_key() );
			$size_webp_stats  = $webp_optimization->get_size_stats( $size->get_key() );
			$size_webp_stats->from_array(
				$this->make_webp_stats_array( $size_smush_stats, $size )
			);
			$full_webp_stats->add( $size_webp_stats );
		}
		$webp_optimization->get_stats()->from_array( $full_webp_stats->to_array() );
		$webp_optimization->save();

		return true;
	}

	/**
	 * @param $smush_size_stats Media_Item_Stats
	 * @param $media_item_size Media_Item_Size
	 *
	 * @return array
	 */
	private function make_webp_stats_array( $smush_size_stats, $media_item_size ) {
		return array(
			'size_before' => $this->get_before_webp_size( $smush_size_stats ),
			'size_after'  => $this->get_after_webp_size( $media_item_size ),
			'time'        => '1.987', // Dummy time
		);
	}

	/**
	 * @param $smush_size_stats Media_Item_Stats
	 *
	 * @return mixed
	 */
	private function get_before_webp_size( $smush_size_stats ) {
		return $smush_size_stats->get_size_before();
	}

	/**
	 * @param $media_item_size Media_Item_Size
	 */
	private function get_after_webp_size( $media_item_size ) {
		$file_path      = $media_item_size->get_file_path();
		$webp_file_path = $this->webp_helper->get_webp_file_path( $file_path );
		if ( $this->fs->file_exists( $webp_file_path ) ) {
			return $this->fs->filesize( $webp_file_path );
		}

		return 0;
	}
}
