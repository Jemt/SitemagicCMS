var tplMenuTimeout = null;
var tplMenuLastOpened = null;

function tplMenuToggle(elmId, action)
{
	if (action === "onmouseover")
	{
		if (tplMenuLastOpened !== null && elmId !== tplMenuLastOpened)
			SMDom.SetStyle(tplMenuLastOpened, "visibility", "hidden");

		if (tplMenuTimeout !== null)
			clearTimeout(tplMenuTimeout);

		if (SMStringUtilities.Trim(SMDom.GetInnerValue(elmId)) !== "")
			SMDom.SetStyle(elmId, "visibility", "visible");
	}
	else if (action === "onmouseout")
	{
		tplMenuTimeout = setTimeout("SMDom.SetStyle('" + elmId + "', 'visibility', 'hidden')", 500)
	}

	tplMenuLastOpened = elmId;
}
