<?php
/**
 * Compression Level.
 *
 * @var $name string Compression field name.
 */
use Smush\Core\Settings;

$next_gen_cdn_setting = $this->settings->get_cdn_next_gen_conversion_mode();
$field_settings       = array(
	'none' => Settings::NONE_CDN_MODE,
	'webp' => Settings::WEBP_CDN_MODE,
	'avif' => Settings::AVIF_CDN_MODE,
);
$field_descriptions   = array(
	Settings::NONE_CDN_MODE => __( 'When enabled, we’ll detect and serve next-gen images to browsers that will accept them by checking Accept Headers, and gracefully fall back to normal PNGs or JPEGs for non-compatible browsers.', 'wp-smushit' ),
	Settings::WEBP_CDN_MODE => __( 'WebP: a modern image format that offers better compatibility across browsers. If a browser doesn’t support WebP, it will show the original image.', 'wp-smushit' ),
	Settings::AVIF_CDN_MODE => __( 'AVIF: a newer image format that provides better compression, reducing file sizes while maintaining quality. If a browser doesn’t support AVIF, it will fall back to the original image.', 'wp-smushit' ),
);

?>
<div id="next-gen-conversion-setting" class="sui-tabs sui-side-tabs wp-smush-cdn-next-gen-conversion-tabs" style="margin-top: 7px; margin-bottom:0;">
	<div role="tablist" class="sui-tabs-menu">
		<?php
		foreach ( $field_settings as $field_name => $field_value ) :
			$field_label = $this->settings->get_cdn_next_gen_conversion_label( $field_value );
			?>
		<button
			type="button"
			role="tab"
			id="next-gen-cdn-<?php echo esc_attr( $field_name ); ?>"
			class="sui-tab-item<?php echo $field_value === $next_gen_cdn_setting ? ' active' : ''; ?>"
			aria-controls="next-gen-cdn-<?php echo esc_attr( $field_name ); ?>-content"
			tabindex="-1">
			<?php echo esc_html( $field_label ); ?>
		</button>
		<input
			type="radio"
			class="sui-screen-reader-text"
			aria-hidden="true"
			name="next-gen-cdn"
			aria-labelledby="next-gen-cdn-label"
			aria-describedby="next-gen-cdn-desc"
			value="<?php echo (int) $field_value; ?>"
			<?php checked( $next_gen_cdn_setting, (int) $field_value, true ); ?> />
		<?php endforeach; ?>
	</div>
	<div class="sui-tabs-content">
		<?php
		foreach ( $field_settings as $field_name => $field_value ) :
			?>
		<div
			role="tabpanel"
			tabindex="0"
			id="next-gen-cdn-<?php echo esc_attr( $field_name ); ?>-content"
			class="sui-tab-content<?php echo $field_value === $next_gen_cdn_setting ? ' active' : ''; ?>"
			aria-labelledby="next-gen-cdn-<?php echo esc_attr( $field_name ); ?>"
			aria-hidden="<?php echo $field_value !== $next_gen_cdn_setting ? 'true' : 'false'; ?>">
			<p class="sui-description">
				<i class="sui-notice-icon sui-icon-info sui-sm" aria-hidden="true" style="margin-right: 6px"></i>
				<?php echo esc_html( $field_descriptions[ $field_value ] ); ?>
			</p>
		</div>
		<?php endforeach; ?>
	</div>
</div>
