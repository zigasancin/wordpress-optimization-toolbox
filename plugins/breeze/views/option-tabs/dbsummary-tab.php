<?php
/**
 * Basic tab
 */
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

set_as_network_screen();

if ( ! class_exists( 'Breeze_Db_Summary_List_Table' ) ) {
	require_once BREEZE_PLUGIN_DIR . 'inc/helpers/class-breeze-db-summary-table.php';

}
$myListTable = new Breeze_Db_Summary_List_Table();


$summary_icon = BREEZE_PLUGIN_URL . 'assets/images/dbsummary-active.png';


?>
<section>
	<div class="br-section-title">
		<img src="<?php echo $summary_icon; ?>"/>
		<?php _e( 'DATABASE SUMMARY', 'breeze' ); ?>
	</div>
	<div>
		<!-- START OPTION -->
		<div class="br-option-item">
			<div id="dbsummary-content">

				<?php
				// Get statistics data
				echo $myListTable->get_statistics();

				// Invoke table helper
				$myListTable->prepare_items();
				$myListTable->display();
				?>

			</div>
		</div>
		<!-- END OPTION -->
	</div>

</section>
