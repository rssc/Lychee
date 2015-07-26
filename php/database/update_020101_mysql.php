<?php

###
# @name			Update to version 2.1.1
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

$result = $database->query("ALTER TABLE `".LYCHEE_TABLE_SETTINGS."` CHANGE `value` `value` VARCHAR( 200 ) NULL DEFAULT ''");
if ($result === FALSE) {
	Log::error($database, 'update_020101', __LINE__, 'Could not update database (' . $database->errorInfo() . ')');
	return false;
}

# Set version
if (Database::setVersion($database, '020101')===false) return false;

?>
