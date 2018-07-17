if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.OrderList = function()
{
	Fit.Core.Extend(this, JSShop.Presenters.Base).Apply();

	var dom = null;
	var view = null;
	var models = [];
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
			loadData();
			populateToolbarAndColumnsToView();
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
		txtSearch.Width(120);
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
		txtFrom.Width(120);
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
		txtTo.Width(120);
		txtTo.Value(Fit.Date.Format(new Date(), lang.Locale.DateFormat));
		txtTo.GetDomElement().title = lang.OrderList.DisplayToDate;

		cmdUpdate = new Fit.Controls.Button("JSShopUpdateButton");
		cmdUpdate.Icon("fa-refresh");
		cmdUpdate.Type(Fit.Controls.Button.Type.Primary);
		cmdUpdate.OnClick(function(sender)
		{
			cmdUpdate.Enabled(false);
			loadData(function() { cmdUpdate.Enabled(true); });
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
			cmdPdf.Enabled((Fit.Browser.GetInfo().Name !== "MSIE" || Fit.Browser.GetInfo().Name >= 10)); // jsPDF requires IE10+

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
			dia.Open();
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

		if (document.querySelector("link[href*='/Views/OrderList.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/OrderList.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		var tpl = new Fit.Template(true);
		tpl.LoadUrl(JSShop.GetPath() + "/Views/OrderList.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"), function(sender, html)
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
		});
	}

	function loadOrderModels(search, fromTimeStamp, toTimeStamp, onComplete)
	{
		Fit.Validation.ExpectString(search);
		Fit.Validation.ExpectInteger(fromTimeStamp);
		Fit.Validation.ExpectInteger(toTimeStamp);
		Fit.Validation.ExpectFunction(onComplete);

		JSShop.Models.Order.RetrieveAll(search, fromTimeStamp, toTimeStamp, function(req, modelInstances)
		{
			models = modelInstances;
			onComplete();
		} );
	}

	function populateToolbarAndColumnsToView()
	{
		view.Content.ToolbarFromDateField = txtFrom.GetDomElement();
		view.Content.ToolbarToDateField = txtTo.GetDomElement();
		view.Content.ToolbarSearchField = txtSearch.GetDomElement();
		view.Content.ToolbarUpdateButton = cmdUpdate.GetDomElement();
		view.Content.ToolbarExportButton = cmdExport.GetDomElement();
		view.Content.ToolbarInvoiceButton = cmdInvoice.GetDomElement();
		view.Content.ToolbarCaptureButton = cmdCapture.GetDomElement();
		view.Content.ToolbarRejectButton = cmdReject.GetDomElement();
		view.Content.ToolbarConfigButton = cmdConfig.GetDomElement();

		view.Content.HeaderSelectAll = chkSelectAll.GetDomElement();
		view.Content.HeaderHeaderOrderId = lang.OrderList.OrderId;
		view.Content.HeaderTime = lang.OrderList.Time;
		view.Content.HeaderCustomer = lang.OrderList.Customer;
		view.Content.HeaderAmount = lang.OrderList.Amount;
		view.Content.HeaderPaymentMethod = lang.OrderList.PaymentMethod;
		view.Content.HeaderState = lang.OrderList.State;
		view.Content.HeaderInvoiceId = lang.OrderList.InvoiceId;
	}

	function populateOrderDataToView()
	{
		view.Content.Orders.Clear();
		chkSelectAll.Checked(false);

		Fit.Array.ForEach(models, function(model)
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
				displayCustomerDetails(model);
			}
			model._presenter.lnkCustomerDetails.innerHTML = model.FirstName() + " " + model.LastName();

			model._presenter.lnkAmountWithDetails = document.createElement("a");
			model._presenter.lnkAmountWithDetails.href = "javascript:";
			model._presenter.lnkAmountWithDetails.onclick = function(e)
			{
				displayOrderEntries(model);
			}
			model._presenter.lnkAmountWithDetails.innerHTML = Fit.Math.Format(model.Price() + model.Vat(), 2, lang.Locale.DecimalSeparator);

			model._presenter.stateElm = document.createElement("span");
			model._presenter.stateElm.innerHTML = getStateTitle(model.State());

			var item = view.Content.Orders.AddItem();

			item.Select = model._presenter.checkBox.GetDomElement();
			item.OrderId = model.Id();
			item.Time = Fit.Date.Format(new Date(model.Time()), lang.Locale.DateFormat + " " + lang.Locale.TimeFormat);
			item.Customer = model._presenter.lnkCustomerDetails;
			item.Currency = model.Currency();
			item.Amount = model._presenter.lnkAmountWithDetails;
			item.PaymentMethod = model.PaymentMethod();
			item.State = model._presenter.stateElm;
			item.InvoiceId = model.InvoiceId();
		});

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

				model._presenter.StateElement.innerHTML = getStateTitle(model.State());

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

	function displayCustomerDetails(model)
	{
		Fit.Validation.ExpectInstance(model, JSShop.Models.Order);

		var dia = new Fit.Controls.Dialog();
		dia.Modal(true);
		dia.Content(lang.OrderList.Loading);
		dia.Open();

		var cmdOk = new Fit.Controls.Button("JSShopOrderDetailsOkButton");
		cmdOk.Title(lang.Common.Ok);
		cmdOk.Type(Fit.Controls.Button.Type.Success);
		cmdOk.Enabled(false);
		cmdOk.OnClick(function(sender)
		{
			dia.Dispose();
		});
		dia.AddButton(cmdOk);
		cmdOk.Focused(true);

		if (document.querySelector("link[href*='/Views/DialogCustomerDetails.css']") === null)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/DialogCustomerDetails.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/DialogCustomerDetails.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));
		req.OnSuccess(function(sender)
		{
			var html = req.GetResponseText();

			html = html.replace(/{\[CustomerDetailsHeadline\]}/, lang.OrderList.CustomerDetails);
			html = html.replace(/{\[AlternativeAddressHeadline\]}/, lang.OrderList.AlternativeAddress);
			html = html.replace(/{\[CustomerMessageHeadline\]}/, lang.OrderList.Message);

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
		dia.Content(lang.OrderList.Loading);
		dia.Open();
		dia.GetDomElement().style.maxWidth = "95%";

		var cmdOk = new Fit.Controls.Button("JSShopOrderEntriesOkButton");
		cmdOk.Title(lang.Common.Ok);
		cmdOk.Type(Fit.Controls.Button.Type.Success);
		cmdOk.Enabled(false);
		cmdOk.OnClick(function(sender)
		{
			dia.Dispose();
		});
		dia.AddButton(cmdOk);
		cmdOk.Focused(true);

		if (document.querySelector("link[href*='/Views/DialogOrderEntries.css']") === null)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/DialogOrderEntries.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/DialogOrderEntries.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));
		req.OnSuccess(function(sender)
		{
			var html = req.GetResponseText();

			// Headers

			html = html.replace(/{\[HeaderProduct\]}/g, lang.OrderList.Product);
			html = html.replace(/{\[HeaderUnitPrice\]}/g, lang.OrderList.UnitPrice);
			html = html.replace(/{\[HeaderUnits\]}/g, lang.OrderList.Units);
			html = html.replace(/{\[HeaderPrice\]}/g, lang.OrderList.Price);
			html = html.replace(/{\[HeaderTotalVat\]}/g, lang.OrderList.TotalVat);
			html = html.replace(/{\[HeaderTotalPrice\]}/g, lang.OrderList.TotalPrice);

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
					curItemHtml = curItemHtml.replace(/{\[UnitPrice\]}/g, Fit.Math.Format(pricing.UnitPriceInclVat, 2, lang.Locale.DecimalSeparator));
					curItemHtml = curItemHtml.replace(/{\[DiscountMessage\]}/g, entry.DiscountMessage());
					curItemHtml = curItemHtml.replace(/{\[Discount\]}/g, ((pricing.DiscountInclVat > 0 || pricing.DiscountInclVat < 0) ? Fit.Math.Format(pricing.DiscountInclVat * -1, 2, lang.Locale.DecimalSeparator) : ""));
					curItemHtml = curItemHtml.replace(/{\[Units\]}/g, entry.Units());
					curItemHtml = curItemHtml.replace(/{\[Currency\]}/g, entry.Currency());
					curItemHtml = curItemHtml.replace(/{\[Price\]}/g, Fit.Math.Format(pricing.TotalInclVat, 2, lang.Locale.DecimalSeparator));

					allItemsHtml += curItemHtml;
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
						curItemHtml = curItemHtml.replace(/{\[Price\]}/g, Fit.Math.Format(model["CostCorrection" + i]() + model["CostCorrectionVat" + i](), 2, lang.Locale.DecimalSeparator));

						allItemsHtml += curItemHtml;
					}
				}

				html = html.replace(res[0], allItemsHtml);

				html = html.replace(/{\[Currency\]}/g, model.Currency());
				html = html.replace(/{\[TotalVat\]}/g, Fit.Math.Format(model.Vat(), 2, lang.Locale.DecimalSeparator));
				html = html.replace(/{\[TotalPrice\]}/g, Fit.Math.Format(model.Price() + model.Vat(), 2, lang.Locale.DecimalSeparator));

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

	function exportData()
	{
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
			});

			var content = "\"" + data.join("\",\"") + "\"";

			Fit.Array.ForEach(items, function(item)
			{
				content += "\n" + item.join(",");
			});

			if (Fit.Browser.GetInfo().Name === "MSIE") // Internet Explorer
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
					model._presenter.InvoiceElement.innerHTML = model.InvoiceId();
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
