<?php
namespace GarnetDG\FileManager;

// Define constants

// For loaded files to know that they aren't being executed directly
define('GARNETDG_FILEMANAGER', true);

// Version info
define('GARNETDG_FILEMANAGER_VERSION_MAJOR', 3);
define('GARNETDG_FILEMANAGER_VERSION_MINOR', 0);
define('GARNETDG_FILEMANAGER_VERSION_PATCH', 0);
define('GARNETDG_FILEMANAGER_VERSION_PRERELEASE', 'alpha');
define('GARNETDG_FILEMANAGER_VERSION_PRERELEASE_NUMBER', null);

// Generate version string
define('GARNETDG_FILEMANAGER_VERSION',
	GARNETDG_FILEMANAGER_VERSION_MAJOR . '.' . GARNETDG_FILEMANAGER_VERSION_MINOR . '.' . GARNETDG_FILEMANAGER_VERSION_PATCH . ( // basic x.y.z
		!is_null(GARNETDG_FILEMANAGER_VERSION_PRERELEASE) ? // if there is a prerelease type
		'-' . GARNETDG_FILEMANAGER_VERSION_PRERELEASE . ( // append the prerelease type.
			!is_null(GARNETDG_FILEMANAGER_VERSION_PRERELEASE_NUMBER) ? // if there is a prerelease number,
			'.' . GARNETDG_FILEMANAGER_VERSION_PRERELEASE_NUMBER : // append the prerelease number.
			'') :
		''
	)
);

// Application name
define('GARNETDG_FILEMANAGER_NAME', 'Garnet DeGelder\'s File Manager');

// Copyright string
define('GARNETDG_FILEMANAGER_COPYRIGHT', 'Copyright (c) 2017-2019 Garnet DeGelder');
define('GARNETDG_FILEMANAGER_COPYRIGHT_HTML', 'Copyright &copy; 2017-2019 Garnet DeGelder');


// Prevent the user from cancelling the request
ignore_user_abort(true);

// define exceptions in this namespace
class Exception extends \Exception {}
class LogicException extends Exception {}
class BadFunctionCallException extends LogicException {}
class BadMethodCallException extends BadFunctionCallException {}
class DomainException extends LogicException {}
class InvalidArgumentException extends LogicException{}
class LengthException extends LogicException {}
class OutOfRangeException extends LogicException {}
class RuntimeException extends Exception {}
class OutOfBoundsException extends RuntimeException {}
class OverflowException extends RuntimeException {}
class RangeException extends RuntimeException {}
class UnderflowException extends RuntimeException {}
class UnexpectedValueException extends RuntimeException {}

// Load the config file
require_once('config.php');

// Turn on error reporting if in debug mode
if (defined('GARNETDG_FILEMANAGER_DEBUG_ENABLE') && GARNETDG_FILEMANAGER_DEBUG_ENABLE) {
	error_reporting(E_ALL);
	ini_set('display_errors', 'On');
}

// Load the loader
require_once('server/loader.php');

// Execute the loader
loader_load('server');
loader_load('client');

// Execute the requested page
exec_page();
