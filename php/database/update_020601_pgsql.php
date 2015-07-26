<?php

###
# @name			Update to version 2.6.1
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Add `downloadable`
$result = $database->query("SELECT downloadable FROM ".LYCHEE_TABLE_ALBUMS." LIMIT 1");
if ($result === FALSE) {
	$result = $database->exec("ALTER TABLE ".LYCHEE_TABLE_ALBUMS." ADD downloadable smallint NOT NULL DEFAULT 1");
	if ($result === FALSE) {
		Log::error($database, 'update_020601', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '020601')===false) return false;

?>
