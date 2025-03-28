<?php
/**
 * Local WebP page.
 *
 * @package Smush\App\Pages
 */

namespace Smush\App\Pages;

use Smush\App\Abstract_Summary_Page;
use Smush\App\Interface_Page;
use Smush\Core\Webp\Webp_Configuration;
use Smush\Core\Next_Gen\Next_Gen_Manager;
use Smush\Core\Next_Gen\Next_Gen_Settings_Ui_Controller;
use WP_Smush;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Next_Gen
 */
class Next_Gen extends Abstract_Summary_Page implements Interface_Page {
	/**
	 * @var Next_Gen_Configuration_Interface
	 */
	private $next_gen_configuration;

	/**
	 * Abstract_Page constructor.
	 *
	 * @param string $slug     Page slug.
	 * @param string $title    Page title.
	 * @param bool   $parent   Does a page have a parent (will be added as a sub menu).
	 */
	public function __construct( $slug, $title, $parent = false ) {
		parent::__construct( $slug, $title, $parent );

		$this->next_gen_configuration = Next_Gen_Manager::get_instance()->get_active_format_configuration();
		$settings_ui_controller       = new Next_Gen_Settings_Ui_Controller();
		$settings_ui_controller->init();

		// Show success message after deleting all webp images.
		add_action( 'wp_smush_header_notices', array( $this, 'maybe_show_deleted_all_files_notice' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 3.9.0
	 *
	 * @param string $hook Hook from where the call is made.
	 */
	public function enqueue_scripts( $hook ) {
		// We only need this script for the wizard.
		if ( ! $this->is_wizard() ) {
			return;
		}

		$this->enqueue_webp_wizard_scripts();
	}

	/**
	 * Enqueue WebP wizard scripts.
	 */
	private function enqueue_webp_wizard_scripts() {
		wp_enqueue_script(
			'smush-react-webp',
			WP_SMUSH_URL . 'app/assets/js/smush-react-webp.min.js',
			array( 'wp-i18n', 'smush-sui', 'clipboard' ),
			WP_SMUSH_VERSION,
			true
		);

		wp_add_inline_script(
			'smush-react-webp',
			'wp.i18n.setLocaleData( ' . wp_json_encode( $this->get_locale_data() ) . ', "wp-smushit" );',
			'before'
		);

		// Defining this here to esc_html before using dangerouslySetInnerHTML on frontend.
		$third_step_message = ! is_multisite()
			? sprintf(
				/* translators: 1. opening 'b' tag, 2. closing 'b' tag */
				esc_html__(
					'WebP versions of existing images in the Media Library can only be created by ‘smushing’ the originals via the Bulk Smush page. Click %1$sConvert Now%2$s to be redirected to the Bulk Smush page to start smushing your images.',
					'wp-smushit'
				),
				'<b>',
				'</b>'
			)
			: sprintf(
				/* translators: 1. opening 'b' tag, 2. closing 'b' tag */
				esc_html__(
					'WebP versions of existing images in the Media Library can only be created by ‘smushing’ the originals using the %1$sBulk Smush%2$s tool on each subsite.',
					'wp-smushit'
				),
				'<b>',
				'</b>'
			);

		$server_configuration = Webp_Configuration::get_instance()->server_configuration();

		wp_localize_script(
			'smush-react-webp',
			'smushReact',
			array(
				'nonce'          => wp_create_nonce( 'wp-smush-webp-nonce' ),
				'isPro'          => WP_Smush::is_pro(),
				'detectedServer' => $server_configuration->get_server_type(),
				'apacheRules'    => $server_configuration->get_apache_code(),
				'nginxRules'     => $server_configuration->get_nginx_code(),
				'startStep'      => ! $server_configuration->is_configured() || ! WP_Smush::is_pro() ? 1 : 3,
				'isMultisite'    => is_multisite(),
				'isWpmudevHost'  => isset( $_SERVER['WPMUDEV_HOSTED'] ),
				'isWhitelabel'   => apply_filters( 'wpmudev_branding_hide_doc_link', false ),
				'isS3Enabled'    => $this->settings->get( 's3' ) && ! WP_Smush::get_instance()->core()->s3->setting_status(),
				'thirdStepMsg'   => $third_step_message,
				'urls'           => array(
					'bulkPage'  => esc_url( admin_url( 'admin.php?page=smush-bulk' ) ),
					'support'   => 'https://wpmudev.com/hub2/support/#get-support',
					'freeImg'   => esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-next-gen-free-tier.png' ),
					'freeImg2x' => esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-next-gen-free-tier@2x.png' ),
					'upsell'    => $this->get_utm_link(
						array(
							'utm_campaign' => 'smush_next-gen_upgrade_button',
						)
					),
				),
			)
		);
	}

	/**
	 * Register meta boxes.
	 */
	public function register_meta_boxes() {
		parent::register_meta_boxes();

		if ( $this->is_wizard() ) {
			return;
		}

		if ( $this->should_disable_local_next_gen() ) {
			$this->add_meta_box(
				'next-gen/disabled',
				__( 'Next-Gen Formats', 'wp-smushit' ),
				null,
				array( $this, 'next_gen_meta_box_header' )
			);

			return;
		}

		$this->add_meta_box(
			'next-gen/next-gen',
			__( 'Next-Gen Formats', 'wp-smushit' ),
			null,
			array( $this, 'next_gen_meta_box_header' ),
			array( $this, 'common_meta_box_footer' )
		);

		$this->modals['next-gen-delete-all'] = array();

		if ( ! is_network_admin() ) {
			// Add modal for Next-Gen activated.
			$auto_start_bulk_smush_url          = $this->settings->has_bulk_smush_page() ? $this->get_url( 'smush-bulk&smush-action=start-bulk-next-gen-conversion#smush-box-bulk' ) : '';
			$this->modals['next-gen-activated'] = array(
				'auto_start_bulk_smush_url' => $auto_start_bulk_smush_url,
			);
			// Add modal for Next-Gen conversion changed.
			$this->modals['next-gen-conversion-changed'] = array(
				'auto_start_bulk_smush_url' => $auto_start_bulk_smush_url,
			);
		}
	}

	private function should_disable_local_next_gen() {
		$is_cdn_active   = $this->settings->is_cdn_active() && $this->settings->has_cdn_page(); // CDN takes precedence because it handles webp anyway
		$next_gen_active = $this->next_gen_configuration->is_activated();
		return $is_cdn_active || ! $next_gen_active;
	}

	/**
	 * WebP meta box header.
	 *
	 * @since 3.8.0
	 */
	public function next_gen_meta_box_header() {
		$this->view(
			'next-gen/meta-box-header',
			array(
				'is_disabled'   => $this->should_disable_local_next_gen(),
				'is_configured' => $this->next_gen_configuration->is_configured(),
			)
		);
	}

	/**
	 * Common footer meta box.
	 */
	public function common_meta_box_footer() {
		$this->view( 'meta-box-footer', array(), 'common' );
	}

	/**
	 * Whether the wizard should be displayed.
	 *
	 * @since 3.9.0
	 *
	 * @return bool
	 */
	protected function is_wizard() {
		$is_free = ! WP_Smush::is_pro();
		return $is_free || $this->next_gen_configuration->should_show_wizard();
	}

	public function maybe_show_deleted_all_files_notice() {
		// Show only when there are images in the library, except on mu, where the count is always 0.
		$core         = WP_Smush::get_instance()->core();
		$global_stats = $core->get_global_stats();
		if ( ! is_multisite() && empty( $global_stats['count_total'] ) ) {
			return;
		}

		$show_message = filter_input( INPUT_GET, 'smush-notice', FILTER_SANITIZE_SPECIAL_CHARS );
		// Success notice after deleting all Next-Gen images.
		if ( 'webp-deleted' === $show_message ) {
			$message = __( 'WebP files were deleted successfully.', 'wp-smushit' );
			echo '<div role="alert" id="wp-smush-webp-delete-all-notice" data-message="' . esc_attr( $message ) . '" class="sui-notice" aria-live="assertive"></div>';
		} elseif ( 'avif-deleted' === $show_message ) {
			$message = __( 'Avif files were deleted successfully.', 'wp-smushit' );
			echo '<div role="alert" id="wp-smush-avif-delete-all-notice" data-message="' . esc_attr( $message ) . '" class="sui-notice" aria-live="assertive"></div>';
		}
	}
}
