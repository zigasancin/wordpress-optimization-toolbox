<?php if (!defined('WPO_VERSION')) die('No direct access allowed'); ?>

<?php
	$sqlversion = (string)$wpdb->get_var("SELECT VERSION() AS version");

	echo '<h1>WP-Optimize '.WPO_VERSION.'</h1>';

	echo '<em>'.__('Running on:', 'wp-optimize').' PHP '.PHP_VERSION.', '.__('MySQL', 'wp-optimize').' '.$sqlversion.' - '.htmlspecialchars(PHP_OS).'</em><br>';
	
	function wp_optimize_header_link($url, $text) {
	
		if (false !== strpos($url, '//updraftplus.com')) $url = apply_filters('wpoptimize_updraftplus_com_link', $url);
	
		echo '<a href="'.esc_attr($url).'">'.htmlspecialchars($text).'</a>';
		
	}

?>
<p>
		<?php wp_optimize_header_link('https://updraftplus.com/wp-optimize/', __('Home', 'wp-optimize'));?> |

		<?php wp_optimize_header_link('https://updraftplus.com/', 'UpdraftPlus.Com');?> |
		
		<?php wp_optimize_header_link('https://updraftplus.com/news/', __('News', 'wp-optimize'));?> |

		<?php wp_optimize_header_link('https://twitter.com/updraftplus', __('Twitter', 'wp-optimize'));?> |

		<?php wp_optimize_header_link('https://wordpress.org/support/plugin/wp-optimize/', __('Support', 'wp-optimize'));?> |

		<?php wp_optimize_header_link('https://updraftplus.com/newsletter-signup', __('Newsletter sign-up', 'wp-optimize'));?> |

		<?php wp_optimize_header_link('https://david.dw-perspective.org.uk', __("Lead developer", 'wp-optimize'));?> |
		
		<?php wp_optimize_header_link('https://source.updraftplus.com/team-updraft/wp-optimize/', 'Gitlab');?> |
		
		<?php wp_optimize_header_link('https://wordpress.org/plugins/wp-optimize/faq/', __("FAQs", 'wp-optimize'));?> |

		<?php wp_optimize_header_link('https://www.simbahosting.co.uk/s3/shop/', __("More plugins", 'wp-optimize'));?>
		
</p>

<h2 class="nav-tab-wrapper">

		<a href="<?php echo esc_attr($options->admin_page_url()); ?>&amp;tab=wp_optimize_optimize" class="nav-tab <?php if ($active_tab == 'wp_optimize_optimize') echo 'nav-tab-active'; ?>">WP-Optimize</span></a>

		<a href="<?php echo esc_attr($options->admin_page_url()); ?>&amp;tab=wp_optimize_tables" class="nav-tab <?php if ($active_tab == 'wp_optimize_tables') echo 'nav-tab-active'; ?>"><?php _e('Table information', 'wp-optimize') ?></a>

		<a href="<?php echo esc_attr($options->admin_page_url()); ?>&amp;tab=wp_optimize_settings" class="nav-tab <?php if ($active_tab == 'wp_optimize_settings') echo 'nav-tab-active'; ?>"><?php _e('Settings', 'wp-optimize') ?></a>

		<a href="<?php echo esc_attr($options->admin_page_url()); ?>&amp;tab=wp_optimize_may_also" class="nav-tab <?php if ($active_tab == 'wp_optimize_may_also') echo 'nav-tab-active'; ?>"><?php _e('Plugin family', 'wp-optimize') ?></a>

</h2>
