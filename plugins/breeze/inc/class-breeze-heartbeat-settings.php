<?php

/**
 * Handle Heartbeat options.
 *
 * @since 2.0.2
 */
class Breeze_Heartbeat_Settings {

	/**
	 * Front-end Heartbeat option.
	 *
	 * @var string
	 * @access private
	 * @since 2.0.2
	 */
	private $heartbeat_frontend = '';

	/**
	 * Post Editor Heartbeat option.
	 *
	 * @var string
	 * @access private
	 * @since 2.0.2
	 */
	private $heartbeat_editor = '';

	/**
	 * Back-end Heartbeat option.
	 *
	 * @var string
	 * @access private
	 * @since 2.0.2
	 */
	private $heartbeat_backend = '';

	/**
	 * Whether the option is enabled in Breeze plugin.
	 *
	 * @var bool
	 */
	private $heartbeat_active = false;

	function __construct() {
		$options = $this->fetch_heartbeat_options();

		$this->heartbeat_frontend = $options['front-end'];
		$this->heartbeat_editor   = $options['editor'];
		$this->heartbeat_backend  = $options['back-end'];
		$this->heartbeat_active   = $options['active-status'];

		// Disable script in back-end if setting is set to disable.
		add_action( 'admin_enqueue_scripts', array( &$this, 'deregister_heartbeat_script' ), 99 );
		// Disable script in front-end if setting is set to disable.
		add_action( 'wp_enqueue_scripts', array( &$this, 'deregister_heartbeat_script' ), 99 );
		// Change the timer for heartbeat.
		add_filter( 'heartbeat_settings', array( &$this, 'change_heartbeat_interval' ), 99, 1 );

		add_action( 'enqueue_block_editor_assets', array( $this, 'interval_fix_for_gutenberg' ) );
	}

	/**
	 * Modify the heartbeat interval for Gutenberg editor only.
	 *
	 * @return void
	 */
	public function interval_fix_for_gutenberg() {
		// If the option is not enabled in Breeze, skip this step.
		if ( false === $this->heartbeat_active ) {
			return;
		}

		if (
			'disable' === $this->heartbeat_editor ||
			'default' === $this->heartbeat_editor
		) {
			return;
		}

		// Add inline script
		$inline_script = <<<INLINE_ALTER
document.addEventListener("DOMContentLoaded",(function(){setTimeout((function(){var t=function(){if(void 0!==wp.heartbeat.interval&&"undefined"!=typeof heartbeatSettings&&void 0!==heartbeatSettings.interval){var t=parseInt(heartbeatSettings.interval,10);isNaN(t)||wp.heartbeat.interval(t)}};"undefined"!=typeof jQuery?jQuery(document).on("heartbeat-tick",(function(){t()})):document.addEventListener("heartbeat-tick",(function(){t()})),t()}),1)}));
INLINE_ALTER;
		wp_add_inline_script( 'heartbeat', $inline_script );
	}

	/**
	 * Change the Heartbeat interval timer.
	 *
	 * @return void
	 * @since public
	 * @since 2.0.2
	 * @see https://developer.wordpress.org/reference/hooks/heartbeat_settings/
	 */
	public function change_heartbeat_interval( $settings ) {
		// If the option is not enabled in Breeze, skip this step.

		if ( false === $this->heartbeat_active ) {
			return $settings;
		}

		$location = $this->detect_current_screen();

		if (
			'front-end' === $location &&
			is_numeric( $this->heartbeat_frontend )
		) {
			$settings['interval'] = intval( $this->heartbeat_frontend );
		}

		if (
			'back-end' === $location &&
			is_numeric( $this->heartbeat_backend )
		) {
			$settings['interval'] = intval( $this->heartbeat_backend );
		}

		if (
			'editor' === $location &&
			is_numeric( $this->heartbeat_editor )
		) {
			$settings['interval'] = intval( $this->heartbeat_editor );
		}

		return $settings;
	}

	/**
	 * Disable Heartbeat scrip if setting is set to disable.
	 *
	 * @return void
	 * @access public
	 * @since 2.0.2
	 */
	public function deregister_heartbeat_script() {
		// If the option is not enabled in Breeze, skip this step.
		if ( false === $this->heartbeat_active ) {
			return;
		}

		$location = $this->detect_current_screen();

		$disable_script = false;

		if (
			'front-end' === $location &&
			'disable' === $this->heartbeat_frontend
		) {
			$disable_script = true;
		}

		if (
			'back-end' === $location &&
			'disable' === $this->heartbeat_backend
		) {
			$disable_script = true;
		}

		if (
			'editor' === $location &&
			'disable' === $this->heartbeat_editor
		) {
			$disable_script = true;
		}

		if ( true === $disable_script ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	/**
	 * Used to detect if we are in front-end, back-end or WP editor.
	 *
	 * @return string|void
	 * @access private
	 * @since 2.0.2
	 */
	private function detect_current_screen() {

		if ( ! is_admin() ) {
			return 'front-end';
		}

		if ( is_admin() ) {
			$the_current_url = $_SERVER['REQUEST_URI'];
			preg_match( '/\/wp-admin\/post(-new)?\.php/', $the_current_url, $output_array );

			if ( ! empty( $output_array ) ) {
				return 'editor';
			} else {
				return 'back-end';
			}
		}
	}

	/**
	 * Fetch the Breeze settings for Heartbeat.
	 *
	 * @return array
	 * @access private
	 * @since 2.0.2
	 */
	private function fetch_heartbeat_options(): array {

		if ( is_multisite() ) {
			$get_inherit = get_option( 'breeze_inherit_settings', '1' );
			$is_custom   = filter_var( $get_inherit, FILTER_VALIDATE_BOOLEAN );

			set_as_network_screen();
			if ( true === $is_custom ) {
				$option = get_site_option( 'breeze_heartbeat_settings', array() );
			} else {
				$network_id = get_current_blog_id();
				$option     = get_blog_option( $network_id, 'breeze_heartbeat_settings' );
			}
		} else {
			$option = get_option( 'breeze_heartbeat_settings', array() );
		}

		$heartbeat_options['front-end']     = isset( $option['breeze-heartbeat-front'] ) ? $option['breeze-heartbeat-front'] : '';
		$heartbeat_options['editor']        = isset( $option['breeze-heartbeat-postedit'] ) ? $option['breeze-heartbeat-postedit'] : '';
		$heartbeat_options['back-end']      = isset( $option['breeze-heartbeat-backend'] ) ? $option['breeze-heartbeat-backend'] : '';
		$heartbeat_options['active-status'] = isset( $option['breeze-control-heartbeat'] ) ? filter_var( $option['breeze-control-heartbeat'], FILTER_VALIDATE_BOOLEAN ) : false;

		return $heartbeat_options;
	}
}

new Breeze_Heartbeat_Settings();
