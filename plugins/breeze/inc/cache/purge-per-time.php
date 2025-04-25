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

class Breeze_PurgeCacheTime {
	protected $timettl      = false;
	protected $normalcache  = 0;
	protected $varnishcache = 0;

	public function __construct( $settings = null ) {
		if ( is_array( $settings ) ) {
			if ( array_key_exists( 'breeze-b-ttl', $settings ) && ! is_null( $settings['breeze-b-ttl'] ) ) {
				$this->timettl = $settings['breeze-b-ttl'];
			}

			if ( isset( $settings['breeze-active'] ) ) {
				$this->normalcache = (int) $settings['breeze-active'];
			}

			if ( isset( $settings['breeze-varnish-purge'] ) ) {
				$this->varnishcache = (int) $settings['breeze-varnish-purge'];
			}
		}

		add_action( 'breeze_purge_cache', array( $this, 'schedule_varnish' ) );
		add_action( 'init', array( $this, 'schedule_events' ) );
		add_filter( 'cron_schedules', array( $this, 'filter_cron_schedules' ) );

	}

	//     * Unschedule events
	public function unschedule_events() {
		$timestamp = wp_next_scheduled( 'breeze_purge_cache' );

		wp_unschedule_event( $timestamp, 'breeze_purge_cache' );
	}

	//

	/** Setup for schedule_events
	 * TODO: Rethink the current logic as it has flaws and is fired for no actual reason
	 *
	 * @param $time
	 *
	 * @return void
	 */
	public function schedule_events( $time = 0 ) {

		$timestamp = wp_next_scheduled( 'breeze_purge_cache' );

		// If the timer exists and is set by the user to zero ( 0 ) then remove the cache.
		if ( ! is_bool( $this->timettl ) && 0 === (int) $this->timettl ) {
			wp_unschedule_event( $timestamp, 'breeze_purge_cache' );

			return;
		}

		// If the next schedule does not exist, and we have custom value timer.
		if ( ! $timestamp && $time ) {
			wp_schedule_event( $time * 60, 'breeze_varnish_time', 'breeze_purge_cache' );

			return;
		}

		// If the scedule does not exist and we use current time to run the event.
		if ( ! $timestamp ) {
			wp_schedule_event( time(), 'breeze_varnish_time', 'breeze_purge_cache' );
		}
	}

	/**
	 * Add custom cron schedule
	 */
	public function filter_cron_schedules( $schedules ) {
		if ( ! empty( $this->timettl ) && is_numeric( $this->timettl ) && (int) $this->timettl > 0 ) {
			$interval = $this->timettl * 60;
		} else {
			$interval = '86400'; // One day
		}

		$schedules['breeze_varnish_time'] = array(
			'interval' => apply_filters( 'breeze_varnish_purge_interval', $interval ),
			'display'  => esc_html__( 'Cloudways Varnish Purge Interval', 'breeze' ),
		);

		return $schedules;
	}

	//execute purge varnish after time life
	public function schedule_varnish() {
		// Purge varnish cache
		if ( $this->varnishcache ) {
			do_action( 'breeze_clear_varnish' );
		}

		// Purge normal cache
		if ( $this->normalcache ) {
			Breeze_PurgeCache::breeze_cache_flush( true, true, true );
			Breeze_MinificationCache::clear_minification();
		}

	}

	public static function factory() {
		static $instance;
		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}

if ( ! class_exists( 'Breeze_Options_Reader' ) ) {
	require_once( BREEZE_PLUGIN_DIR . 'inc/class-breeze-options-reader.php' );
}


//Enabled auto purge the varnish caching by time life
$params = array(
	'breeze-active'        => (int) Breeze_Options_Reader::get_option_value( 'breeze-active' ),
	'breeze-b-ttl'         => Breeze_Options_Reader::get_option_value( 'breeze-b-ttl' ),
	'breeze-varnish-purge' => (int) Breeze_Options_Reader::get_option_value( 'auto-purge-varnish' ),
);

if ( $params['breeze-active'] || $params['breeze-varnish-purge'] ) {
	$purgeTime = new Breeze_PurgeCacheTime( $params );
}
