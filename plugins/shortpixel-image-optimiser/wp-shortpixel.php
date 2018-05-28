<?php 
/**
 * Plugin Name: ShortPixel Image Optimizer
 * Plugin URI: https://shortpixel.com/
 * Description: ShortPixel optimizes images automatically, while guarding the quality of your images. Check your <a href="options-general.php?page=wp-shortpixel" target="_blank">Settings &gt; ShortPixel</a> page on how to start optimizing your image library and make your website load faster. 
 * Version: 4.10.5
 * Author: ShortPixel
 * Author URI: https://shortpixel.com
 * Text Domain: shortpixel-image-optimiser
 * Domain Path: /lang
 */

define('SHORTPIXEL_RESET_ON_ACTIVATE', false); //if true TODO set false
//define('SHORTPIXEL_DEBUG', true);
//define('SHORTPIXEL_DEBUG_TARGET', true);

define('SHORTPIXEL_PLUGIN_FILE', __FILE__);

//define('SHORTPIXEL_AFFILIATE_CODE', '');

define('SHORTPIXEL_IMAGE_OPTIMISER_VERSION', "4.10.5");
define('SHORTPIXEL_MAX_TIMEOUT', 10);
define('SHORTPIXEL_VALIDATE_MAX_TIMEOUT', 15);
define('SHORTPIXEL_BACKUP', 'ShortpixelBackups');
define('SHORTPIXEL_MAX_API_RETRIES', 50);
define('SHORTPIXEL_MAX_ERR_RETRIES', 5);
define('SHORTPIXEL_MAX_FAIL_RETRIES', 3);
if(!defined('SHORTPIXEL_MAX_THUMBS')) { //can be defined in wp-config.php
    define('SHORTPIXEL_MAX_THUMBS', 100);
}

define('SHORTPIXEL_PRESEND_ITEMS', 3);
define('SHORTPIXEL_API', 'api.shortpixel.com');

define('SHORTPIXEL_MAX_EXECUTION_TIME', ini_get('max_execution_time'));

require_once(ABSPATH . 'wp-admin/includes/file.php');

$sp__uploads = wp_upload_dir();
define('SHORTPIXEL_UPLOADS_BASE', $sp__uploads['basedir']);
define('SHORTPIXEL_UPLOADS_URL', is_main_site() ? $sp__uploads['baseurl'] : dirname(dirname($sp__uploads['baseurl'])));
define('SHORTPIXEL_UPLOADS_NAME', basename(is_main_site() ? SHORTPIXEL_UPLOADS_BASE : dirname(dirname(SHORTPIXEL_UPLOADS_BASE))));
$sp__backupBase = is_main_site() ? SHORTPIXEL_UPLOADS_BASE : dirname(dirname(SHORTPIXEL_UPLOADS_BASE));
define('SHORTPIXEL_BACKUP_FOLDER', $sp__backupBase . '/' . SHORTPIXEL_BACKUP);

/*
 if ( is_numeric(SHORTPIXEL_MAX_EXECUTION_TIME)  && SHORTPIXEL_MAX_EXECUTION_TIME > 10 )
    define('SHORTPIXEL_MAX_EXECUTION_TIME', SHORTPIXEL_MAX_EXECUTION_TIME - 5 );   //in seconds
else
    define('SHORTPIXEL_MAX_EXECUTION_TIME', 25 );
*/

define('SHORTPIXEL_MAX_EXECUTION_TIME2', 2 );
define("SHORTPIXEL_MAX_RESULTS_QUERY", 30);

function shortpixelInit() {
    global $shortPixelPluginInstance;
    //limit to certain admin pages if function available
    $loadOnThisPage = !function_exists('get_current_screen');
    if(!$loadOnThisPage) {
        $screen = get_current_screen();
        if(is_object($screen) && !in_array($screen->id, array('upload', 'edit', 'edit-tags', 'post-new', 'post'))) {
            return;
        }
    }
    require_once('class/shortpixel_queue.php');
    $prio = ShortPixelQueue::get();
    $isAjaxButNotSP = defined( 'DOING_AJAX' ) && DOING_AJAX && !(isset($_REQUEST['action']) && (strpos($_REQUEST['action'], 'shortpixel_') === 0));
    if (!isset($shortPixelPluginInstance)
        && (   ($prio && is_array($prio) && count($prio) && get_option('wp-short-pixel-front-bootstrap'))
            || is_admin() && !$isAjaxButNotSP
               && (function_exists("is_user_logged_in") && is_user_logged_in()) //is admin, is logged in - :) seems funny but it's not, ajax scripts are admin even if no admin is logged in.
               && (   current_user_can( 'manage_options' )
                   || current_user_can( 'upload_files' )
                   || current_user_can( 'edit_posts' )
                  )
           )
       ) 
    {
        require_once('wp-shortpixel-req.php');
        $shortPixelPluginInstance = new WPShortPixel;
    }
} 

function shortPixelHandleImageUploadHook($meta, $ID = null) {
    global $shortPixelPluginInstance;
    if(!isset($shortPixelPluginInstance)) {
        require_once('wp-shortpixel-req.php');
        $shortPixelPluginInstance = new WPShortPixel;
    }
    return $shortPixelPluginInstance->handleMediaLibraryImageUpload($meta, $ID);
}

function shortPixelReplaceHook($params) {
    if(isset($params['post_id'])) { //integration with EnableMediaReplace - that's an upload for replacing an existing ID
        global $shortPixelPluginInstance;
        if (!isset($shortPixelPluginInstance)) {
            require_once('wp-shortpixel-req.php');
            $shortPixelPluginInstance = new WPShortPixel;
        }
        $itemHandler = $shortPixelPluginInstance->onDeleteImage($params['post_id']);
        $itemHandler->deleteAllSPMeta();
    }
}

function shortPixelPng2JpgHook($params) {
    global $shortPixelPluginInstance;
    if(!isset($shortPixelPluginInstance)) {
        require_once('wp-shortpixel-req.php');
        $shortPixelPluginInstance = new WPShortPixel;
    }
    return $shortPixelPluginInstance->convertPng2Jpg($params);
}

function shortPixelNggAdd($image) {
    global $shortPixelPluginInstance;
    if(!isset($shortPixelPluginInstance)) {
        require_once('wp-shortpixel-req.php');
        $shortPixelPluginInstance = new WPShortPixel;
    }
    $shortPixelPluginInstance->handleNextGenImageUpload($image);
}

function shortPixelActivatePlugin () {
    require_once('wp-shortpixel-req.php');
    WPShortPixel::shortPixelActivatePlugin();    
}

function shortPixelDeactivatePlugin () {
    require_once('wp-shortpixel-req.php');
    WPShortPixel::shortPixelDeactivatePlugin();    
}

//Picture generation, hooked on the_content filter
function shortPixelConvertImgToPictureAddWebp($content) {
    if(function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        //for AMP pages the <picture> tag is not allowed
        return $content;
    }
    require_once('class/front/img-to-picture-webp.php');
    return ShortPixelImgToPictureWebp::convert($content);// . "<!-- PICTURE TAGS BY SHORTPIXEL -->";
}
function shortPixelAddPictureJs() {
    // Don't do anything with the RSS feed.
    if ( is_feed() || is_admin() ) { return; }
    
    echo '<script>'
       . 'var spPicTest = document.createElement( "picture" );'
       . 'if(!window.HTMLPictureElement && document.addEventListener) {'
            . 'window.addEventListener("DOMContentLoaded", function() {'
                . 'var scriptTag = document.createElement("script");'
                . 'scriptTag.src = "' . plugins_url('/res/js/picturefill.min.js', __FILE__) . '";'
                . 'document.body.appendChild(scriptTag);'
            . '});'
        . '}'
       . '</script>';
}

if ( get_option('wp-short-pixel-create-webp-markup')) { 
    add_filter( 'the_content', 'shortPixelConvertImgToPictureAddWebp', 10000 ); // priority big, so it will be executed last
    add_filter( 'post_thumbnail_html', 'shortPixelConvertImgToPictureAddWebp');
    add_action( 'wp_head', 'shortPixelAddPictureJs');
//    add_action( 'wp_enqueue_scripts', 'spAddPicturefillJs' );
}


if ( !function_exists( 'vc_action' ) || vc_action() !== 'vc_inline' ) { //handle incompatibility with Visual Composer
    add_action( 'init',  'shortpixelInit');
    add_action('ngg_added_new_image', 'shortPixelNggAdd');
    
    $autoPng2Jpg = get_option('wp-short-pixel-png2jpg');
    if($autoPng2Jpg) {
        add_action( 'wp_handle_upload', 'shortPixelPng2JpgHook');
    }
    add_action('wp_handle_replace', 'shortPixelReplaceHook');
    $autoMediaLibrary = get_option('wp-short-pixel-auto-media-library');
    if($autoMediaLibrary) {
        add_filter( 'wp_generate_attachment_metadata', 'shortPixelHandleImageUploadHook', 10, 2 );
    }
    
    register_activation_hook( __FILE__, 'shortPixelActivatePlugin' );
    register_deactivation_hook( __FILE__, 'shortPixelDeactivatePlugin' );
}
?>
