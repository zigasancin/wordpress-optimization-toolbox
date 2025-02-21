<?php

namespace Smush\Core\Media;

class Attachment_Url_Cache {
	private $cache = array();

	/**
	 * Static instance
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Static instance getter
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function has_cached( $url ) {
		return isset( $this->cache[ trim( $url ) ] );
	}

	public function get_id_for_url( $url, $fetch = false ) {
		if ( ! isset( $this->cache[ trim( $url ) ] ) ) {
			$attachment_id = 0;
			if ( $fetch ) {
				$attachment_id = attachment_url_to_postid( $url );
			}

			$this->set_id_for_url( $url, $attachment_id );
		}

		return $this->cache[ trim( $url ) ] ?? 0;
	}

	public function set_id_for_url( $url, $attachment_id ) {
		$this->cache[ trim( $url ) ] = $attachment_id;
	}

	public function reset() {
		$this->cache = array();
	}

	public function get_all() {
		return $this->cache;
	}
}
