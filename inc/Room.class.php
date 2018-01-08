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

		// switch on the action term
		switch($this->action) {
			case 'room':
				// check if there even is a secondary term
				if(!isset($this->secondary) || strlen($this->secondary) == 0) {
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No room identifier supplied");
					global $config;
					include $config->app_base_dir . '/themes/' . $config->get_value('active_theme') . '/page-404.php';
				}

				// is this a call to create or delete a room?
				if($this->secondary == 'create' || $this->secondary == 'delete' || $this->secondary == 'modify') {
					// check that parameters were passed
					if(!isset($this->parameters) || !is_array($parameters) || count($parameters) == 0) {
						$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Parameters not passed when trying to {$this->secondary} a room");
						$response = array(
							'response' => 'error',
							'message' => 'Parameters not passed'
						);
					}

					// check for missing parameters
					else {
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
						}

						// call the appropriate function
						else {
							if($this->secondary == 'create' && !create_room($parameters['room_name'], $parameters['uri'], $parameters['owner_guid'])) {
								$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failed");
								$response = array(
									'response' => 'error',
									'message' => 'Function call failed'
								);
							}
							else if($this->secondary == 'delete' && !delete_room()) {
								$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failed");
								$response = array(
									'response' => 'error',
									'message' => 'Function call failed'
								);
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
									}
								}
								else if(isset($parameters['uri'])) {
									if(!$this->modify_uri($parameters['uri'])) {
										$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to modify the URI");
										$response = array(
											'response' => 'error',
											'message' => 'Function call failed'
										);
									}
								}
								else if(isset($parameters['owner_guid'])) {
									if(!$this->modify_owner_guid($parameters['owner_guid'])) {
										$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to modify the owner GUID");
										$response = array(
											'response' => 'error',
											'message' => 'Function call failed'
										);
									}
								}
							}
						}
					}

					// since it will only be XHR requests that call this,
					// return a response if it exists
					if(isset($response) && is_array($response)) {
						echo json_encode($response);
					}
				}

				// if it gets here, it's probably a room URI
				else {
					// but let's see if it exists first
					$query = "SELECT guid, uri, name, owner_guid FROM rooms WHERE uri = '{$this->secondary}' LIMIT 1";
					global $db;
					$result = $db->query($query);
					if(!isset($result) || !is_array($result)) {
						$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
						return false;
					}
					else if(count($result) == 0) {
						$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "No room found with URI '{$this->secondary}'");
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
						include $config->app_base_dir . '/themes/' . $config->get_value('active_theme') . '/page-room.php';
					$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Room loaded with GUID '{$this->guid}'");
					}
				}
				break;

		default:
			// a whole lot of nothing
			return false;
			break;
		}
	}

	// additional functions
	function create_room($room_name, $uri, $owner_guid) {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Function called");

		// get a GUID and build the query
		$room_guid = Common::get_guid();
		$query = "INSERT INTO rooms (name, owner_guid, uri, guid) VALUES ('${room_name}', '${owner_guid}', '${uri}', '${room_guid}')";
		if(!$result = $db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			// populate object variables
			$this->guid = $room_guid;
			$this->uri = $uri;
			$this->room_name = $room_name;
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
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
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

	// this will most likely only ever be called procedurally
	static function get_room_list() {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// query the database
		$query = "SELECT guid, uri, name, owner_guid FROM rooms ORDER BY name ASC";
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
