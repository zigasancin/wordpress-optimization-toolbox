<?php if (!defined('WPO_VERSION')) die ('No direct access allowed'); ?>

<div class="wpo_section wpo_group">

	<form action="#" method="post" enctype="multipart/form-data" name="settings_form" id="settings_form">

	<input type="hidden" name="action" value="save_redirect" />

	<?php wp_nonce_field( 'wpo_settings' ); ?>

	<div class="wpo_col wpo_span_1_of_3">
		<div class="postbox">
			<div class="inside">
				<h3><?php _e('General settings', 'wp-optimize'); ?></h3>
				<p>
					<input name="enable-retention" id="enable-retention" type="checkbox" value ="true" <?php echo $options->get_option('retention-enabled') == 'true' ? 'checked="checked"':''; ?> />
					<?php

					$retention_period = max((int)$options->get_option('retention-period', '2'), 1);
					
					echo '<label for="enable-retention">';
					printf(__('Keep last %s weeks data', 'wp-optimize'),
					'</label><input id="retention-period" name="retention-period" type="number" step="1" min="2" max="99" value="'.$retention_period.'"><label for="enable-retention">');
				   	echo '</label>'; ?>
				   	<br>
				   	<small><?php _e('This option will, where relevant, retain data from the chosen period, and remove any garbage data before that period.', 'wp-optimize').' '.__('If the option is not active, then all garbage data will be removed.', 'wp-optimize').' '.__('This will also affect Auto Clean-up process', 'wp-optimize');?></small>
				</p>
				<p>
					<label>
						<input name="enable-admin-bar" id="enable-admin-bar" type="checkbox" value ="true" <?php echo $options->get_option('enable-admin-menu', 'false') == 'true' ? 'checked="checked"':''; ?> />
						<?php _e('Enable admin bar link', 'wp-optimize');?>
					</label>
					<br>
					<small><?php _e('This option will put an WP-Optimize link on the top admin bar (default is off). Requires a second page refresh after saving the settings.', 'wp-optimize');?></small>
				</p>
					<h3><?php _e('Trackback/comments actions', 'wp-optimize'); ?></h3>
				<p>
					<?php _e('Disable/enable trackbacks', 'wp-optimize'); ?>
					<br>
					<select id="wp-optimize-disable-enable-trackbacks" name="wp-optimize-disable-enable-trackbacks">
						<option value="-1"><?php _e('SELECT', 'wp-optimize'); ?></option>
						<option value="0"><?php _e('Disable', 'wp-optimize'); ?></option>
						<option value="1"><?php _e('Enable', 'wp-optimize'); ?></option>
					</select>
					<br><br>
					<small><?php _e('This will disable/enable Trackbacks on all your current and previously published posts', 'wp-optimize');?></small>
				</p>
				<p>
					<?php _e('Disable/enable comments', 'wp-optimize'); ?>
					<br>
					<select id="wp-optimize-disable-enable-comments" name="wp-optimize-disable-enable-comments">
						<option value="-1"><?php _e('SELECT', 'wp-optimize'); ?></option>
						<option value="0"><?php _e('Disable', 'wp-optimize'); ?></option>
						<option value="1"><?php _e('Enable', 'wp-optimize'); ?></option>
					</select>
					<br><br>
					<small><?php _e('This will disable/enable Comments on all your current and previously published posts', 'wp-optimize');?></small>
				</p>

				<p>
					<input class="button-primary" type="submit" name="wp-optimize-settings1" value="<?php esc_attr_e('Save settings', 'wp-optimize'); ?>" />
				</p>
			</div>
		</div>
	</div>

	<?php $wpo_auto_options = $options->get_option('auto'); ?>
	
	<div class="wpo_col wpo_span_1_of_3">
		<div class="postbox">
			<div class="inside">
				<h3><?php _e('Auto clean-up settings', 'wp-optimize'); ?></h3>

				<p>
				
					<input name="enable-schedule" id="enable-schedule" type="checkbox" value ="true" <?php echo $options->get_option('schedule') == 'true' ? 'checked="checked"':''; ?> />
					<label for="enable-schedule"><?php _e('Enable scheduled clean-up and optimization (Beta feature!)', 'wp-optimize'); ?></label>
					
				</p>
					
				<div id="wp-optimize-auto-options">
				
					<p>
						
						<?php _e('Select schedule type (default is Weekly)', 'wp-optimize'); ?><br>
						<select id="schedule_type" name="schedule_type">
						
							<?php
								$schedule_options = array(
									'wpo_daily' => __('Daily', 'wp-optimize'),
									'wpo_weekly' => __('Weekly', 'wp-optimize'),
									'wpo_otherweekly' => __('Fortnightly', 'wp-optimize'),
									'wpo_monthly' => __('Monthly (approx. - every 30 days)', 'wp-optimize'),
								);
								
								$schedule_type_saved_id = $options->get_option('schedule-type', 'wpo_weekly');
								
								foreach ($schedule_options as $opt_id => $opt_description) {
									?>
									<option value="<?php echo esc_attr($opt_id);?>" <?php if ($opt_id == $schedule_type_saved_id) echo 'selected="selected"';?>><?php echo htmlspecialchars($opt_description);?></option>
									<?php
								}
								
							?>

						</select>
						<br><br>
						<small><?php _e('Automatic cleanup will perform the following:', 'wp-optimize');
						echo '<br>';
						_e('Remove revisions, auto drafts, posts/comments in trash, transient options. After that it will optimize the db.', 'wp-optimize');?></small>

					</p>
					
					<?php
					// TODO: No ordering is currently applied. The previous ordering:
					// revisions, drafts(=autodraft), spams(=spam), unapproved, (red)transient, [commented out: postmeta, tags], optimizedb

					// TODO: postmeta ("Remove orphaned post meta") and tags ("Remove unused tags") were present in the HTML previously, but commented out. Should ask Ruhani about that.
						$optimizations = $optimizer->sort_optimizations($optimizer->get_optimizations());
						
						foreach ($optimizations as $id => $optimization) {
						
							if (empty($optimization->available_for_auto)) continue;
							
							$auto_id = $optimization->get_auto_id();
							
							$auto_dom_id = 'wp-optimize-auto-'.$auto_id;

							$setting_activated = (empty($wpo_auto_options[$auto_id]) || 'false' == $wpo_auto_options[$auto_id]) ? false : true;

							?><p>
								<input name="wp-optimize-auto[<?php echo $auto_id;?>]" id="<?php echo $auto_dom_id;?>" type="checkbox" value="true" <?php if ($setting_activated) echo 'checked="checked"'; ?>> <label for="<?php echo $auto_dom_id;?>"><?php echo $optimization->get_auto_option_description(); ?></label>
							</p>
							<?php
						
						}
					?>
					
					<!-- disabled email notification
					<p>
						<label>
								<input name="enable-email" id="enable-email" type="checkbox" value ="true" <?php // echo $options->get_option('enable-email', 'false') == 'true' ? 'checked="checked"':''; ?> />
								<?php //_e('Enable email notification', 'wp-optimize');?>
						</label>
					</p>
					<p>
						<label for="enable-email-address">
								<?php //_e('Send email to', 'wp-optimize');?>
							<input name="enable-email-address" id="enable-email-address" type="text" value ="<?php //echo  // esc_attr( $options->get_option('enable-email-address', get_bloginfo ( 'admin_email' ) ) ); ?>" />
						</label>
					</p> -->
					
				</div>
				
				<input class="button-primary" type="submit" name="wp-optimize-settings" value="<?php esc_attr_e('Save auto clean-up settings', 'wp-optimize'); ?>" />
				
			</div>
			
		</div>
	</div>

	</form>
</div>
