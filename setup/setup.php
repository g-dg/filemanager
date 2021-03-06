<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	die();
}

$settings = [
	'session.gc.probability' => 0.001,
	'session.max_age' => 31536000,
	'database.analyze.probability' => 0.001,
	'database.vacuum.probability' => 0.001,
	'application.base_uri' => null,
	'application.index_page' => null,
	'search.index.word_separators' => ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~',
	'search.index.autoindex.max_time' => 100,
	'search.limit.max_running_searches' => 2,
	'search.index.min_age' => 0,
	'search.index.max_age' => 86400,
	'auth.delay.base' => 500,
	'auth.delay.on_previous_failures' => 1000,
	'log.min_level' => 5,
	'browser.sort.field' => 'name',
	'browser.sort.order' => 'asc',
	'browser.sort.directories_first' => false,
	'browser.group.field' => false,
	'browser.view.mode' => 'list',
	'browser.view.hidden' => false,
	'browser.sidebar.mode' => 'directory_tree',
	'search.hidden.include' => false
];

try {
	$setup_db = new \PDO('sqlite:' . GARNETDG_FILEMANAGER_DATABASE_FILE);

	$setup_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

	$setup_db->setAttribute(\PDO::ATTR_TIMEOUT, 60);

	// use write-ahead logging for performance reasons
	$setup_db->exec('PRAGMA journal_mode=WAL;');
	$setup_db->exec('PRAGMA synchronous=NORMAL;');

	// enable foreign key constraints
	$setup_db->exec('PRAGMA foreign_keys = ON;');

	$setup_db->beginTransaction();
} catch (\Exception $e) {
	throw new Exception('Could not connect to the database', $e->getCode(), $e);
}

try {
	// set up database
	// see doc/database.sql for source
	$setup_db->exec('PRAGMA foreign_keys=ON;PRAGMA user_version=3000000;DROP VIEW IF EXISTS "view_session_user";DROP VIEW IF EXISTS "view_settings";DROP VIEW IF EXISTS "view_users_groups_mountpoints_enabled";DROP VIEW IF EXISTS "view_users_groups_mountpoints";DROP TABLE IF EXISTS "search_index_keywords";DROP TABLE IF EXISTS "search_index_entries";DROP TABLE IF EXISTS "content_types_to_extensions";DROP TABLE IF EXISTS "extensions_to_content_types";DROP TABLE IF EXISTS "history";DROP TABLE IF EXISTS "bookmarks";DROP TABLE IF EXISTS "mountpoints_in_groups";DROP TABLE IF EXISTS "mountpoints";DROP TABLE IF EXISTS "users_in_groups";DROP TABLE IF EXISTS "groups";DROP TABLE IF EXISTS "login_persistence";DROP TABLE IF EXISTS "session_data";DROP TABLE IF EXISTS "sessions";DROP TABLE IF EXISTS "logins";DROP TABLE IF EXISTS "settings";DROP TABLE IF EXISTS "setting_defs";DROP TABLE IF EXISTS "log";DROP TABLE IF EXISTS "users";CREATE TABLE "users"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL UNIQUE,"full_name" TEXT NOT NULL,"password" TEXT NOT NULL,"administrator" INTEGER NOT NULL DEFAULT 0,"read_only" INTEGER NOT NULL DEFAULT 0,"enabled" INTEGER NOT NULL DEFAULT 1,"description" TEXT,"password_changed" INTEGER NOT NULL DEFAULT (STRFTIME(\'%s\',\'now\')));CREATE TRIGGER "trigger_users_update_password_changed" AFTER UPDATE OF "password" ON "users" FOR EACH ROW BEGIN UPDATE "users" SET "password_changed"=STRFTIME(\'%s\',\'now\') WHERE rowid=NEW.rowid; END;CREATE TABLE "log"("id" INTEGER PRIMARY KEY,"level" INTEGER,"type" TEXT,"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME(\'%s\',\'now\')),"user" INTEGER REFERENCES "users" ON UPDATE CASCADE ON DELETE SET NULL,"message" TEXT NOT NULL,"details" TEXT,"client_addr" TEXT,"method" TEXT,"path" TEXT,"host" TEXT,"referrer" TEXT,"user_agent" TEXT);CREATE TABLE "setting_defs"("key" TEXT PRIMARY KEY,"default" TEXT,"system_value" TEXT);CREATE TABLE "settings"("user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,"key" TEXT NOT NULL REFERENCES "setting_defs" ON UPDATE CASCADE ON DELETE CASCADE,"user_value" TEXT,PRIMARY KEY("user","key") ON CONFLICT REPLACE);CREATE TABLE "logins"("id" INTEGER PRIMARY KEY,"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,"successful" INTEGER NOT NULL DEFAULT 1,"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME(\'%s\',\'now\')),"client_addr" TEXT,"user_agent" TEXT);CREATE TABLE "sessions"("id" TEXT PRIMARY KEY ON CONFLICT REPLACE,"login" INTEGER REFERENCES "logins" ON UPDATE CASCADE ON DELETE CASCADE,"last_used" INTEGER NOT NULL DEFAULT (STRFTIME(\'%s\',\'now\')));CREATE TABLE "session_data"("session" TEXT NOT NULL REFERENCES "sessions" ON UPDATE CASCADE ON DELETE CASCADE,"key" TEXT NOT NULL,"value" TEXT,PRIMARY KEY("session","key") ON CONFLICT REPLACE);CREATE TABLE "login_persistence"("key" TEXT PRIMARY KEY,"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,"secret" TEXT NOT NULL,"expires" INTEGER NOT NULL DEFAULT (STRFTIME(\'%s\',\'now\') + 31536000));CREATE TABLE "groups"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL UNIQUE,"enabled" INTEGER NOT NULL DEFAULT 1,"description" TEXT);CREATE TABLE "users_in_groups"("user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,"group" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE,PRIMARY KEY("user","group") ON CONFLICT REPLACE);CREATE TABLE "mountpoints"("id" INTEGER PRIMARY KEY,"name" TEXT NOT NULL UNIQUE,"mountpoint" TEXT NOT NULL UNIQUE,"target" TEXT NOT NULL,"writable" INTEGER NOT NULL DEFAULT 0,"enabled" INTEGER NOT NULL DEFAULT 1,"description" TEXT);CREATE TABLE "mountpoints_in_groups"("mountpoint" INTEGER NOT NULL REFERENCES "mountpoints" ON UPDATE CASCADE ON DELETE CASCADE,"group" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE,"writable" INTEGER NOT NULL DEFAULT 0,PRIMARY KEY("mountpoint","group") ON CONFLICT REPLACE);CREATE TABLE "bookmarks"("id" INTEGER PRIMARY KEY,"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,"name" TEXT NOT NULL,"path" TEXT NOT NULL,UNIQUE("user","name") ON CONFLICT REPLACE);CREATE TABLE "history"("id" INTEGER PRIMARY KEY,"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,"path" TEXT NOT NULL,"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME(\'%s\',\'now\')));CREATE TABLE "extensions_to_content_types"("extension" TEXT PRIMARY KEY ON CONFLICT REPLACE NOT NULL,"type" TEXT NOT NULL,"subtype" TEXT NOT NULL);CREATE TABLE "content_types_to_extensions"("content_type" TEXT PRIMARY KEY ON CONFLICT REPLACE NOT NULL,"extension" TEXT NOT NULL);CREATE TABLE "search_index_entries"("id" INTEGER PRIMARY KEY,"parent" INTEGER REFERENCES "search_index_entries" ON UPDATE CASCADE ON DELETE CASCADE,"name" TEXT NOT NULL,"type" INTEGER NOT NULL,"mtime" INTEGER NOT NULL,"size" INTEGER NOT NULL,"last_indexed" INTEGER NOT NULL DEFAULT (STRFTIME(\'%s\',\'now\')),UNIQUE("parent","name") ON CONFLICT REPLACE);CREATE TABLE "search_index_keywords"("entry" INTEGER NOT NULL REFERENCES "search_index_entries" ON UPDATE CASCADE ON DELETE CASCADE,"keyword" TEXT NOT NULL);CREATE INDEX "index_search_index_keywords_keyword" ON "search_index_keywords"("keyword");CREATE VIEW "view_users_groups_mountpoints" AS SELECT "users"."id" AS "user_id","users"."name" AS "user_name","users"."full_name" AS "user_full_name","users"."password" AS "user_password","users"."administrator" AS "user_administrator","users"."read_only" AS "user_read_only","users"."enabled" AS "user_enabled","users"."description" AS "user_description","groups"."id" AS "group_id","groups"."name" AS "group_name","groups"."enabled" AS "group_enabled","groups"."description" AS "group_description","mountpoints"."id" AS "mountpoint_id","mountpoints"."name" AS "mountpoint_name","mountpoints"."mountpoint" AS "mountpoint_mountpoint","mountpoints"."target" AS "mountpoint_target","mountpoints"."writable" AND "mountpoints_in_groups"."writable" AS "mountpoint_writable","mountpoints"."enabled" AS "mountpoint_enabled","mountpoints"."description" AS "mountpoint_description" FROM users INNER JOIN "users_in_groups" ON "users"."id"="users_in_groups"."user" INNER JOIN "groups" ON "groups"."id"="users_in_groups"."group" INNER JOIN "mountpoints_in_groups" ON "mountpoints_in_groups"."group"="groups"."id" INNER JOIN "mountpoints" ON "mountpoints"."id"="mountpoints_in_groups"."mountpoint";CREATE VIEW "view_users_groups_mountpoints_enabled" AS SELECT "user_id","user_name","user_full_name","user_password","user_administrator","user_read_only","user_description","group_id","group_name","group_description","mountpoint_id","mountpoint_name","mountpoint_mountpoint","mountpoint_target","mountpoint_writable","mountpoint_description" FROM "view_users_groups_mountpoints" WHERE "user_enabled" AND "group_enabled" AND "mountpoint_enabled";CREATE VIEW "view_settings" AS SELECT "setting_defs"."key" AS "key","users"."id" AS "user","setting_defs"."default" AS "default_value","setting_defs"."system_value" AS "system_value",COALESCE("settings"."user_value","setting_defs"."system_value") AS "user_value" FROM "setting_defs" LEFT JOIN "users" ON 1 LEFT JOIN "settings" ON "settings"."key"="setting_defs"."key" AND "settings"."user"="users"."id"; CREATE VIEW "view_session_user" AS SELECT "sessions"."id" AS "session_id","logins"."user" AS "user_id" FROM "sessions" LEFT JOIN "logins" ON "sessions"."login"="logins"."id";');

	// create the administrator account
	$stmt = $setup_db->prepare('INSERT INTO "users"("name", "full_name", "password", "administrator", "read_only", "enabled") VALUES (?, ?, ?, 1, 0, 1);');
	$stmt->execute([GARNETDG_FILEMANAGER_ADMIN_DEFAULT_USERNAME, GARNETDG_FILEMANAGER_ADMIN_DEFAULT_USERNAME, password_hash(GARNETDG_FILEMANAGER_ADMIN_DEFAULT_PASSWORD, PASSWORD_DEFAULT)]);
	$stmt = null;

	// set up settings
	$stmt = $setup_db->prepare('INSERT INTO "setting_defs" ("key", "default", "system_value") VALUES (?, ?, ?);');
	foreach ($settings as $key => $value) {
		$stmt->execute([$key, json_encode($value), json_encode($value)]);
	}
	$stmt = null;

	$setup_db->commit();
} catch (\Exception $e) {
	$setup_db->rollBack();
	throw new Exception('Could not set up the database', null, $e);
}

$setup_db = null;
