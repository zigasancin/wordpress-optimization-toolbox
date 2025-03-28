<div class="sui-notice sui-notice-warning" style="text-align: left" >
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
			<p>
				<?php
				if ( $this->settings->is_cdn_next_gen_conversion_active() ) {
					printf( /* translators: 1: Opening a link, 2: Closing the link */
						esc_html__( 'It looks like your site is already serving WebP and AVIF images via the CDN. Please %1$sdisable the CDN%2$s if you prefer to use Next-Gen Formats instead.', 'wp-smushit' ),
						'<a href="' . esc_url( $this->get_url( 'smush-cdn#smush-cancel-cdn' ) ) . '">',
						'</a>'
					);
				} else {
					printf( /* translators: 1: Opening a link, 2: Closing the link */
						esc_html__( 'It looks like the CDN is enabled. %1$sPlease enable Next-Gen Format%2$s support on the CDN page to serve WebP and AVIF images via the CDN. Or disable the CDN if you wish to use Next-Gen Format standalone.', 'wp-smushit' ),
						'<a href="' . esc_url( $this->get_url( 'smush-cdn#next-gen-conversion-setting' ) ) . '">',
						'</a>'
					);
				}
				?>
			</p>
		</div>
	</div>
</div>
