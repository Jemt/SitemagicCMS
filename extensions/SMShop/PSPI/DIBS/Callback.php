<?php

// This file is responsible for receiving the response from DIBS
// and forward it to the application in a standardized way.

require_once(dirname(__FILE__) . "/../PSPInterface.php"); // Callback is called directly by Payment Service Provider, so we need to include PSPInterface.php

if ($_SERVER["REMOTE_ADDR"] === "85.236.67.1") // Handle Server-To-Server Callback - IP found in documentation under "CallbackUrl": http://tech.dibspayment.com/D2/Hosted/Output_parameters/Return_pages
{
	$name = basename(getcwd());
	$config = PSP::GetConfig($name);

	// Checksum (if keys are configured)

	$checksum = "";

	if (isset($config["Encryption Key 1"]) && $config["Encryption Key 1"] !== "" && isset($config["Encryption Key 2"]) && $config["Encryption Key 2"] !== "")
	{
		$k1 = $config["Encryption Key 1"];
		$k2 = $config["Encryption Key 2"];

		$checksum = md5($k2 . md5($k1 . "transact=" . $_POST["transact"] . "&amount=" . $_POST["amount"] . "&currency=" . $_POST["currency"]));

		if ($checksum !== $_POST["authkey"])
			throw new Exception("SecurityException: Integrity check failed - mismatching checksums (" . $name . ")");
	}

	// Invoke applicaton callback specified in RedirectToPaymentForm(..).
	// Using PSP::InvokeCallback(..) which implements security measures
	// to prevent man-in-the-middle attacks.

	PSP::Log($name . " - invoking callback:\nTransactionId: " . $_POST["transact"] . "\nOrderId: " . $_POST["orderid"] . "\nAmount: " . $_POST["amount"] . "\nCurrency: " . $_POST["currency"]);

	PSP::InvokeCallback($_POST["CUSTOM_Callback"], $_POST["transact"] . ";" . $_POST["orderid"], $_POST["orderid"], (int)$_POST["amount"], $_POST["currency"]);
}
else if (isset($_POST["CUSTOM_ContinueUrl"]) === true) // Handle Continue URL
{
	PSP::RedirectToContinueUrl($_POST["CUSTOM_ContinueUrl"]);
}

?>
