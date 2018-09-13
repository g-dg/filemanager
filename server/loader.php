<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class Loader
{
	protected static $registered_inits = [];

	public static function loadDirectory($directory) {
		$directory = trim($directory, '/');

		if (is_dir($directory) && $dh = opendir($directory)) {
			while (($file = readdir($dh)) !== false) {
				// check if ends in '.php' and doesn't start with a dot or underscore
				if (substr($file, -4, 4) === '.php' && substr($file, 0, 1) !== '.' && substr($file, 0, 1) !== '_') {
					if (is_readable($directory . '/' . $file)) {
						require_once($directory . '/' . $file);
					} else {
						throw new \Exception('Loader could not read "' . $directory . '/' . $file . '".');
					}
				}
			}
		} else {
			throw new \Exception('Loader could not read "' . $directory . '".');
		}
	}

	public static function executeInits() {
		krsort(self::$registered_inits);

		foreach (self::$registered_inits as $priority_level) {
			foreach ($priority_level as $init_function) {
				call_user_func($init_function);
			}
		}
	}

	public static function clearInits() {
		self::$registered_inits = [];
	}

	public static function registerInit($function, $priority = 0)
	{
		self::$registered_inits[$priority][] = $function;
	}
}
