<?php

namespace Smush\Core\Avif;

use Smush\Core\Next_Gen\Next_Gen_Transform;
use Smush\Core\Settings;

class Avif_Transform extends Next_Gen_Transform {
	/**
	 * @var Avif_Helper
	 */
	private $avif_helper;
	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct() {
		$this->avif_helper = new Avif_Helper();
		$this->settings    = Settings::get_instance();

		parent::__construct(
			$this->settings->is_avif_fallback_active(),
			'data-smush-avif-fallback'
		);
	}

	public function should_transform() {
		$is_cdn_active  = $this->settings->is_cdn_active(); // CDN takes precedence because it handles avif anyway
		$is_avif_active = $this->settings->is_avif_module_active();

		return ! $is_cdn_active && $is_avif_active;
	}

	public function transform_image_url( $url ) {
		$avif_url = $this->avif_helper->get_avif_file_url( $url );
		return $avif_url ? $avif_url : $url;
	}
}
