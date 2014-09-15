<?php

###
# @name		Display Log Plugin
# @author	Tobias Reich
# @copyright	2014 by Tobias Reich
# @description	This file queries the database for log messages and displays them if present.
###

# Location
$lychee = __DIR__ . '/../../';

# Load requirements
require($lychee . 'php/define.php');
require($lychee . 'php/autoload.php');
require($lychee . 'php/modules/misc.php');

# Set content
header('content-type: text/plain');

# Load config
if (!file_exists(LYCHEE_CONFIG_FILE)) exit('Error 001: Configuration not found. Please install Lychee first.');
require(LYCHEE_CONFIG_FILE);

# Define the table prefix
if (!isset($dbTablePrefix)) $dbTablePrefix = '';
defineTablePrefix($dbTablePrefix);

# Declare
$result = '';

# Database
try
{
	$database = new PDO($dbType.':host=localhost;dbname='.$dbName, $dbUser, $dbPassword);
}
catch (PDOException $e)
{
	echo 'Error 100: ' . $e->getMessage() . PHP_EOL;
	exit();
}

# Result
if ($database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
{
    $result	= $database->query("SELECT FROM_UNIXTIME(time), type, function, line, text FROM ".LYCHEE_TABLE_LOG);
}
else if ($database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql')
{
    $result	= $database->query("SELECT to_timestamp(time), type, function, line, text FROM ".LYCHEE_TABLE_LOG);
}
else
{
    echo 'Error: Unknown database drive: ' . $database->getAttribute(PDO::ATTR_DRIVER_NAME);
    exit();
}

# Output
if ($result->rowCount()===0) {

	echo('Everything looks fine, Lychee has not reported any problems!' . PHP_EOL . PHP_EOL);

} else {

	while($row = $result->fetch()) {

		# Encode result before printing
		$row = array_map("htmlentities", $row);

		# Format: time TZ - type - function(line) - text
		printf ("%s %s - %s - %s (%s) \t- %s\n", $row[0], date_default_timezone_get(), $row[1], $row[2], $row[3], $row[4]);

	}

}

?>
