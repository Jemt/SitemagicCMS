<?php

require_once(dirname(__FILE__) . "/../PSPI/PSPInterface.php");
require_once(dirname(__FILE__) . "/DSCallbacks/Order.php"); // Defines SMShopSendMail(..)

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

// Helper function(s)

function getOrder($orderId)
{
	$ds = new SMDataSource("SMShopOrders");
	$orders = $ds->Select("*", "Id = '" . $ds->Escape($orderId) . "'");

	if (count($orders) !== 1) // Unlikely, but could happen if user calls URL with an incorrect OrderId
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Order with ID '" . $orderId . "' could not be found";
		exit;
	}

	return $orders[0];
}

// Payment handling.
// Step 1: User is redirected to payment form.
// Step 2: PSP invokes callback to let us know payment was received.

$operation = SMEnvironment::GetQueryValue("PaymentOperation"); // Valid values: null, Auth, Capture, Cancel, or Invoice

if ($operation === null) // Step 1: Redirect to payment window
{
	$orderId = SMEnvironment::GetQueryValue("OrderId", SMValueRestriction::$Numeric);
	$order = getOrder($orderId);

	if ($order["State"] !== "Initial")
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Order with ID '" . $orderId . "' has already been processed";
		exit;
	}

	$config = new SMConfiguration(SMEnvironment::GetDataDirectory() . "/SMShop/Config.xml.php");

	$amount = (int)round(((float)$order["Price"] + (float)$order["Vat"]) * 100); // Amount in smallest possible unit (e.g. USD 10095 = USD 100.95)
	$currency = $order["Currency"];

	$continueUrl = SMEnvironment::GetExternalUrl();
	$continueUrl .= (($config->GetEntryOrEmpty("ReceiptPage") !== "") ? "/" . $config->GetEntryOrEmpty("ReceiptPage") : "");
	$callbackUrl = SMEnvironment::GetExternalUrl() . "/" . SMExtensionManager::GetCallbackUrl(SMExtensionManager::GetExecutingExtension(), "Callbacks/Payment") . "&PaymentOperation=Auth";

	$p = PSP::GetPaymentProvider($order["PaymentMethod"]);
	$p->RedirectToPaymentForm($orderId, $amount, $currency, $continueUrl, $callbackUrl);
}
else if ($operation === "Auth") // Step 2: Handle response from PSP - Callback invoked through PSPI
{
	// NOTICE:
	// This call might stall for 1-10-20-60-120 seconds if XML Archiving is running (depending
	// on amount of data and processing power of course). Most PSPs need a 200 HTTP response within
	// a limited amount of time (e.g. 15 seconds), otherwise it might retry the call later. Actually
	// it doesn't matter if the PSP gives up after some time - it might log an error in the PSP's control
	// panel, but SMShop have still received the new state, even though the Auth request
	// timed out on the PSP's server. The request still finishes here.
	// But we SHOULD make sure any subsequent attempt does not change the state from
	// e.g. Captured back to Authorized!

	$data = PSP::GetCallbackData(); // Securely obtain data passed to callback

	$transactionId = $data["TransactionId"];	// String
	$orderId = $data["OrderId"];				// String

	$order = getOrder($orderId);

	if ($order["State"] !== "Initial")
	{
		// Skip, order is no longer in Initial state. This may happen if PSP performs this call multiple times,
		// which may happen if SMShop was not able to return a valid response within a certain amount of time,
		// last time this call was made, e.g. due to XML Archiving running simultaneously which locks the SMShopOrders
		// DataSource while running - potentially for a fairly long time. In such case the state is actually updated in
		// SMShop, but the PSP retries the call after some time. If the order has been captured in the
		// meantime, we do not want the PSP to change the state from Captured to Authorized again.

		return;
	}

	$order["TransactionId"] = $transactionId;
	$order["State"] = "Authorized";

	$ds = new SMDataSource("SMShopOrders");

	if ($ds->GetDataSourceType() === SMDataSourceType::$Xml)
		$ds->Lock();

	$ds->Update($order, "Id = '" . $ds->Escape($order["Id"]) . "'");
	$ds->Commit();

	if ($order["PaymentMethod"] !== "")
	{
		SMShopSendMail($order); // Alternatively called from Callbacks/DSCallbacks/Order.php in case SMShopSendConfirmationMail option is set to Immediately
	}
}
else if ($operation === "Capture") // Called from JSShop
{
	if (SMAuthentication::Authorized() === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Unauthorized - unable to capture payment";
		exit;
	}

	$orderId = SMEnvironment::GetPostValue("OrderId", SMValueRestriction::$Numeric);
	$order = getOrder($orderId);

	if ($order["State"] !== "Authorized")
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Order with ID '" . $orderId . "' is not in state 'Authorized'";
		exit;
	}

	$amount = (int)round(((float)$order["Price"] + (float)$order["Vat"]) * 100); // Amount in smallest possible unit (e.g. USD 10095 = USD 100.95)

	$p = PSP::GetPaymentProvider($order["PaymentMethod"]);
	$res = $p->CapturePayment($order["TransactionId"], $amount);

	if ($res === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Order with ID '" . $orderId . "' failed payment operation 'Capture'";
		exit;
	}

	$order["State"] = "Captured";

	$ds = new SMDataSource("SMShopOrders");

	if ($ds->GetDataSourceType() === SMDataSourceType::$Xml)
		$ds->Lock();

	$ds->Update($order, "Id = '" . $ds->Escape($order["Id"]) . "'");
	$ds->Commit();
}
else if ($operation === "Cancel") // Called from JSShop
{
	if (SMAuthentication::Authorized() === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Unauthorized - unable to cancel payment";
		exit;
	}

	$orderId = SMEnvironment::GetPostValue("OrderId", SMValueRestriction::$Numeric);
	$order = getOrder($orderId);

	if ($order["State"] !== "Authorized")
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Order with ID '" . $orderId . "' is not in state 'Authorized'";
		exit;
	}

	$p = PSP::GetPaymentProvider($order["PaymentMethod"]);
	$res = $p->CancelPayment($order["TransactionId"]);

	if ($res === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Order with ID '" . $orderId . "' failed payment operation 'Cancel'";
		exit;
	}

	$order["State"] = "Canceled";

	$ds = new SMDataSource("SMShopOrders");

	if ($ds->GetDataSourceType() === SMDataSourceType::$Xml)
		$ds->Lock();

	$ds->Update($order, "Id = '" . $ds->Escape($order["Id"]) . "'");
	$ds->Commit();
}
else if ($operation === "Invoice") // Called from JSShop
{
	if (SMAuthentication::Authorized() === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Unauthorized - unable to send invoice";
		exit;
	}

	$orderId = SMEnvironment::GetPostValue("OrderId", SMValueRestriction::$Numeric);
	$order = getOrder($orderId);

	/*if ($order["State"] !== "Captured")
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Order with ID '" . $orderId . "' is not in state 'Captured'";
		exit;
	}*/

	SMShopSendMail($order, true);
}

?>
