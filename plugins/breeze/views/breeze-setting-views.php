<?php

defined( 'ABSPATH' ) or die;

// Thickbox for popups
add_thickbox();

$global_tabs = array(
	'faq',
);

$breeze_option_tabs = array(
	array(
		'tab-slug' => 'basic',
		'tab-name' => __( 'BASIC OPTIONS', 'breeze' ),
		'tab-icon' => 'basic',
	),
	array(
		'tab-slug' => 'file',
		'tab-name' => __( 'FILE OPTIMIZATION', 'breeze' ),
		'tab-icon' => 'file',
	),
	array(
		'tab-slug' => 'preload',
		'tab-name' => __( 'PRELOAD', 'breeze' ),
		'tab-icon' => 'preload',
	),
	array(
		'tab-slug' => 'advanced',
		'tab-name' => __( 'ADVANCED OPTIONS', 'breeze' ),
		'tab-icon' => 'advanced',
	),
	array(
		'tab-slug' => 'heartbeat',
		'tab-name' => __( 'HEARTBEAT API', 'breeze' ),
		'tab-icon' => 'heartbeat',
	),
	array(
		'tab-slug' => 'database',
		'tab-name' => __( 'DATABASE OPTIONS', 'breeze' ),
		'tab-icon' => 'database',
	),
	//	array(
	//      'tab-slug' => 'varnish',
	//      'tab-name' => __( 'VARNISH', 'breeze' ),
	//      'tab-icon' => 'varnish',
	//  ),
	array(
		'tab-slug' => 'cdn',
		'tab-name' => __( 'CDN', 'breeze' ),
		'tab-icon' => 'cdn',
	),
	array(
		'tab-slug' => 'varnish',
		'tab-name' => __( 'VARNISH', 'breeze' ),
		'tab-icon' => 'varnish',
	),
	array(
		'tab-slug' => 'tools',
		'tab-name' => __( 'TOOLS', 'breeze' ),
		'tab-icon' => 'tools',
	),
	array(
		'tab-slug' => 'faq',
		'tab-name' => __( 'FAQs', 'breeze' ),
		'tab-icon' => 'faqs',
	),
);

$section_icons = array();
$section_title = array();

$show_tabs             = true;
$is_subsite            = is_multisite() && get_current_screen()->base !== 'settings_page_breeze-network';
$logo_subsite          = '';
$is_inherited_settings = false;
if ( $is_subsite ) {
	$logo_subsite = ' subsite-logo';
	// Show settings inherit option.
	$inherit_settings = get_option( 'breeze_inherit_settings', '0' );

	$is_inherited_settings = isset( $inherit_settings ) ? filter_var( $inherit_settings, FILTER_VALIDATE_BOOLEAN ) : false;
	$check_inherit_setting = ( isset( $is_inherited_settings ) && false === $is_inherited_settings ) ? checked( $inherit_settings, '0', false ) : '';

	if ( true === $is_inherited_settings ) {
		$show_tabs = false;
	}
}
?>

<div class="wrap breeze-box">
	<div class="br-menu">
		<div class="br-logo<?php echo esc_attr( $logo_subsite ); ?>">&nbsp;</div>
		<div class="br-mobile-menu">
			<span class="dashicons dashicons-menu"></span>
			<?php _e( 'Open menu', 'breeze' ); ?>
		</div>
		<?php
		$active_selected_tab = 'basic';
		if ( isset( $_COOKIE['breeze_active_tab'] ) && ! empty( $_COOKIE['breeze_active_tab'] ) ) {
			$active_selected_tab = trim( $_COOKIE['breeze_active_tab'] );
		}

		foreach ( $breeze_option_tabs as $item ) {
			$active_css = '';
			$icon       = BREEZE_PLUGIN_URL . 'assets/images/' . esc_attr( $item['tab-icon'] . '.png' );
			if ( $active_selected_tab === $item['tab-slug'] ) {
				$active_css = ' br-active';
				$icon       = BREEZE_PLUGIN_URL . 'assets/images/' . esc_attr( $item['tab-icon'] . '-active.png' );
			}

			$section_icons[ $item['tab-slug'] ] = BREEZE_PLUGIN_URL . 'assets/images/' . esc_attr( $item['tab-icon'] . '.png' );
			$section_title[ $item['tab-slug'] ] = esc_html( $item['tab-name'] );

			$hide_item = '';
			if ( true === $is_inherited_settings && 'faq' !== $item['tab-slug'] ) {
				$hide_item = ' br-hide';
			}
			?>
			<div class="br-link
			<?php
			echo esc_attr( $active_css );
			echo esc_attr( $hide_item );
			?>
			" data-breeze-link="<?php echo esc_attr( $item['tab-slug'] ); ?>">

				<?php
				$key         = $item['tab-slug'];
				$is_inactive = false;
				$name        = mb_strtoupper( esc_html( $item['tab-name'] ) );
				echo '<a id="tab-' . $key . '" class="' . ( $is_inactive ? ' inactive' : '' ) . '" href="#tab-' . $key . '" data-tab-id="' . $key . '">
				<img src="' . $icon . '" data-path="' . BREEZE_PLUGIN_URL . 'assets/images/' . '"/> ' . $name . ' 
				</a> ';
				?>
			</div>
			<?php
		}
		?>

	</div>
	<?php

	if ( true === $is_subsite ) {
		// Show settings inherit option.
		$inherit_settings = get_option( 'breeze_inherit_settings', '0' );

		$is_inherited_settings = isset( $inherit_settings ) ? filter_var( $inherit_settings, FILTER_VALIDATE_BOOLEAN ) : false;
		$check_inherit_setting = ( isset( $is_inherited_settings ) && false === $is_inherited_settings ) ? checked( $inherit_settings, '0', false ) : '';

		if ( true === $is_inherited_settings ) {
			$show_tabs = false;
		}

		$display_text = array(
			'network' => ( true === $is_inherited_settings ) ? 'br-show' : 'br-hide',
			'custom'  => ( false === $is_inherited_settings ) ? 'br-show' : 'br-hide',
		);
		?>
		<div class="br-container">
			<div class="br-network-only change-settings-use">
				<div>
					<p class="br-global-text-settings">
						<span class="br-important br-is-network <?php echo esc_attr( $display_text['network'] ); ?>">
							<strong><?php _e( 'Network Settings', 'breeze' ); ?></strong>:<br/>
							 <?php esc_html_e( 'This option allows the subsite to inherit all the cache settings from network. To modify/update the settings please go to network site.', 'breeze' ); ?>
						</span>

						<span class="br-is-custom <?php echo esc_attr( $display_text['custom'] ); ?>">
							<strong><?php _e( 'Use Custom Settings', 'breeze' ); ?></strong>:<br/>
							 <?php esc_html_e( 'This option allows subsite to have different settings/configuration from the network level. Use this option only if you wish to have separate settings for this subsite.', 'breeze' ); ?>
						</span>
					</p>
					<?php wp_nonce_field( 'breeze_inherit_settings', 'breeze_inherit_settings_nonce' ); ?>
				</div>
				<div class="br-option-net">
					<div class="br-radio">
						<input type="radio" id="inherit-settings-1" name="inherit-settings" value="1" <?php echo ( true === $is_inherited_settings ) ? 'checked' : ''; ?>/>
						<label for="inherit-settings-1" class="radio-label"><?php _e( 'Inherit Network Settings', 'breeze' ); ?></label>
					</div>

					<div class="br-radio">
						<input type="radio" id="inherit-settings-2" name="inherit-settings" value="0" <?php echo ( false === $is_inherited_settings ) ? 'checked' : ''; ?> style="margin-left: 25px;"/>
						<label for="inherit-settings-2" class="radio-label"><?php _e( 'Use Custom Settings', 'breeze' ); ?></label>
					</div>

				</div>
			</div>
			<div class="br-options"></div>
		</div>
	<?php } else { ?>
		<div class="br-options"></div>
	<?php } ?>
</div>
