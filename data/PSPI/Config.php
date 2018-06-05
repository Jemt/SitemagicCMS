<?php

// This file is responsible for contructing the $config array for PSPI.
// The file is dynamically loaded by PSPI when needed. The code below
// is used to construct the necessary configuration.

require_once(dirname(__FILE__) . "/BaseUrl.php"); // defines $smShopPspiBaseUrl and $smShopPspiEncKey

$baseUrl = null;
$encKey = $smShopPspiEncKey;

if (class_exists("SMController") === true && class_exists("SMEnvironment") === true && class_exists("SMTextFileWriter") === true)
{
	// Safe to assume code is running within Sitemagic CMS.
	// PSPI might be used from PSPM callbacks which are not
	// running in the context of Sitemagic CMS (else portion).

	$baseUrl = SMEnvironment::GetExternalUrl() . "/" . SMEnvironment::GetExtensionsDirectory() . "/SMShop/PSPI";

	if ($baseUrl !== $smShopPspiBaseUrl)
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
	// Not running in the context of Sitemagic CMS - use
	// BaseUrl stored in BaseUrl.php which defines $smShopPspiBaseUrl.

	$baseUrl = $smShopPspiBaseUrl;
}

$config = array
(
	// Defined by Payment Service Provider standard.
	// Information MUST be supplied by application using PSP interface.

	"TestMode"			=> true,
	"LogFile"			=> dirname(__FILE__) . "/../PSPI.log",
	"LogMode"			=> "Full",
	"EncryptionKey"		=> $encKey, // Random string
	"BaseUrl"			=> $baseUrl // E.g. "http://domain.com/extensions/SMShop/PSPI"
);

?>
