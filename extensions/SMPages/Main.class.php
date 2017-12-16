<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/SMPages.classes.php");
require_once(dirname(__FILE__) . "/SMPagesExtension.class.php");
require_once(dirname(__FILE__) . "/FrmPages.class.php");
require_once(dirname(__FILE__) . "/FrmEditor.class.php");
require_once(dirname(__FILE__) . "/FrmViewer.class.php");

class SMPages extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;

	public function Init()
	{
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu"); // False if not installed or not enabled

		if (SMAuthentication::Authorized() === true && SMEnvironment::GetQueryValue("SMPagesEditor") !== null)
		{
			SMPagesExtensionList::GetInstance()->SetReadyState(true);
			SMPagesLinkList::GetInstance()->SetReadyState(true);
		}

		// Template override (done here rather than in FrmViewer since it must be done during PreInit or Init - Sitemagic loads the template between Init and InitComplete)

		if (SMExtensionManager::GetExecutingExtension() === $this->context->GetExtensionName())
		{
			$page = SMPagesFrmViewer::GetCurrentPage();

			if ($page->GetTemplate() !== "" && SMTemplateInfo::TemplateExists($page->GetTemplate()) === true)
				SMTemplateInfo::OverrideTemplate($page->GetTemplate());
		}
	}

	public function InitComplete()
	{
		$pages = array();

		if ($this->smMenuExists === true && SMMenuLinkList::GetInstance()->GetReadyState() === true)
			$pages = SMPagesLoader::GetPages();
		else if (SMPagesLinkList::GetInstance()->GetReadyState() === true)
			$pages = SMPagesLoader::GetPages();

		if ($this->smMenuExists === true)
		{
			if (SMMenuLinkList::GetInstance()->GetReadyState() === true)
			{
				$menuLinkList = SMMenuLinkList::GetInstance();

				foreach ($pages as $page)
					$menuLinkList->AddLink($this->getTranslation("ContentPages"), $page->GetFilename(), $page->GetUrl());
			}
		}

		if (SMPagesLinkList::GetInstance()->GetReadyState() === true)
		{
			$pagesLinkList = SMPagesLinkList::GetInstance();

			foreach ($pages as $page)
				$pagesLinkList->AddLink($this->getTranslation("ContentPages"), $page->GetFilename(), $page->GetUrl());
		}
	}

	public function Render()
	{
		$isViewer = false;

		if (SMEnvironment::GetQueryValue("SMPagesPageList") !== null)
		{
			if (SMAuthentication::Authorized() === false)
				SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

			$this->SetIsIntegrated(true);
			$frm = new SMPagesFrmPages($this->context);
		}
		else if (SMEnvironment::GetQueryValue("SMPagesEditor") !== null)
		{
			if (SMAuthentication::Authorized() === false)
				SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

			$frm = new SMPagesFrmEditor($this->context);
		}
		else
		{
			$isViewer = true;
			$frm = new SMPagesFrmViewer($this->context);
		}

		$this->context->GetTemplate()->AddHtmlClass("SMPages" . (($isViewer === true) ? "Viewer" . ((SMEnvironment::GetQueryValue("SMPagesDialog") !== null) ? "Dialog" : "") : "Admin"));

		return $frm->Render();
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuContent");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMPages", $this->getTranslation("ContentPages"), SMExtensionManager::GetExtensionUrl("SMPages") . "&SMPagesPageList"));
		}

		// Header and footer

		$tpl = $this->context->GetTemplate();

		if ($this->context->GetTemplateType() === SMTemplateType::$Normal)
		{
			$header = SMPagesPage::GetPersistentByFilename("#Header");
			$footer = SMPagesPage::GetPersistentByFilename("#Footer");

			if ($header !== null && $header->GetContent() !== "")
			{
				$headerViewer = new SMPagesFrmViewer($this->context);
				$tpl->AddHtmlClass("SMPagesCustomHeader");
				$tpl->ReplaceTag(new SMKeyValue("Header", $headerViewer->RenderPage($header)));
			}

			if ($footer !== null && $footer->GetContent() !== "")
			{
				$footerViewer = new SMPagesFrmViewer($this->context);
				$tpl->AddHtmlClass("SMPagesCustomFooter");
				$tpl->ReplaceTag(new SMKeyValue("Footer", $footerViewer->RenderPage($footer)));
			}
		}
	}

	public function TemplateUpdateComplete()
	{
		$tpl = $this->context->GetTemplate();

		// Custom content place holders targeted at current page

		$viewer = new SMPagesFrmViewer($this->context);
		$page = SMPagesFrmViewer::GetCurrentPage(); // Returns "404 Page" if SMPages is not executing extension

		if (SMExtensionManager::GetExecutingExtension() === "SMPages")
		{
			$targetPage = $page->GetFilename();
			$pagePlaceHolderFilename = $tpl->GetTagsContent("{[@" . $targetPage . ":", "]}"); // E.g. {[@Index:ContactDetails]} - reads "On Index page insert content from ContactDetails page"

			while ($pagePlaceHolderFilename !== null)
			{
				$page = SMPagesPage::GetPersistentByFilename($pagePlaceHolderFilename);

				if ($page !== null && $page->GetContent() !== "")
					$tpl->ReplaceTag(new SMKeyValue("@" . $targetPage . ":" . $pagePlaceHolderFilename, $viewer->RenderPage($page)));
				else
					$tpl->ReplaceTag(new SMKeyValue("@" . $targetPage . ":" . $pagePlaceHolderFilename, ""));

				$pagePlaceHolderFilename = $tpl->GetTagsContent("{[@" . $targetPage . ":", "]}");
			}
		}

		// Custom content place holders targeted at any page

		$pagePlaceHolderFilename = $tpl->GetTagsContent("{[@", "]}"); // E.g. {[@ContactDetails]} - reads "Insert content from ContactDetails page"

		while ($pagePlaceHolderFilename !== null)
		{
			$page = SMPagesPage::GetPersistentByFilename($pagePlaceHolderFilename);

			if ($page !== null && $page->GetContent() !== "")
				$tpl->ReplaceTag(new SMKeyValue("@" . $pagePlaceHolderFilename, $viewer->RenderPage($page)));
			else
				$tpl->ReplaceTag(new SMKeyValue("@" . $pagePlaceHolderFilename, ""));

			$pagePlaceHolderFilename = $tpl->GetTagsContent("{[@", "]}");
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMPages");

		return $this->lang->GetTranslation($key);
	}
}

?>
