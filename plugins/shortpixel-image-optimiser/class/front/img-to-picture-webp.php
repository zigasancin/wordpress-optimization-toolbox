<?php
/**
 * Class ShortPixelImgToPictureWebp - convert an <img> tag to a <picture> tag and add the webp versions of the images
 * thanks to the Responsify WP plugin for some of the code
 */

class ShortPixelImgToPictureWebp {
 
    public static function convert($content) {
        // Don't do anything with the RSS feed.
        if ( is_feed() || is_admin() ) { return $content; }

        $thisClass = __CLASS__; // hack for PHP 5.3 which doesn't accept self:: in closures
        return preg_replace_callback('/<img[^>]*>/', function ($match) use ($thisClass) {
            // Do nothing with images that have the 'sp-no-webp' class.
            if ( strpos($match[0], 'sp-no-webp') ) { return $match[0]; }
            
            $img = $thisClass::get_attributes($match[0]);

            $src = (isset($img['src'])) ? $img['src'] : false;
            $srcset = (isset($img['srcset'])) ? $img['srcset'] : false;
            $sizes = (isset($img['sizes'])) ? $img['sizes'] : false;
            
            //check if there are webps
            /*$id = $thisClass::url_to_attachment_id( $src );
            if(!$id) { 
                return $match[0]; 
            }
            $imageBase = dirname(get_attached_file($id)) . '/';
            */
            $updir = wp_upload_dir();
            $proto = explode("://", $src);
            $proto = $proto[0];
            if(strpos($updir['baseurl'], $proto."://") === false) {
                $base = explode("://", $updir['baseurl']);
                $updir['baseurl'] = $proto . "://" . $base[1];
            }
            $imageBase = str_replace($updir['baseurl'], $updir['basedir'], $src);
            if($imageBase == $src) {
                return $match[0];
            }
            $imageBase = dirname($imageBase) . '/';

            // We don't wanna have an src attribute on the <img>
            unset($img['src']);
            //unset($img['srcset']);
            //unset($img['sizes']);
            
            $srcsetWebP = '';
            if($srcset) {
                $defs = explode(",", $srcset);                
                foreach($defs as $item) {
                    $parts = preg_split('/\s+/', trim($item));
                    
                    //echo(" file: " . $parts[0] . " ext: " . pathinfo($parts[0], PATHINFO_EXTENSION) . " basename: " . wp_basename($parts[0], '.' . pathinfo($parts[0], PATHINFO_EXTENSION)));
                    
                    $fileWebP = $imageBase . wp_basename($parts[0], '.' . pathinfo($parts[0], PATHINFO_EXTENSION)) . '.webp';
                    if(file_exists($fileWebP)) {
                        $srcsetWebP .= (strlen($srcsetWebP) ? ',': '') 
                                    . preg_replace('/\.[a-zA-Z0-9]+$/', '.webp', $parts[0]) 
                                    . (isset($parts[1]) ? ' ' . $parts[1] : '');
                    }
                }
                //$srcsetWebP = preg_replace('/\.[a-zA-Z0-9]+\s+/', '.webp ', $srcset);
            } else {
                $srcset = trim($src);
                
//                die(var_dump($match));
                
                $fileWebP = $imageBase . wp_basename($srcset, '.' . pathinfo($srcset, PATHINFO_EXTENSION)) . '.webp';
                if(file_exists($fileWebP)) {
                    $srcsetWebP = preg_replace('/\.[a-zA-Z0-9]+$/', '.webp', $srcset);
                }
            }
            if(!strlen($srcsetWebP))  { return $match[0]; }

            //add the exclude class so if this content is processed again in other filter, the img is not converted again in picture
            $img['class'] = (isset($img['class']) ? $img['class'] . " " : "") . "sp-no-webp";
            
            return '<picture>'
                      .'<source srcset="' . $srcsetWebP . '"' . ($sizes ? ' sizes="' . $sizes . '"' : '') . ' type="image/webp">'
                      .'<source srcset="' . $srcset . '"' . ($sizes ? ' sizes="' . $sizes . '"' : '') . '>'
                      .'<img src="' . $src . '" ' . $thisClass::create_attributes($img) . '>'
                  .'</picture>';
        }, $content);
    }
    
    public static function get_attributes( $image_node )
    {
        if(function_exists("mb_convert_encoding")) {
            $image_node = mb_convert_encoding($image_node, 'HTML-ENTITIES', 'UTF-8');
        }
        $dom = new DOMDocument();
        @$dom->loadHTML($image_node);
        $image = $dom->getElementsByTagName('img')->item(0);
        $attributes = array();
        foreach ( $image->attributes as $attr ) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
        }
        return $attributes;
    }
    
    /**
     * Makes a string with all attributes.
     *
     * @param $attribute_array
     * @return string
     */
    public static function create_attributes( $attribute_array )
    {
        $attributes = '';
        foreach ($attribute_array as $attribute => $value) {
            $attributes .= $attribute . '="' . $value . '" ';
        }
        // Removes the extra space after the last attribute
        return substr($attributes, 0, -1);
    }
    
    /**
     * @param $image_url
     * @return array
     */
    public static function url_to_attachment_id ( $image_url ) {
        // Thx to https://github.com/kylereicks/picturefill.js.wp/blob/master/inc/class-model-picturefill-wp.php
        global $wpdb;
        $original_image_url = $image_url;
        $image_url = preg_replace('/^(.+?)(-\d+x\d+)?\.(jpg|jpeg|png|gif)((?:\?|#).+)?$/i', '$1.$3', $image_url);
        $prefix = $wpdb->prefix;
        $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts" . " WHERE guid='%s';", $image_url ));
        
        //try the other proto (https - http) if full urls are used
        if ( empty($attachment_id) && strpos($image_url, 'http://') === 0 ) {
            $image_url_other_proto =  strpos($image_url, 'https') === 0 ? 
                str_replace('https://', 'http://', $image_url) :
                str_replace('http://', 'https://', $image_url);
            $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts" . " WHERE guid='%s';", $image_url_other_proto ));
        }        
        
        //try using only path
        if (empty($attachment_id) ) {
            $image_path = parse_url($image_url, PHP_URL_PATH); //some sites have different domains in posts guid (site changes, etc.)
            $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts" . " WHERE guid like'%%%s';", $image_path ));
        }        
        
        //try using the initial URL
        if ( empty($attachment_id) ) {
            $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts" . " WHERE guid='%s';", $original_image_url ));
        }
        return !empty($attachment_id) ? $attachment_id[0] : false;
    }
    
}