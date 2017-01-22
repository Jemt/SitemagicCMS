<?php

// ===========================================================================================

$language = new SMLanguageHandler($this->context->GetExtensionName());

// Insert configuration link in admin menu
$cfg["Menu"]["Title"] = $language->GetTranslation("Title");
$cfg["Menu"]["Target"] = "Admin";

// Protect settings by login
$cfg["Settings"]["LoginRequired"] = "Yes";

if ($render === true) {

// ===========================================================================================

$this->SetIsIntegrated(true);
$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $language->GetTranslation("Title")));

// Create GUI (input fields, checkboxes, gallery picker)

$txtMinHeight = new SMInput("SMImageMontageMinHeight", SMInputType::$Text);
$txtMinHeight->SetAttribute(SMInputAttribute::$Style, "width: 100px");

$txtMaxHeight = new SMInput("SMImageMontageMaxHeight", SMInputType::$Text);
$txtMaxHeight->SetAttribute(SMInputAttribute::$Style, "width: 100px");

$txtMargin = new SMInput("SMImageMontageMargin", SMInputType::$Text);
$txtMargin->SetAttribute(SMInputAttribute::$Style, "width: 100px");

$chkDisplayTitle = new SMInput("SMImageMontageDisplayTitle", SMInputType::$Checkbox);
$chkDisplayImageTitle = new SMInput("SMImageMontageDisplayImageTitle", SMInputType::$Checkbox);
$chkDisplayImageExif = new SMInput("SMImageMontageDisplayImageExif", SMInputType::$Checkbox);
$chkDisplayPicker = new SMInput("SMImageMontageDisplayPicker", SMInputType::$Checkbox);
$chkShuffle = new SMInput("SMImageMontageShuffle", SMInputType::$Checkbox);

$lstSlideShowInterval = new SMOptionList("SMImageMontageSlideShowIntervals");
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval1", "1 " . $language->GetTranslation("Second"), "1000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval2", "2 " . $language->GetTranslation("Seconds"), "2000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval3", "3 " . $language->GetTranslation("Seconds"), "3000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval5", "5 " . $language->GetTranslation("Seconds"), "5000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval7", "7 " . $language->GetTranslation("Seconds"), "7000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval10", "10 " . $language->GetTranslation("Seconds"), "10000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval15", "15 " . $language->GetTranslation("Seconds"), "15000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval20", "20 " . $language->GetTranslation("Seconds"), "20000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval30", "30 " . $language->GetTranslation("Seconds"), "30000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval45", "45 " . $language->GetTranslation("Seconds"), "45000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval60", "1 " . $language->GetTranslation("Minute"), "60000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval120", "2 " . $language->GetTranslation("Minutes"), "120000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval180", "3 " . $language->GetTranslation("Minutes"), "180000"));
$lstSlideShowInterval->AddOption(new SMOptionListItem("SMImageMontageSlideShowInterval300", "5 " . $language->GetTranslation("Minutes"), "300000"));

$cmdSave = new SMLinkButton("SMImageMontageSave");
$cmdSave->SetTitle($language->GetTranslation("Save"));
$cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));

$cmdOpen = new SMLinkButton("SMImageMontageOpen");
$cmdOpen->SetTitle($language->GetTranslation("Open"));
$cmdOpen->SetIcon(SMImageProvider::GetImage(SMImageType::$Right));
$cmdOpen->SetPostBack(false);
$cmdOpen->SetOnclick("window.open('" . $this->getUrl("ImageMontage.extension.php", true) . "', SMRandom.CreateGuid(), '')");

// Save settings if save button was clicked

if ($cmdSave->PerformedPostBack() === true)
{
	if ($txtMinHeight->GetValue() !== "" && is_numeric($txtMinHeight->GetValue()) === true)
		SMAttributes::SetAttribute("SMImageMontageMinHeight", $txtMinHeight->GetValue());
	if ($txtMaxHeight->GetValue() !== "" && is_numeric($txtMaxHeight->GetValue()) === true)
		SMAttributes::SetAttribute("SMImageMontageMaxHeight", $txtMaxHeight->GetValue());
	if ($txtMargin->GetValue() !== "" && is_numeric($txtMargin->GetValue()) === true)
		SMAttributes::SetAttribute("SMImageMontageMargin", $txtMargin->GetValue());

	SMAttributes::SetAttribute("SMImageMontageDisplayTitle", (($chkDisplayTitle->GetChecked() === true) ? "true" : "false"));
	SMAttributes::SetAttribute("SMImageMontageDisplayImageTitle", (($chkDisplayImageTitle->GetChecked() === true) ? "true" : "false"));
	SMAttributes::SetAttribute("SMImageMontageDisplayImageExif", (($chkDisplayImageExif->GetChecked() === true) ? "true" : "false"));
	SMAttributes::SetAttribute("SMImageMontageDisplayPicker", (($chkDisplayPicker->GetChecked() === true) ? "true" : "false"));
	SMAttributes::SetAttribute("SMImageMontageShuffle", (($chkShuffle->GetChecked() === true) ? "true" : "false"));
	SMAttributes::SetAttribute("SMImageMontageSlideShowInterval", $lstSlideShowInterval->GetSelectedValue());
}

// Load settings previously set

$txtMinHeight->SetValue(((SMAttributes::GetAttribute("SMImageMontageMinHeight") !== null) ? SMAttributes::GetAttribute("SMImageMontageMinHeight") : "100"));
$txtMaxHeight->SetValue(((SMAttributes::GetAttribute("SMImageMontageMaxHeight") !== null) ? SMAttributes::GetAttribute("SMImageMontageMaxHeight") : "300"));
$txtMargin->SetValue(((SMAttributes::GetAttribute("SMImageMontageMargin") !== null) ? SMAttributes::GetAttribute("SMImageMontageMargin") : "3"));
$chkDisplayTitle->SetChecked((SMAttributes::GetAttribute("SMImageMontageDisplayTitle") === "true"));
$chkDisplayImageTitle->SetChecked((SMAttributes::GetAttribute("SMImageMontageDisplayImageTitle") === "true"));
$chkDisplayImageExif->SetChecked((SMAttributes::GetAttribute("SMImageMontageDisplayImageExif") === "true"));
$chkDisplayPicker->SetChecked((SMAttributes::GetAttribute("SMImageMontageDisplayPicker") === "true"));
$chkShuffle->SetChecked((SMAttributes::GetAttribute("SMImageMontageShuffle") === "true"));
$lstSlideShowInterval->SetSelectedValue(((SMAttributes::GetAttribute("SMImageMontageSlideShowInterval") !== null) ? SMAttributes::GetAttribute("SMImageMontageSlideShowInterval") : "3000"));

// Output user interface

$output = "
<table>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("MinHeight") . "</td>
		<td style=\"width: 100px\">" . $txtMinHeight->Render() . "</td>
	</tr>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("MaxHeight") . "</td>
		<td style=\"width: 100px\">" . $txtMaxHeight->Render() . "</td>
	</tr>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("Margin") . "</td>
		<td style=\"width: 100px\">" . $txtMargin->Render() . "</td>
	</tr>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("DisplayTitle") . "</td>
		<td style=\"width: 100px\">" . $chkDisplayTitle->Render() . "</td>
	</tr>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("DisplayImageTitle") . "</td>
		<td style=\"width: 100px\">" . $chkDisplayImageTitle->Render() . "</td>
	</tr>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("DisplayImageExif") . "</td>
		<td style=\"width: 100px\">" . $chkDisplayImageExif->Render() . "</td>
	</tr>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("DisplayPicker") . "</td>
		<td style=\"width: 100px\">" . $chkDisplayPicker->Render() . "</td>
	</tr>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("Shuffle") . "</td>
		<td style=\"width: 100px\">" . $chkShuffle->Render() . "</td>
	</tr>
	<tr>
		<td style=\"width: 270px\">" . $language->GetTranslation("SlideShowInterval") . "</td>
		<td style=\"width: 100px\">" . $lstSlideShowInterval->Render() . "</td>
	</tr>
</table>

<br>
<span style=\"float: right\">" . $cmdSave->Render() . " | " . $cmdOpen->Render() . "</span>
<div style=\"clear: both\"></div>
";

$fieldset = new SMFieldset("SMImageMontageSettings");
$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 370px");
$fieldset->SetContent($output);
$fieldset->SetLegend($language->GetTranslation("Settings"));
$fieldset->SetPostBackControl($cmdSave->GetClientId());

echo $fieldset->Render();
echo "<br><span style=\"font-size: 9px\">Based on Automatic Image Montage by <a href=\"javascript:window.open('http://tympanus.net/codrops/author/pbotelho/', 'AutomaticImageMontageCredits', '')\">Pedro Botelho</a></span>";

// ===========================================================================================

}

?>
