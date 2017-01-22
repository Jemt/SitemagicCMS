<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

if (SMAuthentication::Authorized() === false)
	throw new exception("Unauthorized!");

// Write template design configuration (JSON)

$config = SMEnvironment::GetPostValue("Config");	// JSON
$css = SMEnvironment::GetPostValue("Css");			// CSS
$templatePath = SMEnvironment::GetPostValue("TemplatePath", SMValueRestriction::$SafePath);

if ($config === null || $css === null || $templatePath === null)
	throw new exception("Unexpected error - Config, Css, and TemplatePath must be provided");

$writer = new SMTextFileWriter($templatePath . "/override.js", SMTextFileWriteMode::$Overwrite);
$writer->Write($config);
$writer->Close();

$writer = new SMTextFileWriter($templatePath . "/override.css", SMTextFileWriteMode::$Overwrite);
$writer->Write($css);
$writer->Close();

SMEnvironment::UpdateClientCacheKey();

?>
