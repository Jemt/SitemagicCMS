<?php

// Ensure gallery folder

if ($cfg["Stage"] === "Init")
{
	$galleryDir = SMEnvironment::GetFilesDirectory() . "/" . "gallery";
	if (SMFileSystem::FolderExists($galleryDir) === false)
		SMFileSystem::CreateFolder($galleryDir);
}

// Add individual galleries to link pickers

if ($cfg["Stage"] === "InitComplete")
{
	// Determine whether Sitemagic CMS needs us to populate the link pickers (only if they are being used)

	$addToMenuPicker = (SMExtensionManager::ExtensionEnabled("SMMenu") === true && SMMenuLinkList::GetInstance()->GetReadyState() === true);
	$addToPagePicker = (SMExtensionManager::ExtensionEnabled("SMPages") === true && SMPagesLinkList::GetInstance()->GetReadyState() === true);

	if ($addToMenuPicker === true || $addToPagePicker === true)
	{
		// Load language and galleries

		$language = new SMLanguageHandler($this->context->GetExtensionName());
		$galleries = SMFileSystem::GetFolders(SMEnvironment::GetFilesDirectory() . "/gallery");

		// Add galleries to link pickers

		$url = null;
		foreach ($galleries as $gallery)
		{
			$url = $this->getUrl("ImageMontage.extension.php") . "&SMImageMontageGallery=" . rawurlencode($gallery);

			if ($addToMenuPicker === true)
				SMMenuLinkList::GetInstance()->AddLink($language->GetTranslation("Title"), $gallery, $url);
			if ($addToPagePicker === true)
				SMPagesLinkList::GetInstance()->AddLink($language->GetTranslation("Title"), $gallery, $url);
		}
	}
}

?>
