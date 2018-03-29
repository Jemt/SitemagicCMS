<?php

/// <container name="base/SMConfiguration">
/// 	Configuration manager used to read and update configuration files
/// 	such as config.xml.php. Use file extension ".xml.php" to prevent
/// 	configuration file from being accessible client side.
///
/// 	$cfg = new SMConfiguration(dirname(__FILE__) . "/MyConfig.xml", true); // Open config file for writing
///
/// 	// Write configuration
/// 	$cfg->SetEntry("width", "400px");
/// 	$cfg->SetEntry("height", "250px");
/// 	$cfg->Commit();
///
/// 	The code above will produce a configuration file with the following content:
///
/// 	&lt;?xml version=&quot;1.0&quot; encoding=&quot;ISO-8859-1&quot;?&gt;
/// 	&lt;entries&gt;
/// 		&lt;entry key=&quot;width&quot; value=&quot;400px&quot; /&gt;
/// 		&lt;entry key=&quot;height&quot; value=&quot;250px&quot; /&gt;
/// 	&lt;/entries&gt;
///
/// 	// Read configuration
/// 	$width = $cfg->GetEntry("width");
/// 	$height = $cfg->GetEntry("height");
/// </container>
class SMConfiguration
{
	private $xml;		// SimpleXMLElement

	private $file;		// String
	private $dom;		// DOMDocument
	private $writable;	// Boolean
	private $dirty;		// Boolean

	/// <function container="base/SMConfiguration" name="__construct" access="public">
	/// 	<description> Create instance of SMConfiguration </description>
	/// 	<param name="file" type="string"> Path to configuration file </param>
	/// 	<param name="writable" type="boolean" default="false"> True to make configuration writable, otherwise False </param>
	/// </function>
	public function __construct($file, $writable = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "file", $file, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "writable", $writable, SMTypeCheckType::$Boolean);

		$this->file = $file;
		$this->writable = $writable;
		$this->dirty = false;

		$xml = "";

		if (file_exists($file) === true)
		{
			$fileReader = new SMTextFileReader($file);
			$xml = $fileReader->ReadAll();
			$xml = trim($xml);
		}
		else
		{
			$xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<entries />";
			$this->dirty = true;
		}

		if (strpos($xml, "<?php exit(); ?>") !== false)
		{
			$xml = str_replace("<?php exit(); ?>", "", $xml);
			$xml = trim($xml);
		}

		if ($writable === false)
		{
			$this->xml = new SimpleXMLElement($xml);
			$this->dom = null;
		}
		else
		{
			$this->dom = new DOMDocument();
			$this->dom->loadXML($xml);
			$this->xml = null;
		}
	}

	/// <function container="base/SMConfiguration" name="GetEntry" access="public" returns="string">
	/// 	<description> Get configuration value. Returns value if found, otherwise Null. </description>
	/// 	<param name="entry" type="string"> Configuration entry name </param>
	/// </function>
	public function GetEntry($entry)
	{
		SMTypeCheck::CheckObject(__METHOD__, "entry", $entry, SMTypeCheckType::$String);
		return $this->getValue($entry);
	}

	/// <function container="base/SMConfiguration" name="GetEntryOrEmpty" access="public" returns="string">
	/// 	<description> Get configuration value. Returns value if found, otherwise an empty string. </description>
	/// 	<param name="entry" type="string"> Configuration entry name </param>
	/// </function>
	public function GetEntryOrEmpty($entry)
	{
		SMTypeCheck::CheckObject(__METHOD__, "entry", $entry, SMTypeCheckType::$String);

		$val = $this->getValue($entry);
		return (($val !== null) ? $val : "");
	}

	/// <function container="base/SMConfiguration" name="GetEntries" access="public" returns="string[]">
	/// 	<description> Get all configuration entry keys </description>
	/// </function>
	public function GetEntries()
	{
		$keys = array();

		if ($this->xml !== null)
		{
			foreach ($this->xml->entry as $entry)
				$keys[] = utf8_decode((string)$entry["key"]);
		}
		else
		{
			$entries = $this->dom->getElementsByTagName("entry"); // DOMNodeList

			foreach ($entries as $entryItem)
				$keys[] = utf8_decode($entryItem->getAttribute("key"));
		}

		return $keys;
	}

	/// <function container="base/SMConfiguration" name="SetEntry" access="public">
	/// 	<description> Set configuration value </description>
	/// 	<param name="entry" type="string"> Configuration entry name </param>
	/// 	<param name="value" type="string"> Configuration entry value </param>
	/// </function>
	public function SetEntry($entry, $value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "entry", $entry, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if ($this->writable === false)
			throw new Exception("Unable to set entry value - configuration was not opened in write mode");

		$entries = $this->dom->getElementsByTagName("entry"); // DOMNodeList
		$found = false;

		// $entryItem->setAttribute(..)  vs.  $entryItem->attributes->getNamedItem("value")->value = $value
		// setAttribute(..) will make sure special characters such as quote, less than, and greater than
		// are encoded as &quot; / &lt; / &gt; to avoid corrupting XML document. Value property will not!

		// PHP Work around - DOMDocument does not handle e.g. danish characters well - causes error:
		// Warning: DOMDocument::saveXML(): output conversion failed due to conv error
		// Output from saveXML() is no longer valid XML (corrupted).
		// Encoding values using utf8_encode(..) before adding to XML Document solves problem.
		// Values being fetched should be decoded using utf8_decode(..)

		foreach ($entries as $entryItem)
		{
			if (utf8_decode($entryItem->getAttribute("key")) === $entry)
			{
				$entryItem->setAttribute("value", utf8_encode($value));
				$found = true;

				break;
			}
		}

		if ($found === false)
		{
			$node = $this->dom->createElement("entry");
			$node->setAttribute("key", utf8_encode($entry));
			$node->setAttribute("value", utf8_encode($value));

			$this->dom->firstChild->appendChild($node);
		}

		$this->dirty = true;
	}

	/// <function container="base/SMConfiguration" name="RemoveEntry" access="public">
	/// 	<description> Remove configuration entry </description>
	/// 	<param name="entry" type="string"> Configuration entry name </param>
	/// </function>
	public function RemoveEntry($entry)
	{
		SMTypeCheck::CheckObject(__METHOD__, "entry", $entry, SMTypeCheckType::$String);

		if ($this->writable === false)
			throw new Exception("Unable to remove entry - configuration was not opened in write mode");

		$entries = $this->dom->getElementsByTagName("entry"); // DOMNodeList

		foreach ($entries as $entryItem)
		{
			if (utf8_decode($entryItem->getAttribute("key")) === $entry)
			{
				$this->dom->firstChild->removeChild($entryItem);
				$this->dirty = true;

				return;
			}
		}
	}

	/// <function container="base/SMConfiguration" name="Commit" access="public">
	/// 	<description> Write changes to configuration file. File is automatically created if not found. </description>
	/// </function>
	public function Commit()
	{
		if ($this->writable === false)
			throw new Exception("Unable to commit changes - configuration was not opened in write mode");

		if ($this->dirty === false)
			return;

		$xml = $this->dom->saveXML();
		$xml = ((SMStringUtilities::EndsWith($this->file, ".php") === true) ? "<?php exit(); ?>\n" : "") . $xml;
		$xml = str_replace("\r", "", $xml);
		$xml = str_replace("\n", "", $xml);
		$xml = str_replace("\t", "", $xml);
		$xml = str_replace("<", "\n<", $xml);
		$xml = str_replace("<entry", "\t<entry", $xml);
		$xml = substr($xml, 1); // Remove first linebreak (breaks XML parser)

		$fileWriter = new SMTextFileWriter($this->file, SMTextFileWriteMode::$Overwrite);
		$result = false;

		$result = $fileWriter->Write($xml);
		if ($result === false)
			throw new Exception("Unable to update configuration file '" . $this->file . "'");

		$result = $fileWriter->Close();
		if ($result === false)
			throw new Exception("Unable to close configuration file '" . $this->file . "'");

		$this->dirty = false;
	}

	/// <function container="base/SMConfiguration" name="EntryExists" access="public" returns="boolean">
	/// 	<description> Check whether configuration entry exists. Returns True if found, otherwise False. </description>
	/// 	<param name="entry" type="string"> Configuration entry name </param>
	/// </function>
	public function EntryExists($entry)
	{
		SMTypeCheck::CheckObject(__METHOD__, "entry", $entry, SMTypeCheckType::$String);
		return ($this->getValue($entry) !== null);
	}

	private function getValue($entry)
	{
		SMTypeCheck::CheckObject(__METHOD__, "entry", $entry, SMTypeCheckType::$String);

		if ($this->xml !== null)
		{
			foreach ($this->xml->entry as $current)
				if (utf8_decode((string)$current["key"]) === $entry)
					return utf8_decode((string)$current["value"]);
		}
		else
		{
			$entries = $this->dom->getElementsByTagName("entry"); // DOMNodeList

			foreach ($entries as $entryItem)
			{
				if (utf8_decode($entryItem->getAttribute("key")) === $entry)
					return utf8_decode($entryItem->getAttribute("value"));
			}
		}

		return null;
	}
}

?>
