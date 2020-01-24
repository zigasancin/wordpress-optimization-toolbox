<?php
/**
 * Onboarding modal.
 *
 * @since 3.1
 * @package WP_Smush
 */

use Smush\Core\Helper;
use Smush\WP_Smush;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<script type="text/template" id="smush-onboarding" data-type="<?php echo WP_Smush::is_pro() ? 'pro' : 'free'; ?>">
	<div class="sui-box-header sui-flatten sui-content-center sui-spacing-sides--90">
		<?php if ( ! $this->hide_wpmudev_branding() ) : ?>
		<figure class="sui-box-banner" aria-hidden="true">
			<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}.png"
				srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}.png 1x, <?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}@2x.png 2x"
				alt="<?php esc_attr_e( 'Smush Onboarding Modal', 'wp-smushit' ); ?>" class="sui-image sui-image-center"
			>
		</figure>
		<?php endif; ?>

		<h3 class="sui-box-title sui-lg" id="smush-title-onboarding-dialog">
			<# if ( 'start' === data.slide ) { #>
			<?php
			/* translators: %s: current user name */
			printf( esc_html__( 'Hey, %s!', 'wp-smushit' ), esc_html( Helper::get_user_name() ) );
			?>
			<# } else if ( 'auto' === data.slide ) { #>
			<?php esc_html_e( 'Automatic Compression', 'wp-smushit' ); ?>
			<# } else if ( 'lossy' === data.slide ) { #>
			<?php esc_html_e( 'Advanced Compression', 'wp-smushit' ); ?>
			<# } else if ( 'strip_exif' === data.slide ) { #>
			<?php esc_html_e( 'EXIF Metadata', 'wp-smushit' ); ?>
			<# } else if ( 'original' === data.slide ) { #>
			<?php esc_html_e( 'Full Size Images', 'wp-smushit' ); ?>
			<# } else if ( 'lazy_load' === data.slide ) { #>
			<?php esc_html_e( 'Lazy Load', 'wp-smushit' ); ?>
			<# } else if ( 'usage' === data.slide ) { #>
			<?php esc_html_e( 'Usage Data', 'wp-smushit' ); ?>
			<# } #>
		</h3>

		<p class="sui-description" id="smush-description-onboarding-dialog">
			<# if ( 'start' === data.slide ) { #>
			<?php esc_html_e( 'Nice work installing Smush! Let’s get started by choosing how you want this plugin to work, and then let Smush do all the heavy lifting for you.', 'wp-smushit' ); ?>
			<# } else if ( 'auto' === data.slide ) { #>
			<?php esc_html_e( 'When you upload images to your site, Smush can automatically optimize and compress them for you saving you having to do this manually.', 'wp-smushit' ); ?>
			<# } else if ( 'lossy' === data.slide ) { #>
			<?php esc_html_e( 'Optimize images up to 2x more than regular smush with our multi-pass lossy compression.', 'wp-smushit' ); ?>
			<# } else if ( 'strip_exif' === data.slide ) { #>
			<?php esc_html_e( 'Whenever you take a photo, your camera stores metadata, such as focal length, date, time and location, within the image. Removing this data will reduce your image sizes.', 'wp-smushit' ); ?>
			<# } else if ( 'original' === data.slide ) { #>
			<?php esc_html_e( 'You can also have Smush compress your original images - this is helpful if your theme serves full size images.', 'wp-smushit' ); ?>
			<# } else if ( 'lazy_load' === data.slide ) { #>
			<?php esc_html_e( 'This feature defers the loading of below the fold imagery until the page has loaded. This reduces load on your server and speeds up the page load time.', 'wp-smushit' ); ?>
			<# } else if ( 'usage' === data.slide ) { #>
			<?php esc_html_e( 'Help us improve Smush by letting our product designers gain insight into what features need improvement. We don’t track any personalized data, it’s all basic stuff.', 'wp-smushit' ); ?>
			<# } #>
		</p>

	</div>

	<div class="sui-box-body sui-content-center sui-spacing-sides--0">
		<# if ( 'start' === data.slide ) { #>
		<a class="sui-button sui-button-blue sui-button-icon-right next" onclick="WP_Smush.onboarding.next(this)">
			<?php esc_html_e( 'Begin setup', 'wp-smushit' ); ?>
			<i class="sui-icon-chevron-right" aria-hidden="true"> </i>
		</a>
		<# } else { #>
		<div class="sui-box-selectors">
			<label class="sui-toggle">
				<input type="checkbox" id="{{{ data.slide }}}" <# if ( data.value ) { #>checked<# } #>>
				<span class="sui-toggle-slider"> </span>
			</label>
			<label for="{{{ data.slide }}}">
				<# if ( 'auto' === data.slide ) { #>
				<?php esc_html_e( 'Automatically optimize new uploads', 'wp-smushit' ); ?>
				<# } else if ( 'lossy' === data.slide ) { #>
				<?php esc_html_e( 'Enable enhanced multi-pass lossy compression', 'wp-smushit' ); ?>
				<# } else if ( 'strip_exif' === data.slide ) { #>
				<?php esc_html_e( 'Strip my image metadata', 'wp-smushit' ); ?>
				<# } else if ( 'original' === data.slide ) { #>
				<?php esc_html_e( 'Compress my full size images', 'wp-smushit' ); ?>
				<# } else if ( 'lazy_load' === data.slide ) { #>
				<?php esc_html_e( 'Enable Lazy Loading', 'wp-smushit' ); ?>
				<# } else if ( 'usage' === data.slide ) { #>
				<?php esc_html_e( 'Allow usage data tracking', 'wp-smushit' ); ?>
				<# } #>
			</label>
		</div>
		<# } #>

		<# if ( 'original' === data.slide ) { #>
		<p class="sui-description" style="padding: 0 90px">
			<?php esc_html_e( 'Note: By default we will store a copy of your original uploads just in case you want to revert in the future - you can turn this off at any time.', 'wp-smushit' ); ?>
		</p>
		<# } else if ( 'lazy_load' === data.slide ) { #>
		<button type="submit" class="sui-button sui-button-blue sui-button-icon-left" data-modal-close="">
			<i class="sui-icon-check" aria-hidden="true"> </i>
			<?php esc_html_e( 'Finish setup wizard', 'wp-smushit' ); ?>
		</button>
		<# } #>

		<# if ( 'start' !== data.slide && 'lazy_load' !== data.slide ) { #>
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
			<button onclick="WP_Smush.onboarding.goTo('start')" class="<# if ( 'start' === data.slide ) { #>sui-current<# } #>" <# if ( 'start' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'First step', 'wp-smushit' ); ?>
			</button>
			<button onclick="WP_Smush.onboarding.goTo('auto')" class="<# if ( 'auto' === data.slide ) { #>sui-current<# } #>" <# if ( 'auto' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'Automatic Compression', 'wp-smushit' ); ?>
			</button>
			<?php if ( WP_Smush::is_pro() ) : ?>
				<button onclick="WP_Smush.onboarding.goTo('lossy')" class="<# if ( 'lossy' === data.slide ) { #>sui-current<# } #>" <# if ( 'lossy' === data.slide ) { #>disabled<# } #>>
					<?php esc_html_e( 'Advanced Compression', 'wp-smushit' ); ?>
				</button>
			<?php endif; ?>
			<button onclick="WP_Smush.onboarding.goTo('strip_exif')" class="<# if ( 'strip_exif' === data.slide ) { #>sui-current<# } #>" <# if ( 'strip_exif' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'EXIF Metadata', 'wp-smushit' ); ?>
			</button>
			<?php if ( WP_Smush::is_pro() ) : ?>
				<button onclick="WP_Smush.onboarding.goTo('original')" class="<# if ( 'original' === data.slide ) { #>sui-current<# } #>" <# if ( 'original' === data.slide ) { #>disabled<# } #>>
					<?php esc_html_e( 'Full Size Images', 'wp-smushit' ); ?>
				</button>
			<?php endif; ?>
			<button onclick="WP_Smush.onboarding.goTo('lazy_load')" class="<# if ( 'lazy_load' === data.slide ) { #>sui-current<# } #>" <# if ( 'lazy_load' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'Lazy Load', 'wp-smushit' ); ?>
			</button>
			<!--
			<button onclick="WP_Smush.onboarding.goTo('usage')" class="<# if ( 'usage' === data.slide ) { #>sui-current<# } #>" <# if ( 'usage' === data.slide ) { #>disabled<# } #>>
				<?php esc_html_e( 'Usage Data', 'wp-smushit' ); ?>
			</button>
			-->
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
			<?php wp_nonce_field( 'smush_quick_setup' ); ?>
		</div>
		<button class="sui-modal-skip smush-onboarding-skip-link">
			<?php esc_html_e( 'Skip this, I’ll set it up later', 'wp-smushit' ); ?>
		</button>
	</div>
</div>
