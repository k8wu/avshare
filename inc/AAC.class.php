<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// includes needed
global $config;
if(!class_exists('Module')) {
	require $config->app_base_dir . '/inc/Module.class.php';
}

// class definition
class AAC extends Module {
	// constants
	const ACCESS_LEVEL_INACTIVE = 0;
	const ACCESS_LEVEL_USER = 1;
	const ACCESS_LEVEL_ADMIN = 2;
	const ACCESS_LEVEL_SYSOP = 3;

	// public properties
	protected $guid;
	protected $username;
	protected $password;
	protected $access_level;
	protected $email_address;
	protected $banned;
	protected $ban_reason;

	// for errors
	private $error;

	// getters and setters
	function get_guid() {
		return $this->guid;
	}

	function set_guid($new_guid) {
		$this->guid = $new_guid;
	}

	function get_username() {
		return $this->username;
	}

	function set_username($new_username) {
		$this->username = $new_username;
	}

	function get_access_level() {
		return $this->access_level;
	}

	function set_access_level($new_access_level) {
		$this->access_level = $new_access_level;
	}

	function get_email_address() {
		return $this->email_address;
	}

	function set_email_address($new_email_address) {
		$this->email_address = $new_email_address;
	}

	function get_banned() {
		return $this->banned;
	}

	function set_banned($new_banned) {
		$this->banned = $new_banned;
	}

	function get_ban_reason() {
		return $this->ban_reason;
	}

	function set_ban_reason($new_ban_reason) {
		$this->ban_reason = $new_ban_reason;
	}

	// passwords are a special case - we don't want hashes in the objects
	function get_password() {
		return '*';
	}

	function set_password() {
		$this->password = '*';
	}

	// required function declaration
	function process_action() {
		// tell the logger what we're doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Processing action '{$this->action}'");

		// switch on action
		switch($this->action) {
			case 'user':
				// now switch on secondary
				switch($this->secondary) {
				case 'login':
					// bring up the login page
					global $config;
					include $config->get_theme_location() . '/page-login.php';
					break;

				case 'logout':
					$this->logout();
					$response = array(
						'response' => 'ok',
						'message' => 'Logged out',
						'location' => '/'
					);
					break;

				case 'process-login':
					// check if there are parameters passed
					if(!isset($this->parameters) || !is_array($this->parameters)) {
						$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, 'No parameters passed while trying to call for a login');
						$response = array(
							'response' => 'error',
							'message' => 'No parameters passed'
						);
						break;
					}

					// were the right ones passed?
					$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Parameters: '" . print_r($this->parameters, true) . "'");
					$missing = false;
					foreach(array("username", "password") as $param) {
						$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Parameter: '${param}'  Value: '{$this->parameters[$param]}'");
						if(!isset($this->parameters[$param])) {
							$missing = true;
						}
					}
					if($missing === true) {
						$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, 'Missing parameters while trying to call for a login');
						$response = array(
							'response' => 'error',
							'message' => 'Missing parameters'
						);
						break;
					}

					// try to do the login and get the result
					if(!$this->login($this->parameters['username'], $this->parameters['password'])) {
						$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Login failure notice sent to client");
						$response = array(
							'response' => 'error',
							'message' => 'Login failed!'
						);
						break;
					}
					else {
						$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Login success notice sent to client");
						global $config;
						$response = array(
							'response' => 'ok',
							'message' => 'Login successful!',
							'location' => '/' . $config->get_value('default_home_page')
						);
					}
					break;

				case 'get':
					$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Parameters passed: '" . print_r($this->parameters, true) . "'");

					// are there parameters at all?
					if(!isset($this->parameters) || !is_array($this->parameters) || count($this->parameters) == 0) {
						$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Action {$this->action} requires parameters to be sent");
						$response = array(
							'response' => 'error',
							'message' => 'No parameters sent!'
						);
					}

					// did the user send back a guid?
					else if(!isset($this->parameters['guid'])) {
						$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Action {$this->action} expects the 'guid' parameter");
						$response = array(
							'response' => 'error',
							'message' => 'Missing guid parameter!'
						);
					}
					else {
						$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User data lookup for {$this->parameters['guid']} was successful");
						$response = array(
							'response' => 'ok',
							'message' => 'Request successful!'
						);
						$response += $this->get_user_data($this->parameters['guid']);
					}
					break;

				case 'create':
					global $logger;
					$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Parameters passed: '" . print_r($this->parameters, true) . "'");

					// are there parameters at all?
					if(!isset($this->parameters) || !is_array($this->parameters) | count($this->parameters) == 0) {
						$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Action '{$this->action}' requires parameters to be sent");
						$response = array(
							'response' => 'error',
							'message' => 'No parameters sent!'
						);
					}

					// were the proper ones sent?
					$missing = false;
					foreach(array('username', 'password', 'email_address', 'access_level') as $param) {
						if(!isset($this->parameters[$param])) {
							$missing = true;
						}
					}
					if($missing) {
						$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Invalid parameters passed for action '{$this->action}'");
						$response = array(
							'response' => 'error',
							'message' => 'Invalid parameters sent'
						);
						break;
					}

					// process the request
					else {
						if (!$this->create_user($this->parameters['username'], $this->parameters['password'], $this->parameters['email_address'], $this->parameters['access_level'])) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Function call failure");
							$response = array(
								'response' => 'error',
								'message' => 'Function call failure'
							);
							break;
						}
						else {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Creation call successful for user");
							$response = array(
								'response' => 'ok',
								'message' => 'Request to add user successful!'
							);
						}
					}
					break;

				case 'modify':
					global $logger;
					$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Parameters passed: '" . print_r($this->parameters, true) . "'");

					// are there parameters at all?
					if(!isset($this->parameters) || !is_array($this->parameters) | count($this->parameters) == 0) {
						$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Action '{$this->action}' requires parameters to be sent");
						$response = array(
							'response' => 'error',
							'message' => 'No parameters sent!'
						);
						break;
					}

					// process the request
					else {
						if(!$this->populate_user_info($this->parameters['guid'])) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to populate user data from GUID '{$this->parameters['guid']}'");
							$response = array(
								'response' => 'error',
								'message' => 'Cannot populate user data'
							);
							break;
						}

						// different functions correspond to different requests
						if(isset($this->parameters['username'])) {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Attempting to modify username");
							if(!$this->modify_username($this->parameters['username'])) {
								$response = array(
									'response' => 'error',
									'message' => $this->get_error()
								);
								break;
							}
						}
						if(isset($this->parameters['password'])) {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Attempting to modify password");
							$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Password: '{$this->password}'");
							if(!$this->modify_password($this->parameters['password'], $this->parameters['guid'])) {
								$response = array (
									'response' => 'error',
									'message' => $this->get_error()
								);
								break;
							}
						}
						if(isset($this->parameters['email_address'])) {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Attempting to modify email address");
							if(!$this->modify_email_address($this->parameters['email_address'], $this->parameters['guid'])) {
								$response = array(
									'response' => 'error',
									'message' => $this->get_error()
								);
								break;
							}
						}
						if(isset($this->parameters['access_level'])) {
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Attempting to modify access level");
							if(!$this->modify_access_level($this->parameters['access_level'], $this->parameters['guid'])) {
								$response = array(
									'response' => 'error',
									'message' => $this->get_error()
								);
								break;
							}
						}

						// making it here = success
						$response = array(
							'response' => 'ok',
							'message' => 'Request to modify user data successful'
						);
					}
					break;

				case 'delete':
					global $logger;
					$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Parameters passed: '" . print_r($this->parameters, true) . "'");

					// are there parameters at all?
					if(!isset($this->parameters) || !is_array($this->parameters) | count($this->parameters) == 0) {
						$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Action '{$this->action}' requires parameters to be sent");
						$response = array(
							'response' => 'error',
							'message' => 'No parameters sent!'
						);
					}

					// process the request
					else {
						// populate user info
						if(!$this->populate_user_info($this->parameters['guid'])) {
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to populate user data from GUID '{$this->parameters['guid']}'");
							$response = array(
								'response' => 'error',
								'message' => 'Cannot populate user data'
							);
							break;
						}

						// call the delete function
						if(!$this->delete_user()) {
							$response['response'] = 'error';
							$response['message'] = $this->get_error();
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
							break;
						}
						else {
							$response['response'] = 'ok';
							$response['message'] = 'Deletion successful';
							$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Delete request successful for '{$this->parameters['guid']}'");
						}
					}
					break;

				default:
					// a whole lot of nothing
					break;
			}

			default:
				break;
		}

		// if there is an API response, return it encoded
		if(isset($response) && is_array($response)) {
			echo json_encode($response);
			$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Response: '" . json_encode($response) . "'");
		}
	}

	function login($username, $password) {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// hash the password
		$password_hash = hash('sha512', $password);

		// build and execute query - we only need to do this once
		$query = "SELECT guid, name, password, access_level, banned, ban_reason FROM users WHERE name = '${username}' AND password = '${password_hash}'";
		global $db;
		$result = $db->query($query);

		// if there are no results, return false
		if(!$result || count($result) == 0) {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Invalid login for user ${username}");
			return false;
		}

		// if the user is banned, deny login
		else if($result[0]['banned'] > 0) {
			$this->banned = $result[0]['banned'];
			$this->ban_reason = $result[0]['ban_reason'];
			global $logger;
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Login attempt rejected for banned user ${username}");
			return false;
		}

		// if the user's access level indicates that the user is inactive, deny login
		else if($result[0]['access_level'] == $this::ACCESS_LEVEL_INACTIVE) {
			global $logger;
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Login attempt rejected for inactive user ${username}");
			return false;
		}

		// otherwise, update the database
		else {
			// parse variables
			$this->set_guid($result[0]['guid']);
			$this->set_username($result[0]['name']);
			$this->set_access_level($result[0]['access_level']);

			// get timestamp
			$current_timestamp = date('Y-m-d H:i:s');

			// build and execute update query
			$query = "UPDATE users SET last_login = '${current_timestamp}' WHERE guid = '{$this->guid}'";
			$result = $db->query($query);

			// start the session if it hasn't been already
			if(session_status() == PHP_SESSION_NONE) {
				session_start();
			}
			$_SESSION['user_object'] = $this;

			// log it
			global $logger;
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Login successful for user {$this->username} ({$this->guid})");

			// signal that the login was successful
			return true;
		}
	}

	function logout() {
		// log entry for the logout process
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User {$this->username} ({$this->guid}) logging out");

		// destroy the session variable that contains the AAC instance
		unset($_SESSION['user_object']);
		return true;
	}

	function create_user($username, $password, $email_address, $access_level = self::ACCESS_LEVEL_USER) {
		// log this
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// if the username already exists, bail
		$query = "SELECT name FROM users WHERE name = '${username}'";
		global $db;
		$result = $db->query($query);
		if(isset($result[0]) && $result[0] == $username) {
			$this->set_error('Invalid username (or username already exists)');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// if the password isn't at least 5 characters long, bail
		if(!isset($password) || strlen($password) < 5) {
			$this->set_error("Invalid password (must be 5 characters long at least)");
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// if the access level is not valid, bail
		if(!is_numeric($access_level) || $access_level > $this::ACCESS_LEVEL_SYSOP) {
			$this->set_error('Access level invalid');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// if the email address doesn't look like an email address, bail
		$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Email address: '${email_address}'");
		if(!isset($email_address) || strlen($email_address) == 0 || strpos($email_address, '@') === false || strpos($email_address, '.') === false | strpos($email_address, '.') - strpos($email_address, '@') < 2) {
			$this->set_error('Email address invalid');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// we need a GUID and a password hash
		global $config;
		if(!class_exists('Common')) {
			require $config->app_base_dir . '/inc/Common.class.php';
		}
		$guid = Common::get_guid();
		$password_hash = hash('sha512', $password);
		$current_timestamp = date('Y-m-d H:i:s');

		// build query and execute it
		$query = "INSERT INTO users (guid, name, password, email_address, access_level, created, modified) VALUES ('${guid}', '${username}', '${password_hash}', '${email_address}', ${access_level}, '${current_timestamp}', '${current_timestamp}')";
		$result = $db->query($query);

		// log this event
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User ${username} created successfully (GUID: ${guid})");
		return true;
	}

	function modify_username($new_username) {
		// log this event
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// validate input
		if(!isset($new_username) || strlen($new_username) == 0) {
			$this->set_error('Invalid username');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// build query and execute
		global $db;
		$query = "UPDATE users SET name = '${new_username}' WHERE guid = '{$this->guid}'";
		if(!$db->query($query)) {
			// something went wrong
			$this->set_error('Database query error');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}
		else {
			// successful
			$this->update_modified_timestamp($this->guid);
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function call successful");
			return true;
		}
	}

	function modify_password($new_password) {
		// log this event
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// validate input
		if(!isset($new_password) || strlen($new_password) < 5) {
			$this->set_error('Invalid password');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// get the password hash
		$new_password_hash = hash('sha512', $new_password);

		// build query and execute
		global $db;
		$query = "UPDATE users SET password = '${new_password_hash}' WHERE guid = '{$this->guid}'";
		if(!$db->query($query)) {
			// something went wrong
			$this->set_error('Database query error');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}
		else {
			// successful
			$this->update_modified_timestamp($this->guid);
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function call successful");
			return true;
		}
	}

	function modify_email_address($new_email_address) {
		// log this event
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// validate input
		if(!isset($new_email_address) || strlen($new_email_address) == 0 || strpos($new_email_address, '@') === false || strpos($new_email_address, '.') === false | strpos($new_email_address, '.') - strpos($new_email_address, '@') < 2) {
			$this->set_error('Invalid email address');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// build query and execute
		global $db;
		$query = "UPDATE users SET email_address = '${new_email_address}' WHERE guid = '{$this->guid}'";
		if(!$db->query($query)) {
			// something went wrong
			$this->set_error('Database query error');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}
		else {
			// successful
			$this->update_modified_timestamp($this->guid);
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function call successful");
			return true;
		}
	}

	function modify_access_level($new_access_level) {
		// log this event
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// validate input
		if(!is_numeric($new_access_level) || $new_access_level > $this::ACCESS_LEVEL_SYSOP) {
			$this->set_error('Invalid access level');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// there can be no more than one sysop
		if($new_access_level == $this::ACCESS_LEVEL_SYSOP) {
			$this->set_error('Cannot set the access level to sysop');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}

		// build query and execute
		global $db;
		$query = "UPDATE users SET access_level = '${new_access_level}' WHERE guid = '{$this->guid}'";
		if(!$db->query($query)) {
			// something went wrong
			$this->set_error('Database query error');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}
		else {
			// log the user out if they were inactivated and are logged in anywhere
			if($new_access_level == $this::ACCESS_LEVEL_INACTIVE) {
				$this->logout($this->guid);
			}

			// successful
			$this->update_modified_timestamp($this->guid);
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function call successful");
			return true;
		}
	}

	function delete_user() {
		// log this event
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// build query and execute it
		global $db;
		$query = "DELETE FROM users WHERE guid = '{$this->guid}'";
		if(!$db->query($query)) {
			// something went wrong
			$this->set_error('Database query error');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}
		else {
			// do some cleanup using the logout function
			$this->logout($this->guid);

			// log this event
			global $logger;
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User deleted successfully (GUID: '{$this->guid}')");
			return true;
		}
	}

	static function list_users() {
		// log that we are doing this
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// build and execute the query
		$query = "SELECT guid, name FROM users";
		global $db;
		$result = $db->query($query);

		// check for the validity of the results
		if(!$result || !is_array($result) || count($result) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No users found!");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User list retrieved successfully");
			return $result;
		}
	}

	static function get_user_data($guid = null) {
		// which GUID are we working on?
		if(!isset($guid) || strlen($guid) == 0) {
			$guid = $this->guid;
		}

		// log that we are doing this
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User data requested for user with GUID ${guid}");

		// query the database for the requested information
		$query = "SELECT guid, name, email_address, access_level, modified, created, last_login, banned, ban_reason FROM users WHERE guid = '${guid}' LIMIT 1";
		global $db;
		$result = $db->query($query);

		// there will only be one result
		return $result[0];
	}

	function update_modified_timestamp() {
		// log that we are doing this
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Updating modified timestamp for user with GUID {$this->guid}");

		// build and execute query
		$query = "UPDATE users SET modified = '" . date('Y-m-d H:i:s') . "' WHERE guid = '{$this->guid}'";
		global $db;
		if(!$db->query($query)) {
			// something went wrong
			$this->set_error('Database query error');
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, $this->get_error());
			return false;
		}
		else {
			// log this event
			global $logger;
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Timestamp modified successfully for user with GUID: '{$this->guid}'");
			return true;
		}
	}

	function populate_user_info($guid) {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// check that the passed argument is valid
		if(!isset($guid)) {
			$logger->emit($logger::WARN, __CLASS__, __FUNCTION__, "Invalid GUID passed");
			return false;
		}

		// do a database query to get the user details
		$query = "SELECT name, access_level, email_address, banned, ban_reason FROM users WHERE guid = '${guid}' LIMIT 1";
		global $db;
		$result = $db->query($query);

		// check if anything came back
		if(!isset($result) || !is_array($result)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}

		// otherwise, populate object properties
		$this->set_guid($guid);
		$this->set_username($result[0]['name']);
		$this->set_password('*');  // special case - we don't want hashes available
		$this->set_email_address($result[0]['email_address']);
		$this->set_access_level($result[0]['access_level']);
		$this->set_banned($result[0]['banned']);
		$this->set_ban_reason($result[0]['ban_reason']);
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Populated user object with information about user with GUID: '${guid}'");
		return true;
	}

	function is_admin() {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// if for some reason the access level is not set already...
		if(!isset($this->access_level) || strlen($this->access_level) == 0) {
			// log this incident
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "User access level was not present in the object instance - populating");

			// call out to the database for the info that we need
			$query = "SELECT access_level FROM users WHERE guid = '{$this->guid}'";
			global $db;
			$result = $db->query($query);

			// if there is no result, something went terribly wrong
			if(!isset($result)) {
				$logger->emit($logger::LOGGER_ERROR, __CLASS__, __FUNCTION__, "Database query failure");
				return false;
			}

			// otherwise, populate the access level on this instance
			else {
				$this->access_level = $result[0]['access_level'];
			}
		}

		// we definitely have the information that we need now
		if($this->access_level == $this::ACCESS_LEVEL_ADMIN || $this->access_level == $this::ACCESS_LEVEL_SYSOP) {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User with guid '{$this->guid}' is an admin level user");
			return true;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "User with guid '{$this->guid}' is not an admin level user");
			return false;
		}
	}

	function get_error() {
		return $this->error;
	}

	function set_error($error_str) {
		global $db;
		$this->error = $db->sanitize_input($error_str);
	}
}
