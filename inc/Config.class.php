<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// class definition
class Config {
	// all properties are public

	// database specific properties
	public $database_user;
	public $database_password;
	public $database_name;
	public $database_host;
	public $database_engine;

	// application base directory
	public $app_base_dir;

	// logger specific properties
	public $logger_location;
	public $logger_level;

	// database configuration methods are here as well
	function get_value($option_name) {
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// is the user admin?
		if(!isset($_SESSION['user_object']) || !$_SESSION['user_object']->is_admin()) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Non-administrator level users not allowed to use this function");
			return false;
		}

		// clean it up
		global $db;
		if(!isset($option_name) || strlen($option_name) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No option name provided");
			return false;
		}
		else {
			$query = "SELECT option_value FROM config WHERE option_name = '${option_name}' LIMIT 1";
			$result = $db->query($query);

			// is the result valid?
			if(!isset($result) || count($result) == 0) {
				global $logger;
				$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No result for option name '${option_name}'");
				return false;
			}
			else {
				global $logger;
				$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Result returned for option name '${option_name}'");
				return $result[0]['option_value'];
			}
		}
	}

	function set_value($option_name, $option_value) {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// is the user admin?
		if(!isset($_SESSION['user_object']) || !$_SESSION['user_object']->is_admin()) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Non-administrator level users not allowed to use this function");
			return false;
		}

		// check for valid input for option name (value can be blank)
		if(strlen($option_name) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Option name invalid");
			return false;
		}

		// check that the option exists in the database
		$query = "SELECT option_name FROM config WHERE option_name = '${option_name}'";
		global $db;
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Option '${option_name}' not found in the database");
			return false;
		}

		// otherwise, attempt to update it with the new value
		else {
			// build the query
			$query = "UPDATE config SET option_value = '${option_value}' WHERE option_name = '${option_name}'";
			if(!$db->query($query)) {
				$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to update the option value for option name '${option_name}'");
				return false;
			}
			else {
				$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Option '${option_name}' successfully updated");
			}
		}

		// if we get here, everything worked
		return true;
	}

	function get_description($option_name) {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// is the user admin?
		if(!isset($_SESSION['user_object']) || !$_SESSION['user_object']->is_admin()) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Non-administrator level users not allowed to use this function");
			return false;
		}

		// is the option name valid?
		if(!isset($option_name)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Invalid option name passed");
			return false;
		}

		// look up the option description
		$query = "SELECT option_desc FROM config WHERE option_name = '${option_name}' LIMIT 1";
		global $db;
		$result = $db->query($query);

		// return the result, regardless of whether anything came back
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Returning description (if any) for config option '{$option_name}'");
		return $result[0]['option_desc'];
	}

	function set_description($option_name, $option_desc) {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// is the user admin?
		if(!isset($_SESSION['user_object']) || !$_SESSION['user_object']->is_admin()) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Non-administrator level users not allowed to use this function");
			return false;
		}

		// is the option name valid?
		if(!isset($option_name)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Invalid option name passed");
			return false;
		}

		// insert the new option description
		$query = "UPDATE config SET option_desc = '${option_desc}' WHERE option_name = '${option_name}'";
		global $db;
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Description for option '${option_name}' successfully updated");
			return true;
		}
	}

	function get_module_list() {
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// is the user admin?
		if(!isset($_SESSION['user_object']) || !$_SESSION['user_object']->is_admin()) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Non-administrator level users not allowed to use this function");
			return false;
		}

		// build and execute the query
		$query = "SELECT id, descr, name, core, enabled FROM modules";
		global $db;
		$result = $db->query($query);

		// check to see if anything came back (most likely yes)
		if(!isset($result) || count($result) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No modules returned!");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Module list returned");
			return $result;
		}
	}

	function get_module_description($module_name) {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// is the user admin?
		if(!isset($_SESSION['user_object']) || !$_SESSION['user_object']->is_admin()) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Non-administrator level users not allowed to use this function");
			return false;
		}

		// sanitize input
		global $db;
		$module_name_clean = $db->sanitize_input($module_name);

		// if it's still valid, proceed, otherwise bail
		if(!isset($module_name_clean) || strlen($module_name_clean) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Invalid module name passed");
			return false;
		} else {
			// build and execute the query
			$query = "SELECT descr FROM modules WHERE name = '${module_name_clean}'";
			$result = $db->query($query);

			// check the validity of the result
			if(!isset($result) || count($result) == 0) {
				$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No description found for module ${module_name_clean}");
				return false;
			}
			else {
				$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Description found for module ${module_name_clean}");
				return $result[0]['descr'];
			}
		}
	}

	function get_all_themes() {
		// log the action
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// is the user admin?
		if(!isset($_SESSION['user_object']) || !$_SESSION['user_object']->is_admin()) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Non-administrator level users not allowed to use this function");
			return false;
		}

		// get the listing of themes from the directory
		global $config;
		$theme_dir_contents = scandir($config->app_base_dir . '/themes');
		if(!isset($theme_dir_contents) || count($theme_dir_contents) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Failed to get theme directory listing");
			return false;
		}

		// filter out "." and ".." (needed on POSIX compliant hosts)
		$themes = array();
		foreach($theme_dir_contents as $entity) {
			if(isset($entity) && strlen($entity) > 0 && $entity != '.' && $entity != '..') {
				array_push($themes, $entity);
			}
		}

		// return the results
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Results returned");
		return $themes;
	}

	function get_theme_location($theme = null) {
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// clean the input
		global $db;
		$theme_clean = $db->sanitize_input($theme);

		// if the theme was not given, it is default
		if(!isset($theme_clean) || strlen($theme_clean) == 0) {
			return $this->app_base_dir . '/themes/default';
		}

		// otherwise, build it on the return here
		else {
			return $this->app_base_dir . '/themes/' . $theme_clean;
		}
	}

	function get_theme_uri($theme = null) {
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// clean the input
		global $db;
		$theme_clean = $db->sanitize_input($theme);

		// if the theme was not given, it is default
		if(!isset($theme_clean) || strlen($theme_clean) == 0) {
			return '/themes/default';
		}

		// otherwise, build it on the return here
		else {
			return '/themes/' . $theme_clean;
		}
	}

	function get_title() {
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// query the database directly to get the requested value
		$query = "SELECT option_value FROM config WHERE option_name = 'title' LIMIT 1";
		global $db;
		$result = $db->query($query);

		// is the result valid?
		if(!isset($result) || !is_array($result) || count($result) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Title returned");
			return $result[0]['option_value'];
		}
	}

	function get_subtitle() {
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");

		// query the database directly to get the requested value
		$query = "SELECT option_value FROM config WHERE option_name = 'subtitle' LIMIT 1";
		global $db;
		$result = $db->query($query);

		// is the result valid?
		if(!isset($result) || !is_array($result) || count($result) == 0) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Subtitle returned");
			return $result[0]['option_value'];
		}
	}
}
