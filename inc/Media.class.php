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

         case 'queue-get':
            // what is in the queue for this room?
            $response = $this->queue_get();
            if(!isset($response) || !is_array($response)) {
               $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No queue items found");
               $response = array(
                  'response' => 'no_queue',
                  'message' => 'No media in the queue'
               );
               break;
            }
            else {
               $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Queue items found and sent to caller");
               break;
            }
            break;

         case 'first-play':
            // is there anything that is playing right now?
            $start_time = $this->time_from_media_start();
            $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Start time: ${start_time}");
            if(isset($start_time) && $start_time > 0) {
               // see what it is
               $queue = $this->queue_get(true);
               $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Response: '" . print_r($queue, true) . "'");
               if(isset($queue) && is_array($queue)) {
                  $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "There is something playing here");
                  $queue[0]['media_url'] .= "&start=${start_time}";
                  $response = $queue[0];
                  $response['response'] = 'ok';
                  $response['room_guid'] = $this->parameters['room_guid'];
                  break;
               }
            }

            // is there anything in the queue that hasn't been played yet?
            $media_info = $this->get_next_media();
            if(isset($media_info['media_url']) && strlen($media_info['media_url'] > 0)) {
               $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "media_url: {$media_info['media_url']}");
               $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Next unplayed media object sent to caller");
               $response = $media_info;
               $response['response'] = 'ok';
               break;
            }

            // nothing is playing, and nothing is in queue that hasn't been played yet
            $response = array(
               'response' => 'no_media',
               'message' => 'Nothing playing right now'
            );
            break;

         case 'queue-can-admin':
            // pick up the user GUID from the current object
            $user_guid = $_SESSION['user_object']->get_guid();

            // the result is based on active user object in the session
            $can_admin = Room::user_can_admin($this->parameters['room_guid'], $user_guid);

            // formulate the response to the caller
            $response = array(
               'response' => ($can_admin ? 'ok' : 'error'),
               'message' => 'User may ' . ($can_admin ? 'administer' : 'not administer') . ' the queue',
               'media_guid' => (isset($this->parameters['media_guid']) ? $this->parameters['media_guid'] : false)
            );
            break;

         case 'queue-remove':
            // if user is not admin, no way can this happen
            $user_guid = $_SESSION['user_object']->get_guid();
            if(!Room::user_can_admin($this->parameters['room_guid'], $user_guid)) {
               $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "User is not a room or site admin - cannot delete queue objects");
               $response = array(
                  'response' => 'error',
                  'message' => 'User does not have moderator or administrator privileges'
               );
               break;
            }

            // check that we got a media GUID for the object
            if(!isset($this->parameters['media_guid']) || strlen($this->parameters['media_guid']) == 0) {
               $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No media GUID passed");
               $response = array(
                  'response' => 'error',
                  'message' => 'Media GUID not passed'
               );
               break;
            }

            // pass the media GUID to the function that actually handles this
            if(!$this->queue_remove($this->parameters['media_guid'])) {
               $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failure");
               $response = array(
                  'response' => 'error',
                  'message' => 'Function call failed'
               );
               break;
            }

            // it worked if we got here
            $response = array(
               'response' => 'ok',
               'message' => 'Media object removed from queue'
            );
            break;

         case 'poll':
            // what is the wait time for the next queue item to play?
            $wait_time = $this->next_play_wait();
            $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Wait time: ${wait_time}");
            if(isset($wait_time) && $wait_time > 0) {
               $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Not playing any media for ${wait_time} seconds");
               $response = array(
                  'response' => 'wait',
                  'message' => 'Wait until next play',
                  'wait_time' => $wait_time
               );
               break;
            }

            // if there is no wait time, just get the next URL and send it to the frontend
            else {
               // but first, check if there is a media URL to send
               $media_info = $this->get_next_media();
               if(!isset($media_info['media_url']) || $media_info['media_url'] === false) {
                  $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Not sending any media");
                  $response = array(
                     'response' => 'no_media',
                     'message' => 'No new media in queue'
                  );
                  break;
               }
               else {
                  $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Sending media to play");
                  $response = array(
                     'response' => 'ok',
                     'message' => 'Play this media',
                     'media_url' => $media_info['media_url'],
                     'media_guid' => $media_info['media_guid']
                  );
                  break;
               }
               break;
            }
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

      // since we only support certain types of media, do some checks for those - right now it's only YouTube
      if(strpos($media_url, 'youtube') > 0 || strpos($media_url, 'youtu.be') > 0) {
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "YouTube video URL detected - processing");

         // get the video ID
         if(strpos($media_url, 'youtube') > 0) {
            $media_id = explode("?v=", $media_url);
         }
         else if(strpos($media_url, 'youtu.be') > 0) {
            $media_id = explode("//youtu.be/", $media_url);
         }

         // if we don't have it yet, they're probably in the other format
         if(empty($media_id[1])) {
            $media_id = explode("/v/", $media_url);
         }

         // remove other parameters
         $media_id = explode("&", $media_id[1]);
         $media_id = $media_id[0];

         // build the necessary URLs for the queue and the front end
         $embed_url = "https://www.youtube.com/embed/${media_id}?&controls=0&disablekb=1&enablejsapi=1&fs=0&origin=" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off" ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . "&playsinline=1&rel=0";
         $image_url = "https://img.youtube.com/vi/${media_id}/0.jpg";
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
      $duration = $this->youtube_get_media_length($media_id);

      // store it in the database
      $user_guid = $_SESSION['user_object']->get_guid();
      $room_guid = $this->parameters['room_guid'];
      $media_guid = Common::get_guid();
      $query = "INSERT INTO media_queues (room_guid, user_guid, media_guid, media_url, when_added, duration) VALUES ('${room_guid}', '{$user_guid}', '${media_guid}', '${embed_url}', UNIX_TIMESTAMP(), '${duration}')";
      global $db;
      if(!$db->query($query)) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to add media to queue");
         return false;
      }
      else {
         // it worked - build an array and return it
         $response = array(
            'media_url' => $embed_url,
            'image_url' => $image_url,
            'media_guid' => $media_guid
         );
         return $response;
      }
   }

   function queue_remove($media_guid) {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // build the query for the database
      $room_guid = $this->parameters['room_guid'];
      $query = "DELETE FROM media_queues WHERE room_guid = '${room_guid}' AND media_guid = '${media_guid}'";
      global $db;
      if(!$db->query($query)) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database call failure (or media GUID did not exist in the database)");
         return false;
      }
      else {
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Media removed from database");
         return true;
      }
   }

   function queue_get($first_play = false) {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // pick up the room GUID from the parameters passed
      $room_guid = $this->parameters['room_guid'];

      // see if there are any queue items for this room
      if($first_play) {
         $query = "SELECT media_url, media_guid FROM media_queues WHERE room_guid = '${room_guid}' AND when_played IS NOT NULL ORDER BY when_played DESC LIMIT 1";
      }
      else {
         $query = "SELECT media_url, media_guid FROM media_queues WHERE room_guid = '${room_guid}' AND when_played IS NULL ORDER BY when_added ASC";
      }
      global $db;
      $result = $db->query($query);

      // check that we even have results
      if(!isset($result) || !is_array($result)) {
         $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "No media objects found in queue");
         return false;
      }

      // the client is going to want image URLs to show in the queue, so let's put those PHP string manipulation functions to good use!
      $out = array();
      for($i = 0; $i < count($result); $i++) {
         $temp_url = explode('?', $result[$i]['media_url']);
         $temp_url = str_replace('embed', 'vi', $temp_url[0]);
         $temp_url = str_replace('www', 'img', $temp_url);
         $temp_url .= '/0.jpg';
         $out[$i]['image_url'] = $temp_url;
         $out[$i]['media_url'] = $result[$i]['media_url'];
         $out[$i]['media_guid'] = $result[$i]['media_guid'];
      }

      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Returning queue to caller");
      return $out;
   }

   function youtube_get_media_length($media_id) {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // craft a URL to get the contentDetails attributes of a YouTube video
      global $config;
      $request_url = 'https://www.googleapis.com/youtube/v3/videos?id=' . $media_id . '&part=contentDetails&key=' . $config->google_api_key;

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

   function get_next_media() {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // pick up the room GUID from the parameters passed
      $room_guid = $this->parameters['room_guid'];

      // what, if anything, is next in the queue?
      $query = "SELECT media_url, media_guid, when_added FROM media_queues WHERE room_guid = '${room_guid}' AND when_played IS NULL ORDER BY when_added ASC LIMIT 1";
      global $db;
      $result = $db->query($query);

      // if there's nothing in the queue, bail
      if(!isset($result) || !is_array($result)) {
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Nothing found in the queue");
         return false;
      }

      // otherwise, mark it as played in the database and return the URL
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Next media object marked as played and returned to the caller");
      $when_added = $result[0]['when_added'];
      $media_url = $result[0]['media_url'];
      $media_guid = $result[0]['media_guid'];
      $query = "UPDATE media_queues SET when_played = " . time() . " WHERE room_guid = '${room_guid}' AND media_guid = '${media_guid}' AND when_added = '${when_added}'";
      if(!$db->query($query)) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database call failure");
      }
      return array(
         'media_url' => $media_url,
         'media_guid' => $media_guid
      );
   }

   // if nothing is playing, this will return false, but if something is playing, then it will return the number of seconds that the caller should wait until playing the next item from the queue
   function next_play_wait() {
      // log that we are here
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // pick up the room GUID from the parameters passed
      $room_guid = $this->parameters['room_guid'];

      // check the database for the latest media object that was added
      $query = "SELECT when_played, duration FROM media_queues WHERE room_guid = '${room_guid}' AND when_played IS NOT NULL ORDER BY when_played DESC LIMIT 1";
      global $db;
      $result = $db->query($query);

      // if there are no results, bail (return zero)
      if(!isset($result) || !is_array($result)) {
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Nothing found in the queue");
         return 0;
      }

      // do some math to determine whether the media that was found would still be playing
      $next_play = $result[0]['when_played'] + $result[0]['duration'];
      $wait_time = $next_play - time();
      if($wait_time <= 0) {
         // getting here means that nothing is playing, so we tell the caller to go for it
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Nothing is currently playing in this room");
         return 0;
      }
      else {
         // getting here means that something is playing, so we pass the wait time to the caller
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Something is playing in this room that will be finished in ${wait_time} seconds");
         return $wait_time;
      }
   }

   function time_from_media_start() {
      global $logger;
      $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

      // pick up the room GUID from the parameters passed
      $room_guid = $this->parameters['room_guid'];

      // check the database for the latest media object that might be playing
      $query = "SELECT media_url, when_played, duration FROM media_queues WHERE room_guid = '${room_guid}' AND when_played IS NOT NULL ORDER BY when_played DESC LIMIT 1";
      global $db;
      $result = $db->query($query);
      $logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "when_played: {$result[0]['when_played']} - current timestamp: " . time());

      // if there are no results, then nothing has ever been played here
      if(!isset($result) || !is_array($result)) {
         $logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Possible database query failure (but proceeding with a false return anyway)");
         return false;
      }

      // if when_played is null, nothing is playing, so return false
      if(!isset($result[0]['when_played']) || $result[0]['when_played'] === null) {
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No media is playing right now");
         return false;
      }

      // do some math to determine whether the media that was found would still be playing
      $start_time = time() - $result[0]['when_played'];
      if($start_time >= $result[0]['duration']) {
         // getting here means that nothing is playing, so we tell the caller to go for it
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Nothing is currently playing in this room");
         return false;
      }
      else {
         // getting here means that something is playing, so we pass the wait time to the caller
         $logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Something is playing in this room that will be finished in ${start_time} seconds");
         return $start_time;
      }
   }
}
