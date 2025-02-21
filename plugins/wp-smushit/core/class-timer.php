<?php

namespace Smush\Core;

class Timer {
	/**
	 * @var float
	 */
	private $time_start;

	public function start() {
		$this->time_start = microtime( true );
	}

	public function end() {
		$time_end = microtime( true );
		return round( $time_end - $this->time_start, 2 );
	}
}
