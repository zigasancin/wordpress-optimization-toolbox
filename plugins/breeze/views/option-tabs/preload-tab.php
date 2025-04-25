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

$options = breeze_get_option( 'preload_settings', true );

$icon = BREEZE_PLUGIN_URL . 'assets/images/preload-active.png';
?>
<form data-section="preload">
	<?php if ( true === $is_custom ) { ?>
		<div class="br-overlay-disable"><?php _e( 'Settings are inherited', 'breeze' ); ?></div>
	<?php } ?>
	<section>
		<div class="br-section-title">
			<img src="<?php echo $icon; ?>"/>
			<?php _e( 'PRELOAD', 'breeze' ); ?>
		</div>

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Preload Webfont', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<div class="breeze-list-url">
					<?php if ( ! empty( $options['breeze-preload-fonts'] ) ) : ?>
						<?php foreach ( $options['breeze-preload-fonts'] as $font_url ) : ?>
							<div class="breeze-input-group">
								<input type="text" size="98"
									   class="breeze-input-url"
									   name="breeze-preload-font[]"
									   placeholder="<?php _e( 'Enter Font/CSS URL...', 'breeze' ); ?>"
									   value="<?php echo esc_html( $font_url ); ?>"/>
								<span class="sort-handle">
										<span class="dashicons dashicons-arrow-up moveUp"></span>
										<span class="dashicons dashicons-arrow-down moveDown"></span>
									</span>
								<span class="dashicons dashicons-no item-remove" title="<?php _e( 'Remove', 'breeze' ); ?>"></span>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="breeze-input-group">
							<input type="text" size="98"
								   class="breeze-input-url"
								   id="breeze-preload-font"
								   name="breeze-preload-font[]"
								   placeholder="<?php _e( 'Enter Font/CSS URL...', 'breeze' ); ?>"
								   value=""/>
							<span class="sort-handle">
									<span class="dashicons dashicons-arrow-up moveUp"></span>
									<span class="dashicons dashicons-arrow-down moveDown"></span>
								</span>
							<span class="dashicons dashicons-no item-remove" title="<?php _e( 'Remove', 'breeze' ); ?>"></span>
						</div>
					<?php endif; ?>
				</div>
				<div style="margin: 10px 0">
					<button type="button" class="br-blue-button-reverse add-url" id="add-breeze-preload-fonts">
						<?php _e( 'Add URL', 'breeze' ); ?>
					</button>
				</div>
				<div class="br-note">
					<p>
						<?php

						_e( 'Specify the local font URL or the URL for the CSS file which loads only fonts. Load WOFF formats fonts for the best performance. Do not preload the whole website CSS file as it will slow down your website. Do not add Google Fonts links as those already use preload.', 'breeze' );
						?>
						<br/>
						<?php
						$theme_url = get_template_directory_uri() . '/assets/fonts/my-font.woff';
						echo '<strong>';
						_e( 'Example:', 'breeze' );
						echo '</strong>';
						echo ' ' . esc_url( $theme_url );
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
					<?php _e( 'Preload Links', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$basic_value = isset( $options['breeze-preload-links'] ) ? filter_var( $options['breeze-preload-links'], FILTER_VALIDATE_BOOLEAN ) : false;
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-preload-links'], '1', false ) : '';
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="preload-links" name="preload-links" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php

						_e( 'When users hover over links, the cache is created in advance. The page will load faster upon link visiting.', 'breeze' );
						?>
					</p>
					<p class="br-important">
						<?php
						echo '<strong>';
						_e( 'Important: ', 'breeze' );
						echo '</strong>';
						_e( 'This feature is supported by Chromium based browsers (Chrome, Opera, Microsoft Edge Chromimum, Brave...);', 'breeze' );
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
					<?php _e( 'Prefetch of DNS Request', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<p>
					<?php _e( 'Specify domain URLs, one per line', 'breeze' ); ?>
				</p>
				<?php
				$prefetch_domain_list = true;

				if ( isset( $options['breeze-prefetch-urls'] ) && ! empty( $options['breeze-prefetch-urls'] ) ) {
					$prefetch_domain_list = breeze_validate_urls( $options['breeze-prefetch-urls'] );
				}


				$domains_output = '';
				if ( ! empty( $options['breeze-prefetch-urls'] ) ) {
					$output         = implode( "\n", $options['breeze-prefetch-urls'] );
					$domains_output = esc_textarea( $output );
				}

				$placeholder_never_cache_url = '//demo.com';
				?>
				<textarea cols="100" rows="7" id="br-prefetch-urls" name="br-prefetch-urls" placeholder="<?php echo esc_attr( $placeholder_never_cache_url ); ?>"><?php echo $domains_output; ?></textarea>
				<div class="br-note">
					<p>
						<?php

						_e( 'Third-party content load will be optimized if we prefetch the domains.', 'breeze' );
						?>
					</p>
					<?php if ( false === $prefetch_domain_list ) { ?>
						<p class="br-notice">
							<?php _e( 'One (or more) URL is invalid. Please check and correct the entry.', 'breeze' ); ?>
						</p>
					<?php } ?>
				</div>
			</div>
		</div>
		<!-- END OPTION -->
	</section>
	<div class="br-submit">
		<input type="submit" value="<?php echo __( 'Save Changes', 'breeze' ); ?>" class="br-submit-save"/>
	</div>
</form>
