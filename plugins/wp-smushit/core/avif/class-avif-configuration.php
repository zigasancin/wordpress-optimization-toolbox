<?php

namespace Smush\Core\Avif;

use Smush\Core\Settings;
use Smush\Core\Next_Gen\Next_Gen_Configuration_Interface;

class Avif_Configuration implements Next_Gen_Configuration_Interface {
	const FORMAT_KEY = 'avif';

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Avif_Helper;
	 */
	private $helper;

	public function __construct() {
		$this->settings = Settings::get_instance();
		$this->helper   = new Avif_Helper();
	}

	public function get_format_name() {
		return __( 'AVIF', 'wp-smushit' );
	}

	public function get_format_key() {
		return self::FORMAT_KEY;
	}

	public function is_activated() {
		return $this->settings->is_avif_module_active();
	}

	public function is_fallback_activated() {
		return $this->settings->is_avif_fallback_active();
	}

	public function is_configured() {
		return $this->is_activated();
	}

	public function direct_conversion_enabled() {
		return $this->is_activated();
	}

	public function is_server_configured() {
		return false;
	}

	public function support_server_configuration() {
		return false;
	}

	public function should_show_wizard() {
		return false;
	}

	public function toggle_module( $enable_avif ) {
		if ( $enable_avif ) {
			$this->activate();
		} else {
			$this->deactivate();
		}

		do_action( 'wp_smush_avif_status_changed' );
	}

	public function set_next_gen_method( $avif_method ) {
		// No-op.
	}

	public function set_next_gen_fallback( $fallback_activated ) {
		$this->settings->set( 'avif_fallback', $fallback_activated );
	}

	private function activate() {
		$this->settings->set( 'avif_mod', true );
	}

	private function deactivate() {
		$this->settings->set( 'avif_mod', false );
	}

	public function delete_all_next_gen_files() {
		$this->helper->delete_all_avif_files();
	}
}
