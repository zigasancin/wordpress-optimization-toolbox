<?php
defined( 'ABSPATH' ) or die( 'Not allow!' );

/*
 * Class for E-commerce Cache
 */
class Breeze_Ecommerce_Cache {
	public function __construct() {
		add_action( 'activated_plugin', array( $this, 'detect_ecommerce_activation' ) );
		add_action( 'deactivated_plugin', array( $this, 'detect_ecommerce_deactivation' ) );
		add_action( 'wp_loaded', array( $this, 'update_ecommerce_activation' ) );
	}

	// After woocommerce active,merge array disable page config
	public function detect_ecommerce_activation( $plugin ) {
		if ( 'woocommerce/woocommerce.php' == $plugin ) {
			update_option( 'breeze_ecommerce_detect', 1 );
		}
	}

	// Delete option detect when deactivate woo
	public function detect_ecommerce_deactivation( $plugin ) {
		if ( 'woocommerce/woocommerce.php' == $plugin ) {
			delete_option( 'breeze_ecommerce_detect' );
		}
	}

	// Update option when WooCommerce active
	public function update_ecommerce_activation() {
		$check = get_option( 'breeze_ecommerce_detect' );
		if ( stripos( $_SERVER['REQUEST_URI'], 'wc-setup&step=locale' ) !== false ) {
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once( ABSPATH . '/wp-admin/includes/file.php' );
				WP_Filesystem();
			}
			Breeze_ConfigCache::write_config_cache();
		}
		if ( ! empty( $check ) ) {
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once( ABSPATH . '/wp-admin/includes/file.php' );
				WP_Filesystem();
			}
			Breeze_ConfigCache::write_config_cache();
			update_option( 'breeze_ecommerce_detect', 0 );
		}
	}


	public function exclude_give_pages() {
		$urls  = array();
		$regex = '*';

		if ( function_exists( 'give_get_settings' ) && defined( 'GIVE_VERSION' ) ) {
			$give_shop_settings = give_get_settings();
			if ( ! empty( $give_shop_settings ) ) {

				if ( isset( $give_shop_settings['success_page'] ) ) {
					$success_page_id = absint( $give_shop_settings['success_page'] );

					if ( ! empty( $success_page_id ) ) {
						$urls[] = $this->get_basic_urls( $success_page_id, $regex );
						// Get url through multi-languages plugin
						$urls = $this->get_translate_urls( $urls, $success_page_id, $regex );
					}
				}

				if ( isset( $give_shop_settings['history_page'] ) ) {
					$history_page_id = absint( $give_shop_settings['history_page'] );

					if ( ! empty( $history_page_id ) ) {
						$urls[] = $this->get_basic_urls( $history_page_id, $regex );
						// Get url through multi-languages plugin
						$urls = $this->get_translate_urls( $urls, $history_page_id, $regex );
					}
				}

				if ( isset( $give_shop_settings['failure_page'] ) ) {
					$failure_page_id = absint( $give_shop_settings['failure_page'] );

					if ( ! empty( $failure_page_id ) ) {
						$urls[] = $this->get_basic_urls( $failure_page_id, $regex );
						// Get url through multi-languages plugin
						$urls = $this->get_translate_urls( $urls, $failure_page_id, $regex );
					}
				}

				// Process urls to return
				if ( ! empty( $urls ) ) {
					$urls = array_unique( $urls );
					$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
				}
			}
		}

		return $urls;
	}

	/**
	 * Excludes from cache for Easy Digital Downloads pages.
	 *
	 * @return array
	 * @since 1.1.7
	 * @access public
	 */
	public function exclude_edd_pages() {
		$urls  = array();
		$regex = '*';

		if ( function_exists( 'EDD' ) ) {
			$edd_settings = get_option( 'edd_settings' );
			if ( ! empty( $edd_settings ) && isset( $edd_settings['purchase_page'] ) ) {
				$checkout_page_id = absint( $edd_settings['purchase_page'] );
				if ( $checkout_page_id > 0 ) {
					$urls[] = $this->get_basic_urls( $checkout_page_id, $regex );
					// Get url through multi-languages plugin
					$urls = $this->get_translate_urls( $urls, $checkout_page_id, $regex );
				}
			}
			// Process urls to return
			$urls = array_unique( $urls );
			$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
		}

		return $urls;
	}

	/**
	 * Excludes from cache for Ecwid Ecommerce Shopping Cart.
	 *
	 * @return array
	 * @since 1.1.7
	 * @access public
	 */
	public function exclude_ecwid_store_pages() {
		$urls  = array();
		$regex = '*';

		if ( function_exists( 'ecwid_init_integrations' ) && defined( 'ECWID_PLUGIN_DIR' ) ) {
			$ecwid_store_page      = get_option( 'ecwid_store_page_id', 0 );
			$ecwid_last_store_page = get_option( 'ecwid_last_store_page_id', 0 );

			if ( ! empty( $ecwid_store_page ) ) {
				$urls[] = $this->get_basic_urls( $ecwid_store_page, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $ecwid_store_page, $regex );
			}

			if ( ! empty( $ecwid_last_store_page ) ) {
				$urls[] = $this->get_basic_urls( $ecwid_last_store_page, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $ecwid_last_store_page, $regex );
			}

			// Process urls to return
			if ( ! empty( $urls ) ) {
				$urls = array_unique( $urls );
				$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
			}
		}

		return $urls;
	}

	/**
	 * Excludes from cache pages from MemberPress.
	 *
	 * @return array
	 * @since 1.1.7
	 * @access public
	 */
	public function exclude_member_press_pages() {
		$urls  = array();
		$regex = '*';

		if ( class_exists( 'MeprJobs' ) && defined( 'MEPR_OPTIONS_SLUG' ) ) {
			$member_press_options = get_option( MEPR_OPTIONS_SLUG );

			if ( ! empty( $member_press_options ) ) {
				$account_page_id   = isset( $member_press_options['account_page_id'] ) ? $member_press_options['account_page_id'] : 0;
				$login_page_id     = isset( $member_press_options['login_page_id'] ) ? $member_press_options['login_page_id'] : 0;
				$thank_you_page_id = isset( $member_press_options['thankyou_page_id'] ) ? $member_press_options['thankyou_page_id'] : 0;

				if ( ! empty( $account_page_id ) ) {
					$urls[] = $this->get_basic_urls( $account_page_id, $regex );
					// Get url through multi-languages plugin
					$urls = $this->get_translate_urls( $urls, $account_page_id, $regex );
				}

				if ( ! empty( $login_page_id ) ) {
					$urls[] = $this->get_basic_urls( $login_page_id, $regex );
					// Get url through multi-languages plugin
					$urls = $this->get_translate_urls( $urls, $login_page_id, $regex );
				}

				if ( ! empty( $thank_you_page_id ) ) {
					$urls[] = $this->get_basic_urls( $thank_you_page_id, $regex );
					// Get url through multi-languages plugin
					$urls = $this->get_translate_urls( $urls, $thank_you_page_id, $regex );
				}

				// Process urls to return
				if ( ! empty( $urls ) ) {
					$urls = array_unique( $urls );
					$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
				}
			}
		}

		return $urls;
	}

	/**
	 * Excludes from cache pages from Funnel Builder by CartFlows – Create High Converting Sales Funnels For WordPress.
	 *
	 * @return array
	 * @since 1.1.7
	 * @access public
	 */
	public function exclude_cart_flows_pages() {
		$urls  = array();
		$regex = '*';
		if ( class_exists( 'Cartflows_Loader' ) && defined( 'CARTFLOWS_FILE' ) ) {
			$cart_flow_settings = get_option( '_cartflows_permalink', '' );

			if ( ! empty( $cart_flow_settings ) ) {
				$permalink           = isset( $cart_flow_settings['permalink'] ) ? trim( $cart_flow_settings['permalink'] ) : '';
				$permalink_flow_base = isset( $cart_flow_settings['permalink_flow_base'] ) ? trim( $cart_flow_settings['permalink_flow_base'] ) : '';
				$permalink_structure = isset( $cart_flow_settings['permalink_structure'] ) ? trim( $cart_flow_settings['permalink_structure'] ) : '';
				if ( $permalink === $permalink_flow_base ) {
					$urls[] = "/{$permalink}/{$regex}";
				} else {
					$urls[] = "/{$permalink}/{$regex}";
					$urls[] = "/{$permalink_flow_base}/{$regex}";
				}
			}
		}
		// Process urls to return
		if ( ! empty( $urls ) ) {
			$urls = array_unique( $urls );
			$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
		}

		return $urls;
	}

	/**
	 * WP eCommerce pages excluded from cache (cart,checkout...)
	 *
	 * @return array
	 * @since 1.1.7
	 * @access public
	 */
	public function exclude_wp_e_commerce_pages() {
		$urls  = array();
		$regex = '*';

		if ( class_exists( 'WP_eCommerce' ) ) {
			$e_commerce_settings = get_option( 'wpsc_shortcode_page_ids', '' );

			if ( ! empty( $e_commerce_settings ) ) {
				$products_page       = isset( $e_commerce_settings['[productspage]'] ) ? $e_commerce_settings['[productspage]'] : 0;
				$shopping_cart       = isset( $e_commerce_settings['[shoppingcart]'] ) ? $e_commerce_settings['[shoppingcart]'] : 0;
				$transaction_results = isset( $e_commerce_settings['[transactionresults]'] ) ? $e_commerce_settings['[transactionresults]'] : 0;
				$user_log            = isset( $e_commerce_settings['[userlog]'] ) ? $e_commerce_settings['[userlog]'] : 0;

				if ( ! empty( $products_page ) ) {
					$urls[] = $this->get_basic_urls( $products_page, $regex );
					// Get url through multi-languages plugin
					$urls = $this->get_translate_urls( $urls, $products_page, $regex );
				}

				if ( ! empty( $shopping_cart ) ) {
					$urls[] = $this->get_basic_urls( $shopping_cart, $regex );
					// Get url through multi-languages plugin
					$urls = $this->get_translate_urls( $urls, $shopping_cart, $regex );
				}

				if ( ! empty( $transaction_results ) ) {
					$urls[] = $this->get_basic_urls( $transaction_results, $regex );
					// Get url through multi-languages plugin
					$urls = $this->get_translate_urls( $urls, $transaction_results, $regex );
				}

				if ( ! empty( $user_log ) ) {
					$urls[] = $this->get_basic_urls( $user_log, $regex );
					// Get url through multi-languages plugin
					$urls = $this->get_translate_urls( $urls, $user_log, $regex );
				}

				// Process urls to return
				if ( ! empty( $urls ) ) {
					$urls = array_unique( $urls );
					$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
				}
			}
		}

		return $urls;
	}

	/**
	 * WP EasyCart plugin pages excluded form cache ( cart,account...)
	 * @return array
	 * @since 1.1.7
	 * @access public
	 */
	public function exclude_easy_cart_pages() {
		$urls  = array();
		$regex = '*';
		if ( defined( 'EC_PUGIN_NAME' ) && function_exists( 'wpeasycart_load_startup' ) ) {
			$account_page_id = get_option( 'ec_option_accountpage', 0 );
			$cart_page_id    = get_option( 'ec_option_cartpage', 0 );

			if ( ! empty( $account_page_id ) ) {
				$urls[] = $this->get_basic_urls( $account_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $account_page_id, $regex );
			}

			/**
			 * For EasyCart plugin, the cart and checkout page have the same slug.
			 * The only difference is the get value ( e.g. cart/?ec_page=checkout_info )
			 */
			if ( ! empty( $cart_page_id ) ) {
				$urls[] = $this->get_basic_urls( $cart_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $cart_page_id, $regex );
			}

			// Process urls to return
			if ( ! empty( $urls ) ) {
				$urls = array_unique( $urls );
				$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
			}
		}

		return $urls;
	}

	/**
	 * Excludes from cache cart,checkout and other important pages for BigCommerce.
	 *
	 * @return array
	 * @since 1.1.7
	 * @access public
	 */
	public function exclude_big_commerce_pages() {
		$urls  = array();
		$regex = '*';
		if ( function_exists( 'bigcommerce' ) ) {
			$bigcommerce_cart_page_id = get_option( 'bigcommerce_cart_page_id', 0 );
			if ( ! empty( $bigcommerce_cart_page_id ) ) {
				$urls[] = $this->get_basic_urls( $bigcommerce_cart_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $bigcommerce_cart_page_id, $regex );
			}

			$bigcommerce_checkout_page_id = get_option( 'bigcommerce_checkout_page_id', 0 );
			if ( ! empty( $bigcommerce_checkout_page_id ) ) {
				$urls[] = $this->get_basic_urls( $bigcommerce_checkout_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $bigcommerce_checkout_page_id, $regex );
			}

			$bigcommerce_account_page_id = get_option( 'bigcommerce_account_page_id', 0 );
			if ( ! empty( $bigcommerce_account_page_id ) ) {
				$urls[] = $this->get_basic_urls( $bigcommerce_account_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $bigcommerce_account_page_id, $regex );
			}

			$bigcommerce_address_page_id = get_option( 'bigcommerce_address_page_id', 0 );
			if ( ! empty( $bigcommerce_address_page_id ) ) {
				$urls[] = $this->get_basic_urls( $bigcommerce_address_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $bigcommerce_address_page_id, $regex );
			}

			$bigcommerce_gift_balance_page_id = get_option( 'bigcommerce_gift_balance_page_id', 0 );
			if ( ! empty( $bigcommerce_gift_balance_page_id ) ) {
				$urls[] = $this->get_basic_urls( $bigcommerce_gift_balance_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $bigcommerce_gift_balance_page_id, $regex );
			}

			$bigcommerce_gift_certificate_page_id = get_option( 'bigcommerce_gift_certificate_page_id', 0 );
			if ( ! empty( $bigcommerce_gift_certificate_page_id ) ) {
				$urls[] = $this->get_basic_urls( $bigcommerce_gift_certificate_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $bigcommerce_gift_certificate_page_id, $regex );
			}

			$bigcommerce_login_page_id = get_option( 'bigcommerce_login_page_id', 0 );
			if ( ! empty( $bigcommerce_login_page_id ) ) {
				$urls[] = $this->get_basic_urls( $bigcommerce_login_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $bigcommerce_login_page_id, $regex );
			}

			$bigcommerce_registration_page_id = get_option( 'bigcommerce_registration_page_id', 0 );
			if ( ! empty( $bigcommerce_registration_page_id ) ) {
				$urls[] = $this->get_basic_urls( $bigcommerce_registration_page_id, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $bigcommerce_registration_page_id, $regex );
			}

			// Process urls to return
			if ( ! empty( $urls ) ) {
				$urls = array_unique( $urls );
				$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
			}
		}

		return $urls;
	}

	/**
	 * Exclude pages from cache for the plugin BuddyBoss.
	 *
	 * @return array
	 */
	public function buddyboss_exclude_urls(): array {
		$urls  = array();
		$regex = '*';

		if ( !function_exists('bbp_get_current_user_id') || !function_exists('bbp_get_user_profile_url') ) {
			return $urls;
		}

		if ( class_exists( 'BuddyPress' ) ) {
			$user_id     = bbp_get_current_user_id();
			$url_profile = bbp_get_user_profile_url( $user_id );
			$url_profile = trailingslashit( $url_profile );

			$user = get_userdata( $user_id );
			if ( ! empty( $user->user_nicename ) ) {
				$user_nicename = $user->user_nicename;
				$url_profile   = str_replace( $user_nicename . '/', '', $url_profile );
			}

			$url_profile = parse_url( $url_profile, PHP_URL_PATH ) . $regex;
			#$url_profile .= '/profile/' . $regex;

			$urls[] = $url_profile;


			if ( ! empty( $urls ) ) {
				// Process urls to return
				$urls = array_unique( $urls );
				$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
			}
		}

		return $urls;
	}

	/**
	 * Take page id and checks if it's in the exluded pages list 
	 * when woocommerce is active the exluded pages currently are
	 * cart,checkout,myaccount.
	 * 
	 * @param int $page_id
	 * @return boolean $is_excluded_ecom_page
	 */
	public static function is_excluded_ecom_page( $page_id ) {

		$is_excluded_ecom_page = false;
		if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_page_id' ) ) {
			$cart_id      = wc_get_page_id( 'cart' );
			$checkout_id  = wc_get_page_id( 'checkout' );
			$myaccount_id = wc_get_page_id( 'myaccount' );
			if ( 
				$page_id == $cart_id
				|| $page_id == $checkout_id
				|| $page_id == $myaccount_id
			) {
				$is_excluded_ecom_page = true;
			}
		}
		return $is_excluded_ecom_page;

	}

	/**
	 * Exclude pages of e-commerce from cache
	 */
	public function ecommerce_exclude_pages() {
		$urls  = array();
		$regex = '*';

		if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_page_id' ) ) {
			$cardId      = wc_get_page_id( 'cart' );
			$checkoutId  = wc_get_page_id( 'checkout' );
			$myaccountId = wc_get_page_id( 'myaccount' );

			if ( $cardId > 0 && 'publish' === get_post_status( $cardId ) ) {
				$urls[] = $this->get_basic_urls( $cardId, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $cardId, $regex );
			}

			if ( $checkoutId > 0 && 'publish' === get_post_status( $checkoutId ) ) {
				$urls[] = $this->get_basic_urls( $checkoutId, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $checkoutId, $regex );
			}

			if ( $myaccountId > 0 && 'publish' === get_post_status( $myaccountId ) ) {
				$urls[] = $this->get_basic_urls( $myaccountId, $regex );
				// Get url through multi-languages plugin
				$urls = $this->get_translate_urls( $urls, $myaccountId, $regex );
			}

			// Process urls to return
			$urls = array_unique( $urls );
			$urls = array_map( array( $this, 'rtrim_urls' ), $urls );
		}

		return $urls;
	}

	/**
	 * Removes from cache WooCommerce Facebook Product Feed.
	 *
	 * @return array
	 * @since 1.1.8
	 * @access public
	 */
	public function wc_facebook_feed() {
		$urls = array();
		if ( class_exists( 'WC_Facebook_Loader' ) ) {
			if ( class_exists( 'SkyVerge\WooCommerce\Facebook\Products\Feed' ) ) {
			$urls[] = SkyVerge\WooCommerce\Facebook\Products\Feed::get_feed_data_url();
		}
			if ( class_exists( 'WooCommerce\Facebook\Products\Feed' ) ) {
				$urls[] = WooCommerce\Facebook\Products\Feed::get_feed_data_url();
			}
		}

		return $urls;
	}
	/*
	 * Return basic url without translate plugin
	 */
	public function get_basic_urls( $postID, $regex = null ) {
		$permalink = get_option( 'permalink_structure' );

		if ( ! empty( $permalink ) ) {
			// Custom URL structure
			$url           = parse_url( get_permalink( $postID ), PHP_URL_PATH );
			$home_url      = trailingslashit( get_home_url() );
			$home_url_path = parse_url( $home_url, PHP_URL_PATH );

			if( '/' === $url || $home_url_path === $url || $home_url === $url ) {
				$url = '/' . get_post_field( 'post_name', $postID ) . '/';
			}
		} else {
			$url = get_permalink( $postID );
		}

		return $url . $regex;
	}

	/*
	* Return translate url without translate plugin
	*/

	public function get_translate_urls( $urls, $postID, $regex = null ) {

		// WPML plugins
		if ( class_exists( 'SitePress' ) ) {
			global $sitepress;
			if ( isset( $sitepress ) ) {
				$active_languages = $sitepress->get_active_languages();

				if ( ! empty( $active_languages ) ) {
					$languages = array_keys( $active_languages );
					foreach ( $languages as $language ) {
						$translatedId = icl_object_id( $postID, 'page', false, $language );

						if ( empty( $translatedId ) ) {
							continue;
						}

						$urls[] = $this->get_basic_urls( $translatedId, $regex );
					}
				}
			}
		}

		// Polylang plugin
		if ( class_exists( 'Polylang' ) && function_exists( 'pll_languages_list' ) && function_exists( 'PLL' ) ) {
			$translatedId = pll_get_post_translations( $postID );

			if ( ! empty( $translatedId ) ) {
				foreach ( $translatedId as $id ) {
					$urls[] = $this->get_basic_urls( $id, $regex );
				}
			}
		}

		// qTranslate-x plugin
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'qtranslate-x/qtranslate.php' ) ) {
			global $q_config;
			if ( isset( $q_config ) && function_exists( 'qtranxf_convertURL' ) ) {
				$url = $this->get_basic_urls( $postID );

				if ( ! empty( $q_config['enabled_languages'] ) ) {
					foreach ( $q_config['enabled_languages'] as $language ) {
						$urls[] = qtranxf_convertURL( $url, $language, true );
					}
				}
			}
		}

		// WPGlobus Plugin
		if ( class_exists( 'WPGlobus' ) && class_exists( 'WPGlobus_Utils' ) ) {
			$enabled_languages = apply_filters( 'wpglobus_extra_languages', WPGlobus::Config()->enabled_languages, WPGlobus::Config()->language );
			$default_permalink = get_permalink( $postID );
			$config            = null;

			if ( ! empty( $default_permalink ) ) {

				foreach ( $enabled_languages as $language ) {

					// Skip default language, and loop for other languages.
					if ( $language != WPGlobus::Config()->language ) {

						if ( null === $config ) {
							// @codeCoverageIgnoreStart
							$config = WPGlobus::Config();
						}

						$urls[] = WPGlobus_Utils::localize_url( $default_permalink, $language, $config );
					}
				}
			}
		}

		// TranslatePress Plugin
		if ( class_exists( 'TRP_Translate_Press' ) ) {
			global $TRP_LANGUAGE;

			/**
			 * @see TRP_Url_Converter::get_url_for_language
			 * @see TRP_Machine_Translator::translate
			 */
			$current_language = array();
			$other_languages  = array();
			$page_link        = get_permalink( $postID );

			$trp = TRP_Translate_Press::get_trp_instance();

			$trp_languages       = $trp->get_component( 'languages' );
			$trp_settings        = $trp->get_component( 'settings' );
			$published_languages = $trp_languages->get_language_names( $trp_settings->get_settings()['publish-languages'] );
			$url_converter       = $trp->get_component( 'url_converter' );

			foreach ( $published_languages as $code => $name ) {
				if ( $code == $TRP_LANGUAGE ) {
					$current_language['code'] = $code;
					$current_language['name'] = $name;
				} else {
					$other_languages[ $code ] = $name;
				}
			}

			foreach ( $other_languages as $code => $name ) {

				$url_obj             = new \TranslatePress\Uri( $page_link );
				$processed_permalink = get_permalink( $postID );

				if ( $url_obj->isSchemeless() ) {
					$arguments = str_replace( trailingslashit( $processed_permalink ), '', trailingslashit( trailingslashit( home_url() ) . ltrim( $page_link, '/' ) ) );
				} else {
					$arguments = str_replace( $processed_permalink, '', $page_link );
				}
				if ( $arguments == $page_link ) {
					$arguments = '';
				}

				if ( null === $url_converter->get_lang_from_url_string( $page_link ) ) {
					$abs_home_url_obj         = new \TranslatePress\Uri( $url_converter->get_abs_home() );
					$new_url_obj              = $url_obj;
					$abs_home_considered_path = trim( str_replace( $abs_home_url_obj->getPath(), '', $url_obj->getPath() ), '/' );
					$new_url_obj->setPath( trailingslashit( trailingslashit( $abs_home_url_obj->getPath() ) . trailingslashit( $url_converter->get_url_slug( $code ) ) . $abs_home_considered_path ) );
					$new_url = $new_url_obj->getUri();
					if ( ! empty( $new_url ) ) {
						$urls[] = str_replace( $abs_home_url_obj->getUri(), '', $new_url );
					}
				}
			}
		}

		return $urls;
	}

	/*
	 * Remove '/' chacracter of end url
	 */
	public function rtrim_urls( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}

		return rtrim( $url, '/' );
	}

	public static function factory() {
		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}
