<?php

class SMGalleryFrmSettings implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $message;

	private $txtColumns;
	private $txtRows;
	private $txtWidth;
	private $txtHeight;
	private $txtPadding;
	private $cmdSave;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMGallery");
		$this->message = "";

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtColumns = new SMInput("SMGalleryColumns", SMInputType::$Text);
		$this->txtColumns->SetAttribute(SMInputAttributeText::$Style, "width: 100px");
		$this->txtColumns->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtRows = new SMInput("SMGalleryRows", SMInputType::$Text);
		$this->txtRows->SetAttribute(SMInputAttributeText::$Style, "width: 100px");
		$this->txtRows->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtWidth = new SMInput("SMGalleryWidth", SMInputType::$Text);
		$this->txtWidth->SetAttribute(SMInputAttributeText::$Style, "width: 100px");
		$this->txtWidth->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtHeight = new SMInput("SMGalleryHeight", SMInputType::$Text);
		$this->txtHeight->SetAttribute(SMInputAttributeText::$Style, "width: 100px");
		$this->txtHeight->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		$this->txtPadding = new SMInput("SMGalleryPadding", SMInputType::$Text);
		$this->txtPadding->SetAttribute(SMInputAttributeText::$Style, "width: 100px");
		$this->txtPadding->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		if ($this->context->GetForm()->PostBack() === false)
		{
			$this->txtColumns->SetValue(((SMAttributes::AttributeExists("SMGalleryColumns") === true) ? SMAttributes::GetAttribute("SMGalleryColumns") : ""));
			$this->txtRows->SetValue(((SMAttributes::AttributeExists("SMGalleryRows") === true) ? SMAttributes::GetAttribute("SMGalleryRows") : ""));
			$this->txtWidth->SetValue(((SMAttributes::AttributeExists("SMGalleryWidth") === true) ? SMAttributes::GetAttribute("SMGalleryWidth") : ""));
			$this->txtHeight->SetValue(((SMAttributes::AttributeExists("SMGalleryHeight") === true) ? SMAttributes::GetAttribute("SMGalleryHeight") : ""));
			$this->txtPadding->SetValue(((SMAttributes::AttributeExists("SMGalleryPadding") === true) ? SMAttributes::GetAttribute("SMGalleryPadding") : ""));
		}

		$this->cmdSave = new SMLinkButton("SMGallerySave");
		$this->cmdSave->SetTitle($this->lang->GetTranslation("SaveSettings"));
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdSave->PerformedPostBack() === true)
			{
				if (($this->txtColumns->GetValue() !== "" && (is_numeric($this->txtColumns->GetValue()) === false || (int)$this->txtColumns->GetValue() <= 0)) ||
					($this->txtRows->GetValue() !== "" && (is_numeric($this->txtRows->GetValue()) === false || (int)$this->txtRows->GetValue() <= 0)) ||
					($this->txtWidth->GetValue() !== "" && is_numeric($this->txtWidth->GetValue()) === false) ||
					($this->txtHeight->GetValue() !== "" && is_numeric($this->txtHeight->GetValue()) === false) ||
					($this->txtPadding->GetValue() !== "" && is_numeric($this->txtPadding->GetValue()) === false))
				{
					$this->message = $this->lang->GetTranslation("SettingsTypeError");
					return;
				}

				SMAttributes::SetAttribute("SMGalleryColumns", $this->txtColumns->GetValue());
				SMAttributes::SetAttribute("SMGalleryRows", $this->txtRows->GetValue());
				SMAttributes::SetAttribute("SMGalleryWidth", $this->txtWidth->GetValue());
				SMAttributes::SetAttribute("SMGalleryHeight", $this->txtHeight->GetValue());
				SMAttributes::SetAttribute("SMGalleryPadding", $this->txtPadding->GetValue());

				$this->message = $this->lang->GetTranslation("SettingsSaved");
			}
		}
	}

	public function Render()
	{
		$output = "";

		if ($this->message !== "")
			$output .= SMNotify::Render($this->message);

		$output .= "
		<table>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("SettingColumns") . "</td>
				<td style=\"width: 100px\">" . $this->txtColumns->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("SettingRows") . "</td>
				<td style=\"width: 100px\">" . $this->txtRows->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("SettingWidth") . "</td>
				<td style=\"width: 100px\">" . $this->txtWidth->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("SettingHeight") . "</td>
				<td style=\"width: 100px\">" . $this->txtHeight->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">" . $this->lang->GetTranslation("SettingPadding") . "</td>
				<td style=\"width: 100px\">" . $this->txtPadding->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">&nbsp;</td>
				<td style=\"width: 100px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 200px\">&nbsp;</td>
				<td style=\"width: 100px\"><div style=\"text-align: right\">" . $this->cmdSave->Render() . "</div></td>
			</tr>
		</table>
		";

		$fieldset = new SMFieldset("SMGallery");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 300px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Settings"));
		$fieldset->SetPostBackControl($this->cmdSave->GetClientId());

		return $fieldset->Render();
	}
}

?>
