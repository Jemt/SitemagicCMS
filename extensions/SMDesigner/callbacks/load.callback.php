<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

if (SMAuthentication::Authorized() === false)
	throw new exception("Unauthorized!");

// Load template design configuration (JSON)

$templatePath = SMEnvironment::GetPostValue("TemplatePath", SMValueRestriction::$SafePath);
$loadDefaultOverrides = SMEnvironment::GetPostValue("LoadDefaultOverrides");

if ($templatePath === null)
	throw new exception("Unexpected error - TemplatePath must be provided");

$config = "";

if (SMFileSystem::FileExists($templatePath . "/override" . (($loadDefaultOverrides === "true") ? ".defaults" : "") . ".js") === true)
{
	$reader = new SMTextFileReader($templatePath . "/override" . (($loadDefaultOverrides === "true") ? ".defaults" : "") . ".js");
	$config = $reader->ReadAll($config);
}

echo $config;

?>
