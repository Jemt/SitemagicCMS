<?php

/// <container name="base/SMLanguageHandler">
/// 	This class is useful for adding language support to an extension.
/// 	To enable language support, the extension folder must contain a sub folder called
/// 	Languages. Each language supported must be defined in its own translation file. Example:
/// 	Languages/da.xml - Danish translations
/// 	Languages/en.xml - English translations - must be defined (fall back translations)
///
/// 	Translations are defined like so (example content for en.xml):
///
/// 	&lt;?xml version=&quot;1.0&quot; encoding=&quot;ISO-8859-1&quot;?&gt;
/// 	&lt;entries&gt;
/// 		&lt;entry key=&quot;Welcome&quot; value=&quot;Welcome to My Extension&quot; /&gt;
/// 		&lt;entry key=&quot;ClickStart&quot; value=&quot;Click the Start button to begin&quot; /&gt;
/// 	&lt;/entries&gt;
///
/// 	Translations can be queried using the GetTranslation(..) function.
/// 	Translations are loaded in the language currently used by the system.
///
/// 	$lang = new SMLanguageHandler("MyExtension");
/// 	$welcome = $lang->GetTranslation("Welcome");
/// 	$start = $lang->GetTranslation("ClickStart");
/// </container>
class SMLanguageHandler
{
	private $lang;
	private $fallbackPath;		// String:			Path to language file
	private $fallbackLang;		// SMConfiguration:	Fallback language
	private $fallbackEnabled;	// Bool:			Flag indicating whether fallback is enabled or not

	/// <function container="base/SMLanguageHandler" name="__construct" access="public">
	/// 	<description> Create instance of SMLanguageHandler </description>
	/// 	<param name="extension" type="string" default="String.Empty"> Name of extension to load translations for - provides translations for Sitemagic framework if string is empty. </param>
	/// 	<param name="langCode" type="string" default="String.Empty"> Optionally force use of specific language by providing language code - system language is used if string is empty </param>
	/// 	<param name="writable" type="boolean" default="false"> True to make translation file writable, otherwise False </param>
	/// </function>
	public function __construct($extension = "", $langCode = "", $writable = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "langCode", $langCode, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "writable", $writable, SMTypeCheckType::$Boolean);

		if ($extension !== "" && SMExtensionManager::ExtensionExists($extension, true) === false)
			throw new Exception("Unable to load language for extension '" . $extension . "' - not accessible");

		if ($langCode === "" && SMEnvironment::GetSessionValue("SitemagicLanguage") !== null)
			$langCode = SMEnvironment::GetSessionValue("SitemagicLanguage");

		if ($langCode === "")
			$langCode = self::GetDefaultSystemLanguage();

		$this->fallbackPath = (($extension !== "") ? SMExtensionManager::GetExtensionPath($extension) : "base") . "/Languages/en.xml";
		$this->fallbackLang = null;
		$this->fallbackEnabled = (($langCode !== "en") ? true : false);

		$langFile = (($extension !== "") ? SMExtensionManager::GetExtensionPath($extension) : "base") . "/Languages/" . $langCode . ".xml";

		if (SMFileSystem::FileExists($langFile) === false && $writable === false) // Do not assume english if $writable is true
			$langFile = $this->fallbackPath;

		$this->lang = new SMConfiguration($langFile, $writable);
	}

	/// <function container="base/SMLanguageHandler" name="HasTranslation" access="public" returns="boolean">
	/// 	<description> Returns True if translation exists for provided key, otherwise False </description>
	/// 	<param name="key" type="string"> Name of translation </param>
	/// </function>
	public function HasTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		return ($this->lang->GetEntry($key) !== null);
	}

	/// <function container="base/SMLanguageHandler" name="GetTranslation" access="public" returns="string">
	/// 	<description> Get translation - returns empty string if not found </description>
	/// 	<param name="key" type="string"> Name of translation </param>
	/// 	<param name="escape" type="boolean" default="false"> Set True to escape quote and single quote characters, False not to (default) </param>
	/// </function>
	public function GetTranslation($key, $escape = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "escape", $escape, SMTypeCheckType::$Boolean);

		$entry = $this->lang->GetEntry($key);

		if ($entry === null && $this->fallbackEnabled === true)
		{
			if ($this->fallbackLang === null)
				$this->fallbackLang = new SMConfiguration($this->fallbackPath);

			$entry = $this->fallbackLang->GetEntry($key);
		}

		return (($entry !== null) ? (($escape === false) ? $entry : str_replace("'", "\'", str_replace("\"", "\\\"", $entry))) : "");
	}

	/// <function container="base/SMLanguageHandler" name="SetTranslation" access="public">
	/// 	<description> Update or add translation </description>
	/// 	<param name="key" type="string"> Name of translation </param>
	/// 	<param name="value" type="string"> Translation value </param>
	/// </function>
	public function SetTranslation($key, $value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		$this->lang->SetEntry($key, $value);
	}

	/// <function container="base/SMLanguageHandler" name="RemoveTranslation" access="public">
	/// 	<description> Remove translation </description>
	/// 	<param name="key" type="string"> Name of translation </param>
	/// </function>
	public function RemoveTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		$this->lang->RemoveEntry($key);
	}

	/// <function container="base/SMLanguageHandler" name="Commit" access="public">
	/// 	<description> Write changes to translation file </description>
	/// </function>
	public function Commit()
	{
		$this->lang->Commit();
	}

	/// <function container="base/SMLanguageHandler" name="GetTranslationKeys" access="public" returns="string[]">
	/// 	<description> Get all translation keys </description>
	/// </function>
	public function GetTranslationKeys()
	{
		return $this->lang->GetEntries();
	}

	/// <function container="base/SMLanguageHandler" name="GetFallbackEnabled" access="public" returns="boolean">
	/// 	<description> Get value indicating whether GetTranslation(..) should fall back to English if a given translation is missing </description>
	/// </function>
	public function GetFallbackEnabled()
	{
		return $this->fallbackEnabled;
	}

	/// <function container="base/SMLanguageHandler" name="SetFallbackEnabled" access="public">
	/// 	<description> Set value indicating whether GetTranslation(..) should fall back to English if a given translation is missing </description>
	/// 	<param name="val" type="boolean"> Set True to enable fallback, False to disable fallback </param>
	/// </function>
	public function SetFallbackEnabled($val)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$Boolean);
		$this->fallbackEnabled = $val;
	}

	// Static members

	/// <function container="base/SMLanguageHandler" name="OverrideSystemLanguage" access="public" static="true">
	/// 	<description> Change language for the current session </description>
	/// 	<param name="langCode" type="string"> Specify language code. Supported language codes are defined in config.xml.php in the Languages element. </param>
	/// </function>
	public static function OverrideSystemLanguage($langCode)
	{
		SMTypeCheck::CheckObject(__METHOD__, "langCode", $langCode, SMTypeCheckType::$String);
		SMEnvironment::SetSession("SitemagicLanguage", ((in_array($langCode, self::GetLanguages()) === true) ? $langCode : "en"));
	}

	/// <function container="base/SMLanguageHandler" name="GetSystemLanguage" access="public" static="true" returns="string">
	/// 	<description> Get language code currently used by the system (e.g. da or en) </description>
	/// </function>
	public static function GetSystemLanguage()
	{
		if (SMEnvironment::GetSessionValue("SitemagicLanguage") !== null)
			return SMEnvironment::GetSessionValue("SitemagicLanguage");
		else
			return self::GetDefaultSystemLanguage();
	}

	/// <function container="base/SMLanguageHandler" name="RestoreSystemLanguage" access="public" static="true">
	/// 	<description> Have system revert to default language configured in config.xml.php in the Language element </description>
	/// </function>
	public static function RestoreSystemLanguage()
	{
		SMEnvironment::DestroySession("SitemagicLanguage");
	}

	/// <function container="base/SMLanguageHandler" name="GetDefaultSystemLanguage" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Get language code configured in config.xml.php in the Language element,
	/// 		which is used by default. Returns &quot;en&quot; if not configured.
	/// 	</description>
	/// </function>
	public static function GetDefaultSystemLanguage()
	{
		$cfg = SMEnvironment::GetConfiguration();
		$lang = $cfg->GetEntry("Language");
		return (($lang !== null && $lang !== "") ? $lang : "en");
	}

	/// <function container="base/SMLanguageHandler" name="GetLanguages" access="public" static="true" returns="string[]">
	/// 	<description>
	/// 		Get language codes available to the system, as defined in config.xml.php
	/// 		in the Languages element. An empty array is returned if not configured.
	/// 	</description>
	/// </function>
	public static function GetLanguages()
	{
		$cfg = SMEnvironment::GetConfiguration();
		$languagesStr = $cfg->GetEntry("Languages");

		if ($languagesStr === null)
			return array();

		$languages = array();
		if ($languagesStr !== "")
			$languages = explode(";", $languagesStr);

		return $languages;
	}

	// Functions for managing translation files

	/// <function container="base/SMLanguageHandler" name="HasTranslations" access="public" static="true" returns="boolean">
	/// 	<description> Check whether given extension has translations for a given language </description>
	/// 	<param name="extension" type="string"> Name of extension. Checks for translations to Sitemagic framework if string is empty. </param>
	/// 	<param name="langCode" type="string"> Language code for translations to check for </param>
	/// </function>
	public static function HasTranslations($extension, $langCode)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "langCode", $langCode, SMTypeCheckType::$String);

		return SMFileSystem::FileExists(self::getTranslationFilePath($extension, $langCode));
	}

	/// <function container="base/SMLanguageHandler" name="CreateTranslations" access="public" static="true" returns="boolean">
	/// 	<description>
	/// 		Create new translation file. An existing translation file will not be replaced. Returns True on success, or
	/// 		False on failure, e.g. due to insufficient permissions on language folder (extensions/MyExtension/Languages).
	/// 	</description>
	/// 	<param name="extension" type="string"> Name of extension </param>
	/// 	<param name="langCode" type="string"> Language code for translation file </param>
	/// 	<param name="copyFromEnglish" type="boolean" default="false"> Set True to copy English translations </param>
	/// </function>
	public static function CreateTranslations($extension, $langCode, $copyFromEnglish = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "langCode", $langCode, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "copyFromEnglish", $copyFromEnglish, SMTypeCheckType::$Boolean);

		$file = self::getTranslationFilePath($extension, $langCode);

		if (SMFileSystem::FileExists($file) === true)
			return false;

		if ($copyFromEnglish === true)
		{
			// Copy English translations
			return SMFileSystem::Copy(self::getTranslationFilePath($extension, "en"), $file);
		}
		else
		{
			// Create new empty translation file

			$cfg = new SMConfiguration($file, true);

			try
			{
				$cfg->Commit(); // Throws exception if file cannot be created, e.g. due to insufficient write access
			}
			catch (Exception $ex)
			{
				return false;
			}

			return true;
		}
	}

	/// <function container="base/SMLanguageHandler" name="RemoveTranslations" access="public" static="true" returns="boolean">
	/// 	<description> Remove translations for specified extension and language </description>
	/// 	<param name="extension" type="string"> Name of extension </param>
	/// 	<param name="langCode" type="string"> Language code for translation file </param>
	/// </function>
	public static function RemoveTranslations($extension, $langCode)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "langCode", $langCode, SMTypeCheckType::$String);

		return SMFileSystem::Delete(self::getTranslationFilePath($extension, $langCode));
	}

	/// <function container="base/SMLanguageHandler" name="CanUpdateTranslations" access="public" static="true" returns="boolean">
	/// 	<description> Check whether translation file is writable for specified extension and language </description>
	/// 	<param name="extension" type="string"> Name of extension </param>
	/// 	<param name="langCode" type="string"> Language code for translation file </param>
	/// </function>
	public static function CanUpdateTranslations($extension, $langCode)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "langCode", $langCode, SMTypeCheckType::$String);

		return SMFileSystem::FileIsWritable(self::getTranslationFilePath($extension, $langCode));
	}

	/// <function container="base/SMLanguageHandler" name="CanAddTranslations" access="public" static="true" returns="boolean">
	/// 	<description>
	/// 		Check whether translation folder (e.g. extensions/MyExtension/Languages) is
	/// 		writable, allowing new translation files to be created for specified extension.
	/// 	</description>
	/// 	<param name="extension" type="string"> Name of extension </param>
	/// </function>
	public static function CanAddTranslations($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		return SMFileSystem::FolderIsWritable(self::getTranslationFolderPath($extension));
	}

	private static function getTranslationFolderPath($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		return (($extension === "") ? "base" : SMEnvironment::GetExtensionsDirectory() . "/" . $extension) . "/Languages";
	}

	private static function getTranslationFilePath($extension, $langCode)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "langCode", $langCode, SMTypeCheckType::$String);

		return self::getTranslationFolderPath($extension) . "/" . $langCode . ".xml";
	}
}

?>
