<?php

class SMFilesFrmManager implements SMIExtensionForm
{
	private $context;
	private $lang;

	private $errorFolders;
	private $errorFiles;

	private $treeFolders;
	private $txtFolderName;
	private $cmdCreateFolder;
	private $cmdRenameFolder;
	private $cmdDeleteFolder;

	private $gridFiles;
	private $txtFileName;
	private $cmdUploadFile;
	private $cmdDownloadFile;
	private $cmdRenameFile;
	private $cmdDeleteFile;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMFiles");
		$this->errorFolders = "";
		$this->errorFiles = "";

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("FileManagerTitle")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->createTreeFolders();

		$this->txtFolderName = new SMInput("SMFilesFolderName", SMInputType::$Hidden);

		$this->cmdCreateFolder = new SMLinkButton("SMFilesCreateFolder");
		$this->cmdCreateFolder->SetTitle($this->lang->GetTranslation("Create"));
		$this->cmdCreateFolder->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));
		$this->cmdCreateFolder->SetOnclick("var name = SMMessageDialog.ShowInputDialog('" . $this->lang->GetTranslation("Foldername", true) . "\\n" . $this->lang->GetTranslation("ValidCharacters", true) . "', ''); if (name === null) { return; } SMDom.SetAttribute('" . $this->txtFolderName->GetClientId() . "', 'value', name);");

		$this->cmdRenameFolder = new SMLinkButton("SMFilesRenameFolder");
		$this->cmdRenameFolder->SetTitle($this->lang->GetTranslation("Rename"));
		$this->cmdRenameFolder->SetIcon(SMImageProvider::GetImage(SMImageType::$Properties));
		$this->cmdRenameFolder->SetOnclick("var name = SMMessageDialog.ShowInputDialog('" . $this->lang->GetTranslation("Foldername", true) . "\\n" . $this->lang->GetTranslation("ValidCharacters", true) . "', ''); if (name === null) { return; } SMDom.SetAttribute('" . $this->txtFolderName->GetClientId() . "', 'value', name);");

		$this->cmdDeleteFolder = new SMLinkButton("SMFilesDeleteFolder");
		$this->cmdDeleteFolder->SetTitle($this->lang->GetTranslation("Delete"));
		$this->cmdDeleteFolder->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDeleteFolder->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("Confirm", true) . "') === false) { return false; }");

		$this->createGridFiles();

		$this->txtFileName = new SMInput("SMFilesFileName", SMInputType::$Hidden);

		$this->cmdUploadFile = new SMLinkButton("SMFilesSendFile");
		$this->cmdUploadFile->SetTitle($this->lang->GetTranslation("Upload"));
		$this->cmdUploadFile->SetIcon(SMImageProvider::GetImage(SMImageType::$Up));
		$this->cmdUploadFile->SetPostBack(false);
		$this->cmdUploadFile->SetOnclick("smFilesOpenUploader()");

		$this->cmdDownloadFile = new SMLinkButton("SMFilesDownloadFile");
		$this->cmdDownloadFile->SetTitle($this->lang->GetTranslation("Download"));
		$this->cmdDownloadFile->SetIcon(SMImageProvider::GetImage(SMImageType::$Down));

		$this->cmdRenameFile = new SMLinkButton("SMFilesRenameFile");
		$this->cmdRenameFile->SetTitle($this->lang->GetTranslation("Rename"));
		$this->cmdRenameFile->SetIcon(SMImageProvider::GetImage(SMImageType::$Properties));
		$this->cmdRenameFile->SetOnclick("var name = SMMessageDialog.ShowInputDialog('" . $this->lang->GetTranslation("Filename", true) . "\\n" . $this->lang->GetTranslation("ValidCharacters", true) . "', ''); if (name === null) { return; } SMDom.SetAttribute('" . $this->txtFileName->GetClientId() . "', 'value', name);");

		$this->cmdDeleteFile = new SMLinkButton("SMFilesDeleteFile");
		$this->cmdDeleteFile->SetTitle($this->lang->GetTranslation("Delete"));
		$this->cmdDeleteFile->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDeleteFile->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("Confirm", true) . "') === false) { return false; }");
	}

	private function createTreeFolders($resetSelected = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "resetSelected", $resetSelected, SMTypeCheckType::$Boolean);

		$rootItem = new SMTreeMenuItem("SMFilesRootItem", SMEnvironment::GetFilesDirectory(), SMEnvironment::GetFilesDirectory());
		$this->populateTreeMenuItem($rootItem);

		$this->treeFolders = new SMTreeMenu("SMFiles");
		$this->treeFolders->SetAutoPostBack(true);
		$this->treeFolders->AddChild($rootItem);

		if ($this->context->GetForm()->PostBack() === false)
		{
			$imagesItem = $this->treeFolders->GetChildByValue(SMEnvironment::GetFilesDirectory() . "/images", true);

			if ($imagesItem !== null)
				$this->treeFolders->SetSelected($imagesItem->GetId());
		}

		if ($resetSelected === true) // Reset selection when e.g. a folder is renamed or removed - node no longer exists - GetSelectedId/Value returns null
			$this->treeFolders->ResetSelected();
	}

	private function populateTreeMenuItem(SMTreeMenuItem $top)
	{
		$treeItem = null;
		$folders = SMFileSystem::GetFolders(SMEnvironment::GetFilesDirectory());

		foreach ($folders as $folder)
		{
			if (strpos($folder, ".") === 0) // Hide folders starting with dot
				continue;

			// Folder in ID is base64 encoded to prevent problems if folder name contains an apostrophe
			$treeItem = new SMTreeMenuItem($top->GetId() . base64_encode($folder), $folder, SMEnvironment::GetFilesDirectory() . "/" . $folder);
			$this->populateTreeMenuItemChildren($treeItem);
			$top->AddChild($treeItem);
		}
	}

	private function populateTreeMenuItemChildren(SMTreeMenuItem $root)
	{
		$treeItem = null;
		$folders = SMFileSystem::GetFolders($root->GetValue());

		foreach ($folders as $folder)
		{
			// Folder in ID is base64 encoded to prevent problems if folder name contains an apostrophe
			$treeItem = new SMTreeMenuItem($root->GetId() . base64_encode($folder), $folder, $root->GetValue() . "/" . $folder);
			$this->populateTreeMenuItemChildren($treeItem);
			$root->AddChild($treeItem);
		}
	}

	private function createGridFiles()
	{
		$folder = (($this->treeFolders->GetSelectedValue() !== null) ? $this->treeFolders->GetSelectedValue() : SMEnvironment::GetFilesDirectory());
		$files = SMFileSystem::GetFiles($folder);

		$data = array();
		$size = null;
		$modified = null;

		foreach ($files as $file)
		{
			if (strpos($file, ".") === 0) // Hide files starting with dot
				continue;

			$size = SMFileSystem::GetFileSize($folder . "/" . $file);
			$size = round($size / 1024, 2);
			$modified = date($this->lang->GetTranslation("DateTimeFormat"), SMFileSystem::GetFileModificationTime($folder . "/" . $file));

			// Each row much have a unique ID to ensure that changes made from
			// another session will not affect the current session.
			// Take the following example: We have a list with 3 elements indexed
			// 0, 1, and 2. Select the second element (index 1) from one session,
			// remove the same element from another session, then rename the selected
			// element in the first session. The element renamed will acutally be the
			// second element, as it has now moved up from index 2 to index 1 because
			// of a post back. This won't happen in this case, as the index is based on
			// the filename. So nothing bad happens if a file is removed or renamed.
			// Either the element is removed, or the index (md5 sum) changes.
			$data[md5($file)] = array(
				$this->lang->GetTranslation("ColFilename") => $file,
				$this->lang->GetTranslation("ColSize") => $size . " KB",
				$this->lang->GetTranslation("ColModified") => $modified
			);
		}

		if (count($data) === 0)
		{
			$data[] = array(
				$this->lang->GetTranslation("ColFilename") => "",
				$this->lang->GetTranslation("ColSize") => "",
				$this->lang->GetTranslation("ColModified") => ""
			);
		}

		$this->gridFiles = new SMGrid("SMFilesFiles");

		if (count($files) > 0)
			$this->gridFiles->EnableSelector($this->lang->GetTranslation("ColFilename"));

		if ($this->treeFolders->PerformedPostBack() === true)
			$this->gridFiles->SetRestoreSelection(false);

		$this->gridFiles->SetData($data);
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdCreateFolder->PerformedPostBack() === true)
				$this->createFolder();
			else if ($this->cmdRenameFolder->PerformedPostBack() === true)
				$this->renameFolder();
			else if ($this->cmdDeleteFolder->PerformedPostBack() === true)
				$this->deleteFolder();
			else if ($this->cmdDownloadFile->PerformedPostBack() === true)
				$this->downloadFile();
			else if ($this->cmdDeleteFile->PerformedPostBack() === true)
				$this->deleteFile();
			else if ($this->cmdRenameFile->PerformedPostBack() === true)
				$this->renameFile();
		}
	}

	private function createFolder()
	{
		if ($this->validateFileSystemEntryName($this->txtFolderName->GetValue(), "folders") === false)
			return;

		$parent = $this->getSelectedFolderPath();

		if ($parent === null)
		{
			$this->errorFolders = $this->lang->GetTranslation("FolderMissing");
			return;
		}

		$path = $parent . "/" . $this->txtFolderName->GetValue();

		$result = SMFileSystem::CreateFolder($path);

		if ($result === false)
		{
			$this->errorFolders = $this->lang->GetTranslation("FilesystemError");
			return;
		}

		$this->createTreeFolders();
	}

	private function renameFolder()
	{
		if ($this->treeFolders->GetSelectionMade() === false || $this->treeFolders->GetSelectedId() === "SMFilesRootItem")
		{
			$this->errorFolders = $this->lang->GetTranslation("SelectFolder");
			return;
		}

		if ($this->validateFileSystemEntryName($this->txtFolderName->GetValue(), "folders") === false)
			return;

		$folder = $this->getSelectedFolderPath();

		if ($folder === null)
		{
			$this->errorFolders = $this->lang->GetTranslation("FolderMissing");
			return;
		}

		$lastSlashPos = strrpos($folder, "/");
		$parent = substr($folder, 0, $lastSlashPos);
		$newPath = $parent . "/" . $this->txtFolderName->GetValue();

		if (SMFileSystem::FolderExists($newPath) === true)
		{
			$this->errorFolders = $this->lang->GetTranslation("AlreadyExists");
			return;
		}

		$result = SMFileSystem::Move($folder, $newPath);

		if ($result === false)
		{
			$this->errorFolders = $this->lang->GetTranslation("FilesystemError");
			return;
		}

		$this->createTreeFolders(true);

		// Select renamed node
		$id = $this->treeFolders->GetChildByValue($newPath, true)->GetId();
		$this->treeFolders->SetSelected($id);

		$this->createGridFiles();
	}

	private function downloadFile()
	{
		if ($this->gridFiles->GetSelectionMade() === false)
		{
			$this->errorFiles = $this->lang->GetTranslation("SelectFile");
			return;
		}

		$folder = $this->getSelectedFolderPath();

		if ($folder === null)
		{
			$this->errorFiles = $this->lang->GetTranslation("FolderMissing");
			return;
		}

		$file = $this->gridFiles->GetSelectedValue();

		if ($file === null)
		{
			$this->errorFiles = $this->lang->GetTranslation("FileMissing");
			return;
		}

		SMFileSystem::DownloadFileToClient($folder . "/" . $file);
	}

	private function deleteFolder()
	{
		if ($this->treeFolders->GetSelectionMade() === false || $this->treeFolders->GetSelectedId() === "SMFilesRootItem")
		{
			$this->errorFolders = $this->lang->GetTranslation("SelectFolder");
			return;
		}

		$folder = $this->getSelectedFolderPath();

		if ($folder === null)
		{
			$this->errorFolders = $this->lang->GetTranslation("FolderMissing");
			return;
		}

		$result = SMFileSystem::Delete($folder, true);

		if ($result === false)
		{
			$this->errorFolders = $this->lang->GetTranslation("FilesystemError");
			return;
		}

		$this->createTreeFolders(true);
		$this->createGridFiles();
	}

	private function deleteFile()
	{
		if ($this->gridFiles->GetSelectionMade() === false)
		{
			$this->errorFiles = $this->lang->GetTranslation("SelectFile");
			return;
		}

		$folder = $this->getSelectedFolderPath();

		if ($folder === null)
		{
			$this->errorFiles = $this->lang->GetTranslation("FolderMissing");
			return;
		}

		$file = $this->gridFiles->GetSelectedValue();

		if ($file === null)
		{
			$this->errorFiles = $this->lang->GetTranslation("FileMissing");
			return;
		}

		$result = SMFileSystem::Delete($folder . "/" . $file);

		if ($result === false)
		{
			$this->errorFiles = $this->lang->GetTranslation("FilesystemError");
			return;
		}

		$this->createGridFiles();
	}

	private function renameFile()
	{
		if ($this->gridFiles->GetSelectionMade() === false)
		{
			$this->errorFiles = $this->lang->GetTranslation("SelectFile");
			return;
		}

		$orgFilename = $this->gridFiles->GetSelectedValue();

		if ($orgFilename === null)
		{
			$this->errorFiles = $this->lang->GetTranslation("FileMissing");
			return;
		}

		$newFilename = $this->txtFileName->GetValue();

		if ($this->validateFileSystemEntryName($newFilename, "files") === false)
		{
			$this->errorFiles = $this->lang->GetTranslation("InvalidCharacters");
			return;
		}

		if (strpos($orgFilename, ".") !== false)
		{
			$fileExt = substr($orgFilename, strrpos($orgFilename, "."));

			if (strlen($newFilename) > strlen($fileExt))
			{
				if (substr($newFilename, strlen($newFilename) - strlen($fileExt)) !== $fileExt)
					$newFilename .= $fileExt;
			}
			else
			{
				$newFilename .= $fileExt;
			}
		}

		$folder = $this->getSelectedFolderPath();

		if ($folder === null)
		{
			$this->errorFiles = $this->lang->GetTranslation("FolderMissing");
			return;
		}

		if (SMFileSystem::FileExists($folder . "/" . $newFilename) === true)
		{
			$this->errorFiles = $this->lang->GetTranslation("AlreadyExists");
			return;
		}

		$result = SMFileSystem::Move($folder . "/" . $orgFilename, $folder . "/" . $newFilename);

		if ($result === false)
		{
			$this->errorFiles = $this->lang->GetTranslation("FilesystemError");
			return;
		}

		$this->createGridFiles();
	}

	private function getSelectedFolderPath()
	{
		// Selected item has been removed in another session (possibly)
		if ($this->treeFolders->GetSelectionMade() === true && $this->treeFolders->GetSelectedId() === null)
			return null;

		return $this->treeFolders->GetSelectedValue();
	}

	private function validateFileSystemEntryName($value, $log)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "log", $log, SMTypeCheckType::$String);

		if (SMStringUtilities::Validate($value, SMValueRestriction::$Filename) === false)
		{
			if ($log === "folders")
				$this->errorFolders = $this->lang->GetTranslation("InvalidCharacters");
			else
				$this->errorFiles = $this->lang->GetTranslation("InvalidCharacters");

			return false;
		}

		return true;
	}

	public function Render()
	{
		$output = "";

		$output .= "
		<table style=\"width: 100%\">
			<tr>
				<td style=\"vertical-align: top; width: 285px\">
					" . $this->renderFolders() . "
				</td>
				<td style=\"width: 50px\">&nbsp;</td>
				<td style=\"vertical-align: top\">
					" . $this->renderFiles() . "
				</td>
			</tr>
		</table>

		<script type=\"text/javascript\">
		var smFilesUploader = null;

		function smFilesCloseUploader()
		{
			if (smFilesUploader !== null)
				smFilesUploader.Close();
		}

		function smFilesOpenUploader()
		{
			smFilesCloseUploader();
			var folderPath = '" . rawurlencode((($this->getSelectedFolderPath() !== null) ? $this->getSelectedFolderPath() : SMEnvironment::GetFilesDirectory())) . "';

			var width = 700;
			var height = 200;
			var posX = Math.floor((SMBrowser.GetPageWidth() / 2) - (width / 2));
			var posY = 200;

			smFilesUploader = new SMWindow(\"SMFilesUpload\");
			smFilesUploader.SetSize(width, height);
			smFilesUploader.SetPosition(posX, posY); //smFilesUploader.SetCenterWindow(true);
			smFilesUploader.SetOnCloseCallback(smFormPostBack);
			smFilesUploader.SetUrl(\"" . SMExtensionManager::GetExtensionUrl("SMFiles", SMTemplateType::$Basic, SMExecutionMode::$Dedicated) . "&SMFilesUpload&SMFilesUploadPath=\" + folderPath);
			smFilesUploader.Show();
		}
		</script>

		" . $this->txtFolderName->Render() . "
		" . $this->txtFileName->Render() . "
		";

		return $output;
	}

	private function renderFolders()
	{
		$output = "";

		if ($this->errorFolders !== "")
			$output .= SMNotify::Render($this->errorFolders);

		$output .= "
		" . $this->treeFolders->Render() . "
		<br>
		" . $this->cmdCreateFolder->Render() . "
		" . $this->cmdRenameFolder->Render() . "
		" . $this->cmdDeleteFolder->Render() . "
		";

		$fieldset = new SMFieldset("SMFilesFolders");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Folders"));

		return $fieldset->Render();
	}

	private function renderFiles()
	{
		$output = "";

		if ($this->errorFiles !== "")
			$output .= SMNotify::Render($this->errorFiles);

		$output .= "
		" . $this->gridFiles->Render() . "
		<br>
		<span style=\"float: right\">
			" . $this->cmdUploadFile->Render() . "
			" . $this->cmdDownloadFile->Render() . "
			" . $this->cmdRenameFile->Render() . "
			" . $this->cmdDeleteFile->Render() . "
		</span>
		<div style=\"clear: both\"></div>
		";

		$fieldset = new SMFieldset("SMFilesFiles");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Files"));

		return $fieldset->Render();
	}
}

?>
