<?php
namespace GarnetDG\FileManager;

if (!defined('GARNETDG_FILEMANAGER')) {
	http_response_code(403);
	exit();
}

class SettingAccessForbiddenException extends Exception {}
class SettingNotFoundException extends Exception {}

/**
 * Get the system value of a setting
 * @param key The key to get
 * @param force Set to true to bypass permission checks
 * @return mixed The setting value
 */
function settings_get_system($key, $force = false)
{
	$result = database_query('SELECT "system_value" FROM "setting_defs" WHERE "key" = ?', [$key]);
	if (count($result) < 1) {
		throw new SettingNotFoundException('Setting key "' . $key . '" was not found');
	}
	return json_decode($result[0][0]);
}

/**
 * Get the user value of a setting
 * @param key The key to get
 * @param user The user to get the setting of
 * @param force Set to true to bypass permission checks
 * @return mixed The setting value
 */
function settings_get_user($key, $user = null, $force = false)
{
	if (is_null($user)) {
		if (is_null($user = auth_current_user_id())) {
			throw new SettingAccessForbiddenException('User is not logged in');
		}
	} else {
		if (!($user == auth_current_user_id() || auth_current_user_administrator() || $force)) {
			throw new SettingAccessForbiddenException('Not allowed to access another user\'s settings');
		}
	}
	$result = database_query('SELECT "user_value" FROM "view_settings" WHERE "key" = ? AND "user" = ?', [$key, $user]);
	if (count($result) < 1) {
		throw new SettingNotFoundException('Setting key "' . $key . '" was not found');
	}
	return json_decode($result[0][0]);
}

/**
 * Set the system value of a setting
 * @param key The key to set
 * @param value The value to set
 * @param force Set to true to bypass permission checks
 */
function settings_set_system($key, $value, $force = false)
{
	if (!(auth_current_user_administrator() || $force)) {
		throw new SettingAccessForbiddenException('Not allowed to access another user\'s settings');
	}
	database_query('UPDATE "settings_defs" SET "system_value" = ? WHERE "key" = ?', [json_encode($value), $key]);
}

/**
 * Set the user value of a setting
 * @param key The key to set
 * @param value The value to set
 * @param user The user to set the setting for
 * @param force Set to true to bypass permission checks
 */
function settings_set_user($key, $value, $user = null, $force = false)
{
	if (is_null($user)) {
		if (is_null($user = auth_current_user_id())) {
			throw new SettingAccessForbiddenException('User is not logged in');
		}
	} else {
		if (!($user == auth_current_user_id() || auth_current_user_administrator() || $force)) {
			throw new SettingAccessForbiddenException('Not allowed to modify another user\'s settings');
		}
	}
	database_query('INSERT INTO "settings"("user", "key", "user_value") VALUES (?, ?, ?)', [$user, $key, json_encode($value)]);
}

/**
 * Set a system setting value to the default
 * @param key The key to reset
 * @param force Set to true to bypass permission checks
 */
function settings_reset_system($key, $force = false)
{
	if (!(auth_current_user_administrator() || $force)) {
		throw new SettingAccessForbiddenException('Not allowed to access another user\'s settings');
	}
	database_query('UPDATE "setting_defs" SET "system_value" = (SELECT "default" FROM "setting_defs" WHERE "key" = ?) WHERE "key" = ?', [$key, $key]);
}

/**
 * Set the user setting to the system value
 * @param key The key to set
 * @param user The user to reset the setting for
 * @param force Set to true to bypass permission checks
 */
function settings_reset_user($key, $user = null, $force = false)
{
	if (is_null($user)) {
		if (is_null($user = auth_current_user_id())) {
			throw new SettingAccessForbiddenException('User is not logged in');
		}
	} else {
		if (!($user == auth_current_user_id() || auth_current_user_administrator() || $force)) {
			throw new SettingAccessForbiddenException('Not allowed to access another user\'s settings');
		}
	}
	database_query('DELETE FROM "settings" WHERE "key" = ? AND "user" = ?', [$key, $user]);
}

/**
 * Gets all the setting values for a particular user
 * @param user The user to get the settings for
 * @param force Set to true to bypass permission checks
 * @return array The setting values
 */
function settings_get_all($user = null, $force = false)
{
	if (is_null($user)) {
		if (is_null($user = auth_current_user_id())) {
			throw new SettingAccessForbiddenException('User is not logged in');
		}
	} else {
		if (!($user == auth_current_user_id() || auth_current_user_administrator() || $force)) {
			throw new SettingAccessForbiddenException('Not allowed to access another user\'s settings');
		}
	}
	$result = database_query('SELECT "key", "user_value" FROM "view_settings" WHERE "user" = ?', [$user]);
	$settings = [];
	foreach ($results as $result) {
		$settings[$result['key']] = json_decode($result['user_value']);
	}
	return $settings;
}
