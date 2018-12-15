<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

const LOG_EMERGE = 0;
const LOG_ALERT = 1;
const LOG_CRIT = 2;
const LOG_ERR = 3;
const LOG_WARNING = 4;
const LOG_NOTICE = 5;
const LOG_INFO = 6;
const LOG_DEBUG = 7;

function log($level, $message, $type, $details = null)
{
	database_query(
		'INSERT INTO "log"(
	"level",
	"type",
	"user",
	"message",
	"details",
	"client_addr",
	"method",
	"path",
	"host",
	"referrer",
	"user_agent"
) VALUES (
	:level,
	:type,
	:user,
	:message,
	:details,
	:client_addr,
	:method,
	:path,
	:host,
	:referrer,
	:user_agent
);',
		[
			':level' => (int)$level,
			':type' => (string)$type,
			':user' => null,//TODO
			':message' => (string)$message,
			':details' => json_encode($details),
			':client_addr' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null,
			':method' => isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_METHOD'] : null,
			':path' => isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_URI'] : null,
			':host' => isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : null,
			':referrer' => isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : null,
			':user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null
		]
	);
}
