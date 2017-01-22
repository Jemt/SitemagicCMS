<?php

/// <container name="gui/SMCheckboxListItem">
/// 	Class represents an item within a check box list.
///
/// 	$summer = new SMCheckboxListItem(&quot;MyExtensionSummer&quot;, &quot;Summer&quot;, &quot;On vaccation in the summer&quot;);
/// 	$winter = new SMCheckboxListItem(&quot;MyExtensionWinter&quot;, &quot;Winter&quot;, &quot;On vaccation in the winter&quot;);
/// </container>
class SMCheckboxListItem
{
	private $id;				// string
	private $value;				// string
	private $label;				// string
	private $description;		// string

	private $listId;			// string
	private $selected;			// bool
	private $labelStyle;		// string
	private $descriptionStyle;	// string

	/// <function container="gui/SMCheckboxListItem" name="__construct" access="public">
	/// 	<description> Create instance of SMCheckboxListItem </description>
	/// 	<param name="id" type="string"> Unique ID identifying item </param>
	/// 	<param name="value" type="string"> Value posted back from checkbox list </param>
	/// 	<param name="label" type="string"> Label </param>
	/// 	<param name="desc" type="string" default="String.Empty"> Optionally specify a description </param>
	/// </function>
	public function __construct($id, $value, $label, $desc = "", $listId = "", $selected = false, $labelStyle = "", $descStyle = "")
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "label", $label, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "desc", $desc, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "listId", $listId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "selected", $selected, SMTypeCheckType::$Boolean);
		SMTypeCheck::CheckObject(__METHOD__, "labelStyle", $labelStyle, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "descStyle", $descStyle, SMTypeCheckType::$String);

		$this->id = $id;
		$this->value = $value;
		$this->label = $label;
		$this->description = $desc;

		$this->listId = $listId;
		$this->selected = $selected;
		$this->labelStyle = $labelStyle;
		$this->descriptionStyle = $descStyle;
	}

	/// <function container="gui/SMCheckboxListItem" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMCheckboxListItem" name="GetClientId" access="public" returns="string">
	/// 	<description> Get component client ID </description>
	/// </function>
	public function GetClientId()
	{
		return "SMCheckboxListItem" . $this->id;
	}

	/// <function container="gui/SMCheckboxListItem" name="GetValue" access="public" returns="string">
	/// 	<description> Get value </description>
	/// </function>
	public function GetValue()
	{
		return $this->value;
	}

	/// <function container="gui/SMCheckboxListItem" name="SetValue" access="public">
	/// 	<description> Set value posted back from checkbox list </description>
	/// 	<param name="value" type="string"> Value </param>
	/// </function>
	public function SetValue($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->Value = $value;
	}

	/// <function container="gui/SMCheckboxListItem" name="GetLabel" access="public" returns="string">
	/// 	<description> Get label </description>
	/// </function>
	public function GetLabel()
	{
		return $this->label;
	}

	/// <function container="gui/SMCheckboxListItem" name="SetLabel" access="public">
	/// 	<description> Set label </description>
	/// 	<param name="value" type="string"> Label </param>
	/// </function>
	public function SetLabel($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->label = $value;
	}

	/// <function container="gui/SMCheckboxListItem" name="GetDescription" access="public" returns="string">
	/// 	<description> Get description </description>
	/// </function>
	public function GetDescription()
	{
		return $this->description;
	}

	/// <function container="gui/SMCheckboxListItem" name="SetDescription" access="public">
	/// 	<description> Set description </description>
	/// 	<param name="value" type="string"> Description </param>
	/// </function>
	public function SetDescription($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->description = $value;
	}

	public function Render()
	{
		$output = "";

		$output .= "<div style=\"width: 25px; height: 1px; float: left;\"><input type=\"checkbox\" id=\"" . $this->GetClientId() . "\" name=\"" . $this->listId . "[]\" value=\"" . $this->value . "\"" . (($this->selected === true) ? " checked=\"checked\"" : "") . "></div>";
		$output .= "<div onclick=\"SMDom.SetAttribute('" . $this->GetClientId() . "', 'checked', ((SMDom.GetAttribute('" . $this->GetClientId() . "', 'checked') === 'true') ? 'false' : 'true'))\" style=\"cursor: pointer;" . $this->labelStyle . "\">" . $this->label . "</div>";
		$output .= "<div style=\"margin-left: 25px;" . $this->descriptionStyle . "\">" . $this->description . "</div>";

		return $output;
	}
}

/// <container name="gui/SMCheckboxList">
/// 	Class represents a list of check box items.
///
/// 	$list = new SMCheckboxList(&quot;MyExtensionVaccation&quot;);
/// 	$list->AddItem(new SMCheckboxListItem(&quot;MyExtensionSummer&quot;, &quot;Summer&quot;, &quot;On vaccation in the summer&quot;));
/// 	$list->AddItem(new SMCheckboxListItem(&quot;MyExtensionWinter&quot;, &quot;Winter&quot;, &quot;On vaccation in the winter&quot;));
///
/// 	// Get value after post back
/// 	$items = $list->GetSelectedValue(); // May contain &quot;Summer&quot;, &quot;Winter&quot;, or &quot;Summer;Winter&quot;
///
/// 	// Get checkbox list as HTML
/// 	$html = $list->Render();
/// </container>
class SMCheckboxList
{
	private $id;				// string
	private $clientId;			// string
	private $items;				// SMCheckboxListItem[]
	private $selectedValues;	// string[]
	private $labelStyle;		// string
	private $descriptionStyle;	// string
	private $doRender;			// bool

	/// <function container="gui/SMCheckboxList" name="__construct" access="public">
	/// 	<description> Create instance of SMCheckboxList </description>
	/// 	<param name="id" type="string"> Unique ID identifying checkbox list </param>
	/// </function>
	public function __construct($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$this->id = $id;
		$this->clientId = "SMCheckboxList" . $id;
		$this->items = array();
		$this->selectedValues = SMEnvironment::GetPostValue($this->clientId);
		$this->labelStyle = "";
		$this->descriptionStyle = "";
		$this->doRender = true;

		// Remove last item - it is always selected and serves only to ensure
		// that the array is always set on post back. See Render() for explaination.
		if ($this->selectedValues !== null)
			unset($this->selectedValues[count($this->selectedValues) - 1]);
	}

	/// <function container="gui/SMCheckboxList" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMCheckboxList" name="AddItem" access="public">
	/// 	<description> Add checkbox item to checkbox list </description>
	/// 	<param name="item" type="SMCheckboxListItem"> Item </param>
	/// </function>
	public function AddItem(SMCheckboxListItem $item)
	{
		$this->items[] = $item;
	}

	/// <function container="gui/SMCheckboxList" name="RemoveItem" access="public">
	/// 	<description> Remove checkbox item using its unique ID </description>
	/// 	<param name="id" type="string"> Item ID </param>
	/// </function>
	public function RemoveItem($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$tmp = array();

		foreach ($this->items as $item)
			if ($item->GetId() !== $id)
				$tmp[] = $item;

		$this->items = $tmp;
	}

	/// <function container="gui/SMCheckboxList" name="SetItems" access="public">
	/// 	<description> Replace internal collection of checkbox items </description>
	/// 	<param name="items" type="SMCheckboxListItem[]"> Array of checkbox items </param>
	/// </function>
	public function SetItems($items)
	{
		SMTypeCheck::CheckArray(__METHOD__, "items", $items, "SMCheckboxListItem");
		$this->items = $items;
	}

	/// <function container="gui/SMCheckboxList" name="GetItems" access="public" returns="SMCheckboxListItem[]">
	/// 	<description> Get items added to checkbox list </description>
	/// </function>
	public function GetItems()
	{
		return $this->items;
	}

	/// <function container="gui/SMCheckboxList" name="GetItem" access="public" returns="SMCheckboxListItem">
	/// 	<description> Get checkbox item by its unique ID - returns Null if not found </description>
	/// 	<param name="id" type="string"> Item ID </param>
	/// </function>
	public function GetItem($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		foreach ($this->items as $item)
			if ($item->GetId() === $id)
				return $item;

		return null;
	}

	/// <function container="gui/SMCheckboxList" name="GetItemByValue" access="public" returns="SMCheckboxListItem">
	/// 	<description> Get checkbox item by its value - returns Null if not found </description>
	/// 	<param name="val" type="string"> Item value </param>
	/// </function>
	public function GetItemByValue($val)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$String);

		foreach ($this->items as $item)
			if ($item->GetValue() === $val)
				return $item;

		return null;
	}

	/// <function container="gui/SMCheckboxList" name="SetSelectedValue" access="public">
	/// 	<description>
	/// 		Initially select one or more items by their values.
	/// 		Multiple items are selected by specifing their values separated by semi colon.
	/// 	</description>
	/// 	<param name="value" type="string"> Item value(s) </param>
	/// </function>
	public function SetSelectedValue($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->selectedValues = SMStringUtilities::SplitBySemicolon($value); // Preserves HEX/HTML entities
	}

	/// <function container="gui/SMCheckboxList" name="ResetSelectedValue" access="public">
	/// 	<description> Reset any selections made </description>
	/// </function>
	public function ResetSelectedValue()
	{
		$this->selectedValues = null;
	}

	/// <function container="gui/SMCheckboxList" name="GetSelectionMade" access="public" returns="boolean">
	/// 	<description> Returns True if one or more selections were made, otherwise False </description>
	/// </function>
	public function GetSelectionMade()
	{
		return ($this->selectedValues !== null && count($this->selectedValues) > 0);
	}

	/// <function container="gui/SMCheckboxList" name="GetSelectedValue" access="public" returns="string">
	/// 	<description>
	/// 		Get value(s) selected - multiple values are separated by semi colon.
	/// 		Returns Null if post back has not yet occured.
	/// 	</description>
	/// </function>
	public function GetSelectedValue()
	{
		if ($this->selectedValues !== null)
		{
			$selectedValues = array();

			foreach ($this->selectedValues as $selectedValue)
				if ($this->GetItemByValue($selectedValue) !== null)
					$selectedValues[] = $selectedValue;

			if (count($selectedValues) === 0)
				return null;

			return implode(";", $selectedValues);
		}
		else
		{
			return null;
		}
	}

	/// <function container="gui/SMCheckboxList" name="SetLabelStyle" access="public">
	/// 	<description> Set CSS styles used to display labels </description>
	/// 	<param name="value" type="string"> Label style (CSS) </param>
	/// </function>
	public function SetLabelStyle($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->labelStyle = $value;
	}

	/// <function container="gui/SMCheckboxList" name="GetLabelStyle" access="public" returns="string">
	/// 	<description> Get CSS styles used to display labels </description>
	/// </function>
	public function GetLabelStyle()
	{
		return $this->labelStyle;
	}

	/// <function container="gui/SMCheckboxList" name="SetDescriptionStyle" access="public">
	/// 	<description> Set CSS styles used to display descriptions </description>
	/// 	<param name="value" type="string"> Description style (CSS) </param>
	/// </function>
	public function SetDescriptionStyle($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->descriptionStyle = $value;
	}

	/// <function container="gui/SMCheckboxList" name="GetDescriptionStyle" access="public" returns="string">
	/// 	<description> Get CSS styles used to display descriptions </description>
	/// </function>
	public function GetDescriptionStyle()
	{
		return $this->descriptionStyle;
	}

	/// <function container="gui/SMCheckboxList" name="SetRender" access="public">
	/// 	<description> Enable or disable rendering of checkbox list </description>
	/// 	<param name="value" type="boolean"> True to have checkbox list rendered, False not to </param>
	/// </function>
	public function SetRender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->doRender = $value;
	}

	/// <function container="gui/SMCheckboxList" name="GetRender" access="public" returns="boolean">
	/// 	<description> Returns True if checkbox list is to be rendered, otherwise False </description>
	/// </function>
	public function GetRender()
	{
		return $this->doRender;
	}

	/// <function container="gui/SMCheckboxList" name="Render" access="public" returns="string">
	/// 	<description> Returns HTML representation of checkbox list </description>
	/// </function>
	public function Render()
	{
		if ($this->doRender === false)
			return "";

		$output = "";

		$item = null;
		for ($i = 0 ; $i < count($this->items) ; $i++)
		{
			$item = $this->items[$i];

			$item = new SMCheckboxListItem($item->GetId(), $item->GetValue(), $item->GetLabel(), $item->GetDescription(), $this->clientId, ($this->selectedValues !== null && in_array($item->GetValue(), $this->selectedValues, true) === true), $this->labelStyle, $this->descriptionStyle);
			$this->items[$i] = $item;

			$output .= $item->Render();
		}

		// Add hidden element that is always checked, in order to ensure that checkbox list array
		// always exists on post back. No checked items results in array not being available server side.
		$output .= "<input type=\"checkbox\" id=\"" . $this->clientId . "EnsureArray\" name=\"" . $this->clientId . "[]\" value=\"EnsureArrayServerSide\" checked=\"checked\" style=\"display: none;\">";

		return "<div class=\"SMCheckboxList\">" . $output . "</div>";
	}
}

?>
