<?php

###
# @name			Update to version 2.1
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

$result = $database->query("SELECT `tags` FROM `".LYCHEE_TABLE_PHOTOS."` LIMIT 1");
if($result === FALSE) {
	$result = $database->query("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` ADD `tags` CHARACTER VARYING(1000) NULL DEFAULT ''");
	if ($result === FALSE) {
		Log::error($database, 'update_020100', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

$result = $database->query("SELECT `key` FROM `".LYCHEE_TABLE_SETTINGS."` WHERE `key` = 'dropboxKey' LIMIT 1");
if ($result->rowCount()===0) {
	$result = $database->query($database, "INSERT INTO `".LYCHEE_TABLE_SETTINGS."` (`key`, `value`) VALUES ('dropboxKey', '')");
	if ($result === FALSE) {
		Log::error($database, 'update_020100', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

$result = $database->query("SELECT `key` FROM `".LYCHEE_TABLE_SETTINGS."` WHERE `key` = 'version' LIMIT 1");
if ($result->rowCount()===0) {
	$result = $database->query("INSERT INTO `".LYCHEE_TABLE_SETTINGS."` (`key`, `value`) VALUES ('version', '020100')");
	if ($result === FALSE) {
		Log::error($database, 'update_020100', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
} else {
	if (Database::setVersion($database, '020100')===false) return false;
}

?>
