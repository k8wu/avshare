<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// class definition (anything that is a module must extend this)
abstract class Module {
	// properties
	protected $name;
	protected $action;
	protected $secondary;
	protected $parameters;

	// getters (no setters necessary)
	function get_action() {
		return $this->action;
	}

	function get_secondary() {
		return $this->secondary;
	}

	function get_parameters() {
		return $this->parameters;
	}

	// constructor method - feel free to override this
	function __construct($action, $secondary, $parameters) {
		// define the variables
		$this->action = $action;
		$this->secondary = $secondary;
		$this->parameters = $parameters;

		// this makes the module name easier to use below
		$this->name = get_called_class();

		// log that we loaded the module
		global $logger;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Module '{$this->name}' loaded");
		$logger->emit($logger::LOGGER_DEBUG, get_called_class(), __FUNCTION__, "Action: '{$this->action}'");
		$logger->emit($logger::LOGGER_DEBUG, get_called_class(), __FUNCTION__, "Secondary: '{$this->secondary}'");
	}

	// public functions
	function register_module($is_core = false) {
		// log that the function is called
		global $db;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, 'Function called');

		// we only take true for is_core's value - all others are false
		$is_core = ($is_core === true ? '1' : '0');

		// build the query and execute it
		$query = "INSERT INTO modules (name, core) VALUES ('{$this->name}', '${is_core}')";
		global $db;
		$result = $db->query($query);
		if(!isset($result) || !is_array($result)) {
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Module '{$this->name}' failed to register");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Module '{$this->name}' registered");
			return true;
		}
	}

	function unregister_module() {
		// log that the function is called
		global $logger;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, 'Function called');

		// if the module is core, we don't want to allow this
		if($this->is_core()) {
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "'{$this->name}' is a core module - not unregistering");
			return false;
		}

		// otherwise, proceed with unregistering
		else {
			$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "'{$this->name}' is not a core module - proceeding with unregistering");

			// delete the registered module actions
			$query = "DELETE FROM module_actions WHERE id = (SELECT id FROM modules WHERE name = '{$this->name}')";
			if(!$db->query($query)) {
				$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
				return false;
			}
			else {
				$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Unregistered module actions");
			}

			// delete the module record from the database
			$query = "DELETE FROM modules WHERE name = '{$this->name}'";
			if(!$db->query($query)) {
				$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
				return false;
			}
			else {
				$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Unregistered module");
			}

			return true;
		}
	}

	// with this one, you should define $this->actions_register as a simple
	// array containing zero or more actions
	function register_module_actions() {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Function called");

		// are the actions valid?
		if(!isset($this->actions_register) || !is_array($this->actions_register) || count($this->actions_register) === 0) {
			// this isn't a hard failure - it just means that there are no
			// actions to register
			$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "No actions passed - not registering");
			return false;
		}
		else {
			// build the query and execute it
			global $db;
			foreach($actions_register as $item) {
				$query = "INSERT INTO module_actions (module_id, action) VALUES ('{$this->name}', '${item}')";
				if(!$db->query($query)) {
					$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
					return false;
				}
				else {
					$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Action '${item}' registered for module '{$this->name}'");
				}
			}
		}

		return true;
	}

	function enable_module() {
		// log this function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Function called");

		// enable the module in the database
		$query = "UPDATE modules SET enabled = 1 WHERE name = '${module_name_clean}'";
		global $db;
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Module '{$this->name}' is now enabled");
		}

		return true;
	}

	function disable_module() {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Function called");

		// check if this is a core module
		if($this->is_core()) {
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "'{$this->name}' is a core module - not unregistering");
			return false;
		}

		// enable the module in the database
		$query = "UPDATE modules SET enabled = 0 WHERE name = '{$this->name}'";
		global $db;
		if(!$db->query($query)) {
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Module '{$this->name}' is now disabled");
			return true;
		}
	}

	function is_core() {
		// log the function call
		global $logger;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Function called");

		// if the module is core, we don't want to allow this
		$query = "SELECT core FROM modules WHERE name = '{$this->module_name}' LIMIT 1";
		global $db;
		$result = $db->query($query);

		// there should only be one result in the set
		if(!isset($result) || !is_array($result)) {
			$logger->emit($logger::LOGGER_WARN, get_called_class(), __FUNCTION__, "Database query failure");
			return false;
		}
		else {
			// convert to true or false
			if($result[0]['core'] == '1') {
				$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Module is core");
				return true;
			}
			else {
				$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Module is not core");
				return false;
			}
		}
	}

	function get_admin_panel_data() {
		global $logger;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Function called");

		// load the settings display from the file
		global $config;
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Loading settings from template");
		include $config->app_base_dir . $config->get_theme_uri() . '/config-' . get_called_class() . '.php';

		// return the output back to the caller
		$logger->emit($logger::LOGGER_INFO, get_called_class(), __FUNCTION__, "Data returned to caller");
	}

	// this function needs to be explicitly implemented by any module
	abstract function process_action();
}
