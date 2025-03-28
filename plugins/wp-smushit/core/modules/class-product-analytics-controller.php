<?php

namespace Smush\Core\Modules;

use Smush\Core\Array_Utils;
use Smush\Core\CDN\CDN_Helper;
use Smush\Core\Helper;
use Smush\Core\Media\Media_Item_Cache;
use Smush\Core\Media\Media_Item_Query;
use Smush\Core\Media_Library\Background_Media_Library_Scanner;
use Smush\Core\Media_Library\Media_Library_Last_Process;
use Smush\Core\Media_Library\Media_Library_Scan_Background_Process;
use Smush\Core\Media_Library\Media_Library_Scanner;
use Smush\Core\Modules\Background\Background_Pre_Flight_Controller;
use Smush\Core\Modules\Background\Background_Process;
use Smush\Core\Next_Gen\Next_Gen_Manager;
use Smush\Core\Product_Analytics;
use Smush\Core\Server_Utils;
use Smush\Core\Settings;
use Smush\Core\Stats\Global_Stats;
use Smush\Core\Webp\Webp_Configuration;
use WP_Smush;

class Product_Analytics_Controller {
	/**
	 * @var Settings
	 */
	private $settings;
	/**
	 * @var Media_Library_Scan_Background_Process
	 */
	private $scan_background_process;
	private $scanner_slice_size;

	/**
	 * @var Media_Library_Last_Process
	 */
	private $media_library_last_process;

	/**
	 * @var bool
	 */
	private $scan_background_process_dead = false;
	/**
	 * @var Product_Analytics
	 */
	private $product_analytics;

	/**
	 * @var Next_Gen_Manager
	 */
	private $next_gen_manager;

	public function __construct() {
		$this->settings                   = Settings::get_instance();
		$this->scan_background_process    = Background_Media_Library_Scanner::get_instance()->get_background_process();
		$this->media_library_last_process = Media_Library_Last_Process::get_instance();
		$this->product_analytics          = Product_Analytics::get_instance();
		$this->next_gen_manager           = Next_Gen_Manager::get_instance();

		$this->hook_actions();
	}

	private function hook_actions() {
		// Setting events.
		add_action( 'wp_smush_settings_updated', array( $this, 'track_opt_toggle' ), 10, 2 );
		add_action( 'wp_smush_settings_updated', array( $this, 'intercept_settings_update' ), 10, 2 );
		add_action( 'wp_smush_settings_deleted', array( $this, 'intercept_reset' ) );
		add_action( 'wp_smush_settings_updated', array( $this, 'track_integrations_saved' ), 10, 2 );
		add_action( 'wp_smush_settings_updated', array( $this, 'track_toggle_next_gen_fallback' ), 10, 2 );

		add_action( 'wp_ajax_smush_track_deactivate', array( $this, 'ajax_track_deactivation_survey' ) );
		add_action( 'wp_ajax_smush_analytics_track_event', array( $this, 'ajax_handle_track_request' ) );

		if ( ! $this->settings->get( 'usage' ) ) {
			return;
		}

		// Other events.
		add_action( 'wp_smush_directory_smush_start', array( $this, 'track_directory_smush' ) );
		add_action( 'wp_smush_bulk_smush_start', array( $this, 'track_bulk_smush_start' ), 20 );
		add_action( 'wp_smush_bulk_smush_completed', array( $this, 'track_background_bulk_smush_completed' ) );
		add_action( 'wp_smush_bulk_smush_dead', array( $this, 'track_bulk_smush_background_process_death' ) );
		add_action( 'wp_smush_config_applied', array( $this, 'track_config_applied' ) );
		add_action( 'wp_smush_webp_method_changed', array( $this, 'track_webp_method_changed' ) );
		add_action( 'wp_smush_webp_status_changed', array( $this, 'track_next_gen_status_changed' ) );
		add_action( 'wp_smush_avif_status_changed', array( $this, 'track_next_gen_status_changed' ) );
		add_action( 'wp_smush_after_delete_all_webp_files', array(
			$this,
			'track_deleting_all_next_gen_files',
		) );
		add_action( 'wp_smush_after_delete_all_avif_files', array( $this,
			'track_deleting_all_next_gen_files',
		) );
		add_action( 'wp_ajax_smush_toggle_webp_wizard', array( $this, 'track_webp_reconfig' ), - 1 );
		add_action( 'shutdown', array( $this, 'maybe_track_next_gen_format_changed' ) );

		$identifier          = $this->scan_background_process->get_identifier();
		$scan_started_action = "{$identifier}_started";
		$scan_dead_action    = "{$identifier}_dead";

		add_action( "{$identifier}_before_start", array( $this, 'record_scan_death' ), 10, 2 );
		add_action( $scan_started_action, array( $this, 'track_background_scan_start' ), 10, 2 );
		add_action( "{$identifier}_completed", array( $this, 'track_background_scan_end' ), 10, 2 );

		add_action( $scan_dead_action, array( $this, 'track_background_scan_process_death' ) );

		add_action( 'wp_smush_plugin_activated', array( $this, 'track_plugin_activation' ) );
		if ( defined( 'WP_SMUSH_BASENAME' ) ) {
			$plugin_basename = WP_SMUSH_BASENAME;
			add_action( "deactivate_$plugin_basename", array( $this, 'track_plugin_deactivation' ) );
		}

		add_action( 'wp_smush_bulk_smush_stuck', array( $this, 'track_bulk_smush_progress_stuck' ) );

		add_action( 'wp_smush_lazy_load_updated', array( $this, 'track_lazy_load_settings_updated' ), 10, 2 );
	}

	private function track( $event, $properties = array() ) {
		$this->product_analytics->track( $event, $properties );
	}

	public function intercept_settings_update( $old_settings, $settings ) {
		if ( empty( $settings['usage'] ) ) {
			// Use the most up-to-data value of 'usage'
			return;
		}

		$settings = $this->remove_unchanged_settings( $old_settings, $settings );
		$handled  = $this->maybe_track_feature_toggle( $settings );

		if ( ! $handled ) {
			$this->maybe_track_cdn_update( $settings );
		}
	}

	private function maybe_track_feature_toggle( array $settings ) {
		foreach ( $settings as $setting_key => $setting_value ) {
			$handler = "track_{$setting_key}_feature_toggle";
			if ( method_exists( $this, $handler ) ) {
				call_user_func( array( $this, $handler ), $setting_value );

				return true;
			}
		}

		return false;
	}

	private function remove_unchanged_settings( $old_settings, $settings ) {
		$changed = array();
		foreach ( $settings as $setting_key => $setting_value ) {
			$old_setting_value = isset( $old_settings[ $setting_key ] ) ? $old_settings[ $setting_key ] : '';
			$setting_value     = isset( $setting_value ) ? $setting_value : '';
			if ( $old_setting_value !== $setting_value ) {
				$changed[ $setting_key ] = $setting_value;
			}
		}

		return $changed;
	}

	public function get_bulk_properties() {
		$bulk_property_labels = array(
			'auto'       => 'Automatic Compression',
			'strip_exif' => 'Metadata',
			'resize'     => 'Resize Original Images',
			'original'   => 'Compress original images',
			'backup'     => 'Backup original images',
			'png_to_jpg' => 'Auto-convert PNGs to JPEGs (lossy)',
			'no_scale'   => 'Disable scaled images',
		);

		$image_sizes     = Settings::get_instance()->get_setting( 'wp-smush-image_sizes' );
		$bulk_properties = array(
			'Image Sizes'         => empty( $image_sizes ) ? 'All' : 'Custom',
			'Mode'                => $this->get_current_lossy_level_label(),
			'Parallel Processing' => $this->get_parallel_processing_status(),
			'Smush Type'          => $this->get_smush_type(),
		);

		foreach ( $bulk_property_labels as $bulk_setting => $bulk_property_label ) {
			$property_value                          = Settings::get_instance()->get( $bulk_setting )
				? 'Enabled'
				: 'Disabled';
			$bulk_properties[ $bulk_property_label ] = $property_value;
		}

		return $bulk_properties;
	}

	private function get_parallel_processing_status() {
		return defined( 'WP_SMUSH_PARALLEL' ) && WP_SMUSH_PARALLEL ? 'Enabled' : 'Disabled';
	}

	private function get_smush_type(): string {
		if ( $this->settings->is_webp_module_active() ) {
			return 'WebP';
		}

		if ( $this->settings->is_avif_module_active() ) {
			return 'AVIF';
		}

		return 'Classic';
	}

	private function get_current_lossy_level_label() {
		$lossy_level = $this->settings->get_lossy_level_setting();
		$smush_modes = array(
			Settings::LEVEL_LOSSLESS    => 'Basic',
			Settings::LEVEL_SUPER_LOSSY => 'Super',
			Settings::LEVEL_ULTRA_LOSSY => 'Ultra',
		);
		if ( ! isset( $smush_modes[ $lossy_level ] ) ) {
			$lossy_level = Settings::LEVEL_LOSSLESS;
		}

		return $smush_modes[ $lossy_level ];
	}

	private function track_detection_feature_toggle( $setting_value ) {
		return $this->track_feature_toggle( $setting_value, 'Image Resize Detection' );
	}

	protected function track_webp_mod_feature_toggle( $setting_value ) {
		if ( $this->is_switching_next_gen_format() ) {
			return;
		}

		return $this->track_feature_toggle( $setting_value, 'Next-Gen' );
	}

	protected function track_avif_mod_feature_toggle( $setting_value ) {
		if ( $this->is_switching_next_gen_format() ) {
			return;
		}

		return $this->track_feature_toggle( $setting_value, 'Next-Gen' );
	}

	private function is_switching_next_gen_format() {
		return did_action( 'wp_smush_next_gen_before_format_switch' );
	}

	private function track_cdn_feature_toggle( $setting_value ) {
		return $this->track_feature_toggle( $setting_value, 'CDN' );
	}

	private function track_lazy_load_feature_toggle( $setting_value ) {
		$this->track_lazy_load_feature_updated_on_toggle( $setting_value );

		return $this->track_feature_toggle( $setting_value, 'Lazy Load' );
	}

	private function track_lazy_load_feature_updated_on_toggle( $activate ) {
		$this->track_lazy_load_updated(
			array(
				'update_type'       => $activate ? 'activate' : 'deactivate',
				'modified_settings' => 'na',
			),
			$this->settings->get_setting( 'wp-smush-lazy_load', array() )
		);
	}

	private function track_feature_toggle( $active, $feature ) {
		$event = $active
			? 'Feature Activated'
			: 'Feature Deactivated';

		$this->track( $event, array(
			'Feature'        => $feature,
			'Triggered From' => $this->identify_referrer(),
		) );

		return true;
	}

	private function get_next_gen_referer() {
		$page                   = $this->get_referer_page();
		$webp_configuration     = Webp_Configuration::get_instance();
		$is_user_on_wizard_webp = 'smush-next-gen' === $page
		                          && $webp_configuration->should_show_wizard()
		                          && ! $webp_configuration->direct_conversion_enabled();

		if ( $is_user_on_wizard_webp ) {
			return 'Wizard';
		}

		return $this->identify_referrer();
	}

	private function identify_referrer() {
		$onboarding_request = ! empty( $_REQUEST['action'] ) && 'smush_setup' === $_REQUEST['action'];
		if ( $onboarding_request ) {
			return 'Wizard';
		}

		$page           = $this->get_referer_page();
		$triggered_from = array(
			'smush'              => 'Dashboard',
			'smush-bulk'         => 'Bulk Smush',
			'smush-directory'    => 'Directory Smush',
			'smush-lazy-load'    => 'Lazy Load',
			'smush-cdn'          => 'CDN',
			'smush-next-gen'     => 'Next-Gen Formats',
			'smush-integrations' => 'Integrations',
			'smush-settings'     => 'Settings',
		);

		return empty( $triggered_from[ $page ] )
			? ''
			: $triggered_from[ $page ];
	}

	private function maybe_track_cdn_update( $settings ) {
		$cdn_properties      = array();
		$cdn_property_labels = $this->cdn_property_labels();
		foreach ( $settings as $setting_key => $setting_value ) {
			if ( array_key_exists( $setting_key, $cdn_property_labels ) ) {
				$property_label                    = $cdn_property_labels[ $setting_key ];
				$property_value                    = $setting_value ? 'Enabled' : 'Disabled';
				$cdn_properties[ $property_label ] = $property_value;
			}
		}

		if ( isset( $settings[ Settings::NEXT_GEN_CDN_KEY ] ) ) {
			$cdn_next_gen_conversions_mode = $this->settings->sanitize_cdn_next_gen_conversion_mode( $settings[ Settings::NEXT_GEN_CDN_KEY ] );
			$cdn_next_gen_conversions      = array(
				Settings::NONE_CDN_MODE => 'None',
				Settings::WEBP_CDN_MODE => 'WebP',
				Settings::AVIF_CDN_MODE => 'AVIF',
			);
			if ( ! isset( $cdn_next_gen_conversions[ $cdn_next_gen_conversions_mode ] ) ) {
				$cdn_next_gen_conversions_mode = Settings::NONE_CDN_MODE;
			}

			$cdn_properties['Next-Gen Conversions'] = $cdn_next_gen_conversions[ $cdn_next_gen_conversions_mode ];
		}

		if ( $cdn_properties ) {
			$this->track( 'CDN Updated', $cdn_properties );

			return true;
		}

		return false;
	}

	private function cdn_property_labels() {
		return array(
			'background_images' => 'Background Images',
			'auto_resize'       => 'Automatic Resizing',
			'rest_api_support'  => 'Rest API',
		);
	}

	public function track_directory_smush() {
		$this->track( 'Directory Smushed' );
	}

	public function track_bulk_smush_start() {
		$properties = $this->get_bulk_properties();
		$properties = array_merge(
			$properties,
			array(
				'process_id'              => $this->get_process_id(),
				'Background Optimization' => $this->get_background_optimization_status(),
				'Cron'                    => $this->get_cron_healthy_status(),
			)
		);
		$this->track( 'Bulk Smush Started', $properties );
	}

	private function get_process_id() {
		return md5( $this->media_library_last_process->get_process_start_time() );
	}

	/**
	 * Track the event on background optimization completed.
	 * Note: For ajax Bulk Smush, we will track it via js.
	 *
	 * @return void
	 */
	public function track_background_bulk_smush_completed() {
		$bg_optimization    = WP_Smush::get_instance()->core()->mod->bg_optimization;
		$total_items        = $bg_optimization->get_total_items();
		$failed_items       = $bg_optimization->get_failed_items();
		$failure_percentage = $total_items > 0 ? round( $failed_items * 100 / $total_items ) : 0;

		$properties = array_merge(
			$this->get_bulk_smush_stats(),
			array(
				'Total Enqueued Images' => $total_items,
				'Failure Percentage'    => $failure_percentage,
			)
		);
		$properties = $this->filter_bulk_smush_completed_properties( $properties );

		$this->track( 'Bulk Smush Completed', $properties );
	}

	/**
	 * Add extra properties to the bulk smush completed event for Bulk Smush include ajax method.
	 *
	 * @param array $properties Bulk Smush completed properties.
	 */
	protected function filter_bulk_smush_completed_properties( $properties ) {
		return array_merge(
			$properties,
			array(
				'process_id'              => $this->get_process_id(),
				'Background Optimization' => $this->get_background_optimization_status(),
				'Cron'                    => $this->get_cron_healthy_status(),
				'Time Elapsed'            => $this->media_library_last_process->get_process_elapsed_time(),
				'Smush Type'              => $this->get_smush_type(),
				'Mode'                    => $this->get_current_lossy_level_label(),
			)
		);
	}

	private function get_bulk_smush_stats() {
		$global_stats = WP_Smush::get_instance()->core()->get_global_stats();
		$array_util   = new Array_Utils();

		return array(
			'Total Savings'                 => $this->convert_to_megabytes( (int) $array_util->get_array_value( $global_stats, 'savings_bytes' ) ),
			'Total Images'                  => (int) $array_util->get_array_value( $global_stats, 'count_images' ),
			'Media Optimization Percentage' => (float) $array_util->get_array_value( $global_stats, 'percent_optimized' ),
			'Percentage of Savings'         => (float) $array_util->get_array_value( $global_stats, 'savings_percent' ),
			'Images Resized'                => (int) $array_util->get_array_value( $global_stats, 'count_resize' ),
			'Resize Savings'                => $this->convert_to_megabytes( (int) $array_util->get_array_value( $global_stats, 'savings_resize' ) ),
		);
	}

	public function track_config_applied( $config_name ) {
		$properties = $config_name
			? array( 'Config Name' => $config_name )
			: array();

		$properties['Triggered From'] = $this->identify_referrer();

		$this->track( 'Config Applied', $properties );
	}

	public function track_opt_toggle( $old_settings, $settings ) {
		$settings = $this->remove_unchanged_settings( $old_settings, $settings );

		if ( isset( $settings['usage'] ) ) {
			// Following the new change, the location for Opt In/Out is lowercase and none whitespace.
			// @see SMUSH-1538.
			$location = str_replace( ' ', '_', $this->identify_referrer() );
			$location = strtolower( $location );
			$this->track(
				$settings['usage'] ? 'Opt In' : 'Opt Out',
				array(
					'Location'       => $location,
					'active_plugins' => $this->get_active_plugins(),
				)
			);
		}
	}

	public function track_integrations_saved( $old_settings, $settings ) {
		if ( empty( $settings['usage'] ) ) {
			return;
		}

		$settings = $this->remove_unchanged_settings( $old_settings, $settings );
		if ( empty( $settings ) ) {
			return;
		}

		$this->maybe_track_integrations_toggle( $settings );
	}

	private function maybe_track_integrations_toggle( $settings ) {
		$integrations = array(
			'gutenberg'  => 'Gutenberg',
			'gform'      => 'Gravity Forms',
			'js_builder' => 'WP Bakery',
			's3'         => 'Amazon S3',
			'nextgen'    => 'NextGen Gallery',
		);

		foreach ( $settings as $integration_slug => $is_activated ) {
			if ( ! array_key_exists( $integration_slug, $integrations ) ) {
				continue;
			}

			if ( $is_activated ) {
				$this->track(
					'Integration Activated',
					array(
						'Integration' => $integrations[ $integration_slug ],
					)
				);
			} else {
				$this->track(
					'Integration Deactivated',
					array(
						'Integration' => $integrations[ $integration_slug ],
					)
				);
			}
		}
	}

	public function intercept_reset() {
		if ( $this->settings->get( 'usage' ) ) {
			$this->track(
				'Opt Out',
				array(
					'Location'       => 'reset',
					'active_plugins' => $this->get_active_plugins(),
				)
			);
		}
	}

	public function record_scan_death() {
		$this->scan_background_process_dead = $this->scan_background_process->get_status()->is_dead();
	}

	public function track_background_scan_start( $identifier, $background_process ) {
		$type = $this->scan_background_process_dead
			? 'Retry'
			: 'New';

		$this->_track_background_scan_start( $type, $background_process );
	}

	private function _track_background_scan_start( $type, $background_process ) {
		$properties = array(
			'Scan Type' => $type,
		);

		$this->track( 'Scan Started', array_merge(
			$properties,
			$this->get_bulk_properties(),
			$this->get_scan_properties()
		) );
	}

	/**
	 * @param $identifier
	 * @param $background_process Background_Process
	 *
	 * @return void
	 */
	public function track_background_scan_end( $identifier, $background_process ) {
		$properties = array(
			'Retry Attempts' => $background_process->get_revival_count(),
			'Time Elapsed'   => $this->media_library_last_process->get_process_elapsed_time(),
		);
		$this->track( 'Scan Ended', array_merge(
			$properties,
			$this->get_bulk_properties(),
			$this->get_scan_properties()
		) );
	}

	public function track_background_scan_process_death() {
		$this->track(
			'Background Process Dead',
			array_merge(
				array(
					'Process Type' => 'Scan',
					'Slice Size'   => $this->get_scanner_slice_size(),
					'Time Elapsed' => $this->media_library_last_process->get_process_elapsed_time(),
					'Smush Type'   => $this->get_smush_type(),
					'Mode'         => $this->get_current_lossy_level_label(),
				),
				$this->get_scan_background_process_properties()
			)
		);
	}

	public function track_bulk_smush_background_process_death() {
		$this->track(
			'Background Process Dead',
			array_merge(
				array(
					'Process Type' => 'Smush',
					'Slice Size'   => 0,
					'Time Elapsed' => $this->media_library_last_process->get_process_elapsed_time(),
					'Smush Type'   => $this->get_smush_type(),
					'Mode'         => $this->get_current_lossy_level_label(),
				),
				$this->get_bulk_background_process_properties()
			)
		);
	}

	private function get_scan_properties() {
		$global_stats       = Global_Stats::get();
		$global_stats_array = $global_stats->to_array();
		$properties         = array(
			'process_id' => $this->get_process_id(),
			'Slice Size' => $this->get_scanner_slice_size(),
		);

		$labels = array(
			'image_attachment_count' => 'Image Attachment Count',
			'optimized_images_count' => 'Optimized Images Count',
			'optimize_count'         => 'Optimize Count',
			'reoptimize_count'       => 'Reoptimize Count',
			'ignore_count'           => 'Ignore Count',
			'animated_count'         => 'Animated Count',
			'error_count'            => 'Error Count',
			'percent_optimized'      => 'Percent Optimized',
			'size_before'            => 'Size Before',
			'size_after'             => 'Size After',
			'savings_percent'        => 'Savings Percent',
		);

		$savings_keys = array(
			'size_before',
			'size_after',
		);

		foreach ( $labels as $key => $label ) {
			if ( isset( $global_stats_array[ $key ] ) ) {
				$properties[ $label ] = $global_stats_array[ $key ];

				if ( in_array( $key, $savings_keys, true ) ) {
					$properties[ $label ] = $this->convert_to_megabytes( $properties[ $label ] );
				}
			}
		}

		return $properties;
	}

	private function get_bulk_background_process_properties() {
		$bg_optimization = WP_Smush::get_instance()->core()->mod->bg_optimization;
		$process_id      = $this->get_process_id();

		if ( ! $bg_optimization->is_background_enabled() ) {
			return array(
				'process_id' => $process_id,
			);
		}

		$total_items     = $bg_optimization->get_total_items();
		$processed_items = $bg_optimization->get_processed_items();

		return array(
			'process_id'             => $process_id,
			'Retry Attempts'         => $bg_optimization->get_revival_count(),
			'Total Enqueued Images'  => $total_items,
			'Completion Percentage'  => $this->get_background_process_completion_percentage( $total_items, $processed_items ),
			'Total Processed Images' => $processed_items,
		);
	}

	private function get_scan_background_process_properties() {
		$query                  = new Media_Item_Query();
		$total_enqueued_images  = $query->get_image_attachment_count();
		$total_items            = $this->scan_background_process->get_status()->get_total_items();
		$processed_items        = $this->scan_background_process->get_status()->get_processed_items();
		$scanner_slice_size     = $this->get_scanner_slice_size();
		$total_processed_images = $processed_items * $scanner_slice_size;
		$total_processed_images = min( $total_processed_images, $total_enqueued_images );

		return array(
			'process_id'             => $this->get_process_id(),
			'Retry Attempts'         => $this->scan_background_process->get_revival_count(),
			'Total Enqueued Images'  => $total_enqueued_images,
			'Completion Percentage'  => $this->get_background_process_completion_percentage( $total_items, $processed_items ),
			'Total Processed Images' => $total_processed_images,
		);
	}

	private function get_background_process_completion_percentage( $total_items, $processed_items ) {
		if ( $total_items < 1 ) {
			return 0;
		}

		return ceil( $processed_items * 100 / $total_items );
	}

	private function convert_to_megabytes( $size_in_bytes ) {
		if ( empty( $size_in_bytes ) ) {
			return 0;
		}
		$unit_mb = pow( 1024, 2 );
		return round( $size_in_bytes / $unit_mb, 2 );
	}

	private function get_scanner_slice_size() {
		if ( is_null( $this->scanner_slice_size ) ) {
			$this->scanner_slice_size = ( new Media_Library_Scanner() )->get_slice_size();
		}

		return $this->scanner_slice_size;
	}

	public function track_toggle_next_gen_fallback( $old_settings, $settings ) {
		if ( empty( $settings['usage'] ) ) {
			return;
		}

		$webp_activated     = ! empty( $settings['webp_mod'] );
		$avif_activated     = ! empty( $settings['avif_mod'] );
		$next_gen_activated = $webp_activated || $avif_activated;
		// Do not track when Next Gen is not activated.
		if ( ! $next_gen_activated ) {
			return;
		}

		$modified_settings         = $this->remove_unchanged_settings( $old_settings, $settings );
		$next_gen_fallback_changed = isset( $modified_settings['webp_fallback'] ) || isset( $modified_settings['avif_fallback'] );
		// Do not track if both WebP and AVIF fallbacks are not changed.
		if ( ! $next_gen_fallback_changed ) {
			return;
		}

		$webp_fallback_activated = ! empty( $settings['webp_fallback'] );
		$avif_fallback_activated = ! empty( $settings['avif_fallback'] );
		// Do not track if both WebP and AVIF fallbacks have the same status while switching the Next-Gen formats.
		if ( $this->is_switching_next_gen_format() && ( $webp_fallback_activated === $avif_fallback_activated ) ) {
			return;
		}

		$next_gen_fallback_activated = ( $webp_activated && $webp_fallback_activated )
										|| ( $avif_activated && $avif_fallback_activated );

		$update_type         = $next_gen_fallback_activated ? 'browser_support_on' : 'browser_support_off';
		$next_gen_properties = $this->get_next_gen_properties();
		$next_gen_method     = 'avif_direct';
		if ( $webp_activated ) {
			$direct_conversion_enabled = ! empty( $settings['webp_direct_conversion'] );// WebP method might or might not be changed.
			$next_gen_method           = $direct_conversion_enabled ? 'webp_direct' : 'server_redirect';
		}

		$this->track(
			'next_gen_updated',
			array_merge(
				$next_gen_properties,
				array(
					'update_type' => $update_type,
					'Method'      => $next_gen_method,
				)
			)
		);
	}

	public function track_deleting_all_next_gen_files() {
		$auto_deleting_old_next_gen_files = wp_doing_cron();
		if ( $auto_deleting_old_next_gen_files ) {
			return;
		}

		$next_gen_properties = $this->get_next_gen_properties();
		$this->track(
			'next_gen_updated',
			array_merge(
				$next_gen_properties,
				array(
					'update_type' => 'delete_files',
				)
			)
		);
	}

	public function track_webp_method_changed() {
		$next_gen_properties = $this->get_next_gen_properties();
		$this->track(
			'next_gen_updated',
			array_merge(
				$next_gen_properties,
				array(
					'update_type' => 'switch_webp_method',
				)
			)
		);
	}

	public function track_next_gen_status_changed() {
		if ( $this->is_switching_next_gen_format() ) {
			return;
		}

		$next_gen_properties = $this->get_next_gen_properties();
		$update_type         = $this->next_gen_manager->is_active() ? 'activate' : 'deactivate';
		$this->track(
			'next_gen_updated',
			array_merge(
				$next_gen_properties,
				array(
					'update_type' => $update_type,
				)
			)
		);
	}

	/**
	 * Note: Uses shutdown action to ensure all new settings are updated.
	 */
	public function maybe_track_next_gen_format_changed() {
		$switched_next_gen_format = did_action( 'wp_smush_next_gen_after_format_switch' );
		if ( ! $switched_next_gen_format ) {
			return;
		}

		$next_gen_properties = $this->get_next_gen_properties();
		$this->track(
			'next_gen_updated',
			array_merge(
				$next_gen_properties,
				array(
					'update_type' => 'switch_next_gen_format',
				)
			)
		);
	}

	private function get_next_gen_properties() {
		$location                    = $this->get_next_gen_referer();
		$active_format_configuration = $this->next_gen_manager->get_active_format_configuration();
		$next_gen_status_notice      = $this->get_next_gen_status_notice();
		$next_gen_method             = 'avif_direct';
		if ( Webp_Configuration::FORMAT_KEY === $active_format_configuration->get_format_key() ) {
			// Directly check webp_direct_conversion option to identify webp method even webp module is disabled.
			$direct_conversion_enabled = $this->settings->get( 'webp_direct_conversion' );
			$next_gen_method           = $direct_conversion_enabled ? 'webp_direct' : 'webp_server';
		}

		return array(
			'Location'      => $location,
			'Method'        => $next_gen_method,
			'status_notice' => $next_gen_status_notice,
		);
	}

	private function get_next_gen_status_notice() {
		if ( ! $this->next_gen_manager->is_active() ) {
			return 'na';
		}

		if ( ! $this->next_gen_manager->is_configured() ) {
			$webp_configuration = Webp_Configuration::get_instance();
			return $webp_configuration->server_configuration()->get_configuration_error_code();
		}

		if ( is_multisite() ) {
			return 'active_subsite';// Activated but required run Bulk Smush on subsites.
		}

		$required_bulk_smush = Global_Stats::get()->is_outdated() || Global_Stats::get()->get_remaining_count() > 0;
		if ( $required_bulk_smush ) {
			return 'active_need_smush';
		}

		$auto_smush_enabled = $this->settings->is_automatic_compression_active();
		if ( $auto_smush_enabled ) {
			return 'active_automatic_enabled';
		}

		return 'active_automatic_disabled';
	}

	public function track_webp_reconfig() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$next_gen_properties = $this->get_next_gen_properties();
		$this->track(
			'next_gen_updated',
			array_merge(
				$next_gen_properties,
				array(
					'update_type' => 'reconfig',
				)
			)
		);
	}

	private function get_referer_page() {
		$path       = parse_url( wp_get_referer(), PHP_URL_QUERY );
		$query_vars = array();
		parse_str( $path, $query_vars );

		return empty( $query_vars['page'] ) ? '' : $query_vars['page'];
	}

	public function track_plugin_activation() {
		$this->track(
			'Opt In',
			array(
				'Location'       => 'reactivate',
				'active_plugins' => $this->get_active_plugins(),
			)
		);
	}

	public function track_plugin_deactivation() {
		$location = $this->get_deactivation_location();
		$this->track(
			'Opt Out',
			array(
				'Location'       => $location,
				'active_plugins' => $this->get_active_plugins(),
			)
		);
	}

	private function get_deactivation_location() {
		$is_hub_request = ! empty( $_REQUEST['wpmudev-hub'] );
		if ( $is_hub_request ) {
			return 'deactivate_hub';
		}

		$is_dashboard_request = wp_doing_ajax() &&
		                        ! empty( $_REQUEST['action'] ) &&
		                        'wdp-project-deactivate' === wp_unslash( $_REQUEST['action'] );

		if ( $is_dashboard_request ) {
			return 'deactivate_dashboard';
		}

		return 'deactivate_pluginlist';
	}

	private function get_active_plugins() {
		$active_plugins      = array();
		$active_plugin_files = $this->get_active_and_valid_plugin_files();
		foreach ( $active_plugin_files as $plugin_file ) {
			$plugin_name = $this->get_plugin_name( $plugin_file );
			if ( $plugin_name ) {
				$active_plugins[] = $plugin_name;
			}
		}

		return $active_plugins;
	}

	private function get_active_and_valid_plugin_files() {
		$active_plugins = is_multisite() ? wp_get_active_network_plugins() : array();
		$active_plugins = array_merge( $active_plugins, wp_get_active_and_valid_plugins() );

		return array_unique( $active_plugins );
	}

	private function get_plugin_name( $plugin_file ) {
		$plugin_data = get_plugin_data( $plugin_file );

		return ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : '';
	}

	private function get_cron_healthy_status() {
		$is_cron_healthy = Background_Pre_Flight_Controller::get_instance()->is_cron_healthy();
		return $is_cron_healthy ? 'Enabled' : 'Disabled';
	}

	private function get_background_optimization_status() {
		$bg_optimization = WP_Smush::get_instance()->core()->mod->bg_optimization;
		return $bg_optimization->is_background_enabled() ? 'Enabled' : 'Disabled';
	}

	public function ajax_handle_track_request() {
		$event_name = $this->get_event_name();
		if ( ! check_ajax_referer( 'wp-smush-ajax' ) || ! Helper::is_user_allowed() || empty( $event_name ) ) {
			wp_send_json_error();
		}

		$properties = $this->get_event_properties( $event_name );

		if ( ! $this->allow_to_track( $event_name, $properties ) ) {
			wp_send_json_error();
		}

		$this->track(
			$event_name,
			$properties
		);

		wp_send_json_success();
	}

	private function allow_to_track( $event_name, $properties ) {
		$trackable_events   = array(
			'Setup Wizard'     => true,
			'smush_pro_upsell' => isset( $properties['Location'] ) && 'wizard' === $properties['Location'],
		);
		$is_trackable_event = ! empty( $trackable_events[ $event_name ] );

		return $is_trackable_event || $this->settings->get( 'usage' );
	}

	private function get_event_name() {
		return isset( $_POST['event'] ) ? sanitize_text_field( wp_unslash( $_POST['event'] ) ) : '';
	}

	private function get_event_properties( $event_name ) {
		$properties = isset( $_POST['properties'] ) && is_array( $_POST['properties'] ) ? wp_unslash( $_POST['properties'] ) : array();
		$properties = map_deep( $properties, 'sanitize_text_field' );

		$filter_callback = $this->get_filter_properties_callback( $event_name );
		if ( method_exists( $this, $filter_callback ) ) {
			$properties = call_user_func( array( $this, $filter_callback ), $properties );
		}

		return $properties;
	}

	private function get_filter_properties_callback( $event_name ) {
		$event_name = str_replace( ' ', '_', $event_name );
		$event_name = sanitize_key( $event_name );
		return "filter_{$event_name}_properties";
	}

	/**
	 * Filter properties for Scan Interrupted event.
	 *
	 * @param array $properties JS properties.
	 */
	protected function filter_scan_interrupted_properties( $properties ) {
		return array_merge(
			$properties,
			array(
				'Slice Size'              => $this->get_scanner_slice_size(),
				'Background Optimization' => $this->get_background_optimization_status(),
				'Cron'                    => $this->get_cron_healthy_status(),
				'Time Elapsed'            => $this->media_library_last_process->get_process_elapsed_time(),
				'Smush Type'              => $this->get_smush_type(),
				'Mode'                    => $this->get_current_lossy_level_label(),
				'WP Loopback Status'      => $this->get_wp_loopback_status( $properties ),
			),
			$this->get_scan_background_process_properties(),
			$this->get_last_image_process_properties()
		);
	}


	private function get_last_image_process_properties() {
		$last_image_id = $this->media_library_last_process->get_last_process_attachment_id();
		if ( ! $last_image_id ) {
			return array();
		}

		$media_item              = Media_Item_Cache::get_instance()->get( $last_image_id );
		$last_image_time_elapsed = $this->media_library_last_process->get_last_process_attachment_elapsed_time();
		$properties              = array(
			'Last Image Time Elapsed' => $last_image_time_elapsed,
		);

		if ( ! $media_item->is_valid() ) {
			return $properties;
		}

		$full_size = $media_item->get_full_or_scaled_size();
		if ( ! $full_size ) {
			return $properties;
		}

		$file_size    = $this->convert_to_megabytes( $full_size->get_filesize() );
		$image_width  = $full_size->get_width();
		$image_height = $full_size->get_height();
		$image_type   = strtoupper( $full_size->get_extension() );

		return array(
			'Last Image Time Elapsed' => $last_image_time_elapsed,
			'Last Image Size'         => $file_size,
			'Last Image Width'        => $image_width,
			'Last Image Height'       => $image_height,
			'Last Image Type'         => $image_type,
		);
	}

	/**
	 * Filter properties for Bulk Smush interrupted event.
	 *
	 * @param array $properties JS properties.
	 */
	protected function filter_bulk_smush_interrupted_properties( $properties ) {
		return array_merge(
			$properties,
			array(
				'Background Optimization' => $this->get_background_optimization_status(),
				'Cron'                    => $this->get_cron_healthy_status(),
				'Parallel Processing'     => $this->get_parallel_processing_status(),
				'Time Elapsed'            => $this->media_library_last_process->get_process_elapsed_time(),
				'Smush Type'              => $this->get_smush_type(),
				'Mode'                    => $this->get_current_lossy_level_label(),
				'WP Loopback Status'      => $this->get_wp_loopback_status( $properties ),
			),
			$this->get_bulk_background_process_properties(),
			$this->get_last_image_process_properties()
		);
	}

	public function ajax_track_deactivation_survey() {
		$event_name = $this->get_event_name();
		if ( ! check_ajax_referer( 'wp-smush-ajax' ) || ! Helper::is_user_allowed() || empty( $event_name ) ) {
			wp_send_json_error();
		}

		$properties = $this->get_event_properties( $event_name );
		$properties = array_merge(
			$properties,
			array(
				'active_features' => $this->get_active_features(),
				'active_plugins'  => $this->get_active_plugins(),
			)
		);

		$this->track(
			$event_name,
			$properties
		);

		wp_send_json_success();
	}

	private function get_active_features() {
		$lossy_level           = $this->settings->get_lossy_level_setting();
		$cdn_module_activated  = CDN_Helper::get_instance()->is_cdn_active();
		$webp_module_activated = ! $cdn_module_activated && $this->settings->is_webp_module_active();
		$avif_module_activated = ! $cdn_module_activated && $this->settings->is_avif_module_active();
		$webp_direct_activated = $webp_module_activated && $this->settings->is_webp_direct_conversion_active();
		$webp_server_activated = $webp_module_activated && ! $webp_direct_activated;

		$features = array(
			'lazy_load'        => $this->settings->is_lazyload_active(),
			'cdn'              => $cdn_module_activated,
			'avif'             => $avif_module_activated,
			'webp_direct'      => $webp_direct_activated,
			'webp_server'      => $webp_server_activated,
			'smush_basic'      => Settings::LEVEL_LOSSLESS === $lossy_level,
			'smush_super'      => Settings::LEVEL_SUPER_LOSSY === $lossy_level,
			'smush_ultra'      => Settings::LEVEL_ULTRA_LOSSY === $lossy_level,
			's3_offload'       => $this->settings->is_s3_active(),
			'wp_bakery'        => $this->settings->get( 'js_builder' ),
			'gravity_forms'    => $this->settings->get( 'gform' ),
			'nextgen_gallery'  => $this->settings->get( 'nextgen' ),
			'gutenberg_blocks' => $this->settings->get( 'gutenberg' ),
		);

		return array_keys( array_filter( $features ) );
	}

	private function get_wp_loopback_status( $properties ) {
		$is_loopback_error = ! empty( $properties['Trigger'] ) && 'loopback_error' === $properties['Trigger'];
		if ( $is_loopback_error ) {
			$loopback_status = Helper::loopback_supported() ? 'Pass' : 'Fail';
		} else {
			$loopback_status = 'na';
		}

		return $loopback_status;
	}

	public function track_bulk_smush_progress_stuck() {
		$properties = array(
			'Trigger'      => 'stuck_notice',
			'Modal Action' => 'na',
			'Troubleshoot' => 'na',
		);

		$properties = $this->filter_bulk_smush_interrupted_properties( $properties );

		$this->track( 'Bulk Smush Interrupted', $properties );
	}

	public function track_lazy_load_settings_updated( $old_settings, $settings ) {
		$changed_settings = $this->remove_unchanged_settings( (array) $old_settings, (array) $settings );

		$modified_settings = 'na';
		if ( ! empty( $changed_settings ) ) {
			$modified_settings_map = array(
				'format'            => 'media_type',
				'output'            => 'output_location',
				'animation'         => 'display_animation',
				'include'           => 'include_exclude_posttype',
				'exclude-pages'     => 'include_exclude_url',
				'exclude-classes'   => 'include_exclude_keyword',
				'footer'            => 'script_method',
				'native'            => 'native_lazyload',
				'noscript_fallback' => 'noscript',
			);

			$modified_settings = array_intersect_key( $modified_settings_map, $changed_settings );
			$modified_settings = ! empty( $modified_settings ) ? array_values( $modified_settings ) : 'na';
		}

		$this->track_lazy_load_updated(
			array(
				'update_type'       => 'modify',
				'modified_settings' => $modified_settings,
			),
			$settings
		);
	}

	private function track_lazy_load_updated( $properties, $settings ) {
		$exclusion_enabled         = $this->is_lazy_load_exclusion_enabled( $settings );
		$native_lazyload_enabled   = ! empty( $settings['native'] );
		$noscript_fallback_enabled = ! empty( $settings['noscript_fallback'] );
		$properties                = array_merge(
			array(
				'Location'           => $this->identify_referrer(),
				'exclusions'         => $exclusion_enabled ? 'Enabled' : 'Disabled',
				'native_lazy_status' => $native_lazyload_enabled ? 'Enabled' : 'Disabled',
				'noscript_status'    => $noscript_fallback_enabled ? 'Enabled' : 'Disabled',
			),
			$properties
		);

		$this->track( 'lazy_load_updated', $properties );
	}

	private function is_lazy_load_exclusion_enabled( $settings ) {
		if ( ! empty( $settings['exclude-pages'] ) || ! empty( $settings['exclude-classes'] ) ) {
			return true;
		}

		if ( empty( $settings['include'] ) || ! is_array( $settings['include'] ) ) {
			return false;
		}

		$included_post_types = $settings['include'];

		// By default, we activated for all post types, so this option is changed when any post type is unchecked.
		return in_array( false, $included_post_types, true );
	}
}
