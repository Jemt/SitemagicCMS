if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.OrderDetails = function(model)
{
	Fit.Validation.ExpectInstance(model, JSShop.Models.Order);

	var view = null;
	var lang = JSShop.Language.Translations.OrderDetails;

	function init()
	{
		// Load view

		view = document.createElement("div");

		if (document.querySelector("link[href*='/Views/OrderDetails.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/OrderDetails.css");

		var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/OrderDetails.html");
		req.OnSuccess(function(sender)
		{
			//view.innerHTML = req.GetResponseText();

			var html = req.GetResponseText();

			html = html.replace(/{\[CustomerDetailsHeadline\]}/, lang.CustomerDetails);
			html = html.replace(/{\[AlternativeAddressHeadline\]}/, lang.AlternativeAddress);

			var data = [ "Company", "FirstName", "LastName", "Address", "ZipCode", "City", "Phone", "Email" ];
			data = Fit.Array.Merge(data, [ "AltCompany", "AltFirstName", "AltLastName", "AltAddress", "AltZipCode", "AltCity" ]);

			Fit.Array.ForEach(data, function(d)
			{
				html = html.replace(new RegExp("{\\[" + d + "\\]}", "g"), model[d]());
			});


			view.innerHTML = html;

			//var customer = view.querySelector("div.JSShopCustomerDetails");
			var altAddress = view.querySelector("div.JSShopAlternativeAddress");

			altAddress.style.display = ((model.AltAddress() !== "") ? "block" : "none");
		});
		req.Start();

		/*if (document.querySelector("link[href*='/Views/OrderForm.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/OrderForm.css");

		var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/OrderForm.html");
		req.OnSuccess(function(sender)
		{
			view.innerHTML = req.GetResponseText();

			var addressForm = view.querySelector("div.JSShopAddress");
			var altAddressForm = view.querySelector("div.JSShopAlternativeAddress");
			var paymentForm = view.querySelector("div.JSShopPaymentForm");

			Fit.Dom.Remove(paymentForm);
			Fit.Dom.Remove(addressForm.querySelector("#JSShop-RememberMe-Label"));
			Fit.Dom.Remove(addressForm.querySelector("#JSShop-RememberMe-Control"));
			Fit.Dom.Remove(addressForm.querySelector("#JSShop-AlternativeAddress-Label"));
			Fit.Dom.Remove(addressForm.querySelector("#JSShop-AlternativeAddress-Control"));

			altAddressForm.style.display = ((model.AltAddress() !== "") ? "block" : "none");

			Fit.Dom.Add(addressForm.querySelector("#JSShop-Headline-Label"), document.createTextNode(lang.CustomerDetails));

			Fit.Dom.Add(addressForm.querySelector("#JSShop-Company-Label"), document.createTextNode(lang.Company));
			addressForm.querySelector("#JSShop-Company-Control").innerHTML = model.Company();

			Fit.Dom.Add(addressForm.querySelector("#JSShop-FirstName-Label"), document.createTextNode(lang.FirstName));
			addressForm.querySelector("#JSShop-FirstName-Control").innerHTML = model.FirstName();

			Fit.Dom.Add(addressForm.querySelector("#JSShop-LastName-Label"), document.createTextNode(lang.LastName));
			addressForm.querySelector("#JSShop-LastName-Control").innerHTML = model.LastName();

			Fit.Dom.Add(addressForm.querySelector("#JSShop-Address-Label"), document.createTextNode(lang.Address));
			addressForm.querySelector("#JSShop-Address-Control").innerHTML = model.Address();

			Fit.Dom.Add(addressForm.querySelector("#JSShop-ZipCode-Label"), document.createTextNode(lang.ZipCode));
			addressForm.querySelector("#JSShop-ZipCode-Control").innerHTML = model.ZipCode();

			Fit.Dom.Add(addressForm.querySelector("#JSShop-City-Label"), document.createTextNode(lang.City));
			addressForm.querySelector("#JSShop-City-Control").innerHTML = model.City();

			Fit.Dom.Add(addressForm.querySelector("#JSShop-Email-Label"), document.createTextNode(lang.Email));
			addressForm.querySelector("#JSShop-Email-Control").innerHTML = model.Email();

			Fit.Dom.Add(addressForm.querySelector("#JSShop-Phone-Label"), document.createTextNode(lang.Phone));
			addressForm.querySelector("#JSShop-Phone-Control").innerHTML = model.Phone();

			Fit.Dom.Add(addressForm.querySelector("#JSShop-Message-Label"), document.createTextNode(lang.Message));
			addressForm.querySelector("#JSShop-Message-Control").innerHTML = model.Message();
		});
		req.Start();*/
	}

	this.Render = function(toElement)
	{
		Fit.Validation.ExpectDomElement(toElement, true);

		if (Fit.Validation.IsSet(toElement) === true)
		{
			Fit.Dom.Add(toElement, view);
		}
		else
		{
			var script = document.scripts[document.scripts.length - 1];
			Fit.Dom.InsertBefore(script, view);
		}
	}

	init();
}
