<?php

class SMShopFrmAdmin implements SMIExtensionForm
{
	private $context;
	private $lang;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler($this->context->GetExtensionName());

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
		}
	}

	public function Render()
	{
		$extPath = SMExtensionManager::GetExtensionPath($this->context->GetExtensionName());

		$output = "
		<div id=\"" . $this->context->GetExtensionName() . "Administration\"></div>

		<script type=\"text/javascript\">

		JSShop.Initialize(function()
		{
			var ol = new JSShop.Presenters.OrderList();
			ol.Render(document.getElementById(\"" . $this->context->GetExtensionName() . "Administration\"));
		});

		</script>
		";

		return $output;
	}
}

?>
