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
/// </container>
class SMTypeCheck
{
	private static $check = true;

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

	/// <function container="base/SMTypeCheck" name="CheckObject" access="public" static="true" returns="boolean">
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
	public static function CheckObject($methodName, $objectName, $object, $typeExpected, $throwExceptionOnTypeError = true)
	{
		// Types are validated in checkObj(..) - not validated here due to performance
		return self::checkObj($methodName, $objectName, $object, $typeExpected, false, $throwExceptionOnTypeError);
	}

	/// <function container="base/SMTypeCheck" name="CheckObjectAllowNull" access="public" static="true" returns="boolean">
	/// 	<description> Same as CheckObject(..), but object being checked is allowed to be Null </description>
	/// 	<param name="methodName" type="string"> See CheckObject(..) function for description </param>
	/// 	<param name="objectName" type="string"> See CheckObject(..) function for description </param>
	/// 	<param name="object" type="object"> See CheckObject(..) function for description </param>
	/// 	<param name="typeExpected" type="SMTypeCheckType | string"> See CheckObject(..) function for description </param>
	/// 	<param name="throwExceptionOnTypeError" type="boolean" default="true"> See CheckObject(..) function for description </param>
	/// </function>
	public static function CheckObjectAllowNull($methodName, $objectName, $object, $typeExpected, $throwExceptionOnTypeError = true)
	{
		// Types are validated in checkObj(..) - not validated here due to performance
		return self::checkObj($methodName, $objectName, $object, $typeExpected, true, $throwExceptionOnTypeError);
	}

	/// <function container="base/SMTypeCheck" name="CheckArray" access="public" static="true" returns="boolean">
	/// 	<description> Check PHP array to make sure all contained elements are of the same type </description>
	/// 	<param name="methodName" type="string"> See CheckObject(..) function for description </param>
	/// 	<param name="arrayName" type="string"> See CheckObject(..) function for description (objectName) </param>
	/// 	<param name="array" type="array"> See CheckObject(..) function for description (object) </param>
	/// 	<param name="typeExpected" type="SMTypeCheckType | string"> See CheckObject(..) function for description </param>
	/// 	<param name="throwExceptionOnTypeError" type="boolean" default="true"> See CheckObject(..) function for description </param>
	/// </function>
	public static function CheckArray($methodName, $arrayName, $array, $typeExpected, $throwExceptionOnTypeError = true)
	{
		if (self::$check === false)
			return true;

		if (is_string($methodName) === false || is_string($arrayName) === false || is_array($array) === false || is_string($typeExpected) === false || is_bool($throwExceptionOnTypeError) === false)
			throw new Exception("Invalid parameter(s) - SMTypeCheck::CheckArray(string, string, string, array, SMTypeCheckType::Type|string[, bool]) expected");

		$keys = array_keys($array);
		$result = false;

		foreach ($keys as $key)
		{
			$result = self::CheckObject($methodName, $arrayName . "[" . $key . "]", $array[$key], $typeExpected, $throwExceptionOnTypeError);

			if ($result === false)
				return false;
		}

		return true;
	}

	private static function checkObj($methodName, $objectName, $object, $typeExpected, $allowNull = false, $throwExceptionOnTypeError = true)
	{
		if (self::$check === false)
			return true;

		// $object is the dynamic type that is supposed to be validated later against $typeExpected, which contains the expected type
		if (is_string($methodName) === false || is_string($objectName) === false || is_string($typeExpected) === false || is_bool($allowNull) === false || is_bool($throwExceptionOnTypeError) === false)
			throw new Exception("Invalid parameter(s) - SMTypeCheck::checkObj(string, string, string, object, SMTypeCheckType::Type|string[, bool[, bool]]) expected");

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
			$expectedType = self::checkType($typeExpected, $object);
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

	private static function checkType($smTypeCheckType, $dynamicArgument)
	{
		// $dynamicArgument validated further down
		if (is_string($smTypeCheckType) === false)
			throw new Exception("Invalid parameter(s) - SMTypeCheck::checkType(string, object) expected");

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

?>
