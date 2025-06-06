<?php
namespace ShortPixel\Controller\Queue;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

use ShortPixel\ShortQ\ShortQ as ShortQ;
use ShortPixel\ShortPixelLogger\ShortPixelLogger as Log;
use ShortPixel\Model\Image\ImageModel as ImageModel;

class CustomQueue extends Queue
{

   protected $cacheName = 'CustomCache'; // When preparing, write needed data to cache.

   protected static $instance;

   public function __construct($queueName = 'Custom')
   {
     $shortQ = new ShortQ(static::PLUGIN_SLUG);
     $this->q = $shortQ->getQueue($queueName);
     $this->queueName = $queueName;

      $options = array(
         'numitems' => 5,
         'mode' => 'wait',
         'process_timeout' => 7000,
         'retry_limit' => 20,
         'enqueue_limit' => 120,
      );

     $options = apply_filters('shortpixel/customqueue/options', $options);
     $this->q->setOptions($options);
   }

   public function getType()
   {
      return 'custom';
   }


   protected function prepare()
   {
      $items = $this->queryItems();

      return $this->prepareItems($items);
   }

   protected function prepareBulkRestore()
   {
      $items = $this->queryOptimizedItems();

      return $this->prepareItems($items);
   }

   private function queryItems()
   {
     $last_id = $this->getStatus('last_item_id');
     $limit = $this->q->getOption('enqueue_limit');
     $prepare = array();
     $items = array();
     $fastmode = apply_filters('shortpixel/queue/fastmode', false);


     global $wpdb;

     $folderSQL = ' SELECT id FROM ' . $wpdb->prefix . 'shortpixel_folders where status >= 0 ';
     $folderRow = $wpdb->get_col($folderSQL);

     // No Active Folders, No Items.
     if (count($folderRow) == 0)
       return $items;

     // List of prepared (%d) for the folders.
     $query_arr = join( ',', array_fill( 0, count( $folderRow ), '%d' ) );

     $sql = 'SELECT id FROM ' . $wpdb->prefix . 'shortpixel_meta WHERE folder_id in ( ';

     $sql .= $query_arr . ') ';
     // Query anything else than success, since that is done.
     $prepare = array_merge($prepare, $folderRow);

      if (true === $fastmode)
      {
         $sql .= ' AND status <> %d';
         $prepare[] = ImageModel::FILE_STATUS_SUCCESS;
      }

     if ($last_id > 0)
     {
        $sql .= " AND id < %d ";
        $prepare [] = intval($last_id);
     }


     $sql .= ' order by id DESC LIMIT %d ';
     $prepare[] = $limit;

     $sql = $wpdb->prepare($sql, $prepare);

     $results = $wpdb->get_col($sql);

     foreach($results as $item_id)
     {
          $items[] = $item_id; //$fs->getImage($item_id, 'custom');
     }

     return array_filter($items);
   }

   private function queryOptimizedItems()
   {
     global $wpdb;

     $last_id = $this->getStatus('last_item_id');
     $limit = $this->q->getOption('enqueue_limit');
     $prepare = [];
     $items = [];

     $sql = 'SELECT id FROM ' . $wpdb->prefix . 'shortpixel_meta WHERE status = %d  ';
     $prepare[] = ImageModel::FILE_STATUS_SUCCESS;

     if ($last_id > 0)
     {
        $sql .= " AND id < %d ";
        $prepare [] = intval($last_id);
     }

     $sql .= ' order by id DESC limit %d';
     $prepare[] = $limit;

     $sql = $wpdb->prepare($sql, $prepare);

     $results = $wpdb->get_col($sql);

     foreach($results as $item_id)
     {
        $items[] = $item_id;
     }

     return array_filter($items);
   }


} // class
