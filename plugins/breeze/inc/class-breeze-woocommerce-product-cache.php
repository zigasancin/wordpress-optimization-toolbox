<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

/**
 * Class Breeze_Woocommerce_Product_Cache
 *
 * This class is responsible for recreating the cache for order products to refresh the stock value
 * when a new order is placed. It also handles the clearing of product page cache and related archive
 * cache when necessary.
 */
class Breeze_Woocommerce_Product_Cache {
	function __construct() {
		// When a new order is placed.
		add_action( 'woocommerce_checkout_order_processed', array( &$this, 'recreate_cache_for_products' ), 99, 3 );
		// When an order status is changed.
		add_action( 'woocommerce_order_status_changed', array( &$this, 'recreate_cache_for_products' ), 99, 3 );
	}

	/**
	 * When a new order is placed we must re-create the cache for the order
	 * products to refresh the stock value.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @since 1.1.10
	 */
	public function recreate_cache_for_products( $order_id, $posted_data, $order ) {

		if ( ! empty( $order_id ) ) {

			// Checks if the Varnish server is ON.
			$do_varnish_purge = is_varnish_cache_started();

			// fetch the order data.
			$order_id = absint( $order_id );
			// Get WC order object.
			$order = new WC_Order( $order_id );
			// Fetch the order products.
			$items = $order->get_items();

			$product_list_cd   = array();
			$change_stock_hide = false;
			$is_no_stock       = false;
			$archive_urls      = array();
			// Get shop archive
			$archive_urls[] = get_permalink( wc_get_page_id( 'shop' ) );

			// yes === Hide out of stock items from the catalog
			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
				$change_stock_hide = true;
			}

			if ( ! empty( $items ) ) {
				foreach ( $items as $item_id => $item_product ) {

					$product_id = $item_product->get_product_id();
					if ( ! is_numeric( $product_id ) ) {
						continue;
					}
					$product_id = absint( $product_id );
					$product    = wc_get_product( $product_id );

					if ( ! is_object( $product ) || ! $product instanceof WC_Product ) {
						continue;
					}

					$get_stock_quantity = $product->get_stock_quantity();
					$stock_no           = 0;
					if ( is_numeric( $get_stock_quantity ) ) {
						$stock_no = intval( $get_stock_quantity ) - 1;
					}

					$product_archives = array();

					if ( true === $product->managing_stock() && 1 > $stock_no ) { // if stock is 0
						$is_no_stock = true;
					}

					// Clear product page cache
					if ( ! empty( $product_id ) ) {
						$url_path          = get_permalink( $product_id );
						$product_list_cd[] = $url_path;
						// Clear Varnish server cache for this URL.
						breeze_varnish_purge_cache( $url_path, $do_varnish_purge );
					}

					if ( ! empty( $product_id ) ) {
						// Get related archive terms permalinks for the product.
						$product_archives = $this->get_product_related_archive( $product_id );
						// Merge the archive URLs.
						if ( ! empty( $product_archives ) ) {
							$archive_urls = array_merge( $archive_urls, $product_archives );
						}
					}
				}

				if ( ! empty( $archive_urls ) ) {
					foreach ( $archive_urls as $url ) {
						global $wp_filesystem;
						// Clear the cache for the product page.
						if ( $wp_filesystem->exists( breeze_get_cache_base_path() . hash( 'sha512', $url ) ) ) {
							$wp_filesystem->rmdir( breeze_get_cache_base_path() . hash( 'sha512', $url ), true );
						}
						// Clear Varnish server cache for this URL.
						breeze_varnish_purge_cache( $url, $do_varnish_purge );
					}
				}

				if ( true === $is_no_stock && true === $change_stock_hide ) {

					$home_url          = trailingslashit( home_url() );
					$shop_page         = trailingslashit( wc_get_page_permalink( 'shop' ) );
					$product_list_cd[] = $home_url;
					$product_list_cd[] = $shop_page;

					// Clear Varnish server cache for the home and shop page.
					breeze_varnish_purge_cache( $home_url, $do_varnish_purge );
					breeze_varnish_purge_cache( $shop_page, $do_varnish_purge );
				}

				if ( ! empty( $product_list_cd ) ) {
					// Clear Cloudflare cache for the product URLs.
					Breeze_CloudFlare_Helper::purge_cloudflare_cache_urls( $product_list_cd );
				}
			}
		}

	}

	/**
	 * Retrieves the related archive terms permalinks for a given product.
	 *
	 * This method checks if the "breeze-minify-html" option is set to true. If not,
	 * the method will return null. Then, it checks if the given product ID is numeric.
	 * If not, the method will return null.
	 *
	 * The method retrieves all the taxonomies associated with the product using the
	 * `get_post_taxonomies` function. For each taxonomy, it retrieves the term data
	 * using `wp_get_post_terms`. Then, it retrieves the term link for each term using
	 * the `get_term_link` function and adds it to the `$all_terms` array.
	 *
	 * @param int $product_id The ID of the product.
	 *
	 * @return array|null An array of related archive terms if the conditions are met,
	 *                    otherwise null.
	 */
	protected function get_product_related_archive( int $product_id ): ?array {
		if ( false === filter_var( Breeze_Options_Reader::get_option_value( 'breeze-minify-html' ), FILTER_VALIDATE_BOOLEAN ) ) {
			return null;
		}

		if ( ! is_numeric( $product_id ) ) {
			return null;
		}

		$all_terms = array();
		// Get all taxonomies for the product.
		$product_taxonomies = get_post_taxonomies( $product_id );
		// Get the term data for each taxonomy.
		foreach ( $product_taxonomies as $taxonomy ) {
			$term_data = wp_get_post_terms( $product_id, $taxonomy );
			foreach ( $term_data as $term ) {
				// Get the term link and add it to the array.
				$all_terms[] = get_term_link( $term );
			}
		}

		return $all_terms;
	}
}

// Initialize the class on init.
add_action(
	'init',
	function () {
		// Check if WooCommerce is active.
		if ( class_exists( 'WooCommerce' ) ) {
			// Initialize the class.
			new Breeze_Woocommerce_Product_Cache();
		}
	}
);


