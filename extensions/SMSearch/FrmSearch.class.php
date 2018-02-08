<?php

class SMSearchFrmSearch implements SMIExtensionForm
{
	private $context;
	private $instanceId;
	private $name;
	private $lang;

	private $txtValue;
	private $cmdSubmit;

	public function __construct(SMContext $context, $instanceId, $arg)
	{
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "arg", $arg, SMTypeCheckType::$String);

		$this->context = $context; // Most likely NOT SMSearch context - this form is used as a ContentPageExtension, so it most often runs under SMPages
		$this->instanceId = $instanceId;
		$this->name = "SMSearch";
		$this->lang = new SMLanguageHandler($this->name);

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$search = SMEnvironment::GetQueryValue($this->name . "Value");

		$this->txtValue = new SMInput($this->name . "Value" . $this->instanceId, SMInputType::$Text);
		$this->txtValue->SetAttribute(SMInputAttributeText::$Style, "width: 150px");

		if ($this->context->GetForm()->PostBack() === false && $search !== null)
			$this->txtValue->SetValue(str_replace("&amp;", "&", htmlspecialchars($search))); // Notice that htmlspecialchars(..) replaces & with &amp; which breaks encoded characters (e.g. Euro Symbol => &#8364;) - restored here (&amp; => &)

		$this->cmdSubmit = new SMLinkButton($this->name . "Submit" . $this->instanceId);
		if (SMEnvironment::GetVersion() >= 20160123) // Not pretty, but this allows for extension to be installed on older versions of Sitemagic
			$this->cmdSubmit->SetFontIcon("search");
		else
			$this->cmdSubmit->SetIcon(SMImageProvider::GetImage(SMImageType::$Search));
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdSubmit->PerformedPostBack() === true)
			{
				$args = new SMKeyValueCollection();
				$args[$this->name . "Value"] = urlencode($this->txtValue->GetValue());

				SMExtensionManager::ExecuteExtension($this->name, $args);
			}
		}
	}

	public function Render()
	{
		$output = $this->txtValue->Render() . " " . $this->cmdSubmit->Render();

		$fieldset = new SMFieldset($this->name . $this->instanceId);
		$fieldset->SetContent($output);
		$fieldset->SetDisplayFrame(false);
		$fieldset->SetPostBackControl($this->cmdSubmit->GetClientId());

		return $fieldset->Render();
	}
}

?>
