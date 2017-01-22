<?php

/// <container name="gui/SMTreeMenu">
/// 	Class represents a tree structure with selectable nodes.
///
/// 	// Construct tree menu
///
/// 	$id = &quot;MyExtensionTreeMenu&quot;
///
/// 	$tree = new SMTreeMenu($id);
/// 	$tree-&gt;SetAutoPostBack(true);
///
/// 	$itemImages = new SMTreeMenuItem($id . &quot;Item1&quot;, &quot;Images&quot;, &quot;Images&quot;);
/// 	$itemImages-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item11&quot;, &quot;FancyCar.png&quot;, &quot;Images/FancyCar.png&quot;));
/// 	$itemImages-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item12&quot;, &quot;SantaClaus.gif&quot;, &quot;Images/SantaClaus.gif&quot;));
/// 	$itemImages-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item13&quot;, &quot;Summer.png&quot;, &quot;Images/Summer.png&quot;));
///
/// 	$itemDocs = new SMTreeMenuItem($id . &quot;Item2&quot;, &quot;Documents&quot;, &quot;Documents&quot;);
/// 	$itemDocs-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item21&quot;, &quot;CMS Events.pdf&quot;, &quot;Documents/CMS Events.pdf&quot;));
/// 	$itemDocs-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item22&quot;, &quot;Sitemagic.pdf&quot;, &quot;Documents/Sitemagic.pdf&quot;));
/// 	$itemDocs-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item23&quot;, &quot;Tasks May.docx&quot;, &quot;Documents/Tasks May.docx&quot;));
///
/// 	$itemDocsEvts = new SMTreeMenuItem($id . &quot;Item3&quot;, &quot;Events&quot;, &quot;Events&quot;);
/// 	$itemDocsEvts-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item31&quot;, &quot;2009.pdf&quot;, &quot;Documents/Events/2009.pdf&quot;));
/// 	$itemDocsEvts-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item32&quot;, &quot;2010.pdf&quot;, &quot;Documents/Events/2010.pdf&quot;));
/// 	$itemDocsEvts-&gt;AddChild(new SMTreeMenuItem($id . &quot;Item33&quot;, &quot;2011.pdf&quot;, &quot;Documents/Events/2011.pdf&quot;));
/// 	$itemDocs-&gt;AddChild($itemDocsEvts);
///
/// 	$tree-&gt;AddChild($itemImages);
/// 	$tree-&gt;AddChild($itemDocs);
///
/// 	// The code above will create a tree menu with the following structure:
/// 	//
/// 	// &#160;&#160; Images
/// 	// &#160;&#160;&#160;&#160; |- FancyCar.png
/// 	// &#160;&#160;&#160;&#160; |- SantaClaus.gif
/// 	// &#160;&#160;&#160;&#160; |- Summer.png
/// 	// &#160;&#160; Documents
/// 	// &#160;&#160;&#160;&#160; |- CMS Events.pdf
/// 	// &#160;&#160;&#160;&#160; |- Sitemagic.pdf
/// 	// &#160;&#160;&#160;&#160; |- Tasks May.docx
/// 	// &#160;&#160;&#160;&#160; |- Events
/// 	// &#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; |- 2009.pdf
/// 	// &#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; |- 2010.pdf
/// 	// &#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; |- 2011.pdf
///
/// 	// Handle item selection
///
/// 	if ($tree-&gt;PerformedPostBack() === true &amp;&amp; $this-&gt;isSafe(&quot;files/&quot; . $tree-&gt;GetSelectedValue()) === true)
/// 	{
/// 		&#160;&#160;&#160;&#160; SMFileSystem::DownloadFileToClient(&quot;files/&quot; . $tree-&gt;GetSelectedValue());
/// 	}
/// </container>
class SMTreeMenu
{
	private $children;				// SMMMenItem[]
	private $id;					// String
	private $startCollapsed;		// Bool
	private $autoPostBack;			// Bool
	private $restoreState;			// Bool
	private $restoreSelection;		// Bool
	private $selectedId;			// String
	private $doRender;				// Bool

	/// <function container="gui/SMTreeMenu" name="__construct" access="public">
	/// 	<description> Create instance of SMTreeMenu </description>
	/// 	<param name="id" type="string"> Unique ID identifying tree menu control </param>
	/// </function>
	public function __construct($id)
	{
		// Note: $id MUST be the same on every page load, as it
		// is used to restore the menu state (expanded/collapsed),
		// and retreive the post back value.

		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$this->children = array();
		$this->id = $id;
		$this->startCollapsed = false;
		$this->autoPostBack = false;
		$this->restoreState = true;
		$this->restoreSelection = true;
		$this->selectedId = ((SMEnvironment::GetPostValue("SMTreeMenuItemId" . $this->id) !== "") ? SMEnvironment::GetPostValue("SMTreeMenuItemId" . $this->id) : null);
		$this->doRender = true;
	}

	/// <function container="gui/SMTreeMenu" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMTreeMenu" name="AddChild" access="public">
	/// 	<description> Add menu item to tree menu </description>
	/// 	<param name="child" type="SMTreeMenuItem"> Instance of SMTreeMenuItem </param>
	/// </function>
	public function AddChild(SMTreeMenuItem $child)
	{
		$newChild = new SMTreeMenuItem($child->GetId(), $child->GetTitle(), $child->GetValue(), $this->id, "", $child->GetChildren(), $this->startCollapsed);
		$newChild->SetSelectable($child->GetSelectable());
		$this->children[] = $newChild;
	}

	/// <function container="gui/SMTreeMenu" name="RemoveChild" access="public" returns="boolean">
	/// 	<description> Remove menu item by ID. Returns True on success, otherwise False. </description>
	/// 	<param name="id" type="string"> ID of item to remove </param>
	/// 	<param name="searchDeep" type="boolean" default="false">
	/// 		By default only the root of the tree menu is searched for an item
	/// 		with the given ID. Set this argument True to search recursively and
	/// 		remove the item no matter its position in the hierarchy.
	/// 	</param>
	/// </function>
	public function RemoveChild($id, $searchDeep = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "searchDeep", $searchDeep, SMTypeCheckType::$Boolean);

		$child = $this->GetChild($id, $searchDeep);

		if ($child === null)
			return false;

		if ($child->GetParentId() === "")
		{
			$tmp = array();

			foreach ($this->children as $child)
				if ($child->GetId() !== $id)
					$tmp[] = $child;

			$result = (count($tmp) < count($this->children));
			$this->children = $tmp;

			return $result;
		}
		else
		{
			$parent = $this->GetChild($child->GetParentId(), true);

			if ($parent === null)
				return false;

			return $parent->RemoveChild($id);
		}
	}

	/// <function container="gui/SMTreeMenu" name="SetChildren" access="public">
	/// 	<description> Set collection of items in tree menu </description>
	/// 	<param name="children" type="SMTreeMenuItem[]"> Array of SMTreeMenuItem instances </param>
	/// </function>
	public function SetChildren($children)
	{
		SMTypeCheck::CheckArray(__METHOD__, "children", $children, "SMTreeMenuItem");

		$this->children = array();

		foreach ($children as $child)
			$this->children[] = new SMTreeMenuItem($child->GetId(), $child->GetTitle(), $child->GetValue(), $this->id, "", $child->GetChildren(), $this->startCollapsed);
	}

	/// <function container="gui/SMTreeMenu" name="GetChildren" access="public" returns="SMTreeMenuItem[]">
	/// 	<description> Returns collection of items in tree menu </description>
	/// </function>
	public function GetChildren()
	{
		return $this->children;
	}

	/// <function container="gui/SMTreeMenu" name="GetChild" access="public" returns="SMTreeMenuItem">
	/// 	<description> Searches and returns item by its ID if found, otherwise Null </description>
	/// 	<param name="id" type="string"> ID of item to get </param>
	/// 	<param name="searchDeep" type="boolean" default="false">
	/// 		By default only the root of the tree menu is searched for an item
	/// 		with the given ID. Set this argument True to search recursively and
	/// 		return the item no matter its position in the hierarchy.
	/// 	</param>
	/// </function>
	public function GetChild($id, $searchDeep = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "searchDeep", $searchDeep, SMTypeCheckType::$Boolean);

		return $this->getChildInternal("id", $id, $searchDeep);
	}

	/// <function container="gui/SMTreeMenu" name="GetChildByValue" access="public" returns="SMTreeMenuItem">
	/// 	<description> Searches and returns item by its value if found, otherwise Null </description>
	/// 	<param name="val" type="string"> Value of item to get </param>
	/// 	<param name="searchDeep" type="boolean" default="false">
	/// 		By default only the root of the tree menu is searched for an item
	/// 		with the given value. Set this argument True to search recursively and
	/// 		return the item no matter its position in the hierarchy.
	/// 		First matching item is returned if multiple items have the same value.
	/// 	</param>
	/// </function>
	public function GetChildByValue($val, $searchDeep = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "searchDeep", $searchDeep, SMTypeCheckType::$Boolean);

		return $this->getChildInternal("value", $val, $searchDeep);
	}

	private function getChildInternal($searchBy, $search, $searchDeep = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "searchBy", $searchBy, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "searchDeep", $searchDeep, SMTypeCheckType::$Boolean);

		if ($searchDeep === false)
		{
			foreach ($this->children as $child)
				if (($searchBy === "id" && $child->GetId() === $search) || ($searchBy === "value" && $child->GetValue() === $search))
					return $child;
		}
		else
		{
			$result = null;

			foreach ($this->children as $child)
			{
				$result = $this->getChildDeep($searchBy, $child, $search);

				if ($result !== null)
					return $result;
			}
		}

		return null;
	}

	private function getChildDeep($searchBy, SMTreeMenuItem $parent, $search)
	{
		SMTypeCheck::CheckObject(__METHOD__, "searchBy", $searchBy, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);

		if (($searchBy === "id" && $parent->GetId() === $search) || ($searchBy === "value" && $parent->GetValue() === $search))
			return $parent;

		$children = $parent->GetChildren();

		if (count($children) === 0)
			return null;

		$result = null;

		foreach ($children as $child)
		{
			$result = $this->getChildDeep($searchBy, $child, $search);

			if ($result !== null)
				return $result;
		}

		return null;
	}

	/// <function container="gui/SMTreeMenu" name="SetAutoPostBack" access="public">
	/// 	<description>
	/// 		Set True to have tree memu post back automatically when selection is changed,
	/// 		False not to. Tree menu does not automatically post back by default.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable automatic post back, False not to </param>
	/// </function>
	public function SetAutoPostBack($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->autoPostBack = $value;
	}

	/// <function container="gui/SMTreeMenu" name="GetAutoPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if tree menu performs post back when selection is changed, False otherwise </description>
	/// </function>
	public function GetAutoPostBack()
	{
		return $this->autoPostBack;
	}

	/// <function container="gui/SMTreeMenu" name="SetCollapsed" access="public">
	/// 	<description> Determines whether all items containing children is initially collapsed (closed) or expanded (opened) </description>
	/// 	<param name="value" type="boolean"> True to initially collapse all nodes, False not to </param>
	/// </function>
	public function SetCollapsed($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->startCollapsed = $value;

		// Recreating children to change collapsed mode

		$tmp = array();

		foreach ($this->children as $child)
			$tmp[] = new SMTreeMenuItem($child->GetId(), $child->GetTitle(), $child->GetValue(), $this->id, "", $child->GetChildren(), $this->startCollapsed);

		$this->children = $tmp;
	}

	/// <function container="gui/SMTreeMenu" name="GetCollapsed" access="public" returns="boolean">
	/// 	<description> Returns True if all items containing children is initially collapsed (closed), False otherwise </description>
	/// </function>
	public function GetCollapsed()
	{
		return $this->startCollapsed;
	}

	/// <function container="gui/SMTreeMenu" name="SetRestoreState" access="public">
	/// 	<description> Determines whether tree menu will remember its state of collapsed (closed) and expanded (opened) items or not </description>
	/// 	<param name="value" type="boolean"> True to remember collapsed/expanded state of items containing children, False not to </param>
	/// </function>
	public function SetRestoreState($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->restoreState = $value;
	}

	/// <function container="gui/SMTreeMenu" name="GetRestoreState" access="public" returns="boolean">
	/// 	<description> Returns True if tree menu will remember its state of collapsed (closed) and expanded (opened) items, False otherwise </description>
	/// </function>
	public function GetRestoreState()
	{
		return $this->restoreState;
	}

	/// <function container="gui/SMTreeMenu" name="SetRestoreSelection" access="public">
	/// 	<description> Determines whether tree menu will restore selection after post back or not </description>
	/// 	<param name="value" type="boolean"> True to restore selection after post back, False not to </param>
	/// </function>
	public function SetRestoreSelection($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->restoreSelection = $value;
	}

	/// <function container="gui/SMTreeMenu" name="GetRestoreSelection" access="public" returns="boolean">
	/// 	<description> Returns True if tree menu will restore selection after post back, False otherwise </description>
	/// </function>
	public function GetRestoreSelection()
	{
		return $this->restoreSelection;
	}

	/// <function container="gui/SMTreeMenu" name="PerformedPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if tree menu performed post back, otherwise False </description>
	/// </function>
	public function PerformedPostBack()
	{
		return (SMEnvironment::GetPostValue("SMPostBackControl") === "SMTreeMenu" . $this->id);
	}

	/// <function container="gui/SMTreeMenu" name="GetSelectedId" access="public" returns="string">
	/// 	<description> Returns ID of selected item, Null if no selection was made </description>
	/// </function>
	public function GetSelectedId()
	{
		$item = $this->getSelectedItem();
		return (($item !== null) ? $item->GetId() : null);
	}

	/// <function container="gui/SMTreeMenu" name="GetSelectedValue" access="public" returns="string">
	/// 	<description> Returns value of selected item, Null if no selection was made </description>
	/// </function>
	public function GetSelectedValue()
	{
		$item = $this->getSelectedItem();
		return (($item !== null) ? $item->GetValue() : null);
	}

	/// <function container="gui/SMTreeMenu" name="SetSelected" access="public">
	/// 	<description> Initially select item with specified ID </description>
	/// 	<param name="id" type="string"> ID of item to select </param>
	/// </function>
	public function SetSelected($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		$this->selectedId = $id;
	}

	/// <function container="gui/SMTreeMenu" name="ResetSelected" access="public">
	/// 	<description> Reset selection so that no item is selected in tree menu </description>
	/// </function>
	public function ResetSelected()
	{
		$this->selectedId = null;
	}

	/// <function container="gui/SMTreeMenu" name="GetSelectionMade" access="public" returns="boolean">
	/// 	<description> Returns True if an item has been selected, False otherwise </description>
	/// </function>
	public function GetSelectionMade()
	{
		return ($this->selectedId !== null);
	}

	/// <function container="gui/SMTreeMenu" name="SetRender" access="public">
	/// 	<description> Set value indicating whether to render tree menu or not </description>
	/// 	<param name="value" type="boolean"> Set True to have tree menu rendered, False not to </param>
	/// </function>
	public function SetRender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->doRender = $value;
	}

	/// <function container="gui/SMTreeMenu" name="GetRender" access="public" returns="boolean">
	/// 	<description> Returns True if tree menu is to be rendered, False otherwise </description>
	/// </function>
	public function GetRender()
	{
		return $this->doRender;
	}

	/// <function container="gui/SMTreeMenu" name="Render" access="public" returns="string">
	/// 	<description> Returns HTML representation of tree menu </description>
	/// </function>
	public function Render()
	{
		if ($this->doRender === false)
			return "";

		$output = "";

		$output .= "
		<input id=\"SMTreeMenuItemId" . $this->id . "\" type=\"hidden\" name=\"SMTreeMenuItemId" . $this->id . "\">

		<script type=\"text/javascript\">
		var smTreeMenuAutoPostBack" . $this->id . " = " . (($this->autoPostBack === true) ? "true" : "false") . ";
		var smTreeMenuSaveState" . $this->id . " = " . (($this->restoreState === true) ? "true" : "false") . ";
		var smTreeMenuLastSelectionId" . $this->id . " = '';

		if (smTreeMenuSaveState" . $this->id . " === true)
			SMEventHandler.AddEventHandler(window, 'load', smTreeMenuRestoreState" . $this->id . ");

		SMEventHandler.AddEventHandler(window, 'load', smTreeMenuRestoreSelection" . $this->id . ");

		function smTreeMenuToggle" . $this->id . "(id, saveState)
		{
			var smMenuHidden = (SMDom.GetStyle('SMTreeMenuChildren' + id, 'display') === 'none') ? true : false;
			var smMenuSrc = ((smMenuHidden === true) ? 'expanded.gif' : 'collapsed.gif');
			SMDom.SetAttribute('SMTreeMenuItemImg' + id, 'src', 'base/gui/SMTreeMenu/images/' + smMenuSrc);

			if (smMenuHidden === true)
			{
				SMDom.SetStyle('SMTreeMenuChildren' + id, 'display', 'block');

				if (saveState === true)
					smTreeMenuSaveMenuState" . $this->id . "(id, false);
			}
			else
			{
				SMDom.SetStyle('SMTreeMenuChildren' + id, 'display', 'none');

				if (saveState === true)
					smTreeMenuSaveMenuState" . $this->id . "(id, true);
			}
		}

		function smTreeMenuSaveMenuState" . $this->id . "(id, hide)
		{
			var cookieValue = SMCookie.GetCookie('SMTreeMenu" . (($this->startCollapsed === false) ? "Collapsed" : "Expanded") . $this->id . "');
			var value = (cookieValue !== null) ? cookieValue : '';

			if (hide === " . (($this->startCollapsed === false) ? "true" : "false"). ")
			{
				if (value !== '')
					value = value + id + '|';
				else
					value = '|' + id + '|';
			}
			else
			{
				value = value.replace('|' + id + '|', '|');
			}

			if (value !== '|')
				SMCookie.SetCookie('SMTreeMenu" . (($this->startCollapsed === false) ? "Collapsed" : "Expanded") . $this->id . "', value, 365 * 24 * 60 * 60);
			else
				SMCookie.RemoveCookie('SMTreeMenu" . (($this->startCollapsed === false) ? "Collapsed" : "Expanded") . $this->id . "');
		}

		function smTreeMenuRestoreState" . $this->id . "()
		{
			var cookieValue = SMCookie.GetCookie('SMTreeMenu" . (($this->startCollapsed === false) ? "Collapsed" : "Expanded") . $this->id . "');
			var menuItems = (cookieValue !== null) ? cookieValue.substring(1, cookieValue.length - 1).split('|') : new Array();

			var cleanMenuStateCookie = false;

			for (i = 0 ; i < menuItems.length ; i++)
			{
				if (SMDom.ElementExists('SMTreeMenuChildren' + menuItems[i]) === false)
				{
					cleanMenuStateCookie = true;
					continue;
				}

				smTreeMenuToggle" . $this->id . "(menuItems[i], false);
			}

			if (cleanMenuStateCookie === true)
				smTreeMenuCleanMenuState" . $this->id . "();
		}

		function smTreeMenuCleanMenuState" . $this->id . "()
		{
			var cookieValue = SMCookie.GetCookie('SMTreeMenu" . (($this->startCollapsed === false) ? "Collapsed" : "Expanded") . $this->id . "');
			var menuItems = (cookieValue !== null) ? cookieValue.substring(1, cookieValue.length - 1).split('|') : new Array();

			for (i = 0 ; i < menuItems.length ; i++)
			{
				if (SMDom.ElementExists('SMTreeMenuChildren' + menuItems[i]) === false)
					cookieValue = cookieValue.replace('|' + menuItems[i] + '|', '|');
			}

			if (cookieValue !== '|')
				SMCookie.SetCookie('SMTreeMenu" . (($this->startCollapsed === false) ? "Collapsed" : "Expanded") . $this->id . "', cookieValue, 365 * 24 * 60 * 60);
			else
				SMCookie.RemoveCookie('SMTreeMenu" . (($this->startCollapsed === false) ? "Collapsed" : "Expanded") . $this->id . "');
		}

		function smTreeMenuRestoreSelection" . $this->id . "()
		{
			if (" . (($this->restoreSelection === true && $this->selectedId !== null && $this->GetSelectedValue() !== null) ? "true" : "false") . ")
			{
				var id = '" . $this->selectedId . "';
				smTreeMenuClientSelection" . $this->id . "(id, true);
			}
		}

		function smTreeMenuClientSelection" . $this->id . "(id, ignoreAutoPostBack)
		{
			if (smTreeMenuAutoPostBack" . $this->id . " === true && ignoreAutoPostBack === false)
				SMDom.SetAttribute('SMPostBackControl', 'value', 'SMTreeMenu" . $this->id . "');

			SMDom.SetAttribute('SMTreeMenuItemId" . $this->id . "', 'value', id);

			if (smTreeMenuLastSelectionId" . $this->id . " !== '')
				SMDom.SetStyle('SMTreeMenuItemSelectedImg' + smTreeMenuLastSelectionId" . $this->id . ", 'display', 'none');

			SMDom.SetStyle('SMTreeMenuItemMouseOverImg' + id, 'display', 'none');
			SMDom.SetStyle('SMTreeMenuItemSelectedImg' + id, 'display', 'inline');

			smTreeMenuLastSelectionId" . $this->id . " = id;

			if (smTreeMenuAutoPostBack" . $this->id . " === true && ignoreAutoPostBack === false)
				smFormPostBack();
		}

		function smTreeMenuHoverItem" . $this->id . "(over, itemId)
		{
			if (smTreeMenuLastSelectionId" . $this->id . " !== itemId)
				SMDom.SetStyle('SMTreeMenuItemMouseOverImg' + itemId, 'display', ((over === true) ? 'inline' : 'none'));
		}
		</script>";

		foreach ($this->children as $child)
			$output .= $child->Render();

		return "<div class=\"SMTreeMenu\">" . $output . "</div>";
	}

	private function getSelectedItem()
	{
		if ($this->selectedId === null)
			return null;

		$item = $this->GetChild($this->selectedId, true);

		return $item;
	}
}

/// <container name="gui/SMTreeMenuItem">
/// 	Class represents an item within a tree menu. See gui/SMTreeMenu for an example of how to use it.
/// </container>
class SMTreeMenuItem
{
	private $menuId;				// String
	private $parentId;				// String
	private $children;				// SMMMenItem[]
	private $id;					// String
	private $title;					// String
	private $value;					// String
	private $selectable;			// Bool
	private $startCollapsed;		// Bool

	/// <function container="gui/SMTreeMenuItem" name="__construct" access="public">
	/// 	<description> Create instance of SMTreeMenuItem </description>
	/// 	<param name="itemId" type="string"> Unique ID identifying tree menu item </param>
	/// 	<param name="title" type="string"> Item display title </param>
	/// 	<param name="value" type="string"> Item value </param>
	/// </function>
	public function __construct($itemId, $title, $value, $menuId = "", $parentId = "", $children = array(), $startCollapsed = false)
	{
		// Note: $menuId and $itemId MUST be the same on every page load,
		// as they are used to restore the menu state (expanded/collapsed)

		SMTypeCheck::CheckObject(__METHOD__, "itemId", $itemId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "menuId", $menuId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "parentId", $parentId, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "children", $children, "SMTreeMenuItem");
		SMTypeCheck::CheckObject(__METHOD__, "startCollapsed", $startCollapsed, SMTypeCheckType::$Boolean);

		$this->menuId = $menuId;
		$this->parentId = $parentId;
		$this->children = $children;
		$this->id = $itemId;
		$this->title = $title;
		$this->value = $value;
		$this->selectable = true;
		$this->startCollapsed = $startCollapsed;

		// Recreating children in order to assign menu ID and collapsed mode

		$child = null;
		$newChild = null;

		for ($i = 0 ; $i < count($this->children) ; $i++)
		{
			$child = $this->children[$i];
			$newChild = new SMTreeMenuItem($child->GetId(), $child->GetTitle(), $child->GetValue(), $this->menuId, $child->GetParentId(), $child->GetChildren(), $this->startCollapsed);
			$newChild->SetSelectable($child->GetSelectable());

			$this->children[$i] = $newChild;
		}
	}

	/// <function container="gui/SMTreeMenuItem" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMTreeMenuItem" name="GetParentId" access="public" returns="string">
	/// 	<description> Get ID of parent component </description>
	/// </function>
	public function GetParentId()
	{
		return $this->parentId;
	}

	/// <function container="gui/SMTreeMenuItem" name="SetTitle" access="public">
	/// 	<description> Set item title </description>
	/// 	<param name="value" type="string"> Item title </param>
	/// </function>
	public function SetTitle($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->title = $value;
	}

	/// <function container="gui/SMTreeMenuItem" name="GetTitle" access="public" returns="string">
	/// 	<description> Returns item title </description>
	/// </function>
	public function GetTitle()
	{
		return $this->title;
	}

	/// <function container="gui/SMTreeMenuItem" name="SetValue" access="public">
	/// 	<description> Set item value </description>
	/// 	<param name="value" type="string"> Item value </param>
	/// </function>
	public function SetVale($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->value = $value;
	}

	/// <function container="gui/SMTreeMenuItem" name="GetValue" access="public" returns="string">
	/// 	<description> Returns item value </description>
	/// </function>
	public function GetValue()
	{
		return $this->value;
	}

	/// <function container="gui/SMTreeMenuItem" name="AddChild" access="public">
	/// 	<description> Add child item </description>
	/// 	<param name="child" type="SMTreeMenuItem"> Instance of SMTreeMenuItem </param>
	/// </function>
	public function AddChild(SMTreeMenuItem $child)
	{
		$newChild = new SMTreeMenuItem($child->GetId(), $child->GetTitle(), $child->GetValue(), $this->menuId, $this->id, $child->GetChildren(), $this->startCollapsed);
		$newChild->SetSelectable($child->GetSelectable());
		$this->children[] = $newChild;
	}

	/// <function container="gui/SMTreeMenuItem" name="RemoveChild" access="public" returns="boolean">
	/// 	<description> Remove child item by ID. Returns True on success, otherwise False. </description>
	/// 	<param name="id" type="string"> ID of child item to remove </param>
	/// </function>
	public function RemoveChild($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$tmp = array();

		foreach ($this->children as $child)
			if ($child->GetId() !== $id)
				$tmp[] = $child;

		$result = (count($tmp) < count($this->children));
		$this->children = $tmp;

		return $result;
	}

	/// <function container="gui/SMTreeMenuItem" name="GetChildren" access="public" returns="SMTreeMenuItem[]">
	/// 	<description> Returns collection of children </description>
	/// </function>
	public function GetChildren()
	{
		return $this->children;
	}

	/// <function container="gui/SMTreeMenuItem" name="GetChild" access="public" returns="SMTreeMenuItem">
	/// 	<description> Searches and returns item by its ID if found, otherwise Null </description>
	/// 	<param name="id" type="string"> ID of item to get </param>
	/// </function>
	public function GetChild($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		foreach ($this->children as $child)
			if ($child->GetId() === $id)
				return $child;

		return null;
	}

	/// <function container="gui/SMTreeMenuItem" name="SetSelectable" access="public">
	/// 	<description> Determines whether item is selectable or not </description>
	/// 	<param name="value" type="boolean"> True to make item selectable, False not to </param>
	/// </function>
	public function SetSelectable($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->selectable = $value;
	}

	/// <function container="gui/SMTreeMenuItem" name="GetSelectable" access="public" returns="boolean">
	/// 	<description> Returns True if item is selectable, False otherwise </description>
	/// </function>
	public function GetSelectable()
	{
		return $this->selectable;
	}

	public function Render()
	{
		$childrenAvailable = (count($this->children) > 0);
		$imagePath = "base/gui/SMTreeMenu/images/";
		$toggle = (($childrenAvailable === true) ? " onclick=\"smTreeMenuToggle" . $this->menuId . "('" . $this->id . "', smTreeMenuSaveState" . $this->menuId. ")\"" : "");
		$click = (($this->selectable === true) ? "smTreeMenuClientSelection" . $this->menuId . "('" . $this->id . "', false)" : "");
		$mouseOver = (($this->selectable === true) ? "smTreeMenuHoverItem" . $this->menuId . "(true, '" . $this->id . "')" : "");
		$mouseOut = (($this->selectable === true) ? "smTreeMenuHoverItem" . $this->menuId . "(false, '" . $this->id . "')" : "");
		$style = (($this->selectable === false) ? "text-decoration: line-through" : "cursor: pointer");
		$icon = "";

		if ($childrenAvailable === true && $this->startCollapsed === false)
			$icon = "expanded.gif";
		else if ($childrenAvailable === true && $this->startCollapsed === true)
			$icon = "collapsed.gif";
		else if ($childrenAvailable === false)
			$icon = "point.gif";

		$output = "
		<div>
			<img id=\"SMTreeMenuItemImg" . $this->id . "\" src=\"" . $imagePath . $icon . "\"" . $toggle . " alt=\"\" style=\"padding-right: 3px; vertical-align: middle;\">
			<span id=\"SMTreeMenuItem" . $this->id . "\" onclick=\"" . $click . "\" onmouseover=\"" . $mouseOver . "\" onmouseout=\"" . $mouseOut . "\" style=\"" . $style . "\">" . SMStringUtilities::HtmlEncode($this->title) . "</span>
			<img id=\"SMTreeMenuItemMouseOverImg" . $this->id . "\" src=\"" . $imagePath . "selection.gif\" alt=\"\" style=\"vertical-align: middle; display: none\">
			<img id=\"SMTreeMenuItemSelectedImg" . $this->id . "\" src=\"" . $imagePath . "selected.gif\" alt=\"\" style=\"vertical-align: middle; display: none\">
		</div>
		";

		if ($childrenAvailable === true)
		{
			$output .= "<div id=\"SMTreeMenuChildren" . $this->id . "\" style=\"margin-left: 15px; display: " . (($this->startCollapsed === false) ? 'block' : 'none') . "\">";

			foreach ($this->children as $child)
				$output .= $child->Render();

			$output .= "</div>";
		}

		return $output;
	}
}

?>
