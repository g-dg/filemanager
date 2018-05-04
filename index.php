<?php
namespace GarnetDG\FileBrowser;

// define some constants
define('GARNETDG_FILEBROWSER_VERSION', '3.0.0-dev');
define('GARNETDG_FILEBROWSER_COPYRIGHT', 'Copyright &copy; 2017-2018 Garnet DeGelder');

// Prevent premature cancellation
set_time_limit(60);
ignore_user_abort(true);

// Error reporting
//TODO: Remove before release
error_reporting(E_ALL);
ini_set('display_errors', 'On');

