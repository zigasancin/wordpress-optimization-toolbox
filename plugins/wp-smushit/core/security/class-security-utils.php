<?php

namespace Smush\Core\Security;

use Smush\Core\Array_Utils;
use Smush\Core\Threads\Thread_Safe_Options;

class Security_Utils {
	const EXPECTED_NONCES_OPTION = 'wp_smush_public_expected_nonces';
	/**
	 * @var Array_Utils
	 */
	private $array_utils;
	/**
	 * @var Thread_Safe_Options
	 */
	private $thread_safe_options;

	public function __construct() {
		$this->array_utils         = new Array_Utils();
		$this->thread_safe_options = new Thread_Safe_Options();
	}

	public function create_public_nonce( $action = - 1 ) {
		$nonce = wp_hash( wp_nonce_tick() . '|' . $action, 'nonce' );

		$added = $this->add_expected_nonce( $nonce );
		if ( ! $added ) {
			return false;
		}

		return $nonce;
	}

	public function verify_public_nonce( $nonce, $action = - 1 ) {
		$nonce_valid    = hash_equals( wp_hash( wp_nonce_tick() . '|' . $action, 'nonce' ), $nonce );
		$nonce_expected = $this->is_nonce_expected( $nonce );

		return $nonce_valid && $nonce_expected;
	}

	public function clean_public_nonce( $nonce ) {
		$this->thread_safe_options->remove_data( self::EXPECTED_NONCES_OPTION, $this->expected_nonce_key( $nonce ) );
	}

	private function add_expected_nonce( $nonce ) {
		return $this->thread_safe_options->add_data(
			self::EXPECTED_NONCES_OPTION,
			$this->expected_nonce_key( $nonce ),
			array( 'time' => time(), 'nonce' => $nonce )
		);
	}

	private function is_nonce_expected( $nonce ) {
		$expected_nonces = $this->get_expected_nonces();
		foreach ( $expected_nonces as $data ) {
			$now            = time();
			$time           = (int) $this->array_utils->get_array_value( $data, 'time' );
			$is_fresh       = ( $now - $time ) < $this->get_expected_nonce_expiry();
			$expected_nonce = $this->array_utils->get_array_value( $data, 'nonce' );
			if ( $is_fresh && $expected_nonce === $nonce ) {
				return true;
			}
		}

		return false;
	}

	public function clean_expected_nonces() {
		$expected_nonces = $this->clean_expected( $this->get_expected_nonces() );
		update_option( self::EXPECTED_NONCES_OPTION, json_encode( $expected_nonces ) );
	}

	private function clean_expected( $expected_nonces ) {
		$now = time();
		foreach ( $expected_nonces as $key => $data ) {
			$time = (int) $this->array_utils->get_array_value( $data, 'time' );
			if ( ( $now - $time ) > $this->get_expected_nonce_expiry() ) {
				unset( $expected_nonces[ $key ] );
			}
		}
		return $expected_nonces;
	}

	/**
	 * @return array
	 */
	public function get_expected_nonces() {
		$nonces = $this->thread_safe_options->get_option( self::EXPECTED_NONCES_OPTION, array() );

		return $this->array_utils->ensure_array( $nonces );
	}

	/**
	 * @return float|int
	 */
	private function get_expected_nonce_expiry() {
		// 15 minutes
		return MINUTE_IN_SECONDS * 15;
	}

	private function expected_nonce_key( $nonce ) {
		return "nonce_$nonce";
	}
}
