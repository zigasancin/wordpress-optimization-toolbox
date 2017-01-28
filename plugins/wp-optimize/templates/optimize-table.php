<?php if (!defined('WPO_VERSION')) die ('No direct access allowed'); ?>
<div class="wpo_section wpo_group">
	<form onSubmit="return confirm('<?php echo esc_js('WARNING: This operation is permanent. Continue?', 'wp-optimize'); ?>')" action="#" method="post" enctype="multipart/form-data" name="optimize_form" id="optimize_form">
	
	<?php wp_nonce_field('wpo_optimization'); ?>
	
	<div class="wpo_col wpo_span_2_of_3">
		<div class="postbox">
			<div class="inside">
			
				<h3><?php _e('Optimizations', 'wp-optimize'); ?></h3>
				
				<p>
					<!-- <span style="text-align:center;"><a href="#" onClick="javascript:SetDefaults();">
					<?php _e('Select safe options', 'wp-optimize'); ?></a></span> -->
					<small><strong><?php _e('Warning:', 'wp-optimize'); ?></strong> <?php echo __('It is best practice to always make a backup of your database before any major operation (optimizing, upgrading, etc.).', 'wp-optimize').' <a href="https://wordpress.org/plugins/updraftplus">'.__('If you need a backup plugin, then we recommend UpdraftPlus.', 'wp-optimize').'</a>'; ?></small>

				</p>
				<p>
					<input class="wpo_primary_big button-primary" type="submit" id="wp-optimize" name="wp-optimize" value="<?php esc_attr_e('Run all selected optimizations now', 'wp-optimize'); ?>" />
				</p>
				
				<?php include('optimizations-table.php'); ?>
				
				<p>
					<?php echo '<span style="color: red;">'.__('Warning:', 'wp-optimize').'</span> '.__('Items marked in red, whilst still safe, perform more database delete operations.', 'wp-optimize').' '.__('You may wish to run a backup before performing them.', 'wp-optimize'); ?>
				</p>
			
			</div>
		</div>
	 </div>

	<div id="wp_optimize_status_box" class="wpo_col wpo_span_1_of_3">
		<div class="postbox">
			<div class="inside">
			
				<?php include('status-box-contents.php'); ?>
				
			</div>
		</div>
	</div>
	</form>
</div>
