<?php
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

/**
 * When minification is not enabled but there are scripts into deferred loading option
 * that need to be handles.
 *
 * Class Breeze_Js_Deferred_Loading
 * @since 1.1.8
 */
class Breeze_Js_Deferred_Loading extends Breeze_MinificationBase {

	/**
	 * Javascript URLs that need the defer tag.
	 *
	 * @var array
	 * @since 1.1.8
	 * @access private
	 */
	private $defer_js = array();

	/**
	 * CDN domain.
	 *
	 * @since 1.1.8
	 * @access private
	 */
	protected $cdn_url = '';

	/**
	 * Will hold the JS Scripts found in the header
	 * @var array
	 * @since 1.1.8
	 * @access private
	 */
	private $head_scripts = array();

	/**
	 * Will hold the JS Scripts found in the body/footer
	 * @var array
	 * @since 1.1.8
	 * @access private
	 */
	private $footer_scripts = array();

	/**
	 * Will hold scripts that need to be removed.
	 * @var array
	 * @since 1.1.8
	 * @access private
	 */
	private $jsremovables = array();

	/**
	 * Holds scripts that need to be moved to the footer.
	 *
	 * @var array
	 * @since 1.1.8
	 * @access private
	 */
	private $move_to_footer_js = array();

	/**
	 * Prepared scripts that need to be moved to footer.
	 *
	 * @var array
	 * @since 1.1.8
	 * @access private
	 */
	private $move_to_footer = array();

	/**
	 * Files first or last.
	 *
	 * @var array[]
	 * @since 1.1.8
	 * @access private
	 */
	private $move = array(
		'first' => array(),
		'last'  => array(),
	);

	private $domove = array(
		'gaJsHost',
		'load_cmc',
		'jd.gallery.transitions.js',
		'swfobject.embedSWF(',
		'tiny_mce.js',
		'tinyMCEPreInit.go',
	);

	private $domovelast = array(
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
	);

	private $dontmove = array(
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
	);
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

	private $donotmove_exception = array( 'jQuery' );

	private $custom_js_exclude = array();

	/**
	 * Reads the page content and fetches the JavaScript script tags.
	 *
	 * @param array $options Script options.
	 *
	 * @return bool
	 * @since 1.1.8
	 * @access public
	 */
	public function read( $options = array() ) {

		// Inline delay scripts
		if ( ! empty( $options['delay_inline_js'] ) ) {
			$this->delay_inline_js = $options['delay_inline_js'];
		}

		// Inline delay scripts.
		if ( ! empty( $options['no_delay_js'] ) ) {
			$this->no_delay_js = $options['no_delay_js'];
		}

		$this->delay_javascript   = $options['delay_javascript'];
		$this->is_inline_delay_on = $options['is_inline_delay_on'];


		// Read the list of scripts that need defer tag.
		if ( ! empty( $options['defer_js'] ) ) {
			$this->defer_js = $options['defer_js'];
		}

		// JS files will move to footer
		if ( ! empty( $options['move_to_footer_js'] ) ) {
			$this->move_to_footer_js = $options['move_to_footer_js'];
		}


		// is there JS we should simply remove
		$removableJS = apply_filters( 'breeze_filter_js_removables', '' );
		if ( ! empty( $removableJS ) ) {
			$this->jsremovables = array_filter( array_map( 'trim', explode( ',', $removableJS ) ) );
		}

		// noptimize me
		$this->content = $this->hide_noptimize( $this->content );
		// Save IE hacks
		$this->content = $this->hide_iehacks( $this->content );
		// comments
		$this->content = $this->hide_comments( $this->content );

		// get cdn url
		$this->cdn_url = $options['cdn_url'];

		//Get script files
		$split_content = explode( '</head>', $this->content, 2 );
		$this->fetch_javascript( $split_content[0] );
		$this->fetch_javascript( $split_content[1], false );

		if ( ! empty( $this->head_scripts ) || ! empty( $this->footer_scripts ) ) {
			// Re-order moving to footer JS files
			$ordered_moving_js    = array_intersect_key( $this->move_to_footer_js, $this->move_to_footer );
			$ordered_moving_js    = array_map( array( $this, 'getpath' ), $ordered_moving_js );
			$this->footer_scripts = array_merge( $ordered_moving_js, $this->footer_scripts );

			// JS Scripts found, we can start processing them.
			return true;
		}

		// The page holds no JS scripts
		return false;
	}

	/**
	 * Returns the found javascript
	 *
	 * @param string $content HTML content
	 * @param bool $head to process header or not.
	 *
	 * @return bool
	 * @since 1.1.8
	 * @access private
	 */
	private function fetch_javascript( $content = '', $head = true ) {
		$cdn_array_move_to_footer = array();

		if ( ! empty( $this->move_to_footer_js ) ) {
			foreach ( $this->move_to_footer_js as $index => $key ) {
				$cdn_array_move_to_footer[ $this->url_replace_cdn( $index ) ] = $this->url_replace_cdn( $key );
				$index                                                        = ltrim( $index, 'https:' );
				$key                                                          = ltrim( $key, 'https:' );
				$cdn_array_move_to_footer[ $this->url_replace_cdn( $index ) ] = $this->url_replace_cdn( $key );
			}
		}

		if ( preg_match_all( '#<script.*</script>#Usmi', $content, $matches ) ) {

			if ( wp_script_is( 'spai-sniper', 'enqueued' ) ) {
				$jquery_local_path         = home_url( '/wp-includes/js/jquery/jquery.js' );
				$this->custom_js_exclude[] = $jquery_local_path;
			}

			if ( isset( $matches[0] ) && ! empty( $matches[0] ) ) {
				$matches[0] = $this->delay_script_loading( $matches[0] );
			}

			foreach ( $matches[0] as $tag ) {
				if ( ! empty( $tag ) ) {
					$tag = str_replace( '”', '"', $tag );
				}

				if ( true === breeze_is_script_ignored_from_delay( $tag ) ) {
					$tag = '';
					continue;
				}

				// only consider aggregation whitelisted in should_aggregate-function
				if ( ! $this->should_aggregate( $tag ) ) {
					$tag = '';
					continue;
				}

				// handle only the scripts that have the a file as source.
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

					if ( true === $this->is_inline_delay_on ) {
						if ( $path !== false && preg_match( '#\.js$#', $path ) ) {

							if ( $this->is_merge_valid( $tag ) ) {
								//We can merge it
								if ( true === $head ) {
									// If this file will be move to footer
									$compare_url  = ltrim( $url, 'https:' );
									$cdn_url      = $this->url_replace_cdn( $url );
									$cdn_url_trim = ltrim( $cdn_url, 'https:' );

									if (
										( in_array( $compare_url, $this->move_to_footer_js ) ||
										  in_array( $url, $this->move_to_footer_js ) ||
										  in_array( $cdn_url, $this->move_to_footer_js ) ||
										  in_array( $cdn_url_trim, $this->move_to_footer_js )
										) ||
										(
											in_array( $compare_url, $cdn_array_move_to_footer ) ||
											in_array( $url, $cdn_array_move_to_footer ) ||
											in_array( $cdn_url, $cdn_array_move_to_footer ) ||
											in_array( $cdn_url_trim, $cdn_array_move_to_footer )
										)
									) {
										$this->footer_scripts[ $url ] = $path;
										$content                      = str_replace( $tag, '', $content );
									} else {
										$this->head_scripts[ $url ] = $path;
									}
								} else {
									$this->footer_scripts[ $url ] = $path;
									$content                      = str_replace( $tag, '', $content );
								}
							} else {
								//No merge, but maybe we can move it
								if ( $this->is_movable( $tag ) ) {
									//Yeah, move it
									if ( $this->move_to_last( $tag ) ) {
										$this->move['last'][] = $tag;
									} else {
										$this->move['first'][] = $tag;
									}
								} else {
									$is_delayed = $this->is_inline_delay( $tag );
									if ( $is_delayed ) {
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
										$content = str_replace( $tag, '', $content );
									}

									//We shouldn't touch this
									$tag = '';
								}
							}
						} else {
							if ( breeze_validate_url_via_regexp( $url ) ) {

								if ( true === $head ) {

									if ( in_array( $url, $this->move_to_footer_js ) ) {
										$this->move_to_footer[ $url ] = $url;
									} else {
										$this->head_scripts[ $url ] = $url;
									}
								} else {
									$this->footer_scripts[ $url ] = $url;
								}

								//Remove the original script tag
								$content = str_replace( $tag, '', $content );
							}
						}
					} else {
						if ( $path !== false && preg_match( '#\.js$#', $path ) ) {
							//Inline
							if ( $this->is_merge_valid( $tag ) ) {
								//We can merge it
								if ( true === $head ) {
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
								if ( $this->is_movable( $tag ) ) {
									//Yeah, move it
									if ( $this->move_to_last( $tag ) ) {
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
							if ( $this->is_movable( $tag ) ) {
								if ( $this->move_to_last( $tag ) ) {
									$this->move['last'][] = $tag;
								} else {
									$this->move['first'][] = $tag;
								}

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

								}
							} else {
								$is_delayed = $this->ignore_from_delay( $tag );
								if ( $is_delayed ) {
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
					}
				} else {

					if ( true === $this->is_inline_delay_on ) {
						$is_delayed = $this->is_inline_delay( $tag );
						if ( true === $is_delayed ) {

							preg_match( '#<script.*>(.*)</script>#Usmi', $tag, $code );
							$code = preg_replace( '#.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*#sm', '$1', $code[1] );
							$code = preg_replace( '/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $code );
							if ( true === $head ) {
								$this->delay_scripts['header'][] = $code;
							} else {
								$this->delay_scripts['footer'][] = $code;
							}
							$content = str_replace( $tag, '', $content );
						}
					} else {

						// Inline script
						if ( $this->isremovable( $tag, $this->jsremovables ) ) {
							$content = str_replace( $tag, '', $content );
							continue;
						}

						// unhide comments, as javascript may be wrapped in comment-tags for old times' sake
						$tag = $this->restore_comments( $tag );
						if ( $this->is_merge_valid( $tag ) ) {

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
							if ( $this->is_movable( $tag ) ) {
								if ( $this->move_to_last( $tag ) ) {
									$this->move['last'][] = $tag;
								} else {
									$this->move['first'][] = $tag;
								}
							} else {
								$tag = '';
							}
						}
						// re-hide comments to be able to do the removal based on tag from $this->content
						$tag = $this->hide_comments( $tag );
					}

				}
				//Remove the original script tag
				if ( false === $this->is_inline_delay_on ) {
					$content = str_replace( $tag, '', $content );
				}
			}
		}

		if ( true === $head ) {
			$this->content = $content;
		} else {
			$this->content .= '</head>' . $content;
		}

		return true;
	}

	/**
	 * Needed function to match Breeze_MinificationBase class pattern
	 *
	 * @since 1.1.8
	 * @access public
	 */
	public function minify() {

		return true;
	}

	/**
	 * Needed function to match Breeze_MinificationBase class pattern
	 *
	 * @since 1.1.8
	 * @access public
	 */
	public function cache() {

		return true;
	}

	/**
	 * Needed function to match Breeze_MinificationBase class pattern
	 *
	 * @since 1.1.8
	 * @access public
	 */
	public function getcontent() {
		if ( ! empty( $this->cdn_url ) ) {
			foreach ( $this->defer_js as $index => $key ) {
				$this->defer_js[ $this->url_replace_cdn( $index ) ] = $this->url_replace_cdn( $key );
				$index                                              = ltrim( $index, 'https:' );
				$key                                                = ltrim( $key, 'https:' );
				$this->defer_js[ $this->url_replace_cdn( $index ) ] = $this->url_replace_cdn( $key );
			}
		} else {
			foreach ( $this->defer_js as $index => $key ) {
				unset( $this->defer_js[ $index ] );
				$index                    = ltrim( $index, 'https:' );
				$key                      = ltrim( $key, 'https:' );
				$this->defer_js[ $index ] = $key;
			}
		}

		if ( true === $this->is_inline_delay_on ) {

			// Load inline JS to html
			if ( ! empty( $this->head_scripts ) ) {

				$replaceTag = array( '</head>', 'before' );
				$js_head    = array();

				foreach ( $this->head_scripts as $js_url => $js_path ) {
					$defer = '';

					if ( ! empty( $this->cdn_url ) ) {
						$js_url = $this->url_replace_cdn( $js_url );
					}

					$js_url_trim = ltrim( $js_url, 'https:' );

					if (
						gettype( $js_url ) == 'string' &&
						(
							in_array( $js_url, $this->defer_js ) ||
							in_array( $js_url_trim, $this->defer_js ) ||
							$this->is_inline_delay( $js_url )
						)
					) {
						$defer = 'defer ';
					}

					$js_head[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
				}
				$js_replacement = '';
				$js_replacement .= implode( '', $js_head );

				if ( ! empty( $this->move['first'] ) ) {
					$js_replacement_first = implode( '', $this->move['first'] );
					$js_replacement       .= $js_replacement_first;
				}

				$this->inject_in_html( $js_replacement, $replaceTag );
			}

			if ( ! empty( $this->footer_scripts ) ) {
				$replaceTag = array( '</body>', 'before' );
				$js_footer  = array();

				foreach ( $this->footer_scripts as $js_url => $js_path ) {
					$defer = '';
					if ( ! empty( $this->cdn_url ) ) {
						$js_url = $this->url_replace_cdn( $js_url );
					}

					$js_url_trim = ltrim( $js_url, 'https:' );

					if (
						gettype( $js_url ) == 'string' &&
						(
							in_array( $js_url, $this->defer_js ) ||
							in_array( $js_url_trim, $this->defer_js ) ||
							$this->is_inline_delay( $js_url )
						)
					) {
						$defer = 'defer ';
					}

					$js_footer[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
				}
				$js_replacement = '';
				$js_replacement .= implode( '', $js_footer );

				if ( ! empty( $this->move['last'] ) ) {
					$js_replacement .= implode( '', $this->move['last'] );
				}

				$this->inject_in_html( $js_replacement, $replaceTag );
			}


			// handle the 3rd party defer scripts in header.
			if ( ! empty( $this->delay_scripts ) && ! empty( $this->delay_scripts['header'] ) ) {
				$replace_tag   = array( '</head>', 'before' );
				$js_head_defer = array();
				$defer         = 'defer';
				foreach ( $this->delay_scripts['header'] as $js_url => $js_script ) {
					if ( is_string( $js_url ) ) {
						$js_url = trim( $js_url, '”' );
					}

					if ( filter_var( $js_url, FILTER_VALIDATE_URL ) ) {
						if ( ! empty( $js_script['version'] ) ) {
							$js_url_add = '?' . $js_script['version'];
							$js_url_add = trim( $js_url_add, "'" );
						}
						$js_head_defer[] = "<script type='application/javascript' {$defer} src='{$js_url_add}'></script>\n";
					} else {
						$js_head_defer[] = "<script type='module'>{$js_script}</script>\n";
					}
				}
				$js_replacement = '';
				$js_replacement .= implode( '', $js_head_defer );
				$this->inject_in_html( $js_replacement, $replace_tag );
			}

			// handle the 3rd party defer scripts in footer.
			if ( ! empty( $this->delay_scripts ) && ! empty( $this->delay_scripts['footer'] ) ) {
				$replace_tag     = array( '</body>', 'before' );
				$js_footer_defer = array();
				$defer           = 'defer';
				foreach ( $this->delay_scripts['footer'] as $js_url => $js_script ) {
					if ( is_string( $js_url ) ) {
						$js_url = trim( $js_url, '”' );
					}

					if ( filter_var( $js_url, FILTER_VALIDATE_URL ) ) {
						if ( ! empty( $js_script['version'] ) ) {
							$js_url_add = $js_url . '?' . $js_script['version'];
							$js_url_add = trim( $js_url_add, "'" );
						}
						$js_footer_defer[] = "<script type=\"application/javascript\" {$defer} src=\"{$js_url_add}\"></script>\n";
					} else {
						$js_footer_defer[] = "<script type='module'>{$js_script}</script>\n";
					}
				}

				$js_replacement = '';
				$js_replacement .= implode( '', $js_footer_defer );
				$this->inject_in_html( $js_replacement, $replace_tag );
			}

		} else {

			// Load inline JS to html
			if ( ! empty( $this->head_scripts ) ) {

				$replaceTag = array( '</head>', 'before' );
				$js_head    = array();

				foreach ( $this->head_scripts as $js_url => $js_path ) {
					$defer = '';

					if ( ! empty( $this->cdn_url ) ) {
						$js_url = $this->url_replace_cdn( $js_url );
					}

					$js_url_trim = ltrim( $js_url, 'https:' );

					if (
						gettype( $js_url ) == 'string' &&
						(
							in_array( $js_url, $this->defer_js ) ||
							in_array( $js_url_trim, $this->defer_js )
						)
					) {
						$defer = 'defer ';
					}

					if ( empty( $defer ) ) {
						$delay_defer = 'false';
					} else {
						$delay_defer = 'true';
					}
					#$defer = "true";
					if ( false !== strpos( $js_path, 'INLINE;' ) ) {
						$js_path = str_replace( 'INLINE;', '', $js_path );

						if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_url ) ) {
							$js_head[] = '<div class="breeze-scripts-load" data-file="0" data-async="false" data-locate="head" data-defer="' . $delay_defer . '" style="display:none">' . htmlspecialchars( $js_path, ENT_QUOTES ) . '</div>' . "\n";
						} else {
							$js_head[] = "<script type='application/javascript' {$defer}>{$js_path}</script>\n";
						}
					} else {
						if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_url ) ) {
							$js_head[] = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="head" data-defer="' . $delay_defer . '" style="display:none">' . $js_url . '</div>' . "\n";
						} else {
							$js_head[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
						}

					}

					//$js_head[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
				}

				$js_replacement = '';
				$js_replacement .= implode( '', $js_head );


				if ( ! empty( $this->move['first'] ) ) {
					$js_replacement_first = implode( '', $this->move['first'] );
					$js_replacement       .= $js_replacement_first;
				}


				$this->inject_in_html( $js_replacement, $replaceTag );
			}

			if ( ! empty( $this->footer_scripts ) ) {
				$replaceTag = array( '</body>', 'before' );
				$js_footer  = array();

				foreach ( $this->footer_scripts as $js_url => $js_path ) {
					$defer = '';
					if ( ! empty( $this->cdn_url ) ) {
						$js_url = $this->url_replace_cdn( $js_url );
					}

					$js_url_trim = ltrim( $js_url, 'https:' );

					if (
						gettype( $js_url ) == 'string' &&
						(
							in_array( $js_url, $this->defer_js ) ||
							in_array( $js_url_trim, $this->defer_js )
						)
					) {
						$defer = 'defer ';
					}

					if ( empty( $defer ) ) {
						$delay_defer = 'false';
					} else {
						$delay_defer = 'true';
					}


					if ( false !== strpos( $js_path, 'INLINE;' ) ) {
						$js_path = str_replace( 'INLINE;', '', $js_path );
						if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_path ) ) {
							$js_footer[] = '<div class="breeze-scripts-load" data-file="0" data-async="false" data-locate="footer" data-defer="' . $delay_defer . '" style="display:none">' . htmlspecialchars( $js_path, ENT_QUOTES ) . '</div>' . "\n";
						} else {
							$js_footer[] = "<script type='application/javascript' {$defer}>{$js_path}</script>\n";
						}

					} else {
						if ( true === $this->delay_javascript && false === $this->ignore_from_delay( $js_url ) ) {
							$js_footer[] = '<div class="breeze-scripts-load" data-file="1" data-async="false" data-locate="footer" data-defer="' . $delay_defer . '" style="display:none">' . $js_url . '</div>' . "\n";
						} else {
							$js_footer[] = "<script type='application/javascript' {$defer}src='{$js_url}'></script>\n";
						}

					}
				}
				$js_replacement = '';
				$js_replacement .= implode( '', $js_footer );

				if ( ! empty( $this->move['last'] ) ) {
					$js_replacement .= implode( '', $this->move['last'] );
				}

				$this->inject_in_html( $js_replacement, $replaceTag );
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

					$js_replacement = '';
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

					$js_replacement = '';
					$js_replacement .= implode( '', $js_footer );
				$this->inject_in_html( $js_replacement, $replaceTag );
			}
			}

			if ( true === $this->delay_javascript ) {
				$delay_script_js = breeze_load_delay_script();
				$replace_tag     = array( '</body>', 'before' );
				$this->inject_in_html( $delay_script_js, $replace_tag );
			}

		}


		// restore comments
		$this->content = $this->restore_comments( $this->content );

		// Restore IE hacks
		$this->content = $this->restore_iehacks( $this->content );

		// Restore noptimize
		$this->content = $this->restore_noptimize( $this->content );

		return $this->content;
	}

	// Checks against the white- and blacklists
	private function is_merge_valid( $tag ) {
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

			if ( $this->move_to_last( $tag ) ) {
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

	/**
	 * Check if the script can be moved
	 *
	 * @param $tag
	 *
	 * @return bool
	 * @since 1.1.8
	 * @access private
	 */
	private function is_movable( $tag ) {

		foreach ( $this->domove as $match ) {
			if ( strpos( $tag, $match ) !== false ) {
				//Matched something
				return true;
			}
		}

		if ( $this->move_to_last( $tag ) ) {
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
	 * Move the script last
	 *
	 * @param $tag
	 *
	 * @return bool
	 * @since 1.1.8
	 * @access private
	 */
	private function move_to_last( $tag ) {
		foreach ( $this->domovelast as $match ) {
			if ( strpos( $tag, $match ) !== false ) {
				//Matched, return true
				return true;
			}
		}

		//Should be in 'first'
		return false;
	}

	/**
	 * Determines whether a <script> $tag should be aggregated or not.
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
	 * Change the URL from local domain to CDN domain.
	 *
	 * @param string $url the given URL
	 *
	 * @return mixed|void
	 */
	protected function url_replace_cdn( $url ) {
		$cdn_url = apply_filters( 'breeze_filter_base_cdnurl', $this->cdn_url );
		if ( ! empty( $cdn_url ) ) {
			// secondly prepend domain-less absolute URL's
			if ( ( substr( $url, 0, 1 ) === '/' ) && ( substr( $url, 1, 1 ) !== '/' ) ) {
				if ( ! is_string( $cdn_url ) ) {
					$cdn_url = '';
				}
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
			} else if ( true === $add_later ) {
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
