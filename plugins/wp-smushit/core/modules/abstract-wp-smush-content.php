<?php
/**
 * Abstract class for image (<img>) manipulation on the frontend: WP_Smush_Content
 *
 * This class is for front-end functionality only! Do not confuse with class-wp-smushit that does all the image
 * manipulation stuff on the back-end.
 *
 * @since 3.2.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class WP_Smush_Content extends WP_Smush_Module {

	/**
	 * Get image tags from page content.
	 *
	 * @since 3.1.0
	 * @since 3.2.0  Moved to WP_Smush_Content from WP_Smush_CDN
	 *
	 * @param string $content  Page content.
	 *
	 * @return array
	 */
	protected function get_images_from_content( $content ) {
		$images = array();

		if ( preg_match_all( '/(?:<img[^>]*?\s+?src=["|\'](?P<img_url>[^\s]+?)["|\'].*?>){1}?/is', $content, $images ) ) {
			foreach ( $images as $key => $unused ) {
				// Simplify the output as much as possible, mostly for confirming test results.
				if ( is_numeric( $key ) && $key > 0 ) {
					unset( $images[ $key ] );
				}
			}
		}

		return $images;
	}

	/**
	 * Add attribute to selected tag.
	 *
	 * @since 3.1.0
	 * @since 3.2.0  Moved to WP_Smush_Content from WP_Smush_CDN
	 *
	 * @param string $element  Image element.
	 * @param string $name     Img attribute name (srcset, size, etc).
	 * @param string $value    Attribute value.
	 */
	protected function add_attribute( &$element, $name, $value ) {
		$closing = false === strpos( $element, '/>' ) ? '>' : ' />';
		$element = rtrim( $element, $closing ) . " {$name}=\"{$value}\"{$closing}";
	}

	/**
	 * Get attribute from an HTML element.
	 *
	 * @since 3.2.0
	 *
	 * @param string $element  HTML element.
	 * @param string $name     Attribute name.
	 *
	 * @return string
	 */
	protected function get_attribute( $element, $name ) {
		$value = array();

		preg_match( '/<img(.*?)' . $name . '=[\'|"](.*?)[\'|"](.*?)>/i', $element, $value );

		return isset( $value['2'] ) ? $value['2'] : '';
	}

	/**
	 * Remove attribute from selected tag.
	 *
	 * @since 3.2.0
	 *
	 * @param string $element    Image element.
	 * @param string $attribute  Img attribute name (srcset, size, etc).
	 */
	protected function remove_attribute( &$element, $attribute ) {
		$element = preg_replace( '/' . $attribute . '=[\'|"](.*?)[\'|"]/', '', $element );
	}

}
