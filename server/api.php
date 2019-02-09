<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

/**
 * The api handler
 */
function exec_api($request)
{
	// check that the request url is not too long
	if (strlen($request) > 255) {
		http_response_code(414);
		return false;
	}

	// parse the api request into an array
	$api_request = array_filter(explode('/', trim($request, '/')), 'strlen');

	// we need something to do
	if (count($api_request) == 0) {
		http_response_code(400);
		return false;
	}


}
