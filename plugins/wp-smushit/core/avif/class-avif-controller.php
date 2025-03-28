<?php

namespace Smush\Core\Avif;

use Smush\Core\Controller;
use Smush\Core\Helper;
use Smush\Core\Media\Media_Item_Cache;
use Smush\Core\Settings;
use Smush\Core\Stats\Global_Stats;
use Smush\Core\Stats\Media_Item_Optimization_Global_Stats_Persistable;

class Avif_Controller extends Controller {
	const AVIF_OPTIMIZATION_ORDER  = 30;
	const AVIF_TRANSFORM_PRIORITY  = 40;
	const AVIF_CONFIGURATION_ORDER = 20;
	const GLOBAL_STATS_OPTION_ID   = 'wp-smush-avif-global-stats';

	/**
	 * @var Avif_Helper
	 */
	private $helper;
	/**
	 * @var Media_Item_Cache
	 */
	private $media_item_cache;
	/**
	 * @var \WDEV_Logger
	 */
	private $logger;
	/**
	 * @var Global_Stats
	 */
	private $global_stats;
	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Avif_Configuration
	 */
	private $configuration;

	public function __construct() {
		$this->helper           = new Avif_Helper();
		$this->media_item_cache = Media_Item_Cache::get_instance();
		$this->logger           = Helper::logger();
		$this->global_stats     = Global_Stats::get();
		$this->settings         = Settings::get_instance();
		$this->configuration    = new Avif_Configuration();

		$this->register_filter( 'wp_smush_optimizations', array(
			$this,
			'add_avif_optimization',
		), self::AVIF_OPTIMIZATION_ORDER, 2 );

		$this->register_filter( 'wp_smush_content_transforms', array(
			$this,
			'add_avif_transform',
		), self::AVIF_TRANSFORM_PRIORITY );

		$this->register_filter( 'wp_smush_next_gen_configuration_objects', array(
			$this,
			'add_avif_configuration',
		), self::AVIF_CONFIGURATION_ORDER );

		$this->register_action( 'wp_smush_before_restore_backup', array(
			$this,
			'delete_avif_versions_on_restore',
		), 10, 2 );

		$this->register_action( 'wp_smush_png_jpg_converted', array( $this, 'delete_avif_versions_of_pngs' ), 10, 4 );
		$this->register_action( 'delete_attachment', array( $this, 'delete_avif_versions_before_delete' ) );
		$this->register_filter( 'wp_smush_global_optimization_stats', array( $this, 'add_avif_global_stats' ) );
		$this->register_action( 'wp_smush_settings_updated', array( $this, 'maybe_mark_global_stats_as_outdated' ), 10, 2 );
		$this->register_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_fallback_js' ) );
		$this->register_action( 'wp_ajax_smush_avif_toggle', array( $this, 'ajax_avif_toggle' ) );
		$this->register_action( 'wp_ajax_smush_avif_delete_all', array( $this, 'ajax_delete_all_avif_files' ) );
	}

	public function maybe_enqueue_fallback_js() {
		if ( ! $this->settings->is_avif_fallback_active() ) {
			return;
		}
		$handle = 'smush-nextgen-fallback';
		wp_enqueue_script(
			$handle,
			WP_SMUSH_URL . 'app/assets/js/smush-nextgen-fallback.min.js',
			array(),
			WP_SMUSH_VERSION,
			true
		);
		wp_localize_script( $handle, 'wp_smushit_nextgen_data', array(
			'mode' => 'avif',
		) );
	}

	public function add_avif_optimization( $optimizations, $media_item ) {
		$avif_optimization                              = new Avif_Optimization( $media_item );
		$optimizations[ $avif_optimization->get_key() ] = $avif_optimization;

		return $optimizations;
	}

	public function add_avif_transform( $transforms ) {
		$transforms['avif'] = new Avif_Transform();

		return $transforms;
	}

	/**
	 * @param $backup_full_path
	 * @param $attachment_id
	 *
	 * @return bool
	 */
	public function delete_avif_versions_on_restore( $backup_full_path, $attachment_id ) {
		$media_item = $this->media_item_cache->get( $attachment_id );
		if ( ! $media_item->is_valid() ) {
			return false;
		}

		$this->helper->delete_media_item_avif_versions( $media_item );

		return true;
	}

	public function delete_avif_versions_of_pngs( $attachment_id, $meta, $stats, $png_paths ) {
		foreach ( $png_paths as $png_path ) {
			$this->helper->delete_avif_version( $png_path );
		}

		$this->delete_avif_meta( $attachment_id );
	}

	private function delete_avif_meta( $attachment_id ) {
		$media_item = $this->media_item_cache->get( $attachment_id );
		if ( $media_item->is_valid() ) {
			$avif_optimization = new Avif_Optimization( $media_item );
			$avif_optimization->delete_data();
		}
	}

	public function delete_avif_versions_before_delete( $attachment_id ) {
		$media_item = $this->media_item_cache->get( $attachment_id );
		if ( $media_item->is_valid() ) {
			$this->helper->delete_media_item_avif_versions( $media_item );
		} else {
			$this->logger->error( sprintf( 'Count not delete avif versions of the media item [%d]', $attachment_id ) );
		}
	}

	public function add_avif_global_stats( $stats ) {
		$stats[ Avif_Optimization::OPTIMIZATION_KEY ] = new Media_Item_Optimization_Global_Stats_Persistable( self::GLOBAL_STATS_OPTION_ID );

		return $stats;
	}

	public function maybe_mark_global_stats_as_outdated( $old_settings, $settings ) {
		$old_avif_status = ! empty( $old_settings['avif_mod'] );
		$new_avif_status = ! empty( $settings['avif_mod'] );
		if ( $old_avif_status !== $new_avif_status ) {
			$this->global_stats->mark_as_outdated();
		}
	}

	public function add_avif_configuration( $modules ) {
		$modules[ $this->configuration->get_format_key() ] = $this->configuration;

		return $modules;
	}

	public function ajax_avif_toggle() {
		check_ajax_referer( 'save_wp_smush_options' );

		$capability = is_multisite() ? 'manage_network' : 'manage_options';
		if ( ! Helper::is_user_allowed( $capability ) ) {
			wp_send_json_error(
				array(
					'message' => __( "You don't have permission to do this.", 'wp-smushit' ),
				),
				403
			);
		}

		$param       = isset( $_POST['param'] ) ? sanitize_text_field( wp_unslash( $_POST['param'] ) ) : '';
		$enable_avif = 'true' === $param;

		$this->configuration->toggle_module( $enable_avif );

		wp_send_json_success();
	}
	/**
	 * Delete all avif images.
	 * Triggered by the "Delete AVIF images" button in the avif tab.
	 */
	public function ajax_delete_all_avif_files() {
		check_ajax_referer( 'save_wp_smush_options' );

		$capability = is_multisite() ? 'manage_network' : 'manage_options';

		if ( ! Helper::is_user_allowed( $capability ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'This user can not delete all AVIF images.', 'wp-smushit' ),
				),
				403
			);
		}

		$this->helper->delete_all_avif_files();

		wp_send_json_success();
	}
}
