<?php
/**
 * @package WP Smush
 * @subpackage Admin
 * @version 1.0
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2017, Incsub (http://incsub.com)
 */


if ( ! class_exists( 'WpSmushHelper' ) ) {

	class WpSmushHelper {

		function __construct() {
			$this->init();
		}

		function init() {

		}

		/**
		 * Return unfiltered file path
		 *
		 * @param $attachment_id
		 *
		 * @return bool
		 */
		function get_attached_file( $attachment_id ) {
			if ( empty( $attachment_id ) ) {
				return false;
			}

			$file_path = get_attached_file( $attachment_id );
			if ( ! empty( $file_path ) && strpos( $file_path, 's3' ) !== false ) {
				$file_path = get_attached_file( $attachment_id, true );
			}

			return $file_path;
		}

		/**
		 * Iterate over PNG->JPG Savings to return cummulative savings for an image
		 *
		 * @param string $attachment_id
		 *
		 * @return array|bool
		 */
		function get_pngjpg_savings( $attachment_id = '' ) {
			//Initialize empty array
			$savings = array(
				'bytes'       => 0,
				'size_before' => 0,
				'size_after'  => 0
			);

			//Return empty array if attaachment id not provided
			if( empty( $attachment_id ) ) {
				return $savings;
			}

			$pngjpg_savings = get_post_meta( $attachment_id, WP_SMUSH_PREFIX . 'pngjpg_savings', true );
			if( empty( $pngjpg_savings ) || !is_array( $pngjpg_savings )) {
				return $savings;
			}

			foreach ( $pngjpg_savings as $size => $s_savings ) {
				if( empty( $s_savings ) ) {
					continue;
				}
				$savings['size_before'] += $s_savings['size_before'];
				$savings['size_after'] += $s_savings['size_after'];
			}
			$savings['bytes'] = $savings['size_before'] - $savings['size_after'];

			return $savings;
		}

		/**
		 * Multiple Needles in an array
		 *
		 * @param $haystack
		 * @param $needle
		 * @param int $offset
		 *
		 * @return bool
		 */
		function strposa( $haystack, $needle, $offset = 0 ) {
			if ( ! is_array( $needle ) ) {
				$needle = array( $needle );
			}
			foreach ( $needle as $query ) {
				if ( strpos( $haystack, $query, $offset ) !== false ) {
					return true;
				} // stop on first true result
			}

			return false;
		}


		/**
		 * Checks if file for given attachment id exists on s3, otherwise looks for local path
		 *
		 * @param $id
		 * @param $file_path
		 *
		 * @return bool
		 */
		function file_exists( $id, $file_path ) {

			//If not attachment id is given return false
			if ( empty( $id ) ) {
				return false;
			}

			//Get file path, if not provided
			if ( empty( $file_path ) ) {
				$file_path = $this->get_attached_file( $id );
			}

			global $wpsmush_s3;

			//If S3 is enabled
			if ( is_object( $wpsmush_s3 ) && method_exists( $wpsmush_s3, 'is_image_on_s3' ) && $wpsmush_s3->is_image_on_s3( $id ) ) {
				$file_exists = true;
			} else {
				$file_exists = file_exists( $file_path );
			}

			return $file_exists;
		}

		/**
		 * Add ellipsis in middle of long strings
		 *
		 * @param string $string
		 *
		 * @return string Truncated string
		 */
		function add_ellipsis( $string = '' ) {
			if( empty( $string ) ){
				return $string;
			}
			//Return if the character length is 120 or less, else add ellipsis in between
			if( strlen( $string ) < 121 ) {
				return $string;
			}
			$start = substr( $string, 0, 60 );
			$end = substr( $string, -40 );
			$string = $start . '...' . $end;

			return $string;
		}
	}

	global $wpsmush_helper;
	$wpsmush_helper = new WpSmushHelper();

}