<?php

class Breeze_Store_Files {

	var $store_files_dir    = '/breeze/';
	var $cdn_pixel_file_url = 'https://connect.facebook.net/en_US/fbevents.js';
	var $cdn_gtm_js_file    = 'https://www.googletagmanager.com/gtm.js';
	var $cdn_gtm_js_route   = 'https://www.googletagmanager.com/gtag/js';

	public function init( $html = 0, $options = array() ) {

		if ( empty( $options ) || ! $html ) {
			return $html;
		}

		$wp_upload       = wp_upload_dir();
		$store_files_uri = $wp_upload['baseurl'] . $this->store_files_dir;
		$store_files_dir = $wp_upload['basedir'] . $this->store_files_dir;

		if ( $options['breeze-store-googlefonts-locally'] ) {
			$html = $this->extract_google_fonts_css( $html, $store_files_dir, $store_files_uri );

		}

		if ( $options['breeze-store-googleanalytics-locally'] ) {
			$html = $this->extract_gtm( $html, $store_files_dir, $store_files_uri );
			$html = $this->extract_google_analytics( $html, $store_files_dir, $store_files_uri );
		}

		if ( $options['breeze-store-facebookpixel-locally'] ) {
			$html = $this->extract_facebook_pixel( $html, $store_files_dir, $store_files_uri );
		}

		return $html;
	}

	/**
	 * Get all google fonts css files
	 *
	 * @param $html
	 *
	 * @return mixed
	 */
	public function extract_google_fonts_css( $html, $files_dir, $stored_files_uri ) {

		// Preset routes
		$files_dir        = $files_dir . 'google/fonts/';
		$stored_files_uri = $stored_files_uri . 'google/fonts/';

		// Extract Google Fonts declarations using link tags
		$pattern = '/<link[^>]+?href=["\'](https:\/\/fonts\.googleapis\.com\/css[^"\']+)["\'][^>]+?>/i';
		preg_match_all( $pattern, $html, $matches );

		$font_declarations = $matches[1];

		foreach ( $font_declarations as $font_url ) {

			$url_structured = parse_url( $font_url );
			parse_str( $url_structured['query'], $query );
			$font_title = explode( ':', $query['family'] );
			$font_title = explode( ' ', $font_title[0] );
			$font_title = strtolower( implode( '_', $font_title ) );

			$file_dir        = $files_dir . $font_title . '/';
			$stored_file_uri = $stored_files_uri . $font_title . '/';

			$font_api_response = wp_remote_get(
				esc_url_raw( $font_url ),
				array(
					'headers' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
				)
			);
			$font_content      = wp_remote_retrieve_body( $font_api_response );

			$local_css = $this->rewrite_google_fonts_files( $font_content, $font_title, $file_dir, $stored_file_uri );

			if ( $local_css ) {
				$html = str_replace( $font_url, $local_css, $html );
			}
		}

		return $html;
	}

	/**
	 * Rewriting .css file contents with local font files
	 *
	 * @param $css
	 * @param $font_title
	 * @param $file_dir
	 * @param $stored_files_uri
	 *
	 * @return false|string
	 */
	public function rewrite_google_fonts_files( $css, $font_title, $file_dir, $stored_files_uri ) {
		$font_declarations = array();

		// Extract Google Fonts declarations from css file
		$pattern = '/url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)/';
		preg_match_all( $pattern, $css, $font_declarations );

		foreach ( $font_declarations[0] as $font_declaration ) {

			$clean_font_url = str_replace( array( 'url(', ')' ), '', $font_declaration );
			$filename       = basename( $clean_font_url );

			$local_font_file = $this->download_files_locally( $clean_font_url, $file_dir, $filename );

			if ( $local_font_file ) {
				$css = str_replace( $clean_font_url, $stored_files_uri . $filename, $css );
			}
		}

		$local_css_file_name = $font_title . '.css';
		$local_css_file_dir  = $file_dir . $local_css_file_name;
		$local_css_file_uri  = $stored_files_uri . $local_css_file_name;

		if ( file_exists( $file_dir ) ) {
			$css_file = fopen( $local_css_file_dir, 'w' );
			fwrite( $css_file, $css );
			fclose( $css_file );
		}

		if ( file_exists( $local_css_file_dir ) ) {
			return $local_css_file_uri;
		}

		return false;
	}

	/**
	 * Extract Google Tag Manager files
	 *
	 * @param $html
	 * @param $file_dir
	 * @param $stored_files_uri
	 *
	 * @return array|false|string|string[]
	 */
	public function extract_gtm( $html, $file_dir, $stored_files_uri ) {
		$gtm_url        = $this->cdn_gtm_js_file;
		$gtag_url       = $this->cdn_gtm_js_route;
		$gtm_id_pattern = '/\'GTM-(.*?)\'/';

		$gtag_id_pattern = "/gtag\('config', '([A-Za-z0-9-]+)'\)/";

		preg_match( $gtm_id_pattern, $html, $gtm_id );
		preg_match( $gtag_id_pattern, $html, $gtag_id );

		$file_dir = $file_dir . 'google/';

		if ( isset( $gtm_id[1] ) ) {
			$configValue = $gtm_id[1];

			preg_match( '/' . preg_quote( $gtm_url, '/' ) . '/', $html, $gtm_file_url );

			if ( ! empty( $gtm_file_url ) ) {
				$gtm_file_url = $gtm_file_url[0] . '?id=GTM-' . $configValue;
				$local_file   = $this->download_files_locally( $gtm_file_url, $file_dir, 'gtm.js', '.js' );
				$log          = 'User: ' . $_SERVER['REMOTE_ADDR'] . ' - ' . date( 'F j, Y, g:i a' ) . PHP_EOL .
						'Attempt: gtm_not_empty' . PHP_EOL .
						'URL Gtm: ' . print_r( $gtm_file_url, true ) . PHP_EOL;

				if ( $local_file ) {
					$local_file_url = $stored_files_uri . 'google/gtm.js';

					$html = str_replace( $gtm_url, $local_file_url, $html );
				}
			}
		}

		if ( isset( $gtag_id[1] ) ) {
			$configValue = $gtag_id[1];

			preg_match( '/' . preg_quote( $gtag_url, '/' ) . '/', $html, $gtag_file_url );

			if ( ! empty( $gtag_file_url[0] ) ) {
				$gtag_file_url = $gtag_file_url[0] . '?id=' . $configValue;
				$local_file    = $this->download_files_locally( $gtag_file_url, $file_dir, 'gtag.js', '.js' );

				if ( $local_file ) {
					$local_file_url = $stored_files_uri . 'google/gtag.js';

					$html = str_replace( $gtag_url, $local_file_url, $html );
				}
			}
		}

		return $html;
	}


	/**
	 * Extract Google Analytics Js locally
	 *
	 * @param $html
	 * @param $file_dir
	 * @param $stored_files_uri
	 *
	 * @return array|mixed|string|string[]
	 */
	public function extract_google_analytics( $html, $file_dir, $stored_files_uri ) {

		$file_dir         = $file_dir . 'google/';
		$stored_files_uri = $stored_files_uri . 'google/';

		// Extract the Google Analytics script URLs using script tags
		$pattern = '/https:\/\/www.google-analytics.com\/analytics.js/i';
		preg_match_all( $pattern, $html, $matches );

		$analytics_script_urls = $matches[0];

		foreach ( $analytics_script_urls as $analytics_url ) {
			$filename = basename( $analytics_url );

			$local_analytics_file = $this->download_files_locally( $analytics_url, $file_dir, $filename, '.js' );

			if ( $local_analytics_file ) {
				$local_analytics_url = $stored_files_uri . $filename;
				$html                = str_replace( $analytics_url, $local_analytics_url, $html );
			}
		}

		return $html;
	}


	/**
	 * Extract Facebook Pixel Files
	 *
	 * @param $html
	 * @param $file_dir
	 * @param $stored_files_uri
	 *
	 * @return array|false|string|string[]
	 */
	public function extract_facebook_pixel( $html, $file_dir, $stored_files_uri ) {
		$url = $this->cdn_pixel_file_url;

		$pixel_pattern = '/https:\/\/connect\.facebook\.net\/[a-zA-Z_]+\/fbevents\.js/';
		preg_match_all( $pixel_pattern, $html, $fb_pixel_file_url );

		if ( ! empty( $fb_pixel_file_url[0][0] ) ) {

			$file_dir   = $file_dir . 'facebook/';
			$local_file = $this->download_files_locally( $fb_pixel_file_url[0][0], $file_dir, 'fbevents.js', '.js' );

			if ( $local_file ) {
				$local_file_url = $stored_files_uri . 'facebook/fbevents.js';

				return str_replace( $fb_pixel_file_url[0][0], $local_file_url, $html );
			}
		}

		return $html;
	}


	// Helper Functions

	/**
	 * Helper to download files locally (be it font or others)
	 *
	 * @param $url
	 * @param $file_dir
	 * @param $file_name
	 *
	 * @return bool|false
	 */
	public function download_files_locally( $url, $file_dir, $file_name, $file_type = '.css' ) {
		if ( ! file_exists( $file_dir ) ) {
			mkdir( $file_dir, 0775, true );
		}

		if ( $file_type = '.css' ) {
			$max_age_in_seconds = 7 * 24 * 60 * 60;
		} else {
			$max_age_in_seconds = 30 * 24 * 60 * 60;
		}

		$local_file_path = $file_dir . $file_name;

		// Check if the file exists and is less than 1 week old
		if ( file_exists( $local_file_path ) && time() - filemtime( $local_file_path ) < $max_age_in_seconds ) {
			return true; // File is already up-to-date
		}

		// Validate the url.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$original_file_content = file_get_contents( $url );

		if ( ! $original_file_content ) {
			return false;
		}

		$file = fopen( $local_file_path, 'w' );
		fwrite( $file, $original_file_content );
		fclose( $file );

		if ( ! file_exists( $local_file_path ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Function to delete temporary files
	 *
	 * @return bool
	 */
	public static function cleanup_all_extra_folder() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$ret = true;

		$folder = untrailingslashit( WP_CONTENT_DIR ) . '/uploads/breeze';

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			$ret = false;
		}

		/**
		 * For cache folder we only delete specific folders since the folder is being used by other plugins.
		 */
		$folder = untrailingslashit( WP_CONTENT_DIR ) . '/cache/breeze'; // HTML cache

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			$ret = false;
		}

		$folder = untrailingslashit( WP_CONTENT_DIR ) . '/cache/breeze-extra'; // Gravatars.

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			$ret = false;
		}

		$folder = untrailingslashit( WP_CONTENT_DIR ) . '/cache/breeze-minification'; // CSS/JS minified files.

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			$ret = false;
		}

		$folder = untrailingslashit( WP_CONTENT_DIR ) . '/cache/uncss'; // Clean of unneeded CSS.

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			$ret = false;
		}

		$folder = untrailingslashit( WP_CONTENT_DIR ) . '/cache'; // Cache folder in wp-content
		if ( true === breeze_is_folder_empty( $folder ) ) {
			$wp_filesystem->delete( $folder, true );
		}

		return $ret;
	}
}
