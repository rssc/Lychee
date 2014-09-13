<?php

###
# @name			Update to version 2.6.1
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

# Add `downloadable`
$result = $database->query("SELECT `downloadable` FROM `".LYCHEE_TABLE_ALBUMS."` LIMIT 1");
if ($result === FALSE) {
	$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_ALBUMS."` ADD `downloadable` TINYINT(1) NOT NULL DEFAULT 1");
	if ($result === FALSE) {
		Log::error($database, 'update_020601', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '020601')===false) return false;

?>
