if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Models.Config = function(id)
{
	Fit.Validation.ExpectStringValue(id, true);
	Fit.Core.Extend(this, JSShop.Models.Base).Apply("-1");

	var me = this;

	var properties =
	{
		Basic:
		{
			TermsPage: "Terms.html",
			ReceiptPage: "Receipt.html",
			ShopBccEmail: "copy@domain.com"
		},
		MailTemplates:
		{
			Confirmation: "Confirmation.html",	// Expression enabled
			Invoice: "Invoice.html",			// Expression enabled
			Templates:
			[
				{ Title: "Confirmation.html", Subject: "Confirmation", Content: "" },	// Subject and Content may contain place holders
				{ Title: "Invoice.html", Subject: "Invoice", Content: "" }				// Subject and Content may contain place holders
			]
		},
		PaymentMethods:
		[
			{
				Module: "DIBS",
				Title: "Credit Card",
				Enabled: true,
				Settings:
				[
					{ Title: "Merchant ID", Value: "39461770" },
					{ Title: "Encryption Key", Value: "JFq9w72d3kh:f-2863@s.3l:l62kjfo-oo_u623467GhS" }
				]
			}
		],
		CostCorrections: // Expression enabled
		[
			{
				CostCorrection: "price * -0.10",
				Vat: "25",
				Message: "'10% Christmas discount'",
			},
			{
				CostCorrection: "price < 100 ? 5 : 0",
				Vat: "25",
				Message: "'Shipping fee'"
			},
			{
				CostCorrection: "",
				Vat: "",
				Message: ""
			}
		],
		AdditionalData: "{}" // Value exposed as JSON object to expression enabled fields as the 'data' variable
	};

	function init()
	{
		me.InitializeModel();
	}

	this.GetModelName = function()
	{
		return "Config";
	}

	this.GetProperties = function()
	{
		return properties;
	}

	this.GetWebServiceUrls = function()
	{
		var urls =
		{
			Retrieve: JSShop.WebService.Configuration.Retrieve,
			Update: JSShop.WebService.Configuration.Update
		}

		return urls;
	}

	this.Create = function()
	{
		Fit.Validation.ThrowError("Not supported");
	}

	this.Delete = function()
	{
		Fit.Validation.ThrowError("Not supported");
	}

	init();
}
JSShop.Models.Config.RetrieveAll = function(id, cbSuccess, cbFailure)
{
	Fit.Validation.ThrowError("Not supported");
}
JSShop.Models.Config.Current = new JSShop.Models.Config("-1");


// TEST:

/*
//STEP 1:
JSShop.Models.Config.Current.Retrieve(function(req, model) { console.log(model.GetProperties()) });

//STEP 2:
var loop = function(obj)
{
    if (obj.length !== undefined && obj.pop !== undefined && obj.push !== undefined)
    {
        for (var i = 0 ; i < obj.length ; i++)
        {
            if (typeof(obj[i]) === "string")
                obj[i] = obj[i].replace(/\|ABC123\|/g, "");
                //obj[i] += "|ABC123|";
            else
                loop(obj[i]);
        }
    }
    else
    {
        Fit.Array.ForEach(obj, function(k)
        {
            if (k === "Module" || (typeof(obj[k]) === "string" && obj[k].indexOf(".html") > -1))
                return;

            if (typeof(obj[k]) === "string")
                obj[k] = obj[k].replace(/\|ABC123\|/g, "");
                //obj[k] += "|ABC123|";
            else
                loop(obj[k]);
        });
    }
}
loop(JSShop.Models.Config.Current.GetProperties());
console.log(JSShop.Models.Config.Current.GetProperties());

// STEP 3:
JSShop.Models.Config.Current.Update();

*/
