<?php

if (!defined('WPO_VERSION')) die('No direct access allowed');

// Parent class for all optimizations

abstract class WP_Optimization {

	// Ideally, these would all be the same. But, historically, some are not; hence, three separate IDs.
	// TODO: Make them all the same (will involve some options re-writing)
	public $id;
	
	protected $setting_id;
	// TODO: I suspect that dom_id can be changed + then removed fairly easily. Even if it is used for saving options (haven't checked), that can be re-written at save time.
	protected $dom_id;
	protected $auto_id;
	
	protected $available_for_auto;
	
	protected $ui_sort_order;
	protected $run_sort_order = 1000;
	
	protected $optimizer;
	protected $options;
	
	public $retention_enabled;
	public $retention_period;
	
	// Results. These should be accessed via get_results()
	private $output;
	private $meta;
	private $sql_commands;

	protected $wpdb;

	// This is abstracted so as to provide future possibilities, e.g. logging
	protected function query($sql) {
		$this->sql_commands[] = $sql;
		do_action('wp_optimize_optimization_query', $sql, $this);
		$result = $this->wpdb->query($sql);
		return apply_filters('wp_optimize_optimization_query_result', $result, $sql, $this);
	}
	
	abstract public function get_info();
	
	abstract public function optimize();
	
	abstract public function settings_label();
	
	public function __construct() {
		$class_name = get_class($this);
		// Remove the prefixed WP_Optimization_
		$this->id = substr($class_name, 16);
		$this->optimizer = WP_Optimize()->get_optimizer();
		$this->options = WP_Optimize()->get_options();
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function do_optimization() {
		return $this->optimizer->do_optimization($this);
	}
	
	public function get_optimization_info() {
		return $this->optimizer->get_optimization_info($this);
	}
	
	// This function adds output to the current registered output
	public function register_output($output) {
		$this->output[] = $output;
	}
	
	// This function adds meta-data associated with the result to the registered output
	public function register_meta($key, $value) {
		$this->meta[$key] = $value;
	}
	
	public function init() {
	
		$this->output = array();
		$this->meta = array();
		$this->sql_commands = array();
		
		list ($retention_enabled, $retention_period) = $this->optimizer->get_retain_info();
		
		$this->retention_enabled = $retention_enabled;
		$this->retention_period = $retention_period;
		
	}
	
	// The next three functions reflect the fact that historically, WP-Optimize has not, for all optimizations, used the same ID consistently throughout forms, saved settings, and saved settings for automatic clean-ups. Mostly, it has; but some flexibility is needed for the exceptions.
	public function get_setting_id() {
		return empty($this->setting_id) ? 'user-'.$this->id : 'user-'.$this->setting_id;
	}
	
	public function get_dom_id() {
		return empty($this->dom_id) ? 'clean-'.$this->id : $this->dom_id;
	}
	
	public function get_auto_id() {
		return empty($this->auto_id) ? $this->id : $this->auto_id;
	}
	
	// Only used if $available_for_auto is true, in which case this function should be over-ridden
	public function get_auto_option_description() {
		return 'Error: missing automatic option description ('.$this->id.')';
	}
	
	public function get_results() {
	
		// As yet, we have no need for a dedicated object type for our results
		$results = new stdClass;
		
		$results->sql_commands = $this->sql_commands;
		$results->output = $this->output;
		$results->meta = $this->meta;
		
		return apply_filters('wp_optimize_optimization_results', $results, $this->id, $this);
	}
	
	public function output_settings_html() {

		$wpo_user_selection = $this->options->get_main_settings();
		$setting_id = $this->get_setting_id();
		$dom_id = $this->get_dom_id();

		// N.B. Some of the optimizations used to have an onclick call to fCheck(). But that function was commented out, so did nothing.

		$settings_label = $this->settings_label();
		
		$setting_activated = (empty($wpo_user_selection[$setting_id]) || 'false' == $wpo_user_selection[$setting_id]) ? false : true;
		
		if (!empty($settings_label)) {

			?>
			<div class="wp-optimize-settings wp-optimize-settings-<?php echo $dom_id;?>">
			
				<input name="<?php echo $dom_id;?>" id="<?php echo $dom_id;?>" type="checkbox" value="true" <?php if ($setting_activated) echo 'checked="checked"';?>>

				<label for="<?php echo $dom_id;?>"><?php echo $settings_label; ?></label>

				<br>
				
				<div class="wp-optimize-settings-optimization-info"><?php
					$results = $this->get_optimization_info()->output;
					$subsequent_one = false;
					foreach ($results as $key => $line) {
						if ($subsequent_one) { echo '<br>'; } else { $subsequent_one = true; }
						echo $line;
					}
				?></div>
				
			</div>
			<?php
		} else {
			// error_log, as this is a defect
			error_log("Optimization with setting ID ".$setting_id." lacks a settings label (method: settings_label())");
		}
	}
	
}
