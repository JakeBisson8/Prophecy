<?php
	//Author: Jake Bisson
	//Date: 2020-11-16
	class Sequence {
		private $database;
		private $schema;
		private $name;

		public function __construct($schema, $name) {
			$this->database = null;
			$this->schema = $schema;
			$this->name = $name;
		}

		public function getSchema() {
			return $this->schema;
		}

		public function getName() {
			return $this->name;
		}

		public function getDatabase() {
			return $this->database;
		}

		public function setDatabase($database) {
			$this->database = $database;
		}

		//returns the next value in the sequence, equivalent to NEXTVAL
		public function nextval() {
			if ($this->database->isConnected()) {
				$query = "SELECT " . $this->schema . "." . $this->name . ".NEXTVAL FROM DUAL";
				$stid = $this->database->executeRead($query);
				$results = $this->database->getResults($stid);
				foreach ($results as $row) {
					return $row['NEXTVAL'];
				}
			} else {
				return null;
			}
		}

		//returns the current value in the sequence, equivalent to CURRVAL
		public function currval() {
			if ($this->database->isConnected()) {
				$query = "SELECT " . $this->schema . "." . $this->name . ".CURRVAL FROM DUAL";
				$stid = $this->database->executeRead($query);
				$results = $this->database->getResults($stid);
				foreach ($results as $row) {
					return $row['NEXTVAL'];
				}
			} else {
				return null;
			}
		}
	}
?>