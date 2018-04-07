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
	$path = SMEnvironment::GetDataDirectory() . "/SMShop";
	$configuration = new SMConfiguration($path . "/Config.xml.php");
	$files = SMFileSystem::GetFiles($path);

	$mailTemplates = "";
	$reader = null;
	$fileData = null;
	$subject = null;
	$content = null;

	// Load e-mail templates

	foreach ($files as $file)
	{
		if (strpos($file, ".html") === strlen($file) - 5)
		{
			$reader = new SMTextFileReader($path . "/" . $file);
			$content = $reader->ReadAll();
			$fileData = explode("\n", $content);

			$subject = $fileData[0];
			$content = implode("\n", array_slice($fileData, 1));

			$mailTemplates .= (($mailTemplates !== "") ? ", " : "");
			$mailTemplates .= '{ "Title": "' . $file . '", "Subject": "' . SMStringUtilities::JsonEncode($subject) . '", "Content": "' . SMStringUtilities::JsonEncode($content) . '" }';
		}
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
	"AdditionalData": "' . SMStringUtilities::JsonEncode($configuration->GetEntryOrEmpty("AdditionalData")) . '"';

	return "{" . $json . "\n}";
}

function SMShopSetConfiguration($data, $pspmHardcodedSettings)
{
	$path = SMEnvironment::GetDataDirectory() . "/SMShop";
	$configuration = new SMConfiguration($path . "/Config.xml.php", true);

	// Basic

	if (isset($data["Basic"]) === true)
	{
		if (isset($data["Basic"]["TermsPage"]) === true)
		{
			$configuration->SetEntry("TermsPage", $data["Basic"]["TermsPage"]);
		}

		if (isset($data["Basic"]["ReceiptPage"]) === true)
		{
			$configuration->SetEntry("ReceiptPage", $data["Basic"]["ReceiptPage"]);
		}

		if (isset($data["Basic"]["ShopBccEmail"]) === true)
		{
			$configuration->SetEntry("ShopBccEmail", $data["Basic"]["ShopBccEmail"]);
		}
	}

	// Mail templates

	$writer = null;

	if (isset($data["MailTemplates"]) === true)
	{
		if (isset($data["MailTemplates"]["Confirmation"]) === true)
		{
			$configuration->SetEntry("ConfirmationMailTemplateExpression", $data["MailTemplates"]["Confirmation"]);
		}

		if (isset($data["MailTemplates"]["Invoice"]) === true)
		{
			$configuration->SetEntry("InvoiceMailTemplateExpression", $data["MailTemplates"]["Invoice"]);
		}

		if (isset($data["MailTemplates"]["Templates"]) === true)
		{
			foreach ($data["MailTemplates"]["Templates"] as $mt)
			{
				if (isset($mt["Title"]) === false || isset($mt["Subject"]) === false || isset($mt["Content"]) === false)
					continue;

				$writer = new SMTextFileWriter($path . "/" . $mt["Title"], SMTextFileWriteMode::$Overwrite);
				$writer->Write($mt["Subject"] . "\n" . $mt["Content"]);
			}
		}
	}

	// Payment methods

	$modules = "";

	if (isset($data["PaymentMethods"]) === true)
	{
		// Save enabled payment methods

		foreach ($data["PaymentMethods"] as $pm)
		{
			if (isset($pm["Module"]) === false || isset($pm["Title"]) === false || isset($pm["Enabled"]) === false)
				continue;

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

			// Add settings hardcoded by SMShop
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
	}

	// Cost corrections

	if (isset($data["CostCorrections"]) === true)
	{
		for ($i = 1 ; $i <= count($data["CostCorrections"]) ; $i++)
		{
			if ($i > 3) break; // No more than 3 Cost Corrections are supported

			$configuration->SetEntry("CostCorrection" . $i, $data["CostCorrections"][$i - 1]["CostCorrection"]);
			$configuration->SetEntry("CostCorrectionVat" . $i, $data["CostCorrections"][$i - 1]["Vat"]);
			$configuration->SetEntry("CostCorrectionMessage" . $i, $data["CostCorrections"][$i - 1]["Message"]);
		}
	}

	// Additional data

	if (isset($data["AdditionalData"]) === true)
	{
		$configuration->SetEntry("AdditionalData", $data["AdditionalData"]);
	}

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
