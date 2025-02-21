<?php

namespace Smush\Core\Webp;

use Smush\Core\Upload_Dir;

class Webp_Dir extends Upload_Dir {
	private $webp_path;

	private $webp_rel_path;

	private $webp_url;
	/**
	 * @var array
	 */
	private $wp_upload_dir;

	/**
	 * @return string
	 */
	public function get_webp_path() {
		if ( is_null( $this->webp_path ) ) {
			$this->webp_path = $this->prepare_webp_path();
		}

		return $this->webp_path;
	}

	/**
	 * @return string
	 */
	public function get_webp_rel_path() {
		if ( is_null( $this->webp_rel_path ) ) {
			$this->webp_rel_path = $this->prepare_webp_rel_path();
		}

		return $this->webp_rel_path;
	}

	/**
	 * @return string
	 */
	public function get_webp_url() {
		if ( is_null( $this->webp_url ) ) {
			$this->webp_url = $this->prepare_webp_url();
		}

		return $this->webp_url;
	}

	public function get_wp_upload_dir() {
		if ( is_null( $this->wp_upload_dir ) ) {
			$this->wp_upload_dir = $this->prepare_wp_upload_dir();
		}

		return $this->wp_upload_dir;
	}

	private function prepare_wp_upload_dir() {
		if ( ! is_multisite() || is_main_site() ) {
			$upload = wp_upload_dir();
		} else {
			// Use the main site's upload directory for all subsite's webp converted images.
			// This makes it easier to have a single rule on the server configs for serving webp in mu.
			$blog_id = get_main_site_id();
			switch_to_blog( $blog_id );
			$upload = wp_upload_dir();
			restore_current_blog();
		}

		return $upload;
	}

	private function prepare_webp_path() {
		return dirname( $this->get_upload_path() ) . '/smush-webp';
	}

	private function prepare_webp_rel_path() {
		return dirname( $this->get_upload_rel_path() ) . '/smush-webp';
	}

	private function prepare_webp_url() {
		return dirname( $this->get_upload_url() ) . '/smush-webp';
	}

	protected function prepare_root_path() {
		return apply_filters( 'smush_webp_rules_root_path_base', parent::prepare_root_path() );
	}
}
