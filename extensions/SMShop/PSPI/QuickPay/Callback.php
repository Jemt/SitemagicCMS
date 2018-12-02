<?php

// This file is responsible for receiving the response from QuickPay
// and forward it to the application in a standardized way.

require_once(dirname(__FILE__) . "/../PSPInterface.php"); // Callback is called directly by Payment Service Provider, so we need to include PSPInterface.php

$name = basename(getcwd());
$config = PSP::GetConfig($name);

// Function from https://learn.quickpay.net/tech-talk/api/callback/
function sign($base, $private_key)
{
	return hash_hmac("sha256", $base, $private_key);
}

$requestBody = file_get_contents("php://input");
$checksum = sign($requestBody, ((isset($config["Private API key"]) === true) ? $config["Private API key"] : ""));

if (isset($_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"]) === true && $checksum === $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"])
{
	// Request is authenticated

	$data = json_decode($requestBody, true);

	if ($_SERVER["HTTP_QUICKPAY_RESOURCE_TYPE"] === "Payment" && $data["accepted"] === true)
	{
		$operation = $data["operations"][count($data["operations"]) - 1]; // Full history - last entry is the most recent operation that just happend

		if ($operation["type"] === "authorize") // Type is 'capture' if captured from QuickPay Manager and Callback URL has been configured under Settings > Integration
		{
			PSP::Log($name . " - invoking callback:\nTransactionId: " . $data["id"] . "\nOrderId: " . $data["order_id"] . "\nAmount: " . $operation["amount"] . "\nCurrency: " . $data["currency"]);

			PSP::InvokeCallback($data["variables"]["CUSTOM_Callback"], (string)$data["id"], $data["order_id"], (int)$operation["amount"], $data["currency"]);
		}
	}
}
else
{
	// Request is NOT authenticated
	throw new Exception("SecurityException: Integrity check failed - mismatching checksums (" . $name . ")");
}

?>
