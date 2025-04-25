<?php

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( ! class_exists( 'Breeze_Preload_Fonts' ) ) {
	/**
	 * Handles the Prefetch functionality.
	 *
	 * Class Breeze_Prefetch
	 * @since 1.2.0
	 */
	class Breeze_Preload_Fonts {

		public function __construct() {
			add_action( 'wp_head', array( $this, 'load_preload_scripts' ) );
		}

		/**
		 * Preload the website fonts added in the options.
		 *
		 * @since 1.2.0
		 * @access public
		 */
		public function load_preload_scripts() {
			$preload_fonts    = Breeze_Options_Reader::get_option_value( 'breeze-preload-fonts' );
			$fonts_extensions = array(
				'woff',
				'woff2',
				'ttf',
				'eot',
				'bmap',
				'otf',
				'otf',
				'svg',
			);

			$fonts_extensions = apply_filters( 'breeze_preload_fonts_exception', $fonts_extensions );

			// Check if the option is enabled by admin.
			if ( isset( $preload_fonts ) && ! empty( $preload_fonts ) ) {

				foreach ( $preload_fonts as $index => $preload_url ) {
					$extension = pathinfo( $preload_url, PATHINFO_EXTENSION );
					$extension = strtolower( $extension );
					if ( 'css' === $extension ) {
						echo '<link rel="preload" as="style" onload="this.rel = \'stylesheet\'" href="' . $preload_url . '" crossorigin>' . "\n";
					} elseif ( in_array( $extension, $fonts_extensions, true ) ) {
						echo '<link rel="preload" as="font" type="font/' . $extension . '" href="' . $preload_url . '" crossorigin>' . "\n";
					}
				}
			}
		}

	}

	new Breeze_Preload_Fonts();
}
