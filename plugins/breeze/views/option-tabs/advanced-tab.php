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


$options = breeze_get_option( 'advanced_settings', true );

$icon = BREEZE_PLUGIN_URL . 'assets/images/advanced-active.png';
?>
<form data-section="advanced">
	<?php if ( true === $is_custom ) { ?>
		<div class="br-overlay-disable"><?php _e( 'Settings are inherited', 'breeze' ); ?></div>
	<?php } ?>
	<section>
		<div class="br-section-title">
			<img src="<?php echo $icon; ?>"/>
			<?php _e( 'ADVANCED OPTIONS', 'breeze' ); ?>
		</div>

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Never Cache URL(s)', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<p>
					<?php _e( 'Specify URLs of Pages or posts that should never be cached (one per line)', 'breeze' ); ?>
				</p>
				<?php
				$excluded_url_list = true;

				if ( isset( $options['breeze-exclude-urls'] ) && ! empty( $options['breeze-exclude-urls'] ) ) {
					$excluded_url_list = breeze_validate_urls( $options['breeze-exclude-urls'] );
				}

				$never_cache_urls = '';
				if ( ! empty( $options['breeze-exclude-urls'] ) ) {
					$output           = implode( "\n", $options['breeze-exclude-urls'] );
					$never_cache_urls = esc_textarea( $output );
				}

				$placeholder_never_cache_url = 'Exclude Single URL:&#10;https://demo.com/example/&#10;&#10;Exclude Multiple URL using wildcard&#10;https://demo.com/example/(.*)';
				?>
				<textarea cols="100" rows="7" id="exclude-urls" name="exclude-urls"
						  placeholder="<?php echo esc_attr( $placeholder_never_cache_url ); ?>"><?php echo $never_cache_urls; ?></textarea>
				<div class="br-note">
					<p>
						<?php

						_e( 'Add the URLs of the pages (one per line) you wish to exclude from the WordPress internal cache. To exclude URLs from the Varnish cache, please refer to this ', 'breeze' );
						?>
						<a
								href="https://support.cloudways.com/en/articles/5126470-how-to-install-and-configure-breeze-wordpress-cache-plugin#h_4be3a0ff05"
								target="_blank"><?php _e( 'Knowledge Base', 'breeze' ); ?></a><?php _e( ' article.', 'breeze' ); ?>
					</p>
					<?php if ( false === $excluded_url_list ) { ?>
						<p class="br-notice">
							<?php _e( 'One (or more) URL is invalid. Please check and correct the entry.', 'breeze' ); ?>
						</p>
					<?php } ?>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Cache Query Strings', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$cached_query_strings = '';
				if ( isset( $options['cached-query-strings'] ) && ! empty( $options['cached-query-strings'] ) ) {
					$output               = implode( "\n", $options['cached-query-strings'] );
					$cached_query_strings = esc_textarea( $output );
				}

				$placeholder_cache_query_str = 'Include Single Query String:&#10;city&#10;&#10;Include Multiple Query Strings using wildcard&#10;city(.*)';
				?>
				<textarea cols="100" rows="7" id="cache-query-str" name="cache-query-str"
						  placeholder="<?php esc_attr_e( $placeholder_cache_query_str ); ?>"><?php echo $cached_query_strings; ?></textarea>
				<div class="br-note">
					<p>
						<?php
						_e( 'Pages that contain the query strings added here, will be cached. Each entry must be added in a new line. For further details please refer to this ', 'breeze' );
						?>
						<a
								href="https://support.cloudways.com/en/articles/5126470-how-to-install-and-configure-breeze-wordpress-cache-plugin#h_3f620cea69"
								target="_blank"><?php _e( 'Knowledge Base', 'breeze' ); ?></a><?php _e( ' article.', 'breeze' ); ?>
					</p>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Disable Emoji', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				if ( ! isset( $options['breeze-wp-emoji'] ) ) {
					$options['breeze-wp-emoji'] = '0';
				}

				$basic_value = filter_var( $options['breeze-wp-emoji'], FILTER_VALIDATE_BOOLEAN );
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-wp-emoji'], '1', false ) : '';
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="breeze-wpjs-emoji" name="breeze-wpjs-emoji" type="checkbox" class="br-box"
							   value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php

						_e( 'Disable the loading of emoji libraries and CSS', 'breeze' );
						?>
					</p>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Host Files Locally', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				if ( ! isset( $options['breeze-store-googlefonts-locally'] ) ) {
					$options['breeze-store-googlefonts-locally'] = '0';
				}

				$basic_value = filter_var( $options['breeze-store-googlefonts-locally'], FILTER_VALIDATE_BOOLEAN );
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-store-googlefonts-locally'], '1', false ) : '';
				?>
				<br>
				<strong> Google Fonts </strong>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="breeze-store-googlefonts-locally" name="breeze-store-googlefonts-locally"
							   type="checkbox" class="br-box"
							   value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<?php
				if ( ! isset( $options['breeze-store-googleanalytics-locally'] ) ) {
					$options['breeze-store-googleanalytics-locally'] = '0';
				}

				$basic_value = filter_var( $options['breeze-store-googleanalytics-locally'], FILTER_VALIDATE_BOOLEAN );
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-store-googleanalytics-locally'], '1', false ) : '';
				?>
				<br>
				<strong> Google Analytics </strong>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="breeze-store-googleanalytics-locally" name="breeze-store-googleanalytics-locally"
							   type="checkbox" class="br-box"
							   value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<?php
				if ( ! isset( $options['breeze-store-facebookpixel-locally'] ) ) {
					$options['breeze-store-facebookpixel-locally'] = '0';
				}

				$basic_value = filter_var( $options['breeze-store-facebookpixel-locally'], FILTER_VALIDATE_BOOLEAN );
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-store-facebookpixel-locally'], '1', false ) : '';
				?>
				<br>
				<strong> Facebook Pixel </strong>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="breeze-store-facebookpixel-locally" name="breeze-store-facebookpixel-locally"
							   type="checkbox" class="br-box"
							   value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<?php
				if ( ! isset( $options['breeze-store-gravatars-locally'] ) ) {
					$options['breeze-store-gravatars-locally'] = '0';
				}

				$basic_value = filter_var( $options['breeze-store-gravatars-locally'], FILTER_VALIDATE_BOOLEAN );
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-store-gravatars-locally'], '1', false ) : '';
				?>
				<br>
				<strong> Gravatars </strong>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="breeze-store-gravatars-locally" name="breeze-store-gravatars-locally" type="checkbox"
							   class="br-box"
							   value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>Optimize performance: Store Google Fonts, Google Analytics, Facebook Pixel, and Gravatar files at
						uploads/breeze/service-name/</p>
				</div>


			</div>
		</div>
		<!-- END OPTION -->
		<!-- START OPTION -->
		<div class="br-option-item br-top">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'API Integration', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$basic_value = isset( $options['breeze-enable-api'] ) ? filter_var( $options['breeze-enable-api'], FILTER_VALIDATE_BOOLEAN ) : false;
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-enable-api'], '1', false ) : '';

				$api_state = true;
				if ( empty( $is_enabled ) ) {
					$api_state = false;
				}

				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="breeze-enable-api" name="breeze-enable-api" type="checkbox" class="br-box"
							   value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php _e( 'Enable Breeze API endpoint to purge cache via Rest API OnDemand. The functionality can be used to integrate with your  workflows. i.e. when updating or adding new posts or products through the WordPress Rest API.', 'breeze' ); ?>
					</p>
				</div>

			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<?php
		$basic_value = isset( $options['breeze-secure-api'] ) ? filter_var( $options['breeze-secure-api'], FILTER_VALIDATE_BOOLEAN ) : false;
		$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-secure-api'], '1', false ) : '';

		$disable_group_css = '';
		$disable_overlay   = '';

		if ( false === $api_state ) {
			//$disable_group_css = 'disabled="disabled"';
			$disable_overlay = ' br-apply-disable';
		}
		?>
		<div class="br-option-item<?php echo $disable_overlay; ?>">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Authentication', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<!-- Checkbox -->
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="breeze-secure-api" name="breeze-secure-api" type="checkbox" class="br-box"
							   value="1" <?php echo $is_enabled; ?> <?php echo $disable_group_css; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>


				<div class="br-note">
					<p>
						<?php _e( 'Secure Breeze API Endpoint with authentication key, allowing the ability option to safeguard the purge endpoint from unauthorized access. Use the option below to autogenerate a random key.', 'breeze' ); ?>
					</p>
				</div>

				<?php

				$api_token_output = '';
				if ( ! empty( $options['breeze-api-token'] ) ) {
					$api_token_output = esc_attr( $options['breeze-api-token'] );
				}
				?>
				<div>
					<input type="text" id="breeze-api-token" name="breeze-api-token"
						   placeholder="5a1404a010241e0b79aecb67581009d5"
						   value="<?php echo $api_token_output; ?>"/>
					<a id="refresh-api-token" title="<?php esc_attr_e( 'Refresh Token', 'breeze' ); ?>">
						<span class="dashicons dashicons-update"></span>
					</a>
				</div>
				<div class="br-note">

				</div>


			</div>
		</div>
		<!-- END OPTION -->
	</section>
	<div class="br-submit">
		<input type="submit" value="<?php echo __( 'Save Changes', 'breeze' ); ?>" class="br-submit-save"/>
	</div>
</form>
