<?php

/**
 * Disabled minification for HTML, CSS and JS.
 */

function amp_breeze_compat() {

	if ( true === breeze_is_amp_page() ) {
		// Disable html minification.
		add_filter( 'breeze_filter_html_noptimize', '__return_true' );

		// Disable script minification.
		add_filter( 'breeze_filter_js_noptimize', '__return_true' );

		// Disable style minification.
		add_filter( 'breeze_filter_css_noptimize', '__return_true' );

		// Force native lazy loading on AMP.
		add_filter( 'breeze_use_native_lazy_load', '__return_true' );
	}

}

add_action( 'wp', 'amp_breeze_compat' );
