<?php

/// <container name="gui/SMGrid">
/// 	Grid control used to represent data in a table like structure
/// 	with built-in row selector.
///
/// 	$data = array(); // Record set
///
/// 	// Adding rows/records - must be indexed with names of columns
/// 	$data[] = new array(
/// 		&#160;&#160;&#160;&#160; &quot;First name&quot; => &quot;Casper&quot;,
/// 		&#160;&#160;&#160;&#160; &quot;Last name&quot; => &quot;Mayfield&quot;,
/// 		&#160;&#160;&#160;&#160; &quot;Title&quot; => &quot;Developer&quot;
/// 	);
/// 	$data[] = new array(
/// 		&#160;&#160;&#160;&#160; &quot;First name&quot; => &quot;Emiley&quot;,
/// 		&#160;&#160;&#160;&#160; &quot;Last name&quot; => &quot;Jacobsen&quot;,
/// 		&#160;&#160;&#160;&#160; &quot;Title&quot; => &quot;Account manager&quot;
/// 	);
///
/// 	$grid = new SMGrid(&quot;MyExtensionPersons&quot;);
/// 	$grid->SetData($data);
/// 	$grid->EnableSelector();
///
/// 	$index = $grid->GetSelectedId(); // Returns selected ID after post back if selection has been made
/// </container>
class SMGrid
{
	private $id;
	private $data;
	private $valueField;
	private $selectedId;
	private $restoreSelection;
	private $autoPostBack;
	private $autoNewLine;
	private $doRender;

	/// <function container="gui/SMGrid" name="__construct" access="public">
	/// 	<description> Create instance of SMGrid </description>
	/// 	<param name="id" type="string"> Unique ID identifying grid </param>
	/// </function>
	public function __construct($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);

		$this->id = $id;
		$this->data = array();
		$this->valueField = null;
		$this->selectedId = ((SMEnvironment::GetPostValue("SMGridSelected" . $this->id) !== "") ? SMEnvironment::GetPostValue("SMGridSelected" . $this->id) : null);
		$this->restoreSelection = true;
		$this->autoPostBack = false;
		$this->autoNewLine = false;
		$this->doRender = true;
	}

	/// <function container="gui/SMGrid" name="GetId" access="public" returns="string">
	/// 	<description> Get component ID </description>
	/// </function>
	public function GetId()
	{
		return $this->id;
	}

	/// <function container="gui/SMGrid" name="GetClientId" access="public" returns="string">
	/// 	<description> Get component client ID </description>
	/// </function>
	public function GetClientId()
	{
		return "SMGrid" . $this->id;
	}

	/// <function container="gui/SMGrid" name="SetData" access="public">
	/// 	<description> Set data to be displayed in grid </description>
	/// 	<param name="data" type="string[][]">
	/// 		Argument must be a multi dimentional array such as this:
	///
	/// 		$data = array(); // Record set
	///
	/// 		// Adding rows/records - must be indexed with names of columns
	/// 		$data[] = new array(
	/// 			&quot;First name&quot; => &quot;Casper&quot;,
	/// 			&quot;Last name&quot; => &quot;Mayfield&quot;,
	/// 			&quot;Title&quot; => &quot;Developer&quot;
	/// 		);
	/// 		$data[] = new array(
	/// 			&quot;First name&quot; => &quot;Emiley&quot;,
	/// 			&quot;Last name&quot; => &quot;Jacobsen&quot;,
	/// 			&quot;Title&quot; => &quot;Account manager&quot;
	/// 		);
	///
	/// 		The record set array represents multiple rows in the grid.
	/// 		Each element in the record set represents a row - it must be an associative array.
	/// 		Each element in a row represents data for a specific column (given by its key).
	/// 		Values in a row must be either strings or objects implementing __toString().
	///
	/// 		An optional ID may be set for each row using the following construction:
	///
	/// 		$data[&quot;BrianHays&quot;] = new array(
	/// 			&quot;First name&quot; => &quot;Brian&quot;,
	/// 			&quot;Last name&quot; => &quot;Hays&quot;,
	/// 			&quot;Title&quot; => &quot;Sales agent&quot;
	/// 		);
	///
	/// 		The ID set (BrianHays) will be returned from the GetSelectedId() function.
	/// 		If no ID is set, the index of the given row will be returned (as a string).
	/// 		First row will have ID "0", next row ID "1" and so forth.
	/// 	</param>
	/// </function>
	public function SetData($data)
	{
		// Notice that CheckArray is not used, to check that array contains only strings.
		// The reason for this is simple; objects may implement the __toString function,
		// allowing them to be printed
		SMTypeCheck::CheckObject(__METHOD__, "data", $data, SMTypeCheckType::$Array);
		$this->data = $data;
	}

	/// <function container="gui/SMGrid" name="GetData" access="public" returns="string[][]">
	/// 	<description> Get data set using SetData(..) </description>
	/// </function>
	public function GetData()
	{
		return $this->data;
	}

	/// <function container="gui/SMGrid" name="SetSelectedId" access="public">
	/// 	<description> Initially select row with specified ID </description>
	/// 	<param name="id" type="string"> Unique ID identifying row to select </param>
	/// </function>
	public function SetSelectedId($id)
	{
		SMTypeCheck::CheckObject(__METHOD__, "id", $id, SMTypeCheckType::$String);
		$this->selectedId = $id;
	}

	/// <function container="gui/SMGrid" name="ResetSelectedId" access="public">
	/// 	<description> Remove selection </description>
	/// </function>
	public function ResetSelectedId()
	{
		$this->selectedId = null;
	}

	/// <function container="gui/SMGrid" name="GetSelectionMade" access="public" returns="boolean">
	/// 	<description> Returns True if a selection has been made, otherwise False </description>
	/// </function>
	public function GetSelectionMade()
	{
		return ($this->selectedId !== null);
	}

	/// <function container="gui/SMGrid" name="GetSelectedId" access="public" returns="string">
	/// 	<description> Get ID from selected row - returns Null if no selection has been made </description>
	/// </function>
	public function GetSelectedId()
	{
		if ($this->selectedId === null || isset($this->data[$this->selectedId]) === false)
			return null;

		return $this->selectedId;
	}

	/// <function container="gui/SMGrid" name="GetSelectedValue" access="public" returns="string">
	/// 	<description>
	/// 		Get value from selected row - returns Null if no selection has been made.
	/// 		Selector must have been configured with name of column to get value from.
	/// 		Otherwise Null is returned. See EnableSelector(..) function for more information.
	/// 	</description>
	/// </function>
	public function GetSelectedValue()
	{
		if ($this->selectedId === null || $this->valueField === "" || isset($this->data[$this->selectedId]) === false) // $this->valueField == "": Only GetSelectedId can be used
			return null;

		if (isset($this->data[$this->selectedId][$this->valueField]) === false)
			throw new Exception("Value field '" . $this->valueField . "' not found in data collection");

		return $this->data[$this->selectedId][$this->valueField];
	}

	/// <function container="gui/SMGrid" name="EnableSelector" access="public">
	/// 	<description> Enable selector to allow row selections </description>
	/// 	<param name="valueField" type="string" default="String.Empty">
	/// 		Optionally specify name of column, to allow values from this column
	/// 		to be returned from the GetSelectedValue() function. If no column
	/// 		is specified, GetSelectedId() can be used to identify the selected row.
	/// 	</param>
	/// </function>
	public function EnableSelector($valueField = "") // $valueField empty = GetSelectedId() can be used, GetSelectedValue() can't
	{
		SMTypeCheck::CheckObject(__METHOD__, "valueField", $valueField, SMTypeCheckType::$String);
		$this->valueField = $valueField;
	}

	/// <function container="gui/SMGrid" name="DisableSelector" access="public">
	/// 	<description> Disable selector </description>
	/// </function>
	public function DisableSelector()
	{
		$this->valueField = null;
	}

	/// <function container="gui/SMGrid" name="GetSelectorEnabled" access="public" returns="boolean">
	/// 	<description> Returns True if selector has been enabled, otherwise False </description>
	/// </function>
	public function GetSelectorEnabled()
	{
		return ($this->valueField !== null);
	}

	/// <function container="gui/SMGrid" name="SetRestoreSelection" access="public">
	/// 	<description> Set whether to restore selection after post back (selection is restored by default) </description>
	/// 	<param name="value" type="boolean"> Set True to restore (keep) selection after post back, False not to </param>
	/// </function>
	public function SetRestoreSelection($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->restoreSelection = $value;
	}

	/// <function container="gui/SMGrid" name="GetRestoreSelection" access="public" returns="boolean">
	/// 	<description> Returns True if selection is restored (kept) after post back, False if not </description>
	/// </function>
	public function GetRestoreSelection()
	{
		return $this->restoreSelection;
	}

	/// <function container="gui/SMGrid" name="SetAutoPostBack" access="public">
	/// 	<description>
	/// 		Set True to have grid post back automatically when a row is selected,
	/// 		False not to. Grid does not automatically post back by default.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable automatic post back, False not to </param>
	/// </function>
	public function SetAutoPostBack($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->autoPostBack = $value;
	}

	/// <function container="gui/SMGrid" name="GetAutoPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if grid performs post back when a row is selected, False otherwise </description>
	/// </function>
	public function GetAutoPostBack()
	{
		return $this->autoPostBack;
	}

	/// <function container="gui/SMGrid" name="SetNewLineToLineBreak" access="public">
	/// 	<description> Set True to have grid automatically replace line breaks with HTML line breaks, False not to </description>
	/// 	<param name="value" type="boolean"> Set True to have line breaks replaced with HTML line breaks, False not to </param>
	/// </function>
	public function SetNewLineToLineBreak($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->autoNewLine = $value;
	}

	/// <function container="gui/SMGrid" name="GetNewLineToLineBreak" access="public" returns="boolean">
	/// 	<description> Returns True if grid is set to automatically replace line breaks with HTML line breaks, otherwise False </description>
	/// </function>
	public function GetNewLineToLineBreak()
	{
		return $this->autoNewLine;
	}

	/// <function container="gui/SMGrid" name="PerformedPostBack" access="public" returns="boolean">
	/// 	<description> Returns True if grid performed post back, False otherwise </description>
	/// </function>
	public function PerformedPostBack()
	{
		return (SMEnvironment::GetPostValue("SMPostBackControl") === "SMGrid" . $this->id);
	}

	/// <function container="gui/SMGrid" name="SetRender" access="public">
	/// 	<description> Enable or disable rendering of grid </description>
	/// 	<param name="value" type="boolean"> True to have grid rendered, False not to </param>
	/// </function>
	public function SetRender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->doRender = $value;
	}

	/// <function container="gui/SMGrid" name="GetRender" access="public" returns="boolean">
	/// 	<description> Returns True if grid is to be rendered, False otherwise </description>
	/// </function>
	public function GetRender()
	{
		return $this->doRender;
	}

	/// <function container="gui/SMGrid" name="Render" access="public" returns="string">
	/// 	<description> Returns HTML representation of grid (table like structure) </description>
	/// </function>
	public function Render()
	{
		if ($this->doRender === false || count($this->data) === 0)
			return "";

		$output = "";

		if ($this->valueField !== null)
		{
			$output .= "
			<input type=\"hidden\" name=\"SMGridSelected" . $this->id . "\" value=\"\" id=\"SMGridSelected" . $this->id . "\">
			<script type=\"text/javascript\">";

			if ($this->restoreSelection === true && $this->GetSelectedId() !== null)
				$output .= "SMEventHandler.AddEventHandler(window, 'load', smGridPreSelect" . $this->id . ")";

			$output .= "
			var smGridSelectedValue = SMDom.GetAttribute('SMGridSelected" . $this->id . "', 'value');
			var smGridAutoPostBack" . $this->id . " = " . (($this->autoPostBack === true) ? "true" : "false") . ";

			function smGridSelect" . $this->id . "(id, ignoreAutoPostBack)
			{
				if (smGridSelectedValue !== '')
				{
					SMDom.SetAttribute('SMGridSelectionImg" . $this->id . "' + smGridSelectedValue, 'src', 'base/gui/SMGrid/images/selection.gif');
					SMDom.SetStyle('SMGridSelectionImg" . $this->id . "' + smGridSelectedValue, 'visibility', 'hidden');
				}

				SMDom.SetAttribute('SMGridSelected" . $this->id . "', 'value', id);

				SMDom.SetAttribute('SMGridSelectionImg" . $this->id . "' + id, 'src', 'base/gui/SMGrid/images/selected.gif');
				SMDom.SetStyle('SMGridSelectionImg" . $this->id . "' + id, 'visibility', 'visible');

				smGridSelectedValue = SMDom.GetAttribute('SMGridSelected" . $this->id . "', 'value');

				if (smGridAutoPostBack" . $this->id . " === true && ignoreAutoPostBack === false)
				{
					SMDom.SetAttribute('SMPostBackControl', 'value', 'SMGrid" . $this->id . "');
					smFormPostBack();
				}
			}

			function smGridHighlight" . $this->id . "(id, action)
			{
				if (action === 'show')
				{
					SMDom.SetStyle('SMGridSelectionImg" . $this->id . "' + id, 'visibility', 'visible');
				}
				else
				{
					if (smGridSelectedValue !== id)
						SMDom.SetStyle('SMGridSelectionImg" . $this->id . "' + id, 'visibility', 'hidden');
				}
			}";

			if ($this->restoreSelection === true && $this->selectedId !== null && $this->GetSelectedValue() !== null)
			{
				$output .= "
				function smGridPreSelect" . $this->id . "()
				{
					smGridSelect" . $this->id . "('" . $this->selectedId . "', true);
				}
				";
			}

			$output .= "</script>";
		}

		$columns = array();
		foreach ($this->data as $data)
		{
			foreach ($data as $key => $value)
				if (in_array($key, $columns, true) === false)
					$columns[] = $key;
		}

		$output .= "<table id=\"" . $this->GetClientId() . "\" class=\"SMGrid\"><tr>";

		if ($this->valueField !== null) // true if selector is enabled
			$output .= "<td style=\"width: 25px; text-align: center\"><img src=\"base/gui/SMGrid/images/selection.gif\" alt=\"\"></td>";

		foreach ($columns as $column)
			$output .= "<td><b>" . $column . "</b></td>";
		$output .= "</tr>";

		$js = "";

		foreach ($this->data as $key => $row)
		{
			if ($this->valueField !== null) // true if selector is enabled
				$js = "onmouseover=\"smGridHighlight" . $this->id . "('" . $key . "', 'show')\" onmouseout=\"smGridHighlight" . $this->id . "('" . $key . "', 'hide')\" onclick=\"smGridSelect" . $this->id . "('" . $key . "', false)\"";

			$output .= "<tr" . (($this->valueField !== null) ? " style=\"cursor: pointer\"" : "") . ">";

			if ($this->valueField !== null) // true if selector is enabled
				$output .= "<td style=\"text-align: center\" " . $js . "><img id=\"SMGridSelectionImg" . $this->id . $key . "\" src=\"base/gui/SMGrid/images/selection.gif\" alt=\"\" style=\"visibility: hidden\"></td>";

			foreach ($columns as $column)
				$output .= "<td " . $js . ">" . ((isset($row[$column]) === true) ? (($this->autoNewLine === true) ? str_replace("\n", "<br>", str_replace("\r", "", $row[$column])) : $row[$column]) : "") . "</td>";

			$output .= "</tr>";
		}

		$output .= "
		</table>
		";

		return $output;
	}
}

?>
