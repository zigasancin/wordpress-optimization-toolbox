<?php
/**
 * Class WP_Smush_Modules.
 *
 * Used in WP_Smush_Core to type hint the $mod variable. For example, this way any calls to
 * WP_Smush::get_instance()->core()->mod->settings will be typehinted as a call to WP_Smush_Settings module.
 *
 * @package WP_Smush
 */

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
	 * WP_Smush_Modules constructor.
	 */
	public function __construct() {
		$this->db       = new WP_Smush_DB();
		$this->dir      = new WP_Smush_Dir();
		$this->smush    = new WP_Smushit();
		$this->backup   = new WP_Smush_Backup( $this->smush );
		$this->png2jpg  = new WP_Smush_Png2jpg();
		$this->resize   = new WP_Smush_Resize();
		$this->cdn      = new WP_Smush_CDN();
		$this->settings = WP_Smush_Settings::get_instance();

		$this->init_compat();
	}

	/**
	 * Hold all globals for compatibility.
	 *
	 * @since 3.0
	 */
	private function init_compat() {
		global $WpSmush, $wpsmushit_admin, $wpsmush_backup, $wpsmush_db, $wpsmush_dir, $wpsmush_pngjpg, $wpsmush_resize, $wpsmush_settings;

		/**
		 * Former WpSmush class.
		 *
		 * @deprecated
		 */
		$WpSmush = $this->smush;

		/**
		 * Former WpSmushitAdmin class.
		 *
		 * @deprecated
		 */
		$wpsmushit_admin = $WpSmush;

		/**
		 * Former WpSmushBackup class.
		 *
		 * @deprecated
		 */
		$wpsmush_backup = $this->backup;

		/**
		 * Former WpSmushDB class.
		 *
		 * @deprecated
		 */
		$wpsmush_db = $this->db;

		/**
		 * Former WpSmushDir class.
		 *
		 * @deprecated
		 */
		$wpsmush_dir = $this->dir;

		/**
		 * Former WpSmushPngtoJpg class.
		 *
		 * @deprecated
		 */
		$wpsmush_pngjpg = $this->png2jpg;

		/**
		 * Former WpSmushResize class.
		 *
		 * @deprecated
		 */
		$wpsmush_resize = $this->resize;

		/**
		 * Former WpSmushSettings class.
		 *
		 * @deprecated
		 */
		$wpsmush_settings = $this->settings;
	}

}
