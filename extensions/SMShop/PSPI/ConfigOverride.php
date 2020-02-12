<?php

// This file is responsible for contructing the $config array for PSPI.
// The file is dynamically loaded by PSPI when needed. The code below
// is used to construct the necessary configuration.

// IMPORTANT:
// This file is copied to e.g. data/PSPI/Config.php or sites/demo/data/PSPI/Config.php.
// Attach to one of these copies to debug the code. Also make sure to have the files
// in these folders upgraded if changes are made to this file!

$baseUrl = null;
$encKey = null;

if (class_exists("SMController") === true && class_exists("SMEnvironment") === true && class_exists("SMTextFileWriter") === true)
{
	// Safe to assume code is running within Sitemagic CMS.
	// PSPI might be used from PSPM callbacks which are not
	// running in the context of Sitemagic CMS (else portion).

	$prevBaseUrl = null;
	if (SMFileSystem::FileExists(dirname(__FILE__) . "/BaseUrl.php") === true)
	{
		require_once(dirname(__FILE__) . "/BaseUrl.php"); // defines $smShopPspiBaseUrl and $smShopPspiEncKey
		$prevBaseUrl = $smShopPspiBaseUrl;
		$encKey = $smShopPspiEncKey;
	}

	$baseUrl = SMEnvironment::GetExternalUrl() . "/" . SMEnvironment::GetExtensionsDirectory() . "/SMShop/PSPI";

	if ($baseUrl !== $prevBaseUrl)
	{
		// Update URL in BaseUrl.php to make sure it is current, in case
		// Sitemagic CMS is moved to a different folder or even a different domain.
		// EncryptionKey is also updated to make sure two installations do not share
		// the same key if copied around.

		$encKey = SMRandom::CreateText(SMRandom::CreateNumber(5, 10)) . SMRandom::CreateGuid() . SMRandom::CreateText(SMRandom::CreateNumber(5, 10));

		$writer = new SMTextFileWriter(dirname(__FILE__) . "/BaseUrl.php", SMTextFileWriteMode::$Overwrite);
		$writer->Write("<?php\n\$smShopPspiBaseUrl = \"" . $baseUrl . "\";\n\$smShopPspiEncKey = \"" . $encKey . "\";\n?>");
		$writer->Close();
	}
}
else
{
	// Not running in the context of Sitemagic CMS - get BaseUrl and EncKey from BaseUrl.php

	require_once(dirname(__FILE__) . "/BaseUrl.php"); // defines $smShopPspiBaseUrl and $smShopPspiEncKey
	$baseUrl = $smShopPspiBaseUrl;
	$encKey = $smShopPspiEncKey;
}

// Make sure configuration path has a valid Unix like format such as /var/www/vhost on Windows
$configPath = dirname(__FILE__);
$configPath = strpos($configPath, ":\\") === 1 ? substr($configPath, 2) : $configPath; // Remove drive letter on Windows, e.g. "C:"
$configPath = str_replace("\\", "/", $configPath); // Replace backslashes with forward slashes on Windows

$config = array
(
	// Defined by Payment Service Provider standard.
	// Information MUST be supplied by application using PSP interface.

	// WARNING! Log file may contain sensitive information if logging is enabled.
	// Do NOT enable logging for a production system and make sure to remove the
	// log file when done debugging!

	"TestMode"			=> false,
	"LogFile"			=> $configPath . "/PSPI.log",
	"LogMode"			=> "Disabled",
	"EncryptionKey"		=> $encKey, // Random string
	"BaseUrl"			=> $baseUrl // E.g. "http://domain.com/extensions/SMShop/PSPI"
);

?>
