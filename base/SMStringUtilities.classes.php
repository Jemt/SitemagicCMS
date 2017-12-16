<?php

/// <container name="base/SMStringUtilities">
/// 	Class contains useful and common functionality used to manipulate and parse strings.
/// </container>
class SMStringUtilities
{
	/// <function container="base/SMStringUtilities" name="StartsWith" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if specified string starts with search value, otherwise False </description>
	/// 	<param name="str" type="string"> String to examine </param>
	/// 	<param name="search" type="string"> Expected value in beginning of string </param>
	/// </function>
	public static function StartsWith($str, $search)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);

		if ($str === "" || $search === "")
			return false;

		return (strpos($str, $search) === 0);
	}

	/// <function container="base/SMStringUtilities" name="EndsWith" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if specified string ends with search value, otherwise False </description>
	/// 	<param name="str" type="string"> String to examine </param>
	/// 	<param name="search" type="string"> Expected value in end of string </param>
	/// </function>
	public static function EndsWith($str, $search)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);

		if ($str === "" || $search === "")
			return false;

		$end = substr($str, strlen($str) - strlen($search));
		return ($end === $search);
	}

	/// <function container="base/SMStringUtilities" name="SplitCaseInsensitive" access="public" static="true" returns="string[]">
	/// 	<description> Splits string with a split sequence that might occure with different casings </description>
	/// 	<param name="str" type="string"> String to split </param>
	/// 	<param name="splitter" type="string"> Split sequence </param>
	/// </function>
	public static function SplitCaseInsensitive($str, $splitter)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "splitter", $splitter, SMTypeCheckType::$String);

		if ($str === "" || $splitter === "")
			return array($str);

		$elements = explode(strtolower($splitter), strtolower($str));
		$newElements = array();

		$lengthStr = strlen($str);
		$lengthSplitter = strlen($splitter);
		$offset = 0;
		$length = 0;

		foreach ($elements as $element)
		{
			$length = strlen($element);
			$newElements[] = substr($str, $offset, $length);
			$offset = $offset + $length + $lengthSplitter;

			if ($offset === $lengthStr)
			{
				$newElements[] = "";
				break;
			}
		}

		return $newElements;
	}

	/// <function container="base/SMStringUtilities" name="SplitBySemicolon" access="public" static="true" returns="string[]">
	/// 	<description> Splits string by semicolon while preserving HEX/HTML entities </description>
	/// 	<param name="str" type="string"> String to split </param>
	/// </function>
	public static function SplitBySemicolon($str)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);

		if (strpos($str, ";") === false)
			return array($str);

		$chars = str_split($str);
		$offset = 0;
		$amp = false;
		$res = array();

		for ($i = 0 ; $i < count($chars) ; $i++)
		{
			// Set flag preventing split on semicolon when it comes after an ampersand (to prevent splitting e.g. &euro;)
			if ($chars[$i] === "&")
			{
				$amp = true;
				continue;
			}

			// Split if semicolon does not come after an ampersand (e.g. &euro;)
			if ($chars[$i] === ";" && $amp === false)
			{
				$res[] = substr($str, $offset, $i - $offset);
				$offset = $i+1;
				$amp = false;
				continue;
			}

			// Set ampersand flag False when either a whitespace or semicolon is met (in this case a semicolon comes after an ampersand, so it is terminating an HTML/HEX entity)
			if ($chars[$i] === " " || $chars[$i] === "\t" || $chars[$i] === "\r" || $chars[$i] === "\n" ||  $chars[$i] === ";")
			{
				$amp = false;
				continue;
			}
		}

		if ($offset < count($chars)) // Get remaining characters after last split
		{
			$res[] = substr($str, $offset);
		}
		else if ($offset === count($chars)) // string ends with a semicolon
		{
			$res[] = "";
		}

		return $res;
	}

	/// <function container="base/SMStringUtilities" name="Replace" access="public" static="true" returns="string">
	/// 	<description> Replace search value within a string. Supports case insensitive search. A limit may be set to avoid replacement of all occurences. </description>
	/// 	<param name="str" type="string"> String containing values to replace </param>
	/// 	<param name="search" type="string"> Value to search for to replace </param>
	/// 	<param name="replace" type="string"> Replacement value </param>
	/// 	<param name="caseSensitive" type="boolean" default="true"> Perform case insensitive search </param>
	/// 	<param name="limit" type="integer" default="-1"> Optionally limit number of replacements to specified value </param>
	/// </function>
	public static function Replace($str, $search, $replace, $caseSensitive = true, $limit = -1)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "search", $search, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "replace", $replace, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "caseSensitive", $caseSensitive, SMTypeCheckType::$Boolean);
		SMTypeCheck::CheckObject(__METHOD__, "limit", $limit, SMTypeCheckType::$Integer);

		if ($str === "" || $search === "" || $limit === 0)
			return $str;

		$index = strpos((($caseSensitive === true) ? $str : strtolower($str)), (($caseSensitive === true) ? $search : strtolower($search)));
		$tmp = "";

		while ($index !== false)
		{
			$tmp = substr($str, 0, $index);
			$str = $tmp . $replace . substr($str, $index + strlen($search));

			$limit--;
			if ($limit === 0)
				break;

			$index = strpos((($caseSensitive === true) ? $str : strtolower($str)), (($caseSensitive === true) ? $search : strtolower($search)), $index + strlen($replace));
		}

		return $str;
	}

	/// <function container="base/SMStringUtilities" name="Validate" access="public" static="true" returns="boolean">
	/// 	<description> Validate string against simple validation rule - returns True if valid, otherwise False </description>
	/// 	<param name="str" type="string"> String value to validate </param>
	/// 	<param name="restriction" type="SMValueRestriction">
	/// 		Value restriction - rule to validate against.
	/// 		See base/SMValueRestriction for more information.
	/// 	</param>
	/// 	<param name="exceptions" type="string[]" default="string[0]">
	/// 		Array of strings/characters allowed dispite of chosen value restriction.
	/// 		This parameter should not be used with the following value restrictions:
	/// 		- SMValueRestriction::$Url
	/// 		- SMValueRestriction::$UrlEncoded
	/// 		- SMValueRestriction::$Guid
	/// 		- SMValueRestriction::$SafePath
	/// 	</param>
	/// </function>
	public static function Validate($str, $restriction, $exceptions = array())
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "restriction", $restriction, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "exceptions", $exceptions, SMTypeCheckType::$String);

		if (property_exists("SMValueRestriction", $restriction) === false)
			throw new Exception("Specified value restriction does not exist - use SMValueRestriction::Restriction");

		return self::validateString($str, $restriction, $exceptions);
	}

	private static function validateString($str, $restriction, $exceptions = array())
	{
		foreach ($exceptions as $exception) // Allow elements in $exceptions
			$str = str_replace($exception, "", $str);

		if ($restriction === SMValueRestriction::$LocaleAlpha)
		{
			return ctype_alpha($str); // Validated against current locale (could accept e.g. Danish characters). Empty not allowed.
		}
		else if ($restriction === SMValueRestriction::$LocaleAlphaNumeric)
		{
			return ctype_alnum($str); // Validated against current locale (could accept e.g. Danish characters). Empty not allowed.
		}
		else if ($restriction === SMValueRestriction::$Alpha)
		{
			return (preg_match("/^[A-Z]+$/i", $str) === 1); // A-Z (empty not allowed)
		}
		else if ($restriction === SMValueRestriction::$Numeric)
		{
			return (preg_match("/^[0-9]+$/", $str) === 1); // 0-9 (empty not allowed)
		}
		else if ($restriction === SMValueRestriction::$NumericDecimal)
		{
			return (preg_match("/^[0-9]+(.[0-9]+)?$/", $str) === 1); // 0-9 and 0-9.0-9 (empty not allowed)
		}
		else if ($restriction === SMValueRestriction::$AlphaNumeric)
		{
			return (preg_match("/^[A-Z0-9]+$/i", $str) === 1); // A-Z 0-9 (empty not allowed)
		}
		else if ($restriction === SMValueRestriction::$Url)
		{
			return (preg_match("|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i", $str) === 1); // Empty not allowed
		}
		else if ($restriction === SMValueRestriction::$UrlEncoded)
		{
			return self::Validate(urldecode($str), SMValueRestriction::$Url, $exceptions); // Empty not allowed
		}
		else if ($restriction === SMValueRestriction::$Guid)
		{
			if (strpos($str, "-") !== false)
				return (preg_match("/^\{?[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}\}?$/i", $str) === 1); // Case insensitive (/i). Empty not allowed.
			else
				return (preg_match("/^[A-F0-9]{32}$/i", $str) === 1); // Case insensitive (/i). Empty not allowed.
		}
		else if ($restriction === SMValueRestriction::$SafePath)
		{
			//return ($str === "" || (strpos($str, "..") === false && preg_match("/^[.A-Z0-9-_]+[\/.A-Z0-9-_]*$/i", $str) === 1));
			//return (strpos($str, "..") === false && preg_match("/^[.A-Z0-9-_]+[\/.A-Z0-9-_]*$/i", $str) === 1); // RegEx: First letter must start with one of ".A-Z0-9-_" (no leading slash - quotes excluded) optionally followed by any number of "/.A-Z0-9-_" (slash accepted - quotes excluded) - case insensitive (/i) - empty not allowed

			// Cannot start with a slash or contain "..".
			// Allow standard letters and digits, dot, space, apostrophe, underscore, and dash, as well as special characters from the extended ASCII table, given by HEX range: http://www.ascii-code.com
			$expr = "a-zA-Z0-9\xC0-\xFF. '_-"; // NOTICE: RegEx should be identical to the one used in ValueRestriction::$Filename
			return (strpos($str, "..") === false && preg_match("/^[" . $expr . "]+[\/" . $expr . "]*$/", $str) === 1);
		}
		else if ($restriction === SMValueRestriction::$Filename)
		{
			// Allow standard letters and digits, dot, space, apostrophe, underscore, and dash, as well as special characters from the extended ASCII table, given by HEX range: http://www.ascii-code.com
			return (preg_match("/^[a-zA-Z0-9\xC0-\xFF. '_-]+$/", $str) === 1); // NOTICE: RegEx should be identical to the one used in ValueRestriction::$SafePath
		}
		else if ($restriction === SMValueRestriction::$NonEmpty)
		{
			return ($str !== "");
		}
		else if ($restriction === SMValueRestriction::$EmailAddress)
		{
			return (preg_match("/^[a-z0-9!#$%&'*+-\/=?^_`{|}~]+@[a-z0-9_.-]+$/i", $str) === 1); // https://regex101.com/r/zU5vG7/2
		}

		return true; // SMValueRestriction::$None
	}

	/// <function container="base/SMStringUtilities" name="RemoveInvalidCharacters" access="public" static="true" returns="string">
	/// 	<description> Remove characters that are invalid according to given value restriction </description>
	/// 	<param name="str" type="string"> String value from which invalid characters are removed </param>
	/// 	<param name="restriction" type="SMValueRestriction">
	/// 		Value restriction - rule to validate against.
	/// 		See base/SMValueRestriction for more information.
	/// 	</param>
	/// 	<param name="exceptions" type="string[]" default="string[0]">
	/// 		Array of characters allowed dispite of chosen value restriction.
	/// 		This parameter should not be used with the following value restrictions:
	/// 		- SMValueRestriction::$Url
	/// 		- SMValueRestriction::$UrlEncoded
	/// 		- SMValueRestriction::$Guid
	/// 		- SMValueRestriction::$SafePath
	/// 	</param>
	/// </function>
	public static function RemoveInvalidCharacters($str, $restriction, $exceptions = array())
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "restriction", $restriction, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "exceptions", $exceptions, SMTypeCheckType::$String);

		if (property_exists("SMValueRestriction", $restriction) === false)
			throw new Exception("Specified value restriction does not exist - use SMValueRestriction::Restriction");

		if (self::validateString($str, $restriction, $exceptions) === true)
			return $str;

		// NOTICE: This is pretty inefficient! TODO: copy all regular expressions from validateString(..),
		// invert them to match invalid characters instead, and remove them using preg_replace(..).

		$chars = str_split($str);
		$newStr = "";

		for ($i = 0 ; $i < count($chars) ; $i++)
		{
			if (in_array($chars[$i], $exceptions, true) === true)
				$newStr .= $chars[$i];
			else if (self::validateString($chars[$i], $restriction, $exceptions) === true)
				$newStr .= $chars[$i];
		}

		return $newStr;
	}

	/// <function container="base/SMStringUtilities" name="HtmlEntityEncode" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Returns string with reserved HTML characters (&amp; &quot; &lt; &gt;) encoded into HTML entities.
	/// 		All characters that have HTML entity equivalents are also translated into these.
	/// 	</description>
	/// 	<param name="str" type="string"> Value to encode </param>
	/// 	<param name="doubleEncode" type="boolean" default="false"> Set True to double encode existing entities, False not to </param>
	/// </function>
	public static function HtmlEntityEncode($str, $doubleEncode = false) // Double encoding breaks encoded unicode characters
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "doubleEncode", $doubleEncode, SMTypeCheckType::$Boolean);

		return htmlentities($str, ENT_COMPAT, "ISO-8859-1", $doubleEncode); // Notice: ENT_HTML401 undefined in PHP 5.2
	}

	/// <function container="base/SMStringUtilities" name="HtmlEntityDecode" access="public" static="true" returns="string">
	/// 	<description> Returns string with HTML entities decoded (usually encoded using the HtmlEntityEncode function) </description>
	/// 	<param name="str" type="string"> Value to decode </param>
	/// </function>
	public static function HtmlEntityDecode($str)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		return html_entity_decode($str, ENT_COMPAT, "ISO-8859-1"); // Notice: ENT_HTML401 undefined in PHP 5.2
	}

	/// <function container="base/SMStringUtilities" name="HtmlEncode" access="public" static="true" returns="string">
	/// 	<description> Returns string with reserved HTML characters (&amp; &quot; &lt; &gt;) encoded into HTML entities </description>
	/// 	<param name="str" type="string"> Value to encode </param>
	/// 	<param name="doubleEncode" type="boolean" default="false"> Set True to double encode existing entities, False not to </param>
	/// </function>
	public static function HtmlEncode($str, $doubleEncode = false) // Double encoding breaks encoded unicode characters
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "doubleEncode", $doubleEncode, SMTypeCheckType::$Boolean);

		return htmlspecialchars($str, ENT_COMPAT, "ISO-8859-1", $doubleEncode); // Notice: ENT_HTML401 undefined in PHP 5.2
	}

	/// <function container="base/SMStringUtilities" name="HtmlDecode" access="public" static="true" returns="string">
	/// 	<description> Returns string with reserved HTML characters decoded (usually encoded using the HtmlEncode function) </description>
	/// 	<param name="str" type="string"> Value to decode </param>
	/// </function>
	public static function HtmlDecode($str)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		return htmlspecialchars_decode($str, ENT_COMPAT); // Notice: ENT_HTML401 undefined in PHP 5.2 + No encoding argument according to documentation
	}

	/// <function container="base/SMStringUtilities" name="UnicodeEncode" access="public" static="true" returns="string">
	/// 	<description> Converts Unicode string to ISO-8859-1 string with unicode characters encoded into HEX entities </description>
	/// 	<param name="unicodeStr" type="string"> Value to encode </param>
	/// </function>
	public static function UnicodeEncode($unicodeStr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "unicodeStr", $unicodeStr, SMTypeCheckType::$String);

		// Notice: Do NOT pass anything but a Unicode string into this function.
		// Passing e.g. "זרו" as ISO-8859-1 will cause characters to be corrupted
		// since utf8_decode(..) only converts reliably from UTF-8 to ISO-8859-1.

		// Prevent invalid byte sequences which may lead to Invalid Encoding Attacks
		if (mb_check_encoding($unicodeStr, "UTF-8") === false)
			throw new Exception("Invalid byte sequence detected");

		// Encode characters that are not between code point 0 (Unicode HEX 0000) and code point 255 (Unicode HEX 0100).
		// Behaviour must be identical to SMStringUtilities.UnicodeEncode(..) client side!
		// Also passing data through utf8_decode(..) since remaining ISO-8859-1 characters are still encoded as UTF-8.
		return utf8_decode(preg_replace_callback("/[^\x{0000}-\x{0100}]/u", "self::unicodeEncodeReplaceCallback", $unicodeStr));
	}

	private static function unicodeEncodeReplaceCallback($matchArray)
	{
		$res = mb_convert_encoding($matchArray[0], "UTF-32BE", "UTF-8");
		return "&#" . hexdec(bin2hex($res)) . ";";
	}

	/// <function container="base/SMStringUtilities" name="UnicodeDecode" access="public" static="true" returns="string">
	/// 	<description> Returns string with unicode characters represented by HEX entities decoded into a true unicode string </description>
	/// 	<param name="str" type="string"> Value to decode </param>
	/// 	<param name="isUnicode" type="boolean" default="false"> Set True if string passed is already unicode encoded </param>
	/// </function>
	public static function UnicodeDecode($str, $isUnicode = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "isUnicode", $isUnicode, SMTypeCheckType::$Boolean);

		$str = ($isUnicode === false ? utf8_encode($str) : $str); // Make sure an ISO-8859-1 string is transformed into UTF-8 - however, calling utf8_encode(..) on a string already unicode encoded will cause characters such as "זרו" to be corrupted
		return preg_replace_callback("/&#\d+;/", "self::unicodeDecodeReplaceCallback", $str);
	}

	private static function unicodeDecodeReplaceCallback($matchArray)
	{
		return html_entity_decode($matchArray[0], ENT_COMPAT, "UTF-8"); // Notice: ENT_HTML401 undefined in PHP 5.2
	}

	/// <function container="base/SMStringUtilities" name="EscapeJson" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Escape string value to prevent e.g. line breaks or back slashes from breaking JSON output.
	/// 	</description>
	/// 	<param name="str" type="string"> Value to make safe for JSON output </param>
	/// </function>
	public static function EscapeJson($str)
	{
		SMTypeCheck::CheckObject(__METHOD__, "str", $str, SMTypeCheckType::$String);

		$str = str_replace("\\", "\\\\", $str);
		$str = str_replace("\r", "", $str);
		$str = str_replace("\n", "\\n", $str);
		$str = str_replace("\t", "\\t", $str);
		$str = str_replace("\"", "\\\"", $str);

		return $str;
	}

	public static function JsonEncode($str) // Backward compatibility
	{
		return self::EscapeJson($str);
	}
}

/// <container name="base/SMValueRestriction">
/// 	Enum defining various common validation rules
/// </container>
class SMValueRestriction
{
	/// <member container="base/SMValueRestriction" name="None" access="public" static="true" type="string" default="None">
	/// 	<description> Any value is allowed - no validation is performed </description>
	/// </member>
	public static $None = "None";

	/// <member container="base/SMValueRestriction" name="LocaleAlpha" access="public" static="true" type="string" default="LocaleAlpha">
	/// 	<description> Only allow characters defined in locale used by server </description>
	/// </member>
	public static $LocaleAlpha = "LocaleAlpha";

	/// <member container="base/SMValueRestriction" name="LocaleAlphaNumeric" access="public" static="true" type="string" default="LocaleAlphaNumeric">
	/// 	<description> Only allow numbers and characters defined in locale used by server </description>
	/// </member>
	public static $LocaleAlphaNumeric = "LocaleAlphaNumeric";

	/// <member container="base/SMValueRestriction" name="Alpha" access="public" static="true" type="string" default="Alpha">
	/// 	<description> Only allow characters A-Z (upper case and lower case) </description>
	/// </member>
	public static $Alpha = "Alpha";

	/// <member container="base/SMValueRestriction" name="Numeric" access="public" static="true" type="string" default="Numeric">
	/// 	<description> Only allow numbers 0-9 </description>
	/// </member>
	public static $Numeric = "Numeric";

	/// <member container="base/SMValueRestriction" name="NumericDecimal" access="public" static="true" type="string" default="NumericDecimal">
	/// 	<description> Only allow numbers 0-9 and optionally decimals 0-9.0-9 </description>
	/// </member>
	public static $NumericDecimal = "NumericDecimal";

	/// <member container="base/SMValueRestriction" name="AlphaNumeric" access="public" static="true" type="string" default="AlphaNumeric">
	/// 	<description> Only allow characters A-Z (upper case and lower case) and numbers 0-9 </description>
	/// </member>
	public static $AlphaNumeric = "AlphaNumeric";

	/// <member container="base/SMValueRestriction" name="Url" access="public" static="true" type="string" default="Url">
	/// 	<description> Value must be a valid URL </description>
	/// </member>
	public static $Url = "Url";

	/// <member container="base/SMValueRestriction" name="UrlEncoded" access="public" static="true" type="string" default="UrlEncoded">
	/// 	<description> Value must be a valid URL (encoded) </description>
	/// </member>
	public static $UrlEncoded = "UrlEncoded";

	/// <member container="base/SMValueRestriction" name="Guid" access="public" static="true" type="string" default="Guid">
	/// 	<description> Value must be a valid GUID (with or without dashes) </description>
	/// </member>
	public static $Guid = "Guid";

	/// <member container="base/SMValueRestriction" name="SafePath" access="public" static="true" type="string" default="SafePath">
	/// 	<description>
	/// 		Value must be a valid and safe file reference - e.g. path/to/my favorites/file.html
	/// 		Value can't start with a leading slash or contain ".."
	/// 	</description>
	/// </member>
	public static $SafePath = "SafePath";

	/// <member container="base/SMValueRestriction" name="Filename" access="public" static="true" type="string" default="Filename">
	/// 	<description> Value must be a valid file or folder name, hence not containing special characters </description>
	/// </member>
	public static $Filename = "Filename";

	/// <member container="base/SMValueRestriction" name="NonEmpty" access="public" static="true" type="string" default="NonEmpty">
	/// 	<description> Value must be a valid string with a length of 1 or more characters </description>
	/// </member>
	public static $NonEmpty = "NonEmpty";

	/// <member container="base/SMValueRestriction" name="EmailAddress" access="public" static="true" type="string" default="EmailAddress">
	/// 	<description> Value must be a valid e-mail address containing only ASCII compatible characters </description>
	/// </member>
	public static $EmailAddress = "EmailAddress";
}

?>
