(function()
{
	if (SMBrowser.GetBrowser() === "MSIE")
	{
		// Force IE to repaint during the first couple of seconds after load.
		// This is necessary to fix problems with background positioning and
		// background size for pseudo element used to create header images.
		// Search designer.js for "iefix" for more details.

		var c = 0;
		var iv = -1;

		iv = setInterval(function()
		{
			// CSS class IeFixHeaderImage is only defined and in affect if a
			// Page Header Image is applied to current page. See designer.js for details.
			SMDom.AddClass(document.documentElement, "IeFixHeaderImage");
			setTimeout(function() { SMDom.RemoveClass(document.documentElement, "IeFixHeaderImage"); }, 100);

			if (++c === 10)
				clearInterval(iv);
		}, 1000);
	}
})();
