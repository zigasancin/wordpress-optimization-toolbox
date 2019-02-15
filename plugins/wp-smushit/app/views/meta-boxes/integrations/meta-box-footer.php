<?php
/**
 * Integrations meta box footer.
 *
 * @package WP_Smush
 *
 * @var bool $show_submit  Show submit button.
 */

?>

<div class="sui-actions-right">
	<span class="wp-smush-submit-wrap">
		<input type="submit" id="wp-smush-save-settings" class="sui-button sui-button-blue" aria-describedby="smush-submit-description" value="<?php esc_attr_e( 'UPDATE SETTINGS', 'wp-smushit' ); ?>" <?php disabled( ! $show_submit, true, false ); ?>>
		<span class="sui-icon-loader sui-loading sui-hidden"></span>
		<span class="smush-submit-note" id="smush-submit-description">
			<?php esc_html_e( 'Smush will automatically check for any images that need re-smushing.', 'wp-smushit' ); ?>
		</span>
	</span>
</div>
