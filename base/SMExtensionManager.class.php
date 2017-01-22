<?php

/// <container name="base/SMExtensionManager">
/// 	Class responsible for handling and executing extensions.
///
/// 	$url = SMExtensionManager::GetExtensionUrlEncoded(&quot;MyExtension&quot;);
/// 	$link = &quot;&lt;a href='&quot; . $url . &quot;'&gt;Go to MyExtension&lt;/a&gt;&quot;;
///
/// 	The link created above will cause MyExtension to be loaded and executed
/// 	as the privileged extension if clicked. This will allow the extension to render
/// 	output to the content area. See base/SMExtension for more information.
/// </container>
class SMExtensionManager
{
	private static $extensions = null; // string[] - string["Extension"] = enabled (boolean)

	private function __construct()
	{
	}

	/// <function container="base/SMExtensionManager" name="GetExtensions" access="public" static="true" returns="string[]">
	/// 	<description> Get names of extensions </description>
	/// 	<param name="includeDisabled" type="boolean" default="false"> True to include extensions not enabled, false to return only enabled extensions </param>
	/// </function>
	public static function GetExtensions($includeDisabled = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "includeDisabled", $includeDisabled, SMTypeCheckType::$Boolean);

		self::ensureExtensions();

		if ($includeDisabled === true)
			return array_keys(self::$extensions);

		$extensionsEnabled = array();

		foreach (self::$extensions as $extension => $enabled)
			if ($enabled === true)
				$extensionsEnabled[] = $extension;

		return $extensionsEnabled;
	}

	/// <function container="base/SMExtensionManager" name="GetExtensionPath" access="public" static="true" returns="string">
	/// 	<description> Get path to given extension </description>
	/// 	<param name="extension" type="string"> Name of extension </param>
	/// </function>
	public static function GetExtensionPath($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		return SMEnvironment::GetExtensionsDirectory() . "/" . $extension;
	}

	/// <function container="base/SMExtensionManager" name="GetExtensionUrl" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Get URL to extension. Browsing to the URL returned will causing the extension
	/// 		to be executed privileged, allowing it to render output to the content area.
	/// 	</description>
	/// 	<param name="extension" type="string"> Name of extension </param>
	/// 	<param name="templateType" type="SMTemplateType" default="SMTemplateType::$Normal">
	/// 		Template type to use.
	/// 		SMTemplateType::$Normal = Load extension in normal design template.
	/// 		SMTemplateType::$Basic = Load extension in minimalistic template suitable for pop ups.
	/// 		See base/SMTemplateType for more information.
	/// 	</param>
	/// 	<param name="execMode" type="SMExecutionMode" default="SMExecutionMode::$Shared">
	/// 		Execution mode to use.
	/// 		SMExecutionMode::$Shared = Have extension execute together with all other extensions
	/// 		SMExecutionMode::$Dedicated = Have extension execute alone.
	/// 		Dedicated execution mode may improve load time, but also disable e.g. navigation
	/// 		and other extensions providing additional functionality. Dedicated execution mode
	/// 		is mainly used when template type is SMTemplateType::$Basic.
	/// 		See base/SMExtension and base/SMExecutionMode for more information.
	/// 	</param>
	/// </function>
	public static function GetExtensionUrl($extension, $templateType = "Normal", $execMode = "Shared")
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "templateType", $templateType, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "execMode", $execMode, SMTypeCheckType::$String);

		if (property_exists("SMTemplateType", $templateType) === false)
			throw new Exception("Invalid template type '" . $templateType . "' specified - use SMTemplateType::Type");

		if (property_exists("SMExecutionMode", $execMode) === false)
			throw new Exception("Invalid execution mode '" . $execMode . "' specified - use SMExecutionMode::Mode");

		$url = "index.php?SMExt=" . $extension;
		$url .= (($templateType === SMTemplateType::$Basic) ? "&SMTemplateType=Basic" : "");
		$url .= (($execMode === SMExecutionMode::$Dedicated) ? "&SMExecMode=Dedicated" : "");

		return $url;
	}

	/// <function container="base/SMExtensionManager" name="GetExtensionUrlEncoded" access="public" static="true" returns="string">
	/// 	<description> See GetExtensionUrl(..) function for description. Returned value is URL encoded. </description>
	/// 	<param name="extension" type="string"> See GetExtensionUrl(..) function for description </param>
	/// 	<param name="templateType" type="SMTemplateType" default="SMTemplateType::$Normal"> See GetExtensionUrl(..) function for description </param>
	/// 	<param name="execMode" type="SMExecutionMode" default="SMExecutionMode::$Shared"> See GetExtensionUrl(..) function for description </param>
	/// </function>
	public static function GetExtensionUrlEncoded($extension, $templateType = "Normal", $execMode = "Shared")
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "templateType", $templateType, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "execMode", $execMode, SMTypeCheckType::$String);

		$url = self::GetExtensionUrl($extension, $templateType, $execMode);
		return str_replace("&", "&amp;", $url);
	}

	/// <function container="base/SMExtensionManager" name="GetCallbackUrl" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Get URL to callback PHP file which will execute in the context of Sitemagic.
	/// 		This is often used to perform AJAX requests using SMHttpRequest client side.
	/// 		Notice that the callback file can ensure that a given request is performed
	/// 		through Sitemagic using the following check: if ($SMCallback !== true) exit;
	/// 	</description>
	/// 	<param name="extension" type="string"> Name of extension, e.g. MyExtension </param>
	/// 	<param name="callbackFile" type="string"> Callback filename without required .callback.php suffix </param>
	/// 	<param name="urlEncode" type="boolean" default="false"> Set True to encode URL, False not to </param>
	/// </function>
	public static function GetCallbackUrl($extension, $callbackFile, $urlEncode = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "callbackFile", $callbackFile, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "urlEncode", $urlEncode, SMTypeCheckType::$Boolean);

		$url = "index.php?SMExt=" . $extension . "&SMCallback=" . $callbackFile;

		if ($urlEncode === false)
			return $url;
		else
			return str_replace("&", "&amp;", $url);
	}

	/// <function container="base/SMExtensionManager" name="Import" access="public" static="true" returns="boolean">
	/// 	<description>
	/// 		Most extensions load common functionality automatically to make it
	/// 		available to all extensions participating in the life cycle.
	/// 		However, some extensions may not load functionality automatically
	/// 		- e.g. because of performance costs. Such functionality may be loaded
	/// 		on demand using the Import(..) function.
	/// 	</description>
	/// 	<param name="extension" type="string"> Name of extension to load file from </param>
	/// 	<param name="file" type="string"> Name of file to load </param>
	/// 	<param name="throwExceptionIfMissing" type="boolean" default="false"> Set True to throw exception rather than returning False if file is not found </param>
	/// 	<param name="allowDisabled" type="boolean" default="false"> Set True to allow import from extensions not enabled - these extensions will not be able to initialize </param>
	/// </function>
	public static function Import($extension, $file, $throwExceptionIfMissing = false, $allowDisabled = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "file", $file, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "throwExceptionIfMissing", $throwExceptionIfMissing, SMTypeCheckType::$Boolean);
		SMTypeCheck::CheckObject(__METHOD__, "allowDisabled", $allowDisabled, SMTypeCheckType::$Boolean);

		$path = dirname(__FILE__) . "/../" . SMEnvironment::GetExtensionsDirectory() . "/" . $extension . "/" . $file;

		if (self::ExtensionExists($extension, $allowDisabled) === false || SMFileSystem::FileExists($path) === false)
		{
			if ($throwExceptionIfMissing === true)
				throw new Exception("Unable to import - extension '" . $extension . "' is not accessible, or does not contain the specified file '" . $file . "'");

			return false;
		}

		require_once($path);
		return true;
	}

	/// <function container="base/SMExtensionManager" name="ExtensionExists" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if given extension exists, otherwise False </description>
	/// 	<param name="extension" type="string"> Name of extension </param>
	/// 	<param name="allowDisabled" type="boolean" default="false"> Set True to also check disabled extensions </param>
	/// </function>
	public static function ExtensionExists($extension, $allowDisabled = false) // Extension is not considered existing if disabled, unless $allowDisabled is True
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "allowDisabled", $allowDisabled, SMTypeCheckType::$Boolean);

		self::ensureExtensions();

		if ($allowDisabled === true) // Simply check if it exists - disabled or enabled doesn't matter
			return isset(self::$extensions[$extension]);
		else // Make sure extension both exists and has been enabled
			return (isset(self::$extensions[$extension]) && self::$extensions[$extension] === true);
	}

	/// <function container="base/SMExtensionManager" name="ExtensionEnabled" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if given extension exists and has been enabled, otherwise False </description>
	/// 	<param name="extension" type="string"> Name of extension </param>
	/// </function>
	public static function ExtensionEnabled($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		return self::ExtensionExists($extension, false); // Will also return False for non-existing extension
	}

	/// <function container="base/SMExtensionManager" name="SetExtensionEnabled" access="public" static="true">
	/// 	<description> Enable or disable extension </description>
	/// 	<param name="extension" type="string"> Extension to enable or disable </param>
	/// 	<param name="enabled" type="boolean"> Set True to enable extension, False to disable it </param>
	/// </function>
	public static function SetExtensionEnabled($extension, $enabled)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "enabled", $enabled, SMTypeCheckType::$Boolean);

		// Skip if already set as requested

		if ($enabled === true && self::ExtensionEnabled($extension) === true)
			return;
		else if ($enabled === false && self::ExtensionEnabled($extension) === false)
			return;

		// Read extensions enabled

		$conf = SMEnvironment::GetConfiguration(true);
		$extensionsStr = $conf->GetEntry("ExtensionsEnabled");

		// Enable or disable extension

		if ($enabled === true)
		{
			$extensionsStr .= (($extensionsStr !== "") ? ";" : "") . $extension;
		}
		else
		{
			$extensions = explode(";", $extensionsStr);
			$extensionsStr = "";

			foreach ($extensions as $ext)
				if ($ext !== $extension)
					$extensionsStr .= (($extensionsStr !== "") ? ";" : "") . $ext;
		}

		// Update configuration

		$conf->SetEntry("ExtensionsEnabled", $extensionsStr);
		$conf->Commit();

		// Clear cache

		self::$extensions = null;

		// Call extension event

		$extInstance = self::GetExtensionInstance($extension);
		$event = null;

		if ($enabled === true)
			$event = "Enabled";
		else
			$event = "Disabled";

		$extInstance->$event();
	}

	/// <function container="base/SMExtensionManager" name="GetExecutingExtension" access="public" static="true" returns="string">
	/// 	<description> Returns name of extension currently executing (privileged extension) </description>
	/// </function>
	public static function GetExecutingExtension()
	{
		$val = SMEnvironment::GetQueryValue("SMExt", SMValueRestriction::$AlphaNumeric, array(".", "-", "_"));
		return (($val !== null) ? $val : self::GetDefaultExtension());
	}

	/// <function container="base/SMExtensionManager" name="GetDefaultExtension" access="public" static="true" returns="string">
	/// 	<description> Returns name of extension set to executy by default. Returns Null if not configured. </description>
	/// </function>
	public static function GetDefaultExtension()
	{
		$config = SMEnvironment::GetConfiguration();
		return $config->GetEntry("DefaultExtension");
	}

	/// <function container="base/SMExtensionManager" name="ExecuteExtension" access="public" static="true">
	/// 	<description> Have system reload and execute given extension through a new GET request. Function stops further code execution. </description>
	/// 	<param name="extension" type="string"> Name of extension to execute </param>
	/// 	<param name="args" type="SMKeyValueCollection" default="null"> Optional collection of query string parameters </param>
	/// </function>
	public static function ExecuteExtension($extension, SMKeyValueCollection $args = null)
	{
		// This function is not the one executing extensions, this is handled by the SMController.
		// However, this function may be used to "restart" the system, forcing a new extension to be executed.
		// The function allows for the $extension argument to be null, so the following call can be used:
		// SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());
		// The GetDefaultExtension might return null. In this case we want the SMController to determine
		// what to do, when no extension is specified.

		SMTypeCheck::CheckObject(__METHOD__, "extension", (($extension !== null) ? $extension : ""), SMTypeCheckType::$String);

		if ($extension === null)
			SMRedirect::Redirect("index.php");

		if (self::ExtensionExists($extension) === false)
			throw new Exception("Specified extension '" . $extension . "' is not accessible - unable to execute");

		$argsStr = "";

		if ($args !== null)
		{
			foreach ($args as $key => $value)
			{
				$argsStr .= "&" . $key;
				$argsStr .= (($value !== "") ? "=" . $value : "");
			}
		}

		SMRedirect::Redirect("index.php?SMExt=" . $extension . $argsStr);
	}

	/// <function container="base/SMExtensionManager" name="GetMetaData" access="public" static="true" returns="SMKeyValueCollection">
	/// 	<description>
	/// 		Returns instance of SMKeyValueCollection containing meta
	/// 		data from metadata.xml found in extension folder.
	/// 		Null is returned if extension does not exist.
	/// 	</description>
	/// 	<param name="extension" type="string"> Name of extension to get meta data from </param>
	/// </function>
	public static function GetMetaData($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);

		if (self::ExtensionExists($extension, true) === false)
			return null;

		$lang = new SMLanguageHandler($extension);
		$cfg = new SMConfiguration(dirname(__FILE__) . "/../" . SMEnvironment::GetExtensionsDirectory() . "/" . $extension . "/metadata.xml");

		$data = new SMKeyValueCollection();
		$data["Title"] = self::getMetaDataEntry($cfg, $lang, "Title");
		$data["Description"] = self::getMetaDataEntry($cfg, $lang, "Description");
		$data["Author"] = self::getMetaDataEntry($cfg, $lang, "Author");
		$data["Company"] = self::getMetaDataEntry($cfg, $lang, "Company");
		$data["Website"] = self::getMetaDataEntry($cfg, $lang, "Website");
		$data["Email"] = self::getMetaDataEntry($cfg, $lang, "Email");
		$data["Version"] = self::getMetaDataEntry($cfg, $lang, "Version");
		$data["Dependencies"] = self::getMetaDataEntry($cfg, $lang, "Dependencies");
		$data["Notes"] = self::getMetaDataEntry($cfg, $lang, "Notes");

		return $data;
	}

	public static function GetExtensionInstance($extension) // Do NOT add XML comments, this is for Sitemagic CMS only
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);

		$extensionController = SMEnvironment::GetExtensionsDirectory() . "/" . $extension . "/Main.class.php";

		if (SMFileSystem::FileExists($extensionController) === false)
			return null;

		require_once($extensionController);

		if (class_exists($extension) === false)
			throw new Exception("Extension controller '" . $extension . "' did not define class '" . $extension . "'");

		$refClass = new ReflectionClass($extension);
		$extends = false;

		while ($refClass !== false)
		{
			$refClass = $refClass->getParentClass();

			if ($refClass !== false && $refClass->getName() === "SMExtension")
			{
				$extends = true;
				break;
			}
		}

		if ($extends === false)
			throw new Exception("Extension controller '" . $extension . "' must extend class 'SMExtension'");

		return new $extension(new SMContext($extension, ((SMEnvironment::GetQueryValue("SMExecMode") === null || SMEnvironment::GetQueryValue("SMExecMode") === SMExecutionMode::$Shared) ? SMExecutionMode::$Shared : SMExecutionMode::$Dedicated), ((SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal) ? SMTemplateType::$Normal : SMTemplateType::$Basic)));
	}

	private static function getMetaDataEntry(SMConfiguration $md, SMLanguageHandler $lang, $key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		$entry = $md->GetEntry($key);

		if ($entry === null || $entry === "")
			return "";
		else if (strpos($entry, "$") === 0 && strlen($entry) > 1)
			return $lang->GetTranslation(substr($entry, 1)); // Returns empty string if not found
		else
			return $entry;
	}

	private static function ensureExtensions()
	{
		if (self::$extensions === null)
		{
			self::$extensions = array();

			$extensions = SMFileSystem::GetFolders(dirname(__FILE__) . "/../" . SMEnvironment::GetExtensionsDirectory());

			$cfg = SMEnvironment::GetConfiguration();
			$configExtensionsEnabled = $cfg->GetEntry("ExtensionsEnabled");
			$extensionsEnabled = (($configExtensionsEnabled === null || $configExtensionsEnabled === "") ? array() : explode(";", $configExtensionsEnabled));

			foreach ($extensions as $extension)
				self::$extensions[$extension] = in_array($extension, $extensionsEnabled, true);
		}
	}
}

/// <container name="base/SMExecutionMode">
/// 	Enum defining available modes in which an extension may be executed.
/// </container>
class SMExecutionMode
{
	/// <member container="base/SMExecutionMode" name="Shared" access="public" static="true" type="string" default="Shared">
	/// 	<description> Shared execution mode allow other extensions to participate in the life cycle, which is most common </description>
	/// </member>
	public static $Shared = "Shared";

	/// <member container="base/SMExecutionMode" name="Dedicated" access="public" static="true" type="string" default="Dedicated">
	/// 	<description> Dedicated execution mode results in only privileged extension being executed. This is mainly used when the extension needs to display something in a pop up window, and does not depend on other extensions. </description>
	/// </member>
	public static $Dedicated = "Dedicated";
}

?>
