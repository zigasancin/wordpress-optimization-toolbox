<?php

namespace Smush\Core\Next_Gen;

use Smush\Core\Controller;
use Smush\App\Settings_Row;
use Smush\Core\Modules\Helpers\WhiteLabel;
use Smush\Core\Helper;

class Next_Gen_Settings_Ui_Controller extends Controller {
	const NEXT_GEN_FORMATS_FIELD_PRIORITY = 10;
	const LEGACY_BROWSER_SUPPORT_FIELD_PRIORITY = 20;
	const SUPPORTED_MEDIA_TYPES_FIELD_PRIORITY = 30;
	const REVERT_NEXT_GEN_CONVERSION_FIELD_PRIORITY = 40;
	const DEACTIVATE_BUTTON_PRIORITY = 50;

	/**
	 * @var Next_Gen_Manager
	 */
	private $next_gen_manager;

	/**
	 * @var Next_Gen_Configuration_Interface
	 */
	private $next_gen_configuration;

	/**
	 * @var WhiteLabel
	 */
	private $white_label;

	public function __construct() {
		$this->white_label            = new WhiteLabel();
		$this->next_gen_manager       = Next_Gen_Manager::get_instance();
		$this->next_gen_configuration = $this->next_gen_manager->get_active_format_configuration();

		$this->register_action( 'wp_smush_next_gen_formats_settings', array( $this, 'add_next_gen_formats_field' ), self::NEXT_GEN_FORMATS_FIELD_PRIORITY );
		$this->register_action( 'wp_smush_next_gen_formats_settings', array( $this, 'add_legacy_browser_support_field' ), self::LEGACY_BROWSER_SUPPORT_FIELD_PRIORITY );
		$this->register_action( 'wp_smush_next_gen_formats_settings', array( $this, 'add_supported_media_types_field' ), self::SUPPORTED_MEDIA_TYPES_FIELD_PRIORITY );
		$this->register_action( 'wp_smush_next_gen_formats_settings', array( $this, 'add_revert_next_gen_conversion_field' ), self::REVERT_NEXT_GEN_CONVERSION_FIELD_PRIORITY );
		$this->register_action( 'wp_smush_next_gen_formats_settings', array( $this, 'add_deactivate_button' ), self::DEACTIVATE_BUTTON_PRIORITY );
	}

	public function add_next_gen_formats_field() {
		$settings_row = new Settings_Row(
			array( $this, 'get_next_gen_formats_field_title' ),
			__( 'Choose between WebP and AVIF next-gen formats, offering superior quality and compression for faster load times and better performance.', 'wp-smushit' ),
			array( $this, 'get_next_gen_formats_field_content' ),
			array(
				'id' => 'next-gen-next-gen-formats-settings-row',
			)
		);

		$settings_row->render();
	}

	public function add_legacy_browser_support_field() {
		$settings_row = new Settings_Row(
			__( 'Legacy Browser Support', 'wp-smushit' ),
			__( 'Use JavaScript to serve original image files to unsupported browsers.', 'wp-smushit' ),
			array( $this, 'get_legacy_browser_support_field_content' ),
			array(
				'id'    => 'next-gen-legacy-browser-support-settings-row',
				'class' => $this->next_gen_configuration->direct_conversion_enabled() ? '' : 'sui-hidden',
			)
		);

		$settings_row->render();
	}

	public function add_supported_media_types_field() {
		$settings_row = new Settings_Row(
			__( 'Supported Media Types', 'wp-smushit' ),
			array( $this, 'get_supported_media_types_field_description' ),
			array( $this, 'get_supported_media_types_field_content' ),
			array(
				'id' => 'next-gen-supported-media-types-settings-row',
			)
		);

		$settings_row->render();
	}

	public function add_revert_next_gen_conversion_field() {
		$settings_row = new Settings_Row(
			__( 'Revert Next-Gen Conversion', 'wp-smushit' ),
			__( 'If your server storage space is full, use this feature to revert the Next-Gen conversions by deleting all generated files. The files will fall back to normal PNGs or JPEGs once you delete them.', 'wp-smushit' ),
			array( $this, 'get_revert_next_gen_conversion_field_content' ),
			array(
				'id' => 'next-gen-revert-conversion-settings-row',
			)
		);

		$settings_row->render();
	}

	public function add_deactivate_button() {
		$settings_row = new Settings_Row(
			__( 'Deactivate', 'wp-smushit' ),
			array( $this, 'get_deactivate_button_description' ),
			array( $this, 'get_deactivate_button_content' ),
			array(
				'id' => 'next-gen-deactivate-settings-row',
			)
		);

		$settings_row->render();
	}

	public function get_deactivate_button_description() {
		/* translators: %s: Next-Gen format name */
		printf( esc_html__( 'If you no longer require your images to be served in %s format, you can disable this feature.', 'wp-smushit' ), esc_html( $this->next_gen_configuration->get_format_name() ) );
	}

	public function get_deactivate_button_content() {
		?>
		<button class="sui-button sui-button-ghost" id="smush-toggle-<?php echo esc_attr( $this->next_gen_configuration->get_format_key() ); ?>-button" data-action="disable">
			<span class="sui-loading-text">
				<i class="sui-icon-power-on-off" aria-hidden="true"></i><?php esc_html_e( 'Deactivate', 'wp-smushit' ); ?>
			</span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>

		<span class="sui-description">
			<?php
			/* translators: %s: Next-Gen format name */
			printf( esc_html__( "Deactivation won't delete existing %s images.", 'wp-smushit' ), esc_html( $this->next_gen_configuration->get_format_name() ) );
			?>
		</span>
		<?php
	}

	public function get_revert_next_gen_conversion_field_content() {
		?>
		<button
				type="button"
				class="sui-button sui-button-ghost"
				id="wp-smush-<?php echo esc_attr( $this->next_gen_configuration->get_format_key() ); ?>-delete-all-modal-open"
				data-modal-open="wp-smush-wp-delete-all-dialog"
				data-modal-close-focus="wp-smush-<?php echo esc_attr( $this->next_gen_configuration->get_format_key() ); ?>-delete-all-modal-open"
		>
			<span class="sui-loading-text">
				<i class="sui-icon-trash" aria-hidden="true"></i>
				<?php
				/* translators: %s: Next-Gen format name */
				printf( esc_html__( 'Delete %s Files', 'wp-smushit' ), esc_html( $this->next_gen_configuration->get_format_name() ) ); ?>
			</span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>

		<span class="sui-description">
			<?php
			esc_html_e( 'Note: This feature won’t delete the WebP and AVIF files converted via CDN, only the files generated via the Next-Gen Formats feature.', 'wp-smushit' );
			?>
		</span>
		<?php
	}

	public function get_supported_media_types_field_description() {
		/* translators: %s: Next-Gen format name */
		printf( esc_html__( 'Here\'s a list of the media types that will be converted to %s format.', 'wp-smushit' ), esc_html( $this->next_gen_configuration->get_format_name() ) );
	}

	public function get_supported_media_types_field_content() {
		?>
		<div class="sui-form-field">
			<span class="smush-filename-extension smush-extension-jpg">
				<?php esc_html_e( 'jpg', 'wp-smushit' ); ?>
			</span>
			<span class="smush-filename-extension smush-extension-png">
				<?php esc_html_e( 'png', 'wp-smushit' ); ?>
			</span>
			<?php if ( ! $this->white_label->hide_doc_link() ) : ?>
				<span class="sui-description">
					<?php
					$doc_link = Helper::get_utm_link(
						array( 'utm_campaign' => 'smush_pluginlist_docs' ),
						'https://wpmudev.com/docs/wpmu-dev-plugins/smush/#verifying-next-gen-output'
					);

					printf(
					/* translators: 2. opening 'a' tag to docs, 3. closing 'a' tag. */
						esc_html__( 'To verify if the JPG and PNG images are being served correctly as %1$s files, please refer to our %2$sDocumentation%3$s.', 'wp-smushit' ),
						esc_html( $this->next_gen_configuration->get_format_name() ),
						'<a href="' . esc_url( $doc_link ) . '" target="_blank">',
						'</a>'
					);
					?>
				</span>
			<?php endif; ?>
		</div>
		<?php
	}

	public function get_legacy_browser_support_field_content() {
		?>
		<div class="sui-form-field">
			<label for="next-gen-fallback" class="sui-toggle">
				<input
						type="checkbox"
						id="next-gen-fallback"
						name="next-gen-fallback"
						aria-labelledby="next-gen-fallback-label"
						aria-describedby="next-gen-fallback-description"
					<?php checked( $this->next_gen_configuration->is_fallback_activated() ); ?>
				/>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="next-gen-fallback-label" class="sui-toggle-label">
					<?php esc_html_e( 'Enable JavaScript Fallback', 'wp-smushit' ); ?>
				</span>
				<span class="sui-description">
					<?php
					printf(
					/* translators: 1: Opening a link, 2: Closing a link */
						esc_html__( 'Enable this option to serve original files to unsupported browsers. %1$sCheck Browser Compatibility%2$s.', 'wp-smushit' ),
						'<a target="_blank" href="https://caniuse.com/"' . esc_html( $this->next_gen_configuration->get_format_key() ) . '>',
						'</a>'
					);
					?>
				</span>
			</label>
		</div>
		<?php
	}

	public function get_next_gen_formats_field_title() {
		esc_html_e( 'Next-Gen Formats', 'wp-smushit' );
		?>
		<span class="sui-tag smush-sui-tag-new" style="margin-left:5px; font-size:10px;line-height: 12px;border-radius: 12px;padding-top:3px"><?php esc_html_e( 'New', 'wp-smushit' ); ?></span>
		<?php
	}

	public function get_next_gen_formats_field_content() {
		?>
		<div class="sui-form-field">
			<div class="sui-tabs sui-side-tabs">
				<div role="tablist" class="sui-tabs-menu">
					<?php
					foreach ( $this->next_gen_manager->get_configuration_objects() as $module ) :
						$toggle_fields = array();
						$require_legacy_browser_support_field = ! $module->support_server_configuration();
						if ( $require_legacy_browser_support_field ) {
							$toggle_fields['next-gen-fallback'] = 'show';
						}

						$is_selected = $module->get_format_key() === $this->next_gen_manager->get_active_format_key();
						?>
						<button
								type="button"
								role="tab"
								id="<?php echo esc_attr( $module->get_format_key() ); ?>_tab"
								class="sui-tab-item <?php echo esc_attr( $is_selected ? 'active' : '' ); ?>"
								aria-controls="<?php echo esc_attr( $module->get_format_key() ); ?>_content"
								aria-selected="<?php echo esc_attr( $is_selected ? 'true' : 'false' ); ?>">
							<?php echo esc_html( $module->get_format_name() ); ?>
						</button>
						<input
								type="radio"
								name="next-gen-format"
								value="<?php echo esc_attr( $module->get_format_key() ); ?>"
								class="sui-screen-reader-text"
								aria-label="<?php echo esc_attr( $module->get_format_name() ); ?>"
								data-toggle-fields='<?php echo wp_json_encode( $toggle_fields ); ?>'
								aria-hidden="true"
							<?php checked( $module->is_activated() ); ?>
						/>
					<?php endforeach; ?>
				</div>

				<div class="sui-tabs-content">
					<?php
					foreach ( $this->next_gen_manager->get_configuration_objects() as $module ) :
						if ( ! $module->support_server_configuration() ) {
							continue;
						}

						$method_name = 'direct_conversion';
						if ( $module->is_activated() ) {
							$method_name = $module->direct_conversion_enabled() ? 'direct_conversion' : 'rewrite_rule';
						}
						?>
						<div
								role="tabpanel"
								id="<?php echo esc_attr( $module->get_format_key() ); ?>_content"
								class="sui-tab-content <?php echo esc_attr( $module->is_activated() ? 'active' : '' ); ?>"
								aria-labelledby="<?php echo esc_attr( $module->get_format_key() ); ?>_tab"
								tabindex="0"
							<?php echo $module->is_activated() ? '' : 'hidden'; ?>>
							<div class="sui-border-frame" style="padding-top:22px">
								<div class="sui-form-field" style="margin-bottom: 4px" ;>
									<label for="direct_conversion" class="sui-radio">
										<input
												type="radio"
												name="next-gen-method"
												id="direct_conversion"
												aria-labelledby="label-direct_conversion"
												value="direct_conversion"
											<?php checked( $method_name, 'direct_conversion' ); ?>
												data-toggle-fields='<?php echo wp_json_encode( array( 'next-gen-fallback' => 'show' ) ); ?>'
										/>
										<span aria-hidden="true"></span>
										<span id="label-direct_conversion"><?php esc_html_e( 'Direct Conversion (Recommended)', 'wp-smushit' ); ?></span>
									</label>
									<p class="sui-description"
									   style="margin:0 0 0 26px"><?php esc_html_e( 'Serve Next-Gen images directly from your server to supported browsers without any server configuration.', 'wp-smushit' ); ?></p>
								</div>
								<div class="sui-form-field" style="margin-bottom: 4px">
									<label for="rewrite_rule" class="sui-radio">
										<input
												type="radio"
												name="next-gen-method"
												id="rewrite_rule"
												aria-labelledby="label-rewrite_rule"
												value="rewrite_rule"
											<?php checked( $method_name, 'rewrite_rule' ); ?>
												data-toggle-fields='<?php echo wp_json_encode( array( 'next-gen-fallback' => 'hide' ) ); ?>'
										/>
										<span aria-hidden="true"></span>
										<span id="label-rewrite_rule"><?php esc_html_e( 'Server Configuration', 'wp-smushit' ); ?></span>
									</label>
									<p class="sui-description"
									   style="margin:0 0 0 26px"><?php esc_html_e( 'Serve Next-Gen images directly from your server to supported browsers. Requires server configuration.', 'wp-smushit' ); ?></p>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
