<?php
/**
 * Smush upgrade page class: WP_Smush_Upgrade_Page extends WP_Smush_View.
 *
 * @since 3.2.3
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_Upgrade_Page
 */
class WP_Smush_Upgrade_Page extends WP_Smush_View {

	/**
	 * Render the page.
	 */
	public function render() {
		?>
		<div class="<?php echo $this->settings->get( 'accessible_colors' ) ? 'sui-wrap sui-color-accessible' : 'sui-wrap'; ?>">
			<?php $this->render_inner_content(); ?>
		</div>
		<?php
	}

	/**
	 * On load actions.
	 */
	public function on_load() {
		add_action(
			'admin_enqueue_scripts',
			function() {
				wp_enqueue_script( 'smush-sui', WP_SMUSH_URL . 'app/assets/js/smush-sui.min.js', array( 'jquery' ), WP_SHARED_UI_VERSION, true );
				wp_enqueue_style( 'smush-admin', WP_SMUSH_URL . 'app/assets/css/smush-admin.min.css', array(), WP_SMUSH_VERSION );
			}
		);
	}

	/**
	 * Common hooks for all screens.
	 */
	public function add_action_hooks() {
		add_filter( 'admin_body_class', array( $this, 'smush_body_classes' ) );
	}

	/**
	 * Enqueue admin styles.
	 */
	public function enqueue_styles() {

	}

}
