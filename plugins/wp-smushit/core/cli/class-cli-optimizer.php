<?php
/**
 * Class CLI
 *
 * @since 3.1
 * @package Smush\Core
 */

namespace Smush\Core\CLI;

use Smush\Core\Array_Utils;
use Smush\Core\Media\Media_Item_Cache;
use Smush\Core\Media\Media_Item_Optimizer;
use WP_CLI;
use WP_Smush;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class CLI_Optimizer {
	const IMAGE_ID_KEY = 'ID';
	const EDIT_LINK_KEY = 'Edit Link';
	const ERROR_MESSAGE_KEY = 'Error Message';
	const IMAGE_LINK_KEY = 'IMAGE LINK';
	const MIME_TYPE_KEY = 'MIME TYPE';
	/**
	 * @var int
	 */
	private $limit;

	/**
	 * @var array
	 */
	private $errors;

	/**
	 * @var array
	 */
	private $ids;

	/**
	 * @var Array_Utils
	 */
	private $array_utils;

	public function __construct( $array_utils ) {
		$this->array_utils = $array_utils;
	}

	public function bulk_restore( $start_message ) {
		WP_CLI::log( $start_message );
		$total_images = $this->get_count();
		if ( $total_images < 1 ) {
			WP_CLI::success( __( 'No images available to restore', 'wp-smushit' ) );
			return;
		}

		$progress     = WP_CLI\Utils\make_progress_bar( __( 'Progress:', 'wp-smushit' ), $total_images );
		$optimize_ids = $this->get_ids();
		WP_CLI::log( sprintf( __( 'Found %d attachments that need to be restored!', 'wp-smushit' ), $total_images ) );
		$this->log_start_restore();
		foreach ( $optimize_ids as $attachment_id ) {
			$this->restore( (int) $attachment_id );
			$progress->tick();
		}

		$progress->finish();

		$this->render_restore_status();
		$this->reset();
	}


	private function log_start_restore() {
		if ( 1 === $this->get_limit() ) {
			return WP_CLI::log( __( 'Starting restoration ...', 'wp-smushit' ) );
		}
		return WP_CLI::log( __( 'Starting bulk restoration ...', 'wp-smushit' ) );
	}

	private function restore( $attachment_id ) {
		$media_item = Media_Item_Cache::get_instance()->get( $attachment_id );
		$optimizer  = new Media_Item_Optimizer( $media_item );
		$restored   = $optimizer->restore();

		if ( ! $restored ) {
			$error_message = sprintf( /* translators: %d - attachment ID */
				esc_html__( 'Image %d cannot be restored.', 'wp-smushit' ),
				(int) $attachment_id
			);
			$this->add_error( $this->get_error_item( $attachment_id, $error_message ) );
		}

		return $restored;
	}

	private function render_restore_status() {
		$total_images = $this->get_count();
		$errors       = $this->get_errors();
		if ( 1 === $this->get_limit() ) {
			$this->render_single_restore_status( $errors );
			return;
		}

		$this->render_bulk_restore_status( $total_images, $errors );
	}

	private function render_single_restore_status( $errors ) {
		if ( empty( $errors ) ) {
			WP_CLI::success( __( 'Image restored successfully!', 'wp-smushit' ) );
			return;
		}

		WP_CLI::warning(
			sprintf(
			/* translators: %s: Error message */
				__( 'Image could not be restored: %s', 'wp-smushit' ),
				$this->array_utils->get_array_value( $errors[0], self::ERROR_MESSAGE_KEY )
			)
		);
	}

	private function render_bulk_restore_status( $total_images, $errors ) {
		$no_errors            = count( $errors );
		$bulk_restore_message = $this->get_bulk_restore_message( $no_errors, $total_images );

		if ( empty( $no_errors ) ) {
			WP_CLI::success( $bulk_restore_message );
			return;
		}

		WP_CLI::warning( $bulk_restore_message );
		WP_CLI\Utils\format_items( 'table', $errors, array( self::IMAGE_ID_KEY, self::EDIT_LINK_KEY, self::ERROR_MESSAGE_KEY ) );
	}

	private function get_bulk_restore_message( $no_errors, $total_images ) {
		if ( $no_errors === $total_images ) {
			return esc_html__( 'All of your images failed to restore. Find out why and how to resolve the issue(s) below.', 'wp-smushit' );
		} elseif ( $no_errors > 0 ) {
			$no_restored          = $total_images - $no_errors;
			$bulk_restore_message = esc_html__( '{{smushed}}/{{total}} images restored successfully, {{errors}} images were not restored, find out why and how to resolve the issue(s) below.', 'wp-smushit' );
			$bulk_restore_message = str_replace( array( '{{smushed}}', '{{total}}', '{{errors}}' ), array( $no_restored, $total_images, $no_errors ), $bulk_restore_message );
			return $bulk_restore_message;
		}

		return WP_CLI::success( __( 'All images restored.', 'wp-smushit' ) );
	}



	public function bulk_optimize( $start_message ) {
		WP_CLI::log( $start_message );
		$total_images = $this->get_count();
		if ( $total_images < 1 ) {
			WP_CLI::success( __( 'No images available to smush.', 'wp-smushit' ) );
			return;
		}

		$progress     = WP_CLI\Utils\make_progress_bar( __( 'Progress:', 'wp-smushit' ), $total_images );
		$optimize_ids = $this->get_ids();

		WP_CLI::log( sprintf( __( 'Found %d attachments that need smushing!', 'wp-smushit' ), $total_images ) );
		$this->log_start_smush();
		foreach ( $optimize_ids as $attachment_id ) {
			$this->optimize( (int) $attachment_id );

			$progress->tick();
		}

		$progress->finish();

		$this->render_smush_status();
		$this->reset();
	}

	private function log_start_smush() {
		if ( 1 === $this->get_limit() ) {
			return WP_CLI::log( __( 'Starting smush ...', 'wp-smushit' ) );
		}
		return WP_CLI::log( __( 'Starting smush ...', 'wp-smushit' ) );
	}

	private function optimize( $attachment_id ) {
		$media_item = Media_Item_Cache::get_instance()->get( $attachment_id );
		$optimizer  = new Media_Item_Optimizer( $media_item );
		$optimized  = $optimizer->optimize();

		if ( $optimized ) {
			return true;
		}

		if ( $media_item->has_errors() ) {
			$this->add_error( $this->get_error_item( $attachment_id, $media_item->get_errors()->get_error_message() ) );
		} else {
			$this->add_error( $this->get_error_item( $attachment_id, $optimizer->get_errors()->get_error_message() ) );
		}

		return false;
	}

	private function get_error_item( $attachment_id, $error_message ) {
		$media_item = Media_Item_Cache::get_instance()->get( $attachment_id );
		return array(
			self::IMAGE_ID_KEY      => $attachment_id,
			self::EDIT_LINK_KEY     => $media_item->get_edit_link(),
			self::ERROR_MESSAGE_KEY => $error_message,
		);
	}

	private function render_smush_status() {
		$total_images = $this->get_count();
		$errors       = $this->get_errors();
		if ( 1 === $this->get_limit() ) {
			$this->render_single_smush_status( $errors );
			return;
		}

		$this->render_bulk_smush_status( $total_images, $errors );
	}

	private function render_single_smush_status( $errors ) {
		if ( empty( $errors ) ) {
			WP_CLI::success( __( 'Image smushed.', 'wp-smushit' ) );
			return;
		}

		WP_CLI::warning(
			sprintf(
			/* translators: %s: Error message */
				__( 'Image could not be smushed: %s', 'wp-smushit' ),
				$this->array_utils->get_array_value( $errors[0], self::ERROR_MESSAGE_KEY )
			)
		);
	}

	private function render_bulk_smush_status( $total_images, $errors ) {
		$no_errors          = count( $errors );
		$bulk_smush_message = $this->get_bulk_smush_message( $no_errors, $total_images );

		if ( empty( $no_errors ) ) {
			WP_CLI::success( $bulk_smush_message );
			return;
		}

		WP_CLI::warning( $bulk_smush_message );
		WP_CLI\Utils\format_items( 'table', $errors, array( self::IMAGE_ID_KEY, self::EDIT_LINK_KEY, self::ERROR_MESSAGE_KEY ) );
	}

	private function get_bulk_smush_message( $no_errors, $total_images ) {
		$localize_strings = WP_Smush::get_instance()->core()->get_localize_strings();
		if ( $no_errors === $total_images ) {
			return $this->array_utils->get_array_value( $localize_strings, 'all_failed' );
		} elseif ( $no_errors > 0 ) {
			$no_smushed         = $total_images - $no_errors;
			$bulk_smush_message = $this->array_utils->get_array_value( $localize_strings, 'error_in_bulk' );
			$bulk_smush_message = str_replace( array( '{{smushed}}', '{{total}}', '{{errors}}' ), array( $no_smushed, $total_images, $no_errors ), $bulk_smush_message );
			return $bulk_smush_message;
		}

		return $this->array_utils->get_array_value( $localize_strings, 'all_smushed' );
	}

	public function render_optimize_list( $title ) {
		$optimize_list = $this->get_optimize_list();
		if ( empty( $optimize_list ) ) {
			WP_CLI::success( __( 'We did not find any images that need smushing.', 'wp-smushit' ) );
			return;
		}
		WP_CLI::log( $title );
		WP_CLI\Utils\format_items( 'table', $this->get_optimize_list(), array( self::IMAGE_ID_KEY, self::IMAGE_LINK_KEY, self::MIME_TYPE_KEY ) );
		$this->reset();
	}

	private function get_optimize_list() {
		$optimize_list = array();
		$optimize_ids  = $this->get_ids();
		foreach ( $optimize_ids as $attachment_id ) {
			$media_item = Media_Item_Cache::get_instance()->get( $attachment_id );
			if ( ! $media_item->is_valid() || ! $media_item->get_main_size() ) {
				continue;
			}
			$optimize_list[] = $this->get_optimize_item( $media_item );
		}

		return $optimize_list;
	}

	private function get_ids() {
		return $this->ids;
	}

	public function set_ids( $ids ) {
		$limit = $this->get_limit();
		$ids   = (array) $ids;
		if ( $limit && $limit < count( $ids ) ) {
			$ids = array_slice( $ids, 0, $limit );
		}

		$this->ids = $ids;

		return $this;
	}

	public function get_optimize_item( $media_item ) {
		return array(
			self::IMAGE_ID_KEY   => $media_item->get_id(),
			self::IMAGE_LINK_KEY => $media_item->get_main_size()->get_file_url(),
			self::MIME_TYPE_KEY  => $media_item->get_mime_type(),
		);
	}

	private function get_count() {
		return count( $this->get_ids() );
	}

	private function get_limit() {
		return $this->limit;
	}

	public function set_limit( $limit ) {
		$this->limit = $limit > 0 ? $limit : 0;
		return $this;
	}

	private function add_error( $error_item ) {
		$this->errors[] = $error_item;
	}

	public function get_errors() {
		return (array) $this->errors;
	}

	private function set_errors( array $errors ) {
		$this->errors = $errors;
		return $this;
	}

	public function reset() {
		$this->set_limit( 0 );
		$this->set_errors( array() );
		$this->set_ids( array() );
	}
}
