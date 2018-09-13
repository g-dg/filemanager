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
define(
	'GARNETDG_FILEMANAGER_VERSION',
	GARNETDG_FILEMANAGER_VERSION_MAJOR . '.' . GARNETDG_FILEMANAGER_VERSION_MINOR . '.' . GARNETDG_FILEMANAGER_VERSION_PATCH . (
		!is_null(GARNETDG_FILEMANAGER_VERSION_PRERELEASE) ?
		'-' . GARNETDG_FILEMANAGER_VERSION_PRERELEASE . (
			!is_null(GARNETDG_FILEMANAGER_VERSION_PRERELEASE_NUMBER) ?
			'.' . GARNETDG_FILEMANAGER_VERSION_PRERELEASE_NUMBER :
			'') :
		''
	)
);

// Application name
define('GARNETDG_FILEMANAGER_NAME', 'Garnet DeGelder\'s File Manager');

// Copyright string
define('GARNETDG_FILEMANAGER_COPYRIGHT_HTML', 'Copyright &copy; 2017-2018 Garnet DeGelder');


// Prevent the user from cancelling the request
ignore_user_abort(true);

// define the exeption for the file manager
class Exception extends \Exception {}

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
Loader::loadDirectory('server');
Loader::loadDirectory('client');

// Run the inits
Loader::executeInits();

// Start the session
Session::start();
