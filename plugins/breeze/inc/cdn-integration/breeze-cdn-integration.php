<?php
/**
 * @copyright 2017  Cloudways  https://www.cloudways.com
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

class Breeze_CDN_Integration {

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_rewrite_cdn' ) );
	}

	/**
	 * Execute rewrite cdn
	 */
	public function handle_rewrite_cdn() {

		$cdn_integration = Breeze_Options_Reader::get_option_value( 'cdn_integration', true );

		if ( empty( Breeze_Options_Reader::get_option_value( 'cdn-active' ) ) ) {
			return;
		}

		if ( empty( Breeze_Options_Reader::get_option_value( 'cdn-url' ) ) ) {
			return;
		}

		if ( get_option( 'home' ) == Breeze_Options_Reader::get_option_value( 'cdn-url' ) ) {
			return;
		}

		if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['job_id'] ) && ! empty( $_GET['job_id'] ) ) {
			return;
		}

		$rewrite = new Breeze_CDN_Rewrite( $cdn_integration );

		//rewrite CDN Url to html raw
		//        ob_start(array(&$rewrite,'rewrite'));
		add_filter( 'breeze_cdn_content_return', array( &$rewrite, 'rewrite' ) );

	}

	public static function instance() {
		new self();
	}
}
