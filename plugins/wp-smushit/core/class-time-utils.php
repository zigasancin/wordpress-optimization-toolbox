<?php

namespace Smush\Core;

class Time_Utils {
	private $time;

	public function get_time() {
		if ( is_null( $this->time ) ) {
			return time();
		}

		return $this->time;
	}

	/**
	 * ONLY FOR TESTING
	 */
	public function set_time( $time ) {
		$this->time = $time;
	}
}
