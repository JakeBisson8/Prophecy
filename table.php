<?php
	//Author: Jake Bisson
	//Date: 2020-11-16
	class Table {
		private $database;
		private $schema;
		private $name;
		private $columns;

		public function __construct($schema, $name, $columns) {
			$this->database = null;
			$this->schema = $schema;
			$this->name = $name;
			$this->columns = $columns;
		}

		public function getColumn($col) {
			return $this->columns[$col];
		}

		public function getColumns() {
			return $this->columns;
		}

		public function getName() {
			return $this->name;
		}

		public function getSchema() {
			return $this->schema;
		}

		public function getDatabase() {
			return $this->database;
		}

		public function setDatabase($database) {
			$this->database = $database;
		}

		//runs select query
		public function select($conditions) {
			if (!$this->database->isConnected()) {
				return array();
			}

			$query = "SELECT ";
			$stid = null;

			//add cols to the query
			$counter = 1;
			foreach ($this->columns as $col) {
				$query = $query . $col;
				if ($counter != sizeof($this->columns)) {
					$query = $query . ", ";
				} else {
					$query = $query . " ";
				}
				$counter++;
			}

			//add table to the query
			$query = $query . " FROM " . $this->schema . "." . $this->name;

			//add where statement to the query
			if ($conditions != "" && $conditions != null) {
				$query = $query . " WHERE ";
				$query = $query . $conditions;
			}
			$stid = $this->database->executeRead($query);
			$results = $this->database->getResults($stid);
			return $results;	
		}

		//runs insert query
		public function insert($values) {
			if (!$this->database->isConnected()) {
				return false;
			}

			$query = "INSERT INTO " . $this->schema . "." . $this->name;

			//building the cols and values pieces
			$colString = "(";
			$valueString = "(";
			$counter = 1;
			$size = sizeof($values); // defines the total number of valid fields contained in $values
			foreach ($values as $col=>$value) {
				//check if the current col is a valid column in the table
				if (isset($this->columns[$col])) {
					//if the counter is not equal to the total number of valid fields and it is not the first valid field, add the comma.
					if ($counter != $size && $counter != 1) {
						$colString = $colString . ", ";
						$valueString = $valueString . ", ";
					}
					$colString = $colString . $col;
					if ($value == "DEFAULT") {
						$valueString = $valueString . $value;
					} else {
						$valueString = $valueString . "'" . $value . "'";
					}
					$counter++;
				} else { //current column was not a valid column in the table
					$size--;
				}
			}

			$colString = $colString . ")";
			$valueString = $valueString . ")";

			//build the query
			$query = $query . " " . $colString . " VALUES " . $valueString;

			//execute the query
			$success = $this->database->executeWrite($query);
			return $success;
		}

		//runs update query
		public function update($values, $conditions){
			if (!$this->database->isConnected()) {
				return false;
			}

			$query = "UPDATE " . $this->schema . "." . $this->name . " SET ";

			$counter = 1;
			$size = sizeof($values);
			foreach($values as $col => $value) {
				//check if the current col is a valid column in the table
				if (isset($this->columns[$col])) {
					//if the counter is not equal to the total number of valid fields and it is not the first valid field, add the comma.
					if ($counter != $size && $counter != 1) {
						$query = $query . ", ";
					}
					if ($value == "DEFAULT") {
						$query = $query . $col . "=" . $value;
					} else {
						$query = $query . $col . "='" . $value . "'";
					}
					$counter++;
				} else { //current column was not a valid column in the table
					$size--;
				}
			}

			//add the conditions
			if ($conditions != "" && $conditions != null) {
				$query = $query. " WHERE " . $conditions;
			}

			//execute the query
			$success = $this->database->executeWrite($query);
			return $success;
		}

		//runs delete query
		public function delete($conditions) {
			if (!$this->database->isConnected()) {
				return false;
			}

			$query = "DELETE FROM " . $this->schema . "." . $this->name;

			if ($conditions != "" && $conditions != null) {
				$query = $query . " WHERE " . $conditions;
			}

			//execute the query
			$success = $this->database->executeWrite($query);
			return $success;
		}
	}
?>