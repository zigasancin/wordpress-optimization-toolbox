<?php
$docs_link = $this->get_utm_link(
	array(
		'utm_campaign' => 'smush_troubleshooting_docs_cron_disabled',
	),
	'https://wpmudev.com/docs/wpmu-dev-plugins/smush/#troubleshooting-guide'
);
?>
<div class="sui-notice sui-notice-warning smush-cron-disabled-notice">
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>
			<p>
				<?php
				printf(
					/* translators: %s1$d - Open link, %2$s - Close the link */
					esc_html__( 'It seems cron is disabled on your site. Please enable cron before running Bulk Smush to ensure everything works as expected. %1$sLearn More%2$s', 'wp-smushit' ),
					'<a href="' . esc_url( $docs_link ) . '" target="_blank"><strong>',
					'</strong></a>'
				);
				?>
			</p>
		</div>
		<div class="sui-notice-actions"><button class="sui-button-icon"  data-notice-close="smush-box-cron-disabled-notice" type="button"><span class="sui-icon-check" aria-hidden="true"></span><span class="sui-screen-reader-text"><?php esc_html_e( 'Close this notice', 'wp-smushit' ); ?></span></button></div>
	</div>
</div>
