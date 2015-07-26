<?php

###
# @name			Update to version 2.6.2
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Add a checksum
$result = $database->query("SELECT `id`, `url` FROM `".LYCHEE_TABLE_PHOTOS."` WHERE `checksum` IS NULL");
if ($result === FALSE) {
	Log::error($database, 'update_020602', __LINE__, 'Could not find photos without checksum (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}
$stmt = $database->prepare("UPDATE `".LYCHEE_TABLE_PHOTOS."` SET `checksum` = ? WHERE `id` = ?");
if ($stmt === FALSE) {
	Log::error($database, 'update_020602', __LINE__, 'Could not prepare statement to update checksum (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}
while ($photo = $result->fetchObject()) {
	$checksum = sha1_file(LYCHEE_UPLOADS_BIG . $photo->url);
	if ($checksum!==false) {
		$setChecksum = $stmt->execute(array($checksum, $photo->id));
		if ($setChecksum === FALSE) {
			Log::error($database, 'update_020602', __LINE__, 'Could not update checksum (' . print_r($database->errorInfo(), TRUE) . ')');
			return false;
		}
	} else {
		Log::error($database, 'update_020602', __LINE__, 'Could not calculate checksum for photo with id ' . $photo->id);
		return false;
	}
}

# Add Imagick
$result	= $database->query("SELECT `key` FROM `".LYCHEE_TABLE_SETTINGS."` WHERE `key` = 'imagick' LIMIT 1");
if ($result->rowCount()===0) {
	$result = $database->exec("INSERT INTO `".LYCHEE_TABLE_SETTINGS."` (`key`, `value`) VALUES ('imagick', '1')");
	if (!$result) {
		Log::error($database, 'update_020100', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '020602')===false) return false;

?>
