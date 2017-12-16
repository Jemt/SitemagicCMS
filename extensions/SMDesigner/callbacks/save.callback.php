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

// Writing data to files.
// Notice: Unicode characters are transformed into HEX entities when retrieved using
// SMEnvironment::GetPostValue(..), and will be shown as such since we are serving
// ISO-8859-1 to the page. Therefore, unicode characters will not work as expected.
// However, once the Designer is loaded, the unicode specific characters are decoded
// by Sitemagic and applied by the Designer, making them re-emerge as real unicode
// characters. This is a bit odd, but there's nothing we can do about it except
// remove the unicode characters which would probably be even worse, since they are
// still valid for e.g. CSS comments: /* example comment */

$writer = new SMTextFileWriter($templatePath . "/override.js", SMTextFileWriteMode::$Overwrite);
$writer->Write($config);
$writer->Close();

$writer = new SMTextFileWriter($templatePath . "/override.css", SMTextFileWriteMode::$Overwrite);
$writer->Write($css);
$writer->Close();

SMEnvironment::UpdateClientCacheKey();

?>
