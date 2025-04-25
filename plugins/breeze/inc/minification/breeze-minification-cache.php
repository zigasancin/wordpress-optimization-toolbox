<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
/*
 *  Based on some work of autoptimize plugin
 */

class Breeze_MinificationCache {
	private $filename;
	private $mime;
	private $cachedir;
	private $delayed;
	private $nogzip;

	public function __construct( $md5, $ext = 'php' ) {
		$separate_cache = breeze_mobile_detect();
		$this->cachedir = BREEZE_MINIFICATION_CACHE . breeze_current_user_type();
		if ( is_multisite() ) {
			$blog_id        = get_current_blog_id();
			$this->cachedir = BREEZE_MINIFICATION_CACHE . $blog_id . '/' . breeze_current_user_type();
		}

		$this->delayed = BREEZE_CACHE_DELAY;
		$this->nogzip  = BREEZE_CACHE_NOGZIP;
		if ( $this->nogzip == false ) {
			$this->filename = BREEZE_CACHEFILE_PREFIX . $separate_cache . $md5 . '.php';
		} else {
			if ( in_array( $ext, array( 'js', 'css' ) ) ) {
				$this->filename = $ext . '/' . BREEZE_CACHEFILE_PREFIX . $separate_cache . $md5 . breeze_currency_switcher_cache() . '.' . $ext;
			} else {
				$this->filename = '/' . BREEZE_CACHEFILE_PREFIX . $separate_cache . $md5 . '.' . $ext;
			}
		}

	}

	public function get_cache_dir() {
		return $this->cachedir;
	}

	public function get_file_name() {
		return $this->filename;
	}

	public function check() {
		if ( ! file_exists( $this->cachedir . $this->filename ) ) {

			// No cached file, sorry
			return false;
		}

		// Cache exists!
		return true;
	}

	public function retrieve() {
		if ( $this->check() ) {
			if ( $this->nogzip == false ) {
				return file_get_contents( $this->cachedir . $this->filename . '.none' );
			} else {
				return file_get_contents( $this->cachedir . $this->filename );
			}
		}

		return false;
	}

	public function cache( $code, $mime ) {
		if ( $this->nogzip == false ) {
			$file    = ( $this->delayed ? 'delayed.php' : 'default.php' );
			$phpcode = file_get_contents( BREEZE_PLUGIN_DIR . '/inc/minification/config/' . $file );
			$phpcode = str_replace( array( '%%CONTENT%%', 'exit;' ), array( $mime, '' ), $phpcode );

			//file_put_contents( $this->cachedir . $this->filename, $phpcode, LOCK_EX );
			breeze_read_write_file( $this->cachedir . $this->filename, $phpcode );

			// file_put_contents( $this->cachedir . $this->filename . '.none', $code, LOCK_EX );
			breeze_read_write_file( $this->cachedir . $this->filename . '.none', $code );

			if ( ! $this->delayed ) {
				// Compress now!
				// file_put_contents( $this->cachedir . $this->filename . '.deflate', gzencode( $code, 9, FORCE_DEFLATE ), LOCK_EX );
				breeze_read_write_file( $this->cachedir . $this->filename . '.deflate', gzencode( $code, 9, FORCE_DEFLATE ) );

				// file_put_contents( $this->cachedir . $this->filename . '.gzip', gzencode( $code, 9, FORCE_GZIP ), LOCK_EX );
				breeze_read_write_file( $this->cachedir . $this->filename . '.deflate', gzencode( $code, 9, FORCE_DEFLATE ) );
			}
		} else {
			// Write code to cache without doing anything else
			// file_put_contents( $this->cachedir . $this->filename, $code, LOCK_EX );
			breeze_read_write_file( $this->cachedir . $this->filename, $code );
		}
	}

	public function getname() {
		apply_filters( 'breeze_filter_cache_getname', breeze_CACHE_URL . breeze_current_user_type() . $this->filename );

		return $this->filename;
	}

	//create folder cache
	public static function create_cache_minification_folder() {
		if ( ! defined( 'BREEZE_MINIFICATION_CACHE' ) ) {
			// We didn't set a cache
			return false;
		}
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			foreach ( array( '', 'js', 'css' ) as $checkDir ) {
				if ( ! Breeze_MinificationCache::checkCacheDir( BREEZE_MINIFICATION_CACHE . $blog_id . '/' . breeze_current_user_type() . $checkDir ) ) {
					return false;
				}
			}

			/** write index.html here to avoid prying eyes */
			$indexFile = BREEZE_MINIFICATION_CACHE . $blog_id . '/' . breeze_current_user_type() . '/index.html';
			if ( ! is_file( $indexFile ) ) {
				//@file_put_contents( $indexFile, '<html><head><meta name="robots" content="noindex, nofollow"></head><body></body></html>' );
				breeze_read_write_file( $indexFile, '<html><head><meta name="robots" content="noindex, nofollow"></head><body></body></html>' );
			}

			/** write .htaccess here to overrule wp_super_cache */
			$htAccess = BREEZE_MINIFICATION_CACHE . $blog_id . '/' . breeze_current_user_type() . '.htaccess';
		} else {
			foreach ( array( '', 'js', 'css' ) as $checkDir ) {
				if ( ! Breeze_MinificationCache::checkCacheDir( BREEZE_MINIFICATION_CACHE . breeze_current_user_type() . $checkDir ) ) {
					return false;
				}
			}
			/** write index.html here to avoid prying eyes */
			$indexFile = BREEZE_MINIFICATION_CACHE . breeze_current_user_type() . '/index.html';
			if ( ! is_file( $indexFile ) ) {
				//@file_put_contents( $indexFile, '<html><head><meta name="robots" content="noindex, nofollow"></head><body></body></html>' );
				breeze_read_write_file( $indexFile, '<html><head><meta name="robots" content="noindex, nofollow"></head><body></body></html>' );
			}

			/** write .htaccess here to overrule wp_super_cache */
			$htAccess = BREEZE_MINIFICATION_CACHE . breeze_current_user_type() . '/.htaccess';
		}

		if ( ! is_file( $htAccess ) ) {
			/**
			 * create wp-content/AO_htaccess_tmpl with
			 * whatever htaccess rules you might need
			 * if you want to override default AO htaccess
			 */
			$htaccess_tmpl = WP_CONTENT_DIR . '/AO_htaccess_tmpl';
			if ( is_file( $htaccess_tmpl ) ) {
				$htAccessContent = file_get_contents( $htaccess_tmpl );
			} elseif ( is_multisite() ) {
				$htAccessContent = '<IfModule mod_headers.c>
        Header set Vary "Accept-Encoding"
        Header set Cache-Control "max-age=10672000, must-revalidate"
</IfModule>
<IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css A30672000
        ExpiresByType text/javascript A30672000
        ExpiresByType application/javascript A30672000
</IfModule>
<IfModule mod_deflate.c>
        <FilesMatch "\.(js|css)$">
        SetOutputFilter DEFLATE
    </FilesMatch>
</IfModule>
<IfModule mod_authz_core.c>
    <Files *.php>
        Require all granted
    </Files>
</IfModule>
<IfModule !mod_authz_core.c>
    <Files *.php>
        Order allow,deny
        Allow from all
    </Files>
</IfModule>';
			} else {
				$htAccessContent = '<IfModule mod_headers.c>
        Header set Vary "Accept-Encoding"
        Header set Cache-Control "max-age=10672000, must-revalidate"
</IfModule>
<IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css A30672000
        ExpiresByType text/javascript A30672000
        ExpiresByType application/javascript A30672000
</IfModule>
<IfModule mod_deflate.c>
    <FilesMatch "\.(js|css)$">
        SetOutputFilter DEFLATE
    </FilesMatch>
</IfModule>
<IfModule mod_authz_core.c>
    <Files *.php>
        Require all denied
    </Files>
</IfModule>
<IfModule !mod_authz_core.c>
    <Files *.php>
        Order deny,allow
        Deny from all
    </Files>
</IfModule>';
			}

			@file_put_contents( $htAccess, $htAccessContent );
		}

		// All OK
		return true;

	}

	//      check dir cache
	static function checkCacheDir( $dir ) {
		// Check and create if not exists
		if ( ! file_exists( $dir ) ) {
			@mkdir( $dir, 0775, true );
			if ( ! file_exists( $dir ) ) {
				return false;
			}
		}

		// check if we can now write
		if ( ! is_writable( $dir ) ) {
			return false;
		}

		// and write index.html here to avoid prying eyes
		$indexFile = $dir . '/index.html';
		if ( ! is_file( $indexFile ) ) {
			@file_put_contents( $indexFile, '<html><head><meta name="robots" content="noindex, nofollow"></head><body></body></html>' );
		}

		return true;
	}

	public static function clear_minification( $blog_id = null ) {
		if ( true === Breeze_CloudFlare_Helper::is_log_enabled() ) {
			error_log( '######### PURGE LOCAL CACHE MINIFICATION; ###: ' . var_export( 'true', true ) );
		}
		if ( is_multisite() && is_network_admin() ) {
			$sites = get_sites(
				array(
					'fields' => 'ids',
				)
			);
			foreach ( $sites as $blog_id ) {
				switch_to_blog( $blog_id );
				self::clear_site_minification();
				restore_current_blog();
			}
		} else {
			self::clear_site_minification( $blog_id );
		}
	}

	public static function clear_site_minification( $blog_id_custom = null ) {
		if ( ! isset( $_GET['breeze_purge'] ) && ! Breeze_MinificationCache::create_cache_minification_folder() ) {
			return false;
		}
		if ( ! isset( $scan ) ) {
			$scan = array();
		}
		$cache_folders = breeze_all_user_folders();

		if ( is_multisite() ) {
			if ( is_null( $blog_id_custom ) ) {
				$blog_id = absint( $blog_id_custom );
			} else {
				$blog_id = get_current_blog_id();
			}
			// scan the cachedirs
			foreach ( $cache_folders as $user_folder ) {
				foreach ( array( '', 'js', 'css' ) as $scandirName ) {
					$directory = BREEZE_MINIFICATION_CACHE . $blog_id . '/' . ( ! empty( $user_folder ) ? $user_folder . '/' : '' ) . $scandirName;

					if ( is_dir( $directory ) ) {

						$files_list = scandir( $directory );
						if ( ! empty( $files_list ) ) {
							if ( ! isset( $scan[ $scandirName ] ) ) {
								$scan[ $scandirName ] = array();
							}

							foreach ( $files_list as $index => $filename ) {
								if ( ! in_array( $filename, $scan[ $scandirName ] ) ) {
									$scan[ $scandirName ][] = $filename;
								}
							}
						}
					}
				}
			}

			// clear the cachedirs
			foreach ( $cache_folders as $user_folder ) {
				foreach ( $scan as $scandirName => $scanneddir ) {
					$thisAoCacheDir = rtrim( BREEZE_MINIFICATION_CACHE . $blog_id . '/' . ( ! empty( $user_folder ) ? $user_folder . '/' : '' ) . $scandirName, '/' ) . '/';

					foreach ( $scanneddir as $file ) {
						if ( ! in_array( $file, array(
								'.',
								'..'
							) ) && ( strpos( $file, 'lock' ) !== false || strpos( $file, BREEZE_CACHEFILE_PREFIX ) !== false ) && is_file( $thisAoCacheDir . $file ) ) {
							@unlink( $thisAoCacheDir . $file );
						}
					}

					@unlink( BREEZE_MINIFICATION_CACHE . $blog_id . '/' . ( ! empty( $user_folder ) ? $user_folder . '/' : '' ) . '.htaccess' );
					@unlink( BREEZE_MINIFICATION_CACHE . $blog_id . '/' . ( ! empty( $user_folder ) ? $user_folder . '/' : '' ) . 'process.lock' );
				}
			}
		} else {

			// scan the cachedirs
			foreach ( $cache_folders as $user_folder ) {
				foreach ( array( '', 'js', 'css' ) as $scandirName ) {
					$directory = BREEZE_MINIFICATION_CACHE . ( ! empty( $user_folder ) ? $user_folder . '/' : '' ) . $scandirName;

					if ( is_dir( $directory ) ) {
						$files_list = scandir( $directory );
						if ( ! empty( $files_list ) ) {
							if ( ! isset( $scan[ $scandirName ] ) ) {
								$scan[ $scandirName ] = array();
							}

							foreach ( $files_list as $index => $filename ) {
								if ( ! in_array( $filename, $scan[ $scandirName ] ) ) {
									$scan[ $scandirName ][] = $filename;
								}
							}
						}
					}
				}
			}

			// clear the cachedirs
			foreach ( $cache_folders as $user_folder ) {
				foreach ( $scan as $scandirName => $scanneddir ) {
					$thisAoCacheDir = rtrim( BREEZE_MINIFICATION_CACHE . ( ! empty( $user_folder ) ? $user_folder . '/' : '' ) . $scandirName, '/' ) . '/';

					foreach ( $scanneddir as $file ) {
						if ( ! in_array( $file, array(
								'.',
								'..'
							) ) && ( strpos( $file, 'lock' ) !== false || strpos( $file, BREEZE_CACHEFILE_PREFIX ) !== false ) && is_file( $thisAoCacheDir . $file ) ) {
							@unlink( $thisAoCacheDir . $file );
						}
					}
				}
				@unlink( BREEZE_MINIFICATION_CACHE . ( ! empty( $user_folder ) ? $user_folder . '/' : '' ) . '.htaccess' );
				@unlink( BREEZE_MINIFICATION_CACHE . ( ! empty( $user_folder ) ? $user_folder . '/' : '' ) . 'process.lock' );
			}
		}

		return true;
	}

	public static function factory() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
			$instance->set_action();
		}

		return $instance;
	}
}
