<?php

/// <container name="gui/SMLinkButton">
/// 	Class represents an HTML link button which performs an action when clicked.
/// 	The action commonly performed is triggering post back or client side behaviour.
///
/// 	// Construct link button.
/// 	// Notice how simply returning from the OnClick event will cause the action to be canceled.
///
/// 	$cmdSave = new SMLinkButton(&quot;MyExtensionSave&quot;);
/// 	$cmdSave-&gt;SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
/// 	$cmdSave-&gt;SetTitle(&quot;Save profile&quot;);
/// 	$cmdSave-&gt;SetOnClick(&quot;var res = prompt('Save data now?'); if (res === false) return;&quot;);
///
/// 	// Read and handle data if post back was performed
///
/// 	if ($cmdSave-&gt;PerformedPostBack() === true)
/// 	{
/// 		&#160;&#160;&#160;&#160; $this-&gt;saveDataFromGuiToDatabase();
/// 	}
///
/// 	// Render link button
///
/// 	$html = $cmdSave-&gt;Render();
/// </container>
class SMLinkButton
{
	private $id;
	private $title;
	private $description;
	private $icon;
	private $fontIcon;
	private $onclick;
	private $postBack;
	private $doRender;

	/// <function container="gui/SMLinkButton" name="__construct" access="public">
	/// 	<description> Create instance of SMLinkButton </description>
	/// 	<param name="id" type="string"> Unique ID identifying link button control </param>
	/// </function>
	public function __construct($id)
	{
		// Note: $id MUST be the same on every page load,
		// as it is used to retrieve the post back value.

		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$this->id = $id;
		$this->title = null;
		$this->description = null;
		$this->icon = null;
		$this->fontIcon = null;
		$this->onclick = null;
		$this->postBack = true;
		$this->doRender = true;
	}

	/// <function container="gui/SMLinkButton" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMLinkButton" name="GetClientId" access="public" returns="string">
	/// 	<description> Get component client ID </description>
	/// </function>
	public function GetClientId()
	{
		return "SMLinkButton" . $this->id;
	}

	/// <function container="gui/SMLinkButton" name="SetTitle" access="public">
	/// 	<description> Set link title </description>
	/// 	<param name="title" type="string"> Link title </param>
	/// </function>
	public function SetTitle($title)
	{
		SMTypeCheck::CheckObject(__METHOD__, "title", $title, SMTypeCheckType::$String);
		$this->title = $title;
	}

	/// <function container="gui/SMLinkButton" name="GetTitle" access="public" returns="string">
	/// 	<description> Returns link title if set, otherwise Null </description>
	/// </function>
	public function GetTitle()
	{
		return $this->title;
	}

	/// <function container="gui/SMLinkButton" name="SetDescription" access="public">
	/// 	<description> Set link description (shown on mouse over) </description>
	/// 	<param name="desc" type="string"> Link description </param>
	/// </function>
	public function SetDescription($desc)
	{
		SMTypeCheck::CheckObject(__METHOD__, "desc", $desc, SMTypeCheckType::$String);
		$this->description = $desc;
	}

	/// <function container="gui/SMLinkButton" name="GetDescription" access="public" returns="string">
	/// 	<description> Returns link description if set, otherwise Null </description>
	/// </function>
	public function GetDescription()
	{
		return $this->description;
	}

	/// <function container="gui/SMLinkButton" name="SetIcon" access="public">
	/// 	<description> Set link icon reference </description>
	/// 	<param name="icon" type="string"> Link icon reference </param>
	/// </function>
	public function SetIcon($icon)
	{
		SMTypeCheck::CheckObject(__METHOD__, "icon", $icon, SMTypeCheckType::$String);
		$this->icon = $icon;
	}

	/// <function container="gui/SMLinkButton" name="GetIcon" access="public" returns="string">
	/// 	<description> Returns link icon reference if set, otherwise Null </description>
	/// </function>
	public function GetIcon()
	{
		return $this->icon;
	}

	/// <function container="gui/SMLinkButton" name="SetFontIcon" access="public">
	/// 	<description> Set Font Awesome icon reference (e.g. fa-search) - http://fontawesome.io/icons </description>
	/// 	<param name="icon" type="string"> Icon reference </param>
	/// </function>
	public function SetFontIcon($icon)
	{
		SMTypeCheck::CheckObject(__METHOD__, "icon", $icon, SMTypeCheckType::$String);
		$this->fontIcon = $icon;
	}

	/// <function container="gui/SMLinkButton" name="GetFontIcon" access="public" returns="string">
	/// 	<description> Returns Font Awesome icon reference if set, otherwise Null </description>
	/// </function>
	public function GetFontIcon()
	{
		return $this->fontIcon;
	}

	/// <function container="gui/SMLinkButton" name="SetOnclick" access="public">
	/// 	<description> Set JavaScript to execute on client click. Have logic simply return to suppress post back. </description>
	/// 	<param name="value" type="string"> JavaScript to execute on client click </param>
	/// </function>
	public function SetOnclick($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->onclick = $value;
	}

	/// <function container="gui/SMLinkButton" name="GetOnclick" access="public" returns="string">
	/// 	<description> Returns JavaScript to execute on client click if set, otherwise Null </description>
	/// </function>
	public function GetOnclick()
	{
		return $this->onclick;
	}

	/// <function container="gui/SMLinkButton" name="SetPostBack" access="public">
	/// 	<description> Set value indicating whether to perform post back on client click or not </description>
	/// 	<param name="value" type="boolean"> True to perform post back on client click, False not to </param>
	/// </function>
	public function SetPostBack($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->postBack = $value;
	}

	/// <function container="gui/SMLinkButton" name="GetPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if link performs post back on client click, False otherwise </description>
	/// </function>
	public function GetPostBack()
	{
		return $this->postBack;
	}

	/// <function container="gui/SMLinkButton" name="PerformedPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if link button performed post back, otherwise False </description>
	/// </function>
	public function PerformedPostBack()
	{
		return (SMEnvironment::GetPostValue("SMPostBackControl") === $this->GetClientId());
	}

	/// <function container="gui/SMLinkButton" name="SetRender" access="public">
	/// 	<description> Set value indicating whether to render link button or not </description>
	/// 	<param name="value" type="boolean"> Set True to have link button rendered, False not to </param>
	/// </function>
	public function SetRender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->doRender = $value;
	}

	/// <function container="gui/SMLinkButton" name="GetRender" access="public" returns="boolean">
	/// 	<description> Returns True if link button is to be rendered, False otherwise </description>
	/// </function>
	public function GetRender()
	{
		return $this->doRender;
	}

	/// <function container="gui/SMLinkButton" name="Render" access="public" returns="string">
	/// 	<description> Returns HTML representation of link button </description>
	/// </function>
	public function Render()
	{
		if ($this->doRender === false)
			return "";

		$output = "";

		if ($this->onclick !== null)
		{
			$this->onclick = trim($this->onclick);
			if ($this->postBack === true && substr($this->onclick, strlen($this->onclick) - 1) !== ";" && substr($this->onclick, strlen($this->onclick) - 1) !== "}")
				$this->onclick = $this->onclick . ";";
		}

		$postBack = (($this->postBack === true) ? "SMDom.SetAttribute('SMPostBackControl', 'value', '" . $this->GetClientId() . "');smFormPostBack()" : "");

		$onclick = (($this->onclick !== null) ? $this->onclick : "") . $postBack;
		$desc = (($this->description !== null) ? " title=\"" . SMStringUtilities::HtmlEncode($this->description) . "\"" : "");
		$output .= "<span id=\"" . $this->GetClientId() . "\" onclick=\"" . $onclick . "\"" . $desc . " style=\"cursor: pointer; white-space: nowrap\" class=\"SMLinkButton\">";

		if ($this->icon !== null)
			$output .= "<img src=\"" . $this->icon . "\" alt=\"\" style=\"vertical-align: middle; margin-right: 3px\">";

		if ($this->fontIcon !== null)
			$output .= "<span class=\"fa " . ((strpos(strtolower($this->fontIcon), "fa-") === false) ? "fa-" : "") . strtolower($this->fontIcon) . "\" style=\"margin-right: 0.3em;\"></span>";

		if ($this->title !== null)
			$output .= "<a>" . SMStringUtilities::HtmlEncode($this->title) . "</a>"; // Wrapped in link to obtain look and feel
			//$output .= "<a href=\"javascript:void(0)\">" . SMStringUtilities::HtmlEncode($this->title) . "</a>"; // IE7/IE8 bug: javascript:void(0) in href attribute causes OnBeforeUnload messages to show twice when canceling postback

		$output .= "</span>";

		return $output;
	}
}

?>
