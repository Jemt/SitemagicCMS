<?php

class SMContactFrmSettings implements SMIExtensionForm
{
	private $context;
	private $manager;
	private $lang;

	private $messageSettings;
	private $messageFieldForm;
	private $messageFieldList;

	private $lstConfigs;

	private $txtRecipients;
	private $txtSubject;
	private $txtSuccessMessage;
	private $cmdSaveSettings;

	private $txtFieldId;
	private $txtFieldTitle;
	private $lstFieldTypes;
	private $cmdCreate;
	private $cmdClear;
	private $cmdSave;

	private $grdFields;
	private $cmdEdit;
	private $cmdDelete;
	private $cmdMoveUp;
	private $cmdMoveDown;

	private $displayEdit;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->manager = new SMContactFields();
		$this->lang = new SMLanguageHandler("SMContact");

		$this->messageSettings = "";
		$this->messageFieldForm = "";
		$this->messageFieldList = "";

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("ContactForms")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->lstConfigs = new SMOptionList("SMContactConfig");
		$this->lstConfigs->SetAttribute(SMOptionListAttribute::$Style, "width: 250px");
		$this->lstConfigs->SetAutoPostBack(true);

		if ($this->context->GetForm()->PostBack() === false)
			$this->lstConfigs->SetSelectedValue("");

		$this->createContactFormsList();

		// Load configuration from drop down menu above
		$this->manager->SetAlternativeInstanceId($this->lstConfigs->GetSelectedValue());
		$this->manager->LoadPersistentFields();

		$this->txtRecipients = new SMInput("SMContactRecipients", SMInputType::$Text);
		$this->txtRecipients->SetAttribute(SMInputAttributeText::$Style, "width: 250px");
		$this->txtRecipients->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtSubject = new SMInput("SMContactSubject", SMInputType::$Text);
		$this->txtSubject->SetAttribute(SMInputAttributeText::$Style, "width: 250px");
		$this->txtSubject->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtSuccessMessage = new SMInput("SMContactSuccessMessage", SMInputType::$Text);
		$this->txtSuccessMessage->SetAttribute(SMInputAttributeText::$Style, "width: 250px");
		$this->txtSuccessMessage->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		if ($this->context->GetForm()->PostBack() === false || $this->lstConfigs->PerformedPostBack() === true)
		{
			$this->txtRecipients->SetValue(SMContactSettings::GetRecipients());
			$this->txtSubject->SetValue(SMContactSettings::GetSubject());
			$this->txtSuccessMessage->SetValue(SMContactSettings::GetSuccessMessage());
		}

		$this->cmdSaveSettings = new SMLinkButton("SMContactSaveSettings");
		$this->cmdSaveSettings->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
		$this->cmdSaveSettings->SetTitle($this->lang->GetTranslation("Save"));

		$this->txtFieldId = new SMInput("SMContactFieldId", SMInputType::$Hidden);

		$this->txtFieldTitle = new SMInput("SMContactFieldTitle", SMInputType::$Text);
		$this->txtFieldTitle->SetAttribute(SMInputAttributeText::$Style, "width: 200px");
		$this->txtFieldTitle->SetAttribute(SMInputAttributeText::$MaxLength, "255");

		$this->lstFieldTypes = new SMOptionList("SMContactFieldTypes");
		$this->lstFieldTypes->SetAttribute(SMOptionListAttribute::$Style, "width: 200px");
		$this->lstFieldTypes->AddOption(new SMOptionListItem("SMContactFieldTypeEmpty", "", ""));
		$this->lstFieldTypes->AddOption(new SMOptionListItem("SMContactFieldTypeTextField", $this->lang->GetTranslation("TextField"), SMContactFieldTypes::$Textfield));
		$this->lstFieldTypes->AddOption(new SMOptionListItem("SMContactFieldTypeTextBox", $this->lang->GetTranslation("TextBox"), SMContactFieldTypes::$Textbox));
		$this->lstFieldTypes->AddOption(new SMOptionListItem("SMContactFieldTypeCheckBox", $this->lang->GetTranslation("CheckBox"), SMContactFieldTypes::$Checkbox));
		$this->lstFieldTypes->AddOption(new SMOptionListItem("SMContactFieldTypeEmail", $this->lang->GetTranslation("EmailCopy"), SMContactFieldTypes::$Email));
		$this->lstFieldTypes->AddOption(new SMOptionListItem("SMContactFieldTypeAttachment", $this->lang->GetTranslation("Attachment"), SMContactFieldTypes::$Attachment));

		$this->cmdCreate = new SMLinkButton("SMContactCreate");
		$this->cmdCreate->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));
		$this->cmdCreate->SetTitle($this->lang->GetTranslation("Create"));

		$this->cmdClear = new SMLinkButton("SMContactClear");
		$this->cmdClear->SetIcon(SMImageProvider::GetImage(SMImageType::$Clear));
		$this->cmdClear->SetTitle($this->lang->GetTranslation("Clear"));

		$this->cmdSave = new SMLinkButton("SMContactSave");
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
		$this->cmdSave->SetTitle($this->lang->GetTranslation("Save"));

		$this->createFieldList();

		$this->cmdEdit = new SMLinkButton("SMContactEdit");
		$this->cmdEdit->SetIcon(SMImageProvider::GetImage(SMImageType::$Properties));
		$this->cmdEdit->SetTitle($this->lang->GetTranslation("Edit"));

		$this->cmdDelete = new SMLinkButton("SMContactDelete");
		$this->cmdDelete->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDelete->SetTitle($this->lang->GetTranslation("Delete"));
		$this->cmdDelete->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteWarning", true) . "') === false) { return false; }");

		$this->cmdMoveUp = new SMLinkButton("SMContactMoveUp");
		$this->cmdMoveUp->SetIcon(SMImageProvider::GetImage(SMImageType::$Up));

		$this->cmdMoveDown = new SMLinkButton("SMContactMoveDown");
		$this->cmdMoveDown->SetIcon(SMImageProvider::GetImage(SMImageType::$Down));

		$this->displayEdit = false;
	}

	private function createContactFormsList()
	{
		$this->lstConfigs->SetOptions(array());

		for ($i = 0 ; $i < 10 ; $i++)
		{
			$this->manager->SetAlternativeInstanceId((($i > 0) ? "CONF" . $i . "_" : ""));
			$this->lstConfigs->AddOption(new SMOptionListItem("SMContactConfig" . $i, $this->lang->GetTranslation("Form") . " " . ($i + 1) . ((SMContactSettings::GetSubject() !== "") ? " (" . SMContactSettings::GetSubject() . ")" : ""), (($i > 0) ? "CONF" . $i . "_" : "")));
		}
	}

	private function createFieldList()
	{
		$fields = $this->manager->GetFields();

		$this->grdFields = new SMGrid("SMContactFieldList");
		$this->grdFields->EnableSelector($this->lang->GetTranslation("FieldTitle"));

		$data = array();
		$type = "";
		foreach ($fields as $field)
		{
			$type = "";
			if ($field->GetType() === SMContactFieldTypes::$Textfield)
				$type = $this->lang->GetTranslation("TextField");
			else if ($field->GetType() === SMContactFieldTypes::$Textbox)
				$type = $this->lang->GetTranslation("TextBox");
			else if ($field->GetType() === SMContactFieldTypes::$Checkbox)
				$type = $this->lang->GetTranslation("CheckBox");
			else if ($field->GetType() === SMContactFieldTypes::$Email)
				$type = $this->lang->GetTranslation("EmailCopy");
			else if ($field->GetType() === SMContactFieldTypes::$Attachment)
				$type = $this->lang->GetTranslation("Attachment");

			$data[md5($field->GetTitle())] = array(
				$this->lang->GetTranslation("FieldTitle")	=> $field->GetTitle(),
				$this->lang->GetTranslation("FieldType")	=> $type
			);
		}

		if (count($data) === 0)
		{
			$data[] = array($this->lang->GetTranslation("FieldTitle") => "", $this->lang->GetTranslation("FieldType") => "");
			$this->grdFields->DisableSelector();
		}

		$this->grdFields->SetData($data);
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->lstConfigs->PerformedPostBack() === true)
				$this->clearForm();
			else if ($this->cmdSaveSettings->PerformedPostBack() === true)
				$this->saveSettings();
			elseif ($this->cmdCreate->PerformedPostBack() === true)
				$this->createField();
			else if ($this->cmdClear->PerformedPostBack() === true)
				$this->clearForm();
			else if ($this->cmdSave->PerformedPostBack() === true)
				$this->saveField();
			else if ($this->cmdEdit->PerformedPostBack() === true)
				$this->setEditMode();
			else if ($this->cmdDelete->PerformedPostBack() === true)
				$this->deleteField();
			else if ($this->cmdMoveUp->PerformedPostBack() === true)
				$this->moveFieldUp();
			else if ($this->cmdMoveDown->PerformedPostBack() === true)
				$this->moveFieldDown();
		}
	}

	private function saveSettings()
	{
		SMContactSettings::SetRecipients($this->txtRecipients->GetValue());
		SMContactSettings::SetSubject($this->txtSubject->GetValue());
		SMContactSettings::SetSuccessMessage($this->txtSuccessMessage->GetValue());

		// SaveRecipients(..) removes whitespaces and replaces semicolon, so to ensure that
		// we have the actual attribute value displayed, we reload the saved value.
		$this->txtRecipients->SetValue(SMContactSettings::GetRecipients());

		$this->messageSettings = $this->lang->GetTranslation("SettingsSaved");

		$this->createContactFormsList();
	}

	private function createField()
	{
		$title = $this->txtFieldTitle->GetValue();
		$type = $this->lstFieldTypes->GetSelectedValue();

		if ($title === "" || $type === "")
		{
			$this->messageFieldForm = $this->lang->GetTranslation("ErrorFillOutAll");
			return;
		}

		if ($this->manager->GetFieldByTitle($title) !== null)
		{
			$this->messageFieldForm = $this->lang->GetTranslation("ErrorTitleAlreadyExists");
			return;
		}

		$this->manager->AddField(new SMContactField($title, $type));
		$this->manager->Sort(true); // Sort and commits

		$this->createFieldList();
		$this->clearForm();

		$this->saveSettings(); // Users forget to save changes to settings when customizing fields
	}

	private function clearForm()
	{
		$this->txtFieldId->SetValue("");
		$this->txtFieldTitle->SetValue("");
		$this->lstFieldTypes->SetSelectedValue("");
	}

	private function setEditMode()
	{
		if ($this->grdFields->GetSelectionMade() === false)
		{
			$this->messageFieldList = $this->lang->GetTranslation("ErrorNoSelection");
			return;
		}

		$title = $this->grdFields->GetSelectedValue();
		$field = (($title !== null) ? $this->manager->GetFieldByTitle($title) : null);

		if ($field === null)
		{
			$this->messageFieldList = $this->lang->GetTranslation("ErrorMissing");
			return;
		}

		$this->txtFieldId->SetValue($field->GetId());
		$this->txtFieldTitle->SetValue($field->GetTitle());
		$this->lstFieldTypes->SetSelectedValue($field->GetType());

		$this->displayEdit = true;
	}

	private function saveField()
	{
		$newTitle = $this->txtFieldTitle->GetValue();
		$newType = $this->lstFieldTypes->GetSelectedValue();

		if ($newTitle === "" || $newType === "")
		{
			$this->messageFieldForm = $this->lang->GetTranslation("ErrorFillOutAll");
			return;
		}

		$fieldId = $this->txtFieldId->GetValue();
		$field = $this->manager->GetFieldById($fieldId);

		if ($field === null)
		{
			$this->messageFieldForm = $this->lang->GetTranslation("ErrorMissing");
			return;
		}

		if ($field->GetTitle() !== $newTitle && $this->manager->GetFieldByTitle($newTitle) !== null)
		{
			$this->messageFieldForm = $this->lang->GetTranslation("ErrorTitleAlreadyExists");
			return;
		}

		$field->SetTitle($newTitle);
		$field->SetType($newType);

		$field->CommitPersistent();
		$this->createFieldList();

		$this->clearForm();

		$this->saveSettings(); // Users forget to save changes to settings when customizing fields
	}

	private function deleteField()
	{
		if ($this->grdFields->GetSelectionMade() === false)
		{
			$this->messageFieldList = $this->lang->GetTranslation("ErrorNoSelection");
			return;
		}

		$title = $this->grdFields->GetSelectedValue();
		$field = (($title !== null) ? $this->manager->GetFieldByTitle($title) : null);

		if ($field === null)
		{
			$this->messageFieldList = $this->lang->GetTranslation("ErrorMissing");
			return;
		}

		$this->manager->RemoveField($field->GetId(), true);
		$this->createFieldList();
		$this->grdFields->SetRestoreSelection(false);

		$this->saveSettings(); // Users forget to save changes to settings when customizing fields
	}

	private function moveFieldUp()
	{
		if ($this->grdFields->GetSelectionMade() === false)
		{
			$this->messageFieldList = $this->lang->GetTranslation("ErrorNoSelection");
			return;
		}

		$title = $this->grdFields->GetSelectedValue();
		$field = (($title !== null) ? $this->manager->GetFieldByTitle($title) : null);

		if ($field === null)
		{
			$this->messageFieldList = $this->lang->GetTranslation("ErrorMissing");
			return;
		}

		$this->manager->MoveFieldUp($field->GetId(), true);
		$this->createFieldList();

		$this->saveSettings(); // Users forget to save changes to settings when customizing fields
	}

	private function moveFieldDown()
	{
		if ($this->grdFields->GetSelectionMade() === false)
		{
			$this->messageFieldList = $this->lang->GetTranslation("ErrorNoSelection");
			return;
		}

		$title = $this->grdFields->GetSelectedValue();
		$field = (($title !== null) ? $this->manager->GetFieldByTitle($title) : null);

		if ($field === null)
		{
			$this->messageFieldList = $this->lang->GetTranslation("ErrorMissing");
			return;
		}

		$this->manager->MoveFieldDown($field->GetId(), true);
		$this->createFieldList();

		$this->saveSettings(); // Users forget to save changes to settings when customizing fields
	}

	public function Render()
	{
		$output = "";

		$output .= $this->renderSettings();
		$output .= "<br><br>";

		$output .= "
		<table style=\"width: 100%\">
			<tr>
				<td style=\"vertical-align: top; width: 330px;\">
					" . $this->renderForm() . "
				</td>
				<td style=\"width: 50px\">&nbsp;</td>
				<td style=\"vertical-align: top\">
					" . $this->renderList() . "
				</td>
			</tr>
		</table>
		";

		return $output;
	}

	private function renderSettings()
	{
		$outputSettings = "";

		if ($this->messageSettings !== "")
			$outputSettings .= SMNotify::Render($this->messageSettings);

		$outputSettings .= "
		<table>
			<tr>
				<td style=\"width: 150px\">" . $this->lang->GetTranslation("ChangeForm") . "</td>
				<td style=\"width: 250px\">" . $this->lstConfigs->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">&nbsp;</td>
				<td style=\"width: 250px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">" . $this->lang->GetTranslation("Recipients") . "</td>
				<td style=\"width: 250px\">" . $this->txtRecipients->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">" . $this->lang->GetTranslation("Subject") . "</td>
				<td style=\"width: 250px\">" . $this->txtSubject->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">" . $this->lang->GetTranslation("SuccessMessage") . "</td>
				<td style=\"width: 250px\">" . $this->txtSuccessMessage->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">&nbsp;</td>
				<td style=\"width: 250px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">&nbsp;</td>
				<td style=\"width: 250px\"><div style=\"text-align: right\">" . $this->cmdSaveSettings->Render() . "</div></td>
			</tr>
		</table>
		";

		$settingsFieldset = new SMFieldset("SMContactSettings");
		$settingsFieldset->SetContent($outputSettings);
		$settingsFieldset->SetLegend($this->lang->GetTranslation("FieldsetSettings"));
		$settingsFieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 400px");
		$settingsFieldset->SetPostBackControl($this->cmdSaveSettings->GetClientId());

		return $settingsFieldset->Render();
	}

	private function renderForm()
	{
		$outputFields = "";

		if ($this->messageFieldForm !== "")
			$outputFields .= SMNotify::Render($this->messageFieldForm);

		$outputFields .= "
		<table>
			<tr>
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("FieldTitle") . "</td>
				<td style=\"width: 200px\">" . $this->txtFieldId->Render() . $this->txtFieldTitle->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 120px\">" . $this->lang->GetTranslation("FieldType") . "</td>
				<td style=\"width: 200px\">" . $this->lstFieldTypes->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 120px\">&nbsp;</td>
				<td style=\"width: 200px\">&nbsp;</td>
			</tr>
		</table>
		<div style=\"text-align: right\">" . (($this->displayEdit === false) ? $this->cmdCreate->Render() : $this->cmdClear->Render() . " " . $this->cmdSave->Render()) . "</div>
		";

		$fieldsFieldset = new SMFieldset("SMContactFields");
		$fieldsFieldset->SetContent($outputFields);
		$fieldsFieldset->SetLegend($this->lang->GetTranslation("FieldsetFieldForm"));
		$fieldsFieldset->SetPostBackControl((($this->displayEdit === false) ? $this->cmdCreate->GetClientId() : $this->cmdSave->GetClientId()));

		return $fieldsFieldset->Render();
	}

	private function renderList()
	{
		$output = "";

		if ($this->messageFieldList !== "")
			$output .= SMNotify::Render($this->messageFieldList);

		$output .= "
		" . $this->grdFields->Render() . "
		<br>
		<div style=\"text-align: right\">" . $this->cmdEdit->Render() . " " . $this->cmdDelete->Render() . " " . $this->cmdMoveUp->Render() . " " . $this->cmdMoveDown->Render() . "</div>
		";

		$fieldsFieldset = new SMFieldset("SMContactFieldList");
		$fieldsFieldset->SetAttribute(SMFieldsetAttribute::$Style, "max-width: 500px");
		$fieldsFieldset->SetContent($output);
		$fieldsFieldset->SetLegend($this->lang->GetTranslation("FieldsetFieldList"));

		return $fieldsFieldset->Render();
	}
}

?>
