<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta name="application-name" content="<?= html_encode(GARNETDG_FILEMANAGER_NAME); ?>"
			data-copyright="<?= GARNETDG_FILEMANAGER_COPYRIGHT_HTML; ?>"
			data-version="<?= html_encode(GARNETDG_FILEMANAGER_VERSION); ?>"
			data-debug="<?= GARNETDG_FILEMANAGER_DEBUG_ENABLE ? 'true' : 'false'; ?>"
			data-application_static_root_path="<?= html_encode(http_encode_path(get_application_http_root_path())); ?>/client"
			data-application_dynamic_root_path="<?= html_encode(http_encode_path(get_application_http_script_path())); ?>"
			data-application_api_path="<?= html_encode(http_encode_path(get_application_http_script_path())); ?>/api" />
		<title><?= html_encode(GARNETDG_FILEMANAGER_NAME); ?></title>
		<link rel="shortcut icon" href="<?= html_encode(http_encode_path(get_application_http_root_path())); ?>/client/img/favicon.ico" />
		<link rel="stylesheet" href="<?= html_encode(http_encode_path(get_application_http_root_path())); ?>/client/css/normalize.css" />
		<link rel="stylesheet" href="<?= html_encode(http_encode_path(get_application_http_root_path())); ?>/client/css/style.css" />
		<script src="<?= html_encode(http_encode_path(get_application_http_root_path())); ?>/client/js/jquery.js"></script>
		<script src="<?= html_encode(http_encode_path(get_application_http_root_path())); ?>/client/js/filemanager.js"></script>
	</head>
	<body>
		
	</body>
</html>
