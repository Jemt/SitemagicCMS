<?php

class SMConfigFrmConfig implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $msg;
	private $defaultExtensions;
	private $cloudMode;

	private $txtUsername;
	private $txtPassword;
	private $chkMaskPassword;

	private $lstLanguages;
	private $lstTimeZones;
	private $lstImageThemes;
	private $lstTemplates;
	private $chkInstallWebsiteData;

	private $txtCopyTemplateName;
	private $cmdCopyTemplate;
	private $cmdDeleteTemplate;

	//private $txtLicense; // NOTICE: License validation disabled for the 2012-04-01 edition by the Sitemagic team.

	private $txtDbServer;
	private $txtDbDatabase;
	private $txtDbUsername;
	private $txtDbPassword;
	private $chkDbMaskPassword;

	private $txtSmtpHost;
	private $txtSmtpPort;
	private $txtSmtpUser;
	private $txtSmtpPass;
	private $chkSmtpMaskPassword;
	private $lstSmtpAuthType;
	private $lstSmtpEncryption;
	private $txtSmtpSender;

	private $chkLstExtensions;

	// SUBSITES - BETA SECTION - START
	private $arrSubSites;
	private $cmdNewSubSite;
	// SUBSITES - BETA SECTION - END

	private $cmdSave;
	private $cmdCancel;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMConfig");
		$this->msg = "";
		$this->defaultExtensions = array("SMAnnouncements", "SMAutoSeoUrls", "SMConfig", "SMExtensionCommon", "SMFiles", "SMLogin", "SMMenu", "SMPages");
		$this->cloudMode = SMEnvironment::GetCloudEnabled();

		if ($this->cloudMode === true)
			$this->defaultExtensions[] = "SMCloudApp";

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtUsername = new SMInput("SMConfigUsername", SMInputType::$Text);
		$this->txtUsername->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtUsername->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtPassword = new SMInput("SMConfigPassword", SMInputType::$Password);
		$this->txtPassword->SetValue((($this->context->GetForm()->PostBack() === true) ? $this->txtPassword->GetValue() : "")); // Disable security feature: Prevent password field from being cleared after postback
		$this->txtPassword->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtPassword->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->chkMaskPassword = new SMInput("SMConfigMaskPassword", SMInputType::$Checkbox);
		$this->chkMaskPassword->SetAttribute(SMInputAttributeCheckbox::$OnClick, "smConfigChangeInputType(SMDom.GetElement('" . $this->txtPassword->GetClientId() . "'), ((this.checked === true) ? 'password' : 'text'))");
		$this->chkMaskPassword->SetChecked(true);

		$this->lstLanguages = new SMOptionList("SMConfigLanguages");
		$this->lstLanguages->SetAttribute(SMOptionListAttribute::$Style, "width: 150px");
		$languages = SMLanguageHandler::GetLanguages();
		foreach ($languages as $language)
			$this->lstLanguages->AddOption(new SMOptionListItem("SMConfigLanguage" . $language, $language, $language));

		$this->lstTimeZones = new SMOptionList("SMConfigTimeZones");
		$this->lstTimeZones->SetAttribute(SMOptionListAttribute::$Style, "width: 150px");
		$this->lstTimeZones->AddOption(new SMOptionListItem("SMConfigTimeZoneEmpty", "", ""));
		$timeZones = DateTimeZone::listIdentifiers();
		foreach ($timeZones as $ts)
			$this->lstTimeZones->AddOption(new SMOptionListItem("SMConfigTimeZone" . $ts, $ts, $ts));

		$this->lstImageThemes = new SMOptionList("SMConfigImageThemes");
		$this->lstImageThemes->SetAttribute(SMOptionListAttribute::$Style, "width: 150px");
		$imageThemes = SMImageProvider::GetImageThemes();
		foreach ($imageThemes as $imageTheme)
			$this->lstImageThemes->AddOption(new SMOptionListItem("SMConfigImageTheme" . $imageTheme, $imageTheme, $imageTheme));

		$this->lstTemplates = new SMOptionList("SMConfigTemplates");
		$this->lstTemplates->SetAttribute(SMOptionListAttribute::$Style, "width: 150px");
		if ($this->cloudMode === false)
			$this->lstTemplates->SetAttribute(SMOptionListAttribute::$OnChange, "smConfigTemplateChangeHandler(this);");
		$this->populateTemplates();

		$this->chkInstallWebsiteData = new SMInput("SMConfigInstallWebsiteData", SMInputType::$Checkbox);
		$this->chkInstallWebsiteData->SetAttribute(SMInputAttributeCheckbox::$OnChange, "smConfigState.PreferInstallDemoData = this.checked;");

		$this->txtCopyTemplateName = new SMInput("SMConfigCopyTemplateName", SMInputType::$Hidden);

		$this->cmdCopyTemplate = new SMLinkButton("SMConfigCopyTemplate");
		$this->cmdCopyTemplate->SetTitle($this->lang->GetTranslation("CopyTemplate"));
		$this->cmdCopyTemplate->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));
		$this->cmdCopyTemplate->SetOnClick("var res = SMMessageDialog.ShowInputDialog('" . $this->lang->GetTranslation("CopyTemplateDialog", true) . "', ''); if (res !== null) { document.getElementById('" . $this->txtCopyTemplateName->GetClientId() . "').value = res; } else { return false; }");

		$this->cmdDeleteTemplate = new SMLinkButton("SMConfigDeleteTemplate");
		$this->cmdDeleteTemplate->SetTitle($this->lang->GetTranslation("DeleteTemplate"));
		$this->cmdDeleteTemplate->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDeleteTemplate->SetOnClick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteTemplateDialog", true) . "') === false) { return false; }");

		/*$this->txtLicense = new SMInput("SMConfigLicense", SMInputType::$Text);
		$this->txtLicense->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtLicense->SetAttribute(SMInputAttributeText::$MaxLength, "1000");*/

		$this->txtDbServer = new SMInput("SMConfigDbServer", SMInputType::$Text);
		$this->txtDbServer->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtDbServer->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtDbDatabase = new SMInput("SMConfigDbDatabase", SMInputType::$Text);
		$this->txtDbDatabase->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtDbDatabase->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtDbUsername = new SMInput("SMConfigDbUsername", SMInputType::$Text);
		$this->txtDbUsername->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtDbUsername->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtDbPassword = new SMInput("SMConfigDbPassword", SMInputType::$Password);
		$this->txtDbPassword->SetValue((($this->context->GetForm()->PostBack() === true) ? $this->txtDbPassword->GetValue() : "")); // Disable security feature: Prevent password field from being cleared after postback
		$this->txtDbPassword->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtDbPassword->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->chkDbMaskPassword = new SMInput("SMConfigDbMaskPassword", SMInputType::$Checkbox);
		$this->chkDbMaskPassword->SetAttribute(SMInputAttributeCheckbox::$OnClick, "smConfigChangeInputType(SMDom.GetElement('" . $this->txtDbPassword->GetClientId() . "'), ((this.checked === true) ? 'password' : 'text'))");
		$this->chkDbMaskPassword->SetChecked(true);

		$this->txtSmtpHost = new SMInput("SMConfigSmtpHost", SMInputType::$Text);
		$this->txtSmtpHost->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtSmtpHost->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtSmtpPort = new SMInput("SMConfigSmtpPort", SMInputType::$Text);
		$this->txtSmtpPort->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtSmtpPort->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtSmtpUser = new SMInput("SMConfigSmtpUser", SMInputType::$Text);
		$this->txtSmtpUser->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtSmtpUser->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtSmtpPass = new SMInput("SMConfigSmtpPass", SMInputType::$Password);
		$this->txtSmtpPass->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtSmtpPass->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->chkSmtpMaskPassword = new SMInput("SMConfigSmtpMaskPassword", SMInputType::$Checkbox);
		$this->chkSmtpMaskPassword->SetAttribute(SMInputAttributeCheckbox::$OnClick, "smConfigChangeInputType(SMDom.GetElement('" . $this->txtSmtpPass->GetClientId() . "'), ((this.checked === true) ? 'password' : 'text'))");
		$this->chkSmtpMaskPassword->SetChecked(true);

		$this->lstSmtpAuthType = new SMOptionList("SMConfigSmtpAuthType");
		$this->lstSmtpAuthType->SetAttribute(SMOptionListAttribute::$Style, "width: 150px");
		$this->lstSmtpAuthType->AddOption(new SMOptionListItem("SMConfigSmtpAuthTypeEmpty", "", ""));
		$this->lstSmtpAuthType->AddOption(new SMOptionListItem("SMConfigSmtpAuthTypeLogin", "LOGIN", "LOGIN"));
		$this->lstSmtpAuthType->AddOption(new SMOptionListItem("SMConfigSmtpAuthTypePlain", "PLAIN", "PLAIN"));
		$this->lstSmtpAuthType->AddOption(new SMOptionListItem("SMConfigSmtpAuthTypeNtlm", "NTLM", "NTLM"));
		$this->lstSmtpAuthType->AddOption(new SMOptionListItem("SMConfigSmtpAuthTypeCramMd5", "CRAM-MD5", "CRAM-MD5"));

		$this->lstSmtpEncryption = new SMOptionList("SMConfigSmtpEncryption");
		$this->lstSmtpEncryption->SetAttribute(SMOptionListAttribute::$Style, "width: 150px");
		$this->lstSmtpEncryption->AddOption(new SMOptionListItem("SMConfigSmtpEncryptionEmpty", "", ""));
		$this->lstSmtpEncryption->AddOption(new SMOptionListItem("SMConfigSmtpEncryptionTls", "TLS", "TLS"));
		$this->lstSmtpEncryption->AddOption(new SMOptionListItem("SMConfigSmtpEncryptionSsl", "SSL", "SSL"));

		$this->txtSmtpSender = new SMInput("SMConfigSmtpSender", SMInputType::$Text);
		$this->txtSmtpSender->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtSmtpSender->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->chkLstExtensions = new SMCheckboxList("SMConfigExtensions");
		$this->chkLstExtensions->SetLabelStyle("font-weight: bold");
		$this->chkLstExtensions->SetDescriptionStyle("margin-bottom: 10px");
		$extensions = SMExtensionManager::GetExtensions(true);
		$md = null;
		foreach ($extensions as $extension)
		{
			if (in_array($extension, $this->defaultExtensions, true) === false)
			{
				$md = SMExtensionManager::GetMetaData($extension);
				$this->chkLstExtensions->AddItem(new SMCheckboxListItem("SMConfig" . $extension, $extension, $md["Title"], $md["Description"]));
			}
		}


		// SUBSITES - BETA SECTION - START
		// REQUIRES REVIEW/IMPROVEMENTS!

		$this->populateSubSites();

		$this->cmdNewSubSite = new SMLinkButton("SMConfigNewSubSite");
		$this->cmdNewSubSite->SetTitle("New subsite");
		$this->cmdNewSubSite->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));
		$this->cmdNewSubSite->SetOnClick("var w = new SMWindow('SubsiteDialog'); w.SetUrl('" . SMExtensionManager::GetExtensionUrl($this->context->GetExtensionName(), SMTemplateType::$Basic, SMExecutionMode::$Dedicated) . "&SMConfigSubsiteForm'); w.SetSize(500, 500); w.Show(); return;");

		// SUBSITES - BETA SECTION - END


		$this->cmdSave = new SMLinkButton("SMConfigSave");
		$this->cmdSave->SetTitle($this->lang->GetTranslation("Save"));
		if ($this->cloudMode === false)
			$this->cmdSave->SetOnClick("if (smConfigValidateBeforeSave() === false) { return; }");
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));

		$this->cmdCancel = new SMLinkButton("SMConfigCancel");
		$this->cmdCancel->SetTitle($this->lang->GetTranslation("Cancel"));
		$this->cmdCancel->SetIcon(SMImageProvider::GetImage(SMImageType::$Clear));

		if ($this->cmdSave->PerformedPostBack() === false && $this->cmdCopyTemplate->PerformedPostBack() === false && $this->cmdDeleteTemplate->PerformedPostBack() === false)
			$this->loadConfiguration();
	}

	private function populateTemplates($restoreSelectionFromConfig = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "restoreSelectionFromConfig", $restoreSelectionFromConfig, SMTypeCheckType::$Boolean);

		$this->lstTemplates->SetOptions(array());

		$templates = SMTemplateInfo::GetTemplates();

		$tplTitle = null;
		$mdFile = null;
		$cfg = null;

		foreach ($templates as $template)
		{
			$tplTitle = $template;

			if (strpos($template, "SM") === 0) // Built-in templates start with "SM" - we cannot expect metadata.xml to contain an updated title for user-defined templates
			{
				$mdFile = SMEnvironment::GetTemplatesDirectory() . "/" . $template . "/metadata.xml";

				if (SMFileSystem::FileExists($mdFile) === true)
				{
					$cfg = new SMConfiguration($mdFile);
					$tplTitle = $cfg->GetEntry("Title");
				}
				else
				{
					$tplTitle = substr($template, 2);
				}
			}

			$this->lstTemplates->AddOption(new SMOptionListItem("SMConfigTemplate" . $template, $tplTitle, $template));
		}

		if ($restoreSelectionFromConfig === true)
		{
			$conf = SMEnvironment::GetConfiguration();
			$this->lstTemplates->SetSelectedValue((($conf->GetEntry("TemplatePublic") !== null) ? $conf->GetEntry("TemplatePublic") : "Default"));
		}
	}

	private function getTemplatesJson()
	{
		$templates = SMTemplateInfo::GetTemplates();
		$json = "";

		foreach ($templates as $template)
		{
			$json .= (($json !== "") ? ", " : "");
			$json .= "\"" . $template . "\": { HasDemoData: " . (($this->hasDemoData($template) === true) ? "true" : "false") . " }";
		}

		return "{" . $json . "}";
	}


	// SUBSITES - BETA SECTION - START
	// REQUIRES REVIEW/IMPROVEMENTS!

	private function populateSubSites()
	{
		$this->arrSubSites = array();
		if (SMEnvironment::IsSubSite() === false) // Only show subsite administration on main site
		{
			foreach (SMEnvironment::GetSubSites() as $ss)
			{
				$this->arrSubSites[] = array(
					"Name"			=> $ss,
					"EditButton"	=> new SMLinkButton("SMConfigSubSiteEdit" . $ss),
					"DeleteButton"	=> new SMLinkButton("SMConfigSubSiteDelete" . $ss)
				);

				$this->arrSubSites[count($this->arrSubSites) - 1]["EditButton"]->SetIcon(SMImageProvider::GetImage(SMImageType::$Modify));
				$this->arrSubSites[count($this->arrSubSites) - 1]["EditButton"]->SetOnClick("alert('This function is not available yet'); return;");
				$this->arrSubSites[count($this->arrSubSites) - 1]["DeleteButton"]->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
				$this->arrSubSites[count($this->arrSubSites) - 1]["DeleteButton"]->SetOnClick("if (confirm('Delete subsite?') === false) return;");
			}
		}
	}

	// SUBSITES - BETA SECTION - END


	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdSave->PerformedPostBack() === true)
			{
				$this->saveConfiguration();
			}
			else if ($this->cmdCopyTemplate->PerformedPostBack() === true)
			{
				$this->copyTemplate();
			}
			else if ($this->cmdDeleteTemplate->PerformedPostBack() === true)
			{
				$this->deleteTemplate();
			}
			// SUBSITES - BETA SECTION - START
			// REQUIRES REVIEW/IMPROVEMENTS!
			else // Check subsite buttons
			{
				foreach ($this->arrSubSites as $ss)
				{
					if ($ss["DeleteButton"]->PerformedPostBack() === true)
					{
						$this->deleteSubSite($ss["Name"]);
						break;
					}
				}
			}
			// SUBSITES - BETA SECTION - END
		}
	}

	public function Render()
	{
		$output = "";

		if (SMEnvironment::GetQueryValue("SMConfigSaved") !== null && $this->cmdCancel->PerformedPostBack() === false && $this->cmdCopyTemplate->PerformedPostBack() === false && $this->cmdDeleteTemplate->PerformedPostBack() === false)
			$output .= SMNotify::Render($this->lang->GetTranslation("SettingsSaved"));

		if ($this->msg !== "")
			$output .= SMNotify::Render($this->msg);

		$tableLogin = "
		<table>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Username") . "</td>
				<td>" . $this->txtUsername->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Password") . "</td>
				<td>" . $this->txtPassword->Render()
				. "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("HidePassword") . "</td>
				<td>" . $this->chkMaskPassword->Render() . "</td>
			</tr>
		</table>
		";

		$tableAppearance = "
		<table>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("ImageTheme") . "</td>
				<td>" . $this->lstImageThemes->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Template") . "</td>
				<td>" . $this->lstTemplates->Render() . "</td>
			</tr>
		";

		if ($this->cloudMode === false)
		{
			$tableAppearance .= "
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("InstallWebsiteData") . "</td>
				<td>" . $this->chkInstallWebsiteData->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">&nbsp;</td>
				<td>" . $this->txtCopyTemplateName->Render() . $this->cmdCopyTemplate->Render() . " " . $this->cmdDeleteTemplate->Render() . "</td>
			</tr>";
		}

		$tableAppearance .= "
		</table>
		";

		$tableLanguage = "
		<table>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Language") . "</td>
				<td>" . $this->lstLanguages->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("TimeZone") . "</td>
				<td>" . $this->lstTimeZones->Render() . "</td>
			</tr>
		</table>
		";

		/*$tableLicense = "
		<table>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("LicenseKey") . "</td>
				<td>" . $this->txtLicense->Render() . "</td>
			</tr>
		</table>
		";*/

		$tableDatabase = "
		<table>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Server") . "</td>
				<td>" . $this->txtDbServer->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Database") . "</td>
				<td>" . $this->txtDbDatabase->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Username") . "</td>
				<td>" . $this->txtDbUsername->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Password") . "</td>
				<td>" . $this->txtDbPassword->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("HidePassword") . "</td>
				<td>" . $this->chkDbMaskPassword->Render() . "</td>
			</tr>
		</table>
		";

		$tableSmtp = "
		<table>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Server") . "</td>
				<td>" . $this->txtSmtpHost->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Port") . "</td>
				<td>" . $this->txtSmtpPort->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Username") . "</td>
				<td>" . $this->txtSmtpUser->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Password") . "</td>
				<td>" . $this->txtSmtpPass->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("HidePassword") . "</td>
				<td>" . $this->chkSmtpMaskPassword->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("AuthType") . "</td>
				<td>" . $this->lstSmtpAuthType->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Encryption") . "</td>
				<td>" . $this->lstSmtpEncryption->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("Sender") . "</td>
				<td>" . $this->txtSmtpSender->Render() . "</td>
			</tr>
		</table>
		";


		// SUBSITES - BETA SECTION - START
		// REQUIRES REVIEW/IMPROVEMENTS!

		$tableSubSites = "";
		if (SMEnvironment::IsSubSite() === false) // Only show subsite administration on main site
		{
			$tableSubSites .= "<span style=\"float: right;\">" . $this->cmdNewSubSite->Render() . "</span>";

			if (count($this->arrSubSites) > 0)
			{
				$tableSubSites .= "<br><br>";
				$tableSubSites .= "<table>";

				foreach ($this->arrSubSites as $ss)
				{
					// TODO: Extension should NOT know anothing about the sites/xyz path (see link created below) !!!
					$tableSubSites .= "
					<tr>
						<td style=\"width: 220px\"><a href=\"sites/" . $ss["Name"] . "\">sites/" . $ss["Name"] . "</a></td>
						<td style=\"width: 80px\"><span style=\"float: right;\">" . $ss["EditButton"]->Render() . " " . $ss["DeleteButton"]->Render() . "</span></td>
					</tr>
					";
				}

				$tableSubSites .= "</table>";
			}
		}

		// SUBSITES - BETA SECTION - END


		$output .= "
		<table>
			<tr>
				<td style=\"width: 350px; vertical-align: top;\">
					" . $this->renderFieldset("Login", $this->lang->GetTranslation("Login"), $tableLogin) . "
					<br><br>
					" . $this->renderFieldset("Extensions", $this->lang->GetTranslation("ExtensionsEnabled"), $this->chkLstExtensions->Render()) . "
				</td>
				<td style=\"width: 75px\">&nbsp;</td>
				<td style=\"width: 350px; vertical-align: top;\">
					" . $this->renderFieldset("Appearance", $this->lang->GetTranslation("Appearance"), $tableAppearance) . "
					<br><br>
					" . $this->renderFieldset("Language", $this->lang->GetTranslation("Language"), $tableLanguage) . "
					";
					/*<br><br>
					" . $this->renderFieldset("License", $this->lang->GetTranslation("License"), $tableLicense) . "*/
					if ($this->cloudMode === false)
					{
						$output .= "<br><br>" . $this->renderFieldset("Database", $this->lang->GetTranslation("DatabaseTitle"), $tableDatabase, true);
					}
					$output .= "
					<br><br>
					" . $this->renderFieldset("Smtp", $this->lang->GetTranslation("SmtpTitle"), $tableSmtp, true) . "
					" . /* SUBSITES - BETA SECTION - START */ ((SMEnvironment::IsSubSite() === false) ? "<br><br>" . $this->renderFieldset("Subsites", "Subsites (experimental)", $tableSubSites, true) : "") /* SUBSITES - BETA SECTION - END */ . "
					<br><br>
					<span style=\"float: right;\">
						" . $this->cmdCancel->Render() . "
						" . $this->cmdSave->Render() . "
					</span>
					<span style=\"clear: both;\"></span>
				</td>
			</tr>
		</table>
		";

		$output .= "
		<script type=\"text/javascript\">
		var smConfigState = { Templates: " . $this->getTemplatesJson() . ", TemplateActive: \"" . $this->lstTemplates->GetSelectedValue() . "\", PreferInstallDemoData: true };

		function smConfigChangeInputType(input, newType)
		{
			var newInput = document.createElement(\"input\");
			newInput.type = newType;
			newInput.value = input.value;
			newInput.name = input.name;

			// TODO: OnKeyDown handler is not restored for new input field - will not submit when ENTER key is pressed

			for (var i = 0 ; i < input.attributes.length ; i++)
				if (input.attributes[i].specified === true && input.attributes[i].name !== \"type\" && input.attributes[i].name !== \"value\" && input.attributes[i].name !== \"name\")
					newInput.setAttribute(input.attributes[i].name, input.attributes[i].value);

			input.parentNode.replaceChild(newInput, input);
		}

		function smConfigTemplateChangeHandler(sender)
		{
			var lstTemplates = sender;
			var selected = lstTemplates.options[lstTemplates.selectedIndex].value;
			var chkInstallWebsiteData = document.getElementById(\"" . $this->chkInstallWebsiteData->GetClientId() . "\");

			if (smConfigState.Templates[selected].HasDemoData === true)
			{
				chkInstallWebsiteData.disabled = false;
				chkInstallWebsiteData.checked = smConfigState.PreferInstallDemoData;
			}
			else
			{
				chkInstallWebsiteData.disabled = true;
				chkInstallWebsiteData.checked = false;
			}

			if (selected === smConfigState.TemplateActive)
				chkInstallWebsiteData.checked = false;
		}

		function smConfigValidateBeforeSave()
		{
			var res = true;

			if (document.getElementById(\"" . $this->chkInstallWebsiteData->GetClientId() . "\").checked === true)
			{
				res = confirm(\"" . $this->lang->GetTranslation("WarningInstallWebsiteData") . "\");
			}

			return (res === true);
		}
		</script>
		";

		// Disable password storage prompts (not W3C compliant).
		$output .= "<script type=\"text/javascript\">SMEventHandler.AddEventHandler(window, \"load\", function() { SMDom.GetElement(\"" . $this->txtPassword->GetClientId() . "\").autocomplete = \"off\"; });</script>";
		if ($this->cloudMode === false)
			$output .= "<script type=\"text/javascript\">SMEventHandler.AddEventHandler(window, \"load\", function() { SMDom.GetElement(\"" . $this->txtDbPassword->GetClientId() . "\").autocomplete = \"off\"; });</script>";

		return $output;
	}

	private function renderFieldset($id, $title, $content, $collapse = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "content", $content, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "collapse", $collapse, SMTypeCheckType::$Boolean);

		$fieldset = new SMFieldset("SMConfig" . $id);
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 350px");
		$fieldset->SetContent($content);
		$fieldset->SetLegend($title);
		$fieldset->SetPostBackControl($this->cmdSave->GetClientId());

		$fieldset->SetCollapsable($collapse);
		$fieldset->SetCollapsed($collapse);

		return $fieldset->Render();
	}

	private function loadConfiguration()
	{
		$conf = SMEnvironment::GetConfiguration();

		$this->txtUsername->SetValue((($conf->GetEntry("Username") !== null) ? $conf->GetEntry("Username") : ""));
		$this->txtPassword->SetValue((($conf->GetEntry("Password") !== null) ? $conf->GetEntry("Password") : ""));
		$this->lstLanguages->SetSelectedValue((($conf->GetEntry("Language") !== null) ? $conf->GetEntry("Language") : "en"));
		$this->lstTimeZones->SetSelectedValue((($conf->GetEntry("DefaultTimeZoneOverride") !== null) ? $conf->GetEntry("DefaultTimeZoneOverride") : ""));
		$this->lstImageThemes->SetSelectedValue((($conf->GetEntry("ImageTheme") !== null) ? $conf->GetEntry("ImageTheme") : "Default"));
		$this->lstTemplates->SetSelectedValue((($conf->GetEntry("TemplatePublic") !== null) ? $conf->GetEntry("TemplatePublic") : "Default"));
		$this->chkInstallWebsiteData->SetChecked(false);

		if ($this->hasDemoData($this->lstTemplates->GetSelectedValue()) === false)
		{
			$this->chkInstallWebsiteData->SetAttribute(SMInputAttributeCheckbox::$Disabled, "true");
		}

		//$this->txtLicense->SetValue((($conf->GetEntry("LicenseKey") !== null) ? $conf->GetEntry("LicenseKey") : ""));

		$dbInfo = (($conf->GetEntry("DatabaseConnection") !== null) ? explode(";", $conf->GetEntry("DatabaseConnection")) : array());

		if (count($dbInfo) === 4)
		{
			$this->txtDbServer->SetValue($dbInfo[0]);
			$this->txtDbDatabase->SetValue($dbInfo[1]);
			$this->txtDbUsername->SetValue($dbInfo[2]);
			$this->txtDbPassword->SetValue($dbInfo[3]);
		}

		$this->txtSmtpHost->SetValue((($conf->GetEntry("SMTPHost") !== null) ? $conf->GetEntry("SMTPHost") : ""));
		$this->txtSmtpPort->SetValue((($conf->GetEntry("SMTPPort") !== null) ? $conf->GetEntry("SMTPPort") : ""));
		$this->txtSmtpUser->SetValue((($conf->GetEntry("SMTPUser") !== null) ? $conf->GetEntry("SMTPUser") : ""));
		$this->txtSmtpPass->SetValue((($conf->GetEntry("SMTPPass") !== null) ? $conf->GetEntry("SMTPPass") : ""));
		$this->lstSmtpAuthType->SetSelectedValue((($conf->GetEntry("SMTPAuthType") !== null) ? $conf->GetEntry("SMTPAuthType") : ""));
		$this->lstSmtpEncryption->SetSelectedValue((($conf->GetEntry("SMTPEncryption") !== null) ? $conf->GetEntry("SMTPEncryption") : ""));
		$this->txtSmtpSender->SetValue((($conf->GetEntry("SMTPSender") !== null) ? $conf->GetEntry("SMTPSender") : ""));

		$this->chkLstExtensions->SetSelectedValue(implode(";", SMExtensionManager::GetExtensions()));
	}

	private function saveConfiguration()
	{
		// Update configuration file

		$conf = SMEnvironment::GetConfiguration(true);

		$lang = $this->lstLanguages->GetSelectedValue(); // NULL if no languages are available (not specified in config.xml.php)
		if ($lang !== null && $lang !== $conf->GetEntry("Language"))
		{
			SMLanguageHandler::OverrideSystemLanguage($lang);
			$conf->SetEntry("Language", $lang);
		}

		$conf->SetEntry("DefaultTimeZoneOverride", $this->lstTimeZones->GetSelectedValue());

		// Notice: TemplatePublic and TemplateAdmin are usually identical. But if they are different,
		//         only TemplatePublic will be updated (user will not see the change until logged out).
		//         If they are identical, both TemplatePublic and TemplateAdmin are updated, causing the
		//         change to be immediately visible to the administrator.

		$templateChanged = ($conf->GetEntry("TemplatePublic") !== $this->lstTemplates->GetSelectedValue());
		$templatesIdentical = ($conf->GetEntry("TemplatePublic") === $conf->GetEntry("TemplateAdmin"));
		$admTpl = (($templatesIdentical === true) ? $this->lstTemplates->GetSelectedValue() : $conf->GetEntry("TemplateAdmin"));

		$conf->SetEntry("Username", $this->txtUsername->GetValue());
		$conf->SetEntry("Password", $this->txtPassword->GetValue());
		$conf->SetEntry("ImageTheme", $this->lstImageThemes->GetSelectedValue());
		$conf->SetEntry("TemplatePublic", $this->lstTemplates->GetSelectedValue());
		$conf->SetEntry("TemplateAdmin", $admTpl);
		//$conf->SetEntry("LicenseKey", $this->txtLicense->GetValue());
		if ($this->cloudMode === false)
			$conf->SetEntry("DatabaseConnection", $this->txtDbServer->GetValue() . ";" . $this->txtDbDatabase->GetValue() . ";" . $this->txtDbUsername->GetValue() . ";" . $this->txtDbPassword->GetValue());
		$conf->SetEntry("SMTPHost", $this->txtSmtpHost->GetValue());
		$conf->SetEntry("SMTPPort", $this->txtSmtpPort->GetValue());
		$conf->SetEntry("SMTPUser", $this->txtSmtpUser->GetValue());
		$conf->SetEntry("SMTPPass", $this->txtSmtpPass->GetValue());
		$conf->SetEntry("SMTPAuthType", $this->lstSmtpAuthType->GetSelectedValue());
		$conf->SetEntry("SMTPEncryption", $this->lstSmtpEncryption->GetSelectedValue());
		$conf->SetEntry("SMTPSender", $this->txtSmtpSender->GetValue());
		//$conf->SetEntry("ExtensionsEnabled", implode(";", $this->defaultExtensions) . (($this->chkLstExtensions->GetSelectedValue() !== null) ? ";" : "") . $this->chkLstExtensions->GetSelectedValue());

		$conf->Commit();

		// Enable/Disable extensions using Extension Manager which takes care of firing Enabled/Disabled event

		$extensions = SMExtensionManager::GetExtensions(true);
		$enabled = explode(";", $this->chkLstExtensions->GetSelectedValue());

		foreach ($extensions as $ext)
		{
			if (in_array($ext, $this->defaultExtensions) === true)
				continue;

			SMExtensionManager::SetExtensionEnabled($ext, in_array($ext, $enabled, true)); // Enabled/Disabled event is only fired if Enabled state is changed
		}

		// Install template demo data if template was changed.
		// NOTICE: Only files (e.g. images) will be available if Sitemagic has been configured with MySQL!

		if ($this->chkInstallWebsiteData->GetChecked() === true)
		{
			// Handle extension dependencies

			$mdFile = SMEnvironment::GetTemplatesDirectory() . "/" . $this->lstTemplates->GetSelectedValue() . "/metadata.xml";
			$alternativeData = null;

			if (SMFileSystem::FileExists($mdFile) === true)
			{
				$cfg = new SMConfiguration($mdFile);
				$dependencies = $cfg->GetEntry("Dependencies");
				$alternativeData = $cfg->GetEntry("TemplateData");

				if ($dependencies !== null && $dependencies !== "")
				{
					$deps = explode(";", $dependencies);

					foreach ($deps as $dep)
					{
						if (SMExtensionManager::ExtensionExists($dep, true) === true)
						{
							SMExtensionManager::SetExtensionEnabled($dep, true);
						}
					}
				}
			}

			// Handle demo data

			$path = SMEnvironment::GetTemplatesDirectory() . "/" . (($alternativeData !== null && $alternativeData !== "") ? $alternativeData : $this->lstTemplates->GetSelectedValue()) . "/demo-data";

			// Allow session to be restored if SMAttributes.xml.php is replaced.
			// If the session name contained in SMAttributes.xml.php is not restored,
			// then the user will loose the session once SMAttributes.xml.php is replaced,
			// and be logged out of Sitemagic.
			// This solution is ugly as it requires knowledge about how SMEnvironment
			// establishes the session (see Initialize() function).

			$sessionToRestore = null;
			if (SMFileSystem::FileExists($path . "/data/SMAttributes.xml.php") === true)
			{
				$sessionToRestore = SMAttributes::GetAttribute("SMEnvironmentSessionName" . md5(SMEnvironment::GetDocumentRoot()));
			}

			// Copy folders - will merge with existing folders but overwrite existing files

			if (SMFileSystem::FolderExists($path . "/data") === true)
			{
				SMFileSystem::Copy($path . "/data", SMEnvironment::GetDataDirectory(), true);

				// Purge old data to prevent Sitemagic from committing it back at the end of the page's life cycle

				$dataSourceNames = SMDataSourceCache::GetInstance()->GetDataSourceNames();
				foreach ($dataSourceNames as $dataSourceName)
				{
					SMDataSourceCache::GetInstance()->RemoveDataSource($dataSourceName);
				}

				SMAttributes::Reload(true, true);
			}

			if (SMFileSystem::FolderExists($path . "/files") === true)
				SMFileSystem::Copy($path . "/files", SMEnvironment::GetFilesDirectory(), true);

			/*if (SMFileSystem::FolderExists($path . "/images") === true) // WARNING: May not be writable since we never instruct users to make it so!
				SMFileSystem::Copy($path . "/images", SMEnvironment::GetImagesDirectory(), true);

			if (SMFileSystem::FolderExists($path . "/extensions") === true) // WARNING: May not be writable since we never instruct users to make it so!
				SMFileSystem::Copy($path . "/extensions", SMEnvironment::GetExtensionsDirectory(), true);*/

			// Restore session

			if ($sessionToRestore !== null)
			{
				$sessionToRestore = SMAttributes::SetAttribute("SMEnvironmentSessionName" . md5(SMEnvironment::GetDocumentRoot()), $sessionToRestore);
			}
		}

		// Re-login in case username or password was changed

		SMAuthentication::Login($this->txtUsername->GetValue(), $this->txtPassword->GetValue());

		// Reload

		$args = new SMKeyValueCollection();
		$args["SMConfigSaved"] = "true";

		// Administrator may have overridden design template using URL parameters.
		// Make sure a newly selected design template becomes immediately visible to the user.
		if ($templateChanged === true)
		{
			SMTemplateInfo::ClearTemplateOverrides();

			/*$args["SMTplPublic"] = $conf->getEntry("TemplatePublic");

			// Update admin session template if both public template and admin template are identical.
			// Only then would we expect both sessions to adjust to the newly selected design template.
			if ($templatesIdentical === true)
				$args["SMTplAdmin"] = $conf->getEntry("TemplateAdmin");*/
		}

		// "Restart" - we need to make sure that added/removed extensions does not (or has not) been partially
		// executed. Also make sure that e.g. design template is reloaded (it was loaded very early).
		SMExtensionManager::ExecuteExtension("SMConfig", $args);
	}

	private function hasDemoData($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);

		$hasData = SMFileSystem::FolderExists(SMEnvironment::GetTemplatesDirectory() . "/" . $template . "/demo-data");

		if ($hasData === false) // Check if metadata.xml points to data from another template
		{
			$mdFile = SMEnvironment::GetTemplatesDirectory() . "/" . $template . "/metadata.xml";

			if (SMFileSystem::FileExists($mdFile) === true)
			{
				$cfg = new SMConfiguration($mdFile);

				if ($cfg->GetEntry("TemplateData") !== null && $cfg->GetEntry("TemplateData") !== "" && SMFileSystem::FolderExists(SMEnvironment::GetTemplatesDirectory() . "/" . $cfg->GetEntry("TemplateData") . "/demo-data") === true)
					$hasData = true;
			}
		}

		return $hasData;
	}

	private function copyTemplate()
	{
		if ($this->cloudMode === true)
			return; // Make sure function cannot be triggered programmatically client side

		$newName = $this->txtCopyTemplateName->GetValue();

		if (SMStringUtilities::Validate($newName, SMValueRestriction::$AlphaNumeric) === false)
		{
			$this->msg = $this->lang->GetTranslation("WarningTemplateNameInvalid");
			return;
		}

		$templatesDir = SMEnvironment::GetTemplatesDirectory();

		if (SMFileSystem::FolderIsWritable($templatesDir) === false)
		{
			$this->msg = $this->lang->GetTranslation("WarningTemplatesNotWritable");
			return;
		}

		if (SMFileSystem::FolderExists($templatesDir . "/" . $newName) === true)
		{
			$this->msg = $this->lang->GetTranslation("WarningTemplateAlreadyExists");
			return;
		}

		$res = SMFileSystem::Copy($templatesDir . "/" . $this->lstTemplates->GetSelectedValue(), $templatesDir . "/" . $newName);

		if ($res === true)
		{
			$this->msg = $this->lang->GetTranslation("NotificationTemplateCopySucceeded");
			$this->populateTemplates(true);
		}
		else
		{
			$this->msg = $this->lang->GetTranslation("WarningTemplateCopyFailed");
		}
	}

	private function deleteTemplate()
	{
		if ($this->cloudMode === true)
			return; // Make sure function cannot be triggered programmatically client side

		$tpl = $this->lstTemplates->GetSelectedValue();

		if (SMTemplateInfo::GetPublicTemplate() === $tpl || SMTemplateInfo::GetAdminTemplate() === $tpl || SMTemplateInfo::GetCurrentTemplate() === $tpl || $this->checkTemplateInUse($tpl) === true)
		{
			$this->msg = $this->lang->GetTranslation("WarningTemplateDeleteInUse");
			return;
		}

		$templatesDir = SMEnvironment::GetTemplatesDirectory();

		if (SMFileSystem::FolderIsWritable($templatesDir . "/" . $tpl) === false)
		{
			$this->msg = $this->lang->GetTranslation("WarningTemplateNotWritable") . ": " . $templatesDir . "/" . $tpl;
			return;
		}

		$res = SMFileSystem::Delete($templatesDir . "/" . $tpl, true);

		if ($res === true)
		{
			$this->msg = $this->lang->GetTranslation("NotificationTemplateDeleteSucceeded");
			$this->populateTemplates(true);
		}
		else
		{
			$this->msg = $this->lang->GetTranslation("WarningTemplateDeleteFailed");
		}
	}

	private function checkTemplateInUse($tpl)
	{
		SMTypeCheck::CheckObject(__METHOD__, "tpl", $tpl, SMTypeCheckType::$String);

		if (SMExtensionManager::ExtensionEnabled("SMPages") === false)
			return false;

		$pages = SMPagesLoader::GetPages();

		foreach ($pages as $page)
			if ($page->GetTemplate() === $tpl)
				return true;

		return false;
	}


	// SUBSITES - BETA SECTION - START
	// REQUIRES REVIEW/IMPROVEMENTS!

	private function deleteSubSite($name)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);

		SMFileSystem::Delete("sites/" . $name, true); // TODO: Extension should NOT know anothing about the sites/xyz path
		$this->populateSubSites();
	}

	// SUBSITES - BETA SECTION - END
}

?>
