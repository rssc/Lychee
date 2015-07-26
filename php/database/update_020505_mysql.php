<?php

###
# @name			Update to version 2.5.5
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Add `checksum`
$result= $database->query("SELECT `checksum` FROM `".LYCHEE_TABLE_PHOTOS."` LIMIT 1");
if ($result === FALSE) {
	$result = $database->exec("ALTER TABLE `".LYCHEE_TABLE_PHOTOS."` ADD `checksum` VARCHAR(100) DEFAULT NULL");
	if ($result === FALSE) {
		Log::error($database, 'update_020505', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '020505')===false) return false;

?>
