<?php

// XML based DataSource is case sensitive on DataSource names, not attributes.
// It can't handle special characters such as the Euro sign.
// It is dynamic - new columns/fields can be added on the fly for individual entries.

class SMDataSourceCacheItemXml extends SMDataSourceCacheItem
{
	private $verified;	// bool
	private $dom;		// DOMDocument

	public function __construct($name)
	{
		SMTypeCheck::CheckObject(__METHOD__, "name", $name, SMTypeCheckType::$String);

		parent::__construct($name);

		$this->verified = true;
		$this->dom = null;
	}

	public function SetInternalDataSource($dom)
	{
		SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dom", $dom, "DOMDocument");
		$this->dom = $dom;
	}

	public function GetInternalDataSource()
	{
		return $this->dom;
	}

	public function SetVerified($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		$this->verified = $value;
	}

	public function GetVerified()
	{
		return $this->verified;
	}
}

/// <container name="base/SMDataSource (XML)">
/// 	The XML based data source controller allows for simple
/// 	data storage and data manipulation based on locale XML files.
///
/// 	This is the default data source controller used by Sitemagic CMS.
/// 	Data is stored in the data folder on the server. Write access is required.
///
/// 	This data source controller does not require data sources (XML files)
/// 	to be defined before use - they are automatically created.
///
/// 	For more information on how to use the data source controller,
/// 	please see base/SMIDataSource.
/// </container>
class SMDataSource implements SMIDataSource
{
	private $ds; // SMDataSourceCacheItemXml

	public function __construct($source)
	{
		SMTypeCheck::CheckObject(__METHOD__, "source", $source, SMTypeCheckType::$String);

		$this->ds = SMDataSourceCache::GetInstance()->GetDataSource($source);

		if ($this->ds === null)
		{
			$this->ds = new SMDataSourceCacheItemXml($source);
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

		$this->ensureDomDocument();

		$select = SMSqlParser::CleanSql($select);
		SMSqlParser::SyntaxCheckSelectStatement($select);

		$entries = $this->getEntries($where); // Array of DOMElements (extending DOMNode)
		$entries = $this->orderBy($entries, $orderby);
		$entries = $this->limitEntries($entries, $limit, $offset);

		$selects = explode(" , ", $select);

		$returnEntries = array();
		$dataCollection = null;

		foreach ($entries as $entry)
		{
			$dataCollection = new SMKeyValueCollection(SMKeyValueCollectionType::$CaseInsensitive);

			foreach ($entry->attributes as $key => $value) // $key = string (attribute name), $value = DOMAttr
			{
				if ($select === "" || $select === "*")
				{
					$dataCollection[strtolower($key)] = $this->decode($value->nodeValue); // $nodeValue = string
				}
				else
				{
					if ($this->getElementFromStringArrayCaseInsensitive($key, $selects) !== null)
						$dataCollection[strtolower($key)] = $this->decode($value->nodeValue); // $nodeValue = string
				}
			}

			// Only add item if columns/fields were added.
			// This is to avoid "empty" records in the following case: $ds->Select("UnknownColumn");
			if (count($dataCollection) > 0)
				$returnEntries[] = $dataCollection;
		}

		return $returnEntries;
	}

	public function Count($where = "", $limit = -1, $offset = 0)
	{
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		$this->ensureDomDocument();

		$entries = $this->getEntries($where);
		$entries = $this->limitEntries($entries, $limit, $offset);

		return count($entries);
	}

	public function Insert(SMKeyValueCollection $data)
	{
		// Avoid empty <entry /> elements if no data is contained
		if (count($data) === 0)
			return;

		$this->ensureDomDocument();

		$database = $this->ds->GetInternalDataSource()->documentElement; // DOMElement (extending DOMNode)
		$newEntry = $this->ds->GetInternalDataSource()->createElement("entry"); // DOMElement (extending DOMNode)
		$newAttribute = null;
		$newTextNode = null;

		foreach ($data as $key => $value)
		{
			SMSqlParser::ValidateFieldName($key);

			$newAttribute = $this->ds->GetInternalDataSource()->createAttribute($key); // DOMAttr
			$newTextNode = $this->ds->GetInternalDataSource()->createTextNode($this->encode($value)); // DOMText

			$newAttribute->appendChild($newTextNode);
			$newEntry->appendChild($newAttribute);
		}

		$database->appendChild($newEntry);
		$this->setDirty(true);
	}

	public function Update(SMKeyValueCollection $data, $where = "", $orderby = "", $limit = -1, $offset = 0)
	{
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "orderby", $orderby, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		// Do nothing if no data is contained
		if (count($data) === 0)
			return 0;

		$this->ensureDomDocument();

		$entries = $this->getEntries($where); // Array of DOMElements (extending DOMNode)
		$entries = $this->orderBy($entries, $orderby);
		$entries = $this->limitEntries($entries, $limit, $offset);

		foreach ($entries as $entry)
		{
			foreach ($data as $key => $value)
			{
				SMSqlParser::ValidateFieldName($key);
				$entry->setAttribute($this->getCaseSensitiveAttribute($entry, $key), $this->encode($value)); // use getCaseSensitiveAttribute(..) to make sure multiple attributes with the same name but different casing cannot be added (e.g. price, Price, PRicE, etc)
			}
		}

		if (count($entries) > 0)
			$this->setDirty(true);

		return count($entries);
	}

	public function Delete($where = "", $orderby = "", $limit = -1, $offset = 0)
	{
		SMTypeCheck::CheckObject(__METHOD__, "where", $where, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "orderby", $orderby, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		$this->ensureDomDocument();

		$database = $this->ds->GetInternalDataSource()->documentElement; // DOMElement (extending DOMNode)
		$entries = $this->getEntries($where); // Array of DOMElements (extending DOMNode)
		$entries = $this->orderBy($entries, $orderby);
		$entries = $this->limitEntries($entries, $limit, $offset);

		foreach ($entries as $entry)
			$database->removeChild($entry);

		if (count($entries) > 0)
			$this->setDirty(true);

		return count($entries);
	}

	public function Verify()
	{
		if ($this->ds->GetVerified() === true)
			return true;

		// No need to call $this->ensureDomDocument() - $this->ds->GetVerified() only
		// returns false when data has been modified, which means data has been loaded.

		// Make sure XML is valid. It will be corrupted if
		// the Euro sign was used, perhaps other characters too.

		// Throws warning in case invalid character is used. Strangely enough saveXML()
		// doesn't return false in this case - $xml simply contains an invalid XML node
		// like this, which is not properly terminated: <entry attribute="&amp;acirc;"
		// Warning is caught by error handler while error in $xml is discovered when
		// trying to parse the variable with $parser->loadXML($xml) further down.
		$xml = $this->ds->GetInternalDataSource()->saveXML();

		if ($xml === false)
			return false;

		$parser = new DOMDocument();

		if ($parser->loadXML($xml) === false)
		{
			// In case of errors caused by invalid characters, both saveXML and loadXML will result in errors being written to the log
			SMLog::Log(__FILE__, __LINE__, "DOMDocument errors occured, but they are safe to ignore, as they were handled");
			return false;
		}

		// Make sure data source is writable

		if (SMFileSystem::FileIsWritable($this->getSourcePath()) === false)
			return false;

		// Mark verified, return true

		$this->ds->SetVerified(true);

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

		// No need to call $this->ensureDomDocument() - $this->ds->GetDirty() only
		// returns true when data has been modified, which means data has been loaded.

		// Make sure XML is valid. It will be corrupted if
		// the Euro sign was used, perhaps other characters too.
		// The function Verify has been introduced to allow
		// the controller to check all data sources before committing,
		// to ensure that all data sources can in fact be written
		// without exceptions. An extension might call Commit on
		// it's own, so we have to verify data here as well.
		if ($this->Verify() === false)
			throw new Exception("Unable to write data to resource '" . $this->ds->GetName() . "' - possible causes: write protection or data corruption (possibly due to use of invalid character set)");

		$xml = $this->ds->GetInternalDataSource()->saveXML();
		$xml = "<?php exit(); ?>" . $xml;
		$xml = str_replace("\r", "", $xml);
		$xml = str_replace("\n", "", $xml);
		$xml = str_replace("\t", "", $xml);
		$xml = str_replace("<", "\n<", $xml);
		$xml = str_replace("\n<?php", "<?php", $xml);
		$xml = str_replace("<entry", "\t<entry", $xml);

		$fileWriter = new SMTextFileWriter($this->getSourcePath(), SMTextFileWriteMode::$Overwrite);
		$result = false;

		$result = $fileWriter->Write($xml);
		if ($result === false)
			throw new Exception("Unable to write data to resource '" . $this->ds->GetName() . "'");

		$result = $fileWriter->Close();
		if ($result === false)
			throw new Exception("Unable to properly close resource '" . $this->ds->GetName() . "'");

		$this->setDirty(false);

		if ($unlock === true)
			$this->Unlock();
	}

	public function RollBack($unlock = true)
	{
		SMTypeCheck::CheckObject(__METHOD__, "unlock", $unlock, SMTypeCheckType::$Boolean);

		// Discard changes - data will be reloaded next time it is needed
		$this->ds->SetInternalDataSource(null);
		$this->setDirty(false);

		if ($unlock === true)
			$this->Unlock();
	}

	// While a SQL based DataSource allows for multiple sessions to write
	// data at the same time (synchronized), this will not necessary work
	// with the XML based data source, as it is not synchronized.
	// Example follows:
	// --------------------------------------------------
	// Session 1:        Session 2:
	// read data
	//                   read data
	//                   insert, commit
	// insert, commit
	// --------------------------------------------------
	// The example above demonstrates the problem.
	// As both sessions read the data and holds a local copy temporarily,
	// old data may be written to the XML file at some point.
	// Both sessions contain the same data, but the change made by
	// Session 2 is lost, because Session 1 does not contain the
	// modification. The solution is, to make code running under multiple
	// sessions, run in serial, instead of in parallel. We achieve this
	// using a simple locking mechanism.
	// This lock should only be used when writing data that may never
	// be lost. Reading data does not require a lock. The lock is
	// released when data is committed, when data is discarded (rolled
	// back or reloaded), if Unlock() is invoked, or when application is
	// done executing. Therefore, make sure to call Commit() after calling
	// this function, in order to release the lock as soon as
	// possible, allowing other sessions to modify the data again.
	// Notice that Lock() locks a data source in an advisory way.
	// This means that all accessing programs/code has to call this
	// function before writing data - otherwise the lock will be ignored.
	// Also notice that data is reloaded, to ensure the most recent changes.
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
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__);
		return $this->deprecatedSetUseCache;
	}

	public function SetEscapeData($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);
		// Specific to SQL based DataSources - do nothing.
	}

	public function GetEscapeData()
	{
		return false;
	}

	public function Escape($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		return addslashes($value);
	}

	public static function GetDataSourceType()
	{
		return SMDataSourceType::$Xml;
	}

	public static function GetDataSourceVersion()
	{
		return "20180821"; //"20150926";
	}

	private function getEntries($whereStr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "whereStr", $whereStr, SMTypeCheckType::$String);

		$entriesMatching = array();
		$entries = $this->ds->GetInternalDataSource()->getElementsByTagName("entry"); // DOMNodeList

		// Return all entries if no WHERE statement is specified

		if ($whereStr === "")
		{
			foreach ($entries as $entry) // $entry = DOMElement (extending DOMNode)
				$entriesMatching[] = $entry;

			return $entriesMatching;
		}

		$whereStr = SMSqlParser::CleanSql($whereStr);
		SMSqlParser::SyntaxCheckWhereStatement($whereStr);

		// Extract entries matching WHERE statements

		// One of these expressions must be matched in order to include a given entry
		$whereOrs = SMSqlParser::SplitSql($whereStr, " OR ");
		$wheres = null;

		// Variables used in loops below

		$tmpStrArr = null;
		$whereCol = "";
		$whereOpr = "";
		$whereExpr = "";

		$include = false;
		$dbValue = "";

		foreach ($entries as $entry) // $entry = DOMElement (extending DOMNode)
		{
			$include = false;

			foreach ($whereOrs as $whereOr) // OR
			{
				if ($include === true)
					break;

				// All of these expressions must be matched in order to include a given entry
				$wheres = SMSqlParser::SplitSql($whereOr, " AND ");

				$include = true;

				foreach ($wheres as $where) // AND
				{
					$tmpStrArr = SMSqlParser::SplitSql($where, " ");

					$whereCol = strtolower($tmpStrArr[0]);
					$whereOpr = strtoupper($tmpStrArr[1]);

					if ($whereOpr === "NOT") // NOT is followed by LIKE
						$whereOpr = $whereOpr . " " . strtoupper($tmpStrArr[2]);

					if ($whereOpr === "IS") // IS is followed by NOT or NULL
						$whereOpr = $whereOpr . " " . strtoupper($tmpStrArr[2]);

					if ($whereOpr === "IS NOT") // IS NOT is followed by NULL
						$whereOpr = $whereOpr . " " . strtoupper($tmpStrArr[3]);

					$whereExpr = $tmpStrArr[count($tmpStrArr) - 1];

					// Remove pings surrounding search expression (use of pings is optional for numeric values)

					if (strlen($whereExpr) >= 2 && SMStringUtilities::StartsWith($whereExpr, "'") === true && SMStringUtilities::EndsWith($whereExpr, "'") === true)
						$whereExpr = substr($whereExpr, 1, strlen($whereExpr) - 2);

					$whereExpr = str_replace("\\'", "'", $whereExpr);	// allows for single quotes to be used within search expression
					$whereExpr = str_replace("\\\\", "\\", $whereExpr);	// Keep last (!!) - allows for backspace to be used within search expression - must be last to prevent this from transforming e.g. \\' to \' which is also an escape sequence

					// Prepare values to be compared

					$whereExpr = $this->normalize($whereExpr); // Unicode lower cased string (HTML and HEX entities are translated into Unicode characters)

					$dbValue = null;

					foreach ($entry->attributes as $attrName => $attrValue)
						if (strtolower($attrName) === $whereCol)
							$dbValue = $attrValue->value;

					if ($dbValue !== null)
					{
						$dbValue = $this->decode($dbValue);
						$dbValue = $this->normalize($dbValue); // Unicode lower cased string (HTML and HEX entities are translated into Unicode characters)
					}

					// Do actual comparison

					if ($whereOpr === "=")
					{
						if ($dbValue === null)
						{
							$include = false;
							break;
						}

						if ($dbValue === $whereExpr)
							continue;
					}
					else if ($whereOpr === "!=")
					{
						if ($dbValue === null)
						{
							$include = false;
							break;
						}

						if ($dbValue !== $whereExpr)
							continue;
					}
					else if ($whereOpr === ">")
					{
						if ($dbValue === null)
						{
							$include = false;
							break;
						}

						if ($dbValue > $whereExpr)
							continue;
					}
					else if ($whereOpr === ">=")
					{
						if ($dbValue === null)
						{
							$include = false;
							break;
						}

						if ($dbValue >= $whereExpr)
							continue;
					}
					else if ($whereOpr === "<")
					{
						if ($dbValue === null)
						{
							$include = false;
							break;
						}

						if ($dbValue < $whereExpr)
							continue;
					}
					else if ($whereOpr === "<=")
					{
						if ($dbValue === null)
						{
							$include = false;
							break;
						}

						if ($dbValue <= $whereExpr)
							continue;
					}
					else if ($whereOpr === "NOT LIKE")
					{
						if ($dbValue === null)
						{
							$include = false;
							break;
						}

						if (self::likeMatched($whereExpr, $dbValue) === false)
							continue;
					}
					else if ($whereOpr === "LIKE")
					{
						if ($dbValue === null)
						{
							$include = false;
							break;
						}

						if (self::likeMatched($whereExpr, $dbValue) === true)
							continue;
					}
					else if ($whereOpr === "IS NULL")
					{
						if ($dbValue === null)
							continue;
					}
					else if ($whereOpr === "IS NOT NULL")
					{
						if ($dbValue !== null)
							continue;
					}
					else
					{
						throw new Exception("Unsupported operator '" . $whereOpr . "'");
					}

					$include = false;
				}
			}

			if ($include === true)
				$entriesMatching[] = $entry;
		}

		return $entriesMatching;
	}

	private function likeMatched($whereExpr, $dbValue) // Expects unicode strings
	{
		SMTypeCheck::CheckObject(__METHOD__, "whereExpr", $whereExpr, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "dbValue", $dbValue, SMTypeCheckType::$String);

		if ($whereExpr !== "" && mb_ereg_replace("%", "", $whereExpr) === "") // example:   name LIKE '%'   or   name LIKE '%%%'   (includes all records in MySQL, even though one would expect only records with an empty name to be included)
			return true;

		if ($whereExpr === "")
			return ($dbValue === "");

		$regex = "";

		$regex .= "^";
		$regex .= mb_ereg_replace("%", ".*", preg_quote($whereExpr));
		$regex .= "$";

		return (mb_eregi($regex, $dbValue) !== false);

		/* // OLD LOGIC
		$whereExprSearches = SMSqlParser::SplitSql($whereExpr, "%");
		$whereExprOffset = 0;

		for ($i = 0 ; $i < count($whereExprSearches) ; $i++)
		{
			if ($whereExprSearches[$i] === "")
				continue;

			if ($i === 0 && SMStringUtilities::StartsWith($dbValue, $whereExprSearches[$i]) === false)
			{
				return false;
			}
			else if ($i === count($whereExprSearches) - 1 && SMStringUtilities::EndsWith($dbValue, $whereExprSearches[$i]) === false)
			{
				return false;
			}
			else
			{
				$whereExprOffset = strpos($dbValue, $whereExprSearches[$i], $whereExprOffset);

				if ($whereExprOffset === false)
					return false;

				$whereExprOffset = $whereExprOffset + strlen($whereExprSearches[$i]);
			}
		}

		return true;*/
	}

	private function orderBy($entries, $orderStr)
	{
		SMTypeCheck::CheckArray(__METHOD__, "entries", $entries, "DOMElement");
		SMTypeCheck::CheckObject(__METHOD__, "orderStr", $orderStr, SMTypeCheckType::$String);

		if ($orderStr === "")
			return $entries;

		$orderStr = SMSqlParser::CleanSql($orderStr);
		SMSqlParser::SyntaxCheckOrderByStatement($orderStr);

		$orderbys = explode(" , ", $orderStr);

		// $orderData will contain values from columns by which entries are ordered.
		//   Example (name column): $orderData[0] = array("Michael", "James", "Adam", "Ann", "Louise");
		//   Example (age column) : $orderData[1] = array("25", "23", "28", "19", "25");
		//   In the example above the data is ordered by two columns, name and age.
		// $dataOrder will contain either DESC or ASC for each column that data is ordered by.
		//   Example: $dataOrder[0] = ASC , $dataOrder[1] = DESC
		// $orderbyInfo will temporarily contain an array containing a column name and optionally DESC or ASC.
		// $orderbyField will temporarily contain the case sensitive name of an attribute
		// $orderbyValue will temporarily contain the value by which the given entry is ordered.
		$orderData = array();
		$dataOrder = array();
		$orderbyInfo = null;
		$orderbyField = "";
		$orderbyValue = "";

		$countOrder = -1;
		foreach ($orderbys as $orderby)
		{
			$countOrder++;
			$orderbyInfo = explode(" ", $orderby);

			$dataOrder[$countOrder] = SORT_ASC;
			if (count($orderbyInfo) === 2)
				$dataOrder[$countOrder] = (strtoupper($orderbyInfo[1]) === "DESC") ? SORT_DESC : SORT_ASC;

			$orderData[$countOrder] = array(); // Will hold unicode strings (notice call to normalize(..) where data is added to array)

			foreach ($entries as $entry)
			{
				// Notice that the XML based data source will not complain if non-existing columns
				// are referenced. An error will occure on the MySQL based data source.

				// DOMElement->getAttribute is case sensitive. The function below gives us the
				// case sensitive name of an attribute from a case insensitive attribute.
				$orderbyField = $this->getCaseSensitiveAttribute($entry, $orderbyInfo[0]);

				$orderbyValue = (($orderbyField !== null) ? $entry->getAttribute($orderbyField) : null);
				$orderData[$countOrder][] = (($orderbyValue !== null) ? $this->normalize($this->decode($orderbyValue)) : null);
			}
		}

		$sortResult = false;

		// The obvious solution would be to construct a call to array_multisort using call_user_func,
		// instead of hardcoding support for a limited amount of Order By statements. Unfortunately
		// that didn't seem to work during testing (PHP bug?), so we have to do it like this.
		// The parser will make sure that the number of Order By statements won't exceed the supported
		// number of Order By Statements.
		// Notice that array_multisort(..) will sort the data almost like the MySQL database. However,
		// records that contain identical values in the columns being sorted on, might not have the
		// same position in the result, compared to the result from the MySQL database. The result
		// from the MySQL database might actually differ from call to call. Therefore a column containing
		// unique data should be used, if updating or deleting data in conjunction with an Order By
		// statement and a Limit.
		if (count($orderData) === 1)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $entries);
		else if (count($orderData) === 2)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $entries);
		else if (count($orderData) === 3)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $orderData[2], $dataOrder[2], $entries);
		else if (count($orderData) === 4)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $orderData[2], $dataOrder[2], $orderData[3], $dataOrder[3], $entries);
		else if (count($orderData) === 5)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $orderData[2], $dataOrder[2], $orderData[3], $dataOrder[3], $orderData[4], $dataOrder[4], $entries);
		else if (count($orderData) === 6)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $orderData[2], $dataOrder[2], $orderData[3], $dataOrder[3], $orderData[4], $dataOrder[4], $orderData[5], $dataOrder[5], $entries);
		else if (count($orderData) === 7)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $orderData[2], $dataOrder[2], $orderData[3], $dataOrder[3], $orderData[4], $dataOrder[4], $orderData[5], $dataOrder[5], $orderData[6], $dataOrder[6], $entries);
		else if (count($orderData) === 8)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $orderData[2], $dataOrder[2], $orderData[3], $dataOrder[3], $orderData[4], $dataOrder[4], $orderData[5], $dataOrder[5], $orderData[6], $dataOrder[6], $orderData[7], $dataOrder[7], $entries);
		else if (count($orderData) === 9)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $orderData[2], $dataOrder[2], $orderData[3], $dataOrder[3], $orderData[4], $dataOrder[4], $orderData[5], $dataOrder[5], $orderData[6], $dataOrder[6], $orderData[7], $dataOrder[7], $orderData[8], $dataOrder[8], $entries);
		else if (count($orderData) === 10)
			$sortResult = array_multisort($orderData[0], $dataOrder[0], $orderData[1], $dataOrder[1], $orderData[2], $dataOrder[2], $orderData[3], $dataOrder[3], $orderData[4], $dataOrder[4], $orderData[5], $dataOrder[5], $orderData[6], $dataOrder[6], $orderData[7], $dataOrder[7], $orderData[8], $dataOrder[8], $orderData[9], $dataOrder[9], $entries);

		if ($sortResult === false)
			throw new Exception("An unexpected error occured - unable to sort data");

		return $entries;
	}

	private function getElementFromStringArrayCaseInsensitive($search, $elements)
	{
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "elements", $elements, SMTypeCheckType::$String);

		foreach ($elements as $element)
			if (strtolower($element) === strtolower($search))
				return $element;

		return null;
	}

	// Get the case sensitive name of an attribute, by performing a case insensitive search
	private function getCaseSensitiveAttribute(DOMElement $entry, $attribute)
	{
		SMTypeCheck::CheckObject(__METHOD__, "attribute", $attribute, SMTypeCheckType::$String);

		foreach ($entry->attributes as $attr)
			if (strtolower($attr->name) === strtolower($attribute))
				return $attr->name;

		return null;
	}

	private function limitEntries($entries, $limit, $offset)
	{
		SMTypeCheck::CheckArray(__METHOD__, "entries", $entries, "DOMElement");
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "offset", $offset, SMTypeCheckType::$Integer);

		SMSqlParser::ValidateLimitOffset($limit, $offset);

		$newEntries = array();
		$limitCount = 0;

		for ($i = $offset ; $i < count($entries) ; $i++)
		{
			if ($limit === $limitCount)
				break;

			$limitCount++;
			$newEntries[] = $entries[$i];
		}

		return $newEntries;
	}

	private function encode($str)
	{
		// SMStringUtilities::HtmlEncode(..) would probably have been a better choice!
		// No need to encode every single character that has an equivalent HTML entity,
		// which HtmlEntityEncode does. HtmlEncode only encodes special characters
		// like: < > & " '
		// Using HtmlEntityEncode results in more data and larger XML files.

		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		return SMStringUtilities::HtmlEntityEncode($str, true); // True = double encode to preserve encoded HTML (e.g. <p>&lt;i&gt;Hello&lt;/i&gt;</p>)
	}

	private function decode($str)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		return SMStringUtilities::HtmlEntityDecode($str);
	}

	private function normalize($str)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);

		$val = $str;

		// Returns lower cased string.
		// HEX and HTML entities are decoded and lower cased too.
		// This function returns a unicode string !

		$val = SMStringUtilities::HtmlEntityDecode($val);
		$uniVal = SMStringUtilities::UnicodeDecode($val); // Returns unicode string
		$uniVal = mb_strtolower($uniVal);
		//$val = SMStringUtilities::UnicodeEncode($uniVal);
		//return $val;
		return $uniVal;
	}

	private function ensureDomDocument()
	{
		if ($this->ds->GetInternalDataSource() !== null)
			return;

		// Read data from data source file

		// In theory it is possible that multiple sessions invokes createSource()
		// at the same time. This is handled within the createSource() function.
		if (SMFileSystem::FileExists($this->getSourcePath()) === false)
			$this->createSource();

		// Read data

		$fileReader = new SMTextFileReader($this->getSourcePath());
		$xml = $fileReader->ReadAll();

		$xml = str_replace("<?php exit(); ?>", "", $xml);
		$xml = trim($xml);

		$this->ds->SetInternalDataSource(new DOMDocument());
		$this->ds->GetInternalDataSource()->loadXML($xml);
	}

	private function createSource()
	{
		$fileWriter = null;

		// Protection agains multiple sessions executing the following code at the same time.
		// Creating a Text File Writer with the Write Mode "Create" throws an exception
		// if the file already exists. Returning right away is not safe, as the
		// source may not yet be ready for data population (XML below has not yet been written).
		// Therefore we wait until the source is properly populated with the XML. It is very
		// unlikely that two sessions will try to create the same source at the same time,
		// and that one of the sessions will start writing data before the source is actually ready.
		// The solution below is to be safe rather than sorry, if it should happen.
		try
		{
			$fileWriter = new SMTextFileWriter($this->getSourcePath(), SMTextFileWriteMode::$Create);
		}
		catch (Exception $ex)
		{
			$filesize = filesize($this->getSourcePath());

			while ($filesize === 0)
			{
				clearstatcache(true, $this->getSourcePath());
				$filesize = filesize($this->getSourcePath());

				if ($filesize === false)
					throw new Exception("Unable to determine filesize for data source '" . $this->ds->GetName() . "'");

				usleep(250000); // 0.25 sec
			}

			return;
		}

		$result = false;

		$result = $fileWriter->Write("<?php exit(); ?>\n<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?><database/>");
		if ($result === false)
			throw new Exception("Unable to write data to data source '" . $this->ds->GetName() . "'");

		$result = $fileWriter->Close();
		if ($result === false)
			throw new Exception("Unable to properly close data source '" . $this->ds->GetName() . "'");
	}

	private function setDirty($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$Boolean);

		$this->ds->SetDirty($value);

		if ($value === true)
			$this->ds->SetVerified(false);
		else
			$this->ds->SetVerified(true);
	}

	private function getSourcePath()
	{
		return dirname(__FILE__) . "/../" . SMEnvironment::GetDataDirectory() . "/" . $this->ds->GetName() . ".xml.php";
	}
}

?>
