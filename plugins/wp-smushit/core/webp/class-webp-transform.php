<?php

namespace Smush\Core\Webp;

use Smush\Core\Next_Gen\Next_Gen_Transform;
use Smush\Core\Settings;

class Webp_Transform extends Next_Gen_Transform {
	/**
	 * @var Webp_Helper
	 */
	private $webp_helper;
	/**
	 * @var Webp_Configuration
	 */
	private $configuration;
	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct() {
		$this->webp_helper   = new Webp_Helper();
		$this->configuration = Webp_Configuration::get_instance();
		$this->settings      = Settings::get_instance();

		parent::__construct(
			$this->settings->is_webp_fallback_active(),
			'data-smush-webp-fallback'
		);
	}

	public function should_transform() {
		$is_cdn_active             = $this->settings->is_cdn_active(); // CDN takes precedence because it handles webp anyway
		$is_webp_active            = $this->settings->is_webp_module_active();
		$direct_conversion_enabled = $this->configuration->direct_conversion_enabled();
		$is_avif_active            = $this->settings->is_avif_module_active();

		return ! $is_cdn_active && ! $is_avif_active && $is_webp_active && $direct_conversion_enabled;
	}

	public function transform_image_url( $url ) {
		$webp_url = $this->webp_helper->get_webp_file_url( $url );
		return $webp_url ? $webp_url : $url;
	}
}
