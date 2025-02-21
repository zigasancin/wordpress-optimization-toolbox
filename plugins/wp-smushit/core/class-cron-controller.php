<?php

namespace Smush\Core;

class Cron_Controller extends Controller {
	const CRON_HOOK = 'wp_smush_daily_cron';

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

	public function __construct() {
		$this->register_action( 'admin_init', array( $this, 'schedule_cron' ) );
	}

	/**
	 * Schedule cron
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule cron
	 */
	public function unschedule_cron() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}
}