<?php

/// <container name="base/SMTemplate">
/// 	Class represents a design template - an HTML file containing place holders and
/// 	repeating block, which can be easily replaced with actual data.
///
/// 	The design template currently loaded by the web application is
/// 	accessible to extensions through $this->context->GetTemplate().
/// 	See base/SMExtension for more information.
///
/// 	// Replace place holder {[VisitorsCount]} defined in design template
/// 	$tpl->ReplaceTag(new SMKeyValue(&quot;VisitorsCount&quot;, &quot;Visits: 87&quot;));
///
/// 	// Prepare links for a simple menu (will replace place holders in repeating block)
///
/// 	$linkA = new SMKeyValueCollection();
/// 	$linkA[&quot;MyLinksTitle&quot;] = &quot;Google Search&quot;;
/// 	$linkA[&quot;MyLinksUrl&quot;] = &quot;htto://google.com&quot;;
///
/// 	$linkB = new SMKeyValueCollection();
/// 	$linkB[&quot;MyLinksTitle&quot;] = &quot;Yahoo Search&quot;;
/// 	$linkB[&quot;MyLinksUrl&quot;] = &quot;htto://yahoo.com&quot;;
///
/// 	// Replace repeating block defined in design template, with a dynamic number of links
/// 	// &lt;!-- REPEAT MyLinks --&gt;&lt;a href=&quot;{[MyLinksUrl]}&quot;&gt;{[MyLinksTitle]}&lt;/a&gt;&lt;!-- /REPEAT MyLinks --&gt;
/// 	$tpl->ReplaceTagsRepeated(&quot;MyLinks&quot;, array($linkA, $linkB));
///
/// 	It is recommended to prefix place holders and repeating blocks with the
/// 	name of the extension responsible for replacing them. Place holders and
/// 	repeating blocks that have not been replaced by an extension, will be
/// 	automatically removed after the PreOutput stage of the life cycle.
/// 	See base/SMExtension for more information.
///
/// 	Design template may contain file includes, to include content of other
/// 	files. These are defined like so: &lt;!-- FileInclude ../Default/MyFile.html --&gt;
/// 	Path specified must be relative to design template in which the file include
/// 	is specified.
/// </container>
class SMTemplate
{
	private $content;
	private $resources;
	private $closed;

	/// <function container="base/SMTemplate" name="__construct" access="public">
	/// 	<description> Create instance of SMTemplate </description>
	/// 	<param name="templateFile" type="string" default="null"> Optional path to template file </param>
	/// </function>
	public function __construct($templateFile = null)
	{
		SMTypeCheck::CheckObject(__METHOD__, "templateFile", (($templateFile !== null) ? $templateFile : ""), SMTypeCheckType::$String);

		$this->content = "";
		$this->resources = array();
		$this->closed = false;

		if ($templateFile !== null)
			$this->LoadFile($templateFile);
	}

	/// <function container="base/SMTemplate" name="LoadHtml" access="public">
	/// 	<description> Load template from string </description>
	/// 	<param name="html" type="string"> HTML template string </param>
	/// </function>
	public function LoadHtml($html)
	{
		SMTypeCheck::CheckObject(__METHOD__, "html", $html, SMTypeCheckType::$String);

		$this->content = $html;
		$this->closed = false;
	}

	/// <function container="base/SMTemplate" name="LoadFile" access="public">
	/// 	<description> Load template from file </description>
	/// 	<param name="templateFile" type="string"> Path to template file </param>
	/// </function>
	public function LoadFile($templateFile)
	{
		SMTypeCheck::CheckObject(__METHOD__, "templateFile", $templateFile, SMTypeCheckType::$String);

		if (file_exists($templateFile) === false)
			throw new Exception("Specified template file (" . $templateFile . ") does not exist");

		$fileReader = new SMTextFileReader($templateFile);
		$this->content = $fileReader->ReadAll();
		$this->closed = false;

		$basePath = substr($templateFile, 0, strrpos($templateFile, "/")); // Strip file from path
		$this->loadHtmlIncludes($basePath);
	}

	/// <function container="base/SMTemplate" name="ReplaceTag" access="public">
	/// 	<description> Alias for ReplacePlaceholder(..) </description>
	/// 	<param name="kv" type="SMKeyValue">
	/// 		See ReplacePlaceholder(..) for description.
	/// 	</param>
	/// </function>
	public function ReplaceTag(SMKeyValue $kv)
	{
		$this->ReplacePlaceholder($kv);
	}

	/// <function container="base/SMTemplate" name="ReplacePlaceholder" access="public">
	/// 	<description> Replace place holder </description>
	/// 	<param name="kv" type="SMKeyValue">
	/// 		Instance of SMKeyValue with represents place holder to replace.
	/// 		Key represents name of place holder to replace, while value
	/// 		represents the replacement value.
	/// 	</param>
	/// </function>
	public function ReplacePlaceholder(SMKeyValue $kv)
	{
		if ($this->closed === true)
			return;

		$this->content = str_replace("{[" . $kv->GetKey() . "]}", $kv->GetValue(), $this->content);
	}

	/// <function container="base/SMTemplate" name="ReplaceTagsRepeated" access="public">
	/// 	<description> Alias for ReplaceRepeatingBlock(..) </description>
	/// 	<param name="block" type="string"> See ReplaceRepeatingBlock(..) for description </param>
	/// 	<param name="kvcs" type="SMKeyValueCollection[]"> See ReplaceRepeatingBlock(..) for description </param>
	/// </function>
	public function ReplaceTagsRepeated($block, $kvcs)
	{
		SMTypeCheck::CheckObject(__METHOD__, "block", $block, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "kvcs", $kvcs, "SMKeyValueCollection");

		$this->ReplaceRepeatingBlock($block, $kvcs);
	}

	/// <function container="base/SMTemplate" name="ReplaceRepeatingBlock" access="public">
	/// 	<description> Replace repeating block and contained place holders </description>
	/// 	<param name="block" type="string"> Name of repeating block to replace </param>
	/// 	<param name="kvcs" type="SMKeyValueCollection[]">
	/// 		Array of instances of SMKeyValueCollection - each instance
	/// 		represents data for place holders within a repeating block.
	/// 	</param>
	/// </function>
	public function ReplaceRepeatingBlock($block, $kvcs)
	{
		SMTypeCheck::CheckObject(__METHOD__, "block", $block, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "kvcs", $kvcs, "SMKeyValueCollection");

		if ($this->closed === true)
			return;

		$blockStart = "<!-- REPEAT " . $block . " -->";
		$blockEnd = "<!-- /REPEAT " . $block . " -->";

		$blockContentOrg = "";
		$blockContentMod = "";
		$blockContentCopy = "";

		while (true)
		{
			$blockContentOrg = $this->GetTagsContent($blockStart, $blockEnd);
			$blockContentMod = "";
			$blockContentCopy = "";

			if ($blockContentOrg === null)
				break;

			$kvc = null;

			for ($i = 0 ; $i < count($kvcs) ; $i++)
			{
				$kvc = $kvcs[$i];

				$blockContentCopy = $blockContentOrg;

				foreach ($kvc as $key => $value)
					$blockContentCopy = str_replace("{[" . $key . "]}", $value, $blockContentCopy);

				$blockContentMod = $blockContentMod . $blockContentCopy;
			}

			$this->content = str_replace($blockStart . $blockContentOrg . $blockEnd, $blockContentMod, $this->content);
		}
	}

	/// <function container="base/SMTemplate" name="SetBodyContent" access="public">
	/// 	<description> Set &lt;body&gt; content </description>
	/// 	<param name="content" type="string"> New content for the body section </param>
	/// </function>
	public function SetBodyContent($content)
	{
		SMTypeCheck::CheckObject(__METHOD__, "content", $content, SMTypeCheckType::$String);

		if ($this->closed === true)
			return;

		if (strpos($this->content, "<body") === false)
			return;

		$contentStart = strpos($this->content, "<body");
		$contentStart = strpos($this->content, ">", $contentStart) + 1;
		$contentEnd = strpos($this->content, "</body>");

		$newContent = "";
		$newContent .= substr($this->content, 0, $contentStart);
		$newContent .= $content;
		$newContent .= substr($this->content, $contentEnd);

		$this->content = $newContent;
	}

	/// <function container="base/SMTemplate" name="GetBodyContent" access="public" returns="string">
	/// 	<description> Returns &lt;body&gt; content if tag is defined, otherwise null </description>
	/// </function>
	public function GetBodyContent()
	{
		if (strpos($this->content, "<body") === false)
			return null;

		$bodyContentStart = strpos($this->content, "<body");
		$bodyContentStart = strpos($this->content, ">", $bodyContentStart) + 1;
		$bodyContentEnd = strpos($this->content, "</body>");
		$bodyContent = substr($this->content, $bodyContentStart, $bodyContentEnd - $bodyContentStart);

		return $bodyContent;
	}

	/// <function container="base/SMTemplate" name="AddBodyClass" access="public">
	/// 	<description> Add CSS class(es) to class attribute on body element  </description>
	/// 	<param name="cls" type="string"> Name of CSS class to add - multiple CSS classes may be specified separated by space </param>
	/// </function>
	public function AddBodyClass($cls)
	{
		SMTypeCheck::CheckObject(__METHOD__, "cls", $cls, SMTypeCheckType::$String);
		$this->addClass("body", $cls);
	}

	/// <function container="base/SMTemplate" name="AddHtmlClass" access="public">
	/// 	<description> Add CSS class(es) to class attribute on html element </description>
	/// 	<param name="cls" type="string"> Name of CSS class to add - multiple CSS classes may be specified separated by space </param>
	/// </function>
	public function AddHtmlClass($cls)
	{
		SMTypeCheck::CheckObject(__METHOD__, "cls", $cls, SMTypeCheckType::$String);
		$this->addClass("html", $cls);
	}

	private function addClass($tag, $cls)
	{
		SMTypeCheck::CheckObject(__METHOD__, "tag", $tag, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "cls", $cls, SMTypeCheckType::$String);

		// Get <tag ..> element

		$elmStart = strpos($this->content, "<" . $tag);
		if ($elmStart === false) return;
		$elmEnd = strpos($this->content, ">", $elmStart);
		if ($elmEnd === false) return;
		$elm = substr($this->content, $elmStart, $elmEnd - $elmStart);

		// Get position of class attribute in <tag ..> element

		$classValuePos = strpos($elm, "class");

		if ($classValuePos === false) // Class attribute not found, add it
		{
			$this->content = str_replace($elm, str_replace("<" . $tag, "<" . $tag . " class=\"" . $cls . "\"", $elm), $this->content);
		}
		else // Class attribute found, update it
		{
			$classValuePos = $classValuePos + 7; // Skip position to actual class value rather than start position of class attribute: class="value"

			// Figure out whether quotes or pings are surrounding class attribute value
			$quoteType = substr($elm, $classValuePos - 1, 1);
			if ($quoteType !== "\"" && $quoteType !== "'") return;

			// Find position of quote/ping terminating class attribute value
			$quoteEndPos = strpos($elm, $quoteType, $classValuePos);
			if ($quoteEndPos === false) return;

			// Construct new tag with updated class attribute

			$elmStart = substr($elm, 0, $classValuePos);
			$classValue = substr($elm, $classValuePos, $quoteEndPos - $classValuePos);
			$elmEnd = substr($elm, $quoteEndPos);
			$newElmTag = $elmStart . $classValue . (($classValue !== "") ? " " : "") . $cls . $elmEnd;

			// Replace old tag with new tag

			$this->content = str_replace($elm, $newElmTag, $this->content);
		}
	}

	/// <function container="base/SMTemplate" name="SetContent" access="public">
	/// 	<description> Set template content (replaces entire content) </description>
	/// 	<param name="content" type="string"> New template content </param>
	/// </function>
	public function SetContent($content)
	{
		SMTypeCheck::CheckObject(__METHOD__, "content", $content, SMTypeCheckType::$String);

		if ($this->closed === true)
			return;

		$this->content = $content;
	}

	/// <function container="base/SMTemplate" name="GetContent" access="public" returns="string">
	/// 	<description> Get template content (everything) </description>
	/// </function>
	public function GetContent()
	{
		return $this->content;
	}

	/// <function container="base/SMTemplate" name="AddToHeadSection" access="public">
	/// 	<description> Add content to &lt;head&gt; section </description>
	/// 	<param name="content" type="string"> Content to add </param>
	/// 	<param name="prepend" type="boolean" default="false"> True to prepend, False to append </param>
	/// </function>
	public function AddToHeadSection($content, $prepend = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "content", $content, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "prepend", $prepend, SMTypeCheckType::$Boolean);

		if ($this->closed === true)
			return;

		if (strpos($this->content, "<head") === false)
			return;

		if ($prepend === false)
		{
			$this->content = str_replace("</head>", $content . "</head>", $this->content);
		}
		else
		{
			$head = strpos($this->content, "<head");
			$head = strpos($this->content, ">", $head) + 1;

			$document = "";
			$document .= substr($this->content, 0, $head);
			$document .= $content;
			$document .= substr($this->content, $head);

			$this->content = $document;
		}
	}

	/// <function container="base/SMTemplate" name="RegisterResource" access="public">
	/// 	<description> Register resource to &lt;head&gt; section </description>
	/// 	<param name="type" type="SMTemplateResource"> Resource type </param>
	/// 	<param name="path" type="string"> Path to resource </param>
	/// 	<param name="prepend" type="boolean" default="false"> Set True to prepend resource, False to append </param>
	/// </function>
	public function RegisterResource($type, $path, $prepend = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "prepend", $prepend, SMTypeCheckType::$Boolean);

		if (property_exists("SMTemplateResource", $type) === false)
			throw new Exception("Invalid template resource '" . $type . "' specified - use SMTemplateResource::Type");

		if (isset($this->resources[$type . ":" . $path]) === true)
			return;

		if ($type === SMTemplateResource::$JavaScript)
			$this->AddToHeadSection((($prepend === true) ? "\n" : "") . "\t<script type=\"text/javascript\" src=\"" . $path . "\"></script>" . (($prepend === false) ? "\n" : ""), $prepend);
		else // StyleSheet
			$this->AddToHeadSection((($prepend === true) ? "\n" : "") . "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $path . "\">" . (($prepend === false) ? "\n" : ""), $prepend);

		$this->resources[$type . ":" . $path] = "true";
	}

	/// <function container="base/SMTemplate" name="RemovePlaceholders" access="public">
	/// 	<description> Remove remaining place holders </description>
	/// </function>
	public function RemovePlaceholders()
	{
		if ($this->closed === true)
			return;

		$content = "";

		while (true)
		{
			$content = $this->GetTagsContent("{[", "]}");

			if ($content === null)
				break;

			$this->content = str_replace("{[" . $content . "]}", "", $this->content);
		}
	}

	/// <function container="base/SMTemplate" name="RemoveRepeatingBlocks" access="public">
	/// 	<description> Remove remaining repeating blocks and their contained place holders </description>
	/// </function>
	public function RemoveRepeatingBlocks()
	{
		if ($this->closed === true)
			return;

		$content = "";
		$startTag = "";
		$endTag = "";

		$startPos = -1;
		$endPos = -1;

		while (true)
		{
			$content = $this->GetTagsContent("<!-- REPEAT ", " -->");

			if ($content === null)
				break;

			$startTag = "<!-- REPEAT " . $content . " -->";
			$endTag = "<!-- /REPEAT " . $content . " -->";

			$startPos = strpos($this->content, $startTag);
			if ($startPos === false) break;
			$startPos = $startPos + strlen($startTag);

			$endPos = strpos($this->content, $endTag);
			if ($endPos === false) break;

			$content = substr($this->content, $startPos, $endPos - $startPos);
			$this->content = str_replace($startTag . $content . $endTag, "", $this->content);
		}
	}

	/// <function container="base/SMTemplate" name="Close" access="public">
	/// 	<description> Close template - further changes to content is ignored </description>
	/// </function>
	public function Close()
	{
		$this->closed = true;
	}

	/// <function container="base/SMTemplate" name="IsClosed" access="public" returns="boolean">
	/// 	<description> Returns True if template has been closed, otherwise False </description>
	/// </function>
	public function IsClosed()
	{
		return $this->closed;
	}

	/// <function container="base/SMTemplate" name="GetTagsContent" access="public" returns="string">
	/// 	<description>
	/// 		Get content between two tags or values.
	/// 		First occurence found is returned. Returns Null if not found.
	/// 	</description>
	/// 	<param name="tagStart" type="string"> Start tag </param>
	/// 	<param name="endTag" type="string"> End tag </param>
	/// </function>
	public function GetTagsContent($tagStart, $tagEnd)
	{
		SMTypeCheck::CheckObject(__METHOD__, "tagStart", $tagStart, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "tagEnd", $tagEnd, SMTypeCheckType::$String);

		$startPos = strpos($this->content, $tagStart);
		if ($startPos === false) return null;
		$startPos = $startPos + strlen($tagStart);

		$endPos = strpos($this->content, $tagEnd, $startPos);
		if ($endPos === false) return null;

		return substr($this->content, $startPos, $endPos - $startPos);
	}

	private function loadHtmlIncludes($basePath)
	{
		// Handle file includes such as <!-- FileInclude ../Default/MyFile.html -->

		$startTag = "<!-- FileInclude ";
		$endTag = " -->";

		$fileRef = null;
		$fileReader = null;
		$content = null;

		while (true)
		{
			$fileRef = $this->GetTagsContent($startTag, $endTag);

			if ($fileRef === null)
				break;

			$path = $basePath . "/" . $fileRef;

			if (SMEnvironment::IsSubSite() === true && SMFileSystem::FileExists($basePath . "/../.htaccess") === true) // .htaccess is found in sites/XYZ/templates folder
			{
				// Subsite is configured to allow templates in sites/XYZ/templates to include index.html and basic.html from the main site's _BaseGeneric template.
				// This is necessary in order to ensure backward compatibility, without requiring _BaseGeneric to be installed in every subsite. We only
				// want _BaseGeneric to be installed on the main site which makes upgrading easier.

				// Get name of templates folder (most likely just "templates", unless renamed in custom build).
				// In this case we (fairly) assume that the name of the templates folder is the same for both the main site and subsites.
				$templatesFolderName = SMEnvironment::GetTemplatesDirectory(); // templates (shared) or sites/XYZ/templates (not shared)
				$templatesFolderName = ((strpos($templatesFolderName, "/") !== false) ? substr($templatesFolderName, strrpos($templatesFolderName, "/") + 1) : $templatesFolderName);

				if (SMStringUtilities::EndsWith($path, "../_BaseGeneric/index.html") === true)
					$path = SMEnvironment::GetTemplatesDirectory() . "/../../../" . $templatesFolderName . "/_BaseGeneric/index.html";
				else if (SMStringUtilities::EndsWith($path, "../_BaseGeneric2/index.html") === true)
					$path = SMEnvironment::GetTemplatesDirectory() . "/../../../" . $templatesFolderName . "/_BaseGeneric2/index.html";
				else if (SMStringUtilities::EndsWith($path, "../_BaseGeneric/basic.html") === true)
					$path = SMEnvironment::GetTemplatesDirectory() . "/../../../" . $templatesFolderName . "/_BaseGeneric/basic.html";
				else if (SMStringUtilities::EndsWith($path, "../_BaseGeneric2/basic.html") === true)
					$path = SMEnvironment::GetTemplatesDirectory() . "/../../../" . $templatesFolderName . "/_BaseGeneric2/basic.html";
			}

			$fileReader = new SMTextFileReader($path);
			$content = $fileReader->ReadAll();

			$this->content = str_replace($startTag . $fileRef . $endTag, $content, $this->content);
		}
	}
}

/// <container name="base/SMTemplateInfo">
/// 	Class responsible for providing access to and information about
/// 	templates used within the web application.
/// </container>
class SMTemplateInfo
{
	/// <function container="base/SMTemplateInfo" name="GetPublicTemplate" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Returns name of template used for public access.
	/// 		Returns &quot;Default&quot; if not configured.
	/// 		Value is defined in config.xml.php in the TemplatePublic element.
	/// 	</description>
	/// </function>
	public static function GetPublicTemplate()
	{
		return self::getTemplate("TemplatePublic");
	}

	/// <function container="base/SMTemplateInfo" name="GetAdminTemplate" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Returns name of template used for administration access.
	/// 		Returns &quot;Default&quot; if not configured.
	/// 		Value is defined in config.xml.php in the TemplateAdmin element.
	/// 	</description>
	/// </function>
	public static function GetAdminTemplate()
	{
		return self::getTemplate("TemplateAdmin");
	}

	/// <function container="base/SMTemplateInfo" name="GetTemplates" access="public" static="true" returns="string[]">
	/// 	<description> Get names of all templates available in the templates folder on the server </description>
	/// </function>
	public static function GetTemplates()
	{
		$folders = SMFileSystem::GetFolders(dirname(__FILE__) . "/../" . SMEnvironment::GetTemplatesDirectory());
		$templates = array();

		foreach ($folders as $folder)
		{
			if (strpos($folder, "_") !== 0 && SMFileSystem::FolderIsReadable(dirname(__FILE__) . "/../" . SMEnvironment::GetTemplatesDirectory() . "/" . $folder) === true)
				$templates[] = $folder;
		}

		return $templates;
	}

	/// <function container="base/SMTemplateInfo" name="TemplateExists" access="public" static="true" returns="boolean">
	/// 	<description> Check whether template exists - returns True if found, otherwise False </description>
	/// 	<param name="template" type="string"> Name of template </param>
	/// </function>
	public static function TemplateExists($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);
		return (in_array($template, self::GetTemplates(), true) === true);
	}

	/// <function container="base/SMTemplateInfo" name="GetCurrentTemplate" access="public" static="true" returns="string">
	/// 	<description> Get name of template currently loaded </description>
	/// </function>
	public static function GetCurrentTemplate()
	{
		$override = self::getTemplateOverride();

		if ($override !== null)
			return $override;

		return ((SMAuthentication::Authorized() === false) ? self::GetPublicTemplate() : self::GetAdminTemplate());
	}

	/// <function container="base/SMTemplateInfo" name="OverrideTemplate" access="public" static="true">
	/// 	<description>
	/// 		Override template for current page load. This must take place before InitComplete to work.
	///			Overriding template by code will even override template overriding using the URL parameter SMTpl.
	/// 	</description>
	/// 	<param name="template" type="string"> Name of template to use </param>
	/// </function>
	public static function OverrideTemplate($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);
		self::$overrideByCode = $template;
	}
	private static $overrideByCode = null;

	/// <function container="base/SMTemplateInfo" name="GetTemplateOverridden" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if template has been overridden (temporarily changed for current session), False otherwise </description>
	/// </function>
	public static function GetTemplateOverridden()
	{
		return (self::getTemplateOverride() !== null);
	}

	/// <function container="base/SMTemplateInfo" name="ClearTemplateOverrides" access="public" static="true">
	/// 	<description> Clear any template overriding currently in effect </description>
	/// </function>
	public static function ClearTemplateOverrides()
	{
		self::$overrideByCode = null;
		SMEnvironment::DestroySession("SMTplPublic");
		SMEnvironment::DestroySession("SMTplAdmin");
	}

	/// <function container="base/SMTemplateInfo" name="GetTemplateHtmlFile" access="public" static="true" returns="string">
	/// 	<description> Get file reference to index.html file for specified template </description>
	/// 	<param name="template" type="string"> Name of template </param>
	/// </function>
	public static function GetTemplateHtmlFile($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);
		return self::getFile($template, "index.html");
	}

	/// <function container="base/SMTemplateInfo" name="GetBasicHtmlFile" access="public" static="true" returns="string">
	/// 	<description> Get file reference to basic.html file for specified template </description>
	/// 	<param name="template" type="string"> Name of template </param>
	/// </function>
	public static function GetBasicHtmlFile($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);
		return self::getFile($template, "basic.html");
	}

	/// <function container="base/SMTemplateInfo" name="GetTemplateCssFile" access="public" static="true" returns="string">
	/// 	<description> Get file reference to style.css (preferred) or index.css file for specified template - returns Null if not defined </description>
	/// 	<param name="template" type="string"> Name of template </param>
	/// </function>
	public static function GetTemplateCssFile($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);

		// Sitemagic 2015 prefers a common style.css file for both basic.html and index.html
		$style = self::getFile($template, "style.css");

		if ($style !== null)
			return $style;

		return self::getFile($template, "index.css");
	}

	/// <function container="base/SMTemplateInfo" name="GetBasicCssFile" access="public" static="true" returns="string">
	/// 	<description> Get file reference to style.css (preferred) or basic.css file for specified template - returns Null if not defined </description>
	/// 	<param name="template" type="string"> Name of template </param>
	/// </function>
	public static function GetBasicCssFile($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);

		// Sitemagic 2015 prefers a common style.css file for both basic.html and index.html
		$style = self::getFile($template, "style.css");

		if ($style !== null)
			return $style;

		return self::getFile($template, "basic.css");
	}

	/// <function container="base/SMTemplateInfo" name="GetOverrideCssFile" access="public" static="true" returns="string">
	/// 	<description> Get file reference to override.css file for specified template - returns Null if not defined </description>
	/// 	<param name="template" type="string"> Name of template </param>
	/// </function>
	public static function GetOverrideCssFile($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);
		return self::getFile($template, "override.css");
	}

	/// <function container="base/SMTemplateInfo" name="GetTemplateEnhancementFiles" access="public" static="true" returns="string[]">
	/// 	<description>
	/// 		Get file references to all CSS and JS files located in enhancements folder for specified
	/// 		template and template type. Paths returned are relative to template directory.
	/// 		Enhancement files ending with basic.js is returned only for templates of type SMTemplateType::$Basic,
	/// 		while files ending with normal.js is returned only for templates of type SMTemplateType::$Normal.
	/// 		Files ending with just .js is returned for both template types.
	/// 		Example usage:
	/// 		$files = SMTemplateInfo::GetTemplateEnhancementFiles(SMTemplateInfo::GetCurrentTemplate(), SMTemplateType::$Normal);
	/// 		foreach ($files as $file) $this-&gt;register(SMEnvironment::GetTemplatesDirectory() . &quot;/&quot; . SMTemplateInfo::GetCurrentTemplate() . &quot;/&quot; . $file);
	/// 	</description>
	/// 	<param name="template" type="string"> Name of template </param>
	/// 	<param name="templateType" type="SMTemplateType"> Type of template </param>
	/// </function>
	public static function GetTemplateEnhancementFiles($template, $templateType)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "templateType", $templateType, SMTypeCheckType::$String);

		if (property_exists("SMTemplateType", $templateType) === false)
			throw new Exception("Invalid template type '" . $templateType . "' specified - use SMTemplateType::Type");

		$folder = dirname(__FILE__) . "/../" . SMEnvironment::GetTemplatesDirectory() . "/" . $template . "/enhancements";
		$exclude = strtolower((($templateType === SMTemplateType::$Basic) ? SMTemplateType::$Normal : SMTemplateType::$Basic)) . ".js";

		if (SMFileSystem::FolderExists($folder) === true)
		{
			$files = SMFileSystem::GetFiles($folder);
			$enhancements = array();

			for ($i = 0 ; $i < count($files) ; $i++)
			{
				if (SMStringUtilities::EndsWith(strtolower($files[$i]), $exclude) === false)
					$enhancements[] = "enhancements/" . $files[$i];
			}

			return $enhancements;
		}

		return array();
	}

	private static function getFile($template, $file)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "file", $file, SMTypeCheckType::$String);

		if (SMFileSystem::FileExists(dirname(__FILE__) . "/../" . SMEnvironment::GetTemplatesDirectory() . "/" . $template . "/" . $file) === false)
			return null;

		return SMEnvironment::GetTemplatesDirectory() . "/" . $template . "/" . $file;
	}

	private static function getTemplate($template)
	{
		SMTypeCheck::CheckObject(__METHOD__, "template", $template, SMTypeCheckType::$String);

		$cfg = SMEnvironment::GetConfiguration();
		$tpl = $cfg->GetEntry($template);
		return (($tpl !== null && $tpl !== "") ? $tpl : "Default");
	}

	private static $templateOverridingHandled = false;
	private static function getTemplateOverride()
	{
		if (self::$templateOverridingHandled === false)
		{
			$smTpl = SMEnvironment::GetQueryValue("SMTpl", SMValueRestriction::$AlphaNumeric, array(".", "-", "_"));
			$smTplPub = SMEnvironment::GetQueryValue("SMTplPublic", SMValueRestriction::$AlphaNumeric, array(".", "-", "_"));
			$smTplAdm = SMEnvironment::GetQueryValue("SMTplAdmin", SMValueRestriction::$AlphaNumeric, array(".", "-", "_"));

			if (SMAuthentication::Authorized() === false && ($smTpl !== null || $smTplPub !== null || $smTplAdm !== null))
			{
				$cfg = SMEnvironment::GetConfiguration();
				$allowed = $cfg->GetEntry("AllowTemplateOverriding");

				if ($allowed === null || strtolower($allowed) !== "true")
					throw new Exception("Template overriding using URL is not allowed");
			}

			if ($smTpl !== null)
			{
				SMEnvironment::SetSession("SMTplPublic", $smTpl);
				SMEnvironment::SetSession("SMTplAdmin", $smTpl);
			}

			if ($smTplPub !== null)
				SMEnvironment::SetSession("SMTplPublic", $smTplPub);

			if ($smTplAdm !== null)
				SMEnvironment::SetSession("SMTplAdmin", $smTplAdm);

			self::$templateOverridingHandled = true;
		}

		if (self::$overrideByCode !== null)
			return self::$overrideByCode;

		return SMEnvironment::GetSessionValue(((SMAuthentication::Authorized() === false) ? "SMTplPublic" : "SMTplAdmin"));
	}
}

/// <container name="base/SMTemplateType">
/// 	Enum representing template type
/// </container>
class SMTemplateType
{
	/// <member container="base/SMTemplateType" name="Basic" access="public" static="true" type="string" default="Basic">
	/// 	<description> Template type mainly used with pop up windows </description>
	/// </member>
	public static $Basic = "Basic";

	/// <member container="base/SMTemplateType" name="Normal" access="public" static="true" type="string" default="Normal">
	/// 	<description> Template type most commonly used - usually includes navigation </description>
	/// </member>
	public static $Normal = "Normal";
}

/// <container name="base/SMTemplateResource">
/// 	Enum representing template resource (JavaScript or StyleSheet)
/// </container>
class SMTemplateResource
{
	/// <member container="base/SMTemplateResource" name="StyleSheet" access="public" static="true" type="string" default="StyleSheet">
	/// 	<description> Cascading StyleSheet resource </description>
	/// </member>
	public static $StyleSheet = "StyleSheet";

	/// <member container="base/SMTemplateResource" name="JavaScript" access="public" static="true" type="string" default="JavaScript">
	/// 	<description> JavaScript resource </description>
	/// </member>
	public static $JavaScript = "JavaScript";
}

?>
