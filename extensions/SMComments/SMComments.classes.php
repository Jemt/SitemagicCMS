<?php

class SMCommentsItem
{
	private $pageId;		// String (Guid)
	private $instanceId;	// Integer
	private $commentId;		// String (Guid)
	private $name;			// String
	private $comment;		// String
	private $timestamp;		// Integer

	public function __construct($pageId, $instanceId, $commentId, $name = "", $comment = "", $timestamp = -1)
	{
		SMTypeCheck::CheckObject(__METHOD__, "pageId", $pageId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "commentId", $commentId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "comment", $comment, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "timestamp", $timestamp, SMTypeCheckType::$Integer);

		$this->pageId = $pageId;
		$this->instanceId = $instanceId;
		$this->commentId = $commentId;
		$this->name = $name;
		$this->comment = $comment;
		$this->timestamp = $timestamp;
	}

	public function GetPageId()
	{
		return $this->pageId;
	}

	public function GetInstanceId()
	{
		return $this->instanceId;
	}

	public function GetCommentId()
	{
		return $this->commentId;
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

	public function SetComment($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->comment = $value;
	}

	public function GetComment()
	{
		return $this->comment;
	}

	public function GetTimeStamp()
	{
		return $this->timestamp;
	}

	// Database functions

	public function CommitPersistent()
	{
		$db = new SMDataSource("SMComments");
		$kvc = new SMKeyValueCollection();

		$this->timestamp = time();

		if (self::GetPersistent($this->commentId) !== null)
		{
			$kvc["name"] = $this->name;
			$kvc["comment"] = $this->comment;
			$kvc["timestamp"] = (string)$this->timestamp;
			$updateCount = $db->Update($kvc, "commentid = '" . $db->Escape($this->commentId) . "'");

			if ($updateCount === 0)
				return false;
		}
		else
		{
			$kvc["pageid"] = $this->pageId;
			$kvc["instanceid"] = (string)$this->instanceId;
			$kvc["commentid"] = $this->commentId;
			$kvc["name"] = $this->name;
			$kvc["comment"] = $this->comment;
			$kvc["timestamp"] = (string)$this->timestamp;
			$db->Insert($kvc);
		}

		return true;
	}

	public function DeletePersistent()
	{
		$db = new SMDataSource("SMComments");
		$deleteCount = $db->Delete("commentid = '" . $db->Escape($this->commentId) . "'");

		if ($deleteCount === 0)
			return false;

		return true;
	}

	public static function GetPersistent($commentId)
	{
		SMTypeCheck::CheckObject(__METHOD__, "commentId", $commentId, SMTypeCheckType::$String);

		$ds = new SMDataSource("SMComments");
		$records = $ds->Select("*", "commentid = '" . $ds->Escape($commentId) . "'");

		if (count($records) === 0)
			return null;

		$record = $records[0];

		return new SMCommentsItem($record["pageid"], (int)$record["instanceid"], $record["commentid"], $record["name"], $record["comment"], (int)$record["timestamp"]);
	}
}

class SMCommentsLoader
{
	public static function GetComments($pageId, $instanceId)
	{
		SMTypeCheck::CheckObject(__METHOD__, "pageId", $pageId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);

		$ds = new SMDataSource("SMComments");
		$records = $ds->Select("*", "pageid = '" . $ds->Escape($pageId) . "' AND instanceid = " . $instanceId, "timestamp DESC");

		$comments = array();

		foreach ($records as $record)
			$comments[] = new SMCommentsItem($record["pageid"], (int)$record["instanceid"], $record["commentid"], $record["name"], $record["comment"], (int)$record["timestamp"]);

		return $comments;
	}
}

?>
