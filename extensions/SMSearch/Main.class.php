<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmResults.class.php");

class SMSearch extends SMExtension
{
	private $name = null;
	private $lang = null;
	private $smMenuExists = false;
	private $smPagesExists = false;

	public function Init()
	{
		$this->name = $this->context->GetExtensionName();

		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu");	// False if not installed or not enabled
		$this->smPagesExists = SMExtensionManager::ExtensionEnabled("SMPages");	// False if not installed or not enabled
	}

	public function InitComplete()
	{
		// Add menu link

		if ($this->smMenuExists === true)
		{
			$menuLinkList = SMMenuLinkList::GetInstance();

			if ($menuLinkList->GetReadyState() === true)
				$menuLinkList->AddLink($this->getTranslation("Title"), $this->getTranslation("SearchPage"), SMExtensionManager::GetExtensionUrl($this->name));
		}

		// Add page extension

		if ($this->smPagesExists === true)
		{
			$extList = SMPagesExtensionList::GetInstance();
			if ($extList->GetReadyState() === true)
				$extList->AddExtension($this->getTranslation("Title"), $this->getTranslation("SearchField"), $this->name, "ContentPageExtension.class.php", $this->name . "ContentPageExtension", "", 180, 25);

			$pagesLinkList = SMPagesLinkList::GetInstance();
			if ($pagesLinkList->GetReadyState() === true)
				$pagesLinkList->AddLink($this->getTranslation("Title"), $this->getTranslation("SearchPage"), SMExtensionManager::GetExtensionUrl($this->name));
		}
	}

	public function Render()
	{
		$frm = new SMSearchFrmResults($this->context);
		return $frm->Render();
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler($this->name);

		return $this->lang->GetTranslation($key);
	}
}

?>
