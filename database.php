<?php
	//Author: Jake Bisson
	//Date: 2020-11-16
	class Database {

		private $connection; // the connection variable
		private $tables; // the table objects
		private $sequences; // the sequence objects 
		private $procedures; // the procedure objects
		private $isConnected; // defines if the database is connected or not

		//constructs the database object and sets the instance variables to null
		public function __contruct() {
			$this->connection = null;
			$this->isConnected = false;
			$this->tables = array();
			$this->sequences = array();
			$this->procedures = array();
		}

		//Establishes a connection to an oracle database
		//@param $username (String) username connecting to the database
		//@param $password (String) password connecting to the database
		//@param $connectionString (String) connection string for the database
		//@param $charset (String) specifies the charset for the connection to the database
		public function connect($username, $password, $connectionString, $charset) {
			$conn = oci_connect($username, $password, $connectionString, $charset);
			if ($conn) {
				$this->isConnected = true;
				$this->connection = $conn;
			} else {
				$this->isConnected = false;
				$this->connection = null;
			}
		}

		//Disconnects the currently connected database
		//@return (bool) True - disconnected successfully, False - failed to disconnect
		public function disconnect() {
			if (!$this->isConnected) {
				return true;
			}
			$success = oci_close($this->connection);
			if ($success) {
				$this->connection = null;
				$this->isConnected = false;
			}
			
			return $success;
		}

		//Returns a boolean that identifies if the database is connected or not
		//@return (boolean) true - database connected, false - Data not connected
		public function isConnected() {
			return $this->isConnected;
		}

		//Adds a table object to the database object, the database is also set as the tables database parameter
		//@param $table (Table) defines the table to be added to the database object
		public function addTable($table) {
			$schema = strtoupper($table->getSchema());
			$name = strtoupper($table->getName());
			$this->tables["$schema.$name"] = $table;
			$table->setDatabase($this);
		}

		//Returns a table object
		//@param $schema (String) defines the schema of the table
		//@param $name (String) defines the name of the table
		//@return (Table) the table that was specified by the schema and name
		public function table($schema, $name) {
			$schema = strtoupper($schema);
			$name = strtoupper($name);
			return $this->tables["$schema.$name"];
		}

		//Adds a sequence object to this object, the database is also set as the sequences database parameter
		//@param $sequence (Sequence) defines teh sequence to be added to the database object
		public function addSequence($sequence) {
			$schema = strtoupper($sequence->getSchema());
			$name = strtoupper($sequence->getName());
			$this->sequences["$schema.$name"] = $sequence;
			$sequence->setDatabase($this);
		}

		//Returns a sequence object
		//@param $schema (String) defines the schema of the sequence
		//@param $name (String) defines the name of the sequence
		//@return (Sequence) the sequence that was specified by the schema and name
		public function sequence($schema, $name) {
			$schema = strtoupper($schema);
			$name = strtoupper($name);
			return $this->sequences["$schema.$name"];
		}

		//Adds a procedure object to this object, the database is also set as the procedure's database parameter
		//@param $procedure (Procedure) defines the procedure to be added to the database object
		public function addProcedure($procedure) {
			$schema = strtoupper($procedure->getSchema());
			$name = strtoupper($procedure->getName());
			$this->procedures["$schema.$name"] = $procedure;
			$procedure->setDatabase($this);
		}

		//Returns a procedure object
		//@param $schema (String) defines the schema of the procedure
		//@param $name (String) defines the name of the procedure
		//@return (Procedure) the procedure that was specified by the schema and name
		public function procedure($schema, $name) {
			$schema = strtoupper($schema);
			$name = strtoupper($name);
			return $this->procedures["$schema.$name"];
		}

		//Executes a query that reads from the database (select queries)
		//@param $query (String) The query to be executed
		//@return returns the stid variable that was used to execute the query
		public function executeRead($query) {
			if ($this->isConnected()) {
				$stid = oci_parse($this->connection, $query);
				$r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
				if (!$r) {
					echo $query;
					return null;
				}
				return $stid;
			} else {
				return null;
			}
		}

		//Executes a query that writes data to the database (insert, update, and delete queries)
		//@param $query (String) The query to be executed
		//@return (boolean) true - query was successful, false - query was not successful
		public function executeWrite($query) {
			if (!$this->isConnected()) {
				return false;
			}

			$stid = oci_parse($this->connection, $query);
			$r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
			if (!$r){
				echo $query;
				oci_rollback($this->connection);
				return false;
			}
			return true;
		}

		//Parses the query into an stid variable
		//@param $query (String) the query to be parsed
		//@return (stid) the stid variable generated from the parsing of the query
		public function parse($query) {
			if (!$this->isConnected()) {
				return null;
			}
			$stid = oci_parse($this->connection, $query);
			return $stid;
		}

		//Executes the query contained in the stid variable
		//@param $stid (stid) the stid to execute
		//@return (bool) true - execution succesful, false - execution failed
		public function execute($stid) {
			if(!$this->isConnected()) {
				return false;
			}
			$r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
			if (!$r) {
				oci_rollback($this->connection);
				return false;
			}
			return true;
		}

		//Commits any changes to the database, equivalent to COMMIT
		//@return (boolean) true - commit was successful, false - commit failed
		public function commit() {
			if ($this->isConnected()) {
				$success = oci_commit($this->connection);
				if (!$success) {
					oci_rollback($this->connection);
					return false;
				}
				return true;
			} else {
				return false;
			}
		}

		//Rolls back any changes made to the database, equivalent to ROLLBACK
		//@return (boolean) true - rollback succesful, false - rollback failed
		public function rollback() {
			if ($this->isConnected()) {
				$success = oci_rollback($this->connection);
				return $success;
			} else {
				return false;
			}
		}

		//Reads results from an stid resulting from a read query into array format
		//@param $stid the stid of the read query that was executed
		//@return (array) returns an array containing the data that was read
		public function getResults($stid) {
			if ($stid != null) {
				//construct array that contains a row for each of its indexes
				$results = array();
				$i = 0;
				while ($row = oci_fetch_array($stid)) {
					$results[$i] = $row;
					$i++;
				}
				return $results;
			} else {
				return array();
			}	
		}

		//executes a custom select function on the database
		//@param $columns (array) specifies the columns to be selected
		//@param $tables (array) specifies the tables to select the data from
		//@param $conditions (String) specifies the conditions of the query after the WHERE statement
		//@return (array) returns an array containing the data read from the database
		public function select($columns, $tables, $conditions) {
			//check if the database is connected and variables are valid
			if (!$this->isConnected() || $tables == null) {
				return array();
			}

			$query = "SELECT ";
			$stid = null;

			//add cols to the query
			$counter = 1;
			foreach ($columns as $col) {
				$query = $query . $col;
				if ($counter != sizeof($columns)) {
					$query = $query . ", ";
				} else {
					$query = $query . " ";
				}
				$counter++;
			}

			//add tables to the query
			$query = $query . " FROM ";
			$counter = 1; 
			foreach($tables as $table => $tableName) {
				$query = $query . $table . " " . $tableName;
				if ($counter != sizeof($tables)) {
					$query = $query . ", ";
				} else {
					$query = $query . " ";
				}
				$counter++;
			}

			//add where statement to the query
			if ($conditions != "" || $conditions != null) {
				$query = $query . " WHERE ";
				$query = $query . $conditions;
			}

			$stid = $this->executeRead($query);
			$results = $this->getResults($stid);
			return $results;
		}

		//executes custom insert function on the database
		//@param $table (String) the table data is being inserted to (schema must also be specified <schema>.<table>)
		//@param $values (Array) The values to be inserted in form col=>value
		//@return (bool) True - Query executed successfully, False - query failed to execute
		public function insert($table, $values) {
			//check if database is connected and validate parameters
			if (!$this->isConnected() || sizeof($values) <= 0 || $table == "" || $table == null) {
				return false;
			}

			$query = "INSERT INTO " . $table;

			//build the cols and values pieces
			$colString = "(";
			$valueString = "(";
			$counter = 1;
			foreach ($values as $col => $value) {
				$colString = $colString . $col;
				$valueString = $valueString . "'" . $value . "'";
				if ($counter != sizeof($values)) {
					$colString = $colString . ", ";
					$valueString = $valueString . ", ";
				}
				$counter++;
			}
			$colString = $colString . ")";
			$valueString = $valueString . ")";

			//combine into the final query
			$query = $query . " " . $colString . " VALUES " . $valueString;

			//execute the query and return success
			$success = $this->executeWrite($query);
			return $success;
		}

		//executes custom update function on the database
		//@param $table (String) The table to be updated
		//@param $values (Array) The values to be updated in the form col=>value
		//@param $conditions (String) The conditions of the query after the WHERE keyword
		//@return (bool) true - query was successful, false - query was unsuccessful
		public function update($table, $values, $conditions) {
			//check if database is connected and validate parameters
			if (!$this->isConnected() || $table == "" || $table == null || sizeof($values) <= 0) {
				return false;
			}

			$query = "UPDATE " . $table . " SET ";

			//build col and values portion
			$counter = 1;
			foreach ($values as $col => $value) {
				//if the value does not require the single quotes don't add them
				if ($value == "DEFAULT") {
					$query = $query . $col . "=" . $value;
				} else {
					$query = $query . $col . "='" . $value . "'";
				}
				if ($counter != sizeof($values)) {
					$query = $query . ", ";
				} else {
					$query = $query . " ";
				}
				$counter++;
			}

			//add where conditions
			$query = $query . "WHERE " . $conditions;

			//execute query and return success
			$success = $this->executeWrite($query);
			return $success;
		}

		//executes custom delete function on the database
		//@param $conditions (String) the conditions of the query after the where keyword
		//@return (bool) true - Query was succesful, false - query was unsuccessful
		public function delete($table, $conditions) {
			if (!$this->isConnected()) {
				return false;
			}

			$query = "DELETE FROM " . $table;

			if ($conditions != "" && $conditions != null) {
				$query = $query . " WHERE " . $conditions;
			}

			//execute the query
			$success = $this->executeWrite($query);
			return $success;
		}
	}
?>