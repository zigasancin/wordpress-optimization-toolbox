<?php if (!defined('WPO_VERSION')) die('No direct access allowed'); ?>

<h3><?php echo __('Database Name:', 'wp-optimize')." '".htmlspecialchars(DB_NAME)."'"; ?></h3>

<?php
// This next bit belongs somewhere else, I think.
?>
<?php if ($optimize_db) { ?>
	<p><?php _e('Optimized all the tables found in the database.', 'wp-optimize')?></p>
<?php } ?>

<br style="clear">
<table class="widefat">
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

<tbody id="the-list">
<?php
	$alternate = ' class="alternate"';

	// Read SQL Version and act accordingly
	// Check for InnoDB tables
	// Check for windows servers
	$sqlversion = $wpdb->get_var("SELECT VERSION() AS version");
	$total_gain = 0;
	$no = 0;
	$row_usage = 0;
	$data_usage = 0;
	$index_usage = 0;
	$overhead_usage = 0;
	
	$tablesstatus = $optimizer->get_tables();
	
	foreach ($tablesstatus as $tablestatus) {
	
		$style = (0 == $no % 2) ? '' : ' class="alternate"';
		
		$no++;
		echo "<tr$style>\n";
		echo '<td>'.number_format_i18n($no).'</td>'."\n";
		echo "<td>".htmlspecialchars($tablestatus->Name)."</td>\n";
		echo '<td>'.number_format_i18n($tablestatus->Rows).'</td>'."\n";
		echo '<td>'.$wp_optimize->format_size($tablestatus->Data_length).'</td>'."\n";
		echo '<td>'.$wp_optimize->format_size($tablestatus->Index_length).'</td>'."\n";;
		echo '<td>'.htmlspecialchars($tablestatus->Engine).'</td>'."\n";;
		//echo '<td>'.$wp_optimize->format_size($tablestatus->Data_free).'</td>'."\n";

		if ($tablestatus->Engine != 'InnoDB') {

			echo '<td>';
			
			$font_colour = ($optimize_db) ? (($tablestatus->Data_free>0) ? 'blue' : 'green') : (($tablestatus->Data_free>0) ? 'red' : 'green');
			
			echo '<span style="color:'.$font_colour.';">';
			echo $wp_optimize->format_size($tablestatus->Data_free);
			echo '</span>';
			
			echo '</td>'."\n";
		} else {
			echo '<td>';
			echo '<span style="color:blue;">-</span>';
			echo '</td>'."\n";
		}

		$row_usage += $tablestatus->Rows;
		$data_usage += $tablestatus->Data_length;
		$index_usage +=  $tablestatus->Index_length;

		if ($tablestatus->Engine != 'InnoDB') {
			$overhead_usage += $tablestatus->Data_free;
			$total_gain += $tablestatus->Data_free;
		}
		echo '</tr>'."\n";
	}

	echo '<tr class="thead">'."\n";
	echo '<th>'.__('Total:', 'wp-optimize').'</th>'."\n";
	echo '<th>'.sprintf(_n('%d Table', '%d Tables', $no, 'wp-optimize'), number_format_i18n($no)).'</th>'."\n";
	echo '<th>'.sprintf(_n('%d Record', '%d Records', $row_usage, 'wp-optimize'), number_format_i18n($row_usage)).'</th>'."\n";
	echo '<th>'.$wp_optimize->format_size($data_usage).'</th>'."\n";
	echo '<th>'.$wp_optimize->format_size($index_usage).'</th>'."\n";
	echo '<th>'.'-'.'</th>'."\n";
	echo '<th>';

	$font_colour = $optimize_db ? (($overhead_usage>0) ? 'blue' : 'green') : (($overhead_usage>0) ? 'red' : 'green');
	
	echo '<span style="color:'.$font_colour.'">'.$wp_optimize->format_size($overhead_usage).'</span>';
	
	?>
	</th>
	</tr>
	</tbody>
</table>

<h3><?php _e('Total size of database:', 'wp-optimize'); ?></h3>
<h2><?php
	list ($part1, $part2) = $optimizer->get_current_db_size();
	echo $part1;
?></h2>

<?php if ($optimize_db) {
	?>

	<h3><?php _e('Optimization Results:', 'wp-optimize'); ?></h3>
	<p style="color: #0000FF;"><?php

	if ($total_gain > 0) {
		_e('Total Space Saved:', 'wp-optimize');
		echo $wp_optimize->format_size($total_gain);
		$optimizer->update_total_cleaned(strval($total_gain));
	}
	
	echo '</p>';
	
} else { ?>

	<?php if ($total_gain != 0) { ?>

		<h3><?php if ($total_gain > 0) _e('Optimization Possibility:', 'wp-optimize'); ?></h3>
		<p style="color: #FF0000;">
		<?php if ($total_gain > 0) {
			echo __('Total space that can be saved:', 'wp-optimize').' '.$wp_optimize->format_size($total_gain).' ';
		}
		echo '</p>';
		
	}
}
