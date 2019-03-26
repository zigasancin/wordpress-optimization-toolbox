<?php
/**
 * Smush API class that handles communications with WPMU DEV API: WP_Smush_API class
 *
 * @since 3.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_API.
 */
class WP_Smush_API {

	/**
	 * Endpoint name.
	 *
	 * @since 3.0
	 *
	 * @var string
	 */
	public $name = 'smush';

	/**
	 * Endpoint version.
	 *
	 * @since 3.0
	 *
	 * @var string
	 */
	public $version = 'v1';

	/**
	 * API key.
	 *
	 * @since 3.0
	 *
	 * @var string
	 */
	public $api_key = '';

	/**
	 * API request instance.
	 *
	 * @since 3.0
	 *
	 * @var WP_Smush_API_Request
	 */
	private $request;

	/**
	 * WP_Smush_API constructor.
	 *
	 * @since 3.0
	 *
	 * @param string $key  API key.
	 *
	 * @throws Exception  API Request exception.
	 */
	public function __construct( $key ) {
		$this->api_key = $key;
		$this->request = new WP_Smush_API_Request( $this );
	}

	/**
	 * Check CDN status (same as verify the is_pro status).
	 *
	 * @since 3.0
	 *
	 * @return mixed|WP_Error
	 */
	public function check() {
		return $this->request->get(
			"check/{$this->api_key}",
			array(
				'api_key' => $this->api_key,
				'domain'  => $this->request->get_this_site(),
			)
		);
	}

	/**
	 * Enable CDN for site.
	 *
	 * @since 3.0
	 *
	 * @return mixed|WP_Error
	 */
	public function enable() {
		return $this->request->post(
			'cdn',
			array(
				'api_key' => $this->api_key,
				'domain'  => $this->request->get_this_site(),
			)
		);
	}

}
