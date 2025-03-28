<?php

namespace Smush\Core\Threads;

/**
 * TODO: use this in places where we are currently using mutex
 */
class Thread_Safe_Options {

	public function delete_option( $option_id ) {
		return $this->delete( $option_id );
	}

	public function delete_site_option( $option_id ) {
		return $this->delete( $option_id, true );
	}

	private function delete( $option_id, $site_option = false ) {
		global $wpdb;

		list( $table, $column ) = $this->get_table_columns( $site_option );

		return $wpdb->delete( $table, array(
			$column => $option_id,
		), '%s' );
	}

	public function get_option( $option_id, $default = false ) {
		return $this->get_value_from_db( $option_id, $default );
	}

	/**
	 * Thread safe version of get_site_option, queries the database directly to prevent use of cached values
	 *
	 * @param $option_id string
	 * @param $default
	 *
	 * @return false|mixed
	 */
	public function get_site_option( $option_id, $default = false ) {
		return $this->get_value_from_db( $option_id, $default, true );
	}

	private function get_value_from_db( $option_id, $default, $site_option = false ) {
		global $wpdb;

		list( $table, $column, $value_column, $key_column ) = $this->get_table_columns( $site_option );

		$row = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			FROM {$table}
			WHERE {$column} = %s
			ORDER BY {$key_column} ASC
			LIMIT 1
		", $option_id ) );

		if ( empty( $row->$value_column ) || ! is_object( $row ) ) {
			return $default;
		}

		$decoded = json_decode( $row->$value_column, true );
		if ( is_null( $decoded ) ) {
			return $default;
		}

		return $decoded;
	}

	public function add_data( $option_id, $key, $data ) {
		return $this->json_set_object( $option_id, $key, $data );
	}

	public function add_data_in_site_option( $option_id, $key, $data ) {
		return $this->json_set_object( $option_id, $key, $data, true );
	}

	public function remove_data( $option_id, $key ) {
		return $this->json_remove( $option_id, $key );
	}

	private function json_set_object( $option_id, $key, $data, $site_option = false ) {
		global $wpdb;

		list( $table, $column, $value_column ) = $this->get_table_columns( $site_option );

		$initialized = $this->initialize_json_object_option( $table, $column, $option_id, $value_column );

		$json_object = [];
		foreach ( $data as $data_key => $value ) {
			$json_object[] = $wpdb->prepare( is_int( $value ) ? "%s, %d" : "%s, %s", $data_key, $value );
		}
		$json_object_string = implode( ',', $json_object );

		return $wpdb->query( "
			UPDATE {$table}
			SET {$value_column} = JSON_SET({$value_column}, '$.\"$key\"', JSON_OBJECT({$json_object_string}))
			WHERE {$column} = '$option_id';
		" );
	}

	private function json_remove( $option_id, $key, $site_option = false ) {
		global $wpdb;

		list( $table, $column, $value_column ) = $this->get_table_columns( $site_option );

		$initialized = $this->initialize_json_object_option( $table, $column, $option_id, $value_column );

		return $wpdb->query( "
			UPDATE {$table}
			SET {$value_column} = JSON_REMOVE({$value_column}, '$.\"$key\"')
			WHERE {$column} = '$option_id';
		" );
	}

	public function append_to_array( $option_id, $values ) {
		return $this->json_array_append_scalars( $option_id, $values );
	}

	public function add_to_array_in_site_option( $option_id, $values ) {
		return $this->json_array_append_scalars( $option_id, $values, true );
	}

	private function json_array_append_scalars( $option_id, $values, $site_option = false ) {
		global $wpdb;

		list( $table, $column, $value_column ) = $this->get_table_columns( $site_option );

		$initialized = $this->initialize_json_array_option( $table, $column, $option_id, $value_column );

		$json_values = [];
		foreach ( $values as $value ) {
			$json_values[] = $wpdb->prepare( is_int( $value ) ? "'$', %d" : "'$', %s", $value );
		}
		$json_values_string = implode( ',', $json_values );

		return $wpdb->query( "
			UPDATE {$table}
			SET {$value_column} = JSON_ARRAY_APPEND({$value_column}, {$json_values_string})
			WHERE {$column} = '$option_id';
		" );
	}

	public function remove_from_array( $option_id, $value ) {
		return $this->json_array_remove_scalars( $option_id, $value );
	}

	public function remove_from_array_in_site_option( $option_id, $value ) {
		return $this->json_array_remove_scalars( $option_id, $value, true );
	}

	private function json_array_remove_scalars( $option_id, $value, $site_option = false ) {
		global $wpdb;

		list( $table, $column, $value_column ) = $this->get_table_columns( $site_option );

		$initialized = $this->initialize_json_array_option( $table, $column, $option_id, $value_column );

		$json_value = $wpdb->prepare( is_int( $value ) ? "%d" : "%s", $value );

		return $wpdb->query( "
			UPDATE {$table}
			SET {$value_column} = IF(
			    JSON_SEARCH({$value_column}, 'one', {$json_value}, NULL, '$') IS NOT NULL,
			    JSON_REMOVE({$value_column}, JSON_UNQUOTE(JSON_SEARCH({$value_column}, 'one', {$json_value}, NULL, '$'))),
			    {$value_column}
			)
			WHERE {$column} = '$option_id';
		" );
	}

	public function set_values( $option_id, $associative_array ) {
		return $this->set_json_values( $option_id, $associative_array );
	}

	public function set_values_in_site_option( $option_id, $associative_array ) {
		return $this->set_json_values( $option_id, $associative_array, true );
	}

	public function get_value( $option_id, $key, $default = false ) {
		$values = $this->get_option( $option_id );
		$values = empty( $values ) ? array() : $values;

		return isset( $values[ $key ] ) ? $values[ $key ] : $default;
	}

	private function set_json_values( $option_id, $associative_array, $site_option = false ) {
		return $this->run_json_set_query( $option_id, $associative_array, $site_option, function ( $value_column, $key, $value ) {
			global $wpdb;

			return $wpdb->prepare( "%s, %s", "$.\"$key\"", $value );
		} );
	}

	public function increment_values( $option_id, $keys ) {
		return $this->increment_json_values( $option_id, $keys );
	}

	public function increment_values_in_site_option( $option_id, $keys ) {
		return $this->increment_json_values( $option_id, $keys, true );
	}

	private function increment_json_values( $option_id, $keys, $site_option = false ) {
		$values = [];
		foreach ( $keys as $key ) {
			$values[ $key ] = 1;
		}

		return $this->add_to_values( $option_id, $values, $site_option );
	}

	public function add_to_values( $option_id, $values, $site_option = false ) {
		return $this->run_json_set_query( $option_id, $values, $site_option, function ( $value_column, $key, $addend ) {
			global $wpdb;

			return $wpdb->prepare( "%s, CAST(JSON_UNQUOTE(IFNULL(JSON_EXTRACT($value_column, %s), 0)) + %d AS SIGNED)", "$.\"$key\"", "$.\"$key\"", $addend );
		} );
	}

	public function decrement_values( $option_id, $keys ) {
		return $this->decrement_json_values( $option_id, $keys );
	}

	public function decrement_values_in_site_option( $option_id, $keys ) {
		return $this->decrement_json_values( $option_id, $keys, true );
	}

	private function decrement_json_values( $option_id, $keys, $site_option = false ) {
		$values = [];
		foreach ( $keys as $key ) {
			$values[ $key ] = 1;
		}

		return $this->subtract_from_values( $option_id, $values, $site_option );
	}

	public function subtract_from_values( $option_id, $values, $site_option = false ) {
		return $this->run_json_set_query( $option_id, $values, $site_option, function ( $value_column, $key, $subtrahend ) {
			global $wpdb;

			return $wpdb->prepare( "%s, CAST(JSON_UNQUOTE(IFNULL(JSON_EXTRACT($value_column, %s), 0)) - %d AS SIGNED)", "$.\"$key\"", "$.\"$key\"", $subtrahend );
		} );
	}

	/**
	 * @param $site_option
	 *
	 * @return array
	 */
	private function get_table_columns( $site_option ): array {
		global $wpdb;

		$table        = $wpdb->options;
		$column       = 'option_name';
		$value_column = 'option_value';
		$key_column   = 'option_id';

		if ( $site_option && is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$value_column = 'meta_value';
			$key_column   = 'meta_id';
		}

		return array( $table, $column, $value_column, $key_column );
	}

	private function run_json_set_query( $option_id, $values, $site_option, $prepare_single_value_query ) {
		global $wpdb;

		list( $table, $column, $value_column ) = $this->get_table_columns( $site_option );

		$initialized = $this->initialize_json_object_option( $table, $column, $option_id, $value_column );

		$set_values = [];
		foreach ( $values as $key => $value ) {
			$set_values[] = call_user_func( $prepare_single_value_query, $value_column, $key, $value );
		}
		$set = implode( ', ', $set_values );

		$query = "
				UPDATE {$table}
				SET $value_column = JSON_SET($value_column, $set)
				WHERE {$column} = %s;
		";
		return $wpdb->query( $wpdb->prepare( $query, $option_id ) );
	}

	private function initialize_json_object_option( $table, $column, $option_id, $value_column ) {
		global $wpdb;

		return $wpdb->query( "
			INSERT IGNORE INTO {$table}
			SET `$column` = '$option_id',
				`$value_column` = '{}';
		" );
	}

	private function initialize_json_array_option( $table, $column, $option_id, $value_column ) {
		global $wpdb;

		return $wpdb->query( "
			INSERT IGNORE INTO {$table}
			SET `$column` = '$option_id',
				`$value_column` = '[]';
		" );
	}
}
