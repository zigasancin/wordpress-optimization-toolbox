<?php
/**
 * @copyright 2017  Cloudways  https://www.cloudways.com
 *
 *  Original development of this plugin by JoomUnited https://www.joomunited.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Breeze_PurgeCache {

	/**
	 * Temporary page/post/cpt ID list of already cache cleared.
	 */
	private array $cleared_extra_items = array();

	public function set_action() {
		add_action( 'pre_post_update', array( $this, 'purge_post_on_update' ), 10, 1 );
		add_action( 'save_post', array( $this, 'purge_post_on_update' ), 10, 1 );
		add_action( 'save_post', array( $this, 'purge_post_on_update_content' ), 9, 3 );

		add_action( 'edited_term', array( $this, 'purge_term_on_update' ), 9, 3 );
		add_action( 'wp_trash_post', array( $this, 'purge_post_on_update' ), 10, 1 );
		add_action( 'wp_trash_post', array( $this, 'purge_post_on_trash' ), 9, 1 );
		add_action( 'comment_post', array( $this, 'purge_post_on_new_comment' ), 10, 3 );
		add_action( 'wp_set_comment_status', array( $this, 'purge_post_on_comment_status_change' ), 10, 2 );
		add_action( 'spammed_comment', array( $this, 'purge_post_on_comment_status_change' ), 10, 2 );
		add_action( 'trashed_comment', array( $this, 'purge_post_on_comment_status_change' ), 10, 2 );
		add_action( 'edit_comment', array( $this, 'purge_post_on_comment_status_change' ), 10, 2 );
		add_action( 'set_comment_cookies', array( $this, 'set_comment_cookie_exceptions' ), 10, 2 );

		add_action( 'switch_theme', array( &$this, 'clear_local_cache_on_switch' ), 9, 3 );
		add_action( 'customize_save_after', array( &$this, 'clear_customizer_cache' ), 11, 1 );
	}

	/**
	 * Detects pages with the latest comments block and clears their cache.
	 *
	 * This method scans the content of published posts to find pages that contain
	 * the latest comments block (`<!-- wp:latest-comments -->`). If such pages are found,
	 * it purges their Cloudflare cache and removes local cache files.
	 *
	 * @return void
	 */
	private function detect_comments_page_clear_cache(): void {
		global $wpdb;

		$query = "SELECT ID
			FROM `$wpdb->posts`
			WHERE post_content LIKE '%<!-- wp:latest-comments %>'
			AND post_status = 'publish'";

		$results = $wpdb->get_results( $query ); // phpcs:ignore

		$pages_list = array();

		if ( $results ) {
			foreach ( $results as $result ) {
				$page_id       = $result->ID;
				$get_permalink = get_permalink( $page_id );
				if ( false !== $get_permalink && ! in_array( $page_id, $this->cleared_extra_items, true ) ) {
					$pages_list[]                = $get_permalink;
					$this->cleared_extra_items[] = $page_id;
				}
			}
		}

		if ( ! empty( $pages_list ) ) {
			// CLear Cloudflare, if enabled.
			Breeze_CloudFlare_Helper::purge_cloudflare_cache_urls( $pages_list );

			// Remove local cache file.
			foreach ( $pages_list as $url_path ) {
				$this->clear_local_cache_for_urls( array( $url_path ) );

				$main     = new Breeze_PurgeVarnish();
				$item_url = untrailingslashit( $url_path ) . '/?breeze';
				$main->purge_cache( $item_url );
			}
		}

	}

	/**
	 * When customizer settings are saved ( Publish button is clicked ), clear all cache.
	 *
	 * @param $element
	 *
	 * @return void
	 */
	public function clear_customizer_cache( $element ) {

		do_action( 'breeze_clear_all_cache' );
	}

	/**
	 * Clear local cache on theme switch.
	 *
	 * @param $new_name
	 * @param $new_theme
	 * @param $old_theme
	 *
	 * @return void
	 */
	public function clear_local_cache_on_switch( $new_name, $new_theme, $old_theme ) {
		//delete minify
		Breeze_MinificationCache::clear_minification();
		//clear normal cache
		Breeze_PurgeCache::breeze_cache_flush( true, true, true );
		//do_action( 'breeze_clear_all_cache' );
	}

	/**
	 * When user posts a comment, set a cookie so we don't show them page cache
	 *
	 * @param WP_Comment $comment
	 * @param WP_User $user
	 *
	 * @since  1.3
	 */
	public function set_comment_cookie_exceptions( $comment, $user ) {
		// File based caching only
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-active' ) ) ) {

			$post_id = $comment->comment_post_ID;

			setcookie( 'breeze_commented_posts[' . $post_id . ']', parse_url( get_permalink( $post_id ), PHP_URL_PATH ), ( time() + HOUR_IN_SECONDS * 24 * 7 ) );
		}
	}

	//    Automatically purge all file based page cache on post changes
	public function purge_post_on_update( $post_id ) {

		$post_type = get_post_type( $post_id );

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'revision' === $post_type ) {
			return;
		} elseif ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) ) {
			return;
		}

		$do_cache_reset = true;
		if ( 'tribe_events' === $post_type ) {
			$do_cache_reset = false;
		}
		$clear_wp_cache = true;

		if ( true === self::is_pro_plugin_ob_cache_enabled() ) {
			$clear_wp_cache = false;
		}

		if ( did_action( 'edit_post' ) ) {
			return;
		}

		// File based caching only
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-active' ) ) ) {
			self::breeze_cache_flush( $do_cache_reset, $clear_wp_cache );
		}
	}

	/**
	 * Purges cache related to a term when it is updated.
	 *
	 * This method handles clearing object cache and file-based caching mechanisms
	 * for the specified term. It ensures cache consistency across term updates
	 * based on plugin configuration and active caching mechanisms.
	 *
	 * @param int $term_id The ID of the term being updated.
	 * @param int $tt_id The term taxonomy ID associated with the term.
	 * @param string $taxonomy The taxonomy name of the term being updated.
	 *
	 * @return void
	 */
	public function purge_term_on_update( int $term_id, int $tt_id, string $taxonomy ) {

		$clear_wp_cache = true;
		if ( true === self::is_pro_plugin_ob_cache_enabled() ) {
			$clear_wp_cache = false;
		}

		// File based caching only
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-active' ) ) ) {
			self::clear_op_cache_for_terms( $term_id, $tt_id, $taxonomy );
			self::breeze_cache_flush( true, $clear_wp_cache );
		}
	}

	/**
	 * Purge Cloudflare data on post/page/cpt update.
	 *
	 * @param int $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool $update Whether this is an existing post being updated.
	 *
	 * @return void
	 * @access public
	 * @since 2.0.15
	 */
	public function purge_post_on_update_content( int $post_id, WP_Post $post, bool $update ) {
		if ( true === $update ) {

			$post_type = get_post_type( $post_id );

			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'revision' === $post_type ) {
				return;
			} elseif ( ! current_user_can( 'edit_post', $post_id ) && ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) ) {
				return;
			}

			$list_of_urls = $this->collect_urls_for_cache_purge( $post_id );

			if ( ! empty( $list_of_urls ) ) {
				// Purge local cache for the URLs list.
				$this->clear_local_cache_for_urls( $list_of_urls );
				// Purge CF cache.
				Breeze_CloudFlare_Helper::purge_cloudflare_cache_urls( $list_of_urls );
			}
		}

	}

	/**
	 * Clear cloudflare cache on post/page/cpt delete action.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function purge_post_on_trash( int $post_id ) {
		$list_of_urls = $this->collect_urls_for_cache_purge( $post_id );

		if ( ! empty( $list_of_urls ) ) {
			// Purge local cache for the URLs list.
			$this->clear_local_cache_for_urls( $list_of_urls );
			// Purge CF cache.
			Breeze_CloudFlare_Helper::purge_cloudflare_cache_urls( $list_of_urls );
		}

	}

	private function collect_urls_for_cache_purge( $post_id ): array {

		if ( false === get_permalink( $post_id ) ) {
			return array();
		}
		// Reset CloudFlare cache.
		$get_permalink = get_permalink( $post_id );
		// On delete action, the permalink has "__trashed" added to the permalink.
		// We need to remove that.
		$get_permalink  = str_replace( '__trashed', '', $get_permalink );
		$list_of_urls   = array();
		$list_of_urls[] = $get_permalink;

		$noarchive_post_type = array( 'post', 'page' );
		$this_post_status    = get_post_status( $post_id );
		$this_post_type      = get_post_type( $post_id );
		$rest_api_route      = 'wp/v2';
		$valid_post_status   = array( 'publish', 'private', 'trash' );

		$post_type_object = get_post_type_object( $this_post_type );
		if ( isset( $post_type_object->rest_base ) && ! empty( $post_type_object->rest_base ) ) {
			$rest_permalink = get_rest_url() . $rest_api_route . '/' . $post_type_object->rest_base . '/' . $post_id . '/';
		} elseif ( 'post' === $this_post_type ) {
			$rest_permalink = get_rest_url() . $rest_api_route . '/posts/' . $post_id . '/';
		} elseif ( 'page' === $this_post_type ) {
			$rest_permalink = get_rest_url() . $rest_api_route . '/views/' . $post_id . '/';
		}
		if ( isset( $rest_permalink ) ) {
			$list_of_urls[] = $rest_permalink;
		}

		// Add in AMP permalink if Automattic's AMP is installed
		if ( function_exists( 'amp_get_permalink' ) ) {
			$list_of_urls[] = amp_get_permalink( $post_id );
			// Regular AMP url for posts
			$list_of_urls[] = get_permalink( $post_id ) . 'amp/';
		}
		if ( 'trash' === $this_post_status ) {
			$list_of_urls[] = $get_permalink . 'feed/';
		}

		$author_id = get_post_field( 'post_author', $post_id );
		array_push(
			$list_of_urls,
			get_author_posts_url( $author_id ),
			get_author_feed_link( $author_id ),
			get_rest_url() . $rest_api_route . '/users/' . $author_id . '/'
		);

		$categories = get_the_category( $post_id );
		if ( $categories ) {
			foreach ( $categories as $cat ) {
				$category_link        = get_category_link( $cat->term_id );
				$category_link_no_cat = str_replace( 'category/', '', $category_link );
				if ( ! empty( $category_link ) && $category_link !== $category_link_no_cat ) {
					array_push( $list_of_urls, $category_link_no_cat );
				}

				array_push(
					$list_of_urls,
					$category_link,
					get_rest_url() . $rest_api_route . '/categories/' . $cat->term_id . '/'
				);
				$category_link = '';
			}
		}

		$tags = get_the_tags( $post_id );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				array_push(
					$list_of_urls,
					get_tag_link( $tag->term_id ),
					get_rest_url() . $rest_api_route . '/tags/' . $tag->term_id . '/'
				);
			}
		}

		// Archives and their feeds
		if ( $this_post_type && ! in_array( $this_post_type, $noarchive_post_type, true ) ) {
			$get_archive_link = get_post_type_archive_link( get_post_type( $post_id ) );

			$get_archive_feed_link = get_post_type_archive_feed_link( get_post_type( $post_id ) );
			if ( ! empty( $get_archive_link ) ) {
				$list_of_urls[] = $get_archive_link;
			}
			if ( ! empty( $get_archive_feed_link ) ) {
				$list_of_urls[] = $get_archive_feed_link;
			}
		}

		// Feeds
		array_push(
			$list_of_urls,
			get_bloginfo_rss( 'rdf_url' ),
			get_bloginfo_rss( 'rss_url' ),
			get_bloginfo_rss( 'rss2_url' ),
			get_bloginfo_rss( 'atom_url' ),
			get_bloginfo_rss( 'comments_rss2_url' ),
			get_post_comments_feed_link( $post_id )
		);
		// Home Pages and (if used) posts page
		array_push(
			$list_of_urls,
			get_rest_url(),
		);
		if ( 'page' === get_option( 'show_on_front' ) ) {
			// Ensure we have a page_for_posts setting to avoid empty URL
			if ( get_option( 'page_for_posts' ) ) {
				$list_of_urls[] = get_permalink( get_option( 'page_for_posts' ) );
			}
		}

		// Trim all URLs in the list to ensure clean output
		$list_of_urls = array_map( 'trim', $list_of_urls );

		$homepage = trailingslashit( home_url() );
		if ( ! in_array( $homepage, $list_of_urls, true ) ) {
			// Clear the cache for homepage
			array_push(
				$list_of_urls,
				$homepage
			);
		}

		return $list_of_urls;
	}

	/**
	 * Clears the local cache for the specified URLs.
	 *
	 * @param array $list_of_urls An array of URLs for which the local cache should be cleared.
	 *
	 * @return void
	 */
	private function clear_local_cache_for_urls( array $list_of_urls ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! empty( $list_of_urls ) ) {
			foreach ( $list_of_urls as $local_url ) {
				if ( $wp_filesystem->exists( breeze_get_cache_base_path() . hash( 'sha512', $local_url ) ) ) {
					$wp_filesystem->rmdir( breeze_get_cache_base_path() . hash( 'sha512', $local_url ), true );
				}
			}
		}
	}

	/**
	 * Purges the cache for a post associated with a newly approved comment.
	 *
	 * This method is triggered when a new comment is added and approved, handling cache
	 * clearing for the related post using file-based caching and Cloudflare integration.
	 *
	 * @param int $comment_ID The ID of the new comment.
	 * @param int|string $approved Approval status of the comment. Non-zero value indicates approval.
	 * @param array $commentdata An array of comment data including the ID of the associated post.
	 *
	 * @return void
	 */
	public function purge_post_on_new_comment( $comment_ID, $approved, $commentdata ) {
		if ( 1 !== $approved ) {
			return;
		}
		// File based caching only
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-active' ) ) ) {
			$post_id = $commentdata['comment_post_ID'];

			Breeze_CloudFlare_Helper::purge_cloudflare_cache_urls( array( get_permalink( $post_id ) ) );

			$url_path = get_permalink( $post_id );
			// Purge local cache for the URLs list.
			$this->clear_local_cache_for_urls( array( $url_path ) );

			$this->detect_comments_page_clear_cache();
			$this->clear_op_cache_for_comments( $comment_ID );
		}
	}

	/**
	 * Purges the post associated with a comment when the comment's status changes.
	 *
	 * This method is triggered to handle cache clearing for posts related to comments,
	 * based on file-based caching settings and configurations specific to Breeze.
	 *
	 * @param int $comment_ID The ID of the comment whose status has changed.
	 * @param mixed $comment_status The new status of the comment.
	 *
	 * @return void
	 */
	public function purge_post_on_comment_status_change( int $comment_ID, $comment_status ) {
		// File based caching only
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'breeze-active' ) ) ) {
			$comment = get_comment( $comment_ID );
			if ( ! empty( $comment ) ) {
				$post_id = $comment->comment_post_ID;

				$url_path = get_permalink( $post_id );
				Breeze_CloudFlare_Helper::purge_cloudflare_cache_urls( array( $url_path ) );
				$this->clear_local_cache_for_urls( array( $url_path ) );
			}

			$this->detect_comments_page_clear_cache();
			$this->clear_op_cache_for_comments( $comment_ID );
		}
	}

	/**
	 * Clear Breeze & WordPress Cache
	 *
	 * @param $flush_cache
	 * @param $clear_ocp
	 *
	 * @return void
	 */
	public static function breeze_cache_flush( $flush_cache = true, $clear_ocp = true, $purge_all_html_folder = false ) {
		global $post;

		$post_id = null;
		if ( $post instanceof WP_Post ) {
			$post_id = $post->ID;
		}

		if ( true === Breeze_CloudFlare_Helper::is_log_enabled() ) {
			error_log( '######### PURGE LOCAL CACHE HTML ###: ' . var_export( 'true', true ) );
		}

		if ( true === $purge_all_html_folder ) {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$cache_path = breeze_get_cache_base_path( is_network_admin() );
			$wp_filesystem->rmdir( untrailingslashit( $cache_path ), true );
		}

		if ( true === $flush_cache && ! empty( $post ) && is_object( $post ) ) {
			$post_type = get_post_type( $post->ID );

			$flush_cache         = true;
			$ignore_object_cache = array(
				'tribe_events',
				'shop_order',
			);
			if ( in_array( $post_type, $ignore_object_cache, true ) ) {
				$flush_cache = false;
			}
		}

		if ( true === $flush_cache && isset( $_GET['post_type'] ) && 'tribe_events' === $_GET['post_type'] ) {
			$flush_cache = false;
		}

		if ( ! empty( $post_id ) && true === $flush_cache && true === $clear_ocp ) {
			if ( true === Breeze_CloudFlare_Helper::is_log_enabled() ) {
				error_log( '######### PURGE OBJECT CACHE ###: ' . var_export( 'true', true ) );
			}
			self::clear_op_cache_for_posts( $post_id );
		}
	}

	//delete file for clean up

	public function clean_up() {

		global $wp_filesystem;
		$file = untrailingslashit( WP_CONTENT_DIR ) . '/advanced-cache.php';

		$ret = true;

		if ( ! $wp_filesystem->delete( $file ) ) {
			$ret = false;
		}

		$folder = untrailingslashit( breeze_get_cache_base_path() );

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			$ret = false;
		}

		return $ret;
	}

	/**
	 * Return an instance of the current class, create one if it doesn't exist
	 * @return object
	 * @since  1.0
	 */
	public static function factory() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
			$instance->set_action();
		}

		return $instance;
	}


	/**
	 * Flushes the entire object cache for the current context.
	 *
	 * This method resets the object cache, clearing all stored data. When executed
	 * on a network admin screen, it will handle any additional logic specific to
	 * network context (if required). Otherwise, it flushes the cache for the current
	 * instance or site.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function __flush_object_cache() {
		set_as_network_screen();

		if ( is_network_admin() ) {
			// in case we need to add something specific for network.
			return wp_cache_flush();
		}

		return wp_cache_flush();
	}

	/**
	 * Clears the object cache associated with a specific post or custom post type.
	 *
	 * This method removes cached data for the given post ID, including post metadata
	 * and any related term cache associated with the post.
	 *
	 * @param int $object_id The ID of the post or custom post type for which the cache should be cleared.
	 *
	 * @return void
	 */
	public static function clear_op_cache_for_posts( int $object_id ) {
		if ( true === self::is_pro_plugin_ob_cache_enabled() ) {
			return;
		}

		// POST/PAGE/CPT
		// $object_id is $post_id
		wp_cache_delete( $object_id, 'posts' );
		wp_cache_delete( $object_id, 'post_meta' ); // If needed
		wp_cache_delete( 'post_terms_' . $object_id, 'terms' ); //for term related to post

	}

	/**
	 * Clears the object cache related to specific terms and their taxonomy.
	 *
	 * This method removes cached data for the given term, term taxonomy, and associated taxonomy
	 * data, ensuring that outdated cached information is invalidated and updated.
	 *
	 * @param int $object_id The ID of the term for which the cache should be cleared.
	 * @param int $tt_id The term taxonomy ID associated with the term.
	 * @param string $taxonomy The taxonomy name related to the term.
	 *
	 * @return void
	 */
	private static function clear_op_cache_for_terms( int $object_id, int $tt_id, string $taxonomy ) {
		if ( true === self::is_pro_plugin_ob_cache_enabled() ) {
			return;
		}

		// TAXONOMY
		// $object_id is term_id
		wp_cache_delete( $object_id, 'terms' );
		wp_cache_delete( $tt_id, 'term_taxonomy' );
		#delete_transient( 'all_terms' ); //for all terms, if needed
		$tax_cache_key = "{$taxonomy}_terms"; //dynamic based on current taxonomy
		wp_cache_delete( $tax_cache_key, 'terms' ); //clear cache for taxonomy

	}

	/**
	 * Clears the object cache related to a specific comment and its associated post.
	 *
	 * This method removes cached data for the given comment, resets queries related
	 * to comments, clears the comment count cache, and clears the cache for the post
	 * associated with the given comment.
	 *
	 * @param int $comment_id The ID of the comment for which the cache should be cleared.
	 *
	 * @return void
	 */
	private static function clear_op_cache_for_comments( int $comment_id ) {
		if ( true === self::is_pro_plugin_ob_cache_enabled() ) {
			return;
		}
		error_log( '$comment_id: ' . var_export( $comment_id, true ) );
		wp_cache_delete( $comment_id, 'comment' );
		wp_cache_delete( 'comment_query_1_' . get_the_ID(), 'comment' );
		wp_cache_delete( 'get_comments_by_post_id_' . get_the_ID(), 'comment' );
		//Clear all comments count cache, as it has changed
		wp_cache_delete( 'comments-per-page', 'counts' );

		// Clear the cache for the post the comment belongs to
		$post_id = get_comment( $comment_id )->comment_post_ID;
		wp_cache_delete( $post_id, 'posts' );
		wp_cache_delete( $post_id, 'post_meta' ); // If needed
	}

	/**
	 * Checks if object cache support is enabled by a Pro plugin.
	 *
	 * This method verifies the presence of object caching functionality typically provided
	 * by Pro-level caching plugins, such as Redis Cache Pro or similar plugins.
	 *
	 * @return bool Returns true if a Pro plugin enabling object cache is detected, otherwise false.
	 */
	private static function is_pro_plugin_ob_cache_enabled(): bool {
		// If Redis Pro is found, leave the object cache clear to it.
		if ( defined( 'RedisCachePro\Version' ) || defined( 'WP_REDIS_VERSION' ) ) {
			return true;
		}

		// if the function does not exist, block the calling of it.
		if ( ! function_exists( 'wp_cache_delete' ) ) {
			return true;
		}
		// Go ahead and clear the object cache.
		return false;

	}

}

$breeze_basic_settings = Breeze_Options_Reader::get_option_value( 'breeze-active' );

if ( isset( $breeze_basic_settings ) && $breeze_basic_settings ) {
	Breeze_PurgeCache::factory();
}
