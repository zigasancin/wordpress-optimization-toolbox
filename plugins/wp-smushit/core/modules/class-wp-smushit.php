<?php
/**
 * Smush core class: WP_Smushit class
 *
 * @package WP_Smushit
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smushit.
 */
class WP_Smushit extends WP_Smush_Module {

	/**
	 * Meta key to save smush result to db.
	 *
	 * @var string $smushed_meta_key
	 */
	public static $smushed_meta_key = 'wp-smpro-smush-data';

	/**
	 * Images dimensions array.
	 *
	 * @var array $image_sizes
	 */
	public $image_sizes = array();

	/**
	 * Attachment type, being Smushed currently.
	 *
	 * @var string $media_type  Default: 'wp'. Accepts: 'wp', 'nextgen'.
	 */
	public $media_type = 'wp';

	/**
	 * Attachment ID for the image being Smushed currently.
	 *
	 * @var int $attachment_id
	 */
	public $attachment_id;

	/**
	 * Stores the headers returned by the latest API call.
	 *
	 * @var array $api_headers
	 */
	protected $api_headers = array();

	/**
	 * WP_Smush constructor.
	 */
	public function init() {
		// Update the Super Smush count, after the Smush'ing.
		add_action( 'wp_smush_image_optimised', array( $this, 'update_lists' ), '', 2 );

		// Smush image (Auto Smush) when `wp_update_attachment_metadata` filter is fired.
		add_filter( 'wp_update_attachment_metadata', array( $this, 'smush_image' ), 15, 2 );
		// Delete backup files.
		add_action( 'delete_attachment', array( $this, 'delete_images' ), 12 );

		// Handle the Async optimisation.
		add_action( 'wp_async_wp_generate_attachment_metadata', array( $this, 'wp_smush_handle_async' ) );
		add_action( 'wp_async_wp_save_image_editor_file', array( $this, 'wp_smush_handle_editor_async' ), '', 2 );

		// Register function for sending unsmushed image count to hub.
		add_filter( 'wdp_register_hub_action', array( $this, 'smush_stats' ) );

		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'smush_send_status' ), 99, 3 );
	}

	/**
	 * Set send button status
	 *
	 * @param int  $id        Attachment ID.
	 * @param bool $echo      Echo or return.
	 * @param bool $text_only Returns the stats text instead of button.
	 * @param bool $wrapper   Required for `column_html`, to include the wrapper div or not.
	 *
	 * @return string|array
	 */
	public function set_status( $id, $echo = true, $text_only = false, $wrapper = true ) {
		$status_txt  = $button_txt = $stats = $links = '';
		$show_button = $show_resmush = false;

		$links = '';

		$wp_smush_data      = get_post_meta( $id, self::$smushed_meta_key, true );
		$wp_resize_savings  = get_post_meta( $id, WP_SMUSH_PREFIX . 'resize_savings', true );
		$conversion_savings = get_post_meta( $id, WP_SMUSH_PREFIX . 'pngjpg_savings', true );

		$combined_stats = $this->combined_stats( $wp_smush_data, $wp_resize_savings );

		$combined_stats = $this->combine_conversion_stats( $combined_stats, $conversion_savings );

		// Remove Smush s3 hook, as it downloads the file again.
		if ( class_exists( 'WP_Smush_S3_Compat' ) && class_exists( 'AS3CF_Plugin_Compatibility' ) ) {
			$s3_compat = new WP_Smush_S3_Compat();
			remove_filter( 'as3cf_get_attached_file', array( $s3_compat, 'smush_download_file' ), 11, 4 );
		}
		$attachment_data = wp_get_attachment_metadata( $id );

		// If the image is smushed.
		if ( ! empty( $wp_smush_data ) ) {
			$image_count    = count( $wp_smush_data['sizes'] );
			$bytes          = isset( $combined_stats['stats']['bytes'] ) ? $combined_stats['stats']['bytes'] : 0;
			$bytes_readable = ! empty( $bytes ) ? size_format( $bytes, 1 ) : '';
			$percent        = isset( $combined_stats['stats']['percent'] ) ? $combined_stats['stats']['percent'] : 0;
			$percent        = $percent < 0 ? 0 : $percent;

			// Show resmush link, if the settings were changed.
			$show_resmush = $this->show_resmush( $id, $wp_smush_data );

			if ( empty( $wp_resize_savings['bytes'] ) && isset( $wp_smush_data['stats']['size_before'] ) && $wp_smush_data['stats']['size_before'] == 0 && ! empty( $wp_smush_data['sizes'] ) ) {
				$status_txt = __( 'Already Optimized', 'wp-smushit' );
				if ( $show_resmush ) {
					$links .= $this->get_resmsuh_link( $id );
				}
				$show_button = false;
			} else {
				if ( $bytes == 0 || $percent == 0 ) {
					$status_txt = __( 'Already Optimized', 'wp-smushit' );

					if ( $show_resmush ) {
						$links .= $this->get_resmsuh_link( $id );
					}
				} elseif ( ! empty( $percent ) && ! empty( $bytes_readable ) ) {
					/* translators: %d: number of images reduced */
					$status_txt = $image_count > 1 ? sprintf( __( '%d images reduced ', 'wp-smushit' ), $image_count ) : __( 'Reduced ', 'wp-smushit' );

					$stats_percent = number_format_i18n( $percent, 2, '.', '' );
					$stats_percent = $stats_percent > 0 ? sprintf( '(  %01.1f%% )', $stats_percent ) : '';
					/* translators: %1$s: bytes in readable format, %2$s: percent */
					$status_txt .= sprintf( __( 'by %1$s %2$s', 'wp-smushit' ), $bytes_readable, $stats_percent );

					$file_path = get_attached_file( $id );
					$size      = file_exists( $file_path ) ? filesize( $file_path ) : 0;
					if ( $size > 0 ) {
						$update_size = size_format( $size, 0 ); // Used in js to update image stat.
						$size        = size_format( $size, 1 );
						/* translators: %s: image size */
						$image_size  = sprintf( __( '<br /> Image Size: %s', 'wp-smushit' ), $size );
						$status_txt .= $image_size;
					}

					$show_resmush = $this->show_resmush( $id, $wp_smush_data );

					if ( $show_resmush ) {
						$links .= $this->get_resmsuh_link( $id );
					}

					// Restore Image: Check if we need to show the restore image option.
					$show_restore = $this->show_restore_option( $id, $attachment_data );

					if ( $show_restore ) {
						$links .= $this->get_restore_link( $id );
					}

					// Detailed Stats: Show detailed stats if available.
					if ( ! empty( $wp_smush_data['sizes'] ) ) {

						// Detailed Stats Link.
						$links .= sprintf(
							'<a href="#" class="wp-smush-action smush-stats-details wp-smush-title sui-tooltip sui-tooltip-mobile sui-tooltip-top-left-mobile button" data-tooltip="%s">%s</a>',
							esc_html__( 'Detailed stats for all the image sizes', 'wp-smushit' ),
							esc_html__( 'View Stats', 'wp-smushit' )
						);

						// Stats.
						$stats = $this->get_detailed_stats( $id, $wp_smush_data, $attachment_data );

						if ( ! $text_only ) {
							$links .= $stats;
						}
					}
				}
			}
			// Wrap links if not empty.
			$links = ! empty( $links ) ? "<div class='sui-smush-media smush-status-links'>" . $links . '</div>' : '';

			/** Super Smush Button  */
			// IF current compression is lossy.
			if ( ! empty( $wp_smush_data ) && ! empty( $wp_smush_data['stats'] ) ) {
				$lossy    = ! empty( $wp_smush_data['stats']['lossy'] ) ? $wp_smush_data['stats']['lossy'] : '';
				$is_lossy = $lossy == 1 ? true : false;
			}

			// Check image type.
			$image_type = get_post_mime_type( $id );

			// Check if premium user, compression was lossless, and lossy compression is enabled.
			// If we are displaying the resmush option already, no need to show the Super Smush button.
			if ( ! $show_resmush && ! $is_lossy && WP_Smush::is_pro() && $this->settings->get( 'lossy' ) && 'image/gif' !== $image_type ) {
				$button_txt  = __( 'Super-Smush', 'wp-smushit' );
				$show_button = true;
			}
		} elseif ( get_option( 'smush-in-progress-' . $id, false ) ) {
			// The status.
			$status_txt = __( 'Smushing in progress..', 'wp-smushit' );

			// Set WP Smush data to true in order to show the text.
			$wp_smush_data = true;

			// We need to show the smush button.
			$show_button = false;

			// The button text.
			$button_txt = '';
		} else {

			// Show status text.
			$wp_smush_data = true;

			// The status.
			$ignored    = get_post_meta( $id, WP_SMUSH_PREFIX . 'ignore-bulk', true );
			$status_txt = 'true' === $ignored ? __( 'Ignored in Bulk Smush', 'wp-smushit' ) : __( 'Not processed', 'wp-smushit' );

			// We need to show the smush button.
			$show_button = true;

			// The button text.
			$button_txt = __( 'Smush', 'wp-smushit' );
		}

		$class      = $wp_smush_data ? '' : ' hidden';
		$status_txt = '<p class="smush-status' . $class . '">' . $status_txt . '</p>';

		$status_txt .= $links;

		if ( $text_only ) {
			// For ajax response.
			return array(
				'status'       => $status_txt,
				'stats'        => $stats,
				'show_warning' => intval( $this->show_warning() ),
				'new_size'     => isset( $update_size ) ? $update_size : 0,
			);
		}

		// If we are not showing smush button, append progress bar, else it is already there.
		if ( ! $show_button ) {
			$status_txt .= $this->progress_bar();
		}

		$text = $this->column_html( $id, $status_txt, $button_txt, $show_button, $wp_smush_data, $echo, $wrapper );
		if ( ! $echo ) {
			return $text;
		}
	}

	/**
	 * Smush and Resizing Stats Combined together.
	 *
	 * @param array $smush_stats     Smush stats.
	 * @param array $resize_savings  Resize savings.
	 *
	 * @return array Array of all the stats
	 */
	public function combined_stats( $smush_stats, $resize_savings ) {
		if ( empty( $smush_stats ) || empty( $resize_savings ) ) {
			return $smush_stats;
		}

		// Initialize key full if not there already.
		if ( ! isset( $smush_stats['sizes']['full'] ) ) {
			$smush_stats['sizes']['full']              = new stdClass();
			$smush_stats['sizes']['full']->bytes       = 0;
			$smush_stats['sizes']['full']->size_before = 0;
			$smush_stats['sizes']['full']->size_after  = 0;
			$smush_stats['sizes']['full']->percent     = 0;
		}

		// Full Image.
		if ( ! empty( $smush_stats['sizes']['full'] ) ) {
			$smush_stats['sizes']['full']->bytes       = ! empty( $resize_savings['bytes'] ) ? $smush_stats['sizes']['full']->bytes + $resize_savings['bytes'] : $smush_stats['sizes']['full']->bytes;
			$smush_stats['sizes']['full']->size_before = ! empty( $resize_savings['size_before'] ) && ( $resize_savings['size_before'] > $smush_stats['sizes']['full']->size_before ) ? $resize_savings['size_before'] : $smush_stats['sizes']['full']->size_before;
			$smush_stats['sizes']['full']->percent     = ! empty( $smush_stats['sizes']['full']->bytes ) && $smush_stats['sizes']['full']->size_before > 0 ? ( $smush_stats['sizes']['full']->bytes / $smush_stats['sizes']['full']->size_before ) * 100 : $smush_stats['sizes']['full']->percent;

			$smush_stats['sizes']['full']->size_after = $smush_stats['sizes']['full']->size_before - $smush_stats['sizes']['full']->bytes;

			$smush_stats['sizes']['full']->percent = round( $smush_stats['sizes']['full']->percent, 1 );
		}

		$smush_stats = $this->total_compression( $smush_stats );

		return $smush_stats;
	}

	/**
	 * Iterate over all the size stats and calculate the total stats
	 *
	 * @param array $stats  Stats array.
	 *
	 * @return mixed
	 */
	public function total_compression( $stats ) {
		$stats['stats']['size_before'] = 0;
		$stats['stats']['size_after']  = 0;
		$stats['stats']['time']        = 0;

		foreach ( $stats['sizes'] as $size_stats ) {
			$stats['stats']['size_before'] += ! empty( $size_stats->size_before ) ? $size_stats->size_before : 0;
			$stats['stats']['size_after']  += ! empty( $size_stats->size_after ) ? $size_stats->size_after : 0;
			$stats['stats']['time']        += ! empty( $size_stats->time ) ? $size_stats->time : 0;
		}

		$stats['stats']['bytes'] = ! empty( $stats['stats']['size_before'] ) && $stats['stats']['size_before'] > $stats['stats']['size_after'] ? $stats['stats']['size_before'] - $stats['stats']['size_after'] : 0;

		if ( ! empty( $stats['stats']['bytes'] ) && ! empty( $stats['stats']['size_before'] ) ) {
			$stats['stats']['percent'] = ( $stats['stats']['bytes'] / $stats['stats']['size_before'] ) * 100;
		}

		return $stats;
	}

	/**
	 * Combine Savings from PNG to JPG conversion with smush stats
	 *
	 * @param array $stats               Savings from Smushing the image.
	 * @param array $conversion_savings  Savings from converting the PNG to JPG.
	 *
	 * @return Object|array Total Savings
	 */
	public function combine_conversion_stats( $stats, $conversion_savings ) {
		if ( empty( $stats ) || empty( $conversion_savings ) ) {
			return $stats;
		}

		foreach ( $conversion_savings as $size_k => $savings ) {
			// Initialize Object for size.
			if ( empty( $stats['sizes'][ $size_k ] ) ) {
				$stats['sizes'][ $size_k ]              = new stdClass();
				$stats['sizes'][ $size_k ]->bytes       = 0;
				$stats['sizes'][ $size_k ]->size_before = 0;
				$stats['sizes'][ $size_k ]->size_after  = 0;
				$stats['sizes'][ $size_k ]->percent     = 0;
			}

			if ( ! empty( $stats['sizes'][ $size_k ] ) && ! empty( $savings ) ) {
				$stats['sizes'][ $size_k ]->bytes       = $stats['sizes'][ $size_k ]->bytes + $savings['bytes'];
				$stats['sizes'][ $size_k ]->size_before = $stats['sizes'][ $size_k ]->size_before > $savings['size_before'] ? $stats['sizes'][ $size_k ]->size_before : $savings['size_before'];
				$stats['sizes'][ $size_k ]->percent     = ! empty( $stats['sizes'][ $size_k ]->bytes ) && $stats['sizes'][ $size_k ]->size_before > 0 ? ( $stats['sizes'][ $size_k ]->bytes / $stats['sizes'][ $size_k ]->size_before ) * 100 : $stats['sizes'][ $size_k ]->percent;
				$stats['sizes'][ $size_k ]->percent     = round( $stats['sizes'][ $size_k ]->percent, 1 );
			}
		}

		$stats = $this->total_compression( $stats );

		return $stats;
	}

	/**
	 * Checks the current settings and returns the value whether to enable or not the resmush option.
	 *
	 * @param string $id             Attachment ID.
	 * @param array  $wp_smush_data  Smush data.
	 *
	 * @return bool
	 */
	private function show_resmush( $id = '', $wp_smush_data ) {
		// Resmush: Show resmush link, Check if user have enabled smushing the original and full image was skipped
		// Or: If keep exif is unchecked and the smushed image have exif
		// PNG To JPEG.
		if ( $this->settings->get( 'original' ) && WP_Smush::is_pro() ) {
			// IF full image was not smushed.
			if ( ! empty( $wp_smush_data ) && empty( $wp_smush_data['sizes']['full'] ) ) {
				return true;
			}
		}

		// If image needs to be resized.
		if ( WP_Smush::get_instance()->core()->mod->resize->should_resize( $id ) ) {
			return true;
		}

		// EXIF Check.
		if ( $this->settings->get( 'strip_exif' ) ) {
			// If Keep Exif was set to true initially, and since it is set to false now.
			if ( isset( $wp_smush_data['stats']['keep_exif'] ) && true === $wp_smush_data['stats']['keep_exif'] ) {
				return true;
			}
		}

		// PNG to JPEG.
		if ( WP_Smush::get_instance()->core()->mod->png2jpg->can_be_converted( $id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Generates a Resmush link for a image.
	 *
	 * @param int    $image_id  Attachment ID.
	 * @param string $type      Type of attachment.
	 *
	 * @return bool|string
	 */
	public function get_resmsuh_link( $image_id, $type = 'wp' ) {
		if ( empty( $image_id ) ) {
			return false;
		}
		$class  = 'wp-smush-action wp-smush-title sui-tooltip sui-tooltip-constrained';
		$class .= 'wp' === $type ? ' wp-smush-resmush button' : ' wp-smush-nextgen-resmush';

		$ajax_nonce = wp_create_nonce( 'wp-smush-resmush-' . $image_id );

		return sprintf( '<a href="#" data-tooltip="%s" data-id="%d" data-nonce="%s" class="%s">%s</a>', esc_html__( 'Smush image including original file.', 'wp-smushit' ), $image_id, $ajax_nonce, $class, esc_html__( 'Resmush', 'wp-smushit' ) );
	}

	/**
	 * If any of the image size have a backup file, show the restore option
	 *
	 * @param int          $image_id         Attachment ID.
	 * @param string|array $attachment_data  Attachment data.
	 *
	 * @return bool
	 */
	private function show_restore_option( $image_id, $attachment_data ) {
		// No Attachment data, don't go ahead.
		if ( empty( $attachment_data ) ) {
			return false;
		}

		// Get the image path for all sizes.
		$file = get_attached_file( $image_id );

		// Get stored backup path, if any.
		$backup_sizes = get_post_meta( $image_id, '_wp_attachment_backup_sizes', true );

		// Check if we've a backup path.
		if ( ! empty( $backup_sizes ) && ( ! empty( $backup_sizes['smush-full'] ) || ! empty( $backup_sizes['smush_png_path'] ) ) ) {
			// Check for PNG backup.
			$backup = ! empty( $backup_sizes['smush_png_path'] ) ? $backup_sizes['smush_png_path'] : '';

			// Check for original full size image backup.
			$backup = empty( $backup ) && ! empty( $backup_sizes['smush-full'] ) ? $backup_sizes['smush-full'] : $backup;
			$backup = ! empty( $backup['file'] ) ? $backup['file'] : '';
		}

		// If we still don't have a backup path, use traditional method to get it.
		if ( empty( $backup ) ) {
			// Check backup for Full size.
			$backup = $this->get_image_backup_path( $file );
		} else {
			// Get the full path for file backup.
			$backup = str_replace( wp_basename( $file ), wp_basename( $backup ), $file );
		}

		$file_exists = apply_filters( 'smush_backup_exists', file_exists( $backup ), $image_id, $backup );

		if ( $file_exists ) {
			return true;
		}

		// Additional Backup Check for JPEGs converted from PNG.
		$pngjpg_savings = get_post_meta( $image_id, WP_SMUSH_PREFIX . 'pngjpg_savings', true );
		if ( ! empty( $pngjpg_savings ) ) {

			// Get the original File path and check if it exists.
			$backup = get_post_meta( $image_id, WP_SMUSH_PREFIX . 'original_file', true );
			$backup = $this->original_file( $backup );

			if ( ! empty( $backup ) && is_file( $backup ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the backup path for attachment
	 *
	 * @param string $attachment_path  Attachment path.
	 *
	 * @return bool|string
	 */
	public function get_image_backup_path( $attachment_path ) {
		// If attachment id is not available, return false.
		if ( empty( $attachment_path ) ) {
			return false;
		}
		$path = pathinfo( $attachment_path );

		// If we don't have complete filename return false.
		if ( empty( $path['extension'] ) ) {
			return false;
		}

		$backup_name = trailingslashit( $path['dirname'] ) . $path['filename'] . '.bak.' . $path['extension'];

		return $backup_name;
	}

	/**
	 * Original File path
	 *
	 * @param string $original_file  Original file.
	 *
	 * @return string File Path
	 */
	public function original_file( $original_file = '' ) {
		$uploads     = wp_get_upload_dir();
		$upload_path = $uploads['basedir'];

		return path_join( $upload_path, $original_file );
	}

	/**
	 * Returns a restore link for given image id
	 *
	 * @param int    $image_id  Attachment ID.
	 * @param string $type      Attachment type.
	 *
	 * @return bool|string
	 */
	public function get_restore_link( $image_id, $type = 'wp' ) {
		if ( empty( $image_id ) ) {
			return false;
		}

		$class  = 'wp-smush-action wp-smush-title sui-tooltip';
		$class .= 'wp' === $type ? ' wp-smush-restore button' : ' wp-smush-nextgen-restore';

		$ajax_nonce = wp_create_nonce( 'wp-smush-restore-' . $image_id );

		return sprintf( '<a href="#" data-tooltip="%s" data-id="%d" data-nonce="%s" class="%s">%s</a>', esc_html__( 'Restore original image.', 'wp-smushit' ), $image_id, $ajax_nonce, $class, esc_html__( 'Restore', 'wp-smushit' ) );
	}

	/**
	 * Shows the image size and the compression for each of them
	 *
	 * @param int   $image_id             Attachment ID.
	 * @param array $wp_smush_data        Smush data array.
	 * @param array $attachment_metadata  Attachment meta data.
	 *
	 * @return string
	 */
	private function get_detailed_stats( $image_id, $wp_smush_data, $attachment_metadata ) {
		$stats      = '<div id="smush-stats-' . $image_id . '" class="sui-smush-media smush-stats-wrapper hidden">
			<table class="wp-smush-stats-holder">
				<thead>
					<tr>
						<th class="smush-stats-header">' . esc_html__( 'Image size', 'wp-smushit' ) . '</th>
						<th class="smush-stats-header">' . esc_html__( 'Savings', 'wp-smushit' ) . '</th>
					</tr>
				</thead>
				<tbody>';
		$size_stats = $wp_smush_data['sizes'];

		// Reorder Sizes as per the maximum savings.
		uasort( $size_stats, array( $this, 'cmp' ) );

		if ( ! empty( $attachment_metadata['sizes'] ) ) {
			// Get skipped images.
			$skipped = $this->get_skipped_images( $image_id, $size_stats, $attachment_metadata );

			if ( ! empty( $skipped ) ) {
				foreach ( $skipped as $img_data ) {
					$skip_class = 'size_limit' === $img_data['reason'] ? ' error' : '';
					$stats     .= '<tr>
				<td>' . strtoupper( $img_data['size'] ) . '</td>
				<td class="smush-skipped' . $skip_class . '">' . $this->skip_reason( $img_data['reason'] ) . '</td>
			</tr>';
				}
			}
		}
		// Show Sizes and their compression.
		foreach ( $size_stats as $size_key => $size_value ) {
			$dimensions = '';
			// Get the dimensions for the image size if available.
			if ( ! empty( $this->image_sizes ) && ! empty( $this->image_sizes[ $size_key ] ) ) {
				$dimensions = $this->image_sizes[ $size_key ]['width'] . 'x' . $this->image_sizes[ $size_key ]['height'];
			}
			$dimensions = ! empty( $dimensions ) ? sprintf( ' <br /> (%s)', $dimensions ) : '';
			if ( $size_value->bytes > 0 ) {
				$percent = round( $size_value->percent, 1 );
				$percent = $percent > 0 ? ' ( ' . $percent . '% )' : '';
				$stats  .= '<tr>
				<td>' . strtoupper( $size_key ) . $dimensions . '</td>
				<td>' . size_format( $size_value->bytes, 1 ) . $percent . '</td>
			</tr>';
			}
		}
		$stats .= '</tbody>
			</table>
		</div>';

		return $stats;
	}

	/**
	 * Return a list of images not smushed and reason
	 *
	 * @param int   $image_id             Attachment ID.
	 * @param array $size_stats           Stats array.
	 * @param array $attachment_metadata  Attachment meta data.
	 *
	 * @return array
	 */
	private function get_skipped_images( $image_id, $size_stats, $attachment_metadata ) {
		$skipped = array();

		// Get a list of all the sizes, Show skipped images.
		$media_size = get_intermediate_image_sizes();

		// Full size.
		$full_image = get_attached_file( $image_id );

		// If full image was not smushed, reason 1. Large Size logic, 2. Free and greater than 1Mb.
		if ( ! array_key_exists( 'full', $size_stats ) ) {
			// For free version, Check the image size.
			if ( ! WP_Smush::is_pro() ) {
				// For free version, check if full size is greater than 1 Mb, show the skipped status.
				$file_size = file_exists( $full_image ) ? filesize( $full_image ) : '';
				if ( ! empty( $file_size ) && ( $file_size / WP_SMUSH_MAX_BYTES ) > 1 ) {
					$skipped[] = array(
						'size'   => 'full',
						'reason' => 'size_limit',
					);
				}
			}

			// In other case, if full size is skipped.
			if ( ! isset( $skipped['full'] ) ) {
				// Paid version, Check if we have large size.
				$skipped[] = array(
					'size'   => 'full',
					'reason' => 'large_size',
				);
			}
		}
		// For other sizes, check if the image was generated and not available in stats.
		if ( is_array( $media_size ) ) {
			foreach ( $media_size as $size ) {
				if ( array_key_exists( $size, $attachment_metadata['sizes'] ) && ! array_key_exists( $size, $size_stats ) && ! empty( $size['file'] ) ) {
					// Image Path.
					$img_path   = path_join( dirname( $full_image ), $size['file'] );
					$image_size = file_exists( $img_path ) ? filesize( $img_path ) : '';
					if ( ! empty( $image_size ) && ( $image_size / WP_SMUSH_MAX_BYTES ) > 1 ) {
						$skipped[] = array(
							'size'   => 'full',
							'reason' => 'size_limit',
						);
					}
				}
			}
		}

		return $skipped;
	}

	/**
	 * Check whether to show warning or not for Pro users, if they don't have a valid install
	 *
	 * @return bool
	 */
	public function show_warning() {
		// If it's a free setup, Go back right away!
		if ( ! WP_Smush::is_pro() ) {
			return false;
		}

		// Return. If we don't have any headers.
		if ( ! isset( $this->api_headers ) ) {
			return false;
		}

		// Show warning, if function says it's premium and api says not premium.
		if ( isset( $this->api_headers['is_premium'] ) && ! intval( $this->api_headers['is_premium'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the HTML for progress bar
	 *
	 * @return string
	 */
	public function progress_bar() {
		return '<span class="spinner wp-smush-progress"></span>';
	}

	/**
	 * Print the column html.
	 *
	 * @param string  $id           Media id.
	 * @param string  $html         Status text.
	 * @param string  $button_txt   Button label.
	 * @param boolean $show_button  Whether to shoe the button.
	 * @param bool    $smushed      Whether image is smushed or not.
	 * @param bool    $echo         If true, it directly outputs the HTML.
	 * @param bool    $wrapper      Whether to return the button with wrapper div or not.
	 *
	 * @return string
	 */
	private function column_html( $id, $html = '', $button_txt = '', $show_button = true, $smushed = false, $echo = true, $wrapper = true ) {
		$allowed_images = array( 'image/jpeg', 'image/jpg', 'image/x-citrix-jpeg', 'image/png', 'image/x-png', 'image/gif' );

		// Don't proceed if attachment is not image, or if image is not a jpg, png or gif.
		if ( ! wp_attachment_is_image( $id ) || ! in_array( get_post_mime_type( $id ), $allowed_images ) ) {
			$status_txt = __( 'Not processed', 'wp-smushit' );
			if ( $echo ) {
				echo esc_html( $status_txt );
				return;
			}

			return $status_txt;
		}

		// If we aren't showing the button.
		if ( ! $show_button ) {
			if ( $echo ) {
				echo wp_kses_post( $html );
				return;
			}

			$class = $smushed ? ' smushed' : ' currently-smushing';

			return $wrapper ? '<div class="smush-wrap' . $class . '">' . $html . '</div>' : $html;
		}

		$html .= '
		<button  class="button button-primary wp-smush-send" data-id="' . $id . '">
            ' . $button_txt . '
		</button>';

		$skipped = get_post_meta( $id, WP_SMUSH_PREFIX . 'ignore-bulk', true );
		if ( 'true' === $skipped ) {
			$nonce = wp_create_nonce( 'wp-smush-remove-skipped' );
			$html .= '
			<button  class="button button-primary wp-smush-remove-skipped" data-id="' . $id . '" data-nonce="' . $nonce . '">
                ' . __( 'Show in bulk Smush', 'wp-smushit' ) . '
			</button>';
		}

		$html .= $this->progress_bar();

		if ( ! $echo ) {
			$class = $smushed ? ' smushed' : ' unsmushed';
			$html  = $wrapper ? '<div class="smush-wrap' . $class . '">' . $html . '</div>' : $html;

			return $html;
		}

		echo wp_kses_post( $html );
	}

	/**
	 * Add/Remove image id from Super Smushed images count.
	 *
	 * @param int    $id       Image id.
	 * @param string $op_type  Add/remove, whether to add the image id or remove it from the list.
	 * @param string $key      Options key.
	 *
	 * @return bool Whether the Super Smushed option was update or not
	 */
	public function update_super_smush_count( $id, $op_type = 'add', $key = 'wp-smush-super_smushed' ) {
		// Get the existing count.
		$super_smushed = get_option( $key, false );

		// Initialize if it doesn't exists.
		if ( ! $super_smushed || empty( $super_smushed['ids'] ) ) {
			$super_smushed = array(
				'ids' => array(),
			);
		}

		// Insert the id, if not in there already.
		if ( 'add' === $op_type && ! in_array( $id, $super_smushed['ids'] ) ) {
			$super_smushed['ids'][] = $id;
		} elseif ( 'remove' === $op_type && false !== ( $k = array_search( $id, $super_smushed['ids'] ) ) ) {
			// Else remove the id from the list.
			unset( $super_smushed['ids'][ $k ] );

			// Reset all the indexes.
			$super_smushed['ids'] = array_values( $super_smushed['ids'] );
		}

		// Add the timestamp.
		$super_smushed['timestamp'] = current_time( 'timestamp' );

		update_option( $key, $super_smushed, false );

		// Update to database.
		return true;
	}

	/**
	 * Checks if the image compression is lossy, stores the image id in options table
	 *
	 * @param int    $id     Image Id.
	 * @param array  $stats  Compression Stats.
	 * @param string $key    Meta Key for storing the Super Smushed ids (Optional for Media Library).
	 *                       Need To be specified for NextGen.
	 *
	 * @return bool
	 */
	public function update_lists( $id, $stats, $key = '' ) {
		// If Stats are empty or the image id is not provided, return.
		if ( empty( $stats ) || empty( $id ) || empty( $stats['stats'] ) ) {
			return false;
		}

		// Update Super Smush count.
		if ( isset( $stats['stats']['lossy'] ) && 1 == $stats['stats']['lossy'] ) {
			if ( empty( $key ) ) {
				update_post_meta( $id, 'wp-smush-lossy', 1 );
			} else {
				$this->update_super_smush_count( $id, 'add', $key );
			}
		}

		// Check and update re-smush list for media gallery.
		if ( ! empty( $this->resmush_ids ) && in_array( $id, $this->resmush_ids ) ) {
			$this->update_resmush_list( $id );
		}
	}

	/**
	 * Remove the given attachment id from resmush list and updates it to db
	 *
	 * @param string $attachment_id  Attachment ID.
	 * @param string $mkey           Option key.
	 */
	public function update_resmush_list( $attachment_id, $mkey = 'wp-smush-resmush-list' ) {
		$resmush_list = get_option( $mkey );

		// If there are any items in the resmush list, Unset the Key.
		if ( ! empty( $resmush_list ) && count( $resmush_list ) > 0 ) {
			$key = array_search( $attachment_id, $resmush_list );
			if ( $resmush_list ) {
				unset( $resmush_list[ $key ] );
			}
			$resmush_list = array_values( $resmush_list );
		}

		// If Resmush List is empty.
		if ( empty( $resmush_list ) || 0 === count( $resmush_list ) ) {
			// Delete resmush list.
			delete_option( $mkey );
		} else {
			update_option( $mkey, $resmush_list, false );
		}
	}

	/**
	 * Get the smush button text for attachment.
	 *
	 * @param int $id  Attachment ID for which the Status has to be set.
	 *
	 * @return string
	 */
	private function smush_status( $id ) {
		// Show Temporary Status, For Async Optimisation, No Good workaround.
		if ( ! get_option( "wp-smush-restore-{$id}", false ) && ! empty( $_POST['action'] ) && 'upload-attachment' === $_POST['action'] && $this->is_auto_smush_enabled() ) {
			$status_txt = '<p class="smush-status">' . __( 'Smushing in progress..', 'wp-smushit' ) . '</p>';

			// We need to show the smush button.
			$show_button = false;
			$button_txt  = __( 'Smush Now!', 'wp-smushit' );

			return $this->column_html( $id, $status_txt, $button_txt, $show_button, true, false, true );
		}
		// Else Return the normal status.
		$response = trim( $this->set_status( $id, false ) );

		return $response;
	}

	/**
	 * Send smush status for attachment
	 *
	 * @param array   $response    Response array.
	 * @param WP_Post $attachment  Attachment object.
	 *
	 * @return mixed
	 */
	public function smush_send_status( $response, $attachment ) {
		if ( ! isset( $attachment->ID ) ) {
			return $response;
		}

		// Validate nonce.
		$status            = $this->smush_status( $attachment->ID );
		$response['smush'] = $status;

		return $response;
	}

	/**
	 * Remove the Update info.
	 *
	 * @param bool $remove_notice  Remove notice.
	 */
	public function dismiss_update_info( $remove_notice = false ) {
		// From URL arg.
		if ( isset( $_GET['dismiss_smush_update_info'] ) && 1 == $_GET['dismiss_smush_update_info'] ) {
			$remove_notice = true;
		}

		// From Ajax.
		if ( ! empty( $_REQUEST['action'] ) && 'dismiss_update_info' === $_REQUEST['action'] ) {
			$remove_notice = true;
		}

		// Update Db.
		if ( $remove_notice ) {
			update_site_option( 'wp-smush-hide_update_info', 1 );
		}
	}

	/**
	 * Check whether to skip a specific image size or not
	 *
	 * @param string $size  Registered image size.
	 *
	 * @return bool true/false Whether to skip the image size or not
	 */
	public function skip_image_size( $size = '' ) {
		// No image size specified, Don't skip.
		if ( empty( $size ) ) {
			return false;
		}

		$image_sizes = $this->settings->get_setting( WP_SMUSH_PREFIX . 'image_sizes' );

		// If Images sizes aren't set, don't skip any of the image size.
		if ( false === $image_sizes ) {
			return false;
		}

		// Check if the size is in the smush list.
		if ( is_array( $image_sizes ) && ! in_array( $size, $image_sizes ) ) {
			return true;
		}
	}

	/**
	 * Process an image with Smush.
	 *
	 * @param string $file_path  Absolute path to the image.
	 *
	 * @return array|bool|WP_Error
	 */
	public function do_smushit( $file_path = '' ) {
		$errors   = new WP_Error();
		$dir_name = trailingslashit( dirname( $file_path ) );

		// Check if file exists and the directory is writable.
		if ( empty( $file_path ) ) {
			$errors->add( 'empty_path', __( 'File path is empty', 'wp-smushit' ) );
		} elseif ( ! file_exists( $file_path ) || ! is_file( $file_path ) ) {
			// Check that the file exists.
			/* translators: %s: file path */
			$errors->add( 'file_not_found', sprintf( __( 'Could not find %s', 'wp-smushit' ), $file_path ) );
		} elseif ( ! is_writable( $dir_name ) ) {
			// Check that the file is writable.
			/* translators: %s: directory name */
			$errors->add( 'not_writable', sprintf( __( '%s is not writable', 'wp-smushit' ), $dir_name ) );
		}

		$file_size = file_exists( $file_path ) ? filesize( $file_path ) : '';

		// Check if premium user.
		$max_size = WP_Smush::is_pro() ? WP_SMUSH_PREMIUM_MAX_BYTES : WP_SMUSH_MAX_BYTES;

		// Check if file exists.
		if ( 0 === (int) $file_size ) {
			/* translators: %1$s: image size, %2$s: image name */
			$errors->add( 'image_not_found', '<p>' . sprintf( __( 'Skipped (%1$s), image not found. Attachment: %2$s', 'wp-smushit' ), size_format( $file_size, 1 ), basename( $file_path ) ) . '</p>' );
		} elseif ( $file_size > $max_size ) {
			// Check size limit.
			/* translators: %1$s: image size, %2$s: image name */
			$errors->add( 'size_limit', '<p>' . sprintf( __( 'Skipped (%1$s), size limit exceeded. Attachment: %2$s', 'wp-smushit' ), size_format( $file_size, 1 ), basename( $file_path ) ) . '</p>' );
		}

		if ( count( $errors->get_error_messages() ) ) {
			return $errors;
		}

		// Save original file permissions.
		clearstatcache();
		$perms = fileperms( $file_path ) & 0777;

		/** Send image for smushing, and fetch the response */
		$response = $this->_post( $file_path, $file_size );

		if ( ! $response['success'] ) {
			$errors->add( 'false_response', $response['message'] );
		} elseif ( empty( $response['data'] ) ) {
			// If there is no data.
			$errors->add( 'no_data', __( 'Unknown API error', 'wp-smushit' ) );
		}

		if ( count( $errors->get_error_messages() ) ) {
			return $errors;
		}

		// If there are no savings, or image returned is bigger in size.
		if ( ( ! empty( $response['data']->bytes_saved ) && intval( $response['data']->bytes_saved ) <= 0 ) || empty( $response['data']->image ) ) {
			return $response;
		}
		$tempfile = $file_path . '.tmp';

		// Add the file as tmp.
		file_put_contents( $tempfile, $response['data']->image );

		// Replace the file.
		$success = @rename( $tempfile, $file_path );

		// If tempfile still exists, unlink it.
		if ( file_exists( $tempfile ) ) {
			@unlink( $tempfile );
		}

		// If file renaming failed.
		if ( ! $success ) {
			@copy( $tempfile, $file_path );
			@unlink( $tempfile );
		}

		// Some servers are having issue with file permission, this should fix it.
		if ( empty( $perms ) || ! $perms ) {
			// Source: WordPress Core.
			$stat  = stat( dirname( $file_path ) );
			$perms = $stat['mode'] & 0000666; // Same permissions as parent folder, strip off the executable bits.
		}
		@chmod( $file_path, $perms );

		return $response;
	}

	/**
	 * Posts an image to Smush.
	 *
	 * @param string $file_path  Path of file to send to Smush.
	 * @param int    $file_size  File size.
	 *
	 * @return bool|array array containing success status, and stats
	 */
	private function _post( $file_path, $file_size ) {
		$data = false;

		$file_data = file_get_contents( $file_path );

		$headers = array(
			'accept'       => 'application/json',   // The API returns JSON.
			'content-type' => 'application/binary', // Set content type to binary.
		);

		// Check if premium member, add API key.
		$api_key = $this->_get_api_key();
		if ( ! empty( $api_key ) && WP_Smush::is_pro() ) {
			$headers['apikey'] = $api_key;
		}

		if ( WP_Smush::is_pro() && $this->settings->get( 'lossy' ) ) {
			$headers['lossy'] = 'true';
		} else {
			$headers['lossy'] = 'false';
		}

		$headers['exif'] = $this->settings->get( 'strip_exif' ) ? 'false' : 'true';

		$api_url = defined( 'WP_SMUSH_API_HTTP' ) ? WP_SMUSH_API_HTTP : WP_SMUSH_API;
		$args    = array(
			'headers'    => $headers,
			'body'       => $file_data,
			'timeout'    => WP_SMUSH_TIMEOUT,
			'user-agent' => WP_SMUSH_UA,
		);
		// Temporary increase the limit.
		wp_raise_memory_limit( 'image' );
		$result = wp_remote_post( $api_url, $args );

		unset( $file_data ); // Free memory.
		if ( is_wp_error( $result ) ) {
			$er_msg = $result->get_error_message();

			// Hostgator Issue.
			if ( ! empty( $er_msg ) && strpos( $er_msg, 'SSL CA cert' ) !== false ) {
				// Update DB for using http protocol.
				$this->settings->set_setting( WP_SMUSH_PREFIX . 'use_http', 1 );
			}
			// Check for timeout error and suggest to filter timeout.
			if ( strpos( $er_msg, 'timed out' ) ) {
				$data['message'] = esc_html__( "Skipped due to a timeout error. You can increase the request timeout to make sure Smush has enough time to process larger files. define('WP_SMUSH_API_TIMEOUT', 150);", 'wp-smushit' );
			} else {
				// Handle error.
				/* translators: %s error message */
				$data['message'] = sprintf( __( 'Error posting to API: %s', 'wp-smushit' ), $result->get_error_message() );
			}
			$data['success'] = false;
			unset( $result ); // Free memory.
			return $data;
		} elseif ( 200 !== wp_remote_retrieve_response_code( $result ) ) {
			// Handle error.
			/* translators: %1$s: respnse code, %2$s error message */
			$data['message'] = sprintf( __( 'Error posting to API: %1$s %2$s', 'wp-smushit' ), wp_remote_retrieve_response_code( $result ), wp_remote_retrieve_response_message( $result ) );
			$data['success'] = false;
			unset( $result ); // Free memory.
			return $data;
		}

		// If there is a response and image was successfully optimised.
		$response = json_decode( $result['body'] );
		if ( $response && true === $response->success ) {

			// If there is any savings.
			if ( $response->data->bytes_saved > 0 ) {
				// base64_decode is necessary to send binary img over JSON, no security problems here!
				$image     = base64_decode( $response->data->image );
				$image_md5 = md5( $response->data->image );
				if ( $response->data->image_md5 !== $image_md5 ) {
					// Handle error.
					$data['message'] = __( 'Smush data corrupted, try again.', 'wp-smushit' );
					$data['success'] = false;
				} else {
					$data['success']     = true;
					$data['data']        = $response->data;
					$data['data']->image = $image;
				}
				unset( $image );// Free memory.
			} else {
				// Just return the data.
				$data['success'] = true;
				$data['data']    = $response->data;
			}

			// Check for API message and store in db.
			if ( isset( $response->data->api_message ) && ! empty( $response->data->api_message ) ) {
				$this->add_api_message( $response->data->api_message );
			}

			// If is_premium is set in response, send it over to check for member validity.
			if ( ! empty( $response->data ) && isset( $response->data->is_premium ) ) {
				$this->api_headers['is_premium'] = $response->data->is_premium;
			}
		} else {
			// Server side error, get message from response.
			$data['message'] = ! empty( $response->data ) ? $response->data : __( "Image couldn't be smushed", 'wp-smushit' );
			$data['success'] = false;
		}

		// Free memory and return data.
		unset( $result );
		unset( $response );
		return $data;
	}

	/**
	 * Replace the old API message with the latest one if it doesn't exists already
	 *
	 * @param array $api_message  API message.
	 *
	 * @return null
	 */
	private function add_api_message( $api_message = array() ) {
		if ( empty( $api_message ) || ! count( $api_message ) || empty( $api_message['timestamp'] ) || empty( $api_message['message'] ) ) {
			return null;
		}
		$o_api_message = get_site_option( WP_SMUSH_PREFIX . 'api_message', array() );
		if ( array_key_exists( $api_message['timestamp'], $o_api_message ) ) {
			return null;
		}
		$api_message['status'] = 'show';

		$message                              = array();
		$message[ $api_message['timestamp'] ] = array(
			'message' => sanitize_text_field( $api_message['message'] ),
			'type'    => sanitize_text_field( $api_message['type'] ),
			'status'  => 'show',
		);
		update_site_option( WP_SMUSH_PREFIX . 'api_message', $message );
	}

	/**
	 * Fills $placeholder array with values from $data array
	 *
	 * @param array $placeholders  Placeholders array.
	 * @param array $data          Data to fill with.
	 *
	 * @return array
	 */
	public function _array_fill_placeholders( array $placeholders, array $data ) {
		$placeholders['percent']     = $data['compression'];
		$placeholders['bytes']       = $data['bytes_saved'];
		$placeholders['size_before'] = $data['before_size'];
		$placeholders['size_after']  = $data['after_size'];
		$placeholders['time']        = $data['time'];

		return $placeholders;
	}

	/**
	 * Returns signature for single size of the smush api message to be saved to db;
	 *
	 * @return array
	 */
	public function _get_size_signature() {
		return array(
			'percent'     => 0,
			'bytes'       => 0,
			'size_before' => 0,
			'size_after'  => 0,
			'time'        => 0,
		);
	}

	/**
	 * Optimises the image sizes
	 *
	 * Note: Function name is bit confusing, it is for optimisation, and calls the resizing function as well
	 *
	 * Read the image paths from an attachment's meta data and process each image
	 * with wp_smushit().
	 *
	 * @param array    $meta  Image meta data.
	 * @param null|int $id    Image ID.
	 *
	 * @return mixed
	 */
	public function resize_from_meta_data( $meta, $id = null ) {
		// Flag to check, if original size image should be smushed or not.
		$original   = $this->settings->get( 'original' );
		$smush_full = WP_Smush::is_pro() && true === $original;

		$errors = new WP_Error();
		$stats  = array(
			'stats' => array_merge(
				$this->_get_size_signature(),
				array(
					'api_version' => - 1,
					'lossy'       => - 1,
					'keep_exif'   => false,
				)
			),
			'sizes' => array(),
		);

		if ( $id && false === wp_attachment_is_image( $id ) ) {
			return $meta;
		}

		// Set attachment id and media type.
		$this->attachment_id = $id;
		$this->media_type    = 'wp';

		// File path and URL for original image.
		$attachment_file_path = WP_Smush_Helper::get_attached_file( $id );

		// If images has other registered size, smush them first.
		if ( ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size_key => $size_data ) {
				// Check if registered size is supposed to be Smushed or not.
				if ( 'full' !== $size_key && $this->skip_image_size( $size_key ) ) {
					continue;
				}

				// We take the original image. The 'sizes' will all match the same URL and
				// path. So just get the dirname and replace the filename.
				$attachment_file_path_size = path_join( dirname( $attachment_file_path ), $size_data['file'] );

				/**
				 * Allows S3 to hook over here and check if the given file path exists else download the file.
				 */
				do_action( 'smush_file_exists', $attachment_file_path_size, $id, $size_data );

				$ext = WP_Smush_Helper::get_mime_type( $attachment_file_path_size );

				if ( $ext ) {
					$valid_mime = array_search(
						$ext,
						array(
							'jpg' => 'image/jpeg',
							'png' => 'image/png',
							'gif' => 'image/gif',
						),
						true
					);

					if ( false === $valid_mime ) {
						continue;
					}
				}

				/**
				 * Allows to skip a image from smushing.
				 *
				 * @param bool , Smush image or not
				 * @$size string, Size of image being smushed
				 */
				$smush_image = apply_filters( 'wp_smush_media_image', true, $size_key );
				if ( ! $smush_image ) {
					continue;
				}

				// Store details for each size key.
				$response = $this->do_smushit( $attachment_file_path_size );

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				// If there are no stats.
				if ( empty( $response['data'] ) ) {
					continue;
				}

				// If the image size grew after smushing, skip it.
				if ( $response['data']->after_size > $response['data']->before_size ) {
					continue;
				}

				// All Clear, Store the stat.
				// TODO: Move the existing stats code over here, we don't need to do the stats part twice.
				$stats['sizes'][ $size_key ] = (object) $this->_array_fill_placeholders( $this->_get_size_signature(), (array) $response['data'] );

				if ( empty( $stats['stats']['api_version'] ) || $stats['stats']['api_version'] == - 1 ) {
					$stats['stats']['api_version'] = $response['data']->api_version;
					$stats['stats']['lossy']       = $response['data']->lossy;
					$stats['stats']['keep_exif']   = ! empty( $response['data']->keep_exif ) ? $response['data']->keep_exif : 0;
				}
			}
		} else {
			$smush_full = true;
		}

		/**
		 * Allows to skip a image from smushing
		 *
		 * @param bool , Smush image or not
		 * @$size string, Size of image being smushed
		 */
		$smush_full_image = apply_filters( 'wp_smush_media_image', true, 'full' );

		// Whether to update the image stats or not.
		$store_stats = true;

		// If original size is supposed to be smushed.
		if ( $smush_full && $smush_full_image ) {

			$full_image_response = $this->do_smushit( $attachment_file_path );

			if ( is_wp_error( $full_image_response ) ) {
				return $full_image_response;
			}

			// If there are no stats.
			if ( empty( $full_image_response['data'] ) ) {
				$store_stats = false;
			}

			// If the image size grew after smushing, skip it.
			if ( $full_image_response['data']->after_size > $full_image_response['data']->before_size ) {
				$store_stats = false;
			}

			if ( $store_stats ) {
				$stats['sizes']['full'] = (object) $this->_array_fill_placeholders( $this->_get_size_signature(), (array) $full_image_response['data'] );
			}

			// Api version and lossy, for some images, full image i skipped and for other images only full exists
			// so have to add code again.
			if ( empty( $stats['stats']['api_version'] ) || $stats['stats']['api_version'] == - 1 ) {
				$stats['stats']['api_version'] = $full_image_response['data']->api_version;
				$stats['stats']['lossy']       = $full_image_response['data']->lossy;
				$stats['stats']['keep_exif']   = ! empty( $full_image_response['data']->keep_exif ) ? $full_image_response['data']->keep_exif : 0;
			}
		}

		$has_errors = (bool) count( $errors->get_error_messages() );

		// Set smush status for all the images, store it in wp-smpro-smush-data.
		if ( ! $has_errors ) {
			$existing_stats = get_post_meta( $id, self::$smushed_meta_key, true );

			if ( ! empty( $existing_stats ) ) {

				// Update stats for each size.
				if ( isset( $existing_stats['sizes'] ) && ! empty( $stats['sizes'] ) ) {

					foreach ( $existing_stats['sizes'] as $size_name => $size_stats ) {
						// If stats for a particular size doesn't exists.
						if ( empty( $stats['sizes'][ $size_name ] ) ) {
							$stats['sizes'][ $size_name ] = $existing_stats['sizes'][ $size_name ];
						} else {

							$existing_stats_size = (object) $existing_stats['sizes'][ $size_name ];

							// Store the original image size.
							$stats['sizes'][ $size_name ]->size_before = ( ! empty( $existing_stats_size->size_before ) && $existing_stats_size->size_before > $stats['sizes'][ $size_name ]->size_before ) ? $existing_stats_size->size_before : $stats['sizes'][ $size_name ]->size_before;

							// Update compression percent and bytes saved for each size.
							$stats['sizes'][ $size_name ]->bytes   = $stats['sizes'][ $size_name ]->bytes + $existing_stats_size->bytes;
							$stats['sizes'][ $size_name ]->percent = $this->calculate_percentage( $stats['sizes'][ $size_name ], $existing_stats_size );
						}
					}
				}
			}

			// Sum Up all the stats.
			$stats = $this->total_compression( $stats );

			// If there was any compression and there was no error in smushing.
			if ( isset( $stats['stats']['bytes'] ) && $stats['stats']['bytes'] >= 0 && ! $has_errors ) {
				/**
				 * Runs if the image smushing was successful
				 *
				 * @param int $id Image Id
				 *
				 * @param array $stats Smush Stats for the image
				 */
				do_action( 'wp_smush_image_optimised', $id, $stats );
			}
			update_post_meta( $id, self::$smushed_meta_key, $stats );
		}

		unset( $stats );

		// Unset response.
		if ( ! empty( $response ) ) {
			unset( $response );
		}

		return $meta;
	}

	/**
	 * Calculate saving percentage from existing and current stats
	 *
	 * @param object|string $stats           Stats object.
	 * @param object|string $existing_stats  Existing stats object.
	 *
	 * @return float
	 */
	public function calculate_percentage( $stats = '', $existing_stats = '' ) {
		if ( empty( $stats ) || empty( $existing_stats ) ) {
			return 0;
		}
		$size_before = ! empty( $stats->size_before ) ? $stats->size_before : $existing_stats->size_before;
		$size_after  = ! empty( $stats->size_after ) ? $stats->size_after : $existing_stats->size_after;
		$savings     = $size_before - $size_after;
		if ( $savings > 0 ) {
			$percentage = ( $savings / $size_before ) * 100;
			$percentage = $percentage > 0 ? round( $percentage, 2 ) : $percentage;

			return $percentage;
		}

		return 0;
	}

	/**
	 * Read the image paths from an attachment's meta data and process each image with wp_smushit().
	 *
	 * @uses resize_from_meta_data
	 *
	 * @param mixed    $meta  Attachment meta data.
	 * @param null|int $id    Attachment ID.
	 *
	 * @return mixed
	 */
	public function smush_image( $meta, $id = null ) {
		if ( ! is_admin() ) {
			// We need to check if this call originated from Gutenberg (is_admin() does not work in REST API).
			if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
				return $meta;
			}

			$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );
			if ( empty( $route ) || '/wp/v2/media' !== $route ) {
				// If not - return image meta data.
				return $meta;
			}
		}

		$upload_attachment    = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		$is_upload_attachment = 'upload-attachment' === $upload_attachment || isset( $_POST['post_id'] );

		// Our async task runs when action is upload-attachment and post_id found. So do not run on these conditions.
		if ( $is_upload_attachment && defined( 'WP_SMUSH_ASYNC' ) && WP_SMUSH_ASYNC ) {
			return $meta;
		}

		// Return directly if not a image.
		if ( ! wp_attachment_is_image( $id ) ) {
			return $meta;
		}

		// Check if we're restoring the image Or already smushing the image.
		if ( get_option( "wp-smush-restore-$id", false ) || get_option( "smush-in-progress-$id", false ) ) {
			return $meta;
		}

		/**
		 * Filter: wp_smush_image
		 *
		 * Whether to smush the given attachment id or not
		 *
		 * @param bool $skip  Bool, whether to Smush image or not.
		 * @param int  $ID    Attachment Id, Attachment id of the image being processed.
		 */
		if ( ! apply_filters( 'wp_smush_image', true, $id ) ) {
			return $meta;
		}

		// Set a transient to avoid multiple request.
		update_option( 'smush-in-progress-' . $id, true );

		// While uploading from Mobile App or other sources, admin_init action may not fire.
		// So we need to manually initialize those.
		WP_Smush::get_instance()->core()->mod->resize->initialize( true );

		// Check if auto is enabled.
		$auto_smush = $this->is_auto_smush_enabled();

		// Get the file path for backup.
		$attachment_file_path = WP_Smush_Helper::get_attached_file( $id );

		$this->check_animated_status( $attachment_file_path, $id );

		// Take backup.
		WP_Smush::get_instance()->core()->mod->backup->create_backup( $attachment_file_path, '', $id );

		// Optionally resize images.
		$meta = WP_Smush::get_instance()->core()->mod->resize->auto_resize( $id, $meta );

		// Auto Smush the new image.
		if ( $auto_smush ) {
			// Optionally convert PNGs to JPG.
			$meta = WP_Smush::get_instance()->core()->mod->png2jpg->png_to_jpg( $id, $meta );

			/**
			 * Fix for Hostgator.
			 * Check for use of http url (Hostgator mostly).
			 */
			$use_http = wp_cache_get( WP_SMUSH_PREFIX . 'use_http', 'smush' );

			if ( ! $use_http ) {
				$use_http = $this->settings->get_setting( WP_SMUSH_PREFIX . 'use_http', false );
				wp_cache_add( WP_SMUSH_PREFIX . 'use_http', $use_http, 'smush' );
			}

			if ( $use_http ) {
				// HTTP url.
				define( 'WP_SMUSH_API_HTTP', 'http://smushpro.wpmudev.org/1.0/' );
			}

			$this->resize_from_meta_data( $meta, $id );
		} else {
			// Remove the smush metadata.
			delete_post_meta( $id, self::$smushed_meta_key );
		}

		// Delete transient.
		delete_option( 'smush-in-progress-' . $id );

		return $meta;
	}

	/**
	 * Smush single images
	 *
	 * @param int  $attachment_id  Attachment ID.
	 * @param bool $return         Return/echo the stats.
	 *
	 * @return array|string
	 */
	public function smush_single( $attachment_id, $return = false ) {
		// If the smushing option is already set, return the status.
		if ( get_option( "smush-in-progress-{$attachment_id}", false ) || get_option( "wp-smush-restore-{$attachment_id}", false ) ) {
			// Get the button status.
			$status = $this->set_status( $attachment_id, false, true );
			if ( $return ) {
				return $status;
			}

			wp_send_json_success( $status );
		}

		// Set a transient to avoid multiple request.
		update_option( "smush-in-progress-{$attachment_id}", true );

		$attachment_id = absint( (int) ( $attachment_id ) );

		// Get the file path for backup.
		$attachment_file_path = WP_Smush_Helper::get_attached_file( $attachment_id );

		// Download file if not exists.
		do_action( 'smush_file_exists', $attachment_file_path, $attachment_id );

		$this->check_animated_status( $attachment_file_path, $attachment_id );

		// Take backup.
		WP_Smush::get_instance()->core()->mod->backup->create_backup( $attachment_file_path, '', $attachment_id );

		// Get the image metadata from $_POST.
		$original_meta = ! empty( $_POST['metadata'] ) ? WP_Smush_Helper::format_meta_from_post( $_POST['metadata'] ) : '';

		$original_meta = empty( $original_meta ) ? wp_get_attachment_metadata( $attachment_id ) : $original_meta;

		// Send image for resizing, if enabled resize first before any other operation.
		$updated_meta = $this->resize_image( $attachment_id, $original_meta );

		// Convert PNGs to JPG.
		$updated_meta = WP_Smush::get_instance()->core()->mod->png2jpg->png_to_jpg( $attachment_id, $updated_meta );

		$original_meta = ! empty( $updated_meta ) ? $updated_meta : $original_meta;

		// Smush the image.
		$smush = $this->resize_from_meta_data( $original_meta, $attachment_id );

		/**
		 * When S3 integration is enabled, the wp_update_attachment_metadata below will trigger the
		 * wp_update_attachment_metadata filter WP Offload Media, which in turn will try to re-upload all the files
		 * to an S3 bucket. But, if some sizes are skipped during Smushing, WP Offload Media will print error
		 * messages to debug.log. This will help avoid that.
		 *
		 * @since 3.0
		 */
		add_filter( 'as3cf_attachment_file_paths', array( $this, 'remove_sizes_from_s3_upload' ), 10, 3 );

		// Update the details, after smushing, so that latest image is used in hook.
		wp_update_attachment_metadata( $attachment_id, $original_meta );

		// Get the button status.
		$status = $this->set_status( $attachment_id, false, true );

		// Delete the transient after attachment meta is updated.
		delete_option( 'smush-in-progress-' . $attachment_id );

		// Send Json response if we are not suppose to return the results.
		if ( is_wp_error( $smush ) ) {
			if ( $return ) {
				return array( 'error' => $smush->get_error_message() );
			}

			wp_send_json_error(
				array(
					'error_msg'    => '<p class="wp-smush-error-message">' . $smush->get_error_message() . '</p>',
					'show_warning' => intval( $this->show_warning() ),
				)
			);
		}

		$this->update_resmush_list( $attachment_id );
		if ( $return ) {
			return $status;
		}

		wp_send_json_success( $status );
	}

	/**
	 * Returns api key.
	 *
	 * @return mixed
	 */
	private function _get_api_key() {
		$api_key = false;

		// If API key defined manually, get that.
		if ( defined( 'WPMUDEV_APIKEY' ) && WPMUDEV_APIKEY ) {
			$api_key = WPMUDEV_APIKEY;
		} elseif ( class_exists( 'WPMUDEV_Dashboard' ) ) {
			// If dashboard plugin is active, get API key from db.
			$api_key = get_site_option( 'wpmudev_apikey' );
		}

		return $api_key;
	}

	/**
	 * Returns size saved from the api call response
	 *
	 * @param string $message  Message.
	 *
	 * @return string|bool
	 */
	private function get_saved_size( $message ) {
		if ( preg_match( '/\((.*)\)/', $message, $matches ) ) {
			return isset( $matches[1] ) ? $matches[1] : false;
		}

		return false;
	}

	/**
	 * Skip messages respective to their IDs.
	 *
	 * @param string $msg_id  Message ID.
	 *
	 * @return bool
	 */
	public function skip_reason( $msg_id ) {
		$count           = count( get_intermediate_image_sizes() );
		$smush_orgnl_txt = sprintf(
			/* translators: %s: number of thumbnails */
			esc_html__(
				'When you upload an image to WordPress it automatically creates %s thumbnail sizes
			that are commonly used in your pages. WordPress also stores the original full-size image, but because
			these are not usually embedded on your site we don’t Smush them. Pro users can
			override this.',
				'wp-smushit'
			),
			$count
		);

		$skip_msg = array(
			'large_size' => $smush_orgnl_txt,
			'size_limit' => esc_html__(
				"Image couldn't be smushed as it exceeded the 1Mb size limit,
			Pro users can smush images with size up to 32Mb.",
				'wp-smushit'
			),
		);

		$skip_rsn = ! empty( $skip_msg[ $msg_id ] ) ? esc_html__( ' Skipped', 'wp-smushit' ) : '';
		$skip_rsn = ! empty( $skip_rsn ) ? $skip_rsn . '<span class="sui-tooltip sui-tooltip-left sui-tooltip-constrained sui-tooltip-top-right-mobile" data-tooltip="' . $skip_msg[ $msg_id ] . '"><i class="dashicons dashicons-editor-help"></i></span>' : '';

		return $skip_rsn;
	}

	/**
	 * If auto smush is set to true or not, default is true
	 *
	 * @return int|mixed
	 */
	public function is_auto_smush_enabled() {
		$auto_smush = $this->settings->get( 'auto' );

		// Keep the auto smush on by default.
		if ( ! isset( $auto_smush ) ) {
			$auto_smush = 1;
		}

		return $auto_smush;
	}

	/**
	 * Deletes all the backup files when an attachment is deleted
	 * Update resmush List
	 * Update Super Smush image count
	 *
	 * @param int $image_id  Attachment ID.
	 *
	 * @return bool
	 */
	public function delete_images( $image_id ) {
		$db = WP_Smush::get_instance()->core()->mod->db;

		// Update the savings cache.
		$db->resize_savings( true );

		// Update the savings cache.
		$db->conversion_savings( true );

		// If no image id provided.
		if ( empty( $image_id ) ) {
			return false;
		}

		// Check and Update resmush list.
		if ( $resmush_list = get_option( 'wp-smush-resmush-list' ) ) {
			$this->update_resmush_list( $image_id, 'wp-smush-resmush-list' );
		}

		/** Delete Backups  */
		// Check if we have any smush data for image.
		$this->delete_backup_files( $image_id );
	}

	/**
	 * Clear up all the backup files for the image, if any.
	 *
	 * @param int $image_id  Attachment ID.
	 */
	private function delete_backup_files( $image_id ) {
		$smush_meta = get_post_meta( $image_id, self::$smushed_meta_key, true );
		if ( empty( $smush_meta ) ) {
			// Return if we don't have any details.
			return;
		}

		// Get the attachment details.
		$meta = wp_get_attachment_metadata( $image_id );

		// Attachment file path.
		$file = get_attached_file( $image_id );

		// Get the backup path.
		$backup_name = $this->get_image_backup_path( $file );

		// If file exists, corresponding to our backup path, delete it.
		@unlink( $backup_name );

		// Check meta for rest of the sizes.
		if ( ! empty( $meta ) && ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size ) {
				// Get the file path.
				if ( empty( $size['file'] ) ) {
					continue;
				}

				// Image Path and Backup path.
				$image_size_path  = path_join( dirname( $file ), $size['file'] );
				$image_bckup_path = $this->get_image_backup_path( $image_size_path );
				@unlink( $image_bckup_path );
			}
		}
	}

	/**
	 * Calculate saving percentage for a given size stats
	 *
	 * @param $stats
	 *
	 * @return float|int
	 */
	private function calculate_percentage_from_stats( $stats ) {
		if ( empty( $stats ) || ! isset( $stats->size_before, $stats->size_after ) ) {
			return 0;
		}

		$savings = $stats->size_before - $stats->size_after;
		if ( $savings > 0 ) {
			$percentage = ( $savings / $stats->size_before ) * 100;
			$percentage = $percentage > 0 ? round( $percentage, 2 ) : $percentage;

			return $percentage;
		}
	}

	/**
	 * Perform the resize operation for the image
	 *
	 * @param $attachment_id
	 *
	 * @param $meta
	 *
	 * @return mixed
	 */
	public function resize_image( $attachment_id, $meta ) {
		if ( empty( $attachment_id ) || empty( $meta ) ) {
			return $meta;
		}

		return WP_Smush::get_instance()->core()->mod->resize->auto_resize( $attachment_id, $meta );
	}

	/**
	 * Send a smush request for the attachment
	 *
	 * @param int $id  Attachment ID.
	 */
	public function wp_smush_handle_async( $id ) {
		// If we don't have image id, or the smush is already in progress for the image, return.
		if ( empty( $id ) || get_option( 'smush-in-progress-' . $id, false ) || get_option( "wp-smush-restore-$id", false ) ) {
			return;
		}

		// If auto Smush is disabled.
		if ( ! $this->is_auto_smush_enabled() ) {
			return;
		}

		/**
		 * Filter: wp_smush_image
		 *
		 * Whether to smush the given attachment id or not
		 *
		 * @param $skip bool, whether to Smush image or not
		 *
		 * @param $Attachment Id, Attachment id of the image being processed
		 */
		if ( ! apply_filters( 'wp_smush_image', true, $id ) ) {
			return;
		}

		$this->smush_single( $id, true );
	}

	/**
	 * Send a smush request for the attachment
	 *
	 * @param int   $id         Attachment ID.
	 * @param array $post_data  Post data.
	 */
	public function wp_smush_handle_editor_async( $id, $post_data ) {
		// If we don't have image id, or the smush is already in progress for the image, return.
		if ( empty( $id ) || get_option( "smush-in-progress-$id", false ) || get_option( "wp-smush-restore-$id", false ) ) {
			return;
		}

		// If auto Smush is disabled.
		if ( ! $this->is_auto_smush_enabled() ) {
			return;
		}

		/**
		 * Filter: wp_smush_image
		 *
		 * Whether to smush the given attachment id or not
		 *
		 * @param bool $skip  Whether to Smush image or not.
		 * @param int  $id    Attachment ID of the image being processed.
		 */
		if ( ! apply_filters( 'wp_smush_image', true, $id ) ) {
			return;
		}

		// If filepath is not set or file doesn't exists.
		if ( ! isset( $post_data['filepath'] ) || ! file_exists( $post_data['filepath'] ) ) {
			return;
		}

		// Send image for smushing.
		$res = $this->do_smushit( $post_data['filepath'] );

		// Exit if smushing wasn't successful.
		if ( is_wp_error( $res ) || empty( $res['success'] ) || ! $res['success'] ) {
			return;
		}

		// Update stats if it's the full size image.
		// Return if it's not the full image size.
		if ( $post_data['filepath'] != get_attached_file( $post_data['postid'] ) ) {
			return;
		}

		// Get the existing Stats.
		$smush_stats = get_post_meta( $post_data['postid'], self::$smushed_meta_key, true );
		$stats_full  = ! empty( $smush_stats['sizes'] ) && ! empty( $smush_stats['sizes']['full'] ) ? $smush_stats['sizes']['full'] : '';

		if ( empty( $stats_full ) ) {
			return;
		}

		// store the original image size.
		$stats_full->size_before = ( ! empty( $stats_full->size_before ) && $stats_full->size_before > $res['data']->before_size ) ? $stats_full->size_before : $res['data']->before_size;
		$stats_full->size_after  = $res['data']->after_size;

		// Update compression percent and bytes saved for each size.
		$stats_full->bytes = $stats_full->size_before - $stats_full->size_after;

		$stats_full->percent          = $this->calculate_percentage_from_stats( $stats_full );
		$smush_stats['sizes']['full'] = $stats_full;

		// Update Stats
		update_post_meta( $post_data['postid'], self::$smushed_meta_key, $smush_stats );
	}

	/**
	 * Registers smush action for HUB API
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function smush_stats( $actions ) {
		$actions['smush_get_stats'] = array( $this, 'smush_attachment_count' );

		return $actions; // always return at least the original array so we don't mess up other integrations.
	}

	/**
	 * Send stats to Hub
	 *
	 * @param $params
	 * @param $action
	 * @param $request
	 */
	public function smush_attachment_count( $params, $action, $request ) {
		$core = WP_Smush::get_instance()->core();

		$stats = array(
			'count_total'     => 0,
			'count_smushed'   => 0,
			'count_unsmushed' => 0,
			'savings'         => array(),
		);

		if ( ! isset( $core->stats ) ) {
			// Setup stats, if not set already.
			$core->setup_global_stats();
		}
		// Total, Smushed, Unsmushed, Savings.
		$stats['count_total']   = $core->total_count;
		$stats['count_smushed'] = $core->smushed_count;
		// Considering the images to be resmushed.
		$stats['count_unsmushed'] = $core->remaining_count;
		$stats['savings']         = $core->stats;

		$request->send_json_success( $stats );
	}

	/**
	 * Compare Values
	 *
	 * @param object $a
	 * @param object $b
	 *
	 * @return bool
	 */
	public static function cmp( $a, $b ) {
		return $a->bytes < $b->bytes;
	}

	/**
	 * Remove paths that should not be re-uploaded to an S3 bucket.
	 *
	 * See as3cf_attachment_file_paths filter description for more information.
	 *
	 * @since 3.0
	 *
	 * @param array $paths          Paths to be uploaded to S3 bucket.
	 * @param int   $attachment_id  Attachment ID.
	 * @param array $meta           Image meta data.
	 *
	 * @return mixed
	 */
	public function remove_sizes_from_s3_upload( $paths, $attachment_id, $meta ) {
		// Only run when S3 integration is active. It won't run otherwise, but check just in case.
		if ( ! $this->settings->get( 's3' ) ) {
			return $paths;
		}

		foreach ( $meta['sizes'] as $size_key => $size_data ) {
			// Check if registered size is supposed to be Smushed or not.
			if ( 'full' !== $size_key && $this->skip_image_size( $size_key ) ) {
				unset( $paths[ $size_key ] );
			}
		}

		return $paths;
	}

	/**
	 * Check to see if file is animated.
	 *
	 * @since 3.0  Moved from class-wp-smush-resize.php
	 *
	 * @param string $file_path  Image file path.
	 * @param int    $id         Attachment ID.
	 */
	public function check_animated_status( $file_path, $id ) {
		// Only do this for GIFs.
		if ( 'image/gif' !== get_post_mime_type( $id ) || ! isset( $file_path ) ) {
			return;
		}

		$filecontents = file_get_contents( $file_path );

		$str_loc = 0;
		$count   = 0;

		// There is no point in continuing after we find a 2nd frame.
		while ( $count < 2 ) {
			$where1 = strpos( $filecontents, "\x00\x21\xF9\x04", $str_loc );
			if ( false === $where1 ) {
				break;
			} else {
				$str_loc = $where1 + 1;
				$where2  = strpos( $filecontents, "\x00\x2C", $str_loc );
				if ( false === $where2 ) {
					break;
				} else {
					if ( $where2 === $where1 + 8 ) {
						$count++;
					}
					$str_loc = $where2 + 1;
				}
			}
		}

		if ( $count > 1 ) {
			update_post_meta( $id, WP_SMUSH_PREFIX . 'animated', true );
		}
	}

}
