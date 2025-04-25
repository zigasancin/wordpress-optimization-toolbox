<?php

/**
 * Compatibility with Avada theme cache reset.
 */
class Breeze_Avada_Cache {
	private $run_time = 0;

	private static $instance = null;

	function __construct() {
		add_action( 'fusion_cache_reset_after', array( &$this, 'clear_breeze_cache' ), 99 );
	}

	/**
	 * On Avada cache clear, also clear breeze cache.
	 *
	 * @return void
	 * @since 2.0.12
	 * @access public
	 */
	public function clear_breeze_cache() {
		if ( 0 === $this->run_time && wp_doing_ajax() ) {
			//delete minify
			Breeze_MinificationCache::clear_minification();
			//clear normal cache
			Breeze_PurgeCache::breeze_cache_flush( false, true, true );

			$admin = new Breeze_Admin();
			$admin->breeze_clear_varnish();

			$this->run_time ++;
		}
	}

	public static function get_instance(): ?Breeze_Avada_Cache {
		if ( null === self::$instance ) {
			self::$instance = new Breeze_Avada_Cache();
		}

		return self::$instance;
	}
}

Breeze_Avada_Cache::get_instance();
