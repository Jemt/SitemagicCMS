<?php

require_once(dirname(__FILE__) . "/../SMCookieConsentStatistics.classes.php");

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

$consentJson = SMEnvironment::GetPostValue("consent"); // E.g. { "Statistics": true, "Marketing": false }, but URL encoded!

if ($consentJson === null)
{
	echo "Error - missing consent information";
	exit;
}

$consent = json_decode(urldecode($consentJson));

foreach ($consent as $key => $value)
{
	SMCookieConsentStatistics::UpdateModuleStats($key, $value);
}

?>
