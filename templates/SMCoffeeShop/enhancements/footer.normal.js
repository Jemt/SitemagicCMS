// Make stretched footer stick to bottom of window if window is higher than the
// content. When stretched, it looks odd that it "floats" below the content if
// the window is higher than the content.

SMEventHandler.AddEventHandler(document, "DOMContentLoaded", function(e)
{
	var footer = document.getElementById("TPLFooter");
	var lastContentHeight = document.body.offsetHeight;
	var initialized = false;
	var enabled = false;

	footer.StickyFooter = function(enable) // Called from Designer Definition when footer is stretched (designer.js)
	{
		if (enable === enabled)
			return;

		if (enable === false)
		{
			enabled = false;
			release();

			return;
		}

		enabled = true;
		position();

		if (initialized === false)
		{
			initialized = true;

			// Recalculate frequently in case page contains dynamic content that changes the size of content
			setInterval(function()
			{
				if (enabled === false)
					return;

				if (lastContentHeight !== document.body.offsetHeight)
				{
					position();
					lastContentHeight = document.body.offsetHeight;
				}

			}, 200);

			// Recalculate immediately if window is resized, zoomed, or orientation is changed (device is rotated).
			// Known problem: Seems to fire when orientation is initially horizontal (page reloaded) and changed to
			// vertical, but not when initially vertical (page reloaded) and changed to horizontal.
			SMEventHandler.AddEventHandler(window, "resize", function(e)
			{
				if (enabled === false)
					return;

				position();
			});
		}
	}

	function position()
	{
		release();

		var bottomPos = footer.offsetTop + footer.offsetHeight;
		var pageHeight = SMBrowser.GetPageHeight();
		var pos = SMDom.GetComputedStyle(footer, "position");

		if (bottomPos < pageHeight && pos !== "static") // Mobile optimization may force footer back to ordinary positioning (position:static) below content - respect that
		{
			attach();
		}
	}

	function attach()
	{
		footer.style.position = "fixed";
		footer.style.bottom = "0px";
	}

	function release()
	{
		footer.style.position = "";
		footer.style.bottom = "";
	}

	// Make footer sticky if it has been stretched using Designer

	if (SMDom.GetComputedStyle(footer, "position") === "absolute")
	{
		footer.StickyFooter(true);
	}
});
