function SMCookieConsent()
{
	var me = this;
	this.Text = "We use cookies to give you the best possible experience";
	this.Deny = "Deny";
	this.Accept = "Accept";
	this.Position = "bottom";	// "top" or "bottom"
	this.HideHours = 12;		// How many hours until consent dialog is shown again
	this.Modules = [];			// [{Name:string, Description:String, Code:string}]

	this.Render = function()
	{
		if (window.parent !== window)
		{
			return; // Currently within a dialog/popup - do not render consent dialog in this case
		}

		// Panel

		var panel = document.createElement("div");
		panel.className = "SMCookieConsentPanel";
		panel.style[me.Position.toLowerCase() === "top" ? "top" : "bottom"] = "0px";
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
			var chk = { Name: module.Name, Checkbox: createCheckbox(module.Name, module.Description, true), Code: module.Code };

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
			document.body.removeChild(panel);
			SMCookie.SetCookie("SMCookieConsentAllowed", "", me.HideHours * 60 * 60);
		});
		buttonDeny.className += " SMCookieConsentButtonDeny";
		buttons.appendChild(buttonDeny);

		// Accept button

		var buttonAccept = createButton(me.Accept, function()
		{
			var allowed = "";

			for (var i = 0 ; i < checkboxes.length ; i++)
			{
				if (checkboxes[i].Checkbox.IsChecked === true)
				{
					allowed += (allowed !== "" ? "|#|" : "") + checkboxes[i].Name;
					eval(checkboxes[i].Code);
				}
			}

			document.body.removeChild(panel);
			SMCookie.SetCookie("SMCookieConsentAllowed", encodeURIComponent(allowed), me.HideHours * 60 * 60); // Encoding cookie value to allow use of semicolon which is used in unicode encoding (e.g. &#1234;)
		});
		buttonAccept.className += " SMCookieConsentButtonAccept";
		buttons.appendChild(buttonAccept);

		// SMDesigner integration

		panel.className += " SMDesignerElement"
		panel.setAttribute("data-id", "Cookie Panel");
		panel.setAttribute("data-preserve", "true");

		buttonDeny.className += " SMDesignerElement"
		buttonDeny.setAttribute("data-id", "Cookie Deny Button");
		buttonDeny.setAttribute("data-preserve", "true");

		buttonAccept.className += " SMDesignerElement"
		buttonAccept.setAttribute("data-id", "Cookie Accept Button");
		buttonAccept.setAttribute("data-preserve", "true");
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
}