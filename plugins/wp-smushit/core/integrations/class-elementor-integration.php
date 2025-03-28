<?php

namespace Smush\Core\Integrations;

use Smush\Core\Controller;
use Smush\Core\Server_Utils;
use Smush\Core\Url_Utils;
use Smush\Core\Parser\Image_URL;
use Smush\Core\Transform\Transformer;
use Smush\Core\Media\Media_Item_Size;
/**
 * Elementor_Integration
 */
class Elementor_Integration extends Controller {

	/**
	 * Utility for URL operations.
	 *
	 * @var Url_Utils
	 */
	private $url_utils;

	/**
	 * @var string
	 */
	private $current_url;

	/**
	 * @var Transformer
	 */
	private $transformer;

	public function __construct() {
		$this->url_utils   = new Url_Utils();
		$this->transformer = new Transformer();

		$this->register_filter( 'elementor/frontend/the_content', array( $this, 'transform_elementor_content' ) );
		$this->register_filter( 'wp_smush_media_item_size', array( $this, 'initialize_elementor_custom_size' ), 10, 4 );
	}

	public function should_run() {
		return class_exists( '\\Elementor\Plugin' );
	}

	public function initialize_elementor_custom_size( $size, $key, $metadata, $media_item ) {
		if ( false === strpos( $key, 'elementor_custom_' ) ) {
			return $size;
		}

		$uploads_dir = wp_get_upload_dir();
		if ( ! isset( $uploads_dir['basedir'], $uploads_dir['baseurl'] ) ) {
			return $size;
		}

		$base_dir = $uploads_dir['basedir'];
		$base_url = $uploads_dir['baseurl'];

		return new Media_Item_Size( $key, $media_item->get_id(), $base_dir, $base_url, $metadata );
	}

	/**
	 * Transforms Elementor content by replacing URLs with CDN URLs.
	 *
	 * This function processes Elementor's content to identify image URLs
	 * (e.g., JPEG, PNG, GIF, WebP) hosted on the site's content or site URL,
	 * and replaces them with the corresponding CDN URLs.
	 *
	 * @param string $element_data The Elementor settings data containing URLs
	 *                             that may need transformation.
	 *
	 * @return string Transformed Elementor content with URLs replaced by CDN URLs.
	 */
	public function transform_elementor_content( $element_data ) {

		$content_url = $this->prepare_url( content_url() );
		// Replace URLs in the data.
		return preg_replace_callback(
			"#(?:https?:)?{$content_url}[^'|,;\"]*\.(?:jpe?g|png|gif|webp)#m",
			function ( $matches ) {
				return addcslashes( $this->transform_url( stripslashes( $matches[0] ) ), '/' );
			},
			$element_data
		);
	}

	private function transform_url( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}

		$extension = $this->url_utils->get_extension( $url );
		$image_url = new Image_URL( $url, $extension, $this->get_current_url() );
		return $this->transformer->transform_url( $image_url->get_absolute_url() );
	}

	private function get_current_url() {
		if ( ! $this->current_url ) {
			$this->current_url = ( new Server_Utils() )->get_current_url();
		}
		return $this->current_url;
	}

	/**
	 * Prepare a URL for use in a regular expression.
	 *
	 * @param string $url The URL to prepare.
	 * @return string The escaped URL for use in regex.
	 */
	private function prepare_url( $url ) {
		$url = untrailingslashit( preg_replace( '/https?:/', '', $url ) );
		return addcslashes( preg_quote( $url, '/' ), '/' );
	}
}
