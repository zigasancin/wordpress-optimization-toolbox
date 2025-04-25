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


$options       = breeze_get_option( 'varnish_cache', true );
$check_varnish = is_varnish_cache_started();

$icon = BREEZE_PLUGIN_URL . 'assets/images/varnish-active.png';

?>
<form data-section="varnish">
	<?php if ( true === $is_custom ) { ?>
		<div class="br-overlay-disable"><?php _e( 'Settings are inherited', 'breeze' ); ?></div>
	<?php } ?>
	<section>
		<div class="br-section-title">
			<img src="<?php echo $icon; ?>"/>
			<?php _e( 'Varnish', 'breeze' ); ?>
			<p class="br-subtitle">
				<?php _e( 'By default Varnish is enabled on all WordPress websites hosted on Cloudways.', 'breeze' ); ?>
			</p>
		</div>

		<!-- START OPTION -->
		<div class="br-option-item">
			<div class="br-label">
				<div class="br-option-text">
					<?php _e( 'Auto Purge Varnish', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				if ( ! isset( $options['auto-purge-varnish'] ) ) {
					$options['auto-purge-varnish'] = '0';
				}

				$basic_value = filter_var( $options['auto-purge-varnish'], FILTER_VALIDATE_BOOLEAN );
				$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['auto-purge-varnish'], '1', false ) : '';
				?>
				<div class="on-off-checkbox">
					<label class="br-switcher">
						<input id="auto-purge-varnish" name="auto-purge-varnish" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
						<div class="br-see-state">
						</div>
					</label><br>
				</div>

				<div class="br-note">
					<p>
						<?php _e( 'Keep this option enabled to automatically purge Varnish cache on actions like publishing new blog posts, pages and comments.', 'breeze' ); ?>
					</p>
					<?php
					if ( ! $check_varnish ) {
						echo '<p class="br-notice">';
						//                      echo '<strong>';
						//                      _e( 'Note: ', 'breeze' );
						//                      echo '</strong>';
						_e( 'Seems Varnish is disabled on your Application. Please refer to this', 'breeze' );
						?>
						<a href="https://support.cloudways.com/most-common-varnish-issues-and-queries/"
						   target="_blank"><?php _e( 'Knowledge Base', 'breeze' ); ?></a><?php _e( ' article and learn how to enable it.', 'breeze' ); ?> </span>
						<?php
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
					<?php _e( 'Varnish Server', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<?php
				$varnish_ip = ( ! empty( $options['breeze-varnish-server-ip'] ) ? esc_html( $options['breeze-varnish-server-ip'] ) : '127.0.0.1' );

				?>
				<input type="text" id="varnish-server-ip" name="varnish-server-ip" size="50" placeholder="<?php _e( '127.0.0.1', 'breeze' ); ?>" value="<?php echo $varnish_ip; ?>"/>
				<div class="br-note">
					<p> 
					<?php
						_e( 'Keep this default if you are a Cloudways customer. Otherwise ask your hosting provider on what to set here.', 'breeze' );
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
					<?php _e( 'Purge Varnish Cache', 'breeze' ); ?>
				</div>
			</div>
			<div class="br-option">
				<a href="#" id="purge-varnish-button" class="br-blue-button-reverse"><?php _e( 'Purge', 'breeze' ); ?></a>

				<div class="br-note">
					<p>
						<?php

						_e( 'Use this option to instantly Purge Varnish Cache on entire website.', 'breeze' );
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

