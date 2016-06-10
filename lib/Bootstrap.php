<?php

class Bootstrap {

	public function __construct() {
		// call all init methods
		foreach (get_class_methods($this) as $method) {
			if (strpos($method, 'init') === 0) {
				$this->$method();
			}
		}
	}

	private function initDatabase() {
		Database::setDb();
	}
}
