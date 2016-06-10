<?php

class JsonDecodeException extends \AbstractException {

	public function __construct($json) {
		parent::__construct("Could not decode json: '".$json."'");
	}
}
