function SMImageMontage(path, containerId)
{
	var filePath = path;

	var container = document.getElementById(containerId);
	container.className = "SMImageMontageContainer";

	// Start loading indicator (removed once Show() is invoked)

	var loading = document.createElement("div");
	loading.className = "SMImageMontageLoading";

	var loadingImg = document.createElement("img");
	loadingImg.src = filePath + "/images/loading.gif";

	var loadingStatus = document.createElement("div");
	loadingStatus.className = "SMImageMontageLoadingStatus";
	loadingStatus.innerHTML = "0%";

	loading.appendChild(loadingImg);
	loading.appendChild(loadingStatus);
	container.appendChild(loading);

	// Settings
	this.MinHeight = 100;
	this.MaxHeight = 300;
	this.Margin = 3;
	this.SlideShowInterval = 3000;

	// Private members
	var fullscreenLayer = null;
	var fullscreenTitle = null;
	var fullscreenDescription = null;
	var fullscreenImage = null;
	var imgInFullScreen = null;
	var slideShowTimer = null;

	// Buttons
	var cmdPrev = null;
	var cmdNext = null;
	var cmdPlay = null;

	// Make "this" available to events where "this" has another meaning
	var me = this;

	// Create fullscreen layer
	function ensureFullScreenLayer()
	{
		if (fullscreenLayer !== null)
			return;

		// Dark background layer
		fullscreenLayer = document.createElement("div");
		fullscreenLayer.className = "SMImageMontageFullScreenLayer";
		fullscreenLayer.onclick = function(e)
		{
			closeFullScreen();
		}

		// Previous button
		cmdPrev = document.createElement("div");
		cmdPrev.className = "SMImageMontageButton SMImageMontageButtonPrevious";
		cmdPrev.onclick = function(e)
		{
			previousImage();

			// Make sure fullscreen is not closed when clicking button

			var ev = (window.event ? window.event : e);

			ev.cancelBubble = true;
			if (ev.stopPropagation) ev.stopPropagation();
		}

		// Next button
		cmdNext = document.createElement("div");
		cmdNext.className = "SMImageMontageButton SMImageMontageButtonNext";
		cmdNext.onclick = function(e)
		{
			nextImage();

			// Make sure fullscreen is not closed when clicking button

			var ev = (window.event ? window.event : e);

			ev.cancelBubble = true;
			if (ev.stopPropagation) ev.stopPropagation();
		}

		// Slide show button
		cmdPlay = document.createElement("div");
		cmdPlay.className = "SMImageMontageButton SMImageMontageButtonSlideShow SMImageMontageButtonSlideShowPlay";
		cmdPlay.onclick = function(e)
		{
			toggleSlideShow();

			// Make sure fullscreen is not closed when clicking button

			var ev = (window.event ? window.event : e);

			ev.cancelBubble = true;
			if (ev.stopPropagation) ev.stopPropagation();
		}

		// Fullscreen title
		fullscreenTitle = document.createElement("div");
		fullscreenTitle.className = "SMImageMontageTitle";

		// Fullscreen description
		fullscreenDescription = document.createElement("div");
		fullscreenDescription.className = "SMImageMontageDescription";

		// Append elements
		fullscreenLayer.appendChild(cmdPrev);
		fullscreenLayer.appendChild(cmdNext);
		fullscreenLayer.appendChild(cmdPlay);
		fullscreenLayer.appendChild(fullscreenTitle);
		fullscreenLayer.appendChild(fullscreenDescription);
		container.appendChild(fullscreenLayer);
	}

	// Load image with specified src in fullscreen
	function loadFullScreenImage(src)
	{
		ensureFullScreenLayer();

		// Remove image previously displayed in fullscreen
		if (fullscreenImage !== null)
			fullscreenLayer.removeChild(fullscreenImage);

		// Create fullscreen image and append to fullscreen layer
		fullscreenImage = document.createElement("img");
		fullscreenImage.className = "SMImageMontageFullScreenImage";
		fullscreenImage.style.visibility = "hidden";
		fullscreenImage.onload = optimizeSize;
		fullscreenImage.onstatechange = optimizeSize;
		fullscreenImage.src = src;
		fullscreenImage.alt = "";
		fullscreenLayer.appendChild(fullscreenImage);
	}

	// Open specified img element in fullscreen
	function openFullScreen(img)
	{
		// Load image into fullscreen

		imgInFullScreen = img;
		loadFullScreenImage(img.src);

		// Remove page scrollbar
		document.getElementsByTagName("html")[0].style.overflowY = ""; // Set by Automatic Image Montage
		document.getElementsByTagName("body")[0].style.overflow = "hidden";

		// Display fullscreen

		fullscreenLayer.style.display = "block";

		// Add support for keyboard navigation

		if (document.addEventListener) // W3C
			document.addEventListener("keydown", keyboardNavigate, false);
		else if (document.attachEvent) // IE
			document.attachEvent("onkeydown", keyboardNavigate);
	}

	function closeFullScreen()
	{
		// Restore page scrollbar

		document.getElementsByTagName("html")[0].style.overflowY = "scroll"; // Set by Automatic Image Montage
		document.getElementsByTagName("body")[0].style.overflow = "";

		// Make sure slide show is stopped

		stopSlideShow();

		// Close fullscreen image

		fullscreenLayer.style.display = "none";

		// Remove support for keyboard navigation

		if (document.removeEventListener) // W3C
			document.removeEventListener("keydown", keyboardNavigate, false);
		else if (document.attachEvent) // IE
			document.detachEvent("onkeydown", keyboardNavigate);
	}

	// Keyboard navigation handler
	function keyboardNavigate(e)
	{
		var ev = (window.event ? window.event : e);

		if (ev.keyCode === 37)
			previousImage();
		else if (ev.keyCode === 39)
			nextImage();
		else if (ev.keyCode === 32)
			toggleSlideShow();
		else if (ev.keyCode === 27)
			closeFullScreen();
	}

	// Load previous image
	function previousImage()
	{
		if (imgInFullScreen.parentNode.previousSibling !== null)
		{
			var img = imgInFullScreen.parentNode.previousSibling.firstChild;
			imgInFullScreen = img;
			loadFullScreenImage(img.src);

			return true;
		}

		return false;
	}

	// Load next image
	function nextImage()
	{
		if (imgInFullScreen.parentNode.nextSibling.tagName.toLowerCase() === "a")
		{
			var img = imgInFullScreen.parentNode.nextSibling.firstChild;
			imgInFullScreen = img;
			loadFullScreenImage(img.src);

			return true;
		}

		return false;
	}

	function toggleSlideShow()
	{
		if (slideShowTimer === null)
			startSlideShow();
		else
			stopSlideShow();
	}

	function startSlideShow()
	{
		if (slideShowTimer !== null)
			return;

		var f = function()
		{
			var res = nextImage();

			if (res === false) // Reached end of gallery - rewind
			{
				var img = container.getElementsByTagName("img")[0];
				imgInFullScreen = img;
				loadFullScreenImage(img.src);
			}

			slideShowTimer = setTimeout(f, me.SlideShowInterval);
		}

		cmdPlay.className = cmdPlay.className.replace("Play", "Pause");
		f();
	}

	function stopSlideShow()
	{
		if (slideShowTimer !== null)
			clearTimeout(slideShowTimer);

		slideShowTimer = null;
		cmdPlay.className = cmdPlay.className.replace("Pause", "Play");
	}

	// Optimize fulscreen image size (make it as large as possible)
	function optimizeSize(e)
	{
		var ev = (window.event ? window.event : e);
		var img = this;

		// IE8 and earlier: Cancel out if image is not ready (loaded)
		if (img.readyState)
		{
			img.onload = null;

			if (img.readyState !== "complete" && img.readyState !== "loaded")
				return;
		}

		// Optimize size

		if (img.width > img.height) // Handle image in landscape mode
		{
			var ratio = img.height / img.width;
			var width = img.width;
			var height = img.height;

			// Decrease image size as long as width and height is larger than page width minus offset (reserved for buttons, title, and description)
			while (width > (SMBrowser.GetPageWidth() - 200) || height > (SMBrowser.GetPageHeight() - 150))
			{
				width = width - 25;
				height = width * ratio;
			}

			img.width = Math.round(width);
			img.height = Math.round(height);
		}
		else // Handle image in portrait mode
		{
			var ratio = img.width / img.height;
			var width = img.width;
			var height = img.height;

			// Decrease image size as long as width and height is larger than page width minus offset (reserved for buttons, title, and description)
			while (width > (SMBrowser.GetPageWidth() - 200) || height > (SMBrowser.GetPageHeight() - 150))
			{
				height = height - 25;
				width = height * ratio;
			}

			// Assign optimized dimensions
			img.width = Math.round(width);
			img.height = Math.round(height);
		}

		// Center image

		var x = (SMBrowser.GetPageWidth()/2) - (img.width/2);
		var y = (SMBrowser.GetPageHeight()/2) - (img.height/2);
		y = y + 20; // Move a bit closer to the bottom

		img.style.position = "absolute";
		img.style.top = y + "px";
		img.style.left = x + "px";

		// Insert title and description

		fullscreenTitle.innerHTML = imgInFullScreen.title;
		fullscreenDescription.innerHTML = imgInFullScreen.alt;

		// Display

		img.style.visibility = "visible";
	}

	// Add image to gallery
	this.AddImage = function(src, title, description)
	{
		// Create image element
		var img = document.createElement("img");
		img.src = src;
		img.alt = "";
		img.style.display = "none";
		img.onclick = function()
		{
			openFullScreen(img);
		}

		// Assign title and description if specified
		if (title !== undefined)
			img.title = title;
		if (description !== undefined)
			img.alt = description;

		// Image is expected to be placed within a link (required by Image Montage jQuery plugin)
		var link = document.createElement("a");
		link.href = "javascript:void(0)";
		link.appendChild(img);

		container.appendChild(link);
	}

	// Render Image Montage
	this.Start = function()
	{
		// Make values available to closure - "this" will have another meaning
		var minHeight = this.MinHeight;
		var maxHeight = this.MaxHeight;
		var margin = this.Margin;

		// Load jQuery and Image Montage plugin
		SMResourceManager.Jquery.Load(
		{
			version: "1.6.2",
			plugins: [ { source: filePath + "/js/jquery.montage.min.js" } ],
			complete: function($)
			{
				// Code specific to Automatic Image Montage jQuery plugin

				var container = $("#" + containerId),
				imgs = container.find("a img").hide(),
				totalImgs = imgs.length,
				cnt = 0;

				if (totalImgs === 0)
					loading.parentNode.removeChild(loading); // Remove loading indicator

				imgs.each(function(i)
				{
					var img = $(this);
					$("<img>").load(function()
					{
						++cnt;

						loadingStatus.innerHTML = Math.round((cnt / totalImgs) * 100) + "%";

						if(cnt === totalImgs)
						{
							loading.parentNode.removeChild(loading); // Remove loading indicator

							imgs.show();
							container.montage(
							{
								fillLastRow: true,
								alternateHeight: true,
								alternateHeightRange:
								{
									min : minHeight,
									max : maxHeight
								},
								margin : margin
							});
						}
					}).attr("src",img.attr("src"));
				});
			}
		});
	}
}
