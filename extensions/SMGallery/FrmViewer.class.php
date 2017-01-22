<?php

class SMGalleryFrmViewer implements SMIExtensionForm
{
	private $context;
	private $instanceId;
	private $galleryPreset;
	private $lang;

	private $images;

	private $lstGalleries;
	private $cmdBack;
	private $cmdForward;

	public function __construct(SMContext $context, $instanceId, $galleryPreset)
	{
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "galleryPreset", $galleryPreset, SMTypeCheckType::$String);

		$this->context = $context;
		$this->galleryPreset = $galleryPreset;
		$this->instanceId = (string)$instanceId;

		$this->lang = new SMLanguageHandler("SMGallery");

		// Only set title if extension is executed alone. Title will not be set if executed within a content page.
		if ($this->context->GetExtensionName() === "SMGallery")
			$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->handlePostBack();

		$this->loadImages();
	}

	private function createControls()
	{
		$this->images = array();

		$this->createGalleryList();

		$this->cmdBack = new SMLinkButton("SMGalleryBack" . $this->instanceId);
		$this->cmdBack->SetIcon(SMImageProvider::GetImage(SMImageType::$Left));
		$this->cmdBack->SetOnclick("smGalleryChangePage" . $this->instanceId . "('back')");
		$this->cmdBack->SetPostBack(false);

		$this->cmdForward = new SMLinkButton("SMGalleryForward" . $this->instanceId);
		$this->cmdForward->SetIcon(SMImageProvider::GetImage(SMImageType::$Right));
		$this->cmdForward->SetOnclick("smGalleryChangePage" . $this->instanceId . "('forward')");
		$this->cmdForward->SetPostBack(false);
	}

	private function createGalleryList()
	{
		$this->lstGalleries = new SMOptionList("SMGalleryList" . $this->instanceId);
		$this->lstGalleries->SetAutoPostBack(true);

		$this->lstGalleries->AddOption(new SMOptionListItem("SMGalleryOptionChoose" . $this->instanceId, $this->lang->GetTranslation("Choose"), ""));
		$this->lstGalleries->AddOption(new SMOptionListItem("SMGalleryOptionEmpty" . $this->instanceId, "", ""));

		if ($this->context->GetForm()->PostBack() === false && $this->galleryPreset !== "")
			$this->lstGalleries->SetSelectedValue($this->galleryPreset);

		$galleryFolder = SMEnvironment::GetFilesDirectory() . "/gallery";

		if (SMFileSystem::FolderExists($galleryFolder) === true)
		{
			$galleries = SMFileSystem::GetFolders($galleryFolder);

			for ($i = 0 ; $i < count($galleries) ; $i++)
				$this->lstGalleries->AddOption(new SMOptionListItem("SMGalleryOption" . $i . "Instance" . $this->instanceId, $galleries[$i], $galleries[$i]));
		}
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
		}
	}

	private function loadImages()
	{
		$gallery = $this->lstGalleries->GetSelectedValue();

		if ($gallery === null || $gallery === "")
			return;

		$this->images = SMFileSystem::GetFiles(SMEnvironment::GetFilesDirectory() . "/gallery/" . $gallery);

		for ($i = 0 ; $i < count($this->images) ; $i++)
			$this->images[$i] = SMEnvironment::GetFilesDirectory() . "/gallery/" . str_replace(" ", "%20", $gallery) . "/" . str_replace(" ", "%20", $this->images[$i]);
	}

	private function getConfigValue($key, $defaultValue)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "defaultValue", $defaultValue, SMTypeCheckType::$String);

		$attribute = SMAttributes::GetAttribute($key);
		return (($attribute !== null && $attribute !== "") ? $attribute : $defaultValue);
	}

	public function Render()
	{
		$output = "";

		// Render gallery changer (drop down)

		$output .= $this->lstGalleries->Render();

		// Render first gallery page

		if (count($this->images) > 0)
		{
			// Read configuration

			$columns = (int)$this->getConfigValue("SMGalleryColumns", "3");
			$rows = (int)$this->getConfigValue("SMGalleryRows", "3");
			$width = $this->getConfigValue("SMGalleryWidth", "");
			$height = $this->getConfigValue("SMGalleryHeight", "");
			$padding = $this->getConfigValue("SMGalleryPadding", "");

			// Prepare image styles

			if ($width === "" && $height === "")
				$width = "150";

			$width = (($width !== "") ? "width: " . $width . "px": "");
			$height = (($height !== "") ? "height: " . $height . "px": "");
			$padding = (($padding !== "") ? "padding: " . $padding . "px": "");

			$style = " style=\"border-width: 0px; cursor: pointer; float:left{;1}{width}{;2}{height}{;3}{padding}\"";
			$style = str_replace("{;1}", (($width !== "") ? "; " : ""), $style);
			$style = str_replace("{width}", $width, $style);
			$style = str_replace("{;2}", (($height !== "") ? "; " : ""), $style);
			$style = str_replace("{height}", $height, $style);
			$style = str_replace("{;3}", (($padding !== "") ? "; " : ""), $style);
			$style = str_replace("{padding}", $padding, $style);

			$pagesTotal = ceil(count($this->images) / ($columns * $rows)) - 1;

			// Render back/forward button and page status (x of y)

			$output .= " " . $this->cmdBack->Render();
			$output .= " " . $this->cmdForward->Render();

			$pageXY = $this->lang->GetTranslation("Page");
			$pageXY = str_replace("{x}", "<span id=\"smGalleryPage" . $this->instanceId . "\">1</span>", $pageXY);
			$pageXY = str_replace("{y}", (string)($pagesTotal + 1), $pageXY);
			$output .= " " . $pageXY;

			$output .= "<br><br>";

			// Render images on first page

			$imageCount = -1;
			$break = false;

			for ($i = 0 ; $i < $rows ; $i++)
			{
				if ($break === true)
					break;

				// The style used below makes the div stretch to the height of the images. Notice: width=100% makes it difficult to center gallery in page editor.
				$output .= "<div style=\"overflow: auto; width: 100%\">";

				for ($j = 0 ; $j < $columns ; $j++)
				{
					$imageCount++;

					if ($imageCount >= count($this->images))
					{
						$break = true;
						break;
					}

					$output .= "<img src=\"" . $this->images[$imageCount] . "\" id=\"smGalleryImage" . $imageCount . "_" . $this->instanceId . "\" onclick=\"smGalleryDisplayImage" . $this->instanceId . "(this.src)\" alt=\"\"" . $style . ">";
				}

				$output .= "</div>";
			}

			// Client side paging

			$imagesStr = "";
			for ($i = 0 ; $i < count($this->images) ; $i++)
				$imagesStr .= (($imagesStr !== "") ? ", " : "") . "\"" . $this->images[$i] . "\"";

			$imagesPerPage = $rows * $columns;

			$output .= "
			<script type=\"text/javascript\">
			smGalleryCurrentPage" . $this->instanceId . " = 0;
			smGalleryImages" . $this->instanceId . " = new Array(" . $imagesStr . ");

			function smGalleryChangePage" . $this->instanceId . "(direction)
			{
				var images = smGalleryGetImages" . $this->instanceId . "(direction);

				if (images === null)
					return;

				smGalleryClearImages" . $this->instanceId . "();
				smGalleryLoadImages" . $this->instanceId . "(images);

				smGalleryCurrentPage" . $this->instanceId . " = ((direction === \"forward\") ? smGalleryCurrentPage" . $this->instanceId . " + 1 : smGalleryCurrentPage" . $this->instanceId . " - 1);
				document.getElementById(\"smGalleryPage" . $this->instanceId . "\").innerHTML = smGalleryCurrentPage" . $this->instanceId . " + 1;
			}

			function smGalleryGetImages" . $this->instanceId . "(direction)
			{
				var offset = -1;

				if (direction === \"forward\")
					offset = (smGalleryCurrentPage" . $this->instanceId . " + 1) * " . $imagesPerPage . ";
				else
					offset = (smGalleryCurrentPage" . $this->instanceId . " - 1) * " . $imagesPerPage . ";

				var newImageSet = new Array();

				if (offset >= smGalleryImages" . $this->instanceId . ".length || offset < 0)
					return null;

				for (var i = 0 ; i < " . $imagesPerPage . " ; i++)
				{
					if (offset + i === smGalleryImages" . $this->instanceId . ".length)
						break;

					newImageSet.push(smGalleryImages" . $this->instanceId . "[offset + i]);
				}

				return newImageSet;
			}

			function smGalleryClearImages" . $this->instanceId . "()
			{
				for (var i = 0 ; i < " . $imagesPerPage . " ; i++)
					document.getElementById(\"smGalleryImage\" + i + \"_" . $this->instanceId . "\").style.display = \"none\";
			}

			function smGalleryLoadImages" . $this->instanceId . "(images)
			{
				for (var i = 0 ; i < images.length ; i++)
				{
					document.getElementById(\"smGalleryImage\" + i + \"_" . $this->instanceId . "\").src = images[i];
					document.getElementById(\"smGalleryImage\" + i + \"_" . $this->instanceId . "\").style.display = \"block\";
				}
			}

			function smGalleryDisplayImage" . $this->instanceId . "(imgSrc)
			{
				var image = new Image();
				image.onload = function()
				{
					var w = image.width;
					var h = image.height;

					if (w > 640)
					{
						h = (h / w) * 640;
						w = 640;
					}

					if (h > 480)
					{
						w = (w / h) * 480;
						h = 480;
					}

					var win = new SMWindow(\"smGalleryWindow\" + SMRandom.CreateGuid());
					win.SetUrl(imgSrc);
					win.SetSize(Math.round(w), Math.round(h));
					win.SetDisplayScrollBars(false);
					win.Show();
				}
				image.src = imgSrc;
			}
			</script>
			";
		}

		return $output;
	}
}

?>
