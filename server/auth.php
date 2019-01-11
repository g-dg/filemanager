<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class NotAuthenticatedException extends Exception {}

/**
 * Check if the user is authenticated
 * @return bool Whether the authentication was successful or not
 */
function authenticate($username = null, $password = null, $die_on_failure = false) {
	session_start();
	if (!is_null(session_get('auth.user.id'))) { // check if not already authenticated
		if (is_null($username) && is_null($password)) {
			// no username/password specified, not able to log in
			if ($die_on_failure) die();
			return false;
		} else {
			$user_records = database_query('SELECT "id", "name", "password", "administrator", "read_only" FROM "users" WHERE "name" = ? AND "enabled";', [$username]);
			if (isset($user_records[0])) { // check if user exists or is enabled
				// user exists and is enabled
				$user_record = $user_record[0];
				if (password_verify($password, $user_record['password'])) { // check password
					// password is valid
					// set session values
					session_lock();
					session_set('auth.user.id', (int)$user_record['id']);
					session_set('auth.user.name', (string)$user_record['name']);
					session_set('auth.user.administrator', ($user_record['administrator'] == 1));
					session_set('auth.user.read_only', ($user_record['read_only'] == 1));
					session_unlock();
					return true;
				} else {
					if ($die_on_failure) die();
					return false;
				}
			} else {
				// user doesn't exist or is disabled
				if ($die_on_failure) die();
				return false;
			}
		}
	} else {
		// already logged in
		return true;
	}
}

/**
 * Get the current user ID, null if not logged in
 * @return int The current user ID
 */
function auth_current_user_id()
{
	return session_get('auth.user.id');
}

/**
 * Get the current user name, null if not logged in
 * @return string The current user name
 */
function auth_current_user_name()
{
	return session_get('auth.user.name');
}

/**
 * Get whether the current user is an administrator
 * @return bool The current user administrator status
 */
function auth_current_user_administrator()
{
	return session_get('auth.user.administrator');
}

/**
 * Get whether the current user is read-only
 * @return bool The current user read-only status
 */
function auth_current_user_readonly()
{
	return session_get('auth.user.read_only');
}
