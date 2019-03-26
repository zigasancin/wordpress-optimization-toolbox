<?php
/**
 * Database class.
 *
 * @package WP_Smush
 * @subpackage Admin
 * @version 2.3
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_DB
 */
class WP_Smush_DB {

	/**
	 * WP_Smush_DB constructor.
	 */
	public function __construct() {
		// Recalculate resize savings.
		add_action( 'wp_smush_image_resized', array( $this, 'resize_savings' ) );

		// Update Conversion savings.
		add_action( 'wp_smush_png_jpg_converted', array( $this, 'conversion_savings' ) );
	}

	/**
	 * Filter the Posts object as per mime type.
	 *
	 * @param array $posts Object of Posts.
	 *
	 * @return mixed array of post ids
	 */
	private function filter_by_mime( $posts ) {
		if ( empty( $posts ) ) {
			return $posts;
		}

		foreach ( $posts as $post_k => $post ) {
			if ( ! isset( $post->post_mime_type ) || ! in_array( $post->post_mime_type, WP_Smush_Core::$mime_types, true ) ) {
				unset( $posts[ $post_k ] );
			} else {
				$posts[ $post_k ] = $post->ID;
			}
		}

		return $posts;
	}

	/**
	 * Limit for all the queries
	 *
	 * @return int|mixed
	 */
	public function query_limit() {
		$limit = apply_filters( 'wp_smush_query_limit', 2000 );
		$limit = ! empty( $this->total_count ) && $limit > $this->total_count ? $this->total_count : $limit;
		$limit = intval( $limit );

		return $limit;
	}

	/**
	 * Filter the number of results fetched at once for NextGen queries
	 *
	 * @return int|mixed
	 */
	public function nextgen_query_limit() {
		$limit = apply_filters( 'wp_smush_nextgen_query_limit', 1000 );
		$limit = intval( $limit );

		return $limit;
	}

	/**
	 * Fetch all the unsmushed attachments
	 *
	 * @return array $attachments
	 */
	public function get_unsmushed_attachments() {
		if ( isset( $_REQUEST['ids'] ) ) {
			return array_map( 'intval', explode( ',', $_REQUEST['ids'] ) );
		}

		/** Do not fetch more than this, any time
		Localizing all rows at once increases the page load and slows down everything */
		$r_limit = apply_filters( 'wp_smush_max_rows', 5000 );

		// Check if we can get the unsmushed attachments from the other two variables.
		if ( ! empty( WP_Smush::get_instance()->core()->attachments ) && ! empty( WP_Smush::get_instance()->core()->smushed_attachments ) ) {
			$unsmushed_posts = array_diff( WP_Smush::get_instance()->core()->attachments, WP_Smush::get_instance()->core()->smushed_attachments );

			// Remove skipped attachments.
			if ( ! empty( WP_Smush::get_instance()->core()->smushed_attachments ) ) {
				$unsmushed_posts = array_diff( $unsmushed_posts, WP_Smush::get_instance()->core()->skipped_attachments );
			}

			$unsmushed_posts = ! empty( $unsmushed_posts ) && is_array( $unsmushed_posts ) ? array_slice( $unsmushed_posts, 0, $r_limit ) : array();
		} else {
			$limit = $this->query_limit();

			$get_posts       = true;
			$unsmushed_posts = array();
			$args            = array(
				'fields'                 => array( 'ids', 'post_mime_type' ),
				'post_type'              => 'attachment',
				'post_status'            => 'any',
				'orderby'                => 'ID',
				'order'                  => 'DESC',
				'posts_per_page'         => $limit,
				'offset'                 => 0,
				'meta_query'             => array(
					array(
						'key'     => WP_Smushit::$smushed_meta_key,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'wp-smush-ignore-bulk',
						'value'   => 'true',
						'compare' => 'NOT EXISTS',
					),
				),
				'update_post_term_cache' => false,
				'no_found_rows'          => true,
			);

			// Loop Over to get all the attachments.
			while ( $get_posts ) {
				// Remove the Filters added by WP Media Folder.
				$this->remove_filters();

				$query = new WP_Query( $args );

				if ( ! empty( $query->post_count ) && count( $query->posts ) > 0 ) {
					// Get a filtered list of post ids.
					$posts = $this->filter_by_mime( $query->posts );

					// Merge the results.
					$unsmushed_posts = array_merge( $unsmushed_posts, $posts );

					// Update the offset.
					$args['offset'] += $limit;
				} else {
					// If we didn't get any posts from query, set $get_posts to false.
					$get_posts = false;
				}

				// If we already got enough posts.
				if ( count( $unsmushed_posts ) >= $r_limit ) {
					$get_posts = false;
				} elseif ( ! empty( WP_Smush::get_instance()->core()->total_count ) && WP_Smush::get_instance()->core()->total_count <= $args['offset'] ) {
					// If total Count is set, and it is alread lesser than offset, don't query.
					$get_posts = false;
				}
			}
		}

		// Remove resmush list from unsmushed images.
		if ( ! empty( WP_Smush::get_instance()->core()->resmush_ids ) && is_array( WP_Smush::get_instance()->core()->resmush_ids ) ) {
			$unsmushed_posts = array_diff( $unsmushed_posts, WP_Smush::get_instance()->core()->resmush_ids );
		}

		return $unsmushed_posts;
	}

	/**
	 * Total image count.
	 *
	 * @param bool $force_update  Force update.
	 *
	 * @return bool|int|mixed
	 */
	public function total_count( $force_update = false ) {
		// Retrieve from Cache.
		if ( ! $force_update && $count = wp_cache_get( 'total_count', 'wp-smush' ) ) {
			if ( $count ) {
				return $count;
			}
		}

		// Set Attachment IDs, and total count.
		$posts = $this->get_media_attachments( '', $force_update );

		WP_Smush::get_instance()->core()->attachments = $posts;

		// Get total count from attachments.
		$total_count = ! empty( $posts ) && is_array( $posts ) ? count( $posts ) : 0;

		// Set total count.
		WP_Smush::get_instance()->core()->total_count = $total_count;

		wp_cache_add( 'total_count', $total_count, 'wp-smush' );

		// Send the count.
		return $total_count;
	}

	/**
	 * Get the media attachment ID/count.
	 *
	 * @param bool $return_count  Return count.
	 * @param bool $force_update  Force update.
	 *
	 * @return array|bool|int|mixed
	 */
	public function get_media_attachments( $return_count = false, $force_update = false ) {
		global $wpdb;

		// Return results from cache.
		if ( ! $force_update ) {
			$posts = wp_cache_get( 'media_attachments', 'wp-smush' );
			$count = ! empty( $posts ) ? count( $posts ) : 0;

			// Return results only if we've got any.
			if ( $count ) {
				return $return_count ? $count : $posts;
			}
		}

		$posts = array();

		// Else Get it Fresh!!
		$offset = 0;
		$limit  = $this->query_limit();
		$mime   = implode( "', '", WP_Smush_Core::$mime_types );
		// Remove the Filters added by WP Media Folder.
		$this->remove_filters();

		$get_posts = true;

		while ( $get_posts ) {
			$results = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_mime_type IN ('$mime') ORDER BY `ID` DESC LIMIT %d, %d",
					$offset,
					$limit
				)
			); // Db call ok.
			if ( ! empty( $results ) && is_array( $results ) && count( $results ) > 0 ) {

				// Get a filtered list of post ids.
				$posts = array_merge( $posts, $results );

				// Update the offset.
				$offset += $limit;
			} else {
				// If we didn't get any posts from query, set $get_posts to false.
				$get_posts = false;
			}
		}

		// Add the attachments to cache.
		wp_cache_add( 'media_attachments', $posts, 'wp-smushit' );

		if ( $return_count ) {
			return count( $posts );
		}

		return $posts;
	}

	/**
	 * Optimised images count or IDs.
	 *
	 * @param bool $return_ids Should return ids?.
	 * @param bool $force_update Should force update?.
	 *
	 * @return array|int
	 */
	public function smushed_count( $return_ids = false, $force_update = false ) {
		global $wpdb;

		// Don't query again, if the variable is already set.
		if ( ! $return_ids && ! empty( WP_Smush::get_instance()->core()->smushed_count ) && WP_Smush::get_instance()->core()->smushed_count > 0 ) {
			return WP_Smush::get_instance()->core()->smushed_count;
		}

		// Key for cache.
		$key = $return_ids ? WP_SMUSH_PREFIX . 'smushed_ids' : WP_SMUSH_PREFIX . 'smushed_count';

		// If not forced to update, try to get from cache.
		if ( ! $force_update ) {
			// TODO: This is an issue. If not forcing the update, the cached version is never incremented during image Smush.
			$smushed_count = wp_cache_get( $key, 'wp-smush' );
			// Return the cache value if cache is set.
			if ( false !== $smushed_count && ! empty( $smushed_count ) ) {
				return $smushed_count;
			}
		}

		/**
		 * Allows to set a limit of mysql query
		 * Default value is 2000.
		 */
		$limit      = $this->query_limit();
		$offset     = 0;
		$query_next = true;

		$posts = array();

		// Remove the Filters added by WP Media Folder.
		$this->remove_filters();
		while ( $query_next && $results = $wpdb->get_col( $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key=%s ORDER BY `post_id` DESC LIMIT $offset, $limit", WP_Smushit::$smushed_meta_key ) ) ) {
			if ( ! is_wp_error( $results ) && count( $results ) > 0 ) {
				$posts = array_merge( $posts, $results );
			}
			// Update the offset.
			$offset += $limit;

			// Compare the Offset value to total images.
			if ( ! empty( WP_Smush::get_instance()->core()->total_count ) && WP_Smush::get_instance()->core()->total_count <= $offset ) {
				$query_next = false;
			}
		}

		// Remove resmush IDs from the list.
		if ( ! empty( WP_Smush::get_instance()->core()->resmush_ids ) && is_array( WP_Smush::get_instance()->core()->resmush_ids ) ) {
			$posts = array_diff( $posts, WP_Smush::get_instance()->core()->resmush_ids );
		}

		// Set in cache.
		wp_cache_set( $key, $return_ids ? $posts : count( $posts ), 'wp-smush' );

		return $return_ids ? $posts : count( $posts );
	}

	/**
	 * Return the number of skipped attachments.
	 *
	 * @since 3.0
	 *
	 * @param bool $force  Force data refresh.
	 *
	 * @return array
	 */
	public function skipped_count( $force ) {
		if ( ! $force && $images = wp_cache_get( 'skipped_images', 'wp-smush' ) ) {
			return $images;
		}

		global $wpdb;
		$images = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='wp-smush-ignore-bulk'" ); // Db call ok.
		wp_cache_set( 'skipped_images', $images, 'wp-smush' );

		return $images;
	}

	/**
	 * Returns/Updates the number of images Super Smushed.
	 *
	 * @param string $type media/nextgen, Type of images to get/set the super smushed count for.
	 *
	 * @param array  $attachments Optional, By default Media attachments will be fetched.
	 *
	 * @return array|mixed
	 *
	 * @todo Refactor Method, Separate Media Library and Nextgen, moreover nextgen functionality is broken
	 */
	public function super_smushed_count( $type = 'media', $attachments = array() ) {
		if ( 'media' === $type ) {
			$count = $this->get_super_smushed_attachments();
		} else {
			$key = 'wp-smush-super_smushed_nextgen';

			// Clear up the stats, if there are no images.
			if ( method_exists( 'WP_Smush_Nextgen_Stats', 'total_count' ) && 0 == WP_Smush_Nextgen_Stats::total_count() ) {
				delete_option( $key );
			}

			// Flag to check if we need to re-evaluate the count.
			$revaluate = false;

			$super_smushed = get_option( $key, false );

			// Check if need to revalidate.
			if ( ! $super_smushed || empty( $super_smushed ) || empty( $super_smushed['ids'] ) ) {
				$super_smushed = array(
					'ids' => array(),
				);

				$revaluate = true;
			} else {
				$last_checked = $super_smushed['timestamp'];

				$diff = $last_checked - current_time( 'timestamp' );

				// Difference in hour.
				$diff_h = $diff / 3600;

				// if last checked was more than 1 hours.
				if ( $diff_h > 1 ) {
					$revaluate = true;
				}
			}
			// Do not reevaluate stats if nextgen attachments are not provided.
			if ( 'nextgen' === $type && empty( $attachments ) && $revaluate ) {
				$revaluate = false;
			}

			// Need to scan all the image.
			if ( $revaluate ) {
				// Get all the Smushed attachments ids
				// Note: Wrong Method called, it'll fetch media images and not NextGen images
				// Should be $attachments, in place of $super_smushed_images.
				$super_smushed_images = $this->get_super_smushed_attachments( true );

				if ( ! empty( $super_smushed_images ) && is_array( $super_smushed_images ) ) {
					// Iterate over all the attachments to check if it's already there in list, else add it.
					foreach ( $super_smushed_images as $id ) {
						if ( ! in_array( $id, $super_smushed['ids'] ) ) {
							$super_smushed['ids'][] = $id;
						}
					}
				}

				$super_smushed['timestamp'] = current_time( 'timestamp' );

				update_option( $key, $super_smushed, false );
			}

			$count = ! empty( $super_smushed['ids'] ) ? count( $super_smushed['ids'] ) : 0;
		}

		return $count;
	}

	/**
	 * Updates the Meta for existing smushed images and retrieves the count of Super Smushed images
	 *
	 * @param bool $return_ids Whether to return ids or just the count.
	 *
	 * @return array|int Super Smushed images Id / Count of Super Smushed images
	 */
	private function get_super_smushed_attachments( $return_ids = false ) {
		// Get all the attachments with wp-smush-lossy.
		$limit         = $this->query_limit();
		$get_posts     = true;
		$super_smushed = array();
		$args          = array(
			'fields'                 => array( 'ids', 'post_mime_type' ),
			'post_type'              => 'attachment',
			'post_status'            => 'any',
			'orderby'                => 'ID',
			'order'                  => 'DESC',
			'posts_per_page'         => $limit,
			'offset'                 => 0,
			'meta_query'             => array(
				array(
					'key'   => 'wp-smush-lossy',
					'value' => 1,
				),
			),
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
		);
		// Loop over to get all the attachments.
		while ( $get_posts ) {
			// Remove the filters added by WP Media folder.
			$this->remove_filters();

			$query = new WP_Query( $args );

			if ( ! empty( $query->post_count ) && count( $query->posts ) > 0 ) {
				$posts = $this->filter_by_mime( $query->posts );
				// Merge the results.
				$super_smushed = array_merge( $super_smushed, $posts );

				// Update the offset.
				$args['offset'] += $limit;
			} else {
				// If we didn't get any posts from query, set $get_posts to false.
				$get_posts = false;
			}
			// If total Count is set, and it is alread lesser than offset, don't query.
			if ( ! empty( WP_Smush::get_instance()->core()->total_count ) && WP_Smush::get_instance()->core()->total_count <= $args['offset'] ) {
				$get_posts = false;
			}
		}
		// Remove resmush IDs from the list.
		if ( ! empty( WP_Smush::get_instance()->core()->resmush_ids ) && is_array( WP_Smush::get_instance()->core()->resmush_ids ) ) {
			$super_smushed = array_diff( $super_smushed, WP_Smush::get_instance()->core()->resmush_ids );
		}

		return $return_ids ? $super_smushed : count( $super_smushed );
	}

	/**
	 * Remove any pre_get_posts_filters added by WP Media Folder plugin
	 */
	private function remove_filters() {
		// Remove any filters added b WP media Folder plugin to get the all attachments.
		if ( class_exists( 'Wp_Media_Folder' ) ) {
			global $wp_media_folder;
			if ( is_object( $wp_media_folder ) ) {
				remove_filter( 'pre_get_posts', array( $wp_media_folder, 'wpmf_pre_get_posts1' ) );
				remove_filter( 'pre_get_posts', array( $wp_media_folder, 'wpmf_pre_get_posts' ), 0, 1 );
			}
		}

		global $wpml_query_filter;

		// If WPML is not installed, return.
		if ( ! is_object( $wpml_query_filter ) ) {
			return;
		}

		// Remove language filter and let all the images be smushed at once.
		if ( has_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ) ) ) {
			remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10, 2 );
			remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10, 2 );
		}
	}

	/**
	 * Get the savings from image resizing, And force update if set to true.
	 *
	 * @param bool $force_update , Whether to Re-Calculate all the stats or not.
	 * @param bool $format Format the Bytes in readable format.
	 * @param bool $return_count Return the resized image count, Set to false by default.
	 *
	 * @return array|bool|mixed|string Array of {
	 *      'bytes',
	 *      'before_size',
	 *      'after_size'
	 * }
	 */
	public function resize_savings( $force_update = true, $format = false, $return_count = false ) {
		$savings = '';

		if ( ! $force_update ) {
			$savings = wp_cache_get( WP_SMUSH_PREFIX . 'resize_savings', 'wp-smush' );
		}

		$count = wp_cache_get( WP_SMUSH_PREFIX . 'resize_count', 'wp-smush' );

		// If resize image count is not stored in db, recalculate.
		if ( $return_count && false === $count ) {
			$count        = 0;
			$force_update = true;
		}

		// If nothing in cache, calculate it.
		if ( empty( $savings ) || $force_update ) {
			$savings = array(
				'bytes'       => 0,
				'size_before' => 0,
				'size_after'  => 0,
			);

			$limit      = $this->query_limit();
			$offset     = 0;
			$query_next = true;

			global $wpdb;

			while ( $query_next ) {
				$resize_data = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key=%s LIMIT %d, %d",
						WP_SMUSH_PREFIX . 'resize_savings',
						$offset,
						$limit
					)
				); // Db call ok.

				if ( ! empty( $resize_data ) ) {
					foreach ( $resize_data as $data ) {
						// Skip resmush ids.
						if ( ! empty( WP_Smush::get_instance()->core()->resmush_ids ) && in_array( $data->post_id, WP_Smush::get_instance()->core()->resmush_ids ) ) {
							continue;
						}
						if ( ! empty( $data ) ) {
							$meta = maybe_unserialize( $data->meta_value );
							if ( ! empty( $meta ) && ! empty( $meta['bytes'] ) ) {
								$savings['bytes']       += $meta['bytes'];
								$savings['size_before'] += $meta['size_before'];
								$savings['size_after']  += $meta['size_after'];
							}
						}
						$count++;
					}
				}
				// Update the offset.
				$offset += $limit;

				// Compare the offset value to total images.
				if ( ! empty( WP_Smush::get_instance()->core()->total_count ) && WP_Smush::get_instance()->core()->total_count <= $offset ) {
					$query_next = false;
				} elseif ( ! $resize_data ) {
					// If we didn't get any results.
					$query_next = false;
				}
			}

			if ( $format ) {
				$savings['bytes'] = size_format( $savings['bytes'], 1 );
			}

			wp_cache_set( WP_SMUSH_PREFIX . 'resize_savings', $savings, 'wp-smush' );
			wp_cache_set( WP_SMUSH_PREFIX . 'resize_count', $count, 'wp-smush' );
		}

		return $return_count ? $count : $savings;
	}

	/**
	 * Return/Update PNG -> JPG Conversion savings
	 *
	 * @param bool $force_update  Whether to force update the conversion savings or not.
	 * @param bool $format        Optionally return formatted savings.
	 *
	 * @return array Savings
	 */
	public function conversion_savings( $force_update = true, $format = false ) {
		$savings = '';

		if ( ! $force_update ) {
			$savings = wp_cache_get( WP_SMUSH_PREFIX . 'pngjpg_savings', 'wp-smush' );
		}

		// If nothing in cache, calculate it.
		if ( empty( $savings ) || $force_update ) {
			$savings = array(
				'bytes'       => 0,
				'size_before' => 0,
				'size_after'  => 0,
			);

			$limit      = $this->query_limit();
			$offset     = 0;
			$query_next = true;

			global $wpdb;

			while ( $query_next ) {
				$conversion_savings = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key=%s LIMIT %d, %d",
						WP_SMUSH_PREFIX . 'pngjpg_savings',
						$offset,
						$limit
					)
				); // Db call ok.

				if ( ! empty( $conversion_savings ) ) {
					foreach ( $conversion_savings as $data ) {
						// Skip resmush IDs.
						if ( ! empty( WP_Smush::get_instance()->core()->resmush_ids ) && in_array( $data->post_id, WP_Smush::get_instance()->core()->resmush_ids ) ) {
							continue;
						}

						if ( ! empty( $data ) ) {
							$meta = maybe_unserialize( $data->meta_value );

							if ( is_array( $meta ) ) {
								foreach ( $meta as $size ) {
									if ( ! empty( $size ) && is_array( $size ) ) {
										$savings['bytes']       += $size['bytes'];
										$savings['size_before'] += $size['size_before'];
										$savings['size_after']  += $size['size_after'];
									}
								}
							}
						}
					}
				}
				// Update the offset.
				$offset += $limit;

				// Compare the Offset value to total images.
				if ( ! empty( WP_Smush::get_instance()->core()->total_count ) && WP_Smush::get_instance()->core()->total_count < $offset ) {
					$query_next = false;
				} elseif ( ! $conversion_savings ) {
					// If we didn' got any results.
					$query_next = false;
				}
			}

			if ( $format ) {
				$savings['bytes'] = size_format( $savings['bytes'], 1 );
			}

			wp_cache_set( WP_SMUSH_PREFIX . 'pngjpg_savings', $savings, 'wp-smush' );
		}

		return $savings;
	}

	/**
	 * Get the savings for the given set of attachments
	 *
	 * @param array $attachments  Array of attachment IDs.
	 *
	 * @return array Stats
	 *  array(
	 * 'size_before' => 0,
	 * 'size_after'    => 0,
	 * 'savings_resize' => 0,
	 * 'savings_conversion' => 0
	 *  )
	 */
	public function get_stats_for_attachments( $attachments = array() ) {
		$stats = array(
			'size_before'        => 0,
			'size_after'         => 0,
			'savings_resize'     => 0,
			'savings_conversion' => 0,
			'count_images'       => 0,
			'count_supersmushed' => 0,
			'count_smushed'      => 0,
			'count_resize'       => 0,
			'count_remaining'    => 0,
		);

		// If we don't have any attachments, return empty array.
		if ( empty( $attachments ) || ! is_array( $attachments ) ) {
			return $stats;
		}

		// Loop over all the attachments to get the cummulative savings.
		foreach ( $attachments as $attachment ) {
			$smush_stats        = get_post_meta( $attachment, WP_Smushit::$smushed_meta_key, true );
			$resize_savings     = get_post_meta( $attachment, WP_SMUSH_PREFIX . 'resize_savings', true );
			$conversion_savings = WP_Smush_Helper::get_pngjpg_savings( $attachment );

			if ( ! empty( $smush_stats['stats'] ) ) {
				// Combine all the stats, and keep the resize and send conversion settings separately.
				$stats['size_before'] += ! empty( $smush_stats['stats']['size_before'] ) ? $smush_stats['stats']['size_before'] : 0;
				$stats['size_after']  += ! empty( $smush_stats['stats']['size_after'] ) ? $smush_stats['stats']['size_after'] : 0;
			}

			$stats['count_images']       += ! empty( $smush_stats['sizes'] ) && is_array( $smush_stats['sizes'] ) ? count( $smush_stats['sizes'] ) : 0;
			$stats['count_supersmushed'] += ! empty( $smush_stats['stats'] ) && $smush_stats['stats']['lossy'] ? 1 : 0;

			// Add resize saving stats.
			if ( ! empty( $resize_savings ) ) {
				// Add resize and conversion savings.
				$stats['savings_resize'] += ! empty( $resize_savings['bytes'] ) ? $resize_savings['bytes'] : 0;
				$stats['size_before']    += ! empty( $resize_savings['size_before'] ) ? $resize_savings['size_before'] : 0;
				$stats['size_after']     += ! empty( $resize_savings['size_after'] ) ? $resize_savings['size_after'] : 0;
				$stats['count_resize']   += 1;
			}

			// Add conversion saving stats.
			if ( ! empty( $conversion_savings ) ) {
				// Add resize and conversion savings.
				$stats['savings_conversion'] += ! empty( $conversion_savings['bytes'] ) ? $conversion_savings['bytes'] : 0;
				$stats['size_before']        += ! empty( $conversion_savings['size_before'] ) ? $conversion_savings['size_before'] : 0;
				$stats['size_after']         += ! empty( $conversion_savings['size_after'] ) ? $conversion_savings['size_after'] : 0;
			}
			$stats['count_smushed'] += 1;
		}

		return $stats;
	}

}
