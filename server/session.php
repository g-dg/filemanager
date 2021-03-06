<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class SessionException extends Exception {}
class SessionNotStartedException extends SessionException {}
class SessionInvalidException extends SessionException {}

define('GARNETDG_FILEMANAGER_SESSION_ID_LENGTH', 255);
define('GARNETDG_FILEMANAGER_SESSION_ID_CHARACTERS', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
define('GARNETDG_FILEMANAGER_SESSION_GC_PROBABILITY', 0.001);

/**
 * The session id for the current request, null if not set
 */
$session_id = null;

/**
 * Starts a new session or resumes any available one
 * @param sessid The session ID
 */
function session_start($sessid)
{
	global $session_id;

	// don't run if the session is already started
	if (is_null($session_id)) {
		// check whether to do a garbage-collect
		if ((mt_rand() / mt_getrandmax()) < GARNETDG_FILEMANAGER_SESSION_GC_PROBABILITY) {
			session_gc();
		}

		$session_id = $sessid;

		database_lock();
		try {
			// check if the session is valid
			if ((int)database_query('SELECT COUNT() FROM "sessions" WHERE "id" = ? AND "last_used" > ?;', [(string)$session_id], time() - settings_get_system('session.max_age'))[0][0] == 0) {
				throw new SessionInvalidException();
			} else {
				// update timestamp
				database_query('UPDATE "sessions" SET "last_used" = STRFTIME(\'%s\', \'now\') WHERE "id" = ? AND "last_used" < STRFTIME(\'%s\', \'now\');', [$session_id]);
			}
		}
		finally {
			database_unlock();
		}
	}
}

/**
 * Creates a new session
 * @return string new session ID
 */
function session_new()
{
	global $session_id;

	// generate a new session
	$session_id = generate_random_string(GARNETDG_FILEMANAGER_SESSION_ID_LENGTH, GARNETDG_FILEMANAGER_SESSION_ID_CHARACTERS);
	database_query('INSERT INTO "sessions"("id") VALUES (?);', [$session_id]);
	return $session_id;
}

/**
 * Destroys a session and all data associated with it
 * Requires started session
 */
function session_destroy()
{
	global $session_id;

	if (!session_started()) {
		log(LOG_ERR, 'Could not destroy session (session not started).', 'session');
		throw new SessionNotStartedException('Could not destroy session (session not started).');
	} else {
		database_query('DELETE FROM "sessions" WHERE "id" = ?;', [$session_id]);
		$session_id = null;
	}
}

/**
 * Destroys all sessions associated with a user, effectively logging them out of all of them
 * WARNING: There are no checks to ensure the user is the current user
 * Requires started session if keep_current is true (destroys all sessions if not started)
 * @param user_id User to delete all sessions of (null deletes all sessions for everyone)
 * @param keep_current Don't destroy the current session (if true and session is not started, it will delete all sessions)
 */
function session_destroy_all($user_id, $keep_current = true) {
	global $session_id;

	if (!is_null($user_id)) {
		if ($keep_current && session_started()) {
			database_query('DELETE FROM "sessions" WHERE "user" = ? AND "id" != ?;', [(int)$user_id, (int)$session_id]);
		} else {
			database_query('DELETE FROM "sessions" WHERE "user" = ?;', [(int)$user_id]);
		}
	} else {
		if ($keep_current && session_started()) {
			database_query('DELETE FROM "sessions" WHERE "id" != ?;', [(int)$session_id]);
		} else {
			database_query('DELETE FROM "sessions";');
		}
	}
}

/**
 * Checks whether the session is started
 * @return bool Whether the session is started or not
 */
function session_started()
{
	global $session_id;

	return (!is_null($session_id));
}

/**
 * Get the session ID for the current session
 * @return mixed session id as a string if started, null if not started
 */
function session_id()
{
	global $session_id;

	return $session_id;
}

/**
 * Sets the specified key
 * Requires started session
 * @param key the key to set
 * @param value the value (may be any PHP serializable type)
 */
function session_set($key, $value)
{
	global $session_id;

	if (!session_started()) {
		log(LOG_ERR, 'Could not set value of "' . $key . '" (session not started).', 'session');
		throw new SessionNotStartedException('Can not set value of "' . $key . '" (session not started).');
	}
	try {
		database_query('INSERT INTO "session_data"("session", "key", "value") VALUES (:session, :key, :value);', [':session' => $session_id, ':key' => $key, ':value' => serialize($value)]);
	} catch (DatabaseException $e) {
		log(LOG_ERR, 'Could not set value of "' . $key . '"', 'session');
		throw new SessionException('Could not set value of "' . $key . '" (database error).', $e->getCode(), $e);
	}
}

/**
 * Gets the value of the specified key
 * Requires started session
 * @param key the key to get
 * @param value the default value if the key is not set
 * @return mixed the value
 */
function session_get($key, $default = null)
{
	global $session_id;

	if (!session_started()) {
		log(LOG_ERR, 'Could not get value of "' . $key . '" (session not started).', 'session');
		throw new SessionNotStartedException('Can not get value of "' . $key . '" (session not started).');
	}
	try {
		$result = database_query('SELECT "value" FROM "session_data" WHERE "session" = :session AND "key" = :key;', [':session' => $session_id, ':key' => $key]);
		if (isset($result[0])) {
			return unserialize($result[0][0]);
		} else {
			log(LOG_WARNING, 'Attempted to get value of "' . $key . '" when not set', 'session');
			return null;
		}
	} catch (DatabaseException $e) {
		log(LOG_ERR, 'Could not get value of "' . $key . '" (database error)', 'session');
		throw new SessionException('Could not get value of "' . $key . '" (database error).', $e->getCode(), $e);
	}
}

/**
 * Gets whether the specified key is set
 * Requires started session
 * @param key the key to check
 * @return bool whether the key exists
 */
function session_isset($key)
{
	global $session_id;

	if (!session_started()) {
		log(LOG_ERR, 'Could not check if "' . $key . '" is set (session not started).', 'session');
		throw new SessionNotStartedException('Could not check if "' . $key . '" is set (session not started).');
	}
	try {
		return database_query('SELECT COUNT() FROM "session_data" WHERE "session" = :session AND "key" = :key;', [':session' => $session_id, ':key' => $key])[0][0] > 0;
	} catch (DatabaseException $e) {
		log(LOG_ERR, 'Could not check if "' . $key . '" is set (database error)', 'session');
		throw new SessionException('Could not check if "' . $key . '" is set (database error).', $e->getCode(), $e);
	}
}

/**
 * Deletes the specified key
 * Requires started session
 * @param key the key to delete
 */
function session_unset($key)
{
	global $session_id;

	if (!session_started()) {
		log(LOG_ERR, 'Could not unset "' . $key . '" (session not started).', 'session');
		throw new SessionNotStartedException('Could not unset "' . $key . '" (session not started).');
	}
	try {
		database_query('DELETE FROM "session_data" WHERE "session" = :session AND "key" = :key;', [':session' => $session_id, ':key' => $key]);
		return true;
	} catch (DatabaseException $e) {
		log(LOG_ERR, 'Could not unset "' . $key . '" (database error)', 'session');
		throw new SessionException('Could not unset "' . $key . '" (database error).', $e->getCode(), $e);
	}
}

/**
 * Remove expired sessions
 */
function session_gc()
{
	database_query('DELETE FROM "sessions" WHERE "last_used" < ?;', [time() - settings_get_system('session.max_age')]);
}

/**
 * Generates a random string
 * Note: on PHP <= 5.6 and in some other cases, this is not cryptographically secure
 * @param length The length of the string
 * @param chars A string of characters to use
 */
function generate_random_string($length, $chars)
{
	if (function_exists('random_int')) {
		try {
			$string = '';
			for ($i = 0; $i < $length; $i++) {
				$string .= substr($chars, random_int(0, strlen($chars) - 1), 1);
			}
			return $string;
		} catch (\Exception $e) {
		}
	}
	$string = '';
	for ($i = 0; $i < $length; $i++) {
		$string .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	}
	return $string;
}

/**
 * Lock the session
 * Requires started session
 */
function session_lock()
{
	if (!session_started()) {
		log(LOG_ERR, 'Could not lock session (session not started).', 'session');
		throw new SessionNotStartedException('Could not lock session (session not started).');
	}
	database_lock();
}

/**
 * Unlock the session
 * Requires started session
 */
function session_unlock()
{
	if (!session_started()) {
		log(LOG_ERR, 'Could not unlock session (session not started).', 'session');
		throw new SessionNotStartedException('Could not unlock session (session not started).');
	}
	database_unlock();
}
