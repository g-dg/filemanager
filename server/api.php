<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

$api_request = [];
$api_request_ptr = 0;
$api_response = [];

/**
 * The api handler
 */
function exec_api($request)
{
	global $api_request, $api_request_ptr, $api_response;

	// check that the request url is not too long
	if (strlen($request) > 255) {
		http_response_code(414);
		return false;
	}

	// parse the api request into an array
	$api_request = array_filter(explode('/', trim($request, '/')), 'strlen');
	$api_request_ptr = 0;
	$api_response = [];

	// we need something to do
	if (count($api_request) == 0) {
		http_response_code(400);
		return false;
	}

	
}
