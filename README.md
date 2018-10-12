Garnet DeGelder's File Manager version 3.0.0
============================================

About
=====
This is a web-based file manager implemented in PHP that allows downloading, uploading, and modification of files on a server. It supports multiple users and allows access control using groups.

Requirements
============
* PHP >= 5.6
	* Developed with PHP 7.0
	* PHP 5.6 will also be tested
* PDO SQLite3 >= 3.8.0
	* Developed with SQLite3 3.16.2
	* Should work with >= 3.7.0

Installation
============
1. Copy config.php.sample to config.php
2. Create the specified database file (ensure the file and directory are writable by the server process)
3. Optional: Set the administrator username and password.
	* These are used to set up the administrator account.
4. Navigate to where this application is installed and run index.php

