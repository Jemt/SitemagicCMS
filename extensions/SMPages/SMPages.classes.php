<?php

/// <container name="SMPages">
/// 	SMPages is an extension running within Sitemagic CMS.
/// 	It provides functionality for handling content pages within Sitemagic CMS.
/// </container>

/// <container name="SMPages/SMPagesLinkList">
/// 	Class allow extensions to easily add links to the link
/// 	picker in the WYSIWYG editor.
///
/// 	$links = SMPagesLinkList::GetInstance();
///
/// 	if ($links-&gt;GetReadyState() === true)
/// 	{
/// 		&#160;&#160;&#160;&#160; $category = &quot;Search engines&quot;;
/// 		&#160;&#160;&#160;&#160; $links-&gt;AddLink($category, &quot;Google&quot;, &quot;http://google.com&quot;);
/// 		&#160;&#160;&#160;&#160; $links-&gt;AddLink($category, &quot;Bing&quot;, &quot;http://bing.com&quot;);
/// 		&#160;&#160;&#160;&#160; $links-&gt;AddLink($category, &quot;Altavista&quot;, &quot;http://av.com&quot;);
/// 	}
/// </container>
class SMPagesLinkList
{
	private static $instance = null;
	private $ready;
	private $links;

	private function __construct()
	{
		$this->ready = false;
		$this->links = array();
	}

	/// <function container="SMPages/SMPagesLinkList" name="GetInstance" access="public" static="true" returns="SMPagesLinkList">
	/// 	<description> Returns instance of SMPagesLinkList to which links may be added </description>
	/// </function>
	public static function GetInstance()
	{
		if (self::$instance === null)
			self::$instance = new SMPagesLinkList();

		return self::$instance;
	}

	/// <function container="SMPages/SMPagesLinkList" name="GetLinkCollection" access="public" returns="string[][]">
	/// 	<description>
	/// 		Returns internal link collection as a multi dimentional array.
	/// 		$arr[n][&quot;category&quot;]
	/// 		$arr[n][&quot;title&quot;]
	/// 		$arr[n][&quot;url&quot;]
	/// 	</description>
	/// </function>
	public function GetLinkCollection()
	{
		return $this->links;
	}

	/// <function container="SMPages/SMPagesLinkList" name="SetLinkCollection" access="public">
	/// 	<description> Set internal link collection as a multidimentional array </description>
	/// 	<param name="arr" type="string[][]">
	/// 		Multi dimentional array containing links.
	/// 		$arr[n][&quot;category&quot;] = &quot;Category&quot;;
	/// 		$arr[n][&quot;title&quot;] = &quot;My link&quot;;
	/// 		$arr[n][&quot;url&quot;] = &quot;http://mydomain.com&quot;;
	/// 	</param>
	/// </function>
	public function SetLinkCollection($arr)
	{
		SMTypeCheck::CheckArray(__METHOD__, "arr", $arr, SMTypeCheckType::$Array);
		$this->links = $arr;
	}

	/// <function container="SMPages/SMPagesLinkList" name="AddLink" access="public">
	/// 	<description> Add link </description>
	/// 	<param name="category" type="string"> Link category (link is gathered under this category) </param>
	/// 	<param name="title" type="string"> Link title </param>
	/// 	<param name="url" type="string"> Link reference (URL) </param>
	/// </function>
	public function AddLink($category, $title, $url)
	{
		SMTypeCheck::CheckObject(__METHOD__, "category", $category, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "url", $url, SMTypeCheckType::$String);

		$this->links[] = array(
			"category"	=> $category,
			"title"		=> $title,
			"url"		=> $url
		);
	}

	/// <function container="SMPages/SMPagesLinkList" name="GetReadyState" access="public" returns="boolean">
	/// 	<description> Returns True when link list is loaded and links should be added, False otherwise </description>
	/// </function>
	public function GetReadyState()
	{
		return $this->ready;
	}

	/// <function container="SMPages/SMPagesLinkList" name="SetReadyState" access="public">
	/// 	<description> Set value indicating whether link providers should add their links </description>
	/// 	<param name="value" type="string"> Set True to have link providers add links, False not to </param>
	/// </function>
	public function SetReadyState($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->ready = $value;
	}
}

/// <container name="SMPages/SMPagesExtensionList">
/// 	Class allow extensions to easily register their Content Page Extensions, which
/// 	will make them available to the Content Page Extension picker in the WYSIWYG editor.
///
/// 	$list = SMPagesExtensionList::GetInstance();
///
/// 	if ($list-&gt;GetReadyState() === false) return;
///
/// 	$category = &quot;Adwords plugin&quot;;
/// 	$ext = &quot;MyExtension&quot;;
/// 	$file = &quot;Adwords.class.php&quot;;
/// 	$class = $ext . &quot;AdWords&quot;;
/// 	$width = 150;
/// 	$height = 500;
///
/// 	// Register the same Adwords Content Page Extension with different titles
/// 	// (Computer ads, Smartphone ads, and Entertainment ads).
/// 	// Each time the extension is also registered with an optional argument
/// 	// (PCs, Phones, Fun) which is passed to the Content Page Extension.
/// 	// This enables the Content Page Extension to behave differently based on
/// 	// the value of the argument (in this case display different adds).
/// 	// See SMPagesExtension class for more information.
///
/// 	$list->AddExtension($category, &quot;Computer ads&quot;, $extension, $file, $class, &quot;PCs&quot;, $width, $height);
/// 	$list->AddExtension($category, &quot;Smartphone ads&quot;, $extension, $file, $class, &quot;Phones&quot;, $width, $height);
/// 	$list->AddExtension($category, &quot;Entertainment ads&quot;, $extension, $file, $class, &quot;Fun&quot;, $width, $height);
/// </container>
class SMPagesExtensionList
{
	private static $instance = null;
	private $ready;
	private $extensions;

	private function __construct()
	{
		$this->ready = false;
		$this->extensions = array();
	}

	/// <function container="SMPages/SMPagesExtensionList" name="GetInstance" access="public" static="true" returns="SMPagesExtensionList">
	/// 	<description> Returns instance ofSMPagesExtensionList to which Content Page Extensions may be registered </description>
	/// </function>
	public static function GetInstance()
	{
		if (self::$instance === null)
			self::$instance = new SMPagesExtensionList();

		return self::$instance;
	}

	/// <function container="SMPages/SMPagesExtensionList" name="GetExtensionCollection" access="public" returns="string[][]">
	/// 	<description>
	/// 		Returns internal extension collection as a multi dimentional array.
	/// 		$arr[n][&quot;category&quot;]
	/// 		$arr[n][&quot;title&quot;]
	/// 		$arr[n][&quot;extension&quot;]
	/// 		$arr[n][&quot;file&quot;]
	/// 		$arr[n][&quot;class&quot;]
	/// 		$arr[n][&quot;argument&quot;]
	/// 		$arr[n][&quot;width&quot;]
	/// 		$arr[n][&quot;height&quot;]
	/// 	</description>
	/// </function>
	public function GetExtensionCollection()
	{
		return $this->extensions;
	}

	/// <function container="SMPages/SMPagesExtensionList" name="SetExtensionCollection" access="public">
	/// 	<description> Set internal extension collection as a multidimentional array. </description>
	/// 	<param name="arr" type="string[][]">
	/// 		Multi dimentional array containing links.
	/// 		$arr[n][&quot;category&quot;] = &quot;Category&quot;;
	/// 		$arr[n][&quot;title&quot;] = &quot;My Content Page Extension&quot;;
	/// 		$arr[n][&quot;extension&quot;] = &quot;MyExtension&quot;;
	/// 		$arr[n][&quot;file&quot;] = &quot;PageExtension.class.php&quot;;
	/// 		$arr[n][&quot;class&quot;] = &quot;MyExtensionTest&quot;;
	/// 		$arr[n][&quot;argument&quot;] = &quot;&quot;;
	/// 		$arr[n][&quot;width&quot;] = &quot;200px&quot;;
	/// 		$arr[n][&quot;height&quot;] = &quot;100px&quot;;
	/// 	</param>
	/// </function>
	public function SetExtensionCollection($arr)
	{
		SMTypeCheck::CheckArray(__METHOD__, "arr", $arr, SMTypeCheckType::$Array);
		$this->extensions = $arr;
	}

	/// <function container="SMPages/SMPagesExtensionList" name="AddExtension" access="public">
	/// 	<description> Add (register) extension </description>
	/// 	<param name="category" type="string"> Content Page Extension category (Content Page Extensions are gathered under this category) </param>
	/// 	<param name="title" type="string"> Content Page Extension title </param>
	/// 	<param name="extension" type="string"> Name of extension containing Content Page Extension (folder name) </param>
	/// 	<param name="file" type="string"> Name of file containg class defining Content Page Extension </param>
	/// 	<param name="class" type="string"> Name of class defining Content Page Extension </param>
	/// 	<param name="argument" type="string" default="String.Empty"> Optional argument passed to Content Page Extension </param>
	/// 	<param name="pxWidth" type="integer" default="100"> Width of place holder (in pixels) shown within WYSIWYG editor </param>
	/// 	<param name="pxHeight" type="integer" default="100"> Height of place holder (in pixels) shown within WYSIWYG editor </param>
	/// 	<param name="useRelativeWidth" type="boolean" default="false"> Make width relative (percentage) </param>
	/// </function>
	public function AddExtension($category, $title, $extension, $file, $class, $argument = "", $pxWidth = 100, $pxHeight = 100, $useRelativeWidth = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "category", $category, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "file", $file, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "class", $class, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "argument", $argument, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "pxWidth", $pxWidth, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "pxHeight", $pxHeight, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "useRelativeWidth", $useRelativeWidth, SMTypeCheckType::$Boolean);

		SMExtensionManager::Import($extension, $file, true); // throws exception if extension file does not exist

		$reflectionClass = new ReflectionClass($class);
		$extends = false;

		while ($reflectionClass !== false)
		{
			$reflectionClass = $reflectionClass->getParentClass();

			if ($reflectionClass !== false && $reflectionClass->getName() === "SMPagesExtension")
			{
				$extends = true;
				break;
			}
		}

		if ($extends === false)
			throw new Exception("Content page extension '" . $extension . "' must be an instance of (extend) SMPagesExtension");

		$this->extensions[] = array(
			"category"	=> $category,
			"title"		=> $title,
			"extension"	=> $extension,
			"file"		=> $file,
			"class"		=> $class,
			"argument"	=> $argument,
			"width"		=> $pxWidth . (($useRelativeWidth === false) ? "px" : "%"),
			"height"	=> $pxHeight . "px"
		);
	}

	/// <function container="SMPages/SMPagesExtensionList" name="GetReadyState" access="public" returns="boolean">
	/// 	<description> Returns True when Content Page Extension list is loaded and Content Page Extensions should be added, False otherwise </description>
	/// </function>
	public function GetReadyState()
	{
		return $this->ready;
	}

	/// <function container="SMPages/SMPagesExtensionList" name="SetReadyState" access="public">
	/// 	<description> Set value indicating whether Content Page Extension providers should register their Content Page Extensions </description>
	/// 	<param name="value" type="boolean"> Set True to have Content Page Extension providers register their Content Page Extensions, False not to </param>
	/// </function>
	public function SetReadyState($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->ready = $value;
	}
}

/// <container name="SMPages/SMPagesPage">
/// 	Class represents a content page.
///
/// 	$page = new SMPagesPage(SMRandom::CreateGuid(), "AboutUs");
/// 	$page-&gt;SetTitle(&quot;About our company&quot;);
/// 	$page-&gt;SetContent(&quot;&lt;h1&gt;About us&lt;/h1&gt;&lt;p&gt;Hello, and welcome to our page&lt;/p&gt;&quot;);
/// 	$page-&gt;CommitPersistent();
/// </container>
class SMPagesPage
{
	private $guid;			// string
	private $filename;		// string
	private $title;			// string
	private $content;		// string
	private $accessible;	// bool

	private $template;		// string

	private $keywords;		// string
	private $description;	// string
	private $allowIndexing;	// bool

	private $password;		// string

	private $lastModified;	// int

	private static $urlFormat = -1;	// int (0 = SMPagesId=guid, 1 = guid.htm, 2 = SMPagesFilename=filename, 3 = filename.html)

	/// <function container="SMPages/SMPagesPage" name="__construct" access="public">
	/// 	<description> Create instance of SMPagesPage </description>
	/// 	<param name="guid" type="string"> Unique page ID (GUID) </param>
	/// 	<param name="filename" type="string"> Page filename </param>
	/// </function>
	public function __construct($guid, $filename, $lastModified = -1)
	{
		SMTypeCheck::CheckObject(__METHOD__, "guid", $guid, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "filename", $filename, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "lastModified", $lastModified, SMTypeCheckType::$Integer);

		$this->guid = $guid;
		$this->filename = $filename;
		$this->title = "";
		$this->content = "";
		$this->accessible = false;

		$this->template = "";

		$this->keywords = "";
		$this->description = "";
		$this->allowIndexing = false;

		$this->password = "";

		$this->lastModified = $lastModified;
	}

	/// <function container="SMPages/SMPagesPage" name="GetId" access="public" returns="string">
	/// 	<description> Get page ID (GUID) </description>
	/// </function>
	public function GetId()
	{
		return $this->guid;
	}

	/// <function container="SMPages/SMPagesPage" name="SetFilename" access="public">
	/// 	<description> Set page filename </description>
	/// 	<param name="value" type="string"> Page filename </param>
	/// </function>
	public function SetFilename($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->filename = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetFilename" access="public" returns="string">
	/// 	<description> Returns page filename </description>
	/// </function>
	public function GetFilename()
	{
		return $this->filename;
	}

	/// <function container="SMPages/SMPagesPage" name="SetTitle" access="public">
	/// 	<description> Set page title </description>
	/// 	<param name="value" type="string"> Page title </param>
	/// </function>
	public function SetTitle($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->title = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetTitle" access="public" returns="string">
	/// 	<description> Returns page title if set, otherwise empty string </description>
	/// </function>
	public function GetTitle()
	{
		return $this->title;
	}

	/// <function container="SMPages/SMPagesPage" name="SetContent" access="public">
	/// 	<description> Set page content </description>
	/// 	<param name="value" type="string"> Page content </param>
	/// </function>
	public function SetContent($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->content = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetContent" access="public" returns="string">
	/// 	<description> Returns page content if set, otherwise empty string </description>
	/// </function>
	public function GetContent()
	{
		return $this->content;
	}

	/// <function container="SMPages/SMPagesPage" name="SetAccessible" access="public">
	/// 	<description> Set value indicating whether page is accessible or not </description>
	/// 	<param name="value" type="boolean"> Set True to make page accessible, False not to </param>
	/// </function>
	public function SetAccessible($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->accessible = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetAccessible" access="public" returns="boolean">
	/// 	<description> Returns value indicating whether page is accessible or not </description>
	/// </function>
	public function GetAccessible()
	{
		return $this->accessible;
	}

	/// <function container="SMPages/SMPagesPage" name="SetTemplate" access="public">
	/// 	<description> Set name of alternative template to use when rendering page </description>
	/// 	<param name="value" type="string"> Name of template </param>
	/// </function>
	public function SetTemplate($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->template = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetTemplate" access="public" returns="string">
	/// 	<description> Returns name of alternative template used to render page, empty string if not defined </description>
	/// </function>
	public function GetTemplate()
	{
		return $this->template;
	}

	/// <function container="SMPages/SMPagesPage" name="SetKeywords" access="public">
	/// 	<description> Set comma separated list of page keywords (meta data) </description>
	/// 	<param name="value" type="string"> Page keywords </param>
	/// </function>
	public function SetKeywords($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->keywords = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetKeywords" access="public" returns="string">
	/// 	<description> Returns page keywords if set, otherwise empty string </description>
	/// </function>
	public function GetKeywords()
	{
		return $this->keywords;
	}

	/// <function container="SMPages/SMPagesPage" name="SetDescription" access="public">
	/// 	<description> Set page description (meta data) </description>
	/// 	<param name="value" type="string"> Page description </param>
	/// </function>
	public function SetDescription($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->description = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetDescription" access="public" returns="string">
	/// 	<description> Returns page description if set, otherwise empty string </description>
	/// </function>
	public function GetDescription()
	{
		return $this->description;
	}

	/// <function container="SMPages/SMPagesPage" name="SetAllowIndexing" access="public">
	/// 	<description> Set value indicating whether page may be indexed by search engines or not (not quaranteed to be honoured) </description>
	/// 	<param name="value" type="boolean"> True to welcome indexing, False to request page not to be indexed </param>
	/// </function>
	public function SetAllowIndexing($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->allowIndexing = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetAllowIndexing" access="public" returns="boolean">
	/// 	<description> Returns value indicating whether page welcomes indexing by search engines or not </description>
	/// </function>
	public function GetAllowIndexing()
	{
		return $this->allowIndexing;
	}

	/// <function container="SMPages/SMPagesPage" name="GetLastModified" access="public" returns="integer">
	/// 	<description> Returns unix time stamp representing latest update time </description>
	/// </function>
	public function GetLastModified()
	{
		return $this->lastModified;
	}

	/// <function container="SMPages/SMPagesPage" name="GetUrl" access="public" returns="string">
	/// 	<description> Returns page URL </description>
	/// </function>
	public function GetUrl($resetUrlFormat = false) // Notice: Leave $resetUrlFormat parameter out of XML documentation above
	{
		SMTypeCheck::CheckObject(__METHOD__, "resetUrlFormat", $resetUrlFormat, SMTypeCheckType::$Boolean);

		if ($resetUrlFormat === true)
			self::$urlFormat = -1;

		if (self::$urlFormat === -1)
		{
			if (SMEnvironment::IsSubSite() === true && SMAttributes::GetAttribute("SMPagesSettingsUrlType") === null) // Subsites requires support for .htaccess, so SEO friendly URLs are already enabled - prefer Filename URLs
			{
				self::$urlFormat = 3;
			}
			else if (SMAttributes::GetAttribute("SMPagesSettingsUrlType") === null || SMAttributes::GetAttribute("SMPagesSettingsUrlType") === "UniqueId")
			{
				if (SMAttributes::GetAttribute("SMPagesSettingsSeoUrls") === null || SMAttributes::GetAttribute("SMPagesSettingsSeoUrls") === "false")
					self::$urlFormat = 0;
				else
					self::$urlFormat = 1;
			}
			else
			{
				if (SMAttributes::GetAttribute("SMPagesSettingsSeoUrls") === null || SMAttributes::GetAttribute("SMPagesSettingsSeoUrls") === "false")
					self::$urlFormat = 2;
				else
					self::$urlFormat = 3;
			}
		}

		if (self::$urlFormat === 3)
			return $this->filename . ".html";
		else if (self::$urlFormat === 2)
			return SMExtensionManager::GetExtensionUrl("SMPages") . "&SMPagesFilename=" . $this->filename;
		else if (self::$urlFormat === 1)
			return $this->guid . ".htm";
		else
			return SMExtensionManager::GetExtensionUrl("SMPages") . "&SMPagesId=" . $this->guid;
	}

	/// <function container="SMPages/SMPagesPage" name="SetPassword" access="public">
	/// 	<description> Set password to protect page from unauthorized access </description>
	/// 	<param name="value" type="string"> Page password </param>
	/// </function>
	public function SetPassword($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->password = $value;
	}

	/// <function container="SMPages/SMPagesPage" name="GetPassword" access="public" returns="string">
	/// 	<description> Returns page password if set, otherwise empty string </description>
	/// </function>
	public function GetPassword()
	{
		return $this->password;
	}

	// Database functions

	/// <function container="SMPages/SMPagesPage" name="CommitPersistent" access="public" returns="boolean">
	/// 	<description> Save page or update page within persistent data storage (database) - returns True on success, otherwise False </description>
	/// </function>
	public function CommitPersistent()
	{
		$db = new SMDataSource("SMPages");
		$kvc = new SMKeyValueCollection();

		$exists = (self::GetPersistentByGuid($this->guid) !== null);

		if ($exists === false)
			$kvc["guid"] = $this->guid;

		$kvc["filename"] = $this->filename;
		$kvc["title"] = $this->title;
		$kvc["content"] = $this->content;
		$kvc["accessible"] = (($this->accessible === true) ? "true" : "false");

		$kvc["template"] = $this->template;

		$kvc["keywords"] = $this->keywords;
		$kvc["description"] = $this->description;
		$kvc["allowindexing"] = (($this->allowIndexing === true) ? "true" : "false");

		$kvc["password"] = $this->password;

		$this->lastModified = time();
		$kvc["lastmodified"] = (string)$this->lastModified;

		if ($exists === true)
		{
			$updateCount = $db->Update($kvc, "guid = '" . $db->Escape($this->guid) . "'");

			if ($updateCount === 0)
				return false;
		}
		else
		{
			$db->Insert($kvc);
		}

		//$db->Commit();
		return true;
	}

	/// <function container="SMPages/SMPagesPage" name="DeletePersistent" access="public" returns="boolean">
	/// 	<description> Delete page from persistent data storage (database) - returns True on success, otherwise False </description>
	/// </function>
	public function DeletePersistent()
	{
		$db = new SMDataSource("SMPages");

		$deleteCount = $db->Delete("guid = '" . $db->Escape($this->guid) . "'");

		if ($deleteCount === 0)
			return false;

		//$db->Commit();
		return true;
	}

	/// <function container="SMPages/SMPagesPage" name="GetPersistentByGuid" access="public" static="true" returns="SMPagesPage">
	/// 	<description> Get page from persistent data storage (database) by ID (GUID) - returns page if found, otherwise Null </description>
	/// 	<param name="guid" type="string"> Page ID (GUID) </param>
	/// </function>
	public static function GetPersistentByGuid($guid)
	{
		SMTypeCheck::CheckObject(__METHOD__, "guid", $guid, SMTypeCheckType::$String);
		return self::getPersistent("guid", $guid);
	}

	/// <function container="SMPages/SMPagesPage" name="GetPersistentByFilename" access="public" static="true" returns="SMPagesPage">
	/// 	<description> Get page from persistent data storage (database) by filename - returns page if found, otherwise Null </description>
	/// 	<param name="filename" type="string"> Page filename </param>
	/// </function>
	public static function GetPersistentByFilename($filename)
	{
		SMTypeCheck::CheckObject(__METHOD__, "filename", $filename, SMTypeCheckType::$String);
		return self::getPersistent("filename", $filename);
	}

	private static function getPersistent($field, $search)
	{
		SMTypeCheck::CheckObject(__METHOD__, "field", $field, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);

		$db = new SMDataSource("SMPages");
		$kvcs = $db->Select("*", $field . " = '" . $db->Escape($search) . "'");

		if (count($kvcs) === 0)
			return null;

		$page = new SMPagesPage($kvcs[0]["guid"], $kvcs[0]["filename"], (int)$kvcs[0]["lastmodified"]);
		$page->SetTitle($kvcs[0]["title"]);
		$page->SetContent($kvcs[0]["content"]);
		$page->SetTemplate((($kvcs[0]["template"] !== null) ? $kvcs[0]["template"] : ""));
		$page->SetAccessible((($kvcs[0]["accessible"] === "true") ? true : false));
		$page->SetKeywords($kvcs[0]["keywords"]);
		$page->SetDescription($kvcs[0]["description"]);
		$page->SetAllowIndexing((($kvcs[0]["allowindexing"] === "true") ? true : false));
		$page->SetPassword((($kvcs[0]["password"] !== null) ? $kvcs[0]["password"] : "")); // Might be null - did not exist in first version

		return $page;
	}
}

/// <container name="SMPages/SMPagesLoader">
/// 	Class providing a loader mechanism to fetch all pages.
///
/// 	$pages = SMPagesLoader::GetPages();
/// </container>
class SMPagesLoader
{
	private function __construct()
	{
	}

	/// <function container="SMPages/SMPagesLoader" name="GetPages" access="public" static="true" returns="SMPagesPage[]">
	/// 	<description> Get all pages from persistent data storage (database) </description>
	/// 	<param name="includeContent" type="boolean" default="false"> Value indicating whether to include page content or not </param>
	/// </function>
	public static function GetPages($includeContent = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "includeContent", $includeContent, SMTypeCheckType::$Boolean);

		$db = new SMDataSource("SMPages");
		$kvcs = array();

		if ($includeContent === true)
			$kvcs = $db->Select("*", "", "filename ASC");
		else
			$kvcs = $db->Select("guid, filename, lastmodified, title, accessible, template, keywords, description, allowindexing, password", "", "filename ASC");

		$pages = array();

		foreach ($kvcs as $kvc)
		{
			if (strpos($kvc["filename"], "#") === 0) // Skip system pages (hidden pages created by SMPages that are prefixed with a hash in the filename)
				continue;

			$page = new SMPagesPage($kvc["guid"], $kvc["filename"], (int)$kvc["lastmodified"]);
			$page->SetTitle($kvc["title"]);

			if ($includeContent === true)
				$page->SetContent($kvc["content"]);

			$page->SetAccessible((($kvc["accessible"] === "true") ? true : false));
			$page->SetTemplate((($kvc["template"] !== null) ? $kvc["template"] : ""));
			$page->SetKeywords($kvc["keywords"]);
			$page->SetDescription($kvc["description"]);
			$page->SetAllowIndexing((($kvc["allowindexing"] === "true") ? true : false));
			$page->SetPassword((($kvc["password"] !== null) ? $kvc["password"] : "")); // Might be null - did not exist in first version

			$pages[] = $page;
		}

		return $pages;
	}
}

?>
