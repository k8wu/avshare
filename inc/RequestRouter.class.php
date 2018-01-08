<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// class definition
class RequestRouter {
	// properties
	private $request;
	private $parameters;

	// constructor method
	function __construct($request_uri, $parameters) {
		$this->request = explode('/', $request_uri);
		$this->parameters = $parameters;
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Loaded class");
		$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Parameters: '" . print_r($this->parameters, true) . "'");
	}

	// functions
	public function process_uri() {
		// get the first two terms - we'll call them action and secondary
		global $db;
		global $logger;
		if(count($this->request) > 1) {
			$action_clean = $db->sanitize_input($this->request[1]);
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Action: '${action_clean}'");
		}
		if(count($this->request) > 2) {
			$secondary_clean = $db->sanitize_input($this->request[2]);
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Secondary: '${secondary_clean}'");
		}

		// is the user logged in?
		if(!isset($_SESSION['user_object']) && $action_clean != 'user' && $secondary_clean != 'login' && $secondary_clean != 'logout') {
			// redirect user to the login page
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "User not logged in - redirecting to login page");
			header('Location: /user/login');
			exit();
		}

		// was the front page requested?
		if(!isset($action_clean) || strlen($action_clean) == 0) {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Front page requested");
			header("Location: /home");
			exit();
		}

		// otherwise, continue
		else {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Finding handler for action '${action_clean}'");

			// find which module handles that
			$query = "SELECT name FROM modules WHERE id = (SELECT module_id FROM module_actions WHERE action = '${action_clean}' LIMIT 1) LIMIT 1";
			$result = $db->query($query);

			// send the data to the module (or bail if there is no module)
			if(!isset($result) || !is_array($result)) {
				$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No module registered for action '${action_clean}'");
				global $config;
				include $config->app_base_dir . $config->get_theme_uri() . '/page-404.php';
				exit;
			}
			else {
				// get the module name
				$module = $result[0]['name'];

				// determine if there is a secondary term
				if(!isset($secondary_clean)) {
					// if there isn't, just make it blank
					$secondary_clean = '';
				}

				// unpack parameters (if there are any)
				if(isset($this->parameters)) {
					// these need to be cleaned
					$parameters_clean = array();
					foreach($this->parameters as $key => $value) {
						$key_clean = $db->sanitize_input($key);
						$value_clean = $db->sanitize_input($value);
						$parameters_clean[$key_clean] = $value_clean;
					}
					$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Parameters were passed (and cleaned)");
				}
				else {
					$parameters_clean = null;
					$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No parameters were passed");
				}
				$logger->emit($logger::LOGGER_DEBUG, __CLASS__, __FUNCTION__, "Parameters cleaned: '" . print_r($parameters_clean, true) . "'");

				// due to the "LIMIT 1" in the query, there is only one result
				if(!class_exists($module)) {
					global $config;
					include $config->app_base_dir . '/inc/' . $module . '.class.php';
				}

				// instantiate the module with the requested data
				$module_instance = new $module($action_clean, $secondary_clean, $parameters_clean);
				$module_instance->process_action();
				$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Module '${module}' handling requested action '${action_clean}' with secondary '${secondary_clean}'");
			}
		}
	}
}
