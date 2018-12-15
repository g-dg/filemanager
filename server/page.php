<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

/**
 * Encodes a string as HTML
 * @param string The string to encode
 * @return string The encoded string
 */
function html_encode($string) {
	return htmlspecialchars($string, ENT_HTML5);
}

/**
 * Decodes an HTML string
 * @param string The string to decode
 * @return string The decoded string
 */
function html_decode($string) {
	return html_entity_decode($string, ENT_HTML5);
}

/**
 * Returns the http root directory of the application
 * Note: this may not work well with url rewriting
 * @return string Root path of application
 */
function get_application_http_root_path() {
	return dirname($_SERVER['SCRIPT_NAME']);
}

/**
 * Executes the requested page
 */
function exec_page()
{
	$page_path_array = isset($_SERVER['PATH_INFO']) ? explode('/', trim($_SERVER['PATH_INFO'], '/')) : [];
	$root_page = isset($page_path_array[0]) ? $page_path_array[0] : '';
	$requested_path = $page_path_array;
	array_shift($requested_path);
	$requested_path = '/' . implode('/', $requested_path);
	switch ($root_page) {
		case 'api':
			// run the api handler

			break;
		case 'file':
			// run file serving handler

			break;
		case 'm3u_playlist':
			// run the m3u playlist handler

			break;
		default:
			// send the client page to the client
			exec_client($requested_path);
			break;
	}
}
