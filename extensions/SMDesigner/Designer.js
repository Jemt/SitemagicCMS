
// TODO:
//  - "Save as" to save template under a different name. Show dialog: Apply new template to: This page | Entire system
//  - Expert and Simple mode - lots of things are too difficult to understand for most people!
//  - Allow file uploads
//  - Language support (Designer Defition + Menu)
//  - Excitement factors:
//     - Cool speech buble menus
//     - CSS Animations
//     - Random background generator
//     - Gradient colors
//     - Text shadow (easy!)
//     - More fonts (Google WebFonts, offline - also in Editor = page specific!)

SMDesigner =
{
	// Dependencies to Sitemagic:
	//  1) Using SMClient (SMDom, SMCore, SMWindow, SMColor, SMBrowser, SMHttpRequest, etc.)
	//  2) Loads Internal Jquery and Jquery UI
	//  3) Uses internal Jquery UI "Sitemagic" CSS scope (Slider)

	Jquery: null,

	Resources: { WsSaveUrl: null, WsLoadUrl: null, WsGraphicsUrl: null, Files: [] },

	Designer: function(templatePath)
	{
		var me = this;
		var container = null;			// Designer: DOMElement containing content for Design Editor
		var toolbar = null;				// Toolbar: DOMElement containing toolbar controls (sections drop down and buttons)
		var lstSections = null;			// Toolbar: SMDesigner.Controls.Selector instance (sections, e.g. Header, Menu, or Page)
		var cmdDownload = null;			// Toolbar: Download button (span element)
		var cmdSave = null;				// Toolbar: Save button (span element)
		var cmdUndo = null;				// Toolbar: Undo button (span element)
		var cmdReset = null;			// Toolbar: Reset button (span element)
		var config = null;				// Config: Template configuration object with GetEditors(), GetCss(..), etc.
		var editors = null;				// Config: Array containing editor control configuration objects (config.GetEditors())
		var selectors = null;			// Config: Selector strings identifying editable blocks - for point and click (config.GetSelectors())
		var unrecognizedValues = null;	// Internal: Values which (no longer) have any associated controls
		var selectorsThread = -1;		// Internal: ID of scheduled task responsible for updating selectors - createSelectableAreas()
		var suppressUpdate = false;		// Internal: Suppress call to config.GetCss(..) if True
		var lockLayer = null;			// Internal: Loading indicator
		var isDirty = false;			// Internal: Flag indicating whether user has changed design (used by download button)

		// Initialization

		init();

		function init()
		{
			var parentWindow = window.opener || window.top; // Support both Dialog Mode and Legacy Mode (SMWindow)

			// Make sure browser is supported

			if (!document.querySelector || !window.JSON)
			{
				alert("Legacy browsers are not supported, please upgrade to a modern browser to use the designer");
				parentWindow.SMWindow.GetInstance(window.name).Close();
				return;
			}

			// Make sure user doesn't navigate away from designer by mistake

			// Only works in IE8 if registered on window rather than parentWindow.
			// But that won't work if designer is opened in LegacyMode.
			// IE9-10 fires OnBeforeUnload even for disabled links, causing the alert
			// to appear when e.g. the menu is clicked. Avoid this feature on Legacy IE.
			if (SMBrowser.GetBrowser() !== "MSIE" || SMBrowser.GetVersion() > 10)
			{
				parentWindow.onbeforeunload = function()
				{
					return "Are you sure you want to navigate away from designer?";
				}
			}

			// Disable all links and elements with an onclick handler to prevent user from
			// navigating away from page when clicking various elements (e.g. Menu) to style them.
			SMCore.ForEach(parentWindow.document.querySelectorAll("a, *[onclick]"), function(elm)
			{
				if (elm.tagName === "A")
					elm.href = "javascript:void(0)";

				elm.onclick = null;
			});

			// User may hold down mouse button, move mouse outside of Designer which is
			// hosted in an iFrame, and release the mouse button. When the mouse returns
			// to the iFrame, it will think the mouse button is still held down. This causes
			// a problem in ColorPicker and Slider which both continue to change the value
			// when moving the mouse around inside the iFrame.
			// To fix this we manually fire the MouseUp event when the mouse leaves the iFrame.
			if (SMBrowser.GetBrowser() !== "Firefox" && (SMBrowser.GetBrowser() !== "MSIE" || SMBrowser.GetVersion() > 8)) // Not necessary with Firefox and IE8
			{
				SMEventHandler.AddEventHandler(document, "mouseout", function(e)
				{
					var ev = e || window.event;
					var elm = ev.relatedElement || ev.toElement;

					if (!elm || elm.nodeName === "HTML") // Event constantly fires when leaving child elements
					{
						var me = document.createEvent("MouseEvents");
						me.initEvent("mouseup", true, false);
						document.activeElement.dispatchEvent(me);
					}
				});
			}

			// Remove override.css, we do not want changes to affect Designer

			var overrideCss = document.querySelector("link[href^='" + templatePath + "/override.css']")
			if (overrideCss !== null)
				overrideCss.parentNode.removeChild(overrideCss);

			// Set theme (Dark or Light)

			var defaultTheme = "LightTheme";

			if (SMCookie.GetCookie("SMDesignerTheme") === null)
				SMDom.AddClass(document.querySelector("html"), defaultTheme);
			else
				SMDom.AddClass(document.querySelector("html"), SMCookie.GetCookie("SMDesignerTheme"));

			// Allow user to change theme using F2 key

			SMEventHandler.AddEventHandler(document, "keydown", function(e)
			{
				var ev = e || window.event;

				if (ev.keyCode === 113) // F2 key
				{
					var currentTheme = ((SMCookie.GetCookie("SMDesignerTheme") !== null) ? SMCookie.GetCookie("SMDesignerTheme") : defaultTheme);
					var newTheme = ((currentTheme === "DarkTheme") ? "LightTheme" : "DarkTheme");

					SMDom.RemoveClass(document.querySelector("html"), currentTheme);
					SMDom.AddClass(document.querySelector("html"), newTheme);
					SMCookie.SetCookie("SMDesignerTheme", newTheme, 60 * 60 * 24 * 365);
				}
			});

			// Prevent scroll on parent page if opened in Dialog Mode

			var scroller = document.scrollingElement || document.body;
			scroller.onmousewheel = function(e)
			{
				var ev = e || window.event;
				var target = ev.srcElement || ev.target;

				if (!ev.preventDefault)
					return; // Not supported

				if (target.tagName === "TEXTAREA")
				{
					target.scrollTop -= ev.wheelDeltaY;
					target.scrollLeft -= ev.wheelDeltaX;
				}
				else
				{
					scroller.scrollTop -= ev.wheelDeltaY;
				}

				ev.preventDefault();
			}

			// Load graphics engine

			SMDesigner.Graphics.LoadResources();

			// Load jQuery
			// Notice: Controls depend on specific features in jQuery loaded by designer,
			// meaning the controls can't be moved to other projects without modifications.
			// E.g. Slider depends on jQuery UI plugin and ColorPicker depends on ColPick plugin.
			// However, having controls load individual jQuery instances with the plugins
			// they need would require more memory, so we'll stick with a shared instance.

			SMResourceManager.Jquery.LoadInternal(function($)
			{
				SMDesigner.Jquery = $;

				if (config !== null) // Initialize UI and load data if Template Designer Definition finished loading before jQuery (see further down below)
				{
					createEditorControls();
					loadData(null, function() // loadData(..) calls updateCss()
					{
						createSelectableAreas();
						renderUi();
					});

					/*createEditorControls();
					createSelectableAreas();
					renderUi();
					loadData(); // calls updateCss()*/
				}
			},
			// Load additional plugin(s) - must be compatible with version of jQuery used by Sitemagic!
			[SMEnvironment.GetExtensionsDirectory() + "/SMDesigner/plugins/colpick/js/colpick.js"], [SMEnvironment.GetExtensionsDirectory() + "/SMDesigner/plugins/colpick/css/colpick.css"]);

			// Load designer definition from template

			var req = new SMHttpRequest(templatePath + "/designer.js?ran=" + SMRandom.CreateGuid(), true);
			req.SetStateListener(function()
			{
				if (req.GetCurrentState() === 4 && req.GetHttpStatus() === 200)
				{
					config = eval(req.GetResponseText());
					editors = config.GetEditors.call(config, getEventArgs());

					if (SMDesigner.Jquery !== null) // JQuery may not be ready yet - JQuery loader callback takes care of initialization in that case!
					{
						createEditorControls();
						loadData(null, function() // loadData(..) calls updateCss()
						{
							createSelectableAreas();
							renderUi();
						});

						/*createEditorControls();
						createSelectableAreas();
						renderUi();
						loadData(); // calls updateCss()*/
					}
				}
				else if (req.GetCurrentState() === 4)
				{
					alert("Designer definition '" + templatePath + "/designer.js' not accessible");
				}
			});
			req.Start();
		}

		function createEditorControls()
		{
			for (var i in editors) // Level 1 (section drop down in toolbar)
			{
				for (var j in editors[i]) // Level 2 (accordion tab)
				{
					for (var k in editors[i][j]) // Level 3 (headline in accordion section)
					{
						for (var l in editors[i][j][k]) // Level 4 (property under headline - label and editor control)
						{
							// Create object for holding internal variables
							editors[i][j][k][l]._internal = {};

							// Adds Control property to configuration object with reference to control instance.
							// The property key may consist of both a display value and internal value separated by semicolon.
							createPropertyEditor(((l.indexOf(";") > -1) ? l.split(";")[0] : l), editors[i][j][k][l]);

							// Make sure initial value is accessible when control value is changed (used by updateLinkedControls(..))

							editors[i][j][k][l]._internal.PreviousValue = editors[i][j][k][l].Control.GetValue();

							if (editors[i][j][k][l].Selector || editors[i][j][k][l].Slider)
							{
								editors[i][j][k][l]._internal.PreviousValue += "|" + ((editors[i][j][k][l].Selector && editors[i][j][k][l].Selector.Control.GetValue() !== null) ? editors[i][j][k][l].Selector.Control.GetValue() : "");
								editors[i][j][k][l]._internal.PreviousValue += "|" + ((editors[i][j][k][l].Slider && editors[i][j][k][l].Slider.Control.GetValue() !== 0) ? editors[i][j][k][l].Slider.Control.GetValue().toString() : "");
							}
						}
					}
				}
			}

			if (config.OnEditorsCreated)
				config.OnEditorsCreated.call(config, getEventArgs()); // Allows developer to link controls once they are created
		}

		function createPropertyEditor(title, contrl)
		{
			var container = document.createElement("div");
			SMDom.AddClass(container, "Property");

			var label = document.createElement("div");
			label.innerHTML = title;

			var control = null;

			if (contrl.Type.toLowerCase() === "label")
			{
				contrl.Control = new SMDesigner.Controls.Label(contrl);
				control = contrl.Control.GetElement();
			}
			else if (contrl.Type.toLowerCase() === "button")
			{
				var cfg = {};
				cfg.Value = contrl.Value;
				cfg.Disabled = contrl.Disabled;
				cfg.OnChange = function(sender, value)
				{
					var cb = (contrl.OnChange ? contrl.OnChange : contrl.OnClick);

					if (cb)
						cb(sender, getEventArgs());
				}

				contrl.Control = new SMDesigner.Controls.Button(cfg);
				control = contrl.Control.GetElement();
			}
			else if (contrl.Type.toLowerCase() === "file")
			{
				var cfg = {};
				cfg.Items = (contrl.Items ? contrl.Items : []);
				cfg.AllowEmpty = true;
				cfg.Value = contrl.Value;
				cfg.Disabled = contrl.Disabled;
				cfg.OnChange = function(sender, value)
				{
					if (contrl.OnChange)
					{
						// Notice:
						// Control may have been set by control synchronization mechanism.
						// In that case we must make sure not to set suppressUpdate
						// to False, but keep it True. If we fail to do so, an infinite loop
						// will occure, and eventually a Stack Overflow Exception is thrown.

						var prevSuppressUpdate = suppressUpdate;
						suppressUpdate = true;
						contrl.OnChange(sender, value);
						suppressUpdate = prevSuppressUpdate;
					}

					contrl.Value = value;
					updateCss(contrl);
				}

				for (var i = 0 ; i < SMDesigner.Resources.Files.length ; i++)
					cfg.Items.push({ Value: SMDesigner.Resources.Files[i] });

				// Add value if not already found in items.
				// Selector control also adds missing values to the collection
				// of items, but if an OnChange handler is registered, it fires.
				// No need to cause events to fire unnecessarily.
				if (cfg.Value)
				{
					var valueExists = false;

					for (var i = 0 ; i < cfg.Items.length ; i++)
					{
						if (cfg.Items[i].Value === cfg.Value)
						{
							valueExists = true;
							break;
						}
					}

					if (valueExists === false)
						cfg.Items.push({ Value: cfg.Value });
				}

				contrl.Control = new SMDesigner.Controls.Selector(cfg);
				control = contrl.Control.GetElement();
			}
			else if (contrl.Type.toLowerCase() === "page")
			{
				var cfg = {};
				cfg.Items = (contrl.Items ? contrl.Items : []);
				cfg.AllowEmpty = true;
				cfg.Value = contrl.Value;
				cfg.Disabled = contrl.Disabled;
				cfg.OnChange = function(sender, value)
				{
					if (contrl.OnChange)
					{
						// Notice:
						// Control may have been set by control synchronization mechanism.
						// In that case we must make sure not to set suppressUpdate
						// to False, but keep it True. If we fail to do so, an infinite loop
						// will occure, and eventually a Stack Overflow Exception is thrown.

						var prevSuppressUpdate = suppressUpdate;
						suppressUpdate = true;
						contrl.OnChange(sender, value);
						suppressUpdate = prevSuppressUpdate;
					}

					contrl.Value = value;
					updateCss(contrl);
				}

				for (var i = 0 ; i < SMDesigner.Resources.Pages.length ; i++)
					cfg.Items.push({ Title: SMDesigner.Resources.Pages[i].Filename, Value: SMDesigner.Resources.Pages[i].Id });

				// Add value if not already found in items.
				// Selector control also adds missing values to the collection
				// of items, but if an OnChange handler is registered, it fires.
				// No need to cause events to fire unnecessarily.
				if (cfg.Value)
				{
					var valueExists = false;

					for (var i = 0 ; i < cfg.Items.length ; i++)
					{
						if (cfg.Items[i].Value === cfg.Value)
						{
							valueExists = true;
							break;
						}
					}

					if (valueExists === false)
						cfg.Items.push({ Value: cfg.Value });
				}

				contrl.Control = new SMDesigner.Controls.Selector(cfg);
				control = contrl.Control.GetElement();
			}
			else if (contrl.Type.toLowerCase() === "selector")
			{
				var cfg = {};
				cfg.AllowEmpty = (contrl.AllowEmpty === true);
				cfg.Items = contrl.Items;
				cfg.Value = contrl.Value;
				cfg.Disabled = contrl.Disabled;
				cfg.OnChange = function(sender, value)
				{
					if (contrl.OnChange)
					{
						// Notice:
						// Control may have been set by control synchronization mechanism.
						// In that case we must make sure not to set suppressUpdate
						// to False, but keep it True. If we fail to do so, an infinite loop
						// will occure, and eventually a Stack Overflow Exception is thrown.

						var prevSuppressUpdate = suppressUpdate;
						suppressUpdate = true;
						contrl.OnChange(sender, value);
						suppressUpdate = prevSuppressUpdate;
					}

					contrl.Value = value;
					updateCss(contrl);
				}

				contrl.Control = new SMDesigner.Controls.Selector(cfg);
				control = contrl.Control.GetElement();
			}
			else if (contrl.Type.toLowerCase() === "color")
			{
				var cfg = {};
				cfg.Value = contrl.Value;
				cfg.Disabled = contrl.Disabled;
				cfg.OnChange = function(sender, value)
				{
					if (contrl.OnChange)
					{
						// Notice:
						// Control may have been set by control synchronization mechanism.
						// In that case we must make sure not to set suppressUpdate
						// to False, but keep it True. If we fail to do so, an infinite loop
						// will occure, and eventually a Stack Overflow Exception is thrown.

						var prevSuppressUpdate = suppressUpdate;
						suppressUpdate = true;
						contrl.OnChange(sender, value);
						suppressUpdate = prevSuppressUpdate;
					}

					contrl.Value = value;
					updateCss(contrl);
				}

				contrl.Control = new SMDesigner.Controls.ColorPicker(cfg);
				control = contrl.Control.GetElement();
			}
			else if (contrl.Type.toLowerCase() === "slider")
			{
				var cfg = {};
				cfg.Value = contrl.Value;
				cfg.Min = contrl.Min;
				cfg.Max = contrl.Max;
				cfg.Step = contrl.Step;
				cfg.Disabled = contrl.Disabled;
				cfg.OnChange = function(sender, value)
				{
					if (contrl.OnChange)
					{
						// Notice:
						// Control may have been set by control synchronization mechanism.
						// In that case we must make sure not to set suppressUpdate
						// to False, but keep it True. If we fail to do so, an infinite loop
						// will occure, and eventually a Stack Overflow Exception is thrown.

						var prevSuppressUpdate = suppressUpdate;
						suppressUpdate = true;
						contrl.OnChange(sender, value);
						suppressUpdate = prevSuppressUpdate;
					}

					contrl.Value = value;
					updateCss(contrl);
				}

				contrl.Control = new SMDesigner.Controls.Slider(cfg);
				control = contrl.Control.GetElement();
			}
			else // input
			{
				var cfg = {};
				cfg.Value = contrl.Value;
				cfg.Disabled = contrl.Disabled;
				cfg.MultiLine = contrl.MultiLine;
				cfg.OnChange = function(sender, value)
				{
					if (contrl.OnChange)
					{
						// Notice:
						// Control may have been set by control synchronization mechanism.
						// In that case we must make sure not to set suppressUpdate
						// to False, but keep it True. If we fail to do so, an infinite loop
						// will occure, and eventually a Stack Overflow Exception is thrown.

						var prevSuppressUpdate = suppressUpdate;
						suppressUpdate = true;
						contrl.OnChange(sender, value);
						suppressUpdate = prevSuppressUpdate;
					}

					contrl.Value = value;
					updateCss(contrl);
				}

				if (contrl.Selector)
				{
					var selectorCfg = {};
					selectorCfg.Items = contrl.Selector.Items;
					selectorCfg.Value = contrl.Selector.Value;
					selectorCfg.Disabled = contrl.Disabled;
					selectorCfg.AllowEmpty = (contrl.Selector.AllowEmpty === true);
					selectorCfg.OnChange = function(sender, value)
					{
						if (contrl.Selector.OnChange)
						{
							// Notice:
							// Control may have been set by control synchronization mechanism.
							// In that case we must make sure not to set suppressUpdate
							// to False, but keep it True. If we fail to do so, an infinite loop
							// will occure, and eventually a Stack Overflow Exception is thrown.

							var prevSuppressUpdate = suppressUpdate;
							suppressUpdate = true;
							contrl.Selector.OnChange(sender, value);
							suppressUpdate = prevSuppressUpdate;
						}

						contrl.Selector.Value = value;
						updateCss(contrl);
					}

					contrl.Selector.Control = new SMDesigner.Controls.Selector(selectorCfg);
					cfg.Selector = contrl.Selector.Control;
				}

				if (contrl.Slider)
				{
					var sliderCfg = {};
					sliderCfg.Value = contrl.Slider.Value;
					sliderCfg.Min = contrl.Slider.Min;
					sliderCfg.Max = contrl.Slider.Max;
					sliderCfg.Step = contrl.Slider.Step;
					sliderCfg.Disabled = contrl.Disabled;
					sliderCfg.OnChange = function(sender, value)
					{
						if (contrl.Slider.OnChange)
						{
							// Notice:
							// Control may have been set by control synchronization mechanism.
							// In that case we must make sure not to set suppressUpdate
							// to False, but keep it True. If we fail to do so, an infinite loop
							// will occure, and eventually a Stack Overflow Exception is thrown.

							var prevSuppressUpdate = suppressUpdate;
							suppressUpdate = true;
							contrl.Slider.OnChange(sender, value);
							suppressUpdate = prevSuppressUpdate;
						}

						contrl.Slider.Value = value;
						updateCss(contrl);
					}

					contrl.Slider.Control = new SMDesigner.Controls.Slider(sliderCfg);
					cfg.Slider = contrl.Slider.Control;
				}

				input = new SMDesigner.Controls.Input(cfg);
				contrl.Control = input;
				control = input.GetElement();
			}

			SMDom.AddClass(control, "Control");

			container.appendChild(label);
			container.appendChild(control);

			contrl._internal.Element = container;
		}

		// Selectable areas

		function createSelectableAreas()
		{
			// Allow user to change styles for elements in design
			// by simply clicking directly on the elements. The
			// designer opens the associated section in the drop
			// down menu in the toolbar.

			var parentWindow = window.opener || window.top;

			if (selectors === null) // First call
			{
				var css = "";
				css += "\n .ActiveElement,";
				css += "\n .SelectableElement:hover";
				css += "\n {";
				css += "\n      outline: 3px dashed red;";
				css += "\n }";

				// Outline is buggy in Firefox: http://reference.sitepoint.com/css/outline
				// Using shadow behind elements to highlight them instead.
				css += "\n @-moz-document url-prefix()";
				css += "\n {";
				css += "\n      .ActiveElement,";
				css += "\n      .SelectableElement:hover";
				css += "\n      {";
				css += "\n          outline: none;";
				css += "\n          box-shadow: 0px 0px 0px 3px red !important;";
				css += "\n      }";
				css += "\n }";

				var style = parentWindow.document.createElement("style");
				style.type = "text/css";
				parentWindow.document.getElementsByTagName("head")[0].appendChild(style);

				if (style.styleSheet)
					style.styleSheet.cssText = css; // IE8 - element MUST be appended first!
				else
					style.appendChild(document.createTextNode(css));

				SMEventHandler.AddEventHandler(parentWindow.document, "click", function(e)
				{
					if (!window || !window.SMCore) // Window closed (window becomes null in Chrome while window.SMCore becomes undefined in other browsers)
						return;

					var ev = e || window.event;
					var target = ev.target || ev.srcElement;

					var isSelectable = (SMDom.HasClass(target, "SelectableElement") === true);

					while (/*target !== null &&*/ isSelectable === false && target.tagName !== "HTML")
					{
						if (!target.parentElement)
							return; // Happens if element clicked was removed (e.g. button in a dialog that closes)

						isSelectable = (target.parentElement.getAttribute("data-selelmkey") !== null)
						target = target.parentElement;
					}

					if (isSelectable === true)
					{
						var elmKey = target.getAttribute("data-selelmkey");
						var accordionKey = null;

						if (elmKey.indexOf(";") > -1)
						{
							var info = elmKey.split(";");

							elmKey = info[0];
							accordionKey = info[1];
						}

						lstSections.SetValue(elmKey);

						if (accordionKey !== null)
							document.querySelector("h3[data-title='" + accordionKey + "']").click();

						if (ev.stopPropagation)
							ev.stopPropagation();
						ev.cancelBubble = true;
					}
				});
			}

			if (selectors !== null)
			{
				SMCore.ForEach(selectors, function(key)
				{
					var elms = parentWindow.document.querySelectorAll(selectors[key]);

					SMCore.ForEach(elms, function(elm)
					{
						SMDom.RemoveClass(elm, "SelectableElement");
						elm.removeAttribute("data-selelmkey");
					});
				});
			}

			selectors = [];

			if (config.GetSelectors)
			{
				selectors = config.GetSelectors.call(config, getEventArgs());
				selectors = ((selectors !== null) ? selectors : []); // Make sure initial configuration done above does not happen multiple times
			}

			var exclude = [];
			if (config.GetExclusion)
				exclude = config.GetExclusion.call(config, getEventArgs());

			SMCore.ForEach(selectors, function(key)
			{
				if (SMCore.GetIndex(exclude, key) !== -1)
					return; // Skip

				var elms = parentWindow.document.querySelectorAll(selectors[key]);
				SMCore.ForEach(elms, function(elm)
				{
					// Do not apply to BODY and HTML element since mouse is always hovering these elements
					if (elm !== parentWindow.document.body && elm !== parentWindow.document.body.parentNode)
						SMDom.AddClass(elm, "SelectableElement");

					elm.setAttribute("data-selelmkey", key);
				});
			});

			// Reload page when designer is closed to get rid of styles and click handlers registered above.

			// Notice: Approach disabled below works great when designer is opened in Dialog Mode (SMWindow).
			// But in Legacy Mode the callback function's execution context (closure) is destroyed
			// when the popup window is closed, which prevents the function from executing.
			// Instead we use eval() to define the callback on the parent window, which moves the
			// execution context to the parent window.
			// parentWindow.SMWindow.GetInstance(window.name).SetOnCloseCallback(function() { parentWindow.onbeforeunload = null; parentWindow.location.href = parentWindow.location.href; });

			// This works as expected in both Dialog Mode and Legacy Mode
			parentWindow.eval("SMWindow.GetInstance('" + window.name + "').SetOnCloseCallback(function() { window.onbeforeunload = null; location.href = location.href; });");
		}

		function flash(elm, count)
		{
			if (count === 4)
				return;

			setTimeout(function()
			{
				if (!window || !window.SMCore) // Window closed (window becomes null in Chrome while window.SMCore becomes undefined in other browsers)
					return;

				if (SMDom.HasClass(elm, "ActiveElement") === false)
					SMDom.AddClass(elm, "ActiveElement");
				else
					SMDom.RemoveClass(elm, "ActiveElement");

				flash(elm, ((count !== undefined) ? count + 1 : 1));
			}, ((count === undefined) ? 0 : ((count % 2 === 1) ? 700 : 400) ));
		}

		// Designer user interface

		function renderUi()
		{
			// This function is used to initially render the UI, meaning the toolbar,
			// accordion, and controls. It is also used to update the UI when another
			// section is selected in the sections drop down, or selected by "point and
			// click" directly in the design.

			// Create toolbar

			var initialLoad = (container === null);

			if (initialLoad === true)
				createToolbar();

			// Create container

			if (initialLoad === false) // Remove container containing controls for previously selected section
				container.parentNode.removeChild(container);

			container = document.createElement("div");
			SMDom.AddClass(container, "Container");
			SMDom.AddClass(container, "Sitemagic"); // jQuery UI CSS scope (Sitemagic)
			document.body.appendChild(container);

			container.appendChild(toolbar);

			// Add accordion element

			var accordion = document.createElement("div");
			SMDom.AddClass(accordion, "Accordion");

			// Add sections/tabs to accordion

			var section = lstSections.GetValue();
			var accordionTabs = editors[section];
			var tabCount = 0;

			SMCore.ForEach(accordionTabs, function(accordionTab)
			{
				if (checkControlsHidden(accordionTabs[accordionTab]) === true)
					return;

				var t = createTab(accordionTab);
				accordion.appendChild(t.Header);
				accordion.appendChild(t.Content);

				tabCount++;

				// Add headlines

				SMCore.ForEach(accordionTabs[accordionTab], function(headlineSection)
				{
					if (checkControlsHidden(accordionTabs[accordionTab][headlineSection]) === true)
						return;

					var h = createHeadline(headlineSection);
					t.Content.appendChild(h.Header);
					t.Content.appendChild(h.Content);

					// Add properties (labels + editor controls)

					SMCore.ForEach(accordionTabs[accordionTab][headlineSection], function(prop)
					{
						if (accordionTabs[accordionTab][headlineSection][prop].Hidden === true)
							return;

						if (accordionTabs[accordionTab][headlineSection][prop].Selector && accordionTabs[accordionTab][headlineSection][prop].Selector.Hidden === true)
						{
							accordionTabs[accordionTab][headlineSection][prop].Selector.Control.GetElement().style.display = "none";
						}
						if (accordionTabs[accordionTab][headlineSection][prop].Slider && accordionTabs[accordionTab][headlineSection][prop].Slider.Hidden === true)
						{
							accordionTabs[accordionTab][headlineSection][prop].Slider.Control.GetElement().style.display = "none";
						}

						var p = accordionTabs[accordionTab][headlineSection][prop]._internal.Element;
						h.Content.appendChild(p);
					});
				});
			});

			// Transform accordion element into visual accordion representation

			var accordionConfig = { heightStyle: "content", collapsible: true };

			if (initialLoad === false && tabCount > 1)
				accordionConfig.active = false; // False = collapse all tabs

			container.appendChild(accordion);
			SMDesigner.Jquery("div.Accordion").accordion(accordionConfig);

			// Flash selected section within design

			if (selectors && selectors[section])
			{
				var parentWindow = window.opener || window.top;

				var elms = parentWindow.document.querySelectorAll(selectors[section]);
				SMCore.ForEach(elms, function(el)
				{
					flash(el);
				});
			}

			// Notify designer definition about what editors are being edited.
			// This allows for definition to e.g. open a drop down menu to make
			// it visible while styling it.

			if (config.OnEditorsDisplayed)
				config.OnEditorsDisplayed.call(config, getEventArgs());
		}

		function checkControlsHidden(objArr)
		{
			for (var prop in objArr)
			{
				if (!objArr[prop].Control)
				{
					if (checkControlsHidden(objArr[prop]) === false)
						return false;
				}
				else if (objArr[prop].Hidden !== true)
				{
					return false;
				}
			}

			return true;
		}

		function createToolbar()
		{
			// Create toolbar

			toolbar = document.createElement("div");
			SMDom.AddClass(toolbar, "Toolbar");

			// Create section drop down

			var selectorCfg = {};
			selectorCfg.AllowEmpty = false;
			selectorCfg.Items = [];
			selectorCfg.Sort = true;
			selectorCfg.OnChange = function(sender, value)
			{
				if (!window.SMCore) // IE Bugfix - OnChange is fired when designer is closed, at which point page is disposed
					return;

				renderUi();
			}

			var exclude = [];
			if (config.GetExclusion)
				exclude = config.GetExclusion.call(config, getEventArgs());

			SMCore.ForEach(editors, function(section)
			{
				if (SMCore.GetIndex(exclude, section) === -1)
				{
					if (section.indexOf(";") > -1)
					{
						var info = section.split(";"); // 0 = Title, 1 = Unique ID
						selectorCfg.Items.push({ Title: info[0], Value: section });
					}
					else
					{
						selectorCfg.Items.push({ Value: section });
					}
				}
			});

			var defaultSection = ((config.GetDefaultSection) ? config.GetDefaultSection.call(config, getEventArgs()) : null);
			if (defaultSection !== null)
				selectorCfg.Value = defaultSection;

			lstSections = new SMDesigner.Controls.Selector(selectorCfg);
			toolbar.appendChild(lstSections.GetElement());

			// Create buttons

			var buttonPanel = document.createElement("div");
			SMDom.AddClass(buttonPanel, "ButtonPanel");
			toolbar.appendChild(buttonPanel);

			cmdReset = document.createElement("span");
			SMDom.AddClass(cmdReset, "Button");
			SMDom.AddClass(cmdReset, "Reset");
			cmdReset.innerHTML = "Reset";
			cmdReset.onclick = function()
			{
				if (SMMessageDialog.ShowConfirmDialog("Restore back to original design?") === true)
					reset();
			}
			buttonPanel.appendChild(cmdReset);

			cmdUndo = document.createElement("span");
			SMDom.AddClass(cmdUndo, "Button");
			SMDom.AddClass(cmdUndo, "Undo");
			cmdUndo.innerHTML = "Undo";
			cmdUndo.onclick = function()
			{
				if (SMMessageDialog.ShowConfirmDialog("Undo changes since last time design was saved?") === true)
					reload();
			}
			buttonPanel.appendChild(cmdUndo);

			cmdSave = document.createElement("span");
			SMDom.AddClass(cmdSave, "Button");
			SMDom.AddClass(cmdSave, "Save");
			cmdSave.innerHTML = "Save";
			cmdSave.onclick = function()
			{
				saveData();
			}
			buttonPanel.appendChild(cmdSave);

			if (SMDesigner.Resources.WsDownloadUrl) // WsDownloadUrl is only set if download is supported on server
			{
				var cmdDownload = document.createElement("span");
				SMDom.AddClass(cmdDownload, "Button");
				SMDom.AddClass(cmdDownload, "Download");
				cmdDownload.innerHTML = "";
				cmdDownload.onclick = function()
				{
					downloadData();
				}
				buttonPanel.appendChild(cmdDownload);
			}
		}

		function createTab(title)
		{
			var header = document.createElement("h3"); // h3 required by jQuery UI accordion
			header.setAttribute("data-title", title);
			header.innerHTML = title;

			var content = document.createElement("div");

			return { Header: header, Content: content };
		}

		function createHeadline(title)
		{
			var header = document.createElement("h4"); // h4 might as well be used when accordion requires h3 (see createTab(..))
			header.innerHTML = title;

			var content = document.createElement("div");

			return { Header: header, Content: content };
		}

		// Control synchronization

		function updateLinkedControls(senderCfg)
		{
			// Synchronize value change from source control (master) to target control (slave) if synchronization has been configured

			if (!senderCfg.Sync || senderCfg.Sync.length === 0)
				return;

			if ((!senderCfg.Type || senderCfg.Type.toLowerCase() === "input") && (senderCfg.Selector || senderCfg.Slider))
			{
				// Combined Input + Selector control:
				// Synchronize value change from combined Input and Selector control (source/master) to target control(s) (slave(s)) of same type

				var senderPreviousValue = senderCfg._internal.PreviousValue;
				senderCfg._internal.PreviousValue = ((senderCfg.Control.GetValue() !== null) ? senderCfg.Control.GetValue() : "");
				senderCfg._internal.PreviousValue += "|" + ((senderCfg.Selector && senderCfg.Selector.Control.GetValue() !== null) ? senderCfg.Selector.Control.GetValue() : "");
				senderCfg._internal.PreviousValue += "|" + ((senderCfg.Slider && senderCfg.Slider.Control.GetValue() !== 0) ? senderCfg.Slider.Control.GetValue().toString() : "");

				// Bidirectional: 0					- Keep both controls in sync. at all times
				// UnidirectionalAlways: 1			- Always sync. value of Control1 to Control2
				// UnidirectionalEqualsOrNull: 2	- Sync. value of Control1 to Control2 if its current value is either Null or equal to value of Control1
				// UnidirectionalEquals: 3			- Sync. value of Control1 to Control2 if its current value is equal to value of Control1

				SMCore.ForEach(senderCfg.Sync, function(sync)
				{
					var combinedCurrentValue = ((sync.Target.Control.GetValue() !== null) ? sync.Target.Control.GetValue() : "");
					combinedCurrentValue += "|" + ((senderCfg.Selector && sync.Target.Selector.Control.GetValue() !== null) ? sync.Target.Selector.Control.GetValue() : "");
					combinedCurrentValue += "|" + ((senderCfg.Slider && sync.Target.Slider.Control.GetValue() !== 0) ? sync.Target.Slider.Control.GetValue().toString() : "");

					var syncType = ((sync.Type === SMDesigner.Helpers.SyncType.UnidirectionalEqual) ? SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull : sync.Type); // Backward compability

					if (syncType === SMDesigner.Helpers.SyncType.Bidirectional || syncType === SMDesigner.Helpers.SyncType.UnidirectionalAlways // Bidirectional or UnidirectionalAlways
						|| (syncType === SMDesigner.Helpers.SyncType.UnidirectionalEquals && senderPreviousValue === combinedCurrentValue) // UnidirectionalEquals
						|| (syncType === SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull && (senderPreviousValue === combinedCurrentValue || combinedCurrentValue === "||"))) // UnidirectionalEqualsOrNull
					{
						sync.Target.Control.SetValue(senderCfg.Control.GetValue());

						if (senderCfg.Selector)
							sync.Target.Selector.Control.SetValue(senderCfg.Selector.Control.GetValue());

						if (senderCfg.Slider)
							sync.Target.Slider.Control.SetValue(senderCfg.Slider.Control.GetValue());

						sync.Target._internal.PreviousValue = senderCfg._internal.PreviousValue;
					}
				});
			}
			else
			{
				// All other control types:
				// Synchronize value change from all other control types (source/master) to target control(s) (slave(s))

				var senderPreviousValue = senderCfg._internal.PreviousValue;
				senderCfg._internal.PreviousValue = senderCfg.Control.GetValue();

				SMCore.ForEach(senderCfg.Sync, function(sync)
				{
					var syncType = ((sync.Type === SMDesigner.Helpers.SyncType.UnidirectionalEqual) ? SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull : sync.Type); // Backward compability

					if (syncType === SMDesigner.Helpers.SyncType.Bidirectional || syncType === SMDesigner.Helpers.SyncType.UnidirectionalAlways // Bidirectional or UnidirectionalAlways
						|| (syncType === SMDesigner.Helpers.SyncType.UnidirectionalEquals && SMCore.IsEqual(sync.Target.Control.GetValue(), senderPreviousValue) === true) // UnidirectionalEquals
						|| (syncType === SMDesigner.Helpers.SyncType.UnidirectionalEqualsOrNull && (SMCore.IsEqual(sync.Target.Control.GetValue(), senderPreviousValue) === true || sync.Target.Control.GetValue() === "" || sync.Target.Control.GetValue() === null))) // UnidirectionalEqualsOrNull
					{
						sync.Target.Control.SetValue(((sync.Target.Type.toLowerCase() === "selector" && sync.Mapping && sync.Mapping[senderCfg.Control.GetValue()] !== undefined) ? sync.Mapping[senderCfg.Control.GetValue()] : senderCfg.Control.GetValue()));
						sync.Target._internal.PreviousValue = senderCfg._internal.PreviousValue;
					}
				});
			}
		}

		// On-the-fly design updator

		function updateCss(senderCfg)
		{
			if (suppressUpdate === true)
				return;

			isDirty = (senderCfg !== undefined); // senderCfg is a control which is set when the user updates the design

			suppressUpdate = true; // Prevent infinite loop when synchronizing control values, or if code in designer.js calls SetValue(..) on a control which would otherwise cause updateCss() to be called again

			// Synchronize controls

			if (senderCfg) // Not set when initially loading (or reloading) and applying CSS - see loadData()
				updateLinkedControls(senderCfg);

			// Get CSS from Designer Definition

			var eventArgs = getEventArgs();
			eventArgs.Sender = (senderCfg ? senderCfg : null);

			var css = config.GetCss.call(config, eventArgs);

			suppressUpdate = false;

			if (typeof(css) !== "string") // So only an empty string can clear changes! Returning e.g. null/undefined/number/object/array will cancel changes, but NOT revert control values!!
				return;

			// Inject generated CSS into design

			var parentWindow = window.opener || window.top;

			var style = (window.opener || window.top).document.createElement("style");
			style.type = "text/css";
			style.id = "SMDesignerStyle";

			var previousOverride = (window.opener || window.top).document.getElementById(style.id);

			parentWindow.document.getElementsByTagName("head")[0].appendChild(style);

			if (style.styleSheet)
				style.styleSheet.cssText = css; // IE8 - element MUST be appended first!
			else
				style.appendChild(document.createTextNode(css));

			// Remove previously added overrides - done at the end to prevent FOUC (Flash Of Unstyled Content)

			if (previousOverride)
				parentWindow.document.getElementsByTagName("head")[0].removeChild(previousOverride);

			// Update selectable areas in case they change together with CSS settings.
			// For instance a given area could become selectable if certain styling is applied, e.g. visibility.

			if (selectorsThread !== -1)
			{
				clearTimeout(selectorsThread);
				selectorsThread = -1;
			}

			// Schedule - updateCss(..) may be called very frequently while using e.g. Color Picker or Slider
			selectorsThread = setTimeout(function()
			{
				if (!window || !window.SMCore) // Window closed (window becomes null in Chrome while window.SMCore becomes undefined in other browsers)
					return;

				createSelectableAreas();
			}, 250);
		}

		// Data transport

		function downloadData()
		{
			// Warn about unsaved changes which will not be included in download

			if (isDirty === true)
			{
				var res = confirm("Template contains unsaved changes!\nSave changes before downloading template to include recent changes.\nPress OK to download template without changes, or Cancel to return to Designer without downloading template.");

				if (res === false)
					return;
			}

			// Ask for new template name

			var newTemplateName = "MyTemplate"; //templatePath.substring(templatePath.lastIndexOf("/") + 1);
			var forceAsk = true;

			while ((forceAsk === true) || (newTemplateName !== null && newTemplateName !== "" && /^[a-z0-9]+$/i.test(newTemplateName) === false))
			{
				newTemplateName = prompt("Download design template:\nEnter new template name (A-Z, 0-9)" + ((forceAsk === false) ? "\n\nError: Invalid template name entered, please try again" : ""), newTemplateName);
				forceAsk = false;
			}

			if (newTemplateName === null || newTemplateName === "")
				return;

			// Download template to user

			location.href = SMDesigner.Resources.WsDownloadUrl + "&TemplatePath=" + templatePath + "&TemplateName=" + newTemplateName;
		}

		function saveData()
		{
			var eventArgs = getEventArgs();

			// Fire OnBeforeSave event

			if (config.OnBeforeSave)
			{
				if (config.OnBeforeSave.call(config, eventArgs) === false)
					return;
			}

			// Generate CSS - this value is used to generate override.css which is loaded on every page

			suppressUpdate = true;

			eventArgs.Saving = true;
			var css = config.GetCss.call(config, eventArgs);

			if (typeof(css) !== "string")
				return;

			suppressUpdate = false;

			// Get dirty values (changes) - these values are used to restore control values in Designer - see loadData()

			var values = {};

			for (var editorSection in editors)
			{
				for (var accordionSection in editors[editorSection])
				{
					for (var headlineSection in editors[editorSection][accordionSection])
					{
						for (var prop in editors[editorSection][accordionSection][headlineSection])
						{
							var property = editors[editorSection][accordionSection][headlineSection][prop];

							if (property.NoSave === true)
								continue;

							if (property.Control.IsDirty() === true || (property.Selector && property.Selector.Control.IsDirty() === true) || (property.Slider && property.Slider.NoSave !== true && property.Slider.Control.IsDirty() === true))
							{
								if (values[editorSection] === undefined)
									values[editorSection] = {};
								if (values[editorSection][accordionSection] === undefined)
									values[editorSection][accordionSection] = {};
								if (values[editorSection][accordionSection][headlineSection] === undefined)
									values[editorSection][accordionSection][headlineSection] = {};

								values[editorSection][accordionSection][headlineSection][prop] = {};

								if (property.Control.IsDirty() === true)
									values[editorSection][accordionSection][headlineSection][prop] = { Value: property.Control.GetValue() };
								if (property.Selector && property.Selector.Control.IsDirty() === true)
									values[editorSection][accordionSection][headlineSection][prop].Selector = { Value: property.Selector.Control.GetValue() };
								if (property.Slider && property.Slider.NoSave !== true && property.Slider.Control.IsDirty() === true) // Supporting NoSave in case Slider is used to set Input value
									values[editorSection][accordionSection][headlineSection][prop].Slider = { Value: property.Slider.Control.GetValue() };
							}
						}
					}
				}
			}

			for (var editorSection in unrecognizedValues)
			{
				for (var accordionSection in unrecognizedValues[editorSection])
				{
					for (var headlineSection in unrecognizedValues[editorSection][accordionSection])
					{
						for (var prop in unrecognizedValues[editorSection][accordionSection][headlineSection])
						{
							var property = unrecognizedValues[editorSection][accordionSection][headlineSection][prop];

							if (property.Preserve !== true)
								continue;

							if (values[editorSection] === undefined)
								values[editorSection] = {};
							if (values[editorSection][accordionSection] === undefined)
								values[editorSection][accordionSection] = {};
							if (values[editorSection][accordionSection][headlineSection] === undefined)
								values[editorSection][accordionSection][headlineSection] = {};

							values[editorSection][accordionSection][headlineSection][prop] = {};

							if (property.Value !== undefined)
								values[editorSection][accordionSection][headlineSection][prop].Value = property.Value;
							if (property.Selector)
								values[editorSection][accordionSection][headlineSection][prop].Selector = { Value: property.Selector.Value };
							if (property.Slider)
								values[editorSection][accordionSection][headlineSection][prop].Slider = { Value: property.Slider.Value };
						}
					}
				}
			}

			// Send data to server

			var req = new SMHttpRequest(SMDesigner.Resources.WsSaveUrl, true);
			req.SetData("Config=" + encodeURIComponent(JSON.stringify(values)) + "&Css=" + encodeURIComponent(css) + "&TemplatePath=" + templatePath);
			req.SetStateListener(function()
			{
				if (req.GetCurrentState() === 4 && req.GetHttpStatus() === 200)
				{
					// Temporarily show checkmark when data is successfully saved

					SMDom.AddClass(cmdSave, "Saved");
					setTimeout(function()
					{
						SMDom.RemoveClass(cmdSave, "Saved");
					}, 1500);
				}
				else if (req.GetCurrentState() === 4)
				{
					alert("Error occured saving changes, please try again!");
				}
			});
			req.Start();

			// Save operation may have caused changes to controls (caused by designer definition) - update design for consistent view

			updateCss();
		}

		function loadData(additionalData, cb)
		{
			var req = new SMHttpRequest(SMDesigner.Resources.WsLoadUrl + "&ran=" + SMRandom.CreateGuid(), true);
			req.SetData("TemplatePath=" + templatePath + (additionalData ? "&" + additionalData : ""));
			req.SetStateListener(function()
			{
				if (req.GetCurrentState() === 4 && req.GetHttpStatus() === 200)
				{
					suppressUpdate = true; // Assigning values to controls trigger updateCss() every time - suppress it!

					var values = req.GetResponseJson();
					values = ((values !== null) ? values : {});

					unrecognizedValues = {};

					for (var editorSection in values)
					{
						for (var accordionSection in values[editorSection])
						{
							for (var headlineSection in values[editorSection][accordionSection])
							{
								for (var prop in values[editorSection][accordionSection][headlineSection])
								{
									// Make sure editor control still exists (in case it was removed in a more recent version of Designer Definition,
									// or the given element only exists on a particular page, in which case it may not always have controls created).
									// Notice: We do not store control type information, so changing a control type may cause an error if the new control
									// type does not support the value saved.

									// If editor control no longer exists, the value is added to unrecognizedValues which is passed to Designer
									// Definition, allowing it to either preserve the value, or simply discard it.

									if (editors[editorSection] === undefined
										|| editors[editorSection][accordionSection] === undefined
										|| editors[editorSection][accordionSection][headlineSection] === undefined
										|| editors[editorSection][accordionSection][headlineSection][prop] === undefined)
									{
										if (unrecognizedValues[editorSection] === undefined)
											unrecognizedValues[editorSection] = {};
										if (unrecognizedValues[editorSection][accordionSection] === undefined)
											unrecognizedValues[editorSection][accordionSection] = {};
										if (unrecognizedValues[editorSection][accordionSection][headlineSection] === undefined)
											unrecognizedValues[editorSection][accordionSection][headlineSection] = {};

										// Designer definition may switch Preserve property to true to force Designer to preserve the value even though it has no control associated at this point
										unrecognizedValues[editorSection][accordionSection][headlineSection][prop] = { Preserve: false };

										if (values[editorSection][accordionSection][headlineSection][prop].Value !== undefined)
											unrecognizedValues[editorSection][accordionSection][headlineSection][prop].Value = values[editorSection][accordionSection][headlineSection][prop].Value;
										if (values[editorSection][accordionSection][headlineSection][prop].Selector)
											unrecognizedValues[editorSection][accordionSection][headlineSection][prop].Selector = { Value: values[editorSection][accordionSection][headlineSection][prop].Selector.Value };
										if (values[editorSection][accordionSection][headlineSection][prop].Slider)
											unrecognizedValues[editorSection][accordionSection][headlineSection][prop].Slider = { Value: values[editorSection][accordionSection][headlineSection][prop].Slider.Value };

										continue;
									}

									// Value has an associated control - assign value to control

									if (values[editorSection][accordionSection][headlineSection][prop].Value !== undefined)
										editors[editorSection][accordionSection][headlineSection][prop].Control.SetValue(values[editorSection][accordionSection][headlineSection][prop].Value);
									if (values[editorSection][accordionSection][headlineSection][prop].Selector) // We assume control is still a combined Input and Selector control
										editors[editorSection][accordionSection][headlineSection][prop].Selector.Control.SetValue(values[editorSection][accordionSection][headlineSection][prop].Selector.Value);
									if (values[editorSection][accordionSection][headlineSection][prop].Slider) // We assume control is still a combined Input and Slider control
										editors[editorSection][accordionSection][headlineSection][prop].Slider.Control.SetValue(values[editorSection][accordionSection][headlineSection][prop].Slider.Value);

									// Make sure initial value is accessible when control value is changed (used by updateLinkedControls(..))

									editors[editorSection][accordionSection][headlineSection][prop]._internal.PreviousValue = editors[editorSection][accordionSection][headlineSection][prop].Control.GetValue();

									if (editors[editorSection][accordionSection][headlineSection][prop].Selector || editors[editorSection][accordionSection][headlineSection][prop].Slider)
									{
										editors[editorSection][accordionSection][headlineSection][prop]._internal.PreviousValue += "|" + ((editors[editorSection][accordionSection][headlineSection][prop].Selector && editors[editorSection][accordionSection][headlineSection][prop].Selector.Control.GetValue() !== null) ? editors[editorSection][accordionSection][headlineSection][prop].Selector.Control.GetValue() : "");
										editors[editorSection][accordionSection][headlineSection][prop]._internal.PreviousValue += "|" + ((editors[editorSection][accordionSection][headlineSection][prop].Slider && editors[editorSection][accordionSection][headlineSection][prop].Slider.Control.GetValue() !== 0) ? editors[editorSection][accordionSection][headlineSection][prop].Slider.Control.GetValue().toString() : "");
									}
								}
							}
						}
					}

					suppressUpdate = false;

					if (config.OnDataLoaded)
						config.OnDataLoaded.call(config, getEventArgs());

					// Update CSS - injects CSS from designer into actual design

					updateCss();

					// Remove static override.css from design if found (created by Designer if design has been changed).
					// Designer has now injected changes as an inline style element, which allow new changes to be applied on the fly - see updateCss().

					overrideCss = (window.opener || window.top).document.querySelector("link[href^='" + templatePath + "/override.css']")
					if (overrideCss !== null)
						overrideCss.parentNode.removeChild(overrideCss);
				}
				else if (req.GetCurrentState() === 4)
				{
					alert("Error occured loading design, please try again!");
				}

				if (req.GetCurrentState() === 4 && cb)
				{
					cb();
				}
			});
			req.Start();
		}

		function reset(preventDefaults) // Reset - revert design to settings defined in Designer Definition, and load override.defaults.js if found
		{
			// NOTICE: Pressing the Reset or Reload button will cause the original styles to be loaded.
			// However, it will NOT result in editor controls being reloaded and selectable areas being
			// re-calculated. This is a problem if Reset/Reload reverts to a completely different design
			// (e.g. Hyperspace to Sunrise), and the Designer Definition's GetEditors(..) implementation
			// relies on GetComputedStyle(..) to determining what editor controls to make available.
			// Templates ought to ship with override.defaults.js to prevent this problem by making sure
			// e.g. Hyperspace resets to a clean version of Hyperspace, and not to the Sunrise design.
			// If the requirement for override.defaults.js proves to be a major problem, we need to fully
			// reset the state of the Designer and the changes we made to the page being styled, and then
			// rebuild the UI of the Designer.
			// Be aware that this is probably only a problem for templates that does advanced things like
			// positioning using custom CSS (e.g. under Advanced).

			// Hide design while resetting to prevent too much flickering
			var parentWindow = window.opener || window.top; // Support both Dialog Mode and Legacy Mode (SMWindow)
			parentWindow.document.body.parentElement.style.display = "none";

			suppressUpdate = true; // Resetting controls fire OnChange handlers, which in turn causes CSS to be updated - suppress to prevent a lot of updates

			// Reset all controls - initial values are restored

			for (var level1 in editors)
			{
				for (var level2 in editors[level1])
				{
					for (var level3 in editors[level1][level2])
					{
						for (var prop in editors[level1][level2][level3])
						{
							editors[level1][level2][level3][prop].Control.Reset();

							if (editors[level1][level2][level3][prop].Selector)
								editors[level1][level2][level3][prop].Selector.Control.Reset();

							if (editors[level1][level2][level3][prop].Slider)
								editors[level1][level2][level3][prop].Slider.Control.Reset();

							// Make sure initial value is accessible when control value is changed

							editors[level1][level2][level3][prop]._internal.PreviousValue = editors[level1][level2][level3][prop].Control.GetValue();

							if (editors[level1][level2][level3][prop].Selector || editors[level1][level2][level3][prop].Slider)
							{
								editors[level1][level2][level3][prop]._internal.PreviousValue += "|" + ((editors[level1][level2][level3][prop].Selector && editors[level1][level2][level3][prop].Selector.Control.GetValue() !== null) ? editors[level1][level2][level3][prop].Selector.Control.GetValue() : "");
								editors[level1][level2][level3][prop]._internal.PreviousValue += "|" + ((editors[level1][level2][level3][prop].Slider && editors[level1][level2][level3][prop].Slider.Control.GetValue() !== 0) ? editors[level1][level2][level3][prop].Slider.Control.GetValue().toString() : "");
							}
						}
					}
				}
			}

			suppressUpdate = false;

			if (preventDefaults !== true)
			{
				// Load default overrides (override.defaults.js).
				// This is a very convenient way for designers to allow other users to reset their design to its initial look and feel.
				// If a design is built on e.g. Sunrise, but with a completely different look and feel, and saved under a new name (e.g. Diamonds),
				// we do not want the Reset button to revert the design back to the look and feel of the Sunrise template. Rather, we want the Reset
				// button to revert the design to the look and feel of the Diamonds template.
				// One way to achieve this, is to create the style.css and designer.js files which contains the default values (this is how Sunrise is built).
				// But that makes it very difficult to distribute and re-use designs created using the Designer, since it would require the new design to be
				// recreated with a custom style.css file and a modified version of designer.js with all the new default values. That is way to complicated.
				// Instead, we allow the designer/developer to simply ship the template with a copy of override.js, called override.defaults.js.
				// When the Reset button is clicked, all the controls are reset to their initial state (which is the values from the template the design was
				// originally built on (e.g. Sunrise)). But afterwards override.defaults.js is loaded, which updates all the controls to the values of the
				// new design (e.g. Diamonds).
				loadData("LoadDefaultOverrides=true", function() // Callback gets called, even if override.defaults.js does not exist - updateCss() is called from loadData(..)
				{
					parentWindow.document.body.parentElement.style.display = "";

					if (config.OnReset)
						config.OnReset.call(config, getEventArgs());
				});
			}
			else
			{
				if (config.OnReset)
					config.OnReset.call(config, getEventArgs());
			}
		}

		function reload() // Undo - load previously saved design
		{
			reset(true); // True = Reset controls without loading override.defaults.js, and by that also preventing updateCss() from being called

			loadData(null, function() // Reload changes previously saved (updateCss() is called from loadData(..)) - code above reset controls to Designer Definition defaults
			{
				var parentWindow = window.opener || window.top; // Support both Dialog Mode and Legacy Mode (SMWindow)
				parentWindow.document.body.parentElement.style.display = "";
			});
		}

		function lockUi(enabled)
		{
			if (enabled === true && lockLayer === null)
			{
				lockLayer = document.createElement("div");
				lockLayer.className = "LockLayer";
				container.appendChild(lockLayer);

				var span = document.createElement("span");
				span.className = "fa fa-cog fa-spin";
				lockLayer.appendChild(span);
			}
			else if (enabled === false && lockLayer !== null)
			{
				lockLayer.parentNode.removeChild(lockLayer);
				lockLayer = null;
			}
		}

		// Misc.

		function getEventArgs()
		{
			return { Designer: me, Editors: editors, Sender: null, Saving: false, TemplatePath: templatePath, Section: ((lstSections !== null) ? lstSections.GetValue() : null), UnrecognizedValues: unrecognizedValues };
		}

		// Public API

		this.Update = function() { updateCss(); }

		this.Save = function() { saveData(); }

		this.Reset = function() { reset(); }

		this.Undo = function() { reload(); }

		this.Locked = function(locked) { lockUi(locked); }
	},

	Controls:
	{
		// Regarding control specific features (not common to every control):
		//  - Input.SetValue(val, defaultValue)			- Allows for fallback value if control is left empty
		//  - Slider OnChange event						- Passes additional setProgrammatically arguments
		//  - SetValue(..), GetValue()					- Accepts and returns different object types (e.g. Input returns string, Slider returns number)

		Label: function(config)
		{
			var me = this;
			var label = null;

			function construct()
			{
				label = document.createElement("span");
				label.innerHTML = ((config.Title) ? config.Title : ((config.Value) ? config.Value : ""));
			}

			this.GetElement = function()
			{
				return label;
			}

			this.IsDirty = function()
			{
				return false;
			}

			this.GetValue = function()
			{
				return null;
			}

			this.SetValue = function(val)
			{
			}

			this.Reset = function()
			{
			}

			construct();
		},

		Button: function(config)
		{
			var me = this;
			var cfg = ((typeof(config) === "object") ? SMCore.Clone(config) : {}); // Clone config object to prevent external code from messing with its properties (e.g. Value)
			var button = null;
			var dirty = false;

			function construct()
			{
				button = document.createElement("input");

				if (cfg.Disabled === true)
					button.setAttribute("disabled", "disabled");

				button.type = "button";
				button.value = (cfg.Value ? cfg.Value : "");

				if (typeof(cfg.OnChange) === "function")
				{
					button.onclick = function()
					{
						dirty = true;
						cfg.OnChange(me, button.value);
					}
				}
			}

			this.GetElement = function()
			{
				return button;
			}

			this.IsDirty = function()
			{
				// Dirty state determined by whether button was
				// clicked, not by whether value was changed.
				return dirty;
			}

			this.GetValue = function()
			{
				return button.value;
			}

			this.SetValue = function(val)
			{
				if (typeof(val) === "string")
					button.value = val;
			}

			this.Reset = function()
			{
				button.value = (cfg.Value ? cfg.Value : "");
				dirty = false;
			}

			construct();
		},

		Selector: function(config)
		{
			// Example:
			// var f = function(sender, value) { alert("Value selected: " + (value ? value : "NONE") + " - Dirty: " + sender.IsDirty()); };
			// var s = new SMDesigner.Controls.Selector({ Items: [ {Value: "val1"}, {Value: "val2", Title: "My item 2"} ], Value: "val2", OnChange: f, AllowEmpty: true });
			// document.body.appendChild(s.GetElement());

			var me = this;
			var cfg = ((typeof(config) === "object") ? SMCore.Clone(config) : {}); // Clone config object to prevent external code from messing with its properties (e.g. Value)
			var selector = null;

			// Required by IsDirty() to determine dirty state
			cfg.Value = ((typeof(cfg.Value) === "string") ? cfg.Value : null);

			// Constructor

			function construct()
			{
				selector = document.createElement("select");

				if (cfg.Disabled === true)
					selector.setAttribute("disabled", "disabled");

				// Add items

				if (cfg.AllowEmpty === true)
					selector.add(new Option("", ""));

				if (typeof(cfg.Items) === "object" && cfg.Items instanceof Array)
				{
					// Sort

					if (cfg.Sort === true)
					{
						cfg.Items.sort(function(a, b)
						{
							var aTitle = ((typeof(a.Title) === "string") ? a.Title : a.Value);
							var bTitle = ((typeof(b.Title) === "string") ? b.Title : b.Value);

							return ((aTitle < bTitle) ? -1 : ((aTitle > bTitle) ? 1 : 0));
						});
					}

					// Add

					SMCore.ForEach(cfg.Items, function(item)
					{
						if (typeof(item) !== "object")
							return;

						var value = ((typeof(item.Value) === "string") ? item.Value : "");
						var title = ((typeof(item.Title) === "string") ? item.Title : value);

						selector.add(new Option(title, value));
					});
				}

				// Set selected item

				//if (cfg.Value !== null)
				me.SetValue(cfg.Value);

				// cfg.Value was either not initially set, or contained a value that was not added as an item.
				// In this case simply set cfg.Value to the value of the currently selected item (the first one),
				// to make sure the control is not considered dirty.
				if (me.GetValue() !== cfg.Value)
					cfg.Value = me.GetValue();

				// Register OnChange handler

				if (typeof(cfg.OnChange) === "function")
				{
					selector.onchange = function()
					{
						cfg.OnChange(me, me.GetValue());
					}
				}
			}

			// Public

			this.GetElement = function()
			{
				return selector;
			}

			this.IsDirty = function()
			{
				return (cfg.Value !== me.GetValue());
			}

			this.GetValue = function()
			{
				if (selector.options.length === 0)
					return null;

				return selector.options[selector.selectedIndex].value;
			}

			this.SetValue = function(val)
			{
				if (val === null && cfg.AllowEmpty === true)
				{
					me.SetValue("");
				}
				else if (typeof(val) === "string")
				{
					var idx = selector.selectedIndex;
					var found = false;

					for (var i = 0 ; i < selector.options.length ; i++)
					{
						if (selector.options[i].value === val)
						{
							selector.selectedIndex = i;

							if (selector.onchange && selector.selectedIndex !== idx)
								selector.onchange();

							found = true;
							break;
						}
					}

					if (found === false) // Value set does not exist - add it
					{
						selector.add(new Option(val, val));
						selector.selectedIndex = selector.options.length - 1;

						if (selector.onchange)
							selector.onchange();
					}
				}
			}

			this.Reset = function()
			{
				me.SetValue(cfg.Value);
			}

			construct();
		},

		Input: function(config)
		{
			// Example:
			// var f = function(sender, value) { alert("Value: " + value + " - Dirty: " + sender.IsDirty()); };
			// var s = new SMDesigner.Controls.Selector({ Items: [ {Value: "px"}, {Value: "%", Title: "Percent"} ], Value: "px", OnChange: f, AllowEmpty: true });
			// var i = new SMDesigner.Controls.Input( { Value: "100", OnChange: f, Selector: s });
			// document.body.appendChild(i.GetElement());

			var me = this;
			var cfg = ((typeof(config) === "object") ? SMCore.Clone(config) : {}); // Clone config object to prevent external code from messing with its properties (e.g. Value)
			var container = null;	// HTMLDivElement
			var input = null;		// HTMLInputElement
			var oldVal = "";		// Used by OnChange handler

			// Required by IsDirty() to determine dirty state
			cfg.Value = ((typeof(cfg.Value) === "string") ? cfg.Value : "");

			// Constructor

			function construct()
			{
				input = document.createElement( ((cfg.MultiLine !== true) ? "input" : "textarea") );

				if (cfg.Disabled === true)
					input.setAttribute("disabled", "disabled");

				// Set initial value

				me.SetValue(cfg.Value);

				// Register OnChange handler

				if (typeof(cfg.OnChange) === "function")
				{
					oldVal = me.GetValue();

					input.onkeyup = function()
					{
						if (input.value !== oldVal)
						{
							cfg.OnChange(me, input.value);
							oldVal = input.value;
						}
					}
				}

				// Combine input with selector and/or slider if set

				if (cfg.Selector || cfg.Slider)
				{
					container = document.createElement("div");
					container.appendChild(input);
				}

				if (cfg.Selector)
					container.appendChild(cfg.Selector.GetElement());

				if (cfg.Slider)
					container.appendChild(cfg.Slider.GetElement());
			}

			// Public

			this.GetElement = function()
			{
				if (container === null)
					return input;
				else
					return container;
			}

			this.IsDirty = function()
			{
				return (cfg.Value !== me.GetValue());
			}

			this.GetValue = function(defaultValue)
			{
				if (input.value === "" && defaultValue !== undefined)
					return defaultValue;

				return input.value;
			}

			this.SetValue = function(val)
			{
				if (val === null)
				{
					me.SetValue("");
				}
				else if (typeof(val) === "string")
				{
					input.value = val;

					if (input.onkeyup)
						input.onkeyup();
				}
			}

			this.Reset = function()
			{
				me.SetValue(cfg.Value);
			}

			construct();
		},

		Slider: function(config)
		{
			// Example:
			// var f = function(sender, value, setProgrammatically) { console.log("Value: " + value + " - Dirty: " + sender.IsDirty() + " - Set programmatically: " + setProgrammatically); };
			// var s = new SMDesigner.Controls.Slider({ Min: -100, Max: 250, Step: 5, Value: 40, OnChange: f });
			// document.body.appendChild(s.GetElement());

			var me = this;
			var cfg = ((typeof(config) === "object") ? SMCore.Clone(config) : {}); // Clone config object to prevent external code from messing with its properties (e.g. Value)
			var container = null;	// HTMLDivElement
			var control = null;		// HTMLDivElement turned into JQuery UI Slider control
			var value = 0;			// Used by OnChange handler, holds current value

			cfg.Min = ((typeof(cfg.Min) === "number") ? cfg.Min : 0);
			cfg.Max = ((typeof(cfg.Max) === "number") ? cfg.Max : config.min + 100);
			cfg.Step = ((typeof(cfg.Step) === "number") ? cfg.Step : 1);
			cfg.Value = ((typeof(cfg.Value) === "number") ? cfg.Value : 0);

			// Constructor

			function construct()
			{
				container = document.createElement("div");
				SMDom.AddClass(container, "Sitemagic"); // jQuery UI CSS namespace

				control = document.createElement("div");
				container.appendChild(control)

				var config = {};
				config.min = cfg.Min;
				config.max = cfg.Max;
				config.step = cfg.Step;
				config.value = cfg.Value;
				config.disabled = ((cfg.Disabled === true) ? true : false);

				value = cfg.Value;

				if (typeof(cfg.OnChange) === "function")
				{
					// Fires constantly when using the slider - does not fire when value
					// is programmatically set, however. Handled in SetValue(..) function.
					config.slide = function(event, ui)
					{
						value = ui.value; // Used in GetValue() to return current value - jQuery slider returns previous value during Slide event
						cfg.OnChange(me, ui.value, false);
					}
				}

				SMDesigner.Jquery(control).slider(config);
			}

			// Public

			this.GetElement = function()
			{
				return container;
			}

			this.IsDirty = function()
			{
				return (cfg.Value !== me.GetValue());
			}

			this.GetValue = function()
			{
				return value; // Notice: SMDesigner.Jquery(control).slider("option", "value") returns previous value if called during Slide event
			}

			this.SetValue = function(val)
			{
				if (typeof(val) === "number" && isNaN(val) === false && val !== value)
				{
					value = val;
					SMDesigner.Jquery(control).slider("option", "value", val); // Does not fire slide event (which in turn fires OnChange event) - manually handled below

					if (typeof(cfg.OnChange) === "function")
						cfg.OnChange(me, val, true);
				}
			}

			this.Reset = function()
			{
				me.SetValue(cfg.Value);
			}

			construct();
		},

		ColorPicker: function(config)
		{
			/* var f = function(sender, value) { console.log(sender, value, sender.IsDirty()); };
			var c1 = new SMDesigner.Controls.ColorPicker({ Value: "#C0C0C0", NoAlpha: true, OnChange: f });
			var c2 = new SMDesigner.Controls.ColorPicker({ Value: { Red: 100, Green: 100, Blue: 100, Alpha: 0.38 }, OnChange: f });
			document.body.appendChild(c1.GetElement());
			document.body.appendChild(c2.GetElement()); */

			// How it works:
			//  - This control actually consists of three controls internally: Input field,
			//    ColPick color control, and the opacity/transparency (Alpha channel) slider.
			//  - The private function updateColor(colorObj, updateInput, updatePickers) is responsible
			//    for updating all three internal controls based on the supplied color object,
			//    and for firering the OnChange event.
			//    It is called by the three internal controls' OnChange handlers and by SetValue(..),
			//    and ensures that all three internal controls are updated to reflect the new color.

			var me = this;
			var cfg = ((typeof(config) === "object") ? SMCore.Clone(config) : {}); // Clone config object to prevent external code from messing with its properties (e.g. Value)
			var container = null;		// HTMLDivElement
			var input = null;			// HTMLInputElement
			var slider = null;			// SMDesigner.Controls.Slider
			var curColor = null;		// Objebt holding selected color - example: { Hex: "#FFFFFF", Red: 255, Green: 255, Blue: 255, Alpha: 1.00 };
			var oldInputVal = "";		// Holds input field value prior to a change - used internally by event handlers
			var supportsRgba = SMBrowser.CssSupported("background", "rgba(100, 100, 100, 0.5)");

			// Constructor

			function construct()
			{
				container = document.createElement("div");

				// Create input control

				input = document.createElement("input");

				if (cfg.Disabled === true)
					input.setAttribute("disabled", "disabled");

				input.onfocus = function()
				{
					// Show color picker when input is focused
					SMDesigner.Jquery(input).click(); // SMDesigner.Jquery(input).colpickShow() is buggy - https://github.com/josedvq/colpick-jQuery-Color-Picker/issues/17
				}

				input.onblur = function()
				{
					// Make sure input field contains valid value when focus is lost, by updating it to last valid value given by
					// curColor object. This also ensures correct format (e.g. hash in front of HEX value, and "pretty" rgba() format).
					executeWithNoOnChange(function() { updateColor(curColor, true, false); }); // Suppress OnChange triggered by updateColor(..)

					// Close color picker.
					// However, do not close picker in IE8 since OnBlur is fired when clicking picker, which prevents
					// user from fiddling with the colors - the picker should remain open to allow user to try different colors.
					// Simply don't auto-close the picker when Blur is fired for this browser - user must click outside of picker to close it.
					if (SMBrowser.GetBrowser() !== "MSIE" || SMBrowser.GetVersion() > 8)
						SMDesigner.Jquery(input).colpickHide();
				}

				input.onkeydown = function(e)
				{
					var ev = e || window;

					if (ev.keyCode === 27) // Close picker when ESC is pressed
						SMDesigner.Jquery(input).colpickHide();
				}

				input.onkeyup = function()
				{
					// Cancel out if value was not changed (e.g. if using arrow keys)
					if (input.value === oldInputVal)
						return;

					if (input.value === "")
					{
						updateColor(null, false, true);
						oldInputVal = input.value;
					}
					else if (SMColor.ParseHex(input.value) !== null) // HEX
					{
						updateColor({ Hex: input.value }, false, true);
						oldInputVal = input.value;
					}
					else // RGB(A)
					{
						var c = SMColor.ParseRgb(input.value);

						if (c === null)
							return;

						updateColor(c, false, true);
						oldInputVal = input.value;
					}
				}

				// Create color picker

				var colpick = {};
				colpick.layout = "hex";
				colpick.submit = false;
				colpick.onChange = function(hsb, hex, rgb, element, setProgrammatically) // setProgrammatically is 1 if color was set using updateColor(..), othersize 0
				{
					if (curColor === null)
						curColor = { Hex: "#FFFFFF", Red: 255, Green: 255, Blue: 255, Alpha: 1.00 };

					curColor.Hex = "#" + hex.toUpperCase();
					curColor.Red = rgb.r;
					curColor.Green = rgb.g;
					curColor.Blue = rgb.b;

					// Update input control and fire OnChange, unless value was set programmatically through updateColor(..) - prevents infinite loop
					if (setProgrammatically === 0)
						updateColor(curColor, true, false);
				}
				colpick.onShow = function(element)
				{
					// Scroll picker into view if necessary

					var windowHeight = window.innerHeight;
					var scrollPosition = document.body.scrollTop;
					var pickerPositionY = element.offsetTop;
					var pickerHeight = element.offsetHeight;

					if ((pickerPositionY + pickerHeight) > (windowHeight + scrollPosition))
						window.scrollTo(0, (pickerPositionY + pickerHeight) - windowHeight);
				}

				SMDesigner.Jquery(input).colpick(colpick);

				// Create opacity/transparency (Alpha channel) slider

				slider = new SMDesigner.Controls.Slider(
				{
					Min: 0.00,
					Max: 1.00,
					Step: 0.01,
					Value: 1.00,
					Disabled: cfg.Disabled,
					OnChange: function(sender, value, setProgrammatically) // setProgrammatically is True if color was set using updateColor(..)
					{
						if (curColor === null)
							curColor = { Hex: "#FFFFFF", Red: 255, Green: 255, Blue: 255, Alpha: 1.00 };

						curColor.Alpha = value;

						// Update input control and fire OnChange, unless value was set programmatically through updateColor(..) - prevents infinite loop
						if (setProgrammatically === false)
							updateColor(curColor, true, false);
					}
				});

				// Set initial value

				executeWithNoOnChange(function() { me.SetValue(cfg.Value); })
				cfg.Value = me.GetValue(); // Required by IsDirty() - cfg.Value may be a string or an object with Hex and/or RGB(A) set. Make sure cfg.Value is comparable to internal value which is an object with all properties set (HEX + RGBA).

				// Place input in container with white background.
				// Colors with transparency does not display properly in
				// Designer with dark theme without a white background.

				var whiteBg = document.createElement("div");
				whiteBg.style.background = "white";
				whiteBg.style.display = "inline-block";
				container.appendChild(whiteBg);

				input.style.margin = "0px";
				whiteBg.appendChild(input);

				// Only add slider if browser supports RGBA colors, and NoAlpha is different from True
				if (supportsRgba === true && cfg.NoAlpha !== true)
					container.appendChild(slider.GetElement());
			}

			// Private helpers

			function updateColor(colorObj, updateInput, updatePickers)
			{
				// This function is responsible for updating the UI of the control, meaning
				// updating the input field, the color picker, and the slider, based on a
				// color object containing either a HEX color or RGB(A) values.

				// Validate color object and transform it into a CSS color value (hex or rgb(..) or rgba(..))

				var colorString = null;
				var colorContrast = null;

				if (colorObj === null)
				{
					colorString = "";
					colorContrast = "black";
				}
				else if (typeof(colorObj) === "object" && typeof(colorObj.Red) === "number" && typeof(colorObj.Green) === "number" && typeof(colorObj.Blue) === "number")
				{
					if (supportsRgba === true && typeof(colorObj.Alpha) === "number" && colorObj.Alpha < 1)
					{
						colorString = "rgba(" + colorObj.Red + ", " + colorObj.Green + ", " + colorObj.Blue + ", " + colorObj.Alpha + ")";
					}
					else
					{
						colorString = SMColor.RgbToHex(colorObj.Red, colorObj.Green, colorObj.Blue); // Prefer HEX when no opacity is set
					}

					colorContrast = getContrastTextColor(colorObj);
				}
				else if (typeof(colorObj.Hex) === "string" && SMColor.ParseHex(colorObj.Hex) !== null)
				{
					colorString = fixHexFormat(colorObj.Hex); // Ensure HEX value is in upper case and starts with a hash
					colorContrast = getContrastTextColor(SMColor.ParseHex(colorString));
				}

				// Skip if no valid value was supplied

				if (colorString === null)
					return;

				// Skip if value supplied is identical to value previously set

				if (colorString === oldInputVal)
					return;

				// Set input value

				if (updateInput === true) // Only update if necessary since it causes text cursor to jump to the end of input field
				{
					input.value = colorString;
					oldInputVal = input.value;
				}

				// Set input color (background and text color)

				input.style.backgroundColor = colorString;
				input.style.color = colorContrast; // Black or white

				// Update pickers

				if (updatePickers === true) // This argument will be False if updateColor(..) was called from color picker's or slider's Change event handler
				{
					// Controls' OnChange handlers are responsible for updating curColor object

					if (colorString === "")
					{
						slider.SetValue(1.00);
						curColor = null; // Don't reset color picker, only its value
					}
					else if (colorString.indexOf("rgba") === -1) // HEX
					{
						// TODO: Triggers OnChange twice!
						SMDesigner.Jquery(input).colpickSetColor(colorString);
						slider.SetValue(1.00);
					}
					else // RGB(A)
					{
						// TODO: Triggers OnChange twice!
						SMDesigner.Jquery(input).colpickSetColor({r: colorObj.Red, g: colorObj.Green, b: colorObj.Blue});
						slider.SetValue(colorObj.Alpha);
					}
				}

				// Fire change handler

				if (typeof(cfg.OnChange) === "function")
				{
					cfg.OnChange(me, curColor);
				}
			}

			function fixHexFormat(val)
			{
				val = val.toUpperCase();

				if (val.indexOf("#") !== 0)
					val = "#" + val;

				return val;
			}

			function getContrastTextColor(color) // color.Red, color.Green, and color.Blue MUST be defined - color.Alpha is optional!
			{
				var brightness = (0.2126 * color.Red + 0.7152 * color.Green + 0.0722 * color.Blue); // http://stackoverflow.com/questions/596216/formula-to-determine-brightness-of-rgb-color

				if (typeof(color.Alpha) === "number") // Taking opacity into account
					brightness = brightness + ((1 - color.Alpha) * 200);

				if (brightness < 150)
					return "white";
				else
					return "black";
			}

			function executeWithNoOnChange(cb)
			{
				// Do not trigger OnChange
				var onChg = cfg.OnChange;
				delete cfg.OnChange;

				cb();

				// Restore OnChange event handler if previously defined
				if (typeof(onChg) === "function")
					cfg.OnChange = onChg;
			}

			// Public

			this.GetElement = function()
			{
				return container;
			}

			this.IsDirty = function()
			{
				// During initialization cfg.Value (which is a mixed type: string/object) was replaced
				// by an object with the same format as the internal color object, making them comparable.
				return (SMCore.IsEqual(curColor, cfg.Value) === false);
			}

			this.GetValue = function(defaultValue)
			{
				if (curColor === null)
				{
					if (defaultValue !== undefined)
						return defaultValue;

					return null;
				}

				// Return clone to make sure internal changes does not affect external code with shared object reference

				return SMCore.Clone(curColor);
			}

			this.SetValue = function(val)
			{
				if (val === null || val === "")
				{
					updateColor(null, true, true);
				}
				else if (typeof(val) === "string") // Format must be HEX or RGB(A) - e.g. #C0C0C0 or rgb(255, 255, 255) or rgba(100, 150, 100, 0.55)
				{
					if (SMColor.ParseHex(val) !== null) // HEX
					{
						var c = { Hex: val }
						updateColor(c, true, true);
					}
					else // RGB(A)
					{
						var c = SMColor.ParseRgb(val);

						if (c === null)
							return;

						updateColor(c, true, true);
					}
				}
				else if (typeof(val) === "object")
				{
					updateColor(val, true, true);
				}
			}

			this.Reset = function()
			{
				me.SetValue(cfg.Value);
			}

			construct();
		}
	},

	Graphics:
	{
		IsBrowserSupported: function()
		{
			return !!document.createElement("canvas").getContext;
		},

		LoadResources: function()
		{
			if (!window.Trianglify && SMDesigner.Graphics.IsBrowserSupported() === true)
			{
				Trianglify = {}; // Prevent script from loading multiple times
				SMResourceManager.LoadScript(SMEnvironment.GetExtensionsDirectory() + "/SMDesigner/plugins/trianglify/trianglify.min.js"); // https://cdnjs.cloudflare.com/ajax/libs/trianglify/0.2.0/trianglify.min.js
			}
		},

		GenerateBackground: function(width, height, tileSize, variance)
		{
			if (SMDesigner.Graphics.IsBrowserSupported() === false)
				throw new Error("Unable to generate background, please upgrade to a modern browser supporting HTML5 Canvas");

			if (!window.Trianglify)
				throw new Error("Background generator not loaded yet");

			var cfg = {};
			cfg.width = ((typeof(width) === "number") ? parseInt(width) : 800);
			cfg.height = ((typeof(height) === "number") ? parseInt(height) : 600);
			cfg.cell_size = ((typeof(tileSize) === "number") ? parseInt(tileSize) : 40);
			cfg.variance = ((typeof(variance) === "number") ? ((variance >= 0 && variance <= 1) ? variance : 0.5) : 0.5);

			return Trianglify(cfg).png(); // Returns Base64 image data
		},

		SaveImage: function(destination, base64ImageData, quality, cb, cbError)
		{
			if (!destination || !base64ImageData || typeof(quality) !== "number" || (cb && typeof(cb) !== "function") || (cbError && typeof(cbError) !== "function"))
				throw new Error("SaveImage(destination, base64ImageData, quality[, cb[, cbError]]) called with invalid parameters");

			var async = (typeof(cb) === "function");

			var req = new SMHttpRequest(SMDesigner.Resources.WsGraphicsUrl, async);
			if (async === true)
			{
				req.SetStateListener(function()
				{
					if (req.GetCurrentState() === 4)
					{
						if (req.GetHttpStatus() === 200)
						{
							var res = req.GetResponseText();

							if (res.toLowerCase().indexOf("error") === 0)
							{
								if (typeof(cbError) === "function")
									cbError(res);
								else
									throw new Error(res);
							}
							else
							{
								cb(res);
							}
						}
						else
						{
							var err = "SaveImage failed with HTTP status code: " + req.GetHttpStatus();

							if (typeof(cbError) === "function")
								cbError(err);
							else
								throw new Error(err);
						}
					}
				});
			}
			req.SetData("Function=Save&Base64=" + encodeURIComponent(base64ImageData).replace(/%20/g, "+") + "&Destination=" + destination + "&Quality=" + ((quality >= 1 || quality <= 100) ? parseInt(quality) : 100));
			req.Start();

			if (async === true)
				return null;

			// Synchronous request (if no callback function has been provided)

			var res = req.GetResponseText(); // Text contains error on failure, filename on success

			if (res.toLowerCase().indexOf("error") === 0)
				throw new Error(res);

			return res;
		},

		ScaleImage: function(source, target, scale, quality, cb, cbError)
		{
			if (!source || !target || typeof(scale) !== "number" || typeof(quality) !== "number" || (cb && typeof(cb) !== "function") || (cbError && typeof(cbError) !== "function"))
				throw new Error("ScaleImage(source, target, scale, quality[, cb[, cbError]]) called with invalid parameters");

			var async = (typeof(cb) === "function");

			var req = new SMHttpRequest(SMDesigner.Resources.WsGraphicsUrl, async);
			if (async === true)
			{
				req.SetStateListener(function()
				{
					if (req.GetCurrentState() === 4)
					{
						if (req.GetHttpStatus() === 200)
						{
							var res = req.GetResponseText();

							if (res.toLowerCase().indexOf("error") === 0)
							{
								if (typeof(cbError) === "function")
									cbError(res);
								else
									throw new Error(res);
							}
							else
							{
								cb(res);
							}
						}
						else
						{
							var err = "ScaleImage failed with HTTP status code: " + req.GetHttpStatus();

							if (typeof(cbError) === "function")
								cbError(err);
							else
								throw new Error(err);
						}
					}
				});
			}
			req.SetData("Function=Scale&Source=" + source + "&Target=" + target + "&Scale=" + scale + "&Quality=" + ((quality >= 1 || quality <= 100) ? parseInt(quality) : 100));
			req.Start();

			if (async === true)
				return null;

			// Synchronous request (if no callback function has been provided)

			var res = req.GetResponseText(); // Text contains error on failure, filename on success

			if (res.toLowerCase().indexOf("error") === 0)
				throw new Error(res);

			return res;
		},

		MoveImage: function(destination, newDestination, cb, cbError)
		{
			if (!destination || !newDestination || (cb && typeof(cb) !== "function") || (cbError && typeof(cbError) !== "function"))
				throw new Error("MoveImage(destination, newDestination[, cb[, cbError]]) called with invalid parameters");

			var async = (typeof(cb) === "function");

			var req = new SMHttpRequest(SMDesigner.Resources.WsGraphicsUrl, async);
			if (async === true)
			{
				req.SetStateListener(function()
				{
					if (req.GetCurrentState() === 4)
					{
						if (req.GetHttpStatus() === 200)
						{
							var res = req.GetResponseText();

							if (res.toLowerCase().indexOf("error") === 0)
							{
								if (typeof(cbError) === "function")
									cbError(res);
								else
									throw new Error(res);
							}
							else
							{
								cb(res);
							}
						}
						else
						{
							var err = "MoveImage failed with HTTP status code: " + req.GetHttpStatus();

							if (typeof(cbError) === "function")
								cbError(err);
							else
								throw new Error(err);
						}
					}
				});
			}
			req.SetData("Function=Move&MoveFrom=" + destination + "&MoveTo=" + newDestination);
			req.Start();

			if (async === true)
				return null;

			// Synchronous request (if no callback function has been provided)

			var res = req.GetResponseText(); // Text contains error on failure, filename on success

			if (res.toLowerCase().indexOf("error") === 0)
				throw new Error(res);

			return res;
		}
	},

	Helpers:
	{
		DefaultDimensionUnit: "px",

		// Value getters

		GetControlValue: function(cfg, treatEmptyAsNotDirty)
		{
			// Returns Null if control is NOT dirty.
			// If control is a combined Input and Selector, both values are returned combined.
			// An empty string value can optionally be considered "not dirty", meaning Null is returned.
			// - This feature is used when an empty string is considered an invalid value.
			//   This is almost always used with a combined Input and Selector control where
			//   both the input value and drop down value is required to produce a valid combined value.

			var control = cfg.Control;
			var selector = (cfg.Selector ? cfg.Selector.Control : null);

			if (selector === null)
			{
				if (control.IsDirty() === false || (treatEmptyAsNotDirty === true && control.GetValue() === ""))
					return null;

				return control.GetValue();
			}
			else
			{
				if ((control.IsDirty() === false && selector.IsDirty() === false)
					|| (treatEmptyAsNotDirty === true && (control.GetValue() === "" || selector.GetValue() === "")))
					return null;

				return control.GetValue() + selector.GetValue();
			}
		},

		GetColorString: function(cfg, noRgba)
		{
			// Returns "transparent" if control is empty (no value set).
			// So this function is different from the other value getters,
			// since it does not consider the Dirty state of the control.

			var color = cfg.Control.GetValue();

			if (color === null)
				return "transparent";

			return ((color.Alpha === 1 || noRgba === true) ? color.Hex : "rgba(" + color.Red + ", " + color.Green + ", " + color.Blue + ", " + color.Alpha + ")");
		},

		GetColorCss: function(cfg, type, rgbaFallback)
		{
			// Returns Null if control is NOT dirty

			if (cfg.Control.IsDirty() === false)
				return null;

			var color = SMDesigner.Helpers.GetColorString(cfg);
			var css = type.toLowerCase() + ": " + color + ";";

			if (color.indexOf("rgba") > -1 && rgbaFallback === true)
			{
				css = type.toLowerCase() + ": " + SMDesigner.Helpers.GetColorString(cfg, true) + ";" + css;

				// Ordinary fallback does not work for color property in IE7 - fixing
				if (type.toLowerCase() === "color")
					css += "*color: " + SMDesigner.Helpers.GetColorString(cfg, true) + ";";
			}

			return css;
		},

		// Dimension control and CSS getter

		GetDimensionControl: function(val, unit, sliderMin, sliderMax, sliderStep)
		{
			var cfg = null;

			cfg =
			{
				Type: "Input",
				Value: (val || val === 0 ? val.toString() : ""),
				Selector:
				{
					Value: (unit ? unit : SMDesigner.Helpers.DefaultDimensionUnit),
					AllowEmpty: false,
					Items: [ { Value: "em" }, { Value: "in" }, { Value: "mm" }, { Value: "pc" }, { Value: "pt" }, { Value: "px" }, { Value: "%" } ]
				}
			};

			if (sliderMin !== undefined && sliderMax !== undefined && sliderStep !== undefined)
			{
				var suppressChangeHandler = false;

				cfg.OnChange = function(sender, value)
				{
					if (suppressChangeHandler === true)
						return;

					try
					{
						var numVal = ((value !== "") ? parseFloat(value) : 0.0);

						suppressChangeHandler = true;
						cfg.Slider.Control.SetValue(numVal);
						suppressChangeHandler = false;
					}
					catch (err) {}
				},
				cfg.Slider =
				{
					Min: sliderMin,
					Max: sliderMax,
					Step: sliderStep,
					Value: (val || val === 0 ? val : 0),
					NoSave: true,
					OnChange: function(sender, value)
					{
						if (suppressChangeHandler === true)
							return;

						suppressChangeHandler = true;
						cfg.Control.SetValue(value.toString());
						suppressChangeHandler = false;
					}
				}
			}

			return cfg;
		},

		GetDimensionCss: function(cfg, type)
		{
			// Returns Null if control is NOT dirty or if value is invalid

			if (cfg.Control.IsDirty() === true && cfg.Control.GetValue() === "")
				return (type ? type + ": 0px;" : "0px"); // Added px to make sure returned value works in calc(..) where a unit is required

			if (cfg.Control.IsDirty() === true && /^-?[0-9]+([.][0-9]+)?$/.test(cfg.Control.GetValue()) === false)
				return null;

			var val = SMDesigner.Helpers.GetControlValue(cfg);

			if (val === null)
				return null;
			if (/[0-9]+/.test(cfg.Control.GetValue()) === false)
				return null;

			return (type ? type + ": " + val + ";" : val);
		},

		// Indentation controls and CSS getter

		GetIndentationControls: function(topVal, leftVal, rightVal, bottomVal, unit)
		{
			var max = ((unit === "em" || (!unit && SMDesigner.Helpers.DefaultDimensionUnit === "em")) ? 20 : 100);
			var step = ((unit === "em" || (!unit && SMDesigner.Helpers.DefaultDimensionUnit === "em")) ? 0.1 : 1);

			var cfg =
			{
				"All sides": { Type: "Slider", Min: 0, Max: max, Step: step, Value: 0, NoSave: true },
				"Top": SMDesigner.Helpers.GetDimensionControl(topVal, unit),
				"Left": SMDesigner.Helpers.GetDimensionControl(leftVal, unit),
				"Right": SMDesigner.Helpers.GetDimensionControl(rightVal, unit),
				"Bottom": SMDesigner.Helpers.GetDimensionControl(bottomVal, unit)
			};

			cfg["All sides"].OnChange = function(sender, value)
			{
				// Synchronize slider value to dimension controls.
				// This can not be achieved using LinkControls(..) since
				// it restricts synchronization between two controls of
				// identical types (different controls may use
				// different value types).

				if (cfg["Top"].Disabled !== true)
				{
					cfg["Top"].Control.SetValue(value.toString());
					cfg["Top"].Selector.Control.SetValue(unit);
				}
				if (cfg["Left"].Disabled !== true)
				{
					cfg["Left"].Control.SetValue(value.toString());
					cfg["Left"].Selector.Control.SetValue(unit);
				}
				if (cfg["Right"].Disabled !== true)
				{
					cfg["Right"].Control.SetValue(value.toString());
					cfg["Right"].Selector.Control.SetValue(unit);
				}
				if (cfg["Bottom"].Disabled !== true)
				{
					cfg["Bottom"].Control.SetValue(value.toString());
					cfg["Bottom"].Selector.Control.SetValue(unit);
				}
			}

			return cfg;
		},

		GetIndentationCss: function(cfg, type, disableMarginCollapse)
		{
			// Returns Null if control is NOT dirty

			var indent = "";
			var val = null;

			// Disable margin collapse to make contained elements with top/bottom margins
			// expand the content area rather than causing spacing above and below it.
			// Examples: https://jsfiddle.net/275z819u/ and http://jsfiddle.net/o1vkar7c/2/
			// Also see https://stackoverflow.com/questions/13573653/css-margin-terror-margin-adds-space-outside-parent-element.
			var noMarginCollapse = (disableMarginCollapse === true && type.toLowerCase() === "padding");

			for (var prop in cfg)
			{
				if (prop === "All sides")
					continue;

				if (noMarginCollapse === true && (prop === "Top" || prop === "Bottom") && parseFloat(SMDesigner.Helpers.GetDimensionCss(cfg[prop])) === 0)
				{
					val = "padding-" + prop.toLowerCase() + ": 0.03px;"; // 0.02 works on all supported browsers but will not be sufficient if zooming out in e.g. Chrome to 75%. A value of 0.03 allow us to zoom out to 67%
				}
				else
				{
					val = SMDesigner.Helpers.GetDimensionCss(cfg[prop], type.toLowerCase() + "-" + prop.toLowerCase());
				}

				if (val !== null)
					indent += val;
			}

			return (indent !== "" ? indent : null);
		},

		// Border controls and CSS getter

		GetBorderControls: function(color, style, size, radius, applyTo)
		{
			var cfg =
			{
				"Color": { Type: "Color" },
				"Style" : { Type: "Selector", Items: [{ Title: "Solid", Value: "solid" }, { Title: "Dashed", Value: "dashed" }, { Title: "Dotted", Value: "dotted" }, { Title: "Double", Value: "double" }] },
				"Size": SMDesigner.Helpers.GetDimensionControl(1, "px", 0, 20, 1), //"Size": { Type: "Slider", Min: 0, Max: 50, Step: 1, Value: 1 },
				"Rounded corners": SMDesigner.Helpers.GetDimensionControl(0, "px", 0, 20, 1), //{ Type: "Slider", Min: 0, Max: 50, Step: 1, Value: 0 },
				"Apply to": { Type: "Selector", Items: [{ Title: "All", Value: "" }, { Title: "Top", Value: "top" }, { Title: "Left", Value: "left" }, { Title: "Right", Value: "right" }, { Title: "Bottom", Value: "bottom" }] }
			};

			if (color)
				cfg["Color"].Value = color;
			if (style)
				cfg["Style"].Value = style;
			if (size || size === 0)
			{
				cfg["Size"].Value = size.toString();
				cfg["Size"].Slider.Value = size;
			}
			if (radius || radius === 0)
			{
				cfg["Rounded corners"].Value = radius.toString();
				cfg["Rounded corners"].Slider.Value = radius;
			}
			if (applyTo)
				cfg["Apply to"].Value = applyTo.toLowerCase();

			return cfg;
		},

		GetBorderCss: function(cfg, rgbaFallback)
		{
			// Returns Null if control is NOT dirty

			var css = "";

			// Border

			if (cfg["Apply to"].Control.IsDirty() === true || cfg["Color"].Control.IsDirty() === true
				|| cfg["Size"].Control.IsDirty() === true || cfg["Size"].Selector.Control.IsDirty() === true
				|| cfg["Style"].Control.IsDirty() === true)
			{
				var borderEdge = cfg["Apply to"].Control.GetValue();
				var width = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(cfg["Size"], function() { return SMDesigner.Helpers.GetDimensionCss(cfg["Size"]); });
				var style = cfg["Style"].Control.GetValue();
				var colorStr = SMDesigner.Helpers.GetColorString(cfg["Color"]);
				var colorFallbackStr = ((colorStr.indexOf("rgba") > -1 && rgbaFallback === true) ? SMDesigner.Helpers.GetColorString(cfg["Color"], true) : "");

				css += "border-style: none;";

				if (parseFloat(width) > 0 && colorStr !== "transparent") // Color became transparent if user removed a preset color value
				{
					if (colorFallbackStr !== "")
						css += "border" + ((borderEdge !== "") ? "-" + borderEdge : "") + ": " + width + " " + style + " " + colorFallbackStr + ";";
					css += "border" + ((borderEdge !== "") ? "-" + borderEdge : "") + ": " + width + " " + style + " " + colorStr + ";";
				}
			}

			// Rounded corners (radius)

			if (cfg["Rounded corners"].Control.IsDirty() === true || cfg["Rounded corners"].Selector.Control.IsDirty() === true || cfg["Apply to"].Control.IsDirty() === true)
			{
				var edge = cfg["Apply to"].Control.GetValue();
				var radius = SMDesigner.Helpers.GetDimensionCss(cfg["Rounded corners"]);

				if (cfg["Apply to"].Control.IsDirty() === true)
				{
					css += "border-radius: 0px;";
					radius = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(cfg["Rounded corners"], function() { return SMDesigner.Helpers.GetDimensionCss(cfg["Rounded corners"]); });
				}

				radius = ((radius !== null) ? radius : "0px");

				if (edge === "")
				{
					css += "border-radius: " + radius + ";";
				}
				else if (edge === "top")
				{
					css += "border-top-left-radius: " + radius + ";";
					css += "border-top-right-radius: " + radius + ";";
				}
				else if (edge === "bottom")
				{
					css += "border-bottom-left-radius: " + radius + ";";
					css += "border-bottom-right-radius: " + radius + ";";
				}
				else if (edge === "left")
				{
					css += "border-top-left-radius: " + radius + ";";
					css += "border-bottom-left-radius: " + radius + ";";
				}
				else if (edge === "right")
				{
					css += "border-top-right-radius: " + radius + ";";
					css += "border-bottom-right-radius: " + radius + ";";
				}
			}

			return (css !== "" ? css : null);
		},

		// Shadow controls and CSS getter

		GetTextShadowControls: function(color, size, blur, posX, posY)
		{
			return SMDesigner.Helpers.GetShadowControls(color, -1, blur, posX, posY);
		},

		GetShadowControls: function(color, size, blur, posX, posY)
		{
			var cfg =
			{
				"Color": { Type: "Color" },
				"Size": SMDesigner.Helpers.GetDimensionControl(1, "px", 0, 20, 0.5),
				"Blur": SMDesigner.Helpers.GetDimensionControl(3, "px", 0, 20, 0.5),
				"Horizontal position": SMDesigner.Helpers.GetDimensionControl(0, "px", -20, 20, 0.5),
				"Vertical position": SMDesigner.Helpers.GetDimensionControl(0, "px", -20, 20, 0.5)
			};

			if (color)
			{
				cfg["Color"].Value = color
			}
			if (size === -1)
			{
				delete cfg["Size"]; // Not used for text-shadow
			}
			else if (size || size === 0)
			{
				cfg["Size"].Value = size.toString();
				cfg["Size"].Slider.Value = size;
			}
			if (blur || blur === 0)
			{
				cfg["Blur"].Value = blur.toString();
				cfg["Blur"].Slider.Value = blur;
			}
			if (posX || posX === 0)
			{
				cfg["Horizontal position"].Value = posX.toString();
				cfg["Horizontal position"].Slider.Value = posX;
			}
			if (posY || posY === 0)
			{
				cfg["Vertical position"].Value = posY.toString();
				cfg["Vertical position"].Slider.Value = posY;
			}

			return cfg;
		},

		GetShadowCss: function(cfg, shadowType)
		{
			// Returns Null if control is NOT dirty

			var type = (shadowType ? shadowType : "box-shadow");

			var sizeDirty = ((type !== "text-shadow") ? (cfg["Size"].Control.IsDirty() || cfg["Size"].Selector.Control.IsDirty()) : false);
			if (cfg["Color"].Control.IsDirty() === false && sizeDirty === false && cfg["Blur"].Control.IsDirty() === false && cfg["Blur"].Selector.Control.IsDirty() === false
				&& cfg["Horizontal position"].Control.IsDirty() === false && cfg["Horizontal position"].Selector.Control.IsDirty() === false
				&& cfg["Vertical position"].Control.IsDirty() === false && cfg["Vertical position"].Selector.Control.IsDirty() === false)
				return null;

			var css = "";
			var color = SMDesigner.Helpers.GetColorString(cfg["Color"]);

			if (color === "transparent")
			{
				css = type + ": none;";
			}
			else
			{
				var size = -1;

				if (type !== "text-shadow")
					size = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(cfg["Size"], function() { return SMDesigner.Helpers.GetDimensionCss(cfg["Size"]); });

				var blur = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(cfg["Blur"], function() { return SMDesigner.Helpers.GetDimensionCss(cfg["Blur"]); });
				var posX = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(cfg["Horizontal position"], function() { return SMDesigner.Helpers.GetDimensionCss(cfg["Horizontal position"]); });
				var posY = SMDesigner.Helpers.ExecuteWithoutDirtyCheck(cfg["Vertical position"], function() { return SMDesigner.Helpers.GetDimensionCss(cfg["Vertical position"]); });

				css = type + ": " + posX + " " + posY + " " + blur + ((type !== "text-shadow") ? " " + size : "") + " " + color + ";"
			}

			// Make shadow compatible with older versions of Mozilla/Firefox and Safari/Chrome
			var result = "";
			result += "-moz-" + css;
			result += "-webkit-" + css;
			result += css;

			return result;
		},

		// Font controls and CSS getter

		GetFontControl: function(selected)
		{
			var fonts =
			[
				{ Title: "Serif - Georgia", Value: "Georgia, serif" },
				{ Title: "Serif - Palatino", Value: "'Palatino Linotype', 'Book Antiqua', Palatino, serif" },
				{ Title: "Serif - Times New Roman", Value: "'Times New Roman', Times, serif" },
				{ Title: "Sans-Serif - Arial", Value: "Arial, Helvetica, sans-serif" },
				{ Title: "Sans-Serif - Arial Black", Value: "'Arial Black', Gadget, sans-serif" },
				{ Title: "Sans-Serif - Comic Sans", Value: "'Comic Sans MS', cursive, sans-serif" },
				{ Title: "Sans-Serif - Impact", Value: "Impact, Charcoal, sans-serif" },
				{ Title: "Sans-Serif - Lucida", Value: "'Lucida Sans Unicode', 'Lucida Grande', sans-serif" },
				{ Title: "Sans-Serif - Tahoma", Value: "Tahoma, Geneva, sans-serif" },
				{ Title: "Sans-Serif - Trebuchet", Value: "'Trebuchet MS', Helvetica, sans-serif" },
				{ Title: "Sans-Serif - Verdana", Value: "Verdana, Geneva, sans-serif" },
				{ Title: "Monospace - Courier New", Value: "'Courier New', Courier, monospace" },
				{ Title: "Monospace - Lucida Console", Value: "'Lucida Console', Monaco, monospace" }
			];

			// Allow font selection using font name in title, e.g. "Sans-Serif - Verdana", or simply "Verdana".
			// Font selection using actual value is still possible since it will result in no match below.
			if (selected)
			{
				var shortFormat = (selected.indexOf(" - ") === -1);

				SMCore.ForEach(fonts, function(font)
				{
					if (shortFormat === true)
					{
						if (font.Title.substring(font.Title.indexOf(" - ") + 3).toLowerCase() === selected.toLowerCase())
						{
							selected = font.Value;
							return false; // Break loop
						}
					}
					else
					{
						if (font.Title.toLowerCase() === selected.toLowerCase())
						{
							selected = font.Value;
							return false; // Break loop
						}
					}
				});
			}

			var cfg =
			{
				Type: "Selector",
				AllowEmpty: false,
				Items: fonts,
				Value: selected
			};

			return cfg;
		},

		GetFontControls: function(font, size, sizeUnit, color, style, align, lineHeight, lineHeightUnit, letterSpacing, letterSpacingUnit)
		{
			var cfg =
			{
				"Font": SMDesigner.Helpers.GetFontControl(font),
				"Size": SMDesigner.Helpers.GetDimensionControl(size, (sizeUnit ? sizeUnit : "em"), 0, 20, 0.1),
				"Color": { Type: "Color", Value: color },
				"Style": { Type: "Selector", Value: (style ? style.toLowerCase() : undefined), AllowEmpty: false, Items: [{Title: "Normal", Value: "normal"}, {Title: "Bold", Value: "bold"}, {Title: "Italic", Value: "italic"}, {Title: "Bold Italic", Value: "bold italic"}] },
				"Alignment": { Type: "Selector", Value: (align ? align.toLowerCase() : undefined), AllowEmpty: false, Items: [{Title: "Left", Value: "left"}, {Title: "Center", Value: "center"}, {Title: "Right", Value: "right"}] },
				"Line height": SMDesigner.Helpers.GetDimensionControl(lineHeight, (lineHeightUnit ? lineHeightUnit : "em"), 1.2, 20, 0.1),
				"Letter spacing": SMDesigner.Helpers.GetDimensionControl(letterSpacing, (letterSpacingUnit ? letterSpacingUnit : "em"), 0, 5, 0.01)
			}

			return cfg;
		},

		GetFontHeadingControls: function(font, size, sizeUnit, color, style, align, lineHeight, lineHeightUnit, letterSpacing, letterSpacingUnit, marginTop, marginTopUnit, marginBottom, marginBottomUnit)
		{
			var cfg = SMDesigner.Helpers.GetFontControls(font, size, sizeUnit, color, style, align, lineHeight, lineHeightUnit, letterSpacing, letterSpacingUnit);
			cfg["Margin above"] = SMDesigner.Helpers.GetDimensionControl(marginTop, (marginTopUnit ? marginTopUnit : "em"), 0, 20, 0.1);
			cfg["Margin below"] = SMDesigner.Helpers.GetDimensionControl(marginBottom, (marginBottomUnit ? marginBottomUnit : "em"), 0, 20, 0.1);

			return cfg;
		},

		GetFontCss: function(cfg)
		{
			// Returns Null if control is NOT dirty

			var css = "";

			var font = SMDesigner.Helpers.GetControlValue(cfg["Font"]);
			var size = SMDesigner.Helpers.GetDimensionCss(cfg["Size"]);
			var color = SMDesigner.Helpers.GetColorCss(cfg["Color"], "color", true);
			var style = SMDesigner.Helpers.GetControlValue(cfg["Style"]);
			var align = SMDesigner.Helpers.GetControlValue(cfg["Alignment"]);
			var lineHeight = SMDesigner.Helpers.GetDimensionCss(cfg["Line height"]);
			var letterSpacing = SMDesigner.Helpers.GetDimensionCss(cfg["Letter spacing"]);
			var marginTop = ((cfg["Margin above"] !== undefined) ? SMDesigner.Helpers.GetDimensionCss(cfg["Margin above"]) : null);
			var marginBottom = ((cfg["Margin below"] !== undefined) ? SMDesigner.Helpers.GetDimensionCss(cfg["Margin below"]) : null);

			css += ((font !== null) ? "font-family: " + font + ";" : "");
			css += ((size !== null) ? "font-size: " + size + ";" : "");
			css += ((color !== null) ? color : "");

			if (style !== null)
			{
				if (style.indexOf("bold") > -1)
					css += "font-weight: bold;";
				else
					css += "font-weight: normal;";

				if (style.indexOf("italic") > -1)
					css += "font-style: italic;";
				else
					css += "font-style: normal;";
			}
			if (align !== null)
				css += "text-align: " + align + ";";
			if (lineHeight !== null)
				css += "line-height: " + lineHeight + ";";
			if (letterSpacing !== null)
				css += "letter-spacing: " + letterSpacing + ";";
			if (marginTop !== null)
				css += "margin-top: " + marginTop + ";";
			if (marginBottom !== null)
				css += "margin-bottom: " + marginBottom + ";";

			return ((css !== "") ? css : null);
		},

		HideAllControls: function(objArr)
		{
			for (var prop in objArr)
			{
				if (typeof(objArr[prop].Type) !== "string") // NOTICE: Will not work if .Type property is omitted, although it is optional (in which case Input is assumed)
					SMDesigner.Helpers.HideAllControls(objArr[prop]);
				else
					objArr[prop].Hidden = true;
			}
		},

		DisableAllControls: function(objArr)
		{
			for (var prop in objArr)
			{
				if (typeof(objArr[prop].Type) !== "string") // NOTICE: Will not work if .Type property is omitted, although it is optional (in which case Input is assumed)
					SMDesigner.Helpers.DisableAllControls(objArr[prop]);
				else
					objArr[prop].Disabled = true;
			}
		},

		SetAllAllowEmpty: function(obj) // Be careful when using SetAllAllowEmpty(..) with control configurations with default values! Changing controls with AllowEmpty to "empty" will cause the control to become Dirty, but most GetXyzCss helpers will produce something like "property: ;" (value missing)
		{
			if (typeof(obj.Type) === "string") // Control configuration
			{
				obj.AllowEmpty = true;
			}
			else // Object array
			{
				for (var prop in obj)
				{
					SMDesigner.Helpers.SetAllAllowEmpty(obj[prop]);
				}
			}

			return obj; // Return to allow calls to SetAllAllowEmpty to wrap calls to other helper functions, e.g.: SetAllAllowEmpty(GetFontControl())
		},

		// Work around to allow CSS values to be retrived even when controls are not dirty.
		// Consider refactoring all helpers - e.g. GetFontCss(..). Make them consistent
		// and let them all accept a flag indicating whether to ignore dirty state or not.
		ExecuteWithoutDirtyCheck: function(cfg, cb)
		{
			var dirtyFunc = cfg.Control.IsDirty;
			var selectorDirtyFunc = null;

			cfg.Control.IsDirty = function() { return true; };

			if (cfg.Selector)
			{
				selectorDirtyFunc = cfg.Selector.Control.IsDirty;
				cfg.Selector.Control.IsDirty = function() { return true; };
			}

			var res = cb();

			cfg.Control.IsDirty = dirtyFunc;

			if (cfg.Selector)
			{
				cfg.Selector.Control.IsDirty = selectorDirtyFunc;
			}

			return res;
		},

		// Control synchronization

		// syncType		: SMDesigner.Helpers.SyncType.TYPE
		// c1Cfg		: Source control configuration (master)
		// c2Cfg		: Target control configuration (slave)
		// mapping		: Specific to Selector control - allows for one value to be mapped to another value when synchronized, e.g. {"left":"right", "right":"left"}
		LinkControls: function(syncType, c1Cfg, c2Cfg, mapping)
		{
			// LinkControls(..) adds a Sync property to control configurations that tells SMDesigner
			// how to synchronize values between two controls. So the actual synchronization is done by the Designer.

			// Make sure only controls of same type is linked.
			// Sync'ing e.g. Slider to Input or Color to Input is not possible since
			// Get/SetValue() returns/takes different object types (Number / String / Color object).

			if (c1Cfg.Type !== c2Cfg.Type)
				throw new Error("Unable to link controls of different types - make sure Type properties are identical");
			if ((!c1Cfg.Type || c1Cfg.Type.toLowerCase() === "input") && ((c1Cfg.Selector && !c2Cfg.Selector) || (!c1Cfg.Selector && c2Cfg.Selector)))
				throw new Error("Unable to link different types of Input controls - both must define a selector");

			// Configure synchronization from Control1 to Control2

			if (!c1Cfg.Sync)
				c1Cfg.Sync = [];

			c1Cfg.Sync.push({ Target: c2Cfg, Type: syncType, Mapping: ((typeof(mapping) === "object") ? mapping : null) });

			// Configure synchronization from Control2 to Control1 if syncType is Bidirectional

			if (syncType === SMDesigner.Helpers.SyncType.Bidirectional)
			{
				if (!c2Cfg.Sync)
					c2Cfg.Sync = [];

				c2Cfg.Sync.push({ Target: c1Cfg, Type: syncType, Mapping: ((typeof(mapping) === "object") ? mapping : null) });
			}
		},

		// Enum used by LinkControls(..) function
		SyncType:
		{
			Bidirectional: 0,				// Keep both controls in sync. at all times
			UnidirectionalAlways: 1,		// Always sync. value of Control1 to Control2
			UnidirectionalEqualsOrNull: 2,	// Sync. value of Control1 to Control2 if its current value is either Null or equal to value of Control1
			UnidirectionalEquals: 3,		// Sync. value of Control1 to Control2 if its current value is equal to value of Control1

			UnidirectionalEqual: 2			// OBSOLETE - same as UnidirectionalEqualsOrNull - kept for backward compatibility
		}
	}
}
