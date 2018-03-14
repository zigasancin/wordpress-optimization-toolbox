<?php
if ( !function_exists( 'download_url' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class ShortPixelAPI {
    
    const STATUS_SUCCESS = 1;
    const STATUS_UNCHANGED = 0;
    const STATUS_ERROR = -1;
    const STATUS_FAIL = -2;
    const STATUS_QUOTA_EXCEEDED = -3;
    const STATUS_SKIP = -4;
    const STATUS_NOT_FOUND = -5;
    const STATUS_NO_KEY = -6;
    const STATUS_RETRY = -7;
    const STATUS_QUEUE_FULL = -404;
    const STATUS_MAINTENANCE = -500;
    
    const ERR_FILE_NOT_FOUND = -2;
    const ERR_TIMEOUT = -3;
    const ERR_SAVE = -4;
    const ERR_SAVE_BKP = -5;
    const ERR_INCORRECT_FILE_SIZE = -6;
    const ERR_DOWNLOAD = -7;
    const ERR_PNG2JPG_MEMORY = -8;
    const ERR_POSTMETA_CORRUPT = -9;
    const ERR_UNKNOWN = -999;

    private $_settings;
    private $_maxAttempts = 10;
    private $_apiEndPoint;
    private $_apiDumpEndPoint;


    public function __construct($settings) {
        $this->_settings = $settings;
        $this->_apiEndPoint = $this->_settings->httpProto . '://' . SHORTPIXEL_API . '/v2/reducer.php';
        $this->_apiDumpEndPoint = $this->_settings->httpProto . '://' . SHORTPIXEL_API . '/v2/cleanup.php';
    }

    protected function prepareRequest($requestParameters, $Blocking = false) {
        $arguments = array(
            'method' => 'POST',
            'timeout' => 15,
            'redirection' => 3,
            'sslverify' => false,
            'httpversion' => '1.0',
            'blocking' => $Blocking,
            'headers' => array(),
            'body' => json_encode($requestParameters),
            'cookies' => array()
        );
        //die(var_dump($requestParameters));
        //add this explicitely only for https, otherwise (for http) it slows down the request
        if($this->_settings->httpProto !== 'https') {
            unset($arguments['sslverify']);
        }

        return $arguments;
    }

    public function doDumpRequests($URLs) {
        if(!count($URLs)) {
            return false;
        }
        return wp_remote_post($this->_apiDumpEndPoint, $this->prepareRequest(array(
                'plugin_version' => SHORTPIXEL_IMAGE_OPTIMISER_VERSION,
                'key' => $this->_settings->apiKey,
                'urllist' => $URLs
            ) ) );
    }

    /**
     * sends a compression request to the API
     * @param array $URLs - list of urls to send to API
     * @param Boolean $Blocking - true means it will wait for an answer
     * @param ShortPixelMetaFacade $itemHandler - the Facade that manages different types of image metadatas: MediaLibrary (postmeta table), ShortPixel custom (shortpixel_meta table)
     * @param int $compressionType 1 - lossy, 2 - glossy, 0 - lossless
     * @return response from wp_remote_post or error
     */
    public function doRequests($URLs, $Blocking, $itemHandler, $compressionType = false, $refresh = false) {
        
        if(!count($URLs)) {
            throw new Exception(__('Image files are missing.','shortpixel-image-optimiser'));
        }
        
        $requestParameters = array(
            'plugin_version' => SHORTPIXEL_IMAGE_OPTIMISER_VERSION,
            'key' => $this->_settings->apiKey,
            'lossy' => $compressionType === false ? $this->_settings->compressionType : $compressionType,
            'cmyk2rgb' => $this->_settings->CMYKtoRGBconversion,
            'keep_exif' => ($this->_settings->keepExif ? "1" : "0"),
            'convertto' => ($this->_settings->createWebp ? urlencode("+webp") : ""),
            'resize' => $this->_settings->resizeImages ? 1 + 2 * ($this->_settings->resizeType == 'inner' ? 1 : 0) : 0,
            'resize_width' => $this->_settings->resizeWidth,
            'resize_height' => $this->_settings->resizeHeight,
            'urllist' => $URLs
        );
        if($refresh) {
            $requestParameters['refresh'] = 1;
        }

        $response = wp_remote_post($this->_apiEndPoint, $this->prepareRequest($requestParameters, $Blocking) );
        
        //only if $Blocking is true analyze the response
        if ( $Blocking )
        {
            //WpShortPixel::log("API response : " . json_encode($response));
            
            //die(var_dump(array('URL: ' => $this->_apiEndPoint, '<br><br>REQUEST:' => $this->prepareRequest($requestParameters), '<br><br>RESPONSE: ' => $response )));
            //there was an error, save this error inside file's SP optimization field
            if ( is_object($response) && get_class($response) == 'WP_Error' ) 
            {
                $errorMessage = $response->errors['http_request_failed'][0];
                $errorCode = 503;
            }
            elseif ( isset($response['response']['code']) && $response['response']['code'] <> 200 )
            {
                $errorMessage = $response['response']['code'] . " - " . $response['response']['message'];
                $errorCode = $response['response']['code'];
            }
            
            if ( isset($errorMessage) )
            {//set details inside file so user can know what happened
                $itemHandler->incrementRetries(1, $errorCode, $errorMessage);
                return array("response" => array("code" => $errorCode, "message" => $errorMessage ));
            }

            return $response;//this can be an error or a good response
        }
                
        return $response;
    }

    /**
     * parse the JSON response
     * @param $response
     * @return parsed array
     */
    public function parseResponse($response) {
        $data = $response['body'];
        $data = json_decode($data);
        return (array)$data;
    }

    /**
     * handles the processing of the image using the ShortPixel API
     * @param array $URLs - list of urls to send to API
     * @param array $PATHs - list of local paths for the images
     * @param ShortPixelMetaFacade $itemHandler - the Facade that manages different types of image metadatas: MediaLibrary (postmeta table), ShortPixel custom (shortpixel_meta table)
     * @return status/message array
     */
    public function processImage($URLs, $PATHs, $itemHandler = null) {    
        return $this->processImageRecursive($URLs, $PATHs, $itemHandler, 0);       
    }
    
    /**
     * handles the processing of the image using the ShortPixel API - cals itself recursively until success
     * @param array $URLs - list of urls to send to API
     * @param array $PATHs - list of local paths for the images
     * @param ShortPixelMetaFacade $itemHandler - the Facade that manages different types of image metadatas: MediaLibrary (postmeta table), ShortPixel custom (shortpixel_meta table)
     * @param type $startTime - time of the first call
     * @return status/message array
     */
    private function processImageRecursive($URLs, $PATHs, $itemHandler = null, $startTime = 0) 
    {    
        //WPShortPixel::log("processImageRecursive ID: " . $itemHandler->getId() . " PATHs: " . json_encode($PATHs));
        
        $PATHs = self::CheckAndFixImagePaths($PATHs);//check for images to make sure they exist on disk
        if ( $PATHs === false  || isset($PATHs['error'])) {
            $missingFiles = '';
            if(isset($PATHs['error'])) {
                foreach($PATHs['error'] as $errPath) {
                    $missingFiles .= (strlen($missingFiles) ? ', ':'') . basename(stripslashes($errPath));
                }
            }
            $msg = __('The file(s) do not exist on disk: ','shortpixel-image-optimiser') . $missingFiles;
            $itemHandler->setError(self::ERR_FILE_NOT_FOUND, $msg );
            return array("Status" => self::STATUS_SKIP, "Message" => $msg, "Silent" => $itemHandler->getType() == ShortPixelMetaFacade::CUSTOM_TYPE ? 1 : 0);
        }
        
        //tries multiple times (till timeout almost reached) to fetch images.
        if($startTime == 0) { 
            $startTime = time(); 
        }        
        $apiRetries = $this->_settings->apiRetries;
        
        if( time() - $startTime > SHORTPIXEL_MAX_EXECUTION_TIME2) 
        {//keeps track of time
            if ( $apiRetries > SHORTPIXEL_MAX_API_RETRIES )//we tried to process this time too many times, giving up...
            {
                $itemHandler->incrementRetries(1, self::ERR_TIMEOUT, __('Timed out while processing.','shortpixel-image-optimiser'));
                $this->_settings->apiRetries = 0; //fai added to solve a bug?
                return array("Status" => self::STATUS_SKIP, 
                             "Message" => ($itemHandler->getType() == ShortPixelMetaFacade::CUSTOM_TYPE ? __('Image ID','shortpixel-image-optimiser') : __('Media ID','shortpixel-image-optimiser')) 
                                         . ": " . $itemHandler->getId() .' ' . __('Skip this image, try the next one.','shortpixel-image-optimiser'));                
            }
            else
            {//we'll try again next time user visits a page on admin panel
                $apiRetries++;
                $this->_settings->apiRetries = $apiRetries;
                return array("Status" => self::STATUS_RETRY, "Message" => __('Timed out while processing.','shortpixel-image-optimiser') . ' (pass '.$apiRetries.')', 
                             "Count" => $apiRetries);
            }
        }
        
        //#$compressionType = isset($meta['ShortPixel']['type']) ? ($meta['ShortPixel']['type'] == 'lossy' ? 1 : 0) : $this->_settings->compressionType;
        $meta = $itemHandler->getMeta();
        $compressionType = $meta->getCompressionType() !== null ? $meta->getCompressionType() : $this->_settings->compressionType;
        $response = $this->doRequests($URLs, true, $itemHandler, $compressionType);//send requests to API
        
        //die(var_dump($response));
        
        if($response['response']['code'] != 200) {//response <> 200 -> there was an error apparently?
            return array("Status" => self::STATUS_FAIL, "Message" => __('There was an error and your request was not processed.', 'shortpixel-image-optimiser')
                . (isset($response['response']['message']) ? ' (' . $response['response']['message'] . ')' : ''), "Code" => $response['response']['code']);
        }

        $APIresponse = $this->parseResponse($response);//get the actual response from API, its an array
        
        if ( isset($APIresponse[0]) ) //API returned image details
        {
            foreach ( $APIresponse as $imageObject ) {//this part makes sure that all the sizes were processed and ready to be downloaded
                if ( $imageObject->Status->Code == 0 || $imageObject->Status->Code == 1  ) {
                    sleep(1);
                    return $this->processImageRecursive($URLs, $PATHs, $itemHandler, $startTime);    
                }        
            }
            
            $firstImage = $APIresponse[0];//extract as object first image
            switch($firstImage->Status->Code) 
            {
            case 2:
                //handle image has been processed
                if(!isset($firstImage->Status->QuotaExceeded)) {
                    $this->_settings->quotaExceeded = 0;//reset the quota exceeded flag
                }
                return $this->handleSuccess($APIresponse, $PATHs, $itemHandler, $compressionType);
            default:
                //handle error
                $incR = 1;
                if ( !file_exists($PATHs[0]) ) {
                    $err = array("Status" => self::STATUS_NOT_FOUND, "Message" => "File not found on disk. "
                                 . ($itemHandler->getType() == ShortPixelMetaFacade::CUSTOM_TYPE ? "Image" : "Media")
                                 . " ID: " . $itemHandler->getId(), "Code" => self::ERR_FILE_NOT_FOUND);
                    $incR = 3;
                }
                elseif ( isset($APIresponse[0]->Status->Message) ) {
                    //return array("Status" => self::STATUS_FAIL, "Message" => "There was an error and your request was not processed (" . $APIresponse[0]->Status->Message . "). REQ: " . json_encode($URLs));                
                    $err = array("Status" => self::STATUS_FAIL, "Code" => (isset($APIresponse[0]->Status->Code) ? $APIresponse[0]->Status->Code : self::ERR_UNKNOWN), 
                                 "Message" => __('There was an error and your request was not processed.','shortpixel-image-optimiser') 
                                              . " (" . $APIresponse[0]->Status->Message . ")");                
                } else {
                    $err = array("Status" => self::STATUS_FAIL, "Message" => __('There was an error and your request was not processed.','shortpixel-image-optimiser'),
                                 "Code" => (isset($APIresponse[0]->Status->Code) ? $APIresponse[0]->Status->Code : self::ERR_UNKNOWN));
                }
                
                $itemHandler->incrementRetries($incR, $err["Code"], $err["Message"]);
                $meta = $itemHandler->getMeta();
                if($meta->getRetries() >= SHORTPIXEL_MAX_FAIL_RETRIES) {
                    $meta->setStatus($APIresponse[0]->Status->Code);
                    $meta->setMessage($APIresponse[0]->Status->Message);
                    $itemHandler->updateMeta($meta);
                }
                return $err;
            }
        }
        
        if(!isset($APIresponse['Status'])) {
            WpShortPixel::log("API Response Status unfound : " . json_encode($APIresponse));
            return array("Status" => self::STATUS_FAIL, "Message" => __('Unrecognized API response. Please contact support.','shortpixel-image-optimiser'),
                         "Code" => self::ERR_UNKNOWN, "Debug" => ' (SERVER RESPONSE: ' . json_encode($response) . ')');
        } else {
            switch($APIresponse['Status']->Code) 
            {            
                case -403:
                    @delete_option('bulkProcessingStatus');
                    $this->_settings->quotaExceeded = 1;
                    return array("Status" => self::STATUS_QUOTA_EXCEEDED, "Message" => __('Quota exceeded.','shortpixel-image-optimiser'));
                    break;                
                case -404: 
                    return array("Status" => self::STATUS_QUEUE_FULL, "Message" => $APIresponse['Status']->Message);
                case -500: 
                    return array("Status" => self::STATUS_MAINTENANCE, "Message" => $APIresponse['Status']->Message);
            }

            //sometimes the response array can be different
            if (is_numeric($APIresponse['Status']->Code)) {
                return array("Status" => self::STATUS_FAIL, "Message" => $APIresponse['Status']->Message);
            } else {
                return array("Status" => self::STATUS_FAIL, "Message" => $APIresponse[0]->Status->Message);
            }
        }
    }
    
    /**
     * sets the preferred protocol of URL using the globally set preferred protocol.
     * If  global protocol not set, sets it by testing the download of a http test image from ShortPixel site. 
     * If http works then it's http, otherwise sets https
     * @param string $url
     * @param bool $reset - forces recheck even if preferred protocol is already set
     * @return url with the preferred protocol
     */
    public function setPreferredProtocol($url, $reset = false) {
        //switch protocol based on the formerly detected working protocol
        if($this->_settings->downloadProto == '' || $reset) {
            //make a test to see if the http is working
            $testURL = 'http://' . SHORTPIXEL_API . '/img/connection-test-image.png';
            $result = download_url($testURL, 10);
            $this->_settings->downloadProto = is_wp_error( $result ) ? 'https' : 'http';
        }
        return $this->_settings->downloadProto == 'http' ? 
                str_replace('https://', 'http://', $url) :
                str_replace('http://', 'https://', $url);


    }
    
    /**
     * handles the download of an optimized image from ShortPixel API
     * @param type $fileData - info about the file
     * @param int $compressionType - 1 - lossy, 2 - glossy, 0 - lossless
     * @return status/message array
     */
    private function handleDownload($fileData, $compressionType){
        //var_dump($fileData);
        if($compressionType)
        {
            $fileType = "LossyURL";
            $fileSize = "LossySize";
            $webpType = "WebPLossyURL";
            $webpSize = "WebPLossySize";
        }    
        else
        {
            $fileType = "LosslessURL";
            $fileSize = "LoselessSize";
            $webpType = "WebPLosslessURL";
            $webpSize = "WebPLosslessSize";
        }
        
        $downloadTimeout = max(ini_get('max_execution_time') - 10, 15);        
        
        $webpTempFile = "NA";
        if(isset($fileData->$webpType) && $fileData->$webpType !== "NA") {
            $webpURL = $this->setPreferredProtocol(urldecode($fileData->$webpType));
            $webpTempFile = download_url($webpURL, $downloadTimeout);
            $webpTempFile = is_wp_error( $webpTempFile ) ? "NA" : $webpTempFile;
        } 
        
        //if there is no improvement in size then we do not download this file
        if ( $fileData->OriginalSize == $fileData->$fileSize )
            return array("Status" => self::STATUS_UNCHANGED, "Message" => "File wasn't optimized so we do not download it.", "WebP" => $webpTempFile);
        
        $correctFileSize = $fileData->$fileSize;
        $fileURL = $this->setPreferredProtocol(urldecode($fileData->$fileType));
 
        $tempFile = download_url($fileURL, $downloadTimeout);
        if(is_wp_error( $tempFile )) 
        { //try to switch the default protocol
            $fileURL = $this->setPreferredProtocol(urldecode($fileData->$fileType), true); //force recheck of the protocol
            $tempFile = download_url($fileURL, $downloadTimeout);
        }    

        //on success we return this
        $returnMessage = array("Status" => self::STATUS_SUCCESS, "Message" => $tempFile, "WebP" => $webpTempFile);
        
        if ( is_wp_error( $tempFile ) ) {
            @unlink($tempFile);
            $returnMessage = array(
                "Status" => self::STATUS_ERROR, 
                "Code" => self::ERR_DOWNLOAD,
                "Message" => __('Error downloading file','shortpixel-image-optimiser') . " ({$fileData->$fileType}) " . $tempFile->get_error_message());
        } 
        //check response so that download is OK
        elseif (!file_exists($tempFile)) {
            $returnMessage = array("Status" => self::STATUS_ERROR,
                "Code" => self::ERR_FILE_NOT_FOUND,
                "Message" => __('Unable to locate downloaded file','shortpixel-image-optimiser') . " " . $tempFile);
        }
        elseif( filesize($tempFile) != $correctFileSize) {
            $size = filesize($tempFile);
            @unlink($tempFile);
            $returnMessage = array(
                "Status" => self::STATUS_ERROR,
                "Code" => self::ERR_INCORRECT_FILE_SIZE,
                "Message" => sprintf(__('Error downloading file - incorrect file size (downloaded: %s, correct: %s )','shortpixel-image-optimiser'),$size, $correctFileSize));
        }
        return $returnMessage;
    }
    
    public static function backupImage($mainPath, $PATHs) {
        //$fullSubDir = str_replace(wp_normalize_path(get_home_path()), "", wp_normalize_path(dirname($itemHandler->getMeta()->getPath()))) . '/';
        //$SubDir = ShortPixelMetaFacade::returnSubDir($itemHandler->getMeta()->getPath(), $itemHandler->getType());
        $fullSubDir = ShortPixelMetaFacade::returnSubDir($mainPath);
        $source = $PATHs; //array with final paths for these files

        if( !file_exists(SHORTPIXEL_BACKUP_FOLDER) && !@mkdir(SHORTPIXEL_BACKUP_FOLDER, 0777, true) ) {//creates backup folder if it doesn't exist
            return array("Status" => self::STATUS_FAIL, "Message" => __('Backup folder does not exist and it cannot be created','shortpixel-image-optimiser'));
        }
        //create subdir in backup folder if needed
        @mkdir( SHORTPIXEL_BACKUP_FOLDER . '/' . $fullSubDir, 0777, true);

        foreach ( $source as $fileID => $filePATH )//create destination files array
        {
            $destination[$fileID] = SHORTPIXEL_BACKUP_FOLDER . '/' . $fullSubDir . self::MB_basename($source[$fileID]);     
        }
        //die("IZ BACKUP: " . SHORTPIXEL_BACKUP_FOLDER . '/' . $SubDir . var_dump($destination));

        //now that we have original files and where we should back them up we attempt to do just that
        if(is_writable(SHORTPIXEL_BACKUP_FOLDER)) 
        {
            foreach ( $destination as $fileID => $filePATH )
            {
                if ( !file_exists($filePATH) )
                {  
                    if ( !@copy($source[$fileID], $filePATH) )
                    {//file couldn't be saved in backup folder
                        $msg = sprintf(__('Cannot save file <i>%s</i> in backup directory','shortpixel-image-optimiser'),self::MB_basename($source[$fileID]));
                        return array("Status" => self::STATUS_FAIL, "Message" => $msg);
                    }
                }
            }
            return array("Status" => self::STATUS_SUCCESS);
        } 
        else {//cannot write to the backup dir, return with an error
            $msg = __('Cannot save file in backup directory','shortpixel-image-optimiser');
            return array("Status" => self::STATUS_FAIL, "Message" => $msg);
        }
    }

    /**
     * handles a successful optimization, setting metadata and handling download for each file in the set
     * @param type $APIresponse - the response from the API - contains the optimized images URLs to download
     * @param type $PATHs - list of local paths for the files
     * @param ShortPixelMetaFacade $itemHandler - the Facade that manages different types of image metadatas: MediaLibrary (postmeta table), ShortPixel custom (shortpixel_meta table)
     * @param int $compressionType - 1 - lossy, 2 - glossy, 0 - lossless
     * @return status/message array
     */
    private function handleSuccess($APIresponse, $PATHs, $itemHandler, $compressionType) {
        $counter = $savedSpace =  $originalSpace =  $optimizedSpace =  $averageCompression = 0;
        $NoBackup = true;

        $fileType = ( $compressionType ) ? "LossySize" : "LoselessSize";
        
        //download each file from array and process it
        foreach ( $APIresponse as $fileData )
        {
            if ( $fileData->Status->Code == 2 ) //file was processed OK
            {
                if ( $counter == 0 ) { //save percent improvement for main file
                    $percentImprovement = $fileData->PercentImprovement;
                } else { //count thumbnails only
                    $this->_settings->thumbsCount = $this->_settings->thumbsCount + 1;
                }
                $downloadResult = $this->handleDownload($fileData,$compressionType);
                
                if ( $downloadResult['Status'] == self::STATUS_SUCCESS ) {
                    $tempFiles[$counter] = $downloadResult;
                } 
                //when the status is STATUS_UNCHANGED we just skip the array line for that one
                elseif( $downloadResult['Status'] == self::STATUS_UNCHANGED ) {
                    //this image is unchanged so won't be copied below, only the optimization stats need to be computed
                    $originalSpace += $fileData->OriginalSize;
                    $optimizedSpace += $fileData->$fileType;
                    $tempFiles[$counter] = $downloadResult;
                }
                else { 
                    return array("Status" => $downloadResult['Status'], "Code" => $downloadResult['Code'], "Message" => $downloadResult['Message']);
                }
                
            }    
            else { //there was an error while trying to download a file
                $tempFiles[$counter] = "";
            }
            $counter++;
        }
        
        //figure out in what SubDir files should land
        $mainPath = $itemHandler->getMeta()->getPath();

        //if backup is enabled - we try to save the images
        if( $this->_settings->backupImages )
        {
            $backupStatus = self::backupImage($mainPath, $PATHs);
            if($backupStatus == self::STATUS_FAIL) {
                $itemHandler->incrementRetries(1, self::ERR_SAVE_BKP, $backupStatus["Message"]);
                return $backupStatus;
            }
            $NoBackup = false;
        }//end backup section

        $writeFailed = 0;
        $width = $height = null;
        $resize = $this->_settings->resizeImages;
        $retinas = 0;
        $thumbsOpt = 0;
        $thumbsOptList = array();
        $webpSizes = array();
        
        if ( !empty($tempFiles) )
        {
            //overwrite the original files with the optimized ones
            foreach ( $tempFiles as $tempFileID => $tempFile )
            { 
                if(!is_array($tempFile)) continue;
                
                $targetFile = $PATHs[$tempFileID];
                $isRetina = ShortPixelMetaFacade::isRetina($targetFile);
                
                if(   ($tempFile['Status'] == self::STATUS_UNCHANGED || $tempFile['Status'] == self::STATUS_SUCCESS) && !$isRetina
                   && $targetFile !== $mainPath) {
                    $thumbsOpt++;
                    $thumbsOptList[] = self::MB_basename($targetFile);
                }
                
                if($tempFile['Status'] == self::STATUS_SUCCESS) { //if it's unchanged it will still be in the array but only for WebP (handled below)
                    $tempFilePATH = $tempFile["Message"];
                    if ( file_exists($tempFilePATH) && file_exists($PATHs[$tempFileID]) && is_writable($PATHs[$tempFileID]) ) {
                        copy($tempFilePATH, $targetFile);
                        if(ShortPixelMetaFacade::isRetina($targetFile)) {
                            $retinas ++;
                        }
                        if($resize && $itemHandler->getMeta()->getPath() == $targetFile) { //this is the main image
                            $size = getimagesize($PATHs[$tempFileID]);
                            $width = $size[0];
                            $height = $size[1];
                        }
                        //Calculate the saved space
                        $fileData = $APIresponse[$tempFileID];
                        $savedSpace += $fileData->OriginalSize - $fileData->$fileType;
                        $originalSpace += $fileData->OriginalSize;
                        $optimizedSpace += $fileData->$fileType;
                        $averageCompression += $fileData->PercentImprovement;
                        WPShortPixel::log("HANDLE SUCCESS: Image " . $PATHs[$tempFileID] . " original size: ".$fileData->OriginalSize . " optimized: " . $fileData->$fileType);

                        //add the number of files with < 5% optimization
                        if ( ( ( 1 - $APIresponse[$tempFileID]->$fileType/$APIresponse[$tempFileID]->OriginalSize ) * 100 ) < 5 ) {
                            $this->_settings->under5Percent++; 
                        }
                    } 
                    else {
                        $writeFailed++;
                    }
                    @unlink($tempFilePATH);
                }

                $tempWebpFilePATH = $tempFile["WebP"];
                if(file_exists($tempWebpFilePATH)) {
                    $targetWebPFile = dirname($targetFile) . '/' . self::MB_basename($targetFile, '.' . pathinfo($targetFile, PATHINFO_EXTENSION)) . ".webp";                
                    copy($tempWebpFilePATH, $targetWebPFile);
                    @unlink($tempWebpFilePATH);
                }
            }
            
            if ( $writeFailed > 0 )//there was an error
            {
                $msg = sprintf(__('Optimized version of %s file(s) couldn\'t be updated.','shortpixel-image-optimiser'),$writeFailed);
                $itemHandler->incrementRetries(1, self::ERR_SAVE, $msg);
                $this->_settings->bulkProcessingStatus = "error";
                return array("Status" => self::STATUS_FAIL, "Code" =>"write-fail", "Message" => $msg);
            }
        } elseif( 0 + $fileData->PercentImprovement < 5) {
            $this->_settings->under5Percent++; 
        }
        //old average counting
        $this->_settings->savedSpace += $savedSpace;
        $averageCompression = $this->_settings->averageCompression * $this->_settings->fileCount /  ($this->_settings->fileCount + count($APIresponse));
        $this->_settings->averageCompression = $averageCompression;
        $this->_settings->fileCount += count($APIresponse);
        //new average counting
        $this->_settings->totalOriginal += $originalSpace;
        $this->_settings->totalOptimized += $optimizedSpace;
        
        //update metadata for this file
        $meta = $itemHandler->getMeta();
//        die(var_dump($percentImprovement));
        if($meta->getThumbsTodo()) {
            $percentImprovement = $meta->getImprovementPercent();
        }
        $png2jpg = $meta->getPng2Jpg();
        $png2jpg = is_array($png2jpg) ? $png2jpg['optimizationPercent'] : 0;
        $meta->setMessage($originalSpace 
                ? number_format(100.0 * (1.0 - $optimizedSpace / $originalSpace), 2)
                : "Couldn't compute thumbs optimization percent. Main image: " . $percentImprovement);
        WPShortPixel::log("HANDLE SUCCESS: Image optimization: ".$meta->getMessage());
        $meta->setCompressionType($compressionType);
        $meta->setCompressedSize(@filesize($meta->getPath()));
        $meta->setKeepExif($this->_settings->keepExif);
        $meta->setTsOptimized(date("Y-m-d H:i:s"));
        $meta->setThumbsOptList(is_array($meta->getThumbsOptList()) ? array_unique(array_merge($meta->getThumbsOptList(), $thumbsOptList)) : $thumbsOptList);
        $meta->setThumbsOpt(($meta->getThumbsTodo() ||  $this->_settings->processThumbnails) ? count($meta->getThumbsOptList()) : 0);
        $meta->setRetinasOpt($retinas);
        if(null !== $this->_settings->excludeSizes) {
            $meta->setExcludeSizes($this->_settings->excludeSizes);
        }
        $meta->setThumbsTodo(false);
        //* Not yet as it doesn't seem to work... */$meta->addThumbs($webpSizes);
        if($width && $height) {
            $meta->setActualWidth($width);
            $meta->setActualHeight($height);
        }
        $meta->setRetries($meta->getRetries() + 1);
        $meta->setBackup(!$NoBackup);
        $meta->setStatus(2);
        
        $itemHandler->updateMeta($meta);
        $itemHandler->doActions();
        
        if(!$originalSpace) { //das kann nicht sein, alles klar?!
            throw new Exception("OriginalSpace = 0. APIResponse" . json_encode($APIresponse));
        }
        
        //we reset the retry counter in case of success
        $this->_settings->apiRetries = 0;
        
        return array("Status" => self::STATUS_SUCCESS, "Message" => 'Success: No pixels remained unsqueezed :-)',
            "PercentImprovement" => $originalSpace
            ? number_format(100.0 * (1.0 - (1.0 - $png2jpg / 100.0) * $optimizedSpace / $originalSpace), 2)
            : "Couldn't compute thumbs optimization percent. Main image: " . $percentImprovement);
    }//end handleSuccess
        
    /**
     * a basename alternative that deals OK with multibyte charsets (e.g. Arabic)
     * @param string $Path
     * @return string
     */
    static public function MB_basename($Path, $suffix = false){
        $Separator = " qq ";
        $qqPath = preg_replace("/[^ ]/u", $Separator."\$0".$Separator, $Path);
        if(!$qqPath) { //this is not an UTF8 string!! Don't rely on basename either, since if filename starts with a non-ASCII character it strips it off
            $fileName = end(explode(DIRECTORY_SEPARATOR, $Path));
            $pos = strpos($fileName, $suffix);
            if($pos !== false) {
                return substr($fileName, 0, $pos);
            }
            return $fileName;
        }
        $suffix = preg_replace("/[^ ]/u", $Separator."\$0".$Separator, $suffix);
        $Base = basename($qqPath, $suffix);
        $Base = str_replace($Separator, "", $Base);
        return $Base;
    }
    
    /**
     * sometimes, the paths to the files as defined in metadata are wrong, we try to automatically correct them
     * @param type $PATHs
     * @return boolean|string
     */
    static public function CheckAndFixImagePaths($PATHs){

        $ErrorCount = 0;
        $uploadDir = wp_upload_dir();
        $Tmp = explode("/", $uploadDir['basedir']);
        $TmpCount = count($Tmp);
        $StichString = $Tmp[$TmpCount-2] . "/" . $Tmp[$TmpCount-1];
        //files exist on disk?
        $missingFiles = array();
        foreach ( $PATHs as $Id => $File )
        {
            //we try again with a different path
            if ( !file_exists($File) ){
                //$NewFile = $uploadDir['basedir'] . "/" . substr($File,strpos($File, $StichString));//+strlen($StichString));
                $NewFile = $uploadDir['basedir'] . substr($File,strpos($File, $StichString)+strlen($StichString));
                if (file_exists($NewFile)) {
                    $PATHs[$Id] = $NewFile;
                } else {
	            $NewFile = $uploadDir['basedir'] . "/" . $File;
                    if (file_exists($NewFile)) {
                        $PATHs[$Id] = $NewFile;
                    } else {
                        $missingFiles[] = $File;
                        $ErrorCount++;
                    }
                }
            }
        }
        
        if ( $ErrorCount > 0 ) {
            return array("error" => $missingFiles);//false;
        } else {
            return $PATHs;
        }
    }

    static public function getCompressionTypeName($compressionType) {
        if(is_array($compressionType)) {
            return array_map(array('ShortPixelAPI', 'getCompressionTypeName'), $compressionType);
        }
        return 0 + $compressionType == 2 ? 'glossy' : (0 + $compressionType == 1 ? 'lossy' : 'lossless');
    }
    
    static public function getCompressionTypeCode($compressionName) {
        return $compressionName == 'glossy' ? 2 : ($compressionName == 'lossy' ? 1 : 0);
    }
}
