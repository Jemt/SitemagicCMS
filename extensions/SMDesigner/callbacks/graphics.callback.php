<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

if (SMAuthentication::Authorized() === false)
	throw new exception("Unauthorized!");

// Determine function to execute

$func = SMEnvironment::GetPostValue("Function");

if ($func === "Save") // Save PNG image based on Base64 encoded data
{
	$base64 = SMEnvironment::GetPostValue("Base64");
	$destination = SMEnvironment::GetPostValue("Destination", SMValueRestriction::$SafePath);
	$quality = SMEnvironment::GetPostValue("Quality", SMValueRestriction::$Numeric);

	if ($base64 !== null && $destination !== null && $quality !== null)
	{
		// Remove file extension if included

		if (strpos($destination, ".") !== false)
			$destination = substr($destination, 0, strrpos($destination, "."));

		// Make sure folder and files are writable

		$folder = substr($destination, 0, strrpos($destination, "/"));

		if (((SMFileSystem::FileExists($destination . ".png") === false || SMFileSystem::FileExists($destination . ".jpg") === false) && SMFileSystem::FolderIsWritable($folder) === false)
			|| (SMFileSystem::FileExists($destination . ".png") === true && SMFileSystem::FileIsWritable($destination . ".png") === false)
			|| (SMFileSystem::FileExists($destination . ".jpg") === true && SMFileSystem::FileIsWritable($destination . ".jpg") === false))
		{
			echo "Error - '" . $folder . "', '" . $destination . ".png', or '" . $destination . ".jpg' is write protected";
			exit;
		}

		// Write PNG image data to image file

		$base64 = str_replace("data:image/png;base64,", "", $base64);
		$base64decoded = base64_decode($base64); // May fail for large amounts of data

		$writer = new SMTextFileWriter($destination . ".png", SMTextFileWriteMode::$Overwrite);
		$writer->Write($base64decoded);
		$writer->Close();

		// Convert PNG to JPG for better compression

		// Load PNG image to convert
		$image = imagecreatefrompng($destination . ".png");

		// Create white background
		$bg = imagecreatetruecolor(imagesx($image), imagesy($image));
		imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
		imagealphablending($bg, true);

		// Copy PNG image to white background - transparent areas become white
		imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
		imagedestroy($image);

		// Save new image as JPG
		imagejpeg($bg, $destination . ".jpg", (int)$quality);
		imagedestroy($bg);

		// Remove PNG image
		SMFileSystem::Delete($destination . ".png"); // Folder must be writable for clean-up to work

		// Send back new filename

		echo $destination . ".jpg";
		exit;
	}
}

if ($func === "Scale") // Scale image
{
	$scale = SMEnvironment::GetPostValue("Scale", SMValueRestriction::$NumericDecimal);
	$source = SMEnvironment::GetPostValue("Source", SMValueRestriction::$SafePath);
	$target = SMEnvironment::GetPostValue("Target", SMValueRestriction::$SafePath);
	$quality = SMEnvironment::GetPostValue("Quality", SMValueRestriction::$Numeric);

	if ($scale !== null && $source !== null && $target !== null && $quality !== null)
	{
		// Make sure source exists

		if (SMFileSystem::FileExists($source) === false)
		{
			echo "Error - '" . $source . "' does not exist";
			exit;
		}

		// Make sure target can be written

		$folder = substr($target, 0, strrpos($target, "/"));

		if ((SMFileSystem::FileExists($target) === true && SMFileSystem::FileIsWritable($target) === false)
			|| (SMFileSystem::FileExists($target) === false && SMFileSystem::FolderIsWritable($folder) === false))
		{
			echo "Error - '" . $target . "' cannot be created due to insufficient write permissions";
			exit;
		}

		// Determine current and new dimensions

		$image = imagecreatefromjpeg($source);

		$width = imagesx($image);
		$height = imagesy($image);
		$newWidth = (int)floor($width * (float)$scale);   // Notice: floor(..) returns a float which is why we cast to integer
		$newHeight = (int)floor($height * (float)$scale); // Notice: floor(..) returns a float which is why we cast to integer

		// Create new image canvas and copy original image to canvas with new dimensions

		$newImg = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($newImg, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height); // imagecopyresampled results in higher quality than imagecopyresized

		// Save new image

		imagejpeg($newImg, $target, (int)$quality);

		// Free memory

		imagedestroy($newImg);
		imagedestroy($image);

		// Send back new filename

		echo $target;
		exit;
	}
}

if ($func === "Move") // Move or rename image
{
	$moveFrom = SMEnvironment::GetPostValue("MoveFrom", SMValueRestriction::$SafePath);
	$moveTo = SMEnvironment::GetPostValue("MoveTo", SMValueRestriction::$SafePath);

	if ($moveFrom !== null && $moveTo !== null)
	{
		// Make sure file exists

		if (SMFileSystem::FileExists($moveFrom) === false)
		{
			echo "Error - '" . $moveFrom . "' does not exist";
			exit;
		}

		// Check permissions
		// Notice: Write permissions on files does not matter when moving/renaming, only folder permissions matter!

		$folderFrom = substr($moveFrom, 0, strrpos($moveFrom, "/"));
		$folderTo = substr($moveTo, 0, strrpos($moveTo, "/"));

		$folderFrom = (($folderFrom !== "") ? $folderFrom : ".");
		$folderTo = (($folderTo !== "") ? $folderTo : ".");

		if (SMFileSystem::FolderIsWritable($folderFrom) === false || SMFileSystem::FolderIsWritable($folderTo) === false)
		{
			echo "Error - unable to move '" . $moveFrom . "' to '" . $moveTo . "' due to insufficient write permissions on '" . $folderFrom . "'" . (($folderFrom !== $folderTo) ? " or '" . $folderTo . "'" : "");
			exit;
		}

		// Move/rename file

		SMFileSystem::Move($moveFrom, $moveTo); // Only folder permissions are relevant - file permissions are ignored when moving/renaming, as it is considered a change to the folder structure

		// Send back new filename

		echo $moveTo;
		exit;
	}
}

echo "ERROR";

?>
