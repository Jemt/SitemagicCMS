<?php

/// <container name="base/SMAttributes">
/// 	Persistent key value pair storage useful for storing extension configuration.
/// 	Be careful to use unique keys - preferably prefix keys with name of extension.
///
/// 	Data added will be automatically committed between the Unload and Finalize
/// 	stages of the life cycle.
///
/// 	SMAttributes::SetAttribute("MyExtensionMailAddress", "test@domain.com"); // Set value
/// 	$mailAddress = SMAttributes::GetAttribute("MyExtensionMailAddress"); // Get value
/// </container>
class SMAttributes
{
	private static $ds = null;					// SMDataSource
	private static $keyValueCollections = null;	// SMKeyValueCollection[]
	private static $changesMade = false;		// Bool

	private function __construct()
	{
	}

	private static function ensureResources()
	{
		if (self::$ds === null)
		{
			self::$ds = new SMDataSource("SMAttributes");
			self::$keyValueCollections = self::$ds->Select("*");
			self::$changesMade = false;
		}
	}

	/// <function container="base/SMAttributes" name="SetAttribute" access="public" static="true">
	/// 	<description> Store key value pair </description>
	/// 	<param name="key" type="string"> Unique key identifying key value pair </param>
	/// 	<param name="value" type="string"> Value to store with specified key </param>
	/// </function>
	public static function SetAttribute($key, $value) // create and update
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		self::ensureResources();

		$lengthKey = strlen($key);
		$lengthValue = strlen($value);

		if ($lengthKey === 0 || $lengthKey > 255)
			throw new Exception("Attribute key must consist of at least 1 character and a maximum of 255 characters");
		if ($lengthValue > 255)
			throw new Exception("Attribute value cannot exceed the length of 255 characters");

		$keyValueCollection = self::getKeyValueCollection($key);

		if ($keyValueCollection === null)
		{
			$kvc = new SMKeyValueCollection();
			$kvc["key"] = $key;
			$kvc["value"] = $value;
			self::$keyValueCollections[] = $kvc;
		}
		else
		{
			$keyValueCollection["value"] = $value;
		}

		self::$changesMade = true;
	}

	/// <function container="base/SMAttributes" name="RemoveAttribute" access="public" static="true" returns="boolean">
	/// 	<description> Remove key value pair. Returns True on success, otherwise False. </description>
	/// 	<param name="key" type="string"> Unique key identifying key value pair to remove </param>
	/// </function>
	public static function RemoveAttribute($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		self::ensureResources();

		$tmp = array();
		$removed = false;

		foreach (self::$keyValueCollections as $keyValueCollection)
		{
			if ($keyValueCollection["key"] !== $key)
				$tmp[] = $keyValueCollection;
			else
				$removed = true;
		}

		self::$keyValueCollections = $tmp;

		if ($removed === true)
			self::$changesMade = true;

		return $removed;
	}

	/// <function container="base/SMAttributes" name="AttributeExists" access="public" static="true" returns="boolean">
	/// 	<description> Check whether key value pair exists. Returns True if found, otherwise False. </description>
	/// 	<param name="key" type="string"> Unique key identifying key value pair </param>
	/// </function>
	public static function AttributeExists($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		self::ensureResources();

		return (self::getKeyValueCollection($key) !== null);
	}

	/// <function container="base/SMAttributes" name="GetAttribute" access="public" static="true" returns="string">
	/// 	<description> Get value from key value pair. Returns value if found, otherwise Null. </description>
	/// 	<param name="key" type="string"> Unique key identifying key value pair </param>
	/// </function>
	public static function GetAttribute($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		self::ensureResources();

		$keyValueCollection = self::getKeyValueCollection($key);

		if ($keyValueCollection !== null)
			return $keyValueCollection["value"];

		return null;
	}

	/// <function container="base/SMAttributes" name="Lock" access="public" static="true">
	/// 	<description>
	/// 		Lock attribute collection for modifications, allowing changes to be made
	/// 		synchroneusly. Locking is done in an advisory way, meaning all accessing
	/// 		programs/code has to call this function before making changes - otherwise
	/// 		the lock will be ignored.
	/// 		Invoking the Lock() function will cause the application to wait
	/// 		for the lock to be released. When released, the lock can be aquired,
	/// 		forcing other sessions or applications to wait.
	/// 	</description>
	/// </function>
	public static function Lock()
	{
		self::ensureResources();
		self::$ds->Lock();
	}

	/// <function container="base/SMAttributes" name="Unlock" access="public" static="true">
	/// 	<description>
	/// 		Unlock attribute collection. See Lock() function for more information.
	/// 	</description>
	/// </function>
	public static function Unlock()
	{
		self::ensureResources();
		self::$ds->Unlock();
	}

	/// <function container="base/SMAttributes" name="Reload" access="public" static="true">
	/// 	<description> Reload attributes - uncommitted changes are discarded </description>
	/// 	<param name="unlock" type="boolean" default="true"> True to release lock, false to keep lock </param>
	/// </function>
	public static function Reload($unlock = true)
	{
		SMTypeCheck::CheckObject(__METHOD__, "unlock", $unlock, SMTypeCheckType::$Boolean);
		self::ensureResources();

		if ($unlock === true)
			self::Unloack();

		self::$keyValueCollections = self::$ds->Select("*");
		self::$changesMade = false;
	}

	/// <function container="base/SMAttributes" name="Commit" access="public" static="true">
	/// 	<description>
	/// 		Commit changes to attribute collection.
	/// 		Collection is automatically committed immediately before the
	/// 		Finalize stage of the life cycle if no exceptions has occured.
	/// 	</description>
	/// </function>
	public static function Commit()
	{
		self::ensureResources();

		self::$ds->Delete();

		foreach (self::$keyValueCollections as $keyValueCollection)
			self::$ds->Insert($keyValueCollection);

		self::$ds->Commit();
		self::$changesMade = false;
	}

	/// <function container="base/SMAttributes" name="CollectionChanged" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if collection contains uncommitted changes, otherwise False </description>
	/// </function>
	public static function CollectionChanged()
	{
		self::ensureResources();
		return self::$changesMade;
	}

	private static function getKeyValueCollection($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		self::ensureResources();

		foreach (self::$keyValueCollections as $keyValueCollection)
			if ($keyValueCollection["key"] === $key)
				return $keyValueCollection;

		return null;
	}
}

?>
