<?php

// Configuration required for PSPM to work.
// MUST be defined in an associative array
// called $config, and only contain strings as key/value pairs.
// Application using PSP package may choose to maintain this
// configuration file using an easy-to-use front-end. Therefore,
// make sure to use meaningful keys and example values.

// DIBS Configuration:
// DIBS must be configured to provide callbacks with all
// possible information available about transactions.
//  1) Login to DIBS
//  2) Browse to: Integration > Return values
//  3) Check all options
//  4) Save changes

$config = array
(
	"Merchant ID"			=> "12345678",
	"Encryption Key 1"		=> "!jD84hgGY/_7f5e9lo?dF923D@45v.2D#1",
	"Encryption Key 2"		=> "-/dKKyJ620hpw2KK0!LK28B.S?3:372JF/24G",
	"API User: Username"	=> "ExternalUser",	// Required by Cancel API call
	"API User: Password"	=> "p@ssW0rD"		// Required by Cancel API call
);

?>
