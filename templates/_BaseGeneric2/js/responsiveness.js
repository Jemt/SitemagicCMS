// Size Media Query replacement for legacy browsers to allow responsive designs on e.g. IE8.
// Often times this is used to make sure something is applied ONLY to the Desktop version
// (using TplOnDesktop), which would not be possible in Legacy IE - here we would have to
// do it the other way around; general styling that is reverted/changed on mobile devices.
// Originally implemented to support HyperSpace template.

(function()
{
	var prevClass = "";
	var prevWidth = -1;
	var checkSize = null;

	checkSize = function()
	{
		var width = SMBrowser.GetPageWidth();

		if (width !== prevWidth)
		{
			prevWidth = width;

			var newClass = "TplOnDesktop";

			if (width <= 500)
				newClass = "TplOnPhone";
			else if (width <= 900)
				newClass = "TplOnTablet";

			if (newClass !== prevClass)
			{
				if (prevClass !== "")
				{
					SMDom.RemoveClass(document.documentElement, prevClass);

					if (prevClass === "TplOnTablet" || prevClass === "TplOnPhone")
						SMDom.RemoveClass(document.documentElement, "TplOnMobile");
				}

				prevClass = newClass;
				SMDom.AddClass(document.documentElement, prevClass);

				if (prevClass === "TplOnTablet" || prevClass === "TplOnPhone")
					SMDom.AddClass(document.documentElement, "TplOnMobile");
			}
		}

		setTimeout(checkSize, 250);
	};

	checkSize();
})();
