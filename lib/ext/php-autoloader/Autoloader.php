<?php

class Autoloader {

	public static function load($targetClass) {
		require_once 'php-exception/AbstractException.php';
		require_once 'FileNotFoundException.php';
		require_once 'ClassNotFoundException.php';
			
		if (!defined('APPLICATION_PATH')) {
			throw new \Exception('APPLICATION_PATH is not set!');
		}

		$partsTargetClass = explode('\\', $targetClass);
		$path = APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $partsTargetClass).'.php';
		if (file_exists($path)) {
			require_once $path;
			if (!class_exists($targetClass)) {
				throw new ClassNotFoundException($targetClass);
			}
		} else {
			throw new FileNotFoundException($path);
		}
	}
}
