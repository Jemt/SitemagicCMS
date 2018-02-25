<?php

// Functions invoked from DataSource.callback.php (in the context of Sitemagic)

function SMShopDeleteOrderEntries($orderId)
{
	SMTypeCheck::CheckObject(__METHOD__, "orderId", $orderId, SMTypeCheckType::$String);

	$eDs = new SMDataSource("SMShopOrderEntries");

	if ($eDs->GetDataSourceType() === SMDataSourceType::$Xml)
		$eDs->Lock();

	$eDs->Delete("OrderId = '" . $eDs->Escape($orderId) . "'");
	$eDs->Commit(); // Also releases lock
}

function SMShopProcessNewOrder(SMKeyValueCollection $order)
{
	// Variables

	$eDs = new SMDataSource("SMShopOrderEntries");
	$pDs = new SMDataSource("SMShopProducts");

	$products = null;
	$product = null;

	$discount = 0.0;
	$discountMessage = null;

	$pricing = null;

	/*$vat = 0.0;
	$vatFactor = 0.0;
	$numberOfUnits = 0;
	$unitPriceExclVat = 0.0;
	$unitPriceInclVat = 0.0;
	$priceAllUnitsInclVat = 0.0;

	$discountExclVat = 0.0;
	$discountInclVat = 0.0;

	$resultPriceInclVat = 0.0;
	$resultVat = 0.0;*/

	$commonVatFactor = -99999.99;
	$identicalVat = true;

	$priceTotal = 0.0;
	$vatTotal = 0.0;
	$currency = null;
	$weightTotal = 0.0;
	$weightUnit = null;

	// Load order entries

	if ($eDs->GetDataSourceType() === SMDataSourceType::$Xml)
		$eDs->Lock();

	$entries = $eDs->Select("*", "OrderId = '" . $eDs->Escape($order["Id"]) . "'");

	// Ensure that order has order entries associated

	if (count($entries) === 0) // Unlikely to happen, unless Order was created programmatically using JS API
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Inconsistent order - no associated order entries found (must be created first)";
		exit;
	}

	// Obtain new order ID (order was created with a temporary ID (GUID) generated client side)

	SMAttributes::Lock(); // Prevent two sessions from obtaining the same Order ID
	SMAttributes::Reload(false); // No data will be lost when reloading attributes from a callback since no extensions are being executed

	$orderIdStr = SMAttributes::GetAttribute("SMShopNextOrderId");
	$orderId = (($orderIdStr !== null) ? (int)$orderIdStr : 1);

	SMAttributes::SetAttribute("SMShopNextOrderId", (string)($orderId + 1));
	SMAttributes::Commit(); // Also releases lock

	// Loop through order entries to extract currency, calculate
	// discounts/totals/VAT, and update entries with these information.

	$config = new SMConfiguration(SMEnvironment::GetDataDirectory() . "/SMShop/Config.xml.php");

	foreach ($entries as $entry)
	{
		// Get product associated with entry

		$products = $pDs->Select("*", "Id = '" . $entry["ProductId"] . "'");

		if (count($products) === 0) // Very unlikely to happen, but theoretically possible
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "Product with ID '" . $entry["ProductId"] . "' has been removed";
			exit;
		}

		$product = $products[0];

		// Make sure all products are defined with the same currency and weight unit

		$currency = (($currency !== null) ? $currency : $product["Currency"]);

		if ($currency !== $product["Currency"])
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "Buying products with different currencies is not supported";
			exit;
		}

		$weightUnit = (($weightUnit !== null) ? $weightUnit : $product["WeightUnit"]);

		if ($weightUnit !== $product["WeightUnit"])
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "Buying products with different weight units is not supported";
			exit;
		}

		// Get discount expression

		$discount = 0.0;
		$discountMessage = "";

		if ($product["DiscountExpression"] !== "")
		{
			$discount = SMShopHandleExpression($config, $entry["Units"], $product["Price"], $product["Vat"], $product["Currency"], $product["Weight"], $product["WeightUnit"], null, null, null, null, null, null, $product["DiscountExpression"], "number");

			if ($discount !== 0 && $product["DiscountMessage"] !== "")
			{
				$discountMessage = SMShopHandleExpression($config, $entry["Units"], $product["Price"], $product["Vat"], $product["Currency"], $product["Weight"], $product["WeightUnit"], null, null, null, null, null, null, $product["DiscountMessage"], "string");
			}
		}

		// Totals

		// Important: Calculates MUST be identical to the onces done client side
		// to make sure the results are exactly the same. The code below is based
		// on the JS code found in JSShop/JSShop.js => JSShop.CalculatePricing(..)

		/*******$vat = (float)$product["Vat"];
		$vatFactor = (($vat > 0) ? 1.0 + ($vat / 100) : 1.0);
		$numberOfUnits = (int)$entry["Units"];
		$unitPriceExclVat = (float)$product["Price"];
		$unitPriceInclVat = round($unitPriceExclVat * $vatFactor, 2);
		$priceAllUnitsInclVat = round($unitPriceInclVat * $numberOfUnits, 2); //$unitPriceInclVat * $numberOfUnits;

		$discountExclVat = round($discount, 2); //$discount;
		$discountInclVat = round($discountExclVat * $vatFactor, 2);

		$resultPriceInclVat = round($priceAllUnitsInclVat - $discountInclVat, 2); //$priceAllUnitsInclVat - $discountInclVat;
		$resultVat = $resultPriceInclVat - round($resultPriceInclVat / $vatFactor, 2);

		$priceTotal += $resultPriceInclVat;
		$vatTotal += $resultVat;
		SMLog::Log(__FILE__, __LINE__, "PriceTotal and VAT is now: " . $priceTotal . " incl. VAT - " . $vatTotal . " (VAT) = " . ($priceTotal - $vatTotal) . " excl. VAT");
		$weightTotal += (int)$entry["Units"] * (float)$product["Weight"];*******/


		$pricing = SMShopCalculatePricing((float)$product["Price"], (int)$entry["Units"], (float)$product["Vat"], $discount);

		$vatTotal = round($vatTotal + $pricing["TotalVat"], 2);
		$priceTotal = round($priceTotal + $pricing["TotalInclVat"], 2);
		$weightTotal = round($weightTotal + ((int)$entry["Units"] * (float)$product["Weight"]), 2);

		if ($commonVatFactor === -99999.99)
			$commonVatFactor = $pricing["VatFactor"];

		if ($commonVatFactor !== $pricing["VatFactor"])
			$identicalVat = false;

		// Update entry

		$entry["OrderId"] = (string)$orderId;
		$entry["UnitPrice"] = $product["Price"];
		$entry["Vat"] = $product["Vat"];
		$entry["Currency"] = $product["Currency"];
		$entry["Discount"] = (string)$discount;
		$entry["DiscountMessage"] = $discountMessage;

		$eDs->Update($entry, "Id = '" . $eDs->Escape($entry["Id"]) . "'");
	}

	//$eDs->Commit();

	// Handle cost corrections (shipping expense, order discount, credit card fee, etc)

	$costCorrections = array
	(
		array
		(
			"Expression"			=> $config->GetEntry("CostCorrection1"),
			"Vat"					=> $config->GetEntry("CostCorrectionVat1"),
			"Msg"					=> $config->GetEntry("CostCorrectionMessage1"),
			"CostCorrectionExVat"	=> 0,
			"CostCorrectionVat"		=> 0,
			"CostCorrectionMsg"		=> ""
		),
		array
		(
			"Expression"			=> $config->GetEntry("CostCorrection2"),
			"Vat"					=> $config->GetEntry("CostCorrectionVat2"),
			"Msg"					=> $config->GetEntry("CostCorrectionMessage2"),
			"CostCorrectionExVat"	=> 0,
			"CostCorrectionVat"		=> 0,
			"CostCorrectionMsg"		=> ""
		),
		array
		(
			"Expression"			=> $config->GetEntry("CostCorrection3"),
			"Vat"					=> $config->GetEntry("CostCorrectionVat3"),
			"Msg"					=> $config->GetEntry("CostCorrectionMessage3"),
			"CostCorrectionExVat"	=> 0,
			"CostCorrectionVat"		=> 0,
			"CostCorrectionMsg"		=> ""
		)
	);

	$correctionExclVat = 0.0;
	///////$correctionInclVat = 0.0;
	$correctionVat = 0.0;
	///////$correctionVatFactor = 0.0;
	$correctionMessage = null;

	for ($i = 0 ; $i < count($costCorrections) ; $i++)
	{
		if ($costCorrections[$i]["Expression"] === "")
			continue;

		$correctionExclVat = SMShopHandleExpression($config, null, (string)($priceTotal - $vatTotal), (string)$vatTotal, $currency, (string)$weightTotal, $weightUnit, (($order["AltZipCode"] !== "") ? $order["AltZipCode"] : $order["ZipCode"]), $order["PaymentMethod"], $order["PromoCode"], $order["CustData1"], $order["CustData2"], $order["CustData3"], $costCorrections[$i]["Expression"], "number");
		/////$correctionInclVat = 0.0;

		if ($correctionExclVat > 0 || $correctionExclVat < 0)
		{
			$correctionVat = 0.0;

			if ($costCorrections[$i]["Vat"] !== "")
				$correctionVat = SMShopHandleExpression($config, null, (string)($priceTotal - $vatTotal), (string)$vatTotal, $currency, (string)$weightTotal, $weightUnit, (($order["AltZipCode"] !== "") ? $order["AltZipCode"] : $order["ZipCode"]), $order["PaymentMethod"], $order["PromoCode"], $order["CustData1"], $order["CustData2"], $order["CustData3"], $costCorrections[$i]["Vat"], "number");

			/*****$correctionVatFactor = (($correctionVat > 0) ? 1.0 + ($correctionVat / 100) : 1.0);
			$correctionExclVat = round($correctionExclVat, 2);
			$correctionInclVat = round($correctionExclVat * $correctionVatFactor, 2);****/

			/*$pricing = SMShopCalculatePricing($correctionExclVat, 1, $correctionVat, 0);
			$correctionVatFactor = $pricing["VatFactor"];
			$correctionExclVat = $pricing["TotalExclvat"];
			$correctionInclVat = $pricing["TotalInclvat"];*/

			$correctionMessage = "";

			if ($costCorrections[$i]["Msg"] !== "")
				$correctionMessage = SMShopHandleExpression($config, null, (string)($priceTotal - $vatTotal), (string)$vatTotal, $currency, (string)$weightTotal, $weightUnit, (($order["AltZipCode"] !== "") ? $order["AltZipCode"] : $order["ZipCode"]), $order["PaymentMethod"], $order["PromoCode"], $order["CustData1"], $order["CustData2"], $order["CustData3"], $costCorrections[$i]["Msg"], "string");

			$pricing = SMShopCalculatePricing($correctionExclVat, 1, $correctionVat, 0.0);

			// Results are added to totals later, to prevent CostCorrections from affecting each other
			$costCorrections[$i]["CostCorrectionExVat"] = $pricing["TotalExclVat"];
			$costCorrections[$i]["CostCorrectionVat"] = $pricing["TotalVat"];
			$costCorrections[$i]["CostCorrectionMsg"] = $correctionMessage;

			if ($commonVatFactor !== $pricing["VatFactor"])
				$identicalVat = false;

			// Results are added to totals later, to prevent CostCorrections from affecting each other
			/*****$costCorrections[$i]["CostCorrectionExVat"] = $correctionExclVat;
			$costCorrections[$i]["CostCorrectionVat"] = ($correctionInclVat - $correctionExclVat);
			$costCorrections[$i]["CostCorrectionMsg"] = $correctionMessage;

			if ($commonVatFactor !== $correctionVatFactor)
				$identicalVat = false;*****/
		}
	}

	// Update totals

	for ($i = 0 ; $i < count($costCorrections) ; $i++)
	{
		$priceTotal += $costCorrections[$i]["CostCorrectionExVat"] + $costCorrections[$i]["CostCorrectionVat"];
		$vatTotal += $costCorrections[$i]["CostCorrectionVat"];
		////SMLog::Log(__FILE__, __LINE__, "PriceTotal and VAT is now (CC): " . $priceTotal . " incl. VAT - " . $vatTotal . " (VAT) = " . ($priceTotal - $vatTotal) . " excl. VAT");

	}

	if ($identicalVat === true)
	{
		////SMLog::Log(__FILE__, __LINE__, "VAT is identical - current value: " . $vatTotal);
		$vatTotal = round($priceTotal - ($priceTotal / $commonVatFactor), 2);
		////SMLog::Log(__FILE__, __LINE__, "VAT is identical - new value: " . $vatTotal);
	}

	// Update order details

	$eDs->Commit(); // Committed this late to reduce risk of partially committed data

	$order["Id"] = (string)$orderId;
	$order["Price"] = (string)($priceTotal - $vatTotal);
	$order["Vat"] = (string)$vatTotal;
	$order["Currency"] = $currency;
	$order["Weight"] = (string)$weightTotal;
	$order["WeightUnit"] = $weightUnit;
	$order["CostCorrection1"] = (string)$costCorrections[0]["CostCorrectionExVat"];
	$order["CostCorrectionVat1"] = (string)$costCorrections[0]["CostCorrectionVat"];
	$order["CostCorrectionMessage1"] = $costCorrections[0]["CostCorrectionMsg"];
	$order["CostCorrection2"] = (string)$costCorrections[1]["CostCorrectionExVat"];
	$order["CostCorrectionVat2"] = (string)$costCorrections[1]["CostCorrectionVat"];
	$order["CostCorrectionMessage2"] = $costCorrections[1]["CostCorrectionMsg"];
	$order["CostCorrection3"] = (string)$costCorrections[2]["CostCorrectionExVat"];
	$order["CostCorrectionVat3"] = (string)$costCorrections[2]["CostCorrectionVat"];
	$order["CostCorrectionMessage3"] = $costCorrections[2]["CostCorrectionMsg"];
	$order["TransactionId"] = "";
	$order["State"] = "Initial";

	// Send confirmation mail

	if (SMAttributes::GetAttribute("SMShopSendConfirmationMail") === null || strtolower(SMAttributes::GetAttribute("SMShopSendConfirmationMail")) === "immediately")
	{
		SMShopSendMail($order); // Alternatively called Callbacks/Payment.callback.php in case SMShopSendConfirmationMail is set to OnPaymentAuthorized
	}
}

function SMShopSendMail(SMKeyValueCollection $order, $asInvoice = false, SMKeyValueCollection $additionalArgs = null)
{
	SMTypeCheck::CheckObject(__METHOD__, "asInvoice", $asInvoice, SMTypeCheckType::$Boolean);

	//SMLog::Log(__FILE__, __LINE__, "SMShopSendMail(" . $asInvoice . ")");
	//SMLog::Log(__FILE__, __LINE__, " - Order details: " . print_r($order, true));

	if ($asInvoice === true && $order["InvoiceId"] === "")
	{
		// Ensure invoice ID

		//SMLog::Log(__FILE__, __LINE__, " - Ensure invoice ID");

		SMAttributes::Lock(); // Prevent two sessions from obtaining the same Invoice ID
		SMAttributes::Reload(false); // No data will be lost when reloading attributes from a callback since no extensions are being executed

		$invoiceIdStr = SMAttributes::GetAttribute("SMShopNextInvoiceId");
		$invoiceId = (($invoiceIdStr !== null) ? (int)$invoiceIdStr : 1);

		$update = new SMKeyValueCollection();
		$update["InvoiceId"] = (string)$invoiceId;
		$update["InvoiceTime"] = (string)(time() * 1000);

		$ds = new SMDataSource("SMShopOrders");

		if ($ds->GetDataSourceType() === SMDataSourceType::$Xml)
			$ds->Lock();

		$ds->Update($update, "Id = '" . $ds->Escape($order["Id"]) . "'");
		$ds->Commit(); // Also releases lock

		//SMLog::Log(__FILE__, __LINE__, " - New invoice ID: " . $invoiceId);

		SMAttributes::SetAttribute("SMShopNextInvoiceId", (string)($invoiceId + 1));
		SMAttributes::Commit(); // Also releases lock

		$order["InvoiceId"] = $update["InvoiceId"];
		$order["InvoiceTime"] = $update["InvoiceTime"];
	}

	$data = SMShopGetOrderConfirmationData($order, $asInvoice);

	if ($data === null)
		return; // Expression used to choose mail template returned nothing, which means no e-mail should be sent

	$config = new SMConfiguration(SMEnvironment::GetDataDirectory() . "/SMShop/Config.xml.php");

	$mailAddress = $data["MailAddress"];
	$title = $data["Title"];
	$content = $data["Content"];

	if ($additionalArgs != null)
	{
		foreach ($additionalArgs as $key => $value)
		{
			if (strpos($key, "{") === 0 && strrpos($key, "}") === strlen($key) - 1) // E.g. {placeholder}
			{
				$content = str_replace($key, $value, $content);
			}
			else if ($key === "Content")
			{
				$content .= $value;
			}
		}
	}

	$content = preg_replace('/{\S+}/', "", $content); // Remove unsupported (unknown) place holders

	// Generate PDF files
	$generated = SMShopGeneratePdfAttachments($content);
	$content = $generated["Content"];	// Content without PDF attachments
	$pdfFiles = $generated["Files"];	// File paths to generated PDF files - should be removed when no longer needed using SMShopCleanUpPdfAttachments($pdfFiles)

	// Send mail

	$mail = new SMMail();
	$mail->AddRecipient($mailAddress);
	$mail->SetSubject((($title !== null && $title !== "") ? $title : $lang->GetTranslation((($asInvoice === false) ? "Confirmation" : "Invoice") . "Title")));
	$mail->SetContent($content);

	if ($config->GetEntry("ShopEmail") !== "" && SMStringUtilities::Validate($config->GetEntry("ShopEmail"), SMValueRestriction::$EmailAddress) === true)
		$mail->SetSender($config->GetEntry("ShopEmail"));
	if ($config->GetEntry("ShopBccEmail") !== "" && SMStringUtilities::Validate($config->GetEntry("ShopBccEmail"), SMValueRestriction::$EmailAddress) === true)
		$mail->AddRecipient($config->GetEntry("ShopBccEmail"), SMMailRecipientType::$Bcc);

	// Attach PDF files
	foreach ($pdfFiles as $filename => $path)
		$mail->AddAttachment($filename, $path);

	$mail->Send();

	// Remove generated PDF files
	SMShopCleanUpPdfAttachments($pdfFiles);
}

// Helpers

function SMShopGetOrderConfirmationData(SMKeyValueCollection $order, $asInvoice = false)
{
	SMTypeCheck::CheckObject(__METHOD__, "asInvoice", $asInvoice, SMTypeCheckType::$Boolean);

	$config = new SMConfiguration(SMEnvironment::GetDataDirectory() . "/SMShop/Config.xml.php");

	$mailAddress = $order["Email"];
	$title = null;
	$content = null;

	$template = (($asInvoice === false) ? "ConfirmationMail.html" : "InvoiceMail.html");

	if ($asInvoice === false && $config->GetEntry("ConfirmationMailTemplateExpression") !== null && $config->GetEntry("ConfirmationMailTemplateExpression") !== "")
		$template = SMShopHandleExpression($config, null, $order["Price"], $order["Vat"], $order["Currency"], $order["Weight"], $order["WeightUnit"], (($order["AltZipCode"] !== "") ? $order["AltZipCode"] : $order["ZipCode"]), $order["PaymentMethod"], $order["PromoCode"], $order["CustData1"], $order["CustData2"], $order["CustData3"], $config->GetEntry("ConfirmationMailTemplateExpression"), "string");
	else if ($asInvoice === true && $config->GetEntry("InvoiceMailTemplateExpression") !== null && $config->GetEntry("InvoiceMailTemplateExpression") !== "")
		$template = SMShopHandleExpression($config, null, $order["Price"], $order["Vat"], $order["Currency"], $order["Weight"], $order["WeightUnit"], (($order["AltZipCode"] !== "") ? $order["AltZipCode"] : $order["ZipCode"]), $order["PaymentMethod"], $order["PromoCode"], $order["CustData1"], $order["CustData2"], $order["CustData3"], $config->GetEntry("InvoiceMailTemplateExpression"), "string");

	if ($template === "")
		return null;

	$template = SMEnvironment::GetDataDirectory() . "/SMShop/" . $template;

	if (SMFileSystem::FileExists($template) === true)
	{
		$reader = new SMTextFileReader($template);
		$fileContent = $reader->ReadAll();

		$data = explode("\n", $fileContent); // 0 = title, 1-* = content

		if (count($data) < 2)
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "E-mail template file '" . $file . "' is malformed";
			exit;
		}

		$title = $data[0];
		$content = implode(array_slice($data, 1));
	}

	if ($content === null || $content === "" || SMStringUtilities::Validate($mailAddress, SMValueRestriction::$EmailAddress) === false)
		return;

	$lang = new SMLanguageHandler(SMExtensionManager::GetExecutingExtension());

	$eDs = new SMDataSource("SMShopOrderEntries");
	$pDs = new SMDataSource("SMShopProducts");

	// Order details

	$entries = $eDs->Select("*", "OrderId = '" . $eDs->Escape($order["Id"]) . "'");
	$products = null;

	$orderDetails = "";

	/*****$vatFactor = 0.0;
	$unitPriceExclVat = 0.0;
	$UnitPriceInclVat = 0.0;
	$discountInclVat = 0.0;******/

	foreach ($entries as $entry)
	{
		$products = $pDs->Select("*", "Id = '" . $pDs->Escape($entry["ProductId"]) . "'");

		if (count($products) === 0) // Very unlikely to happen - but theoretically a product could be removed while placing an order
		{
			header("HTTP/1.1 500 Internal Server Error");
			echo "Product could not be found";
			exit;
		}

		$pricing = SMShopCalculatePricing((float)$entry["UnitPrice"], (int)$entry["Units"], (float)$entry["Vat"], (float)$entry["Discount"]);

		$orderDetails .= (($orderDetails !== "") ? "<br>" : "");
		$orderDetails .= $entry["Units"] . " x " . $products[0]["Title"] . ", " . $order["Currency"] . " " . number_format($pricing["TotalInclVat"], 2, $lang->GetTranslation("DecimalSeparator"), "");


		/*******$vatFactor = (((float)$entry["Vat"] > 0) ? 1.0 + ((float)$entry["Vat"] / 100) : 1.0);
		$unitPriceExclVat = (float)$entry["UnitPrice"];
		$unitPriceInclVat = round($unitPriceExclVat * $vatFactor, 2);
		$discountInclVat = round((float)$entry["Discount"] * $vatFactor, 2);

		$orderDetails .= (($orderDetails !== "") ? "<br>" : "");
		$orderDetails .= $entry["Units"] . " x " . $products[0]["Title"] . ", " . $order["Currency"] . " " . number_format(((int)$entry["Units"] * $unitPriceInclVat) - $discountInclVat, 2, $lang->GetTranslation("DecimalSeparator"), "");
		//$orderDetails .= $entry["Units"] . " x " . $products[0]["Title"] . ", " . $order["Currency"] . " " . number_format((((int)$entry["Units"] * (float)$entry["UnitPrice"]) - (float)$entry["Discount"]) * (((float)$entry["Vat"] / 100) + 1), 2, $lang->GetTranslation("DecimalSeparator"), "");
		********/
	}

	// Cost corrections

	for ($i = 1 ; $i <= 3 ; $i++)
	{
		if ((float)$order["CostCorrection" . $i] === 0.0)
			continue;

		$orderDetails .= "<br>";
		$orderDetails .= $order["CostCorrectionMessage" . $i] . ", " . $order["Currency"] . " " . number_format((float)$order["CostCorrection" . $i] + (float)$order["CostCorrectionVat" . $i], 2, $lang->GetTranslation("DecimalSeparator"), "");
	}

	// Mail content - replace place holders

	$expressionMatches = null; // Matched expressions such as ${[(paymentmethod == "CreditCard" ? "Paying by Credit Card" : "Using alternative payment method")]}
	$expressionResult = null;

	// Mail subject

	$expressionMatches = null; // Matched expressions such as ${[(paymentmethod == "CreditCard" ? "Paying by Credit Card" : "Using alternative payment method")]}
	$expressionResult = null;
	preg_match_all('/\${\[([\s\S]*?)\]}/', $title, $expressionMatches, PREG_SET_ORDER, 0); // https://regex101.com/r/fKNYgA/3/

	foreach ($expressionMatches as $expr) // $expr[0] = full match, $expr[1] = capture group (actual expression to evaluate)
	{
		$expressionResult = SMShopHandleExpression($config, null, $order["Price"], $order["Vat"], $order["Currency"], $order["Weight"], $order["WeightUnit"], (($order["AltZipCode"] !== "") ? $order["AltZipCode"] : $order["ZipCode"]), $order["PaymentMethod"], $order["PromoCode"], $order["CustData1"], $order["CustData2"], $order["CustData3"], $expr[1], "string");
		$title = str_replace($expr[0], $expressionResult, $title);
	}

	// Mail content

	$expressionMatches = null; // Matched expressions such as {[(paymentmethod == "CreditCard" ? "Paying by Credit Card" : "Using alternative payment method")]}
	$expressionResult = null;
	preg_match_all('/\${\[([\s\S]*?)\]}/', $content, $expressionMatches, PREG_SET_ORDER, 0); // https://regex101.com/r/fKNYgA/3/

	foreach ($expressionMatches as $expr) // $expr[0] = full match, $expr[1] = capture group (actual expression to evaluate)
	{
		$expressionResult = SMShopHandleExpression($config, null, $order["Price"], $order["Vat"], $order["Currency"], $order["Weight"], $order["WeightUnit"], (($order["AltZipCode"] !== "") ? $order["AltZipCode"] : $order["ZipCode"]), $order["PaymentMethod"], $order["PromoCode"], $order["CustData1"], $order["CustData2"], $order["CustData3"], $expr[1], "htmlstring");
		$content = str_replace($expr[0], $expressionResult, $content);
	}

	// TODO: These place holders are not consistent with expressions above ( e.g. ${[..]} ) or place holders used to populate orderline data (see further down where {[Placeholder]} syntax is used)
	$content = str_replace("{Company}", $order["Company"], $content);
	$content = str_replace("{FirstName}", $order["FirstName"], $content);
	$content = str_replace("{LastName}", $order["LastName"], $content);
	$content = str_replace("{Address}", $order["Address"], $content);
	$content = str_replace("{ZipCode}", $order["ZipCode"], $content);
	$content = str_replace("{City}", $order["City"], $content);
	$content = str_replace("{Phone}", $order["Phone"], $content);
	$content = str_replace("{Email}", $order["Email"], $content);
	$content = str_replace("{Message}", nl2br($order["Message"], false), $content);
	$content = str_replace("{AltCompany}", $order["AltCompany"], $content);
	$content = str_replace("{AltFirstName}", $order["AltFirstName"], $content);
	$content = str_replace("{AltLastName}", $order["AltLastName"], $content);
	$content = str_replace("{AltAddress}", $order["AltAddress"], $content);
	$content = str_replace("{AltZipCode}", $order["AltZipCode"], $content);
	$content = str_replace("{AltCity}", $order["AltCity"], $content);
	$content = str_replace("{DeliveryCompany}", (($order["AltAddress"] !== "") ? $order["AltCompany"] : $order["Company"]), $content); // Use AltCompany (which is optional) only if AltAddress is set
	$content = str_replace("{DeliveryFirstName}", (($order["AltFirstName"] !== "") ? $order["AltFirstName"] : $order["FirstName"]), $content);
	$content = str_replace("{DeliveryLastName}", (($order["AltLastName"] !== "") ? $order["AltLastName"] : $order["LastName"]), $content);
	$content = str_replace("{DeliveryAddress}", (($order["AltAddress"] !== "") ? $order["AltAddress"] : $order["Address"]), $content);
	$content = str_replace("{DeliveryZipCode}", (($order["AltZipCode"] !== "") ? $order["AltZipCode"] : $order["ZipCode"]), $content);
	$content = str_replace("{DeliveryCity}", (($order["AltCity"] !== "") ? $order["AltCity"] : $order["City"]), $content);
	$content = str_replace("{OrderId}", $order["Id"], $content);
	$content = str_replace("{InvoiceId}", $order["InvoiceId"], $content);
	$content = str_replace("{Currency}", $order["Currency"], $content);
	$content = str_replace("{Vat}", number_format((float)$order["Vat"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{Price}", number_format((float)$order["Price"] + (float)$order["Vat"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{Weight}", number_format((float)$order["Weight"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{WeightUnit}", $order["WeightUnit"], $content);
	$content = str_replace("{CostCorrection1}", number_format((float)$order["CostCorrection1"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{CostCorrectionVat1}", number_format((float)$order["CostCorrectionVat1"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{CostCorrectionMessage1}", $order["CostCorrectionMessage1"], $content);
	$content = str_replace("{CostCorrection2}", number_format((float)$order["CostCorrection2"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{CostCorrectionVat2}", number_format((float)$order["CostCorrectionVat2"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{CostCorrectionMessage2}", $order["CostCorrectionMessage2"], $content);
	$content = str_replace("{CostCorrection3}", number_format((float)$order["CostCorrection3"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{CostCorrectionVat3}", number_format((float)$order["CostCorrectionVat3"], 2, $lang->GetTranslation("DecimalSeparator"), ""), $content);
	$content = str_replace("{CostCorrectionMessage3}", $order["CostCorrectionMessage3"], $content);
	$content = str_replace("{PaymentMethod}", $order["PaymentMethod"], $content);
	$content = str_replace("{TransactionId}", $order["TransactionId"], $content);
	$content = str_replace("{PromoCode}", $order["PromoCode"], $content);
	$content = str_replace("{CustData1}", $order["CustData1"], $content);
	$content = str_replace("{CustData2}", $order["CustData2"], $content);
	$content = str_replace("{CustData3}", $order["CustData3"], $content);
	$content = str_replace("{OrderYear}", date("Y", ((int)$order["Time"])/1000), $content);
	$content = str_replace("{OrderMonth}", date("m", ((int)$order["Time"])/1000), $content);
	$content = str_replace("{OrderDay}", date("d", ((int)$order["Time"])/1000), $content);
	$content = str_replace("{InvoiceYear}", date("Y", ((int)$order["InvoiceTime"])/1000), $content);
	$content = str_replace("{InvoiceMonth}", date("m", ((int)$order["InvoiceTime"])/1000), $content);
	$content = str_replace("{InvoiceDay}", date("d", ((int)$order["InvoiceTime"])/1000), $content);
	$content = str_replace("{DateYear}", date("Y"), $content);
	$content = str_replace("{DateMonth}", date("m"), $content);
	$content = str_replace("{DateDay}", date("d"), $content);
	$content = str_replace("{OrderDetails}", $orderDetails, $content);
	$content = str_replace("{WebsiteUrl}", SMEnvironment::GetExternalUrl(), $content);
	//$content = preg_replace('/{\S+}/', "", $content); // Remove unsupported place holders

	$t = new SMTemplate();
	$t->LoadHtml($content);

	$pricing = null;
	$orderLine = null;
	$orderLines = array();
	$productLines = array();

	// TODO: These place holders are not consistent with place holders used to populate order data (see further up where {Placeholder} syntax is used)
	foreach (array("OrderLine", "ProductLine") as $listType)
	{
		foreach ($entries as $entry)
		{
			$products = $pDs->Select("*", "Id = '" . $pDs->Escape($entry["ProductId"]) . "'");

			if (count($products) === 0) // Very unlikely to happen - but theoretically a product could be removed while placing an order
			{
				header("HTTP/1.1 500 Internal Server Error");
				echo "Product could not be found";
				exit;
			}

			$pricing = SMShopCalculatePricing((float)$entry["UnitPrice"], (int)$entry["Units"], (float)$entry["Vat"], (float)$entry["Discount"]);

			//SMLog::Log(__FILE__, __LINE__, print_r($pricing, true));

			$orderLine = new SMKeyValueCollection();
			$orderLine[$listType . "Amount"] = $entry["Units"];
			$orderLine[$listType . "ProductId"] = $entry["ProductId"];
			$orderLine[$listType . "ProductTitle"] = $products[0]["Title"];
			$orderLine[$listType . "ProductPriceExclVat"] = number_format($pricing["UnitPriceExclVat"], 2, $lang->GetTranslation("DecimalSeparator"), "");
			$orderLine[$listType . "ProductPriceInclVat"] = number_format($pricing["UnitPriceInclVat"], 2, $lang->GetTranslation("DecimalSeparator"), "");
			$orderLine[$listType . "TotalExclVat"] = number_format($pricing["TotalExclVat"], 2, $lang->GetTranslation("DecimalSeparator"), "");
			$orderLine[$listType . "TotalInclVat"] = number_format($pricing["TotalInclVat"], 2, $lang->GetTranslation("DecimalSeparator"), "");

			if ($listType === "OrderLine")
				$orderLines[] = $orderLine;
			else
				$productLines[] = $orderLine;

			/*
			$resultObj = array
			(
				"VatFactor"			=> $vatFactor,
				"UnitPriceExclVat"	=> $unitPriceExclVat,
				"UnitPriceInclVat"	=> $unitPriceInclVat,
				"DiscountExclVat"	=> $discountExclVat,
				"DiscountInclVat"	=> $discountInclVat,
				"TotalInclVat"		=> $resultPriceInclVat,
				"TotalExclVat"		=> $resultPriceExclVat,
				"TotalVat"			=> $resultVat
			);
			*/
		}

		if ($listType === "OrderLine")
		{
			for ($i = 1 ; $i <= 3 ; $i++)
			{
				if ((float)$order["CostCorrection" . $i] === 0.0)
					continue;

				$orderLine = new SMKeyValueCollection();
				$orderLine["OrderLineAmount"] = "1";
				$orderLine["OrderLineProductId"] = "";
				$orderLine["OrderLineProductTitle"] = $order["CostCorrectionMessage" . $i];
				$orderLine["OrderLineProductPriceExclVat"] = number_format((float)$order["CostCorrection" . $i], 2, $lang->GetTranslation("DecimalSeparator"), "");
				$orderLine["OrderLineProductPriceInclVat"] = number_format((float)$order["CostCorrection" . $i] + (float)$order["CostCorrectionVat" . $i], 2, $lang->GetTranslation("DecimalSeparator"), "");
				$orderLine["OrderLineTotalExclVat"] = $orderLine["OrderLineProductPriceExclVat"];
				$orderLine["OrderLineTotalInclVat"] = $orderLine["OrderLineProductPriceInclVat"];
				$orderLines[] = $orderLine;
			}
		}
	}

	$t->ReplaceTagsRepeated("OrderLines", $orderLines);
	$t->ReplaceTagsRepeated("ProductLines", $productLines);
	$content = $t->GetContent();

	$res = array();
	$res["Title"] = $title;
	$res["Content"] = $content;
	$res["MailAddress"] = $mailAddress;

	return $res;
}

function SMShopGeneratePdfAttachments($content)
{
	SMTypeCheck::CheckObject(__METHOD__, "content", $content, SMTypeCheckType::$String);

	$pdfFiles = array();

	$pdfMatches = null;
	//$pdfMatchCount = preg_match_all('/<!--\s*PDF:(\w+)\s*-->\s*(.*?)\s*<!--\s*\/PDF:\1\s*-->/ms', $content, $pdfMatches, PREG_SET_ORDER); // https://regex101.com/r/3J3pV2/4
	$pdfMatchCount = preg_match_all('/<!--\s*PDF:([\w\-]+)\s*-->\s*(.*?)\s*<!--\s*\/PDF:\1\s*-->/ms', $content, $pdfMatches, PREG_SET_ORDER); // https://regex101.com/r/3J3pV2/5

	if ($pdfMatchCount !== false && $pdfMatchCount > 0)
	{
		if (SMFileSystem::FolderExists(SMEnvironment::GetDataDirectory() . "/SMShop/PDFs") === false)
		{
			SMFileSystem::CreateFolder(SMEnvironment::GetDataDirectory() . "/SMShop/PDFs");
		}

		require_once(dirname(__FILE__) . "/../../TCPDF/tcpdf.php");

		$fullMatch = null;
		$filename = null;
		$pdfContent = null;
		$pdf = null;

		// TODO: Replace code below with SMEnvironment::GetDocumentRoot() - code copied here instead
		// to work around bug on Stampemollen which is running with a buggy version of SMEnvironment.
		$root = $_SERVER["SCRIPT_FILENAME"];			// E.g. /var/www/domain.com/web/Sitemagic/index.php
		$root = str_replace("\\", "/", $root);			// In case backslashes are used on Windows Server
		$root = substr($root, 0, strrpos($root, "/"));	// Remove last slash and filename (e.g. /index.php)

		foreach ($pdfMatches as $match) // $match[0] = full match, $match[1] = 1st capture group (filename), $match[2] = 2nd capture group (PDF content)
		{
			$fullMatch = $match[0];
			$filename = $match[1] . ".pdf";
			$pdfContent = $match[2];

			$pdfFiles[$filename] = SMEnvironment::GetDataDirectory() . "/SMShop/PDFs/" . SMRandom::CreateGuid() . ".pdf";

			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, "UTF-8", false);

			$pdf->SetCreator("JSShop on Sitemagic CMS");
			$pdf->SetAuthor("");
			$pdf->SetTitle("");
			$pdf->SetSubject("");
			$pdf->SetKeywords("");

			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);

			// set margins
			$pdf->SetMargins(20, 20, 20);
			$pdf->SetAutoPageBreak(true, 20);

			$pdf->SetFont("dejavusans", "", 10); // Notice that only dejavusans ships with TCFTP in SMShop to save space!

			$pdf->AddPage();

			$pdf->writeHTML(utf8_encode($pdfContent), false);
			$pdf->Output($root . "/" . $pdfFiles[$filename], "F");

			$content = str_replace($fullMatch, "", $content);
		}
	}

	return array("Content" => $content, "Files" => $pdfFiles);
}

function SMShopCleanUpPdfAttachments($pdfFiles)
{
	SMTypeCheck::CheckArray(__METHOD__, "pdfFiles", $pdfFiles, SMTypeCheckType::$String);

	foreach ($pdfFiles as $filename => $path)
	{
		SMFileSystem::Delete($path);
	}
}

function SMShopHandleExpression($config, $units, $price, $vat, $currency, $weight, $weightUnit, $zipCode, $paymentMethod, $promoCode, $custData1, $custData2, $custData3, $expression, $returnType)
{
	///////SMTypeCheck::CheckObject(__METHOD__, "expression", $expression, SMTypeCheckType::$String);

	// Security validation

	$expr = $expression;
	$expr = preg_replace("/\r|\n|\t/", "", $expr);
	$expr = preg_replace("/\/\*.*?\*\//", "", $expr);
	$expr = preg_replace("/index|units|price|vat|currency|weightunit|weight|zipcodeval|zipcode|paymentmethod|promocode|custdata1|custdata2|custdata3/", "", $expr);
	$expr = preg_replace("/JSShop.Floor|JSShop.Ceil|JSShop.Round/", "", $expr);
	$expr = preg_replace("/ |[0-9]|\\*|\\+|\\-|\\/|%|=|&|\\||!|\\.|:|\\(|\\)|\\[|\\]|>|<|\\?|true|false/", "", $expr);
	$expr = preg_replace("/([\"']).*?\\1/", "", $expr);

	$secure = ($expr === ""); // All valid elements were removed above, so if $expr contains anything, it is potentially a security threat

	if ($secure === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Invalid and potentially insecure expression detected";
		//echo "Invalid and potentially insecure expression detected: " . $expression . "<br>Tokens causing security exception: " . $expr;
		exit;
	}

	// Allow string concatenation - PHP uses . (period) while JS uses + (plus)

	$expression = preg_replace("/([\"'])\\s*\\+/", "$1 .", $expression); // https://regex101.com/r/tS6yR4/1 - replace "+ or '+ with ". and '.
	$expression = preg_replace("/\\+\\s*([\"'])/", ". $1", $expression); // https://regex101.com/r/uC3tO9/1 - replace +" or +' with ." and .'

	// Make variables available to discount expression

	$zipCodeVal = -1;

	if ($zipCode !== "")
	{
		$zipCode = trim(str_replace(" ", " ", $zipCode)); // Replace non-breaking spaces with spaces (Mac: Alt + Space), and trim value
		$zipCodeVal = ((is_numeric($zipCode) === true && (int)$zipCode . "" === $zipCode) ? (int)$zipCode : -1);
	}

	$expr = "";
	$expr .= "\nunits = " . (($units !== null) ? $units : -1) . ";";
	$expr .= "\nprice = " . (($price !== null) ? $price : -1) . ";";
	$expr .= "\nvat = " . (($vat !== null) ? $vat : -1) . ";";
	$expr .= "\ncurrency = \"" . (($currency !== null) ? $currency : -1) . "\";";
	$expr .= "\nweight = " . (($weight !== null) ? $weight : -1) . ";";
	$expr .= "\nweightunit = \"" . (($weightUnit !== null) ? $weightUnit : "") . "\";";
	$expr .= "\nzipcode = \"" . (($zipCode !== null) ? $zipCode : "") . "\";";
	$expr .= "\nzipcodeval = " . (($zipCodeVal !== null) ? $zipCodeVal : -1) . ";";
	$expr .= "\npaymentmethod = \"" . (($paymentMethod !== null) ? $paymentMethod : "") . "\";";
	$expr .= "\npromocode = \"" . (($promoCode !== null) ? $promoCode : "") . "\";";
	$expr .= "\ncustdata1 = \"" . (($custData1 !== null) ? $custData1 : "") . "\";";
	$expr .= "\ncustdata2 = \"" . (($custData2 !== null) ? $custData2 : "") . "\";";
	$expr .= "\ncustdata3 = \"" . (($custData3 !== null) ? $custData3 : "") . "\";";
	$expr .= "\nindex = json_decode('" . (($config !== null && $config->GetEntry("PriceIndex") !== "") ? str_replace("'", "\"", $config->GetEntry("PriceIndex")) : "{}") . "', true);";
	$expr .= "\nreturn (" . str_replace("JSShop.Floor", "floor", str_replace("JSShop.Ceil", "ceil", str_replace("JSShop.Round", "round", $expression))) . ");";

	// Turn JS variables into PHP compliant variables

	$expr = str_replace("units", "\$units", $expr);
	$expr = str_replace("price", "\$price", $expr);
	$expr = str_replace("vat", "\$vat", $expr);
	$expr = str_replace("currency", "\$currency", $expr);
	$expr = str_replace("weight", "\$weight", $expr); // $weight AND $weightunit (both starts with "weight")
	$expr = str_replace("zipcode", "\$zipcode", $expr); // $zipcode AND $zipcodeval (both starts with "zipcode")
	$expr = str_replace("paymentmethod", "\$paymentmethod", $expr);
	$expr = str_replace("promocode", "\$promocode", $expr);
	$expr = str_replace("custdata", "\$custdata", $expr); // $custdata1 AND $custdata2 AND $custdata3
	$expr = str_replace("index", "\$index", $expr);

	// Evaluate expression

	$res = eval($expr);

	$isValid = false;

	if ($returnType === "number")
	{
		$isValid = (is_numeric($res) === true);
	}
	else if ($returnType === "string")
	{
		$isValid = (is_string($res) === true && strip_tags($res) === $res); // TBD: Why are we using strip_tags(..) ? HTML is encoded further down - this seems a bit abrupt as it will halt execution
	}
	else if ($returnType === "htmlstring")
	{
		$isValid = (is_string($res) === true);
	}
	else
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Return type must be either 'string' or 'number'";
		exit;
	}

	if ($isValid === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Expression did not produce a valid value of type '" . $returnType . "'";
		exit;
	}

	if (gettype($res) === "string")
	{
		if ($returnType !== "htmlstring")
		{
			$res = SMStringUtilities::HtmlEncode($res);
		}
	}
	else // number - always return as float
	{
		$res = (float)$res;
	}

	return $res;
}

function SMShopCalculatePricing($priceExVat, $units, $vatPercentage, $discountExVat)
{
	SMTypeCheck::CheckObject(__METHOD__, "priceExVat", $priceExVat, SMTypeCheckType::$Float);
	SMTypeCheck::CheckObject(__METHOD__, "units", $units, SMTypeCheckType::$Integer);
	SMTypeCheck::CheckObject(__METHOD__, "vatPercentage", $vatPercentage, SMTypeCheckType::$Float);
	SMTypeCheck::CheckObject(__METHOD__, "discountExVat", $discountExVat, SMTypeCheckType::$Float);

	// Important: Calculates MUST be identical to the onces done client side
	// to make sure the results are exactly the same. The code below is based
	// on the JS code found in JSShop/JSShop.js => JSShop.CalculatePricing(..)

	$vatFactor = (($vatPercentage > 0) ? 1.0 + ($vatPercentage / 100) : 1.0);
	$numberOfUnits = $units;
	$unitPriceExclVat = round($priceExVat, 2);
	$unitPriceInclVat = round($unitPriceExclVat * $vatFactor, 2);
	$priceAllUnitsInclVat = round($unitPriceInclVat * $numberOfUnits, 2);

	$discountExclVat = round($discountExVat, 2);
	$discountInclVat = round($discountExclVat * $vatFactor, 2);

	$resultPriceInclVat = round($priceAllUnitsInclVat - $discountInclVat, 2);
	$resultPriceExclVat = round($resultPriceInclVat / $vatFactor, 2);
	$resultVat = round($resultPriceInclVat - $resultPriceExclVat, 2);

	$resultObj = array
	(
		"VatFactor"			=> $vatFactor,
		"UnitPriceExclVat"	=> $unitPriceExclVat,
		"UnitPriceInclVat"	=> $unitPriceInclVat,
		"DiscountExclVat"	=> $discountExclVat,
		"DiscountInclVat"	=> $discountInclVat,
		"TotalInclVat"		=> $resultPriceInclVat,
		"TotalExclVat"		=> $resultPriceExclVat,
		"TotalVat"			=> $resultVat
	);

	return $resultObj;
}

?>
