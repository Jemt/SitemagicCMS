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

$config = array
(
	"ConfigPath"		=> "../../../data/PSPI"								// Path to alternative config folder (relative to application or absolute if starting with /)
);

?>
