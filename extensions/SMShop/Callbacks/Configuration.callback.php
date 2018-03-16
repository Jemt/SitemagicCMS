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

function SMShopGetConfiguration()
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
	if ($configuration->GetEntry("PaymentMethods") !== "")
	{
		$modules = explode("#;#", $configuration->GetEntry("PaymentMethods"));
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
			$paymentModuleSettings .= (($paymentModuleSettings !== "") ? ", " : "");
			$paymentModuleSettings .= '{ "Title": "' . SMStringUtilities::JsonEncode($key) . '", "Value": "' . SMStringUtilities::JsonEncode($val) . '" }';
		}

		// Register PSPI module

		$paymentModulesStr .= (($paymentModulesStr !== "") ? ", " : "");
		$paymentModulesStr .= '{ "Module": "' . $pm . '", "Title": "' . ((isset($paymentModulesConfigured[$pm]) === true) ? SMStringUtilities::JsonEncode($paymentModulesConfigured[$pm]["Title"]) : "") . '", "Enabled": ' . ((isset($paymentModulesConfigured[$pm]) === true && isset($paymentModulesConfigured[$pm]["Enabled"]) === true) ? "true" : "false") . ', "Settings": [ ' . $paymentModuleSettings . ' ] }';
	}

	// Retrieve cost correction expressions

	$costCorrections = "";

	for ($i = 1 ; $i <= 3 ; $i++)
	{
		$costCorrections .= (($costCorrections !== "") ? ", " : "");
		$costCorrections .= '{ "CostCorrection": "' . SMStringUtilities::JsonEncode($configuration->GetEntry("CostCorrection" . $i)) . '", "Vat": "' . SMStringUtilities::JsonEncode($configuration->GetEntry("CostCorrectionVat" . $i)) . '", "Message": "' . SMStringUtilities::JsonEncode($configuration->GetEntry("CostCorrectionMessage" . $i)) . '" }';
	}

	// Return data as JSON

	$json = '
	"Basic": { "TermsPage": "' . $configuration->GetEntry("TermsPage") . '", "ReceiptPage": "' . $configuration->GetEntry("ReceiptPage") . '", "ShopBccEmail": "' . $configuration->GetEntry("ShopBccEmail") . '" },
	"MailTemplates":
	{
		"Confirmation": ' . (($configuration->GetEntry("ConfirmationMailTemplateExpression") !== null) ? '"' . SMStringUtilities::JsonEncode($configuration->GetEntry("ConfirmationMailTemplateExpression")) . '"' : "null") . ',
		"Invoice": ' . (($configuration->GetEntry("InvoiceMailTemplateExpression") !== null) ? '"' . SMStringUtilities::JsonEncode($configuration->GetEntry("InvoiceMailTemplateExpression")) . '"' : "null") . ',
		"Templates": [' . $mailTemplates . ']
	},
	"PaymentMethods": [' . $paymentModulesStr . '],
	"CostCorrections": [ ' . $costCorrections . ' ],
	"PriceIndex": "' . (($configuration->GetEntry("PriceIndex") !== null) ? SMStringUtilities::JsonEncode($configuration->GetEntry("PriceIndex")) : "") . '"';

	return "{" . $json . "\n}";
}

function SMShopSetConfiguration($data)
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
		for ($i = 1 ; $i <= 3 ; $i++)
		{
			$configuration->SetEntry("CostCorrection" . $i, $data["CostCorrections"][$i - 1]["CostCorrection"]);
			$configuration->SetEntry("CostCorrectionVat" . $i, $data["CostCorrections"][$i - 1]["Vat"]);
			$configuration->SetEntry("CostCorrectionMessage" . $i, $data["CostCorrections"][$i - 1]["Message"]);
		}
	}

	// Price index

	if (isset($data["PriceIndex"]) === true)
	{
		$configuration->SetEntry("PriceIndex", $data["PriceIndex"]);
	}

	$configuration->Commit();
}

// Execution

$json = SMEnvironment::GetJsonData(); //SMShopGetJsonData();
$model = $json["Model"];
$props = $json["Properties"];
$operation = $json["Operation"];

if ($operation === "Retrieve")
{
	echo SMShopGetConfiguration();
}
else if ($operation === "Update")
{
	SMShopSetConfiguration($props);
}

?>
