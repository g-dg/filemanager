<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class LoaderException extends Exception {}

/**
 * Loads a directory
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
