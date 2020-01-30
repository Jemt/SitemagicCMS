if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.Basket = function()
{
	Fit.Core.Extend(this, JSShop.Presenters.Base).Apply();

	var me = this;

	var dom = null;
	var tpl = null;
	var basket = JSShop.Models.Basket;
	var lang = JSShop.Language.Translations;

	var zipCode = "";
	var paymentMethod = "";
	var promoCode = "";
	var custData1 = "";
	var custData2 = "";
	var custData3 = "";
	var onUpdateHandlers = [];
	var onUpdatedHandlers = [];

	var result = { Price: -1, Vat: -1 };

	function init(cb)
	{
		loadView(function()
		{
			populateView();
		});
	}

	function loadView(cb)
	{
		Fit.Validation.ExpectFunction(cb);

		dom = document.createElement("div");

		// Load CSS

		if (document.querySelector("link[href*='/Views/Basket/Basket.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
		{
			Fit.Browser.Log("Lazy loading Basket.css");
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/Basket/Basket.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));
		}

		// Load view

		tpl = new Fit.Template(true);
		tpl.AllowUnsafeContent(true);
		tpl.LoadUrl(JSShop.GetPath() + "/Views/Basket/Basket.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"), function(sender, html)
		{
			// Populate header titles

			tpl.Content.HeaderProduct = lang.Basket.Product;
			tpl.Content.HeaderUnitPrice = lang.Basket.UnitPrice;
			tpl.Content.HeaderUnits = lang.Basket.Units;
			tpl.Content.HeaderDiscount = lang.Basket.Discount;
			tpl.Content.HeaderPrice = lang.Basket.Price;
			tpl.Content.HeaderTotalVat = lang.Basket.TotalVat;
			tpl.Content.HeaderTotalPrice = lang.Basket.TotalPrice;

			cb();
		});

		tpl.Render(dom);
	}

	function populateView() // May be called multiple times to update basket
	{
		if (tpl.Content === null)
		{
			// Skip, external code tried to update basket while still loading,
			// e.g. by calling ZipCode(..), PaymentMethod(..), Update(), or something
			// else that calls this function. It is safe to just ignore it (return out)
			// as the most recent data will be loaded once the template is done loading.
			return;
		}

		// Get items from basket

		var items = basket.GetItems();

		// Fire OnUpdate

		Fit.Array.ForEach(onUpdateHandlers, function(handler) // No handlers set when instance of Basket is created - handlers fire when init() is invoked later when interacting with UI
		{
			handler(me);
		});

		// Load CSS

		if (document.querySelector("link[href*='/Views/Basket/Basket.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/Basket/Basket.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		// Check basket

		if (items.length === 0)
		{
			dom.innerHTML = lang.Basket.BasketEmpty;
			return;
		}

		tpl.Content.ProductEntries.Clear();

		// Load view and data

		var totalVat = 0.0;
		var totalPrice = 0.0;
		var totalDiscount = 0.0;
		var totalWeight = 0.0;

		// Load product details

		var itemCount = items.length;
		var currency = null;
		var currencyError = false;
		var weightUnit = null;
		var weightUnitError = false;
		var commonVatFactor = -99999.99;
		var identicalVat = true;

		Fit.Array.ForEach(items, function(item)
		{
			item.Product = new JSShop.Models.Product(item.ProductId);
			item.View = tpl.Content.ProductEntries.AddItem();

			var product = item.Product;

			product.Retrieve(function(req, model)
			{
				itemCount--;

				if (itemCount === 0) // All products loaded
				{
					// Populate template

					var productEntry = null;

					Fit.Array.ForEach(items, function(item)
					{
						// Currency and weight unit validation

						currency = ((currency !== null) ? currency : item.Product.Currency());

						if (item.Product.Currency() !== currency)
							currencyError = true;

						weightUnit = ((weightUnit !== null) ? weightUnit : item.Product.WeightUnit());

						if (item.Product.WeightUnit() !== weightUnit)
							weightUnitError = true;

						// Add to template

						productEntry = item.View;

						var discountExclVat = Fit.Math.Round(item.Product.CalculateDiscount(item.Units), 2);
						var pricing = JSShop.CalculatePricing(item.Product.Price(), item.Units, item.Product.Vat(), discountExclVat);

						productEntry.Title = item.Product.Title();
						productEntry.UnitPrice = Fit.Math.Format(pricing.UnitPriceInclVat, 2, lang.Locale.DecimalSeparator);
						productEntry.DiscountMessage = ((pricing.DiscountInclVat > 0 || pricing.DiscountInclVat < 0) ? item.Product.CalculateDiscountMessage(item.Units) : "");
						productEntry.Discount = ((pricing.DiscountInclVat > 0 || pricing.DiscountInclVat < 0) ? Fit.Math.Format(pricing.DiscountInclVat * -1, 2, lang.Locale.DecimalSeparator) : "");
						productEntry.Units = null; // Set to button further down
						productEntry.Currency = item.Product.Currency();
						productEntry.Price = Fit.Math.Format(pricing.TotalInclVat, 2, lang.Locale.DecimalSeparator);

						// Calculate totals

						totalVat = Fit.Math.Round(totalVat + pricing.TotalVat, 2);
						totalPrice = Fit.Math.Round(totalPrice + pricing.TotalInclVat, 2);
						totalDiscount = Fit.Math.Round(totalDiscount + pricing.DiscountInclVat, 2);
						totalWeight = Fit.Math.Round(totalWeight + (item.Units * item.Product.Weight()), 2);

						if (commonVatFactor === -99999.99)
							commonVatFactor = pricing.VatFactor;

						if (commonVatFactor !== pricing.VatFactor)
							identicalVat = false;
					});

					// Handle cost corrections (shipping expense, order discount, credit card fee, etc)

					var costCorrections =
					[
						{
							Expression: JSShop.Settings.CostCorrection1 ? JSShop.Settings.CostCorrection1 : "",
							Vat: JSShop.Settings.CostCorrectionVat1 ? JSShop.Settings.CostCorrectionVat1 : "",
							Msg: JSShop.Settings.CostCorrectionMessage1 ? JSShop.Settings.CostCorrectionMessage1 : "",
							CostCorrectionExVat: 0,
							CostCorrectionVat: 0
						},
						{
							Expression: JSShop.Settings.CostCorrection2 ? JSShop.Settings.CostCorrection2 : "",
							Vat: JSShop.Settings.CostCorrectionVat2 ? JSShop.Settings.CostCorrectionVat2 : "",
							Msg: JSShop.Settings.CostCorrectionMessage2 ? JSShop.Settings.CostCorrectionMessage2 : "",
							CostCorrectionExVat: 0,
							CostCorrectionVat: 0
						},
						{
							Expression: JSShop.Settings.CostCorrection3 ? JSShop.Settings.CostCorrection3 : "",
							Vat: JSShop.Settings.CostCorrectionVat3 ? JSShop.Settings.CostCorrectionVat3 : "",
							Msg: JSShop.Settings.CostCorrectionMessage3 ? JSShop.Settings.CostCorrectionMessage3 : "",
							CostCorrectionExVat: 0,
							CostCorrectionVat: 0
						}
					];

					Fit.Array.ForEach(costCorrections, function(cc)
					{
						if (!cc.Expression)
							return;

						var correctionExclVat = JSShop.Models.Order.CalculateExpression(totalPrice - totalVat, totalVat, currency, totalWeight, weightUnit, zipCode, paymentMethod, promoCode, custData1, custData2, custData3, cc.Expression, "number");
						var correctionInclVat = 0;

						if (correctionExclVat > 0 || correctionExclVat < 0)
						{
							var correctionVat = 0;

							if (cc.Vat)
								correctionVat = JSShop.Models.Order.CalculateExpression(totalPrice - totalVat, totalVat, currency, totalWeight, weightUnit, zipCode, paymentMethod, promoCode, custData1, custData2, custData3, cc.Vat, "number");

							var costCorrectionResult = JSShop.CalculatePricing(correctionExclVat, 1, correctionVat, 0);
							correctionExclVat = costCorrectionResult.TotalExclVat;
							correctionInclVat = costCorrectionResult.TotalInclVat;

							var correctionMessage = "";

							if (cc.Msg)
								correctionMessage = JSShop.Models.Order.CalculateExpression(totalPrice - totalVat, totalVat, currency, totalWeight, weightUnit, zipCode, paymentMethod, promoCode, custData1, custData2, custData3, cc.Msg, "string");

							productEntry = tpl.Content.ProductEntries.AddItem();
							productEntry.Title = correctionMessage;
							productEntry.UnitPrice = "&nbsp;";
							productEntry.DiscountMessage = "";
							productEntry.Discount = "";
							productEntry.Units = "&nbsp;";
							productEntry.Currency = currency;
							productEntry.Price = Fit.Math.Format(correctionInclVat, 2, lang.Locale.DecimalSeparator);

							// Results are added to totals later, to prevent CostCorrections from affecting each other
							cc.CostCorrectionExVat = correctionExclVat;
							cc.CostCorrectionVat = (correctionInclVat - correctionExclVat);

							if (commonVatFactor !== costCorrectionResult.VatFactor)
								identicalVat = false;
						}
					});

					// Update result

					Fit.Array.ForEach(costCorrections, function(cc)
					{
						totalVat += cc.CostCorrectionVat;
						totalPrice += (cc.CostCorrectionExVat + cc.CostCorrectionVat);
					});

					if (identicalVat === true)
					{
						totalVat = Fit.Math.Round(totalPrice - (totalPrice / commonVatFactor), 2);
					}

					result = // All pricing ex. VAT
					{
						Price: totalPrice - totalVat,
						Vat: totalVat
					};

					// Add totals and update view

					tpl.Content.Currency = items[0].Product.Currency();
					tpl.Content.TotalVat = Fit.Math.Format(totalVat, 2, lang.Locale.DecimalSeparator);
					tpl.Content.TotalPrice = Fit.Math.Format(totalPrice, 2, lang.Locale.DecimalSeparator);
					tpl.Content.TotalDiscount = Fit.Math.Format(totalDiscount, 2, lang.Locale.DecimalSeparator);

					// Create unit updator controls

					Fit.Array.ForEach(items, function(item)
					{
						var cmdUnits = new Fit.Controls.Button(Fit.Data.CreateGuid());
						cmdUnits.Title(item.Units.toString());
						cmdUnits.Type(Fit.Controls.Button.Type.Default);
						cmdUnits.GetDomElement().style.cssText = "font-size: 0.9em; padding: 2px 10px 2px 10px;";
						cmdUnits.Width(4, "em");
						cmdUnits.OnClick(function(sender)
						{
							// Create dialog

							var html = "";
							html += "<div style='text-align: center' id='JSShopUnitsDialogInput'>";
							html += "<b>" + item.Product.Title() + "</b><br><br>";
							html += lang.Basket.NumberOfUnits + ":<br><br>";
							html += "</div>";

							var dialog = new Fit.Controls.Dialog();
							dialog.Modal(true);
							dialog.Content(html);

							// Create input field

							var units = item.Units.toString();
							var txtUnits = new Fit.Controls.Input(Fit.Data.CreateGuid());
							txtUnits.Width(4, "em");
							txtUnits.OnChange(function(sender)
							{
								// Validate input - auto correct invalid values

								if (txtUnits.Value() !== "")
								{
									if (isNaN(parseInt(txtUnits.Value())) === true || parseInt(txtUnits.Value()).toString() !== txtUnits.Value() || parseInt(txtUnits.Value()) < 0 || parseInt(txtUnits.Value()) > 999999)
									{
										txtUnits.Value(units);
									}
									else
									{
										units = txtUnits.Value();
									}
								}
							});
							txtUnits.OnBlur(function(sender)
							{
								if (txtUnits.Value() === "")
									txtUnits.Value("0");
							});
							txtUnits.Render(dialog.GetDomElement().querySelector("#JSShopUnitsDialogInput"));

							// Create dialog buttons

							var dispose = function()
							{
								txtUnits.Dispose();
								dialog.Dispose(); // Will also dispose buttons added to dialog
							}

							var cmdSave = new Fit.Controls.Button(Fit.Data.CreateGuid());
							cmdSave.Title(lang.Common.Ok);
							cmdSave.Type(Fit.Controls.Button.Type.Success);
							cmdSave.OnClick(function(sender)
							{
								basket.Update(item.ProductId, parseInt(txtUnits.Value()));
								dispose();
								populateView();
							});
							dialog.AddButton(cmdSave);

							var cmdCancel = new Fit.Controls.Button(Fit.Data.CreateGuid());
							cmdCancel.Title(lang.Common.Cancel);
							cmdCancel.Type(Fit.Controls.Button.Type.Danger);
							cmdCancel.OnClick(function(sender)
							{
								dispose();
							});
							dialog.AddButton(cmdCancel);

							// Add better keyboard support (Enter + Esc)

							Fit.Events.AddHandler(txtUnits.GetDomElement(), "keydown", function(e)
							{
								var ev = Fit.Events.GetEvent(e);

								if (ev.keyCode === 13) // Enter
								{
									if (txtUnits.Value() === "")
										txtUnits.Value("0");

									cmdSave.Click();
								}
							});
							Fit.Events.AddHandler(dialog.GetDomElement(), "keydown", function(e)
							{
								var ev = Fit.Events.GetEvent(e);

								if (ev.keyCode === 27) // ESC
								{
									cmdCancel.Click();
								}
							});

							dialog.Open();
							txtUnits.Focused(true);
						});
						item.View.Units = cmdUnits.GetDomElement();
					});

					tpl.Update();

					// Display warnings

					if (currencyError === true)
						Fit.Controls.Dialog.Alert(lang.Basket.ErrorCurrencies);
					if (weightUnitError === true)
						Fit.Controls.Dialog.Alert(lang.Basket.ErrorWeightUnits);

					// Fire OnUpdated

					Fit.Array.ForEach(onUpdatedHandlers, function(handler) // No handlers set when instance of Basket is created - handlers fire when init() is invoked later when interacting with UI
					{
						handler(me);
					});
				}
			});
		});
	}

	// Public members

	this.ZipCode = function(zip)
	{
		Fit.Validation.ExpectString(zip, true);

		if (Fit.Validation.IsSet(zip) === true)
		{
			if (zip !== zipCode && expressionsContain("zipcode") === true)
			{
				zipCode = zip;
				populateView(); // Update - zip code may influence cost corrections
			}
		}

		return zipCode;
	}

	this.PaymentMethod = function(pm)
	{
		Fit.Validation.ExpectString(pm, true);

		if (Fit.Validation.IsSet(pm) === true)
		{
			if (pm !== paymentMethod && expressionsContain("paymentmethod") === true)
			{
				paymentMethod = pm;
				populateView(); // Update - payment method may influence cost corrections
			}
		}

		return paymentMethod;
	}

	this.PromoCode = function(val)
	{
		Fit.Validation.ExpectString(val, true);

		if (Fit.Validation.IsSet(val) === true)
		{
			if (val !== promoCode && expressionsContain("promocode") === true)
			{
				promoCode = val;
				populateView(); // Update - promocode may influence cost corrections
			}
		}

		return promoCode;
	}

	this.CustData1 = function(val)
	{
		Fit.Validation.ExpectString(val, true);

		if (Fit.Validation.IsSet(val) === true)
		{
			if (val !== custData1 && expressionsContain("custdata1") === true)
			{
				custData1 = val;
				populateView(); // Update - custdata1 may influence cost corrections
			}
		}

		return custData1;
	}

	this.CustData2 = function(val)
	{
		Fit.Validation.ExpectString(val, true);

		if (Fit.Validation.IsSet(val) === true)
		{
			if (val !== custData2 && expressionsContain("custdata2") === true)
			{
				custData2 = val;
				populateView(); // Update - custdata2 may influence cost corrections
			}
		}

		return custData2;
	}

	this.CustData3 = function(val)
	{
		Fit.Validation.ExpectString(val, true);

		if (Fit.Validation.IsSet(val) === true)
		{
			if (val !== custData3 && expressionsContain("custdata3") === true)
			{
				custData3 = val;
				populateView(); // Update - custdata3 may influence cost corrections
			}
		}

		return custData3;
	}

	this.GetPricing = function()
	{
		return result;
	}

	this.GetDomElement = function()
	{
		return dom;
	}

	this.Update = function()
	{
		if (Fit.Dom.IsRooted(dom) === true)
		{
			populateView();
		}
	}

	this.OnUpdate = function(cb)
	{
		Fit.Validation.ExpectFunction(cb);
		Fit.Array.Add(onUpdateHandlers, cb);
	}

	this.OnUpdated = function(cb)
	{
		Fit.Validation.ExpectFunction(cb);
		Fit.Array.Add(onUpdatedHandlers, cb);
	}

	function expressionsContain(str)
	{
		Fit.Validation.ExpectString(str);

		for (var i = 1 ; i <= 3 ; i++)
		{
			if (JSShop.Settings["CostCorrection" + i] && JSShop.Settings["CostCorrection" + i].indexOf(str) > -1
				|| JSShop.Settings["CostCorrectionVat" + i] && JSShop.Settings["CostCorrectionVat" + i].indexOf(str) > -1
				|| JSShop.Settings["CostCorrectionMessage" + i] && JSShop.Settings["CostCorrectionMessage" + i].indexOf(str) > -1)
			{
				return true;
			}
		}

		return false;
	}

	init();
}
