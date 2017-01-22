<?php

/// <container name="SMMenu">
/// 	SMMenu is an extension running within Sitemagic CMS.
/// 	It provides navigation capabilities on the website and for
/// 	the various other extensions running within Sitemagic CMS.
/// </container>

/// <container name="SMMenu/SMMenuLinkList">
/// 	This class provides access to the link list which may be opened when
/// 	the user wants to create a new link in the navigation menu.
///
/// 	// Add links to link list - this will enable the user to easily link to a search engine
///
/// 	$menuEnabled = SMExtensionManager::ExtensionEnabled(&quot;SMMenu&quot;);
///
/// 	if ($menuEnabled === true &amp;&amp; SMMenuLinkList::GetInstance()-&gt;GetReadyState() === true)
/// 	{
/// 		&#160;&#160;&#160;&#160; SMMenuLinkList::GetInstance()-&gt;AddLink(&quot;Search&quot;, &quot;Google", &quot;http://google.com&quot;);
/// 		&#160;&#160;&#160;&#160; SMMenuLinkList::GetInstance()-&gt;AddLink(&quot;Search&quot;, &quot;Altavista", &quot;http://av.com&quot;);
/// 		&#160;&#160;&#160;&#160; SMMenuLinkList::GetInstance()-&gt;AddLink(&quot;Search&quot;, &quot;Bing", &quot;http://bing.com&quot;);
/// 	}
/// </container>
class SMMenuLinkList
{
	private static $instance = null;
	private $ready;
	private $links;

	private function __construct()
	{
		$this->ready = false;
		$this->links = array();
	}

	/// <function container="SMMenu/SMMenuLinkList" name="GetInstance" access="public" static="true" returns="SMMenuLinkList">
	/// 	<description> Returns instance of SMMenuLinkList to which links may be added </description>
	/// </function>
	public static function GetInstance()
	{
		if (self::$instance === null)
			self::$instance = new SMMenuLinkList();

		return self::$instance;
	}

	/// <function container="SMMenu/SMMenuLinkList" name="GetLinkCollection" access="public" returns="string[][]">
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

	/// <function container="SMMenu/SMMenuLinkList" name="SetLinkCollection" access="public">
	/// 	<description> Set internal link collection as a multidimentional array. </description>
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

	/// <function container="SMMenu/SMMenuLinkList" name="AddLink" access="public">
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

	/// <function container="SMMenu/SMMenuLinkList" name="GetReadyState" access="public" returns="boolean">
	/// 	<description> Returns True when link list is loaded and links should be added, False otherwise </description>
	/// </function>
	public function GetReadyState()
	{
		return $this->ready;
	}

	/// <function container="SMMenu/SMMenuLinkList" name="SetReadyState" access="public">
	/// 	<description> Set value indicating whether link providers should add their links </description>
	/// 	<param name="url" type="boolean"> Set True to have link providers add links, False not to </param>
	/// </function>
	public function SetReadyState($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->ready = $value;
	}
}

/// <container name="SMMenu/SMMenuItemAppendMode">
/// 	Enum defining how to add a new link to a menu item
/// </container>
class SMMenuItemAppendMode
{
	/// <member container="SMMenu/SMMenuItemAppendMode" name="Beginning" access="public" static="true" type="string" default="Beginning">
	/// 	<description> Add link to the beginning of the children collection </description>
	/// </member>
	public static $Beginning = "Beginning";

	/// <member container="SMMenu/SMMenuItemAppendMode" name="End" access="public" static="true" type="string" default="End">
	/// 	<description> Add link to the end of the children collection </description>
	/// </member>
	public static $End = "End";
}

/// <container name="SMMenu/SMMenuItem">
/// 	Class represents an item within the navigation menu.
///
/// 	$item = new SMMenuItem("MyExtensionProducts", "Products", "");
/// 	$item->AddChild(new SMMenuItem("MyExtensionProductsLaptops", "Laptops", "Laptops.html"));
/// 	$item->AddChild(new SMMenuItem("MyExtensionProductsTablets", "Tablet PCs", "Tablets.html"));
/// </container>
class SMMenuItem
{
	private $parentId;		// string
	private $id;			// string
	private $children;		// SMMenuItem[]
	private $title;			// string
	private $url;			// string
	private $order;			// int

	/// <function container="SMMenu/SMMenuItem" name="__construct" access="public">
	/// 	<description> Create instance of SMMenuItem </description>
	/// 	<param name="id" type="string"> Unique ID identifying menu item </param>
	/// 	<param name="title" type="string"> Menu item title </param>
	/// 	<param name="url" type="string"> Menu item link reference (URL) </param>
	/// </function>
	public function __construct($id, $title, $url, $parentId = "")
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "url", $url, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "parentId", $parentId, SMTypeCheckType::$String);

		$this->parentId = $parentId;
		$this->id = $id;
		$this->children = array();
		$this->title = $title;
		$this->url = $url;
		$this->order = 0;
	}

	/// <function container="SMMenu/SMMenuItem" name="AddChild" access="public" returns="SMMenuItem">
	/// 	<description> Add child to menu item - new instance is returned which is now associated with parent item </description>
	/// 	<param name="child" type="SMMenuItem"> Child to add </param>
	/// 	<param name="appendMode" type="SMMenuItemAppendMode" default="SMMenuItemAppendMode::$End"> Value indicating whether to add menu item to beginning or end of children collection </param>
	/// 	<param name="commit" type="boolean" default="false"> Value indicating whether to immediately make change permanent or not </param>
	/// </function>
	public function AddChild(SMMenuItem $child, $appendMode = "End", $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "appendMode", $appendMode, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		if (property_exists("SMMenuItemAppendMode", $appendMode) === false)
			throw new Exception("Invalid append mode '" . $appendMode . "' specified - use SMMenuItemAppendMode::Mode");

		$newChild = new SMMenuItem($child->GetId(), $child->GetTitle(), $child->GetUrl(), $this->id);
		$newChild->SetChildren($child->GetChildren());
		$newChild->SetOrder($child->GetOrder());

		if ($appendMode === SMMenuItemAppendMode::$End)
		{
			$order = ((count($this->children) > 0) ? $this->children[count($this->children) - 1]->GetOrder() + 1 : 0);
			$newChild->SetOrder($order);
			$this->children[] = $newChild;
		}
		else
		{
			$order = ((count($this->children) > 0) ? $this->children[0]->GetOrder() - 1 : 0);
			$newChild->SetOrder($order);
			array_unshift($this->children, $newChild);
		}

		if ($commit === true)
			return $newChild->CommitPersistent(true);

		// Returning new child element which is now associated with parent item
		return $newChild;
	}

	/// <function container="SMMenu/SMMenuItem" name="RemoveChild" access="public" returns="boolean">
	/// 	<description> Remove child from children collection - returns True on succes, otherwise False </description>
	/// 	<param name="id" type="string"> ID of child to remove </param>
	/// 	<param name="commit" type="boolean" default="false"> Value indicating whether to immediately make change permanent or not </param>
	/// </function>
	public function RemoveChild($id, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		$tmp = array();
		$childRemoved = null;

		foreach ($this->children as $child)
		{
			if ($child->GetId() !== $id)
				$tmp[] = $child;
			else
				$childRemoved = $child;
		}

		if ($childRemoved === null)
			return false;

		$this->children = $tmp;

		if ($commit === true)
			return $childRemoved->DeletePersistent(true);

		return true;
	}

	/// <function container="SMMenu/SMMenuItem" name="SetChildren" access="public">
	/// 	<description> Set internal children collection </description>
	/// 	<param name="children" type="SMMenuItem[]"> Array of SMMenuItem instances </param>
	/// </function>
	public function SetChildren($children)
	{
		SMTypeCheck::CheckArray(__METHOD__, "children", $children, "SMMenuItem");

		$this->children = array();
		$newChild = null;

		foreach ($children as $child)
		{
			$newChild = new SMMenuItem($child->GetId(), $child->GetTitle(), $child->GetUrl(), $this->id);
			$newChild->SetChildren($child->GetChildren());
			$newChild->SetOrder($child->GetOrder());

			$this->children[] = $newChild;
		}
	}

	/// <function container="SMMenu/SMMenuItem" name="GetChildren" access="public" returns="SMMenuItem[]">
	/// 	<description> Get internal children collection </description>
	/// </function>
	public function GetChildren()
	{
		return $this->children;
	}

	/// <function container="SMMenu/SMMenuItem" name="GetChild" access="public" returns="SMMenuItem">
	/// 	<description> Returns child if found, otherwise Null </description>
	/// 	<param name="id" type="string"> ID of child to return </param>
	/// </function>
	public function GetChild($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		foreach ($this->children as $child)
			if ($child->GetId() === $id)
				return $child;

		return null;
	}

	/// <function container="SMMenu/SMMenuItem" name="GetParentId" access="public" returns="string">
	/// 	<description> Returns ID of parent item if attached to a parent, otherwise an empty string </description>
	/// </function>
	public function GetParentId()
	{
		return $this->parentId;
	}

	/// <function container="SMMenu/SMMenuItem" name="GetId" access="public" returns="string">
	/// 	<description> Returns item ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="SMMenu/SMMenuItem" name="SetTitle" access="public">
	/// 	<description> Set item title </description>
	/// 	<param name="value" type="string"> Title value </param>
	/// </function>
	public function SetTitle($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->title = $value;
	}

	/// <function container="SMMenu/SMMenuItem" name="GetTitle" access="public" returns="string">
	/// 	<description> Returns item title </description>
	/// </function>
	public function GetTitle()
	{
		return $this->title;
	}

	/// <function container="SMMenu/SMMenuItem" name="SetUrl" access="public">
	/// 	<description> Set item URL </description>
	/// 	<param name="value" type="string"> URL value </param>
	/// </function>
	public function SetUrl($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->url = $value;
	}

	/// <function container="SMMenu/SMMenuItem" name="GetUrl" access="public" returns="string">
	/// 	<description> Returns item URL </description>
	/// 	<param name="eval" type="boolean" default="false"> Set True to evaluate/translate tilde (~) to absolute path </param>
	/// </function>
	public function GetUrl($eval = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "eval", $eval, SMTypeCheckType::$Boolean);

		if ($eval === true && strpos($this->url, "~") === 0)
		{
			$path = SMEnvironment::GetRequestPath();
			$path .= (($path !== "/") ? "/" : ""); // E.g. / or /sites/demo/

			$url = substr($this->url, 1); // Remove tilde
			$url = ((strpos($url, "/") === 0) ? substr($url, 1) : $url); // Remove any leading slash

			return $path . $url;
		}

		return $this->url;
	}

	/// <function container="SMMenu/SMMenuItem" name="SetOrder" access="public">
	/// 	<description> Set value representing order of appearance </description>
	/// 	<param name="order" type="integer"> New value by which item is ordered </param>
	/// </function>
	public function SetOrder($order)
	{
		SMTypeCheck::CheckObject(__METHOD__, "order", $order, SMTypeCheckType::$Integer);
		$this->order = $order;
	}

	/// <function container="SMMenu/SMMenuItem" name="GetOrder" access="public" returns="integer">
	/// 	<description> Returns value representing order of appearance </description>
	/// </function>
	public function GetOrder()
	{
		return $this->order;
	}

	/// <function container="SMMenu/SMMenuItem" name="Sort" access="public">
	/// 	<description> Sort children by display title </description>
	/// </function>
	public function Sort()
	{
		usort($this->children, array($this, "compare"));
	}

	private function compare($a, $b)
	{
		return strcmp($a->GetTitle(), $b->GetTitle());
	}

	// Database functions

	/// <function container="SMMenu/SMMenuItem" name="DeletePersistent" access="public" returns="boolean">
	/// 	<description> Delete item from persistent data storage (database) - returns True on success, otherwise False </description>
	/// 	<param name="deleteChildren" type="boolean" default="false"> Value indicating whether to delete children or not. It is recommended to pass True to avoid orphaned children. </param>
	/// </function>
	public function DeletePersistent($deleteChildren = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "deleteChildren", $deleteChildren, SMTypeCheckType::$Boolean);

		$db = new SMDataSource("SMMenu");
		$result = false;

		if ($deleteChildren === true)
		{
			foreach ($this->children as $child)
			{
				$result = $this->deletePersistentRecursively($child, $db);

				if ($result === false)
					return false;
			}
		}

		$deleteCount = $db->Delete("id = '" . $db->Escape($this->id) . "'");

		if ($deleteCount === 0)
			return false;

		//$db->Commit();
		return true;
	}

	private function deletePersistentRecursively(SMMenuItem $parent, SMIDataSource $db)
	{
		$children = $parent->GetChildren();
		$result = false;

		foreach ($children as $child)
		{
			$result = $this->deletePersistentRecursively($child, $db);

			if ($result === false)
				return false;
		}

		$deleteCount = $db->Delete("id = '" . $db->Escape($parent->GetId()) . "'");
		return ($deleteCount > 0);
	}

	/// <function container="SMMenu/SMMenuItem" name="CommitPersistent" access="public" returns="boolean">
	/// 	<description> Update item within persistent data storage (database) - returns True on success, otherwise False </description>
	/// 	<param name="commitChildren" type="boolean" default="false"> Value indicating whether to update children or not. </param>
	/// </function>
	public function CommitPersistent($commitChildren = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "commitChildren", $commitChildren, SMTypeCheckType::$Boolean);

		$db = new SMDataSource("SMMenu");
		$kvc = new SMKeyValueCollection();

		if (self::GetPersistent($this->id) !== null)
		{
			$kvc["title"] = $this->title;
			$kvc["url"] = $this->url;
			$kvc["order"] = (string)$this->order;
			$updateCount = $db->Update($kvc, "id = '" . $db->Escape($this->id) . "'");

			if ($updateCount === 0)
				return false;
		}
		else
		{
			$kvc["id"] = $this->id;
			$kvc["parent"] = $this->parentId;
			$kvc["order"] = (string)$this->order;
			$kvc["title"] = $this->title;
			$kvc["url"] = $this->url;
			$db->Insert($kvc);
		}

		if ($commitChildren === true)
		{
			$result = $this->commitPersistentChildren($this, $db);

			if ($result === false)
				return false;
		}

		//$db->Commit();
		return true;
	}

	private function commitPersistentChildren(SMMenuItem $parent, SMIDataSource $db)
	{
		$children = $parent->GetChildren();
		$updateCount = -1;
		$result = false;
		$kvc = null;

		foreach ($children as $child)
		{
			$kvc = new SMKeyValueCollection();

			if (self::GetPersistent($child->GetId()) !== null)
			{
				$kvc["title"] = $child->GetTitle();
				$kvc["url"] = $child->GetUrl();
				$kvc["order"] = (string)$child->GetOrder();
				$updateCount = $db->Update($kvc, "id = '" . $db->Escape($child->GetId()) . "'");

				if ($updateCount === 0)
					return false;
			}
			else
			{
				$kvc["id"] = $child->GetId();
				$kvc["parent"] = $child->GetParentId();
				$kvc["order"] = (string)$child->GetOrder();
				$kvc["title"] = $child->GetTitle();
				$kvc["url"] = $child->GetUrl();
				$db->Insert($kvc);
			}

			$result = $this->commitPersistentChildren($child, $db);

			if ($result === false)
				return false;
		}

		return true;
	}

	/// <function container="SMMenu/SMMenuItem" name="GetPersistent" access="public" static="true" returns="SMMenuItem">
	/// 	<description> Get item from persistent data storage (database) - returns instance of SMMenuItem if found, otherwise Null </description>
	/// 	<param name="id" type="string"> Unique item ID </param>
	/// 	<param name="getChildren" type="boolean" default="false"> Value indicating whether to include children or not </param>
	/// </function>
	public static function GetPersistent($id, $getChildren = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "getChildren", $getChildren, SMTypeCheckType::$Boolean);

		$db = new SMDataSource("SMMenu");
		$kvcs = $db->Select("*", "id = '" . $db->Escape($id) . "'", "order ASC");

		if (count($kvcs) === 0)
			return null;

		$child = new SMMenuItem($kvcs[0]["id"], $kvcs[0]["title"], $kvcs[0]["url"], $kvcs[0]["parent"]);
		$child->SetOrder((int)$kvcs[0]["order"]);

		if ($getChildren === true)
			self::getPersistetChildren($child, $db);

		return $child;
	}

	private static function getPersistetChildren(SMMenuItem $parent, SMIDataSource $db)
	{
		$kvcs = $db->Select("*", "parent = '" . $db->Escape($parent->GetId()) . "'", "order ASC");

		if (count($kvcs) === 0)
			return;

		$child = null;

		foreach ($kvcs as $kvc)
		{
			$child = new SMMenuItem($kvc["id"], $kvc["title"], $kvc["url"]);
			$child->SetOrder((int)$kvc["order"]);
			self::getPersistetChildren($child, $db);
			$parent->AddChild($child);
		}
	}
}

/// <container name="SMMenu/SMMenuManager">
/// 	Class provides access to the navigation menu.
///
/// 	// Add menu item - only visible when logged in.
/// 	// Notice that this should be done after the Init stage of the life cycle,
/// 	// and only if SMExtensionManager::ExtensionEnabled(&quot;SMMenu&quot;) returns true.
///
/// 	$menu = SMMenuManager::GetInstance();
///
/// 	if (SMAuthentication::Authorized() === true)
/// 	{
/// 		&#160;&#160;&#160;&#160; $id = SMRandom::CreateGuid();
/// 		&#160;&#160;&#160;&#160; $title = &quot;Change password&quot;;
/// 		&#160;&#160;&#160;&#160; $url = SMExtensionManager::GetExtensionUrl(&quot;MyExtension&quot;) . &quot;&amp;MyExtensionFunction=ChangePassword&quot;;
/// 		&#160;&#160;&#160;&#160;
/// 		&#160;&#160;&#160;&#160; $menu-&gt;AddChild(new SMMenuItem($id, $title, $url));
/// 	}
///
/// 	// Add permanent menu item if not already added
///
/// 	$uid = &quot;180c7d99871a41819758642d0481e06e&quot;;
///
/// 	if ($menu-&gt;GetChild($uid, true) === null)
/// 	{
/// 		&#160;&#160;&#160;&#160; $item = new SMMenuItem($uid, &quot;Online help&quot;, &quot;http://sitemagic.org&quot;);
/// 		&#160;&#160;&#160;&#160; $menu-&gt;AddChild($item, SMMenuItemAppendMode::$Beginning, true);
/// 	}
///
/// 	// Add menu item to the admin menu item which is accessible only when logged in.
/// 	// Notice that this should be done during the PreTemplateUpdate stage of the life cycle,
/// 	// as the admin menu item is not available until then.
///
/// 	$adm = $menu-&gt;GetChild("SMMenuAdmin"); // Null if not found (not logged in)
///
/// 	if ($adm !== null)
/// 	{
/// 		&#160;&#160;&#160;&#160; $url = &quot;index.php?SMExt=MyExtension&amp;MyExtensionFunc=Config&quot;
/// 		&#160;&#160;&#160;&#160; $adm-&gt;AddChild(new SMMenuItem(&quot;MyExtensionConfig&quot;, &quot;Config MyExtension&quot;, $url));
/// 	}
/// </container>
class SMMenuManager
{
	private static $instance = null;
	private $rootItem;

	private function __construct()
	{
		$this->rootItem = new SMMenuItem("", "", "");
	}

	/// <function container="SMMenu/SMMenuManager" name="GetInstance" access="public" static="true" returns="SMMenuManager">
	/// 	<description> Returns instance of SMMenuManager which represents the navigation menu </description>
	/// </function>
	public static function GetInstance()
	{
		if (self::$instance === null)
			self::$instance = new SMMenuManager();

		return self::$instance;
	}

	/// <function container="SMMenu/SMMenuManager" name="AddChild" access="public" returns="SMMenuItem">
	/// 	<description> Add menu item to navigation menu </description>
	/// 	<param name="child" type="SMMenuItem"> Menu item to add </param>
	/// 	<param name="appendMode" type="SMMenuItemAppendMode" default="SMMenuItemAppendMode::$End"> Value indicating whether to add menu item to beginning or end of navigation menu </param>
	/// 	<param name="commit" type="boolean" default="false"> Value indicating whether to immediately make change permanent or not </param>
	/// </function>
	public function AddChild(SMMenuItem $child, $appendMode = "End", $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "appendMode", $appendMode, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		if (property_exists("SMMenuItemAppendMode", $appendMode) === false)
			throw new Exception("Invalid append mode '" . $appendMode . "' specified - use SMMenuItemAppendMode::Mode");

		return $this->rootItem->AddChild($child, $appendMode, $commit);
	}

	/// <function container="SMMenu/SMMenuManager" name="RemoveChild" access="public" returns="boolean">
	/// 	<description> Remove menu item from navigation menu - returns True on succes, otherwise False </description>
	/// 	<param name="id" type="string"> ID of menu item to remove </param>
	/// 	<param name="commit" type="boolean" default="false"> Value indicating whether to immediately make change permanent or not </param>
	/// </function>
	public function RemoveChild($id, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		return $this->rootItem->RemoveChild($id, $commit);
	}

	/// <function container="SMMenu/SMMenuManager" name="SetChildren" access="public">
	/// 	<description> Set internal collection of menu items </description>
	/// 	<param name="children" type="SMMenuItem[]"> Array of SMMenuItem instances </param>
	/// </function>
	public function SetChildren($children)
	{
		SMTypeCheck::CheckArray(__METHOD__, "children", $children, "SMMenuItem");
		$this->rootItem->SetChildren($children);
	}

	/// <function container="SMMenu/SMMenuManager" name="GetChildren" access="public" returns="SMMenuItem[]">
	/// 	<description> Get internal collection of menu items </description>
	/// </function>
	public function GetChildren()
	{
		return $this->rootItem->GetChildren();
	}

	/// <function container="SMMenu/SMMenuManager" name="GetChild" access="public" returns="SMMenuItem">
	/// 	<description> Returns menu item if found, otherwise Null </description>
	/// 	<param name="id" type="string"> ID of menu item to return </param>
	/// 	<param name="searchDeep" type="boolean" default="false"> Search recursively through entire hierarchy </param>
	/// </function>
	public function GetChild($id, $searchDeep = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "searchDeep", $searchDeep, SMTypeCheckType::$Boolean);

		if ($searchDeep === false)
		{
			$children = $this->rootItem->GetChildren();

			foreach ($children as $child)
				if ($child->GetId() === $id)
					return $child;
		}
		else
		{
			$children = $this->rootItem->GetChildren();
			$result = null;

			foreach ($children as $child)
			{
				$result = $this->getChildDeep($child, $id);

				if ($result !== null)
					return $result;
			}
		}

		return null;
	}

	private function getChildDeep(SMMenuItem $parent, $id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		if ($parent->GetId() === $id)
			return $parent;

		$children = $parent->GetChildren();

		if (count($children) === 0)
			return null;

		$result = null;

		foreach ($children as $child)
		{
			$result = $this->getChildDeep($child, $id);

			if ($result !== null)
				return $result;
		}

		return null;
	}

	/// <function container="SMMenu/SMMenuManager" name="MoveChildUp" access="public" returns="boolean">
	/// 	<description> Move menu item up within its hierarchy (order of appearance) - returns True on success, otherwise False </description>
	/// 	<param name="id" type="string"> ID of menu item to move up </param>
	/// 	<param name="commit" type="boolean" default="false"> Value indicating whether to immediately make change permanent or not </param>
	/// </function>
	public function MoveChildUp($id, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		return $this->moveChild($id, "up", $commit);
	}

	/// <function container="SMMenu/SMMenuManager" name="MoveChildDown" access="public" returns="boolean">
	/// 	<description> Move menu item down within its hierarchy (order of appearance) - returns True on success, otherwise False </description>
	/// 	<param name="id" type="string"> ID of menu item to move down </param>
	/// 	<param name="commit" type="boolean" default="false"> Value indicating whether to immediately make change permanent or not </param>
	/// </function>
	public function MoveChildDown($id, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		return $this->moveChild($id, "down", $commit);
	}

	private function moveChild($id, $direction, $commit = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "direction", $direction, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "commit", $commit, SMTypeCheckType::$Boolean);

		$childToMove = $this->GetChild($id, true);

		if ($childToMove === null)
			return false;

		$parent = null;
		$children = null;

		if ($childToMove->GetParentId() === "")
		{
			$children = $this->rootItem->GetChildren();
		}
		else
		{
			$parent = $this->GetChild($childToMove->GetParentId(), true);
			$children = $parent->GetChildren();
		}

		$tmpChild = null;
		$tmpOrder = -1;

		for ($i = 0 ; $i < count($children) ; $i++)
		{
			if ($children[$i]->GetId() === $childToMove->GetId())
			{
				if ($direction === "up")
				{
					if ($i === 0)
						return true;

					$tmpChild = $children[$i - 1];
					$tmpOrder = $children[$i - 1]->GetOrder();

					$tmpChild->SetOrder($children[$i]->GetOrder());
					$children[$i]->SetOrder($tmpOrder);

					$children[$i - 1] = $children[$i];
					$children[$i] = $tmpChild;
				}
				else
				{
					if ($i === count($children) - 1)
						return true;

					$tmpChild = $children[$i + 1];
					$tmpOrder = $children[$i + 1]->GetOrder();

					$tmpChild->SetOrder($children[$i]->GetOrder());
					$children[$i]->SetOrder($tmpOrder);

					$children[$i + 1] = $children[$i];
					$children[$i] = $tmpChild;
				}

				break;
			}
		}

		if ($parent === null)
		{
			$this->rootItem->SetChildren($children);
			$result = false;

			if ($commit === true)
			{
				// Calling $this->rootItem->CommitPersistent(true) is not allowed!
				// It will commit the rootItem which is not a real menu entry
				// - it only serves as our collection of root menu items.
				foreach ($children as $child)
				{
					$result = $child->CommitPersistent();

					if ($result === false)
						return false;
				}
			}
		}
		else
		{
			$parent->SetChildren($children);

			if ($commit === true)
				return $parent->CommitPersistent(true);
		}

		return true;
	}

	/// <function container="SMMenu/SMMenuManager" name="PopulateTemplate" access="public">
	/// 	<description>
	/// 		Populate navigation menu to design template - this is invoked by the
	/// 		SMMenu extensions during the TemplateUpdateComplete stage of the life cycle.
	/// 	</description>
	/// 	<param name="template" type="SMTemplate"> Instance of SMTemplate which represents the underlaying design template </param>
	/// </function>
	public function PopulateTemplate(SMTemplate $template)
	{
		$kvcs = array();
		$kvc = null;

		$children = $this->rootItem->GetChildren();

		foreach ($children as $child)
		{
			$kvc = new SMKeyValueCollection();
			$kvc["SMMenuItemLevel1 Id"] = "SMMenu" . $child->GetId();
			$kvc["SMMenuItemLevel1 Title"] = SMStringUtilities::HtmlEncode($child->GetTitle());
			$kvc["SMMenuItemLevel1 Url"] = SMStringUtilities::HtmlEncode((($child->GetUrl() !== "" && $child->GetUrl() !== "http://") ? $child->GetUrl(true) : "javascript:void(0)"));

			$kvcs[] = $kvc;
		}

		$template->ReplaceTagsRepeated("SMMenuLevel1", $kvcs);

		foreach ($children as $child)
			$this->populateTemplateChildren($template, $child, 2);
	}

	private function populateTemplateChildren(SMTemplate $template, SMMenuItem $parent, $level)
	{
		SMTypeCheck::CheckObject(__METHOD__, "level", $level, SMTypeCheckType::$Integer);

		$children = $parent->GetChildren();

		$kvcs = array();
		$kvc = null;

		foreach ($children as $child)
		{
			$kvc = new SMKeyValueCollection();
			$kvc["SMMenuItemLevel" . (string)$level . " SMMenu" . $parent->GetId() . " Id"] = "SMMenu" . $child->GetId();
			$kvc["SMMenuItemLevel" . (string)$level . " SMMenu" . $parent->GetId() . " Title"] = SMStringUtilities::HtmlEncode($child->GetTitle());
			$kvc["SMMenuItemLevel" . (string)$level . " SMMenu" . $parent->GetId() . " Url"] = SMStringUtilities::HtmlEncode((($child->GetUrl() !== "" && $child->GetUrl() !== "http://") ? $child->GetUrl(true) : "javascript:void(0)"));

			$kvcs[] = $kvc;
		}

		$template->ReplaceTagsRepeated("SMMenuLevel" . (string)$level . " SMMenu" . $parent->GetId(), $kvcs);

		foreach ($children as $child)
			$this->populateTemplateChildren($template, $child, $level + 1);
	}

	/// <function container="SMMenu/SMMenuManager" name="LoadPersistentMenuItems" access="public">
	/// 	<description>
	/// 		Invoked by the SMMenu extension during the Init stage of the life cycle
	/// 		- causes the SMMenuManager instance to load all persisted menu items.
	/// 	</description>
	/// </function>
	public function LoadPersistentMenuItems()
	{
		$items = SMMenuLoader::GetMenuItems();
		$fetched = array();

		foreach ($items as $item)
			$fetched[] = $item;

		$this->rootItem->SetChildren($fetched);
	}
}

/// <container name="SMMenu/SMMenuLoader">
/// 	Class providing a loader mechanism to fetch all menu items recursively
///
/// 	$items = SMMenuLoader::GetMenuItems();
/// </container>
class SMMenuLoader
{
	private function __construct()
	{
	}

	/// <function container="SMMenu/SMMenuLoader" name="GetMenuItems" access="public" static="true" returns="SMMenuItem[]">
	/// 	<description> Recursively loads and returns all menu items from persistent data storage (database) </description>
	/// </function>
	public static function GetMenuItems()
	{
		$db = new SMDataSource("SMMenu");
		$kvcs = $db->Select("*", "parent = ''", "order ASC");
		$item = null;
		$items = array();

		foreach ($kvcs as $kvc)
		{
			$item = new SMMenuItem($kvc["id"], $kvc["title"], $kvc["url"]);
			$item->SetOrder((int)$kvc["order"]);
			$items[] = $item;
			self::getChildren($items[count($items) - 1], $db);
		}

		return $items;
	}

	private static function getChildren(SMMenuItem $parentItem, SMIDataSource $db)
	{
		$kvcs = $db->Select("*", "parent = '" . $db->Escape($parentItem->GetId()) . "'", "order ASC");
		$child = null;

		foreach ($kvcs as $kvc)
		{
			$child = new SMMenuItem($kvc["id"], $kvc["title"], $kvc["url"]);
			$child->SetOrder((int)$kvc["order"]);
			self::getChildren($child, $db);

			$parentItem->AddChild($child);
		}
	}
}

?>
