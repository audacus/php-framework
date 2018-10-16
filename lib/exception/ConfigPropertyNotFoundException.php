<?php

class ConfigPropertyNotFoundException extends \AbstractException {

	public function __construct($property) {
		parent::__construct("Property '".$property."' could not be found in the config");
	}

}
