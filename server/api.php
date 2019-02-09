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
	// parse the api request into an array
	$api_request = array_filter(explode('/', trim($request, '/')), 'strlen');

	// we need something to do
	if (count($api_request) == 0) {
		http_response_code(400);
		return false;
	}

	switch ($api_request[0]) {

		case 'login':

			break;
		
		case 'users':

			break;

		case 'groups':

			break;
		
		case 'mountpoints':

			break;

		case 'filesystem':

			break;

		case 'application':

			break;

		case 'sessions':

			break;

		case 'administration':

			break;
		
		case 'search':

			break;
	}
}
