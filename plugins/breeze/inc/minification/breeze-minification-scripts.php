<?php

use MatthiasMullie\Minify;

/*
 *  Based on some work of autoptimize plugin
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Breeze_MinificationScripts extends Breeze_MinificationBase {
	private $head_scripts        = array();
	private $footer_scripts      = array();
	private $dontmove            = array(
		'gtag',
		'document.write',
		'html5.js',
		'show_ads.js',
		'google_ad',
		'blogcatalog.com/w',
		'tweetmeme.com/i',
		'mybloglog.com/',
		'histats.com/js',
		'ads.smowtion.com/ad.js',
		'statcounter.com/counter/counter.js',
		'widgets.amung.us',
		'ws.amazon.com/widgets',
		'media.fastclick.net',
		'/ads/',
		'comment-form-quicktags/quicktags.php',
		'edToolbar',
		'intensedebate.com',
		'scripts.chitika.net/',
		'_gaq.push',
		'jotform.com/',
		'admin-bar.min.js',
		'GoogleAnalyticsObject',
		'plupload.full.min.js',
		'syntaxhighlighter',
		'adsbygoogle',
		'gist.github.com',
		'_stq',
		'nonce',
		'post_id',
		'data-noptimize',
		'googletagmanager',
	);
	private $donotmove_exception = array( 'jQuery' );

	private $domove                = array( 'gaJsHost', 'load_cmc', 'jd.gallery.transitions.js', 'swfobject.embedSWF(', 'tiny_mce.js', 'tinyMCEPreInit.go' );
	private $domovelast            = array(
		'addthis.com',
		'/afsonline/show_afs_search.js',
		'disqus.js',
		'networkedblogs.com/getnetworkwidget',
		'infolinks.com/js/',
		'jd.gallery.js.php',
		'jd.gallery.transitions.js',
		'swfobject.embedSWF(',
		'linkwithin.com/widget.js',
		'tiny_mce.js',
		'tinyMCEPreInit.go',
		'post_id',
		'googletagmanager',
	);
	private $trycatch              = false;
	private $alreadyminified       = false;
	private $forcehead             = true;
	private $include_inline        = false;
	private $jscode                = '';
	private $url                   = '';
	private $move                  = array(
		'first' => array(),
		'last'  => array(),
	);
	private $restofcontent         = '';
	private $md5hash               = '';
	private $whitelist             = '';
	private $jsremovables          = array();
	private $inject_min_late       = '';
	private $group_js              = false;
	private $custom_js_exclude     = array();
	private $js_head_group         = array();
	private $js_footer_group       = array();
	private $js_min_head           = array();
	private $js_min_footer         = array();
	private $url_group_head        = array();
	private $url_group_footer      = array();
	private $jscode_inline_head    = array();
	private $jscode_inline_footer  = array();
	private $move_to_footer_js     = array();
	private $move_to_footer        = array();
	private $defer_js              = array();
	private $full_script           = '';
	private $uncompressed_inline   = array();
	private $inline_increment      = 1;
	private $original_content      = '';
	private $show_original_content = 0;
	private $do_process            = false;

	/**
	 * Holds the 3rd party defer links
	 * @var array[]
	 */
	private $do_defer_action = array(
		'head'   => array(),
		'footer' => array(),
	);
	/**
	 * Holds the 3rd party defer tags
	 * @var array[]
	 */
	private $do_defer_tag = array();

	/**
	 * Defer/Delay the inline scripts.
	 *
	 * @var array
	 */
	private $no_delay_js = array();

	/**
	 * Delay the Javascript
	 * @var array
	 */
	private $delay_javascript = false;

	/**
	 * Defer/Delay the inline scripts.
	 *
	 * @var array
	 */
	private $delay_inline_js = array();

	/**
	 * If Inline JS delay is on/off.
	 *
	 * @var array
	 */
	private $is_inline_delay_on = false;

	/**
	 * Contains all the scripts that will be delayed.
	 *
	 * @var array
	 */
	private $delay_scripts = array(
		'header' => array(),
		'footer' => array(),
	);

	//Reads the page and collects script tags
	public function read( $options ) {

		$this_path_url = $this->get_cache_file_url( 'js' );
		if ( false === breeze_is_process_locked( $this_path_url ) ) {
			$this->do_process = breeze_lock_cache_process( $this_path_url );
		} else {
			$this->original_content = $this->content;

			return true;
		}

		$noptimizeJS = apply_filters( 'breeze_filter_js_noptimize', false, $this->content );
		if ( $noptimizeJS ) {
			return false;
		}

		if ( false === $this->group_js && false === $this->include_inline ) {
			$this->donotmove_exception[] = '/wp-includes/js/dist/i18n.js';
			$this->donotmove_exception[] = '/wp-includes/js/dist/i18n.min.js';
			$this->dontmove[]            = '/wp-includes/js/dist/i18n.js';
			$this->dontmove[]            = '/wp-includes/js/dist/i18n.min.js';
		}

		$this->delay_javascript   = $options['delay_javascript'];
		$this->is_inline_delay_on = $options['is_inline_delay_on'];
		// only optimize known good JS?
		$whitelistJS = apply_filters( 'breeze_filter_js_whitelist', '' );
		if ( ! empty( $whitelistJS ) ) {
			$this->whitelist = array_filter( array_map( 'trim', explode( ',', $whitelistJS ) ) );
		}

		// is there JS we should simply remove
		$removableJS = apply_filters( 'breeze_filter_js_removables', '' );
		if ( ! empty( $removableJS ) ) {
			$this->jsremovables = array_filter( array_map( 'trim', explode( ',', $removableJS ) ) );
		}

		// only header?
		if ( apply_filters( 'breeze_filter_js_justhead', $options['justhead'] ) == true ) {
			$content             = explode( '</head>', $this->content, 2 );
			$this->content       = $content[0] . '</head>';
			$this->restofcontent = $content[1];
		}

		// include inline?
		if ( apply_filters( 'breeze_js_include_inline', $options['include_inline'] ) == true ) {
			$this->include_inline = true;
		}

		// group js?
		if ( apply_filters( 'breeze_js_group_js', $options['group_js'] ) == true ) {
			$this->group_js = true;
		}

		//custom js exclude
		if ( ! empty( $options['custom_js_exclude'] ) ) {
			$this->custom_js_exclude = $options['custom_js_exclude'];
		}

		// JS files will move to footer
		if ( ! empty( $options['move_to_footer_js'] ) ) {
			$this->move_to_footer_js = $options['move_to_footer_js'];
		}

		// JS files will move to footer
		if ( ! empty( $options['defer_js'] ) ) {
			$this->defer_js = $options['defer_js'];
		}

		// Inline delay scripts
		if ( ! empty( $options['delay_inline_js'] ) ) {
			$this->delay_inline_js = $options['delay_inline_js'];
		}

		if ( ! empty( $options['no_delay_js'] ) ) {
			$this->no_delay_js = $options['no_delay_js'];
		}

		// filter to "late inject minified JS", default to true for now (it is faster)
		$this->inject_min_late = apply_filters( 'breeze_filter_js_inject_min_late', true );

		// filters to override hardcoded do(nt)move(last) array contents (array in, array out!)
		$this->dontmove   = apply_filters( 'breeze_js_dontmove', $this->dontmove );
		$this->domovelast = apply_filters( 'breeze_filter_js_movelast', $this->domovelast );
		$this->domove     = apply_filters( 'breeze_filter_js_domove', $this->domove );

		// get extra exclusions settings or filter
		$excludeJS = $options['js_exclude'];
		$excludeJS = apply_filters( 'breeze_filter_js_exclude', $excludeJS );
		if ( $excludeJS !== '' ) {
			$exclJSArr      = array_filter( array_map( 'trim', explode( ',', $excludeJS ) ) );
			$this->dontmove = array_merge( $exclJSArr, $this->dontmove );
		}

		//Should we add try-catch?
		if ( $options['trycatch'] == true ) {
			$this->trycatch = true;
		}

		// force js in head?
		if ( $options['forcehead'] == true ) {
			$this->forcehead = true;
		} else {
			$this->forcehead = false;
		}
		$this->forcehead = apply_filters( 'breeze_filter_js_forcehead', $this->forcehead );

		// get cdn url
		$this->cdn_url = $options['cdn_url'];

		// noptimize me
		$this->content = $this->hide_noptimize( $this->content );

		// Save IE hacks
		$this->content = $this->hide_iehacks( $this->content );

		// comments
		$this->content = $this->hide_comments( $this->content );

		//Get script files
		$exploded_content = explode( '</head>', $this->content, 2 );
		$this->getJS( $exploded_content[0] );
		$this->getJS( $exploded_content[1], false );

		if ( ! empty( $this->head_scripts ) || ! empty( $this->footer_scripts ) ) {
			// Re-order moving to footer JS files
			$ordered_moving_js    = array_intersect_key( $this->move_to_footer_js, $this->move_to_footer );
			$ordered_moving_js    = array_map( array( $this, 'getpath' ), $ordered_moving_js );
			$this->footer_scripts = array_merge( $ordered_moving_js, $this->footer_scripts );

			return true;
		}

		// No script files, great!
		return false;
	}

	//Get all JS in page
	private function getJS( $content, $head = true ) {

		if ( preg_match_all( '#<script.*</script>#Usmi', $content, $matches ) ) {

			if ( wp_script_is( 'spai-sniper', 'enqueued' ) ) {
				$jquery_local_path         = home_url( '/wp-includes/js/jquery/jquery.js' );
				$this->custom_js_exclude[] = $jquery_local_path;
			}

			if ( isset( $matches[0] ) && ! empty( $matches[0] ) ) {
				$matches[0] = $this->delay_script_loading( $matches[0] );
			}

			foreach ( $matches[0] as $tag ) {

				if ( false !== strpos( $tag, 'ga(' ) ||
					 false !== strpos( $tag, 'google-analytics.com/analytics.js' ) ||
					 false !== strpos( $tag, '/breeze-extra/' ) ||
					 false !== strpos( $tag, "gtag('js'" )
				) {
					$tag = '';
					continue;
				}

				// only consider aggregation whitelisted in should_aggregate-function
				if ( ! $this->should_aggregate( $tag ) ) {
					$tag = '';
					continue;
				}

				if ( preg_match( '/\ssrc=("|\')?(.*(\ |\>))("|\')?/Usmi', $tag, $source ) ) {
					$source[2] = substr( $source[2], 0, - 1 );
					if ( $this->isremovable( $tag, $this->jsremovables ) ) {
						$content = str_replace( $tag, '', $content );
						continue;
					}

					// Get the script version.
					$script_version = '';
					$explode_url    = explode( '?', $source[2] );
					if ( isset( $explode_url[1] ) ) {
						$script_version = $explode_url[1];
					}

					// External script
					$url = current( explode( '?', $source[2], 2 ) );
					if ( $url[0] == "'" || $url[0] == '"' ) {
						$url = substr( $url, 1 );
					}
					if ( $url[ strlen( $url ) - 1 ] == '"' || $url[ strlen( $url ) - 1 ] == "'" ) {
						$url = substr( $url, 0, - 1 );
					}

					// Let's check if this file is in the excluded list.
					$is_excluded = breeze_is_string_in_array_values( $url, $this->custom_js_exclude );

					//exclude js
					if ( ! empty( $is_excluded ) ) {
						continue;
					}

					if ( false !== strpos( $tag, '.php' ) ) {
						continue;
					}

					$path = $this->getpath( $url );

					if ( $path !== false && preg_match( '#\.js$#', $path ) ) {
						//Inline
						if ( $this->ismergeable( $tag ) ) {
							//We can merge it
							if ( $head ) {
								// If this file will be move to footer
								if ( in_array( $url, $this->move_to_footer_js ) ) {
									$this->move_to_footer[ $url ] = $path;
								} else {
									$this->head_scripts[ $url ] = $path;
								}
							} else {
								$this->footer_scripts[ $url ] = $path;
							}
						} else {
							//No merge, but maybe we can move it
							if ( $this->ismovable( $tag ) ) {
								//Yeah, move it
								if ( $this->movetolast( $tag ) ) {
									$this->move['last'][] = $tag;
								} else {
									$this->move['first'][] = $tag;
								}
							} else {
								//We shouldn't touch this
								$tag = '';
							}
						}
					} else {
						//External script (example: google analytics)
						//OR Script is dynamic (.php etc)
						if ( // Defer 3rd party scripts.
							breeze_validate_url_via_regexp( $url ) &&
							! empty( $this->defer_js ) &&
							true === $this->is_in_defer_is( $url, $this->defer_js )
						) {

							if ( true === $head ) {
								$this->do_defer_action['head'][ $url ] = $url;
							} else {
								$this->do_defer_action['footer'][ $url ] = $url;
							}
							$this->do_defer_tag[ $url ] = $tag;

							//Remove the original script tag
							#$content = str_replace( $tag, '', $content );

						} elseif ( $this->ismovable( $tag ) ) {
							if ( $this->movetolast( $tag ) ) {
								$this->move['last'][] = $tag;
							} else {
								$this->move['first'][] = $tag;
							}
						} else {
							$is_delayed = false;
							if ( true === $this->is_inline_delay_on ) {
								$is_delayed = $this->is_inline_delay( $tag );
							}
							if ( true === $is_delayed ) {
								if ( true === $head ) {
									$this->delay_scripts['header'][ $url ] = array(
										'path'    => $path,
										'version' => $script_version,
									);
								} else {
									$this->delay_scripts['footer'][ $url ] = array(
										'path'    => $path,
										'version' => $script_version,
									);
								}
							} else {
								//We shouldn't touch this
								$tag = '';
							}
						}
					}
				} else {
					// Inline script
					if ( $this->isremovable( $tag, $this->jsremovables ) ) {
						$content = str_replace( $tag, '', $content );
						continue;
					}

					// unhide comments, as javascript may be wrapped in comment-tags for old times' sake
					$tag = $this->restore_comments( $tag );
					if ( $this->ismergeable( $tag ) && ( $this->include_inline || $this->group_js ) ) {

						preg_match( '#<script.*>(.*)</script>#Usmi', $tag, $code );
						$code = preg_replace( '#.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*#sm', '$1', $code[1] );
						$code = preg_replace( '/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $code );

						if ( $head ) {
							$this->head_scripts[] = 'INLINE;' . $code;
						} else {
							$this->footer_scripts[] = 'INLINE;' . $code;
						}
					} else {
						// Can we move this?
						if ( $this->ismovable( $tag ) ) {
							if ( $this->movetolast( $tag ) ) {
								$this->move['last'][] = $tag;
							} else {
								$this->move['first'][] = $tag;
							}
						} else {
							$is_delayed = false;
							if ( true === $this->is_inline_delay_on ) {
								$is_delayed = $this->is_inline_delay( $tag );
							}
							if ( true === $is_delayed ) {
								preg_match( '#<script.*>(.*)</script>#Usmi', $tag, $code );
								$code = preg_replace( '#.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*#sm', '$1', $code[1] );
								$code = preg_replace( '/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $code );
								if ( true === $head ) {
									$this->delay_scripts['header'][] = $code;
								} else {
									$this->delay_scripts['footer'][] = $code;
								}
							} else {
								//We shouldn't touch this
								$tag = '';
							}
						}
					}
					// re-hide comments to be able to do the removal based on tag from $this->content
					$tag = $this->hide_comments( $tag );
				}
				//Remove the original script tag
				$content = str_replace( $tag, '', $content );
			}
		}

		if ( $head ) {
			$this->content = $content;
		} else {
			$this->content .= '</head>' . $content;
		}

		return true;
	}

	public function minify() {
		$this->runMinify( $this->head_scripts );
		$this->runMinify( $this->footer_scripts, false );

		if ( ! empty( $this->group_js ) && empty( $this->include_inline ) && ! empty( $this->uncompressed_inline ) ) {
			foreach ( $this->uncompressed_inline as $index_inline => $uncompressed_js ) {
				// add the uncompressed inline
				$this->full_script = str_replace( $index_inline, $uncompressed_js, $this->full_script );
			}
		}

		return true;
	}

	//Joins and optimizes JS
	private function runMinify( $scripts, $head = true ) {
		if ( false === $this->do_process ) {
			return true;
		}

		foreach ( $scripts as $url => $script ) {

			if ( preg_match( '#^INLINE;#', $script ) ) {
				//Inline script
				$script = preg_replace( '#^INLINE;#', '', $script );
				$script = rtrim( $script, ";\n\t\r" ) . ';';
				//Add try-catch?
				if ( $this->trycatch ) {
					$script = 'try{' . $script . '}catch(e){}';
				}
				$tmpscript = apply_filters( 'breeze_js_individual_script', $script, '' );
				if ( has_filter( 'breeze_js_individual_script' ) && ! empty( $tmpscript ) ) {
					$script                = $tmpscript;
					$this->alreadyminified = true;
				}

				if ( empty( $this->group_js ) ) {

					if ( $head ) {
						$this->js_head_group[] = $script;
					} else {
						$this->js_footer_group[] = $script;
					}
				}

				if ( empty( $this->include_inline ) ) {

					$index_inline                               = "/*!IS_UNCOMPRESSED_INLINE_{$this->inline_increment}*/";
					$this->full_script                         .= $index_inline;
					$this->uncompressed_inline[ $index_inline ] = $script;
					$this->inline_increment ++;

				} else {

					$this->full_script .= $script;
				}
			} else {

				//External script
				if ( $script !== false && file_exists( $script ) && is_readable( $script ) ) {

					$scriptsrc = file_get_contents( $script );
					$scriptsrc = preg_replace( '/\x{EF}\x{BB}\x{BF}/', '', $scriptsrc );
					if ( empty( $scriptsrc ) ) {
						$scriptsrc = '';
					}
					$scriptsrc = rtrim( $scriptsrc, ";\n\t\r" ) . ';';

					//Add try-catch?
					if ( $this->trycatch ) {
						$scriptsrc = 'try{' . $scriptsrc . '}catch(e){}';
					}
					$tmpscriptsrc = apply_filters( 'breeze_js_individual_script', $scriptsrc, $script );
					if ( has_filter( 'breeze_js_individual_script' ) && ! empty( $tmpscriptsrc ) ) {
						$scriptsrc             = $tmpscriptsrc;
						$this->alreadyminified = true;

					} elseif ( apply_filters( 'breeze_js_ignore_minify', $script ) || ( strpos( $script, 'min.js' ) !== false ) && ( $this->inject_min_late === true ) || $this->breeze_js_files_exceptions( $url ) ) {
						$scriptsrc = '%%INJECTLATER' . breeze_HASH . '%%' . base64_encode( $script ) . '|' . hash( 'sha512', $scriptsrc ) . '%%INJECTLATER%%';

					}

					if ( $this->group_js == true ) {
						$this->jscode      .= "\n" . $scriptsrc;
						$this->full_script .= "\n" . $scriptsrc;
					} else {
						if ( $head ) {
							$this->js_head_group[ $url ] = $scriptsrc;
						} else {
							$this->js_footer_group[ $url ] = $scriptsrc;
						}
					}
				}/*else{
					//Couldn't read JS. Maybe getpath isn't working?
				}*/
			}
		}

		// Minify JS
		// When using group JS
		if ( ! $head && ! empty( $this->jscode ) ) {
			//Check for already-minified code
			$this->md5hash = hash( 'sha512', $this->full_script );
			$ccheck        = new Breeze_MinificationCache( $this->md5hash, 'js' );
			if ( $ccheck->check() ) {
				$this->full_script     = $ccheck->retrieve();
				$this->alreadyminified = true;
			}
			unset( $ccheck );

			//$this->jscode has all the uncompressed code now.

			if ( $this->alreadyminified !== true ) {

				if ( apply_filters( 'breeze_js_do_minify', true ) && ! empty( $this->full_script ) ) {
					$minifier = new Minify\JS();
					$minifier->add( $this->full_script );
					$tmp_jscode = $minifier->minify();

					if ( ! empty( $tmp_jscode ) ) {
						$this->full_script = trim( $tmp_jscode );
						unset( $tmp_jscode );
					}
				}

				$this->full_script = $this->inject_minified( $this->full_script );
			}

			// Get the inline JS and minify
			if ( ! empty( $this->js_head_group ) || ! empty( $this->js_footer_group ) ) {
				if ( ! empty( $this->js_head_group ) ) {
					foreach ( $this->js_head_group as $jscode ) {
						//$this->jscode has all the uncompressed code now.
						if ( apply_filters( 'breeze_js_do_minify', true ) ) {
							$minifier = new Minify\JS();
							$minifier->add( $jscode );
							$tmp_jscode = $minifier->minify();

							if ( ! empty( $tmp_jscode ) ) {
								$jscode = $tmp_jscode;
								unset( $tmp_jscode );
							}
						}

						$this->jscode_inline_head[] = $this->inject_minified( $jscode );
					}
				}

				if ( ! empty( $this->js_footer_group ) ) {
					foreach ( $this->js_footer_group as $jscode ) {
						//$this->jscode has all the uncompressed code now.
						if ( apply_filters( 'breeze_js_do_minify', true ) ) {
							$minifier = new Minify\JS();
							$minifier->add( $jscode );
							$tmp_jscode = $minifier->minify();

							if ( ! empty( $tmp_jscode ) ) {
								$jscode = $tmp_jscode;
								unset( $tmp_jscode );
							}
						}

						$this->jscode_inline_footer[] = $this->inject_minified( $jscode );
					}
				}
			}

			return true;
		}

		// Not using group JS
		if ( ! empty( $this->js_head_group ) || ! empty( $this->js_footer_group ) ) {
			if ( $head && ! empty( $this->js_head_group ) ) {
				foreach ( $this->js_head_group as $url => $jscode ) {
					//Check for already-minified code
					$this->md5hash = hash( 'sha512', $jscode );
					$ccheck        = new Breeze_MinificationCache( $this->md5hash, 'js' );

					if ( $ccheck->check() ) {
						$js_exist                  = $ccheck->retrieve();
						$this->js_min_head[ $url ] = $this->md5hash . '_breezejsgroup_' . $js_exist;
						continue;
					}
					unset( $ccheck );

					//$this->jscode has all the uncompressed code now.
					if ( apply_filters( 'breeze_js_do_minify', true ) ) {
						$minifier = new Minify\JS();
						$minifier->add( $jscode );
						$tmp_jscode = $minifier->minify();

						if ( ! empty( $tmp_jscode ) ) {
							$jscode = $tmp_jscode;
							unset( $tmp_jscode );
						}
					}

					$jscode                    = $this->inject_minified( $jscode );
					$this->js_min_head[ $url ] = $this->md5hash . '_breezejsgroup_' . $jscode;
				}
			}

			if ( ! $head && ! empty( $this->js_footer_group ) ) {
				foreach ( $this->js_footer_group as $url => $jscode ) {
					//Check for already-minified code
					$this->md5hash = hash( 'sha512', $jscode );
					$ccheck        = new Breeze_MinificationCache( $this->md5hash, 'js' );
					if ( $ccheck->check() ) {
						$js_exist                    = $ccheck->retrieve();
						$this->js_min_footer[ $url ] = $this->md5hash . '_breezejsgroup_' . $js_exist;
						continue;
					}
					unset( $ccheck );

					//$this->jscode has all the uncompressed code now.
					if ( apply_filters( 'breeze_js_do_minify', true ) ) {
						$minifier = new Minify\JS();
						$minifier->add( $jscode );
						$tmp_jscode = $minifier->minify();

						if ( ! empty( $tmp_jscode ) ) {
							$jscode = $tmp_jscode;
							unset( $tmp_jscode );
						}
					}

					$jscode                      = $this->inject_minified( $jscode );
					$this->js_min_footer[ $url ] = $this->md5hash . '_breezejsgroup_' . $jscode;
				}
			}
		}

		return true;
	}

	//Caches the JS in uncompressed, deflated and gzipped form.
	public function cache() {
		if ( false === $this->do_process ) {
			return true;
		}

		if ( $this->group_js == true ) {
			// If inline is also included
			$cache = new Breeze_MinificationCache( $this->md5hash, 'js' );

			if ( ! $cache->check() ) {
				//Cache our code
				$cache->cache( $this->full_script, 'text/javascript' );
			}

			$cache_directory = $cache->get_cache_dir();
			if ( $this->is_cache_file_present( $cache_directory . $cache->get_file_name() ) ) {
				$this->url = breeze_CACHE_URL . breeze_current_user_type() . $cache->getname();
				$this->url = $this->url_replace_cdn( $this->url );
			} else {
				$this->show_original_content = 1;
				$this->clear_cache_data();
			}
		} else {
			$url_exists = true;

			foreach ( $this->js_min_head as $old_url => $js_min ) {
				$namehash = substr( $js_min, 0, strpos( $js_min, '_breezejsgroup_' ) );
				$js_code  = substr( $js_min, strpos( $js_min, '_breezejsgroup_' ) + strlen( '_breezejsgroup_' ) );
				$cache    = new Breeze_MinificationCache( $namehash, 'js' );
				if ( ! $cache->check() ) {
					//Cache our code
					$cache->cache( $js_code, 'text/javascript' );
				}

				$cache_directory = $cache->get_cache_dir();
				if ( ! file_exists( $cache_directory . $cache->get_file_name() ) ) {
					$url_exists = false;
				} else {
					$url = breeze_CACHE_URL . breeze_current_user_type() . $cache->getname();
					if ( true === $this->delay_javascript && is_numeric( $old_url ) && $this->ignore_from_delay( $js_code ) ) {
						$this->url_group_head['defer'] = $this->url_replace_cdn( $url );
					} elseif ( true === $this->is_inline_delay_on && is_numeric( $old_url ) && $this->is_inline_delay( $js_code ) ) {
						$this->url_group_head['defer'] = $this->url_replace_cdn( $url );
					} else {
						$this->url_group_head[ $old_url ] = $this->url_replace_cdn( $url );
					}
				}
			}

			foreach ( $this->js_min_footer as $old_url => $js_min ) {
				$namehash = substr( $js_min, 0, strpos( $js_min, '_breezejsgroup_' ) );
				$js_code  = substr( $js_min, strpos( $js_min, '_breezejsgroup_' ) + strlen( '_breezejsgroup_' ) );
				$cache    = new Breeze_MinificationCache( $namehash, 'js' );
				if ( ! $cache->check() ) {
					//Cache our code
					$cache->cache( $js_code, 'text/javascript' );
				}

				$cache_directory = $cache->get_cache_dir();
				if ( ! file_exists( $cache_directory . $cache->get_file_name() ) ) {
					$url_exists = false;
				} else {
					$url = breeze_CACHE_URL . breeze_current_user_type() . $cache->getname();
					if ( true === $this->delay_javascript && is_numeric( $old_url ) && $this->ignore_from_delay( $js_code ) ) {
						$this->url_group_footer['defer'] = $this->url_replace_cdn( $url );
					} elseif ( true === $this->is_inline_delay_on && is_numeric( $old_url ) && $this->is_inline_delay( $js_code ) ) {
						$this->url_group_footer['defer'] = $this->url_replace_cdn( $url );
					} else {
						$this->url_group_footer[ $old_url ] = $this->url_replace_cdn( $url );
					}
				}
			}

			if ( false === $url_exists ) {
				$this->show_original_content = 1;
				$this->clear_cache_data();
			}
		}
	}

	// Returns the content
	public function getcontent() {
		if ( ! empty( $this->show_original_content ) ) {
			return $this->original_content;
		}

		// Restore the full content
		if ( ! empty( $this->restofcontent ) ) {
			$this->content      .= $this->restofcontent;
			$this->restofcontent = '';
		}

		// Load inline JS to html
		if ( ! empty( $this->jscode_inline_head ) && empty( $this->group_js ) ) {

			$replaceTag = array( '</head>', 'before' );

			foreach ( $this->jscode_inline_head as $js ) {
				if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js ) ) {
					$jsHead[] = '<div class="breeze-scripts-load" data-file="0" data-async="false" data-locate="head" data-defer="false" style="display:none">' . htmlspecialchars( $js, ENT_QUOTES ) . '</div>' . "\n";
				} elseif ( $this->is_inline_delay_on && $this->is_inline_delay( $js ) ) {
					$jsHead[] = '<script type="module">' . $js . '</script>';
				} else {
					$jsHead[] = '<script type="text/javascript">' . $js . '</script>';
				}
			}
			$jsReplacement  = '';
			$jsReplacement .= implode( '', $jsHead );
			$this->inject_in_html( $jsReplacement, $replaceTag );
		}

		if ( ! empty( $this->jscode_inline_footer ) && empty( $this->group_js ) ) {
			$replaceTag = array( '</body>', 'before' );

			foreach ( $this->jscode_inline_footer as $js ) {
				if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js ) ) {
					$jsFooter[] = '<div class="breeze-scripts-load" data-file="0" data-async="false" data-locate="footer" data-defer="false" style="display:none">' . htmlspecialchars( $js, ENT_QUOTES ) . '</div>' . "\n";
				} elseif ( $this->is_inline_delay_on && $this->is_inline_delay( $js ) ) {
					$jsFooter[] = '<script type="module">' . $js . '</script>';
				} else {
					$jsFooter[] = '<script type="text/javascript">' . $js . '</script>';
				}
			}
			$jsReplacement  = '';
			$jsReplacement .= implode( '', $jsFooter );
			$this->inject_in_html( $jsReplacement, $replaceTag );
		}

		//$defer = apply_filters('breeze_filter_js_defer', $defer);

		if ( $this->group_js == true ) {
			$replaceTag = array( '</body>', 'before' );
			if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $this->url ) ) {
				$bodyreplacementpayload = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="footer" data-defer="true"  style="display:none">' . $this->url . '</div>' . "\n";
			} elseif ( $this->is_inline_delay_on && $this->is_inline_delay( $this->url ) ) { // Might add specific changes later.
				$bodyreplacementpayload = '<script type="text/javascript" defer src="' . $this->url . '"></script>';
			} else {
				$bodyreplacementpayload = '<script type="text/javascript" defer src="' . $this->url . '"></script>';
			}
			$bodyreplacementpayload = apply_filters( 'breeze_filter_js_bodyreplacementpayload', $bodyreplacementpayload );

			$bodyreplacement  = implode( '', $this->move['first'] );
			$bodyreplacement .= $bodyreplacementpayload;
			$bodyreplacement .= implode( '', $this->move['last'] );

			$replaceTag = apply_filters( 'breeze_filter_js_replacetag', $replaceTag );

			if ( strlen( $this->full_script ) > 0 ) {
				$this->inject_in_html( $bodyreplacement, $replaceTag );
			}
		} else {

			// handle the 3rd party defer scripts in header.
			if ( ! empty( $this->delay_scripts ) && ! empty( $this->delay_scripts['header'] ) ) {
				$replace_tag   = array( '</head>', 'before' );
				$js_head_defer = array();
				$defer         = 'defer ';
				foreach ( $this->delay_scripts['header'] as $js_url => $js_script ) {
					if ( is_string( $js_url ) ) {
						$js_url = trim( $js_url, '”' );
					}

					if ( filter_var( $js_url, FILTER_VALIDATE_URL ) ) {
						if ( ! empty( $js_script['version'] ) ) {
							$js_url .= '?' . $js_script['version'];
						}
						if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_url ) ) {
							$js_head_defer[] = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="head" data-defer="true" style="display:none">' . $js_url . '</div>' . "\n";
						} else {
							$js_head_defer[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
						}
					} else {
						if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_script ) ) {
							$js_head_defer[] = '<div class="breeze-scripts-load" data-file="0" data-async="false" data-locate="head" data-defer="true" style="display:none">' . htmlspecialchars( $js_script, ENT_QUOTES ) . '</div>' . "\n";
						} else {
							$js_head_defer[] = "<script type='module'>{$js_script}</script>\n";
						}
					}
				}
				$js_replacement  = '';
				$js_replacement .= implode( '', $js_head_defer );
				$this->inject_in_html( $js_replacement, $replace_tag );
			}

			// handle the 3rd party defer scripts in footer.
			if ( ! empty( $this->delay_scripts ) && ! empty( $this->delay_scripts['footer'] ) ) {
				$replace_tag     = array( '</body>', 'before' );
				$js_footer_defer = array();
				$defer           = 'defer ';
				foreach ( $this->delay_scripts['footer'] as $js_url => $js_script ) {
					if ( is_string( $js_url ) ) {
						$js_url = trim( $js_url, '”' );
					}

					if ( filter_var( $js_url, FILTER_VALIDATE_URL ) ) {
						if ( ! empty( $js_script['version'] ) ) {
							$js_url .= '?' . $js_script['version'];
						}

						if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_url ) ) {
							$js_footer_defer[] = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="footer" data-defer="true" style="display:none">' . $js_url . '</div>' . "\n";
						} else {
							$js_footer_defer[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
						}
					} else {
						if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_script ) ) {
							$js_footer_defer[] = '<div class="breeze-scripts-load" data-file="0" data-async="false" data-locate="footer" data-defer="true" style="display:none">' . htmlspecialchars( $js_script, ENT_QUOTES ) . '</div>' . "\n";
						} else {
							$js_footer_defer[] = "<script type='module'>{$js_script}</script>\n";
						}
					}
				}

				$js_replacement  = '';
				$js_replacement .= implode( '', $js_footer_defer );
				$this->inject_in_html( $js_replacement, $replace_tag );
			}

			$headScript   = array();
			$footerScript = array();

			if ( ! empty( $this->url_group_head ) ) {
				$replaceTag = array( '</head>', 'before' );

				foreach ( $this->url_group_head as $old_url => $url ) {
					$defer = '';
					if (
						gettype( $old_url ) == 'string' &&
						(
							in_array( $old_url, $this->defer_js ) ||
							$this->is_inline_delay( $url ) ||
							'defer' === $old_url
						)
					) {
						$defer = 'defer ';
					}

					if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $url ) ) {
						if ( empty( $defer ) ) {
							$defer = 'false';
						} else {
							$defer = 'true';
						}
						$headScript[] = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="head" data-defer="' . $defer . '" style="display:none">' . $url . '</div>' . "\n";
					} else {
						$headScript[] = '<script type="text/javascript" ' . $defer . 'src="' . $url . '"></script>';
					}
				}
				$jsReplacementPayload = implode( '', $headScript );

				$jsReplacement  = implode( '', $this->move['first'] );
				$jsReplacement .= $jsReplacementPayload;

				$replaceTag = apply_filters( 'breeze_filter_js_replacetag', $replaceTag );

				if ( ! empty( $this->js_min_head ) ) {
					$this->inject_in_html( $jsReplacement, $replaceTag );
				}
			}

			if ( ! empty( $this->url_group_footer ) ) {
				$replaceTag = array( '</body>', 'before' );

				foreach ( $this->url_group_footer as $old_url => $url ) {
					$defer = '';
					if ( gettype( $old_url ) == 'string' &&
						 (
							 in_array( $old_url, $this->defer_js ) ||
							 $this->is_inline_delay( $url ) ||
							 'defer' === $old_url
						 )
					) {
						$defer = 'defer ';
					}
					if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $url ) ) {
						if ( empty( $defer ) ) {
							$defer = 'false';
						} else {
							$defer = 'true';
						}
						$footerScript[] = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="footer" data-defer="' . $defer . '" style="display:none">' . $url . '</div>' . "\n";
					} else {
						$footerScript[] = '<script type="text/javascript" ' . $defer . 'src="' . $url . '"></script>';
					}
				}
				$jsReplacementPayload = implode( '', $footerScript );

				$jsReplacement  = $jsReplacementPayload;
				$jsReplacement .= implode( '', $this->move['last'] );

				$replaceTag = apply_filters( 'breeze_filter_js_replacetag', $replaceTag );

				if ( ! empty( $this->js_min_footer ) ) {
					$this->inject_in_html( $jsReplacement, $replaceTag );
				}
			}
		}

		if ( ! empty( $this->do_defer_action['head'] ) || ! empty( $this->do_defer_action['footer'] ) ) {

			if ( ! empty( $this->do_defer_action['head'] ) ) {
				$replaceTag  = array( '</head>', 'before' );
				$js_head     = array();
				$defer       = 'defer ';
				$delay_defer = 'true';

				foreach ( $this->do_defer_action['head'] as $js_url => $js_path ) {
					if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_url ) ) {
						$js_head[] = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="footer" data-defer="' . $delay_defer . '" style="display:none">' . $js_url . '</div>' . "\n";
					} else {
						$js_head[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
					}

					if ( isset( $this->do_defer_tag[ $js_url ] ) ) {
						$this->content = str_replace( $this->do_defer_tag[ $js_url ], '', $this->content );
					}
				}

				$js_replacement  = '';
				$js_replacement .= implode( '', $js_head );
				$this->inject_in_html( $js_replacement, $replaceTag );
			}

			if ( ! empty( $this->do_defer_action['footer'] ) ) {
				$replaceTag  = array( '</body>', 'before' );
				$js_footer   = array();
				$defer       = 'defer ';
				$delay_defer = 'true';

				foreach ( $this->do_defer_action['footer'] as $js_url => $js_path ) {
					if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_url ) ) {
						$js_footer[] = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="footer" data-defer="' . $delay_defer . '" style="display:none">' . $js_url . '</div>' . "\n";
					} else {
						$js_footer[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
					}

					if ( isset( $this->do_defer_tag[ $js_url ] ) ) {
						$this->content = str_replace( $this->do_defer_tag[ $js_url ], '', $this->content );
					}
				}

				$js_replacement  = '';
				$js_replacement .= implode( '', $js_footer );
				$this->inject_in_html( $js_replacement, $replaceTag );
			}
		}

		if ( true === $this->delay_javascript ) {
			$delay_script_js = breeze_load_delay_script();
			$replace_tag     = array( '</body>', 'before' );
			$this->inject_in_html( $delay_script_js, $replace_tag );
		}

		// restore comments
		$this->content = $this->restore_comments( $this->content );

		// Restore IE hacks
		$this->content = $this->restore_iehacks( $this->content );

		// Restore noptimize
		$this->content = $this->restore_noptimize( $this->content );

		if ( true === $this->do_process ) {
			$this_path_url = $this->get_cache_file_url( 'js' );
			breeze_unlock_process( $this_path_url );

			return $this->content;
		} else {
			return $this->original_content;
		}
		// Return the modified HTML
		//return $this->content;
	}

	// Checks against the white- and blacklists
	private function ismergeable( $tag ) {
		if ( ! empty( $this->whitelist ) ) {
			foreach ( $this->whitelist as $match ) {
				if ( strpos( $tag, $match ) !== false ) {
					return true;
				}
			}

			// no match with whitelist
			return false;
		} else {
			foreach ( $this->domove as $match ) {
				if ( strpos( $tag, $match ) !== false ) {
					//Matched something
					return false;
				}
			}

			if ( $this->movetolast( $tag ) ) {
				return false;
			}

			$return_value = true;
			foreach ( $this->dontmove as $match ) {
				if ( strpos( $tag, $match ) !== false ) {
					//Matched something
					$return_value = false;

				}
			}

			if ( false === $return_value ) {

				foreach ( $this->donotmove_exception as $match ) {
					if ( strpos( $tag, $match ) !== false ) {
						//Matched something

						return true;

					}
				}
			}

			// If we're here it's safe to merge
			return true;
		}
	}

	//Checks agains the blacklist
	private function ismovable( $tag ) {
		if ( $this->include_inline !== true || apply_filters( 'breeze_filter_js_unmovable', true ) ) {
			return false;
		}

		foreach ( $this->domove as $match ) {
			if ( strpos( $tag, $match ) !== false ) {
				//Matched something
				return true;
			}
		}

		if ( $this->movetolast( $tag ) ) {
			return true;
		}

		foreach ( $this->dontmove as $match ) {
			if ( strpos( $tag, $match ) !== false ) {
				//Matched something
				return false;
			}
		}

		//If we're here it's safe to move
		return true;
	}

	private function movetolast( $tag ) {
		$return_value = false;
		foreach ( $this->domovelast as $match ) {
			if ( strpos( $tag, $match ) !== false ) {
				//Matched, return true
				$return_value = true;
			}
		}

		if ( true === $return_value ) {

			foreach ( $this->donotmove_exception as $match ) {
				if ( strpos( $tag, $match ) !== false ) {
					//Matched something

					return false;

				}
			}
		}

		//Should be in 'first'
		return false;
	}

	/**
	 * Determines wheter a <script> $tag should be aggregated or not.
	 *
	 * We consider these as "aggregation-safe" currently:
	 * - script tags without a `type` attribute
	 * - script tags with an explicit `type` of `text/javascript`, 'text/ecmascript',
	 *   'application/javascript' or 'application/ecmascript'
	 *
	 * Everything else should return false.
	 *
	 * @param string $tag
	 *
	 * @return bool
	 *
	 * original function by https://github.com/zytzagoo/ on his AO fork, thanks Tomas!
	 */
	public function should_aggregate( $tag ) {
		preg_match( '#<(script[^>]*)>#i', $tag, $scripttag );
		if ( strpos( $scripttag[1], 'type=' ) === false ) {
			return true;
		} elseif ( preg_match( '/type=["\']?(?:text|application)\/(?:javascript|ecmascript)["\']?/i', $scripttag[1] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Search for specific exceptions.
	 * Files that should not be minified twice or included in grouping..
	 *
	 * @param $needle
	 *
	 * @return bool
	 * @since 1.1.3
	 */
	private function breeze_js_files_exceptions( $needle ) {
		$search_patterns = array(
			'cs-vendor\.[a-zA-Z0-9]*\.js',
			'cs\.[a-zA-Z0-9]*\.js',
			#'woocommerce-currency-switcher/js/front.js',
		);

		$needle = trim( $needle );
		foreach ( $search_patterns as $pattern ) {
			preg_match( '/(' . $pattern . ')/i', $needle, $output_array );
			if ( ! empty( $output_array ) ) { // is found ?
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the inline script is in the list of delay.
	 *
	 * @param $tag
	 *
	 * @return bool
	 */
	private function ignore_from_delay( $tag ) {

		foreach ( $this->no_delay_js as $match ) {
			if ( strpos( $tag, $match ) !== false ) {
				//Matched something
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the inline script is in the list of delay.
	 *
	 * @param $tag
	 *
	 * @return bool
	 */
	private function is_inline_delay( $tag ) {
		foreach ( $this->delay_inline_js as $match ) {
			if ( strpos( $tag, $match ) !== false ) {
				//Matched something
				return true;
			}
		}

		return false;
	}

	/**
	 * This is an exception for bad written plugins.
	 * Where they use jQuery but do not load their script with jQuery dependency.
	 * We will delay these scripts. But if jQuery is not loaded the issue will still persist.
	 *
	 * @since 1.2.4
	 * @access private
	 */
	private function delay_script_loading( $scripts = array() ) {

		if ( ! is_array( $scripts ) || empty( $scripts ) ) {
			return $scripts;
		}

		$to_move_last = apply_filters(
			'breeze_delay_bag_scripts',
			array(
				'sp-scripts.min.js',
				'js/tubular.js',
			)
		);

		$return_scripts = array();
		$add_last       = array();

		foreach ( $scripts as $index => $script ) {
			$add_return = false;
			$add_later  = false;
			foreach ( $to_move_last as $script_to_delay ) {
				if ( false === strpos( $script, $script_to_delay ) ) {
					$add_return = true;
					break;
				} else {
					$add_later = true;
				}
			}

			if ( false === $add_later && true === $add_return ) {
				$return_scripts[] = $script;
			} elseif ( true === $add_later ) {
				$add_last[] = $script;
			}
		}

		if ( ! empty( $add_last ) ) {
			foreach ( $add_last as $delayed_js_script ) {
				$return_scripts[] = $delayed_js_script;
			}
		}

		return $return_scripts;

	}
}
