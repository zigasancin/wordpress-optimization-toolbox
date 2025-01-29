<?php
	class WpFastestCacheAdmin extends WpFastestCache{
		private $adminPageUrl = "wp-fastest-cache/admin/index.php";
		private $systemMessage = array();
		private $options = array();
		private $cronJobSettings;
		private $startTime;
		private $blockCache = false;

		public function __construct(){
			$this->options = $this->getOptions();
			
			$this->setCronJobSettings();
			$this->addButtonOnEditor();
			add_action('admin_enqueue_scripts', array($this, 'addJavaScript'));
			add_filter('plugin_locale', array($this, 'my_plugin_locale_filter'), 10, 2);
		}

		public function my_plugin_locale_filter($locale, $domain){
			if($domain === 'wp-fastest-cache'){

				if(!isset($this->options->wpFastestCacheLanguage)){
					return "en_US";
				}

				$locale = $this->options->wpFastestCacheLanguage;
				
				if(file_exists(WPFC_MAIN_PATH."languages/wp-fastest-cache-".$locale.".mo")){
					return $locale;
				}else{
					return "en_US";
				}
			}

			return $locale;
		}

		public function create_auto_cache_timeout($recurrance, $interval){
			$exist_cronjob = false;
			$wpfc_timeout_number = 0;

			$crons = _get_cron_array();

			foreach ((array)$crons as $cron_key => $cron_value) {
				foreach ( (array) $cron_value as $hook => $events ) {
					if(preg_match("/^wp\_fastest\_cache(.*)/", $hook, $id)){
						if(!$id[1] || preg_match("/^\_(\d+)$/", $id[1])){
							$wpfc_timeout_number++;

							foreach ( (array) $events as $event_key => $event ) {
								$schedules = wp_get_schedules();

								if(isset($event["args"]) && isset($event["args"][0])){
									if($event["args"][0] == '{"prefix":"all","content":"all"}'){
										if($schedules[$event["schedule"]]["interval"] <= $interval){
											$exist_cronjob = true;
										}
									}
								}
							}
						}
					}
				}
			}

			if(!$exist_cronjob){
				$args = array("prefix" => "all", "content" => "all");
				wp_schedule_event(time(), $recurrance, "wp_fastest_cache_".$wpfc_timeout_number, array(json_encode($args)));
			}
		}

		public function get_premium_version(){
			$wpfc_premium_version = "";
			if(file_exists(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/wpFastestCachePremium.php")){
				if($data = @file_get_contents(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/wpFastestCachePremium.php")){
					preg_match("/Version:\s*(.+)/", $data, $out);
					if(isset($out[1]) && $out[1]){
						$wpfc_premium_version = trim($out[1]);
					}
				}
			}
			return $wpfc_premium_version;
		}

		public function addButtonOnEditor(){
			add_action('admin_print_footer_scripts', array($this, 'addButtonOnQuicktagsEditor'));
			add_action('init', array($this, 'myplugin_buttonhooks'));
		}

		public function checkShortCode($content){
			preg_match("/\[wpfcNOT\]/", $content, $wpfcNOT);
			if(count($wpfcNOT) > 0){
				if(is_single() || is_page()){
					$this->blockCache = true;
				}
				$content = str_replace("[wpfcNOT]", "", $content);
			}
			return $content;
		}

		public function myplugin_buttonhooks() {
		   // Only add hooks when the current user has permissions AND is in Rich Text editor mode
		   if (current_user_can( 'manage_options' )) {
		     add_filter("mce_external_plugins", array($this, "myplugin_register_tinymce_javascript"));
		     add_filter('mce_buttons', array($this, 'myplugin_register_buttons'));
		   }
		}
		// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
		public function myplugin_register_tinymce_javascript($plugin_array) {
		   $plugin_array['wpfc'] = plugins_url('../js/button.js?v='.time(),__file__);
		   return $plugin_array;
		}

		public function myplugin_register_buttons($buttons) {
		   array_push($buttons, 'wpfc');
		   return $buttons;
		}

		public function addButtonOnQuicktagsEditor(){
			if (wp_script_is('quicktags') && current_user_can( 'manage_options' )){ ?>
				<script type="text/javascript">
					if(typeof QTags != "undefined"){
				    	QTags.addButton('wpfc_not', 'wpfcNOT', '<!--[wpfcNOT]-->', '', '', 'Block caching for this page');
					}
			    </script>
		    <?php }
		}

		public function optionsPageRequest(){
			if(!empty($_POST)){
				if(isset($_POST["wpFastestCachePage"])){
					include_once ABSPATH."wp-includes/capabilities.php";
					include_once ABSPATH."wp-includes/pluggable.php";

					// if(defined("WPFC_MULTI_SITE_BETA") && WPFC_MULTI_SITE_BETA){
					// 	//nothing
					// }else{
					// 	if(is_multisite()){
					// 		$this->notify(array("The plugin does not work with Multisite.\n Please <a target='_blank' href='https://www.wpfastestcache.com/blog/multi-site/'>click here</a> to learn how to enable it.", "error"));
					// 		return 0;
					// 	}
					// }

					if(current_user_can('manage_options')){
						if($_POST["wpFastestCachePage"] == "options"){
							$this->exclude_urls();

							$this->saveOption();
						}else if($_POST["wpFastestCachePage"] == "deleteCache"){
							$this->deleteCache();
						}else if($_POST["wpFastestCachePage"] == "deleteCssAndJsCache"){
							$this->deleteCache(true);
						}else if($_POST["wpFastestCachePage"] == "cacheTimeout"){
							$this->addCacheTimeout();
						}
					}else{
						die("Forbidden");
					}
				}
			}
		}

		public function exclude_urls(){
			// to exclude wishlist url of YITH WooCommerce Wishlist
			if($this->isPluginActive('yith-woocommerce-wishlist/init.php')){
				$wishlist_page_id = get_option("yith_wcwl_wishlist_page_id");
				$permalink = urldecode(get_permalink($wishlist_page_id));

				if(preg_match("/https?:\/\/[^\/]+\/(.+)/", $permalink, $out)){
					$url = trim($out[1], "/");
				}
			}


			if(isset($url) && $url){
				$rules_std = array();
				$rules_json = get_option("WpFastestCacheExclude");

				$new_rule = new stdClass;
				$new_rule->prefix = "exact";
				$new_rule->content = $url;
				$new_rule->type = "page";


				if($rules_json === false){
					array_push($rules_std, $new_rule);
					add_option("WpFastestCacheExclude", json_encode($rules_std), null, "yes");
				}else{
					$rules_std = json_decode($rules_json);

					if(!is_array($rules_std)){
						$rules_std = array();
					}

					if(!in_array($new_rule, $rules_std)){
						array_push($rules_std, $new_rule);
						update_option("WpFastestCacheExclude", json_encode($rules_std));
					}
				}
			}
		}

		public function addCacheTimeout(){
			if(isset($_POST["wpFastestCacheTimeOut"])){
				if($_POST["wpFastestCacheTimeOut"]){
					if(isset($_POST["wpFastestCacheTimeOutHour"]) && is_numeric($_POST["wpFastestCacheTimeOutHour"])){
						if(isset($_POST["wpFastestCacheTimeOutMinute"]) && is_numeric($_POST["wpFastestCacheTimeOutMinute"])){
							$selected = mktime($_POST["wpFastestCacheTimeOutHour"], $_POST["wpFastestCacheTimeOutMinute"], 0, date("n"), date("j"), date("Y"));

							if($selected > time()){
								$timestamp = $selected;
							}else{
								if(time() - $selected < 60){
									$timestamp = $selected + 60;
								}else{
									// if selected time is less than now, 24hours is added
									$timestamp = $selected + 24*60*60;
								}
							}

							wp_clear_scheduled_hook($this->slug());
							wp_schedule_event($timestamp, $_POST["wpFastestCacheTimeOut"], $this->slug());
						}else{
							echo "Minute was not set";
							exit;
						}
					}else{
						echo "Hour was not set";
						exit;
					}
				}else{
					wp_clear_scheduled_hook($this->slug());
				}
			}
		}

		public function setCronJobSettings(){
			if(wp_next_scheduled($this->slug())){
				$this->cronJobSettings["period"] = wp_get_schedule($this->slug());
				$this->cronJobSettings["time"] = wp_next_scheduled($this->slug());
			}
		}

		public function addMenuPage(){
			add_action('admin_menu', array($this, 'register_my_custom_menu_page'));
		}

		public function addJavaScript(){
			wp_enqueue_script("jquery-ui-draggable");
			wp_enqueue_script("jquery-ui-position");
			wp_enqueue_script("jquery-ui-sortable");
			wp_enqueue_script("wpfc-dialog", plugins_url("wp-fastest-cache/js/dialog.js"), array(), time(), false);
			wp_enqueue_script("wpfc-dialog-new", plugins_url("wp-fastest-cache/js/dialog_new.js"), array(), time(), false);


			wp_enqueue_script("wpfc-cdn", plugins_url("wp-fastest-cache/js/cdn/cdn.js"), array(), time(), false);


			wp_enqueue_script("wpfc-schedule", plugins_url("wp-fastest-cache/js/schedule.js"), array(), time(), true);
			wp_enqueue_script("wpfc-db", plugins_url("wp-fastest-cache/js/db.js"), array(), time(), true);

			
			if(class_exists("WpFastestCacheImageOptimisation")){
				if(file_exists(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/js/statics.js")){
					wp_enqueue_script("wpfc-statics", plugins_url("wp-fastest-cache-premium/pro/js/statics.js"), array(), time(), false);
				}

				if(file_exists(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/js/premium.js")){
					wp_enqueue_script("wpfc-premium", plugins_url("wp-fastest-cache-premium/pro/js/premium.js"), array(), time(), true);
				}
			}
			
		}

		public function saveOption(){
			unset($_POST["wpFastestCachePage"]);
			unset($_POST["option_page"]);
			unset($_POST["action"]);
			unset($_POST["_wpnonce"]);
			unset($_POST["_wp_http_referer"]);
			
			$data = json_encode($_POST);
			//for optionsPage() $_POST is array and json_decode() converts to stdObj
			$this->options = json_decode($data);

			$this->systemMessage = $this->modifyHtaccess($_POST);

			if(isset($this->systemMessage[1]) && $this->systemMessage[1] != "error"){

				if($message = $this->checkCachePathWriteable()){


					if(is_array($message)){
						$this->systemMessage = $message;
					}else{
						if(isset($this->options->wpFastestCachePreload)){
							$this->set_preload();
						}else{
							delete_option("WpFastestCachePreLoad");
							wp_clear_scheduled_hook("wp_fastest_cache_Preload");
						}

						if(get_option("WpFastestCache")){
							update_option("WpFastestCache", $data);
						}else{
							add_option("WpFastestCache", $data, null, "yes");
						}
					}
				}
			}

			$this->notify($this->systemMessage);
		}

		public function checkCachePathWriteable(){
			$message = array();

			if(!is_dir($this->getWpContentDir("/cache/"))){
				if (@mkdir($this->getWpContentDir("/cache/"), 0755, true)){
					//
				}else{
					array_push($message, "- ".$this->getWpContentDir("/cache/")." is needed to be created");
				}
			}else{
				if (@mkdir($this->getWpContentDir("/cache/testWpFc/"), 0755, true)){
					rmdir($this->getWpContentDir("/cache/testWpFc/"));
				}else{
					array_push($message, "- ".$this->getWpContentDir("/cache/")." permission has to be 755");
				}
			}

			if(!is_dir($this->getWpContentDir("/cache/all/"))){
				if (@mkdir($this->getWpContentDir("/cache/all/"), 0755, true)){
					//
				}else{
					array_push($message, "- ".$this->getWpContentDir("/cache/all/")." is needed to be created");
				}
			}else{
				if (@mkdir($this->getWpContentDir("/cache/all/testWpFc/"), 0755, true)){
					rmdir($this->getWpContentDir("/cache/all/testWpFc/"));
				}else{
					array_push($message, "- ".$this->getWpContentDir("/cache/all/")." permission has to be 755");
				}	
			}

			if(count($message) > 0){
				return array(implode("<br>", $message), "error");
			}else{
				return true;
			}
		}

		public function modifyHtaccess($post){
			$path = ABSPATH;
			if($this->is_subdirectory_install()){
				$path = $this->getABSPATH();
			}

			// if(isset($_SERVER["SERVER_SOFTWARE"]) && $_SERVER["SERVER_SOFTWARE"] && preg_match("/iis/i", $_SERVER["SERVER_SOFTWARE"])){
			// 	return array("The plugin does not work with Microsoft IIS. Only with Apache", "error");
			// }

			// if(isset($_SERVER["SERVER_SOFTWARE"]) && $_SERVER["SERVER_SOFTWARE"] && preg_match("/nginx/i", $_SERVER["SERVER_SOFTWARE"])){
			// 	return array("The plugin does not work with Nginx. Only with Apache", "error");
			// }

			if(!file_exists($path.".htaccess")){
				if(isset($_SERVER["SERVER_SOFTWARE"]) && $_SERVER["SERVER_SOFTWARE"] && (preg_match("/iis/i", $_SERVER["SERVER_SOFTWARE"]) || preg_match("/nginx/i", $_SERVER["SERVER_SOFTWARE"]))){
					//
				}else{
					return array("<label>.htaccess was not found</label> <a target='_blank' href='http://www.wpfastestcache.com/warnings/htaccess-was-not-found/'>Read More</a>", "error");
				}
			}

			if($this->isPluginActive('wp-postviews/wp-postviews.php')){
				$wp_postviews_options = get_option("views_options");
				$wp_postviews_options["use_ajax"] = true;
				update_option("views_options", $wp_postviews_options);

				if(!WP_CACHE){
					if($wp_config = @file_get_contents(ABSPATH."wp-config.php")){
						$wp_config = str_replace("\$table_prefix", "define('WP_CACHE', true);\n\$table_prefix", $wp_config);

						if(!@file_put_contents(ABSPATH."wp-config.php", $wp_config)){
							return array("define('WP_CACHE', true); is needed to be added into wp-config.php", "error");
						}
					}else{
						return array("define('WP_CACHE', true); is needed to be added into wp-config.php", "error");
					}
				}
			}

			if(get_option('template') == "Divi"){
				// Divi Theme - Static CSS File Generation
				if($et_divi = get_option("et_divi")){
					if(isset($et_divi["et_pb_static_css_file"]) && $et_divi["et_pb_static_css_file"] == "on"){
						return array("You have to disable the <u><a target='_blank' href='https://www.wpfastestcache.com/tutorial/divi-theme-settings/'>Static CSS File Generation</a></u> option of Divi Theme", "error");
					}
				}
			}

			if($this->isPluginActive('elementor/elementor.php')){
				// Elementor Plugin - Element Caching
				if($elementor_cache = get_option("elementor_experiment-e_element_cache")){
					if($elementor_cache != "inactive"){
						return array("You have to set the <u><a target='_blank' href='https://www.wpfastestcache.com/tutorial/elementor-plugin-settings/'>Element Caching</a></u> option of the Elementor plugin to Inactive", "error");
					}
				}
			}

			if(file_exists($path.".htaccess")){
				$htaccess = @file_get_contents($path.".htaccess");
			}else{
				$htaccess = "";
			}

			// if(defined('DONOTCACHEPAGE')){
			// 	return array("DONOTCACHEPAGE <label>constant is defined as TRUE. It must be FALSE</label>", "error");
			// }else 
			

			if(!get_option('permalink_structure')){
				return array("You have to set <strong><u><a target='_blank' href='https://www.wpfastestcache.com/tutorial/how-to-change-default-permalink-in-wordpress/'>permalinks</a></u></strong>", "error");
			}else if($res = $this->checkSuperCache($path, $htaccess)){
				return $res;
			}else if($this->isPluginActive('cookie-notice/cookie-notice.php')){
				return array("Cookie Notice & Compliance for GDPR / CCPA needs to be deactivated", "error");
			}else if($this->isPluginActive('fast-velocity-minify/fvm.php')){
				return array("Fast Velocity Minify needs to be deactivated", "error");
			}else if($this->isPluginActive('far-future-expiration/far-future-expiration.php')){
				return array("Far Future Expiration Plugin needs to be deactivated", "error");
			}else if($this->isPluginActive('sg-cachepress/sg-cachepress.php')){
				return array("SG Optimizer needs to be deactived", "error");
			}else if($this->isPluginActive('adrotate/adrotate.php') || $this->isPluginActive('adrotate-pro/adrotate.php')){
				return $this->warningIncompatible("AdRotate");
			}else if($this->isPluginActive('mobilepress/mobilepress.php')){
				return $this->warningIncompatible("MobilePress", array("name" => "WPtouch Mobile", "url" => "https://wordpress.org/plugins/wptouch/"));
			}else if($this->isPluginActive('speed-booster-pack/speed-booster-pack.php')){
				return array("Speed Booster Pack needs to be deactivated<br>", "error");
			}else if($this->isPluginActive('cdn-enabler/cdn-enabler.php')){
				return array("CDN Enabler needs to be deactivated<br>This plugin has aldready CDN feature", "error");
			}else if($this->isPluginActive('wp-performance-score-booster/wp-performance-score-booster.php')){
				return array("WP Performance Score Booster needs to be deactivated<br>This plugin has aldready Gzip, Leverage Browser Caching features", "error");
			}else if($this->isPluginActive('bwp-minify/bwp-minify.php')){
				return array("Better WordPress Minify needs to be deactivated<br>This plugin has aldready Minify feature", "error");
			}else if($this->isPluginActive('check-and-enable-gzip-compression/richards-toolbox.php')){
				return array("Check and Enable GZIP compression needs to be deactivated<br>This plugin has aldready Gzip feature", "error");
			}else if($this->isPluginActive('gzippy/gzippy.php')){
				return array("GZippy needs to be deactivated<br>This plugin has aldready Gzip feature", "error");
			}else if($this->isPluginActive('gzip-ninja-speed-compression/gzip-ninja-speed.php')){
				return array("GZip Ninja Speed Compression needs to be deactivated<br>This plugin has aldready Gzip feature", "error");
			}else if($this->isPluginActive('wordpress-gzip-compression/ezgz.php')){
				return array("WordPress Gzip Compression needs to be deactivated<br>This plugin has aldready Gzip feature", "error");
			}else if($this->isPluginActive('filosofo-gzip-compression/filosofo-gzip-compression.php')){
				return array("GZIP Output needs to be deactivated<br>This plugin has aldready Gzip feature", "error");
			}else if($this->isPluginActive('head-cleaner/head-cleaner.php')){
				return array("Head Cleaner needs to be deactivated", "error");
			}else if($this->isPluginActive('far-future-expiry-header/far-future-expiration.php')){
				return array("Far Future Expiration Plugin needs to be deactivated", "error");
			}else if(is_writable($path.".htaccess")){
				$htaccess = $this->insertWebp($htaccess);
				$htaccess = $this->insertLBCRule($htaccess, $post);
				$htaccess = $this->insertGzipRule($htaccess, $post);
				$htaccess = $this->insertRewriteRule($htaccess, $post);

				$htaccess = $this->to_move_gtranslate_rules($htaccess);

				file_put_contents($path.".htaccess", $htaccess);
			}else{
				return array(__("Options have been saved", 'wp-fastest-cache'), "updated");
				//return array(".htaccess is not writable", "error");
			}
			return array(__("Options have been saved", 'wp-fastest-cache'), "updated");

		}

		public function to_move_gtranslate_rules($htaccess){
			preg_match("/\#\#\#\s+BEGIN\sGTranslate\sconfig\s\#\#\#[^\#]+\#\#\#\s+END\sGTranslate\sconfig\s\#\#\#/i", $htaccess, $gtranslate);

			if(isset($gtranslate[0])){
				$htaccess = preg_replace("/\#\#\#\s+BEGIN\sGTranslate\sconfig\s\#\#\#[^\#]+\#\#\#\s+END\sGTranslate\sconfig\s\#\#\#/i", "", $htaccess);
				$htaccess = $gtranslate[0]."\n".$htaccess;
			}

			return $htaccess;
		}

		public function warningIncompatible($incompatible, $alternative = false){
			if($alternative){
				return array($incompatible." <label>needs to be deactive</label><br><label>We advise</label> <a id='alternative-plugin' target='_blank' href='".$alternative["url"]."'>".$alternative["name"]."</a>", "error");
			}else{
				return array($incompatible." <label>needs to be deactive</label>", "error");
			}
		}

		public function insertWebp($htaccess){
			if(class_exists("WpFastestCachePowerfulHtml")){
				if(defined("WPFC_DISABLE_WEBP") && WPFC_DISABLE_WEBP){
					$webp = false;
				}else{
					$webp = true;

					$cdn_values = get_option("WpFastestCacheCDN");

					if($cdn_values){
						$std_obj = json_decode($cdn_values);

						foreach($std_obj as $key => $value){
							if($value->id == "cloudflare"){
								include_once('cdn.php');
								
								CdnWPFC::cloudflare_clear_cache();
								$res = CdnWPFC::cloudflare_get_zone_id($value->cdnurl, $value->originurl);

								if($res["success"] && ($res["plan"] == "free")){
									$webp = false;
								}
								break;
							}
						}
					}
				}
			}else{
				$webp = false;
			}

							
			if($webp){
				$basename = "$1.webp";

				/* 
					This part for sub-directory installation
					WordPress Address (URL): site_url() 
					Site Address (URL): home_url()
				*/
				if(preg_match("/https?\:\/\/[^\/]+\/(.+)/", site_url(), $siteurl_base_name)){
					if(preg_match("/https?\:\/\/[^\/]+\/(.+)/", home_url(), $homeurl_base_name)){
						/*
							site_url() return http://example.com/sub-directory
							home_url() returns http://example.com/sub-directory
						*/

						$homeurl_base_name[1] = trim($homeurl_base_name[1], "/");
						$siteurl_base_name[1] = trim($siteurl_base_name[1], "/");

						if($homeurl_base_name[1] == $siteurl_base_name[1]){
							if(preg_match("/".preg_quote($homeurl_base_name[1], "/")."$/", trim(ABSPATH, "/"))){
								$basename = $homeurl_base_name[1]."/".$basename;
							}
						}else{
							if(!preg_match("/\//", $homeurl_base_name[1]) && !preg_match("/\//", $siteurl_base_name[1])){
								/*
									site_url() return http://example.com/wordpress
									home_url() returns http://example.com/blog
								*/

								$basename = $homeurl_base_name[1]."/".$basename;
								$tmp_ABSPATH = str_replace(" ", "\ ", ABSPATH);

								if(preg_match("/\/$/", $tmp_ABSPATH)){
									$tmp_ABSPATH = rtrim($tmp_ABSPATH, "/");
									$tmp_ABSPATH = dirname($tmp_ABSPATH)."/".$homeurl_base_name[1]."/";
								}
							}
						}
					}else{
						/*
							site_url() return http://example.com/sub-directory
							home_url() returns http://example.com/
						*/
						$siteurl_base_name[1] = trim($siteurl_base_name[1], "/");
						$basename = $siteurl_base_name[1]."/".$basename;
					}
				}

				if(ABSPATH == "//"){
					$RewriteCond = "RewriteCond %{DOCUMENT_ROOT}/".$basename." -f"."\n";
				}else{
					// to escape spaces
					if(!isset($tmp_ABSPATH)){
						$tmp_ABSPATH = str_replace(" ", "\ ", ABSPATH);
					}

					$RewriteCond = "RewriteCond %{DOCUMENT_ROOT}/".$basename." -f [or]"."\n";
					$RewriteCond = $RewriteCond."RewriteCond ".$tmp_ABSPATH."$1.webp -f"."\n";
				}


				$data = "# BEGIN WEBPWpFastestCache"."\n".
						"<IfModule mod_rewrite.c>"."\n".
						"RewriteEngine On"."\n".
						"RewriteCond %{HTTP_ACCEPT} image/webp"."\n".
						"RewriteCond %{REQUEST_URI} \.(jpe?g|png)"."\n".
						$RewriteCond.
						"RewriteRule ^(.*) \"/".$basename."\" [L]"."\n".
						"</IfModule>"."\n".
						"<IfModule mod_headers.c>"."\n".
						"Header append Vary Accept env=REDIRECT_accept"."\n".
						"</IfModule>"."\n".
						"AddType image/webp .webp"."\n".
						"# END WEBPWpFastestCache"."\n";

				if(!preg_match("/BEGIN\s*WEBPWpFastestCache/", $htaccess)){
					$htaccess = $data.$htaccess;
				}

				return $htaccess;
			}else{
				$htaccess = preg_replace("/#\s?BEGIN\s?WEBPWpFastestCache.*?#\s?END\s?WEBPWpFastestCache/s", "", $htaccess);
				return $htaccess;
			}
		}

		public function insertLBCRule($htaccess, $post){
			if(isset($post["wpFastestCacheLBC"]) && $post["wpFastestCacheLBC"] == "on"){


			$data = "# BEGIN LBCWpFastestCache"."\n".
					'<FilesMatch "\.(webm|ogg|mp4|ico|pdf|flv|avif|jpg|jpeg|png|gif|webp|js|css|swf|x-html|xml|woff|woff2|otf|ttf|svg|eot)(\.gz)?$">'."\n".
					'<IfModule mod_expires.c>'."\n".
					'AddType application/font-woff2 .woff2'."\n".
					'AddType application/x-font-opentype .otf'."\n".
					'ExpiresActive On'."\n".
					'ExpiresDefault A0'."\n".
					'ExpiresByType video/webm A10368000'."\n".
					'ExpiresByType video/ogg A10368000'."\n".
					'ExpiresByType video/mp4 A10368000'."\n".
					'ExpiresByType image/avif A10368000'."\n".
					'ExpiresByType image/webp A10368000'."\n".
					'ExpiresByType image/gif A10368000'."\n".
					'ExpiresByType image/png A10368000'."\n".
					'ExpiresByType image/jpg A10368000'."\n".
					'ExpiresByType image/jpeg A10368000'."\n".
					'ExpiresByType image/ico A10368000'."\n".
					'ExpiresByType image/svg+xml A10368000'."\n".
					'ExpiresByType text/css A10368000'."\n".
					'ExpiresByType text/javascript A10368000'."\n".
					'ExpiresByType application/javascript A10368000'."\n".
					'ExpiresByType application/x-javascript A10368000'."\n".
					'ExpiresByType application/font-woff2 A10368000'."\n".
					'ExpiresByType application/x-font-opentype A10368000'."\n".
					'ExpiresByType application/x-font-truetype A10368000'."\n".
					'</IfModule>'."\n".
					'<IfModule mod_headers.c>'."\n".
					'Header set Expires "max-age=A10368000, public"'."\n".
					'Header unset ETag'."\n".
					'Header set Connection keep-alive'."\n".
					'FileETag None'."\n".
					'</IfModule>'."\n".
					'</FilesMatch>'."\n".
					"# END LBCWpFastestCache"."\n";

				if(!preg_match("/BEGIN\s*LBCWpFastestCache/", $htaccess)){
					return $data.$htaccess;
				}else{
					return $htaccess;
				}
			}else{
				//delete levere browser caching
				$htaccess = preg_replace("/#\s?BEGIN\s?LBCWpFastestCache.*?#\s?END\s?LBCWpFastestCache/s", "", $htaccess);
				return $htaccess;
			}
		}

		public function insertGzipRule($htaccess, $post){
			if(isset($post["wpFastestCacheGzip"]) && $post["wpFastestCacheGzip"] == "on"){
		    	$data = "# BEGIN GzipWpFastestCache"."\n".
		          		"<IfModule mod_deflate.c>"."\n".
		          		"AddType x-font/woff .woff"."\n".
		          		"AddType x-font/ttf .ttf"."\n".
		          		"AddOutputFilterByType DEFLATE image/svg+xml"."\n".
		  				"AddOutputFilterByType DEFLATE text/plain"."\n".
		  				"AddOutputFilterByType DEFLATE text/html"."\n".
		  				"AddOutputFilterByType DEFLATE text/xml"."\n".
		  				"AddOutputFilterByType DEFLATE text/css"."\n".
		  				"AddOutputFilterByType DEFLATE text/javascript"."\n".
		  				"AddOutputFilterByType DEFLATE application/xml"."\n".
		  				"AddOutputFilterByType DEFLATE application/xhtml+xml"."\n".
		  				"AddOutputFilterByType DEFLATE application/rss+xml"."\n".
		  				"AddOutputFilterByType DEFLATE application/javascript"."\n".
		  				"AddOutputFilterByType DEFLATE application/x-javascript"."\n".
		  				"AddOutputFilterByType DEFLATE application/x-font-ttf"."\n".
		  				"AddOutputFilterByType DEFLATE x-font/ttf"."\n".
						"AddOutputFilterByType DEFLATE application/vnd.ms-fontobject"."\n".
						"AddOutputFilterByType DEFLATE font/opentype font/ttf font/eot font/otf"."\n".
		  				"</IfModule>"."\n";

				if(defined("WPFC_GZIP_FOR_COMBINED_FILES") && WPFC_GZIP_FOR_COMBINED_FILES){
					$data = $data."\n".'<FilesMatch "\d+index\.(css|js)(\.gz)?$">'."\n".
			  				"# to zip the combined css and js files"."\n\n".
							"RewriteEngine On"."\n".
							"RewriteCond %{HTTP:Accept-encoding} gzip"."\n".
							"RewriteCond %{REQUEST_FILENAME}\.gz -s"."\n".
							"RewriteRule ^(.*)\.(css|js) $1\.$2\.gz [QSA]"."\n\n".
							"# to revent double gzip and give the correct mime-type"."\n\n".
							"RewriteRule \.css\.gz$ - [T=text/css,E=no-gzip:1,E=FORCE_GZIP]"."\n".
							"RewriteRule \.js\.gz$ - [T=text/javascript,E=no-gzip:1,E=FORCE_GZIP]"."\n".
							"Header set Content-Encoding gzip env=FORCE_GZIP"."\n".
							"</FilesMatch>"."\n";
				}

				$data = $data."# END GzipWpFastestCache"."\n";

				$htaccess = preg_replace("/\s*\#\s?BEGIN\s?GzipWpFastestCache.*?#\s?END\s?GzipWpFastestCache\s*/s", "", $htaccess);
				return $data.$htaccess;

			}else{
				//delete gzip rules
				$htaccess = preg_replace("/\s*\#\s?BEGIN\s?GzipWpFastestCache.*?#\s?END\s?GzipWpFastestCache\s*/s", "", $htaccess);
				return $htaccess;
			}
		}

		public function insertRewriteRule($htaccess, $post){
			if(isset($post["wpFastestCacheStatus"]) && $post["wpFastestCacheStatus"] == "on"){
				$htaccess = preg_replace("/#\s?BEGIN\s?WpFastestCache.*?#\s?END\s?WpFastestCache/s", "", $htaccess);
				$htaccess = $this->getHtaccess().$htaccess;
			}else{
				$htaccess = preg_replace("/#\s?BEGIN\s?WpFastestCache.*?#\s?END\s?WpFastestCache/s", "", $htaccess);
				$this->deleteCache();
			}

			if(defined("WPFC_SERVE_ONLY_VIA_CACHE") && WPFC_SERVE_ONLY_VIA_CACHE){
				$htaccess = preg_replace("/#\s?BEGIN\s?WpFastestCache.*?#\s?END\s?WpFastestCache/s", "", $htaccess);
			}

			return $htaccess;
		}

		public function prefixRedirect(){
			$forceTo = "";
			
			if(defined("WPFC_DISABLE_REDIRECTION") && WPFC_DISABLE_REDIRECTION){
				return $forceTo;
			}

			if(preg_match("/^https:\/\//", home_url())){
				if(preg_match("/^https:\/\/www\./", home_url())){
					$forceTo = "\nRewriteCond %{HTTPS} =on"."\n".
					           "RewriteCond %{HTTP_HOST} ^www.".str_replace("www.", "", $_SERVER["HTTP_HOST"])."\n";
				}else{
					$forceTo = "\nRewriteCond %{HTTPS} =on"."\n".
							   "RewriteCond %{HTTP_HOST} ^".str_replace("www.", "", $_SERVER["HTTP_HOST"])."\n";
				}
			}else{
				if(preg_match("/^http:\/\/www\./", home_url())){
					$forceTo = "\nRewriteCond %{HTTP_HOST} ^".str_replace("www.", "", $_SERVER["HTTP_HOST"])."\n".
							   "RewriteRule ^(.*)$ ".preg_quote(home_url(), "/")."\/$1 [R=301,L]"."\n";
				}else{
					$forceTo = "\nRewriteCond %{HTTP_HOST} ^www.".str_replace("www.", "", $_SERVER["HTTP_HOST"])." [NC]"."\n".
							   "RewriteRule ^(.*)$ ".preg_quote(home_url(), "/")."\/$1 [R=301,L]"."\n";
				}
			}
			return $forceTo;
		}

		public function getHtaccess(){
			$mobile = "";
			$loggedInUser = "";
			$ifIsNotSecure = "";
			$trailing_slash_rule = "";
			$consent_cookie = "";

			$cache_path = '/cache/all/';


			if($this->isPluginActive('sitepress-multilingual-cms/sitepress.php')){
				$language_negotiation_type = apply_filters('wpml_setting', false, 'language_negotiation_type');

				if($language_negotiation_type == 2){
					$cache_path = '/cache/%{HTTP_HOST}/all/';
				}
			}

			if($this->isPluginActive('polylang/polylang.php') || $this->isPluginActive('polylang-pro/polylang.php')){
				$polylang_settings = get_option("polylang");

				if(isset($polylang_settings["force_lang"])){
					if($polylang_settings["force_lang"] == 2 || $polylang_settings["force_lang"] == 3){
						// The language is set from the subdomain name in pretty permalinks
						// The language is set from different domains
						$cache_path = '/cache/%{HTTP_HOST}/all/';
					}
				}
			}


			if(isset($_POST["wpFastestCacheMobile"]) && $_POST["wpFastestCacheMobile"] == "on"){
				$mobile = "RewriteCond %{HTTP_USER_AGENT} !^.*".$this->getMobileUserAgents().".*$ [NC]"."\n";

				if(isset($_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'])){
					$mobile = $mobile."RewriteCond %{HTTP_CLOUDFRONT_IS_MOBILE_VIEWER} false [NC]"."\n";
					$mobile = $mobile."RewriteCond %{HTTP_CLOUDFRONT_IS_TABLET_VIEWER} false [NC]"."\n";
				}
			}

			if(isset($_POST["wpFastestCacheLoggedInUser"]) && $_POST["wpFastestCacheLoggedInUser"] == "on"){
				$loggedInUser = "RewriteCond %{HTTP:Cookie} !wordpress_logged_in"."\n";
			}

			if(!preg_match("/^https/i", get_option("home"))){
				$ifIsNotSecure = "RewriteCond %{HTTPS} !=on";
			}

			if($this->is_trailing_slash()){
				$trailing_slash_rule = "RewriteCond %{REQUEST_URI} \/$"."\n";
			}else{
				$trailing_slash_rule = "RewriteCond %{REQUEST_URI} ![^\/]+\/$"."\n";
			}

			$data = "# BEGIN WpFastestCache"."\n".
					"# Modified Time: ".date("d-m-y G:i:s", current_time('timestamp'))."\n".
					"<IfModule mod_rewrite.c>"."\n".
					"RewriteEngine On"."\n".
					"RewriteBase /"."\n".
					$this->ruleForWpContent()."\n".
					$this->prefixRedirect().
					$this->excludeRules()."\n".
					$this->excludeAdminCookie()."\n".
					$this->http_condition_rule()."\n".
					"RewriteCond %{HTTP_USER_AGENT} !(".$this->get_excluded_useragent().")"."\n".
					"RewriteCond %{HTTP_USER_AGENT} !(WP\sFastest\sCache\sPreload(\siPhone\sMobile)?\s*Bot)"."\n".
					"RewriteCond %{REQUEST_METHOD} !POST"."\n".
					$ifIsNotSecure."\n".
					"RewriteCond %{REQUEST_URI} !(\/){2,}"."\n".
					"RewriteCond %{THE_REQUEST} !(\/){2,}"."\n".
					$trailing_slash_rule.
					"RewriteCond %{QUERY_STRING} !.+"."\n".$loggedInUser.
					$consent_cookie.
					"RewriteCond %{HTTP:Cookie} !comment_author_"."\n".
					//"RewriteCond %{HTTP:Cookie} !woocommerce_items_in_cart"."\n".
					'RewriteCond %{HTTP:Profile} !^[a-z0-9\"]+ [NC]'."\n".$mobile;
			

			if(ABSPATH == "//"){
				$data = $data."RewriteCond %{DOCUMENT_ROOT}/".WPFC_WP_CONTENT_BASENAME.$cache_path."$1/index.html -f"."\n";
			}else{
				//WARNING: If you change the following lines, you need to update webp as well
				$data = $data."RewriteCond %{DOCUMENT_ROOT}/".WPFC_WP_CONTENT_BASENAME.$cache_path."$1/index.html -f [or]"."\n";
				// to escape spaces
				$tmp_WPFC_WP_CONTENT_DIR = str_replace(" ", "\ ", WPFC_WP_CONTENT_DIR);

				$data = $data."RewriteCond ".$tmp_WPFC_WP_CONTENT_DIR.$cache_path.$this->getRewriteBase(true)."$1/index.html -f"."\n";
			}

			$data = $data.'RewriteRule ^(.*) "/'.$this->getRewriteBase().WPFC_WP_CONTENT_BASENAME.$cache_path.$this->getRewriteBase(true).'$1/index.html" [L]'."\n";
			
			//RewriteRule !/  "/wp-content/cache/all/index.html" [L]


			if(class_exists("WpFcMobileCache") && isset($this->options->wpFastestCacheMobileTheme) && $this->options->wpFastestCacheMobileTheme){
				$wpfc_mobile = new WpFcMobileCache();

				if($this->isPluginActive('wptouch/wptouch.php') || $this->isPluginActive('wptouch-pro/wptouch-pro.php')){
					$wpfc_mobile->set_wptouch(true);
				}else{
					$wpfc_mobile->set_wptouch(false);
				}

				$data = $data."\n\n\n".$wpfc_mobile->update_htaccess($data);
			}

			$data = $data."</IfModule>"."\n".
					"<FilesMatch \"index\.(html|htm)$\">"."\n".
					"AddDefaultCharset UTF-8"."\n".
					"<ifModule mod_headers.c>"."\n".
					"FileETag None"."\n".
					"Header unset ETag"."\n".
					"Header set Cache-Control \"max-age=0, no-cache, no-store, must-revalidate\""."\n".
					"Header set Pragma \"no-cache\""."\n".
					"Header set Expires \"Mon, 29 Oct 1923 20:30:00 GMT\""."\n".
					"</ifModule>"."\n".
					"</FilesMatch>"."\n".
					"# END WpFastestCache"."\n";

			if(is_multisite()){
				return "";
			}else{
				return preg_replace("/\n+/","\n", $data);
			}
		}

		public function http_condition_rule(){
			$http_host = preg_replace("/(http(s?)\:)?\/\/(www\d*\.)?/i", "", trim(home_url(), "/"));

			if(preg_match("/\//", $http_host)){
				$http_host = strstr($http_host, '/', true);
			}

			if(preg_match("/www\./", home_url())){
				$http_host = "www.".$http_host;
			}

			return "RewriteCond %{HTTP_HOST} ^".$http_host;
		}

		public function ruleForWpContent(){
			return "";
			$newContentPath = str_replace(home_url(), "", content_url());
			if(!preg_match("/wp-content/", $newContentPath)){
				$newContentPath = trim($newContentPath, "/");
				return "RewriteRule ^".$newContentPath."/cache/(.*) ".WPFC_WP_CONTENT_DIR."/cache/$1 [L]"."\n";
			}
			return "";
		}

		public function getRewriteBase($sub = ""){
			if($sub && $this->is_subdirectory_install()){
				$trimedProtocol = preg_replace("/http:\/\/|https:\/\//", "", trim(home_url(), "/"));
				$path = strstr($trimedProtocol, '/');

				if($path){
					return trim($path, "/")."/";
				}else{
					return "";
				}
			}
			
			$url = rtrim(site_url(), "/");
			preg_match("/https?:\/\/[^\/]+(.*)/", $url, $out);

			if(isset($out[1]) && $out[1]){
				$out[1] = trim($out[1], "/");

				if(preg_match("/\/".preg_quote($out[1], "/")."\//", WPFC_WP_CONTENT_DIR)){
					return $out[1]."/";
				}else{
					return "";
				}
			}else{
				return "";
			}
		}



		public function checkSuperCache($path, $htaccess){
			if($this->isPluginActive('wp-super-cache/wp-cache.php')){
				return array("WP Super Cache needs to be deactive", "error");
			}else{
				if(file_exists($path."wp-content/wp-cache-config.php")){
					@unlink($path."wp-content/wp-cache-config.php");
				}

				$message = "";
				
				if(is_file($path."wp-content/wp-cache-config.php")){
					$message .= "<br>- be sure that you removed /wp-content/wp-cache-config.php";
				}

				if(preg_match("/supercache/", $htaccess)){
					$message .= "<br>- be sure that you removed the rules of super cache from the .htaccess";
				}

				return $message ? array("WP Super Cache cannot remove its own remnants so please follow the steps below".$message, "error") : "";
			}

			return "";
		}

		public function check_htaccess(){
			$path = ABSPATH;

			if($this->is_subdirectory_install()){
				$path = $this->getABSPATH();
			}
			
			if(!is_writable($path.".htaccess") && count($_POST) > 0){
				include_once(WPFC_MAIN_PATH."templates/htaccess.html");

				$htaccess = @file_get_contents($path.".htaccess");

				if(isset($this->options->wpFastestCacheLBC)){
					$htaccess = $this->insertLBCRule($htaccess, array("wpFastestCacheLBC" => "on"));
				}
				if(isset($this->options->wpFastestCacheGzip)){
					$htaccess = $this->insertGzipRule($htaccess, array("wpFastestCacheGzip" => "on"));
				}
				if(isset($this->options->wpFastestCacheStatus)){
					$htaccess = $this->insertRewriteRule($htaccess, array("wpFastestCacheStatus" => "on"));
				}
				
				$htaccess = preg_replace("/\n+/","\n", $htaccess);

				echo "<noscript id='wpfc-htaccess-data'>".esc_html($htaccess)."</noscript>";
				echo "<noscript id='wpfc-htaccess-path-data'>".esc_html($path).".htaccess"."</noscript>";
				?>
				<script type="text/javascript">
					jQuery(document).ready(function(){
						Wpfc_New_Dialog.dialog("wpfc-modal-htaccess", {close: "default"}, function(modal){
							jQuery("#" + modal.id).find("label.mm-input-label").html(jQuery("#wpfc-htaccess-path-data").html());
							jQuery("#" + modal.id).find("textarea.wiz-inp-readonly-textarea").html(jQuery("#wpfc-htaccess-data").html());
						});
					});
				</script>
				<?php
			}
		}

		public function optionsPage(){
			$wpFastestCacheCombineCss = isset($this->options->wpFastestCacheCombineCss) ? 'checked="checked"' : "";
			$wpFastestCacheGoogleFonts = isset($this->options->wpFastestCacheGoogleFonts) ? 'checked="checked"' : "";
			$wpFastestCacheGzip = isset($this->options->wpFastestCacheGzip) ? 'checked="checked"' : "";
			$wpFastestCacheCombineJs = isset($this->options->wpFastestCacheCombineJs) ? 'checked="checked"' : "";
			$wpFastestCacheCombineJsPowerFul = isset($this->options->wpFastestCacheCombineJsPowerFul) ? 'checked="checked"' : "";
			$wpFastestCacheDisableEmojis = isset($this->options->wpFastestCacheDisableEmojis) ? 'checked="checked"' : "";

			$wpFastestCacheRenderBlocking = isset($this->options->wpFastestCacheRenderBlocking) ? 'checked="checked"' : "";
			
			$wpFastestCacheRenderBlockingCss = isset($this->options->wpFastestCacheRenderBlockingCss) ? 'checked="checked"' : "";

			$wpFastestCacheDelayJS = isset($this->options->wpFastestCacheDelayJS) ? 'checked="checked"' : "";

			$wpFastestCacheLanguage = isset($this->options->wpFastestCacheLanguage) ? $this->options->wpFastestCacheLanguage : "eng";
			

			$wpFastestCacheLazyLoad = isset($this->options->wpFastestCacheLazyLoad) ? 'checked="checked"' : "";
			$wpFastestCacheLazyLoad_keywords = isset($this->options->wpFastestCacheLazyLoad_keywords) ? $this->options->wpFastestCacheLazyLoad_keywords : "";
			$wpFastestCacheLazyLoad_placeholder = isset($this->options->wpFastestCacheLazyLoad_placeholder) ? $this->options->wpFastestCacheLazyLoad_placeholder : "default";
			$wpFastestCacheLazyLoad_exclude_full_size_img = isset($this->options->wpFastestCacheLazyLoad_exclude_full_size_img) ? 'checked="checked"' : "";


			$wpFastestCacheLBC = isset($this->options->wpFastestCacheLBC) ? 'checked="checked"' : "";
			$wpFastestCacheLoggedInUser = isset($this->options->wpFastestCacheLoggedInUser) ? 'checked="checked"' : "";
			$wpFastestCacheMinifyCss = isset($this->options->wpFastestCacheMinifyCss) ? 'checked="checked"' : "";

			$wpFastestCacheMinifyCssPowerFul = isset($this->options->wpFastestCacheMinifyCssPowerFul) ? 'checked="checked"' : "";


			$wpFastestCacheMinifyHtml = isset($this->options->wpFastestCacheMinifyHtml) ? 'checked="checked"' : "";
			$wpFastestCacheMinifyHtmlPowerFul = isset($this->options->wpFastestCacheMinifyHtmlPowerFul) ? 'checked="checked"' : "";

			$wpFastestCacheMinifyJs = isset($this->options->wpFastestCacheMinifyJs) ? 'checked="checked"' : "";

			$wpFastestCacheMobile = isset($this->options->wpFastestCacheMobile) ? 'checked="checked"' : "";
			$wpFastestCacheMobileTheme = isset($this->options->wpFastestCacheMobileTheme) ? 'checked="checked"' : "";
			$wpFastestCacheMobileTheme_themename = isset($this->options->wpFastestCacheMobileTheme_themename) ? $this->options->wpFastestCacheMobileTheme_themename : "";

			$wpFastestCacheNewPost = isset($this->options->wpFastestCacheNewPost) ? 'checked="checked"' : "";
			
			$wpFastestCacheRemoveComments = isset($this->options->wpFastestCacheRemoveComments) ? 'checked="checked"' : "";


			$wpFastestCachePreload = isset($this->options->wpFastestCachePreload) ? 'checked="checked"' : "";
			$wpFastestCachePreload_homepage = isset($this->options->wpFastestCachePreload_homepage) ? 'checked="checked"' : "";
			$wpFastestCachePreload_post = isset($this->options->wpFastestCachePreload_post) ? 'checked="checked"' : "";
			$wpFastestCachePreload_category = isset($this->options->wpFastestCachePreload_category) ? 'checked="checked"' : "";
			$wpFastestCachePreload_customposttypes = isset($this->options->wpFastestCachePreload_customposttypes) ? 'checked="checked"' : "";
			$wpFastestCachePreload_customTaxonomies = isset($this->options->wpFastestCachePreload_customTaxonomies) ? 'checked="checked"' : "";
			$wpFastestCachePreload_page = isset($this->options->wpFastestCachePreload_page) ? 'checked="checked"' : "";
			$wpFastestCachePreload_tag = isset($this->options->wpFastestCachePreload_tag) ? 'checked="checked"' : "";
			$wpFastestCachePreload_attachment = isset($this->options->wpFastestCachePreload_attachment) ? 'checked="checked"' : "";
			$wpFastestCachePreload_number = isset($this->options->wpFastestCachePreload_number) ? esc_attr($this->options->wpFastestCachePreload_number) : 4;
			$wpFastestCachePreload_restart = isset($this->options->wpFastestCachePreload_restart) ? 'checked="checked"' : "";
			$wpFastestCachePreload_order = isset($this->options->wpFastestCachePreload_order) ? esc_attr($this->options->wpFastestCachePreload_order) : "";
			$wpFastestCachePreload_sitemap = isset($this->options->wpFastestCachePreload_sitemap) ? esc_attr($this->options->wpFastestCachePreload_sitemap) : "";




			$wpFastestCacheStatus = isset($this->options->wpFastestCacheStatus) ? 'checked="checked"' : "";
			$wpFastestCacheTimeOut = isset($this->cronJobSettings["period"]) ? $this->cronJobSettings["period"] : "";

			$wpFastestCacheUpdatePost = isset($this->options->wpFastestCacheUpdatePost) ? 'checked="checked"' : "";
			$wpFastestCacheWidgetCache = isset($this->options->wpFastestCacheWidgetCache) ? 'checked="checked"' : "";
			?>
			
			<div class="wrap">

				<h2><?php _e('WP Fastest Cache Options', 'wp-fastest-cache'); ?></h2>
				
				<?php settings_errors("wpfc-notice"); ?>

				<div class="tabGroup">
					<?php
						$tabs = array();
						
						array_push($tabs, array("id"=>"wpfc-options","title" => __("Settings", "wp-fastest-cache" )));
						array_push($tabs, array("id"=>"wpfc-deleteCache","title" => __("Delete Cache", "wp-fastest-cache" )));
						array_push($tabs, array("id"=>"wpfc-imageOptimisation","title" => __("Image Optimization", "wp-fastest-cache" )));

						if(!class_exists("WpFastestCachePowerfulHtml")){
							array_push($tabs, array("id"=>"wpfc-premium","title"=>"Premium"));
						}

						array_push($tabs, array("id"=>"wpfc-exclude","title"=>__("Exclude", "wp-fastest-cache" )));
						array_push($tabs, array("id"=>"wpfc-cdn","title"=>"CDN"));
						array_push($tabs, array("id"=>"wpfc-db","title"=>"DB"));

						foreach ($tabs as $key => $value){
							$checked = "";

							//tab of "delete css and js" has been removed so there is need to check it
							if(isset($_POST["wpFastestCachePage"]) && $_POST["wpFastestCachePage"] && $_POST["wpFastestCachePage"] == "deleteCssAndJsCache"){
								$_POST["wpFastestCachePage"] = "deleteCache";
							}

							if(!isset($_POST["wpFastestCachePage"]) && $value["id"] == "wpfc-options"){
								$checked = ' checked="checked" ';
							}else if((isset($_POST["wpFastestCachePage"])) && ("wpfc-".$_POST["wpFastestCachePage"] == $value["id"])){
								$checked = ' checked="checked" ';
							}
							echo '<input '.esc_html($checked).' type="radio" id="'.esc_html($value["id"]).'" name="tabGroup1" style="display:none;">'."\n";
							echo '<label for="'.esc_html($value["id"]).'">'.esc_html($value["title"]).'</label>'."\n";
						}
					?>
				    <br>
				    <div class="tab1" style="padding-left:10px;">
						<form method="post" name="wp_manager" action="options.php">
							<?php settings_fields( 'wpfc-group' ); ?>

							<input type="hidden" value="options" name="wpFastestCachePage">
							<div class="questionCon">
								<div class="question"><?php _e('Cache System', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheStatus; ?> id="wpFastestCacheStatus" name="wpFastestCacheStatus"><label for="wpFastestCacheStatus"><?php _e("Enable", "wp-fastest-cache"); ?></label></div>
							</div>

							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?>
								<?php if(file_exists(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/library/widget-cache.php")){ ?>
									<?php include_once WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/library/widget-cache.php"; ?>

									<?php if(class_exists("WpfcWidgetCache") && method_exists("WpfcWidgetCache", "add_filter_admin")){ ?>
										<div class="questionCon">
											<div class="question"><?php _e('Widget Cache', 'wp-fastest-cache'); ?></div>
											<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheWidgetCache; ?> id="wpFastestCacheWidgetCache" name="wpFastestCacheWidgetCache"><label for="wpFastestCacheWidgetCache"><?php _e("Reduce the number of SQL queries", "wp-fastest-cache"); ?></label></div>
											<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/widget-cache-reduce-the-number-of-sql-queries/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
										</div>
									<?php }else{ ?>
										<div class="questionCon update-needed">
											<div class="question"><?php _e('Widget Cache', 'wp-fastest-cache'); ?></div>
											<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheWidgetCache; ?> id="wpFastestCacheWidgetCache"><label for="wpFastestCacheWidgetCache"><?php _e("Reduce the number of SQL queries", "wp-fastest-cache"); ?></label></div>
											<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/widget-cache-reduce-the-number-of-sql-queries/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
										</div>
									<?php } ?>
								<?php }else{ ?>
									<div class="questionCon update-needed">
										<div class="question"><?php _e('Widget Cache', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheWidgetCache; ?> id="wpFastestCacheWidgetCache"><label for="wpFastestCacheWidgetCache"><?php _e("Reduce the number of SQL queries", "wp-fastest-cache"); ?></label></div>
										<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/widget-cache-reduce-the-number-of-sql-queries/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
									</div>
								<?php } ?>
							<?php }else{ ?>
								<div class="questionCon disabled">
									<div class="question"><?php _e('Widget Cache', 'wp-fastest-cache'); ?></div>
									<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheWidgetCache; ?> id="wpFastestCacheWidgetCache"><label for="wpFastestCacheWidgetCache"><?php _e("Reduce the number of SQL queries", "wp-fastest-cache"); ?></label></div>
									<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/widget-cache-reduce-the-number-of-sql-queries/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
								</div>
							<?php } ?>



							<div class="questionCon">
								<div class="question"><?php _e('Preload', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCachePreload; ?> id="wpFastestCachePreload" name="wpFastestCachePreload"><label for="wpFastestCachePreload"><?php _e("Create the cache of all the site automatically", "wp-fastest-cache"); ?></label></div>
								

								<div class="get-info" data-info-id="wpFastestCachePreload" style="<?php echo $wpFastestCachePreload ? "display:none;" : "" ?>" ><a target="_blank" href="http://www.wpfastestcache.com/features/preload-settings/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>

								<div class="get-info" data-gear-id="wpFastestCachePreload" style="<?php echo $wpFastestCachePreload ? "" : "display:none;" ?>" >
									<span class="dashicons dashicons-admin-generic"></span>
								</div>
								
								<script type="text/javascript">
									jQuery(document).ready(function() {
										jQuery("#wpFastestCachePreload").change(function(e){
											let id = jQuery(this).attr("id");

											if(jQuery(this).is(':checked')){
												jQuery("div[data-info-id='" + id + "']").hide();
												jQuery("div[data-gear-id='" + id + "']").show();
											}else{
												jQuery("div[data-info-id='" + id + "']").show();
												jQuery("div[data-gear-id='" + id + "']").hide();
											}
										});
									});
								</script>

							</div>

							<?php include(WPFC_MAIN_PATH."templates/update_now.php"); ?>

							<?php include(WPFC_MAIN_PATH."templates/preload.php"); ?>

							<div class="questionCon">
								<div class="question"><?php _e('Logged-in Users', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheLoggedInUser; ?> id="wpFastestCacheLoggedInUser" name="wpFastestCacheLoggedInUser"><label for="wpFastestCacheLoggedInUser"><?php _e("Don't show the cached version for logged-in users", "wp-fastest-cache"); ?></label></div>
							</div>

							<div class="questionCon">
								<div class="question"><?php _e('Mobile', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheMobile; ?> id="wpFastestCacheMobile" name="wpFastestCacheMobile"><label for="wpFastestCacheMobile"><?php _e("Don't show the cached version for desktop to mobile devices", "wp-fastest-cache"); ?></label></div>
							</div>

							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?>
							<div class="questionCon">
								<div class="question"><?php _e('Mobile Theme', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheMobileTheme; ?> id="wpFastestCacheMobileTheme" name="wpFastestCacheMobileTheme"><label for="wpFastestCacheMobileTheme"><?php _e("Create cache for mobile theme", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/mobile-cache/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>

							<?php 
								$tester_arr_mobile = array(
									"tr-TR",
									"tr",
									"berkatan.com",
									"yenihobiler.com",
									"hobiblogu.com",
									"canliradyodinle.life",
									"canlitvturk.org",
									"haftahaftahamilelik.gen.tr",
									"tooxclusive.com",
									"canliradyodinle.fm"
									);

								if(in_array(get_bloginfo('language'), $tester_arr_mobile) || in_array(str_replace("www.", "", $_SERVER["HTTP_HOST"]), $tester_arr_mobile)){
									include_once WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/mobile_theme.php";
								}
							?>
							
							<?php }else{ ?>
							<div class="questionCon disabled">
								<div class="question"><?php _e('Mobile Theme', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" id="wpFastestCacheMobileTheme"><label for="wpFastestCacheMobileTheme"><?php _e("Create cache for mobile theme", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/mobile-cache/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>
							<?php } ?>

							<div class="questionCon">
								<div class="question"><?php _e('New Post', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheNewPost; ?> id="wpFastestCacheNewPost" name="wpFastestCacheNewPost"><label for="wpFastestCacheNewPost"><?php _e("Clear cache files when a post or page is published", "wp-fastest-cache"); ?></label></div>
							</div>

							<?php include(WPFC_MAIN_PATH."templates/newpost.php"); ?>

							<div class="questionCon">
								<div class="question"><?php _e('Update Post', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheUpdatePost; ?> id="wpFastestCacheUpdatePost" name="wpFastestCacheUpdatePost"><label for="wpFastestCacheUpdatePost"><?php _e("Clear cache files when a post or page is updated", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/tutorial/to-clear-cache-after-update"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>

							<?php include(WPFC_MAIN_PATH."templates/updatepost.php"); ?>


							<div class="questionCon">
								<div class="question"><?php _e('Minify HTML', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheMinifyHtml; ?> id="wpFastestCacheMinifyHtml" name="wpFastestCacheMinifyHtml"><label for="wpFastestCacheMinifyHtml"><?php _e("You can decrease the size of page", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/optimization/minify-html/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>

							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?>
							<div class="questionCon">
								<div class="question"><?php _e('Minify HTML Plus', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheMinifyHtmlPowerFul; ?> id="wpFastestCacheMinifyHtmlPowerFul" name="wpFastestCacheMinifyHtmlPowerFul"><label for="wpFastestCacheMinifyHtmlPowerFul"><?php _e("More powerful minify html", "wp-fastest-cache"); ?></label></div>
							</div>
							<?php }else{ ?>
							<div class="questionCon disabled">
								<div class="question"><?php _e('Minify HTML Plus', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" id="wpFastestCacheMinifyHtmlPowerFul"><label for="wpFastestCacheMinifyHtmlPowerFul"><?php _e("More powerful minify html", "wp-fastest-cache"); ?></label></div>
							</div>
							<?php } ?>



							<div class="questionCon">
								<div class="question"><?php _e('Minify Css', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheMinifyCss; ?> id="wpFastestCacheMinifyCss" name="wpFastestCacheMinifyCss"><label for="wpFastestCacheMinifyCss"><?php _e("You can decrease the size of css files", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/optimization/minify-css/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>



							<?php if(class_exists("WpFastestCachePowerfulHtml") && method_exists("WpFastestCachePowerfulHtml", "minify_css")){ ?>
							<div class="questionCon">
								<div class="question"><?php _e('Minify Css Plus', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheMinifyCssPowerFul; ?> id="wpFastestCacheMinifyCssPowerFul" name="wpFastestCacheMinifyCssPowerFul"><label for="wpFastestCacheMinifyCssPowerFul"><?php _e("More powerful minify css", "wp-fastest-cache"); ?></label></div>
							</div>
							<?php }else{ ?>
							<div class="questionCon disabled">
								<div class="question"><?php _e('Minify Css Plus', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" id="wpFastestCacheMinifyCssPowerFul"><label for="wpFastestCacheMinifyCssPowerFul"><?php _e("More powerful minify css", "wp-fastest-cache"); ?></label></div>
							</div>
							<?php } ?>


							<div class="questionCon">
								<div class="question"><?php _e('Combine Css', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheCombineCss; ?> id="wpFastestCacheCombineCss" name="wpFastestCacheCombineCss"><label for="wpFastestCacheCombineCss"><?php _e("Reduce HTTP requests through combined css files", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/optimization/combine-js-css-files/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>

							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?>
								<?php if(method_exists("WpFastestCachePowerfulHtml", "minify_js_in_body")){ ?>
									<div class="questionCon">
										<div class="question"><?php _e('Minify Js', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheMinifyJs; ?> id="wpFastestCacheMinifyJs" name="wpFastestCacheMinifyJs"><label for="wpFastestCacheMinifyJs"><?php _e("You can decrease the size of js files", "wp-fastest-cache"); ?></label></div>
									</div>
								<?php }else{ ?>
									<div class="questionCon update-needed">
										<div class="question"><?php _e('Minify Js', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" id="wpFastestCacheMinifyJs"><label for="wpFastestCacheMinifyJs"><?php _e("You can decrease the size of js files", "wp-fastest-cache"); ?></label></div>
									</div>
								<?php } ?>
							<?php }else{ ?>
							<div class="questionCon disabled">
								<div class="question"><?php _e('Minify Js', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" id="wpFastestCacheMinifyJs"><label for="wpFastestCacheMinifyJs"><?php _e("You can decrease the size of js files", "wp-fastest-cache"); ?></label></div>
							</div>
							<?php } ?>

							<div class="questionCon">
								<div class="question"><?php _e('Combine Js', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheCombineJs; ?> id="wpFastestCacheCombineJs" name="wpFastestCacheCombineJs"><label for="wpFastestCacheCombineJs"><?php _e("Reduce HTTP requests through combined js files", "wp-fastest-cache"); ?></label> <b style="color:red;">(header)</b></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/optimization/combine-js-css-files/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>

							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?> 
								<?php if(method_exists("WpFastestCachePowerfulHtml", "combine_js_in_footer")){ ?>
									<div class="questionCon"> <div class="question"><?php _e('Combine Js Plus', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheCombineJsPowerFul; ?> id="wpFastestCacheCombineJsPowerFul" name="wpFastestCacheCombineJsPowerFul">
											<label for="wpFastestCacheCombineJsPowerFul"><?php _e("Reduce HTTP requests through combined js files", "wp-fastest-cache"); ?></label> <b style="color:red;">(footer)</b>
										</div> 
									</div>
								<?php }else{ ?>
									<div class="questionCon update-needed">
										<div class="question"><?php _e('Combine Js Plus', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" id="wpFastestCacheCombineJsPowerFul"><label for="wpFastestCacheCombineJsPowerFul"><?php _e("Reduce HTTP requests through combined js files", "wp-fastest-cache"); ?></label> <b style="color:red;">(footer)</b></div> 
									</div> 
								<?php } ?>
							<?php }else{ ?>
								<div class="questionCon disabled">
									<div class="question"><?php _e('Combine Js Plus', 'wp-fastest-cache'); ?></div>
									<div class="inputCon"><input type="checkbox" id="wpFastestCacheCombineJsPowerFul"><label for="wpFastestCacheCombineJsPowerFul"><?php _e("Reduce HTTP requests through combined js files", "wp-fastest-cache"); ?></label> <b style="color:red;">(footer)</b></div>
								</div>
							<?php } ?>

							<div class="questionCon">
								<div class="question"><?php _e('Gzip', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheGzip; ?> id="wpFastestCacheGzip" name="wpFastestCacheGzip"><label for="wpFastestCacheGzip"><?php _e("Reduce the size of files sent from your server", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="https://www.wpfastestcache.com/tutorial/how-to-enable-gzip-compression-in-wordpress/#advantage"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>

							<?php
								if(isset($_SERVER["SERVER_SOFTWARE"]) && $_SERVER["SERVER_SOFTWARE"] && preg_match("/nginx/i", $_SERVER["SERVER_SOFTWARE"])){
									include_once(WPFC_MAIN_PATH."templates/nginx_gzip.php"); 
								}
							?>

							<div class="questionCon">
								<div class="question"><?php _e('Browser Caching', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheLBC; ?> id="wpFastestCacheLBC" name="wpFastestCacheLBC"><label for="wpFastestCacheLBC"><?php _e("Reduce page load times for repeat visitors", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/optimization/leverage-browser-caching/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>

							<div class="questionCon">
								<div class="question"><?php _e('Disable Emojis', 'wp-fastest-cache'); ?></div>
								<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheDisableEmojis; ?> id="wpFastestCacheDisableEmojis" name="wpFastestCacheDisableEmojis"><label for="wpFastestCacheDisableEmojis"><?php _e("You can remove the emoji inline css and wp-emoji-release.min.js", "wp-fastest-cache"); ?></label></div>
								<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/optimization/disableremove-wordpress-emojis/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
							</div>


							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?> 
								<?php if(method_exists("WpFastestCachePowerfulHtml", "render_blocking")){ ?>
									<div class="questionCon">
										<div class="question"><?php _e('Render Blocking Js', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheRenderBlocking; ?> id="wpFastestCacheRenderBlocking" name="wpFastestCacheRenderBlocking"><label for="wpFastestCacheRenderBlocking"><?php _e("Eliminate render-blocking JavaScript resources", "wp-fastest-cache"); ?></label></div>
										<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/render-blocking-js/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
									</div>
								<?php }else{ ?>
									<div class="questionCon update-needed">
										<div class="question"><?php _e('Render Blocking Js', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" id="wpFastestCacheRenderBlocking" name="wpFastestCacheRenderBlocking"><label for="wpFastestCacheRenderBlocking"><?php _e("Eliminate render-blocking JavaScript resources", "wp-fastest-cache"); ?></label></div>
										<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/render-blocking-js/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
									</div>
								<?php } ?>
							<?php }else{ ?>
								<div class="questionCon disabled">
									<div class="question"><?php _e('Render Blocking Js', 'wp-fastest-cache'); ?></div>
									<div class="inputCon"><input type="checkbox" id="wpFastestCacheRenderBlocking" name="wpFastestCacheRenderBlocking"><label for="wpFastestCacheRenderBlocking"><?php _e("Eliminate render-blocking JavaScript resources", "wp-fastest-cache"); ?></label></div>
									<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/render-blocking-js/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
								</div>
							<?php } ?>





							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?> 
								<?php if(method_exists("WpFastestCachePowerfulHtml", "google_fonts")){ ?>
									<div class="questionCon">
										<div class="question"><?php _e('Google Fonts', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheGoogleFonts; ?> id="wpFastestCacheGoogleFonts" name="wpFastestCacheGoogleFonts"><label for="wpFastestCacheGoogleFonts"><?php _e("Load Google Fonts asynchronously", "wp-fastest-cache"); ?></label></div>
										<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/google-fonts-optimize-css-delivery/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
									</div>
								<?php }else{ ?>
									<div class="questionCon update-needed">
										<div class="question"><?php _e('Google Fonts', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" id="wpFastestCacheGoogleFonts" name="wpFastestCacheGoogleFonts"><label for="wpFastestCacheGoogleFonts"><?php _e("Load Google Fonts asynchronously", "wp-fastest-cache"); ?></label></div>
										<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/google-fonts-optimize-css-delivery/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
									</div>
								<?php } ?>
							<?php }else{ ?>
								<div class="questionCon disabled">
									<div class="question"><?php _e('Google Fonts', 'wp-fastest-cache'); ?></div>
									<div class="inputCon"><input type="checkbox" id="wpFastestCacheGoogleFonts" name="wpFastestCacheGoogleFonts"><label for="wpFastestCacheGoogleFonts"><?php _e("Load Google Fonts asynchronously", "wp-fastest-cache"); ?></label></div>
									<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/google-fonts-optimize-css-delivery/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
								</div>
							<?php } ?>



							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?>
								<?php if(method_exists("WpFastestCachePowerfulHtml", "lazy_load")){ ?>
									<div class="questionCon">
										<div class="question">Lazy Load</div>
										<div class="inputCon">
											<input type="hidden" value="<?php echo $wpFastestCacheLazyLoad_placeholder; ?>" id="wpFastestCacheLazyLoad_placeholder" name="wpFastestCacheLazyLoad_placeholder">
											<input type="hidden" value="<?php echo $wpFastestCacheLazyLoad_keywords; ?>" id="wpFastestCacheLazyLoad_keywords" name="wpFastestCacheLazyLoad_keywords">
											<input style="display: none;" type="checkbox" <?php echo $wpFastestCacheLazyLoad_exclude_full_size_img; ?>  id="wpFastestCacheLazyLoad_exclude_full_size_img" name="wpFastestCacheLazyLoad_exclude_full_size_img">
											
											<input type="checkbox" <?php echo $wpFastestCacheLazyLoad; ?> id="wpFastestCacheLazyLoad" name="wpFastestCacheLazyLoad"><label for="wpFastestCacheLazyLoad"><?php _e("Load images and iframes when they enter the browsers viewport", "wp-fastest-cache"); ?></label>
										</div>


										<div class="get-info" data-info-id="wpFastestCacheLazyLoad" style="<?php echo $wpFastestCacheLazyLoad ? "display:none;" : "" ?>" ><a target="_blank" href="http://www.wpfastestcache.com/premium/lazy-load-reduce-http-request-and-page-load-time/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>

										<div class="get-info" data-gear-id="wpFastestCacheLazyLoad" style="<?php echo $wpFastestCacheLazyLoad ? "" : "display:none;" ?>" >
											<span class="dashicons dashicons-admin-generic"></span>
										</div>

									</div>

									<?php 
										if(file_exists(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/lazy-load.php")){
											include_once WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/lazy-load.php"; 
										}
									?>

								<?php } ?>
							<?php }else{ ?>
								<div class="questionCon disabled">
									<div class="question">Lazy Load</div>
									<div class="inputCon"><input type="checkbox" id="wpFastestCacheLazyLoad" name="wpFastestCacheLazyLoad"><label for="wpFastestCacheLazyLoad"><?php _e("Load images and iframes when they enter the browsers viewport", "wp-fastest-cache"); ?></label></div>
									<div class="get-info"><a target="_blank" href="http://www.wpfastestcache.com/premium/lazy-load-reduce-http-request-and-page-load-time/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
								</div>
							<?php } ?>



							<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?> 
								<?php if(file_exists(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/library/delay-js.php")){ ?>
									<div class="questionCon">
										<div class="question"><?php _e('Delay Js', 'wp-fastest-cache'); ?></div>
										<div class="inputCon"><input type="checkbox" <?php echo $wpFastestCacheDelayJS; ?> id="wpFastestCacheDelayJS" name="wpFastestCacheDelayJS"><label for="wpFastestCacheDelayJS"><?php _e("Some js sources will not be loaded until scrolling or moving the mouse", "wp-fastest-cache"); ?></label></div>
										<div class="get-info"><a target="_blank" href="https://www.wpfastestcache.com/premium/delay-javascript/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
									</div>
								<?php } ?>
							<?php }else{ ?>
								<div class="questionCon disabled">
									<div class="question"><?php _e('Delay Js', 'wp-fastest-cache'); ?></div>
									<div class="inputCon"><input type="checkbox" id="wpFastestCacheDelayJS" name="wpFastestCacheDelayJS"><label for="wpFastestCacheDelayJS"><?php _e("Some js sources will not be loaded until scrolling or moving the mouse", "wp-fastest-cache"); ?></label></div>
									<div class="get-info"><a target="_blank" href="https://www.wpfastestcache.com/premium/delay-javascript/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>" /></a></div>
								</div>
							<?php } ?>



							<div class="questionCon">
								<div class="question">Language</div>
								<div class="inputCon">
									<select id="wpFastestCacheLanguage" name="wpFastestCacheLanguage" style="width: 100px !important;">
										<?php
											$lang_array = array (
															  'id_ID' => 'Bahasa Indonesia',
															  'de_DE' => 'Deutsch',
															  'en_US' => 'English',
															  'en_ZA' => 'English (South Africa)',
															  'en_GB' => 'English (UK)',
															  'es_ES' => 'Español',
															  'es_AR' => 'Español (Argentine)',
															  'es_CO' => 'Español (Colombia)',
															  'es_EC' => 'Español (Ecuador)',
															  'es_MX' => 'Español (México)',
															  'es_VE' => 'Español (Venezuela)',
															  'fr_FR' => 'Français',
															  'gl_ES' => 'Galego',
															  'it_IT' => 'Italiano',
															  'hu_HU' => 'Magyar',
															  'nl_NL' => 'Nederlands',
															  'nl_BE' => 'Nederlands (België)',
															  'sk_SK' => 'Slovenčina',
															  'sl_SI' => 'Slovenščina',
															  'fi' => 'Suomi',
															  'sv_SE' => 'Svenska',
															  'tr_TR' => 'Türkçe',
															  'cs_CZ' => 'Čeština',
															  'ru_RU' => 'Русский',
															  'fa_IR' => 'فارسی',
															  'zh_CN' => '中文（中国大陆）',
															  'zh_TW' => '中文（台灣）',
															  'ja' => '日本語',
															  'ko_KR' => '한국어 (한국)'
															);

											foreach($lang_array as $lang_array_key => $lang_array_value){
												$option_selected = "";

												if(isset($this->options->wpFastestCacheLanguage)){
													if($this->options->wpFastestCacheLanguage == $lang_array_key){
														$option_selected = 'selected="selected"';
													}
												}else{
													if($lang_array_key == "en_US" || $lang_array_key == "en_EN"){
														$option_selected = 'selected="selected"';
													}
												}

												echo '<option '.$option_selected.' value="'.$lang_array_key.'">'.$lang_array_value.'</option>';
											}
										?>
									</select> 
								</div>
							</div>
							<div class="questionCon qsubmit">
								<div class="submit" style="float: none !important;"><input type="submit" value="Submit" class="button-primary"></div>
							</div>
						</form>
				    </div>
				    <div class="tab2">
				    	<div id="container-show-hide-logs" style="display:none; float:right; padding-right:20px; cursor:pointer;">
				    		<span id="show-delete-log">Show Logs</span>
				    		<span id="hide-delete-log" style="display:none;">Hide Logs</span>
				    	</div>

				    	<?php 
			   				if(class_exists("WpFastestCacheStatics")){
				   				$cache_statics = new WpFastestCacheStatics();
				   				$cache_statics->statics();
			   				}else{
			   					?>
					   			<div style="z-index:9999;width: 160px; height: 60px; position: absolute; margin-left: 254px; margin-top: 25px; color: white;">
						    		<div style="font-family:sans-serif;font-size:13px;text-align: center; border-radius: 5px; float: left; background-color: rgb(51, 51, 51); color: white; width: 147px; padding: 20px 50px;">
						    			<label><?php _e("Only available in Premium version", "wp-fastest-cache"); ?></label>
						    		</div>
						    	</div>
					   			<div style="opacity:0.3;float: right; padding-right: 20px; cursor: pointer;">
						    		<span id="show-delete-log">Show Logs</span>
						    		<span id="hide-delete-log" style="display:none;">Hide Logs</span>
						    	</div>
						    	<h2 style="opacity:0.3;padding-left:20px;padding-bottom:10px;"><?php _e("Cache Statistics", "wp-fastest-cache"); ?></h2>
						    	<div id="wpfc-cache-statics" style="opacity:0.3;width:100%;float:right;margin:15px 0;">
									<style type="text/css">
										#wpfc-cache-statics > div{
											float: left;
											width: 24%;
											text-align: center;
										}
										#wpfc-cache-statics > div > p{
											font-size: 1.3em;
											font-weight: 600;
											margin-top: 10px;
										}
										#wpfc-cache-statics-desktop, #wpfc-cache-statics-mobile, #wpfc-cache-statics-css {
											border-right: 1px solid #ddd;
										}
									</style>
									<div id="wpfc-cache-statics-desktop" style="margin-left:1%;">
										<i class="flaticon-desktop1"></i> 
										<p id="wpfc-cache-statics-desktop-data">12.3Kb / 1 Items</p>
									</div>
									<div id="wpfc-cache-statics-mobile">
										<i class="flaticon-smart"></i> 
										<p id="wpfc-cache-statics-mobile-data">12.4Kb / 1 Items</p>
									</div>
									<div id="wpfc-cache-statics-css">
										<i class="flaticon-css4"></i> 
										<p id="wpfc-cache-statics-css-data">278.2Kb / 9 Items</p>
									</div>
									<div id="wpfc-cache-statics-js">
										<i class="flaticon-js"></i> 
										<p id="wpfc-cache-statics-js-data">338.4Kb / 16 Items</p>
									</div>
								</div>
			   					<?php
			   				}
				   		?>

				   		<div class="exclude_section_clear" style=" margin-left: 3%; width: 95%; margin-bottom: 20px; margin-top: 0;"><div></div></div>

				   		<h2 id="delete-cache-h2" style="padding-left:20px;padding-bottom:10px;"><?php _e("Delete Cache", "wp-fastest-cache"); ?></h2>

				   		<?php //include_once(WPFC_MAIN_PATH."templates/cache_path.php"); ?>

				    	<form method="post" name="wp_manager" class="delete-line" action="options.php">
							<?php settings_fields( 'wpfc-group' ); ?>
				    		<input type="hidden" value="deleteCache" name="wpFastestCachePage">
				    		<div class="questionCon qsubmit left">
				    			<div class="submit"><input type="submit" value="<?php _e("Clear All Cache", "wp-fastest-cache"); ?>" class="button-primary"></div>
				    		</div>
				    		<div class="questionCon right">
				    			<div style="padding-left:11px;">
				    			<label><?php _e("You can delete all cache files", "wp-fastest-cache"); ?></label><br>
				    			<label><?php _e("Target folder", "wp-fastest-cache"); ?></label> <b><?php echo $this->getWpContentDir("/cache/all"); ?></b>
				    			</div>
				    		</div>
				   		</form>
				   		<form method="post" name="wp_manager" class="delete-line" style="height: 120px;" action="options.php">
				   			<?php settings_fields( 'wpfc-group' ); ?>
				    		<input type="hidden" value="deleteCssAndJsCache" name="wpFastestCachePage">
				    		<div class="questionCon qsubmit left">
				    			<div class="submit"><input type="submit" value="<?php _e("Clear Cache and Minified CSS/JS", "wp-fastest-cache"); ?>" class="button-primary"></div>
				    		</div>
				    		<div class="questionCon right">
				    			<div style="padding-left:11px;">
				    			<label><?php _e("If you modify any css file, you have to delete minified css files", "wp-fastest-cache"); ?></label><br>
				    			<label><?php _e("All cache files will be removed as well", "wp-fastest-cache"); ?></label><br>
				    			<label><?php _e("Target folder", "wp-fastest-cache"); ?></label> <b><?php echo $this->getWpContentDir("/cache/all"); ?></b><br>
				    			<label><?php _e("Target folder", "wp-fastest-cache"); ?></label> <b><?php echo $this->getWpContentDir("/cache/wpfc-minified"); ?></b>
				    			</div>
				    		</div>
				   		</form>
				   		<?php 
				   				if(class_exists("WpFastestCacheLogs")){
					   				$logs = new WpFastestCacheLogs("delete");
					   				$logs->printLogs();
				   				}
				   		?>

				   		<div class="exclude_section_clear" style=" margin-left: 3%; width: 95%; margin-bottom: 12px; margin-top: 0;"><div></div></div>


				   		<h2 style="padding-bottom:10px;padding-left:20px;float:left;"><?php _e("Timeout Rules", "wp-fastest-cache"); ?></h2>

				    	<!-- samples start: clones -->
				    	<div class="wpfc-timeout-rule-line" style="display:none;">
							<div class="wpfc-timeout-rule-line-left">
								<select name="wpfc-timeout-rule-prefix">
										<option selected="" value=""></option>
										<option value="all"><?php _e("All", "wp-fastest-cache"); ?></option>
										<option value="homepage"><?php _e("Home Page", "wp-fastest-cache"); ?></option>
										<option value="startwith"><?php _e("Starts With", "wp-fastest-cache"); ?></option>
										<option value="contain"><?php _e("Contains", "wp-fastest-cache"); ?></option>
										<option value="exact"><?php _e("Is Equal To", "wp-fastest-cache"); ?></option>

										<option value="regex">Regular Expression</option>
								</select>
							</div>
							<div class="wpfc-timeout-rule-line-middle">
								<input type="text" name="wpfc-timeout-rule-content">
								<input type="text" name="wpfc-timeout-rule-schedule">
								<input type="text" name="wpfc-timeout-rule-hour">
								<input type="text" name="wpfc-timeout-rule-minute">
							</div>
						</div>
						<!-- item sample -->
	    				<div class="wpfc-timeout-item" tabindex="1" prefix="" content="" schedule="" style="position: relative;display:none;">
	    					<div class="app">
				    			<div class="wpfc-timeout-item-form-title">Title M</div>
				    			<span class="wpfc-timeout-item-details wpfc-timeout-item-url"></span>
	    					</div>
			    		</div>
		    			<!-- samples end -->

				    	<div style="float:left;margin-top:-37px;padding-left:628px;">
				    		<?php
				    			$disable_wp_cron = '';
				    			if(defined("DISABLE_WP_CRON")){
						    		if((is_bool(DISABLE_WP_CRON) && DISABLE_WP_CRON == true) || 
						    			(is_string(DISABLE_WP_CRON) && preg_match("/^true$/i", DISABLE_WP_CRON))){
						    			$disable_wp_cron = 'disable-wp-cron="true" ';

						    			include(WPFC_MAIN_PATH."templates/disable_wp_cron.php");
						    		}
						    	}
				    		?>
				    		<button type="button" <?php echo $disable_wp_cron;?> class="wpfc-add-new-timeout-button wpfc-dialog-buttons" style="display: inline-block;padding: 4px 10px;">
				    			<span><?php _e("Add New Rule", "wp-fastest-cache"); ?></span>
							</button>
				    	</div>

				    	<div class="wpfc-timeout-list" style="display: block;width:98%;float:left;">

				    	</div>

				    	<?php
				    		include(WPFC_MAIN_PATH."templates/timeout.php");
				    	?>

				    	<form method="post" name="wp_manager">
				    		<input type="hidden" value="timeout" name="wpFastestCachePage">
				    		<div class="wpfc-timeout-rule-container"></div>
				    	</form>
				    	<script type="text/javascript">

					    	<?php
					    		$schedules_rules = array();
						    	$crons = _get_cron_array();

						    	foreach ((array)$crons as $cron_key => $cron_value) {
						    		foreach ( (array) $cron_value as $hook => $events ) {
						    			if(preg_match("/^wp\_fastest\_cache(.*)/", $hook, $id)){
						    				if(!$id[1] || preg_match("/^\_(\d+)$/", $id[1])){
							    				foreach ( (array) $events as $event_key => $event ) {
							    					$tmp_array = array();

							    					if($id[1]){
							    						// new cronjob which is (wp_fastest_cache_d+)
								    					$tmp_std = json_decode($event["args"][0]);

								    					$tmp_array = array("schedule" => $event["schedule"],
								    									   "prefix" => $tmp_std->prefix,
								    									   "content" => esc_attr($tmp_std->content));

								    					if(isset($tmp_std->hour) && isset($tmp_std->minute)){
								    						$tmp_array["hour"] = $tmp_std->hour;
								    						$tmp_array["minute"] = $tmp_std->minute;
								    					}
							    					}else{
							    						// old cronjob which is (wp_fastest_cache)
							    						$tmp_array = array("schedule" => $event["schedule"],
								    									   "prefix" => "all",
								    									   "content" => "all");
							    					}
							    				}

							    				array_push($schedules_rules, $tmp_array);
						    				}
						    			}
						    		}
						    	}

					    		echo "WpFcTimeout.schedules = ".json_encode($this->cron_add_minute(array())).";";

					    		if(count($schedules_rules) > 0){
					    			echo "WpFcTimeout.init(".json_encode($schedules_rules).");";
					    		}else{
					    			echo "WpFcTimeout.init();";
					    		} ?>
				    	</script>



				    	<div class="exclude_section_clear" style=" margin-left: 3%; width: 95%; margin-bottom: 12px; margin-top: 0;"><div></div></div>

				    	<h2 style="padding-bottom:10px;padding-left:20px;float:left;"><?php _e("Clearing Specific Pages", "wp-fastest-cache"); ?></h2>

				    	<div style="float:left;margin-top:-37px;padding-left:628px;">
				    		<button type="button" <?php echo $disable_wp_cron;?> class="wpfc-add-new-csp-button wpfc-dialog-buttons" style="display: inline-block;padding: 4px 10px;">
				    			<span><?php _e("Add New Rule", "wp-fastest-cache"); ?></span>
							</button>
				    	</div>

				    	<div class="wpfc-csp-list" style="display: block;width:98%;float:left;">

				    	</div>

				    	<?php
				    		include(WPFC_MAIN_PATH."templates/clearing_specific_pages.php");
				    	?>




				    	<div class="exclude_section_clear" style=" margin-left: 3%; width: 95%; margin-bottom: 12px; margin-top: 0;"><div></div></div>

				    	<h2 style="padding-bottom:10px;padding-left:20px;float:left;">Reverse Proxy Cache</h2>

				    	<div class="varnish-cache-list" style="display: block;width:98%;float:left;">
				    		<div class="int-item int-item-left" style="width: 94%;margin-left: 20px;">
			    				<img style="border-radius: 50px;" src="<?php echo plugins_url("wp-fastest-cache/images/varnish.jpg"); ?>">
			    				<div class="app">
			    					<div style="font-weight:bold;font-size:14px;">Varnish Cache</div>
			    					<p>Varnish Cache is a web application accelerator also known as a caching HTTP reverse proxy.</p>
			    				</div>
			    				<div class="meta <?php echo $this->wpfc_status_varnish(); ?>"></div>
				    		</div>
				    	</div>

				    	<?php
				    		include(WPFC_MAIN_PATH."templates/varnish.php");
				    	?>

	







				    </div>


				    
				    <div class="tab3" style="display:none;"> </div>




				    <?php if(class_exists("WpFastestCacheImageOptimisation")){ ?>
					    <div class="tab4">
					    	<h2 style="padding-left:20px;padding-bottom:10px;"><?php _e("Optimize Image Tool", "wp-fastest-cache"); ?></h2>

					    		<?php $xxx = new WpFastestCacheImageOptimisation(); ?>
					    		<?php $xxx->statics(); ?>
						    	<?php $xxx->imageList(); ?>
					    </div>
				    <?php }else{ ?>
						<div class="tab4" style="">
							<?php include(WPFC_MAIN_PATH."templates/sample_img_list.html"); ?> 
						</div>
				    <?php } ?>
				    <div class="tab5">

						<div id="wpfc-premium">
				            <style>
				                #wpfc-premium .transition { transition-duration: 0.3s; }
				                #wpfc-premium * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; border: 0 solid; outline: 0 solid; }
				                #wpfc-premium .w-full { width: 100%; }
				                #wpfc-premium .h-full { height: 100%; }
				                #wpfc-premium .flex { display: flex; }
				                #wpfc-premium .items-center { align-items: center; }
				                #wpfc-premium .justify-center { justify-content: center; }
				                #wpfc-premium .w-10 { width: 2.5rem; }
				                #wpfc-premium .h-10 { height: 2.5rem; }
				                #wpfc-premium .bg-brand-blue { background-color: #0A1551; }
				                #wpfc-premium .pricingBox-ribbon-belt { align-items: flex-start; display: flex; left: 0; overflow: hidden; position: absolute; right: 0; text-align: center; top: -8px }
				                #wpfc-premium .pricingBox-ribbon-belt .pricingBox-ribbon-shadow { display: inline-block; font-size: 12px; font-weight: 900; line-height: 16px; margin-left: auto; margin-right: auto; max-width: calc(100% - 24px); padding: 0; position: relative; text-transform: uppercase; top: 0; -webkit-transform: none; transform: none }
				                #wpfc-premium .pricingBox-ribbon-belt .pricingBox-ribbon-shadow .pricingBox-ribbon-content { display: flex; padding: 0 0 4px; position: relative }
				                #wpfc-premium .pricingBox-ribbon-belt .pricingBox-ribbon-shadow .pricingBox-ribbon-content>.text-content { background: #ff7321; border: 4px solid #ff7321; margin: 0 21px; max-height: 40px; overflow: hidden; position: relative; z-index: 2 }
				                #wpfc-premium .pricingBox-ribbon-belt .pricingBox-ribbon-shadow .pricingBox-ribbon-content>.triangle-behind { border-bottom: 8px solid #d54000; border-left: 8px solid transparent; border-right: 8px solid transparent; height: 8px; margin-left: -8px; position: absolute; width: calc(100% + 16px); z-index: 0 }
				                #wpfc-premium .pricingBox-ribbon-belt .pricingBox-ribbon-shadow .pricingBox-ribbon-content>.triangle-sides-shadow { border-bottom: 0; border-left: 16px solid transparent; border-right: 16px solid transparent; border-top: 36px solid rgba(0,0,0,.25); height: 8px; margin-left: 0; margin-top: 8px; position: absolute; width: 100%; z-index: 0 }
				                #wpfc-premium .pricingBox-ribbon-belt .pricingBox-ribbon-shadow .pricingBox-ribbon-content>.triangle-sides { background: transparent; bottom: 4px; display: block; left: 0; overflow: hidden; position: absolute; right: 0; top: 0; z-index: 1 }
				                #wpfc-premium .pricingBox-ribbon-belt .pricingBox-ribbon-shadow .pricingBox-ribbon-content>.triangle-sides:after { border-right: 21px solid transparent; border-top: 50px solid #ff7321; content: ""; height: 0; position: absolute; right: 0; width: 0 }
				                #wpfc-premium .pricingBox-ribbon-belt .pricingBox-ribbon-shadow .pricingBox-ribbon-content>.triangle-sides:before { border-left: 21px solid transparent; border-top: 50px solid #ff7321; content: ""; height: 0; left: 0; position: absolute; width: 0 }
				                #wpfc-premium * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; box-sizing: border-box; outline: 0 }
				                #wpfc-premium{ font-family: Circular,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol }
				                #wpfc-premium{ -webkit-text-size-adjust: 100%; line-height: 1.15; -moz-tab-size: 4; -o-tab-size: 4; tab-size: 4 }
				                #wpfc-premium{ margin: 0 }
				                #wpfc-premium b, #wpfc-premium strong { font-weight: bolder }
				                #wpfc-premium fieldset, #wpfc-premium ol, #wpfc-premium ul { margin: 0; padding: 0 }
				                #wpfc-premium ol, #wpfc-premium ul { list-style: none }
				                #wpfc-premium [role=button], #wpfc-premium button { cursor: pointer }
				                /*#wpfc-premium .cursor-pointer { cursor: pointer }*/
				                #wpfc-premium .font-circular { font-family: Circular,sans-serif }
				                #wpfc-premium .absolute { position: absolute }
				                #wpfc-premium .relative { position: relative }
				                #wpfc-premium .text-center { text-align: center }
				                #wpfc-premium .text-left { text-align: left }
				                #wpfc-premium .pt-1, #wpfc-premium .py-1 { padding-top: .25rem }
				                #wpfc-premium .py-2 { padding-bottom: .5rem }
				                #wpfc-premium .pt-2, #wpfc-premium .py-2 { padding-top: .5rem }
				                #wpfc-premium .p-3 { padding: .75rem }
				                #wpfc-premium .px-3 { padding-left: .75rem; padding-right: .75rem }
				                #wpfc-premium .py-3 { padding-bottom: .75rem }
				                #wpfc-premium .pt-3, #wpfc-premium .py-3 { padding-top: .75rem }
				                #wpfc-premium .pt-4, #wpfc-premium .py-4 { padding-top: 1rem }
				                #wpfc-premium .space-y-1>*+* { margin-top: .25rem }
				                #wpfc-premium .text-xs { font-size: .75rem; line-height: 1rem }
				                #wpfc-premium .text-sm { font-size: .875rem; line-height: 1.125rem }
				                #wpfc-premium .text-md { font-size: 1rem; line-height: 1.25rem }
				                #wpfc-premium .text-lg { font-size: 1.125rem; line-height: 1.375rem }
				                #wpfc-premium .text-xl { font-size: 1.25rem; line-height: 1.5rem }
				                #wpfc-premium .text-2xl { font-size: 1.5rem; line-height: 1.75rem }
				                #wpfc-premium .text-3xl { font-size: 1.75rem; line-height: 2rem }
				                #wpfc-premium .text-6xl { font-size: 2.5rem; line-height: 2.875rem }
				                #wpfc-premium .line-height-xs { line-height: 1rem }
				                #wpfc-premium .line-height-md { line-height: 1.25rem }
				                #wpfc-premium .line-height-4xl { line-height: 2.5rem }
				                #wpfc-premium .justify-center { justify-content: center }
				                #wpfc-premium .items-center { align-items: center }
				                #wpfc-premium .items-end { align-items: flex-end }
				                #wpfc-premium .min-h-32 { min-height: 6rem }
				                #wpfc-premium .max-w-72 { max-width: 18rem }
				                #wpfc-premium .max-w-10\/12, #wpfc-premium .max-w-5\/6 { max-width: 83.333333% }
				                #wpfc-premium .gap-4 { gap: 1rem }
				                #wpfc-premium .flex-col { flex-direction: column }
				                #wpfc-premium .flex-1 { flex-grow: 1; flex-shrink: 1 }
				                #wpfc-premium .flex-wrap { flex-wrap: wrap }
				                #wpfc-premium .bg-red-300 { --bg-opacity: 1; background-color: rgba(248,113,113,var(--bg-opacity)) }
				                #wpfc-premium .inline-block { display: inline-block }
				                #wpfc-premium .block { display: block }
				                #wpfc-premium .flex { display: flex }
				                #wpfc-premium .font-normal { font-weight: 400 }
				                #wpfc-premium .font-medium { font-weight: 500 }
				                #wpfc-premium .font-bold { font-weight: 700 }
				                #wpfc-premium .font-extrabold { font-weight: 800 }
				                #wpfc-premium .h-1 { height: .25rem }
				                #wpfc-premium .h-6 { height: 1.5rem }
				                #wpfc-premium .w-6 { width: 1.5rem }
				                #wpfc-premium .w-7 { width: 1.75rem }
				                #wpfc-premium .h-8 { height: 2rem }
				                #wpfc-premium .h-10 { height: 2.5rem }
				                #wpfc-premium .w-10 { width: 2.5rem }
				                #wpfc-premium .w-11 { width: 2.75rem }
				                #wpfc-premium .h-12 { height: 3rem }
				                #wpfc-premium .h-full { height: 100% }
				                #wpfc-premium .w-full { width: 100% }
				                #wpfc-premium .left-0 { left: 0 }
				                #wpfc-premium .right-0 { right: 0 }
				                #wpfc-premium .top-4 { top: 1rem }
				                #wpfc-premium .top-4\/12 { top: 33.333333% }
				                #wpfc-premium .radius { border-radius: .25rem }
				                #wpfc-premium .radius-lg { border-radius: .5rem }
				                #wpfc-premium .radius-t-lg, #wpfc-premium .radius-tl-lg { border-top-left-radius: .5rem }
				                #wpfc-premium .radius-tr-lg { border-top-right-radius: .5rem }
				                #wpfc-premium .radius-b-lg, #wpfc-premium .radius-br-lg { border-bottom-right-radius: .5rem }
				                #wpfc-premium .radius-bl-lg, #wpfc-premium .radius-l-lg { border-bottom-left-radius: .5rem }
				                #wpfc-premium .mr-1 { margin-right: .25rem }
				                #wpfc-premium .mx-2 { margin-left: .25rem; margin-right: .25rem }
				                #wpfc-premium .mt-2 { margin-top: .5rem }
				                #wpfc-premium .my-3 { margin-bottom: .75rem; margin-top: .75rem }
				                #wpfc-premium .mt-4 { margin-top: 1rem }
				                #wpfc-premium .mb-4 { margin-bottom: 1rem }
				                #wpfc-premium .mx-auto { margin-left: auto; margin-right: auto }
				                #wpfc-premium .hover\:opacity-70:hover {opacity: .7;}
				                @media only screen and (min-width: 30rem) { #wpfc-premium .xs\:px-2 { padding-left:.5rem; padding-right: .5rem } #wpfc-premium .xs\:px-3 { padding-left: .75rem; padding-right: .75rem } #wpfc-premium .xs\:px-4 { padding-left: 1rem; padding-right: 1rem } #wpfc-premium .xs\:gap-0 { gap: 0 } #wpfc-premium .xs\:inline { display: inline } #wpfc-premium .xs\:w-44 { width: 11rem } #wpfc-premium .xs\:w-auto { width: auto } #wpfc-premium .xs\:w-max { width: -moz-max-content; width: max-content } #wpfc-premium .xs\:ml-0 { margin-left: 0 } #wpfc-premium .xs\:mt-0 { margin-top: 0 } #wpfc-premium .xs\:mb-0 { margin-bottom: 0 } #wpfc-premium .xs\:mr-2 { margin-right: .5rem } }
				                @media only screen and (min-width: 48rem) { #wpfc-premium .md\:border-b { border-bottom-width:1px } #wpfc-premium .md\:p-0 { padding: 0 } #wpfc-premium .md\:px-2 { padding-left: .5rem; padding-right: .5rem } #wpfc-premium .md\:p-3 { padding: .75rem } #wpfc-premium .md\:px-3 { padding-left: .75rem; padding-right: .75rem } #wpfc-premium .md\:py-3 { padding-bottom: .75rem; padding-top: .75rem } #wpfc-premium .md\:pl-3 { padding-left: .75rem } #wpfc-premium .md\:pr-3 { padding-right: .75rem } #wpfc-premium .md\:px-4 { padding-left: 1rem; padding-right: 1rem } #wpfc-premium .md\:pt-4 { padding-top: 1rem } #wpfc-premium .md\:pl-4 { padding-left: 1rem } #wpfc-premium .md\:pr-4 { padding-right: 1rem } #wpfc-premium .md\:px-8 { padding-left: 2rem; padding-right: 2rem } #wpfc-premium .md\:px-10 { padding-left: 2.5rem; padding-right: 2.5rem } #wpfc-premium .md\:pb-10 { padding-bottom: 2.5rem } #wpfc-premium .md\:pt-12 { padding-top: 3rem } #wpfc-premium .md\:pr-20 { padding-right: 5rem } #wpfc-premium .md\:pb-40 { padding-bottom: 10rem } #wpfc-premium .md\:text-md { font-size: 1rem; line-height: 1.25rem } #wpfc-premium .md\:text-2xl { font-size: 1.5rem; line-height: 1.75rem } #wpfc-premium .md\:text-3xl { font-size: 1.75rem; line-height: 2rem } #wpfc-premium .md\:line-height-xl { line-height: 1.5rem } #wpfc-premium .md\:line-height-3xl { line-height: 2rem } #wpfc-premium .md\:justify-start { justify-content: flex-start } #wpfc-premium .md\:items-center { align-items: center } #wpfc-premium .md\:items-start { align-items: flex-start } #wpfc-premium .md\:min-h-16 { min-height: 4rem } #wpfc-premium .md\:min-w-40 { min-width: 10rem } #wpfc-premium .md\:min-w-76 { min-width: 19rem } #wpfc-premium .md\:min-h-120 { min-height: 30rem } #wpfc-premium .md\:min-w-md { min-width: 48rem } #wpfc-premium .md\:max-w-84 { max-width: 21rem } #wpfc-premium .md\:max-w-92 { max-width: 23rem } #wpfc-premium .md\:max-w-md { max-width: 48rem } #wpfc-premium .md\:max-w-40vw { max-width: 40vw } #wpfc-premium .md\:cols-4 { grid-template-columns: repeat(4,minmax(0,1fr)) } #wpfc-premium .md\:gap-0 { gap: 0 } #wpfc-premium .md\:flex-row { flex-direction: row } #wpfc-premium .md\:flex-col { flex-direction: column } #wpfc-premium .md\:grow-0 { flex-grow: 0 } #wpfc-premium .md\:flex-nowrap { flex-wrap: nowrap } #wpfc-premium .md\:inline-block { display: inline-block } #wpfc-premium .md\:block { display: block } #wpfc-premium .md\:flex { display: flex } #wpfc-premium .md\:inline { display: inline } #wpfc-premium .md\:shadow-lg { box-shadow: 0 4px 6px -2px #1018280d,0 12px 16px -4px #1018281a } #wpfc-premium .md\:opacity-100 { opacity: 1 } #wpfc-premium .md\:font-bold { font-weight: 700 } #wpfc-premium .md\:w-52 { width: 13rem } #wpfc-premium .md\:w-80 { width: 20rem } #wpfc-premium .md\:w-120 { width: 30rem } #wpfc-premium .md\:h-auto { height: auto } #wpfc-premium .md\:w-auto { width: auto } #wpfc-premium .md\:w-2\/6 { width: 33.333333% } #wpfc-premium .md\:w-4\/6 { width: 66.666667% } #wpfc-premium .md\:basis-0 { flex-basis: 0 } #wpfc-premium .md\:basis-80 { flex-basis: 20rem } #wpfc-premium .md\:basis-92 { flex-basis: 23rem } #wpfc-premium .md\:radius-t-none { border-top-left-radius: 0 } #wpfc-premium .md\:radius-r-none, #wpfc-premium .md\:radius-t-none, #wpfc-premium .md\:radius-tr-none { border-top-right-radius: 0 } #wpfc-premium .md\:radius-r-none { border-bottom-right-radius: 0 } #wpfc-premium .md\:radius-bl-none { border-bottom-left-radius: 0 } #wpfc-premium .md\:radius-l { border-bottom-left-radius: .25rem; border-top-left-radius: .25rem } #wpfc-premium .md\:radius-r-md, #wpfc-premium .md\:radius-tr-md { border-top-right-radius: .375rem } #wpfc-premium .md\:radius-r-md { border-bottom-right-radius: .375rem } #wpfc-premium .md\:radius-bl-md, #wpfc-premium .md\:radius-l-md { border-bottom-left-radius: .375rem } #wpfc-premium .md\:radius-l-md { border-top-left-radius: .375rem } #wpfc-premium .md\:radius-tl-lg { border-top-left-radius: .5rem } #wpfc-premium .md\:radius-tr-lg { border-top-right-radius: .5rem } #wpfc-premium .md\:radius-br-lg { border-bottom-right-radius: .5rem } #wpfc-premium .md\:radius-bl-lg { border-bottom-left-radius: .5rem } #wpfc-premium .md\:ml-0 { margin-left: 0 } #wpfc-premium .md\:mr-0 { margin-right: 0 } #wpfc-premium .md\:mt-0 { margin-top: 0 } #wpfc-premium .md\:mb-0 { margin-bottom: 0 } #wpfc-premium .md\:mb-2 { margin-bottom: .5rem } #wpfc-premium .md\:mb-4 { margin-bottom: 1rem } #wpfc-premium .md\:mb-5 { margin-bottom: 1.25rem } #wpfc-premium .md\:ml-6 { margin-left: 1.5rem } #wpfc-premium .md\:mb-6 { margin-bottom: 1.5rem } #wpfc-premium .md\:mb-7 { margin-bottom: 1.75rem } #wpfc-premium .md\:ml-10 { margin-left: 2.5rem } #wpfc-premium .md\:mr-10 { margin-right: 2.5rem } #wpfc-premium .md\:mb-12 { margin-bottom: 3rem } #wpfc-premium .md\:mb-20 { margin-bottom: 5rem } #wpfc-premium .md\:ml-56 { margin-left: 14rem } #wpfc-premium .md\:ml-auto { margin-left: auto } #wpfc-premium .md\:mr-auto { margin-right: auto } }
				                @media only screen and (min-width: 80rem) { #wpfc-premium .xl\:py-0 { padding-bottom:0; padding-top: 0 } #wpfc-premium .xl\:px-2 { padding-left: .5rem; padding-right: .5rem } #wpfc-premium .xl\:px-3 { padding-left: .75rem; padding-right: .75rem } #wpfc-premium .xl\:px-4 { padding-left: 1rem; padding-right: 1rem } #wpfc-premium .xl\:px-14 { padding-left: 3.5rem; padding-right: 3.5rem } #wpfc-premium .xl\:px-32 { padding-left: 8rem; padding-right: 8rem } #wpfc-premium .xl\:max-w-sm { max-width: 40rem } #wpfc-premium .xl\:gap-0 { gap: 0 } #wpfc-premium .xl\:flex-nowrap { flex-wrap: nowrap } #wpfc-premium .xl\:block { display: block } #wpfc-premium .xl\:inline { display: inline } #wpfc-premium .xl\:w-auto { width: auto } #wpfc-premium .xl\:radius { border-radius: .25rem } }
				            </style>
				            <ul class="flex flex-wrap xl:flex-nowrap justify-center gap-4 md:gap-0 w-full mt-2">
				                <li class="w-full max-w-72 radius-lg xs:w-44 mx-2 mt-6"> <div class="text-center w-full h-full flex flex-col cursor-pointer" aria-label="BRONZE"> <div class="w-full"> <div class="relative flex justify-center items-center w-full font-bold radius-tl-lg radius-tr-lg text-xl" style="background-color: rgb(255, 96, 56); color: rgb(255, 255, 255);"> <span class="my-3">Bronze</span> </div> <div class="flex flex-col justify-center items-center w-full text-sm min-h-32" style="background-color: rgb(243, 243, 254); color: rgb(10, 21, 81);"> <span class="text-xl"> <div class="flex flex-col">  <div class="text-md py-2"> <div class="flex items-end"> <span class="text-sm">$</span> <strong class="font-extrabold flex items-end text-6xl line-height-4xl"> 49 <span class="flex flex-col text-left"> <strong class="font-bold text-xl line-height-md">.99</strong> <span class="text-sm font-normal line-height-xs" style="color: rgb(69, 78, 128);">/lifetime</span> </span> </strong> </div> </div> </div> </span>  </div> </div> <div class="w-full p-3" data-test-id="jf-pt-plan-cta" style="background-color: rgb(243, 243, 254);"> <form action="https://www.wpfastestcache.com/#buy" method="post"> <button class="flex justify-center items-center w-full font-bold transition hover:opacity-70 radius max-w-10/12 mx-auto h-10" style="background-color: rgb(255, 96, 56); color: rgb(255, 255, 255);">Buy</button></form> </div> <div class="w-full flex-1 radius-bl-lg radius-br-lg px-3" style="background: rgb(227, 229, 245); color: rgb(52, 60, 106);"> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg">1 License</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Number of Licenses</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg">1,000</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Image Credits per License</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong style="font-size:30px;" class="font-bold block flex justify-center items-center mx-2 text-lg">∞</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Support and Updates</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/dollar-gold.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">One-Time Fee</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/left-right.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">1 year license transfer right</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/refund.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">30 Day Money Back Guarantee</span> </span> </div>       </div> </div> </li>
				                <li class="w-full max-w-72 radius-lg xs:w-44 mx-2 mt-6"> <div class="text-center w-full h-full flex flex-col cursor-pointer" aria-label="SILVER"> <div class="w-full"> <div class="relative flex justify-center items-center w-full font-bold radius-tl-lg radius-tr-lg text-xl" style="background-color: rgb(46, 105, 255); color: rgb(255, 255, 255);"> <span class="my-3">Silver</span>  </div> <div class="flex flex-col justify-center items-center w-full text-sm min-h-32" style="background-color: rgb(243, 243, 254); color: rgb(10, 21, 81);"> <span class="text-xl"> <div class="flex flex-col">  <div class="text-md py-2"> <div class="flex items-end"> <span class="text-sm">$</span> <strong class="font-extrabold flex items-end text-6xl line-height-4xl"> 125 <span class="flex flex-col text-left"> <strong class="font-bold text-xl line-height-md">.00</strong> <span class="text-sm font-normal line-height-xs" style="color: rgb(69, 78, 128);">/lifetime</span> </span> </strong> </div> </div> </div> </span>  </div> </div> <div class="w-full p-3" data-test-id="jf-pt-plan-cta" style="background-color: rgb(243, 243, 254);"> <form action="https://www.wpfastestcache.com/#buy" method="post"> <button class="flex justify-center items-center w-full font-bold transition hover:opacity-70 radius max-w-10/12 mx-auto h-10" style="background-color: rgb(46, 105, 255); color: rgb(255, 255, 255);">Buy</button></form> </div> <div class="w-full flex-1 radius-bl-lg radius-br-lg px-3" style="background: rgb(227, 229, 245); color: rgb(52, 60, 106);"> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg">3 Licenses</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Number of Licenses</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg">1,000</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Image Credits per License</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong style="font-size:30px;" class="font-bold block flex justify-center items-center mx-2 text-lg">∞</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Support and Updates</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/dollar-gold.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">One-Time Fee</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/left-right.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">1 year license transfer right</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/refund.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">30 Day Money Back Guarantee</span> </span> </div>       </div> </div> </li>
				                <li class="w-full max-w-72 radius-lg xs:w-44 mx-2 mt-6"> <div class="text-center w-full h-full flex flex-col cursor-pointer" aria-label="GOLD"> <div class="w-full"> <div class="relative flex justify-center items-center w-full font-bold radius-tl-lg radius-tr-lg text-xl" style="background-color: rgb(255, 184, 40); color: rgb(43, 50, 69);"> <span class="my-3">Gold</span> </div> <div class="flex flex-col justify-center items-center w-full text-sm min-h-32" style="background-color: rgb(243, 243, 254); color: rgb(10, 21, 81);"> <span class="text-xl"> <div class="flex flex-col">  <div class="text-md py-2"> <div class="flex items-end"> <span class="text-sm">$</span> <strong class="font-extrabold flex items-end text-6xl line-height-4xl"> 175 <span class="flex flex-col text-left"> <strong class="font-bold text-xl line-height-md">.00</strong> <span class="text-sm font-normal line-height-xs" style="color: rgb(69, 78, 128);">/lifetime</span> </span> </strong> </div> </div> </div> </span>  </div> </div> <div class="w-full p-3" data-test-id="jf-pt-plan-cta" style="background-color: rgb(243, 243, 254);"> <form action="https://www.wpfastestcache.com/#buy" method="post"> <button class="flex justify-center items-center w-full font-bold transition hover:opacity-70 radius max-w-10/12 mx-auto h-10" style="background-color: rgb(255, 184, 40); color: rgb(43, 50, 69);">Buy</button></form> </div> <div class="w-full flex-1 radius-bl-lg radius-br-lg px-3" style="background: rgb(227, 229, 245); color: rgb(52, 60, 106);"> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg">5 Licenses</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Number of Licenses</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg">1,000</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Image Credits per License</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong style="font-size:30px;" class="font-bold block flex justify-center items-center mx-2 text-lg">∞</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Support and Updates</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/dollar-gold.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">One-Time Fee</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/left-right.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">1 year license transfer right</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/refund.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">30 Day Money Back Guarantee</span> </span> </div>       </div> </div> </li>
				                <li class="w-full max-w-72 radius-lg xs:w-44 mx-2 mt-6"> <div class="text-center w-full h-full flex flex-col cursor-pointer" aria-label="GOLD"> <div class="w-full"> <div class="relative flex justify-center items-center w-full font-bold radius-tl-lg radius-tr-lg text-xl" style="background-color: #9661f9; color: rgb(255, 255, 255);"> <span class="my-3">Platinum</span> </div> <div class="flex flex-col justify-center items-center w-full text-sm min-h-32" style="background-color: rgb(243, 243, 254); color: rgb(10, 21, 81);"> <span class="text-xl"> <div class="flex flex-col">  <div class="text-md py-2"> <div class="flex items-end"> <span class="text-sm">$</span> <strong class="font-extrabold flex items-end text-6xl line-height-4xl"> 300 <span class="flex flex-col text-left"> <strong class="font-bold text-xl line-height-md">.00</strong> <span class="text-sm font-normal line-height-xs" style="color: rgb(69, 78, 128);">/lifetime</span> </span> </strong> </div> </div> </div> </span>  </div> </div> <div class="w-full p-3" data-test-id="jf-pt-plan-cta" style="background-color: rgb(243, 243, 254);"> <form action="https://www.wpfastestcache.com/#buy" method="post"> <button class="flex justify-center items-center w-full font-bold transition hover:opacity-70 radius max-w-10/12 mx-auto h-10" style="background-color: #9661f9; color: rgb(255, 255, 255);">Buy</button></form> </div> <div class="w-full flex-1 radius-bl-lg radius-br-lg px-3" style="background: rgb(227, 229, 245); color: rgb(52, 60, 106);"> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg">10 Licenses</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Number of Licenses</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg">1,000</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Image Credits per License</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong style="font-size:30px;" class="font-bold block flex justify-center items-center mx-2 text-lg">∞</strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">Support and Updates</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/dollar-gold.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">One-Time Fee</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/left-right.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">1 year license transfer right</span> </span> </div> <div class="relative flex justify-center items-center text-sm py-3" style="border-bottom: 1px solid rgb(200, 206, 237);"> <span class="flex flex-col space-y-1"> <strong class="font-bold block flex justify-center items-center mx-2 text-lg"> <img src="<?php echo plugins_url("wp-fastest-cache/images/refund.png"); ?>" width="40" height="40" /> </strong> <span class="font-medium text-xs" style="color: rgb(69, 78, 128);">30 Day Money Back Guarantee</span> </span> </div>       </div> </div> </li>
				            </ul>
				        </div>

				    </div>
				    <div class="tab6" style="padding-left:20px;">
				    	<!-- samples start: clones -->
				    	<div class="wpfc-exclude-rule-line" style="display:none;">
							<div class="wpfc-exclude-rule-line-left">
								<select name="wpfc-exclude-rule-prefix">
										<option selected="" value=""></option>
										<option value="homepage"><?php _e("Home Page", "wp-fastest-cache"); ?></option>
										<option value="category"><?php _e("Categories", "wp-fastest-cache"); ?></option>
										<option value="tag"><?php _e("Tags", "wp-fastest-cache"); ?></option>
										<option value="archive"><?php _e("Archives", "wp-fastest-cache"); ?></option>
										<option value="post"><?php _e("Posts", "wp-fastest-cache"); ?></option>
										<option value="page"><?php _e("Pages", "wp-fastest-cache"); ?></option>
										<option value="attachment"><?php _e("Attachments", "wp-fastest-cache"); ?></option>
										<option value="startwith"><?php _e("Starts With", "wp-fastest-cache"); ?></option>
										<option value="contain"><?php _e("Contains", "wp-fastest-cache"); ?></option>
										<option value="exact"><?php _e("Is Equal To", "wp-fastest-cache"); ?></option>

										<option value="regex">Regular Expression</option>

										<option value="googleanalytics"><?php _e("has Google Analytics Parameters", "wp-fastest-cache"); ?></option>
										<option value="yandexclickid"><?php _e("has Yandex Click ID Parameters", "wp-fastest-cache"); ?></option>
										<option value="woocommerce_items_in_cart"><?php _e("has Woocommerce Items in Cart", "wp-fastest-cache"); ?></option>
								</select>
							</div>
							<div class="wpfc-exclude-rule-line-middle">
								<input type="text" name="wpfc-exclude-rule-content" style="width:390px;">
								<input type="text" name="wpfc-exclude-rule-type" style="width:90px;">
							</div>
						</div>
						<!-- item sample -->
	    				<div class="wpfc-exclude-item" tabindex="1" type="" prefix="" content="" style="position: relative;display:none;">
	    					<div class="app">
				    			<div class="wpfc-exclude-item-form-title">Title M</div>
				    			<span class="wpfc-exclude-item-details wpfc-exclude-item-url"></span>
	    					</div>
			    		</div>
		    			<!-- samples end -->

		    			<h2 style="padding-bottom:10px;float:left;"><?php _e("Exclude Pages", "wp-fastest-cache"); ?></h2>

				    	<div style="float:left;margin-top:-37px;padding-left:608px;">
					    	<button data-type="page" type="button" class="wpfc-add-new-exclude-button wpfc-dialog-buttons" style="display: inline-block;padding: 4px 10px;">
					    		<span><?php _e("Add New Rule", "wp-fastest-cache"); ?></span>
					    	</button>
				    	</div>

				    	<div class="wpfc-exclude-page-list" style="display: block;width:98%;float:left;">

				    	</div>

				    	<div class="exclude_section_clear">
				    		<div></div>
				    	</div>


				    	<h2 style="padding-bottom:10px;float:left;"><?php _e("Exclude User-Agents", "wp-fastest-cache"); ?></h2>

				    	<div style="float:left;margin-top:-37px;padding-left:608px;">
					    	<button data-type="useragent" type="button" class="wpfc-add-new-exclude-button wpfc-dialog-buttons" style="display: inline-block;padding: 4px 10px;">
					    		<span><?php _e("Add New Rule", "wp-fastest-cache"); ?></span>
					    	</button>
				    	</div>

				    	<div class="wpfc-exclude-useragent-list" style="display: block;width:98%;float:left;">

				    	</div>


				    	<div class="exclude_section_clear">
				    		<div></div>
				    	</div>



				    	<h2 style="padding-bottom:10px;float:left;"><?php _e("Exclude Cookies", "wp-fastest-cache"); ?></h2>

				    	<div style="float:left;margin-top:-37px;padding-left:608px;">
					    	<button data-type="cookie" type="button" class="wpfc-add-new-exclude-button wpfc-dialog-buttons" style="display: inline-block;padding: 4px 10px;">
					    		<span><?php _e("Add New Rule", "wp-fastest-cache"); ?></span>
					    	</button>
				    	</div>

				    	<div class="wpfc-exclude-cookie-list" style="display: block;width:98%;float:left;">

				    	</div>


				    	<div class="exclude_section_clear">
				    		<div></div>
				    	</div>


				    	<h2 style="padding-bottom:10px;float:left;"><?php _e("Exclude CSS", "wp-fastest-cache"); ?></h2>

				    	<div style="float:left;margin-top:-37px;padding-left:608px;">
					    	<button data-type="css" type="button" class="wpfc-add-new-exclude-button wpfc-dialog-buttons" style="display: inline-block;padding: 4px 10px;">
					    		<span><?php _e("Add New Rule", "wp-fastest-cache"); ?></span>
					    	</button>
				    	</div>

				    	<div class="wpfc-exclude-css-list" style="display: block;width:98%;float:left;">

				    	</div>



				    	<div class="exclude_section_clear">
				    		<div></div>
				    	</div>



				    	<h2 style="padding-bottom:10px;float:left;"><?php _e("Exclude JS", "wp-fastest-cache"); ?></h2>

				    	<div style="float:left;margin-top:-37px;padding-left:608px;">
					    	<button data-type="js" type="button" class="wpfc-add-new-exclude-button wpfc-dialog-buttons" style="display: inline-block;padding: 4px 10px;">
					    		<span><?php _e("Add New Rule", "wp-fastest-cache"); ?></span>
					    	</button>
				    	</div>

				    	<div class="wpfc-exclude-js-list" style="display: block;width:98%;float:left;">

				    	</div>


				    	<?php
				    		include(WPFC_MAIN_PATH."templates/exclude.php");
				    	?>

				    	<form method="post" name="wp_manager">
				    		<input type="hidden" value="exclude" name="wpFastestCachePage">
				    		<div class="wpfc-exclude-rule-container"></div>
				    		<!-- <div class="questionCon qsubmit">
								<div class="submit"><input type="submit" class="button-primary" value="Submit"></div>
							</div> -->
				    	</form>
				    	<script type="text/javascript">

					    	<?php 
					    		if($rules_json = get_option("WpFastestCacheExclude")){
					    			?>WpFcExcludePages.init(<?php echo $rules_json; ?>);<?php
					    		}else{
					    			?>WpFcExcludePages.init();<?php
					    		}
					    	?>
				    	</script>
				    </div>

				    <div class="tab7" style="padding-left:20px;">
				    	<h2 style="padding-bottom:10px;"><?php _e("CDN Settings", "wp-fastest-cache"); ?></h2>
				    	<div>
				    		<div class="integration-page" style="display: block;width:98%;float:left;">

				    			<div wpfc-cdn-name="maxcdn" class="int-item int-item-left">
				    				<img style="border-radius:50px;" src="<?php echo plugins_url("wp-fastest-cache/images/bunny-cdn-icon.png"); ?>" />
				    				<div class="app">
				    					<div style="font-weight:bold;font-size:14px;">CDN by Bunny</div>
				    					<p>Speed up content with next-generation CDN</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>


				    			<div wpfc-cdn-name="other" class="int-item">
				    				<img src="<?php echo plugins_url("wp-fastest-cache/images/othercdn.png"); ?>" />
				    				<div class="app">
				    					<div style="font-weight:bold;font-size:14px;">Other CDN Providers</div>
				    					<p>You can use any cdn provider.</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>


				    			<div wpfc-cdn-name="cloudflare" class="int-item">
				    				<img style="border-radius:50px;" src="<?php echo plugins_url("wp-fastest-cache/images/cloudflare.png"); ?>" />
				    				<div class="app">
				    					<div style="font-weight:bold;font-size:14px;">CDN by Cloudflare</div>
				    					<p>CDN, DNS, DDoS protection and security</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>

				    		</div>
				    	</div>
				    	<script type="text/javascript">
				    		(function() {
					    		<?php
					    			$cdn_values = get_option("WpFastestCacheCDN");

					    			if($cdn_values){
					    				$std_obj = json_decode($cdn_values);
					    				$cdn_values_arr = array();

					    				if(is_array($std_obj)){
											$cdn_values_arr = $std_obj;
										}else{
											array_push($cdn_values_arr, $std_obj);
										}

					    				foreach ($cdn_values_arr as $cdn_key => $cdn_value) {
						    				if($cdn_value->id == "amazonaws" || $cdn_value->id == "keycdn" || $cdn_value->id == "cdn77"){
						    					$cdn_value->id = "other";
						    				}

						    				if(isset($cdn_value->status) && $cdn_value->status == "pause"){
						    					?>jQuery("div[wpfc-cdn-name='<?php echo $cdn_value->id;?>']").find("div.meta").addClass("isConnected pause");<?php
						    				}else{
						    					?>jQuery("div[wpfc-cdn-name='<?php echo $cdn_value->id;?>']").find("div.meta").addClass("isConnected");<?php
						    				}
					    				}
					    			}
					    		?>
				    			jQuery("div.integration-page .int-item").click(function(e){
				    				jQuery("#revert-loader-toolbar").show();
				    				jQuery("div[id='wpfc-modal-maxcdn'], div[id='wpfc-modal-other'], div[id='wpfc-modal-photon']").remove();

					    			jQuery.ajax({
										type: 'GET', 
										url: ajaxurl,
										cache: false,
										data : {"action": "wpfc_cdn_options"},
										dataType : "json",
										success: function(data){
											if(data.id){
												if(data.id == "keycdn" || data.id == "cdn77" || data.id == "amazonaws"){
													data.id = "other";
												}
											}


											WpfcCDN.init({"id" : jQuery(e.currentTarget).attr("wpfc-cdn-name"),
							    				"template_main_url" : "<?php echo plugins_url('wp-fastest-cache/templates/cdn'); ?>",
							    				"values" : data,
							    				"nonce" : "<?php echo wp_create_nonce("cdn-nonce"); ?>"
							    			});


											
											// if(data.id && jQuery(e.currentTarget).attr("wpfc-cdn-name") != data.id){
											// 	Wpfc_New_Dialog.dialog("wpfc-modal-onlyonecdn", {close: "default"});

											// 	Wpfc_New_Dialog.show_button("close");
												
											// 	jQuery("#revert-loader-toolbar").hide();
											// }else{
							    // 				WpfcCDN.init({"id" : jQuery(e.currentTarget).attr("wpfc-cdn-name"),
							    // 					"template_main_url" : "<?php echo plugins_url('wp-fastest-cache/templates/cdn'); ?>",
							    // 					"values" : data
							    // 				});
											// }
										}
									});
				    			});
				    		})();
				    	</script>
				    </div>

				    <div class="tab8" style="padding-left:20px;">
				    	<h2 style="padding-bottom:10px;display: inline-block;float: left;width: 48%;"><?php _e("Database Cleanup", "wp-fastest-cache"); ?></h2>

				    	<?php
				    		if(file_exists(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/db-auto-cleanup.php")){
				    			include_once WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/db-auto-cleanup.php";
				    		}
				    	?>

				    	<div>

			    		<?php if(!$this->isPluginActive("wp-fastest-cache-premium/wpFastestCachePremium.php")){ ?>
				    			<style type="text/css">
				    				div.tab8 h2{
				    					opacity: 0.3 !important;
				    				}
				    				div.tab8 .integration-page{
				    					opacity: 0.3 !important;
				    					pointer-events: none !important;
				    				}
				    				select#wpfc-auto-cleanup-option{
				    					opacity: 0.3 !important;
				    					pointer-events: none !important;
				    				}
				    			</style>
				    			
				    			<div style="z-index:9999;width: 160px; height: 60px; position: absolute; margin-left: 230px; margin-top: 25px; color: white;">
						    		<div style="font-family:sans-serif;font-size:13px;text-align: center; border-radius: 5px; float: left; background-color: rgb(51, 51, 51); color: white; width: 147px; padding: 20px 50px;">
						    			<label><?php _e("Only available in Premium version", "wp-fastest-cache"); ?></label>
						    		</div>
						    	</div>
			    		<?php } ?>

				    		<div class="integration-page" style="display: block;width:98%;float:left;">

				    			<div wpfc-db-name="all_warnings" class="int-item int-item-left">
				    				<div style="float:left;width:45px;height:45px;margin-right:12px;">
				    					<span class="flaticon-technology"></span> 
				    				</div>
				    				<div class="app db">
				    					<div style="font-weight:bold;font-size:14px;">ALL <span class="db-number">(0)</span></div>
				    					<p>Clean all of them</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>

				    			<div wpfc-db-name="post_revisions" class="int-item int-item-right">
				    				<div style="float:left;width:45px;height:45px;margin-right:12px;">
				    					<span class="flaticon-draft"></span> 
				    				</div>
				    				<div class="app db">
				    					<div style="font-weight:bold;font-size:14px;">Post Revisions <span class="db-number">(0)</span></div>
				    					<p>Clean all post revisions</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>

				    			<div wpfc-db-name="trashed_contents" class="int-item int-item-left">
				    				<div style="float:left;width:45px;height:45px;margin-right:12px;">
				    					<span class="flaticon-recycling"></span> 
				    				</div>
				    				<div class="app db">
				    					<div style="font-weight:bold;font-size:14px;">Trashed Contents <span class="db-number">(0)</span></div>
				    					<p>Clean all trashed posts & pages</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>

				    			<div wpfc-db-name="trashed_spam_comments" class="int-item int-item-right">
				    				<div style="float:left;width:45px;height:45px;margin-right:12px;">
				    					<span class="flaticon-interface"></span> 
				    				</div>
				    				<div class="app db">
				    					<div style="font-weight:bold;font-size:14px;">Trashed & Spam Comments <span class="db-number">(0)</span></div>
				    					<p>Clean all comments from trash & spam</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>

				    			<div wpfc-db-name="trackback_pingback" class="int-item int-item-left">
				    				<div style="float:left;width:45px;height:45px;margin-right:12px;">
				    					<span class="flaticon-pingback"></span> 
				    				</div>
				    				<div class="app db">
				    					<div style="font-weight:bold;font-size:14px;">Trackbacks and Pingbacks <span class="db-number">(0)</span></div>
				    					<p>Clean all trackbacks and pingbacks</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>

				    			<div wpfc-db-name="transient_options" class="int-item int-item-right">
				    				<div style="float:left;width:45px;height:45px;margin-right:12px;">
				    					<span class="flaticon-file"></span> 
				    				</div>
				    				<div class="app db">
				    					<div style="font-weight:bold;font-size:14px;">Transient Options <span class="db-number">(0)</span></div>
				    					<p>Clean all transient options</p>
				    				</div>
				    				<div class="meta"></div>
				    			</div>




				    		</div>
				    	</div>
				    </div>

				    <?php include_once(WPFC_MAIN_PATH."templates/permission_error.html"); ?>
				    <?php include_once(WPFC_MAIN_PATH."templates/toolbar_settings.php"); ?>

				    <?php
				    	if(isset($this->options->wpFastestCacheStatus)){
					    	if(isset($_SERVER["HTTP_CDN_LOOP"]) && $_SERVER["HTTP_CDN_LOOP"] && $_SERVER["HTTP_CDN_LOOP"] == "cloudflare"){
								$cloudflare_integration_exist = false;
					    		$cdn_values = get_option("WpFastestCacheCDN");

								if($cdn_values){
									$std_obj = json_decode($cdn_values);
									
									foreach($std_obj as $key => $value){
										if($value->id == "cloudflare"){
											$cloudflare_integration_exist = true;
											break;
										}
									}
								}

								if(!$cloudflare_integration_exist){
									include_once(WPFC_MAIN_PATH."templates/cloudflare_warning.html"); 
								}
					    	}
				    	}
				    ?>
			</div>

			<div class="omni_admin_sidebar">

				<div class="omni_admin_sidebar_section wpfc-sticky-notification">
		            <main role="main" class="">
		                <div data-variant="7361" class="sticky-common-banner">
		                    <div class="header">
		                    	<img class="header-logo disable-lazy" src="<?php echo plugins_url("wp-fastest-cache/images/customer-service.png?v=1"); ?>" data-pin-no-hover="true" style="margin-top: 3px; margin-bottom: 11px;"/>

		                        <h5 class="title">Our support is here 24/7 for you.</h5>
		                    </div>
		                    <?php if(class_exists("WpFastestCachePowerfulHtml")){ ?>
		                    	<a href="https://www.wpfastestcache.com/contact-us/" target="_blank" class="stickyFooterBannerCTA main-cta">Send Us an Email</a>

		                    <?php }else{ ?>
		                    	<a href="http://wordpress.org/support/plugin/wp-fastest-cache" target="_blank" class="stickyFooterBannerCTA main-cta">Create Topic</a>
		                    <?php } ?>
		                </div>
		            </main>					
				</div>


				<div class="omni_admin_sidebar_section wpfc-sticky-notification">
		            <main role="main" class="">
		                <div data-variant="7361" class="sticky-common-banner">
		                    <div class="header">
		                    	<img class="header-logo disable-lazy" src="<?php echo plugins_url("wp-fastest-cache/images/star-rating.png?v=1"); ?>" data-pin-no-hover="true" style="margin-top: 3px; margin-bottom: 11px;"/>

		                        <h5 class="title">Please support us by giving a review.</h5>
		                    </div>

		                    <a href="https://wordpress.org/support/plugin/wp-fastest-cache/reviews/?rate=5#new-post" target="_blank" class="stickyFooterBannerCTA main-cta">Add Your Review</a>
		                </div>
		            </main>					
				</div>



				<?php if(class_exists("WpFastestCachePowerfulHtml")){ ?>
					<style type="text/css">
						.omni_admin_sidebar{
							width: 200px;
						}
					</style>
				<?php }else{ ?>
					<style type="text/css">
						.omni_admin_sidebar > div:first-child{
							margin-left: 10px;
						}
					</style>
				<div class="omni_admin_sidebar_section wpfc-sticky-notification" style="width: 100%;">
		            <main role="main" class="">
		                <div data-variant="7361" class="sticky-common-banner">
		                    <div class="header">
		                    	<img class="header-logo disable-lazy" src="<?php echo plugins_url("wp-fastest-cache/images/crown.png?v=1"); ?>" data-pin-no-hover="true" />

		                        <h5 class="title">Make today the day you say goodbye to slowness.</h5>
		                    </div>
		                    <img class="visual disable-lazy" src="<?php echo plugins_url("wp-fastest-cache/images/price-mini-banner.jpg"); ?>" alt="Make today the day you say goodbye to slowness." data-pin-no-hover="true">
		                    <a href="https://www.wpfastestcache.com/#buy" target="_blank" class="stickyFooterBannerCTA main-cta">Sign Up Now!</a>
		                </div>
		            </main>					
				</div>
				<?php } ?>



			</div>

			<div id="wpfc-plugin-setup-warning" class="mainContent" style="display:none;border:1px solid black">
			        <div class="pageView"style="display: block;">
			            <div class="fakeHeader">
			                <h3 class="title-h3">Error Occured</h3>
			            </div>
			            <div class="fieldRow active">

			            </div>
			            <div class="pagination">
			                <div class="next" style="text-align: center;float: none;">
			                    <button class="wpfc-btn primaryCta" id="wpfc-read-tutorial">
			                        <span class="label">Continue</span>
			                    </button>
			                </div>
			            </div>
			        </div>
			</div>

			<?php if(!class_exists("WpFastestCacheImageOptimisation")){ ?>
				<div id="wpfc-premium-tooltip" style="display:none;width: 160px; height: 60px; position: absolute; margin-left: 354px; margin-top: 112px; color: white;">
					<div style="float:left;width:13px;">
						<div style="width: 0px; height: 0px; border-top: 6px solid transparent; border-right: 6px solid #333333; border-bottom: 6px solid transparent; float: right; margin-right: 0px; margin-top: 25px;"></div>
					</div>
					<div style="font-family:sans-serif;font-size:13px;text-align: center; border-radius: 5px; float: left; background-color: rgb(51, 51, 51); color: white; width: 147px; padding: 10px 0px;">
						<label><?php _e("Only available in Premium version", "wp-fastest-cache"); ?></label>
					</div>
				</div>

				<script type="text/javascript">
					jQuery("div.questionCon.disabled").click(function(e){
						if(e.target.tagName == "IMG"){
							if(e.target.src.match(/info\.png/)){
								return;
							}
						}

						if(typeof window.wpfc.tooltip != "undefined"){
							clearTimeout(window.wpfc.tooltip);
						}

						var inputCon = jQuery(e.currentTarget).find(".inputCon");
						var left = 30;

						jQuery(e.currentTarget).children().each(function(i, child){
							left = left + jQuery(child).width();
						});

						jQuery("#wpfc-premium-tooltip").css({"margin-left" : left + "px", "margin-top" : (jQuery(e.currentTarget).offset().top - jQuery(".tab1").offset().top + 25) + "px"});
						jQuery("#wpfc-premium-tooltip").fadeIn( "slow", function() {
							window.wpfc.tooltip = setTimeout(function(){ jQuery("#wpfc-premium-tooltip").hide(); }, 1000);
						});
						return false;
					});
				</script>
			<?php }else{ ?>
				<script type="text/javascript">
					jQuery(".update-needed").click(function(){
						if(jQuery("div[id^='wpfc-modal-updatenow-']").length === 0){
							Wpfc_New_Dialog.dialog("wpfc-modal-updatenow", {close: function(){
								Wpfc_New_Dialog.clone.find("div.window-content input").each(function(){
									if(jQuery(this).attr("checked")){
										var id = jQuery(this).attr("action-id");
										jQuery("div.tab1 div[template-id='wpfc-modal-updatenow'] div.window-content input#" + id).attr("checked", true);
									}
								});

								Wpfc_New_Dialog.clone.remove();
							}});

							Wpfc_New_Dialog.show_button("close");
						}

						return false;
					});
				</script>
			<?php } ?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#wpFastestCachePreload, #wpFastestCacheLazyLoad").change(function(){
						let id = jQuery(this).attr("id");

						if(jQuery(this).is(':checked')){
							jQuery("div[data-info-id='" + id + "']").hide();
							jQuery("div[data-gear-id='" + id + "']").show();
						}else{
							jQuery("div[data-info-id='" + id + "']").show();
							jQuery("div[data-gear-id='" + id + "']").hide();
						}
					});

					jQuery("div[data-gear-id='wpFastestCachePreload'], div[data-gear-id='wpFastestCacheLazyLoad']").click(function(){

						if(jQuery(this).attr("data-gear-id") == "wpFastestCachePreload"){
							WpFcPreload.open_modal();
						}else if(jQuery(this).attr("data-gear-id") == "wpFastestCacheLazyLoad"){
							open_lazy_load_modal();
						}

					})

				});

				if(typeof open_lazy_load_modal == "undefined"){
					jQuery("div[data-info-id='wpFastestCacheLazyLoad']").show();
					jQuery("div[data-gear-id='wpFastestCacheLazyLoad']").hide();

				}
			</script>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					//if "Mobile Theme" is selected, "Mobile" is selected as well
					jQuery("#wpFastestCacheMobileTheme").click(function(e){
						if(jQuery(this).is(':checked')){
							jQuery("#wpFastestCacheMobile").attr('checked', true);
						}
					});

					//if "Mobile Theme" has been selected, "Mobile" option cannot be changed
					jQuery("#wpFastestCacheMobile").click(function(e){
						if(jQuery("#wpFastestCacheMobileTheme").is(':checked')){
							jQuery(this).attr('checked', true);
						}
					});

					//if "Lazy Load" has been selected both "Mobile" and "Mobile Theme" options enabled
					jQuery("#wpFastestCacheLazyLoad").click(function(e){
						if(jQuery(this).is(':checked')){
							jQuery("#wpFastestCacheMobile").attr('checked', true);
							jQuery("#wpFastestCacheMobileTheme").attr('checked', true);
						}
					});
				});
			</script>
			<?php
			if(isset($_SERVER["SERVER_SOFTWARE"]) && $_SERVER["SERVER_SOFTWARE"] && !preg_match("/iis/i", $_SERVER["SERVER_SOFTWARE"]) && !preg_match("/nginx/i", $_SERVER["SERVER_SOFTWARE"])){
				if(!isset($_POST["wpFastestCachePage"])){
					$this->check_htaccess();
				}
			}
		}
	}
?>