<?php

/// <container name="gui/SMInputType">
/// 	Enum representing input control type
/// </container>
class SMInputType
{
	/// <member container="gui/SMInputType" name="Text" access="public" static="true" type="string" default="Text" />
	public static $Text = "Text";
	/// <member container="gui/SMInputType" name="Password" access="public" static="true" type="string" default="Password" />
	public static $Password = "Password";
	/// <member container="gui/SMInputType" name="Hidden" access="public" static="true" type="string" default="Hidden" />
	public static $Hidden = "Hidden";
	/// <member container="gui/SMInputType" name="File" access="public" static="true" type="string" default="File" />
	public static $File = "File";
	/// <member container="gui/SMInputType" name="Radio" access="public" static="true" type="string" default="Radio" />
	public static $Radio = "Radio";
	/// <member container="gui/SMInputType" name="Checkbox" access="public" static="true" type="string" default="Checkbox" />
	public static $Checkbox = "Checkbox";
	/// <member container="gui/SMInputType" name="Button" access="public" static="true" type="string" default="Button" />
	public static $Button = "Button";
	/// <member container="gui/SMInputType" name="Submit" access="public" static="true" type="string" default="Submit" />
	public static $Submit = "Submit";
	/// <member container="gui/SMInputType" name="Reset" access="public" static="true" type="string" default="Reset" />
	public static $Reset = "Reset";
	/// <member container="gui/SMInputType" name="Textarea" access="public" static="true" type="string" default="Textarea" />
	public static $Textarea = "Textarea";
}

/// <container name="gui/SMInputAttribute">
/// 	Enum representing an attribute on an input control
/// </container>
class SMInputAttribute
{
	/// <member container="gui/SMInputAttribute" name="OnFocus" access="public" static="true" type="string" default="OnFocus" />
	public static $OnFocus = "OnFocus";
	/// <member container="gui/SMInputAttribute" name="OnBlur" access="public" static="true" type="string" default="OnBlur" />
	public static $OnBlur = "OnBlur";
	/// <member container="gui/SMInputAttribute" name="OnChange" access="public" static="true" type="string" default="OnChange" />
	public static $OnChange = "OnChange";
	/// <member container="gui/SMInputAttribute" name="OnClick" access="public" static="true" type="string" default="OnClick" />
	public static $OnClick = "OnClick";
	/// <member container="gui/SMInputAttribute" name="OnDblClick" access="public" static="true" type="string" default="OnDblClick" />
	public static $OnDblClick = "OnDblClick";
	/// <member container="gui/SMInputAttribute" name="OnMouseDown" access="public" static="true" type="string" default="OnMouseDown" />
	public static $OnMouseDown = "OnMouseDown";
	/// <member container="gui/SMInputAttribute" name="OnMouseUp" access="public" static="true" type="string" default="OnMouseUp" />
	public static $OnMouseUp = "OnMouseUp";
	/// <member container="gui/SMInputAttribute" name="OnMouseOver" access="public" static="true" type="string" default="OnMouseOver" />
	public static $OnMouseOver = "OnMouseOver";
	/// <member container="gui/SMInputAttribute" name="OnMouseMove" access="public" static="true" type="string" default="OnMouseMove" />
	public static $OnMouseMove = "OnMouseMove";
	/// <member container="gui/SMInputAttribute" name="OnMouseOut" access="public" static="true" type="string" default="OnMouseOut" />
	public static $OnMouseOut = "OnMouseOut";
	/// <member container="gui/SMInputAttribute" name="OnKeyPress" access="public" static="true" type="string" default="OnKeyPress" />
	public static $OnKeyPress = "OnKeyPress";
	/// <member container="gui/SMInputAttribute" name="OnKeyDown" access="public" static="true" type="string" default="OnKeyDown" />
	public static $OnKeyDown = "OnKeyDown";
	/// <member container="gui/SMInputAttribute" name="OnKeyUp" access="public" static="true" type="string" default="OnKeyUp" />
	public static $OnKeyUp = "OnKeyUp";

		/// <member container="gui/SMInputAttribute" name="Value" access="public" static="true" type="string" default="Value" />
	public static $Value = "Value";
	/// <member container="gui/SMInputAttribute" name="Title" access="public" static="true" type="string" default="Title" />
	public static $Title = "Title";
	/// <member container="gui/SMInputAttribute" name="ReadOnly" access="public" static="true" type="string" default="ReadOnly" />
	public static $ReadOnly = "ReadOnly";
	/// <member container="gui/SMInputAttribute" name="Disabled" access="public" static="true" type="string" default="Disabled" />
	public static $Disabled = "Disabled";
	/// <member container="gui/SMInputAttribute" name="MaxLength" access="public" static="true" type="string" default="MaxLength" />
	public static $MaxLength = "MaxLength";
	/// <member container="gui/SMInputAttribute" name="Style" access="public" static="true" type="string" default="Style" />
	public static $Style = "Style";
	/// <member container="gui/SMInputAttribute" name="Class" access="public" static="true" type="string" default="Class" />
	public static $Class = "Class";
	/// <member container="gui/SMInputAttribute" name="AccessKey" access="public" static="true" type="string" default="AccessKey" />
	public static $AccessKey = "AccessKey";
	/// <member container="gui/SMInputAttribute" name="TabIndex" access="public" static="true" type="string" default="TabIndex" />
	public static $TabIndex = "TabIndex";

	/// <member container="gui/SMInputAttribute" name="OnSelect" access="public" static="true" type="string" default="OnSelect" />
	public static $OnSelect = "OnSelect";

	/// <member container="gui/SMInputAttribute" name="Accept" access="public" static="true" type="string" default="Accept" />
	public static $Accept = "Accept";

	/// <member container="gui/SMInputAttribute" name="Checked" access="public" static="true" type="string" default="Checked" />
	public static $Checked = "Checked";

	/// <member container="gui/SMInputAttribute" name="Cols" access="public" static="true" type="string" default="Cols" />
	public static $Cols = "Cols";
	/// <member container="gui/SMInputAttribute" name="Rows" access="public" static="true" type="string" default="Rows" />
	public static $Rows = "Rows";
}

class SMInputAttributeEvent // Deprecated - use SMInputAttribute
{
	public static $OnFocus = "OnFocus";
	public static $OnBlur = "OnBlur";
	public static $OnChange = "OnChange";
	public static $OnClick = "OnClick";
	public static $OnDblClick = "OnDblClick";
	public static $OnMouseDown = "OnMouseDown";
	public static $OnMouseUp = "OnMouseUp";
	public static $OnMouseOver = "OnMouseOver";
	public static $OnMouseMove = "OnMouseMove";
	public static $OnMouseOut = "OnMouseOut";
	public static $OnKeyPress = "OnKeyPress";
	public static $OnKeyDown = "OnKeyDown";
	public static $OnKeyUp = "OnKeyUp";
}

class SMInputAttributeText extends SMInputAttributeEvent // Deprecated - use SMInputAttribute
{
	public static $Value = "Value";
	public static $Title = "Title";
	public static $ReadOnly = "ReadOnly";
	public static $Disabled = "Disabled";
	public static $MaxLength = "MaxLength";
	public static $Style = "Style";
	public static $Class = "Class";
	public static $AccessKey = "AccessKey";
	public static $TabIndex = "TabIndex";

	public static $OnSelect = "OnSelect";
}

class SMInputAttributePassword extends SMInputAttributeText // Deprecated - use SMInputAttribute
{
}

class SMInputAttributeHidden // Deprecated - use SMInputAttribute
{
	public static $Value = "Value";
}

class SMInputAttributeFile extends SMInputAttributeEvent // Deprecated - use SMInputAttribute
{
	public static $Value = "Value";
	public static $Title = "Title";
	public static $Disabled = "Disabled";
	public static $Style = "Style";
	public static $Class = "Class";
	public static $AccessKey = "AccessKey";
	public static $TabIndex = "TabIndex";
	public static $Accept = "Accept";

	public static $OnSelect = "OnSelect";
}

class SMInputAttributeRadio extends SMInputAttributeEvent // Deprecated - use SMInputAttribute
{
	public static $Value = "Value";
	public static $Title = "Title";
	public static $ReadOnly = "ReadOnly";
	public static $Disabled = "Disabled";
	public static $Checked = "Checked";
	public static $Style = "Style";
	public static $Class = "Class";
	public static $AccessKey = "AccessKey";
	public static $TabIndex = "TabIndex";
}

class SMInputAttributeCheckbox extends SMInputAttributeRadio // Deprecated - use SMInputAttribute
{
}

class SMInputAttributeButton extends SMInputAttributeEvent
{
	public static $Value = "Value";
	public static $Title = "Title";
	public static $Disabled = "Disabled";
	public static $Style = "Style";
	public static $Class = "Class";
	public static $AccessKey = "AccessKey";
	public static $TabIndex = "TabIndex";
}

class SMInputAttributeSubmit extends SMInputAttributeButton // Deprecated - use SMInputAttribute
{
}

class SMInputAttributeReset extends SMInputAttributeButton // Deprecated - use SMInputAttribute
{
}

class SMInputAttributeTextarea extends SMInputAttributeText // Deprecated - use SMInputAttribute
{
	public static $Cols = "Cols";
	public static $Rows = "Rows";

	public static $OnSelect = "OnSelect";
}

class SMInputRadios
{
	public static $Radios = array();
}

/// <container name="gui/SMInput">
/// 	Class represents an HTML input control (e.g. textbox, textarea, checkbox, radio button, button etc.)
///
/// 	// Construct GUI controls
///
/// 	$txtName = new SMInput(&quot;MyExtensionName&quot;, SMInputType::$Text);
/// 	$txtName-&gt;SetAttribute(SMInputAttribute::$Style, &quot;width: 150px&quot;);
/// 	$txtName-&gt;SetAttribute(SMInputAttribute::$MaxLength, &quot;50&quot;);
///
/// 	$radGenderMan = new SMInput(&quot;MyExtensionGender&quot;, SMInputType::$Radio);
/// 	$radGenderMan-&gt;SetValue(&quot;Man&quot;);
///
/// 	$radGenderWoman = new SMInput(&quot;MyExtensionGender&quot;, SMInputType::$Radio);
/// 	$radGenderWoman-&gt;SetValue(&quot;Woman&quot;);
///
/// 	// Notice how radio buttons are created with the same ID, but with different
/// 	// values - this approach is required in order for radio buttons to become related.
///
/// 	$txtDescription = new SMInput(&quot;MyExtensionDescription&quot;, SMInputType::$Textarea);
/// 	$txtDescription-&gt;SetAttribute(SMInputAttribute::$Style, &quot;width: 150px; height: 100px&quot;);
/// 	$txtDescription-&gt;SetAttribute(SMInputAttribute::$MaxLength, &quot;500&quot;);
///
/// 	$cmdCreate = new SMInput(&quot;MyExtensionCreate&quot;, SMInputType::$Submit);
/// 	$cmdCreate-&gt;SetValue(&quot;Create account&quot;);
///
/// 	// Read and handle data if post back was performed
///
/// 	if ($cmdCreate-&gt;PerformedPostBack() === true)
/// 	{
/// 		&#160;&#160;&#160;&#160; $name = $txtName-&gt;GetValue();
/// 		&#160;&#160;&#160;&#160; $gender = ($radGenderMan-&gt;GetChecked() === true ? &quot;Man&quot; : &quot;&quot;);
/// 		&#160;&#160;&#160;&#160; $gender = ($radGenderWoman-&gt;GetChecked() === true ? &quot;Woman&quot; : $gender);
/// 		&#160;&#160;&#160;&#160; $description = $txtDescription-&gt;GetValue();
///
/// 		&#160;&#160;&#160;&#160; $this-&gt;createUser($name, $gender, $description);
/// 	}
///
/// 	// Render input controls
///
/// 	$html = &quot;&quot;;
/// 	$html .= &quot;&lt;br&gt;Enter name: &quot; . $txtName-&gt;Render();
/// 	$html .= &quot;&lt;br&gt;Gender: &quot; . $radGenderMan-&gt;Render() . &quot; Man | &quot; . $radGenderWoman-&gt;Render() . &quot; Woman&quot;;
/// 	$html .= &quot;&lt;br&gt;Description: &quot; . $txtDescription-&gt;Render();
/// 	$html .= &quot;&lt;br&gt;&lt;br&gt;&quot; . $cmdCreate-&gt;Render();
/// </container>
class SMInput
{
	private $id;					// string
	private $nameAttr;				// string - always the same as $this->GetClientId(), except for Radio buttons
	private $type;					// string
	private $value;					// string
	private $checked;				// bool
	private $attributes;			// string[]
	private $doRender;				// bool

	/// <function container="gui/SMInput" name="__construct" access="public">
	/// 	<description> Create instance of SMInput </description>
	/// 	<param name="id" type="string"> Unique ID identifying input control </param>
	/// 	<param name="type" type="SMInputType"> Input control type </param>
	/// </function>
	public function __construct($id, $type)
	{
		// Note: $id MUST be the same on every page load,
		// as it is used to retrieve the post back value.

		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMInputType", $type) === false)
			throw new Exception("Invalid type '" . $type . "' specified - use SMInputType::Type");

		$this->id = $id;
		$this->nameAttr = $this->GetClientId();
		$this->type = $type;

		// Make sure each radio button has it's own unique Client ID, even though
		// they are created with the exact same ID in the constructor. This way each
		// radio button is accessible client side using JavaScript.
		// $this->nameAttr is used for the NAME attribute in the input element,
		// to group the radio buttons, so that only one radio button may be selected.
		if ($this->type === SMInputType::$Radio)
		{
			if (isset(SMInputRadios::$Radios[$this->nameAttr]) === false)
				SMInputRadios::$Radios[$this->nameAttr] = 0;
			else
				SMInputRadios::$Radios[$this->nameAttr]++;

			$this->id = $this->id . SMInputRadios::$Radios[$this->nameAttr];
		}

		$this->value = SMEnvironment::GetPostValue($this->nameAttr);
		$this->checked = false;
		$this->attributes = array();
		$this->doRender = true;

		// Making sure post back value is assigned to attribute collection,
		// and rendered client side again (except for password fields of course).
		//
		// We don't set the value attribute for Radio buttons either. The reason:
		// When e.g. 3 radio buttons (with the same ID) is posted back, all 3 radio
		// buttons will get the same value above (GetPostValue(..)).
		// Invoking the code below would cause all radio buttons, with the same ID,
		// to be checked, since they have the same value when constructed (GetPostValue(..)).
		// Is is not possible to check the radio buttons before they are given
		// individuel values. Therefore radio buttons requires a value to be
		// set using either SetValue(..) or SetAttribute(..). When one of these
		// are invoked, the proper radio button will be checked, the rest will not.
		if ($this->value !== null && $this->type !== SMInputType::$Password && $this->type !== SMInputType::$Radio)
			$this->SetAttribute(SMInputAttribute::$Value, $this->value);

		if ($this->value !== null && $type === SMInputType::$Checkbox)
			$this->SetAttribute(SMInputAttribute::$Checked, "checked");
	}

	/// <function container="gui/SMInput" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMInput" name="GetClientId" access="public" returns="string">
	/// 	<description> Get component client ID </description>
	/// </function>
	public function GetClientId()
	{
		return "SMInput" . $this->id;
	}

	/// <function container="gui/SMInput" name="GetType" access="public" returns="SMInputType">
	/// 	<description> Get input control type </description>
	/// </function>
	public function GetType()
	{
		return $this->type;
	}

	/// <function container="gui/SMInput" name="SetAttribute" access="public">
	/// 	<description> Set attribute on input control </description>
	/// 	<param name="attr" type="SMInputAttribute"> Attribute type </param>
	/// 	<param name="value" type="string"> Attribute value </param>
	/// </function>
	public function SetAttribute($attr, $value)
	{
		// Notice: id, name and type cannot be set, as they are assigned automatically (see Render())

		SMTypeCheck::CheckObject(__METHOD__, "attr", $attr, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if (property_exists("SMInputAttribute", $attr) === false)
			throw new Exception("Invalid attribute '" . $attr . "' specified - use SMInputAttribute::Attribute");

		if ($this->validAttribute($attr) === false)
			throw new Exception("Specified attribute '" . $attr . "' is not supported by input type '" . $this->type . "'");

		// Restoring checked state for radio button.
		// This can only be done when we have the value. So this
		// requires the value to be always set for radio buttons.
		if ($this->type === SMInputType::$Radio && $attr === SMInputAttribute::$Value && $value === $this->value)
		{
			$this->checked = true;
			$this->attributes[SMInputAttribute::$Checked] = "checked";
		}

		if ($value === "")
			unset($this->attributes[$attr]);
		else
			$this->attributes[$attr] = $value;

		if ($attr === SMInputAttribute::$Value)
			$this->value = $value;
		else if ($attr === SMInputAttribute::$Checked)
			$this->checked = ($value !== "");
	}

	/// <function container="gui/SMInput" name="GetAttribute" access="public" returns="string">
	/// 	<description> Returns attribute value from input control if set, otherwise Null </description>
	/// 	<param name="attr" type="SMInputAttribute"> Attribute type </param>
	/// </function>
	public function GetAttribute($attr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "attr", $attr, SMTypeCheckType::$String);

		if (property_exists("SMInputAttribute", $attr) === false)
			throw new Exception("Invalid attribute '" . $attr . "' specified - use SMInputAttribute::Attribute");

		if ($this->validAttribute($attr) === false)
			throw new Exception("Specified attribute '" . $attr . "' is not supported by input type '" . $this->type . "'");

		return ((isset($this->attributes[$attr]) === true) ? $this->attributes[$attr] : null);
	}

	/// <function container="gui/SMInput" name="PerformedPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if input control (of type Submit) performed post back, otherwise False </description>
	/// </function>
	public function PerformedPostBack()
	{
		return (SMEnvironment::GetPostValue("SMPostBackControl") === $this->nameAttr);
	}

	/// <function container="gui/SMInput" name="GetValue" access="public" returns="string">
	/// 	<description> Returns value from input control if set or posted back, otherwise Null </description>
	/// </function>
	public function GetValue()
	{
		if ($this->type === SMInputType::$File && isset($_FILES[$this->GetClientId()]) === true) // $this-value is returned if postback has not yet occured
			return $_FILES[$this->GetClientId()]["name"];

		$maxLength = ((isset($this->attributes[SMInputAttribute::$MaxLength]) === true) ? $this->attributes[SMInputAttribute::$MaxLength] : null);

		if ($maxLength !== null && SMStringUtilities::Validate($maxLength, SMValueRestriction::$Numeric) === true && strlen($this->value) > (int)$maxLength)
		{
			// Value is too long - substring to fit Max Length.
			// Substringing may break unicode characters encoded into HEX entities - e.g. &#64000; gets substringed into &#640.
			// Using RegEx to remove broken HEX entities: If string ends with & OR ends with &# followed by zero or more digits (e.g. &#640),
			// it's considered a broken HEX entity and will be removed. Naturally this is not perfect, since the user could have entered
			// an ampersand, which substringing could cause to become the last character. In this cause it will mistakenly be taken
			// for a broken HEX entity. But it's fairly unlikely to happen, and it probably won't add much value to the string with
			// an ampersand at the end.
			// We would have to encode ampersands to solve this problem, but we only want non-Latin1 characters to be encoded.

			$val = substr($this->value, 0, (int)$maxLength);
			$val = preg_replace("/&$|&#\d*$/", "", $val);

			return $val; // Just return value, do not replace $this->value - we may want to preserve the original value between postbacks
		}

		return $this->value;
	}

	/// <function container="gui/SMInput" name="SetValue" access="public">
	/// 	<description> Set input control value </description>
	/// 	<param name="value" type="string"> New input control value </param>
	/// </function>
	public function SetValue($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->SetAttribute(SMInputAttribute::$Value, $value);
	}

	/// <function container="gui/SMInput" name="GetChecked" access="public" returns="boolean">
	/// 	<description> Returns True if input control (of type Checkbox or Radio) is checked, otherwise False </description>
	/// </function>
	public function GetChecked()
	{
		return $this->checked;
	}

	/// <function container="gui/SMInput" name="SetChecked" access="public">
	/// 	<description> Set checked state for an input control of type Checkbox or Radio </description>
	/// 	<param name="value" type="boolean"> New checked state </param>
	/// </function>
	public function SetChecked($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);

		if ($this->type !== SMInputType::$Radio && $this->type !== SMInputType::$Checkbox)
			return;

		$this->SetAttribute(SMInputAttribute::$Checked, (($value === true) ? "checked" : ""));
	}

	/// <function container="gui/SMInput" name="SetRender" access="public">
	/// 	<description> Set value indicating whether to render input control or not </description>
	/// 	<param name="value" type="boolean"> Set True to have input control rendered, False not to </param>
	/// </function>
	public function SetRender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->doRender = $value;
	}

	/// <function container="gui/SMInput" name="GetRender" access="public" returns="boolean">
	/// 	<description> Returns True if input control is to be rendered, False otherwise </description>
	/// </function>
	public function GetRender()
	{
		return $this->doRender;
	}

	/// <function container="gui/SMInput" name="Render" access="public" returns="string">
	/// 	<description> Returns HTML representation of input control </description>
	/// </function>
	public function Render()
	{
		if ($this->doRender === false)
			return "";

		if ($this->type !== SMInputType::$Hidden)
		{
			$class = $this->GetAttribute(SMInputAttribute::$Class);
			$this->SetAttribute(SMInputAttribute::$Class, "SMInput" . (($class !== null ? " " . $class : "")));
		}

		$output = "";

		if ($this->type === SMInputType::$Textarea)
		{
			/*if (isset($this->attributes[SMInputAttribute::$MaxLength]) === true)
			{
				$output .= "<script type=\"text/javascript\">";
				$output .= "var smInputCtrlDown" . $this->id . " = false;";
				$output .= "";
				$output .= "function smInputChangeListener" . $this->id . "(e, sender)"; // OnChange event will make absolutely sure only the accepted number of characters are entered (e.g. data from Paste using context menu is trunkcated). Fires when focus is lost.
				$output .= "{";
				$output .= "	if (window.event) e = window.event;";
				$output .= "	";
				$output .= "	if (sender.value.length > " . $this->attributes[SMInputAttribute::$MaxLength] . ")";
				$output .= "		sender.value = sender.value.substring(0, " . $this->attributes[SMInputAttribute::$MaxLength] . ")";
				$output .= "}";
				$output .= "";
				$output .= "function smInputKeyDownListener" . $this->id . "(e, sender)"; // Value not changed until KeyUp is fired. This event only fires once, even though key is held down.
				$output .= "{";
				$output .= "	if (window.event) e = window.event;";
				$output .= "	";
				$output .= "	if (e.keyCode === 17 || e.keyCode === 224)"; // Keycode 17 = Ctrl, keycode 224 = Cmd (OSX)
				$output .= "		smInputCtrlDown" . $this->id . " = true;";
				$output .= "}";
				$output .= "";
				$output .= "function smInputKeyUpListener" . $this->id . "(e, sender)"; // Changed value available during this event. This event only fires once, even though key is held down.
				$output .= "{";
				$output .= "	if (window.event) e = window.event;";
				$output .= "	";
				$output .= "	if (e.keyCode === 17 || e.keyCode === 224)"; // Keycode 17 = Ctrl, keycode 224 = Cmd (OSX)
				$output .= "		smInputCtrlDown" . $this->id . " = false;";
				$output .= "	";
				$output .= "	if (sender.value.length > " . $this->attributes[SMInputAttribute::$MaxLength] . ")"; // OnKeyPress prevents ordinary input from being written to the text area. The paste operation might result in too much data being inserted into the text area, which we cannot detect before OnKeyUp (now) where the new value is available - truncating value if necessary.
				$output .= "		sender.value = sender.value.substring(0, " . $this->attributes[SMInputAttribute::$MaxLength] . ");";
				$output .= "}";
				$output .= "";
				$output .= "function smInputKeyPressListener" . $this->id . "(e, sender)"; // Value not changed until KeyUp is fired. This event fires constantly if a key is held down
				$output .= "{";
				$output .= "	if (window.event) e = window.event;";
				$output .= "	";
				$output .= "	if (smInputCtrlDown" . $this->id . " === true)"; // Always allow e.g. Ctrl/Cmd + C/V/A
				$output .= "		return true;";
				$output .= "	";
				$output .= "	if (e.keyCode === 8 || e.keyCode === 46 || e.keyCode === 37 || e.keyCode === 39 || e.keyCode === 38 || e.keyCode === 40 || e.keyCode === 36 || e.keyCode === 35)"; // Always allow backspace, delete, arrows (left, right, up, down), home, end
				$output .= "		return true;";
				$output .= "	";
				$output .= "	return (sender.value.length < " . $this->attributes[SMInputAttribute::$MaxLength] . ");";
				$output .= "}";
				$output .= "</script>";

				$this->attributes[SMInputAttribute::$OnChange] = "smInputChangeListener" . $this->id . "(event, this)" . ((isset($this->attributes[SMInputAttribute::$OnChange]) === true) ? ";" . $this->attributes[SMInputAttribute::$OnChange] : "");
				$this->attributes[SMInputAttribute::$OnKeyDown] = "smInputKeyDownListener" . $this->id . "(event, this)" . ((isset($this->attributes[SMInputAttribute::$OnKeyDown]) === true) ? ";" . $this->attributes[SMInputAttribute::$OnKeyDown] : "");
				$this->attributes[SMInputAttribute::$OnKeyPress] = "var res = smInputKeyPressListener" . $this->id . "(event, this);" . ((isset($this->attributes[SMInputAttribute::$OnKeyPress]) === true) ? $this->attributes[SMInputAttribute::$OnKeyPress] . ";" : "") . "return res";
				$this->attributes[SMInputAttribute::$OnKeyUp] = "smInputKeyUpListener" . $this->id . "(event, this)" . ((isset($this->attributes[SMInputAttribute::$OnKeyUp]) === true) ? ";" . $this->attributes[SMInputAttribute::$OnKeyUp] : "");
			}*/

			// Ensure required attributes (W3C): rows, cols
			if ($this->GetAttribute(SMInputAttribute::$Rows) === null);
				$this->SetAttribute(SMInputAttribute::$Rows, "5");
			if ($this->GetAttribute(SMInputAttribute::$Cols) === null);
				$this->SetAttribute(SMInputAttribute::$Cols, "10");

			$output .= "<textarea id=\"" . $this->GetClientId() . "\" name=\"" . $this->nameAttr . "\"";
			foreach ($this->attributes as $key => $value)
				if ($key !== SMInputAttribute::$Value && $key !== SMInputAttribute::$MaxLength)
					$output .= " " . strtolower($key) . "=\"" . $value . "\"";
			$output .= ">" . ((isset($this->attributes[SMInputAttribute::$Value]) === true) ? SMStringUtilities::HtmlEncode($this->attributes[SMInputAttribute::$Value], true) : "") . "</textarea>";

			if (isset($this->attributes[SMInputAttribute::$MaxLength]) === true)
			{
				// Register maxlength using JavaScript since it is not a W3C valid attribute for textarea in HTML4 (but is for HTML5)

				$output .= "<script type=\"text/javascript\">";
				$output .= "document.getElementById(\"" . $this->GetClientId() . "\").maxLength = " . $this->attributes[SMInputAttribute::$MaxLength] . ";";
				$output .= "</script>";
			}
		}
		else
		{
			if ($this->type === SMInputType::$Submit)
				$this->attributes[SMInputAttribute::$OnClick] = "SMDom.SetAttribute('SMPostBackControl', 'value', '" . $this->nameAttr . "')" . ((isset($this->attributes[SMInputAttribute::$OnClick]) === true) ? ";" . $this->attributes[SMInputAttribute::$OnClick] : "");

			$output .= "<input id=\"" . $this->GetClientId() . "\" name=\"" . $this->nameAttr . "\" type=\"" . strtolower($this->type) . "\"";
			foreach ($this->attributes as $key => $value)
				$output .= " " . strtolower($key) . "=\"" . (($key === SMInputAttribute::$Value) ? SMStringUtilities::HtmlEncode($value, true) : $value) . "\"";
			$output .= ">";
		}

		return $output;
	}

	private static $validAttributes = null;
	private function validAttribute($attr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "attr", $attr, SMTypeCheckType::$String);

		if (self::$validAttributes === null)
		{
			$commonEvents = array("onfocus", "onblur", "onchange", "onclick", "ondblclick", "onmousedown", "onmouseup", "onmouseover", "onmousemove", "onmouseout", "onkeypress", "onkeydown", "onkeyup");
			$commonAttrs = array("value", "title", "disabled", "style", "class", "accesskey", "tabindex");

			$attributes = array();
			$attributes["Text"] = array_merge(array("readonly", "maxlength", "onselect"), $commonAttrs, $commonEvents);
			$attributes["Password"] = $attributes["Text"];
			$attributes["Hidden"] = array("value");
			$attributes["File"] = array_merge(array("accept", "onselect"), $commonAttrs, $commonEvents);
			$attributes["Radio"] = array_merge(array("readonly", "checked"), $commonAttrs, $commonEvents);
			$attributes["Checkbox"] = $attributes["Radio"];
			$attributes["Button"] = array_merge($commonAttrs, $commonEvents);
			$attributes["Submit"] = $attributes["Button"];
			$attributes["Reset"] = $attributes["Button"];
			$attributes["Textarea"] = array_merge(array("cols", "rows"), $attributes["Text"]);

			self::$validAttributes = $attributes;
		}

		return in_array(strtolower($attr), self::$validAttributes[$this->type], true);
	}
}

?>
