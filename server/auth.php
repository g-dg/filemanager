<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

$current_user_id = null;

/**
 * Get the current user ID, null if not logged in
 * @return int The current user ID
 */
function auth_current_user_id() {
	global $current_user_id;
	return $current_user_id;
}
