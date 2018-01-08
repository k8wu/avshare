<?php
// this may not be run directly
if(!defined('_APP')) die('Cannot be executed directly!');

// class definition
class DatabaseHandler {
	// database engine - "mysql" and "mysqli" are both supported for those who are
	// still on PHP 5.x
	private $engine;

	// database essentials
	private $user;
	private $pass;
	private $dbname;
	private $host;
   
	// needed for using this class
	public $connection;
	private $query_result;

	// if there is an error, it will be held here
	public $error_message;

	// now we define the methods, which are dependent on the engine
	function __construct($user, $pass, $dbname, $host = 'localhost', $engine = 'mysqli') {
		$this->user = $user;
		$this->pass = $pass;
		$this->dbname = $dbname;
		$this->host = $host;
		$this->engine = $engine;
	}

	function connect() {
		if($this->engine == "mysql") {
			$conn = mysql_connect($this->host, $this->user, $this->pass);
			if(!$conn) {
				// this is a terrible way to handle errors
				//die("Error connecting to database server!");

				// this is slightly better
				$this->error_message = 'Failed to connect to the database server!';
				global $logger;
				$logger->emit($logger::LOGGER_ERROR, __CLASS__, __FUNCTION__, $this->error_message);
				return null;
			}
			else {
				$res = mysql_select_db($this->dbname, $conn);
				if(!$res) {
					$this->error_message = 'Failed to select the database!';
					global $logger;
					$logger->emit($logger::LOGGER_ERROR, __CLASS__, __FUNCTION__, $this->error_message);
					return null;
				}
			}
		}
		else if($this->engine == "mysqli") {
			$conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
			if($conn->error) {
				$this->error_message = $conn->error;
				global $logger;
				$logger->emit($logger::LOGGER_ERROR, __CLASS__, __FUNCTION__, $this->error_message);
				return null;
			}
		}

		// define the connection var
		$this->connection = $conn;
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Database connection has been established");
	}

	// handle a database query
	public function query($query) {
		// clear the result array
		unset($this->query_result);
		
		// different PHP engines handle this differently
		if($this->engine == "mysql") {
			// run the query and catch any errors that result
			$result = mysql_query($query, $this->connection);
			if(!$result) {
				$this->error_message = 'Failed to execute the query!';
				global $logger;
				$logger->emit($logger::LOGGER_ERROR, __CLASS__, __FUNCTION__, $this->error_message);
				return false;
			}

			// parse the result var and create an array with the data
			if($result !== TRUE) {
				$i = 0;
				while($row = mysql_fetch_array($result)) {
					$this->query_result[$i] = $row;
					$i++;
				}
			}
		}
		else if($this->engine == "mysqli") {
			// run the query and catch any errors that result
			$result = $this->connection->query($query);
			if(!$result) {
				$this->error_message = $this->connection->error;
				global $logger;
				$logger->emit($logger::LOGGER_ERROR, __CLASS__, __FUNCTION__, $this->error_message);
				return false;
			}

			// build the array if there exists a result set
			if($result !== TRUE) {
				// iterate through results
				$i = 0;
				while($row = $result->fetch_assoc()) {
					$this->query_result[$i] = $row;
					$i++;
				}

				// close the result set
				$result->close();
			}
		}
		
		// log that we executed a query
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Query executed: ${query}");

		// return the query result if it exists (also available as a var obj)
		if(isset($this->query_result)) {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Query returned results");
			return $this->query_result;
		}
		else if(isset($result)) {
			$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Query returned no results (but ran without errors)");
			return true;
		}
		else {
			$logger->emit($logger::LOGGER_WARN, __CLASS__, __FUNCTION__, "Query returned no results (with errors)");
		}
	}

	// sanitize input
	public function sanitize_input($input_str) {
		// there are two different ways of doing this depending on backend
		if($this->engine == "mysql") {
			$sanitized_str = mysql_real_escape_string($input_str, $this->connection);
		}
		else if($this->engine == "mysqli") {
			$sanitized_str = $this->connection->real_escape_string($input_str);
		}

		// return the result
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Function called");
		return $sanitized_str;
	}

	// close a database connection
	public function disconnect() {
		if($this->engine == "mysql") {
			mysql_close($this->connection);
		}
		else if($this->engine == "mysqli") {
			$this->connection->close();
		}
		global $logger;
		$logger->emit($logger::LOGGER_INFO, __CLASS__, __FUNCTION__, "Database connection closed");
	}
}
