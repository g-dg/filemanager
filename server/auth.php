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
	if (!session_started()) {
		log(LOG_ERR, 'Cannot authenticate (session not started)', 'auth');
		throw SessionNotStartedException();
	}
	if (!is_null(session_get('auth.user.id'))) { // check if not already authenticated
		if (is_null($username) && is_null($password)) {
			// no username/password specified, not able to log in
			//TODO: Implement login persistence
			log(LOG_ERR, 'User is not logged in.', 'auth');
			if ($die_on_failure) die();
			return false;
		} else {
			$user_records = database_query('SELECT "id", "name", "password", "administrator", "read_only", "enabled" FROM "users" WHERE "name" = ?;', [$username]);
			if (isset($user_records[0])) { // check if user exists
				// user exists
				$user_record = $user_record[0];
				if ($user_record['enabled'] == 1) { // check if enabled
					// user is enabled
					if (password_verify($password, $user_record['password'])) { // check password
						// password is valid
						// set session values
						session_lock();
						session_set('auth.user.id', (int)$user_record['id']);
						session_set('auth.user.name', (string)$user_record['name']);
						session_set('auth.user.administrator', ($user_record['administrator'] == 1));
						session_set('auth.user.read_only', ($user_record['read_only'] == 1));
						session_unlock();
						log(LOG_INFO, 'User "' . $user_record['name'] . '" logged in', 'auth');
						return true;
					} else {
						log(LOG_NOTICE, 'Attempted login as user "' . $user_record['name'] . '" with incorrect password', 'auth');
					}
				} else {
					log(LOG_NOTICE, 'Attempted login as disabled user account "' . $user_record['name'] . '"', 'auth');
				}
				// by this point, the login failed, thus log it and exit.
				database_query('INSERT INTO "logins"("user", "successful", "client_addr", "user_agent") VALUES (?, 0, ?, ?);', [$user_record['id'], isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null, isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null]);
				if ($die_on_failure) die();
				return false;
			} else {
				// user doesn't exist
				log(LOG_NOTICE, 'Attempted to login as nonexistent user "' . $username . '"', 'auth');
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
