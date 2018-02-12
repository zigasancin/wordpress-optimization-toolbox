<?php
/**
 * @package WP Smush
 * @subpackage S3
 * @version 2.7
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2017, Incsub (http://incsub.com)
 */
if ( ! class_exists( 'WpSmushS3' ) ) {

	class WpSmushS3 {

		private $setup_notice = '';
		private $message_type = 'error';

		function __construct() {
			$this->init();

			//Hook at the end of setting row to output a error div
			add_action( 'smush_setting_column_right_end', array( $this, 's3_setup_message' ) );

		}

		function init() {

			global $WpSmush;

			//Filters the setting variable to add S3 setting title and description
			add_filter( 'wp_smush_settings', array( $this, 'register' ), 6 );

			//Filters the setting variable to add S3 setting in premium features
			add_filter( 'wp_smush_pro_settings', array( $this, 'add_setting' ), 6 );

			//return if not a pro user
			if ( ! $WpSmush->validate_install() ) {
				return;
			}

			//Check if the file exists for the given path and download
			add_action( 'smush_file_exists', array( $this, 'maybe_download_file' ), 10, 3 );

			//Check if the backup file exists
			add_filter( 'smush_backup_exists', array( $this, 'backup_exists_on_s3' ), 10, 3 );


		}

		/**
		 * Filters the setting variable to add S3 setting title and description
		 *
		 * @param $settings
		 *
		 * @return mixed
		 */
		function register( $settings ) {
			$plugin_url     = esc_url( "https://wordpress.org/plugins/amazon-s3-and-cloudfront/" );
			$settings['s3'] = array(
				'label'       => esc_html__( 'Enable Amazon S3 support', 'wp-smushit' ),
				'short_label' => esc_html__( 'Amazon S3', 'wp-smushit' ),
				'desc'        => sprintf( esc_html__( "Storing your image on S3 buckets using %sWP Offload S3%s? Smush can detect and smush those assets for you, including when you're removing files from your host server.", 'wp-smushit' ), "<a href='" . $plugin_url . "' target = '_blank'>", "</a>", "<b>", "</b>" )
			);

			return $settings;
		}

		/**
		 * Append S3 in pro feature list
		 *
		 * @param $pro_settings
		 *
		 * @return array
		 */
		function add_setting( $pro_settings ) {

			if ( ! isset( $pro_settings['s3'] ) ) {
				$pro_settings[] = 's3';
			}

			return $pro_settings;
		}

		/**
		 * Prints the message for S3 setup
		 *
		 * @param $setting_key
		 *
		 * @return null
		 */
		function s3_setup_message( $setting_key ) {

			//Return if not S3
			if( 's3' != $setting_key ) {
				return;
			}

			global $as3cf, $WpSmush, $wpsmush_settings;
			$show_error = false;

			//If S3 integration is not enabled, return
			$setting_val = $WpSmush->validate_install() ? $wpsmush_settings->settings['s3'] : 0;

			if ( ! $setting_val ) {
				return;
			}

			//Check if plugin is setup or not
			//In case for some reason, we couldn't find the function
			if ( ! is_object( $as3cf ) || ! method_exists( $as3cf, 'is_plugin_setup' ) ) {
				$show_error         = true;
				$support_url        = esc_url( "https://premium.wpmudev.org/contact" );
				$this->setup_notice = sprintf( esc_html__( "We are having trouble interacting with WP Offload S3, make sure the plugin is activated. Or you can %sreport a bug%s.", "wp-smushit" ), '<a href="' . $support_url . '" target="_blank">', '</a>' );
			}

			//Plugin is not setup, or some information is missing
			if ( ! $as3cf->is_plugin_setup() ) {
				$show_error         = true;
				$configure_url      = $as3cf->get_plugin_page_url();
				$this->setup_notice = sprintf( esc_html__( "It seems you haven't finished setting up WP Offload S3 yet. %sConfigure%s it now to enable Amazon S3 support.", "wp-smushit" ), "<a href='" . $configure_url . "' target='_blank'>", "</a>" );
			} else {

				$this->message_type = 'notice';
				$this->setup_notice = esc_html__( "Amazon S3 support is active.", "wp-smushit" );

			}

			//Return Early if we don't need to do anything
			if ( empty( $this->setup_notice ) ) {
				return;
			}

			$class      = 'error' == $this->message_type ? ' smush-s3-setup-error' : ' smush-s3-setup-message';
			$icon_class = 'error' == $this->message_type ? ' icon-fi-warning-alert' : ' icon-fi-check-tick';
			echo "<div class='wp-smush-notice" . $class . "'><i class='" . $icon_class . "'></i><p>$this->setup_notice</p></div>";
		}

		/**
		 * Error message to show when S3 support is required.
		 *
		 * Show a error message to admins, if they need to enable S3 support. If "remove files from
		 * server" option is enabled in WP Offload S3 plugin, we need WP Smush Pro to enable S3 support.
		 *
		 * @return mixed
		 */
		function s3_support_required_notice() {

			global $wpsmushit_admin, $wpsmush_settings;

			// Do not display it for other users.
			// Do not display on network screens, if networkwide option is disabled.
			if ( ! current_user_can( 'manage_options' ) || ( is_network_admin() && ! $wpsmush_settings->settings['networkwide'] ) ) {
				return true;
			}

			// Do not display the notice on Bulk Smush Screen.
			global $current_screen;
			if ( ! empty( $current_screen->base ) && 'media_page_wp-smush-bulk' != $current_screen->base && 'gallery_page_wp-smush-nextgen-bulk' != $current_screen->base && 'settings_page_wp-smush-network' != $current_screen->base ) {
				return true;
			}

			// If already dismissed, do not show.
			if ( 1 == get_site_option( 'wp-smush-hide_s3support_alert' ) ) {
				return true;
			}

			// Return early, if support is not required.
			if ( ! $this->s3_support_required() ) {
				return true;
			}

			wp_enqueue_script( 'wp-smushit-notice-js' );
			// Settings link.
			$settings_link = is_multisite() && $wpsmush_settings->settings['networkwide'] ? network_admin_url( 'settings.php?page=wp-smush' ) : admin_url( 'upload.php?page=wp-smush-bulk' );

			if ( $wpsmushit_admin->validate_install() ) {
				// If premium user, but S3 support is not enabled.
				$message = sprintf( __( "We can see you have WP Offload S3 installed with the <strong>Remove Files From Server</strong> option activated. If you want to optimize your S3 images you'll need to enable the <a href='%s'><strong>Amazon S3 Support</strong></a> feature in Smush's settings.", 'wp-smushit' ), $settings_link );
			} else {
				// If not a premium user.
				$message = sprintf( __( "We can see you have WP Offload S3 installed with the <strong>Remove Files From Server</strong> option activated. If you want to optimize your S3 images you'll need to <a href='%s'><strong>upgrade to Smush Pro</strong></a>", 'wp-smushit' ), esc_url( 'https://premium.wpmudev.org/project/wp-smush-pro' ) );
			}

			echo '<div class="wp-smush-notice wp-smush-s3support-alert notice"><span class="notice-message">' . $message . '</span><i class="icon-fi-close"></i></div>';
		}

		/**
		 * Check if S3 support is required for Smush.
		 *
		 * @return bool
		 */
		function s3_support_required() {

			global $wpsmush_settings, $wpsmushit_admin, $as3cf;

			// Check if S3 offload plugin is active and delete file from server option is enabled.
			if ( ! is_object( $as3cf ) || ! method_exists( $as3cf, 'get_setting' ) || ! $as3cf->get_setting( 'remove-local-file' ) ) {
				return false;
			}

			// If not Pro user or S3 support is disabled.
			return ( ! $wpsmushit_admin->validate_install() || ! $wpsmush_settings->settings['s3'] );
		}

		/**
		 * Checks if the given attachment is on S3 or not, Returns S3 URL or WP Error
		 *
		 * @param $attachment_id
		 *
		 * @return bool|false|string
		 *
		 */
		function is_image_on_s3( $attachment_id = '' ) {
			global $as3cf;
			if ( empty( $attachment_id ) ) {
				return false;
			}

			//If we only have the attachment id
			$full_url = $as3cf->is_attachment_served_by_s3( $attachment_id );
			//If the filepath contains S3, get the s3 URL for the file
			if ( ! empty( $full_url ) ) {
				$full_url = $as3cf->get_attachment_url( $attachment_id );
			} else {
				$full_url = false;
			}

			return $full_url;

		}

		/**
		 * Download a specified file to local server with respect to provided attachment id
		 *  and/or Attachment path
		 *
		 * @param $attachment_id
		 *
		 * @param array $size_details
		 *
		 * @param string $uf_file_path
		 *
		 * @return string|bool Returns file path or false
		 *
		 */
		function download_file( $attachment_id, $size_details = array(), $uf_file_path = '' ) {
			global $WpSmush, $wpsmush_settings;
			if ( empty( $attachment_id ) || ! $wpsmush_settings->settings['s3'] || ! $WpSmush->validate_install() ) {
				return false;
			}

			global $as3cf;
			$renamed = $s3_object = $s3_url = $file = false;

			//If file path wasn't specified in argument
			$uf_file_path = empty( $uf_file_path ) ? get_attached_file( $attachment_id, true ) : $uf_file_path;

			//If we have plugin method available, us that otherwise check it ourselves
			if ( method_exists( $as3cf, 'is_attachment_served_by_s3' ) ) {
				$s3_object        = $as3cf->is_attachment_served_by_s3( $attachment_id );
				$size_prefix      = dirname( $s3_object['key'] );
				$size_file_prefix = ( '.' === $size_prefix ) ? '' : $size_prefix . '/';
				if ( ! empty( $size_details ) && is_array( $size_details ) ) {
					$s3_object['key'] = path_join( $size_file_prefix, $size_details['file'] );
				} elseif ( ! empty( $uf_file_path ) ) {
					//Get the File path using basename for given attachment path
					$s3_object['key'] = path_join( $size_file_prefix, wp_basename( $uf_file_path ) );
				}

				//Try to download the attachment
				if ( $s3_object && is_object( $as3cf->plugin_compat ) && method_exists( $as3cf->plugin_compat, 'copy_s3_file_to_server' ) ) {
					//Download file
					$file = $as3cf->plugin_compat->copy_s3_file_to_server( $s3_object, $uf_file_path );
				}

				if ( $file ) {
					return $file;
				}
			}

			//If we don't have the file, Try it the basic way
			if ( ! $file ) {
				$s3_url = $this->is_image_on_s3( $attachment_id );

				//If we couldn't get the image URL, return false
				if ( is_wp_error( $s3_url ) || empty( $s3_url ) || ! $s3_url ) {
					return false;
				}

				if ( ! empty( $size_details ) ) {
					//If size details are available, Update the URL to get the image for the specified size
					$s3_url = str_replace( wp_basename( $s3_url ), $size_details['file'], $s3_url );
				} elseif ( ! empty( $uf_file_path ) ) {
					//Get the File path using basename for given attachment path
					$s3_url = str_replace( wp_basename( $s3_url ), wp_basename( $uf_file_path ), $s3_url );
				}

				//Download the file
				$temp_file = download_url( $s3_url );
				if ( ! is_wp_error( $temp_file ) ) {
					$renamed = @copy( $temp_file, $uf_file_path );
					unlink( $temp_file );
				}

				//If we were able to successfully rename the file, return file path
				if ( $renamed ) {

					return $uf_file_path;
				}
			}

			return false;
		}

		/**
		 * Check if file exists for the given path
		 *
		 * @param string $attachment_id
		 * @param string $file_path
		 *
		 * @return bool
		 */
		function does_image_exists( $attachment_id = '', $file_path = '' ) {
			global $as3cf;
			if ( empty( $attachment_id ) || empty( $file_path ) ) {
				return false;
			}
			//Return if method doesn't exists
			if ( ! method_exists( $as3cf, 'is_attachment_served_by_s3' ) ) {
				error_log( "Couldn't find method is_attachment_served_by_s3." );

				return false;
			}
			//Get s3 object for the file
			$s3_object = $as3cf->is_attachment_served_by_s3( $attachment_id );

			$size_prefix      = dirname( $s3_object['key'] );
			$size_file_prefix = ( '.' === $size_prefix ) ? '' : $size_prefix . '/';

			//Get the File path using basename for given attachment path
			$s3_object['key'] = path_join( $size_file_prefix, wp_basename( $file_path ) );

			//Get bucket details
			$bucket = $as3cf->get_setting( 'bucket' );
			$region = $as3cf->get_setting( 'region' );

			if ( is_wp_error( $region ) ) {
				return false;
			}

			$s3client = $as3cf->get_s3client( $region );

			$file_exists = $s3client->doesObjectExist( $bucket, $s3_object['key'] );

			return $file_exists;
		}

		/**
		 * Check if the file is served by S3 and download the file for given path
		 *
		 * @param string $file_path Full file path
		 * @param string $attachment_id
		 * @param array $size_details Array of width and height for the image
		 *
		 * @return bool|string False/ File Path
		 */
		function maybe_download_file( $file_path = '', $attachment_id = '', $size_details = array() ) {
			if ( empty( $file_path ) || empty( $attachment_id ) ) {
				return false;
			}
			//Download if file not exists and served by S3
			if ( ! file_exists( $file_path ) && $this->is_image_on_s3( $attachment_id ) ) {
				return $this->download_file( $attachment_id, $size_details, $file_path );
			}

			return false;
		}

		/**
		 * Checks if we've backup on S3 for the given attachment id and backup path
		 *
		 * @param string $attachment_id
		 * @param string $backup_path
		 *
		 * @return bool
		 */
		function backup_exists_on_s3( $exists, $attachment_id = '', $backup_path = '' ) {
			//If the file is on S3, Check if backup image object exists
			if ( $this->is_image_on_s3( $attachment_id ) ) {
				return $this->does_image_exists( $attachment_id, $backup_path );
			}

			return $exists;
		}
	}

	global $wpsmush_s3;
	$wpsmush_s3 = new WpSmushS3();

}

if ( class_exists( 'AS3CF_Plugin_Compatibility' ) && ! class_exists( 'wp_smush_s3_compat' ) ) {
	class wp_smush_s3_compat extends AS3CF_Plugin_Compatibility {

		function __construct() {
			$this->init();
		}

		function init() {
			//Plugin Compatibility with Amazon S3
			add_filter( 'as3cf_get_attached_file', array( $this, 'smush_download_file' ), 11, 4 );
		}

		/**
		 * Download the attached file from S3 to local server
		 *
		 * @param $url
		 * @param $file
		 * @param $attachment_id
		 * @param $s3_object
		 */
		function smush_download_file( $url, $file, $attachment_id, $s3_object ) {

			global $as3cf, $wpsmush_settings, $WpSmush;

			//Return if integration is disabled, or not a pro user
			if ( ! $wpsmush_settings->settings['s3'] || ! $WpSmush->validate_install() ) {
				return $url;
			}

			//If we already have the local file at specified path
			if ( file_exists( $file ) ) {
				return $url;
			}

			//Download image for Manual and Bulk Smush
			$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';
			if ( empty( $action ) || ! in_array( $action, array( 'wp_smushit_manual', 'wp_smushit_bulk' ) ) ) {
				return $url;
			}

			//If the plugin compat object is not available, or the method has been updated
			if ( ! is_object( $as3cf->plugin_compat ) || ! method_exists( $as3cf->plugin_compat, 'copy_image_to_server_on_action' ) ) {
				return $url;
			}

			$as3cf->plugin_compat->copy_image_to_server_on_action( $action, true, $url, $file, $s3_object );
		}
	}

	global $wpsmush_s3_compat;
	$wpsmush_s3_compat = new wp_smush_s3_compat();
}