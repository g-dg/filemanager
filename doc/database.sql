-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- Make it atomic
BEGIN TRANSACTION;

-- Major * 1,000,000 + Minor * 1,000 + Revision
PRAGMA user_version = 3000000;

-- Delete previously existing tables (if any)
DROP VIEW IF EXISTS "view_settings";
DROP VIEW IF EXISTS "view_users_groups_mountpoints_enabled";
DROP VIEW IF EXISTS "view_users_groups_mountpoints";

DROP TABLE IF EXISTS "search_index_keywords";
DROP TABLE IF EXISTS "search_index_entries";
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
DROP TABLE IF EXISTS "settings";
DROP TABLE IF EXISTS "setting_defs";
DROP TABLE IF EXISTS "log";
DROP TABLE IF EXISTS "users";

-- User accounts
CREATE TABLE "users"(
	"id" INTEGER PRIMARY KEY, -- User ID
	"name" TEXT NOT NULL UNIQUE, -- User Name
	"full_name" TEXT NOT NULL, -- User's full name, may default to username
	"password" TEXT NOT NULL, -- User's password hashed with `password_hash`
	"administrator" INTEGER NOT NULL DEFAULT 0, -- Whether the user is an administrator
	"read_only" INTEGER NOT NULL DEFAULT 0, -- Whether the user can change anything to do with their account (such as assword)
	"enabled" INTEGER NOT NULL DEFAULT 1, -- Whether the account is enabled
	"description" TEXT, -- Account details, only viewable and editable by administrator
	"created" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')), -- When the user was created
	"password_changed" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')) -- When the user's password was last changed
);
-- Updates the user's last password change field
CREATE TRIGGER "trigger_users_update_password_changed"
AFTER UPDATE OF "password"
ON "users" FOR EACH ROW
BEGIN
	UPDATE "users" SET "password_changed" = STRFTIME('%s', 'now') WHERE rowid = NEW.rowid;
END;

-- Logging
CREATE TABLE "log"(
	"id" INTEGER PRIMARY KEY, -- Log entry ID
	"level" INTEGER, -- Severity
	"type" TEXT, -- Component that caused the log
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')), -- When the entry was added
	"user" INTEGER REFERENCES "users" ON UPDATE CASCADE ON DELETE SET NULL, -- Which user was logged
	"message" TEXT NOT NULL, -- The logging message
	"details" TEXT, -- JSON object of details
	"client_addr" TEXT, -- The client's IP address
	"method" TEXT, -- The HTTP method
	"path" TEXT, -- The requested path
	"host" TEXT, -- The value of the host header
	"referrer" TEXT, -- The HTTP referrer (if any)
	"user_agent" TEXT -- The HTTP user agent (if any)
);

-- Setting definitions
CREATE TABLE "setting_defs"(
	"key" TEXT PRIMARY KEY, -- Setting key
	"default" TEXT, -- Default value (JSON)
	"system_value" TEXT -- System value (JSON)
);
-- User settings
CREATE TABLE "settings"(
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE, -- User ID
	"key" TEXT NOT NULL REFERENCES "setting_defs" ON UPDATE CASCADE ON DELETE CASCADE, -- Setting key
	"user_value" TEXT, -- User's value (JSON)
	PRIMARY KEY("user", "key") ON CONFLICT REPLACE
);

-- Login history
CREATE TABLE "logins"(
	"id" INTEGER PRIMARY KEY, -- Login ID
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE, -- User ID
	"successful" INTEGER NOT NULL DEFAULT 1, -- Whether the login was successful
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')), -- When the login attempt occurred
	"client_addr" TEXT, -- Client IP address (used for auditing)
	"user_agent" TEXT -- Client User Agent string (for auditing)
);

-- Sessions
CREATE TABLE "sessions"(
	"id" TEXT PRIMARY KEY ON CONFLICT REPLACE, -- Session ID
	"login" INTEGER REFERENCES "logins" ON UPDATE CASCADE ON DELETE CASCADE, -- Login ID, used to tell which user is logged in
	"last_used" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')) -- Session timestamp
);
-- Session data
CREATE TABLE "session_data"(
	"session" TEXT NOT NULL REFERENCES "sessions" ON UPDATE CASCADE ON DELETE CASCADE, -- Session ID
	"key" TEXT NOT NULL, -- Session data key
	"value" TEXT, -- Session data value (Serialized as JSON)
	PRIMARY KEY("session", "key") ON CONFLICT REPLACE
);

-- Login Persistence
CREATE TABLE "login_persistence"(
	"key" TEXT PRIMARY KEY,
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE,
	"secret" TEXT NOT NULL,
	"expires" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now') + 31536000)
);

-- Groups
CREATE TABLE "groups"(
	"id" INTEGER PRIMARY KEY, -- Group ID
	"name" TEXT NOT NULL UNIQUE, -- Name of group
	"enabled" INTEGER NOT NULL DEFAULT 1, -- Whether the group is enabled
	"description" TEXT -- Group description (editable by administrator)
);
-- Maps users to groups
CREATE TABLE "users_in_groups"(
	"user" INTEGER NOT NULL REFERENCES "users" ON UPDATE CASCADE ON DELETE CASCADE, -- User ID
	"group" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE, -- Group ID
	PRIMARY KEY("user", "group") ON CONFLICT REPLACE
);

-- Mountpoints
CREATE TABLE "mountpoints"(
	"id" INTEGER PRIMARY KEY, -- Mountpoint ID
	"name" TEXT NOT NULL UNIQUE, -- Name
	"mountpoint" TEXT NOT NULL UNIQUE, -- Where in the virtual filesystem the mountpoint is
	"target" TEXT NOT NULL, -- Where in the real filesystem the mountpoint references
	"writable" INTEGER NOT NULL DEFAULT 0, -- Whether the mountpoint is writable (overrides all other permissions)
	"enabled" INTEGER NOT NULL DEFAULT 1, -- Whether the mountpoint is enabled
	"description" TEXT -- Mountpoint description (editable by administrator)
);
-- Maps groups to mountpoints
CREATE TABLE "mountpoints_in_groups"(
	"mountpoint" INTEGER NOT NULL REFERENCES "mountpoints" ON UPDATE CASCADE ON DELETE CASCADE, -- Mountpoint ID
	"group" INTEGER NOT NULL REFERENCES "groups" ON UPDATE CASCADE ON DELETE CASCADE, -- Group ID
	"list" INTEGER NOT NULL DEFAULT 1, -- List files inside directories
	"create" INTEGER NOT NULL DEFAULT 0, -- Create files and directories
	"write" INTEGER NOT NULL DEFAULT 0, -- Modify file contents
	"rename" INTEGER NOT NULL DEFAULT 0, -- Rename files, keeping them in the current directory
	"move" INTEGER NOT NULL DEFAULT 0, -- Move files to different spots within the mountpoint
	"delete" INTEGER NOT NULL DEFAULT 0, -- Delete files and directories, required for moving to other mountpoints
	PRIMARY KEY("mountpoint", "group") ON CONFLICT REPLACE
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
CREATE TRIGGER "trigger_bookmarks_update_modified"
AFTER UPDATE OF "name", "path"
ON "bookmarks" FOR EACH ROW
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
/*
File types:
0: file
1: directory
NULL: other/unknown
*/
CREATE TABLE "search_index_entries"(
	"id" INTEGER PRIMARY KEY,
	"parent" INTEGER REFERENCES "search_index_entries" ON UPDATE CASCADE ON DELETE CASCADE,
	"name" TEXT NOT NULL,
	"type" INTEGER NOT NULL,
	"mtime" INTEGER NOT NULL,
	"size" INTEGER NOT NULL,
	"last_indexed" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	UNIQUE("parent", "name") ON CONFLICT REPLACE
);
CREATE TABLE "search_index_keywords"(
	"entry" INTEGER NOT NULL REFERENCES "search_index_entries" ON UPDATE CASCADE ON DELETE CASCADE,
	"keyword" TEXT NOT NULL
);
CREATE INDEX "index_search_index_keywords_keyword" ON "search_index_keywords"("keyword");

-- View for the user-group-mountpoint joins
CREATE VIEW "view_users_groups_mountpoints" AS SELECT
	"users"."id" AS "user_id",
	"users"."name" AS "user_name",
	"users"."full_name" AS "user_full_name",
	"users"."password" AS "user_password",
	"users"."administrator" AS "user_administrator",
	"users"."read_only" AS "user_read_only",
	"users"."enabled" AS "user_enabled",
	"users"."description" AS "user_description",
	"users"."created" AS "user_created",
	"users"."modified" AS "user_modified",
	"groups"."id" AS "group_id",
	"groups"."name" AS "group_name",
	"groups"."enabled" AS "group_enabled",
	"groups"."description" AS "group_description",
	"groups"."created" AS "group_created",
	"groups"."modified" AS "group_modified",
	"mountpoints"."id" AS "mountpoint_id",
	"mountpoints"."name" AS "mountpoint_name",
	"mountpoints"."mountpoint" AS "mountpoint_mountpoint",
	"mountpoints"."target" AS "mountpoint_target",
	"mountpoints"."writable" AND "mountpoints_in_groups"."writable" AS "mountpoint_writable",
	"mountpoints"."enabled" AS "mountpoint_enabled",
	"mountpoints"."description" AS "mountpoint_description",
	"mountpoints"."created" AS "mountpoint_created",
	"mountpoints"."modified" AS "mountpoint_modified"
FROM users
INNER JOIN "users_in_groups" ON "users"."id" = "users_in_groups"."user"
INNER JOIN "groups" ON "groups"."id" = "users_in_groups"."group"
INNER JOIN "mountpoints_in_groups" ON "mountpoints_in_groups"."group" = "groups"."id"
INNER JOIN "mountpoints" ON "mountpoints"."id" = "mountpoints_in_groups"."mountpoint";

-- Get the enabled user-group-mountpoint records
CREATE VIEW "view_users_groups_mountpoints_enabled" AS SELECT
	"user_id",
	"user_name",
	"user_full_name",
	"user_password",
	"user_administrator",
	"user_read_only",
	"user_description",
	"user_created",
	"user_modified",
	"group_id",
	"group_name",
	"group_description",
	"group_created",
	"group_modified",
	"mountpoint_id",
	"mountpoint_name",
	"mountpoint_mountpoint",
	"mountpoint_target",
	"mountpoint_writable",
	"mountpoint_description",
	"mountpoint_created",
	"mountpoint_modified"
FROM "view_users_groups_mountpoints"
WHERE "user_enabled" AND "group_enabled" AND "mountpoint_enabled";

-- All settings
CREATE VIEW "view_settings" AS SELECT
	"setting_defs"."key" AS "key",
	"users"."id" AS "user",
	"setting_defs"."default" AS "default_value",
	"setting_defs"."system_value" AS "system_value",
	COALESCE("settings"."user_value", "setting_defs"."system_value") AS "user_value",
	COALESCE("settings"."modified", "setting_defs"."modified") AS "modified"
FROM "setting_defs"
LEFT JOIN "users" ON 1
LEFT JOIN "settings" ON "settings"."key" = "setting_defs"."key" AND "settings"."user" = "users"."id";

-- End the transaction
COMMIT TRANSACTION;
