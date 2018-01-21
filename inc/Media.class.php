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

      // check that the action being called is "media"
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

         case 'poll':
            // we already have what we need - see if there's anything in queue right now
            $response = $this->poll();

            // did the request work at all?
            if(!isset($response) || !is_array($response)) {
               $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Queue polling failed");
               $response = array(
                  'response' => 'error',
                  'message' => 'Failed to add media URL to queue'
               );
               break;
            }

            // is there a video URL ready to go?

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
         $embed_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&controls=0&disablekb=1&fs=0&origin=' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off" ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . '&playsinline=1&rel=0';
         $image_url = 'https://img.youtube.com/vi/' . $video_id . '/0.jpg';
      }

      // otherwise, we don't support the request
      else {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Media URL not supported (yet)");
         return false;
      }

      // unlike standard YouTube URLs, the image URL will actually return a 404 if the video does not exist
      $headers = get_headers($image_url, 1);
      $status_line = explode(' ', $headers[0]);
      $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Status code: {$status_line[1]}");
      if($status_line[1] >= 400) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Media non-existent at the given URL");
         return false;
      }

      // get the duration in seconds
      $duration = $this->youtube_get_media_length($video_id);

      // store it in the database
      $user_guid = $_SESSION['user_object']->get_guid();
      $room_guid = $this->parameters['room_guid'];
      $query = "INSERT INTO media_queues (room_guid, user_guid, media_url, when_added, duration) VALUES ('${room_guid}', '{$user_guid}', '${embed_url}', UNIX_TIMESTAMP(), '${duration}')";
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

   function youtube_get_media_length($video_id) {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // craft a URL to get the contentDetails attributes of a YouTube video
      global $config;
      $request_url = 'https://www.googleapis.com/youtube/v3/videos?id=' . $video_id . '&part=contentDetails&key=' . $config->google_api_key;

      // need a cURL resource for this
      $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Building cURL resource");
      $curl_res = curl_init();
      $curl_parameters = array(
         CURLOPT_URL => $request_url,
         CURLOPT_HEADER => false,
         CURLOPT_RETURNTRANSFER => true
      );
      $result = curl_setopt_array($curl_res, $curl_parameters);

      // check for a non-response
      if($result === false) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "cURL call failed for YouTube resource while attempting to determine video length");
         return false;
      }

      // get the response from the JSON array that got passed back
      $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Executing cURL call");
      $result = json_decode(curl_exec($curl_res));
      if(!$result->items[0]->contentDetails->duration) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Invalid video duration");
         return false;
      }

      // parse the video duration and return it to the caller
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Returning valid duration to caller");
      $video_duration = explode('M', trim($result->items[0]->contentDetails->duration, 'PTS'));
      return ($video_duration[0] * 60) + $video_duration[1];
   }

   // if nothing is playing, this will return false, but if something is playing, then it will return the number of seconds that the caller should wait until playing the next item from the queue
   function next_play_wait() {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // pick up the room GUID from the parameters passed
      $room_guid = $this->parameters['room_guid'];

      // check the database for the latest video that was added
      $query = "SELECT media_url, when_added, duration FROM media_queues WHERE room_guid = '${room_guid}' AND when_played = NULL ORDER BY when_added DESC LIMIT 1";
      global $db;
      $result = $db->query($query);

      // if there are no results, bail
      if(!isset($result) || !is_array($result)) {
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Nothing found in the queue");
         return false;
      }

      // do some math to determine whether the media that was found would still be playing
      $next_play = $result['when_added'] + $result['duration'];
      $wait_time = $next_play - time();
      if($wait_time <= 0) {
         // getting here means that nothing is playing, so we tell the caller to go for it
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Nothing is currently playing in this room");
         return false;
      }
      else {
         // getting here means that something is playing, so we pass the wait time to the caller
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Something is playing in this room that will be finished in ${wait_time} seconds");
         return $wait_time;
      }
   }
}
