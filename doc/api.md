Garnet DeGelder's File Manager 3.0 HTTP REST API Documentation
==============================================================

Endpoints
---------
```
/login		Post a username and password to log in, returns session ID

/users
/groups
/mountpoints
/users/{id}
/groups/{id}
/mountpoints/{id}

/filesystem/<action>		Perform a filesystem action, see Filesystem Actions section

/application/settings		Application setting list
/application/settings/{key}		Application setting
/application/settings/{key}/reset		Post to reset

/users/{id}/settings		User setting list
/users/{id}/settings/{key}		User setting
/users/{id}/settings/{key}/reset		Post to reset

/users/{id}/history		User history
/users/{id}/history/{id}		History item

/users/{id}/bookmarks		User bookmarks
/users/{id}/bookmarks/{id}		User bookmark

/users/{id}/logins		User login list
/users/{id}/logins/{id}		User login

/log		Log file (accessable by admin) (clearable by admin)

/sessions		Manage sessions (admin only) or start a session by sending a blank post request
/sessions/id		Manage client-side session values
/sessions/id/{key}		Get/update client-side session values

/administration/{command}		Used for running administration commands

/search		Performs a search
/search/results/{search_id}		The results of a search
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

- `reIndex`		Used to reindex part of the filesystem or the whole filesystem
- `clearIndex`		Clears part of the index or the whole index
- `analyzeDatabase`		Runs `ANALYZE` on the database
- `vacuumDatabase`		Runs `VACUUM` on the database
