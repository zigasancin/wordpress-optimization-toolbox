<?php
/**
 * User: simon
 * Date: 11.04.2018
 */

class ShortPixelFeedback {

    private $key;
    private $ctrl;
    private $plugin_file = '';
    private $plugin_name = '';

    function __construct( $_plugin_file, $slug, $key, $ctrl) {

        $this->plugin_file = $_plugin_file;
        $this->plugin_name = $slug; //for translations
        $this->key = $key;
        $this->ctrl = $ctrl;

        // Deactivation
        add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array( $this, 'filterActionLinks') );
        add_action( 'admin_footer-plugins.php', array( $this, 'goodbyeAjax') );
        add_action( 'wp_ajax_shortpixel_deactivate_plugin', array( $this, 'deactivatePluginCallback') );

    }

    /**
     * Filter the deactivation link to allow us to present a form when the user deactivates the plugin
     * @since 1.0.0
     */
    public function filterActionLinks( $links ) {

        if( isset( $links['deactivate'] ) ) {
            $deactivation_link = $links['deactivate'];
            // Insert an onClick action to allow form before deactivating
            $deactivation_link = str_replace( '<a ',
                '<div class="shortpixel-deactivate-form-wrapper">
                     <span class="shortpixel-deactivate-form" id="shortpixel-deactivate-form-' . esc_attr( $this->plugin_name ) . '"></span>
                 </div><a onclick="javascript:event.preventDefault();" id="shortpixel-deactivate-link-' . esc_attr( $this->plugin_name ) . '" ', $deactivation_link );
            $links['deactivate'] = $deactivation_link;
        }
        return $links;
    }

    /**
     * Form text strings
     * These can be filtered
     * @since 1.0.0
     */
    public function goodbyeAjax() {
        // Get our strings for the form
        $form = $this->getFormInfo();

        // Build the HTML to go in the form
        $html = '<div class="shortpixel-deactivate-form-head"><strong>' . esc_html( $form['heading'] ) . '</strong></div>';
        $html .= '<div class="shortpixel-deactivate-form-body">';
        if( is_array( $form['options'] ) ) {
            $html .= '<div class="shortpixel-deactivate-options">';
            $html .= '<span title="' . __( 'Check this if you don\\\'t plan to use ShortPixel in the future on this website. You might also want to run a Bulk Delete SP Metadata before removing the plugin (Media Library -> Bulk ShortPixel).', $this->plugin_name )
                  . '"><input type="checkbox" name="shortpixel-remove-settings" id="shortpixel-remove-settings" value="yes"> <label for="shortpixel-remove-settings">'
                  . esc_html__( 'Remove the ShortPixel settings on plugin delete.', $this->plugin_name ) . '</label></span><br>';
            $html .= '<p><strong>' . esc_html( $form['body'] ) . '</strong></p><p>';
            foreach( $form['options'] as $key => $option ) {
                $html .= '<input type="radio" name="shortpixel-deactivate-reason"'.('features' == $key ? ' checked="checked"' : '').' id="' . esc_attr( $key ) . '" value="' . esc_attr( $key ) . '"> <label for="' . esc_attr( $key ) . '">' . esc_attr( $option ) . '</label><br>';
            }
            $html .= '</p><label id="shortpixel-deactivate-details-label" for="shortpixel-deactivate-reasons"><strong>' . esc_html( $form['details'] ) .'</strong></label><textarea name="shortpixel-deactivate-details" id="shortpixel-deactivate-details" rows="2" style="width:100%"></textarea>';
            $html .= '<label for="anonymous" title="'
                . __("If you UNCHECK this then your email address will be sent along with your feedback. This can be used by ShortPixel to get back to you for more info or a solution.",'shortpixel-image-optimiser')
                . '"><input type="checkbox" name="shortpixel-deactivate-tracking" checked="checked" id="anonymous"> ' . esc_html__( 'Send anonymous', $this->plugin_name ) . '</label><br>';
            $html .= '</div><!-- .shortpixel-deactivate-options -->';
        }
        $html .= '</div><!-- .shortpixel-deactivate-form-body -->';
        $html .= '<p class="deactivating-spinner"><span class="spinner"></span> ' . __( 'Submitting form', $this->plugin_name ) . '</p>';
        $html .= '<div class="shortpixel-deactivate-form-footer"><p><a id="shortpixel-deactivate-plugin" href="#">' . __( 'Just Deactivate', $this->plugin_name ) . '</a><a id="shortpixel-deactivate-submit-form" class="button button-primary" href="#">' . __( 'Submit and Deactivate', $this->plugin_name ) . '</a></p></div>'
        ?>
        <div class="shortpixel-deactivate-form-bg"></div>
        <style type="text/css">
            .shortpixel-deactivate-form-active .shortpixel-deactivate-form-bg {
                background: rgba( 0, 0, 0, .5 );
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
            .shortpixel-deactivate-form-wrapper {
                position: relative;
                z-index: 999;
                display: none;
            }
            .shortpixel-deactivate-form-active .shortpixel-deactivate-form-wrapper {
                display: block;
            }
            .shortpixel-deactivate-form {
                display: none;
            }
            .shortpixel-deactivate-form-active .shortpixel-deactivate-form {
                position: absolute;
                bottom: 30px;
                left: 0;
                max-width: 500px;
                min-width: 330px;
                background: #fff;
                white-space: normal;
            }
            .shortpixel-deactivate-form-head {
                background: #4bbfcc;
                color: #fff;
                padding: 8px 18px;
            }
            .shortpixel-deactivate-form-body {
                padding: 8px 18px;
                color: #444;
            }
            .deactivating-spinner {
                display: none;
            }
            .deactivating-spinner .spinner {
                float: none;
                margin: 4px 4px 0 18px;
                vertical-align: bottom;
                visibility: visible;
            }
            .shortpixel-deactivate-form-footer {
                padding: 8px 18px;
            }
            .shortpixel-deactivate-form-footer p {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .shortpixel-deactivate-form.process-response .shortpixel-deactivate-form-body,
            .shortpixel-deactivate-form.process-response .shortpixel-deactivate-form-footer {
                position: relative;
            }
            .shortpixel-deactivate-form.process-response .shortpixel-deactivate-form-body:after,
            .shortpixel-deactivate-form.process-response .shortpixel-deactivate-form-footer:after {
                content: "";
                display: block;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba( 255, 255, 255, .5 );
            }
        </style>
        <script>
            jQuery(document).ready(function($){
                var deactivateURL = $("#shortpixel-deactivate-link-<?php echo esc_attr( $this->plugin_name ); ?>"),
                    formContainer = $('#shortpixel-deactivate-form-<?php echo esc_attr( $this->plugin_name ); ?>'),
                    detailsStrings = {
                        'setup' : '<?php echo __( 'What was the dificult part ?', $this->plugin_name ) ?>',
                        'documentation' : '<?php echo __( 'What can we describe more ?', $this->plugin_name ) ?>',
                        'features' : '<?php echo __( 'How could we improve ?', $this->plugin_name ) ?>',
                        'better-plugin' : '<?php echo __( 'Can you mention it ?', $this->plugin_name ) ?>',
                        'incompatibility' : '<?php echo __( 'With what plugin or theme is incompatible ?', $this->plugin_name ) ?>',
                        'maintenance' : '<?php echo __( 'Please specify', $this->plugin_name ) ?>',
                    };

                $( deactivateURL ).on("click",function(){
                    // We'll send the user to this deactivation link when they've completed or dismissed the form
                    var url = deactivateURL.attr( 'href' );
                    $('body').toggleClass('shortpixel-deactivate-form-active');
                    formContainer.fadeIn({complete: function(){
                        var offset = formContainer.offset();
                        if( offset.top < 50) {
                            $(this).parent().css('top', (50 - offset.top) + 'px')
                        }
                        $('html,body').animate({ scrollTop: Math.max(0, offset.top - 50) });
                    }});
                    formContainer.html( '<?php echo $html; ?>');

                    formContainer.on( 'change', 'input[name="shortpixel-deactivate-reason"]', function(){
                        var detailsLabel = formContainer.find( '#shortpixel-deactivate-details-label strong' );
                        var value = formContainer.find( 'input[name="shortpixel-deactivate-reason"]:checked' ).val();
                        detailsLabel.text( detailsStrings[ value ] );
                    });

                    formContainer.on('click', '#shortpixel-deactivate-submit-form', function(e){
                        debugger;
                        var data = {
                            'action': 'shortpixel_deactivate_plugin',
                            'security': "<?php echo wp_create_nonce ( 'shortpixel_deactivate_plugin' ); ?>",
                            'dataType': "json"
                        };
                        e.preventDefault();
                        // As soon as we click, the body of the form should disappear
                        formContainer.addClass( 'process-response' );
                        // Fade in spinner
                        formContainer.find(".deactivating-spinner").fadeIn();

                        data['reason']   = formContainer.find( 'input[name="shortpixel-deactivate-reason"]:checked' ).val();
                        data['details']  = formContainer.find('#shortpixel-deactivate-details').val();
                        data['anonymous'] = formContainer.find( '#anonymous:checked' ).length;
                        data['remove-settings'] = formContainer.find( '#shortpixel-remove-settings:checked').length;

                        $.post(
                            ajaxurl,
                            data,
                            function(response){
                                // Redirect to original deactivation URL
                                debugger;
                                window.location.href = url;
                            }
                        );
                    });

                    formContainer.on('click', '#shortpixel-deactivate-plugin', function(e){
                        e.preventDefault();
                        window.location.href = url;
                    });

                    // If we click outside the form, the form will close
                    $('.shortpixel-deactivate-form-bg').on('click',function(){
                        formContainer.fadeOut();
                        $('body').removeClass('shortpixel-deactivate-form-active');
                    });
                });
            });
        </script>
    <?php }

    /*
     * Form text strings
     * These are non-filterable and used as fallback in case filtered strings aren't set correctly
     * @since 1.0.0
     */
    public function getFormInfo() {
        $form = array();
        $form['heading'] = __( 'Sorry to see you go', $this->plugin_name );
        $form['body'] = __( 'Before you deactivate the plugin, would you quickly give us your reason for doing so?', $this->plugin_name );
        $form['options'] = array(
            'setup'           => __( 'Set up is too difficult', $this->plugin_name ),
            'documentation'   => __( 'Lack of documentation', $this->plugin_name ),
            'features'        => __( 'Not the features I wanted', $this->plugin_name ),
            'better-plugin'   => __( 'Found a better plugin', $this->plugin_name ),
            'incompatibility' => __( 'Incompatible with theme or plugin', $this->plugin_name ),
            'maintenance'     => __( 'Other', $this->plugin_name ),
        );
        $form['details'] = __( 'How could we improve ?', $this->plugin_name );
        return $form;
    }

    public function deactivatePluginCallback() {

        check_ajax_referer( 'shortpixel_deactivate_plugin', 'security' );

        if ( isset($_POST['reason']) && isset($_POST['details']) && isset($_POST['anonymous']) ) {
            $_POST = $this->ctrl->validateFeedback($_POST);
            require_once 'shortpixel-plugin-request.php';
            $anonymous = isset($_POST['anonymous']) && $_POST['anonymous'];
            $args = array(
                'key' => $anonymous ? false : $this->key,
                'reason' => $_POST['reason'],
                'details' => $_POST['details'],
                'anonymous' => $anonymous
            );
            $request = new ShortPixelPluginRequest( $this->plugin_file, 'http://' . SHORTPIXEL_API . '/v2/feedback.php', $args );
            if ( $request->request_successful ) {
                echo json_encode( array(
                    'status' => 'ok',
                ) );
            }else{
                echo json_encode( array(
                    'status' => 'nok',
                ) );
            }
        }else{
            echo json_encode( array(
                'status' => 'OK',
            ) );
        }

        die();

    }

}