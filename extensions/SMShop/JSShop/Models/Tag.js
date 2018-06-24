if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Models.Tag = function(tagId)
{
	Fit.Validation.ExpectStringValue(tagId);
	Fit.Core.Extend(this, JSShop.Models.Base).Apply(tagId);

	var me = this;

	var properties =
	{
		Id: tagId,				// string
		Category: "",			// string
		Title: ""				// string
	};

	function init()
	{
		me.InitializeModel();
	}

	this.GetModelName = function()
	{
		return "Tag";
	}

	this.GetProperties = function()
	{
		return properties;
	}

	this.GetWebServiceUrls = function()
	{
		var urls =
		{
			Create: JSShop.WebService.Tags.Create,
			Retrieve: JSShop.WebService.Tags.Retrieve,
			RetrieveAll: JSShop.WebService.Tags.RetrieveAll,
			Update: JSShop.WebService.Tags.Update,
			Delete: JSShop.WebService.Tags.Delete
		}

		return urls;
	}

	init();
}
JSShop.Models.Tag.RetrieveAll = function(category, cbSuccess, cbFailure)
{
	Fit.Validation.ExpectString(category);
	Fit.Validation.ExpectFunction(cbSuccess);
	Fit.Validation.ExpectFunction(cbFailure, true);

	var match = [[{ Field: "Category", Operator: "=", Value: category }]]; // Multi dimensional: [ [match1 AND match2] OR [matchA AND matchB] OR ... ]
	JSShop.Models.Base.RetrieveAll(JSShop.Models.Tag, "Id", match, cbSuccess, cbFailure);
}
