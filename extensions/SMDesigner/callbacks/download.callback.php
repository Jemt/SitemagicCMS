<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

if (SMAuthentication::Authorized() === false)
	throw new exception("Unauthorized!");

// Helper function that removes subsite portion of a file path

function stripSubsite($path)
{
	$subsite = SMEnvironment::GetSubsiteDirectory();

	if ($subsite !== null && strpos($path, $subsite) === 0)
		return substr($path, strlen($subsite) + 1);
	return $path;

}

// Create function for recursively adding files to ZIP archive

function filesToZip($templatePath, $newTemplatePath, ZipArchive $zipArch = null)
{
	SMTypeCheck::CheckObject(__METHOD__, "templatePath", $templatePath, SMTypeCheckType::$String);
	SMTypeCheck::CheckObject(__METHOD__, "newTemplatePath", $newTemplatePath, SMTypeCheckType::$String);

	$zip = null;
	$downloadPath = null;

	// Create ZIP archive

	if ($zipArch === null)
	{
		$templateName = substr($newTemplatePath, strrpos($newTemplatePath, "/") + 1);
		$downloadPath = SMEnvironment::GetFilesDirectory() . "/SitemagicTemplate_" . $templateName . ".zip";

		$zip = new ZipArchive();
		$res = $zip->open($downloadPath, ZipArchive::CREATE);

		if ($res !== true)
			throw new exception("Unable to create temporary ZIP file - error code: " . $res);
	}
	else
	{
		$zip = $zipArch;
	}

	// Add files

	foreach (SMFileSystem::GetFiles($templatePath) as $file)
	{
		if ($file !== "override.defaults.js")
			$zip->addFile($templatePath . "/" . $file, stripSubsite($newTemplatePath . "/" . $file));

		if ($file === "override.js")
			$zip->addFile($templatePath . "/override.js", stripSubsite($newTemplatePath . "/override.defaults.js"));
	}

	// Add files in folders

	foreach (SMFileSystem::GetFolders($templatePath) as $subDir)
	{
		filesToZip($templatePath . "/" . $subDir, $newTemplatePath . "/" . $subDir, $zip);
	}

	// Finalize ZIP archive

	if ($zipArch === null) // Runs only on initial call
	{
		// Add images from files folder - read file paths from style.css and override.css

		$processed = array();
		$filesDirectoryName = stripSubsite(SMEnvironment::GetFilesDirectory()); // For subsites: sites/demo/files => files
		$imagePaths = null;

		foreach (array("style.css", "override.css") as $cssFile)
		{
			$reader = new SMTextFileReader($templatePath . "/" . $cssFile);
			$content = $reader->ReadAll();

			// Extract images using Regular Expression.
			// NOTICE: Also include images that have been overridden, and is no longer in use in the design.
			// RegEx example: https://regex101.com/r/vM3nE3/5 : [\"|\'].*files\/(images\/.+\..{3,4})[\"|\']
			// PREG_OFFSET_CAPTURE: $imagePaths[0] = array of full matches, $imagePaths[1] = array of capture groups
			$imagePaths = array();
			preg_match_all("/[\\\"|\\'].*" . $filesDirectoryName . "\\/(images\\/.+\\..{3,4})[\\\"|\\']/iU", $content, $imagePaths, PREG_OFFSET_CAPTURE);

			foreach ($imagePaths[1] as $imgPath) // $imgPath[0] = file path match (string), $imgPath[1] = position (integer)
			{
				if (in_array($imgPath[0], $processed) === true)
					continue; // Skip image, already added

				$processed[] = $imgPath[0];

				if (SMFileSystem::FileExists(SMEnvironment::GetFilesDirectory() . "/" . $imgPath[0]) === true) // Image may be referenced but no longer exist
					$zip->addFile(SMEnvironment::GetFilesDirectory() . "/" . $imgPath[0], stripSubsite(SMEnvironment::GetFilesDirectory() . "/" . $imgPath[0]));
			}
		}

		// Close ZIP archive and return path to ZIP file

		$result = $zip->close();

		if ($result !== true)
			throw new exception("Unable to save files to temporary ZIP file");
	}

	return $downloadPath;
}

// Read query string parameter

$templatePath = SMEnvironment::GetQueryValue("TemplatePath", SMValueRestriction::$SafePath);	 // e.g. templates/Sunrise (from Query String)
$templateName = SMEnvironment::GetQueryValue("TemplateName", SMValueRestriction::$AlphaNumeric); // e.g. SummerTime (from Query String)

if ($templatePath === null || $templateName === null)
	throw new exception("Unexpected error - TemplatePath and TemplateName must be provided");

// Create ZIP file on server

$newTemplatePath = substr($templatePath, 0, strrpos($templatePath, "/")) . "/" . $templateName; // Becomes e.g. templates/SummerTime
$downloadPath = filesToZip($templatePath, $newTemplatePath);

// Download ZIP file to client and remove ZIP file from server

SMFileSystem::DownloadFileToClient($downloadPath, true); // true = proceed with normal execution, rather than terminating process (file is deleted below)
SMFileSystem::Delete($downloadPath);

?>
