Garnet DeGelder's File Manager 3.0 Settings Documentation
=========================================================

General notes
-----------------

Settings are stored in the database as JSON strings, and as such can only contain valid JSON datatypes.

Cascading values
----------------

When accessing settings, there is a level parameter which may be used to specify how far to cascade:
 - SETTING_LEVEL_DEFAULT selects the default value
 - SETTING_LEVEL_SYSTEM selects the system value (it would select the default value if not set, but with the way the database is set up, that will not happen.)
 - SETTING_LEVEL_USER selects a user's preference. When this level is used, a user must be specified, or else the current user will be used. If there is no current user, it will fall back to the system value and log a warning.

