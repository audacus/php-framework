<?php

class ViewScriptNotFoundException extends \AbstractException {

	public function __construct($viewScript) {
		parent::__construct("View script could not be opened: ".$viewScript." ".$this->getFile());
	}
}
