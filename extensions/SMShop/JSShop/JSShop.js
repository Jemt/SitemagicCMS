if (!window.Fit)
	throw "Fit.UI must be loaded for JSShop to work";

// ======================================================
// Namespaces
// ======================================================

JSShop = {};
JSShop._internal = { CacheKey: null, Debug: false };

// Settings

JSShop.Settings = {};
JSShop.Settings.CostCorrection1 = null;
JSShop.Settings.CostCorrectionVat1 = 0;
JSShop.Settings.CostCorrectionMessage1 = null;
JSShop.Settings.CostCorrection2 = null;
JSShop.Settings.CostCorrectionVat2 = 0;
JSShop.Settings.CostCorrectionMessage2 = null;
JSShop.Settings.CostCorrection3 = null;
JSShop.Settings.CostCorrectionVat3 = 0;
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

// Language

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
	JSShop._internal.CacheKey = (cacheKey ? cacheKey : null);

	// Get Debug flag from script URL if defined
	var debug = qs.Parameters["Debug"];
	JSShop._internal.Debug = (debug === "true" ? true : false);

	// Extract Base URL - e.g. http://server.com/libs/jsshop
	JSShop._internal.BaseUrl = src.substring(0, src.lastIndexOf("/"));

	// Calculate Base Path - e.g. /libs/jsshop
	var path = JSShop._internal.BaseUrl.replace("http://", "").replace("https://", "");
	JSShop._internal.BasePath = path.substring(path.indexOf("/"));
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

	if (JSShop._internal.Initialized === true)
	{
		cb();
		return;
	}

	var cacheKey = (JSShop._internal.CacheKey !== null ? JSShop._internal.CacheKey : "0");
	var resources = null;

	if (JSShop._internal.Debug === true)
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

			// Load presenters
			{ source: JSShop.GetPath() + "/Presenters/ProductForm.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/ProductList.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/Basket.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/OrderForm.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/OrderList.js?CacheKey=" + cacheKey },
			{ source: JSShop.GetPath() + "/Presenters/Config.js?CacheKey=" + cacheKey }
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
