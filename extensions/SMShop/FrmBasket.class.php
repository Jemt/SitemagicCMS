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
		SMEnvironment::GetMasterTemplate()->RegisterResource(SMTemplateResource::$StyleSheet, $extPath . "/JSShop/Views/Basket.css?CacheKey=" . SMEnvironment::GetVersion(), true);

		$output = "
		<div id=\"" . $this->context->GetExtensionName() . "BasketContainer\"></div>
		<br>
		<div id=\"" . $this->context->GetExtensionName() . "OrderFormContainer\" style=\"xxxxxxdisplay: none\"></div>

		<script type=\"text/javascript\">

		JSShop.Initialize(function()
		{
			window.SMShop = { BasketForm: null, OrderForm: null };

			var bc = document.getElementById(\"" . $this->context->GetExtensionName() . "BasketContainer\");
			var ofc = document.getElementById(\"" . $this->context->GetExtensionName() . "OrderFormContainer\");

			var o = new JSShop.Presenters.OrderForm();
			o.Render(ofc);

			SMShop.OrderForm = o;

			// Get controls defined by OrderForm presenter
			var chkAltAddress = Fit.Controls.Find('JSShopAlternativeAddress');
			var txtZipCode = Fit.Controls.Find('JSShopZipCode');
			var txtAltZipCode = Fit.Controls.Find('JSShopAltZipCode');
			var txtPromoCode = Fit.Controls.Find('JSShopPromoCode'); // Can be Null if no promotion code has been configured in CostCorrections
			var lstPayment = Fit.Controls.Find('JSShopPaymentMethod'); // Can be Null if payment methods have not been configured
			var cmdContinue = Fit.Controls.Find('JSShopContinue');

			if (cmdContinue === null)
			{
				bc.innerHTML = '" . $this->lang->GetTranslation("BasketEmpty") . "';
				return;
			}

			cmdContinue.Enabled(false);

			// Create basket, set ZipCode and PaymentMethod from OrderForm (may have been restored from session store)
			var b = new JSShop.Presenters.Basket();
			b.ZipCode(((chkAltAddress.Checked() === false) ? txtZipCode.Value() : txtAltZipCode.Value()));

			if (lstPayment !== null && lstPayment.GetSelections().length !== 0)
				b.PaymentMethod(lstPayment.GetSelections()[0].Value);

			b.Render(bc);

			SMShop.BasketForm = b;

			// Disable Continue button on OrderForm while Basket is reloading (when zip code is updated)

			var prevPricing = null;

			b.OnUpdate(function(sender)
			{
				cmdContinue.Enabled(false);
			});
			b.OnUpdated(function(sender)
			{
				if (prevPricing !== null && Fit.Core.IsEqual(prevPricing, b.GetPricing()) === false)
					Fit.Controls.Dialog.Alert('" . $this->lang->GetTranslation("BasketUpdated") . "');

				prevPricing = b.GetPricing();
				cmdContinue.Enabled(true);
			});

			// Refresh Basket when zip code is known - it might be used to calculate shipping expense

			var updateZipCode = function(sender)
			{
				if (chkAltAddress.Checked() === false)
					b.ZipCode(txtZipCode.Value());
				else if (txtAltZipCode.Value() !== '')
					b.ZipCode(txtAltZipCode.Value());
			}

			Fit.Controls.Find('JSShopZipCode').OnBlur(updateZipCode);
			Fit.Controls.Find('JSShopAltZipCode').OnBlur(updateZipCode);
			Fit.Controls.Find('JSShopAlternativeAddress').OnChange(updateZipCode);

			// Refresh Basket when promotion code is changed

			if (txtPromoCode !== null)
			{
				txtPromoCode.OnChange(function(sender)
				{
					b.PromoCode(txtPromoCode.Value());
				});
			}

			// Refresh Basket when payment method is changed

			if (lstPayment !== null)
			{
				lstPayment.OnChange(function(sender)
				{
					if (lstPayment.GetSelections().length === 0)
						return;

					b.PaymentMethod(lstPayment.GetSelections()[0].Value);
				});
			}
		});

		</script>
		";

		return $output;
	}
}

?>
