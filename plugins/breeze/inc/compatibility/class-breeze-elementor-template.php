<?php

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

/**
 * Class responsible for integrating Breeze plugin functionality with Elementor templates.
 */
class Breeze_Elementor_Template {
	public function __construct() {
		// Hook into Elementor's "save" action.
		add_action( 'elementor/editor/after_save', array( $this, 'clear_breeze_cache_on_elementor_template_update' ), 10, 2 );
	}

	/**
	 * Clears Breeze cache if the updated Elementor template is of type header or footer.
	 *
	 * This function checks whether the provided Elementor template is classified as a header
	 * or footer. If the template is built with Elementor, it triggers the Breeze cache clearing
	 * mechanism.
	 *
	 * @param int $post_id The ID of the post being saved.
	 * @param array $editor_data Data related to the Elementor editor for the current post.
	 *
	 * @return void
	 */
	function clear_breeze_cache_on_elementor_template_update( int $post_id, array $editor_data ): void {

		// Retrieve the Elementor template type for the current post.
		$_elementor_template_type = get_metadata( 'post', $post_id, '_elementor_template_type', true );

		// Check if it's a header or footer template
		if ( in_array( $_elementor_template_type, array( 'header', 'footer' ), true ) ) {

			// Trigger an action to clear Breeze cache across the site when an eligible Elementor template is updated.
			do_action( 'breeze_clear_all_cache' );
		}
	}
}


/**
 * Loads compatibility for Elementor plugin if the Elementor plugin is active.
 *
 * This function checks if Elementor is installed and activated by verifying
 * the presence of the 'ELEMENTOR_VERSION' constant. If Elementor is active,
 * it initializes the Breeze_Elementor_Template class to enable necessary
 * compatibility features.
 *
 * @return void
 */
function breeze_load_elementor_compatibility() {

	// Check if Elementor is active.
	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		// Init compatibility code.
		new Breeze_Elementor_Template();
	}

}

add_action( 'plugins_loaded', 'breeze_load_elementor_compatibility' );
