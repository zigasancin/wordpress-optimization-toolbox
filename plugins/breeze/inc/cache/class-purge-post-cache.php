<?php
/**
 * This class handles the functionality of clearing the cache for
 * individual posts or pages.
 */
class Purge_Post_Cache {

	/**
	 * Constructor method for initializing the class.
	 */
	public function __construct() {
		$this->hooks();
	}

	public function hooks() {
		add_filter( 'post_row_actions', array( $this, 'clear_cache_option' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'clear_cache_option' ), 10, 2 );
		add_action( 'post_action_clear-breeze-cache', array( $this, 'process_query' ) );
		add_action( 'admin_notices', array( $this, 'post_cache_cleared_notification' ) );
		add_filter(
			'removable_query_args',
			function ( $args ) {
				return array_merge(
					$args,
					array( 'breeze_post_cache' )
				);
			},
		);
	}

	public function post_cache_cleared_notification() {
		if ( ! is_admin() || ! isset( $_GET['breeze_post_cache'] ) || 'cleared' !== $_GET['breeze_post_cache'] ) {
			return;
		}
		?>
			<div class="notice notice-success is-dismissible breeze-notice">
					<p><?php esc_html_e( 'Cache has been purged.', 'breeze' ); ?></p>
			</div>
		<?php
	}

	/**
	 * Adds a 'Clear Cache' option to the row actions of posts or pages.
	 *
	 * @param array   $actions Current actions available for the post/page.
	 * @param WP_Post $post    The post object.
	 * @return array Modified actions array with the 'Clear Cache' option.
	 */
	public function clear_cache_option( $actions, $post ) {
		$url         = $this->clear_cache_action_url( $post );
		$clear_cache = array( 'clear-cache' => "<a href='$url'>Clear Cache</a>" );

		$actions = array_merge( $clear_cache, $actions );

		return $actions;
	}

	/**
	 * Generates the URL for clearing the cache of a specific post or page.
	 *
	 * @param WP_Post $post The post object.
	 * @return string URL for clearing the cache of the specified post/page.
	 */
	public function clear_cache_action_url( $post ) {
		$post_type_object = get_post_type_object( $post->post_type );
		$url              = wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&action=clear-breeze-cache', $post->ID ) ), 'clear-cache-post_' . $post->ID );
		return $url;
	}

	/**
	 * Processes the cache clearing query for a specific post or page.
	 *
	 * @param int $post_id The ID of the post/page to clear cache for.
	 */
	public function process_query( $post_id ) {

		check_admin_referer( 'clear-cache-post_' . $post_id );

		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$url_path = get_permalink( $post_id );

		$the_blog_id = 0;
		if ( breeze_does_inherit_settings() ) {
			// load advanced cache file.
			include_once WP_CONTENT_DIR . '/advanced-cache.php';

			if ( function_exists( 'breeze_fetch_configuration_data' ) ) {
				$the_blog_id = $this->get_blog_id();
			} else {
				$breeze_config = include_once WP_CONTENT_DIR . '/breeze-config/breeze-config.php';
				$the_blog_id   = isset( $breeze_config['blog_id'] ) ? $breeze_config['blog_id'] : 0;
			}
		} elseif ( is_multisite() ) {
			// load advanced cache file.
			include_once WP_CONTENT_DIR . '/advanced-cache.php';
			$the_blog_id = $this->get_blog_id();
		}

		$cache_base_path = breeze_get_cache_base_path( false, $the_blog_id );

		if ( $wp_filesystem->exists( $cache_base_path . hash( 'sha512', $url_path ) ) ) {
			$wp_filesystem->rmdir( $cache_base_path . hash( 'sha512', $url_path ), true );
		}
		// Clear the varnish cache.
		do_action( 'breeze_clear_varnish' );
		wp_redirect(
			add_query_arg(
				array(
					'breeze_post_cache' => 'cleared',
				),
				$this->sendback_url( $post_id )
			)
		);

		Breeze_PurgeCache::clear_op_cache_for_posts($post_id);
		exit;
	}

	public function get_blog_id() {
		$domain = strtolower( stripslashes( $_SERVER['HTTP_HOST'] ) );
		if ( substr( $domain, -3 ) == ':80' ) {
			$domain = substr( $domain, 0, -3 );
		} elseif ( substr( $domain, -4 ) == ':443' ) {
			$domain = substr( $domain, 0, -4 );
		}
		list( $path ) = explode( '?', stripslashes( $_SERVER['REQUEST_URI'] ) );
		$path_parts   = explode( '/', rtrim( $path, '/' ) );
		if ( ! empty( $path_parts[1] ) && 'wp-admin' != $path_parts[1] ) {
			$site_url = $domain . '/' . $path_parts[1];
		} else {
			$site_url = $domain;
		}
		$breeze_config     = breeze_fetch_configuration_data( $site_url );
		$blog_id_requested = isset( $breeze_config['blog_id'] ) ? $breeze_config['blog_id'] : 0;
	}

	/**
	 * Generates the URL to redirect to after cache clearing.
	 *
	 * @param int $post_id The ID of the post/page for which cache is cleared.
	 * @return string URL to redirect to after cache clearing, typically the previous page or the post/page listing page in the admin panel.
	 */
	public function sendback_url( $post_id ) {
		$post = get_post( $post_id );
		if ( $post ) {
			$post_type = $post->post_type;
		}
		$sendback = wp_get_referer();

		if ( ! $sendback ) {
			$sendback = admin_url( 'edit.php' );
			if ( ! empty( $post_type ) ) {
				$sendback = add_query_arg( 'post_type', $post_type, $sendback );
			}
		}
		return $sendback;
	}
}

new Purge_Post_Cache();
