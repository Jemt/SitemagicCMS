<?php

class SMExternalModulesUnit
{
	public static $Pixels = "Pixels";
	public static $Percent = "Percent";
}

class SMExternalModulesScroll
{
	public static $No = "No";
	public static $Yes = "Yes";
	public static $Auto = "Auto";
}

class SMExternalModulesModule
{
	private $guid;			// String
	private $name;			// String
	private $url;			// String
	private $width;			// Int
	private $widthUnit;		// SMExternalModulesUnit
	private $height;		// Int
	private $heightUnit;	// SMExternalModulesUnit
	private $scroll;		// SMExternalModulesScroll
	private $reloadToTop;	// Bool
	private $frameColor;	// String (empty = display no frame)

	public function SMExternalModulesModule($guid, $name, $url, $width, $widthUnit, $height, $heightUnit, $scroll, $reloadToTop = false, $frameColor = "")
	{
		SMTypeCheck::CheckObject(__METHOD__, "guid", $guid, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "url", $url, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "width", $width, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "widthUnit", $widthUnit, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "height", $height, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "heightUnit", $heightUnit, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "scroll", $scroll, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "reloadToTop", $reloadToTop, SMTypeCheckType::$Boolean);
		SMTypeCheck::CheckObject(__METHOD__, "frameColor", $frameColor, SMTypeCheckType::$String);

		if (property_exists("SMExternalModulesUnit", $widthUnit) === false)
			throw new Exception("Invalid unit type '" . $widthUnit . "' specified - use SMExternalModulesUnit::Unit");
		if (property_exists("SMExternalModulesUnit", $heightUnit) === false)
			throw new Exception("Invalid unit type '" . $heightUnit . "' specified - use SMExternalModulesUnit::Unit");
		if (property_exists("SMExternalModulesScroll", $scroll) === false)
			throw new Exception("Invalid unit type '" . $scroll . "' specified - use SMExternalModulesScroll::Type");

		$this->guid = $guid;
		$this->name = $name;
		$this->url = $url;
		$this->width = $width;
		$this->widthUnit = $widthUnit;
		$this->height = $height;
		$this->heightUnit = $heightUnit;
		$this->scroll = $scroll;
		$this->reloadToTop = $reloadToTop;
		$this->frameColor = $frameColor;
	}

	public function GetGuid()
	{
		return $this->guid;
	}

	public function SetName($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->name = $value;
	}

	public function GetName()
	{
		return $this->name;
	}

	public function SetUrl($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->url = $value;
	}

	public function GetUrl()
	{
		return $this->url;
	}

	public function SetWidth($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Integer);
		$this->width = $value;
	}

	public function GetWidth()
	{
		return $this->width;
	}

	public function SetWidthUnit($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if (property_exists("SMExternalModulesUnit", $value) === false)
			throw new Exception("Invalid unit type '" . $value . "' specified - use SMExternalModulesUnit::Unit");

		$this->widthUnit = $value;
	}

	public function GetWidthUnit()
	{
		return $this->widthUnit;
	}

	public function SetHeight($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Integer);
		$this->height = $value;
	}

	public function GetHeight()
	{
		return $this->height;
	}

	public function SetHeightUnit($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if (property_exists("SMExternalModulesUnit", $value) === false)
			throw new Exception("Invalid unit type '" . $value . "' specified - use SMExternalModulesUnit::Unit");

		$this->heightUnit = $value;
	}

	public function GetHeightUnit()
	{
		return $this->heightUnit;
	}

	public function SetScroll($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if (property_exists("SMExternalModulesScroll", $value) === false)
			throw new Exception("Invalid unit type '" . $value . "' specified - use SMExternalModulesScroll::Type");

		$this->scroll = $value;
	}

	public function GetScroll()
	{
		return $this->scroll;
	}

	public function SetReloadToTop($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->reloadToTop = $value;
	}

	public function GetReloadToTop()
	{
		return $this->reloadToTop;
	}

	public function SetFrameColor($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->frameColor = $value;
	}

	public function GetFrameColor()
	{
		return $this->frameColor;
	}

	// Database functions

	public function CommitPersistent()
	{
		$db = new SMDataSource("SMExternalModules");
		$kvc = new SMKeyValueCollection();

		$existing = self::GetPersistentByGuid($this->guid);

		if ($existing === null)
			$kvc["guid"] = $this->guid;

		$kvc["name"] = $this->name;
		$kvc["url"] = $this->url;
		$kvc["width"] = (string)$this->width;
		$kvc["widthunit"] = $this->widthUnit;
		$kvc["height"] = (string)$this->height;
		$kvc["heightunit"] = $this->heightUnit;
		$kvc["scroll"] = $this->scroll;
		$kvc["reloadtotop"] = (($this->reloadToTop === true) ? "true" : "false");
		$kvc["framecolor"] = $this->frameColor;

		if ($existing !== null)
		{
			$updateCount = $db->Update($kvc, "guid = '" . $db->Escape($this->guid) . "'");

			if ($updateCount === 0)
				return false;
		}
		else
		{
			$db->Insert($kvc);
		}

		return true;
	}

	public function DeletePersistent()
	{
		$db = new SMDataSource("SMExternalModules");
		$deleteCount = $db->Delete("guid = '" . $db->Escape($this->guid) . "'");

		if ($deleteCount === 0)
			return false;

		return true;
	}

	public static function GetPersistentByGuid($guid)
	{
		SMTypeCheck::CheckObject(__METHOD__, "guid", $guid, SMTypeCheckType::$String);
		return self::getPersistent("guid", $guid);
	}

	public static function GetPersistentByName($name)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);
		return self::getPersistent("name", $name);
	}

	private static function getPersistent($field, $search)
	{
		SMTypeCheck::CheckObject(__METHOD__, "field", $field, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);

		$ds = new SMDataSource("SMExternalModules");
		$records = $ds->Select("*", $field . " = '" . $ds->Escape($search) . "'");

		if (count($records) === 0)
			return null;

		$record = $records[0];

		return new SMExternalModulesModule($record["guid"], $record["name"], $record["url"], (int)$record["width"], $record["widthunit"], (int)$record["height"], $record["heightunit"], $record["scroll"], ($record["reloadtotop"] === "true"), $record["framecolor"]);
	}
}

class SMExternalModulesLoader
{
	public static function GetModules()
	{
		$ds = new SMDataSource("SMExternalModules");
		$records = $ds->Select();

		$modules = array();

		foreach ($records as $record)
			$modules[] = new SMExternalModulesModule($record["guid"], $record["name"], $record["url"], (int)$record["width"], $record["widthunit"], (int)$record["height"], $record["heightunit"], $record["scroll"], ($record["reloadtotop"] === "true"), $record["framecolor"]);

		return $modules;
	}
}

?>
