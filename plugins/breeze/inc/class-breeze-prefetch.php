<?php

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( ! class_exists( 'Breeze_Prefetch' ) ) {
	/**
	 * Handles the Prefetch functionality.
	 *
	 * Class Breeze_Prefetch
	 * v
	 */
	class Breeze_Prefetch {

		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_prefetch_scripts' ) );
		}

		/**
		 * Load Prefetch JavaScript library.
		 * @since 1.2.0
		 * @access public
		 */
		public function load_prefetch_scripts() {
			// Check if the option is enabled by admin.
			$preload_links = Breeze_Options_Reader::get_option_value( 'breeze-preload-links' );

			if ( isset( $preload_links ) && true === filter_var( $preload_links, FILTER_VALIDATE_BOOLEAN ) ) {
				// Load the prefetch library.
				wp_enqueue_script( 'breeze-prefetch', BREEZE_PLUGIN_URL . 'assets/js/js-front-end/breeze-prefetch-links.min.js', array(), BREEZE_VERSION, false );
				wp_localize_script(
					'breeze-prefetch',
					'breeze_prefetch',
					array(
						'local_url'              => home_url(),
						'ignore_remote_prefetch' => true,
						'ignore_list'            => $this->href_ignore_list(),
					)
				);
			}
		}


		/**
		 * The list of links that do not need pre-fetch.
		 *
		 * @return array|mixed
		 * @since 1.2.0
		 * @access public
		 */
		public function href_ignore_list() {

			$exclude_urls = array();

			if (
				isset( $GLOBALS['breeze_config'] ) &&
				isset( $GLOBALS['breeze_config']['exclude_url'] ) &&
				! empty( $GLOBALS['breeze_config']['exclude_url'] )
			) {
				$exclude_urls = $GLOBALS['breeze_config']['exclude_url'];

				$clear_star   = function ( $value ) {
					$value = str_replace( '*', '', $value );
					$value = str_replace( home_url(), '', $value );

					return $value;
				};
				$exclude_urls = array_map( $clear_star, $exclude_urls );
			}
			$exclude_urls[] = 'wp-admin';
			$exclude_urls[] = 'wp-login.php';

			return $exclude_urls;
		}
	}

	new Breeze_Prefetch();
}
