if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Models.Product = function(itemId)
{
	Fit.Validation.ExpectStringValue(itemId);
	Fit.Core.Extend(this, JSShop.Models.Base).Apply(itemId);

	var me = this;

	var properties =
	{
		Id: itemId,				// string
		Category: "",			// string
		Title: "",				// string
		Description: "",		// string
		Images: "",				// string
		Price: 0,				// number
		Vat: 0,					// number
		Currency: "",			// string
		Weight: 0,				// number
		WeightUnit: "",			// string
		DeliveryTime: "",		// string
		DiscountExpression: "",	// string
		DiscountMessage: ""		// string
	};

	function init()
	{
		me.InitializeModel();
	}

	this.GetModelName = function()
	{
		return "Product";
	}

	this.GetProperties = function()
	{
		return properties;
	}

	this.GetWebServiceUrls = function()
	{
		var urls =
		{
			Create: JSShop.WebService.Products.Create,
			Retrieve: JSShop.WebService.Products.Retrieve,
			RetrieveAll: JSShop.WebService.Products.RetrieveAll,
			Update: JSShop.WebService.Products.Update,
			Delete: JSShop.WebService.Products.Delete
		}

		return urls;
	}

	this.OLDOLDOLDCalculateDiscount = function(units)
	{
		Fit.Validation.ExpectInteger(units);

		/* Example - how to use it:
		x = new JSShop.Models.Product("10001");
		x.Retrieve(function(model)
		{
			x.DiscountExpression("(units >= 3 ? price : 0)"); // Buy 3+, get one for free
			x.DiscountExpression("(units >= 10 ? units * price * 0.20 : " + x.DiscountExpression() + ")"); // Buy 10+ to get 20% discount, otherwise use rule above

			console.log("Discount", x.CalculateDiscount(15));
		}); */

		var ex = me.DiscountExpression();

		if (ex === "")
			return 0.0;

		// Security validation

		//ex = ex.replace(/Math\.[a-z]+/gi, ""); // Allow Math function calls (disabled - most likely not supported natively on backend)
		ex = ex.replace(/ |[0-9]|\*|\+|\-|\/|=|&|\||!|\.|:|\(|\)|>|<|\?|true|false/g, ""); // Allow various math/comparison/logical operations
		ex = ex.replace(/units|price|vat|currency|weight|weightunit/g, ""); // Allow use of predefined variables

		if (ex !== "") // All valid elements were removed above, so if ex contains anything, it is potentially a security threat
			throw "InvalidDiscountExpression: Invalid and potentially insecure DiscountExpression detected - evaluation aborted";

		// Add data to expression

		var expr = "";
		expr += "var units = " + units + ";";
		expr += "var price = " + me.Price() + ";";
		expr += "var vat = " + me.Vat() + ";";
		expr += "var currency = \"" + me.Currency() + "\";";
		expr += "var weight = \"" + me.Weight() + "\";";
		expr += "var weightunit = \"" + me.WeightUnit() + "\";";
		expr += "(" + me.DiscountExpression() + ");";

		// Evaluate, validate, and return

		var discount = eval(expr); // May throw error on invalid expression

		var isNumber = /^\-?([0-9]+(\.[0-9]+)?)$/.test(discount.toString()); // Both positive and negative values are allowed

		if (isNumber === false || typeof(discount) !== "number")
			throw "NotNumber: Discount Expression did not produce a valid value (number)";

		return discount;
	}

	this.CalculateDiscount = function(units) // TODO: Rename to CostCorrection ???
	{
		Fit.Validation.ExpectInteger(units);

		if (me.DiscountExpression() === "")
			return 0.0;

		var result = calculateExpression(units, me.DiscountExpression(), function(res)
		{
			var isNumber = /^\-?([0-9]+(\.[0-9]+)?)$/.test(res.toString()); // Both positive and negative values are allowed
			return (isNumber === true && typeof(res) === "number");
		});

		return result;
	}

	this.CalculateDiscountMessage = function(units)
	{
		Fit.Validation.ExpectInteger(units);

		if (me.DiscountMessage() === "")
			return "";

		var result = calculateExpression(units, me.DiscountMessage(), function(res)
		{
			return (typeof(res) === "string" && Fit.String.StripHtml(res) === res);
		});

		return result;
	}

	function calculateExpression(units, expression, resultValidationCallback)
	{
		Fit.Validation.ExpectInteger(units);
		Fit.Validation.ExpectStringValue(expression);
		Fit.Validation.ExpectFunction(resultValidationCallback);

		var ex = expression;

		// Security validation

		ex = ex.replace(/\r|\n|\t/g, ""); // Allow use of line breaks and tabs
		ex = ex.replace(/\/\*.*?\*\//g, ""); // Allow use of /*..*/ comments - ? after quantifier makes the match non-greedy
		ex = ex.replace(/data|units|price|vat|currency|weight|weightunit/g, ""); // Allow use of predefined variables
		ex = ex.replace(/JSShop.Floor|JSShop.Ceil|JSShop.Round/g, ""); // Allow use of functions
		ex = ex.replace(/ |[0-9]|\*|\+|\-|\/|%|=|&|\||!|\.|:|\(|\)|\[|\]|>|<|\?|true|false/g, ""); // Allow various math/comparison/logical operations
		ex = ex.replace(/(["']).*?\1/g, ""); // Allow use of double quoted and single quoted strings - ? after quantifiers makes the match non-greedy

		var secure = (ex === ""); // All valid elements were removed above, so if ex contains anything, it is potentially a security threat
		//secure = (secure === true && /(["']).*?<.*?>.*?\1/.test(expression) === false) // Make sure HTML is not found in quoted strings - ? after quantifiers makes the match non-greedy - https://regex101.com/r/dL1bI2/2
		/////secure = (secure === true && /(["'])[^\1.]*?<[^\1.]*?>[^\1.]*?\1/.test(expression) === false) // Make sure HTML is not found in quoted strings - ? after quantifiers makes the match non-greedy - https://regex101.com/r/dL1bI2/3

		/*ex = ex.replace(/units|price|vat|currency|weight|weightunit/g, ""); // Allow use of predefined variables
		ex = ex.replace(/index\[.+?\]/g, ""); // Allow use of price index - ? after quantifiers makes the match non-greedy - https://regex101.com/r/xY9eP6/1
		ex = ex.replace(/ |[0-9]|\*|\+|\-|\/|%|=|&|\||!|\.|:|\(|\)|>|<|\?|true|false/g, ""); // Allow various math/comparison/logical operations
		ex = ex.replace(/".*?"|'.*?'/g, ""); // Allow use of double quoted and single quoted strings - ? after quantifiers makes the match non-greedy - https://regex101.com/r/zI6lT0/1

		var secure = (ex === ""); // All valid elements were removed above, so if ex contains anything, it is potentially a security threat
		secure = (secure === true && /".*?<.*?>.*?"|'.*?<.*?>.*?'/.test(expression) === false) // Make sure HTML is not found in quoted strings - ? after quantifiers makes the match non-greedy - https://regex101.com/r/dL1bI2/1*/

		if (secure === false)
			throw "InvalidExpression: Invalid and potentially insecure expression detected - evaluation aborted";

		// Add data to expression

		var expr = "";
		expr += "var units = " + units + ";";
		expr += "var price = " + me.Price() + ";";
		expr += "var vat = " + me.Vat() + ";";
		expr += "var currency = \"" + me.Currency() + "\";";
		expr += "var weight = \"" + me.Weight() + "\";";
		expr += "var weightunit = \"" + me.WeightUnit() + "\";";
		expr += "var data = " + ((JSShop.Settings.AdditionalData !== null) ? JSON.stringify(JSShop.Settings.AdditionalData) : {}) + ";";
		expr += "(" + expression.replace(/JSShop\.Floor/g, "Math.floor").replace(/JSShop\.Ceil/g, "Math.ceil").replace(/JSShop\.Round/g, "Math.round") + ");";

		// Evaluate, validate, and return

		var result = eval(expr); // May throw error on invalid expression

		if (typeof(result) === "string")
			result = Fit.String.EncodeHtml(result);

		if (resultValidationCallback(result) === false)
			throw "InvalidExpressionResult: Expression did not produce a valid value";

		return result;
	}

	init();
}
JSShop.Models.Product.RetrieveAll = function(category, cbSuccess, cbFailure)
{
	Fit.Validation.ExpectString(category);
	Fit.Validation.ExpectFunction(cbSuccess);
	Fit.Validation.ExpectFunction(cbFailure, true);

	var match = ((category !== "") ? [[{ Field: "Category", Operator: "=", Value: category }]] : []); // Multi dimensional: [ [match1 AND match2] OR [matchA AND matchB] OR ... ]
	JSShop.Models.Base.RetrieveAll(JSShop.Models.Product, "Id", match, cbSuccess, cbFailure);
}
