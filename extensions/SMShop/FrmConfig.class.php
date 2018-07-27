<?php

class SMShopFrmConfig implements SMIExtensionForm
{
	private $context;
	private $lang;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler($this->context->GetExtensionName());

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("ConfigTitle")));

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
		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$StyleSheet, $extPath . "/JSShop/Views/Config/Config.css?CacheKey=" . SMEnvironment::GetVersion(), true);

		$output = "
		<div id=\"" . $this->context->GetExtensionName() . "Config\"></div>

		<script type=\"text/javascript\">

		JSShop.Initialize(function()
		{
			var cfg = new JSShop.Presenters.Config();
			cfg.Render(document.getElementById(\"" . $this->context->GetExtensionName() . "Config\"));
		});

		</script>
		";

		return $output;
	}
}

?>
