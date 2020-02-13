<?php

interface SMIDataSourceCache
{
	// Interface defines functions used outside the SMDataSource - e.g. by the controller.

	public static function GetInstance();
	public function GetDataSourceNames();
}

/// <container name="base/SMIDataSource">
/// 	This interface defines functionality available through a data source controller.
/// 	A data source controller provides simple data storage and data manipulation capabilities.
/// 	The SQL language is used to load and manipulate data.
///
/// 	Changes are performed within transactions to ensure consistency (per data source).
/// 	Isolation is also supported through the built-in locking mechanism.
///
/// 	$ds = new SMDataSource("MyDataSource"); // Open data source
///
/// 	// Insert data - add Casper
/// 	$person = new SMKeyValueCollection();
/// 	$person["Name"] = "Casper K. Mayfield";
/// 	$person["Age"] = "27";
/// 	$ds->Insert($person);
///
/// 	// Get data - load Casper
/// 	$persons = $ds->Select("*", "name = 'Casper K. Mayfield'"); // SMKeyValueCollection[] returned
/// 	$casper = $persons[0];
/// 	$name = $casper["Name"];
/// 	$age = $casper["Age"];
///
/// 	// Update data - change Casper's age
/// 	$changes = new SMKeyValueCollection();
/// 	$changes["Age"] = "28";
/// 	$ds->Update($changes, "name = 'Casper K. Mayfield'");
///
/// 	$ds->Commit(); // Make changes permanent
/// </container>
interface SMIDataSource
{
	// Interface defines required functions for the SMDataSource class

	/// <function container="base/SMIDataSource" name="__construct" access="public">
	/// 	<description> Create instance of data source controller </description>
	/// 	<param name="source" type="string"> Name of data source to open </param>
	/// </function>
	public function __construct($source);

	/// <function container="base/SMIDataSource" name="Select" access="public" returns="SMKeyValueCollection[]">
	/// 	<description> Get data from data source </description>
	/// 	<param name="select" type="string" default="String.Empty"> Comma separated list of fields to select. An empty string or "*" equals all fields. </param>
	/// 	<param name="where" type="string" default="String.Empty"> SQL WHERE statement - example: name != '' AND age > 20 </param>
	/// 	<param name="orderby" type="string" default="String.Empty"> SQL ORDER BY statement - example: age ASC, name DESC </param>
	/// 	<param name="limit" type="integer" default="-1"> Maximum number of records. -1 = no maximum. </param>
	/// 	<param name="offset" type="integer" default="0"> Skip records before offset. 0 = no records are skipped. </param>
	/// </function>
	public function Select($select = "", $where = "", $orderby = "", $limit = -1, $offset = 0);

	/// <function container="base/SMIDataSource" name="Count" access="public" returns="integer">
	/// 	<description> Get number of records matching selection </description>
	/// 	<param name="where" type="string" default="String.Empty"> See Select(..) function for description </param>
	/// 	<param name="limit" type="integer" default="-1"> See Select(..) function for description </param>
	/// 	<param name="offset" type="integer" default="0"> See Select(..) function for description </param>
	/// </function>
	public function Count($where = "", $limit = -1, $offset = 0);

	/// <function container="base/SMIDataSource" name="Insert" access="public">
	/// 	<description> Insert new record </description>
	/// 	<param name="data" type="SMKeyValueCollection"> Instance of SMKeyValueCollection representing record </param>
	/// </function>
	public function Insert(SMKeyValueCollection $data);

	/// <function container="base/SMIDataSource" name="Update" access="public" returns="integer">
	/// 	<description> Update records. Number of updated records returned. </description>
	/// 	<param name="data" type="SMKeyValueCollection"> Instance of SMKeyValueCollection containing changes </param>
	/// 	<param name="where" type="string" default="String.Empty"> See Select(..) function for description </param>
	/// 	<param name="orderby" type="string" default="String.Empty"> See Select(..) function for description </param>
	/// 	<param name="limit" type="integer" default="-1"> See Select(..) function for description </param>
	/// 	<param name="offset" type="integer" default="0"> See Select(..) function for description </param>
	/// </function>
	public function Update(SMKeyValueCollection $data, $where = "", $orderby = "", $limit = -1, $offset = 0);

	/// <function container="base/SMIDataSource" name="Delete" access="public" returns="integer">
	/// 	<description> Delete records. Number of deleted records returned. </description>
	/// 	<param name="where" type="string" default="String.Empty"> See Select(..) function for description </param>
	/// 	<param name="orderby" type="string" default="String.Empty"> See Select(..) function for description </param>
	/// 	<param name="limit" type="integer" default="-1"> See Select(..) function for description </param>
	/// 	<param name="offset" type="integer" default="0"> See Select(..) function for description </param>
	/// </function>
	public function Delete($where = "", $orderby = "", $limit = -1, $offset = 0);

	public function Verify();

	/// <function container="base/SMIDataSource" name="Commit" access="public">
	/// 	<description> Commit changes to data source </description>
	/// 	<param name="unlock" type="boolean" default="true"> True to release lock, false to keep lock </param>
	/// </function>
	public function Commit($unlock = true);

	/// <function container="base/SMIDataSource" name="RollBack" access="public">
	/// 	<description> Undo changes to data source </description>
	/// 	<param name="unlock" type="boolean" default="true"> True to release lock, false to keep lock </param>
	/// </function>
	public function RollBack($unlock = true);

	/// <function container="base/SMIDataSource" name="Lock" access="public">
	/// 	<description>
	/// 		Lock data source for modifications, allowing changes to be made
	/// 		synchroneusly. Locking is done in an advisory way, meaning all accessing
	/// 		programs/code has to call this function before making changes - otherwise
	/// 		the lock will be ignored.
	/// 		Invoking the Lock() function will cause the application to wait
	/// 		for the lock to be released. When released, the lock can be aquired,
	/// 		forcing other sessions or applications to wait.
	/// 	</description>
	/// </function>
	public function Lock();

	/// <function container="base/SMIDataSource" name="Unlock" access="public">
	/// 	<description>
	/// 		Unlock data source. See Lock() function for more information.
	/// 	</description>
	/// </function>
	public function Unlock();

	/// <function container="base/SMIDataSource" name="IsLocked" access="public" returns="boolean">
	/// 	<description>
	/// 		Get flag indicating whether DataSource is currently locked or not.
	/// 		See Lock() function for more information.
	/// 	</description>
	/// </function>
	public function IsLocked();

	/// <function container="base/SMIDataSource" name="Reload" access="public">
	/// 	<description> Reload data - uncommitted changes are discarded </description>
	/// 	<param name="unlock" type="boolean" default="true"> True to release lock, false to keep lock </param>
	/// </function>
	public function Reload($unlock = true);

	/// <function container="base/SMIDataSource" name="SetEscapeData" access="public">
	/// 	<description>
	/// 		Set True to have data automatically escaped (default behaviour),
	/// 		false not to. A value such as "Sam's cornor" is changed to "Sam\'s cornor"
	/// 		before added to the data source.
	/// 		This only applies to data, not to WHERE and ORDER BY statements.
	/// 	</description>
	/// 	<param name="value" type="boolean"> True to enable feature, false to disable </param>
	/// </function>
	public function SetEscapeData($value); // Mainly used by real DBMS systems which require data to be properly escaped (e.g. WHERE name = 'Sam\'s Corner')

	/// <function container="base/SMIDataSource" name="GetEscapeData" access="public" returns="boolean">
	/// 	<description>
	/// 		Returns True if data is being automatically escaped, otherwise
	/// 		False. See SetEscapeData(..) function for more information.
	/// 	</description>
	/// </function>
	public function GetEscapeData();

	/// <function container="base/SMIDataSource" name="Escape" access="public" returns="string">
	/// 	<description> Returns specified value properly escaped </description>
	/// 	<param name="value" type="string"> Value to escape </param>
	/// </function>
	public function Escape($value);

	public static function GetDataSourceType();
	public static function GetDataSourceVersion();

	public function SetUseCache($value); // Deprecated
	public function GetUseCache(); // Deprecated
}

abstract class SMDataSourceCacheItem
{
	private $name;		// string
	private $lock;		// resource (file)
	private $dirty;		// bool

	public function __construct($name)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);

		$this->name = $name;
		$this->lock = null;
		$this->dirty = false;
	}

	public function GetName()
	{
		return $this->name;
	}

	public function Lock()
	{
		if ($this->lock !== null)
			return;

		if ($this->dirty === true)
			throw new Exception("Unable to lock data source '" . $this->name . "' - uncommitted data found");

		$this->lock = fopen(dirname(__FILE__) . "/../" . SMEnvironment::GetDataDirectory() . "/" . $this->name . ".lock", "w");
		if ($this->lock === false)
			throw new Exception("Unable to access lock file for data source '" . $this->name . "'");

		$result = flock($this->lock, LOCK_EX); // Blocks until lock is acquired
		if ($result === false)
			throw new Exception("Unable to set lock on data source '" . $this->name . "'");
	}

	public function Unlock()
	{
		if ($this->lock !== null)
		{
			$result = flock($this->lock, LOCK_UN);
			if ($result === false)
				throw new Exception("Unable to release lock on data source '" . $this->name . "'");

			$result = fclose($this->lock);
			if ($result === false)
				throw new Exception("Unable to close lock file for data source '" . $this->name . "'");

			$this->lock = null;
		}
	}

	public function IsLocked()
	{
		$lock = fopen(dirname(__FILE__) . "/../" . SMEnvironment::GetDataDirectory() . "/" . $this->name . ".lock", "w");

		if ($lock === false)
			throw new Exception("Unable to access lock file for data source '" . $this->name . "'");

		$wouldBlock = 0;

		if (flock($lock, LOCK_EX|LOCK_NB, $wouldBlock) === false) // Lock file but non-blocking
		{
			// File lock was not obtained

			if ($wouldBlock === 1)
			{
				return true; // Another process holds a lock
			}
			else
			{
				throw new Exception("Unable to determine locked state for data source '" . $this->name . "'");
			}
		}
		else
		{
			// File lock was obtained - immediately release it again

			if (flock($lock, LOCK_UN) === false)
				throw new Exception("Unable to release temporary lock on data source '" . $this->name . "'");
		}

		return false;
	}

	public function SetDirty($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->dirty = $value;
	}

	public function GetDirty()
	{
		return $this->dirty;
	}

	// Get/Set internal data source (e.g. DOMDocument or MySQL Connection Resource)
	abstract public function SetInternalDataSource($ds);
	abstract public function GetInternalDataSource();
}

class SMDataSourceCache implements SMIDataSourceCache
{
	private static $instance = null;
	private $cache; // SMDataSourceCacheItem[]

	private function __construct()
	{
		$this->cache = array();
	}

	public static function GetInstance()
	{
		if (self::$instance === null)
			self::$instance = new SMDataSourceCache();

		return self::$instance;
	}

	public function AddDataSource($dataSource)
	{
		SMTypeCheck::CheckObject(__METHOD__, "dataSource", $dataSource, "SMDataSourceCacheItem");

		$this->cache[] = $dataSource;
	}

	public function GetDataSource($dataSourceName)
	{
		SMTypeCheck::CheckObject(__METHOD__, "dataSourceName", $dataSourceName, SMTypeCheckType::$String);

		for ($i = 0 ; $i < count($this->cache) ; $i++)
			if ($this->cache[$i]->GetName() === $dataSourceName)
				return $this->cache[$i];

		return null;
	}

	public function RemoveDataSource($dataSourceName)
	{
		SMTypeCheck::CheckObject(__METHOD__, "dataSourceName", $dataSourceName, SMTypeCheckType::$String);

		$newCache = array();

		for ($i = 0 ; $i < count($this->cache) ; $i++)
			if ($this->cache[$i]->GetName() !== $dataSourceName)
				$newCache[] = $this->cache[$i];

		$this->cache = $newCache;
	}

	public function GetDataSourceNames()
	{
		$names = array();

		for ($i = 0 ; $i < count($this->cache) ; $i++)
			$names[] = $this->cache[$i]->GetName();

		return $names;
	}
}

class SMDataSourceType
{
	public static $Xml = "Xml";
	public static $MySql = "MySql";
}

class SMSqlParser
{
	// Function removes double spaces, and makes sure fields and
	// statements are separated with ' , ' (space-comma-space).
	public static function CleanSql($sql)
	{
		SMTypeCheck::CheckObject(__METHOD__, "sql", $sql, SMTypeCheckType::$String);

		if ($sql === "")
			return $sql;

		$elements = self::SplitSql($sql, " ");

		if (count($elements) === 1)
			return $sql;

		$sql = "";

		// Re-gathering SQL with just one space between each element. Double spaces eliminated.
		for ($i = 0 ; $i < count($elements) ; $i++)
		{
			// Skip empty elements (double spaces in $sql)
			if ($elements[$i] === "")
				continue;

			// Makes sure fields are nicely separated by ' , ': field1 , field2 , field3
			if ($elements[$i] !== "," && SMStringUtilities::StartsWith($elements[$i], ",") === true)
				$elements[$i] = " , " . substr($elements[$i], 1);
			else if ($elements[$i] !== "," && SMStringUtilities::EndsWith($elements[$i], ",") === true)
				$elements[$i] = " " . substr($elements[$i], 0, strlen($elements[$i]) - 1) . " ,";
			else
				$elements[$i] = " " . $elements[$i];

			$sql .= $elements[$i];
		}

		return trim($sql); // Removing first space added in front of SQL
	}

	public static function ValidateFieldName($fieldname)
	{
		SMTypeCheck::CheckObject(__METHOD__, "fieldname", $fieldname, SMTypeCheckType::$String);

		$upperCaseTmp = strtoupper($fieldname);

		// Unfortunately the SplitSql function is used to split WHERE statements, by splitting
		// on AND and OR. This might cause problems if a field is named AND or OR.
		if ($upperCaseTmp === "AND" || $upperCaseTmp === "OR")
			throw new Exception("Field named '" . $fieldname . "' is not allowed");

		if (preg_match("/^[a-z0-9]+$/i", $fieldname) === 0)
			throw new Exception("Invalid field name '" . $fieldname . "' specified");
	}

	// [*|field[ , field...]]
	public static function SyntaxCheckSelectStatement($select) // run argument through SMSqlParser::CleanSql(..) first
	{
		SMTypeCheck::CheckObject(__METHOD__, "select", $select, SMTypeCheckType::$String);

		if ($select === "" || $select === "*")
			return;

		$elements = explode(" ", $select);

		$errorPrefix = "Invalid SELECT statement '" . $select . "' - ";
		$expected = "Field";

		foreach ($elements as $element)
		{
			if ($expected === "Field")
			{
				self::ValidateFieldName($element);

				// The end of the selection could also be expected.
				// The loop will end though, if no more SQL code is
				// available, which will stop the parsing.
				$expected = "Comma";
				continue;
			}

			if ($expected === "Comma")
			{
				if ($element === ",")
				{
					$expected = "Field";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "invalid field separator '" . $element . "' specified");
				}
			}
		}

		if ($expected === "Field")
			throw new Exception($errorPrefix . "invalid end of SELECT statement");
	}

	// [field {{=|!=|<|>} [']search[']|IS[ NOT] NULL|[NOT ]LIKE ['][%]search[%][']}[ AND|OR field...]]
	public static function SyntaxCheckWhereStatement($where) // run argument through SMSqlParser::CleanSql(..) first
	{
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);

		if ($where === "")
			return;

		$elements = self::SplitSql($where, " ", true);

		$errorPrefix = "Invalid WHERE statement '" . $where . "' - ";
		$upperCaseTmp = "";
		$expected = "Field";

		foreach ($elements as $element)
		{
			if ($expected === "Field")
			{
				self::ValidateFieldName($element);

				$expected = "Operator";
				continue;
			}

			if ($expected === "Operator")
			{
				$upperCaseTmp = strtoupper($element);

				if ($element === "=" || $element === "!=" || $element === "<" || $element === "<=" || $element === ">" || $element === ">=" || $upperCaseTmp === "LIKE")
				{
					$expected = "Search";
					continue;
				}
				else if ($upperCaseTmp === "NOT")
				{
					$expected = "OperatorLike";
					continue;
				}
				else if ($upperCaseTmp === "IS")
				{
					$expected = "NullNot";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "invalid operator '" . $element . "' specified");
				}
			}

			if ($expected === "OperatorLike")
			{
				$upperCaseTmp = strtoupper($element);

				if ($upperCaseTmp === "LIKE")
				{
					$expected = "Search";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "invalid operator '" . $element . "' specified");
				}
			}

			if ($expected === "NullNot")
			{
				$upperCaseTmp = strtoupper($element);

				if ($upperCaseTmp === "NULL")
				{
					// The end of the statement could also be expected.
					// The loop will end though, if no more SQL code is
					// available, which will stop the parsing.
					$expected = "AndOr";
					continue;
				}
				else if ($upperCaseTmp === "NOT")
				{
					$expected = "Null";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "invalid operator '" . $element . "' specified");
				}
			}

			if ($expected === "Null")
			{
				$upperCaseTmp = strtoupper($element);

				if ($upperCaseTmp === "NULL")
				{
					// The end of the statement could also be expected.
					// The loop will end though, if no more SQL code is
					// available, which will stop the parsing.
					$expected = "AndOr";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "invalid operator '" . $element . "' specified");
				}
			}

			if ($expected === "Search")
			{
				if (is_numeric($element) === true)
				{
					// The end of the statement could also be expected.
					// The loop will end though, if no more SQL code is
					// available, which will stop the parsing.
					$expected = "AndOr";
					continue;
				}
				else if (SMStringUtilities::StartsWith($element, "'") === true && SMStringUtilities::EndsWith($element, "'") === true)
				{
					// The end of the statement could also be expected.
					// The loop will end though, if no more SQL code is
					// available, which will stop the parsing.
					$expected = "AndOr";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "search expression not surrounded by single quotes must be numeric");
				}
			}

			if ($expected === "AndOr")
			{
				$upperCaseTmp = strtoupper($element);

				if ($upperCaseTmp === "AND" || $upperCaseTmp === "OR")
				{
					$expected = "Field";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "AND/OR expected");
				}
			}
		}

		if ($expected !== "AndOr")
			throw new Exception($errorPrefix . "invalid end of WHERE statement");
	}

	/*public function EscapeFieldNames($sql) // SELECT (e.g.: name, age, gender), ORDER BY (e.g.: name DESC, age) or WHERE (e.g.: name IS NOT NULL AND age > 30)
	{
		// https://regex101.com/r/L7stJ1/7

		$matches = array();
		$count = preg_match_all('/([a-z]+)(.*?)(,| AND | OR |$)/', $sql, $matches, PREG_SET_ORDER);

		if ($count === false)
		{
			throw new Exception("Unable to escape field names");
		}

		if ($count > 0)
		{
			$newSql = "";

			foreach ($matches as $match) // 0 = full match, 1 = field name, 2 = condition for WHERE statement, DESC/ASC/empty for ORDER BY statement, empty for SELECT statement, 3 = AND/OR/empty for WHERE statement, comma/empty for SELECT and ORDER BY statement
			{
				$newSql .= "`" . $match[1] . "`" . $match[2] . $match[3];
			}

			return $newSql;
		}

		return $sql;
	}*/

	// [field[ DESC|ASC][ , field..]
	public static function SyntaxCheckOrderByStatement($orderby) // run argument through SMSqlParser::CleanSql(..) first
	{
		SMTypeCheck::CheckObject(__METHOD__, "orderby", $orderby, SMTypeCheckType::$String);

		if ($orderby === "")
			return;

		$elements = explode(" ", $orderby);

		$errorPrefix = "Invalid ORDER BY statement '" . $orderby . "' - ";
		$upperCaseTmp = "";
		$expected = "Field";

		foreach ($elements as $element)
		{
			if ($expected === "Field")
			{
				self::ValidateFieldName($element);

				// The end of the statement could also be expected.
				// The loop will end though, if no more SQL code is
				// available, which will stop the parsing.
				$expected = "CommaOrOrder";
				continue;
			}

			if ($expected === "CommaOrOrder")
			{
				$upperCaseTmp = strtoupper($element);

				if ($element === ",")
				{
					$expected = "Field";
					continue;
				}
				else if ($upperCaseTmp === "DESC" || $upperCaseTmp === "ASC")
				{
					// The end of the statement could also be expected.
					// The loop will end though, if no more SQL code is
					// available, which will stop the parsing.
					$expected = "Comma";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "field separator or order (DESC/ASC) expected, '" . $element . "' found");
				}
			}

			if ($expected === "Comma")
			{
				if ($element === ",")
				{
					$expected = "Field";
					continue;
				}
				else
				{
					throw new Exception($errorPrefix . "comma expected, '" . $element . "' found");
				}
			}
		}

		if ($expected === "Field")
			throw new Exception($errorPrefix . "invalid end of ORDER BY statement");

		if (count(explode(" , ", $orderby)) > 10)
			throw new Exception($errorPrefix . "ordering by more than 10 fields is not supported");
	}

	public static function ValidateLimitOffset($limit, $offset)
	{
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		if ($limit < -1 || $offset < 0)
			throw new Exception("Limit and Offset must have values equal to or greater than zero");
	}

	// Function makes sure not to split on "splitter" within content surrounded by single quotes.
	// Use trim=true if splitting on e.g. "AND" or "OR", in order to remove spaces surrounding these splitters.
	// col = 123 AND col2 = 'abc' - splitting on 'AND' gives us "col = 123 " and " col2 = 'abc'"
	// TODO: Known issue: Functions using SplitSql should take into account that columns
	//   containing AND/OR as part of the column name will be split! Split on ' AND ' instead of 'AND'.
	//   This will not protect columns which is named AND or OR, only columns containing those words!
	//   Example: (WHERE) name = 'Michael' OR and = 123 AND orbit < 30;
	//   This issue has been handled with a temporary fix in the syntax checker functions, which
	//   will let parsing fail if fields are named AND or OR.
	public static function SplitSql($sql, $splitter, $trim = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "sql", $sql, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "splitter", $splitter, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "trim", $trim, SMTypeCheckType::$Boolean);

		if ($sql === "" || $splitter === "")
			return array($sql);

		// Make sure back spaced single quotes are not split on, by temporarily replacing them
		$sql = str_replace("\'", "{[SMEscapedSingleQuote]}", $sql);
		$elements = explode("'", $sql);

		// Re-create back spaced single quotes
		for ($i = 0 ; $i < count($elements) ; $i++)
			$elements[$i] = str_replace("{[SMEscapedSingleQuote]}", "\'", $elements[$i]);

		$newElements = array();
		$tmpArr = null;
		$ping = false; // single quote

		for ($i = 0 ; $i < count($elements) ; $i++)
		{
			// Add entire element if surrounded by split
			// [elm1]'[elm2]'[elm3]'[elm4]'
			//        ^^^^^^        ^^^^^^
			// Which is every second element within $elements

			if ($i > 0 && $ping === false)
				$ping = true;
			else if ($i > 0 && $ping === true)
				$ping = false;

			if ($ping === true)
			{
				// Create new element if no elements exist. Append to previously added element if such exist.
				if (count($newElements) === 0)
					$newElements[] = "'" . $elements[$i];
				else
					$newElements[count($newElements) - 1] .= "'" . $elements[$i];

				continue;
			}

			// Split element on splitter as it is not surrounded by single quotes, add each item separately.

			$tmpArr = SMStringUtilities::SplitCaseInsensitive($elements[$i], $splitter);

			for ($j = 0 ; $j < count($tmpArr) ; $j++)
			{
				// First item is appended to previously added item if such exists
				if (count($newElements) > 0 && $j === 0)
					$newElements[count($newElements) - 1] .= "'" . $tmpArr[$j];
				else
					$newElements[] = $tmpArr[$j];
			}
		}

		// Trim all elements
		if ($trim === true)
			for ($i = 0 ; $i < count($newElements) ; $i++)
				$newElements[$i] = trim($newElements[$i]);

		return $newElements;
	}
}

?>
