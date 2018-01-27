/* TODO
 - Allow styling of active link in menu
 - Allow styling of contact forms
 - Allow hiding Header/Footer/Top/Bottom on Tablet and/or Mobile
*/

// NOTICE: Regarding the use of GetComputedStyle(..):
// Adjusting controls or behaviour using the results of GetComputedStyle(..)
// is very bad practice, and is NOT guaranteed to work as expected - it is not supported!
// Problem 1:
//   Using GetComputedStyle in e.g. GetEditors(..), GetSelectors(..), or GetExclusion(..)
//   will work on the initial load, but if the design is reverted using e.g. the Reset button,
//   controls and selectable areas will not change to match the new design. In most cases
//   this is not a problem if the design ships with override.defaults.js that resets the
//   design to something that is optimized for the current state of the Designer.
//   See SMDesigner > reset() for additional details.
// Problem 2:
//   Using GetComputedStyle in e.g. GetCss(..) will not work as expected. If GetComputedStyle
//   is used on an element to determine e.g. "position", and that particular style is changed for
//   the element, then GetComputedStyle will NOT return the new value since it is not yet applied,
//   and there is absolutely nothing that can be done about it. Removing any previous styling
//   is not guaranteed to produce the expected result since the original style may also produce
//   a result different from the new value.
//   However, if GetComputedStyle is used to query a style of an element that cannot be changed
//   using the designer (e.g. position:fixed), then the chance of problems is very small since
//   the problem can only be triggered by manipulating styling under Advanced, which may be acceptable.

// Make sure template designer definitions are identical:
// for dir in templates/*/ ; do md5 $dir/designer.js ; done

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
		SMDesigner.Helpers.DefaultDimensionUnit = "em";

		var gfxSupported = SMDesigner.Graphics.IsBrowserSupported();

		var cfg =
		{
			"General":
			{
				"Text":
				{
					"Base text size":
					{
						"Base text size": SMDesigner.Helpers.GetDimensionControl(14, "px"),
					},
					"Device text size":
					{
						"Mobile": SMDesigner.Helpers.GetDimensionControl(100, "%"),					// <= 500px
						"Tablet": SMDesigner.Helpers.GetDimensionControl(100, "%"),					// <= 900px
						"Desktop": SMDesigner.Helpers.GetDimensionControl(100, "%"),				//  > 900px
						"Desktop 1280 HD": SMDesigner.Helpers.GetDimensionControl(null, "%"),		// >= 1280px
						"Desktop 1600 UXGA": SMDesigner.Helpers.GetDimensionControl(null, "%"),		// >= 1600px
						"Desktop 1980 FHD": SMDesigner.Helpers.GetDimensionControl(null, "%"),		// ... etc.
						"Desktop 2560 UWHD": SMDesigner.Helpers.GetDimensionControl(null, "%"),
						"Desktop 2800 QSXGA": SMDesigner.Helpers.GetDimensionControl(null, "%"),
						"Desktop 2560 UWHD": SMDesigner.Helpers.GetDimensionControl(null, "%"),
						"Desktop 3440 UWQHD": SMDesigner.Helpers.GetDimensionControl(null, "%"),
						"Desktop 4096 4K": SMDesigner.Helpers.GetDimensionControl(null, "%")
					}
				}
			},

			"Background":
			{
				"Background":
				{
					"Background":
					{
						"Color": { Type: "Color" },
						"Image (all pages)": { Type: "File", Value: SMEnvironment.GetFilesDirectory() + "/images/backgrounds/Sunrise.jpg", Items: [ { Value: "generated-bg.jpg" } ] }
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
									editors["Background"]["Background"]["Background"]["Image (all pages)"].Control.SetValue("generated-bg.jpg");
									editors["Background"]["Background"]["Generator"]["CacheKey"].Control.SetValue(SMRandom.CreateGuid());

									var parentWindow = window.opener || window.top;

									SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
									{
										if (parentWindow.document.querySelector("html.SMPagesPageId" + page.Id) !== null && editors["Background"]["Background"]["Background"]["Image (current page);" + page.Id].Control.GetValue() !== "generated-bg.jpg")
										{
											// An image may be selected in "Image (current page)" which prevents generated image from
											// being shown since it is set in "Image (all pages)" - clear background image for current page.
											editors["Background"]["Background"]["Background"]["Image (current page);" + page.Id].Control.SetValue("");
										}
									});

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
						"Type": { Type: "Selector", Items: [{ Title : "Normal", Value: "block"}, { Title : "Fit to content", Value: "inline-block"}, { Title : "Stretch across page", Value: "stretch"}, { Title : "Hidden", Value: "none"}], Value: "block" }
					}
				},
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 2.2, "em", "#F0F0F0", "Normal", "Left")
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
						"Width": SMDesigner.Helpers.GetDimensionControl(null, "px"),
						"Height": SMDesigner.Helpers.GetDimensionControl(null, "px")
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(0.7, null, null, null, "em"),
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
						"Type": { Type: "Selector", Items: [{ Title : "Normal", Value: "block"}, { Title : "Fit to content", Value: "inline-block"}, { Title : "Stretch across page", Value: "stretch"}, { Title : "Stretch across page (top)", Value: "stretchtop"} /*, { Title : "Hidden", Value: "none"}*/], Value: "block" }
					}
				},
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 1.2, "em", "#333333", "Bold", "Left", 3, "em"),
					"Shadow": SMDesigner.Helpers.GetTextShadowControls()
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
						"Width": SMDesigner.Helpers.GetDimensionControl(null, "px"),
						"Height (line height)": SMDesigner.Helpers.GetDimensionControl(3, "em", 1.2, 20, 0.1)
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(1.25, null, null, null, "em"),
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

			"Submenus":
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 0.85, "em", "#333333", "Bold", "Left", 2, "em"),
					"Shadow": SMDesigner.Helpers.GetTextShadowControls()
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

			"Headings":
			{
				"Heading 1 (h1)":
				{
					"Formatting": SMDesigner.Helpers.GetFontHeadingControls("Verdana", 1.5, "em", null, "Bold", "Left", null, null, null, null, 0.7, "em", 0.7, "em"),
					"Shadow": SMDesigner.Helpers.GetTextShadowControls()
				},
				"Heading 2 (h2)":
				{
					"Formatting": SMDesigner.Helpers.GetFontHeadingControls("Verdana", 1.25, "em", null, "Bold", "Left", null, null, null, null, 1, "em", 1, "em"),
					"Shadow": SMDesigner.Helpers.GetTextShadowControls()
				},
				"Heading 3 (h3)":
				{
					"Formatting": SMDesigner.Helpers.GetFontHeadingControls("Verdana", 1, "em", null, "Bold", "Left", null, null, null, null, 1.2, "em", 1.2, "em"),
					"Shadow": SMDesigner.Helpers.GetTextShadowControls()
				},
				"Heading 4 (h4)":
				{
					"Formatting": SMDesigner.Helpers.GetFontHeadingControls("Verdana", 1, "em", null, "Bold", "Left", null, null, null, null, 1.2, "em", 1.2, "em"),
					"Shadow": SMDesigner.Helpers.GetTextShadowControls()
				},
				"Heading 5 (h5)":
				{
					"Formatting": SMDesigner.Helpers.GetFontHeadingControls("Verdana", 1, "em", null, "Bold", "Left", null, null, null, null, 1.2, "em", 1.2, "em"),
					"Shadow": SMDesigner.Helpers.GetTextShadowControls()
				},
				"Heading 6 (h6)":
				{
					"Formatting": SMDesigner.Helpers.GetFontHeadingControls("Verdana", 1, "em", null, "Bold", "Left", null, null, null, null, 1.2, "em", 1.2, "em"),
					"Shadow": SMDesigner.Helpers.GetTextShadowControls()
				}
			},

			"Page":
			{
				"Header image": { "Header image": {} }, // Populated further down
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 1, "em", "#333333", "Normal", "Left"),
					"Links":
					{
						"Color": { Type: "Color", Value: "#106FC7" }
					}
				},
				"Borders and colors":
				{
					"Background":
					{
						"Color (all pages)": { Type: "Color", Value: { Red: 255, Green: 255, Blue: 255, Alpha: 0.6 } }
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
					"Margin": SMDesigner.Helpers.GetIndentationControls(1.5, null, null, null, "em"),
					"Padding": SMDesigner.Helpers.GetIndentationControls(1.5, 1.5, 1.5, 1.5, "em")
				},
				"Content":
				{
					"Information":
					{
						"": { Type: "Label", Value: "Add, remove, or edit pages from main menu:<br>Content > Pages" }
					}
				}
			},

			"Extension page":
			{
				// TODO: Allow styling of controls (input, dropdown etc.)

				"Text":
				{
					"Formatting": SMDesigner.Helpers.SetAllAllowEmpty(SMDesigner.Helpers.GetFontControls()),
					"Links":
					{
						"Color": { Type: "Color" }
					}
				},
				"Background":
				{
					"Background":
					{
						"Color": { Type: "Color" }
					}
				},
				"Dimensions":
				{
					"Dimensions":
					{
						"Width": SMDesigner.Helpers.GetDimensionControl(null, "px")
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(),
					"Padding": SMDesigner.Helpers.GetIndentationControls()
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
					"Margin": SMDesigner.Helpers.GetIndentationControls(1.5, null, null, null, "em"),
					"Padding": SMDesigner.Helpers.GetIndentationControls()
				}
			},

			"Cards":
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 1, "em", "#333333", "Normal", "Left"),
					"Header and footer": SMDesigner.Helpers.GetFontControls("Verdana", 1.1, "em", "#FFFFFF", "Bold", "Left"),
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

			"Fluid Grid Cards": // Default values are defined in extensions/SMPages/editor.css
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.SetAllAllowEmpty(SMDesigner.Helpers.GetFontControls()),
					"Header and footer": SMDesigner.Helpers.SetAllAllowEmpty(SMDesigner.Helpers.GetFontControls()),
					"Links":
					{
						"Color": { Type: "Color" }
					}
				},
				"Borders and colors":
				{
					"Background":
					{
						"Content": { Type: "Color", Value: { Red: 255, Green: 255, Blue: 255, Alpha: 0.6 } },
						"Header and footer": { Type: "Color", Value: "#000000" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls(),
					"Shadow": SMDesigner.Helpers.GetShadowControls()
				},
				"Indentation":
				{
					"Indentation":
					{
						"Spacing": SMDesigner.Helpers.GetDimensionControl(2, "em", 0, 20, 0.1),
						"Padding": SMDesigner.Helpers.GetDimensionControl(0.75, "em", 0, 20, 0.1),
						"Margin": SMDesigner.Helpers.GetDimensionControl(2, "em", 0, 20, 0.1),
						"": { Type: "Label", Value: "WARNING: A Margin different from Spacing will not work on IE9 and older" }
					}
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
					"Margin": SMDesigner.Helpers.GetIndentationControls(1.5, 1.5, 1.5, 1.5, "em"),
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
						"Type": { Type: "Selector", Items: [{ Title : "Normal", Value: "block"}, { Title : "Fit to content", Value: "inline-block"}, { Title : "Stretch across page", Value: "stretch"}, { Title : "Hidden", Value: "none"}], Value: "block" }
					}
				},
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 1, "em", "#EFEFEF", "Normal", "Left")
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
				"Dimensions":
				{
					"Dimensions":
					{
						"Width": SMDesigner.Helpers.GetDimensionControl(null, "px"),
						"Height": SMDesigner.Helpers.GetDimensionControl(null, "px")
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(null, null, null, 2.5, "em"),
					"Padding": SMDesigner.Helpers.GetIndentationControls(null, 1.5, 1.5, null, "em")
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
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 1, "em", "#333333", "Normal", "Left")
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

			"Action Buttons": // Default values are defined in extensions/SMPages/editor.css
			{
				"Primary":
				{
					"Text formatting": SMDesigner.Helpers.SetAllAllowEmpty(SMDesigner.Helpers.GetFontControls(null, 1, "em", "#F5F5F5")),
					"Background":
					{
						"Color": { Type: "Color", Value: "#3775B2" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls("#35518D", "solid", 1)
				},
				"Secondary":
				{
					"Text formatting": SMDesigner.Helpers.SetAllAllowEmpty(SMDesigner.Helpers.GetFontControls(null, 1, "em", "#F5F5F5")),
					"Background":
					{
						"Color": { Type: "Color", Value: "#5BA130" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls("#4E8D21", "solid", 1)
				}
			},

			"System links":
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls("Verdana", 0.7, "em", "#AEAEAE", "Normal", "Left")
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
						"Height": SMDesigner.Helpers.GetDimensionControl(null, "px")
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
						"Height": SMDesigner.Helpers.GetDimensionControl(null, "px")
					}
				}
			},

			"Advanced":
			{
				"Custom CSS":
				{
					"Custom CSS":
					{
						"CSS": { Type: "Text", MultiLine: true },
						"":
						{
							Type: "Button", Value: "Open editor", NoSave: true, OnClick: function(sender, eArgs)
							{
								var parentWindow = window.opener || window.top;

								var editors = eArgs.Editors;
								var txtCss = editors["Advanced"]["Custom CSS"]["Custom CSS"]["CSS"].Control;

								// Create layer that prevents user from using Designer while editor dialog is open.
								// This also works around a bug in jQuery UI causing dialogs to reload if multiple
								// dialogs are moved around. This could cause lose of changes if not handled.
								// NOTICE: This is a bit ugly since we need to know the z-index of jQuery dialogs (z-index:100).

								var layer = parentWindow.document.createElement("div");
								layer.style.cssText = "position: fixed; left: 0; top: 0; right: 0; bottom: 0; z-index: 100;";
								layer.onclick = function(e)
								{
									var ev = e || window.event;

									if (ev.StopPropagation)
										ev.StopPropagation();
									ev.cancelBubble = true;

									return false;
								}
								parentWindow.document.body.appendChild(layer);

								// Define callback used to update value in Designer

								parentWindow.updateAdvancedCss = function(val)
								{
									txtCss.SetValue(val);
								}

								// Create editor in dialog

								var w = new parentWindow.SMWindow("AdvancedCss");
								w.SetContent("<style> html, body { width: 100%; height: 100%; margin: 0px; padding: 0px; overflow: hidden; } textarea:focus { outline: none; } textarea { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; border: none;" + ((SMBrowser.GetBrowser() !== "MSIE" || SMBrowser.GetVersion() >= 9) ? " white-space: pre;" : "") + " word-wrap: normal; overflow-x: scroll; font-family: verdana; font-size: 12px; color: #333333; resize: none; } </style><textarea onkeyup='(window.opener || window.top).updateAdvancedCss(this.value)'>" + txtCss.GetValue() + "</textarea>");
								w.SetCenterWindow(true);
								w.SetSize(600, 380);
								w.SetOnCloseCallback(function()
								{
									// IE: Textarea does not always become editable again when modified
									// using dialog. IE users will have to temporariy switch to another section
									// and then back to Advanced to work around this bug, or simply always
									// use the editor within the dialog instead. Tough luck being an IE user.

									layer.parentElement.removeChild(layer);
								});
								w.Show();
							}
						}
					}
				}
			}
		};

		var parentWindow = window.opener || window.top;

		// Hide options not useful for designs with positioned elements (hardcoded using CSS, possibly under Advanced - Hyperspace support)
		if (SMDom.GetComputedStyle(parentWindow.document.getElementById("TPLHeader"), "position") === "fixed")
		{
			cfg["Header"]["Positioning"]["Positioning"]["Position"].Hidden = true;
			//cfg["Header"]["Positioning"]["Display"]["Type"].Hidden = true; // DISABLED - allow user to stretch header across page
		}
		if (SMDom.GetComputedStyle(parentWindow.document.getElementById("TPLMenu"), "position") === "fixed")
		{
			cfg["Menu"]["Positioning"]["Positioning"]["Position"].Hidden = true;
			cfg["Menu"]["Positioning"]["Display"]["Type"].Hidden = true;
		}

		// Cards (obsolete - use Fluid Grid Cards if possible)

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

		// Fluid Grid Cards

		// Show warning if Margin is changed to a value different from Spacing which will not work on IE9 and older
		var fgcChangeEv = cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Margin"].OnChange; // Preserve handler responsible for keeping input and slider in sync.
		cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Margin"].OnChange = function(sender, value)
		{
			fgcChangeEv(sender, value);

			if (value + cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Margin"].Selector.Control.GetValue() !== cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Spacing"].Control.GetValue() + cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Spacing"].Selector.Control.GetValue())
			{
				cfg["Fluid Grid Cards"]["Indentation"]["Indentation"][""].Control.GetElement().style.display = "";
			}
			else
			{
				cfg["Fluid Grid Cards"]["Indentation"]["Indentation"][""].Control.GetElement().style.display = "none";
			}
		};

		// Hide CSS unit selector for Margin
		cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Margin"].Selector.Hidden = true;
		cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Spacing"].Selector.OnChange = function(sender, value)
		{
			// Always keep CSS units in sync, even if user assigns a different value to Margin which will disable
			// control synchronization from Spacing to Margin. Currently we do not support different CSS units in getFluidGridCards().
			cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Margin"].Selector.Control.SetValue(cfg["Fluid Grid Cards"]["Indentation"]["Indentation"]["Spacing"].Selector.Control.GetValue());
		};

		// Snippets (widgets)

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

		// System links

		cfg["System links"]["Text"]["Formatting"]["Alignment"].Hidden = true;
		cfg["System links"]["Text"]["Formatting"]["Line height"].Hidden = true;

		// Page specific options

		var curPageId = null;

		SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
		{
			var id = page.Id;
			curPageId = ((curPageId !== null) ? curPageId : ((parentWindow.document.querySelector("html.SMPagesPageId" + id) !== null) ? id : null));

			// Allow user to set a header image on individual pages

			cfg["Page"]["Header image"]["Header image"]["Image;" + id] = { Type: "File" };
			cfg["Page"]["Header image"]["Header image"]["Stretch across page;" + id] = { Type: "Selector", Value: "No", Items: [{Title: "Yes", Value: "Yes"}, {Title: "No", Value: "No"}] };
			cfg["Page"]["Header image"]["Header image"]["Position;" + id] = { Type: "Selector", Value: "Behind", Items: [{Title: "Behind content", Value: "Behind"}, {Title: "Above content", Value: "Above"}] };
			cfg["Page"]["Header image"]["Header image"]["Height;" + id] = SMDesigner.Helpers.GetDimensionControl(50, "px");
			cfg["Page"]["Header image"]["Header image"]["Indent content from top;" + id] = SMDesigner.Helpers.GetDimensionControl(null, "px");
			cfg["Page"]["Header image"]["Header image"][";" + id] = { Type: "Label", Value: "Notice: Header images are applied to individual pages." };

			// Allow user to set a background image on individual pages

			cfg["Background"]["Background"]["Background"]["Image (current page);" + id] = { Type: "File", Items: [ { Value: "none" }, { Value: "generated-bg.jpg" } ] };

			// Allow user to set a page specific background color

			// Create page specific background color controls.
			// These controls will be linked to (synchronized with) the background color control for
			// "all pages" to make sure they assume the same value by default. That allow the user to clear
			// the background (make it fully transparent) by simply removing the color value.
			// Unfortunately it also introduces a problem; page specific color controls will
			// "come and go" as pages are created and removed.
			// Controls that emerge after the design has been created cannot assume a default value
			// different from the one selected by the user in "all pages", since it would cause the
			// background color to change when the Designer is launched.
			// Example:
			//   All pages: red (changed by user)
			//   Current page: blue (default value set in definition)
			// Since the page specific color is not saved to CSS but is applied runtime when the
			// Designer is launched, the background color will suddenly switch and confuse the user.
			// The work around is to assign a "dummy value" to the page specific color controls.
			// If this is not changed by the user, we copy the background color from "all pages" once the
			// template overrides have been loaded during OnDataLoaded.
			// This dummy value must be one the user will never choose, since choosing it
			// will make the control non-dirty, causing the value to not be saved and used.
			// The only unfortunate side effect is that these controls will become dirty once
			// the value is copied from "all pages", even though the user never changed the values,
			// and so the values will be saved to override.js and override.css, even though they
			// serve no purpose if identical to the color value in "all pages".
			// Of course avoiding the synchronization mechanism between "all pages" and the
			// page specific color controls would also be an option, and simply have the
			// page specific color controls assume an empty value by default. But then the
			// user would only be able to clear the background color by assigning a value
			// such as rgba(0,0,0,0) - full transparency. But that behaviour is different
			// from all the other background color controls - consistency is more important.

			cfg["Page"]["Borders and colors"]["Background"]["Color (current page);" + id] = SMCore.Clone(cfg["Page"]["Borders and colors"]["Background"]["Color (all pages)"]);
			cfg["Page"]["Borders and colors"]["Background"]["Color (current page);" + id].Value = "rgba(0, 0, 0, 0.11111)"; // Dummy value - replaced during OnDataLoaded
		});

		// Hide page specific controls not related to page currently loaded

		var props = cfg["Page"]["Header image"]["Header image"];
		for (prop in props)
		{
			if (prop.split(";")[1] !== curPageId)
				props[prop].Hidden = true;
		}

		props = cfg["Background"]["Background"]["Background"];
		for (prop in props)
		{
			if (prop.indexOf(";") === -1)
				continue;

			if (prop.split(";")[1] !== curPageId)
				props[prop].Hidden = true;
		}

		props = cfg["Page"]["Borders and colors"]["Background"];
		for (prop in props)
		{
			if (prop.indexOf(";") === -1)
				continue;

			if (prop.split(";")[1] !== curPageId)
				props[prop].Hidden = true;
		}

		// Add custom elements defined in template

		var customs = parentWindow.document.querySelectorAll(".SMDesignerElement[data-id]");
		for (var i = 0 ; i < customs.length ; i++)
		{
			cfg["$ " + customs[i].getAttribute("data-id")] =
			{
				"Text":
				{
					"Formatting": SMDesigner.Helpers.GetFontControls(),
					"Links":
					{
						"Color": { Type: "Color" }
					}
				},
				"Borders and colors":
				{
					"Background":
					{
						"Color": { Type: "Color" },
						"Image": { Type: "File" }
					},
					"Border": SMDesigner.Helpers.GetBorderControls(),
					"Shadow": SMDesigner.Helpers.GetShadowControls()
				},
				"Dimensions":
				{
					"Dimensions":
					{
						"Width": SMDesigner.Helpers.GetDimensionControl(null, "px"),
						"Height": SMDesigner.Helpers.GetDimensionControl(null, "px"),
						"Type": { Type: "Selector", Items: [{ Title : "", Value: ""}, { Title : "Block", Value: "block"}, { Title : "Fit to content", Value: "inline-block"}, { Title : "Hidden", Value: "none"}], Value: "" }
					}
				},
				"Indentation":
				{
					"Margin": SMDesigner.Helpers.GetIndentationControls(),
					"Padding": SMDesigner.Helpers.GetIndentationControls()
				},
				"Styling rules":
				{
					"Restrict styling":
					{
						"Apply styling to page (1)": { Type: "Page" },
						"Apply styling to page (2)": { Type: "Page" },
						"Apply styling to page (3)": { Type: "Page" },
						"Apply styling to page (4)": { Type: "Page" },
						"Apply styling to page (5)": { Type: "Page" },
						"Hide on all other pages": { Type: "Selector", Value: "Yes", Items: [{Title: "Yes", Value: "Yes"}, {Title: "No", Value: "No"}] }
					}
				}
			}

			// Make sure we can save CSS generated for page specific custom elements.
			// Such elements will not have edit controls loaded when designing pages without the elements,
			// but we still need to preserve the CSS that was generated for them.
			// Both styling and CSS is available for custom elements not found on the page
			// through the eventArgs.UnrecognizedValues property.
			// <div class="SMDesignerElement" data-id="My custom element" data-preserve="true">Hello world</div>

			if (customs[i].getAttribute("data-preserve") === "true")
			{
				cfg["$ " + customs[i].getAttribute("data-id")]["PreservedCss"] =
				{
					"PreservedCss": { "PreservedCss": { Type: "Input", Hidden: true } }
				}
			}
		}

		return cfg;
	},

	GetSelectors: function(eventArgs)
	{
		// Return selectors which turn elements into selectable areas (point and click)

		var parentWindow = window.opener || window.top;

		var selectors =
		{
			"Background":					"html",
			"Header":						"div.TPLHeader",
			"Menu":							"div.TPLMenu > ul",
			"Submenus":						"div.TPLMenu li ul",
			"Headings;Heading 1 (h1)":		"div.TPLPage h1",
			"Headings;Heading 2 (h2)":		"div.TPLPage h2",
			"Headings;Heading 3 (h3)":		"div.TPLPage h3",
			"Headings;Heading 4 (h4)":		"div.TPLPage h4",
			"Headings;Heading 5 (h5)":		"div.TPLPage h5",
			"Headings;Heading 6 (h6)":		"div.TPLPage h6",
			"Page":							"div.TPLContent", //"html.SMPagesViewer div.TPLContent",
			//"Extension page":				"html.SMIntegratedExtension div.TPLContent",
			"Cards page":					"html.SMPagesCardLayout div.TPLContent", // Obsolete - use Fluid Grid Cards instead
			"Cards":						"div.SMPagesCard", // Obsolete - use Fluid Grid Cards instead
			"Fluid Grid Cards":				"div.SMPagesTable.SMPagesGridCards div.SMPagesTableCell",
			"Snippets":						"div.TPLSnippets",
			"Footer":						"div.TPLFooter",
			"Controls":						"input, textarea, select",
			"Action Buttons;Primary":		"a.SMPagesActionButtonPrimary",
			"Action Buttons;Secondary":		"a.SMPagesActionButtonSecondary",
			"System links":					"div.TPLLinks",
			"Top":							"div.TPLTop",		// if (parentWindow.document.querySelector("div.TPLTop").offsetHeight > 0)
			"Bottom":						"div.TPLBottom"		// if (parentWindow.document.querySelector("div.TPLBottom").offsetHeight > 0)
		}

		/* Make TPLMiddle selectable over ordinary background if changes have been applied */

		var middleSelectable = false;

		for (var accordionSection in editors["Middle"])
		{
			for (var headlineSection in editors["Middle"][accordionSection])
			{
				for (var prop in editors["Middle"][accordionSection][headlineSection])
				{
					var property = editors["Middle"][accordionSection][headlineSection][prop];

					if (property.NoSave === true)
						continue;

					if (property.Control.IsDirty() === true || (property.Selector && property.Selector.Control.IsDirty() === true))
					{
						middleSelectable = true;
						break;
					}
				}

				if (middleSelectable === true)
					break;
			}

			if (middleSelectable === true)
				break;
		}

		if (middleSelectable === true)
			selectors["Middle"] = "div.TPLMiddle";

		// Add custom elements defined in template

		var customs = parentWindow.document.querySelectorAll(".SMDesignerElement[data-id]");
		for (var i = 0 ; i < customs.length ; i++)
		{
			selectors["$ " + customs[i].getAttribute("data-id")] = ".SMDesignerElement[data-id='" + customs[i].getAttribute("data-id") + "']";
		}

		return selectors;
	},

	GetDefaultSection: function(eventArgs)
	{
		return "Background";
	},

	GetExclusion: function(eventArgs)
	{
		return []; // Do not exclude anything, we want the user to see all the features, even when not available

		// Exclude (hide) sections not relevant

		/*var parentWindow = window.opener || window.top;
		var exclude = [];

		if (parentWindow.document.querySelector("html.SMPagesCustomHeader") === null)
			exclude.push("Header");

		if (parentWindow.document.querySelector("html.SMPagesCardLayout") !== null)
		{
			exclude.push("Page");
			exclude.push("Extension page");
		}
		else
		{
			exclude.push("Cards page");
			exclude.push("Cards");
		}

		if (parentWindow.document.querySelector("da.SMPagesActionButtonPrimary") === null && parentWindow.document.querySelector("da.SMPagesActionButtonSecondary") === null)
			exclude.push("Action Buttons");

		if (parentWindow.document.querySelector("div.SMPagesTable.SMPagesGridCards div.SMPagesTableCell") === null)
			exclude.push("Fluid Grid Cards");

		if (parentWindow.document.querySelector("div.TPLSnippet") === null)
			exclude.push("Snippets");

		if (parentWindow.document.querySelector("html.SMPagesCustomFooter") === null)
			exclude.push("Footer");

		if (SMDom.GetComputedStyle(parentWindow.document.querySelector("div.TPLTop"), "display") === "none")
			exclude.push("Top");
		if (SMDom.GetComputedStyle(parentWindow.document.querySelector("div.TPLBottom"), "display") === "none")
			exclude.push("Bottom");

		return exclude;*/
	},

	OnEditorsCreated: function(eventArgs)
	{
		var editors = eventArgs.Editors;

		// Configure control synchronization.
		// Notice that control synchronization takes place when the user interacts with controls, not if controls are updated programmatically!

		// Header and Menu position
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.Bidirectional, editors["Header"]["Positioning"]["Positioning"]["Position"], editors["Menu"]["Positioning"]["Positioning"]["Position"], {"left": "right", "right": "left"});

		// Menu and submenus
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull, editors["Menu"]["Borders and colors"]["Background"]["Color"], editors["Submenus"]["Borders and colors"]["Background"]["Color"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull, editors["Menu"]["Borders and colors"]["Background"]["Highlight"], editors["Submenus"]["Borders and colors"]["Background"]["Highlight"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull, editors["Menu"]["Text"]["Formatting"]["Font"], editors["Submenus"]["Text"]["Formatting"]["Font"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull, editors["Menu"]["Text"]["Formatting"]["Color"], editors["Submenus"]["Text"]["Formatting"]["Color"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull, editors["Menu"]["Text"]["Formatting"]["Style"], editors["Submenus"]["Text"]["Formatting"]["Style"]);
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.Bidirectional, editors["Menu"]["Dimensions"]["Dimensions"]["Height (line height)"], editors["Menu"]["Text"]["Formatting"]["Line height"]);

		// Page and Cards - link color
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull, editors["Page"]["Text"]["Links"]["Color"], editors["Cards"]["Text"]["Links"]["Color"]);

		// Page specific background color in content area
		SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
		{
			SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEquals, editors["Page"]["Borders and colors"]["Background"]["Color (all pages)"], editors["Page"]["Borders and colors"]["Background"]["Color (current page);" + page.Id]);
		});

		// Fluid Grid Cards
		SMDesigner.Helpers.LinkControls(SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull, editors["Fluid Grid Cards"]["Indentation"]["Indentation"]["Spacing"], editors["Fluid Grid Cards"]["Indentation"]["Indentation"]["Margin"]);
		editors["Fluid Grid Cards"]["Indentation"]["Indentation"][""].Control.GetElement().style.display = "none"; // Legacy IE warning
	},

	OnDataLoaded: function(eventArgs)
	{
		// Pages added "later" (after the design has been defined) will not have a page specific background color set,
		// since the color from "all pages" have not been synchronized to controls that did not exist earlier (obviously).

		// If any of the page specific background color controls are non-dirty, it means that a value has not been
		// selected, saved, and now loaded during OnDataLoaded. It is therefore safe to assume that we can set the
		// color value from "all pages" to these controls.
		// Unfortunately this has the side effect that the controls become dirty (even if "all pages" has not been changed),
		// and is therefore saved to override.js and override.css - but that's acceptable.

		// To make sure the user can set the value originally assigned in the definition, the controls will
		// have to assume a dummy value that the user will most likely never use. We have used rgba(0, 0, 0, 0.11111)
		// which is not selectable using the picker control.

		var cfg = eventArgs.Editors;
		var ctlBgColorAllPages = cfg["Page"]["Borders and colors"]["Background"]["Color (all pages)"].Control;

		SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
		{
			var ctlBgColorCurPage = cfg["Page"]["Borders and colors"]["Background"]["Color (current page);" + page.Id].Control;

			if (ctlBgColorCurPage.IsDirty() === false)
				ctlBgColorCurPage.SetValue(ctlBgColorAllPages.GetValue());
		});

		// Preserve styling for page specific custom elements that are not available on current page.
		// <div class="SMDesignerElement" data-id="My custom element" data-preserve="true">Hello world</div>

		var values = eventArgs.UnrecognizedValues;

		for (var editorSection in values)
		{
			if (editorSection.indexOf("$ ") === 0 && values[editorSection]["PreservedCss"] !== undefined)
			{
				for (var accordionSection in values[editorSection])
				{
					for (var headlineSection in values[editorSection][accordionSection])
					{
						for (var prop in values[editorSection][accordionSection][headlineSection])
						{
							values[editorSection][accordionSection][headlineSection][prop].Preserve = true;
						}
					}
				}
			}
		}
	},

	OnReset: function(eventArgs)
	{
		// Remove custom and template specific properties registered on controls.
		// These are used to preserve various kinds of state.

		var values = eventArgs.Editors;
		for (var editorSection in values)
		{
			for (var accordionSection in values[editorSection])
			{
				for (var headlineSection in values[editorSection][accordionSection])
				{
					for (var prop in values[editorSection][accordionSection][headlineSection])
					{
						for (var custControlProp in values[editorSection][accordionSection][headlineSection][prop].Control)
						{
							if (custControlProp.indexOf("___") === 0) // E.g. ___isFloated, ___doNotResetDisplayType, etc.
							{
								delete values[editorSection][accordionSection][headlineSection][prop].Control[custControlProp];
							}
						}
					}
				}
			}
		}
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

		var generatedBgUsed = false;
		SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
		{
			if (parentWindow.document.querySelector("html.SMPagesPageId" + page.Id) !== null) // Check if given page is current page
			{
				if (editors["Background"]["Background"]["Background"]["Image (current page);" + page.Id].Control.GetValue() === "generated-bg.jpg")
					generatedBgUsed = true;

				return false; // Break loop
			}
		});

		if (editors["Background"]["Background"]["Generator"][""].Control.IsDirty() === true && (editors["Background"]["Background"]["Background"]["Image (all pages)"].Control.GetValue() === "generated-bg.jpg" || generatedBgUsed === true))
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

		var getGeneral = function()
		{
			var css = "";

			var txtBaseSize = SMDesigner.Helpers.GetDimensionCss(editors["General"]["Text"]["Base text size"]["Base text size"], "font-size");
			var txtDesktopSize = SMDesigner.Helpers.GetDimensionCss(editors["General"]["Text"]["Device text size"]["Desktop"], "font-size");
			var txtTabletSize = SMDesigner.Helpers.GetDimensionCss(editors["General"]["Text"]["Device text size"]["Tablet"], "font-size");
			var txtMobileSize = SMDesigner.Helpers.GetDimensionCss(editors["General"]["Text"]["Device text size"]["Mobile"], "font-size");

			if (txtBaseSize !== null)
			{
				css += "html";
				css += "{";
				css += txtBaseSize;
				css += "}";

				css += "body";
				css += "{";
				css += "font-size: 100%;";
				css += "}";
			}

			if (txtDesktopSize !== null)
			{
				/*css += "@media (min-width: 900px)"; // Does not work on IE8 - replaced by the use of html.TplOnDesktop below
				css += "{";
				css += "body{" + txtDesktopSize + "}";
				css += "}";*/

				css += "html.TplOnDesktop body, html.SMPagesEditor body {" + txtDesktopSize + "}";
			}

			// High-res desktop break points

			var curSet = null;

			for (var prop in editors["General"]["Text"]["Device text size"])
			{
				if (prop.indexOf("Desktop ") === 0) // E.g. "Desktop 1600 UXGA"
				{
					curSet = SMDesigner.Helpers.GetDimensionCss(editors["General"]["Text"]["Device text size"][prop], "font-size");

					if (curSet !== null)
					{
						css += "@media (min-width: " + prop.split(" ")[1] + "px)";
						css += "{";
						css += "html.Normal body, html.SMPagesEditor body {" + curSet + "}";
						css += "}";
					}
				}
			}

			// Tablet and mobile

			if (txtTabletSize !== null)
			{
				css += "@media (max-width: 900px)";
				css += "{";
				css += "html.Normal body, html.SMPagesEditor body {" + txtTabletSize + "}";
				css += "}";
			}

			if (txtTabletSize !== null || txtMobileSize !== null)
			{
				css += "@media (max-width: 500px)";
				css += "{";
				css += "html.Normal body, html.SMPagesEditor body {" + ((txtMobileSize !== null) ? txtMobileSize : "font-size: 100%;") + "}";
				css += "}";
			}

			return css;
		}

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

			var wallpaper = SMDesigner.Helpers.GetControlValue(editors["Background"]["Background"]["Background"]["Image (all pages)"]);

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

			// Apply page specific background image

			SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
			{
				var pageId = page.Id;
				var pageImage = SMDesigner.Helpers.GetControlValue(editors["Background"]["Background"]["Background"]["Image (current page);" + pageId]);

				if (pageImage === null)
					return;

				if (pageImage === "generated-bg.jpg" && editors["Background"]["Background"]["Generator"][""].Control.IsDirty() === true)
					pageImage = "generated-bg-tmp.jpg";

				if (pageImage.indexOf("generated-bg") === 0 && editors["Background"]["Background"]["Generator"]["CacheKey"].Control.IsDirty() === true)
					pageImage += "?cache=" + editors["Background"]["Background"]["Generator"]["CacheKey"].Control.GetValue();

				if (saving === true && pageImage.indexOf(SMEnvironment.GetFilesDirectory() + "/images/") === 0)
					pageImage = "../../" + pageImage;

				if (saving === false && pageImage !== "" && pageImage.indexOf(SMEnvironment.GetFilesDirectory() + "/images/") !== 0) // Assume images not starting with "files/images/" are located in template folder
					pageImage = eventArgs.TemplatePath + "/" + pageImage;

				css += "html.SMPagesPageId" + pageId + ".Normal,"
				css += "html.SMPagesPageId" + pageId + ".Basic.SMPagesViewer,"
				css += "html.SMPagesPageId" + pageId + ".SMPagesEditor.SMPagesContentPage,"
				css += "html.SMPagesPageId" + pageId + ".SMPagesEditor.SMPagesSystemPage.SMPagesFilenameHeader,"
				css += "html.SMPagesPageId" + pageId + ".SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter"
				css += "{"
				css += "background-image: url(\"" + pageImage + "\");";
				css += "}";
			});

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
				if (section === "Middle" && image !== null)
				{
					// Undo fix from mobile.css making background-size:cover on <html> element work on Safari under iOS.
					// The fix breaks background rendering when a background image is applied to div.TPLMiddle.
					// Unfortunately the background image set on TPLMiddle will not stay fixed and centered on iOS, but
					// will instead scale to the size of the document, and scroll along with the content. It is recommended
					// to avoid using div.TPLMiddle with a background image for maximum consistency and compatibility across devices.
					css += "@media (max-width: 900px)"
					css += "{";
					css += "html.Normal";
					css += "{";
					css += "height: auto;";
					css += "overflow: auto;";
					css += "}";
					css += "}";
				}

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

				// Fix: Make background color in content area stay on top of TPLBottom when moved up behind it using negative margin

				if (section === "Bottom" && editors[section]["Indentation"]["Margin"]["Top"].Control.GetValue().indexOf("-") === 0)
				{
					// Unfortunately this prevents TPLBottom from being selected using point-and-click,
					// but we can use the top menu in the Designer instead.
					// We could also just have given TPLContent position:relative, but that would cause
					// shadows on TPLContent to be shown on top of a floated menu which is not desirable.
					css += "div.TPLBottom { position: relative; z-index: -1; }";
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
					if (!editors["Header"]["Positioning"]["Positioning"]["Position"].Control.___isFloated)
					{
						// Remove any margin
						for (var prop in editors["Header"]["Indentation"]["Margin"])
							if (prop !== "All sides")
								editors["Header"]["Indentation"]["Margin"][prop].Control.SetValue("0");
						editors["Header"]["Indentation"]["Margin"]["All sides"].Control.SetValue(0);

						// Add spacing between header and top of document
						editors["Header"]["Indentation"]["Margin"]["Top"].Control.SetValue("30");
						editors["Header"]["Indentation"]["Margin"]["Top"].Selector.Control.SetValue("px");

						// Remove any padding
						for (var prop in editors["Header"]["Indentation"]["Padding"])
							if (prop !== "All sides")
								editors["Header"]["Indentation"]["Padding"][prop].Control.SetValue("0");
						editors["Header"]["Indentation"]["Padding"]["All sides"].Control.SetValue(0);

						// Set header to inline-block (Fit to content) when floated next to menu
						if (editors["Header"]["Positioning"]["Display"]["Type"].Control.GetValue() !== "none")
							editors["Header"]["Positioning"]["Display"]["Type"].Control.SetValue("inline-block");

						// Remove text alignment - it has no purpose for an inline-block element
						//editors["Header"]["Text"]["Formatting"]["Alignment"].Control.Reset();

						// Prevent changes to margin, padding, etc. if position is changed from left to right or from right to left
						editors["Header"]["Positioning"]["Positioning"]["Position"].Control.___isFloated = true;
					}
				}
				else // Above menu
				{
					// Reset margins to initial values
					for (var prop in editors["Header"]["Indentation"]["Margin"])
						editors["Header"]["Indentation"]["Margin"][prop].Control.Reset();
					editors["Header"]["Indentation"]["Margin"]["Top"].Selector.Control.Reset();

					// Reset paddings to initial values
					for (var prop in editors["Header"]["Indentation"]["Padding"])
						editors["Header"]["Indentation"]["Padding"][prop].Control.Reset();

					// Reset Display Type to initial value unless Position was reset programmatically using setTimeout(..) further down
					if (!editors["Header"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetDisplayType)
					{
						if (editors["Header"]["Positioning"]["Display"]["Type"].Control.GetValue() !== "none")
							editors["Header"]["Positioning"]["Display"]["Type"].Control.Reset();
					}

					delete editors["Header"]["Positioning"]["Positioning"]["Position"].Control.___isFloated;
				}

				// Always reset height and line-height when header position is changed
				editors["Header"]["Text"]["Formatting"]["Line height"].Control.Reset();
				editors["Header"]["Text"]["Formatting"]["Line height"].Selector.Control.Reset();
				editors["Header"]["Dimensions"]["Dimensions"]["Height"].Control.Reset();
				editors["Header"]["Dimensions"]["Dimensions"]["Height"].Selector.Control.Reset();
			}

			if (sender === editors["Header"]["Positioning"]["Display"]["Type"].Control
				&& (editors["Header"]["Positioning"]["Display"]["Type"].Control.GetValue() === "block" || editors["Header"]["Positioning"]["Display"]["Type"].Control.GetValue() === "stretch")
				&& (editors["Header"]["Positioning"]["Positioning"]["Position"].Control.GetValue() !== "")) // Normal position has an empty string value
			{
				// Reset Position to Normal if Display Type is set to Normal or Stretch

				setTimeout(function() // Postpone to allow control synchronization which is disabled while GetCss() is called to prevent infinite loops
				{
					editors["Header"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetDisplayType = true; // Prevent Display Type from being reset when Position is being reset below
					editors["Header"]["Positioning"]["Positioning"]["Position"].Control.Reset();
					delete editors["Header"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetDisplayType;
				}, 0);
			}

			// Variables holding CSS

			var cssHeader = "";
			var cssHeaderEditorOnly = "";
			var cssHeaderEditorOnlyHtmlElement = "";

			// Extract CSS values

			var parentWindow = window.opener || window.top;

			// Display
			var position = SMDesigner.Helpers.GetControlValue(editors["Header"]["Positioning"]["Positioning"]["Position"]);
			var display = SMDesigner.Helpers.GetControlValue(editors["Header"]["Positioning"]["Display"]["Type"]);
			var fixedPos = (SMDom.GetComputedStyle(parentWindow.document.getElementById("TPLHeader"), "position") === "fixed"); // Hyperspace support - hardcoded using CSS, possibly under advanced

			if (display !== null)
			{
				if (display === "stretch")
				{
					if (fixedPos === false)
					{
						var absPos = "position: absolute; left: 0px; right: 0px; top: 0px; z-index: 2;"; // Using z-index to keep header on top of menu (which has z-index:1) in case both header and menu is stretched and ends up on top of each other
						cssHeader += absPos;
					}
					else // Hyperspace support (fixed position)
					{
						cssHeader = "left: 0px; right: 0px; z-index: 4;"; // Using z-index to keep on top of content (div.TPLContent) which for Hyperspace has z-index:3 (defined under Advanced)
					}
				}
				else
				{
					cssHeader += "display: " + display + ";";
					///cssHeaderEditorOnly += ((position === "left" || position === "right") ? "display: inline-block;" : ""); // Header is floated and behaves like inline-block (does not work with IE7-8 - too bad!)
					cssHeaderEditorOnly += ((position === null && display === "hidden") ? "display: block;" : "");			// Undo display:none in editor so we can still change its content
				}
			}

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
			var padding = SMDesigner.Helpers.GetIndentationCss(editors["Header"]["Indentation"]["Padding"], "padding", true);
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
					if (!editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___isFloated)
					{
						// Remove any margin
						for (var prop in editors["Menu"]["Indentation"]["Margin"])
							if (prop !== "All sides")
								editors["Menu"]["Indentation"]["Margin"][prop].Control.SetValue("0");
						editors["Menu"]["Indentation"]["Margin"]["All sides"].Control.SetValue(0);

						// Add spacing between menu and top of document
						editors["Menu"]["Indentation"]["Margin"]["Top"].Control.SetValue("30");
						editors["Menu"]["Indentation"]["Margin"]["Top"].Selector.Control.SetValue("px");

						// Remove any padding
						for (var prop in editors["Menu"]["Indentation"]["Padding"])
							if (prop !== "All sides")
								editors["Menu"]["Indentation"]["Padding"][prop].Control.SetValue("0");
						editors["Menu"]["Indentation"]["Padding"]["All sides"].Control.SetValue(0);

						// Set menu to inline-block (Fit to content) when floated next to header
						editors["Menu"]["Positioning"]["Display"]["Type"].Control.SetValue("inline-block");

						// Remove text alignment - it has no purpose for an inline-block element
						//editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.Reset();

						// Prevent changes to margin, padding, etc. if position is changed from left to right or from right to left
						editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___isFloated = true;
					}
				}
				else // Normal - below header or stretched
				{
					// Reset margins to initial values, unless Position was reset programmatically using setTimeout(..) further down
					if (!editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetMargins)
					{
						for (var prop in editors["Menu"]["Indentation"]["Margin"])
							editors["Menu"]["Indentation"]["Margin"][prop].Control.Reset();
						editors["Menu"]["Indentation"]["Margin"]["Top"].Selector.Control.Reset();
					}

					// Reset paddings to initial values
					for (var prop in editors["Menu"]["Indentation"]["Padding"])
						editors["Menu"]["Indentation"]["Padding"][prop].Control.Reset();

					// Reset Display Type to initial value unless Position was reset programmatically using setTimeout(..) further down
					if (!editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetDisplayType)
					{
						editors["Menu"]["Positioning"]["Display"]["Type"].Control.Reset();
					}

					delete editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___isFloated;
					delete editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___suppressFloatWarning;
				}

				// Always reset line-height when menu position is changed
				editors["Menu"]["Text"]["Formatting"]["Line height"].Control.Reset();
				editors["Menu"]["Text"]["Formatting"]["Line height"].Selector.Control.Reset();
				editors["Menu"]["Dimensions"]["Dimensions"]["Height (line height)"].Control.Reset();
				editors["Menu"]["Dimensions"]["Dimensions"]["Height (line height)"].Selector.Control.Reset();

				// Warn user if menu and header cannot fit next to each other
				if (editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.IsDirty() === true
					&& !editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___suppressFloatWarning
					&& editors["Header"]["Positioning"]["Display"]["Type"].Control.GetValue() !== "none")
				{
					// Postpone check til after JS thread is released, and new CSS is applied. This also allows for
					// designer to perform control synchronization which is disabled when GetCss(..) is called, to
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

					editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___suppressFloatWarning = true;
				}
			}

			if (sender === editors["Menu"]["Positioning"]["Display"]["Type"].Control)
			{
				var preventMarginReset = false; // Prevent margins from being reset to initial values if Position is reset to Normal further down

				if (editors["Menu"]["Positioning"]["Display"]["Type"].Control.GetValue().indexOf("stretch") === 0)
				{
					if (editors["Menu"]["Positioning"]["Display"]["Type"].Control.GetValue() === "stretchtop")
					{
						// Remove any margin
						for (var prop in editors["Menu"]["Indentation"]["Margin"])
							if (prop !== "All sides")
								editors["Menu"]["Indentation"]["Margin"][prop].Control.SetValue("0");
						editors["Menu"]["Indentation"]["Margin"]["All sides"].Control.SetValue(0);

						preventMarginReset = true;
					}

					if (editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.GetValue() === "left"
						&& !editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.___doNotChangeTextAlignAgain)
					{
						editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.SetValue("center");
						editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.___doNotChangeTextAlignAgain = true; // Only center text the first time
					}
				}
				else
				{
					// Restore text alignment to initial value if centered programmatically above
					if (editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.GetValue() === "center"
						&& editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.___doNotChangeTextAlignAgain)
					{
						editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.Reset();
						delete editors["Menu"]["Text"]["Formatting"]["Alignment"].Control.___doNotChangeTextAlignAgain; // User never changed text alignment programmatically set above - allow Designer Definition to center text again if user switches back and forth between different Display Types
					}
				}

				if (editors["Menu"]["Positioning"]["Display"]["Type"].Control.GetValue() !== "inline-block")
				{
					setTimeout(function() // Postpone to allow control synchronization which is disabled while GetCss() is called to prevent infinite loops
					{
						editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetDisplayType = true;
						editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetMargins = preventMarginReset;
						editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.Reset();
						delete editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetDisplayType;
						delete editors["Menu"]["Positioning"]["Positioning"]["Position"].Control.___doNotResetMargins;
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

			if (display !== null)
			{
				if (display === "stretch" || display === "stretchtop")
					cssMenu += "position: absolute; left: 0px; right: 0px;" + ((display === "stretchtop") ? " top: 0px;" : "") + " z-index: 1;" // Using z-index to keep stretched menu on top of content area
				else
					cssMenu += "display: " + display + ";";
			}

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
			var textShadow = SMDesigner.Helpers.GetShadowCss(editors["Menu"]["Text"]["Shadow"], "text-shadow");

			// Fix: Move certain styling from Link CSS to Menu CSS
			if (text !== null)
			{
				var pos = -1;
				var substr = "";

				// Move text-align to menu to allow alignment of links
				if (text.indexOf("text-align") > -1)
				{
					pos = text.indexOf("text-align");
					substr = text.substring(pos, text.indexOf(";", pos) + 1);

					text = text.replace(substr, "");
					cssMenu += substr;
				}

				// Move font-size to menu to allow margin with em unit to be relative to font size
				if (text.indexOf("font-size") > -1)
				{
					pos = text.indexOf("font-size");
					substr = text.substring(pos, text.indexOf(";", pos) + 1);

					text = text.replace(substr, "");
					cssMenu += substr;
				}

				cssLinks += text;
			}

			if (textShadow !== null)
			{
				cssLinks += textShadow;
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
			var textShadow = SMDesigner.Helpers.GetShadowCss(editors["Submenus"]["Text"]["Shadow"], "text-shadow");

			// Fix: Move certain styling from Link CSS to SubMenu CSS
			if (text !== null)
			{
				var pos = -1;
				var substr = "";

				// Move text-align to submenu to allow alignment of links
				if (text.indexOf("text-align") > -1)
				{
					pos = text.indexOf("text-align");
					substr = text.substring(pos, text.indexOf(";", pos) + 1);

					text = text.replace(substr, "");
					cssSubMenus += substr;
				}

				// Move font-size to submenu to allow margin with em unit to be relative to font size
				if (text.indexOf("font-size") > -1)
				{
					pos = text.indexOf("font-size");
					substr = text.substring(pos, text.indexOf(";", pos) + 1);

					text = text.replace(substr, "");
					cssSubMenus += substr;
				}

				cssLinks += text;
			}

			if (textShadow !== null)
			{
				cssLinks += textShadow;
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

		var getHeadings = function()
		{
			var css = "";

			var headings = {};
			var heading = null;
			var shadow = null;

			for (var i = 1 ; i <= 6 ; i++)
			{
				heading = SMDesigner.Helpers.GetFontCss(editors["Headings"]["Heading " + i + " (h" + i + ")"]["Formatting"]);
				shadow = SMDesigner.Helpers.GetShadowCss(editors["Headings"]["Heading " + i + " (h" + i + ")"]["Shadow"], "text-shadow");

				if (heading === null && shadow === null)
					continue;

				headings["h" + i] = (heading !== null ? heading : "") + (shadow !== null ? shadow : "");
			}

			for (var h in headings)
			{
				css += "html.Normal div.TPLPage " + h + ",";
				css += "html.Basic.SMPagesViewer.SMPagesClassicLayout body " + h + ",";
				css += "html.SMPagesEditor.SMPagesContentPage.SMPagesClassicLayout body " + h + ",";
				css += "html.SMPagesEditor.SMPagesFilenameHeader " + h + ",";
				css += "html.SMPagesEditor.SMPagesFilenameFooter " + h;
				css += "{";
				css += headings[h];
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
			var backgroundColor = SMDesigner.Helpers.GetColorCss(editors["Page"]["Borders and colors"]["Background"]["Color (all pages)"], "background", true);
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

				var top = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Indentation"]["Padding"]["Top"]);
				var left = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Indentation"]["Padding"]["Left"]);
				var right = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Indentation"]["Padding"]["Right"]);
				var bottom = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Indentation"]["Padding"]["Bottom"]);

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

			SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
			{
				var backgroundColorCurPage = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(editors["Page"]["Borders and colors"]["Background"]["Color (current page);" + page.Id], function() { return SMDesigner.Helpers.GetColorCss(editors["Page"]["Borders and colors"]["Background"]["Color (current page);" + page.Id], "background", true); });

				if (backgroundColorCurPage !== backgroundColor)
				{
					css += "html.SMPagesPageId" + page.Id + " div.TPLContent,"
					css += "html.SMPagesPageId" + page.Id + ".Basic.SMPagesViewer.SMPagesClassicLayout body,"
					css += "html.SMPagesPageId" + page.Id + ".SMPagesEditor.SMPagesContentPage.SMPagesClassicLayout body"
					css += "{";
					css += backgroundColorCurPage;
					css += "}";
				}
			});

			if (text !== null)
			{
				// Notice regarding use of resolution breakpoints and relative units in Page Editor:
				// The base font size is set on <html>. Any adaption to resolution (resolution breakpoint) is set on <body>,
				// and naturally any font-size override is set on specific elements such as TPLHeader, TPLContent, etc.
				// Unfortunately we do not have the latter containers (TPLHeader, TPLContent, etc) in the Page Editor,
				// so instead we apply the font-styling to ALL immediate children of <body> which gives us almost the same behaviour.
				// However, this means that we can no longer have custom elements such as this
				// <div class="SMDesignerElement" data-id="Customizable element" data-preserve="true">Hello</div>
				// in the root of our document, and have it work reliably with a custom font-size, since it will first be applied
				// the adaption related to resolution, and then have this overridden by any element specific font-size (see example further down).
				// The solution to this problem is to wrap the element in another root element:
				// <div><div class="SMDesignerElement" data-id="Customizable element" data-preserve="true">Hello</div></div>
				// Obviously this is not ideal but it works when we need to apply different font-sizes for different resolutions,
				// AND adjust the font-size for a given content area as well.

				// To summarize, this is what happens on the website where everything works as expected:
				// <html>		14px	= 14px
				// <body>		120%	= 16.8px
				// TPLContent	1.25em	= 21px (correct result)

				// This is what happens if we do NOT apply resolution specific font-size changes to the root elements in
				// <body> in the page editor, but incorrectly applies it to <body> instead (which we previously did):
				// <html>		14px	= 14px
				// <body>		120%	= 16.8px (adaption related to resolution breakpoint is overridden below and hence lost)
				// <body>		1.25em	= 17.5px (overrides previously value set - no longer results in a value of 21px)

				// This is what happens when we DO apply resolution specific font-size changes to the root elements in <body> in the page editor:
				// <html>		14px	= 14px
				// <body>		120%	= 16.8px
				// <body> > *	1.25em	= 21px (correct result)

				css += "div.TPLContent, html.Basic.SMPagesViewer.SMPagesClassicLayout body > *, html.SMPagesEditor.SMPagesContentPage.SMPagesClassicLayout body > *";
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
					css += "html.SMPagesCardLayout div.TPLContent"; // Not necessary in Preview and Editor since only ClassicLayout is targeted for these views
					css += "{";
					css += revertForCards;
					css += "}";
				}
			}

			if (linkColor !== null)
			{
				css += "div.TPLContent a,";
				css += "html.SMPagesEditor.SMPagesContentPage body a"
				css += "{";
				css += linkColor;
				css += "}";
			}

			return css;
		}

		var getPageHeaderImages = function()
		{
			var css = "";

			var getHeightInPixels = function(anySizeAnyUnit, deviceSize) // anySizeAnyUnit can be e.g. 20px or 1.75em
			{
				var parentWindow = window.opener || window.top;

				// Temporarily apply Base Text Size and Device Text Size

				var generalFontSize = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(editors["General"]["Text"]["Base text size"]["Base text size"], function()
				{
					return SMDesigner.Helpers.GetDimensionCss(editors["General"]["Text"]["Base text size"]["Base text size"]);
				});
				parentWindow.document.getElementById("TPLMiddle").style.fontSize = generalFontSize; // Necessary in case Designer is used in emulated tablet/mobile view (resized window), in which case <body> may have font-size set to e.g. 80%

				var baseFontSize = SMDesigner.Helpers.GetDimensionCss(editors["General"]["Text"]["Device text size"][deviceSize]);
				if (baseFontSize !== null)
					parentWindow.document.getElementById("TPLPage").style.fontSize = baseFontSize; // Set device text size

				// Calculate height

				var elm = document.createElement("div");
				elm.innerHTML = "";
				elm.style.cssText = "position: absolute; visibility: hidden; height: " + anySizeAnyUnit + ";";

				var contentContainer = parentWindow.document.getElementById("TPLContent");
				contentContainer.appendChild(elm);
				var height = elm.offsetHeight;
				contentContainer.removeChild(elm);

				// Revert changes and return result

				parentWindow.document.getElementById("TPLMiddle").style.fontSize = "";
				parentWindow.document.getElementById("TPLPage").style.fontSize = "";

				return height;
			}

			var recalcForced = false;
			var curPageId = getCurrentPageId();

			SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
			{
				var pageId = page.Id;

				var image = SMDesigner.Helpers.GetControlValue(editors["Page"]["Header image"]["Header image"]["Image;" + pageId]);
				var stretch = (SMDesigner.Helpers.GetControlValue(editors["Page"]["Header image"]["Header image"]["Stretch across page;" + pageId]) === "Yes");
				var above = (SMDesigner.Helpers.GetControlValue(editors["Page"]["Header image"]["Header image"]["Position;" + pageId]) === "Above");
				var height = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Header image"]["Header image"]["Height;" + pageId], "height");
				var indentFromTop = SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Header image"]["Header image"]["Indent content from top;" + pageId]);
				var recalcRequired = (indentFromTop !== null
									  && editors["Page"]["Header image"]["Header image"]["Indent content from top;" + pageId].Selector.Control.GetValue() === "em"
									  && (sender === editors["Page"]["Text"]["Formatting"]["Size"].Control
										  || sender === editors["General"]["Text"]["Base text size"]["Base text size"].Control
										  || sender === editors["General"]["Text"]["Device text size"]["Desktop"].Control
										  || sender === editors["General"]["Text"]["Device text size"]["Tablet"].Control
										  || sender === editors["General"]["Text"]["Device text size"]["Mobile"].Control));
				height = ((height !== null) ? height : "height: 50px;");

				if (image === null)
					return; // Skip

				if (recalcRequired === true && recalcForced === false)
				{
					// GetCss(..) was triggered by user changing font-size of content area (e.g. under Page, General or Advanced)
					// The current header image has "Indentation from top" set to a value with the em unit
					// which is turned into a pixel based height. However, that is not possible until after
					// the updated font-size have been applied, hence requiring us to update the CSS twice.

					setTimeout(function()
					{
						eventArgs.Designer.Update();
					}, 0);

					recalcForced = true;
				}

				// For background images located under files/images:
				// Use relative path when saving, since image will be referenced from templates/XYZ/style.css:
				// background: url("../../files/images/background/Sky.jpg");
				// During design time, this is not necessary since CSS is injected
				// into the page, meaning files are resolved from page root.
				if (saving === true && image.indexOf(SMEnvironment.GetFilesDirectory() + "/images/") === 0)
					image = "../../" + image;

				// For background images located under templates/XYZ (e.g. generated-bg.jpg):
				// Use absolute path when making changes (live updates), since
				// image will be referenced from the page root (CSS is injected):
				// background: url("templates/Sunrise/generated-bg.jpg");
				// When saving, this is not necessary since the file will then be
				// referenced from templates/XYZ/style.css.
				if (saving === false && /*image !== "" &&*/ image.indexOf(SMEnvironment.GetFilesDirectory() + "/images/") !== 0) // Assume images not starting with "files/images/" are located in template folder
					image = eventArgs.TemplatePath + "/" + image;

				var borderRadius = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(editors["Page"]["Borders and colors"]["Border"]["Rounded corners"], function()
				{
					return SMDesigner.Helpers.GetDimensionCss(editors["Page"]["Borders and colors"]["Border"]["Rounded corners"]);
				});

				// To add the header image to the page editor we
				// override body:before which is used to indent content using margin-bottom and
				// to prevent margin collapse using padding-top. But since the new body:before
				// element is taken out of flow, we have no choice but to also remove the indentation
				// from the ordinary page view to make sure the result is exactly the same for both
				// the page view and editor, and to apply padding-top to the body element instead
				// to prevent margin-collapse.

				// Internet Explorer bugs:
				// IE8 does not display the header image in the page editor which is fine.
				// Also be aware that legacy IE is not capable of stretching the image, causing
				// the image to be centered and not stretching from left to right as expected.
				// Simply use a higher resolution image to solve this.
				// Position = Above content: This does not work properly on IE8. The content
				// remains on top of header image. This is a minor problem since it's unlikely
				// the intentation is to hide content behind the header image - most often
				// the user will indent the content to have it displayed below the header image.
				// Finally "Indent content from top" does not work reliably on IE9 and below
				// within page editor, since some default padding seems to be applied to body
				// which we cannot get rid of, causing indentation to be off by 15-20 pixels.

				// Safari bug (Safari 10.1):
				// The background image in the Page Editor is not rendered properly
				// when a header image is applied.
				// The bug can be easily reproduced with e.g. JSFiddle as demonstrated
				// here: https://jsfiddle.net/e0znq0mf (make result window 800px wide)
				// A bug report has been filed regarding this issue:
				// https://bugs.webkit.org/show_bug.cgi?id=172551

				// Misc:
				// Header image positioned above content is less likely to render 100% accurate on older browsers.
				// For instance on Android 4.4 with Chrome, some minor rendering glitches may be found at the bottom
				// of the screen when scrolling. It seems to work fine on old iOS devices though (testet with iOS 6).

				// Remove default indentation from top.
				// No need to do this for page editor or page previewer since the pseudo element
				// used to indent content from top, is now used to display the header image instead.
				css += "html.SMPagesPageId" + pageId + " div.SMExtension.SMPages";
				css += "{";
				css += "margin-top: 0px !important;"; // Using important to prevent mobile optimization from changing this (which also uses !important)
				css += "}";

				// Indentation from top using margin-top on first child contained
				for (var baseType in {"Desktop":"", "Tablet":"", "Mobile":""})
				{
					var indentPixels = ((indentFromTop !== null) ? getHeightInPixels(indentFromTop, baseType) : 0);
					// /* Disabled - not worth it for just 0.02px */ indentPixels = (stretch === true ? indentPixels - 0.02 : indentPixels); // Compensate for padding-top:0.02px on div.SMExtension when stretched (applied further down) - not necessary when header image is positioned within div.TPLContent with top:0px

					if (baseType === "Tablet")
						css += "@media (max-width: 900px){"
					else if (baseType === "Mobile")
						css += "@media (max-width: 500px){"

					css += "html.SMPagesPageId" + pageId + " div.SMExtension.SMPages > *:first-child,";
					css += "html.SMPagesEditor.SMPagesPageId" + pageId + " body > *:first-child";
					css += "{";
					css += "margin-top: " + indentPixels + "px !important;"; // !important necessary since tag specific styling takes precedence (e.g. h1 wins over *:first-child)
					css += "}";

					if (baseType === "Tablet" || baseType === "Mobile")
						css += "}"
				}

				// Apply header image using pseudo element
				css += "html.SMPagesPageId" + pageId + " div.SMExtension.SMPages:before,";
				css += "html.SMPagesEditor.SMPagesPageId" + pageId + " body:before";
				css += "{";
				css += "content: '';";
				css += "display: block;";
				css += "width: 100%;";
				css += height;
				css += "background-image: url('" + image + "');";
				css += "background-repeat: no-repeat;";
				css += "background-position: center center;";
				css += "background-size: cover;"; /*100%*/ // Not supported by IE8 - will result in small images not stretching - fix: use a wider image
				css += ((stretch === false) ? "border-top-left-radius: " + borderRadius + ";" : "");
				css += ((stretch === false) ? "border-top-right-radius: " + borderRadius + ";" : "");
				css += "position: absolute;";
				css += ((stretch === false) ? "top: 0px;" : "");
				css += "left: 0px;";
				// /* Disabled - not worth it for just 0.02px */ css += (stretch === true ? "margin-top: -0.02px;" : ""); // Compensate for padding-top:0.02px on div.TPLContent - not necessary when header image is positioned within div.TPLContent with top:0px
				css += ((above === false) ? "z-index: -1;" : ""); // Above does not work on IE8 (unlikely use-case trying to hide content below it so it's fine)
				//css += "-webkit-transform: translate3d(0, 0, 0);"; // Prevent element from disappearing when scrolling on iOS
				css += "}";

				// Prevent any background set on TPLMiddle from hiding header image if positioned behind content.
				// Not necessary in page editor or page previewer since background image
				// is applied to <html> element rather than TPLMiddle.
				// Also necessary to make sure header image scrolls with content on mobile (at least in Chrome)
				// due to background-attachment fix on html element to make background stick and scale properly.
				css += "html.SMPagesPageId" + pageId + " div.TPLMiddle";
				css += "{";
				css += "position: relative;";
				css += "z-index: 0;";
				css += "}";

				// IeFixHeaderImage class is applied by enhancements/iefix.normal.js to fix
				// background image position and size in Internet Explorer (all versions).
				css += "html.SMPagesPageId" + pageId + ".IeFixHeaderImage div.SMExtension.SMPages:before";
				css += "{";
				css += "opacity: 1;";
				css += "}";

				// Prevent header image from being squeezed on mobile
				css += "@media (max-width: 900px)";
				css += "{";
				css += "html.SMPagesPageId" + pageId + " div.SMExtension.SMPages:before";
				css += "{";
				css += "background-size: 900px;";
				css += "}";
				css += "}";

				if (stretch === false) // Not stretched - contained within content area
				{
					// Make image width:100% be equal to div.TPLContent container
					// rather than document, when image has position:absolute
					css += "html.Normal.SMPagesPageId" + pageId + " div.TPLContent,";
					css += "html.Basic.SMPagesPageId" + pageId + " div.TPLBasicContent,";
					css += "html.SMPagesEditor.SMPagesPageId" + pageId + " body";
					css += "{";
					css += "position: relative;"
					css += "}";

					// Remove rounded cornors from header image on mobile, just like
					// rounded corners are removed from content area on mobile.
					css += "@media (max-width: 900px)";
					css += "{";
					css += "html.SMPagesPageId" + pageId + " div.SMExtension.SMPages:before";
					css += "{";
					css += "border-top-left-radius: 0px;";
					css += "border-top-right-radius: 0px;";
					css += "}";
					css += "}";
				}
				else // Stretched across page
				{
					// Undo fix that ensures TPLBottom can be moved up behind content
					// - this prevents header image from stretching across the entire page.
					// Fairly unlikely to be used so this approach seems reasonable.
					css += "html.SMPagesPageId" + pageId + " div.TPLContent";
					css += "{";
					css += "position: static;"; // Revert position:relative
					css += "}";

					// Indent content from top:
					// Prevent margin on first child contained (used to indent content from top)
					// from pushing down header image along with content. This is necessary
					// when header image is stretched.
					css += "html.SMPagesPageId" + pageId + " div.SMExtension";
					css += "{";
					css += "padding-top: 0.02px;"; // 1px
					css += "}";

					// Undo fix in _BaseGeneric/mobile.css which fixes a problem with
					// background images not stretching properly on mobile devices.
					// Unfortunately this fix causes stretched header images to stick.
					// It is fairly unlikely that a background image is applied together
					// with a stretched header image, so it seems safe to revert the fix.
					if (above === true)
					{
						css += "@media (max-width: 900px)";
						css += "{";
						css += "	html.Normal.SMPagesPageId" + pageId;
						css += "	{";
						css += "		overflow: visible;";
						css += "	}";
						css += "}";
					}
				}

				// All versions of IE seems to have problems with positioning the header image properly
				// if the image finish loading AFTER the CSS is applied (e.g. if the image is very large).
				// However, once the image is loaded into cache, it works fine.
				// Therefore we programmatically unselect and reselect the image once it is loaded into the browser cache.
				// NOTICE: Unfortunately ordinary users (visitors) might still experience the problem the first time they
				// visit the website, which is why we introduced the IeFixHeaderImage CSS class and iefix.normal.js
				// (see code further up for details).

				if (SMBrowser.GetBrowser() === "MSIE")
				{
					window.SMDesignerIeImageFix = (window.SMDesignerIeImageFix ? SMDesignerIeImageFix : {});

					if (pageId === curPageId && !window.SMDesignerIeImageFix[image])
					{
						var parentWindow = window.opener || window.top;

						var img = new Image();
						img.style.display = "none";
						parentWindow.document.body.appendChild(img);
						img.onload = function(e)
						{
							var c = editors["Page"]["Header image"]["Header image"]["Image;" + pageId].Control;
							c.SetValue("");

							setTimeout(function()
							{
								window.SMDesignerIeImageFix[image] = true;
								parentWindow.document.body.removeChild(img);
								c.SetValue(image);
							}, 100);
						}
						img.src = image;
					}
				}
			});

			return css;
		}

		var getExtensionPage = function()
		{
			// Dimensions
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Extension page"]["Dimensions"]["Dimensions"]["Width"], "width");

			// Indentation
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Extension page"]["Indentation"]["Margin"], "margin");
			var paddingAsMargin = SMDesigner.Helpers.GetIndentationCss(editors["Extension page"]["Indentation"]["Padding"], "margin");

			// Background color
			var backgroundColor = SMDesigner.Helpers.GetColorCss(editors["Extension page"]["Background"]["Background"]["Color"], "background", true);

			// Text formatting
			var text = SMDesigner.Helpers.GetFontCss(editors["Extension page"]["Text"]["Formatting"]);
			var linkColor = SMDesigner.Helpers.GetColorCss(editors["Extension page"]["Text"]["Links"]["Color"], "color", true);

			// Wrap CSS in selectors

			var css = "";

			if (width !== null)
			{
				css += "html.SMIntegratedExtension div.TPLPage";
				css += "{";
				css += width;
				css += "}";
			}

			if (margin !== null)
			{
				css += "html.SMIntegratedExtension div.TPLContent";
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

				css += "html.SMIntegratedExtension.Normal div.SMExtension";
				css += "{";
				css += paddingAsMargin; // Does not affect dialogs
				css += "}";
			}

			if (backgroundColor !== null)
			{
				css += "html.SMIntegratedExtension div.TPLContent";
				css += "{";
				css += backgroundColor;
				css += "}";
			}

			if (text !== null)
			{
				css += "html.SMIntegratedExtension div.TPLContent";
				css += "{";
				css += text;
				css += "}";
			}

			if (linkColor !== null)
			{
				css += "html.SMIntegratedExtension div.TPLContent a";
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
				css += "div.SMPagesCard";
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

		var getFluidGridCards = function()
		{
			// Borders and colors
			var bgColorTitle = SMDesigner.Helpers.GetColorCss(editors["Fluid Grid Cards"]["Borders and colors"]["Background"]["Header and footer"], "background", true);
			var bgColorContent = SMDesigner.Helpers.GetColorCss(editors["Fluid Grid Cards"]["Borders and colors"]["Background"]["Content"], "background", true);
			var border = SMDesigner.Helpers.GetBorderCss(editors["Fluid Grid Cards"]["Borders and colors"]["Border"], true);
			var shadow = SMDesigner.Helpers.GetShadowCss(editors["Fluid Grid Cards"]["Borders and colors"]["Shadow"]);

			// Text formatting
			var textContent = SMDesigner.Helpers.GetFontCss(editors["Fluid Grid Cards"]["Text"]["Formatting"]);
			var textTitle = SMDesigner.Helpers.GetFontCss(editors["Fluid Grid Cards"]["Text"]["Header and footer"]);
			var linkColor = SMDesigner.Helpers.GetColorCss(editors["Fluid Grid Cards"]["Text"]["Links"]["Color"], "color", true);

			// Indentation
			var margin = SMDesigner.Helpers.GetDimensionCss(editors["Fluid Grid Cards"]["Indentation"]["Indentation"]["Margin"]);
			var spacing = SMDesigner.Helpers.GetDimensionCss(editors["Fluid Grid Cards"]["Indentation"]["Indentation"]["Spacing"]);
			var padding = SMDesigner.Helpers.GetDimensionCss(editors["Fluid Grid Cards"]["Indentation"]["Indentation"]["Padding"], "padding");

			// Wrap CSS in selectors

			var css = "";

			if (margin !== null || spacing !== null)
			{
				if (margin === spacing)
				{
					css += "div.SMPagesTable.SMPagesGridCards,"
					css += ".mceItemTable.SMPagesGridCards"
					css += "{";
					css += ((spacing !== null) ? "border-spacing: " + spacing + ";" : "");
					css += "}";

					css += ".mceItemTable.SMPagesGridCards + .mceItemTable.SMPagesGridCards,";
					css += "div.SMPagesTable.SMPagesGridCards + div.SMPagesTable.SMPagesGridCards";
					css += "{";
					css += "margin-top: -" + ((spacing !== null) ? spacing : "2em") + ";";
					css += "}";
				}
				else // Does not work with IE9 and below, even though IE9 supports calc(..) - it simply doesn't work for elements with display:table
				{
					css += "@media (min-width: 899px)"; /*max-width: 900px*/ // Apply only to desktop version since it may cause horizontal scrolling on smaller devices
					css += "{";

					var spacingUnit = editors["Fluid Grid Cards"]["Indentation"]["Indentation"]["Spacing"].Selector.Control.GetValue();
					var marginUnit = spacingUnit; //editors["Fluid Grid Cards"]["Indentation"]["Indentation"]["Margin"].Selector.Control.GetValue();

					css += "div.SMPagesTable.SMPagesGridCards,"
					css += ".mceItemTable.SMPagesGridCards"
					css += "{";
					css += ((spacing !== null) ? "border-spacing: " + spacing + ";" : "");
					css += ((spacing !== null || margin !== null) ? "margin: calc(-" + ((spacing !== null) ? spacing : "2em") + ((margin !== null) ? " + " + margin : " + 2em") + ");" : "");
					css += ((spacing !== null || margin !== null) ? "width: calc(100% + " + ((spacing !== null) ? (parseFloat(spacing) * 2) + spacingUnit : "4em") + ((margin !== null) ? " - " + (parseFloat(margin) * 2) + marginUnit : " - 4em") + ");" : "");
					css += "}";

					var spacingSize = ((spacing !== null) ? parseFloat(spacing) : 3);
					var marginSize = ((margin !== null) ? parseFloat(margin) : 3);

					if (/*marginUnit === spacingUnit &&*/ spacingSize > marginSize)
					{
						css += ".mceItemTable.SMPagesGridCards + .mceItemTable.SMPagesGridCards,";
						css += "div.SMPagesTable.SMPagesGridCards + div.SMPagesTable.SMPagesGridCards";
						css += "{";
						css += "margin-top: -" + spacing + ";";
						css += "}";
					}
					else //if (/*marginUnit === spacingUnit &&*/ spacingSize <= marginSize)
					{
						css += ".mceItemTable.SMPagesGridCards + .mceItemTable.SMPagesGridCards,";
						css += "div.SMPagesTable.SMPagesGridCards + div.SMPagesTable.SMPagesGridCards";
						css += "{";
						css += "margin-top: -" + ((margin !== null) ? margin : "2em") + ";";
						css += "}";
					}
					/*else
					{
						// If the units for Spacing and Margin is not the same, we cannot determine which of the values are the largest.
						// At least not without injecting elements into the DOM that we can measure the size of (like we do with getPageHeaderImages()).
						// However, currently that is not supported, and will most likely never be missed.
					}*/

					css += "}";
				}
			}

			if (bgColorContent !== null || border !== null || shadow !== null || textContent !== null || padding !== null)
			{
				css += "div.SMPagesTable.SMPagesGridCards div.SMPagesTableCell,"
				css += ".mceItemTable.SMPagesGridCards td"
				css += "{";
				css += ((bgColorContent !== null) ? bgColorContent : "");
				css += ((border !== null) ? border : "");
				css += ((shadow !== null) ? shadow : "");
				css += ((textContent !== null) ? textContent : "");
				css += ((padding !== null) ? padding : "");
				css += "}";
			}

			if (bgColorTitle !== null || textTitle !== null)
			{
				css += "div.SMPagesTable.SMPagesGridCards div.SMPagesTableCell span.SMPagesCardHeader,"
				css += ".mceItemTable.SMPagesGridCards td span.SMPagesCardHeader,"
				css += "div.SMPagesTable.SMPagesGridCards div.SMPagesTableCell span.SMPagesCardFooter,"
				css += ".mceItemTable.SMPagesGridCards td span.SMPagesCardFooter"
				css += "{";
				css += ((bgColorTitle !== null) ? bgColorTitle : "");
				css += ((textTitle !== null) ? textTitle : "");
				css += "}";
			}

			if (linkColor !== null)
			{
				css += "div.SMPagesTable.SMPagesGridCards div.SMPagesTableCell a,"
				css += "table.SMPagesGridCards td a"
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
				var titleBorder = border;

				if (titleBorder !== null)
				{
					// Remove border-radius for title element - inheritance has been configured in style.css
					if (titleBorder.indexOf("border-radius") > -1)
					{
						var pos = titleBorder.indexOf("border-radius");
						var borderRadius = titleBorder.substring(pos, titleBorder.indexOf(";", pos) + 1);
						titleBorder = titleBorder.replace(borderRadius, "");
					}

					// Only apply border styles to bottom
					titleBorder = SMStringUtilities.ReplaceAll(titleBorder, "border:", "border-bottom:");
				}

				css += "html.SMPagesClassicLayout div.TPLSnippetTitle"
				css += "{";
				css += ((bgColorTitle !== null) ? bgColorTitle : "");
				css += ((titleBorder !== null) ? titleBorder : "");
				css += "}";
			}

			if (textContent !== null)
			{
				css += "html.SMPagesClassicLayout div.TPLSnippet";
				css += "{";
				css += textContent;
				css += "}";
			}

			if (textTitle !== null)
			{
				css += "html.SMPagesClassicLayout div.TPLSnippetTitle"
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

			// Display
			var display = SMDesigner.Helpers.GetControlValue(editors["Footer"]["Positioning"]["Display"]["Type"]);
			cssFooter += ((display !== null) ? ((display === "stretch") ? "position: absolute; left: 0px; right: 0px;" : "display: " + display + ";") : "");

			// Dimensions
			var width = SMDesigner.Helpers.GetDimensionCss(editors["Footer"]["Dimensions"]["Dimensions"]["Width"], "width");
			var height = SMDesigner.Helpers.GetDimensionCss(editors["Footer"]["Dimensions"]["Dimensions"]["Height"], "height");

			// Indentation
			var margin = SMDesigner.Helpers.GetIndentationCss(editors["Footer"]["Indentation"]["Margin"], "margin");
			var padding = SMDesigner.Helpers.GetIndentationCss(editors["Footer"]["Indentation"]["Padding"], "padding", true);
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

			if (width !== null && display !== "stretch")
			{
				// Width is set on html element in page editor
				css += "html.SMPagesCustomFooter div.TPLFooter, html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter";
				css += "{";
				css += width;
				css += "}";
			}
			if (height !== null)
			{
				// Height must be set on body element in page editor to work properly
				css += "html.SMPagesCustomFooter div.TPLFooter, html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter body";
				css += "{";
				css += height
				css += "}";
			}

			if (cssFooter !== "")
			{
				css += "html.SMPagesCustomFooter div.TPLFooter, html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter body";
				css += "{";
				css += cssFooter;
				css += "}";

				if (display === "stretch")
				{
					css += "@media (max-width: 900px) { html.SMPagesCustomFooter div.TPLFooter { position: static; } }";
				}
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
				css += "html.SMPagesCustomFooter div.TPLFooter, html.SMPagesEditor.SMPagesSystemPage.SMPagesFilenameFooter body";
				css += "{";
				css += cssText;
				css += "}";
			}

			setTimeout(function() // Postpone to allow CSS to be applied first
			{
				var parentWindow = window.opener || window.top;
				var footer = parentWindow.document.getElementById("TPLFooter");

				if (footer.StickyFooter)
					footer.StickyFooter(display === "stretch"); // Function defined in enhancements/footer.normal.js
			}, 0);

			return css;
		}

		var getMenuPageJoin = function(pageType) // Argument is either "Page" or "Extension page"
		{
			// If menu and page have no spacing between them, make sure borders and
			// border-radius between them is removed to make them join seamlessly.

			var css = "";

			var parentWindow = window.opener || window.top;
			if (SMDom.GetComputedStyle(parentWindow.document.getElementById("TPLMenu"), "position") === "fixed" /*!== "static"*/ // Hyperspace support
				|| SMDom.GetComputedStyle(parentWindow.document.getElementById("TPLPage"), "position") === "absolute" /*!== "static"*/ // Using GetComputedStyle(..) is bad practice within GetCss(..) unless styles are expected to be applied using CSS rather than the Designer - the reason is that once GetCss(..) is invoked, old styles may still be in effect since the updated styles are not yet applied
				|| editors["Menu"]["Positioning"]["Display"]["Type"].Control.GetValue().indexOf("stretch") === 0)
			{
				return css; // Do not join borders when alternative positioning is applied to page and/or menu
			}

			var menuMarginBottomStr = editors["Menu"]["Indentation"]["Margin"]["Bottom"].Control.GetValue();
			var menuMarginBottom = ((isNaN(parseFloat(menuMarginBottomStr)) === false) ? parseFloat(menuMarginBottomStr) : 0);

			var pageMarginTopStr = editors["Page"]["Indentation"]["Margin"]["Top"].Control.GetValue();
			var pageMarginTop = ((isNaN(parseFloat(pageMarginTopStr)) === false) ? parseFloat(pageMarginTopStr) : 0);

			// Color pickers return Null when empty
			var menuBackColor = editors["Menu"]["Borders and colors"]["Background"]["Color"].Control.GetValue();
			var menuBorderColor = editors["Menu"]["Borders and colors"]["Border"]["Color"].Control.GetValue();
			var pageBackColor = editors["Page"]["Borders and colors"]["Background"]["Color (all pages)"].Control.GetValue();
			var pageBorderColor = editors["Page"]["Borders and colors"]["Border"]["Color"].Control.GetValue(); // Extension Page does not have controls for overriding the border settings

			if (pageType === "Extension page")
			{
				var extPageMarginTopStr = editors["Extension page"]["Indentation"]["Margin"]["Top"].Control.GetValue();

				if (extPageMarginTopStr !== "")
				{
					pageMarginTop = ((isNaN(parseFloat(extPageMarginTopStr)) === false) ? parseFloat(extPageMarginTopStr) : pageMarginTop);
				}

				// The background color for Extension Page is null be default, which indicates that the background has not been overridden.
				// Therefore, the only way to remove the background color from an Extension Page is to set a color with 100% transparency (alpha channel == 0).

				var extPageBackColor = editors["Extension page"]["Background"]["Background"]["Color"].Control.GetValue();

				if (extPageBackColor !== null && extPageBackColor.Alpha === 0)
				{
					pageBackColor = null; // Background is fully transparent - set Null to indicate that the background has been removed
				}
			}

			// Notice: Currently, menu/page join is global - it does not take page specific background colors into account
			if (menuMarginBottom === 0 && pageMarginTop === 0
				&& ((menuBackColor !== null && menuBackColor.Alpha > 0) || (menuBorderColor !== null && menuBorderColor.Alpha > 0))
				&& ((pageBackColor !== null && pageBackColor.Alpha > 0) || (pageBorderColor !== null && pageBorderColor.Alpha > 0)))
			{
				css += "html.TplOnDesktop." + ((pageType === "Page") ? "SMPagesViewer" : "SMIntegratedExtension") + " div.TPLMenu > ul";
				css += "{";
				css += "border-bottom-style: none;";
				css += "border-bottom-left-radius: 0px;";
				css += "border-bottom-right-radius: 0px;";
				css += "}";

				css += "html.TplOnDesktop." + ((pageType === "Page") ? "SMPagesViewer" : "SMIntegratedExtension") + " div.TPLContent";
				css += "{";
				css += "border-top-style: none;";
				css += "border-top-left-radius: 0px;";
				css += "border-top-right-radius: 0px;";
				css += "}";

				if (pageType === "Page")
				{
					// Remove border radius from all header images that have not been stretched
					SMCore.ForEach(SMDesigner.Resources.Pages, function(page)
					{
						var pageId = page.Id;
						var image = SMDesigner.Helpers.GetControlValue(editors["Page"]["Header image"]["Header image"]["Image;" + pageId]);
						var stretch = (SMDesigner.Helpers.GetControlValue(editors["Page"]["Header image"]["Header image"]["Stretch across page;" + pageId]) === "Yes");

						if (image === null || stretch === true)
							return; // Skip

						css += "html.TplOnDesktop.SMPagesPageId" + pageId + " div.SMExtension.SMPages:before,";
						css += "html.TplOnDesktop.SMPagesEditor.SMPagesPageId" + pageId + " body:before";
						css += "{";
						css += "border-top-left-radius: 0px;";
						css += "border-top-right-radius: 0px;";
						css += "}";
					});

					// Undo menu styling for pages with Card Layout

					var menuBorder = SMDesigner.Helpers.GetBorderCss(editors["Menu"]["Borders and colors"]["Border"], true);

					if (menuBorder === null) // No overrides specified - use default (see style.css)
						menuBorder = "border-bottom-left-radius: 5px;border-bottom-right-radius: 5px;";

					css += "html.TplOnDesktop.SMPagesCardLayout div.TPLMenu > ul";
					css += "{";
					css += menuBorder;
					css += "}";
				}
			}

			return css;
		}

		var getPageFooterJoin = function(pageType) // Argument is either "Page" or "Extension page"
		{
			// If page and footer have no spacing between them, make sure borders and
			// border-radius between them is removed to make them join seamlessly.

			var css = "";

			var parentWindow = window.opener || window.top;
			if (SMDom.GetComputedStyle(parentWindow.document.getElementById("TPLPage"), "position") === "absolute" /*!== "static"*/ // Hyperspace support
				|| SMDom.GetComputedStyle(parentWindow.document.getElementById("TPLFooter"), "position") === "fixed" /*!== "relative"*/ // Using GetComputedStyle(..) is bad practice within GetCss(..) unless styles are expected to be applied using CSS rather than the Designer - the reason is that once GetCss(..) is invoked, old styles may still be in effect since the updated styles are not yet applied
				|| editors["Footer"]["Positioning"]["Display"]["Type"].Control.GetValue() === "stretch")
			{
				return css; // Do not join borders when alternative positioning is applied to page and/or footer
			}

			if (editors["Footer"]["Positioning"]["Display"]["Type"].Control.GetValue() === "none")
			{
				return css; // Do not join borders when footer has been hidden
			}

			var pageMarginBottomStr = editors["Page"]["Indentation"]["Margin"]["Bottom"].Control.GetValue();
			var pageMarginBottom = ((isNaN(parseFloat(pageMarginBottomStr)) === false) ? parseFloat(pageMarginBottomStr) : 0);

			var footerMarginTopStr = editors["Footer"]["Indentation"]["Margin"]["Top"].Control.GetValue();
			var footerMarginTop = ((isNaN(parseFloat(footerMarginTopStr)) === false) ? parseFloat(footerMarginTopStr) : 0);

			// Color pickers return Null when empty
			var pageBackColor = editors["Page"]["Borders and colors"]["Background"]["Color (all pages)"].Control.GetValue();
			var pageBorderColor = editors["Page"]["Borders and colors"]["Border"]["Color"].Control.GetValue(); // Extension Page does not have controls for overriding the border settings
			var footerBackColor = editors["Footer"]["Borders and colors"]["Background"]["Color"].Control.GetValue();
			var footerBorderColor = editors["Footer"]["Borders and colors"]["Border"]["Color"].Control.GetValue();

			if (pageType === "Extension page")
			{
				var extPageMarginBottomStr = editors["Extension page"]["Indentation"]["Margin"]["Bottom"].Control.GetValue();

				if (extPageMarginBottomStr !== "")
				{
					pageMarginBottom = ((isNaN(parseFloat(extPageMarginBottomStr)) === false) ? parseFloat(extPageMarginBottomStr) : pageMarginBottom);
				}

				// The background color for Extension Page is null be default, which indicates that the background has not been overridden.
				// Therefore, the only way to remove the background color from an Extension Page is to set a color with 100% transparency (alpha channel == 0).

				var extPageBackColor = editors["Extension page"]["Background"]["Background"]["Color"].Control.GetValue();

				if (extPageBackColor !== null && extPageBackColor.Alpha === 0)
				{
					pageBackColor = null; // Background is fully transparent - set Null to indicate that the background has been removed
				}
			}

			// Notice: Currently, page/footer join is global - it does not take page specific background colors into account
			if (pageMarginBottom === 0 && footerMarginTop === 0
				&& ((pageBackColor !== null && pageBackColor.Alpha > 0) || (pageBorderColor !== null && pageBorderColor.Alpha > 0))
				&& ((footerBackColor !== null && footerBackColor.Alpha > 0) || (footerBorderColor !== null && footerBorderColor.Alpha > 0)))
			{
				// Notice: CSS below will automatically be ignored if footer is removed from SMPages since html.SMPagesCustomFooter class will not be set

				css += "html.SMPagesCustomFooter." + ((pageType === "Page") ? "SMPagesViewer" : "SMIntegratedExtension") + " div.TPLContent";
				css += "{";
				css += "border-bottom-style: none;";
				css += "border-bottom-left-radius: 0px;";
				css += "border-bottom-right-radius: 0px;";
				css += "}";

				css += "html.SMPagesCustomFooter." + ((pageType === "Page") ? "SMPagesViewer" : "SMIntegratedExtension") + " div.TPLFooter";
				css += "{";
				css += "border-top-style: none;";
				css += "border-top-left-radius: 0px;";
				css += "border-top-right-radius: 0px;";
				css += "}";

				// Undo footer styling for pages with Card Layout

				if (pageType === "Page")
				{
					var footerBorder = SMDesigner.Helpers.GetBorderCss(editors["Footer"]["Borders and colors"]["Border"]);

					// If no border-radius overrides have been specified, then use default (see style.css)
					footerBorder = ((footerBorder !== null) ? footerBorder : "");
					footerBorder += ((footerBorder.indexOf("radius") === -1) ? "border-top-left-radius: 5px;border-top-right-radius: 5px;" : "");

					css += "html.SMPagesCustomFooter.SMPagesCardLayout div.TPLFooter";
					css += "{";
					css += footerBorder;
					css += "}";
				}
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

		var getActionButtons = function()
		{
			var css = "";

			// Borders
			var borderPrimary = SMDesigner.Helpers.GetBorderCss(editors["Action Buttons"]["Primary"]["Border"], true);
			var borderSecondary = SMDesigner.Helpers.GetBorderCss(editors["Action Buttons"]["Secondary"]["Border"], true);

			// Background color
			var backColorPrimary = SMDesigner.Helpers.GetColorCss(editors["Action Buttons"]["Primary"]["Background"]["Color"], "background-color", true);
			var backColorSecondary = SMDesigner.Helpers.GetColorCss(editors["Action Buttons"]["Secondary"]["Background"]["Color"], "background-color", true);

			// Text formatting
			var textPrimary = SMDesigner.Helpers.GetFontCss(editors["Action Buttons"]["Primary"]["Text formatting"]);
			var textSecondary = SMDesigner.Helpers.GetFontCss(editors["Action Buttons"]["Secondary"]["Text formatting"]);

			if (borderPrimary !== null || backColorPrimary !== null || textPrimary !== null)
			{
				css += "a.SMPagesActionButtonPrimary[class]";
				css += "{";
				css += ((borderPrimary !== null) ? borderPrimary : "");
				css += ((backColorPrimary !== null) ? backColorPrimary : "");
				css += ((textPrimary !== null) ? textPrimary : "");
				css += "}";
			}

			if (borderSecondary !== null || backColorSecondary !== null || textSecondary !== null)
			{
				css += "a.SMPagesActionButtonSecondary[class]";
				css += "{";
				css += ((borderSecondary !== null) ? borderSecondary : "");
				css += ((backColorSecondary !== null) ? backColorSecondary : "");
				css += ((textSecondary !== null) ? textSecondary : "");
				css += "}";
			}

			return css;
		}

		var getSystemLinks = function()
		{
			var links = SMDesigner.Helpers.GetFontCss(editors["System links"]["Text"]["Formatting"]);
			var linkColor = SMDesigner.Helpers.GetColorCss(editors["System links"]["Text"]["Formatting"]["Color"], "color", true);

			var css = "";

			if (links !== null)
			{
				css += "div.TPLLinks";
				css += "{";
				css += links;
				css += "}";
			}

			if (linkColor !== null)
			{
				css += "div.TPLLinks a";
				css += "{";
				css += linkColor;
				css += "}";
			}

			return css;
		}

		var getCustomElements = function(unrecognizedValues)
		{
			var allCss = "";

			var customs = parentWindow.document.querySelectorAll(".SMDesignerElement[data-id]");
			var title = null;
			var css = null;

			for (var i = 0 ; i < customs.length ; i++)
			{
				var title = "$ " + customs[i].getAttribute("data-id");
				var css = "";

				// Dimensions
				var width = SMDesigner.Helpers.GetDimensionCss(editors[title]["Dimensions"]["Dimensions"]["Width"], "width");
				var height = SMDesigner.Helpers.GetDimensionCss(editors[title]["Dimensions"]["Dimensions"]["Height"], "height");
				var display = SMDesigner.Helpers.GetControlValue(editors[title]["Dimensions"]["Dimensions"]["Type"]);

				// Indentation
				var margin = SMDesigner.Helpers.GetIndentationCss(editors[title]["Indentation"]["Margin"], "margin");
				var padding = SMDesigner.Helpers.GetIndentationCss(editors[title]["Indentation"]["Padding"], "padding");

				// Borders and colors
				var backgroundColor = SMDesigner.Helpers.GetColorCss(editors[title]["Borders and colors"]["Background"]["Color"], "background", true);
				var image = SMDesigner.Helpers.GetControlValue(editors[title]["Borders and colors"]["Background"]["Image"]);
				var border = SMDesigner.Helpers.GetBorderCss(editors[title]["Borders and colors"]["Border"], true);
				var shadow = SMDesigner.Helpers.GetShadowCss(editors[title]["Borders and colors"]["Shadow"]);

				// For background images located under files/images:
				// Use relative path when saving, since image will be referenced from templates/XYZ/style.css:
				// background: url("../../files/images/background/Sky.jpg");
				// During design time, this is not necessary since CSS is injected
				// into the page, meaning files are resolved from page root.
				if (saving === true && image !== null && image.indexOf(SMEnvironment.GetFilesDirectory() + "/images/") === 0)
					image = "../../" + image;

				// For background images located under templates/XYZ (e.g. generated-bg.jpg):
				// Use absolute path when making changes (live updates), since
				// image will be referenced from the page root (CSS is injected):
				// background: url("templates/Sunrise/generated-bg.jpg");
				// When saving, this is not necessary since the file will then be
				// referenced from templates/XYZ/style.css.
				if (saving === false && image !== null && image !== "" && image.indexOf(SMEnvironment.GetFilesDirectory() + "/images/") !== 0) // Assume images not starting with "files/images/" are located in template folder
					image = eventArgs.TemplatePath + "/" + image;

				// Text formatting
				var text = SMDesigner.Helpers.GetFontCss(editors[title]["Text"]["Formatting"]);
				var linkColor = SMDesigner.Helpers.GetColorCss(editors[title]["Text"]["Links"]["Color"], "color", true);

				// Wrap CSS in selectors

				var showOn = "";
				var selectors = "";

				for (var j = 1 ; j <= 5 ; j++)
				{
					showOn = SMDesigner.Helpers.GetControlValue(editors[title]["Styling rules"]["Restrict styling"]["Apply styling to page (" + j + ")"]);

					if (showOn !== null)
					{
						selectors += ((selectors !== "") ? "," : "") + "html.SMPagesPageId" + showOn + " .SMDesignerElement[data-id='" + customs[i].getAttribute("data-id") + "']";
					}
				}

				var defaultHideOnAllPages = (SMDesigner.Helpers.GetControlValue(editors[title]["Styling rules"]["Restrict styling"]["Hide on all other pages"]) === null && selectors !== "");
				if (defaultHideOnAllPages === true) // By default hide on all pages - using visibility rather than display:none to avoid having to determine and restore the display type (block vs inline-block vs inline)
					selectors = ".SMDesignerElement[data-id='" + customs[i].getAttribute("data-id") + "']{visibility:hidden;position:absolute;}" + selectors;

				css += ((selectors !== "") ? selectors : ".SMDesignerElement[data-id='" + customs[i].getAttribute("data-id") + "']");
				css += "{";
				css += ((width !== null) ? width : "");
				css += ((height !== null) ? height : "");
				css += ((display !== null) ? "display: " + display + ";" : "");
				css += ((margin !== null) ? margin : "");
				css += ((padding !== null) ? padding : "");
				css += ((backgroundColor !== null) ? backgroundColor : "");
				css += ((image !== null) ? "background-repeat: no-repeat; background-size: cover; background-attachment: fixed; background-image: url('" + image + "');" : "");
				css += ((border !== null) ? border : "");
				css += ((shadow !== null) ? shadow : "");
				css += ((text !== null) ? text : "");
				css += ((defaultHideOnAllPages === true) ? "visibility: visible;position: static;" : "");
				css += "}";

				if (linkColor !== null)
				{
					css += ".SMDesignerElement[data-id='" + customs[i].getAttribute("data-id") + "'] a,";
					css += "html.SMPagesEditor body .SMDesignerElement[data-id='" + customs[i].getAttribute("data-id") + "'] a"; // Needed to obtain greater specificity in Page Editor to overrule styling for Page
					css += "{";
					css += linkColor;
					css += "}";
				}

				// Preserve CSS for page specific custom elements that are not available on all pages.
				// <div class="SMDesignerElement" data-id="My custom element" data-preserve="true">Hello world</div>
				if (customs[i].getAttribute("data-preserve") === "true")
					editors[title]["PreservedCss"]["PreservedCss"]["PreservedCss"].Control.SetValue(css);

				allCss += css;
			}

			// Include preserved CSS for page specific custom elements that are not available on current page.
			// <div class="SMDesignerElement" data-id="My custom element" data-preserve="true">Hello world</div>
			var values = eventArgs.UnrecognizedValues;
			for (var editorSection in values)
			{
				if (editorSection.indexOf("$ ") === 0 && values[editorSection]["PreservedCss"] !== undefined)
				{
					allCss += values[editorSection]["PreservedCss"]["PreservedCss"]["PreservedCss"].Value;
				}
			}

			return allCss;
		}

		var getCustomCss = function()
		{
			var customCss = SMDesigner.Helpers.GetControlValue(editors["Advanced"]["Custom CSS"]["Custom CSS"]["CSS"]);

			if (customCss !== null)
				return customCss;

			return "";
		}

		var getCurrentPageId = function()
		{
			var id = null;

			for (var i = 0 ; i < SMDesigner.Resources.Pages.length ; i++)
			{
				id = SMDesigner.Resources.Pages[i].Id;

				if (parentWindow.document.querySelector("html.SMPagesPageId" + id) !== null)
					return id;
			}

			return null; // Designer not loaded on a content page
		}

		// Generate combined overrides and return result

		var all = "";

		all += getGeneral();
		all += getBackground();
		all += getHeader();
		all += getMenu();
		all += getSubMenus();
		all += getHeadings();
		all += getPage();
		all += getExtensionPage();
		all += getPageHeaderImages();
		all += getCards();
		all += getFluidGridCards();
		all += getSnippets();
		all += getFooter();
		all += getMenuPageJoin("Page");
		all += getMenuPageJoin("Extension page");
		all += getPageFooterJoin("Page");
		all += getPageFooterJoin("Extension page");
		all += getControls();
		all += getActionButtons();
		all += getSystemLinks();
		all += getTopMiddleBottom("Top");
		all += getTopMiddleBottom("Middle");
		all += getTopMiddleBottom("Bottom");
		all += getCustomElements(eventArgs.UnrecognizedValues);
		all += getCustomCss(); // MUST be last! Incomplete CSS may otherwise invalidate CSS that follows

		return all;
	}
})
