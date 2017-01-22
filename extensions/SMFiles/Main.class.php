<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmManager.class.php");
require_once(dirname(__FILE__) . "/FrmUpload.class.php");

class SMFiles extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;

	public function GetExecutionModes()
	{
		return array(SMExecutionMode::$Shared, SMExecutionMode::$Dedicated);
	}

	public function Init()
	{
		// Dedicated execution mode used by uploader
		if ($this->context->GetExecutionMode() === SMExecutionMode::$Dedicated)
			return;

		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu"); // False if not installed or not enabled
	}

	public function InitComplete()
	{
		// SMExtensionCommon cannot ensure styles when running in dedicated execution mode,
		// which is the case when uploading files. Done during InitComplete to make sure
		// styles are ensured at the same time SMExtensionCommon does.
		if (SMEnvironment::GetQueryValue("SMFilesUpload") !== null) // Runs in dedicated execution mode
			SMExtensionCommonUtilities::EnsureStyles();
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		if (SMEnvironment::GetQueryValue("SMFilesUpload") !== null) // Runs in dedicated execution mode
		{
			$uploader = new SMFilesFrmUpload($this->context);
			return $uploader->Render();
		}
		else // Runs in shared execution mode
		{
			$manager = new SMFilesFrmManager($this->context);
			return $manager->Render();
		}
	}

	public function PreTemplateUpdate()
	{
		if ($this->context->GetExecutionMode() === SMExecutionMode::$Dedicated)
			return;

		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuContent");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMFiles", $this->getTranslation("FileManagerTitle"), SMExtensionManager::GetExtensionUrl("SMFiles")));
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMFiles");

		return $this->lang->GetTranslation($key);
	}
}

?>
