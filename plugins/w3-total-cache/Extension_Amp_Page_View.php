<?php
/**
 * File: Extension_Amp_Page_View.php
 *
 * @package W3TC
 */

namespace W3TC;

if ( ! defined( 'W3TC' ) ) {
	die();
}
?>
<form action="admin.php?page=w3tc_extensions&amp;extension=amp&amp;action=view" method="post">
	<?php Util_UI::print_control_bar( 'extension_amp_form_control' ); ?>
	<div class="metabox-holder">
		<?php Util_Ui::postbox_header( esc_html__( 'Configuration', 'w3-total-cache' ), '', 'configuration' ); ?>
		<table class="form-table">
			<?php
			Util_Ui::config_item(
				array(
					'key'               => array( 'amp', 'url_type' ),
					'label'             => esc_html__( 'AMP URL Type:', 'w3-total-cache' ),
					'control'           => 'radiogroup',
					'radiogroup_values' => array(
						'tag'         => 'tag',
						'querystring' => 'query string',
					),
					'description'       => esc_html__(
						'If AMP page URLs are tag based (/my-page/amp/) or query string based (/my-page?amp)',
						'w3-total-cache'
					),
				)
			);
			Util_Ui::config_item(
				array(
					'key'         => array( 'amp', 'url_postfix' ),
					'label'       => esc_html__( 'AMP URL Postfix:', 'w3-total-cache' ),
					'control'     => 'textbox',
					'description' => 'Postfix used',
				)
			);
			?>
		</table>

		<?php Util_Ui::postbox_footer(); ?>
	</div>
</form>
