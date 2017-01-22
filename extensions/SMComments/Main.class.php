<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true); // Depends on CSS

class SMComments extends SMExtension
{
	private $smPagesExists = false;
	private $lang = null;

	public function Init()
	{
		$this->smPagesExists = SMExtensionManager::ExtensionEnabled("SMPages"); // False if not installed or not enabled
	}

	public function InitComplete()
	{
		if ($this->smPagesExists === true)
		{
			if (SMPagesExtensionList::GetInstance()->GetReadyState() === true)
				SMPagesExtensionList::GetInstance()->AddExtension($this->getTranslation("Title"), $this->getTranslation("CommentBox"), "SMComments", "ContentPageExtension.class.php", "SMCommentsContentPageExtension", "", 365, 140);
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMComments");

		return $this->lang->GetTranslation($key);
	}
}

?>
