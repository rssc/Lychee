<?php

###
# @name			Update to version 3.0.1
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Change length of photo title
$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` CHANGE `title` `title` VARCHAR( 100 ) NOT NULL DEFAULT ''");
if ($result === FALSE) {
	Log::error($database, 'update_030001', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Change length of album title
$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_ALBUMS."` CHANGE `title` `title` VARCHAR( 100 ) NOT NULL DEFAULT ''");
if ($result === FALSE) {
	Log::error($database, 'update_030001', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Add album sorting to settings
$result = $database->query("SELECT `key` FROM `".LYCHEE_TABLE_SETTINGS."` WHERE `key` = 'sortingAlbums' LIMIT 1");
if ($result->rowCount()===0) {
	$result = $database->exec("INSERT INTO `".LYCHEE_TABLE_SETTINGS."` (`key`, `value`) VALUES ('sortingAlbums', 'ORDER BY id DESC')");
	if ($result === FALSE) {
		Log::error($database, 'update_030001', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Rename sorting to sortingPhotos
$result = $database->exec("UPDATE `".LYCHEE_TABLE_SETTINGS."` SET `key` = 'sortingPhotos' WHERE `key` = 'sorting' LIMIT 1");
if ($result === FALSE) {
	Log::error($database, 'update_030001', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Add identifier to settings
$result = $database->query("SELECT `key` FROM `".LYCHEE_TABLE_SETTINGS."` WHERE `key` = 'identifier' LIMIT 1");
if ($result->rowCount()===0) {
	$identifier	= md5(microtime(true));
	$stmt		= $database->prepare("INSERT INTO `".LYCHEE_TABLE_SETTINGS."` (`key`, `value`) VALUES ('identifier', ?)");
    if ($stmt === FALSE) {
		Log::error($database, 'update_030001', __LINE__, 'Could not prepare statement to add identifier to settings (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
    }
    $result = $stmt->execute(array($identifier));
	if ($result === FALSE) {
		Log::error($database, 'update_030001', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '030001')===false) return false;

?>
