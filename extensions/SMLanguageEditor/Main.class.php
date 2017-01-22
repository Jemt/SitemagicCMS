<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmEditor.class.php");

class SMLanguageEditor extends SMExtension
{
	private $smMenuExists = false;

	public function Init()
	{
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu"); // False if not installed or not enabled
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
			{
				$lang = new SMLanguageHandler("SMLanguageEditor");
				$adminItem->AddChild(new SMMenuItem("SMLanguageEditor", $lang->GetTranslation("Title"), SMExtensionManager::GetExtensionUrl("SMLanguageEditor")));
			}
		}
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		$frm = new SMLanguageEditorFrmEditor($this->context);
		return $frm->Render();
	}
}

?>
