<?php

class SMCookieConsentFrmConfig implements SMIExtensionForm
{
	// DISCLAIMER:

	// Sitemagic CMS always creates a so-called Session Cookie which is required for the web application to perform
	// certain optimizations (e.g. make sure the same log message is not recorded multiple times), remember the user
	// currently logged in, remember what language the user selected during log-in, handle template overrides via the
	// URL, provide Cross-Site Request Forgery Protection using a CSRF Token, remember what password protected pages
	// the user was grated access to after providing the correct password, and so forth.
	// Search for SetSession and $_SESSION to reveal session usage.

	// Furthermore both "strictly necessary cookies" and "Preference cookie" (or "functional cookies") may be created.
	// These can be used to remember the size of the page editor, remember which fieldsets were collapsed, remember what
	// nodes in a treeview were collapsed, remember what products were added to the shopping basket, make sure the user
	// cannot rate the same item multiple times, remember what tips and tricks have been dismissed (hidden), remember
	// what cookies have been approved, and even remember if all cookies have been denied.
	// As of 2020-07-05 only "strictly necessary cookies" are used on the part of the website accessible to visitors.
	// Several "functional cookies" are used in the control panel.
	// Search for SetCookie and Cookie.*\.Set\( (enable RegEx search) to reveal cookie usage.

	// Sitemagic CMS does not track users for profiling unless an extension clearly states that this is the purpose.
	// An example of this could be a guest counter.

	// More on the cookie law here: https://gdpr.eu/cookies/

	private $context;
	private $name;
	private $lang;
	private $message;
	private $cookieContentHelper;

	private $lstDialogPage;
	private $lstPosition;
	private $lstConsentDuration;
	private $txtDenyText;
	private $txtAcceptText;
	private $cmdDialogSave;

	private $lstModules;
	private $txtModuleName;
	private $txtModuleDescription;
	private $txtModuleCode;
	private $cmdCreate;
	private $cmdClear;
	private $cmdSave;
	private $cmdDelete;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->name = $this->context->GetExtensionName();
		$this->lang = new SMLanguageHandler($this->name);
		$this->message = null;
		$this->cookieContentHelper = new SMCookieConsentHelper();

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->loadFormData();
		$this->handlePostBack();
	}

	private function createControls()
	{
		// Settings

		$this->lstDialogPage = new SMOptionList($this->name . "PageList");
		$this->lstDialogPage->SetAttribute(SMInputAttributeText::$Style, "width: 100%;");

		$this->lstPosition = new SMOptionList($this->name . "PositionList");
		$this->lstPosition->SetAttribute(SMInputAttributeText::$Style, "width: 100%;");

		$this->lstConsentDuration = new SMOptionList($this->name . "ConsentDuration");
		$this->lstConsentDuration->SetAttribute(SMInputAttributeText::$Style, "width: 100%;");

		$this->txtDenyText = new SMInput($this->name . "DenyText", SMInputType::$Text);
		$this->txtDenyText->SetAttribute(SMInputAttributeText::$MaxLength, "100");
		$this->txtDenyText->SetAttribute(SMInputAttributeText::$Style, "width: 100%;");

		$this->txtAcceptText = new SMInput($this->name . "AcceptText", SMInputType::$Text);
		$this->txtAcceptText->SetAttribute(SMInputAttributeText::$MaxLength, "100");
		$this->txtAcceptText->SetAttribute(SMInputAttributeText::$Style, "width: 100%;");

		$this->cmdDialogSave = new SMLinkButton($this->name . "SaveSettings");
		$this->cmdDialogSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
		$this->cmdDialogSave->SetTitle($this->lang->GetTranslation("Save"));

		// Modules

		$this->lstModules = new SMOptionList($this->name . "ModuleList");
		$this->lstModules->SetAutoPostBack(true);
		$this->lstModules->SetAttribute(SMInputAttributeText::$Style, "width: 100%;");

		$this->txtModuleName = new SMInput($this->name . "ModuleName", SMInputType::$Text);
		$this->txtModuleName->SetAttribute(SMInputAttributeText::$MaxLength, "100");
		$this->txtModuleName->SetAttribute(SMInputAttributeText::$Style, "width: 100%;");

		$this->txtModuleDescription = new SMInput($this->name . "ModuleDescription", SMInputType::$Text);
		$this->txtModuleDescription->SetAttribute(SMInputAttributeText::$MaxLength, "250");
		$this->txtModuleDescription->SetAttribute(SMInputAttributeText::$Style, "width: 100%;");

		$this->txtModuleCode = new SMInput($this->name . "ModuleCode", SMInputType::$Textarea);
		$this->txtModuleCode->SetAttribute(SMInputAttributeText::$Style, "width: 100%; height: 200px;");

		$this->cmdCreate = new SMLinkButton($this->name . "CreateModule");
		$this->cmdCreate->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));
		$this->cmdCreate->SetTitle($this->lang->GetTranslation("Create"));

		$this->cmdSave = new SMLinkButton($this->name . "SaveModule");
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
		$this->cmdSave->SetTitle($this->lang->GetTranslation("Save"));
		$this->cmdSave->SetOnClick("smCookieConsentCodeCleanup()");

		$this->cmdClear = new SMLinkButton($this->name . "ClearModule");
		$this->cmdClear->SetIcon(SMImageProvider::GetImage(SMImageType::$Clear));
		$this->cmdClear->SetTitle($this->lang->GetTranslation("Clear"));

		$this->cmdDelete = new SMLinkButton($this->name . "DeleteModule");
		$this->cmdDelete->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDelete->SetTitle($this->lang->GetTranslation("Delete"));
		$this->cmdDelete->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteModule") . "') === false) { return; }");
	}

	private function loadFormData()
	{
		// Settings data

		$this->lstDialogPage->SetOptions(array());
		$this->lstDialogPage->AddOption(new SMOptionListItem("", "", ""));
		foreach ($this->cookieContentHelper->GetDialogPages() as $pageId => $pageName)
		{
			$this->lstDialogPage->AddOption(new SMOptionListItem($pageId, $pageName, $pageId));
		}

		$this->lstPosition->SetOptions(array());
		$this->lstPosition->AddOption(new SMOptionListItem($this->name . "PositionDisabled", $this->lang->GetTranslation("Disabled"), "disabled"));
		$this->lstPosition->AddOption(new SMOptionListItem($this->name . "PositionTop", $this->lang->GetTranslation("Top"), "top"));
		$this->lstPosition->AddOption(new SMOptionListItem($this->name . "PositionBottom", $this->lang->GetTranslation("Bottom"), "bottom"));

		$this->lstConsentDuration->SetOptions(array());
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration1Hour", "1 " . $this->lang->GetTranslation("Hour"), "1"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration2Hours", "2 " . $this->lang->GetTranslation("Hours"), "2"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration3Hours", "3 " . $this->lang->GetTranslation("Hours"), "3"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration5Hours", "5 " . $this->lang->GetTranslation("Hours"), "5"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration10Hours", "10 " . $this->lang->GetTranslation("Hours"), "10"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration24Hours", "1 " . $this->lang->GetTranslation("Day"), "24"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration48Hours", "2 " . $this->lang->GetTranslation("Days"), "48"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration72Hours", "3 " . $this->lang->GetTranslation("Days"), "72"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration120Hours", "5 " . $this->lang->GetTranslation("Days"), "120"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration240Hours", "10 " . $this->lang->GetTranslation("Days"), "240"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration720Hours", "30 " . $this->lang->GetTranslation("Days"), "720"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration2160Hours", "90 " . $this->lang->GetTranslation("Days"), "2160"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration4320Hours", "180 " . $this->lang->GetTranslation("Days"), "4320"));
		$this->lstConsentDuration->AddOption(new SMOptionListItem($this->name . "ConsentDuration8760Hours", "365 " . $this->lang->GetTranslation("Days"), "8760"));

		if ($this->context->GetForm()->PostBack() === false) // Only load saved values on initial request
		{
			$this->lstDialogPage->SetSelectedValue($this->cookieContentHelper->GetDialogPage());
			$this->lstPosition->SetSelectedValue($this->cookieContentHelper->GetDialogPosition());
			$this->lstConsentDuration->SetSelectedValue((string)$this->cookieContentHelper->GetConsentDuration());
			$this->txtDenyText->SetValue($this->cookieContentHelper->GetDenyText());
			$this->txtAcceptText->SetValue($this->cookieContentHelper->GetAcceptText());
		}

		// Modules data

		$this->lstModules->SetOptions(array());
		$this->lstModules->AddOption(new SMOptionListItem("", "", ""));
		foreach ($this->cookieContentHelper->GetModules() as $module)
		{
			$this->lstModules->AddOption(new SMOptionListItem($this->name . "Module" . $module, $module, $module));
		}

		// Load module data if a module is selected in module picker

		if ($this->lstModules->PerformedPostBack() === true)
		{
			$moduleName = $this->lstModules->GetSelectedValue(); // Returns null if selected module is no longer available, which will be the case if module was removed in another window/tab/session

			if ($moduleName !== null && $moduleName !== "")
			{
				$moduleDescription = $this->cookieContentHelper->GetModuleDescription($moduleName);
				$moduleCode = $this->cookieContentHelper->GetModuleCode($moduleName);

				$this->txtModuleName->SetValue($moduleName);
				$this->txtModuleDescription->SetValue($moduleDescription);
				$this->txtModuleCode->SetValue($moduleCode);

			}
			else
			{
				$this->txtModuleName->SetValue("");
				$this->txtModuleDescription->SetValue("");
				$this->txtModuleCode->SetValue("");
			}
		}
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdDialogSave->PerformedPostBack() === true)
			{
				// Save dialog settings

				$this->cookieContentHelper->SetDialogPage($this->lstDialogPage->GetSelectedValue());
				$this->cookieContentHelper->SetDialogPosition($this->lstPosition->GetSelectedValue());
				$this->cookieContentHelper->SetConsentDuration((int)$this->lstConsentDuration->GetSelectedValue());
				$this->cookieContentHelper->SetDenyText($this->txtDenyText->GetValue());
				$this->cookieContentHelper->SetAcceptText($this->txtAcceptText->GetValue());

				SMEnvironment::DestroyCookieValue("SMCookieConsentAllowed");

				$this->message = $this->lang->GetTranslation("ChangesSaved");
			}
			else if ($this->cmdCreate->PerformedPostBack() === true)
			{
				// Create module

				if ($this->txtModuleName->GetValue() === "")
				{
					$this->message = $this->lang->GetTranslation("FillOutModuleName");
					return;
				}

				if ($this->cookieContentHelper->GetModuleCode($this->txtModuleName->GetValue()) !== null)
				{
					$this->message = $this->lang->GetTranslation("ModuleAlreadyExists");
					return;
				}

				$this->cookieContentHelper->AddModule($this->txtModuleName->GetValue(), $this->txtModuleDescription->GetValue(), $this->txtModuleCode->GetValue());

				$this->loadFormData();

				$this->txtModuleName->SetValue("");
				$this->txtModuleDescription->SetValue("");
				$this->txtModuleCode->SetValue("");

				SMEnvironment::DestroyCookieValue("SMCookieConsentAllowed");

				$this->message = $this->lang->GetTranslation("ModuleCreated");
			}
			else if ($this->cmdSave->PerformedPostBack() === true)
			{
				// Update module

				if ($this->txtModuleName->GetValue() === "")
				{
					$this->message = $this->lang->GetTranslation("FillOutModuleName");
					return;
				}

				if ($this->lstModules->GetSelectedValue() !== $this->txtModuleName->GetValue() && $this->cookieContentHelper->GetModuleCode($this->txtModuleName->GetValue()) !== null)
				{
					$this->message = $this->lang->GetTranslation("ModuleAlreadyExists");
					return;
				}

				$this->cookieContentHelper->SetModule($this->lstModules->GetSelectedValue(), $this->txtModuleName->GetValue(), $this->txtModuleDescription->GetValue(), $this->txtModuleCode->GetValue());

				$this->loadFormData();

				$this->lstModules->SetSelectedValue("");
				$this->txtModuleName->SetValue("");
				$this->txtModuleDescription->SetValue("");
				$this->txtModuleCode->SetValue("");

				SMEnvironment::DestroyCookieValue("SMCookieConsentAllowed");

				$this->message = $this->lang->GetTranslation("ChangesSaved");
			}
			else if ($this->cmdClear->PerformedPostBack() === true)
			{
				// Clear module form

				$this->lstModules->SetSelectedValue("");
				$this->txtModuleName->SetValue("");
				$this->txtModuleDescription->SetValue("");
				$this->txtModuleCode->SetValue("");
			}
			else if ($this->cmdDelete->PerformedPostBack() === true)
			{
				// Delete module

				$this->cookieContentHelper->DeleteModule($this->lstModules->GetSelectedValue());

				$this->loadFormData();

				$this->lstModules->SetSelectedValue("");
				$this->txtModuleName->SetValue("");
				$this->txtModuleDescription->SetValue("");
				$this->txtModuleCode->SetValue("");

				SMEnvironment::DestroyCookieValue("SMCookieConsentAllowed");

				$this->message = $this->lang->GetTranslation("ModuleRemoved");
			}
		}
	}

	public function Render()
	{
		// Settings

		$outputSettings = "";

		$outputSettings .= $this->lang->GetTranslation("DialogContent") . "<br>";
		$outputSettings .= $this->lstDialogPage->Render();

		$outputSettings .= "<br><br>";
		$outputSettings .= $this->lang->GetTranslation("DialogPosition") . "<br>";
		$outputSettings .= $this->lstPosition->Render();

		$outputSettings .= "<br><br>";
		$outputSettings .= $this->lang->GetTranslation("ConsentDuration") . "<br>";
		$outputSettings .= $this->lstConsentDuration->Render();

		$outputSettings .= "<br><br>";
		$outputSettings .= $this->lang->GetTranslation("DenyButtonText") . "<br>";
		$outputSettings .= $this->txtDenyText->Render();

		$outputSettings .= "<br><br>";
		$outputSettings .= $this->lang->GetTranslation("AcceptButtonText") . "<br>";
		$outputSettings .= $this->txtAcceptText->Render();

		$outputSettings .= "<br><br>";
		$outputSettings .= $this->cmdDialogSave->Render();

		// Modules

		$editing = $this->lstModules->GetSelectedValue() !== null && $this->lstModules->GetSelectedValue() !== ""; // Null if module has been removed in another window/tab/session, empty string if empty entry has been selected

		$outputModules = "";

		$outputModules .= $this->lang->GetTranslation("Modules") . "<br>";
		$outputModules .= $this->lstModules->Render();

		$outputModules .= "<br><br>";
		$outputModules .= $this->lang->GetTranslation("ModuleName") . "<br>";
		$outputModules .= $this->txtModuleName->Render();

		$outputModules .= "<br><br>";
		$outputModules .= $this->lang->GetTranslation("ModuleDescription") . "<br>";
		$outputModules .= $this->txtModuleDescription->Render();

		$outputModules .= "<br><br>";
		$outputModules .= $this->lang->GetTranslation("ModuleCode") . " (JavaScript)<br>";
		$outputModules .= $this->txtModuleCode->Render();

		$outputModules .= "<br><br>";
		$outputModules .= $editing === true ? $this->cmdSave->Render() . " " . $this->cmdClear->Render() . " " . $this->cmdDelete->Render() : $this->cmdCreate->Render();

		// Structure

		$output = "";

		if ($this->message !== null)
			$output .= SMNotify::Render($this->message);

		$output .= "
		<br>
		<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"width: 100%;\">
			<tr>
				<td style=\"vertical-align: top; width: 300px;\">" . $outputSettings . "</td>
				<td style=\"width: 100px;\">&nbsp;</td>
				<td style=\"vertical-align: top; width: 400px;\">" . $outputModules . "</td>
			</tr>
		</table>

		<script>
		function smCookieConsentCodeCleanup()
		{
			// Remove <script> and <noscript> tags - content of <script> blocks are wrapped in self-executing functions to retain scope

			var input = document.getElementById(\"" . $this->txtModuleCode->GetClientId() . "\");
			input.value = input.value.replace(/<script.*>([\s\S]*?)<\/script>/gm, \"(function(){\$1})();\").replace(/<noscript.*>[\s\S]*?<\/noscript>/gm, \"\");
		}
		</script>
		";

		$fieldset = new SMFieldset($this->name . "Fieldset");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 800px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Title"));

		return $fieldset->Render();
	}
}

?>
