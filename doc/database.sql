PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

-- Major * 1,000,000 + Minor * 1,000 + Revision
PRAGMA user_version = 3000000;

DROP TABLE IF EXISTS "content_types";
DROP TABLE IF EXISTS "bookmarks";
DROP TABLE IF EXISTS "shares_in_groups";
DROP TABLE IF EXISTS "shares";
DROP TABLE IF EXISTS "users_in_groups";
DROP TABLE IF EXISTS "groups";
DROP TABLE IF EXISTS "login_persistence";
DROP TABLE IF EXISTS "session_data";
DROP TABLE IF EXISTS "sessions";
DROP TABLE IF EXISTS "user_settings";
DROP TABLE IF EXISTS "user_setting_defs";
DROP TABLE IF EXISTS "global_settings";
DROP TABLE IF EXISTS "extensions";
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
	"description" TEXT NOT NULL
);

-- Log
CREATE TABLE "log"(
	"id" INTEGER PRIMARY KEY,
	"level" INTEGER,
	"type" TEXT,
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"user_id" INTEGER REFERENCES "users" ON UPDATE CASCADE ON DELETE SET NULL,
	"message" TEXT,
	"details" BLOB,
	"client_addr" TEXT,
	"method" TEXT,
	"path" TEXT,
	"referrer" TEXT,
	"user_agent" TEXT,
);

-- Extensions
CREATE TABLE "extensions"(
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE,
	"load_order" INTEGER NOT NULL UNIQUE,
	"enabled" INTEGER NOT NULL DEFAULT 1
);

-- Settings
CREATE TABLE "global_settings"(
	"key" TEXT NOT NULL,
	"extension_id" INTEGER REFERENCES "extenstions" ON UPDATE CASCADE ON DELETE CASCADE,
	"value" BLOB,
	PRIMARY KEY("key") ON CONFLICT REPLACE
);
CREATE TABLE "user_setting_defs"(
	"key" TEXT PRIMARY KEY,
	"extension_id" INTEGER REFERENCES "extenstions" ON UPDATE CASCADE ON DELETE CASCADE,
	"default" BLOB
);
CREATE TABLE "user_settings"(
	"user_id" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"key" TEXT NOT NULL REFERENCES "user_setting_defs" ON UPDATE CASCADE ON DELETE CASCADE,
	"value" BLOB,
	PRIMARY KEY("user_id", "key") ON CONFLICT REPLACE
);

-- Sessions
CREATE TABLE "sessions"(
	"id" TEXT PRIMARY KEY,
	"user_id" INTEGER REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%S', 'now')),
	"ip_address" TEXT
);
CREATE TABLE "session_data"(
	"session_id" TEXT NOT NULL REFERENCES "sessions" ON UPDATE CASCADE ON DELETE CASCADE,
	"key" TEXT NOT NULL,
	"value" BLOB,
	PRIMARY KEY("session_id", "key") ON CONFLICT REPLACE
);

-- Login Persistence
CREATE TABLE "login_persistence"(
	"key" TEXT PRIMARY KEY,
	"user_id" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
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
	"user_id" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"group_id" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE,
	PRIMARY KEY("user_id", "group_id") ON CONFLICT REPLACE
);

-- Shares
CREATE TABLE "shares"(
	"id" INTEGER PRIMARY KEY,
	"name" TEXT NOT NULL UNIQUE,
	"path" TEXT NOT NULL,
	"enabled" INTEGER NOT NULL DEFAULT 1,
	"description" TEXT
);
CREATE TABLE "shares_in_groups"(
	"share_id" INTEGER NOT NULL REFERENCES "shares" ON UPDATE CASCADE ON DELETE CASCADE,
	"group_id" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE,
	PRIMARY KEY("share_id", "group_id")
);

-- Bookmarks
CREATE TABLE "bookmarks"(
	"id" INTEGER PRIMARY KEY,
	"user_id" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT NOT NULL UNIQUE ON CONFLICT REPLACE,
	"path" TEXT NOT NULL
);

-- Content-types
CREATE TABLE "content_types"(
	"extenstion" TEXT PRIMARY KEY ON CONFLICT REPLACE NOT NULL,
	"content_type" TEXT NOT NULL
);

COMMIT TRANSACTION;
