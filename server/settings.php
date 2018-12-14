<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class SettingAccessForbiddenException extends Exception {}
class SettingNotFoundException extends Exception {}

const SETTING_LEVEL_DEFAULT = 0;
const SETTING_LEVEL_SYSTEM = 1;
const SETTING_LEVEL_USER = 2;

/**
 * Get the value of a setting
 * @param key The setting to get
 * @param level The level to cascade to
 * @param user The user ID to get the setting of
 */
function settings_get($key, $level = SETTING_LEVEL_USER, $user = null)
{
	if (is_null($user)) {
		$user = auth_current_user_id();
	}

	if (is_null($user)) { // user is not logged in
		if ($level == SETTING_LEVEL_USER) {
			throw new SettingAccessForbiddenException('User is not logged in');
		}
		$result = database_query('SELECT "default" AS "default_value", "system_value" FROM "setting_defs" WHERE "key" = ?', [$key]);
		if (count($result) < 1) {
			throw new SettingNotFoundException('Setting key "' . $key . '" was not found');
		}
		$setting = $result[0];
	} else {
		$result = database_query('SELECT "default_value", "system_value", "user_value" FROM "view_settings" WHERE "key" = ? AND "user" = ?', [$key, $user]);
		if (count($result) < 1) {
			throw new SettingNotFoundException('Setting key "' . $key . '" was not found');
		}
		$setting = $result[0];
	}

	switch ($level) {
		case SETTING_LEVEL_DEFAULT:
			return json_decode($setting['default_value']);
		case SETTING_LEVEL_SYSTEM:
			return json_decode($setting['system_value']);
		case SETTING_LEVEL_USER:
			return json_decode($setting['user_value']);
		default:
			throw new Exception('Invalid setting level');
	}
}

/**
 * Set the value of a setting
 * @param key The setting to set
 * @param level The level of the setting to set. Note: SETTING_LEVEL_DEFAULT may not be used and will result in an InvalidArgumentException
 * @param user The user ID to set the setting of
 */
function settings_set($key, $level = SETTING_LEVEL_USER, $user = null)
{

}

/**
 * Reset the setting to the level above it
 * @param key The setting to reset
 * @param level The level of the setting to reset Note: SETTING_LEVEL_DEFAULT may not be used and will result in an InvalidArgumentException
 * @param user The user ID to reset the setting of
 */
function settings_reset($key, $level = SETTING_LEVEL_USER, $user = null)
{

}

/**
 * Get all setting keys and values
 * @param level The level of the settings to get
 * @param user The user to get the settings of
 */
function settings_list($level = SETTING_LEVEL_USER, $user = null)
{

}
