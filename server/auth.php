<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

/**
 * Get the current user ID, null if not logged in
 * @return int The current user ID
 */
function auth_current_user_id() {
	return session_get('auth.user.id');
}

/**
 * Get the current user name, null if not logged in
 * @return string The current user name
 */
function auth_current_user_name() {
	return session_get('auth.user.name');
}

/**
 * Get whether the current user is an administrator
 * @return bool The current user administrator status
 */
function auth_current_user_administrator() {
	return session_get('auth.user.administrator');
}

/**
 * Get whether the current user is read-only
 * @return bool The current user read-only status
 */
function auth_current_user_readonly() {
	return session_get('auth.user.read_only');
}
