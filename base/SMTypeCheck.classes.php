<?php

/// <container name="base/SMTypeCheckType">
/// 	Enum representing data type to check for
/// </container>
class SMTypeCheckType
{
	/// <member container="base/SMTypeCheckType" name="String" access="public" static="true" type="string" default="String"></member>
	public static $String = "String";

	/// <member container="base/SMTypeCheckType" name="Integer" access="public" static="true" type="string" default="Integer"></member>
	public static $Integer = "Integer";

	/// <member container="base/SMTypeCheckType" name="Boolean" access="public" static="true" type="string" default="Boolean"></member>
	public static $Boolean = "Boolean";

	/// <member container="base/SMTypeCheckType" name="Array" access="public" static="true" type="string" default="Array"></member>
	public static $Array = "Array";

	/// <member container="base/SMTypeCheckType" name="Float" access="public" static="true" type="string" default="Float"></member>
	public static $Float = "Float";

	/// <member container="base/SMTypeCheckType" name="Object" access="public" static="true" type="string" default="Object"></member>
	public static $Object = "Object";

	/// <member container="base/SMTypeCheckType" name="Null" access="public" static="true" type="string" default="Null"></member>
	public static $Null = "Null";

	/// <member container="base/SMTypeCheckType" name="Resource" access="public" static="true" type="string" default="Resource"></member>
	public static $Resource = "Resource";
}

/// <container name="base/SMTypeChecker">
/// 	Class contains functionality used to validate data.
/// 	Most often SMTypeCheck is used instead of SMTypeChecker
/// 	since all the same functions are exposed as static members,
/// 	allowing for validation without creating an instance of SMTypeChecker.
///
/// 	However, there is one important difference: SMTypeChecker
/// 	always performs type checking while SMTypeCheck may skip
/// 	type checking if Debug Mode is disabled, which is usually the
/// 	case for production systems.
/// </container>
class SMTypeChecker
{
	public function __construct()
	{
	}

	/// <function container="base/SMTypeChecker" name="CheckObject" access="public" returns="boolean">
	/// 	<description> Check object to ensure certain data type </description>
	/// 	<param name="methodName" type="string">
	/// 		Name of function from which check is performed, which is used for logging purposes. It is
	/// 		recommended to specify __METHOD__ which will evaluate to the name of the function at run time.
	/// 	</param>
	/// 	<param name="objectName" type="string"> Name of variable to check, which is used for logging purposes. </param>
	/// 	<param name="object" type="object"> Pass object to check </param>
	/// 	<param name="typeExpected" type="SMTypeCheckType | string">
	/// 		Specify expected type, which can be either a value of SMTypeCheckType or the name of a class.
	/// 	</param>
	/// 	<param name="throwExceptionOnTypeError" type="boolean" default="true">
	/// 		Set False to suppress exception being thrown if an object is not of the expected
	/// 		type. In this case True or False is returned to indicate whether object is of
	/// 		the expected type.
	/// 	</param>
	/// </function>
	public function CheckObject($methodName, $objectName, $object, $typeExpected, $throwExceptionOnTypeError = true)
	{
		// Types are validated in checkObj(..) - not validated here due to performance
		return $this->checkObj($methodName, $objectName, $object, $typeExpected, false, $throwExceptionOnTypeError);
	}

	/// <function container="base/SMTypeChecker" name="CheckObjectAllowNull" access="public" returns="boolean">
	/// 	<description> Same as CheckObject(..), but object being checked is allowed to be Null </description>
	/// 	<param name="methodName" type="string"> See CheckObject(..) function for description </param>
	/// 	<param name="objectName" type="string"> See CheckObject(..) function for description </param>
	/// 	<param name="object" type="object"> See CheckObject(..) function for description </param>
	/// 	<param name="typeExpected" type="SMTypeCheckType | string"> See CheckObject(..) function for description </param>
	/// 	<param name="throwExceptionOnTypeError" type="boolean" default="true"> See CheckObject(..) function for description </param>
	/// </function>
	public function CheckObjectAllowNull($methodName, $objectName, $object, $typeExpected, $throwExceptionOnTypeError = true)
	{
		// Types are validated in checkObj(..) - not validated here due to performance
		return $this->checkObj($methodName, $objectName, $object, $typeExpected, true, $throwExceptionOnTypeError);
	}

	/// <function container="base/SMTypeChecker" name="CheckArray" access="public" returns="boolean">
	/// 	<description> Check PHP array to make sure all contained elements are of the same type </description>
	/// 	<param name="methodName" type="string"> See CheckObject(..) function for description </param>
	/// 	<param name="arrayName" type="string"> See CheckObject(..) function for description (objectName) </param>
	/// 	<param name="array" type="array"> See CheckObject(..) function for description (object) </param>
	/// 	<param name="typeExpected" type="SMTypeCheckType | string"> See CheckObject(..) function for description </param>
	/// 	<param name="throwExceptionOnTypeError" type="boolean" default="true"> See CheckObject(..) function for description </param>
	/// </function>
	public function CheckArray($methodName, $arrayName, $array, $typeExpected, $throwExceptionOnTypeError = true)
	{
		if (is_string($methodName) === false || is_string($arrayName) === false || is_array($array) === false || is_string($typeExpected) === false || is_bool($throwExceptionOnTypeError) === false)
			throw new Exception("Invalid parameter(s) - SMTypeChecker->CheckArray(string, string, string, array, SMTypeCheckType::Type|string[, bool]) expected");

		$keys = array_keys($array);
		$result = false;

		foreach ($keys as $key)
		{
			$result = $this->CheckObject($methodName, $arrayName . "[" . $key . "]", $array[$key], $typeExpected, $throwExceptionOnTypeError);

			if ($result === false)
				return false;
		}

		return true;
	}

	/// <function container="base/SMTypeChecker" name="CheckMultiArray" access="public" returns="boolean">
	/// 	<description> Check multi dimentional PHP array to make sure all contained elements are of the same type </description>
	/// 	<param name="methodName" type="string"> See CheckObject(..) function for description </param>
	/// 	<param name="arrayName" type="string"> See CheckObject(..) function for description (objectName) </param>
	/// 	<param name="array" type="array"> See CheckObject(..) function for description (object) </param>
	/// 	<param name="typeExpected" type="SMTypeCheckType | string"> See CheckObject(..) function for description </param>
	/// 	<param name="throwExceptionOnTypeError" type="boolean" default="true"> See CheckObject(..) function for description </param>
	/// </function>
	public function CheckMultiArray($methodName, $arrayName, $array, $typeExpected, $throwExceptionOnTypeError = true)
	{
		if (is_string($methodName) === false || is_string($arrayName) === false || is_array($array) === false || is_string($typeExpected) === false || is_bool($throwExceptionOnTypeError) === false)
			throw new Exception("Invalid parameter(s) - SMTypeChecker->CheckMultiArray(string, string, string, array, SMTypeCheckType::Type|string[, bool]) expected");

		$keys = array_keys($array);
		$subKeys = null;
		$result = false;

		foreach ($keys as $key)
		{
			if (is_array($array[$key]) === false)
				throw new Exception("Array '" . $arrayName . "' within method '" . $methodName . "' was expected to be a multi dimentional array of type '" . $typeExpected . "' but the array is not pure");

			$subKeys = array_keys($array[$key]);

			foreach ($subKeys as $subKey)
			{
				$result = $this->CheckObject($methodName, $arrayName . "[" . $key . "][" . $subKey . "]", $array[$key][$subKey], $typeExpected, $throwExceptionOnTypeError);

				if ($result === false)
					return false;
			}
		}

		return true;
	}

	/// <function container="base/SMTypeChecker" name="ValidateObjectArray" access="public" returns="boolean">
	/// 	<description> Check associative object array (e.g. from json_decode($json, true)) to make sure it is compatible with schema </description>
	/// 	<param name="data" type="array">
	/// 		Associative object array, e.g. representing JSON object. Example:
	///
	/// 		$jsonData = {
	/// 			&#160;&#160;&#160;&#160; &quot;Name&quot; : &quot;James Thompson&quot;,
	/// 			&#160;&#160;&#160;&#160; &quot;BirthYear&quot; : 1983,
	/// 			&#160;&#160;&#160;&#160; &quot;PhoneNumbers&quot; : [5559271, 5558311],
	/// 			&#160;&#160;&#160;&#160; Cars: [
	/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; { &quot;CarTitle&quot; : &quot;Hyundai Getz 1.4&quot;, &quot;RegistrationId&quot; : &quot;HT 388 27&quot; },
	/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; { &quot;CarTitle&quot; : &quot;Toyota Aygo 1.1&quot;, &quot;RegistrationId&quot; : &quot;UM 231 90&quot; }
	/// 			&#160;&#160;&#160;&#160; ]
	/// 		}
	///
	/// 		$phpData = array( // json_decode($jsonData, true)
	/// 			&#160;&#160;&#160;&#160; &quot;Name&quot; => &quot;James Thompson&quot;,
	/// 			&#160;&#160;&#160;&#160; &quot;BirthYear&quot; => 1983,
	/// 			&#160;&#160;&#160;&#160; &quot;PhoneNumbers&quot; => array(5559271, 5558311),
	/// 			&#160;&#160;&#160;&#160; &quot;Cars&quot; = array(
	/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; array(&quot;CarTitle&quot; => &quot;Hyundai Getz 1.4&quot;, &quot;RegistrationId&quot; => &quot;HT 388 27&quot;),
	/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; array(&quot;CarTitle&quot; => &quot;Toyota Aygo 1.1&quot;, &quot;RegistrationId&quot; => &quot;UM 231 90&quot;)
	/// 			&#160;&#160;&#160;&#160; )
	/// 		);
	/// 	</param>
	/// 	<param name="schema" type="array">
	/// 		Schema describing expected object (the contract). Example:
	///
	/// 		$schema = array(
	/// 			&#160;&#160;&#160;&#160; &quot;Name&quot; = array(&quot;DataType&quot; => &quot;string&quot;),
	/// 			&#160;&#160;&#160;&#160; &quot;BirthYear&quot; = array(&quot;DataType&quot; => &quot;number&quot;, &quot;AllowNull&quot; => true, &quot;Optional&quot; => true),
	/// 			&#160;&#160;&#160;&#160; &quot;PhoneNumbers&quot; = array(&quot;DataType&quot; => &quot;number[]&quot;, &quot;AllowNullValues&quot; => true),
	/// 			&#160;&#160;&#160;&#160; &quot;Cars&quot; = array(&quot;DataType&quot; => &quot;object[]&quot;, &quot;Schema&quot; => array(
	/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; &quot;CarTitle&quot;		=> array(&quot;DataType&quot; => &quot;string&quot;),
	/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; &quot;RegistrationId&quot;	=> array(&quot;DataType&quot; => &quot;string&quot;)
	/// 			&#160;&#160;&#160;&#160; ))
	/// 		);
	///
	/// 		The following data types are supported:
	/// 		string, number (integer, float), boolean, array, object (represented by associative object array), null, and any.
	/// 		Obviously &quot;any&quot; is not a real type, but it can be used to allow any kind of data.
	/// 		In addition all the mentioned types can be used as arrays and multi dimentional arrays like so:
	/// 		string[], number[], boolean[], array[], object[], any[], and
	/// 		string[][], number[][], boolean[][], array[][], object[][], any[][]
	/// 		Each property must be described with a DataType set, and optionally one of the following options:
	/// 		AllowNull (boolean), AllowNullValues (boolean, applies to arrays), Schema (array, describes an object), and Optional (boolean).
	/// 	</param>
	/// 	<param name="allowUndefinedData" type="boolean" default="false"> Flag indicating whether data not described in schema is allowed (default is false) </param>
	/// 	<param name="throwExceptionOnTypeError" type="boolean" default="true">
	/// 		Set False to suppress exception being thrown if an object is not of the expected
	/// 		type. In this case True or False is returned to indicate whether object is of
	/// 		the expected type.
	/// 	</param>
	/// </function>
	public function ValidateObjectArray($data, $schema, $allowUndefinedData = false, $throwExceptionOnTypeError = true)
	{
		try
		{
			$this->validateSchema($schema);
			$this->validateObjectDataArray($data, $schema, $allowUndefinedData);
		}
		catch (Exception $ex)
		{
			if ($throwExceptionOnTypeError === false)
				return false;

			throw $ex;
		}

		return true;
	}

	// Copy of JSONValidator.php.zip from https://github.com/Jemt/SitemagicCMS/issues/38 with minor changes
	private function validateObjectDataArray($data, $schema, $allowUndefinedData = false, $propPath = "")
	{
		// $data is the associative data array representing a JSON object.
		// $schema is an associative array describing the expected structure and format of $data.

		if (is_array($data) === false || is_array($schema) === false || is_bool($allowUndefinedData) === false || is_string($propPath) === false)
			throw new Exception("Invalid parameter(s) - SMTypeChecker->validateObjectDataArray(array, array[, boolean[, string]]) expected");

		if (count($schema) === 0)
			return; // An empty schema indicates that validation is not wanted

		$types = array(
			// JS type	=> PHP type(s) as returned by gettype($var)

			// Valid types
			"number"	=> array("integer", "double"),
			"boolean"	=> array("boolean"),
			"string"	=> array("string"),
			//"array"		=> array("array"),

			// Valid types in generic collections
			"array"		=> array("integer", "double", "boolean", "string", "array", "NULL"),
			"any"		=> array("integer", "double", "boolean", "string", "array", "NULL")
		);

		// Validate properties against schema

		$newPropPath = null;
		$type = null;
		$isSingleArray = false;
		$isMultiArray = false;

		foreach ($data as $prop => $val)
		{
			$newPropPath = (($propPath !== "") ? $propPath . " > " : "") . $prop;

			if (isset($schema[$prop]) === false)
			{
				if ($allowUndefinedData === true)
					continue;

				throw new Exception("Property '" . $newPropPath . "' defined in data is not described in schema");
			}

			$type = $schema[$prop]["DataType"];

			if ($type === "any")
				continue;

			if ($val === null && (isset($schema[$prop]["AllowNull"]) === false || $schema[$prop]["AllowNull"] === false))
				throw new Exception("Property '" . $newPropPath . "' is null which is not allowed");

			if ($val === null)
				continue;

			$isSingleArray = false;
			$isMultiArray = false;

			if (strpos($type, "[][]") > -1) // E.g. string[][] or object[][]
			{
				$type = substr($type, 0, -4);
				$isMultiArray = true;
			}
			else if (strpos($type, "[]") > -1) // E.g. string[] or object[]
			{
				$type = substr($type, 0, -2);
				$isSingleArray = true;
			}
			else if ($type === "array")
			{
				$isSingleArray = true;
			}

			if ($isSingleArray === false && $isMultiArray === false)
			{
				if ($type === "object")
				{
					$this->validateObjectDataArray($val, $schema[$prop]["Schema"], $allowUndefinedData, (($propPath !== "") ? $propPath . " > " : "") . $prop);
				}
				else if (in_array(gettype($val), $types[$type]) === false)
				{
					throw new Exception("Property '" . $newPropPath . "' was not of expected type '" . $schema[$prop]["DataType"] . "'");
				}
			}
			else if ($isMultiArray === true)
			{
				if (is_array($val) === false)
					throw new Exception("Property '" . $newPropPath . "' was not of expected type '" . $schema[$prop]["DataType"] . "'");

				foreach ($val as $arr)
				{
					if (is_array($arr) === false)
						throw new Exception("Property '" . $newPropPath . "' was not of expected type '" . $schema[$prop]["DataType"] . "'");

					foreach ($arr as $o)
					{
						if ($type === "object")
						{
							if (is_array($o) === false) // Prevent problems with odd collections like this: array(   array("Title" => "Hello", Value => 384),   array("Title" => "Morning", Value => 922),   "a string here is invalid"   )
								throw new Exception("Property '" . $newPropPath . "' is not compatible with multi dimentional array '" . $schema[$prop]["DataType"] . "'");

							$this->validateObjectDataArray($o, $schema[$prop]["Schema"], $allowUndefinedData, (($propPath !== "") ? $propPath . " > " : "") . $prop);
						}
						else if ($o === null && $type !== "any" && $type !== "array")
						{
							if (isset($schema[$prop]["AllowNullValues"]) === false || $schema[$prop]["AllowNullValues"] === false)
								throw new Exception("Property '" . $newPropPath . "' contained null which is not allowed");
						}
						else if (in_array(gettype($o), $types[$type]) === false)
						{
							throw new Exception("Property '" . $newPropPath . "' was not of expected type '" . $schema[$prop]["DataType"] . "'");
						}
					}
				}
			}
			else if ($isSingleArray === true)
			{
				if (is_array($val) === false)
					throw new Exception("Property '" . $newPropPath . "' was not of expected type '" . $schema[$prop]["DataType"] . "'");

				foreach ($val as $o)
				{
					if ($type === "object")
					{
						if (is_array($o) === false) // Prevent problems with odd collections like this: array(   array("Title" => "Hello", Value => 384),   array("Title" => "Morning", Value => 922),   "a string here is invalid"   )
							throw new Exception("Property '" . $newPropPath . "' is not compatible with array '" . $schema[$prop]["DataType"] . "'");

						$this->validateObjectDataArray($o, $schema[$prop]["Schema"], $allowUndefinedData, (($propPath !== "") ? $propPath . " > " : "") . $prop);
					}
					else if ($o === null && $type !== "any" && $type !== "array")
					{
						if (isset($schema[$prop]["AllowNullValues"]) === false || $schema[$prop]["AllowNullValues"] === false)
							throw new Exception("Property '" . $newPropPath . "' contained null which is not allowed");
					}
					else if (in_array(gettype($o), $types[$type]) === false)
					{
						throw new Exception("Property '" . $newPropPath . "' was not of expected type '" . $schema[$prop]["DataType"] . "'");
					}
				}
			}
		}

		// Make sure required (non-optional) data is present

		foreach ($schema as $prop => $def)
		{
			if ($schema[$prop]["DataType"] === "any")
				continue; // Allow anything for 'any' - properties can even be undefined (not present in data)

			$newPropPath = (($propPath !== "") ? $propPath . " >" : "") . " " . $prop;

			// Check further down will not work if value is null as isset(..) returns false for null values.
			// And we cannot check ($data[$prop] === null) since this will emit a warning if the property is
			// not defined. Therefore we have to make sure null values are skipped first.
			if (array_key_exists($prop, $data) === true)
			{
				if ($data[$prop] === null && (isset($schema[$prop]["AllowNull"]) === false || $schema[$prop]["AllowNull"] === false))
					throw new Exception("Property '" . $newPropPath . "' is not allowed a value of null");

				continue;
			}

			if (isset($data[$prop]) === false && (isset($schema[$prop]["Optional"]) === false || $schema[$prop]["Optional"] === false))
			{
				throw new Exception("Property '" . $newPropPath . "' is required but not defined in data");
			}
		}
	}

	// Copy of JSONValidator.php.zip from https://github.com/Jemt/SitemagicCMS/issues/38 with minor changes
	private function validateSchema($schemaToValidate)
	{
		if (is_array($schemaToValidate) === false)
			throw new Exception("Invalid parameter(s) - SMTypeChecker->validateSchema(array) expected");

		// JSON can represent four primitive types (strings, numbers, booleans, and null)
		// and two structured types (objects and arrays) - http://www.faqs.org/rfcs/rfc7159.html
		$simpleTypes = array("string", "number", "boolean", "object", "array", "any"); // We consider 'null' a value rather than a type, and 'any' is added to allow for mixed types
		$type = null;
		$schema = null;

		foreach ($schemaToValidate as $prop => $val)
		{
			$type = ((isset($val["DataType"]) === true) ? $val["DataType"] : null);
			$schema = ((isset($val["Schema"]) === true) ? $val["Schema"] : null);

			if (is_array($val) === false || $type === null)
				throw new Exception("Property '" . $prop . "' is incorrectly described - it must be an array defining at least DataType");

			if (isset($val["Optional"]) === true && is_bool($val["Optional"]) === false)
				throw new Exception("Property '" . $prop . "' defines Optional with an invalid value - boolean expected");

			if (isset($val["AllowNull"]) === true && is_bool($val["AllowNull"]) === false)
				throw new Exception("Property '" . $prop . "' defines AllowNull with an invalid value - boolean expected");

			if (strpos($type, "[][]") > -1) // E.g. string[][] or object[][]
			{
				$type = substr($type, 0, -4);
			}
			else if (strpos($type, "[]") > -1) // E.g. string[] or object[]
			{
				$type = substr($type, 0, -2);
			}

			if (in_array($type, $simpleTypes, true) === false)
				throw new Exception("Property '" . $prop . "' has been defined with an unsupported DataType '" . $val["DataType"] . "'");

			if ($schema !== null && is_array($schema) === false)
				throw new Exception("Property '" . $prop . "' defines Schema with an invalid value - array expected");

			if ($type === "object" && $schema === null)
				throw new Exception("Property '" . $prop . "' has been defined without a schema");

			if ($schema !== null && $type !== "object")
				throw new Exception("Property '" . $prop . "' does not define a data type with support for validation against a schema");

			if ($schema !== null)
				$this->validateSchema($schema);
		}
	}

	private function checkObj($methodName, $objectName, $object, $typeExpected, $allowNull = false, $throwExceptionOnTypeError = true)
	{
		// $object is the dynamic type that is supposed to be validated later against $typeExpected, which contains the expected type
		if (is_string($methodName) === false || is_string($objectName) === false || is_string($typeExpected) === false || is_bool($allowNull) === false || is_bool($throwExceptionOnTypeError) === false)
			throw new Exception("Invalid parameter(s) - SMTypeChecker->checkObj(string, string, string, object, SMTypeCheckType::Type|string[, bool[, bool]]) expected");

		// Null check

		if ($allowNull === true && $object === null)
			return true;

		// Determine type check method (PHP native type check (on e.g. string or bool) versus class check)

		$phpTypeCheck = false; // False = check for object type (class instance)
		if (property_exists("SMTypeCheckType", $typeExpected) === true)
			$phpTypeCheck = true; // True = check for PHP type

		// Type check

		$expectedType = false;

		if ($phpTypeCheck === true)
			$expectedType = $this->checkType($typeExpected, $object);
		else
			$expectedType = ($object instanceof $typeExpected);

		// Handle error in case object was not of expected type

		if ($expectedType === false)
		{
			if ($throwExceptionOnTypeError === false)
			{
				return false;
			}
			else
			{
				$typeFound = (($phpTypeCheck === true) ? gettype($object) : get_class($object));
				throw new Exception("Object '" . $objectName . "' within method '" . $methodName . "' was expected to be of type '" . $typeExpected . "' but '" . $typeFound . "' was found");
			}
		}

		return true;
	}

	private function checkType($smTypeCheckType, $dynamicArgument)
	{
		// $dynamicArgument validated further down
		if (is_string($smTypeCheckType) === false)
			throw new Exception("Invalid parameter(s) - SMTypeChecker->checkType(string, object) expected");

		if ($smTypeCheckType === SMTypeCheckType::$String && is_string($dynamicArgument) === false)
			return false;
		else if ($smTypeCheckType === SMTypeCheckType::$Integer && is_int($dynamicArgument) === false)
			return false;
		else if ($smTypeCheckType === SMTypeCheckType::$Float && is_float($dynamicArgument) === false)
			return false;
		else if ($smTypeCheckType === SMTypeCheckType::$Boolean && is_bool($dynamicArgument) === false)
			return false;
		else if ($smTypeCheckType === SMTypeCheckType::$Array && is_array($dynamicArgument) === false)
			return false;
		else if ($smTypeCheckType === SMTypeCheckType::$Object && is_object($dynamicArgument) === false)
			return false;
		else if ($smTypeCheckType === SMTypeCheckType::$Null && is_null($dynamicArgument) === false)
			return false;
		else if ($smTypeCheckType === SMTypeCheckType::$Resource && is_resource($dynamicArgument) === false)
			return false;

		return true;
	}
}

/// <container name="base/SMTypeCheck">
/// 	Class contains functionality used to ensure that correct data types are being
/// 	passed through the system. These checks are performed throughout the entire application
/// 	in the beginning of most functions. Usually an exception is thrown in case of an
/// 	incorrect data type being passed.
///
/// 	function createText($name, $age)
/// 	{
/// 		SMTypeCheck::CheckObject(__METHOD__, &quot;name&quot;, $name, SMTypeCheckType::$String);
/// 		SMTypeCheck::CheckObject(__METHOD__, &quot;age&quot;, $age, SMTypeCheckType::$Integer);
/// 		return $name . &quot; is &quot; . $age . &quot; years old&quot;;
/// 	}
///
/// 	$textA = createText(&quot;Casper&quot;, 28);
/// 	$textB = createText(&quot;Casper&quot;, &quot;twenty eight&quot;);
///
/// 	Variable $textA will be properly constructed.
/// 	Variable $textB will not - an exception will be thrown with a stack trace
/// 	explaining exactly what function parameter was found to be of an unexpected type.
///
/// 	SMTypeCheck basically does what SMTypeChecker does, but all functions are static,
/// 	allowing for type checking without creating an instance of SMTypeChecker.
/// 	IMPORTANT difference: SMTypeCheck may skip checks if globally disabled.
/// 	Type checking can be disabled or enabled using SMTypeCheck::SetEnabled(boolea).
/// 	By default SMTypeCheck is enabled only when the application runs in Debug Mode.
///
/// 	Available functions:
/// 	 - SMTypeCheck::CheckObject(..)
/// 	 - SMTypeCheck::CheckObjectAllowNull(..)
/// 	 - SMTypeCheck::CheckArray(..)
/// 	 - SMTypeCheck::CheckMultiArray(..)
/// 	 - SMTypeCheck::ValidateObjectArray(..) ***
///
/// 	*** SMTypeCheck::ValidateObjectArray(..) is the only function that performs
/// 	checking, even when globally disabled in the application. That's because this function
/// 	may be used to validate JSON data passed from the client which cannot be trusted.
///
/// 	See SMTypeChecker for additional details about functions and arguments.
/// </container>
class SMTypeCheck
{
	private static $check = true;
	private static $checker = null;

	/// <function container="base/SMTypeCheck" name="SetEnabled" access="public" static="true">
	/// 	<description> Enable or disable type checking. Disabling type checking will cause check functions to always return True. </description>
	/// 	<param name="value" type="boolean"> True to enable type checking (default), False to disable </param>
	/// </function>
	public static function SetEnabled($value)
	{
		if (is_bool($value) === false)
			throw new Exception("Invalid parameter(s) - SMTypeCheck::SetEnabled(bool) expected");

		self::$check = $value;
	}

	/// <function container="base/SMTypeCheck" name="GetEnabled" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if type checking is enabled, otherwise False </description>
	/// </function>
	public static function GetEnabled()
	{
		return self::$check;
	}

	public static function CheckObject($methodName, $objectName, $object, $typeExpected, $throwExceptionOnTypeError = true)
	{
		if (self::$check === false)
			return true;

		if (self::$checker === null)
			self::$checker = new SMTypeChecker();

		return self::$checker->CheckObject($methodName, $objectName, $object, $typeExpected, false, $throwExceptionOnTypeError);
	}

	public static function CheckObjectAllowNull($methodName, $objectName, $object, $typeExpected, $throwExceptionOnTypeError = true)
	{
		if (self::$check === false)
			return true;

		if (self::$checker === null)
			self::$checker = new SMTypeChecker();

		return self::$checker->CheckObjectAllowNull($methodName, $objectName, $object, $typeExpected, $throwExceptionOnTypeError);
	}

	public static function CheckArray($methodName, $arrayName, $array, $typeExpected, $throwExceptionOnTypeError = true)
	{
		if (self::$check === false)
			return true;

		if (self::$checker === null)
			self::$checker = new SMTypeChecker();

		return self::$checker->CheckArray($methodName, $arrayName, $array, $typeExpected, $throwExceptionOnTypeError);
	}

	public static function CheckMultiArray($methodName, $arrayName, $array, $typeExpected, $throwExceptionOnTypeError = true)
	{
		if (self::$check === false)
			return true;

		if (self::$checker === null)
			self::$checker = new SMTypeChecker();

		return self::$checker->CheckMultiArray($methodName, $arrayName, $array, $typeExpected, $throwExceptionOnTypeError);
	}

	public static function ValidateObjectArray($data, $schema, $allowUndefinedData = false, $throwExceptionOnTypeError = true)
	{
		// Notice that this function ALWAYS checks data, even when self::$check is false.
		// That's because this function is used to validate JSON data passed from the client which cannot be trusted.

		//if (self::$check === false)
			//return true;

		if (self::$checker === null)
			self::$checker = new SMTypeChecker();

		return self::$checker->ValidateObjectArray($data, $schema, $allowUndefinedData, $throwExceptionOnTypeError);
	}
}

?>
