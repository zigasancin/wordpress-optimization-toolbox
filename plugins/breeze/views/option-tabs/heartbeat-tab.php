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


$options = breeze_get_option( 'heartbeat_settings', true );

$icon = BREEZE_PLUGIN_URL . 'assets/images/heartbeat-active.png';
?>
<form data-section="heartbeat">
	<?php if ( true === $is_custom ) { ?>
		<div class="br-overlay-disable"><?php _e( 'Settings are inherited', 'breeze' ); ?></div>
	<?php } ?>
	<section>
		<div class="br-section-title">
			<img src="<?php echo $icon; ?>"/>
			<?php _e( 'HEARTBEAT API', 'breeze' ); ?>
		</div>

		<div class="br-option-group">
			<span class="section-title"><?php _e( 'Heartbeat Control', 'breeze' ); ?></span>
			<!-- START OPTION -->
			<div class="br-option-item br-top">
				<div class="br-label">
					<div class="br-option-text">
						<?php _e( 'Control Heartbeat', 'breeze' ); ?>
					</div>
				</div>
				<div class="br-option">
					<?php
					$basic_value = isset( $options['breeze-control-heartbeat'] ) ? filter_var( $options['breeze-control-heartbeat'], FILTER_VALIDATE_BOOLEAN ) : false;
					$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-control-heartbeat'], '1', false ) : '';
					?>

					<div class="on-off-checkbox">
						<label class="br-switcher">
							<input id="breeze-control-hb" name="breeze-control-hb" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
							<div class="br-see-state">
							</div>
						</label><br>
					</div>

					<div class="br-note">
						<p>
							<?php
							_e( 'Can help save some of the server resources when it\'s controlled.', 'breeze' );
							?>
						</p>
					</div>
				</div>
			</div>
			<!-- END OPTION -->

			<?php
			$heartbeat_options = array(
				'default' => __( 'Default', 'breeze' ),
				'30'      => __( 'Every 30 seconds', 'breeze' ),
				'60'      => __( 'Every 60 seconds', 'breeze' ),
				'90'      => __( 'Every 90 seconds', 'breeze' ),
				'120'     => __( 'Every 120 seconds', 'breeze' ),
				'disable' => __( 'Disable', 'breeze' ),
			);

			?>
			<!-- START OPTION -->
			<div class="br-option-item">
				<div class="br-label">
					<div class="br-option-text">
						<?php _e( 'Heartbeat Front-end', 'breeze' ); ?>
					</div>
				</div>
				<div class="br-option">
					<?php
					$select_active = isset( $options['breeze-heartbeat-front'] ) ? $options['breeze-heartbeat-front'] : '';
					?>
					<select name="br-heartbeat-front" id="br-heartbeat-front">
						<?php
						foreach ( $heartbeat_options as $value => $label ) {
							$selected = '';
							if ( $select_active === (string) $value || empty( $select_active ) ) {
								$selected = 'selected';
							}
							echo "<option value='{$value}' {$selected}>{$label}</option>";
						}
						?>
					</select>
					<div class="br-note">
						<p>
							<?php
							_e( 'Change front-end Heartbeat behaviour or disable it completely', 'breeze' );
							?>
						</p>
						<p class="br-important">
							<?php
							echo '<strong>';
							_e( 'Important: ', 'breeze' );
							echo '</strong>';
							_e( 'Disabling Heartbeat may break some functionalities from plugins and themes which are dependent on Heartbeat API.', 'breeze' );
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
						<?php _e( 'Heartbeat Post Editor', 'breeze' ); ?>
					</div>
				</div>
				<div class="br-option">
					<?php
					$select_active = isset( $options['breeze-heartbeat-postedit'] ) ? $options['breeze-heartbeat-postedit'] : '';
					?>
					<select name="br-heartbeat-postedit" id="br-heartbeat-postedit">
						<?php
						foreach ( $heartbeat_options as $value => $label ) {
							$selected = '';

							if ( $select_active === (string) $value ) {
								$selected = 'selected';
							}
							echo "<option value='{$value}' {$selected}>{$label}</option>";
						}
						?>
					</select>
					<div class="br-note">
						<p>
							<?php
							_e( 'Change Heartbeat behaviour or disable it completely in post edit screen', 'breeze' );
							?>
						</p>
						<p class="br-important">
							<?php
							echo '<strong>';
							_e( 'Important: ', 'breeze' );
							echo '</strong>';
							_e( 'Disabling Heartbeat may break some functionalities from plugins and themes which are dependent on Heartbeat API.', 'breeze' );
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
						<?php _e( 'Heartbeat Back-end', 'breeze' ); ?>
					</div>
				</div>
				<div class="br-option">
					<?php
					$select_active = isset( $options['breeze-heartbeat-backend'] ) ? $options['breeze-heartbeat-backend'] : '';
					?>
					<select name="br-heartbeat-backend" id="br-heartbeat-backend">
						<?php
						foreach ( $heartbeat_options as $value => $label ) {
							$selected = '';

							if ( $select_active === (string) $value ) {
								$selected = 'selected';
							}
							echo "<option value='{$value}' {$selected}>{$label}</option>";
						}
						?>
					</select>
					<div class="br-note">
						<p>
							<?php
							_e( 'Change Heartbeat behaviour or disable it completely in the admin area', 'breeze' );
							?>
						</p>
						<p class="br-important">
							<?php
							echo '<strong>';
							_e( 'Important: ', 'breeze' );
							echo '</strong>';
							_e( 'Disabling Heartbeat may break some functionalities from plugins and themes which are dependent on Heartbeat API.', 'breeze' );
							?>
						</p>
					</div>
				</div>
			</div>
			<!-- END OPTION -->

		</div><!-- END GROUP -->
	</section>
	<div class="br-submit">
		<input type="submit" value="<?php echo __( 'Save Changes', 'breeze' ); ?>" class="br-submit-save"/>
	</div>
</form>
