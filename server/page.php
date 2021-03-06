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
 * This has a leading slash, but no trailing slash
 * Note: this may not work well with url rewriting
 * @return string Root path of application
 */
function get_application_http_root_path()
{
	return dirname($_SERVER['SCRIPT_NAME']);
}

/**
 * Returns the http script path and nmae of the application
 * This has a leading slash, but no trailing slash
 * Note: this may not work well with url rewriting
 * @return string Script path of application
 */
function get_application_http_script_path()
{
	return $_SERVER['SCRIPT_NAME'];
}

/**
 * Encodes a http path
 * @param path the path to encode
 * @return string The encoded path
 */
function http_encode_path($path)
{
	$raw_path_array = explode('/', $path);
	$encoded_path_array = [];
	foreach ($raw_path_array as $pathpart) {
		$encoded_path_array[] = rawurlencode($pathpart);
	}
	return implode('/', $encoded_path_array);
}

/**
 * Encodes a string for inclusion in a query string
 * This encodes '?', '=', '&', so it should not be used for the whole query string
 * @param query The query string part
 * @return string The encoded query string part
 */
function http_encode_query_part($query)
{
	return urlencode($query);
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
	try {
		switch ($root_page) {
			case 'api':
				// run the api handler
				$result = exec_api($requested_path);
				break;
			case 'file':
				// run file serving handler
				$result = send_file($requested_path);
				break;
			case 'm3u_playlist':
				// run the m3u playlist handler
				$result = send_playlist($requested_path);
				break;
			default:
				// send the client page to the client
				$result = exec_client($requested_path);
				break;
		}
		if (!$result) {
			log(LOG_ERR, 'Page handler for "' . $root_page . '" exited with an error status', 'page');
			http_response_code(500);
		}
	} catch (\Exception $e) {
		log(LOG_ERR, 'Page handler for "' . $root_page . '" threw an unhandled exception', 'page', ['exception' => exception_to_array($e)]);
		http_response_code(500);
	}
}
