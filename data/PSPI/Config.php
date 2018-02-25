<?php

// This file is responsible for contructing the $config array for PSPI.
// The file is dynamically loaded by PSPI when needed. The code below
// is used to construct the necessary configuration.

require_once(dirname(__FILE__) . "/BaseUrl.php"); // defines $smShopPspiBaseUrl

$baseUrl = null;

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

		$writer = new SMTextFileWriter(dirname(__FILE__) . "/BaseUrl.php", SMTextFileWriteMode::$Overwrite);
		$writer->Write("<?php \$smShopPspiBaseUrl = \"" . $baseUrl . "\"; ?>");
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
	"EncryptionKey"		=> "fU7#24d/81LGf-f4dy@y0=-Ir9it2h-78823HK367?_1gu7YW38yHEptG'.218_!IHU8g",
	"BaseUrl"			=> $baseUrl // "http://domain.com/extensions/SMShop/PSPI"
);

?>
