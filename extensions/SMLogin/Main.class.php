<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmLogin.class.php");

class SMLogin extends SMExtension
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
		if ($this->smMenuExists === true)
		{
			if (SMMenuLinkList::GetInstance()->GetReadyState() === true)
			{
				$menuLinkList = SMMenuLinkList::GetInstance();
				$menuLinkList->AddLink($this->getTranslation("LoginForm"), $this->getTranslation("Login"), SMExtensionManager::GetExtensionUrl("SMLogin"));
				$menuLinkList->AddLink($this->getTranslation("LoginForm"), $this->getTranslation("Logout"), SMExtensionManager::GetExtensionUrl("SMLogin") . "&SMLoginFunc=logout");
			}
		}

		if ($this->smPagesExists === true)
		{
			if (SMPagesLinkList::GetInstance()->GetReadyState() === true)
			{
				$pagesLinkList = SMPagesLinkList::GetInstance();
				$pagesLinkList->AddLink($this->getTranslation("LoginForm"), $this->getTranslation("Login"), SMExtensionManager::GetExtensionUrl("SMLogin"));
				$pagesLinkList->AddLink($this->getTranslation("LoginForm"), $this->getTranslation("Logout"), SMExtensionManager::GetExtensionUrl("SMLogin") . "&SMLoginFunc=logout");
			}
		}
	}

	public function Render()
	{
		$func = SMEnvironment::GetQueryValue("SMLoginFunc", SMValueRestriction::$Alpha);

		if ($func === "logout")
		{
			SMAuthentication::Logout();
			SMLanguageHandler::RestoreSystemLanguage();
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());
		}
		else
		{
			$this->SetIsIntegrated(true);

			$login = new SMLoginFrmLogin($this->context);
			return $login->Render();
		}
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMLoginLogout", "« " . $this->getTranslation("Logout"), SMExtensionManager::GetExtensionUrl("SMLogin") . "&SMLoginFunc=logout"));
		}
	}

	public function TemplateUpdateComplete()
	{
		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("SMLogin Access", ((SMAuthentication::Authorized() === false) ? "Public" : "Admin")));
		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("SMLogin Link", ((SMAuthentication::Authorized() === false) ? $this->getTranslation("Login") : $this->getTranslation("Logout"))));
		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("SMLogin Url", SMExtensionManager::GetExtensionUrlEncoded("SMLogin") . ((SMAuthentication::Authorized() === true) ? "&amp;SMLoginFunc=logout" : "")));
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMLogin");

		return $this->lang->GetTranslation($key);
	}
}

?>
