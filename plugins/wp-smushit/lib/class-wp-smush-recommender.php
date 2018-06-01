<?php
/**
 * Displays the UI for .org plugin recommendations
 *
 * @package WP Smush
 * @subpackage Admin
 * @since 2.7.9
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

if ( ! class_exists( 'WpSmushRecommender' ) ) {

	class WpSmushRecommender {

		function __construct() {
			//Hook UI at the end of Settings UI
			add_action( 'smush_settings_ui_bottom', array( $this, 'ui' ), 12 );

		}

		/**
		 * Do not display Directory smush for Subsites
		 *
		 * @return bool True/False, whether to display the Directory smush or not
		 *
		 */
		function should_continue() {
			global $WpSmush;

			//Do not show directory smush, if not main site in a network
			if ( $WpSmush->validate_install() ) {
				return false;
			}

			return true;
		}

		/**
		 * Output the required UI for Plugin recommendations
		 */
		function ui() {
			global $wpsmushit_admin;
			if ( $this->should_continue() ) { ?>

                <div class="sui-row" id="sui-cross-sell-footer">
                    <div><span class="sui-icon-plugin-2"></span></div>
                    <h3><?php esc_html_e( "Check out our other free wordpress.org plugins!", "wp-smushit" ); ?></h3>
                </div>
                <div class="sui-row sui-cross-sell-modules"><?php
					//Hummingbird
					$hb_title   = esc_html__( "Hummingbird Page Speed Optimization", "wp-smushit" );
					$hb_content = esc_html__( "Performance Tests, File Optimization & Compression, Page, Browser & Gravatar Caching, GZIP Compression, CloudFlare Integration & more.", "wp-smushit" );
					$hb_class   = "hummingbird";
					$hb_url     = esc_url( "https://wordpress.org/plugins/hummingbird-performance/" );
					echo $this->recommendation_box( $hb_title, $hb_content, $hb_url, $hb_class, 1 );
					//Defender
					$df_title   = esc_html__( "Defender Security, Monitoring, and Hack Protection", "wp-smushit" );
					$df_content = esc_html__( "Security Tweaks & Recommendations, File & Malware Scanning, Login & 404 Lockout Protection, Two-Factor Authentication & more.", "wp-smushit" );
					$df_class   = "defender";
					$df_url     = esc_url( "https://wordpress.org/plugins/defender-security/" );
					echo $this->recommendation_box( $df_title, $df_content, $df_url, $df_class, 2 );
					//SmartCrawl
					$sc_title   = esc_html__( "SmartCrawl Search Engine Optimization", "wp-smushit" );
					$sc_content = esc_html__( "Customize Titles & Meta Data, OpenGraph, Twitter & Pinterest Support, Auto-Keyword Linking, SEO & Readability Analysis, Sitemaps, URL Crawler & more.", "wp-smushit" );
					$sc_class   = "smartcrawl";
					$sc_url     = esc_url( "https://wordpress.org/plugins/smartcrawl-seo" );
					echo $this->recommendation_box( $sc_title, $sc_content, $sc_url, $sc_class, 3 );
					$site_url = esc_url( "https://premium.wpmudev.org/projects/" );
					$site_url = add_query_arg(
						array(
							'utm_source'   => 'smush',
							'utm_medium'   => 'plugin',
							'utm_campaign' => 'smush_footer_upsell_notice'
						),
						$site_url
					);
					$dir = defined('__DIR__') ? __DIR__ : dirname(__FILE__);
					?>
                </div>
                <div class="sui-cross-sell-bottom">
                <h3>WPMU DEV - Your WordPress Toolkit</h3>
                <p>Pretty much everything you need for developing and managing WordPress based websites, and then
                    some.</p>

                <a class="sui-button sui-button-green" href="<?php echo $site_url; ?>"
                   id="dash-uptime-update-membership" target="_blank">
                    Learn more
                </a>

                <img class="sui-image"
                     src="<?php echo plugins_url( "assets/images/dev-team.png", $dir ); ?>"
                     srcset="<?php echo plugins_url( "assets/images/dev-team@2x.png", $dir ); ?> 2x"
                     alt="Try pro features for free!">
                </div><?php
			}
                ?>
            <div class="sui-footer">Made with <i class="sui-icon-heart"></i> by WPMU DEV</div><?php

		}

		/**
		 * Prints the UI for the given recommended plugin
		 *
		 * @param $title
		 * @param $content
		 * @param $link
		 * @param $plugin_class
		 */
		function recommendation_box( $title, $content, $link, $plugin_class, $seq ) {
			//Put bg to box parent div ?>
            <div class="sui-col-md-4">
            <div class="sui-cross-<?php echo $seq; ?> sui-cross-<?php echo $plugin_class; ?>"><span></span></div>
            <div class="sui-box">
                <div class="sui-box-body">
                    <h3><?php echo $title; ?></h3>
                    <p><?php echo $content; ?></p>
                    <a href="<?php echo esc_url( $link ); ?>" class="sui-button sui-button-ghost"
                       target="_blank">
                        View features <i class="sui-icon-arrow-right"></i>
                    </a>
                </div>
            </div>
            </div><?php
		}

	}

	//Class Object
	global $wpsmush_recommender;
	$wpsmush_promo = new WpSmushRecommender();
}