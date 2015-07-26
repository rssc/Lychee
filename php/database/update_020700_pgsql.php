<?php

###
# @name			Update to version 2.7.0
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Add medium to photos
$result = $database->query("SELECT medium FROM ".LYCHEE_TABLE_PHOTOS." LIMIT 1");
if ($result === false) {
	$result	= $database->exec("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." ADD medium smallint NOT NULL DEFAULT 0");
	if ($result === FALSE) {
		Log::error($database, 'update_020700', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Create medium folder
if (is_dir(LYCHEE_UPLOADS_MEDIUM)===false) {
	# Only create the folder when it is missing
	if (@mkdir(LYCHEE_UPLOADS_MEDIUM)===false)
		Log::error($database, 'update_020700', __LINE__, 'Could not create medium-folder');
}

# Add medium to settings
$result	= $database->query("SELECT key FROM ".LYCHEE_TABLE_SETTINGS." WHERE key = 'medium' LIMIT 1");
if ($result->rowCount()===0) {
	$result	= $database->exec("INSERT INTO ".LYCHEE_TABLE_SETTINGS." (key, value) VALUES ('medium', '1')");
	if ($result === FALSE) {
		Log::error($database, 'update_020700', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '020700')===false) return false;

?>
