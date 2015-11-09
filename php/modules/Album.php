<?php

###
# @name			Album Module
# @copyright	2015 by Tobias Reich
###

if (!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

class Album extends Module {

	private $database	= null;
	private $settings	= null;
	private $albumIDs	= null;

	public function __construct($database, $plugins, $settings, $albumIDs) {

		# Init vars
		$this->database	= $database;
		$this->plugins	= $plugins;
		$this->settings	= $settings;
		$this->albumIDs	= $albumIDs;

		return true;

	}

	public function add($title = 'Untitled', $public = 0, $visible = 1) {

		# Check dependencies
		self::dependencies(isset($this->database));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Parse
		if (strlen($title)>50) $title = substr($title, 0, 50);

		# Database
		$sysstamp	= time();
		$stmt		= $this->database->prepare("INSERT INTO ".LYCHEE_TABLE_ALBUMS." (title, sysstamp, public, visible) VALUES (?, ?, ?, ?)");
		if ($stmt === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
			return false;
		}
		$result     = $stmt->execute(array($title, $sysstamp, $public, $visible));

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if ($result === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
			return false;
		}

		if ($this->database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
		{
			return $this->database->lastInsertId();
		}
		else if ($this->database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql')
		{
			return $this->database->lastInsertId(LYCHEE_TABLE_ALBUMS.'_id_seq');
		}
		else
		{
			Log::error($this->database, __METHOD__, __LINE__, 'Unknown database driver: ' . $this->database->getAttribute(PDO::ATTR_DRIVER_NAME));
			return false;
		}

	}

	public static function prepareData($data) {

		# This function requires the following album-attributes and turns them
		# into a front-end friendly format: id, title, public, sysstamp, password
		# Note that some attributes remain unchanged

		# Check dependencies
		self::dependencies(isset($data));

		# Init
		$album = null;

		# Set unchanged attributes
		$album['id']		= $data['id'];
		$album['title']		= $data['title'];
		$album['public']	= $data['public'];

		# Parse date
		$album['sysdate'] = date('F Y', $data['sysstamp']);

		# Parse password
		$album['password'] = ($data['password']=='' ? '0' : '1');

		# Set placeholder for thumbs
		$album['thumbs'] = array();

		return $album;

	}

	public function get() {

		# Check dependencies
		self::dependencies(isset($this->database, $this->settings, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Get album information
		switch ($this->albumIDs) {

			case 'f':	$return['public'] = '0';
						$photos = $this->database->query("SELECT id, title, tags, public, star, album, thumburl, takestamp, url FROM ".LYCHEE_TABLE_PHOTOS." WHERE star = 1 " . $this->settings['sortingPhotos']);
						break;

			case 's':	$return['public'] = '0';
						$photos = $this->database->query("SELECT id, title, tags, public, star, album, thumburl, takestamp, url FROM ".LYCHEE_TABLE_PHOTOS." WHERE public = 1 " . $this->settings['sortingPhotos']);
						break;

			case 'r':	$return['public'] = '0';
						if ($this->database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
						{
							$photos = $this->database->query("SELECT id, title, tags, public, star, album, thumburl, takestamp, url FROM ".LYCHEE_TABLE_PHOTOS." WHERE LEFT(id, 10) >= unix_timestamp(DATE_SUB(NOW(), INTERVAL 1 DAY)) " . $this->settings['sortingPhotos']);
						}
						else if ($this->database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql')
						{
							$photos = $this->database->query("SELECT id, title, tags, public, star, album, thumburl, takestamp, url FROM ".LYCHEE_TABLE_PHOTOS." WHERE id >= extract(epoch FROM NOW() - INTERVAL '1' DAY)*10000 " . $this->settings['sortingPhotos']);
						}
						else
						{
							Log::error($this->database, __METHOD__, __LINE__, 'Unknown database driver: ' . $this->database->getAttribute(PDO::ATTR_DRIVER_NAME));
						}
						break;

			case '0':	$return['public'] = '0';
						$photos = $this->database->query("SELECT id, title, tags, public, star, album, thumburl, takestamp, url FROM ".LYCHEE_TABLE_PHOTOS." WHERE album = '0' " . $this->settings['sortingPhotos']);
						break;

			default:	$stmt = $this->database->prepare("SELECT * FROM ".LYCHEE_TABLE_ALBUMS." WHERE id = ? LIMIT 1");
						$albums = $stmt->execute(array($this->albumIDs));
						$return = $stmt->fetch(PDO::FETCH_ASSOC);
						# fix public, visible, downloadable
						$return['public'] = $return['public'] == 1 ? '1' : '0';
						$return['visible'] = $return['visible'] == 1 ? '1' : '0';
						$return['downloadable'] = $return['downloadable'] == 1 ? '1' : '0';
						#
						$return['sysdate'] = date('d M. Y', $return['sysstamp']);
						$return['password'] = ($return['password']=='' ? '0' : '1');
						$stmt = $this->database->prepare("SELECT id, title, tags, public, star, album, thumburl, takestamp, url FROM ".LYCHEE_TABLE_PHOTOS." WHERE album = ? " . $this->settings['sortingPhotos']);
						$photos = $stmt->execute(array($this->albumIDs));
						$photos = $stmt;
						break;

		}

		# Get photos
		$previousPhotoID	= '';
		while ($photo = $photos->fetch(PDO::FETCH_ASSOC)) {

			# Turn data from the database into a front-end friendly format
			$photo = Photo::prepareData($photo);

			# Set previous and next photoID for navigation purposes
			$photo['previousPhoto'] = $previousPhotoID;
			$photo['nextPhoto']		= '';

			# Set current photoID as nextPhoto of previous photo
			if ($previousPhotoID!=='') $return['content'][$previousPhotoID]['nextPhoto'] = $photo['id'];
			$previousPhotoID = $photo['id'];

			# Add to return
			$return['content'][$photo['id']] = $photo;

		}

		if ($photos->rowCount()===0) {

			# Album empty
			$return['content'] = false;

		} else {

			# Enable next and previous for the first and last photo
			$lastElement	= end($return['content']);
			$lastElementId	= $lastElement['id'];
			$firstElement	= reset($return['content']);
			$firstElementId	= $firstElement['id'];

			if ($lastElementId!==$firstElementId) {
				$return['content'][$lastElementId]['nextPhoto']			= $firstElementId;
				$return['content'][$firstElementId]['previousPhoto']	= $lastElementId;
			}

		}

		$return['id']	= $this->albumIDs;
		$return['num']	= $photos->rowCount();

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		return $return;

	}

	public function getAll($public) {

		# Check dependencies
		self::dependencies(isset($this->database, $this->settings, $public));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Initialize return var
		$return = array(
			'smartalbums'	=> null,
			'albums'		=> null,
			'num'			=> 0
		);

		# Get SmartAlbums
		if ($public===false) $return['smartalbums'] = $this->getSmartInfo();

		# Albums query
		if ($public===false)
		{
			$albums = $this->database->query('SELECT id, title, public, sysstamp, password FROM '.LYCHEE_TABLE_ALBUMS.' '.$this->settings['sortingAlbums']);
		}
		else
		{
			$albums = $this->database->query('SELECT id, title, public, sysstamp, password FROM '.LYCHEE_TABLE_ALBUMS.' WHERE public = 1 AND visible <> 0 '.$this->settings['sortingAlbums']);
		}

		# check query status
		if ($albums === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, 'Could not get all albums (' . print_r($this->database->errorInfo(), TRUE) . ')');
			exit('Error: ' . print_r($this->database->errorInfo(), TRUE));
		}

		# prepare thumbnail statement
		$stmtThumbs = $this->database->prepare("SELECT thumburl FROM ".LYCHEE_TABLE_PHOTOS." WHERE album = ? ORDER BY star DESC, " . substr($this->settings['sortingPhotos'], 9) . " LIMIT 3");
		if ($stmtThumbs === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, 'Could not get prepare statement for thumbnails (' . print_r($this->database->errorInfo(), TRUE) . ')');
			exit('Error: ' . print_r($this->database->errorInfo(), TRUE));
		}
		# For each album
		while ($album = $albums->fetch(PDO::FETCH_ASSOC)) {

			# Turn data from the database into a front-end friendly format
			$album = Album::prepareData($album);

			# Thumbs
			if (($public===true&&$album['password']==='0')||
				($public===false)) {

				# Execute query
				$resultThumbs = $stmtThumbs->execute(array($album['id']));
				if ($resultThumbs === FALSE) {
					Log::error($this->database, __METHOD__, __LINE__, 'Could not get thumbnails (' . print_r($this->database->errorInfo(), TRUE) . ')');
					exit('Error: ' . print_r($this->database->errorInfo(), TRUE));
				}

				# For each thumb
				$k = 0;
				while ($thumb = $stmtThumbs->fetchObject()) {
					$album['thumbs'][$k] = LYCHEE_URL_UPLOADS_THUMB . $thumb->thumburl;
					$k++;
				}

			}

			# Add to return
			$return['albums'][] = $album;

		}

		# Num of albums
		$return['num'] = $albums->rowCount();

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		return $return;

	}

	private function getSmartInfo() {

		# Check dependencies
		self::dependencies(isset($this->database, $this->settings));

		# Initialize return var
		$return = array(
			'unsorted'	=> null,
			'public'	=> null,
			'starred'	=> null,
			'recent'	=> null
		);

		###
		# Unsorted
		###

		$unsorted	= $this->database->query("SELECT thumburl FROM ".LYCHEE_TABLE_PHOTOS." WHERE album = '0' " . $this->settings['sortingPhotos']);
		if ($unsorted === FALSE) Log::error($this->database, __METHOD__, __LINE__, 'Could not get unsorted thumbnails (' . print_r($this->database->errorInfo(), TRUE) . ')');
		$i			= 0;

		$return['unsorted'] = array(
			'thumbs'	=> array(),
			'num'		=> $unsorted->rowCount()
		);

		while($row = $unsorted->fetchObject()) {
			if ($i<3) {
				$return['unsorted']['thumbs'][$i] = LYCHEE_URL_UPLOADS_THUMB . $row->thumburl;
				$i++;
			} else break;
		}

		###
		# Starred
		###

		$starred	= $this->database->query('SELECT thumburl FROM '.LYCHEE_TABLE_PHOTOS.' WHERE star = 1 ' . $this->settings['sortingPhotos']);
		if ($starred === FALSE) Log::error($this->database, __METHOD__, __LINE__, 'Could not get starred thumbnails (' . print_r($this->database->errorInfo(), TRUE) . ')');
		$i			= 0;

		$return['starred'] = array(
			'thumbs'	=> array(),
			'num'		=> $starred->rowCount()
		);

		while($row3 = $starred->fetchObject()) {
			if ($i<3) {
				$return['starred']['thumbs'][$i] = LYCHEE_URL_UPLOADS_THUMB . $row3->thumburl;
				$i++;
			} else break;
		}

		###
		# Public
		###

		$public		= $this->database->query('SELECT thumburl FROM '.LYCHEE_TABLE_PHOTOS.' WHERE public = 1 ' . $this->settings['sortingPhotos']);
		if ($public === FALSE) Log::error($this->database, __METHOD__, __LINE__, 'Could not get public thumbnails (' . print_r($this->database->errorInfo(), TRUE) . ')');
		$i			= 0;

		$return['public'] = array(
			'thumbs'	=> array(),
			'num'		=> $public->rowCount()
		);

		while($row2 = $public->fetchObject()) {
			if ($i<3) {
				$return['public']['thumbs'][$i] = LYCHEE_URL_UPLOADS_THUMB . $row2->thumburl;
				$i++;
			} else break;
		}

		###
		# Recent
		###

		if ($this->database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
		{
			$recent		= $this->database->query('SELECT thumburl FROM '.LYCHEE_TABLE_PHOTOS." WHERE LEFT(id, 10) >= unix_timestamp(DATE_SUB(NOW(), INTERVAL 1 DAY)) " . $this->settings['sortingPhotos']);
		}
		elseif ($this->database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql')
		{
			$recent		= $this->database->query('SELECT thumburl FROM '.LYCHEE_TABLE_PHOTOS." WHERE id >= extract(epoch FROM NOW() - INTERVAL '1' DAY)*10000 " . $this->settings['sortingPhotos']);
		}
		else
		{
			Log::error($this->database, __METHOD__, __LINE__, 'Could not get recent thumbnails, unknown database driver: ' . $this->database->getAttribute(PDO::ATTR_DRIVER_NAME));
		}

		$i = 0;

		$return['recent'] = array(
			'thumbs'	=> array(),
			'num'		=> $recent->rowCount()
		);

		while($row3 = $recent->fetchObject()) {
			if ($i<3) {
				$return['recent']['thumbs'][$i] = LYCHEE_URL_UPLOADS_THUMB . $row3->thumburl;
				$i++;
			} else break;
		}

		# Return SmartAlbums
		return $return;

	}

	public function getArchive() {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Illicit chars
		$badChars =	array_merge(
						array_map('chr', range(0,31)),
						array("<", ">", ":", '"', "/", "\\", "|", "?", "*")
					);

		# Photos query
		switch($this->albumIDs) {
			case 's':
				$photos		= $this->database->query('SELECT title, url FROM '.LYCHEE_TABLE_PHOTOS.' WHERE public = 1');
				$zipTitle	= 'Public';
				break;
			case 'f':
				$photos		= $this->database->query('SELECT title, url FROM '.LYCHEE_TABLE_PHOTOS.' WHERE star = 1');
				$zipTitle	= 'Starred';
				break;
			case 'r':
				# FIXME: GROUP BY checksum??
				$photos		= $this->database->query("SELECT title, url FROM ".LYCHEE_TABLE_PHOTOS." WHERE id >= extract(epoch FROM NOW() - INTERVAL '1' DAY)*10000"); // GROUP BY checksum");
				$zipTitle	= 'Recent';
				break;
			default:
				$stmt		= $this->database->prepare("SELECT title, url FROM ".LYCHEE_TABLE_PHOTOS." WHERE album = ?");
				$result     = $stmt->execute(array($this->albumIDs));
				$photos     = $stmt;
				$zipTitle	= 'Unsorted';
		}

		if ($photos === FALSE)
		{
			Log::error($this->database, __METHOD__, __LINE__, 'Could not get photos for archive: ' . print_r($this->database->errorInfo(), TRUE));
		}

		# Get title from database when album is not a SmartAlbum
		if ($this->albumIDs!=0&&is_numeric($this->albumIDs)) {

			$stmt2 = $this->database->prepare("SELECT title FROM ".LYCHEE_TABLE_ALBUMS." WHERE id = ? LIMIT 1");
			$album = $stmt2->execute(array($this->albumIDs));

			# Error in database query
			if ($album === FALSE) {
				Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
				return false;
			}

			# Fetch object
			$album = $stmt2->fetchObject();

			# Photo not found
			if ($album===null) {
				Log::error($this->database, __METHOD__, __LINE__, 'Album not found. Cannot start download.');
				return false;
			}

			# Set title
			$zipTitle = $album->title;

		}

		# Escape title
		$zipTitle = str_replace($badChars, '', $zipTitle);

		$filename = LYCHEE_DATA . $zipTitle . '.zip';

		# Create zip
		$zip = new ZipArchive();
		if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
			Log::error($this->database, __METHOD__, __LINE__, 'Could not create ZipArchive');
			return false;
		}

		# Check if album empty
		if ($photos->rowCount()==0) {
			Log::error($this->database, __METHOD__, __LINE__, 'Could not create ZipArchive without images');
			return false;
		}

		# Parse each path
		$files = array();
		while ($photo = $photos->fetchObject()) {

			# Parse url
			$photo->url = LYCHEE_UPLOADS_BIG . $photo->url;

			# Parse title
			$photo->title = str_replace($badChars, '', $photo->title);
			if (!isset($photo->title)||$photo->title==='') $photo->title = 'Untitled';

			# Check if readable
			if (!@is_readable($photo->url)) continue;

			# Get extension of image
			$extension = getExtension($photo->url);

			# Set title for photo
			$zipFileName = $zipTitle . '/' . $photo->title . $extension;

			# Check for duplicates
			if (!empty($files)) {
				$i = 1;
				while (in_array($zipFileName, $files)) {

					# Set new title for photo
					$zipFileName = $zipTitle . '/' . $photo->title . '-' . $i . $extension;

					$i++;

				}
			}

			# Add to array
			$files[] = $zipFileName;

			# Add photo to zip
			$zip->addFile($photo->url, $zipFileName);

		}

		# Finish zip
		$zip->close();

		# Send zip
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=\"$zipTitle.zip\"");
		header("Content-Length: " . filesize($filename));
		readfile($filename);

		# Delete zip
		unlink($filename);

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		return true;

	}

	public function setTitle($title = 'Untitled') {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Execute query
		$stmt	= $this->database->prepare("UPDATE ".LYCHEE_TABLE_ALBUMS." SET title = ? WHERE id IN (?)");
		$result = $stmt->execute(array($title, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if ($result === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
			return false;
		}
		return true;

	}

	public function setDescription($description = '') {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Execute query
		$stmt	= $this->database->prepare("UPDATE ".LYCHEE_TABLE_ALBUMS." SET description = ? WHERE id IN (?)");
		$result = $stmt->execute(array($description, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if ($result === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
			return false;
		}
		return true;

	}

	public function getPublic() {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		if ($this->albumIDs==='0'||$this->albumIDs==='s'||$this->albumIDs==='f') return false;

		# Execute query
		$stmt	= $this->database->prepare("SELECT public FROM ".LYCHEE_TABLE_ALBUMS." WHERE id = ? LIMIT 1");
		$result = $stmt->execute(array($this->albumIDs));
		$album	= $stmt->fetchObject();

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if ($album->public==1) return true;
		return false;

	}

	public function getDownloadable() {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		if ($this->albumIDs==='0'||$this->albumIDs==='s'||$this->albumIDs==='f'||$this->albumIDs==='r') return false;

		# Execute query
		$stmt	= $this->database->prepare("SELECT downloadable FROM ".LYCHEE_TABLE_ALBUMS." WHERE id = ? LIMIT 1");
		$result = $stmt->execute(array($this->albumIDs));
		$album	= $stmt->fetchObject();

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if ($album->downloadable==1) return true;
		return false;

	}

	public function setPublic($public, $password, $visible, $downloadable) {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Convert values
		$public			= ($public==='1' ? 1 : 0);
		$visible		= ($visible==='1' ? 1 : 0);
		$downloadable	= ($downloadable==='1' ? 1 : 0);

		# Set public
		$stmt = $this->database->prepare("UPDATE ".LYCHEE_TABLE_ALBUMS." SET public = ?, visible = ?, downloadable = ?, password = NULL WHERE id IN (?)");
		$result = $stmt->execute(array($public, $visible, $downloadable, $this->albumIDs));
		if ($result === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
			return false;
		}

		# Reset permissions for photos
		if ($public===1) {
			$stmt	= $this->database->prepare("UPDATE ".LYCHEE_TABLE_PHOTOS." SET public = 0 WHERE album IN (?)");
			$result = $stmt->execute(array($this->albumIDs));
			if ($result === FALSE) {
				Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
				return false;
			}
		}

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		# Set password
		if (isset($password)&&strlen($password)>0) return $this->setPassword($password);

		return true;

	}

	private function setPassword($password) {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		if (strlen($password)>0) {

			# Get hashed password
			$password = getHashedString($password);

			# Set hashed password
			# Do not prepare $password because it is hashed and save
			# Preparing (escaping) the password would destroy the hash
			$stmt	= $this->database->prepare("UPDATE ".LYCHEE_TABLE_ALBUMS." SET password = ? WHERE id IN (?)");
			$result = $stmt->execute(array($password, $this->albumIDs));

		} else {

			# Unset password
			$stmt	= $this->database->prepare("UPDATE ".LYCHEE_TABLE_ALBUMS." SET password = NULL WHERE id IN (?)");
			$result = $stmt->execute(array($this->albumIDs));

		}

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if ($result === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
			return false;
		}
		return true;

	}

	public function checkPassword($password) {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Execute query
		$stmt	= $this->database->prepare("SELECT password FROM ".LYCHEE_TABLE_ALBUMS." WHERE id = ? LIMIT 1");
		$result = $stmt->execute(array($this->albumIDs));
		$album	= $stmt->fetchObject();

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if ($album->password=='') return true;
		else if ($album->password===crypt($password, $album->password)) return true;
		return false;

	}

	public function merge() {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Convert to array
		$albumIDs = explode(',', $this->albumIDs);

		# Get first albumID
		$albumID = array_splice($albumIDs, 0, 1);
		$albumID = $albumID[0];

		$query	= Database::prepare($this->database, "UPDATE ? SET album = ? WHERE album IN (?)", array(LYCHEE_TABLE_PHOTOS, $albumID, $this->albumIDs));
		$result	= $this->database->query($query);

		if (!$result) {
			Log::error($this->database, __METHOD__, __LINE__, $this->database->error);
			return false;
		}

		# $albumIDs contains all IDs without the first albumID
		# Convert to string
		$filteredIDs = implode(',', $albumIDs);

		$query	= Database::prepare($this->database, "DELETE FROM ? WHERE id IN (?)", array(LYCHEE_TABLE_ALBUMS, $filteredIDs));
		$result	= $this->database->query($query);

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if (!$result) {
			Log::error($this->database, __METHOD__, __LINE__, $this->database->error);
			return false;
		}
		return true;

	}

	public function delete() {

		# Check dependencies
		self::dependencies(isset($this->database, $this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 0, func_get_args());

		# Init vars
		$error = false;

		# Execute query
		$stmt	= $this->database->prepare("SELECT id FROM ".LYCHEE_TABLE_PHOTOS." WHERE album IN (?)");
		$result = $stmt->execute(array($this->albumIDs));

		if ($result === FALSE) {
			Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
			return false;
		}

		# For each album delete photo
		while ($row = $stmt->fetchObject()) {

			$photo = new Photo($this->database, $this->plugins, null, $row->id);
			if (!$photo->delete($row->id)) $error = true;

		}

		# Delete albums
		$stmt	= $this->database->prepare("DELETE FROM ".LYCHEE_TABLE_ALBUMS." WHERE id IN (?)");
		$result = $stmt->execute(array($this->albumIDs));

		# Call plugins
		$this->plugins(__METHOD__, 1, func_get_args());

		if (!$result) {
			Log::error($this->database, __METHOD__, __LINE__, print_r($this->database->errorInfo(), TRUE));
			return false;
		}

		if ($error) return false;

		return true;

	}

}

?>
