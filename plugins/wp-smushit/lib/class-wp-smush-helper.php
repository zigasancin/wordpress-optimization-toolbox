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
	}

	global $wpsmush_helper;
	$wpsmush_helper = new WpSmushHelper();

}