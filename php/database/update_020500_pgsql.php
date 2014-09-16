<?php

###
# @name			Update to version 2.5
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

# Add `plugins`
$result = $database->query("SELECT key FROM ".LYCHEE_TABLE_SETTINGS." WHERE key = 'plugins' LIMIT 1");
if ($result->rowCount()===0) {
	$result = $database->query("INSERT INTO ".LYCHEE_TABLE_SETTINGS." (key, value) VALUES ('plugins', '')");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Add `takestamp`
$result = $database->query("SELECT takestamp FROM ".LYCHEE_TABLE_PHOTOS." LIMIT 1");
if ($result === FALSE) {
	$result = $database->exec("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." ADD takestamp INTEGER DEFAULT NULL");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Convert to `takestamp`
$result = $database->query("SELECT takedate, taketime FROM ".LYCHEE_TABLE_PHOTOS." LIMIT 1");
if ($result !== FALSE) {
	$result = $database->query("SELECT id, takedate, taketime FROM ".LYCHEE_TABLE_PHOTOS." WHERE takedate <> '' AND taketime <> ''");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
	$stmt = $database->prepare("UPDATE ".LYCHEE_TABLE_PHOTOS." SET takestamp = ? WHERE id = ?");
	while ($photo = $result->fetchObject()) {
		$takestamp = strtotime($photo->takedate . $photo->taketime);
		$stmt->execute(array($takestamp, $photo->id));
	}
	$result = $database->query("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." DROP COLUMN takedate");
	$result = $database->query("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." DROP COLUMN taketime");
}

# Remove `import_name`
$result = $database->query("SELECT import_name FROM ".LYCHEE_TABLE_PHOTOS." LIMIT 1");
if ($result !== FALSE) {
	$result = $database->query("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." DROP COLUMN import_name");
}

# Remove `sysdate` and `systime`
$result = $database->query("SELECT sysdate, systime FROM ".LYCHEE_TABLE_PHOTOS." LIMIT 1");
if ($result !== FALSE) {
	$query	= $database->query("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." DROP COLUMN sysdate");
	$query	= $database->query("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." DROP COLUMN systime");
}

# Add `sysstamp`
$result = $database->query("SELECT sysstamp FROM ".LYCHEE_TABLE_ALBUMS." LIMIT 1");
if ($result === FALSE) {
	$result = $database->query("ALTER TABLE ".LYCHEE_TABLE_ALBUMS." ADD sysstamp INTEGER DEFAULT NULL");
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
}

# Convert to `sysstamp`
$result = $database->query("SELECT sysdate FROM ".LYCHEE_TABLE_ALBUMS." LIMIT 1");
if ($result !== FALSE) {
	$result = $database->query("SELECT id, sysdate FROM ".LYCHEE_TABLE_ALBUMS);
	if ($result === FALSE) {
		Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
		return false;
	}
	$stmt = $database->prepare("UPDATE ".LYCHEE_TABLE_ALBUMS." SET `sysstamp = ? WHERE id = ?");
	while ($album = $result->fetchObject()) {
		$sysstamp = strtotime($album->sysdate);
		$result = $stmt->execute(array($sysstamp, $album->id));
	}
	$result = $database->exec("ALTER TABLE ".LYCHEE_TABLE_ALBUMS." DROP COLUMN sysdate");
}

# Set album password length to 100 (for longer hashes)
$result = $database->exec("ALTER TABLE ".LYCHEE_TABLE_ALBUMS." ALTER password TYPE CHARACTER VARYING(100)");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Set make length to 50
$result = $database->exec("ALTER TABLE ".LYCHEE_TABLE_PHOTOS." ALTER make TYPE CHARACTER VARYING(50)");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Reset sorting
$result = $database->exec("UPDATE ".LYCHEE_TABLE_SETTINGS." SET value = 'ORDER BY takestamp DESC' WHERE key = 'sorting' AND value LIKE '%UNIX_TIMESTAMP%'");
if ($result === FALSE) {
	Log::error($database, 'update_020500', __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
	return false;
}

# Set version
if (Database::setVersion($database, '020500')===false) return false;

?>
