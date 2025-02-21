<?php
/**
 * Class CLI
 *
 * @since 3.1
 * @package Smush\Core
 */

namespace Smush\Core\CLI;

use Smush\Core\Array_Utils;
use Smush\Core\Helper;
use Smush\Core\Media_Library\Background_Media_Library_Scanner;
use Smush\Core\Stats\Global_Stats;
use WP_CLI;
use WP_CLI_Command;
use WP_Smush;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Reduce image file sizes, improve performance and boost your SEO using the free WPMU DEV Smush API.
 */
class CLI extends WP_CLI_Command {
	/**
	 * @var Array_Utils
	 */
	private $array_utils;

	/**
	 * @var CLI_Optimizer
	 */
	private $cli_optimizer;

	public function __construct() {
		parent::__construct();
		$this->array_utils   = new Array_Utils();
		$this->cli_optimizer = new CLI_Optimizer( $this->array_utils );
	}

	/**
	 * Optimize image.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Optimize single image, batch or all images.
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - single
	 *   - multiple
	 *   - batch
	 * ---
	 *
	 * [--image=<ID>]
	 * : Attachment ID to compress.
	 * ---
	 * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * # Smush all images.
	 * $ wp smush compress
	 *
	 * # Smush single image with ID = 10.
	 * $ wp smush compress --type=single --image=10
	 *
	 * # Smush multiple image IDs.
	 * $ wp smush compress --type=multiple --image=10,15,16
	 *
	 * # Smush first 5 images.
	 * $ wp smush compress --type=batch --image=5
	 *
	 * @param array $args All the positional arguments.
	 * @param array $assoc_args All the arguments defined like --key=value or --flag or --no-flag.
	 */
	public function compress( $args, $assoc_args ) {
		$type  = $this->array_utils->get_array_value( $assoc_args, 'type' );
		$image = $this->array_utils->get_array_value( $assoc_args, 'image' );

		if ( 'single' !== $type && Global_Stats::get()->is_outdated() ) {
			WP_CLI::warning( 'Smush needs to scan the media library for changes before starting optimization. Running a scan now.', 'wp-smushit' );
			WP_CLI::runcommand( 'smush scan' );
		}

		switch ( $type ) {
			case 'single':
			case 'multiple':
				if ( empty( $image ) ) {
					WP_CLI::warning( __( 'Missing image id(s).', 'wp-smushit' ) );
					return;
				}
				$image_ids = explode( ',', $image );
				$count     = count( $image_ids );
				$this->cli_optimizer->set_limit( $count )
				                    ->set_ids( $image_ids )
				                    ->bulk_optimize(
					                    sprintf(
					                    /* translators: %s Smush image Id(s) */
						                    _n( 'Smushing image ID: %d', 'Smushing images %s', $count, 'wp-smushit' ),
						                    $image
					                    )
				                    );
				$count_limit = 25;
				$this->_list( array( $count_limit ) );
				break;
			case 'batch':
				$limit = absint( $image );
				$this->cli_optimizer->set_limit( $limit )
				                    ->set_ids( $this->get_all_optimize_ids() )
					/* translators: %d - number of images */
					                ->bulk_optimize( sprintf( __( 'Smushing first %d images', 'wp-smushit' ), absint( $image ) ) );
				break;
			case 'all':
			default:
				$this->cli_optimizer->set_ids( $this->get_all_optimize_ids() )
				                    ->bulk_optimize( __( 'Smushing all images', 'wp-smushit' ) );
				break;
		}
	}

	/**
	 * List unoptimized images.
	 *
	 * ## OPTIONS
	 *
	 * [<count>]
	 * : Limit number of images to get.
	 *
	 * ## EXAMPLES
	 *
	 * # Get all unoptimized images.
	 * $ wp smush list
	 *
	 * # Get the first 100 images that are not optimized.
	 * $ wp smush list 100
	 *
	 * @subcommand list
	 * @when after_wp_load
	 *
	 * @param array $args All the positional arguments.
	 */
	public function _list( $args = array() ) {
		if ( ! empty( $args ) ) {
			list( $count ) = $args;
		} else {
			$count = PHP_INT_MAX;
		}

		if ( Global_Stats::get()->is_outdated() ) {
			WP_CLI::warning( 'Smush needs to scan the media library for changes before starting optimization. Running a scan now.', 'wp-smushit' );
			WP_CLI::runcommand( 'smush scan' );
		}

		$this->cli_optimizer->set_limit( $count )
		                    ->set_ids( $this->get_all_optimize_ids() )
		                    ->render_optimize_list( __( 'Images that need to be smushed:', 'wp-smushit' ) );
	}

	private function get_all_optimize_ids() {
		$global_stats  = Global_Stats::get();
		$optimize_list = $global_stats->get_optimize_list();
		return $this->array_utils->fast_array_unique(
			array_merge( $optimize_list->get_ids(), $global_stats->get_redo_ids() )
		);
	}

	/**
	 * Restore image.
	 *
	 * ## OPTIONS
	 *
	 * [--id=<ID>]
	 * : Attachment ID to restore.
	 * ---
	 * default: all
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * # Restore all images that have backups.
	 * $ wp smush restore
	 *
	 * # Restore single image with ID = 10.
	 * $ wp smush restore --id=10
	 *
	 * @param array $args All the positional arguments.
	 * @param array $assoc_args All the arguments defined like --key=value or --flag or --no-flag.
	 */
	public function restore( $args, $assoc_args ) {
		$id = $this->array_utils->get_array_value( $assoc_args, 'id' );
		if ( 'all' === $id ) {
			$restore_ids = WP_Smush::get_instance()->core()->mod->backup->get_attachments_with_backups();
			$this->cli_optimizer->set_ids( $restore_ids )
			                    ->bulk_restore( __( 'Restoring all images', 'wp-smushit' ) );
			return;
		}

		$restore_ids          = explode( ',', $id );
		$total_restore_images = count( $restore_ids );
		$this->cli_optimizer->set_limit( $total_restore_images )
		                    ->set_ids( $restore_ids )
		                    ->bulk_restore(
			                    sprintf(
			                    /* translators: %s Restore image Id(s) */
				                    _n( 'Restoring %s image', 'Restoring %s images', $total_restore_images, 'wp-smushit' ),
				                    $id
			                    )
		                    );
	}

	/**
	 * Scan Media.
	 *
	 * ## EXAMPLES
	 *
	 * # Scan media library.
	 * $ wp smush scan
	 */
	public function scan() {
		if ( ! Helper::loopback_supported() ) {
			WP_CLI::warning(
				esc_html__( 'Your site seems to have an issue with loopback requests. Please try again and if the problem persists find out more here: https://wpmudev.com/docs/wpmu-dev-plugins/smush/#background-processing', 'wp-smushit' )
			);
			return;
		}

		$background_scan = Background_Media_Library_Scanner::get_instance();
		$status          = $background_scan->start_background_scan_direct();
		if ( is_wp_error( $status ) ) {
			WP_CLI::warning( $status->get_error_message() );
			return;
		}

		WP_CLI::log( __( 'Starting media library scan', 'wp-smushit' ) );

		$background_scan_status = $background_scan->get_background_process()->get_status();

		$progress = WP_CLI\Utils\make_progress_bar(
			__( 'Progress:', 'wp-smushit' ),
			$this->array_utils->get_array_value( $status, 'total_items' )
		);

		$processed_items = $this->array_utils->get_array_value( $status, 'processed_items' );
		$this->update_progress( $progress, $processed_items );
		do {
			$prev_processed_items = $processed_items;
			$processed_items      = $background_scan_status->get_processed_items();
			$this->update_progress( $progress, $processed_items - $prev_processed_items );

			sleep( 2 );
		} while ( $background_scan_status->is_in_processing() );

		$progress->finish();

		if ( $background_scan_status->is_dead() ) {
			WP_CLI::warning( esc_html__( 'Unfortunately the scan could not be completed due to an unknown error. Please restart the scan.', 'wp-smushit' ) );
			return;
		}

		if ( $background_scan_status->is_cancelled() ) {
			WP_CLI::warning( esc_html__( 'The background process is cancelled.', 'wp-smushit' ) );
			return;
		}

		WP_CLI::success( esc_html__( 'Media library scan complete.', 'wp-smushit' ) );

		// Reset notoptions cache to fetch the latest stats.
		wp_cache_delete( 'notoptions', 'options' );

		// Get new instance to avoid the cache.
		$global_stats = new Global_Stats();
		$total_stats  = $global_stats->get_sum_of_optimization_global_stats();

		$remaining_count = $global_stats->get_remaining_count();
		$redo_count      = $global_stats->get_redo_count();
		$optimize_count  = $global_stats->get_optimize_list()->get_count();

		WP_CLI::log( $this->get_pending_bulk_smush_content( $remaining_count, $redo_count, $optimize_count ) );

		$global_stats = array(
			esc_html__( 'Total Savings', 'wp-smushit' )        => $total_stats->get_human_bytes(),
			esc_html__( 'Savings Percent(%)', 'wp-smushit' )   => $total_stats->get_percent(),
			esc_html__( 'Images Smushed', 'wp-smushit' )       => $global_stats->get_optimized_images_count(),
			esc_html__( 'Optimized Percent(%)', 'wp-smushit' ) => $global_stats->get_percent_optimized(),
			esc_html__( 'Unsmushed Count', 'wp-smushit' )      => $optimize_count,
			esc_html__( 'Resmush Count', 'wp-smushit' )        => $redo_count,
		);

		WP_CLI\Utils\format_items( 'table', array( $global_stats ), array_keys( $global_stats ) );
	}

	private function update_progress( $progress, $new_processed_items ) {
		if ( $new_processed_items < 1 ) {
			return;
		}
		for ( $i = 0; $i < $new_processed_items; $i ++ ) {
			$progress->tick();
		}
	}

	private function get_pending_bulk_smush_content( $remaining_count, $reoptimize_count, $optimize_count ) {
		if ( $remaining_count < 1 ) {
			return esc_html__( 'Yay! All images are optimized as per your current settings.', 'wp-smushit' );
		}

		$optimize_message = '';
		if ( 0 < $optimize_count ) {
			$optimize_message = sprintf(
			/* translators: 1. opening strong tag, 2: unsmushed images count,3. closing strong tag. */
				esc_html( _n( 'Found %d attachment that needs smushing', 'Found %1$d attachments that need smushing', $optimize_count, 'wp-smushit' ) ),
				absint( $optimize_count )
			);
		}

		$reoptimize_message = '';
		if ( 0 < $reoptimize_count ) {
			$reoptimize_message = sprintf(
			/* translators: 1. opening strong tag, 2: re-smush images count,3. closing strong tag. */
				esc_html( _n( 'Found %d attachment that needs re-smushing', 'Found %d attachments that need re-smushing', $reoptimize_count, 'wp-smushit' ) ),
				esc_html( $reoptimize_count )
			);
		}

		$bulk_smush_suggestion = '';
		if ( $remaining_count ) {
			$bulk_smush_suggestion = __( 'Run "wp smush compress" to smush all images.', 'wp-smushit' );
		}

		return sprintf(
		/* translators: 1. unsmushed images message, 2. 'and' text for when having both unsmushed and re-smush images, 3. re-smush images message. */
			__( 'You have %1$s%2$s%3$s. %4$s', 'wp-smushit' ),
			$optimize_message,
			( $optimize_message && $reoptimize_message ? esc_html__( ', and ', 'wp-smushit' ) : '' ),
			$reoptimize_message,
			$bulk_smush_suggestion
		);
	}
}
