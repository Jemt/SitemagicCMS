<?php

// SQL based DataSource is case insensitive on both DataSource names and attributes.
// It can handle special characters such as the Euro sign (although SMCMS is optimized for ISO-8859-1).
// It is not dynamic - columns/fields must be defined in table definition.

class SMDataSourceCacheItemSql extends SMDataSourceCacheItem
{
	private $autoEscapeData;	// bool
	private $connection;		// MySQL connection resource

	public function __construct($name)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);

		parent::__construct($name);

		$this->autoEscapeData = true;
		$this->connection = null;
	}

	public function SetAutoEscapeData($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->autoEscapeData = $value;
	}

	public function GetAutoEscapeData()
	{
		return $this->autoEscapeData;
	}

	public function SetInternalDataSource($connection)
	{
		SMTypeCheck::CheckObject(__METHOD__, "connection", $connection, "MySQLi");
		$this->connection = $connection;
	}

	public function GetInternalDataSource()
	{
		return $this->connection;
	}
}

/// <container name="base/SMDataSource (MySQL)">
/// 	The MySQL based data source controller allows for simple
/// 	data storage and data manipulation based on MySQL database tables.
///
/// 	This is an alternative data source controller available for Sitemagic CMS,
/// 	which is not enabled by default. Please refer to the installation guide for
/// 	information on how to set up Sitemagic CMS with MySQL.
///
/// 	Database tables must be defined before use. Example:
/// 	CREATE TABLE IF NOT EXISTS MyTable
/// 	(
/// 		&#160;&#160;&#160;&#160; `key` varchar(250) DEFAULT NULL,
/// 		&#160;&#160;&#160;&#160; `value` varchar(250) DEFAULT NULL
/// 	) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;
///
/// 	For more information on how to use the data source controller,
/// 	please see base/SMIDataSource.
/// </container>
class SMDataSource implements SMIDataSource
{
	private $ds; // SMDataSourceCacheItemSql

	public function __construct($source)
	{
		SMTypeCheck::CheckObject(__METHOD__, "source", $source, SMTypeCheckType::$String);

		$this->ds = SMDataSourceCache::GetInstance()->GetDataSource($source);

		if ($this->ds === null)
		{
			$this->ds = new SMDataSourceCacheItemSql($source);
			$this->ds->SetInternalDataSource($this->getConnection($source));

			SMDataSourceCache::GetInstance()->AddDataSource($this->ds);
		}
	}

	public function Select($select = "", $where = "", $orderby = "", $limit = -1, $offset = 0)
	{
		SMTypeCheck::CheckObject(__METHOD__, "select", $select, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "orderby", $orderby, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		$select = SMSqlParser::CleanSql($select);
		SMSqlParser::SyntaxCheckSelectStatement($select);
		$where = SMSqlParser::CleanSql($where);
		SMSqlParser::SyntaxCheckWhereStatement($where);
		$orderby = SMSqlParser::CleanSql($orderby);
		SMSqlParser::SyntaxCheckOrderByStatement($orderby);
		SMSqlParser::ValidateLimitOffset($limit, $offset);

		$select = $this->escapeSelectColumns($select);
		$where = $this->escapeWhereStatementColumns($where);
		$orderby = $this->escapeOrderByColumns($orderby);

		$sql = "SELECT " . (($select === "") ? "*" : $select) . " FROM `" . $this->ds->GetName() . "`";
		$sql .= (($where !== "") ? " WHERE " . $where : "");
		$sql .= (($orderby !== "") ? " ORDER BY " . $orderby : "");
		$sql .= (($limit !== -1) ? " LIMIT " . (string)$limit : "");
		$sql .= (($offset !== 0) ? " OFFSET " . (string)$offset : "");

		$query = mysqli_query($this->ds->GetInternalDataSource(), $sql);

		if ($query === false)
			throw new Exception("Unable to select data from data source '" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));

		$kvcs = array();
		$kvc = null;

		while ($row = mysqli_fetch_assoc($query))
		{
			$kvc = new SMKeyValueCollection(SMKeyValueCollectionType::$CaseInsensitive);

			foreach ($row as $key => $value)
				if ($value !== null)
					$kvc[strtolower($key)] = $value;

			$kvcs[] = $kvc;
		}

		$result = mysqli_free_result($query);

		if ($result === false)
			throw new Exception("Unable to free result set from data source '" . $this->ds->GetName() . "'");

		return $kvcs;
	}

	public function Count($where = "", $limit = -1, $offset = 0)
	{
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		$where = SMSqlParser::CleanSql($where);
		SMSqlParser::SyntaxCheckWhereStatement($where);
		SMSqlParser::ValidateLimitOffset($limit, $offset);

		$where = $this->escapeWhereStatementColumns($where);

		$sql = "SELECT COUNT(*) as `total` FROM `" . $this->ds->GetName() . "`";
		$sql .= (($where !== "") ? " WHERE " . $where : "");
		$sql .= (($limit !== -1) ? " LIMIT " . (string)$limit : "");
		$sql .= (($offset !== 0) ? " OFFSET " . (string)$offset : "");

		$query = mysqli_query($this->ds->GetInternalDataSource(), $sql);

		if ($query === false)
			throw new Exception("Unable to count data from data source '" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));

		$row = mysqli_fetch_assoc($query);
		$total = (int)$row["total"];

		$result = mysqli_free_result($query);

		if ($result === false)
			throw new Exception("Unable to free result set from data source '" . $this->ds->GetName() . "'");

		return $total;
	}

	public function Insert(SMKeyValueCollection $data)
	{
		if (count($data) === 0)
			return;

		$columns = "";
		$values = "";

		foreach ($data->GetKeys() as $col)
		{
			if ($columns !== "")
				$columns .= ", ";

			$columns .= "`" . $col . "`";
		}

		$data = $this->escapeData($data);

		foreach ($data as $val)
		{
			if ($values !== "")
				$values .= ", ";

			$values .= "'" . $val . "'";
		}

		$sql = "INSERT INTO `" . $this->ds->GetName() . "` (" . $columns . ") VALUES (" . $values . ")";

		$query = mysqli_query($this->ds->GetInternalDataSource(), $sql);

		if ($query === false)
			throw new Exception("Unable to insert data into data source '" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));

		$this->ds->SetDirty(true);
	}

	public function Update(SMKeyValueCollection $data, $where = "", $orderby = "", $limit = -1, $offset = 0)
	{
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "orderby", $orderby, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		if (count($data) === 0)
			return 0;

		$where = SMSqlParser::CleanSql($where);
		SMSqlParser::SyntaxCheckWhereStatement($where);
		$orderby = SMSqlParser::CleanSql($orderby);
		SMSqlParser::SyntaxCheckOrderByStatement($orderby);
		SMSqlParser::ValidateLimitOffset($limit, $offset);

		$data = $this->escapeData($data);

		$values = "";

		foreach ($data as $key => $value)
		{
			if ($values !== "")
				$values .= ", ";

			$values .= "`" . $key . "` = '" . $value . "'";
		}

		$toBeAffected = $this->Count($where, $limit, $offset);

		$sql = "UPDATE `" . $this->ds->GetName() . "` SET " . $values;
		$sql .= (($where !== "") ? " WHERE " . $where : "");
		$sql .= (($orderby !== "") ? " ORDER BY " . $orderby : "");
		$sql .= (($limit !== -1) ? " LIMIT " . (string)$limit : "");
		$sql .= (($offset !== 0) ? " OFFSET " . (string)$offset : "");

		$query = mysqli_query($this->ds->GetInternalDataSource(), $sql);

		if ($query === false)
			throw new Exception("Unable to update data in data source '" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));

		if (mysqli_affected_rows($this->ds->GetInternalDataSource()) > 0)
			$this->ds->SetDirty(true);

		// We want to know how many records matched the WHERE statement and
		// was updated, even though records were updated with the same data
		// already contained. The function mysqli_affected_rows(..) only gives us
		// the number of records updated with new (different from current) data.
		return $toBeAffected;
	}

	public function Delete($where = "", $orderby = "", $limit = -1, $offset = 0)
	{
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "orderby", $orderby, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		$where = SMSqlParser::CleanSql($where);
		SMSqlParser::SyntaxCheckWhereStatement($where);
		$orderby = SMSqlParser::CleanSql($orderby);
		SMSqlParser::SyntaxCheckOrderByStatement($orderby);
		SMSqlParser::ValidateLimitOffset($limit, $offset);

		$where = $this->escapeWhereStatementColumns($where);
		$orderby = $this->escapeOrderByColumns($orderby);

		$sql = "DELETE FROM `" . $this->ds->GetName() . "`";
		$sql .= (($where !== "") ? " WHERE " . $where : "");
		$sql .= (($orderby !== "") ? " ORDER BY " . $orderby : "");
		$sql .= (($limit !== -1) ? " LIMIT " . (string)$limit : "");
		$sql .= (($offset !== 0) ? " OFFSET " . (string)$offset : "");

		$query = mysqli_query($this->ds->GetInternalDataSource(), $sql);

		if ($query === false)
			throw new Exception("Unable to delete data from data source '" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));

		if (mysqli_affected_rows($this->ds->GetInternalDataSource()) > 0)
			$this->ds->SetDirty(true);

		return mysqli_affected_rows($this->ds->GetInternalDataSource());
	}

	// Specific to XML data source
	public function Verify()
	{
		return true;
	}

	public function Commit($unlock = true)
	{
		SMTypeCheck::CheckObject(__METHOD__, "unlock", $unlock, SMTypeCheckType::$Boolean);

		if ($this->ds->GetDirty() === false)
		{
			if ($unlock === true)
				$this->Unlock();

			return;
		}

		$query = mysqli_query($this->ds->GetInternalDataSource(), "COMMIT");

		if ($query === false)
			throw new Exception("Unable to commit data to data source '" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));

		$this->ds->SetDirty(false);

		// When data is committed, a new transaction is created. A snapshot is made immediately
		// (WITH CONSISTENT SNAPSHOT), to mimic the behaviour of the XML based Data Source, which
		// holds the most current data after commit (since it overwrites the XML file).
		$query = mysqli_query($this->ds->GetInternalDataSource(), "START TRANSACTION WITH CONSISTENT SNAPSHOT");

		if ($query === false)
			throw new Exception("Unable to start new transaction on data source '" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));

		// Unlocking AFTER new transaction with immediate snapshot has been made,
		// to make sure the data committed is identical to the data in the snapshot
		// (mimics behaviour of the XML based Data Source).
		if ($unlock === true)
			$this->Unlock();
	}

	public function RollBack($unlock = true)
	{
		SMTypeCheck::CheckObject(__METHOD__, "unlock", $unlock, SMTypeCheckType::$Boolean);

		$query = mysqli_query($this->ds->GetInternalDataSource(), "ROLLBACK");

		if ($query === false)
			throw new Exception("Unable to perform roll back on data source'" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));

		$this->ds->SetDirty(false);

		if ($unlock === true)
			$this->Unlock();

		// Notice that we start a new transaction, but does not load a snapshot immediately (WITH
		// CONSISTENT SNAPSHOT). This mimics the behaviour of the XML based Data Source, which
		// loads data when needed, after af Roll Back has been performed.
		$query = mysqli_query($this->ds->GetInternalDataSource(), "START TRANSACTION");

		if ($query === false)
			throw new Exception("Unable to start new transaction on data source '" . $this->ds->GetName() . "' - " . mysqli_error($this->ds->GetInternalDataSource()));
	}

	// This Data Source also implements a locking mechanism to ensure that
	//   very important data will never be lost. While the XML based data source
	//   might loose data because of potentially old data being committed (if the
	//   locking is not used), the SQL based DataSource might loose data because
	//   of the risk of dead locks.
	// This function ensure true syncronization - not two sessions will access
	//   the data source at the same time.
	// Notice that Lock() locks a data source in an advisory way.
	//   This means that all accessing programs/code has to call this
	//   function before writing data - otherwise the lock will be ignored.
	// MySQL features Table Locks. Unfortunately there is no guarantee that the
	//   LOCK TABLES privilege has been granted, so we use a file based lock instead.
	//   Also, table locks are not advisory, so that behaviour would differ from
	//   the data source "pattern".
	public function Lock()
	{
		$this->ds->Lock();
		$this->Reload(false); // false = do not unlock
	}

	public function Unlock()
	{
		$this->ds->Unlock();
	}

	public function Reload($unlock = true)
	{
		SMTypeCheck::CheckObject(__METHOD__, "unlock", $unlock, SMTypeCheckType::$Boolean);
		$this->RollBack($unlock); // Roll Back causes data to be reloaded
	}

	private $deprecatedSetUseCache = true;
	public function SetUseCache($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);

		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "Reload");
		$this->deprecatedSetUseCache = $value;

		if ($value === false)
			$this->Reload();
	}

	public function GetUseCache()
	{
		return $this->deprecatedSetUseCache;
	}

	public function SetEscapeData($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->ds->SetAutoEscapeData($value);
	}

	public function GetEscapeData()
	{
		return $this->ds->GetAutoEscapeData();
	}

	public function Escape($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		return mysqli_real_escape_string($this->ds->GetInternalDataSource(), $value);
	}

	public static function GetDataSourceType()
	{
		return SMDataSourceType::$MySql;
	}

	public static function GetDataSourceVersion()
	{
		return "20150802";
	}

	private function getConnection($source)
	{
		SMTypeCheck::CheckObject(__METHOD__, "source", $source, SMTypeCheckType::$String);

		// Get connection info

		$cfg = SMEnvironment::GetConfiguration();
		$connectionStr = $cfg->GetEntry("DatabaseConnection");

		if ($connectionStr === null)
			throw new Exception("Database connection information missing from configuration file");

		$connectionInfo = explode(";", $connectionStr); // server[:port];database;username;password

		if (count($connectionInfo) !== 4)
			throw new Exception("Database connection information malformed - expected server[:port];database;username;password");

		// Connect to database server and select database

		if (ini_get("mysql.safe_mode") === "1" || ini_get("sql.safe_mode") === "1")
			throw new Exception("MySQL Safe Mode not supported (mysql.safe_mode or sql.safe_mode)");

		if (ini_get("mysql.max_links") !== false && ini_get("mysql.max_links") !== "-1")
			throw new Exception("The number of MySQL connections allowed cannot be restricted (mysql.max_links)");

		$serverInfo = explode(":", $connectionInfo[0]);
		$host = $serverInfo[0];
		$port = ((count($serverInfo) > 1) ? (int)$serverInfo[1] : -1);

		$connection = null;

		if ($port > -1)
			$connection = mysqli_connect($host, $connectionInfo[2], $connectionInfo[3], "", $port);
		else
			$connection = mysqli_connect($host, $connectionInfo[2], $connectionInfo[3], "");

		if ($connection === false)
		{
			// Do NOT throw exception - the stack trace includes a warning
			// emitted from mysqli_connect(..) containing the login information,
			// which is caught by error handler defined in SMLog.class.php
			// and displayed in the browser.

			echo "Unable to connect to database server '" . $connectionInfo[0] . "'";
			exit;
		}

		$result = mysqli_set_charset($connection, "latin1"); // Latin1 actually means CP-1252 (also known as Windows-1252) within MySQL, not ISO-8859-1 as one would expect - fortunately it's compatible with characters from ISO-8859-1 - http://dev.mysql.com/doc/refman/5.5/en/charset-charsets.html

		if ($result === false)
			throw new Exception("Unable to set character encoding");

		$result = mysqli_select_db($connection, $connectionInfo[1]);

		if ($result === false)
			throw new Exception("Unable to select database '" . $connectionInfo[1] . "' - " . mysqli_error($connection));

		// Start transaction

		// Default isolation level is REPEATABLE READ.
		// REPEATABLE READ = All reads are made on a snapshot of the data - sort of a temporary copy.
		// Previously started like this: START TRANSACTION WITH CONSISTENT SNAPSHOT
		// This forced a snapshot to be made immediately. "WITH CONSISTENT SNAPSHOT" has now been removed,
		// so that the snapshot is created when data is first read or modified. This mimics the behaviour
		// of the XML based Data Source better, since the XML data is not read until it is actually needed.
		// Changes made within the transaction will be available for the transaction only, until committed.
		$query = mysqli_query($connection, "START TRANSACTION");

		if ($query === false)
			throw new Exception("Unable to start transaction on data source '" . $source . "' - " . mysqli_error($connection));

		return $connection;
	}

	// Surround column names with '`' to avoid problems with reserved words like BY, ORDER etc.
	// Argument must have been cleaned with SMSqlParser::CleanSql(..)
	private function escapeWhereStatementColumns($where)
	{
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);

		if ($where === "")
			return "";

		$whereAnds = SMSqlParser::SplitSql($where, " AND ");
		$whereOrs = null;
		$info = null;

		for ($i = 0 ; $i < count($whereAnds) ; $i++)
		{
			$whereOrs = SMSqlParser::SplitSql($whereAnds[$i], " OR ");

			for ($j = 0 ; $j < count($whereOrs) ; $j++)
			{
				$info = explode(" ", $whereOrs[$j]);
				$info[0] = "`" . $info[0] . "`";

				$whereOrs[$j] = implode(" ", $info);
			}

			$whereAnds[$i] = implode(" OR ", $whereOrs);
		}

		return implode(" AND ", $whereAnds);
	}

	// Surround column names with '`' to avoid problems with reserved words like BY, ORDER etc.
	// Argument must have been cleaned with SMSqlParser::CleanSql(..)
	private function escapeOrderByColumns($orderStr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "orderStr", $orderStr, SMTypeCheckType::$String);

		if ($orderStr === "")
			return "";

		$orderbys = explode(" , ", $orderStr);
		$orderbyInfo = null;

		for ($i = 0 ; $i < count($orderbys) ; $i++)
		{
			$orderbyInfo = explode(" ", $orderbys[$i]); // 0 = column name, 1 = DESC|ASC (optional)
			$orderbyInfo[0] = "`" . $orderbyInfo[0] . "`";

			$orderbys[$i] = $orderbyInfo[0];

			if (count($orderbyInfo) === 2)
				$orderbys[$i] .= " " . $orderbyInfo[1];
		}

		return implode(" , ", $orderbys);
	}

	// Surround column names with '`' to avoid problems with reserved words like BY, ORDER etc.
	// Argument must have been cleaned with SMSqlParser::CleanSql(..)
	private function escapeSelectColumns($selectStr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "selectStr", $selectStr, SMTypeCheckType::$String);

		if ($selectStr === "" || $selectStr === "*")
			return $selectStr;

		$columns = explode(" , ", $selectStr);

		for ($i = 0 ; $i < count($columns) ; $i++)
			$columns[$i] = "`" . $columns[$i] . "`";

		return implode(" , ", $columns);
	}

	private function escapeData(SMKeyValueCollection $record)
	{
		// Transform KeyValue Collection into Case Insensitive collection
		// to make sure mixed casing does not prevent some fields from being escaped.

		$data = new SMKeyValueCollection(SMKeyValueCollectionType::$CaseInsensitive);

		foreach ($record as $key => $value)
			$data[$key] = $value;

		// Escape data

		if ($this->ds->GetAutoEscapeData() === false)
			return $data;

		$keys = $data->GetKeys();

		foreach ($keys as $key)
			$data[$key] = $this->Escape($data[$key]);

		return $data;
	}
}

?>
