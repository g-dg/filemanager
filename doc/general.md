Garnet DeGelder's File Manager 3.0 General System Documentation
===============================================================

Directory Structure
-------------------
```
<file manager root folder>
 |- client/         client-side HTML, CSS, and JavaScript
 |- doc/            documentation
 |- server/         server-side PHP code
 |- setup/
 |   `- upgrades/   upgrade scripts, run when needed
 |   `- setup.php   used for setup, run when needed
 |- config.php      the configuration file
 |- index.php       the "bootloader" of the file manager
 |- license.txt     the license
 `- README.md       installation instructions
```

Server-side request timeline
----------------------------

1. Request starts
2. A few constants are defined (such as version)
3. The config file is loaded
4. All php files in the root of the server directory are loaded
5. All php files in the root of the client directory are loaded
6. The database is set up
7. The session is started
8. The registered inits are executed
9. The requested page is executed
10. Request ends
