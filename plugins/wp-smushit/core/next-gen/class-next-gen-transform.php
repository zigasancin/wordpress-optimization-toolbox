<?php

namespace Smush\Core\Next_Gen;

use Smush\Core\Parser\Element;
use Smush\Core\Parser\Element_Attribute;
use Smush\Core\Parser\Image_URL;
use Smush\Core\Parser\Page;
use Smush\Core\Transform\Transform;

abstract class Next_Gen_Transform implements Transform {
	/**
	 * @var bool
	 */
	private $is_fallback_enabled;
	/**
	 * @var string
	 */
	private $fallback_attribute_name;

	public function __construct( $is_fallback_enabled, $fallback_attribute_name ) {
		$this->is_fallback_enabled     = $is_fallback_enabled;
		$this->fallback_attribute_name = $fallback_attribute_name;
	}

	abstract public function should_transform();

	/**
	 * @param $page Page
	 *
	 * @return void
	 */
	public function transform_page( $page ) {
		foreach ( $page->get_styles() as $style ) {
			$this->update_image_urls( $style->get_image_urls() );
		}

		foreach ( $page->get_composite_elements() as $composite_element ) {
			$this->transform_elements( $composite_element->get_elements() );
		}

		$this->transform_elements( $page->get_elements() );
	}

	private function add_fallback_attribute( Element $element ) {
		$fallback_values = array();

		foreach ( $element->get_image_attributes() as $fallback_attribute ) {
			if ( $fallback_attribute->has_updates() ) {
				$fallback_values[ $fallback_attribute->get_name() ] = $fallback_attribute->get_value();
			}
		}

		$background_property = $element->get_background_css_property();
		if ( $background_property && $background_property->has_updates() ) {
			$property_key                     = str_replace( 'background', 'bg', $background_property->get_property() );
			$fallback_values[ $property_key ] = $background_property->get_value();
		}

		if ( ! empty( $fallback_values ) ) {
			$element->add_attribute( new Element_Attribute( $this->fallback_attribute_name, json_encode( $fallback_values ) ) );
		}
	}

	/**
	 * @param Element $element
	 *
	 * @return void
	 */
	private function transform_image_element_attributes( $element ) {
		foreach ( $element->get_image_attributes() as $attribute ) {
			$this->update_image_urls( $attribute->get_image_urls() );
		}
	}

	/**
	 * @param Element $element
	 *
	 * @return void
	 */
	private function transform_image_element_css_properties( $element ) {
		foreach ( $element->get_css_properties() as $css_property ) {
			$this->update_image_urls( $css_property->get_image_urls() );
		}
	}

	/**
	 * @param $image_urls Image_URL[]
	 */
	private function update_image_urls( $image_urls ) {
		foreach ( $image_urls as $image_url ) {
			$image_url_original = $image_url->get_absolute_url();
			$next_gen_image_url = $this->transform_image_url( $image_url_original );
			// TODO: find a way to convert the URL back to a relative one so multidomain sites will work
			if ( $next_gen_image_url ) {
				$image_url->set_url( $next_gen_image_url );
			}
		}
	}

	abstract public function transform_image_url( $url );

	/**
	 * @param Element $element
	 *
	 * @return void
	 */
	private function transform_element( Element $element ) {
		$this->transform_image_element_attributes( $element );

		$this->transform_image_element_css_properties( $element );

		if ( $this->is_fallback_enabled ) {
			$this->add_fallback_attribute( $element );
		}
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
}
