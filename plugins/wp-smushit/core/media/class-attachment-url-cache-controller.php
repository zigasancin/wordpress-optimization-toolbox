<?php

namespace Smush\Core\Media;

use Smush\Core\Array_Utils;
use Smush\Core\CDN\CDN_Helper;
use Smush\Core\Controller;
use Smush\Core\Parser\Element;
use Smush\Core\Parser\Page;
use Smush\Core\Upload_Dir;
use Smush\Core\Url_Utils;

class Attachment_Url_Cache_Controller extends Controller {
	private $cache;

	private $bulk_image_urls = array();
	/**
	 * @var Upload_Dir
	 */
	private $upload_dir;
	/**
	 * @var Media_Item_Query
	 */
	private $media_item_query;

	private $element_urls = array();

	private $url_elements = array();
	/**
	 * @var Array_Utils
	 */
	private $array_utils;
	/**
	 * @var Url_Utils
	 */
	private $url_utils;
	/**
	 * @var CDN_Helper
	 */
	private $cdn_helper;

	public function __construct() {
		$this->cache            = Attachment_Url_Cache::get_instance();
		$this->upload_dir       = new Upload_Dir();
		$this->media_item_query = new Media_Item_Query();
		$this->array_utils      = new Array_Utils();
		$this->url_utils        = new Url_Utils();
		$this->cdn_helper       = CDN_Helper::get_instance();

		$this->register_filter( 'wp_get_attachment_image_src', array( $this, 'save__wp_get_attachment_image_src' ), 10, 2 );
		$this->register_filter( 'wp_calculate_image_srcset', array( $this, 'save__wp_calculate_image_srcset' ), 10, 5 );
		$this->register_filter( 'wp_smush_pre_transform_page', array( $this, 'pre_transform_bulk_cache_page_urls' ) );
	}

	public function should_run() {
		return parent::should_run() && ! is_admin();
	}

	public function save__wp_get_attachment_image_src( $image, $attachment_id ) {
		if ( ! empty( $image ) ) {
			$this->cache->set_id_for_url( $image[0], $attachment_id );
		}

		return $image;
	}

	public function save__wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( ! empty( $sources ) ) {
			foreach ( $sources as $source ) {
				$this->cache->set_id_for_url( $source['url'], $attachment_id );
			}
		}

		return $sources;
	}

	/**
	 * @param $page Page
	 *
	 * @return void
	 */
	public function pre_transform_bulk_cache_page_urls( $page ) {
		if ( ! $this->cdn_helper->is_cdn_active() ) {
			// Right now we only need to lookup attachment ID to generate CDN srcset
			// No need to make the bulk lookup if CDN is not active
			return;
		}

		foreach ( $page->get_composite_elements() as $composite_element ) {
			$this->collect_bulk_image_urls( $composite_element->get_elements() );
		}

		$this->collect_bulk_image_urls( $page->get_elements() );

		if ( ! empty( $this->bulk_image_urls ) ) {
			$urls_to_ids = $this->media_item_query->attachment_urls_to_ids( $this->bulk_image_urls );
			foreach ( $urls_to_ids as $url => $attachment_id ) {
				$element_key  = $this->array_utils->get_array_value( $this->url_elements, $url );
				$element_urls = $this->array_utils->get_array_value( $this->element_urls, $element_key );
				if ( ! empty( $element_urls ) && is_array( $element_urls ) ) {
					foreach ( $element_urls as $element_url ) {
						$this->cache->set_id_for_url( $element_url, $attachment_id );
					}
				}
			}
		}

		$this->element_urls    = array();
		$this->url_elements    = array();
		$this->bulk_image_urls = array();
	}

	/**
	 * @param $elements Element[]
	 *
	 * @return void
	 */
	private function collect_bulk_image_urls( $elements ) {
		foreach ( $elements as $element ) {
			$element_key = md5( $element->get_markup() );

			if ( $element->has_attribute( 'src' ) ) {
				$src_url = $element->get_attribute( 'src' )->get_single_image_url();
				if ( $src_url ) {
					$src_absolute_url = $src_url->get_absolute_url();
					if ( $this->should_add_url( $src_absolute_url ) ) {
						$this->collect_url( $src_absolute_url, $element_key );
					}

					$src_url_without_dimensions = $this->url_utils->get_url_without_dimensions( $src_absolute_url );
					if ( $this->should_add_url( $src_url_without_dimensions ) ) {
						$this->collect_url( $src_url_without_dimensions, $element_key );
					}
				}
			}

			if ( $element->has_attribute( 'srcset' ) ) {
				$src_set_urls = $element->get_attribute( 'srcset' )->get_image_urls();
				$src_set_urls = $this->array_utils->ensure_array( $src_set_urls );
				foreach ( $src_set_urls as $image_url ) {
					$srcset_url = $image_url->get_absolute_url();
					if ( $this->should_add_url( $srcset_url ) ) {
						$this->collect_url( $srcset_url, $element_key );
					}
				}
			}
		}
	}

	private function should_add_url( $url ) {
		return ! empty( $url )
		       && ! in_array( $url, $this->bulk_image_urls, true )
		       && $this->upload_dir->is_uploads_url( $url );
	}

	/**
	 * @param string $src_url
	 * @param string $element_key
	 *
	 * @return void
	 */
	private function collect_url( string $src_url, string $element_key ) {
		$this->bulk_image_urls[]              = $src_url;
		$this->element_urls[ $element_key ][] = $src_url;
		$this->url_elements[ $src_url ]       = $element_key;
	}
}
