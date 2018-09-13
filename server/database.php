<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	die();
}

class Database
{
	const VERSION_MIN = 3000000;
	const VERSION_MAX = 3999999;

	public static $connection = null;

	protected static $lock_level = 0;

	public static function connect()
	{
		if (is_null(self::$connection)) {
			$db_file = GARNETDG_FILEMANAGER_DATABASE_FILE;
			if (!is_file($db_file) ||
					!is_readable($db_file) ||
					!is_writable($db_file)) {
				throw new \Exception('The database is not set up or is inaccessible');
			}

			$dsn = 'sqlite:' . $db_file;

			self::$connection = new \PDO($dsn);

			self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			self::$connection->setAttribute(\PDO::ATTR_TIMEOUT, 60);

			$db_version = self::query('PRAGMA user_version;')[0][0];

			if ($db_version < self::VERSION_MIN || $db_version > self::VERSION_MAX) {
				throw new \Exception('Incompatable database version');
			}

			self::query('PRAGMA journal_mode=WAL;');
			self::query('PRAGMA synchronous=NORMAL;');

			self::query('PRAGMA foreign_keys = ON;');
		}
	}

	public static function lock()
	{
		self::connect();
		if (self::$lock_level++ == 0) {
			self::$connection->beginTransaction();
		}
	}
	public static function unlock()
	{
		self::connect();
		if (self::$lock_level-- == 1) {
			self::$connection->commit();
		}
	}
	public static function isLocked()
	{
		return (self::$lock_level <= 0);
	}

	public static function query($sql, $params = [])
	{
		self::connect();

		$done_retrying = false;
		$start_time = time();
		while (!$done_retrying) {
			try {
				$stmt = self::$connection->prepare($sql);
				$stmt->execute($params);
				$done_retrying = true;
			} catch (\PDOException $e) {
				// keep retrying if locked
				if (substr_count($e->getMessage(), 'database is locked') == 0) {
					throw new \Exception($e->getMessage(), $e->getCode(), $e);
				} else {
					if (time() - $start_time > 60) {
						throw new \Exception($e->getMessage(), $e->getCode(), $e);
					}
					usleep(mt_rand(1000, 10000));
				}
			}
		}
		return $stmt->fetchAll();
	}
}
