<?php

class SMGoogleAnalyticsSettings implements SMIExtensionForm
{
	private $context;
	private $lang;

	private $txtTrackerId;
	private $lstReportMode;
	private $cmdSave;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMGoogleAnalytics");

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtTrackerId = new SMInput("SMGoogleAnalyticsTrackerId", SMInputType::$Text);
		$this->txtTrackerId->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtTrackerId->SetAttribute(SMInputAttributeText::$MaxLength, "250");

		if ($this->context->GetForm()->PostBack() === false && SMAttributes::AttributeExists("SMGoogleAnalyticsTrackerId") === true)
			$this->txtTrackerId->SetValue(SMAttributes::GetAttribute("SMGoogleAnalyticsTrackerId"));

		$this->lstReportMode = new SMOptionList("SMGoogleAnalyticsReportMode");
		$this->lstReportMode->AddOption(new SMOptionListItem("SMGoogleAnalyticsReportModeContentPages", $this->lang->GetTranslation("ReportModeContentPages"), "ContentPages"));
		$this->lstReportMode->AddOption(new SMOptionListItem("SMGoogleAnalyticsReportModeEverything", $this->lang->GetTranslation("ReportModeEverything"), "Everything"));
		$this->lstReportMode->AddOption(new SMOptionListItem("SMGoogleAnalyticsReportModeNothing", $this->lang->GetTranslation("ReportModeNothing"), "Nothing"));

		if ($this->context->GetForm()->PostBack() === false)
		{
			$value = SMAttributes::GetAttribute("SMGoogleAnalyticsReportMode");

			if ($value === null)
				$this->lstReportMode->SetSelectedValue("ContentPages");
			else
				$this->lstReportMode->SetSelectedValue($value);
		}

		$this->cmdSave = new SMLinkButton("SMGoogleAnalyticsSave");
		$this->cmdSave->SetTitle($this->lang->GetTranslation("Save"));
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdSave->PerformedPostBack() === true)
				$this->saveSettings();
		}
	}

	private function saveSettings()
	{
		$value = $this->txtTrackerId->GetValue();

		if ($value !== "")
			SMAttributes::SetAttribute("SMGoogleAnalyticsTrackerId", $value);
		else
			SMAttributes::RemoveAttribute("SMGoogleAnalyticsTrackerId");

		SMAttributes::SetAttribute("SMGoogleAnalyticsReportMode", $this->lstReportMode->GetSelectedValue());
	}

	public function Render()
	{
		$output = "";

		if ($this->cmdSave->PerformedPostBack() === true)
			$output .= SMNotify::Render($this->lang->GetTranslation("SettingsSaved"));

		$output .= "
		<table>
			<tr>
				<td style=\"width: 150px\">" . $this->lang->GetTranslation("TrackerId") . "</td>
				<td style=\"width: 150px\">" . $this->txtTrackerId->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">" . $this->lang->GetTranslation("Report") . "</td>
				<td style=\"width: 150px\">" . $this->lstReportMode->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">&nbsp;</td>
				<td style=\"width: 150px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 150px\">&nbsp;</td>
				<td style=\"width: 150px\"><div style=\"text-align: right\">" . $this->cmdSave->Render() . "</div></td>
			</tr>
		</table>
		";

		$fieldset = new SMFieldset("SMGoogleAnalytics");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 300px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Settings"));
		$fieldset->SetPostBackControl($this->cmdSave->GetClientId());

		return $fieldset->Render();
	}
}

?>
