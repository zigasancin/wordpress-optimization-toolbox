<?php

class Breeze_Cache_CronJobs {

	/**
	 * Option prefix for the DB.
	 */
	private const OPT_PREFIX = 'breeze_';

	function __construct($enabled) {
		$current_time_utc = current_time( 'timestamp' );  //phpcs:ignore

		if ( $enabled ) {

			/**
			 * Will handle the cache Gravatars.
			 */
			if ( ! wp_next_scheduled( 'breeze_clear_remote_gravatar', 'gravatars' ) ) {
				wp_schedule_event( $current_time_utc, 'weekly', 'breeze_clear_remote_gravatar', 'gravatars' );
			}
			add_action( 'breeze_clear_remote_gravatar', array( &$this, 'extra_cache_cleanup' ), 10, 1 );
			add_filter( 'get_avatar', array( &$this, 'breeze_replace_gravatar_image' ) );
		} else {

			if ( wp_next_scheduled( 'breeze_clear_remote_gravatar', 'gravatars' ) ) {
				wp_unschedule_event( $current_time_utc, 'weekly', 'breeze_clear_remote_gravatar', 'gravatars' );
			}
		}
	}

	/**
	 * Clean the cache for the extra options.
	 *
	 * @param string $folder_for extra ache folder for which to clean the cache.
	 *
	 * @return void
	 */
	public function extra_cache_cleanup( $folder_for = '' ) {
		$allowed_actions = array(
			'gravatars',
		);
		if ( empty( $folder_for ) || ! in_array( $folder_for, $allowed_actions, true ) ) {
			return;
		}
		$blog_id         = $this->get_blog_id();
		$folder_for_dash = $folder_for . '/';
		$directory       = BREEZE_MINIFICATION_EXTRA . $folder_for_dash . $blog_id;
		if ( is_dir( $directory ) ) {
			$files_list = scandir( $directory );
			if ( ! empty( $files_list ) ) {
				if ( ! isset( $scanned_files[ $folder_for ] ) ) { // TODO check if $directory or $folder_for
					$scanned_files[ $folder_for ] = array();
				}
				foreach ( $files_list as $index => $filename ) {
					if ( ! in_array( $filename, $scanned_files[ $folder_for ] ) ) {
						$scanned_files[ $folder_for ][] = $filename;
					}
				}
			}
		}
		if ( ! empty( $scanned_files ) ) {
			foreach ( $scanned_files as $scan_dir_name => $scanned_dir ) {
				$current_cache_dir = rtrim( BREEZE_MINIFICATION_EXTRA . $folder_for_dash . $blog_id, '/' ) . '/';
				foreach ( $scanned_dir as $file ) {
					if (
						! in_array( $file, array( '.', '..' ), true ) &&
						is_file( $current_cache_dir . $file ) ) {
						@unlink( $current_cache_dir . $file );
					}
				}
			}
		}
	}

	/**
	 * @param $gravatar
	 *
	 * @return array|mixed|string|string[]
	 */
	public function breeze_replace_gravatar_image( $gravatar ) {
		preg_match_all( '/srcset=["\']?((?:.(?!["\']?\s+(?:\S+)=|\s*\/?[>"\']))+.)["\']?/', $gravatar, $srcset );
		if ( isset( $srcset[1] ) && isset( $srcset[1][0] ) ) {
			$url             = explode( ' ', $srcset[1][0] )[0];
			$local_gravatars = $this->fetch_gravatar_from_remote( $url );
			$gravatar        = str_replace( $url, $local_gravatars, $gravatar );
		}
		preg_match_all( '/src=["\']?((?:.(?!["\']?\s+(?:\S+)=|\s*\/?[>"\']))+.)["\']?/', $gravatar, $src );
		if ( isset( $src[1] ) && isset( $src[1][0] ) ) {
			$url             = explode( ' ', $src[1][0] )[0];
			$local_gravatars = $this->fetch_gravatar_from_remote( $url );
			$gravatar        = str_replace( $url, $local_gravatars, $gravatar );
		}

		return $gravatar;
	}

	/**
	 * @param $url
	 *
	 * @return mixed|string
	 */
	private function fetch_gravatar_from_remote( $url = '' ) {
		if ( empty( $url ) ) {
			return $url;
		}
		$blog_id             = $this->get_blog_id();
		$local_gravatar_name = basename( wp_parse_url( $url, PHP_URL_PATH ) );
		$saved_gravatar      = $this->check_for_content( 'gravatars', $local_gravatar_name );
		if ( ! empty( $saved_gravatar ) ) {
			return $saved_gravatar;
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();
		}
		$gravatar_local_path = $this->get_local_extra_cache_directory( 'gravatars' );
		$gravatar_name       = basename( wp_parse_url( $url, PHP_URL_PATH ) );
		if ( ! file_exists( $gravatar_local_path . $gravatar_name ) ) {
			// Making sure the download_url functions is loaded.
			if ( ! function_exists( 'download_url' ) ) {
				require_once wp_normalize_path( ABSPATH . '/wp-admin/includes/file.php' );
			}
			// Downloads a URL to a local temporary file using the WordPress HTTP API.
			$temp_gravatar = download_url( $url );
			if ( ! is_wp_error( $temp_gravatar ) ) {
				// Move the file to the breeze gravatar cache folder.
				$is_saved = $wp_filesystem->move( $temp_gravatar, $gravatar_local_path . $gravatar_name, true ); // overwriting the destination file.
				if ( ! $is_saved ) {
					// if the download and save did not work, return the original url.
					return $url;
				}
				@unlink( $temp_gravatar );
			}
		}

		return content_url( '/cache/breeze-extra/gravatars/' . $blog_id . $gravatar_name );
	}

	/**
	 * @param $folder
	 * @param $filename
	 *
	 * @return string
	 */
	private function check_for_content( $folder = '', $filename = '' ) {
		if ( empty( $folder ) || empty( $filename ) ) {
			return '';
		}
		$blog_id        = $this->get_blog_id();
		$gravatar_cache = WP_CONTENT_DIR . '/cache/breeze-extra/' . $folder . '/' . $blog_id . $filename;
		if ( file_exists( $gravatar_cache ) ) {
			return content_url( '/cache/breeze-extra/' . $folder . '/' . $blog_id . $filename );
		}

		return '';
	}

	/**
	 * @return int|mixed|string
	 */
	private function get_blog_id() {
		$blog_id = '';
		if ( function_exists( 'is_multisite' ) && function_exists( 'get_current_blog_id' ) ) {
			if ( is_multisite() ) {
				$blog_id = get_current_blog_id() . '/';
			}
		} else {
			$blog_id = isset( $GLOBALS['breeze_config']['blog_id'] ) ? $GLOBALS['breeze_config']['blog_id'] : 1;
		}

		return $blog_id;
	}

	/**
	 * @param $folder
	 *
	 * @return string
	 */
	private function get_local_extra_cache_directory( $folder = '' ) {
		$blog_id = $this->get_blog_id();
		Breeze_MinificationCache::checkCacheDir( BREEZE_MINIFICATION_EXTRA );
		Breeze_MinificationCache::checkCacheDir( BREEZE_MINIFICATION_EXTRA . '/' . $folder . '/' );
		if ( ! empty( $blog_id ) ) {
			Breeze_MinificationCache::checkCacheDir( BREEZE_MINIFICATION_EXTRA . '/' . $folder . '/' . $blog_id );
		}

		return BREEZE_MINIFICATION_EXTRA . '/' . $folder . '/' . $blog_id;
	}

}