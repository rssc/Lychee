<?php

###
# @name			Update to version 2.5.5
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

# Add `checksum`
$result= $database->query("SELECT checksum FROM ".LYCHEE_TABLE_PHOTOS." LIMIT 1");
if ($result === FALSE) {
	$result = $database->exec("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." ADD checksum CHARACTER VARYING(100) DEFAULT NULL");
	if ($result === FALSE) {
		Log::error($database, 'update_020505', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '020505')===false) return false;

?>
