<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// includes needed
global $config;
if(!class_exists('Module')) {
	require $config->app_base_dir . '/inc/Module.class.php';
}

// class definition
class Admin extends Module {
	// required function declaration
	function process_action() {
		// tell the logger what we're doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Processing action '{$this->action}'");
		
		// check that the user is really an admin
		if(!$_SESSION['user_object']->is_admin()) {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Non-admin level user denied access to an admin-level function");
			// if it's an XHR, return a response
			if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
				$response = array(
					'response' => 'error',
					'message' => 'You are not an admin level user'
				);
				echo json_encode($response);
			}
			return false;
		}
		
		// switch on the action term
		switch($this->action) {
			case 'admin':
				// bring up the login page
				global $config;
				include $config->app_base_dir . '/themes/' . $config->get_value('active_theme') . '/page-admin.php';
				break;
			
			case 'config-set-values':
				// check that the proper parameters were passed
				if(!isset($this->parameters) || !is_array($this->parameters) || count($this->parameters) == 0) {
					$response = array(
						'response' => 'error',
						'message' => 'No parameters passed!'
					);
					$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "No parameters passed");
				}
				else {
					global $config;
					foreach($this->parameters as $parameter => $value) {
						if(!$config->set_value($parameter, $value)) {
							$response = array(
								'response' => 'error',
								'message' => 'Cannot set configuration value!'
							);
							$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Error setting configuration parameter '${parameter}'");
							break;
						}
					}
					
					// if we got here, it was successful
					$response = array(
						'response' => 'ok',
						'message' => 'Configuration parameter(s) successfully set!'
					);
					$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Configuration parameter(s) successfully set");
				}
				break;
				
			default:
				// a whole lot of nothing
				return false;
				break;
		}
		
		// if there is an API response, echo it now
		if(isset($response)) {
			echo json_encode($response);
		}
	}
}