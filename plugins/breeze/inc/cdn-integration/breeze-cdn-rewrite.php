<?php
/**
 *  @copyright 2017  Cloudways  https://www.cloudways.com
 *
 *  Original development of this plugin by JoomUnited https://www.joomunited.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class Breeze_CDN_Rewrite {
	private $blog_url = null;
	private $cdn_url  = null;
	private $dirs     = array();
	private $excludes = array();
	private $relative = false;

	public function __construct( &$option ) {
		//storage option
		$this->blog_url = get_option( 'home' );
		$this->cdn_url  = $option['cdn-url'];
		$this->dirs     = $option['cdn-content'];
		$this->excludes = $option['cdn-exclude-content'];
		$this->relative = $option['cdn-relative-path'];

		$this->hardcoded_exceptions_to_ignore();
	}

	/**
	 * Handles extra exceptions which need to be excluded
	 * and instead use local URL instead of CDN.
	 *
	 * @since 1.1.3
	 */
	private function hardcoded_exceptions_to_ignore() {
		if ( ! array( $this->excludes ) || empty( $this->excludes ) ) {
			$this->excludes = array();
		}
		$this->excludes [] = 'download_file';
		// Allow users to use filter and add exceptions from CDN url.
		$this->excludes = apply_filters( 'breeze_cdn_exclude_paths', $this->excludes );
	}

	/*
	 * Replace cdn on html raw
	 */
	public function rewrite( $content ) {

		$blog_url = quotemeta( $this->blog_url );

		// get dir scope in regex format
		$dirs = $this->get_dir_scope();

		// regex rule start
		$regex_rule = '#(?<=[(\"\'])';

		// create blog url without http or https
		$parseurl = parse_url( $this->blog_url );
		$scheme   = 'http:';
		if ( ! empty( $parseurl['scheme'] ) ) {
			$scheme = $parseurl['scheme'] . ':';
		}
		$blog_url_short = str_replace( $scheme, '', $this->blog_url );

		// check if relative paths
		if ( $this->relative ) {
			$regex_rule .= '(?:' . $blog_url . '|' . $blog_url_short . ')?';
		} else {
			$regex_rule .= '(' . $blog_url . '|' . $blog_url_short . ')';
		}

		// regex rule end
		$regex_rule .= '/(?:((?:' . $dirs . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';

		// call the cdn rewriter callback
		$new_content = preg_replace_callback( $regex_rule, array( &$this, 'replace_cdn_url' ), $content );

		return $new_content;
	}

	/**
	 * get directory scope
	 */

	protected function get_dir_scope() {
		// default
		if ( empty( $this->dirs ) || count( $this->dirs ) < 1 ) {
			return 'wp\-content|wp\-includes';
		}

		return implode( '|', array_map( 'quotemeta', array_map( 'trim', $this->dirs ) ) );
	}

	/*
	 * Replace cdn url to root url
	 */
	protected function replace_cdn_url( $match ) {
		//return file type or directories excluded
		if ( $this->excludes_check( $match[0] ) ) {
			return $match[0];
		}

		$parseUrl = parse_url( $this->blog_url );
		$scheme   = 'http://';
		if ( isset( $parseUrl['scheme'] ) ) {
			$scheme = $parseUrl['scheme'] . '://';
		}
		$host = $parseUrl['host'];
		//get domain
		$domain = '//' . $host;

		// check if not a relative path
		if ( ! $this->relative || strstr( $match[0], $this->blog_url ) ) {
			$domain = $scheme . $host;
		}

		return str_replace( $domain, $this->cdn_url, $match[0] );

	}
	/*
	 * Check excludes assets
	 */
	protected function excludes_check( $dir ) {
		if ( ! empty( $this->excludes ) ) {
			foreach ( $this->excludes as $exclude ) {
				if ( stristr( $dir, $exclude ) != false ) {
					return true;
				}
			}
		}
		return false;
	}
}
