SMEventHandler.AddEventHandler(window, "load", tplEnsureEqualHeight);
SMEventHandler.AddEventHandler(window, "load", tplSetActiveLinkClass);

function tplEnsureEqualHeight()
{
	if (location.href.indexOf("stop") > -1)
		return;

	var m = document.getElementById("TPLMenu");
	var c = document.getElementById("TPLContent");

	// Using min-height instead of height, which allows dynamically created content
	// to increase the height of the containers. Using the height property may cause
	// dynamically added content to overflow the boundaries of the containers.

	if (m.offsetHeight > c.offsetHeight)
		c.style.minHeight = m.offsetHeight + "px";
	else
		m.style.minHeight = c.offsetHeight + "px";

	setTimeout(tplEnsureEqualHeight, 250); // Keep checking for changes
}

function tplSetActiveLinkClass()
{
	// Get menu links

	var menuLinks = [];

	if (document.querySelectorAll)
	{
		menuLinks = document.querySelectorAll("a.TPLMenuLink");
	}
	else
	{
		var allLinks = document.getElementsByTagName("a");

		for (var i = 0 ; i < allLinks.length ; i++)
			if (allLinks[i].className.indexOf("TPLMenuLink") > -1)
				menuLinks.push(allLinks[i]);
	}

	// Set active link and open sub menu if necessary

	for (var i = 0 ; i < menuLinks.length ; i++)
	{
		if (location.href.indexOf(menuLinks[i].href) > -1) // Using indexOf to support extra URL parameters added during operation (e.g. when saving Settings)
		{
			// Set link clicked active
			menuLinks[i].className += (menuLinks[i].className !== "" ? " " : "") + "TPLMenuLinkActive";

			// If link clicked is a sub menu link, make the sub menu active as well
			if (menuLinks[i].className.indexOf("TPLSubMenuLink") > -1)
				menuLinks[i].parentNode.className += (menuLinks[i].className !== "" ? " " : "") + "TPLSubMenuActive";

			break;
		}
	}
}
