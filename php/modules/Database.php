<?php

###
# @name		Database Module
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

class Database extends Module {

	static function connect($host = 'localhost', $user, $password, $name = 'lychee', $type = 'mysql') {

		# Check dependencies
		Module::dependencies(isset($host, $user, $password, $name));

		try
		{
			$database = new PDO($type.':host=localhost;dbname='.$name, $user, $password);
		}
		catch (PDOException $e)
		{
			exit ('Error: '.$e->getMessage());
		}

		# Check connection
		#if ($database->connect_errno) exit('Error: ' . $database->connect_error);

		# Avoid sql injection on older MySQL versions by using GBK
		#if ($database->server_version<50500) $database->set_charset('GBK');
		#else $database->set_charset("utf8");

		# Check database
		#if (!$database->select_db($name))
		#	if (!Database::createDatabase($database, $name)) exit('Error: Could not create database!');

		# set mode
		if ($type === "mysql")
		{
			if ($database->exec("SET SESSION sql_mode = ANSI_QUOTES") === FALSE)
			{
				error_log('Cannot set MySQL SQL mode: ' . print_r($database->errorInfo(), TRUE));
			}
		}

		# Check tables
		$result = $database->query('SELECT * FROM '.LYCHEE_TABLE_PHOTOS.', '.LYCHEE_TABLE_ALBUMS.', '.LYCHEE_TABLE_SETTINGS.', '.LYCHEE_TABLE_LOG.' LIMIT 0');
		if ($result === FALSE)
		{
			# tables do not exist, create them
			if (!Database::createTables($database, $type)) exit('Error: Could not create tables!');
		}

		return $database;

	}

	static function update($database, $dbName, $version = 0, $type = 'mysql') {

		# Check dependencies
		Module::dependencies(isset($database, $dbName));

		# List of updates
		$updates = array(
			'020100', #2.1
			'020101', #2.1.1
			'020200', #2.2
			'020500', #2.5
			'020505', #2.5.5
			'020601', #2.6.1
			'020602' #2.6.2
		);

		# For each update
		foreach ($updates as $update) {

			if (isset($version)&&$update<=$version) continue;

			# Load update
			include(__DIR__ . '/../database/update_' . $update . '_'.$type.'.php');

		}

		return true;

	}

	static function createConfig($host = 'localhost', $user, $password, $name = 'lychee', $prefix = '', $type = 'mysql') {

		# Check dependencies
		Module::dependencies(isset($host, $user, $password, $name));

		try
		{
			$database = new PDO($type.':host=localhost;dbname='.$name, $user, $password);
		}
		catch (PDOException $e)
		{
			exit ('Warning: Connection failed: '.$e->getMessage());
		}

		#if ($database->connect_errno) return 'Warning: Connection failed!';

		# Check if database exists
		#if (!$database->select_db($name)) {

			# Database doesn't exist
			# Check if user can create a database
			#$result = $database->query('CREATE DATABASE lychee_dbcheck');
			#if (!$result) return 'Warning: Creation failed!';
			#else $database->query('DROP DATABASE lychee_dbcheck');

		#}

		# Escape data
		$host		= $database->quote($host);
		$user		= $database->quote($user);
		$password	= $database->quote($password);
		$name		= $database->quote($name);
		# FIXME: stricter check for prefix (i.e., only characters and potentially numbers)
		$prefix		= $database->quote($prefix);

		# Save config.php
$config = "<?php

###
# @name			Configuration
# @author		Tobias Reich
# @copyright	2014 Tobias Reich
###

if(!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Database configuration
\$dbType = 'mysql'; # Database type (mysql / pgsql)
\$dbHost = $host; # Host of the database
\$dbUser = $user; # Username of the database
\$dbPassword = $password; # Password of the database
\$dbName = $name; # Database name
\$dbTablePrefix = $prefix; # Table prefix

?>";

		# Save file
		if (file_put_contents(LYCHEE_CONFIG_FILE, $config)===false) return 'Warning: Could not create file!';

		return true;

	}

	static function createDatabase($database, $name = 'lychee') {

		# Check dependencies
		Module::dependencies(isset($database, $name));

		# not implemented
		return false;

		# Create database
		#$result = $database->query("CREATE DATABASE IF NOT EXISTS $name;");
		#$database->select_db($name);

		#if (!$database->select_db($name)||!$result) return false;
		#return true;

	}

	static function createTables($database, $type='pgsql') {

		# Check dependencies
		Module::dependencies(isset($database));

		# Create log
		$result = $database->query('SELECT * FROM '.LYCHEE_TABLE_LOG.' LIMIT 0');
		if ($result === FALSE)
		{

			# Read file
			$file	= __DIR__ . '/../database/log_table_'.$type.'.sql';
			$query	= @file_get_contents($file);

			if (!isset($query)||$query===false) return false;

			# Create table
			# Replace table prefix in query loaded from file (native parametrization of identifiers not supported in PDO)
			$query = str_replace("_PREFIX_", LYCHEE_TABLE_PREFIX, $query);
			$result = $database->exec($query);
			if ($result === FALSE)
			{
				error_log(print_r($database->errorInfo(), TRUE));
				return false;
			}

		}

		# Create settings
		$result = $database->query('SELECT * FROM '.LYCHEE_TABLE_SETTINGS.' LIMIT 0');
		if ($result === FALSE) {

			# Read file
			$file	= __DIR__ . '/../database/settings_table_'.$type.'.sql';
			$query	= @file_get_contents($file);

			if (!isset($query)||$query===false) {
				Log::error($database, __METHOD__, __LINE__, 'Could not load query for lychee_settings');
				return false;
			}

			# Create table
			# Replace table prefix in query loaded from file (native parametrization of identifiers not supported in PDO)
			$query = str_replace("_PREFIX_", LYCHEE_TABLE_PREFIX, $query);
			$result = $database->exec($query);
			if ($result === FALSE)
			{
				Log::error($database, __METHOD__, __LINE__, $database->errorInfo());
				return false;
			}

			# Read file
			$file	= __DIR__ . '/../database/settings_content_'.$type.'.sql';
			$query	= @file_get_contents($file);

			if (!isset($query)||$query===false) {
				Log::error($database, __METHOD__, __LINE__, 'Could not load content-query for lychee_settings');
				return false;
			}

			# Add content
			$query = str_replace("_PREFIX_", LYCHEE_TABLE_PREFIX, $query);
			$result = $database->exec($query);
			if ($result === FALSE)
			{
				Log::error($database, __METHOD__, __LINE__, $database->errorInfo());
				return false;
			}

		}

		# Create albums
		$result = $database->query('SELECT * FROM '.LYCHEE_TABLE_ALBUMS.' LIMIT 0');
		if ($result === FALSE) {

			# Read file
			$file	= __DIR__ . '/../database/albums_table_'.$type.'.sql';
			$query	= @file_get_contents($file);

			if (!isset($query)||$query===false) {
				Log::error($database, __METHOD__, __LINE__, 'Could not load query for lychee_albums');
				return false;
			}

			# Create table
			# Replace table prefix in query loaded from file (native parametrization of identifiers not supported in PDO)
			$query = str_replace("_PREFIX_", LYCHEE_TABLE_PREFIX, $query);
			$result = $database->exec($query);
			if ($result === FALSE)
			{
				Log::error($database, __METHOD__, __LINE__, $database->errorInfo());
				return false;
			}

		}

		# Create photos
		$result = $database->query('SELECT * FROM '.LYCHEE_TABLE_PHOTOS.' LIMIT 0');
		if ($result === FALSE) {

			# Read file
			$file	= __DIR__ . '/../database/photos_table_'.$type.'.sql';
			$query	= @file_get_contents($file);

			if (!isset($query)||$query===false) {
				Log::error($database, __METHOD__, __LINE__, 'Could not load query for lychee_photos');
				return false;
			}

			# Create table
			# Replace table prefix in query loaded from file (native parametrization of identifiers not supported in PDO)
			$query = str_replace("_PREFIX_", LYCHEE_TABLE_PREFIX, $query);
			$result = $database->exec($query);
			if ($result === FALSE)
			{
				Log::error($database, __METHOD__, __LINE__, $database->errorInfo());
				return false;
			}

		}

		return true;

	}

	static function setVersion($database, $version) {

		$stmt = $database->prepare("UPDATE ".LYCHEE_TABLE_SETTINGS." SET value = ? WHERE \"key\" = 'version'");
		$result = $stmt->execute(array($version));
		if ($result === FALSE) {
			Log::error($database, __METHOD__, __LINE__, 'Could not update database (' . print_r($database->errorInfo(), TRUE) . ')');
			return false;
		}

	}

}

?>
