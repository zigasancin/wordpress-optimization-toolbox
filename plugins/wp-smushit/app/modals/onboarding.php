<?php
/**
 * Onboarding modal.
 *
 * @since 3.1
 * @package WP_Smush
 *
 * @var $cta_url    string CTA URL.
 */

use Smush\Core\Helper;
use Smush\App\Admin;

if ( ! defined( 'WPINC' ) ) {
	die;
}
$should_show_tracking_confirmation = ! is_multisite();
$is_pro                            = WP_Smush::is_pro();
$lossy_title                       = $is_pro ? __( 'Ultra Smush', 'wp-smushit' ) : __( 'Super Smush', 'wp-smushit' );
$lossy_description                 = $is_pro ? esc_html__( 'Optimize images up to 5x more than Super Smush with our professional grade multi-pass lossy compression.', 'wp-smushit' )
												: esc_html__( 'Optimize images up to 2x more than regular smush with our multi-pass lossy compression.', 'wp-smushit' );
$lossy_action_label                = $is_pro ? __( 'Enable Ultra Smush', 'wp-smushit' ) : __( 'Enable Super Smush', 'wp-smushit' );
// Pro features.
$plugin_discount     = WP_Smush::get_instance()->admin()->get_plugin_discount();
$total_cdn_locations = Admin::CDN_POP_LOCATIONS;
$upsell_url          = $this->get_utm_link(
	array(
		'utm_campaign' => 'smush_wizard',
		'utm_content'  => 'view_plans_wizard',
	)
);
$pro_features        = array(
	'ultra'                   => array(
		'title'       => __( 'Ultra Smush - 5x Compression', 'wp-smushit' ),
		'description' => __( '5x higher compression for snappier site loads.', 'wp-smushit' ),
	),
	'cdn'                     => array(
		'title'       => __( 'Global Edge CDN', 'wp-smushit' ),
		'description' => sprintf(
			/* translators: %d: total CDN locations */
			__( 'Spread over %d locations for instant worldwide delivery.', 'wp-smushit' ),
			$total_cdn_locations
		),
	),
	'auto_resize'             => array(
		'title'       => __( 'Automatic Image Resizing', 'wp-smushit' ),
		'description' => __( 'Smart auto-resize images to fit image containers perfectly.', 'wp-smushit' ),
	),
	'next_gen'                => array(
		'title'       => __( 'Next-Gen Conversions', 'wp-smushit' ),
		'description' => __( '1-click WebP and AVIF Conversion for superior performance.', 'wp-smushit' ),
	),
	'background_optimization' => array(
		'title'       => __( 'Continuous Background Optimization', 'wp-smushit' ),
		'description' => __( 'Bulk Smush continues even when you leave the page.', 'wp-smushit' ),
	),
);
?>

<script type="text/template" id="smush-onboarding" data-cta-url="<?php echo esc_js( $cta_url ); ?>" data-type="<?php echo WP_Smush::is_pro() ? 'pro' : 'free'; ?>">
	<div class="sui-box-header sui-flatten sui-content-center sui-spacing-sides--90">
		<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
		<figure class="sui-box-banner" aria-hidden="true">
			<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}.png"
				srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}.png 1x, <?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}@2x.png 2x"
				alt="<?php esc_attr_e( 'Smush Onboarding Modal', 'wp-smushit' ); ?>" class="sui-image sui-image-center"
			>
		</figure>
		<?php endif; ?>

		<h3 class="sui-box-title sui-lg" id="smush-title-onboarding-dialog">
			<# if ( data.first_slide === data.slide ) { #>
			<?php

			/* translators: %s: current user name */
			printf( esc_html__( 'Hey, %s!', 'wp-smushit' ), esc_html( Helper::get_user_name() ) );
			?>
			<# } else if ( 'auto' === data.slide ) { #>
			<?php esc_html_e( 'Automatic Compression', 'wp-smushit' ); ?>
			<# } else if ( 'lossy' === data.slide ) { #>
			<?php echo esc_html( $lossy_title ); ?>
			<# } else if ( 'strip_exif' === data.slide ) { #>
			<?php esc_html_e( 'EXIF Metadata', 'wp-smushit' ); ?>
			<# } else if ( 'original' === data.slide ) { #>
			<?php esc_html_e( 'Full Size Images', 'wp-smushit' ); ?>
			<# } else if ( 'lazy_load' === data.slide ) { #>
			<?php esc_html_e( 'Lazy Load', 'wp-smushit' ); ?>
			<# } else if ( 'pro_upsell' === data.slide ) { #>
				<?php esc_html_e( 'Upgrade to Smush Pro', 'wp-smushit' ); ?>
			<# } #>
		</h3>

		<p class="sui-description" id="smush-description-onboarding-dialog">
			<# if ( data.first_slide === data.slide ) { #>
			<?php esc_html_e( "Nice work installing Smush! Let's get started by choosing how you want this plugin to work, and then let Smush do all the heavy lifting for you.", 'wp-smushit' ); ?>
			<# } else if ( 'auto' === data.slide ) { #>
			<?php esc_html_e( 'When you upload images to your site, Smush can automatically optimize and compress them for you saving you having to do this manually.', 'wp-smushit' ); ?>
			<# } else if ( 'lossy' === data.slide ) { #>
			<?php echo esc_html( $lossy_description ); ?>
			<# } else if ( 'strip_exif' === data.slide ) { #>
			<?php esc_html_e( 'Photos often store camera settings in the file, i.e., focal length, date, time and location. Removing EXIF data reduces the file size. Note: it does not strip SEO metadata.', 'wp-smushit' ); ?>
			<# } else if ( 'original' === data.slide ) { #>
			<?php esc_html_e( 'You can also have Smush compress your original images - this is helpful if your theme serves full size images.', 'wp-smushit' ); ?>
			<# } else if ( 'lazy_load' === data.slide ) { #>
			<?php esc_html_e( 'This feature stops offscreen images from loading until a visitor scrolls to them. Make your page load faster, use less bandwidth and fix the “defer offscreen images” recommendation from a Google PageSpeed test.', 'wp-smushit' ); ?>
			<# } else if ( 'pro_upsell' === data.slide ) { #>
				<?php esc_html_e( 'Level Up Your Performance – Smarter, Faster, Effortless!', 'wp-smushit' ); ?>
			<# } #>
		</p>

	</div>

	<div class="sui-box-body sui-content-center sui-spacing-sides--0">
		<# if ( data.first_slide === data.slide ) { #>
			<?php if ( $should_show_tracking_confirmation ) : ?>
				<div class="smush-onboarding-tracking-box">
					<label for="{{{ data.slide }}}" class="sui-checkbox">
						<input
							type="checkbox"
							id="{{{ data.slide }}}"
							aria-labelledby="{{{ data.slide }}}-label"
							<# if ( data.value ) { #>checked<# } #>/>

						<span aria-hidden="true"></span>

						<span id="{{{ data.slide }}}-label">
							<?php
								/* translators: %1$: start bold tag  %2$: end of the bold tag */
								echo sprintf( esc_html__( 'Share %1$sanonymous%2$s usage data to help us improve your Smush experience (recommended).', 'wp-smushit' ), '<strong>', '</strong>' );
							?>
						</span>
					</label>
				</div>
			<?php endif; ?>
			<a class="sui-button sui-button-blue sui-button-icon-right next" onclick="WP_Smush.onboarding.next(this)">
				<?php esc_html_e( 'Begin setup', 'wp-smushit' ); ?>
				<i class="sui-icon-chevron-right" aria-hidden="true"> </i>
			</a>
		<# } else { #>
			<# if ( 'pro_upsell' !== data.slide ) { #>
			<div class="sui-box-selectors">
				<label for="{{{ data.slide }}}" class="sui-toggle">
					<input type="checkbox" id="{{{ data.slide }}}" aria-labelledby="{{{ data.slide }}}-label" <# if ( data.value ) { #>checked<# } #>>
					<span class="sui-toggle-slider" aria-hidden="true"> </span>
					<span id="{{{ data.slide }}}-label" class="sui-toggle-label">
						<# if ( 'auto' === data.slide ) { #>
						<?php esc_html_e( 'Automatically optimize new uploads', 'wp-smushit' ); ?>
						<# } else if ( 'lossy' === data.slide ) { #>
						<?php echo esc_html( $lossy_action_label ); ?>
						<# } else if ( 'strip_exif' === data.slide ) { #>
						<?php esc_html_e( 'Strip my image metadata', 'wp-smushit' ); ?>
						<# } else if ( 'original' === data.slide ) { #>
						<?php esc_html_e( 'Compress my full size images', 'wp-smushit' ); ?>
						<# } else if ( 'lazy_load' === data.slide ) { #>
						<?php esc_html_e( 'Enable Lazy Loading', 'wp-smushit' ); ?>
						<# } #>
					</span>
				</label>
			</div>
			<# } else { #>
			<div class="sui-field-list">
				<div class="sui-field-list-body">
					<?php foreach ( $pro_features as $name => $feature ) : ?>
					<div class="sui-field-list-item">
						<label
							class="sui-field-list-item-label"
							for="<?php echo esc_attr( $name ); ?>"
						>
							<?php echo esc_html( $feature['title'] ); ?>
							<button class="sui-button-icon sui-tooltip sui-tooltip-top-center sui-tooltip-constrained" data-tooltip="<?php echo esc_html( $feature['description'] ); ?>">
								<span class="sui-icon-info" aria-hidden="true"></span>
							</button>
						</label>
						<label class="sui-toggle">
							<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" disabled />
							<span class="sui-toggle-slider"></span>
						</label>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<# } #>
		<# } #>

		<# if ( 'original' === data.slide ) { #>
		<p class="sui-description" style="padding: 0 90px">
			<?php esc_html_e( 'Note: By default we will store a copy of your original uploads just in case you want to revert in the future - you can turn this off at any time.', 'wp-smushit' ); ?>
		</p>
		<# } #>
		<# if ( data.last ) { #>
			<# if ( 'pro_upsell' === data.slide ) { #>
				<div class="smush-onboarding-buttons">
					<div class="smush-onboarding-buttons-inner">
						<a class="sui-button sui-button-blue smush-btn-pro-upsell" target="_blank" href="<?php echo esc_url( $upsell_url ); ?>">
							<?php
							/* translators: %s: plugin discount */
							printf( esc_html__( 'View Plans - %s Off', 'wp-smushit' ), esc_html( $plugin_discount ) );
							?>
						</a>
						<button type="submit" class="sui-button sui-button-grey sui-button-icon-left" data-modal-close="">
							<?php esc_html_e( 'Complete Setup', 'wp-smushit' ); ?>
						</button>
					</div>
				</div>
			<# } else { #>
				<button type="submit" class="sui-button sui-button-blue sui-button-icon-left" data-modal-close="">
					<i class="sui-icon-check" aria-hidden="true"> </i>
					<?php esc_html_e( 'Finish setup wizard', 'wp-smushit' ); ?>
				</button>
			<# } #>
		<# } else if ( data.first_slide !== data.slide && ! data.last ) { #>
		<a class="sui-button sui-button-gray next" onclick="WP_Smush.onboarding.next(this)">
			<?php esc_html_e( 'Next', 'wp-smushit' ); ?>
		</a>
		<# } #>

		<div class="smush-onboarding-arrows">
			<a href="#" class="previous <# if ( data.first ) { #>sui-hidden<# } #>" onclick="WP_Smush.onboarding.next(this)">
				<i class="sui-icon-chevron-left" aria-hidden="true"> </i>
			</a>
			<a href="#" class="next <# if ( data.last ) { #>sui-hidden<# } #>" onclick="WP_Smush.onboarding.next(this)">
				<i class="sui-icon-chevron-right" aria-hidden="true"> </i>
			</a>
		</div>
	</div>

	<div class="sui-box-footer sui-flatten sui-content-center">
		<div class="sui-box-steps sui-sm">
			<button onclick="WP_Smush.onboarding.goTo('usage')" class="<# if ( data.first_slide === data.slide ) { #>sui-current<# } #>" <# if ( data.first_slide === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'First step', 'wp-smushit' ); ?>
			</button>
			<button onclick="WP_Smush.onboarding.goTo('auto')" class="<# if ( 'auto' === data.slide ) { #>sui-current<# } #>" <# if ( 'auto' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'Automatic Compression', 'wp-smushit' ); ?>
			</button>
			<button onclick="WP_Smush.onboarding.goTo('lossy')" class="<# if ( 'lossy' === data.slide ) { #>sui-current<# } #>" <# if ( 'lossy' === data.slide ) { #>disabled<# } #>>
				<?php echo esc_html( $lossy_title ); ?>
			</button>
			<button onclick="WP_Smush.onboarding.goTo('strip_exif')" class="<# if ( 'strip_exif' === data.slide ) { #>sui-current<# } #>" <# if ( 'strip_exif' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'EXIF Metadata', 'wp-smushit' ); ?>
			</button>
			<button onclick="WP_Smush.onboarding.goTo('original')" class="<# if ( 'original' === data.slide ) { #>sui-current<# } #>" <# if ( 'original' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'Full Size Images', 'wp-smushit' ); ?>
			</button>
			<button onclick="WP_Smush.onboarding.goTo('lazy_load')" class="<# if ( 'lazy_load' === data.slide ) { #>sui-current<# } #>" <# if ( 'lazy_load' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'Lazy Load', 'wp-smushit' ); ?>
			</button>
			<?php if ( ! WP_Smush::is_pro() ) : ?>
				<button onclick="WP_Smush.onboarding.goTo('pro_upsell')" class="<# if ( 'pro_upsell' === data.slide ) { #>sui-current<# } #>" <# if ( 'pro_upsell' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'Upgrade to Smush Pro', 'wp-smushit' ); ?>
			</button>
			<?php endif; ?>
		</div>
	</div>
</script>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="smush-onboarding-dialog"
		class="sui-modal-content smush-onboarding-dialog"
		aria-modal="true"
		aria-labelledby="smush-title-onboarding-dialog"
		aria-describedby="smush-description-onboarding-dialog"
	>
		<div class="sui-box">
			<div id="smush-onboarding-content" aria-live="polite"></div>
			<input type="hidden" id="smush_quick_setup_nonce" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'smush_quick_setup' ) ); ?>">
		</div>
		<button class="sui-modal-skip smush-onboarding-skip-link">
			<?php esc_html_e( 'Skip this, I’ll set it up later', 'wp-smushit' ); ?>
		</button>
	</div>
</div>
