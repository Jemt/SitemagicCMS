<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/SMMenu.classes.php");
require_once(dirname(__FILE__) . "/FrmMenu.class.php");
require_once(dirname(__FILE__) . "/FrmLinkList.class.php");

class SMMenu extends SMExtension
{
	private $lang = null;

	public function Init()
	{
		if (SMAuthentication::Authorized() === true && SMEnvironment::GetQueryValue("SMMenuLinkList") !== null)
			SMMenuLinkList::GetInstance()->SetReadyState(true);

		SMMenuManager::GetInstance()->LoadPersistentMenuItems();
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		if (SMEnvironment::GetQueryValue("SMMenuLinkList") !== null)
			$frm = new SMMenuFrmLinkList($this->context);
		else
			$frm = new SMMenuFrmMenu($this->context);

		return $frm->Render();
	}

	public function RenderComplete() // Adding menu item after the privileged extension has been executed, as this extension might change authorization
	{
		if (SMAuthentication::Authorized() === true && SMEnvironment::GetQueryValue("SMMenuLinkList") === null)
		{
			SMMenuManager::GetInstance()->AddChild(new SMMenuItem("SMMenuAdmin", $this->getTranslation("AdminMenu"), ""), SMMenuItemAppendMode::$Beginning);
			SMMenuManager::GetInstance()->AddChild(new SMMenuItem("SMMenuContent", $this->getTranslation("ContentMenu"), ""), SMMenuItemAppendMode::$Beginning);
		}
	}

	public function PreTemplateUpdate()
	{
		$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuContent");

		if ($adminItem !== null)
			$adminItem->AddChild(new SMMenuItem("SMMenu", $this->getTranslation("MenuTitle"), SMExtensionManager::GetExtensionUrl("SMMenu")));
	}

	public function TemplateUpdateComplete()
	{
		if (SMMenuManager::GetInstance()->GetChild("SMMenuContent") !== null)
		{
			SMMenuManager::GetInstance()->GetChild("SMMenuContent")->Sort();
			SMMenuManager::GetInstance()->GetChild("SMMenuAdmin")->Sort();
		}

		SMMenuManager::GetInstance()->PopulateTemplate($this->context->GetTemplate());
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMMenu");

		return $this->lang->GetTranslation($key);
	}
}

?>
