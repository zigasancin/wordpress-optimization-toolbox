<?php

class WPShortPixelSettings {
    private $_apiKey = '';
    private $_compressionType = 1;
    private $_keepExif = 0;
    private $_processThumbnails = 1;
    private $_CMYKtoRGBconversion = 1;
    private $_backupImages = 1;
    private $_verifiedKey = false;
    
    private $_resizeImages = false;
    private $_resizeWidth = 0;
    private $_resizeHeight = 0;

    private static $_optionsMap = array(
        //This one is accessed also directly via get_option
        'frontBootstrap' => array('key' => 'wp-short-pixel-front-bootstrap', 'default' => null), //set to 1 when need the plugin active for logged in user in the front-end
        'lastBackAction' => array('key' => 'wp-short-pixel-last-back-action', 'default' => null), //when less than 10 min. passed from this timestamp, the front-bootstrap is ineffective.

        //optimization options
        'apiKey' => array('key' => 'wp-short-pixel-apiKey', 'default' => ''),
        'verifiedKey' => array('key' => 'wp-short-pixel-verifiedKey', 'default' => false),
        'compressionType' => array('key' => 'wp-short-pixel-compression', 'default' => 1),
        'processThumbnails' => array('key' => 'wp-short-process_thumbnails', 'default' => null),
        'keepExif' => array('key' => 'wp-short-pixel-keep-exif', 'default' => 0),
        'CMYKtoRGBconversion' => array('key' => 'wp-short-pixel_cmyk2rgb', 'default' => 1),
        'createWebp' => array('key' => 'wp-short-create-webp', 'default' => null),
        'createWebpMarkup' => array('key' => 'wp-short-pixel-create-webp-markup', 'default' => null),
        'optimizeRetina' => array('key' => 'wp-short-pixel-optimize-retina', 'default' => 1),
        'optimizeUnlisted' => array('key' => 'wp-short-pixel-optimize-unlisted', 'default' => 0),
        'backupImages' => array('key' => 'wp-short-backup_images', 'default' => 1),
        'resizeImages' => array('key' => 'wp-short-pixel-resize-images', 'default' => false),
        'resizeType' => array('key' => 'wp-short-pixel-resize-type', 'default' => null),
        'resizeWidth' => array('key' => 'wp-short-pixel-resize-width', 'default' => 0),
        'resizeHeight' => array('key' => 'wp-short-pixel-resize-height', 'default' => 0),
        'siteAuthUser' => array('key' => 'wp-short-pixel-site-auth-user', 'default' => null),
        'siteAuthPass' => array('key' => 'wp-short-pixel-site-auth-pass', 'default' => null),
        'autoMediaLibrary' => array('key' => 'wp-short-pixel-auto-media-library', 'default' => 1),
        'optimizePdfs' => array('key' => 'wp-short-pixel-optimize-pdfs', 'default' => 1),
        'excludePatterns' => array('key' => 'wp-short-pixel-exclude-patterns', 'default' => array()),
        'png2jpg' => array('key' => 'wp-short-pixel-png2jpg', 'default' => 0),
        
        //optimize other images than the ones in Media Library
        'includeNextGen' => array('key' => 'wp-short-pixel-include-next-gen', 'default' => null),
        'hasCustomFolders' => array('key' => 'wp-short-pixel-has-custom-folders', 'default' => false),
        'customBulkPaused' => array('key' => 'wp-short-pixel-custom-bulk-paused', 'default' => false),
        
        //stats, notices, etc.
        'currentStats' => array('key' => 'wp-short-pixel-current-total-files', 'default' => null),
        'fileCount' => array('key' => 'wp-short-pixel-fileCount', 'default' => 0),
        'thumbsCount' => array('key' => 'wp-short-pixel-thumbnail-count', 'default' => 0),
        'under5Percent' => array('key' => 'wp-short-pixel-files-under-5-percent', 'default' => 0),
        'savedSpace' => array('key' => 'wp-short-pixel-savedSpace', 'default' => 0),
        'averageCompression' => array('key' => 'wp-short-pixel-averageCompression', 'default' => null),
        'apiRetries' => array('key' => 'wp-short-pixel-api-retries', 'default' => 0),
        'totalOptimized' => array('key' => 'wp-short-pixel-total-optimized', 'default' => 0),
        'totalOriginal' => array('key' => 'wp-short-pixel-total-original', 'default' => 0),
        'quotaExceeded' => array('key' => 'wp-short-pixel-quota-exceeded', 'default' => 0),
        'httpProto' => array('key' => 'wp-short-pixel-protocol', 'default' => 'https'),
        'downloadProto' => array('key' => 'wp-short-pixel-download-protocol', 'default' => null),
        'mediaAlert' => array('key' => 'wp-short-pixel-media-alert', 'default' => null),
        'dismissedNotices' => array('key' => 'wp-short-pixel-dismissed-notices', 'default' => array()),
        'activationDate' => array('key' => 'wp-short-pixel-activation-date', 'default' => null),
        'activationNotice' => array('key' => 'wp-short-pixel-activation-notice', 'default' => null),
        'mediaLibraryViewMode' => array('key' => 'wp-short-pixel-view-mode', 'default' => null),
        'redirectedSettings' => array('key' => 'wp-short-pixel-redirected-settings', 'default' => null),
        'convertedPng2Jpg' => array('key' => 'wp-short-pixel-converted-png2jpg', 'default' => array()),
        
        //bulk state machine
        'bulkType' => array('key' => 'wp-short-pixel-bulk-type', 'default' => null),
        'bulkLastStatus' => array('key' => 'wp-short-pixel-bulk-last-status', 'default' => null),
        'startBulkId' => array('key' => 'wp-short-pixel-query-id-start', 'default' => 0),
        'stopBulkId' => array('key' => 'wp-short-pixel-query-id-stop', 'default' => 0),
        'bulkCount' => array('key' => 'wp-short-pixel-bulk-count', 'default' => 0),
        'bulkPreviousPercent' => array('key' => 'wp-short-pixel-bulk-previous-percent', 'default' => 0),
        'bulkCurrentlyProcessed' => array('key' => 'wp-short-pixel-bulk-processed-items', 'default' => 0),
        'bulkAlreadyDoneCount' => array('key' => 'wp-short-pixel-bulk-done-count', 'default' => 0),
        'lastBulkStartTime' => array('key' => 'wp-short-pixel-last-bulk-start-time', 'default' => 0),
        'lastBulkSuccessTime' => array('key' => 'wp-short-pixel-last-bulk-success-time', 'default' => 0),
        'bulkRunningTime' => array('key' => 'wp-short-pixel-bulk-running-time', 'default' => 0),
        'cancelPointer' => array('key' => 'wp-short-pixel-cancel-pointer', 'default' => 0),
        'skipToCustom' => array('key' => 'wp-short-pixel-skip-to-custom', 'default' => null),
        'bulkEverRan' => array('key' => 'wp-short-pixel-bulk-ever-ran', 'default' => false),
        'flagId' => array('key' => 'wp-short-pixel-flag-id', 'default' => 0),
        'failedImages' => array('key' => 'wp-short-pixel-failed-imgs', 'default' => 0),
        'bulkProcessingStatus' => array('key' => 'bulkProcessingStatus', 'default' => null),
        
        //'priorityQueue' => array('key' => 'wp-short-pixel-priorityQueue', 'default' => array()),
        'prioritySkip' => array('key' => 'wp-short-pixel-prioritySkip', 'default' => array()),
        
        //'' => array('key' => 'wp-short-pixel-', 'default' => null),
    );
    
    public function __construct() {
        $this->populateOptions();
    }    
    
    public function populateOptions() {

        $this->_apiKey = self::getOpt('wp-short-pixel-apiKey', '');
        $this->_verifiedKey = self::getOpt('wp-short-pixel-verifiedKey', $this->_verifiedKey);
        $this->_compressionType = self::getOpt('wp-short-pixel-compression', $this->_compressionType);
        $this->_processThumbnails = self::getOpt('wp-short-process_thumbnails', $this->_processThumbnails);
        $this->_CMYKtoRGBconversion = self::getOpt('wp-short-pixel_cmyk2rgb', $this->_CMYKtoRGBconversion);
        $this->_backupImages = self::getOpt('wp-short-backup_images', $this->_backupImages);
        $this->_resizeImages =  self::getOpt( 'wp-short-pixel-resize-images', 0);        
        $this->_resizeWidth = self::getOpt( 'wp-short-pixel-resize-width', 0);        
        $this->_resizeHeight = self::getOpt( 'wp-short-pixel-resize-height', 0);                

        // the following lines practically set defaults for options if they're not set
        foreach(self::$_optionsMap as $opt) {
            self::getOpt($opt['key'], $opt['default']);
        }
    }
    
    public static function debugResetOptions() {
        foreach(self::$_optionsMap as $key => $val) {
            delete_option($val['key']);
        }
        delete_option("wp-short-pixel-bulk-previous-percent");
    }
    
    public static function onActivate() {
        if(!self::getOpt('wp-short-pixel-verifiedKey', false)) {
            update_option('wp-short-pixel-activation-notice', true, 'no');
        }
        update_option( 'wp-short-pixel-activation-date', time(), 'no');
        delete_option( 'wp-short-pixel-bulk-last-status');
        delete_option( 'wp-short-pixel-current-total-files');
        $dismissed = get_option('wp-short-pixel-dismissed-notices', array());
        if(isset($dismissed['compat'])) {
            unset($dismissed['compat']);
            update_option('wp-short-pixel-dismissed-notices', $dismissed, 'no');
        }
        $formerPrio = get_option('wp-short-pixel-priorityQueue');
        if(is_array($formerPrio) && !count(ShortPixelQueue::get())) {
            ShortPixelQueue::set($formerPrio);
            delete_option('wp-short-pixel-priorityQueue');
        }
    }
    
    public static function onDeactivate() {
        delete_option('wp-short-pixel-activation-notice');
    }

    
    public function __get($name)
    {
        if (array_key_exists($name, self::$_optionsMap)) {
            return $this->getOpt(self::$_optionsMap[$name]['key']);
        }
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __set($name, $value) {
        if (array_key_exists($name, self::$_optionsMap)) {
            if($value !== null) {
                $this->setOpt(self::$_optionsMap[$name]['key'], $value);
            } else {
                delete_option(self::$_optionsMap[$name]['key']);
            } 
        }        
    }

    public static function getOpt($key, $default = null) {
        if(isset(self::$_optionsMap[$key]['key'])) { //first try our name
            $key = self::$_optionsMap[$key]['key'];
        }
        if(get_option($key) === false) {
            add_option( $key, $default, '', 'no' );
        }
        return get_option($key);
    }
    
    public function setOpt($key, $val) {
        $ret = update_option($key, $val, 'no');

        //hack for the situation when the option would just not update....
        if($ret === false && !is_array($val) && $val != get_option($key)) {
            delete_option($key);
            $alloptions = wp_load_alloptions();
            if ( isset( $alloptions[$key] ) ) {
                wp_cache_delete( 'alloptions', 'options' );
            } else {
                wp_cache_delete( $key, 'options' );
            }
            add_option($key, $val, '', 'no');

            // still not? try the DB way...
            if($ret === false && $val != get_option($key)) {
                global $wpdb;
                $sql = "SELECT * FROM {$wpdb->prefix}options WHERE option_name = '" . $key . "'";
                $rows = $wpdb->get_results($sql);
                if(count($rows) === 0) {
                    $wpdb->insert($wpdb->prefix.'options', 
                                 array("option_name" => $key, "option_value" => (is_array($val) ? serialize($val) : $val), "autoload" => "no"), 
                                 array("option_name" => "%s", "option_value" => (is_numeric($val) ? "%d" : "%s")));
                } else { //update
                    $sql = "update {$wpdb->prefix}options SET option_value=" . 
                           (is_array($val) 
                               ? "'" . serialize($val) . "'" 
                               : (is_numeric($val) ? $val : "'" . $val . "'")) . " WHERE option_name = '" . $key . "'";
                    $rows = $wpdb->get_results($sql);
                }
                
                if($val != get_option($key)) {
                    //tough luck, gonna use the bomb...
                    wp_cache_flush();
                    add_option($key, $val, '', 'no');
                }
            }
        }
    }
}
