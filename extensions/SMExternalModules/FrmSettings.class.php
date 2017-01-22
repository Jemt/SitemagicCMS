<?php

class SMExternalModulesFrmSettings implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $msg;
	private $editMode;

	private $lstModules;
	private $txtName;
	private $txtUrl;
	private $txtWidth;
	private $lstWidthUnit;
	private $txtHeight;
	private $lstHeightUnit;
	private $lstScroll;
	private $lstFrameColor;
	private $chkReloadScrollToTop;
	private $cmdCreate;
	private $cmdClear;
	private $cmdSave;
	private $cmdDelete;
	private $cmdPreview;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMExternalModules");
		$this->msg = "";
		$this->editMode = false;

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->createModuleList();

		$this->txtName = new SMInput("SMExternalModulesName", SMInputType::$Text);
		$this->txtName->SetAttribute(SMInputAttributeText::$Style, "width: 250px");
		$this->txtName->SetAttribute(SMInputAttributeText::$MaxLength, "255");

		$this->txtUrl = new SMInput("SMExternalModulesUrl", SMInputType::$Text);
		$this->txtUrl->SetAttribute(SMInputAttributeText::$Style, "width: 250px");
		$this->txtUrl->SetAttribute(SMInputAttributeText::$MaxLength, "2048");
		$this->txtUrl->SetAttribute(SMInputAttributeText::$OnBlur, "smExternalModulesCleanUrl(this)");
		$this->txtUrl->SetAttribute(SMInputAttributeText::$OnKeyUp, "smExternalModulesEmbedHandler(this.value)");
		if ($this->context->GetForm()->PostBack() === false)
			$this->txtUrl->SetValue("http://");

		$this->txtWidth = new SMInput("SMExternalModulesWidth", SMInputType::$Text);
		$this->txtWidth->SetAttribute(SMInputAttributeText::$Style, "width: 50px");
		$this->txtWidth->SetAttribute(SMInputAttributeText::$MaxLength, "5");
		if ($this->context->GetForm()->PostBack() === false)
			$this->txtWidth->SetValue("600");

		$this->lstWidthUnit = new SMOptionList("SMExternalModulesWidthUnit");
		$this->lstWidthUnit->SetAttribute(SMOptionListAttribute::$Style, "width: 75x");
		$this->lstWidthUnit->AddOption(new SMOptionListItem("SMExternalModulesWidthUnitPixels", $this->lang->GetTranslation("Pixels"), SMExternalModulesUnit::$Pixels));
		$this->lstWidthUnit->AddOption(new SMOptionListItem("SMExternalModulesWidthUnitPercent", $this->lang->GetTranslation("Percent"), SMExternalModulesUnit::$Percent));

		$this->txtHeight = new SMInput("SMExternalModulesHeight", SMInputType::$Text);
		$this->txtHeight->SetAttribute(SMInputAttributeText::$Style, "width: 50px");
		$this->txtHeight->SetAttribute(SMInputAttributeText::$MaxLength, "5");
		if ($this->context->GetForm()->PostBack() === false)
			$this->txtHeight->SetValue("400");

		$this->lstHeightUnit = new SMOptionList("SMExternalModulesHeightUnit");
		$this->lstHeightUnit->SetAttribute(SMOptionListAttribute::$Style, "width: 75x");
		$this->lstHeightUnit->AddOption(new SMOptionListItem("SMExternalModulesHeightUnitPixels", $this->lang->GetTranslation("Pixels"), SMExternalModulesUnit::$Pixels));
		//$this->lstHeightUnit->AddOption(new SMOptionListItem("SMExternalModulesHeightUnitPercent", $this->lang->GetTranslation("Percent"), SMExternalModulesUnit::$Percent));

		$this->lstScroll = new SMOptionList("SMExternalModulesScroll");
		$this->lstScroll->AddOption(new SMOptionListItem("SMExternalModulesScrollNo", $this->lang->GetTranslation("ScrollNo"), SMExternalModulesScroll::$No));
		$this->lstScroll->AddOption(new SMOptionListItem("SMExternalModulesScrollYes", $this->lang->GetTranslation("ScrollYes"), SMExternalModulesScroll::$Yes));
		$this->lstScroll->AddOption(new SMOptionListItem("SMExternalModulesScrollAuto", $this->lang->GetTranslation("ScrollAuto"), SMExternalModulesScroll::$Auto));

		$colors = array("Black", "White", "Gray", "Silver", "Maroon", "Red", "Purple", "Fuchsia", "Green", "Lime", "Olive", "Yellow", "Navy", "Blue", "Teal", "Aqua");

		$this->lstFrameColor = new SMOptionList("SMExternalModulesFrameColor");
		$this->lstFrameColor->AddOption(new SMOptionListItem("SMExternalModulesFrameColorNone", $this->lang->GetTranslation("NoFrame"), ""));

		foreach ($colors as $color)
			$this->lstFrameColor->AddOption(new SMOptionListItem("SMExternalModulesFrameColor" . $color, $this->lang->GetTranslation("FrameColor" . $color), $color));

		$this->chkReloadScrollToTop = new SMInput("SMExternalModulesScrollToTop", SMInputType::$Checkbox);

		$this->cmdCreate = new SMLinkButton("SMExternalModulesCreate");
		$this->cmdCreate->SetTitle($this->lang->GetTranslation("Create"));
		$this->cmdCreate->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));

		$this->cmdClear = new SMLinkButton("SMExternalModulesClear");
		$this->cmdClear->SetTitle($this->lang->GetTranslation("Clear"));
		$this->cmdClear->SetIcon(SMImageProvider::GetImage(SMImageType::$Clear));

		$this->cmdSave = new SMLinkButton("SMExternalModulesSave");
		$this->cmdSave->SetTitle($this->lang->GetTranslation("Save"));
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));

		$this->cmdDelete = new SMLinkButton("SMExternalModulesDelete");
		$this->cmdDelete->SetTitle($this->lang->GetTranslation("Delete"));
		$this->cmdDelete->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDelete->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteWarning", true) . "') === false) { return false; }");

		$this->cmdPreview = new SMLinkButton("SMExternalModulesPreview");
		$this->cmdPreview->SetTitle($this->lang->GetTranslation("Preview"));
		$this->cmdPreview->SetIcon(SMImageProvider::GetImage(SMImageType::$Display));
		$this->cmdPreview->SetOnclick("smExternalModulesPreview(); return false;");
	}

	private function createModuleList()
	{
		$modules = SMExternalModulesLoader::GetModules();

		$this->lstModules = new SMOptionList("SMExternalModulesList");
		$this->lstModules->SetAutoPostBack(true);
		$this->lstModules->AddOption(new SMOptionListItem("SMExternalModulesListEmpty", "", ""));

		foreach ($modules as $module)
			$this->lstModules->AddOption(new SMOptionListItem("SMexternalModulesModule" . $module->GetGuid(), $module->GetName(), $module->GetGuid()));
	}

	private function clearForm()
	{
		$this->lstModules->SetSelectedValue("");
		$this->txtName->SetValue("");
		$this->txtUrl->SetValue("http://");
		$this->txtWidth->SetValue("600");
		$this->lstWidthUnit->SetSelectedValue(SMExternalModulesUnit::$Pixels);
		$this->txtHeight->SetValue("400");
		$this->lstHeightUnit->SetSelectedValue(SMExternalModulesUnit::$Pixels);
		$this->lstScroll->SetSelectedValue(SMExternalModulesScroll::$No);
		$this->lstFrameColor->SetSelectedValue("");
		$this->chkReloadScrollToTop->SetChecked(false);
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->lstModules->PerformedPostBack() === true)
			{
				if ($this->lstModules->GetSelectedValue() === "")
				{
					$this->clearForm();
					return;
				}

				if ($this->lstModules->GetSelectedValue() === null) // Most likely removed in another session
				{
					$this->msg = $this->lang->GetTranslation("ModuleNotFound");
					return;
				}

				$this->editMode = true;

				$module = SMExternalModulesModule::GetPersistentByGuid($this->lstModules->GetSelectedValue());

				$this->txtName->SetValue($module->GetName());
				$this->txtUrl->SetValue($module->GetUrl());
				$this->txtWidth->SetValue((string)$module->GetWidth());
				$this->lstWidthUnit->SetSelectedValue($module->GetWidthUnit());
				$this->txtHeight->SetValue((string)$module->GetHeight());
				$this->lstHeightUnit->SetSelectedValue($module->GetHeightUnit());
				$this->lstScroll->SetSelectedValue($module->GetScroll());
				$this->lstFrameColor->SetSelectedValue($module->GetFrameColor());
				$this->chkReloadScrollToTop->SetChecked($module->GetReloadToTop());
			}
			else if ($this->cmdCreate->PerformedPostBack() === true)
			{
				if ($this->txtName->GetValue() === "" || $this->txtUrl->GetValue() === "" || $this->txtWidth->GetValue() === "" || $this->txtHeight->GetValue() === "")
				{
					$this->msg = $this->lang->GetTranslation("MissingFields");
					return;
				}

				if (SMExternalModulesModule::GetPersistentByName($this->txtName->GetValue()) !== null)
				{
					$this->msg = $this->lang->GetTranslation("AlreadyExists");
					return;
				}

				if (is_numeric($this->txtWidth->GetValue()) === false || is_numeric($this->txtHeight->GetValue()) === false)
				{
					$this->msg = $this->lang->GetTranslation("InvalidWidthHeight");
					return;
				}

				$module = new SMExternalModulesModule(SMRandom::CreateGuid(), $this->txtName->GetValue(), $this->txtUrl->GetValue(), (int)$this->txtWidth->GetValue(), $this->lstWidthUnit->GetSelectedValue(), (int)$this->txtHeight->GetValue(), $this->lstHeightUnit->GetSelectedValue(), $this->lstScroll->GetSelectedValue(), $this->chkReloadScrollToTop->GetChecked(), $this->lstFrameColor->GetSelectedValue());

				$result = $module->CommitPersistent();

				if ($result === false)
				{
					$this->msg = $this->lang->GetTranslation("CreationError");
					return;
				}

				$this->createModuleList();
				$this->clearForm();

				$this->msg = $this->lang->GetTranslation("ModuleCreated");
			}
			else if ($this->cmdSave->PerformedPostBack() === true)
			{
				if ($this->txtName->GetValue() === "" || $this->txtUrl->GetValue() === "" || $this->txtWidth->GetValue() === "" || $this->txtHeight->GetValue() === "")
				{
					$this->msg = $this->lang->GetTranslation("MissingFields");
					return;
				}

				if (is_numeric($this->txtWidth->GetValue()) === false || is_numeric($this->txtHeight->GetValue()) === false)
				{
					$this->msg = $this->lang->GetTranslation("InvalidWidthHeight");
					return;
				}

				if ($this->lstModules->GetSelectedValue() === null) // Most likely removed in another session
				{
					$this->msg = $this->lang->GetTranslation("ModuleNotFound");
					return;
				}

				$module = SMExternalModulesModule::GetPersistentByGuid($this->lstModules->GetSelectedValue());

				$module->SetName($this->txtName->GetValue());
				$module->SetUrl($this->txtUrl->GetValue());
				$module->SetWidth((int)$this->txtWidth->GetValue());
				$module->SetWidthUnit($this->lstWidthUnit->GetSelectedValue());
				$module->SetHeight((int)$this->txtHeight->GetValue());
				$module->SetHeightUnit($this->lstHeightUnit->GetSelectedValue());
				$module->SetScroll($this->lstScroll->GetSelectedValue());
				$module->SetFrameColor($this->lstFrameColor->GetSelectedValue());
				$module->SetReloadToTop($this->chkReloadScrollToTop->GetChecked());

				$result = $module->CommitPersistent();

				if ($result === false)
				{
					$this->msg = $this->lang->GetTranslation("UpdateError");
					return;
				}

				$this->createModuleList();
				$this->clearForm();

				$this->msg = $this->lang->GetTranslation("SettingsSaved");
			}
			else if ($this->cmdClear->PerformedPostBack() === true)
			{
				$this->clearForm();
			}
			else if ($this->cmdDelete->PerformedPostBack() === true)
			{
				$module = SMExternalModulesModule::GetPersistentByGuid($this->lstModules->GetSelectedValue());

				if ($module === null)
				{
					$this->msg = $this->lang->GetTranslation("ModuleNotFound");
					return;
				}

				$result = $module->DeletePersistent();

				if ($result === false)
				{
					$this->msg = $this->lang->GetTranslation("DeleteError");
					return;
				}

				$this->createModuleList();
				$this->clearForm();

				$this->msg = $this->lang->GetTranslation("ModuleDeleted");
			}
		}
	}

	public function Render()
	{
		$output = "";

		if ($this->msg !== "")
			$output .= SMNotify::Render($this->msg);

		$output .= "
		<table>
			<tr>
				<td style=\"width: 225px\">" . $this->lang->GetTranslation("Modules") . "</td>
				<td style=\"width: 400px\">" . $this->lstModules->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">&nbsp;</td>
				<td style=\"width: 400px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">" . $this->lang->GetTranslation("Name") . "</td>
				<td style=\"width: 400px\">" . $this->txtName->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">" . $this->lang->GetTranslation("Url") . "</td>
				<td style=\"width: 400px\">" . $this->txtUrl->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">" . $this->lang->GetTranslation("Width") . "</td>
				<td style=\"width: 400px\">" . $this->txtWidth->Render() . " " . $this->lstWidthUnit->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">" . $this->lang->GetTranslation("Height") . "</td>
				<td style=\"width: 400px\">" . $this->txtHeight->Render() . " " . $this->lstHeightUnit->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">" . $this->lang->GetTranslation("Scroll") . "</td>
				<td style=\"width: 400px\">" . $this->lstScroll->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">" . $this->lang->GetTranslation("ShowFrame") . "</td>
				<td style=\"width: 400px\">" . $this->lstFrameColor->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">" . $this->lang->GetTranslation("ReloadToTop") . "</td>
				<td style=\"width: 400px\">" . $this->chkReloadScrollToTop->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">&nbsp;</td>
				<td style=\"width: 400px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 225px\">&nbsp;</td>
				<td style=\"width: 400px\">" . (($this->editMode === true) ? $this->cmdClear->Render() . " " . $this->cmdSave->Render() . " " . $this->cmdDelete->Render() : $this->cmdCreate->Render()) . " " . $this->cmdPreview->Render() . "</td>
			</tr>
		</table>
		";

		$fieldset = new SMFieldset("SMExternalModules");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 625px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Settings"));
		$fieldset->SetPostBackControl((($this->editMode === true) ? $this->cmdSave->GetClientId() : $this->cmdCreate->GetClientId()));

		$output = $fieldset->Render();

		// Preview functionality (client side logic)

		$output .= "
		<div id=\"SMExternalModulesPreviewContainer\" style=\"display: none\">

			<br><br>
			<h1>" . $this->lang->GetTranslation("Preview") . "</h1>

			<div id=\"SMExternalModulesPreview\"></div>

		</div>";

		$output .= "
		<script type=\"text/javascript\">

		function smExternalModulesCleanUrl(txtUrl)
		{
			txtUrl.value = SMStringUtilities.ReplaceAll(txtUrl.value, \"&amp;\", \"&\");
		}

		function smExternalModulesEmbedHandler(str)
		{
			if (str.toLowerCase().indexOf(\"<\" + \"iframe\") > -1 && str.toLowerCase().indexOf(\"</\" + \"iframe>\") > -1)
			{
				// Handling iFrame

				var match = null;

				// Find iFrame width

				// Match width attribute (example: width='100')
				match = str.match(/width=[\"'](\\d+)[\"']/i); // If matched: 0 = entire match, 1 = matched capture group (attribute value)
				var width = ((match !== null) ? match[1] : -1);

				if (width === -1)
				{
					// Match width in style attribute (example: width: 100px)
					match = str.match(/width\\s*:\s*(\\d+)px/i); // If matched: 0 = entire match, 1 = matched capture group (attribute value)
					var width = ((match !== null) ? match[1] : -1);
				}

				if (width !== -1)
				{
					document.getElementById(\"" . $this->txtWidth->GetClientId() . "\").value = width;
					document.getElementById(\"" . $this->lstWidthUnit->GetClientId() . "\").value = '" . SMExternalModulesUnit::$Pixels . "';
				}

				// Find iFrame height

				// Match height attribute (example: height='100')
				match = str.match(/height=[\"'](\\d+)[\"']/i); // If matched: 0 = entire match, 1 = matched capture group (attribute value)
				var height = ((match !== null) ? match[1] : -1);

				if (height === -1)
				{
					// Match height in style attribute (example: height: 100px)
					match = str.match(/height\\s*:\s*(\\d+)px/); // If matched: 0 = entire match, 1 = matched capture group (attribute value)
					var height = ((match !== null) ? match[1] : -1);
				}

				if (height !== -1)
				{
					document.getElementById(\"" . $this->txtHeight->GetClientId() . "\").value = height;
					document.getElementById(\"" . $this->lstHeightUnit->GetClientId() . "\").value = '" . SMExternalModulesUnit::$Pixels . "';
				}

				// Find iFrame source

				// Match all characters in src attribute
				match = str.match(/src=[\"'](.+?)[\"']/); // If matched: 0 = entire match, 1 = matched capture group (attribute value)
				var src = ((match !== null) ? match[1] : \"\");

				if (src !== \"\")
				{
					document.getElementById(\"" . $this->txtUrl->GetClientId() . "\").value = src;
					smExternalModulesPreview();
				}
			}
			else if (str.toLowerCase().indexOf(\"youtube.com/watch?v\") > -1)
			{
				// Handle YouTube URL

				var match = str.match(/youtube.com\\/watch\\?v=([\\w-]+)/i); // If matched: 0 = entire match, 1 = matched capture group (Video ID)

				if (match !== null)
				{
					document.getElementById(\"" . $this->txtUrl->GetClientId() . "\").value = \"//www.youtube.com/embed/\" + match[1];
					smExternalModulesPreview();
				}
			}
		}

		document.getElementById(\"" . $this->txtUrl->GetClientId() . "\").onpaste = function() { var inp = this; setTimeout(function() { smExternalModulesEmbedHandler(inp.value); }, 0); };

		function smExternalModulesPreview()
		{
			// Access preview containers

			var container = document.getElementById(\"SMExternalModulesPreviewContainer\");
			var preview = document.getElementById(\"SMExternalModulesPreview\");

			// Access form elements

			var txtName = document.getElementById(\"" . $this->txtName->GetClientId() . "\");
			var txtUrl = document.getElementById(\"" . $this->txtUrl->GetClientId() . "\");
			var txtWidth = document.getElementById(\"" . $this->txtWidth->GetClientId() . "\");
			var lstWidthUnit = document.getElementById(\"" . $this->lstWidthUnit->GetClientId() . "\");
			var txtHeight = document.getElementById(\"" . $this->txtHeight->GetClientId() . "\");
			var lstHeightUnit = document.getElementById(\"" . $this->lstHeightUnit->GetClientId() . "\");
			var lstScroll = document.getElementById(\"" . $this->lstScroll->GetClientId() . "\");
			var lstFrameColor = document.getElementById(\"" . $this->lstFrameColor->GetClientId() . "\");
			var chkScrollTop = document.getElementById(\"" . $this->chkReloadScrollToTop->GetClientId() . "\");

			if (txtUrl.value === \"\" || txtUrl.value === \"http://\") return;

			// Calculate height and width units (px vs %)

			var widthUnitValue = lstWidthUnit.options[lstWidthUnit.selectedIndex].value;
			var widthUnit = ((widthUnitValue === \"" . SMExternalModulesUnit::$Percent . "\") ? \"%\" : \"\");

			var heightUnitValue = lstHeightUnit.options[lstHeightUnit.selectedIndex].value;
			var heightUnit = ((heightUnitValue === \"" . SMExternalModulesUnit::$Percent . "\") ? \"%\" : \"\");

			// Construct object and display

			// Constructing iFrame as text, since the DOM method doesn't work properly on IE.
			// Onload event can only be registered using the attach method, border style can only
			// be applied using iframe.style.border (doesn't work in Firefox), and frameborder
			// must be set using setAttribute('frameBorder', 'x') - notice upper cased B. There
			// are just too many odd bugs.
			// This is a simple solution that just works - no need to write browser specific code.
			// The allowTransparency attribute it used by IE to make the iFrame adapt the
			// background color of the website hosting the iFrame.
			var module = \"<\" + \"iframe src='\" + txtUrl.value + \"' width='\" + txtWidth.value + widthUnit + \"' height='\" + txtHeight.value + heightUnit + \"' scrolling='\" + lstScroll.options[lstScroll.selectedIndex].value.toLowerCase() + \"' frameBorder='0' style='\" + ((lstFrameColor.options[lstFrameColor.selectedIndex].value !== \"\") ? \"border: 1px solid \" + lstFrameColor.options[lstFrameColor.selectedIndex].value : \"\") + \"' onload='\" + ((chkScrollTop.checked === true) ? \"window.scrollTo(0, 0)\" : \"\") + \"' allowTransparency='true'></\" + \"iframe>\";

			preview.innerHTML = module;

			container.style.display = \"block\";
		}

		</script>
		";

		return $output;;
	}
}

?>
