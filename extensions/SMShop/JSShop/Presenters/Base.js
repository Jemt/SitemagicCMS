if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.Base = function()
{
	var me = this;

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
		Fit.Dom.Add(toElement, me.GetDomElement());
	}

	init();
}
