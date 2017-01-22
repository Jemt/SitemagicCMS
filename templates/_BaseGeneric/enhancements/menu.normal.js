SMEventHandler.AddEventHandler(window, "load", function()
{
	// Make drop downs aware of missing children (TPLMenuIsEmpty CSS class)

	var menu = document.getElementById("TPLMenu");
	var uls = menu.getElementsByTagName("ul");

	for (var i = 0 ; i < uls.length ; i++)
		if (uls[i].children.length === 1) // An item container always contains one hidden list item to satisfy W3C validator
			SMDom.AddClass(uls[i], "TPLMenuIsEmpty");

	// Make menu aware of currently active link (TPLMenuIsCurrent and TPLMenuHasCurrent CSS classes)

	var links = menu.getElementsByTagName("a");

	for (var i = 0 ; i < links.length ; i++)
	{
		if (links[i].href === location.href || links[i].href.replace(/index.html/i, "") === location.href)
		{
			SMDom.AddClass(links[i].parentNode, "TPLMenuIsCurrent");

			if (links[i].parentNode.parentNode.parentNode.tagName.toLowerCase() === "li")
				SMDom.AddClass(links[i].parentNode.parentNode.parentNode, "TPLMenuHasCurrent");

			if (links[i].parentNode.parentNode.parentNode.parentNode.parentNode.tagName.toLowerCase() === "li")
				SMDom.AddClass(links[i].parentNode.parentNode.parentNode.parentNode.parentNode, "TPLMenuHasCurrent");
		}
	}

	// Create menu for mobile/tablet

	if (document.querySelectorAll)
	{
		// Define helper function used to generate link indentation

		var getIndent = function(item)
		{
			var level = "";
			while (item.parentElement.parentElement.tagName === "LI")
			{
				level += "--";
				item = item.parentElement.parentElement;
			}
			return ((level !== "") ? " " + level + " " : level);
		}

		// Create select drop down menu

		var select = document.createElement("select");
		select.onchange = function()
		{
			var opt = this.options[this.selectedIndex];
			this.selectedIndex = -1;
			location.href = opt.value;
		}

		// Read links from normal menu and add them to select drop down

		var menuLinks = document.querySelectorAll("div.TPLMenu a");
		var option = null;

		for (var i = 0 ; i < menuLinks.length ; i++)
		{
			option = new Option(getIndent(menuLinks[i].parentElement) + (menuLinks[i].textContent ? menuLinks[i].textContent : menuLinks[i].innerText), menuLinks[i].href); // Notice that .href property contains fully qualified URL (e.g. http://domain.com/Test.html) even though href attribute only contains Test.html in HTML source
			select.appendChild(option);

			// Select current item if link points to current page.

			if (/(\/|index.html|index.php)$/.test(location.href.toLowerCase()) === true && /(index.html|index.php)$/.test(option.value.toLowerCase()) === true)
			{
				// Current page URL ends with slash or index.html or index.php (front page),
				// and current link ends with either index.html or index.php (also front page)
				select.selectedIndex = i;
			}
			else if (location.href.toLowerCase().indexOf(option.value.toLowerCase()) !== -1)
			{
				select.selectedIndex = i;
			}
		}

		// Wrap select drop down in div to allow use of pseudo selectors (:before and :after) which does not work reliably on select element

		var outerDiv = document.createElement("div");
		outerDiv.className = "TPLMenuMobile";
		outerDiv.appendChild(select);

		// Insert select drop down right before ordinary menu

		document.querySelector("div.TPLMenu").parentElement.insertBefore(outerDiv, document.querySelector("div.TPLMenu"));
	}
});
