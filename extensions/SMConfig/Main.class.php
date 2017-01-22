<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmConfig.class.php");
require_once(dirname(__FILE__) . "/FrmSubsite.class.php");

class SMConfig extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;

	public function GetExecutionModes()
	{
		return array(SMExecutionMode::$Shared, SMExecutionMode::$Dedicated);
	}

	public function Init()
	{
		// Dedicated execution mode used by subsite form
		if ($this->context->GetExecutionMode() === SMExecutionMode::$Dedicated)
			return;

		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu"); // False if not installed or not enabled
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		if (SMEnvironment::GetQueryValue("SMConfigSubsiteForm") !== null) // Runs in dedicated execution mode
		{
			// SUBSITES - BETA SECTION - START
			// REQUIRES REVIEW/IMPROVEMENTS!
			$cfg = new SMConfigFrmSubSite($this->context);
			return $cfg->Render();
			// SUBSITES - BETA SECTION - END
		}
		else // Runs in shared execution mode
		{
			$cfg = new SMConfigFrmConfig($this->context);
			return $cfg->Render();
		}
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMConfiguration", "» " . $this->getTranslation("Title"), SMExtensionManager::GetExtensionUrl("SMConfig")));
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMConfig");

		return $this->lang->GetTranslation($key);
	}
}

?>
