<?php

class SMPagesFrmPages implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $cloudMode;

	private $errorForm;
	private $errorList;
	private $msgSettings;

	private $txtOriginalFilename;

	private $txtFilename;
	private $txtTitle;
	private $chkAccessible;

	private $lstTemplates;

	private $txtCopyTemplateName;
	private $cmdCopyTemplate;
	private $cmdDeleteTemplate;

	private $txtKeywords;
	private $txtDescription;
	private $chkAllowIndexing;

	private $txtPassword;
	private $chkMaskPassword;

	private $cmdAllOptions;
	private $cmdCreatePage;
	private $cmdClear;
	private $cmdSave;

	private $cmdEditHeader;
	private $cmdRemoveHeader;
	private $cmdEditFooter;
	private $cmdRemoveFooter;

	private $lstUrlTypes;
	private $chkSeoUrls;
	private $cmdSaveSettings;

	private $grdPages;
	private $cmdEdit;
	private $cmdEditContent;
	private $cmdDisplay;
	private $cmdDelete;

	private $editContentGuid;
	private $displayContentGuid;
	private $refreshPageOnEditorClosed;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMPages");
		$this->cloudMode = SMEnvironment::GetCloudEnabled();

		$this->errorForm = "";
		$this->errorList = "";
		$this->msgSettings = "";

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("ContentPages")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtOriginalFilename = new SMInput("SMPagesOriginalFilename", SMInputType::$Hidden);

		$this->txtFilename = new SMInput("SMPagesFilename", SMInputType::$Text);
		$this->txtFilename->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtFilename->SetAttribute(SMInputAttributeText::$MaxLength, "255");

		$this->txtTitle = new SMInput("SMPagesPageTitle", SMInputType::$Text);
		$this->txtTitle->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtTitle->SetAttribute(SMInputAttributeText::$MaxLength, "255");

		$this->chkAccessible = new SMInput("SMPagesAccessible", SMInputType::$Checkbox);
		if ($this->context->GetForm()->PostBack() === false)
			$this->chkAccessible->SetChecked(true);

		$this->lstTemplates = new SMOptionList("SMPagesTemplates");
		$this->lstTemplates->SetAttribute(SMOptionListAttribute::$Style, "width: 150px");
		$this->populateTemplates();

		$this->txtCopyTemplateName = new SMInput("SMPagesCopyTemplateName", SMInputType::$Hidden);

		$this->cmdCopyTemplate = new SMLinkButton("SMPagesCopyTemplate");
		$this->cmdCopyTemplate->SetTitle($this->lang->GetTranslation("CopyTemplate"));
		$this->cmdCopyTemplate->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));
		$this->cmdCopyTemplate->SetOnClick("var res = SMMessageDialog.ShowInputDialog('" . $this->lang->GetTranslation("CopyTemplateDialog", true) . "', ''); if (res !== null) { document.getElementById('" . $this->txtCopyTemplateName->GetClientId() . "').value = res; } else { return false; }");

		$this->cmdDeleteTemplate = new SMLinkButton("SMPagesDeleteTemplate");
		$this->cmdDeleteTemplate->SetTitle($this->lang->GetTranslation("DeleteTemplate"));
		$this->cmdDeleteTemplate->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDeleteTemplate->SetOnClick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteTemplateDialog", true) . "') === false) { return false; }");

		$this->txtKeywords = new SMInput("SMPagesKeywords", SMInputType::$Text);
		$this->txtKeywords->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtKeywords->SetAttribute(SMInputAttributeText::$MaxLength, "255");

		$this->txtDescription = new SMInput("SMPagesDescription", SMInputType::$Text);
		$this->txtDescription->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtDescription->SetAttribute(SMInputAttributeText::$MaxLength, "255");

		$this->chkAllowIndexing = new SMInput("SMPagesAllowIndexing", SMInputType::$Checkbox);
		$this->txtPassword = new SMInput("SMPagesPassword", SMInputType::$Password);

		if ($this->context->GetForm()->PostBack() === false)
			$this->chkAllowIndexing->SetChecked(true);
		else if ($this->txtPassword->GetValue() !== "")
			$this->chkAllowIndexing->SetChecked(false);

		$this->txtPassword->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtPassword->SetAttribute(SMInputAttributeText::$MaxLength, "255");
		$this->txtPassword->SetAttribute(SMInputAttributeText::$OnKeyUp, "if (this.value !== '' && SMDom.GetAttribute('" . $this->chkAllowIndexing->GetClientId() . "', 'checked') === 'true') { SMDom.SetAttribute('" . $this->chkAllowIndexing->GetClientId() . "', 'checked', 'false'); }");

		$this->chkAllowIndexing->SetAttribute(SMInputAttributeText::$OnChange, "if (SMDom.GetAttribute('" . $this->txtPassword->GetClientId() . "', 'value') !== '') { SMMessageDialog.ShowMessageDialog('" . $this->lang->GetTranslation("NoIndexingOnPassword", true) . "'); this.checked = false; }");

		$this->chkMaskPassword = new SMInput("SMPagesMaskPassword", SMInputType::$Checkbox);
		$this->chkMaskPassword->SetAttribute(SMInputAttributeCheckbox::$OnChange, "SMDom.GetElement('" . $this->txtPassword->GetClientId() . "').type = ((this.checked === true) ? 'password' : 'text')");
		$this->chkMaskPassword->SetChecked(true);

		$this->cmdAllOptions = new SMLinkButton("SMPagesAllOptions");
		$this->cmdAllOptions->SetTitle($this->lang->GetTranslation("AllOptions"));
		$this->cmdAllOptions->SetOnclick("SMCore.ForEach(document.getElementById('smPagesEditForm').getElementsByTagName('tr'), function(tr) { tr.style.display = ''; }); this.style.display = 'none'; return;");

		$this->cmdCreatePage = new SMLinkButton("SMPagesCreatePage");
		$this->cmdCreatePage->SetTitle($this->lang->GetTranslation("Create"));
		$this->cmdCreatePage->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));

		$this->cmdClear = new SMLinkButton("SMPagesCreateNewPage");
		$this->cmdClear->SetTitle($this->lang->GetTranslation("Clear"));
		$this->cmdClear->SetIcon(SMImageProvider::GetImage(SMImageType::$Clear));

		$this->cmdSave = new SMLinkButton("SMPagesUpdatePage");
		$this->cmdSave->SetTitle($this->lang->GetTranslation("Save"));
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));

		// Created here so that state is restored before Edit Header and Edit Footer buttons are created
		$this->cmdRemoveHeader = new SMLinkButton("SMPagesRemoveHeader");
		$this->cmdRemoveFooter = new SMLinkButton("SMPagesRemoveFooter");

		$this->cmdEditHeader = new SMLinkButton("SMPagesEditHeader");
		$this->cmdEditHeader->SetTitle((((SMPagesPage::GetPersistentByFilename("#Header") !== null && $this->cmdRemoveHeader->PerformedPostBack() === false) || $this->cmdEditHeader->PerformedPostBack() === true) ? $this->lang->GetTranslation("EditHeader") : $this->lang->GetTranslation("AddHeader")));
		$this->cmdEditHeader->SetIcon(SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/images/AddHeader.png");

		$this->cmdRemoveHeader->SetTitle($this->lang->GetTranslation("RemoveHeader"));
		$this->cmdRemoveHeader->SetIcon(SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/images/RemoveHeader.png");
		$this->cmdRemoveHeader->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteWarning", true) . "') === false) { return false; }");

		$this->cmdEditFooter = new SMLinkButton("SMPagesEditFooter");
		$this->cmdEditFooter->SetTitle((((SMPagesPage::GetPersistentByFilename("#Footer") !== null && $this->cmdRemoveFooter->PerformedPostBack() === false) || $this->cmdEditFooter->PerformedPostBack() === true) ? $this->lang->GetTranslation("EditFooter") : $this->lang->GetTranslation("AddFooter")));
		$this->cmdEditFooter->SetIcon(SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/images/AddFooter.png");

		$this->cmdRemoveFooter->SetTitle($this->lang->GetTranslation("RemoveFooter"));
		$this->cmdRemoveFooter->SetIcon(SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/images/RemoveFooter.png");
		$this->cmdRemoveFooter->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteWarning", true) . "') === false) { return false; }");

		$this->lstUrlTypes = new SMOptionList("SMPagesUrlTypes");
		$this->lstUrlTypes->SetAttribute(SMOptionListAttribute::$Style, "width: 150px");
		$this->lstUrlTypes->AddOption(new SMOptionListItem("SMPagesUrlTypeFilename", $this->lang->GetTranslation("UrlTypeFilename"), "Filename"));
		$this->lstUrlTypes->AddOption(new SMOptionListItem("SMPagesUrlTypeUniqueId", $this->lang->GetTranslation("UrlTypeUniqueId"), "UniqueId"));

		if ($this->context->GetForm()->PostBack() === false)
		{
			if (SMAttributes::GetAttribute("SMPagesSettingsUrlType") === null)
			{
				if (SMEnvironment::IsSubSite() === true) // Subsites requires support for .htaccess, so SEO friendly URLs are already enabled - prefer Filename URLs
					$this->lstUrlTypes->SetSelectedValue("Filename");
				else
					$this->lstUrlTypes->SetSelectedValue("UniqueId");
			}
			else
			{
				$this->lstUrlTypes->SetSelectedValue(SMAttributes::GetAttribute("SMPagesSettingsUrlType"));
			}
		}

		$this->chkSeoUrls = new SMInput("SMPagesSeoUrls", SMInputType::$Checkbox);

		if ($this->context->GetForm()->PostBack() === false && ((SMAttributes::GetAttribute("SMPagesSettingsSeoUrls") === "true") || (SMEnvironment::IsSubSite() === true && SMAttributes::GetAttribute("SMPagesSettingsSeoUrls") === null)))
			$this->chkSeoUrls->SetChecked(true);

		$this->cmdSaveSettings = new SMLinkButton("SMPagesSaveSettings");
		$this->cmdSaveSettings->SetTitle($this->lang->GetTranslation("Save"));
		$this->cmdSaveSettings->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));

		$this->createPageList();

		$this->cmdEdit = new SMLinkButton("SMPagesEdit");
		$this->cmdEdit->SetTitle($this->lang->GetTranslation("Edit"));
		$this->cmdEdit->SetIcon(SMImageProvider::GetImage(SMImageType::$Properties));

		$this->cmdEditContent = new SMLinkButton("SMPagesEditContent");
		$this->cmdEditContent->SetTitle($this->lang->GetTranslation("EditContent"));
		$this->cmdEditContent->SetIcon(SMImageProvider::GetImage(SMImageType::$Modify));

		$this->cmdDisplay = new SMLinkButton("SMPagesDisplay");
		$this->cmdDisplay->SetTitle($this->lang->GetTranslation("Display"));
		$this->cmdDisplay->SetIcon(SMImageProvider::GetImage(SMImageType::$Display));

		$this->cmdDelete = new SMLinkButton("SMPagesDelete");
		$this->cmdDelete->SetTitle($this->lang->GetTranslation("Delete"));
		$this->cmdDelete->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDelete->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteWarning", true) . "') === false) { return false; }");

		$this->editContentGuid = "";
		$this->displayContentGuid = "";
		$this->refreshPageOnEditorClosed = false;
	}

	private function populateTemplates($selected = null)
	{
		SMTypeCheck::CheckObject(__METHOD__, "selected", (($selected !== null) ? $selected : ""), SMTypeCheckType::$String);

		$this->lstTemplates->SetOptions(array());

		$this->lstTemplates->AddOption(new SMOptionListItem("SMPagesTemplatesEmpty", "", ""));
		$templates = SMTemplateInfo::GetTemplates();
		foreach ($templates as $t)
			$this->lstTemplates->AddOption(new SMOptionListItem("SMPagesTemplates" . $t, $t, $t));

		if ($selected !== null)
			$this->lstTemplates->SetSelectedValue($selected);
	}

	private function createPageList()
	{
		$this->grdPages = new SMGrid("SMPages");
		$this->grdPages->EnableSelector($this->lang->GetTranslation("Filename"));

		/*$accessible = "<img src=\"" . SMImageProvider::GetImage(SMImageType::$Display) . "\" alt=\"\" style=\"vertical-align: middle\">";
		$allowIndexing = "<img src=\"" . SMImageProvider::GetImage(SMImageType::$Search) . "\" alt=\"\" style=\"vertical-align: middle\">";
		$locked = "<img src=\"" . SMImageProvider::GetImage(SMImageType::$Lock) . "\" alt=\"\" style=\"vertical-align: middle\">";*/

		$pagesPersistet = SMPagesLoader::GetPages();

		$pages = array();

		foreach ($pagesPersistet as $page)
		{
			$pages[md5($page->GetFilename())] = array(
				$this->lang->GetTranslation("Filename")	=> $page->GetFilename(),
				$this->lang->GetTranslation("Modified")	=> date($this->lang->GetTranslation("DateTimeFormat"), $page->GetLastModified())/*,
				$accessible		=> (($page->GetAccessible() === true) ? $this->lang->GetTranslation("Yes") : $this->lang->GetTranslation("No")),
				$allowIndexing	=> (($page->GetAllowIndexing() === true) ? $this->lang->GetTranslation("Yes") : $this->lang->GetTranslation("No")),
				$locked			=> (($page->GetPassword() !== "") ? $this->lang->GetTranslation("Yes") : $this->lang->GetTranslation("No"))*/
			);
		}

		if (count($pagesPersistet) === 0)
		{
			$this->grdPages->DisableSelector();

			$pages[] = array(
				$this->lang->GetTranslation("Filename")	=> "",
				$this->lang->GetTranslation("Modified")	=> ""/*,
				$accessible		=> "",
				$allowIndexing	=> "",
				$locked			=> ""*/
			);
		}

		$this->grdPages->SetData($pages);
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdCopyTemplate->PerformedPostBack() === true)
				$this->copyTemplate();
			else if ($this->cmdDeleteTemplate->PerformedPostBack() === true)
				$this->deleteTemplate();
			else if ($this->cmdCreatePage->PerformedPostBack() === true)
				$this->createPage();
			else if ($this->cmdDelete->PerformedPostBack() === true)
				$this->deletePage();
			else if ($this->cmdEdit->PerformedPostBack() === true)
				$this->editPageMode();
			else if ($this->cmdEditContent->PerformedPostBack() === true)
				$this->editContent();
			else if ($this->cmdDisplay->PerformedPostBack() === true)
				$this->displayPage();
			else if ($this->cmdSave->PerformedPostBack() === true)
				$this->updatePage();
			else if ($this->cmdClear->PerformedPostBack() === true)
				$this->clearForm();
			else if ($this->cmdSaveSettings->PerformedPostBack() === true)
				$this->saveSettings();
			else if ($this->cmdEditHeader->PerformedPostBack() === true)
				$this->editSystemPage("Header");
			else if ($this->cmdRemoveHeader->PerformedPostBack() === true)
				$this->removeSystemPage("Header");
			else if ($this->cmdEditFooter->PerformedPostBack() === true)
				$this->editSystemPage("Footer");
			else if ($this->cmdRemoveFooter->PerformedPostBack() === true)
				$this->removeSystemPage("Footer");
		}
	}

	private function copyTemplate()
	{
		if ($this->cloudMode === true)
			return; // Make sure function cannot be triggered programmatically client side

		if ($this->lstTemplates->GetSelectedValue() === "")
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplateNoSelection");
			return;
		}

		$newName = $this->txtCopyTemplateName->GetValue();

		if (SMStringUtilities::Validate($newName, SMValueRestriction::$AlphaNumeric) === false)
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplateNameInvalid");
			return;
		}

		$templatesDir = SMEnvironment::GetTemplatesDirectory();

		if (SMFileSystem::FolderIsWritable($templatesDir) === false)
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplatesNotWritable");
			return;
		}

		if (SMFileSystem::FolderExists($templatesDir . "/" . $newName) === true)
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplateAlreadyExists");
			return;
		}

		$res = SMFileSystem::Copy($templatesDir . "/" . $this->lstTemplates->GetSelectedValue(), $templatesDir . "/" . $newName);

		if ($res === true)
		{
			$this->errorForm = $this->lang->GetTranslation("NotificationTemplateCopySucceeded");
			$this->populateTemplates($newName);
		}
		else
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplateCopyFailed");
		}
	}

	private function deleteTemplate()
	{
		if ($this->cloudMode === true)
			return; // Make sure function cannot be triggered programmatically client side

		$tpl = $this->lstTemplates->GetSelectedValue();

		if ($tpl === "")
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplateNoSelection");
			return;
		}

		if (SMTemplateInfo::GetPublicTemplate() === $tpl || SMTemplateInfo::GetAdminTemplate() === $tpl || SMTemplateInfo::GetCurrentTemplate() === $tpl || $this->checkTemplateInUse($tpl) === true)
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplateDeleteInUse");
			return;
		}

		$templatesDir = SMEnvironment::GetTemplatesDirectory();

		if (SMFileSystem::FolderIsWritable($templatesDir . "/" . $tpl) === false)
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplateDeleteNotWritable") . ": " . $templatesDir . "/" . $tpl;
			return;
		}

		$res = SMFileSystem::Delete($templatesDir . "/" . $tpl, true);

		if ($res === true)
		{
			$this->errorForm = $this->lang->GetTranslation("NotificationTemplateDeleteSucceeded");
			$this->populateTemplates();
		}
		else
		{
			$this->errorForm = $this->lang->GetTranslation("WarningTemplateDeleteFailed");
		}
	}

	private function checkTemplateInUse($tpl)
	{
		SMTypeCheck::CheckObject(__METHOD__, "tpl", $tpl, SMTypeCheckType::$String);

		$pages = SMPagesLoader::GetPages();
		$currentPage = $this->txtOriginalFilename->GetValue();

		foreach ($pages as $page)
		{
			if ($currentPage === $page->GetFilename()) // Do not check page currently being edited ($currentPage is an empty string if not in edit mode)
				continue;

			if ($page->GetTemplate() === $tpl)
				return true;
		}

		return false;
	}

	private function createPage()
	{
		$filename = $this->txtFilename->GetValue();

		if ($filename === "")
		{
			$this->errorForm = $this->lang->GetTranslation("FilenameMissing");
			return;
		}

		if (SMStringUtilities::Validate($filename, SMValueRestriction::$AlphaNumeric, array(".", "-", "_")) === false)
		{
			$this->errorForm = $this->lang->GetTranslation("FilenameInvalid");
			return;
		}

		if (SMPagesPage::GetPersistentByFilename($filename) !== null)
		{
			$this->errorForm = $this->lang->GetTranslation("FilenameExists");
			return;
		}

		$this->txtKeywords->SetValue($this->cleanUpKeywords($this->txtKeywords->GetValue()));

		$page = new SMPagesPage(SMRandom::CreateGuid(), $filename);
		$page->SetTitle($this->txtTitle->GetValue());
		$page->SetAccessible($this->chkAccessible->GetChecked());
		$page->SetTemplate($this->lstTemplates->GetSelectedValue());
		$page->SetKeywords($this->txtKeywords->GetValue());
		$page->SetDescription($this->txtDescription->GetValue());
		$page->SetAllowIndexing($this->chkAllowIndexing->GetChecked());
		$page->SetPassword($this->txtPassword->GetValue());

		$page->CommitPersistent();

		$this->createPageList();
		$this->clearForm();
	}

	private function cleanUpKeywords($keywords)
	{
		SMTypeCheck::CheckObject(__METHOD__, "keywords", $keywords, SMTypeCheckType::$String);

		// Make sure that all keywords are seperated by
		// a comma and a following space.

		$keywords = str_replace(",", " ", $keywords);

		while (strpos($keywords, "  ") !== false)
			$keywords = str_replace("  ", " ", $keywords);

		return str_replace(" ", ", ", $keywords);
	}

	private function deletePage()
	{
		if ($this->grdPages->GetSelectionMade() === false)
		{
			$this->errorList = $this->lang->GetTranslation("SelectPage");
			return;
		}

		$filename = $this->grdPages->GetSelectedValue();
		$page = (($filename !== null) ? SMPagesPage::GetPersistentByFilename($filename) : null);

		if ($page === null)
		{
			$this->errorList = $this->lang->GetTranslation("PageMissing");
			return;
		}

		$page->DeletePersistent();

		$this->createPageList();
		$this->clearForm();
	}

	private function editPageMode()
	{
		if ($this->grdPages->GetSelectionMade() === false)
		{
			$this->errorList = $this->lang->GetTranslation("SelectPage");
			return;
		}

		$filename = $this->grdPages->GetSelectedValue();
		$page = (($filename !== null) ? SMPagesPage::GetPersistentByFilename($filename) : null);

		if ($page === null)
		{
			$this->errorList = $this->lang->GetTranslation("PageMissing");
			return;
		}

		$this->txtOriginalFilename->SetValue($page->GetFilename());

		$this->txtFilename->SetValue($page->GetFilename());
		$this->txtTitle->SetValue($page->GetTitle());
		$this->chkAccessible->SetChecked($page->GetAccessible());

		$this->lstTemplates->SetSelectedValue($page->GetTemplate());

		$this->txtKeywords->SetValue($page->GetKeywords());
		$this->txtDescription->SetValue($page->GetDescription());
		$this->chkAllowIndexing->SetChecked($page->GetAllowIndexing());

		$this->txtPassword->SetValue($page->GetPassword());
	}

	private function inEditMode()
	{
		$val = $this->txtOriginalFilename->GetValue();
		return ($val !== null && $val !== "");
	}

	private function editContent()
	{
		if ($this->grdPages->GetSelectionMade() === false)
		{
			$this->errorList = $this->lang->GetTranslation("SelectPage");
			return;
		}

		$filename = $this->grdPages->GetSelectedValue();
		$page = (($filename !== null) ? SMPagesPage::GetPersistentByFilename($filename) : null);

		if ($page === null)
		{
			$this->errorList = $this->lang->GetTranslation("PageMissing");
			return;
		}

		$this->editContentGuid = $page->GetId();
	}

	private function editSystemPage($filename)
	{
		SMTypeCheck::CheckObject(__METHOD__, "filename", $filename, SMTypeCheckType::$String);

		$page = SMPagesPage::GetPersistentByFilename("#" . $filename);

		if ($page === null)
		{
			$page = new SMPagesPage(SMRandom::CreateGuid(), "#" . $filename);
			$page->SetAccessible(true); // Make sure SMPagesFrmViewer::RenderPage(..) does not render system page with a "Not Accessible" error
			$page->CommitPersistent();
		}

		$this->editContentGuid = $page->GetId();
		$this->refreshPageOnEditorClosed = true;
	}

	private function removeSystemPage($filename)
	{
		SMTypeCheck::CheckObject(__METHOD__, "filename", $filename, SMTypeCheckType::$String);

		$page = SMPagesPage::GetPersistentByFilename("#" . $filename);

		if ($page !== null)
			$page->DeletePersistent();
	}

	private function displayPage()
	{
		if ($this->grdPages->GetSelectionMade() === false)
		{
			$this->errorList = $this->lang->GetTranslation("SelectPage");
			return;
		}

		$filename = $this->grdPages->GetSelectedValue();
		$page = (($filename !== null) ? SMPagesPage::GetPersistentByFilename($filename) : null);

		if ($page === null)
		{
			$this->errorList = $this->lang->GetTranslation("PageMissing");
			return;
		}

		$this->displayContentGuid = $page->GetId();
	}

	private function updatePage()
	{
		$filename = $this->txtFilename->GetValue();

		if ($filename === "")
		{
			$this->errorForm = $this->lang->GetTranslation("FilenameMissing");
			return;
		}

		//if (preg_match("/^[a-z0-9._-]+$/i", $filename) === 0)
		if (SMStringUtilities::Validate($filename, SMValueRestriction::$AlphaNumeric, array(".", "-", "_")) === false)
		{
			$this->errorForm = $this->lang->GetTranslation("FilenameInvalid");
			return;
		}

		$filename = $this->txtOriginalFilename->GetValue();
		$page = (($filename !== null) ? SMPagesPage::GetPersistentByFilename($filename) : null);

		if ($page === null)
		{
			$this->errorForm = $this->lang->GetTranslation("PageMissing");
			return;
		}

		$this->txtKeywords->SetValue($this->cleanUpKeywords($this->txtKeywords->GetValue()));

		$page->SetFilename($this->txtFilename->GetValue());
		$page->SetTitle($this->txtTitle->GetValue());
		$page->SetAccessible($this->chkAccessible->GetChecked());
		if ($this->cloudMode === false)
			$page->SetTemplate($this->lstTemplates->GetSelectedValue());
		$page->SetKeywords($this->txtKeywords->GetValue());
		$page->SetDescription($this->txtDescription->GetValue());
		$page->SetAllowIndexing($this->chkAllowIndexing->GetChecked());
		$page->SetPassword($this->txtPassword->GetValue());

		$page->CommitPersistent();

		$this->txtOriginalFilename->SetValue($page->GetFilename());
		$this->createPageList();

		$this->grdPages->SetSelectedId(md5($page->GetFilename()));

		$this->clearForm();
	}

	private function clearForm()
	{
		$this->txtOriginalFilename->SetValue("");
		$this->txtFilename->SetValue("");
		$this->txtTitle->SetValue("");
		$this->chkAccessible->SetChecked(true);
		$this->lstTemplates->SetSelectedValue("");
		$this->txtKeywords->SetValue("");
		$this->txtDescription->SetValue("");
		$this->chkAllowIndexing->SetChecked(true);
		$this->txtPassword->SetValue("");
	}

	private function saveSettings()
	{
		SMAttributes::SetAttribute("SMPagesSettingsUrlType", $this->lstUrlTypes->GetSelectedValue());
		SMAttributes::SetAttribute("SMPagesSettingsSeoUrls", (($this->chkSeoUrls->GetChecked() === true) ? "true" : "false"));

		$this->msgSettings = $this->lang->GetTranslation("SettingsSaved");
	}

	public function Render()
	{
		$output = "";

		if ($this->editContentGuid !== "")
		{
			// Force IE11 into IE10 Document Mode since it is not fully compatible with TinyMCE 3.5.7 (e.g. HTML Source plugin is not working)
			// NOTICE: Same code found in FrmEditor.class.php in case Legacy Mode is enabled for pop ups, which spawns a new browser window.
			// TODO: Upgrade to 3.5.11 and later switch to TinyMCE 4 when we are ready for it.
			//header("X-UA-Compatible: IE=10,chrome=1");

			$output .= "
			<script type=\"text/javascript\">
			var smPagesWindowSizeCookie = SMCookie.GetCookie(\"SMPagesEditorSize\");
			var smPagesWindowSize = ((smPagesWindowSizeCookie !== null) ? smPagesWindowSizeCookie.split(\"x\") : new Array(\"950\", \"630\"));

			var smPagesWindowWidth = parseInt(smPagesWindowSize[0]);
			var smPagesWindowHeight = parseInt(smPagesWindowSize[1]);

			smPagesWindowWidth = ((smPagesWindowWidth <= SMBrowser.GetScreenWidth(true)) ? smPagesWindowWidth : SMBrowser.GetScreenWidth(true));
			smPagesWindowHeight = ((smPagesWindowHeight <= SMBrowser.GetScreenHeight(true)) ? smPagesWindowHeight : SMBrowser.GetScreenHeight(true));

			var smPagesWin = new SMWindow(\"" . $this->editContentGuid . "\");
			smPagesWin.SetUrl(\"" . SMExtensionManager::GetExtensionUrl("SMPages", SMTemplateType::$Basic) . "&SMPagesEditor&SMPagesId=" . $this->editContentGuid . "\");
			smPagesWin.SetSize(smPagesWindowWidth, smPagesWindowHeight);
			smPagesWin.SetCenterWindow(true);
			smPagesWin.SetDisplayScrollBars(false);
			smPagesWin.SetOnCloseCallback(function() { " . (($this->refreshPageOnEditorClosed === true) ? "location.href = location.href;" : "") . " });
			SMEventHandler.AddEventHandler(window, \"load\", function() { if (SMBrowser.GetBrowser() === \"MSIE\" && SMBrowser.GetVersion() < 8) { smPagesWin.Show(true); } else { smPagesWin.Show(); } }); // Use Legacy Mode for old buggy IE7, to prevent infinite loop (onresize keeps firering for iFrame)
			</script>
			";
		}

		if ($this->displayContentGuid !== "")
		{
			$output .= "
			<script type=\"text/javascript\">
			var smPagesWindowSizeCookie = SMCookie.GetCookie(\"SMPagesEditorSize\");
			var smPagesWindowSize = ((smPagesWindowSizeCookie !== null) ? smPagesWindowSizeCookie.split(\"x\") : new Array(\"950\", \"630\"));

			var smPagesWindowWidth = parseInt(smPagesWindowSize[0]);
			var smPagesWindowHeight = parseInt(smPagesWindowSize[1]);

			smPagesWindowWidth = ((smPagesWindowWidth <= SMBrowser.GetScreenWidth(true)) ? smPagesWindowWidth : SMBrowser.GetScreenWidth(true));
			smPagesWindowHeight = ((smPagesWindowHeight <= SMBrowser.GetScreenHeight(true)) ? smPagesWindowHeight : SMBrowser.GetScreenHeight(true));

			var smPagesWinDisplay = new SMWindow(\"" . $this->editContentGuid . "\");
			smPagesWinDisplay.SetUrl(\"" . SMExtensionManager::GetExtensionUrl("SMPages", SMTemplateType::$Basic) . "&SMPagesId=" . $this->displayContentGuid . "\");
			smPagesWinDisplay.SetSize(smPagesWindowWidth, smPagesWindowHeight);
			smPagesWinDisplay.SetCenterWindow(true);
			SMEventHandler.AddEventHandler(window, \"load\", function() { if (SMBrowser.GetBrowser() === \"MSIE\" && SMBrowser.GetVersion() < 8) { smPagesWinDisplay.Show(true); } else { smPagesWinDisplay.Show(); } }); // Use Legacy Mode for IE7 for consistency (required in edit mode on IE7)
			</script>
			";
		}

		$output .= $this->txtOriginalFilename->Render();

		$output .= "
		<table style=\"width: 100%\">
			<tr>
				<td style=\"vertical-align: top; width: 300px\">
					" . $this->renderForm() . "
					<br><br>
					" . $this->renderHeaderFooter() . "
					<br>
					" . $this->renderSettings() . "
				</td>
				<td style=\"width: 50px\">&nbsp;</td>
				<td style=\"vertical-align: top\">
					" . $this->renderList() . "
				</td>
			</tr>
		</table>";

		return $output;
	}

	private function renderForm()
	{
		$output = "";

		if ($this->errorForm !== "")
			$output .= SMNotify::Render($this->errorForm);

		$showAll =  ($this->inEditMode() || $this->cmdCopyTemplate->PerformedPostBack() === true || $this->cmdDeleteTemplate->PerformedPostBack() === true);

		$output .= "
		<table id=\"smPagesEditForm\">
			<tr>
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("Filename") . "</td>
				<td>" . $this->txtFilename->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("PageTitle") . "</td>
				<td>" . $this->txtTitle->Render() . "</td>
			</tr>
			<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("Accessible") . "</td>
				<td>" . $this->chkAccessible->Render() . "</td>
			</tr>
			<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
				<td style=\"width: 120px\">&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
		";

		if ($this->cloudMode === false)
		{
			$output .= "
				<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
					<td style=\"width: 120px\">" . $this->lang->GetTranslation("Template") . "</td>
					<td>" . $this->lstTemplates->Render() . "</td>
				</tr>
				<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
					<td style=\"width: 120px\">&nbsp;</td>
					<td>" . $this->txtCopyTemplateName->Render() . $this->cmdCopyTemplate->Render() . " " . $this->cmdDeleteTemplate->Render() . "</td>
				</tr>
				<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
					<td style=\"width: 120px\">&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
			";
		}

		$output .= "
			<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("Keywords") . "</td>
				<td>" . $this->txtKeywords->Render() . "</td>
			</tr>
			<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("Description") . "</td>
				<td>" . $this->txtDescription->Render() . "</td>
			</tr>
			<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("AllowIndexing") . "</td>
				<td>" . $this->chkAllowIndexing->Render() . "</td>
			</tr>
			<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
				<td style=\"width: 120px\">&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("Password") . "</td>
				<td>" . $this->txtPassword->Render() . "</td>
			</tr>
			<tr" . (($showAll === false) ? " style=\"display: none\"" : "") . ">
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("MaskPassword") . "</td>
				<td>" . $this->chkMaskPassword->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 120px\">&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 120px\">
					" . (($showAll === false) ? $this->cmdAllOptions->Render() : "") . "
				</td>
				<td style=\"text-align: right\">
					" . (($this->inEditMode() === false) ? $this->cmdCreatePage->Render() : $this->cmdClear->Render()) . "
					" . (($this->inEditMode() === true) ? $this->cmdSave->Render() : "") . "
				</td>
			</tr>
		</table>
		";

		$fieldset = new SMFieldset("SMPagesForm");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 300px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("ContentPageDetails"));
		$fieldset->SetPostBackControl((($this->inEditMode() === false) ? $this->cmdCreatePage->GetClientId() : $this->cmdSave->GetClientId()));

		$output = $fieldset->Render();

		// Disable password storage prompts (not W3C complient).
		$output .= "<script type=\"text/javascript\">SMEventHandler.AddEventHandler(window, \"load\", function() { SMDom.GetElement(\"" . $this->txtPassword->GetClientId() . "\").autocomplete = \"off\"; });</script>";

		return $output;
	}

	private function renderHeaderFooter()
	{
		$output = "
		<table>
			<tr>
				<td>" . $this->cmdEditHeader->Render() . "</td>
				<td>&nbsp;&nbsp;</td>
				<td>" . ((SMPagesPage::GetPersistentByFilename("#Header") !== null) ? $this->cmdRemoveHeader->Render() : "&nbsp;") . "</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>" . $this->cmdEditFooter->Render() . "</td>
				<td>&nbsp;&nbsp;</td>
				<td>" . ((SMPagesPage::GetPersistentByFilename("#Footer") !== null) ? $this->cmdRemoveFooter->Render() : "&nbsp;") . "</td>
			</tr>
		</table>
		";

		$fieldset = new SMFieldset("SMPagesHeaderFooter");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 300px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("HeaderFooter"));
		$fieldset->SetCollapsable(true);
		//$fieldset->SetCollapsed(true);

		return $fieldset->Render();
	}

	private function renderSettings()
	{
		$output = "";

		if ($this->msgSettings !== "")
			$output .= SMNotify::Render($this->msgSettings);

		$output .= "
		<table>
			<tr>
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("UrlType") . "</td>
				<td>" . $this->lstUrlTypes->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("SeoUrls") . "</td>
				<td>" . $this->chkSeoUrls->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 120px\">&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 120px\">&nbsp;</td>
				<td style=\"text-align: right\">" . $this->cmdSaveSettings->Render() . "</td>
			</tr>
		</table>
		";

		$fieldset = new SMFieldset("SMPagesSettings");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 300px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Settings"));
		$fieldset->SetCollapsable(true);
		$fieldset->SetCollapsed(true);

		return $fieldset->Render();
	}

	private function renderList()
	{
		$output = "";

		if ($this->errorList !== "")
			$output .= SMNotify::Render($this->errorList);

		$output .= "
		" . $this->grdPages->Render() . "
		<br>
		<span style=\"float: right\">
			" . $this->cmdEdit->Render() . "
			" . $this->cmdEditContent->Render() . "
			" . $this->cmdDisplay->Render() . "
			" . $this->cmdDelete->Render() . "
		</span>
		<div style=\"clear: both\"></div>
		";

		$fieldset = new SMFieldset("SMPagesList");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("ContentPages"));

		return $fieldset->Render();
	}
}

?>
