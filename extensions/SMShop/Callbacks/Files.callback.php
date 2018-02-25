<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

if (SMAuthentication::Authorized() === false)
	throw new exception("Unauthorized!");

// Parameters

$imagesFolder = SMEnvironment::GetDataDirectory() . "/SMShop";
$command = ((count($_FILES) > 0) ? "Upload" : "Remove");

// Upload file

if ($command === "Upload")
{
	if (isset($_FILES["SelectedFile"]) === false || $_FILES["SelectedFile"]["error"] !== 0 || $_FILES["SelectedFile"]["name"] === "")
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Error";
		exit;
	}

	// File information

	$dir = $imagesFolder;
	$filename = $_FILES["SelectedFile"]["name"];

	// Ensure target folder

	if (SMFileSystem::FolderExists($dir) === false)
	{
		$res = SMFileSystem::CreateFolder($dir);

		if ($res === false)
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "Error - unable to create '" . $dir . "'";
			exit;
		}
	}

	// Determine file extension

	$match = array();
	if (preg_match("/\\.[a-z]{3,4}$/i", $filename, $match) !== 1)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Error - unable to determine file extension";
		exit;
	}

	$extension = $match[0];

	// Determine new filename

	$newFilename = SMRandom::CreateGuid() . $extension;
	while (SMFileSystem::FileExists($dir . "/" . $newFilename) === true) // Should never be true - Guids ought to be unique
		$newFilename = SMRandom::CreateGuid() . $extension;

	// Move file

	if (move_uploaded_file($_FILES["SelectedFile"]["tmp_name"], $dir . "/" . $newFilename) === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Error moving temporary file";
		exit;
	}

	echo $dir . "/" . $newFilename; // Write new filename back to client on success
}
else if ($command === "Remove")
{
	$paths = null;
	$file = SMEnvironment::GetPostValue("File");
	$files = SMEnvironment::GetPostValue("Files");

	if ($file !== null)
		$paths = array($file);
	else if ($files !== null)
		$paths = explode(";", $files);

	if ($paths === null)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Error - unable to remove files - no path(s) given";
		exit;
	}

	foreach ($paths as $path)
	{
		// Make sure $path is a safe path (e.g. does not contain ../../), and make sure the file referenced is found in $imagesFolder
		if (SMStringUtilities::Validate($path, SMValueRestriction::$SafePath) === false || strpos($path, $imagesFolder) !== 0)
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "Error - unsafe path '" . $path . "' detected";
			exit;
		}

		if (SMFileSystem::FileExists($path) === true)
			SMFileSystem::Delete($path);
	}
}

?>
