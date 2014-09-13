<?php

###
# @name			Log Module
# @author		Tobias Reich
# @copyright	2014 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

class Log extends Module {

	public static function notice($database, $function, $line, $text = '') {

		return Log::text($database, 'notice', $function, $line, $text);

	}

	public static function warning($database, $function, $line, $text = '') {

		return Log::text($database, 'warning', $function, $line, $text);

	}

	public static function error($database, $function, $line, $text = '') {

		return Log::text($database, 'error', $function, $line, $text);

	}

	public static function text($database, $type, $function, $line, $text = '') {

		# Check dependencies
		Module::dependencies(isset($database, $type, $function, $line, $text));

		# Get time
		$sysstamp = time();

		# Save in database
        $stmt = $database->prepare("INSERT INTO ".LYCHEE_TABLE_LOG." (time, type, function, line, text) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute(array($sysstamp, $type, $function, $line, $text));

		if (!$result) return false;
		return true;

	}

}

?>
