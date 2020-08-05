if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.OrderList = function()
{
	Fit.Core.Extend(this, JSShop.Presenters.Base).Apply();

	var me = this;
	var dom = null;
	var view = null;
	var models = [];
	var tagModels = [];
	var lang = JSShop.Language.Translations;

	var process = [];

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

	function init()
	{
		dom = document.createElement("div");

		loadView(function()
		{
			loadTagModels(function()
			{
				loadData();
				populateToolbarAndColumnsToView();
			});
		});

		createControls();
	}

	this.GetDomElement = function()
	{
		return dom;
	}

	//#region Initialization helpers (data and view)

	function createControls()
	{
		txtSearch = new Fit.Controls.Input("JSShopSearch");
		txtSearch.Width(140);
		txtSearch.GetDomElement().title = lang.OrderList.Search;
		txtSearch.Placeholder(lang.OrderList.Search + "..");

		//var now = new Date();
		//var yesterday = new Date(((new Date()).setDate(now.getDate() - 1)));

		txtFrom = new Fit.Controls.Input("JSShopFromDate");
		txtFrom.Required(true);
		txtFrom.SetValidationCallback(function(res)
		{
			try
			{
				Fit.Date.Parse(txtFrom.Value(), lang.Locale.DateFormat);
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
				txtFrom.Value(Fit.Date.Format(new Date(), lang.Locale.DateFormat));
			}
			else
			{
				// Parser is very forgiving - e.g. it allow letters in date (2016-05ABC-20).
				// Parse and re-format to make sure value in input field is properly formatted.
				var d = Fit.Date.Parse(txtFrom.Value(), lang.Locale.DateFormat);
				var v = Fit.Date.Format(d, lang.Locale.DateFormat);
				txtFrom.Value(v);
			}
		});
		txtFrom.Width(140);
		txtFrom.Value(Fit.Date.Format(new Date(), lang.Locale.DateFormat));
		txtFrom.GetDomElement().title = lang.OrderList.DisplayFromDate;

		txtTo = new Fit.Controls.Input("JSShopToDate");
		txtTo.Required(true);
		txtTo.SetValidationCallback(function(res)
		{
			try
			{
				Fit.Date.Parse(txtTo.Value(), lang.Locale.DateFormat);
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
				txtTo.Value(Fit.Date.Format(new Date(), lang.Locale.DateFormat));
			}
			else
			{
				// Parser is very forgiving - e.g. it allow letters in date (2016-05ABC-20).
				// Parse and re-format to make sure value in input field is properly formatted.
				var d = Fit.Date.Parse(txtTo.Value(), lang.Locale.DateFormat);
				var v = Fit.Date.Format(d, lang.Locale.DateFormat);
				txtTo.Value(v);
			}
		});
		txtTo.Width(140);
		txtTo.Value(Fit.Date.Format(new Date(), lang.Locale.DateFormat));
		txtTo.GetDomElement().title = lang.OrderList.DisplayToDate;

		cmdUpdate = new Fit.Controls.Button("JSShopUpdateButton");
		cmdUpdate.Icon("fa-refresh");
		cmdUpdate.Type(Fit.Controls.Button.Type.Primary);
		cmdUpdate.OnClick(function(sender)
		{
			loadData();
		});
		cmdUpdate.GetDomElement().title = lang.OrderList.Update;

		cmdExport = new Fit.Controls.Button("JSShopExportButton");
		cmdExport.Icon("fa-table");
		cmdExport.Type(Fit.Controls.Button.Type.Primary);
		cmdExport.OnClick(function(sender)
		{
			if (process.length === 0)
			{
				Fit.Controls.Dialog.Alert(lang.OrderList.SelectOrders);
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
			cmdPdf.Enabled((Fit.Browser.GetInfo().Name !== "MSIE" || Fit.Browser.GetInfo().Version >= 10)); // jsPDF requires IE10+

			var cmdCancel = new Fit.Controls.Button("JSShopExportCancelButton");
			cmdCancel.Title(lang.Common.Cancel);
			cmdCancel.Icon("fa-cancel");
			cmdCancel.Type(Fit.Controls.Button.Type.Danger);
			cmdCancel.OnClick(function(sender)
			{
				dia.Dispose();
			});

			var dia = new Fit.Controls.Dialog();
			dia.Content(lang.OrderList.ChooseFormat);
			dia.Modal(true);
			dia.AddButton(cmdCsv);
			dia.AddButton(cmdPdf);
			dia.AddButton(cmdCancel);
			dia.Open(me.GetDomElement().parentElement);
			cmdCsv.Focused(true);
		});
		cmdExport.GetDomElement().title = lang.OrderList.Export;

		cmdInvoice = new Fit.Controls.Button("JSShopInvoiceButton");
		cmdInvoice.Icon("fa-paperclip");
		cmdInvoice.Type(Fit.Controls.Button.Type.Primary);
		cmdInvoice.OnClick(function(sender)
		{
			if (process.length === 0)
			{
				Fit.Controls.Dialog.Alert(lang.OrderList.SelectOrders);
				return;
			}

			Fit.Controls.Dialog.Confirm(lang.OrderList.ConfirmAction + ": " + lang.OrderList.SendInvoice, function(res)
			{
				if (res === true)
					sendInvoices();
			});
		});
		cmdInvoice.GetDomElement().title = lang.OrderList.SendInvoice;

		if (JSShop.Settings.PaymentCaptureUrl)
		{
			cmdCapture = new Fit.Controls.Button("JSShopCaptureButton");
			cmdCapture.Icon("fa-exchange");
			cmdCapture.Type(Fit.Controls.Button.Type.Success);
			cmdCapture.OnClick(function(sender)
			{
				if (process.length === 0)
				{
					Fit.Controls.Dialog.Alert(lang.OrderList.SelectOrders);
					return;
				}

				Fit.Controls.Dialog.Confirm(lang.OrderList.ConfirmAction + ": " + lang.OrderList.Capture, function(res)
				{
					if (res === true)
						processPayments("Capture");
				});
			});
			cmdCapture.GetDomElement().title = lang.OrderList.Capture;
		}

		if (JSShop.Settings.PaymentCancelUrl)
		{
			cmdReject = new Fit.Controls.Button("JSShopReturnButton");
			cmdReject.Icon("fa-trash");
			cmdReject.Type(Fit.Controls.Button.Type.Danger);
			cmdReject.OnClick(function(sender)
			{
				if (process.length === 0)
				{
					Fit.Controls.Dialog.Alert(lang.OrderList.SelectOrders);
					return;
				}

				Fit.Controls.Dialog.Confirm(lang.OrderList.ConfirmAction + ": " + lang.OrderList.Reject, function(res)
				{
					if (res === true)
						processPayments("Reject");
				});
			});
			cmdReject.GetDomElement().title = lang.OrderList.Reject;
		}

		if (JSShop.Settings.ConfigUrl)
		{
			cmdConfig = new Fit.Controls.Button("JSShopConfigButton");
			cmdConfig.Icon("fa-cog");
			cmdConfig.Type(Fit.Controls.Button.Type.Warning);
			cmdConfig.OnClick(function(sender)
			{
				location.href = JSShop.Settings.ConfigUrl;
			});
			cmdConfig.GetDomElement().title = lang.OrderList.Settings;
		}

		chkSelectAll = new Fit.Controls.CheckBox("JSShopSelectAll");
		chkSelectAll.OnChange(function(sender)
		{
			if (chkSelectAll.Focused() === true)
			{
				Fit.Array.ForEach(models, function(model)
				{
					model._presenter.checkBox.Checked(chkSelectAll.Checked());
				});
			}
		});
	}

	function loadView(onComplete)
	{
		Fit.Validation.ExpectFunction(onComplete);

		if (document.querySelector("link[href*='/Views/OrderList/OrderList.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
		{
			Fit.Browser.Log("Lazy loading OrderList.css");
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/OrderList/OrderList.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));
		}

		var tpl = new Fit.Template(true);
		tpl.AllowUnsafeContent(false);
		tpl.LoadUrl(JSShop.GetPath() + "/Views/OrderList/OrderList.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"), function(sender, html)
		{
			view = sender;
			onComplete();
		});
		tpl.Render(dom);
	}

	function loadData(onComplete) // This function can be called multiple times - use it to reload data
	{
		Fit.Validation.ExpectFunction(onComplete, true);

		cmdUpdate.Enabled(false); // Will be enabled again when data is done loading

		var oldModels = models;
		models = [];
		process = [];

		var from = Fit.Date.Parse(txtFrom.Value() + " 00:00:00", lang.Locale.DateFormat + " hh:mm:ss").getTime();
		var to = Fit.Date.Parse(txtTo.Value() + " 23:59:59", lang.Locale.DateFormat + " hh:mm:ss").getTime();

		loadOrderModels(txtSearch.Value(), from, to, function()
		{
			Fit.Array.ForEach(oldModels, function(model)
			{
				if (model._presenter)
				{
					model._presenter.checkBox.Dispose();
				}
			});

			populateOrderDataToView();

			cmdUpdate.Enabled(true);

			if (onComplete)
			{
				onComplete();
			}
		},
		function()
		{
			cmdUpdate.Enabled(true);
		});
	}

	function loadOrderModels(search, fromTimeStamp, toTimeStamp, onComplete, onError)
	{
		Fit.Validation.ExpectString(search);
		Fit.Validation.ExpectInteger(fromTimeStamp);
		Fit.Validation.ExpectInteger(toTimeStamp);
		Fit.Validation.ExpectFunction(onComplete);
		Fit.Validation.ExpectFunction(onError);

		var expr = /^\$(.+?) (.+?) (.+)$/.exec(search); // E.g. "$FirstName = James" or "$ZipCode >= 5000"

		if (expr !== null)
		{
			var field = expr[1];	// E.g. FirstName, AltFirstName, CustData2
			var operator = expr[2];	// E.g. CONTAINS, <, <=, >, >=, =, !=
			//var match = expr[3];	// E.g. "James"

			if (Fit.Array.Contains(Fit.Array.GetKeys((new JSShop.Models.Order("-1")).GetProperties()), field) === false)
			{
				Fit.Controls.Dialog.Alert(lang.OrderList.SearchErrorField);

				if (Fit.Validation.IsSet(onError) === true)
				{
					onError();
				}

				return;
			}

			if (operator != "=" && operator != "!=" && operator != "<" && operator != "<=" && operator != ">" && operator != ">=" && operator.toUpperCase() != "CONTAINS")
			{
				Fit.Controls.Dialog.Alert(lang.OrderList.SearchErrorOperator);

				if (Fit.Validation.IsSet(onError) === true)
				{
					onError();
				}

				return;
			}
		}

		JSShop.Models.Order.RetrieveAll(search, fromTimeStamp, toTimeStamp, function(req, modelInstances)
		{
			models = modelInstances;
			onComplete();
		},
		function()
		{
			onError();
		});
	}

	function loadTagModels(onComplete)
	{
		Fit.Validation.ExpectFunction(onComplete);

		JSShop.Models.Tag.RetrieveAll("Order", function(req, modelInstances)
		{
			tagModels = modelInstances;
			onComplete();
		});
	}

	function populateToolbarAndColumnsToView()
	{
		view.Content.ToolbarFromDateField = txtFrom.GetDomElement();
		view.Content.ToolbarToDateField = txtTo.GetDomElement();
		view.Content.ToolbarSearchField = txtSearch.GetDomElement();
		view.Content.ToolbarUpdateButton = cmdUpdate.GetDomElement();
		view.Content.ToolbarExportButton = cmdExport.GetDomElement();
		view.Content.ToolbarInvoiceButton = cmdInvoice.GetDomElement();
		view.Content.ToolbarCaptureButton = cmdCapture && cmdCapture.GetDomElement() || null;
		view.Content.ToolbarRejectButton = cmdReject && cmdReject.GetDomElement() || null;
		view.Content.ToolbarConfigButton = cmdConfig && cmdConfig.GetDomElement() || null;

		view.Content.HeaderSelectAll = chkSelectAll.GetDomElement();
		view.Content.HeaderHeaderOrderId = lang.OrderList.OrderId;
		view.Content.HeaderTime = lang.OrderList.Time;
		view.Content.HeaderCustomer = lang.OrderList.Customer;
		view.Content.HeaderAmount = lang.OrderList.Amount;
		view.Content.HeaderPaymentMethod = lang.OrderList.PaymentMethod;
		view.Content.HeaderState = lang.OrderList.State;
		view.Content.HeaderInvoiceId = lang.OrderList.InvoiceId;
	}

	function populateOrderDataToView() // May be invoked multiple times to refresh the view (e.g. to refresh tags when renamed/deleted)
	{
		view.Content.Orders.Clear();
		var allSelected = (models.length > 0);

		Fit.Array.ForEach(models, function(model)
		{
			if (!model._presenter)
			{
				model._presenter = {};

				model._presenter.checkBox = new Fit.Controls.CheckBox("JSShopOrder" + model.Id());
				model._presenter.checkBox.OnChange(function(sender)
				{
					if (sender.Checked() === true)
						Fit.Array.Add(process, model);
					else
						Fit.Array.Remove(process, model);

					if (sender.Focused() === true)
						updateSelectAll();
				});

				model._presenter.lnkCustomerDetails = document.createElement("a");
				model._presenter.lnkCustomerDetails.href = "javascript:";
				model._presenter.lnkCustomerDetails.onclick = function(e)
				{
					var target = Fit.Events.GetTarget(e);
					var oc = target.onclick;
					target.onclick = null;

					displayCustomerDetails(model, function()
					{
						target.onclick = oc;
					});
				}
				model._presenter.lnkCustomerDetails.innerHTML = model.FirstName() + " " + model.LastName();

				model._presenter.lnkAmountWithDetails = document.createElement("a");
				model._presenter.lnkAmountWithDetails.href = "javascript:";
				model._presenter.lnkAmountWithDetails.onclick = function(e)
				{
					var target = Fit.Events.GetTarget(e);
					var oc = target.onclick;
					target.onclick = null;

					displayOrderEntries(model, function()
					{
						target.onclick = oc;
					});
				}
				model._presenter.lnkAmountWithDetails.innerHTML = Fit.Math.Format(model.Price() + model.Vat(), 2, lang.Locale.DecimalSeparator);

				model._presenter.stateElement = document.createElement("a");
				model._presenter.stateElement.href = "javascript:";
				model._presenter.stateElement.innerHTML = getStateTitle(model.State());
				model._presenter.stateElement.onclick = function(e)
				{
					var target = Fit.Events.GetTarget(e);
					var oc = target.onclick;
					target.onclick = null;

					displayStateDialog(model, function(tagsChanged)
					{
						// Callback invoked if changes were made to tags on order, or if tags were renamed/removed

						if (tagsChanged === true)
						{
							// Tags were renamed or deleted - reload entire view as other orders may reference updated tags

							populateOrderDataToView();
						}
						else
						{
							// Tags were added/removed from order - affects only current order

							var newAltStateText = getTagTitlesByIds(model.TagIds() !== "" ? model.TagIds().split(";") : []);
							model._presenter.stateElement.innerHTML = (newAltStateText !== "" ? newAltStateText : getStateTitle(model.State()));
						}
					},
					function()
					{
						target.onclick = oc;
					});
				}

				model._presenter.invoiceElement = document.createElement("span");
				model._presenter.invoiceElement.innerHTML = model.InvoiceId();
			}

			var altStateText = getTagTitlesByIds(model.TagIds() !== "" ? model.TagIds().split(";") : []);
			model._presenter.stateElement.innerHTML = (altStateText !== "" ? altStateText : getStateTitle(model.State()));

			if (model._presenter.checkBox.Checked() === false)
			{
				allSelected = false;
			}

			var item = view.Content.Orders.AddItem();

			item.Select = model._presenter.checkBox.GetDomElement();
			item.OrderId = model.Id();
			item.Time = Fit.Date.Format(new Date(model.Time()), lang.Locale.DateFormat + " " + lang.Locale.TimeFormat);
			item.Customer = model._presenter.lnkCustomerDetails;
			item.Currency = model.Currency();
			item.Amount = model._presenter.lnkAmountWithDetails;
			item.PaymentMethod = model.PaymentMethod();
			item.State = model._presenter.stateElement;
			item.InvoiceId = model._presenter.invoiceElement;
		});

		chkSelectAll.Checked(allSelected);

		view.Update();
	}

	//#endregion

	function getStateTitle(state)
	{
		Fit.Validation.ExpectStringValue(state);

		if (state === "Initial")
			return lang.OrderList.StateInitial;
		else if (state === "Authorized")
			return lang.OrderList.StateAuthorized;
		else if (state === "Captured")
			return lang.OrderList.StateCaptured;
		else if (state === "Canceled")
			return lang.OrderList.StateCanceled;

		return "UNKNOWN";
	}

	function getTagTitlesByIds(ids)
	{
		Fit.Validation.ExpectTypeArray(ids, Fit.Validation.ExpectString);

		var titles = [];
		var existingTagsIds = [];

		Fit.Array.ForEach(tagModels, function(tag)
		{
			Fit.Array.Add(existingTagsIds, tag.Id());

			if (Fit.Array.Contains(ids, tag.Id()) === true)
			{
				Fit.Array.Add(titles, tag.Title());
			}
		});

		Fit.Array.ForEach(ids, function(id)
		{
			if (Fit.Array.Contains(existingTagsIds, id) === false)
			{
				Fit.Array.Add(titles, lang.OrderList.UnknownTag);
			}
		});

		return titles.join(", ");
	}

	function processPayments(type)
	{
		Fit.Validation.ExpectStringValue(type);

		var processing = [];
		var failed = [];
		//var skipped = [];
		var scheduled = 0;
		var processed = 0;

		Fit.Array.ForEach(process, function(model)
		{
			if ((model.State() === "Captured" && type === "Capture") || (model.State() === "Canceled" && type === "Reject"))
			{
				//Fit.Array.Add(skipped, model.Id());
				return; // Skip, already in desired state
			}

			Fit.Array.Add(processing, model);
		});

		//if (skipped.length > 0)
		//	Fit.Controls.Dialog.Alert("Skipped:<br><br>" + skipped.join(((skipped.length <= 10) ? "<br>" : ", ")));

		if (processing.length === 0)
			return;

		var statusDialog = new JSShop.Presenters.StatusDialog();
		statusDialog.Text(lang.OrderList.Processing);
		statusDialog.Modal(true);
		statusDialog.WarnOnExit(true, lang.OrderList.NavigateAway);
		statusDialog.Open();

		var scheduled = processing.length;

		var execute = null;
		execute = function(model)
		{
			Fit.Validation.ExpectInstance(model, JSShop.Models.Order);

			var method = ((type === "Capture") ? model.CapturePayment : model.CancelPayment);

			method(function(req, m) // Success handler
			{
				processed++;
				statusDialog.Progress(Fit.Math.Round((processed/scheduled) * 100));

				model._presenter.stateElement.innerHTML = getStateTitle(model.State());

				if (processing.length === 0) // No more requests to be made
				{
					if (processed === scheduled) // All responses have been received
					{
						statusDialog.Dispose();

						if (failed.length === 0)
							Fit.Controls.Dialog.Alert(lang.OrderList.DoneSuccess);
						else
							Fit.Controls.Dialog.Alert(lang.OrderList.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
					}
				}
				else // More requests to be made
				{
					var nextModel = processing[0];
					Fit.Array.Remove(processing, nextModel);
					execute(nextModel);
				}
			},
			function(req, m) // Error handler
			{
				Fit.Array.Add(failed, model.Id());

				processed++;
				statusDialog.Progress(Fit.Math.Round((processed/scheduled) * 100));

				if (processing.length === 0) // No more requests to be made
				{
					if (processed === scheduled) // All responses have been received
					{
						statusDialog.Dispose();
						Fit.Controls.Dialog.Alert(lang.OrderList.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
					}
				}
				else // More requests to be made
				{
					var nextModel = processing[0];
					Fit.Array.Remove(processing, nextModel);
					execute(nextModel);
				}
			});
		};

		var maxConcurrentRequests = 3;
		var startProcessing = [];

		// Get up to a maximum of X number of models (specified in maxConcurrentRequests)
		for (var i = 0 ; i < maxConcurrentRequests ; i++)
		{
			if (i <= processing.length - 1)
			{
				Fit.Array.Add(startProcessing, processing[i]);
			}
		}

		// Remove models from processing array
		Fit.Array.ForEach(startProcessing, function(model)
		{
			Fit.Array.Remove(processing, model);
		});

		// Invoke requests
		Fit.Array.ForEach(startProcessing, function(model)
		{
			execute(model);
		});
	}

	function updateSelectAll()
	{
		var allSelected = true;

		Fit.Array.ForEach(models, function(model)
		{
			if (model._presenter.checkBox.Checked() === false)
			{
				allSelected = false;
				return false;
			}
		});

		chkSelectAll.Checked(allSelected);
	}

	function displayCustomerDetails(model, closedCallback)
	{
		Fit.Validation.ExpectInstance(model, JSShop.Models.Order);
		Fit.Validation.ExpectFunction(closedCallback);

		var dia = new Fit.Controls.Dialog();
		dia.Modal(true);

		var cmdOk = new Fit.Controls.Button("JSShopOrderDetailsOkButton");
		cmdOk.Title(lang.Common.Ok);
		cmdOk.Type(Fit.Controls.Button.Type.Success);
		cmdOk.OnClick(function(sender)
		{
			dia.Dispose();
			closedCallback();
		});
		dia.AddButton(cmdOk);

		if (document.querySelector("link[href*='/Views/OrderList/DialogCustomerDetails.css']") === null)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/OrderList/DialogCustomerDetails.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		var tpl = new Fit.Template();
		tpl.AllowUnsafeContent(true);
		tpl.LoadUrl(JSShop.GetPath() + "/Views/OrderList/DialogCustomerDetails.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"), function(sender, html)
		{
			// Headlines

			tpl.Content.CustomerDetailsHeadline = lang.OrderList.CustomerDetails;
			tpl.Content.AlternativeAddressHeadline = lang.OrderList.AlternativeAddress;
			tpl.Content.CustomerMessageHeadline = lang.OrderList.Message;
			tpl.Content.CustomDataHeadline = lang.OrderList.CustomData;

			// Data that maps directly from model to view

			var data = [ "Company", "FirstName", "LastName", "Address", "ZipCode", "City", "Phone", "Email", "Message" ];
			data = Fit.Array.Merge(data, [ "AltCompany", "AltFirstName", "AltLastName", "AltAddress", "AltZipCode", "AltCity" ]);

			Fit.Array.ForEach(data, function(d)
			{
				tpl.Content[d] = model[d]().replace(/\n/g, "<br>");
			});

			// Custom data

			var custData = "";
			for (var i = 1 ; i <= 3 ; i++)
			{
				if (model["CustData" + i]() !== "")
				{
					if (model["CustData" + i]() === "")
						continue;

					custData += ((custData !== "") ? "<br>" : "") + model["CustData" + i]();
				}
			}
			tpl.Content.CustomData = custData;

			// Determine whether to hide certain sections or not

			tpl.Content.AltAddressDisplay = ((model.AltAddress() !== "") ? "block" : "none");
			tpl.Content.MessageDisplay = ((model.Message() !== "") ? "block" : "none");
			tpl.Content.CustomDataDisplay = ((custData !== "") ? "block" : "none");

			// Finalize

			tpl.Render(dia.GetContentDomElement());
			dia.Open(me.GetDomElement().parentElement);
			cmdOk.Focused(true);
		});
	}

	function displayOrderEntries(model, closedCallback)
	{
		Fit.Validation.ExpectInstance(model, JSShop.Models.Order);
		Fit.Validation.ExpectFunction(closedCallback);

		var dia = new Fit.Controls.Dialog();
		dia.Modal(true);
		dia.MaximumWidth(95, "%");

		var cmdOk = new Fit.Controls.Button("JSShopOrderEntriesOkButton");
		cmdOk.Title(lang.Common.Ok);
		cmdOk.Type(Fit.Controls.Button.Type.Success);
		cmdOk.OnClick(function(sender)
		{
			dia.Dispose();
			closedCallback();
		});
		dia.AddButton(cmdOk);

		if (document.querySelector("link[href*='/Views/OrderList/DialogOrderEntries.css']") === null)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/OrderList/DialogOrderEntries.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		var tpl = new Fit.Template();
		tpl.AllowUnsafeContent(false);
		tpl.LoadUrl(JSShop.GetPath() + "/Views/OrderList/DialogOrderEntries.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"), function(sender, html)
		{
			// Headers

			tpl.Content.HeaderProduct = lang.OrderList.Product;
			tpl.Content.HeaderUnitPrice = lang.OrderList.UnitPrice;
			tpl.Content.HeaderUnits = lang.OrderList.Units;
			tpl.Content.HeaderPrice = lang.OrderList.Price;
			tpl.Content.HeaderTotalVat = lang.OrderList.TotalVat;
			tpl.Content.HeaderTotalPrice = lang.OrderList.TotalPrice;

			// Populate data

			var populateEntries = function(entryModels)
			{
				var item = null;

				Fit.Array.ForEach(entryModels, function(entry)
				{
					var pricing = JSShop.CalculatePricing(entry.UnitPrice(), entry.Units(), entry.Vat(), entry.Discount());

					item = tpl.Content.OrderEntries.AddItem();
					item.Title = ((entry.Product !== null) ? entry.Product.Title() : entry.ProductId());
					item.UnitPrice = Fit.Math.Format(pricing.UnitPriceInclVat, 2, lang.Locale.DecimalSeparator);
					item.DiscountMessage = entry.DiscountMessage();
					item.Discount = ((pricing.DiscountInclVat > 0 || pricing.DiscountInclVat < 0) ? Fit.Math.Format(pricing.DiscountInclVat * -1, 2, lang.Locale.DecimalSeparator) : "");
					item.Units = entry.Units();
					item.Currency = entry.Currency();
					item.Price = Fit.Math.Format(pricing.TotalInclVat, 2, lang.Locale.DecimalSeparator);
				});

				for (var i = 1 ; i <= 3 ; i++)
				{
					if (model["CostCorrection" + i]() > 0 || model["CostCorrection" + i]() < 0)
					{
						item = tpl.Content.OrderEntries.AddItem();
						item.Title = model["CostCorrectionMessage" + i]();
						item.UnitPrice = "";
						item.DiscountMessage = "";
						item.Discount = "";
						item.Units = "";
						item.Currency = model.Currency();
						item.Price = Fit.Math.Format(model["CostCorrection" + i]() + model["CostCorrectionVat" + i](), 2, lang.Locale.DecimalSeparator);
					}
				}

				tpl.Content.Currency = model.Currency();
				tpl.Content.TotalVat = Fit.Math.Format(model.Vat(), 2, lang.Locale.DecimalSeparator);
				tpl.Content.TotalPrice = Fit.Math.Format(model.Price() + model.Vat(), 2, lang.Locale.DecimalSeparator);

				tpl.Render(dia.GetContentDomElement());
				dia.Open(me.GetDomElement().parentElement);
				cmdOk.Focused(true);
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
	}

	function displayStateDialog(model, updateStateCallback, closedCallback)
	{
		Fit.Validation.ExpectInstance(model, JSShop.Models.Order);
		Fit.Validation.ExpectFunction(updateStateCallback);
		Fit.Validation.ExpectFunction(closedCallback);

		// TODO at some point (low priority) - inconsistency:
		// It's kind of odd that renaming/deleting a tag happens instantly while
		// new tags on the other hand are first created when the OK button is clicked.

		var dia = new Fit.Controls.Dialog();
		dia.Modal(true);
		dia.MinimumHeight(22, "em");

		var tagsCreated = {};
		var tagsChanged = false;

		var treeview = new Fit.Controls.TreeView("JSShopStateDialogTreeViewPicker");
		var dropdown = new Fit.Controls.DropDown("JSShopStateDialogDropDown");
		dropdown.Width(100, "%");
		dropdown.DropDownMaxHeight(10, "em");
		dropdown.MultiSelectionMode(true);
		dropdown.SetPicker(treeview);
		dropdown.InputEnabled(true);
		dropdown.OnInputChanged(function(sender, value)
		{
			dropdown.CloseDropDown();
		});
		dropdown.OnBlur(function(sender)
		{
			var text = dropdown.GetInputValue();

			if (text !== "")
			{
				var existing = null;

				Fit.Array.ForEach(treeview.GetChildren(), function(node)
				{
					if (node.Title().toLowerCase() === text.toLowerCase())
					{
						existing = node;
						return false; // Break loop
					}
				});

				if (existing === null)
				{
					var id = Fit.Data.CreateGuid();
					dropdown.AddSelection(text, id);
					tagsCreated[id] = { Id: id, Title: text, Type: "Order" };
				}
				else
				{
					dropdown.AddSelection(existing.Title(), existing.Value());
				}
			}
		});

		// Add context menu to picker control (tree view)
		// which allows us to rename and remove tags.

		var ctx = new Fit.Controls.ContextMenu();
		treeview.OnContextMenu(function(sender, node)
		{
			ctx.RemoveAllChildren(true);
			ctx.AddChild(new Fit.Controls.ContextMenuItem("<span style='display: inline-block; width: 1.1em;' class='fa fa-edit'></span> Rename", "Rename;" + node.Title() + ";" + node.Value()));
			ctx.AddChild(new Fit.Controls.ContextMenuItem("<span style='display: inline-block; width: 1.1em; color: red;' class='fa fa-remove'></span> Delete", "Delete;" + node.Title() + ";" + node.Value()));

			ctx.Show();
		});
		ctx.OnSelect(function(sender, item)
		{
			ctx.Hide();

			var info = item.Value().split(";"); // 0 = Command, 1 = Tag name, 2 = Tag ID

			if (info[0] === "Delete")
			{
				Fit.Controls.Dialog.Confirm(lang.OrderList.DeleteTagWarning.replace("{0}", info[1]), function(res)
				{
					if (res === false)
						return;

					tagsChanged = true;

					Fit.Array.ForEach(tagModels, function(tagModel)
					{
						if (info[2] === tagModel.Id())
						{
							tagModel.Delete(function()
							{
								dropdown.RemoveSelection(info[2]);
								treeview.RemoveChild(treeview.GetChild(info[2]));

								Fit.Array.Remove(tagModels, tagModel);
							});

							return false; // Break loop
						}
					});

					if (tagsCreated[info[2]])
					{
						delete tagsCreated[info[2]];
					}
				});
			}
			else if (info[0] === "Rename")
			{
				Fit.Controls.Dialog.Prompt(lang.OrderList.RenameTag.replace("{0}", info[1]), info[1], function(newTitle)
				{
					if (newTitle === null || newTitle === "" || newTitle === info[1])
					{
						return;
					}

					tagsChanged = true;

					Fit.Array.ForEach(tagModels, function(tagModel)
					{
						if (info[2] === tagModel.Id())
						{
							tagModel.Title(newTitle);
							tagModel.Update(function()
							{
								if (dropdown.GetSelectionByValue(info[2]) !== null)
								{
									dropdown.RemoveSelection(info[2]);
									dropdown.AddSelection(tagModel.Title(), tagModel.Id());
								}

								treeview.GetChild(info[2]).Title(newTitle);
							});

							return false; // Break loop
						}
					});

					if (tagsCreated[info[2]])
					{
						tagsCreated[info[2]].Title = newTitle;
					}
				});
			}
		});

		treeview.Width(100, "%");
		treeview.ContextMenu(ctx);
		treeview.Selectable(true, dropdown.MultiSelectionMode());

		// Add selections to dropdown and all tags to picker control

		var selected = (model.TagIds() !== "" ? model.TagIds().split(";") : []);

		Fit.Array.ForEach(tagModels, function(tag)
		{
			var node = new Fit.Controls.TreeViewNode(tag.Title(), tag.Id());
			treeview.AddChild(node);

			if (Fit.Array.Contains(selected, tag.Id()) === true)
			{
				dropdown.AddSelection(tag.Title(), tag.Id());
			}
		});

		Fit.Array.ForEach(selected, function(tagId)
		{
			if (treeview.GetChild(tagId) === null)
			{
				dropdown.AddSelection(lang.OrderList.UnknownTag, tagId);
			}
		});

		// Helper functions

		var disposeControls = function()
		{
			// No need to dispose buttons, treeview, or context menu.
			// They are automatically disposed when calling Dialog.Dispose()
			// and DropDown.Dispose().

			dia.Dispose();
			dropdown.Dispose();
			closedCallback();
		}

		// Create OK and Cancel buttons

		var cmdOk = new Fit.Controls.Button("JSShopStateDialogOkButton");
		cmdOk.Title(lang.Common.Ok);
		cmdOk.Type(Fit.Controls.Button.Type.Success);
		cmdOk.OnClick(function(sender)
		{
			// Callback responsible for updating order with selected tags

			var updateOrder = function(tags)
			{
				cmdOk.Enabled(false);

				model.TagIds(tags.join(";"));
				model.Update(function(sender, orderModel)
				{
					updateStateCallback(tagsChanged);
					disposeControls();
				});
			};

			// Get IDs for selected tags

			var selections = dropdown.GetSelections();
			var selectedTags = [];

			Fit.Array.ForEach(selections, function(selection)
			{
				if (tagsCreated[selection.Value] === undefined) // Skip new tags - must first be created server side (see further down)
				{
					Fit.Array.Add(selectedTags, selection.Value);
				}
			});

			// Handle non-existing (new) tags if any is added

			if (Fit.Array.Count(tagsCreated) === 0)
			{
				// No new tags added - update order

				updateOrder(selectedTags);
			}
			else
			{
				// Create non-existing tags

				tagsChanged = true;

				Fit.Array.ForEach(tagsCreated, function(tagId)
				{
					var tagInfo = tagsCreated[tagId];

					var tagModel = new JSShop.Models.Tag(tagInfo.Id);
					tagModel.Type(tagInfo.Type);
					tagModel.Title(tagInfo.Title);
					tagModel.Create(function(req, tModel)
					{
						Fit.Array.Add(tagModels, tagModel);

						// Notice: Backend may have updated model (tag) - e.g. replaced Id (GUID) with something else.
						// That's why we are waiting for tags to complete creation before updating Order. At this point
						// the Model mechanism will have received any changes and updated the internal model data, making
						// such changes available by now.

						Fit.Array.Add(selectedTags, tagModel.Id());

						if (selections.length === selectedTags.length)
						{
							updateOrder(selectedTags);
						}
					});
				});
			}
		});
		dia.AddButton(cmdOk);

		var cmdCancel = new Fit.Controls.Button("JSShopStateDialogCancelButton");
		cmdCancel.Title(lang.Common.Cancel);
		cmdCancel.Type(Fit.Controls.Button.Type.Danger);
		cmdCancel.OnClick(function(sender)
		{
			disposeControls();

			if (tagsChanged === true)
			{
				// Tags were renamed or removed
				updateStateCallback(true);
			}
		});
		dia.AddButton(cmdCancel);

		// Load dialog content

		if (document.querySelector("link[href*='/Views/OrderList/OrderTags.css']") === null)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/OrderList/OrderTags.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		var tpl = new Fit.Template();
		tpl.AllowUnsafeContent(false);
		tpl.LoadUrl(JSShop.GetPath() + "/Views/OrderList/OrderTags.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"), function(sender, html)
		{
			tpl.Content.Headline = lang.OrderList.State;
			tpl.Content.PaymentText = lang.OrderList.PaymentState + ": ";
			tpl.Content.PaymentValue = getStateTitle(model.State());
			tpl.Content.TagText = lang.OrderList.CustomState;
			tpl.Content.TagValue = dropdown.GetDomElement();

			tpl.Render(dia.GetContentDomElement());
			dia.Open(me.GetDomElement().parentElement);
			dropdown.Focused(true);
		});
	}

	function exportData()
	{
		var execute = function()
		{
			var data = [ "Id", "Time", "ClientIp", "Company", "FirstName", "LastName", "Address", "ZipCode", "City", "Email", "Phone", "Message", "AltCompany", "AltFirstName", "AltLastName", "AltAddress", "AltZipCode", "AltCity", "Price", "Vat", "Currency", "Weight", "WeightUnit", "CostCorrection1", "CostCorrectionVat1", "CostCorrectionMessage1", "CostCorrection2", "CostCorrectionVat2", "CostCorrectionMessage2", "CostCorrection3", "CostCorrectionVat3", "CostCorrectionMessage3", "PaymentMethod", "TransactionId", "State", "TagIds", "PromoCode", "CustData1", "CustData2", "CustData3" ];
			var renames = { "TagIds": "Tags" };
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
					else if (key === "TagIds")
					{
						Fit.Array.Add(item, "\"" + getTagTitlesByIds(model.TagIds() !== "" ? model.TagIds().split(";") : []) + "\"");
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
			});

			var content = "";
			Fit.Array.ForEach(data, function(key)
			{
				content += (content !== "" ? "," : "") + "\"" + (renames[key] ? renames[key] : key) + "\"";;
			});

			Fit.Array.ForEach(items, function(item)
			{
				content += "\n" + item.join(",");
			});

			if (navigator.msSaveOrOpenBlob) // Newer versions of Internet Explorer and older versions of Microsoft Edge (prior to Edge with Chromium engine)
			{
				var blob = new Blob([ content ], { type: "text/csv" });
				navigator.msSaveOrOpenBlob(blob, "Export.csv");
			}
			else if (Fit.Browser.GetInfo().Name === "MSIE") // Older versions of Internet Explorer
			{
				var ifr = document.createElement("iframe");
				ifr.style.display = "none";
				document.body.appendChild(ifr);
				var doc = ifr.contentDocument;

				doc.write(content);
				doc.close();
				doc.execCommand('SaveAs', true, "Export.csv");

				Fit.Dom.Remove(ifr);
			}
			else // Modern browsers
			{
				window.open(encodeURI("data:text/csv;charset=utf-8," + content));
			}
		}

		execute();
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
				pdf.text(x, y, lang.OrderList.Order + " " + order.Id());
				pdf.setFontSize(fontSize);
				pdf.setFontStyle("normal");

				pdf.setFontSize(fontSize);
				pdf.text(450, y, Fit.Date.Format(new Date(order.Time()), lang.Locale.DateFormat + " " + lang.Locale.TimeFormat));
				pdf.setFontSize(fontSize);

				y += 30;

				// Customer details

				var customerY = y;

				pdf.setFontStyle("bold");
				pdf.text(x, customerY += 20, lang.OrderList.CustomerDetails);
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
				pdf.text(x + 250, deliveryY += 20, lang.OrderList.AlternativeAddress);
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
				pdf.text(x, y, lang.OrderList.Order);
				pdf.setFontStyle("normal");

				y += 20;

				pdf.text(x, y, lang.OrderList.Product);
				pdf.text(x + 250, y, lang.OrderList.UnitPrice);
				pdf.text(x + 350, y, lang.OrderList.Units);
				pdf.text(x + 400, y, lang.OrderList.Price);

				Fit.Array.ForEach(order._presenter.Entries, function(entry)
				{
					y += 20;

					var pricing = JSShop.CalculatePricing(entry.UnitPrice(), entry.Units(), entry.Vat(), entry.Discount());

					pdf.text(x, y, ((entry.Product !== null) ? entry.Product.Title() : entry.ProductId()));
					pdf.text(x + 250, y, Fit.Math.Format(pricing.UnitPriceInclVat, 2, lang.Locale.DecimalSeparator));
					pdf.text(x + 350, y, entry.Units().toString());
					pdf.text(x + 400, y, Fit.Math.Format(pricing.TotalInclVat, 2, lang.Locale.DecimalSeparator));

					if (entry.Discount() !== 0)
					{
						y += 14;

						pdf.setFontSize(minorSize);

						pdf.text(x, y, entry.DiscountMessage());
						pdf.text(x + 400, y, Fit.Math.Format(pricing.DiscountInclVat * -1, 2, lang.Locale.DecimalSeparator));

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
						pdf.text(x + 400, y, Fit.Math.Format(order["CostCorrection" + i]() + order["CostCorrectionVat" + i](), 2, lang.Locale.DecimalSeparator));
					}
				}

				// Totals

				y += 50;

				pdf.text(x + 250, y, lang.OrderList.TotalVat);
				pdf.text(x + 400, y, Fit.Math.Format(order.Vat(), 2, lang.Locale.DecimalSeparator));

				y += 20;

				pdf.text(x + 250, y, lang.OrderList.TotalPrice);
				pdf.text(x + 400, y, Fit.Math.Format(order.Price() + order.Vat(), 2, lang.Locale.DecimalSeparator));

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
					pdf.text(x, y, lang.OrderList.Message);
					pdf.setFontStyle("normal");

					var msg = order.Message();
					while (msg.indexOf("\n\n") > -1)
						msg = msg.replace("\n\n", "\n");

					pdf.text(x, y += 20, msg);

					y += 50;
				}
			});

			pdf.save(lang.OrderList.Export + ".pdf");
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
			if (Fit.Validation.IsSet(order._presenter.Entries) === true)
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
							orderModel._presenter.Entries = entries;

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
							model._presenter.Entries = entries;

							if (loaded === process.length)
								execute();
						}
					});
				});
			},
			function(req, entries)
			{
				loaded++;
				model._presenter.Entries = null;

				if (loaded === process.length)
					execute();
			});
		});
	}

	function sendInvoices()
	{
		var processing = [];
		var failed = [];
		var scheduled = 0;
		var processed = 0;

		Fit.Array.ForEach(process, function(model)
		{
			Fit.Array.Add(processing, model);
		});

		if (processing.length === 0)
			return;

		var scheduled = processing.length;

		var statusDialog = new JSShop.Presenters.StatusDialog();
		statusDialog.Text(lang.OrderList.Processing);
		statusDialog.Modal(true);
		statusDialog.WarnOnExit(true, lang.OrderList.NavigateAway);
		statusDialog.Open();

		var execute = null;
		execute = function(model)
		{
			Fit.Validation.ExpectInstance(model, JSShop.Models.Order);

			model.SendInvoice(function(req, m) // Success handler
			{
				processed++;
				statusDialog.Progress(Fit.Math.Round((processed/scheduled) * 100));

				if (processing.length === 0) // No more requests to be made
				{
					if (processed === scheduled) // All responses have been received
					{
						statusDialog.Dispose();

						if (failed.length === 0)
							Fit.Controls.Dialog.Alert(lang.OrderList.DoneSuccess);
						else
							Fit.Controls.Dialog.Alert(lang.OrderList.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
					}
				}
				else // More requests to be made
				{
					var nextModel = processing[0];
					Fit.Array.Remove(processing, nextModel);
					execute(nextModel);
				}

				// SendInvoice(..), CapturePayment(..), and CancelPayment(..)
				// functions on Model does not update data. Calling Retrieve(..)
				// to fetch newly assigned Invoice ID.

				model.Retrieve(function(req, m)
				{
					model._presenter.invoiceElement.innerHTML = model.InvoiceId();
				});
			},
			function(req, m) // Error handler
			{
				Fit.Array.Add(failed, model.Id());

				processed++;
				statusDialog.Progress(Fit.Math.Round((processed/scheduled) * 100));

				if (processing.length === 0) // No more requests to be made
				{
					if (processed === scheduled) // All responses have been received
					{
						statusDialog.Dispose();
						Fit.Controls.Dialog.Alert(lang.OrderList.DoneFailure + ":<br><br>" + failed.join(((failed.length <= 10) ? "<br>" : ", ")));
					}
				}
				else // More requests to be made
				{
					var nextModel = processing[0];
					Fit.Array.Remove(processing, nextModel);
					execute(nextModel);
				}
			});
		}

		var maxConcurrentRequests = 3;
		var startProcessing = [];

		// Get up to a maximum of X number of models (specified in maxConcurrentRequests)
		for (var i = 0 ; i < maxConcurrentRequests ; i++)
		{
			if (i <= processing.length - 1)
			{
				Fit.Array.Add(startProcessing, processing[i]);
			}
		}

		// Remove models from processing array
		Fit.Array.ForEach(startProcessing, function(model)
		{
			Fit.Array.Remove(processing, model);
		});

		// Invoke requests
		Fit.Array.ForEach(startProcessing, function(model)
		{
			execute(model);
		});
	}

	init();
}
