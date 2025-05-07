<?php
/**
 * @copyright 2017  Cloudways  https://www.cloudways.com
 *
 *  This plugin is inspired from WP Speed of Light by JoomUnited.
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

/**
 * Load the required resources.
 *
 * Class Breeze_Admin
 */
class Breeze_Admin {
	public function __construct() {
		add_action(
			'init',
			function () {
				load_plugin_textdomain( 'breeze', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			}
		);

		// Load the Javascript for Lazy load.
		add_action( 'wp_enqueue_scripts', array( $this, 'breeze_lazy_load' ) );

		// Add our custom action to clear cache
		add_action( 'breeze_clear_all_cache', array( $this, 'breeze_clear_all_cache' ) );
		add_action( 'breeze_clear_varnish', array( $this, 'breeze_clear_varnish' ) );

		// Check the status change of excluded ecommerce pages.
		add_action( 'transition_post_status', array( $this, 'on_all_status_transitions' ), 10, 3 );
		// Check if woocommerce exists
		if ( function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			// Clear all cache on bulk update
			add_action( 'woocommerce_after_product_object_save', array( $this, 'clear_cache_if_changed_api' ), 10, 2 );
		}

		if ( is_admin() || 'cli' === php_sapi_name() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			//register menu
			add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
			add_action( 'network_admin_menu', array( $this, 'register_network_menu_page' ) );

			// Add notice when installing plugin
			$first_install = get_option( 'breeze_first_install' );
			if ( false === $first_install ) {
				add_option( 'breeze_first_install', 'yes' );
			}
			if ( 'yes' === $first_install ) {
				add_action( 'admin_notices', array( $this, 'installing_notices' ) );
			}

			$breeze_display_clean = Breeze_Options_Reader::get_option_value( 'breeze-display-clean' );

			if ( isset( $breeze_display_clean ) && $breeze_display_clean ) {
				//register top bar menu
				add_action( 'admin_bar_menu', array( $this, 'register_admin_bar_menu' ), 999 );
			}
			/** Load admin js * */
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );

			add_action( 'wp_head', array( $this, 'define_ajaxurl' ) );
			$this->ajax_handle();

			// Add setting buttons to plugins list page
			add_filter( 'plugin_action_links_' . BREEZE_BASENAME, array( $this, 'breeze_add_action_links' ) );
			add_filter(
				'network_admin_plugin_action_links_' . BREEZE_BASENAME,
				array(
					$this,
					'breeze_add_action_links_network',
				)
			);
			add_action( 'wpmu_new_blog', array( &$this, 'create_new_blog_items' ), 10, 6 );
		}

		add_action(
			'admin_init',
			function () {
				// When permalinks are reset, we also reset the config files.
				if ( isset( $_POST['permalink_structure'] ) || isset( $_POST['category_base'] ) ) {
					$to_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'permalink';
					if ( 'permalink' !== $to_action ) {
						check_admin_referer( 'options-options' );
					} else {
						check_admin_referer( 'update-permalink' );
					}
					Breeze_Upgrade::refresh_config_files();
				}
			},
			99
		);

		add_action(
			'wp_login',
			function ( $user_login, $user ) {
				if ( in_array( 'administrator', (array) $user->roles, true ) ) {
					Breeze_Upgrade::refresh_config_files();
				}
			},
			10,
			2
		);
	}

	/**
	 * Check the page status and if the current page is
	 * in the excluded ecommerce pages list.
	 *
	 * Clears the cache when a scheduled post get's published.
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param object $post
	 * @return void
	 */
	public function on_all_status_transitions( $new_status, $old_status, $post ) {

		// Make sure the Breeze_Ecommerce_Cache class is available.
		require_once( BREEZE_PLUGIN_DIR . 'inc/cache/ecommerce-cache.php' );

		if ( $new_status != $old_status && Breeze_Ecommerce_Cache::is_excluded_ecom_page( $post->ID ) ) {
			Breeze_ConfigCache::write_config_cache();
		}
		// Clear cache when a scheduled post get's published.
		if ( 'future' === $old_status && 'publish' === $new_status ) {
			do_action( 'breeze_clear_varnish' );
			Breeze_PurgeCache::breeze_cache_flush();
			Breeze_MinificationCache::clear_minification();
		}
	}

	public function create_new_blog_items( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

		if ( ! is_multisite() ) {
			return;
		}
		$blog_id        = intval( $blog_id );
		$default_option = self::breeze_default_options_value();

		$basic     = $default_option['basic'];
		$file      = $default_option['file'];
		$heartbeat = $default_option['heartbeat'];
		$cdn       = $default_option['cdn'];
		$advanced  = $default_option['advanced'];
		$preload   = $default_option['preload'];
		$varnish   = $default_option['varnish'];

		$is_inherit_already = get_blog_option( $blog_id, 'breeze_inherit_settings', '' );
		if ( '' === $is_inherit_already ) {
			update_blog_option( $blog_id, 'breeze_inherit_settings', '1' );
		}

		$blog_basic = get_blog_option( $blog_id, 'breeze_basic_settings', '' );
		if ( empty( $blog_basic ) ) {
			update_blog_option( $blog_id, 'breeze_basic_settings', $basic );
		}

		$blog_advanced = get_blog_option( $blog_id, 'breeze_advanced_settings', '' );
		if ( empty( $blog_advanced ) ) {
			update_blog_option( $blog_id, 'breeze_advanced_settings', $advanced );
		}

		$blog_heartbeat = get_blog_option( $blog_id, 'breeze_heartbeat_settings', '' );
		if ( empty( $blog_heartbeat ) ) {
			update_blog_option( $blog_id, 'breeze_heartbeat_settings', $heartbeat );
		}

		$blog_preload = get_blog_option( $blog_id, 'breeze_preload_settings', '' );
		if ( empty( $blog_preload ) ) {
			update_blog_option( $blog_id, 'breeze_preload_settings', $preload );
		}

		$blog_file = get_blog_option( $blog_id, 'breeze_file_settings', '' );
		if ( empty( $blog_file ) || empty( $is_advanced ) ) {
			$save_file = $file;

			if ( isset( $breeze_delay_js_scripts ) ) {
				if ( empty( $blog_file ) ) {
					$save_file['breeze-delay-js-scripts'] = $breeze_delay_js_scripts;
				} else {
					$save_file                            = $blog_file;
					$save_file['breeze-delay-js-scripts'] = $breeze_delay_js_scripts;
				}
			}
			update_blog_option( $blog_id, 'breeze_file_settings', $save_file );
		}

		$blog_cdn = get_blog_option( $blog_id, 'breeze_cdn_integration', '' );
		if ( empty( $blog_cdn ) ) {
			update_blog_option( $blog_id, 'breeze_cdn_integration', $cdn );
		}

		$blog_varnish = get_blog_option( $blog_id, 'breeze_varnish_cache', '' );
		if ( empty( $blog_varnish ) ) {
			update_blog_option( $blog_id, 'breeze_varnish_cache', $varnish );
		}

	}


	/**
	 * Clear cache if products are changed trough API
	 *
	 * @param $product
	 * @param $data_store
	 *
	 * @return void
	 */
	public function clear_cache_if_changed_api( $product, $data_store ) {
		// Check if this is a REST API update and clear the cache only if this is the first time hook is called.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && did_action( 'woocommerce_after_product_object_save' ) === 1 ) {
			$this->breeze_clear_all_cache();
		}
	}

	/**
	 * Load Lazy Load library
	 * @since 1.2.0
	 * @access public
	 */
	public function breeze_lazy_load() {

		$is_lazy_load_enabled = false;
		$is_lazy_load_native  = false;
		$is_lazy_load_iframe  = false;

		$option_breeze_lazy_load         = Breeze_Options_Reader::get_option_value( 'breeze-lazy-load' );
		$option_breeze_lazy_load_native  = Breeze_Options_Reader::get_option_value( 'breeze-lazy-load-native' );
		$option_breeze_lazy_load_iframes = Breeze_Options_Reader::get_option_value( 'breeze-lazy-load-iframes' );

		if ( isset( $option_breeze_lazy_load ) ) {
			$is_lazy_load_enabled = filter_var( $option_breeze_lazy_load, FILTER_VALIDATE_BOOLEAN );
		}
		if ( isset( $option_breeze_lazy_load_native ) ) {
			$is_lazy_load_native = filter_var( $option_breeze_lazy_load_native, FILTER_VALIDATE_BOOLEAN );
		}
		if ( isset( $option_breeze_lazy_load_iframes ) ) {
			$is_lazy_load_iframe = filter_var( $option_breeze_lazy_load_iframes, FILTER_VALIDATE_BOOLEAN );
		}

		if ( ( true === $is_lazy_load_enabled && false === $is_lazy_load_native ) || true === $is_lazy_load_iframe ) {
			if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery' );
			}

			wp_enqueue_script( 'breeze-lazy', plugins_url( 'assets/js/js-front-end/breeze-lazy-load.min.js', dirname( __FILE__ ) ), array(), BREEZE_VERSION, true );
		}

		// Fix viewport images when lazy-load is active.
		if ( true === $is_lazy_load_enabled ) {
			if ( false === $is_lazy_load_native ) {
				$data = 'const lazyLoadInstance = new LazyLoad({
						    elements_selector: ".br-lazy",
						    data_src: "breeze",
						    data_srcset: "brsrcset",
						    data_sizes: "brsizes",
						    class_loaded: "br-loaded",
						    threshold: 300,
						    callback_enter: (element) => {
						        console.log("Entered viewport:", element);
						    },
						    callback_loaded: (element) => {
						        console.log("Loaded:", element);
						    }
						});';
				wp_add_inline_script( 'breeze-lazy', $data, 'after' );
			} else {
				$inline_js = <<<INLINEJS
window.addEventListener("DOMContentLoaded",(e=>{document.querySelectorAll('img[loading="lazy"]').forEach((e=>{e.getBoundingClientRect().top<=window.innerHeight&&(e.loading="eager")}))}));
INLINEJS;

				add_action(
					'wp_footer',
					function () use ( $inline_js ) {
						printf( '<script type="text/javascript">%s</script>', $inline_js );
					},
					99
				);
			}
		}
	}

	/**
	 * Admin Init.
	 *
	 */
	public function admin_init() {
		//Check plugin requirements
		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			if ( current_user_can( 'activate_plugins' ) && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				deactivate_plugins( __FILE__ );
				add_action( 'admin_notices', array( $this, 'breeze_show_error' ) );
				unset( $_GET['activate'] );
			}
		}

		//Do not load anything more
		return;
	}

	/**
	 * define Ajax URL.
	 */
	function define_ajaxurl() {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<script type="text/javascript">
           var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";
             </script>';
		}
	}

	/**
	 * Add notice message when install plugin.
	 */
	public function installing_notices() {
		if ( get_option( 'breeze_first_install' ) === 'no' ) {
			return;
		}
		if ( is_user_logged_in() && ( is_super_admin() || current_user_can( 'administrator' ) ) ) {
			$class = 'notice notice-success';

			$message = __( 'Thanks for installing Breeze. It is always recommended not to use more than one caching plugin at the same time. We recommend you to purge cache if necessary.', 'breeze' );

			printf( '<div class="%1$s"><p>%2$s <button class="button" id="breeze-hide-install-msg">' . __( 'Hide message', 'breeze' ) . '</button></p></div>', esc_attr( $class ), esc_html( $message ) );
			update_option( 'breeze_first_install', 'no' );
		}
	}

	/**
	 * Enqueue CSS and JS files required for the plugin functionality.
	 */
	function load_admin_scripts() {
		if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
			wp_enqueue_script( 'jquery' );
		}
		$min = '.min';
		if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) {
			$min = '';
		}
		wp_enqueue_script( 'breeze-backend', plugins_url( 'assets/js/breeze-main' . $min . '.js', dirname( __FILE__ ) ), array( 'jquery' ), BREEZE_VERSION, true ); // BREEZE_VERSION
		wp_enqueue_style( 'breeze-notice', plugins_url( 'assets/css/breeze-admin-global.css', dirname( __FILE__ ) ), array(), BREEZE_VERSION );
		$current_screen = get_current_screen();
		if ( $current_screen->base == 'settings_page_breeze' || $current_screen->base == 'settings_page_breeze-network' ) {
			//add css
			wp_enqueue_style( 'breeze-fonts', plugins_url( 'assets/css/breeze-fonts.css', dirname( __FILE__ ) ), array(), BREEZE_VERSION ); // BREEZE_VERSION
			wp_enqueue_style( 'breeze-style', plugins_url( 'assets/css/breeze-admin.css', dirname( __FILE__ ) ), array( 'breeze-fonts' ), BREEZE_VERSION ); // BREEZE_VERSION

			//js
			#wp_enqueue_script( 'breeze-configuration', plugins_url( 'assets/js/breeze-configuration.js', dirname( __FILE__ ) ), array( 'jquery' ), BREEZE_VERSION, true );

			// Include the required jQuery UI Core & Libraries
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-widget' );

		}

		$token_name = array(
			'breeze_purge_varnish'   => '',
			'breeze_purge_database'  => '',
			'breeze_purge_cache'     => '',
			'breeze_save_options'    => '',
			'breeze_purge_opcache'   => '',
			'breeze_import_settings' => '',
			'breeze_reset_default'   => '',
			'breeze_check_cdn_url'   => '',
		);

		// Only create the security nonce if the user has manage_options ( administrator capabilities ).
		if ( false === breeze_is_restricted_access( true ) ) {
			$token_name = array(
				'breeze_purge_varnish'   => wp_create_nonce( '_breeze_purge_varnish' ),
				'breeze_purge_database'  => wp_create_nonce( '_breeze_purge_database' ),
				'breeze_purge_cache'     => wp_create_nonce( '_breeze_purge_cache' ),
				'breeze_save_options'    => wp_create_nonce( '_breeze_save_options' ),
				'breeze_purge_opcache'   => wp_create_nonce( '_breeze_purge_opcache' ),
				'breeze_import_settings' => wp_create_nonce( '_breeze_import_settings' ),
				'breeze_reset_default'   => wp_create_nonce( '_breeze_reset_default' ),
				'breeze_check_cdn_url'   => wp_create_nonce( '_breeze_check_cdn_url' ),
			);
		}

		wp_localize_script( 'breeze-backend', 'breeze_token_name', $token_name );
	}

	/**
	 * Register menu.
	 *
	 */
	function register_menu_page() {
		//add submenu for Cloudways
		add_submenu_page(
			'options-general.php',
			__( 'Breeze', 'breeze' ),
			__( 'Breeze', 'breeze' ),
			'manage_options',
			'breeze',
			array(
				$this,
				'breeze_load_page',
			)
		);
	}

	/**
	 * Register menu for multisite.
	 */
	function register_network_menu_page() {
		//add submenu for multisite network
		add_submenu_page(
			'settings.php',
			__( 'Breeze', 'breeze' ),
			__( 'Breeze', 'breeze' ),
			'manage_options',
			'breeze',
			array(
				$this,
				'breeze_load_page',
			)
		);
	}


	/**
	 * Register bar menu.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	function register_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {

		if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'editor' ) )
		     && ! ( is_plugin_active( 'woocommerce/woocommerce.php' ) && current_user_can( 'manage_woocommerce' ) ) ) {
			return;
		}

		$is_network = is_multisite() && is_network_admin();

		// add parent item
		$args = array(
			'id'    => 'breeze-topbar',
			'title' => esc_html__( 'Breeze', 'breeze' ),
			'meta'  => array(
				'classname' => 'breeze',
			),
		);
		$wp_admin_bar->add_node( $args );

		// Recreate the current URL in order to redirect to the same page on cache purge.
		$current_protocol = is_ssl() ? 'https' : 'http';
		$current_host     = $_SERVER['HTTP_HOST'];
		$current_script   = $_SERVER['SCRIPT_NAME'];
		$current_params   = $_SERVER['QUERY_STRING'];
		$current_params ? $current_params = '?' . $current_params : $current_params = '';
		$current_screen_base = get_current_screen()->base;

		if ( is_multisite() && ! is_subdomain_install() ) {
			$blog_details = get_blog_details();
			if ( ! empty( $blog_details->path ) ) {
				$blog_details->path = '';
			}

			$current_host .= rtrim( $blog_details->path, '/' );
		}

		#$current_screen_url = $current_protocol . '://' . $current_host . $current_script . '?' . $current_params;
		$current_script = str_replace( '/wp-admin/', '', $current_script );
		if ( $current_screen_base == 'dashboard' ) {
			$current_screen_url = admin_url() . $current_params;
		} else {
			$current_screen_url = admin_url( basename( $_SERVER['REQUEST_URI'] ) );
		}

		if ( true === $is_network ) {
			$current_screen_url = network_admin_url( basename( $_SERVER['REQUEST_URI'] ) );
			// particular fix when network is found twice in the url.
			$current_screen_url = str_replace( 'network/network', 'network/', $current_screen_url );
		}

		$current_screen_url = remove_query_arg(
			array(
				'breeze_purge',
				'_wpnonce',
				'breeze_purge_cloudflare',
				'breeze_purge_cache_cloudflare',
			),
			$current_screen_url
		);

		$purge_site_cache_url       = esc_url( wp_nonce_url( add_query_arg( 'breeze_purge', 1, $current_screen_url ), 'breeze_purge_cache' ) );
		$purge_cloudflare_cache_url = esc_url( wp_nonce_url( add_query_arg( 'breeze_purge_cloudflare', 1, $current_screen_url ), 'breeze_purge_cache_cloudflare' ) );

		// add purge all item
		$args = array(
			'id'     => 'breeze-purge-all',
			'title'  => ( ! is_multisite() || $is_network ) ? esc_html__( 'Purge All Cache', 'breeze' ) : esc_html__( 'Purge Site Cache', 'breeze' ),
			'parent' => 'breeze-topbar',
			'href'   => $purge_site_cache_url,
			'meta'   => array( 'class' => 'breeze-toolbar-group' ),
		);
		$wp_admin_bar->add_node( $args );

		// Only admin can purge cache per module.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add purge modules group
		$args = array(
			'id'     => 'breeze-purge-modules',
			'title'  => esc_html__( 'Purge Modules', 'breeze' ),
			'parent' => 'breeze-topbar',
			'meta'   => array( 'class' => 'breeze-toolbar-group' ),
		);
		$wp_admin_bar->add_node( $args );

		if ( true === Breeze_CloudFlare_Helper::is_cloudflare_enabled() ) {
			$args = array(
				'id'     => 'breeze-purge-cloudflare',
				'title'  => esc_html__( 'Purge Cloudflare Cache', 'breeze' ),
				'parent' => 'breeze-purge-modules',
				'href'   => $purge_cloudflare_cache_url,
				'meta'   => array(
					'class' => 'breeze-toolbar-group',
				),
			);
			$wp_admin_bar->add_node( $args );
		}

		if ( true === is_varnish_cache_started() ) {
			// add child item (Purge Modules)
			$args = array(
				'id'     => 'breeze-purge-varnish-group',
				'title'  => esc_html__( 'Purge Varnish Cache', 'breeze' ),
				'parent' => 'breeze-purge-modules',
			);
			$wp_admin_bar->add_node( $args );
		}

		// add child item (Purge Modules)
		$args = array(
			'id'     => 'breeze-purge-file-group',
			'title'  => esc_html__( 'Purge Internal Cache', 'breeze' ),
			'parent' => 'breeze-purge-modules',
		);
		$wp_admin_bar->add_node( $args );

		// add child item (Purge Modules)
		$args = array(
			'id'     => 'breeze-purge-object-cache-group',
			'title'  => esc_html__( 'Purge Object Cache', 'breeze' ),
			'parent' => 'breeze-purge-modules',
		);
		$wp_admin_bar->add_node( $args );

		// add settings item
		$args = array(
			'id'     => 'breeze-settings',
			'title'  => esc_html__( 'Settings', 'breeze' ),
			'parent' => 'breeze-topbar',
			'href'   => $is_network ? network_admin_url( 'settings.php?page=breeze' ) : admin_url( 'options-general.php?page=breeze' ),
			'meta'   => array( 'class' => 'breeze-toolbar-group' ),
		);
		$wp_admin_bar->add_node( $args );

		// add support item
		$args = array(
			'id'     => 'breeze-support',
			'title'  => esc_html__( 'Support', 'breeze' ),
			'href'   => 'https://support.cloudways.com/breeze-wordpress-cache-configuration',
			'parent' => 'breeze-topbar',
			'meta'   => array(
				'class'  => 'breeze-toolbar-group',
				'target' => '_blank',
			),
		);
		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Load plugin settings page for back-end.
	 */
	function breeze_load_page() {
		if ( isset( $_GET['page'] ) && 'breeze' === $_GET['page'] ) {
			require_once( BREEZE_PLUGIN_DIR . 'views/breeze-setting-views.php' );
		}
	}

	/**
	 * Error displayed of the PHP version is to low.
	 */
	public function breeze_show_error() {
		echo '<div class="error"><p><strong>Breeze</strong> need at least PHP 5.3 version, please update php before installing the plugin.</p></div>';
	}

	/**
	 * Admin ajax actions.
	 */
	public function ajax_handle() {
		add_action( 'wp_ajax_breeze_purge_varnish', array( 'Breeze_Configuration', 'purge_varnish_action' ) );
		add_action( 'wp_ajax_breeze_purge_file', array( 'Breeze_Configuration', 'breeze_ajax_clean_cache' ) );
		add_action( 'wp_ajax_breeze_purge_database', array( 'Breeze_Configuration', 'breeze_ajax_purge_database' ) );
		add_action( 'wp_ajax_breeze_purge_opcache', array( 'Breeze_Configuration', 'breeze_ajax_purge_opcache' ) );
		add_action( 'wp_ajax_breeze_reset_default', array( 'Breeze_Configuration', 'reset_to_default_ajax' ) );
		add_action( 'wp_ajax_breeze_check_cdn_url', array( 'Breeze_Configuration', 'breeze_ajax_check_cdn_url' ) );
	}

	/**
	 * Default Breeze settings.
	 * @return array
	 * @throws \Random\RandomException
	 */
	private static function breeze_default_options_value(): array {
		$default_data = array();
		// Default basic
		$basic = breeze_get_option( 'basic_settings' );
		if ( empty( $basic ) ) {
			$basic = array();
		}

		$all_user_roles     = breeze_all_wp_user_roles();
		$active_cache_users = array();
		foreach ( $all_user_roles as $usr_role ) {
			$active_cache_users[ $usr_role ] = 0;
		}

		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$token      = '';

		for ( $i = 0; $i < 12; $i ++ ) {
			$token .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
		}

		$default_basic         = array(
			'breeze-active'            => '1',
			'breeze-mobile-separate'   => '1',
			'breeze-cross-origin'      => '0',
			'breeze-disable-admin'     => $active_cache_users,
			'breeze-gzip-compression'  => '1',
			'breeze-desktop-cache'     => '1',
			'breeze-mobile-cache'      => '1',
			'breeze-b-ttl'             => 1440,
			'breeze-browser-cache'     => '1',
			'breeze-lazy-load'         => '0',
			'breeze-lazy-load-native'  => '0',
			'breeze-lazy-load-iframes' => '0',
			'breeze-lazy-load-videos'  => '0',
			'breeze-display-clean'     => '1',

		);
		$default_data['basic'] = array_merge( $default_basic, $basic );

		// Default File
		$file = breeze_get_option( 'file_settings' );
		if ( empty( $file ) ) {
			$file = array();
		}
		$breeze_delay_js_scripts = array(
			'gtag',
			'document.write',
			'html5.js',
			'show_ads.js',
			'google_ad',
			'blogcatalog.com/w',
			'tweetmeme.com/i',
			'mybloglog.com/',
			'histats.com/js',
			'ads.smowtion.com/ad.js',
			'statcounter.com/counter/counter.js',
			'widgets.amung.us',
			'ws.amazon.com/widgets',
			'media.fastclick.net',
			'/ads/',
			'comment-form-quicktags/quicktags.php',
			'edToolbar',
			'intensedebate.com',
			'scripts.chitika.net/',
			'_gaq.push',
			'jotform.com/',
			'admin-bar.min.js',
			'GoogleAnalyticsObject',
			'plupload.full.min.js',
			'syntaxhighlighter',
			'adsbygoogle',
			'gist.github.com',
			'_stq',
			'nonce',
			'post_id',
			'data-noptimize',
			'googletagmanager',
		);
		$default_file            = array(
			'breeze-minify-html'       => '0',
			// --
			'breeze-minify-css'        => '0',
			'breeze-font-display-swap' => '0',
			'breeze-group-css'         => '0',
			'breeze-exclude-css'       => array(),
			// --
			'breeze-minify-js'         => '0',
			'breeze-group-js'          => '0',
			'breeze-include-inline-js' => '0',
			'breeze-exclude-js'        => array(),
			'breeze-move-to-footer-js' => array(),
			'breeze-defer-js'          => array(),
			'breeze-enable-js-delay'   => '0',
			'no-breeze-no-delay-js'    => array(),
			'breeze-delay-all-js'      => '0',
			'breeze-delay-js-scripts'  => $breeze_delay_js_scripts,
		);

		$default_data['file'] = array_merge( $default_file, $file );

		// Default Advanced
		$advanced = breeze_get_option( 'advanced_settings' );
		if ( empty( $advanced ) ) {
			$advanced = array();
		}
		$default_advanced         = array(
			'breeze-exclude-urls'                  => array(),
			'cached-query-strings'                 => array(),
			'breeze-wp-emoji'                      => '0',
			'breeze-store-googlefonts-locally'     => '0',
			'breeze-store-googleanalytics-locally' => '0',
			'breeze-store-facebookpixel-locally'   => '0',
			'breeze-store-gravatars-locally'       => '0',
			'breeze-enable-api'                    => '0',
			'breeze-secure-api'                    => '0',
			'breeze-api-token'                     => $token,
		);
		$default_data['advanced'] = array_merge( $default_advanced, $advanced );

		$heartbeat = breeze_get_option( 'heartbeat_settings' );
		if ( empty( $heartbeat ) ) {
			$heartbeat = array();
		}

		$default_heartbeat         = array(
			'breeze-control-heartbeat'  => '0',
			'breeze-heartbeat-front'    => '',
			'breeze-heartbeat-postedit' => '',
			'breeze-heartbeat-backend'  => '',
		);
		$default_data['heartbeat'] = array_merge( $default_heartbeat, $heartbeat );

		//CDN default
		$cdn = breeze_get_option( 'cdn_integration' );
		if ( empty( $cdn ) ) {
			$cdn = array();
		}
		$wp_content          = substr( WP_CONTENT_DIR, strlen( ABSPATH ) );
		$default_cdn         = array(
			'cdn-active'          => '0',
			'cdn-url'             => '',
			'cdn-content'         => array( 'wp-includes', $wp_content ),
			'cdn-exclude-content' => array( '.php' ),
			'cdn-relative-path'   => '1',
		);
		$default_data['cdn'] = array_merge( $default_cdn, $cdn );

		// Preload default
		$preload = breeze_get_option( 'preload_settings' );
		if ( empty( $preload ) ) {
			$preload = array();
		}
		$default_preload         = array(
			'breeze-preload-fonts' => array(),
			'breeze-preload-links' => '1',
			'breeze-prefetch-urls' => array(),
		);
		$default_data['preload'] = array_merge( $default_preload, $preload );

		// Varnish default
		$varnish = breeze_get_option( 'varnish_cache' );
		if ( empty( $varnish ) ) {
			$varnish = array();
		}
		$default_varnish         = array(
			'auto-purge-varnish'       => '1',
			'breeze-varnish-server-ip' => '127.0.0.1',
			'breeze-ttl'               => 1440,
		);
		$default_data['varnish'] = array_merge( $default_varnish, $varnish );

		return $default_data;
	}

	/*
	 * Register active plugin hook.
	 */
	/**
	 * @throws \Random\RandomException
	 */
	public static function plugin_active_hook( $network_wide ) {
		WP_Filesystem();
		// Include required files.
		if ( ! class_exists( 'Breeze_ConfigCache' ) ) {
			require_once( BREEZE_PLUGIN_DIR . 'inc/cache/config-cache.php' );
		}
		if ( ! class_exists( 'Breeze_Configuration' ) ) {
			require_once( BREEZE_PLUGIN_DIR . 'inc/breeze-configuration.php' );
		}
		$default_option = self::breeze_default_options_value();
		$basic          = $default_option['basic'];
		$file           = $default_option['file'];
		$heartbeat      = $default_option['heartbeat'];
		$cdn            = $default_option['cdn'];
		$advanced       = $default_option['advanced'];
		$preload        = $default_option['preload'];
		$varnish        = $default_option['varnish'];

		if ( is_multisite() ) {
			if ( ! isset( $network_wide ) ) {
				$network_wide = is_network_admin();
			}

			$blogs = get_sites();
			foreach ( $blogs as $blog ) {
				$is_inherit_already = get_blog_option( (int) $blog->blog_id, 'breeze_inherit_settings', '' );
				if ( '' === $is_inherit_already ) {
					update_blog_option( (int) $blog->blog_id, 'breeze_inherit_settings', '1' );
				}

				$blog_basic = get_blog_option( (int) $blog->blog_id, 'breeze_basic_settings', '' );
				if ( empty( $blog_basic ) ) {
					update_blog_option( (int) $blog->blog_id, 'breeze_basic_settings', $basic );
				}

				$blog_advanced = get_blog_option( (int) $blog->blog_id, 'breeze_advanced_settings', '' );
				if ( empty( $blog_advanced ) ) {
					update_blog_option( (int) $blog->blog_id, 'breeze_advanced_settings', $advanced );
				}

				$blog_heartbeat = get_blog_option( (int) $blog->blog_id, 'breeze_heartbeat_settings', '' );
				if ( empty( $blog_heartbeat ) ) {
					update_blog_option( (int) $blog->blog_id, 'breeze_heartbeat_settings', $heartbeat );
				}

				$blog_preload = get_blog_option( (int) $blog->blog_id, 'breeze_preload_settings', '' );
				if ( empty( $blog_preload ) ) {
					update_blog_option( (int) $blog->blog_id, 'breeze_preload_settings', $preload );
				}

				$blog_file = get_blog_option( (int) $blog->blog_id, 'breeze_file_settings', '' );
				if ( empty( $blog_file ) || empty( $is_advanced ) ) {
					update_blog_option( (int) $blog->blog_id, 'breeze_file_settings', $file );
				}

				$blog_cdn = get_blog_option( (int) $blog->blog_id, 'breeze_cdn_integration', '' );
				if ( empty( $blog_cdn ) ) {
					update_blog_option( (int) $blog->blog_id, 'breeze_cdn_integration', $cdn );
				}

				$blog_varnish = get_blog_option( (int) $blog->blog_id, 'breeze_varnish_cache', '' );
				if ( empty( $blog_varnish ) ) {
					update_blog_option( (int) $blog->blog_id, 'breeze_varnish_cache', $varnish );
				}
			}

			if ( $network_wide ) {
				$network_basic = breeze_get_option( 'basic_settings' );
				if ( ! $network_basic ) {
					breeze_update_option( 'basic_settings', $basic );
				}

				$network_advanced = breeze_get_option( 'advanced_settings' );
				if ( ! $network_advanced ) {
					breeze_update_option( 'advanced_settings', $advanced );
				}

				$network_heartbeat = breeze_get_option( 'heartbeat_settings' );
				if ( ! $network_heartbeat ) {
					breeze_update_option( 'heartbeat_settings', $heartbeat );
				}

				$network_preload = breeze_get_option( 'preload_settings' );
				if ( ! $network_preload ) {
					breeze_update_option( 'preload_settings', $preload );
				}

				$network_file = breeze_get_option( 'file_settings' );
				if ( ! $network_file || empty( $is_advanced ) ) {
					breeze_update_option( 'file_settings', $file, true );
				}

				$network_cdn = breeze_get_option( 'cdn_integration' );
				if ( ! $network_cdn ) {
					breeze_update_option( 'cdn_integration', $cdn );
				}

				$network_varnish = breeze_get_option( 'varnish_cache' );
				if ( ! $network_varnish ) {
					breeze_update_option( 'varnish_cache', $varnish );
				}
			}

			Breeze_ConfigCache::factory()->write_config_cache( true );
		} else {
			$singe_network_basic = breeze_get_option( 'basic_settings' );
			if ( ! $singe_network_basic ) {
				breeze_update_option( 'basic_settings', $basic );
			}

			$singe_network_advanced = breeze_get_option( 'advanced_settings' );
			if ( ! $singe_network_advanced ) {
				breeze_update_option( 'advanced_settings', $advanced );
			}

			$singe_network_heartbeat = breeze_get_option( 'heartbeat_settings' );
			if ( ! $singe_network_heartbeat ) {
				breeze_update_option( 'heartbeat_settings', $heartbeat );
			}

			$singe_network_preload = breeze_get_option( 'preload_settings' );
			if ( ! $singe_network_preload ) {
				breeze_update_option( 'preload_settings', $preload );
			}

			$singe_network_file = breeze_get_option( 'file_settings' );
			if ( ! $singe_network_file || empty( $is_advanced ) ) {
				breeze_update_option( 'file_settings', $file, true );
			}

			$singe_network_cdn = breeze_get_option( 'cdn_integration' );
			if ( ! $singe_network_cdn ) {
				breeze_update_option( 'cdn_integration', $cdn );
			}

			$singe_network_varnish = breeze_get_option( 'varnish_cache' );
			if ( ! $singe_network_varnish ) {
				breeze_update_option( 'varnish_cache', $varnish );
			}
		}

		//add header to htaccess if setting is enabled or by default if first installed
		Breeze_Configuration::update_htaccess();
		//automatic config start cache
		Breeze_ConfigCache::factory()->write();
		Breeze_ConfigCache::factory()->write_config_cache();

		if ( ! empty( $basic ) && ! empty( $basic['breeze-active'] ) ) {
			Breeze_ConfigCache::factory()->toggle_caching( true );
		}
	}

	/**
	 * Unschedules the 'breeze_purge_cache' event if it is scheduled.
	 *
	 * Identifies the next scheduled occurrence of the 'breeze_purge_cache' event
	 * and removes it from the WordPress cron schedule.
	 *
	 * @return void
	 */
	public static function unschedule_events() {
		$timestamp = wp_next_scheduled( 'breeze_purge_cache' );
		wp_unschedule_event( $timestamp, 'breeze_purge_cache' );
	}

	/*
	 * Register deactivate plugin hook.
	 */
	public static function plugin_deactive_hook() {
		WP_Filesystem();
		if ( ! class_exists( 'Breeze_ConfigCache' ) ) {
			require_once( BREEZE_PLUGIN_DIR . 'inc/cache/config-cache.php' );
		}
		if ( ! class_exists( 'Breeze_Configuration' ) ) {
			require_once( BREEZE_PLUGIN_DIR . 'inc/breeze-configuration.php' );
		}

		Breeze_ConfigCache::factory()->clean_up();
		self::unschedule_events();
		//Breeze_ConfigCache::factory()->clean_config();
		Breeze_ConfigCache::factory()->toggle_caching( false );
		Breeze_Configuration::update_htaccess( true );

		Breeze_Store_Files::cleanup_all_extra_folder();

		$check_varnish = is_varnish_cache_started();
		if ( $check_varnish ) {
			if ( is_multisite() ) {
				$sites = get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					self::unschedule_events();
					do_action( 'breeze_clear_varnish' );
					restore_current_blog();
				}
			} else {
				do_action( 'breeze_clear_varnish' );
			}
		}
	}

	/**
	 * Removed the files and the database settings when plugin is uninstalled
	 * @since 1.1.6
	 * @static
	 * @access public
	 */
	public static function plugin_uninstall_hook() {
		// Remove config files and update .htaccess.
		self::plugin_deactive_hook();
		// Remove extra cache files. (fb,google,fonts...)
		Breeze_Store_Files::cleanup_all_extra_folder();
		// Delete config file(s).
		Breeze_ConfigCache::factory()->clean_config();
		// Remove data from the database.
		self::purge_local_options();
	}

	public static function purge_local_options() {
		if ( ( is_admin() || 'cli' === php_sapi_name() ) ) {
			if ( is_multisite() ) {
				$sites = get_sites(
					array(
						'fields' => 'ids',
					)
				);

				// Delete NETWORK level options.
				delete_site_option( 'breeze_basic_settings' );
				delete_site_option( 'breeze_preload_settings' );
				delete_site_option( 'breeze_file_settings' );
				delete_site_option( 'breeze_advanced_settings' );
				delete_site_option( 'breeze_heartbeat_settings' );
				delete_site_option( 'breeze_cdn_integration' );
				delete_site_option( 'breeze_varnish_cache' );
				delete_site_option( 'breeze_inherit_settings' );
				delete_site_option( 'breeze_show_incompatibility' );
				delete_site_option( 'breeze_first_install' );
				delete_site_option( 'breeze_advanced_settings_120' );
				delete_site_option( 'breeze_new_update' );
				delete_site_option( 'breeze_ecommerce_detect' );
				delete_site_option( 'breeze_exclude_url_pages' );
				delete_site_option( 'breeze_hide_notice' );
				delete_site_option( 'breeze_version' );

				// Delete transients.
				delete_transient( 'breeze_custom_varnish_server_active' );

				// Delete options for each sub-blog.
				foreach ( $sites as $blog_id ) {
					switch_to_blog( $blog_id );
					delete_option( 'breeze_basic_settings' );
					delete_option( 'breeze_preload_settings' );
					delete_option( 'breeze_file_settings' );
					delete_option( 'breeze_advanced_settings' );
					delete_option( 'breeze_heartbeat_settings' );
					delete_option( 'breeze_cdn_integration' );
					delete_option( 'breeze_varnish_cache' );
					delete_option( 'breeze_inherit_settings' );
					delete_option( 'breeze_show_incompatibility' );
					delete_option( 'breeze_first_install' );
					delete_option( 'breeze_advanced_settings_120' );
					delete_option( 'breeze_new_update' );
					delete_option( 'breeze_ecommerce_detect' );
					delete_option( 'breeze_exclude_url_pages' );
					delete_option( 'breeze_hide_notice' );
					delete_option( 'breeze_version' );
					restore_current_blog();
				}
			} else {
				// Delete options for each sub-blog.
				delete_option( 'breeze_basic_settings' );
				delete_option( 'breeze_preload_settings' );
				delete_option( 'breeze_file_settings' );
				delete_option( 'breeze_advanced_settings' );
				delete_option( 'breeze_heartbeat_settings' );
				delete_option( 'breeze_cdn_integration' );
				delete_option( 'breeze_varnish_cache' );
				delete_option( 'breeze_inherit_settings' );
				delete_option( 'breeze_show_incompatibility' );
				delete_option( 'breeze_first_install' );
				delete_option( 'breeze_advanced_settings_120' );
				delete_option( 'breeze_new_update' );
				delete_option( 'breeze_ecommerce_detect' );
				delete_option( 'breeze_exclude_url_pages' );
				delete_option( 'breeze_hide_notice' );
				delete_option( 'breeze_version' );

				// Delete transients.
				delete_transient( 'breeze_custom_varnish_server_active' );
			}
		}
	}


	/*
	 * Render tab for the settings in back-end.
	 */
	public static function render( $tab ) {
		require_once( BREEZE_PLUGIN_DIR . 'views/option-tabs/' . $tab . '-tab.php' );
	}

	/**
	 * Check varnish cache exist.
	 *
	 * @return bool
	 */
	public static function check_varnish() {
		if ( isset( $_SERVER['HTTP_X_VARNISH'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Applied to the list of links to display on the plugins page.
	 *
	 * @param array $links List of links.
	 *
	 * @return array
	 */
	public function breeze_add_action_links( $links ) {
		$my_links = array(
			'<a href="' . admin_url( 'options-general.php?page=breeze' ) . '">Settings</a>',
		);

		return array_merge( $my_links, $links );
	}

	/**
	 * Applied to the list of links to display on the network plugins page
	 *
	 * @param array $links List of links.
	 *
	 * @return array
	 */
	public function breeze_add_action_links_network( $links ) {
		$my_links = array(
			'<a href="' . network_admin_url( 'settings.php?page=breeze' ) . '">Settings</a>',
		);

		return array_merge( $my_links, $links );
	}

	/**
	 * Clear all cache action.
	 */
	public function breeze_clear_all_cache() {
		set_as_network_screen();

		global $post;
		$flush_cache = false;

		if ( ! empty( $post ) ) {
			$post_type = get_post_type( $post->ID );

			$flush_cache = true;
			if ( 'tribe_events' === $post_type ) {
				$flush_cache = false;
			}
		}

		if ( true === $flush_cache && isset( $_GET['post_type'] ) && 'tribe_events' === $_GET['post_type'] ) {
			$flush_cache = false;
		}

		if ( is_multisite() && ! is_network_admin() ) {

			// Show settings inherit option.
			$inherit_settings      = get_option( 'breeze_inherit_settings', '0' );
			$is_inherited_settings = isset( $inherit_settings ) ? filter_var( $inherit_settings, FILTER_VALIDATE_BOOLEAN ) : false;

			$url            = get_home_url();
			$list_of_urls   = array();
			$list_of_urls[] = trailingslashit( $url );

			if ( true === $is_inherited_settings ) {

				switch_to_blog( get_network()->site_id );
				//delete minify
				Breeze_MinificationCache::clear_minification();
				//clear normal cache
				Breeze_PurgeCache::breeze_cache_flush( $flush_cache, true, true );
				//clear varnish cache
				$this->breeze_clear_varnish();
				Breeze_PurgeCache::__flush_object_cache();
				$url            = get_home_url();
				$list_of_urls[] = trailingslashit( $url );
				restore_current_blog();
			}

			// for current blog.
			$current_blog_id = get_current_blog_id();
			Breeze_MinificationCache::clear_minification( $current_blog_id );
			Breeze_PurgeCache::breeze_cache_flush( $flush_cache, true, true );
			$main     = new Breeze_PurgeVarnish();
			$homepage = home_url() . '/?breeze';
			$main->purge_cache( $homepage );

			Breeze_CloudFlare_Helper::reset_all_cache( $list_of_urls );
			Breeze_PurgeCache::__flush_object_cache();

		} else {
			$current_blog_id = null;
			//delete minify
			if ( is_multisite() ) {
				$current_blog_id = get_current_blog_id();
			}
			Breeze_MinificationCache::clear_minification( $current_blog_id );
			//clear normal cache
			Breeze_PurgeCache::breeze_cache_flush( $flush_cache, true, true );
			//clear varnish cache
			$this->breeze_clear_varnish();
			$url            = get_home_url();
			$list_of_urls   = array();
			$list_of_urls[] = trailingslashit( $url );
			Breeze_CloudFlare_Helper::reset_all_cache( $list_of_urls );
			Breeze_PurgeCache::__flush_object_cache();
		}
	}

	/**
	 * Clear all varnish cache action.
	 */
	public function breeze_clear_varnish() {
		$main = new Breeze_PurgeVarnish();

		$is_network = ( is_network_admin() || ( ! empty( $_POST['is_network'] ) && 'true' === $_POST['is_network'] ) );
		$response   = null;

		if ( is_multisite() && $is_network ) {
			$sites = get_sites();
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				$homepage = home_url() . '/?breeze';
				$response = $main->purge_cache( $homepage );
				restore_current_blog();
			}
		} else {
			$homepage = home_url() . '/?breeze';
			$response = $main->purge_cache( $homepage );
		}

		if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
			return false;
		} else {
			return true;
		}
	}
}

add_action(
	'init',
	function () {
		$admin = new Breeze_Admin();
	},
	0
);
