<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class LoaderException extends Exception {}

class Loader
{
	/**
	 * The inits registerd through registerInit()
	 */
	protected static $registered_inits = [];

	/**
	 * Loads the specified directory
	 * @param directory The directory (in relation to the current directory)
	 * @return bool whether everything was loaded correctly
	 * @throws LoaderException when the loader cannot load something
	 */
	public static function loadDirectory($directory) {
		$directory = trim($directory, '/');

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
	 * @return bool whether the inits were successfully executed
	 */
	public static function executeInits() {
		krsort(self::$registered_inits);

		foreach (self::$registered_inits as $priority_level) {
			foreach ($priority_level as $init_function) {
				call_user_func($init_function);
			}
		}
		return true;
	}

	/**
	 * Clears the registered inits
	 * @return bool whether all inits were cleared
	 */
	public static function clearInits() {
		self::$registered_inits = [];
		return true;
	}

	/**
	 * Registers an init
	 * @param function the init function to run
	 * @param priority which priority to run the init at, higher priority runs first
	 * @return bool whether the init was successfully registered
	 */
	public static function registerInit($function, $priority = 0)
	{
		self::$registered_inits[$priority][] = $function;
		return true;
	}
}
