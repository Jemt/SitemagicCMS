<?php

class SMShopFrmShop implements SMIExtensionForm
{
	private $context;
	private $lang;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler($this->context->GetExtensionName());

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title") . " - " . $this->lang->GetTranslation("Products")));

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
		$output = "
		<div id=\"" . $this->context->GetExtensionName() . "Container\"></div>

		<script type=\"text/javascript\">

		JSShop.Initialize(function()
		{
			var p = new JSShop.Presenters.ProductForm();
			p.Render(document.getElementById(\"" . $this->context->GetExtensionName() . "Container\"));
		});

		</script>
		";

		return $output;
	}
}

?>
