<?php

abstract class AbstractException extends \ErrorException {

	const APPLICATION_ERROR = 1337;

	public function __construct($message) {
		parent::__construct($message,
			$this->getCode(),
			self::APPLICATION_ERROR,
			$this->file,
			$this->getLine(),
			$this->getPrevious());
	}
}
