<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// includes needed
global $config;
require_once $config->app_base_dir . '/inc/Module.class.php';

// class definition
class Media extends Module {
   // functions
   function process_action() {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // check that the action being called is "chat"
      if($this->action != 'media') {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Incorrect action for this module");
         $response = array(
            'response' => 'error',
            'message' => 'Internal request error'
         );
         echo json_encode($response);
         return false;
      }

      // do a sanity check for the presence of secondary and parameters
      if(!isset($this->secondary) || !isset($this->parameters)) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Missing secondary and/or parameters");
         $response = array(
            'response' => 'error',
            'message' => 'Missing request data'
         );
         echo json_encode($response);
         return false;
      }

      // we need a room GUID or else it doesn't fly
      if(!isset($this->parameters['room_guid'])) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Missing room GUID in request");
         $response = array(
            'response' => 'error',
            'message' => 'Missing room GUID'
         );
         echo json_encode($response);
         return false;
      }

      // switch on secondary
      switch($this->secondary) {
         case 'queue-add':
            // check for more specific parameters
            if(!isset($this->parameters['media_url'])) {
               $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Missing media URL in request");
               $response = array(
                  'response' => 'error',
                  'message' => 'Missing media URL'
               );
               break;
            }

            // call the function to determine whether it's a valid media URL and throw it in the queue
            $response = $this->queue_add($this->parameters['media_url']);
            if(!isset($response) || !is_array($response)) {
               $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to add media URL to queue");
               $response = array(
                  'response' => 'error',
                  'message' => 'Failed to add media URL to queue'
               );
               break;
            }

            // it works if it gets here
            $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Media added to queue");
            $response['response'] = 'ok';
            break;

         default:
            break;
      }

      // echo the response back to the caller
      echo json_encode($response);
   }

   function queue_add($media_url) {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // see if the media even exists
      if(!$this->is_url_valid($media_url)) {
         return false;
      }

      // since we only support certain types of media, do some checks for those - right now it's only YouTube
      if(strpos($media_url, 'youtube') > 0 || strpos($media_url, 'youtu.be') > 0) {
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "YouTube video URL detected - processing");

         // get the video ID
         $video_id = explode("?v=", $media_url);

         // if we don't have it yet, they're probably in the other format
         if(empty($video_id[1])) {
            $video_id = explode("/v/", $media_url);
         }

         // remove other parameters
         $video_id = explode("&", $video_id[1]);
         $video_id = $video_id[0];

         // build the necessary URLs for the queue and the front end
         $embed_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&controls=0&disablekb=1&fs=0&origin=' . ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off" ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . '&playsinline=1&rel=0';
         $image_url = 'https://img.youtube.com/vi/' . $video_id . '/0.jpg';
      }

      // otherwise, we don't support the request
      else {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Media URL not supported (yet)");
         return false;
      }

      // store it in the database
      $user_guid = $_SESSION['user_object']->get_guid();
      $room_guid = $this->parameters['room_guid'];
      $query = "INSERT INTO media_queues (room_guid, user_guid, media_url) VALUES ('${room_guid}', '{$user_guid}', '${embed_url}')";
      global $db;
      if(!$db->query($query)) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to add media to queue");
         return false;
      }
      else {
         // it worked - build an array and return it
         $response = array(
            'media_url' => $embed_url,
            'image_url' => $image_url
         );
         return $response;
      }
   }

   function is_url_valid($url) {
      // log the function call
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // get the headers
      $headers = get_headers($url);

      // if this function comes back false, the URL isn't there
      if($headers === false) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Resource doesn't exist");
         return false;
      }

      // actually parse the headers for the response code
      foreach($headers as $header) {
         // corrects $url when 301/302 redirect(s) lead(s) to 200:
         if(preg_match("/^Location: (http.+)$/",$header,$m)) {
            $url=$m[1];
         }

         // grabs the last $header $code, in case of redirect(s):
         if(preg_match("/^HTTP.+\s(\d\d\d)\s/",$header,$m)) {
            $code=$m[1];
         }
      }

      // check the return code
      if($code == 200) {
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Resource exists");
         return true;
      }
      else {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Resource returned error code ${code}");
         return false;
      }
   }
}
