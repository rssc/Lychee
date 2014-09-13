<?php

###
# @name			Define
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

# Define root
define('LYCHEE', substr(__DIR__, 0, -3));

# Define dirs
define('LYCHEE_DATA', LYCHEE . 'data/');
define('LYCHEE_BUILD', LYCHEE . 'build/');
define('LYCHEE_UPLOADS', LYCHEE . 'uploads/');
define('LYCHEE_UPLOADS_BIG', LYCHEE_UPLOADS . 'big/');
define('LYCHEE_UPLOADS_MEDIUM', LYCHEE_UPLOADS . 'medium/');
define('LYCHEE_UPLOADS_THUMB', LYCHEE_UPLOADS . 'thumb/');
define('LYCHEE_UPLOADS_IMPORT', LYCHEE_UPLOADS . 'import/');
define('LYCHEE_PLUGINS', LYCHEE . 'plugins/');

# Define files
define('LYCHEE_CONFIG_FILE', LYCHEE_DATA . 'config.php');

# Define urls
define('LYCHEE_URL_UPLOADS_THUMB', 'uploads/thumb/');
define('LYCHEE_URL_UPLOADS_BIG', 'uploads/big/');

function defineTablePrefix($dbTablePrefix) {

	# This part is wrapped into a function, because it needs to be called
	# after the config-file has been loaded. Other defines are also available
	# before the config-file has been loaded.

	# Parse table prefix
	# Old users do not have the table prefix stored in their config-file
	if (!isset($dbTablePrefix)||$dbTablePrefix==='') $dbTablePrefix = '';
	else $dbTablePrefix .= '_';

    # Define prefix
	define('LYCHEE_TABLE_PREFIX', $dbTablePrefix);
	# Define tables
	define('LYCHEE_TABLE_ALBUMS', $dbTablePrefix . 'lychee_albums');
	define('LYCHEE_TABLE_LOG', $dbTablePrefix . 'lychee_log');
	define('LYCHEE_TABLE_PHOTOS', $dbTablePrefix . 'lychee_photos');
	define('LYCHEE_TABLE_SETTINGS', $dbTablePrefix . 'lychee_settings');

}

?>
