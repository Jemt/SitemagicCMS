<?php

require_once(dirname(__FILE__) . "/SMRating.class.php");

class SMRating extends SMExtension
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
			{
				$extList = SMPagesExtensionList::GetInstance();
				$extList->AddExtension($this->getTranslation("Rating"), "3 " . $this->getTranslation("Stars"), "SMRating", "ContentPageExtension.class.php", "SMRatingContentPageExtension", "3", 60, 20);
				$extList->AddExtension($this->getTranslation("Rating"), "5 " . $this->getTranslation("Stars"), "SMRating", "ContentPageExtension.class.php", "SMRatingContentPageExtension", "5", 100, 20);
				$extList->AddExtension($this->getTranslation("Rating"), "6 " . $this->getTranslation("Stars"), "SMRating", "ContentPageExtension.class.php", "SMRatingContentPageExtension", "6", 120, 20);
				$extList->AddExtension($this->getTranslation("Rating"), "10 " . $this->getTranslation("Stars"), "SMRating", "ContentPageExtension.class.php", "SMRatingContentPageExtension", "10", 200, 20);
			}
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMRating");

		return $this->lang->GetTranslation($key);
	}
}

?>
