PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

-- Major * 1,000,000 + Minor * 1,000 + Revision
PRAGMA user_version = 3000000;

DROP TABLE IF EXISTS "search_keyword_index";
DROP TABLE IF EXISTS "search_file_index";
DROP TABLE IF EXISTS "content_types_to_extensions";
DROP TABLE IF EXISTS "extensions_to_content_types";
DROP TABLE IF EXISTS "history";
DROP TABLE IF EXISTS "bookmarks";
DROP TABLE IF EXISTS "mountpoints_in_groups";
DROP TABLE IF EXISTS "mountpoints";
DROP TABLE IF EXISTS "users_in_groups";
DROP TABLE IF EXISTS "groups";
DROP TABLE IF EXISTS "login_persistence";
DROP TABLE IF EXISTS "session_data";
DROP TABLE IF EXISTS "sessions";
DROP TABLE IF EXISTS "logins";
DROP TABLE IF EXISTS "user_settings";
DROP TABLE IF EXISTS "user_setting_defs";
DROP TABLE IF EXISTS "global_settings";
DROP TABLE IF EXISTS "log";
DROP TABLE IF EXISTS "users";

-- Users
CREATE TABLE "users"(
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE,
	"full_name" TEXT NOT NULL,
	"password" TEXT NOT NULL,
	"administrator" INTEGER NOT NULL DEFAULT 0,
	"read_only" INTEGER NOT NULL DEFAULT 0,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	"description" TEXT,
	"created" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"modified" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);
CREATE TRIGGER "trigger_users_update_modified" AFTER UPDATE OF "name", "full_name", "password", "administrator", "read_only", "enabled", "description" ON "users" FOR EACH ROW
BEGIN
	UPDATE "users" SET "modified" = STRFTIME('%s', 'now') WHERE rowid = NEW.rowid;
END;

-- Log
CREATE TABLE "log"(
	"id" INTEGER PRIMARY KEY,
	"level" INTEGER,
	"type" TEXT,
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"user" INTEGER REFERENCES "users" ON UPDATE CASCADE ON DELETE SET NULL,
	"message" TEXT,
	"details" TEXT,
	"client_addr" TEXT,
	"method" TEXT,
	"path" TEXT,
	"referrer" TEXT,
	"user_agent" TEXT
);

-- Global Settings
CREATE TABLE "global_settings"(
	"key" TEXT PRIMARY KEY ON CONFLICT REPLACE NOT NULL,
	"default" TEXT,
	"value" TEXT,
	"modified" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);
CREATE TRIGGER "trigger_global_settings_update_modified" AFTER UPDATE OF "value" ON "global_settings" FOR EACH ROW
BEGIN
	UPDATE "global_settings" SET "modified" = STRFTIME('%s', 'now') WHERE rowid = NEW.rowid;
END;

-- User Settings
CREATE TABLE "user_setting_defs"(
	"key" TEXT PRIMARY KEY,
	"default" TEXT
);
CREATE TABLE "user_settings"(
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"key" TEXT NOT NULL REFERENCES "user_setting_defs" ON UPDATE CASCADE ON DELETE CASCADE,
	"value" TEXT,
	"modified" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	PRIMARY KEY("user", "key") ON CONFLICT REPLACE
);
CREATE TRIGGER "trigger_user_settings_update_modified" AFTER UPDATE OF "value" ON "user_settings" FOR EACH ROW
BEGIN
	UPDATE "user_settings" SET "modified" = STRFTIME('%s', 'now') WHERE rowid = NEW.rowid;
END;

-- Logins
CREATE TABLE "logins"(
	"id" INTEGER PRIMARY KEY,
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"successful" INTEGER NOT NULL DEFAULT 1,
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"client_addr" TEXT,
	"user_agent" TEXT
);

-- Sessions
CREATE TABLE "sessions"(
	"id" TEXT PRIMARY KEY,
	"login" INTEGER REFERENCES "logins" ON UPDATE CASCADE ON DELETE CASCADE,
	"last_used" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);
CREATE TABLE "session_data"(
	"session" TEXT NOT NULL REFERENCES "sessions" ON UPDATE CASCADE ON DELETE CASCADE,
	"key" TEXT NOT NULL,
	"value" TEXT,
	PRIMARY KEY("session", "key") ON CONFLICT REPLACE
);
CREATE TRIGGER "trigger_session_data_insert_last_used" AFTER INSERT ON "session_data" FOR EACH ROW
BEGIN
	UPDATE "sessions" SET "last_used" = STRFTIME('%s', 'now') WHERE "id" = NEW."session" AND "last_used" < STRFTIME('%s', 'now');
END;
CREATE TRIGGER "trigger_session_data_update_last_used" AFTER UPDATE ON "session_data" FOR EACH ROW
BEGIN
	UPDATE "sessions" SET "last_used" = STRFTIME('%s', 'now') WHERE "id" = NEW."session" AND "last_used" < STRFTIME('%s', 'now');
END;
CREATE TRIGGER "trigger_session_data_delete_last_used" BEFORE DELETE ON "session_data" FOR EACH ROW
BEGIN
	UPDATE "sessions" SET "last_used" = STRFTIME('%s', 'now') WHERE "id" = OLD."session" AND "last_used" < STRFTIME('%s', 'now');
END;

-- Login Persistence
CREATE TABLE "login_persistence"(
	"key" TEXT PRIMARY KEY,
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"secret" TEXT NOT NULL,
	"created" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"expires" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now') + 31536000)
);

-- Groups
CREATE TABLE "groups"(
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	"description" TEXT,
	"created" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"modified" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);
CREATE TRIGGER "trigger_groups_update_modified" AFTER UPDATE OF "name", "enabled", "description" ON "groups" FOR EACH ROW
BEGIN
	UPDATE "groups" SET "modified" = STRFTIME('%s', 'now') WHERE rowid = NEW.rowid;
END;
CREATE TABLE "users_in_groups"(
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"group" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE,
	PRIMARY KEY("user", "group") ON CONFLICT REPLACE
);

-- Mountpoints
CREATE TABLE "mountpoints"(
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE,
	"mountpoint" TEXT NOT NULL UNIQUE,
	"target" TEXT NOT NULL,
	"writable" INTEGER NOT NULL DEFAULT 0,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	"description" TEXT,
	"created" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"modified" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);
CREATE TRIGGER "trigger_mountpoints_update_modified" AFTER UPDATE OF "name", "mountpoint", "target", "writable", "enabled", "description" ON "mountpoints" FOR EACH ROW
BEGIN
	UPDATE "mountpoints" SET "modified" = STRFTIME('%s', 'now') WHERE rowid = NEW.rowid;
END;
CREATE TABLE "mountpoints_in_groups"(
	"mountpoint" INTEGER NOT NULL REFERENCES "mountpoints" ON UPDATE CASCADE ON DELETE CASCADE,
	"group" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE,
	"writable" INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("mountpoint", "group")
);

-- Bookmarks
CREATE TABLE "bookmarks"(
	"id" INTEGER PRIMARY KEY,
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT NOT NULL UNIQUE ON CONFLICT REPLACE,
	"path" TEXT NOT NULL,
	"sort_order" INTEGER NOT NULL,
	"created" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"modified" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);
CREATE TRIGGER "trigger_bookmarks_update_modified" AFTER UPDATE OF "name", "path" ON "bookmarks" FOR EACH ROW
BEGIN
	UPDATE "bookmarks" SET "modified" = STRFTIME('%s', 'now') WHERE rowid = NEW.rowid;
END;

-- Access history
CREATE TABLE "history"(
	"id" INTEGER PRIMARY KEY,
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"path" TEXT NOT NULL,
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);

-- Extensions to content-types
CREATE TABLE "extensions_to_content_types"(
	"extension" TEXT PRIMARY KEY ON CONFLICT REPLACE NOT NULL,
	"type" TEXT NOT NULL,
	"subtype" TEXT NOT NULL
);

--  Content-types to extensions
CREATE TABLE "content_types_to_extensions"(
	"content_type" TEXT PRIMARY KEY ON CONFLICT REPLACE NOT NULL,
	"extension" TEXT NOT NULL
);

-- Search Index
CREATE TABLE "search_file_index"(
	"id" INTEGER PRIMARY KEY,
	"directory" TEXT NOT NULL,
	"filename" TEXT NOT NULL,
	"modified" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"type" INTEGER NOT NULL DEFAULT 1,
	"size" INTEGER NOT NULL DEFAULT 0,
	"last_updated" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);
CREATE TABLE "search_keyword_index"(
	"file" INTEGER NOT NULL REFERENCES "search_file_index" ON UPDATE CASCADE ON DELETE CASCADE,
	"keyword" TEXT NOT NULL
);
CREATE INDEX "index_search_keyword_index_keyword" ON "search_keyword_index"("keyword");


COMMIT TRANSACTION;
