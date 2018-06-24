if (!window.Fit)
	throw "Fit.UI must be loaded for JSShop to work";

// ======================================================
// Namespaces
// ======================================================

JSShop = {};
JSShop._internal = { };

// Settings

JSShop.Settings = {};
JSShop.Settings.CacheKey = "";
JSShop.Settings.Debug = false;
JSShop.Settings.CostCorrection1 = null;
JSShop.Settings.CostCorrectionVat1 = null;
JSShop.Settings.CostCorrectionMessage1 = null;
JSShop.Settings.CostCorrection2 = null;
JSShop.Settings.CostCorrectionVat2 = null;
JSShop.Settings.CostCorrectionMessage2 = null;
JSShop.Settings.CostCorrection3 = null;
JSShop.Settings.CostCorrectionVat3 = null;
JSShop.Settings.CostCorrectionMessage3 = null;
JSShop.Settings.AdditionalData = {};
JSShop.Settings.ConfigUrl = null;
JSShop.Settings.BasketUrl = null;
JSShop.Settings.TermsUrl = null;
JSShop.Settings.PaymentUrl = null;
JSShop.Settings.PaymentMethods = null;
/*JSShop.Settings.PaymentMethods =
[
	{ Title: "Credit Card - VISA, MasterCard, Dinners Club", Module: "DIBS", Enabled: true }
]*/
JSShop.Settings.PaymentCaptureUrl = null;
JSShop.Settings.PaymentCancelUrl = null;
JSShop.Settings.SendInvoiceUrl = null;
JSShop.Settings.Pages = null;

// Language (cannot be null or undefined - must defined .Name and all entries in .Translations)

JSShop.Language = {};
JSShop.Language.Name = "en";
JSShop.Language.Translations = {};

// Models, Presenters, and Cookies

JSShop.Models = {};
JSShop.Presenters = {};

JSShop.Cookies = new Fit.Cookies();
JSShop.Cookies.Prefix("JSShop");

// Communication

JSShop.WebService = {};
JSShop.WebService.Configuration = {};
JSShop.WebService.Configuration.Retrieve = null;
JSShop.WebService.Configuration.Update = null;
JSShop.WebService.Products = {};
JSShop.WebService.Products.Create = null;
JSShop.WebService.Products.Retrieve = null;
JSShop.WebService.Products.Update = null;
JSShop.WebService.Products.Delete = null;
JSShop.WebService.Files = {};
JSShop.WebService.Files.Upload = null;
JSShop.WebService.Files.Remove = null;
JSShop.WebService.Orders = {};
JSShop.WebService.Orders.Create = null;
JSShop.WebService.Orders.Retrieve = null;
JSShop.WebService.Orders.Update = null;
JSShop.WebService.Orders.Delete = null;
JSShop.WebService.OrderEntries = {};
JSShop.WebService.OrderEntries.Create = null;
JSShop.WebService.OrderEntries.Retrieve = null;
JSShop.WebService.OrderEntries.Update = null;
JSShop.WebService.OrderEntries.Delete = null;
JSShop.WebService.Tags = {};
JSShop.WebService.Tags.Create = null;
JSShop.WebService.Tags.Retrieve = null;
JSShop.WebService.Tags.Update = null;
JSShop.WebService.Tags.Delete = null;

// ======================================================
// Cross model event handlers
// ======================================================

// Callback signatures: function(request, model[], operationType).
// Called when models interact with backend.

JSShop.Events = {};
JSShop.Events.OnRequest = null;
JSShop.Events.OnSuccess = null;
JSShop.Events.OnError = null;

// ======================================================
// JSShop path info
// ======================================================

// Determine path information

(function()
{
	// Get URL to this script file (e.g. http://server.com/libs/jsshop/JSShop.js?CacheKey=3910)
	// NOTICE: This will not work if loaded dynamically using a script loader since loading is async,
	// resulting in the last script reference on the page being fetched which may not be this file.
	var src = document.scripts[document.scripts.length - 1].src;

	var qs = Fit.Browser.GetQueryString(src);

	// Get CacheKey from script URL if defined
	var cacheKey = qs.Parameters["CacheKey"];
	JSShop.Settings.CacheKey = (cacheKey ? cacheKey : null);

	// Get Debug flag from script URL if defined
	var debug = qs.Parameters["Debug"];
	JSShop.Settings.Debug = (debug === "true" ? true : false);

	// Extract Base URL - e.g. http://server.com/libs/jsshop
	JSShop._internal.BaseUrl = src.substring(0, src.lastIndexOf("/"));

	// Calculate Base Path - e.g. /libs/jsshop
	var path = JSShop._internal.BaseUrl.replace("http://", "").replace("https://", "");
	JSShop._internal.BasePath = path.substring(path.indexOf("/"));

	// Optimize if debug mode is not enabled
	if (JSShop.Settings.Debug === false)
	{
		Fit.Validation.Enabled(false);
	}
})();

JSShop.GetUrl = function()
{
	return JSShop._internal.BaseUrl;
}

JSShop.GetPath = function()
{
	return JSShop._internal.BasePath;
}

JSShop.CalculatePricing = function(priceExVat, units, vatPercentage, discountExVat)
{
	Fit.Validation.ExpectNumber(priceExVat);
	Fit.Validation.ExpectInteger(units);
	Fit.Validation.ExpectNumber(vatPercentage);
	Fit.Validation.ExpectNumber(discountExVat);

	// Using Round everywhere to prevent common floating point arithmetic problems (e.g. 0.1 + 0.2 = 0.30000000000000004)

	var vatFactor = ((vatPercentage > 0) ? 1.0 + (vatPercentage / 100) : 1.0);
	var numberOfUnits = units;
	var unitPriceExclVat = Fit.Math.Round(priceExVat, 2); //priceExVat;
	var unitPriceInclVat = Fit.Math.Round(unitPriceExclVat * vatFactor, 2);
	var priceAllUnitsInclVat = Fit.Math.Round(unitPriceInclVat * numberOfUnits, 2);

	var discountExclVat = Fit.Math.Round(discountExVat, 2);
	var discountInclVat = Fit.Math.Round(discountExclVat * vatFactor, 2);

	var resultPriceInclVat = Fit.Math.Round(priceAllUnitsInclVat - discountInclVat, 2);
	var resultPriceExclVat = Fit.Math.Round(resultPriceInclVat / vatFactor, 2);
	var resultVat = Fit.Math.Round(resultPriceInclVat - resultPriceExclVat, 2);
	//console.log(resultPriceInclVat, resultPriceExclVat, resultVat);

	//var resultPriceInclVat = Fit.Math.Round(priceAllUnitsInclVat - discountInclVat, 2);
	//var resultVat = Fit.Math.Round(resultPriceInclVat - Fit.Math.Round(resultPriceInclVat / vatFactor, 2) /*(resultPriceInclVat / vatFactor)*/, 2);
	//var resultPriceExclVat = Fit.Math.Round(resultPriceInclVat - resultVat, 2);
	//console.log(resultPriceInclVat, resultPriceExclVat, resultVat);

	var resultObj =
	{
		VatFactor: vatFactor,
		UnitPriceExclVat: unitPriceExclVat,
		UnitPriceInclVat: unitPriceInclVat,
		DiscountExclVat: discountExclVat,
		DiscountInclVat: discountInclVat,
		TotalInclVat: resultPriceInclVat,
		TotalExclVat: resultPriceExclVat,
		TotalVat: resultVat
	};

	/*{
		VatPercentage: vatPercentage,
		VatFactor: vatFactor,
		UnitPriceExclVat: unitPriceExclVat,
		UnitPriceInclVat: unitPriceInclVat,
		DiscountExclVat: discountExclVat,
		DiscountInclVat: discountInclVat,
		TotalVat: resultVat,
		TotalInclVat: resultPriceInclVat,
		TotalExclVat: resultPriceExclVat
	}*/

	return resultObj;
}

// ======================================================
// JSShop loader
// ======================================================

JSShop._internal.Initialized = false;

JSShop.Initialize = function(cb)
{
	Fit.Validation.ExpectFunction(cb);

	// Validate JSShop object - arrays are validated further down

	Fit.Validation.ExpectObject(window.JSShop);

	Fit.Validation.ExpectObject(JSShop.Settings);
	Fit.Validation.ExpectString(JSShop.Settings.CacheKey, true);
	Fit.Validation.ExpectBoolean(JSShop.Settings.Debug, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrection1, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrectionVat1, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrectionMessage1, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrection2, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrectionVat2, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrectionMessage2, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrection3, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrectionVat3, true);
	Fit.Validation.ExpectString(JSShop.Settings.CostCorrectionMessage3, true);
	Fit.Validation.ExpectObject(JSShop.Settings.AdditionalData);
	Fit.Validation.ExpectString(JSShop.Settings.ConfigUrl, true);
	Fit.Validation.ExpectString(JSShop.Settings.BasketUrl, true);
	Fit.Validation.ExpectString(JSShop.Settings.TermsUrl, true);
	Fit.Validation.ExpectString(JSShop.Settings.PaymentUrl, true);
	Fit.Validation.ExpectArray(JSShop.Settings.PaymentMethods, true);
	Fit.Validation.ExpectString(JSShop.Settings.PaymentCaptureUrl, true);
	Fit.Validation.ExpectString(JSShop.Settings.PaymentCancelUrl, true);
	Fit.Validation.ExpectString(JSShop.Settings.SendInvoiceUrl, true);
	Fit.Validation.ExpectArray(JSShop.Settings.Pages, true);

	Fit.Validation.ExpectObject(JSShop.Language);
	Fit.Validation.ExpectObject(JSShop.Language.Translations);
	Fit.Validation.ExpectString(JSShop.Language.Name);

	Fit.Validation.ExpectObject(JSShop.Models);
	Fit.Validation.ExpectObject(JSShop.Presenters);
	Fit.Validation.ExpectInstance(JSShop.Cookies, Fit.Cookies);

	// Although WebService function URLs are marked optional (true argument), not providing this information will
	// prevent WebService features from working - network communication errors will occur. But JSShop could be
	// used with almost all Presenters except e.g. Configuration, in which case we would like to be able to avoid
	// assigning values to JSShop.WebService.Configuration.Retrieve and JSShop.WebService.Configuration.Update.
	// The approach below allows for most flexibility.
	Fit.Validation.ExpectObject(JSShop.WebService);
	Fit.Validation.ExpectObject(JSShop.WebService.Configuration);
	Fit.Validation.ExpectString(JSShop.WebService.Configuration.Retrieve, true);
	Fit.Validation.ExpectString(JSShop.WebService.Configuration.Update, true);
	Fit.Validation.ExpectObject(JSShop.WebService.Products);
	Fit.Validation.ExpectString(JSShop.WebService.Products.Create, true);
	Fit.Validation.ExpectString(JSShop.WebService.Products.Retrieve, true);
	Fit.Validation.ExpectString(JSShop.WebService.Products.Update, true);
	Fit.Validation.ExpectString(JSShop.WebService.Products.Delete, true);
	Fit.Validation.ExpectObject(JSShop.WebService.Files);
	Fit.Validation.ExpectString(JSShop.WebService.Files.Upload, true);
	Fit.Validation.ExpectString(JSShop.WebService.Files.Remove, true);
	Fit.Validation.ExpectObject(JSShop.WebService.Orders);
	Fit.Validation.ExpectString(JSShop.WebService.Orders.Create, true);
	Fit.Validation.ExpectString(JSShop.WebService.Orders.Retrieve, true);
	Fit.Validation.ExpectString(JSShop.WebService.Orders.Update, true);
	Fit.Validation.ExpectString(JSShop.WebService.Orders.Delete, true);
	Fit.Validation.ExpectObject(JSShop.WebService.OrderEntries);
	Fit.Validation.ExpectString(JSShop.WebService.OrderEntries.Create, true);
	Fit.Validation.ExpectString(JSShop.WebService.OrderEntries.Retrieve, true);
	Fit.Validation.ExpectString(JSShop.WebService.OrderEntries.Update, true);
	Fit.Validation.ExpectString(JSShop.WebService.OrderEntries.Delete, true);

	Fit.Validation.ExpectObject(JSShop.Events);
	Fit.Validation.ExpectFunction(JSShop.Events.OnRequest, true);
	Fit.Validation.ExpectFunction(JSShop.Events.OnSuccess, true);
	Fit.Validation.ExpectFunction(JSShop.Events.OnError, true);

	if (JSShop.Settings.PaymentMethods)
	{
		Fit.Array.ForEach(JSShop.Settings.PaymentMethods, function(pm)
		{
			Fit.Validation.ExpectString(pm.Module);
			Fit.Validation.ExpectString(pm.Title);
			Fit.Validation.ExpectBoolean(pm.Enabled);
		});
	}

	if (JSShop.Settings.Pages)
	{
		Fit.Array.ForEach(JSShop.Settings.Pages, function(p)
		{
			Fit.Validation.ExpectString(p.Title);
			Fit.Validation.ExpectString(p.Value);
		});
	}

	// Initialize JSShop

	if (JSShop._internal.Initialized === true)
	{
		cb();
		return;
	}

	var cacheKey = (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0");
	var resources = null;

	if (JSShop.Settings.Debug)
	{
		resources =
		[
			// Load language
			{ source: JSShop.GetPath() + "/Languages/" + JSShop.Language.Name.toLowerCase() + ".js?CacheKey=" + cacheKey },

			// Load models
			{ source: JSShop.GetPath() + "/Models/Base.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Models/Config.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Models/Product.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Models/Basket.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Models/Order.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Models/OrderEntry.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Models/Tag.js?CacheKey=" + cacheKey },

			// Load presenters
			{ source: JSShop.GetPath() + "/Presenters/Base.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/ProductForm.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/ProductList.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/Basket.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/OrderForm.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/OrderList.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/Config.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/StatusDialog.js?CacheKey=" + cacheKey }
		]
	}
	else
	{
		resources =
		[
			// Load language
			{ source: JSShop.GetPath() + "/Languages/" + JSShop.Language.Name.toLowerCase() + ".js?CacheKey=" + cacheKey },

			// Load bundle including all models and presenters
			{ source: JSShop.GetPath() + "/JSShopBundle.js?CacheKey=" + cacheKey }
		]
	}

	Fit.Loader.LoadScripts(resources, function(cfgs)
	{
		JSShop._internal.Initialized = true;

		Fit.Language.Translations.Required = JSShop.Language.Translations.Common.Required;
		Fit.Language.Translations.Ok = JSShop.Language.Translations.Common.Ok;
		Fit.Language.Translations.Cancel = JSShop.Language.Translations.Common.Cancel;

		cb();
	});
}
