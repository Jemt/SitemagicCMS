<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmViewer.class.php");
require_once(dirname(__FILE__) . "/FrmSettings.class.php");

class SMGallery extends SMExtension
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
		// Ensure gallery folder

		if (SMFileSystem::FolderExists(SMEnvironment::GetFilesDirectory() . "/gallery") === false)
		{
			$res = SMFileSystem::CreateFolder(SMEnvironment::GetFilesDirectory() . "/gallery");

			if ($res === false)
				return;
		}

		// Read galleries if necessary

		$galleryFolders = array();

		$getGalleries = false;

		if ($this->smMenuExists === true && SMMenuLinkList::GetInstance()->GetReadyState() === true)
			$getGalleries = true;
		else if ($this->smPagesExists === true && (SMPagesExtensionList::GetInstance()->GetReadyState() === true || SMPagesLinkList::GetInstance()->GetReadyState() === true))
			$getGalleries = true;

		if ($getGalleries === true)
		{
			$galleryFolder = SMEnvironment::GetFilesDirectory() . "/gallery";
			$galleryFolders = SMFileSystem::GetFolders($galleryFolder);
		}

		if ($this->smMenuExists === true)
		{
			if (SMMenuLinkList::GetInstance()->GetReadyState() === true)
			{
				$menuLinkList = SMMenuLinkList::GetInstance();
				$menuLinkList->AddLink($this->getTranslation("Title"), $this->getTranslation("EntireGallery"), SMExtensionManager::GetExtensionUrl("SMGallery"));

				if (count($galleryFolders) > 0)
				{
					foreach ($galleryFolders as $gallery)
						$menuLinkList->AddLink($this->getTranslation("Title"), $gallery, SMExtensionManager::GetExtensionUrl("SMGallery") . "&SMGalleryName=" . rawurlencode($gallery));
				}
			}
		}

		if ($this->smPagesExists === true)
		{
			if (SMPagesExtensionList::GetInstance()->GetReadyState() === true)
			{
				$extList = SMPagesExtensionList::GetInstance();
				$extList->AddExtension($this->getTranslation("Title"), $this->getTranslation("EntireGallery"), "SMGallery", "ContentPageExtension.class.php", "SMGalleryContentPageExtension", "", 300, 300);

				if (count($galleryFolders) > 0)
				{
					foreach ($galleryFolders as $gallery)
						$extList->AddExtension($this->getTranslation("Title"), $gallery, "SMGallery", "ContentPageExtension.class.php", "SMGalleryContentPageExtension", $gallery, 300, 300);
				}
			}

			if (SMPagesLinkList::GetInstance()->GetReadyState() === true)
			{
				$pagesLinkList = SMPagesLinkList::GetInstance();
				$pagesLinkList->AddLink($this->getTranslation("Title"), $this->getTranslation("EntireGallery"), SMExtensionManager::GetExtensionUrl("SMGallery"));

				if (count($galleryFolders) > 0)
				{
					foreach ($galleryFolders as $gallery)
						$pagesLinkList->AddLink($this->getTranslation("Title"), $gallery, SMExtensionManager::GetExtensionUrl("SMGallery") . "&SMGalleryName=" . $gallery);
				}
			}
		}
	}

	public function Render()
	{
		if (SMEnvironment::GetQueryValue("SMGalleryAdmin") !== null)
		{
			if (SMAuthentication::Authorized() === false)
				SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

			$this->SetIsIntegrated(true);
			$frm = new SMGalleryFrmSettings($this->context);
		}
		else
		{
			$gallery = SMEnvironment::GetQueryValue("SMGalleryName", SMValueRestriction::$Filename);
			$frm = new SMGalleryFrmViewer($this->context, 0, (($gallery !== null) ? $gallery : ""));
		}

		return $frm->Render();
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMGallery", $this->getTranslation("Title"), SMExtensionManager::GetExtensionUrl("SMGallery") . "&SMGalleryAdmin"));
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMGallery");

		return $this->lang->GetTranslation($key);
	}
}

?>
