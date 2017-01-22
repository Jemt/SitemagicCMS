<?php

class SMContactSettings
{
	private static $instanceId = "";

	public static function SetRecipients($recipients)
	{
		SMTypeCheck::CheckObject(__METHOD__, "recipients", $recipients, SMTypeCheckType::$String);

		$recipients = str_replace(" ", "", $recipients);
		$recipients = str_replace(";", ",", $recipients);

		SMAttributes::SetAttribute("SMContact" . self::$instanceId . "Recipients", $recipients);
	}

	public static function GetRecipients()
	{
		if (SMAttributes::AttributeExists("SMContact" . self::$instanceId . "Recipients") === false)
			return "";

		return SMAttributes::GetAttribute("SMContact" . self::$instanceId . "Recipients");
	}

	public static function SetSubject($subject)
	{
		SMTypeCheck::CheckObject(__METHOD__, "subject", $subject, SMTypeCheckType::$String);
		SMAttributes::SetAttribute("SMContact" . self::$instanceId . "Subject", $subject);
	}

	public static function GetSubject()
	{
		if (SMAttributes::AttributeExists("SMContact" . self::$instanceId . "Subject") === false)
			return "";

		return SMAttributes::GetAttribute("SMContact" . self::$instanceId . "Subject");
	}

	public static function SetSuccessMessage($msg)
	{
		SMTypeCheck::CheckObject(__METHOD__, "msg", $msg, SMTypeCheckType::$String);
		SMAttributes::SetAttribute("SMContact" . self::$instanceId . "SuccessMessage", $msg);
	}

	public static function GetSuccessMessage()
	{
		if (SMAttributes::AttributeExists("SMContact" . self::$instanceId . "SuccessMessage") === false)
			return "";

		return SMAttributes::GetAttribute("SMContact" . self::$instanceId . "SuccessMessage");
	}

	public static function SetAlternativeInstanceId($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		self::$instanceId = $id;
	}
}

class SMContactFieldTypes
{
	public static $Textfield = "Textfield";
	public static $Textbox = "Textbox";
	public static $Checkbox = "Checkbox";
	public static $Email = "Email";
	public static $Attachment = "Attachment";
}

class SMContactField
{
	private static $instanceId = "";

	private $id;		// string
	private $title;		// string
	private $type;		// string
	private $position;	// int

	public function __construct($title, $type, $id = "", $position = 0)
	{
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "position", $position, SMTypeCheckType::$Integer);

		if (property_exists("SMContactFieldTypes", $type) === false)
			throw new Exception("Invalid field type - use SMContactFieldTypes::FieldType");

		$this->id = (($id !== "") ? $id : SMRandom::CreateGuid());
		$this->title = $title;
		$this->type = $type;
		$this->position = $position;
	}

	public function GetId()
	{
		return substr($this->id, strlen(self::$instanceId));
	}

	public function GetTitle()
	{
		return $this->title;
	}

	public function SetTitle($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->title = $value;
	}

	public function GetType()
	{
		return $this->type;
	}

	public function SetType($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if (property_exists("SMContactFieldTypes", $value) === false)
			throw new Exception("Invalid field type - use SMContactFieldTypes::FieldType");

		$this->type = $value;
	}

	public function GetPosition()
	{
		return $this->position;
	}

	public function SetPosition($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Integer);
		$this->position = $value;
	}

	// Database functions

	public function DeletePersistent()
	{
		$ds = new SMDataSource("SMContact");
		$deleteCount = $ds->Delete("id LIKE '" . $ds->Escape(self::$instanceId) . "%' AND title = '" . $ds->Escape($this->title) . "'");

		if ($deleteCount === 0)
			return false;

		//$ds->Commit();
		return true;
	}

	// Make sure new contact fields are added to the collection class and Sort(true) is called afterwards,
	// in order to ensure properly positioned items. Creating multiple contact fields and committing them
	// results in multiple fields with the same position (0), which will break the move functionality of
	// items - until Sort is invoked again. Sort provides each contact field with a new unique position number.
	public function CommitPersistent()
	{
		$ds = new SMDataSource("SMContact");
		$data = new SMKeyValueCollection();

		if (self::GetPersistentById($this->id) !== null)
		{
			$data["title"] = $this->title;
			$data["type"] = $this->type;
			$data["position"] = (string)$this->position;
			$updateCount = $ds->Update($data, "id = '" . $ds->Escape(self::$instanceId . $this->id) . "'");

			if ($updateCount === 0)
				return false;
		}
		else
		{
			$data["id"] = self::$instanceId . $this->id;
			$data["title"] = $this->title;
			$data["type"] = $this->type;
			$data["position"] = (string)$this->position;
			$ds->Insert($data);
		}

		//$ds->Commit();
		return true;
	}

	public static function GetPersistentById($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		return self::getPersistent("id", $id);
	}

	public static function GetPersistentByTitle($title)
	{
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		return self::getPersistent("title", $title);
	}

	private static function getPersistent($field, $search)
	{
		SMTypeCheck::CheckObject(__METHOD__, "field", $field, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);

		$ds = new SMDataSource("SMContact");
		$kvcs = array();

		if ($field === "id")
			$kvcs = $ds->Select("*", "id = '" . $ds->Escape(self::$instanceId . $search) . "'");
		else if ($field === "title")
			$kvcs = $ds->Select("*", "id LIKE '" . $ds->Escape(self::$instanceId) . "%' AND title = '" . $ds->Escape($search) . "'");

		if (count($kvcs) !== 1)
			return null;

		return new SMContactField($kvcs[0]["title"], $kvcs[0]["type"], $kvcs[0]["id"], (int)$kvcs[0]["position"]);
	}

	public static function SetAlternativeInstanceId($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		self::$instanceId = $id;
	}
}

class SMContactFields
{
	private static $instanceId = "";

	private $fields; // SMContactField[]

	public function __construct()
	{
		$this->fields = array();
	}

	public function GetFields()
	{
		return $this->fields;
	}

	public function SetFields($fields)
	{
		SMTypeCheck::CheckArray(__METHOD__, "fields", $fields, "SMContactField");
		$this->fields = $fields;
	}

	public function GetFieldById($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		foreach ($this->fields as $field)
			if ($field->GetId() === $id)
				return $field;

		return null;
	}

	public function GetFieldByTitle($title)
	{
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);

		foreach ($this->fields as $field)
			if ($field->GetTitle() === $title)
				return $field;

		return null;
	}

	public function AddField(SMContactField $field, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		if (count($this->fields) > 0)
			$field->SetPosition($this->fields[count($this->fields) - 1]->GetPosition() + 1);

		$this->fields[] = $field;

		if ($commit === true)
			$field->CommmitPersistent();
	}

	public function Sort($commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		$tmp = null;
		$modified = true;

		while ($modified === true)
		{
			$modified = false;

			for ($i = 0 ; $i < count($this->fields) ; $i++)
			{
				if ($i === 0)
					continue;

				if ($this->fields[$i - 1]->GetPosition() > $this->fields[$i]->GetPosition())
				{
					$tmp = $this->fields[$i - 1];

					$this->fields[$i - 1] = $this->fields[$i];
					$this->fields[$i] = $tmp;

					$modified = true;
				}
			}
		}

		for ($i = 0 ; $i < count($this->fields) ; $i++)
		{
			$this->fields[$i]->SetPosition($i + 1);

			if ($commit === true)
				$this->fields[$i]->CommitPersistent(true);
		}
	}

	public function RemoveField($id, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		$toRemove = null;
		$newFields = array();

		foreach ($this->fields as $field)
		{
			if ($field->GetId() !== $id)
				$newFields[] = $field;
			else
				$toRemove = $field;
		}

		if ($toRemove === null)
			return false;

		$this->fields = $newFields;

		if ($commit === true)
			return $toRemove->DeletePersistent();

		return true;
	}

	public function MoveFieldUp($id, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		return $this->moveField($id, "up", $commit);
	}

	public function MoveFieldDown($id, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		return $this->moveField($id, "down", $commit);
	}

	private function moveField($id, $direction, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "direction", $direction, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		$index = -1;
		for ($i = 0 ; $i < count($this->fields) ; $i++)
		{
			if ($this->fields[$i]->GetId() === $id)
			{
				$index = $i;
				break;
			}
		}

		// Cancel if not found, cancel if in top and moving up, cancel if in bottom and moving down
		if ($index === -1 || ($index === 0 && $direction === "up") || $index === (count($this->fields) - 1) && $direction === "down")
			return false;

		$toMove = $this->fields[$index];
		$toMovePosition = $toMove->GetPosition();

		if ($direction === "up")
		{
			$toMove->SetPosition($this->fields[$index - 1]->GetPosition());
			$this->fields[$index - 1]->SetPosition($toMovePosition);

			$this->fields[$index] = $this->fields[$index - 1];
			$this->fields[$index - 1] = $toMove;
		}
		else
		{
			$toMove->SetPosition($this->fields[$index + 1]->GetPosition());
			$this->fields[$index + 1]->SetPosition($toMovePosition);

			$this->fields[$index] = $this->fields[$index + 1];
			$this->fields[$index + 1] = $toMove;
		}

		if ($commit === true)
		{
			$result = $toMove->CommitPersistent();

			if ($result === false)
				return false;

			return $this->fields[$index]->CommitPersistent();
		}

		return true;
	}

	public function LoadPersistentFields()
	{
		$ds = new SMDataSource("SMContact");
		$kvcs = $ds->Select("*", "id LIKE '" . $ds->Escape(self::$instanceId) . "%'", "position ASC");

		foreach ($kvcs as $kvc)
		{
			if (self::$instanceId !== "") // Only instance specific fields selected
				$this->fields[] = new SMContactField($kvc["title"], $kvc["type"], substr($kvc["id"], strlen(self::$instanceId)), (int)$kvc["position"]);
			else if (SMStringUtilities::Validate($kvc["id"], SMValueRestriction::$Guid) === true) // All fields got selected - only include those with valid GUID (original entries)
				$this->fields[] = new SMContactField($kvc["title"], $kvc["type"], substr($kvc["id"], strlen(self::$instanceId)), (int)$kvc["position"]);
		}
	}

	public static function SetAlternativeInstanceId($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		self::$instanceId = $id;

		SMContactSettings::SetAlternativeInstanceId($id);
		SMContactField::SetAlternativeInstanceId($id);
	}
}

?>
