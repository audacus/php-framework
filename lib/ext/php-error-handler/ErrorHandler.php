<?php

abstract class ErrorHandler {

	public static function error($code, $message, $file, $line, array $context = null) {
		static::exception(new Exception($message, $code, new ErrorException($message, $code, 0, $file, $line)));
	}

	abstract public static function exception($e);
}
