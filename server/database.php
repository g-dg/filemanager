<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	die();
}

class DatabaseException extends Exception {}

class Database
{
	/**
	 * The minimum and maximum compatable versions
	 */
	const VERSION_MIN = 3000000;
	const VERSION_MAX = 3999999;

	/**
	 * The database connection
	 */
	protected static $connection = null;

	/**
	 * Used for nesting database locks
	 */
	protected static $lock_level = 0;

	/**
	 * The number of rows that were affected in the last query
	 */
	protected static $row_count = 0;

	/**
	 * Connect to the database
	 * @throws DatabaseException on failure
	 */
	public static function connect()
	{
		if (is_null(self::$connection)) {
			$db_file = GARNETDG_FILEMANAGER_DATABASE_FILE;
			if (!is_file($db_file) ||
					!is_readable($db_file) ||
					!is_writable($db_file)) {
				throw new DatabaseException('The database is not set up or is inaccessible');
			}

			$dsn = 'sqlite:' . $db_file;

			self::$connection = new \PDO($dsn);

			self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			self::$connection->setAttribute(\PDO::ATTR_TIMEOUT, 60);

			$db_version = self::query('PRAGMA user_version;')[0][0];

			// check the database version
			//TODO: use an upgrader if incompatable
			if ($db_version < self::VERSION_MIN || $db_version > self::VERSION_MAX) {
				throw new DatabaseException('Incompatable database version');
			}

			// use write-ahead logging for performance reasons
			self::query('PRAGMA journal_mode=WAL;');
			self::query('PRAGMA synchronous=NORMAL;');

			// enable foreign key constraints
			self::query('PRAGMA foreign_keys = ON;');
		}
	}

	/**
	 * Lock the database
	 */
	public static function lock()
	{
		self::connect();
		if (self::$lock_level++ == 0) {
			try {
				self::$connection->beginTransaction();
			} catch (\PDOException $e) {
				throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
			}
		}
	}

	/**
	 * Unlock the database
	 */
	public static function unlock()
	{
		self::connect();
		if (self::$lock_level-- == 1) {
			try {
				self::$connection->commit();
			} catch (\PDOException $e) {
				throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
			}
		}
	}

	/**
	 * Get whether the database is locked
	 * @return bool whether the database is locked
	 */
	public static function isLocked()
	{
		return (self::$connection->inTransaction());
	}

	/**
	 * Execute a query on the database
	 * @param sql The SQL statement
	 * @param params The parameters for the sql statement
	 * @return array the result set
	 * @throws DatabaseException on failure
	 */
	public static function query($sql, $params = [])
	{
		self::connect();

		$done_retrying = false;
		$start_time = time();
		while (!$done_retrying) {
			try {
				$stmt = self::$connection->prepare($sql);
				$stmt->execute($params);
				self::$row_count = $stmt->rowCount();
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
	public static function getAffectedRows()
	{
		return self::$row_count;
	}
}
