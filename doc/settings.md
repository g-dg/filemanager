Garnet DeGelder's File Manager 3.0 Settings Documentation
=========================================================

General notes
-------------

Settings are stored in the database as JSON strings, and as such can only contain valid JSON datatypes.


Settings
--------

- `session.gc.probability`: `0.001`
- `session.gc.age.max`: `31536000`
- `session.cookie.name`: `"sessid"`
- `session.id.length`: `255`
- `session.id.chars`: `"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"`
- `session.age.max`: `2592000`
- `session.csrf_token.length`: `255`
- `session.csrf_token.chars`: `"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"`
- `database.analyze.probability`: `0.001`
- `database.vacuum.probability`: `0.001`
- `application.base_uri`: `null`
- `application.index_page`: `null`
- `files.buffersize`: `4096`
- `search.index.word_separators`: ``" !\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~"``
- `search.limit.max_running_searches`: `2`
- `search.index.autoindex.max_time`: `100`
- `search.index.min_age`: `0`
- `search.index.max_age`: `86400`
- `auth.delay.base`: `500`
- `auth.delay.on_previous_failures`: `1000`

- `browser.sort.field`: `"name"`
- `browser.sort.order`: `"asc"`
- `browser.sort.directories_first`: `false`
- `browser.group.field`: `false`
- `browser.view.mode`: `"list"`
- `browser.view.hidden`: `false`
- `browser.view.zoom`: `100`
- `browser.sidebar.mode`: `false`
- `search.hidden.include`: `false`
