if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.Config = function()
{
	Fit.Core.Extend(this, JSShop.Presenters.Base).Apply();

	var view = null;
	var tpl = null;
	var config = null;
	var lang = JSShop.Language.Translations.Config;
	var buttons = [];
	var cmdSave = null;
	var codeMirrorLoadState = -1; // -1 = Not loaded, 0 = loading, 1 = loaded

	function init()
	{
		if (document.querySelector("link[href*='/Views/Config.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/Config.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		view = document.createElement("div");

		tpl = new Fit.Template(true);
		tpl.Render(view);

		JSShop.Models.Config.Current.Retrieve(function(sender)
		{
			config = JSShop.Models.Config.Current.GetProperties();

			tpl.LoadUrl(JSShop.GetPath() + "/Views/Config.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"), function(sender, html)
			{
				var cmdBasic = createTabButton("Basic", function(sender) { loadBasicConfig(sender); });
				var cmdMails = createTabButton("E-mail templates", function(sender) { showMailTemplates(sender); });
				var cmdPayMethods = createTabButton("Payment methods", function(sender) { showPayMethods(sender); });
				var cmdAdvanced = createTabButton("Advanced", function(sender) { showAdvanced(sender); });

				cmdMails.Enabled(((config.MailTemplates.Templates && config.MailTemplates.Templates.length > 0) ? true : false));
				cmdPayMethods.Enabled((config.PaymentMethods.length > 0 ? true : false));

				cmdSave = new Fit.Controls.Button(Fit.Data.CreateGuid());
				cmdSave.Title("Save");
				cmdSave.Type(Fit.Controls.Button.Type.Success);
				cmdSave.OnClick(function(sender)
				{
					JSShop.Models.Config.Current.Update(function(req, model)
					{
						Fit.Controls.Dialog.Alert("Done");
					});
				});

				tpl.Content.SectionList.AddItem().Section = cmdBasic.GetDomElement();
				tpl.Content.SectionList.AddItem().Section = cmdMails.GetDomElement();
				tpl.Content.SectionList.AddItem().Section = cmdPayMethods.GetDomElement();
				tpl.Content.SectionList.AddItem().Section = cmdAdvanced.GetDomElement();
				tpl.Content.SectionList.AddItem().Section = cmdSave.GetDomElement();

				cmdBasic.Type(Fit.Controls.Button.Type.Primary);
				loadBasicConfig(cmdBasic);

				tpl.Update();

				// Prepare Code Mirror in the background when UI is ready
				setTimeout(function() { loadCodeMirror(); }, 1000);
			});
		});
	}

	this.GetDomElement = function()
	{
		return view;
	}

	function createTabButton(title, cb)
	{
		Fit.Validation.ExpectString(title);
		Fit.Validation.ExpectFunction(cb);

		var b = new Fit.Controls.Button(Fit.Data.CreateGuid());
		b.Title(title);
		b.Type(Fit.Controls.Button.Type.Default);
		b.OnClick(function(sender)
		{
			cb(sender);
		});

		Fit.Array.Add(buttons, b);

		return b;
	}

	function disposeControls()
	{
		var items = tpl.Content.Properties.GetItems();

		Fit.Array.ForEach(items, function(item)
		{
			item.PropertyValue.FitControl.Dispose();
		});
	}

	function loadBasicConfig(sender)
	{
		setActiveTabButton(sender);
		disposeControls();

		tpl.Content.Properties.Clear();

		tpl.Content.Headline = "Basic";

		var itm = tpl.Content.Properties.AddItem();
		itm.PropertyName = "Receipt";

		if (JSShop.Settings.Pages && JSShop.Settings.Pages.length > 0)
		{
			itm.PropertyValue = createDropDown((config.Basic.ReceiptPage ? config.Basic.ReceiptPage : ""), JSShop.Settings.Pages, function(sender, val) { config.Basic.ReceiptPage = val; })
		}
		else
		{
			itm.PropertyValue = createInput((config.Basic.ReceiptPage ? config.Basic.ReceiptPage : ""), function(sender, val) { config.Basic.ReceiptPage = val; });
		}

		itm = tpl.Content.Properties.AddItem();
		itm.PropertyName = "Terms";

		if (JSShop.Settings.Pages && JSShop.Settings.Pages.length > 0)
		{
			itm.PropertyValue = createDropDown((config.Basic.TermsPage ? config.Basic.TermsPage : ""), JSShop.Settings.Pages, function(sender, val) { config.Basic.TermsPage = val; })
		}
		else
		{
			itm.PropertyValue = createInput((config.Basic.TermsPage ? config.Basic.TermsPage : ""), function(sender, val) { config.Basic.TermsPage = val; });
		}

		itm = tpl.Content.Properties.AddItem();
		itm.PropertyName = "BCC e-mail address (receive copies of all e-mails sent)";
		itm.PropertyValue = createInput(config.Basic.ShopBccEmail ? config.Basic.ShopBccEmail : "", function(sender, val) { config.Basic.ShopBccEmail = val; });

		tpl.Update();
	}

	function showMailTemplates(btn)
	{
		var options = [];
		var templates = (config.MailTemplates.Templates ? config.MailTemplates.Templates : []);

		Fit.Array.ForEach(templates, function(mt)
		{
			Fit.Array.Add(options, mt.Title);
		});

		showOptions(btn, options, function(selectedValue)
		{
			loadMailConfig(btn, selectedValue);
		});
	}

	function loadMailConfig(btn, tplName)
	{
		Fit.Validation.ExpectInstance(btn, Fit.Controls.Button);
		Fit.Validation.ExpectString(tplName);

		setActiveTabButton(btn);
		disposeControls();

		tpl.Content.Properties.Clear();

		var templates = (config.MailTemplates.Templates ? config.MailTemplates.Templates : []);
		var mt = null;

		Fit.Array.ForEach(templates, function(t)
		{
			if (t.Title === tplName)
			{
				mt = t;
				return false;
			}
		});

		tpl.Content.Headline = mt.Title;

		var itmSubject = tpl.Content.Properties.AddItem();
		itmSubject.PropertyName = "Subject";
		itmSubject.PropertyValue = createInput(mt.Subject, function(sender, val) { mt.Subject = val; });

		var autoLineBreaksTag = "<!-- JSShopAutoLineBreaks -->\n";
		var autoLineBreaks = (mt.Content.indexOf(autoLineBreaksTag) === 0);

		var val = mt.Content.replace(autoLineBreaksTag, "");
		val = ((autoLineBreaks === true) ? val.replace(/<br\s?\/?>/g, "\n") : val);

		var itmContent = tpl.Content.Properties.AddItem();
		itmContent.PropertyName = "Content";
		itmContent.PropertyValue = createInput("", function(sender, val)
		{
			if (autoLineBreaks === true)
			{
				mt.Content = autoLineBreaksTag + val.replace(/\n/g, "<br>");
			}
			else
			{
				mt.Content = val;
			}
		});
		itmContent.PropertyValue.FitControl.Width(700);
		itmContent.PropertyValue.FitControl.Height(250);
		itmContent.PropertyValue.FitControl.MultiLine(true);
		itmContent.PropertyValue.FitControl.Maximizable(true);
		itmContent.PropertyValue.FitControl.Value(val); // Set value after MultiLine is enabled to preserve line breaks

		var chk = new Fit.Controls.CheckBox(Fit.Data.CreateGuid());
		chk.Label("Automatically turn line breaks into &lt;br&gt; HTML line breaks (default)");
		chk.Checked(autoLineBreaks);
		chk.OnChange(function(sender)
		{
			if (chk.Checked() === true)
			{
				mt.Content = autoLineBreaksTag + itmContent.PropertyValue.FitControl.Value().replace(/\n/g, "<br>");
				itmContent.PropertyValue.FitControl.Value(itmContent.PropertyValue.FitControl.Value().replace(/<br\s?\/?>/g, "\n"));
				autoLineBreaks = true;
			}
			else
			{
				mt.Content = itmContent.PropertyValue.FitControl.Value().replace(autoLineBreaksTag, "");
				itmContent.PropertyValue.FitControl.Value(itmContent.PropertyValue.FitControl.Value().replace(/\n/g, "<br>"));
				autoLineBreaks = false;
			}
		});
		chk.GetDomElement().FitControl = chk;

		var itmLineBreaks = tpl.Content.Properties.AddItem();
		itmLineBreaks.PropertyValue = chk.GetDomElement();

		tpl.Update();
	}

	function showPayMethods(btn)
	{
		var options = [];

		Fit.Array.ForEach(config.PaymentMethods, function(pm)
		{
			Fit.Array.Add(options, pm.Module);
		});

		showOptions(btn, options, function(selectedValue)
		{
			loadPaymentMethod(btn, selectedValue);
		});
	}

	function loadPaymentMethod(btn, moduleName)
	{
		setActiveTabButton(btn);
		disposeControls();

		tpl.Content.Properties.Clear();

		var module = null;

		Fit.Array.ForEach(config.PaymentMethods, function(pm)
		{
			if (pm.Module === moduleName)
			{
				module = pm;
				return false;
			}
		});

		tpl.Content.Headline = module.Module;

		var chk = new Fit.Controls.CheckBox(Fit.Data.CreateGuid());
		chk.Label("Enable this payment module");
		chk.Checked(module.Enabled);
		chk.OnChange(function(sender)
		{
			module.Enabled = chk.Checked();
		});
		chk.GetDomElement().FitControl = chk;

		var itmTitle = tpl.Content.Properties.AddItem();
		itmTitle.PropertyName = "Title";
		itmTitle.PropertyValue = createInput(module.Title, function(sender, val) { module.Title = val; });;

		Fit.Array.ForEach(module.Settings ? module.Settings : [], function(s)
		{
			var itm = tpl.Content.Properties.AddItem();
			itm.PropertyName = s.Title;
			itm.PropertyValue = createInput(s.Value, function(sender, val) { s.Value = val; });
		});

		var enabledItem = tpl.Content.Properties.AddItem();
		enabledItem.PropertyName = "Enabled";
		enabledItem.PropertyValue = chk.GetDomElement();

		tpl.Update();
	}

	function showAdvanced(btn)
	{
		var options = [];

		Fit.Array.Add(options, (!config.MailTemplates.Templates || config.MailTemplates.Templates.length === 0 ? "!" : "") + "E-mail templates");

		for (var i = 0 ; i < 3 ; i++)
		{
			Fit.Array.Add(options, (!config.CostCorrections || !config.CostCorrections[i] ? "!" : "") + "Cost correction " + (i + 1));
		}

		Fit.Array.Add(options, (config.CostCorrections.length === 0 ? "!" : "") + "Additional data");

		showOptions(btn, options, function(selectedValue)
		{
			loadAdvanced(btn, selectedValue);
		});
	}

	function loadAdvanced(btn, section)
	{
		setActiveTabButton(btn);
		disposeControls();

		tpl.Content.Properties.Clear();

		tpl.Content.Headline = section;

		var itm = null;

		if (section === "E-mail templates") // Lang. support
		{
			itm = tpl.Content.Properties.AddItem();
			itm.PropertyName = "Confirmation E-mail template";
			itm.PropertyValue = createInput(config.MailTemplates.Confirmation ? config.MailTemplates.Confirmation : "", function(sender, val) { config.MailTemplates.Confirmation = val; });

			itm = tpl.Content.Properties.AddItem();
			itm.PropertyName = "Invoice E-mail template";
			itm.PropertyValue = createInput(config.MailTemplates.Invoice ? config.MailTemplates.Invoice : "", function(sender, val) { config.MailTemplates.Invoice = val; });
		}
		else if (section.indexOf("Cost correction") === 0)
		{
			var ccId = parseInt(section.substring(section.length - 1), 10) - 1;
			var cc = config.CostCorrections[ccId];

			if (!cc) // Cost Correction object not provided by backend which may not support multiple cost corrections (or any)
				return;

			itm = tpl.Content.Properties.AddItem();
			itm.PropertyName = "Cost expression";
			itm.PropertyValue = createCostCorrectionExpressionInput(cc.CostCorrection, "number", function(sender, val) { cc.CostCorrection = val; });

			itm = tpl.Content.Properties.AddItem();
			itm.PropertyName = "VAT expression";
			itm.PropertyValue = createCostCorrectionExpressionInput(cc.Vat, "number", function(sender, val) { cc.Vat = val; });

			itm = tpl.Content.Properties.AddItem();
			itm.PropertyName = "Message expression";
			itm.PropertyValue = createCostCorrectionExpressionInput(cc.Message, "string", function(sender, val) { cc.Message = val; });
		}
		else if (section === "Additional data")
		{
			itm = tpl.Content.Properties.AddItem();
			itm.PropertyName = "Additional data (JSON object)";

			itm.PropertyValue = createAdditionalDataExpressionInput(config.AdditionalData, function(sender, val)
			{
				config.AdditionalData = val;

				if (sender.IsValid() === true)
				{
					// Make sure changes to Additional Data is immediately accessible when configuring Cost Corrections
					JSShop.Settings.AdditionalData = (val !== "" ? JSON.parse(val) : {});
				}
			});
		}

		tpl.Update();
	}

	function showOptions(btn, options, cb)
	{
		var ctx = new Fit.Controls.ContextMenu();

		Fit.Array.ForEach(options, function(opt)
		{
			var enabled = (opt.indexOf("!") !== 0); // An option starting with "!" is disabled
			var title = (enabled === true ? opt : opt.substring(1)); // Remove "!" from title if disabled

			var item = new Fit.Controls.ContextMenu.Item(title, title);
			item.Selectable(enabled);

			ctx.AddChild(item);
		});

		ctx.OnSelect(function(sender, item)
		{
			cb(item.Value());
			ctx.Hide();
		});

		var el = btn.GetDomElement();
		var pos = Fit.Dom.GetPosition(el);
		var x = pos.X;
		var y = pos.Y + el.offsetHeight;

		ctx.Show(x, y);
	}

	function createInput(value, onChange)
	{
		Fit.Validation.ExpectString(value);

		var ctl = new Fit.Controls.Input(Fit.Data.CreateGuid());
		ctl.Value(value);
		ctl.OnChange(function(sender) { onChange(sender, ctl.Value()); });
		ctl.Width(700);

		ctl.GetDomElement().FitControl = ctl;

		return ctl.GetDomElement();
	}

	function createExpressionInput(value, onChange)
	{
		var input = createInput(value, onChange);

		input.FitControl.CheckSpelling(false);
		input.FitControl.MultiLine(true);
		input.FitControl.Height(-1); // Reset height to make it resize to fit content
		input.FitControl.Value(value); // Line breaks are not preserved when control is initially single line

		setTimeout(function() { activateCodeMirror(input.FitControl); }, 0); // Postpone - code mirror can only be enabled for elements added to DOM and template has not yet been pushed to DOM

		return input;
	}

	function createCostCorrectionExpressionInput(value, valueType, onChange)
	{
		var input = createExpressionInput(value, onChange);

		input.FitControl.SetValidationCallback(function(val)
		{
			if (val === "")
				return true;

			try
			{
				JSShop.Models.Order.CalculateExpression(100, 0.10, "USD", 0.5, "kg", "5000", "DIBS", "TvCampaign", "A", "B", "C", val, valueType);
				JSShop.Models.Order.CalculateExpression(1, 0.25, "DKK", 25, "lbs", "9850", "QuickPay", "Spring", "X", "Y", "Z", val, valueType);
			}
			catch (err)
			{
				return false;
			}

			return true;
		}, "Invalid expression");

		return input;
	}

	function createAdditionalDataExpressionInput(value, onChange)
	{
		var input = createExpressionInput(value, onChange);

		input.FitControl.SetValidationCallback(function(val)
		{
			if (val === "")
				return true;

			try
			{
				var res = JSON.parse(val);
				return (res && typeof res === "object");
			}
			catch (err)
			{
				return false;
			}
		}, "Invalid JSON object");

		return input;
	}

	function createDropDown(value, options, onChange)
	{
		Fit.Validation.ExpectString(value);
		//Fit.Validation.ExpectTypeArray(options, Fit.Validation.ExpectString);
		Fit.Validation.ExpectArray(options, Fit.Validation.ExpectString);

		var ctl = new Fit.Controls.DropDown(Fit.Data.CreateGuid());
		ctl.SetPicker(new Fit.Controls.ListView());

		var title = value;

		Fit.Array.ForEach(options, function(option)
		{
			Fit.Validation.ExpectString(option.Title);
			Fit.Validation.ExpectString(option.Value);

			if (option.Value === value)
				title = option.Title;

			ctl.GetPicker().AddItem(option.Title, option.Value);
		});

		ctl.AddSelection(title, value);
		ctl.OnChange(function(sender)
		{
			onChange(sender, ((ctl.GetSelections().length !== 0) ? ctl.GetSelections()[0].Value : ""));
		});
		ctl.Width(700);

		ctl.GetDomElement().FitControl = ctl;

		return ctl.GetDomElement();
	}

	function setActiveTabButton(active)
	{
		Fit.Validation.ExpectInstance(active, Fit.Controls.Button);

		Fit.Array.ForEach(buttons, function(btn)
		{
			btn.Type(Fit.Controls.Button.Type.Default);
		});

		active.Type(Fit.Controls.Button.Type.Primary);
		active.Focused(false);
	}

	function loadCodeMirror(callback)
	{
		Fit.Validation.ExpectFunction(callback, true);

		var cb = (callback ? callback : function() {});

		if (codeMirrorLoadState === 1) // Already loaded
		{
			cb();
		}
		else if (codeMirrorLoadState === 0) // Loading but not ready yet
		{
			var checkInt = -1;
			checkInt = setInterval(function()
			{
				if (codeMirrorLoadState === 1)
				{
					clearInterval(checkInt);
					cb();
				}
			}, 250);
		}
		else // Not loaded
		{
			codeMirrorLoadState = 0; // Indicate loading state

			// The file codemirror.min.css is a minified version of lib/codemirror.css while codemirror.bundle.min.js
			// is a minified bundle of lib/codemirror.js, mode/javascript/javascript.js, and addon/edit/matchbrackets.js
			// which reduces the total size by ~55%.

			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/CodeMirror/codemirror.min.css");
			Fit.Loader.LoadScript(JSShop.GetPath() + "/CodeMirror/codemirror.bundled.min.js", function(cfgs)
			{
				codeMirrorLoadState = 1; // Done loading
				cb();
			});
		}
	}

	function activateCodeMirror(ctl)
	{
		loadCodeMirror(function()
		{
			var codeEditor = CodeMirror.fromTextArea(ctl.GetDomElement().querySelector("textarea"),
			{
				lineNumbers: true,
				mode: "javascript", // "htmlmixed", "css", "javascript"
				matchBrackets: true,
				viewportMargin: Infinity, // Used with height:auto to enable auto resize: https://codemirror.net/demo/resize.html
				styleActiveLine: true
			});

			codeEditor.getWrapperElement().style.cssText += "; border: 1px solid silver; height: auto;";

			codeEditor.on("change", function(sender, args)
			{
				ctl.Value(sender.getValue());
			});
		});
	}

	init();
}
