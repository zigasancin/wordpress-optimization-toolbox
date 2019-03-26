<?php
/**
 * CDN class: WP_Smush_CDN
 *
 * @package WP_Smush
 * @version 3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_CDN
 */
class WP_Smush_CDN extends WP_Smush_Content {

	/**
	 * Smush CDN base url.
	 *
	 * @var null|string
	 */
	private $cdn_base = null;

	/**
	 * Flag to check if CDN is active.
	 *
	 * @var bool
	 */
	private $cdn_active = false;

	/**
	 * CDN status.
	 *
	 * @var stdClass $status
	 */
	private $status;

	/**
	 * Supported file extensions.
	 *
	 * @var array $supported_extensions
	 */
	private $supported_extensions = array(
		'gif',
		'jpg',
		'jpeg',
		'png',
	);

	/**
	 * WP_Smush_CDN constructor.
	 */
	public function init() {
		/**
		 * Settings.
		 */
		// Filters the setting variable to add module setting title and description.
		add_filter( 'wp_smush_settings', array( $this, 'register' ) );

		// Add settings descriptions to the meta box.
		add_action( 'smush_setting_column_right_inside', array( $this, 'settings_desc' ), 10, 2 );

		// Add setting names to appropriate group.
		add_action( 'wp_smush_cdn_settings', array( $this, 'add_settings' ) );

		// Cron task to update CDN stats.
		add_action( 'smush_update_cdn_stats', array( $this, 'cron_update_stats' ) );

		// Set auto resize flag.
		$this->init_flags();

		// Add stats to stats box.
		add_action( 'stats_ui_after_resize_savings', array( $this, 'cdn_stats_ui' ), 20 );

		/**
		 * Main functionality.
		 */
		if ( ! $this->settings->get( 'cdn' ) || ! $this->cdn_active ) {
			return;
		}

		// Set Smush API config.
		add_action( 'init', array( $this, 'set_cdn_url' ) );

		// Only do stuff on the frontend.
		if ( is_admin() ) {
			// Verify the cron task to update stats is configured.
			$this->schedule_cron();
			return;
		}

		// Add cdn url to dns prefetch.
		add_filter( 'wp_resource_hints', array( $this, 'dns_prefetch' ), 99, 2 );

		// Start an output buffer before any output starts.
		add_action( 'template_redirect', array( $this, 'process_buffer' ), 1 );

		// Update responsive image srcset and sizes if required.
		add_filter( 'wp_calculate_image_srcset', array( $this, 'update_image_srcset' ), 99, 5 );
		add_filter( 'wp_calculate_image_sizes', array( $this, 'update_image_sizes' ), 10, 5 );

		// Add resizing arguments to image src.
		add_filter( 'smush_image_cdn_args', array( $this, 'update_cdn_image_src_args' ), 99, 3 );
	}

	/**************************************
	 *
	 * PUBLIC METHODS SETTINGS & UI
	 */

	/**
	 * Get CDN status.
	 *
	 * @since 3.0
	 */
	public function get_status() {
		return $this->cdn_active;
	}

	/**
	 * Add setting names to the appropriate group.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function add_settings() {
		return array(
			'auto_resize',
			'webp',
		);
	}

	/**
	 * Add settings to settings array.
	 *
	 * @since 3.0
	 *
	 * @param array $settings  Current settings array.
	 *
	 * @return array
	 */
	public function register( $settings ) {
		return array_merge(
			$settings,
			array(
				'auto_resize' => array(
					'label'       => __( 'Enable automatic resizing of my images', 'wp-smushit' ),
					'short_label' => __( 'Automatic Resizing', 'wp-smushit' ),
					'desc'        => __( 'If your images don’t match their containers, we’ll automatically serve a correctly sized image.', 'wp-smushit' ),
				),
				'webp'        => array(
					'label'       => __( 'Enable WebP conversion', 'wp-smushit' ),
					'short_label' => __( 'WebP conversion', 'wp-smushit' ),
					'desc'        => __( 'Smush can automatically convert and serve your images as WebP to compatible browsers.', 'wp-smushit' ),
				),
			)
		);
	}

	/**
	 * Show additional descriptions for settings.
	 *
	 * @since 3.0
	 *
	 * @param string $setting_key Setting key.
	 */
	public function settings_desc( $setting_key = '' ) {
		if ( empty( $setting_key ) || ! in_array( $setting_key, array( 'webp', 'auto_resize' ), true ) ) {
			return;
		}
		?>
		<span class="sui-description sui-toggle-description" id="<?php echo esc_attr( WP_SMUSH_PREFIX . $setting_key . '-desc' ); ?>">
			<?php
			switch ( $setting_key ) {
				case 'webp':
					esc_html_e(
						'Note: We’ll detect and serve WebP images to browsers that will accept them by checking
						Accept Headers, and gracefully fall back to normal PNGs or JPEGs for non-compatible browsers.',
						'wp-smushit'
					);
					break;
				case 'auto_resize':
					esc_html_e(
						'Having trouble with Google PageSpeeds ‘compress and resize’ suggestion? This feature
						will fix this without any coding needed! Note: No resizing is done on your actual images, only
						what is served from the CDN - so your original images will remain untouched.',
						'wp-smushit'
					);
					break;
				default:
					break;
			}
			?>
		</span>
		<?php
	}

	/**
	 * Add CDN stats to stats meta box.
	 *
	 * @since 3.0
	 */
	public function cdn_stats_ui() {
		// Only show the UI box if CDN module is enabled and has some data.
		if ( ! $this->settings->get( 'cdn' ) || ! $this->status ) {
			return;
		}

		$plan      = isset( $this->status->bandwidth_plan ) ? $this->status->bandwidth_plan : 10;
		$bandwidth = isset( $this->status->bandwidth ) ? $this->status->bandwidth : 0;

		$percentage = round( $plan * $bandwidth / 1024 / 1024 / 1024 );
		if ( $percentage > 100 ) {
			$percentage = 100;
		}
		?>
		<li class="smush-cdn-stats">
			<span class="sui-list-label"><?php esc_html_e( 'CDN', 'wp-smushit' ); ?></span>
			<span class="wp-smush-stats sui-list-detail">
				<i class="sui-icon-loader sui-loading sui-hidden" aria-hidden="true" title="<?php esc_attr_e( 'Updating Stats', 'wp-smushit' ); ?>"></i>
				<?php if ( 100 === $percentage ) : ?>
					<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="<?php esc_attr_e( 'You have exceed your 30 day bandwidth allowance. The CDN is currently inactive until you upgrade your plan', 'wp-smushit' ); ?>">
						<i class="sui-icon-warning-alert sui-error sui-md" aria-hidden="true"></i>
					</span>
				<?php endif; ?>
				<span class="wp-smush-cdn-stats"><?php echo esc_html( WP_Smush_Helper::format_bytes( $bandwidth, 2 ) ); ?></span>
				<span class="wp-smush-stats-sep">/</span>
				<span class="wp-smush-cdn-usage">
					<?php echo absint( $plan ); ?> GB
				</span>
				<div class="sui-circle-score <?php echo 100 === $percentage ? 'sui-grade-f' : ''; ?>" data-score="<?php echo absint( $percentage ); ?>"></div>
			</span>
		</li>
		<?php
	}

	/**
	 * Initialize required flags.
	 */
	public function init_flags() {
		// All these are members only feature.
		if ( ! WP_Smush::is_pro() ) {
			return;
		}

		// CDN will not work if site is not registered with the dashboard.
		if ( ! file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' ) ) {
			return;
		}

		$this->status = $this->settings->get_setting( WP_SMUSH_PREFIX . 'cdn_status' );

		// CDN is not enabled and not active.
		if ( ! $this->status ) {
			return;
		}

		$this->cdn_active = isset( $this->status->cdn_enabled ) && $this->status->cdn_enabled;
	}

	/**
	 * Set the API base for the member.
	 */
	public function set_cdn_url() {
		$site_id = absint( $this->status->site_id );

		$this->cdn_base = trailingslashit( "https://{$this->status->endpoint_url}/{$site_id}" );
	}

	/**
	 * Add CDN url to header for better speed.
	 *
	 * @since 3.0
	 *
	 * @param array  $urls URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed.
	 *
	 * @return array
	 */
	public function dns_prefetch( $urls, $relation_type ) {
		// Add only if CDN active.
		if ( 'dns-prefetch' === $relation_type && $this->cdn_active && ! empty( $this->cdn_base ) ) {
			$urls[] = $this->cdn_base;
		}

		return $urls;
	}

	/**
	 * Generate CDN url from given image url.
	 *
	 * @since 3.0
	 *
	 * @param string $src Image url.
	 * @param array  $args Query parameters.
	 *
	 * @return string
	 */
	public function generate_cdn_url( $src, $args = array() ) {
		// Do not continue in case we try this when cdn is disabled.
		if ( ! $this->cdn_active ) {
			return $src;
		}

		// Support for WP installs in subdirectories: remove the site url and leave only the file path.
		$path = str_replace( get_site_url(), '', $src );

		// Parse url to get all parts.
		$url_parts = wp_parse_url( $path );

		// If path not found, do not continue.
		if ( empty( $url_parts['path'] ) ) {
			return $src;
		}

		// Arguments for CDN.
		$pro_args = array(
			'lossy' => $this->settings->get( 'lossy' ) ? 1 : 0,
			'strip' => $this->settings->get( 'strip_exif' ) ? 1 : 0,
			'webp'  => $this->settings->get( 'webp' ) ? 1 : 0,
		);

		$args = wp_parse_args( $pro_args, $args );

		// Replace base url with cdn base.
		$url = $this->cdn_base . ltrim( $url_parts['path'], '/' );

		// Now we need to add our CDN parameters for resizing.
		$url = add_query_arg( $args, $url );

		return $url;
	}

	/**************************************
	 *
	 * PUBLIC METHODS CDN
	 *
	 * @see process_buffer()
	 * @see process_img_tags()
	 * @see update_image_srcset()
	 * @see update_image_sizes()
	 * @see update_cdn_image_src_args()
	 * @see process_cdn_status()
	 * @see cron_update_stats()
	 * @see unschedule_cron()
	 * @see schedule_cron()
	 */

	/**
	 * Starts an output buffer and register the callback function.
	 *
	 * Register callback function that adds attachment ids of images
	 * those are from media library and has an attachment id.
	 *
	 * @since 3.0
	 *
	 * @uses ob_start()
	 */
	public function process_buffer() {
		ob_start( array( $this, 'process_img_tags' ) );
	}

	/**
	 * Process images from current buffer content.
	 *
	 * Use DOMDocument class to find all available images
	 * in current HTML content and set attachmet id attribute.
	 *
	 * @since 3.0
	 *
	 * @param string $content Current buffer content.
	 *
	 * @return string
	 */
	public function process_img_tags( $content ) {
		if ( empty( $content ) || ! $this->cdn_active ) {
			return $content;
		}

		$images = $this->get_images_from_content( $content );

		if ( empty( $images ) ) {
			return $content;
		}

		foreach ( $images[0] as $key => $image ) {
			$src = $images['img_url'][ $key ];

			/**
			 * Filter to skip a single image from cdn.
			 *
			 * @param bool       $skip     Should skip? Default: false.
			 * @param string     $img_url  Image url.
			 * @param array|bool $image    Image tag or false.
			 */
			if ( apply_filters( 'smush_skip_image_from_cdn', false, $src, $image ) ) {
				continue;
			}

			// Make sure this image is inside a supported directory. Try to convert to valid path.
			if ( ! $src = $this->is_supported_path( $src ) ) {
				continue;
			}

			// Store the original $src to be used later on.
			$original_src = $src;

			/**
			 * Filter hook to alter image src arguments before going through cdn.
			 *
			 * @param array  $args   Arguments.
			 * @param string $src    Image src.
			 * @param string $image  Image tag.
			 */
			$args = apply_filters( 'smush_image_cdn_args', array(), $image );

			/**
			 * Filter hook to alter image src before going through cdn.
			 *
			 * @param string $src    Image src.
			 * @param string $image  Image tag.
			 */
			$src = apply_filters( 'smush_image_src_before_cdn', $src, $image );

			// Generate cdn url from local url.
			$src = $this->generate_cdn_url( $src, $args );

			/**
			 * Filter hook to alter image src after replacing with CDN base.
			 *
			 * @param string $src    Image src.
			 * @param string $image  Image tag.
			 */
			$src = apply_filters( 'smush_image_src_after_cdn', $src, $image );

			$new_image = $image;
			if ( ! empty( $images['img_url'][ $key ] ) ) {
				$new_image = preg_replace( '#(src=["|\'])' . $images['img_url'][ $key ] . '(["|\'])#i', '\1' . $src . '\2', $new_image, 1 );
			}

			// See if srcset is already set.
			if ( ! preg_match( '/srcset=["|\']([^"|\']+)["|\']/i', $images[0][ $key ] ) && $this->settings->get( 'auto_resize' ) ) {
				list( $srcset, $sizes ) = $this->generate_srcset( $original_src );

				$this->add_attribute( $new_image, 'srcset', $srcset );

				if ( false !== $sizes ) {
					$this->add_attribute( $new_image, 'sizes', $sizes );
				}
			}

			/**
			 * Filter hook to alter image tag before replacing the image in content.
			 *
			 * @param string $image  Image tag.
			 */
			$new_image = apply_filters( 'smush_cdn_image_tag', $new_image );

			$content = str_replace( $image, $new_image, $content );
		}

		return $content;
	}

	/**
	 * Filters an array of image srcset values, replacing each URL with resized CDN urls.
	 *
	 * Keep the existing srcset sizes if already added by WP, then calculate extra sizes
	 * if required.
	 *
	 * @since 3.0
	 *
	 * @param array  $sources        One or more arrays of source data to include in the 'srcset'.
	 * @param array  $size_array     Array of width and height values in pixels.
	 * @param string $image_src      The 'src' of the image.
	 * @param array  $image_meta     The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id  Image attachment ID or 0.
	 *
	 * @return array $sources
	 */
	public function update_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id = 0 ) {
		if ( ! is_array( $sources ) ) {
			return $sources;
		}

		$main_image_url = false;

		// Try to get image URL from attachment ID.
		if ( empty( $attachment_id ) ) {
			$url = $main_image_url = wp_get_attachment_url( $attachment_id );
		}

		foreach ( $sources as $i => $source ) {
			if ( ! $this->is_valid_url( $source['url'] ) ) {
				continue;
			}

			if ( apply_filters( 'smush_cdn_skip_image', false, $source['url'], $source ) ) {
				continue;
			}

			list( $width, $height ) = $this->get_size_from_file_name( $source['url'] );

			// The file already has a resized version as a thumbnail.
			if ( 'w' === $source['descriptor'] && $width === $source['value'] ) {
				$sources[ $i ]['url'] = $this->generate_cdn_url( $source['url'] );
				continue;
			}

			// If don't have attachment id, get original image by removing dimensions from url.
			if ( empty( $url ) ) {
				$url = $this->get_url_without_dimensions( $source['url'] );
			}

			$args = array();
			// If we got size from url, add them.
			if ( ! empty( $width ) && ! empty( $height ) ) {
				// Set size arg.
				$args = array(
					'size' => "{$width}x{$height}",
				);
			}

			// Replace with CDN url.
			$sources[ $i ]['url'] = $this->generate_cdn_url( $url, $args );
		}

		// Set additional sizes if required.
		if ( $this->settings->get( 'auto_resize' ) ) {
			$sources = $this->set_additional_srcset( $sources, $size_array, $main_image_url, $image_meta, $image_src );

			// Make it look good.
			ksort( $sources );
		}

		return $sources;
	}

	/**
	 * Update image sizes for responsive size.
	 *
	 * @since 3.0
	 *
	 * @param string $sizes A source size value for use in a 'sizes' attribute.
	 * @param array  $size  Requested size.
	 *
	 * @return string
	 */
	public function update_image_sizes( $sizes, $size ) {
		// Get maximum content width.
		$content_width = $this->max_content_width();

		if ( is_array( $size ) && $size[0] < $content_width ) {
			return $sizes;
		}

		return sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $content_width );
	}

	/**
	 * Add resize arguments to content image src.
	 *
	 * @since 3.0
	 *
	 * @param array  $args  Current arguments.
	 * @param object $image Image tag object from DOM.
	 *
	 * @return array $args
	 */
	public function update_cdn_image_src_args( $args, $image ) {
		// Don't need to auto resize - return default args.
		if ( ! $this->settings->get( 'auto_resize' ) ) {
			return $args;
		}

		// Get registered image sizes.
		$image_sizes = $this->get_image_sizes();

		// Find the width and height attributes.
		$width  = false;
		$height = false;
		wp_is_mobile();
		// Try to get the width and height from img tag.
		if ( preg_match( '/width=["|\']?(\b[[:digit:]]+(?!\%)\b)["|\']?/i', $image, $width_string ) ) {
			$width = $width_string[1];
		}

		if ( preg_match( '/height=["|\']?(\b[[:digit:]]+(?!\%)\b)["|\']?/i', $image, $height_string ) ) {
			$height = $height_string[1];
		}

		$size = array();

		// Detect WP registered image size from HTML class.
		if ( preg_match( '/size-([^"\'\s]+)[^"\']*["|\']?/i', $image, $size ) ) {
			$size = array_pop( $size );

			if ( ! array_key_exists( $size, $image_sizes ) ) {
				return $args;
			}

			// This is probably a correctly sized thumbnail - no need to resize.
			if ( (int) $width === $image_sizes[ $size ]['width'] || (int) $height === $image_sizes[ $size ]['height'] ) {
				return $args;
			}

			// If this size exists in registered sizes, add argument.
			if ( 'full' !== $size ) {
				$args['size'] = (int) $image_sizes[ $size ]['width'] . 'x' . (int) $image_sizes[ $size ]['height'];
			}
		} else {
			// It's not a registered thumbnail size.
			if ( $width && $height ) {
				$args['size'] = (int) $width . 'x' . (int) $height;
			}
		}

		return $args;
	}

	/**
	 * Process CDN status.
	 *
	 * @since 3.0
	 * @since 3.1  Moved from Ajax class.
	 *
	 * @param string $status  Status in JSON format.
	 *
	 * @return mixed
	 */
	public function process_cdn_status( $status ) {
		if ( is_wp_error( $status ) ) {
			wp_send_json_error(
				array(
					'message' => $status->get_error_message(),
				)
			);
		}


		$status = json_decode( $status['body'] );

		// Error from API.
		if ( ! $status->success ) {
			wp_send_json_error(
				array(
					'message' => $status->data->message,
				),
				$status->data->error_code
			);
		}

		return $status->data;
	}

	/**
	 * Update CDN stats (daily) cron task.
	 *
	 * @since 3.1.0
	 */
	public function cron_update_stats() {
		$current_status = $this->settings->get_setting( WP_SMUSH_PREFIX . 'cdn_status' );

		if ( isset( $current_status->cdn_enabling ) && $current_status->cdn_enabling ) {
			$status = WP_Smush::get_instance()->api()->enable();
		} else {
			$status = WP_Smush::get_instance()->api()->check();
		}

		$data = $this->process_cdn_status( $status );
		$this->settings->set_setting( WP_SMUSH_PREFIX . 'cdn_status', $data );
	}

	/**
	 * Disable CDN stats update cron task.
	 *
	 * @since 3.1.0
	 */
	public function unschedule_cron() {
		$timestamp = wp_next_scheduled( 'smush_update_cdn_stats' );
		wp_unschedule_event( $timestamp, 'smush_update_cdn_stats' );
	}

	/**
	 * Set cron task to update CDN stats daily.
	 *
	 * @since 3.1.0
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( 'smush_update_cdn_stats' ) ) {
			wp_schedule_event( time(), 'daily', 'smush_update_cdn_stats' );
		}
	}

	/**************************************
	 *
	 * PRIVATE METHODS
	 *
	 * Functions that are used by the public methods of this CDN class.
	 *
	 * @since 3.0.0:
	 *
	 * @see is_valid_url()
	 * @see get_size_from_file_name()
	 * @see get_url_without_dimensions()
	 * @see max_content_width()
	 * @see set_additional_srcset()
	 * @see get_image_sizes()
	 * @see generate_srcset()
	 * @see maybe_generate_srcset()
	 * @see is_supported_path()
	 *
	 * @since 3.1.0:
	 *
	 * @see get_images_from_content()
	 * @see add_attribute()
	 */

	/**
	 * Check if we can use the image URL in CDN.
	 *
	 * @since 3.0
	 *
	 * @param string $url  Image URL.
	 *
	 * @return bool
	 */
	private function is_valid_url( $url ) {
		$parsed_url = wp_parse_url( $url );

		if ( ! $parsed_url ) {
			return false;
		}

		// No host or path found.
		if ( ! isset( $parsed_url['host'] ) || ! isset( $parsed_url['path'] ) ) {
			return false;
		}

		// If not supported extension - return false.
		if ( ! in_array( strtolower( pathinfo( $parsed_url['path'], PATHINFO_EXTENSION ) ), $this->supported_extensions, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Try to determine height and width from strings WP appends to resized image filenames.
	 *
	 * @since 3.0
	 *
	 * @param string $src The image URL.
	 *
	 * @return array An array consisting of width and height.
	 */
	private function get_size_from_file_name( $src ) {
		$size = array();

		if ( preg_match( '/(\d+)x(\d+)\.(?:' . implode( '|', $this->supported_extensions ) . '){1}$/i', $src, $size ) ) {
			// Get size and width.
			$width  = (int) $size[1];
			$height = (int) $size[2];

			// Handle retina images.
			if ( strpos( $src, '@2x' ) ) {
				$width  = 2 * $width;
				$height = 2 * $height;
			}

			// Return width and height as array.
			if ( $width && $height ) {
				return array( $width, $height );
			}
		}

		return array( false, false );
	}

	/**
	 * Get full size image url from resized one.
	 *
	 * @since 3.0
	 *
	 * @param string $src Image URL.
	 *
	 * @return string
	 */
	private function get_url_without_dimensions( $src ) {
		if ( ! preg_match( '/(-\d+x\d+)\.(' . implode( '|', $this->supported_extensions ) . '){1}(?:\?.+)?$/i', $src, $src_parts ) ) {
			return $src;
		}

		// Remove WP's resize string to get the original image.
		$original_src = str_replace( $src_parts[1], '', $src );

		// Upload directory.
		$upload_dir = wp_get_upload_dir();

		// Extracts the file path to the image minus the base url.
		$file_path = substr( $original_src, strlen( $upload_dir['baseurl'] ) );

		// Continue only if the file exists.
		if ( file_exists( $upload_dir['basedir'] . $file_path ) ) {
			return $original_src;
		}

		// Revert to source if file does not exist.
		return $src;
	}

	/**
	 * Get $content_width global var value.
	 *
	 * @since 3.0
	 *
	 * @return bool|string
	 */
	private function max_content_width() {
		// Get global content width (if content width is empty, set 1900).
		$content_width = isset( $GLOBALS['content_width'] ) ? $GLOBALS['content_width'] : 1900;

		// Check to see if we are resizing the images (can not go over that value).
		$resize_sizes = $this->settings->get_setting( WP_SMUSH_PREFIX . 'resize_sizes' );

		if ( isset( $resize_sizes['width'] ) && $resize_sizes['width'] < $content_width ) {
			return $resize_sizes['width'];
		}

		return $content_width;
	}

	/**
	 * Filters an array of image srcset values, and add additional values.
	 *
	 * @since 3.0
	 *
	 * @param array  $sources    An array of image urls and widths.
	 * @param array  $size_array Array of width and height values in pixels.
	 * @param string $url        Image URL.
	 * @param array  $image_meta The image metadata.
	 * @param string $image_src  The src of the image.
	 *
	 * @return array $sources
	 */
	private function set_additional_srcset( $sources, $size_array, $url, $image_meta, $image_src = '' ) {
		$content_width = $this->max_content_width();

		// If url is empty, try to get from src.
		if ( empty( $url ) ) {
			$url = $this->get_url_without_dimensions( $image_src );
		}

		// We need to add additional dimensions.
		$full_width     = $image_meta['width'];
		$full_height    = $image_meta['height'];
		$current_width  = $size_array[0];
		$current_height = $size_array[1];
		// Get width and height calculated by WP.
		list( $constrained_width, $constrained_height ) = wp_constrain_dimensions( $full_width, $full_height, $current_width, $current_height );

		// Calculate base width.
		// If $constrained_width sizes are smaller than current size, set maximum content width.
		if ( abs( $constrained_width - $current_width ) <= 1 && abs( $constrained_height - $current_height ) <= 1 ) {
			$base_width = $content_width;
		} else {
			$base_width = $current_width;
		}

		$current_widths = array_keys( $sources );
		$new_sources    = array();

		/**
		 * Filter to add/update/bypass additional srcsets.
		 *
		 * If empty value or false is retured, additional srcset
		 * will not be generated.
		 *
		 * @param array|bool $additional_multipliers Additional multipliers.
		 */
		$additional_multipliers = apply_filters(
			'smush_srcset_additional_multipliers',
			array(
				0.2,
				0.4,
				0.6,
				0.8,
				1,
				2,
				3,
			)
		);

		// Continue only if additional multipliers found or not skipped.
		// Filter already documented in class-wp-smush-cdn.php.
		if ( apply_filters( 'smush_skip_image_from_cdn', false, $url, false ) || empty( $additional_multipliers ) ) {
			return $sources;
		}

		// Loop through each multipliers and generate image.
		foreach ( $additional_multipliers as $multiplier ) {
			// New width by multiplying with original size.
			$new_width = intval( $base_width * $multiplier );
			// If a nearly sized image already exist, skip.
			foreach ( $current_widths as $_width ) {
				if ( abs( $_width - $new_width ) < 50 || ( $new_width > $full_width ) ) {
					continue 2;
				}
			}

			// We need the width as well...
			$dimensions = wp_constrain_dimensions( $current_width, $current_height, $new_width );

			// Arguments for cdn url.
			$args = array(
				'size' => "{$new_width}x{$dimensions[1]}",
			);

			// Add new srcset item.
			$new_sources[ $new_width ] = array(
				'url'        => $this->generate_cdn_url( $url, $args ),
				'descriptor' => 'w',
				'value'      => $new_width,
			);
		}

		// Assign new srcset items to existing ones.
		if ( ! empty( $new_sources ) ) {
			// Loop through each items and replace/add.
			foreach ( $new_sources as $_width_key => $_width_values ) {
				$sources[ $_width_key ] = $_width_values;
			}
		}

		return $sources;
	}

	/**
	 * Get registered image sizes and its sizes.
	 *
	 * Custom function to get all registered image sizes
	 * and their width and height.
	 *
	 * @since 3.0
	 *
	 * @return array|bool|mixed
	 */
	private function get_image_sizes() {
		// Get from cache if available to avoid duplicate looping.
		$sizes = wp_cache_get( 'get_image_sizes', 'smush_image_sizes' );
		if ( $sizes ) {
			return $sizes;
		}

		// Get additional sizes registered by themes.
		global $_wp_additional_image_sizes;

		$sizes = array();

		// Get intermediate image sizes.
		$get_intermediate_image_sizes = get_intermediate_image_sizes();

		// Create the full array with sizes and crop info.
		foreach ( $get_intermediate_image_sizes as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ), true ) ) {
				$sizes[ $_size ]['width']  = get_option( $_size . '_size_w' );
				$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
				$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}

		// Set cache to avoid this loop next time.
		wp_cache_set( 'get_image_sizes', $sizes, 'smush_image_sizes' );

		return $sizes;
	}

	/**
	 * Try to generate the srcset for the image.
	 *
	 * @since 3.0
	 *
	 * @param string $src  Image source.
	 *
	 * @return array|bool
	 */
	private function generate_srcset( $src ) {
		// Try to get the attachment URL.
		$attachment_id = attachment_url_to_postid( $src );

		// Try to get width and height from image.
		if ( $attachment_id ) {
			list( $src, $width, $height ) = wp_get_attachment_image_src( $attachment_id, 'full' );

			// Revolution slider fix: images will always return 0 height and 0 width.
			if ( 0 === $width && 0 === $height ) {
				// Try to get the dimensions directly from the file.
				list( $width, $height ) = getimagesize( $src );
			}

			$image_meta = wp_get_attachment_metadata( $attachment_id );
		} else {
			// Try to get the dimensions directly from the file.
			list( $width, $height ) = getimagesize( $src );

			$image_meta = array(
				'width'  => $width,
				'height' => $height,
			);
		}

		$size_array = array( absint( $width ), absint( $height ) );
		$srcset     = wp_calculate_image_srcset( $size_array, $src, $image_meta, $attachment_id );

		/**
		 * In some rare cases, the wp_calculate_image_srcset() will not generate any srcset, because there are
		 * not image sizes defined. If that is the case, try to revert to our custom maybe_generate_srcset() to
		 * generate the srcset string.
		 *
		 * Also srcset will not be generated for images that are not part of the media library (no $attachment_id).
		 */
		if ( ! $srcset ) {
			$srcset = $this->maybe_generate_srcset( $width, $height, $src, $image_meta );
		}

		$sizes = wp_calculate_image_sizes( $size_array, $src, $image_meta, $attachment_id );

		return array( $srcset, $sizes );
	}

	/**
	 * Try to generate srcset.
	 *
	 * @since 3.0
	 *
	 * @param int    $width   Attachment width.
	 * @param int    $height  Attachment height.
	 * @param string $src     Image source.
	 * @param array  $meta    Image meta.
	 *
	 * @return string
	 */
	private function maybe_generate_srcset( $width, $height, $src, $meta ) {
		$sources[ $width ] = array(
			'url'        => $this->generate_cdn_url( $src ),
			'descriptor' => 'w',
			'value'      => $width,
		);

		$sources = $this->set_additional_srcset(
			$sources,
			array( absint( $width ), absint( $height ) ),
			$src,
			$meta
		);

		$srcset = '';
		foreach ( $sources as $source ) {
			$srcset .= str_replace( ' ', '%20', $source['url'] ) . ' ' . $source['value'] . $source['descriptor'] . ', ';
		}

		return $srcset;
	}

	/**
	 * Check if the image path is supported by the CDN.
	 *
	 * @since 3.0
	 *
	 * @param string $src  Image path.
	 *
	 * @return bool|string
	 */
	private function is_supported_path( $src ) {
		$url_parts = wp_parse_url( $src );

		// Unsupported scheme.
		if ( isset( $url_parts['scheme'] ) && 'http' !== $url_parts['scheme'] && 'https' !== $url_parts['scheme'] ) {
			return false;
		}

		// This is a relative path, try to get the URL.
		if ( ! isset( $url_parts['host'] ) && ! isset( $url_parts['scheme'] ) ) {
			$src = site_url( $src );
		}

		$mapped_domain = $this->check_mapped_domain();

		// URL does not belong to the site or a site mapped domain.
		if ( false === strpos( $src, content_url() ) || ( is_multisite() && false === strpos( $src, $mapped_domain ) ) ) {
			return false;
		}

		// Allow only these extensions in CDN.
		$ext = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'gif', 'jpg', 'jpeg', 'png' ), true ) ) {
			return false;
		}

		return $src;
	}

	/**
	 * Support for domain mapping plugin.
	 *
	 * @since 3.1.1
	 */
	private function check_mapped_domain() {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( ! defined( 'DOMAINMAP_BASEFILE' ) ) {
			return false;
		}

		$domain = wp_cache_get( 'smush_mapped_site_domain', 'smush' );

		if ( ! $domain ) {
			global $wpdb;

			$domain = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT domain FROM {$wpdb->base_prefix}domain_mapping WHERE blog_id = %d ORDER BY id ASC LIMIT 1",
					get_current_blog_id()
				)
			); // Db call ok.

			if ( null !== $domain ) {
				wp_cache_add( 'smush_mapped_site_domain', $domain, 'smush' );
			}
		}

		return $domain;
	}

}
