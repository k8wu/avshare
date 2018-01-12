<?php
// debug
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');

// this needs to happen before any includes
define('_APP', true);

// class includes
require_once 'inc/Config.class.php';
require_once 'inc/Logger.class.php';
require_once 'inc/RequestRouter.class.php';

// source the configuration
require_once 'conf.php';

// set up the logger
$logger = new Logger($config->logger_level, $config->logger_location);
$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, 'Configuration read');

// open the database connection
require 'inc/DatabaseHandler.class.php';
$db = new DatabaseHandler(
	$config->database_user,
	$config->database_password,
	$config->database_name,
	$config->database_host,
	$config->database_engine
);
$db->connect();

// remove old user/room entries (i.e. if the user lost their Internet connection, or their web browser crashed)
$query = "DELETE FROM users_in_rooms WHERE last_seen < UNIX_TIMESTAMP() - 30";
$db->query($query);

// pick up the session if there is one
require_once 'inc/Module.class.php';
require_once 'inc/AAC.class.php';
if(session_status() == PHP_SESSION_NONE) {
	session_start();
	if(isset($_SESSION['user_object'])) {
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Session started for user with GUID '" . $_SESSION['user_object']->get_guid() . "'");
	}
	else {
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "No session found");
	}
}

// now figure out what to do based on the URL
$request_router = new RequestRouter($_SERVER['REQUEST_URI'], $_POST);
$request_router->process_uri();

// tear down the database connection
$db->disconnect();

// bye!
exit();
?>
