<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// includes needed
global $config;
if(!class_exists('Module')) {
	require $config->app_base_dir . '/inc/Module.class.php';
}

// class definition
class Home extends Module {
	// required function definition
	function process_action() {
		// tell the logger what we're doing
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Processing action '{$this->action}'");
		
		// there is only one action
		if(isset($this->action) && $this->action == 'home') {
			switch($this->secondary) {
				case null:
				case '':
					// log the event
					$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Processing secondary '{$this->secondary}'");
					
					// load the page from the template
					global $config;
					require $config->get_theme_location() . '/page-home.php';
					$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Home page loaded");
					break;
				
				default:
					break;
			}
		}
	}
}