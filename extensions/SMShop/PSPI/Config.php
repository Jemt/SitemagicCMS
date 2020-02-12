<?php

/*$config = array
(
	// Defined by Payment Service Provider standard.
	// Information MUST be supplied by application using PSP interface.

	"EncryptionKey"		=> "-3%8h7_8//snm.Gw3bDFU#@G6.723#pma53/YG821jv",	// String: Random value used to encrypt data to prevent man-in-the-middle attacks
	"BaseUrl"			=> "http://example.com/libs/PSPI",					// String: External URL to folder containing PSPI package (e.g. http://example.com/libs/PSPI)
	"LogFile"			=> "../../logs/PSPI.log",							// String: Path to log file (relative to application or absolute if starting with /) - leave empty to disable logging
	"LogMode"			=> "Simple",										// String: Log mode - possible values are: Disabled, Simple, or Full (WARNING: Log may contain sensitive information!)
	"TestMode"			=> true												// Boolean: Set True to switch to test mode - this usually puts Payment Service Provider Modules in test mode to make sure no money will be charged when testing
);*/

$path = $_SERVER["SCRIPT_FILENAME"];				// E.g. /var/www/domain.com/web/Sitemagic/index.php - opposite to DOCUMENT_ROOT this value will always contain the name of the (sub-) folder containing the file
$path = str_replace("\\", "/", $path);				// In case backslashes are used on Windows Server
$path = substr($path, 0, strrpos($path, "/") + 1);	// Remove filename (e.g. index.php) - result e.g. /var/www/domain.com/web/Sitemagic/ or /var/www/domain.com/web/Sitemagic/sites/demo/

$smContext = (class_exists("SMController") === true && class_exists("SMEnvironment") === true && class_exists("SMTextFileWriter") === true); // isset($_GET["SMExt"]) === true

if ($smContext === false) // False when not executed in the context of Sitemagic CMS (e.g. if required from extensions/SMShop/PSPI/QuickPay/Callback.php when requested directly by Payment Service Provider)
{
	$path = $path . "../../../../"; // Move path back to website's document root (e.g. /var/www/domain.com/web/ or /var/www/domain.com/web/sites/demo/)

	$matches = array();
	$matchCount = preg_match('/^\/(sites\/.+?)\//', $_SERVER["REQUEST_URI"], $matches); // 0 = full match, 1 = capture group (e.g. sites/demo)

	if ($matchCount === 1)
	{
		// This is a subsite. In this case the request URL is something like https://server/sites/demo/extensions/SMShop/PSPI/QuickPay/Callback.php
		// URL rewriting makes sure this works since a subsite does not actually have an extensions folder).

		$path .= $matches[1] . "/"; // Add subsite portion to path (e.g. sites/demo)
	}
}

$path .= "data/PSPI";

$config = array
(
	"ConfigPath" => $path // Path to alternative config folder (relative to application or absolute if starting with /)
);

?>
