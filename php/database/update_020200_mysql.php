<?php

###
# @name			Update to version 2.2
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

$result = $database->query("SELECT `visible` FROM `".LYCHEE_TABLE_ALBUMS."` LIMIT 1");
if($result === FALSE) {
	$result = $database->query("ALTER TABLE `".LYCHEE_TABLE_ALBUMS."` ADD `visible` TINYINT(1) NOT NULL DEFAULT 1");
	if ($result === FALSE) {
		Log::error($database, 'update_020200', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '020200')===false) return false;

?>
