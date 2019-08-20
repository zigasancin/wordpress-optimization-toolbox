<?php
/**
 * Pro features meta box header.
 *
 * @package WP_Smush
 *
 * @var string $title        Title.
 * @var string $upgrade_url  Upgrade URL.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<h3 class="sui-box-title">
	<i class="sui-icon-smush" aria-hidden="true"></i>
	<?php echo esc_html( $title ); ?>
	<span class="sui-tag sui-tag-pro">Pro</span>
</h3>
<div class="sui-actions-right">
	<a class="sui-button sui-button-purple sui-tooltip sui-tooltip-constrained" target="_blank" href="<?php echo esc_url( $upgrade_url ); ?>" data-tooltip="<?php esc_attr_e( 'Join WPMU DEV to unlock all Pro features for FREE today', 'wp-smushit' ); ?>">
		<?php esc_html_e( 'Try Smush pro for Free', 'wp-smushit' ); ?>
	</a>
</div>
