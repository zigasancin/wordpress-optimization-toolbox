<?php if (!defined('WPO_VERSION')) die('No direct access allowed'); ?>

				<h3><?php _e('Logging settings', 'wp-optimize'); ?></h3>

				<p></p>

				<div id="wp-optimize-logging-options">
					<?php
					$wpo_logging_options = $options->get_option('logging');
					$wpo_logging_additional_options = $options->get_option('logging-additional');
					$loggers = $wp_optimize->get_logger()->get_loggers();

					foreach ($loggers as $logger) {
						$logger_id = strtolower(get_class($logger));

						$logger_dom_id = 'wp-optimize-logger-'.$logger_id;

						$setting_activated = (empty($wpo_logging_options[$logger_id]) || 'false' == $wpo_logging_options[$logger_id]) ? false : true;

						// Check to ensure that there are additional options.
						$additional_options_details = (empty($wpo_logging_additional_options[$logger_id])) ? null : $wpo_logging_additional_options[$logger_id];

						$additional_options_dom_id = 'additional_options_'.$logger_id;
						$additional_options_filter = 'additional_options_'.$logger_id;
						$additional_options_form_name = 'wp-optimize-logging-additional['.$logger_id.']';
						$additional_options_html = '';
						$additional_options_html = apply_filters($additional_options_filter, $additional_options_html, $additional_options_form_name, $additional_options_details, $logger);
						?>
						<p>
							<input class="wp-optimize-logging-settings" name="wp-optimize-logging[<?php echo $logger_id; ?>]" id="<?php echo $logger_dom_id; ?>" type="checkbox" value="true" <?php if ($setting_activated) echo 'checked="checked"'; ?> data-additional="<?php echo $additional_options_dom_id; ?>"> <label for="<?php echo $logger_dom_id; ?>"><?php echo $logger->get_description(); ?></label>
						</p>
						<?php
						if (!empty($additional_options_html)) {
						?>
							<p id="<?php echo $additional_options_dom_id; ?>"><?php echo $additional_options_html; ?></p>
						<?php
						}
						?>
					<?php
					}
					?>
				</div>

				<hr>

				<div id="wp-optimize-settings-save-results"></div>
				
				<input id="wp-optimize-settings-save" class="button button-primary" type="submit" name="wp-optimize-settings" value="<?php esc_attr_e('Save settings', 'wp-optimize'); ?>" />
				
				<img id="save_spinner" class="wpo_spinner" src="<?php echo esc_attr(admin_url('images/spinner-2x.gif')); ?>" alt="...">
				
				<span id="save_done" class="dashicons dashicons-yes display-none"></span>
				
			</div>
		</div>
	</div>

	</form>

