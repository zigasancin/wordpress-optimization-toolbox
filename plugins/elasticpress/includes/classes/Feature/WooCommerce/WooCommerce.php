<?php
/**
 * ElasticPress WooCommerce feature
 *
 * @since  2.1
 * @package elasticpress
 */

namespace ElasticPress\Feature\WooCommerce;

use ElasticPress\Feature;
use ElasticPress\FeatureRequirementsStatus;
use ElasticPress\Indexables;
use ElasticPress\IndexHelper;
use ElasticPress\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce feature class
 */
class WooCommerce extends Feature {
	/**
	 * If enabled, receive the OrdersAutosuggest object instance
	 *
	 * @since 4.7.0
	 * @var null|OrdersAutosuggest
	 */
	public $orders_autosuggest = null;

	/**
	 * Receive the Products object instance
	 *
	 * @since 4.7.0
	 * @var null|Products
	 */
	public $products = null;

	/**
	 * Receive the Orders object instance
	 *
	 * @since 4.5.0
	 * @var null|Orders
	 */
	public $orders = null;

	/**
	 * Initialize feature setting it's config
	 *
	 * @since  3.0
	 */
	public function __construct() {
		$this->slug = 'woocommerce';

		$this->requires_install_reindex = true;

		$this->setting_requires_install_reindex = 'orders';

		$this->available_during_installation = true;

		$this->default_settings = [
			'orders' => '0',
		];

		$this->orders             = new Orders( $this );
		$this->products           = new Products( $this );
		$this->orders_autosuggest = new OrdersAutosuggest( $this );

		parent::__construct();
	}

	/**
	 * Sets i18n strings.
	 *
	 * @return void
	 * @since 5.2.0
	 */
	public function set_i18n_strings(): void {
		$this->title = esc_html__( 'WooCommerce', 'elasticpress' );

		$this->summary = '<p>' . __( 'Most caching and performance tools can’t keep up with the nearly infinite ways your visitors might filter or navigate your products. No matter how many products, filters, or customers you have, ElasticPress will keep your online store performing quickly. If used in combination with the Protected Content feature, ElasticPress will also accelerate order searches and back end product management.', 'elasticpress' ) . '</p>';

		$this->docs_url = __( 'https://www.elasticpress.io/documentation/article/configuring-elasticpress-via-the-plugin-dashboard/#woocommerce', 'elasticpress' );
	}

	/**
	 * Setup all feature filters
	 *
	 * @since  2.1
	 */
	public function setup() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		add_action( 'switch_blog', [ $this, 'setup_or_tear_down' ] );

		add_filter( 'ep_integrate_search_queries', [ $this, 'disallow_coupons' ], 10, 2 );

		$this->products->setup();
		$this->orders->setup();
		$this->orders_autosuggest->setup();
	}

	/**
	 * Setup or tear down the functionality depending on the plugin being active for the current site.
	 *
	 * If the site wasn't initialized yet (it does not have its database tables created) we skip it.
	 *
	 * @since 5.0.0
	 * @param int $blog_id Blog ID
	 * @return void
	 */
	public function setup_or_tear_down( $blog_id ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( wp_is_site_initialized( $blog_id ) && \is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$this->setup();
		} else {
			$this->tear_down();
		}
	}

	/**
	 * Un-setup all feature filters
	 *
	 * @since 5.0.0
	 */
	public function tear_down() {
		remove_filter( 'ep_integrate_search_queries', [ $this, 'disallow_coupons' ] );

		$this->products->tear_down();
		$this->orders->tear_down();
		$this->orders_autosuggest->tear_down();
	}

	/**
	 * Given a WP_Query object, return its search term (if any)
	 *
	 * This method also accounts for the `'search'` parameter used by
	 * WooCommerce, in addition to the regular `'s'` parameter.
	 *
	 * @param \WP_Query $query The WP_Query object
	 * @return string
	 */
	public function get_search_term( \WP_Query $query ): string {
		$search = $query->get( 'search' );
		return ( ! empty( $search ) ) ? $search : $query->get( 's', '' );
	}

	/**
	 * Make search coupons don't go through ES
	 *
	 * @param  bool     $enabled Coupons enabled or not
	 * @param  WP_Query $query WP Query
	 * @since  4.7.0
	 * @return bool
	 */
	public function disallow_coupons( $enabled, $query ) {
		if ( is_admin() ) {
			return $enabled;
		}

		if ( 'shop_coupon' === $query->get( 'post_type' ) && empty( $query->query_vars['ep_integrate'] ) ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Output feature box long
	 *
	 * @since 2.1
	 */
	public function output_feature_box_long() {
		?>
		<p><?php esc_html_e( 'Most caching and performance tools can’t keep up with the nearly infinite ways your visitors might filter or navigate your products. No matter how many products, filters, or customers you have, ElasticPress will keep your online store performing quickly. If used in combination with the Protected Content feature, ElasticPress will also accelerate order searches and back end product management.', 'elasticpress' ); ?></p>
		<?php
	}

	/**
	 * Determine WC feature reqs status
	 *
	 * @since  2.2
	 * @return EP_Feature_Requirements_Status
	 */
	public function requirements_status() {
		$status = new FeatureRequirementsStatus( 0 );

		if ( ! class_exists( 'WooCommerce' ) ) {
			$status->code    = 2;
			$status->message = esc_html__( 'WooCommerce not installed.', 'elasticpress' );
		}

		return $status;
	}

	/**
	 * Determines whether or not ES should be integrating with the provided query
	 *
	 * @param \WP_Query $query Query we might integrate with
	 *
	 * @return bool
	 */
	public function should_integrate_with_query( $query ) {
		// Lets make sure this doesn't interfere with the CLI
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		}

		if ( defined( 'WC_API_REQUEST' ) && WC_API_REQUEST ) {
			return false;
		}

		if ( isset( $query->query_vars['ep_integrate'] ) && ! filter_var( $query->query_vars['ep_integrate'], FILTER_VALIDATE_BOOLEAN ) ) {
			return false;
		}

		/**
		 * Filter to skip WP Query integration
		 *
		 * @hook ep_skip_query_integration
		 * @param  {bool} $skip True to skip
		 * @param  {WP_Query} $query WP Query to evaluate
		 * @return  {bool} New skip value
		 */
		if ( apply_filters( 'ep_skip_query_integration', false, $query ) ) {
			return false;
		}

		if ( ! Utils\is_integrated_request( $this->slug ) ) {
			return false;
		}

		/**
		 * Do nothing for single product queries
		 */
		$product_name = $query->get( 'product', false );
		if ( ! empty( $product_name ) || $query->is_single() ) {
			return false;
		}

		/**
		 * ElasticPress does not yet support post_parent queries
		 */
		$post_parent = $query->get( 'post_parent', false );
		if ( ! empty( $post_parent ) ) {
			return false;
		}

		/**
		 * If this is just a preview, let's not use Elasticsearch.
		 */
		if ( $query->get( 'preview', false ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Set the `settings_schema` attribute
	 *
	 * @since 5.0.0
	 */
	protected function set_settings_schema() {
		/**
		 * Filters the WooCommerce Settings schema.
		 *
		 * @since 5.1.0
		 * @hook ep_woocommerce_settings_schema
		 * @param {array} $settings_schema WooCommerce feature settings schema
		 * @return {array} $settings_schema
		 */
		$this->settings_schema = apply_filters( 'ep_woocommerce_settings_schema', $this->settings_schema );
	}

	/**
	 * DEPRECATED. Dashboard WooCommerce settings
	 *
	 * @since 4.5.0
	 * @deprecated 5.1.0
	 */
	public function output_feature_box_settings() {
		_doing_it_wrong(
			__METHOD__,
			esc_html__( 'Settings are now generated via the set_settings_schema() method.', 'elasticpress' ),
			'5.0.0'
		);
	}

	/**
	 * DEPRECATED. Whether orders autosuggest is available or not
	 *
	 * @since 4.5.0
	 * @deprecated 5.1.0
	 * @return boolean
	 */
	public function is_orders_autosuggest_available(): bool {
		_deprecated_function( __METHOD__, '5.1.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders_autosuggest->is_available()" );
		return $this->orders_autosuggest->is_available();
	}

	/**
	 * DEPRECATED. Whether orders autosuggest is enabled or not
	 *
	 * @since 4.5.0
	 * @deprecated 5.1.0
	 * @return boolean
	 */
	public function is_orders_autosuggest_enabled(): bool {
		_deprecated_function( __METHOD__, '5.1.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders_autosuggest->is_enabled()" );
		return $this->orders_autosuggest->is_enabled();
	}

	/**
	 * DEPRECATED. Translate args to ElasticPress compat format. This is the meat of what the feature does
	 *
	 * @param  \WP_Query $query WP Query
	 * @since  2.1
	 * @deprecated 4.7.0
	 */
	public function translate_args( $query ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->translate_args() OR \ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders->translate_args()" );
		$this->products->translate_args( $query );
		$this->orders->translate_args( $query );
	}

	/**
	 * DEPRECATED. Fetch the ES related meta mapping for orderby
	 *
	 * @param array $meta_key The meta key to get the mapping for.
	 * @since  2.1
	 * @deprecated 4.7.0
	 * @return string    The mapped meta key.
	 */
	public function get_orderby_meta_mapping( $meta_key ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->get_orderby_meta_mapping()" );
		return $this->products->get_orderby_meta_mapping( $meta_key );
	}

	/**
	 * DEPRECATED. Remove the author_name from search fields.
	 *
	 * @param array $search_fields Array of search fields.
	 * @since  3.0
	 * @deprecated 4.7.0
	 * @return array
	 */
	public function remove_author( $search_fields ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->remove_author()" );
		return $this->products->remove_author( $search_fields );
	}

	/**
	 * DEPRECATED. Index WooCommerce meta
	 *
	 * @param   array $meta Existing post meta.
	 * @param   array $post Post arguments array.
	 * @deprecated 4.7.0
	 * @since   2.1
	 * @return  array
	 */
	public function whitelist_meta_keys( $meta, $post ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->allow_meta_keys() AND/OR \ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders->allow_meta_keys()" );
		return array_unique(
			array_merge(
				$this->products->allow_meta_keys( $meta ),
				$this->orders->allow_meta_keys( $meta )
			)
		);
	}

	/**
	 * DEPRECATED. Index WooCommerce taxonomies
	 *
	 * @param   array $taxonomies Index taxonomies array.
	 * @param   array $post Post properties array.
	 * @deprecated 4.7.0
	 * @since   2.1
	 * @return  array
	 */
	public function whitelist_taxonomies( $taxonomies, $post ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->sync_taxonomies()" );
		return $this->products->sync_taxonomies( $taxonomies );
	}

	/**
	 * DEPRECATED. Returns the WooCommerce-oriented post types in admin that EP will search
	 *
	 * @since 4.4.0
	 * @deprecated 4.7.0
	 * @return mixed|void
	 */
	public function get_admin_searchable_post_types() {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders->get_admin_searchable_post_types()" );
		return $this->orders->get_admin_searchable_post_types();
	}

	/**
	 * DEPRECATED. Make search coupons don't go through ES
	 *
	 * @param  bool     $enabled Coupons enabled or not
	 * @param  WP_Query $query WP Query
	 * @since  2.1
	 * @deprecated 4.7.0
	 * @return bool
	 */
	public function blacklist_coupons( $enabled, $query ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->disallow_coupons()" );
		return $this->disallow_coupons( $enabled, $query );
	}

	/**
	 * DEPRECATED. Allow order creations on the front end to get synced
	 *
	 * @since  2.1
	 * @param  bool $override Original order perms check value
	 * @deprecated 4.7.0
	 * @param  int  $post_id Post ID
	 * @return bool
	 */
	public function bypass_order_permissions_check( $override, $post_id ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders->price_filter()" );
		return $this->orders->bypass_order_permissions_check( $override, $post_id );
	}

	/**
	 * DEPRECATED. Sets WooCommerce meta search fields to an empty array if we are integrating the main query with ElasticSearch
	 *
	 * WooCommerce calls this action as part of its own callback on parse_query. We add this filter only if the query
	 * is integrated with ElasticSearch.
	 * If we were to always return array() on this filter, we'd break admin searches when WooCommerce module is activated
	 * without the Protected Content Module
	 *
	 * @deprecated 4.7.0
	 * @param \WP_Query $query Current query
	 */
	public function maybe_hook_woocommerce_search_fields( $query ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders->maybe_hook_woocommerce_search_fields()" );
		return $this->orders->maybe_hook_woocommerce_search_fields( $query );
	}

	/**
	 * DEPRECATED. Enhance WooCommerce search order by order id, email, phone number, name, etc..
	 * What this function does:
	 * 1. Reverse the woocommerce shop_order_search_custom_fields query
	 * 2. If the search key is integer and it is an Order Id, just query with post__in
	 * 3. If the search key is integer but not an order id ( might be phone number ), use ES to find it
	 *
	 * @param WP_Query $wp WP Query
	 * @deprecated 4.7.0
	 * @since  2.3
	 */
	public function search_order( $wp ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders->search_order()" );
		return $this->orders->search_order( $wp );
	}

	/**
	 * DEPRECATED. Add order items as a searchable string.
	 *
	 * This mimics how WooCommerce currently does in the order_itemmeta
	 * table. They combine the titles of the products and put them in a
	 * meta field called "Items".
	 *
	 * @since 2.4
	 *
	 * @param array      $post_args Post arguments
	 * @param string|int $post_id Post id
	 * @deprecated 4.7.0
	 * @return array
	 */
	public function add_order_items_search( $post_args, $post_id ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders->add_order_items_search()" );
		return $this->orders->add_order_items_search( $post_args, $post_id );
	}

	/**
	 * DEPRECATED. Add WooCommerce Product Attributes to EP Facets.
	 *
	 * @param array $taxonomies Taxonomies array
	 * @deprecated 4.7.0
	 * @return array
	 */
	public function add_product_attributes( $taxonomies = [] ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->add_product_attributes()" );
		return $this->products->add_product_attributes( $taxonomies );
	}

	/**
	 * DEPRECATED. Add WooCommerce Fields to the Weighting Dashboard.
	 *
	 * @since 3.x
	 * @deprecated 4.7.0
	 * @param array  $fields    Current weighting fields.
	 * @param string $post_type Current post type.
	 * @return array            New fields.
	 */
	public function add_product_attributes_to_weighting( $fields, $post_type ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->add_product_attributes_to_weighting()" );
		return $this->products->add_product_attributes_to_weighting( $fields, $post_type );
	}

	/**
	 * DEPRECATED. Add WooCommerce Fields to the default values of the Weighting Dashboard.
	 *
	 * @since 3.x
	 * @deprecated 4.7.0
	 * @param array  $defaults  Default values for the post type.
	 * @param string $post_type Current post type.
	 * @return array
	 */
	public function add_product_default_post_type_weights( $defaults, $post_type ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->add_product_default_post_type_weights()" );
		return $this->products->add_product_default_post_type_weights( $defaults, $post_type );
	}

	/**
	 * DEPRECATED. Add WC post type to autosuggest
	 *
	 * @param array $post_types Array of post types (e.g. post, page).
	 * @since  2.6
	 * @deprecated 4.7.0
	 * @return array
	 */
	public function suggest_wc_add_post_type( $post_types ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->suggest_wc_add_post_type()" );
		return $this->products->suggest_wc_add_post_type( $post_types );
	}

	/**
	 * DEPRECATED. Modifies main query to allow filtering by price with WooCommerce "Filter by price" widget.
	 *
	 * @param  array    $args ES args
	 * @param  array    $query_args WP_Query args
	 * @param  WP_Query $query WP_Query object
	 * @since  3.2
	 * @deprecated 4.7.0
	 * @return array
	 */
	public function price_filter( $args, $query_args, $query ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->price_filter()" );
		return $this->products->price_filter( $args, $query_args, $query );
	}

	/**
	 * DEPRECATED. Prevent order fields from being removed.
	 *
	 * When Protected Content is enabled, all posts with password have their content removed.
	 * This can't happen for orders, as the order key is added in that field.
	 *
	 * @see https://github.com/10up/ElasticPress/issues/2726
	 *
	 * @since 4.2.0
	 * @deprecated 4.7.0
	 * @param bool  $skip      Whether the password protected content should have their content, and meta removed
	 * @param array $post_args Post arguments
	 * @return bool
	 */
	public function keep_order_fields( $skip, $post_args ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->orders->keep_order_fields()" );
		return $this->orders->keep_order_fields( $skip, $post_args );
	}

	/**
	 * DEPRECATED. Add a new `_variations_skus` meta field to the product to be indexed in Elasticsearch.
	 *
	 * @since 4.2.0
	 * @deprecated 4.7.0
	 * @param array   $post_meta Post meta
	 * @param WP_Post $post      Post object
	 * @return array
	 */
	public function add_variations_skus_meta( $post_meta, $post ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->add_variations_skus_meta()" );
		return $this->products->add_variations_skus_meta( $post_meta, $post );
	}

	/**
	 * DEPRECATED. Integrate ElasticPress with the WooCommerce Admin Product List.
	 *
	 * WooCommerce uses its `WC_Admin_List_Table_Products` class to control that screen. This
	 * function adds all necessary hooks to bypass the default behavior and integrate with ElasticPress.
	 * By default, WC runs a SQL query to get the Product IDs that match the list criteria and passes
	 * that list of IDs to the main WP_Query. This integration changes that process to a single query, run
	 * by ElasticPress.
	 *
	 * @since 4.2.0
	 * @deprecated 4.7.0
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	public function admin_product_list_request_query( $query_vars ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->admin_product_list_request_query()" );
		return $this->products->admin_product_list_request_query( $query_vars );
	}

	/**
	 * DEPRECATED. Apply the necessary changes to WP_Query in WooCommerce Admin Product List.
	 *
	 * @param WP_Query $query The WP Query being executed.
	 * @deprecated 4.7.0
	 */
	public function translate_args_admin_products_list( $query ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->price_filter()" );
		$this->products->translate_args_admin_products_list( $query );
	}

	/**
	 * DEPRECATED. Depending on the number of products display an admin notice in the custom sort screen for WooCommerce Products
	 *
	 * @since 4.4.0
	 * @deprecated 4.7.0
	 * @param array $notices Current ElasticPress admin notices
	 * @return array
	 */
	public function maybe_display_notice_about_product_ordering( $notices ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->maybe_display_notice_about_product_ordering()" );
		return $this->products->maybe_display_notice_about_product_ordering( $notices );
	}

	/**
	 * DEPRECATED. Conditionally resync products after applying a custom order.
	 *
	 * @since 4.4.0
	 * @deprecated 4.7.0
	 * @param int   $sorting_id  ID of post dragged and dropped
	 * @param array $menu_orders Post IDs and their new menu_order value
	 */
	public function action_sync_on_woocommerce_sort_single( $sorting_id, $menu_orders ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->action_sync_on_woocommerce_sort_single()" );
		return $this->products->action_sync_on_woocommerce_sort_single( $sorting_id, $menu_orders );
	}

	/**
	 * DEPRECATED. Add weight by date settings related to WooCommerce
	 *
	 * @since 4.6.0
	 * @deprecated 4.7.0
	 * @param array $settings Current settings.
	 */
	public function add_weight_settings_search( $settings ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->add_weight_settings_search()" );
		$this->products->add_weight_settings_search( $settings );
	}

	/**
	 * DEPRECATED. Conditionally disable decaying by date based on WooCommerce Decay settings.
	 *
	 * @since 4.6.0
	 * @deprecated 4.7.0
	 * @param bool  $is_decaying_enabled Whether decay by date is enabled or not
	 * @param array $settings            Settings
	 * @param array $args                WP_Query args
	 * @return bool
	 */
	public function maybe_disable_decaying( $is_decaying_enabled, $settings, $args ) {
		_deprecated_function( __METHOD__, '4.7.0', "\ElasticPress\Features::factory()->get_registered_feature( 'woocommerce' )->products->maybe_disable_decaying()" );
		return $this->products->maybe_disable_decaying( $is_decaying_enabled, $settings, $args );
	}
}
