<?php



// NOTICE: This was developed really quickly - it needs improvements !!!
//  - Design (looks terrible)
//  - Input validation
//  - Support for white or black listing extensions (SMLanguageEditor allow subsite admins to mess up translations on all sites)
//  - Support for editing (currently only Create and Delete works)


class SMConfigFrmSubSite implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $error;

	private $txtName;
	private $txtUsername;
	private $txtPassword;
	private $lstFiles;
	private $lstTemplates;
	private $txtUploadFilter;
	private $cmdCreate;

	// TODO: Whitelist extensions (so e.g. SMLanguageEditor does not become available)

	// TODO: This extension knows WAY TO MUCH about subsites! Separate ALL logic
	// related to subsites into some sort of Subsite Manager !!!

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMConfig");
		$this->error = null;

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Administration")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtName = new SMInput("SMConfigName", SMInputType::$Text);
		$this->txtName->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtName->SetAttribute(SMInputAttributeText::$MaxLength, "50");

		$this->txtUsername = new SMInput("SMConfigUsername", SMInputType::$Text);
		$this->txtUsername->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtUsername->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtPassword = new SMInput("SMConfigPassword", SMInputType::$Password);
		$this->txtPassword->SetValue((($this->context->GetForm()->PostBack() === true) ? $this->txtPassword->GetValue() : "")); // Disable security feature: Prevent password field from being cleared after postback
		$this->txtPassword->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtPassword->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->chkMaskPassword = new SMInput("SMConfigMaskPassword", SMInputType::$Checkbox);
		$this->chkMaskPassword->SetAttribute(SMInputAttributeCheckbox::$OnClick, "var parent = (window.opener || window.top); parent.smConfigChangeInputType(SMDom.GetElement('" . $this->txtPassword->GetClientId() . "'), ((this.checked === true) ? 'password' : 'text'))");
		$this->chkMaskPassword->SetChecked(true);

		$this->lstFiles = new SMOptionList("SMConfigFiles");
		$this->lstFiles->SetAttribute(SMOptionListAttribute::$Style, "width: 300px");
		$this->lstFiles->AddOption(new SMOptionListItem("SMConfigFilesShared", "Shared with main site", "Shared"));
		$this->lstFiles->AddOption(new SMOptionListItem("SMConfigFilesSeparateCopy", "Separate (copy from main site)", "SeparateCopy"));
		$this->lstFiles->AddOption(new SMOptionListItem("SMConfigFilesSeparateEmpty", "Separate (empty - not recommended)", "SeparateEmpty")); // Creating a new site without all the images from the original site will most likely break the design

		$this->lstTemplates = new SMOptionList("SMConfigTemplates");
		$this->lstTemplates->SetAttribute(SMOptionListAttribute::$Style, "width: 300px");
		$this->lstTemplates->AddOption(new SMOptionListItem("SMConfigTemplatesSeparateEmpty", "Shared with main site", "Shared"));
		$this->lstTemplates->AddOption(new SMOptionListItem("SMConfigTemplatesSeparateCopy", "Separate (copy from main site)", "SeparateCopy"));

		$this->txtUploadFilter = new SMInput("SMConfigFileFilter", SMInputType::$Text);
		$this->txtUploadFilter->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtUploadFilter->SetAttribute(SMInputAttributeText::$MaxLength, "500");

		$this->cmdCreate = new SMLinkButton("SMConfigCreate");
		$this->cmdCreate->SetTitle("Create");
		$this->cmdCreate->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdCreate->PerformedPostBack() === true)
			{
				$this->createSubSite();
			}
		}
	}

	public function Render()
	{
		$output = "";

		if ($this->error !== null)
			$output .= SMNotify::Render($this->error);

		if ($this->error === null && $this->cmdCreate->PerformedPostBack() === true)
		{
			return "
			<script type=\"text/javascript\">
			var parent = (window.opener || window.top);
			parent.SMForm.PostBack()
			</script>";
		}

		if (SMDataSource::GetDataSourceType() === SMDataSourceType::$MySql)
		{
			$output .= "<span style='color: red'>IMPORTANT: The new subsite will initially use the same database as the primary site. Log in to the new subsite and point it to a separate database when it has been created.</span><br><br>";
		}

		$output .= "
		Site name (subsite URL: sites/name)<br>
		" . $this->txtName->Render() . "
		<br><br>
		Username<br>
		" . $this->txtUsername->Render() . "
		<br><br>
		Password<br>
		" . $this->txtPassword->Render() . "
		<br>
		" . $this->chkMaskPassword->Render() . " Hide password
		<br><br>
		Files<br>
		" . $this->lstFiles->Render() . "
		<br><br>
		Templates<br>
		" . $this->lstTemplates->Render() . "
		<br><br>
		Restrict file upload (e.g. png;gif;jpg;jpeg)<br>
		" . $this->txtUploadFilter->Render() . "
		<br><br>
		" . $this->cmdCreate->Render() . "
		";

		/*$output .= "
		<table>
			<tr>
				<td style=\"width: 100px\">" . $this->lang->GetTranslation("Username") . "</td>
				<td style=\"width: 150px\">" . $this->txtUsername->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">" . $this->lang->GetTranslation("Password") . "</td>
				<td style=\"width: 150px\">" . $this->txtPassword->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">" . $this->lang->GetTranslation("Language") . "</td>
				<td style=\"width: 150px\">" . $this->lstLanguages->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">&nbsp;</td>
				<td style=\"width: 150px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">&nbsp;</td>
				<td style=\"width: 150px\"><div style=\"text-align: right\">" . $this->cmdLogin->Render() . "</div></td>
			</tr>
		</table>
		";*/

		$fieldset = new SMFieldset("SMConfigSubSite");
		//$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 500px");
		$fieldset->SetDisplayFrame(false);
		$fieldset->SetContent($output);
		$fieldset->SetPostBackControl($this->cmdCreate->GetClientId());

		return $fieldset->Render();
	}

	private function createSubSite()
	{
		if (SMFileSystem::FolderIsWritable("sites") === false)
		{
			$this->error = "Subsite folder is not writable";
			return;
		}

		$name = $this->txtName->GetValue();

		if ($name === "") // TODO: RegEx match!
		{
			$this->error = "Invalid subsite name (A-Z, 0-9, dash, dot, and underscore)";
			return;
		}

		$username = $this->txtUsername->GetValue();
		$password = $this->txtPassword->GetValue();

		if ($username === "" || $password === "")
		{
			$this->error = "Username and password must be set";
			return;
		}

		$files = $this->lstFiles->GetSelectedValue();
		$templates = $this->lstTemplates->GetSelectedValue();
		$filter = $this->txtUploadFilter->GetValue();

		$subSiteDir = "sites/" . $name;

		SMFileSystem::CreateFolder($subSiteDir);
		SMFileSystem::CreateFolder($subSiteDir . "/data");
		//SMFileSystem::CreateFolder($subSiteDir . "/files");
		//SMFileSystem::CreateFolder($subSiteDir . "/templates");

		SMFileSystem::Copy("config.xml.php", $subSiteDir . "/config.xml.php");

		$cfg = new SMConfiguration($subSiteDir . "/config.xml.php", true);
		$cfg->SetEntry("Username", $username);
		$cfg->SetEntry("Password", $password);
		if ($filter !== "")
			$cfg->SetEntry("FileExtensionFilter", $filter);
		$cfg->Commit();

		SMFileSystem::Copy("index.php", $subSiteDir . "/index.php");

		if ($files === "Shared")
		{
			SMFileSystem::CreateFolder($subSiteDir . "/files");
			SMFileSystem::Copy("sites/htaccess.fls", $subSiteDir . "/files/.htaccess");
		}
		else if ($files === "SeparateEmpty")
		{
			SMFileSystem::CreateFolder($subSiteDir . "/files");
			SMFileSystem::CreateFolder($subSiteDir . "/files/images");
			SMFileSystem::CreateFolder($subSiteDir . "/files/media");
		}
		else // SeparateCopy
		{
			SMFileSystem::Copy("files", $subSiteDir . "/files");
		}

		if ($templates === "Shared")
		{
			SMFileSystem::CreateFolder($subSiteDir . "/templates");
			SMFileSystem::Copy("sites/htaccess.tpls", $subSiteDir . "/templates/.htaccess");
		}
		else // SeparateCopy
		{
			SMFileSystem::Copy("templates", $subSiteDir . "/templates");
		}
	}
}

?>
