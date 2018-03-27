if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

// This is not a real Presenter - it is not extending JSShop.Presenters.Base !
// Invoking JSShop.Presenters.ProductList.Initialize(domElm) will create Buy and Info buttons in domElm.

// Buy Button example: <span class="JSShopBuyButton" data-ProductId="1003">Buy</span>
// The DOM element above will be populated with a Buy button and a Unit input field.
// A Buy Button will add the product to the basket when clicked and ask the user whether to
// navigate to the basket or continue shopping.

// Info Button example: <span class="JSShopInfoButton" onclick="ShowProductInfo('1003');">Read more</span>
// The DOM element above will be populated with an Info Button which visually is similar to a Buy Button,
// but it has no predefined behaviour.

// It's important to understand that JSShop.Presenters.ProductList.Initialize(domElm) only add
// buttons to make it possible to add products to the basket. The page on which the function is called should
// already contain all the product information and images necessary for the user to find and buy relevant products.

JSShop.Presenters.ProductList = {};
JSShop.Presenters.ProductList.Initialize = function(productList)
{
	Fit.Validation.ExpectDomElement(productList);

	// Load CSS

	if (document.querySelector("link[href*='/Views/ProductList.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
		Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/ProductList.css");

	// Create Buy buttons

	var buyButtons = document.querySelectorAll(".JSShopBuyButton");
	Fit.Array.ForEach(buyButtons, function(button)
	{
		var productId = Fit.Dom.Data(button, "ProductId");

		if (productId === null)
		{
			console.log("Unable to create Buy Button - data-ProductId attribute must be set");
			return;
		}

		var units = "1";
		var i = new Fit.Controls.Input("JSShopUnits" + Fit.Data.CreateGuid());
		i.Width(2.5, "em");
		i.Height(2, "em");
		i.Value(units);
		i.OnChange(function(sender)
		{
			if (i.Value() !== "" && (isNaN(parseInt(i.Value())) === true || parseInt(i.Value()).toString() !== i.Value() || parseInt(i.Value()) < 1 || parseInt(i.Value()) > 999999))
			{
				i.Value(units);
				return;
			}

			units = i.Value();
		});
		i.OnBlur(function(sender)
		{
			if (i.Value() === "")
				i.Value("1");
		});

		var b = new Fit.Controls.Button("JSShopBuyButton" + Fit.Data.CreateGuid());
		b.Height(2, "em");
		b.Title(button.innerHTML);
		b.Type(Fit.Controls.Button.Type.Primary);
		b.Icon("shopping-cart");
		b.OnClick(function()
		{
			b.Enabled(false);

			var p = new JSShop.Models.Product(productId);
			p.Retrieve(function()
			{
				// Add to basket

				JSShop.Models.Basket.Add(p.Id(), parseInt(i.Value()));

				// Display dialog with Checkout and Continue button

				var dialog = new Fit.Controls.Dialog();
				dialog.Modal(true);
				dialog.Content("<b>" + JSShop.Language.Translations.ProductList.ProductAdded + "</b><br><br>" + i.Value() + " x " + p.Title());

				if (JSShop.Settings.BasketUrl !== null)
				{
					var cmdOpenBasket = new Fit.Controls.Button("JSShopBasketButton" + Fit.Data.CreateGuid());
					cmdOpenBasket.Type(Fit.Controls.Button.Type.Default);
					cmdOpenBasket.Icon("shopping-basket"); // credit-card-alt
					cmdOpenBasket.Title(JSShop.Language.Translations.ProductList.OpenBasket);
					cmdOpenBasket.OnClick(function(sender)
					{
						dialog.Close();
						b.Enabled(true);
						location.href = JSShop.Settings.BasketUrl;
					});
					dialog.AddButton(cmdOpenBasket);
				}

				var cmdContinue = new Fit.Controls.Button("JSShopContinueButton" + Fit.Data.CreateGuid());
				cmdContinue.Type(Fit.Controls.Button.Type.Default);
				cmdContinue.Icon("shopping-cart");
				cmdContinue.Title(JSShop.Language.Translations.ProductList.ContinueShopping);
				cmdContinue.OnClick(function(sender)
				{
					dialog.Close();
					b.Enabled(true);
				});
				dialog.AddButton(cmdContinue);

				dialog.Open();
				cmdContinue.Focused(true);
			},
			function()
			{
				alert("Product with ID '" + productId + "' not found");
				b.Enabled(true);
			});
		});

		// Make button and input align properly on Internet Explorer 11
		var container = document.createElement("span");
		b.GetDomElement().style.verticalAlign = "top";
		i.GetDomElement().style.verticalAlign = "top";
		i.GetDomElement().style.marginLeft = "0.3em";
		Fit.Dom.Add(container, b.GetDomElement());
		Fit.Dom.Add(container, i.GetDomElement());

		button.innerHTML = "";
		Fit.Dom.Add(button, container);
	});

	// Create ordinary buttons

	var buttons = document.querySelectorAll(".JSShopInfoButton");
	Fit.Array.ForEach(buttons, function(btn)
	{
		var b = new Fit.Controls.Button("SMShopButton" + Fit.Data.CreateGuid());
		b.Height(2, "em");
		b.Title(btn.innerHTML);
		b.Type(Fit.Controls.Button.Type.Info);

		btn.innerHTML = "";
		Fit.Dom.Add(btn, b.GetDomElement());
	});

	// Localize numbers

	var elements = document.querySelectorAll(".JSShopLocalizedNumber");
	Fit.Array.ForEach(elements, function(elm)
	{
		var val = elm.innerHTML;
		var isNumber = /^\-?([0-9]+(\.[0-9]+)?)$/.test(val); // Both positive and negative values are allowed

		if (isNumber === false)
			return; // Skip

		elm.innerHTML = val.replace(".", JSShop.Language.Translations.Locale.DecimalSeparator);
	});
}
