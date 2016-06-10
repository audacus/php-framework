<?php

class ViewNotFoundException extends \AbstractException {

	public function __construct($view) {
		parent::__construct("View '".$view."' could not be found: ".$this->getFile());
	}
}
