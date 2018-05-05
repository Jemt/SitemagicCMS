<?php

/// <container name="@FrontPage">
/// 	Welcome to the online documentation for Sitemagic CMS.
///
/// 	Sitemagic CMS is an amazing Content Management System - free, easy to use
/// 	and install, super reliable, highly customizable, and fully extendable.
/// 	It even runs without a database, although MySQL support is available for
/// 	huge websites.
///
/// 	Sitemagic CMS is available for download at http://sitemagic.org
///
/// 	This is the raw API documentation. For documentation on how to get
/// 	started developing for Sitemagic CMS, please go to http://sitemagic.org/developers
/// </container>

/// <container name="base">
/// 	Base contains all the basic functionality of Sitemagic CMS.
/// 	It is the underlaying framework that helps you and us create great
/// 	functionality with less code. It helps us ensure consistency and better
/// 	quality.
/// </container>

/// <container name="gui">
/// 	GUI contains the server side GUI controls such as input controls,
/// 	drop down menus, tree menu, checkbox list, grid control etc.
/// </container>

/// <container name="client">
/// 	Client contains the client side functionality for Sitemagic CMS.
/// 	It has become a small JavaScript library with common browser functionality.
/// </container>


require_once(dirname(__FILE__) . "/SMTypeCheck.classes.php");
require_once(dirname(__FILE__) . "/SMKeyValue.classes.php");
require_once(dirname(__FILE__) . "/SMFileSystem.class.php");
require_once(dirname(__FILE__) . "/SMEnvironment.class.php");
require_once(dirname(__FILE__) . "/SMLog.class.php");
require_once(dirname(__FILE__) . "/SMAttributes.class.php");
require_once(dirname(__FILE__) . "/SMConfiguration.class.php");
require_once(dirname(__FILE__) . "/SMAuthentication.class.php");
require_once(dirname(__FILE__) . "/SMTemplate.classes.php");
require_once(dirname(__FILE__) . "/SMForm.classes.php");
require_once(dirname(__FILE__) . "/SMSqlCommon.classes.php");
require_once(dirname(__FILE__) . "/SMDataSource.classes.php");
require_once(dirname(__FILE__) . "/SMImageProvider.classes.php");
require_once(dirname(__FILE__) . "/SMLanguageHandler.class.php");
require_once(dirname(__FILE__) . "/SMRandom.class.php");
require_once(dirname(__FILE__) . "/SMTextFile.classes.php");
require_once(dirname(__FILE__) . "/SMRequest.classes.php");
require_once(dirname(__FILE__) . "/SMStringUtilities.classes.php");
require_once(dirname(__FILE__) . "/SMUtilities.classes.php");
require_once(dirname(__FILE__) . "/SMMail.classes.php");
require_once(dirname(__FILE__) . "/SMExtensionManager.class.php");
require_once(dirname(__FILE__) . "/SMExtension.class.php");
require_once(dirname(__FILE__) . "/SMContext.class.php");
require_once(dirname(__FILE__) . "/gui/SMTreeMenu/SMTreeMenu.classes.php");
require_once(dirname(__FILE__) . "/gui/SMInput/SMInput.classes.php");
require_once(dirname(__FILE__) . "/gui/SMLinkButton/SMLinkButton.class.php");
require_once(dirname(__FILE__) . "/gui/SMOptionList/SMOptionList.classes.php");
require_once(dirname(__FILE__) . "/gui/SMGrid/SMGrid.class.php");
require_once(dirname(__FILE__) . "/gui/SMFieldset/SMFieldset.classes.php");
require_once(dirname(__FILE__) . "/gui/SMCheckboxList/SMCheckboxList.classes.php");
require_once(dirname(__FILE__) . "/gui/SMNotify/SMNotify.class.php");


class SMController
{
	private $config;
	private $template;
	private $form;
	private $extensionInstances;

	public function __construct()
	{
		set_error_handler("SMErrorHandler");
		set_exception_handler("SMExceptionHandler");
		$this->disableMagicQuotes();

		$this->config = SMEnvironment::GetConfiguration();

		$debug = $this->config->GetEntry("Debug");
		SMTypeCheck::SetEnabled(($debug !== null && strtolower($debug) === "true"));

		$this->template = null;
		$this->form = null;
		$this->extensionInstances = array();

		$this->initialization();
	}

	private function initialization()
	{
		SMEnvironment::Initialize(); // Initializes cookies and sessions (calls session_name(..) and session_start())

		$timezone = $this->config->GetEntry("DefaultTimeZoneOverride");
		if ($timezone !== null && $timezone !== "")
		{
			date_default_timezone_set($timezone);
		}
		else
		{
			// Prevent annoying warning throughout Sitemagic:
			// Strict Standards: date() [function.date]: It is not safe to rely on the system's timezone settings
			date_default_timezone_set("UTC");
		}
	}

	public function Execute()
	{
		// Handle AJAX callbacks
		if ($this->handleCallback() === true)
		{
			$this->commitCachedData();
			return;
		}

		// Handle normal requests

		$this->autoExecuteExtensions("PreInit");
		$this->autoExecuteExtensions("Init");

		// Initialize SMTemplate and SMForm

		$this->template = $this->loadTemplate();
		$this->form = new SMForm();

		SMEnvironment::SetMasterTemplate($this->template);
		SMEnvironment::SetFormInstance($this->form);

		// Register meta tags, StyleSheets, and JavaScript

		$isHtml5 = $this->template->IsHtml5();
		$charSet = (($isHtml5 === false) ? "ISO-8859-1" : "windows-1252");
		$basicCss = SMTemplateInfo::GetBasicCssFile(SMTemplateInfo::GetCurrentTemplate());				// basic.css, style.css (preferred), or null
		$basicCss = (($basicCss !== null) ? $basicCss . "?v=" . SMEnvironment::GetVersion() : null);
		$indexCss = SMTemplateInfo::GetTemplateCssFile(SMTemplateInfo::GetCurrentTemplate());			// index.css, style.css (preferred), or null
		$indexCss = (($indexCss !== null) ? $indexCss . "?v=" . SMEnvironment::GetVersion() : null);
		$overrideCss = SMTemplateInfo::GetOverrideCssFile(SMTemplateInfo::GetCurrentTemplate());		// override.css or null
		$overrideCss = (($overrideCss !== null) ? $overrideCss . "?v=" . SMEnvironment::GetVersion() . "&amp;c=" . SMEnvironment::GetClientCacheKey() : null);

		$head = "";
		$head .= "\n\t<meta name=\"generator\" content=\"Sitemagic CMS\">";
		//$head .= "\n\t<meta http-equiv=\"content-type\" content=\"text/html;charset=" . $charSet . "\">";
		$head .= "\n\t<link rel=\"shortcut icon\" type=\"images/x-icon\" href=\"favicon.ico\">";
		$head .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"base/gui/gui.css?ver=" . SMEnvironment::GetVersion() . "\">";
		if ($basicCss !== null)
			$head .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $basicCss . "\">";
		if ($basicCss !== $indexCss && $indexCss !== null && SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal)
			$head .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $indexCss . "\">";
		if ($overrideCss !== null)
			$head .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $overrideCss . "\">";
		$this->template->AddToHeadSection($head, true);

		// Register Client Library

		$scripts = "";
		$scripts .= "\n\t<script type=\"text/javascript\" src=\"base/gui/json2.js?ver=" . SMEnvironment::GetVersion() . "\"></script>"; // JSON.parse(..) and JSON.stringify(..) for IE7
		$scripts .= "\n\t<script type=\"text/javascript\">" . $this->getClientLanguage() . "</script>";
		$scripts .= "\n\t<script type=\"text/javascript\">" . $this->getClientEnvironment() . "</script>";
		$scripts .= "\n\t<script type=\"text/javascript\" src=\"base/gui/SMClient.js?ver=" . SMEnvironment::GetVersion() . "\"></script>";
		if ($this->config->GetEntry("SMWindowLegacyMode") !== null && strtolower($this->config->GetEntry("SMWindowLegacyMode")) === "true")
			$scripts .= "\n\t<script type=\"text/javascript\">SMWindow.LegacyMode = true;</script>";
		$this->template->AddToHeadSection($scripts, true);

		// Auto load template enhancements (CSS and JS files)

		$templateType = ((SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal) ? SMTemplateType::$Normal : SMTemplateType::$Basic);
		$enhancements = SMTemplateInfo::GetTemplateEnhancementFiles(SMTemplateInfo::GetCurrentTemplate(), $templateType);

		foreach ($enhancements as $enhancement)
		{
			if (SMStringUtilities::EndsWith(strtolower($enhancement), ".css") === true)
				$this->template->RegisterResource(SMTemplateResource::$StyleSheet, SMEnvironment::GetTemplatesDirectory() . "/" . SMTemplateInfo::GetCurrentTemplate() . "/" . $enhancement);
			else if (SMStringUtilities::EndsWith(strtolower($enhancement), ".js") === true)
				$this->template->RegisterResource(SMTemplateResource::$JavaScript, SMEnvironment::GetTemplatesDirectory() . "/" . SMTemplateInfo::GetCurrentTemplate() . "/" . $enhancement);
		}

		// Continue life cycle

		$this->autoExecuteExtensions("InitComplete");
		$this->autoExecuteExtensions("PreRender");

		// Execute privileged extension

		$extension = SMExtensionManager::GetExecutingExtension();
		$extensionContent = (($extension !== null && $extension !== "") ? $this->loadExtension($extension) : "");

		// Continue life cycle

		$this->autoExecuteExtensions("RenderComplete");
		$this->autoExecuteExtensions("PreTemplateUpdate");

		// Render SMForm instance to template

		if ($this->form->GetRender() === true)
			$this->template->SetBodyContent("\n" . $this->form->RenderStart() . $this->template->GetBodyContent() . $this->form->RenderEnd() . "\n");

		// Replace Sitemagic specific place holders

		$this->template->ReplaceTag(new SMKeyValue("Extension", $extensionContent));
		$this->template->ReplaceTag(new SMKeyValue("Version", (string)SMEnvironment::GetVersion()));	// Useful to avoid caching of JS and CSS (<link rel="stylesheet" type="text/css" href="templates/Default/index.css?{[Version]}">)
		$this->template->ReplaceTag(new SMKeyValue("RequestId", SMRandom::CreateGuid()));				// Useful to avoid caching of JS and CSS (<link rel="stylesheet" type="text/css" href="templates/Default/index.css?{[RequestId]}">)
		$this->template->ReplaceTag(new SMKeyValue("TemplateType", ((SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal) ? SMTemplateType::$Normal : SMTemplateType::$Basic)));
		$this->template->ReplaceTag(new SMKeyValue("TemplatesDirectory", SMEnvironment::GetTemplatesDirectory()));
		$this->template->ReplaceTag(new SMKeyValue("CurrentTemplate", SMTemplateInfo::GetCurrentTemplate()));
		$this->template->ReplaceTag(new SMKeyValue("CurrentTemplatePath", SMEnvironment::GetTemplatesDirectory() . "/" . SMTemplateInfo::GetCurrentTemplate()));
		$this->template->ReplaceTag(new SMKeyValue("ImagesDirectory", SMEnvironment::GetImagesDirectory()));
		$this->template->ReplaceTag(new SMKeyValue("CurrentImageTheme", SMImageProvider::GetImageTheme()));
		$this->template->ReplaceTag(new SMKeyValue("CurrentImageThemePath", SMEnvironment::GetImagesDirectory() . "/" . SMImageProvider::GetImageTheme()));
		$this->template->ReplaceTag(new SMKeyValue("Language", SMLanguageHandler::GetSystemLanguage()));

		// Convert HTML4 compliant markup to HTML5 compliant markup

		if ($isHtml5 === true)
		{
			// Remove type attribute from script tags - https://regex101.com/r/FO5v8L/2
			$this->template->SetContent(preg_replace('/(<script.*) type=([\'"]).*?\2(.*?>)/m', '${1}${3}', $this->template->GetContent()));
		}

		// Continue life cycle

		$this->autoExecuteExtensions("TemplateUpdateComplete");
		$this->autoExecuteExtensions("PreOutput");

		// Clean up template

		$this->template->RemoveRepeatingBlocks();
		$this->template->RemovePlaceholders();

		// Send result to client

		$this->setHeaders($charSet);
		echo $this->template->GetContent();

		// Continue life cycle

		$this->autoExecuteExtensions("OutputComplete");
		$this->autoExecuteExtensions("Unload");

		// Commit cached adta

		$this->commitCachedData();

		// End life cycle

		$this->autoExecuteExtensions("Finalize");
	}

	private function getClientLanguage()
	{
		$json = "";
		$lang = new SMLanguageHandler();
		$entries = $lang->GetTranslationKeys();

		foreach ($entries as $entry)
			if (SMStringUtilities::StartsWith($entry, "SMClient") === true)
				$json .= (($json !== "") ? ", " : "") . substr($entry, strlen("SMClient")) . " : \"" . $lang->GetTranslation($entry) . "\"";

		return "SMClientLanguageStrings = {" . $json . "};";
	}

	private function getClientEnvironment()
	{
		// From a client side perspective, all system folders are hosted under a subsite. Therefore sites/xyz is removed from all the folder paths.
		// For a subsite, the folders extensions, images, and base are actually found under the main site, but the root .htaccess file makes sure
		// to redirect any requests to these. Example: sites/demo/extensions/SMPages/editor.css => extensions/SMPages/editor.css
		// The templates folder and files folder may be either shared with the main site, or separated from the main site.
		// If the first is the case, a .htaccess file within these folders will make sure to perform relevant redirection.
		// Example: sites/templates/Sunrise/styles.css => templates/Sunrise/styles.css.
		// If the templates folder or files folder is separated from the main site, obviously the .htaccess is left out, causing
		// any contained files to be used when referenced. Example: sites/demo/templates/Sunrise/styles.css.
		// In the case where server side code uses SMEnvironment::Get***Directory() to obtain the path to any given folder hosted
		// under a subsite - e.g. SMEnvironment::GetTemplatesDirectory() - and this particular folder has been configured to act
		// as a folder separate from the main site, the path returned would be something like: sites/demo/templates.
		// However, if this path is used client side on a subsite, it would produce a request to sites/demo/templates from the
		// location sites/demo, meaning the browser would request the file like so: sites/demo/sites/demo/templates.
		// To simplify development, the root .htaccess file handles this by redirecting any request to e.g. sites/demo/sites/demo/templates
		// to sites/demo/templates, making it completely transparent to the developer, allowing for paths returned from any
		// SMEnvironment::Get***Directory() function to be used both server side and client side.

		$requestPath = $this->stripSubsite(SMEnvironment::GetRequestPath());
		$extensionsDir = $this->stripSubsite(SMEnvironment::GetExtensionsDirectory());
		$filesDir = $this->stripSubsite(SMEnvironment::GetFilesDirectory());
		$templatesDir = $this->stripSubsite(SMEnvironment::GetTemplatesDirectory());
		$dataDir = $this->stripSubsite(SMEnvironment::GetDataDirectory());
		$imagesDir = $this->stripSubsite(SMEnvironment::GetImagesDirectory());

		return "SMClientEnvironmentInfo = { IsSubSite: " . ((SMEnvironment::IsSubSite() === true) ? "true" : "false") . ", Dirs: { RequestPath: '" . $requestPath . "', Files: '" . $filesDir . "', Data: '" . $dataDir . "', Images: '" . $imagesDir . "', Templates: '" . $templatesDir . "', Extensions: '" . $extensionsDir . "' } };";
	}

	private function setHeaders($charSet)
	{
		SMTypeCheck::CheckObject(__METHOD__, "charSet", $charSet, SMTypeCheckType::$String);

		if ($this->isHeaderSet("Cache-Control") === false)
			header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate"); // Content is dynamic, never cache

		if ($this->isHeaderSet("Content-Type") === false)
			header("Content-Type: text/html; charset=" . $charSet);
	}

	private function isHeaderSet($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		$key = strtolower($key);

		foreach (headers_list() as $header)
			if (strpos(strtolower($header), $key) === 0)
				return true;

		return false;
	}

	private function stripSubsite($path)
	{
		$subsite = SMEnvironment::GetSubsiteDirectory();

		if ($subsite !== null && strpos($path, $subsite) === 0)
			return substr($path, strlen($subsite) + 1);
		return $path;

	}

	private function loadTemplate()
	{
		$templateName = SMTemplateInfo::GetCurrentTemplate();
		$file = "";

		if (SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal)
			$file = SMTemplateInfo::GetTemplateHtmlFile($templateName);
		else
			$file = SMTemplateInfo::GetBasicHtmlFile($templateName);

		if ($file === null)
			throw new Exception("Template file missing (" . $templateName . ")");

		return new SMTemplate($file);
	}

	private function handleCallback()
	{
		$cb = SMEnvironment::GetQueryValue("SMCallback", SMValueRestriction::$SafePath);

		if ($cb !== null)
		{
			$extension = SMExtensionManager::GetExecutingExtension(); // Value is safe to use - validated in GetExecutingExtension()

			if (SMExtensionManager::ExtensionEnabled($extension) === false)
				throw new Exception("Extension '" . $extension . "' is not accessible (not found or enabled) - unable to invoke callback");

			$callback = SMEnvironment::GetExtensionsDirectory() . "/" . $extension . "/" . $cb . ".callback.php";

			if (SMFileSystem::FileExists($callback) === false)
				throw new Exception("Callback '" . $cb . "' not found");

			$SMCallback = true; // Allow callback to determine whether it is invoked through Sitemagic

			// NOTICE:
			// Data sent from the client using e.g. AJAX is Unicode.
			// But Sitemagic CMS turns data from GET, POST, SESSION, COOKIE, and SERVER
			// into ISO-8859-1 so the data can safely be passed around in the system.
			// However, this only occure when data is retrieved using SMEnvironment::GetPostValue(..),
			// SMEnvironment::GetQueryValue(..) etc.
			// Once data is returned/outputted, the encoded unicode characters are transformed into
			// real unicode characters again (see further down). This makes it completely transparent
			// to the developer of callbacks that encoding and decoding is taking place, and one
			// never has to worry about it.
			// However, the developer will never be able to return e.g. &#8364; since it would always
			// be turned into its unicode equivalent (Euro sign in this case) by the code below.

			ob_start();
			require_once($callback);
			$output = ob_get_contents(); // NOTICE: MUST return ISO-8859-1 - data is passed to UnicodeDecoded(..) below which will corrupt data if encoding is not ISO-8859-1
			ob_end_clean();

			$this->setHeaders("UTF-8"); // Notice: Contrary to ordinary requests, callbacks are mainly used to exchange data using JS which is all Unicode

			// Callbacks are expected to return unicode (used by JS), so we decode unicode characters represented as HEX entities.
			// UnicodeDecode(..) turns ISO-8859-1 into real unicode character and all HEX entities (e.g. &#8364; = Euro sign) are transformed to unicode characters.
			echo SMStringUtilities::UnicodeDecode($output);

			return true;
		}

		return false;
	}

	private function loadExtension($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extensions", $extension, SMTypeCheckType::$String);

		/*if (preg_match("/^[a-z0-9]+$/i", $extension) === 0) // No need to validate again - $extension comes from SMExtensionManager::GetExecutingExtension()
			throw new Exception("Invalid extension name (" . $extension . ")");*/

		if (isset($this->extensionInstances[$extension]) === false)
			throw new Exception("Extension '" . $extension . "' is not accessible (not found or enabled), or does not support current execution mode");

		$ext = $this->extensionInstances[$extension]; // instances created during PreInit()

		$content = $ext->Render();

		if (is_string($content) === false)
			throw new Exception($extension . "->Render() did not return a valid string");

		$this->template->AddHtmlClass("SM" . (($ext->GetIsIntegrated() === true) ? "Integrated" : "") . "Extension");

		return "<div class=\"SMExtension" . (($ext->GetIsIntegrated() === true) ? " SMIntegrated" : "") . " " . $extension . "\">" . $content . "</div>";
	}

	private function autoExecuteExtensions($eventName)
	{
		SMTypeCheck::CheckObject(__METHOD__, "eventName", $eventName, SMTypeCheckType::$String);

		$execMode = SMEnvironment::GetQueryValue("SMExecMode");
		$execMode = (($execMode === null) ? SMExecutionMode::$Shared : $execMode);

		$extensions = null;

		if ($eventName === "PreInit")
		{
			if ($execMode === SMExecutionMode::$Shared)
				$extensions = SMExtensionManager::GetExtensions();
			else if ($execMode === SMExecutionMode::$Dedicated)
				$extensions = array(SMExtensionManager::GetExecutingExtension());
		}
		else
		{
			$extensions = array_keys($this->extensionInstances);
		}

		$refClass = null;
		$instance = null;

		foreach ($extensions as $extension)
		{
			if ($eventName === "PreInit")
			{
				$instance = $this->createExtensionInstance($extension);

				// In case extension does not contain a controller.
				// This is the case for e.g. SMPayment, which just defines
				// functionality without actually providing any.
				if ($instance === null)
					continue;

				if (in_array($execMode, $instance->GetExecutionModes(), true) === false)
					continue;

				$this->extensionInstances[$extension] = $instance;
			}
			else
			{
				$instance = $this->extensionInstances[$extension];
			}

			if ($eventName === "InitComplete")
			{
				$instance->GetContext()->SetTemplate($this->template);
				$instance->GetContext()->SetForm($this->form);
			}

			$instance->$eventName();
		}
	}

	private function createExtensionInstance($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		return SMExtensionManager::GetExtensionInstance($extension);
	}

	private function commitCachedData()
	{
		if (SMAttributes::CollectionChanged() === true)
			SMAttributes::Commit();

		// SMDataSourceCache implements interface SMIDataSourceCache
		$dataSourceNames = SMDataSourceCache::GetInstance()->GetDataSourceNames();
		$dataSource = null;

		// Verify data, to make sure all data sources are able to be commited (consistency, all or nothing)
		foreach ($dataSourceNames as $dataSourceName)
		{
			$dataSource = new SMDataSource($dataSourceName);

			if ($dataSource->Verify() === false)
				throw new Exception("Unable to commit data - data source '" . $dataSourceName . "' failed verification");
		}

		// Commit data
		foreach ($dataSourceNames as $dataSourceName)
		{
			$dataSource = new SMDataSource($dataSourceName);
			$dataSource->Commit();
		}
	}

	private function disableMagicQuotes()
	{
		// Magic Quotes GPC enabled and Magic Quote Sybase disabled:
		//   The following characters are escaped with a back slash:
		//   Single quote, double quote, backslash and NULL.
		// Magic Quotes GPC enabled and Magic Quote Sybase enabled:
		//   Only single quotes are escaped with another single quote

		if (get_magic_quotes_runtime() === 1) // Magic Quotes Runtime = escaping data from data sources
			exit("This system does not support servers with Magic Quotes Runtime enabled - please disable this functionality as discribed in the <a href=\"http://dk.php.net/manual/en/security.magicquotes.disabling.php\">documentation</a>.");

		if (get_magic_quotes_gpc() === 1) // Ordinary escaping as well as Sybase escaping is supported
		{
			$_REQUEST = $this->stripSlashesArrayResursively($_REQUEST);
			$_POST = $this->stripSlashesArrayResursively($_POST);
			$_GET = $this->stripSlashesArrayResursively($_GET);
			$_COOKIE = $this->stripSlashesArrayResursively($_COOKIE);
		}
	}

	private function stripSlashesArrayResursively($arr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "arr", $arr, SMTypeCheckType::$Array);

		foreach ($arr as $key => $value)
		{
			if (is_array($value) === true)
				$arr[$key] = $this->stripSlashesArrayResursively($value);
			else
				$arr[$key] = stripslashes($value);
		}

		return $arr;
	}
}

?>
