Garnet DeGelder's File Manager 3.0 HTTP REST API Documentation
==============================================================

Endpoints
---------
```
/login		Post a username and password to log in, returns session ID if GET parameter `start_session` is true

/users
/groups
/mountpoints
/users/{id}
/groups/{id}
/mountpoints/{id}

/filesystem/<action>		Perform a filesystem action, see Filesystem Actions section

/application/settings		Application setting list
/application/settings/{key}		Application setting (DELETE to reset)

/users/{id}/settings		User setting list
/users/{id}/settings/{key}		User setting (DELETE to reset)

/users/{id}/history		User history (NOT YET IMPLEMENTED)
/users/{id}/history/{id}		History item (NOT YET IMPLEMENTED)

/users/{id}/bookmarks		User bookmarks (NOT YET IMPLEMENTED)
/users/{id}/bookmarks/{id}		User bookmark (NOT YET IMPLEMENTED)

/users/{id}/logins		User login list (NOT YET IMPLEMENTED)
/users/{id}/logins/{id}		User login (NOT YET IMPLEMENTED)

/log		Log file (accessable by admin) (clearable by admin) (NOT YET IMPLEMENTED)

/sessions		Manage sessions (admin only, NOT YET IMPLEMENTED) or start a session by sending a blank post request
/sessions/id		Manage client-side session values (NOT YET IMPLEMENTED)
/sessions/id/{key}		Get/update client-side session values (NOT YET IMPLEMENTED)

/administration/{command}		Used for running administration commands

/search		Performs a search, returns a search ID
/search/{search_id}		The results of a search
```

Filesystem Actions
------------------

- `copy`		Copies a file/directory
- `create`		Creates a file
- `delete`		Deletes a file/directory
- `mkdir`		Creates a directory
- `read`		Returns a file's contents
- `readdir`		Returns a directory listing
- `rename`		Rename/move a file
- `rmdir`		Delete a directory and contents
- `stat`		Get info about a file/directory
- `truncate`		Empty/clear a file
- `unlink`		Delete a file
- `write`		Sets a file's contents

Administration Commands
-----------------------

You must be an administrator to use these commands

- `search_index_rebuild`		Used to reindex part of the filesystem or the whole filesystem
- `search_index_clear`		Clears part of the index or the whole index
- `database_analyze`		Runs `ANALYZE` on the database
- `database_vacuum`		Runs `VACUUM` on the database
