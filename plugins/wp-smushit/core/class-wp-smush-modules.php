<?php
/**
 * Class WP_Smush_Modules.
 *
 * Used in WP_Smush_Core to type hint the $mod variable. For example, this way any calls to
 * WP_Smush::get_instance()->core()->mod->settings will be typehinted as a call to WP_Smush_Settings module.
 *
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WP_Smush_Modules
 */
class WP_Smush_Modules {

	/**
	 * Database module.
	 *
	 * @var WP_Smush_DB
	 */
	public $db;

	/**
	 * Directory Smush module.
	 *
	 * @var WP_Smush_Dir
	 */
	public $dir;

	/**
	 * Main Smush module.
	 *
	 * @var WP_Smushit
	 */
	public $smush;

	/**
	 * Backup module.
	 *
	 * @var WP_Smush_Backup
	 */
	public $backup;

	/**
	 * PNG 2 JPG module.
	 *
	 * @var WP_Smush_Png2jpg
	 */
	public $png2jpg;

	/**
	 * Resize module.
	 *
	 * @var WP_Smush_Resize
	 */
	public $resize;

	/**
	 * CDN module.
	 *
	 * @var WP_Smush_CDN
	 */
	public $cdn;

	/**
	 * Settings module.
	 *
	 * @var WP_Smush_Settings
	 */
	public $settings;

	/**
	 * Image lazy load module.
	 *
	 * @since 3.2
	 *
	 * @var WP_Smush_Lazy_Load
	 */
	public $lazy;

	/**
	 * WP_Smush_Modules constructor.
	 */
	public function __construct() {
		$this->db       = new WP_Smush_DB();
		$this->dir      = new WP_Smush_Dir();
		$this->smush    = new WP_Smushit();
		$this->backup   = new WP_Smush_Backup( $this->smush );
		$this->png2jpg  = new WP_Smush_Png2jpg();
		$this->resize   = new WP_Smush_Resize();
		$this->settings = WP_Smush_Settings::get_instance();

		$page_parser = new WP_Smush_Page_Parser();
		$this->cdn   = new WP_Smush_CDN( $page_parser );
		$this->lazy  = new WP_Smush_Lazy_Load( $page_parser );
	}

}
