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


$options                   = breeze_get_option( 'cdn_integration', true );
$cdn_content_value         = '';
$cdn_exclude_content_value = '';
$icon                      = BREEZE_PLUGIN_URL . 'assets/images/cdn-active.png';

?>
<form data-section="cdn">
	<?php if ( true === $is_custom ) { ?>
		<div class="br-overlay-disable"><?php _e( 'Settings are inherited', 'breeze' ); ?></div>
	<?php } ?>
	<section>
		<div class="br-section-title">
			<img src="<?php echo $icon; ?>"/>
			<?php _e( 'CDN', 'breeze' ); ?>
		</div>

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Activate CDN', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				if ( ! isset( $options['cdn-active'] ) ) {
					$options['cdn-active'] = '0';
				}

				$basic_value = filter_var( $options['cdn-active'], FILTER_VALIDATE_BOOLEAN );
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['cdn-active'], '1', false ) : '';
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="activate-cdn" name="activate-cdn" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php _e( 'Enable to make CDN effective on your website.', 'breeze' ); ?>
					</p>
				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'CDN CNAME', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php

				$cdn_url            = ( isset( $options['cdn-url'] ) ) ? esc_html( $options['cdn-url'] ) : '';
				$cdn_url_validation = breeze_validate_url_via_regexp( $cdn_url );

				$error_message_cdn = '';
				if ( ! empty( $cdn_url ) && true === $cdn_url_validation ) {
					$is_warning = breeze_static_check_cdn_url( $cdn_url );
					if ( 'warning' === $is_warning ) {
						$error_message_cdn = '<strong>' . __( 'Important: ', 'breeze' ) . '</strong>';
						$error_message_cdn .= __( 'The CDN URL you\'ve used is insecure.', 'breeze' );
					}

				}

                $display_error_cdn = 'none';
                if(!empty($error_message_cdn)){
	                $display_error_cdn = 'block';
                }

				?>
				<input type="text" id="cdn-url" name="cdn-url" size="50" placeholder="<?php _e( 'https://www.domain.com', 'breeze' ); ?>" value="<?php echo $cdn_url; ?>"/>
				<div class="br-note">
					<p>
						<?php

						_e( 'Use double slash ‘//’ at the start of CDN CNAME, if you have some pages on  HTTP and some are on HTTPS.', 'breeze' );
						?>
                    </p>
                    <p class="br-important" id="cdn-message-error" style="display:<?php echo $display_error_cdn; ?>; margin-top: 20px;">
                        <?php echo $error_message_cdn;?>
					</p>
					<?php if ( false === $cdn_url_validation && ! empty( $cdn_url ) ) { ?>
						<p class="br-notice">
							<?php
							echo $cdn_url . ' ';
							echo esc_html__( 'is not a valid CDN url.', 'breeze' );
							?>
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
					<?php _e( 'CDN Content', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				if ( ! empty( $options['cdn-content'] ) ) {
					$cdn_content_value = implode( ',', $options['cdn-content'] );
				}

				?>
				<input type="text" id="cdn-content" name="cdn-content" size="50" value="<?php echo( ( $cdn_content_value ) ? esc_html( $cdn_content_value ) : '' ); ?>"/>
				<div class="br-note">
					<p>
						<?php

						_e( 'Enter the directories (comma separated) of which you want the CDN to serve the content.', 'breeze' );
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
					<?php _e( 'Exclude Content', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				if ( ! empty( $options['cdn-exclude-content'] ) ) {
					$cdn_exclude_content_value = implode( ',', $options['cdn-exclude-content'] );
				}

				?>
				<input type="text" id="cdn-exclude-content" name="cdn-exclude-content" size="50"
					   value="<?php echo( ( $cdn_exclude_content_value ) ? esc_html( $cdn_exclude_content_value ) : '' ); ?>"/>
				<div class="br-note">
					<p>
						<?php

						_e( 'Exclude file types or directories from CDN. Example, enter .css to exclude the CSS files.', 'breeze' );
						?>

				</div>
			</div>
		</div>
		<!-- END OPTION -->

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Relative Path', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				if ( ! isset( $options['cdn-relative-path'] ) ) {
					$options['cdn-relative-path'] = '0';
				}

				$basic_value = filter_var( $options['cdn-relative-path'], FILTER_VALIDATE_BOOLEAN );
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['cdn-relative-path'], '1', false ) : '';
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="cdn-relative-path" name="cdn-relative-path" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php

						_e( 'Keep this option enabled. Use this option to enable relative path for your CDN on your WordPress site.', 'breeze' );
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

