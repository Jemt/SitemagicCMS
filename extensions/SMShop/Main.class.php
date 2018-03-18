<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmShop.class.php");
require_once(dirname(__FILE__) . "/FrmProducts.class.php");
require_once(dirname(__FILE__) . "/FrmBasket.class.php");
require_once(dirname(__FILE__) . "/FrmAdmin.class.php");
require_once(dirname(__FILE__) . "/FrmConfig.class.php");

class SMShop extends SMExtension
{
	private $name = null;
	private $lang = null;
	private $smMenuExists = false;
	private $smPagesExists = false;

	public function Init()
	{
		$this->name = $this->context->GetExtensionName();
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu");	// False if not installed or not enabled
		$this->smPagesExists = SMExtensionManager::ExtensionEnabled("SMPages");	// False if not installed or not enabled
	}

	public function InitComplete()
	{
		// Add basket and product categories to link pickers

		if ($this->smMenuExists === true && SMMenuLinkList::GetInstance()->GetReadyState() === true)
		{
			$ds = new SMDataSource("SMShopProducts");
			$products = $ds->Select("Category, CategoryId", "", "Category ASC");

			$menuLinkList = SMMenuLinkList::GetInstance();
			$added = array();

			foreach ($products as $prod)
			{
				if (in_array($prod["CategoryId"], $added, true) === true)
					continue;

				$menuLinkList->AddLink($this->getTranslation("Title"), $prod["Category"], "~/shop/" . $prod["CategoryId"]);
				$added[] = $prod["CategoryId"];
			}
		}

		if ($this->smPagesExists === true && SMPagesLinkList::GetInstance()->GetReadyState() === true)
		{
			$ds = new SMDataSource("SMShopProducts");
			$products = $ds->Select("Category, CategoryId", "", "Category ASC");

			$pagesLinkList = SMPagesLinkList::GetInstance();
			$added = array();

			foreach ($products as $prod)
			{
				if (in_array($prod["CategoryId"], $added, true) === true)
					continue;

				$pagesLinkList->AddLink($this->getTranslation("Title"), $prod["Category"], "~/shop/" . $prod["CategoryId"]);
				$added[] = $prod["CategoryId"];
			}
		}

		// Load JS and CSS resources

		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$JavaScript, SMExtensionManager::GetExtensionPath($this->name) . "/JSShop/JSShop.js?version=2" /* Increment version in JSShop.js too */, true);
		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$JavaScript, SMExtensionManager::GetExtensionPath($this->name) . "/JSShop/Fit.UI/Fit.UI.js", true);
		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$StyleSheet, SMExtensionManager::GetExtensionPath($this->name) . "/JSShop/Fit.UI/Fit.UI.css", true);

		// Prepare callbacks

		$basePath = SMEnvironment::GetInstallationPath(); // Use full path to prevent problems when calling WebServices under /shop/XYZ which would be redirected to / without preserving POST data (htaccess)
		$basePath .= (($basePath !== "/") ? "/" : "");

		$dsCallback = $basePath . SMExtensionManager::GetCallbackUrl($this->name, "Callbacks/DataSource");
		$fsCallback = $basePath . SMExtensionManager::GetCallbackUrl($this->name, "Callbacks/Files");
		$payCallback = $basePath . SMExtensionManager::GetCallbackUrl($this->name, "Callbacks/Payment");
		$cfgCallback = $basePath . SMExtensionManager::GetCallbackUrl($this->name, "Callbacks/Configuration");

		// Prepare language

		$langCode = SMLanguageHandler::GetSystemLanguage();
		$shopLang = ((SMFileSystem::FileExists(dirname(__FILE__) . "/JSShop/Languages/" . $langCode . ".js") === true) ? $langCode : "en");

		// Prepare cookie store

		$cookiePrefix = ((SMEnvironment::IsSubSite() === false) ? "SMRoot" : ""); // Prevent cookies on root site from causing naming conflicts with cookies on subsites
		$cookiePath = SMEnvironment::GetInstallationPath(); // Prevent /shop virtual directory from being used as cookie path when adding products to basket by forcing cookie path

		// Prepare payment modules

		$config = new SMConfiguration(SMEnvironment::GetDataDirectory() . "/SMShop/Config.xml.php");

		//$paymentMethodsStr = ((SMAttributes::GetAttribute("SMShopPaymentMethods") !== null) ? SMAttributes::GetAttribute("SMShopPaymentMethods") : "");
		$paymentMethodsStr = $config->GetEntry("PaymentMethods");

		if ($paymentMethodsStr !== "")
		{
			$paymentMethods = explode("#;#", $paymentMethodsStr);
			$paymentMethodsStr = "";
			$paymentModule = null;

			foreach ($paymentMethods as $pm)
			{
				$paymentModule = explode("#:#", $pm); // 0 = PSPI module name, 1 = title, 2 = enabled (true/false)

				if (count($paymentModule) !== 3)
					continue; // Not valid

				$paymentMethodsStr .= (($paymentMethodsStr !== "") ? ", " : "");
				$paymentMethodsStr .= "{ Module: '" . $paymentModule[0] . "', Title: '" . $paymentModule[1] . "', Enabled: " . $paymentModule[2] . " }";
			}
		}

		$pages = "";

		if ($this->smPagesExists === true)
		{
			foreach (SMPagesLoader::GetPages() as $page)
			{
				$pages .= (($pages !== "") ? ", " : "") . "{ Title: \"" . $page->GetFilename() . "\", Value: \"" . $page->GetUrl() . "\" }";
			}
		}

		// Configure JSShop

		$jsInit = "
		<script type=\"text/javascript\">
		JSShop.Settings.CostCorrection1 = \"" . $this->escapeJson($config->GetEntry("CostCorrection1")) . "\";
		JSShop.Settings.CostCorrectionVat1 = \"" . $this->escapeJson($config->GetEntry("CostCorrectionVat1")) . "\";
		JSShop.Settings.CostCorrectionMessage1 = SMStringUtilities.UnicodeDecode(\"" . $this->escapeJson($config->GetEntry("CostCorrectionMessage1")) . "\");
		JSShop.Settings.CostCorrection2 = \"" . $this->escapeJson($config->GetEntry("CostCorrection2")) . "\";
		JSShop.Settings.CostCorrectionVat2 = \"" . $this->escapeJson($config->GetEntry("CostCorrectionVat2")) . "\";
		JSShop.Settings.CostCorrectionMessage2 = SMStringUtilities.UnicodeDecode(\"" . $this->escapeJson($config->GetEntry("CostCorrectionMessage2")) . "\");
		JSShop.Settings.CostCorrection3 = \"" . $this->escapeJson($config->GetEntry("CostCorrection3")) . "\";
		JSShop.Settings.CostCorrectionVat3 = \"" . $this->escapeJson($config->GetEntry("CostCorrectionVat3")) . "\";
		JSShop.Settings.CostCorrectionMessage3 = SMStringUtilities.UnicodeDecode(\"" . $this->escapeJson($config->GetEntry("CostCorrectionMessage3")) . "\");
		JSShop.Settings.ConfigUrl = \"" . SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopConfig" . "\";
		JSShop.Settings.BasketUrl = \"" . SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopBasket" . "\";
		JSShop.Settings.TermsUrl = \"" . $config->GetEntry("TermsPage") . (($config->GetEntry("TermsPage") !== "") ? "?SMTemplateType=Basic&SMPagesDialog" : "") . "\";
		JSShop.Settings.PaymentUrl = \"" . $payCallback . "\";
		JSShop.Settings.PaymentMethods = [ " . $paymentMethodsStr . " ];
		JSShop.Settings.PaymentCaptureUrl = \"" . $payCallback . "&PaymentOperation=Capture\";
		JSShop.Settings.PaymentCancelUrl = \"" . $payCallback . "&PaymentOperation=Cancel\";
		JSShop.Settings.SendInvoiceUrl = \"" . $payCallback . "&PaymentOperation=Invoice\";
		JSShop.Settings.AdditionalData = " . (($config->GetEntry("AdditionalData") !== null && $config->GetEntry("AdditionalData") !== "") ? $config->GetEntry("AdditionalData") : "{}") . ";
		JSShop.Settings.Pages = [ " . $pages . "];

		JSShop.Language.Name = \"" . $shopLang . "\";

		JSShop.Cookies.Prefix(\"" . $cookiePrefix . "\" + JSShop.Cookies.Prefix());
		JSShop.Cookies.Path(\"" . $cookiePath . "\");

		JSShop.WebService.Products.Create = \"" . $dsCallback . "\";
		JSShop.WebService.Products.Retrieve = \"" . $dsCallback . "\";
		JSShop.WebService.Products.RetrieveAll = \"" . $dsCallback . "\";
		JSShop.WebService.Products.Update = \"" . $dsCallback . "\";
		JSShop.WebService.Products.Delete = \"" . $dsCallback . "\";

		JSShop.WebService.Files.Upload = \"" . $fsCallback . "\"; // Expected to respond with file path on server
		JSShop.WebService.Files.Remove = \"" . $fsCallback . "\";

		JSShop.WebService.Orders.Create = \"" . $dsCallback . "\";
		JSShop.WebService.Orders.Retrieve = \"" . $dsCallback . "\";
		JSShop.WebService.Orders.RetrieveAll = \"" . $dsCallback . "\";
		JSShop.WebService.Orders.Update = \"" . $dsCallback . "\";
		JSShop.WebService.Orders.Delete = \"" . $dsCallback . "\";

		JSShop.WebService.OrderEntries.Create = \"" . $dsCallback . "\";
		JSShop.WebService.OrderEntries.Retrieve = \"" . $dsCallback . "\";
		JSShop.WebService.OrderEntries.RetrieveAll = \"" . $dsCallback . "\";
		JSShop.WebService.OrderEntries.Update = \"" . $dsCallback . "\";
		JSShop.WebService.OrderEntries.Delete = \"" . $dsCallback . "\";

		JSShop.WebService.Configuration.Retrieve = \"" . $cfgCallback . "\";
		JSShop.WebService.Configuration.Update = \"" . $cfgCallback . "\";

		JSShop.Events.OnRequest = function(request, models, operation)
		{
			// Product model: Create URL friendly category name

			if ((operation === \"Create\" || operation === \"Update\") && Fit.Core.InstanceOf(models[0], JSShop.Models.Product) === true)
			{
				var data = request.GetData();
				var properties = data.Properties;

				var category = properties[\"Category\"];
				var catId = category;

				catId = catId.replace(/ /g, \"-\"); // Replace spaces with dashes

				// Support alternatives to danish characters
				catId = catId.replace(/Æ/g, \"Ae\");
				catId = catId.replace(/æ/g, \"ae\");
				catId = catId.replace(/Ø/g, \"Eo\");
				catId = catId.replace(/ø/g, \"oe\");
				catId = catId.replace(/Å/g, \"Aa\");
				catId = catId.replace(/å/g, \"aa\");

				catId = catId.replace(/[^A-Za-z0-9_-]/g, \"\"); // Remove invalid characters (^ in a range means NOT)

				if (catId !== category)
				{
					// Two different categories can end up with the same Category ID, e.g. XæYæZ and X.Y.Z = XYZ.
					// This will especially be true if categories only consists of invalid characters (unicode),
					// in which case the Category ID will now be empty. Therefore, a hash code representing the
					// name of the category is used to create a unique and valid Category ID.

					var hash = Fit.String.Hash(category);
					catId = ((catId !== \"\") ? catId : \"cat\") + \"-\" + ((hash < 0) ? \"m\" : \"\") + Math.abs(hash);
				}

				properties[\"CategoryId\"] = catId; // NOTICE: CategoryId is NOT defined in Product model, only here in JSON data

				request.SetData(data);
			}
		};

		JSShop.Events.OnError = function(request, models, operation)
		{
			Fit.Controls.Dialog.Alert('WebService communication failed (' + operation + '):<br><br>' + request.GetResponseText().replace(\"<pre>\", \"<pre style='overflow: auto'>\"));
		};
		</script>
		";

		SMEnvironment::GetMasterTemplate()->AddToHeadSection($jsInit);
	}

	public function Render()
	{
		if (SMEnvironment::GetQueryValue("SMShopEditProducts") !== null)
		{
			if (SMAuthentication::Authorized() === false)
				SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

			$frm = new SMShopFrmShop($this->context);
			return $frm->Render();
		}
		else if (SMEnvironment::GetQueryValue("SMShopBasket") !== null)
		{
			$frm = new SMShopFrmBasket($this->context);
			return $frm->Render();
		}
		else if (SMEnvironment::GetQueryValue("SMShopAdministration") !== null)
		{
			if (SMAuthentication::Authorized() === false)
				SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

			$frm = new SMShopFrmAdmin($this->context);
			return $frm->Render();
		}
		else if (SMEnvironment::GetQueryValue("SMShopConfig") !== null)
		{
			if (SMAuthentication::Authorized() === false)
				SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

			$frm = new SMShopFrmConfig($this->context);
			return $frm->Render();
		}
		else
		{
			$frm = new SMShopFrmProducts($this->context);
			return $frm->Render();
		}
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$menuItem = SMMenuManager::GetInstance()->GetChild("SMMenuContent");
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($menuItem !== null)
				$menuItem->AddChild(new SMMenuItem($this->name, $this->getTranslation("Products"), SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopEditProducts"));
			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem($this->name . "Adm", $this->getTranslation("Title"), SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopAdministration"));
			//if ($adminItem !== null)
			//	$adminItem->AddChild(new SMMenuItem($this->name . "Cfg", $this->getTranslation("ConfigTitle"), SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopConfig"));
		}
	}

	private function escapeJson($str)
	{
		$str = str_replace("\\", "\\\\", $str);
		$str = str_replace("\r", "", $str);
		$str = str_replace("\n", "\\n", $str);
		$str = str_replace("\t", "\\t", $str);
		$str = str_replace("\"", "\\\"", $str);

		return $str;
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler($this->name);

		return $this->lang->GetTranslation($key);
	}
}

?>
