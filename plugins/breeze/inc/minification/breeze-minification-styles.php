<?php
/*
 *  Based on some work of autoptimize plugin
 */

use MatthiasMullie\Minify;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
class Breeze_MinificationStyles extends Breeze_MinificationBase {
	private $css                   = array();
	private $csscode               = array();
	private $url                   = array();
	private $restofcontent         = '';
	private $mhtml                 = '';
	private $datauris              = false;
	private $hashmap               = array();
	private $alreadyminified       = false;
	private $inline                = false;
	private $defer                 = false;
	private $defer_inline          = false;
	private $whitelist             = '';
	private $cssinlinesize         = '';
	private $cssremovables         = array();
	private $include_inline        = false;
	private $font_swap             = false;
	private $inject_min_late       = '';
	private $group_css             = false;
	private $custom_css_exclude    = array();
	private $css_group_val         = array();
	private $css_min_arr           = array();
	private $issetminfile          = false;
	private $url_group_arr         = array();
	private $include_imported_css  = false;
	private $original_content      = '';
	private $show_original_content = 0;
	private $do_process            = false;
	private $dontmove              = false;


	//Reads the page and collects style tags
	public function read( $options ) {
		$this->include_imported_css = filter_var( $options['include_imported_css'], FILTER_VALIDATE_BOOLEAN );

		$this_path_url = $this->get_cache_file_url( 'css' );
		if ( false === breeze_is_process_locked( $this_path_url ) ) {
			$this->do_process = breeze_lock_cache_process( $this_path_url );
		} else {
			$this->original_content = $this->content;

			return true;
		}

		$noptimizeCSS = apply_filters( 'breeze_filter_css_noptimize', false, $this->content );
		if ( $noptimizeCSS ) {
			return false;
		}
		$whitelistCSS = apply_filters( 'breeze_filter_css_whitelist', '' );
		if ( ! empty( $whitelistCSS ) ) {
			$this->whitelist = array_filter( array_map( 'trim', explode( ',', $whitelistCSS ) ) );
		}

		if ( ! is_array( $this->whitelist ) ) {
			$this->whitelist = array();
		}

		if ( $options['nogooglefont'] == true ) {
			$removableCSS = 'fonts.googleapis.com';
		} else {
			$removableCSS = '';
		}
		$removableCSS = apply_filters( 'breeze_filter_css_removables', $removableCSS );
		if ( ! empty( $removableCSS ) ) {
			$this->cssremovables = array_filter( array_map( 'trim', explode( ',', $removableCSS ) ) );
		}
		$this->cssinlinesize = apply_filters( 'breeze_filter_css_inlinesize', 256 );
		// filter to "late inject minified CSS", default to true for now (it is faster)
		$this->inject_min_late = apply_filters( 'breeze_filter_css_inject_min_late', true );
		// Remove everything that's not the header
		if ( apply_filters( 'breeze_filter_css_justhead', $options['justhead'] ) == true ) {
			$content             = explode( '</head>', $this->content, 2 );
			$this->content       = $content[0] . '</head>';
			$this->restofcontent = $content[1];
		}
		// include inline?
		if ( apply_filters( 'breeze_css_include_inline', $options['include_inline'] ) == true ) {
			$this->include_inline = true;
		}
		// group css?
		if ( apply_filters( 'breeze_css_include_inline', $options['groupcss'] ) == true ) {
			$this->group_css = true;
		}

		// group css?
		if ( apply_filters( 'breeze_css_font_swap', $options['font_swap'] ) == true ) {
			$this->font_swap = true;
		}

		//custom js exclude
		if ( ! empty( $options['custom_css_exclude'] ) ) {
			$this->custom_css_exclude = array_merge( $this->custom_css_exclude, $options['custom_css_exclude'] );
		}
		// what CSS shouldn't be autoptimized
		$excludeCSS = $options['css_exclude'];
		$excludeCSS = apply_filters( 'breeze_filter_css_exclude', $excludeCSS );
		if ( $excludeCSS !== '' ) {
			$this->dontmove = array_filter( array_map( 'trim', explode( ',', $excludeCSS ) ) );
		} else {
			$this->dontmove = array();
		}
		// should we defer css?
		// value: true/ false
		$this->defer = $options['defer'];
		$this->defer = apply_filters( 'breeze_filter_css_defer', $this->defer );
		// should we inline while deferring?
		// value: inlined CSS
		$this->defer_inline = $options['defer_inline'];
		// should we inline?
		// value: true/ false
		$this->inline = $options['inline'];
		$this->inline = apply_filters( 'breeze_filter_css_inline', $this->inline );
		// get cdn url
		$this->cdn_url = $options['cdn_url'];
		// Store data: URIs setting for later use
		$this->datauris = $options['datauris'];
		// noptimize me
		$this->content = $this->hide_noptimize( $this->content );
		// exclude (no)script, as those may contain CSS which should be left as is
		if ( strpos( $this->content, '<script' ) !== false ) {
			$this->content = preg_replace_callback(
				'#<(?:no)?script.*?<\/(?:no)?script>#is',
				function ( $matches ) {
					return '%%SCRIPT' . breeze_HASH . '%%' . base64_encode( $matches[0] ) . '%%SCRIPT%%';
				},
				$this->content
			);
		}
		// Save IE hacks
		$this->content = $this->hide_iehacks( $this->content );
		// hide comments
		$this->content = $this->hide_comments( $this->content );
		// Get <style> and <link>
		if ( preg_match_all( '#(<style[^>]*>.*</style>)|(<link[^>]*stylesheet[^>]*>)#Usmi', $this->content, $matches ) ) {
			foreach ( $matches[0] as $tag ) {
				if ( $this->isremovable( $tag, $this->cssremovables ) ) {
					$this->content = str_replace( $tag, '', $this->content );
				} elseif ( $this->ismovable( $tag ) ) {
					// Get the media
					if ( strpos( $tag, 'media=' ) !== false ) {
						preg_match( '#media=(?:"|\')([^>]*)(?:"|\')#Ui', $tag, $medias );
						$medias = explode( ',', $medias[1] );
						$media  = array();
						foreach ( $medias as $elem ) {
							if ( empty( $elem ) ) {
								$elem = 'all';
							}
							$media[] = $elem;
						}
					} else {
						// No media specified - applies to all
						$media = array( 'all' );
					}
					$media = apply_filters( 'breeze_filter_css_tagmedia', $media, $tag );
					if ( preg_match( '#<link.*href=("|\')(.*)("|\')#Usmi', $tag, $source ) ) {
						// <link>
						$url = current( explode( '?', $source[2], 2 ) );
						// Let's check if this file is in the excluded list.
						$is_excluded = breeze_is_string_in_array_values( $url, $this->custom_css_exclude );
						//exclude css file
						if ( ! empty( $is_excluded ) ) {
							continue;
						}

						// Treat special exceptions that might break front-end for admin/editor/author/contributor.
						$is_an_exception = $this->breeze_css_files_exceptions( $url );
						if ( true === $is_an_exception ) {
							continue;
						}

						$path = $this->getpath( $url );
						if ( $path !== false && preg_match( '#\.css$#', $path ) ) {
							// Good link
							$this->css[] = array( $media, $path );
						} else {
							// Link is dynamic (.php etc)
							$tag = '';
						}
					} else {
						// inline css in style tags can be wrapped in comment tags, so restore comments
						$tag = $this->restore_comments( $tag );
						preg_match( '#<style.*>(.*)</style>#Usmi', $tag, $code );
						// and re-hide them to be able to to the removal based on tag
						$tag = $this->hide_comments( $tag );
						if ( $this->include_inline ) {
							$code = preg_replace( '#^.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*$#sm', '$1', $code[1] );
							if ( true == $this->group_css ) {
								// Not the problem
								if ( isset( $media[0] ) && 'print' === trim( $media[0] ) ) {
									if ( false === strpos( $code, '@media' ) ) {
										$code = '@media print{' . $code . '}';
									}
								} elseif ( isset( $media[0] ) && 'speech' === trim( $media[0] ) ) {
									if ( false === strpos( $code, '@media' ) ) {
										$code = '@media speech{' . $code . '}';
									}
								} elseif ( isset( $media[0] ) && 'screen' === trim( $media[0] ) ) {
									if ( false === strpos( $code, '@media' ) ) {
										$code = '@media screen{' . $code . '}';
									}
								}
							}
							$is_elementor_exception = false;
							if ( defined( 'ELEMENTOR_VERSION' ) || defined( 'ELEMENTOR_PRO_VERSION' ) ) {
								$is_elementor_exception = true;
							}

							if ( false === $is_elementor_exception ) {
								$this->css[] = array( $media, 'INLINE;' . $code );
							} else {
								if ( false === strpos( $code, '.elementor-' ) ) {
									$this->css[] = array( $media, 'INLINE;' . $code );
								} else {
									$tag = '';
								}
							}
						} else {
							$tag = '';
						}
					}
					// Remove the original style tag
					$this->content = str_replace( $tag, '', $this->content );
				}
			}

			return true;
		}

		// Really, no styles?
		return false;
	}

	// Joins and optimizes CSS
	public function minify() {

		if ( false === $this->do_process ) {
			return true;
		}

		foreach ( $this->css as $group ) {
			list( $media, $css ) = $group;
			if ( preg_match( '#^INLINE;#', $css ) ) {
				// <style>
				$css      = preg_replace( '#^INLINE;#', '', $css );
				$css      = $this->fixurls( ABSPATH . '/index.php', $css );
				$tmpstyle = apply_filters( 'breeze_css_individual_style', $css, '' );
				if ( has_filter( 'breeze_css_individual_style' ) && ! empty( $tmpstyle ) ) {
					$css                   = $tmpstyle;
					$this->alreadyminified = true;
				}
			} else {
				//<link>
				if ( $css !== false && file_exists( $css ) && is_readable( $css ) ) {
					$cssPath = $css;
					$css     = $this->fixurls( $cssPath, file_get_contents( $cssPath ) );
					$css     = preg_replace( '/\x{EF}\x{BB}\x{BF}/', '', $css );
					if (
						false !== strpos( $css, '.elementor-products-grid ul.products.elementor-grid li.product' ) ||
						false !== strpos( $css, 'li.product,.woocommerce-page ul.products[class*=columns-] li.product' )
					) {

					}

					$tmpstyle = apply_filters( 'breeze_css_individual_style', $css, $cssPath );
					if ( has_filter( 'breeze_css_individual_style' ) && ! empty( $tmpstyle ) ) {
						$css                   = $tmpstyle;
						$this->alreadyminified = true;
					} elseif ( $this->can_inject_late( $cssPath, $css ) ) {
						$css = '%%INJECTLATER' . breeze_HASH . '%%' . base64_encode( $cssPath ) . '|' . hash( 'sha512', $css ) . '%%INJECTLATER%%';
					}
				} else {
					// Couldn't read CSS. Maybe getpath isn't working?
					$css = '';
				}
			}

			$is_elementor_exception = false;
			if ( class_exists( 'WooCommerce' ) && ( defined( 'ELEMENTOR_VERSION' ) || defined( 'ELEMENTOR_PRO_VERSION' ) ) ) {
				$is_elementor_exception = true;
			}

			if ( $this->group_css == true ) {
				foreach ( $media as $elem ) {
					if ( ! isset( $this->csscode[ $elem ] ) ) {
						$this->csscode[ $elem ] = '';
					}
					if ( $is_elementor_exception && false !== strpos( $css, 'li.product,.woocommerce-page ul.products[class*=columns-] li.product' ) ) {
						$this->csscode['all'] .= "\n/*FILESTART*/" . "@media {$elem}{" . $css . '}'; // TODO aici se strica
					} else {
						$this->csscode[ $elem ] .= "\n/*FILESTART*/" . "@media {$elem}{" . $css . '}';
					}
				}
			} else {
				foreach ( $media as $elem ) {
					$this->css_group_val[] = $elem . '_breezecssgroup_' . $css;
				}
			}
		}
		if ( $this->group_css == true ) {
			// Check for duplicate code
			$md5list = array();
			$tmpcss  = $this->csscode;
			foreach ( $tmpcss as $media => $code ) {
				$md5sum    = hash('sha512', $code);
				$medianame = $media;
				foreach ( $md5list as $med => $sum ) {
					// If same code
					if ( $sum === $md5sum ) {
						//Add the merged code
						$medianame                   = $med . ', ' . $media;
						$this->csscode[ $medianame ] = $code;
						$md5list[ $medianame ]       = $md5list[ $med ];
						unset( $this->csscode[ $med ], $this->csscode[ $media ] );
						unset( $md5list[ $med ] );
					}
				}
				$md5list[ $medianame ] = $md5sum;
			}
			unset( $tmpcss );
			// Manage @imports, while is for recursive import management
			foreach ( $this->csscode as &$thiscss ) {
				// Flag to trigger import reconstitution and var to hold external imports
				$fiximports       = false;
				$external_imports = '';
				while ( preg_match_all( '#^(/*\s?)@import.*(?:;|$)#Um', $thiscss, $matches ) ) {
					foreach ( $matches[0] as $import ) {

						if ( $this->isremovable( $import, $this->cssremovables ) ) {

							$thiscss   = str_replace( $import, '', $thiscss );
							$import_ok = true;
						} else {
							$url       = trim( preg_replace( '#^.*((?:https?:|ftp:)?//.*\.css).*$#', '$1', trim( $import ) ), " \t\n\r\0\x0B\"'" );
							$path      = $this->getpath( $url );
							$import_ok = false;

							if ( true === $this->include_imported_css && file_exists( $path ) && is_readable( $path ) ) { // add settings for this

								$code     = addcslashes( $this->fixurls( $path, file_get_contents( $path ) ), '\\' );
								$code     = preg_replace( '/\x{EF}\x{BB}\x{BF}/', '', $code );
								$tmpstyle = apply_filters( 'breeze_css_individual_style', $code, '' );
								if ( has_filter( 'breeze_css_individual_style' ) && ! empty( $tmpstyle ) ) {
									$code                  = $tmpstyle;
									$this->alreadyminified = true;
								} elseif ( $this->can_inject_late( $path, $code ) ) {
									$code = '%%INJECTLATER' . breeze_HASH . '%%' . base64_encode( $path ) . '|' . hash('sha512', $code) . '%%INJECTLATER%%';
								}
								if ( ! empty( $code ) ) {
									$tmp_thiscss = preg_replace( '#(/\*FILESTART\*/.*)' . preg_quote( $import, '#' ) . '#Us', '/*FILESTART2*/' . $code . '$1', $thiscss );
									if ( ! empty( $tmp_thiscss ) ) {
										$thiscss   = $tmp_thiscss;
										$import_ok = true;
										unset( $tmp_thiscss );
									}
									unset( $code );
								}
							}
						}
						if ( ! $import_ok ) {
							// external imports and general fall-back
							$external_imports .= $import;
							$thiscss           = str_replace( $import, '', $thiscss );
							$fiximports        = true;
						}
					}
					$thiscss = preg_replace( '#/\*FILESTART\*/#', '', $thiscss );
					$thiscss = preg_replace( '#/\*FILESTART2\*/#', '/*FILESTART*/', $thiscss );
				}
				// add external imports to top of aggregated CSS
				if ( $fiximports ) {
					$thiscss = $external_imports . $thiscss;
				}
			}
			unset( $thiscss );
			// $this->csscode has all the uncompressed code now.
			$mhtmlcount = 0;
			foreach ( $this->csscode as &$code ) {
				// Check for already-minified code
				$hash   = hash('sha512', $code);
				$ccheck = new Breeze_MinificationCache( $hash, 'css' );
				if ( $ccheck->check() ) {
					$code                          = $ccheck->retrieve();
					$this->hashmap[ hash('sha512', $code) ] = $hash;
					continue;
				}
				unset( $ccheck );
				// Do the imaging!
				$imgreplace = array();
				preg_match_all( '#(background[^;}]*url\((?!\s?"?\s?data)(.*)\)[^;}]*)(?:;|$|})#Usm', $code, $matches );
				if ( ( $this->datauris == true ) && ( function_exists( 'base64_encode' ) ) && ( is_array( $matches ) ) ) {
					foreach ( $matches[2] as $count => $quotedurl ) {
						$iurl = trim( $quotedurl, " \t\n\r\0\x0B\"'" );
						// if querystring, remove it from url
						if ( strpos( $iurl, '?' ) !== false ) {
							$iurl = strtok( $iurl, '?' );
						}
						$ipath            = $this->getpath( $iurl );
						$datauri_max_size = 4096;
						$datauri_max_size = (int) apply_filters( 'breeze_filter_css_datauri_maxsize', $datauri_max_size );
						$datauri_exclude  = apply_filters( 'breeze_filter_css_datauri_exclude', '' );
						if ( ! empty( $datauri_exclude ) ) {
							$no_datauris = array_filter( array_map( 'trim', explode( ',', $datauri_exclude ) ) );
							foreach ( $no_datauris as $no_datauri ) {
								if ( strpos( $iurl, $no_datauri ) !== false ) {
									$ipath = false;
									break;
								}
							}
						}
						if ( $ipath != false && preg_match( '#\.(jpe?g|png|gif|bmp)$#i', $ipath ) && file_exists( $ipath ) && is_readable( $ipath ) && filesize( $ipath ) <= $datauri_max_size ) {
							$ihash  = hash('sha512', $ipath);
							$icheck = new Breeze_MinificationCache( $ihash, 'img' );
							if ( $icheck->check() ) {
								// we have the base64 image in cache
								$headAndData = $icheck->retrieve();
								$_base64data = explode( ';base64,', $headAndData );
								$base64data  = $_base64data[1];
							} else {
								// It's an image and we don't have it in cache, get the type
								$explA = explode( '.', $ipath );
								$type  = end( $explA );
								switch ( $type ) {
									case 'jpeg':
										$dataurihead = 'data:image/jpeg;base64,';
										break;
									case 'jpg':
										$dataurihead = 'data:image/jpeg;base64,';
										break;
									case 'gif':
										$dataurihead = 'data:image/gif;base64,';
										break;
									case 'png':
										$dataurihead = 'data:image/png;base64,';
										break;
									case 'bmp':
										$dataurihead = 'data:image/bmp;base64,';
										break;
									default:
										$dataurihead = 'data:application/octet-stream;base64,';
								}
								// Encode the data
								$base64data  = base64_encode( file_get_contents( $ipath ) );
								$headAndData = $dataurihead . $base64data;
								// Save in cache
								$icheck->cache( $headAndData, 'text/plain' );
							}
							unset( $icheck );
							// Add it to the list for replacement
							$imgreplace[ $matches[1][ $count ] ] = str_replace( $quotedurl, $headAndData, $matches[1][ $count ] ) . ";\n*" . str_replace( $quotedurl, 'mhtml:%%MHTML%%!' . $mhtmlcount, $matches[1][ $count ] ) . ";\n_" . $matches[1][ $count ] . ';';
							// Store image on the mhtml document
							$this->mhtml .= "--_\r\nContent-Location:{$mhtmlcount}\r\nContent-Transfer-Encoding:base64\r\n\r\n{$base64data}\r\n";
							$mhtmlcount ++;
						} else {
							// just cdn the URL if applicable
							if ( ! empty( $this->cdn_url ) ) {
								$url                                 = trim( $quotedurl, " \t\n\r\0\x0B\"'" );
								$cdn_url                             = $this->url_replace_cdn( $url );
								$imgreplace[ $matches[1][ $count ] ] = str_replace( $quotedurl, $cdn_url, $matches[1][ $count ] );
							}
						}
					}
				} elseif ( ( is_array( $matches ) ) && ( ! empty( $this->cdn_url ) ) ) {
					// change background image urls to cdn-url
					foreach ( $matches[2] as $count => $quotedurl ) {
						$url                                 = trim( $quotedurl, " \t\n\r\0\x0B\"'" );
						$cdn_url                             = $this->url_replace_cdn( $url );
						$imgreplace[ $matches[1][ $count ] ] = str_replace( $quotedurl, $cdn_url, $matches[1][ $count ] );
					}
				}
				if ( ! empty( $imgreplace ) ) {
					$code = str_replace( array_keys( $imgreplace ), array_values( $imgreplace ), $code );
				}
				// CDN the fonts!
				if ( ( ! empty( $this->cdn_url ) ) && ( apply_filters( 'breeze_filter_css_fonts_cdn', false ) ) && ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) ) {
					$fontreplace = array();
					include_once( BREEZE_PLUGIN_DIR . 'inc/minification/config/minificationFontRegex.php' );
					preg_match_all( $fonturl_regex, $code, $matches );
					if ( is_array( $matches ) ) {
						foreach ( $matches[8] as $count => $quotedurl ) {
							$url                                  = trim( $quotedurl, " \t\n\r\0\x0B\"'" );
							$cdn_url                              = $this->url_replace_cdn( $url );
							$fontreplace[ $matches[8][ $count ] ] = str_replace( $quotedurl, $cdn_url, $matches[8][ $count ] );
						}
						if ( ! empty( $fontreplace ) ) {
							$code = str_replace( array_keys( $fontreplace ), array_values( $fontreplace ), $code );
						}
					}
				}
				// Minify
				if ( ( $this->alreadyminified !== true ) && ( apply_filters( 'breeze_css_do_minify', true ) ) ) {

					if ( class_exists( 'MatthiasMullie\Minify\CSS' ) ) {
						//$tmp_code = trim( Minify_CSS_Compressor::process( $code ) );
						$minifier = new MatthiasMullie\Minify\CSS();
						$minifier->add( $code );
						$tmp_code = $minifier->minify();

					} elseif ( class_exists( 'CSSmin' ) ) {
						$cssmin = new CSSmin();

						if ( method_exists( $cssmin, 'run' ) ) {
							$tmp_code = trim( $cssmin->run( $code ) );
						} elseif ( @is_callable( array( $cssmin, 'minify' ) ) ) {
							$tmp_code = trim( CssMin::minify( $code ) );
						}
					}
					if ( ! empty( $tmp_code ) ) {
						$code = $tmp_code;
						unset( $tmp_code );
					}
				}
				$code     = $this->inject_minified( $code );
				$tmp_code = apply_filters( 'breeze_css_after_minify', $code );
				if ( ! empty( $tmp_code ) ) {
					$code = $tmp_code;
					unset( $tmp_code );
				}
				$this->hashmap[ hash('sha512', $code) ] = $hash;
			}
			unset( $code );
		} else {
			foreach ( $this->css_group_val as $value ) {
				$media  = substr( $value, 0, strpos( $value, '_breezecssgroup_' ) );
				$css    = substr( $value, strpos( $value, '_breezecssgroup_' ) + strlen( '_breezecssgroup_' ) );
				$hash   = hash('sha512', $css);
				$ccheck = new Breeze_MinificationCache( $hash, 'css' );
				if ( $ccheck->check() ) {
					$css_exist           = $ccheck->retrieve();
					$this->css_min_arr[] = $media . '_breezemedia_' . $hash . '_breezekey_' . $css_exist;
					continue;
				}
				unset( $ccheck );
				// Minify

				if ( class_exists( 'MatthiasMullie\Minify\CSS' ) ) {
					//$tmp_code = trim( Minify_CSS_Compressor::process( $css ) );
					$minifier = new MatthiasMullie\Minify\CSS();
					$minifier->add( $css );
					$tmp_code = $minifier->minify();

				} elseif ( class_exists( 'CSSmin' ) ) {
					$cssmin = new CSSmin();
					if ( method_exists( $cssmin, 'run' ) ) {
						$tmp_code = trim( $cssmin->run( $css ) );
					} elseif ( @is_callable( array( $cssmin, 'minify' ) ) ) {
						$tmp_code = trim( CssMin::minify( $css ) );
					}
				}
				if ( ! empty( $tmp_code ) ) {
					$css = $tmp_code;
					unset( $tmp_code );
				}
				$css                 = $this->inject_minified( $css );
				$css                 = apply_filters( 'breeze_css_after_minify', $css );
				$this->css_min_arr[] = $media . '_breezemedia_' . $hash . '_breezekey_' . $css;
			}
			unset( $css );
		}

		return true;
	}

	//Caches the CSS in uncompressed, deflated and gzipped form.
	public function cache() {
		if ( false === $this->do_process ) {
			return true;
		}

		if ( $this->datauris ) {
			// MHTML Preparation
			$this->mhtml = "/*\r\nContent-Type: multipart/related; boundary=\"_\"\r\n\r\n" . $this->mhtml . "*/\r\n";
			$md5         = hash('sha512', $this->mhtml );
			$cache       = new Breeze_MinificationCache( $md5, 'txt' );
			if ( ! $cache->check() ) {
				// Cache our images for IE
				$cache->cache( $this->mhtml, 'text/plain' );
			}
			$mhtml = breeze_CACHE_URL . breeze_current_user_type() . $cache->getname();

		}
		if ( $this->group_css == true ) {
			$whole_css_file = '';
			// CSS cache
			foreach ( $this->csscode as $media => $code ) {

				if ( $this->datauris ) {
					// Images for ie! Get the right url
					$code = str_replace( '%%MHTML%%', $mhtml, $code );
				}
				// compile all the CSS together to create a single file.
				$whole_css_file .= $code;
			}

			$whole_css_file = $this->append_font_swap( $whole_css_file );
			$md5            = hash('sha512', $whole_css_file);
			$cache          = new Breeze_MinificationCache( $md5, 'css' );
			if ( ! $cache->check() ) {
				// Cache our code
				$cache->cache( $whole_css_file, 'text/css' );
			}

			$cache_file_url  = breeze_CACHE_URL . breeze_current_user_type() . $cache->getname();
			$cache_directory = $cache->get_cache_dir();

			if ( $this->is_cache_file_present( $cache_directory . $cache->get_file_name() ) ) {
				$this->url['all'] = $cache_file_url;
			} else {
				$this->show_original_content = 1;
				$this->clear_cache_data();
			}
		} else {
			$url_exists = true;
			foreach ( $this->css_min_arr as $value ) {
				$media = substr( $value, 0, strpos( $value, '_breezemedia_' ) );
				$code  = substr( $value, strpos( $value, '_breezemedia_' ) + strlen( '_breezemedia_' ) );
				$hash  = substr( $code, 0, strpos( $code, '_breezekey_' ) );
				$css   = substr( $code, strpos( $code, '_breezekey_' ) + strlen( '_breezekey_' ) );
				$cache = new Breeze_MinificationCache( $hash, 'css' );
				if ( ! $cache->check() ) {
					// Cache our code
					$css = $this->append_font_swap( $css );
					$cache->cache( $css, 'text/css' );
				}

				$cache_directory = $cache->get_cache_dir();

				if ( ! file_exists( $cache_directory . $cache->get_file_name() ) ) {
					$url_exists = false;
				} else {
					$this->url_group_arr[] = $media . '_breezemedia_' . $hash . '_breezekey_' . breeze_CACHE_URL . breeze_current_user_type() . $cache->getname();
				}
			}

			if ( false === $url_exists ) {
				$this->show_original_content = 1;
				$this->clear_cache_data();
			}
		}
	}

	//Returns the content
	public function getcontent() {

		if ( ! empty( $this->show_original_content ) ) {
			return $this->original_content;
		}
		// restore IE hacks
		$this->content = $this->restore_iehacks( $this->content );
		// restore comments
		$this->content = $this->restore_comments( $this->content );
		// restore (no)script
		if ( strpos( $this->content, '%%SCRIPT%%' ) !== false ) {
			$this->content = preg_replace_callback(
				'#%%SCRIPT' . breeze_HASH . '%%(.*?)%%SCRIPT%%#is',
				function ( $matches ) {
					return base64_decode( $matches[1] );
				},
				$this->content
			);
		}
		// restore noptimize
		$this->content = $this->restore_noptimize( $this->content );
		//Restore the full content
		if ( ! empty( $this->restofcontent ) ) {
			$this->content      .= $this->restofcontent;
			$this->restofcontent = '';
		}
		// Inject the new stylesheets
		$replaceTag = array( '<title', 'before' );
		$replaceTag = apply_filters( 'breeze_filter_css_replacetag', $replaceTag );
		if ( $this->group_css == true ) {
			if ( $this->inline == true ) {
				foreach ( $this->csscode as $media => $code ) {
					$this->inject_in_html( '<style type="text/css" media="' . $media . '">' . $code . '</style>', $replaceTag );
				}
			} else {
				if ( $this->defer == true ) {
					$deferredCssBlock  = "<script data-cfasync='false'>function lCss(url,media) {var d=document;var l=d.createElement('link');l.rel='stylesheet';l.type='text/css';l.href=url;l.media=media;aoin=d.getElementsByTagName('noscript')[0];aoin.parentNode.insertBefore(l,aoin.nextSibling);}function deferredCSS() {";
					$noScriptCssBlock  = '<noscript>';
					$defer_inline_code = $this->defer_inline;
					$defer_inline_code = apply_filters( 'breeze_filter_css_defer_inline', $defer_inline_code );
					if ( ! empty( $defer_inline_code ) ) {
						$iCssHash  = hash('sha512', $defer_inline_code);
						$iCssCache = new Breeze_MinificationCache( $iCssHash, 'css' );
						if ( $iCssCache->check() ) {
							// we have the optimized inline CSS in cache
							$defer_inline_code = $iCssCache->retrieve();
						} else {

							if ( class_exists( 'MatthiasMullie\Minify\CSS' ) ) {
								//$tmp_code = trim( Minify_CSS_Compressor::process( $this->defer_inline ) );
								$minifier = new MatthiasMullie\Minify\CSS();
								$minifier->add( $this->defer_inline );
								$tmp_code = $minifier->minify();

							} elseif ( class_exists( 'CSSmin' ) ) {
								$cssmin   = new CSSmin();
								$tmp_code = trim( $cssmin->run( $defer_inline_code ) );
							}
							if ( ! empty( $tmp_code ) ) {
								$defer_inline_code = $tmp_code;
								$iCssCache->cache( $defer_inline_code, 'text/css' );
								unset( $tmp_code );
							}
						}
						$code_out = '<style type="text/css" id="aoatfcss" media="all">' . $defer_inline_code . '</style>';
						$this->inject_in_html( $code_out, $replaceTag );
					}
				}
				foreach ( $this->url as $media => $url ) {
					$url = $this->url_replace_cdn( $url );
					//Add the stylesheet either deferred (import at bottom) or normal links in head
					if ( $this->defer == true ) {
						$deferredCssBlock .= "lCss('" . $url . "','" . $media . "');";
						$noScriptCssBlock .= '<link type="text/css" media="' . $media . '" href="' . $url . '" rel="stylesheet" />';
					} else {
						if ( strlen( $this->csscode[ $media ] ) > $this->cssinlinesize ) {
							$this->inject_in_html( '<link type="text/css" media="' . $media . '" href="' . $url . '" rel="stylesheet" />', $replaceTag );
						} elseif ( strlen( $this->csscode[ $media ] ) > 0 ) {
							$this->inject_in_html( '<style type="text/css" media="' . $media . '">' . $this->csscode[ $media ] . '</style>', $replaceTag );
						}
					}
				}
				if ( $this->defer == true ) {
					$deferredCssBlock .= "}if(window.addEventListener){window.addEventListener('DOMContentLoaded',deferredCSS,false);}else{window.onload = deferredCSS;}</script>";
					$noScriptCssBlock .= '</noscript>';
					$this->inject_in_html( $noScriptCssBlock, $replaceTag );
					$this->inject_in_html( $deferredCssBlock, array( '</body>', 'before' ) );
				}
			}
		} else {
			if ( $this->inline == true ) {
				foreach ( $this->csscode as $media => $code ) {
					$this->inject_in_html( '<style type="text/css" media="' . $media . '">' . $code . '</style>', $replaceTag );
				}
			} else {
				if ( $this->defer == true ) {
					$deferredCssBlock  = "<script data-cfasync='false'>function lCss(url,media) {var d=document;var l=d.createElement('link');l.rel='stylesheet';l.type='text/css';l.href=url;l.media=media;aoin=d.getElementsByTagName('noscript')[0];aoin.parentNode.insertBefore(l,aoin.nextSibling);}function deferredCSS() {";
					$noScriptCssBlock  = '<noscript>';
					$defer_inline_code = $this->defer_inline;
					$defer_inline_code = apply_filters( 'breeze_filter_css_defer_inline', $defer_inline_code );
					if ( ! empty( $defer_inline_code ) ) {
						$iCssHash  = hash('sha512', $defer_inline_code);
						$iCssCache = new Breeze_MinificationCache( $iCssHash, 'css' );
						if ( $iCssCache->check() ) {
							// we have the optimized inline CSS in cache
							$defer_inline_code = $iCssCache->retrieve();
						} else {

							if ( class_exists( 'MatthiasMullie\Minify\CSS' ) ) {
								//$tmp_code = trim( Minify_CSS_Compressor::process( $this->defer_inline ) );
								$minifier = new MatthiasMullie\Minify\CSS();
								$minifier->add( $this->defer_inline );
								$tmp_code = $minifier->minify();

							} elseif ( class_exists( 'CSSmin' ) ) {
								$cssmin   = new CSSmin();
								$tmp_code = trim( $cssmin->run( $defer_inline_code ) );
							}
							if ( ! empty( $tmp_code ) ) {
								$defer_inline_code = $tmp_code;
								$iCssCache->cache( $defer_inline_code, 'text/css' );
								unset( $tmp_code );
							}
						}
						$code_out = '<style type="text/css" id="aoatfcss" media="all">' . $defer_inline_code . '</style>';
						$this->inject_in_html( $code_out, $replaceTag );
					}
				}
				foreach ( $this->url_group_arr as $value ) {
					$media = substr( $value, 0, strpos( $value, '_breezemedia_' ) );
					$code  = substr( $value, strpos( $value, '_breezemedia_' ) + strlen( '_breezemedia_' ) );
					$hash  = substr( $code, 0, strpos( $code, '_breezekey_' ) );
					$url   = substr( $code, strpos( $code, '_breezekey_' ) + strlen( '_breezekey_' ) );
					$cache = new Breeze_MinificationCache( $hash, 'css' );
					if ( $cache->check() ) {
						$csscode = $cache->retrieve();
					}
					//Add the stylesheet either deferred (import at bottom) or normal links in head
					if ( $this->defer == true ) {
						$deferredCssBlock .= "lCss('" . $url . "','" . $media . "');";
						$noScriptCssBlock .= '<link type="text/css" media="' . $media . '" href="' . $url . '" rel="stylesheet" />';
					} else {
						if ( strlen( $csscode ) > $this->cssinlinesize ) {
							$url = $this->url_replace_cdn( $url );
							$this->inject_in_html( '<link type="text/css" media="' . $media . '" href="' . $url . '" rel="stylesheet" />', $replaceTag );
						} elseif ( strlen( $csscode ) > 0 ) {
							$this->inject_in_html( '<style type="text/css" media="' . $media . '">' . $csscode . '</style>', $replaceTag );
						}
					}
				}
				if ( $this->defer == true ) {
					$deferredCssBlock .= "}if(window.addEventListener){window.addEventListener('DOMContentLoaded',deferredCSS,false);}else{window.onload = deferredCSS;}</script>";
					$noScriptCssBlock .= '</noscript>';
					$this->inject_in_html( $noScriptCssBlock, $replaceTag );
					$this->inject_in_html( $deferredCssBlock, array( '</body>', 'before' ) );
				}
			}
		}

		if ( true === $this->do_process ) {
			$this_path_url = $this->get_cache_file_url( 'css' );
			breeze_unlock_process( $this_path_url );

			return $this->content;
		} else {
			return $this->original_content;
		}
		//Return the modified stylesheet
		//return $this->content;
	}

	static function fixurls( $file, $code ) {
		$file = str_replace( BREEZE_ROOT_DIR, '/', $file );
		$dir  = dirname( $file ); //Like /wp-content
		// quick fix for import-troubles in e.g. arras theme
		$code = preg_replace( '#@import ("|\')(.+?)\.css("|\')#', '@import url("${2}.css")', $code );
		if ( preg_match_all( '#url\((?!data)(?!\#)(?!"\#)(.*)\)#Usi', $code, $matches ) ) {
			$replace = array();
			foreach ( $matches[1] as $k => $url ) {
				// Remove quotes
				$url    = trim( $url, " \t\n\r\0\x0B\"'" );
				$noQurl = trim( $url, "\"'" );
				if ( $url !== $noQurl ) {
					$removedQuotes = true;
				} else {
					$removedQuotes = false;
				}
				$url = $noQurl;
				if ( substr( $url, 0, 1 ) == '/' || preg_match( '#^(https?://|ftp://|data:)#i', $url ) ) {
					//URL is absolute
					continue;
				} else {
					// relative URL
					$newurl = preg_replace( '/https?:/', '', str_replace( ' ', '%20', breeze_WP_ROOT_URL . str_replace( '//', '/', $dir . '/' . $url ) ) );
					$hash   = hash('sha512', $url);
					$code   = str_replace( $matches[0][ $k ], $hash, $code );
					if ( ! empty( $removedQuotes ) ) {
						$replace[ $hash ] = 'url(\'' . $newurl . '\')';
					} else {
						$replace[ $hash ] = 'url(' . $newurl . ')';
					}
				}
			}
			//Do the replacing here to avoid breaking URLs
			$code = str_replace( array_keys( $replace ), array_values( $replace ), $code );
		}

		return $code;
	}

	private function ismovable( $tag ) {
		if ( ! empty( $this->whitelist ) ) {
			foreach ( $this->whitelist as $match ) {
				if ( strpos( $tag, $match ) !== false ) {
					return true;
				}
			}

			// no match with whitelist
			return false;
		} else {
			if ( is_array( $this->dontmove ) && ! empty( $this->dontmove ) ) {
				foreach ( $this->dontmove as $match ) {
					if ( strpos( $tag, $match ) !== false ) {
						//Matched something
						return false;
					}
				}
			}

			//If we're here it's safe to move
			return true;
		}
	}

	private function can_inject_late( $cssPath, $css ) {
		if ( ( strpos( $cssPath, 'min.css' ) === false ) || ( $this->inject_min_late !== true ) ) {
			// late-inject turned off or file not minified based on filename
			return false;
		} elseif ( strpos( $css, '@import' ) !== false ) {
			// can't late-inject files with imports as those need to be aggregated
			return false;
		} elseif ( ( strpos( $css, '@font-face' ) !== false ) && ( apply_filters( 'breeze_filter_css_fonts_cdn', false ) === true ) && ( ! empty( $this->cdn_url ) ) ) {
			// don't late-inject CSS with font-src's if fonts are set to be CDN'ed
			return false;
		} elseif ( ( ( $this->datauris == true ) || ( ! empty( $this->cdn_url ) ) ) && preg_match( '#background[^;}]*url\(#Ui', $css ) ) {
			// don't late-inject CSS with images if CDN is set OR is image inlining is on
			return false;
		} else {
			// phew, all is safe, we can late-inject
			return true;
		}
	}


	/**
	 * Search for specific exceptions.
	 * Files that should not be included in grouping.
	 *
	 * @param $needle
	 *
	 * @return bool
	 * @since 1.1.3
	 */
	private function breeze_css_files_exceptions( $needle ) {
		$search_patterns = array(
			'huebert\.[a-zA-Z0-9]*\.css',
			'app\.[a-zA-Z0-9]*\.css',
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
	 * Append font-display: wap parameter to font-face definitions.
	 *
	 * @param string $code
	 *
	 * @return mixed|string|string[]
	 * @since 1.2.0
	 * @access private
	 */
	private function append_font_swap( $code = '' ) {
		if ( false === $this->font_swap ) {
			return $code;
		}

		if ( ! empty( $code ) ) {
			preg_match_all( '/[\s+]?\@font-face[\s+]?(\{[a-zA-Z\s\:\;\0-9\,\?\=]+\})/mi', $code, $matches );
			if ( isset( $matches ) && ! empty( $matches ) && isset( $matches[0] ) && ! empty( $matches[0] ) ) {
				foreach ( $matches[0] as $index => $css_font_face ) {
					if ( ! substr_count( $css_font_face, 'font-display' ) ) {
						$font_display = str_replace( '{', '{font-display:swap;', $css_font_face );
						$code         = str_replace( $css_font_face, $font_display, $code );
					}
				}
			}
		}

		return $code;
	}
}
