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

	public function PreInit()
	{
		$this->name = $this->context->GetExtensionName();

		// Ensure folders and configuration files

		if (SMExtensionManager::GetExecutingExtension() === $this->name)
		{
			// Ensure folders

			$dataDir = SMEnvironment::GetDataDirectory();

			$dirs = array(
				$dataDir . "/SMShop",
				$dataDir . "/SMShop/MailTemplates",
				$dataDir . "/SMShop/PDFs",
				$dataDir . "/PSPI"
			);

			foreach ($dirs as $dir)
			{
				if (SMFileSystem::FolderExists($dir) === false)
				{
					SMFileSystem::CreateFolder($dir);
				}
			}

			// Ensure e-mail templates

			$extDir = SMExtensionManager::GetExtensionPath($this->context->GetExtensionName());

			if (SMFileSystem::FileExists($dataDir . "/SMShop/MailTemplates/OrderConfirmation.html") === false)
			{
				SMFileSystem::Copy($extDir . "/MailTemplates/OrderConfirmation.html", $dataDir . "/SMShop/MailTemplates/OrderConfirmation.html");
			}

			if (SMFileSystem::FileExists($dataDir . "/SMShop/MailTemplates/Invoice.html") === false)
			{
				SMFileSystem::Copy($extDir . "/MailTemplates/Invoice.html", $dataDir . "/SMShop/MailTemplates/Invoice.html");
			}

			// Ensure PSPM folders and files

			$pspiPath = $extDir . "/PSPI";

			foreach (SMFileSystem::GetFolders($pspiPath) as $pspm)
			{
				if (SMFileSystem::FolderExists($dataDir . "/PSPI/" . $pspm) === false)
				{
					SMFileSystem::CreateFolder($dataDir . "/PSPI/" . $pspm);
					SMFileSystem::Copy($pspiPath . "/" . $pspm . "/Config.php", $dataDir . "/PSPI/" . $pspm . "/Config.php");
				}
			}

			if (SMFileSystem::FileExists($dataDir . "/PSPI/Config.php") === false)
			{
				SMFileSystem::Copy($pspiPath . "/ConfigOverride.php", $dataDir . "/PSPI/Config.php");
			}
		}
	}

	public function Init()
	{
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu");	// False if not installed or not enabled
		$this->smPagesExists = SMExtensionManager::ExtensionEnabled("SMPages");	// False if not installed or not enabled
	}

	public function InitComplete()
	{
		// Add basket and product categories to link pickers

		$urlRewrite = (SMAttributes::GetAttribute("SMUrlRewritingEnabled") === "true");

		if ($this->smMenuExists === true && SMMenuLinkList::GetInstance()->GetReadyState() === true)
		{
			$menuLinkList = SMMenuLinkList::GetInstance();
			$menuLinkList->AddLink($this->getTranslation("Title"), "# " . $this->lang->GetTranslation("Basket"), SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopBasket");

			$ds = new SMDataSource("SMShopProducts");
			$products = $ds->Select("Category, CategoryId", "", "Category ASC");

			$added = array();

			foreach ($products as $prod)
			{
				if (in_array($prod["CategoryId"], $added, true) === true)
					continue;

				if ($urlRewrite === true)
					$menuLinkList->AddLink($this->getTranslation("Title"), $prod["Category"], "~/shop/" . $prod["CategoryId"]);
				else
					$menuLinkList->AddLink($this->getTranslation("Title"), $prod["Category"], SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopCategory=" . $prod["CategoryId"]);

				$added[] = $prod["CategoryId"];
			}
		}

		if ($this->smPagesExists === true && SMPagesLinkList::GetInstance()->GetReadyState() === true)
		{
			$pagesLinkList = SMPagesLinkList::GetInstance();
			$pagesLinkList->AddLink($this->getTranslation("Title"), "# " . $this->lang->GetTranslation("Basket"), SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopBasket");

			$ds = new SMDataSource("SMShopProducts");
			$products = $ds->Select("Category, CategoryId", "", "Category ASC");

			$added = array();

			foreach ($products as $prod)
			{
				if (in_array($prod["CategoryId"], $added, true) === true)
					continue;

				if ($urlRewrite === true)
					$pagesLinkList->AddLink($this->getTranslation("Title"), $prod["Category"], "~/shop/" . $prod["CategoryId"]);
				else
					$pagesLinkList->AddLink($this->getTranslation("Title"), $prod["Category"], SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopCategory=" . $prod["CategoryId"]);

				$added[] = $prod["CategoryId"];
			}
		}

		// Load JS and CSS resources

		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$JavaScript, SMExtensionManager::GetExtensionPath($this->name) . "/JSShop/JSShop.js?CacheKey=" . SMEnvironment::GetVersion() . "&Debug=" . ((SMEnvironment::GetDebugEnabled() === true) ? "true" : "false"), true);
		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$JavaScript, SMExtensionManager::GetExtensionPath($this->name) . "/JSShop/Fit.UI/Fit.UI" . ((SMEnvironment::GetDebugEnabled() === false) ? ".min" : "") . ".js?Fit&CacheKey=" . SMEnvironment::GetVersion(), true);
		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$StyleSheet, SMExtensionManager::GetExtensionPath($this->name) . "/JSShop/Fit.UI/Fit.UI" . ((SMEnvironment::GetDebugEnabled() === false) ? ".min" : "") . ".css?CacheKey=" . SMEnvironment::GetVersion(), true);

		// Prepare callbacks

		$basePath = SMEnvironment::GetRequestPath();
		$basePath .= (($basePath !== "/") ? "/" : "");

		$dsCallback = $basePath . SMExtensionManager::GetCallbackUrl($this->name, "Callbacks/DataSource");
		$fsCallback = $basePath . SMExtensionManager::GetCallbackUrl($this->name, "Callbacks/Files");
		$payCallback = $basePath . SMExtensionManager::GetCallbackUrl($this->name, "Callbacks/Payment");
		$cfgCallback = $basePath . SMExtensionManager::GetCallbackUrl($this->name, "Callbacks/Configuration");

		// Prepare language

		$langCode = SMLanguageHandler::GetSystemLanguage();
		$shopLang = ((SMFileSystem::FileExists(dirname(__FILE__) . "/JSShop/Languages/" . $langCode . ".js") === true) ? $langCode : "en");

		// Prepare cookie store
		// NOTICE: Cookies are separated between the main site (e.g. /Sitemagic) and subsites (e.g. /Sitemagic/sites/test). But if an installation of
		// Sitemagic (e.g. /Sitemagic) contains another full installation of Sitemagic in a subfolder (e.g. /Sitemagic/AnotherSitemagic), then any cookie
		// set on the main site (/Sitemagic) will be accessible to the nested Sitemagic installation (/Sitemagic/AnotherSitemagic) - cookies are inherited from top to bottom.
		// So adding a product to the basket on /Sitemagic will result in the same product being found in the basket for /Sitemagic/AnotherSitemagic. If the product databases
		// are identical, then the product will show up just fine on the nested site, otherwise an error will be shown because the product details cannot be loaded.
		// If the nested site defines the basket cookie first, then the main site's basket cookie won't affect the nested site since it's own cookies take precedence.
		// Obviously this problem could be solved if each Sitemagic installation had unique cookie prefixes rather than just "SMRoot", but we don't want to waste additional characters since cookie storage is limited.

		$cookiePrefix = ((SMEnvironment::IsSubSite() === false) ? "SMRoot" : ""); // Prevent cookies on root site from causing naming conflicts with cookies on subsites
		$cookiePath = $basePath; // Used to prevent /shop virtual directory from being used as cookie path when adding products to basket - forcing cookie path to Sitemagic installation path

		// Prepare payment modules

		$config = new SMConfiguration(SMEnvironment::GetDataDirectory() . "/SMShop/Config.xml.php");

		$paymentMethodsStr = $config->GetEntryOrEmpty("PaymentMethods");
		$paymentMethodsAvailable = false;

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

				if ($paymentModule[2] === "true")
					$paymentMethodsAvailable = true;

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
		// JSShop.Settings is not the same that can be resolved using JSShop.Models.Config.Current.
		// For instance JSShop.Settings.AdditionalData is an object while JSShop.Models.Config.Current.AdditionalData() returns a string.
		// JSShop.Settings.TermsUrl returns a URL with arguments while JSShop.Models.Config.Current.Basic().TermsPage is just a filename (e.g. Terms.html).
		// JSShop.Settings contain various settings not found in the Config model such as ConfigUrl, BasketUrl, PaymentUrl, Pages, etc.
		// In addition, the JSShop.Settings object is immediately available while the Config model needs to be loaded asynchronously.
		// In fact, JSShop could be used without the Config model - it is only used by the Config presenter.
		// Furthermore the Config model also exposes configuration needed by the backend that is not exposed though JSShop.Settings, for instance MailTemplates.

		$jsInit = "
		<script type=\"text/javascript\">
		JSShop.Settings.CostCorrection1 = \"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrection1")) . "\";
		JSShop.Settings.CostCorrectionVat1 = \"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrectionVat1")) . "\";
		JSShop.Settings.CostCorrectionMessage1 = SMStringUtilities.UnicodeDecode(\"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrectionMessage1")) . "\");
		JSShop.Settings.CostCorrection2 = \"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrection2")) . "\";
		JSShop.Settings.CostCorrectionVat2 = \"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrectionVat2")) . "\";
		JSShop.Settings.CostCorrectionMessage2 = SMStringUtilities.UnicodeDecode(\"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrectionMessage2")) . "\");
		JSShop.Settings.CostCorrection3 = \"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrection3")) . "\";
		JSShop.Settings.CostCorrectionVat3 = \"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrectionVat3")) . "\";
		JSShop.Settings.CostCorrectionMessage3 = SMStringUtilities.UnicodeDecode(\"" . $this->escapeJson($config->GetEntryOrEmpty("CostCorrectionMessage3")) . "\");
		JSShop.Settings.ConfigUrl = \"" . SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopConfig" . "\";
		JSShop.Settings.BasketUrl = \"" . SMExtensionManager::GetExtensionUrl($this->name) . "&SMShopBasket" . "\";
		JSShop.Settings.TermsUrl = \"" . $config->GetEntryOrEmpty("TermsPage") . (($config->GetEntryOrEmpty("TermsPage") !== "") ? ((strpos($config->GetEntryOrEmpty("TermsPage"), "?") === false) ? "?" : "&") . "SMTemplateType=Basic&SMPagesDialog" : "") . "\";
		JSShop.Settings.ReceiptUrl = \"" . $config->GetEntryOrEmpty("ReceiptPage") . "\";
		JSShop.Settings.PaymentUrl = \"" . (($paymentMethodsAvailable === true) ? $payCallback : "") . "\";
		JSShop.Settings.PaymentMethods = [ " . $paymentMethodsStr . " ];
		JSShop.Settings.PaymentCaptureUrl = \"" . (($paymentMethodsAvailable === true) ? $payCallback . "&PaymentOperation=Capture" : "") . "\";
		JSShop.Settings.PaymentCancelUrl = \"" . (($paymentMethodsAvailable === true) ? $payCallback . "&PaymentOperation=Cancel" : "") . "\";
		JSShop.Settings.SendInvoiceUrl = \"" . $payCallback . "&PaymentOperation=Invoice\";
		JSShop.Settings.AdditionalData = " . (($config->GetEntryOrEmpty("AdditionalData") !== "") ? $config->GetEntryOrEmpty("AdditionalData") : "{}") . ";
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

		JSShop.WebService.Tags.Create = \"" . $dsCallback . "\";
		JSShop.WebService.Tags.Retrieve = \"" . $dsCallback . "\";
		JSShop.WebService.Tags.RetrieveAll = \"" . $dsCallback . "\";
		JSShop.WebService.Tags.Update = \"" . $dsCallback . "\";
		JSShop.WebService.Tags.Delete = \"" . $dsCallback . "\";

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
		else // SMShopCategory
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
		}
	}

	private function escapeJson($str)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);

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
