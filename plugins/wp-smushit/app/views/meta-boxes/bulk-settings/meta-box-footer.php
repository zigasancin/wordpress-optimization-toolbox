<?php
/**
 * Settings meta box footer.
 *
 * @package WP_Smush
 */

?>

<div class="sui-actions-right">
	<span class="wp-smush-submit-wrap">
		<input type="submit" id="wp-smush-save-settings" class="sui-button sui-button-primary" aria-describedby="smush-submit-description" value="<?php esc_attr_e( 'UPDATE SETTINGS', 'wp-smushit' ); ?>">
		<span class="sui-icon-loader sui-loading sui-hidden"></span>
		<span class="smush-submit-note" id="smush-submit-description">
			<?php esc_html_e( 'Smush will automatically check for any images that need re-smushing.', 'wp-smushit' ); ?>
		</span>
	</span>
</div>
