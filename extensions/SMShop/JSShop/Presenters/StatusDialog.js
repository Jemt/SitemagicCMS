if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.StatusDialog = function()
{
	Fit.Core.Extend(this, JSShop.Presenters.Base).Apply();

	var dialog = null;
	var textElm = null;
	var status = null;
	var warnId = -1;
	var isOpen = false;

	function init()
	{
		dialog = new Fit.Controls.Dialog();

		var container = dialog.GetDomElement().children[0];

		textElm = Fit.Dom.CreateElement("<span></span>");
		Fit.Dom.Add(container, textElm);

		status = new Fit.Controls.ProgressBar("JSShopStatusDialog" + Fit.Data.CreateGuid());
		status.Width(100, "%");
		Fit.Dom.Add(container, status.GetDomElement());
	}

	this.GetDomElement = function()
	{
		return dialog.GetDomElement();
	}

	this.WarnOnExit = function(newVal, msg)
	{
		Fit.Validation.ExpectBoolean(newVal, true);
		Fit.Validation.ExpectString(msg, true);

		if (Fit.Validation.IsSet(newVal) === true && warnId !== -1)
		{
			Fit.Events.RemoveHandler(window, warnId);
			warnId = -1;
		}

		if (newVal === true)
		{
			warnId = Fit.Events.AddHandler(window, "beforeunload", function(e)
			{
				if (isOpen === false)
					return;

				var ev = Fit.Events.GetEvent(e);
				ev.returnValue = (msg ? msg : "Are you sure?");

				return ev.returnValue;
			});
		}

		return (warnId !== -1);
	}

	this.Text = function(newVal)
	{
		Fit.Validation.ExpectString(newVal, true);
		return Fit.Dom.Text(textElm, newVal);
	}

	this.Progress = function(newVal)
	{
		Fit.Validation.ExpectInteger(newVal, true);
		return status.Progress(newVal);
	}

	this.Modal = function(newVal)
	{
		Fit.Validation.ExpectBoolean(newVal, true);
		return dialog.Modal(newVal);
	}

	this.Open = function()
	{
		isOpen = true;
		dialog.Open();
	}

	this.Close = function()
	{
		isOpen = false;
		dialog.Close();
	}

	this.Dispose = function()
	{
		dialog.Dispose();

		if (warnId !== -1)
		{
			Fit.Events.RemoveHandler(window, warnId);
		}

		dialog = textElm = status = warnId = isOpen = null;
	}

	init();
}
