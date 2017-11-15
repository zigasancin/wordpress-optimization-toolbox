<?php

class ShortPixelQueue {
    
    private $ctrl;
    private $settings;
    
    const BULK_TYPE_OPTIMIZE = 0;
    const BULK_TYPE_RESTORE = 1;
    const BULK_TYPE_CLEANUP = 2;
    
    const BULK_NEVER = 0; //bulk never ran
    const BULK_RUNNING = 1; //bulk is running
    const BULK_PAUSED = 2; //bulk is paused
    const BULK_FINISHED = 3; //bulk finished
    
    public function __construct($controller, $settings) {
        $this->ctrl = $controller;
        $this->settings = $settings;
    //init the option if needed
        if(   !isset($_SESSION["wp-short-pixel-priorityQueue"]) //session is not defined
           || !(is_admin() && function_exists("is_user_logged_in") && is_user_logged_in())) { //or we're not in the admin - re-init each time
            //take the priority list from the options (we persist there the priority IDs from the previous session)
            $prioQueueOpt = $this->settings->getOpt( 'priorityQueue', array());//here we save the IDs for the files that need to be processed after an image upload for example
            $_SESSION["wp-short-pixel-priorityQueue"] = array();
            foreach($prioQueueOpt as $ID) {
                if(ShortPixelMetaFacade::isCustomQueuedId($ID)) {
                    $meta = $this->ctrl->getSpMetaDao()->getMeta(ShortPixelMetaFacade::stripQueuedIdType($ID));
                    $todo = isset($meta) && ($meta->getStatus() == 0 || $meta->getStatus() == 1);
                } else {
                    $meta = wp_get_attachment_metadata($ID);
                    $todo = !isset($meta['ShortPixelImprovement']);
                }
                WPShortPixel::log("INIT: Item $ID from options has metadata: " .json_encode($meta));
                if($todo) {
                    $this->push($ID);
                }
            }
            $this->settings->priorityQueue = $_SESSION["wp-short-pixel-priorityQueue"];
            
            if(is_admin() && function_exists("is_user_logged_in") && is_user_logged_in()) {
                WPShortPixel::log("INIT: Session queue not found, updated from Options with "
                             .json_encode($_SESSION["wp-short-pixel-priorityQueue"]));
            }
        }
    }
    
    //handling older
    public function ShortPixelQueue($controller) {
        $this->__construct($controller);
    }

    public function get() {
        return $_SESSION["wp-short-pixel-priorityQueue"];//get_option("wp-short-pixel-priorityQueue");
    }
    
    public function skip($id) {
        if(is_array($this->settings->prioritySkip)) {
            $this->settings->prioritySkip = array_merge($this->settings->prioritySkip, array($id));
        } else {
            $this->settings->prioritySkip = array($id);
        }            
    }
    
    public function allSkipped() {
        if( !is_array($this->settings->prioritySkip) ) return false;
        count(array_diff($_SESSION["wp-short-pixel-priorityQueue"], $this->settings->prioritySkip));
    }
    
    public function skippedCount() {
        return is_array($this->settings->prioritySkip) ? count($this->settings->prioritySkip) : 0; 
    }
    
    public function isSkipped($id) {
        return is_array($this->settings->prioritySkip) && in_array($id, $this->settings->prioritySkip);
    }
    
    public function isPrio($id) {
        return is_array($_SESSION["wp-short-pixel-priorityQueue"]) && in_array($id, $_SESSION["wp-short-pixel-priorityQueue"]);
    }
    
    public function getSkipped() {
        return $this->settings->prioritySkip;
    }
    
    public function reverse() {
        $this->settings->priorityQueue = $_SESSION["wp-short-pixel-priorityQueue"] = array_reverse($_SESSION["wp-short-pixel-priorityQueue"]);

    }
    
    public function push($ID)//add an ID to priority queue
    {
        $priorityQueue = $_SESSION["wp-short-pixel-priorityQueue"]; //get_option("wp-short-pixel-priorityQueue");
        WPShortPixel::log("PUSH: Push ID $ID into queue ".json_encode($priorityQueue));
        array_push($priorityQueue, $ID);
        $prioQ = array_unique($priorityQueue);
        $_SESSION["wp-short-pixel-priorityQueue"] = $prioQ;
        //push also to the options queue, in case the session gets killed retrieve from there
        $this->settings->priorityQueue = $prioQ;

        WPShortPixel::log("PUSH: Updated: ".json_encode($_SESSION["wp-short-pixel-priorityQueue"]));//get_option("wp-short-pixel-priorityQueue")));
    }

    public function enqueue($ID)//add an ID to priority queue as LAST
    {
        $priorityQueue = $_SESSION["wp-short-pixel-priorityQueue"]; //get_option("wp-short-pixel-priorityQueue");
        WPShortPixel::log("PUSH: Push ID $ID into queue ".json_encode($priorityQueue));
        array_unshift($priorityQueue, $ID);
        $prioQ = array_unique($priorityQueue);
        $_SESSION["wp-short-pixel-priorityQueue"] = $prioQ;
        //push also to the options queue, in case the session gets killed retrieve from there
        $this->settings->priorityQueue = $prioQ;

        WPShortPixel::log("ENQUEUE: Updated: ".json_encode($_SESSION["wp-short-pixel-priorityQueue"]));//get_option("wp-short-pixel-priorityQueue")));
    }

    public function getFirst($count = 1)//return the first values added to priority queue
    {
        $priorityQueue = $_SESSION["wp-short-pixel-priorityQueue"];//self::getOpt("wp-short-pixel-priorityQueue", array());
        $count = min(count($priorityQueue), $count);
        return(array_slice($priorityQueue, count($priorityQueue) - $count, $count));
    }
    
    public function getFromPrioAndCheck() {
        $ids = array();
        $removeIds = array();
        
        $idsPrio = $this->get();
        for($i = count($idsPrio) - 1, $cnt = 0; $i>=0 && $cnt < 3; $i--) {
            if(!isset($idsPrio[$i])) continue; //saw this situation but then couldn't reproduce it to see the cause, so at least treat the effects.
            $id = $idsPrio[$i];
            if(!$this->isSkipped($id) && $this->ctrl->isValidMetaId($id)) {
                $ids[] = $id; //valid ID
                $cnt++;
            } elseif(!$this->isSkipped($id)) {
                $removeIds[] = $id;//not skipped, url not found, means it's absent, to remove
            }
        }
        foreach($removeIds as $rId){
            WPShortPixel::log("HIP: Unfound ID $rId Remove from Priority Queue: ".json_encode($this->get()));
            $this->remove($rId);
        }
        return $ids;
    }    

    public function remove($ID)//remove an ID from priority queue
    {
        $priorityQueue = $_SESSION["wp-short-pixel-priorityQueue"];//get_option("wp-short-pixel-priorityQueue");
        WPShortPixel::log("REM: Remove ID $ID from queue ".json_encode($priorityQueue));
        $newPriorityQueue = array();
        $found = false;
        foreach($priorityQueue as $item) {
            if($item != $ID) {
                $newPriorityQueue[] = $item;
            } else {
                $found = true;
            }
        }
        //$this->settings->setOpt("wp-short-pixel-priorityQueue", $newPriorityQueue);
        $_SESSION["wp-short-pixel-priorityQueue"] = $newPriorityQueue;
        WPShortPixel::log("REM: " . ($found ? "Updated: " : "Not found") . json_encode($_SESSION["wp-short-pixel-priorityQueue"]));//get_option("wp-short-pixel-priorityQueue")));
        return $found;
    }
    
    public function removeFromFailed($ID) {
        $failed = explode(",", $this->settings->failedImages);
        $key = array_search($ID, $failed);
        if($key !== false) {
            unset($failed[$key]);
            $failed = array_values($failed);
            $this->settings->failedImages = implode(",", $failed) ;
        }        
    }
    
    public function addToFailed($ID) {
        $failed = $this->settings->failedImages;
        if(!in_array($ID, explode(",", $failed))) {
            $this->settings->failedImages = (strlen($failed) ? $failed . "," : "") . $ID;
        }                        
    }

    public function getFailed() {
        $failed = $this->settings->failedImages;
        if(!strlen($failed)) return array();
        $ret = explode(",", $failed);
        $fails = array();
        foreach($ret as $fail) { 
            if(ShortPixelMetaFacade::isCustomQueuedId($fail)) {
                $meta = $this->ctrl->getSpMetaDao()->getMeta(ShortPixelMetaFacade::stripQueuedIdType($fail));
                if($meta) {
                    $fails[] = (object)array("id" => ShortPixelMetaFacade::stripQueuedIdType($fail), "type" => ShortPixelMetaFacade::CUSTOM_TYPE, "meta" => $meta);                    
                }
            } else {
                $meta = wp_get_attachment_metadata($fail);
                if(!$meta || (isset($meta["ShortPixelImprovement"]) && is_numeric($meta["ShortPixelImprovement"]))){
                    $this->removeFromFailed($fail);
                } else {
                    $fails[] = (object)array("id" => $fail, "type" => ShortPixelMetaFacade::MEDIA_LIBRARY_TYPE, "meta" => $meta);
                }
            }
        }
        return $fails;
    }

    public function bulkRunning() {
        //$bulkProcessingStatus = get_option('bulkProcessingStatus');
        return $this->settings->startBulkId > $this->settings->stopBulkId;
    }
    
    public function bulkPaused() {
        //WPShortPixel::log("Bulk Paused: " . $this->settings->cancelPointer);
        return $this->settings->cancelPointer;
    }
    
    public function bulkRan() {
        return $this->settings->bulkEverRan != 0;
    }
    
    public function  processing() {
        //WPShortPixel::log("QUEUE: processing(): get:" . json_encode($this->get()));
        return $this->bulkRunning() || count($this->get());
    }
    
    public function getFlagBulkId() {
        return $this->settings->flagId;
    }

    public function getStartBulkId() {
        return $this->settings->startBulkId;
    }

    public function resetStartBulkId() {
        $this->setStartBulkId(ShortPixelMetaFacade::getMaxMediaId());
    }
    
    public function setStartBulkId($start){
        $this->settings->startBulkId = $start;
    }

    public function getStopBulkId() {
        return $this->settings->stopBulkId;
    }

    public function resetStopBulkId() {
        $this->settings->stopBulkId = ShortPixelMetaFacade::getMinMediaId();
    }
    
    public function setBulkPreviousPercent() {
        //processable and already processed
        $res = WpShortPixelMediaLbraryAdapter::countAllProcessableFiles($this->settings->optimizePdfs, $this->getFlagBulkId(), $this->settings->stopBulkId);
        $this->settings->bulkCount = $res["mainFiles"];
        
        //if compression type changed, add also the images with the other compression type
        switch (0 + $this->ctrl->getCompressionType()) {
            case 2:
                $this->settings->bulkAlreadyDoneCount =  $res["mainProcessedFiles"] - $res["mainProcLossyFiles"] - $res["mainProcLosslessFiles"];
                break;
            case 1:
                $this->settings->bulkAlreadyDoneCount =  $res["mainProcessedFiles"] - $res["mainProcGlossyFiles"] - $res["mainProcLosslessFiles"];
                break;
            default: //lossless
                $this->settings->bulkAlreadyDoneCount =  $res["mainProcessedFiles"] - $res["mainProcLossyFiles"] - $res["mainProcGlossyFiles"];
                break;
                
        }
        //$this->settings->bulkAlreadyDoneCount =  $res["mainProcessedFiles"] - $res["mainProc".((0 + $this->ctrl->getCompressionType() == 1) ? "Lossless" : "Lossy")."Files"];

        // if the thumbnails are to be processed, add also the images that have thumbs not processed
        if($this->settings->processThumbnails) {
            $this->settings->bulkAlreadyDoneCount -= $res["mainUnprocessedThumbs"];
        }
        
        //percent already done
        $this->settings->bulkPreviousPercent =  round($this->settings->bulkAlreadyDoneCount / ($this->settings->bulkCount ? $this->settings->bulkCount : 1) * 100);
    }
    
    public function getBulkToProcess() {
        return $this->settings->bulkCount - $this->settings->bulkAlreadyDoneCount;
    }
    
    public function flagBulkStart() {
        $this->settings->flagId = $this->settings->startBulkId;
        $this->settings->bulkProcessingStatus = 'running';//set bulk flag        
    }
    
    public function setBulkType($type) {
        $this->settings->bulkType = $type;
    }
    
    public function getBulkType() {
        return $this->settings->bulkType;
    }
    
    public function startBulk($type = self::BULK_TYPE_OPTIMIZE) {
        $this->resetStartBulkId(); //start downwards from the biggest item ID            
        $this->resetStopBulkId();
        $this->flagBulkStart(); //we use this to detect new added files while bulk is running            
        $this->setBulkPreviousPercent();
        $this->resetBulkCurrentlyProcessed();
        $this->setBulkType($type);
        $this->settings->bulkEverRan = 1;
    }
    
    public function pauseBulk() {
        $cancelPointer = $this->settings->startBulkId;
        $bulkStartId = $this->getFlagBulkId();
        $this->settings->cancelPointer = $cancelPointer;//we save this so we can resume bulk processing
        $this->settings->skipToCustom = NULL;
        WPShortPixel::log("PAUSE: Pointer = ".$this->settings->cancelPointer);
        //remove the bulk items from prio queue
        foreach($this->get() as $qItem) {
            if($qItem < $bulkStartId) {
                $this->remove($qItem);
            }
        }
        $this->stopBulk();
    }
    
    public function cancelBulk() {
        $this->pauseBulk();
        WPShortPixel::log("STOP, delete pointer.");
        $this->settings->cancelPointer = NULL;
    }
    
    public function stopBulk() {
        $this->settings->startBulkId = ShortPixelMetaFacade::getMaxMediaId();
        $this->settings->stopBulkId = $this->settings->startBulkId;
        $this->settings->bulkProcessingStatus = null;
        return $this->settings->bulkEverRan;
    }
    
    public function resumeBulk() {
        $this->settings->startBulkId = $this->settings->cancelPointer;
        $this->settings->stopBulkId = ShortPixelMetaFacade::getMinMediaId();
        //$this->settings->setOpt("wp-short-pixel-flag-id", $this->startBulkId);//we use to detect new added files while bulk is running
        $this->settings->bulkProcessingStatus = 'running';//set bulk flag    
        $this->settings->cancelPointer = null;
        WPShortPixel::log("Resumed: (pause says: " . $this->bulkPaused() . ") Start from: " . $this->settings->startBulkId . " to " . $this->settings->stopBulkId);
    }
    
    public function resetBulkCurrentlyProcessed() {
        $this->settings->bulkCurrentlyProcessed = 0;
    }
    
    public function incrementBulkCurrentlyProcessed() {
        $this->settings->bulkCurrentlyProcessed = $this->settings->bulkCurrentlyProcessed + 1;
    }
    
    public function markBulkComplete() {
        $this->settings->bulkProcessingStatus = null;
        $this->settings->cancelPointer = null;
    }
    
    public static function resetBulk() {
        delete_option('wp-short-pixel-bulk-type');        
        delete_option('bulkProcessingStatus');        
        delete_option( 'wp-short-pixel-cancel-pointer');
        delete_option( "wp-short-pixel-flag-id");
        $startBulkId = $stopBulkId = ShortPixelMetaFacade::getMaxMediaId();
        update_option( 'wp-short-pixel-query-id-stop', $startBulkId, 'no');
        update_option( 'wp-short-pixel-query-id-start', $startBulkId, 'no');                    
        delete_option( "wp-short-pixel-bulk-previous-percent");
        delete_option( "wp-short-pixel-bulk-processed-items");
        delete_option('wp-short-pixel-bulk-running-time');
        delete_option('wp-short-pixel-last-bulk-start-time');
        delete_option('wp-short-pixel-last-bulk-success-time');
        delete_option( "wp-short-pixel-bulk-processed-items");
        delete_option( "wp-short-pixel-bulk-count");
        delete_option( "wp-short-pixel-bulk-done-count");
    }
    
    public static function resetPrio() {
        delete_option( "wp-short-pixel-priorityQueue");
        if(isset($_SESSION["wp-short-pixel-priorityQueue"])){
            unset($_SESSION["wp-short-pixel-priorityQueue"]);   
        }
    }    
    
    public function logBulkProgress() {
        $t = time();
        $this->incrementBulkCurrentlyProcessed();
        $successTime = $this->settings->lastBulkSuccessTime;
        if($t - $successTime > 120) { //if break longer than two minutes we mark a pause in the bulk
            $this->settings->bulkRunningTime += ($successTime - $this->settings->lastBulkStartTime);
            $this->settings->lastBulkStartTime = $t;
            $this->settings->lastBulkSuccessTime = $t;
        } else {
            $this->settings->lastBulkSuccessTime = $t;
        }
    }
    
    public function getBulkPercent() {
        $previousPercent = $this->settings->bulkPreviousPercent;
        //WPShortPixel::log("QUEUE - BulkPrevPercent: " . $previousPercent . " BulkCurrentlyProcessing: "
        //        . $this->settings->bulkCurrentlyProcessed . " out of " . $this->getBulkToProcess());
        
        if($this->getBulkToProcess() <= 0) return ($this->processing () ? 99: 100);
        // return maximum 99%
        $percent = $previousPercent + round($this->settings->bulkCurrentlyProcessed / $this->getBulkToProcess()
                                              * (100 - $previousPercent));

        //WPShortPixel::log("QUEUE - Calculated Percent: " . $percent);
        
        return min(99, $percent);
    }

    public function getDeltaBulkPercent() {
        return $this->getBulkPercent() - $this->settings->bulkPreviousPercent;
    }
    
    public function getTimeRemaining (){
        $p = $this->getBulkPercent();
        $pAlready = $this->settings->bulkCount == 0 ? 0 : round($this->settings->bulkAlreadyDoneCount / $this->settings->bulkCount * 100);
//        die("" . ($this->lastBulkSuccessTime - $this->lastBulkStartTime));
        if(($p - $pAlready) == 0) return 0;
        return round(((100 - $p) / ($p - $pAlready)) * ($this->settings->bulkRunningTime + $this->settings->lastBulkSuccessTime - $this->settings->lastBulkStartTime)/60);
    }
}
