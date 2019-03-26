<?php
/**
 * Lazy loading disabled meta box.
 *
 * @since 3.2.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<?php if ( ! $this->hide_wpmudev_branding() ) : ?>
	<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-lazyload-default.png' ); ?>"
		srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-lazyload-default@2x.png' ); ?> 2x"
		alt="<?php esc_html_e( 'Smush CDN', 'wp-smushit' ); ?>">
<?php endif; ?>

<div class="sui-message-content">
	<p>
		<?php
		esc_html_e(
			'This feature defers the loading of below the fold imagery until the page has loaded. This reduces
			load on your server and speeds up the page load time.',
			'wp-smushit'
		);
		?>
	</p>

	<button class="sui-button sui-button-blue" id="smush-enable-lazyload">
		<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wp-smushit' ); ?></span>
		<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
	</button>
</div>

