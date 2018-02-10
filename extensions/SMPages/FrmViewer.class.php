<?php

class SMPagesFrmViewer implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $error;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMPages");
		$this->error = "";

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

	private function loadPage()
	{
		$page = self::GetCurrentPage();

		if ($page->GetPassword() !== "")
		{
			$txtPassword = new SMInput("SMPagesPassword", SMInputType::$Hidden);
			$accessOkay = (SMEnvironment::GetSessionValue("SMPagesAuth" . $page->GetId()) !== null);

			if ($accessOkay === false && $this->context->GetForm()->PostBack() === true && $txtPassword->GetValue() === $page->GetPassword())
			{
				SMEnvironment::SetSession("SMPagesAuth" . $page->GetId(), "true");
				$accessOkay = true;
			}

			if ($accessOkay === false)
			{
				$content = "
				<h1>" . $this->lang->GetTranslation("PasswordProtected") . "</h1>

				" . $this->lang->GetTranslation("Unauthorized") . "
				 - <a href=\"javascript: smPagesLogin()\">" . $this->lang->GetTranslation("PageLogin") . "</a>

				" . ((($this->context->GetForm()->PostBack() === true && $txtPassword->GetValue() !== "")) ? "<br><br><i>" . $this->lang->GetTranslation("IncorrectPassword") . "</i>" : "") . "

				" . $txtPassword->Render() . "
				<script type=\"text/javascript\">
				SMEventHandler.AddEventHandler(window, \"load\", smPagesLogin);

				function smPagesLogin()
				{
					SMMessageDialog.ShowPasswordDialog(smPagesLoginCallback);
				}

				function smPagesLoginCallback(password)
				{
					if (password === null || password === \"\")
						return;

					SMDom.SetAttribute(\"" . $txtPassword->GetClientId() . "\", \"value\", password);
					smFormPostBack();
				}
				</script>
				";

				$page = new SMPagesPage(SMRandom::CreateGuid(), "SMUnauthorized401");
				$page->SetTitle("401");
				$page->SetAccessible(true);
				$page->SetAllowIndexing(false);
				$page->SetContent($content);
			}
		}

		if ($page->GetAllowIndexing() === false)
			$this->context->GetTemplate()->AddToHeadSection("\t<meta name=\"robots\" content=\"noindex, nofollow\">\n");

		if ($page->GetAccessible() === true)
		{
			$this->context->GetTemplate()->AddToHeadSection("\t<meta name=\"robots\" content=\"noodp\">\n");
			$this->context->GetTemplate()->AddToHeadSection("\t<meta name=\"keywords\" content=\"" . $page->GetKeywords() . "\">\n");
			$this->context->GetTemplate()->AddToHeadSection("\t<meta name=\"description\" content=\"" .$page->GetDescription() . "\">\n");
			$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $page->GetTitle()));
		}
		else
		{
			$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", "403"));
		}

		// Register CSS classes on <html> element

		if (strpos($page->GetContent(), "<div class=\"SMPagesCard") !== false)
			$this->context->GetTemplate()->AddHtmlClass("SMPagesCardLayout");
		else
			$this->context->GetTemplate()->AddHtmlClass("SMPagesClassicLayout");

		$this->context->GetTemplate()->AddHtmlClass("SMPagesFilename" . str_replace("#", "", $page->GetFilename()));
		$this->context->GetTemplate()->AddHtmlClass("SMPagesPageId" . $page->GetId());

		return $page;
	}

	private function insertExtensions($pageContent, $pageId)
	{
		SMTypeCheck::CheckObject(__METHOD__, "pageContent", $pageContent, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "pageId", $pageId, SMTypeCheckType::$String);

		$errorBox = "<div style=\"border: 1px solid #808080\">{error}</div>";

		// Replace extension place holders with actual extensions

		$placeholder = "editor/plugins/smextensions/img/placeholder.gif";
		$offset = 0;

		$imageStartPos = -1;
		$imageEndPos = -1;
		$imgPlaceHolder = "";

		$altValueStartPos = -1;
		$altValueEndPos = -1;
		$altValue = "";
		$altValueData = null;

		$extensionContent = "";

		// Keep looping until no more place holders are found
		while (strpos($pageContent, $placeholder, $offset) !== false)
		{
			// Find start and end position of place holder

			$imageStartPos = strpos($pageContent, $placeholder, $offset); // Validated in while loop

			while ($imageStartPos > 0)
			{
				$imageStartPos--;

				if (substr($pageContent, $imageStartPos, 4) === "<img")
					break;
			}

			$imageEndPos = strpos($pageContent, ">", $imageStartPos);

			if ($imageEndPos === false) // Skip if no end tag is found
			{
				$extensionContent = str_replace("{error}", "Extension place holder was not properly terminated", $errorBox);
				$pageContent = str_replace($imgPlaceHolder, $extensionContent, $pageContent);
				$offset = $imageStartPos + 1;
				continue;
			}

			$imgPlaceHolder = substr($pageContent, $imageStartPos, ($imageEndPos + 1) - $imageStartPos);

			// Extract extension execution information from alt attribute

			$altValueStartPos = strpos($imgPlaceHolder, "alt=\"") + 5;

			if ($altValueStartPos === false) // Skip if image has no alt attribute with execution information
			{
				$extensionContent = str_replace("{error}", "Extension place holder contained no execution information", $errorBox);
				$pageContent = str_replace($imgPlaceHolder, $extensionContent, $pageContent);
				$offset = $imageStartPos + 1;
				continue;
			}

			$altValueEndPos = strpos($imgPlaceHolder, "\"", $altValueStartPos);

			if ($altValueEndPos === false) // Skip if alt attribute is not properly terminated
			{
				$extensionContent = str_replace("{error}", "Extension execution information could not be read - not properly terminated", $errorBox);
				$pageContent = str_replace($imgPlaceHolder, $extensionContent, $pageContent);
				$offset = $imageStartPos + 1;
				continue;
			}

			$altValue = substr($imgPlaceHolder, $altValueStartPos, $altValueEndPos - $altValueStartPos);

			$altValueData = explode("|", $altValue);

			if (count($altValueData) !== 5) // Skip if execution information does not contain: module, filename, class, arguments string, and instance ID
			{
				$extensionContent = str_replace("{error}", "Extension execution information invalid", $errorBox);
				$pageContent = str_replace($imgPlaceHolder, $extensionContent, $pageContent);
				$offset = $imageStartPos + 1;
				continue;
			}

			// Load and insert extension

			try
			{
				$extensionResult = $this->loadExtension($altValueData[0], $altValueData[1], $altValueData[2], $altValueData[3], $pageId, (int)$altValueData[4]);
				$extensionContent = "<div class=\"SMPagesExtension " . $altValueData[2] . "" . (($extensionResult[1] === true) ? " SMIntegrated" : "") . "\">" . $extensionResult[0] . "</div>";
			}
			catch (Exception $ex)
			{
				$extensionContent = str_replace("{error}", $ex->getMessage() . "<br><br>" . str_replace("\n", "<br>", str_replace("\r", "", $ex->getTraceAsString())), $errorBox);
			}

			$pageContent = str_replace($imgPlaceHolder, $extensionContent, $pageContent);

			$offset = $imageStartPos + 1;
		}

		return $pageContent;
	}

	private function loadExtension($extension, $file, $class, $arg, $pageId, $instanceId)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "file", $file, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "class", $class, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "arg", $arg, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "pageId", $pageId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);

		SMExtensionManager::Import($extension, $file, true, true); // Throws exception if extension file does not exist. Allows for disabled extensions to be executed.
		$extensionClass = $class;

		$ext = new $extensionClass($this->context, $pageId, $instanceId, $arg);

		$content = $ext->Render();

		if (is_string($content) === false)
			throw new Exception($extensionClass . "->Render() did not return a valid string");

		return array($content, $ext->GetIsIntegrated());
	}

	private function convertTableElements($html)
	{
		SMTypeCheck::CheckObject(__METHOD__, "html", $html, SMTypeCheckType::$String);

		$cfg = SMEnvironment::GetConfiguration();

		if ($cfg->GetEntry("SMPagesLegacyTables") !== null && strtolower($cfg->GetEntry("SMPagesLegacyTables")) === "true")
			return $html;

		$html = str_replace("<table class=\"", "<div class=\"SMPagesTable ", $html);
		$html = str_replace("<table", "<div class=\"SMPagesTable\" ", $html);
		$html = preg_replace("/(<div[0-9a-z \\-=\"\\']*) border=[\"\\']0[\"\\']([0-9a-z \\-=\"\\']*>)/miU", "$1$2", $html); // https://regex101.com/r/z5xBPF/1
		$html = str_replace("</table", "</div", $html);

		$html = str_replace("<thead", "<div class=\"SMPagesTableHeader\" ", $html);
		$html = str_replace("</thead", "</div", $html);

		$html = str_replace("<tbody", "<div class=\"SMPagesTableBody\" ", $html);
		$html = str_replace("</tbody", "</div", $html);

		$html = str_replace("<tfoot", "<div class=\"SMPagesTableFooter\" ", $html);
		$html = str_replace("</tfoot", "</div", $html);

		$html = str_replace("<tr class=\"", "<div class=\"SMPagesTableRow ", $html);
		$html = str_replace("<tr", "<div class=\"SMPagesTableRow\" ", $html);
		$html = str_replace("</tr", "</div", $html);

		$html = str_replace("<td class=\"", "<div class=\"SMPagesTableCell ", $html);
		$html = str_replace("<td", "<div class=\"SMPagesTableCell\" ", $html);
		$html = str_replace("</td", "</div", $html);

		/*$html = str_replace("<th", "<div class=\"SMPagesTableHeaderCell\" ", $html);
		$html = str_replace("</th", "</div", $html);*/

		return $html;
	}

	public function Render()
	{
		return $this->RenderPage($this->loadPage());
	}

	public function RenderPage(SMPagesPage $page)
	{
		if ($page->GetAccessible() === false)
			return $this->lang->GetTranslation("NotAccessible");

		$content = $page->GetContent();
		$content = $this->convertTableElements($content);
		$content = $this->insertExtensions($content, $page->GetId());

		if ($content === "")
			return "";

		// Paragraphs are only allowed to contain inline elements like text.
		// This is a problem when pages may contain advanced extensions containing
		// JavaScript, tables, objects and so on. Paragrahps are therefore replaced.
		// Known problem: <p> tag may already contain a class attribute.
		//                Temporary fix implemented which works if class attribute is first attribute.
		//                <p style="padding: 10px" class="MyClass"> will not work. Result will be:
		//                <div class="smPagesParagraph" style="padding: 10px" class="MyClass">
		$content = str_replace("<p class=\"", "<div class=\"smPagesParagraph ", $content); // Work around: keep existing class(es) if registered on paragraph (not done by TinyMCE) - will only work if class attribute comes first and uses quotes rather than pings
		$content = str_replace("<p ", "<div class=\"smPagesParagraph\" ", $content);
		$content = str_replace("<p>", "<div class=\"smPagesParagraph\">", $content);
		$content = str_replace("</p>", "</div>", $content);

		// Add CSS to div.smPagesParagraph and Cards
		$this->context->GetTemplate()->RegisterResource(SMTemplateResource::$StyleSheet, SMExtensionManager::GetExtensionPath("SMPages") . "/editor.css?ver=" . SMEnvironment::GetVersion(), true); // Prepend to allow overrides in template CSS

		// Clear float in case it was used on images or if Cards were used
		$content = $content . "<div class=\"smPagesClear\"></div>";

		// Replace tilde (~) in links with actual path to website (https://regex101.com/r/jV3cB6/4)
		$content = preg_replace_callback("/(<a.*?href=([\"']))~(\\S*?\\2.*?>)/", "smPagesPregReplaceTildeCallback", $content);
		$content = preg_replace_callback("/(<a.*?href=([\"']))&#126;(\\S*?\\2.*?>)/", "smPagesPregReplaceTildeCallback", $content); // SMCMS 4.4 has enabled numeric encoding for TinyMCE which for some reason encodes tilde even though it's an ASCII character

		return $content;
	}

	public static function GetCurrentPage()
	{
		$pageGuid = SMEnvironment::GetQueryValue("SMPagesId", SMValueRestriction::$Guid);
		$filename = SMEnvironment::GetQueryValue("SMPagesFilename", SMValueRestriction::$AlphaNumeric, array(".", "-", "_"));
		$page = null;

		if ($pageGuid !== null)
		{
			$page = SMPagesPage::GetPersistentByGuid($pageGuid);
		}
		else if ($filename !== null)
		{
			$page = SMPagesPage::GetPersistentByFilename($filename);
		}
		else if (SMEnvironment::GetQueryValue("SMPagesPageList") === null)
		{
			$page = SMPagesPage::GetPersistentByFilename("index");
			if ($page === null)
				$page = SMPagesPage::GetPersistentByFilename("default");
			if ($page === null)
				$page = SMPagesPage::GetPersistentByFilename("frontpage");
			if ($page === null)
				$page = SMPagesPage::GetPersistentByFilename("start");
		}

		if ($page === null)
		{
			$lang = new SMLanguageHandler("SMPages");
			$translation = (($pageGuid === null && $filename === null) ? "NoIndexPage" : "PageNotFound");

			$page = new SMPagesPage(SMRandom::CreateGuid(), "404");
			$page->SetTitle("404");
			$page->SetAccessible(true);
			$page->SetContent($lang->GetTranslation($translation));
		}

		return $page;
	}
}

function smPagesPregReplaceTildeCallback($matches) // 0 = full match, 1 = first portion (e.g. "<a href='"), 2 = quote type (" or '), 3 = last portion (e.g. "index.html title='Click me'>")
{
	$path = SMEnvironment::GetRequestPath();
	$path .= (($path !== "/") ? "/" : ""); // E.g. / or /sites/demo/

	return $matches[1] . $path . ((strpos($matches[3], "/") === 0) ? substr($matches[3], 1) : $matches[3]);
}

?>
