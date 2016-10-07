<?php
// Place inside your theme's functions.php file
add_filter('amp_post_template_metadata', function ($metadata, $post) {
    if (!array_key_exists('image', $metadata)) {
		$metadata['image'] = array(
			'@type' => 'ImageObject',
			'url' => 'https://www.yoururl.com/images/image.jpg',
			// Values in pixels - 512px is the recommended minimum width and height for Google AMP
			'height' => 512,
			'width' => 512,
		);
    }

    return $metadata;
}, 10, 2 );
?>