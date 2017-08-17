<?php
function dequeue_woocommerce_cart_fragments() {
    if (is_front_page()) {
        wp_dequeue_script('wc-cart-fragments');
    }
}
add_action('wp_enqueue_scripts', 'dequeue_woocommerce_cart_fragments', 11);
?>