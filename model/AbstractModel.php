<?php

namespace model;

abstract class AbstractModel implements \JsonSerializable {

	public function __call($name, array $argument) {
		$return = null;
		$getter = strpos($name, 'get') === 0;
		$setter = strpos($name, 'set') === 0;
		if ($getter || $setter) {
			$property = lcfirst(substr($name, 3));
			if (property_exists($this, $property)) {
				if ($getter) {
					$return = $this->$property;
				} else if ($setter) {
					$this->$property = current($argument);
					$return = $this;
				}
			} else {
				// echo 'property does not exist: '.get_class($this).'::'.$property;
				// throw new PropertyDoesNotExistException($this, $property);
			}
		}
		return $return;
	}

	public function __construct(array $data = array(), array $initArgs = array()) {
		if (!empty($data)) {
			$this->fromArray($data);
		}
		$this->init($initArgs);
	}

	protected function init($initArgs) {}

	public function fromArray(array $data) {
		foreach ($data as $key => $value) {
			$method = 'set'.ucfirst($key);
			$this->$method($value);
		}
		return $this;
	}

	public function toArray($emptyValues = false) {
		$data = array();
		$properties = get_object_vars($this);
		foreach ($properties as $property => $value) {
			if (!empty($value) || $emptyValues) {
				$method = 'get'.ucfirst($property);
				$data[$property] = $this->$method();
			}
		}
		return $data;
	}
	
	public function jsonSerialize() {
		return get_object_vars($this);
	}
}
