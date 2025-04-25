<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( ! class_exists( 'Breeze_Disable_Emoji_Option' ) ) {
	/**
	 * Disable the emoji library in WordPress.
	 *
	 * Class Breeze_Disable_Emoji_Option
	 * @since 1.1.9
	 */
	class Breeze_Disable_Emoji_Option {

		private $emoji_status = false;

		function __construct() {

			$emoji_data         = Breeze_Options_Reader::get_option_value( 'breeze-wp-emoji' );
			$this->emoji_status = isset( $emoji_data ) ? filter_var( $emoji_data, FILTER_VALIDATE_BOOLEAN ) : false;

			if ( true === $this->emoji_status ) {
				add_action( 'init', array( &$this, 'disable_emoji_wp_wide' ) );
				remove_action( 'init', 'smilies_init', 5 );
			}
		}

		/**
		 * Remove the emoji functionalities from multiple locations in WordPress.
		 * @since 1.1.9
		 * @access public
		 */
		public function disable_emoji_wp_wide() {
			/**
			 *  Remove the print of inline Emoji detection script.
			 *
			 * @see print_emoji_detection_script()
			 */
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

			/**(
			 * Remove the printed the important emoji-related CSS styles.
			 * @see print_emoji_styles()
			 */
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );


			/**
			 * Remove the script that converts the emoji to a static img element.
			 * @see wp_staticize_emoji()
			 */
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

			/**
			 * Remove the script that converts emoji in emails into static images.
			 * @see wp_staticize_emoji_for_email()
			 */
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

			/**
			 * Disable Emoji DNS prefetch.
			 */
			add_filter( 'emoji_svg_url', '__return_false' );

			/**
			 * Disable the emoji library from TinyMce editor.
			 */
			add_filter( 'tiny_mce_plugins', array( &$this, 'disable_tinymce_emojis_script' ) );

			add_filter( 'wp_resource_hints', array( &$this, 'disable_prefetch' ), 10, 2 );
			add_filter( 'smilies', array( &$this, 'remove_conversion' ) );
		}

		public function remove_conversion() {
			return array();
		}

		/**
		 * Disable the emoji library from TinyMce editor.
		 *
		 * @param array $plugins
		 *
		 * @return array
		 * @access public
		 * @since 1.1.9
		 */
		public function disable_tinymce_emojis_script( $plugins = array() ) {
			if ( is_array( $plugins ) && ! empty( $plugins ) ) {
				return array_diff( $plugins, array( 'wpemoji' ) );
			} else {
				return array();
			}
		}

		public function disable_prefetch( $url_list, $type ) {
			if ( 'dns-prefetch' === mb_strtolower( $type ) ) {
				// Strip out any URLs referencing the WordPress.org emoji location
				$emoji_svg_url_bit = 'https://s.w.org/images/core/emoji/';
				foreach ( $url_list as $key => $url ) {
					if ( strpos( $url, $emoji_svg_url_bit ) !== false ) {
						unset( $url_list[ $key ] );
					}
				}
			}

			return $url_list;
		}
	}

	new Breeze_Disable_Emoji_Option();
}
