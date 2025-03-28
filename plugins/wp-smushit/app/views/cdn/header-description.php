<p>
	<?php
	$cdn_resize_message = __(
		'Multiply the speed and savings! Upload huge images and the Smush CDN will perfectly resize the files, safely convert to a Next-Gen format (WebP or AVIF), and deliver them directly to your visitors from our blazing-fast multi-location global servers.',
		'wp-smushit'
	);
	echo esc_html( $this->whitelabel->whitelabel_string( $cdn_resize_message ) );
	?>
</p>
