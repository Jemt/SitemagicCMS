<?php

class SMRatingItem
{
	private $pageId;		// String (Guid)
	private $instanceId;	// Integer
	private $maxValue;		// Integer

	private $ratingCount;	// Integer
	private $ratingValue;	// Float

	public function __construct($pageId, $instanceId, $maxValue, $ratingCount = 0, $ratingValue = 0.00)
	{
		SMTypeCheck::CheckObject(__METHOD__, "pageId", $pageId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "maxValue", $maxValue, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "ratingCount", $ratingCount, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "ratingValue", $ratingValue, SMTypeCheckType::$Float);

		$this->pageId = $pageId;
		$this->instanceId = $instanceId;
		$this->maxValue = $maxValue;

		$this->ratingCount = $ratingCount;
		$this->ratingValue = $ratingValue;
	}

	public function GetMaxValue()
	{
		return $this->maxValue;
	}

	public function GetRatingCount()
	{
		return $this->ratingCount;
	}

	public function GetRatingValue()
	{
		return $this->ratingValue;
	}

	public function Rate($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Integer);

		$this->ratingValue = (($this->ratingValue * $this->ratingCount) + $value) / ($this->ratingCount + 1);
		$this->ratingCount++;
	}

	// Database functions

	public function CommitPersistent()
	{
		$db = new SMDataSource("SMRating");
		$kvc = new SMKeyValueCollection();

		if (self::GetPersistent($this->pageId, $this->instanceId) !== null)
		{
			$kvc["count"] = (string)$this->ratingCount;
			$kvc["value"] = (string)$this->ratingValue;
			$updateCount = $db->Update($kvc, "pageid = '" . $db->Escape($this->pageId) . "' AND instanceid = " . $this->instanceId);

			if ($updateCount === 0)
				return false;
		}
		else
		{
			$kvc["pageid"] = $this->pageId;
			$kvc["instanceid"] = (string)$this->instanceId;
			$kvc["maxvalue"] = (string)$this->maxValue;
			$kvc["count"] = (string)$this->ratingCount;
			$kvc["value"] = (string)$this->ratingValue;
			$db->Insert($kvc);
		}

		return true;
	}

	public function DeletePersistent()
	{
		$db = new SMDataSource("SMRating");
		$deleteCount = $db->Delete("pageid = '" . $db->Escape($this->pageId) . "' AND instanceid = " . $this->instanceId);

		if ($deleteCount === 0)
			return false;

		return true;
	}

	public static function GetPersistent($pageId, $instanceId)
	{
		SMTypeCheck::CheckObject(__METHOD__, "pageId", $pageId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);

		$ds = new SMDataSource("SMRating");
		$records = $ds->Select("*", "pageid = '" . $ds->Escape($pageId) . "' AND instanceid = " . $instanceId);

		if (count($records) === 0)
			return null;

		$record = $records[0];

		return new SMRatingItem($record["pageid"], (int)$record["instanceid"], (int)$record["maxvalue"], (int)$record["count"], (float)$record["value"]);
	}
}

?>
