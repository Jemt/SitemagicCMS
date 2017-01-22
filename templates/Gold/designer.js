({
	/*GetTranslations: function(lang)
	{
		var l = {};

		if (lang === "da")
		{
			l["Background"] = "Baggrund";
			l["Color"] = "Farve";
			l["Image"] = "Billede";

			l["Positioning"] = "Positionering";
			l["Position"] = "Placering";
			l["Display"] = "Visning";

			l["Background"] = "Baggrund";
			l["Background"] = "Baggrund";
			l["Background"] = "Baggrund";
			l["Background"] = "Baggrund";
			l["Background"] = "Baggrund";
			l["Background"] = "Baggrund";
			l["Background"] = "Baggrund";
			l["Background"] = "Baggrund";
			l["Background"] = "Baggrund";
		}

		return l; // Return null or empty object if no translations are wanted (e.g. for English)
	},*/

	GetEditors: function(eventArgs)
	{
		var gfxSupported = SMDesigner.Graphics.IsBrowserSupported();

		var cfg =
		{
			"Background":
			{
				"Background":
				{
					"Background":
					{
						"Color": { Type: "Color" },
						"Image": { Type: "File", Value: SMEnvironment.GetFilesDirectory() + "/images/backgrounds/Sunrise.jpg", Items: [ { Value: "generated-bg.jpg" } ] }
					},
					"Generator":
					{
						"Quality": { Type: "Slider", Min: 1, Max: 100, Step: 1, Value: 100, NoSave: true, Hidden: !gfxSupported },
						"Tile size": { Type: "Slider", Min: 20, Max: 300, Step: 10, Value: 100, NoSave: true, Hidden: !gfxSupported },
						"Variance" : { Type: "Slider", Min: 0, Max: 1, Step: 0.01, Value: 0.50, NoSave: true, Hidden: !gfxSupported },
						"": { Type: "Button", Value: "Generate background", NoSave: true, Hidden: !gfxSupported, OnClick: function(sender, eArgs)
						{
							var editors = eArgs.Editors;

							// Generate temporary background image

							var res = [1280, 720]; // Half HD
							var quality = editors["Background"]["Background"]["Generator"]["Quality"].Control.GetValue();
							var tileSize = editors["Background"]["Background"]["Generator"]["Tile size"].Control.GetValue();
							var variance = editors["Background"]["Background"]["Generator"]["Variance"].Control.GetValue();

							eArgs.Designer.Locked(true); // Display loading indicator

							setTimeout(function() // Allow UI to update (display loading indicator) by releasing JS thread which GenerateBackground(..) blocks until complete
							{
								var base64ImageData = SMDesigner.Graphics.GenerateBackground(parseInt(res[0]), parseInt(res[1]), tileSize, variance);

								SMDesigner.Graphics.SaveImage(eArgs.TemplatePath + "/generated-bg-tmp.jpg", base64ImageData, quality, function(filename)
								{
									editors["Background"]["Background"]["Background"]["Image"].Control.SetValue("generated-bg.jpg");
									editors["Background"]["Background"]["Generator"]["CacheKey"].Control.SetValue(SMRandom.CreateGuid());

									eArgs.Designer.Locked(false);
								},
								function(err)
								{
									eArgs.Designer.Locked(false);
									alert(err);
								});
							}, 25);
						}},
						"CacheKey": { Type: "Input", Hidden: true }
					}
				}
			},

			"Header":
			{
				"Positioning":
				{
					"Positioning":
					{
						"Position": { Type: "Selector", Value: "", Items: [{Title: "Normal", Value: ""}, {Title: "Left", Value: "left"}, {Title: "Right", Value: "right"}] }
					},
					"Display":
					{
						"Type": { Type: "Selector", Items: [{ Title : "Normal", Value: "block"}, { Title : "Fit to content", Value: "inline-block"}, { Title : "Hidden", Value: "none"}], Value: "block" }
					}
				},
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 32, "px", "#F0F0F0", "Normal", "Left")
				},
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls(),
					"Shadow": SMDesigner.Helpers.GetShadowControls()
				},
				"Dimensions":
				{
					"Dimensions":
					{
						"Width": SMDesigner.Helpers.GetDimensionControl(),
						"Height": SMDesigner.Helpers.GetDimensionControl()
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(null, null, null, 20, "px"),
					"Padding": SMDesigner.Helpers.GetIndentationControls()
				},
				"Content":
				{
					"Information":
					{
						"": { Type: "Label", Value: "Add, remove, or edit header content from main menu:<br>Content > Pages ><br>Header and Footer" }
					}
				}
			},

			"Menu":
			{
				"Positioning":
				{
					"Positioning":
					{
						"Position": { Type: "Selector", Value: "", Items: [{Title: "Normal", Value: ""}, {Title: "Left", Value: "left"}, {Title: "Right", Value: "right"}] }
					},
					"Display":
					{
						"Type": { Type: "Selector", Items: [{ Title : "Normal", Value: "block"}, { Title : "Fit to content", Value: "inline-block"}/*, { Title : "Hidden", Value: "none"}*/], Value: "block" }
					}
				},
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 16, "px", "#333333", "Bold", "Left", 300, "%")
				},
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color", Value: "#F7D35E" },
						"Highlight": { Type: "Color", Value: { Red: 255, Green: 255, Blue: 255, Alpha: 0.4 } }
					},
					"Border": SMDesigner.Helpers.GetBorderControls(null, null, null, 5, null),
					"Shadow": SMDesigner.Helpers.GetShadowControls()
				},
				"Dimensions":
				{
					"Dimensions":
					{
						"Width": SMDesigner.Helpers.GetDimensionControl(),
						"Height (line height)": SMDesigner.Helpers.GetDimensionControl(300, "%")
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(),
					"Padding": SMDesigner.Helpers.GetIndentationControls(null, 5, 5, null, "px")
				},
				"Content":
				{
					"Information":
					{
						"": { Type: "Label", Value: "Add, remove, or edit menu links from main menu:<br>Content > Menu" }
					}
				}
			},

			"Submenus":
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 14, "px", "#333333", "Bold", "Left", 200, "%")
				},
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color", Value: "#F7D35E" },
						"Highlight": { Type: "Color", Value: { Red: 255, Green: 255, Blue: 255, Alpha: 0.4 } }
					},
					"Border": SMDesigner.Helpers.GetBorderControls(null, null, null, 5, "Bottom"),
					"Shadow": SMDesigner.Helpers.GetShadowControls()
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(),
					"Padding": SMDesigner.Helpers.GetIndentationControls()
				},
				"Content":
				{
					"Information":
					{
						"": { Type: "Label", Value: "Add, remove, or edit menu links from main menu:<br>Content > Menu" }
					}
				}
			},

			"Page":
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 14, "px", "#333333", "Normal", "Left"),
					"Links":
					{
						"Color": { Type: "Color", Value: "#106FC7" }
					}
				},
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color", Value: { Red: 255, Green: 255, Blue: 255, Alpha: 0.6 } }
					},
					"Border": SMDesigner.Helpers.GetBorderControls(null, null, null, 5, null),
					"Shadow": SMDesigner.Helpers.GetShadowControls()
				},
				"Dimensions":
				{
					"Dimensions":
					{
						"Width": SMDesigner.Helpers.GetDimensionControl(900, "px")
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(20, null, null, null, "px"),
					"Padding": SMDesigner.Helpers.GetIndentationControls(20, 20, 20, 20, "px")
				},
				"Content":
				{
					"Information":
					{
						"": { Type: "Label", Value: "Add, remove, or edit pages from main menu:<br>Content > Pages" }
					}
				}
			},

			"Cards page":
			{
				"Dimensions":
				{
					"Dimensions":
					{
						"Width": SMDesigner.Helpers.GetDimensionControl(900, "px")
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(20, null, null, null, "px"),
					"Padding": SMDesigner.Helpers.GetIndentationControls(null, null, null, null, "px")
				}
			},

			"Cards":
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 14, "px", "#333333", "Normal", "Left"),
					"Header and footer": SMDesigner.Helpers.GetFontControls("Verdana", 14, "px", "#FFFFFF", "Bold", "Left"),
					"Links":
					{
						"Color": { Type: "Color", Value: "#106FC7" }
					}
				},
				"Borders and colors":
				{
					"Background":
					{
						"Content": { Type: "Color", Value: { Red: 255, Green: 255, Blue: 255, Alpha: 0.6 } },
						"Header and footer": { Type: "Color", Value: "#000000" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls(null, null, null, 5, null),
					"Shadow": SMDesigner.Helpers.GetShadowControls("#333333", 0, 10, 2, 2)
				},
				"Content":
				{
					"Information":
					{
						"": { Type: "Label", Value: "Add, remove, or edit cards on pages from main menu:<br>Content > Pages" }
					}
				}
			},

			"Snippets":
			{
				"Positioning":
				{
					"Positioning":
					{
						"Position": { Type: "Selector", Value: "right", Items: [{Title: "Left", Value: "left"}, {Title: "Right", Value: "right"}] }
					},
					"Display":
					{
						"Type": { Type: "Selector", Items: [{ Title : "Normal", Value: "block"}, { Title : "Hidden", Value: "none"}], Value: "block" }
					}
				},
				"Text":
				{
					// NOTICE: Snippets are special since they inherit font styling from Page.
					// Therefore most initial values are left out since controls are only used to override defaults.
					// To allow this behaviour, AllowEmpty is set True on drop downs further down below.
					"Formatting": SMDesigner.Helpers.GetFontControls(), // Snippets inherits from Page
					"Title": SMDesigner.Helpers.GetFontControls(null, 1.15, "em", null, "Bold", null), // Title inherits from Page and Formatting above
					"Links":
					{
						"": { Type: "Label", Value: "By default links inherit their color from Page." },
						"Color": { Type: "Color" }
					}
				},
				"Borders and colors":
				{
					"Background":
					{
						"Background": { Type: "Color", Value: { Red: 255, Green: 255, Blue: 255, Alpha: 0.4 } },
						"Title": { Type: "Color", Value: { Red: 255, Green: 255, Blue: 255, Alpha: 0.40 } },
					},
					"Border": SMDesigner.Helpers.GetBorderControls({ Red: 0, Green: 0, Blue: 0, Alpha: 0.25 }, null, null, 5, null),
					"Shadow": SMDesigner.Helpers.GetShadowControls()
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(20, 20, 20, 20, "px"),
				},
				"Dimensions":
				{
					"Dimensions":
					{
						"Width": SMDesigner.Helpers.GetDimensionControl(200, "px")
					}
				}
			},

			"Footer":
			{
				"Positioning":
				{
					"Display":
					{
						"Type": { Type: "Selector", Items: [{ Title : "Normal", Value: "block"}, { Title : "Fit to content", Value: "inline-block"}, { Title : "Hidden", Value: "none"}], Value: "block" }
					}
				},
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 14, "px", "#EFEFEF", "Normal", "Left")
				},
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color", Value: { Red: 0, Green: 0, Blue: 0, Alpha: 0.7 } }
					},
					"Border": SMDesigner.Helpers.GetBorderControls(null, null, null, 5, null),
					"Shadow": SMDesigner.Helpers.GetShadowControls()
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(),
					"Padding": SMDesigner.Helpers.GetIndentationControls(null, 20, 20, null, "px")
				},
				"Content":
				{
					"Information":
					{
						"": { Type: "Label", Value: "Add, remove, or edit footer content from main menu:<br>Content > Pages ><br>Header and Footer" }
					}
				}
			},

			"Controls":
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 14, "px", "#333333", "Normal", "Left")
				},
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color", Value: "#FFFFFF" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls("#9F9F9F", "solid", 1)
				}
			},

			"System links":
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 11, "px", "#AEAEAE", "Normal", "Left")
				}
			},

			"Top":
			{
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color" },
						"Image": { Type: "File" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls()
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(null, null, null, null, "px")
				},
				"Dimensions":
				{
					"Dimensions":
					{
						"Height": SMDesigner.Helpers.GetDimensionControl()
					}
				}
			},

			"Middle":
			{
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color" },
						"Image": { Type: "File" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls()
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(null, null, null, null, "px")
				}
			},

			"Bottom":
			{
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color" },
						"Image": { Type: "File" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls()
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(null, null, null, null, "px")
				},
				"Dimensions":
				{
					"Dimensions":
					{
						"Height": SMDesigner.Helpers.GetDimensionControl()
					}
				}
			},

			"Advanced":
			{
				"Custom CSS":
				{
					"Custom CSS":
					{
						"CSS": { Type: "Text", MultiLine: true }
					}
				}
			}
		};

		var parentWindow = window.opener || window.top;

		if (parentWindow.document.querySelector("html.SMPagesCardLayout") !== null)
		{
			// Margin-top is fixed in Card Layout - disable control
			cfg["Footer"]["Indentation"]["Margin"]["Top"].Disabled = true;
		}

		cfg["Cards page"]["Indentation"]["Margin"]["All sides"].Hidden = true;
		cfg["Cards page"]["Indentation"]["Margin"]["Left"].Hidden = true;
		cfg["Cards page"]["Indentation"]["Margin"]["Right"].Hidden = true;
		cfg["Cards page"]["Indentation"]["Padding"]["All sides"].Hidden = true;
		cfg["Cards page"]["Indentation"]["Padding"]["Left"].Hidden = true;
		cfg["Cards page"]["Indentation"]["Padding"]["Right"].Hidden = true;

		if (parentWindow.document.querySelector("div.TPLSnippet") !== null)
		{
			// Hide "Apply to" - borders are fixed for snippets, and applied to both snippet box and below header
			cfg["Snippets"]["Borders and colors"]["Border"]["Apply to"].Hidden = true;

			// Add label as first control to Text > Formatting section
			var formatting = cfg["Snippets"]["Text"]["Formatting"];
			cfg["Snippets"]["Text"]["Formatting"] = {};
			cfg["Snippets"]["Text"]["Formatting"][""] =  { Type: "Label", Value: "By default snippets inherit font styles from Page." };
			for (var prop in formatting)
				cfg["Snippets"]["Text"]["Formatting"][prop] = formatting[prop];

			// Add label as first control to Text > Title section
			formatting = cfg["Snippets"]["Text"]["Title"];
			cfg["Snippets"]["Text"]["Title"] = {};
			cfg["Snippets"]["Text"]["Title"][""] =  { Type: "Label", Value: "By default snippet titles inherit font styles from Page and Formatting above." };
			for (var prop in formatting)
				cfg["Snippets"]["Text"]["Title"][prop] = formatting[prop];

			// Allow empty selection in font selectors - styles are inherited from Page if not overridden
			cfg["Snippets"]["Text"]["Formatting"]["Font"].AllowEmpty = true;
			cfg["Snippets"]["Text"]["Formatting"]["Style"].AllowEmpty = true;
			cfg["Snippets"]["Text"]["Formatting"]["Alignment"].AllowEmpty = true;
			cfg["Snippets"]["Text"]["Title"]["Font"].AllowEmpty = true;
			//cfg["Snippets"]["Text"]["Title"]["Style"].AllowEmpty = true;
			cfg["Snippets"]["Text"]["Title"]["Alignment"].AllowEmpty = true;
		}

		cfg["System links"]["Text"]["Formatting"]["Alignment"].Hidden = true;
		cfg["System links"]["Text"]["Formatting"]["Line height"].Hidden = true;

		return cfg;
	},

	GetSelectors: function(eventArgs)
	{
		// Return selectors which turn elements into selectable areas (point and click)

		var parentWindow = window.opener || window.top;

		var selectors =
		{
			"Background":	"html",
			"Header":		"div.TPLHeader",
			"Menu":			"div.TPLMenu > ul",
			"Submenus":		"div.TPLMenu li ul",
			"Page":			"div.TPLContent",
			"Cards page":	"html.SMPagesCardLayout div.TPLContent",
			"Cards":		"div.SMPagesCard",
			"Snippets":		"div.TPLSnippets",
			"Footer":		"div.TPLFooter",
			"Controls":		"input, textarea, select",
			"System links":	"div.TPLLinks",
			"Top":			"div.TPLTop",		// if (parentWindow.document.querySelector("div.TPLTop").offsetHeight > 0)
			"Bottom":		"div.TPLBottom"		// if (parentWindow.document.querySelector("div.TPLBottom").offsetHeight > 0)
		}

		// Only make TPLMiddle selectable if its height is different from the height of the document.
		// Otherwise the user will not be able to click the background which is more likely to be used.
		// This feature is used by the Sitemagic2012 template.
		if (SMBrowser.GetBrowser() === "MSIE" && SMBrowser.GetVersion() <= 10)
		{
			if (parentWindow.document.querySelector("body").offsetHeight !== (parentWindow.document.querySelector("div.TPLMiddle").offsetHeight - parentWindow.document.querySelector("div.TPLMiddle").offsetTop))
				selectors["Middle"] = "div.TPLMiddle";
		}
		else
		{
			if (parentWindow.document.querySelector("html").offsetHeight !== parentWindow.document.querySelector("div.TPLMiddle").offsetHeight)
				selectors["Middle"] = "div.TPLMiddle";
		}

		return selectors;
	},

	GetExclusion: function(eventArgs)
	{
		// Exclude (hide) sections not relevant

		var parentWindow = window.opener || window.top;
		var exclude = [];

		if (parentWindow.document.querySelector("html.SMPagesCustomHeader") === null)
			exclude.push("Header");

		if (parentWindow.document.querySelector("html.SMPagesCardLayout") !== null)
		{
			exclude.push("Page");
		}
		else
		{
			exclude.push("Cards page");
			exclude.push("Cards");
		}

		if (parentWindow.document.querySelector("div.TPLSnippet") === null)
			exclude.push("Snippets");

		if (parentWindow.document.querySelector("html.SMPagesCustomFooter") === null)
			exclude.push("Footer");

		return exclude;
	},

	OnEditorsCreated: function(eventArgs)
	{
		var editors = eventArgs.Editors;

		// Configure control synchronization.
		// Notice that control synchronization takes place when the user interacts with controls, not if controls are updated programmatically!

		// Header and Menu position
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.Bidirectional, editors["Header"]["Positioning"]["Positioning"]["Position"], editors["Menu"]["Positioning"]["Positioning"]["Position"], {"left": "right", "right": "left"});

		// Menu and submenus
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Menu"]["Borders and colors"]["Background"]["Color"], editors["Submenus"]["Borders and colors"]["Background"]["Color"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Menu"]["Borders and colors"]["Background"]["Highlight"], editors["Submenus"]["Borders and colors"]["Background"]["Highlight"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Menu"]["Text"]["Formatting"]["Font"], editors["Submenus"]["Text"]["Formatting"]["Font"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Menu"]["Text"]["Formatting"]["Color"], editors["Submenus"]["Text"]["Formatting"]["Color"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Menu"]["Text"]["Formatting"]["Style"], editors["Submenus"]["Text"]["Formatting"]["Style"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.Bidirectional, editors["Menu"]["Dimensions"]["Dimensions"]["Height (line height)"], editors["Menu"]["Text"]["Formatting"]["Line height"]);

		// Page and Cards - link color
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Links"]["Color"], editors["Cards"]["Text"]["Links"]["Color"]);

		// Page and Snippets - link color
		// Notice: In this case synchronization has a disadvantage. Font formatting is inherited since Snippets are contained in Page.
		/*SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Font"], editors["Snippets"]["Text"]["Formatting"]["Font"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Size"], editors["Snippets"]["Text"]["Formatting"]["Size"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Color"], editors["Snippets"]["Text"]["Formatting"]["Color"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Style"], editors["Snippets"]["Text"]["Formatting"]["Style"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Alignment"], editors["Snippets"]["Text"]["Formatting"]["Alignment"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Line height"], editors["Snippets"]["Text"]["Formatting"]["Line height"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Font"], editors["Snippets"]["Text"]["Title"]["Font"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Size"], editors["Snippets"]["Text"]["Title"]["Size"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Color"], editors["Snippets"]["Text"]["Title"]["Color"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Alignment"], editors["Snippets"]["Text"]["Title"]["Alignment"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Formatting"]["Line height"], editors["Snippets"]["Text"]["Title"]["Line height"]);*/
		//SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqual, editors["Page"]["Text"]["Links"]["Color"], editors["Snippets"]["Text"]["Links"]["Color"]);
	},

	OnEditorsDisplayed: function(eventArgs)
	{
		var section = eventArgs.Section;
		var parentWindow = window.opener || window.top;

		// Menu: Open Admin drop down and highlight Logout item when menu is being styled - close it again when another section is being styled
		if (section === "Menu" || section === "Submenus")
		{
			SMDom.AddClass(parentWindow.document.querySelector("li.SMMenuSMMenuAdmin"), "TPLMenuHover");		// Admin drop down
			SMDom.AddClass(parentWindow.document.querySelector("li.SMMenuSMLoginLogout"), "TPLMenuHover");		// Logout item
		}
		else
		{
			SMDom.RemoveClass(parentWindow.document.querySelector("li.SMMenuSMMenuAdmin"), "TPLMenuHover");		// Admin drop down
			SMDom.RemoveClass(parentWindow.document.querySelector("li.SMMenuSMLoginLogout"), "TPLMenuHover");	// Logout item
		}
	},

	OnBeforeSave: function(eventArgs)
	{
		var editors = eventArgs.Editors;

		if (editors["Background"]["Background"]["Generator"][""].Control.IsDirty() === true && editors["Background"]["Background"]["Background"]["Image"].Control.GetValue() === "generated-bg.jpg")
		{
			// Save temporary background image permanently

			eventArgs.Designer.Locked(true);

			SMDesigner.Graphics.MoveImage(eventArgs.TemplatePath + "/generated-bg-tmp.jpg", eventArgs.TemplatePath + "/generated-bg.jpg", function(filename)
			{
				// IE7/8 cannot scale CSS background images.
				// Enlarge background image to make it look decent on legacy IE.
				SMDesigner.Graphics.ScaleImage(eventArgs.TemplatePath + "/generated-bg.jpg", eventArgs.TemplatePath + "/generated-bg.jpg", 2.0, 90, function(filename)
				{
					editors["Background"]["Background"]["Generator"][""].Control.Reset(); // Button is no longer considered Dirty when reset

					eventArgs.Designer.Save();
					eventArgs.Designer.Locked(false);
				},
				function(err)
				{
					editors["Background"]["Background"]["Generator"][""].Control.Reset(); // Button is no longer considered Dirty when reset

					eventArgs.Designer.Save(); // An error occured, but we want to save remaining changes
					eventArgs.Designer.Locked(false);

					alert(err);
				});
			},
			function(err)
			{
				editors["Background"]["Background"]["Generator"][""].Control.Reset(); // Button is no longer considered Dirty when reset

				eventArgs.Designer.Save(); // An error occured, but we want to save remaining changes
				eventArgs.Designer.Locked(false);

				alert(err);
			});

			return false; // Cancel Save - once image is done processing, the Save operation will be triggered again programmatically (see above)
		}
	},

	GetCss: function(eventArgs)
	{
		var parentWindow = window.opener || window.top;

		var editors = eventArgs.Editors;
		var sender = (eventArgs.Sender ? eventArgs.Sender.Control : null);
		var saving = eventArgs.Saving;

		var getBackground = function()
		{
			var css = "";

			// Background color

			var colorValue = SMDesigner.Helpers.GetColorCss(editors["Background"]["Background"]["Background"]["Color"], "background", true);
			if (colorValue !== null)
			{
				/* Apply background color to everything - pages, extensions, and dialogs */

				css += "html";
				css += "{";
				css += colorValue;
				css += "}";
			}

			// Apply background image

			var wallpaper = SMDesigner.Helpers.GetControlValue(editors["Background"]["Background"]["Background"]["Image"]);

			if (wallpaper !== null)
			{
				// Use generated-bg-tmp.jpg instead of generated-bg.jpg if a new background has been generated
				if (wallpaper === "generated-bg.jpg" && editors["Background"]["Background"]["Generator"][""].Control.IsDirty() === true)
					wallpaper = "generated-bg-tmp.jpg";

				// Append CacheKey to generated image
				if (wallpaper.indexOf("generated-bg") === 0 && editors["Background"]["Background"]["Generator"]["CacheKey"].Control.IsDirty() === true)
					wallpaper += "?cache=" + editors["Background"]["Background"]["Generator"]["CacheKey"].Control.GetValue();

				// For background images located under files/images:
				// Use relative path when saving, since image will be referenced from templates/XYZ/style.css:
				// background: url("../../files/images/background/Sky.jpg");
				// During design time, this is not necessary since CSS is injected
				// into the page, meaning files are resolved from page root.
				if (saving === true && wallpaper.indexOf(SMEnvironment.GetFilesDirectory() + "/images/") === 0)
					wallpaper = "../../" + wallpaper;

				// For background images located under templates/XYZ (e.g. generated-bg.jpg):
				// Use absolute path when making changes (live updates), since
				// image will be referenced from the page root (CSS is injected):
				// background: url("templates/Sunrise/generated-bg.jpg");
				// When saving, this is not necessary since the file will then be
				// referenced from templates/XYZ/style.css.
				if (saving === false && wallpaper !== "" && wallpaper.indexOf(SMEnvironment.GetFilesDirectory() + "/images/") !== 0) // Assume images not starting with "files/images/" are located in template folder
					wallpaper = eventArgs.TemplatePath + "/" + wallpaper;

				css += "html.Normal,"
				css += "html.Basic.SMPagesViewer,"
				css += "html.SMPagesEditor.SMPagesContentPage,"
				css += "html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameHeader,"
				css += "html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter"
				css += "{"
				css += "background-image: " + ((wallpaper !== "") ? "url(\"" + wallpaper + "\")" : "none") + ";";
				css += "}";
			}

			return css;
		}

		var getTopMiddleBottom = function(section)
		{
			var css = "";

			var colorValue = SMDesigner.Helpers.GetColorCss(editors[section]["Borders and colors"]["Background"]["Color"], "background", true);
			var image = SMDesigner.Helpers.GetControlValue(editors[section]["Borders and colors"]["Background"]["Image"]);
			//var repeat = SMDesigner.Helpers.GetControlValue(editors[section]["Background"]["Image"]["Repeat"]);
			//var size = SMDesigner.Helpers.GetControlValue(editors[section]["Background"]["Image"]["Size"]);
			var border = SMDesigner.Helpers.GetBorderCss(editors[section]["Borders and colors"]["Border"], true);
			var margin = SMDesigner.Helpers.GetIndentationCss(editors[section]["Indentation"]["Margin"], "margin");
			var height = ((section !== "Middle") ? SMDesigner.Helpers.GetDimensionCss(editors[section]["Dimensions"]["Dimensions"]["Height"], "height") : null);

			if (colorValue !== null || border !== null || image !== null || border !== null || height !== null)
			{
				if (height === null && section !== "Middle")
					height = "height: 50px;";

				css += "div.TPL" + section;
				css += "{";
				css += ((colorValue !== null) ? colorValue : "");
				css += ((image !== null) ? "background-image: url('" + ((saving === true) ? "../../" : "") + image + "');" : "");
				css += ((image !== null) ? "background-attachment: fixed;" : "");
				css += ((image !== null) ? "background-repeat: no-repeat;" : "");
				css += ((image !== null) ? "background-size: cover;" : "");
				//css += ((repeat !== null) ? "background-repeat: " + repeat + ";" : "");
				//css += ((size !== null) ? "background-size: cover;" : "");
				css += ((border !== null) ? border : "");
				css += ((margin !== null) ? margin : "");
				css += ((height !== null) ? height : "");
				css += "}";

				// Adjust look and feel in content editor

				if (section === "Middle")
				{
					if (image !== null)
					{
						css += "html.Basic.SMPagesViewer,"
						css += "html.SMPagesEditor.SMPagesContentPage"
						css += "{"
						css += "background-image: url('" + ((saving === true) ? "../../" : "") + image + "');";
						css += "}";
					}
					else if (colorValue !== null)
					{
						css += "html.Basic.SMPagesViewer,"
						css += "html.SMPagesEditor.SMPagesContentPage"
						css += "{"
						css += colorValue;
						css += "}";
					}
				}
			}

			return css;
		}

		var getHeader = function()
		{
			// Adjust control values when necessary

			if (sender === editors["Header"]["Positioning"]["Positioning"]["Position"].Control
				|| sender === editors["Menu"]["Positioning"]["Positioning"]["Position"].Control) // Menu position affects header position!
			{
				// Optimize settings when header is positioned

				var pos = SMDesigner.Helpers.GetControlValue(editors["Header"]["Positioning"]["Positioning"]["Position"]);

				if (pos === "left" || pos === "right") // Next to menu
				{
					// Remove any margin
					for (var prop in editors["Header"]["Indentation"]["Margin"])
						if (prop !== "All sides")
							editors["Header"]["Indentation"]["Margin"][prop].Control.SetValue("0");

					editors["Header"]["Indentation"]["Margin"]["All sides"].Control.SetValue(0);
				}
				else // Above menu
				{
					// Reset margins to initial values
					for (var prop in editors["Header"]["Indentation"]["Margin"])
						editors["Header"]["Indentation"]["Margin"][prop].Control.Reset();
				}

				// Always reset height and line-height when header position is changed
				editors["Header"]["Text"]["Formatting"]["Line height"].Control.Reset();
				editors["Header"]["Text"]["Formatting"]["Line height"].Selector.Control.Reset();
				editors["Header"]["Dimensions"]["Dimensions"]["Height"].Control.Reset();
				editors["Header"]["Dimensions"]["Dimensions"]["Height"].Selector.Control.Reset();
			}

			// Variables holding CSS

			var cssHeader = "";
			var cssHeaderEditorOnly = "";
			var cssHeaderEditorOnlyHtmlElement = "";

			// Extract CSS values

			// Display
			var position = SMDesigner.Helpers.GetControlValue(editors["Header"]["Positioning"]["Positioning"]["Position"]);
			var display = SMDesigner.Helpers.GetControlValue(editors["Header"]["Positioning"]["Display"]["Type"]);
			cssHeader += ((display !== null) ? "display: " + display + ";" : "");
			cssHeaderEditorOnly += ((position === "left" || position === "right") ? "display: inline-block;" : ""); // Header is floated and behaves like inline-block (does not work with IE7-8 - too bad!)
			cssHeaderEditorOnly += ((position === null && display === "hidden") ? "display: block;" : "");			// Undo display:none in editor so we can still change its content

			// Width (from page)
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Dimensions"]["Dimensions"]["Width"], "width");
			cssHeaderEditorOnlyHtmlElement += ((width !== null && display !== "inline-block") ? width : "");

			// Dimensions
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Header"]["Dimensions"]["Dimensions"]["Width"], "width");
			var height = SMDesigner.Helpers.GetDimensionCss(editors["Header"]["Dimensions"]["Dimensions"]["Height"], "height");
			cssHeader += ((width !== null) ? width : "");
			cssHeader += ((height !== null) ? height : "");

			// Indentation
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Header"]["Indentation"]["Margin"], "margin");
			var padding = SMDesigner.Helpers.GetIndentationCss(editors["Header"]["Indentation"]["Padding"], "padding");
			cssHeader += ((margin !== null) ? margin : "");
			cssHeader += ((padding !== null) ? padding : "");

			// Keep default margin-top/bottom in editor
			if (margin !== null && margin.indexOf("margin-top") > -1)
				cssHeaderEditorOnly += "margin-top: 20px;";
			if (margin !== null && margin.indexOf("margin-bottom") > -1)
				cssHeaderEditorOnly += "margin-bottom: 20px;";

			// Borders
			var border = SMDesigner.Helpers.GetBorderCss(editors["Header"]["Borders and colors"]["Border"], true);
			cssHeader += ((border !== null) ? border : "");
			cssHeaderEditorOnly += ((border !== null) ? "outline: none;" : "");

			// Shadows
			var shadow = SMDesigner.Helpers.GetShadowCss(editors["Header"]["Borders and colors"]["Shadow"]);
			cssHeader += ((shadow !== null) ? shadow : "");

			// Background color
			var backColor = SMDesigner.Helpers.GetColorCss(editors["Header"]["Borders and colors"]["Background"]["Color"], "background", true);
			cssHeader += ((backColor !== null) ? backColor : "");

			// Text formatting
			var text = SMDesigner.Helpers.GetFontCss(editors["Header"]["Text"]["Formatting"]);
			cssHeader += ((text !== null) ? text : "");

			// Wrap CSS in selectors

			var css = "";

			if (cssHeader !== "")
			{
				css += "html.SMPagesCustomHeader div.TPLHeader, html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameHeader body";
				css += "{";
				css += cssHeader;
				css += "}";
			}

			if (cssHeaderEditorOnly !== "")
			{
				css += "html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameHeader body";
				css += "{";
				css += cssHeaderEditorOnly;
				css += "}";
			}

			if (cssHeaderEditorOnlyHtmlElement !== "")
			{
				css += "html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameHeader";
				css += "{";
				css += cssHeaderEditorOnlyHtmlElement;
				css += "}";
			}

			return css;
		}

		var getMenu = function()
		{
			// Adjust control values when necessary

			if (sender === editors["Menu"]["Positioning"]["Positioning"]["Position"].Control
				|| sender === editors["Header"]["Positioning"]["Positioning"]["Position"].Control) // Header position affects menu position!
			{
				// Optimize settings when menu is positioned

				var pos = SMDesigner.Helpers.GetControlValue(editors["Menu"]["Positioning"]["Positioning"]["Position"]);

				if (pos === "left" || pos === "right") // Next to header
				{
					// Remove any margin
					for (var prop in editors["Menu"]["Indentation"]["Margin"])
						if (prop !== "All sides")
							editors["Menu"]["Indentation"]["Margin"][prop].Control.SetValue("0");

					editors["Menu"]["Indentation"]["Margin"]["All sides"].Control.SetValue(0);
				}
				else // Below header
				{
					// Reset margins to initial values
					for (var prop in editors["Menu"]["Indentation"]["Margin"])
						editors["Menu"]["Indentation"]["Margin"][prop].Control.Reset();
				}

				// Always reset line-height when menu position is changed
				editors["Menu"]["Text"]["Formatting"]["Line height"].Control.Reset();
				editors["Menu"]["Text"]["Formatting"]["Line height"].Selector.Control.Reset();
				editors["Menu"]["Dimensions"]["Dimensions"]["Height (line height)"].Control.Reset();
				editors["Menu"]["Dimensions"]["Dimensions"]["Height (line height)"].Selector.Control.Reset();

				// Warn user if menu and header cannot fit next to each other
				if (editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.IsDirty() === true
					&& editors["Header"]["Positioning"]["Display"]["Type"].Control.GetValue() !== "none")
				{
					// Postpone check til after JS thread is released, and new CSS is applied. This also allows for
					// designer to perform control synchronization which is disabled when GetCss(..) is called to
					// prevent infinite loops.
					setTimeout(function()
					{
						var head = parentWindow.document.querySelector("div.TPLHeader");
						var menu = parentWindow.document.querySelector("div.TPLMenu > ul");

						if (head.offsetTop !== menu.offsetTop)
						{
							var err = "";
							err += "Header and menu positioning:\n";
							err += "There is not sufficient space to position header and menu next to each other. ";
							err += "Either reduce the size of the header and/or menu, e.g. by changing font size, ";
							err += "or increase the width of your page.\n\n";
							err += "Do you want to keep the change ?";

							if (confirm(err) !== true)
								editors["Header"]["Positioning"]["Positioning"]["Position"].Control.SetValue(""); // Synchronization updates menu position
						}
					}, 0);
				}
			}

			// Variables holding CSS

			var cssMenu = "";				// div.TPLMenu > ul
			var cssLinkItems = "";			// div.TPLMenu > ul > li			(root items only)
			var cssLinkItemsHover = "";		// div.TPLMenu > ul > li:hover		(root items only)
			var cssLinks = "";				// div.TPLMenu > ul > li > a		(root links only)

			// Extract CSS values

			// Display
			var display = SMDesigner.Helpers.GetControlValue(editors["Menu"]["Positioning"]["Display"]["Type"]);
			cssMenu += ((display !== null) ? "display: " + display + ";" : "");

			// Dimensions
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Menu"]["Dimensions"]["Dimensions"]["Width"], "width");
			cssMenu += ((width !== null) ? width : "");

			// Indentation
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Menu"]["Indentation"]["Margin"], "margin");
			var padding = SMDesigner.Helpers.GetIndentationCss(editors["Menu"]["Indentation"]["Padding"], "padding");
			cssMenu += ((margin !== null) ? margin : "");
			cssMenu += ((padding !== null) ? padding : "");

			// Borders
			var border = SMDesigner.Helpers.GetBorderCss(editors["Menu"]["Borders and colors"]["Border"], true);
			cssMenu += ((border !== null) ? border : "");

			// Shadows
			var shadow = SMDesigner.Helpers.GetShadowCss(editors["Menu"]["Borders and colors"]["Shadow"]);
			cssMenu += ((shadow !== null) ? shadow : "");

			// Background colors
			var backColor = SMDesigner.Helpers.GetColorCss(editors["Menu"]["Borders and colors"]["Background"]["Color"], "background", true);
			var highlightColor = SMDesigner.Helpers.GetColorCss(editors["Menu"]["Borders and colors"]["Background"]["Highlight"], "background", true);
			cssMenu += ((backColor !== null) ? backColor : "");
			cssLinkItemsHover += ((highlightColor !== null) ? highlightColor : "");

			// Text formatting
			var text = SMDesigner.Helpers.GetFontCss(editors["Menu"]["Text"]["Formatting"]);

			// Fix: Move text alignment from Link CSS to Menu CSS, where it belongs
			if (text !== null)
			{
				if (text.indexOf("text-align") > -1)
				{
					var pos = text.indexOf("text-align");
					var align = text.substring(pos, text.indexOf(";", pos) + 1);

					cssLinks += text.replace(align, "");
					cssMenu += align;
				}
				else
				{
					cssLinks += text;
				}
			}

			// Wrap CSS in selectors

			var css = "";

			if (cssMenu !== "")
			{
				css += "div.TPLMenu > ul";
				css += "{";
				css += cssMenu;
				css += "}";
			}

			if (cssLinkItems !== "")
			{
				css += "div.TPLMenu > ul > li";
				css += "{";
				css += cssLinkItems;
				css += "}";
			}

			if (cssLinkItemsHover !== "")
			{
				css += "div.TPLMenu > ul > li:hover, div.TPLMenu > ul > li.TPLMenuHover";
				css += "{";
				css += cssLinkItemsHover;
				css += "}";
			}

			if (cssLinks !== "")
			{
				css += "div.TPLMenu > ul > li > a";
				css += "{";
				css += cssLinks;
				css += "}";
			}

			// Advanced menu and header positioning

			var menuPos = SMDesigner.Helpers.GetControlValue(editors["Menu"]["Positioning"]["Positioning"]["Position"]);
			var headerPos = SMDesigner.Helpers.GetControlValue(editors["Header"]["Positioning"]["Positioning"]["Position"]);

			if (menuPos !== null)
			{
				// Float menu
				css += "div.TPLMenu > ul";
				css += "{";
				css += "float: " + menuPos + ";";
				css += "}";

				// Float header in oppersite direction
				css += "html.SMPagesCustomHeader div.TPLHeader";
				css += "{";
				css += "float: " + headerPos + ";";
				css += "}";

				/* Stop float - clear fix: http://css-tricks.com/snippets/css/clear-fix */
				css += "div.TPLMenu:after"
				css += "{";
				css += "visibility: hidden;";
				css += "display: block;";
				css += "content: '';";
				css += "clear: both;";
				css += "height: 0;";
				css += "}";
				css += "div.TPLMenu { *zoom: 1; }"; /* Fix IE7 */
			}

			return css;
		}

		var getSubMenus = function()
		{
			// Variables holding CSS

			var cssSubMenus = "";			// div.TPLMenu li ul
			var cssLinkItems = "";			// div.TPLMenu li ul li
			var cssLinkItemsHover = "";		// div.TPLMenu li ul li:hover
			var cssLinks = "";				// div.TPLMenu li ul li a

			// Extract CSS values

			// Indentation - padding:
			// Top/bottom is applied to submenu, left/right to links.
			// This is done to make sure highlight color is not affected by
			// padding, hence it keeps stretching edge-to-edge horizontally.
			var paddingTop = SMDesigner.Helpers.GetControlValue(editors["Submenus"]["Indentation"]["Padding"]["Top"], true);
			var paddingLeft = SMDesigner.Helpers.GetControlValue(editors["Submenus"]["Indentation"]["Padding"]["Left"], true);
			var paddingRight = SMDesigner.Helpers.GetControlValue(editors["Submenus"]["Indentation"]["Padding"]["Right"], true);
			var paddingBottom = SMDesigner.Helpers.GetControlValue(editors["Submenus"]["Indentation"]["Padding"]["Bottom"], true);
			cssSubMenus += ((paddingTop !== null) ? "padding-top: " + paddingTop + ";" : "");
			cssLinkItems += ((paddingLeft !== null) ? "padding-left: " + paddingLeft + ";" : "");
			cssLinkItems += ((paddingRight !== null) ? "padding-right: " + paddingRight + ";" : "");
			cssSubMenus += ((paddingBottom !== null) ? "padding-bottom: " + paddingBottom + ";" : "");

			// Indentation - margin
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Submenus"]["Indentation"]["Margin"], "margin");
			cssSubMenus += ((margin !== null) ? margin : "");

			// Borders
			var border = SMDesigner.Helpers.GetBorderCss(editors["Submenus"]["Borders and colors"]["Border"], true);
			cssSubMenus += ((border !== null) ? border : "");

			// Shadows
			var shadow = SMDesigner.Helpers.GetShadowCss(editors["Submenus"]["Borders and colors"]["Shadow"]);
			cssSubMenus += ((shadow !== null) ? shadow : "");

			// Background colors
			var backColor = SMDesigner.Helpers.GetColorCss(editors["Submenus"]["Borders and colors"]["Background"]["Color"], "background", true);
			var highlightColor = SMDesigner.Helpers.GetColorCss(editors["Submenus"]["Borders and colors"]["Background"]["Highlight"], "background", true);
			cssSubMenus += ((backColor !== null) ? backColor : "");
			cssLinkItemsHover += ((highlightColor !== null) ? highlightColor : "");

			// Text formatting
			var text = SMDesigner.Helpers.GetFontCss(editors["Submenus"]["Text"]["Formatting"]);

			// Fix: Move text alignment from Link CSS to Submenu CSS, where it belongs
			if (text !== null)
			{
				if (text.indexOf("text-align") > -1)
				{
					var pos = text.indexOf("text-align");
					var align = text.substring(pos, text.indexOf(";", pos) + 1);

					cssLinks += text.replace(align, "");
					cssSubMenus += align;
				}
				else
				{
					cssLinks += text;
				}
			}

			// Wrap CSS in selectors

			var css = "";

			if (cssSubMenus !== "")
			{
				css += "div.TPLMenu li ul";
				css += "{";
				css += cssSubMenus;
				css += "}";
			}

			if (cssLinkItems !== "")
			{
				css += "div.TPLMenu li ul li";
				css += "{";
				css += cssLinkItems;
				css += "}";
			}

			if (cssLinkItemsHover !== "")
			{
				css += "div.TPLMenu li ul li:hover, div.TPLMenu li ul li.TPLMenuHover";
				css += "{";
				css += cssLinkItemsHover;
				css += "}";
			}

			if (cssLinks !== "")
			{
				css += "div.TPLMenu li ul li a";
				css += "{";
				css += cssLinks;
				css += "}";
			}

			return css;
		}

		var getPage = function()
		{
			// Dimensions
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Dimensions"]["Dimensions"]["Width"], "width");

			// Indentation
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Page"]["Indentation"]["Margin"], "margin");
			var paddingAsMargin = SMDesigner.Helpers.GetIndentationCss(editors["Page"]["Indentation"]["Padding"], "margin");

			// Borders and colors
			var backgroundColor = SMDesigner.Helpers.GetColorCss(editors["Page"]["Borders and colors"]["Background"]["Color"], "background", true);
			var border = SMDesigner.Helpers.GetBorderCss(editors["Page"]["Borders and colors"]["Border"], true);
			var shadow = SMDesigner.Helpers.GetShadowCss(editors["Page"]["Borders and colors"]["Shadow"]);

			// Text formatting
			var text = SMDesigner.Helpers.GetFontCss(editors["Page"]["Text"]["Formatting"]);
			var linkColor = SMDesigner.Helpers.GetColorCss(editors["Page"]["Text"]["Links"]["Color"], "color", true);

			// Wrap CSS in selectors

			var css = "";

			if (width !== null)
			{
				css += "div.TPLPage, html.Basic.SMPagesViewer, html.SMPagesEditor.SMPagesContentPage";
				css += "{";
				css += width;
				css += "}";

				// Undo width for Card Layout
				css += "html.SMPagesCardLayout div.TPLPage, html.Basic.SMPagesViewer.SMPagesCardLayout, html.SMPagesEditor.SMPagesContentPage.SMPagesCardLayout";
				css += "{";
				css += "width: 900px;";
				css += "}";
			}

			if (margin !== null)
			{
				css += "div.TPLContent, html.Basic.SMPagesViewer body, html.SMPagesEditor.SMPagesContentPage body";
				css += "{";
				css += margin;
				css += "}";

				// Use original margin-top/bottom in preview and editor
				css += "html.Basic.SMPagesViewer body, html.SMPagesEditor.SMPagesContentPage body";
				css += "{";
				css += "margin-top: 20px;";
				css += "margin-bottom: 20px;";
				css += "}";

				// Make sure margin does not affect Card Layout - cards have margins surrounding them
				css += "html.SMPagesCardLayout div.TPLContent, html.Basic.SMPagesViewer.SMPagesCardLayout body, html.SMPagesEditor.SMPagesContentPage.SMPagesCardLayout body";
				css += "{";
				css += "margin: 0px;";
				css += "}";
				css += "html.SMPagesCardLayout div.TPLContent";
				css += "{";
				css += "margin-top: 20px;";
				css += "}";
				css += "html.SMPagesCardLayout div.TPLFooter";
				css += "{";
				css += "margin-bottom: 20px;";
				css += "}";
			}

			if (paddingAsMargin !== null)
			{
				// Notice that we use margin on an inner container to indent content.
				// This gives us the bennefits of margin collapse. That way we do not
				// get extra spacing at the top if e.g. a headline (h1) tag is found
				// at the top of our page, since h1 tags also have margin applied.

				css += "html.Normal div.SMExtension, html.Basic.SMPagesViewer div.SMExtension";
				css += "{";
				css += paddingAsMargin; // Does not affect dialogs
				css += "}";

				// Padding in editor:
				// Using margin as padding in the editor requires a few work arounds which are found below.
				// See _BaseGeneric/page.css for more information on this technique.
				// Card Layout is not affected since margin:0px, padding-left:0px, and padding-right:0px
				// are enforced in _BaseGeneric/cards.css

				var top = SMDesigner.Helpers.GetControlValue(editors["Page"]["Indentation"]["Padding"]["Top"], true);
				var left = SMDesigner.Helpers.GetControlValue(editors["Page"]["Indentation"]["Padding"]["Left"], true);
				var right = SMDesigner.Helpers.GetControlValue(editors["Page"]["Indentation"]["Padding"]["Right"], true);
				var bottom = SMDesigner.Helpers.GetControlValue(editors["Page"]["Indentation"]["Padding"]["Bottom"], true);

				if (top !== null)
				{
					css += "html.SMPagesEditor.SMPagesContentPage body:before";
					css += "{";
					css += "margin-bottom: " + top + ";";
					css += "}";
				}
				if (bottom !== null)
				{
					css += "html.SMPagesEditor.SMPagesContentPage body:after";
					css += "{";
					css += "margin-top: " + bottom + ";";
					css += "}";
				}
				if (left !== null || right !== null)
				{
					css += "html.SMPagesEditor.SMPagesContentPage body";
					css += "{";
					css += ((left !== null) ? "padding-left: " + left + ";" : "");
					css += ((right !== null) ? "padding-right: " + right + ";" : "");
					css += "}";
				}
			}

			if (backgroundColor !== null || border !== null || shadow !== null)
			{
				css += "div.TPLContent, html.Basic.SMPagesViewer.SMPagesClassicLayout body, html.SMPagesEditor.SMPagesContentPage.SMPagesClassicLayout body";
				css += "{";
				css += ((backgroundColor !== null) ? backgroundColor : ""); // Does not affect Card Layout - background:none enforced
				css += ((border !== null) ? border : "");
				css += ((shadow !== null) ? shadow : "");
				css += "}";

				// Undo above for Card Layout
				if (border !== null || shadow !== null)
				{
					css += "html.SMPagesCardLayout div.TPLContent"; // Not necessary in Preview and Editor since only ClassicLayout is targeted for these views
					css += "{";
					css += ((border !== null) ? "border: none;" : "");
					css += ((shadow !== null) ? "box-shadow: none;" : "");
					css += "}";
				}
			}

			if (text !== null)
			{
				css += "div.TPLContent, html.Basic.SMPagesViewer.SMPagesClassicLayout body, html.SMPagesEditor.SMPagesContentPage.SMPagesClassicLayout body,";
				css += "div.TPLContent td, html.Basic.SMPagesViewer.SMPagesClassicLayout body td, html.SMPagesEditor.SMPagesContentPage.SMPagesClassicLayout body td,";
				css += "div.TPLContent legend, html.Basic.SMPagesViewer.SMPagesClassicLayout body legend, html.SMPagesEditor.SMPagesContentPage.SMPagesClassicLayout body legend";
				css += "{";
				css += text;
				css += "}";

				// Revert changes for Card Layout - floating easily breaks if font changes causes cards to change size
				var revertForCards = "";
				if (text.indexOf("font-family") > -1 && editors["Cards"]["Text"]["Formatting"]["Font"].Control.IsDirty() === false)
					revertForCards += "font-family: verdana;";
				if (text.indexOf("font-size") > -1 && editors["Cards"]["Text"]["Formatting"]["Size"].Control.IsDirty() === false)
					revertForCards += "font-size: 14px;";
				if (text.indexOf("color") > -1 && editors["Cards"]["Text"]["Formatting"]["Color"].Control.IsDirty() === false)
					revertForCards += "color: #333333;";
				if (text.indexOf("font-weight") > -1 && editors["Cards"]["Text"]["Formatting"]["Style"].Control.IsDirty() === false)
					revertForCards += "font-weight: normal;";
				if (text.indexOf("font-style") > -1 && editors["Cards"]["Text"]["Formatting"]["Style"].Control.IsDirty() === false)
					revertForCards += "font-style: normal;";
				if (text.indexOf("text-align") > -1 && editors["Cards"]["Text"]["Formatting"]["Alignment"].Control.IsDirty() === false)
					revertForCards += "text-align: left;";
				if (text.indexOf("line-height") > -1 && editors["Cards"]["Text"]["Formatting"]["Line height"].Control.IsDirty() === false)
					revertForCards += "line-height: normal;";

				if (revertForCards !== "")
				{
					css += "html.SMPagesCardLayout div.TPLContent, html.SMPagesCardLayout div.TPLContent td"; // Not necessary in Preview and Editor since only ClassicLayout is targeted for these views
					css += "{";
					css += revertForCards;
					css += "}";
				}
			}

			if (linkColor !== null)
			{
				css += "a";
				css += "{";
				css += linkColor;
				css += "}";
			}

			return css;
		}

		var getCards = function()
		{
			// Dimensions
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Cards page"]["Dimensions"]["Dimensions"]["Width"], "width");

			// Indentation
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Cards page"]["Indentation"]["Margin"], "margin");
			var paddingAsMargin = SMDesigner.Helpers.GetIndentationCss(editors["Cards page"]["Indentation"]["Padding"], "margin");

			// Borders and colors
			var bgColorTitle = SMDesigner.Helpers.GetColorCss(editors["Cards"]["Borders and colors"]["Background"]["Header and footer"], "background", true);
			var bgColorContent = SMDesigner.Helpers.GetColorCss(editors["Cards"]["Borders and colors"]["Background"]["Content"], "background", true);
			var border = SMDesigner.Helpers.GetBorderCss(editors["Cards"]["Borders and colors"]["Border"], true);
			var shadow = SMDesigner.Helpers.GetShadowCss(editors["Cards"]["Borders and colors"]["Shadow"]);

			// Text formatting
			var textContent = SMDesigner.Helpers.GetFontCss(editors["Cards"]["Text"]["Formatting"]);
			var textTitle = SMDesigner.Helpers.GetFontCss(editors["Cards"]["Text"]["Header and footer"]);
			var linkColor = SMDesigner.Helpers.GetColorCss(editors["Cards"]["Text"]["Links"]["Color"], "color", true);

			// Wrap CSS in selectors

			var css = "";

			if (width !== null)
			{
				css += "html.SMPagesCardLayout div.TPLPage, html.Basic.SMPagesViewer.SMPagesCardLayout, html.SMPagesEditor.SMPagesContentPage.SMPagesCardLayout";
				css += "{";
				css += width;
				css += "}";
			}

			if (margin !== null)
			{
				css += "html.SMPagesCardLayout div.TPLContent"; // We only want margin in ordinary design, not in editor or preview, so it's safe to use div.TPLContent for this
				css += "{";
				css += margin;
				css += "}";
			}

			if (paddingAsMargin !== null)
			{
				// Notice that we use margin on an inner container to indent content.
				// This gives us the bennefits of margin collapse. That way we do not
				// get extra spacing at the top if e.g. a headline (h1) tag is found
				// at the top of our page, since h1 tags also have margin applied.

				css += "html.SMPagesCardLayout div.TPLContent div.SMExtension"; // We only want margin in ordinary design, not in editor or preview, so it's safe to use div.TPLContent for this
				css += "{";
				css += paddingAsMargin; // Does not affect dialogs
				css += "}";
			}

			if (bgColorContent !== null || border !== null || shadow !== null)
			{
				css += "div.SMPagesCard"
				css += "{";
				css += ((bgColorContent !== null) ? bgColorContent : "");
				css += ((border !== null) ? border : "");
				css += ((shadow !== null) ? shadow : "");
				css += "}";
			}

			if (textContent !== null)
			{
				css += "div.SMPagesCard, div.SMPagesCard td"
				css += "{";
				css += textContent;
				css += "}";

				// Font formatting applied above gets inherited to card header and card footer.
				// We want to control styles for card header/footer individually, so changes are
				// reverted below to initial values.

				var undo = "";

				if (textContent.indexOf("font-family") > -1 && editors["Cards"]["Text"]["Header and footer"]["Font"].Control.IsDirty() === false)
				{
					undo += "font-family: verdana;";
				}

				if ((textContent.indexOf("font-weight") > -1 || textContent.indexOf("font-style") > -1) && editors["Cards"]["Text"]["Header and footer"]["Style"].Control.IsDirty() === false)
				{
					undo += "font-weight: bold;";
					undo += "font-style: normal;";
				}

				if (textContent.indexOf("text-align") > -1 && editors["Cards"]["Text"]["Header and footer"]["Alignment"].Control.IsDirty() === false)
				{
					undo += "text-align: left;";
				}

				if (textContent.indexOf("line-height") > -1 && editors["Cards"]["Text"]["Header and footer"]["Line height"].Control.IsDirty() === false)
				{
					undo += "line-height: normal;";
				}

				if (undo !== "")
				{
					css += "span.SMPagesCardHeader, span.SMPagesCardFooter"
					css += "{";
					css += undo;
					css += "}";
				}
			}

			if (bgColorTitle !== null || textTitle)
			{
				css += "span.SMPagesCardHeader, span.SMPagesCardFooter"
				css += "{";
				css += ((bgColorTitle !== null) ? bgColorTitle : "");
				css += ((textTitle !== null) ? textTitle : "");
				css += "}";
			}

			if (linkColor !== null)
			{
				css += "div.SMPagesCard a"
				css += "{";
				css += linkColor;
				css += "}";
			}

			return css;
		}

		var getSnippets = function()
		{
			// Positioning
			var position = SMDesigner.Helpers.GetControlValue(editors["Snippets"]["Positioning"]["Positioning"]["Position"]);
			var display = SMDesigner.Helpers.GetControlValue(editors["Snippets"]["Positioning"]["Display"]["Type"]);

			// Dimensions
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Snippets"]["Dimensions"]["Dimensions"]["Width"], "width");

			// Indentation
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Snippets"]["Indentation"]["Margin"], "margin");

			// Borders and colors
			var bgColorContent = SMDesigner.Helpers.GetColorCss(editors["Snippets"]["Borders and colors"]["Background"]["Background"], "background", true);
			var bgColorTitle = SMDesigner.Helpers.GetColorCss(editors["Snippets"]["Borders and colors"]["Background"]["Title"], "background", true);
			var border = SMDesigner.Helpers.GetBorderCss(editors["Snippets"]["Borders and colors"]["Border"], true);
			var shadow = SMDesigner.Helpers.GetShadowCss(editors["Snippets"]["Borders and colors"]["Shadow"]);

			// Text formatting
			var textContent = SMDesigner.Helpers.GetFontCss(editors["Snippets"]["Text"]["Formatting"]);
			var textTitle = SMDesigner.Helpers.GetFontCss(editors["Snippets"]["Text"]["Title"]);
			var linkColor = SMDesigner.Helpers.GetColorCss(editors["Snippets"]["Text"]["Links"]["Color"], "color", true);

			// Wrap CSS in selectors

			var css = "";

			if (display !== null || position !== null)
			{
				css += "html.SMPagesClassicLayout div.TPLSnippets";
				css += "{";
				css += ((display !== null) ? "display: " + display + ";" : "");
				css += ((position !== null) ? "float: " + position + ";" : "");
				css += "}";
			}

			if (bgColorContent !== null || border !== null || shadow !== null || width !== null || margin !== null)
			{
				css += "html.SMPagesClassicLayout div.TPLSnippet"
				css += "{";
				css += ((bgColorContent !== null) ? bgColorContent : "");
				css += ((border !== null) ? border : "");
				css += ((shadow !== null) ? shadow : "");
				css += ((width !== null) ? width : "");
				css += ((margin !== null) ? margin : "");
				css += "}";
			}

			if (bgColorTitle !== null || border !== null)
			{
				var h1Border = border;

				if (h1Border !== null)
				{
					// Remove border-radius for title element - inheritance has been configured in style.css
					if (h1Border.indexOf("border-radius") > -1)
					{
						var pos = h1Border.indexOf("border-radius");
						var borderRadius = h1Border.substring(pos, h1Border.indexOf(";", pos) + 1);
						h1Border = h1Border.replace(borderRadius, "");
					}

					// Only apply border styles to bottom
					h1Border = SMStringUtilities.ReplaceAll(h1Border, "border:", "border-bottom:");
				}

				css += "html.SMPagesClassicLayout div.TPLSnippet > h1"
				css += "{";
				css += ((bgColorTitle !== null) ? bgColorTitle : "");
				css += ((h1Border !== null) ? h1Border : "");
				css += "}";
			}

			if (textContent !== null)
			{
				css += "html.SMPagesClassicLayout div.TPLSnippet, html.SMPagesClassicLayout div.TPLSnippet td"
				css += "{";
				css += textContent;
				css += "}";
			}

			if (textTitle !== null)
			{
				css += "html.SMPagesClassicLayout div.TPLSnippet > h1"
				css += "{";
				css += textTitle;
				css += "}";
			}

			if (linkColor !== null)
			{
				css += "html.SMPagesClassicLayout div.TPLSnippet a"
				css += "{";
				css += linkColor;
				css += "}";
			}

			return css;
		}

		var getFooter = function()
		{
			// Variables holding CSS

			var cssFooter = "";
			var cssFooterEditorOnly = "";
			var cssText = "";

			// Extract CSS values

			// Width (from Page)
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Dimensions"]["Dimensions"]["Width"], "width");

			// Display
			var display = SMDesigner.Helpers.GetControlValue(editors["Footer"]["Positioning"]["Display"]["Type"]);
			cssFooter += ((display !== null) ? "display: " + display + ";" : "");

			// Indentation
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Footer"]["Indentation"]["Margin"], "margin");
			var padding = SMDesigner.Helpers.GetIndentationCss(editors["Footer"]["Indentation"]["Padding"], "padding");
			cssFooter += ((margin !== null) ? margin : "");
			cssFooter += ((padding !== null) ? padding : "");

			if (margin !== null && margin.indexOf("margin-top") > -1)
				cssFooterEditorOnly += "margin-top: 20px;";
			if (margin !== null && margin.indexOf("margin-bottom") > -1)
				cssFooterEditorOnly += "margin-bottom: 20px;";

			// Borders
			var border = SMDesigner.Helpers.GetBorderCss(editors["Footer"]["Borders and colors"]["Border"], true);
			cssFooter += ((border !== null) ? border : "");

			// Shadows
			var shadow = SMDesigner.Helpers.GetShadowCss(editors["Footer"]["Borders and colors"]["Shadow"]);
			cssFooter += ((shadow !== null) ? shadow : "");

			// Background color
			var backColor = SMDesigner.Helpers.GetColorCss(editors["Footer"]["Borders and colors"]["Background"]["Color"], "background", true);
			cssFooter += ((backColor !== null) ? backColor : "");

			// Text formatting
			var text = SMDesigner.Helpers.GetFontCss(editors["Footer"]["Text"]["Formatting"]);
			cssText += ((text !== null) ? text : "");

			// Wrap CSS in selectors

			var css = "";

			if (width !== null)
			{
				css += "html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter";
				css += "{";
				css += width;
				css += "}";
			}

			if (cssFooter !== "")
			{
				css += "html.SMPagesCustomFooter div.TPLFooter, html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter body";
				css += "{";
				css += cssFooter;
				css += "}";
			}

			if (cssFooterEditorOnly !== "")
			{
				css += "html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter body";
				css += "{";
				css += cssFooterEditorOnly;
				css += "}";
			}

			if (cssText !== "")
			{
				css += "html.SMPagesCustomFooter div.TPLFooter, html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter body,";
				css += "html.SMPagesCustomFooter div.TPLFooter td, html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter body td";
				css += "{";
				css += cssText;
				css += "}";
			}

			return css;
		}

		var getMenuPageJoin = function()
		{
			// If menu and page have no spacing between them, make sure borders and
			// border-radius between them is removed to make them join seamlessly.

			var css = "";

			var menuMarginBottomStr = editors["Menu"]["Indentation"]["Margin"]["Bottom"].Control.GetValue();
			var menuMarginBottom = ((isNaN(parseInt(menuMarginBottomStr)) === false) ? parseInt(menuMarginBottomStr) : 0);

			var pageMarginTopStr = editors["Page"]["Indentation"]["Margin"]["Top"].Control.GetValue();
			var pageMarginTop = ((isNaN(parseInt(pageMarginTopStr)) === false) ? parseInt(pageMarginTopStr) : 0);

			if (menuMarginBottom === 0 && pageMarginTop === 0)
			{
				css += "div.TPLMenu > ul";
				css += "{";
				css += "border-bottom-style: none;";
				css += "border-bottom-left-radius: 0px;";
				css += "border-bottom-right-radius: 0px;";
				css += "}";

				css += "div.TPLContent";
				css += "{";
				css += "border-top-style: none;";
				css += "border-top-left-radius: 0px;";
				css += "border-top-right-radius: 0px;";
				css += "}";

				// Undo above for pages with Card Layout

				var menuBorder = SMDesigner.Helpers.GetBorderCss(editors["Menu"]["Borders and colors"]["Border"], true);

				if (menuBorder === null) // No overrides specified - use default (see style.css)
					menuBorder = "border-bottom-left-radius: 5px;border-bottom-right-radius: 5px;";

				css += "html.SMPagesCardLayout div.TPLMenu > ul";
				css += "{";
				css += menuBorder;
				css += "}";
			}

			return css;
		}

		var getPageFooterJoin = function()
		{
			// If page and footer have no spacing between them, make sure borders and
			// border-radius between them is removed to make them join seamlessly.

			var css = "";

			var pageMarginBottomStr = editors["Page"]["Indentation"]["Margin"]["Bottom"].Control.GetValue();
			var pageMarginBottom = ((isNaN(parseInt(pageMarginBottomStr)) === false) ? parseInt(pageMarginBottomStr) : 0);

			var footerMarginTopStr = editors["Footer"]["Indentation"]["Margin"]["Top"].Control.GetValue();
			var footerMarginTop = ((isNaN(parseInt(footerMarginTopStr)) === false) ? parseInt(footerMarginTopStr) : 0);

			if (pageMarginBottom === 0 && footerMarginTop === 0 && editors["Footer"]["Positioning"]["Display"]["Type"].Control.GetValue() !== "none")
			{
				// Notice: CSS below will automatically be ignored if footer is removed from SMPages since html.SMPagesCustomFooter class will not be set

				css += "html.SMPagesCustomFooter div.TPLContent";
				css += "{";
				css += "border-bottom-style: none;";
				css += "border-bottom-left-radius: 0px;";
				css += "border-bottom-right-radius: 0px;";
				css += "}";

				css += "html.SMPagesCustomFooter div.TPLFooter";
				css += "{";
				css += "border-top-style: none;";
				css += "border-top-left-radius: 0px;";
				css += "border-top-right-radius: 0px;";
				css += "}";

				// Undo above for pages with Card Layout

				var footerBorder = SMDesigner.Helpers.GetBorderCss(editors["Footer"]["Borders and colors"]["Border"]);

				// If no border-radius overrides have been specified, then use default (see style.css)
				footerBorder = ((footerBorder !== null) ? footerBorder : "");
				footerBorder += ((footerBorder.indexOf("radius") === -1) ? "border-top-left-radius: 5px;border-top-right-radius: 5px;" : "");

				css += "html.SMPagesCustomFooter.SMPagesCardLayout div.TPLFooter";
				css += "{";
				css += footerBorder;
				css += "}";
			}

			return css;
		}

		var getControls = function()
		{
			var css = "";

			// Borders
			var border = SMDesigner.Helpers.GetBorderCss(editors["Controls"]["Borders and colors"]["Border"], true);

			// Background color
			var backColor = SMDesigner.Helpers.GetColorCss(editors["Controls"]["Borders and colors"]["Background"]["Color"], "background", true);

			// Text formatting
			var text = SMDesigner.Helpers.GetFontCss(editors["Controls"]["Text"]["Formatting"]);

			if (text !== null || border !== null)
			{
				css += "input, textarea, select"
				css += "{"
				css += ((text !== null) ? text : "");
				css += ((border !== null) ? border : "");
				css += "}"
			}

			if (backColor !== null)
			{
				css += "input, textarea, select, option"
				css += "{"
				css += backColor;
				css += "}"
			}

			return css;
		}

		var getSystemLinks = function()
		{
			var links = SMDesigner.Helpers.GetFontCss(editors["System links"]["Text"]["Formatting"]);

			var css = "";

			if (links !== null)
			{
				css += "div.TPLLinks, div.TPLLinks a";
				css += "{";
				css += links;
				css += "}";
			}

			return css;
		}

		var getCustomCss = function()
		{
			var customCss = SMDesigner.Helpers.GetControlValue(editors["Advanced"]["Custom CSS"]["Custom CSS"]["CSS"]);

			if (customCss !== null)
				return customCss;

			return "";
		}

		// Generate combined overrides and return result

		var all = "";

		all += getBackground();
		all += getHeader();
		all += getMenu();
		all += getSubMenus();
		all += getPage();
		all += getCards();
		all += getSnippets();
		all += getFooter();
		all += getMenuPageJoin();
		all += getPageFooterJoin();
		all += getControls();
		all += getSystemLinks();
		all += getTopMiddleBottom("Top");
		all += getTopMiddleBottom("Middle");
		all += getTopMiddleBottom("Bottom");
		all += getCustomCss(); // MUST be last! Incomplete CSS may otherwise invalidate CSS that follows

		return all;
	}
})
