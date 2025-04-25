<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}
/**
 * Class used to check if user has any other cache plugins active.
 * If here are any then we need to display a message informing the user of possible issues.
 *
 * @since 1.1.1
 *
 * Class Breeze_Incompatibility_Plugins
 */
if ( ! class_exists( 'Breeze_Incompatibility_Plugins' ) ) {
	class Breeze_Incompatibility_Plugins {
		/**
		 * Used to hold the data needed to display in the notice.
		 * @var string HTML notification content.
		 */
		protected $notification_message = '';

		/**
		 * Contains the plugins list
		 *
		 * @var array current install plugins list.
		 */
		protected $plugins_list = array();

		/**
		 * Breeze_Incompatibility_Plugins constructor.
		 */
		function __construct() {
			/**
			 * Init the notification.
			 *
			 * @see https://developer.wordpress.org/reference/hooks/after_plugin_row_plugin_file/
			 */
			#add_action( 'after_plugin_row_' . BREEZE_BASENAME, array( &$this, 'prepare_and_display_notification_content' ), 15, 2 );
			add_action( 'admin_notices', array( &$this, 'prepare_and_display_notification_content' ), 15 );
			add_action( 'network_admin_notices', array( &$this, 'prepare_and_display_notification_content' ), 15 );

			add_action( 'wp_ajax_compatibility_warning_close', array( &$this, 'compatibility_warning_close' ) );
		}

		public function compatibility_warning_close() {
			$response            = array();
			$response['success'] = false;
			// Only administrator can close this notice.
			if ( false === breeze_is_restricted_access( true ) ) {
				$response['success'] = true;
				update_option( 'breeze_hide_notice', 'yes', 'no' );
			}
			wp_send_json( $response );
		}

		/**
		 * Display plugin conflicts list in plugins page.
		 *
		 * @param string $file Plugin basename.
		 * @param array $plugin_data Plugin information.
		 */
		public function prepare_and_display_notification_content( $file = '', $plugin_data = array() ) {
			$current_screen = get_current_screen(); // get the current WP screen

			// If we are on Network plugins or single site plugins screen, run the script.
			if ( ( ! empty( $current_screen ) && ( 'plugins-network' === $current_screen->base || 'plugins' === $current_screen->base ) ) ) {
				// Gather messages for incompatibility notice.
				$this->notification_message = $this->notification_for_incompatibility();
				// Future notices can be added and captured here.

				// Start the notice row.
				if ( ! empty( $this->notification_message ) ) {
					// Display catched notifications.
					echo $this->notification_message;
				}
				// End the notice row.

			}
		}

		/**
		 *
		 *
		 * @return false|string
		 */
		protected function notification_for_incompatibility() {
			// Fetch the list of processed incompatible/conflicting plugins.
			$incompatibility_list = $this->incompatibility_list();
			$notice_message       = '';
			$show_message         = true;

			$get_notice_rule = get_option( 'breeze_hide_notice', '' );

			$comparing_value = hash( 'sha512', wp_json_encode( $incompatibility_list ) );
			if ( 'yes' === $get_notice_rule ) {
				$get_old_values = get_option( 'breeze_show_incompatibility', '' );
				if ( $get_old_values !== $comparing_value ) {
					delete_option( 'breeze_hide_notice' );
					update_option( 'breeze_show_incompatibility', $comparing_value, 'no' );
				} else {
					$show_message = false;
				}
			} else {
				update_option( 'breeze_show_incompatibility', $comparing_value, 'no' );
			}

			if ( $show_message && ! empty( $incompatibility_list ) ) {
				// Build the HTML for the error notice.
				ob_start();
				require BREEZE_PLUGIN_DIR . 'views/html-notice-plugin-screen-incompatibilities.php';
				$notice_message = ob_get_contents();
				ob_get_clean();
			}

			return $notice_message;
		}

		protected function incompatibility_list() {
			global $status, $page, $s;

			// Current installed plugins
			$installed_plugins = $this->plugins_list();
			// Fetch the list of incompatible/conflicting plugins data
			$incompatible_plugins = $this->list_of_incompatible_plugins();
			$final_list           = array();
			$context              = $status;

			if ( ! empty( $incompatible_plugins ) && ! empty( $installed_plugins ) ) {
				foreach ( $incompatible_plugins as $plugin => $details ) {

					if ( isset( $installed_plugins[ $plugin ] ) ) {

						// Only do the operations if the given plugin is active.
						if ( is_plugin_active( $plugin ) ) {

							// Version that not compatible
							$warning_version = $details['warning_version'];
							// Need to see if the values is -1
							$warning_version_int = (int) $warning_version;
							$active_item         = $installed_plugins[ $plugin ];
							$is_warning          = false;

							// This means it's a specific version we need to be aware of.
							if ( - 1 !== $warning_version_int ) {
								$current_version = $active_item['Version']; // Current installed plugin version.
								$operator        = $details['compare_sign']; // Compare Operator
								$is_warning      = version_compare( $current_version, $warning_version, $operator );
							} elseif ( - 1 === $warning_version_int ) { // incompatible no matter the the version
								$is_warning = true;
							}

							$network_only_text = '';
							// If the plugin is marked as a warning
							if ( true === $is_warning ) {
								// Build the default message for incompatibility.
								$message = $active_item['Title'] . ' ' . esc_html__( 'Plugin', 'breeze' ) . ' ' . $active_item['Version'] . ' ' . esc_html__( 'is not compatible', 'breeze' ) . ' ';
								if ( ! empty( trim( $details['warning_message'] ) ) ) {
									// If a custom message exists, overwrite the default message.
									$message = trim( $details['warning_message'] );
								}
								$current_screen  = get_current_screen();
								$show_deactivate = false;
								if ( 'plugins-network' === $current_screen->base ) {
									if ( current_user_can( 'manage_network_plugins' ) ) {
										$show_deactivate = true;
									}
								} elseif ( 'plugins' === $current_screen->base ) {
									if ( current_user_can( 'deactivate_plugin', $plugin ) ) {
										$is_active               = is_plugin_active( $plugin );
										$restrict_network_active = ( is_multisite() && is_plugin_active_for_network( $plugin ) );
										$restrict_network_only   = ( is_multisite() && is_network_only_plugin( $plugin ) && ! $is_active );

										if ( ! $restrict_network_active && ! $restrict_network_only ) {
											$show_deactivate = true;
										}

										// Some plugins can only be disabled from Network if Multi-site
										if ( $restrict_network_active ) {
											$network_only_text = __( 'Network Active - deactivate from Network' );
										} elseif ( $restrict_network_only ) {
											$network_only_text = __( 'Network Only - deactivate from Network' );
										}
									}
								}
								// Build data for the notice HTML
								$final_list[] = array(
									'warning_message'      => $message,
									'safe_version_message' => ( ! empty( trim( $details['safe_version_message'] ) ) ? $details['safe_version_message'] : '' ),
									'display_deactivate_button' => $show_deactivate,
									'deactivate_url'       => wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . urlencode( $plugin ) . '&amp;plugin_status=' . $context . '&amp;paged=' . $page . '&amp;s=' . $s, 'deactivate-plugin_' . $plugin ),
									'is_network_only'      => $network_only_text,
								);

							}
						}
					}
				}
			}

			return $final_list;
		}

		/**
		 * Cache the plugins list.
		 *
		 * @return array Plugins of current WP install
		 */
		protected function plugins_list() {
			// If $plugins_list is empty, request the list again
			if ( empty( $this->plugins_list ) || ! is_array( $this->plugins_list ) ) {
				$this->plugins_list = get_plugins();
			}

			/* Example of an element from get_plugins()
			array(
				'breeze/breeze.php' => array(
					'WC requires at least' => '',
					'WC tested up to'      => '',
					'Woo'                  => '',
					'Name'                 => 'Breeze',
					'PluginURI'            => '',
					'Version'              => '1.1.1',
					'Description'          => 'Breeze is a WordPress cache plugin with extensive options to speed up your website. All the options including Varnish Cache are compatible with Cloudways hosting.',
					'Author'               => 'Cloudways',
					'AuthorURI'            => 'https://www.cloudways.com',
					'TextDomain'           => 'breeze',
					'DomainPath'           => '/languages',
					'Network'              => true,
					'Title'                => 'Breeze',
					'AuthorName'           => 'Cloudways',
				),
			);
			*/

			return $this->plugins_list;
		}

		/**
		 * Add/Remove from this list incompatible plugins.
		 * Follow the instructions in the comment below and template copy an existent teplate
		 * to modify for any new item.
		 *
		 * @return array
		 */
		protected function list_of_incompatible_plugins() {
			return array(
				/**
				 * The format:
				 * <plugin-folder>/<plugin-main-file>.php
				 * This is how WordPress recognizes the plugins
				 *
				 * Parameter explanation:
				 * ! warning_message !
				 * If left empty it will automatically use "<Plugin name> Plugin (<version_no>)".
				 * If you fill this field, it will overwrite the default message and display the custom message instead.
				 *
				 * ! safe_version_message !
				 * This message is just text to inform the user about a compatible version that is working with Breeze.
				 * If left empty, it does not display anything.
				 * Will display as Note: safe_version_message
				 *
				 * ! warning_version !
				 * Type the version from which the plugin is no longer compatible.
				 * Type -1 so that all versions of this plugin are not compatible.
				 *
				 * ! compare_sign !
				 * It will use current plugin version to compare with warning_version using the given sign.
				 * Accepted compare operators <,<=,>,>=, ==, =, !=, <>.
				 * If warning_version has the value -1, the compare_sign will be ignored.
				 * Current version of installed plugin compared to warning_version
				 */
				'w3-total-cache/w3-total-cache.php'   => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'wp-super-cache/wp-cache.php'         => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'litespeed-cache/litespeed-cache.php' => array(
					'warning_message'      => '',
					'warning_version'      => '2.0',
					'compare_sign'         => '>=', // Current version of installed plugin compared to warning_version
					'safe_version_message' => 'Version (1.0 - 1.9) are compatible.',
				),
				'quick-cache/quick-cache.php'         => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'hyper-cache/plugin.php'              => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'hyper-cache-extended/plugin.php'     => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'wp-fast-cache/wp-fast-cache.php'     => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'flexicache/wp-plugin.php'            => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'wp-fastest-cache/wpFastestCache.php' => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'lite-cache/plugin.php'               => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'gator-cache/gator-cache.php'         => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'wp-http-compression/wp-http-compression.php' => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'wordpress-gzip-compression/ezgz.php' => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'gzip-ninja-speed-compression/gzip-ninja-speed.php' => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),
				'speed-booster-pack/speed-booster-pack.php' => array(
					'warning_message'      => '',
					'warning_version'      => - 1,
					'compare_sign'         => '>',
					'safe_version_message' => '',
				),

			);
		}


	}

	new Breeze_Incompatibility_Plugins();
}
