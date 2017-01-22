<?php

require_once(dirname(__FILE__) . "/FrmSearch.class.php");

class SMSearchFrmResults implements SMIExtensionForm
{
	private $context;
	private $name;
	private $lang;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->name = $this->context->GetExtensionName();
		$this->lang = new SMLanguageHandler($this->name);

		$this->context->GetTemplate()->AddToHeadSection("\t<meta name=\"robots\" content=\"noindex, nofollow\">\n");

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
		}
	}

	public function Render()
	{
		if (SMExtensionManager::ExtensionEnabled("SMPages") === false)
			return "Unable to search pages - SMPages extension must be installed and enabled";

		$search = SMEnvironment::GetQueryValue($this->name . "Value");
		$output = "";

		// Heading

		$heading = null;

		if ($search !== null && $search !== "")
		{
			// Notice that htmlspecialchars(..) replaces & with &amp; which breaks
			// encoded characters (e.g. Euro Symbol => &#8364;) - restored below (&amp; => &).

			$heading = $this->lang->GetTranslation("SearchResults") . ": ";
			$heading .= str_replace("&amp;", "&", htmlspecialchars($search));
		}
		else
		{
			$heading = $this->lang->GetTranslation("Title");
		}

		$output .= "<h1>" . $heading . "</h1>";

		// Search form

		$frm = new SMSearchFrmSearch($this->context, 5555555, "");
		$output .= $frm->Render();

		if ($search === null || $search === "")
			return $output;

		// Perform search

		$searchOrg = $search;
		$search = strtolower($search);
		$pages = SMPagesLoader::GetPages(true);

		$results = "";
		$title = "";
		$content = "";
		$cIdx = -1;
		$posStart = -1;
		$length = -1;

		foreach ($pages as $page)
		{
			if ($page->GetAccessible() === false || $page->GetPassword() !== "")
				continue;

			// Get content

			$title = $page->GetTitle();
			$content = $page->GetContent();

			// Remove HTML

			$content = str_replace("<br>", " ", $content);
			$content = preg_replace("/<([^>]+)>/m", "", $content);

			// Get rid of linebreaks and double spaces

			$content = str_replace("\r", "", $content);
			$content = str_replace("\n", " ", $content); // Remove normal line breaks - required by RegEx used further down which expects one single line (no /m flag set)
			$content = str_replace(" ", " ", $content);  // NOTICE: Replacing non-breaking whitespaces (e.g. ALT+Space on Mac) with normal whitespaces
			$content = str_replace("&nbsp;", " ", $content);

			while (strpos($content, "  ") !== false)
				$content = str_replace("  ", " ", $content);

			// Search

			if (strpos(strtolower($title), $search) === false && strpos(strtolower($content), $search) === false)
				continue; // Skip, search value not found in title or content

			// Extract content 400 characters before and 400 characters after first matching word

			$cIdx = strpos(strtolower($content), $search);
			$posStart = 0;
			$length = 400 + strlen($search) + 400;

			if ($cIdx !== false && $cIdx > 400) // $cIdx is False if match was found in page title
				$posStart = $cIdx - 400;

			$content = trim(substr($content, $posStart, $length));
			$content = (($cIdx > 400) ? "..." : "") . $content;

			// Fix broken HTML entities at beginning or end (e.g. &#10084; or &quot; may have been corrupted by substr(..) above)

			$content = preg_replace("/^#?\\w*\\d*;/", "", $content); // Removes any broken HTML/HEX entity at the beginning of the the string - https://regex101.com/r/cR4aF6/3
			$content = preg_replace("/&#?\\d*\\w*$/", "", $content); // Removes any broken HTML/HEX entity at the end of the the string - https://regex101.com/r/gU0lE1/3

			// Highlight matching words

			$title = SMStringUtilities::Replace($title, $search, "<span class=\"SMSearchMatch\">" . $searchOrg . "</span>", false); // Case-insensitive replace
			$content = SMStringUtilities::Replace($content, $search, "<span class=\"SMSearchMatch\">" . $searchOrg . "</span>", false); // Case-insensitive replace

			// Add "read more" link

			$content = $content . "... <a href=\"" . $page->GetUrl() . "\">" . $this->lang->GetTranslation("ReadMore") . "</a>";

			// Add to result output

			$results .= (($results !== "") ? "<br><br>" : "");
			$results .= "<b>" . $title . "</b><br>";
			$results .= $content;
		}

		return $output . "<br><br>" . (($results !== "") ? $results : "<i>" . $this->lang->GetTranslation("NoMatches") . "</i>");
	}
}

?>
