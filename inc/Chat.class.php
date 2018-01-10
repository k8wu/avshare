<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// includes needed
global $config;
if(!class_exists('Module')) {
	require $config->app_base_dir . '/inc/Module.class.php';
}

// class definition
class Chat extends Module {
	// public properties
	protected $room_guid;
	protected $users;
	protected $messages;

	// required function definition
	function process_action() {
		// tell the logger what we're doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Processing action '{$this->action}'");

		// what was the requested action?
		switch($this->action) {
			case 'chat':
				// check that there is a room GUID in the parameters before doing anything else - pretty much everything in this method requires one
				if(!isset($this->parameters) || !isset($this->parameters['room_guid'])) {
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No room GUID defined");
					$response = array(
						'response' => 'error',
						'message' => 'No room GUID defined'
					);
					break;
				}

				// switch on the secondary term
				switch($this->secondary) {
					case 'get-messages':
						if(isset($this->parameters['since'])) {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Parameter 'since' has been passed");
							$response = $this->get_messages($this->parameters['since']);
							if(!isset($response)) {
								$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No new messages (the 'since' parameter was passed");
								$response = array(
									'response' => 'no_new_messages',
									'message'=> 'No new messages'
								);
								break;
							}
						}
						else {
							$response = $this->get_messages();
							if(!isset($response)) {
								$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No messages");
								$response = array(
									'response' => 'no_messages',
									'message' =>'No messages'
								);
								break;
							}
							else {
								$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Messages successfully retrieved");
								$response = array(
									'response' => 'ok'
								);
								break;
							}
						}
						break;

					case 'send-message':
						// check if there are parameters at all
						if(!isset($this->parameters) || !is_array($this->parameters) || count($this->parameters) == 0) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No parameters passed - cannot send message");
							$response = array(
								'response' => 'error',
								'message' => 'No parameters passed'
							);
							break;
						}

						// check for message
						else if(!isset($this->parameters['message']) || strlen($parameters['message'] == 0)) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Message parameter missing - cannot send message");
							$response = array(
								'response' => 'error',
								'message' => 'Message missing'
							);
							break;
						}

						// call the function
						else {
							if(!$this->send_message($this->parameters['message'])) {
								$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failure - cannot send message");
								$response = array(
									'response' => 'error',
									'message' => 'Function call failed'
								);
								break;
							}
							else {
								$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Message successfully sent");
								break;
							}
						}
						break;

					case 'get-users':
						$response = $this->get_users();
						if(!$response) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failure - cannot retrieve users for room GUID: '{$this->room_guid}'");
							$response = array(
								'response' => 'error',
								'message' => 'Function call failure'
							);
							break;
						}
						else {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function call successful for room GUID: '{$this->room_guid}'");
							$response['response'] = 'ok';
						}
						break;

					case 'change-user-level':
						// check if there are parameters at all
						if(!isset($this->parameters) || !is_array($this->parameters) || count($this->parameters) == 0) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No parameters passed - cannot change user level");
							$response = array(
								'response' => 'error',
								'message' => 'No parameters passed'
							);
							break;
						}

						// check for user level
						else if(!isset($this->parameters['user_level']) || strlen($this->parameters['user_level'] == 0)) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "User level parameter missing - cannot change user level");
							$response = array(
								'response' => 'error',
								'message' => 'User level missing'
							);
							break;
						}

						// check for user GUID
						else if(!isset($this->parameters['user_guid']) || strlen($this->parameters['user_guid'] == 0)) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "User GUID parameter missing - cannot change user level");
							$response = array(
								'response' => 'error',
								'message' => 'User GUID missing'
							);
							break;
						}

						// call the function
						else if(!$this->user_level_change($this->parameters['user_guid'], $this->parameters['user_level'])) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failure");
							$response = array(
								'response' => 'error',
								'message' => 'Function call failure'
							);
							break;
						}

						// success!
						else {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function call successful for 'change-user-level'");
							$response = array(
								'response' => 'ok',
								'message' => 'User level changed'
							);
						}
						break;

					default:
						break;
				}
			default:
				$response = array(
					'response' => 'error',
					'message' => 'No function match'
				);
				break;
		}

		// if there is an API response, send it now
		if(isset($response) && is_array($response)) {
			return json_encode($response);
		}
	}

	// additional functions
	function get_messages($since = null) {
		// say what we're doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Retrieving messages for room with GUID '{$this->room_guid}'");

		// start building the query
		$query = "SELECT chat_messages.date_time, users.name AS user_name, chat_messages.message, chat_messages.action FROM chat_messages INNER JOIN users ON chat_messages.user_guid = users.guid WHERE chat_messages.room_guid = '{$this->room_guid}'";

		// if we just want messages since a certain date/time
		if(isset($since)) {
			// add it to the query
			$since_timestamp = date('Y-m-d H:i:s', $since);
			$query .= " AND date_time > '${since_timestamp}' ORDER BY date_time DESC";
		}

		// otherwise, it will just grab the latest messages
		else {
			$query .= " ORDER BY date_time DESC LIMIT 10";
		}

		// execute the query
		global $db;
		$result = $db->query($query);
		if(!isset($result)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}

		// if there are no results...
		if(count($result) == 0) {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No results for room with GUID '{$this->room_guid}'");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Messages found for room with GUID '{$this->room_guid}'");
			return $result;
		}
	}

	function send_message($message) {
		// it'll be easier to work with the user GUID if we do this first
		$user_guid = $_SESSION['user_object']->get_guid();

		// log what we are doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Processing message send request for user with GUID '${user_guid}' in room with GUID '{$this->room_guid}'");

		// TODO - check for action
		$action = false;

		// TODO - determine whether the message should be sent to all users
		$private = false;

		// build the query
		$current_timestamp = date('Y-m-d H:i:s');
		$query = "INSERT INTO chat_messages (date_time, room_guid, user_guid, message, action, private) VALUES (${current_timestamp}, '{$this->room_guid}', '${user_guid}', '${message}', ${action}, ${private})";
		global $db;
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Message not recorded due to database error");
			return false;
		} else {
			// log and leave
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Message recorded");
			return true;
		}
	}

	function get_users() {
		// log what we are doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Getting a list of users in the room '{$this->room_guid}'");

		// call out to the database to get the users
		$query = "SELECT user_guid, user_level FROM rooms WHERE room_guid = '($this->room_guid}' and user_level > 0";
		global $db;
		$result = $db->query($query);

		// parse the results
		if(!isset($results) || !is_array($results)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Error retrieving the list of users for room with GUID: '{$this->room_guid}'");
			return false;
		}
		else if(count($results) == 0) {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No users found in room with GUID: '{$this->room_guid}'");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User list retrieved for room with GUID: '{$this->room_guid}'");
			return $result;
		}
	}

	function user_join($user_guid) {
		// log what we are doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User joining the room with GUID: '{$this->room_guid}'");

		// only allow the login if the user is not banned
		$query = "SELECT user_level FROM rooms WHERE room_guid = '{$this->room_guid}' AND user_guid = '${user_guid}' AND user_level = 0";
		global $db;
		$result = $db->query($query);
		if(isset($result) && count($result) > 0) {
			// user is banned - bail
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User with GUID '${user_guid}' prevented from joining room with GUID '{$this->room_guid}' (banned)");
			return false;
		}
		else {
			// user is cool to join - but are they already here?
			$query = "SELECT user_guid FROM rooms WHERE room_guid = '{$this->room_guid}' AND user_guid = '${user_guid}'";
			$result = $db->query($query);
			if(isset($result) && count($result) > 0) {
				// user is already here - rejoin session
				$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User with GUID '${user_guid}' rejoined session in room with GUID '{$this->room_guid}'");
				return true;
			}
			else {
				// perform a new join
				$query = "INSERT INTO rooms (user_guid, room_guid, user_level) VALUES ('${user_guid}', '{$this->room_guid}', 1)";
				if(!$db->query($query)) {
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database call failed");
					return false;
				}
				else {
					$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Join successful for user with GUID '${user_guid}' into room with GUID '{$this->room_guid}'");
					return true;
				}
			}
		}
	}

	function user_part($user_guid) {
		// log what we are doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User leaving the room with GUID: '{$this->room_guid}'");

		// update the database
		$query = "DELETE FROM rooms WHERE user_guid = '${user_guid}' AND room_guid = '{$this->room_guid}'";
		global $db;
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User successfully removed from the room with GUID '{$this->room_guid}'");
			return true;
		}
	}

	function user_level_change($user_guid, $new_access_level) {
		// log what we are doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Attempting to change the access level of the user with GUID '${user_guid}' in the room with GUID: '{$this->room_guid}'");

		// update the database
		global $db;
		$query = "UPDATE room SET user_level = ${new_access_level} WHERE user_guid = '${user_guid}' AND room_guid = '{$this->room_guid}'";
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User access level changed in room with GUID {$this->room_guid}");
			return true;
		}
	}

	function set_room_guid($room_guid) {
		// log what we are doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// if GUID is valid, set it in the object instance
		if(!isset($room_guid)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Invalid GUID passed");
			return false;
		}
		else {
			$this->room_guid = $room_guid;
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Room GUID set:'{$this->room_guid}'");
			return true;
		}
	}
}
