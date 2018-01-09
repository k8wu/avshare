<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// includes needed
global $config;
if(!class_exists('Module')) {
	require $config->app_base_dir . '/inc/Module.class.php';
}
if(!class_exists('Common')) {
	require $config->app_base_dir . '/inc/Common.class.php';
}

// class definition
class Room extends Module {
	// private properties
	protected $guid;
	protected $uri;
	protected $room_name;
	protected $max_users;
	protected $owner_guid;

	// constructor method
	function __construct($action, $secondary, $parameters) {
		$this->action = $action;
		$this->secondary = $secondary;
		$this->parameters = $parameters;
	}

	// required function definition for modules
	function process_action() {
		// tell the logger what we're doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Processing action '{$this->action}'");

		// check if there even is a secondary term
		if(!isset($this->secondary) || strlen($this->secondary) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No secondary term supplied");

			// if it's an XHR, handle this via JSON response
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				$response = array(
					'response' => 'error',
					'message' => 'No secondary term supplied'
				);
				echo json_encode($response);
				return false;
			}

			// otherwise, just send the user to a 404 page
			else {
				global $config;
				include $config->get_theme_location() . '/page-404.php';
				return false;
			}
		}

		// switch on the action term
		switch($this->action) {
			case 'room-admin':
				// is the user an admin?
				if(!isset($_SESSION['user_object']) || !$_SESSION['user_object']->is_admin()) {
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Access to an administrative level function by non-admin not allowed");
					$response = array(
						'response' => 'error',
						'message' => 'Non-admin access not allowed'
					);
					break;
				}

				// were parameters passed? (all secondaries here need them)
				if(!isset($this->parameters) || !is_array($this->parameters) || count($this->parameters) == 0) {
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Parameters not passed when trying to {$this->secondary} a room");
					$response = array(
						'response' => 'error',
						'message' => 'Parameters not passed'
					);
					break;
				}

				// is the caller requesting information on a room?
				if($this->secondary == 'get-info') {
					// we need a GUID
					if(!isset($this->parameters['guid'])) {
						$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "GUID not passed while attempting to get info");
						$response = array(
							'response' => 'error',
							'message' => 'GUID not passed'
						);
						break;
					}
					else {
						// populate the room GUID
						$this->room_guid = $this->parameters['guid'];

						// fill the rest of the room properties
						$response = $this->get_room_info();
						if(!isset($response) || !is_array($response)) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failure");
							$response = array(
								'response' => 'error',
								'message' => 'Function call failure'
							);
							break;
						}
						else {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Room info sent for room with GUID '{$this->room_guid}'");
							$response['response'] = 'ok';
							$response['message'] = 'Room details successfully retrieved';
							break;
						}
					}
					break;
				}

				// is this a call to create or delete a room?
				if($this->secondary == 'create' || $this->secondary == 'delete' || $this->secondary == 'modify') {
					// check for missing parameters
					$missing = false;

					// required parameters depend on the action requested
					if($this->secondary == 'create' && (!isset($this->parameters['uri']) || !isset($this->parameters['owner_guid']))) {
						$missing = true;
					}
					else if(($this->secondary == 'create' || $this->secondary == 'delete') && !isset($this->parameters['room_name'])) {
						$missing = true;
					}

					// check if anything is missing now
					if($missing) {
						$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Missing parameters when trying to {$this->secondary} a room");
						$response = array(
							'response' => 'error',
							'message' => 'Missing parameters'
						);
						break;
					}

					// call the appropriate function
					else {
						if($this->secondary == 'create' && !$this->create_room($this->parameters['room_name'], $this->parameters['uri'], $this->parameters['max_users'], $this->parameters['owner_guid'])) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failed");
							$response = array(
								'response' => 'error',
								'message' => 'Function call failed'
							);
							break;
						}
						else if($this->secondary == 'delete' && !$this->delete_room()) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failed");
							$response = array(
								'response' => 'error',
								'message' => 'Function call failed'
							);
							break;
						}
						else if($this->secondary == 'modify') {
							// for each parameter we receive, we send it to the proper method
							if(isset($parameters['room_name'])) {
								if(!$this->modify_room_name($parameters['room_name'])) {
									$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to modify the room name");
									$response = array(
										'response' => 'error',
										'message' => 'Function call failed'
									);
									break;
								}
							}
							else if(isset($parameters['uri'])) {
								if(!$this->modify_uri($parameters['uri'])) {
									$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to modify the URI");
									$response = array(
										'response' => 'error',
										'message' => 'Function call failed'
									);
									break;
								}
							}
							else if(isset($parameters['max_users'])) {
								if(!$this->modify_max_users($parameters['max_users'])) {
									$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to modify the maximum number of users");
									$response = array(
										'response' => 'error',
										'message' => 'Function call failed'
									);
									break;
								}
							}
							else if(isset($parameters['owner_guid'])) {
								if(!$this->modify_owner_guid($parameters['owner_guid'])) {
									$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to modify the owner GUID");
									$response = array(
										'response' => 'error',
										'message' => 'Function call failed'
									);
									break;
								}
							}
						}
						else {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Call to secondary '{$this->secondary}' successful");
							$response = array(
								'response' => 'ok',
								'message' => 'Call successful'
							);
						}
					}
				}

				// otherwise, it's not valid
				else {
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Invalid secondary term specified");
					$response = array(
						'response' => 'error',
						'message' => 'Invalid term'
					);
					break;
				}
				break;

			case 'room':
				// let's see if it exists first
				$query = "SELECT guid, uri, name, owner_guid FROM rooms WHERE uri = '{$this->secondary}' LIMIT 1";
				global $db;
				$result = $db->query($query);
				if(!isset($result) || !is_array($result)) {
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
					return false;
				}
				else if(count($result) == 0) {
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No room found with URI '{$this->secondary}'");
					return false;
				}

				// great, it exists
				else {
					// set the instance variables from the query results
					$this->guid = $result[0]['guid'];
					$this->uri = $result[0]['uri'];
					$this->room_name = $result[0]['name'];
					$this->owner_guid = $result[0]['owner_guid'];

					// load the page
					global $config;
					include $config->get_theme_location() . '/page-room.php';
				$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Room loaded with GUID '{$this->guid}'");
				}
				break;

			default:
				// a whole lot of nothing
				return false;
				break;
			}

			// return the response, if any
			if(isset($response) && is_array($response)) {
				echo json_encode($response);
			}
		}

	// additional functions
	function create_room($room_name, $uri, $max_users, $owner_guid) {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// get a GUID and build the query
		$room_guid = Common::get_guid();
		$query = "INSERT INTO rooms (name, owner_guid, max_users, uri, guid) VALUES ('${room_name}', '${owner_guid}', '${max_users}', '${uri}', '${room_guid}')";
		global $db;
		if(!$result = $db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			// populate object variables
			$this->guid = $room_guid;
			$this->uri = $uri;
			$this->room_name = $room_name;
			$this->max_users = $max_users;
			$this->owner_guid = $owner_guid;

			// log this event
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Room '{$this->room_name}' created successfully (GUID: '{$this->guid}')");
			return true;
		}
	}

	function delete_room() {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// build the database query and execute it
		$query = "DELETE FROM rooms WHERE guid = '{$this->guid}'";
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			// log the event and finish
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Room with GUID '{$this->guid}' deleted");
			return true;
		}
	}

	function change_room_name($new_room_name) {
		// log the function call
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// build the database query
		$query = "UPDATE rooms SET room_name = '${new_room_name}' WHERE guid = '{$this->room_name}'";

		// execute it
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			// update the object instance as well
			$this->room_name = $new_room_name;

			// log the event and finish
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Name of room with GUID '{$this->guid}' changed to '{$this->room_name}'");
			return true;
		}
	}

	function change_uri($new_uri) {
		// log the function call
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// build the database query
		$query = "UPDATE rooms SET uri = '${new_uri}' WHERE guid = '{$this->uri}'";

		// execute it
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			// update the object instance as well
			$this->uri = $new_uri;

			// log the event and finish
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "URI of room with GUID '{$this->guid}' changed to '{$this->uri}'");
			return true;
		}
	}

	function change_room_owner($new_owner_guid) {
		// log the function call
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// build the database query
		$query = "UPDATE rooms SET owner_guid = '${new_owner_guid}' WHERE guid = '{$this->guid}'";

		// execute it
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			// update the object instance as well
			$this->owner_guid = $new_owner_guid;

			// log the event and finish
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Owner of room with GUID '{$this->guid}' changed to '{$this->owner_guid}'");
			return true;
		}
	}

	function get_room_info() {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// query the database to get the other details necessary
		$query = "SELECT name, owner_guid, max_users, uri FROM rooms WHERE guid = '{$this->room_guid}' LIMIT 1";
		global $db;
		$result = $db->query($query);

		// hopefully, something came back, but check for that
		if(!isset($result) || !is_array($result)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Database query success - returning results");
			return $result[0];
		}
	}

	// this will most likely only ever be called procedurally
	static function get_room_list() {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// query the database
		$query = "SELECT guid, uri, name, max_users, owner_guid FROM rooms ORDER BY name ASC";
		global $db;
		$result = $db->query($query);

		// only fail if the database had an error
		if(!isset($result) || !is_array($result)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Room list returned");
			return $result;
		}
	}
}
