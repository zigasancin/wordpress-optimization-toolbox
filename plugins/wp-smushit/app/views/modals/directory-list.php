<?php
/**
 * Output the content for Directory smush list dialog content.
 *
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-dialog wp-smush-list-dialog" aria-hidden="true" id="wp-smush-list-dialog">
	<div class="sui-dialog-overlay sui-fade-in" tabindex="0"></div>
	<div class="sui-dialog-content sui-bounce-in" role="dialog">
		<div class="sui-box" role="document">
			<div class="sui-box-header">
				<h3 class="sui-box-title"><?php esc_html_e( 'Choose Directory', 'wp-smushit' ); ?></h3>
				<div class="sui-actions-right">
					<button class="sui-dialog-close" aria-label="<?php esc_attr_e( 'Close', 'wp-smushit' ); ?>"></button>
				</div>
			</div>

			<div class="sui-box-body">
				<p><?php esc_html_e( 'Choose which folder you wish to smush. Smush will automatically include any images in subfolders of your selected folder.', 'wp-smushit' ); ?></p>
				<div class="content"></div>
			</div>

			<div class="sui-box-footer">
				<div class="sui-actions-right">
					<span class="add-dir-loader"></span>
					<button class="sui-modal-close sui-button sui-button-blue wp-smush-select-dir" disabled>
						<?php esc_html_e( 'SMUSH', 'wp-smushit' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
