<?php
/**
 * User: simon
 * Date: 17.11.2017
 * Time: 13:44
 */

//TODO decouple from directly using WP metadata, in order to be able to use it for custom images
class ShortPixelPng2Jpg {
    private $_settings = null;

    public function __construct($settings){
        wp_raise_memory_limit( 'image' );
        $this->_settings = $settings;
    }

    protected function canConvertPng2Jpg($image) {
        $transparent = 0;
        if (ord(file_get_contents($image, false, null, 25, 1)) & 4) {
            $transparent = 1;
        }
        $contents = file_get_contents($image);
        if (stripos($contents, 'PLTE') !== false && stripos($contents, 'tRNS') !== false) {
            $transparent = 1;
        }
        $transparent_pixel = $img = $bg = false;
        if (!$transparent) {
            $is = getimagesize($image);
            WPShortPixel::log("PNG2JPG Image size: " . round($is[0]*$is[1]*5/1024/1024) . "M memory limit: " . ini_get('memory_limit') . " USED: " . memory_get_usage());
            WPShortPixel::log("PNG2JPG create from png $image");
            $img = @imagecreatefrompng($image);
            if(!$img) {
                $transparent = true; //it's not a PNG, can't convert it
            } else {
                $w = imagesx($img); // Get the width of the image
                $h = imagesy($img); // Get the height of the image
                //run through pixels until transparent pixel is found:
                for ($i = 0; $i < $w; $i++) {
                    for ($j = 0; $j < $h; $j++) {
                        $rgba = imagecolorat($img, $i, $j);
                        if (($rgba & 0x7F000000) >> 24) {
                            $transparent_pixel = true;
                            break;
                        }
                    }
                }
            }
        }

        //pass on the img too, if it was already loaded from PNG, matter of performance
        return array('notTransparent' => !$transparent && !$transparent_pixel, 'img' => $img);
    }

    /**
     *
     * @param array $params
     * @param string $backupPath
     * @param string $suffixRegex for example [0-9]+x[0-9]+ - a thumbnail suffix - to add the counter of file name collisions files before that suffix (img-2-150x150.jpg).
     * @param image $img - the image if it was already created from png. It will be destroyed at the end.
     * @return string
     */

    protected function doConvertPng2Jpg($params, $backup, $suffixRegex = false, $img = false) {
        $image = $params['file'];
        if(!$img) {
            $img = imagecreatefrompng($image);
            if(!$img) {
                return $params; //actually not a PNG.
            }
        }

        $x = imagesx($img);
        $y = imagesy($img);
        $bg = imagecreatetruecolor($x, $y);
        if(!$bg) return $params;
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, 1);
        imagecopy($bg, $img, 0, 0, 0, 0, $x, $y);
        imagedestroy($img);
        $newPath = preg_replace("/\.png$/i", ".jpg", $image);
        $newUrl = preg_replace("/\.png$/i", ".jpg", $params['url']);
        for ($i = 1; file_exists($newPath); $i++) {
            if($suffixRegex) {
                $newPath = preg_replace("/(" . $suffixRegex . ")\.png$/i", $i . '-$1.jpg', $image);
            }else {
                $newPath = preg_replace("/\.png$/i", "-" . $i . ".jpg", $image);
            }
        }
        if (imagejpeg($bg, $newPath, 90)) {
            $newSize = filesize($newPath);
            $origSize = filesize($image);
            if($newSize > $origSize * 0.95) {
                //if the image is not 5% smaller, don't bother.
                unlink($newPath);
                return $params;
            }
            //backup?
            if($backup) {
                $imageForBk = trailingslashit(dirname($image)) . ShortPixelAPI::MB_basename($newPath, '.jpg') . '.png';
                @rename($image, $imageForBk);
                if(!file_exists($imageForBk)) {
                    unlink($newPath);
                    return $params;
                }
                $image = $imageForBk;
                $ret = ShortPixelAPI::backupImage($image, array($image));
                if($ret['Status'] !== ShortPixelAPI::STATUS_SUCCESS) {
                    unlink($newPath);
                    return $params;
                }
            }
            unlink($image);
            $params['file'] = $newPath;
            $params['original_file'] = $image;
            $params['url'] = $newUrl;
            $params['type'] = 'image/jpeg';
            $params['png_size'] = $origSize;
            $params['jpg_size'] = $newSize;
        }
        return $params;
    }

    /**
     * Convert an uploaded image from PNG to JPG
     * @param type $params
     * @return string
     */
    public function convertPng2Jpg($params) {

        //echo("PARAMS : ");var_dump($params);
        if(!$this->_settings->png2jpg || strtolower(substr($params['file'], -4)) !== '.png') {
            return $params;
        }

        $image = $params['file'];
        WPShortPixel::log("Convert Media PNG to JPG on upload: {$image}");

        $ret = $this->canConvertPng2Jpg($image);
        if ($ret['notTransparent']) {
            $paramsC = $this->doConvertPng2Jpg($params, $this->_settings->backupImages, false, $ret['img']);
            if($paramsC['type'] == 'image/jpeg') {
                // we don't have metadata, so save the information in a temporary map
                $conv = $this->_settings->convertedPng2Jpg;
                //do a cleanup first
                foreach($conv as $key => $val) {
                    if(time() - $val['timestamp'] > 3600) unset($conv[$key]);
                }
                $conv[$paramsC['file']] = array('pngFile' => $paramsC['original_file'], 'backup' => $this->_settings->backupImages,
                    'optimizationPercent' => round(100.0 * (1.00 - $paramsC['jpg_size'] / $paramsC['png_size'])),
                    'timestamp' => time());
                $this->_settings->convertedPng2Jpg = $conv;
            }
            return $paramsC;
        }
        return $params;
    }

    /**
     * convert PNG to JPEG if possible - already existing image in Media Library
     *
     * @param type $meta
     * @param type $ID
     * @return string
     */
    public function checkConvertMediaPng2Jpg($meta, $ID) {

        if(!$this->_settings->png2jpg || !isset($meta['file']) || strtolower(substr($meta['file'], -4)) !== '.png') {
            return $meta;
        }

        WPShortPixel::log("Send to processing: Convert Media PNG to JPG #{$ID} META: " . json_encode($meta));

        $image = $meta['file'];
        $imagePath = get_attached_file($ID);
        $basePath = trailingslashit(str_replace($image, "", $imagePath));
        $imageUrl = wp_get_attachment_url($ID);
        $baseUrl = trailingslashit(str_replace($image, "", $imageUrl));

        // set a temporary error in order to make sure user gets something if the image failed from memory limit.
        if(   isset($meta['ShortPixel']['Retries']) && $meta['ShortPixel']['Retries'] > 3
           && isset($meta['ShortPixel']['ErrCode']) && $meta['ShortPixel']['ErrCode'] == ShortPixelAPI::ERR_PNG2JPG_MEMORY) {
            WPShortPixel::log("PNG2JPG too many memory failures!");
            throw new Exception('Not enough memory to convert from PNG to JPG.', ShortPixelAPI::ERR_PNG2JPG_MEMORY);
        }
        $meta['ShortPixelImprovement'] = 'Error: <i>Not enough memory to convert from PNG to JPG.</i>';
        if(!isset($meta['ShortPixel']) || !is_array($meta['ShortPixel'])) {
            $meta['ShortPixel'] = array();
        }
        $meta['ShortPixel']['Retries'] = isset($meta['ShortPixel']['Retries']) ? $meta['ShortPixel']['Retries'] + 1 : 1;
        $meta['ShortPixel']['ErrCode'] = ShortPixelAPI::ERR_PNG2JPG_MEMORY;
        wp_update_attachment_metadata($ID, $meta);

        $ret = $this->canConvertPng2Jpg($imagePath);
        if (!$ret['notTransparent']) {
            return $meta; //cannot convert it
        }

        $ret = $this->doConvertPng2Jpg(array('file' => $imagePath, 'url' => false, 'type' => 'image/png'), $this->_settings->backupImages, false, $ret['img']);

        //unset the temporary error
        unset($meta['ShortPixelImprovement']);
        unset($meta['ShortPixel']['ErrCode']);
        $meta['ShortPixel']['Retries'] -= 1;
        wp_update_attachment_metadata($ID, $meta);

        if ($ret['type'] == 'image/jpeg') {
            //convert to the new URLs the urls in the existing posts.
            $baseRelPath = trailingslashit(dirname($image));
            $this->png2JpgUpdateUrls(array(), $imageUrl, $baseUrl . $baseRelPath . wp_basename($ret['file']));
            $pngSize = $ret['png_size'];
            $jpgSize = $ret['jpg_size'];
            $imagePath = isset($ret['original_file']) ? $ret['original_file'] : $imagePath;

            //conversion succeeded for the main image, update meta and proceed to thumbs. (It could also not succeed if the converted file is not smaller)
            $meta['file'] = str_replace($basePath, '', $ret['file']);
            $meta['type'] = 'image/jpeg';

            $originalSizes = isset($meta['sizes']) ? $meta['sizes'] : array();
            foreach($meta['sizes'] as $size => $info) {
                $rett = $this->doConvertPng2Jpg(array('file' => $basePath . $baseRelPath . $info['file'], 'url' => false, 'type' => 'image/png'),
                    $this->_settings->backupImages, "[0-9]+x[0-9]+");
                if ($rett['type'] == 'image/jpeg') {
                    $meta['sizes'][$size]['file'] = wp_basename($rett['file']);
                    $meta['sizes'][$size]['mime-type'] = 'image/jpeg';
                    $pngSize += $ret['png_size'];
                    $jpgSize += $ret['jpg_size'];
                    $originalSizes[$size]['file'] = wp_basename($rett['file'], '.jpg') . '.png';
                    $this->png2JpgUpdateUrls(array(), $baseUrl . $baseRelPath . $info['file'], $baseUrl . $baseRelPath . wp_basename($rett['file']));
                }
            }
            $meta['ShortPixelPng2Jpg'] = array('originalFile' => $imagePath, 'originalSizes' => $originalSizes,
                'backup' => $this->_settings->backupImages,
                'optimizationPercent' => round(100.0 * (1.00 - $jpgSize / $pngSize)));
            update_attached_file($ID, $meta['file']);
            wp_update_attachment_metadata($ID, $meta);
        }

        return $meta;
    }

    /**
     * taken from Velvet Blues Update URLs plugin
     * @param $options
     * @param $oldurl
     * @param $newurl
     * @return array
     */
    protected function png2JpgUpdateUrls($options,$oldurl,$newurl){
        global $wpdb;
        $results = array();
        $queries = array(
            'content' =>		array("UPDATE $wpdb->posts SET post_content = replace(post_content, %s, %s)",  __('Content Items (Posts, Pages, Custom Post Types, Revisions)','hortpixel-image-optimiser') ),
            'excerpts' =>		array("UPDATE $wpdb->posts SET post_excerpt = replace(post_excerpt, %s, %s)", __('Excerpts','hortpixel-image-optimiser') ),
            'attachments' =>	array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s) WHERE post_type = 'attachment'",  __('Attachments','hortpixel-image-optimiser') ),
            'links' =>			array("UPDATE $wpdb->links SET link_url = replace(link_url, %s, %s)", __('Links','hortpixel-image-optimiser') ),
            'custom' =>			array("UPDATE $wpdb->postmeta SET meta_value = replace(meta_value, %s, %s)",  __('Custom Fields','hortpixel-image-optimiser') ),
            'guids' =>			array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s)",  __('GUIDs','hortpixel-image-optimiser') )
        );
        if(count($options) == 0) {
            $options = array_keys($queries);
        }
        foreach($options as $option){
            if( $option == 'custom' ){
                $n = 0;
                $row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta" );
                $page_size = 10000;
                $pages = ceil( $row_count / $page_size );

                for( $page = 0; $page < $pages; $page++ ) {
                    $current_row = 0;
                    $start = $page * $page_size;
                    $end = $start + $page_size;
                    $pmquery = "SELECT * FROM $wpdb->postmeta WHERE meta_value <> ''";
                    $items = $wpdb->get_results( $pmquery );
                    foreach( $items as $item ){
                        $value = $item->meta_value;
                        if( trim($value) == '' )
                            continue;

                        $edited = $this->png2JpgUnserializeReplace( $oldurl, $newurl, $value );

                        if( $edited != $value ){
                            $fix = $wpdb->query("UPDATE $wpdb->postmeta SET meta_value = '".$edited."' WHERE meta_id = ".$item->meta_id );
                            if( $fix )
                                $n++;
                        }
                    }
                }
                $results[$option] = array($n, $queries[$option][1]);
            }
            else{
                $result = $wpdb->query( $wpdb->prepare( $queries[$option][0], $oldurl, $newurl) );
                $results[$option] = array($result, $queries[$option][1]);
            }
        }
        return $results;
    }

    /**
     * taken from Velvet Blues Update URLs plugin
     * @param string $from
     * @param string $to
     * @param string $data
     * @param bool|false $serialised
     * @return array|mixed|string
     */
    function png2JpgUnserializeReplace( $from = '', $to = '', $data = '', $serialised = false ) {
        try {
            if ( false !== is_serialized( $data ) ) {
                $unserialized = unserialize( $data );
                $data = $this->png2JpgUnserializeReplace( $from, $to, $unserialized, true );
            }
            elseif ( is_array( $data ) ) {
                $_tmp = array( );
                foreach ( $data as $key => $value ) {
                    $_tmp[ $key ] = $this->png2JpgUnserializeReplace( $from, $to, $value, false );
                }
                $data = $_tmp;
                unset( $_tmp );
            }
            else {
                if ( is_string( $data ) )
                    $data = str_replace( $from, $to, $data );
            }
            if ( $serialised )
                return serialize( $data );
        } catch( Exception $error ) {
        }
        return $data;
    }
}