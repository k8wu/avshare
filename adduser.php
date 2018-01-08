<?php
// debug
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');

// this needs to happen before any includes
define('_APP', true);

// class includes
require 'inc/Config.class.php';
require 'inc/Logger.class.php';
require 'inc/RequestRouter.class.php';

// source the configuration
require 'conf.php';

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

// add an admin user
require 'inc/AAC.class.php';
$aac = new AAC();
$res = $aac->create_user('admin', 'admin1234', 'test@test.com', $aac::ACCESS_LEVEL_SYSOP);
if(!$res) {
	echo $aac->get_error();
} else {
	print_r($aac);
}
?>
