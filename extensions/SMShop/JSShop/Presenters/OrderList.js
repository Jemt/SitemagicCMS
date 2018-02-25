if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.OrderList = function()
{
	var view = null;
	var models = [];
	var lang = JSShop.Language.Translations.OrderList;

	var orderEntryTemplate = null;
	var entriesParent = null
	var entriesIndex = -1;

	var process = [];

	// TODO: Hide buttons if features are not supported !!!

	var lstSearchType = null;
	var txtSearch = null;
	var txtFrom = null;
	var txtTo = null;
	var cmdUpdate = null;

	var cmdExport = null;
	var cmdInvoice = null;
	var cmdCapture = null;
	var cmdReject = null;
	var cmdConfig = null;
	var chkSelectAll = null;

	view = document.createElement("div");

	function init()
	{
		// Load view

		//view = document.createElement("div");

		var loadData = function(cb)
		{
			Fit.Validation.ExpectFunction(cb, true);

			Fit.Array.ForEach(models, function(model)
			{
				model._internal.CheckBox.Dispose();
			});

			chkSelectAll.Checked(false);

			var oldModels = models;

			models = [];
			process = [];

			if (document.querySelector("link[href*='/Views/OrderList.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
				Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/OrderList.css");

			var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/OrderList.html");
			req.OnSuccess(function(sender)
			{
				var initialLoad = (view.innerHTML === "");

				if (initialLoad === true)
				{
					view.innerHTML = req.GetResponseText();

					lstSearchType.Render(view.querySelector("#JSShopOrderListButtons"));
					txtSearch.Render(view.querySelector("#JSShopOrderListButtons"));
					txtFrom.Render(view.querySelector("#JSShopOrderListButtons"));
					//Fit.Dom.Add(view.querySelector("#JSShopOrderListButtons"), Fit.Dom.CreateElement("<span>-</span>"));
					txtTo.Render(view.querySelector("#JSShopOrderListButtons"));
					cmdUpdate.Render(view.querySelector("#JSShopOrderListButtons"));

					cmdExport.Render(view.querySelector("#JSShopOrderListButtons"));
					cmdInvoice.Render(view.querySelector("#JSShopOrderListButtons"));
					cmdCapture.Render(view.querySelector("#JSShopOrderListButtons"));
					cmdReject.Render(view.querySelector("#JSShopOrderListButtons"));

					if (cmdConfig !== null)
						cmdConfig.Render(view.querySelector("#JSShopOrderListButtons"));

					Fit.Dom.Add(view.querySelector("#JSShop-Select"), chkSelectAll.GetDomElement());
					Fit.Dom.Add(view.querySelector("#JSShop-OrderId"), document.createTextNode(lang.OrderId));
					Fit.Dom.Add(view.querySelector("#JSShop-Time"), document.createTextNode(lang.Time));
					Fit.Dom.Add(view.querySelector("#JSShop-Customer"), document.createTextNode(lang.Customer));
					Fit.Dom.Add(view.querySelector("#JSShop-Amount"), document.createTextNode(lang.Amount));
					Fit.Dom.Add(view.querySelector("#JSShop-PaymentMethod"), document.createTextNode(lang.PaymentMethod));
					Fit.Dom.Add(view.querySelector("#JSShop-State"), document.createTextNode(lang.State));
					Fit.Dom.Add(view.querySelector("#JSShop-InvoiceId"), document.createTextNode(lang.InvoiceId));

					orderEntryTemplate = view.querySelector("#JSShopOrderEntry");
					Fit.Dom.Attribute(orderEntryTemplate, "id", null);
					entriesParent = orderEntryTemplate.parentElement;
					entriesIndex = Fit.Dom.GetIndex(orderEntryTemplate);

					Fit.Dom.Remove(orderEntryTemplate);
				}

				// Remove any previous loaded entries

				Fit.Array.ForEach(oldModels, function(model)
				{
					Fit.Dom.Remove(model._internal.DomElement);
				});

				var from = Fit.Date.Parse(txtFrom.Value() + " 00:00:00", JSShop.Language.Translations.Locale.DateFormat + " hh:mm:ss").getTime();
				var to = Fit.Date.Parse(txtTo.Value() + " 23:59:59", JSShop.Language.Translations.Locale.DateFormat + " hh:mm:ss").getTime();

				//var from = (new Date(txtFrom.Value() + " 00:00:00")).getTime(); //(new Date()).setDate(-30);
				//var to = (new Date(txtTo.Value() + " 23:59:59")).getTime(); //(new Date()).getTime();

				JSShop.Models.Order.RetrieveAll(from, to, function(request, mdls)
				{
					models = mdls;

					var previousEntry = null;

					Fit.Array.ForEach(models, function(model)
					{
						var entryHtml = orderEntryTemplate.outerHTML;

						entryHtml = entryHtml.replace(/{\[CheckBox\]}/g, "<div id='JSShopOrderCheckBox" + model.Id() + "'></div>");
						entryHtml = entryHtml.replace(/{\[OrderId\]}/g, model.Id());
						//entryHtml = entryHtml.replace(/{\[OrderId\]}/g, "<div id='JSShopOrderDetails" + model.Id() + "'></div>");
						entryHtml = entryHtml.replace(/{\[Time\]}/g, Fit.Date.Format(new Date(model.Time()), JSShop.Language.Translations.Locale.DateFormat + " " + JSShop.Language.Translations.Locale.TimeFormat));
						//entryHtml = entryHtml.replace(/{\[Customer\]}/g, model.FirstName() + " " + model.LastName());
						entryHtml = entryHtml.replace(/{\[Customer\]}/g, "<div id='JSShopCustomerDetails" + model.Id() + "'></div>");
						entryHtml = entryHtml.replace(/{\[Currency\]}/g, model.Currency());
						//entryHtml = entryHtml.replace(/{\[Amount\]}/g, Fit.Math.Format(model.Price() + model.Vat(), 2, JSShop.Language.Translations.Locale.DecimalSeparator));
						entryHtml = entryHtml.replace(/{\[Amount\]}/g, "<div id='JSShopOrderEntries" + model.Id() + "'></div>");
						entryHtml = entryHtml.replace(/{\[State\]}/g, "<span id='JSShopOrderState" + model.Id() + "'></span>");
						entryHtml = entryHtml.replace(/{\[InvoiceId\]}/g, "<span id='JSShopOrderInvoice" + model.Id() + "'></span>");
						entryHtml = entryHtml.replace(/{\[PaymentMethod\]}/g, model.PaymentMethod());

						var entry = Fit.Dom.CreateElement(entryHtml);

						if (previousEntry === null)
						{
							Fit.Dom.InsertAt(entriesParent, entriesIndex, entry);
						}
						else
						{
							Fit.Dom.InsertAfter(previousEntry, entry);
						}

						previousEntry = entry;

						var chk = new Fit.Controls.CheckBox("JSShopOrder" + model.Id());
						//chk.Enabled((model.State() === "Authorized"));
						chk.OnChange(function(sender)
						{
							if (chk.Checked() === true)
								Fit.Array.Add(process, model);
							else
								Fit.Array.Remove(process, model);

							/*cmdExport.Enabled(process.length > 0);
							cmdCapture.Enabled(process.length > 0);
							cmdReject.Enabled(process.length > 0);*/

							if (chk.Focused() === true)
								updateSelectAll();
						});
						Fit.Dom.Replace(entry.querySelector("#JSShopOrderCheckBox" + model.Id()), chk.GetDomElement());

						/*var lnkOrderDetails = document.createElement("a");
						lnkOrderDetails.href = "javascript:";
						lnkOrderDetails.onclick = function(e)
						{
							Fit.Controls.Dialog.Alert("Muuuuhh !");
							//displayCustomerDetails(model);
						}
						lnkOrderDetails.innerHTML = model.Id();
						Fit.Dom.Replace(entry.querySelector("#JSShopOrderDetails" + model.Id()), lnkOrderDetails);*/

						var lnkCustomerDetails = document.createElement("a");
						lnkCustomerDetails.href = "javascript:";
						lnkCustomerDetails.onclick = function(e)
						{
							displayCustomerDetails(model);
						}
						lnkCustomerDetails.innerHTML = model.FirstName() + " " + model.LastName();
						Fit.Dom.Replace(entry.querySelector("#JSShopCustomerDetails" + model.Id()), lnkCustomerDetails);

						var lnkOrderEntries = document.createElement("a");
						lnkOrderEntries.href = "javascript:";
						lnkOrderEntries.onclick = function(e)
						{
							displayOrderEntries(model);
						}
						lnkOrderEntries.innerHTML = Fit.Math.Format(model.Price() + model.Vat(), 2, JSShop.Language.Translations.Locale.DecimalSeparator);
						Fit.Dom.Replace(entry.querySelector("#JSShopOrderEntries" + model.Id()), lnkOrderEntries);

						var stateElm = document.createElement("span");
						stateElm.innerHTML = getStateTitle(model.State());
						Fit.Dom.Replace(entry.querySelector("#JSShopOrderState" + model.Id()), stateElm);

						var invoiceElm = document.createElement("span");
						invoiceElm.innerHTML = model.InvoiceId();
						Fit.Dom.Replace(entry.querySelector("#JSShopOrderInvoice" + model.Id()), invoiceElm);

						//model._internal = {};
						// TODO: BAD PRACTICE ! _internal is defined by JSShop.Models.Base which all models inherit from !!
						model._internal.StateElement = stateElm;
						model._internal.InvoiceElement = invoiceElm;
						model._internal.CheckBox = chk;
						model._internal.DomElement = entry;
					});

					filterOrders();

					if (cb)
						cb();

					//updateSelectAll();
				});
			});
			req.Start();
		}

		lstSearchType = new Fit.Controls.DropDown("JSShopSearchType");
		lstSearchType.SetPicker(new Fit.Controls.ListView());
		lstSearchType.GetPicker().AddItem("Eksakt match", "Exact");
		lstSearchType.GetPicker().AddItem("Alle ord", "ContainsAll");
		lstSearchType.GetPicker().AddItem("Ethvert ord", "ContainsAny");
		lstSearchType.OnChange(function(sender)
		{
			if (lstSearchType.GetSelections().length === 1)
				filterOrders();
		});
		lstSearchType.OnBlur(function(sender)
		{
			if (lstSearchType.GetSelections().length === 0)
				lstSearchType.AddSelection("Eksakt match", "Exact");
		});
		lstSearchType.Width(150);
		lstSearchType.AddSelection("Eksakt match", "Exact");

		txtSearch = new Fit.Controls.Input("JSShopSearch");
		txtSearch.OnChange(function(sender) { filterOrders(); });
		txtSearch.Width(120);
		txtSearch.title = "Search";

		var now = new Date();
		var yesterday = new Date(((new Date()).setDate(now.getDate() - 1)));

		txtFrom = new Fit.Controls.Input("JSShopFromDate");
		txtFrom.Required(true);
		//txtFrom.SetValidationExpression(/^\d{4}(-\d{2}){2}$/, "");
		txtFrom.SetValidationCallback(function(res)
		{
			try
			{
				Fit.Date.Parse(txtFrom.Value(), JSShop.Language.Translations.Locale.DateFormat);
			}
			catch (err)
			{
				return false;
			}

			return true;
		});
		txtFrom.OnBlur(function(sender)
		{
			if (txtFrom.IsValid() === false)
			{
				txtFrom.Value(Fit.Date.Format(new Date(), JSShop.Language.Translations.Locale.DateFormat));
			}
			else
			{
				// Parser is very forgiving - e.g. it allow letters in date (2016-05ABC-20).
				// Parse and re-format to make sure value in input field is properly formatted.
				var d = Fit.Date.Parse(txtFrom.Value(), JSShop.Language.Translations.Locale.DateFormat);
				var v = Fit.Date.Format(d, JSShop.Language.Translations.Locale.DateFormat);
				txtFrom.Value(v);
			}
		});
		txtFrom.Width(120);
		txtFrom.Value(Fit.Date.Format(new Date(), JSShop.Language.Translations.Locale.DateFormat));
		txtFrom.title = "Display orders from this date";

		txtTo = new Fit.Controls.Input("JSShopToDate");
		txtTo.Required(true);
		//txtTo.SetValidationExpression(/^\d{4}(-\d{2}){2}$/, "");
		txtTo.SetValidationCallback(function(res)
		{
			try
			{
				Fit.Date.Parse(txtTo.Value(), JSShop.Language.Translations.Locale.DateFormat);
			}
			catch (err)
			{
				return false;
			}

			return true;
		});
		txtTo.OnBlur(function(sender)
		{
			if (txtTo.IsValid() === false)
			{
				txtTo.Value(Fit.Date.Format(new Date(), JSShop.Language.Translations.Locale.DateFormat));
			}
			else
			{
				// Parser is very forgiving - e.g. it allow letters in date (2016-05ABC-20).
				// Parse and re-format to make sure value in input field is properly formatted.
				var d = Fit.Date.Parse(txtTo.Value(), JSShop.Language.Translations.Locale.DateFormat);
				var v = Fit.Date.Format(d, JSShop.Language.Translations.Locale.DateFormat);
				txtTo.Value(v);
			}
		});
		txtTo.Width(120);
		txtTo.Value(Fit.Date.Format(new Date(), JSShop.Language.Translations.Locale.DateFormat));
		txtTo.title = "Display orders to this date";

		cmdUpdate = new Fit.Controls.Button("JSShopUpdateButton");
		//cmdUpdate.Title("Update");
		cmdUpdate.Icon("fa-refresh");
		cmdUpdate.Type(Fit.Controls.Button.Type.Primary);
		cmdUpdate.OnClick(function(sender)
		{
			cmdUpdate.Enabled(false);
			loadData(function() { cmdUpdate.Enabled(true); });
		});
		cmdUpdate.GetDomElement().title = "Update";

		cmdExport = new Fit.Controls.Button("JSShopExportButton");
		//cmdExport.Title(lang.Export);
		cmdExport.Icon("fa-table");
		cmdExport.Type(Fit.Controls.Button.Type.Primary);
		//cmdExport.Enabled(false);
		cmdExport.OnClick(function(sender)
		{
			if (process.length === 0)
			{
				Fit.Controls.Dialog.Alert(lang.SelectOrders);
				return;
			}

			var cmdCsv = new Fit.Controls.Button("JSShopExportCsvButton");
			cmdCsv.Title("CSV");
			cmdCsv.Icon("fa-table");
			cmdCsv.Type(Fit.Controls.Button.Type.Primary);
			cmdCsv.OnClick(function(sender)
			{
				dia.Dispose();
				exportData();
			});

			var cmdPdf = new Fit.Controls.Button("JSShopExportPdfButton");
			cmdPdf.Title("PDF");
			cmdPdf.Icon("fa-file-pdf-o");
			cmdPdf.Type(Fit.Controls.Button.Type.Primary);
			cmdPdf.OnClick(function(sender)
			{
				dia.Dispose();
				printOrders();
			});
			cmdPdf.Enabled((Fit.Browser.GetInfo().Name !== "MSIE" || Fit.Browser.GetInfo().Name >= 10)); // jsPDF requires IE10+

			var cmdCancel = new Fit.Controls.Button("JSShopExportCancelButton");
			cmdCancel.Title("Annuller");
			cmdCancel.Icon("fa-cancel");
			cmdCancel.Type(Fit.Controls.Button.Type.Danger);
			cmdCancel.OnClick(function(sender)
			{
				dia.Dispose();
			});

			var dia = new Fit.Controls.Dialog();
			dia.Content("Vælg venligst format");
			dia.Modal(true);
			dia.AddButton(cmdCsv);
			dia.AddButton(cmdPdf);
			dia.AddButton(cmdCancel);
			dia.Open();
		});
		cmdExport.GetDomElement().title = lang.Export;

		cmdInvoice = new Fit.Controls.Button("JSShopInvoiceButton");
		//cmdInvoice.Title(lang.SendInvoice);
		cmdInvoice.Icon("fa-paperclip");
		cmdInvoice.Type(Fit.Controls.Button.Type.Primary);
		cmdInvoice.OnClick(function(sender)
		{
			if (process.length === 0)
			{
				Fit.Controls.Dialog.Alert(lang.SelectOrders);
				return;
			}

			sendInvoices();
		});
		cmdInvoice.GetDomElement().title = lang.SendInvoice;

		cmdCapture = new Fit.Controls.Button("JSShopCaptureButton");
		//cmdCapture.Title(lang.Capture);
		cmdCapture.Icon("fa-exchange");
		cmdCapture.Type(Fit.Controls.Button.Type.Success);
		//cmdCapture.Enabled(false);
		cmdCapture.OnClick(function(sender)
		{
			if (process.length === 0)
			{
				Fit.Controls.Dialog.Alert(lang.SelectOrders);
				return;
			}

			Fit.Controls.Dialog.Confirm(lang.ConfirmAction + ": " + lang.Capture, function(res)
			{
				if (res === true)
					processPayments("Capture");
			});
		});
		cmdCapture.GetDomElement().title = lang.Capture;

		cmdReject = new Fit.Controls.Button("JSShopReturnButton");
		//cmdReject.Title(lang.Reject);
		cmdReject.Icon("fa-trash");
		cmdReject.Type(Fit.Controls.Button.Type.Danger);
		//cmdReject.Enabled(false);
		cmdReject.OnClick(function(sender)
		{
			if (process.length === 0)
			{
				Fit.Controls.Dialog.Alert(lang.SelectOrders);
				return;
			}

			Fit.Controls.Dialog.Confirm(lang.ConfirmAction + ": " + lang.Reject, function(res)
			{
				if (res === true)
					processPayments("Reject");
			});
		});
		cmdReject.GetDomElement().title = lang.Reject;

		if (JSShop.Settings.ConfigUrl !== null)
		{
			cmdConfig = new Fit.Controls.Button("JSShopConfigButton");
			cmdConfig.Icon("fa-cog");
			cmdConfig.Type(Fit.Controls.Button.Type.Warning);
			cmdConfig.OnClick(function(sender)
			{
				location.href = JSShop.Settings.ConfigUrl;
			});
			cmdConfig.GetDomElement().title = "Settings"; ///lang.Reject;
		}

		chkSelectAll = new Fit.Controls.CheckBox("JSShopSelectAll");
		chkSelectAll.OnChange(function(sender)
		{
			if (chkSelectAll.Focused() === true)
			{
				Fit.Array.ForEach(models, function(model)
				{
					//if (model._internal.CheckBox.Enabled() === true)

					if (model._internal.DomElement.style.display !== "none")
						model._internal.CheckBox.Checked(chkSelectAll.Checked());
				});
			}
		});

		loadData();
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

	function getStateTitle(state)
	{
		Fit.Validation.ExpectStringValue(state);

		if (state === "Initial")
			return lang.StateInitial;
		else if (state === "Authorized")
			return lang.StateAuthorized;
		else if (state === "Captured")
			return lang.StateCaptured;
		else if (state === "Canceled")
			return lang.StateCanceled;

		return "UNKNOWN";
	}

	function processPayments(type)
	{
		Fit.Validation.ExpectStringValue(type);

		/*cmdExport.Enabled(false);
		cmdCapture.Enabled(false);
		cmdReject.Enabled(false);*/

		var processing = [];
		var failed = [];
		var skipped = [];
		var scheduled = 0;
		var processed = 0;

		Fit.Array.ForEach(process, function(model)
		{
			if ((model.State() === "Captured" && type === "Capture") || (model.State() === "Canceled" && type === "Reject"))
			{
				Fit.Array.Add(skipped, model.Id());
				return; // Skip, already in desired state
			}

			Fit.Array.Add(processing, model);
		});

		if (skipped.length > 0)
			Fit.Controls.Dialog.Alert(lang.OrdersSkipped + ":<br><br>" + skipped.join(((skipped.length <= 10) ? "<br>" : ", ")));

		if (processing.length === 0)
			return;

		var scheduled = processing.length;

		var execute = null;
		execute = function(model)
		{
			var method = ((type === "Capture") ? model.CapturePayment : model.CancelPayment);

			method(function(req, m) // Success handler
			{
				//Fit.Array.Remove(processing, model);
				processed++;

				model._internal.StateElement.innerHTML = getStateTitle(model.State());

				if (processing.length === 0)
				{
					if (processed === scheduled)
					{
						if (failed.length === 0)
							Fit.Controls.Dialog.Alert(lang.DoneSuccess);
						else
							Fit.Controls.Dialog.Alert(lang.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
					}
				}
				else
				{
					execute(processing[0]);
					Fit.Array.Remove(processing, processing[0]);
				}
			},
			function(req, m) // Error handler
			{
				//Fit.Array.Remove(processing, model);
				Fit.Array.Add(failed, model.Id());

				processed++;

				if (processing.length === 0)
				{
					if (processed === scheduled)
						Fit.Controls.Dialog.Alert(lang.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
				}
				else
				{
					execute(processing[0]);
					Fit.Array.Remove(processing, processing[0]);
				}
			});
		};

		/*if (processing.length > 0)
		{
			execute(processing[0]);
		}*/

		var maxConcurrentRequests = 3;
		var remove = [];

		for (var i = 0 ; i < maxConcurrentRequests ; i++)
		{
			if (i <= processing.length - 1)
			{
				execute(processing[i]);
				Fit.Array.Add(remove, processing[i]);
			}
		}

		Fit.Array.ForEach(remove, function(model)
		{
			Fit.Array.Remove(processing, model);
		});

		/*Fit.Array.ForEach(processing, function(model)
		{
			var method = ((type === "Capture") ? model.CapturePayment : model.CancelPayment);

			method(function(req, m) // Success handler
			{
				Fit.Array.Remove(processing, model);

				model._internal.StateElement.innerHTML = getStateTitle(model.State());

				if (processing.length === 0)
				{
					if (failed.length === 0)
						Fit.Controls.Dialog.Alert(lang.DoneSuccess);
					else
						Fit.Controls.Dialog.Alert(lang.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
				}
			},
			function(req, m) // Error handler
			{
				Fit.Array.Remove(processing, model);
				Fit.Array.Add(failed, model.Id());

				if (processing.length === 0)
					Fit.Controls.Dialog.Alert(lang.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
			});
		});*/
	}

	function updateSelectAll()
	{
		var allSelected = true;

		Fit.Array.ForEach(models, function(model)
		{
			if (model._internal.DomElement.style.display !== "none" && model._internal.CheckBox.Checked() === false)
			{
				allSelected = false;
				return false;
			}
		});

		chkSelectAll.Checked(allSelected);

		/*var allSelected = true;
		var enabled = false;

		Fit.Array.ForEach(models, function(model)
		{
			if (model._internal.CheckBox.Enabled() === true && model._internal.CheckBox.Checked() === false)
			{
				allSelected = false;
				chkSelectAll.Checked(false);
			}

			if (model._internal.CheckBox.Enabled() === true)
			{
				enabled = true;
			}
		});

		if (enabled === false)
		{
			chkSelectAll.Checked(false);
			chkSelectAll.Enabled(false);
		}
		else
		{
			if (allSelected === true)
				chkSelectAll.Checked(true);
		}*/
	}

	function displayCustomerDetails(model)
	{
		Fit.Validation.ExpectInstance(model, JSShop.Models.Order);

		var dia = new Fit.Controls.Dialog();
		dia.Modal(true);
		dia.Open();

		var cmdOk = new Fit.Controls.Button("JSShopOrderDetailsOkButton");
		cmdOk.Title(JSShop.Language.Translations.Common.Ok);
		cmdOk.Type(Fit.Controls.Button.Type.Success);
		cmdOk.Enabled(false);
		cmdOk.OnClick(function(sender)
		{
			dia.Dispose();
		});
		dia.AddButton(cmdOk);

		if (document.querySelector("link[href*='/Views/DialogCustomerDetails.css']") === null)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/DialogCustomerDetails.css");

		var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/DialogCustomerDetails.html");
		req.OnSuccess(function(sender)
		{
			var html = req.GetResponseText();

			html = html.replace(/{\[CustomerDetailsHeadline\]}/, lang.CustomerDetails);
			html = html.replace(/{\[AlternativeAddressHeadline\]}/, lang.AlternativeAddress);
			html = html.replace(/{\[CustomerMessageHeadline\]}/, lang.Message);

			var data = [ "Company", "FirstName", "LastName", "Address", "ZipCode", "City", "Phone", "Email", "Message" ];
			data = Fit.Array.Merge(data, [ "AltCompany", "AltFirstName", "AltLastName", "AltAddress", "AltZipCode", "AltCity" ]);

			Fit.Array.ForEach(data, function(d)
			{
				html = html.replace(new RegExp("{\\[" + d + "\\]}", "g"), model[d]().replace("\n", "<br>"));
			});

			cmdOk.Enabled(true);
			dia.Content(html);

			var altAddress = dia.GetDomElement().querySelector("#JSShopAlternativeAddress");
			altAddress.style.display = ((model.AltAddress() !== "") ? "block" : "none");

			var custData = "";

			for (var i = 1 ; i <= 3 ; i++)
			{
				if (model["CustData" + i]() !== "")
					custData += ((custData !== "") ? "<br>" : "") + model["CustData" + i]();
			}

			var msg = dia.GetDomElement().querySelector("#JSShopCustomerMessage");

			if (custData !== "")
				msg.innerHTML = msg.innerHTML + "<br><br>" + custData;

			msg.style.display = ((model.Message() !== "" || custData !== "") ? "block" : "none");
		});
		req.Start();
	}

	function displayOrderEntries(model)
	{
		Fit.Validation.ExpectInstance(model, JSShop.Models.Order);

		var dia = new Fit.Controls.Dialog();
		dia.Modal(true);
		dia.Content("Working on it..");
		dia.Open();
		dia.GetDomElement().style.maxWidth = "95%";

		var cmdOk = new Fit.Controls.Button("JSShopOrderEntriesOkButton");
		cmdOk.Title(JSShop.Language.Translations.Common.Ok);
		cmdOk.Type(Fit.Controls.Button.Type.Success);
		cmdOk.Enabled(false);
		cmdOk.OnClick(function(sender)
		{
			dia.Dispose();
		});
		dia.AddButton(cmdOk);

		if (document.querySelector("link[href*='/Views/DialogOrderEntries.css']") === null)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/DialogOrderEntries.css");

		var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/DialogOrderEntries.html");
		req.OnSuccess(function(sender)
		{
			var html = req.GetResponseText();

			// Headers

			html = html.replace(/{\[HeaderProduct\]}/g, lang.Product);
			html = html.replace(/{\[HeaderUnitPrice\]}/g, lang.UnitPrice);
			html = html.replace(/{\[HeaderUnits\]}/g, lang.Units);
			html = html.replace(/{\[HeaderPrice\]}/g, lang.Price);
			html = html.replace(/{\[HeaderTotalVat\]}/g, lang.TotalVat);
			html = html.replace(/{\[HeaderTotalPrice\]}/g, lang.TotalPrice);

			// Extract item HTML

			var startTag = "<!-- REPEAT Items -->";
			var endTag = "<!-- /REPEAT Items -->";

			var regEx = new RegExp(startTag + "[\\s\\S]*" + endTag);
			var res = regEx.exec(html);

			if (res === null)
				return;

			// Remove <!-- REPEAT Items --> block from HTML

			var itemHtml = res[0];

			var posStart = itemHtml.indexOf(startTag) + startTag.length;
			var posEnd = itemHtml.indexOf(endTag);

			itemHtml = itemHtml.substring(posStart, posEnd);

			// Populate data

			var populateEntries = function(entryModels)
			{
				var allItemsHtml = "";
				var curItemHtml = null;

				var vatTotal = 0.0;
				var priceTotal = 0.0;

				Fit.Array.ForEach(entryModels, function(entry)
				{
					curItemHtml = itemHtml;

					var pricing = JSShop.CalculatePricing(entry.UnitPrice(), entry.Units(), entry.Vat(), entry.Discount());

					curItemHtml = curItemHtml.replace(/{\[Title\]}/g, ((entry.Product !== null) ? entry.Product.Title() : entry.ProductId()));
					curItemHtml = curItemHtml.replace(/{\[UnitPrice\]}/g, Fit.Math.Format(pricing.UnitPriceInclVat, 2, JSShop.Language.Translations.Locale.DecimalSeparator));
					curItemHtml = curItemHtml.replace(/{\[DiscountMessage\]}/g, entry.DiscountMessage());
					curItemHtml = curItemHtml.replace(/{\[Discount\]}/g, ((pricing.DiscountInclVat > 0) ? Fit.Math.Format(pricing.DiscountInclVat * -1, 2, JSShop.Language.Translations.Locale.DecimalSeparator) : ""));
					curItemHtml = curItemHtml.replace(/{\[Units\]}/g, entry.Units());
					curItemHtml = curItemHtml.replace(/{\[Currency\]}/g, entry.Currency());
					curItemHtml = curItemHtml.replace(/{\[Price\]}/g, Fit.Math.Format(pricing.TotalInclVat, 2, JSShop.Language.Translations.Locale.DecimalSeparator));

					allItemsHtml += curItemHtml;


					/****var vatFactor = ((entry.Vat() > 0) ? 1.0 + (entry.Vat() / 100) : 1.0);
					var numberOfUnits = entry.Units();
					var unitPriceExclVat = entry.UnitPrice();
					var unitPriceInclVat = Fit.Math.Round(unitPriceExclVat * vatFactor, 2);
					var priceAllUnitsInclVat = unitPriceInclVat * numberOfUnits;

					var discountExclVat = Fit.Math.Round(entry.Discount(), 2);
					var discountInclVat = Fit.Math.Round(discountExclVat * vatFactor, 2);

					var resultPriceInclVat = priceAllUnitsInclVat - discountInclVat;
					var resultVat = Fit.Math.Round(resultPriceInclVat - (resultPriceInclVat / vatFactor), 2);

					curItemHtml = curItemHtml.replace(/{\[Title\]}/g, ((entry.Product !== null) ? entry.Product.Title() : entry.ProductId()));
					curItemHtml = curItemHtml.replace(/{\[UnitPrice\]}/g, Fit.Math.Format(unitPriceInclVat, 2, JSShop.Language.Translations.Locale.DecimalSeparator));
					curItemHtml = curItemHtml.replace(/{\[DiscountMessage\]}/g, entry.DiscountMessage());
					curItemHtml = curItemHtml.replace(/{\[Discount\]}/g, ((discountInclVat > 0) ? Fit.Math.Format(entry.Discount() * vatFactor * -1, 2, JSShop.Language.Translations.Locale.DecimalSeparator) : ""));
					curItemHtml = curItemHtml.replace(/{\[Units\]}/g, entry.Units());
					curItemHtml = curItemHtml.replace(/{\[Currency\]}/g, entry.Currency());
					curItemHtml = curItemHtml.replace(/{\[Price\]}/g, Fit.Math.Format(resultPriceInclVat, 2, JSShop.Language.Translations.Locale.DecimalSeparator));

					allItemsHtml += curItemHtml;****/

					/*var vatFactor = ((entry.Vat() > 0) ? 1.0 + (entry.Vat() / 100) : 1.0);

					curItemHtml = curItemHtml.replace(/{\[Title\]}/g, ((entry.Product !== null) ? entry.Product.Title() : entry.ProductId()));
					curItemHtml = curItemHtml.replace(/{\[UnitPrice\]}/g, Fit.Math.Format(entry.UnitPrice() * vatFactor, 2, JSShop.Language.Translations.Locale.DecimalSeparator));
					curItemHtml = curItemHtml.replace(/{\[DiscountMessage\]}/g, entry.DiscountMessage());
					curItemHtml = curItemHtml.replace(/{\[Discount\]}/g, ((entry.Discount() > 0) ? Fit.Math.Format(entry.Discount() * vatFactor * -1, 2, JSShop.Language.Translations.Locale.DecimalSeparator) : ""));
					curItemHtml = curItemHtml.replace(/{\[Units\]}/g, entry.Units());
					curItemHtml = curItemHtml.replace(/{\[Currency\]}/g, entry.Currency());
					curItemHtml = curItemHtml.replace(/{\[Price\]}/g, Fit.Math.Format(((entry.Units() * entry.UnitPrice()) - entry.Discount()) * vatFactor, 2, JSShop.Language.Translations.Locale.DecimalSeparator));

					allItemsHtml += curItemHtml;

					vatTotal += ((entry.Units() * entry.UnitPrice()) - entry.Discount()) * (entry.Vat() / 100);
					priceTotal += ((entry.Units() * entry.UnitPrice()) - entry.Discount()) * ((entry.Vat() / 100) + 1.0);*/
				});

				for (var i = 1 ; i <= 3 ; i++)
				{
					if (model["CostCorrection" + i]() > 0 || model["CostCorrection" + i]() < 0)
					{
						curItemHtml = itemHtml;

						curItemHtml = curItemHtml.replace(/{\[Title\]}/g, model["CostCorrectionMessage" + i]());
						curItemHtml = curItemHtml.replace(/{\[UnitPrice\]}/g, "");
						curItemHtml = curItemHtml.replace(/{\[DiscountMessage\]}/g, "");
						curItemHtml = curItemHtml.replace(/{\[Discount\]}/g, "");
						curItemHtml = curItemHtml.replace(/{\[Units\]}/g, "");
						curItemHtml = curItemHtml.replace(/{\[Currency\]}/g, model.Currency());
						curItemHtml = curItemHtml.replace(/{\[Price\]}/g, Fit.Math.Format(model["CostCorrection" + i]() + model["CostCorrectionVat" + i](), 2, JSShop.Language.Translations.Locale.DecimalSeparator));

						allItemsHtml += curItemHtml;

						//vatTotal += model["CostCorrectionVat" + i]();
						//priceTotal += model["CostCorrection" + i]() + model["CostCorrectionVat" + i]();
					}
				}

				html = html.replace(res[0], allItemsHtml);

				html = html.replace(/{\[Currency\]}/g, model.Currency());
				html = html.replace(/{\[TotalVat\]}/g, Fit.Math.Format(/*vatTotal*/model.Vat(), 2, JSShop.Language.Translations.Locale.DecimalSeparator));
				html = html.replace(/{\[TotalPrice\]}/g, Fit.Math.Format(/*priceTotal*/model.Price() + model.Vat(), 2, JSShop.Language.Translations.Locale.DecimalSeparator));

				cmdOk.Enabled(true);
				dia.Content(html);
			}

			// Load order entries

			JSShop.Models.OrderEntry.RetrieveAll(model.Id(), function(req, entryModels)
			{
				var products = 0;

				// Load associated product details

				Fit.Array.ForEach(entryModels, function(entry)
				{
					var p = new JSShop.Models.Product(entry.ProductId());
					p.Retrieve(function(req, product)
					{
						products++;
						entry.Product = p;

						if (products === entryModels.length)
							populateEntries(entryModels);
					},
					function(req, product) // Product has most likely been removed
					{
						products++;
						entry.Product = null;

						if (products === entryModels.length)
							populateEntries(entryModels);
					});
				});
			});
		});
		req.Start();
	}

	function exportData(includeOrderEntries)
	{
		Fit.Validation.ExpectBoolean(includeOrderEntries, true);

		var execute = function()
		{
			var data = [ "Id", "Time", "ClientIp", "Company", "FirstName", "LastName", "Address", "ZipCode", "City", "Email", "Phone", "Message", "AltCompany", "AltFirstName", "AltLastName", "AltAddress", "AltZipCode", "AltCity", "Price", "Vat", "Currency", "Weight", "WeightUnit", "CostCorrection1", "CostCorrectionVat1", "CostCorrectionMessage1", "CostCorrection2", "CostCorrectionVat2", "CostCorrectionMessage2", "CostCorrection3", "CostCorrectionVat3", "CostCorrectionMessage3", "PaymentMethod", "TransactionId", "State", "PromoCode", "CustData1", "CustData2", "CustData3" ];
			var items = [];

			Fit.Array.ForEach(process, function(model)
			{
				var item = [];

				Fit.Array.ForEach(data, function(key)
				{
					if (key === "Time")
					{
						var date = new Date(model.Time());
						Fit.Array.Add(item, "\"" + Fit.Date.Format(date, "YYYY-MM-DD hh:mm:ss") + "\"");
					}
					else if (key === "State")
					{
						Fit.Array.Add(item, "\"" + getStateTitle(model.State()) + "\"");
					}
					else
					{
						if (typeof(model[key]()) === "number")
							Fit.Array.Add(item, Fit.Math.Format(model[key](), 4, "."));
						else
							Fit.Array.Add(item, "\"" + model[key]() + "\"");
					}
				});

				Fit.Array.Add(items, item);

				/*if (includeOrderEntries === true)
				{
					data = Fit.Array.Merge(data, [ "" ]);
				}*/

				//////Fit.Array.Remove(process, model);
				//model._internal.CheckBox.Checked(false);
			});

			/*Fit.Array.ForEach(models, function(model)
			{
				model._internal.CheckBox.Checked(false);
			});
			chkSelectAll.Checked(false);*/

			var content = "\"" + data.join("\",\"") + "\"";

			Fit.Array.ForEach(items, function(item)
			{
				content += "\n" + item.join(","); //content += "\n\"" + item.join("\",\"") + "\"";
			});

			if (Fit.Browser.GetInfo().Name === "MSIE") // Internet Explorer
			{
				var ifr = document.createElement("iframe");
				ifr.style.display = "none";
				document.body.appendChild(ifr);
				var doc = ifr.contentDocument;

				doc.write(/*'sep=,\r\n' +*/content);
				doc.close();
				doc.execCommand('SaveAs', true, "Export.csv");

				Fit.Dom.Remove(ifr);
			}
			else if (navigator.msSaveOrOpenBlob) // Microsoft Edge
			{
				var blob = new Blob([ content ], { type: "text/csv" });
				navigator.msSaveOrOpenBlob(blob, "Export.csv");
			}
			else // Modern browsers
			{
				window.open(encodeURI("data:text/csv;charset=utf-8," + content));
			}
		}

		if (includeOrderEntries === true)
		{
			/*var loaded = 0;

			Fit.Array.ForEach(process, function(model)
			{
				if (model._internal.Entries !== undefined)
				{
					loaded++;

					if (loaded === process.length)
						execute();

					return;
				}

				JSShop.Models.OrderEntry.RetrieveAll(model.Id(), function(req, models)
				{
					loaded++;
					model._internal.Entries = models;

					if (loaded === process.length)
						execute();
				},
				function(req, models)
				{
					loaded++;
					model._internal.Entries = null;

					if (loaded === process.length)
						execute();
				});
			});*/
		}
		else
		{
			execute();
		}
	}

	function printOrders()
	{
		var execute = function()
		{
			var fontSize = 12;
			var minorSize = 10;
			var headerSize = 14;

			var pdf = new jsPDF("portrait", "pt", "a4");
			pdf.setFont("helvetica", "normal")
			pdf.setFontSize(fontSize);

			var first = true;

			var x = 50;
			var y = 0;

			Fit.Array.ForEach(process, function(order)
			{
				if (first === false)
					pdf.addPage();

				first = false;

				y = 50;

				pdf.setFontSize(headerSize);
				pdf.setFontStyle("bold")
				pdf.text(x, y, "Order " + order.Id());
				pdf.setFontSize(fontSize);
				pdf.setFontStyle("normal");

				pdf.setFontSize(fontSize);
				pdf.text(450, y, Fit.Date.Format(new Date(order.Time()), "YYYY-MM-DD hh:mm:ss"));
				pdf.setFontSize(fontSize);

				y += 30;

				// Customer details

				var customerY = y;

				pdf.setFontStyle("bold");
				pdf.text(x, customerY += 20, "Kundedetaljer");
				pdf.setFontStyle("normal");

				if (order.Company() !== "")
					pdf.text(x, customerY += 20, order.Company());
				pdf.text(x, customerY += 20, order.FirstName() + " " + order.LastName());
				pdf.text(x, customerY += 20, order.Address());
				pdf.text(x, customerY += 20, order.ZipCode() + " " + order.City());
				if (order.Phone() !== "")
					pdf.text(x, customerY += 20, order.Phone());
				pdf.text(x, customerY += 20, order.Email());

				// Alternative delivery address

				var deliveryY = y;

				pdf.setFontStyle("bold");
				pdf.text(x + 250, deliveryY += 20, "Alternativ leveringsadresse");
				pdf.setFontStyle("normal");

				if (order.AltCompany() !== "")
					pdf.text(x + 250, deliveryY += 20, order.AltCompany());
				pdf.text(x + 250, deliveryY += 20, order.AltFirstName() + " " + order.AltLastName());
				pdf.text(x + 250, deliveryY += 20, order.AltAddress());
				pdf.text(x + 250, deliveryY += 20, order.AltZipCode() + " " + order.AltCity());

				y = ((customerY > deliveryY) ? customerY : deliveryY);

				// Order entries

				y += 50;

				pdf.setFontStyle("bold");
				pdf.text(x, y, "Bestilling");
				pdf.setFontStyle("normal");

				y += 20;

				pdf.text(x, y, "Title");
				pdf.text(x + 250, y, "Enhedspris");
				pdf.text(x + 350, y, "Antal");
				pdf.text(x + 400, y, "Pris");

				Fit.Array.ForEach(order._internal.Entries, function(entry)
				{
					y += 20;

					///var vatFactor = ((entry.Vat() > 0) ? 1.0 + (entry.Vat() / 100) : 1.0);
					var pricing = JSShop.CalculatePricing(entry.UnitPrice(), entry.Units(), entry.Vat(), entry.Discount());

					pdf.text(x, y, ((entry.Product !== null) ? entry.Product.Title() : entry.ProductId()));
					pdf.text(x + 250, y, Fit.Math.Format(pricing.UnitPriceInclVat, 2, JSShop.Language.Translations.Locale.DecimalSeparator));
					pdf.text(x + 350, y, entry.Units().toString());
					pdf.text(x + 400, y, Fit.Math.Format(pricing.TotalInclVat, 2, JSShop.Language.Translations.Locale.DecimalSeparator));

					/*pdf.text(x, y, ((entry.Product !== null) ? entry.Product.Title() : entry.ProductId()));
					pdf.text(x + 250, y, Fit.Math.Format(entry.UnitPrice() * vatFactor, 2, JSShop.Language.Translations.Locale.DecimalSeparator));
					pdf.text(x + 350, y, entry.Units().toString());
					pdf.text(x + 400, y, Fit.Math.Format(((entry.Units() * entry.UnitPrice()) - entry.Discount()) * vatFactor, 2, JSShop.Language.Translations.Locale.DecimalSeparator));*/

					if (entry.Discount() !== 0)
					{
						y += 14;

						pdf.setFontSize(minorSize);

						pdf.text(x, y, entry.DiscountMessage());
						pdf.text(x + 400, y, Fit.Math.Format(pricing.DiscountInclVat * -1, 2, JSShop.Language.Translations.Locale.DecimalSeparator));
						//pdf.text(x + 400, y, Fit.Math.Format(entry.Discount() * vatFactor * -1, 2, JSShop.Language.Translations.Locale.DecimalSeparator));

						pdf.setFontSize(fontSize);
					}
				});

				// Cost corrections

				for (var i = 1 ; i <= 3 ; i++)
				{
					if (order["CostCorrection" + i]() > 0 || order["CostCorrection" + i]() < 0)
					{
						y += 20;

						pdf.text(x, y, order["CostCorrectionMessage" + i]());
						pdf.text(x + 400, y, Fit.Math.Format(order["CostCorrection" + i]() + order["CostCorrectionVat" + i](), 2, JSShop.Language.Translations.Locale.DecimalSeparator));
					}
				}

				// Totals

				y += 50;

				pdf.text(x + 250, y, "Moms");
				pdf.text(x + 400, y, Fit.Math.Format(order.Vat(), 2, JSShop.Language.Translations.Locale.DecimalSeparator));

				y += 20;

				pdf.text(x + 250, y, "Total inkl. moms");
				pdf.text(x + 400, y, Fit.Math.Format(order.Price() + order.Vat(), 2, JSShop.Language.Translations.Locale.DecimalSeparator));

				// Custom data

				if (order.PromoCode() !== "" || order.CustData1() !== "" || order.CustData2() !== "" || order.CustData3() !== "")
				{
					y += 30;

					if (order.PromoCode() !== "")
						pdf.text(x, y += 20, order.PromoCode());
					if (order.CustData1() !== "")
						pdf.text(x, y += 20, order.CustData1());
					if (order.CustData2() !== "")
						pdf.text(x, y += 20, order.CustData2());
					if (order.CustData3() !== "")
						pdf.text(x, y += 20, order.CustData3());
				}

				// Message

				if (order.Message() !== "")
				{
					y += 50;

					pdf.setFontStyle("bold");
					pdf.text(x, y, "Besked");
					pdf.setFontStyle("normal");

					var msg = order.Message();
					while (msg.indexOf("\n\n") > -1)
						msg = msg.replace("\n\n", "\n");

					pdf.text(x, y += 20, msg);

					y += 50;
				}
			});

			pdf.save("Export.pdf");
			//window.open(pdf.output("datauristring"));  //window.open(encodeURI(content));
		}

		var loaded = -1;

		Fit.Loader.LoadScript(JSShop.GetPath() + "/jspdf.min.js", function(src)
		{
			loaded++;

			if (loaded === process.length)
				execute();
		});

		Fit.Array.ForEach(process, function(order)
		{
			if (Fit.Validation.IsSet(order._internal.Entries) === true)
			{
				loaded++;

				if (loaded === process.length)
					execute();

				return;
			}

			JSShop.Models.OrderEntry.RetrieveAll(order.Id(), function(req, entries)
			{
				var productsLoaded = 0;
				var orderModel = order; // Make order available to closure created "later" during async. operation

				Fit.Array.ForEach(entries, function(entry)
				{
					var prod = new JSShop.Models.Product(entry.ProductId());
					prod.Retrieve(function(req, product)
					{
						productsLoaded++;
						entry.Product = product;

						if (productsLoaded === entries.length)
						{
							loaded++;
							orderModel._internal.Entries = entries;

							if (loaded === process.length)
								execute();
						}
					},
					function (req, product)
					{
						productsLoaded++;
						entry.Product = null;

						if (productsLoaded === entries.length)
						{
							loaded++;
							model._internal.Entries = entries;

							if (loaded === process.length)
								execute();
						}
					});
				});
			},
			function(req, entries)
			{
				loaded++;
				model._internal.Entries = null;

				if (loaded === process.length)
					execute();
			});
		});
	}

	window.PrintOrders = printOrders;

	function sendInvoices()
	{
		var processing = [];
		var failed = [];
		var skipped = [];
		var scheduled = 0;
		var processed = 0;

		Fit.Array.ForEach(process, function(model)
		{
			if (model.State() !== "Captured")
			{
				Fit.Array.Add(skipped, model.Id());
				return; // Skip, must be captured to send invoice
			}

			Fit.Array.Add(processing, model);
		});

		if (skipped.length > 0)
			Fit.Controls.Dialog.Alert(lang.OrdersSkipped + ":<br><br>" + skipped.join(((skipped.length <= 10) ? "<br>" : ", ")));

		if (processing.length === 0)
			return;

		var scheduled = processing.length;

		var execute = null;
		execute = function(model)
		{
			model.SendInvoice(function(req, m) // Success handler
			{
				//Fit.Array.Remove(processing, model);
				processed++;

				if (processing.length === 0)
				{
					if (processed === scheduled)
					{
						if (failed.length === 0)
							Fit.Controls.Dialog.Alert(lang.DoneSuccess);
						else
							Fit.Controls.Dialog.Alert(lang.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
					}
				}
				else
				{
					execute(processing[0]);
					Fit.Array.Remove(processing, processing[0]);
				}

				// SendInvoice(..), CapturePayment(..), and CancelPayment(..)
				// functions on Model does not update data. Calling Retrieve(..)
				// to fetch newly assigned Invoice ID.

				model.Retrieve(function(req, m)
				{
					model._internal.InvoiceElement.innerHTML = model.InvoiceId();
				});
			},
			function(req, m) // Error handler
			{
				//Fit.Array.Remove(processing, model);
				Fit.Array.Add(failed, model.Id());

				processed++;

				if (processing.length === 0)
				{
					if (processed === scheduled)
						Fit.Controls.Dialog.Alert(lang.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
				}
				else
				{
					execute(processing[0]);
					Fit.Array.Remove(processing, processing[0]);
				}
			});
		}

		var maxConcurrentRequests = 3;
		var remove = [];

		for (var i = 0 ; i < maxConcurrentRequests ; i++)
		{
			if (i <= processing.length - 1)
			{
				execute(processing[i]);
				Fit.Array.Add(remove, processing[i]);
			}
		}

		Fit.Array.ForEach(remove, function(model)
		{
			Fit.Array.Remove(processing, model);
		});

		/*Fit.Array.ForEach(processing, function(model)
		{
			model.SendInvoice(function(req, m) // Success handler
			{
				Fit.Array.Remove(processing, model);

				if (processing.length === 0)
				{
					if (failed.length === 0)
						Fit.Controls.Dialog.Alert(lang.DoneSuccess);
					else
						Fit.Controls.Dialog.Alert(lang.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
				}

				// SendInvoice(..), CapturePayment(..), and CancelPayment(..)
				// functions on Model does not update data. Calling Retrieve(..)
				// to fetch newly assigned Invoice ID.

				model.Retrieve(function(req, m)
				{
					model._internal.InvoiceElement.innerHTML = model.InvoiceId();
				});
			},
			function(req, m) // Error handler
			{
				Fit.Array.Remove(processing, model);
				Fit.Array.Add(failed, model.Id());

				if (processing.length === 0)
					Fit.Controls.Dialog.Alert(lang.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
			});
		});*/
	}

	var searchTimeout = -1;
	function filterOrders()
	{
		if (searchTimeout !== -1)
			clearTimeout(searchTimeout);

		searchTimeout = setTimeout(function()
		{
			chkSelectAll.Checked(false);

			Fit.Array.ForEach(models, function(model)
			{
				model._internal.CheckBox.Checked(false);
			});

			var searchType = lstSearchType.GetSelections()[0].Value;
			var searchString = txtSearch.Value().toLowerCase();

			if (searchType === "Exact")
			{
				searchString = searchString.replace(" ", " "); // Replace spaces with non-breaking spaces (ALT+Space on Mac)
			}
			else
			{
				searchString = searchString.replace(/["|'].*?["|']/g, function(match, offset, str)
				{
					return match.replace(/"|'/g, "").replace(" ", " "); // Remove quotes and replace spaces with non-breaking spaces (ALT+Space on Mac)
				});
			}

			var searches = ((searchType === "Exact") ? [ searchString ] : searchString.split(" "));
			var firstSearch = true;

			Fit.Array.ForEach(searches, function(search)
			{
				if (firstSearch === false && search === "")
					return;

				/*var exactMatch = false;

				if (search.indexOf("=") === 0)
				{
					search = search.substring(1);
					exactMatch = true;
				}*/

				chkSelectAll.Checked(false);

				Fit.Array.ForEach(models, function(model)
				{
					if (firstSearch === false && searchType === "ContainsAll" && model._internal.Match === false)
						return;

					if (firstSearch === false && searchType === "ContainsAny" && model._internal.Match === true)
						return;

					var exclude = [ "Time", "ClientIp", "Price", "Vat", "Weight", "CostCorrection1", "CostCorrectionVat1", "CostCorrection2", "CostCorrectionVat2", "CostCorrection3", "CostCorrectionVat3", "TransactionId" ];
					var match = false;

					Fit.Array.ForEach(model.GetProperties(), function(prop)
					{
						if (Fit.Array.Contains(exclude, prop) === true)
							return;

						var val = model[prop]().toString().toLowerCase();

						if (prop === "State")
							val = getStateTitle(model[prop]()).toLowerCase();

						val = val.replace(" ", " "); // Replace spaces with non-breaking spaces (ALT+Space on Mac)

						if ((searchType === "Exact" && val === search) || (searchType !== "Exact" && val.indexOf(search) > -1))
						{
							match = true;
							return false; // Break loop
						}
					});

					model._internal.Match = match;
					model._internal.DomElement.style.display = ((match === true) ? "" : "none");
				});

				firstSearch = false;
			});

			Fit.Array.ForEach(models, function(model)
			{
				delete model._internal.Match;
			});

			searchTimeout = -1;
		}, 250);
	}

	function NANANANNAdisplayOrder(model)
	{
		Fit.Validation.ExpectInstance(model, JSShop.Models.Order);

		var dia = new Fit.Controls.Dialog();
		dia.Content("<div id='JSShopOrderDetails'></div>");

		var cmdOk = new Fit.Controls.Button("JSShopOrderDetailsOkButton");
		cmdOk.Title(JSShop.Language.Translations.Common.Ok);
		cmdOk.Type(Fit.Controls.Button.Type.Success);
		cmdOk.OnClick(function(sender)
		{
			dia.Close();
			cmdOk.Dispose();
		});

		dia.AddButton(cmdOk);
		dia.Open();

		var container = dia.GetDomElement().querySelector("#JSShopOrderDetails");

		detailsPresenter = new JSShop.Presenters.OrderDetails(model);
		detailsPresenter.Render(container);

		/*JSShop.Models.OrderEntry.RetrieveAll(model.Id(), function(req, entryModels)
		{
			Fit.Array.ForEach(entryModels, function(entry)
			{
			});
		});*/
	}

	init();
}
