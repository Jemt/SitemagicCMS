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
			TermsPage: "",		// Required
			ReceiptPage: "",	// Required
			ShopBccEmail: ""	// Required
		},
		MailTemplates: // Required object
		{
			Confirmation: "",	// Required - Expression enabled
			Invoice: "",		// Required - Expression enabled
			Templates:			// Required - but must be an array if defined and contained objects must define all properties
			[
				//{ Name: "Confirmation.html", Title: "Confirmation e-mail", Subject: "Confirmation", Content: "" },	// Subject and Content may contain place holders
				//{ Name: "Invoice.html", Title: "Invoice e-mail", Subject: "Invoice", Content: "" }					// Subject and Content may contain place holders
			]
		},
		PaymentMethods: // Required array - any contained object must define all properties
		[
			/*{
				Module: "DIBS",
				Title: "Credit Card",
				Enabled: true,
				Settings:
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
		AdditionalData: "", // Required JSON string (can be empty but must be set like the other outer properties for consistency) - value exposed as JSON object to expression enabled fields as the 'data' variable
		Identifiers: // Required object, including all contained objects and properties
		{
			// Dirty flag is used to tell backend whether to update Value or not.
			// If Config model was loaded yesterday, 10 orders have been placed since,
			// and Config model is updated (pushed to backend) today, then NextOrderId would
			// be updated with an outdated value unless the Dirty flag prevents it by default.

			NextOrderId:	{ Value: 1, Dirty: false },
			NextInvoiceId:	{ Value: 1, Dirty: false }
		}
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
				Fit.Validation.ExpectObject(props.Basic);
				Fit.Validation.ExpectObject(props.MailTemplates);
				Fit.Validation.ExpectArray(props.PaymentMethods);
				Fit.Validation.ExpectArray(props.CostCorrections);
				Fit.Validation.ExpectString(props.AdditionalData);
				Fit.Validation.ExpectObject(props.Identifiers);

				// Validate inner properties
				Fit.Validation.ExpectString(props.Basic.TermsPage);
				Fit.Validation.ExpectString(props.Basic.ReceiptPage);
				Fit.Validation.ExpectString(props.Basic.ShopBccEmail);
				Fit.Validation.ExpectString(props.MailTemplates.Confirmation);
				Fit.Validation.ExpectString(props.MailTemplates.Invoice);
				Fit.Validation.ExpectArray(props.MailTemplates.Templates);
				Fit.Validation.ExpectObject(props.Identifiers.NextOrderId);
				Fit.Validation.ExpectInteger(props.Identifiers.NextOrderId.Value);
				Fit.Validation.ExpectBoolean(props.Identifiers.NextOrderId.Dirty);
				Fit.Validation.ExpectObject(props.Identifiers.NextInvoiceId);
				Fit.Validation.ExpectInteger(props.Identifiers.NextInvoiceId.Value);
				Fit.Validation.ExpectBoolean(props.Identifiers.NextInvoiceId.Dirty);

				// Validate arrays and contained objects

				Fit.Array.ForEach(props.PaymentMethods, function(p)
				{
					Fit.Validation.ExpectString(p.Module);
					Fit.Validation.ExpectString(p.Title);
					Fit.Validation.ExpectBoolean(p.Enabled);
					Fit.Validation.ExpectArray(p.Settings);

					Fit.Array.ForEach(p.Settings, function(s)
					{
						Fit.Validation.ExpectString(s.Title);
						Fit.Validation.ExpectString(s.Value);
					});
				});

				Fit.Array.ForEach(props.CostCorrections, function(c)
				{
					Fit.Validation.ExpectString(c.CostCorrection);
					Fit.Validation.ExpectString(c.Vat);
					Fit.Validation.ExpectString(c.Message);
				});

				Fit.Array.ForEach(props.MailTemplates.Templates, function(t)
				{
					Fit.Validation.ExpectString(t.Name);
					Fit.Validation.ExpectString(t.Title);
					Fit.Validation.ExpectString(t.Subject);
					Fit.Validation.ExpectString(t.Content);
				});
			}
			else if (operation === "Update")
			{
				var props = me.GetProperties();

				// Always assume NextOrderId and NextInvoiceId is not dirty
				// to prevent old values from being send to backend in case Config
				// model is updated multiple times over a period of time.
				props.Identifiers.NextOrderId.Dirty = false;
				props.Identifiers.NextInvoiceId.Dirty = false;
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