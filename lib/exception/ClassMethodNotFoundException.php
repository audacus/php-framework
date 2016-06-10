<?php

class ClassMethodNotFoundException extends \AbstractException {

	public function __construct($class, $method) {
		parent::__construct("Class method '".get_class($class).'::'.$method.'()'."' could not be found");
	}
}
