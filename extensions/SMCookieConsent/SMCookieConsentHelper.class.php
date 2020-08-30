<?php

class SMCookieConsentHelper
{
	public $db = null;

	public function __construct()
	{
		$this->db = new SMDataSource("SMCookieConsent");
	}

	// Settings

	public function SetDialogPage($val)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$String);
		$this->setSetting("DialogPage", $val);
	}

	public function GetDialogPage()
	{
		return $this->getSetting("DialogPage");
	}

	public function GetDialogPages()
	{
		$res = array();

		if (SMExtensionManager::ExtensionExists("SMPages") === true)
		{
			foreach (SMPagesLoader::GetPages() as $page)
			{
				$res[$page->GetId()] = $page->GetFilename();
			}
		}

		return $res;
	}

	public function GetDialogPageContent()
	{
		$res = "";

		if (SMExtensionManager::ExtensionExists("SMPages") === true)
		{
			$page = SMPagesPage::GetPersistentByGuid($this->GetDialogPage());

			if ($page !== null)
			{
				$res = $page->GetContent();
			}
		}

		return $res;
	}

	public function SetDialogPosition($val)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$String);
		$this->setSetting("DialogPosition", $val);
	}

	public function GetDialogPosition()
	{
		return $this->getSetting("DialogPosition");
	}

	public function SetDenyText($val)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$String);
		$this->setSetting("DialogDenyText", $val);
	}

	public function GetDenyText()
	{
		return $this->getSetting("DialogDenyText");
	}

	public function SetAcceptText($val)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$String);
		$this->setSetting("DialogAcceptText", $val);
	}

	public function GetAcceptText()
	{
		return $this->getSetting("DialogAcceptText");
	}

	public function SetConsentDuration($val)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$Integer);
		$this->setSetting("DialogConsentDuration", (string)$val);
	}

	public function GetConsentDuration()
	{
		$duration = $this->getSetting("DialogConsentDuration");
		return ($duration !== "" ? (int)$duration : 1);
	}

	private function setSetting($key, $value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		SMAttributes::SetAttribute("SMCookieConsent" . $key, $value); // Commit automatically at the end of server request
	}

	private function getSetting($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		$val = SMAttributes::GetAttribute("SMCookieConsent" . $key);
		return (($val !== null) ? $val : "");
	}

	// Modules

	public function AddModule($name, $description, $code)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "description", $description, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "code", $code, SMTypeCheckType::$String);

		$kvc = new SMKeyValueCollection();
		$kvc["name"] = $name;
		$kvc["description"] = $description;
		$kvc["code"] = $code;
		$kvc["acceptedall"] = "0";
		$kvc["rejectedall"] = "0";
		$kvc["acceptedperiod"] = "0";
		$kvc["rejectedperiod"] = "0";

		$this->db->Insert($kvc);
		$this->db->Commit();
	}

	public function SetModule($name, $newName, $newDescription, $newCode)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "newName", $newName, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "newDescription", $newDescription, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "newCode", $newCode, SMTypeCheckType::$String);

		$kvc = new SMKeyValueCollection();
		$kvc["name"] = $newName;
		$kvc["description"] = $newDescription;
		$kvc["code"] = $newCode;

		if ($this->db->Update($kvc, "name = '" . $this->db->Escape($name) . "'") === 0)
		{
			$this->db->Insert($kvc);
		}

		$this->db->Commit();
	}

	public function GetModules()
	{
		$res = array();

		$modules = $this->db->Select("*");
		foreach ($modules as $module)
		{
			$res[] = $module["name"];
		}

		return $res;
	}

	public function GetModuleDescription($name)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);

		$kvc = $this->db->Select("*", "name = '" . $this->db->Escape($name) . "'");
		return ((count($kvc) !== 0) ? $kvc[0]["description"] : null);
	}

	public function GetModuleCode($name)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);

		$kvc = $this->db->Select("*", "name = '" . $this->db->Escape($name) . "'");
		return ((count($kvc) !== 0) ? $kvc[0]["code"] : null);
	}

	public function DeleteModule($name)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);
		$this->db->Delete("name = '" . $this->db->Escape($name) . "'");
		$this->db->Commit();
	}
}

?>