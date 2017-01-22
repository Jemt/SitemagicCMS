<?php

/// <container name="base/SMContext">
/// 	An instance of SMContext is passed to running extensions, allowing easy
/// 	access to the underlaying design template and the form element (after the Init stage).
/// 	The object also provides the name of the currently running extension, the given
/// 	execution mode, and template type.
/// 	The context object is available from extensions through $this->context.
///
/// 	$template = $this->context->GetTemplate();
/// 	$template->ReplaceTag(new SMKeyValue("VisitorsPlaceHolder", "Visitors today: " . $visitors));
/// </container>
class SMContext
{
	private $extensionName;
	private $template;
	private $form;
	private $execMode;
	private $templateType;

	public function __construct($extensionName, $execMode, $templateType)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extensionName", $extensionName, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "execMode", $execMode, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "templateType", $templateType, SMTypeCheckType::$String);

		if (property_exists("SMExecutionMode", $execMode) === false)
			throw new Exception("Invalid execution mode '" . $execMode . "' specified - use SMExecutionMode::Mode");

		if (property_exists("SMTemplateType", $templateType) === false)
			throw new Exception("Invalid template type '" . $templateType . "' specified - use SMTemplateType::Type");

		$this->extensionName = $extensionName;
		$this->template = null;
		$this->form = null;
		$this->execMode = $execMode;
		$this->templateType = $templateType;
	}

	/// <function container="base/SMContext" name="GetExtensionName" access="public" returns="string">
	/// 	<description> Get name of extension running. This is the name of the folder containing the extension. </description>
	/// </function>
	public function GetExtensionName()
	{
		return $this->extensionName;
	}

	/// <function container="base/SMContext" name="GetTemplate" access="public" returns="SMTemplate">
	/// 	<description> Instance of SMTemplate allowing extension to manipulate output </description>
	/// </function>
	public function GetTemplate()
	{
		return $this->template;
	}

	public function SetTemplate(SMTemplate $t) // Used by SMController only to set SMTemplate instance when ready
	{
		$this->template = $t;
	}

	/// <function container="base/SMContext" name="GetForm" access="public" returns="SMForm">
	/// 	<description> Instance of SMForm responsible for transfering post back data from client to server </description>
	/// </function>
	public function GetForm()
	{
		return $this->form;
	}

	public function SetForm(SMForm $f) // Used by SMController only to set SMForm instance when ready
	{
		$this->form = $f;
	}

	/// <function container="base/SMContext" name="GetExecutionMode" access="public" returns="SMExecutionMode">
	/// 	<description>
	/// 		Get current execution mode.
	/// 		SMExecutionMode::$Shared - extension is being executed as part of ordinary life cycle.
	/// 		SMExecutionMode::$Dedicated - extension is being executed alone.
	/// 	</description>
	/// </function>
	public function GetExecutionMode()
	{
		return $this->execMode;
	}

	/// <function container="base/SMContext" name="GetTemplateType" access="public" returns="SMTemplateType">
	/// 	<description>
	/// 		Get template type.
	/// 		SMTemplateType::$Normal - normal template used for ordinary presentation (usually contains at least navigation and content area).
	/// 		SMTemplateType::$Basic - simple template mainly used for extensions running in Dedicated Executed Mode (e.g. without navigation).
	/// 	</description>
	/// </function>
	public function GetTemplateType()
	{
		return $this->templateType;
	}
}

?>
