<?php
/**
 * Directory Smush: WpSmushDir class
 *
 * @package WP_Smush
 * @subpackage Admin
 * @since 2.6
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */

if ( ! class_exists( 'WpSmushDir' ) ) {
	/**
	 * Class WpSmushDir
	 */
	class WpSmushDir {
		/**
		 * Contains a list of optimised images.
		 *
		 * @var $optimised_images
		 */
		public $optimised_images;

		/**
		 * Total Stats for the image optimisation.
		 *
		 * @var $stats
		 */
		public $stats;

		/**
		 * WpSmushDir constructor.
		 */
		public function __construct() {
			if ( ! $this->should_continue() ) {
				// Remove directory smush from tabs if not required.
				add_filter( 'smush_setting_tabs', array( $this, 'remove_directory_tab' ) );

				return;
			}

			// Hook UI at the end of Settings UI.
			add_action( 'smush_directory_settings_ui', array( $this, 'ui' ), 11 );

			// Output Stats after Resize savings.
			add_action( 'stats_ui_after_resize_savings', array( $this, 'stats_ui' ), 10 );

			// Handle Ajax request 'smush_get_directory_list'.
			add_action( 'wp_ajax_smush_get_directory_list', array( $this, 'directory_list' ) );

			// Scan the given directory path for the list of images.
			add_action( 'wp_ajax_image_list', array( $this, 'image_list' ) );

			// Handle Ajax Request to optimise images.
			add_action( 'wp_ajax_optimise', array( $this, 'optimise' ) );

			// Handle Exclude path request.
			add_action( 'wp_ajax_smush_exclude_path', array( $this, 'smush_exclude_path' ) );

			// Handle Ajax request: resume scan.
			add_action( 'wp_ajax_resume_scan', array( $this, 'resume_scan' ) );

			// Handle Ajax request for directory smush stats.
			add_action( 'wp_ajax_get_dir_smush_stats', array( $this, 'get_dir_smush_stats' ) );
		}

		/**
		 * Do not display Directory smush for Subsites
		 *
		 * @return bool True/False, whether to display the Directory smush or not
		 *
		 */
		function should_continue() {

			//Do not show directory smush, if not main site in a network
			if ( is_multisite() && ! is_main_site() ) {
				return false;
			}

			return true;
		}

		/**
		 * Set directory smush stats to stats box.
		 *
		 * @return void
		 */
		function stats_ui() {

			$dir_smush_stats = get_option( 'dir_smush_stats' );
			$human = 0;
			if ( ! empty( $dir_smush_stats ) && ! empty( $dir_smush_stats['dir_smush'] ) ) {
				$human = ! empty( $dir_smush_stats['dir_smush']['bytes'] ) && $dir_smush_stats['dir_smush']['bytes'] > 0 ? $dir_smush_stats['dir_smush']['bytes'] : 0;
			}
			?>
			<!-- Savings from Directory Smush -->
			<li class="smush-dir-savings">
				<span class="sui-list-label"><?php _e( 'Directory Smush Savings', 'wp-smushit' ); ?>
					<?php if ( $human <= 0 ) { ?>
						<p class="wp-smush-stats-label-message">
							<?php esc_html_e( 'Smush images that aren\'t located in your uploads folder.', 'wp-smushit' ); ?>
							<a href="<?php echo admin_url( 'admin.php?page=smush&tab=directory' ); ?>" class="wp-smush-dir-link" title="<?php esc_html_e( 'Select a directory you\'d like to Smush.', 'wp-smushit' ); ?>"><?php esc_html_e( 'Choose directory', 'wp-smushit' ); ?></a>
						</p>
					<?php } ?>
				</span>
				<span class="wp-smush-stats sui-list-detail">
					<i class="sui-icon-loader sui-loading" aria-hidden="true" title="<?php esc_html_e( 'Updating Stats', 'wp-smushit' ); ?>"></i>
					<span class="wp-smush-stats-human"></span>
					<span class="wp-smush-stats-sep sui-hidden">/</span>
                    <span class="wp-smush-stats-percent"></span>
				</span>
			</li>
			<?php
		}

		/**
		 *  Create the Smush image table to store the paths of scanned images, and stats
		 */
		function create_table() {
			global $wpdb;

			//Run the query only on directory smush page
			if ( ! isset( $_GET['page'] ) || 'smush' != $_GET['page'] ) {
				return null;
			}

			$charset_collate = $wpdb->get_charset_collate();

			/**
			 * Table: wp_smush_dir_images
			 * Columns:
			 * id -> Auto Increment ID
			 * path -> Absolute path to the image file
			 * resize -> Whether the image was resized or not
			 * lossy -> Whether the image was super-smushed/lossy or not
			 * image_size -> Current image size post optimisation
			 * orig_size -> Original image size before optimisation
			 * file_time -> Unix time for the file creation, to match it against the current creation time,
			 *                  in order to confirm if it is optimised or not
			 * last_scan -> Timestamp, Get images form last scan by latest timestamp
			 *                  are from latest scan only and not the whole list from db
			 * meta -> For any future use
			 *
			 */
			$sql = "CREATE TABLE {$wpdb->prefix}smush_dir_images (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				path text NOT NULL,
				path_hash CHAR(32),
				resize varchar(55),
				lossy varchar(55),
				error varchar(55) DEFAULT NULL,
				image_size int(10) unsigned,
				orig_size int(10) unsigned,
				file_time int(10) unsigned,
				last_scan timestamp DEFAULT '0000-00-00 00:00:00',
				meta text,
				UNIQUE KEY id (id),
				UNIQUE KEY path_hash (path_hash),
				KEY image_size (image_size)
			) $charset_collate;";

			// include the upgrade library to initialize a table
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		/**
		 * Get the image ids and path for last scanned images
		 *
		 * @return array Array of last scanned images containing image id and path
		 */
		function get_scanned_images() {
			global $wpdb;

			$query = "SELECT id, path, orig_size FROM {$wpdb->prefix}smush_dir_images WHERE last_scan = (SELECT MAX(last_scan) FROM {$wpdb->prefix}smush_dir_images )  GROUP BY id ORDER BY id";

			$results = $wpdb->get_results( $query, ARRAY_A );

			//Return image ids
			if ( is_wp_error( $results ) ) {
				error_log( sprintf( "WP Smush Query Error in %s at %s: %s", __FILE__, __LINE__, $results->get_error_message() ) );
				$results = array();
			}

			return $results;
		}

		/**
		 * Check if there is any unsmushed image from last scan
		 *
		 * @return bool True/False
		 *
		 */
		function get_unsmushed_image() {
			global $wpdb, $wp_smush;

			// If super-smush enabled, add lossy check.
			$lossy_condition = $wp_smush->lossy_enabled ? '(image_size IS NULL OR lossy <> 1)' : 'image_size IS NULL';

			$query   = $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}smush_dir_images WHERE $lossy_condition && last_scan = (SELECT MAX(last_scan) FROM {$wpdb->prefix}smush_dir_images t2 )  GROUP BY id ORDER BY id LIMIT %d", 1 );
			$results = $wpdb->get_col( $query );

			//If The query went through
			if ( empty( $results ) ) {
				return false;
			} elseif ( is_wp_error( $results ) ) {
				error_log( sprintf( "WP Smush Query Error in %s at %s: %s", __FILE__, __LINE__, $results->get_error_message() ) );

				return false;
			}

			return true;
		}


		/**
		 * Prints a resume button if required
		 */
		function show_resume_button() {
			if ( ! $this->get_unsmushed_image() ) {
				return null;
			}
			//Print the button ?>
			<button type="button" class="sui-button wp-smush-resume tc"><?php esc_html_e( 'RESUME LAST SCAN', 'wp-smushit' ); ?></button>
			<span class="wp-smush-resume-loder sui-icon-loader sui-loading sui-hidden" aria-hidden="true"></span>
			<?php
		}

		/**
		 * Bulk Smush UI and progress bar.
		 */
		function ui() {
			global $wp_smush, $wpsmushit_admin, $wpsmush_bulkui;

			//Print Directory Smush UI, if not a network site
			if ( is_network_admin() ) {
				return;
			}

			//Reset the bulk limit
			if ( ! $wp_smush->validate_install() ) {
				//Reset Transient
				$wpsmushit_admin->check_bulk_limit( true, 'dir_sent_count' );
			}

			wp_nonce_field( 'smush_get_dir_list', 'list_nonce' );
			wp_nonce_field( 'smush_get_image_list', 'image_list_nonce' );

			$upgrade_url = add_query_arg(
				array(
					'utm_source'   => 'smush',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'smush_directorysmush_limit_notice'
				),
				$wpsmushit_admin->upgrade_url
			);

			$dir_button = '<button class="sui-button wp-smush-browse sui-hidden" data-a11y-dialog-show="wp-smush-list-dialog">' . esc_html__( 'ADD FOLDER', 'wp-smushit' ) . '</button>';

			echo '<div class="sui-box" id="wp-smush-dir-wrap-box">';

			// Container header.
			$wpsmush_bulkui->container_header( esc_html__( 'Directory Smush', 'wp-smushit' ), $dir_button ); ?>

			<div class="sui-box-body">
				<!-- Directory Path -->
				<input type="hidden" class="wp-smush-dir-path" value=""/>
				<div class="wp-smush-scan-result">
					<div class="content">
						<!-- Show a list of images, inside a fixed height div, with a scroll. As soon as the image is
						optimised show a tick mark, with savings below the image. Scroll the li each time for the
						current optimised image -->
						<span class="wp-smush-no-image tc">
							<img src="<?php echo WP_SMUSH_URL . 'assets/images/smush-no-media.png'; ?>" alt="<?php esc_html_e( 'Directory Smush - Choose Folder', 'wp-smushit' ); ?>">
				        </span>
						<p class="wp-smush-no-images-content tc roboto-regular">
							<?php esc_html_e( 'In addition to smushing your media uploads, you may want to also smush images living outside your uploads directory.', 'wp-smushit' ); ?><br>
							<?php esc_html_e( 'Add any folders you wish to smush and bulk smush away!', 'wp-smushit' ); ?>
						</p>
						<span class="wp-smush-upload-images sui-no-padding-bottom tc">
							<button type="button" class="sui-button sui-button-primary wp-smush-browse tc" data-a11y-dialog-show="wp-smush-list-dialog"><?php esc_html_e( 'CHOOSE FOLDER', 'wp-smushit' ); ?></button>
							<?php $this->show_resume_button(); ?>
						</span>
					</div>
					<table class="smush-dir-smush-done sui-table sui-hidden">
						<thead>
						<tr>
							<th><?php esc_html_e( 'Folder', 'wp-smushit' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<tr>
							<td class="smush-notice-content"><div class="sui-notice sui-notice-info smush-no-images sui-hidden"><p><?php esc_html_e( 'You haven’t added any folders to smush.', 'wp-smushit' ); ?></p></div></td>
						</tr>
						<tr>
							<td><button type="button" class="sui-button wp-smush-browse wp-smush-browse-top" data-a11y-dialog-show="wp-smush-list-dialog"><?php esc_html_e( 'ADD FOLDER', 'wp-smushit' ); ?></button></td>
						</tr>
						</tbody>
					</table>
					<!-- Notices -->
					<div class="sui-notice sui-notice-success wp-smush-dir-all-done sui-hidden">
						<p><?php esc_html_e( "All images for the selected directory are smushed and up to date. Awesome!", "wp-smushit" ); ?></p>
					</div>
					<div class="sui-notice sui-notice-warning wp-smush-dir-remaining sui-hidden">
						<p><?php printf( esc_html__( "%s/%s image(s) were successfully smushed, however %s image(s) could not be smushed due to an error.", "wp-smushit" ), '<span class="wp-smush-dir-smushed"></span>', '<span class="wp-smush-dir-total"></span>', '<span class="wp-smush-dir-remaining"></span>' ); ?></p>
					</div>
					<div class="sui-notice sui-notice-info wp-smush-dir-limit sui-hidden">
						<p><?php printf( esc_html__( " %sUpgrade to pro%s to bulk smush all your directory images with one click. Free users can smush 50 images with each click.", "wp-smushit" ), '<a href="' . esc_url( $upgrade_url ) . '" target="_blank" title="' . esc_html__( "Smush Pro", "wp-smushit" ) . '">', '</a>' ); ?></p>
					</div>
					<?php
					//Nonce Field
					wp_nonce_field( 'wp_smush_all', 'wp-smush-all' ); ?>
					<input type="hidden" name="wp-smush-continue-ajax" value=1>
				</div>
				<input type="hidden" name="wp-smush-base-path" value="<?php echo $this->get_root_path(); ?>">
			</div>
			<?php $this->directory_list_dialog(); ?>
			</div>
			<?php
		}

		/**
		 * Check if the image file is media library file
		 *
		 * @param $file_path
		 *
		 * @return bool
		 *
		 */
		function is_media_library_file( $file_path ) {
			$upload_dir  = wp_upload_dir();
			$upload_path = $upload_dir["path"];

			//Get the base path of file
			$base_dir = dirname( $file_path );
			if ( $base_dir == $upload_path ) {
				return true;
			}

			return false;
		}

		/**
		 * Return a directory/File list
		 *
		 * PHP Connector
		 */
		function directory_list() {
			//Check For Permission
			if ( ! current_user_can( 'manage_options' ) || ! is_user_logged_in() ) {
				wp_send_json_error( "Unauthorized" );
			}
			//Verify nonce
			check_ajax_referer( 'smush_get_dir_list', 'list_nonce' );

			//Get the Root path for a main site or subsite
			$root = realpath( $this->get_root_path() );

			$dir     = isset( $_GET['dir'] ) ? ltrim( $_GET['dir'], '/' ) : null;
			$postDir = strlen( $dir ) > 1 ? path_join( $root, $dir ) : $root . $dir;
			$postDir = realpath( rawurldecode( $postDir ) );

			//If the final path doesn't contains the root path, bail out.
			if ( ! $root || $postDir === false || strpos( $postDir, $root ) !== 0 ) {
				wp_send_json_error( "Unauthorized" );
			}

			$supported_image = array(
				'gif',
				'jpg',
				'jpeg',
				'png'
			);

			$list = '';

			if ( file_exists( $postDir ) ) {

				$files = scandir( $postDir );
				//Exclude hidden files
				if ( ! empty( $files ) ) {
					$files = preg_grep( '/^([^.])/', $files );
				}
				$returnDir = substr( $postDir, strlen( $root ) );

				natcasesort( $files );

				if ( count( $files ) > 2 ) {
					$list = "<ul class='jqueryFileTree' tabindex='0'>";
					foreach ( $files as $file ) {

						$htmlRel  = htmlentities( ltrim( path_join( $returnDir, $file ), '/' ) );
						$htmlName = htmlentities( $file );
						$ext      = preg_replace( '/^.*\./', '', $file );

						$file_path = path_join( $postDir, $file );
						if ( file_exists( $file_path ) && $file != '.' && $file != '..' ) {
							if ( is_dir( $file_path ) && ! $this->skip_dir( $file_path ) ) {
								//Skip Uploads folder - Media Files
								$list .= "<li class='directory collapsed'><a rel='" . $htmlRel . "/' tabindex='0'>" . $htmlName . "</a></li>";
							} else if ( in_array( $ext, $supported_image ) && ! $this->is_media_library_file( $file_path ) ) {
								$list .= "<li class='file ext_{$ext}'><a rel='" . $htmlRel . "' tabindex='0'>" . $htmlName . "</a></li>";
							}
						}
					}

					$list .= "</ul>";
				}
			}
			echo $list;
			die();

		}

		public function get_root_path() {
			if ( is_main_site() ) {

				return rtrim( get_home_path(), '/' );
			} else {
				$up = wp_upload_dir();

				return $up['basedir'];
			}
		}

		/**
		 * @param SplFileInfo $file
		 * @param mixed $key
		 * @param RecursiveCallbackFilterIterator $iterator
		 *
		 * @return bool True if you need to recurse or if the item is acceptable
		 */
		function exclude( $file, $key, $iterator ) {
			// Will exclude everything under these directories
			$exclude_dir = array( '.git', 'test' );

			//Exclude from the list, if one of the media upload folders
			if ( $this->skip_dir( $file->getPath() ) ) {
				return true;
			}

			//Exclude Directories like git, and test
			if ( $iterator->hasChildren() && ! in_array( $file->getFilename(), $exclude_dir ) ) {
				return true;
			}

			//Do not exclude, if image
			if ( $file->isFile() && $this->is_image_from_extension( $file->getPath() ) ) {
				return true;
			}

			return $file->isFile();
		}

		/**
		 * Get the image list in a specified directory path
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		function get_image_list( $path = '' ) {
			global $wpdb, $wpsmush_helper;

			//Return Error if not a valid directory path
			if ( ! is_dir( $path ) ) {
				wp_send_json_error( array( "message" => "Not a valid directory path" ) );
			}

			$base_dir = empty( $path ) ? ltrim( $_GET['path'], '/' ) : $path;
			$base_dir = realpath( rawurldecode( $base_dir ) );

			if ( ! $base_dir ) {
				wp_send_json_error( array( "message" => "Unauthorized" ) );
			}

			//Store the path in option
			update_option( 'wp-smush-dir_path', $base_dir, false );

			//Directory Iterator, Exclude . and ..
			$dirIterator = new RecursiveDirectoryIterator(
				$base_dir
			//PHP 5.2 compatibility
			//RecursiveDirectoryIterator::SKIP_DOTS
			);

			$filtered_dir = new WPSmushRecursiveFilterIterator( $dirIterator );

			//File Iterator
			$iterator = new RecursiveIteratorIterator( $filtered_dir,
				RecursiveIteratorIterator::CHILD_FIRST
			);

			//Iterate over the file List
			$files_arr = array();
			$images    = array();
			$count     = 0;
			$timestamp = gmdate( 'Y-m-d H:i:s' );
			$values    = array();
			//Temporary Increase the limit
			$wpsmush_helper->increase_memory_limit();
			foreach ( $iterator as $path ) {

				//Used in place of Skip Dots, For php 5.2 compatability
				if ( basename( $path ) == '..' || basename( $path ) == '.' ) {
					continue;
				}
				if ( $path->isFile() ) {
					$file_path = $path->getPathname();
					$file_name = $path->getFilename();

					if ( $this->is_image( $file_path ) && ! $this->is_media_library_file( $file_path ) && strpos( $path, '.bak' ) === false ) {

						/**  To generate Markup **/
						$dir_name = dirname( $file_path );

						//Initialize if dirname doesn't exists in array already
						if ( ! isset( $files_arr[ $dir_name ] ) ) {
							$files_arr[ $dir_name ] = array();
						}
						$files_arr[ $dir_name ][ $file_name ] = $file_path;
						/** End */

						//Get the file modification time
						$file_time = @filectime( $file_path );

						/** To be stored in DB, Part of code inspired from Ewwww Optimiser  */
						$image_size = $path->getSize();
						$images []  = $file_path;
						$images []  = md5($file_path );
						$images []  = $image_size;
						$images []  = $file_time;
						$images []  = $timestamp;
						$values[]   = '(%s, %s, %d, %d, %s)';
						$count ++;
					}
				}

				//Store the Images in db at an interval of 5k
				if ( $count >= 5000 ) {
					$count  = 0;
					$query  = $this->build_query( $values, $images );
					$images = $values = array();
					$wpdb->query( $query );
				}
			}

			//Update rest of the images
			if ( ! empty( $images ) && $count > 0 ) {
				$query = $this->build_query( $values, $images );
				$wpdb->query( $query );
			}

			//remove scanne dimages from cache
			wp_cache_delete( 'wp_smush_scanned_images' );

			//Get the image ids
			$images = $this->get_scanned_images();

			//Store scanned images in cache
			wp_cache_add( 'wp_smush_scanned_images', $images );

			return array( 'files_arr' => $files_arr, 'base_dir' => $base_dir, 'image_items' => $images );
		}

		/**
		 * Build and prepare query from the given values and image array
		 *
		 * @param $values
		 * @param $images
		 *
		 * @return bool|string|void
		 */
		function build_query( $values, $images ) {

			if ( empty( $images ) || empty( $values ) ) {
				return false;
			}

			global $wpdb;
			$values = implode( ',', $values );

			//Replace with image path and respective parameters
			$query = "INSERT INTO {$wpdb->prefix}smush_dir_images (path, path_hash, orig_size,file_time,last_scan) VALUES $values ON DUPLICATE KEY UPDATE image_size = IF( file_time < VALUES(file_time), NULL, image_size ), file_time = IF( file_time < VALUES(file_time), VALUES(file_time), file_time ), last_scan = VALUES( last_scan )";
			$query = $wpdb->prepare( $query, $images );

			return $query;

		}

		/**
		 * Sends a Ajax response if no images are found in selected directory
		 */
		function send_error() {
			$message = sprintf( "<div class='sui-notice sui-notice-info'><p>%s</p></div>", esc_html__( "We could not find any images in the selected directory.", "wp-smushit" ) );
			wp_send_json_error( array( 'message' => $message ) );
		}

		/**
		 * Handles Ajax request to obtain the Image list within a selected directory path
		 *
		 */
		function image_list() {

			//Check For Permission
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( "Unauthorized" );
			}

			//Verify nonce
			check_ajax_referer( 'smush_get_image_list', 'image_list_nonce' );

			//Check if directory path is set or not
			if ( empty( $_GET['smush_path'] ) ) {
				wp_send_json_error( "Empth Directory Path" );
			}

			//Get the File list
			$files = $this->get_image_list( $_GET['smush_path'] );

			//If files array is empty, send a message
			if ( empty( $files['files_arr'] ) ) {
				$this->send_error();
			}

			//Get the markup from the list
			$markup = $this->generate_markup( $files );

			//Send response
			wp_send_json_success( $markup );

		}

		/**
		 * Check whether the given path is a image or not
		 *
		 * Do not include backup files
		 *
		 * @param $path
		 *
		 * @return bool
		 *
		 */
		function is_image( $path ) {

			//Check if the path is valid
			if ( ! file_exists( $path ) || ! $this->is_image_from_extension( $path ) ) {
				return false;
			}

			$a = @getimagesize( $path );

			//If a is not set
			if ( ! $a || empty( $a ) ) {
				return false;
			}

			$image_type = $a[2];

			if ( in_array( $image_type, array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Obtain the path to the admin directory.
		 *
		 * @return string
		 *
		 * Thanks @andrezrv (Github)
		 *
		 */
		function get_admin_path() {
			// Replace the site base URL with the absolute path to its installation directory.
			$admin_path = rtrim( str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, get_admin_url() ), '/' );

			// Make it filterable, so other plugins can hook into it.
			$admin_path = apply_filters( 'wp_smush_get_admin_path', $admin_path );

			return $admin_path;
		}

		/**
		 * Check if the given file path is a supported image format
		 *
		 * @param $path File Path
		 *
		 * @return bool Whether a image or not
		 */
		function is_image_from_extension( $path ) {
			$supported_image = array(
				'gif',
				'jpg',
				'jpeg',
				'png'
			);
			$ext             = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ); // Using strtolower to overcome case sensitive
			if ( in_array( $ext, $supported_image ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Excludes the Media Upload Directory ( Checks for Year and Month )
		 *
		 * @param $path
		 *
		 * @return bool
		 *
		 * Borrowed from Shortpixel - (y)
		 *
		 * @todo: Add a option to filter images if User have turned off the Year and Month Organize option
		 *
		 */
		public function skip_dir( $path ) {

			//Admin Directory path
			$admin_dir = $this->get_admin_path();

			//Includes directory path
			$includes_dir = ABSPATH . WPINC;

			//Upload Directory
			$upload_dir = wp_upload_dir();
			$base_dir   = $upload_dir["basedir"];

			$skip = false;

			//Skip sites folder for Multisite
			if ( false !== strpos( $path, $base_dir . '/sites' ) ) {
				$skip = true;
			} else if ( false !== strpos( $path, $base_dir ) ) {
				//If matches the current upload path
				//contains one of the year subfolders of the media library
				$pathArr = explode( '/', str_replace( $base_dir . '/', "", $path ) );
				if ( count( $pathArr ) >= 1
				     && is_numeric( $pathArr[0] ) && $pathArr[0] > 1900 && $pathArr[0] < 2100 //contains the year subfolder
				     && ( count( $pathArr ) == 1 //if there is another subfolder then it's the month subfolder
				          || ( is_numeric( $pathArr[1] ) && $pathArr[1] > 0 && $pathArr[1] < 13 ) )
				) {
					$skip = true;
				}
			} elseif ( ( false !== strpos( $path, $admin_dir ) ) || false !== strpos( $path, $includes_dir ) ) {
				$skip = true;
			}

			/**
			 * Can be used to skip/include folders matching a specific directory path
			 *
			 */
			apply_filters( 'wp_smush_skip_folder', $skip, $path );

			return $skip;
		}

		/**
		 * Creates a tree out of Given path array
		 *
		 * @param $path_list Array of path and images
		 * @param $base_dir Selected Base Path for the image search
		 *
		 * @return array Array of images, Child Directories and images inside
		 *
		 */
		function build_tree( $path_list, $base_dir ) {
			$path_tree = array();
			foreach ( $path_list as $path => $images ) {
				$path     = str_replace( $base_dir, '', $path );
				$list     = explode( '/', trim( $path, '/' ), 3 );
				$last_dir = &$path_tree;
				$length   = sizeof( $list );
				foreach ( $list as $dir ) {
					$length --;
					$last_dir =& $last_dir[ $dir ];
				}
			}

			return $path_tree;
		}

		/**
		 * Returns the count of optimised image
		 *
		 * @param array $images
		 *
		 * @return int
		 */
		function optimised_count( $images = array() ) {
			//If we have optimised images
			if ( ! empty( $images ) && is_array( $images ) ) {
				$optimised = 0;
				if ( ! is_array( $this->optimised_images ) ) {
					return 0;
				}
				foreach ( $images as $item ) {
					//Check if the image is already in optimised list
					if ( array_key_exists( $item, $this->optimised_images ) ) {
						$optimised ++;
					}
				}
			}

			return $optimised;
		}

		/**
		 *
		 * Search for image id from path
		 *
		 * @param $path Image path to be searched
		 * @param $images Array of images
		 *
		 * @return image id
		 */
		function get_image_id( $path, $images ) {
			foreach ( $images as $key => $val ) {
				if ( $val['path'] === $path ) {
					return $val['id'];
				}
			}

			return null;
		}

		/**
		 *
		 * Search for image id from path
		 *
		 * @param $path Image path to be searched
		 * @param $images Array of images
		 *
		 * @return image id
		 */
		function get_image_path( $id, $images ) {
			foreach ( $images as $key => $val ) {
				if ( $val['id'] === $id ) {
					return $val['path'];
				}
			}

			return null;
		}

		/**
		 * Search for image from given image id or path
		 *
		 * @param string $id Image id to search for
		 * @param string $path Image path to search for
		 * @param $images Image array to search within
		 *
		 * @return Image array or Empty array
		 */
		function get_image( $id = '', $path = '', $images ) {
			foreach ( $images as $key => $val ) {
				if ( ! empty( $id ) && $val['id'] == $id ) {
					return $images[ $key ];
				} elseif ( ! empty( $path ) && $val['path'] == $path ) {
					return $images[ $key ];
				}
			}

			return array();
		}

		/*
		 * Generate the markup for all the images
		 *
		 * @note Work in progress.
		 */
		function generate_markup( $images ) {

			global $wpsmush_helper;

			if ( empty( $images ) || empty( $images['files_arr'] ) || empty( $images['image_items'] ) ) {
				return null;
			}

			$this->total_stats();

			$div = wp_nonce_field( 'wp-smush-exclude-path', 'exclude-path-nonce', '', false );
			$div .= '<table class="sui-table sui-accordion wp-smush-image-list smush-dir-smush-accordion">';
			$div .= '<thead><tr><th>' . __( 'Folder', 'wp-smushit' ) . '</th></tr></thead><tbody>';
			$files_arr = $images['files_arr'];

			$plugin_path = array(
				path_join( WP_PLUGIN_DIR, 'wp-smushit' ),
				path_join( WP_PLUGIN_DIR, 'wp-smush-pro' ),
			);

			$total_pending_count = 0;

			foreach ( $files_arr as $image_path => $image ) {

				$count = sizeof( $image );
				$wrapper_class = 'partial';
				$smush_image = false;
				//Mark Smush plugin images optimised
				if ( false !== $wpsmush_helper->strposa( $image_path, $plugin_path ) ) {
					$smush_image = true;
				}
				if ( is_array( $image ) && $count > 1 ) {

					//Get the number of optimised images for the given image array
					$optimised_count = $this->optimised_count( $image );
					$pending_count = $count - $optimised_count;
					$pending_count = $pending_count > 0 ? $pending_count : 0;
					$total_pending_count = $total_pending_count + $pending_count;

					$tag_class = '';
					if ( $optimised_count > 0 ) {
						$wrapper_class = $count == $optimised_count ? 'optimised' : 'partial';
						$tag_class = $count == $optimised_count ? 'sui-tag-inactive' : 'sui-tag-warning';
					}
					//Mark Smush plugin images optimised
					if ( $smush_image ) {
						$wrapper_class = 'optimised';
						$tag_class = 'sui-tag-inactive';
						//Show 100% progress bar for Smush
						$optimised_count = $count;
					}

					$div .= '<tr class="sui-accordion-item">';
					$div .= '<td class="sui-accordion-item-title ' . $wrapper_class . '"><div class="image-list-wrapper"><span class="wp-smush-li-path">' . $image_path . ' <span class="sui-tag ' . $tag_class . '">' . $count . '</span></span>';
					$div .= $this->progress_ui( $count, $optimised_count, $image_path );
					$div .= '</div></td></tr>';
					$item_count = 1;

					foreach ( $image as $item ) {
						$class = ' partial';
						$img_progress_class = '';
						//Check if the image is already in optimised list
						if ( is_array( $this->optimised_images ) && array_key_exists( $item, $this->optimised_images ) ) {
							$class = $img_progress_class = ' optimised';
						} elseif ( false !== $wpsmush_helper->strposa( $item, $plugin_path ) ) {
							//Mark Smush images optimised
							$class = $img_progress_class = ' optimised';
						}

						$image_id = $this->get_image_id( $item, $images['image_items'] );

						if ( $item_count === 1 ) :
							$div .= '<tr class="sui-accordion-item-content">';
							$div .= '<td class="' . $wrapper_class . '"><div class="sui-box"><div class="sui-box-body"><ul class="wp-smush-image-list-inner" tabindex="0">';
						endif;

						if( !empty( $image_id ) ) {
							$div .= '<li class="wp-smush-image-ele' . $class . '" id="' . $image_id . '">';
							$div .= '<span class="wp-smush-image-ele-progress' . $img_progress_class . '"></span>';
							$div .= '<span class="wp-smush-image-ele-status"></span>';
							$div .= '<span class="wp-smush-image-path">' . $item . '</span>';
							$div .= '</li>';
						}
						if ( $count === $item_count ) :
							$div .= '</ul></div></div></td></tr>';
						endif;
						$item_count++;
					}

				} else {

					$image_p = array_pop( $image );

					$class = ' partial';
					$img_progress_class = '';
					// Check if the image is already in optimised list.
					if ( ( is_array( $this->optimised_images ) && array_key_exists( $image_p, $this->optimised_images ) ) || $smush_image ) {
						$class = $img_progress_class = ' optimised';
					} else {
						$total_pending_count = $total_pending_count + 1;
					}

					$image_id = $this->get_image_id( $image_p, $images['image_items'] );

					// Add new table row for images.
					$div .= '<tr>';
					$div .= '<td id="' . $image_id . '" class="wp-smush-image-ele' . $class . '">';
					$div .= '<span class="wp-smush-image-ele-status"></span><span class="wp-smush-image-path">' . $image_p . '</span>';
					$div .= '<span class="wp-smush-image-ele-progress' . $img_progress_class . '"></span>';
					$div .= '</td></tr>';
				}
			}
			$actions_class = $total_pending_count > 0 ? '' : ' sui-hidden';
			$div .= '<tr><td>';
			$div .= '<div class="sui-actions-right wp-smush-all-button-wrap' . $actions_class . '">';
			$div .= '<button class="sui-button sui-button-primary wp-smush-start">' . esc_html__( 'BULK SMUSH', 'wp-smushit' ) . '</button>';
			$div .= '<button type="button" title="' . esc_html__( 'Click to stop the directory smushing process.', 'wp-smushit' ) . '" class="sui-button sui-button-ghost wp-smush-pause sui-hidden">' . esc_html__( 'CANCEL', 'wp-smushit' ) . '</button>';
			$div .= '</div>';
			$div .= '</td></tr>';
			$div .= '</tbody></table>';

			return $div;
		}

		/**
		 * Display a progress bar for the images in particular directory
		 *
		 * @param $count
		 * @param $optimised
		 * @param $dir_path
		 *
		 * @return bool|string
		 */
		function progress_ui( $count, $optimised, $dir_path ) {

			if ( ! $count ) {
				return false;
			}

			$width = ( $optimised > 0 ) ? ( $optimised / $count ) * 100 : 0;

			$class = $count === $optimised ? ' optimised' : '';
			$percent_text = 0 == $width ? esc_html__( 'Waiting...', 'wp-smushit' ) : $width . '%';

			$content = '<div class="wp-smush-dir-progress-wrap sui-progress-block sui-progress-can-close">';
			$content .= '<span class="wp-smush-image-progress-percent ' . $class . ' sui-hidden">' . $percent_text . '</span>';
			$content .= '<span class="wp-smush-image-dir-progress ' . $class . '"></span>';
			$content .= '<span class="wp-smush-image-dir-exclude">';
			$content .= $width !== 100 ? '<button class="sui-progress-close sui-tooltip wp-smush-exclude-dir" data-path="' . $dir_path . '" type="button" data-tooltip="' . esc_html__( 'Remove', 'wp-smushit' ) . '"><i class="sui-icon-close"></i></button>' : '';
			$content .= '</span>';
			$content .= "</div>";

			return $content;
		}

		/**
		 * Fetch all the optimised image, Calculate stats
		 *
		 * @return array Total Stats
		 *
		 */
		function total_stats() {
			global $wpdb, $wp_smush;

			$offset    = 0;
			$optimised = 0;
			$limit     = 1000;
			$images    = array();

			$total = $wpdb->get_col( "SELECT count(id) FROM {$wpdb->prefix}smush_dir_images" );

			$total = ! empty( $total ) && is_array( $total ) ? $total[0] : 0;

			// If super-smush enabled, add meta condition.
			$lossy_condition = $wp_smush->lossy_enabled ? 'AND lossy = 1' : '';

			$continue = true;
			while ( $continue && $results = $wpdb->get_results( "SELECT path, image_size, orig_size FROM {$wpdb->prefix}smush_dir_images WHERE image_size IS NOT NULL $lossy_condition ORDER BY `id` LIMIT $offset, $limit", ARRAY_A ) ) {
				if ( ! empty( $results ) ) {
					$images = array_merge( $images, $results );
				}
				$offset += $limit;
				//If offset is above total number, do not query
				if ( $offset > $total ) {
					$continue = false;
				}
			}

			//Iterate over stats, Return Count and savings
			if ( ! empty( $images ) ) {
				$this->stats                     = array_shift( $images );
				$path                            = $this->stats['path'];
				$this->optimised_images[ $path ] = $this->stats;

				foreach ( $images as $im ) {
					foreach ( $im as $key => $val ) {
						if ( 'path' == $key ) {
							$this->optimised_images[ $val ] = $im;
							continue;
						}
						$this->stats[ $key ] += $val;
					}
					$optimised ++;
				}
			}

			//Get the savings in bytes and percent
			if ( ! empty( $this->stats ) && ! empty( $this->stats['orig_size'] ) ) {
				$this->stats['bytes']   = ( $this->stats['orig_size'] > $this->stats['image_size'] ) ? $this->stats['orig_size'] - $this->stats['image_size'] : 0;
				$this->stats['percent'] = number_format_i18n( ( ( $this->stats['bytes'] / $this->stats['orig_size'] ) * 100 ), 1 );
				//Convert to human readable form
				$this->stats['human'] = size_format( $this->stats['bytes'], 1 );
			}

			$this->stats['total']     = $total;
			$this->stats['optimised'] = $optimised;

			return $this->stats;

		}

		/**
		 * Returns the number of images scanned and optimised
		 *
		 * @return array
		 *
		 */
		function last_scan_stats() {
			global $wpdb;
			$query   = "SELECT id, image_size, orig_size FROM {$wpdb->prefix}smush_dir_images WHERE last_scan = (SELECT MAX(last_scan) FROM {$wpdb->prefix}smush_dir_images ) GROUP BY id";
			$results = $wpdb->get_results( $query, ARRAY_A );
			$total   = count( $results );
			$smushed = 0;
			$stats   = array(
				'image_size' => 0,
				'orig_size'  => 0
			);

			//Get the Smushed count, and stats sum
			foreach ( $results as $image ) {
				if ( ! is_null( $image['image_size'] ) ) {
					$smushed ++;
				}
				//Summation of stats
				foreach ( $image as $k => $v ) {
					if ( 'id' == $k ) {
						continue;
					}
					$stats[ $k ] += $v;
				}
			}

			//Stats
			$stats['total']   = $total;
			$stats['smushed'] = $smushed;

			return $stats;
		}

		/**
		 * Handles the ajax request  for image optimisation in a folder
		 */
		function optimise() {
			global $wpdb, $wp_smush, $wpsmushit_admin;

			//Verify the ajax nonce
			check_ajax_referer( 'wp_smush_all', 'nonce' );

			$error_msg = '';
			if ( empty( $_GET['image_id'] ) ) {
				//If there are no stats
				$error_msg = esc_html__( "Incorrect image id", "wp-smushit" );
				wp_send_json_error( $error_msg );
			}

			// Get the last scan stats.
			$last_scan = $this->last_scan_stats();
			$stats     = array();

			//Check smush limit for free users
			if ( ! $wp_smush->validate_install() ) {

				//Free version bulk smush, check the transient counter value
				$should_continue = $wpsmushit_admin->check_bulk_limit( false, 'dir_sent_count' );

				//Send a error for the limit
				if ( ! $should_continue ) {
					wp_send_json_error(
						array(
							'error'    => 'dir_smush_limit_exceeded',
							'continue' => false
						)
					);
				}
			}

			$id = intval( $_GET['image_id'] );
			if ( ! $scanned_images = wp_cache_get( 'wp_smush_scanned_images' ) ) {
				$scanned_images = $this->get_scanned_images();
			}

			$image = $this->get_image( $id, '', $scanned_images );

			if ( empty( $image ) ) {
				//If there are no stats
				$error_msg = esc_html__( "Could not find image id in last scanned images", "wp-smushit" );
				wp_send_json_error( $error_msg );
			}

			$path = $image['path'];

			//We have the image path, optimise
			$smush_results = $wp_smush->do_smushit( $path );

			if ( is_wp_error( $smush_results ) ) {
				$error_msg = $smush_results->get_error_message();
			} else if ( empty( $smush_results['data'] ) ) {
				//If there are no stats
				$error_msg = esc_html__( "Image couldn't be optimized", "wp-smushit" );
			}

			if ( ! empty( $error_msg ) ) {

				//Store the error in DB
				//All good, Update the stats
				$query = "UPDATE {$wpdb->prefix}smush_dir_images SET error=%s WHERE id=%d LIMIT 1";
				$query = $wpdb->prepare( $query, $error_msg, $id );
				$wpdb->query( $query );

				$error_msg = "<div class='wp-smush-error'>" . $error_msg . "</div>";

				wp_send_json_error(
					array(
						'error' => $error_msg,
						'image' => array( 'id' => $id )
					)
				);
			}
			//Get file time
			$file_time = @filectime( $path );

			// If super-smush enabled, update supersmushed meta value also.
			$lossy = $wp_smush->lossy_enabled ? 1 : 0;

			// All good, Update the stats.
			$query = "UPDATE {$wpdb->prefix}smush_dir_images SET image_size=%d, file_time=%d, lossy=%s WHERE id=%d LIMIT 1";
			$query = $wpdb->prepare( $query, $smush_results['data']->after_size, $file_time, $lossy, $id );
			$wpdb->query( $query );

			// Get the global stats if current dir smush completed.
			if ( isset( $_GET['get_stats'] ) && 1 == $_GET['get_stats'] ) {
				// This will setup directory smush stats too.
				$wpsmushit_admin->setup_global_stats();
				$stats          = $wpsmushit_admin->stats;
				$stats['total'] = $wpsmushit_admin->total_count;
				$resmush_count  = empty( $wpsmushit_admin->resmush_ids ) ? count( $wpsmushit_admin->resmush_ids = get_option( "wp-smush-resmush-list" ) ) : count( $wpsmushit_admin->resmush_ids );
//				$stats['smushed'] = ! empty( $wpsmushit_admin->resmush_ids ) ? $wpsmushit_admin->smushed_count - $resmush_count : $wpsmushit_admin->smushed_count;
				$stats['smushed'] = $wpsmushit_admin->smushed_count;
				if ( $lossy == 1 ) {
					$stats['super_smushed'] = $wpsmushit_admin->super_smushed;
				}
				// Set tootltip text to update.
				$stats['tooltip_text'] = ! empty( $stats['total_images'] ) ? sprintf( __( "You've smushed %d images in total.", "wp-smushit" ), $stats['total_images'] ) : '';
				// Get the total dir smush stats.
				$total = $wpsmushit_admin->dir_stats;
			} else {
				$total = $this->total_stats();
			}

			//Show the image wise stats
			$image = array(
				'id'          => $id,
				'size_before' => $image['orig_size'],
				'size_after'  => $smush_results['data']->after_size
			);

			$bytes            = $image['size_before'] - $image['size_after'];
			$image['savings'] = size_format( $bytes, 1 );
			$image['percent'] = $image['size_before'] > 0 ? number_format_i18n( ( ( $bytes / $image['size_before'] ) * 100 ), 1 ) . '%' : 0;

			$data = array(
				'image'       => $image,
				'total'       => $total,
				'latest_scan' => $last_scan,
			);

			// If current dir smush completed, include global stats.
			if ( ! empty( $stats ) ) {
				$data['stats'] = $stats;
			}

			//Update Bulk Limit Transient
			$wpsmushit_admin->update_smush_count( 'dir_sent_count' );

			wp_send_json_success( $data );
		}

		/**
		 * Remove image/image from db based on path details
		 */
		function smush_exclude_path() {
			//Validate Ajax nonce
			check_ajax_referer( 'wp-smush-exclude-path', 'nonce' );

			//If we don't have path, send json error
			if ( empty( $_POST['path'] ) ) {
				wp_send_json_error( 'missing_path' );
			}

			global $wpdb;

			$path  = realpath( $_POST['path'] );
			$table = "{$wpdb->prefix}smush_dir_images";
			if ( is_file( $path ) ) {
				$sql = sprintf( "DELETE FROM $table WHERE path='%s'", $path );
			} else {
				$sql = sprintf( "DELETE FROM $table WHERE path LIKE '%s'", '%' . $path . '%' );
			}

			//Execute the query
			$result = $wpdb->query( $sql );

			if ( $result ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}

		/**
		 * Send the markup for image list scanned from a directory path
		 *
		 */
		function resume_scan() {
			$dir_path = get_option( 'wp-smush-dir_path' );

			//If we don't get an error path, return an error message
			if ( empty( $dir_path ) ) {
				$message = "<div class='error'>" . esc_html__( "We were unable to retrieve the image list from last scan, please continue with the latest scan", "wp-smushit" ) . "</div>";
				wp_send_json_error( array( 'message' => $message ) );
			}

			//Else, Get the image list and then markup
			$file_list = $this->get_image_list( $dir_path );

			//If there are no image files in selected directory
			if ( empty( $file_list['files_arr'] ) ) {
				$this->send_error();
			}

			$markup = $this->generate_markup( $file_list );

			//Send response
			wp_send_json_success( $markup );
		}

		/**
		 * Combine the stats from Directory Smush and Media Library Smush
		 *
		 * @param $stats Directory Smush stats
		 *
		 * @return array Combined array of Stats
		 */
		function combined_stats( $stats ) {

			if ( empty( $stats ) || empty( $stats['percent'] ) || empty( $stats['bytes'] ) ) {
				return array();
			}

			global $wpsmushit_admin;

			$result    = array();
			$dasharray = 125.663706144;

			//Initialize Global Stats
			$wpsmushit_admin->setup_global_stats();

			//Get the total/Smushed attachment count
			$total_attachments = $wpsmushit_admin->total_count + $stats['total'];
			$total_images      = $wpsmushit_admin->stats['total_images'] + $stats['total'];

			$smushed     = $wpsmushit_admin->smushed_count + $stats['optimised'];
			$savings     = ! empty( $wpsmushit_admin->stats ) ? $wpsmushit_admin->stats['bytes'] + $stats['bytes'] : $stats['bytes'];
			$size_before = ! empty( $wpsmushit_admin->stats ) ? $wpsmushit_admin->stats['size_before'] + $stats['orig_size'] : $stats['orig_size'];
			$percent     = $size_before > 0 ? ( $savings / $size_before ) * 100 : 0;

			//Store the stats in array
			$result = array(
				'total_count'   => $total_attachments,
				'smushed_count' => $smushed,
				'savings'       => size_format( $savings ),
				'percent'       => round( $percent, 1 ),
				'image_count'   => $total_images,
				'dash_offset'   => $total_attachments > 0 ? $dasharray - ( $dasharray * ( $smushed / $total_attachments ) ) : $dasharray,
				'tooltip_text'  => ! empty( $total_images ) ? sprintf( __( "You've smushed %d images in total.", "wp-smushit" ), $total_images ) : ''
			);

			return $result;
		}

		/**
		 * Returns Directory Smush stats and Cumulative stats
		 *
		 */
		function get_dir_smush_stats() {

			$result = array();

			//Store the Total/Smushed count
			$stats = $this->total_stats();

			$result['dir_smush'] = $stats;

			//Cumulative Stats
			$result['combined_stats'] = $this->combined_stats( $stats );

			//Store the stats in options table
			update_option( 'dir_smush_stats', $result, false );

			//Send ajax response
			wp_send_json_success( $result );
		}

		/**
		 * Output the content for Directory smush list dialog content
		 *
		 */
		function directory_list_dialog() {

			$current_screen = get_current_screen();
			if ( empty( $current_screen ) || empty( $current_screen->base ) || ( 'toplevel_page_smush' != $current_screen->base && 'toplevel_page_smush-network' != $current_screen->base ) ) {
				return;
			}
			?>

			<div class="sui-dialog wp-smush-list-dialog" aria-hidden="true" tabindex="-1" id="wp-smush-list-dialog">

				<div class="sui-dialog-overlay sui-fade-in"></div>

				<div class="sui-dialog-content sui-bounce-in" aria-labelledby="smush-dir-modal-title" role="dialog">

					<div class="sui-box" role="document">

						<div class="sui-box-header">
							<h3 class="sui-box-title" id="smush-dir-modal-title"><?php esc_html_e( 'Choose Directory', 'wp-smushit' ); ?></h3>
							<div class="sui-actions-right">
								<button class="sui-dialog-close" data-a11y-dialog-hide aria-label="<?php esc_html_e( 'Close', 'wp-smushit' ); ?>"></button>
							</div>
						</div>

						<div class="sui-box-body">
							<p><?php esc_html_e( 'Choose which folder you wish to smush. Smush will automatically include any images in subfolders of your selected folder.', 'wp-smushit' ); ?></p>
							<div class="content"></div>
						</div>

						<div class="sui-box-footer">
							<div class="sui-actions-right">
								<span class="add-dir-loader"></span>
								<button class="sui-modal-close sui-button wp-smush-select-dir"><?php esc_html_e( 'ADD FOLDER', 'wp-smushit' ); ?></button>
							</div>
						</div>

					</div>

				</div>

			</div>
			<?php
		}

		/**
		 * Display a admin notice on smush screen if the custom table wasn't created
		 *
		 * @return Notice if table doesn't exists
		 *
		 * @todo: Update text
		 */
		function check_for_table_error() {
			global $wpdb;
			$notice = '';
			$current_screen = get_current_screen();
			if ( 'toplevel_page_smush' != $current_screen->id && 'toplevel_page_smush-network' != $current_screen->id ) {
				return $notice;
			}
			$sql         = $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $wpdb->prefix . 'smush_dir_images' ) );
			$smush_table = ( $wpdb->get_var( $sql ) != null );
			if ( ! $smush_table ) {
				//Display a notice
				$notice = '<div class="sui-notice sui-notice-warning missing_table"><p>';
				$notice .= esc_html__( 'Directory smushing requires custom tables and it seems there was an error creating tables. For help, please contact our team on the support forums', "wp-smushit" );
				$notice .= '</p></div>';
			}

			return $notice;
		}

		/**
		 * Remove directory smush from tabs.
		 *
		 * If not in main site, do not show directory smush.
		 *
		 * @param array $tabs Tabs.
		 *
		 * @return array
		 */
		public function remove_directory_tab( $tabs ) {

			if ( isset( $tabs['directory'] ) ) {
				unset( $tabs['directory'] );
			}

			return $tabs;
		}

	}

	//Class Object
	global $wpsmush_dir;
	$wpsmush_dir = new WpSmushDir();
}

/**
 * Filters the list of directories, Exclude the Media Subfolders
 *
 */
if ( class_exists( 'RecursiveFilterIterator' ) && ! class_exists( 'WPSmushRecursiveFilterIterator' ) ) {
	class WPSmushRecursiveFilterIterator extends RecursiveFilterIterator {

		public function accept() {
			global $wpsmush_dir;
			$path = $this->current()->getPathname();
			if ( $this->isDir() ) {
				if ( ! $wpsmush_dir->skip_dir( $path ) ) {
					return true;
				}
			} else {
				return true;
			}

			return false;
		}

	}
}