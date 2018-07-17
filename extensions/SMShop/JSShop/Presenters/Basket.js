if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.Basket = function()
{
	Fit.Core.Extend(this, JSShop.Presenters.Base).Apply();

	var me = this;

	var view = null;
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

	view = document.createElement("div");

	function init()
	{
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
			view.innerHTML = lang.Basket.BasketEmpty;
			return;
		}

		// Load view and data

		var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/Basket/Basket.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));
		req.OnSuccess(function(sender)
		{
			var htmlView = req.GetResponseText();

			// Populate header titles

			htmlView = htmlView.replace(/{\[HeaderProduct\]}/g, lang.Basket.Product);
			htmlView = htmlView.replace(/{\[HeaderUnitPrice\]}/g, lang.Basket.UnitPrice);
			htmlView = htmlView.replace(/{\[HeaderUnits\]}/g, lang.Basket.Units);
			htmlView = htmlView.replace(/{\[HeaderDiscount\]}/g, lang.Basket.Discount);
			htmlView = htmlView.replace(/{\[HeaderPrice\]}/g, lang.Basket.Price);
			htmlView = htmlView.replace(/{\[HeaderTotalVat\]}/g, lang.Basket.TotalVat);
			htmlView = htmlView.replace(/{\[HeaderTotalPrice\]}/g, lang.Basket.TotalPrice);

			// Extract item HTML

			var startTag = "<!-- REPEAT Items -->";
			var endTag = "<!-- /REPEAT Items -->";

			var regEx = new RegExp(startTag + "[\\s\\S]*" + endTag);
			var res = regEx.exec(htmlView);

			if (res !== null)
			{
				var allItemsHtml = "";
				var totalVat = 0.0;
				var totalPrice = 0.0;
				var totalDiscount = 0.0;
				var totalWeight = 0.0;

				// Remove <!-- REPEAT Items --> block from item HTML

				var itemHtml = res[0];

				var posStart = itemHtml.indexOf(startTag) + startTag.length;
				var posEnd = itemHtml.indexOf(endTag);

				itemHtml = itemHtml.substring(posStart, posEnd);

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
					var product = new JSShop.Models.Product(item.ProductId);
					product.Retrieve(function(req, model)
					{
						itemCount--;
						item.Product = product;

						if (itemCount === 0) // All products loaded
						{
							// Populate item HTML

							Fit.Array.ForEach(items, function(item)
							{
								// Currency and weight unit validation

								currency = ((currency !== null) ? currency : item.Product.Currency());

								if (item.Product.Currency() !== currency)
									currencyError = true;

								weightUnit = ((weightUnit !== null) ? weightUnit : item.Product.WeightUnit());

								if (item.Product.WeightUnit() !== weightUnit)
									weightUnitError = true;

								// Populate HTML

								var curItemHtml = itemHtml;

								var discountExclVat = Fit.Math.Round(item.Product.CalculateDiscount(item.Units), 2);
								var pricing = JSShop.CalculatePricing(item.Product.Price(), item.Units, item.Product.Vat(), discountExclVat);

								curItemHtml = curItemHtml.replace(/{\[Title\]}/g, item.Product.Title());
								curItemHtml = curItemHtml.replace(/{\[UnitPrice\]}/g, Fit.Math.Format(pricing.UnitPriceInclVat, 2, lang.Locale.DecimalSeparator));
								curItemHtml = curItemHtml.replace(/{\[DiscountMessage\]}/g, ((pricing.DiscountInclVat > 0 || pricing.DiscountInclVat < 0) ? item.Product.CalculateDiscountMessage(item.Units) : ""));
								curItemHtml = curItemHtml.replace(/{\[Discount\]}/g, ((pricing.DiscountInclVat > 0 || pricing.DiscountInclVat < 0) ? Fit.Math.Format(pricing.DiscountInclVat * -1, 2, lang.Locale.DecimalSeparator) : ""));
								curItemHtml = curItemHtml.replace(/{\[Units\]}/g, "<div id='JSShopBasketItem" + item.ProductId + "'></div>");
								curItemHtml = curItemHtml.replace(/{\[Currency\]}/g, item.Product.Currency());
								curItemHtml = curItemHtml.replace(/{\[Price\]}/g, Fit.Math.Format(pricing.TotalInclVat, 2, lang.Locale.DecimalSeparator));

								allItemsHtml += curItemHtml;

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

									var correctionItemHtml = itemHtml;
									correctionItemHtml = correctionItemHtml.replace(/{\[Title\]}/g, correctionMessage);
									correctionItemHtml = correctionItemHtml.replace(/{\[UnitPrice\]}/g, "&nbsp;");
									correctionItemHtml = correctionItemHtml.replace(/{\[Discount\]}/g, "");
									correctionItemHtml = correctionItemHtml.replace(/{\[DiscountMessage\]}/g, "");
									correctionItemHtml = correctionItemHtml.replace(/{\[Units\]}/g, "&nbsp;");
									correctionItemHtml = correctionItemHtml.replace(/{\[Currency\]}/g, currency);
									correctionItemHtml = correctionItemHtml.replace(/{\[Price\]}/g, Fit.Math.Format(correctionInclVat, 2, lang.Locale.DecimalSeparator));

									allItemsHtml += correctionItemHtml;

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

							htmlView = htmlView.replace(res[0], allItemsHtml);
							htmlView = htmlView.replace(/{\[Currency\]}/g, items[0].Product.Currency());
							htmlView = htmlView.replace(/{\[TotalVat\]}/g, Fit.Math.Format(totalVat, 2, lang.Locale.DecimalSeparator));
							htmlView = htmlView.replace(/{\[TotalPrice\]}/g, Fit.Math.Format(totalPrice, 2, lang.Locale.DecimalSeparator));
							htmlView = htmlView.replace(/{\[TotalDiscount\]}/g, Fit.Math.Format(totalDiscount, 2, lang.Locale.DecimalSeparator));

							view.innerHTML = htmlView;

							// Create unit updator controls

							Fit.Array.ForEach(items, function(item)
							{
								var cmdUnits = new Fit.Controls.Button(Fit.Data.CreateGuid());
								cmdUnits.Title(item.Units.toString());
								cmdUnits.Type(Fit.Controls.Button.Type.Default);
								cmdUnits.GetDomElement().style.cssText = "font-size: 0.9em; padding: 2px 10px 2px 10px;";
								cmdUnits.Width(4, "em");
								cmdUnits.Render(view.querySelector("#JSShopBasketItem" + item.ProductId));
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

									var cmdSave = new Fit.Controls.Button(Fit.Data.CreateGuid());
									cmdSave.Title(lang.Common.Ok);
									cmdSave.Type(Fit.Controls.Button.Type.Success);
									cmdSave.OnClick(function(sender)
									{
										basket.Update(item.ProductId, parseInt(txtUnits.Value()));
										dialog.Close();
										init();
									});
									dialog.AddButton(cmdSave);

									var cmdCancel = new Fit.Controls.Button(Fit.Data.CreateGuid());
									cmdCancel.Title(lang.Common.Cancel);
									cmdCancel.Type(Fit.Controls.Button.Type.Danger);
									cmdCancel.OnClick(function(sender)
									{
										dialog.Close();
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
											dialog.Close();
									});

									dialog.Open();
									txtUnits.Focused(true);
								});
							});

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
				})
			}
		});
		req.Start();
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
				init(); // Update - zip code may influence cost corrections
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
				init(); // Update - payment method may influence cost corrections
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
				init(); // Update - promocode may influence cost corrections
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
				init(); // Update - custdata1 may influence cost corrections
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
				init(); // Update - custdata2 may influence cost corrections
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
				init(); // Update - custdata3 may influence cost corrections
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
		return view;
	}

	this.Update = function()
	{
		if (Fit.Dom.IsRooted(view) === true)
		{
			init();
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
