<?php if (!defined('WPO_VERSION')) die('No direct access allowed'); ?>

<h3><?php echo __('Database name:', 'wp-optimize')." '".htmlspecialchars(DB_NAME)."'"; ?><span id="wp_optimize_table_list_refresh" class="dashicons dashicons-image-rotate"></span></h3>

<?php
// This next bit belongs somewhere else, I think.
?>
<?php if ($optimize_db) { ?>
	<p><?php _e('Optimized all the tables found in the database.', 'wp-optimize')?></p>
<?php } ?>

<table id="wpoptimize_table_list" class="widefat">
	<thead>
		<tr>
			<th><?php _e('No.', 'wp-optimize'); ?></th>
			<th><?php _e('Table', 'wp-optimize'); ?></th>
			<th><?php _e('Records', 'wp-optimize'); ?></th>
			<th><?php _e('Data Size', 'wp-optimize'); ?></th>
			<th><?php _e('Index Size', 'wp-optimize'); ?></th>
			<th><?php _e('Type', 'wp-optimize'); ?></th>
			<th><?php _e('Overhead', 'wp-optimize');?></th>
		</tr>
	</thead>
	
	<?php include('tables-body.php'); ?>

</table>

<h3><?php _e('Total size of database:', 'wp-optimize'); ?> <span id="optimize_current_db_size"><?php
	list ($part1, $part2) = $optimizer->get_current_db_size();
	echo $part1;
?></span></h3>

<?php if ($optimize_db) {
	?>

	<h3><?php _e('Optimization results:', 'wp-optimize'); ?></h3>
	<p style="color: #0000ff;"><?php

	if ($total_gain > 0) {
		echo __('Total space saved:', 'wp-optimize').$wp_optimize->format_size($total_gain);
		$optimizer->update_total_cleaned(strval($total_gain));
	}
	
	echo '</p>';
	
} else { ?>

	<?php if ($total_gain != 0) { ?>

		<h3><?php if ($total_gain > 0) _e('Optimization Possibility:', 'wp-optimize'); ?></h3>
		<p style="color: #ff0000;">
		<?php if ($total_gain > 0) {
			echo __('Total space that can be saved:', 'wp-optimize').' '.$wp_optimize->format_size($total_gain).' ';
		}
		echo '</p>';
		
	}
}
