<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

if (SMAuthentication::Authorized() === false)
	throw new exception("Unauthorized!");

// Functions

function SMShopGetConfiguration($pspmHardcodedSettings)
{
	SMTypeCheck::CheckMultiArray(__METHOD__, "pspmHardcodedSettings", $pspmHardcodedSettings, SMTypeCheckType::$String);

	$path = SMEnvironment::GetDataDirectory() . "/SMShop";
	$configuration = new SMConfiguration($path . "/Config.xml.php");

	// Load e-mail templates

	$files = SMFileSystem::GetFiles($path . "/MailTemplates");
	$templateFiles = array();

	$mailTemplates = "";
	$reader = null;
	$fileData = null;
	$title = null;
	$subject = null;
	$content = null;

	$lang = new SMLanguageHandler("SMShop");

	foreach ($files as $file)
	{
		if (SMStringUtilities::EndsWith($file, ".html") === true) // Make sure we do not include e.g. .DS_Store files or similar
		{
			$templateFiles[] = $file;
		}
	}

	foreach ($templateFiles as $file)
	{
		$reader = new SMTextFileReader($path . "/MailTemplates/" . $file);
		$content = $reader->ReadAll();
		$fileData = explode("\n", $content);

		$title = ((count($templateFiles) === 2) ? $lang->GetTranslation("File:" . $file) : null);
		$title = (($title !== null) ? $title : $file);

		$subject = $fileData[0];
		$content = implode("\n", array_slice($fileData, 1));

		$mailTemplates .= (($mailTemplates !== "") ? ", " : "");
		$mailTemplates .= '{ "Name": "' . $file . '", "Title": "' . $title . '", "Subject": "' . SMStringUtilities::JsonEncode($subject) . '", "Content": "' . SMStringUtilities::JsonEncode($content) . '" }';
	}

	// Load payment modules and associated settings

	$paymentModules = array();
	$paymentModulesConfigured = array();
	$paymentModulesStr = "";
	$paymentModuleSettings = "";

	// Load available PSPI modules
	$paymentModules = SMFileSystem::GetFolders($path . "/../PSPI");

	// Load configured PSPI modules
	if ($configuration->GetEntryOrEmpty("PaymentMethods") !== "")
	{
		$modules = explode("#;#", $configuration->GetEntryOrEmpty("PaymentMethods"));
		$paymentModule = null;

		foreach ($modules as $pm)
		{
			$paymentModule = explode("#:#", $pm); // 0 = PSPI module name, 1 = title, 2 = enabled (true/false)
			$paymentModulesConfigured[$paymentModule[0]] = array("Title" => $paymentModule[1], "Enabled" => ($paymentModule[2] === "true"));
		}
	}

	// Make payment modules available as JSON
	foreach ($paymentModules as $pm)
	{
		// Load PSPI settings

		require_once($path . "/../PSPI/" . $pm . "/Config.php"); // Contains $config array with key-value pairs

		$paymentModuleSettings = "";
		foreach ($config as $key => $val)
		{
			if (isset($pspmHardcodedSettings[$pm]) === true && isset($pspmHardcodedSettings[$pm][$key]) === true)
				continue; // Skip setting hardcoded by SMShop

			$paymentModuleSettings .= (($paymentModuleSettings !== "") ? ", " : "");
			$paymentModuleSettings .= '{ "Title": "' . SMStringUtilities::JsonEncode($key) . '", "Value": "' . SMStringUtilities::JsonEncode($val) . '" }';
		}

		// Register PSPI module

		$paymentModulesStr .= (($paymentModulesStr !== "") ? ", " : "");
		$paymentModulesStr .= '{ "Module": "' . $pm . '", "Title": "' . ((isset($paymentModulesConfigured[$pm]) === true) ? SMStringUtilities::JsonEncode($paymentModulesConfigured[$pm]["Title"]) : "") . '", "Enabled": ' . ((isset($paymentModulesConfigured[$pm]) === true && isset($paymentModulesConfigured[$pm]["Enabled"]) === true && $paymentModulesConfigured[$pm]["Enabled"] === true) ? "true" : "false") . ', "Settings": [ ' . $paymentModuleSettings . ' ] }';
	}

	// Retrieve cost correction expressions

	$costCorrections = "";

	for ($i = 1 ; $i <= 3 ; $i++)
	{
		$costCorrections .= (($costCorrections !== "") ? ", " : "");
		$costCorrections .= '{ "CostCorrection": "' . SMStringUtilities::JsonEncode($configuration->GetEntryOrEmpty("CostCorrection" . $i)) . '", "Vat": "' . SMStringUtilities::JsonEncode($configuration->GetEntryOrEmpty("CostCorrectionVat" . $i)) . '", "Message": "' . SMStringUtilities::JsonEncode($configuration->GetEntryOrEmpty("CostCorrectionMessage" . $i)) . '" }';
	}

	// Get NextOrderId and NextInvoiceId

	$state = new SMDataSource("SMShopState"); // IMPORTANT: Always lock on this DS when writing data! It contains important data such as NextOrderId and NextInvoiceId! ONLY lock for a very small amount of time - it is constantly being used!

	$items = $state->Select("*", "key = 'NextOrderId'");
	$nextOrderId = ((count($items) > 0) ? $items[0]["value"] : "1");

	$items = $state->Select("*", "key = 'NextInvoiceId'");
	$nextInvoiceId = ((count($items) > 0) ? $items[0]["value"] : "1");

	$state = null; // Prevent further use! If we need it to write data, make sure it is locked first!

	// Return data as JSON

	$json = '
	"Basic": { "TermsPage": "' . $configuration->GetEntryOrEmpty("TermsPage") . '", "ReceiptPage": "' . $configuration->GetEntryOrEmpty("ReceiptPage") . '", "ShopBccEmail": "' . $configuration->GetEntryOrEmpty("ShopBccEmail") . '" },
	"MailTemplates":
	{
		"Confirmation": "' . SMStringUtilities::JsonEncode($configuration->GetEntryOrEmpty("ConfirmationMailTemplateExpression")) . '",
		"Invoice": "' . SMStringUtilities::JsonEncode($configuration->GetEntryOrEmpty("InvoiceMailTemplateExpression")) . '",
		"Templates": [' . $mailTemplates . ']
	},
	"PaymentMethods": [' . $paymentModulesStr . '],
	"CostCorrections": [ ' . $costCorrections . ' ],
	"AdditionalData": "' . SMStringUtilities::JsonEncode($configuration->GetEntryOrEmpty("AdditionalData")) . '",
	"Identifiers": { "NextOrderId": { "Value": ' . $nextOrderId . ', "Dirty": false }, "NextInvoiceId": { "Value": ' . $nextInvoiceId . ', "Dirty": false } },
	"Behaviour": { "CaptureBeforeInvoice": ' . ($configuration->GetEntry("CaptureBeforeInvoice") === "true" ? "true" : "false") . ' }';

	return "{" . $json . "\n}";
}

function SMShopSetConfiguration($data, $pspmHardcodedSettings)
{
	// Both $data and $pspmHardcodedSettings are associative arrays. The latter has a uniform structure.
	// The $data argument however is an associative array representing a JSON object with a more flexible structure.
	// The $dataSchema below is used to describe the expected structure of $data.

	$dataSchema = array(
		"Basic"			=> array("DataType" => "object", "Schema" => array(
			"TermsPage"		=> array("DataType" => "string"),
			"ReceiptPage"	=> array("DataType" => "string"),
			"ShopBccEmail"	=> array("DataType" => "string")
		)),
		"MailTemplates"		=> array("DataType" => "object", "Schema" => array(
			"Confirmation"	=> array("DataType" => "string"),
			"Invoice"		=> array("DataType" => "string"),
			"Templates"		=> array("DataType" => "object[]", "Schema" => array(
				"Name"			=> array("DataType" => "string"),
				"Title"			=> array("DataType" => "string"),
				"Subject"		=> array("DataType" => "string"),
				"Content"		=> array("DataType" => "string")
			))
		)),
		"PaymentMethods"	=> array("DataType" => "object[]", "Schema" => array(
			"Module"			=> array("DataType" => "string"),
			"Title"				=> array("DataType" => "string"),
			"Enabled"			=> array("DataType" => "boolean"),
			"Settings"			=> array("DataType" => "object[]", "Schema" => array(
				"Title"				=> array("DataType" => "string"),
				"Value"				=> array("DataType" => "string")
			)),
		)),
		"CostCorrections"	=> array("DataType" => "object[]", "Schema" => array(
			"CostCorrection"	=> array("DataType" => "string"),
			"Vat"				=> array("DataType" => "string"),
			"Message"			=> array("DataType" => "string")
		)),
		"AdditionalData"	=> array("DataType" => "string"),
		"Identifiers"		=> array("DataType" => "object", "Schema" => array(
			"NextOrderId"		=> array("DataType" => "object", "Schema" => array(
				"Value"				=> array("DataType" => "number"),
				"Dirty"				=> array("DataType" => "boolean")
			)),
			"NextInvoiceId"		=> array("DataType" => "object", "Schema" => array(
				"Value"				=> array("DataType" => "number"),
				"Dirty"				=> array("DataType" => "boolean")
			))
		)),
		"Behaviour"				=> array("DataType" => "object", "Schema" => array(
			"CaptureBeforeInvoice"	=> array("DataType" => "boolean")
		))
	);

	SMTypeCheck::ValidateObjectArray($data, $dataSchema);
	SMTypeCheck::CheckMultiArray(__METHOD__, "pspmHardcodedSettings", $pspmHardcodedSettings, SMTypeCheckType::$String);

	// Config file

	$path = SMEnvironment::GetDataDirectory() . "/SMShop";
	$configuration = new SMConfiguration($path . "/Config.xml.php", true);

	if ($data["Identifiers"]["NextOrderId"]["Dirty"] === true || $data["Identifiers"]["NextInvoiceId"]["Dirty"] === true)
	{
		$state = new SMDataSource("SMShopState"); // IMPORTANT: Always lock on this DS when writing data! It contains important data such as NextOrderId and NextInvoiceId! ONLY lock for a very small amount of time - it is constantly being used!
		$state->Lock();

		$update = null;

		if ($data["Identifiers"]["NextOrderId"]["Dirty"] === true)
		{
			$update = new SMKeyValueCollection();
			$update["key"] = "NextOrderId";
			$update["value"] = (string)$data["Identifiers"]["NextOrderId"]["Value"];

			if ($state->Count("key = '" . $update["key"] . "'") === 0)
			{
				$state->Insert($update);
			}
			else
			{
				$state->Update($update, "key = '" . $update["key"] . "'");
			}
		}

		if ($data["Identifiers"]["NextInvoiceId"]["Dirty"] === true)
		{
			$update = new SMKeyValueCollection();
			$update["key"] = "NextInvoiceId";
			$update["value"] = (string)$data["Identifiers"]["NextInvoiceId"]["Value"];

			if ($state->Count("key = '" . $update["key"] . "'") === 0)
			{
				$state->Insert($update);
			}
			else
			{
				$state->Update($update, "key = '" . $update["key"] . "'");
			}
		}

		$state->Commit();
	}

	// Basic

	$configuration->SetEntry("TermsPage", $data["Basic"]["TermsPage"]);
	$configuration->SetEntry("ReceiptPage", $data["Basic"]["ReceiptPage"]);
	$configuration->SetEntry("ShopBccEmail", $data["Basic"]["ShopBccEmail"]);

	// Mail templates

	$writer = null;

	$configuration->SetEntry("ConfirmationMailTemplateExpression", $data["MailTemplates"]["Confirmation"]);
	$configuration->SetEntry("InvoiceMailTemplateExpression", $data["MailTemplates"]["Invoice"]);

	foreach ($data["MailTemplates"]["Templates"] as $mt)
	{
		$writer = new SMTextFileWriter($path . "/MailTemplates/" . $mt["Name"], SMTextFileWriteMode::$Overwrite);
		$writer->Write($mt["Subject"] . "\n" . $mt["Content"]);
	}

	// Payment methods

	$modules = "";

	// Save enabled payment methods

	foreach ($data["PaymentMethods"] as $pm)
	{
		$modules .= (($modules !== "") ? "#;#" : "");
		$modules .= $pm["Module"] . "#:#" . $pm["Title"] . "#:#" . (($pm["Enabled"] === true) ? "true" : "false");
	}

	$configuration->SetEntry("PaymentMethods", $modules);

	// PSPI module settings

	$php = "";
	$settings = "";

	foreach ($data["PaymentMethods"] as $pm)
	{
		$settings = "";
		foreach ($pm["Settings"] as $s)
			$settings .= (($settings !== "") ? ",\n" : "") . "\t\"" . $s["Title"] . "\" => \"" . str_replace("\"", "\\\"", str_replace("\$", "\\\$", $s["Value"])) . "\"";

		// Add settings hardcoded by SMShop, if any is defined
		if (isset($pspmHardcodedSettings[$pm["Module"]]) === true)
		{
			foreach ($pspmHardcodedSettings[$pm["Module"]] as $s => $v)
				$settings .= (($settings !== "") ? ",\n" : "") . "\t\"" . $s . "\" => \"" . str_replace("\"", "\\\"", str_replace("\$", "\\\$", $v)) . "\"";
		}

		$php = "";
		$php .= "<?php\n\n";
		$php .= "\$config = array\n(\n";
		$php .= $settings;
		$php .= "\n);\n\n";
		$php .= "?>\n";

		$writer = new SMTextFileWriter($path . "/../PSPI/" . $pm["Module"] . "/Config.php", SMTextFileWriteMode::$Overwrite);
		$writer->Write($php);
	}

	// Cost corrections

	for ($i = 1 ; $i <= count($data["CostCorrections"]) ; $i++)
	{
		if ($i > 3) break; // No more than 3 Cost Corrections are supported

		$configuration->SetEntry("CostCorrection" . $i, $data["CostCorrections"][$i - 1]["CostCorrection"]);
		$configuration->SetEntry("CostCorrectionVat" . $i, $data["CostCorrections"][$i - 1]["Vat"]);
		$configuration->SetEntry("CostCorrectionMessage" . $i, $data["CostCorrections"][$i - 1]["Message"]);
	}

	// Additional data

	$configuration->SetEntry("AdditionalData", $data["AdditionalData"]);

	// Behaviour

	$configuration->SetEntry("CaptureBeforeInvoice", $data["Behaviour"]["CaptureBeforeInvoice"] === true ? "true" : "false");

	// Commit

	$configuration->Commit();
}

// Execution

$json = SMEnvironment::GetJsonData();
$model = $json["Model"];
$props = $json["Properties"];
$operation = $json["Operation"];

// Some PSPM settings can be automatically supplied so there is no need
// to expose these settings and their associated complexity to the user.
// The settings below are obviously PSPM specific and will be hidden from
// the configuration interface.
$pspmHardcodedSettings = array(
	"QuickPay" => array(
		"Cancel URL" => SMEnvironment::GetExternalUrl()
	)
);

if ($operation === "Retrieve")
{
	echo SMShopGetConfiguration($pspmHardcodedSettings);
}
else if ($operation === "Update")
{
	SMShopSetConfiguration($props, $pspmHardcodedSettings);
}

?>
