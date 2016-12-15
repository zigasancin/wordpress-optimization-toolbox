<?php

	if (!defined('WPO_VERSION')) die ('No direct access allowed');

	$retention_enabled = $options->get_option('retention-enabled', 'false');
	$retention_period = $options->get_option('retention-period', '2');
	$admin_page_url = $options->admin_page_url();
	
?>

<div class="wpo_section wpo_group">
	<form onSubmit="return confirm('<?php echo esc_js('WARNING: This operation is permanent. Continue?', 'wp-optimize'); ?>')" action="#" method="post" enctype="multipart/form-data" name="optimize_form" id="optimize_form">
	
	<?php wp_nonce_field('wpo_optimization'); ?>
	
	<div class="wpo_col wpo_span_1_of_3">
		<div class="postbox">
			<div class="inside">
			
				<h3><?php _e('Optimization options', 'wp-optimize'); ?></h3>
				
				<?php
					$optimizations = $optimizer->sort_optimizations($optimizer->get_optimizations());

					foreach ($optimizations as $id => $optimization) {
						$optimization->output_settings_html();
					}
				?>
				
				<p>
					<?php echo '<span style="color: red;">'.__('Warning:', 'wp-optimize').'</span> '.__('Items marked in red, whilst still safe, perform more database delete operations.', 'wp-optimize').' '.__('You may wish to run a backup before performing them.', 'wp-optimize'); ?>
				</p>
			
			</div>
		</div>
	 </div>

	<div class="wpo_col wpo_span_1_of_3">
		<div class="postbox">
			<div class="inside">
				<h3><?php _e('Actions', 'wp-optimize'); ?></h3>

				<p>
					<!-- <span style="text-align:center;"><a href="#" onClick="javascript:SetDefaults();">
					<?php _e('Select safe options', 'wp-optimize'); ?></a></span> -->
					<small><strong><?php _e('Warning:', 'wp-optimize'); ?></strong> <?php echo __('It is best practice to always make a backup of your database before any major operation (optimizing, upgrading, etc.).', 'wp-optimize').' <a href="https://wordpress.org/plugins/updraftplus">'.__('If you need a backup plugin, then we recommend UpdraftPlus.', 'wp-optimize').'</a>'; ?></small>

				</p>
				<p>
					<input class="wpo_primary_big button-primary" type="submit" id="wp-optimize" name="wp-optimize" value="<?php esc_attr_e('Run optimizations now', 'wp-optimize'); ?>" />
				</p>

				<h3><?php _e('Status', 'wp-optimize'); ?></h3>

   				<?php
   				
   				$sqlversion = (string)$wpdb->get_var("SELECT VERSION() AS version");

				echo '<p>WP-Optimize '.WPO_VERSION.' - '.__('MySQL', 'wp-optimize').' '.$sqlversion.' - '.htmlspecialchars(PHP_OS).'</p>';
   				
   				echo '<p>';
   				
				$lastopt = $options->get_option('last-optimized', 'Never');
				if ($lastopt !== 'Never') {
					echo __('Last automatic optimization was at', 'wp-optimize').': ';
					echo '<span style="font-color: green; font-weight:bold;">';
					echo htmlspecialchars($lastopt);
					echo '</span>';
				} else {
					echo __('There was no automatic optimization', 'wp-optimize');
				} 
				?>
				<br>

				<?php
				if ($options->get_option('schedule', 'false') == 'true') {
					echo '<strong><span style="font-color: green">';
					_e('Scheduled cleaning enabled', 'wp-optimize');
					echo ', </span></strong>';
					if (wp_next_scheduled('wpo_cron_event2')) {
						//$timestamp = wp_next_scheduled( 'wpo_cron_event2' );
						$wp_optimize->cron_activate();

						$timestamp = wp_next_scheduled( 'wpo_cron_event2' );
						$date = new DateTime("@$timestamp");
						_e('Next schedule:', 'wp-optimize');
						echo ' ';
						echo '<span style="font-color: green">';
						echo gmdate(get_option('date_format') . ' ' . get_option('time_format'), $timestamp );
						echo '</span>';
						echo ' - <a href="'.esc_attr($admin_page_url).'">'.__('Refresh', 'wp-optimize').'</a>';
						//echo $timestamp;
					}
				} else {
					echo '<strong>';
					_e('Scheduled cleaning disabled', 'wp-optimize');
					echo '</strong>';
				}
				echo '<br>';

				if ($retention_enabled == 'true') {
					echo '<strong><span style="font-color: blue;">';
					printf(__('Keeping last %s weeks data', 'wp-optimize'), $retention_period) ;
					echo '</span></strong>';
				} else {
					echo '<strong>'.__('Not keeping recent data', 'wp-optimize').'</strong>';
				}
				?>
				</p>
				
				<p>
				<strong><?php _e('Current database size:', 'wp-optimize');?></strong>

					<?php
					
					list ($total_size, $total_gain) = $optimizer->get_current_db_size();
					echo ' <span class="current-database-size">'.$total_size.'</span> <br>';
					
					if ($optimize_db) {
						_e('You have saved:', 'wp-optimize');
						echo ' <span style="font-color: blue;">'.$total_gain.'</span>';
					} else {
						if ($total_gain > 0) {
							_e('You can save around:', 'wp-optimize');
							echo ' <span style="font-color: red;">'.$total_gain.'</span> ';
						}
					}
					?>
				
				</p>
				<p>
				<?php
					$total_cleaned = $options->get_option('total-cleaned');
    				$total_cleaned_num = floatval($total_cleaned);

        			if ($total_cleaned_num  > 0) {
						echo '<h5>'.__('Total clean up overall:','wp-optimize').' ';
						echo '<span style="font-color: green">';
						echo $wp_optimize->format_size($total_cleaned);
						echo '</span></h5>';
					}
				?></p>
				
				
				<h3><?php _e('Support and feedback', 'wp-optimize');?></h3>
				<p>
					<?php echo __('If you like WP-Optimize,', 'wp-optimize').' <a href="https://wordpress.org/support/plugin/wp-optimize/reviews/?rate=5#new-post" target="_blank">'.__('please give us a positive review, here.', 'wp-optimize'); ?></a> <?php echo __('Or, if you did not like it,', 'wp-optimize').' <a target="_blank" href="https://wordpress.org/support/plugin/wp-optimize/">'.__('please tell us why at this link.', 'wp-optimize'); ?></a>
					<a href="https://wordpress.org/support/plugin/wp-optimize/"><?php _e('Support is available here.', 'wp-optimize'); ?></a>
				</p>

				
			</div>
		</div>
	</div>
	<div class="wpo_col wpo_span_1_of_3">
	</div>
	</form>
</div>
