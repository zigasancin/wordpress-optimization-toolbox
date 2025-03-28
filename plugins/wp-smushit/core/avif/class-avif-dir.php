<?php

namespace Smush\Core\Avif;

use Smush\Core\Upload_Dir;

class Avif_Dir extends Upload_Dir {
	private $avif_path;

	private $avif_rel_path;

	private $avif_url;
	/**
	 * @var array
	 */
	private $wp_upload_dir;

	/**
	 * @return string
	 */
	public function get_avif_path() {
		if ( is_null( $this->avif_path ) ) {
			$this->avif_path = $this->prepare_avif_path();
		}

		return $this->avif_path;
	}

	/**
	 * @return string
	 */
	public function get_avif_rel_path() {
		if ( is_null( $this->avif_rel_path ) ) {
			$this->avif_rel_path = $this->prepare_avif_rel_path();
		}

		return $this->avif_rel_path;
	}

	/**
	 * @return string
	 */
	public function get_avif_url() {
		if ( is_null( $this->avif_url ) ) {
			$this->avif_url = $this->prepare_avif_url();
		}

		return $this->avif_url;
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
			// Use the main site's upload directory for all subsite's avif converted images.
			// This makes it easier to have a single rule on the server configs for serving avif in mu.
			$blog_id = get_main_site_id();
			switch_to_blog( $blog_id );
			$upload = wp_upload_dir();
			restore_current_blog();
		}

		return $upload;
	}

	private function prepare_avif_path() {
		return dirname( $this->get_upload_path() ) . '/smush-avif';
	}

	private function prepare_avif_rel_path() {
		return dirname( $this->get_upload_rel_path() ) . '/smush-avif';
	}

	private function prepare_avif_url() {
		return dirname( $this->get_upload_url() ) . '/smush-avif';
	}

	protected function prepare_root_path() {
		return apply_filters( 'smush_avif_rules_root_path_base', parent::prepare_root_path() );
	}
}
