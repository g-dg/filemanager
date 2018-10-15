<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class SessionException extends Exception {}

/**
 * The session id for the current request, null if not set
 */
$session_id = null;

/**
 * Starts a new session or resumes any available one
 * @param session_id The session ID (pass this if not to be read from the cookie)
 * @param set_cookie Set this to false to not set the session cookie
 * @return string the session id
 * @throws SessionException if the session could not be started.
 */
function session_start($session_id = null, $set_cookie = true)
{

}

/**
 * Creates a new session and sets the cookie
 * @param destroy_previous destroys the previous session
 */
function session_new($destroy_previous = false)
{

}

/**
 * Checks whether the session is started
 * @return bool Whether the session is started or not
 */
function session_started()
{
	return (!is_null($session_id));
}

/**
 * Get the session ID for the current session
 * @return mixed session id as a string if started, null if not
 */
function session_get_id()
{
	return $session_id;
}

/**
 * Sets the specified key
 * @param key the key to set
 * @param value the value (may be any PHP serializable type)
 */
function session_set($key, $value)
{

}

/**
 * Gets the value of the specified key
 * @param key the key to get
 * @param value the default value if the key is not set
 * @return mixed the value
 */
function session_get($key, $default = null)
{

}

/**
 * Gets whether the specified key is set
 * @param key the key to check
 * @return bool whether the key exists
 */
function session_isset($key)
{

}

/**
 * Deletes the specified key
 * @param key the key to delete
 */
function session_unset($key)
{

}

/**
 * Remove expired sessions
 * @return int the number of sessions removed
 */
function session_gc()
{

}

/**
 * Unsets the session cookie and destroys all data associated with it
 */
function session_destroy()
{

}

/**
 * Lock the session
 */
function session_lock()
{
	database_lock();
}

/**
 * Unlock the session
 */
function session_unlock()
{
	database_unlock();
}
