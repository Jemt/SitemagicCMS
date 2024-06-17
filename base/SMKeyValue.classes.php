<?php

/// <container name="base/SMKeyValue">
/// 	Class represents a key and an associated value. Both must be strings.
///
/// 	$entry = new SMKeyValue("Name", "Casper");
/// 	$key = $entry->GetKey();
/// 	$value = $entry->GetValue();
/// </container>
class SMKeyValue
{
	private $key;
	private $value;

	/// <function container="base/SMKeyValue" name="__construct" access="public">
	/// 	<description> Create instance of SMKeyValue </description>
	/// 	<param name="key" type="string"> Specify key </param>
	/// 	<param name="value" type="string" default="String.Empty"> Optionally specify value </param>
	/// </function>
	public function __construct($key, $value = "")
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		$this->key = $key;
		$this->value = $value;
	}

	/// <function container="base/SMKeyValue" name="GetKey" access="public" returns="string">
	/// 	<description> Get key </description>
	/// </function>
	public function GetKey()
	{
		return $this->key;
	}

	/// <function container="base/SMKeyValue" name="GetValue" access="public" returns="string">
	/// 	<description> Get value </description>
	/// </function>
	public function GetValue()
	{
		return $this->value;
	}

	/// <function container="base/SMKeyValue" name="SetValue" access="public">
	/// 	<description> Set value </description>
	/// 	<param name="value" type="string"> Specify value </param>
	/// </function>
	public function SetValue($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->value = $value;
	}
}

/// <container name="base/SMKeyValueCollectionType">
/// 	Enum that determines whether keys in an instance of
/// 	SMKeyValueCollection are case sensitive or not.
/// </container>
class SMKeyValueCollectionType
{
	/// <member container="base/SMKeyValueCollectionType" name="CaseInsensitive" access="public" static="true" type="string" default="CaseInsensitive" />
	public static $CaseInsensitive = "CaseInsensitive";
	/// <member container="base/SMKeyValueCollectionType" name="CaseSensitive" access="public" static="true" type="string" default="CaseSensitive" />
	public static $CaseSensitive = "CaseSensitive";
}

/// <container name="base/SMKeyValueCollection">
/// 	Represents collection of instances of SMKeyValue - a strongly
/// 	typed and associative string array.
///
/// 	The collection implements the following PHP interfaces:
/// 	 - ArrayAccess (http://php.net/ArrayAccess)
/// 	 - Iterator (http://php.net/Iterator)
/// 	 - Countable (http://php.net/Countable)
/// 	The following PHP array functions are supported:
/// 	Iterator (foreach), count(..), isset(..), unset(..).
///
/// 	$kvc = new SMKeyValueCollection(SMKeyValueCollectionType::$CaseInsensitive);
/// 	$kvc["Name"] = "Casper";
/// 	$kvc["Gender"] = "Male";
///
/// 	$kvc = new SMKeyValueCollection(SMKeyValueCollectionType::$CaseInsensitive);
/// 	$kvc->Add(new SMKeyValue("Name", "Casper"));
/// 	$kvc->Add(new SMKeyValue("Gender", "Male"));
///
///		foreach ($kvc as $key) displayKeyAndValue($key, $kvc[$key]);
///
/// 	$numberOfItems = count($kvc);
///
/// 	unset($kvc["casPER"]); // This instance is case insensitive
/// </container>
class SMKeyValueCollection implements ArrayAccess, Iterator, Countable
{
	private $collKeyIndexedCs;
	private $collKeyIndexedCi;

	private $idx;
	private $collNumIndexed;

	private $type;

	/// <function container="base/SMKeyValueCollection" name="__construct" access="public">
	/// 	<description> Create instance of SMKeyValueCollection </description>
	/// 	<param name="type" type="SMKeyValueCollectionType" default="SMKeyValueCollectionType::$CaseSensitive"> Optionally specify whether keys should be case sensitive or not </param>
	/// </function>
	public function __construct($type = "CaseSensitive")
	{
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMKeyValueCollectionType", $type) === false)
			throw new Exception("Invalid collection type '" . $type . "' specified - use SMKeyValueCollectionType::Type");

		$this->idx = -1;
		$this->collKeyIndexedCs = array(); // Used to access data using indexing (case sensitive - actual key set)
		$this->collKeyIndexedCi = array(); // Used to access data using indexing (case insensitive - lower cased key copy)
		$this->collNumIndexed = array(); // Used for iterator (foreach)

		$this->type = $type;
	}

	/// <function container="base/SMKeyValueCollection" name="GetCollectionType" access="public" returns="SMKeyValueCollectionType">
	/// 	<description> Get value indicating whether collection keys are case sensitive or not </description>
	/// </function>
	public function GetCollectionType()
	{
		return $this->type;
	}

	/// <function container="base/SMKeyValueCollection" name="Add" access="public">
	/// 	<description>
	/// 		Add item to collection. Value is updated if item already exists.
	/// 		Alternative way of adding or updating an item: $kvc[&quot;Key&quot;] = &quot;Value&quot;;
	/// 	</description>
	/// 	<param name="kv" type="SMKeyValue"> Item to add to collection </param>
	/// </function>
	public function Add(SMKeyValue $kv)
	{
		$this[$kv->GetKey()] = $kv->GetValue();
	}

	// The PHP functions array_keys(..) and array_values(..)
	// cannot be used with the SMKeyValueCollection array.
	// The following functions may be used instead.

	/// <function container="base/SMKeyValueCollection" name="GetKeys" access="public" returns="string[]">
	/// 	<description> Get all keys contained in collection </description>
	/// </function>
	public function GetKeys()
	{
		return array_keys($this->collKeyIndexedCs);
	}

	/// <function container="base/SMKeyValueCollection" name="GetValues" access="public" returns="string[]">
	/// 	<description> Get all values contained in collection </description>
	/// </function>
	public function GetValues()
	{
		$arr = array();

		foreach ($this->collKeyIndexedCs as $key => $kv)
			$arr[] = $kv->GetValue();

		return $arr;
	}

	// Interface ArrayAccess

	// Used to assign value to eksisting KeyValue.
	// Automatically creates KeyValue if not found.
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		$kv = $this->getItem($offset);

		if ($kv !== null)
		{
			$kv->SetValue($value);
		}
		else
		{
			$kv = new SMKeyValue($offset, $value);
			$this->collKeyIndexedCs[$offset] = $kv;
			$this->collNumIndexed[] = $kv;

			if ($this->type === SMKeyValueCollectionType::$CaseInsensitive)
				$this->collKeyIndexedCi[strtolower($offset)] = $kv;

		}
	}

	// Used by isset()
	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$String);
		return ($this->getItem($offset) !== null);
	}

	// Used to delete a KeyValue using unset()
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$String);

		$kv = $this->getItem($offset);

		if (($key = array_search($kv, $this->collKeyIndexedCs, true)) !== false)
			unset($this->collKeyIndexedCs[$key]);

		if (($key = array_search($kv, $this->collKeyIndexedCi, true)) !== false)
			unset($this->collKeyIndexedCi[$key]);

		// Rebuilding numeric indexed array without element to unset.
		// Unsetting the element will leave a "hole" in the array.

		$tmp = array();

		for ($i = 0 ; $i < count($this->collNumIndexed) ; $i++)
		{
			if ($this->collNumIndexed[$i] !== $kv)
				$tmp[] = $this->collNumIndexed[$i];
		}

		$this->collNumIndexed = $tmp;
	}

	// Used to get value
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$String);

		$kv = $this->getItem($offset);
		return (($kv !== null) ? $kv->GetValue() : null);
	}

	// Interface Iterator

	#[\ReturnTypeWillChange]
	public function current()
	{
		return $this->collNumIndexed[$this->idx]->GetValue();
	}

	#[\ReturnTypeWillChange]
	public function key()
	{
		return $this->collNumIndexed[$this->idx]->GetKey();
	}

	#[\ReturnTypeWillChange]
	public function next()
	{
		$this->idx = $this->idx + 1;
	}

	#[\ReturnTypeWillChange]
	public function rewind()
	{
		$this->idx = 0;
	}

	#[\ReturnTypeWillChange]
	public function valid()
	{
		return ($this->idx < count($this->collNumIndexed));
	}

	// Interface Countable

	#[\ReturnTypeWillChange]
	public function count()
	{
		return count($this->collNumIndexed);
	}

	// Helper functions

	private function getItem($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		$coll = (($this->type === SMKeyValueCollectionType::$CaseSensitive) ? $this->collKeyIndexedCs : $this->collKeyIndexedCi);
		$key = (($this->type === SMKeyValueCollectionType::$CaseSensitive) ? $key : strtolower($key));
		$kv = ((isset($coll[$key]) === true) ? $coll[$key] : null);

		return $kv;
	}
}

?>
