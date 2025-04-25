<?php

class Breeze_File_Permissions {
	/**
	 * @var null Hold the class instance.
	 */
	private static $instance = null;

	/**
	 * @var array All the errors found.
	 */
	private static $errors = array();

	private static $is_cache = null;

	function __construct() {
		// When Cache System is set to ON.
		add_action( 'admin_notices', array( &$this, 'display_the_errors' ) );
		add_action( 'network_admin_notices', array( &$this, 'display_the_errors' ) );

		// Whe the Cache System is set to OFF.
		add_action( 'admin_notices', array( &$this, 'display_cache_system_notice' ) );
		add_action( 'network_admin_notices', array( &$this, 'display_cache_system_notice' ) );

		add_action( 'wp_ajax_breeze_file_permission_check', array( &$this, 'breeze_check_the_files_permission' ) );

		// Add an AJAX action with nonce verification to handle dismissing the notice
		add_action( 'wp_ajax_breeze_dismiss_cache_notice', array( $this, 'breeze_dismiss_cache_notice' ) );
	}

	/**
	 * Determines the cache activation status for the Breeze plugin.
	 *
	 * @return bool The cache activation status, true/false.
	 */
	private function is_cache() {
		if ( is_bool( self::$is_cache ) ) {
			return self::$is_cache;
		} else {
			self::$is_cache = filter_var( Breeze_Options_Reader::get_option_value( 'breeze-active' ), FILTER_VALIDATE_BOOLEAN );
			return self::$is_cache;
		}
	}

	/**
	 * Checks file and folder permissions and handles associated errors.
	 *
	 * This method verifies the permissions of specific files and folders required
	 * by the Breeze plugin. If there is a cache issue or any permission errors,
	 * it constructs a message detailing the issues and terminates execution using
	 * wp_die() to display the message. If no issues are found, it indicates so.
	 *
	 * @return void This method does not return a value but terminates script execution
	 *              and outputs a message regarding file permission issues or a no-issue status.
	 */
	public function breeze_check_the_files_permission() {
		$this->check_specific_files_folders();
		$message_display = '';
		if ( false === self::is_cache() ) {
			wp_die( $message_display );
		}

		if ( ! empty( self::$errors ) ) {
			$message_display .= '<div class="notice notice-error is-dismissible breeze-per" style="margin-left:2px">';
			$message_display .= '<p><strong>' . __( 'Breeze plugin functionality may be affected due to missing config file(s) or folder(s), or issues with file permissions preventing proper operation.', 'breeze' ) . '</strong></p>';
			foreach ( self::$errors as $message ) {
				$message_display .= '<p>' . $message . '</p>';
			}
			$message_display .= '<p>';
			$message_display .= sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( 'https://support.cloudways.com/en/articles/5126387-how-can-i-reset-file-and-folder-permissions' ),
				esc_html__( 'For reference please click on the KB', 'breeze' )
			);
			$message_display .= '</p>';
			$message_display .= '</div>';
		}

		if ( empty( $message_display ) ) {
			$message_display = 'no-issue';
		}

		wp_die( $message_display );
	}

	/**
	 * Retrieves the singleton instance of the Breeze_File_Permissions class.
	 *
	 * This method ensures that only one instance of the Breeze_File_Permissions
	 * class is created. If the instance does not exist, it initializes the instance
	 * and returns it. Subsequent calls will return the existing instance.
	 *
	 * @return Breeze_File_Permissions|null The singleton instance of Breeze_File_Permissions,
	 *                                      or null if the instance could not be created.
	 */
	public static function get_instance(): ?Breeze_File_Permissions {
		if ( null === self::$instance ) {
			self::$instance = new Breeze_File_Permissions();
		}

		return self::$instance;
	}

	/**
	 * Appends a permission error message to the list of errors.
	 *
	 * @param string $message The error message to be appended. Defaults to an empty string.
	 *
	 * @return void
	 */
	public static function append_permission_error( string $message = '' ) {
		if ( ! empty( $message ) ) {
			self::$errors[] = $message;
		}

	}

	public function check_specific_files_folders() {

		$cache_specific_folders = breeze_all_user_folders();
		$assets_folders         = array(
			'css',
			'js',
			'',
		);
		$wp_content_dir         = trailingslashit( WP_CONTENT_DIR );

		/**
		 * Check global cache folders.
		 */

		// Advanced cache file.
		$file = $wp_content_dir . 'advanced-cache.php';

		if ( ! file_exists( $file ) ) {
			self::append_permission_error( $file . __( ' file does not exist. Save Breeze settings to create the file.', 'breeze' ) );
		} elseif ( ! is_writable( $file ) ) {
			self::append_permission_error( $file . __( ' file is not writable.', 'breeze' ) );
		}

		$folder = $wp_content_dir . 'breeze-config/';

		if ( is_dir( $folder ) && ! is_writable( $folder ) ) {
			self::append_permission_error( $folder . __( '  folder is not writable.', 'breeze' ) );
		}

		$folder = $wp_content_dir . 'cache/';
		if ( is_dir( $folder ) && ! is_writable( $folder ) ) {
			self::append_permission_error( $folder . __( ' folder is not writable.', 'breeze' ) );
		}

		$folder = $wp_content_dir . 'cache/breeze/';
		if ( is_dir( $folder ) && ! is_writable( $folder ) ) {
			self::append_permission_error( $folder . __( ' folder is not writable.', 'breeze' ) );
		}

		/**
		 * Checking multisite specific folders.
		 */
		if ( is_multisite() ) {
			set_as_network_screen();

			if ( is_network_admin() ) {

				$file = $wp_content_dir . 'breeze-config/breeze-config.php';

				if ( ! file_exists( $file ) ) {
					self::append_permission_error( $file . __( ' file does not exist. Save Breeze settings to create the file.', 'breeze' ) );
				} elseif ( ! is_writable( $file ) ) {
					self::append_permission_error( $file . __( ' file is not writable.', 'breeze' ) );
				}

				$folder_min = $wp_content_dir . 'cache/breeze-minification/';
				if ( is_dir( $folder_min ) && ! is_writable( $folder_min ) ) {
					self::append_permission_error( $folder_min . __( ' folder is not writable.', 'breeze' ) );
				}

				$blogs = get_sites();
				if ( ! empty( $blogs ) ) {
					foreach ( $blogs as $blog_data ) {
						$blog_id = $blog_data->blog_id;
						$folder  = $wp_content_dir . 'cache/breeze/' . $blog_id . '/';
						if ( is_dir( $folder ) && ! is_writable( $folder ) ) {
							self::append_permission_error( $folder . __( ' folder is not writable.', 'breeze' ) );
						}

						$folder_min = $wp_content_dir . 'cache/breeze-minification/' . $blog_id . '/';

						if ( ! empty( $cache_specific_folders ) && is_array( $cache_specific_folders ) ) {
							foreach ( $cache_specific_folders as $item_folder ) {
								foreach ( $assets_folders as $asset_folder ) {
									$check_folder = trailingslashit( trailingslashit( $folder_min . $item_folder ) . $asset_folder );

									if ( is_dir( $check_folder ) && ! is_writable( $check_folder ) ) {
										self::append_permission_error( $check_folder . __( ' folder is not writable.', 'breeze' ) );
									}
								}
							}
						}// endif
					}
				}
			} else {

				$the_blog_id = get_current_blog_id();

				$inherit_option = get_blog_option( $the_blog_id, 'breeze_inherit_settings' );
				$inherit_option = filter_var( $inherit_option, FILTER_VALIDATE_BOOLEAN );

				$folder_min = $wp_content_dir . 'cache/breeze-minification/';
				if ( is_dir( $folder_min ) && ! is_writable( $folder_min ) ) {
					self::append_permission_error( $folder_min . __( ' folder is not writable.', 'breeze' ) );
				}

				$file = $wp_content_dir . 'breeze-config/breeze-config-' . $the_blog_id . '.php';
				if ( false === $inherit_option && ! file_exists( $file ) ) {
					self::append_permission_error( $file . __( ' file does not exist. Save Breeze settings to create the file.', 'breeze' ) );
				} elseif ( false === $inherit_option && file_exists( $file ) && ! is_writable( $file ) ) {
					self::append_permission_error( $file . __( ' file is not writable.', 'breeze' ) );
				}

				$folder = $wp_content_dir . 'cache/breeze/' . $the_blog_id . '/';
				if ( is_dir( $folder ) && ! is_writable( $folder ) ) {
					self::append_permission_error( $folder . __( ' folder is not writable.', 'breeze' ) );
				}

				$folder_min = $wp_content_dir . 'cache/breeze-minification/' . $the_blog_id . '/';

				if ( ! empty( $cache_specific_folders ) && is_array( $cache_specific_folders ) ) {
					foreach ( $cache_specific_folders as $item_folder ) {
						foreach ( $assets_folders as $asset_folder ) {
							$check_folder = trailingslashit( trailingslashit( $folder_min . $item_folder ) . $asset_folder );

							if ( is_dir( $check_folder ) && ! is_writable( $check_folder ) ) {
								self::append_permission_error( $check_folder . __( ' folder is not writable.', 'breeze' ) );
							}
						}
					}
				}// endif
			}
		} else {

			$file = $wp_content_dir . 'breeze-config/breeze-config.php';

			if ( ! file_exists( $file ) ) {
				self::append_permission_error( $file . __( ' file does not exist. Save Breeze settings to create the file.', 'breeze' ) );
			} elseif ( ! is_writable( $file ) ) {
				self::append_permission_error( $file . __( ' file is not writable.', 'breeze' ) );
			}

			/**
			 * Checking single site specific folders.
			 */
			$folder_min = $wp_content_dir . 'cache/breeze-minification/';

			if ( ! empty( $cache_specific_folders ) && is_array( $cache_specific_folders ) ) {
				foreach ( $cache_specific_folders as $item_folder ) {

					foreach ( $assets_folders as $asset_folder ) {
						$check_folder = trailingslashit( trailingslashit( $folder_min . $item_folder ) . $asset_folder );

						if ( is_dir( $check_folder ) && ! is_writable( $check_folder ) ) {
							self::append_permission_error( $check_folder . __( ' folder is not writable.', 'breeze' ) );
						}
					}
				}
			}// endif
		}

	}

	/**
	 * Displays error messages related to file permission issues.
	 *
	 * @return void
	 */
	public function display_the_errors() {
		if ( false === self::is_cache() ) {
			echo '';
		} else {
			$this->check_specific_files_folders();
			if ( ! empty( self::$errors ) ) {
				echo '<div class="notice notice-error is-dismissible breeze-per" style="margin-left:2px">';
				echo '<p><strong>' . __( 'Breeze plugin functionality may be affected due to missing config file(s) or folder(s), or issues with file permissions preventing proper operation.', 'breeze' ) . '</strong></p>';
				foreach ( self::$errors as $message ) {
					echo '<p>' . $message . '</p>';
				}
				echo '<p>';
				printf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( 'https://support.cloudways.com/en/articles/5126387-how-can-i-reset-file-and-folder-permissions' ),
					esc_html__( 'For reference please click on the KB', 'breeze' )
				);
				echo '</p>';
				echo '</div>';
			}
		}

	}

	/**
	 * Displays a system notice for the Breeze Cache System if caching is disabled.
	 *
	 * This function checks whether the cache notice has been dismissed or if the cache
	 * is enabled. If neither condition is met, it outputs a warning notice indicating
	 * that the cache system is disabled, which might negatively affect website performance,
	 * providing a link to enable it in the settings. It includes a nonce for verifying
	 * the AJAX request when the notice is dismissed.
	 *
	 * @return void
	 */
	public function display_cache_system_notice() {

		// Check if the notice has been dismissed
		if ( get_transient( 'breeze_cache_notice_dismissed' ) ) {
			return;
		}

		if ( true === self::is_cache() ) {
			return;
		}

		$message = __( 'The Breeze Cache System is disabled in the settings</strong>, the website performance might drop. You can enable the cache system in the Breeze settings ', 'breeze' );

		$is_network   = is_multisite() && is_network_admin();
		$settings_url = $is_network ? network_admin_url( 'settings.php?page=breeze' ) : admin_url( 'options-general.php?page=breeze' );

		$notice  = '<div class="notice notice-warning is-dismissible breeze-cs" style="margin-left:2px">';
		$notice .= '<p><strong>' . $message . '<a href="%s" id="breeze-cache-on">%s</a>.</p>';
		$notice .= '</div>';

		printf(
			$notice,
			esc_url( $settings_url ),
			esc_html__( 'here', 'breeze' )
		);

		// Output the JavaScript to handle the dismissal of the notice with nonce verification
		wp_nonce_field( 'breeze_dismiss_cache_notice_nonce', 'breeze_dismiss_cache_notice_nonce' );
		?>
		<script>
		   jQuery( function ( $ ) {
			   $( document ).on( 'click', '.breeze-cs .notice-dismiss', function () {
				   var data = {
					   action: 'breeze_dismiss_cache_notice',
					   nonce: $( '#breeze_dismiss_cache_notice_nonce' ).val()
				   };

				   $.post( ajaxurl, data, function ( response ) {
					   // Handle response if needed
				   } );
				   $( '.breeze-cs' ).remove();
			   } );
		   } );
		</script>
		<?php
	}

	/**
	 * Dismisses the cache notice by verifying the AJAX request and setting a transient.
	 *
	 * This function checks the AJAX referer for security purposes, sets a transient
	 * to remember the dismissal of the cache notice, and then terminates the request.
	 *
	 * @return void
	 */
	function breeze_dismiss_cache_notice() {
		check_ajax_referer( 'breeze_dismiss_cache_notice_nonce', 'nonce' );

		set_transient( 'breeze_cache_notice_dismissed', true, DAY_IN_SECONDS );

		wp_die();
	}
}

add_action(
	'admin_init',
	function () {
		new Breeze_File_Permissions();
	}
);
