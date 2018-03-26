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

		// Adjust search value to make it compatible with encoding applied by TinyMCE - otherwise
		// some characters won't be searchable, and ampersand (&) will match HTML entities (e.g. &lt;)

		$searchTitle = $search;
		$searchContent = $search;
		$searchContent = str_replace("&", "&amp;", $searchContent); // TinyMCE encodes & into &amp; - unfortunately this fix breaks HEX entities representing unicode (e.g. &#12345; becomes &amp;#12345;) - fixed below
		$searchContent = preg_replace('/&amp;(#\d+;)/', "&$1", $searchContent); // Fix HEX entities broken on line above
		$searchContent = str_replace("<", "&lt;", $searchContent);
		$searchContent = str_replace(">", "&gt;", $searchContent);

		// Heading

		$heading = null;

		if ($search !== null && $search !== "")
		{
			// Notice that SMStringUtilities::HtmlEncode(..) replaces & with &amp; which breaks encoded characters (e.g. Euro Symbol => &#8364;) - restored below (&amp; => &).

			$heading = $this->lang->GetTranslation("SearchResults") . ": ";
			$heading .= preg_replace('/&amp;(#\d+;)/', "&$1", SMStringUtilities::HtmlEncode($search));
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
		$fallbackTitle = "";
		$content = "";
		$fallbackContent = "";
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
			$content = str_replace("&#160;", " ", $content); // Since SMCMS 4.4 numeric HTML entities have been used instead of e.g. &nbsp;
			$content = str_replace("&#126;", "~", $content); // Make sure we can highlight tilde which in SMStringUtilities::Search(..) becomes decoded but remains encoded in original value hence preventing highlighting further down - fixed here

			while (strpos($content, "  ") !== false)
				$content = str_replace("  ", " ", $content);

			// Search

			$titleMatches = SMStringUtilities::Search($title, $searchTitle, true);
			$contentMatches = SMStringUtilities::Search($content, $searchContent, true);

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

			$fallbackTitle = $title;

			foreach ($titleMatches as $titleMatch)
				$title = str_replace($titleMatch, "<span class=\"SMSearchMatch\">" . $titleMatch . "</span>", $title);

			$fallbackContent = $content;

			foreach ($contentMatches as $contentMatch)
				$content = str_replace($contentMatch, "<span class=\"SMSearchMatch\">" . $contentMatch . "</span>", $content);

			// If the page contains e.g. an ampersand (&), a hash (#), or a number (e.g 23490) in content or title, then those same values
			// may be present in HTML or HEX entities and therefore highlighted (replaced above) which will break the entities.
			// Example page		: About amplifiers &amp; other things
			// Example search	: amp
			// Example result	: About <span class="SMSearchMatch">amp</span>lifiers &<span class="SMSearchMatch">amp</span>; other things
			// Notice how the ampersand is broken because 'amp' was highlighted.
			// If a broken HEX entity is detected, then don't highlight anything for simplicity. Fortunately this will rarely happen.

			// https://regex101.com/r/DO3vwv/9/
			$re = '/(&)<span class="SMSearchMatch">([a-z]+;)<\/span>|(&)<span class="SMSearchMatch">([a-z]+)<\/span>([a-z]*;)|(&[a-z]+)<span class="SMSearchMatch">([a-z]*;)<\/span>|(&[a-z]+)<span class="SMSearchMatch">([a-z]*)<\/span>;|<span class="SMSearchMatch">(&)<\/span>([a-z]+;)|<span class="SMSearchMatch">(&[a-z]+)<\/span>([a-z]*;)|<span class="SMSearchMatch">(&#\d+)<\/span>(\d*;)|<span class="SMSearchMatch">(&#)<\/span>(\d+;)|<span class="SMSearchMatch">(&)<\/span>(#\d+;)|(&)<span class="SMSearchMatch">(#\d+;)<\/span>|(&)<span class="SMSearchMatch">(#\d+)<\/span>(\d*;)|(&)<span class="SMSearchMatch">(#)<\/span>(\d+;)|(&#)<span class="SMSearchMatch">(\d+;)<\/span>|(&#)<span class="SMSearchMatch">(\d+)<\/span>(\d*;)|(&#\d+)<span class="SMSearchMatch">(\d*;)<\/span>|(&#\d+)<span class="SMSearchMatch">(\d*)<\/span>(\d*;)/i';

			if (preg_match($re, $title) === 1)
			{
				$title = $fallbackTitle;
			}

			if (preg_match($re, $content) === 1)
			{
				$content = $fallbackContent;
			}

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
