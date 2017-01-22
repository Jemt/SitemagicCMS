<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/SMContact.classes.php");
require_once(dirname(__FILE__) . "/FrmSettings.class.php");
require_once(dirname(__FILE__) . "/FrmContactForm.class.php");

class SMContact extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;
	private $smPagesExists = false;

	public function Init()
	{
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu");	// False if not installed or not enabled
		$this->smPagesExists = SMExtensionManager::ExtensionEnabled("SMPages");	// False if not installed or not enabled
	}

	public function InitComplete()
	{
		if ($this->smPagesExists === true)
		{
			if (SMPagesExtensionList::GetInstance()->GetReadyState() === true)
			{
				$manager = null;
				$extList = SMPagesExtensionList::GetInstance();

				for ($i = 0 ; $i < 10 ; $i++)
				{
					$manager = new SMContactFields();
					$manager->SetAlternativeInstanceId((($i > 0) ? "CONF" . $i . "_" : ""));
					$manager->LoadPersistentFields();

					if (SMContactSettings::GetRecipients() === "" || count($manager->GetFields()) === 0)
						continue;

					$extList->AddExtension($this->getTranslation("ContactForms"), $this->getTranslation("Form") . " " . ($i + 1) . ((SMContactSettings::GetSubject() !== "") ? " (" . SMContactSettings::GetSubject() . ")" : ""), "SMContact", "ContentPageExtension.class.php", "SMContactContentPageExtension", (($i > 0) ? "CONF" . $i . "_" : ""), 300, 300);
				}
			}
		}
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		$frm = new SMContactFrmSettings($this->context);
		return $frm->Render();
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMContact", $this->getTranslation("ContactForms"), SMExtensionManager::GetExtensionUrl("SMContact")));
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMContact");

		return $this->lang->GetTranslation($key);
	}
}

?>
