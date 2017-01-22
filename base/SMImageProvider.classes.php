<?php

/// <container name="base/SMImageType">
/// 	Enum that represents images from image package
/// </container>
class SMImageType
{
	// Enum (editable entries unfortunately) - real enums does not exist in PHP.
	// Constants could be used, but requires use of functions, which integrates
	// badly with most Auto Proposal functions in Integrated Development Environments.

	/// <member container="base/SMImageType" name="Left" access="public" static="true" type="string" default="left" />
	public static $Left = "left";
	/// <member container="base/SMImageType" name="Right" access="public" static="true" type="string" default="right" />
	public static $Right = "right";
	/// <member container="base/SMImageType" name="Up" access="public" static="true" type="string" default="up" />
	public static $Up = "up";
	/// <member container="base/SMImageType" name="Down" access="public" static="true" type="string" default="down" />
	public static $Down = "down";
	/// <member container="base/SMImageType" name="Create" access="public" static="true" type="string" default="create" />
	public static $Create = "create";
	/// <member container="base/SMImageType" name="Modify" access="public" static="true" type="string" default="modify" />
	public static $Modify = "modify";
	/// <member container="base/SMImageType" name="Update" access="public" static="true" type="string" default="update" />
	public static $Update = "update";
	/// <member container="base/SMImageType" name="Delete" access="public" static="true" type="string" default="delete" />
	public static $Delete = "delete";
	/// <member container="base/SMImageType" name="Save" access="public" static="true" type="string" default="save" />
	public static $Save = "save";
	/// <member container="base/SMImageType" name="Properties" access="public" static="true" type="string" default="properties" />
	public static $Properties = "properties";
	/// <member container="base/SMImageType" name="Browse" access="public" static="true" type="string" default="browse" />
	public static $Browse = "browse";
	/// <member container="base/SMImageType" name="Clear" access="public" static="true" type="string" default="clear" />
	public static $Clear = "clear";
	/// <member container="base/SMImageType" name="Search" access="public" static="true" type="string" default="search" />
	public static $Search = "search";
	/// <member container="base/SMImageType" name="Lock" access="public" static="true" type="string" default="lock" />
	public static $Lock = "lock";
	/// <member container="base/SMImageType" name="Unlock" access="public" static="true" type="string" default="unlock" />
	public static $Unlock = "unlock";
	/// <member container="base/SMImageType" name="Mail" access="public" static="true" type="string" default="mail" />
	public static $Mail = "mail";
	/// <member container="base/SMImageType" name="Display" access="public" static="true" type="string" default="display" />
	public static $Display = "display";
	/// <member container="base/SMImageType" name="Help" access="public" static="true" type="string" default="help" />
	public static $Help = "help";
	/// <member container="base/SMImageType" name="Settings" access="public" static="true" type="string" default="settings" />
	public static $Settings = "settings";
	/// <member container="base/SMImageType" name="Information" access="public" static="true" type="string" default="information" />
	public static $Information = "information";
}

/// <container name="base/SMImageProvider">
/// 	Provides access to image packages and common images.
/// 	These are commonly used by link buttons throughout the application.
///
/// 	$img = &quot;&lt;img src='&quot; . SMImageProvider::GetImage(SMImageType::$Browse) . &quot;'>&quot;;
/// </container>
class SMImageProvider
{
	private static $theme = null;
	private static $extension = null;

	/// <function container="base/SMImageProvider" name="GetImage" access="public" static="true" returns="string">
	/// 	<description> Get reference to image of specified type </description>
	/// 	<param name="image" type="SMImageType"> Specify image type </param>
	/// </function>
	public static function GetImage($image)
	{
		SMTypeCheck::CheckObject(__METHOD__, "image", $image, SMTypeCheckType::$String);

		if (self::$theme === null)
			self::initializeTheme();

		if (property_exists("SMImageType", ucfirst($image)) === false)
			throw new Exception("Specified image does not exist - use SMImageType::Image");

		return SMEnvironment::GetImagesDirectory() . "/" . self::$theme . "/" . $image . self::$extension;
	}

	/// <function container="base/SMImageProvider" name="GetImageThemes" access="public" static="true" returns="string[]">
	/// 	<description> Get names of image themes (packages) available </description>
	/// </function>
	public static function GetImageThemes()
	{
		return SMFileSystem::GetFolders(dirname(__FILE__) . "/../" . SMEnvironment::GetImagesDirectory());
	}

	/// <function container="base/SMImageProvider" name="GetImageTheme" access="public" static="true" returns="string">
	/// 	<description> Get name of image theme (package) configured to be used </description>
	/// </function>
	public static function GetImageTheme()
	{
		if (self::$theme === null)
			self::initializeTheme();

		return self::$theme;
	}

	private static function initializeTheme()
	{
		$config = SMEnvironment::GetConfiguration();
		$cfgImageTheme = $config->GetEntry("ImageTheme");

		self::$theme = (($cfgImageTheme !== null && $cfgImageTheme !== "") ? $cfgImageTheme : "Default");

		if (SMFileSystem::FolderExists(dirname(__FILE__) . "/../" . SMEnvironment::GetImagesDirectory() . "/" . self::$theme) === false)
			throw new Exception("Specified image theme '" . self::$theme . "' does not exist");

		// First image in folder determines image type (gif, png, jpg etc.)
		$files = SMFileSystem::GetFiles(dirname(__FILE__) . "/../" . SMEnvironment::GetImagesDirectory() . "/" . self::$theme);

		if (count($files) === 0)
			throw new Exception("Specified image theme '" . self::$theme . "' contains no images");

		$extPos = strrpos($files[0], ".");
		self::$extension = substr($files[0], $extPos);
	}
}

?>
