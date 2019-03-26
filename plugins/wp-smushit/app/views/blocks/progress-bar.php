<?php
/**
 * Progress bar block.
 *
 * @package WP_Smush
 *
 * @var object $count  WP_Smush_Core
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="wp-smush-bulk-progress-bar-wrapper sui-hidden">
	<p class="wp-smush-bulk-active roboto-medium">
		<?php
		printf(
			/* translators: %1$s: strong opening tag, %2$s: strong closing tag */
			esc_html__( '%1$sBulk smush is currently running.%2$s You need to keep this page open for the process to complete.', 'wp-smushit' ),
			'<strong>',
			'</strong>'
		);
		?>
	</p>

	<div class="sui-notice sui-notice-warning sui-hidden"></div>

	<div class="sui-progress-block sui-progress-can-close">
		<div class="sui-progress">
			<span class="sui-progress-icon" aria-hidden="true">
				<i class="sui-icon-loader sui-loading"></i>
			</span>
			<div class="sui-progress-text">
				<span class="wp-smush-images-percent">0%</span>
			</div>
			<div class="sui-progress-bar">
				<span class="wp-smush-progress-inner" style="width: 0%"></span>
			</div>
		</div>
		<button class="sui-progress-close sui-button-icon sui-tooltip wp-smush-cancel-bulk" type="button" data-tooltip="<?php esc_html_e( 'Stop current bulk smush process.', 'wp-smushit' ); ?>">
			<i class="sui-icon-close"></i>
		</button>
		<button class="sui-progress-close sui-button-icon sui-tooltip wp-smush-all sui-hidden" type="button" data-tooltip="<?php esc_html_e( 'Resume scan.', 'wp-smushit' ); ?>">
			<i class="sui-icon-close"></i>
		</button>
	</div>

	<div class="sui-progress-state">
		<span class="sui-progress-state-text">
			<span>0</span>/<span><?php echo absint( $count->remaining_count ); ?></span> <?php esc_html_e( 'images optimized', 'wp-smushit' ); ?>
		</span>
	</div>

	<div class="sui-box-body sui-no-padding-right sui-hidden">
		<button type="button" class="wp-smush-all sui-button wp-smush-started">
			<?php esc_html_e( 'RESUME', 'wp-smushit' ); ?>
		</button>
	</div>
</div>
