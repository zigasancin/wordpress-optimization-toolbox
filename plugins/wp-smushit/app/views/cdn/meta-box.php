<?php
/**
 * CDN meta box.
 *
 * @since 3.0
 * @package WP_Smush
 *
 * @var array    $cdn_group   CDN settings keys.
 * @var string   $class       CDN status class (for icon color).
 * @var array    $settings    Settings.
 * @var string   $status      CDN status.
 * @var string   $status_msg  CDN status messages.
 */

use Smush\Core\CDN\CDN_Helper;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<p>
	<?php
	$cdn_message = __( 'Take a load off your server by delivering your images from our blazingly-fast CDN. The Smush CDN is a multi-location network ensuring faster delivery of site content, as users will be served optimized and cached versions of files from the server closest to them.', 'wp-smushit' );
	echo esc_html( $this->whitelabel->whitelabel_string( $cdn_message ) );
	?>
</p>

<div class="sui-notice sui-notice-<?php echo esc_attr( $class ); ?>">
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<i class="sui-notice-icon sui-icon-<?php echo 'enabled' === $status ? 'check-tick' : 'info'; ?> sui-md" aria-hidden="true"></i>
			<p><?php echo wp_kses_post( $status_msg ); ?></p>
		</div>
	</div>
</div>

<div class="sui-box-settings-row" id="cdn-supported-media-types-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label">
			<?php esc_html_e( 'Supported Media Types', 'wp-smushit' ); ?>
		</span>
		<span class="sui-description">
			<?php
			esc_html_e( 'Here’s a list of the media types we serve from the CDN.', 'wp-smushit' );
			?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<span class="smush-filename-extension smush-extension-jpg">
			<?php esc_html_e( 'jpg', 'wp-smushit' ); ?>
		</span>
		<span class="smush-filename-extension smush-extension-png">
			<?php esc_html_e( 'png', 'wp-smushit' ); ?>
		</span>
		<span class="smush-filename-extension smush-extension-gif">
			<?php esc_html_e( 'gif', 'wp-smushit' ); ?>
		</span>
		<?php if ( $settings['webp'] ) : ?>
			<span class="smush-filename-extension smush-extension-webp">
				<?php esc_html_e( 'webp', 'wp-smushit' ); ?>
			</span>
		<?php endif; ?>

		<span class="sui-description">
			<?php
			esc_html_e(
				'At this time, we don’t support videos. We recommend uploading your media to a third-party provider and embedding the videos into your posts/pages.',
				'wp-smushit'
			);
			?>
		</span>
	</div>
</div>

<?php
foreach ( $cdn_group as $name ) {
	if ( 'cdn' === $name ) {
		continue;
	}

	do_action( 'wp_smush_render_setting_row', $name, $settings[ $name ] );
}

$excluded_keywords = CDN_Helper::get_instance()->get_excluded_keywords();
?>

<div class="sui-box-settings-row" id="cdn-excluded-keywords-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label">
			<?php esc_html_e( 'Image Exclusions', 'wp-smushit' ); ?>
		</span>
		<span class="sui-description">
			<?php esc_html_e( 'Prevent specific images from being served via CDN.', 'wp-smushit' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<strong><?php esc_html_e( 'Keywords', 'wp-smushit' ); ?></strong>
			<div class="sui-description">
				<?php esc_html_e( 'Specify keywords from the image code - classes, IDs, filenames, source URLs or any string of characters - to exclude from CDN (case-sensitive).', 'wp-smushit' ); ?>
			</div>
			<?php
			$strings = '';
			if ( ! empty( $excluded_keywords ) ) {
				$strings = join( PHP_EOL, $excluded_keywords );
			}

			$line_height     = 20;
			$padding         = 18;
			$textarea_height = max( 100, count( $excluded_keywords ) * $line_height + $padding );
			$textarea_height = min( 200, $textarea_height );
			?>
			<textarea class="sui-form-control" name="excluded-keywords" placeholder="<?php esc_attr_e( 'Add keywords, one per line', 'wp-smushit' ); ?>" style="height:<?php echo esc_attr( $textarea_height ); ?>px"><?php echo esc_attr( $strings ); ?></textarea>
			<div class="sui-description">
				<?php
				printf(
					/* translators: %1$s - opening strong tag, %2$s - closing strong tag */
					esc_html__( 'Add one keyword per line. E.g. %1$s#image-id%2$s or %1$s.image-class%2$s or %1$slogo_image%2$s or %1$sgo_imag%2$s or %1$sx.com/%2$s or %1$s@url-keyword%2$s', 'wp-smushit' ),
					'<strong>',
					'</strong>'
				);
				?>
			</div>
		</div>
	</div>
</div>

<div class="sui-box-settings-row" id="cdn-deactivate-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label">
			<?php esc_html_e( 'Deactivate', 'wp-smushit' ); ?>
		</span>
		<span class="sui-description">
		<?php
		esc_html_e(
			'If you no longer require your images to be hosted from our CDN, you can disable this feature.',
			'wp-smushit'
		);
		?>
	</span>
	</div>
	<div class="sui-box-settings-col-2">
		<button class="sui-button sui-button-ghost" id="smush-cancel-cdn">
			<span class="sui-loading-text">
				<i class="sui-icon-power-on-off" aria-hidden="true"></i>
				<?php esc_html_e( 'Deactivate', 'wp-smushit' ); ?>
			</span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>
		<span class="sui-description">
		<?php
		esc_html_e(
			'Note: You won’t lose any images by deactivating, all of your attachments are still stored locally on your own server.',
			'wp-smushit'
		);
		?>
		</span>
	</div>
</div>
