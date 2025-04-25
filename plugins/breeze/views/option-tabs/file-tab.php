<?php
/**
 * Basic tab
 */
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

set_as_network_screen();

$is_custom = false;
if ( ( ! defined( 'WP_NETWORK_ADMIN' ) || ( defined( 'WP_NETWORK_ADMIN' ) && false === WP_NETWORK_ADMIN ) ) && is_multisite() ) {
	$get_inherit = get_option( 'breeze_inherit_settings', '1' );
	$is_custom   = filter_var( $get_inherit, FILTER_VALIDATE_BOOLEAN );
}

$options                      = breeze_get_option( 'file_settings', true );
$excluded_css_check           = true;
$excluded_js_check            = true;
$excluded_css_check_extension = true;
$excluded_js_check_extension  = true;
$excluded_url_list            = true;

$icon = BREEZE_PLUGIN_URL . 'assets/images/file-active.png';
?>
<form data-section="file">
	<?php if ( true === $is_custom ) { ?>
        <div class="br-overlay-disable"><?php _e( 'Settings are inherited', 'breeze' ); ?></div>
	<?php } ?>
    <section>
        <div class="br-section-title">
            <img src="<?php echo $icon; ?>"/>
			<?php _e( 'FILE OPTIMIZATION', 'breeze' ); ?>
        </div>

        <div class="br-option-group">
            <span class="section-title"><?php _e( 'HTML Settings', 'breeze' ); ?></span>
            <!-- START OPTION -->
            <div class="br-option-item br-top">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'HTML Minify', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
					<?php
					$basic_value = isset( $options['breeze-minify-html'] ) ? filter_var( $options['breeze-minify-html'], FILTER_VALIDATE_BOOLEAN ) : false;
					$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-minify-html'], '1', false ) : '';
					?>
                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input id="minification-html" name="minification-html" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>

                    <div class="br-note">
                        <p>
							<?php

							_e( 'Minifying HTML removes whitespace and comments to reduce the size.', 'breeze' );
							?>
                        </p>

                        <p class="br-important">
							<?php
							echo '<strong>';
							_e( 'Important: ', 'breeze' );
							echo '</strong>';
							_e( 'We recommend testing minification on a staging website before deploying it on a live website. ', 'breeze' );
							echo '<br/>';
							_e( 'Minification is known to cause issues on the frontend.', 'breeze' );
							?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->
        </div><!-- END GROUP -->

        <!-- START GROUP -->
        <div class="br-option-group">
            <span class="section-title"><?php _e( 'CSS Settings', 'breeze' ); ?></span>
            <!-- START OPTION -->
            <div class="br-option-item br-top">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'CSS Minify', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
					<?php
					$basic_value = isset( $options['breeze-minify-css'] ) ? filter_var( $options['breeze-minify-css'], FILTER_VALIDATE_BOOLEAN ) : false;
					$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-minify-css'], '1', false ) : '';

					$css_minify_state = true;
					if ( empty( $is_enabled ) ) {
						$css_minify_state = false;
					}

					?>
                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input id="minification-css" name="minification-css" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>

                    <div class="br-note">
                        <p>
							<?php _e( 'Minify CSS removes whitespace and comments to reduce the file size.', 'breeze' ); ?>
                        </p>
                    </div>

					<?php
					$is_font_display = '';
					if ( empty( $is_enabled ) ) {
						$is_font_display = ' style="display: none"';
					}

					$basic_value = isset( $options['breeze-font-display-swap'] ) ? filter_var( $options['breeze-font-display-swap'], FILTER_VALIDATE_BOOLEAN ) : false;
					$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-font-display-swap'], '1', false ) : '';
					?>
                    <div id="font-display-swap" <?php echo $is_font_display; ?>>
                        <div class="on-off-checkbox">
                            <label class="br-switcher">
                                <input id="font-display" name="font-display" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
                                <div class="br-see-state">
                                </div>
                            </label><br>
                        </div>

                        <p>
							<?php _e( 'Font remain visible during load', 'breeze' ); ?><br/>
                        </p>
                    </div>

                    <p class="br-important">
						<?php
						echo '<strong>';
						_e( 'Important: ', 'breeze' );
						echo '</strong>';
						_e( 'We recommend testing minification on a staging website before deploying it on a live website. ', 'breeze' );
						echo '<br/>';
						_e( 'Minification is known to cause issues on the frontend.', 'breeze' );
						?>
                    </p>
                </div>
            </div>
            <!-- END OPTION -->

            <!-- START OPTION -->
			<?php
			$basic_value        = isset( $options['breeze-include-inline-css'] ) ? filter_var( $options['breeze-include-inline-css'], FILTER_VALIDATE_BOOLEAN ) : false;
			$is_enabled         = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-include-inline-css'], '1', false ) : '';
			$disable_inline_css = '';
			$disable_overlay    = '';

			if ( false === $css_minify_state ) {
				//$disable_inline_css = 'disabled="disabled"';
				$disable_overlay = ' br-apply-disable';
			}
			?>
            <div class="br-option-item<?php echo $disable_overlay; ?>">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Include Inline CSS', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">

                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input id="include-inline-css" name="include-inline-css" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?> <?php echo $disable_inline_css; ?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>

                    <div class="br-note">
                        <p>
							<?php _e( 'Minify Inline CSS removes whitespace and create seprate cache file for inline CSS.', 'breeze' ); ?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->

            <!-- START OPTION -->
			<?php
			$basic_value = isset( $options['breeze-group-css'] ) ? filter_var( $options['breeze-group-css'], FILTER_VALIDATE_BOOLEAN ) : false;
			$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-group-css'], '1', false ) : '';

			$disable_group_css = '';
			$disable_overlay   = '';

			if ( false === $css_minify_state ) {
				//$disable_group_css = 'disabled="disabled"';
				$disable_overlay = ' br-apply-disable';
			}
			?>
            <div class="br-option-item<?php echo $disable_overlay; ?>">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Combine CSS', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">

                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input id="group-css" name="group-css" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?> <?php echo $disable_group_css; ?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>

                    <div class="br-note">
                        <p>
							<?php _e( 'Combine CSS merges all your minified files into a single file, reducing HTTP requests.', 'breeze' ); ?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->

            <!-- START OPTION -->
			<?php

			if ( isset( $options['breeze-exclude-css'] ) && ! empty( $options['breeze-exclude-css'] ) ) {
				$excluded_css_check = breeze_validate_urls( $options['breeze-exclude-css'] );
				if ( true === $excluded_css_check ) {
					$excluded_css_check_extension = breeze_validate_the_right_extension( $options['breeze-exclude-css'], 'css' );
				}
			}

			$css_output = '';
			if ( ! empty( $options['breeze-exclude-css'] ) ) {
				$output     = implode( "\n", $options['breeze-exclude-css'] );
				$css_output = esc_textarea( $output );
			}
			$disable_overlay = '';
			if ( false === $css_minify_state ) {
				$disable_overlay = ' br-apply-disable';
			}
			?>
            <div class="br-option-item<?php echo $disable_overlay; ?>">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Exclude CSS', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">

					<textarea cols="100" rows="7" id="exclude-css" name="exclude-css"
                              placeholder="Exclude CSS on the basis of the folder&#10;https://demo/wp-content/plugins/some-plugin/assets/css/demo(.*)&#10;&#10;Exclude CSS on the basis of the file name&#10;https://demo/wp-content/plugins/some-plugin/assets/css/demo_1/css_random_someplugin_(.*).css"><?php echo $css_output; ?></textarea>
                    <div class="br-note">
                        <p>
							<?php

							_e( 'Use this option to exclude CSS files from Minification and Grouping. Enter the URLs of CSS files on each line.', 'breeze' );
							?>
                        </p>
                        <p class="br-notice">
							<?php if ( false === $excluded_css_check_extension ) { ?>
								<?php _e( 'One (or more) URL is incorrect. Please confirm that all URLs have the .css extension', 'breeze' ); ?>
							<?php } ?>
							<?php if ( false === $excluded_css_check ) { ?>
								<?php _e( 'One (or more) URL is invalid. Please check and correct the entry.', 'breeze' ); ?>
							<?php } ?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->
        </div><!-- END GROUP -->

        <!-- START GROUP -->
        <div class="br-option-group">
            <span class="section-title"><?php _e( 'JS Settings', 'breeze' ); ?></span>
            <!-- START OPTION -->
            <div class="br-option-item br-top">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'JS Minify', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
					<?php
					$basic_value = isset( $options['breeze-minify-js'] ) ? filter_var( $options['breeze-minify-js'], FILTER_VALIDATE_BOOLEAN ) : false;
					$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-minify-js'], '1', false ) : '';

					$js_minify_state = true;
					if ( empty( $is_enabled ) ) {
						$js_minify_state = false;
					}
					?>
                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input id="minification-js" name="minification-js" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>

                    <div class="br-note">
                        <p>
							<?php _e( 'Minify JavaScript removes whitespace and comments to reduce the file size.', 'breeze' ); ?>
                        </p>

                        <p class="br-important">
							<?php
							echo '<strong>';
							_e( 'Important: ', 'breeze' );
							echo '</strong>';
							_e( 'We recommend testing minification on a staging website before deploying it on a live website. ', 'breeze' );
							echo '<br/>';
							_e( 'Minification is known to cause issues on the frontend.', 'breeze' );
							?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->

            <!-- START OPTION -->
			<?php
			$basic_value = isset( $options['breeze-include-inline-js'] ) ? filter_var( $options['breeze-include-inline-js'], FILTER_VALIDATE_BOOLEAN ) : false;
			$is_enabled  = ( isset( $basic_value ) && true === $basic_value ) ? checked( $options['breeze-include-inline-js'], '1', false ) : '';

			$js_inline_minify = true;
			if ( empty( $is_enabled ) ) {
				$js_inline_minify = false;
			}

			$disable_inline_js = '';
			$disable_overlay   = '';
			if ( false === $js_minify_state ) {
				//$disable_inline_js = 'disabled="disabled"';
				$disable_overlay = ' br-apply-disable';
			}
			?>
            <div class="br-option-item<?php echo $disable_overlay; ?>">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Include Inline JS', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">

                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input id="include-inline-js" name="include-inline-js" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?> <?php echo $disable_inline_js; ?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>

                    <div class="br-note">
                        <p>
							<?php _e( 'Minify Inline JS removes whitespace and create seprate cache file for inline JS.', 'breeze' ); ?>

                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->

            <!-- START OPTION -->
			<?php
			$combine_js_is = isset( $options['breeze-group-js'] ) ? filter_var( $options['breeze-group-js'], FILTER_VALIDATE_BOOLEAN ) : false;
			$is_enabled    = ( isset( $combine_js_is ) && true === $combine_js_is ) ? checked( $options['breeze-group-js'], '1', false ) : '';

			$disable_group_js = '';
			$disable_overlay  = '';

			if ( false === $js_minify_state ) {
				//$disable_group_js = 'disabled="disabled"';
				$disable_overlay = ' br-apply-disable';
			}
			?>
            <div class="br-option-item<?php echo $disable_overlay; ?>">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Combine JS', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input id="group-js" name="group-js" type="checkbox" class="br-box" value="1" <?php echo $is_enabled; ?> <?php echo $disable_group_js; ?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>

                    <div class="br-note">
                        <p>
							<?php _e( 'Combine JS merges all your minified files into a single file, reducing HTTP requests.', 'breeze' ); ?>
                        </p>
                        <p class="br-important">

							<?php
							echo '<strong>';
							_e( 'Important: ', 'breeze' );
							echo '</strong>';
							_e( 'This option can\'t be combined with "Delay JS Inline Script" or "Delay All JavaScript" .', 'breeze' );
							?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->


            <!-- START OPTION -->
			<?php
			if ( isset( $options['breeze-exclude-js'] ) && ! empty( $options['breeze-exclude-js'] ) ) {
				$excluded_js_check = breeze_validate_urls( $options['breeze-exclude-js'] );
				if ( true === $excluded_js_check ) {
					$excluded_js_check_extension = breeze_validate_the_right_extension( $options['breeze-exclude-js'], 'js' );
				}
			}

			$js_output = '';
			if ( ! empty( $options['breeze-exclude-js'] ) ) {
				$output    = implode( "\n", $options['breeze-exclude-js'] );
				$js_output = esc_textarea( $output );
			}

			$disable_overlay = '';
			if ( false === $js_minify_state ) {
				$disable_overlay = ' br-apply-disable';
			}

			?>
            <div class="br-option-item<?php echo $disable_overlay; ?>">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Exclude JS', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
					<textarea cols="100" rows="7" id="exclude-js" name="exclude-js"
                              placeholder="Exclude JS on the basis of the folder&#10;https://demo/wp-content/plugins/some-plugin/assets/js/demo(.*)&#10;&#10;Exclude JS on the basis of the file name&#10;https://demo/wp-content/plugins/some-plugin/assets/js/demo_1/js_random_someplugin_(.*).js"><?php echo $js_output; ?></textarea>
                    <div class="br-note">
                        <p>
							<?php

							_e( 'Use this option to exclude JS files from Minification and Grouping. Enter the URLs of JS files on each line.', 'breeze' );
							?>
                        </p>
                        <p class="br-notice">
							<?php if ( false === $excluded_js_check_extension ) { ?>
								<?php _e( 'One (or more) URL is incorrect. Please confirm that all URLs have the .js extension', 'breeze' ); ?>
							<?php } ?>
							<?php if ( false === $excluded_js_check ) { ?>
								<?php _e( 'One (or more) URL is invalid. Please check and correct the entry.', 'breeze' ); ?>
							<?php } ?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->
            <!-- START OPTION -->
            <div class="br-option-item">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Delay JS Inline Scripts', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
					<?php
					// Delay All JavaScript START
					/**
					 * Used to disable one of the options when the ther is active.
					 */
					$basic_value_all = isset( $options['breeze-delay-all-js'] ) ? filter_var( $options['breeze-delay-all-js'], FILTER_VALIDATE_BOOLEAN ) : false;
					$is_enabled_all  = ( isset( $basic_value_all ) && true === $basic_value_all ) ? checked( $options['breeze-delay-all-js'], '1', false ) : '';
					// END

					$basic_value_inlinejs = isset( $options['breeze-enable-js-delay'] ) ? filter_var( $options['breeze-enable-js-delay'], FILTER_VALIDATE_BOOLEAN ) : false;
					$is_enabled_inlinejs  = ( isset( $basic_value_inlinejs ) && true === $basic_value_inlinejs ) ? checked( $options['breeze-enable-js-delay'], '1', false ) : '';

					$delay_inline_disabled = '';
					if ( true === $basic_value_all ) {
						$delay_inline_disabled = 'disabled="disabled"';
					}

                    $is_data_stop = '';
					if ( true === $combine_js_is && true === $basic_value_inlinejs ) {
						$delay_inline_disabled = '';
						$is_data_stop = 'data-noaction="1"';
					}
					?>
                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input <?php echo $delay_inline_disabled; ?> id="enable-js-delay" name="enable-js-delay" type="checkbox" class="br-box" value="1" <?php echo $is_enabled_inlinejs; echo $is_data_stop;?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>
					<?php
					$js_output = '';
					if ( ! empty( $options['breeze-delay-js-scripts'] ) ) {
						$output    = implode( "\n", $options['breeze-delay-js-scripts'] );
						$js_output = esc_textarea( $output );
					}

					$display_text_area = 'style="display:none"';
					if ( true === $basic_value_inlinejs ) {
						$display_text_area = 'style="display:block"';
					}
					?>
                    <div <?php echo $display_text_area; ?> id="breeze-delay-js-scripts-div">
                        <br/>
                        <textarea cols="100" rows="7" id="delay-js-scripts" name="delay-js-scripts"><?php echo $js_output; ?></textarea>
                        <div class="br-note">
                            <p>
								<?php

								_e( 'You can add specific keywords to identify the inline JavaScript to be delayed. Each script identifying keyword must be added on a new line.', 'breeze' );
								?>
                                <a href="https://www.cloudways.com/blog/breeze-1-2-version-released/" target="_blank"><?php _e( 'More info here', 'breeze' ); ?></a>
                            </p>
                            <p class="br-notice">
								<?php _e( 'Please clear Varnish after applying the new settings.', 'breeze' ); ?><br/>
								<?php _e( 'This option can\'t be combined with "Combine JS" .', 'breeze' ); ?>
                            </p>
                        </div>
                    </div>
                    <p class="br-important">
						<?php
						echo '<strong>';
						_e( 'Important: ', 'breeze' );
						echo '</strong>';
						_e( 'Use only one option "Delay JS Inline Scripts" OR "Delay All JavaScript" at same time.', 'breeze' );
						?>
                    </p>
                </div>
            </div>
            <!-- END OPTION -->

            <!-- START OPTION -->
            <div class="br-option-item">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Delay All JavaScript', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
					<?php
					$basic_value_all = isset( $options['breeze-delay-all-js'] ) ? filter_var( $options['breeze-delay-all-js'], FILTER_VALIDATE_BOOLEAN ) : false;
					$is_enabled_all  = ( isset( $basic_value_all ) && true === $basic_value_all ) ? checked( $options['breeze-delay-all-js'], '1', false ) : '';

					$js_minify_state = true;
					if ( empty( $is_enabled_all ) ) {
						$js_minify_state = false;
					}

					$delay_js_disabled = '';
					if ( true === $basic_value_inlinejs ) {
						$delay_js_disabled = 'disabled="disabled"';
					}
					$is_data_stop = '';
					if ( true === $combine_js_is && true === $basic_value_all ) {
						$delay_inline_disabled = '';
						$is_data_stop = 'data-noaction="1"';
					}
					?>
                    <div class="on-off-checkbox">
                        <label class="br-switcher">
                            <input <?php echo $delay_js_disabled; ?> id="breeze-delay-all-js" name="breeze-delay-all-js" type="checkbox" class="br-box" value="1" <?php echo $is_enabled_all; echo $is_data_stop;?>>
                            <div class="br-see-state">
                            </div>
                        </label><br>
                    </div>

                    <div class="br-note">
                        <p>
							<?php _e( 'Improve the page load by delaying JavaScript execution.', 'breeze' ); ?>
                        </p>

                        <p class="br-important">
							<?php
							//                          echo '<strong>';
							//                          _e( 'Important: ', 'breeze' );
							//                          echo '</strong>';
							//                          _e( 'We recommend testing minification on a staging website before deploying it on a live website. ', 'breeze' );
							//                          echo '<br/>';
							//                          _e( 'Minification is known to cause issues on the frontend.', 'breeze' );
							?>
                        </p>
                    </div>

					<?php
					$js_output = '';
					if ( ! empty( $options['no-breeze-no-delay-js'] ) ) {
						$output    = implode( "\n", $options['no-breeze-no-delay-js'] );
						$js_output = esc_textarea( $output );
					}

					$display_text_area = 'style="display:none"';
					if ( true === $basic_value_all ) {
						$display_text_area = 'style="display:block"';
					}

					?>

                    <div <?php echo $display_text_area; ?> id="breeze-delay-js-scripts-div-all">
                        <br/>
                        <div class="br-option-text"><strong><?php _e( 'List of scripts not to delay', 'breeze' ); ?></strong></div>
                        <textarea cols="100" rows="7" id="no-delay-js-scripts" name="no-delay-js-scripts"><?php echo $js_output; ?></textarea>
                        <div class="br-note">
                            <p>
								<?php

								_e( 'You can add specific keywords to identify the Inline JavaScript or JavaScript files to not be delayed. Each script identifying keyword must be added on a new line.', 'breeze' );
								?>
                            </p>
                            <p class="br-notice">
								<?php _e( 'Please clear Varnish after applying the new settings.', 'breeze' ); ?><br>
								<?php _e( 'This option can\'t be combined with "Combine JS" .', 'breeze' ); ?>

                            </p>
                        </div>
                    </div>
                    <p class="br-important">
						<?php
						echo '<strong>';
						_e( 'Important: ', 'breeze' );
						echo '</strong>';
						_e( 'Use only one option "Delay JS Inline Scripts" OR "Delay All JavaScript" at same time.', 'breeze' );
						?>
                    </p>
                </div>
            </div>
            <!-- END OPTION -->
            <!-- START OPTION -->
            <div class="br-option-item">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'Move JS Files to Footer', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
                    <div class="breeze-list-url">
						<?php if ( ! empty( $options['breeze-move-to-footer-js'] ) ) : ?>
							<?php foreach ( $options['breeze-move-to-footer-js'] as $js_url ) : ?>
                                <div class="breeze-input-group">
                                    <input type="text" size="98"
                                           class="breeze-input-url"
                                           name="move-to-footer-js[]"
                                           placeholder="<?php _e( 'Enter URL...', 'breeze' ); ?>"
                                           value="<?php echo esc_html( $js_url ); ?>"/>
                                    <span class="sort-handle">
										<span class="dashicons dashicons-arrow-up moveUp"></span>
										<span class="dashicons dashicons-arrow-down moveDown"></span>
									</span>
                                    <span class="dashicons dashicons-no item-remove" title="<?php _e( 'Remove', 'breeze' ); ?>"></span>
                                </div>
							<?php endforeach; ?>
						<?php else : ?>
                            <div class="breeze-input-group">
                                <input type="text" size="98"
                                       class="breeze-input-url"
                                       id="move-to-footer-js"
                                       name="move-to-footer-js[]"
                                       placeholder="<?php _e( 'Enter URL...', 'breeze' ); ?>"
                                       value=""/>
                                <span class="sort-handle">
									<span class="dashicons dashicons-arrow-up moveUp"></span>
									<span class="dashicons dashicons-arrow-down moveDown"></span>
								</span>
                                <span class="dashicons dashicons-no item-remove" title="<?php _e( 'Remove', 'breeze' ); ?>"></span>
                            </div>
						<?php endif; ?>
                    </div>
                    <div style="margin: 10px 0">
                        <button type="button" class="br-blue-button-reverse add-url" id="add-move-to-footer-js">
							<?php _e( 'Add URL', 'breeze' ); ?>
                        </button>
                    </div>
                    <div class="br-note">
                        <p>
							<?php

							_e( 'Enter the complete URLs of JS files to be moved to the footer during minification process.', 'breeze' );
							?>
                        </p>
                        <p class="br-important">
							<?php
							echo '<strong>';
							_e( 'Important: ', 'breeze' );
							echo '</strong>';
							_e( 'You should add the URL of original files as URL of minified files are not supported.', 'breeze' );
							?>
                        </p>

                    </div>
                </div>
            </div>
            <!-- END OPTION -->
            <!-- START OPTION -->
            <div class="br-option-item">
                <div class="br-label">
                    <div class="br-option-text">
						<?php _e( 'JS Files With Deferred Loading', 'breeze' ); ?>
                    </div>
                </div>
                <div class="br-option">
                    <div class="breeze-list-url">
						<?php if ( ! empty( $options['breeze-defer-js'] ) ) : ?>
							<?php foreach ( $options['breeze-defer-js'] as $js_url ) : ?>
                                <div class="breeze-input-group">

                                    <input type="text" size="98"
                                           class="breeze-input-url"
                                           name="defer-js[]"
                                           placeholder="<?php _e( 'Enter URL...', 'breeze' ); ?>"
                                           value="<?php echo esc_html( $js_url ); ?>"/>
                                    <span class="sort-handle">
										<span class="dashicons dashicons-arrow-up moveUp"></span>
										<span class="dashicons dashicons-arrow-down moveDown"></span>
									</span>
                                    <span class="dashicons dashicons-no item-remove" title="<?php _e( 'Remove', 'breeze' ); ?>"></span>
                                </div>
							<?php endforeach; ?>
						<?php else : ?>
                            <div class="breeze-input-group">
                                <input type="text" size="98"
                                       class="breeze-input-url"
                                       name="defer-js[]"
                                       id="defer-js"
                                       placeholder="<?php _e( 'Enter URL...', 'breeze' ); ?>"
                                       value=""/>
                                <span class="sort-handle">
									<span class="dashicons dashicons-arrow-up moveUp"></span>
									<span class="dashicons dashicons-arrow-down moveDown"></span>
								</span>
                                <span class="dashicons dashicons-no item-remove" title="<?php _e( 'Remove', 'breeze' ); ?>"></span>
                            </div>
						<?php endif; ?>
                    </div>
                    <div style="margin: 10px 0">
                        <button type="button" class="br-blue-button-reverse add-url" id="add-defer-js">
							<?php _e( 'Add URL', 'breeze' ); ?>
                        </button>
                    </div>
                    <div class="br-note">
                        <p class="br-important">
							<?php
							echo '<strong>';
							_e( 'Important: ', 'breeze' );
							echo '</strong>';
							_e( 'You should add the URL of original files as URL of minified files are not supported.', 'breeze' );
							?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- END OPTION -->


        </div><!-- END GROUP -->

    </section>
    <div class="br-submit">
        <input type="submit" value="<?php echo __( 'Save Changes', 'breeze' ); ?>" class="br-submit-save"/>
    </div>
</form>
