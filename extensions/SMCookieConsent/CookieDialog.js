function SMCookieConsent()
{
	var me = this;
	this.Text = "We use cookies to give you the best possible experience";
	this.Deny = "Deny";
	this.AcceptSelected = "Accept selected";
	this.AcceptAll = "Accept all";
	this.Position = "bottom";	// "top" or "bottom" or "center"
	this.Modal = false;			// Whether to prevent interaction with website until cookies have been denied/accepted
	this.HideHours = 12;		// How many hours until consent dialog is shown again
	this.Modules = [];			// [{Name:string, Description:String, Code:string}]
	this.WebService = null;		// URL to webservice accepting object array - e.g. consent=encodeURIComponent(JSON.stringify({Statistics:true,Marketing:false}))

	this.Render = function()
	{
		if (SMCookieConsent.SuppressRender === true)
		{
			return;
		}

		if (window.parent !== window)
		{
			return; // Currently within a dialog/popup - do not render consent dialog in this case
		}

		// Modal background layer

		var bgLayer = document.createElement("div");
		bgLayer.className = "SMCookieConsentPanelBackground";
		bgLayer.style.display = me.Modal === false ? "none" : "";
		document.body.appendChild(bgLayer);

		// Panel

		var panel = document.createElement("div");
		panel.className = "SMCookieConsentPanel";
		panel.setAttribute("data-position", me.Position.toLowerCase());
		document.body.appendChild(panel);

		// Description

		var description = document.createElement("div");
		description.className = "SMCookieConsentDescription";
		description.innerHTML = me.Text;
		panel.appendChild(description);

		// Action panel

		var actions = document.createElement("div");
		actions.className = "SMCookieConsentActions"
		panel.appendChild(actions);

		// Checkbox panel

		var checkboxContainer = document.createElement("div");
		checkboxContainer.className = "SMCookieConsentCheckboxes";
		actions.appendChild(checkboxContainer);

		var checkboxes = [];

		for (var i = 0 ; i < me.Modules.length ; i++)
		{
			var module = me.Modules[i];
			var chk = { Name: module.Name, Checkbox: createCheckbox(module.Name, module.Description, module.Checked), Code: module.Code };

			checkboxes.push(chk);
			checkboxContainer.appendChild(chk.Checkbox);
		}

		// Button panel

		var buttons = document.createElement("div");
		buttons.className = "SMCookieConsentButtons";
		actions.appendChild(buttons);

		// Deny button

		var buttonDeny = createButton(me.Deny, function()
		{
			var consent = {};
			for (var i = 0 ; i < checkboxes.length ; i++)
			{
				consent[checkboxes[i].Name] = false;
			}
			submitConsent(consent);

			document.body.removeChild(bgLayer);
			document.body.removeChild(panel);

			SMCookie.SetCookie("SMCookieConsentAllowed", "", me.HideHours * 60 * 60);
		});
		buttonDeny.className += " SMCookieConsentButtonDeny";
		buttons.appendChild(buttonDeny);

		// Accept buttons

		var acceptCookies = function(forceAll)
		{
			var allowed = "";
			var consent = {};

			for (var i = 0 ; i < checkboxes.length ; i++)
			{
				if (forceAll === true || checkboxes[i].Checkbox.IsChecked === true)
				{
					allowed += (allowed !== "" ? "|#|" : "") + checkboxes[i].Name;
					eval(checkboxes[i].Code);
				}

				consent[checkboxes[i].Name] = checkboxes[i].Checkbox.IsChecked;
			}

			submitConsent(consent);

			document.body.removeChild(bgLayer);
			document.body.removeChild(panel);

			SMCookie.SetCookie("SMCookieConsentAllowed", encodeURIComponent(allowed), me.HideHours * 60 * 60); // Encoding cookie value to allow use of semicolon which is used in unicode encoding (e.g. &#1234;)
		};

		var buttonAcceptSelected = createButton(me.AcceptSelected, function()
		{
			acceptCookies();
		});
		buttons.appendChild(buttonAcceptSelected);

		var buttonAcceptAll = createButton(me.AcceptAll, function()
		{
			acceptCookies(true);
		});
		buttons.appendChild(buttonAcceptAll);

		// SMDesigner integration

		panel.className += " SMDesignerElement"
		panel.setAttribute("data-id", "Cookie Panel");
		panel.setAttribute("data-preserve", "true");

		buttonDeny.className += " SMDesignerElement"
		buttonDeny.setAttribute("data-id", "Cookie Deny Button");
		buttonDeny.setAttribute("data-preserve", "true");

		buttonAcceptSelected.className += " SMDesignerElement"
		buttonAcceptSelected.setAttribute("data-id", "Cookie Accept Selected Button");
		buttonAcceptSelected.setAttribute("data-preserve", "true");

		buttonAcceptAll.className += " SMDesignerElement"
		buttonAcceptAll.setAttribute("data-id", "Cookie Accept Button");
		buttonAcceptAll.setAttribute("data-preserve", "true");
	}

	function createCheckbox(title, description, checked)
	{
		var wrapper = document.createElement("div");
		wrapper.className = "SMCookieConsentCheckbox";
		wrapper.IsChecked = checked || false;

		var chk = document.createElement("input");
		chk.type = "checkbox";
		chk.checked = wrapper.IsChecked;
		chk.onchange = function()
		{
			wrapper.IsChecked = chk.checked;
		}
		wrapper.appendChild(chk);

		var label = document.createElement("span");
		label.innerHTML = title;
		label.onclick = function()
		{
			chk.checked = !chk.checked;
			wrapper.IsChecked = chk.checked;
		}
		wrapper.appendChild(label);

		if (description)
		{
			var desc = document.createElement("span");
			desc.innerHTML = " (?)";
			desc.title = SMStringUtilities.UnicodeDecode(description);
			desc.onclick = function()
			{
				alert(SMStringUtilities.UnicodeDecode(description));
			}
			wrapper.appendChild(desc);
		}

		return wrapper;
	}

	function createButton(title, cb)
	{
		var button = document.createElement("div");
		button.className = "SMCookieConsentButton"
		button.innerHTML = title;
		button.onclick = function()
		{
			if (button.className.indexOf("SelectableElement") > -1)
				return; // SMDesigner is open - do nothing

			cb();
		};

		return button;
	}

	function submitConsent(consent)
	{
		if (me.WebService)
		{
			var r = new SMHttpRequest(me.WebService, true);
			r.SetData("consent=" + encodeURIComponent(JSON.stringify(consent))); // Encoding consent object as special characters may be contained - e.g. equal sign and ampersand which are used in form data to separate arguments
			r.Start();
		}
	}
}

SMCookieConsent.ResetConsent = function()
{
	SMCookie.RemoveCookie('SMCookieConsentAllowed');
	location.href=location.href;
}

SMCookieConsent.SuppressRender = false; // Allow pages to set this property to True to suppress CookieDialog - e.g. for the cookie information page