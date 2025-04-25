<?php
/*
 *  Based on some work of autoptimize plugin
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

abstract class Breeze_MinificationBase {
	protected $content    = '';
	protected $tagWarning = false;
	protected $cdn_url    = '';

	public function __construct( $content ) {
		$this->content = $content;
	}

	//Reads the page and collects tags
	abstract public function read( $justhead );

	//Joins and optimizes collected things
	abstract public function minify();

	//Caches the things
	abstract public function cache();

	//Returns the content
	abstract public function getcontent();

	//Converts an URL to a full path
	protected function getpath( $url ) {
		$url = apply_filters( 'breeze_filter_cssjs_alter_url', $url );

		if ( strpos( $url, '%' ) !== false ) {
			$url = urldecode( $url );
		}

		// normalize
		if ( strpos( $url, '//' ) === 0 ) {
			if ( is_ssl() ) {
				$url = 'https:' . $url;
			} else {
				$url = 'http:' . $url;
			}
		} elseif ( ( strpos( $url, '//' ) === false ) && ( strpos( $url, parse_url( breeze_WP_SITE_URL, PHP_URL_HOST ) ) === false ) ) {
			$url = breeze_WP_SITE_URL . $url;
		}

		// first check; hostname wp site should be hostname of url
		$thisHost = @parse_url( $url, PHP_URL_HOST );
		if ( $thisHost !== parse_url( breeze_WP_SITE_URL, PHP_URL_HOST ) ) {
			/*
			* first try to get all domains from WPML (if available)
			* then explicitely declare $this->cdn_url as OK as well
			* then apply own filter autoptimize_filter_cssjs_multidomain takes an array of hostnames
			* each item in that array will be considered part of the same WP multisite installation
			*/
			$multidomains = array();

			$multidomainsWPML = apply_filters( 'wpml_setting', array(), 'language_domains' );
			if ( ! empty( $multidomainsWPML ) ) {
				$multidomains = array_map( array( $this, 'ao_getDomain' ), $multidomainsWPML );
			}

			if ( ! empty( $this->cdn_url ) ) {
				$multidomains[] = parse_url( $this->cdn_url, PHP_URL_HOST );
			}

			$multidomains = apply_filters( 'breeze_filter_cssjs_multidomain', $multidomains );

			if ( ! empty( $multidomains ) ) {
				if ( in_array( $thisHost, $multidomains ) ) {
					$url = str_replace( $thisHost, parse_url( breeze_WP_SITE_URL, PHP_URL_HOST ), $url );
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		// try to remove "wp root url" from url while not minding http<>https
		$tmp_ao_root = preg_replace( '/https?/', '', breeze_WP_ROOT_URL );
		$tmp_url     = preg_replace( '/https?/', '', $url );
		$path        = str_replace( $tmp_ao_root, '', $tmp_url );

		// final check; if path starts with :// or //, this is not a URL in the WP context and we have to assume we can't aggregate
		if ( preg_match( '#^:?//#', $path ) ) {
			/** External script/css (adsense, etc) */
			return false;
		}

		$path = str_replace( '//', '/', BREEZE_ROOT_DIR . $path );

		return $path;
	}

	// needed for WPML-filter
	protected function ao_getDomain( $in ) {
		// make sure the url starts with something vaguely resembling a protocol
		if ( ( strpos( $in, 'http' ) !== 0 ) && ( strpos( $in, '//' ) !== 0 ) ) {
			$in = 'http://' . $in;
		}

		// do the actual parse_url
		$out = parse_url( $in, PHP_URL_HOST );

		// fallback if parse_url does not understand the url is in fact a url
		if ( empty( $out ) ) {
			$out = $in;
		}

		return $out;
	}


	// logger
	protected function ao_logger( $logmsg, $appendHTML = true ) {
		if ( $appendHTML ) {
			$logmsg         = '<!--noptimize--><!-- ' . $logmsg . ' --><!--/noptimize-->';
			$this->content .= $logmsg;
		} else {
			error_log( 'Error: ' . $logmsg );
		}
	}

	// hide everything between noptimize-comment tags
	protected function hide_noptimize( $noptimize_in ) {
		if ( preg_match( '/<!--\s?noptimize\s?-->/', $noptimize_in ) ) {
			$noptimize_out = preg_replace_callback(
				'#<!--\s?noptimize\s?-->.*?<!--\s?/\s?noptimize\s?-->#is',
				function ( $matches ) {
					return '%%NOPTIMIZE' . breeze_HASH . '%%' . base64_encode( $matches[0] ) . '%%NOPTIMIZE%%';
				},
				$noptimize_in
			);
		} else {
			$noptimize_out = $noptimize_in;
		}

		return $noptimize_out;
	}

	// unhide noptimize-tags
	protected function restore_noptimize( $noptimize_in ) {
		if ( strpos( $noptimize_in, '%%NOPTIMIZE%%' ) !== false ) {
			$noptimize_out = preg_replace_callback(
				'#%%NOPTIMIZE' . breeze_HASH . '%%(.*?)%%NOPTIMIZE%%#is',
				function ( $matches ) {
					return base64_decode( $matches[1] );
				},
				$noptimize_in
			);
		} else {
			$noptimize_out = $noptimize_in;
		}

		return $noptimize_out;
	}

	protected function hide_iehacks( $iehacks_in ) {
		if ( strpos( $iehacks_in, '<!--[if' ) !== false ) {
			$iehacks_out = preg_replace_callback(
				'#<!--\[if.*?\[endif\]-->#is',
				function ( $matches ) {
					return '%%IEHACK' . breeze_HASH . '%%' . base64_encode( $matches[0] ) . '%%IEHACK%%';
				},
				$iehacks_in
			);
		} else {
			$iehacks_out = $iehacks_in;
		}

		return $iehacks_out;
	}

	protected function restore_iehacks( $iehacks_in ) {
		if ( strpos( $iehacks_in, '%%IEHACK%%' ) !== false ) {
			$iehacks_out = preg_replace_callback(
				'#%%IEHACK' . breeze_HASH . '%%(.*?)%%IEHACK%%#is',
				function ( $matches ) {
					return base64_decode( $matches[1] );
				},
				$iehacks_in
			);
		} else {
			$iehacks_out = $iehacks_in;
		}

		return $iehacks_out;
	}

	protected function hide_comments( $comments_in ) {
		if ( strpos( $comments_in, '<!--' ) !== false ) {
			$comments_out = preg_replace_callback(
				'#<!--.*?-->#is',
				function ( $matches ) {
					return '%%COMMENTS' . breeze_HASH . '%%' . base64_encode( $matches[0] ) . '%%COMMENTS%%';
				},
				$comments_in
			);
		} else {
			$comments_out = $comments_in;
		}

		return $comments_out;
	}

	protected function restore_comments( $comments_in ) {
		if ( strpos( $comments_in, '%%COMMENTS%%' ) !== false ) {
			$comments_out = preg_replace_callback(
				'#%%COMMENTS' . breeze_HASH . '%%(.*?)%%COMMENTS%%#is',
				function ( $matches ) {
					return base64_decode( $matches[1] );
				},
				$comments_in
			);
		} else {
			$comments_out = $comments_in;
		}

		return $comments_out;
	}

	protected function url_replace_cdn( $url ) {
		$cdn_url = apply_filters( 'breeze_filter_base_cdnurl', $this->cdn_url );
		if ( ! empty( $cdn_url ) ) {
			// secondly prepend domain-less absolute URL's
			if ( ( substr( $url, 0, 1 ) === '/' ) && ( substr( $url, 1, 1 ) !== '/' ) ) {
				$url = rtrim( $cdn_url, '/' ) . $url;
			} else {
				// get WordPress base URL
				$WPSiteBreakdown = parse_url( breeze_WP_SITE_URL );
				$WPBaseUrl       = $WPSiteBreakdown['scheme'] . '://' . $WPSiteBreakdown['host'];
				if ( ! empty( $WPSiteBreakdown['port'] ) ) {
					$WPBaseUrl .= ':' . $WPSiteBreakdown['port'];
				}
				// three: replace full url's with scheme
				$tmp_url = str_replace( $WPBaseUrl, rtrim( $cdn_url, '/' ), $url );
				if ( $tmp_url === $url ) {
					// last attempt; replace scheme-less URL's
					$url = str_replace( preg_replace( '/https?:/', '', $WPBaseUrl ), rtrim( $cdn_url, '/' ), $url );
				} else {
					$url = $tmp_url;
				}
			}
		}

		// allow API filter to take alter after CDN replacement
		$url = apply_filters( 'breeze_filter_base_replace_cdn', $url );

		return $url;
	}

	protected function inject_in_html( $payload, $replaceTag ) {
		if ( strpos( $this->content, $replaceTag[0] ) !== false ) {
			if ( $replaceTag[1] === 'after' ) {
				$replaceBlock = $replaceTag[0] . $payload;
			} elseif ( $replaceTag[1] === 'replace' ) {
				$replaceBlock = $payload;
			} else {
				$replaceBlock = $payload . $replaceTag[0];
			}
			$this->content = substr_replace( $this->content, $replaceBlock, strpos( $this->content, $replaceTag[0] ), strlen( $replaceTag[0] ) );
		} else {
			$this->content .= $payload;
			if ( ! $this->tagWarning ) {
				$this->content   .= '<!--noptimize--><!-- breeze found a problem with the HTML in your Theme, tag ' . $replaceTag[0] . ' missing --><!--/noptimize-->';
				$this->tagWarning = true;
			}
		}
	}

	protected function isremovable( $tag, $removables ) {
		foreach ( $removables as $match ) {
			if ( strpos( $tag, $match ) !== false ) {
				return true;
			}
		}

		return false;
	}

	// inject already minified code in optimized JS/CSS
	protected function inject_minified( $in ) {
		if ( strpos( $in, '%%INJECTLATER%%' ) !== false ) {
			$out = preg_replace_callback(
				'#%%INJECTLATER' . breeze_HASH . '%%(.*?)%%INJECTLATER%%#is',
				function ( $matches ) {
					$filepath    = base64_decode( strtok( $matches[1], '|' ) );
					$filecontent = file_get_contents( $filepath );
					if ( empty( trim( $filecontent ) ) ) {
						return '';
					}

					// remove BOM
					$filecontent = preg_replace( "#\x{EF}\x{BB}\x{BF}#", '', $filecontent );

					// remove comments and blank lines
					if ( substr( $filepath, - 3, 3 ) === '.js' ) {
						$filecontent = preg_replace( '#^\s*\/\/.*$#Um', '', $filecontent );
					}

					$filecontent = preg_replace( '#^\s*\/\*[^!].*\*\/\s?#Us', '', $filecontent );
					$filecontent = preg_replace( "#(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+#", "\n", $filecontent );

					// specific stuff for JS-files
					if ( substr( $filepath, - 3, 3 ) === '.js' ) {
						if ( ( substr( $filecontent, - 1, 1 ) !== ';' ) && ( substr( $filecontent, - 1, 1 ) !== '}' ) ) {
							$filecontent .= ';';
						}

						if ( get_option( 'breeze_js_trycatch' ) === 'on' ) {
							$filecontent = 'try{' . $filecontent . '}catch(e){}';
						}
					} elseif ( ( substr( $filepath, - 4, 4 ) === '.css' ) ) {
						$filecontent = Breeze_MinificationStyles::fixurls( $filepath, $filecontent );
					}

					// return
					return "\n" . $filecontent;
				},
				$in
			);
		} else {
			$out = $in;
		}

		return $out;
	}

	/**
	 * Handles clear cache for the situation where cache files do not exist.
	 * @since 1.1.3
	 */
	protected function clear_cache_data() {
		//delete minify
		Breeze_MinificationCache::clear_minification();
		//clear normal cache
		Breeze_PurgeCache::breeze_cache_flush( false, false, true );

		//Breeze_PurgeCache::factory();
		//clear varnish cache
		$varnish_cache = new Breeze_PurgeVarnish();

		$is_network = ( is_network_admin() || ( ! empty( $_POST['is_network'] ) && 'true' === $_POST['is_network'] ) );

		if ( is_multisite() && $is_network ) {
			$sites = get_sites();
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				$homepage = home_url() . '/?breeze';
				$varnish_cache->purge_cache( $homepage );
				restore_current_blog();
			}
		} else {
			$homepage = home_url() . '/?breeze';
			$varnish_cache->purge_cache( $homepage );
		}
	}

	/**
	 * Helps to check if the cache file actually exists.
	 *
	 * @param string $file_path Full file path.
	 *
	 * @return bool
	 * @since 1.1.3
	 */
	protected function is_cache_file_present( $file_path = '' ) {
		if ( file_exists( $file_path ) ) {
			return true;
		}

		return false;
	}

	public function get_cache_file_url( $type = 'css' ) {
		$cache_dir = BREEZE_MINIFICATION_CACHE . breeze_current_user_type() . ( ! empty( $type ) ? $type . '/' : '' );
		if ( is_multisite() ) {
			$blog_id   = get_current_blog_id();
			$cache_dir = BREEZE_MINIFICATION_CACHE . $blog_id . '/' . breeze_current_user_type() . ( ! empty( $type ) ? $type . '/' : '' );
		}

		return $cache_dir;
	}

	/**
	 * @param string $url Script url.
	 * @param array $defer_data Array with all defer scripts.
	 *
	 * @return bool
	 */
	protected function is_in_defer_is( $url, $defer_data ): bool {
		if ( empty( $url ) || empty( $defer_data ) ) {
			return false;
		}

		if (
			in_array( $url, $defer_data, true ) ||
			array_key_exists( $url, $defer_data )
		) {
			return true;
		}

		$return = false;

		foreach ( $defer_data as $key => $value ) {
			if (
				false !== strpos( $key, $url ) ||
				false !== strpos( $value, $url )
			) {
				$return = true;
				break;
			}
		}

		return $return;
	}
}
