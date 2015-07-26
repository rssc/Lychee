<?php

###
# @name			Update to version 3.0.3
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Add skipDuplicates to settings
$result = $database->query("SELECT key FROM ".LYCHEE_TABLE_SETTINGS." WHERE key = 'skipDuplicates' LIMIT 1");
if ($result->rowCount()===0) {
	$result = $database->exec("INSERT INTO ".LYCHEE_TABLE_SETTINGS." (key, value) VALUES ('skipDuplicates', '0')");
	if ($result === FALSE) {
		Log::error($database, 'update_030003', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Set version
if (Database::setVersion($database, '030003')===false) return false;

?>
