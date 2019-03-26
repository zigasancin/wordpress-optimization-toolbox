<?php
/**
 * Pro features meta box.
 *
 * @package WP_Smush
 *
 * @var string $upsell_url  Upsell URL.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<ul class="smush-pro-features">
	<li class="smush-pro-feature-row">
		<div class="smush-pro-feature-title">
			<?php esc_html_e( 'Super-smush lossy compression', 'wp-smushit' ); ?></div>
		<div class="smush-pro-feature-desc">
			<?php
			esc_html_e(
				'Optimize images 2x more than regular smushing and with no visible loss
							in quality using Smush’s intelligent multi-pass lossy compression.',
				'wp-smushit'
			);
			?>
		</div>
	</li>
	<li class="smush-pro-feature-row">
		<div class="smush-pro-feature-title">
			<?php esc_html_e( 'Smush my original full size images', 'wp-smushit' ); ?></div>
		<div class="smush-pro-feature-desc">
			<?php
			esc_html_e(
				'By default, Smush only compresses thumbnails and image sizes generated
							by WordPress. With Smush Pro you can also smush your original images.',
				'wp-smushit'
			);
			?>
		</div>
	</li>
	<li class="smush-pro-feature-row">
		<div class="smush-pro-feature-title">
			<?php esc_html_e( 'Make a copy of my full size images', 'wp-smushit' ); ?></div>
		<div class="smush-pro-feature-desc">
			<?php
			esc_html_e(
				'Save copies of the original full-size images you upload to your site so you
							can restore them at any point. Note: Activating this setting will double the size of the
							uploads folder where your site’s images are stored.',
				'wp-smushit'
			);
			?>
		</div>
	</li>
	<li class="smush-pro-feature-row">
		<div class="smush-pro-feature-title">
			<?php esc_html_e( 'Auto-convert PNGs to JPEGs (lossy)', 'wp-smushit' ); ?></div>
		<div class="smush-pro-feature-desc">
			<?php
			esc_html_e(
				'When you compress a PNG, Smush will check if converting it to JPEG could
							further reduce its size, and do so if necessary,',
				'wp-smushit'
			);
			?>
		</div>
	</li>
	<li class="smush-pro-feature-row">
		<div class="smush-pro-feature-title">
			<?php esc_html_e( 'NextGen Gallery Integration', 'wp-smushit' ); ?></div>
		<div class="smush-pro-feature-desc">
			<?php
			esc_html_e( 'Allow smushing images directly through NextGen Gallery settings.', 'wp-smushit' );
			?>
		</div>
	</li>
</ul>
<div class="sui-upsell-row">
	<img class="sui-image sui-upsell-image sui-upsell-image-smush" src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/smush-promo.png' ); ?>">
	<div class="sui-upsell-notice">
		<p>
			<?php
			printf(
				/* translators: %1$s: starting a tag, %2$s: ending a tag */
				esc_html__(
					'Smush Pro gives you all these extra settings and absolutely not
								limits on smushing your images? Did we mention Smush Pro also gives you up to 2x
								better compression too? %1$sTry it all free%2$s with a WPMU DEV membership today!',
					'wp-smushit'
				),
				'<a href="' . esc_url( $upsell_url ) . '" target="_blank" title="' . esc_html__( 'Try Smush Pro for FREE', 'wp-smushit' ) . '">',
				'</a>'
			);
			?>
		</p>
	</div>
</div>
