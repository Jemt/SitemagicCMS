<?php

class SMLoginFrmLogin implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $error;

	private $txtUsername;
	private $txtPassword;
	private $lstLanguages;
	private $cmdLogin;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMLogin");
		$this->error = false;

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Administration")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtUsername = new SMInput("SMLoginUsername", SMInputType::$Text);
		$this->txtUsername->SetAttribute(SMInputAttributeText::$Style, "width: 150px");

		$this->txtPassword = new SMInput("SMLoginPassword", SMInputType::$Password);
		$this->txtPassword->SetAttribute(SMInputAttributeText::$Style, "width: 150px");

		$this->lstLanguages = new SMOptionList("SMLoginLanguages");

		$languageSet = SMLanguageHandler::GetSystemLanguage();
		$languages = SMLanguageHandler::GetLanguages();

		foreach ($languages as $language)
		{
			$this->lstLanguages->AddOption(new SMOptionListItem("SMLogin" . $language, $language, $language));

			if ($this->context->GetForm()->PostBack() === false && $languageSet === $language)
				$this->lstLanguages->SetSelectedValue($language);
		}

		$this->cmdLogin = new SMLinkButton("SMLoginSubmit");
		$this->cmdLogin->SetIcon(SMImageProvider::GetImage(SMImageType::$Unlock));
		$this->cmdLogin->SetTitle($this->lang->GetTranslation("Login"));
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdLogin->PerformedPostBack() === true)
			{
				$res = SMAuthentication::Login($this->txtUsername->GetValue(), $this->txtPassword->GetValue());

				if ($res === true)
				{
					if ($this->lstLanguages->GetSelectedValue() !== null)
						SMLanguageHandler::OverrideSystemLanguage($this->lstLanguages->GetSelectedValue());

					if (SMExtensionManager::ExtensionExists("SMAnnouncements") === true)
						SMExtensionManager::ExecuteExtension("SMAnnouncements");

					SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());
				}
				else
				{
					$this->error = true;
				}
			}
		}
	}

	public function Render()
	{
		$output = "";

		if ($this->error === true)
			$output .= SMNotify::Render($this->lang->GetTranslation("Error"));

		$output .= "
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
		";

		$fieldset = new SMFieldset("SMLogin");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 275px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Login"));
		$fieldset->SetPostBackControl($this->cmdLogin->GetClientId());

		return $fieldset->Render();
	}
}

?>
