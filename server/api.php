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

	// the majority of these will return JSON
	header('Content-Type: application/json');

	$session_id = null;
	if (isset($_GET['_sessid']))
		$session_id = $_GET['_sessid'];
	if (isset($_POST['_sessid']))
		$session_id = $_POST['_sessid'];

	// start session if required
	if (!($api_request[0] === 'login')) {
		if (!is_null($session_id)) {
			session_start($session_id);
		} else {
			log(LOG_ERR, 'Session ID not passed to api endpoint that requires it', 'api');
			http_response_code(400);
			return false;
		}
	}

	switch ($api_request[0]) {

		case 'login':
			if (isset($_POST['username'], $_POST['password'])) {
				
			} else {
				http_response_code(400);
				return false;
			}
			break;
		
		case 'logout':
			session_start($session_id);
			session_destroy();
			break;
		
		case 'users':

			break;

		case 'groups':

			break;
		
		case 'mountpoints':

			break;

		case 'filesystem':
			if (count($api_request) >= 2) {
				switch ($api_request[1]) {
					case 'copy':

						break;
					case 'create':

						break;
					case 'delete':

						break;
					case 'mkdir':

						break;
					case 'read':

						break;
					case 'readdir':

						break;
					case 'rename':

						break;
					case 'rmdir':

						break;
					case 'stat':

						break;
					case 'truncate':

						break;
					case 'unlink':

						break;
					case 'write':

						break;
					default:
						http_response_code(404);
						return false;
				}
			} else {
				http_response_code(404);
				return false;
			}
			break;

		case 'application':
			if (count($api_request) >= 2) {
				switch ($api_request[1]) {
					case 'settings':

						break;
					case 'info':

						break;
					default:
						http_response_code(404);
						return false;
				}
			} else {
				http_response_code(404);
				return false;
			}
			break;

		case 'administration':
			if (!auth_current_user_administrator()) {
				http_response_code(403);
				return false;
			}
			if (count($api_request) >= 2) {
				switch ($api_request[1]) {
					case 'search_index_rebuild':

						break;
					case 'search_index_clear':
						database_lock();
						database_query('DELETE FROM "search_index_keywords";');
						database_query('DELETE FROM "search_index_entries";');
						database_unlock();
						break;
					case 'database_analyze':
						database_query('ANALYZE;');
						break;
					case 'database_vacuum':
						database_query('VACUUM;');
						break;
					default:
						http_response_code(404);
						return false;
				}
			} else {
				http_response_code(404);
				return false;
			}
			break;
		
		case 'search':

			break;

		default:
			http_response_code(404);
			return false;
	}
	return true;
}
