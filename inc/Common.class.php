<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// common functions that don't belong to one specific category
class Common {
	// functions
	static function get_guid() {
		// log this
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");
		
		// Windows users will have this function available
		if(function_exists('com_create_guid')) {
			return trim(com_create_guid(), '{}');
		}

		// for everyone else, there's this...
		else {
			// probably a good idea to have this
			mt_srand((double) microtime() * 10000);

			// generate the actual GUID
			$charid = strtolower(md5(uniqid(rand(), true)));

			// short-hand for the standard hyphen character
			$hyphen = chr(45);

			// format the GUID
			$guid = substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);

			// return the result to the user
			return $guid;
		}

		// we should never get here
		return false;
	}
}