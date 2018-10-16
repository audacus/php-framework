<?php

class Config {

	const DEFAULT_DELIMITER = '.';
	private static $array = array();
	private static $delimiter = self::DEFAULT_DELIMITER;

	public static function get($property = null, $currentPosition = null) {
		if (empty($currentPosition)) {
			$currentPosition = self::$array;
		}
		$propertyArray = explode(self::$delimiter, $property);
		if (count($propertyArray) > 1 && isset($currentPosition[$propertyArray[0]])) {
			$currentPosition = $currentPosition[$propertyArray[0]];
			array_shift($propertyArray);
			$property = self::get(implode(self::$delimiter, $propertyArray), $currentPosition);
		} else {
			$property = isset($currentPosition[$property]) ? $currentPosition[$property] : null;
		}
		return $property;
	}

	public static function setDelimiter($delimiter = null) {
		if (!empty($delimiter)) {
			$delimiter = self::DEFAULT_DELIMITER;
		}
		self::$delimiter = $delimiter;
		return self::$delimiter;
	}

	public static function setConfig(array $config) {
		self::$array = $config;
	}

	public static function parseConfig($configPath) {
		$configFiles = array();
		$config = array();
		$configPath = $configPath.DIRECTORY_SEPARATOR;
		$configFiles[] = 'default.json';
		$userConfigs = false;
		$counter = 0;
		while (count($configFiles) > $counter) {
			$fileName = $configFiles[$counter];
			if (!empty($fileName)) {
				$configFile = $configPath.$configFiles[$counter];

				if (file_exists($configFile)) {
					$tmpConfig = json_decode(file_get_contents($configFile), true);
					if (!is_null($tmpConfig)) {
						if (isset($tmpConfig['app']['path']['config'])) {
							// TODO 2016-05-28 dave: describe following lines
							$configFiles = array_replace_recursive($configFiles, array_filter(array_values($tmpConfig['app']['path']['config']), function($value) {
								return !is_array($value) ? strpos($value, 'default') === false : false;
							}));
							// read in os config
							if (isset($tmpConfig['app']['path']['config']['os'][strtolower(PHP_OS)])) {
								$configFiles[] = $tmpConfig['app']['path']['config']['os'][strtolower(PHP_OS)];
							}
						}
						$config = array_replace_recursive($config, $tmpConfig);
						// read in userconfigs at the end
						if ($counter+1 == count($configFiles)
							&& !$userConfigs
							&& isset($config['app']['path']['config']['userconfig']['prefix'])
							&& !empty($config['app']['path']['config']['userconfig']['prefix'])) {
							$prefix = $config['app']['path']['config']['userconfig']['prefix'];
							foreach (glob($configPath.$prefix.'*.json') as $userconfig) {
								$configFiles[] = basename($userconfig);
							}
							$userConfigs = true;
						}
					} else {
						throw new \Exception('Could not decode config file: '.$configFile);
					}
				} else {
					die($configFile);
					throw new \FileNotFoundException($configFile);
				}
			}
			$counter++;
		}
		return $config;
	}

	public static function parseAndSetConfig($configPath) {
		self::setConfig(self::parseConfig($configPath));
	}
}
