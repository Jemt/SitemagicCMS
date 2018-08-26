if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.Base = function()
{
	var me = this;
	var onRenderHandlers = [];
	var onRenderedHandlers = [];

	function init()
	{
	}

	this.GetDomElement = function()
	{
		throw new Error("Not implemented");
	}

	this.Render = function(toElement)
	{
		Fit.Validation.ExpectDomElement(toElement);

		Fit.Array.ForEach(onRenderHandlers, function(cb)
		{
			cb(me);
		});

		Fit.Dom.Add(toElement, me.GetDomElement());

		Fit.Array.ForEach(onRenderedHandlers, function(cb)
		{
			cb(me);
		});
	}

	this.OnRender = function(cb)
	{
		Fit.Validation.ExpectFunction(cb);
		Fit.Array.Add(onRenderHandlers, cb);
	}

	this.OnRendered = function(cb)
	{
		Fit.Validation.ExpectFunction(cb);
		Fit.Array.Add(onRenderedHandlers, cb);
	}

	init();
}
