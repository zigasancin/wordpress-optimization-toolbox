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
	<?php echo esc_html( $title ); ?>
</h3>
<div class="sui-actions-right">
	<a class="sui-button sui-button-green sui-tooltip" target="_blank" href="<?php echo esc_url( $upgrade_url ); ?>" data-tooltip="<?php esc_attr_e( 'Join WPMU DEV to try Smush Pro for free.', 'wp-smushit' ); ?>">
		<?php esc_html_e( 'UPGRADE TO PRO', 'wp-smushit' ); ?>
	</a>
</div>
