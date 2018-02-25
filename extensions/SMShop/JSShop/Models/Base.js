if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Models.Base = function(itemId)
{
	// All models must have a unique ID - RetrieveAll(..) expects to be able to
	// create a template model with a constructor accepting exactly one argument,
	// and Update/Delete/Retrieve operations depend on a unique ID to identify a record.
	Fit.Validation.ExpectStringValue(itemId);

	var me = this;
	var urls = null;
	var complex = false;

	this.InitializeModel = function(isComplex) // Derivatives MUST call InitializeModel once GetProperties() has been overridden, in order for Get/Set functions to be created
	{
		Fit.Validation.ExpectBoolean(isComplex, true);

		// Get WS Urls

		urls = me.GetWebServiceUrls();

		/*if (isComplex === true)
		{
			complex = isComplex;
			return;
		}*/

		// Create Get/Set functions - examples:
		// var title = model.Title();
		// model.Title("New title");

		var properties = me.GetProperties();

		Fit.Array.ForEach(properties, function(prop)
		{
			if (typeof(properties[prop]) === "number")
			{
				me[prop] = createGetSet(function() { return me.GetProperties()[prop] }, function(val) { me.GetProperties()[prop] = val }, Fit.Validation.ExpectNumber);
			}
			else if (typeof(properties[prop]) === "string")
			{
				me[prop] = createGetSet(function() { return me.GetProperties()[prop] }, function(val) { me.GetProperties()[prop] = val }, Fit.Validation.ExpectString);
			}
			else if (typeof(properties[prop]) === "object")
			{
				me[prop] = createGetSet(function() { return me.GetProperties()[prop] }, function(val) { me.GetProperties()[prop] = val }, null);
			}
		});
	}

	this.GetModelName = function() // Returns model name - used to synchronize data with data source (e.g. database table) in backend
	{
		Fit.Validation.ThrowError("Missing implementation");
	}

	this.GetProperties = function() // Returns all properties - used to synchronize data with backend, and to determine data types - so initial values are important!
	{
		Fit.Validation.ThrowError("Missing implementation");
	}

	this.GetWebServiceUrls = function() // Returns all WebService URLs as JSON object - determines supported CRUD functions: { Create: "", Retrieve: "", Update: "", Delete: "" }
	{
		Fit.Validation.ThrowError("Missing implementation");
	}

	/*this.IsComplex = function()
	{
		return complex;
	}*/

	this.Create = function(cbSuccess, cbFailure)
	{
		Fit.Validation.ExpectFunction(cbSuccess, true);
		Fit.Validation.ExpectFunction(cbFailure, true);

		if (Fit.Validation.IsSet(urls.Create) === false)
			Fit.Validation.ThrowError("WebService URL not configured");

		var req = JSShop.Models.Base.CreateRequest(me, "Create", cbSuccess, cbFailure);
		req.SetData({ Model: me.GetModelName(), Properties: Fit.Core.Clone(me.GetProperties()), Operation: "Create" });
		req.Start();
	}

	this.Retrieve = function(cbSuccess, cbFailure)
	{
		Fit.Validation.ExpectFunction(cbSuccess, true);
		Fit.Validation.ExpectFunction(cbFailure, true);

		if (Fit.Validation.IsSet(urls.Retrieve) === false)
			Fit.Validation.ThrowError("WebService URL not configured");

		var req = JSShop.Models.Base.CreateRequest(me, "Retrieve", cbSuccess, cbFailure);
		req.SetData({ Model: me.GetModelName(), Properties: Fit.Core.Clone(me.GetProperties()), Operation: "Retrieve" });
		req.Start();
	}

	this.Update = function(cbSuccess, cbFailure)
	{
		Fit.Validation.ExpectFunction(cbSuccess, true);
		Fit.Validation.ExpectFunction(cbFailure, true);

		if (Fit.Validation.IsSet(urls.Update) === false)
			Fit.Validation.ThrowError("WebService URL not configured");

		var req = JSShop.Models.Base.CreateRequest(me, "Update", cbSuccess, cbFailure);
		req.SetData({ Model: me.GetModelName(), Properties: Fit.Core.Clone(me.GetProperties()), Operation: "Update" });
		req.Start();
	}

	this.Delete = function(cbSuccess, cbFailure)
	{
		Fit.Validation.ExpectFunction(cbSuccess, true);
		Fit.Validation.ExpectFunction(cbFailure, true);

		if (Fit.Validation.IsSet(urls.Delete) === false)
			Fit.Validation.ThrowError("WebService URL not configured");

		var req = JSShop.Models.Base.CreateRequest(me, "Delete", cbSuccess, cbFailure);
		req.SetData({ Model: me.GetModelName(), Properties: Fit.Core.Clone(me.GetProperties()), Operation: "Delete" });
		req.Start();
	}

	// Private

	this.toString = function()
	{
		return "JSShop.Models." + me.GetModelName() + " = " + JSON.stringify(me.GetProperties());
	}

	function createGetSet(getter, setter, validator)
	{
		return function(val)
		{
			if (Fit.Validation.IsSet(validator) === true)
				validator(val, true);

			if (Fit.Validation.IsSet(val) === true)
			{
				setter(val);
			}

			return getter();
		}
	}
}
JSShop.Models.Base.CreateRequest = function(model, operation, cbSuccess, cbFailure)
{
	Fit.Validation.ExpectInstance(model, JSShop.Models.Base);
	Fit.Validation.ExpectString(operation);
	Fit.Validation.ExpectFunction(cbSuccess, true);
	Fit.Validation.ExpectFunction(cbFailure, true);

	var req = new Fit.Http.JsonRequest(model.GetWebServiceUrls()[operation]);

	req.OnRequest(function(sender)
	{
		if (req.SuppressEvents === true) // SuppressEvents is a custom property not defined by Fit.UI
			return;

		if (Fit.Validation.IsSet(JSShop.Events.OnRequest) === true)
		{
			if (JSShop.Events.OnRequest(req, [model], operation) === false)
				return false;
		}
	});
	req.OnSuccess(function(sender)
	{
		if (req.SuppressEvents === true) // SuppressEvents is a custom property not defined by Fit.UI
			return;

		// Update model before firing events

		var resp = req.GetResponseJson();

		if (resp !== null) // Returning model data is optional (often not necessary for Create/Update/Delete)
		{
			Fit.Array.ForEach(model.GetProperties(), function(prop)
			{
				if (resp[prop] === null || resp[prop] === undefined) // WebService may have omitted some properties
					return;

				model[prop](resp[prop]);
			});
		}

		// Fire global success event handler if defined
		if (Fit.Validation.IsSet(JSShop.Events.OnSuccess) === true)
			JSShop.Events.OnSuccess(req, [model], operation);

		// Fire success callback if passed to function
		if (Fit.Validation.IsSet(cbSuccess) === true)
			cbSuccess(req, model); // Do not pass operation - callback is operation specific, e.g. passed to Create function
	});
	req.OnFailure(function(sender)
	{
		if (req.SuppressEvents === true) // SuppressEvents is a custom property not defined by Fit.UI
			return;

		// Fire global error event handler if defined
		if (Fit.Validation.IsSet(JSShop.Events.OnError) === true)
			JSShop.Events.OnError(req, [model], operation);

		// Fire failure callback if passed to function
		if (Fit.Validation.IsSet(cbFailure) === true)
			cbFailure(req, model); // Do not pass operation - callback is operation specific, e.g. passed to Create function
	});

	return req;
}
JSShop.Models.Base.RetrieveAll = function(modelType, idProp, match, cbSuccess, cbFailure)
{
	Fit.Validation.ExpectFunction(modelType);
	Fit.Validation.ExpectString(idProp);
	Fit.Validation.ExpectIsSet(match); // JSON, no type
	Fit.Validation.ExpectFunction(cbSuccess);
	Fit.Validation.ExpectFunction(cbFailure, true);

	var templateModel = new modelType("TemplateModel" + Fit.Data.CreateGuid());

	if (Fit.Validation.IsSet(templateModel.GetWebServiceUrls().RetrieveAll) === false)
		Fit.Validation.ThrowError("WebService URL not configured");

	var req = JSShop.Models.Base.CreateRequest(templateModel, "RetrieveAll");
	req.SuppressEvents = true; // Do not fire events defined in CreateRequest(..) - it would cause template model created above to be passed to event handlers - we want to pass the models loaded below instead
	req.SetData({ Model: templateModel.GetModelName(), Properties: Fit.Core.Clone(templateModel.GetProperties()), Operation: "RetrieveAll", Match: match });
	req.OnRequest(function(sender)
	{
		if (Fit.Validation.IsSet(JSShop.Events.OnRequest) === true)
		{
			if (JSShop.Events.OnRequest(req, [], "RetrieveAll") === false)
				return false;
		}
	});
	req.OnSuccess(function(sender)
	{
		var json = req.GetResponseJson(); // Throws error if JSON is invalid
		var models = [];

		// Populate models

		Fit.Array.ForEach(json, function(item) // JSON is expected to be an array
		{
			var model = new modelType(item[idProp]); // Assuming WebService returned valid item with expected ID property

			Fit.Array.ForEach(model.GetProperties(), function(prop)
			{
				if (item[prop] === null || item[prop] === undefined) // WebService may have omitted some properties
					return;

				model[prop](item[prop]);
			});

			Fit.Array.Add(models, model);
		});

		// Fire global success event handler if defined
		if (Fit.Validation.IsSet(JSShop.Events.OnSuccess) === true)
			JSShop.Events.OnSuccess(req, models, "RetrieveAll");

		// Fire success callback passed to function
		cbSuccess(req, models);
	});
	req.OnFailure(function(sender)
	{
		// Fire global error event handler if defined
		if (Fit.Validation.IsSet(JSShop.Events.OnError) === true)
			JSShop.Events.OnError(req, [], "RetrieveAll");

		// Fire failure callback if passed to function
		if (Fit.Validation.IsSet(cbFailure) === true)
			cbFailure(req, []);
	});

	req.Start();
}
