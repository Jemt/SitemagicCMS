<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/SMExternalModules.classes.php");
require_once(dirname(__FILE__) . "/FrmSettings.class.php");

class SMExternalModules extends SMExtension
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
				$extList = SMPagesExtensionList::GetInstance();
				$modules = SMExternalModulesLoader::GetModules();

				foreach ($modules as $module)
					$extList->AddExtension($this->getTranslation("Title"), $module->GetName(), "SMExternalModules", "ContentPageExtension.class.php", "SMExternalModulesContentPageExtension", $module->GetGuid(), $module->GetWidth(), (($module->GetHeightUnit() === SMExternalModulesUnit::$Pixels) ? $module->GetHeight() : "50"), ($module->GetWidthUnit() === SMExternalModulesUnit::$Percent));
			}
		}
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		$frm = new SMExternalModulesFrmSettings($this->context);
		return $frm->Render();
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMExternalModules", $this->getTranslation("Title"), SMExtensionManager::GetExtensionUrl("SMExternalModules")));
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMExternalModules");

		return $this->lang->GetTranslation($key);
	}
}

?>
