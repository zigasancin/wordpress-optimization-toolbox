<?php

if (!defined('WPO_VERSION')) die('No direct access allowed');

class WP_Optimization_optimizetables extends WP_Optimization {

	protected $auto_id = 'optimize';
	protected $setting_id = 'optimize';
	protected $dom_id = 'optimize-db';

	public $available_for_saving = true;
	public $available_for_auto = true;

	public $setting_default = true;

	public $changes_table_data = true;
	public $ui_sort_order = 500;
	public $run_sort_order = 100000;

	public function optimize() {
		$this->optimize_tables(true);
	}

	public function get_info() {
		$this->optimize_tables(false);
	}
	
	private function optimize_tables($optimize) {
	
		$total_gain = 0;
		$row_usage = 0;
		$data_usage = 0;
		$index_usage = 0;
		$overhead_usage = 0;
		$wp_optimize = WP_Optimize();
		$tablesstatus = $this->optimizer->get_tables();
		$how_many_inno_db_tables = 0;
		
		foreach ($tablesstatus as $table) {
			// TODO: $row_usage, $data_usage, $index_usage appear to be unused elsewhere in the plugin. Investigate. Also, on InnoDB, more is saved than the numeric results inform you of.
			// This all comes from the checkbox with name=id=optimize-db. So, that's the place to look to untangle.
			$row_usage += $table->Rows;
			$data_usage += $table->Data_length;
			$index_usage +=  $table->Index_length;
			if ('InnoDB' != $table->Engine) {
				$overhead_usage += $table->Data_free;
				$total_gain += $table->Data_free;
			} else {
				$how_many_inno_db_tables++;
			}
			if ($optimize) {
				$table_name = $table->Name;
				$wp_optimize->log('Optimizing: '.$table_name);
				$result_query  = $this->query('OPTIMIZE TABLE '.$table_name);
			}
		}

		if ($optimize) {
			
			// No apparent reason for this (or the ob_end_flush() that was at the end). TODO: Ask Ruhani. Does something give unwanted debug output?
			ob_start();

			$thedate = gmdate(get_option('date_format') . ' ' . get_option('time_format'), current_time( "timestamp", 0 ));
			$this->options->update_option('last-optimized', $thedate);

			$this->optimizer->update_total_cleaned(strval($total_gain));

			// Sending notification email
			if ($this->options->get_option('email') !== false) {
				//TODO need to fix the problem with variable value not passing through
				if ($this->options->get_option('email-address') !== '') {
					//$wp_optimize->send_email($thedate, $total_gain);                     
				}
			}
			ob_end_flush();

			$wp_optimize->log('Total Gain .... '.strval($total_gain));
			
			$this->register_output(sprintf(_x('%s database optimized!', '%s is the database name', 'wp-optimize'), "'".htmlspecialchars(DB_NAME)."'"));
			
		}
		
		$this->register_output(__('Total gain:', 'wp-optimize').' '.WP_Optimize()->format_size(($total_gain)));
		
		if ($how_many_inno_db_tables >0 && !$optimize) {
				
			$this->register_output(sprintf(__('Tables using the InnoDB engine (%d) will not be optimized.', 'wp-optimize'), $how_many_inno_db_tables));
				
		}
		
	}
	
	public function get_auto_option_description() {
		return __('Optimize database tables', 'wp-optimize');
	}
	
	public function settings_label() {
		return __('Optimize database tables', 'wp-optimize');
	}
}
