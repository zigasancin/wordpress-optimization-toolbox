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

$icon = BREEZE_PLUGIN_URL . 'assets/images/faq-active.png';


?>
<section>
	<div class="br-section-title">
		<img src="<?php echo $icon; ?>"/>
		<?php _e( 'FAQS', 'breeze' ); ?>
	</div>
	<div>
	<!-- START OPTION -->
	<div class="br-option-item">
	<div id="faq-content">
		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Delay inline JavaScript issue', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p>
					<?php _e( 'If JavaScript errors show up in front-end you can try the following solutions.', 'breeze' ); ?><br/>
					<?php _e( '- You can disable the option altogether.', 'breeze' ); ?><br/>
					<?php _e( '- You can leave the option enabled and identify which line found in the textarea is causing the issue, then remove only that online that line.', 'breeze' ); ?>
				</p>
			</div>
		</div>

        <div class="faq-block">
            <h3 class="faq-question"><?php _e( 'Does Breeze support Aelia Currency Switcher Plugin For WooCommerce?', 'breeze' ); ?></h3>
            <div class="faq-answer">
                <p>
                    <?php
	                $url_kb_aelia = 'https://support.cloudways.com/en/articles/5496342-how-to-use-varnish-at-cloudways#h_23a588a7a9';
	                echo sprintf(
	                /* translators: %s Export file location */
		                __( 'Breeze is fully compatible with Aelia Currency Switcher Plugin. Also exclude the cookie of Aelia currency switcher plugin "aelia_cs_selected_currency" from Varnish settings.
<br/>
<br/>
If Varnish is active, you will need to exclude URLs and file type(s) in the Varnish configuration. If you are hosting WordPress websites on Cloudways servers, follow <a href="%s" target="_blank">this KB to exclude URLs from the Varnish cache</a>.', 'breeze' ),
		                $url_kb_aelia
	                );
                      ?>
                </p>
            </div>
        </div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does Breeze support Varnish and to what extent?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Breeze, by default, supports Varnish. It has been tested to be fully compatible with Cloudways Servers that come with Varnish pre-installed. If you are using hosting providers other than Cloudways, we suggest you confirm Varnish support with your hosting provider.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does Breeze support WooCommerce?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Breeze is fully compatible with WooCommerce, out of the box. It does not require any special configurations.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does Breeze support WordPress Multisite?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Breeze is fully compatible with WordPress Multisite without the need for any extra configuration.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'How does Breeze handle WordPress multisite?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Breeze handles all WordPress multisite instances globally. All the settings for multisite are now handled on the network level.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Is Breeze compatible with other WordPress Cache plugins?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'We DO NOT recommend using two WordPress cache plugins at the same time on any WordPress website.', 'breeze' ); ?></p>
				<p><?php _e( 'We strongly recommend that you use Breeze as the only cache plugin for your website. If there are any other cache plugins installed, please ensure that you have disabled them prior to proceeding with the Breeze installation.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Is Breeze compatible with HTTPS?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Breeze does not require any special configuration to work with HTTP or HTTPS pages.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does Breeze have compatibility issues with other known plugins?', 'breeze' ); ?></h3>
			<div class="faq-answer">
                <p><?php
					$url_kb_compat = 'https://community.cloudways.com/';
					echo sprintf(
					/* translators: %s Cloudways community */
						__( 'Breeze has been tested with popular plugins available on WordPress.org. Please feel free to report any incompatibilities on the WordPress Support Forums or on <a href="%s" target="_blank">Cloudways Community Forum.</a>.', 'breeze' ),
						$url_kb_compat
					);
					?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does Breeze support CDN?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Breeze supports CDN integration. It allows all static assets (such as images, CSS and JS files) to be served via CDN.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'What does Breeze’s Database Optimization feature do?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'WordPress databases are notorious for storing information like post revisions, spam comments and much more. Over time, databases l become bloated and it is a good practice to clear out unwanted information to reduce database size and improve optimization.', 'breeze' ); ?></p>
				<p><?php _e( 'Breeze’s database optimization cleans out unwanted information in a single click.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Will comments and other dynamic parts of my blog appear immediately?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Comments will appear upon moderation as per the comment system (or policy) set in place by the blog owner. Other dynamic changes such as any modifications in files will require a full cache purge.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Can I exclude URLs of individual files and pages from cache?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'You can exclude URL(s) by mentioning in exclude fields available in the Breeze settings. Excluding a URL(s) will automatically prevent the page to be cached on server side caching (Varnish) by adding a cache-control:no-cache Response Header.', 'breeze' ); ?></p>
				<p><?php _e( 'If Varnish is active, you will need to exclude URLs and file type(s) in the Varnish configuration. If you are hosting WordPress websites on Cloudways servers, follow ', 'breeze' ); ?>
					<a href="https://support.cloudways.com/how-to-exclude-url-from-varnish/" target="_blank"><?php _e( 'this KB to exclude URLs from the Varnish cache.', 'breeze' ); ?></a>
				</p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does it work with all hosting providers?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Breeze has been tested to work with all major hosting providers. In addition, major Breeze options such as Gzip, browser cache, minification, grouping, database optimization. CDN integration will work as expected on all hosting providers.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Where can I get support for Breeze?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'You can get your questions answered on the WordPress support forums. If you are a Cloudways customer, please feel free to start a discussion at', 'breeze' ); ?>
					<a href="https://community.cloudways.com/" target="_blank"><?php _e( 'Cloudways Community Forum.', 'breeze' ); ?></a>
				</p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'How can I test and verify the results?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'You will be able to see the impact of the Breeze Cache Plugin almost immediately. We also recommend using the following tools for generating metrics:', 'breeze' ); ?></p>
				<ul style="margin-top: 10px">
					<li><a href="https://developers.google.com/speed/pagespeed/" target="_blank"><?php _e( 'Google Page Speed', 'breeze' ); ?></a></li>
					<li><a href="https://www.webpagetest.org/test" target="_blank"><?php _e( 'WebPagetest', 'breeze' ); ?></a></li>
					<li><a href="https://tools.pingdom.com/" target="_blank"><?php _e( 'Pingdom', 'breeze' ); ?></a></li>
				</ul>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does Breeze plugin work with Visual Builder?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Yes, Breeze Plugin is compatible with Visual Builder.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'What popular CDN are supported by Breeze Plugin?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Breeze supports the following three popular CDNs:', 'breeze' ); ?></p>
				<ul style="margin-top: 10px">
					<li><a href="https://support.cloudways.com/how-to-use-breeze-with-maxcdn/" target="_blank"><?php _e( 'MaxCDN', 'breeze' ); ?></a></li>
					<li><a href="https://support.cloudways.com/how-to-use-breeze-with-keycdn/" target="_blank"><?php _e( 'KeyCDN', 'breeze' ); ?></a></li>
					<li><a href="https://support.cloudways.com/how-to-use-breeze-with-amazon-cloudfront/" target="_blank"><?php _e( 'Amazon Cloudfront', 'breeze' ); ?></a></li>
				</ul>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does Breeze support Push CDN?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'No, Breeze does not support Push CDN. However, you could use Breeze with Push CDNs using third party plugins.', 'breeze' ); ?></p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'Does Breeze Work With CloudFlare?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Yes. The process of setting up CloudFlare with Breeze is easy. Check out the following ', 'breeze' ); ?>
					<a href="https://support.cloudways.com/can-i-use-cloudflare-cdn/" target="_blank"><?php _e( 'KnowledgeBase article for details.', 'breeze' ); ?></a>
				</p>
			</div>
		</div>

		<div class="faq-block">
			<h3 class="faq-question"><?php _e( 'How Breeze cache uses Gzip?', 'breeze' ); ?></h3>
			<div class="faq-answer">
				<p><?php _e( 'Using Gzip, Breeze compresses the request files, further reducing the size of the download files and speeding up the user experience.', 'breeze' ); ?></p>
			</div>
		</div>
	</div>
	</div>
	<!-- END OPTION -->
	</div>

</section>
