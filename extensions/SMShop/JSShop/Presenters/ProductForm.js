if (!window.JSShop)
	Fit.Validation.ThrowError("JSShop.js must be loaded");

JSShop.Presenters.ProductForm = function()
{
	Fit.Core.Extend(this, JSShop.Presenters.Base).Apply();

	var me = this;

	// MVC
	var view = null;			// View (ProductForm.html)
	var models = {};			// All models (products) loaded - associative object array
	var currentModel = null;	// Model (product) currently being edited

	// Edit controls
	var lstProducts = null;
	var lstCategories = null;
	var txtId = null;
	var txtTitle = null;
	var txtDescription = null;
	var picImages = null;
	var txtPrice = null;
	var lstCurrencies = null;
	var txtVat = null;
	var txtWeight = null;
	var lstWeightUnits = null;
	var txtDeliveryTime = null;
	var txtDiscountExpr = null;
	var txtDiscountMsg = null;

	// Buttons
	var cmdSave = null;
	var cmdClear = null;
	var cmdDelete = null;

	// Misc.
	var lang = JSShop.Language.Translations.ProductForm;
	var imagesRemoved = []; // Existing product images located on server that the user removed from prevew section
	var imagesIgnored = []; // New images selected using file picker (not uploaded yet), that user removed from preview section

	function init()
	{
		view = document.createElement("div");

		// Load view

		if (document.querySelector("link[href*='/Views/ProductForm.css']") === null) // Might have been loaded by CMS to prevent flickering (FOUC - flash of unstyled content)
			Fit.Loader.LoadStyleSheet(JSShop.GetPath() + "/Views/ProductForm.css?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));

		var req = new Fit.Http.Request(JSShop.GetPath() + "/Views/ProductForm.html?CacheKey=" + (JSShop.Settings.CacheKey ? JSShop.Settings.CacheKey : "0"));
		req.OnSuccess(function(sender)
		{
			var htmlView = req.GetResponseText();
			view.innerHTML = htmlView;

			// Add controls and buttons to view

			Fit.Dom.Add(view.querySelector("#JSShop-Products-Label"), document.createTextNode(lang.EditProduct));
			lstProducts.Render(view.querySelector("#JSShop-Products-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-Category-Label"), document.createTextNode(lang.Category));
			lstCategories.Render(view.querySelector("#JSShop-Category-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-ProductId-Label"), document.createTextNode(lang.ProductId));
			txtId.Render(view.querySelector("#JSShop-ProductId-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-Title-Label"), document.createTextNode(lang.Title));
			txtTitle.Render(view.querySelector("#JSShop-Title-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-Description-Label"), document.createTextNode(lang.Description));
			txtDescription.Render(view.querySelector("#JSShop-Description-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-Images-Label"), document.createTextNode(lang.Images));
			picImages.Render(view.querySelector("#JSShop-Images-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-Price-Label"), document.createTextNode(lang.Price));
			txtPrice.Render(view.querySelector("#JSShop-Price-Control"));

			lstCurrencies.Render(view.querySelector("#JSShop-Currency-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-Vat-Label"), document.createTextNode(lang.Vat));
			txtVat.Render(view.querySelector("#JSShop-Vat-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-Weight-Label"), document.createTextNode(lang.Weight));
			txtWeight.Render(view.querySelector("#JSShop-Weight-Control"));

			lstWeightUnits.Render(view.querySelector("#JSShop-WeightUnit-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-DeliveryTime-Label"), document.createTextNode(lang.DeliveryTime));
			txtDeliveryTime.Render(view.querySelector("#JSShop-DeliveryTime-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-DiscountExpression-Label"), document.createTextNode(lang.DiscountExpression));
			txtDiscountExpr.Render(view.querySelector("#JSShop-DiscountExpression-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-DiscountMessage-Label"), document.createTextNode(lang.DiscountMessage));
			txtDiscountMsg.Render(view.querySelector("#JSShop-DiscountMessage-Control"));

			Fit.Dom.Add(view.querySelector("#JSShop-Buttons"), cmdSave.GetDomElement());
			Fit.Dom.Add(view.querySelector("#JSShop-Buttons"), cmdClear.GetDomElement());
			Fit.Dom.Add(view.querySelector("#JSShop-Buttons"), cmdDelete.GetDomElement());
		});
		req.Start();

		// Create controls and buttons

		lstProducts = new Fit.Controls.DropDown("JSShopProducts");
		lstProducts.TreeView = new Fit.Controls.TreeView("JSShopProductsTreeView"); // .TreeView is a custom property!
		lstProducts.TreeView.Lines(true);
		lstProducts.TreeView.Width(100, "%"); // Consume width of picker container
		lstProducts.ListView = new Fit.Controls.ListView(); // .ListView is a custom property!
		lstProducts.Width(350, "px");
		lstProducts.DropDownMaxWidth(180, "%");
		lstProducts.InputEnabled(true);
		lstProducts.OnInputChanged(function(sender)
		{
			// Search support

			if (lstProducts.GetInputValue() === "")
			{
				lstProducts.SetPicker(lstProducts.TreeView);
			}
			else
			{
				lstProducts.OpenDropDown();
				lstProducts.ListView.RemoveItems();

				var nodes = lstProducts.TreeView.GetAllNodes();
				var search = lstProducts.GetInputValue().toLowerCase();

				Fit.Array.ForEach(nodes, function(node)
				{
					if (node.GetLevel() === 0) // Level 0 = Categories, Level 1 = Products
						return;

					if (node.Title().toLowerCase().indexOf(search) > -1 || node.Value().toLowerCase() === search)
						lstProducts.ListView.AddItem(node.Title() + " (" + node.GetParent().Title() + ")", node.Value());
				});

				lstProducts.SetPicker(lstProducts.ListView);
			}
		});
		lstProducts.OnChange(function(sender)
		{
			// Load selected product into form

			lstProducts.SetPicker(lstProducts.TreeView); // Make sure TreeView reflects new selection in case it was made using ListView control

			if (lstProducts.TreeView.Selected().length === 0)
				bind(null);
			else
				bind(models[lstProducts.TreeView.Selected()[0].Value()]);
		});
		lstProducts.SetPicker(lstProducts.TreeView);

		lstCategories = new Fit.Controls.DropDown("JSShopProductCategory");
		lstCategories.SetPicker(new Fit.Controls.ListView());
		lstCategories.Width(350, "px");
		lstCategories.DropDownMaxWidth(150, "%");
		lstCategories.InputEnabled(true);
		lstCategories.SetValidationCallback(function(val) { return (val.split("=")[0].length <= 50); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		lstCategories.Required(true);
		lstCategories.Scope("JSShopProductForm");
		lstCategories.OnBlur(function(sender)
		{
			// Remove text entered if a selection is made.
			// If no selection is made and text is entered, turn the text into a new selection.

			if (lstCategories.GetSelections().length > 0)
				lstCategories.SetInputValue("");
			else if (lstCategories.GetInputValue() !== "")
				lstCategories.AddSelection(lstCategories.GetInputValue(), lstCategories.GetInputValue());
		});

		me.OnRendered(function(sender) // Notice: Event obviously won't fire if presenter is added to page like this: Fit.Dom.Add(document.body, productForm.GetDomElement())
		{
			populatePickers(); // Populate lstProducts and lstCategories when rendered
		});

		//populatePickers(); // Populate lstProducts and lstCategories - called at the end since it throws error if RetrieveAll WebService URL has not been configured

		txtId = new Fit.Controls.Input("JSShopProductId");
		txtId.Width(350, "px");
		txtId.Required(true);
		txtId.SetValidationCallback(function(val) { return (val.length <= 30); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		txtId.Scope("JSShopProductForm");

		txtTitle = new Fit.Controls.Input("JSShopProductTitle");
		txtTitle.Width(350, "px");
		txtTitle.Required(true);
		txtTitle.SetValidationCallback(function(val) { return (val.length <= 250); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		txtTitle.Scope("JSShopProductForm");

		txtDescription = new Fit.Controls.Input("JSShopProductDescription");
		txtDescription.Width(350, "px");
		txtDescription.Height(80, "px");
		txtDescription.MultiLine(true, 300);
		txtDescription.Maximizable(true, 250);
		txtDescription.SetValidationCallback(function(val) { return (val.length <= 1000); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		txtDescription.Scope("JSShopProductForm");

		picImages = new Fit.Controls.FilePicker("JSShopProductImages");
		picImages.Url(JSShop.WebService.Files.Upload);
		picImages.Enabled(JSShop.WebService.Files.Upload ? true : false);
		picImages.MultiSelectionMode(true);
		picImages.Title(lang.SelectFiles);
		picImages.OnChange(function(sender)
		{
			if (picImages.IsLegacyModeEnabled() === true)
				return; // No previews or progress bars for legacy browsers (e.g. IE9 and below)

			imagesIgnored = []; // Files selected using picker, but removed from preview section prior to upload

			var formImages = view.querySelector(".JSShopProductFormImages");

			// Remove previews for previously selected files (in case selection is changed)

			Fit.Array.ForEach(formImages.querySelectorAll("div.JSShopPreviewContainer"), function(pc)
			{
				Fit.Dom.Remove(pc);
			});

			// Add previews for newly selected files

			Fit.Array.ForEach(picImages.GetFiles(), function(file)
			{
				if (Fit.Array.Contains(["image/png", "image/jpg", "image/jpeg", "image/gif"], file.Type) === false)
					return; // Skip if file is not an image

				var previewContainer = document.createElement("div");
				previewContainer.className = "JSShopPreviewContainer";

				// Add progress bar

				var p = new Fit.Controls.ProgressBar("JSShopFileProgress" + file.Id);
				p.Title(file.Filename);
				p.Render(previewContainer);

				// Add preview

				var preview = file.GetImagePreview();

				if (preview !== null) // Null if control is in Legacy Mode or if file is not an image
				{
					previewContainer.appendChild(createPreviewFrame(preview, function() // Callback invoked when deleting preview
					{
						Fit.Array.Add(imagesIgnored, file.Filename);
						Fit.Dom.Remove(previewContainer);
					}));
				}

				// Add progress bar and preview to UI

				formImages.appendChild(previewContainer);
			});
		});
		picImages.OnProgress(function(sender, file)
		{
			if (picImages.IsLegacyModeEnabled() === false)
				Fit.Controls.Find("JSShopFileProgress" + file.Id).Progress(file.Progress);
		});
		picImages.OnCompleted(function(sender)
		{
			// Continue save operation from cmdSave.OnClick(..). Images are uploaded first (done),
			// then images removed by the user is removed from server (removeImages(..) - done below),
			// and finally product information is updated on the server (saveData() - passed as callback below).
			removeImages(saveData);
		});

		txtPrice = new Fit.Controls.Input("JSShopProductPrice");
		txtPrice.Width(185, "px");
		txtPrice.Required(true);
		txtPrice.SetValidationCallback(function(val) { return (val.length <= 10); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		txtPrice.SetValidationExpression(new RegExp("^([0-9]+(\\" + JSShop.Language.Translations.Locale.DecimalSeparator + "[0-9]+)?)?$"), JSShop.Language.Translations.Common.InvalidValue); // /^([0-9]+(\.[0-9]+)?)?$/
		txtPrice.Scope("JSShopProductForm");

		if (JSShop.Cookies.Get("PreviousCurrency") === null)
		{
			JSShop.Cookies.Set("PreviousCurrency", JSShop.Language.Translations.Locale.Currency, 365 * 24 * 60 * 60); // Expires in 1 year
		}

		var currencies = null; // Data from Currencies.json
		var prevCur = JSShop.Cookies.Get("PreviousCurrency");

		lstCurrencies = new Fit.Controls.DropDown("JSShopProductCurrency");
		lstCurrencies.Width(125, "px");
		lstCurrencies.SetPicker(new Fit.Controls.ListView());
		lstCurrencies.Value(prevCur);
		lstCurrencies.Required(true);
		lstCurrencies.InputEnabled(true);
		lstCurrencies.OnOpen(function(sender)
		{
			if (currencies === null)
			{
				currencies = []; // Prevent multiple requests in case drop down is opened/closed multiple times while loading Currencies.json

				var jsonReq = new Fit.Http.JsonRequest(JSShop.GetUrl() + "/Currencies.json"); // https://github.com/mhs/world-currencies/blob/master/currencies.json
				jsonReq.OnSuccess(function(req)
				{
					currencies = jsonReq.GetResponseJson();
					var input = lstCurrencies.GetInputValue().toLowerCase(); // User could have entered a search value before data was done loading

					Fit.Array.ForEach(currencies, function(currency)
					{
						if (input === "" || currency.cc.toLowerCase().indexOf(input) > -1 || currency.symbol.toLowerCase().indexOf(input) > -1 || currency.name.toLowerCase().indexOf(input) > -1)
						{
							lstCurrencies.GetPicker().AddItem(currency.cc + " (" + currency.symbol + ")", currency.cc);
						}
					});
				});
				jsonReq.Start();
			}
		});
		lstCurrencies.OnInputChanged(function(sender)
		{
			if (currencies === null) // Force data to load
			{
				lstCurrencies.OpenDropDown();
			}
			else
			{
				var input = lstCurrencies.GetInputValue().toLowerCase();

				lstCurrencies.GetPicker().RemoveItems();

				Fit.Array.ForEach(currencies, function(currency)
				{
					if (input === "" || currency.cc.toLowerCase().indexOf(input) > -1 || currency.symbol.toLowerCase().indexOf(input) > -1 || currency.name.toLowerCase().indexOf(input) > -1)
					{
						lstCurrencies.GetPicker().AddItem(currency.cc + " (" + currency.symbol + ")", currency.cc);
					}
				});

				if (lstCurrencies.GetInputValue() !== "") // Do not re-open when a selection is made, which clears the input value and fires OnInputChanged
					lstCurrencies.OpenDropDown();
			}
		});
		lstCurrencies.OnChange(function(sender)
		{
			JSShop.Cookies.Set("PreviousCurrency", lstCurrencies.Value(), 365 * 24 * 60 * 60); // Expires in 1 year
		});

		txtVat = new Fit.Controls.Input("JSShopProductVat");
		txtVat.Width(185, "px");
		txtVat.SetValidationCallback(function(val) { return (val.length <= 10); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		txtVat.SetValidationExpression(new RegExp("^([0-9]+(\\" + JSShop.Language.Translations.Locale.DecimalSeparator + "[0-9]+)?)?$"), JSShop.Language.Translations.Common.InvalidValue); // /^([0-9]+(\.[0-9]+)?)?$/
		txtVat.Scope("JSShopProductForm");
		txtVat.Value(((JSShop.Cookies.Get("PreviousVat") !== null) ? JSShop.Cookies.Get("PreviousVat") : ""));
		txtVat.OnChange(function(sender)
		{
			JSShop.Cookies.Set("PreviousVat", txtVat.Value(), 365 * 24 * 60 * 60); // Expires in 1 year
		});

		txtWeight = new Fit.Controls.Input("JSShopProductWeight");
		txtWeight.Width(185, "px");
		txtWeight.SetValidationCallback(function(val) { return (val.length <= 10); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		txtWeight.SetValidationExpression(new RegExp("^([0-9]+(\\" + JSShop.Language.Translations.Locale.DecimalSeparator + "[0-9]+)?)?$"), JSShop.Language.Translations.Common.InvalidValue); // /^([0-9]+(\.[0-9]+)?)?$/
		txtWeight.Scope("JSShopProductForm");

		if (JSShop.Cookies.Get("PreviousWeightUnit") === null)
		{
			JSShop.Cookies.Set("PreviousWeightUnit", getWeightUnitControlValue(JSShop.Language.Translations.Locale.WeightUnit), 365 * 24 * 60 * 60); // Expires in 1 year
		}

		JSShop.Cookies.Get("PreviousWeightUnit")

		lstWeightUnits = new Fit.Controls.DropDown("JSShopProductWeightUnit");
		lstWeightUnits.Width(125, "px");
		lstWeightUnits.SetPicker(new Fit.Controls.ListView());
		lstWeightUnits.GetPicker().AddItem(lang.Kilos, "kg");
		lstWeightUnits.GetPicker().AddItem(lang.Pounds, "lbs");
		lstWeightUnits.Value(JSShop.Cookies.Get("PreviousWeightUnit"));
		lstWeightUnits.Required(true);
		lstWeightUnits.OnChange(function(sender)
		{
			JSShop.Cookies.Set("PreviousWeightUnit", lstWeightUnits.Value(), 365 * 24 * 60 * 60); // Expires in 1 year
		});

		txtDeliveryTime = new Fit.Controls.Input("JSShopProductDeliveryTime");
		txtDeliveryTime.Width(350, "px");
		txtDeliveryTime.Value(((JSShop.Cookies.Get("PreviousDeliveryTime") !== null) ? JSShop.Cookies.Get("PreviousDeliveryTime") : ""));
		txtDeliveryTime.SetValidationCallback(function(val) { return (val.length <= 50); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		txtDeliveryTime.Scope("JSShopProductForm");
		txtDeliveryTime.OnChange(function(sender)
		{
			JSShop.Cookies.Set("PreviousDeliveryTime", txtDeliveryTime.Value(), 365 * 24 * 60 * 60); // Expires in 1 year
		});

		// Test:  (items >= 10 ? items*price*0.20 : (items >= 3 ? price : 0))
		// Test:  (items >= 26 ? items*199 : (items >=12 ? items*170 : (items >= 7 ? items*130 : (items >= 4 ? items*100 : -150))))
		txtDiscountExpr = new Fit.Controls.Input("JSShopProductDiscountExpression");
		txtDiscountExpr.Width(350, "px");
		txtDiscountExpr.SetValidationCallback(function(val)
		{
			var p = new JSShop.Models.Product("JSShopTemp" + Fit.Data.CreateGuid());
			p.Price(100);
			p.Currency("USD");
			p.Vat(25.0);
			p.DiscountExpression(val);

			try
			{
				// Notice: Unlikely that all possible conditions in Discount Expression is covered with the test below.
				// However, JSShop.Models.Product.CalculateDiscount(items) will catch problems with returned discount value runtime.
				p.CalculateDiscount(-100);
				p.CalculateDiscount(0);
				p.CalculateDiscount(100);
				return true;
			}
			catch (err)
			{
				return false;
			}
		}, JSShop.Language.Translations.Common.InvalidValue);
		txtDiscountExpr.SetValidationExpression(/^(.|\n){0,250}$/, JSShop.Language.Translations.Common.MaxLengthExceeded); // RegEx MaxLength: https://regex101.com/r/bY3xS9/1
		txtDiscountExpr.Scope("JSShopProductForm");

		txtDiscountMsg = new Fit.Controls.Input("JSShopProductDiscountMessage");
		txtDiscountMsg.Width(350, "px");
		txtDiscountMsg.SetValidationCallback(function(val)
		{
			var p = new JSShop.Models.Product("JSShopTemp" + Fit.Data.CreateGuid());
			p.Price(100);
			p.Currency("USD");
			p.Vat(25.0);
			p.DiscountMessage(val);

			try
			{
				// Notice: Unlikely that all possible conditions in Discount Expression is covered with the test below.
				// However, JSShop.Models.Product.CalculateDiscount(items) will catch problems with returned discount value runtime.
				p.CalculateDiscountMessage(-100);
				p.CalculateDiscountMessage(0);
				p.CalculateDiscountMessage(100);
				return true;
			}
			catch (err)
			{
				return false;
			}
		}, JSShop.Language.Translations.Common.InvalidValue);
		txtDiscountMsg.SetValidationExpression(/^(.|\n){0,250}$/, JSShop.Language.Translations.Common.MaxLengthExceeded); // RegEx MaxLength: https://regex101.com/r/bY3xS9/1
		//txtDiscountMsg.SetValidationCallback(function(val) { return (val.length <= 250); }, JSShop.Language.Translations.Common.MaxLengthExceeded);
		txtDiscountMsg.Scope("JSShopProductForm");

		cmdSave = new Fit.Controls.Button("JSShopSaveButton");
		cmdSave.Title(lang.Save);
		cmdSave.Icon("floppy-o");
		cmdSave.Type(Fit.Controls.Button.Type.Success);
		cmdSave.OnClick(function(sender)
		{
			// Save operations does the following:
			//  1) Uploads new images
			//  2) Deletes images from server if removed by user
			//  3) Saves product information

			if (Fit.Controls.ValidateAll("JSShopProductForm") === false)
			{
				Fit.Controls.Dialog.Alert(JSShop.Language.Translations.Common.InvalidEntries);
				return;
			}

			if (picImages.GetFiles().length > 0)
			{
				var invalidFiles = [];

				Fit.Array.ForEach(picImages.GetFiles(), function(file)
				{
					if (picImages.IsLegacyModeEnabled() === false)
					{
						if (Fit.Array.Contains(["image/png", "image/jpg", "image/jpeg", "image/gif"], file.Type) === false)
							Fit.Array.Add(invalidFiles, file.Filename);
					}
					else
					{
						if (Fit.Array.Contains([".png", ".jpg", ".jpeg", ".gif"], file.Filename.substring(file.Filename.lastIndexOf("."))) === false)
							Fit.Array.Add(invalidFiles, file.Filename);
					}
				});

				var skipFiles = Fit.Array.Merge(invalidFiles, imagesIgnored);

				if (picImages.GetFiles().length > skipFiles.length)
					picImages.Upload(skipFiles); // Upload control's OnCompleted event handler resumes save operation when upload is complete
				else
					removeImages(saveData); // Delete any images from server, if removed by user from preview section - then continue with saveData
			}
			else
			{
				removeImages(saveData); // Delete any images from server, if removed by user from preview section - then continue with saveData
			}
		});

		cmdClear = new Fit.Controls.Button("JSShopClearButton");
		cmdClear.Title(lang.Clear);
		cmdClear.Icon("refresh");
		cmdClear.Type(Fit.Controls.Button.Type.Warning);
		cmdClear.OnClick(function(sender)
		{
			cmdClear.Icon("fa-check");
			setTimeout(function() { cmdClear.Icon("refresh"); }, 1500);

			lstProducts.SetInputValue("");
			lstProducts.Clear();
			bind(null);
		});

		cmdDelete = new Fit.Controls.Button("JSShopDeleteButton");
		cmdDelete.Title(lang.Delete);
		cmdDelete.Icon("minus-circle");
		cmdDelete.Type(Fit.Controls.Button.Type.Danger);
		cmdDelete.GetDomElement().style.display = "none";
		cmdDelete.OnClick(function(sender)
		{
			Fit.Controls.Dialog.Confirm(lang.DeleteWarning, function(res)
			{
				if (res === false)
					return;

				imagesRemoved = ((currentModel.Images() !== "") ? currentModel.Images().split(";") : []);
				removeImages(function()
				{
					currentModel.Delete(function()
					{
						cmdDelete.Icon("fa-check");
						setTimeout(function() { cmdDelete.Icon("minus-circle"); }, 1500);

						bind(null);
						populatePickers(); // Update pickers (products drop down and categories drop down)
					});
				});
			});
		});
	}

	this.GetDomElement = function()
	{
		return view;
	}

	// Private

	function populatePickers()
	{
		// Clear Products and Categories drop downs

		lstProducts.SetInputValue(""); // Fires OnInputChanged which changes picker to TreeView
		lstProducts.Value("");
		lstProducts.TreeView.RemoveAllChildren(true);

		lstCategories.GetPicker().RemoveItems();

		// Load models and populate drop downs

		models = {};

		JSShop.Models.Product.RetrieveAll("", function(request, allModels)
		{
			Fit.Array.ForEach(allModels, function(model)
			{
				// Category value is prefixed with "CAT#" to avoid problems if user creates both
				// a category and a product with an Id (model.Id()) identical to a category name.
				// Values added to TreeView should be unique.
				var categoryValue = "CAT#" + model.Category();

				if (lstProducts.TreeView.GetChild(categoryValue) === null) // Avoid adding category multiple times
				{
					lstProducts.TreeView.AddChild(new Fit.Controls.TreeView.Node(model.Category(), categoryValue));
					lstCategories.GetPicker().AddItem(model.Category(), model.Category());
				}

				var node = new Fit.Controls.TreeView.Node(model.Title(), model.Id());
				node.Selectable(true);

				lstProducts.TreeView.GetChild(categoryValue).AddChild(node);

				models[model.Id()] = model;
			});

			if (Fit.Array.Count(models) === 0)
			{
				lstProducts.SetInputValue(lang.NoProducts); // Causes drop down to open (see lstProducts.OnChange)
				lstProducts.CloseDropDown(); // Close drop down again
			}
		});
	}

	function bind(model)
	{
		Fit.Validation.ExpectInstance(model, JSShop.Models.Product, true);

		if (Fit.Validation.IsSet(model) === true)
		{
			// Set drop downs

			lstCategories.ClearInput();
			lstCategories.AddSelection(model.Category(), model.Category());

			// Insert data

			txtId.Value(model.Id());
			txtTitle.Value(model.Title());
			txtDescription.Value(model.Description());
			picImages.Clear(); // Clear file picker
			txtPrice.Value(model.Price().toString().replace(".", JSShop.Language.Translations.Locale.DecimalSeparator));
			lstCurrencies.Value(model.Currency());
			txtVat.Value(((model.Vat() !== 0) ? model.Vat().toString().replace(".", JSShop.Language.Translations.Locale.DecimalSeparator) : ""));
			txtWeight.Value(((model.Weight() !== 0) ? model.Weight().toString().replace(".", JSShop.Language.Translations.Locale.DecimalSeparator) : ""));
			lstWeightUnits.Value(getWeightUnitControlValue(model.WeightUnit()));
			txtDeliveryTime.Value(model.DeliveryTime());
			txtDiscountExpr.Value(model.DiscountExpression());
			txtDiscountMsg.Value(model.DiscountMessage());

			// Add product images

			view.querySelector(".JSShopProductFormImages").innerHTML = "";

			if (model.Images() !== "")
			{
				var formImages = view.querySelector(".JSShopProductFormImages");

				Fit.Array.ForEach(model.Images().split(";"), function(imgSrc)
				{
					var img = new Image();

					formImages.appendChild(createPreviewFrame(img, function() // Callback invoked when deleting preview
					{
						Fit.Array.Add(imagesRemoved, imgSrc);
						Fit.Dom.Remove(img.parentElement);
					}));

					img.src = imgSrc; // Must be set after calling createPreviewFrame(..) which registers an OnLoad handler on the image
				});
			}

			// Misc.

			cmdDelete.GetDomElement().style.display = "";
			currentModel = model;
		}
		else
		{
			lstCategories.ClearInput();
			lstCategories.ClearSelections();

			txtId.Value("");
			txtTitle.Value("");
			txtDescription.Value("");
			picImages.Clear(); // Clear file picker
			txtPrice.Value("");
			lstCurrencies.Value(((JSShop.Cookies.Get("PreviousCurrency") !== null) ? JSShop.Cookies.Get("PreviousCurrency") : ""));
			txtVat.Value(((JSShop.Cookies.Get("PreviousVat") !== null) ? JSShop.Cookies.Get("PreviousVat") : ""));
			txtWeight.Value("");
			lstWeightUnits.Value(((JSShop.Cookies.Get("PreviousWeightUnit") !== null) ? JSShop.Cookies.Get("PreviousWeightUnit") : ""));
			txtDeliveryTime.Value(((JSShop.Cookies.Get("PreviousDeliveryTime") !== null) ? JSShop.Cookies.Get("PreviousDeliveryTime") : ""));
			txtDiscountExpr.Value("");
			txtDiscountMsg.Value("");

			view.querySelector(".JSShopProductFormImages").innerHTML = "";
			cmdDelete.GetDomElement().style.display = "none";
			currentModel = null;
		}
	}

	function saveData()
	{
		var updateExisting = (currentModel !== null && currentModel.Id() === txtId.Value());
		var model = ((updateExisting === true) ? currentModel : new JSShop.Models.Product(txtId.Value())); // Notice: New model created if product does not already exist, or if Product ID is changed (in which case currentMode.Update(..) will not work since the ID is used to identify what record to update)

		if (currentModel !== null && model !== currentModel) // Product ID changed - a new product entry will be created
			model.Images(currentModel.Images());

		// Add newly uploaded images (if any) to collection of existing images

		var imagesStr = model.Images();
		var newImages = ((imagesStr !== "") ? imagesStr.split(";") : []);

		Fit.Array.ForEach(picImages.GetFiles(), function(file)
		{
			if (file.Processed === false)
				return; // Skip file, was not uploaded (not an image file, or removed by user prior to upload)

			if (file.ServerResponse === null || file.ServerResponse === "")
				throw "File upload did not produce a valid file reference";

			Fit.Array.Add(newImages, file.ServerResponse);
		});

		Fit.Array.ForEach(imagesRemoved, function(imgRemoved)
		{
			Fit.Array.Remove(newImages, imgRemoved);
		});

		imagesStr = "";
		Fit.Array.ForEach(newImages, function(img)
		{
			imagesStr += ((imagesStr !== "") ? ";" : "") + img;
		});

		// Update model

		model.Category(lstCategories.GetSelections()[0].Value);
		model.Id(txtId.Value());
		model.Title(txtTitle.Value());
		model.Description(txtDescription.Value());
		model.Images(imagesStr);
		model.Price(parseFloat(txtPrice.Value().replace(JSShop.Language.Translations.Locale.DecimalSeparator, ".")));
		model.Currency(lstCurrencies.GetSelections()[0].Value);
		model.Vat(((txtVat.Value() !== "") ? parseFloat(txtVat.Value().replace(JSShop.Language.Translations.Locale.DecimalSeparator, ".")) : 0));
		model.Weight(((txtWeight.Value() !== "") ? parseFloat(txtWeight.Value().replace(JSShop.Language.Translations.Locale.DecimalSeparator, ".")) : 0));
		model.WeightUnit(lstWeightUnits.GetSelections()[0].Value);
		model.DeliveryTime(txtDeliveryTime.Value());
		model.DiscountExpression(txtDiscountExpr.Value());
		model.DiscountMessage(txtDiscountMsg.Value());

		// Update backend

		var cb = function() // Callback invoked once backend is updated
		{
			var cbUpdateUi = function() // Callback responsible for update UI
			{
				cmdSave.Icon("fa-check");

				setTimeout(function() { cmdSave.Icon("floppy-o"); }, 1500);

				bind(null); // Clear UI
				populatePickers(); // Update pickers (products drop down and categories drop down)
			}

			if (currentModel !== null && model !== currentModel) // Product ID was changed, so a new product was created - remove old product model
				currentModel.Delete(cbUpdateUi);
			else
				cbUpdateUi();
		}

		if (updateExisting === true)
			model.Update(cb);
		else
			model.Create(cb);
	}

	function removeImages(cb)
	{
		if (imagesRemoved.length === 0)
		{
			cb();
			return;
		}

		var req = new Fit.Http.Request(JSShop.WebService.Files.Remove);
		req.SetData("Files=" + imagesRemoved.join(";"));
		req.OnSuccess(function(sender)
		{
			cb();
		});
		req.OnFailure(function(sender)
		{
			Fit.Controls.Dialog.Alert(lang.ImagesNotRemoved);
			cb();
		});
		req.Start();
	}

	function createPreviewFrame(preview, deleteCallback)
	{
		// Create preview container

		var previewFrame = document.createElement("div");
		previewFrame.className = "JSShopFilePreview";

		// Add delete button

		var cmdDelete = document.createElement("span");
		cmdDelete.className = "fa fa-remove";
		cmdDelete.onclick = function(e)
		{
			if (JSShop.WebService.Files.Remove)
				deleteCallback();
		}
		cmdDelete.style.cssText = (!JSShop.WebService.Files.Remove ? "opacity: 0.5; cursor: not-allowed;" : "");

		previewFrame.appendChild(cmdDelete);

		// Configure image

		preview.onload = function(e)
		{
			// Optimize preview size (keep aspect ratio) and center it

			var frameDim = Fit.Dom.GetInnerDimensions(preview.parentElement);

			if (preview.width / 2 > preview.height) // Landscape
			{
				preview.style.height = "100%";
				preview.style.width = "auto";
				preview.style.marginLeft = "-" + ((preview.offsetWidth - frameDim.X) / 2) + "px"; // Using preview.offsetWidth rather than .width to get scaled width
			}
			else if (preview.width / 2 < preview.height) // Portrait
			{
				preview.style.width = "100%";
				preview.style.height = "auto";
				preview.style.marginTop = "-" + ((preview.offsetHeight - frameDim.Y) / 2) + "px";  // Using preview.offsetHeight rather than .height to get scaled height
			}
			else // Same aspect ratio as preview frame
			{
				preview.style.width = "100%";
				preview.style.height = "100%";
			}
		}

		previewFrame.appendChild(preview);

		// Done

		return previewFrame;
	}

	function getWeightUnitControlValue(unit)
	{
		if (unit === "lbs")
			return lang.Pounds + "=lbs"

		return lang.Kilos + "=kg";
	}

	init();
}
