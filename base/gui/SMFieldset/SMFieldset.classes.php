<?php

/// <container name="gui/SMFieldsetAttribute">
/// 	Enum representing an attribute in field set
/// </container>
class SMFieldsetAttribute
{
	/// <member container="gui/SMFieldsetAttribute" name="Title" access="public" static="true" type="string" default="Title" />
	public static $Title = "Title";
	/// <member container="gui/SMFieldsetAttribute" name="Style" access="public" static="true" type="string" default="Style" />
	public static $Style = "Style";
	/// <member container="gui/SMFieldsetAttribute" name="Class" access="public" static="true" type="string" default="Class" />
	public static $Class = "Class";
	/// <member container="gui/SMFieldsetAttribute" name="AccessKey" access="public" static="true" type="string" default="AccessKey" />
	public static $AccessKey = "AccessKey";

	/// <member container="gui/SMFieldsetAttribute" name="OnClick" access="public" static="true" type="string" default="OnClick" />
	public static $OnClick = "OnClick";
	/// <member container="gui/SMFieldsetAttribute" name="OnDblClick" access="public" static="true" type="string" default="OnDblClick" />
	public static $OnDblClick = "OnDblClick";
	/// <member container="gui/SMFieldsetAttribute" name="OnMouseDown" access="public" static="true" type="string" default="OnMouseDown" />
	public static $OnMouseDown = "OnMouseDown";
	/// <member container="gui/SMFieldsetAttribute" name="OnMouseUp" access="public" static="true" type="string" default="OnMouseUp" />
	public static $OnMouseUp = "OnMouseUp";
	/// <member container="gui/SMFieldsetAttribute" name="OnMouseOver" access="public" static="true" type="string" default="OnMouseOver" />
	public static $OnMouseOver = "OnMouseOver";
	/// <member container="gui/SMFieldsetAttribute" name="OnMouseMove" access="public" static="true" type="string" default="OnMouseMove" />
	public static $OnMouseMove = "OnMouseMove";
	/// <member container="gui/SMFieldsetAttribute" name="OnMouseOut" access="public" static="true" type="string" default="OnMouseOut" />
	public static $OnMouseOut = "OnMouseOut";
	/// <member container="gui/SMFieldsetAttribute" name="OnKeyPress" access="public" static="true" type="string" default="OnKeyPress" />
	public static $OnKeyPress = "OnKeyPress";
	/// <member container="gui/SMFieldsetAttribute" name="OnKeyDown" access="public" static="true" type="string" default="OnKeyDown" />
	public static $OnKeyDown = "OnKeyDown";
	/// <member container="gui/SMFieldsetAttribute" name="OnKeyUp" access="public" static="true" type="string" default="OnKeyUp" />
	public static $OnKeyUp = "OnKeyUp";
}

/// <container name="gui/SMFieldset">
/// 	Class represents a field set used to group related GUI components.
///
/// 	$fs = new SMFieldset(&quot;MyExtensionMyId&quot;);
/// 	$fs->SetLegend(&quot;Address information&quot;);
/// 	$fs->SetContent(&quot;GUI controls mark up goes here&quot;);
/// 	$html = $fs->Render();
/// </container>
class SMFieldset
{
	private $id;				// string
	private $legend;			// string
	private $content;			// string
	private $attributes;		// string[]
	private $postBackControlId;	// string
	private $displayFrame;		// bool
	private $doRender;			// bool

	private $collapsable;		// bool
	private $collapsed;			// bool

	/// <function container="gui/SMFieldset" name="__construct" access="public">
	/// 	<description> Create instance of SMFieldset </description>
	/// 	<param name="id" type="string"> Unique ID identifying field set </param>
	/// </function>
	public function __construct($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$this->id = $id;
		$this->legend = "";
		$this->content = "";
		$this->attributes = array();
		$this->postBackControlId = null;
		$this->displayFrame = true;
		$this->doRender = true;

		$this->collapsable = false;
		$this->collapsed = false;
	}

	/// <function container="gui/SMFieldset" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMFieldset" name="GetClientId" access="public" returns="string">
	/// 	<description> Get component client ID </description>
	/// </function>
	public function GetClientId()
	{
		return "SMFieldset" . $this->id;
	}

	/// <function container="gui/SMFieldset" name="GetLegend" access="public" returns="string">
	/// 	<description> Get legend text </description>
	/// </function>
	public function GetLegend()
	{
		return $this->legend;
	}

	/// <function container="gui/SMFieldset" name="SetLegend" access="public">
	/// 	<description> Set legend text </description>
	/// 	<param name="value" type="string"> Legend text </param>
	/// </function>
	public function SetLegend($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->legend = $value;
	}

	/// <function container="gui/SMFieldset" name="GetContent" access="public" returns="string">
	/// 	<description> Get field set content </description>
	/// </function>
	public function GetContent()
	{
		return $this->content;
	}

	/// <function container="gui/SMFieldset" name="SetContent" access="public">
	/// 	<description> Set field set content (usually GUI controls mark up) </description>
	/// 	<param name="value" type="string"> Content </param>
	/// </function>
	public function SetContent($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->content = $value;
	}

	/// <function container="gui/SMFieldset" name="SetAttribute" access="public">
	/// 	<description> Set attribute on field set </description>
	/// 	<param name="attr" type="SMFieldsetAttribute"> Attribute type </param>
	/// 	<param name="value" type="string"> Attribute value </param>
	/// </function>
	public function SetAttribute($attr, $value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "attr", $attr, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if (property_exists("SMFieldsetAttribute", $attr) === false)
			throw new Exception("Invalid attribute '" . $attr . "' specified - use SMFieldsetAttribute::Attribute");

		$this->attributes[$attr] = $value;
	}

	/// <function container="gui/SMFieldset" name="GetAttribute" access="public" returns="string">
	/// 	<description> Get value from attribute on field set. Returns Null if not set. </description>
	/// 	<param name="attr" type="SMFieldsetAttribute"> Attribute type </param>
	/// </function>
	public function GetAttribute($attr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "attr", $attr, SMTypeCheckType::$String);

		if (property_exists("SMFieldsetAttribute", $attr) === false)
			throw new Exception("Invalid attribute '" . $attr . "' specified - use SMFieldsetAttribute::Attribute");

		return ((isset($this->attributes[$attr]) === true) ? $this->attributes[$attr] : null);
	}

	/// <function container="gui/SMFieldset" name="SetPostBackControl" access="public">
	/// 	<description>
	/// 		Set client ID of control to set as post back control when ENTER key is pressed.
	/// 		This is commonly used to have a save operation or similar triggered when ENTER is pressed.
	/// 		Example: $fieldset->SetPostBackControl($saveButton->GetClientId());
	/// 		The web application will now behave as if the save button was triggered when
	/// 		the ENTER key is pressed within the field set.
	/// 	</description>
	/// 	<param name="controlId" type="string"> Client ID of control to set as post back control </param>
	/// </function>
	public function SetPostBackControl($controlId)
	{
		SMTypeCheck::CheckObject(__METHOD__, "controlId", $controlId, SMTypeCheckType::$String);
		$this->postBackControlId = $controlId;
	}

	/// <function container="gui/SMFieldset" name="GetPostBackControl" access="public" returns="string">
	/// 	<description>
	/// 		Get client ID of control set as post back control when ENTER is
	/// 		pressed within field set. Returns Null if not configured.
	/// 	</description>
	/// </function>
	public function GetPostBackControl()
	{
		return $this->postBackControlId;
	}

	/// <function container="gui/SMFieldset" name="SetDisplayFrame" access="public">
	/// 	<description> Enable or disable frame surrounding field set, which is used to visually group related GUI controls </description>
	/// 	<param name="value" type="boolean"> Set True to enable frame, False to disable frame </param>
	/// </function>
	public function SetDisplayFrame($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->displayFrame = $value;

		if ($value === false)
			$this->collapsable = false;
	}

	/// <function container="gui/SMFieldset" name="GetDisplayFrame" access="public" returns="boolean">
	/// 	<description> Get value indicating whether frame is enabled or not </description>
	/// </function>
	public function GetDisplayFrame()
	{
		return $this->displayFrame;
	}

	/// <function container="gui/SMFieldset" name="SetRender" access="public">
	/// 	<description> Enable or disable rendering of field set </description>
	/// 	<param name="value" type="boolean"> Set True to have field set rendered, False not to </param>
	/// </function>
	public function SetRender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->doRender = $value;
	}

	/// <function container="gui/SMFieldset" name="GetRender" access="public" returns="boolean">
	/// 	<description> Get value indicating whether field set will be rendered or not </description>
	/// </function>
	public function GetRender()
	{
		return $this->doRender;
	}

	/// <function container="gui/SMFieldset" name="SetCollapsable" access="public">
	/// 	<description> Enable or disable functionality making field set collapsable </description>
	/// 	<param name="value" type="boolean"> Set True to make field set collapsable, False not to </param>
	/// </function>
	public function SetCollapsable($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->collapsable = $value;

		if ($value === true)
			$this->displayFrame = true;
	}

	/// <function container="gui/SMFieldset" name="GetCollapsable" access="public" returns="boolean">
	/// 	<description> Get value indicating whether field set is collapsable or not </description>
	/// </function>
	public function GetCollapsable()
	{
		return $this->collapsable;
	}

	/// <function container="gui/SMFieldset" name="SetCollapsed" access="public">
	/// 	<description> Have field set initially render collapsed </description>
	/// 	<param name="value" type="boolean"> Set True to initially collapse field set, False not to </param>
	/// </function>
	public function SetCollapsed($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->collapsed = $value;
	}

	/// <function container="gui/SMFieldset" name="GetCollapsed" access="public" returns="boolean">
	/// 	<description> Get value indicating whether field set will be rendered initially collapsed </description>
	/// </function>
	public function GetCollapsed()
	{
		return $this->collapsed;
	}

	/// <function container="gui/SMFieldset" name="Render" access="public" returns="string">
	/// 	<description> Returns HTML representation of field set </description>
	/// </function>
	public function Render()
	{
		if ($this->doRender === false)
			return "";

		// Register CSS classes

		$class = $this->GetAttribute(SMInputAttribute::$Class);
		$cls = "";

		if ($this->displayFrame === true)
		{
			$cls = "SMFieldset";

			if ($this->collapsed === false)
				$cls .= " SMFieldsetExpanded";
			else
				$cls .= " SMFieldsetCollapsed";
		}
		else
		{
			$cls = "SMFieldsetNoChrome";
		}

		$this->SetAttribute(SMInputAttribute::$Class, $cls . (($class !== null ? " " . $class : "")));

		// Inline CSS

		if ($this->displayFrame === false)
		{
			$style = ((isset($this->attributes[SMFieldsetAttribute::$Style]) === true) ? $this->attributes[SMFieldsetAttribute::$Style] : "");

			if (strlen($style) > 0)
			{
				$lastChar = substr($style, -1);

				if ($lastChar !== ";")
					$style .= ";";
			}

			$this->attributes[SMFieldsetAttribute::$Style] = $style . "border-style: none";
		}

		// Output

		$output = "";

		if ($this->postBackControlId !== null)
		{
			$output .= "
			<script type=\"text/javascript\">
			SMEventHandler.AddEventHandler(window, \"load\", smFieldsetRegisterPostBackControl" . $this->id . ");

			function smFieldsetRegisterPostBackControl" . $this->id . "()
			{
				var fieldset = SMDom.GetElement(\"" . $this->GetClientId() . "\");
				var inputFields = fieldset.getElementsByTagName(\"input\");

				var type = \"\";

				for (i = 0 ; i < inputFields.length ; i++)
				{
					type = inputFields[i].getAttribute(\"type\");

					if (type === null)
						continue;

					type = type.toLowerCase();

					if (type !== \"text\" && type !== \"password\" && type !== \"file\")
						continue;

					SMEventHandler.AddEventHandler(inputFields[i], \"keydown\", smFieldsetKeyListener" . $this->id . ");
				}
			}

			function smFieldsetKeyListener" . $this->id . "(ev)
			{
				if (window.event) // Event from IE
					ev = window.event;

				if (ev.keyCode === 13)
				{
					SMDom.SetAttribute(\"SMPostBackControl\", \"value\", \"" . $this->postBackControlId . "\");
					smFormPostBack();
				}
			}
			</script>
			";
		}

		$attributes = "";
		foreach ($this->attributes as $key => $value)
			$attributes .= " " . strtolower($key) . "=\"" . $value . "\"";

		$output .= "<fieldset id=\"" . $this->GetClientId() . "\"" . $attributes . ">";

		if ($this->collapsable === false)
			$output .= "<legend style=\"margin-left: 5px; padding-left: 5px; padding-right: 5px;" . (($this->displayFrame === false || $this->legend === "") ? " display: none;" : "") . "\">" . $this->legend . "</legend>";
		else
			$output .= "<legend onclick=\"smFieldsetToggle" . $this->id . "()\" style=\"margin-left: 5px; padding-left: 5px; padding-right: 5px; cursor: pointer;" . (($this->displayFrame === false || $this->legend === "") ? " display: none;" : "") . "\"><img src=\"base/gui/SMFieldset/images/" . (($this->collapsed === false) ? "expanded" : "collapsed") . ".gif\" id=\"smFieldsetImage" . $this->id . "\" style=\"cursor: pointer;\" alt=\"\"> " . $this->legend . "</legend>";

		$output .= "<div style=\"padding: " . (($this->displayFrame === true) ? (($this->collapsable === true && $this->collapsed === true) ? "3" : "10") : "0") . "px;\">"; // Fix: padding on fieldset does not work well in IE (padding-top moves fieldset down the page - padding is set outside the fieldset)
		$output .= "<div id=\"smFieldsetContent" . $this->id . "\"" . (($this->collapsable === true && $this->collapsed === true) ? " style=\"display: none;\"" : "") . ">";
		$output .= $this->content;
		$output .= "</div>";
		$output .= "</div>";

		$output .= "</fieldset>";

		if ($this->collapsable === true)
		{
			$output .= "
			<script type=\"text/javascript\">

			function smFieldsetToggle" . $this->id . "()
			{
				var content = document.getElementById(\"smFieldsetContent" . $this->id . "\");

				if (content.style.display === \"none\")
					smFieldsetDisplay" . $this->id . "();
				else
					smFieldsetHide" . $this->id . "();
			}

			function smFieldsetDisplay" . $this->id . "()
			{
				var fieldset = document.getElementById(\"" . $this->GetClientId() . "\");
				var img = document.getElementById(\"smFieldsetImage" . $this->id . "\");
				var content = document.getElementById(\"smFieldsetContent" . $this->id . "\");

				fieldset.className = \"SMFieldset SMFieldsetExpanded\";
				img.src = \"base/gui/SMFieldset/images/expanded.gif\";
				content.style.display = \"block\";
				content.parentNode.style.padding = \"10px\";

				SMCookie.SetCookie(\"smFieldsetVisible" . $this->id . "\", \"true\", 31536000); // Expires after one year
			}

			function smFieldsetHide" . $this->id . "()
			{
				var fieldset = document.getElementById(\"" . $this->GetClientId() . "\");
				var img = document.getElementById(\"smFieldsetImage" . $this->id . "\");
				var content = document.getElementById(\"smFieldsetContent" . $this->id . "\");

				fieldset.className = \"SMFieldset SMFieldsetCollapsed\";
				img.src = \"base/gui/SMFieldset/images/collapsed.gif\";
				content.style.display = \"none\";
				content.parentNode.style.padding = \"3px\";

				SMCookie.SetCookie(\"smFieldsetVisible" . $this->id . "\", \"false\", 31536000); // Expires after one year
			}

			function smFieldsetInitialize" . $this->id . "()
			{
				if (SMCookie.GetCookie(\"smFieldsetVisible" . $this->id . "\") === null)
					return;

				if (SMCookie.GetCookie(\"smFieldsetVisible" . $this->id . "\") === \"true\")
					smFieldsetDisplay" . $this->id . "();
				else
					smFieldsetHide" . $this->id . "();
			}

			SMEventHandler.AddEventHandler(window, \"load\", smFieldsetInitialize" . $this->id . ");

			</script>
			";
		}

		return $output;
	}
}

?>
