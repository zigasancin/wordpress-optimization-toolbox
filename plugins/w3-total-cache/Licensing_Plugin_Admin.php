<?php
/**
 * File: Licensing_Plugin_Admin.php
 *
 * @package W3TC
 */

namespace W3TC;

/**
 * Class Licensing_Plugin_Admin
 *
 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
 * phpcs:disable Generic.CodeAnalysis.EmptyStatement
 */
class Licensing_Plugin_Admin {
	/**
	 * Config
	 *
	 * @var Config
	 */
	private $_config = null;

	/**
	 * Constructor for the Licensing Plugin Admin class.
	 *
	 * Initializes the configuration.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->_config = Dispatcher::config();
	}

	/**
	 * Registers hooks for the plugin's admin functionality.
	 *
	 * Adds actions and filters for admin initialization, AJAX, UI updates, and admin bar menu.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'w3tc_config_ui_save-w3tc_general', array( $this, 'possible_state_change' ), 2, 10 );

		add_action( 'w3tc_message_action_licensing_upgrade', array( $this, 'w3tc_message_action_licensing_upgrade' ) );

		add_filter( 'w3tc_admin_bar_menu', array( $this, 'w3tc_admin_bar_menu' ) );
	}

	/**
	 * Adds licensing menu items to the admin bar.
	 *
	 * @param array $menu_items Existing admin bar menu items.
	 *
	 * @return array Modified admin bar menu items.
	 */
	public function w3tc_admin_bar_menu( $menu_items ) {
		if ( ! Util_Environment::is_w3tc_pro( $this->_config ) ) {
			$menu_items['00020.licensing'] = array(
				'id'     => 'w3tc_overlay_upgrade',
				'parent' => 'w3tc',
				'title'  => wp_kses(
					sprintf(
						// translators: 1 opening HTML span tag, 2 closing HTML span tag.
						__(
							'%1$sUpgrade Performance%2$s',
							'w3-total-cache'
						),
						'<span style="color: red; background: none;">',
						'</span>'
					),
					array(
						'span' => array(
							'style' => array(),
						),
					)
				),
				'href'   => wp_nonce_url( network_admin_url( 'admin.php?page=w3tc_dashboard&amp;w3tc_message_action=licensing_upgrade' ), 'w3tc' ),
			);
		}

		if ( defined( 'W3TC_DEBUG' ) && W3TC_DEBUG ) {
			$menu_items['90040.licensing'] = array(
				'id'     => 'w3tc_debug_overlay_upgrade',
				'parent' => 'w3tc_debug_overlays',
				'title'  => esc_html__( 'Upgrade', 'w3-total-cache' ),
				'href'   => wp_nonce_url( network_admin_url( 'admin.php?page=w3tc_dashboard&amp;w3tc_message_action=licensing_upgrade' ), 'w3tc' ),
			);
		}

		return $menu_items;
	}

	/**
	 * Handles the licensing upgrade action.
	 *
	 * Adds a hook to modify the admin head for licensing upgrades.
	 *
	 * @return void
	 */
	public function w3tc_message_action_licensing_upgrade() {
		add_action( 'admin_head', array( $this, 'admin_head_licensing_upgrade' ) );
	}

	/**
	 * Outputs JavaScript for the licensing upgrade page.
	 *
	 * @return void
	 */
	public function admin_head_licensing_upgrade() {
		?>
		<script type="text/javascript">
			jQuery(function() {
				w3tc_lightbox_upgrade(w3tc_nonce, 'topbar_performance');
				jQuery('#w3tc-license-instruction').show();
			});
		</script>
		<?php
	}

	/**
	 * Handles possible state changes for plugin licensing.
	 *
	 * @param object $config     Current configuration object.
	 * @param object $old_config Previous configuration object.
	 *
	 * @return void
	 */
	public function possible_state_change( $config, $old_config ) {
		$changed     = false;
		$new_key     = $config->get_string( 'plugin.license_key' );
		$new_key_set = ! empty( $new_key );
		$old_key     = $old_config->get_string( 'plugin.license_key' );
		$old_key_set = ! empty( $old_key );

		switch ( true ) {
			// No new key or old key. Do nothing.
			case ( ! $new_key_set && ! $old_key_set ):
				return;

			// Current key set but new is blank, deactivating old.
			case ( ! $new_key_set && $old_key_set ):
				$deactivate_result = Licensing_Core::deactivate_license( $old_key );
				$changed           = true;
				break;

			// Current key is blank but new is not, activating new.
			case ( $new_key_set && ! $old_key_set ):
				$activate_result = Licensing_Core::activate_license( $new_key, W3TC_VERSION );
				$changed         = true;
				if ( $activate_result ) {
					$config->set( 'common.track_usage', true );
				}
				break;

			// Current key is set and new different key provided. Deactivating old and activating new.
			case ( $new_key_set && $old_key_set && $new_key !== $old_key ):
				$deactivate_result = Licensing_Core::deactivate_license( $old_key );
				$activate_result   = Licensing_Core::activate_license( $new_key, W3TC_VERSION );
				$changed           = true;
				break;
		}

		if ( $changed ) {
			$state = Dispatcher::config_state();
			$state->set( 'license.next_check', 0 );
			$state->save();

			delete_transient( 'w3tc_imageservice_limited' );

			$messages = array();

			// If the old key was deactivated, add a message.
			if ( isset( $deactivate_result ) ) {
				$status = $deactivate_result->license_status;

				switch ( true ) {
					case ( strpos( $status, 'inactive.expired.' ) === 0 ):
						$messages[] = array(
							'message' => __( 'Your previous W3 Total Cache Pro license key is expired and will remain registered to this domain.', 'w3-total-cache' ),
							'type'    => 'error',
						);
						break;

					case ( strpos( $status, 'inactive.not_present' ) === 0 ):
						$messages[] = array(
							'message' => __( 'Your previous W3 Total Cache Pro license key was not found and cannot be deactivated.', 'w3-total-cache' ),
							'type'    => 'info',
						);
						break;

					case ( strpos( $status, 'inactive' ) === 0 ):
						$messages[] = array(
							'message' => __( 'Your previous W3 Total Cache Pro license key has been deactivated.', 'w3-total-cache' ),
							'type'    => 'info',
						);
						break;

					case ( strpos( $status, 'invalid' ) === 0 ):
						$messages[] = array(
							'message' => __( 'Your previous W3 Total Cache Pro license key is invalid and cannot be deactivated.', 'w3-total-cache' ),
							'type'    => 'error',
						);
						break;
				}
			}

			// Handle new activation status.
			if ( isset( $activate_result ) ) {
				$status = $activate_result->license_status;

				switch ( true ) {
					case ( strpos( $status, 'active' ) === 0 ):
						$messages[] = array(
							'message' => __( 'The W3 Total Cache Pro license key you provided is valid and has been applied.', 'w3-total-cache' ),
							'type'    => 'info',
						);
						break;
				}
			}

			// Store messages for processing.
			update_option( 'license_update_messages', $messages );
		}
	}

	/**
	 * Initializes admin-specific features and hooks.
	 *
	 * Adds admin notices, UI filters, and license status checks.
	 *
	 * @return void
	 */
	public function admin_init() {
		$capability = apply_filters( 'w3tc_capability_admin_notices', 'manage_options' );

		$this->maybe_update_license_status();

		if ( current_user_can( $capability ) ) {
			if ( is_admin() ) {
				/**
				 * Only admin can see W3TC notices and errors
				 */
				if ( ! Util_Environment::is_wpmu() ) {
					add_action( 'admin_notices', array( $this, 'admin_notices' ), 1, 1 );
				}
				add_action( 'network_admin_notices', array( $this, 'admin_notices' ), 1, 1 );

				if ( Util_Admin::is_w3tc_admin_page() ) {
					add_filter( 'w3tc_notes', array( $this, 'w3tc_notes' ) );
				}
			}
		}
	}

	/**
	 * Checks if a status starts with a specific prefix.
	 *
	 * @param string $s           The status string.
	 * @param string $starts_with The prefix to check against.
	 *
	 * @return bool True if the status starts with the prefix, false otherwise.
	 */
	private function _status_is( $s, $starts_with ) {
		$s           .= '.';
		$starts_with .= '.';
		return substr( $s, 0, strlen( $starts_with ) ) === $starts_with;
	}

	/**
	 * Displays admin notices related to licensing.
	 *
	 * phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
	 * phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	 *
	 * @return void
	 */
	public function admin_notices() {
		$message = '';

		$state  = Dispatcher::config_state();
		$status = $state->get_string( 'license.status' );

		switch ( true ) {
			case $this->_status_is( $status, 'inactive.expired' ):
				$message = wp_kses(
					sprintf(
						// Translators: 1 HTML input button to renew license.
						__(
							'Your W3 Total Cache Pro license key has expired. %1$s to continue using the Pro features',
							'w3-total-cache'
						),
						'<input type="button" class="button button-renew-plugin" data-nonce="' .
							wp_create_nonce( 'w3tc' ) . '" data-renew-key="' . esc_attr( $this->get_license_key() ) .
							'" data-src="licensing_expired" value="' . __( 'Renew Now', 'w3-total-cache' ) . '" />'
					),
					array(
						'input' => array(
							'type'           => array(),
							'class'          => array(),
							'data-nonce'     => array(),
							'data-renew-key' => array(),
							'data-src'       => array(),
							'value'          => array(),
						),
					)
				);
				break;

			case $this->_status_is( $status, 'inactive.by_rooturi' ) || $this->_status_is( $status, 'inactive.by_rooturi.activations_limit_not_reached' ):
				$message = wp_kses(
					sprintf(
						// Translators: 1 opening HTML a tag to license rest link, 2 closing HTML a tag.
						__(
							'Your W3 Total Cache license key is not active for this site. You can switch your license to this website following %1$sthis link%2$s',
							'w3-total-cache'
						),
						'<a class="w3tc_licensing_reset_rooturi" href="' . Util_Ui::url(
							array(
								'page'                         => 'w3tc_general', // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
								'w3tc_licensing_reset_rooturi' => 'y',
							)
						) . '">',
						'</a>'
					),
					array(
						'a' => array(
							'class' => array(),
							'href'  => array(),
						),
					)
				);
				break;

			case $this->_status_is( $status, 'inactive.by_rooturi.activations_limit_reached' ):
				$message = __(
					'Your W3 Total Cache license key is not active and cannot be activated due to the license activation limit being reached.',
					'w3-total-cache'
				);
				break;

			case $this->_status_is( $status, 'inactive' ):
				$message = __( 'The W3 Total Cache license key is not active.', 'w3-total-cache' );
				break;

			case $this->_status_is( $status, 'invalid' ):
				$license_url         = admin_url( 'admin.php?page=w3tc_general#licensing' );
				$network_license_url = network_admin_url( 'admin.php?page=w3tc_general#licensing' );
				$message             = sprintf(
					// Translators: 1 opening HMTL a tag to license setting, 2 closing HTML a tag.
					__(
						'Your current W3 Total Cache Pro license key is not valid. %1$sPlease confirm it%2$s.',
						'w3-total-cache'
					),
					'<a href="' . ( is_network_admin() ? $network_license_url : $license_url ) . '">',
					'</a>'
				);
				break;

			case ( 'no_key' === $status || $this->_status_is( $status, 'active' ) ):
				// License is active, do nothing.
				break;

			default:
				$message = __( 'The W3 Total Cache license key cannot be verified.', 'w3-total-cache' );
				break;
		}

		if ( $message ) {
			if ( ! Util_Admin::is_w3tc_admin_page() ) {
				echo '<script src="' . esc_url( plugins_url( 'pub/js/lightbox.js', W3TC_FILE ) ) . '"></script>';
				echo '<link rel="stylesheet" id="w3tc-lightbox-css"  href="' . esc_url( plugins_url( 'pub/css/lightbox.css', W3TC_FILE ) ) . '" type="text/css" media="all" />';
			}

			Util_Ui::error_box( '<p>' . $message . '</p>' );
		}

		$license_update_messages = get_option( 'license_update_messages' );

		if ( $license_update_messages ) {
			foreach ( $license_update_messages as $message_data ) {
				if ( 'error' === $message_data['type'] ) {
					Util_Ui::error_box( '<p>' . $message_data['message'] . '</p>' );
				} elseif ( 'info' === $message_data['type'] ) {
					Util_Ui::e_notification_box( '<p>' . $message_data['message'] . '</p>' );
				}
			}
			delete_option( 'license_update_messages' );
		}
	}

	/**
	 * Modifies the notes displayed in the W3TC UI.
	 *
	 * @param array $notes Existing notes to display.
	 *
	 * @return array Modified notes with licensing terms.
	 */
	public function w3tc_notes( $notes ) {
		$terms        = '';
		$state_master = Dispatcher::config_state_master();

		if ( Util_Environment::is_pro_constant( $this->_config ) ) {
			$terms = 'accept';
		} elseif ( ! Util_Environment::is_w3tc_pro( $this->_config ) ) {
			$terms = $state_master->get_string( 'license.community_terms' );

			$buttons = sprintf(
				'<br /><br />%s&nbsp;%s',
				Util_Ui::button_link(
					__( 'Accept', 'w3-total-cache' ),
					Util_Ui::url( array( 'w3tc_licensing_terms_accept' => 'y' ) )
				),
				Util_Ui::button_link(
					__( 'Decline', 'w3-total-cache' ),
					Util_Ui::url( array( 'w3tc_licensing_terms_decline' => 'y' ) )
				)
			);
		} else {
			$state = Dispatcher::config_state();
			$terms = $state->get_string( 'license.terms' );

			$return_url = self_admin_url( Util_Ui::url( array( 'w3tc_licensing_terms_refresh' => 'y' ) ) );

			$buttons =
				sprintf( '<form method="post" action="%s">', W3TC_TERMS_ACCEPT_URL ) .
				Util_Ui::r_hidden( 'return_url', 'return_url', $return_url ) .
				Util_Ui::r_hidden( 'license_key', 'license_key', $this->get_license_key() ) .
				Util_Ui::r_hidden( 'home_url', 'home_url', home_url() ) .
				'<input type="submit" class="button" name="answer" value="Accept" />&nbsp;' .
				'<input type="submit" class="button" name="answer" value="Decline" />' .
				'</form>';
		}

		if ( 'accept' !== $terms && 'decline' !== $terms && 'postpone' !== $terms ) {
			if ( $state_master->get_integer( 'common.install' ) < 1542029724 ) {
				/* installed before 2018-11-12 */
				$notes['licensing_terms'] = sprintf(
					// translators: 1 opening HTML a tag to W3TC Terms page, 2 closing HTML a tag.
					esc_html__(
						'Our terms of use and privacy policies have been updated. Please %1$sreview%2$s and accept them.',
						'w3-total-cache'
					),
					'<a target="_blank" href="' . esc_url( W3TC_TERMS_URL ) . '">',
					'</a>'
				) . $buttons;
			} else {
				$notes['licensing_terms'] = sprintf(
					// translators: 1: HTML break tag, 2: Anchor/link open tag, 3: Anchor/link close tag.
					esc_html__(
						'By allowing us to collect data about how W3 Total Cache is used, we can improve our features and experience for everyone. This data will not include any personally identifiable information.%1$sFeel free to review our %2$sterms of use and privacy policy%3$s.',
						'w3-total-cache'
					),
					'<br />',
					'<a target="_blank" href="' . esc_url( W3TC_TERMS_URL ) . '">',
					'</a>'
				) .
					$buttons;
			}
		}

		return $notes;
	}

	/**
	 * Updates the license status if needed.
	 *
	 * Performs a license check and updates the configuration state accordingly.
	 *
	 * @return string The updated license status.
	 */
	private function maybe_update_license_status() {
		$state = Dispatcher::config_state();
		if ( time() < $state->get_integer( 'license.next_check' ) ) {
			return;
		}

		$check_timeout = 3600 * 24 * 5;
		$status        = '';
		$terms         = '';
		$license_key   = $this->get_license_key();

		$old_plugin_type = $this->_config->get_string( 'plugin.type' );
		$plugin_type     = '';

		if ( ! empty( $license_key ) || defined( 'W3TC_LICENSE_CHECK' ) ) {
			$license = Licensing_Core::check_license( $license_key, W3TC_VERSION );

			if ( $license ) {
				$status = $license->license_status;
				$terms  = $license->license_terms;
				if ( $this->_status_is( $status, 'active' ) ) {
					$plugin_type = 'pro';
				} elseif ( $this->_status_is( $status, 'inactive.by_rooturi' ) &&
					Util_Environment::is_w3tc_pro_dev() ) {
					$status      = 'valid';
					$plugin_type = 'pro_dev';
				}
			}

			$this->_config->set( 'plugin.type', $plugin_type );
		} else {
			$status = 'no_key';
		}

		if ( 'no_key' === $status ) {
			// Do nothing.
		} elseif ( $this->_status_is( $status, 'invalid' ) ) {
			// Do nothing.
		} elseif ( $this->_status_is( $status, 'inactive' ) ) {
			// Do nothing.
		} elseif ( $this->_status_is( $status, 'active' ) ) {
			// Do nothing.
		} else {
			$check_timeout = 60;
		}

		$state->set( 'license.status', $status );
		$state->set( 'license.next_check', time() + $check_timeout );
		$state->set( 'license.terms', $terms );
		$state->save();

		if ( $old_plugin_type !== $plugin_type ) {
			try {
				$this->_config->set( 'plugin.type', $plugin_type );
				$this->_config->save();
			} catch ( \Exception $ex ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// missing exception handle?
			}
		}
		return $status;
	}

	/**
	 * Retrieves the license key for the plugin.
	 *
	 * @return string The license key.
	 */
	public function get_license_key() {
		$license_key = $this->_config->get_string( 'plugin.license_key', '' );
		if ( '' === $license_key ) {
			$license_key = ini_get( 'w3tc.license_key' );
		}
		return $license_key;
	}
}
