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
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class Breeze_PurgeVarnish {
	protected $blogId;
	protected $urlsPurge   = array();
	protected $auto_purge  = false;
	protected $actions     = array(
		'switch_theme',                        // After a theme is changed
		'save_post',                            // Save a post
		'deleted_post',                        // Delete a post
		'edit_post',                            // Edit a post - includes leaving comments
	);
	protected $actionsNoId = array( 'switch_theme' );

	public function __construct() {
		global $blog_id;
		$this->blogId = $blog_id;

		//storage config
		if ( ! empty( Breeze_Options_Reader::get_option_value( 'auto-purge-varnish' ) ) ) {
			$this->auto_purge = (int) Breeze_Options_Reader::get_option_value( 'auto-purge-varnish' );
			if ( $this->auto_purge && ! isset( $_GET['breeze_check_cache_available'] ) ) {
				// before sending the requests, we need to make sure Varnish is actually enabled.
				// If Varnish is disabled, the requests will take longer to finish and will affect
				// the WordPress performance.
				#$this->auto_purge = is_varnish_cache_started();
			}
		}
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		if ( $this->auto_purge ) {
			if ( ! empty( $this->actions ) ) {
				//if enabled auto purge option , this action will start
				foreach ( $this->actions as $action ) {
					if ( in_array( $action, $this->actionsNoId ) ) {
						add_action(
							$action,
							function () {
								array_push( $this->urlsPurge, home_url() . '/?breeze' );
							}
						);
					} else {
						add_action( $action, array( $this, 'purge_post' ) );
					}
				}
			}
			//Pust urlsPurge after comment
			add_action( 'comment_post', array( $this, 'purge_post_on_comment' ), 10, 3 );
			add_action( 'wp_set_comment_status', array( $this, 'purge_post_on_comment_status' ), 10, 2 );
		}
		//Execute Purge
		add_action( 'shutdown', array( $this, 'breeze_execute_purge' ) );
	}

	/**
	 * Execute Purge
	 *
	 */
	public function breeze_execute_purge() {

		if ( ! empty( $this->urlsPurge ) ) {
			$urlsPurge = array_unique( $this->urlsPurge );

			// before sending the requests, we need to make sure Varnish is actually enabled.
			// If Varnish is disabled, the requests will take longer to finish and will affect
			// the WordPress performance.
			$do_purge = is_varnish_cache_started();

			if ( true === $do_purge ) {
				foreach ( $urlsPurge as $url ) {
					$this->purge_cache( $url );
				}
			}
		} else {
			$homepage = home_url() . '/?breeze';

			if ( isset( $_GET['breeze_purge_cloudflare'] ) && check_admin_referer( 'breeze_purge_cache_cloudflare' ) ) {
				$reset   = Breeze_CloudFlare_Helper::reset_all_cache();
				$json    = json_decode( trim( $reset ), true );
				$message = __( 'CloudWays - Cloudflare microservice was not reachable. ', 'breeze' );
				$class   = 'notice notice-error is-dismissible breeze-notice message-clear-cache-top';
				if ( null !== $json && json_last_error() === JSON_ERROR_NONE && isset( $json['success'] ) ) {
					$success = filter_var( $json['success'], FILTER_VALIDATE_BOOLEAN );
					$class   = 'notice notice-warning is-dismissible breeze-notice';
					$message = __( 'Cloudflare cache data has not been purged. ', 'breeze' );
					if ( true === $success ) {
						$message = __( 'Cloudflare cache data has been purged. ', 'breeze' );
						$class   = 'notice notice-success is-dismissible breeze-notice';
					}
				}
				printf( '<div class="%1$s" style="margin: 10px 14px 10px 0;padding: 10px; display: none; font-weight: 600;"><p>%2$s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>', esc_attr( $class ), esc_html( $message ) );
			}

			if ( isset( $_GET['breeze_purge'] ) && check_admin_referer( 'breeze_purge_cache' ) ) {
				//clear static cache
				$size_cache = Breeze_Configuration::breeze_clean_cache();
				$class      = 'notice notice-success is-dismissible breeze-notice message-clear-cache-top';
				$message    = __( 'Cache data has been purged: ', 'breeze' ) . $size_cache . __( ' Kb static cache cleaned', 'breeze' );

				printf( '<div class="%1$s" style="margin: 10px 14px 10px 0;padding: 10px; display: none; font-weight: 600;"><p>%2$s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>', esc_attr( $class ), esc_html( $message ) );
				// clear varnish cache.
				if ( is_varnish_cache_started() ) {
					$admin              = new Breeze_Admin();
					$varnish_response   = $admin->breeze_clear_varnish();
					$message            = $varnish_response ? __( 'Varnish Cache has been purged.', 'breeze' ) : __( 'Problem: Varnish Cache not purged.', 'breeze' );
					$notification_class = $varnish_response ? 'notice-success' : 'notice-error';
					$classes            = 'notice is-dismissible breeze-notice message-clear-cache-top ' . $notification_class;
					printf( '<div class="%1$s" style="margin: 10px 14px 10px 0;padding: 10px;display: none; font-weight: 600;"><p>%2$s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>', esc_attr( $classes ), esc_html( $message ) );
				}
				// Clear Cloudflare cache.
				if ( Breeze_CloudFlare_Helper::is_cloudflare_enabled() ) {
					$response           = Breeze_CloudFlare_Helper::reset_all_cache();
					$response           = json_decode( trim( $response ), true );
					$message            = __( 'Cloudflare cache data not purged. ', 'breeze' );
					$notification_class = 'notice-error';
					if ( null !== $response
					&& json_last_error() === JSON_ERROR_NONE
					&& isset( $response['success'] ) ) {

						$message            = __( 'Cloudflare cache data has been purged. ', 'breeze' );
						$notification_class = 'notice-success';
					}
					$classes = 'notice is-dismissible breeze-notice message-clear-cache-top ' . $notification_class;
					printf( '<div class="%1$s" style="margin: 10px 14px 10px 0;padding: 10px;display: none; font-weight: 600;"><p>%2$s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>', esc_attr( $classes ), esc_html( $message ) );
				}
			}
		}
	}


	/**
	 * Purge varnish cache
	 *
	 */
	public function purge_cache( $url ) {
		if ( true === Breeze_CloudFlare_Helper::is_log_enabled() ) {
			error_log( '######### PURGE VANISH; URL(s) ###: ' . var_export( $url, true ) );
		}
		$parseUrl = parse_url( $url );
		$pregex   = '';
		// Default method is URLPURGE to purge only one object, this method is specific to cloudways configuration
		$purge_method = 'URLPURGE';
		// Use PURGE method when purging all site
		if ( isset( $parseUrl['query'] ) && ( $parseUrl['query'] == 'breeze' ) ) {
			// The regex is not needed as cloudways configuration purge all the cache of the domain when a PURGE is done
			$pregex       = '.*';
			$purge_method = 'PURGE';
		}
		// Determine the path
		$path = '';
		if ( isset( $parseUrl['path'] ) ) {
			$path = $parseUrl['path'];
		}
		// Determine the schema
		$schema = 'http://';
		if ( isset( $parseUrl['scheme'] ) ) {
			$schema = $parseUrl['scheme'] . '://';
		}
		// Determine the host
		$host = $parseUrl['host'];

		$varnish_ip   = Breeze_Options_Reader::get_option_value( 'breeze-varnish-server-ip' );
		$varnish_host = isset( $varnish_ip ) ? $varnish_ip : '127.0.0.1';
		$purgeme      = $varnish_host . $path . $pregex;
		if ( ! empty( $parseUrl['query'] ) && $parseUrl['query'] != 'breeze' ) {
			$purgeme .= '?' . $parseUrl['query'];
		}

		$ssl_verification = apply_filters( 'breeze_ssl_check_certificate', true );

		if ( ! is_bool( $ssl_verification ) ) {
			$ssl_verification = true;
		}

		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			$ssl_verification = false;
		}

		$request_args = array(
			'method'    => $purge_method,
			'headers'   => array(
				'Host'       => $host,
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
			),
			'sslverify' => $ssl_verification,
		);
		$response     = wp_remote_request( $schema . $purgeme, $request_args );
		if ( is_wp_error( $response ) || $response['response']['code'] != '200' ) {
			if ( $schema === 'https://' ) {
				$schema = 'http://';
			} else {
				$schema = 'https://';
			}
			$response = wp_remote_request( $schema . $purgeme, $request_args );
		}
		return $response;
	}

	//check permission
	public function check_permission() {
		return ( ! is_multisite() && current_user_can( 'activate_plugins' ) ) || current_user_can( 'manage_network' ) || ( is_multisite() && ! current_user_can( 'manage_network' ) && ( SUBDOMAIN_INSTALL || ( ! SUBDOMAIN_INSTALL && ( BLOG_ID_CURRENT_SITE != $this->blogId ) ) ) );
	}

	/*
	 * Purge varnish cache with action if id post exists
	 */
	public function purge_post( $postId ) {
		if ( 'save_post' === current_action() && did_action( 'edit_post' ) ) {
			// Prevent triggering this method twice when posts are updated.
			return;
		}
		$this->pushUrl( $postId );
	}

	/*
	 * Purge varnish cache after comment
	 */
	public function purge_post_on_comment( $comment_ID, $approved, $commentdata ) {
		if ( empty( $approved ) ) {
			return;
		}
		$postId = $commentdata['comment_post_ID'];
		$this->pushUrl( $postId );
	}

	/*
	 * if a comments status changes, purge varnish
	 */
	public function purge_post_on_comment_status( $comment_ID, $comment_status ) {
		$comment = get_comment( $comment_ID );
		if ( ! empty( $comment ) ) {
			$postId = $comment->comment_post_ID;
			$this->pushUrl( $postId );
		}
	}

	/*
	 * Push url from post ID
	 */
	public function pushUrl( $postId ) {
		// If this is a valid post we want to purge the post,
		// the home page and any associated tags and categories
		$valid_post_status = array( 'publish', 'private', 'trash' );
		$this_post_status  = get_post_status( $postId );
		// Not all post types are created equal
		$invalid_post_type   = array( 'nav_menu_item', 'revision' );
		$noarchive_post_type = array( 'post', 'page' );
		$this_post_type      = get_post_type( $postId );
		// Determine the route for the rest API
		// This will need to be revisted if WP updates the version.
		$rest_api_route = 'wp/v2';
		// array to collect all our URLs
		$listofurls = array();
		// Verify we have a permalink and that we're a valid post status and a not an invalid post type
		if ( get_permalink( $postId ) == true && in_array( $this_post_status, $valid_post_status ) && ! in_array( $this_post_type, $invalid_post_type ) ) {
			// Post URL
			array_push( $listofurls, get_permalink( $postId ) );
			// JSON API Permalink for the post based on type
			// We only want to do this if the rest_base exists
			$post_type_object = get_post_type_object( $this_post_type );
			if ( isset( $post_type_object->rest_base ) && ! empty( $post_type_object->rest_base ) ) {
				$rest_permalink = get_rest_url() . $rest_api_route . '/' . $post_type_object->rest_base . '/' . $postId . '/';
			} elseif ( $this_post_type == 'post' ) {
				$rest_permalink = get_rest_url() . $rest_api_route . '/posts/' . $postId . '/';
			} elseif ( $this_post_type == 'page' ) {
				$rest_permalink = get_rest_url() . $rest_api_route . '/views/' . $postId . '/';
			}
			if ( isset( $rest_permalink ) ) {
				array_push( $listofurls, $rest_permalink );
			}
			// Add in AMP permalink if Automattic's AMP is installed
			if ( function_exists( 'amp_get_permalink' ) ) {
				array_push( $listofurls, amp_get_permalink( $postId ) );
			}
			// Regular AMP url for posts
			array_push( $listofurls, get_permalink( $postId ) . 'amp/' );
			// Also clean URL for trashed post.
			if ( $this_post_status == 'trash' ) {
				$trashpost = get_permalink( $postId );
				$trashpost = str_replace( '__trashed', '', $trashpost );
				array_push( $listofurls, $trashpost, $trashpost . 'feed/' );
			}
			// Category purge based on Donnacha's work in WP Super Cache
			$categories = get_the_category( $postId );
			if ( $categories ) {
				foreach ( $categories as $cat ) {
					$category_link        = get_category_link( $cat->term_id );
					$category_link_no_cat = str_replace( 'category/', '', $category_link );
					if ( ! empty( $category_link ) && $category_link !== $category_link_no_cat ) {
						array_push( $listofurls, $category_link_no_cat );
					}

					array_push(
						$listofurls,
						$category_link,
						get_rest_url() . $rest_api_route . '/categories/' . $cat->term_id . '/'
					);
					$category_link = '';
				}
			}
			// Tag purge based on Donnacha's work in WP Super Cache
			$tags = get_the_tags( $postId );
			if ( $tags ) {
				foreach ( $tags as $tag ) {
					array_push(
						$listofurls,
						get_tag_link( $tag->term_id ),
						get_rest_url() . $rest_api_route . '/tags/' . $tag->term_id . '/'
					);
				}
			}
			// Author URL
			$author_id = get_post_field( 'post_author', $postId );
			array_push(
				$listofurls,
				get_author_posts_url( $author_id ),
				get_author_feed_link( $author_id ),
				get_rest_url() . $rest_api_route . '/users/' . $author_id . '/'
			);
			// Archives and their feeds
			if ( $this_post_type && ! in_array( $this_post_type, $noarchive_post_type ) ) {
				array_push(
					$listofurls,
					get_post_type_archive_link( get_post_type( $postId ) ),
					get_post_type_archive_feed_link( get_post_type( $postId ) )
					// Need to add in JSON?
				);
			}
			// Feeds
			array_push(
				$listofurls,
				get_bloginfo_rss( 'rdf_url' ),
				get_bloginfo_rss( 'rss_url' ),
				get_bloginfo_rss( 'rss2_url' ),
				get_bloginfo_rss( 'atom_url' ),
				get_bloginfo_rss( 'comments_rss2_url' ),
				get_post_comments_feed_link( $postId )
			);
			// Home Pages and (if used) posts page
			array_push(
				$listofurls,
				get_rest_url(),
			);
			if ( get_option( 'show_on_front' ) == 'page' ) {
				// Ensure we have a page_for_posts setting to avoid empty URL
				if ( get_option( 'page_for_posts' ) ) {
					array_push( $listofurls, get_permalink( get_option( 'page_for_posts' ) ) );
				}
			}

			// Trim all URLs in the list to ensure clean output
			$listofurls = array_map( 'trim', $listofurls );
			$homepage   = trailingslashit( home_url() );
			if ( ! in_array( $homepage, $listofurls, true ) ) {
				// Clear the cache for homepage
				array_push(
					$listofurls,
					$homepage
				);
			}

		} else {
			// Nothing
			return;
		}
		// Now flush all the URLs we've collected provided the array isn't empty
		if ( ! empty( $listofurls ) ) {
			$this->urlsPurge = array_filter(
				array_unique(
					array_merge(
						$this->urlsPurge,
						$listofurls
					),
					SORT_REGULAR
				)
			);
		}
	}
}

new Breeze_PurgeVarnish();
