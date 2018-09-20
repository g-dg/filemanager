PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

-- Major * 1,000,000 + Minor * 1,000 + Revision
PRAGMA user_version = 3000000;

DROP TABLE IF EXISTS "access_codes";
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
	"password" BLOB NOT NULL,
	"administrator" INTEGER NOT NULL DEFAULT 0,
	"read_only" INTEGER NOT NULL DEFAULT 0,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	"description" TEXT
);

-- Log
CREATE TABLE "log"(
	"id" INTEGER PRIMARY KEY,
	"level" INTEGER,
	"type" TEXT,
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"user" INTEGER REFERENCES "users" ON UPDATE CASCADE ON DELETE SET NULL,
	"message" TEXT,
	"details" BLOB,
	"client_addr" TEXT,
	"method" TEXT,
	"path" TEXT,
	"referrer" TEXT,
	"user_agent" TEXT
);

-- Settings
CREATE TABLE "global_settings"(
	"key" TEXT PRIMARY KEY ON CONFLICT REPLACE NOT NULL,
	"default" BLOB,
	"value" BLOB
);
CREATE TABLE "user_setting_defs"(
	"key" TEXT PRIMARY KEY,
	"default" BLOB
);
CREATE TABLE "user_settings"(
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"key" TEXT NOT NULL REFERENCES "user_setting_defs" ON UPDATE CASCADE ON DELETE CASCADE,
	"value" BLOB,
	PRIMARY KEY("user", "key") ON CONFLICT REPLACE
);

-- Logins and Sessions
CREATE TABLE "logins"(
	"id" INTEGER PRIMARY KEY,
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"successful" INTEGER NOT NULL DEFAULT 1,
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"client_addr" TEXT,
	"user_agent" TEXT
);
CREATE TABLE "sessions"(
	"id" INTEGER PRIMARY KEY,
	"session_key" TEXT NOT NULL UNIQUE,
	"login" INTEGER REFERENCES "logins" ON UPDATE CASCADE ON DELETE CASCADE,
	"last_used" INTEGER NOT NULL DEFAULT (STRFTIME('%S', 'now'))
);
CREATE TABLE "session_data"(
	"session" INTEGER NOT NULL REFERENCES "sessions" ON UPDATE CASCADE ON DELETE CASCADE,
	"key" TEXT NOT NULL,
	"value" BLOB,
	PRIMARY KEY("session", "key") ON CONFLICT REPLACE
);

-- Login Persistence
CREATE TABLE "login_persistence"(
	"key" TEXT PRIMARY KEY,
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"secret" BLOB NOT NULL,
	"expires" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now') + 31536000)
);

-- Groups
CREATE TABLE "groups"(
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	"description" TEXT
);
CREATE TABLE "users_in_groups"(
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"group" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE,
	PRIMARY KEY("user", "group") ON CONFLICT REPLACE
);

-- Mountpoints
CREATE TABLE "mountpoints"(
	"id" INTEGER PRIMARY KEY,
	"mountpoint" TEXT NOT NULL UNIQUE,
	"target" TEXT NOT NULL,
	"writable" INTEGER NOT NULL DEFAULT 0,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	"description" TEXT
);
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
	"sort_order" INTEGER NOT NULL
);

-- Access history
CREATE TABLE "history"(
	"id" INTEGER PRIMARY KEY,
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"path" TEXT NOT NULL,
	"timestamp" INEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
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

-- Access codes
CREATE TABLE "access_codes"(
	"id" INTEGER PRIMARY KEY,
	"code" TEXT NOT NULL UNIQUE,
	"file" TEXT NOT NULL,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	"expires" INTEGER,
	"accesses" INTEGER DEFAULT 0,
	"description" TEXT
);

COMMIT TRANSACTION;
