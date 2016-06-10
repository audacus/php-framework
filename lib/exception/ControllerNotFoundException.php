<?php

class ControllerNotFoundException extends \AbstractException {

	public function __construct($controller) {
		parent::__construct("Controller '".$controller."' could not be found");
	}
}
