<?php

namespace Smush\Core\Webp;

use Smush\Core\Array_Utils;
use Smush\Core\File_System;
use Smush\Core\Media\Media_Item;
use Smush\Core\Media\Media_Item_Optimization;
use Smush\Core\Media\Media_Item_Size;
use Smush\Core\Media\Media_Item_Stats;
use Smush\Core\Settings;

/**
 * TODO: the response from the API has webp: false and mime_content_type of the written file is not webp, investigate
 */
class Webp_Optimization extends Media_Item_Optimization {
	const OPTIMIZATION_KEY = 'webp_optimization';

	const WEBP_META_KEY = 'wp-smush-webp-data';
	private $webp_dir;
	/**
	 * @var Media_Item
	 */
	private $media_item;
	/**
	 * @var Webp_Helper
	 */
	private $webp_helper;
	/**
	 * @var Settings|null
	 */
	private $settings;
	/**
	 * @var Webp_Converter
	 */
	private $converter;
	/**
	 * @var File_System
	 */
	private $fs;
	/**
	 * @var Media_Item_Stats[]
	 */
	private $size_stats = array();
	/**
	 * @var Array_Utils
	 */
	private $array_utils;
	/**
	 * @var array
	 */
	private $meta;
	/**
	 * @var Media_Item_Stats
	 */
	private $stats;

	private $reset_properties = array(
		'meta',
		'stats',
		'size_stats',
	);

	public function __construct( $media_item ) {
		$this->webp_dir    = new Webp_Dir();
		$this->webp_helper = new Webp_Helper();
		$this->media_item  = $media_item;
		$this->settings    = Settings::get_instance();
		$this->converter   = new Webp_Converter();
		$this->fs          = new File_System();
		$this->array_utils = new Array_Utils();
	}

	public function get_key() {
		return self::OPTIMIZATION_KEY;
	}

	public function get_name() {
		return __( 'WebP', 'wp-smushit' );
	}

	public function is_optimized() {
		$stats_available = ! $this->get_stats()->is_empty();
		if ( $stats_available ) {
			return true;
		}

		return $this->webp_helper->legacy_webp_flag_file_exists( $this->media_item->get_id() );
	}

	public function should_optimize() {
		if (
			$this->media_item->is_skipped()
			|| $this->media_item->has_errors()
			|| $this->settings->is_avif_module_active()
			|| ! $this->settings->is_webp_module_active()
		) {
			return false;
		}

		return in_array(
			$this->media_item->get_mime_type(),
			$this->webp_helper->supported_mime_types(),
			true
		);
	}

	public function should_reoptimize() {
		if ( ! $this->should_optimize() ) {
			return false;
		}

		if ( ! $this->has_converted_file() ) {
			return true;
		}

		$smushable_sizes = $this->get_sizes_to_convert();
		foreach ( $smushable_sizes as $size ) {
			$size_stats         = $this->get_size_stats( $size->get_key() );
			$webp_file_path     = $this->webp_helper->get_webp_file_path( $size->get_file_path() );
			$size_has_webp_file = $this->fs->file_exists( $webp_file_path );
			$is_size_optimized  = ! $size_stats->is_empty() || $size_has_webp_file;

			if ( ! $is_size_optimized ) {
				return true;
			}
		}

		return false;
	}

	private function has_converted_file() {
		$smushable_sizes = $this->get_sizes_to_convert();
		foreach ( $smushable_sizes as $size ) {
			$webp_file_path = $this->webp_helper->get_webp_file_path( $size->get_file_path() );
			if ( $this->fs->file_exists( $webp_file_path ) ) {
				return true;
			}
		}

		return false;
	}

	public function save() {
		$attachment_id = $this->media_item->get_id();
		$meta          = $this->make_meta();
		if ( ! empty( $meta ) ) {
			update_post_meta( $attachment_id, self::WEBP_META_KEY, $meta );
			$this->reset();

			// TODO: this is a legacy flag
			$this->webp_helper->unset_legacy_webp_flag( $attachment_id );
		}
	}

	public function get_stats() {
		if ( is_null( $this->stats ) ) {
			$this->stats = $this->prepare_stats();
		}

		return $this->stats;
	}

	private function prepare_stats() {
		$meta  = $this->array_utils->get_array_value( $this->get_meta(), 'stats' );
		$meta  = $this->array_utils->ensure_array( $meta );
		$stats = new Media_Item_Stats();
		$stats->from_array( $meta );

		return $stats;
	}

	public function get_size_stats( $size_key ) {
		if ( empty( $this->size_stats[ $size_key ] ) ) {
			$this->size_stats[ $size_key ] = $this->prepare_size_stats( $size_key );
		}

		return $this->size_stats[ $size_key ];
	}

	private function prepare_size_stats( $size_key ) {
		$size_webp_stats = new Media_Item_Stats();
		$size_webp_stats->from_array( $this->get_size_meta( $size_key ) );

		return $size_webp_stats;
	}

	public function get_file() {
		$meta = $this->get_meta();

		return $this->array_utils->get_array_value( $meta, 'file', '' );
	}

	public function get_meta() {
		if ( is_null( $this->meta ) ) {
			$this->meta = $this->fetch_meta();
		}

		return $this->meta;
	}

	private function fetch_meta() {
		$post_meta = get_post_meta( $this->media_item->get_id(), self::WEBP_META_KEY, true );
		return $this->array_utils->ensure_array( $post_meta );
	}

	private function get_sizes_meta() {
		$smush_meta = $this->get_meta();
		$sizes_meta = $this->array_utils->get_array_value( $smush_meta, 'sizes' );

		return $this->array_utils->ensure_array( $sizes_meta );
	}

	private function get_size_meta( $size_key ) {
		$sizes = $this->get_sizes_meta();
		$size  = $this->array_utils->get_array_value( $sizes, $size_key );

		return $this->array_utils->ensure_array( $size );
	}

	private function make_meta() {
		$meta          = array();
		$meta['stats'] = $this->get_stats()->to_array();

		foreach ( $this->get_sizes_to_convert() as $size_key => $size ) {
			$size_stats = $this->get_size_stats( $size_key );
			if ( ! $size_stats->is_empty() ) {
				$meta['sizes'][ $size_key ] = $size_stats->to_array();
			}
		}

		$file_path     = $this->media_item->get_full_or_scaled_size()->get_file_path();
		$webp_rel_path = $this->webp_helper->file_path_to_webp_flag( $this->webp_helper->get_webp_file_path( $file_path ) );
		$meta['file']  = $webp_rel_path;

		return $meta;
	}

	public function should_optimize_size( $size ) {
		if ( ! $this->should_optimize() ) {
			return false;
		}

		return array_key_exists(
			$size->get_key(),
			$this->get_sizes_to_convert()
		);
	}

	public function delete_data() {
		// TODO: this is a legacy flag
		$this->webp_helper->unset_legacy_webp_flag( $this->media_item->get_id() );

		delete_post_meta( $this->media_item->get_id(), self::WEBP_META_KEY );

		$this->reset();
	}

	public function optimize() {
		$files_data        = array_map( function ( $size ) {
			return array(
				'url'  => $size->get_file_url(),
				'path' => $size->get_file_path(),
			);
		}, $this->get_sizes_to_convert() );
		$responses         = $this->converter->smush( $files_data );
		$success_responses = array_filter( $responses );
		if ( count( $success_responses ) !== count( $responses ) ) {
			return false;
		}

		$media_item_stats = new Media_Item_Stats();
		foreach ( $responses as $size_key => $data ) {
			$this->update_from_response( $size_key, $data, $media_item_stats );
		}
		$this->get_stats()->from_array( $media_item_stats->to_array() );

		$this->save();

		return true;
	}

	public function get_optimized_file_url( $original_file_url ) {
		return $this->webp_helper->get_webp_file_url( $original_file_url );
	}

	public function get_errors() {
		return $this->converter->get_errors();
	}

	public function get_optimized_sizes_count() {
		$count = 0;
		$sizes = $this->get_sizes_meta();
		foreach ( $sizes as $size_key => $size ) {
			$size_stats = $this->get_size_stats( $size_key );
			if ( $size_stats && ! $size_stats->is_empty() ) {
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * @param $size_key
	 * @param object $data
	 * @param $media_item_stats Media_Item_Stats
	 */
	private function update_from_response( $size_key, $data, $media_item_stats ) {
		$size_stats = $this->get_size_stats( $size_key );
		$size_stats->from_array( $this->size_stats_from_response( $size_stats, $data ) );

		$media_item_stats->add( $size_stats );
	}

	/**
	 * @param $existing_stats Media_Item_Stats
	 * @param $data         \stdClass Response from the API
	 *
	 * @return array
	 */
	private function size_stats_from_response( $existing_stats, $data ) {
		$data_before_size = empty( $data->before_size ) ? 0 : $data->before_size;
		$data_after_size  = empty( $data->after_size ) ? 0 : $data->after_size;
		$data_time        = empty( $data->time ) ? 0 : $data->time;

		$size_before = max( $existing_stats->get_size_before(), $data_before_size ); // We want to use the oldest before size

		return array(
			'size_before' => $size_before,
			'size_after'  => $data_after_size,
			'time'        => $data_time,
		);
	}

	private function reset() {
		foreach ( $this->reset_properties as $property ) {
			$this->$property = null;
		}
	}

	/**
	 * @return Media_Item_Size[]
	 */
	public function get_sizes_to_convert() {
		return $this->media_item->get_smushable_sizes();
	}
}
