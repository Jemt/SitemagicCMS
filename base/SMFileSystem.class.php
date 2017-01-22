<?php

/// <container name="base/SMFileSystem">
/// 	SMFileSystem provides access to most common file system operations.
/// 	Easily read, move, rename, copy, or delete files and folders. Query file information
/// 	such as size, modification time and whether a file or folder is readable and writable.
/// 	Handle file uploads and serve files to clients as downloads.
///
/// 	$imageDir = SMEnvironment::GetFilesDirectory() . "/images";
/// 	$oldDir = SMEnvironment::GetFilesDirectory() . "/images/Old";
///
/// 	// Create Old folder if it does not exist
/// 	if (SMFileSystem::FolderExists($oldDir) === false) SMFileSystem::CreateFolder($oldDir);
///
/// 	// Move all image files to Old folder
/// 	$files = SMFileSystem::GetFiles($imageDir);
/// 	foreach ($files as $file) SMFileSystem::Move($imageDir . "/" . $file, $oldDir . "/" . $file);
///
/// 	// Create backup of downloads folder
/// 	$downloadsDir = SMEnvironment::GetFilesDirectory() . "/downloads";
/// 	SMFileSystem::Copy($downloadsDir, $downloadsDir . "_backup", true);
///
/// 	// Send file to client browser
/// 	SMFileSystem::DownloadFileToClient($downloadsDir . "/Events.pdf");
/// </container>
class SMFileSystem
{
	private function __construct()
	{
	}

	/// <function container="base/SMFileSystem" name="CreateFolder" access="public" static="true" returns="boolean">
	/// 	<description>
	/// 		Create new folder.
	/// 		Folder is created with highest permission set possible (inherited from parent).
	/// 		Returns True on success, otherwise False.
	/// 	</description>
	/// 	<param name="path" type="string"> Path to new folder </param>
	/// </function>
	public static function CreateFolder($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		$path = utf8_encode($path);
		return @mkdir($path, 0777, true); // Created with highest possible permissions allowed by parent folder
	}

	/// <function container="base/SMFileSystem" name="Copy" access="public" static="true" returns="boolean">
	/// 	<description> Copy a file or folder - returns True on success, otherwise False </description>
	/// 	<param name="source" type="string"> Path to folder or file to copy </param>
	/// 	<param name="destination" type="string"> Path to target folder or target file </param>
	/// 	<param name="overwriteExisting" type="boolean" default="false">
	/// 		Value indicating whether to overwrite existing files or not.
	/// 		A value of False will cause function to return False if trying to copy a file to an existing
	/// 		file, while for directory copy it will simply skip files already found in destination folder.
	/// 		A value of True will cause files to be overwritten.
	/// 	</param>
	/// </function>
	public static function Copy($source, $destination, $overwriteExisting = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "source", $source, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "destination", $destination, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "overwriteExisting", $overwriteExisting, SMTypeCheckType::$Boolean);

		// Remove trailing slash if found - e.g. my/folder/  =>  my/folder

		$source = trim($source);
		$source = ((substr($source, strlen($source) - 1) === "/") ? substr($source, 0, strlen($source) - 1) : $source);
		$source = (($source !== "") ? $source : "."); // Empty means script root

		$destination = trim($destination);
		$destination = ((substr($destination, strlen($destination) - 1) === "/") ? substr($destination, 0, strlen($destination) - 1) : $destination);
		$destination = (($destination !== "") ? $destination : "."); // Empty means script root

		// Copy

		$sourceUtf8 = utf8_encode($source);
		$destinationUtf8 = utf8_encode($destination);

		if ($overwriteExisting === false && is_file($destinationUtf8) === true)
			return false;

		if (is_file($sourceUtf8) === true)
		{
			// Make sure destination directory exists

			$folderPath = ((strpos($destination, "/") !== false) ? substr($destination, 0, strrpos($destination, "/")) : ""); // Get target folder (remove filename). No slash(es) in destination, then copy file to script root.
			$folderPathUtf8 = utf8_encode($folderPath);

			if ($folderPath !== "" && is_dir($folderPathUtf8) === false) // $folderPath === "" means script root (which already exists)
			{
				$result = mkdir($folderPathUtf8, 0777, true);

				if ($result === false)
					return false;
			}

			// Copy file

			return copy($sourceUtf8, $destinationUtf8); // May return False if $destination points to an existing directory - in this case a filename must be provided; folder/file[.ext]
		}
		else if (is_dir($sourceUtf8) === true)
		{
			return self::copyResursively($source, $destination, $overwriteExisting);
		}

		return false;
	}

	private static function copyResursively($sourceFolder, $destinationFolder, $overwriteExisting)
	{
		SMTypeCheck::CheckObject(__METHOD__, "sourceFolder", $sourceFolder, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "destinationFolder", $destinationFolder, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "overwriteExisting", $overwriteExisting, SMTypeCheckType::$Boolean);

		$result = false;

		$sourceFolderUtf8 = utf8_encode($sourceFolder);
		$destinationFolderUtf8 = utf8_encode($destinationFolder);

		// Make sure destination folder exists

		if (is_dir($destinationFolderUtf8) === false)
		{
			$result = mkdir($destinationFolderUtf8, 0777, true);

			if ($result === false)
				return false;
		}

		// Copy files to destination

		$files = self::GetFiles($sourceFolder);
		$fileUtf8 = "";

		foreach ($files as $file)
		{
			$fileUtf8 = utf8_encode($file);

			if ($overwriteExisting === true || is_file($destinationFolderUtf8 . "/" . $fileUtf8) === false)
			{
				$result = copy($sourceFolderUtf8 . "/" . $fileUtf8, $destinationFolderUtf8 . "/" . $fileUtf8); // Will fail if file is copied to itself

				if ($result === false)
					return false;
			}
		}

		// Copy sub folders to destination

		$folders = self::GetFolders($sourceFolder);
		$folderUtf8 = "";

		foreach ($folders as $folder)
		{
			$folderUtf8 = utf8_encode($folder);

			// Avoid infinite recursive loop if user copies e.g. files/images to files/images/backup.
			// PHP function realpath(..) is used to make sure the two paths are comparable if /../ is used in $sourceFolder:
			// files/images/../images/backup === files/images/backup
			// Now realpath(..) will translate both of these to something like:
			// /var/www/domain/files/images/backup
			if (realpath($sourceFolderUtf8 . "/" . $folderUtf8) === realpath($destinationFolderUtf8))
				continue;

			$result = self::copyResursively($sourceFolder . "/" . $folder, $destinationFolder . "/" . $folder, $overwriteExisting);

			if ($result === false)
				return false;
		}

		return true;
	}

	/// <function container="base/SMFileSystem" name="Move" access="public" static="true" returns="boolean">
	/// 	<description> Move or rename a file or folder - returns True on success, otherwise False </description>
	/// 	<param name="path" type="string"> Path to item to move or rename (e.g. files/images/summer)</param>
	/// 	<param name="newPath" type="string"> New path (e.g. files/gallery/summer)</param>
	/// </function>
	public static function Move($path, $newPath) // Also used to rename
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "newPath", $newPath, SMTypeCheckType::$String);

		$pathUtf8 = utf8_encode($path);
		$newPathUtf8 = utf8_encode($newPath);

		return rename($pathUtf8, $newPathUtf8);
	}

	/// <function container="base/SMFileSystem" name="Delete" access="public" static="true" returns="boolean">
	/// 	<description> Delete a file or folder - returns True on success, otherwise False </description>
	/// 	<param name="path" type="string"> Path to item to delete </param>
	/// 	<param name="force" type="boolean" default="false">
	/// 		Set True to force deletion of a folder, meaning all contained
	/// 		files and sub folders are removed first. A folder containing
	/// 		files cannot be removed unless this parameter has been set True.
	/// 		The force parameter is ignored when deleting a file.
	/// 	</param>
	/// </function>
	public static function Delete($path, $force = false) // $force only used when deleting folders
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "force", $force, SMTypeCheckType::$Boolean);

		if ($path === "") // Empty means script root
			$path = ".";

		$pathUtf8 = utf8_encode($path);

		if (is_file($pathUtf8) === true)
		{
			return unlink($pathUtf8);
		}
		else if (is_dir($pathUtf8) === true)
		{
			if ($force === true)
				return self::deleteFolderRecursively($path);
			else
				return rmdir($pathUtf8);
		}

		return false;
	}

	private static function deleteFolderRecursively($folderPath)
	{
		SMTypeCheck::CheckObject(__METHOD__, "folderPath", $folderPath, SMTypeCheckType::$String);

		$items = null;

		try
		{
			$items = self::GetFilesAndFolders($folderPath);
		}
		catch (Exception $ex)
		{
			return false;
		}

		$itemUtf8 = "";
		$result = true;

		foreach ($items as $item)
		{
			if ($item === "." || $item === "..")
				continue;

			$item = $folderPath . "/" . $item;
			$itemUtf8 = utf8_encode($item);

			if (is_dir($itemUtf8) === true)
				$result = self::deleteFolderRecursively($item);
			else if (is_file($itemUtf8) === true)
				$result = unlink($itemUtf8);

			if ($result === false)
				return false;
		}

		$folderPathUtf8 = utf8_encode($folderPath);
		return rmdir($folderPathUtf8);
	}

	/// <function container="base/SMFileSystem" name="GetFileSize" access="public" static="true" returns="integer">
	/// 	<description> Get file size in bytes - value -1 is returned in case of errors </description>
	/// 	<param name="path" type="string"> Path to file </param>
	/// </function>
	public static function GetFileSize($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		$pathUtf8 = utf8_encode($path);
		$bytes = filesize($pathUtf8);

		if ($bytes === false)
			return -1;

		return $bytes;
	}

	/// <function container="base/SMFileSystem" name="GetFileModificationTime" access="public" static="true" returns="integer">
	/// 	<description> Get file modification time as unix timestamp - value -1 is returned in case of errors </description>
	/// 	<param name="path" type="string"> Path to file </param>
	/// </function>
	public static function GetFileModificationTime($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		$pathUtf8 = utf8_encode($path);
		$timestamp = filemtime($pathUtf8);

		if ($timestamp === false)
			return -1;

		return $timestamp;
	}

	/// <function container="base/SMFileSystem" name="GetFiles" access="public" static="true" returns="string[]">
	/// 	<description> Get names of files contained in specified folder </description>
	/// 	<param name="path" type="string"> Path to folder containing files </param>
	/// </function>
	public static function GetFiles($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		if ($path === "") // Empty means script root
			$path = ".";

		$pathUtf8 = utf8_encode($path);
		$fsItems = scandir($pathUtf8);

		if ($fsItems === false)
			throw new Exception("Unable to read files from specified path '" . $path . "'");

		$files = array();

		foreach ($fsItems as $fsItem)
			if (is_file($pathUtf8 . "/" . $fsItem) === true)
				$files[] = utf8_decode($fsItem);

		return $files;
	}

	/// <function container="base/SMFileSystem" name="GetFolders" access="public" static="true" returns="string[]">
	/// 	<description> Get names of sub folders contained in specified folder </description>
	/// 	<param name="path" type="string"> Path to folder containing sub folders </param>
	/// </function>
	public static function GetFolders($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		if ($path === "") // Empty means script root
			$path = ".";

		$pathUtf8 = utf8_encode($path);
		$fsItems = scandir($pathUtf8);

		if ($fsItems === false)
			throw new Exception("Unable to read folders from specified path '" . $path . "'");

		$directories = array();

		foreach ($fsItems as $fsItem)
			if ($fsItem !== "." && $fsItem !== ".." && is_dir($pathUtf8 . "/" . $fsItem) === true)
				$directories[] = utf8_decode($fsItem);

		return $directories;
	}

	/// <function container="base/SMFileSystem" name="GetFilesAndFolders" access="public" static="true" returns="string[]">
	/// 	<description> Get names of both sub folders and files contained in specified folder </description>
	/// 	<param name="path" type="string"> Path to folder containing sub folders and files </param>
	/// </function>
	public static function GetFilesAndFolders($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		if ($path === "") // Empty means script root
			$path = ".";

		$pathUtf8 = utf8_encode($path);
		$fsItems = scandir($pathUtf8);

		if ($fsItems === false)
			throw new Exception("Unable to read items from specified path '" . $path . "'");

		$items = array();

		foreach ($fsItems as $fsItem)
			if ($fsItem !== "." && $fsItem !== "..")
				$items[] = utf8_decode($fsItem);

		return $items;
	}

	/// <function container="base/SMFileSystem" name="HandleFileUpload" access="public" static="true" returns="boolean">
	/// 	<description> Receive and store a file uploaded client side </description>
	/// 	<param name="fileField" type="string"> Name of file picker element in web form </param>
	/// 	<param name="moveToFolder" type="string"> Specify where to store received file (folder path must exist) </param>
	/// 	<param name="filenameRegEx" type="string" default="String.Empty"> Optional regular expression which filename characters are matched agains - invalid characters are stripped from filename </param>
	/// 	<param name="validExtensions" type="string[]" default="string[0]"> Array of valid file extensions, e.g. array('jpg', 'jpeg', 'png', 'gif') </param>
	/// </function>
	public static function HandleFileUpload($fileField, $moveToFolder, $filenameRegEx = "", $validExtensions = array())
	{
		SMTypeCheck::CheckObject(__METHOD__, "fileField", $fileField, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "moveToFolder", $moveToFolder, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "filenameRegEx", $filenameRegEx, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "validExtensions", $validExtensions, SMTypeCheckType::$String);

		if (isset($_FILES[$fileField]) === false || $_FILES[$fileField]["error"] !== 0 || $_FILES[$fileField]["name"] === "")
			return false;

		if ($moveToFolder === "") // Empty means script root
			$moveToFolder = ".";

		$filename = $_FILES[$fileField]["name"];

		// Check extensions - make sure only valid file types are uploaded

		if (count($validExtensions) > 0)
		{
			if (strpos($filename, ".") === false)
				return false;

			$fileExt = strtolower(substr($filename, strrpos($filename, ".")));
			$isValid = false;

			foreach ($validExtensions as $ext)
			{
				if ($fileExt === strtolower("." . $ext))
				{
					$isValid = true;
					break;
				}
			}

			if ($isValid === false)
				return false;
		}

		// Make sure only valid characters are preserved in filename

		$newFilename = "";

		if ($filenameRegEx !== "")
		{
			$char = "";

			for ($i = 0 ; $i < strlen($filename) ; $i++)
			{
				$char = substr($filename, $i, 1);

				if (preg_match($filenameRegEx, $char) === 0)
					continue;

				$newFilename .= $char;
			}
		}
		else
		{
			$newFilename = $filename;
		}

		$newFilename = trim($newFilename);

		// Create unique filename if file already exists

		$moveToFolderUtf8 = utf8_encode($moveToFolder);
		$newFilenameUtf8 = utf8_encode($newFilename);

		if (file_exists($moveToFolderUtf8 . "/" . $newFilenameUtf8) === true)
		{
			$dotPos = strrpos($newFilename, ".");

			if ($dotPos !== false)
			{
				$tmp = substr($newFilename, 0, $dotPos);
				$tmp .= "_" . time();
				$tmp .= substr($newFilename, $dotPos);

				$newFilename = $tmp;
			}
			else
			{
				$newFilename .= "_" . time();
			}

			$newFilenameUtf8 = utf8_encode($newFilename);
		}

		// Move file from temp directory to target directory

		return move_uploaded_file($_FILES[$fileField]["tmp_name"], $moveToFolderUtf8 . "/" . $newFilenameUtf8);
	}

	/// <function container="base/SMFileSystem" name="GetUploadPath" access="public" static="true" returns="string">
	/// 	<description> Get temporarily path to file uploaded during current request - Null is returned in case of an error or if upload field does not exist </description>
	/// 	<param name="fileField" type="string">
	/// 		Unique name for file upload field.
	/// 		If the GUI framework in Sitemagic is
	/// 		being used, simply pass $input->GetClientId(),
	/// 		where $input is an instance of SMInput.
	/// 	</param>
	/// </function>
	public static function GetUploadPath($fileField)
	{
		SMTypeCheck::CheckObject(__METHOD__, "fileField", $fileField, SMTypeCheckType::$String);

		if (isset($_FILES[$fileField]) === false || $_FILES[$fileField]["error"] !== 0 || $_FILES[$fileField]["name"] === "")
			return null;

		return $_FILES[$fileField]["tmp_name"];
	}

	/// <function container="base/SMFileSystem" name="DownloadFileToClient" access="public" static="true">
	/// 	<description> Send specified file as a download to client. Function stops further code execution. </description>
	/// 	<param name="filePath" type="string"> Path to file to send to client </param>
	/// 	<param name="continueExecution" type="boolean" default="false">
	/// 		Set True to continue normal execution when file has been served. This might cause errors like &quot;Headers already sent&quot;.
	/// 	</param>
	/// </function>
	public static function DownloadFileToClient($filePath, $continueExecution = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "filePath", $filePath, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "continueExecution", $continueExecution, SMTypeCheckType::$Boolean);

		$pathInfo = explode("/", $filePath);
		$filename = $pathInfo[count($pathInfo) - 1];

		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
		header("Content-Type: application/unknown");
		header("Content-Transfer-Encoding: binary");

		$filePathUtf8 = utf8_encode($filePath);
		$res = readfile($filePathUtf8); // Write to output buffer - $res contains number of bytes written

		if ($res === false)
			throw new Exception("Unable to serve file '" . $filePath . "' to client - error occured");

		if ($continueExecution === false)
			exit; // Avoid further processing and potentially "Headers already sent" error
	}

	/// <function container="base/SMFileSystem" name="FolderExists" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if specified folder exists, otherwise False </description>
	/// 	<param name="path" type="string"> Path to folder </param>
	/// </function>
	public static function FolderExists($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		if ($path === "") // Empty means script root
			$path = ".";

		$pathUtf8 = utf8_encode($path);
		return is_dir($pathUtf8);
	}

	/// <function container="base/SMFileSystem" name="FileExists" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if specified file exists, otherwise False </description>
	/// 	<param name="path" type="string"> Path to file </param>
	/// </function>
	public static function FileExists($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		$pathUtf8 = utf8_encode($path);
		return is_file($pathUtf8);
	}

	/// <function container="base/SMFileSystem" name="FileIsWritable" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if specified file is writable, otherwise False </description>
	/// 	<param name="path" type="string"> Path to file </param>
	/// </function>
	public static function FileIsWritable($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		$pathUtf8 = utf8_encode($path);
		return (is_file($pathUtf8) === true && is_writable($pathUtf8) === true);
	}

	/// <function container="base/SMFileSystem" name="FolderIsWritable" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if specified folder is writable, otherwise False </description>
	/// 	<param name="path" type="string"> Path to folder </param>
	/// </function>
	public static function FolderIsWritable($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		if ($path === "") // Empty means script root
			$path = ".";

		$pathUtf8 = utf8_encode($path);
		return (is_dir($pathUtf8) === true && is_writable($pathUtf8) === true);
	}

	/// <function container="base/SMFileSystem" name="FileIsReadable" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if specified file is readable, otherwise False </description>
	/// 	<param name="path" type="string"> Path to file </param>
	/// </function>
	public static function FileIsReadable($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		$pathUtf8 = utf8_encode($path);
		return (is_file($pathUtf8) === true && is_readable($pathUtf8) === true);
	}

	/// <function container="base/SMFileSystem" name="FolderIsReadable" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if specified folder is readable, otherwise False </description>
	/// 	<param name="path" type="string"> Path to folder </param>
	/// </function>
	public static function FolderIsReadable($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		if ($path === "") // Empty means script root
			$path = ".";

		$pathUtf8 = utf8_encode($path);
		return (is_dir($pathUtf8) === true && is_readable($pathUtf8) === true);
	}
}

?>
