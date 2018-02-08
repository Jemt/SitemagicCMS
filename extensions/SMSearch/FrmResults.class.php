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
			// Notice that htmlspecialchars(..) replaces & with &amp; which breaks encoded characters (e.g. Euro Symbol => &#8364;) - restored below (&amp; => &).

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
		$pages = SMPagesLoader::GetPages(true);

		$results = "";
		$title = "";
		$content = "";
		$titleMatches = array();
		$contentMatches = array();
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

			// NOTICE: The use of SMStringUtilities::Search(..) is not 100% perfect. The page editor (TinyMCE) seems to turn some ASCII compatible characters into numeric HEX
			// entities (e.g. ~ which becomes &#126;). These characters will not be returned as HEX entities from SMStringUtilities::Search(..) as only characters extending ASCII is
			// turned back into HEX entities (matches returned). Fortunately, these situations seems rare - rarely will someone search for such odd characters.
			// However, the result being that a match on one of these characters will not result in the match being highlighted.

			$titleMatches = SMStringUtilities::Search($title, $search, false);
			$contentMatches = SMStringUtilities::Search($content, $search, false);

			if (count($titleMatches) === 0 && count($contentMatches) === 0)
				continue;

			// Extract content 400 characters before and 400 characters after first matching word

			$cIdx = ((count($contentMatches) > 0) ? strpos($content, $contentMatches[0]) : false);
			$posStart = 0;
			$length = 400 + strlen($search) + 400;

			if ($cIdx !== false && $cIdx > 400) // $cIdx is False if match was found in page title only
				$posStart = $cIdx - 400;

			$content = trim(substr($content, $posStart, $length));

			// Fix broken HTML entities at beginning or end (e.g. &#10084; or &quot; may have been corrupted by substr(..) above)

			$content = preg_replace("/^#?\\w*\\d*;/", "", $content); // Removes any broken HTML/HEX entity at the beginning of the the string - https://regex101.com/r/cR4aF6/3
			$content = preg_replace("/&#?\\d*\\w*$/", "", $content); // Removes any broken HTML/HEX entity at the end of the the string - https://regex101.com/r/gU0lE1/3

			// Highlight matching words

			foreach ($titleMatches as $titleMatch)
				$title = str_replace($titleMatch, "<span class=\"SMSearchMatch\">" . $titleMatch . "</span>", $title);
			foreach ($contentMatches as $contentMatch)
				$content = str_replace($contentMatch, "<span class=\"SMSearchMatch\">" . $contentMatch . "</span>", $content);

			// Add "read more" link

			$content = $content . "... <a href=\"" . $page->GetUrl() . "\">" . $this->lang->GetTranslation("ReadMore") . "</a>";

			// Add to result output

			$content = (($cIdx !== false && $cIdx > 400) ? "..." : "") . $content; // Prefix with "..." if start of string was cut off

			$results .= (($results !== "") ? "<br><br>" : "");
			$results .= "<b>" . $title . "</b><br>";
			$results .= $content;
		}

		return $output . "<br><br>" . (($results !== "") ? $results : "<i>" . $this->lang->GetTranslation("NoMatches") . "</i>");
	}
}

?>
