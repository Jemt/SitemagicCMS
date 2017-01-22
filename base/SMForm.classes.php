<?php

/// <container name="base/SMForm">
/// 	Represents &lt;form&gt; element in web application.
/// 	The SMForm class is used to generate a global HTML form element in the template,
/// 	in which all GUI controls are added. This makes it possible for e.g. input form
/// 	elements to automatically restore their values after post back.
///
/// 	The form element is accessible from extensions through $this->context->GetForm().
/// 	See base/SMExtension for more information.
/// </container>
class SMForm
{
	private $render;		// bool
	private $contentType;	// SMFormContentType

	public function __construct()
	{
		$this->render = true;
		$this->contentType = SMFormContentType::$Default;
	}

	/// <function container="base/SMForm" name="SetRender" access="public">
	/// 	<description> Enable or disable rendering of form element </description>
	/// 	<param name="value" type="boolean"> Set True to have form element render, False not to </param>
	/// </function>
	public function SetRender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->render = $value;
	}

	/// <function container="base/SMForm" name="GetRender" access="public" returns="boolean">
	/// 	<description> Returns True if form element is to be rendered, otherwise False </description>
	/// </function>
	public function GetRender()
	{
		return $this->render;
	}

	/// <function container="base/SMForm" name="SetContentType" access="public">
	/// 	<description> Set form content type (how data is encoded - enctype attribute) </description>
	/// 	<param name="type" type="SMFormContentType"> Specify content type </param>
	/// </function>
	public function SetContentType($type)
	{
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMFormContentType", $type) === false)
			throw new Exception("Invalid content type '" . $type . "' specified - use SMFormContentType::Type");

		$this->contentType = $type;
	}

	/// <function container="base/SMForm" name="GetContentType" access="public" returns="SMFormContentType">
	/// 	<description> Returns form content type (how data is encoded - enctype attribute) </description>
	/// </function>
	public function GetContentType()
	{
		return $this->contentType;
	}

	/// <function container="base/SMForm" name="PostBack" access="public" returns="boolean">
	/// 	<description> Returns True if a post back was performed, otherwise False </description>
	/// </function>
	public function PostBack()
	{
		return (SMEnvironment::GetEnvironmentValue("REQUEST_METHOD") === "POST");
	}

	public function RenderStart()
	{
		if ($this->render === false)
			return "";

		$multipart = (($this->contentType === SMFormContentType::$MultiPart) ? " enctype=\"multipart/form-data\"" : "");

		return "<form id=\"SMForm\" action=\"" . SMStringUtilities::HtmlEncode(SMEnvironment::GetCurrentUrl(true, true)) . "\" method=\"post\"" . $multipart . " style=\"margin: 0px\">";
		// accept-charset attribute not specified - determined based on Content-Type header set in SMController.
		// Invalid characters are converted to HEX entities by browser - http://en.wikipedia.org/wiki/Unicode_and_HTML
	}

	public function RenderEnd()
	{
		if ($this->render === false)
			return "";

		return "<div><input id=\"SMPostBackControl\" type=\"hidden\" name=\"SMPostBackControl\"></div>\n</form>"; // Input enclosed in div to satisfy W3C validator
	}
}

/// <container name="base/SMFormContentType">
/// 	Enum that represents &lt;form&gt; content type (how data is encoded - enctype attribute)
/// </container>
class SMFormContentType
{
	/// <member container="base/SMFormContentType" name="Default" access="public" static="true" type="string" default="Default">
	/// 	<description> Default encoding </description>
	/// </member>
	public static $Default = "Default";

	/// <member container="base/SMFormContentType" name="MultiPart" access="public" static="true" type="string" default="MultiPart">
	/// 	<description> Multi part encoding used when transfering binary data (file uploads) </description>
	/// </member>
	public static $MultiPart = "MultiPart";
}

?>
