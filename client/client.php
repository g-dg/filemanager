<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

function exec_client($page_path)
{
	session_start();
	require('client/html/index.php');
	return true;
}
