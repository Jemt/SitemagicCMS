<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

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
		"XmlTimeOut"		=> 180,		// Optional
		"XmlMemoryRequired"	=> 512,		// Optional
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
			"CategoryId"			=> array("DataType" => "string", "MaxLength" => 50+20), // Only used to generate URLs (up to 50 ASCII characters + 20 characters for a hash code appended)
			"Title"					=> array("DataType" => "string", "MaxLength" => 250*8),
			"Description"			=> array("DataType" => "string", "MaxLength" => 1000*8),
			"Images"				=> array("DataType" => "string", "MaxLength" => 1000),
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

function SMShopValidateDataSourceDefinitions($dataSourcesAllowed)
{
	if (SMTypeCheck::GetEnabled() === false)
		return;

	SMTypeCheck::CheckObject(__METHOD__, "dataSourcesAllowed", $dataSourcesAllowed, SMTypeCheckType::$Array);

	foreach ($dataSourcesAllowed as $name => $dsDef)
	{
		SMTypeCheck::CheckObject(__METHOD__, "dsDef", $dsDef, SMTypeCheckType::$Array);
		SMTypeCheck::CheckObject(__METHOD__, "dsDef[Name]", $dsDef["Name"], SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "dsDef[AuthRequired]", $dsDef["AuthRequired"], SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "dsDef[XmlLockRequired]", $dsDef["XmlLockRequired"], SMTypeCheckType::$String);
		SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlTimeOut]", $dsDef["XmlTimeOut"], SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObjectAllowNull(__METHOD__, "dsDef[XmlMemoryRequired]", $dsDef["XmlMemoryRequired"], SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "dsDef[OrderBy]", $dsDef["OrderBy"], SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "dsDef[Fields]", $dsDef["Fields"], SMTypeCheckType::$Array);

		if ($name !== $dsDef["Name"])
		{
			throw new Exception("DataSource '" . $name . "' has been configured with an incorrect Name attribute with a value of '" . $dsDef["Name"] . "'");
		}

		if (isset($dsDef["Fields"]["Id"]) === false)
		{
			throw new Exception("DataSource '" . $name . "' does not define the required Id field");
		}

		foreach ($dsDef["Fields"] as $fieldName => $fieldDef)
		{
			SMTypeCheck::CheckObject(__METHOD__, "fieldDef", $fieldDef, SMTypeCheckType::$Array);
			SMTypeCheck::CheckObject(__METHOD__, "fieldDef[DataType]", $fieldDef["DataType"], SMTypeCheckType::$String);
			SMTypeCheck::CheckObject(__METHOD__, "fieldDef[MaxLength]", $fieldDef["MaxLength"], SMTypeCheckType::$Integer);

			if (isset($fieldDef["ForceInitialValue"]) === true)
				SMTypeCheck::CheckObject(__METHOD__, "fieldDef[ForceInitialValue]", $fieldDef["ForceInitialValue"], SMTypeCheckType::$String);

			if ($fieldDef["DataType"] !== "string" && $fieldDef["DataType"] !== "number")
			{
				throw new Exception("DataSource '" . $name . "' defines the field '" . $fieldName . "' with an invalid DataType");
			}
		}

		if (isset($dsDef["Callbacks"]) === true)
		{
			SMTypeCheck::CheckObject(__METHOD__, "dsDef[Callbacks]", $dsDef["Callbacks"], SMTypeCheckType::$Array);
			SMTypeCheck::CheckObject(__METHOD__, "dsDef[Callbacks][File]", $dsDef["Callbacks"]["File"], SMTypeCheckType::$String);
			SMTypeCheck::CheckObject(__METHOD__, "dsDef[Callbacks][Functions]", $dsDef["Callbacks"]["Functions"], SMTypeCheckType::$Array);

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

	echo SMShopDataItemToJson($dsDef, $props, $item); // Return updated data to client
}
else if ($command === "Retrieve")
{
	$items = $ds->Select("*", "Id = '" . $ds->Escape($props["Id"]) . "'", "");

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

	$items = $ds->Select("*", $where, $dsDef["OrderBy"]);

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
