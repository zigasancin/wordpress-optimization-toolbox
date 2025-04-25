<?php

class Breeze_Query_Strings_Rules {

	/**
	 * List of permanently ignored query vars.
	 *
	 * @var string[]
	 */
	public $ignored_list = array(
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_expid',
		'utm_term',
		'utm_content',
		'mtm_source',
		'mtm_medium',
		'mtm_campaign',
		'mtm_keyword',
		'mtm_cid',
		'mtm_content',
		'pk_source',
		'pk_medium',
		'pk_campaign',
		'pk_keyword',
		'pk_cid',
		'pk_content',
		'fb_action_ids',
		'fb_action_types',
		'fb_source',
		'fbclid',
		'campaignid',
		'adgroupid',
		'adid',
		'gclid',
		'age-verified',
		'ao_noptimize',
		'usqp',
		'cn-reloaded',
		'_ga',
		'sscid',
		'gclsrc',
		'_gl',
		'mc_cid',
		'mc_eid',
		'_bta_tid',
		'_bta_c',
		'trk_contact',
		'trk_msg',
		'trk_module',
		'trk_sid',
		'gdfms',
		'gdftrk',
		'gdffi',
		'_ke',
		'redirect_log_mongo_id',
		'redirect_mongo_id',
		'sb_referer_host',
		'mkwid',
		'pcrid',
		'ef_id',
		's_kwcid',
		'msclkid',
		'dm_i',
		'epik',
		'pp',
	);

	/**
	 * List of always cacheable query vars.
	 *
	 * @var string[]
	 */
	public $always_cache_query = array(
		'lang',
		'permalink_name',
		'lp-variation-id',
	);

	// Hold the class instance.
	private static $instance = null;

	function __construct() {
		// Include necessary helper functions.
		require_once $this->trailingslashit( dirname( __FILE__ ) ) . '/helpers.php';
	}

	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Breeze_Query_Strings_Rules();
		}

		return self::$instance;
	}

	/**
	 * Appends a trailing slash.
	 * @param $value the path.
	 * @return string path with a trailing slash added.
	 */
	public function trailingslashit( $value ) {
		return rtrim( $value, '/\\' ) . '/';
	}

	public function fetch_ignored_list() {
		$this->ignored_list = apply_filters( 'breeze_ignored_query_strings_list', $this->ignored_list );

		return $this->ignored_list;
	}

	public static function when_woocommerce_settings_save() {
		if ( isset( $_POST['save'] ) && isset( $_POST['woocommerce_default_customer_address'] ) ) {
			Breeze_ConfigCache::factory()->write_config_cache();
		}
	}

	public function fetch_always_cache_list() {
		$this->always_cache_query = apply_filters( 'breeze_always_cache_query_strings', $this->always_cache_query );

		// woocommerce_geolocation_ajax
		if ( isset( $GLOBALS['breeze_config']['woocommerce_geolocation_ajax_inherit'] ) && ! empty( $GLOBALS['breeze_config']['woocommerce_geolocation_ajax_inherit'] ) ) {
			$sub_blog_id = $GLOBALS['breeze_config']['blog_id'];

			if (
				isset( $GLOBALS['breeze_config']['woocommerce_geolocation_ajax_inherit'][ 'subsite_' . $sub_blog_id ] )
				&&
				1 === (int) $GLOBALS['breeze_config']['woocommerce_geolocation_ajax_inherit'][ 'subsite_' . $sub_blog_id ]
			) {
				$this->always_cache_query[] = 'v';
			}
		} elseif ( isset( $GLOBALS['breeze_config']['woocommerce_geolocation_ajax'] ) && 1 === (int) $GLOBALS['breeze_config']['woocommerce_geolocation_ajax'] ) {
			$this->always_cache_query[] = 'v';
		}

		if ( isset( $GLOBALS['breeze_config'] ) && isset( $GLOBALS['breeze_config']['permalink_structure'] ) ) {
			$get_permalink_structure = $GLOBALS['breeze_config']['permalink_structure'];
			if ( is_array( $get_permalink_structure ) ) {
				// since it's using the default breeze-config.
				$this->always_cache_query[] = 'p';
				$this->always_cache_query[] = 'page_id';
				$this->always_cache_query[] = 'postname';
				$this->always_cache_query[] = 'cat';
				$this->always_cache_query[] = 'attachment_id';
				$this->always_cache_query[] = 'author';
				$this->always_cache_query[] = 'author_name';
				$this->always_cache_query[] = 'calendar';
				$this->always_cache_query[] = 'second';
				$this->always_cache_query[] = 'minute';
				$this->always_cache_query[] = 'hour';
				$this->always_cache_query[] = 'day';
				$this->always_cache_query[] = 'monthnum';
				$this->always_cache_query[] = 'year';
				$this->always_cache_query[] = 'page';
				$this->always_cache_query[] = 'paged';
				$this->always_cache_query[] = 'category';
				$this->always_cache_query[] = 'taxonomy';
				$this->always_cache_query[] = 'tag';
				$this->always_cache_query[] = 'tag_id';
				$this->always_cache_query[] = 'withcomments';
				$this->always_cache_query[] = 'withoutcomments';
				$this->always_cache_query[] = 'm';
			} else {
				if ( empty( trim( $get_permalink_structure ) ) ) {
					$this->always_cache_query[] = 'p';
					$this->always_cache_query[] = 'page_id';
					$this->always_cache_query[] = 'postname';
					$this->always_cache_query[] = 'cat';
					$this->always_cache_query[] = 'attachment_id';
					$this->always_cache_query[] = 'author';
					$this->always_cache_query[] = 'author_name';
					$this->always_cache_query[] = 'calendar';
					$this->always_cache_query[] = 'second';
					$this->always_cache_query[] = 'minute';
					$this->always_cache_query[] = 'hour';
					$this->always_cache_query[] = 'day';
					$this->always_cache_query[] = 'monthnum';
					$this->always_cache_query[] = 'year';
					$this->always_cache_query[] = 'page';
					$this->always_cache_query[] = 'paged';
					$this->always_cache_query[] = 'category';
					$this->always_cache_query[] = 'taxonomy';
					$this->always_cache_query[] = 'tag';
					$this->always_cache_query[] = 'tag_id';
					$this->always_cache_query[] = 'withcomments';
					$this->always_cache_query[] = 'withoutcomments';
					$this->always_cache_query[] = 'm';

				}
			}
		}

		return $this->always_cache_query;
	}


	/**
	 * Validates if a link containing query string(s) should be cached or not.
	 *
	 * @return bool
	 * @since 1.2.4
	 */
	function breeze_is_query_string_ignore() {
		$always_cache_query = $this->fetch_always_cache_list();

		$current_url_query = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );
		parse_str( $current_url_query, $breeze_query_output );

		if ( 'GET' === $_SERVER['REQUEST_METHOD'] && ! empty( $current_url_query ) ) {
			$cache_page = true;
			// If the URL contains query string that needs caching.
			foreach ( $always_cache_query as $query_string ) {
				if ( array_key_exists( $query_string, $breeze_query_output ) ) {
					$cache_page = false;
					break;
				}
			}
			if ( false === $cache_page ) {
				return false;
			}

			// IF user defines query strings that can be cached.
			if (
				isset( $GLOBALS['breeze_config'], $GLOBALS['breeze_config']['cached-query-strings'] ) &&
				! empty( $GLOBALS['breeze_config']['cached-query-strings'] ) &&
				is_array( $GLOBALS['breeze_config']['cached-query-strings'] )
			) {
				foreach ( $GLOBALS['breeze_config']['cached-query-strings'] as $query_string ) {
					if ( array_key_exists( $query_string, $breeze_query_output ) ) {
						$cache_page = false;
						break;
					}
				}

				if ( false === $cache_page ) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Checks if the current URL contains ignored query strings.
	 *
	 * @param string $url Current URL.
	 *
	 * @return bool
	 */
	public function breeze_ignored_query_strings( $url = '' ) {
		if ( empty( trim( $url ) ) ) {
			$url = $_SERVER['REQUEST_URI'];
		}

		if ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
			return false;
		}

		$ignored_list = $this->fetch_ignored_list();

		$current_url_query = parse_url( $url, PHP_URL_QUERY );
		parse_str( $current_url_query, $breeze_query_output );

		$found_index = false;
		foreach ( $breeze_query_output as $index => $value ) {
			$index = mb_strtolower( trim( $index ) );
			if ( in_array( $index, $ignored_list, true ) ) {
				$found_index = true;
				break;
			}
		}

		return $found_index;
	}

	public function extract_query_strings( $url = '' ) {
		if ( empty( trim( $url ) ) ) {
			$url = $_SERVER['REQUEST_URI'];
		}

		$current_url_query = parse_url( $url, PHP_URL_QUERY );

		if ( ! empty( $current_url_query ) ) {
			parse_str( $current_url_query, $breeze_query_output );
		}

		if ( empty( $breeze_query_output ) ) {
			$breeze_query_output = array();
		}

		return $breeze_query_output;
	}

	public function check_query_var_group( $current_url = '' ) {

		if ( empty( trim( $current_url ) ) ) {
			$current_url = $_SERVER['REQUEST_URI'];
		}

		$found_items = array(
			'ignored_no'        => 0, // how many vars from ignore list are found.
			'ignored_items'     => array(), // which items were found in ignore list.
			'cached_no'         => 0, // query vars that need to be cached.
			'cached_items'      => array(), // which items were found in to cache list.
			'user_cached_no'    => 0, // query vars that need to be cached, these are defined by the user in back-end.
			'user_cached_items' => array(), // which items were found in to cache list, these are defined by the user in back-end.
			'extra_query_no'    => 0, // number of query vars not found in any list.
			'extra_query_vars'  => array(), // list of query vars not found in any list.
		);

		// Only process links if the request is GET.
		if ( ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== $_SERVER['REQUEST_METHOD'] ) || empty( $current_url ) ) {
			return $found_items;
		}

		$ignored_query_vars      = $this->fetch_ignored_list();
		$to_cache_query_vars     = $this->fetch_always_cache_list();
		$user_defined_query_vars = array();

		// Current URL query strings ( vars ).
		$extracted_vars = $this->extract_query_strings( $current_url );
		// Query strings that are not found anywhere.
		$not_found_anywhere = $extracted_vars;

		if (
			isset( $GLOBALS['breeze_config'], $GLOBALS['breeze_config']['cached-query-strings'] ) &&
			! empty( $GLOBALS['breeze_config']['cached-query-strings'] ) &&
			is_array( $GLOBALS['breeze_config']['cached-query-strings'] )
		) {
			$user_defined_query_vars = $GLOBALS['breeze_config']['cached-query-strings'];
			if ( is_array( $user_defined_query_vars ) ) {
				foreach ( $user_defined_query_vars as $index => $value ) {
					if ( 's' === $value ) {
						unset( $user_defined_query_vars[ $index ] );
					}
				}
			}
		}

		foreach ( $extracted_vars as $index => $value ) {

			// Fetch all the query vars that are in the ignore list and found in current URL.
			if ( in_array( $index, $ignored_query_vars, true ) ) {
				$found_items['ignored_no'] ++;
				$found_items['ignored_items'][ $index ] = $value;
				unset( $not_found_anywhere[ $index ] );
			}

			// Fetch all the query vars that are in the must cache list and found in current URL.
			if ( in_array( $index, $to_cache_query_vars, true ) ) {
				$found_items['cached_no'] ++;
				$found_items['cached_items'][ $index ] = $value;
				unset( $not_found_anywhere[ $index ] );
			}

			// Fetch all the query vars that are in the must cache list and found in current URL, user defined.
			if ( 
				in_array( $index, $user_defined_query_vars, true ) 
				|| ! empty( breeze_is_string_in_array_values( $index, $user_defined_query_vars ) )
			) {
				$found_items['user_cached_no'] ++;
				$found_items['user_cached_items'][ $index ] = $value;
				unset( $not_found_anywhere[ $index ] );
			}
		}

		$found_items['extra_query_no']   = count( $not_found_anywhere );
		$found_items['extra_query_vars'] = $not_found_anywhere;

		return $found_items;

	}

	/**
	 * Rebuild the URL without the ignored query strings.
	 */
	public function rebuild_url( $url = '', $query_vars = array() ) {

		// if there are any query vars not found in any lists, we send the same link back.
		// This URL will not be cached.
		if ( 0 !== (int) $query_vars['extra_query_no'] ) {
			return $url;
		}

		if ( false !== strpos( $url, '?' ) ) {
			$e      = explode( '?', $url );
			$result = array_merge( $query_vars['cached_items'], $query_vars['user_cached_items'] );
			$url    = rtrim( $e[0], '/\\' ) . '/' . ( ( ! empty( $result ) ) ? '?' . http_build_query( $result ) : '' );
		}

		return $url;

	}

}

new Breeze_Query_Strings_Rules();
