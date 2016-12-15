<?php
/*
Plugin Name: WP-Optimize
Plugin URI: http://updraftplus.com
Description: WP-Optimize is WordPress's #1 most installed optimization plugin. With it, you can clean up your database easily and safely, without manual queries.
Version: 2.0.1
Author: David Anderson, Ruhani Rabin, Team Updraft
Author URI: https://updraftplus.com
Text Domain: wp-optimize
Domain Path: /languages
License: GPLv2 or later
*/

if (!defined('ABSPATH')) die('No direct access allowed');

define('WPO_VERSION', '2.0.1');
define('WPO_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('WPO_PLUGIN_MAIN_PATH', plugin_dir_path( __FILE__ ));

class WP_Optimize {

	private $template_directories;
	
	protected static $_instance = null;
	protected static $_optimizer_instance = null;
	protected static $_options_instance = null;

	public function __construct() {

		register_activation_hook(__FILE__, 'wpo_activation_actions');
		register_deactivation_hook(__FILE__, 'wpo_deactivation_actions');

		add_action('plugins_loaded', array($this, 'plugins_loaded'));

		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'));
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_head', array($this, 'admin_head'));
		add_action('wpo_cron_event2', array($this, 'cron_action'));
		add_filter('cron_schedules', array($this, 'cron_schedules'));
		
	}
	
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public static function get_optimizer() {
		if (empty(self::$_optimizer_instance)) {
			if (!class_exists('WP_Optimizer')) require_once(WPO_PLUGIN_MAIN_PATH.'/includes/class-wp-optimizer.php');
			self::$_optimizer_instance = new WP_Optimizer();
		}
		return self::$_optimizer_instance;
	}
	
	public static function get_options() {
		if (empty(self::$_options_instance)) {
			if (!class_exists('WP_Optimize_Options')) require_once(WPO_PLUGIN_MAIN_PATH.'/includes/class-wp-optimize-options.php');
			self::$_options_instance = new WP_Optimize_Options();
		}
		return self::$_options_instance;
	}
	
	public function admin_init() {
		$this->register_template_directories();
	}
	
	public function plugins_loaded() {
		load_plugin_textdomain('wp-optimize', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function capability_required() {
		return apply_filters('wp_optimize_capability_required', 'manage_options');
	}
	
	public function wp_optimize_menu() {

		$capability_required = $this->capability_required();
	
		if (!current_user_can($capability_required)) { echo "Permission denied."; return; }
	
		$enqueue_version = (defined('WP_DEBUG') && WP_DEBUG) ? WPO_VERSION.'.'.time() : WPO_VERSION;

		wp_enqueue_script('wp-optimize-admin', WPO_PLUGIN_URL.'js/admin.js', array('jquery'), $enqueue_version);
	
		$options = $this->get_options();

		$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'wp_optimize_optimize';  
		if ('wp_optimize_tables' != $active_tab && 'wp_optimize_settings' != $active_tab && 'wp_optimize_may_also' != $active_tab) $active_tab = 'wp_optimize_optimize';

		if ('wp_optimize_optimize' == $active_tab && !empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wpo_optimization')) $options->save_posted_options();
		
		$this->include_template('admin-page-header.php', false, array('active_tab' => $active_tab));

		echo '<div class="wrap wp-optimize-wrap">';

		// TODO: Make the page more interactive by printing all the tabs, and switching using JavaScript instead of using page re-loads
		if ('wp_optimize_tables' == $active_tab) {
		
			// There's no way to trigger this, AFAICT. If we add it, it should have a nonce to protect it.
			$optimize_db = false;//isset($_POST["optimize-db"]);
		
			$this->include_template('tables.php', false, array('optimize_db' => $optimize_db));
			
		} elseif ('wp_optimize_settings' == $active_tab) {
		
			$output = $options->save_posted_settings();
			
			foreach ($output as $item) {
				echo '<div class="updated fade"><strong>'.$item.'</strong></div>';
			}
		
			$this->include_template('admin-settings.php');
			
		} elseif ('wp_optimize_may_also' == $active_tab) {
		
			$this->include_template('may-also-like.php');
			
		} else {
		
			$nonce_passed = (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wpo_optimization')) ? true : false;
		
			// Default tab: wp_optimize_optimize
			
			$optimizer = $this->get_optimizer();
			
			$optimization_results = $nonce_passed ? $optimizer->do_optimizations($_POST) : false;

			if (!empty($optimization_results)) {
			
				echo '<div id="message" class="updated"><strong>';
			
				foreach ($optimization_results as $optimization_result) {
				
					if (!empty($optimization_result->output)) {
					
						foreach ($optimization_result->output as $line) {
							echo $line."<br>";
						}
					
					}
				
				}
				
				echo '</strong></div>';
			
			}

			$optimize_db = ($nonce_passed && isset($_POST["optimize-db"]));
			
			$this->include_template('optimize-table.php', false, array('optimize_db' => $optimize_db));
			
		}

		echo '</div>';

	}

	public function wpo_admin_bar() {
		global $wp_admin_bar;

		// Add a link called at the top admin bar
		$wp_admin_bar->add_node(array(
			'id'    => 'wp-optimize',
			'title' => 'WP-Optimize',
			'href'  => menu_page_url( 'WP-Optimize', false ),
		));

	}

	// Add settings link on plugin page
	public function plugin_settings_link($links) {
	
		$admin_page_url = $this->get_options()->admin_page_url();
	
		$settings_link = '<a href="' . esc_url( $admin_page_url ) . '">' . __( 'Settings', 'wp-optimize' ) . '</a>';
		array_unshift($links, $settings_link);

		$optimize_link = '<a href="' . esc_url( $admin_page_url ) . '">' . __( 'Optimizer', 'wp-optimize' ) . '</a>';
		array_unshift($links, $optimize_link);
		return $links;
	}

	// TODO: Need to find out why the schedule time is not refreshing
	public function cron_activate() {
		$gmtoffset = (int) (3600 * ((double) get_option('gmt_offset')));

		$options = $this->get_options();
		
		if ($options->get_option('schedule') === false ) {
			$options->set_default_options();
		} else {
			if ($options->get_option('schedule') == 'true') {
				if (!wp_next_scheduled('wpo_cron_event2')) {

					$schedule_type = $options->get_option('schedule-type', 'wpo_weekly');

					$this_time = 86400*7;
					
					switch ($schedule_type) {
						case "wpo_daily":
						$this_time = 86400;
						break;

						case "wpo_weekly":
						$this_time = 86400*7;
						break;

						case "wpo_otherweekly":
						$this_time = 86400*14;
						break;

						case "wpo_monthly":
						$this_time = 86400*30;
						break;
					}
					
					add_action('wpo_cron_event2', array($this, 'cron_action'));
					wp_schedule_event(current_time( "timestamp", 0 ) + $this_time , $schedule_type, 'wpo_cron_event2');
					WP_Optimize()->log('running wp_schedule_event()');
				}
			}
		}
	}


	// scheduler public functions to update schedulers
	public function cron_schedules( $schedules ) {
		$schedules['wpo_daily'] = array('interval' => 86400, 'display' => 'Once Daily');
		$schedules['wpo_weekly'] = array('interval' => 86400*7, 'display' => 'Once Weekly');
		$schedules['wpo_otherweekly'] = array('interval' => 86400*14, 'display' => 'Once Every Other Week');
		$schedules['wpo_monthly'] = array('interval' => 86400*30, 'display' => 'Once Every Month');
		return $schedules;
	}


	public function admin_head() {
		$style_url = plugins_url( '/css/wpo_admin.css', __FILE__ ) ;
		echo "<link rel='stylesheet' type='text/css' href='".$style_url."' />\n";
	}
	
	public function admin_menu() {
	
		$capability_required = $this->capability_required();
	
		if (!current_user_can($capability_required)) return;

		if (function_exists('add_meta_box')) {
			add_menu_page("WP-Optimize", "WP-Optimize", $capability_required, "WP-Optimize", array($this,"wp_optimize_menu"), plugin_dir_url( __FILE__ ).'images/icon/wpo.png');
		} else {
			add_submenu_page("index.php", "WP-Optimize", "WP-Optimize", $capability_required, "WP-Optimize", array($this,"wp_optimize_menu"), plugin_dir_url( __FILE__ ).'images/icon/wpo.png');
		}
		
		$options = $this->get_options();
		
		if ($options->get_option('enable-admin-menu', 'false' ) == 'true') {
			add_action('wp_before_admin_bar_render', array($this, 'wpo_admin_bar'));
		}

		$options->set_default_options();
		$this->cron_activate();
	}
	
	private function wp_normalize_path($path) {
		// wp_normalize_path is not present before WP 3.9
		if (function_exists('wp_normalize_path')) return wp_normalize_path($path);
		// Taken from WP 4.6
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}
		return $path;
	}
	
	public function get_templates_dir() {
		return apply_filters('wp_optimize_templates_dir', $this->wp_normalize_path(WPO_PLUGIN_MAIN_PATH.'/templates'));
	}

	public function get_templates_url() {
		return apply_filters('wp_optimize_templates_url', WPO_PLUGIN_MAIN_PATH.'/templates');
	}
	
	public function include_template($path, $return_instead_of_echo = false, $extract_these = array()) {
		if ($return_instead_of_echo) ob_start();

		if (preg_match('#^([^/]+)/(.*)$#', $path, $matches)) {
			$prefix = $matches[1];
			$suffix = $matches[2];
			if (isset($this->template_directories[$prefix])) {
				$template_file = $this->template_directories[$prefix].'/'.$suffix;
			}
		}

		if (!isset($template_file)) {
			$template_file = WPO_PLUGIN_MAIN_PATH.'/templates/'.$path;
		}

		$template_file = apply_filters('wp_optimize_template', $template_file, $path);

		do_action('wp_optimize_before_template', $path, $template_file, $return_instead_of_echo, $extract_these);

		if (!file_exists($template_file)) {
			error_log("WP Optimize: template not found: $template_file");
			echo __('Error:', 'wp-optimize').' '.__('template not found', 'wp-optimize')." ($path)";
		} else {
			extract($extract_these);
			global $wpdb;
			$wp_optimize = $this;
			$optimizer = $this->get_optimizer();
			$options = $this->get_options();
			include $template_file;
		}

		do_action('wp_optimize_after_template', $path, $template_file, $return_instead_of_echo, $extract_these);

		if ($return_instead_of_echo) return ob_get_clean();
	}
	
	private function register_template_directories() {

		$template_directories = array();

		$templates_dir = $this->get_templates_dir();

		if ($dh = opendir($templates_dir)) {
			while (($file = readdir($dh)) !== false) {
				if ('.' == $file || '..' == $file) continue;
				if (is_dir($templates_dir.'/'.$file)) {
					$template_directories[$file] = $templates_dir.'/'.$file;
				}
			}
			closedir($dh);
		}

		// This is the optimal hook for most extensions to hook into
		$this->template_directories = apply_filters('wp_optimize_template_directories', $template_directories);

	}
	
	// TODO: Not currently used; investigate.
	// TODO: The description does not match the actual function
	/**
	* send_email($sendto, $msg)
	* @return success
	* @param $sentdo - eg. who to send it to, abc@def.com
	* @param $msg - the msg in text
	*/
	public function send_email($date, $cleanedup){
	//
		ob_start();
		// #TODO this need to work on - currently not using the parameter values
		$myTime = current_time( "timestamp", 0 );
		$myDate = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $myTime );

		//$formattedCleanedup = $wp_optimize->format_size($cleanedup);

		$sendto = $options->get_option('email-address');
		if (!$sendto) $sendto = get_bloginfo ( 'admin_email' );
				
		//$thiscleanup = $wp_optimize->format_size($cleanedup);
			
		$subject = get_bloginfo ( 'name' ).": ".__("Automatic Operation Completed","wp-optimize")." ".$myDate;

		$msg  = __("Scheduled optimization was executed at","wp-optimize")." ".$myDate."\r\n"."\r\n";
		//$msg .= __("Recovered space","wp-optimize").": ".$thiscleanup."\r\n";
		$msg .= __("You can safely delete this email.","wp-optimize")."\r\n";
		$msg .= "\r\n";
		$msg .= __("Regards,","wp-optimize")."\r\n";
		$msg .= __("WP-Optimize Plugin","wp-optimize");

		//wp_mail( $sendto, $subject, $msg );

		ob_end_flush();
	}
	
	/*
	* function log()
	*
	* parameters: message to debug
	*
	* @return none
	*/
	public function log($message) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log($message);
		}
	}
	
	/**
	* $wp_optimize->format_size()
	* Function: Format Bytes Into KB/MB
	* @param mixed $bytes
	* @return
	*/
	public function format_size($bytes) {
		if ($bytes > 1073741824) {
			return number_format_i18n($bytes/1073741824, 2) . ' '.__('GB', 'wp-optimize');
		} elseif ($bytes > 1048576) {
			return number_format_i18n($bytes/1048576, 1) . ' '.__('MB', 'wp-optimize');
		} elseif ($bytes > 1024) {
			return number_format_i18n($bytes/1024, 1) . ' '.__('KB', 'wp-optimize');
		} else {
			return number_format_i18n($bytes, 0) . ' '.__('bytes', 'wp-optimize');
		}
	}
	
	/*
	* function cron_action()
	*
	* parameters: none
	*
	* executed this function on cron event
	*
	* @return none
	*/
	public function cron_action() {
	
		$optimizer = $this->get_optimizer();
		$options = $this->get_options();
		
		$this->log('WPO: Starting cron_action()');
		
		if ('true' == $options->get_option('schedule')) {

			$this_options = $options->get_option('auto');
			
			$optimizations = $optimizer->get_optimizations();
			
			// TODO: The output of the optimizations is not saved/used/logged
			$results = $optimizer->do_optimizations($this_options, 'auto');
			
		}
		
	}
	
}

function WP_Optimize() {
	return WP_Optimize::instance();
}

$GLOBALS['wp_optimize'] = WP_Optimize();

// plugin activation actions
function wpo_activation_actions() {
	WP_Optimize()->get_options()->set_default_options();
}

// plugin deactivation actions
function wpo_deactivation_actions() {
	wpo_cron_deactivate();
	WP_Optimize()->get_options()->delete_all_options();
}

function wpo_cron_deactivate() {
	WP_Optimize()->log('running wpo_cron_deactivate()');
	wp_clear_scheduled_hook('wpo_cron_event2');
}
