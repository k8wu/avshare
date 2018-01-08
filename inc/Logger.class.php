<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// class definition
class Logger {
	// constants
	const LOGGER_INFO = 1;
	const LOGGER_WARN = 2;
	const LOGGER_ERROR = 4;
	const LOGGER_FATAL_ERROR = 8;
	const LOGGER_DEBUG = 16;
	const LOGGER_ALL = 31;

	// properties
	private $level;
	private $location;
	private $error;

	// constructor method
	function __construct($level, $location) {
		$this->level = $level;
		$this->location = $location;
	}

	// functions
	public function emit($msg_level, $class_name, $function_name, $msg) {
		// does the message level pass the level filter?
		if($this->level & $msg_level == $msg_level) {
			$log_file = fopen($this->location, 'a+');

			// was there some issue with opening the file?
			if(!$log_file) {
				die('Cannot open the log file for writing!');
			}

			// construct the log message
			$log_msg = '[' . date('Y-m-d H:i:s O') . '] ';
			$log_msg .= '[' . $this->get_string_from_level($msg_level) . '] ';
			$log_msg .= ($class_name ? $class_name : '(N/A)') . '::';
			$log_msg .= ($function_name ? $function_name : '(N/A)') . ': ';
			$log_msg .= $msg . "\n";

			// attempt to write the log message
			$log_write = fwrite($log_file, $log_msg);
			fclose($log_file);

			// the return value depends on whether the log write attempt was successful
			if($log_write === false) {
				die('Cannot write to the open log file!');
			}
		}

		// if we reach this point, everything went well
		return true;
	}

	public function get_error() {
		return $this->error;
	}

	// this will be used in case the log file cannot be opened or appended
	public function set_error($error_msg) {
		$this->error = $error_msg;
	}

	public function get_string_from_level($level) {
		// probably the least ugly way to implement this
		$level_strings = array(
			1 => 'INFO',
			2 => 'WARN',
			4 => 'ERROR',
			8 => 'FATAL_ERROR',
			16 => 'DEBUG'
		);

		// if it's not a valid level, it will be 'UNKNOWN'
		if(!isset($level_strings[$level]) || strlen($level_strings[$level]) == 0) {
			return 'UNKNOWN';
		}

		// otherwise, it's valid, and can be returned from the array
		return $level_strings[$level];
	}
}
