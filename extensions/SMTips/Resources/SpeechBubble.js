SMTips = {};
SMTips.CallbackUrl = null;
SMTips.Language =
{
	Ok: "Ok",
	Disable: "Disable tips",
	Confirm: "Some features in Sitemagic CMS requires some explaination. Are you absolutely sure you want to disable tips?"
};
SMTips.Enabled = true;

SMTips.GetTargetPosition = function(target)
{
	var pos = { X: 0, Y: 0 };
	var view = null;

	while (target)
    {
        pos.X += target.offsetLeft;
        pos.Y += target.offsetTop;

		// Handle elements in iframes

		if (target.offsetParent === null)
		{
			view = target.ownerDocument.defaultView || target.ownerDocument.parentWindow; // Resolved in loop, might be contained in nested iFrames
			target = null;

			if (window !== view) // Hosted in iFrame
			{
				// Get iframe DOM element in parent window which only works if src attribute is set
				target = view.parent.document.querySelector("iframe[src='" + view.location.href + "']");
			}
		}
		else
		{
			target = target.offsetParent;
		}
	}

    return pos;
}

SMTips.CreateSpeechBubble = function(id, tar, content, closeCallback, disableCallback)
{
	if (!document.querySelector || document.forms.length === 0)
		return null;

	// Handle disabled speech bubbles which may be displayed as part of a chain

	if (SMTips.Enabled === false)
	{
		if (disableCallback)
			disableCallback();

		return;
	}

	// Skip tip if previously closed using OK button

	if (SMCookie.GetCookie("SMTipsHide" + id) !== null)
	{
		if (closeCallback)
			closeCallback();

		return null;
	}

	// Get target element to position tip next to

	var target = null;

	if (typeof(tar) === "string")
		target = document.querySelector(tar);
	else
		target = tar;

	if (!target)
		return null;

	// Create speech bubble

	var sb = document.createElement("div");
	sb.className = "SMTipsSpeechBubble";
	sb.innerHTML = content;

	// Create close button

	var cmdClose = document.createElement("div");
	cmdClose.innerHTML = "x";
	cmdClose.className = "SMTipsCloseButton";
	cmdClose.onclick = function(e)
	{
		sb.parentElement.removeChild(sb);

		if (closeCallback)
			closeCallback();
	}
	sb.appendChild(cmdClose);

	// Create OK and Disable buttons

	var buttons = document.createElement("div");
	buttons.className = "SMTipsButtonPanel";
	sb.appendChild(buttons);

	var cmdOk = document.createElement("div");
	cmdOk.innerHTML = SMTips.Language.Ok;
	cmdOk.className = "SMTipsOkButton";
	cmdOk.onclick = function(e)
	{
		SMCookie.SetCookie("SMTipsHide" + id, "true", 3 * 365 * 24 * 60 * 60) // Expires after 3 year
		cmdClose.onclick(e);
	}
	buttons.appendChild(cmdOk);

	if (SMTips.CallbackUrl !== null)
	{
		var cmdDisable = document.createElement("div");
		cmdDisable.innerHTML = SMTips.Language.Disable;
		cmdDisable.className = "SMTipsDisableButton";
		cmdDisable.onclick = function(e)
		{
			if (confirm(SMTips.Language.Confirm) === true)
			{
				// Disable extension

				var req = new SMHttpRequest(SMTips.CallbackUrl, true);
				req.SetStateListener(function()
				{
					if (req.GetCurrentState() === 4)
					{
						if (req.GetHttpStatus() === 200)
						{
							sb.parentElement.removeChild(sb);
							SMTips.Enabled = false;

							if (disableCallback)
								disableCallback();
						}
						else
						{
							alert("An error occurred, tips may not have been disabled");
						}
					}
				});
				req.Start();
			}
		}
		buttons.appendChild(cmdDisable);
	}

	// Position tip next to target

	var pos = SMTips.GetTargetPosition(target);

	sb.style.left = pos.X + target.offsetWidth + 15 + "px"; // Add 15px to prevent arrow from overlapping element
	sb.style.top = pos.Y + (target.offsetHeight / 2) - 22 + "px"; // Substract 22px to make arrow point at the middle of the element, rather than having upper left corner of speech bubble point at the middle of the element

	if (parseInt(sb.style.top) < 0) // Make sure tip stays within viewport
		sb.style.top = "0px";

	// Insert into page

	document.forms[0].appendChild(sb); // Append to form element rather than body element, to allow CSS selectors to use action attribute containing URL

	// Adjust if tip goes beyond viewport horizontally (element must be rooted for offsetWidth to work)

	var viewWidth = window.innerWidth || document.documentElement.clientWidth; // clientWidth = Legacy IE
	var off = viewWidth - 10 - (parseInt(sb.style.left) + sb.offsetWidth); // 10 = substract a little to make sure tip does not touch edge

	if (off < 0)
		sb.style.left = parseInt(sb.style.left) + off + "px"; // Remember, off variable is negative

	// If associated with iFrame, make sure it closes along with it

	if (window !== (target.ownerDocument.defaultView || target.ownerDocument.parentWindow))
	{
		var intv = null;
		intv = setInterval(function()
		{
			if (sb.parentElement === null)
			{
				// Tip was closed normally

				clearInterval(intv);
				return;
			}

			// iFrame was closed without closing tip first

			var legacyDenied = false;

			try
			{
				var tmp = target.ownerDocument; // Throws Access Denied error in Legacy IE when document containing target no longer exists
			}
			catch (err)
			{
				legacyDenied = true;
			}

			if (legacyDenied === true || !(target.ownerDocument.defaultView || target.ownerDocument.parentWindow))
			{
				// Element associated with tip was found within an iframe which no longer exists (has been closed)

				sb.parentElement.removeChild(sb);
				clearInterval(intv);
			}
		}, 500);
	}

	return sb;
}
