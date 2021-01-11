<?php
	class Procedure {
		private $database;
		private $schema;
		private $name;
		private $parameters;
		private $parameterDefinitions;

		public function __construct($schema, $name, $parameterDefinitions) {
			$this->database = null;
			$this->schema = $schema;
			$this->name = $name;
			$this->parameterDefinitions = $parameterDefinitions;
			$this->parameters = array();

			//build the parameters array based on the parameter definitions
			foreach ($parameterDefinitions as $definition) {
				$this->parameters[$definition] = "";
			}
		}

		public function setParameters($params) {
			foreach ($params as $param=>$value) {
				$this->setParameter($param, $value);
			}
		}

		public function setParameter($param, $value) {
			if (isset($this->parameters[$param])) {
				$this->parameters[$param] = $value;
			}
		}

		public function getParameters() {
			return $this->parameters;
		}

		public function getParameter($name) {
			return $this->parameters[$name];
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

		//executes the procedure and returns the data from the procedure
		public function execute() {
			$query = "BEGIN " . $this->schema . "." . $this->name . "(";

			//adding parameters to the query
			$counter = 1;
			foreach ($this->parameterDefinitions as $param) {
				$query = $query . ":" . $param;
				if ($counter != sizeof($this->parameters)) {
					$query = $query . ", ";
				}
				$counter++;
			}

			$query = $query . "); END;";

			//parse the query
			$stid = $this->database->parse($query);

			//bind parameters to the query, 4000 is the max buffer size for the variable
			foreach($this->parameters as $param=>$value) {
				oci_bind_by_name($stid, ":$param", $this->parameters[$param], 4000);
			}
			echo $query;
			$success = $this->database->execute($stid);
			return $success;
		}
	}
?>
