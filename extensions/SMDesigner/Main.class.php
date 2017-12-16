<?php

// NOTICE: Extension runs in Dedicated Execution Mode !
// No other extensions are executed together with SMDesigner.

class SMDesigner extends SMExtension
{
	private $id = null;
	private $lang = null;

	public function Init()
	{
		$this->id = $this->context->GetExtensionName();
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$template = SMEnvironment::GetQueryValue($this->id . "Template", SMValueRestriction::$AlphaNumeric);
		$templatePath = (($template !== null) ? SMEnvironment::GetTemplatesDirectory() . "/" . $template : null);

		if ($templatePath !== null && SMFileSystem::FileExists($templatePath . "/designer.js") === true)
		{
			// Check folder and file permissions

			if (SMFileSystem::FolderIsWritable($templatePath) === false)
				return $this->getJsError("Folder '" . $templatePath . "' must be writable");
			else if (SMFileSystem::FileExists($templatePath . "/override.js") === true && SMFileSystem::FileIsWritable($templatePath . "/override.js") === false)
				return $this->getJsError("File '" . $templatePath . "/override.js" . "' must be writable");
			else if (SMFileSystem::FileExists($templatePath . "/override.css") === true && SMFileSystem::FileIsWritable($templatePath . "/override.css") === false)
				return $this->getJsError("File '" . $templatePath . "/override.css" . "' must be writable");

			// Load designer and create instance

			$this->SetIsIntegrated(false);

			$template = $this->context->GetTemplate();
			$template->RegisterResource(SMTemplateResource::$StyleSheet, SMExtensionManager::GetExtensionPath($this->id) . "/Designer.css?ver=" . SMEnvironment::GetVersion());
			$template->RegisterResource(SMTemplateResource::$JavaScript, SMExtensionManager::GetExtensionPath($this->id) . "/Designer.js?ver=" . SMEnvironment::GetVersion());

			$files = $this->getFiles(SMEnvironment::GetFilesDirectory() . "/images");

			// Disabled - difficult for the user to distinguish "images/xyz.jpg" (within template folder) from "files/images/xyz.jpg"
			/*$tplFiles = $this->getFiles($templatePath . "/images");
			for ($i = 0 ; $i < count($tplFiles) ; $i++)
				$tplFiles[$i] = str_replace($templatePath . "/", "", $tplFiles[$i]);
			$files = array_merge($files, $tplFiles);*/

			$downloadCallbackUrl = SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "callbacks/download");
			$saveCallbackUrl = SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "callbacks/save");
			$loadCallbackUrl = SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "callbacks/load");
			$gfxCallbackUrl = SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "callbacks/graphics");

			$script = "";
			$script .= "\n<script type=\"text/javascript\">";
			$script .= "\n(function()";
			$script .= "\n{";
			$script .= "\n    SMDesigner.Resources.WsDownloadUrl = " . ((class_exists("ZipArchive") === true) ? "'" . $downloadCallbackUrl . "'" : "null") . ";";
			$script .= "\n    SMDesigner.Resources.WsSaveUrl = '" . $saveCallbackUrl . "';";
			$script .= "\n    SMDesigner.Resources.WsLoadUrl = '" . $loadCallbackUrl . "';";
			$script .= "\n    SMDesigner.Resources.WsGraphicsUrl = '" . $gfxCallbackUrl . "';";
			$script .= "\n    SMDesigner.Resources.Files = [" . join(", ", $files) . "];";
			$script .= "\n    SMDesigner.Resources.Pages = " . $this->getPagesJson() . ";";
			$script .= "\n    ";
			$script .= "\n    var designer = new SMDesigner.Designer(\"" . $templatePath . "\");";
			$script .= "\n})();";
			$script .= "\n</script>";

			return $script;
		}

		// Template not supported

		$supported = "";
		$templatesDir = SMEnvironment::GetTemplatesDirectory();
		$templates = SMTemplateInfo::GetTemplates();

		foreach ($templates as $tpl)
			if (SMFileSystem::FileExists($templatesDir . "/" . $tpl . "/designer.js") === true)
				$supported .= (($supported !== "") ? "\\n" : "") . " - " . $tpl;

		return $this->getJsError("Template does not support designer!\\nSupported templates found:\\n\\n" . (($supported !== "") ? $supported : "No supported templates found!"));
	}

	public function PreTemplateUpdate()
	{
		if (SMExtensionManager::ExtensionEnabled("SMMenu") === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuContent");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem($this->id, $this->getTranslation("MenuTitle"), "javascript: (function() { var w = new SMWindow('" . $this->id . "'); w.SetSize( ((SMBrowser.GetBrowser() !== 'MSIE' ) ? 260 : 260 /*285*/), 550); w.SetResizable(false); w.SetPosition(SMBrowser.GetPageWidth() - 320, 50); w.SetUrl('" . SMExtensionManager::GetExtensionUrl($this->id, SMTemplateType::$Basic) . "&" . $this->id . "Template=" . SMTemplateInfo::GetCurrentTemplate() . "'); w.Show(); })();"));
		}
	}

	private function getFiles($folder)
	{
		SMTypeCheck::CheckObject(__METHOD__, "folder", $folder, SMTypeCheckType::$String);

		$res = array();

		if (SMFileSystem::FolderExists($folder) === false) // Might not exist
			return $res;

		$subSiteDir = SMEnvironment::GetSubsiteDirectory(); // Null for main site, sites/xyz for a subsite

		$files = SMFileSystem::GetFiles($folder);
		foreach ($files as $file)
		{
			// Removing subsite path to make sure files are always referenced the same on main site and subsites.
			// Main site : files/images/test.png
			// Subsite   : sites/demo/files/images/test.png => files/images/test.pnp
			// Without this, templates would not be portable from a subsite to a main site.
			// Notice that the files directory may be either separated from the main site (e.g. sites/xyz/files),
			// or shared with main site (files). The code below makes sure to only change a file path if it starts
			// with a subsite URL (e.g. sites/xyz).

			$res[] = "\"" . (($subSiteDir !== null && strpos($folder, $subSiteDir) === 0) ? substr($folder, strlen($subSiteDir) + 1) : $folder) . "/" . $file . "\"";
		}

		$subFolders = SMFileSystem::GetFolders($folder);
		foreach ($subFolders as $subFolder)
			$res = array_merge($res, $this->getFiles($folder . "/" . $subFolder));

		return $res;
	}

	private function getPages()
	{
		$res = array();

		if (SMExtensionManager::ExtensionEnabled("SMPages") === true)
		{
			$pages = SMPagesLoader::GetPages();

			foreach ($pages as $page)
				$res[] = array($page->GetFilename(), $page->GetId());
		}

		return $res;
	}

	private function getPagesJson()
	{
		$pages = $this->getPages();

		$json = "[";
		foreach ($pages as $page)
			$json .= (($json !== "[") ? ", " : "") . "{ 'Filename': '" . $page[0] . "', 'Id': '" . $page[1] . "' }";
		$json .= "]";

		return $json;
	}

	private function getJsError($msg)
	{
		SMTypeCheck::CheckObject(__METHOD__, "msg", $msg, SMTypeCheckType::$String);
		return "<script type=\"text/javascript\">SMMessageDialog.ShowMessageDialog(\"" . $msg . "\"); (window.opener || window.top).SMWindow.GetInstance(window.name).Close();</script>";
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler($this->id);

		return $this->lang->GetTranslation($key);
	}
}

?>
