<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

class Breeze_DNS_Prefetch {

	function __construct() {
		add_filter( 'wp_resource_hints', array( &$this, 'breeze_dns_prefetch' ), 10, 2 );
	}

	/**
	 * Optimize by adding URLs to the prefetch DNS list.
	 *
	 * @param array $urls Array of resources and their attributes, or URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed for,  e.g. 'preconnect' or 'prerender'.
	 *
	 * @return array
	 * @since 2.0.2
	 * @access public
	 */
	public function breeze_dns_prefetch( $urls, $relation_type ) {

		$prefetch_url_list = Breeze_Options_Reader::get_option_value( 'breeze-prefetch-urls' );
		if ( ! is_array( $prefetch_url_list ) ) {
			$prefetch_url_list = array();
		}

		if ( ! empty( $prefetch_url_list ) ) {
			$prefetch_url_list = array_map( 'breeze_rtrim_urls', $prefetch_url_list );
			$prefetch_url_list = array_map( array( $this, 'clean_schema' ), $prefetch_url_list );

			foreach ( $prefetch_url_list as $url_domain ) {

				if ( 'dns-prefetch' === $relation_type ) {
					$urls[] = $url_domain;
				}
			}
		}

		return $urls;
	}

	/**
	 * Remove link schema.
	 *
	 * @param string $current_url Given url string.
	 *
	 * @return string
	 * @since 2.0.2
	 * @access public
	 */
	private function clean_schema( $current_url ) {
		return ltrim( $current_url, 'https:' );
	}
}

new Breeze_DNS_Prefetch();
