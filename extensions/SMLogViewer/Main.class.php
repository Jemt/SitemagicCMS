<?php

//SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmViewer.class.php");

class SMLogViewer extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;

	public function Init()
	{
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu");
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$frm = new SMLogViewerFrmViewer($this->context);
		return $frm->Render();
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true && SMMenuManager::GetInstance()->GetChild("SMMenuAdmin") !== null)
			SMMenuManager::GetInstance()->GetChild("SMMenuAdmin")->AddChild(new SMMenuItem("SMLogViewer", $this->getTranslation("Title"), "javascript: if (window.smLogViewer !== undefined) { window.smLogViewer.Close(); } window.smLogViewer = new SMWindow('SMLogViewer'); window.smLogViewer.SetUrl('" . SMExtensionManager::GetExtensionUrl("SMLogViewer", SMTemplateType::$Basic) . "'); window.smLogViewer.SetSize(SMBrowser.GetPageWidth(), Math.floor(SMBrowser.GetPageHeight()/3)); window.smLogViewer.SetPosition(0, 999999); window.smLogViewer.Show();"));
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMLogViewer");

		return $this->lang->GetTranslation($key);
	}
}

?>
