/*
// Performance test client side:

for (var i = 20000 ; i < 20500 ; i++)
{
    create(i.toString(), i.toString());
}

function create(orderId, orderEntryId)
{
    var order = new JSShop.Models.Order(orderId);
    var entry = new JSShop.Models.OrderEntry(orderEntryId);
    entry.OrderId(orderId);
    entry.ProductId("10001");
    entry.UnitPrice(2000);
    entry.Units(3);
    entry.Vat(25);
    entry.Create(function(a,b,c)
    {
        order.Create();
    });
}
*/

if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Models.Order = function(orderId)
{
	Fit.Validation.ExpectStringValue(orderId);
	Fit.Core.Extend(this, JSShop.Models.Base).Apply(orderId);

	var me = this;

	var properties =
	{
		Id: orderId,						// string
		Time: -1,							// number
		ClientIp: "",						// string
		Company: "",						// string
		FirstName: "",						// string
		LastName: "",						// string
		Address: "",						// string
		ZipCode: "",						// string
		City: "",							// string
		Email: "",							// string
		Phone: "",							// string
		Message: "",						// string
		AltCompany: "",						// string
		AltFirstName: "",					// string
		AltLastName: "",					// string
		AltAddress: "",						// string
		AltZipCode: "",						// string
		AltCity: "",						// string
		Price: 0,							// number
		Vat: 0,								// number
		Currency: "",						// string
		Weight: 0,							// number
		WeightUnit: "",						// string
		CostCorrection1: 0,					// number
		CostCorrectionVat1: 0,				// number
		CostCorrectionMessage1: "",			// string
		CostCorrection2: 0,					// number
		CostCorrectionVat2: 0,				// number
		CostCorrectionMessage2: "",			// string
		CostCorrection3: 0,					// number
		CostCorrectionVat3: 0,				// number
		CostCorrectionMessage3: "",			// string
		PaymentMethod: "",					// string
		TransactionId: "",					// string
		State: "",							// string (Initial | Authorized | Captured | Canceled)
		PromoCode: "",						// string
		CustData1: "",						// string
		CustData2: "",						// string
		CustData3: "",						// string
		InvoiceId: "",						// string
		InvoiceTime: -1						// number
	};

	function init()
	{
		me.InitializeModel();
	}

	this.GetModelName = function()
	{
		return "Order";
	}

	this.GetProperties = function()
	{
		return properties;
	}

	this.GetWebServiceUrls = function()
	{
		var urls =
		{
			Create: JSShop.WebService.Orders.Create,
			Retrieve: JSShop.WebService.Orders.Retrieve,
			RetrieveAll: JSShop.WebService.Orders.RetrieveAll,
			Update: JSShop.WebService.Orders.Update,
			Delete: JSShop.WebService.Orders.Delete
		}

		return urls;
	}

	function calculateNumber(expr) // Used ?
	{
		//return JSShop.Models.Order.CalculateCostCorrection(me.Price(), me.Vat(), me.Currency(), me.Weight(), me.WeightUnit(), ((me.AltZipCode() !== "") ? me.AltZipCode() : me.ZipCode()), me.PaymentMethod(), me.CustData1(), me.CustData2(), me.CustData3(), expr, "number");
		return JSShop.Models.Order.CalculateExpression(me.Price(), me.Vat(), me.Currency(), me.Weight(), me.WeightUnit(), ((me.AltZipCode() !== "") ? me.AltZipCode() : me.ZipCode()), me.PaymentMethod(), me.PromoCode(), me.CustData1(), me.CustData2(), me.CustData3(), expr, "number");

		/*var result = JSShop.Models.Order.CalculateCostCorrection(me.Price(), me.Vat(), me.Currency(), me.Weight(), me.WeightUnit(), ((me.AltZipCode() !== "") ? me.AltZipCode() : me.ZipCode()), me.PaymentMethod(), me.CustData1(), me.CustData2(), me.CustData3(), expr, function(res)
		{
			var isNumber = /^\-?([0-9]+(\.[0-9]+)?)$/.test(res.toString()); // Prevent values such as 3.1580800726582476e-21 - both positive and negative values are allowed
			return (isNumber === true && typeof(res) === "number");
		});

		return result;*/
	}

	function calculateString(expr) // Used ?
	{
		//return JSShop.Models.Order.CalculateCostCorrection(me.Price(), me.Vat(), me.Currency(), me.Weight(), me.WeightUnit(), ((me.AltZipCode() !== "") ? me.AltZipCode() : me.ZipCode()), me.PaymentMethod(), me.CustData1(), me.CustData2(), me.CustData3(), expr, "string");
		return JSShop.Models.Order.CalculateExpression(me.Price(), me.Vat(), me.Currency(), me.Weight(), me.WeightUnit(), ((me.AltZipCode() !== "") ? me.AltZipCode() : me.ZipCode()), me.PaymentMethod(), me.PromoCode(), me.CustData1(), me.CustData2(), me.CustData3(), expr, "string");

		/*var result = JSShop.Models.Order.CalculateCostCorrection(me.Price(), me.Vat(), me.Currency(), me.Weight(), me.WeightUnit(), ((me.AltZipCode() !== "") ? me.AltZipCode() : me.ZipCode()), me.PaymentMethod(), me.CustData1(), me.CustData2(), me.CustData3(), expr, function(res)
		{
			return (typeof(res) === "string");
		});

		return result;*/
	}

	this.CalculateCostCorrection1 = function()
	{
		return calculateNumber(JSShop.Settings.CostCorrection1);
	}

	this.CalculateCostCorrectionVat1 = function()
	{
		return calculateNumber(JSShop.Settings.CostCorrectionVat1);
	}

	this.CalculateCostCorrectionMessage1 = function()
	{
		calculateString(JSShop.Settings.CostCorrectionMessage1);
	}

	this.CalculateCostCorrection2 = function()
	{
		return calculateNumber(JSShop.Settings.CostCorrection2);
	}

	this.CalculateCostCorrectionVat2 = function()
	{
		return calculateNumber(JSShop.Settings.CostCorrectionVat2);
	}

	this.CalculateCostCorrectionMessage2 = function()
	{
		calculateString(JSShop.Settings.CostCorrectionMessage2);
	}

	this.CalculateCostCorrection3 = function()
	{
		return calculateNumber(JSShop.Settings.CostCorrection3);
	}

	this.CalculateCostCorrectionVat3 = function()
	{
		return calculateNumber(JSShop.Settings.CostCorrectionVat3);
	}

	this.CalculateCostCorrectionMessage3 = function()
	{
		calculateString(JSShop.Settings.CostCorrectionMessage3);
	}

	this.CapturePayment = function(cbSuccess, cbFailure)
	{
		// WARNING: Backend should reject this request
		// if user is not logged in to backend !

		Fit.Validation.ExpectFunction(cbSuccess);
		Fit.Validation.ExpectFunction(cbFailure, true);

		if (JSShop.Settings.PaymentCaptureUrl === null || JSShop.Settings.PaymentCaptureUrl === "")
		{
			if (Fit.Validation.IsSet(cbFailure) === true)
				cbFailure(me);

			return;
		}

		var req = new Fit.Http.Request(JSShop.Settings.PaymentCaptureUrl);
		req.AddData("OrderId", me.Id());
		req.OnSuccess(function(sender)
		{
			me.State("Captured");
			cbSuccess(req, me);
		});
		req.OnFailure(function(sender)
		{
			if (Fit.Validation.IsSet(cbFailure) === true)
				cbFailure(req, me);
		});
		req.Start();
	}

	this.CancelPayment = function(cbSuccess, cbFailure)
	{
		// WARNING: Backend should reject this request
		// if user is not logged in to backend !

		Fit.Validation.ExpectFunction(cbSuccess);
		Fit.Validation.ExpectFunction(cbFailure, true);

		if (JSShop.Settings.PaymentCancelUrl === null || JSShop.Settings.PaymentCancelUrl === "")
		{
			if (Fit.Validation.IsSet(cbFailure) === true)
				cbFailure(null, me);

			return;
		}

		var req = new Fit.Http.Request(JSShop.Settings.PaymentCancelUrl);
		req.AddData("OrderId", me.Id());
		req.OnSuccess(function(sender)
		{
			me.State("Canceled");
			cbSuccess(req, me);
		});
		req.OnFailure(function(sender)
		{
			if (Fit.Validation.IsSet(cbFailure) === true)
				cbFailure(req, me);
		});
		req.Start();
	}

	this.SendInvoice = function(cbSuccess, cbFailure)
	{
		// WARNING: Backend should reject this request
		// if user is not logged in to backend !

		Fit.Validation.ExpectFunction(cbSuccess);
		Fit.Validation.ExpectFunction(cbFailure, true);

		if (JSShop.Settings.SendInvoiceUrl === null || JSShop.Settings.SendInvoiceUrl === "")
		{
			if (Fit.Validation.IsSet(cbFailure) === true)
				cbFailure(null, me);

			return;
		}

		var req = new Fit.Http.Request(JSShop.Settings.SendInvoiceUrl);
		req.AddData("OrderId", me.Id());
		req.OnSuccess(function(sender)
		{
			cbSuccess(req, me);
		});
		req.OnFailure(function(sender)
		{
			if (Fit.Validation.IsSet(cbFailure) === true)
				cbFailure(req, me);
		});
		req.Start();
	}

	init();
}
JSShop.Models.Order.RetrieveAll = function(fromTimestamp, toTimestamp, cbSuccess, cbFailure)
{
	Fit.Validation.ExpectInteger(fromTimestamp);
	Fit.Validation.ExpectInteger(toTimestamp);
	Fit.Validation.ExpectFunction(cbSuccess);
	Fit.Validation.ExpectFunction(cbFailure, true);

	var match = [];
	Fit.Array.Add(match, { Field: "Time", Operator: ">=", Value: fromTimestamp });
	Fit.Array.Add(match, { Field: "Time", Operator: "<=", Value: toTimestamp });

	JSShop.Models.Base.RetrieveAll(JSShop.Models.Order, "Id", match, cbSuccess, cbFailure);
}
JSShop.Models.Order.CalculateExpression = function(price, vat, currency, weight, weightUnit, zipCode, paymentMethod, promoCode, custData1, custData2, custData3, expression, returnType/*, resultValidationCallback*/)
{
	Fit.Validation.ExpectNumber(price);
	Fit.Validation.ExpectNumber(vat);
	Fit.Validation.ExpectString(currency);
	Fit.Validation.ExpectNumber(weight);
	Fit.Validation.ExpectString(weightUnit);
	Fit.Validation.ExpectString(zipCode);
	Fit.Validation.ExpectString(paymentMethod);
	Fit.Validation.ExpectString(promoCode);
	Fit.Validation.ExpectString(custData1);
	Fit.Validation.ExpectString(custData2);
	Fit.Validation.ExpectString(custData3);
	Fit.Validation.ExpectStringValue(expression);
	Fit.Validation.ExpectStringValue(returnType);
	//Fit.Validation.ExpectFunction(resultValidationCallback);

	var ex = expression;

	// Security validation

	ex = ex.replace(/\r|\n|\t/g, ""); // Allow use of line breaks and tabs
	ex = ex.replace(/\/\*.*?\*\//g, ""); // Allow use of /*..*/ comments - ? after quantifier makes the match non-greedy
	ex = ex.replace(/index|price|vat|currency|weightunit|weight|zipcodeval|zipcode|paymentmethod|promocode|custdata1|custdata2|custdata3/g, ""); // Allow use of predefined variables
	ex = ex.replace(/JSShop.Floor|JSShop.Ceil|JSShop.Round/g, ""); // Allow use of functions
	ex = ex.replace(/ |[0-9]|\*|\+|\-|\/|%|=|&|\||!|\.|:|\(|\)|\[|\]|>|<|\?|true|false/g, ""); // Allow various math/comparison/logical operations
	ex = ex.replace(/(["']).*?\1/g, ""); // Allow use of double quoted and single quoted strings - ? after quantifiers makes the match non-greedy

	var secure = (ex === ""); // All valid elements were removed above, so if ex contains anything, it is potentially a security threat
	//secure = (secure === true && /(["']).*?<.*?>.*?\1/.test(expression) === false) // Make sure HTML is not found in quoted strings - ? after quantifiers makes the match non-greedy - https://regex101.com/r/dL1bI2/2
	////secure = (secure === true && /(["'])[^\1.]*?<[^\1.]*?>[^\1.]*?\1/.test(expression) === false) // Make sure HTML is not found in quoted strings - ? after quantifiers makes the match non-greedy - https://regex101.com/r/dL1bI2/3

	if (secure === false)
		throw "InvalidExpression: Invalid and potentially insecure expression detected - evaluation aborted";

	// Add data to expression

	var zipCodeVal = ((isNaN(parseInt(zipCode)) === false && parseInt(zipCode) + "" === zipCode) ? parseInt(zipCode) : -1);

	var expr = "";
	expr += "var price = " + price + ";";
	expr += "var vat = " + vat + ";";
	expr += "var currency = \"" + currency + "\";";
	expr += "var weight = " + weight + ";";
	expr += "var weightunit = \"" + weightUnit + "\";";
	expr += "var zipcode = \"" + zipCode + "\";";
	expr += "var zipcodeval = " + zipCodeVal + ";";
	expr += "var paymentmethod = \"" + paymentMethod + "\";";
	expr += "var promocode = \"" + promoCode + "\";";
	expr += "var custdata1 = \"" + custData1 + "\";";
	expr += "var custdata2 = \"" + custData2 + "\";";
	expr += "var custdata3 = \"" + custData3 + "\";";
	expr += "var index = " + ((JSShop.Settings.PriceIndex !== null) ? JSON.stringify(JSShop.Settings.PriceIndex) : {}) + ";";
	expr += "(" + expression.replace(/JSShop\.Floor/g, "Math.floor").replace(/JSShop\.Ceil/g, "Math.ceil").replace(/JSShop\.Round/g, "Math.round") + ");";

	// Evaluate, validate, and return

	var result = eval(expr); // May throw error on invalid expression

	if (typeof(result) === "string")
		result = Fit.String.EncodeHtml(result);

	var isValid = false;

	if (returnType === "number")
	{
		isValid = /^\-?([0-9]+(\.[0-9]+)?)$/.test(result.toString()); // Prevent values such as 3.1580800726582476e-21 - both positive and negative values are allowed
		isValid = (isValid === true && typeof(result) === "number");
	}
	else if (returnType === "string")
	{
		isValid = (typeof(result) === "string");
	}
	else
	{
		throw "InvalidReturnType: Return type must be either 'string' or 'number'";
	}

	//if (resultValidationCallback(result) === false)
	if (isValid === false)
		throw "InvalidExpressionResult: Expression did not produce a valid value of type '" + returnType + "'";

	return result;
}
