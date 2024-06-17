<?php

// ===========================================================================================

$language = new SMLanguageHandler($this->context->GetExtensionName());

// Insert gallery link in menu link picker
$cfg["MenuLinkPicker"]["Title"] = $language->GetTranslation("SelectGallery");
$cfg["MenuLinkPicker"]["Category"] = $language->GetTranslation("Title");

// Insert gallery link in editor link picker
$cfg["PageLinkPicker"]["Title"] = $language->GetTranslation("SelectGallery");
$cfg["PageLinkPicker"]["Category"] = $language->GetTranslation("Title");

// Notice: Links to individual galleries are added to the link pickers
//         in ImageMontage.run.php which allows us to "hook in" to
//         all life cycle events.

if ($render === true) {

// ===========================================================================================

$this->SetIsIntegrated(true);

function smImageMontageGetExifData($file)
{
	$desc = "";

	try
	{
		if (defined("EXIF_USE_MBSTRING") === false)
			return "";

		$tmp = array();

		$taken = "";
		$model = "";
		$focal = "";
		$expos = "";
		$fnumb = "";
		$speed = "";

		$exif = exif_read_data(SMStringUtilities::Utf8Encode($file), "IFD0", 0);

		if (isset($exif["DateTime"]) === true)
		{
			$tmp = explode(" ", $exif["DateTime"]);
			$tmp = explode(":", $tmp[0]);
			$taken = $tmp[0] . "-" . $tmp[1] . "-" . $tmp[2];
		}

		if (isset($exif["Model"]) === true)
			$model = $exif["Model"];

		if (isset($exif["FocalLength"]) === true)
		{
			$tmp = explode("/", $exif["FocalLength"]);
			$focal = round((int)$tmp[0]/(int)$tmp[1], 0) . "mm";
		}

		$exif = exif_read_data(SMStringUtilities::Utf8Encode($file), "EXIF", 0);

		if (isset($exif["ExposureTime"]) === true)
			$expos = $exif["ExposureTime"] . "s";

		if (isset($exif["FNumber"]) === true)
		{
			$tmp = explode("/", $exif["FNumber"]);
			$fnumb = "f" . round((int)$tmp[0]/(int)$tmp[1], 0);
		}

		if (isset($exif["ISOSpeedRatings"]) === true)
			$speed = "ISO " . $exif["ISOSpeedRatings"];

		$desc .= (($desc !== "" && $model !== "") ? " | " : "") . $model;
		$desc .= (($desc !== "" && $taken !== "") ? " | " : "") . $taken;
		$desc .= (($desc !== "" && $focal !== "") ? " | " : "") . $focal;
		$desc .= (($desc !== "" && $expos !== "") ? " | " : "") . $expos;
		$desc .= (($desc !== "" && $fnumb !== "") ? " | " : "") . $fnumb;
		$desc .= (($desc !== "" && $speed !== "") ? " | " : "") . $speed;
	}
	catch (Exception $ex)
	{
		SMLog::Log(__FILE__, __LINE__, "Unable to load EXIF data for image '" . $file . "' - exception: " . $ex->getMessage());
	}

	return $desc;
}

$extensionPath = SMExtensionManager::GetExtensionPath($this->context->GetExtensionName());

// Load settings

$minHeight = SMAttributes::GetAttribute("SMImageMontageMinHeight");
$maxHeight = SMAttributes::GetAttribute("SMImageMontageMaxHeight");
$margin = SMAttributes::GetAttribute("SMImageMontageMargin");
$displayTitle = (SMAttributes::GetAttribute("SMImageMontageDisplayTitle") === "true");
$displayImageTitle = (SMAttributes::GetAttribute("SMImageMontageDisplayImageTitle") === "true");
$displayImageExif = (SMAttributes::GetAttribute("SMImageMontageDisplayImageExif") === "true");
$displayPicker = (SMAttributes::GetAttribute("SMImageMontageDisplayPicker") === "true");
$shuffle = (SMAttributes::GetAttribute("SMImageMontageShuffle") === "true");
$slideShowInterval = SMAttributes::GetAttribute("SMImageMontageSlideShowInterval");

$minHeight = (($minHeight !== null) ? $minHeight : "100");
$maxHeight = (($maxHeight !== null) ? $maxHeight : "300");
$margin = (($margin !== null) ? $margin : "3");
$slideShowInterval = (($slideShowInterval !== null) ? $slideShowInterval : "3000");

// Create path to gallery folder

$filesDir = SMEnvironment::GetFilesDirectory();
$galleryDir = $filesDir . "/" . "gallery";

// Get all sub folders from gallery folder

$galleries = SMFileSystem::GetFolders($galleryDir);

// Abort if no galleries are found

if (count($galleries) === 0)
{
	echo $language->GetTranslation("NoGalleries");
	return;
}

// Register Stylesheet and JavaScript for the gallery

$template = $this->context->GetTemplate();
$template->RegisterResource(SMTemplateResource::$StyleSheet, $extensionPath . "/css/style.css");
$template->RegisterResource(SMTemplateResource::$JavaScript, $extensionPath . "/js/ImageMontage.js");

// Create drop down menu for selecting a gallery

$menu = null;
$galleryPreset = SMEnvironment::GetQueryValue("SMImageMontageGallery", SMValueRestriction::$SafePath);

if ($displayPicker === true || $galleryPreset === null)
{
	$menu = new SMOptionList("SMImageMontageGalleries");
	$menu->SetAutoPostBack(true);
	$menu->AddOption(new SMOptionListItem("SMImageMontageGalleryEmpty", "", ""));

	// Add galleries to drop down menu

	for ($i = 0 ; $i < count($galleries); $i++)
		$menu->AddOption(new SMOptionListItem("SMImageMontageGallery" . $i, $galleries[$i], $galleryDir . "/" . $galleries[$i]));

	// Pre-select gallery (from URL) if a selection has not been made

	if ($menu->GetSelectedValue() === null && $galleryPreset !== null)
		$menu->SetSelectedValue($galleryDir . "/" . $galleryPreset);
}

// Load HTML template containing JavaScript and CSS for the gallery

$montageTemplate = new SMTemplate($extensionPath . "/ImageMontage.html");
$montageTemplate->ReplaceTag(new SMKeyValue("ExtensionPath", $extensionPath));
$montageTemplate->ReplaceTag(new SMKeyValue("MinHeight", $minHeight));
$montageTemplate->ReplaceTag(new SMKeyValue("MaxHeight", $maxHeight));
$montageTemplate->ReplaceTag(new SMKeyValue("Margin", $margin));
$montageTemplate->ReplaceTag(new SMKeyValue("SlideShowInterval", $slideShowInterval));

// Prepare image data and insert it into the HTML template if a gallery was selected in the drop down menu

$galleryPath = null;

if ($menu !== null)
	$galleryPath = (($menu->GetSelectedValue() !== "") ? $menu->GetSelectedValue() : null);
else if ($galleryPreset !== null)
	$galleryPath = $galleryDir . "/" . $galleryPreset;

if ($galleryPath !== null)
{
	// Load image paths from selected gallery folder

	$images = SMFileSystem::GetFiles($galleryPath);

	// Shuffle images

	if ($shuffle === true)
		shuffle($images);

	// Prepare image data

	$img = null;
	$replacements = array();

	for ($i = 0 ; $i < count($images) ; $i++)
	{
		$img = $images[$i];

		$replacements[] = new SMKeyValueCollection();
		$replacements[$i]["ImagePath"] = str_replace(" ", "%20", $galleryPath) . "/" . str_replace(" ", "%20", $img);
		$replacements[$i]["ImageTitle"] = "";
		$replacements[$i]["ImageDescription"] = "";

		if ($displayImageTitle === true)
			$replacements[$i]["ImageTitle"] = substr($img, 0, strrpos($img, "."));
		if ($displayImageExif === true)
			$replacements[$i]["ImageDescription"] = smImageMontageGetExifData($galleryPath . "/" . $img);
	}

	// Insert image data into HTML template

	$montageTemplate->ReplaceTagsRepeated("Images", $replacements);
}

// Output drop down menu for selecting a gallery, as well as the HTML template now containing all the images and code for the gallery

if ($displayTitle === true && $galleryPath !== null)
	echo "<h1>" . substr($galleryPath, strrpos($galleryPath, "/") + 1) . "</h1>";

if ($galleryPath !== null)
	$cfg["Settings"]["PageTitle"] = substr($galleryPath, strrpos($galleryPath, "/") + 1);
else
	$cfg["Settings"]["PageTitle"] = $language->GetTranslation("Title");

if ($menu !== null)
	echo $language->GetTranslation("SelectGallery") . ": " . $menu->Render() . "<br><br>";

echo $montageTemplate->GetContent();

// ===========================================================================================

}

?>
