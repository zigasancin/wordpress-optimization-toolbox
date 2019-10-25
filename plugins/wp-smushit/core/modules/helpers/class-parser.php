<?php
/**
 * Smush page parser that is used by CDN and Lazy load modules.
 *
 * @since 3.2.2
 * @package Smush\Core\Modules\Helpers
 */

namespace Smush\Core\Modules\Helpers;

use Smush\WP_Smush;

/**
 * Class Parser
 */
class Parser {

	/**
	 * CDN module status.
	 *
	 * @var bool $cdn
	 */
	private $cdn = false;

	/**
	 * Lazy load module status.
	 *
	 * @var bool $lazy_load
	 */
	private $lazy_load = false;

	/**
	 * Process background images.
	 *
	 * @since 3.2.2
	 *
	 * @var bool $background_images
	 */
	private $background_images = false;

	/**
	 * Parser constructor.
	 *
	 * @since 3.2.2
	 */
	public function __construct() {
		if ( $this->is_smartcrawl_analysis() ) {
			return;
		}

		// Start an output buffer before any output starts.
		add_action(
			'template_redirect',
			function () {
				ob_start( array( $this, 'parse_page' ) );
			},
			1
		);
	}

	/**
	 * Enable parser for selected module.
	 *
	 * @since 3.2.2
	 * @param string $module  Module ID.
	 */
	public function enable( $module ) {
		if ( ! in_array( $module, array( 'cdn', 'lazy_load', 'background_images' ), true ) ) {
			return;
		}

		$this->$module = true;
	}

	/**
	 * Disable parser for selected module.
	 *
	 * @since 3.2.2
	 * @param string $module  Module ID.
	 */
	public function disable( $module ) {
		if ( ! in_array( $module, array( 'cdn', 'lazy_load' ), true ) ) {
			return;
		}

		$this->$module = false;
	}

	/**
	 * Process images from current buffer content.
	 *
	 * Use DOMDocument class to find all available images in current HTML content and set attachment ID attribute.
	 *
	 * @since 3.0
	 * @since 3.2.2  Moved from \Smush\Core\Modules\CDN.
	 *
	 * @param string $content  Current buffer content.
	 *
	 * @return string
	 */
	public function parse_page( $content ) {
		// Do not parse page if CDN and Lazy load modules are disabled.
		if ( ! $this->cdn && ! $this->lazy_load ) {
			return $content;
		}

		/**
		 * Internal filter to disable page parsing.
		 *
		 * Because the page parser module is universal, we need to make sure that all modules have the ability to skip
		 * parsing of certain pages. For example, lazy loading should skip if_preview() pages. In order to achieve this
		 * functionality, I've introduced this filter. Filter priority can be used to overwrite the $skip param.
		 *
		 * @since 3.2.2
		 *
		 * @param bool $skip  Skip status.
		 */
		if ( empty( $content ) || apply_filters( 'wp_smush_should_skip_parse', false ) ) {
			return $content;
		}

		$content = $this->process_images( $content );
		if ( $this->background_images ) {
			$content = $this->process_background_images( $content );
		}

		return $content;
	}

	/**
	 * Process all images within <img> tags.
	 *
	 * @since 3.2.2
	 *
	 * @param string $content  Current buffer content.
	 *
	 * @return string
	 */
	private function process_images( $content ) {
		$images = self::get_images_from_content( $content );

		if ( empty( $images ) ) {
			return $content;
		}

		foreach ( $images[0] as $key => $image ) {
			$img_src   = $images['img_url'][ $key ];
			$new_image = $image;

			// Then update the image with correct CDN links.
			if ( $this->cdn ) {
				$new_image = WP_Smush::get_instance()->core()->mod->cdn->parse_image( $img_src, $new_image );
			}

			// First prepare for lazy-loading, as that does not require any URL rewrites.
			if ( $this->lazy_load ) {
				$new_image = WP_Smush::get_instance()->core()->mod->lazy->parse_image( $img_src, $new_image );
			}

			$content = str_replace( $image, $new_image, $content );
		}

		return $content;
	}

	/**
	 * Process all images that are contained as background-images.
	 *
	 * @since 3.2.2
	 *
	 * @param string $content  Current buffer content.
	 *
	 * @return string
	 */
	private function process_background_images( $content ) {
		$images = self::get_background_images( $content );

		if ( empty( $images ) ) {
			return $content;
		}

		foreach ( $images[0] as $key => $image ) {
			$img_src   = $images['img_url'][ $key ];
			$new_image = $image;

			// Update the image with correct CDN links.
			$new_image = WP_Smush::get_instance()->core()->mod->cdn->parse_background_image( $img_src, $new_image );

			$content = str_replace( $image, $new_image, $content );
		}

		return $content;
	}

	/**
	 * Compatibility with SmartCrawl readability analysis.
	 * Do not process page on analysis.
	 *
	 * @since 3.3.0
	 */
	private function is_smartcrawl_analysis() {
		$wds_analysis = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		if ( ! is_null( $wds_analysis ) && 'wds-analysis-recheck' === $wds_analysis ) {
			return true;
		}

		if ( isset( $_GET['wds-frontend-check'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get image tags from page content.
	 *
	 * @since 3.1.0
	 * @since 3.2.0  Moved to WP_Smush_Content from \Smush\Core\Modules\CDN
	 * @since 3.2.2  Moved to Parser from WP_Smush_Content
	 *
	 * @param string $content  Page content.
	 *
	 * @return array
	 */
	public static function get_images_from_content( $content ) {
		$images = array();

		if ( preg_match_all( '/(?:<img[^>]*?\s+?src=["|\'](?P<img_url>[^\s]+?)["|\'].*?>)/is', $content, $images ) ) {
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
	 * Get background images from content.
	 *
	 * @since 3.2.2
	 *
	 * @param string $content  Page content.
	 *
	 * @return array
	 */
	private static function get_background_images( $content ) {
		$images = array();

		if ( preg_match_all( '/<[^>]*?\s*?background-image:\s*?url\([\'"]*?(?P<img_url>[^\s\'"]+?)[\'")].*?>/is', $content, $images ) ) {
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
	 * @since 3.2.0  Moved to WP_Smush_Content from \Smush\Core\Modules\CDN
	 * @since 3.2.2  Moved to Parser from WP_Smush_Content
	 *
	 * @param string $element  Image element.
	 * @param string $name     Img attribute name (srcset, size, etc).
	 * @param string $value    Attribute value.
	 */
	public static function add_attribute( &$element, $name, $value ) {
		$closing = false === strpos( $element, '/>' ) ? '>' : ' />';
		$element = rtrim( $element, $closing ) . " {$name}=\"{$value}\"{$closing}";
	}

	/**
	 * Get attribute from an HTML element.
	 *
	 * @since 3.2.0
	 * @since 3.2.2  Moved to Parser from WP_Smush_Content
	 *
	 * @param string $element  HTML element.
	 * @param string $name     Attribute name.
	 *
	 * @return string
	 */
	public static function get_attribute( $element, $name ) {
		preg_match( "/{$name}=['\"]([^'\"]+)\"/is", $element, $value );
		return isset( $value['1'] ) ? $value['1'] : '';
	}

	/**
	 * Remove attribute from selected tag.
	 *
	 * @since 3.2.0
	 * @since 3.2.2  Moved to Parser from WP_Smush_Content
	 *
	 * @param string $element    Image element.
	 * @param string $attribute  Img attribute name (srcset, size, etc).
	 */
	public static function remove_attribute( &$element, $attribute ) {
		$element = preg_replace( '/' . $attribute . '=[\'|"](.*?)[\'|"]/', '', $element );
	}

	/**
	 * Get URLs from a string of content.
	 *
	 * This is mostly used to get the URLs from srcset and parse each single URL to use in CDN.
	 *
	 * @since 3.3.0
	 *
	 * @param string $content  Content.
	 *
	 * @return array
	 */
	public static function get_links_from_content( $content ) {
		$images = array();

		if ( preg_match_all( '/([http:|https:][^\s]*)/is', $content, $images ) ) {
			foreach ( $images as $key => $unused ) {
				// Simplify the output as much as possible, mostly for confirming test results.
				if ( is_numeric( $key ) && $key > 0 ) {
					unset( $images[ $key ] );
				}
			}
		}

		return $images;
	}

}
