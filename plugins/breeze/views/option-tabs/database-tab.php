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

$icon         = BREEZE_PLUGIN_URL . 'assets/images/database-active.png';
$summary_icon = BREEZE_PLUGIN_URL . 'assets/images/dbsummary-active.png';

$post_revisions          = 0;
$drafted                 = 0;
$trashed                 = 0;
$comments_trash          = 0;
$comments_spam           = 0;
$trackbacks              = 0;
$transients              = 0;
$comments_unapproved     = 0;
$comments_orphan_meta    = 0;
$comments_duplicate_meta = 0;

$expired_transients   = 0;
$orphan_post_meta     = 0;
$duplicated_post_meta = 0;
$oembed_post          = 0;
$orphan_user_meta     = 0;
$duplicate_user_meta  = 0;
$orphan_term_meta     = 0;
$duplicate_term_meta  = 0;
$tables_to_optimize   = 0;

global $wpdb;

$total_no = 0;

if ( is_multisite() && is_network_admin() ) {
	// Count items from all network sites.
	$sites = get_sites(
		array(
			'fields' => 'ids',
		)
	);

	foreach ( $sites as $blog_id ) {
		switch_to_blog( $blog_id );
		$post_revisions += Breeze_Configuration::get_element_to_clean( 'revisions' );
		$drafted        += Breeze_Configuration::get_element_to_clean( 'drafted' );
		$trashed        += Breeze_Configuration::get_element_to_clean( 'trash' );
		$comments_trash += Breeze_Configuration::get_element_to_clean( 'comments_trash' );
		$comments_spam  += Breeze_Configuration::get_element_to_clean( 'comments_spam' );
		$trackbacks     += Breeze_Configuration::get_element_to_clean( 'trackbacks' );
		$transients     += Breeze_Configuration::get_element_to_clean( 'transient' );
		/**
		 * @since 2.0.7
		 */
		$comments_unapproved     += Breeze_Configuration::get_element_to_clean( 'comments_unapproved' );
		$comments_orphan_meta    += Breeze_Configuration::get_element_to_clean( 'comments_orphan_meta' );
		$comments_duplicate_meta += Breeze_Configuration::get_element_to_clean( 'comments_duplicate_meta' );
		$orphan_post_meta        += Breeze_Configuration::get_element_to_clean( 'orphan_post_meta' );
		$oembed_post             += Breeze_Configuration::get_element_to_clean( 'oembed_cache' );
		$duplicated_post_meta    += Breeze_Configuration::get_element_to_clean( 'duplicated_post_meta' );
		$expired_transients      += Breeze_Configuration::get_element_to_clean( 'expired_transients' );
		$orphan_user_meta        += Breeze_Configuration::get_element_to_clean( 'orphan_user_meta' );
		$duplicate_user_meta     += Breeze_Configuration::get_element_to_clean( 'duplicated_user_meta' );
		$orphan_term_meta        += Breeze_Configuration::get_element_to_clean( 'orphan_term_meta' );
		$duplicate_term_meta     += Breeze_Configuration::get_element_to_clean( 'duplicated_term_meta' );
		restore_current_blog();
	}
	$tables_to_optimize      += Breeze_Configuration::get_element_to_clean( 'optimize_database' );
} else {
	// Count items from the current site.
	$post_revisions = Breeze_Configuration::get_element_to_clean( 'revisions' );
	$drafted        = Breeze_Configuration::get_element_to_clean( 'drafted' );
	$trashed        = Breeze_Configuration::get_element_to_clean( 'trash' );
	$comments_trash = Breeze_Configuration::get_element_to_clean( 'comments_trash' );
	$comments_spam  = Breeze_Configuration::get_element_to_clean( 'comments_spam' );
	$trackbacks     = Breeze_Configuration::get_element_to_clean( 'trackbacks' );
	$transients     = Breeze_Configuration::get_element_to_clean( 'transient' );
	/**
	 * @since 2.0.7
	 */
	$comments_unapproved     = Breeze_Configuration::get_element_to_clean( 'comments_unapproved' );
	$comments_orphan_meta    = Breeze_Configuration::get_element_to_clean( 'comments_orphan_meta' );
	$comments_duplicate_meta = Breeze_Configuration::get_element_to_clean( 'comments_duplicate_meta' );
	$orphan_post_meta        = Breeze_Configuration::get_element_to_clean( 'orphan_post_meta' );
	$oembed_post             = Breeze_Configuration::get_element_to_clean( 'oembed_cache' );
	$duplicated_post_meta    = Breeze_Configuration::get_element_to_clean( 'duplicated_post_meta' );
	$expired_transients      = Breeze_Configuration::get_element_to_clean( 'expired_transients' );
	$orphan_user_meta        = Breeze_Configuration::get_element_to_clean( 'orphan_user_meta' );
	$duplicate_user_meta     = Breeze_Configuration::get_element_to_clean( 'duplicated_user_meta' );
	$orphan_term_meta        = Breeze_Configuration::get_element_to_clean( 'orphan_term_meta' );
	$duplicate_term_meta     = Breeze_Configuration::get_element_to_clean( 'duplicated_term_meta' );
	$tables_to_optimize      = Breeze_Configuration::get_element_to_clean( 'optimize_database' );
}

$total_no = $post_revisions +
			$drafted +
			$trashed +
			$comments_trash +
			$comments_spam +
			$trackbacks +
			$transients +
			$comments_unapproved +
			$comments_orphan_meta +
			$comments_duplicate_meta +
			$orphan_post_meta +
			$orphan_post_meta +
			$oembed_post +
			$duplicated_post_meta +
			$expired_transients +
			$orphan_user_meta +
			$duplicate_user_meta +
			$orphan_term_meta +
			$duplicate_term_meta +
			$tables_to_optimize;

$is_optimize_disabled = is_multisite() && ! is_network_admin() && '0' !== get_option( 'breeze_inherit_settings' );

$sections_actions = array(
	'auto_drafts'             => array(
		'title'    => __( 'Auto Drafts', 'breeze' ),
		'describe' => __( 'Remove all post/pages auto drafts from DB', 'breeze' ),
		'no'       => $drafted,
	),
	'all_transients'          => array(
		'title'    => __( 'All Transients', 'breeze' ),
		'describe' => __( 'Delete expired and active transients from the WordPress database.', 'breeze' ),
		'no'       => $transients,
	),
	'duplicated_post_meta'    => array( // @since 2.0.7
		'title'    => __( 'Duplicated Posts metadata', 'breeze' ),
		'describe' => __( 'Remove all duplicated posts metadata from DB', 'breeze' ),
		'no'       => $duplicated_post_meta,
	),
	'comments_duplicate_meta' => array( // @since 2.0.7
		'title'    => __( 'Duplicated Comments metadata', 'breeze' ),
		'describe' => __( 'Remove all the comments metadata which are duplicated from DB', 'breeze' ),
		'no'       => $comments_duplicate_meta,
	),
	'duplicated_user_meta'    => array( // @since 2.0.7
		'title'    => __( 'Duplicated User metadata', 'breeze' ),
		'describe' => __( 'Remove duplicated user metadata.', 'breeze' ),
		'no'       => $duplicate_user_meta,
	),
	'duplicated_term_meta'    => array( // @since 2.0.7
		'title'    => __( 'Duplicated Term metadata', 'breeze' ),
		'describe' => __( 'Remove duplicated term(taxonomies) metadata.', 'breeze' ),
		'no'       => $duplicate_term_meta,
	),
	'expired_transients'      => array( // @since 2.0.7
		'title'    => __( 'Expired Transients', 'breeze' ),
		'describe' => __( 'Delete expired transients from the WordPress database.', 'breeze' ),
		'no'       => $expired_transients,
	),
	'oembed_cache'            => array( // @since 2.0.7
		'title'    => __( 'oEmbed in Posts metadata', 'breeze' ),
		'describe' => __( 'Remove oEmbed cache in posts metadata from DB', 'breeze' ),
		'no'       => $oembed_post,
	),
	'orphan_post_meta'        => array( // @since 2.0.7
		'title'    => __( 'Orphaned Posts metadata', 'breeze' ),
		'describe' => __( 'Remove all orphaned posts metadata from DB', 'breeze' ),
		'no'       => $orphan_post_meta,
	),
	'orphan_user_meta'        => array( // @since 2.0.7
		'title'    => __( 'Orphan User metadata', 'breeze' ),
		'describe' => __( 'Remove orphan user metadata.', 'breeze' ),
		'no'       => $orphan_user_meta,
	),

	'orphan_term_meta'        => array( // @since 2.0.7
		'title'    => __( 'Orphan Term metadata', 'breeze' ),
		'describe' => __( 'Remove orphan term(taxonomies) metadata.', 'breeze' ),
		'no'       => $orphan_term_meta,
	),
	'comments_orphan_meta'    => array( // @since 2.0.7
		'title'    => __( 'Orphaned Comments metadata', 'breeze' ),
		'describe' => __( 'Remove all the comments metadata which are orphaned from DB', 'breeze' ),
		'no'       => $comments_orphan_meta,
	),
	'optimize_database'       => array( // @since 2.0.7
		'title'    => __( 'Optimize database', 'breeze' ),
		'describe' => __( 'Try to optimize database tables.', 'breeze' ),
		'no'       => $tables_to_optimize,
	),
	'post_revisions'          => array(
		'title'    => __( 'Post Revisions', 'breeze' ),
		'describe' => __( 'Remove all post/pages revisions from DB', 'breeze' ),
		'no'       => $post_revisions,
	),
	'spam_comments'           => array(
		'title'    => __( 'Spam Comments', 'breeze' ),
		'describe' => __( 'Remove all the comments that are considered spam from DB', 'breeze' ),
		'no'       => $comments_spam,
	),
	'trashed_posts'           => array(
		'title'    => __( 'Trashed Posts', 'breeze' ),
		'describe' => __( 'Remove all trashed posts from DB', 'breeze' ),
		'no'       => $trashed,
	),
	'trashed_comments'        => array(
		'title'    => __( 'Trashed Comments', 'breeze' ),
		'describe' => __( 'Remove all trashed comments from DB', 'breeze' ),
		'no'       => $comments_trash,
	),
	'trackbacks_pingbacks'    => array(
		'title'    => __( 'Trackbacks/Pingbacks', 'breeze' ),
		'describe' => __( 'Remove all trackbacks/pingbakcs data from DB', 'breeze' ),
		'no'       => $trackbacks,
	),
	'comments_unapproved'     => array( // @since 2.0.7
		'title'    => __( 'Unapproved/Pending Comments', 'breeze' ),
		'describe' => __( 'Remove all the comments that are considered as unapproved from DB', 'breeze' ),
		'no'       => $comments_unapproved,
	),
//	'clean_optimizer'      => array(
//		'title'    => __( 'Clean CSS/JS Optimizer (0)', 'breeze' ),
//		'describe' => __( 'Optimise CSS/JS', 'breeze' ),
//		'no'       => '',
//	),
//	'optimize_tables'      => array(
//		'title'    => __( 'Optimize Tables', 'breeze' ),
//		'describe' => __( 'Try to optimize all the DB tables', 'breeze' ),
//		'no'       => '',
//	),
);

/**
 * Database summary
 */
if ( ! class_exists( 'Breeze_Db_Summary_List_Table' ) ) {
	require_once BREEZE_PLUGIN_DIR . 'inc/helpers/class-breeze-db-summary-table.php';

}
$myListTable = new Breeze_Db_Summary_List_Table();

?>
<section>
	<div class="br-section-title">
		<img src="<?php echo $icon; ?>"/>
		<?php _e( 'DATABASE OPTIONS', 'breeze' ); ?>
	</div>
	<br/>
	<div class="cta-cleanall">

		<div class="on-off-checkbox brilbr">
			<label class="br-switcher">
				<input type="checkbox" name="br-clean-all" id="br-clean-all"/>
				<div class="br-see-state">
				</div>
			</label><br>
		</div>
		<label for="br-clean-all" class="br-clean-label"><?php _e( 'Clean All', 'breeze' ); ?> <span
					class="br-has">( <?php echo esc_html( $total_no ); ?> )</span></label>
		<p>
			<?php _e( 'Cleall the trashed posts and pages.', 'breeze' ); ?>
		</p>
		<p class="br-important">
			<?php
			echo '<strong>';
			_e( 'Important: ', 'breeze' );
			echo '</strong>';
			_e( 'Backup your database before using the following options!', 'breeze' );
			?>
		</p>

		<input type="button" class="simple-btn" value="<?php _e( 'Clean Now', 'breeze' ); ?>" disabled
			   id="br-clean-all-cta">
	</div>


	<div class="br-db-boxes">
		<?php
		if ( ! empty( $sections_actions ) ) {
			foreach ( $sections_actions as $section_slug => $section_data ) {

				$no_data     = '';
				$css_opacity = '';
				if ( '' !== $section_data['no'] ) {
					if ( 0 === $section_data['no'] ) {
						$no_data     = ' (0)';
						$css_opacity = 'opac';
					} else {
						$no_data = ' (<span class="br-has">' . $section_data['no'] . '</span>)';
					}
				}
				?>
				<div class="br-db-item" data-section-title="<?php echo esc_attr( $section_data['title'] ); ?>"
					 data-section="<?php echo esc_attr( $section_slug ); ?>">
					<img src="<?php echo BREEZE_PLUGIN_URL . 'assets/images/' . esc_attr( $section_slug ) . '.png'; ?>">
					<h3>
						<?php
						echo $section_data['title'];
						echo $no_data;
						?>
					</h3>
					<p>
						<?php echo $section_data['describe']; ?>
					</p>

					<!--<a href="#<?php echo esc_attr( $section_slug ); ?>" data-section="<?php echo esc_attr( $section_slug ); ?>" class="do_clean_action <?php echo $css_opacity; ?>"><?php echo _e( 'Clean now', 'breeze' ); ?></a>-->
				</div>
				<?php
			}
		}
		?>
	</div>


	<div class="cta-cleanall">
	<input type="button" class="simple-btn" id="optimize-selected-services" value="<?php _e( 'Optimize', 'breeze' ); ?>">
	<br/><br/>
	</div>

</section>

<section>
	<div class="br-section-title">
		<img src="<?php echo $summary_icon; ?>"/>
		<?php _e( 'AUTOLOAD SUMMARY', 'breeze' ); ?>
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
    <script>

         var all_lines = jQuery('#dbsummary-content').find('tbody').find('tr');
	    all_lines.each( function ( index, element ) {
		    // element == this
             jQuery(this).addClass('is-expanded');
	    } );


	    jQuery('.br-option-item').on('click tap', '.toggle-row', function ( e ){
		    e.preventDefault();
		    var tr_action = jQuery(this).closest('tr');

		    if(!tr_action.hasClass('is-expanded')){
			  tr_action.addClass('is-expanded');
            }else{
			  tr_action.removeClass('is-expanded');
            }
        })
    </script>
</section>

