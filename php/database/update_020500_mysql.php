<?php

###
# @name			Update to version 2.5
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

# Add `plugins`
$result = $database->query("SELECT `key` FROM `".LYCHEE_TABLE_SETTINGS."` WHERE `key` = 'plugins' LIMIT 1");
if ($result->rowCount()===0) {
	$result = $database->query("INSERT INTO `".LYCHEE_TABLE_SETTINGS."` (`key`, `value`) VALUES ('plugins', '')");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
		return false;
	}
}

# Add `takestamp`
$result = $database->query("SELECT `takestamp` FROM `".LYCHEE_TABLE_PHOTOS."` LIMIT 1");
if ($result === FALSE) {
	$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` ADD `takestamp` INT(11) DEFAULT NULL");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
		return false;
	}
}

# Convert to `takestamp`
$result = $database->query("SELECT `takedate`, `taketime` FROM `".LYCHEE_TABLE_PHOTOS."` LIMIT 1");
if ($result !== FALSE) {
	$result = $database->query("SELECT `id`, `takedate`, `taketime` FROM `".LYCHEE_TABLE_PHOTOS."` WHERE `takedate` <> '' AND `taketime` <> ''");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
		return false;
	}
	$stmt = $database->prepare("UPDATE `".LYCHEE_TABLE_PHOTOS."` SET `takestamp` = ? WHERE `id` = ?");
	while ($photo = $result->fetchObject()) {
		$takestamp = strtotime($photo->takedate . $photo->taketime);
		$stmt->execute(array($takestamp, $photo->id));
	}
	$result = $database->query("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` DROP COLUMN `takedate`");
	$result = $database->query("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` DROP COLUMN `taketime`");
}

# Remove `import_name`
$result = $database->query("SELECT `import_name` FROM `".LYCHEE_TABLE_PHOTOS."` LIMIT 1");
if ($result !== FALSE) {
	$result = $database->query("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` DROP COLUMN `import_name`");
}

# Remove `sysdate` and `systime`
$result = $database->query("SELECT `sysdate`, `systime` FROM `".LYCHEE_TABLE_PHOTOS."` LIMIT 1");
if ($result !== FALSE) {
	$query	= $database->query("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` DROP COLUMN `sysdate`");
	$query	= $database->query("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` DROP COLUMN `systime`");
}

# Add `sysstamp`
$result = $database->query("SELECT `sysstamp` FROM `".LYCHEE_TABLE_ALBUMS."` LIMIT 1");
if ($result === FALSE) {
	$result = $database->query("ALTER TABLE `".LYCHEE_TABLE_ALBUMS."` ADD `sysstamp` INT(11) DEFAULT NULL");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
		return false;
	}
}

# Convert to `sysstamp`
$result = $database->query("SELECT `sysdate` FROM `".LYCHEE_TABLE_ALBUMS."` LIMIT 1");
if ($result !== FALSE) {
	$result = $database->query("SELECT `id`, `sysdate` FROM `".LYCHEE_TABLE_ALBUMS."`");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
		return false;
	}
	$stmt = $database->prepare("UPDATE `".LYCHEE_TABLE_ALBUMS."` SET `sysstamp` = ? WHERE `id` = ?");
	while ($album = $result->fetchObject()) {
		$sysstamp = strtotime($album->sysdate);
		$result = $stmt->execute(array($sysstamp, $album->id));
	}
	$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_ALBUMS."` DROP COLUMN `sysdate`");
}

# Set character of database
$result = $database->exec("ALTER DATABASE $dbName CHARACTER SET utf8 COLLATE utf8_general_ci;");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
	return false;
}

# Set character
$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_ALBUMS."` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
	return false;
}

# Set character
$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
	return false;
}

# Set character
$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_SETTINGS."` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
	return false;
}

# Set album password length to 100 (for longer hashes)
$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_ALBUMS."` CHANGE `password` `password` VARCHAR(100)");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
	return false;
}

# Set make length to 50
$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` CHANGE `make` `make` VARCHAR(50)");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
	return false;
}

# Reset sorting
$result = $database->exec("UPDATE `".LYCHEE_TABLE_SETTINGS."` SET value = 'ORDER BY takestamp DESC' WHERE `key` = 'sorting' AND `value` LIKE '%UNIX_TIMESTAMP%'");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->error, TRUE) . ')');
	return false;
}

# Set version
if (Database::setVersion($database, '020500')===false) return false;

?>
