<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	die();
}

class DatabaseException extends Exception {}

/**
 * The minimum and maximum compatable versions
 */
const DATABASE_VERSION_MIN = 3000000;
const DATABASE_VERSION_MAX = 3999999;

/**
 * The database connection
 */
$database_connection = null;

/**
 * Used for nesting database locks
 */
$database_lock_level = 0;

/**
 * The number of rows that were affected in the last query
 */
$database_affected_row_count = 0;

/**
 * Connect to the database
 * @throws DatabaseException on failure
 */
function database_connect()
{
	global $database_connection;

	if (is_null($database_connection)) {
		// check if file exists and is readable
		if (!is_file(GARNETDG_FILEMANAGER_DATABASE_FILE) ||
				!is_readable(GARNETDG_FILEMANAGER_DATABASE_FILE) ||
				!is_writable(GARNETDG_FILEMANAGER_DATABASE_FILE) ||
				!is_readable(dirname(GARNETDG_FILEMANAGER_DATABASE_FILE)) ||
				!is_writable(dirname(GARNETDG_FILEMANAGER_DATABASE_FILE))) {
			throw new DatabaseException('The database is not set up or is inaccessible (must be readable and writable and in a readable and writable directory)');
		}

		$database_connection = new \PDO('sqlite:' . GARNETDG_FILEMANAGER_DATABASE_FILE);

		$database_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$database_connection->setAttribute(\PDO::ATTR_TIMEOUT, 60);

		$db_version = (int)database_query('PRAGMA user_version;')[0][0];

		if ($db_version === 0) {
			// run setup
			require('setup/setup.php');
			$db_version = (int)database_query('PRAGMA user_version;')[0][0];
		}

		// check the database version
		//TODO: use an upgrader if incompatable
		if ($db_version < DATABASE_VERSION_MIN || $db_version > DATABASE_VERSION_MAX) {
			throw new DatabaseException('Incompatable database version (' . $db_version . ')');
		}

		// use write-ahead logging for performance reasons
		database_query('PRAGMA journal_mode=WAL;');
		database_query('PRAGMA synchronous=NORMAL;');

		// enable foreign key constraints
		database_query('PRAGMA foreign_keys = ON;');
	}
}

/**
 * Lock the database
 */
function database_lock()
{
	global $database_lock_level, $database_connection;

	database_connect();
	if ($database_lock_level++ == 0) {
		try {
			$database_connection->beginTransaction();
		} catch (\PDOException $e) {
			throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
		}
	}
}

/**
 * Unlock the database
 */
function database_unlock()
{
	global $database_lock_level, $database_connection;

	database_connect();
	if ($database_lock_level-- == 1) {
		try {
			$database_connection->commit();
		} catch (\PDOException $e) {
			throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
		}
	}
}

/**
 * Get whether the database is locked
 * @return bool whether the database is locked
 */
function database_locked()
{
	global $database_connection;

	return ($database_connection->inTransaction());
}

/**
 * Execute a query on the database
 * @param sql The SQL statement
 * @param params The parameters for the sql statement
 * @return array the result set
 * @throws DatabaseException on failure
 */
function database_query($sql, $params = [])
{
	global $database_connection, $database_affected_row_count;

	database_connect();

	$done_retrying = false;
	$start_time = time();
	while (!$done_retrying) {
		try {
			$stmt = $database_connection->prepare($sql);
			$stmt->execute($params);
			$database_affected_row_count = $stmt->rowCount();
			$done_retrying = true;
		} catch (\PDOException $e) {
			// keep retrying if locked
			if (substr_count($e->getMessage(), 'database is locked') == 0) {
				throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
			} else {
				if (time() - $start_time > 60) {
					throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
				}
				usleep(mt_rand(1000, 10000));
			}
		}
	}
	return $stmt->fetchAll();
}

/**
 * Get the number of affected rows for the last query
 * @return int The number of affected rows
 */
function database_get_affected_rows()
{
	global $database_affected_row_count;

	return $database_affected_row_count;
}
