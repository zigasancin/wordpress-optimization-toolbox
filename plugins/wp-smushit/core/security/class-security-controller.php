<?php

namespace Smush\Core\Security;

use Smush\Core\Controller;
use Smush\Core\Cron_Controller;

class Security_Controller extends Controller {
	/**
	 * @var Security_Controller
	 */
	private static $instance;

	private $security_utils;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->security_utils = new Security_Utils();
	}
}
