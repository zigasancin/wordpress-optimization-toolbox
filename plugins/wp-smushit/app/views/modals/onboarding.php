<?php
/**
 * Onboarding modal.
 *
 * @since 3.1
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<script type="text/template" id="smush-onboarding" data-type="<?php echo WP_Smush::is_pro() ? 'pro' : 'free'; ?>">
	<div class="sui-box-header sui-dialog-with-image">
		<?php if ( ! $this->hide_wpmudev_branding() ) : ?>
		<div class="sui-dialog-image" aria-hidden="true">
			<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}.png"
				srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}.png 1x, <?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/onboarding/graphic-onboarding-' ); ?>{{{ data.slide }}}@2x.png 2x"
				alt="<?php esc_attr_e( 'Smush Onboarding Modal', 'wp-smushit' ); ?>" class="sui-image sui-image-center">
		</div>
		<?php endif; ?>

		<h3 class="sui-box-title <?php echo $this->hide_wpmudev_branding() ? 'sui-padding-top' : ''; ?>" id="dialogTitle">
			<# if ( 'start' === data.slide ) { #>
			<?php
			/* translators: %s: current user name */
			printf( esc_html__( 'Hey, %s!', 'wp-smushit' ), esc_html( WP_Smush_Helper::get_user_name() ) );
			?>
			<# } else if ( 'auto' === data.slide ) { #>
			<?php esc_html_e( 'Automatic Compression', 'wp-smushit' ); ?>
			<# } else if ( 'lossy' === data.slide ) { #>
			<?php esc_html_e( 'Advanced Compression', 'wp-smushit' ); ?>
			<# } else if ( 'strip_exif' === data.slide ) { #>
			<?php esc_html_e( 'EXIF Metadata', 'wp-smushit' ); ?>
			<# } else if ( 'original' === data.slide ) { #>
			<?php esc_html_e( 'Full Size Images', 'wp-smushit' ); ?>
			<# } else if ( 'usage' === data.slide ) { #>
			<?php esc_html_e( 'Usage Data', 'wp-smushit' ); ?>
			<# } #>
		</h3>
	</div>

	<div class="sui-box-body">
		<p>
			<# if ( 'start' === data.slide ) { #>
			<?php esc_html_e( 'Nice work installing Smush! Let’s get started by choosing how you want this plugin to work, and then let Smush do all the heavy lifting for you.', 'wp-smushit' ); ?>
			<# } else if ( 'auto' === data.slide ) { #>
			<?php esc_html_e( 'When you upload images to your site, Smush can automatically optimize and compress them for you saving you having to do this manually.', 'wp-smushit' ); ?>
			<# } else if ( 'lossy' === data.slide ) { #>
			<?php esc_html_e( 'Optimize images up to 2x more than regular smush with our multi-pass lossy compression.', 'wp-smushit' ); ?>
			<# } else if ( 'strip_exif' === data.slide ) { #>
			<?php esc_html_e( 'Whenever you take a photo, your camera stores metadata, such as focal length, date, time and location, within the image. Removing this data will reduce your image sizes.', 'wp-smushit' ); ?>
			<# } else if ( 'original' === data.slide ) { #>
			<?php esc_html_e( 'Choose to also compress your original image uploads - helpful if your theme serve full size images.', 'wp-smushit' ); ?>
			<# } else if ( 'usage' === data.slide ) { #>
			<?php esc_html_e( 'Help us improve Smush by letting our product designers gain insight into what features need improvement. We don’t track any personalised data, it’s all basic stuff.', 'wp-smushit' ); ?>
			<# } #>
		</p>

		<# if ( 'start' === data.slide ) { #>
		<a class="sui-button sui-button-blue sui-button-icon-right next" onclick="WP_Smush.onboarding.next(this)">
			<?php esc_html_e( 'Begin setup', 'wp-smushit' ); ?>
			<i class="sui-icon-chevron-right" aria-hidden="true"> </i>
		</a>
		<# } else { #>
		<div class="smush-onboarding-toggle">
			<label class="sui-toggle">
				<input type="checkbox" id="{{{ data.slide }}}" <# if ( data.value ) { #>checked<# } #>>
				<span class="sui-toggle-slider"> </span>
			</label>
			<label for="{{{ data.slide }}}" class="sui-toggle-label">
				<# if ( 'auto' === data.slide ) { #>
				<?php esc_html_e( 'Automatically optimize new uploads', 'wp-smushit' ); ?>
				<# } else if ( 'lossy' === data.slide ) { #>
				<?php esc_html_e( 'Enable enhanced multi-pass lossy compression', 'wp-smushit' ); ?>
				<# } else if ( 'strip_exif' === data.slide ) { #>
				<?php esc_html_e( 'Strip my image meta data', 'wp-smushit' ); ?>
				<# } else if ( 'original' === data.slide ) { #>
				<?php esc_html_e( 'Compress my full size images', 'wp-smushit' ); ?>
				<# } else if ( 'usage' === data.slide ) { #>
				<?php esc_html_e( 'Allow usage data tracking', 'wp-smushit' ); ?>
				<# } #>
			</label>
		</div>
		<# } #>

		<# if ( 'original' === data.slide ) { #>
		<p class="smush-onboarding-note"><?php esc_html_e( 'Note: By default we will store a copy of your original uploads just in case you want to revert in the future - you can turn this off at any time.', 'wp-smushit' ); ?></p>
		<# } else if ( 'usage' === data.slide ) { #>
		<button type="submit" class="sui-button sui-button-blue sui-button-icon-left" data-a11y-dialog-hide>
			<i class="sui-icon-check" aria-hidden="true"> </i>
			<?php esc_html_e( 'Finish setup wizard', 'wp-smushit' ); ?>
		</button>
		<# } #>

		<# if ( 'start' !== data.slide && 'usage' !== data.slide ) { #>
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

	<div class="sui-box-footer">
		<div class="smush-onboarding-dots">
			<a href="#" onclick="WP_Smush.onboarding.goTo('start')">
				<span class="<# if ( 'start' === data.slide ) { #>active<# } #>"> </span>
			</a>
			<a href="#" onclick="WP_Smush.onboarding.goTo('auto')">
				<span class="<# if ( 'auto' === data.slide ) { #>active<# } #>"> </span>
			</a>
			<?php if ( WP_Smush::is_pro() ) : ?>
				<a href="#" onclick="WP_Smush.onboarding.goTo('lossy')">
					<span class="<# if ( 'lossy' === data.slide ) { #>active<# } #>"> </span>
				</a>
			<?php endif; ?>
			<a href="#" onclick="WP_Smush.onboarding.goTo('strip_exif')">
				<span class="<# if ( 'strip_exif' === data.slide ) { #>active<# } #>"> </span>
			</a>
			<?php if ( WP_Smush::is_pro() ) : ?>
				<a href="#" onclick="WP_Smush.onboarding.goTo('original')">
					<span class="<# if ( 'original' === data.slide ) { #>active<# } #>"> </span>
				</a>
			<?php endif; ?>
			<a href="#" onclick="WP_Smush.onboarding.goTo('usage')">
				<span class="<# if ( 'usage' === data.slide ) { #>active<# } #>"> </span>
			</a>
		</div>
	</div>
</script>

<div class="sui-dialog sui-dialog-sm smush-onboarding-dialog" aria-hidden="true" tabindex="-1" id="smush-onboarding-dialog">
	<div class="sui-dialog-overlay sui-fade-in"></div>
	<div class="sui-dialog-content sui-bounce-in" aria-labelledby="dialogTitle" aria-describedby="dialogDescription" role="dialog">
		<div class="sui-box" role="document">
			<div id="smush-onboarding-content"></div>

			<?php wp_nonce_field( 'smush_quick_setup' ); ?>
			<a href="#" class="smush-onboarding-skip-link" data-a11y-dialog-hide>
				<?php esc_html_e( 'Skip this, I’ll set it up later', 'wp-smushit' ); ?>
			</a>
		</div>
	</div>
</div>
