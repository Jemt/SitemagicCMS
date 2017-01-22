<?php

class SMImageMontage extends SMExtension
{
	public function PreInit()
	{
		$this->executeRunScripts("PreInit");
	}

	public function Init()
	{
		$this->executeRunScripts("Init");
	}

	public function InitComplete()
	{
		$this->executeRunScripts("InitComplete");

		// Make extensions available to menu and page editor's link picker, and to page editor's page extension picker.
		// This allows user to manually add links to these extensions to the menu and content pages, as well as insert
		// page extension into content pages.

		$addToMenuPicker = (SMExtensionManager::ExtensionEnabled("SMMenu") === true && SMMenuLinkList::GetInstance()->GetReadyState() === true);
		$addToPagePicker = (SMExtensionManager::ExtensionEnabled("SMPages") === true && SMPagesLinkList::GetInstance()->GetReadyState() === true);
		$addToPageExtensions = (SMExtensionManager::ExtensionEnabled("SMPages") === true && SMPagesExtensionList::GetInstance()->GetReadyState() === true);

		if ($addToMenuPicker === true || $addToPagePicker === true || $addToPageExtensions === true)
		{
			$basePath = SMExtensionManager::GetExtensionPath($this->context->GetExtensionName());
			$files = SMFileSystem::GetFiles($basePath);

			$cfg = null;
			$render = false;
			$linkCat = null;
			$linkUrl = null;

			foreach ($files as $file)
			{
				if (($addToMenuPicker === true || $addToPagePicker === true) && SMStringUtilities::EndsWith($file, ".extension.php") === true)
				{
					$cfg = $this->getExtCfg();
					$render = false;

					ob_start();
					require($basePath . "/" . $file);
					ob_end_clean();

					$cfg = ((isset($cfg) === true) ? $this->fixExtCfg($cfg) : $this->getExtCfg());

					if ($addToMenuPicker === true && $cfg["MenuLinkPicker"]["Title"] !== "")
					{
						$linkCat = (($cfg["MenuLinkPicker"]["Category"] !== "") ? $cfg["MenuLinkPicker"]["Category"] : $this->context->GetExtensionName());
						$linkUrl = SMExtensionManager::GetExtensionUrl($this->context->GetExtensionName());
						$linkUrl .= "&" . $this->context->GetExtensionName() . "Display=" . substr($file, 0, strpos($file, ".extension.php"));

						SMMenuLinkList::GetInstance()->AddLink($linkCat, $cfg["MenuLinkPicker"]["Title"], $linkUrl);
					}

					if ($addToPagePicker === true && $cfg["PageLinkPicker"]["Title"] !== "")
					{
						$linkCat = (($cfg["PageLinkPicker"]["Category"] !== "") ? $cfg["PageLinkPicker"]["Category"] : $this->context->GetExtensionName());
						$linkUrl = SMExtensionManager::GetExtensionUrl($this->context->GetExtensionName());
						$linkUrl .= "&" . $this->context->GetExtensionName() . "Display=" . substr($file, 0, strpos($file, ".extension.php"));

						SMPagesLinkList::GetInstance()->AddLink($linkCat, $cfg["PageLinkPicker"]["Title"], $linkUrl);
					}
				}
				else if ($addToPageExtensions === true && SMStringUtilities::EndsWith($file, ".pageextension.php") === true)
				{
					$cfg = $this->getPageExtCfg();
					$render = false;

					ob_start();
					require($basePath . "/" . $file);
					ob_end_clean();

					$cfg = ((isset($cfg) === true) ? $this->fixPageExtCfg($cfg) : $this->getPageExtCfg());

					if ($cfg["Title"] !== "")
						SMPagesExtensionList::GetInstance()->AddExtension((($cfg["Category"] !== "") ? $cfg["Category"] : $this->context->GetExtensionName()), $cfg["Title"], $this->context->GetExtensionName(), "PageExtension.class.php", $this->context->GetExtensionName() . "PageExtension", substr($file, 0, strpos($file, ".pageextension.php")), (int)$cfg["Width"], (int)$cfg["Height"]);
				}
			}
		}
	}

	public function PreRender()
	{
		$this->executeRunScripts("PreRender");
	}

	public function RenderComplete()
	{
		$this->executeRunScripts("RenderComplete");
	}

	public function PreTemplateUpdate()
	{
		$this->executeRunScripts("PreTemplateUpdate");

		// Add extensions to navigation menu (normal link or link in the Admin menu)

		if (SMExtensionManager::ExtensionEnabled("SMMenu") === true && $this->context->GetTemplateType() === SMTemplateType::$Normal)
		{
			$basePath = SMExtensionManager::GetExtensionPath($this->context->GetExtensionName());
			$files = SMFileSystem::GetFiles($basePath);

			$cfg = null;
			$render = false;
			$linkId = null;
			$linkUrl = null;
			$linkPos = null;

			foreach ($files as $file)
			{
				if (SMStringUtilities::EndsWith($file, ".extension.php") === false)
					continue;

				$cfg = $this->getExtCfg();
				$render = false;

				ob_start();
				require($basePath . "/" . $file);
				ob_end_clean();

				$cfg = ((isset($cfg) === true) ? $this->fixExtCfg($cfg) : $this->getExtCfg());

				if ($cfg["Menu"]["Title"] !== "")
				{
					$linkId = SMRandom::CreateGuid();
					$linkUrl = SMExtensionManager::GetExtensionUrl($this->context->GetExtensionName());
					$linkUrl .= "&" . $this->context->GetExtensionName() . "Display=" . substr($file, 0, strpos($file, ".extension.php"));
					$linkPos = ((strtolower($cfg["Menu"]["Position"]) === "beginning") ? SMMenuItemAppendMode::$Beginning : SMMenuItemAppendMode::$End);

					if (strtolower($cfg["Menu"]["Target"]) === "admin" && SMMenuManager::GetInstance()->GetChild("SMMenuAdmin") !== null)
						SMMenuManager::GetInstance()->GetChild("SMMenuAdmin")->AddChild(new SMMenuItem($linkId, $cfg["Menu"]["Title"], $linkUrl, $linkPos));
					else if (strtolower($cfg["Menu"]["Target"]) === "content" && SMMenuManager::GetInstance()->GetChild("SMMenuContent") !== null)
						SMMenuManager::GetInstance()->GetChild("SMMenuContent")->AddChild(new SMMenuItem($linkId, $cfg["Menu"]["Title"], $linkUrl, $linkPos));
					else if (strtolower($cfg["Menu"]["Target"]) !== "admin" && strtolower($cfg["Menu"]["Target"]) !== "content")
						SMMenuManager::GetInstance()->AddChild(new SMMenuItem($linkId, $cfg["Menu"]["Title"], $linkUrl, $linkPos));
				}
			}
		}
	}

	public function TemplateUpdateComplete()
	{
		$this->executeRunScripts("TemplateUpdateComplete");
	}

	public function PreOutput()
	{
		$this->executeRunScripts("PreOutput");
	}

	public function OutputComplete()
	{
		$this->executeRunScripts("OutputComplete");
	}

	public function Unload()
	{
		$this->executeRunScripts("Unload");
	}

	public function Finalize()
	{
		$this->executeRunScripts("Finalize");
	}

	public function Render()
	{
		$this->executeRunScripts("Render");

		$ext = SMEnvironment::GetQueryValue($this->context->GetExtensionName() . "Display", SMValueRestriction::$SafePath);
		$ext = $ext . ".extension.php";
		$file = SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/" . $ext;

		if (SMFileSystem::FileExists($file) === false)
			throw new Exception("Extension file '" . $ext . "' does not exist!");

		$cfg = $this->getExtCfg();
		$render = true;

		ob_start();
		require($file);
		$output = ob_get_contents();
		ob_end_clean();

		$cfg = ((isset($cfg) === true) ? $this->fixExtCfg($cfg) : $this->getExtCfg());

		if (strtolower($cfg["Settings"]["FormType"]) === "multipart")
			$this->context->GetForm()->SetContentType(SMFormContentType::$MultiPart);

		if (strtolower($cfg["Settings"]["LoginRequired"]) === "yes" && SMAuthentication::Authorized() === false)
			$output = "Please log in to use this extension!";

		if ($cfg["Settings"]["PageTitle"] !== "")
			$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $cfg["Settings"]["PageTitle"]));

		return $output;
	}

	private $runScripts = null;
	private function executeRunScripts($stage)
	{
		SMTypeCheck::CheckObject(__METHOD__, "stage", $stage, SMTypeCheckType::$String, true);

		if ($this->runScripts === null)
		{
			$this->runScripts = array();

			$basePath = SMExtensionManager::GetExtensionPath($this->context->GetExtensionName());
			$files = SMFileSystem::GetFiles($basePath);

			foreach ($files as $file)
				if (SMStringUtilities::EndsWith($file, ".run.php") === true)
					$this->runScripts[] = $basePath . "/" . $file;
		}

		$cfg = null;

		foreach ($this->runScripts as $file)
		{
			$cfg = array();
			$cfg["Stage"] = $stage;

			ob_start();
			require($file);
			ob_end_clean();
		}
	}

	private function getExtCfg()
	{
		$cfg = array();
		$cfg["Menu"] = array();
		$cfg["Menu"]["Title"] = "";
		$cfg["Menu"]["Target"] = "Normal";
		$cfg["Menu"]["Position"] = "End";
		$cfg["MenuLinkPicker"] = array();
		$cfg["MenuLinkPicker"]["Title"] = "";
		$cfg["MenuLinkPicker"]["Category"] = "";
		$cfg["PageLinkPicker"]["Title"] = "";
		$cfg["PageLinkPicker"]["Category"] = "";
		$cfg["Settings"] = array();
		$cfg["Settings"]["LoginRequired"] = "No";
		$cfg["Settings"]["FormType"] = "Default";
		$cfg["Settings"]["PageTitle"] = "";
		return $cfg;
	}

	private function fixExtCfg($cfg)
	{
		$fix = $this->getExtCfg();
		$fix["Menu"]["Title"] = ((empty($cfg["Menu"]["Title"]) === false) ? $cfg["Menu"]["Title"] : $fix["Menu"]["Title"]);
		$fix["Menu"]["Target"] = ((empty($cfg["Menu"]["Target"]) === false) ? $cfg["Menu"]["Target"] : $fix["Menu"]["Target"]);
		$fix["Menu"]["Position"] = ((empty($cfg["Menu"]["Position"]) === false) ? $cfg["Menu"]["Position"] : $fix["Menu"]["Position"]);
		$fix["MenuLinkPicker"]["Title"] = ((empty($cfg["MenuLinkPicker"]["Title"]) === false) ? $cfg["MenuLinkPicker"]["Title"] : $fix["MenuLinkPicker"]["Title"]);
		$fix["MenuLinkPicker"]["Category"] = ((empty($cfg["MenuLinkPicker"]["Category"]) === false) ? $cfg["MenuLinkPicker"]["Category"] : $fix["MenuLinkPicker"]["Category"]);
		$fix["PageLinkPicker"]["Title"] = ((empty($cfg["PageLinkPicker"]["Title"]) === false) ? $cfg["PageLinkPicker"]["Title"] : $fix["PageLinkPicker"]["Title"]);
		$fix["PageLinkPicker"]["Category"] = ((empty($cfg["PageLinkPicker"]["Category"]) === false) ? $cfg["PageLinkPicker"]["Category"] : $fix["PageLinkPicker"]["Category"]);
		$fix["Settings"]["LoginRequired"] = ((empty($cfg["Settings"]["LoginRequired"]) === false) ? $cfg["Settings"]["LoginRequired"] : $fix["Settings"]["LoginRequired"]);
		$fix["Settings"]["FormType"] = ((empty($cfg["Settings"]["FormType"]) === false) ? $cfg["Settings"]["FormType"] : $fix["Settings"]["FormType"]);
		$fix["Settings"]["PageTitle"] = ((empty($cfg["Settings"]["PageTitle"]) === false) ? $cfg["Settings"]["PageTitle"] : $fix["Settings"]["PageTitle"]);
		return $fix;
	}

	private function getPageExtCfg()
	{
		$cfg = array();
		$cfg["Title"] = "";
		$cfg["Category"] = "";
		$cfg["Width"] = "100";
		$cfg["Height"] = "100";
		$cfg["FormType"] = "Default";
		return $cfg;
	}

	private function fixPageExtCfg($cfg)
	{
		$fix = $this->getPageExtCfg();
		$fix["Title"] = ((empty($cfg["Title"]) === false) ? $cfg["Title"] : $fix["Title"]);
		$fix["Category"] = ((empty($cfg["Category"]) === false) ? $cfg["Category"] : $fix["Category"]);
		$fix["Width"] = ((empty($cfg["Width"]) === false && ((int)$cfg["Width"]) > 0) ? $cfg["Width"] : $fix["Width"]);
		$fix["Height"] = ((empty($cfg["Height"]) === false && ((int)$cfg["Height"]) > 0) ? $cfg["Height"] : $fix["Height"]);
		$fix["FormType"] = ((empty($cfg["FormType"]) === false) ? $cfg["FormType"] : $fix["FormType"]);
		return $fix;
	}

	private function getUrl($file, $urlEncode = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "file", $file, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "urlEncode", $urlEncode, SMTypeCheckType::$Boolean);

		$fullPath = SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/" . $file;

		if (SMStringUtilities::Validate($file, SMValueRestriction::$SafePath) === false)
			throw new Exception("Security exception - file reference '" . $file . "' is not considered safe!");
		if (SMFileSystem::FileExists($fullPath) === false)
			throw new Exception("File '" . $file . "' does not exist!");
		if (strpos($file, ".extension.php") === false)
			throw new Exception("File '" . $file . "' is not a Pure PHP Extension file!");

		$url = "";

		if ($urlEncode === false)
			$url = SMExtensionManager::GetExtensionUrl($this->context->GetExtensionName());
		else
			$url = SMExtensionManager::GetExtensionUrlEncoded($this->context->GetExtensionName());

		$url .= "&" . (($urlEncode === true) ? "amp;" : "") . $this->context->GetExtensionName() . "Display=" . substr($file, 0, strpos($file, ".extension.php"));

		return $url;
	}
}

?>
