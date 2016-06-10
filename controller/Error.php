<?php

namespace controller;

/*
 * Error handler. Called when something really bad happended. In production use this should never be used
 * Prints a more meaningful error message than the standard php compiler
 */
class Error extends \ErrorHandler {

	/* contructor */
	public static function exception($e) {
		$severity = 1 * E_ERROR
			| 1 * E_WARNING
			| 1 * E_PARSE
			| 1 * E_NOTICE
			| 1 * E_CORE_ERROR
			| 1 * E_CORE_WARNING
			| 1 * E_COMPILE_ERROR
			| 1 * E_COMPILE_WARNING
			| 1 * E_USER_ERROR
			| 1 * E_USER_WARNING
			| 1 * E_USER_NOTICE
			| 1 * E_STRICT
			| 1 * E_RECOVERABLE_ERROR
			| 1 * E_DEPRECATED
			| 1 * E_USER_DEPRECATED;

		if ($e instanceof \ErrorException && (($e->getSeverity() & $severity) != 0 || $e->getSeverity() === 0)) {
			switch ($e->getSeverity()) {
			case E_USER_ERROR:
				echo "<b>USER ERROR</b> ".$e->getCode()." ".$e->getMessage()."<br />\n";
				echo "Fatal error on line ".$e->getLine()." in file ".$e->getFile();
				echo ", PHP ".PHP_VERSION." (".PHP_OS.")<br />\n";
				echo "Aborting...<br />\n";
				exit(1);
				break;
			case E_USER_WARNING:
				echo "<b>WARNING</b><br />\n";
				self::printException($e);
				break;
			case E_USER_NOTICE:
				echo "<b>NOTICE</b><br />\n";
				self::printException($e);
				break;
			case E_STRICT:
				echo "<b>STRICT</b><br />\n";
				self::printException($e);
				break;
			default:
				self::printException($e);
				break;
			}
		} else {
			self::printException($e);
		}
		// Don't execute PHP internal error handler
		return true;
	}

	/*
	 * Do some pretty printing
	 */
	private static function printException($e) {
		echo "<b>".get_class($e)."</b> ".$e->getMessage()." on line ".$e->getLine()." in file ".$e->getFile()."<pre>".$e->getTraceAsString()."</pre>";
	}
}
