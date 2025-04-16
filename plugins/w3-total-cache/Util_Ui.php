<?php
/**
 * File: Util_Ui.php
 *
 * @package W3TC
 */

namespace W3TC;

use DOMDocument;

/**
 * Class Util_Ui
 *
 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 */
class Util_Ui {
	/**
	 * Returns button html
	 *
	 * @param string $text        Text.
	 * @param string $onclick     On click.
	 * @param string $class_value Class.
	 * @param string $name        Name.
	 *
	 * @return string
	 */
	public static function button( $text, $onclick = '', $class_value = 'button', $name = '' ) {
		$maybe_name = ( empty( $name ) ? '' : ' name="' . esc_attr( $name ) . '"' );
		return '<input type="button"' . $maybe_name . ' class="' . esc_attr( $class_value ) . '" value="' .
			esc_attr( $text ) . '" onclick="' . esc_attr( $onclick ) . '" />';
	}

	/**
	 * Returns button link html.
	 *
	 * @param string $text        Text.
	 * @param string $url         URL.
	 * @param bool   $new_window  Open link in a new window.
	 * @param string $class_value Class.
	 * @param string $name        Name.
	 *
	 * @return string
	 */
	public static function button_link( $text, $url, $new_window = false, $class_value = 'button', $name = '' ) {
		$url = str_replace( '&amp;', '&', $url );

		if ( $new_window ) {
			$onclick = sprintf( 'window.open(\'%s\');', addslashes( $url ) );
		} else {
			$onclick = '';

			if ( strpos( $class_value, 'w3tc-button-ignore-change' ) >= 0 ) {
				$onclick .= 'w3tc_beforeupload_unbind(); ';
			}

			$onclick .= sprintf( 'document.location.href=\'%s\';', addslashes( $url ) );
		}

		return self::button( $text, $onclick, $class_value, $name );
	}

	/**
	 * Generates a URL with a nonce.
	 *
	 * @param array $addon {
	 *     Addon parameters used to build the URL.
	 *
	 *     @type string $page Optional. The page parameter. Defaults to 'w3tc_dashboard'.
	 * }
	 *
	 * @return string The generated URL with a nonce.
	 */
	public static function url( $addon ) {
		if ( ! isset( $addon['page'] ) ) {
			$addon['page'] = Util_Request::get_string( 'page', 'w3tc_dashboard' );
		}

		$url = 'admin.php';
		$amp = '?';
		foreach ( $addon as $key => $value ) {
			$url .= $amp . rawurlencode( $key ) . '=' . rawurlencode( $value );
			$amp  = '&';
		}

		$url = wp_nonce_url( $url, 'w3tc' );

		return $url;
	}

	/**
	 * Returns hide note button html
	 *
	 * @param string  $text          Text.
	 * @param string  $note          Note.
	 * @param string  $redirect      Redirect.
	 * @param boolean $admin         If to use config admin.
	 * @param string  $page          Page.
	 * @param string  $custom_method Custom method.
	 *
	 * @return string
	 */
	public static function button_hide_note(
		$text,
		$note,
		$redirect = '',
		$admin = false,
		$page = '',
		$custom_method = 'w3tc_default_hide_note'
	) {
		if ( '' === $page ) {
			$page = Util_Request::get_string( 'page', 'w3tc_dashboard' );
		}

		$url = sprintf( 'admin.php?page=%s&%s&note=%s', $page, $custom_method, $note );

		if ( $admin ) {
			$url .= '&admin=1';
		}

		if ( '' !== $redirect ) {
			$url .= '&redirect=' . rawurlencode( $redirect );
		}

		$url = wp_nonce_url( $url, 'w3tc' );

		return self::button_link( $text, $url, false, 'button', 'w3tc_hide_' . $custom_method );
	}

	/**
	 * Hide note button.
	 *
	 * @param array $parameters {
	 *     Parameters for generating the hide note button.
	 *
	 *     @type string $key The configuration key used to generate the button ID.
	 * }
	 *
	 * @return string The generated button HTML.
	 */
	public static function button_hide_note2( $parameters ) {
		return self::button_link(
			__( 'Hide this message', 'w3-total-cache' ),
			self::url( $parameters ),
			false,
			'button',
			'w3tc_hide_' . self::config_key_to_http_name( $parameters['key'] )
		);
	}

	/**
	 * Action button
	 *
	 * @param string $action      Action.
	 * @param string $url         URL.
	 * @param string $class_value Class.
	 * @param bool   $new_window  New window flag.
	 *
	 * @return string
	 */
	public static function action_button( $action, $url, $class_value = '', $new_window = false ) {
		return self::button_link( $action, $url, $new_window, $class_value );
	}
	/**
	 * Returns popup button html
	 *
	 * @param string  $text   Text.
	 * @param string  $action Action.
	 * @param string  $params Parameters.
	 * @param integer $width  Width.
	 * @param integer $height Height.
	 *
	 * @return string
	 */
	public static function button_popup( $text, $action, $params = '', $width = 800, $height = 600 ) {
		$url = wp_nonce_url( sprintf( 'admin.php?page=w3tc_dashboard&w3tc_%s%s', $action, ( '' !== $params ? '&' . $params : '' ) ), 'w3tc' );
		$url = str_replace( '&amp;', '&', $url );

		$onclick = sprintf( 'window.open(\'%s\', \'%s\', \'width=%d,height=%d,status=no,toolbar=no,menubar=no,scrollbars=yes\');', $url, $action, $width, $height );

		return self::button( $text, $onclick );
	}

	/**
	 * Returns label string for a config key.
	 *
	 * @param string $config_key Config key.
	 *
	 * @return string
	 */
	public static function config_label( $config_key ) {
		static $config_labels = null;
		if ( is_null( $config_labels ) ) {
			$config_labels = apply_filters( 'w3tc_config_labels', array() );
		}

		if ( isset( $config_labels[ $config_key ] ) ) {
			return $config_labels[ $config_key ];
		}

		return '';
	}

	/**
	 * Prints the label string for a config key.
	 *
	 * @param string $config_key Config key.
	 *
	 * @return void
	 */
	public static function e_config_label( $config_key ) {
		$config_label = self::config_label( $config_key );
		echo wp_kses(
			$config_label,
			self::get_allowed_html_for_wp_kses_from_content( $config_label )
		);
	}

	/**
	 * Returns postbox header
	 *
	 * WordPress 5.5 introduced .postbox-header, which broke the styles of our postboxes. This was
	 * resolved by adding additional css to /pub/css/options.css and pub/css/widget.css tagged with
	 * a "WP 5.5" comment.
	 *
	 * @todo Add .postbox-header to our postboxes and cleanup css.
	 *
	 * @link https://github.com/BoldGrid/w3-total-cache/issues/237
	 *
	 * @param string $title       Title.
	 * @param string $class_value Class.
	 * @param string $id          ID.
	 *
	 * @return void
	 */
	public static function postbox_header( $title, $class_value = '', $id = '' ) {
		$id = ! empty( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';
		?>
		<div <?php echo wp_kses( $id, self::get_allowed_html_for_wp_kses_from_content( $id ) ); ?> class="postbox <?php echo esc_attr( $class_value ); ?>">
			<h3 class="postbox-title">
				<span><?php echo wp_kses( $title, self::get_allowed_html_for_wp_kses_from_content( $title ) ); ?></span>
			</h3>
			<div class="inside">
		<?php
	}

	/**
	 * Returns postbox header with tabs and links (used on the General settings page exclusively).
	 *
	 * WordPress 5.5 introduced .postbox-header, which broke the styles of our postboxes. This was
	 * resolved by adding additional CSS to `/pub/css/options.css` and `/pub/css/widget.css` tagged with
	 * a "WP 5.5" comment.
	 *
	 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 *
	 * @todo Add .postbox-header to our postboxes and clean up CSS.
	 *
	 * @link https://github.com/BoldGrid/w3-total-cache/issues/237
	 *
	 * @param string $title        The title of the postbox.
	 * @param string $description  Optional. Description of the postbox. Default empty.
	 * @param string $class_value  Optional. Additional CSS class for styling. Default empty.
	 * @param string $id           Optional. HTML ID attribute. Default empty.
	 * @param string $adv_link     Optional. URL for the "Advanced Settings" tab. Default empty.
	 * @param string $premium_link Optional. URL for the "Premium Services" tab. Default empty.
	 * @param string $tutorials_tab Optional. URL for the "Help" tab. Default empty.
	 * @param array  $extra_links {
	 *     Optional. Additional links for the postbox navigation.
	 *
	 *     @type string $text  Link text.
	 *     @type string $url   URL for the extra link.
	 * }
	 *
	 * @return void
	 */
	public static function postbox_header_tabs( $title, $description = '', $class_value = '', $id = '', $adv_link = '', $premium_link = '', $tutorials_tab = '', $extra_links = array() ) {
		$display_id         = ( ! empty( $id ) ) ? ' id="' . esc_attr( $id ) . '"' : '';
		$description        = ( ! empty( $description ) ) ? '<div class="postbox-description">' . wp_kses( $description, self::get_allowed_html_for_wp_kses_from_content( $description ) ) . '</div>' : '';
		$basic_settings_tab = ( ! empty( $adv_link ) ) ? '<a class="w3tc-basic-settings nav-tab nav-tab-active no-link">' . esc_html__( 'Basic Settings', 'w3-total-cache' ) . '</a>' : '';
		$adv_settings_tab   = ( ! empty( $adv_link ) ) ? '<a class="nav-tab link-tab" href="' . esc_url( $adv_link ) . '" gatitle="' . esc_attr( $id ) . '">' . esc_html__( 'Advanced Settings', 'w3-total-cache' ) . '<span class="dashicons dashicons-arrow-right-alt2"></span></a>' : '';
		$premium_link_tab   = ( ! empty( $premium_link ) ) ? '<a class="nav-tab link-tab ' . esc_attr( $id ) . '" data-tab-type="premium-services">' . esc_html__( 'Premium Services', 'w3-total-cache' ) . '</a>' : '';
		$tutorials_tab      = ( ! empty( $premium_link ) ) ? '<a class="nav-tab link-tab ' . esc_attr( $id ) . '" data-tab-type="help">' . esc_html__( 'Help', 'w3-total-cache' ) . '</a>' : '';

		$extra_link_tabs = '';
		foreach ( $extra_links as $extra_link_text => $extra_link ) {
			$extra_link_tabs .= '<a class="nav-tab link-tab" href="' . esc_url( $extra_link ) . '" gatitle="' . esc_attr( $extra_link_text ) . '">' . esc_html( $extra_link_text ) . '</a>';
		}

		echo '<div' . $display_id . ' class="postbox-tabs ' . esc_attr( $class_value ) . '">
			<h3 class="postbox-title"><span>' . wp_kses( $title, self::get_allowed_html_for_wp_kses_from_content( $title ) ) . '</span></h3>
			' . $description . '
			<h2 class="nav-tab-wrapper">' . $basic_settings_tab . $adv_settings_tab . $premium_link_tab . $tutorials_tab . $extra_link_tabs . '</h2>
			<div class="inside">';
	}

	/**
	 * Returns postbox footer
	 *
	 * @return void
	 */
	public static function postbox_footer() {
		echo '</div></div>';
	}

	/**
	 * Retrieves a specific tab's content based on the provided key and tab type.
	 *
	 * @since 2.8.3
	 *
	 * This function dynamically loads content for a specified tab type (e.g., tutorials, premium services)
	 * based on a given configuration key. It uses a mapping to fetch the correct tab content, which is then
	 * wrapped in a `<div>` element with a `data-tab-type` attribute for identification.
	 *
	 * @param string $key      The configuration key used to retrieve tab settings.
	 * @param string $tab_type The type of tab to retrieve (e.g., 'tutorials', 'premium-services').
	 *
	 * @return string|null     The HTML content for the specified tab, or null if the tab or key is not found.
	 *
	 * Usage:
	 * ```
	 * echo wp_kses_post( Util_Ui::get_tab('example_key', 'tutorials');  // Retrieves the tutorials tab for 'example_key'
	 * ```
	 */
	public static function get_tab( string $key, string $tab_type ): ?string {

		// If for any reason the key or tab type is empty, return an empty string.
		if ( empty( $key ) || empty( $tab_type ) ) {
			return '';
		}

		require_once 'ConfigSettingsTabs.php';
		$configs = Config_Tab_Settings::get_config( $key );

		// Define a mapping of tab types to the corresponding config keys.
		$tab_mapping = array(
			'help'             => 'help',
			'premium-services' => 'premium_support',
		);

		// Check if the provided tab type exists in the mapping and in the configs.
		if ( isset( $tab_mapping[ $tab_type ] ) && isset( $configs['tabs'][ $tab_mapping[ $tab_type ] ] ) ) {
			return '<div data-tab-type="' . esc_attr( $tab_type ) . '">' . $configs['tabs'][ $tab_mapping[ $tab_type ] ] . '</div>';
		}

		return null;
	}

	/**
	 * Config save button.
	 *
	 * @param string $id    ID.
	 * @param string $extra Extra.
	 *
	 * @return void
	 */
	public static function button_config_save( $id = '', $extra = '' ) {
		$b1_id = 'w3tc_save_options_' . $id;
		$b2_id = 'w3tc_default_save_and_flush_' . $id;

		$nonce_field = self::nonce_field( 'w3tc' );
		$nonce_html  = wp_kses( $nonce_field, self::get_allowed_html_for_wp_kses_from_content( $nonce_field ) );
		$extra_html  = wp_kses( $extra, self::get_allowed_html_for_wp_kses_from_content( $extra ) );

		?>
		<p class="submit">
			<?php echo $nonce_html; ?>
			<input type="submit" id="<?php echo esc_attr( $b1_id ); ?>" name="w3tc_save_options" class="w3tc-button-save button-primary" value="<?php esc_attr_e( 'Save all settings', 'w3-total-cache' ); ?>" />
			<?php echo $extra_html; ?>
			<?php
			if ( ! is_network_admin() ) {
				echo '<input type="submit" id="' . esc_attr( $b2_id ) . '" name="w3tc_default_save_and_flush" style="float: right"
					class="w3tc-button-save button-primary" value="' . esc_attr__( 'Save Settings & Purge Caches', 'w3-total-cache' ) . '" />';
			}
			?>
		</p>
		<?php
	}

	/**
	 * Config save button with dropdown.
	 *
	 * @param string $id    ID.
	 * @param string $extra Extra.
	 *
	 * @return void
	 */
	public static function button_config_save_dropdown( $id = '', $extra = '' ) {
		?>
		<div class="w3tc-button-control-container">
			<?php
			self::print_save_split_button( $id, $extra );
			self::print_flush_split_button();
			?>
		</div>
		<?php
	}

	/**
	 * Prints the split button for saving setting.
	 *
	 * @param string $id     ID value.
	 * @param string $extra Extra values.
	 *
	 * @return void
	 */
	public static function print_save_split_button( $id = '', $extra = '' ) {
		$b1_id = 'w3tc_save_options_' . $id;
		$b2_id = 'w3tc_default_save_and_flush_' . $id;

		$nonce_field = self::nonce_field( 'w3tc' );
		echo wp_kses(
			$nonce_field,
			self::get_allowed_html_for_wp_kses_from_content( $nonce_field )
		);

		echo wp_kses(
			$extra,
			self::get_allowed_html_for_wp_kses_from_content( $extra )
		);

		?>
		<div class="btn-group w3tc-button-save-dropdown">
			<?php
			if ( ! is_network_admin() ) {
				?>
				<input type="submit" id="<?php echo esc_attr( $b1_id ); ?>" class="w3tc-button-save btn btn-primary btn-sm" name="w3tc_save_options" value="<?php esc_html_e( 'Save Settings', 'w3-total-cache' ); ?>"/>
				<button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="sr-only">Toggle Dropdown</span>
				</button>
				<div class="dropdown-menu dropdown-menu-right">
					<input type="submit" id="<?php echo esc_attr( $b2_id ); ?>" class="w3tc-button-save dropdown-item" name="w3tc_default_save_and_flush" value="<?php esc_html_e( 'Save Settings & Purge Caches', 'w3-total-cache' ); ?>"/>
				</div>
				<?php
			} else {
				?>
				<input type="submit" class="w3tc-button-save btn btn-primary btn-sm" name="w3tc_save_options" value="<?php esc_html_e( 'Save Settings', 'w3-total-cache' ); ?>"/>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Prints the split button for flushing caches.
	 *
	 * @return void
	 */
	public static function print_flush_split_button() {
		$config = Dispatcher::config();

		$nonce_field = self::nonce_field( 'w3tc' );
		echo wp_kses(
			$nonce_field,
			self::get_allowed_html_for_wp_kses_from_content( $nonce_field )
		);

		?>
		<div class="btn-group w3tc-button-flush-dropdown">
			<input id="flush_all" type="submit" class="btn btn-light btn-sm" name="w3tc_flush_all" value="<?php esc_html_e( 'Empty All Caches', 'w3-total-cache' ); ?>"/>
			<button type="button" class="btn btn-light btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span class="sr-only">Toggle Dropdown</span>
			</button>
			<div class="dropdown-menu dropdown-menu-right">
				<?php
				$actions = apply_filters( 'w3tc_dashboard_actions', array() );
				foreach ( $actions as $action ) {
					echo wp_kses(
						$action,
						array(
							'input' => array(
								'class'    => array(),
								'disabled' => array(),
								'name'     => array(),
								'type'     => array(),
								'value'    => array(),
							),
						)
					);
				}
				if ( $config->get_boolean( 'pgcache.enabled' ) ) {
					echo '<input type="submit" class="dropdown-item" name="w3tc_flush_pgcache" value="' . esc_attr__( 'Empty Page Cache', 'w3-total-cache' ) . '"/>';
				}
				if ( $config->get_boolean( 'browsercache.cssjs.replace' ) || $config->get_boolean( 'browsercache.other.replace' ) ) {
					echo '<input type="submit" class="dropdown-item" name="w3tc_flush_browser_cache" value="' . esc_attr__( 'Empty Browser Cache', 'w3-total-cache' ) . '"/>';
				}
				if ( $config->get_boolean( 'minify.enabled' ) ) {
					echo '<input type="submit" class="dropdown-item" name="w3tc_flush_minify" value="' . esc_attr__( 'Empty Minify Cache', 'w3-total-cache' ) . '"/>';
				}
				if ( $config->get_boolean( 'dbcache.enabled' ) ) {
					echo '<input type="submit" class="dropdown-item" name="w3tc_flush_dbcache" value="' . esc_attr__( 'Empty Database Cache', 'w3-total-cache' ) . '"/>';
				}
				if ( $config->getf_boolean( 'objectcache.enabled' ) ) {
					echo '<input type="submit" class="dropdown-item" name="w3tc_flush_objectcache" value="' . esc_attr__( 'Empty Object Cache', 'w3-total-cache' ) . '"/>';
				}
				if ( $config->get_boolean( 'cdn.enabled' ) || $config->get_boolean( 'cdnfsd.enabled' ) ) {
					$disable = ( $config->get_boolean( 'cdn.enabled' ) && Cdn_Util::can_purge_all( $config->get_string( 'cdn.engine' ) ) ) ||
						( $config->get_boolean( 'cdnfsd.enabled' ) && Cdn_Util::can_purge_all( $config->get_string( 'cdnfsd.engine' ) ) ) ?
							'' : ' disabled="disabled" ';
					echo '<input type="submit" class="dropdown-item" name="w3tc_flush_cdn"' . $disable . ' value="' . esc_attr__( 'Empty CDN Cache', 'w3-total-cache' ) . '"/>';
				}
				if ( $config->is_extension_active_frontend( 'fragmentcache' ) && Util_Environment::is_w3tc_pro( $config ) && ! empty( $config->get_string( array( 'fragmentcache', 'engine' ) ) ) ) {
					echo '<input type="submit" class="dropdown-item" name="w3tc_flush_fragmentcache" value="' . esc_attr__( 'Empty Fragment Cache', 'w3-total-cache' ) . '"/>';
				}
				if ( $config->get_boolean( 'varnish.enabled' ) ) {
					echo '<input type="submit" class="dropdown-item" name="w3tc_flush_varnish" value="' . esc_attr__( 'Empty Varnish Cache', 'w3-total-cache' ) . '"/>';
				}
				if ( $config->is_extension_active_frontend( 'cloudflare' ) ) {
					echo '<input type="submit" class="dropdown-item" name="w3tc_cloudflare_flush" value="' . esc_attr__( 'Empty Cloudflare Cache', 'w3-total-cache' ) . '"/>';
				}
				$opcode_enabled = ( Util_Installed::opcache() || Util_Installed::apc_opcache() );
				if ( $opcode_enabled ) {
					$disable = $opcode_enabled ? '' : ' disabled="disabled" ';
					echo '<input type="submit" class="dropdown-item" name="w3tc_opcache_flush"' . $disable . ' value="' . esc_attr__( 'Empty OpCode Cache', 'w3-total-cache' ) . '"/>';
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Prints the form control bar
	 *
	 * @param string $id ID.
	 *
	 * @return void
	 */
	public static function print_control_bar( $id = '' ) {
		?>
		<div class="w3tc_form_bar">
			<?php
			$custom_areas = apply_filters( 'w3tc_settings_general_anchors', array() );
			self::print_options_menu( $custom_areas );
			self::button_config_save_dropdown( $id );
			?>
		</div>
		<?php
	}

	/**
	 * Sealing disabled
	 *
	 * @param string $key Key.
	 *
	 * @return void
	 */
	public static function sealing_disabled( $key ) {
		$c = Dispatcher::config();
		if ( $c->is_sealed( $key ) ) {
			echo 'disabled="disabled" ';
		}
	}

	/**
	 * Returns nonce field HTML
	 *
	 * @param string|int $action  Action.
	 * @param string     $name    Name.
	 * @param bool       $referer Referrer.
	 *
	 * @return string
	 */
	public static function nonce_field( $action = -1, $name = '_wpnonce', $referer = true ) {
		$return = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';

		if ( $referer ) {
			$return .= wp_referer_field( false );
		}

		return $return;
	}

	/**
	 * Returns an notification box
	 *
	 * @param string $message Message.
	 * @param string $id      Adds an id to the notification box.
	 *
	 * @return string
	 */
	public static function get_notification_box( $message, $id = '' ) {
		$page_val = Util_Request::get_string( 'page' );

		if ( empty( $page_val ) || ( ! empty( $page_val ) && 'w3tc_' !== substr( $page_val, 0, 5 ) ) ) {
			$logo = sprintf(
				'<img src="%s" alt="W3 Total Cache" style="height:30px;padding: 10px 2px 0 2px;" />"',
				esc_url( plugins_url( '/pub/img/W3TC_dashboard_logo_title.png', W3TC_FILE ) ) . ''
			);
		} else {
			$logo = '';
		}
		return sprintf(
			'<div %s class="updated inline">%s</div>',
			$id ? 'id="' . esc_attr( $id ) . '"' : '',
			$logo . wp_kses( $message, self::get_allowed_html_for_wp_kses_from_content( $message ) )
		);
	}

	/**
	 * Echos an notification box
	 *
	 * @param string $message Message.
	 * @param string $id      adds an id to the notification box.
	 *
	 * @return void
	 */
	public static function e_notification_box( $message, $id = '' ) {
		$notification_box = self::get_notification_box( $message, $id );
		echo wp_kses(
			$notification_box,
			self::get_allowed_html_for_wp_kses_from_content( $notification_box )
		);
	}

	/**
	 * Echos an error box.
	 *
	 * @param string $message Message.
	 * @param string $id      Id.
	 *
	 * @return void
	 */
	public static function error_box( $message, $id = '' ) {
		$page_val = Util_Request::get_string( 'page' );

		if ( empty( $page_val ) || ( ! empty( $page_val ) && 'w3tc_' !== substr( $page_val, 0, 5 ) ) ) {
			$logo = sprintf(
				'<img src="%s" alt="W3 Total Cache" style="height:30px;padding: 10px 2px 0 2px;" />',
				esc_url( plugins_url( '/pub/img/W3TC_dashboard_logo_title.png', W3TC_FILE ) . '' )
			);
		} else {
			$logo = '';
		}

		$v = sprintf(
			'<div %s class="error inline">%s</div>',
			$id ? 'id="' . esc_attr( $id ) . '"' : '',
			$logo . wp_kses( $message, self::get_allowed_html_for_wp_kses_from_content( $message ) )
		);

		echo wp_kses(
			$v,
			self::get_allowed_html_for_wp_kses_from_content( $v )
		);
	}

	/**
	 * Format bytes into B, KB, MB, GB and TB
	 *
	 * phpcs:disable Squiz.PHP.CommentedOutCode.Found
	 *
	 * @param int $bytes     Bytes.
	 * @param int $precision Precision.
	 *
	 * @return string
	 */
	public static function format_bytes( $bytes, $precision = 2 ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$bytes = max( $bytes, 0 );
		$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow   = min( $pow, count( $units ) - 1 );

		// Uncomment one of the following alternatives.
		$bytes /= pow( 1024, $pow );
		// $bytes /= ( 1 << ( 10 * $pow ) );

		return round( $bytes, $precision ) . ' ' . $units[ $pow ];
	}

	/**
	 * Format mbytes into B, KB, MB, GB and TB
	 *
	 * phpcs:disable Squiz.PHP.CommentedOutCode.Found
	 *
	 * @param int $bytes     Bytes.
	 * @param int $precision Precision.
	 *
	 * @return string
	 */
	public static function format_mbytes( $bytes, $precision = 2 ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$bytes = max( $bytes, 0 );
		$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow   = min( $pow, count( $units ) - 1 );

		// Uncomment one of the following alternatives.
		$bytes /= pow( 1024, $pow );
		// $bytes /= ( 1 << ( 10 * $pow ) );

		return round( $bytes, $precision ) . ' ' . $units[ $pow + 2 ];
	}

	/**
	 * Returns an hidden input text element
	 *
	 * @param string $id       ID.
	 * @param string $name     Name.
	 * @param string $value    Value.
	 *
	 * @return string
	 */
	public static function r_hidden( $id, $name, $value ) {
		return '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
	}

	/**
	 * Echos a hidden input text element
	 *
	 * @param string $id    ID.
	 * @param string $name  Name.
	 * @param string $value Value.
	 *
	 * @return void
	 */
	public static function hidden( $id, $name, $value ) {
		$hidden = self::r_hidden( $id, $name, $value );
		echo wp_kses(
			$hidden,
			self::get_allowed_html_for_wp_kses_from_content( $hidden )
		);
	}

	/**
	 * Echos an label element
	 *
	 * @param string $id   ID.
	 * @param string $text Text.
	 *
	 * @return void
	 */
	public static function label( $id, $text ) {
		$label = '<label for="' . esc_attr( $id ) . '">' . $text . '</label>';
		echo wp_kses(
			$label,
			self::get_allowed_html_for_wp_kses_from_content( $label )
		);
	}

	/**
	 * Echos an input text element
	 *
	 * @param string $id          ID.
	 * @param string $name        Name.
	 * @param string $value       Value.
	 * @param bool   $disabled    Disabled.
	 * @param int    $size        Size.
	 * @param string $type        Type.
	 * @param string $placeholder Placeholder.
	 *
	 * @return void
	 */
	public static function textbox( $id, $name, $value, $disabled = false, $size = 40, $type = 'text', $placeholder = '' ) {
		$placeholder = ! empty( $placeholder ) ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '';

		echo '<input class="enabled" type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"
			 value="' . esc_attr( $value ) . '" ' . disabled( $disabled, true, false ) . ' size="' . esc_attr( $size ) . '"' . $placeholder . ' />';
	}

	/**
	 * Echos an input password element
	 *
	 * @param string $id       ID.
	 * @param string $name     Name.
	 * @param string $value    Value.
	 * @param bool   $disabled Diabled.
	 * @param int    $size     Size.
	 *
	 * @return void
	 */
	public static function passwordbox( $id, $name, $value, $disabled = false, $size = 40 ) {
		echo '<input class="enabled" type="password" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"
			 value="' . esc_attr( $value ) . '" ' . disabled( $disabled, true, false ) . ' size="' . esc_attr( $size ) . '" />';
	}

	/**
	 * Echoes a select element.
	 *
	 * @param string $id        The ID attribute for the select element.
	 * @param string $name      The name attribute for the select element.
	 * @param string $value     The selected value.
	 * @param array  $values    An array of options, where the key is the option value and the value is the label.
	 * @param bool   $disabled  Whether the select element should be disabled. Default false.
	 * @param array  $optgroups {
	 *     Optional. An associative array of optgroup labels.
	 *
	 *     @type int|string $key The optgroup identifier.
	 *     @type string     $label The label for the optgroup.
	 * }
	 *
	 * @return void
	 */
	public static function selectbox( $id, $name, $value, $values, $disabled = false, $optgroups = null ) {
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" ' . disabled( $disabled, true, false ) . ">\n";

		if ( ! is_array( $optgroups ) ) {
			// simle control.
			foreach ( $values as $key => $descriptor ) {
				self::option( $key, $value, $descriptor );
			}
		} else {
			// with optgroups.
			$current_optgroup = -1;
			foreach ( $values as $key => $descriptor ) {
				$optgroup = ( isset( $descriptor['optgroup'] ) ? $descriptor['optgroup'] : -1 );
				if ( $optgroup !== $current_optgroup ) {
					if ( -1 !== $current_optgroup ) {
						echo '</optgroup>';
					}
					echo '<optgroup label="' . esc_attr( $optgroups[ $optgroup ] ) . '">' . "\n";
					$current_optgroup = $optgroup;
				}

				self::option( $key, $value, $descriptor );
			}

			if ( -1 !== $current_optgroup ) {
				echo '</optgroup>';
			}
		}

		echo '</select>';
	}

	/**
	 * Echos a select option
	 *
	 * @param string $key            Key.
	 * @param string $selected_value Name.
	 * @param string $descriptor     Descriptor.
	 *
	 * @return void
	 */
	private static function option( $key, $selected_value, $descriptor ) {
		if ( ! is_array( $descriptor ) ) {
			$label    = $descriptor;
			$disabled = false;
		} else {
			$label    = $descriptor['label'];
			$disabled = ! empty( $descriptor['disabled'] );
		}

		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $selected_value, $key ) . disabled( $disabled, true, false ) . '>' .
			wp_kses( $label, self::get_allowed_html_for_wp_kses_from_content( $label ) ) . '</option>' . "\n";
	}

	/**
	 * Echos a group of radio elements values: value => label pair or value => array(label, disabled, postfix).
	 *
	 * @param string $name      Name.
	 * @param string $value     Value.
	 * @param array  $values    {
	 *     Values.
	 *
	 *     @type string $label             Label for the radio button.
	 *     @type bool   $disabled          Whether the radio button is disabled.
	 *     @type string $postfix           Postfix to be appended to the label.
	 *     @type bool   $pro_feature       Whether the radio button is a pro feature.
	 *     @type string $pro_excerpt       Excerpt for pro feature description.
	 *     @type string $pro_description   Full description for the pro feature.
	 *     @type string $intro_label       Intro label for pro feature.
	 *     @type string $score             Score associated with the pro feature.
	 *     @type string $score_label       Label for the score.
	 *     @type string $score_description Description for the score.
	 *     @type string $score_link        Link related to the score.
	 *     @type bool   $show_learn_more   Whether to show the "learn more" option for the pro feature.
	 * }
	 * @param bool   $disabled  Disabled flag for all radio buttons.
	 * @param string $separator Separator to be used between radio buttons.
	 *
	 * @return void
	 */
	public static function radiogroup( $name, $value, $values, $disabled = false, $separator = '' ) {
		$c      = Dispatcher::config();
		$is_pro = Util_Environment::is_w3tc_pro( $c );
		$first  = true;
		foreach ( $values as $key => $label_or_array ) {
			if ( $first ) {
				$first = false;
			} else {
				echo wp_kses(
					$separator,
					self::get_allowed_html_for_wp_kses_from_content( $separator )
				);
			}

			$label         = '';
			$item_disabled = false;
			$postfix       = '';
			$pro_feature   = false;

			if ( ! is_array( $label_or_array ) ) {
				$label = $label_or_array;
			} else {
				$label         = $label_or_array['label'];
				$item_disabled = $label_or_array['disabled'];
				$postfix       = isset( $label_or_array['postfix'] ) ? $label_or_array['postfix'] : '';
				$pro_feature   = isset( $label_or_array['pro_feature'] ) ? $label_or_array['pro_feature'] : false;
			}

			if ( $pro_feature ) {
				self::pro_wrap_maybe_start();
			}

			echo '<label><input type="radio" id="' . esc_attr( $name . '__' . $key ) . '" name="' . esc_attr( $name ) .
				'" value="' . esc_attr( $key ) . '"' . checked( $value, $key, false ) . disabled( $disabled || $item_disabled, true, false ) . ' />' .
				wp_kses( $label, self::get_allowed_html_for_wp_kses_from_content( $label ) ) . '</label>' .
				wp_kses( $postfix, self::get_allowed_html_for_wp_kses_from_content( $postfix ) ) . "\n";

			if ( $pro_feature ) {
				self::pro_wrap_description(
					$label_or_array['pro_excerpt'],
					$label_or_array['pro_description'],
					$name . '__' . $key
				);

				if ( ! $is_pro && isset( $label_or_array['intro_label'] ) && isset( $label_or_array['score'] ) && isset( $label_or_array['score_label'] ) && isset( $label_or_array['score_description'] ) && isset( $label_or_array['score_link'] ) ) {
					$score_block = self::get_score_block( $label_or_array['intro_label'], $label_or_array['score'], $label_or_array['score_label'], $label_or_array['score_description'], $label_or_array['score_link'] );
					echo wp_kses( $score_block, self::get_allowed_html_for_wp_kses_from_content( $score_block ) );
				}

				$show_learn_more = isset( $label_or_array['show_learn_more'] ) && is_bool( $label_or_array['show_learn_more'] ) ? $label_or_array['show_learn_more'] : true;
				self::pro_wrap_maybe_end( $name . '__' . $key, $show_learn_more );
			}
		}
	}

	/**
	 * Echos an input text element
	 *
	 * @param string $id       ID.
	 * @param string $name     Name.
	 * @param string $value    Value.
	 * @param bool   $disabled Disabled.
	 *
	 * @return void
	 */
	public static function textarea( $id, $name, $value, $disabled = false ) {
		// The "textarea" element must not have padding around the value.
		?>
		<textarea class="enabled" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="5" cols=25 style="width: 100%" <?php disabled( $disabled, true, true ); ?>><?php echo esc_textarea( $value ); ?></textarea>
		<?php
	}

	/**
	 * Echos an input checkbox element
	 *
	 * @param string $id       ID.
	 * @param string $name     Name.
	 * @param bool   $state    Whether checked or not.
	 * @param bool   $disabled Disabled.
	 * @param string $label    Label.
	 *
	 * @return void
	 */
	public static function checkbox( $id, $name, $state, $disabled = false, $label = null ) {
		if ( ! is_null( $label ) ) {
			echo '<label>';
		}

		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( ( ! $disabled ? '0' : ( $state ? '1' : '0' ) ) ) . '">' . "\n";
		echo '<input class="enabled" type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) .
			'" value="1" ' . checked( $state, true, false ) . disabled( $disabled, true, false ) . ' /> ';

		if ( ! is_null( $label ) ) {
			echo wp_kses( $label, self::get_allowed_html_for_wp_kses_from_content( $label ) ) . '</label>';
		}
	}

	/**
	 * Echos an element
	 *
	 * @param string $type     Type.
	 * @param string $id       ID.
	 * @param string $name     Name.
	 * @param mixed  $value    Value.
	 * @param bool   $disabled Disabled.
	 *
	 * @return void
	 */
	public static function element( $type, $id, $name, $value, $disabled = false ) {
		switch ( $type ) {
			case 'textbox':
				self::textbox( $id, $name, $value, $disabled );
				break;
			case 'password':
				self::passwordbox( $id, $name, $value, $disabled );
				break;
			case 'textarea':
				self::textarea( $id, $name, $value, $disabled );
				break;
			case 'checkbox':
			default:
				self::checkbox( $id, $name, $value, $disabled );
				break;
		}
	}

	/**
	 * Checkbox
	 *
	 * @param array $e {
	 *     Config.
	 *
	 *     @type string $name    The name of the checkbox.
	 *     @type mixed  $value   The value of the checkbox.
	 *     @type bool   $disabled Optional. Whether the checkbox is disabled. Defaults to false.
	 *     @type string $label   Optional. The label for the checkbox. Defaults to null.
	 * }
	 *
	 * @return void
	 */
	public static function checkbox2( $e ) {
		self::checkbox(
			$e['name'],
			$e['name'],
			$e['value'],
			( isset( $e['disabled'] ) ? $e['disabled'] : false ),
			( isset( $e['label'] ) ? $e['label'] : null )
		);
	}

	/**
	 * Radio
	 *
	 * @param array $e {
	 *     Config.
	 *
	 *     @type string $name       The name of the radio group.
	 *     @type mixed  $value      The selected value.
	 *     @type array  $values     Array of radio button options.
	 *     @type bool   $disabled   Whether the radio group is disabled.
	 *     @type string $separator  The separator between radio buttons.
	 * }
	 *
	 * @return void
	 */
	public static function radiogroup2( $e ) {
		self::radiogroup(
			$e['name'],
			$e['value'],
			$e['values'],
			$e['disabled'],
			$e['separator']
		);
	}

	/**
	 * Select
	 *
	 * @param array $e {
	 *     Config.
	 *
	 *     @type string   $name      The name of the select element.
	 *     @type mixed    $value     The selected value.
	 *     @type array    $values    The available options.
	 *     @type bool     $disabled  Optional. Whether the select should be disabled. Default false.
	 *     @type array|null $optgroups Optional. The optgroups for grouping options. Default null.
	 * }
	 *
	 * @return void
	 */
	public static function selectbox2( $e ) {
		self::selectbox(
			$e['name'],
			$e['name'],
			$e['value'],
			$e['values'],
			( isset( $e['disabled'] ) ? $e['disabled'] : false ),
			( isset( $e['optgroups'] ) ? $e['optgroups'] : null )
		);
	}

	/**
	 * Textbox
	 *
	 * @param array $e {
	 *     Config.
	 *
	 *     @type string $name        Name of the textbox.
	 *     @type string $value       Value of the textbox.
	 *     @type bool   $disabled    Whether the textbox is disabled. Default is false.
	 *     @type int    $size        Size of the textbox. Default is 20.
	 *     @type string $type        Type of the textbox. Default is 'text'.
	 *     @type string $placeholder Placeholder text for the textbox. Default is an empty string.
	 * }
	 *
	 * @return void
	 */
	public static function textbox2( $e ) {
		self::textbox(
			$e['name'],
			$e['name'],
			$e['value'],
			( isset( $e['disabled'] ) ? $e['disabled'] : false ),
			( ! empty( $e['size'] ) ? $e['size'] : 20 ),
			( ! empty( $e['type'] ) ? $e['type'] : 'text' ),
			( ! empty( $e['placeholder'] ) ? $e['placeholder'] : '' )
		);
	}

	/**
	 * Textarea
	 *
	 * @param array $e {
	 *     Config.
	 *
	 *     @type string  $name      Name of the textarea.
	 *     @type string  $value     Value of the textarea.
	 *     @type bool    $disabled  Whether the textarea is disabled. Default is false.
	 * }
	 *
	 * @return void
	 */
	public static function textarea2( $e ) {
		self::textarea(
			$e['name'],
			$e['name'],
			$e['value'],
			( isset( $e['disabled'] ) ? $e['disabled'] : false )
		);
	}

	/**
	 * Control various types of input elements based on configuration.
	 *
	 * Handles rendering of different input controls (checkbox, radiogroup, selectbox, textbox, textarea, none, and button)
	 * based on the configuration provided in the input array.
	 *
	 * @param array $a {
	 *     Configuration for the control.
	 *
	 *     @type string  $control              The type of control to render. Possible values are 'checkbox', 'radiogroup', 'selectbox', 'textbox', 'textarea', 'none', 'button'.
	 *     @type string  $control_name         The name of the control.
	 *     @type mixed   $value                The value of the control.
	 *     @type bool    $disabled             Whether the control is disabled.
	 *     @type string  $checkbox_label       The label for the checkbox (if applicable).
	 *     @type array   $radiogroup_values    The values for the radiogroup (if applicable).
	 *     @type string  $radiogroup_separator The separator for the radiogroup (if applicable).
	 *     @type array   $selectbox_values     The values for the selectbox (if applicable).
	 *     @type mixed   $selectbox_optgroups  The optgroups for the selectbox (if applicable).
	 *     @type string  $textbox_type         The type of the textbox (if applicable).
	 *     @type int     $textbox_size         The size of the textbox (if applicable).
	 *     @type string  $textbox_placeholder  The placeholder text for the textbox (if applicable).
	 *     @type string  $none_label           The label for 'none' or 'button' controls.
	 * }
	 *
	 * @return void
	 */
	public static function control2( $a ) {
		if ( 'checkbox' === $a['control'] ) {
			self::checkbox2(
				array(
					'name'     => $a['control_name'],
					'value'    => $a['value'],
					'disabled' => $a['disabled'],
					'label'    => $a['checkbox_label'],
				)
			);
		} elseif ( 'radiogroup' === $a['control'] ) {
			self::radiogroup2(
				array(
					'name'      => $a['control_name'],
					'value'     => $a['value'],
					'disabled'  => $a['disabled'],
					'values'    => $a['radiogroup_values'],
					'separator' => isset( $a['radiogroup_separator'] ) ? $a['radiogroup_separator'] : '',
				)
			);
		} elseif ( 'selectbox' === $a['control'] ) {
			self::selectbox2(
				array(
					'name'      => $a['control_name'],
					'value'     => $a['value'],
					'disabled'  => $a['disabled'],
					'values'    => $a['selectbox_values'],
					'optgroups' => isset( $a['selectbox_optgroups'] ) ? $a['selectbox_optgroups'] : null,
				)
			);
		} elseif ( 'textbox' === $a['control'] ) {
			self::textbox2(
				array(
					'name'        => $a['control_name'],
					'value'       => $a['value'],
					'disabled'    => $a['disabled'],
					'type'        => isset( $a['textbox_type'] ) ? $a['textbox_type'] : null,
					'size'        => isset( $a['textbox_size'] ) ? $a['textbox_size'] : null,
					'placeholder' => isset( $a['textbox_placeholder'] ) ? $a['textbox_placeholder'] : null,
				)
			);
		} elseif ( 'textarea' === $a['control'] ) {
			self::textarea2(
				array(
					'name'     => $a['control_name'],
					'value'    => $a['value'],
					'disabled' => $a['disabled'],
				)
			);
		} elseif ( 'none' === $a['control'] ) {
			echo wp_kses( $a['none_label'], self::get_allowed_html_for_wp_kses_from_content( $a['none_label'] ) );
		} elseif ( 'button' === $a['control'] ) {
			echo '<button type="button" class="button">' . wp_kses( $a['none_label'], self::get_allowed_html_for_wp_kses_from_content( $a['none_label'] ) ) . '</button>';
		}
	}

	/**
	 * Get table classes for tables including pro features.
	 *
	 * When on the free version, tables with pro features have additional classes added to help highlight
	 * the premium feature. If the user is on pro, this class is omitted.
	 *
	 * @since 0.14.3
	 *
	 * @return string
	 */
	public static function table_class() {
		$table_class[] = 'form-table';

		if ( ! Util_Environment::is_w3tc_pro( Dispatcher::config() ) ) {
			$table_class[] = 'w3tc-pro-feature';
		}

		return implode( ' ', $table_class );
	}

	/**
	 * Renders <tr> element with controls.
	 *
	 * Renders a table row with various controls, such as checkboxes, select boxes, textboxes, etc.
	 * The control type is determined by the keys in the `$a` array.
	 *
	 * @param array $a {
	 *     Configuration options for rendering the controls.
	 *
	 *     @type string $id          The ID for the control.
	 *     @type string $label       The label for the control.
	 *     @type string $label_class The class to apply to the label.
	 *     @type string $style       The style of the table row. Default is 'label', alternative is 'one-column'.
	 *     @type array  $checkbox    The configuration for a checkbox control.
	 *     @type string $description The description to display below the control.
	 *     @type array  $hidden      The configuration for hidden inputs.
	 *     @type string $html        Raw HTML to insert.
	 *     @type array  $radiogroup  The configuration for a radio group.
	 *     @type array  $selectbox   The configuration for a select box.
	 *     @type array  $textbox     The configuration for a textbox.
	 *     @type array  $textarea    The configuration for a textarea.
	 * }
	 *
	 * @return void
	 */
	public static function table_tr( $a ) {
		$id = isset( $a['id'] ) ? $a['id'] : '';
		$a  = apply_filters( 'w3tc_ui_settings_item', $a );

		echo '<tr><th';

		if ( isset( $a['label_class'] ) ) {
			echo ' class="' . esc_attr( $a['label_class'] ) . '"';
		}
		echo '>';
		if ( isset( $a['label'] ) ) {
			self::label( $id, $a['label'] );
		}

		echo "</th>\n<td>\n";

		foreach ( $a as $key => $e ) {
			if ( 'checkbox' === $key ) {
				self::checkbox(
					$id,
					isset( $e['name'] ) ? $e['name'] : null,
					$e['value'],
					( isset( $e['disabled'] ) ? $e['disabled'] : false ),
					( isset( $e['label'] ) ? $e['label'] : null )
				);
			} elseif ( 'description' === $key ) {
				echo '<p class="description">' . wp_kses( $e, self::get_allowed_html_for_wp_kses_from_content( $e ) ) . '</p>';
			} elseif ( 'hidden' === $key ) {
				self::hidden( '', $e['name'], $e['value'] );
			} elseif ( 'html' === $key ) {
				echo wp_kses( $e, self::get_allowed_html_for_wp_kses_from_content( $e ) );
			} elseif ( 'radiogroup' === $key ) {
				self::radiogroup(
					$e['name'],
					$e['value'],
					$e['values'],
					$e['disabled'],
					$e['separator']
				);
			} elseif ( 'selectbox' === $key ) {
				self::selectbox(
					$id,
					$e['name'],
					$e['value'],
					$e['values'],
					( isset( $e['disabled'] ) ? $e['disabled'] : false ),
					( isset( $e['optgroups'] ) ? $e['optgroups'] : null )
				);
			} elseif ( 'textbox' === $key ) {
				self::textbox(
					$id,
					$e['name'],
					$e['value'],
					( isset( $e['disabled'] ) ? $e['disabled'] : false ),
					( ! empty( $e['size'] ) ? $e['size'] : 20 ),
					( ! empty( $e['type'] ) ? $e['type'] : 'text' ),
					( ! empty( $e['placeholder'] ) ? $e['placeholder'] : '' )
				);
			} elseif ( 'textarea' === $key ) {
				self::textarea(
					$id,
					$e['name'],
					$e['value'],
					( isset( $e['disabled'] ) ? $e['disabled'] : false )
				);
			}
		}

		echo "</td></tr>\n";
	}

	/**
	 * Prints configuration item UI based on description.
	 *
	 * @param array $a {
	 *     Config.
	 *
	 *     @type string $key                 Configuration key.
	 *     @type string $label               Configuration key's label as introduced to the user.
	 *     @type mixed  $value               The value of the configuration item.
	 *     @type bool   $disabled            If the control is disabled.
	 *     @type string $control             Type of control (checkbox, radiogroup, selectbox, textbox).
	 *     @type string $checkbox_label      Text shown after the checkbox.
	 *     @type array  $radiogroup_values   Array of possible values for radiogroup.
	 *     @type array  $selectbox_values    Array of possible values for dropdown.
	 *     @type array  $selectbox_optgroups Option groups for selectbox.
	 *     @type string $textbox_size        Size of the textbox.
	 *     @type string $control_after       Content to add after control.
	 *     @type string $description         Description shown to the user below the control.
	 *     @type bool   $show_in_free        Whether to show the item in the free edition. Defaults to true.
	 *     @type string $label_class         CSS class for the label.
	 *     @type string $control_name        Name attribute for the control.
	 *     @type string $intro_label         Introductory label for the score block.
	 *     @type mixed  $score               Score for the item.
	 *     @type string $score_label         Label for the score.
	 *     @type string $score_description   Description for the score.
	 *     @type string $score_link          Link for more information about the score.
	 *     @type string $style               CSS style for the control.
	 * }
	 *
	 * @return void
	 */
	public static function config_item( $a ) {
		/*
		 * Some items we do not want shown in the free edition.
		 *
		 * By default, they will show in free, unless 'show_in_free' is specifically passed in as false.
		 */
		$is_w3tc_free = ! Util_Environment::is_w3tc_pro( Dispatcher::config() );
		$show_in_free = ! isset( $a['show_in_free'] ) || (bool) $a['show_in_free'];
		if ( ! $show_in_free && $is_w3tc_free ) {
			return;
		}

		$a = self::config_item_preprocess( $a );

		if ( 'w3tc_single_column' === $a['label_class'] ) {
			echo '<tr><th colspan="2">';
		} else {
			echo '<tr><th class="' . esc_attr( $a['label_class'] ) . '">';

			if ( ! empty( $a['label'] ) ) {
				self::label( $a['control_name'], $a['label'] );
			}

			echo "</th>\n<td>\n";
		}

		self::control2( $a );

		if ( isset( $a['control_after'] ) ) {
			echo wp_kses(
				$a['control_after'],
				self::get_allowed_html_for_wp_kses_from_content( $a['control_after'] )
			);
		}
		if ( isset( $a['description'] ) ) {
			echo wp_kses(
				sprintf(
					'%1$s%2$s%3$s',
					'<p class="description">',
					$a['description'],
					'</p>'
				),
				array(
					'p'       => array(
						'class' => array(),
					),
					'acronym' => array(
						'title' => array(),
					),
				)
			);
		}

		if ( $is_w3tc_free && isset( $a['intro_label'] ) && isset( $a['score'] ) && isset( $a['score_label'] ) && isset( $a['score_description'] ) && isset( $a['score_link'] ) ) {
			$score_block = self::get_score_block( $a['score'], $a['score_label'], $a['score_description'], $a['score_link'] );
			echo wp_kses( $score_block, self::get_allowed_html_for_wp_kses_from_content( $score_block ) );
		}

		echo ( isset( $a['style'] ) ? '</th>' : '</td>' );
		echo "</tr>\n";
	}

	/**
	 * Config item extension enabled.
	 *
	 * Outputs the HTML for the config item extension, including a checkbox for enabling the extension,
	 * and additional information such as description, score block, and pro features.
	 *
	 * @param array $a {
	 *     Config.
	 *
	 *     @type string $label_class       The label class for the config item.
	 *     @type string $control_name      The control name for the config item.
	 *     @type string $label             The label for the config item.
	 *     @type string $checkbox_label    The label for the checkbox.
	 *     @type string $extension_id      The extension ID.
	 *     @type bool   $disabled          Whether the checkbox should be disabled.
	 *     @type string $description       The description for the config item.
	 *     @type string $intro_label       The intro label for the score block (if applicable).
	 *     @type int    $score             The score for the score block (if applicable).
	 *     @type string $score_label       The label for the score (if applicable).
	 *     @type string $score_description The description for the score (if applicable).
	 *     @type string $score_link        The link for the score (if applicable).
	 *     @type bool   $pro               Whether the config item is pro.
	 *     @type bool   $show_learn_more   Whether to show the "learn more" link (if applicable).
	 *     @type string $style             Custom style for the config item (optional).
	 * }
	 *
	 * @return void
	 */
	public static function config_item_extension_enabled( $a ) {
		$c      = Dispatcher::config();
		$is_pro = Util_Environment::is_w3tc_pro( $c );

		if ( 'w3tc_single_column' === $a['label_class'] ) {
			echo '<tr><th colspan="2">';
		} else {
			echo '<tr><th class="' . esc_attr( $a['label_class'] ) . '">';

			if ( ! empty( $a['label'] ) ) {
				self::label( $a['control_name'], $a['label'] );
			}

			echo "</th>\n<td>\n";
		}

		if ( isset( $a['pro'] ) ) {
			self::pro_wrap_maybe_start();
		}

		self::checkbox2(
			array(
				'name'     => 'extension__' . self::config_key_to_http_name( $a['extension_id'] ),
				'value'    => $c->is_extension_active_frontend( $a['extension_id'] ),
				'label'    => $a['checkbox_label'],
				'disabled' => isset( $a['disabled'] ) ? $a['disabled'] : false,
			)
		);

		if ( isset( $a['description'] ) ) {
			echo '<p class="description">' . wp_kses( $a['description'], self::get_allowed_html_for_wp_kses_from_content( $a['description'] ) ) . '</p>';
		}

		if ( ! $is_pro && isset( $a['intro_label'] ) && isset( $a['score'] ) && isset( $a['score_label'] ) && isset( $a['score_description'] ) && isset( $a['score_link'] ) ) {
			$score_block = self::get_score_block( $a['intro_label'], $a['score'], $a['score_label'], $a['score_description'], $a['score_link'] );
			echo wp_kses( $score_block, self::get_allowed_html_for_wp_kses_from_content( $score_block ) );
		}

		if ( isset( $a['pro'] ) ) {
			$show_learn_more = isset( $a['show_learn_more'] ) && is_bool( $a['show_learn_more'] ) ? $a['show_learn_more'] : true;
			self::pro_wrap_maybe_end( 'extension__' . self::config_key_to_http_name( $a['extension_id'] ), $show_learn_more );
		}

		echo ( isset( $a['style'] ) ? '</th>' : '</td>' );
		echo "</tr>\n";
	}

	/**
	 * Config item pro.
	 *
	 * @param array $a {
	 *     Configuration settings for the item.
	 *
	 *     @type string $label_class       The CSS class for the label.
	 *     @type string $control_name      The name of the control.
	 *     @type string $label             The label text for the control.
	 *     @type string $wrap_separate     Whether to wrap the description separately.
	 *     @type string $no_wrap           Whether to disable wrapping.
	 *     @type string $control_after     HTML to output after the control.
	 *     @type string $description       The description of the control.
	 *     @type string $excerpt           The excerpt text for the description.
	 *     @type string $intro_label       The intro label for the score block.
	 *     @type string $score             The score value.
	 *     @type string $score_label       The label for the score.
	 *     @type string $score_description The description for the score.
	 *     @type string $score_link        The link associated with the score.
	 *     @type bool   $show_learn_more   Whether to show the "Learn More" link.
	 * }
	 *
	 * @return void
	 */
	public static function config_item_pro( $a ) {
		$c      = Dispatcher::config();
		$is_pro = Util_Environment::is_w3tc_pro( $c );
		$a      = self::config_item_preprocess( $a );

		if ( 'w3tc_single_column' === $a['label_class'] ) {
			echo '<tr><th colspan="2">';
		} elseif ( 'w3tc_no_trtd' !== $a['label_class'] ) {
			echo '<tr><th class="' . esc_attr( $a['label_class'] ) . '">';

			if ( ! empty( $a['label'] ) ) {
				self::label( $a['control_name'], $a['label'] );
			}

			echo "</th>\n<td>\n";
		}

		// If wrap_separate is not set we wrap everything.
		if ( ! isset( $a['wrap_separate'] ) && ! isset( $a['no_wrap'] ) ) {
			self::pro_wrap_maybe_start();
		}

		self::control2( $a );

		if ( isset( $a['control_after'] ) ) {
			echo wp_kses( $a['control_after'], self::get_allowed_html_for_wp_kses_from_content( $a['control_after'] ) );
		}

		// If wrap_separate is set we wrap only the description.
		if ( isset( $a['wrap_separate'] ) && ! isset( $a['no_wrap'] ) ) {
			// If not pro we add a spacer for better separation of control element and wrapper.
			if ( ! $is_pro ) {
				echo '<br/><br/>';
			}
			self::pro_wrap_maybe_start();
		}

		if ( isset( $a['description'] ) ) {
			self::pro_wrap_description( $a['excerpt'], $a['description'], $a['control_name'] );
		}

		if ( ! $is_pro && ! isset( $a['no_wrap'] ) && isset( $a['intro_label'] ) && isset( $a['score'] ) && isset( $a['score_label'] ) && isset( $a['score_description'] ) && isset( $a['score_link'] ) ) {
			$score_block = self::get_score_block( $a['intro_label'], $a['score'], $a['score_label'], $a['score_description'], $a['score_link'] );
			echo wp_kses( $score_block, self::get_allowed_html_for_wp_kses_from_content( $score_block ) );
		}

		if ( ! isset( $a['no_wrap'] ) ) {
			$show_learn_more = isset( $a['show_learn_more'] ) && is_bool( $a['show_learn_more'] ) ? $a['show_learn_more'] : true;
			self::pro_wrap_maybe_end( $a['control_name'], $show_learn_more );
		}

		if ( 'w3tc_no_trtd' !== $a['label_class'] ) {
			echo ( isset( $a['style'] ) ? '</th>' : '</td>' );
			echo "</tr>\n";
		}
	}

	/**
	 * Config item preprocess.
	 *
	 * Processes the configuration item and applies necessary defaults or values based on the config.
	 *
	 * @param array $a {
	 *     Config.
	 *
	 *     @type string $key          The key of the configuration item.
	 *     @type mixed  $value        The value of the configuration item. If not set, defaults are applied.
	 *     @type bool   $disabled     Whether the configuration item is disabled. Defaults to a sealed state.
	 *     @type string $label        The label of the configuration item. Defaults to generated label.
	 *     @type string $control_name The control name for the configuration item.
	 *     @type string $label_class  The CSS class for the label. Defaults to an empty string or 'w3tc_config_checkbox' for checkboxes.
	 *     @type string $control      The type of control (e.g., checkbox).
	 * }
	 *
	 * @return array Processed configuration item.
	 */
	public static function config_item_preprocess( $a ) {
		$c = Dispatcher::config();

		if ( ! isset( $a['value'] ) || is_null( $a['value'] ) ) {
			$a['value'] = $c->get( $a['key'] ) ?? '';
			if ( is_array( $a['value'] ) ) {
				$a['value'] = implode( "\n", $a['value'] );
			}
		}

		if ( ! isset( $a['disabled'] ) || is_null( $a['disabled'] ) ) {
			$a['disabled'] = $c->is_sealed( $a['key'] );
		}

		if ( empty( $a['label'] ) ) {
			$a['label'] = self::config_label( $a['key'] );
		}

		$a['control_name'] = self::config_key_to_http_name( $a['key'] );
		$a['label_class']  = empty( $a['label_class'] ) ? '' : $a['label_class'];
		if ( empty( $a['label_class'] ) && 'checkbox' === $a['control'] ) {
			$a['label_class'] = 'w3tc_config_checkbox';
		}

		$action_key = $a['key'];
		if ( is_array( $action_key ) ) {
			$action_key = 'extension.' . $action_key[0] . '.' . $action_key[1];
		}

		return apply_filters( 'w3tc_ui_config_item_' . $action_key, $a );
	}

	/**
	 * Displays config item - caching engine selectbox.
	 *
	 * @param array $a {
	 *     Config settings.
	 *
	 *     @type string $key           The key for the config item.
	 *     @type string $label         Optional. The label for the selectbox.
	 *     @type bool   $disabled      Optional. Whether the config item should be disabled.
	 *     @type bool   $empty_value   Optional. Whether to include an empty value option. Default is false.
	 *     @type string $control_after Optional. Additional content to display after the control.
	 *     @type bool   $pro           Optional. If set, calls the pro version of the config item function.
	 * }
	 *
	 * @return void
	 */
	public static function config_item_engine( $a ) {
		if ( isset( $a['empty_value'] ) && $a['empty_value'] ) {
			$values[''] = array(
				'label' => 'Please select a method',
			);
		}

		$values['file']         = array(
			'label'    => __( 'Disk', 'w3-total-cache' ),
			'optgroup' => 0,
		);
		$values['apc']          = array(
			'disabled' => ! Util_Installed::apc(),
			'label'    => __( 'Opcode: Alternative PHP Cache (APC / APCu)', 'w3-total-cache' ),
			'optgroup' => 1,
		);
		$values['eaccelerator'] = array(
			'disabled' => ! Util_Installed::eaccelerator(),
			'label'    => __( 'Opcode: eAccelerator', 'w3-total-cache' ),
			'optgroup' => 1,
		);
		$values['xcache']       = array(
			'disabled' => ! Util_Installed::xcache(),
			'label'    => __( 'Opcode: XCache', 'w3-total-cache' ),
			'optgroup' => 1,
		);
		$values['wincache']     = array(
			'disabled' => ! Util_Installed::wincache(),
			'label'    => __( 'Opcode: WinCache', 'w3-total-cache' ),
			'optgroup' => 1,
		);
		$values['memcached']    = array(
			'disabled' => ! Util_Installed::memcached(),
			'label'    => __( 'Memcached', 'w3-total-cache' ),
			'optgroup' => 2,
		);
		$values['redis']        = array(
			'disabled' => ! Util_Installed::redis(),
			'label'    => __( 'Redis', 'w3-total-cache' ),
			'optgroup' => 2,
		);

		$item_engine_config = array(
			'key'                 => $a['key'],
			'label'               => ( isset( $a['label'] ) ? $a['label'] : null ),
			'disabled'            => ( isset( $a['disabled'] ) ? $a['disabled'] : null ),
			'control'             => 'selectbox',
			'selectbox_values'    => $values,
			'selectbox_optgroups' => array(
				__( 'Shared Server:', 'w3-total-cache' ),
				__( 'Dedicated / Virtual Server:', 'w3-total-cache' ),
				__( 'Multiple Servers:', 'w3-total-cache' ),
			),
			'control_after'       => isset( $a['control_after'] ) ? $a['control_after'] : null,
		);

		if ( isset( $a['pro'] ) ) {
			self::config_item_pro( $item_engine_config );
		} else {
			self::config_item( $item_engine_config );
		}
	}

	/**
	 * Pro wrap start
	 *
	 * @return void
	 */
	public static function pro_wrap_maybe_start() {
		if ( Util_Environment::is_w3tc_pro( Dispatcher::config() ) ) {
			return;
		}

		?>
		<div class="w3tc-gopro">
			<div class="w3tc-gopro-ribbon"><span>&bigstar; PRO</span></div>
			<div class="w3tc-gopro-content">
		<?php
	}

	/**
	 * Pro wrap description
	 *
	 * @param string $excerpt_clean Clean exerpt.
	 * @param string $description   Description.
	 * @param string $data_href     Data link.
	 *
	 * @return void
	 */
	public static function pro_wrap_description( $excerpt_clean, $description, $data_href ) {
		echo '<p class="description w3tc-gopro-excerpt">' . wp_kses( $excerpt_clean, self::get_allowed_html_for_wp_kses_from_content( $excerpt_clean ) ) . '</p>';

		if ( ! empty( $description ) ) {
			$d = array_map(
				function ( $e ) {
					return '<p class="description">' . wp_kses( $e, self::get_allowed_html_for_wp_kses_from_content( $e ) ) . '</p>';
				},
				$description
			);

			$descriptions = implode( "\n", $d );

			echo '<div class="w3tc-gopro-description">' . wp_kses( $descriptions, self::get_allowed_html_for_wp_kses_from_content( $descriptions ) ) . '</div>';
			echo '<a href="#" class="w3tc-gopro-more" data-href="w3tc-gopro-more-' . esc_url( $data_href ) . '">' . esc_html( __( 'Show More', 'w3-total-cache' ) ) . '<span class="dashicons dashicons-arrow-down-alt2"></span></a>';
		}
	}

	/**
	 * Pro wrap end
	 *
	 * @param string $button_data_src Butta href.
	 * @param bool   $show_learn_more Show more flag.
	 *
	 * @return void
	 */
	public static function pro_wrap_maybe_end( $button_data_src, $show_learn_more = true ) {
		if ( Util_Environment::is_w3tc_pro( Dispatcher::config() ) ) {
			return;
		}

		?>
			</div>
			<?php if ( $show_learn_more ) { ?>
			<div class="w3tc-gopro-action">
				<button class="button w3tc-gopro-button button-buy-plugin" data-src="<?php echo esc_attr( $button_data_src ); ?>">
					Learn more about Pro
				</button>
			</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Pro wrap start - version 2
	 *
	 * @return void
	 */
	public static function pro_wrap_maybe_start2() {
		if ( Util_Environment::is_w3tc_pro( Dispatcher::config() ) ) {
			return;
		}

		?>
		<div class="updated w3tc_note" id="licensing_terms" style="display: flex; align-items: center">
			<p style="flex-grow: 1">
		<?php
	}

	/**
	 * Pro wrap end - version 2
	 *
	 * @param string $button_data_src     Button link.
	 * @param bool   $show_unlock_feature Show unlock feature flag.
	 *
	 * @return void
	 */
	public static function pro_wrap_maybe_end2( $button_data_src, $show_unlock_feature = true ) {
		if ( Util_Environment::is_w3tc_pro( Dispatcher::config() ) ) {
			return;
		}

		?>
			</p>
			<?php if ( $show_unlock_feature ) { ?>
			<div style="text-align: right">
				<button class="button w3tc-gopro-button button-buy-plugin" data-src="<?php echo esc_attr( $button_data_src ); ?>">
					Unlock Feature
				</button>
			</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * On subblogs - shows button to enable/disable custom configuration.
	 *
	 * @param array $a {
	 *     Config.
	 *
	 *     @type string $key Config key *_overloaded which are managed.
	 * }
	 *
	 * @return void
	 */
	public static function config_overloading_button( $a ) {
		$c = Dispatcher::config();
		if ( $c->is_master() ) {
			return;
		}

		if ( $c->get_boolean( $a['key'] ) ) {
			$name  = 'w3tc_config_overloaded_disable~' . self::config_key_to_http_name( $a['key'] );
			$value = __( 'Use common settings', 'w3-total-cache' );
		} else {
			$name  = 'w3tc_config_overloaded_enable~' . self::config_key_to_http_name( $a['key'] );
			$value = __( 'Use specific settings', 'w3-total-cache' );
		}

		echo '<div style="float: right">';
		echo '<input type="submit" class="button" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
		echo '</div>';
	}

	/**
	 * Get the admin URL based on the path and the interface (network or site).
	 *
	 * @param  string $path Admin path/URI.
	 *
	 * @return string
	 */
	public static function admin_url( $path ) {
		return is_network_admin() ? network_admin_url( $path ) : admin_url( $path );
	}

	/**
	 * Returns a preview link with current state
	 *
	 * @return string
	 */
	public static function preview_link() {
		return self::button_link(
			__( 'Preview', 'w3-total-cache' ),
			self::url( array( 'w3tc_default_previewing' => 'y' ) ),
			true
		);
	}

	/**
	 * Takes seconds and converts to array('Nh ','Nm ', 'Ns ', 'Nms ') or "Nh Nm Ns Nms"
	 *
	 * @param unknown $input        Input.
	 * @param bool    $string_value String.
	 *
	 * @return array|string
	 */
	public static function secs_to_time( $input, $string_value = true ) {
		$input   = (float) $input;
		$time    = array();
		$msecs   = floor( $input * 1000 % 1000 );
		$seconds = $input % 60;

		$minutes = floor( $input / 60 ) % 60;
		$hours   = floor( $input / 60 / 60 ) % 60;

		if ( $hours ) {
			$time[] = $hours;
		}
		if ( $minutes ) {
			$time[] = sprintf( '%dm', $minutes );
		}
		if ( $seconds ) {
			$time[] = sprintf( '%ds', $seconds );
		}
		if ( $msecs ) {
			$time[] = sprintf( '%dms', $msecs );
		}

		if ( empty( $time ) ) {
			$time[] = sprintf( '%dms', 0 );
		}
		if ( $string_value ) {
			return implode( ' ', $time );
		}
		return $time;
	}

	/**
	 * Returns option name accepted by W3TC as http paramter from its id (full name from config file).
	 *
	 * @param mixed $id ID key string/array.
	 *
	 * @return string
	 */
	public static function config_key_to_http_name( $id ) {
		$id = isset( $id ) ? $id : '';

		if ( is_array( $id ) ) {
			$id = $id[0] . '___' . $id[1];
		}

		return str_replace( '.', '__', $id );
	}

	/**
	 * Converts configuration key returned in http _GET/_POST to configuration key
	 *
	 * @param string $http_key HTTP key.
	 *
	 * @return string
	 */
	public static function config_key_from_http_name( $http_key ) {
		$a = explode( '___', $http_key );
		if ( count( $a ) === 2 ) {
			$a[0] = self::config_key_from_http_name( $a[0] );
			$a[1] = self::config_key_from_http_name( $a[1] );
			return $a;
		}

		return str_replace( '__', '.', $http_key );
	}

	/**
	 * Get allowed HTML fro wpkses
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	 *
	 * @param string $content Content.
	 *
	 * @return array
	 */
	public static function get_allowed_html_for_wp_kses_from_content( $content ) {
		$allowed_html = array();

		if ( empty( $content ) ) {
			return $allowed_html;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $content );
		foreach ( $dom->getElementsByTagName( '*' ) as $tag ) {
			$tagname = $tag->tagName;
			foreach ( $tag->attributes as $attribute_name => $attribute_val ) {
				$allowed_html[ $tagname ][ $attribute_name ] = array();
			}
			$allowed_html[ $tagname ] = empty( $allowed_html[ $tagname ] ) ? array() : $allowed_html[ $tagname ];
		}

		return $allowed_html;
	}

	/**
	 * Prints breadcrumb
	 *
	 * @return void
	 */
	public static function print_breadcrumb() {
		$page         = ! empty( Util_Admin::get_current_extension() ) ? Util_Admin::get_current_extension() : Util_Admin::get_current_page();
		$page_mapping = Util_PageUrls::get_page_mapping( $page );
		$parent       = isset( $page_mapping['parent_name'] ) ?
			'<span class="dashicons dashicons-arrow-right-alt2"></span><a href="' . esc_url( $page_mapping['parent_link'] ) . '">' . esc_html( $page_mapping['parent_name'] ) . '</a>' : '';
		$current      = isset( $page_mapping['page_name'] ) ?
			'<span class="dashicons dashicons-arrow-right-alt2"></span><span>' . esc_html( $page_mapping['page_name'] ) . '</span>' : '';
		?>
		<p id="w3tc-breadcrumb">
			<span class="dashicons dashicons-admin-home"></span>
			<a href="<?php echo esc_url( self::admin_url( 'admin.php?page=w3tc_dashboard' ) ); ?>">W3 Total Cache</a>
			<?php echo wp_kses( $parent, self::get_allowed_html_for_wp_kses_from_content( $parent ) ); ?>
			<?php echo wp_kses( $current, self::get_allowed_html_for_wp_kses_from_content( $current ) ); ?>
		</p>
		<?php
	}

	/**
	 * Prints the options anchor menu
	 *
	 * phpcs:disable WordPress.Security.NonceVerification.Recommended
	 * phpcs:disable PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
	 *
	 * @param array $custom_areas Custom Areas.
	 *
	 * @return void
	 */
	public static function print_options_menu( $custom_areas = array() ) {
		$config            = Dispatcher::config();
		$state             = Dispatcher::config_state();
		$page              = Util_Admin::get_current_page();
		$show_purge_link   = 'bunnycdn' === $config->get_string( 'cdn.engine' ) || 'bunnycdn' === $config->get_string( 'cdnfsd.engine' );
		$licensing_visible = (
			( ! Util_Environment::is_wpmu() || is_network_admin() ) &&
			! ini_get( 'w3tc.license_key' ) &&
			'host_valid' !== $state->get_string( 'license.status' )
		);

		switch ( $page ) {
			case 'w3tc_general':
				if ( ! empty( $_REQUEST['view'] ) ) {
					break;
				}

				$message_bus_link = array();
				if ( Util_Environment::is_w3tc_pro( $config ) ) {
					$message_bus_link = array(
						array(
							'id'   => 'amazon_sns',
							'text' => esc_html__( 'Message Bus', 'w3-total-cache' ),
						),
					);
				}

				$licensing_link = array();
				if ( $licensing_visible ) {
					$licensing_link = array(
						array(
							'id'   => 'licensing',
							'text' => esc_html__( 'Licensing', 'w3-total-cache' ),
						),
					);
				}

				$links = array_merge(
					array(
						array(
							'id'   => 'general',
							'text' => esc_html__( 'General', 'w3-total-cache' ),
						),
						array(
							'id'   => 'page_cache',
							'text' => esc_html__( 'Page Cache', 'w3-total-cache' ),
						),
						array(
							'id'   => 'minify',
							'text' => esc_html__( 'Minify', 'w3-total-cache' ),
						),
						array(
							'id'   => 'system_opcache',
							'text' => esc_html__( 'Opcode Cache', 'w3-total-cache' ),
						),
						array(
							'id'   => 'database_cache',
							'text' => esc_html__( 'Database Cache', 'w3-total-cache' ),
						),
						array(
							'id'   => 'object_cache',
							'text' => esc_html__( 'Object Cache', 'w3-total-cache' ),
						),
						array(
							'id'   => 'browser_cache',
							'text' => esc_html__( 'Browser Cache', 'w3-total-cache' ),
						),
						array(
							'id'   => 'allcache_wp_cron',
							'text' => esc_html__( 'Purge via WP Cron', 'w3-total-cache' ),
						),
						array(
							'id'   => 'cdn',
							'text' => wp_kses(
								sprintf(
									// translators: 1 opening HTML abbr tag, 2 closing HTML abbr tag.
									__(
										'%1$sCDN%2$s',
										'w3-total-cache'
									),
									'<abbr title="' . esc_attr__( 'Content Delivery Network', 'w3-total-cache' ) . '">',
									'</abbr>'
								),
								array(
									'abbr' => array(
										'title' => array(),
									),
								)
							),
						),
						array(
							'id'   => 'reverse_proxy',
							'text' => esc_html__( 'Reverse Proxy', 'w3-total-cache' ),
						),
					),
					$message_bus_link,
					$custom_areas,
					$licensing_link,
					array(
						array(
							'id'   => 'miscellaneous',
							'text' => esc_html__( 'Miscellaneous', 'w3-total-cache' ),
						),
						array(
							'id'   => 'debug',
							'text' => esc_html__( 'Debug', 'w3-total-cache' ),
						),
						array(
							'id'   => 'image_service',
							'text' => esc_html__( 'WebP Converter', 'w3-total-cache' ),
						),
						array(
							'id'   => 'google_pagespeed',
							'text' => __( 'Google PageSpeed', 'w3-total-cache' ),
						),
						array(
							'id'   => 'settings',
							'text' => esc_html__( 'Import / Export Settings', 'w3-total-cache' ),
						),
					)
				);

				$links_buff = array();
				foreach ( $links as $link ) {
					$links_buff[] = "<a href=\"#{$link['id']}\">{$link['text']}</a>";
				}

				?>
				<div id="w3tc-options-menu">
					<?php
					echo wp_kses(
						implode( ' | ', $links_buff ),
						array(
							'a' => array(
								'href'  => array(),
								'class' => array(),
							),
						)
					);
					?>
				</div>
				<?php
				break;

			case 'w3tc_pgcache':
				?>
				<div id="w3tc-options-menu">
					<a href="#general"><?php esc_html_e( 'General', 'w3-total-cache' ); ?></a> |
					<a href="#mirrors"><?php esc_html_e( 'Aliases', 'w3-total-cache' ); ?></a> |
					<a href="#cache_preload"><?php esc_html_e( 'Cache Preload', 'w3-total-cache' ); ?></a> |
					<a href="#purge_policy"><?php esc_html_e( 'Purge Policy', 'w3-total-cache' ); ?></a> |
					<a href="#rest"><?php esc_html_e( 'Rest API', 'w3-total-cache' ); ?></a> |
					<a href="#advanced"><?php esc_html_e( 'Advanced', 'w3-total-cache' ); ?></a> |
					<a href="#pgcache_wp_cron"><?php esc_html_e( 'Purge via WP Cron', 'w3-total-cache' ); ?></a> |
					<a href="#notes"><?php esc_html_e( 'Note(s)', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_minify':
				?>
				<div id="w3tc-options-menu">
					<a href="#general"><?php esc_html_e( 'General', 'w3-total-cache' ); ?></a> |
					<a href="#html_xml">
						<?php
						echo wp_kses(
							sprintf(
								// translators: 1 opening HTML acronym tag, 2 closing HTML acronym tag,
								// translators: 3 opening HTML acronym tag, 4 closing HTML acronym tag.
								__(
									'%1$sHTML%2$s &amp; %3$sXML%4$s',
									'w3-total-cache'
								),
								'<acronym title="' . esc_attr__( 'Hypertext Markup Language', 'w3-total-cache' ) . '">',
								'</acronym>',
								'<acronym title="' . esc_attr__( 'eXtensible Markup Language', 'w3-total-cache' ) . '">',
								'</acronym>'
							),
							array(
								'acronym' => array(
									'title' => array(),
								),
							)
						);
						?>
					</a> |
					<a href="#js">
						<?php
						echo wp_kses(
							sprintf(
								// translators: 1 opening HTML acronym tag, 2 closing HTML acronym tag.
								__(
									'%1$sJS%2$s',
									'w3-total-cache'
								),
								'<acronym title="' . esc_attr__( 'JavaScript', 'w3-total-cache' ) . '">',
								'</acronym>'
							),
							array(
								'acronym' => array(
									'title' => array(),
								),
							)
						);
						?>
					</a> |
					<a href="#css">
						<?php
						echo wp_kses(
							sprintf(
								// translators: 1 opening HTML acronym tag, 2 closing HTML acronym tag.
								__(
									'%1$sCSS%2$s',
									'w3-total-cache'
								),
								'<acronym title="' . esc_attr__( 'Cascading Style Sheet', 'w3-total-cache' ) . '">',
								'</acronym>'
							),
							array(
								'acronym' => array(
									'title' => array(),
								),
							)
						);
						?>
					</a> |
					<a href="#advanced"><?php esc_html_e( 'Advanced', 'w3-total-cache' ); ?></a> |
					<a href="#minify_wp_cron"><?php esc_html_e( 'Purge via WP Cron', 'w3-total-cache' ); ?></a> |
					<a href="#notes"><?php esc_html_e( 'Note(s)', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_dbcache':
				?>
				<div id="w3tc-options-menu">
					<a href="#general"><?php esc_html_e( 'General', 'w3-total-cache' ); ?></a> |
					<a href="#advanced"><?php esc_html_e( 'Advanced', 'w3-total-cache' ); ?></a> |
					<a href="#dbcache_wp_cron"><?php esc_html_e( 'Purge via WP Cron', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_objectcache':
				?>
				<div id="w3tc-options-menu">
					<a href="#advanced"><?php esc_html_e( 'Advanced', 'w3-total-cache' ); ?></a> |
					<a href="#objectcache_wp_cron"><?php esc_html_e( 'Purge via WP Cron', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_browsercache':
				?>
				<div id="w3tc-options-menu">
					<a href="#general"><?php esc_html_e( 'General', 'w3-total-cache' ); ?></a> |
					<a href="#css_js">
						<?php
						echo wp_kses(
							sprintf(
								// translators: 1 opening HTML acronym tag, 2 closing HTML acronym tag,
								// translators: 3 opening HTML acronym tag, 4 closing HTML acronym tag.
								__(
									'%1$sCSS%2$s &amp; %3$sJS%4$s',
									'w3-total-cache'
								),
								'<acronym title="' . esc_attr__( 'Cascading Style Sheet', 'w3-total-cache' ) . '">',
								'</acronym>',
								'<acronym title="' . esc_attr__( 'JavaScript', 'w3-total-cache' ) . '">',
								'</acronym>'
							),
							array(
								'acronym' => array(
									'title' => array(),
								),
							)
						);
						?>
					</a> |
					<a href="#html_xml">
						<?php
						echo wp_kses(
							sprintf(
								// translators: 1 opening HTML acronym tag, 2 closing HTML acronym tag,
								// translators: 3 opening HTML acronym tag, 4 closing HTML acronym tag.
								__(
									'%1$sHTML%2$s &amp; %3$sXML%4$s',
									'w3-total-cache'
								),
								'<acronym title="' . esc_attr__( 'Hypertext Markup Language', 'w3-total-cache' ) . '">',
								'</acronym>',
								'<acronym title="' . esc_attr__( 'eXtensible Markup Language', 'w3-total-cache' ) . '">',
								'</acronym>'
							),
							array(
								'acronym' => array(
									'title' => array(),
								),
							)
						);
						?>
					</a> |
					<a href="#media"><?php esc_html_e( 'Media', 'w3-total-cache' ); ?></a> |
					<a href="#security"><?php esc_html_e( 'Security Headers', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_cachegroups':
				?>
				<div id="w3tc-options-menu">
					<a href="#manage-uag"><?php esc_html_e( 'Manage User Agent Groups', 'w3-total-cache' ); ?></a> |
					<a href="#manage-rg"><?php esc_html_e( 'Manage Referrer Groups', 'w3-total-cache' ); ?></a> |
					<a href="#manage-cg"><?php esc_html_e( 'Manage Cookie Groups', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_cdn':
				?>
				<div id="w3tc-options-menu">
					<a href="#general"><?php esc_html_e( 'General', 'w3-total-cache' ); ?></a> |
				<?php if ( ! empty( $config->get_string( 'cdn.engine' ) ) ) : ?>
					<a href="#configuration"><?php esc_html_e( 'Configuration (Objects)', 'w3-total-cache' ); ?></a> |
				<?php endif; ?>
				<?php if ( ! empty( $config->get_string( 'cdnfsd.engine' ) ) ) : ?>
					<a href="#configuration-fsd"><?php esc_html_e( 'Configuration (FSD)', 'w3-total-cache' ); ?></a> |
				<?php endif; ?>
				<?php if ( $show_purge_link ) : ?>
					<a href="#purge-urls"><?php esc_html_e( 'Purge', 'w3-total-cache' ); ?></a> |
				<?php endif; ?>
					<a href="#advanced"><?php esc_html_e( 'Advanced', 'w3-total-cache' ); ?></a> |
					<a href="#notes"><?php esc_html_e( 'Note(s)', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_userexperience':
				?>
				<div id="w3tc-options-menu">
					<?php
					$subnav_links = array( '<a href="#lazy-loading">' . esc_html__( 'Lazy Loading', 'w3-total-cache' ) . '</a>' );

					if ( UserExperience_DeferScripts_Extension::is_enabled() ) {
						$subnav_links[] = '<a href="#defer-scripts">' . esc_html__( 'Delay Scripts', 'w3-total-cache' ) . '</a>';
					}

					if ( UserExperience_Remove_CssJs_Extension::is_enabled() ) {
						$subnav_links[] = '<a href="#remove-cssjs">' . esc_html__( 'Remove CSS/JS On Homepage', 'w3-total-cache' ) . '</a>';
						$subnav_links[] = '<a href="#remove-cssjs-singles">' . esc_html__( 'Remove CSS/JS Individually', 'w3-total-cache' ) . '</a>';
					}

					if ( UserExperience_Preload_Requests_Extension::is_enabled() ) {
						$subnav_links[] = '<a href="#preload-requests">' . esc_html__( 'Preload Requests', 'w3-total-cache' ) . '</a>';
					}

					// If there's only 1 meta box on the page, no need for nav links.
					echo count( $subnav_links ) > 1 ? implode( ' | ', $subnav_links ) : '';
					?>
				</div>
				<?php
				break;

			case 'w3tc_install':
				?>
				<div id="w3tc-options-menu">
					<a href="#initial"><?php esc_html_e( 'Initial Installation', 'w3-total-cache' ); ?></a> |
					<?php if ( count( $rewrite_rules_descriptors ) ) : ?>
						<a href="#rules"><?php esc_html_e( 'Rewrite Rules', 'w3-total-cache' ); ?></a> |
					<?php endif ?>
					<?php if ( count( $other_areas ) ) : ?>
						<a href="#other"><?php esc_html_e( 'Other', 'w3-total-cache' ); ?></a> |
					<?php endif ?>
					<a href="#additional"><?php esc_html_e( 'Services', 'w3-total-cache' ); ?></a> |
					<a href="#modules">
						<?php
						echo wp_kses(
							sprintf(
								// translators: 1 opening HTML acronym tag, 2 closing HTML acronym tag.
								__(
									'%1$sPHP%2$s Modules',
									'w3-total-cache'
								),
								'<acronym title="' . esc_attr__( 'Hypertext Preprocessor', 'w3-total-cache' ) . '">',
								'</acronym>'
							),
							array(
								'acronym' => array(
									'title' => array(),
								),
							)
						);
						?>
					</a>
				</div>
				<?php
				break;

			case 'w3tc_fragmentcache':
				?>
				<div id="w3tc-options-menu">
					<a href="#overview"><?php esc_html_e( 'Overview', 'w3-total-cache' ); ?></a> |
					<a href="#advanced"><?php esc_html_e( 'Advanced', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_monitoring':
				?>
				<div id="w3tc-options-menu">
					<a href="#application"><?php esc_html_e( 'Application', 'w3-total-cache' ); ?></a> |
					<a href="#dashboard"><?php esc_html_e( 'Dashboard', 'w3-total-cache' ); ?></a> |
					<a href="#behavior"><?php esc_html_e( 'Behavior', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_extension_page_imageservice':
				?>
				<div id="w3tc-options-menu">
					<a href="#configuration"><?php esc_html_e( 'Configuration', 'w3-total-cache' ); ?></a> |
					<a href="#tools"><?php esc_html_e( 'Tools', 'w3-total-cache' ); ?></a> |
					<a href="#statistics"><?php esc_html_e( 'Statistics', 'w3-total-cache' ); ?></a>
				</div>
				<?php
				break;

			case 'w3tc_extensions':
				$extension = Util_Admin::get_current_extension();
				switch ( $extension ) {
					case 'cloudflare':
						?>
						<div id="w3tc-options-menu">
							<a href="#credentials"><?php esc_html_e( 'Credentials', 'w3-total-cache' ); ?></a> |
							<a href="#general"><?php esc_html_e( 'General', 'w3-total-cache' ); ?></a>
						</div>
						<?php
						break;

					case 'amp':
						?>
						<div id="w3tc-options-menu">
							<!--<a href="#configuration"><?php esc_html_e( 'Configuration', 'w3-total-cache' ); ?></a>-->
						</div>
						<?php
						break;

					case 'swarmify':
						?>
						<div id="w3tc-options-menu">
							<a href="#configuration"><?php esc_html_e( 'Configuration', 'w3-total-cache' ); ?></a> |
							<a href="#behavior"><?php esc_html_e( 'Behavior Settings', 'w3-total-cache' ); ?></a>
						</div>
						<?php
						break;

					case 'genesis':
						?>
						<div id="w3tc-options-menu">
							<a href="#header"><?php esc_html_e( 'Header', 'w3-total-cache' ); ?></a> |
							<a href="#content"><?php esc_html_e( 'Content', 'w3-total-cache' ); ?></a> |
							<a href="#sidebar"><?php esc_html_e( 'Sidebar', 'w3-total-cache' ); ?></a> |
							<a href="#exclusions"><?php esc_html_e( 'Exclusions', 'w3-total-cache' ); ?></a>
						</div>
						<?php
						break;

					case 'alwayscached':
						?>
						<div id="w3tc-options-menu">
							<a href="#queue"><?php esc_html_e( 'Queue', 'w3-total-cache' ); ?></a> |
							<a href="#exclusions"><?php esc_html_e( 'Exclusions', 'w3-total-cache' ); ?></a> |
							<a href="#cron"><?php esc_html_e( 'Cron', 'w3-total-cache' ); ?></a> |
							<a href="#purge-all-behavior"><?php esc_html_e( 'Purge All Behavior', 'w3-total-cache' ); ?></a>
						</div>
						<?php
						break;
				}
			default:
				?>
				<div id="w3tc-options-menu"></div>
				<?php
				break;
		}
	}

	/**
	 * Gets the HTML markup for the Test Score Block.
	 *
	 * @param string $intro_label       Intro Label.
	 * @param string $score             Score Value.
	 * @param string $score_label       Score Label.
	 * @param string $score_description Score Description.
	 * @param string $score_link        Score Link.
	 *
	 * @return string
	 */
	public static function get_score_block( $intro_label, $score, $score_label, $score_description, $score_link ) {
		$score_block = '
			<div class="w3tc-test-container-intro">
				<span class="w3tc-test-score">' . $score . '</span><b>' . esc_html( $intro_label ) . '</b><span class="dashicons dashicons-arrow-down-alt2" ></span>
			</div>
			<div class="w3tc-test-container">
				<div class="w3tc-test-score-container">
					<div class="w3tc-test-score">' . $score . '</div>
					<p class="w3tc-test-score-label">' . $score_label . '</p>
				</div>
				<div class="w3tc-test-description">
					<p>' . $score_description . ' <a target="_blank" href="' . esc_url( $score_link ) . '">' . esc_html__( 'Review the testing results', 'w3-total-cache' ) . '</a>' . esc_html__( ' to see how.', 'w3-total-cache' ) . '</p>
					<br/>
					<p><input type="button" class="button-primary btn button-buy-plugin" data-src="test_score_upgrade" value="' . esc_attr__( 'Upgrade to', 'w3-total-cache' ) . ' W3 Total Cache Pro">' . esc_html__( ' and improve your PageSpeed Scores today!', 'w3-total-cache' ) . '</p>
				</div>
			</div>';

		return $score_block;
	}

	/**
	 * Prints the Google PageSpeed score block that is built into the config_item_xxx methods.
	 * This allows for manual printing in places that may need it.
	 *
	 * @param string $intro_label       Intro Label.
	 * @param string $score             Score Value.
	 * @param string $score_label       Score Label.
	 * @param string $score_description Score Description.
	 * @param string $score_link        Score Link.
	 *
	 * @return void
	 */
	public static function print_score_block( $intro_label, $score, $score_label, $score_description, $score_link ) {
		$score_block = self::get_score_block( $intro_label, $score, $score_label, $score_description, $score_link );
		echo wp_kses( $score_block, self::get_allowed_html_for_wp_kses_from_content( $score_block ) );
	}
}
