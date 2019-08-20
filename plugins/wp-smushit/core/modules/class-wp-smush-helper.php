<?php
/**
 * Helpers class.
 *
 * @package WP_Smush
 * @version 1.0
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2017, Incsub (http://incsub.com)
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_Helper
 */
class WP_Smush_Helper {

	/**
	 * Get mime type for file.
	 *
	 * @since 3.1.0  Moved here as a helper function.
	 *
	 * @param string $path  Image path.
	 *
	 * @return bool|string
	 */
	public static function get_mime_type( $path ) {
		// Get the File mime.
		if ( class_exists( 'finfo' ) ) {
			$finfo = new finfo( FILEINFO_MIME_TYPE );
		} else {
			$finfo = false;
		}

		if ( $finfo ) {
			$mime = file_exists( $path ) ? $finfo->file( $path ) : '';
		} elseif ( function_exists( 'mime_content_type' ) ) {
			$mime = mime_content_type( $path );
		} else {
			$mime = false;
		}

		return $mime;
	}

	/**
	 * Return unfiltered file path
	 *
	 * @param int $attachment_id  Attachment ID.
	 *
	 * @return bool|false|string
	 */
	public static function get_attached_file( $attachment_id ) {
		if ( empty( $attachment_id ) ) {
			return false;
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! empty( $file_path ) && strpos( $file_path, 's3' ) !== false ) {
			$file_path = get_attached_file( $attachment_id, true );
		}

		return $file_path;
	}

	/**
	 * Iterate over PNG->JPG Savings to return cummulative savings for an image
	 *
	 * @param string $attachment_id  Attachment ID.
	 *
	 * @return array|bool
	 */
	public static function get_pngjpg_savings( $attachment_id = '' ) {
		// Initialize empty array.
		$savings = array(
			'bytes'       => 0,
			'size_before' => 0,
			'size_after'  => 0,
		);

		// Return empty array if attaachment id not provided.
		if ( empty( $attachment_id ) ) {
			return $savings;
		}

		$pngjpg_savings = get_post_meta( $attachment_id, WP_SMUSH_PREFIX . 'pngjpg_savings', true );
		if ( empty( $pngjpg_savings ) || ! is_array( $pngjpg_savings ) ) {
			return $savings;
		}

		foreach ( $pngjpg_savings as $size => $s_savings ) {
			if ( empty( $s_savings ) ) {
				continue;
			}
			$savings['size_before'] += $s_savings['size_before'];
			$savings['size_after']  += $s_savings['size_after'];
		}
		$savings['bytes'] = $savings['size_before'] - $savings['size_after'];

		return $savings;
	}

	/**
	 * Multiple Needles in an array
	 *
	 * @param string $haystack  Where to search.
	 * @param array  $needle    What to search for.
	 * @param int    $offset    Offset.
	 *
	 * @return bool
	 */
	public static function strposa( $haystack, $needle, $offset = 0 ) {
		if ( ! is_array( $needle ) ) {
			$needle = array( $needle );
		}

		foreach ( $needle as $query ) {
			if ( strpos( $haystack, $query, $offset ) !== false ) {
				return true;
			} // stop on first true result
		}

		return false;
	}

	/**
	 * Checks if file for given attachment id exists on s3, otherwise looks for local path.
	 *
	 * @param int    $id         File ID.
	 * @param string $file_path  Path.
	 *
	 * @return bool
	 */
	public static function file_exists( $id, $file_path ) {
		// If not attachment id is given return false.
		if ( empty( $id ) ) {
			return false;
		}

		// Get file path, if not provided.
		if ( empty( $file_path ) ) {
			$file_path = self::get_attached_file( $id );
		}

		$s3 = WP_Smush::get_instance()->core()->s3;

		// If S3 is enabled.
		if ( is_object( $s3 ) && method_exists( $s3, 'is_image_on_s3' ) && $s3->is_image_on_s3( $id ) ) {
			$file_exists = true;
		} else {
			$file_exists = file_exists( $file_path );
		}

		return $file_exists;
	}

	/**
	 * Add ellipsis in middle of long strings
	 *
	 * @param string $string  String.
	 *
	 * @return string Truncated string
	 */
	public static function add_ellipsis( $string = '' ) {
		if ( empty( $string ) ) {
			return $string;
		}
		// Return if the character length is 120 or less, else add ellipsis in between.
		if ( strlen( $string ) < 121 ) {
			return $string;
		}
		$start  = substr( $string, 0, 60 );
		$end    = substr( $string, -40 );
		$string = $start . '...' . $end;

		return $string;
	}

	/**
	 * Returns true if a database table column exists. Otherwise returns false.
	 *
	 * @link http://stackoverflow.com/a/5943905/2489248
	 * @global wpdb $wpdb
	 *
	 * @param string $table_name Name of table we will check for column existence.
	 * @param string $column_name Name of column we are checking for.
	 *
	 * @return boolean True if column exists. Else returns false.
	 */
	public static function table_column_exists( $table_name, $column_name ) {
		global $wpdb;

		$column = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ',
				DB_NAME,
				$table_name,
				$column_name
			)
		); // Db call ok; no-cache ok.

		if ( ! empty( $column ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Drops a specified index from a table.
	 *
	 * @since 1.0.1
	 *
	 * @global wpdb  $wpdb
	 *
	 * @param string $table Database table name.
	 * @param string $index Index name to drop.
	 * @return true True, when finished.
	 */
	public static function drop_index( $table, $index ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare( "ALTER TABLE %s DROP INDEX %s", $table, $index )
		); // Db call ok; no-cache ok.

		return true;
	}

	/**
	 * Sanitizes a hex color.
	 *
	 * @since 2.9  Moved from wp-smushit.php file.
	 *
	 * @param string $color  HEX color code.
	 *
	 * @return string Returns either '', a 3 or 6 digit hex color (with #), or nothing
	 */
	private function smush_sanitize_hex_color( $color ) {
		if ( '' === $color ) {
			return '';
		}

		// 3 or 6 hex digits, or the empty string.
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}

		return false;
	}

	/**
	 * Sanitizes a hex color without hash.
	 *
	 * @since 2.9  Moved from wp-smushit.php file.
	 *
	 * @param string $color  HEX color code with hash.
	 *
	 * @return string Returns either '', a 3 or 6 digit hex color (with #), or nothing
	 */
	public function smush_sanitize_hex_color_no_hash( $color ) {
		$color = ltrim( $color, '#' );

		if ( '' === $color ) {
			return '';
		}

		return $this->smush_sanitize_hex_color( '#' . $color ) ? $color : null;
	}

	/**
	 * Get the link to the media library page for the image.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $id    Image ID.
	 * @param string $name  Image file name.
	 * @param bool   $src   Return only src. Default - return link.
	 *
	 * @return string
	 */
	public static function get_image_media_link( $id, $name, $src = false ) {
		$mode = get_user_option( 'media_library_mode' );
		if ( 'grid' === $mode ) {
			$link = admin_url( "upload.php?item={$id}" );
		} else {
			$link = admin_url( "post.php?post={$id}&action=edit" );
		}

		if ( ! $src ) {
			return "<a href='{$link}'>{$name}</a>";
		}

		return $link;
	}

	/**
	 * Returns current user name to be displayed
	 *
	 * @return string
	 */
	public static function get_user_name() {
		// Get username.
		$current_user = wp_get_current_user();
		$name         = ! empty( $current_user->first_name ) ? $current_user->first_name : $current_user->display_name;

		return $name;
	}

	/**
	 * Allows to filter the error message sent to the user
	 *
	 * @param string $error          Error message.
	 * @param string $attachment_id  Attachment ID.
	 *
	 * @return mixed|null|string
	 */
	public static function filter_error( $error = '', $attachment_id = '' ) {
		if ( empty( $error ) ) {
			return null;
		}

		/**
		 * Used internally to modify the error message
		 */
		$error = apply_filters( 'wp_smush_error', $error, $attachment_id );

		return $error;
	}

	/**
	 * Format metadata from $_POST request.
	 *
	 * Post request in WordPress will convert all values
	 * to string. Make sure image height and width are int.
	 * This is required only when Async requests are used.
	 * See - https://wordpress.org/support/topic/smushit-overwrites-image-meta-crop-sizes-as-string-instead-of-int/
	 *
	 * @since 2.8.0
	 *
	 * @param array $meta Metadata of attachment.
	 *
	 * @return array
	 */
	public static function format_meta_from_post( $meta = array() ) {
		// Do not continue in case meta is empty.
		if ( empty( $meta ) ) {
			return $meta;
		}

		// If metadata is array proceed.
		if ( is_array( $meta ) ) {

			// Walk through each items and format.
			array_walk_recursive( $meta, array( 'WP_Smush_Helper', 'format_attachment_meta_item' ) );
		}

		return $meta;
	}

	/**
	 * If current item is width or height, make sure it is int.
	 *
	 * @since 2.8.0
	 *
	 * @param mixed  $value Meta item value.
	 * @param string $key Meta item key.
	 */
	public static function format_attachment_meta_item( &$value, $key ) {
		if ( 'height' === $key || 'width' === $key ) {
			$value = (int) $value;
		}

		/**
		 * Allows to format single item in meta.
		 *
		 * This filter will be used only for Async, post requests.
		 *
		 * @param mixed $value Meta item value.
		 * @param string $key Meta item key.
		 */
		$value = apply_filters( 'wp_smush_format_attachment_meta_item', $value, $key );
	}

	/**
	 * Format Numbers to short form 1000 -> 1k
	 *
	 * @param int $number  Number.
	 *
	 * @return string
	 */
	public static function format_number( $number ) {
		if ( $number >= 1000 ) {
			return $number / 1000 . 'k'; // NB: you will want to round this.
		}

		return $number;
	}

	/**
	 * Return the file size in a humanly readable format.
	 *
	 * Taken from http://www.php.net/manual/en/function.filesize.php#91477
	 *
	 * @param int $bytes      Number of bytes.
	 * @param int $precision  Precision.
	 *
	 * @return string
	 */
	public static function format_bytes( $bytes, $precision = 1 ) {
		$units  = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$bytes  = max( $bytes, 0 );
		$pow    = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow    = min( $pow, count( $units ) - 1 );
		$bytes /= pow( 1024, $pow );

		return round( $bytes, $precision ) . ' ' . $units[ $pow ];
	}

}
