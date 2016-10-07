<?php
// Place inside your theme's functions.php file
add_filter('robots_txt', function($output, $public) {
    $output .= "Disallow: /*add-to-cart=*";
    return $output;
}, 10, 2);
?>