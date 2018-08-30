<?php

class SMShopFrmBasket implements SMIExtensionForm
{
	private $context;
	private $lang;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler($this->context->GetExtensionName());

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Basket")));

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
		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$StyleSheet, $extPath . "/JSShop/Views/Basket/Basket.css?CacheKey=" . SMEnvironment::GetVersion(), true);
		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$StyleSheet, $extPath . "/JSShop/Views/OrderForm/OrderForm.css?CacheKey=" . SMEnvironment::GetVersion(), true);

		$output = "
		<div id=\"" . $this->context->GetExtensionName() . "BasketContainer\"></div>
		<br>
		<div id=\"" . $this->context->GetExtensionName() . "OrderFormContainer\" style=\"xxxxxxdisplay: none\"></div>

		<script type=\"text/javascript\">
		JSShop.Initialize(function()
		{
			window.SMShop = { BasketForm: null, OrderForm: null }; // Allow Sitemagic template enhancements to access OrderForm and Basket to e.g. set CustData

			var bc = document.getElementById(\"" . $this->context->GetExtensionName() . "BasketContainer\");
			var ofc = document.getElementById(\"" . $this->context->GetExtensionName() . "OrderFormContainer\");

			if (JSShop.Models.Basket.GetItems().length === 0)
			{
				bc.innerHTML = '" . $this->lang->GetTranslation("BasketEmpty") . "';
				return;
			}

			var o = new JSShop.Presenters.OrderForm();
			var b = new JSShop.Presenters.Basket();

			o.BindBasket(b);

			o.Render(ofc);
			b.Render(bc);

			SMShop.OrderForm = o;
			SMShop.BasketForm = b;
		});
		</script>
		";

		return $output;
	}
}

?>
