<?php
/**
 * Created by PhpStorm.
 * User: Mihai Irodiu from WPRiders
 * Date: 07.06.2021
 * Time: 13:22
 */

class Breeze_Cli_Helpers {

	/**
	 * Fetch remote JSON.
	 *
	 * @param $url - remote JSON url
	 *
	 * @since 1.2.2
	 * @access public
	 * @static
	 */
	public static function fetch_remote_json( $url ) {
		$rop_user_agent = 'breeze-import-settings-system';

		$ssl_verification = apply_filters('breeze_ssl_check_certificate', true);
		$verify_host = 2;

		if ( ! is_bool( $ssl_verification ) ) {
			$ssl_verification = true;
		}

		if(defined('WP_DEBUG') && true === WP_DEBUG){
			$ssl_verification = false;
			$verify_host = 0;
		}

		$connection = curl_init( $url );
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, $verify_host );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, $ssl_verification );
		curl_setopt( $connection, CURLOPT_USERAGENT, $rop_user_agent );
		curl_setopt( $connection, CURLOPT_REFERER, home_url() );
		curl_setopt( $connection, CURLOPT_MAXREDIRS, 3 );
		curl_setopt( $connection, CURLOPT_FOLLOWLOCATION, true );

		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
		);

		curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

		$fetch_response = curl_exec( $connection );
		$http_code      = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
		$curl_err_no    = curl_errno( $connection );
		if ( $curl_err_no ) {
			$curl_err_msg = curl_error( $connection );
		}

		curl_close( $connection );

		if ( 200 !== (int) $http_code ) {
			return new WP_Error( 'url-err', __( 'Remote file could not be reached', 'breeze' ) );
		}

		if ( $curl_err_no ) {
			return new WP_Error( 'remote-err', $curl_err_msg );
		} else {
			return $fetch_response;
		}

	}

	public static function cache_helper_display(){
		WP_CLI::line( '---' );
		WP_CLI::line( WP_CLI::colorize( '%Ywp breeze purge --cache=<all|varnish|local>%n is the full command:' ) );
		WP_CLI::line( WP_CLI::colorize( '%Y--cache=%n%Gall%n will clear local cache and varnish cache.' ) );
		WP_CLI::line( WP_CLI::colorize( '%Y--cache=%n%Gvarnish%n will clear varnish cache only.' ) );
		WP_CLI::line( WP_CLI::colorize( '%Y--cache=%n%Glocal%n will clear local cache only.' ) );
		WP_CLI::line( WP_CLI::colorize( '%Y--level=%n%GblogID|network%n will clear cache for the specified blogID or at network level(all sub-sites).' ) );
		WP_CLI::line( '---' );
	}
}
