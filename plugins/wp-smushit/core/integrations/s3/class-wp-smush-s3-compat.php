<?php
/**
 * Class WP_Smush_S3_Compat
 *
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_S3_Compat
 */
class WP_Smush_S3_Compat extends AS3CF_Plugin_Compatibility {

	/**
	 * WP_Smush_S3_Compat constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Init actions.
	 */
	private function init() {
		// Plugin Compatibility with Amazon S3.
		add_filter( 'as3cf_get_attached_file', array( $this, 'smush_download_file' ), 11, 4 );
	}

	/**
	 * Download the attached file from S3 to local server
	 *
	 * @param string $url            URL.
	 * @param string $file           File.
	 * @param int    $attachment_id  Attachment ID.
	 * @param object $s3_object      S3 object.
	 *
	 * @return mixed
	 */
	public function smush_download_file( $url, $file, $attachment_id, $s3_object ) {
		global $as3cf;

		// Return if integration is disabled, or not a pro user.
		if ( ! WP_Smush_Settings::get_instance()->get( 's3' ) || ! WP_Smush::is_pro() ) {
			return $url;
		}

		// If we already have the local file at specified path.
		if ( file_exists( $file ) ) {
			return $url;
		}

		// Download image for Manual and Bulk Smush.
		$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';
		if ( empty( $action ) || ! in_array( $action, array( 'wp_smushit_manual', 'wp_smushit_bulk' ) ) ) {
			return $url;
		}

		// If the plugin compat object is not available, or the method has been updated.
		if ( ! is_object( $as3cf->plugin_compat ) || ! method_exists( $as3cf->plugin_compat, 'copy_image_to_server_on_action' ) ) {
			return $url;
		}

		$as3cf->plugin_compat->copy_image_to_server_on_action( $action, true, $url, $file, $s3_object );
	}

}
