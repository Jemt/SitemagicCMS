<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmFilesList.class.php");

class SMDownloads extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;
	private $smPagesExists = false;

	public function Init()
	{
		$file = SMEnvironment::GetQueryValue("SMDownloadsFile", SMValueRestriction::$Filename);

		if ($file !== null)
		{
			$filePath = SMEnvironment::GetFilesDirectory() . "/downloads/" . $file;

			if (SMFileSystem::FileExists($filePath) === true)
			{
				$ds = new SMDataSource("SMDownloads");
				$ds->Lock();

				if ($ds->Count("file = '" . $ds->Escape($file) . "'") === 0)
				{
					$data = new SMKeyValueCollection();
					$data["file"] = $file;
					$data["count"] = "1";
					$ds->Insert($data);
				}
				else
				{
					$records = $ds->Select("count", "file = '" . $ds->Escape($file) . "'");
					$count = (int)$records[0]["count"];
					$count++;

					$data = new SMKeyValueCollection();
					$data["count"] = (string)$count;
					$ds->Update($data, "file = '" . $ds->Escape($file) . "'");
				}

				$ds->Commit();

				SMFileSystem::DownloadFileToClient($filePath);
			}
		}

		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu");	// False if not installed or not enabled
		$this->smPagesExists = SMExtensionManager::ExtensionEnabled("SMPages");	// False if not installed or not enabled
	}

	public function InitComplete()
	{
		if (SMFileSystem::FolderExists(SMEnvironment::GetFilesDirectory() . "/downloads") === false)
		{
			$res = SMFileSystem::CreateFolder(SMEnvironment::GetFilesDirectory() . "/downloads");

			if ($res === false)
				return;
		}

		if ($this->smPagesExists === true)
		{
			if (SMPagesLinkList::GetInstance()->GetReadyState() === true)
			{
				$pagesLinkList = SMPagesLinkList::GetInstance();
				$files = SMFileSystem::GetFiles(SMEnvironment::GetFilesDirectory() . "/downloads");

				foreach ($files as $file)
					$pagesLinkList->AddLink($this->getTranslation("Title"), $file, SMExtensionManager::GetExtensionUrl("SMDownloads") . "&SMDownloadsFile=" . $file);
			}
		}
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		$frm = new SMDownloadsFrmFilesList($this->context);
		return $frm->Render();
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMDownloads", $this->getTranslation("Title"), SMExtensionManager::GetExtensionUrl("SMDownloads")));
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMDownloads");

		return $this->lang->GetTranslation($key);
	}
}

?>
