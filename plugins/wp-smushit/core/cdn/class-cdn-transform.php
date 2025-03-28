<?php

namespace Smush\Core\CDN;

use Smush\Core\Media\Attachment_Url_Cache;
use Smush\Core\Parser\Element;
use Smush\Core\Parser\Element_Attribute;
use Smush\Core\Parser\Image_URL;
use Smush\Core\Parser\Style;
use Smush\Core\Settings;
use Smush\Core\Transform\Transform;
use Smush\Core\Url_Utils;

class CDN_Transform implements Transform {
	/**
	 * @var CDN_Helper
	 */
	private $cdn_helper;
	/**
	 * @var Settings|null
	 */
	private $settings;
	/**
	 * @var CDN_Srcset_Controller
	 */
	private $cdn_srcset;
	/**
	 * @var Attachment_Url_Cache
	 */
	private $attachment_url_cache;
	/**
	 * @var Url_Utils
	 */
	private $url_utils;

	public function __construct() {
		$this->cdn_helper           = CDN_Helper::get_instance();
		$this->cdn_srcset           = CDN_Srcset_Controller::get_instance();
		$this->settings             = Settings::get_instance();
		$this->attachment_url_cache = Attachment_Url_Cache::get_instance();
		$this->url_utils            = new Url_Utils();
	}

	public function should_transform() {
		if ( ! $this->cdn_helper->is_cdn_active() ) {
			return false;
		}

		if ( $this->cdn_helper->is_rest_request() ) {
			return $this->settings->get( 'rest_api_support' );
		}

		return true;
	}

	public function transform_page( $page ) {
		foreach ( $page->get_composite_elements() as $composite_element ) {
			$this->transform_elements( $composite_element->get_elements() );
		}

		$this->transform_elements( $page->get_elements() );

		foreach ( $page->get_styles() as $style ) {
			$this->transform_style( $style );
		}
	}

	private function transform_element( $element ) {
		if ( $this->element_has_excluded_keywords( $element ) ) {
			return;
		}

		$this->update_element_attributes( $element );

		if ( $this->settings->get( 'background_images' ) ) {
			$this->update_element_background( $element );
		}
	}

	/**
	 * @param $element Element
	 *
	 * @return void
	 */
	private function update_element_attributes( $element ) {
		if ( $element->is_image_element() ) {
			$this->update_img_element_attributes( $element );
		} else {
			$this->update_other_element_attributes( $element );
		}
	}

	/**
	 * @param $element Element
	 *
	 * @return void
	 */
	private function update_other_element_attributes( $element ) {
		foreach ( $element->get_image_attributes() as $attribute ) {
			$this->update_image_urls( $attribute->get_image_urls(), $element->get_markup() );
		}
	}

	/**
	 * @param $element Element
	 *
	 * @return void
	 */
	private function update_img_element_attributes( $element ) {
		$image_markup = $element->get_markup();
		$this->update_alternate_attributes( $element, $image_markup );

		$src_attribute = $element->get_attribute( 'src' );
		if ( ! $src_attribute ) {
			return;
		}

		$src_image_url = $src_attribute->get_single_image_url();
		if ( empty( $src_image_url ) ) {
			return;
		}

		$src_url         = $src_image_url->get_absolute_url();
		$updated_src_url = $this->filter_before_process( $src_url, $image_markup );
		if ( $this->cdn_helper->is_supported_url( $updated_src_url ) && ! $this->cdn_helper->skip_image_url( $updated_src_url, $image_markup ) ) {
			$updated_src_url = $this->process_url( $updated_src_url, $image_markup );
			$src_image_url->set_url( $updated_src_url );

			$this->update_img_element_srcset_attribute( $element, $src_url );
		}
	}

	private function process_url( $url, $image, $resizing = false ) {
		$args = array();

		if ( $resizing && $this->settings->get( 'auto_resize' ) ) {
			$dimensions = $this->cdn_helper->guess_dimensions_from_image_markup( $image );
			if ( $dimensions ) {
				$args['size'] = $dimensions;
			}
		}

		/**
		 * Filter hook to alter image src arguments before going through cdn.
		 *
		 * @param array $args Arguments.
		 * @param string $url Image src.
		 * @param string $image Image tag.
		 */
		$args = apply_filters( 'smush_image_cdn_args', $args, $image );

		/**
		 * Filter hook to alter image src before going through cdn.
		 *
		 * @param string $url Image src.
		 * @param string $image Image tag.
		 */
		$url = apply_filters( 'smush_image_src_before_cdn', $url, $image );

		// Generate cdn url from local url.
		$url = $this->cdn_helper->generate_cdn_url( $url, $args );

		/**
		 * Filter hook to alter image src after replacing with CDN base.
		 *
		 * @param string $url Image src.
		 * @param string $image Image tag.
		 */
		return apply_filters( 'smush_image_src_after_cdn', $url, $image );
	}

	/**
	 * @param $srcset Element_Attribute
	 *
	 * @return boolean
	 */
	private function srcset_attribute_already_updated( $srcset ) {
		return strpos( $srcset->get_value(), 'smushcdn.com' ) !== false;
	}

	/**
	 * @param Element $element
	 * @param $src_url
	 *
	 * @return void
	 */
	private function update_img_element_srcset_attribute( $element, $src_url ) {
		$srcset_attribute = $element->get_attribute( 'srcset' );
		$already_updated  = $srcset_attribute && $this->srcset_attribute_already_updated( $srcset_attribute );
		if ( $already_updated ) {
			return;
		}

		$should_auto_resize = $this->settings->get( 'auto_resize' );
		$element_markup     = $element->get_markup();
		$skip_adding_srcset = apply_filters( 'smush_skip_adding_srcset', false, $src_url, $element_markup );
		if ( $should_auto_resize && ! $skip_adding_srcset ) {
			$this->generate_and_use_fresh_srcset( $src_url, $element );
		} elseif ( $srcset_attribute ) {
			$this->update_image_urls( $srcset_attribute->get_image_urls(), $element_markup );
		}
	}

	/**
	 * @param $src_url
	 * @param Element $element
	 *
	 * @return void
	 */
	private function generate_and_use_fresh_srcset( $src_url, $element ) {
		$attachment_id = $this->attachment_url_cache->get_id_for_url( $src_url );
		list( $width, $height ) = $this->find_image_width_and_height( $src_url, $element );
		list( $srcset, $sizes ) = $this->cdn_srcset->generate_srcset( $src_url, $attachment_id, $width, $height );
		if ( $srcset ) {
			$new_srcset_attribute = new Element_Attribute( 'srcset', $srcset );
			$element->add_or_update_attribute( $new_srcset_attribute );
		}

		if ( $sizes ) {
			$new_sizes_attribute = new Element_Attribute( 'sizes', $sizes );
			$element->add_or_update_attribute( $new_sizes_attribute );
		}
	}

	/**
	 * @param $src_url string
	 * @param $element Element
	 *
	 * @return array
	 */
	private function find_image_width_and_height( $src_url, $element ) {
		if ( $element->has_attribute( 'width' ) ) {
			$width_from_attribute = (int) $element->get_attribute( 'width' )->get_value();
		}

		if ( $element->has_attribute( 'height' ) ) {
			$height_from_attribute = (int) $element->get_attribute( 'height' )->get_value();
		}

		list( $width_from_src, $height_from_src ) = $this->url_utils->guess_dimensions_from_image_url( $src_url );

		$width  = intval( $width_from_attribute ?? $width_from_src );
		$height = intval( $height_from_attribute ?? $height_from_src );

		return array( $width, $height );
	}

	/**
	 * @param Element $element
	 * @param $image_markup
	 *
	 * @return void
	 */
	private function update_alternate_attributes( $element, $image_markup ) {
		foreach ( $element->get_image_attributes() as $alternate_attribute ) {
			if ( in_array( $alternate_attribute->get_name(), array( 'src', 'srcset' ) ) ) {
				// src and srcset are handled separately.
				continue;
			}

			foreach ( $alternate_attribute->get_image_urls() as $alternate_url ) {
				$alternate_url_string = $alternate_url->get_absolute_url();
				if ( $this->cdn_helper->is_supported_url( $alternate_url_string ) && ! $this->cdn_helper->skip_image_url( $alternate_url_string, $image_markup ) ) {
					$updated = $this->process_url( $alternate_url_string, $image_markup );
					$alternate_url->set_url( $updated );
				}
			}
		}
	}

	private function filter_before_process( $src_url, $image_markup ) {
		/**
		 * Filter hook to alter image src at the earliest.
		 *
		 * @param string $src_url Image src.
		 * @param string $image_markup Image tag.
		 */
		return apply_filters( 'wp_smush_cdn_before_process_src', $src_url, $image_markup );
	}

	/**
	 * @param $element Element
	 *
	 * @return void
	 */
	private function update_element_background( $element ) {
		foreach ( $element->get_css_properties() as $css_property ) {
			foreach ( $css_property->get_image_urls() as $image_url ) {
				$image_url_string = $image_url->get_absolute_url();
				$element_markup   = $element->get_markup();
				if ( $this->skip_background_image( $image_url_string, $element_markup ) ) {
					continue;
				}

				$image_url_string = $this->filter_background_url_before_process( $image_url_string, $element_markup );
				if ( $this->cdn_helper->is_supported_url( $image_url_string ) ) {
					// TODO: resizing argument is set to true but this needs to be checked again
					$image_url->set_url( $this->process_url( $image_url_string, $element_markup, true ) );
				}
			}
		}
	}

	/**
	 * @param string $image_url_string
	 * @param string $element_markup
	 *
	 * @return bool
	 */
	private function skip_background_image( $image_url_string, $element_markup = '' ) {
		$is_url_excluded = $this->is_url_excluded_in_settings( $image_url_string );

		/**
		 * Filter to skip a single image from cdn.
		 *
		 * @param bool $skip Should skip? Default: false.
		 * @param string $image_url_string Image url.
		 * @param array|bool $element_markup Image tag or false.
		 */
		return apply_filters( 'smush_skip_background_image_from_cdn', $is_url_excluded, $image_url_string, $element_markup );
	}

	/**
	 * @param $image_url_string
	 * @param $element_markup
	 *
	 * @return mixed|null
	 */
	private function filter_background_url_before_process( $image_url_string, $element_markup ) {
		/**
		 * Filter hook to alter background image src at the earliest.
		 *
		 * @param string $image_url_string Image src.
		 * @param string $element_markup Image tag.
		 */
		return apply_filters( 'smush_cdn_before_process_background_src', $image_url_string, $element_markup );
	}

	/**
	 * @param Style $style
	 *
	 * @return void
	 */
	private function transform_style( $style ) {
		foreach ( $style->get_image_urls() as $image_url ) {
			$image_url_string = $image_url->get_absolute_url();
			if (
				$this->cdn_helper->is_supported_url( $image_url_string ) &&
				! $this->skip_background_image( $image_url_string )
			) {
				$image_url->set_url( $this->cdn_helper->generate_cdn_url( $image_url_string ) );
			}
		}
	}

	/**
	 * @param Image_URL[] $image_urls
	 * @param string $image_markup
	 *
	 * @return void
	 */
	private function update_image_urls( $image_urls, $image_markup ) {
		foreach ( $image_urls as $image_url ) {
			$image_url_string = $image_url->get_absolute_url();
			if ( $this->cdn_helper->is_supported_url( $image_url_string ) && ! $this->cdn_helper->skip_image_url( $image_url_string, $image_markup ) ) {
				$image_url->set_url( $this->process_url( $image_url_string, $image_markup ) );
			}
		}
	}

	public function transform_image_url( $url ) {
		if ( ! $this->cdn_helper->is_supported_url( $url ) || $this->cdn_helper->skip_image_url( $url ) ) {
			return $url;
		}

		return $this->cdn_helper->generate_cdn_url( $url );
	}

	/**
	 * @param array $elements
	 *
	 * @return void
	 */
	private function transform_elements( array $elements ) {
		foreach ( $elements as $element ) {
			$this->transform_element( $element );
		}
	}

	private function element_has_excluded_keywords( Element $element ) {
		$keyword_exclusions = $this->cdn_helper->keyword_exclusions();

		if ( ! $keyword_exclusions->has_excluded_keywords() ) {
			return false;
		}

		return $keyword_exclusions->is_markup_excluded( $element->get_markup() ) ||
		       $keyword_exclusions->is_id_attribute_excluded( $element->get_attribute_value( 'id' ) ) ||
		       $keyword_exclusions->is_class_attribute_excluded( $element->get_attribute_value( 'class' ) );
	}

	private function is_url_excluded_in_settings( $url ) {
		$keyword_exclusions = $this->cdn_helper->keyword_exclusions();

		return $keyword_exclusions->is_url_excluded( $url );
	}
}
