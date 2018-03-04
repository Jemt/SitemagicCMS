<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

$ip = SMEnvironment::GetEnvironmentValue("REMOTE_ADDR");
$ip = (($ip !== null) ? $ip : "");

// DataSource definitions.
// NOTICE: Some fields have MaxLength multiplied by 8 to support the length of encoded Unicode characters.

// IMPORTANT (regarding above): tables.sql does NOT support x8 characters !!! Many fields are varchar(250) !!!

$dataSourcesAllowed = array
(
	/*"SMShopExample" => array
	(
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
			"Price"					=> array("DataType" => "number", "MaxLength" => 100),
			"Vat"					=> array("DataType" => "number", "MaxLength" => 100),
			"Currency"				=> array("DataType" => "string", "MaxLength" => 3),
			"Weight"				=> array("DataType" => "number", "MaxLength" => 100),
			"WeightUnit"			=> array("DataType" => "string", "MaxLength" => 3),
			"DeliveryTime"			=> array("DataType" => "string", "MaxLength" => 50*8),
			"DiscountExpression"	=> array("DataType" => "string", "MaxLength" => 250),
			"DiscountMessage"		=> array("DataType" => "string", "MaxLength" => 250*8)
		)
	),
	"SMShopOrders" => array
	(
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
			"Price"					=> array("DataType" => "number", "MaxLength" => 100),
			"Vat"					=> array("DataType" => "number", "MaxLength" => 100),
			"Currency"				=> array("DataType" => "string", "MaxLength" => 3),
			"Weight"				=> array("DataType" => "number", "MaxLength" => 100),
			"WeightUnit"			=> array("DataType" => "string", "MaxLength" => 3),
			"CostCorrection1"		=> array("DataType" => "number", "MaxLength" => 100),
			"CostCorrectionVat1"	=> array("DataType" => "number", "MaxLength" => 100),
			"CostCorrectionMessage1"=> array("DataType" => "string", "MaxLength" => 250*8),
			"CostCorrection2"		=> array("DataType" => "number", "MaxLength" => 100),
			"CostCorrectionVat2"	=> array("DataType" => "number", "MaxLength" => 100),
			"CostCorrectionMessage2"=> array("DataType" => "string", "MaxLength" => 250*8),
			"CostCorrection3"		=> array("DataType" => "number", "MaxLength" => 100),
			"CostCorrectionVat3"	=> array("DataType" => "number", "MaxLength" => 100),
			"CostCorrectionMessage3"=> array("DataType" => "string", "MaxLength" => 250*8),
			"PaymentMethod"			=> array("DataType" => "string", "MaxLength" => 50),
			"TransactionId"			=> array("DataType" => "string", "MaxLength" => 100),
			"State"					=> array("DataType" => "string", "MaxLength" => 20),
			"PromoCode"				=> array("DataType" => "string", "MaxLength" => 30*8),
			"CustData1"				=> array("DataType" => "string", "MaxLength" => 250*8),
			"CustData2"				=> array("DataType" => "string", "MaxLength" => 250*8),
			"CustData3"				=> array("DataType" => "string", "MaxLength" => 250*8),
/* Why string and not number/int ?? */			"InvoiceId"				=> array("DataType" => "string", "MaxLength" => 50, "ForceInitialValue" => ""), // Set initial value to prevent client from assigning value
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
			"UnitPrice"				=> array("DataType" => "number", "MaxLength" => 100),
			"Vat"					=> array("DataType" => "number", "MaxLength" => 100),
			"Currency"				=> array("DataType" => "string", "MaxLength" => 3),
			"Units"					=> array("DataType" => "number", "MaxLength" => 100), /* Why a number with 100 digits ? 10-20 should suffice */
			"Discount"				=> array("DataType" => "number", "MaxLength" => 100),
			"DiscountMessage"		=> array("DataType" => "string", "MaxLength" => 250*8)
		)
	)
);

// Functions

function SMShopDataItemToJson($dsDef, $props, SMKeyValueCollection $item)
{
	$res = "";

	foreach (array_keys($props) as $prop)  // Using properties from request to prevent any other data in DataSource from being returned (optimization), and to make sure JSON is returned in proper casing (DataSource column names are always lower cased)
	{
		$res .= (($res !== "") ? ", " : "") . "\"" . $prop . "\": " . (($dsDef["Fields"][$prop]["DataType"] === "string") ? "\"" . (($item[$prop] !== null) ? SMStringUtilities::JsonEncode($item[$prop]) : "") . "\"" : (($item[$prop] !== null && $item[$prop] !== "") ? $item[$prop] : "0"));

		/*$res .= (($res !== "") ? ", " : "");
		$res .= "\"" . $prop . "\": ";

		if ($dsDef["Fields"][$prop]["DataType"] === "string")
		{
			$res .= "\"" . (($item[$prop] !== null) ? SMShopEscapeJsonValue($item[$prop]) : "") . "\"";
		}
		else // Number
		{
			if ($item[$prop] === null || $item[$prop] === "")
				$res .= "0";
			else if ((string)(int)$item[$prop] === (string)(float)$item[$prop]) // Int
				$res .= $item[$prop];
			else // Float - round off
				$res .= (string)round((float)$item[$prop], 2);
		}*/
	}

	return "{" . $res . "}";
}

function SMShopValidateField($dsDef, $fieldName, $fieldValue)
{
	if (isset($dsDef["Fields"][$fieldName]) === false)
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Field '" . $fieldName . "' not found in DataSource definition";
		exit;
	}

	if (strlen($fieldValue) > $dsDef["Fields"][$fieldName]["MaxLength"])
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Field '" . $fieldName . "' exceeds MaxLength " . $dsDef["Fields"][$fieldName]["MaxLength"];
		exit;
	}

	if (SMShopGetJsType($fieldValue) !== $dsDef["Fields"][$fieldName]["DataType"])
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Field '" . $fieldName . "' was not passed as type '" . $dsDef["Fields"][$fieldName]["DataType"] . "'";
		exit;
	}
}

function SMShopGetJsType($val)
{
	$type = gettype($val);

	if ($type === "double" || $type === "integer")
		return "number";

	return $type;
}

// Read data

$json = SMEnvironment::GetJsonData(); //SMShopGetJsonData();
$model = $json["Model"];
$props = $json["Properties"];
$command = $json["Operation"];
$match = ((isset($json["Match"]) === true) ? $json["Match"] : null);

$dataSourceName = "SMShop" . (($model !== "OrderEntry") ? $model . "s" : "OrderEntries"); // $model contains e.g. "Product", "Order", or "OrderEntry"

// Make sure DataSource is supported

if (in_array($dataSourceName, array_keys($dataSourcesAllowed), true) === false)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Invalid data source";
	exit;
}

$dsDef = $dataSourcesAllowed[$dataSourceName];

// Make sure user is authorized for operations requiring authorization

if (in_array($command, $dsDef["AuthRequired"]) === true && SMAuthentication::Authorized() === false)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Unauthorized - '" . $model . "' requires authentication for operation '" . $command . "'";
	exit;
}

// Sanitize input

foreach ($props as $prop => $val)
{
	SMShopValidateField($dsDef, $prop, $val);

	if ($dsDef["Fields"][$prop]["DataType"] === "string")
		$props[$prop] = strip_tags($val);
}

foreach ((($match !== null) ? $match : array()) as $m)
{
	SMShopValidateField($dsDef, $m["Field"], $m["Value"]);

	if ($m["Operator"] !== "=" && $m["Operator"] !== "!=" && $m["Operator"] !== "<" && $m["Operator"] !== "<=" && $m["Operator"] !== ">" && $m["Operator"] !== ">=")
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo "Match operator '" . $m["Operator"] . "' is not supported";
		exit;
	}
}

// Initialize data source

$ds = new SMDataSource($dataSourceName);

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
	$items = $ds->Select("*", "Id = '" . $ds->Escape($props["Id"]) . "'", "", 500); // Notice: Hardcoded limit

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
		foreach ($match as $m)
		{
			$where .= (($where !== "") ? " AND " : "") . $m["Field"] . " " . $m["Operator"] . " " . ((SMShopGetJsType($m["Value"]) === "string") ? "'" . $ds->Escape($m["Value"]) . "'" : $m["Value"]);
		}
	}

	$items = $ds->Select("*", $where, $dsDef["OrderBy"], 500); // Notice: Hardcoded limit

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
