if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Models.Basket = {};

JSShop.Models.Basket.Add = function(productId, units)
{
	Fit.Validation.ExpectStringValue(productId);
	Fit.Validation.ExpectInteger(units);

	if (units < 1)
		return;

	var basketData = JSShop.Cookies.Get("Basket");
	var basket = ((basketData !== null) ? JSON.parse(basketData) : { Items: [] });

	var alreadyAdded = false;

	Fit.Array.ForEach(basket.Items, function(item)
	{
		if (item.ProductId === productId)
		{
			alreadyAdded = true;
			item.Units = item.Units + units;

			return false; // Break loop
		}
	});

	if (alreadyAdded === false)
	{
		var item = { ProductId: productId, Units: units };
		Fit.Array.Add(basket.Items, item);
	}

	JSShop.Cookies.Set("Basket", JSON.stringify(basket)); // Session cookie
}

JSShop.Models.Basket.Update = function(productId, units)
{
	Fit.Validation.ExpectStringValue(productId);
	Fit.Validation.ExpectInteger(units);

	if (units < 1)
	{
		JSShop.Models.Basket.Remove(productId);
		return;
	}

	var basketData = JSShop.Cookies.Get("Basket");
	var basket = ((basketData !== null) ? JSON.parse(basketData) : { Items: [] });

	var exists = false;

	Fit.Array.ForEach(basket.Items, function(item)
	{
		if (item.ProductId === productId)
		{
			exists = true;
			item.Units = units;

			return false; // Break loop
		}
	});

	if (exists === true)
	{
		JSShop.Cookies.Set("Basket", JSON.stringify(basket)); // Session cookie
	}
	else
	{
		// In the very odd case where the user changed the number of
		// units after removing the product from the basket in another window.
		JSShop.Models.Basket.Add(productId, units);
	}
}

JSShop.Models.Basket.Remove = function(productId)
{
	Fit.Validation.ExpectStringValue(productId);

	var basketData = JSShop.Cookies.Get("Basket");
	var basket = ((basketData !== null) ? JSON.parse(basketData) : { Items: [] });
	var newBasket = { Items: [] };

	Fit.Array.ForEach(basket.Items, function(item)
	{
		if (item.ProductId !== productId)
			Fit.Array.Add(newBasket.Items, item);
	});

	JSShop.Cookies.Set("Basket", JSON.stringify(newBasket)); // Session cookie
}

JSShop.Models.Basket.GetItems = function()
{
	var basketData = JSShop.Cookies.Get("Basket");
	var basket = ((basketData !== null) ? JSON.parse(basketData) : { Items: [] });

	return basket.Items;
}

JSShop.Models.Basket.Clear = function()
{
	JSShop.Cookies.Remove("Basket");
}

JSShop.Models.Basket.CreateOrder = function(order, cbSuccess, cbFailure)
{
	Fit.Validation.ExpectInstance(order, JSShop.Models.Order);
	Fit.Validation.ExpectFunction(cbSuccess);
	Fit.Validation.ExpectFunction(cbFailure, true);

	// NOTICE:
	// Backend is responsible for calculating discounts,
	// shipping expence, VAT, totals, etc., to prevent
	// malicious users from tampering with data.
	// Backend can optionally push these information
	// back to the client, to update the Order model.

	var basketData = JSShop.Cookies.Get("Basket");
	var basket = ((basketData !== null) ? JSON.parse(basketData) : { Items: [] });

	var orderEntries = [];
	var failure = false;

	Fit.Array.ForEach(basket.Items, function(item) // Consider batching these operations for better performance
	{
		var entry = new JSShop.Models.OrderEntry(Fit.Data.CreateGuid());
		entry.OrderId(order.Id());
		entry.ProductId(item.ProductId);
		entry.Units(item.Units);
		entry.Create(function(eReq, eModel)
		{
			Fit.Array.Add(orderEntries, entry);

			if (orderEntries.length === basket.Items.length)
			{
				if (failure === false)
				{
					order.Create(function(oReq, oModel)
					{
						cbSuccess(order);
					},
					function(oReq, oModel)
					{
						if (Fit.Validation.IsSet(cbFailure) === true)
							cbFailure(order);
					});
				}
				else
				{
					if (Fit.Validation.IsSet(cbFailure) === true)
						cbFailure(order);
				}
			}
		},
		function(eReq, eModel)
		{
			Fit.Array.Add(orderEntries, entry);
			failure = true;

			if (orderEntries.length === basket.Items.length)
			{
				if (Fit.Validation.IsSet(cbFailure) === true)
					cbFailure(order);
			}
		});
	});
}
