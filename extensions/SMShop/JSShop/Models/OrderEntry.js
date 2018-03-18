if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Models.OrderEntry = function(entryId)
{
	Fit.Validation.ExpectStringValue(entryId);
	Fit.Core.Extend(this, JSShop.Models.Base).Apply(entryId);

	var me = this;

	var properties =
	{
		Id: entryId,			// string
		OrderId: "",			// string - related order entries must have the same Order ID
		ProductId: "",			// string
		UnitPrice: 0,			// number
		Vat: 0,					// number
		Currency: "",			// string
		Units: 0,				// number
		Discount: 0,			// number
		DiscountMessage: ""		// string
	};

	function init()
	{
		me.InitializeModel();
	}

	this.GetModelName = function()
	{
		return "OrderEntry";
	}

	this.GetProperties = function()
	{
		return properties;
	}

	this.GetWebServiceUrls = function()
	{
		var urls =
		{
			Create: JSShop.WebService.OrderEntries.Create,
			Retrieve: JSShop.WebService.OrderEntries.Retrieve,
			RetrieveAll: JSShop.WebService.OrderEntries.RetrieveAll,
			Update: JSShop.WebService.OrderEntries.Update,
			Delete: JSShop.WebService.OrderEntries.Delete
		}

		return urls;
	}

	init();
}
JSShop.Models.OrderEntry.RetrieveAll = function(orderId, cbSuccess, cbFailure)
{
	Fit.Validation.ExpectString(orderId);
	Fit.Validation.ExpectFunction(cbSuccess);
	Fit.Validation.ExpectFunction(cbFailure, true);

	var match = [[{ Field: "OrderId", Operator: "=", Value: orderId }]]; // Multi dimensional: [ [match1 AND match2] OR [matchA AND matchB] OR ... ]
	JSShop.Models.Base.RetrieveAll(JSShop.Models.OrderEntry, "Id", match, cbSuccess, cbFailure);
}
