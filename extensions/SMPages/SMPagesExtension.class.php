<?php

/// <container name="SMPages/SMPagesExtension">
/// 	Base class for extensions running within content pages.
///
/// 	Content Page Extensions may be added directly to content pages
/// 	using the WYSIWYG editor.
///
/// 	To build a Content Page Extension, simply inherit from SMPagesExtension,
/// 	and override the Render function which is responsible for returning
/// 	the client code (HTML/CSS/JavaScript).
///
/// 	The Content Page Extension should be registered with the SMPages extension.
/// 	See SMPagesExtensionList class for more information.
/// </container>
class SMPagesExtension
{
	/// <member container="SMPages/SMPagesExtension" name="context" access="protected" type="SMContext">
	/// 	<description>
	/// 		Instance of SMContext which provides access to the underlaying template,
	/// 		the underlaying form element, and information about execution mode.
	/// 		See SMContext class for more information.
	/// 	</description>
	/// </member>
	protected $context;

	/// <member container="SMPages/SMPagesExtension" name="pageId" access="protected" type="string">
	/// 	<description> Unique Page ID </description>
	/// </member>
	protected $pageId;

	/// <member container="SMPages/SMPagesExtension" name="instanceId" access="protected" type="integer">
	/// 	<description> Content Page Extension instance ID </description>
	/// </member>
	protected $instanceId;

	/// <member container="SMPages/SMPagesExtension" name="argument" access="protected" type="string">
	/// 	<description>
	/// 		Argument passed to Content Page Extension instance.
	/// 		The value of this argument is determined when registering
	/// 		the Content Page Extension. This allows for the same Content Page Extension
	/// 		to be registered multiple times, but with different behaviour given by the
	/// 		argument passed.
	/// 	</description>
	/// </member>
	protected $argument;

	protected $integrated;

	public function __construct(SMContext $context, $pageId, $instanceId, $arg)
	{
		SMTypeCheck::CheckObject(__METHOD__, "pageId", $pageId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "arg", $arg, SMTypeCheckType::$String);

		$this->context = $context;
		$this->pageId = $pageId;
		$this->instanceId = $instanceId;
		$this->argument = $arg;
		$this->integrated = false;

		$this->Init();
	}

	/// <function container="SMPages/SMPagesExtension" name="Init" access="public" virtual="true">
	/// 	<description> Optionally override to perform initialization when instance of Content Page Extension is created </description>
	/// </function>
	public function Init()
	{
	}

	/// <function container="SMPages/SMPagesExtension" name="Render" access="public" virtual="true" returns="string">
	/// 	<description> Optionally override to render and return content for Content Page Extension </description>
	/// </function>
	public function Render()
	{
		return "";
	}

	/// <function container="SMPages/SMPagesExtension" name="SetIsIntegrated" access="public">
	/// 	<description>
	///			Set value indicating whether extension executing is considered part of the overall look and feel, and behaviour of Sitemagic.
	/// 		Setting this value may cause Sitemagic to apply specific styling to create a common look and feel for certain elements.
	/// 		Default value is False.
	/// 	</description>
	/// 	<param name="val" type="boolean"> Value indicating whether extension is considered integrated </param>
	/// </function>
	public function SetIsIntegrated($val) // Made public for consistency (GetIsIntegrated is public)
	{
		SMTypeCheck::CheckObject(__METHOD__, "val", $val, SMTypeCheckType::$Boolean);
		$this->integrated = $val;
	}

	/// <function container="SMPages/SMPagesExtension" name="GetIsIntegrated" access="public" returns="boolean">
	/// 	<description>
	///			Returns value indicating whether extension executing is considered integrated (see SetIsIntegrated(..) for details).
	/// 	</description>
	/// </function>
	public function GetIsIntegrated() // Must be public - called by FrmViewer.class.php
	{
		return $this->integrated;
	}
}

?>
