<?php
/**
 * @package WP Smush
 * @subpackage Admin
 * @version 1.0
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */
if ( ! class_exists( 'WpSmushBulkUi' ) ) {
	/**
	 * Show settings in Media settings and add column to media library
	 *
	 */

	/**
	 * Class WpSmushBulkUi
	 */
	class WpSmushBulkUi {

	    public $setting_group = array(
	      'resize',
	      'original',
	      'backup'
	    );

	    function __construct() {
	        add_action('smush_setting_column_right_end', array( $this, 'full_size_options' ), '', 2 );
	    }

		/**
		 * Prints the Header Section for a container as per the Shared UI
		 *
		 * @param string $classes Any additional classes that needs to be added to section
		 * @param string $heading Box Heading
		 * @param string $sub_heading Any additional text to be shown by the side of Heading
		 * @param bool $dismissible If the Box is dimissible
		 *
		 * @return string
		 */
		function container_header( $classes = '', $id = '', $heading = '', $sub_heading = '', $dismissible = false ) {
			if ( empty( $heading ) ) {
				return '';
			}
			echo '<section class="dev-box ' . $classes . ' wp-smush-container" id="' . $id . '">'; ?>
			<div class="wp-smush-container-header box-title" xmlns="http://www.w3.org/1999/html">
			<h3 tabindex="0"><?php echo $heading ?></h3><?php
			//Sub Heading
			if ( ! empty( $sub_heading ) ) { ?>
				<div class="smush-container-subheading roboto-medium"><?php echo $sub_heading ?></div><?php
			}
			//Dismissible
			if ( $dismissible ) { ?>
				<div class="float-r smush-dismiss-welcome">
				<a href="#" title="<?php esc_html_e( "Dismiss Welcome notice", "wp-smushit" ); ?>">
					<i class="icon-fi-close"></i>
				</a>
				</div><?php
			} ?>
			</div><?php
		}

		/**
		 *  Prints the content of WelCome Screen for New Installation
		 *  Dismissible by default
		 */
		function quick_setup() {
			global $WpSmush, $wpsmushit_admin, $wpsmush_settings;

			//Header Of the Box ?>
			<dialog id="smush-quick-setup" title="<?php esc_html_e( "QUICK SETUP", "wp-smushit" ); ?>" class="small">
				<p class="wp-smush-welcome-message end"><?php esc_html_e( 'Welcome to Smush - Winner of Torque Plugin Madness 2017! Let\'s quickly set up the basics for you, then you can fine tune each setting as you go - our recommendations are on by default.', "wp-smushit" ); ?></p>
				<div class="smush-quick-setup-settings">
					<form method="post">
					<input type="hidden" value="setupSmush" name="action"/><?php
					wp_nonce_field( 'setupSmush' );
					    $exclude = array(
					            'backup',
					            'png_to_jpg',
					            'nextgen',
					            's3'
					    );
					    //Settings for free and pro version
					    foreach( $wpsmushit_admin->settings as $name => $values ) {
					        //Skip networkwide settings, we already printed it
					        if( 'networkwide' == $name ) {
					            continue;
					        }
					        //Skip premium features if not a member
					        if( !in_array( $name, $wpsmushit_admin->basic_features ) && !$WpSmush->validate_install() ) {
					            continue;
					        }
					        //Do not output settings listed in exclude array list
					        if( in_array($name, $exclude ) ) {
					            continue;
					        }
					        $setting_m_key = WP_SMUSH_PREFIX . $name;
							$setting_val   = $WpSmush->validate_install() ? $wpsmush_settings->settings[$name] : false;
							//Set the default value 1 for auto smush
							if( 'auto' == $name && false === $setting_val ) {
							    $setting_val = 1;
							} ?>
							<div class='wp-smush-setting-row wp-smush-basic'>
								<label class="inline-label" for="<?php echo $setting_m_key . '-quick-setup'; ?>">
									<span class="wp-smush-setting-label"><?php echo $wpsmushit_admin->settings[ $name ]['label']; ?></span><br/>
									<small class="smush-setting-description">
		                                <?php echo $wpsmushit_admin->settings[ $name ]['desc']; ?>
		                            </small>
		                        </label>
		                        <span class="toggle float-r">
		                            <input type="checkbox" class="toggle-checkbox"
		                               id="<?php echo $setting_m_key . '-quick-setup'; ?>"
		                               name="smush_settings[]" <?php checked( $setting_val, 1, true ); ?> value="<?php echo $setting_m_key; ?>" tabindex="0">
		                            <label class="toggle-label" for="<?php echo $setting_m_key . '-quick-setup'; ?>" aria-hidden="true"></label>
		                        </span>
		                        <?php $this->resize_settings( $name, 'quick-setup-' ); ?>
							</div><?php
						}
						?>
						<div class="columns last">
							<div class="column is-3 tr submit-button-wrap">
								<button type="submit" class="button"><?php _e( "Get Started", "wp-smushit" ) ?></button>
							</div>
						</div>
					</form>
				</div>
			</dialog><?php
		}

		/**
		 * Bulk Smush UI and Progress bar
		 */
		function bulk_smush_container() {
			global $WpSmush;

			//Subheading content
			$smush_individual_msg = sprintf( esc_html__( "Smush individual images via your %sMedia Library%s", "wp-smushit" ), '<a href="' . esc_url( admin_url( 'upload.php' ) ) . '" title="' . esc_html__( 'Media Library', 'wp-smushit' ) . '">', '</a>' );

			$class = $WpSmush->validate_install() ? 'bulk-smush-wrapper wp-smush-pro-install' : 'bulk-smush-wrapper';

			//Contianer Header
			$this->container_header( $class, 'wp-smush-bulk-wrap-box', esc_html__( "BULK SMUSH", "wp-smushit" ), $smush_individual_msg ); ?>

			<div class="box-container"><?php
			$this->bulk_smush_content(); ?>
			</div><?php
			echo "</section>";
		}

		/**
		 * All the settings for Basic and Advanced Users
		 */
		function settings_ui() {
			global $WpSmush;
			$class = $WpSmush->validate_install() ? 'smush-settings-wrapper wp-smush-pro' : 'smush-settings-wrapper';
			$this->container_header( $class, 'wp-smush-settings-box', esc_html__( "SETTINGS", "wp-smushit" ), '' );
			// display the options
			$this->options_ui();
		}

		/**
        * Outputs the Smush stats for the site
        * @todo: Divide the function into parts, way too big
        *
        */
		function smush_stats_container() {
			global $WpSmush, $wpsmushit_admin, $wpsmush_db, $wpsmush_settings, $wpsmush_dir;

			$settings = $wpsmush_settings->settings;

			$button = '<span class="spinner"></span><button tooltip="' . esc_html__( "Lets you check if any images can be further optimized. Useful after changing settings.", "wp-smushit" ) . '" class="wp-smush-title button button-grey button-small wp-smush-scan">' . esc_html__( "RE-CHECK IMAGES", "wp-smushit" ) . '</button>';
			$this->container_header( 'smush-stats-wrapper', 'wp-smush-stats-box', esc_html__( "STATS", "wp-smushit" ), $button );

			$resize_count = $wpsmush_db->resize_savings( false, false, true );
			$resize_count = !$resize_count ? 0 : $resize_count;

			$compression_savings = 0;

			if( !empty( $wpsmushit_admin->stats ) && !empty( $wpsmushit_admin->stats['bytes'] ) ) {
    			$compression_savings = $wpsmushit_admin->stats['bytes'] - $wpsmushit_admin->stats['resize_savings'];
			}

			$tooltip = $wpsmushit_admin->stats['total_images'] > 0 ? 'tooltip="' . sprintf( esc_html__("You've smushed %d images in total", "wp-smushit"), $wpsmushit_admin->stats['total_images'] ) . '"' : ''; ?>
			<div class="box-content">
			<div class="row smush-total-savings smush-total-reduction-percent">

                <div class="wp-smush-current-progress" >
                    <!-- Total Images Smushed -->
                    <div class="wp-smush-count-total">
                        <div class="wp-smush-smush-stats-wrapper">
                            <span class="wp-smush-total-optimised"><?php echo $wpsmushit_admin->stats['total_images']; ?></span>
                        </div>
                        <span class="total-stats-label"><?php esc_html_e( "Images smushed", "wp-smushit" ); ?></span>
                    </div>
                    <!-- Attachments And Resized Images -->
                    <div class="wp-smush-stats-other">
                        <!-- Attachment count -->
                        <div class="wp-smush-count-attachment-total">
                            <div class="wp-smush-smush-stats-wrapper">
                                <span class="wp-smush-total-optimised"><?php echo $wpsmushit_admin->smushed_count; ?></span>
                            </div>
                            <span class="total-stats-label"><?php esc_html_e( "Attachments smushed", "wp-smushit" ); ?></span>
                        </div>
                        <!-- Resized Image count -->
                        <div class="wp-smush-count-resize-total">
                            <div class="wp-smush-smush-stats-wrapper">
                                <span class="wp-smush-total-optimised"><?php echo $resize_count; ?></span>
                            </div>
                            <span class="total-stats-label"><?php esc_html_e( "Images resized", "wp-smushit" ); ?></span>
                        </div>
                    </div>

                </div>
			</div>
			<hr />
			<div class="row wp-smush-savings">
				<span class="float-l wp-smush-stats-label"><?php esc_html_e("Total savings", "wp-smushit");?></span>
				<span class="float-r wp-smush-stats">
					<span class="wp-smush-stats-human">
						<?php echo $wpsmushit_admin->stats['human'] > 0 ? $wpsmushit_admin->stats['human'] : "0MB"; ?>
					</span>
					<span class="wp-smush-stats-sep">/</span>
					<span class="wp-smush-stats-percent"><?php echo $wpsmushit_admin->stats['percent'] > 0 ? number_format_i18n( $wpsmushit_admin->stats['percent'], 1, '.', '' ) : 0; ?></span>%
				</span>
			</div><?php
			/**
			 * Allows to hide the Super Smush stats as it might be heavy for some users
			 */
			if ( $WpSmush->validate_install() && apply_filters( 'wp_smush_show_lossy_stats', true ) ) {
				$wpsmushit_admin->super_smushed = $wpsmush_db->super_smushed_count();?>
				<hr />
				<div class="row super-smush-attachments">
				<span class="float-l wp-smush-stats-label"><strong><?php esc_html_e( "Super-smushed savings", "wp-smushit" ); ?></strong></span>
				<span class="wp-smush-stats<?php echo $WpSmush->lossy_enabled ? ' float-r' : ' float-l wp-smush-lossy-disabled-wrap' ?>"><?php
					if ( $WpSmush->lossy_enabled ) {
						echo '<span class="smushed-savings">' . size_format( $compression_savings, 1 ) . '</span>';
					} else {
					    //Output a button/link to enable respective setting
					    if( !is_multisite() || !$settings['networkwide'] ) {
                            printf( esc_html__( "Compress images up to 2x more than regular smush with almost no visible drop in quality. %sEnable Super-smush%s", "wp-smushit" ), '<a class="wp-smush-lossy-enable" href="#">', '</a>' );
						}else {
					        $settings_link = $wpsmushit_admin->settings_link( array(), true );
                            printf( esc_html__( "Compress images up to 2x more than regular smush with almost no visible drop in quality. %sEnable Super-smush%s", "wp-smushit" ), '<a class="wp-smush-lossy-enable-network" href="'. $settings_link .'">', '</a>' );
						}
					} ?>
				</span>
				</div><?php
			} ?>
			<hr /><?php
			    if( !$settings['resize'] && empty( $wpsmushit_admin->stats['resize_savings'] ) ) {
			        $class = ' settings-desc float-l';
			    }elseif ( empty( $wpsmushit_admin->stats['resize_savings'] ) ) {
			        $class = ' settings-desc float-r';
			    }else{
			        $class = ' float-r';
			    }
			  ?>
			<div class="row smush-resize-savings">
				<span class="float-l wp-smush-stats-label"><strong><?php esc_html_e( "Resize savings", "wp-smushit" ); ?></strong></span>
				<span class="wp-smush-stats<?php echo $class; ?>"><?php
					if( !empty( $wpsmushit_admin->stats['resize_savings'] ) && $wpsmushit_admin->stats['resize_savings'] > 0 ) {
						echo size_format( $wpsmushit_admin->stats['resize_savings'], 1 );
					}else{
						if( !$settings['resize'] ) {
							//Output a button/link to enable respective setting
							if( !is_multisite() || !$settings['networkwide'] ) {
							    printf( esc_html__( "Save storage space by resizing your full sized uploads down to a maximum size. %sEnable image resizing%s", "wp-smushit" ), '<a class="wp-smush-resize-enable" href="#">', '</a>' );
                            }else {
                                $settings_link = $wpsmushit_admin->settings_link( array(), true );
                                printf( esc_html__( "Save storage space by resizing your full sized uploads down to a maximum size. %sEnable image resizing%s", "wp-smushit" ), '<a href="' . $settings_link .'" class="wp-smush-resize-enable">', '</a>' );
                            }
						}else{
							printf( esc_html__( "No resize savings available", "wp-smushit" ), '<span class="total-stats-label"><strong>', '</strong></span>' );
						}
					} ?>
				</span>
			</div>
			<?php
			if( $WpSmush->validate_install() && !empty( $wpsmushit_admin->stats['conversion_savings'] ) && $wpsmushit_admin->stats['conversion_savings'] > 0 ) { ?>
				<hr />
				<div class="row smush-conversion-savings">
					<span class="float-l wp-smush-stats-label"><strong><?php esc_html_e( "PNG to JPEG savings", "wp-smushit" ); ?></strong></span>
					<span class="float-r wp-smush-stats"><?php echo $wpsmushit_admin->stats['conversion_savings'] > 0 ? size_format( $wpsmushit_admin->stats['conversion_savings'], 1 ) : "0MB"; ?></span>
				</div><?php
			}
			/**
			* Allows to output Directory Smush stats
            */
			do_action('stats_ui_after_resize_savings');
			/**
			 * Allows you to output any content within the stats box at the end
			 */
			do_action( 'wp_smush_after_stats' );
			echo "</div>";
			//Pro Savings Expected: For free Version
			if ( ! $WpSmush->validate_install() ) {
			    //Initialize pro savings if not set already
			    if( empty( $wpsmushit_admin->stats) || empty( $wpsmushit_admin->stats['pro_savings'] ) ) {
			        $wpsmushit_admin->set_pro_savings();
			    }
			    $pro_savings = $wpsmushit_admin->stats['pro_savings'];
			    $show_pro_savings = $pro_savings['savings'] > 0 ? true : false;

				//If we have any savings
				$upgrade_url = add_query_arg(
					array(
						'utm_source' => 'Smush-Free',
						'utm_medium' => 'Banner',
						'utm_campaign'=> 'pro-only-stats'
					),
					$wpsmushit_admin->upgrade_url
				);
				$pro_only = sprintf( esc_html__( '%sPRO FEATURE%s', 'wp-smushit' ), '<a href="' . esc_url( $upgrade_url ) . '" target="_blank" tooltip="'. esc_html__( "Join WPMU DEV to try Smush Pro for free.", "wp-smushit" ) .'">', '</a>' ); ?>
				<!-- Make a hidden div if not stats found -->
				<div class="row" id="smush-avg-pro-savings" <?php echo $show_pro_savings ? '' : 'style="display: none;"'; ?>>
					<div class="row smush-avg-pro-savings">
						<span class="float-l wp-smush-stats-label"><span tooltip="<?php esc_html_e("BASED ON AVERAGE SAVINGS IF YOU UPGRADE TO PRO", "wp-smushit"); ?>"><strong><?php esc_html_e( "PRO SAVINGS ESTIMATE", "wp-smushit" ); ?></strong></span><span class="wp-smush-stats-try-pro roboto-regular"><?php echo $pro_only; ?></span></span>
						<span class="float-r wp-smush-stats">
							<span class="wp-smush-stats-human">
								<?php echo $show_pro_savings ? $pro_savings['savings']: '0.0 B'; ?>
							</span>
							<span class="wp-smush-stats-sep">/</span>
							<span class="wp-smush-stats-percent"><?php echo $show_pro_savings ? $pro_savings['percent'] : 0;  ?></span>%
						</span>
					</div>
				</div><?php
			}
			echo "</section>";
		}

		/**
		 * Outputs the advanced settings for Pro users, Disabled for basic users by default
		 */
		function advanced_settings( $configure_screen = false ) {
			global $WpSmush, $wpsmushit_admin, $wpsmush_settings;

			//Content for the End of box container
			$div_end = $this->save_button( $configure_screen );

			//Available advanced settings
			$pro_settings = array(
				'lossy',
				'original',
				'backup',
				'png_to_jpg'
			);

			//For Basic User, Show advanced settings in a separate box
			if ( ! $WpSmush->validate_install() ) {
				echo $div_end;
				do_action('wp_smush_before_advanced_settings');
				//Network settings wrapper
				if( is_multisite() && is_network_admin() ) {
					$class = $wpsmush_settings->settings['networkwide'] ? '' : ' hidden'; ?>
					<div class="network-settings-wrapper<?php echo $class; ?>"><?php
				}
				$upgrade_url = add_query_arg(
					array(
						'utm_source' => 'Smush-Free',
						'utm_medium' => 'Banner',
						'utm_campaign'=> 'pro-only-advanced-settings'
					),
					$wpsmushit_admin->upgrade_url
				);
				$pro_only = sprintf( esc_html__( '%sPRO FEATURE%s', 'wp-smushit' ), '<a href="' . esc_url( $upgrade_url ) . '" target="_blank" tooltip="'. esc_html__( "Join WPMU DEV to try Smush Pro for free.", "wp-smushit" ) .'">', '</a>' );

				$this->container_header( 'wp-smush-premium', 'wp-smush-pro-settings-box', esc_html__( "ADVANCED SETTINGS", "wp-smushit" ), $pro_only, false ); ?>
				<div class="box-content"><?php

				$pro_settings = apply_filters( 'wp_smush_pro_settings', $pro_settings );
				//Iterate Over all the available settings, and print a row for each of them
                foreach ( $pro_settings as $setting_key ) {
                    //Output the Full size setting option only once
					if( in_array( $setting_key, $this->setting_group ) ) {
					    if( ( 'original' != $setting_key ) ) {
					        continue;
					    }
					}

                    if ( isset( $wpsmushit_admin->settings[ $setting_key ] ) ) {
                        $setting_m_key = WP_SMUSH_PREFIX . $setting_key;
                        $label = !empty( $wpsmushit_admin->settings[ $setting_key ]['short_label'] ) ? $wpsmushit_admin->settings[ $setting_key ]['short_label'] : $wpsmushit_admin->settings[ $setting_key ]['label'];
                        $setting_val   = $WpSmush->validate_install() ? $wpsmush_settings->get_setting( $setting_m_key, false ) : 0;?>
                        <div class='wp-smush-setting-row wp-smush-advanced'>
                            <div class="column column-left">
                                <label class="inline-label" for="<?php echo $setting_m_key; ?>" aria-hidden="true">
                                    <span class="wp-smush-setting-label"><?php echo $label; ?></span>
                                    <br/>
                                    <small class="smush-setting-description"><?php
                                        if( 'original' != $setting_key ) {
                                            echo $wpsmushit_admin->settings[ $setting_key ]['desc'];
                                        }else{
                                            esc_html_e("By default, Smush only compresses your cropped image sizes, not your original full-size images.", "wp-smushit");
                                        }
                                    ?>
                                    </small>
                                </label>
                            </div>
                            <div class="column column-right"><?php
						    //Do not print for Resize, Smush Original, Backup
						    if( !in_array( $setting_key, $this->setting_group ) ) { ?>
                                <span class="toggle float-l">
                                    <input type="checkbox" class="toggle-checkbox"
                                           id="<?php echo $setting_m_key; ?>" <?php checked( $setting_val, 1, true ); ?>
                                           value="1"
                                           name="<?php echo $setting_m_key; ?>" tabindex= "0">
                                    <label class="toggle-label <?php echo $setting_m_key . '-label'; ?>" for="<?php echo $setting_m_key; ?>" aria-hidden="true"></label>
                                </span>
                                <div class="column-right-content">
                                    <label class="inline-label" for="<?php echo $setting_m_key; ?>" tabindex="0">
                                        <span class="wp-smush-setting-label"><?php echo $wpsmushit_admin->settings[ $setting_key ]['label']; ?></span><br/><?php
                                        $this->settings_desc( $setting_key );
                                        do_action('smush_setting_label_end', $setting_key);
                                        ?>
                                    </label>
                                </div><?php
                            }
                            do_action( 'smush_setting_column_right_end', $setting_key, 'advanced' );
                            ?>

                            </div>
                            <?php
	                             /**
	                             * Perform a action after setting row content
	                             */
	                            do_action('smush_setting_row_end', $setting_key );?>
                        </div><?php
                    }
                }
			}
			//Output Form end and Submit button for pro version
			if ( $WpSmush->validate_install() ) {
				echo $div_end;
			} else {
				echo "</div><!-- Box Content -->";
				$this->super_smush_promo_post_settings();
				echo "</section><!-- Main Section -->";
			}
			//Close wrapper div
			if( is_multisite() && is_network_admin() && !$WpSmush->validate_install() ) {
				echo "</div>";
			}
		}

		/**
		 * Process and display the Settings
		 * Since Free and Pro version have different sequence of settings, we've a separate method advanced_settings(),
		 * Which prints out pro settings fro free version, otherwise all settings are printed via the current function
		 *
		 * To print Full size smush, resize and backup in group, we hook at `smush_setting_column_right_end`
		 *
		 */
		function options_ui( $configure_screen = false ) {
			global $WpSmush, $wpsmushit_admin, $wpsmush_settings;

			$settings = !empty( $wpsmush_settings->settings ) ? $wpsmush_settings->settings : $wpsmush_settings->init_settings();

			echo '<div class="box-container">
				<form id="wp-smush-settings-form" method="post">';

			//Use settings networkwide,@uses get_site_option() and not get_option
			$opt_networkwide = WP_SMUSH_PREFIX . 'networkwide';
			$opt_networkwide_val = $wpsmush_settings->settings['networkwide'];

			//Option to enable/disable networkwide settings
			if( is_multisite() && is_network_admin() ) {
				$class = $wpsmush_settings->settings['networkwide'] ? '' : ' hidden'; ?>
				<!-- A tab index of 0 keeps the element in tab flow with other elements with an unspecified tab index which are still tabbable.) -->
				<div class='wp-smush-setting-row wp-smush-basic'>
				    <div class="column column-left"">
                        <label class="inline-label" for="<?php echo $opt_networkwide; ?>" aria-hidden="true">
                            <span class="wp-smush-setting-label">
                                <?php echo $wpsmushit_admin->settings['networkwide']['short_label']; ?>
                            </span><br/>
                            <small class="smush-setting-description">
                                <?php echo $wpsmushit_admin->settings['networkwide']['desc']; ?>
                            </small>
                        </label>
					</div>
					<div class="column column-right">
                        <span class="toggle float-l">
                            <input type="checkbox" class="toggle-checkbox"
                               id="<?php echo $opt_networkwide; ?>"
                               name="<?php echo $opt_networkwide; ?>" <?php checked( $opt_networkwide_val, 1, true ); ?> value="1">
                            <label class="toggle-label" for="<?php echo $opt_networkwide; ?>" aria-hidden="true"></label>
                        </span>
                        <div class="column-right-content">
                            <label class="inline-label" for="<?php echo $opt_networkwide; ?>">
                                <span class="wp-smush-setting-label"><?php echo $wpsmushit_admin->settings['networkwide']['label']; ?></span><br/>
                            </label>
                        </div>
					</div>
				</div>
				<input type="hidden" name="setting-type" value="network">
				<div class="network-settings-wrapper<?php echo $class; ?>"><?php
			}

			//Do not print settings in network page if networkwide settings are disabled
			if( ! is_multisite() || ( ! $wpsmush_settings->settings['networkwide'] && !is_network_admin() ) || is_network_admin() ) {
				foreach( $wpsmushit_admin->settings as $name => $values ) {

					//Skip networkwide settings, we already printed it
					if( 'networkwide' == $name ) {
						continue;
					}

			        //Skip premium features if not a member
			        if( !in_array( $name, $wpsmushit_admin->basic_features ) && !$WpSmush->validate_install() ) {
			            continue;
			        }

			        $setting_m_key = WP_SMUSH_PREFIX . $name;
					$setting_val   = !empty( $settings[$name] ) ? $settings[$name] : 0;

					//Set the default value 1 for auto smush
					if( 'auto' == $name && ( false === $setting_val || !isset( $setting_val ) ) ) {
					    $setting_val = 1;
					}

					//Group Original, Resize and Backup for pro users
					if( in_array( $name, $this->setting_group ) ) {
					    if( ( 'original' != $name && $WpSmush->validate_install() ) || ( !$WpSmush->validate_install() && 'resize' != $name ) ) {
					        continue;
					    }
					}

					$label = !empty( $wpsmushit_admin->settings[ $name ]['short_label'] ) ? $wpsmushit_admin->settings[ $name ]['short_label'] : $wpsmushit_admin->settings[ $name ]['label']; ?>
					<div class='wp-smush-setting-row wp-smush-basic'>
						<div class="column column-left">
							<label class="inline-label" for="<?php echo 'column-' . $setting_m_key; ?>" aria-hidden="true">
	                            <span class="wp-smush-setting-label"><?php echo $label; ?></span><br/>
	                            <small class="smush-setting-description"><?php
	                                //For pro settings, print a different description for group setting
	                                if( 'original' != $name && 'resize' != $name ) {
	                                    echo $wpsmushit_admin->settings[ $name ]['desc'];
	                                }else{
	                                    esc_html_e("Save a ton of space by not storing over-sized images on your server.", "wp-smushit");
	                                }?>
	                            </small>
	                        </label>
                        </div>
						<div class="column column-right" id="column-<?php echo $setting_m_key; ?>"><?php
						    //Do not print for Resize, Smush Original, Backup
						    if( !in_array( $name, $this->setting_group ) ) { ?>
                                <span class="toggle float-l">
                                    <input type="checkbox" class="toggle-checkbox" aria-describedby="<?php echo $setting_m_key . '-desc'?>"
                                       id="<?php echo $setting_m_key; ?>"
                                       name="<?php echo $setting_m_key; ?>" <?php checked( $setting_val, 1, true ); ?> value="1">
                                    <label class="toggle-label <?php echo $setting_m_key . '-label'; ?>" for="<?php echo $setting_m_key; ?>" aria-hidden="true"></label>
                                </span>
                                <div class="column-right-content">
                                    <label class="inline-label" for="<?php echo $setting_m_key; ?>">
                                        <span class="wp-smush-setting-label"><?php echo $wpsmushit_admin->settings[ $name ]['label']; ?></span><br/>
                                    </label><?php
                                    $this->settings_desc( $name );
                                    $this->image_sizes( $name ); ?>
                                </div><?php
                            }
                            /**
                            * Print/Perform action in right setting column, Used to group Pro settings
                            */
                            do_action('smush_setting_column_right_end', $name); ?>
                        </div>
				    </div>
				    <?php
				}
				do_action( 'wp_smush_after_basic_settings' );
				$this->advanced_settings( $configure_screen );
			} else{
				echo "<hr />";
				echo $this->save_button( $configure_screen );
				echo "</div><!-- Box Content -->
				</section><!-- Main Section -->";
			}
		}

		/**
		 * Display the Whole page ui, Call all the other functions under this
		 */
		function ui() {

			global $WpSmush, $wpsmushit_admin, $wpsmush_settings, $wpsmush_dir;

			if( !$WpSmush->validate_install() ) {
				//Reset Transient
				$wpsmushit_admin->check_bulk_limit( true );
			}

			$this->smush_page_header();
			$is_network = is_network_admin();

			if( !$is_network ) {
				//Show Configure screen for only a new installation and for only network admins
				if ( ( 1 != get_site_option( 'skip-smush-setup' ) && 1 != get_site_option( 'wp-smush-hide_smush_welcome' ) && 1 != get_option( 'wp-smush-hide_smush_welcome' ) ) && 1 != get_option( 'hide_smush_features' ) && is_super_admin() ) {
					echo '<div class="block float-l">';
					$this->quick_setup();
					echo '</div>';
				}
				//If free version
				if( !$WpSmush->validate_install() ) {
				    $this->smush_pro_modal();
				} ?>

				<!-- Bulk Smush Progress Bar -->
				<div class="wp-smushit-container-left col-half float-l"><?php
					//Bulk Smush Container
					$this->bulk_smush_container();
    				if( $WpSmush->validate_install() ) { ?>
	    			    <!-- Stats Share Widget -->
                        <div class="col-half share-widget-wrapper"><?php
                            global $wpsmush_share;
                            $wpsmush_share->share_widget(); ?>
                        </div><?php
    				} ?>
				</div>

				<!-- Stats -->
				<div class="wp-smushit-container-right col-half float-l"><?php
					//Stats
					$this->smush_stats_container();
					if ( ! $WpSmush->validate_install() ) {
						/**
						 * Allows to Hook in Additional Containers after Stats Box for free version
						 * Pro Version has a full width settings box, so we don't want to do it there
						 */
						do_action( 'wp_smush_after_stats_box' );
					} ?>
				</div><!-- End Of Smushit Container right --><?php
                    if( !$WpSmush->validate_install() ) {?>
                        <!-- Stats Share Widget -->
                        <div class="row"><?php
                            global $wpsmush_share;
                            $wpsmush_share->share_widget(); ?>
                        </div><?php
                    }
			//End of "!is_network()' check
			}?>

			<!-- Settings -->
			<div class="row"><?php
				wp_nonce_field( 'save_wp_smush_options', 'wp_smush_options_nonce', '', true );
				//Check if a network site and networkwide settings is enabled
				if( ! is_multisite() || ( is_multisite() && ! $wpsmush_settings->settings['networkwide'] ) || ( is_multisite() && is_network_admin() ) ) {
					$this->settings_ui();
				}

				do_action('smush_settings_ui_bottom');

				//Validate Membership
				if( !$wpsmushit_admin->validate_install() ) {?>
					<div class="wp-smush-pro-for-free wp-smushit-container-left col-half float-l"><?php
						$this->wp_smush_promo();?>
					</div>
					<div class="wp-smushit-container-left col-half float-l"><?php
						$this->wp_smush_hummingbird_promo(); ?>
					</div><?php
				} ?>
			</div><?php
			$this->smush_page_footer();
		}

		/**
		 * Pro Version
		 */
		function wp_smush_promo() {
			global $wpsmushit_admin;
			$this->container_header( 'wp-smush-pro-adv', 'wp-smush-pro-promo', "READY TO LEVEL UP YOUR WEB DEVELOPMENT?" );
			$upgrade_url = add_query_arg(
				array(
				'utm_source' => 'Smush-Free',
				'utm_medium' => 'Banner',
				'utm_campaign' => 'settings-sidebar'
				),
				$wpsmushit_admin->upgrade_url
			);
			?>
			<div class="box-content">
				<p class="wp-smush-promo-content wp-smush-promo-content-2 roboto">Create amazing websites, automate your workflow, and run your business like a well-oiled machine.
				Get access to the tools that will win you more clients and help you spend less time working.
				Start a free WPMU DEV trial today.</p>
				<span class="wp-smush-pro-cta tc">
					<a href="#smush-pro-features" class="button button-cta button-green get-smush-pro-cta" rel="dialog" target="_blank"><?php esc_html_e("GET STARTED", "wp-smushit"); ?></a>
				</span>
			</div><?php
			echo "</section>";
		}

		/**
		 * HummingBird Promo
		 */
		function wp_smush_hummingbird_promo() {
			//Plugin Already Installed
			if ( class_exists( 'WP_Hummingbird' ) ) {
				return;
			}
			$this->container_header( 'wp-smush-hb-adv', 'wp-smush-hb-promo', "OH YEAH, SMUSHING ON STEROIDS!" ); ?>
			<div class="box-content">
			<span class="wp-smush-hummingbird-image tc">
					<img src="<?php echo WP_SMUSH_URL . 'assets/images/hummingbird.png'; ?>"
					     alt="<?php esc_html_e( "BOOST YOUR PERFORMANCE - HUMMINGBIRD", "wp-smushit" ); ?>">
	        </span>
			<p class="wp-smush-promo-content tc roboto">Hummingbird goes beyond Smush compression with
				minification, caching, performance monitoring and more - every millisecond counts!</p>
			<span class="wp-smush-hb-cta tc roboto-medium">
				<a href="<?php echo esc_url( "https://premium.wpmudev.org/project/wp-hummingbird/" ); ?>"
				   class="button button-cta" target="_blank">TRY HUMMINGBIRD FOR FREE</a>
			</span>
			</div><?php
			echo "</section>";
		}

		/**
		 * Outputs the Content for Bulk Smush Div
		 */
		function bulk_smush_content() {

			global $WpSmush, $wpsmushit_admin, $wpsmush_settings;

			$all_done = ( $wpsmushit_admin->smushed_count == $wpsmushit_admin->total_count ) && 0 == count( $wpsmushit_admin->resmush_ids );

			echo $this->bulk_resmush_content();
			$upgrade_url = add_query_arg(
				array(
				'utm_source' => 'Smush-Free',
				'utm_medium' => 'Banner',
				'utm_campaign' => 'try-pro-free'
				),
				$wpsmushit_admin->upgrade_url
			);

			//Check whether to show pagespeed recommendation or not
			$hide_pagespeed = get_site_option(WP_SMUSH_PREFIX . 'hide_pagespeed_suggestion');

			//If there are no images in Media Library
			if ( 0 >= $wpsmushit_admin->total_count ) { ?>
				<span class="wp-smush-no-image tc">
					<img src="<?php echo WP_SMUSH_URL . 'assets/images/smush-no-media.png'; ?>"
					     alt="<?php esc_html_e( "No attachments found - Upload some images", "wp-smushit" ); ?>">
		        </span>
				<p class="wp-smush-no-images-content tc roboto-regular"><?php printf( esc_html__( "We haven’t found any images in your %smedia library%s yet so there’s no smushing to be done! Once you upload images, reload this page and start playing!", "wp-smushit" ), '<a href="' . esc_url( admin_url( 'upload.php' ) ) . '">', '</a>' ); ?></p>
				<span class="wp-smush-upload-images tc">
				<a class="button button-cta"
				   href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>"><?php esc_html_e( "UPLOAD IMAGES", "wp-smushit" ); ?></a>
				</span><?php
			} else { ?>
				<!-- Hide All done div if there are images pending -->
				<div class="wp-smush-notice wp-smush-all-done<?php echo $all_done ? '' : ' hidden' ?>" tabindex="0">
					<i class="icon-fi-check-tick"></i><?php esc_html_e( "All images are smushed and up to date. Awesome!", "wp-smushit" ); ?>
				</div><?php
				if( !$hide_pagespeed ) {?>
                    <div class="wp-smush-pagespeed-recommendation<?php echo $all_done ? '' : ' hidden' ?>">
                        <span class="smush-recommendation-title roboto-medium"><?php esc_html_e("Still having trouble with PageSpeed tests? Give these a go…", "wp-smsuhit"); ?></span>
                        <ol class="smush-recommendation-list"><?php
                         if( !$WpSmush->validate_install() ) { ?>
                            <li class="smush-recommendation-lossy"><?php printf( esc_html__("Upgrade to Smush Pro for advanced lossy compression. %sTry pro free%s.", "wp-smushit"), '<a href="' . $upgrade_url .'" target="_blank">', '</a>' ); ?></li><?php
                         }elseif ( !$wpsmush_settings->settings['lossy'] ) {?>
                             <li class="smush-recommendation-lossy"><?php printf( esc_html__("Enable %sSuper-smush%s for advanced lossy compression to optimise images further with almost no visible drop in quality.", "wp-smushit"), '<a href="#" class="wp-smush-lossy-enable">', "</a>" ); ?></li><?php
                         }?>
                         <li class="smush-recommendation-resize"><?php printf( esc_html__("Make sure your images are the right size for your theme. %sLearn more%s.", "wp-smushit"), '<a href="'. esc_url("https://goo.gl/kCqWxS") .'" target="_blank">', '</a>' ); ?></li><?php
                         if( !$wpsmush_settings->settings['resize'] ) {
                             //Check if resize original is disabled ?>
                             <li class="smush-recommendation-resize-original"><?php printf( esc_html__("Enable %sResize Original Images%s to scale big images down to a reasonable size and save a ton of space.", "wp-smushit"), '<a href="#" class="wp-smush-resize-enable">', "</a>"); ?></li><?php
                         }
                         ?>
                        </ol>
                        <span class="dismiss-recommendation"><i class="icon-fi-cross-close"></i><?php esc_html_e("DISMISS", "wp-smushit"); ?></span>
                    </div><?php
				} ?>
				<div class="wp-smush-bulk-wrapper <?php echo $all_done ? ' hidden' : ''; ?>"><?php
				//If all the images in media library are smushed
				//Button Text
				$button_content = esc_html__( "BULK SMUSH", "wp-smushit" );

				//Show the notice only if there are remaining images and if we aren't showing a notice for resmush
				if ( $wpsmushit_admin->remaining_count > 0 ) {
					$class = count( $wpsmushit_admin->resmush_ids ) > 0 ? ' hidden' : '';
					$upgrade_url = add_query_arg(
						array(
						'utm_source' => 'Smush-Free',
						'utm_medium' => 'Banner',
						'utm_campaign' => 'yellow-bulk-smush-upsell'
						),
						$wpsmushit_admin->upgrade_url
					);
					?>
					<div class="wp-smush-notice wp-smush-remaining<?php echo $class; ?>" tabindex="0">
					    <i class="icon-fi-warning-alert"></i>
						<span class="wp-smush-notice-text"><?php
							printf( _n( "%s, you have %s%s%d%s attachment%s that needs smushing!", "%s, you have %s%s%d%s attachments%s that need smushing!", $wpsmushit_admin->remaining_count, "wp-smushit" ), $wpsmushit_admin->get_user_name(), '<strong>', '<span class="wp-smush-remaining-count">', $wpsmushit_admin->remaining_count, '</span>', '</strong>' );
							if( !$WpSmush->validate_install() && $wpsmushit_admin->remaining_count > 50 ) {
								printf( esc_html__(" %sUpgrade to Pro%s to bulk smush all your images with one click.", "wp-smushit") , '<a href="' . esc_url( $upgrade_url ). '" target="_blank" title="' . esc_html__("WP Smush Pro", "wp-smushit") . '">', '</a>' );
								esc_html_e(" Free users can smush 50 images with each click.", "wp-smushit");
							 }?>
						</span>
					</div><?php
				} ?>
				<button type="button" class="wp-smush-all wp-smush-button" title="<?php esc_html_e('Click to start Bulk Smushing images in Media Library', 'wp-smushit'); ?>"><?php echo $button_content; ?></button>
				</div><?php
				$this->progress_bar( $wpsmushit_admin );
				//Enable Super Smush
				if ( $WpSmush->validate_install() && ! $WpSmush->lossy_enabled ) { ?>
					<p class="wp-smush-enable-lossy hidden"><?php esc_html_e( "Tip: Enable Super-smush in the Settings area to get even more savings with almost no visible drop in quality.", "wp-smushit" ); ?></p><?php
				}
				$this->super_smush_promo();
			}
		}

		/**
		 * Content for showing Progress Bar
		 */
		function progress_bar( $count ) {

			//If we have resmush list, smushed_count = totalcount - resmush count, else smushed_count
//			$smushed_count = ( $resmush_count = count( $count->resmush_ids ) ) > 0 ? ( $count->total_count - ( $count->remaining_count + $resmush_count ) ) : $count->smushed_count;
			// calculate %ages, avoid divide by zero error with no attachments

			if ( $count->total_count > 0 && $count->smushed_count > 0 ) {
				$smushed_pc = $count->smushed_count / $count->total_count * 100;
			} else {
				$smushed_pc = 0;
			}
			?>
			<div class="wp-smush-bulk-progress-bar-wrapper hidden">
			<p class="wp-smush-bulk-active roboto-medium"><?php printf( esc_html__( "%sBulk smush is currently running.%s You need to keep this page open for the process to complete.", "wp-smushit" ), '<strong>', '</strong>' ); ?></p>
			<div class="wp-smush-progress-wrap">
			    <i class="icon-fi-loader"></i>
				<div class="wp-smush-progress-bar-wrap">
					<div class="wp-smush-progress-bar">
						<div class="wp-smush-progress-inner" style="width: <?php echo $smushed_pc; ?>%;">
						</div>
					</div>
				</div>
			</div>
			<div class="wp-smush-count tc">
                <?php printf( esc_html__( "%s%d%s of your media attachments have been smushed." ), '<span class="wp-smush-images-percent">', $smushed_pc, '</span>%' ); ?>
            </div>
            <div class="smush-cancel-button-wrapper">
                <button type="button"
                        class="button button-grey wp-smush-cancel-bulk" tooltip="<?php esc_html_e( "Stop current bulk smush process.", "wp-smushit"); ?>"><?php esc_html_e( "CANCEL", "wp-smushit" ); ?></button>
            </div>
			</div>
			<div class="smush-final-log notice notice-warning inline hidden"></div><?php
		}

		/**
		 * Shows a option to ignore the Image ids which can be resmushed while bulk smushing
		 *
		 * @param int $count Resmush + Unsmushed Image count
		 */
		function bulk_resmush_content( $count = false, $show = false ) {

			global $wpsmushit_admin;

			//If we already have count, don't fetch it
			if ( false === $count ) {
				//If we have the resmush ids list, Show Resmush notice and button
				if ( $resmush_ids = get_option( "wp-smush-resmush-list" ) ) {

					$count = count( $resmush_ids );

					//Whether to show the remaining re-smush notice
					$show = $count > 0 ? true : false;

					//Get the Actual remainaing count
					if ( ! isset( $wpsmushit_admin->remaining_count ) ) {
						$wpsmushit_admin->setup_global_stats();
					}

					$count = $wpsmushit_admin->remaining_count;
				}
			}
			//Show only if we have any images to ber resmushed
			if ( $show ) {
				return '<div class="wp-smush-notice wp-smush-resmush-notice wp-smush-remaining" tabindex="0">
						<i class="icon-fi-warning-alert"></i>
						<span class="wp-smush-notice-text">' . sprintf( _n( "%s, you have %s%s%d%s attachment%s that needs re-compressing!", "%s, you have %s%s%d%s attachments%s that need re-compressing!", $count, "wp-smushit" ), $wpsmushit_admin->get_user_name(), '<strong>', '<span class="wp-smush-remaining-count">', $count, '</span>', '</strong>' ) . '</span>
						<button class="button button-grey button-small wp-smush-skip-resmush" title="' . esc_html__("Skip re-smushing the images", "wp-smushit") . '">' . esc_html__( "Skip", "wp-smushit" ) . '</button>
	                </div>';
			}
		}

		/**
		 * Displays a admin notice for settings update
		 */
		function settings_updated() {
			global $wpsmushit_admin, $wpsmush_settings;

			//Check if Networkwide settings are enabled, Do not show settings updated message
			if( is_multisite() && $wpsmush_settings->settings['networkwide'] && !is_network_admin() ) {
				return;
			}

			//Show Setttings Saved message
			if ( 1 == $wpsmush_settings->get_setting( 'wp-smush-settings_updated', false ) ) {

				//Default message
				$message = esc_html__( "Your settings have been updated!", "wp-smushit" );

				//Additonal message if we got work to do!
				$resmush_count = is_array( $wpsmushit_admin->resmush_ids ) && count( $wpsmushit_admin->resmush_ids ) > 0;
				$smush_count   = is_array( $wpsmushit_admin->remaining_count ) && $wpsmushit_admin->remaining_count > 0;

				if ( $smush_count || $resmush_count ) {
					$message .= ' ' . sprintf( esc_html__( "You have images that need smushing. %sBulk smush now!%s", "wp-smushit" ), '<a href="#" class="wp-smush-trigger-bulk">', '</a>' );
				}
				echo '<div class="wp-smush-notice wp-smush-settings-updated"><i class="icon-fi-check-tick"></i> ' . $message . '
				<i class="icon-fi-close"></i>
				</div>';

				//Remove the option
				$wpsmush_settings->delete_setting( 'wp-smush-settings_updated' );
			}
		}

		/**
		 * Prints out the page header for Bulk Smush Page
		 */
		function smush_page_header() {
			global $WpSmush, $wpsmushit_admin, $wpsmush_s3;

			//Include Shared UI
			require_once WP_SMUSH_DIR . 'assets/shared-ui/plugin-ui.php';

			if( $wpsmushit_admin->remaining_count == 0 || $wpsmushit_admin->smushed_count == 0 ) {
				//Initialize global Stats
				$wpsmushit_admin->setup_global_stats();
			}

			//Page Heading for Free and Pro Version
			$page_heading = $WpSmush->validate_install() ? esc_html__( 'WP Smush Pro', 'wp-smushit' ) : esc_html__( 'WP Smush', 'wp-smushit' );

			$auto_smush_message = $WpSmush->is_auto_smush_enabled() ? sprintf( esc_html__( "Automatic smushing is %senabled%s. Newly uploaded images will be automagically compressed.", "wp-smushit" ), '<span class="wp-smush-auto-enabled">', '</span>' ) : sprintf( esc_html__( "Automatic smushing is %sdisabled%s. Newly uploaded images will need to be manually smushed.", "wp-smushit" ), '<span class="wp-smush-auto-disabled">', '</span>' );

			//User API check, and display a message if not valid
			$user_validation = $this->get_user_validation_message();

			//Re-Check images notice
			$recheck_notice = $this->get_recheck_message();

			echo '<div class="smush-page-wrap">
				<section id="header">
					<div class="wp-smush-page-header">
						<h1 class="wp-smush-page-heading">' . $page_heading . '</h1>
						<div class="wp-smush-auto-message roboto-regular">' . $auto_smush_message . '</div>
					</div>' .
					$user_validation .
					$recheck_notice .
				'</section>';

			//Check for any stored API message and show it
			$this->show_api_message();

			//Check if settings were updated and shoe a notice
			$this->settings_updated();

			//Show S3 integration message, if user hasn't enabled it
			if( is_object( $wpsmush_s3 ) && method_exists( $wpsmush_s3, 's3_support_required_notice') ) {
			    $wpsmush_s3->s3_support_required_notice();
			}

			echo '<div class="row wp-smushit-container-wrap">';
		}

		/**
		 * Content of the Install/ Upgrade notice based on Free or Pro version
		 */
		function installation_notice() {
			global $wpsmushit_admin;

			//Whether New/Existing Installation
			$install_type = get_site_option('wp-smush-install-type', false );

			if( !$install_type ) {
				$install_type = $wpsmushit_admin->smushed_count > 0 ? 'existing' : 'new';
				update_site_option( 'wp-smush-install-type', $install_type );
			}

			if ( 'new' == $install_type  ) {
				$notice_heading = esc_html__( "Thanks for installing Smush. We hope you like it!", "wp-smushit" );
				$notice_content = esc_html__( "And hey, if you do, you can join WPMU DEV for a free 30 day trial and get access to even more features!", "wp-smushit" );
				$button_content = esc_html__( "Try Smush Pro Free", "wp-smushit" );
			} else {
				$notice_heading = esc_html__( "Thanks for upgrading Smush!", "wp-smushit" );
				$notice_content = 'Did you know she has secret super powers? Yes, she can super-smush images for double the savings, store original images, and bulk smush thousands of images in one go. Get started with a free WPMU DEV trial to access these advanced features.';
				$button_content = esc_html__( "Try Smush Pro Free", "wp-smushit" );
			}
			$upgrade_url = add_query_arg(
				array(
				'utm_source' => 'Smush-Free',
				'utm_medium' => 'Banner',
				'utm_campaign' => 'try-pro-free'
				),
				$wpsmushit_admin->upgrade_url
			);?>
			<div class="notice smush-notice" style="display: none;">
				<div class="smush-notice-logo"><span></span></div>
				<div
					class="smush-notice-message<?php echo 'new' == $install_type ? ' wp-smush-fresh' : ' wp-smush-existing'; ?>">
					<strong><?php echo $notice_heading; ?></strong>
					<?php echo $notice_content; ?>
				</div>
				<div class="smush-notice-cta">
					<a href="<?php echo esc_url( $upgrade_url ); ?>" class="smush-notice-act button-primary" target="_blank">
					<?php echo $button_content; ?>
					</a>
					<button class="smush-notice-dismiss smush-dismiss-welcome" data-msg="<?php esc_html_e( 'Saving', 'wp-smushit'); ?>"><?php esc_html_e( 'Dismiss', "wp-smushit" ); ?></button>
				</div>
			</div><?php
			//Notice CSS
			wp_enqueue_style('wp-smushit-notice-css');
			//Notice JS
			wp_enqueue_script('wp-smushit-notice-js', '', array(), '', true );
		}

		/**
		 * Super Smush promo content
		 */
		function super_smush_promo() {
			global $WpSmush, $wpsmushit_admin;
			if ( $WpSmush->validate_install() ) {
				return;
			}
			$upgrade_url = add_query_arg(
				array(
				'utm_source' => 'Smush-Free',
				'utm_medium' => 'Banner',
				'utm_campaign' => 'smush-lady-upgrade'
				),
				$wpsmushit_admin->upgrade_url
			); ?>
			<div class="wp-smush-super-smush-promo">
			    <div class="wp-smush-super-smush-content-wrapper">
                    <div class="wp-smush-super-smush-content"><?php
                        printf( esc_html__("Did you know WP Smush Pro delivers up to 2x better compression, allows you to smush your originals and removes any bulk smushing limits? – %sTry it absolutely FREE%s", "wp-smushit"), '<a href="' . esc_url( $upgrade_url ). '" target="_blank" title="' . esc_html__("Try WP Smush Pro for FREE", "wp-smushit") . '">', '</a>' ); ?>
                    </div>
				</div>
			</div>
			<?php
		}

		/**
		 * Super Smush promo content
		 */
		function super_smush_promo_post_settings() {
			global $WpSmush, $wpsmushit_admin;
			if ( $WpSmush->validate_install() ) {
				return;
			}
			$upgrade_url = add_query_arg(
				array(
				'utm_source' => 'Smush-Free',
				'utm_medium' => 'Banner',
				'utm_campaign' => 'smush-advanced-settings-upsell'
				),
				$wpsmushit_admin->upgrade_url
			); ?>
			<div class="wp-smush-super-smush-promo">
				<div class="wp-smush-super-smush-content"><?php
					printf( esc_html__("Smush Pro gives you all these extra settings and absolutely no limits on smushing your images! Did we mention Smush Pro also gives you up to 2x better compression too? – %sTry it all free with a WPMU DEV membership today!%s", "wp-smushit"), '<a href="' . esc_url( $upgrade_url ). '" target="_blank" title="' . esc_html__("Try WP Smush Pro for FREE", "wp-smushit") . '">', '</a>' ); ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Prints Out the page Footer
		 */
		function smush_page_footer() {
			echo '</div><!-- End of Container wrap -->
			</div> <!-- End of div wrap -->';
		}

		/**
		* Returns a Warning message if API key is not validated
		*
		* @return string Warning Message to be displayed on Bulk Smush Page
		*
		*/
		function get_user_validation_message( $notice = false ) {
			$notice_class = $notice ? ' notice' : '';
			$wpmu_contact = sprintf( '<a href="%s" target="_blank">', esc_url("https://premium.wpmudev.org/contact") );
			$attr_message = esc_html__("Validating..", "wp-smushit");
			$recheck_link = '<a href="#" id="wp-smush-revalidate-member" data-message="%s">';
			$message = sprintf( esc_html__( "It looks like Smush couldn’t verify your WPMU DEV membership so Pro features have been disabled for now. If you think this is an error, run a %sre-check%s or get in touch with our %ssupport team%s.", "wp-smushit"), $recheck_link, '</a>', $wpmu_contact, '</a>' ) ;
			$content = sprintf( '<div id="wp-smush-invalid-member" data-message="%s" class="hidden' . $notice_class . '"><div class="message">%s</div></div>', $attr_message, $message );
			return $content;
		}

		/**
		*
		* @param $configure_screen
		*
		* @return string
		*
		*/
		function save_button( $configure_screen = false ) {
			$div_end = '';
			//Close wrapper div
			if( is_multisite() && is_network_admin() ) {
				$div_end .= "</div>";
			}

			$div_end .=
			'<span class="wp-smush-submit-wrap">
				<input type="submit" id="wp-smush-save-settings" class="button button-grey"
				       value="' . esc_html__( 'UPDATE SETTINGS', 'wp-smushit' ) . '">
		        <span class="spinner"></span>
		        <span class="smush-submit-note">' . esc_html__( "Smush will automatically check for any images that need re-smushing.", "wp-smushit") . '</span>
		        </span>
			</form>';

			//For Configuration screen we need to show the advanced settings in single box
			if ( ! $configure_screen ) {
				$div_end .= '</div><!-- Box Content -->
					</section><!-- Main Section -->';
			}
			return $div_end;
		}

		function get_recheck_message() {
			global $wpsmush_settings;
			//Return if not multisite, or on network settings page, Netowrkwide settings is disabled
			if( ! is_multisite() || is_network_admin() || ! $wpsmush_settings->settings['networkwide'] ) {
				return;
			}

			//Check the last settings stored in db
			$run_recheck = get_site_option( WP_SMUSH_PREFIX . 'run_recheck', false );

			//If not same, Display notice
			if( !$run_recheck ) {
				return;
			}
			$message = '<div class="wp-smush-notice wp-smush-re-check-message">' . esc_html__( "Smush settings were updated, performing a quick scan to check if any of the images need to be Smushed again.", "wp-smushit") . '<i class="icon-fi-close"></i></div>';

			return $message;
		}

		/**
        * Prints all the registererd image sizes, to be selected/unselected for smushing
        *
        * @param string $name
        */
		function image_sizes( $name = '' ) {
            if( empty( $name ) || 'auto' != $name ) {
                return;
            }
            global $wpsmushit_admin, $wpsmush_settings;
            //Additional Image sizes
            $image_sizes = $wpsmush_settings->get_setting( WP_SMUSH_PREFIX . 'image_sizes', false );
            $sizes = $wpsmushit_admin->image_dimensions();
            if( !empty( $sizes ) ) { ?>
                <!-- List of image sizes recognised by WP Smush -->
                <div class="wp-smush-image-size-list">
                    <span id="wp-smush-auto-desc"><?php printf( esc_html__("Every time you upload an image to your site, WordPress generates a resized version of that image for every default and/or custom image size that your theme has registered. This means there are multiple versions of your images in your media library.%sChoose the images size/s below that you would like optimized:%s", "wp-smushit"), "<br /> <br />", "<br />"); ?></span><?php
                    foreach ( $sizes as $size_k => $size ) {
                        //If image sizes array isn't set, mark all checked ( Default Values )
                        if ( false === $image_sizes ) {
                            $checked = true;
                        }else{
                            $checked = is_array( $image_sizes ) ? in_array( $size_k, $image_sizes ) : false;
                        } ?>
                        <label>
                            <input type="checkbox" id="wp-smush-size-<?php echo $size_k; ?>" <?php checked( $checked, true ); ?> name="wp-smush-image_sizes[]" value="<?php echo $size_k; ?>"><?php
                            if( isset( $size['width'], $size['height'] ) ) {
                                echo $size_k . " (" . $size['width'] . "x" . $size['height'] . ") ";
                            } ?>
                        </label><?php
                    } ?>
                </div><?php
            }

        }

        /**
        * Prints Dimensions required for Resizing
        *
        * @param string $name
        * @param string $class_prefix, To avoid element id repetition on settings page
        *
        * @param int $setting_status
         */
        function resize_settings( $name = '', $class_prefix = '' ) {
            if( empty( $name ) || 'resize' != $name ) {
                return;
            }
            global $wpsmush_settings, $wpsmushit_admin;
            //Dimensions
            $resize_sizes = $wpsmush_settings->get_setting( WP_SMUSH_PREFIX . 'resize_sizes', array( 'width' => '', 'height' => '' ) );
            //Get max. dimesnions
            $max_sizes = $wpsmushit_admin->get_max_image_dimensions();

            $setting_status = !empty( $wpsmush_settings->settings['resize'] ) ? $wpsmush_settings->settings['resize'] : 0;

            $prefix = !empty( $class_prefix ) ? $class_prefix : WP_SMUSH_PREFIX;

            //Placeholder width and Height
            $p_width = $p_height = 2048; ?>
            <div class="wp-smush-resize-settings-wrap<?php echo $setting_status ? '' : ' hidden'?>">
                <label class="resize-width-label" aria-labelledby="<?php echo $prefix; ?>label-max-width" for="<?php echo $prefix . $name . '_width'; ?>"><span class = "label-text" id="<?php echo $prefix; ?>label-max-width"><?php esc_html_e("Max width", "wp-smushit"); ?></span>
                    <input aria-required="true" type="text" aria-describedby="<?php echo $prefix; ?>wp-smush-resize-note" id="<?php echo $prefix . $name . '_width'; ?>" class="wp-smush-resize-input" value="<?php echo isset( $resize_sizes['width'] ) && '' != $resize_sizes['width'] ? $resize_sizes['width'] : $p_width; ?>" name="<?php echo WP_SMUSH_PREFIX . $name . '_width'; ?>" tabindex="0" width=100 /> px
                </label>
                <label class="resize-height-label" aria-labelledby="<?php echo $prefix; ?>label-max-height" for = "<?php echo $prefix . $name . '_height'; ?>"><span class = "label-text" id="<?php echo $prefix; ?>label-max-height"><?php esc_html_e("Max height", "wp-smushit"); ?></span>
                    <input aria-required="true" type="text" aria-describedby="<?php echo $prefix; ?>wp-smush-resize-note" id="<?php echo $prefix . $name . '_height'; ?>" class="wp-smush-resize-input" value="<?php echo isset( $resize_sizes['height'] ) && '' != $resize_sizes['height'] ? $resize_sizes['height'] : $p_height; ?>" name="<?php echo WP_SMUSH_PREFIX . $name . '_height'; ?>" tabindex="0" width=100 /> px
                </label>
                <div class="wp-smush-resize-note" id="<?php echo $prefix; ?>wp-smush-resize-note"><?php printf( esc_html__("Currently, your largest image size is set at %s%dpx wide %s %dpx high%s.", "wp-smushit"), '<strong>', $max_sizes['width'], '&times;', $max_sizes['height'], '</strong>' ); ?></div>
                <div class="wp-smush-settings-info wp-smush-size-info wp-smush-update-width hidden" tabindex="0"><?php esc_html_e( "Just to let you know, the width you've entered is less than your largest image and may result in pixelation.", "wp-smushit" ); ?></div>
                <div class="wp-smush-settings-info wp-smush-size-info wp-smush-update-height hidden" tabindex="0"><?php esc_html_e( "Just to let you know, the height you’ve entered is less than your largest image and may result in pixelation.", "wp-smushit" ); ?></div>
            </div><?php
        }

        /**
        * Prints Resize, Smush Original, and Backup Settings
        *
        * @param string $name Name of the current setting being processed
        */
        function full_size_options( $name = '', $section = '' ) {
		    if( 'original' != $name && 'resize' != $name ) {
		        return;
		    }
		    global $WpSmush, $wpsmushit_admin, $wpsmush_settings;
		    foreach( $this->setting_group as $name ) {
		        //Do not print Smush Original, Backup for free users
		        if( !$WpSmush->validate_install() ) {
		            if( 'resize' == $name && !empty( $section ) ) {
		             continue;
		            }elseif( empty( $section ) && 'resize' != $name ) {
		                continue;
		            }
		        }
		        $setting_val = $wpsmush_settings->settings[$name];
		        //Turn off settings for free users
                if( !in_array( $name, $wpsmushit_admin->basic_features ) && !$WpSmush->validate_install() ) {
                    $setting_val = 0;
                }
		        ?>
		        <div class="smush-sub-setting-wrapper">
                     <span class="toggle float-l">
                        <input type="checkbox" class="toggle-checkbox"
                               id="<?php echo WP_SMUSH_PREFIX . $name ; ?>" <?php checked( $setting_val, 1, true ); ?>
                               value="1"
                               name="<?php echo WP_SMUSH_PREFIX . $name; ?>" aria-describedby="<?php echo WP_SMUSH_PREFIX . $name . "-desc" ;?>">
                        <label class="toggle-label <?php echo WP_SMUSH_PREFIX . $name ; ?>-label" for="<?php echo WP_SMUSH_PREFIX . $name; ?>" aria-hidden="true"></label>
                    </span>
                    <div class="column-right-content">
                        <label class="inline-label" for="<?php echo WP_SMUSH_PREFIX . $name; ?>">
                            <span class="wp-smush-setting-label"><?php echo $wpsmushit_admin->settings[ $name ]['label']; ?></span><br/>
                        </label>
                        <span class="wp-smush-setting-desc" id="<?php echo WP_SMUSH_PREFIX . $name . "-desc" ;?>"><?php echo $wpsmushit_admin->settings[ $name ]['desc']; ?></span><br/><?php
                        $this->resize_settings( $name );?>
                    </div>
                </div><?php
		    }
        }

        /**
        *
        * @param string $setting_key
        */
        function settings_desc( $setting_key = '' ) {
            if( empty( $setting_key ) || !in_array( $setting_key, array( 'keep_exif', 'png_to_jpg', 's3')) ) {
                return;
            } ?>
            <div class="column-right-content-description" id="<?php echo WP_SMUSH_PREFIX . $setting_key . "-desc"; ?>"><?php
                switch ( $setting_key ) {

                    case 'keep_exif':
                        esc_html_e("Note: This data, called EXIF, adds to the size of the image. While this information might be important to photographers, it’s unnecessary for most users and safe to remove.", "wp-smushit");
                        break;
                    case 'png_to_jpg':
                        esc_html_e("Note: Any PNGs with transparency will be ignored. Smush will only convert PNGs if it results in a smaller file size. The resulting file will have a new filename and extension (JPEG), and any hard-coded URLs on your site that contain the original PNG filename will need to be updated.", "wp-smushit");
                        break;
                    case 's3':
                        esc_html_e("Note: For this process to happen automatically you need automatic smushing enabled.", "wp-smushit");
                        break;
                    case 'default':
                        break;
                } ?>
            </div><?php
        }

        function smush_pro_modal() {
            //Header Of the Box
            global $wpsmushit_admin;
            //If we have any savings
				$upgrade_url = add_query_arg(
					array(
						'utm_source' => 'Smush-Free',
						'utm_medium' => 'Banner',
						'utm_campaign'=> 'pro-only-stats'
					),
					$wpsmushit_admin->upgrade_url
				);?>
			<dialog id="smush-pro-features" title='<?php esc_html_e( "GET SMUSH PRO", "wp-smushit" ); ?><a href="<?php echo $upgrade_url; ?>" class="smush-pro-link button button-small button-cta button-green" target="_blank"><?php esc_html_e("LEARN MORE", "wp-smushit"); ?></a>' class="wp-smush-get-pro small">
				<p class="smush-pro-features-message end"><?php esc_html_e( 'Here’s what you’ll get by uprading to Smush Pro.', "wp-smushit" ); ?></p>
				<ul class="smush-pro-features">
				    <li class="smush-pro-feature-row">
				        <div class="smush-pro-feature-title">
				        <?php esc_html_e("Double the compression", "wp-smushit"); ?></div>
				        <div class="smush-pro-feature-desc"><?php esc_html_e("Optimize images 2x more than regular smushing and with no visible loss in quality using Smush’s intelligent multi-pass lossy compression.", "wp-smushit"); ?></div>
				    </li>
				    <li class="smush-pro-feature-row">
				        <div class="smush-pro-feature-title">
				        <?php esc_html_e("No limits", "wp-smushit"); ?></div>
				        <div class="smush-pro-feature-desc"><?php esc_html_e("The free version allows you to Smush up to 1Mb images, and 50 at a time. The Pro version releases all those limits so you can smush all the things.", "wp-smushit"); ?></div>
				    </li>
				    <li class="smush-pro-feature-row">
				        <div class="smush-pro-feature-title">
				        <?php esc_html_e("Include your originals", "wp-smushit"); ?></div>
				        <div class="smush-pro-feature-desc"><?php esc_html_e("The free version of Smush only compresses your automatically generated image thumbnails. With Pro you can compress your original size images too - in case you want to use them in your theme.", "wp-smushit"); ?></div>
				    </li>
				    <li class="smush-pro-feature-row">
				        <div class="smush-pro-feature-title">
				        <?php esc_html_e("Convert PNGs to JPEGs", "wp-smushit"); ?></div>
				        <div class="smush-pro-feature-desc"><?php esc_html_e("If any of your non-transparent PNGs can be made smaller by converting to JPEG, Smush will automatically convert them to JPEGs so they load faster for your visitors.", "wp-smushit"); ?></div>
				    </li>
				    <li class="smush-pro-feature-row">
				        <div class="smush-pro-feature-title">
				        <?php esc_html_e("Integration with NextGen Gallery", "wp-smushit"); ?></div>
				        <div class="smush-pro-feature-desc"><?php esc_html_e("Using the NextGen Gallery plugin? The Pro version allows you to compress images directly through NextGen Gallery’s settings.", "wp-smushit"); ?></div>
				    </li>
				</ul>
				<p class="smush-pro-upsell-text"><?php esc_html_e("Get all of this including 200% faster smushing, Amazon S3 support and heaps more as part of a WPMU DEV membership.", "wp-smushit"); ?></p>
				<div class="smush-pro-link-wrap"><a href="<?php echo $upgrade_url; ?>" class="smush-pro-link button button-cta button-green" target="_blank"><?php esc_html_e("LEARN MORE", "wp-smushit"); ?></a></div>
			</dialog><?php
        }

        /**
        * Display a stored API message
        * @return null
        */
        function show_api_message() {

            //Do not show message for any other users
            if( !is_network_admin() && !is_super_admin() ) {
                return null;
            }

            $message_icon_class = '';
            $api_message = get_site_option( WP_SMUSH_PREFIX . 'api_message', array() );
            $api_message = current( $api_message );

            //Return if the API message is not set or user dismissed it earlier
            if( empty( $api_message ) || !is_array( $api_message ) || $api_message['status'] != 'show' ) {
                return null;
            }

            $message = !empty( $api_message['message'] ) ? $api_message['message'] : '';
            $message_type = is_array( $api_message ) && !empty( $api_message['type'] ) ? $api_message['type'] : 'info';

            if( 'warning' == $message_type ) {
                $message_icon_class = "icon-fi-warning-alert";
            }else if( 'info' == $message_type ) {
                $message_icon_class = "icon-fi-info";
            }
            echo '<div class="wp-smush-notice wp-smush-api-message '. $message_type .'"><i class="'. $message_icon_class .'"></i>' . $message . '<i class="icon-fi-close"></i></div>';
        }
    }
    global $wpsmush_bulkui;
	$wpsmush_bulkui = new WpSmushBulkUi();
}