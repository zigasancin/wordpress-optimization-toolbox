<?php

namespace Smush\Core\Avif;

use Smush\Core\Settings;
use Smush\Core\Smush\Smush_Request_Guzzle_Multiple;
use Smush\Core\Smush\Smush_Request_WP_Sequential;
use Smush\Core\Smush\Smusher;

class Avif_Converter extends Smusher {
	/**
	 * @var Avif_Helper
	 */
	private $avif_helper;
	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct() {
		parent::__construct();

		$this->avif_helper = new Avif_Helper();
		$this->settings    = Settings::get_instance();

		$this->set_request_multiple( new Smush_Request_Guzzle_Multiple( $this->settings->streaming_enabled(), array( 'avif' => 'true' ) ) );
		$this->set_request_sequential( new Smush_Request_WP_Sequential( $this->settings->streaming_enabled(), array( 'avif' => 'true' ) ) );
	}

	protected function save_smushed_image_file( $file_path, $image ) {
		$avif_file_path = $this->avif_helper->get_avif_file_path( $file_path, true );
		$file_saved     = file_put_contents( $avif_file_path, $image );
		if ( ! $file_saved ) {
			return false;
		}

		return $avif_file_path;
	}

	protected function save_from_resource( $input_stream, $target_file_path, $file_md5, $chunk_size ) {
		$avif_file_path = $this->avif_helper->get_avif_file_path( $target_file_path, true );

		return parent::save_from_resource( $input_stream, $avif_file_path, $file_md5, $chunk_size );
	}

	protected function get_type_label() {
		return 'Avif';
	}
}
