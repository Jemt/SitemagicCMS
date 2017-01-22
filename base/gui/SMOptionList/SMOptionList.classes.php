<?php

/// <container name="gui/SMOptionListAttribute">
/// 	Enum representing an attribute on an option list
/// </container>
class SMOptionListAttribute
{
	/// <member container="gui/SMOptionListAttribute" name="Value" access="public" static="true" type="string" default="Value" />
	public static $Value = "Value";
	/// <member container="gui/SMOptionListAttribute" name="Disabled" access="public" static="true" type="string" default="Disabled" />
	public static $Disabled = "Disabled";
	/// <member container="gui/SMOptionListAttribute" name="Title" access="public" static="true" type="string" default="Title" />
	public static $Title = "Title";
	/// <member container="gui/SMOptionListAttribute" name="Style" access="public" static="true" type="string" default="Style" />
	public static $Style = "Style";
	/// <member container="gui/SMOptionListAttribute" name="Class" access="public" static="true" type="string" default="Class" />
	public static $Class = "Class";
	/// <member container="gui/SMOptionListAttribute" name="AccessKey" access="public" static="true" type="string" default="AccessKey" />
	public static $AccessKey = "AccessKey";
	/// <member container="gui/SMOptionListAttribute" name="TabIndex" access="public" static="true" type="string" default="TabIndex" />
	public static $TabIndex = "TabIndex";
	/// <member container="gui/SMOptionListAttribute" name="Multiple" access="public" static="true" type="string" default="Multiple" />
	public static $Multiple = "Multiple";

	/// <member container="gui/SMOptionListAttribute" name="OnFocus" access="public" static="true" type="string" default="Onfocus" />
	public static $OnFocus = "OnFocus";
	/// <member container="gui/SMOptionListAttribute" name="OnBlur" access="public" static="true" type="string" default="OnBlur" />
	public static $OnBlur = "OnBlur";
	/// <member container="gui/SMOptionListAttribute" name="OnChange" access="public" static="true" type="string" default="OnChange" />
	public static $OnChange = "OnChange";
	/// <member container="gui/SMOptionListAttribute" name="OnClick" access="public" static="true" type="string" default="OnClick" />
	public static $OnClick = "OnClick";
	/// <member container="gui/SMOptionListAttribute" name="OnDblClick" access="public" static="true" type="string" default="OnDblClick" />
	public static $OnDblClick = "OnDblClick";
	/// <member container="gui/SMOptionListAttribute" name="OnMouseDown" access="public" static="true" type="string" default="OnMouseDown" />
	public static $OnMouseDown = "OnMouseDown";
	/// <member container="gui/SMOptionListAttribute" name="OnMouseUp" access="public" static="true" type="string" default="OnMouseUp" />
	public static $OnMouseUp = "OnMouseUp";
	/// <member container="gui/SMOptionListAttribute" name="OnMouseOver" access="public" static="true" type="string" default="OnMouseOver" />
	public static $OnMouseOver = "OnMouseOver";
	/// <member container="gui/SMOptionListAttribute" name="OnMouseMove" access="public" static="true" type="string" default="OnMouseMove" />
	public static $OnMouseMove = "OnMouseMove";
	/// <member container="gui/SMOptionListAttribute" name="OnMouseOut" access="public" static="true" type="string" default="OnMouseOut" />
	public static $OnMouseOut = "OnMouseOut";
	/// <member container="gui/SMOptionListAttribute" name="OnKeyPress" access="public" static="true" type="string" default="OnKeyPress" />
	public static $OnKeyPress = "OnKeyPress";
	/// <member container="gui/SMOptionListAttribute" name="OnKeyDown" access="public" static="true" type="string" default="OnKeyDown" />
	public static $OnKeyDown = "OnKeyDown";
	/// <member container="gui/SMOptionListAttribute" name="OnKeyUp" access="public" static="true" type="string" default="OnKeyUp" />
	public static $OnKeyUp = "OnKeyUp";
}

/// <container name="gui/SMOptionListItem">
/// 	Class represents an item within an option list. See gui/SMOptionList for an example of how to use it.
/// </container>
class SMOptionListItem
{
	private $id;
	private $title;
	private $value;
	private $selected;

	/// <function container="gui/SMOptionListItem" name="__construct" access="public">
	/// 	<description> Create instance of SMOptionListItem </description>
	/// 	<param name="id" type="string"> Unique ID identifying option list item </param>
	/// 	<param name="title" type="string"> Item display title </param>
	/// 	<param name="value" type="string"> Item value </param>
	/// </function>
	public function __construct($id, $title, $value, $selected = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "selected", $selected, SMTypeCheckType::$Boolean);

		$this->id = $id;
		$this->title = $title;
		$this->value = $value;
		$this->selected = $selected;
	}

	/// <function container="gui/SMOptionListItem" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMOptionListItem" name="GetClientId" access="public" returns="string">
	/// 	<description> Get component client ID </description>
	/// </function>
	public function GetClientId()
	{
		return "SMOptionListItem" . $this->id;
	}

	/// <function container="gui/SMOptionListItem" name="SetTitle" access="public">
	/// 	<description> Set item title </description>
	/// 	<param name="value" type="string"> Item title </param>
	/// </function>
	public function SetTitle($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->title = $value;
	}

	/// <function container="gui/SMOptionListItem" name="GetTitle" access="public" returns="string">
	/// 	<description> Returns item title </description>
	/// </function>
	public function GetTitle()
	{
		return $this->title;
	}

	/// <function container="gui/SMOptionListItem" name="SetValue" access="public">
	/// 	<description> Set item value </description>
	/// 	<param name="value" type="string"> Item value </param>
	/// </function>
	public function SetValue($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->value = $value;
	}

	/// <function container="gui/SMOptionListItem" name="GetValue" access="public" returns="string">
	/// 	<description> Returns item value </description>
	/// </function>
	public function GetValue()
	{
		return $this->value;
	}

	public function Render()
	{
		$selected = (($this->selected === true) ? " selected=\"selected\"" : "");
		return "<option id=\"" . $this->GetClientId() . "\" value=\"" . SMStringUtilities::HtmlEncode($this->value) . "\"" . $selected . ">" . (($this->title !== "") ? SMStringUtilities::HtmlEncode($this->title) : "&nbsp;") . "</option>";
	}
}

/// <container name="gui/SMOptionList">
/// 	Class represents an HTML option list (commonly rendered as a drop down menu).
///
/// 	// Construct option list (single selection)
///
/// 	$cbxOs = new SMOptionList(&quot;MyExtensionOs&quot;);
/// 	$cbxOs-&gt;AddItem(new SMOptionListItem(&quot;MyExtensionOsLinux&quot;, &quot;Linux&quot;, &quot;Linux&quot;));
/// 	$cbxOs-&gt;AddItem(new SMOptionListItem(&quot;MyExtensionOsOsX&quot;, &quot;Mac OSX&quot;, &quot;Osx&quot;));
/// 	$cbxOs-&gt;AddItem(new SMOptionListItem(&quot;MyExtensionOsWindows&quot;, &quot;Windows&quot;, &quot;Win&quot;));
/// 	$cbxOs-&gt;AddItem(new SMOptionListItem(&quot;MyExtensionOsEmpty&quot;, &quot;&quot;, &quot;Empty&quot;));
/// 	$cbxOs-&gt;SetSelectedValue(&quot;Empty&quot;);
/// 	$cbxOs-&gt;SetAutoPostBack(true);
///
/// 	// Construct option list (multi selection) based on selection from option list created above
///
/// 	$cbxOsVersions = new SMOptionList(&quot;MyExtensionOsVer&quot;);
/// 	$cbxOsVersions-&gt;SetAttribute(SMOptionListAttribute::$Multiple, "multiple");
/// 	$cbxOsVersions-&gt;AddItem(new SMOptionListItem(&quot;MyExtensionOsVerOther&quot;, &quot;Other&quot;, &quot;Other&quot;));
///
/// 	if ($cbxOs-&gt;PerformedPostBack() === true &amp;&amp; $cbxOs-&gt;GetSelectedValue() === &quot;Linux&quot;)
/// 	{
/// 		&#160;&#160;&#160;&#160; $cbxOsVersions-&gt;AddOption(new SMOptionListItem(&quot;MyExtensionOsVer24&quot;, &quot;Linux 2.4&quot;, &quot;24&quot;));
/// 		&#160;&#160;&#160;&#160; $cbxOsVersions-&gt;AddOption(new SMOptionListItem(&quot;MyExtensionOsVer26&quot;, &quot;Linux 2.6&quot;, &quot;26&quot;));
/// 		&#160;&#160;&#160;&#160; $cbxOsVersions-&gt;AddOption(new SMOptionListItem(&quot;MyExtensionOsVer30&quot;, &quot;Linux 3.0&quot;, &quot;30&quot;));
/// 	}
/// 	else if ($cbxOs-&gt;PerformedPostBack() === true &amp;&amp; $cbxOs-&gt;GetSelectedValue() === &quot;Win&quot;)
/// 	{
/// 		&#160;&#160;&#160;&#160; $cbxOsVersions-&gt;AddOption(new SMOptionListItem(&quot;MyExtensionOsVerXP&quot;, &quot;Win XP&quot;, &quot;XP&quot;));
/// 		&#160;&#160;&#160;&#160; $cbxOsVersions-&gt;AddOption(new SMOptionListItem(&quot;MyExtensionOsVer7&quot;, &quot;Win 7&quot;, &quot;7&quot;));
/// 		&#160;&#160;&#160;&#160; $cbxOsVersions-&gt;AddOption(new SMOptionListItem(&quot;MyExtensionOsVer8&quot;, &quot;Win 8&quot;, &quot;8&quot;));
/// 	}
/// 	else if ($cbxOs-&gt;PerformedPostBack() === true &amp;&amp; $cbxOs-&gt;GetSelectedValue() === &quot;Osx&quot;)
/// 	{
/// 		&#160;&#160;&#160;&#160; $cbxOsVersions-&gt;AddOption(new SMOptionListItem(&quot;MyExtensionOsVer105&quot;, &quot;Leopard&quot;, &quot;105&quot;));
/// 		&#160;&#160;&#160;&#160; $cbxOsVersions-&gt;AddOption(new SMOptionListItem(&quot;MyExtensionOsVer107&quot;, &quot;Lion&quot;, &quot;107&quot;));
/// 	}
///
/// 	// Create save button
///
/// 	$cmdSave = new SMLinkButton(&quot;MyExtensionSave&quot;);
/// 	$cmdSave-&gt;SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
/// 	$cmdSave-&gt;SetTitle(&quot;Save selections&quot;);
///
/// 	// Read and handle data if save button was clicked
///
/// 	if ($cmdSave-&gt;PerformedPostBack() === true)
/// 	{
/// 		&#160;&#160;&#160;&#160; $myOperSystem = $cbxOs->GetSelectedValue(); // Returns e.g. "Osx" (Mac OSX was selected)
/// 		&#160;&#160;&#160;&#160; $vers = $cbxOsVersions->GetSelectedValue(); // Returns e.g. "105;107" (Leopard and Lion was selected)
/// 		&#160;&#160;&#160;&#160; $vers = ($vers !== null ? $vers : ""); // A multi selection list may contain no selections (Null returned)
///
/// 		&#160;&#160;&#160;&#160; $this-&gt;saveSelections($myOperSystem, $vers);
/// 	}
///
/// 	// Render GUI
///
/// 	$html = &quot;&quot;;
/// 	$html .= &quot;&lt;br&gt;Select your OS: &quot; . $cbxOs-&gt;Render();
/// 	$html .= &quot;&lt;br&gt;Select your OS versions: &quot; . $cbxOsVersions-&gt;Render();
/// 	$html .= &quot;&lt;br&gt;&lt;br&gt;&quot; . $cmdSave-&gt;Render();
/// </container>
class SMOptionList
{
	private $id;					// string
	private $options;				// SMOptionListItem[]
	private $autoPostBack;			// bool
	private $attributes;			// string[]
	private $selectedValues;		// string[]
	private $doRender;				// bool

	/// <function container="gui/SMOptionList" name="__construct" access="public">
	/// 	<description> Create instance of SMOptionList </description>
	/// 	<param name="id" type="string"> Unique ID identifying option list control </param>
	/// </function>
	public function __construct($id)
	{
		// Note: $id MUST be the same on every page load,
		// as it is used to retrieve the post back value.

		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$this->id = $id;
		$this->options = array();
		$this->autoPostBack = false;
		$this->attributes = array();
		$this->selectedValues = SMEnvironment::GetPostValue($this->GetClientId());
		$this->doRender = true;
	}

	/// <function container="gui/SMOptionList" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMOptionList" name="GetClientId" access="public" returns="string">
	/// 	<description> Get component client ID </description>
	/// </function>
	public function GetClientId()
	{
		return "SMOptionList" . $this->id;
	}

	/// <function container="gui/SMOptionList" name="AddOption" access="public">
	/// 	<description> Add item to option list </description>
	/// 	<param name="option" type="SMOptionListItem"> Instance of SMOptionListItem </param>
	/// </function>
	public function AddOption(SMOptionListItem $option)
	{
		$this->options[] = $option;
	}

	/// <function container="gui/SMOptionList" name="RemoveOption" access="public">
	/// 	<description> Remove item from option list </description>
	/// 	<param name="id" type="string"> ID of item to remove </param>
	/// </function>
	public function RemoveOption($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$tmp = array();

		foreach ($this->options as $option)
			if ($option->GetId() !== $id)
				$tmp[] = $option;

		$this->options = $tmp;
	}

	/// <function container="gui/SMOptionList" name="SetOptions" access="public">
	/// 	<description> Set collection of items in option list </description>
	/// 	<param name="options" type="SMOptionListItem[]"> Array of SMOptionListItem instances </param>
	/// </function>
	public function SetOptions($options)
	{
		SMTypeCheck::CheckArray(__METHOD__, "options", $options, "SMOptionListItem");
		$this->options = $options;
	}

	/// <function container="gui/SMOptionList" name="GetOptions" access="public" returns="SMOptionListItem[]">
	/// 	<description> Returns collection of items in option list </description>
	/// </function>
	public function GetOptions()
	{
		return $this->options;
	}

	/// <function container="gui/SMOptionList" name="GetOption" access="public" returns="SMOptionListItem">
	/// 	<description> Returns item with specified ID if found, otherwise Null </description>
	/// 	<param name="id" type="string"> Item ID </param>
	/// </function>
	public function GetOption($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		foreach ($this->options as $option)
			if ($option->GetId() === $id)
				return $option;

		return null;
	}

	/// <function container="gui/SMOptionList" name="GetOptionByValue" access="public" returns="SMOptionListItem">
	/// 	<description> Returns item with specified value if found, otherwise Null </description>
	/// 	<param name="val" type="string"> Item value </param>
	/// </function>
	public function GetOptionByValue($val)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$String);

		foreach ($this->options as $option)
			if ($option->GetValue() === $val)
				return $option;

		return null;
	}

	/// <function container="gui/SMOptionList" name="SetAutoPostBack" access="public">
	/// 	<description>
	/// 		Set True to have option list post back automatically when selection is changed,
	/// 		False not to. Option list does not automatically post back by default.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable automatic post back, False not to </param>
	/// </function>
	public function SetAutoPostBack($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->autoPostBack = $value;
	}

	/// <function container="gui/SMOptionList" name="GetAutoPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if option list performs post back when selection is changed, False otherwise </description>
	/// </function>
	public function GetAutoPostBack()
	{
		return $this->autoPostBack;
	}

	/// <function container="gui/SMOptionList" name="SetAttribute" access="public">
	/// 	<description> Set attribute on option list control </description>
	/// 	<param name="attr" type="SMOptionListAttribute"> Attribute type </param>
	/// 	<param name="value" type="string"> Attribute value </param>
	/// </function>
	public function SetAttribute($attr, $value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "attr", $attr, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if (property_exists("SMOptionListAttribute", $attr) === false)
			throw new Exception("Invalid attribute '" . $attr . "' specified - use SMOptionListAttribute::Attribute");

		$this->attributes[$attr] = $value;
	}

	/// <function container="gui/SMOptionList" name="GetAttribute" access="public" returns="string">
	/// 	<description> Returns attribute value from option list control if set, otherwise Null </description>
	/// 	<param name="attr" type="SMOptionListAttribute"> Attribute type </param>
	/// </function>
	public function GetAttribute($attr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "attr", $attr, SMTypeCheckType::$String);

		if (property_exists("SMOptionListAttribute", $attr) === false)
			throw new Exception("Invalid attribute '" . $attr . "' specified - use SMOptionListAttribute::Attribute");

		return ((isset($this->attributes[$attr]) === true) ? $this->attributes[$attr] : null);
	}

	/// <function container="gui/SMOptionList" name="SetSelectedValue" access="public">
	/// 	<description>
	/// 		Set selected value(s).
	/// 		Specify value of item to select for an ordinary option list (single selection).
	/// 		Specify semi colon separated list of values of items to select for an option list
	/// 		allowing for multiple selections (val1;val2;val3).
	/// 	</description>
	/// 	<param name="value" type="string"> Value(s) of item(s) to select </param>
	/// </function>
	public function SetSelectedValue($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->selectedValues = SMStringUtilities::SplitBySemicolon($value); // Preserves HEX/HTML entities
	}

	/// <function container="gui/SMOptionList" name="ResetSelectedValue" access="public">
	/// 	<description> Reset selection so that no items are selected </description>
	/// </function>
	public function ResetSelectedValue()
	{
		$this->selectedValues = null;
	}

	/// <function container="gui/SMOptionList" name="GetSelectionMade" access="public" returns="boolean">
	/// 	<description> Returns True if items have been selected, otherwise False </description>
	/// </function>
	public function GetSelectionMade()
	{
		return ($this->selectedValues !== null && count($this->selectedValues) > 0);
	}

	/// <function container="gui/SMOptionList" name="GetSelectedValue" access="public" returns="string">
	/// 	<description>
	/// 		Returns selected value(s). A semi colon separated list of values are returned
	/// 		for an option list allowing for multiple selections (val1;val2;val3).
	/// 	</description>
	/// </function>
	public function GetSelectedValue()
	{
		if ($this->selectedValues !== null)
		{
			$selectedValues = array();

			foreach ($this->selectedValues as $selectedValue)
				if ($this->GetOptionByValue($selectedValue) !== null)
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

	/// <function container="gui/SMOptionList" name="PerformedPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if option list performed post back, otherwise False </description>
	/// </function>
	public function PerformedPostBack()
	{
		return (SMEnvironment::GetPostValue("SMPostBackControl") === $this->GetClientId());
	}

	/// <function container="gui/SMOptionList" name="SetRender" access="public">
	/// 	<description> Set value indicating whether to render option list or not </description>
	/// 	<param name="value" type="boolean"> Set True to have option list rendered, False not to </param>
	/// </function>
	public function SetRender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->doRender = $value;
	}

	/// <function container="gui/SMOptionList" name="GetRender" access="public" returns="boolean">
	/// 	<description> Returns True if option list is to be rendered, False otherwise </description>
	/// </function>
	public function GetRender()
	{
		return $this->doRender;
	}

	/// <function container="gui/SMOptionList" name="Render" access="public" returns="string">
	/// 	<description> Returns HTML representation of option list </description>
	/// </function>
	public function Render()
	{
		if ($this->doRender === false)
			return "";

		$class = $this->GetAttribute(SMInputAttribute::$Class);
		$this->SetAttribute(SMInputAttribute::$Class, "SMOptionList" . (($class !== null ? " " . $class : "")));

		$output = "";

		$onchangeJs = ((isset($this->attributes[SMOptionListAttribute::$OnChange]) === true) ? $this->attributes[SMOptionListAttribute::$OnChange] : "");
		$onchangeJs .= (($this->autoPostBack === true) ? (($onchangeJs !== "") ? ";" : "") . "SMDom.SetAttribute('SMPostBackControl', 'value', '" . $this->GetClientId() . "');smFormPostBack()" : "");
		$onchange = (($onchangeJs !== "") ? " onchange=\"" . $onchangeJs . "\"" : "");
		$output .= "<select name=\"" . $this->GetClientId() . "[]\" id=\"" . $this->GetClientId() . "\"" . $onchange;

		foreach ($this->attributes as $key => $value)
		{
			if ($key !== SMOptionListAttribute::$OnChange)
				$output .= " " . strtolower($key) . "=\"" . $value . "\"";
		}

		$output .= ">";

		$options = $this->options;
		$option = null;
		$select = false;

		if (count($options) === 0) // <select> must contain at least one <option> child. Notice: Not added to $this->options, hence not returned from GetSelectedValue(), even though it is always selected!
			$options[] = new SMOptionListItem(SMRandom::CreateGuid(), "", "");

		for ($i = 0 ; $i < count($options) ; $i++)
		{
			$option = $options[$i];

			if ($this->selectedValues !== null && in_array($option->GetValue(), $this->selectedValues, true) === true)
				$select = true;
			else
				$select = false;

			$option = new SMOptionListItem($option->GetId(), $option->GetTitle(), $option->GetValue(), $select);

			$output .= $option->Render();
		}

		$output .= "</select>";

		return $output;
	}
}

?>
