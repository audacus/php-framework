<?php
ini_set('upload_max_filesize', '256MB');
ini_set('post_max_size', '256MB');
global $cli;
if (!isset($cli) || !$cli) {
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: X-Requested-With');
}

if (!defined('APPLICATION_PATH')) {
	//$apppath = realpath(dirname(__FILE__));
	define('APPLICATION_PATH', realpath(dirname(__FILE__))); //substr($apppath, 0, strrpos($apppath, DIRECTORY_SEPARATOR)));
}
// define host
if (!defined('HOST')) {
	define('HOST', $_SERVER['HTTP_HOST']);
}
// define base path
if (!defined('BASE_URL')) {
	$self = $_SERVER['PHP_SELF'];
	define('BASE_URL', substr($self, 0, strpos($self, '/index.php')));
}
// define config path
if (!defined('CONFIG_PATH')) {
	define('CONFIG_PATH', APPLICATION_PATH.DIRECTORY_SEPARATOR.'config');
}
// require and initialize config
try {
	require_once APPLICATION_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Config.php';
	Config::parseAndSetConfig(CONFIG_PATH);
} catch (\Exception $e) {
	die($e->getMessage());
}

// add external libraries to include path
set_include_path(get_include_path().PATH_SEPARATOR.APPLICATION_PATH.DIRECTORY_SEPARATOR.Config::get('app.path.ext').PATH_SEPARATOR);

// include all files in the lib folder
$pathPartsLib = array(
	APPLICATION_PATH,
	Config::get('app.path.lib'),
	'*.php'
);
foreach (glob(implode(DIRECTORY_SEPARATOR, $pathPartsLib)) as $lib) {
	require_once $lib;
}

// require abstract exception
require_once Config::get('app.exception.file');

// include all exceptions
$pathPartsException = array(
	APPLICATION_PATH,
	Config::get('app.path.exception'),
	'*.php'
);
foreach (glob(Helper::makePathFromParts($pathPartsException)) as $exception) {
	require_once $exception;
}

// initialize autoloader
require_once Config::get('app.autoloader.file');
spl_autoload_register(array(Config::get('app.autoloader.class'), Config::get('app.autoloader.function')));

// initialize error controller
require_once Config::get('app.errorhandler.file');
set_error_handler(Config::get('app.errorhandler.errorfunction'));
set_exception_handler(Config::get('app.errorhandler.exceptionfunction'));

// require NotORM
require_once Config::get('db.notorm.file');

// start application
// var_dump(get_declared_classes());die();
new App();
