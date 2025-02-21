<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="sui-box-body" style="padding: 6px 25px; display:flex; align-items: center;">
	<img style="margin-right: 15px"  src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/smush-config-icon.png' ); ?>" srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/smush-config-icon.png' ); ?> 1x, <?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/smush-config-icon@2x.png' ); ?> 2x" alt="<?php esc_attr_e( 'Smush Logo', 'wp-smushit' ); ?>"/>
	<p>
		<span style="display:block; margin-bottom:10px;">
			<?php
				printf(
					/* translators: 1: Opening strong tag, 2: Closing strong tag */
					esc_html__( 'Optimization is in progress. Please %1$sdo not close this tab or navigate away%2$s from this page, to avoid process failure.', 'wp-smushit' ),
					'<strong>',
					'</strong>'
				);
			?>
		</span>
	</p>
</div>
