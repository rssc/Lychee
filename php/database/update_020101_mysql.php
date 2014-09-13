<?php

###
# @name			Update to version 2.1.1
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

$result = $database->query("ALTER TABLE `".LYCHEE_TABLE_SETTINGS."` CHANGE `value` `value` VARCHAR( 200 ) NULL DEFAULT ''");
if ($result === FALSE) {
	Log::error($database, 'update_020101', __LINE__, 'Could not update database (' . $database->errorInfo() . ')');
	return false;
}

# Set version
if (Database::setVersion($database, '020101')===false) return false;

?>
