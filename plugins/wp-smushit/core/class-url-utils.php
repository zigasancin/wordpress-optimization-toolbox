<?php

namespace Smush\Core;

class Url_Utils {
	/**
	 * @var Upload_Dir
	 */
	private $upload_dir;

	public function __construct() {
		$this->upload_dir = new Upload_Dir();
	}

	public function get_extension( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( empty( $path ) ) {
			return false;
		}

		return strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	}

	public function get_url_scheme( $url ) {
		$url_parts = wp_parse_url( $url );

		return empty( $url_parts['scheme'] )
			? false
			: $url_parts['scheme'];
	}

	/**
	 * @param $url
	 *
	 * @return string
	 * @see attachment_url_to_postid()
	 */
	public function make_media_url_relative( $url ) {
		$upload_url = $this->upload_dir->get_upload_url();
		$path       = $url;

		$site_url   = parse_url( $upload_url );
		$image_path = parse_url( $path );

		// Force the protocols to match if needed.
		if ( isset( $image_path['scheme'] ) && ( $image_path['scheme'] !== $site_url['scheme'] ) ) {
			$path = str_replace( $image_path['scheme'], $site_url['scheme'], $path );
		}

		if ( str_starts_with( $path, $upload_url . '/' ) ) {
			$path = substr( $path, strlen( $upload_url . '/' ) );
		}

		return $path;
	}

	public function guess_dimensions_from_image_url( $url ) {
		$width_height_string = array();

		if ( preg_match( '#-(\d+)x(\d+)\.(?:jpe?g|png|gif|webp|svg)#i', $url, $width_height_string ) ) {
			$width  = (int) $width_height_string[1];
			$height = (int) $width_height_string[2];

			if ( $width && $height ) {
				return array( $width, $height );
			}
		}

		return array( false, false );
	}

	/**
	 * Get full size image url from resized one.
	 *
	 * @param string $src Image URL.
	 *
	 * @return string
	 * @since 3.0
	 *
	 */
	public function get_url_without_dimensions( $src ) {
		$extensions = array(
			'gif',
			'jpg',
			'jpeg',
			'png',
			'webp',
		);
		if ( ! preg_match( '/(-\d+x\d+)\.(' . implode( '|', $extensions ) . ')(?:\?.+)?$/i', $src, $src_parts ) ) {
			return $src;
		}

		// Remove WP's resize string to get the original image.
		$original_src = str_replace( $src_parts[1], '', $src );

		// Extracts the file path to the image minus the base url.
		$file_path = substr( $original_src, strlen( $this->upload_dir->get_upload_url() ) );

		// Continue only if the file exists.
		if ( file_exists( $this->upload_dir->get_upload_path() . $file_path ) ) {
			return $original_src;
		}

		// Revert to source if file does not exist.
		return $src;
	}
}
