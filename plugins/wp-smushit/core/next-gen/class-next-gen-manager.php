<?php

namespace Smush\Core\Next_Gen;

use Smush\Core\Webp\Webp_Configuration;

class Next_Gen_Manager {
	const PREVIOUSLY_ACTIVE_FORMAT_KEY = 'wp_smush_next_gen_previously_active_format_key';

	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @var Next_Gen_Configuration_Interface[]
	 */
	private $configuration_objects;

	/**
	 * @var string[]
	 */
	private $format_keys;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get_active_format_name() {
		return $this->get_active_format_configuration()->get_format_name();
	}

	public function is_active() {
		return ! empty( $this->get_active_format_key() );
	}

	public function is_configured() {
		return $this->get_active_format_configuration()->is_configured();
	}

	public function direct_conversion_enabled() {
		return $this->get_active_format_configuration()->direct_conversion_enabled();
	}

	public function get_active_format_configuration() {
		return $this->get_format_configuration( $this->get_preferred_format() );
	}

	public function get_previously_active_format_configuration() {
		return $this->get_format_configuration( $this->get_previously_active_format_key() );
	}

	private function get_preferred_format() {
		$preferred_format = $this->get_active_format_key();

		if ( ! $preferred_format ) {
			$preferred_format = $this->get_previously_active_format_key();
		}

		return $preferred_format;
	}

	public function activate_format( $format_key ) {
		if ( ! is_string( $format_key ) ) {
			return;
		}

		$format_key                   = strtolower( trim( $format_key ) );
		$target_format_already_active = $this->get_active_format_key() === $format_key;
		$is_unexpected_format         = ! in_array( $format_key, $this->get_format_keys(), true );
		if ( $target_format_already_active || $is_unexpected_format ) {
			return;
		}

		$this->switch_to_format( $format_key );
	}

	/**
	 * Selected Next-Gen format key.
	 *
	 * @return Null|string
	 */
	public function get_active_format_key() {
		return $this->prepare_active_format_key();
	}

	private function prepare_active_format_key() {
		foreach ( $this->get_format_keys() as $format_key ) {
			$configuration_module = $this->get_format_configuration( $format_key );
			if ( $configuration_module->is_activated() ) {
				return $format_key;
			}
		}

		return '';
	}

	private function get_format_keys() {
		if ( ! $this->format_keys ) {
			$this->format_keys = $this->prepare_format_keys();
		}

		return $this->format_keys;
	}

	private function prepare_format_keys() {
		$format_keys = array();
		foreach ( $this->get_configuration_objects() as $configuration ) {
			$format_keys[] = $configuration->get_format_key();
		}

		return $format_keys;
	}

	public function get_format_configuration( $name ) {
		foreach ( $this->get_configuration_objects() as $configuration ) {
			if ( $configuration->get_format_key() === $name ) {
				return $configuration;
			}
		}

		return Webp_Configuration::get_instance();
	}

	private function switch_to_format( $format_key ) {
		$active_format_configuration = $this->get_active_format_configuration();
		$toggle_format_configuration = $this->get_format_configuration( $format_key );
		$next_gen_format_changed     = $active_format_configuration->get_format_key() !== $toggle_format_configuration->get_format_key();

		if ( $this->is_active() && ! $next_gen_format_changed ) {
			return;
		}

		// Activate selected module.
		if ( $this->is_active() ) {
			$new_format_key = $toggle_format_configuration->get_format_key();
			$old_format_key = $active_format_configuration->get_format_key();
			do_action( 'wp_smush_next_gen_before_format_switch', $new_format_key, $old_format_key );

			$active_format_configuration->toggle_module( false );
			$toggle_format_configuration->toggle_module( true );

			do_action( 'wp_smush_next_gen_after_format_switch', $new_format_key, $old_format_key );
		} else {
			$toggle_format_configuration->toggle_module( true );
		}
	}

	public function get_configuration_objects() {
		if ( ! $this->configuration_objects ) {
			$this->configuration_objects = $this->prepare_configuration_objects();
		}

		return $this->configuration_objects;
	}

	private function prepare_configuration_objects() {
		$configuration_objects = apply_filters( 'wp_smush_next_gen_configuration_objects', array() );
		$filtered              = array();

		foreach ( $configuration_objects as $key => $configuration_object ) {
			if ( is_a( $configuration_object, Next_Gen_Configuration_Interface::class ) ) {
				$filtered[ $key ] = $configuration_object;
			}
		}

		return $filtered;
	}

	public function save_previously_active_format_key( $format ) {
		update_option( self::PREVIOUSLY_ACTIVE_FORMAT_KEY, $format, false );
	}

	public function get_previously_active_format_key() {
		return get_option( self::PREVIOUSLY_ACTIVE_FORMAT_KEY );
	}
}
