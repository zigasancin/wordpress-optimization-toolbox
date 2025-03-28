<?php

namespace Smush\Core\Next_Gen;

interface Next_Gen_Configuration_Interface {
	/**
	 * @return string
	 */
	public function get_format_name();

	/**
	 * @return string
	 */
	public function get_format_key();

	/**
	 * @return bool
	 */
	public function is_activated();

	/**
	 * @return bool
	 */
	public function is_fallback_activated();

	/**
	 * @return bool
	 */
	public function is_configured();

	/**
	 * @return bool
	 */
	public function direct_conversion_enabled();

	/**
	 * @return bool
	 */
	public function is_server_configured();

	/**
	 * @return bool
	 */
	public function support_server_configuration();

	/**
	 * @return bool
	 */
	public function should_show_wizard();

	public function toggle_module( $enable_module );

	public function set_next_gen_method( $next_gen_method );

	public function set_next_gen_fallback( $fallback_activated );

	public function delete_all_next_gen_files();
}
