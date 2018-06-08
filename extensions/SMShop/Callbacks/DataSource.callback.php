<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

// Stateless HTTP request!
// Immediately close session to allow multiple concurrent requests from the same
// session/browser. Storing data in session state will NOT work from this point on!
SMEnvironment::CloseSession();

$ip = SMEnvironment::GetEnvironmentValue("REMOTE_ADDR");
$ip = (($ip !== null) ? $ip : "");

// ==================================================================
// DataSource definitions
// ==================================================================

// NOTICE: Some fields have MaxLength multiplied by 8 to support the length of encoded Unicode characters.

$dataSourcesAllowed = array
(
	/*"SMShopExample" => array
	(
		"Name"				=> "SMShopExample", // Required
		"AuthRequired"		=> array("Create", "Retrieve", "Update", "Delete", "RetrieveAll"), // Required
		"XmlLockRequired"  	=> array("Create", "Update", "Delete"), // Required
		"XmlTimeOut"		=> 180,			// Optional
		"XmlMemoryRequired"	=> 512,			// Optional
		"XmlArchive"		=> array(		// Optional
			"RecordLimit"		=> 150,		// Optional - if specified, old rows will be moved to an archive and only X rows will be kept in actual DataSource
			"Relations"			=> array(	// Optional - if specified, related data will be archived as well
				array(
					"DataSource"	=> "SMShopOrderEntries",	// Required
					"DataSourceRef"	=> null,					// Automatically set/replaced by reference to this DataSource definition
					"Reference"		=> "OrderId"				// Required - foreign key containing Id of parent data source
				)
			),
			"Callbacks"				=> array(												// Optional
				"File"					=> dirname(__FILE__) . "/DSCallbacks/Order.php",	// Required
				"Functions"				=> array											// Required
				(
					"SelectArchiveWhen"		=> "SMShopXmlArchiveSelectWhen",				// Optional - Receives string[][] - match array
					"OnArchived"			=> "SMShopXmlArchiveOnArchived" 				// Optional - Receives SMKeyValueCollection item (archived DataSource row) and SMDataSource (SMShopState)
				)
			)
		),
		"OrderBy"			=> "",		// Required
		"Fields"			=> array	// Required
		(
			// Id field is required.
			// All fields must define DataType and MaxLength.
			// Supported data types: string, number.
			// ForceInitialValue is used when creating new items and is optional.
			"Id"					=> array("DataType" => "string", "MaxLength" => 50*8),
			"ClientIp"				=> array("DataType" => "string", "MaxLength" => 45, "ForceInitialValue" => $ip),
			"Name"					=> array("DataType" => "string", "MaxLength" => 50*8),
			"Age"					=> array("DataType" => "number", "MaxLength" => 3)
		),
		"Callbacks"				=> array( // Optional
			"File"					=> dirname(__FILE__) . "/DSCallbacks/Order.php", // Required if Callbacks is defined
			"Functions"				=> array // Required if Callbacks is defined
			(
				"Create"				=> "SMShopProcessNewOrder", 	// Optional - Receives SMKeyValueCollection item
				"Retrieve"				=> null,						// Optional - Receives SMKeyValueCollection item
				"RetrieveAll"			=> null,						// Optional - Receives array of SMKeyValueCollection items
				"Update"				=> null,						// Optional - Receives SMKeyValueCollection item
				"Delete"				=> "SMShopDeleteOrderEntries"	// Optional - Receives unique item ID
			)
		)
	),*/
	"SMShopProducts" => array
	(
		"Name"				=> "SMShopProducts",
		"AuthRequired"		=> array("Create", "Update", "Delete"), // Retrieve and RetrieveAll does not require auth.
		"XmlLockRequired"	=> array(),
		"XmlTimeOut"		=> -1,
		"XmlMemoryRequired"	=> -1,
		"OrderBy"			=> "Category ASC, Title ASC",
		"Fields"			=> array
		(
			"Id"					=> array("DataType" => "string", "MaxLength" => 30*8),
			"Category"				=> array("DataType" => "string", "MaxLength" => 50*8),
			"CategoryId"			=> array("DataType" => "string", "MaxLength" => 50+20),	// Only used to generate URLs (up to 50 ASCII characters + 20 characters for a hash code appended)
			"Title"					=> array("DataType" => "string", "MaxLength" => 250*8),
			"Description"			=> array("DataType" => "string", "MaxLength" => 1000*8),
			"Images"				=> array("DataType" => "string", "MaxLength" => 1000),	// E.g. <GUID>.png;<GUID>.jpeg, etc. - enough to hold 26 filenames
			"Price"					=> array("DataType" => "number", "MaxLength" => 15),
			"Vat"					=> array("DataType" => "number", "MaxLength" => 15),
			"Currency"				=> array("DataType" => "string", "MaxLength" => 3),
			"Weight"				=> array("DataType" => "number", "MaxLength" => 15),
			"WeightUnit"			=> array("DataType" => "string", "MaxLength" => 3),
			"DeliveryTime"			=> array("DataType" => "string", "MaxLength" => 50*8),
			"DiscountExpression"	=> array("DataType" => "string", "MaxLength" => 250),
			"DiscountMessage"		=> array("DataType" => "string", "MaxLength" => 250*8)
		)
	),
	"SMShopOrders" => array
	(
		"Name"				=> "SMShopOrders",
		"AuthRequired"		=> array("Retrieve", "Update", "Delete", "RetrieveAll"), // Create does not require auth.
		"XmlLockRequired"  	=> array("Create", "Update", "Delete"),
		"XmlTimeOut"		=> 180, // Increase script execution timeout if using XML Data Source - data source may contain thousands of records
		"XmlMemoryRequired"	=> 512, // Increase memory if using XML Data Source - data source may contain thousands of records
		"XmlArchive"		=> array(
			"RecordLimit"		=> 150,
			"Relations"			=> array(
				array(
					"DataSource"	=> "SMShopOrderEntries",
					"DataSourceRef"	=> null, // Automatically replaced by reference to this DataSource definition
					"Reference"		=> "OrderId"
				)
			),
			"Callbacks"				=> array(
				"File"					=> dirname(__FILE__) . "/DSCallbacks/Order.php",
				"Functions"				=> array
				(
					"SelectArchiveWhen"		=> "SMShopXmlArchiveSelectWhen",	// Receives string[][] - match array
					"OnArchived"			=> "SMShopXmlArchiveOnArchived" 	// Receives SMKeyValueCollection item (archived DataSource row) and SMDataSource (SMShopState)
				)
			)
		),
		"OrderBy"			=> "",
		"Fields"			=> array
		(
			"Id"					=> array("DataType" => "string", "MaxLength" => 50),
			"Time"					=> array("DataType" => "number", "MaxLength" => 15, "ForceInitialValue" => (string)(time() * 1000)), // Timestamp in milliseconds (compatible with JS)
			"ClientIp"				=> array("DataType" => "string", "MaxLength" => 45, "ForceInitialValue" => $ip), // IPv4 or IPv6
			"Company"				=> array("DataType" => "string", "MaxLength" => 50*8),
			"FirstName"				=> array("DataType" => "string", "MaxLength" => 50*8),
			"LastName"				=> array("DataType" => "string", "MaxLength" => 50*8),
			"Address"				=> array("DataType" => "string", "MaxLength" => 50*8),
			"ZipCode"				=> array("DataType" => "string", "MaxLength" => 20),
			"City"					=> array("DataType" => "string", "MaxLength" => 50*8),
			"Email"					=> array("DataType" => "string", "MaxLength" => 50*8),
			"Phone"					=> array("DataType" => "string", "MaxLength" => 20),
			"Message"				=> array("DataType" => "string", "MaxLength" => 250*8),
			"AltCompany"			=> array("DataType" => "string", "MaxLength" => 50*8),
			"AltFirstName"			=> array("DataType" => "string", "MaxLength" => 50*8),
			"AltLastName"			=> array("DataType" => "string", "MaxLength" => 50*8),
			"AltAddress"			=> array("DataType" => "string", "MaxLength" => 50*8),
			"AltZipCode"			=> array("DataType" => "string", "MaxLength" => 20),
			"AltCity"				=> array("DataType" => "string", "MaxLength" => 50*8),
			"Price"					=> array("DataType" => "number", "MaxLength" => 15),
			"Vat"					=> array("DataType" => "number", "MaxLength" => 15),
			"Currency"				=> array("DataType" => "string", "MaxLength" => 3),
			"Weight"				=> array("DataType" => "number", "MaxLength" => 15),
			"WeightUnit"			=> array("DataType" => "string", "MaxLength" => 3),
			"CostCorrection1"		=> array("DataType" => "number", "MaxLength" => 15),
			"CostCorrectionVat1"	=> array("DataType" => "number", "MaxLength" => 15),
			"CostCorrectionMessage1"=> array("DataType" => "string", "MaxLength" => 250*8),
			"CostCorrection2"		=> array("DataType" => "number", "MaxLength" => 15),
			"CostCorrectionVat2"	=> array("DataType" => "number", "MaxLength" => 15),
			"CostCorrectionMessage2"=> array("DataType" => "string", "MaxLength" => 250*8),
			"CostCorrection3"		=> array("DataType" => "number", "MaxLength" => 15),
			"CostCorrectionVat3"	=> array("DataType" => "number", "MaxLength" => 15),
			"CostCorrectionMessage3"=> array("DataType" => "string", "MaxLength" => 250*8),
			"PaymentMethod"			=> array("DataType" => "string", "MaxLength" => 50),
			"TransactionId"			=> array("DataType" => "string", "MaxLength" => 100),
			"State"					=> array("DataType" => "string", "MaxLength" => 20),
			"PromoCode"				=> array("DataType" => "string", "MaxLength" => 30*8),
			"CustData1"				=> array("DataType" => "string", "MaxLength" => 250*8),
			"CustData2"				=> array("DataType" => "string", "MaxLength" => 250*8),
			"CustData3"				=> array("DataType" => "string", "MaxLength" => 250*8),
			"InvoiceId"				=> array("DataType" => "string", "MaxLength" => 50, "ForceInitialValue" => ""), // Set initial value to prevent client from assigning value
			"InvoiceTime"			=> array("DataType" => "number", "MaxLength" => 15)
		),
		"Callbacks"				=> array(
			"File"					=> dirname(__FILE__) . "/DSCallbacks/Order.php",
			"Functions"				=> array
			(
				"Create"				=> "SMShopProcessNewOrder", 	// Receives SMKeyValueCollection item
				"CreateCompleted"		=> null,//"SMShopSendMail",		// Receives SMKeyValueCollection item
				"Retrieve"				=> null,						// Receives SMKeyValueCollection item
				"RetrieveAll"			=> null,						// Receives array of SMKeyValueCollection items
				"Update"				=> null,						// Receives SMKeyValueCollection item
				"Delete"				=> "SMShopDeleteOrderEntries"	// Receives unique item ID
			)
		)
	),
	"SMShopOrderEntries" => array
	(
		"Name"				=> "SMShopOrderEntries",
		"AuthRequired"		=> array("Retrieve", "Update", "Delete", "RetrieveAll"), // Create does not require auth.
		"XmlLockRequired"  	=> array("Create", "Update", "Delete"),
		"XmlTimeOut"		=> 180, // Increase script execution timeout if using XML Data Source - data source may contain thousands of records
		"XmlMemoryRequired"	=> 512, // Increase memory if using XML Data Source - data source may contain thousands of records
		"XmlArchive"		=> array(
			"Callbacks"				=> array(
				"File"					=> dirname(__FILE__) . "/DSCallbacks/Order.php",
				"Functions"				=> array
				(
					"SelectArchiveWhen"		=> "SMShopXmlArchiveSelectOrderEntriesWhen",	// Receives string[][] - match array
					"OnArchived"			=> "SMShopXmlArchiveOrderEntriesOnArchived" 	// Receives SMKeyValueCollection item (archived DataSource row) and SMDataSource (SMShopState)
				)
			)
		),
		"OrderBy"			=> "",
		"Fields"			=> array
		(
			"Id"					=> array("DataType" => "string", "MaxLength" => 50),
			"OrderId"				=> array("DataType" => "string", "MaxLength" => 50),
			"ProductId"				=> array("DataType" => "string", "MaxLength" => 30*8),
			"UnitPrice"				=> array("DataType" => "number", "MaxLength" => 15),
			"Vat"					=> array("DataType" => "number", "MaxLength" => 15),
			"Currency"				=> array("DataType" => "string", "MaxLength" => 3),
			"Units"					=> array("DataType" => "number", "MaxLength" => 10),
			"Discount"				=> array("DataType" => "number", "MaxLength" => 15),
			"DiscountMessage"		=> array("DataType" => "string", "MaxLength" => 250*8)
		)
	)
);

// Map JSShop models to DataSources
$modelDataSources = array(
	"Product"			=> $dataSourcesAllowed["SMShopProducts"],
	"Order"				=> $dataSourcesAllowed["SMShopOrders"],
	"OrderEntry"		=> $dataSourcesAllowed["SMShopOrderEntries"]
);

// ==================================================================
// Functions
// ==================================================================

function SMShopXmlArchiving($dsDef)
{
	SMTypeCheck::CheckObject(__METHOD__, "dsDef", $dsDef, SMTypeCheckType::$Array);

	// Deadlock:
	// This mechanism has the potential to cause deadlocks because multiple resources
	// are being locked over time. However, DataSource.callback.php usually only lock
	// one DataSource when doing CRUD - the XmlArchiving mechanism is (currently) the only
	// mechanism that locks on multiple DataSources. Therefore we can avoid deadlocking by
	// simply ensuring that multiple XmlArchiving operations do not run at the same time.
	// Locking on each DataSource is still necessary though as we need to ensure consistency,
	// so ordinary CRUD operations must be forced to wait.

	// Transaction safe:
	// One inconvenience with XmlArchiving is that it does not run in a transaction.
	// Theoretically it may result in inconsistency, for instance if data is copied to the
	// archive DataSource but not removed from the original DataSource, or if data referencing
	// the data being archived is not updated appropriately when the archiving has been carried
	// through.
	// To prevent inconsistency a backup is created prior to archiving, and a transaction log
	// is committed before any changes are made. If an error occur, the transaction log and
	// backup is used to automatically bring the system back to a consistent state.

	$source = new SMDataSource($dsDef["Name"]);

	if ($source->GetDataSourceType() === SMDataSourceType::$Xml && isset($dsDef["XmlArchive"]) === true && isset($dsDef["XmlArchive"]["RecordLimit"]) === true)
	{
		// Avoid dead locks for XmlArchiving operations by locking on a resource common to all XmlArchiving operations.
		// XmlArchiving may be invoked multiple times on different DataSources defining "Relations",
		// potentially causing different XmlArchiving operations to wait for each other indefinitely (dead lock).
		// Obviously this has the inconvenient result that XmlArchiving for completely unrelated DataSources
		// will have to wait for each other, but that is acceptable.
		$lockSource = new SMDataSource("SMShopXmlArchiving");
		$lockSource->Lock();

		// WARNING: Do NOT lock on SMAttributes from this point on! It may cause a deadlock if SMAttributes is used in conjunction
		// with an ordinary CRUD operation that also needs to lock SMAttributes. Below is an example with two operations over time.
		// Session 1 performans an XmlArchiving operation while Session 2 performs an ordinary Create operation.
		// ------------------------------------------------------------------
		// SESSION 1 (XmlArchiving)          SESSION 2 (Create operation)
		// ------------------------------------------------------------------
		// Lock DS SMShopOrders
		//                                   Lock SMAttributes
		// Lock DS SMShopOrderEntries
		// Lock DS SMAttributes
		//                                   Lock DS SMShopOrderEntries
		// ------------------------------------------------------------------
		// Session 1 will wait for Session 2 to release the lock on SMAttributes,
		// and Session 2 will wait for Session 1 to release the lock on SMShopOrderEntries.
		// Therefore, the rule is; do NOT lock on SMAttributes during XmlArchiving. It is
		// allowed BEFORE the XmlArchiving operation starts and when it is completed.

		// Lock all DataSources

		$archive = new SMDataSource($dsDef["Name"] . "_archive");

		$source->Lock();
		$archive->Lock();

		$relationSource = null;
		$relationArchive = null;

		if (isset($dsDef["XmlArchive"]["Relations"]) === true)
		{
			foreach ($dsDef["XmlArchive"]["Relations"] as $relation)
			{
				$relationSource = new SMDataSource($relation["DataSource"]);
				$relationArchive = new SMDataSource($relation["DataSource"] . "_archive");

				$relationSource->Lock();
				$relationArchive->Lock();
			}
		}

		// MUST be locked AFTER ordinary DataSources - otherwise we might create
		// a dead lock since SMShopState may be used in conjunction with other
		// DataSource operations such as the Create operation. Locking SMShopState
		// first could cause the Xml Archiving operation and the Create operation
		// to wait for each other, just like the example with SMAttributes given
		// above.
		$state = new SMDataSource("SMShopState");
		$state->Lock();

		// Archive data if number of records in DataSource exceeds configured RecordLimit

		$count = $source->Count();

		if ($count > $dsDef["XmlArchive"]["RecordLimit"])
		{
			$backup = array();		// String[]
			$toCommit = array();	// SMDataSource[]
			$logEntries = array();	// SMKeyValueCollection[]
			$logEntry = null;		// SMKeyValueCollection

			$backup[] = "SMShopState";

			// Oldest records are selected first - will almost always return just one, except if
			// previous XmlArchiving operation somehow wasn't carried through, e.g. in case of timeout.
			$prevs = $source->Select("*", "", "", $count - $dsDef["XmlArchive"]["RecordLimit"]);

			if (count($prevs) > 0)
			{
				$toCommit[] = $source;
				$toCommit[] = $archive;
				$backup[] = $dsDef["Name"]; // Both normal DS and Archive DS will be backed up
			}

			foreach ($prevs as $prev)
			{
				// Transaction log entry
				$logEntry = new SMKeyValueCollection();
				$logEntry["DataSource"] = $dsDef["Name"];
				$logEntry["RowId"] = $prev["Id"];
				$logEntry["Operation"] = "Archiving";
				$logEntries[] = $logEntry;

				// Move record to archive
				$archive->Insert($prev);
				$source->Delete("Id = '" . $prev["Id"] . "'", "", 1);

				// Archive related data

				if (isset($dsDef["XmlArchive"]["Relations"]) === true)
				{
					foreach ($dsDef["XmlArchive"]["Relations"] as $relation)
					{
						$relationSource = new SMDataSource($relation["DataSource"]);
						$relationArchive = new SMDataSource($relation["DataSource"] . "_archive");

						$where = $relation["Reference"] . " = '" . $prev["Id"] . "'";
						$items = $relationSource->Select("*", $where);

						if (count($items) > 0)
						{
							$toCommit[] = $relationSource;
							$toCommit[] = $relationArchive;
							$backup[] = $relation["DataSource"]; // Both normal DS and Archive DS will be backed up
						}

						foreach ($items as $item)
						{
							// Transaction log entry
							$logEntry = new SMKeyValueCollection();
							$logEntry["DataSource"] = $relation["DataSource"];
							$logEntry["RowId"] = $item["Id"];
							$logEntry["Operation"] = "Archiving";
							$logEntries[] = $logEntry;

							// Copy row to archive
							$relationArchive->Insert($item);
						}

						// Remove archived data from original DataSource
						if (count($items) > 0)
						{
							$relationSource->Delete($where);

							// Trigger OnArchived callback if configured for related DataSource
							if (isset($relation["DataSourceRef"]["XmlArchive"]) === true && isset($relation["DataSourceRef"]["XmlArchive"]["Callbacks"]) === true && isset($relation["DataSourceRef"]["XmlArchive"]["Callbacks"]["Functions"]["OnArchived"]) === true)
							{
								require_once($relation["DataSourceRef"]["XmlArchive"]["Callbacks"]["File"]);

								foreach ($items as $item)
								{
									$relation["DataSourceRef"]["XmlArchive"]["Callbacks"]["Functions"]["OnArchived"]($item, $state);
								}
							}
						}
					}
				}

				if (isset($dsDef["XmlArchive"]["Callbacks"]) === true && isset($dsDef["XmlArchive"]["Callbacks"]["Functions"]["OnArchived"]) === true)
				{
					require_once($dsDef["Callbacks"]["File"]);
					$dsDef["XmlArchive"]["Callbacks"]["Functions"]["OnArchived"]($prev, $state);
				}
			}

			// Backup before commit - SMShopXmlArchivingEnsureConsistency() is responsible for restoring data in case of failure

			foreach ($backup as $dsName)
			{
				// This is not ideal - the backup code below depends on implementation details (filesystem details) about the XML DataSource mechanism!

				SMFileSystem::Copy(SMEnvironment::GetDataDirectory() . "/" . $dsName . ".xml.php", SMEnvironment::GetDataDirectory() . "/" . $dsName . ".xml.php.backup", true);

				if ($dsName !== "SMShopState")
					SMFileSystem::Copy(SMEnvironment::GetDataDirectory() . "/" . $dsName . "_archive.xml.php", SMEnvironment::GetDataDirectory() . "/" . $dsName . "_archive.xml.php.backup", true);
			}

			// Register transaction history before data is committed - if anything goes wrong (e.g. timeout or out-of-memory), this data will
			// remain and we can use it to check whether data is still in a consistent state or not and restore it from the backup if necessary.

			foreach ($logEntries as $le)
			{
				$lockSource->Insert($le);
			}

			$lockSource->Commit(false); // false = do not release lock

			// Commit everything at the end - doing this all at once reduces the risk of failure and inconsistency

			foreach ($toCommit as $tc)
			{
				$tc->Commit(false); // false = do not release lock
			}

			$state->Commit(false); // false = do not release lock

			// At this point all DataSources have been committed and it is safe to assume that everything
			// went well. Remove transaction log which is used to determine whether data is in an inconsistent state.

			$lockSource->Delete();
			$lockSource->Commit(false); // false = do not release lock
		}

		// Release all locks

		$source->Unlock();
		$archive->Unlock();

		if (isset($dsDef["XmlArchive"]["Relations"]) === true)
		{
			foreach ($dsDef["XmlArchive"]["Relations"] as $relation)
			{
				$relationSource = new SMDataSource($relation["DataSource"]);
				$relationArchive = new SMDataSource($relation["DataSource"] . "_archive");

				$relationSource->Unlck();
				$relationArchive->Unlck();
			}
		}

		$state->Unlock();
		$lockSource->Unlock();
	}
}

function SMShopXmlArchivingEnsureConsistency($dsDef)
{
	SMTypeCheck::CheckObject(__METHOD__, "dsDef", $dsDef, SMTypeCheckType::$Array);

	// Notice that no order data is lost when restoring data after a failed Xml Archiving operation.
	// Order information is committed before the Xml Archiving operation is executed, so the backup
	// will contain the most recent data.

	$lockSource = new SMDataSource("SMShopXmlArchiving");

	if ($lockSource->GetDataSourceType() === SMDataSourceType::$Xml)
	{
		if ($lockSource->IsLocked() === true)
		{
			// Skip consistency check if $lockSource is locked, which means that Xml Archiving/Recovery is currently
			// running. But only skip check if operation is related to a DataSource not currently undergoing XML Archiving
			// or Recovery - otherwise we might be affected by inconsistent/partial data.
			// Without this logic all operations would stall while perfoming XML Archiving/Recovery (SMShopXmlArchivingEnsureConsistency(..)
			// is called on every request) , making it impossible to e.g. add products to the basket (which reads product information)
			// while an Xml Archiving/Recovery operation related to Order data is in progress.

			if ($lockSource->Count("DataSource = '" . $dsDef["Name"] . "'") === 0)
			{
				return; // No Xml Archiving/Recovery is taking place for the DataSource related to the current request
			}
		}
		else if ($lockSource->Count() === 0)
		{
			// No entries found in transaction log so there is no data inconsistency
			return;
		}

		// Make sure multiple recovery operations do not run simultaneously, or while an Xml Archiving operation is running
		$lockSource->Lock();

		// Multiple recovery sessions may get past $lockSource->Count(..) above because
		// we lock AFTER the initial row count. Naturally we do this to prevent unnecessary
		// waiting since every DataSource operation executes SMShopXmlArchivingEnsureConsistency(..),
		// and they would all be forced to wait for each other (even though it's fairly fast).
		// Therefore we do a second count after the DataSource is locked to make sure another
		// session did not perform recovery first. If that's the case, we simply terminate recovery.
		if ($lockSource->Count() === 0)
		{
			$lockSource->Unlock();
			return;
		}

		SMLog::Log(__FILE__, __LINE__, "Restoring SMShop data after failed Xml Archiving: Running..");

		// Lock all DataSources that needs to be recovered to prevent inconsistency

		$sources = array();

		$entries = $lockSource->Select();
		foreach ($entries as $entry)
		{
			if (isset($sources[$entry["DataSource"]]) === true)
				continue;

			$sources[$entry["DataSource"]] = new SMDataSource($entry["DataSource"]);
			$sources[$entry["DataSource"]]->Lock();
		}

		// MUST be locked AFTER ordinary DataSources - otherwise we might create
		// a dead lock since SMShopState may be used in conjunction with other
		// DataSource operations such as the Create operation. Locking SMShopState
		// first could cause the Xml Archiving operation and the Create operation
		// to wait for each other, just like the example with SMAttributes given
		// in SMShopXmlArchiving(..)
		$sources["SMShopState"] = new SMDataSource("SMShopState");
		$sources["SMShopState"]->Lock();

		// Register transaction history before restoring data in case something goes wrong

		$logEntry = null;
		foreach ($sources as $dsName => $ds)
		{
			$logEntry = new SMKeyValueCollection();
			$logEntry["DataSource"] = $dsName;
			$logEntry["RowId"] = "";
			$logEntry["Operation"] = "Restoring";
			$lockSource->Insert($logEntry);
		}

		$lockSource->Commit(false); // false = do not release lock

		// Restore data from backup

		foreach ($sources as $dsName => $ds)
		{
			// This is not ideal! The recovery code below depends on implementation details (filesystem details) about the XML DataSource mechanism!

			SMFileSystem::Copy(SMEnvironment::GetDataDirectory() . "/" . $dsName . ".xml.php.backup", SMEnvironment::GetDataDirectory() . "/" . $dsName . ".xml.php", true);

			if ($dsName !== "SMShopState")
				SMFileSystem::Copy(SMEnvironment::GetDataDirectory() . "/" . $dsName . "_archive.xml.php.backup", SMEnvironment::GetDataDirectory() . "/" . $dsName . "_archive.xml.php", true);
		}

		// Clear transaction history

		$lockSource->Delete();
		$lockSource->Commit(false); // false = do not release lock

		// Release locks

		foreach ($sources as $dsName => $ds)
		{
			$ds->Unlock();
		}

		$lockSource->Unlock();

		SMLog::Log(__FILE__, __LINE__, "Restoring SMShop data after failed Xml Archiving: Done");
	}
}

function SMShopValidateDataSourceDefinitions($dataSourcesAllowed)
{
	if (SMTypeCheck::GetEnabled() === false)
		return;

	SMTypeCheck::CheckObject(__METHOD__, "dataSourcesAllowed", $dataSourcesAllowed, SMTypeCheckType::$Array);

	foreach ($dataSourcesAllowed as $name => $dsDef)
	{
		SMTypeCheck::CheckObject(__METHOD__, "dsDef", $dsDef, SMTypeCheckType::$Array);
		SMTypeCheck::CheckObject(__METHOD__, "dsDef[Name]", ((isset($dsDef["Name"]) === true) ? $dsDef["Name"] : null), SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "dsDef[AuthRequired]", ((isset($dsDef["AuthRequired"]) === true) ? $dsDef["AuthRequired"] : null), SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "dsDef[XmlLockRequired]", ((isset($dsDef["XmlLockRequired"]) === true) ? $dsDef["XmlLockRequired"] : null), SMTypeCheckType::$String);
		SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlTimeOut]", ((isset($dsDef["XmlTimeOut"]) === true) ? $dsDef["XmlTimeOut"] : null), SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlMemoryRequired]", ((isset($dsDef["XmlMemoryRequired"]) === true) ? $dsDef["XmlMemoryRequired"] : null), SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlArchive]", ((isset($dsDef["XmlArchive"]) === true) ? $dsDef["XmlArchive"] : null), SMTypeCheckType::$Array);
		SMTypeCheck::CheckObject(__METHOD__, "dsDef[OrderBy]", ((isset($dsDef["OrderBy"]) === true) ? $dsDef["OrderBy"] : null), SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "dsDef[Fields]", ((isset($dsDef["Fields"]) === true) ? $dsDef["Fields"] : null), SMTypeCheckType::$Array);

		if ($name !== $dsDef["Name"])
		{
			throw new Exception("DataSource '" . $name . "' has been configured with an incorrect Name attribute with a value of '" . $dsDef["Name"] . "'");
		}

		if (isset($dsDef["XmlArchive"]) === true)
		{
			SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlArchive][RecordLimit]", ((isset($dsDef["XmlArchive"]["RecordLimit"]) === true) ? $dsDef["XmlArchive"]["RecordLimit"] : null), SMTypeCheckType::$Integer);
			SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlArchive][Relations]", ((isset($dsDef["XmlArchive"]["Relations"]) === true) ? $dsDef["XmlArchive"]["Relations"] : null), SMTypeCheckType::$Array);
			SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlArchive][Callbacks]", ((isset($dsDef["XmlArchive"]["Callbacks"]) === true) ? $dsDef["XmlArchive"]["Callbacks"] : null), SMTypeCheckType::$Array);

			if (isset($dsDef["XmlArchive"]["Relations"]) === true)
			{
				foreach ($dsDef["XmlArchive"]["Relations"] as $rel)
				{
					SMTypeCheck::CheckObject(__METHOD__, "rel", $rel, SMTypeCheckType::$Array);
					SMTypeCheck::CheckObject(__METHOD__, "rel[DataSource]", ((isset($rel["DataSource"]) === true) ? $rel["DataSource"] : null), SMTypeCheckType::$String);
					//SMTypeCheck::CheckObject(__METHOD__, "rel[DataSourceRef]", ((isset($rel["DataSourceRef"]) === true) ? $rel["DataSourceRef"] : null), SMTypeCheckType::$Array);
					SMTypeCheck::CheckObject(__METHOD__, "rel[Reference]", ((isset($rel["Reference"]) === true) ? $rel["Reference"] : null), SMTypeCheckType::$String);
				}
			}

			if (isset($dsDef["XmlArchive"]["Callbacks"]) === true)
			{
				SMTypeCheck::CheckObject(__METHOD__, "dsDef[XmlArchive][Callbacks][File]", ((isset($dsDef["XmlArchive"]["Callbacks"]["File"]) === true) ? $dsDef["XmlArchive"]["Callbacks"]["File"] : null), SMTypeCheckType::$String);
				SMTypeCheck::CheckObject(__METHOD__, "dsDef[XmlArchive][Callbacks][Functions]", ((isset($dsDef["XmlArchive"]["Callbacks"]["Functions"]) === true) ? $dsDef["XmlArchive"]["Callbacks"]["Functions"] : null), SMTypeCheckType::$Array);
				SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlArchive][Callbacks][Functions][SelectArchiveWhen]", ((isset($dsDef["XmlArchive"]["Callbacks"]["Functions"]["SelectArchiveWhen"]) === true) ? $dsDef["XmlArchive"]["Callbacks"]["Functions"]["SelectArchiveWhen"] : null), SMTypeCheckType::$String);
				SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlArchive][Callbacks][Functions][OnArchived]", ((isset($dsDef["XmlArchive"]["Callbacks"]["Functions"]["OnArchived"]) === true) ? $dsDef["XmlArchive"]["Callbacks"]["Functions"]["OnArchived"] : null), SMTypeCheckType::$String);
			}
		}

		if (isset($dsDef["Fields"]["Id"]) === false)
		{
			throw new Exception("DataSource '" . $name . "' does not define the required Id field");
		}

		foreach ($dsDef["Fields"] as $fieldName => $fieldDef)
		{
			SMTypeCheck::CheckObject(__METHOD__, "fieldDef", $fieldDef, SMTypeCheckType::$Array);
			SMTypeCheck::CheckObject(__METHOD__, "fieldDef[DataType]", ((isset($fieldDef["DataType"]) === true) ? $fieldDef["DataType"] : null), SMTypeCheckType::$String);
			SMTypeCheck::CheckObject(__METHOD__, "fieldDef[MaxLength]", ((isset($fieldDef["MaxLength"]) === true) ? $fieldDef["MaxLength"] : null), SMTypeCheckType::$Integer);
			SMTypeCheck::CheckObjectAllowNull(__METHOD__, "fieldDef[ForceInitialValue]", ((isset($fieldDef["ForceInitialValue"]) === true) ? $fieldDef["ForceInitialValue"] : null), SMTypeCheckType::$String);

			if ($fieldDef["DataType"] !== "string" && $fieldDef["DataType"] !== "number")
			{
				throw new Exception("DataSource '" . $name . "' defines the field '" . $fieldName . "' with an invalid DataType");
			}
		}

		if (isset($dsDef["Callbacks"]) === true)
		{
			SMTypeCheck::CheckObject(__METHOD__, "dsDef[Callbacks]", $dsDef["Callbacks"], SMTypeCheckType::$Array);
			SMTypeCheck::CheckObject(__METHOD__, "dsDef[Callbacks][File]", ((isset($dsDef["Callbacks"]["File"]) === true) ? $dsDef["Callbacks"]["File"] : null), SMTypeCheckType::$String);
			SMTypeCheck::CheckObject(__METHOD__, "dsDef[Callbacks][Functions]", ((isset($dsDef["Callbacks"]["Functions"]) === true) ? $dsDef["Callbacks"]["Functions"] : null), SMTypeCheckType::$Array);

			foreach ($dsDef["Callbacks"]["Functions"] as $key => $val)
			{
				SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[Callbacks][Functions][" . $key . "]", $dsDef["Callbacks"]["Functions"][$key], SMTypeCheckType::$String);
			}
		}
	}
}

function SMShopValidateJsonData($dataSources, $data)
{
	// Use instance of SMTypeChecker (rather than static functions on SMTypeCheck) which will always perform type checks,
	// even when Debug Mode is disabled. We should always validate arbitrary input data to make sure it is valid and safe to use.
	$checker = new SMTypeChecker();

	$checker->CheckObject(__METHOD__, "dataSources", $dataSources, SMTypeCheckType::$Array);
	$checker->CheckObject(__METHOD__, "data", $data, SMTypeCheckType::$Array);

	if (isset($data["Model"]) === false || isset($data["Properties"]) === false || isset($data["Operation"]) === false)
	{
		throw new Exception("JSON data is not compatible with data interface - Model, Properties, and Operation must be specified");
	}

	$checker->CheckObject(__METHOD__, "data[Model]", $data["Model"], SMTypeCheckType::$String);
	$checker->CheckObject(__METHOD__, "data[Properties]", $data["Properties"], SMTypeCheckType::$Array);
	$checker->CheckObject(__METHOD__, "data[Operation]", $data["Operation"], SMTypeCheckType::$String);

	if (isset($dataSources[$data["Model"]]) === false)
	{
		throw new Exception("Invalid data source '" . $data["Model"] . "'");
	}

	$dsDef = $dataSources[$data["Model"]];

	if (in_array($data["Operation"], $dsDef["AuthRequired"]) === true && SMAuthentication::Authorized() === false)
	{
		throw new Exception("Unauthorized - data source '" . $data["Model"] . "' requires authentication for operation '" . $data["Operation"] . "'");
	}

	foreach ($data["Properties"] as $prop => $val)
	{
		SMShopValidateField($dsDef, $prop, $val); // Make sure property/field is allowed, that data type is valid, and that max length is not exceeded
	}

	$match = array();

	if (isset($data["Match"]) === true)
	{
		$checker->CheckObject(__METHOD__, "data[Match]", $data["Match"], SMTypeCheckType::$Array);
		$match = $data["Match"];
	}

	foreach ($match as $or)
	{
		$checker->CheckObject(__METHOD__, "or", $or, SMTypeCheckType::$Array);

		foreach ($or as $m)
		{
			if (isset($m["Field"]) === false || isset($m["Operator"]) === false || isset($m["Value"]) === false)
			{
				throw new Exception("Filtering (Match) requires a Field, an Operator, and a Value");
			}

			SMShopValidateField($dsDef, $m["Field"], $m["Value"], true); // Passing true to skip MaxLength check which a search value is allowed to exceed

			if ($m["Operator"] !== "CONTAINS" && $m["Operator"] !== "=" && $m["Operator"] !== "!=" && $m["Operator"] !== "<" && $m["Operator"] !== "<=" && $m["Operator"] !== ">" && $m["Operator"] !== ">=")
			{
				throw new Exception("Match operator '" . $m["Operator"] . "' is not supported");
			}
		}
	}
}

function SMShopDataItemToJson($dsDef, $props, SMKeyValueCollection $item)
{
	SMTypeCheck::CheckObject(__METHOD__, "dsDef", $dsDef, SMTypeCheckType::$Array);
	SMTypeCheck::CheckObject(__METHOD__, "props", $props, SMTypeCheckType::$Array);

	$res = "";

	foreach (array_keys($props) as $prop)  // Using properties from request to prevent any other data in DataSource from being returned (optimization), and to make sure JSON is returned in proper casing (DataSource column names are always lower cased)
	{
		$res .= (($res !== "") ? ", " : "") . "\"" . $prop . "\": " . (($dsDef["Fields"][$prop]["DataType"] === "string") ? "\"" . (($item[$prop] !== null) ? SMStringUtilities::JsonEncode($item[$prop]) : "") . "\"" : (($item[$prop] !== null && $item[$prop] !== "") ? $item[$prop] : "0"));
	}

	return "{" . $res . "}";
}

function SMShopValidateField($dsDef, $fieldName, $fieldValue, $skipMaxLengthCheck = false) // Argument $fieldValue is a mixed type - double, float, or string - checked further down
{
	SMTypeCheck::CheckObject(__METHOD__, "dsDef", $dsDef, SMTypeCheckType::$Array);
	SMTypeCheck::CheckObject(__METHOD__, "fieldName", $fieldName, SMTypeCheckType::$String);
	SMTypeCheck::CheckObject(__METHOD__, "skipMaxLengthCheck", $skipMaxLengthCheck, SMTypeCheckType::$Boolean);

	if (isset($dsDef["Fields"][$fieldName]) === false)
	{
		throw new Exception("Field '" . $fieldName . "' not found in DataSource definition");
	}

	if ($skipMaxLengthCheck === false && strlen($fieldValue) > $dsDef["Fields"][$fieldName]["MaxLength"])
	{
		throw new Exception("Field '" . $fieldName . "' exceeds MaxLength '" . $dsDef["Fields"][$fieldName]["MaxLength"] . "'");
	}

	if (SMShopGetJsType($fieldValue) !== $dsDef["Fields"][$fieldName]["DataType"])
	{
		throw new Exception("Field '" . $fieldName . "' was not passed as type '" . $dsDef["Fields"][$fieldName]["DataType"] . "'");
	}
}

function SMShopGetJsType($val) // Mixed type
{
	$type = gettype($val);

	if ($type === "double" || $type === "integer")
		return "number";

	return $type;
}

// ==================================================================
// Execute operation
// ==================================================================

$state = new SMDataSource("SMShopState"); // IMPORTANT: Always lock on this DS when writing data! It contains important data such as NextOrderId and NextInvoiceId! ONLY lock for a very small amount of time - it is constantly being used!

// Validate DataSource definitions to make sure they are valid

try
{
	SMShopValidateDataSourceDefinitions($dataSourcesAllowed);
}
catch (Exception $ex)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Exception occurred validating DataSource definitions: " . $ex->getMessage();
	exit;
}

// Handle XML archiving (maintenance operation)

// For DataSources defining XmlArchive, make sure to provide an object reference to any related DataSources
foreach ($dataSourcesAllowed as $dsName => $dsDef)
{
	if (isset($dsDef["XmlArchive"]) === true && isset($dsDef["XmlArchive"]["Relations"]) === true)
	{
		for ($i = 0 ; $i < count($dsDef["XmlArchive"]["Relations"]) ; $i++)
		{
			if (isset($dataSourcesAllowed[$dsDef["XmlArchive"]["Relations"][$i]["DataSource"]]) === false)
			{
				header("HTTP/1.1 500 Internal Server Error");
				echo "DataSource '" . $dsName . "' defines a relation to a non-existing DataSource";
				exit;
			}

			$dataSourcesAllowed[$dsName]["XmlArchive"]["Relations"][$i]["DataSourceRef"] = $dataSourcesAllowed[$dsDef["XmlArchive"]["Relations"][$i]["DataSource"]];
		}
	}
}

// Perform archiving
$toXmlArchive = SMEnvironment::GetQueryValue("XmlArchiving", SMValueRestriction::$AlphaNumeric);
if ($toXmlArchive !== null)
{
	if ($state->Count("key = 'AllowXmlArchiving" . $toXmlArchive . "'") !== 0)
	{
		$state->Lock();
		$state->Delete("key = 'AllowXmlArchiving" . $toXmlArchive . "'");
		$state->Commit();

		// From the PHP manual - http://php.net/manual/en/function.session-write-close.php
		// "Session data is usually stored after your script terminated without the
		// need to call session_write_close(), but as session data is locked to prevent
		// concurrent writes only one script may operate on a session at any time".
		// The user triggering Xml Archiving will not be able to load new pages
		// while the XML Archiving operation is running, unless we "unlock" the session.
		// But obviously session state will not work properly from this point on !
		//session_write_close(); // DISABLED, SMEnvironment::CloseSession() is now always called in the beginning of this file

		SMShopXmlArchiving($dataSourcesAllowed[$toXmlArchive]);
	}

	exit;
}

// Read and check input data

$json = SMEnvironment::GetJsonData();

try
{
	SMShopValidateJsonData($modelDataSources, $json);
}
catch (Exception $ex)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Exception occurred validating input data (JSON): " . $ex->getMessage();
	exit;
}

// At this point $json has been validated.
// We can trust that the model exists,
// that properties are valid and allowed,
// and that the user is authorized if needed.
// No input sanitation has been performed though!

$model = $json["Model"];
$props = $json["Properties"];
$command = $json["Operation"];
$match = ((isset($json["Match"]) === true) ? $json["Match"] : null);

$dsDef = $modelDataSources[$model];

// Data consistency check (XML Archiving related)

// Executed for all requests to ensure consistency.
// This function will recover data from an interrupted/failed Xml Archiving operation.
SMShopXmlArchivingEnsureConsistency($dsDef);

// Sanitize input

foreach ($props as $prop => $val)
{
	if ($dsDef["Fields"][$prop]["DataType"] === "string")
		$props[$prop] = strip_tags($val);
}

// Initialize data source

$ds = new SMDataSource($dsDef["Name"]);

if ($ds->GetDataSourceType() === SMDataSourceType::$Xml && $dsDef["XmlTimeOut"] > 0)
{
	// Notice: Server configuration could potentially prevent this value from being changed,
	// in which case a timeout may occure if huge amounts of data is contained in DataSource,
	// and the server has limited resources.
	set_time_limit($dsDef["XmlTimeOut"]); // Make sure a potentially large XML based DataSource have sufficient time to process data
}

if ($ds->GetDataSourceType() === SMDataSourceType::$Xml && $dsDef["XmlMemoryRequired"] > 0)
{
	$mem = $dsDef["XmlMemoryRequired"];

	// Notice: Server configuration could potentially prevent this value from being changed (e.g. using Suhosin), in which
	// case problems may occure if huge amounts of data is contained in DataSource, and the server has limited resources.
	$res = ini_set("memory_limit", $mem . "M"); // Make sure a potentially large XML based DataSource have sufficient memory to process data

	if ($res === false && $mem >= 128)
	{
		$mem = $mem / 2;
		$res = ini_set("memory_limit", $mem . "M");

		if ($res === false)
		{
			$mem = $mem / 2;
			ini_set("memory_limit", $mem . "M");
		}
	}
}

if ($ds->GetDataSourceType() === SMDataSourceType::$Xml && in_array($command, $dsDef["XmlLockRequired"]) === true)
	$ds->Lock(); // Make sure two sessions do not write data at the same time, potentially causing session X to overwrite data from session Y

// Check weather item exists for Create/Retrieve/Update/Delete

$exists = ($ds->Count("Id = '" . $ds->Escape($props["Id"]) . "'") > 0);

if ($command === "Create" && $exists === true)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Operation '" . $command . "' failed - " . $model . " item with ID '" . $props["Id"] . "' already exists";
	exit;
}
if (($command === "Retrieve" || $command === "Update" || $command === "Delete") && $exists === false)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Operation '" . $command . "' failed - " . $model . " item with ID '" . $props["Id"] . "' could not be found";
	exit;
}

// Update data source

if ($command === "Create")
{
	$item = new SMKeyValueCollection();
	foreach ($props as $prop => $val)
	{
		if (isset($dsDef["Fields"][$prop]["ForceInitialValue"]) === true)
		{
			$item[$prop] = $dsDef["Fields"][$prop]["ForceInitialValue"];
			continue;
		}

		$item[$prop] = (string)$val;
	}

	if (isset($dsDef["Callbacks"]) === true && isset($dsDef["Callbacks"]["Functions"]["Create"]) === true)
	{
		require_once($dsDef["Callbacks"]["File"]);
		$dsDef["Callbacks"]["Functions"]["Create"]($item);
	}

	$ds->Insert($item);
	$ds->Commit();

	if (isset($dsDef["Callbacks"]) === true && isset($dsDef["Callbacks"]["Functions"]["CreateCompleted"]) === true)
	{
		require_once($dsDef["Callbacks"]["File"]);
		$dsDef["Callbacks"]["Functions"]["CreateCompleted"]($item);
	}

	if ($ds->GetDataSourceType() === SMDataSourceType::$Xml && isset($dsDef["XmlArchive"]) === true && isset($dsDef["XmlArchive"]["RecordLimit"]) === true)
	{
		// Make JSShop trigger a maintenance request to start Xml Archiving if needed.
		// This is done asynchronously since it may take several seconds to complete.

		$state->Lock();

		$allow = new SMKeyValueCollection();
		$allow["key"] = "AllowXmlArchiving" . $dsDef["Name"];
		$allow["value"] = "true";

		$state->Insert($allow);
		$state->Commit();

		header("Post-Process-Url: [same]");
		header("Post-Process-Arguments: XmlArchiving=" . $dsDef["Name"]);
	}

	echo SMShopDataItemToJson($dsDef, $props, $item); // Return updated data to client
}
else if ($command === "Retrieve")
{
	$items = $ds->Select("*", "Id = '" . $ds->Escape($props["Id"]) . "'", "");

	if (count($items) === 0 && $ds->GetDataSourceType() === SMDataSourceType::$Xml && isset($dsDef["XmlArchive"]) === true)
	{
		// Item not found - search archive
		$dsArch = new SMDataSource($dsDef["Name"] . "_archive");
		$items = $dsArch->Select("*", "Id = '" . $dsArch->Escape($props["Id"]) . "'", "");
	}

	if (count($items) > 0)
	{
		$item = $items[0];

		if (isset($dsDef["Callbacks"]) === true && isset($dsDef["Callbacks"]["Functions"]["Retrieve"]) === true)
		{
			require_once($dsDef["Callbacks"]["File"]);
			$dsDef["Callbacks"]["Functions"]["Retrieve"]($item);
		}

		echo SMShopDataItemToJson($dsDef, $props, $item);
	}
}
else if ($command === "RetrieveAll")
{
	$where = "";

	if ($match !== null)
	{
		$nextOr = false;
		$nextAnd = false;

		foreach ($match as $or)
		{
			if ($nextOr === true)
				$where .= " OR ";

			$nextAnd = false;

			foreach ($or as $m)
			{
				$where .= (($nextAnd === true) ? " AND " : "") . $m["Field"] . " " . (($m["Operator"] === "CONTAINS") ? "LIKE" : $m["Operator"]) . " " . ((SMShopGetJsType($m["Value"]) === "string") ? "'" . (($m["Operator"] === "CONTAINS") ? "%" : "") . $ds->Escape($m["Value"]) . (($m["Operator"] === "CONTAINS") ? "%" : "") . "'" : $m["Value"]);
				$nextAnd = true;
			}

			$nextOr = true;
		}
	}

	$dataSources = array($ds);
	$items = array();

	if ($ds->GetDataSourceType() === SMDataSourceType::$Xml && isset($dsDef["XmlArchive"]) === true && isset($dsDef["XmlArchive"]["Callbacks"]) === true && isset($dsDef["XmlArchive"]["Callbacks"]["Functions"]["SelectArchiveWhen"]) === true)
	{
		// Include archived data if SelectArchiveWhen(..) returns true

		require_once($dsDef["XmlArchive"]["Callbacks"]["File"]);

		if ($dsDef["XmlArchive"]["Callbacks"]["Functions"]["SelectArchiveWhen"]((($match !== null) ? $match : array())) === true)
			$dataSources[] = new SMDataSource($dsDef["Name"] . "_archive");
	}

	foreach ($dataSources as $d)
	{
		// This will produce a collection with archived data first followed by more current data
		$items = array_merge($d->Select("*", $where, $dsDef["OrderBy"]), $items);
	}

	if (isset($dsDef["Callbacks"]) === true && isset($dsDef["Callbacks"]["Functions"]["RetrieveAll"]) === true)
	{
		require_once($dsDef["Callbacks"]["File"]);
		$dsDef["Callbacks"]["Functions"]["RetrieveAll"]($items);
	}

	$jsonItem = "";
	$jsonItems = "";

	foreach ($items as $item)
	{
		$jsonItem = SMShopDataItemToJson($dsDef, $props, $item);
		$jsonItems .= (($jsonItems !== "") ? ", " : "") . $jsonItem;
	}

	echo "[" . $jsonItems . "]";
}
else if ($command === "Update")
{
	$item = new SMKeyValueCollection();
	foreach ($props as $prop => $val)
		$item[$prop] = (string)$val;

	if (isset($dsDef["Callbacks"]) === true && isset($dsDef["Callbacks"]["Functions"]["Update"]) === true)
	{
		require_once($dsDef["Callbacks"]["File"]);
		$dsDef["Callbacks"]["Functions"]["Update"]($item);
	}

	$ds->Update($item, "Id = '" . $ds->Escape($props["Id"]) . "'");
	$ds->Commit();

	if (isset($dsDef["Callbacks"]) === true && isset($dsDef["Callbacks"]["Functions"]["UpdateCompleted"]) === true)
	{
		require_once($dsDef["Callbacks"]["File"]);
		$dsDef["Callbacks"]["Functions"]["UpdateCompleted"]($item);
	}

	SMShopDataItemToJson($dsDef, $props, $item); // Return updated data to client
}
else if ($command === "Delete")
{
	if (isset($dsDef["Callbacks"]) === true && isset($dsDef["Callbacks"]["Functions"]["Delete"]) === true)
	{
		require_once($dsDef["Callbacks"]["File"]);
		$dsDef["Callbacks"]["Functions"]["Delete"]($ds->Escape($props["Id"]));
	}

	$ds->Delete("Id = '" . $ds->Escape($props["Id"]) . "'");
	$ds->Commit();

	if (isset($dsDef["Callbacks"]) === true && isset($dsDef["Callbacks"]["Functions"]["DeleteCompleted"]) === true)
	{
		require_once($dsDef["Callbacks"]["File"]);
		$dsDef["Callbacks"]["Functions"]["DeleteCompleted"]($ds->Escape($props["Id"]));
	}
}

?>
