if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Models.Config = function(id)
{
	Fit.Validation.ExpectStringValue(id, true);
	Fit.Core.Extend(this, JSShop.Models.Base).Apply("-1");

	var me = this;

	var properties =
	{
		// The Config model is not a flat and simple data structure like all other models.
		// Therefore this model will make sure any data received from the backend honours
		// the "interface" defined below.

		Basic: // Required object
		{
			TermsPage: "",		// Optional
			ReceiptPage: "",	// Options
			ShopBccEmail: ""	// Optional
		},
		MailTemplates: // Required object
		{
			Confirmation: "",	// Optional - Expression enabled
			Invoice: "",		// Optional - Expression enabled
			Templates:			// Optional - but must be an array if defined and contained objects must define all properties
			[
				//{ Title: "Confirmation.html", Subject: "Confirmation", Content: "" },	// Subject and Content may contain place holders
				//{ Title: "Invoice.html", Subject: "Invoice", Content: "" }			// Subject and Content may contain place holders
			]
		},
		PaymentMethods: // Required array - any contained object must define all properties except Settings which is optional
		[
			/*{
				Module: "DIBS",
				Title: "Credit Card",
				Enabled: true,
				Settings: // Optional - but must be an array if defined and contained objects must define all properties
				[
					{ Title: "Merchant ID", Value: "39461770" },
					{ Title: "Encryption Key", Value: "JFq9w72d3kh:f-2863@s.3l:l62kjfo-oo_u623467GhS" }
				]
			}*/
		],
		CostCorrections: // Required array - any contained object must define all properties which are expression enabled
		[
			/*{
				CostCorrection: "",	// "price * -0.10",
				Vat: "",			// "25",
				Message: ""			// "'10% Christmas discount'"
			},
			{
				CostCorrection: "",	// "price < 100 ? 5 : 0",
				Vat: "",			// "25",
				Message: ""			// "'Shipping fee'"
			},
			{
				CostCorrection: "",
				Vat: "",
				Message: ""
			}*/
		],
		AdditionalData: "" // Required JSON string (can be empty but must be set like the other outer properties for consistency) - value exposed as JSON object to expression enabled fields as the 'data' variable
	};

	function init()
	{
		me.InitializeModel();

		me.OnResponse(function(req, model, operation)
		{
			if (operation === "Retrieve")
			{
				// Validate model data to make sure it is valid - the Config model is not a flat simple model like other models.
				// The outer properties (Basic, MailTemplates, PaymentMethods, CostCorrections, and AdditionalData) must be set!
				// Outer properties's child properties can be null, undefined, or empty (string).
				// Objects contained in arrays must have all properties set.

				var props = me.GetProperties();

				// Validate outer properties

				// Outer properties must be defined with appropriate types (required)
				Fit.Validation.ExpectObject(props.Basic);
				Fit.Validation.ExpectObject(props.MailTemplates);
				Fit.Validation.ExpectArray(props.PaymentMethods);
				Fit.Validation.ExpectArray(props.CostCorrections);
				Fit.Validation.ExpectString(props.AdditionalData);

				// Validate inner properties (can be null/undefined but must have the correct types if defined)
				Fit.Validation.ExpectString(props.Basic.TermsPage, true);
				Fit.Validation.ExpectString(props.Basic.ReceiptPage, true);
				Fit.Validation.ExpectString(props.Basic.ShopBccEmail, true);
				Fit.Validation.ExpectString(props.MailTemplates.Confirmation, true);
				Fit.Validation.ExpectString(props.MailTemplates.Invoice, true);
				Fit.Validation.ExpectArray(props.MailTemplates.Templates, true);

				// Validate arrays and contained objects

				Fit.Array.ForEach(props.PaymentMethods, function(p)
				{
					// Defined payment method objects must define almost all properties (Settings is optional)
					Fit.Validation.ExpectString(p.Module);
					Fit.Validation.ExpectString(p.Title);
					Fit.Validation.ExpectBoolean(p.Enabled);
					Fit.Validation.ExpectArray(p.Settings, true);

					if (p.Settings)
					{
						Fit.Array.ForEach(p.Settings, function(s)
						{
							// Defined payment method settings must define all properties
							Fit.Validation.ExpectString(s.Title);
							Fit.Validation.ExpectString(s.Value);
						});
					}
				});

				Fit.Array.ForEach(props.CostCorrections, function(c)
				{
					// Defined cost correction objects must define all properties
					Fit.Validation.ExpectString(c.CostCorrection);
					Fit.Validation.ExpectString(c.Vat);
					Fit.Validation.ExpectString(c.Message);
				});

				if (props.MailTemplates.Templates)
				{
					Fit.Array.ForEach(props.MailTemplates.Templates, function(t)
					{
						// Defined mail template objects must define all properties
						Fit.Validation.ExpectString(t.Title);
						Fit.Validation.ExpectString(t.Subject);
						Fit.Validation.ExpectString(t.Content);
					});
				}
			}
		})
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
