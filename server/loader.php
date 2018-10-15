<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class LoaderException extends Exception {}

/**
 * The inits registerd through registerInit()
 */
$loader_registered_inits = [];

/**
 * Loads the specified directory
 * @param directory The directory (in relation to the current directory)
 * @return bool whether everything was loaded correctly
 * @throws LoaderException when the loader cannot load something
 */
function loader_load($directory)
{
	if (is_dir($directory) && $dh = opendir($directory)) {
		while (($file = readdir($dh)) !== false) {
			// check if ends in '.php' and doesn't start with a dot or underscore
			if (substr($file, -4, 4) === '.php' && substr($file, 0, 1) !== '.' && substr($file, 0, 1) !== '_') {
				if (is_readable($directory . '/' . $file)) {
					require_once($directory . '/' . $file);
				} else {
					throw new LoaderException('Loader could not read "' . $directory . '/' . $file . '".');
				}
			}
		}
	} else {
		throw new LoaderException('Loader could not read "' . $directory . '".');
	}
	return true;
}

/**
 * Executes the inits registered with registerInit()
 */
function loader_execute_inits()
{
	krsort($loader_registered_inits);

	foreach ($loader_registered_inits as $priority_level) {
		foreach ($priority_level as $init_function) {
			call_user_func($init_function);
		}
	}
}

/**
 * Clears the registered inits
 */
function loader_clear_inits()
{
	$loader_registered_inits = [];
}

/**
 * Registers an init
 * @param function the init function to run
 * @param priority which priority to run the init at, higher priority runs first
 */
function loader_register_init($function, $priority = 0)
{
	$loader_registered_inits[$priority][] = $function;
}
