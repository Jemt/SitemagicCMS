<?php

/// <container name="base/SMExtension">
/// 	Base class for all extensions running within the application. Extensions must inherit from
/// 	SMExtension in order to work.
///
/// 	Extensions are found in the extensions folder on the server. Each extension is installed
/// 	in its own subfolder. The name of the folder becomes the internal name of the extension
/// 	used throughout the system.
/// 	To allow an extension to participate in the life cycle, a file named Main.class.php must
/// 	be found in the extension folder. This file must define a class inheriting from SMExtension.
/// 	The class must have the exact same name as the folder in which it resides - the name of
/// 	the extension.
///
/// 	An instance of SMContext is available through $this->context, providing access to the
/// 	underlaying template, form element and more. See base/SMContext for more information.
///
/// 	Extensions enabled will have their life cycle functions executed. The functions allow each
/// 	extension to interact with the application and other extensions during different stages of the
/// 	life cycle. Simply override a life cycle function to add functionality.
///
/// 	An extension may prepare data or add functionality to the application during e.g. PreInit, which
/// 	may be consumed by other extensions during Init or later.
///
/// 	Each stage of the life cycle is executed for all extensions enabled, before the next stage
/// 	is started. The life cycle is executed in the following order:
///
/// 	1) PreInit
/// 	2) Init
/// 	-- Application makes design template (SMTemplate) and form instance (SMForm) available to extensions
/// 	3) InitComplete
/// 	4) PreRender
/// 	5) Render (only executed by privileged extension)
/// 	6) RenderComplete
/// 	7) PreTemplateUpdate
/// 	-- System adds Form element to template
/// 	-- System adds output from privileged extension to template
/// 	8) TemplateUpdateComplete
/// 	9) PreOutput
/// 	-- Place holders and repeating blocks not replaced are removed
/// 	-- Template including changes is written to client
/// 	10) OutputComplete
/// 	11) Unload
/// 	--- Uncommitted data sources are committed (including SMAttributes)
/// 	12) Finalize
///
/// 	Notice that the application allows only one extension to construct output to the content area.
/// 	This is done by the privileged extension which returns the desired output from the Render()
/// 	function. The privileged extension is determined by the query string parameter SMExt.
/// 	E.g. index.php?SMExt=SMLogin renders the login dialog from the SMLogin extension in the
/// 	content area.
///
/// 	Other extensions may manipulate the output using the instance of SMTemplate accessible
/// 	through $this->context->GetTemplate(). See base/SMTemplate for more information.
///
/// 	An extension being executed privileged may be executed in two different modes.
/// 	- Shared execution mode allow other extensions to participate in the life cycle (most common)
/// 	- Dedicated execution mode results in only privileged extension being executed. This is mainly
/// 	used when the extension needs to display something in a pop up window, and does not depend
/// 	on other extensions. Override GetExecutionModes() to enable support for dedicated execution mode.
///
/// 	$this->context->GetExecutionMode() will reveal current execution mode.
/// 	See base/SMExecutionMode for more information.
/// </container>
class SMExtension
{
	protected $context;
	protected $integrated;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->integrated = false;
	}

	/// <function container="base/SMExtension" name="SetIsIntegrated" access="public">
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

	/// <function container="base/SMExtension" name="GetIsIntegrated" access="public" returns="boolean">
	/// 	<description>
	///			Returns value indicating whether extension executing is considered integrated (see SetIsIntegrated(..) for details).
	/// 	</description>
	/// </function>
	public function GetIsIntegrated() // Must be public - called by SMController
	{
		return $this->integrated;
	}

	/// <function container="base/SMExtension" name="GetContext" access="public" returns="SMContext">
	/// 	<description>
	///			Returns SMContext object providing access to design template (SMTemplate), form element
	/// 		instance (SMForm), name of currently running extension, execution mode, and template type.
	/// 		See SMContext documentation for more information.
	/// 	</description>
	/// </function>
	public function GetContext() // Must be public - called by SMController
	{
		return $this->context;
	}

	// Life cycle

	/// <function container="base/SMExtension" name="GetExecutionModes" access="public" virtual="true" returns="SMExecutionMode[]">
	/// 	<description>
	/// 		Returns supported execution modes.
	/// 		Default implementation returns SMExecutionMode::$Shared only.
	/// 		Dedicated execution mode often requires additional logic to handle
	/// 		e.g. dependencies, if an extension depends upon other extensions.
	/// 		Most functionality provided by other extensions will not work
	/// 		when running in Dedicated execution mode, hence conditional usage
	/// 		is required.
	/// 	</description>
	/// </function>
	public function GetExecutionModes()
	{
		return array(SMExecutionMode::$Shared);
	}

	/// <function container="base/SMExtension" name="PreInit" access="public" virtual="true">
	/// 	<description>
	/// 		The initilization events are commenly used by extensions to interact
	/// 		with each other. Resources, data, and functionality is made ready during PreInit,
	/// 		and may be consumed during Init or InitComplete, or even later using one of
	/// 		the other life cycle events.
	/// 	</description>
	/// </function>
	public function PreInit()
	{
	}

	/// <function container="base/SMExtension" name="Init" access="public" virtual="true">
	/// 	<description> See PreInit() description </description>
	/// </function>
	public function Init()
	{
	}

	/// <function container="base/SMExtension" name="InitComplete" access="public" virtual="true">
	/// 	<description> See PreInit() description </description>
	/// </function>
	public function InitComplete()
	{
	}

	/// <function container="base/SMExtension" name="PreRender" access="public" virtual="true">
	/// 	<description>
	/// 		PreRender executes before privileged extension executes.
	/// 		This is the last chance to interact with the privileged extension
	/// 		before it renders its content.
	/// 	</description>
	/// </function>
	public function PreRender()
	{
	}

	/// <function container="base/SMExtension" name="Render" access="public" virtual="true" returns="string">
	/// 	<description>
	/// 		The privileged extension is responsible for constructing the primary output to
	/// 		the content area, which is returned from this function (usually HTML/GUI).
	/// 		The Render() function is only invoked on the privileged extension. The output
	/// 		returned replaces the {[Extension]} place holder within the template, after
	/// 		the PreTemplateUpdate stage of the life cycle.
	/// 		The privileged extension is defined in the query string parameter SMExt.
	/// 	</description>
	/// </function>
	public function Render()
	{
		return "";
	}

	/// <function container="base/SMExtension" name="RenderComplete" access="public" virtual="true">
	/// 	<description>
	/// 		Executes after the privileged extension has rendered its output.
	/// 	</description>
	/// </function>
	public function RenderComplete()
	{
	}

	/// <function container="base/SMExtension" name="PreTemplateUpdate" access="public" virtual="true">
	/// 	<description>
	/// 		Executes before the template is manipulated by the system.
	/// 		The Form element is added to the template after this stage, making
	/// 		this the last chance to make changes to the SMForm instance accessible
	/// 		through $this->context->GetForm(). See base/SMForm for more information.
	/// 		The output from the privileged extension replaces the {[Extension]} place holder
	/// 		after this stage.
	/// 	</description>
	/// </function>
	public function PreTemplateUpdate()
	{
	}

	// Template is filled with content from the privileged extension

	/// <function container="base/SMExtension" name="TemplateUpdateComplete" access="public" virtual="true">
	/// 	<description> Executes after template has been updated by the system </description>
	/// </function>
	public function TemplateUpdateComplete()
	{
	}

	/// <function container="base/SMExtension" name="PreOutput" access="public" virtual="true">
	/// 	<description>
	/// 		Executes before the template is transfered to the client, making this
	/// 		the last chance to make changes to the template accessible through
	/// 		$this->context->GetTemplate(). See base/SMTemplate for more information.
	/// 		Place holders and repeating blocks that have not yet been
	/// 		replaced, are removed after this stage.
	/// 		Changes to header information cannot occure past this point either.
	/// 	</description>
	/// </function>
	public function PreOutput()
	{
	}

	// Template content is transfered to client (echo)

	/// <function container="base/SMExtension" name="OutputComplete" access="public" virtual="true">
	/// 	<description> Executes after template has been transfered to client </description>
	/// </function>
	public function OutputComplete()
	{
	}

	/// <function container="base/SMExtension" name="Unload" access="public" virtual="true">
	/// 	<description>
	/// 		Executes before system prepares to finalize.
	/// 		Uncommitted data sources are automatically committed after this stage,
	/// 		including the attribute collection - see base/SMIDataSource and
	/// 		base/SMAttributes for more information.
	/// 	</description>
	/// </function>
	public function Unload()
	{
	}

	// Data is being committed to data sources

	/// <function container="base/SMExtension" name="Finalize" access="public" virtual="true">
	/// 	<description>
	/// 		Executes after uncommitted data sources have been committed.
	/// 		This is the last chance to execute logic before control returns
	/// 		to the client.
	/// 	</description>
	/// </function>
	public function Finalize()
	{
	}

	// Other events not related to life cycle

	/// <function container="base/SMExtension" name="Enabled" access="public" virtual="true">
	/// 	<description> Executes when extension has been enabled </description>
	/// </function>
	public function Enabled()
	{
	}

	/// <function container="base/SMExtension" name="Disabled" access="public" virtual="true">
	/// 	<description> Executes when extension has been disabled </description>
	/// </function>
	public function Disabled()
	{
	}
}

?>
