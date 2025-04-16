<?php
/**
 * File: UsageStatistics_Page_View_Disabled.php
 *
 * @package W3TC
 */

namespace W3TC;

if ( ! defined( 'W3TC' ) ) {
	die();
}

require W3TC_INC_DIR . '/options/common/header.php';
?>
<div class="metabox-holder ustats_ad_metabox">
	<?php Util_Ui::postbox_header( esc_html__( 'Usage Statistics', 'w3-total-cache' ) ); ?>

	<div class="ustats_ad">
		<?php require __DIR__ . '/UsageStatistics_Page_View_Ad.php'; ?>

		<a class="button-primary"
			href="admin.php?page=w3tc_general#stats"><?php esc_html_e( 'Enable here', 'w3-total-cache' ); ?></a>
	</div>

	<?php Util_Ui::postbox_footer(); ?>
</div>
