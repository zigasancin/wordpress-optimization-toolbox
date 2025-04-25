<?php
/**
 * Basic tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

set_as_network_screen();

$is_custom = false;
if ( ( ! defined( 'WP_NETWORK_ADMIN' ) || ( defined( 'WP_NETWORK_ADMIN' ) && false === WP_NETWORK_ADMIN ) ) && is_multisite() ) {
	$get_inherit = get_option( 'breeze_inherit_settings', '1' );
	$is_custom   = filter_var( $get_inherit, FILTER_VALIDATE_BOOLEAN );
}

$basic = breeze_get_option( 'basic_settings', true );


if ( ! isset( $basic['breeze-lazy-load'] ) ) {
	$basic['breeze-lazy-load'] = '0';
}

if ( ! isset( $basic['breeze-lazy-load-native'] ) ) {
	$basic['breeze-lazy-load-native'] = '0';
}

if ( ! isset( $basic['breeze-mobile-separate'] ) ) {
	$basic['breeze-mobile-separate'] = '1';
}

$icon = BREEZE_PLUGIN_URL . 'assets/images/basic-active.png';
?>
<form data-section="basic">
	<?php if ( true === $is_custom ) { ?>
		<div class="br-overlay-disable"><?php _e( 'Settings are inherited', 'breeze' ); ?></div>
	<?php } ?>
	<section>
		<div class="br-section-title">
			<img src="<?php echo $icon; ?>"/>
			<?php _e( 'BASIC OPTIONS', 'breeze' ); ?>
		</div>

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Cache System', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$basic_value = isset( $basic['breeze-active'] ) ? filter_var( $basic['breeze-active'], FILTER_VALIDATE_BOOLEAN ) : false;
				$check_basic = ( isset( $basic_value ) && true === $basic_value ) ? checked( $basic['breeze-active'], '1', false ) : '';
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="cache-system" name="cache-system" type="checkbox" class="br-box" value="1" <?php echo $check_basic; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php _e( 'This is the basic cache that we recommend should be kept enabled in all cases. Basic cache will build the internal and static caches for the WordPress websites.', 'breeze' ); ?>
					</p>
				</div>
			</div>
		</div>
		<!-- END OPTION -->
		<?php
		$mobile_cache_enabled = breeze_is_cloudways_server();

		$div_condition = '';
		$item_cursor =' ';
		if ( true === $mobile_cache_enabled ) {
			$div_condition = ' disabled="disabled"';
			$item_cursor =' breeze_disable_cursor';
		}
		?>
		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Mobile Cache', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$basic_value = isset( $basic['breeze-mobile-separate'] ) ? filter_var( $basic['breeze-mobile-separate'], FILTER_VALIDATE_BOOLEAN ) : false;
				$check_basic = ( isset( $basic_value ) && true === $basic_value ) ? checked( $basic['breeze-mobile-separate'], '1', false ) : '';
				if ( ! empty( $div_condition ) ) {
                    $mobile_cache_cw = is_breeze_mobile_cache(true);
                    if(true === $mobile_cache_cw){
	                    $check_basic = checked( '1', '1', false );
                    }else{
	                    $check_basic = checked( '0', '1', false );
                    }

				}
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher<?php esc_attr_e($item_cursor); ?>">
						<input id="breeze-mobile-separate" type="checkbox" name="breeze-mobile-separate" class="br-box" <?php echo $check_basic; ?> value='1' <?php echo $div_condition; ?>/>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>
				<div class="br-note">
					<p>
						<?php _e( 'Modern themes are built to be responsive and they usually function optimally without the need for an additional cache. Only activate mobile caching if you happen to be using a dedicated mobile theme or plugin.', 'breeze' ); ?>
					</p>
				</div>
				<?php
				if ( ! empty( $div_condition ) ) {
					echo '<p class="br-important">';
					echo '<strong>';
					_e( 'Important: ', 'breeze' );
					echo '</strong>';

					$kb_mobile_cache = 'https://support.cloudways.com/en/articles/8460042-how-to-use-device-detection-with-your-application#h_78ed161271';
					echo sprintf(
					/* translators: %s Export file location */
						__( 'To use mobile caching with the Breeze plugin on Cloudways, you must enable the Device Detection feature through the Cloudways Platform. Please follow this <a href="%s" target="_blank">guide</a> to ensure the mobile cache functions properly.', 'breeze' ),
						$kb_mobile_cache
					);
					echo '</p>';
				}
				?>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Purge Cache After', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$cache_ttl = ( isset( $basic['breeze-b-ttl'] ) && ! empty( $basic['breeze-b-ttl'] ) ? (int) $basic['breeze-b-ttl'] : '1440' );
				?>
				<input type="text" id="cache-ttl" name="cache-ttl" size="50" placeholder="<?php _e( '1440', 'breeze' ); ?>" value="<?php echo $cache_ttl; ?>"/>
				<div class="br-note">
					<p>
						<?php _e( 'Automatically purge internal cache after X minutes. By default this is set to 1440 minutes (1 day)', 'breeze' ); ?>
					</p>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<?php
		$supports_conditionals = breeze_is_supported( 'conditional_htaccess' );

		$is_disabled = is_multisite() && ! is_network_admin() && ! $supports_conditionals;
		$basic_value = isset( $basic['breeze-gzip-compression'] ) ? filter_var( $basic['breeze-gzip-compression'], FILTER_VALIDATE_BOOLEAN ) : false;
		$is_checked  = isset( $basic['breeze-gzip-compression'] ) && true === $basic_value && ! $is_disabled;

		$disable_overlay = '';
		if ( $is_disabled ) {
			$disable_overlay = ' br-apply-disable';
		}
		?>
		<div class="br-option-item<?php echo $disable_overlay; ?>">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Gzip Compression', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">

				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="gzip-compression" type="checkbox" name="gzip-compression" class="br-box" value="1"
							<?php echo $is_disabled ? 'disabled="disabled"' : ''; ?> <?php checked( $is_checked, true ); ?>/>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php _e( 'Enable this to compress your files making HTTP requests fewer and faster.', 'breeze' ); ?>
					</p>
					<?php
					if ( $is_disabled ) {
						echo '<p class="br-notice">';
						printf( esc_html__( 'Enabling/disabling %s for subsites is only available for Apache 2.4 and above. For lower versions, the Network-level settings will apply.', 'breeze' ), 'Gzip Compression' );
						echo '</p>';
					}
					?>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<?php
		$supports_conditionals = breeze_is_supported( 'conditional_htaccess' );

		$is_disabled = is_multisite() && ! is_network_admin() && ! $supports_conditionals;
		$basic_value = isset( $basic['breeze-browser-cache'] ) ? filter_var( $basic['breeze-browser-cache'], FILTER_VALIDATE_BOOLEAN ) : false;
		$is_checked  = isset( $basic['breeze-browser-cache'] ) && true === $basic_value && ! $is_disabled;

		$disable_overlay = '';
		if ( $is_disabled ) {
			$disable_overlay = ' br-apply-disable';
		}
		?>
		<div class="br-option-item<?php echo $disable_overlay; ?>">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Browser Cache', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">

				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="browser-cache" type="checkbox" name="browser-cache" class="br-box" value="1"
							<?php echo $is_disabled ? 'disabled="disabled"' : ''; ?> <?php checked( $is_checked, true ); ?>/>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php _e( 'Enable this to add expires headers to static files. This will ask browsers to either request a file from server or fetch from the browser’s cache.', 'breeze' ); ?>
					</p>
					<?php
					if ( $is_disabled ) {
						echo '<p class="br-notice">';
						printf( esc_html__( 'Enabling/disabling %s for subsites is only available for Apache 2.4 and above. For lower versions, the Network-level settings will apply.', 'breeze' ), 'Browser Cache' );
						echo '</p>';
					}
					?>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Lazy Load Images', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$disabled = 'disabled';

				if ( class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {
					$disabled = '';
				}

				$basic_value = isset( $basic['breeze-lazy-load'] ) ? filter_var( $basic['breeze-lazy-load'], FILTER_VALIDATE_BOOLEAN ) : false;
				$check_basic = ( isset( $basic_value ) && true === $basic_value ) ? checked( $basic['breeze-lazy-load'], '1', false ) : '';
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="bz-lazy-load" type="checkbox" name="bz-lazy-load" class="br-box" value='1' <?php echo $disabled; ?> <?php echo $check_basic; ?>/>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>


				<div class="br-note">
					<p><?php _e( 'Images will begin to load before being displayed on screen.', 'breeze' ); ?></p>
					<?php
					if ( ! empty( $disabled ) ) {
						echo '<p class="br-notice">';
						_e( 'This option requires the library PHP DOMDocument and PHP DOMXPath', 'breeze' );
						echo '</p>';
					}
					?>
				</div>
				<?php
				//$basic['breeze-lazy-load-native'] = '0';
				//$basic['breeze-lazy-load'] = '0';

				$is_checked_lazy = checked( $basic['breeze-lazy-load'], '1', false );
				if ( ! empty( $is_checked_lazy ) ) {
					if ( ! empty( $disabled ) ) {
						$hide = ' style="display:none"';
					} else {
						$hide = '';
					}
				} else {
					$hide = ' style="display:none"';
				}
				$basic_value      = isset( $basic['breeze-lazy-load-native'] ) ? filter_var( $basic['breeze-lazy-load-native'], FILTER_VALIDATE_BOOLEAN ) : false;
				$native_lazy_load = ( isset( $basic_value ) && true === $basic_value ) ? checked( $basic['breeze-lazy-load-native'], '1', false ) : '';

                // Lazy load iframe
				$basic_value      = isset( $basic['breeze-lazy-load-iframes'] ) ? filter_var( $basic['breeze-lazy-load-iframes'], FILTER_VALIDATE_BOOLEAN ) : false;
				$iframe_lazy_load = ( isset( $basic_value ) && true === $basic_value ) ? checked( $basic['breeze-lazy-load-iframes'], '1', false ) : '';

                // Lazy load videos
				$basic_value      = isset( $basic['breeze-lazy-load-videos'] ) ? filter_var( $basic['breeze-lazy-load-videos'], FILTER_VALIDATE_BOOLEAN ) : false;
				$videos_lazy_load = ( isset( $basic_value ) && true === $basic_value ) ? checked( $basic['breeze-lazy-load-videos'], '1', false ) : '';
				?>

				<span <?php echo $hide; ?> id="native-lazy-option-iframe">

					<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="bz-lazy-load-iframe" type="checkbox" name="bz-lazy-load-iframe" class="br-box" value='1' <?php echo $iframe_lazy_load; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
					</div>

						<p>
					<?php _e( 'iFrame lazy load', 'breeze' ); ?><br/>
					</p>
					<p class="br-important">
						<?php
						echo '<strong>';
						_e( 'Important: ', 'breeze' );
						echo '</strong>';
						_e( 'Apply lazy load to all iframe tags.', 'breeze' );
						?>
					</p>
				</span>

                <span <?php echo $hide; ?> id="native-lazy-option-videos">

					<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="bz-lazy-load-videos" type="checkbox" name="bz-lazy-load-videos" class="br-box" value='1' <?php echo $videos_lazy_load; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
					</div>

						<p>
					<?php _e( 'Video lazy load', 'breeze' ); ?><br/>
					</p>
					<p class="br-important">
						<?php
						echo '<strong>';
						_e( 'Important: ', 'breeze' );
						echo '</strong>';
						_e( 'Apply lazy load to all videos tags.', 'breeze' );
						?>
					</p>
				</span>

				<span <?php echo $hide; ?> id="native-lazy-option">
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="bz-lazy-load-nat" type="checkbox" name="bz-lazy-load-nat" class="br-box" value='1' <?php echo $native_lazy_load; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>


						<p>
					<?php _e( 'Enable native browser lazy load', 'breeze' ); ?><br/>
					</p>
					<p class="br-important">
						<?php
						echo '<strong>';
						_e( 'Important: ', 'breeze' );
						echo '</strong>';
						_e( 'This is not supported by all browsers.', 'breeze' );
						?>
					</p>
				</span>


			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Cross-origin Safe Links', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$basic_value = isset( $basic['breeze-cross-origin'] ) ? filter_var( $basic['breeze-cross-origin'], FILTER_VALIDATE_BOOLEAN ) : false;
				$check_basic = ( isset( $basic_value ) && true === $basic_value ) ? checked( $basic['breeze-cross-origin'], '1', false ) : '';
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="safe-cross-origin" type="checkbox" name="safe-cross-origin" class="br-box" <?php echo $check_basic; ?> value='1'/>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p><?php _e( 'Apply “noopener noreferrer” to links which have target”_blank” attribute and the anchor leads to external websites', 'breeze' ); ?></p>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Cache Logged-in Users', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				global $wp_roles;
				$roles = $wp_roles->roles;

				foreach ( $roles as $user_role => $user_role_data ) {
					$is_checked_role = 0;
					if (  isset( $basic['breeze-disable-admin'] ) &&  is_array( $basic['breeze-disable-admin'] ) && isset( $basic['breeze-disable-admin'][ $user_role ] ) ) {
						$is_checked_role = (int) $basic['breeze-disable-admin'][ $user_role ];
					}

					$check_role = ( isset( $is_checked_role ) && 1 === $is_checked_role ) ? checked( $is_checked_role, '1', false ) : '';

					?>
					<strong><?php echo esc_html( $user_role_data['name'] ); ?></strong>
					<div class="on-off-checkbox">
						<label class="br-switcher">
							<input id="breeze-admin-cache-<?php echo esc_attr( $user_role ); ?>" type="checkbox" name="breeze-admin-cache[<?php echo esc_attr( $user_role ); ?>]" class="br-box"
								   value="1" <?php echo $check_role; ?>>
							<div class="br-see-state">
							</div>
						</label><br>
					</div>


					<br/>
					<?php
				}
				?>
				<div class="br-note">
					<p>
						<?php

						_e( 'Enable cache for WP standard user roles: Administrator, Editor, Author, Contributor.', 'breeze' );
						?>
					</p>
					<p class="br-important">
						<?php
						echo '<strong>';
						_e( 'Important: ', 'breeze' );
						echo '</strong>';
						_e( 'This option might not work properly with some page builders.', 'breeze' );
						?>
					</p>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

	</section>
	<div class="br-submit">
		<input type="submit" value="<?php echo __( 'Save Changes', 'breeze' ); ?>" class="br-submit-save"/>
	</div>
</form>
