<?php

namespace Smush\Core\Next_Gen;

use Smush\Core\Avif\Avif_Configuration;
use Smush\Core\Controller;
use Smush\Core\Settings;
use Smush\Core\Webp\Webp_Configuration;

class Next_Gen_Controller extends Controller {
	const DELETE_OLD_IMAGES_CRON_HOOK = 'wp_smush_next_gen_delete_old_images';
	const OLD_IMAGES_RETENTION_DAYS   = 7;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Next_Gen_Manager
	 */
	private $next_gen_manager;

	public function __construct() {
		$this->settings         = Settings::get_instance();
		$this->next_gen_manager = Next_Gen_Manager::get_instance();

		$this->register_action( 'wp_smush_webp_status_changed', array( $this, 'maybe_update_previously_active_format_key_on_webp_status_changed' ) );
		$this->register_action( 'wp_smush_avif_status_changed', array( $this, 'maybe_update_previously_active_format_key_on_avif_status_changed' ) );
		$this->register_action( 'wp_smush_next_gen_after_format_switch', array( $this, 'schedule_delete_old_next_gen_images_cron' ), 10, 2 );
		$this->register_action( self::DELETE_OLD_IMAGES_CRON_HOOK, array( $this, 'cron_delete_old_next_gen_images' ) );
	}

	public function maybe_update_previously_active_format_key_on_webp_status_changed() {
		if ( $this->settings->is_webp_module_active() ) {
			return;
		}

		$this->next_gen_manager->save_previously_active_format_key( Webp_Configuration::FORMAT_KEY );
	}

	public function maybe_update_previously_active_format_key_on_avif_status_changed() {
		if ( $this->settings->is_avif_module_active() ) {
			return;
		}

		$this->next_gen_manager->save_previously_active_format_key( Avif_Configuration::FORMAT_KEY );
	}

	public function schedule_delete_old_next_gen_images_cron( $new_format_key, $old_format_key ) {
		wp_unschedule_hook( self::DELETE_OLD_IMAGES_CRON_HOOK );

		// Schedules a new event.
		$cron_time = time() + DAY_IN_SECONDS * self::OLD_IMAGES_RETENTION_DAYS;
		wp_schedule_single_event( $cron_time, self::DELETE_OLD_IMAGES_CRON_HOOK, array( $old_format_key ) );
	}

	public function cron_delete_old_next_gen_images( $format_key ) {
		if ( ! wp_doing_cron() ) {
			return;
		}

		$configuration = $this->next_gen_manager->get_format_configuration( $format_key );
		if ( $configuration->is_activated() ) {
			return;
		}

		$configuration->delete_all_next_gen_files();
	}
}
