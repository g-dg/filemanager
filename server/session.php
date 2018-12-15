<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class SessionException extends Exception {}
class SessionNotStartedException extends SessionException {}

/**
 * The session id for the current request, null if not set
 */
$session_id = null;

/**
 * Starts a new session or resumes any available one
 * @param sessid The session ID (pass this if not to be read from the cookie)
 * @param set_cookie Set this to false to not set the session cookie
 * @param regenerate_on_failure Generates a new session on validation failure
 * @return mixed false if failed, session id if successful
 */
function session_start($sessid = null, $set_cookie = true, $regenerate_on_failure = false)
{
	global $session_id;

	// don't run if the session is already started
	if (is_null($session_id)) {
		// check whether to do a garbage-collect
		if (mt_rand(1, settings_get_system('session.gc.divisor')) <= (settings_get_system('session.gc.probability'))) {
			session_gc();
		}

		$cookie_name = settings_get_system('session.cookie.name');

		// get the session ID
		if (!is_null($sessid)) { // the session ID is passed as a parameter of the function
			$session_id = $sessid;
		} else {
			// check if the cookie is set
			if (isset($_COOKIE[$cookie_name])) {
				$session_id = $_COOKIE[$cookie_name];
			}
		}

		database_lock();
		try {
			// check if the session exists
			if (!is_null($session_id)) {
				// check if the session is valid
				if ((int)database_query('SELECT COUNT() FROM "sessions" WHERE "id" = ? AND "last_used" > ?;', [(string)$session_id], time() - settings_get_system('session.age.max'))[0][0] == 0) {
					if ($generate_new_on_failure) {
						$session_id = generate_random_string(settings_get_system('session.id.length'), settings_get_system('session.id.chars'));
						database_query('INSERT INTO "sessions"("id") VALUES (?);', [$session_id]);

						// generate the CSRF token
						session_set('session.csrf.token', generate_random_string(settings_get_system('session.csrf_token.length'), settings_get_system('session.csrf_token.chars')));
					} else {
						return false;
					}
				} else {
					// update timestamp
					database_query('UPDATE "sessions" SET "last_used" = STRFTIME(\'%s\', \'now\') WHERE "id" = ? AND "last_used" < STRFTIME(\'%s\', \'now\');', [$session_id]);
				}
			} else {
				// generate a new session
				$session_id = generate_random_string(settings_get_system('session.id.length'), settings_get_system('session.id.chars'));
				database_query('INSERT INTO "sessions"("id") VALUES (?);', [$session_id]);

				// generate the CSRF token
				session_set('session.csrf.token', generate_random_string(settings_get_system('session.csrf_token.length'), settings_get_system('session.csrf_token.chars')));
			}
		} finally {
			database_unlock();
		}
		
		// set the cookie
		if ($set_cookie) {
			setcookie($cookie_name, $session_id, 0, dirname($_SERVER['SCRIPT_NAME']), "", false, true);
		}
	}

	return is_null($session_id) ? false : $session_id;
}

/**
 * Creates a new session
 * @param destroy_previous destroys the previous session (session must have already been started)
 * @param set_cookie sets the cookie with the new session id
 */
function session_new($destroy_previous = false, $set_cookie = true)
{
	global $session_id;

	if ($destroy_previous) {
		session_destroy();
	}

	// generate a new session
	$session_id = generate_random_string(settings_get_system('session.id.length'), settings_get_system('session.id.chars'));
	database_query('INSERT INTO "sessions"("id") VALUES (?);', [$session_id]);

	// generate a new CSRF token
	session_set('session.csrf.token', generate_random_string(settings_get_system('session.csrf_token.length'), settings_get_system('session.csrf_token.chars')));

	// set the cookie
	if ($set_cookie) {
		setcookie($cookie_name, $session_id, 0, "", "", false, true);
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
 * @return mixed session id as a string if started, null if not
 */
function session_get_id()
{
	global $session_id;

	return $session_id;
}

/**
 * Sets the specified key
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
	} catch(DatabaseException $e) {
		log(LOG_ERR, 'Could not set value of "' . $key . '"', 'session');
		throw new SessionException('Could not set value of "' . $key . '" (database error).', $e->getCode(), $e);
	}
}

/**
 * Gets the value of the specified key
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
	database_query('DELETE FROM "sessions" WHERE "last_used" < ?;', [time() - settings_get_system('session.gc.age.max')]);
}

/**
 * Unsets the session cookie and destroys all data associated with it
 */
function session_destroy()
{
	global $session_id;
	
	if (!session_started()) {
		//log(LOG_ERR, 'Could not destroy session (session not started).', 'session');
		//throw new SessionNotStartedException('Could not destroy session (session not started).');
		return;
	}
	database_query('DELETE FROM "sessions" WHERE "id" = ?;', [$session_id]);
	$session_id = null;
}

/**
 * Generates a random string
 * Note: on PHP <= 5.6 and in some other cases, this is not cryptographically secure
 * @param length The length of the string
 * @param chars A string of characters to use
 */
function generate_random_string($length, $chars) {
	if (function_exists('random_int')) {
		try {
			$string = '';
			for ($i = 0; $i < $length; $i++) {
				$string .= substr($chars, random_int(0, strlen($chars) - 1), 1);
			}
			return $string;
		} catch (\Exception $e) {}
	}
	$string = '';
	for ($i = 0; $i < $length; $i++) {
		$string .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	}
	return $string;
}

/**
 * Lock the session
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
 */
function session_unlock()
{
	if (!session_started()) {
		log(LOG_ERR, 'Could not unlock session (session not started).', 'session');
		throw new SessionNotStartedException('Could not unlock session (session not started).');
	}
	database_unlock();
}
