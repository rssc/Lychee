<?php

###
# @name			Update to version 2.1.1
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

$result = $database->query("ALTER TABLE ".LYCHEE_TABLE_SETTINGS." ALTER value TYPE CHARACTER VARYING(200)");
if ($result === FALSE) {
	Log::error($database, 'update_020101', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}
$result = $database->query("ALTER TABLE ".LYCHEE_TABLE_SETTINGS." ALTER value SET DEFAULT ''");
if ($result === FALSE) {
	Log::error($database, 'update_020101', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Set version
if (Database::setVersion($database, '020101')===false) return false;

?>
