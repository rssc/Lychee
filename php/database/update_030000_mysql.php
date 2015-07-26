<?php

###
# @name			Update to version 3.0.0
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Remove login
# Login now saved as crypt without md5. Legacy code has been removed.
$resetUsername = $database->exec("UPDATE `".LYCHEE_TABLE_SETTINGS."` SET `value` = '' WHERE `key` = 'username' LIMIT 1");
if ($resetUsername === FALSE) {
	Log::error($database, 'update_030000', __LINE__, 'Could not reset username (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}
$resetPassword = $database->exec("UPDATE `".LYCHEE_TABLE_SETTINGS."` SET `value` = '' WHERE `key` = 'password' LIMIT 1");
if ($resetPassword === FALSE) {
	Log::error($database, 'update_030000', __LINE__, 'Could not reset password (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Make public albums private and reset password
# Password now saved as crypt without md5. Legacy code has been removed.
$resetPublic = $database->exec("UPDATE `".LYCHEE_TABLE_ALBUMS."` SET `public` = 0, `password` = NULL");
if ($resetPublic === FALSE) {
	Log::error($database, 'update_030000', __LINE__, 'Could not reset public albums (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Set version
if (Database::setVersion($database, '030000')===false) return false;

?>
