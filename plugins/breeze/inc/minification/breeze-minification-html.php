<?php
/*
 *  Based on some work of autoptimize plugin
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Breeze_MinificationHtml extends Breeze_MinificationBase {
	private $keepcomments          = false;
	private $exclude               = array( '<!-- ngg_resource_manager_marker -->' );
	private $original_content      = '';
	private $show_original_content = 0;
	private $do_process            = false;
	private $forcexhtml            = false;

	public function read( $options ) {
		$this_path_url = $this->get_cache_file_url( '' );
		if ( false === breeze_is_process_locked( $this_path_url ) ) {
			$this->do_process = breeze_lock_cache_process( $this_path_url );
		} else {
			$this->original_content = $this->content;

			return true;
		}

		// Remove the HTML comments?
		$this->keepcomments = (bool) $options['keepcomments'];

		// filter to force xhtml
		$this->forcexhtml = (bool) apply_filters( 'breeze_filter_html_forcexhtml', false );

		// filter to add strings to be excluded from HTML minification
		$excludeHTML = apply_filters( 'breeze_filter_html_exclude', '' );
		if ( $excludeHTML !== '' ) {
			$exclHTMLArr   = array_filter( array_map( 'trim', explode( ',', $excludeHTML ) ) );
			$this->exclude = array_merge( $exclHTMLArr, $this->exclude );
		}

		// Nothing else for HTML
		return true;
	}

	//Joins and optimizes CSS
	public function minify() {
		if ( false === $this->do_process ) {
			return true;
		}

		$noptimizeHTML = apply_filters( 'breeze_filter_html_noptimize', false, $this->content );
		if ( $noptimizeHTML ) {
			return false;
		}

		if ( class_exists( 'Minify_HTML' ) ) {
			// wrap the to-be-excluded strings in noptimize tags
			foreach ( $this->exclude as $exclString ) {
				if ( strpos( $this->content, $exclString ) !== false ) {
					$replString    = '<!--noptimize-->' . $exclString . '<!--/noptimize-->';
					$this->content = str_replace( $exclString, $replString, $this->content );
				}
			}

			// noptimize me
			$this->content = $this->hide_noptimize( $this->content );

			// Minify html
			$options = array( 'keepComments' => $this->keepcomments );
			if ( $this->forcexhtml ) {
				$options['xhtml'] = true;
			}

			if ( method_exists( 'Minify_HTML', 'minify' ) ) {
				$tmp_content = Minify_HTML::minify( $this->content, $options );
				if ( ! empty( $tmp_content ) ) {
					$this->content = $tmp_content;
					unset( $tmp_content );
				}
			}

			// restore noptimize
			$this->content = $this->restore_noptimize( $this->content );

			// remove the noptimize-wrapper from around the excluded strings
			foreach ( $this->exclude as $exclString ) {
				$replString = '<!--noptimize-->' . $exclString . '<!--/noptimize-->';
				if ( strpos( $this->content, $replString ) !== false ) {
					$this->content = str_replace( $replString, $exclString, $this->content );
				}
			}

			return true;
		}

		// Didn't minify :(
		return false;
	}

	// Does nothing
	public function cache() {
		//No cache for HTML
		return true;
	}

	//Returns the content
	public function getcontent() {
		if ( ! empty( $this->show_original_content ) ) {
			return $this->original_content;
		}

		if ( true === $this->do_process ) {
			$this_path_url = $this->get_cache_file_url( '' );
			breeze_unlock_process( $this_path_url );
			return $this->content;
		} else {
			return $this->original_content;
		}

		//return $this->content;
	}
}
