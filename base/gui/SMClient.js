// SMEnvironment

/// <container name="client/SMEnvironment">
/// 	Sitemagic environment information
/// </container>
SMEnvironment = function()
{
}

/// <function container="client/SMEnvironment" name="GetFilesDirectory" access="public" static="true" returns="string">
/// 	<description> Returns path to files directory </description>
/// </function>
SMEnvironment.GetFilesDirectory = function()
{
	return ((window.SMClientEnvironmentInfo !== undefined) ? SMClientEnvironmentInfo.Dirs.Files : "files");
}

/// <function container="client/SMEnvironment" name="GetDataDirectory" access="public" static="true" returns="string">
/// 	<description> Returns path to data directory </description>
/// </function>
SMEnvironment.GetDataDirectory = function()
{
	return ((window.SMClientEnvironmentInfo !== undefined) ? SMClientEnvironmentInfo.Dirs.Data : "data");
}

/// <function container="client/SMEnvironment" name="GetImagesDirectory" access="public" static="true" returns="string">
/// 	<description> Returns path to images directory </description>
/// </function>
SMEnvironment.GetImagesDirectory = function()
{
	return ((window.SMClientEnvironmentInfo !== undefined) ? SMClientEnvironmentInfo.Dirs.Images : "images");
}

/// <function container="client/SMEnvironment" name="GetTemplatesDirectory" access="public" static="true" returns="string">
/// 	<description> Returns path to templates directory </description>
/// </function>
SMEnvironment.GetTemplatesDirectory = function()
{
	return ((window.SMClientEnvironmentInfo !== undefined) ? SMClientEnvironmentInfo.Dirs.Templates : "templates");
}

/// <function container="client/SMEnvironment" name="GetExtensionsDirectory" access="public" static="true" returns="string">
/// 	<description> Returns path to extensions directory </description>
/// </function>
SMEnvironment.GetExtensionsDirectory = function()
{
	return ((window.SMClientEnvironmentInfo !== undefined) ? SMClientEnvironmentInfo.Dirs.Extensions : "extensions");
}

/// <function container="client/SMEnvironment" name="IsSubSite" access="public" static="true" returns="boolean">
/// 	<description> Returns flag indicating whether current site is a subsite </description>
/// </function>
SMEnvironment.IsSubSite = function()
{
	return ((window.SMClientEnvironmentInfo !== undefined) ? SMClientEnvironmentInfo.IsSubSite : false);
}

// SMLanguageHandler

/// <container name="client/SMLanguageHandler">
/// 	Language support for client API
/// </container>
SMLanguageHandler = function()
{
}

/// <function container="client/SMLanguageHandler" name="GetTranslation" access="public" static="true" returns="string">
/// 	<description> Returns translation for specified language string - returns empty string if not found </description>
/// 	<param name="translation" type="string"> Specify language key for desired translation </param>
/// </function>
SMLanguageHandler.GetTranslation = function(translation)
{
	return SMStringUtilities.UnicodeDecode(((SMClientLanguageStrings[translation] !== undefined) ? SMClientLanguageStrings[translation] : ""));
}

// SMStringUtilities

/// <container name="client/SMStringUtilities">
/// 	String manipulation functionality not provided natively by JavaScript
/// </container>
function SMStringUtilities()
{
}

/// <function container="client/SMStringUtilities" name="StartsWith" access="public" static="true" returns="boolean">
/// 	<description> Returns True if string starts with specified search expression, otherwise False </description>
/// 	<param name="str" type="string"> String to search </param>
/// 	<param name="search" type="string"> String to search for </param>
/// </function>
SMStringUtilities.StartsWith = function(str, search)
{
	return (str.indexOf(search) === 0);
}

/// <function container="client/SMStringUtilities" name="EndsWith" access="public" static="true" returns="boolean">
/// 	<description> Returns True if string ends with specified search expression, otherwise False </description>
/// 	<param name="str" type="string"> String to search </param>
/// 	<param name="search" type="string"> String to search for </param>
/// </function>
SMStringUtilities.EndsWith = function(str, search)
{
	return (str.length >= search.length && str.substring(str.length - search.length) === search);
}

/// <function container="client/SMStringUtilities" name="Trim" access="public" static="true" returns="string">
/// 	<description> Returns provided string excluding whitespaces in beginning and end of string </description>
/// 	<param name="str" type="string"> String to trim </param>
/// </function>
SMStringUtilities.Trim = function(str)
{
	var whitespaces = new Array(" ", "\n", "\r", "\t");
	var changed = true;

	while (changed === true)
	{
		changed = false;

		for (var i = 0 ; i < whitespaces.length ; i++)
		{
			while (str.substring(0, 1) === whitespaces[i])
			{
				str = str.substring(1, str.length);
				changed = true;
			}

			while (str.substring(str.length - 1, str.length) === whitespaces[i])
			{
				str = str.substring(0, str.length - 1);
				changed = true;
			}
		}
	}

	return str;
}

/// <function container="client/SMStringUtilities" name="ReplaceAll" access="public" static="true" returns="string">
/// 	<description> Replace all occurences of given value within a string and return the result </description>
/// 	<param name="str" type="string"> String to replace within </param>
/// 	<param name="search" type="string"> String to replace </param>
/// 	<param name="replace" type="string"> Replacement string </param>
/// </function>
SMStringUtilities.ReplaceAll = function(str, search, replace)
{
	return str.split(search).join(replace);
}

/// <function container="client/SMStringUtilities" name="Replace" access="public" static="true" returns="string">
/// 	<description> Replace single occurence of given value after a given offset within a string, and return the result </description>
/// 	<param name="str" type="string"> String to replace within </param>
/// 	<param name="search" type="string"> String to replace </param>
/// 	<param name="replace" type="string"> Replacement string </param>
/// 	<param name="offset" type="integer"> Perform replacement after specified offset </param>
/// </function>
SMStringUtilities.Replace = function(str, search, replace, offset)
{
	var startText = str.substring(0, offset);
	var endText = str.substring(offset);

	endText = endText.replace(search, replace);

	return startText + endText;
}

/// <function container="client/SMStringUtilities" name="UnicodeEncode" access="public" static="true" returns="string">
/// 	<description>
/// 		Encodes Unicode/UTF-8 characters into HEX entities. For instance the Euro symbol becomes &amp;#8364;.
/// 	</description>
/// 	<param name="str" type="string"> String to encode </param>
/// </function>
SMStringUtilities.UnicodeEncode = function(str) // Also works with Windows-1252 (HTML5 document) - encodes e.g. Euro symbol as expected
{
	// Browsers convert characters not compatible with document encoding into HEX entities.
	// Unfortunately there are two drawbacks:
	// 1) Windows-1252 specific characters are not being encoded into HEX entities when the
	//    document encoding is set to ISO-8859-1, because the browser assumes we want Windows-1252.
	//    http://en.wikipedia.org/wiki/Windows-1252:
	//    "Most modern web browsers and e-mail clients treat the MIME charset ISO-8859-1 as Windows-1252"
	// 2) Conversion only takes place on post back, meaning we cannot get the value client side that
	//    gets posted to the server.
	// This function allows us to encode characters not part of ISO-8859-1 into HEX entities client side.

	// Encode characters that are not between code point 0 (Unicode HEX 0000) and code point 255 (Unicode HEX 0100).
	// Notice that although Windows-1252 specific characters are found between code point 128 and 159 in the
	// Windows-1252 character table (http://en.wikipedia.org/wiki/Windows-1252), the browser internally handles
	// characters as Unicode, which is the reason why the code below works - Windows-1252 specific characters
	// are found outside of code point 0 - 255 in Unicode.
	// Unicode/UTF-8 is backward compatible with ASCII, meaning code point 128-159 is empty - so no
	// Unicode specific characters in this space is left unhandled.
	// Also be aware that JavaScript (ECMAScript prior to version 6) have problems dealing with Unicode Characters
	// outside of Basic Multilingual Plane (BMP). Fortunately BMP covers all the popular character sets. However,
	// popular symbols like Emojis may not work as expected. The following article describes it well:
	// https://mathiasbynens.be/notes/javascript-unicode
	// A really simple example of how JavaScript incorrectly handles symbols outside of BMP is to evaluate
	// e.g. "x".length in a browser running ECMAScript 5. Replace "x" with the symbol "CLOSED LOCK WITH KEY":
	// https://unicode-table.com/en/search/?q=CLOSED+LOCK+WITH+KEY
	// Rather than returning a length of 1, it will return a length of 2.
	// Because of this, the replace logic below will cause the callback to be invoked twice for symbols
	// outside of BMP. "CLOSED LOCK WITH KEY" will produce &#55357;&#56594; (Surrogate pair). This will be turned
	// back to the "CLOSED LOCK WITH KEY" symbol if injected into an Input field, but unfortunately not if injected
	// into an ordinary DOM element such as a <span>. Instead it displays two question marks.
	// The lock symbol can actually be represented by a HEX entity (&#128272;) that works when injected into
	// the DOM, but that on the other hand will not work when injection into an Input field.
	// Basically it's a problem that JS and DOM has different representations for the same thing. So supporting
	// characters outside of BMP is not realistic when using HEX entities to represent Unicode characters - at
	// least not with ECMAScript prior to version 6.
	//return str.replace(/[^\u0000-\u0100]/g, function(character) { /*console.log("Encoding: " + character);*/ return "&#" + character.charCodeAt(0) + ";" });

	// Encodes all characters extending ASCII - ASCII is compatible with UTF-8, ISO-8859-1 is not.
	return str.replace(/[^\x00-\x7F]/g, function(character) { /*console.log("Encoding: " + character);*/ return "&#" + character.charCodeAt(0) + ";" });

	// The example below encodes Windows-1252 specific characters only.
	// The Unicode code points are found on the Windows-1252 character table on http://en.wikipedia.org/wiki/Windows-1252
	// Notice how Windows-1252 specific characters are highlighted with thick green borders.
	// A string consisting of these characters are found in this pastebin: http://pastebin.com/ZR2SW2dL
	//return str.replace(/[\u20AC|\u201A|\u0192|\u201E|\u2026|\u2020|\u2021|\u02C6|\u2030|\u0160|\u2039|\u0152|\u017D|\u2018|\u2019|\u201C|\u201D|\u2022|\u2013|\u2014|\u02DC|\u2122|\u0161|\u203A|\u0153|\u017E|\u0178]/g, function(character) { console.log("Encoding: " + character); return "&#" + character.charCodeAt(0) + ";" });
}

/// <function container="client/SMStringUtilities" name="UnicodeDecode" access="public" static="true" returns="string">
/// 	<description>
/// 		Decodes string containing Unicode HEX entities into ordinary text and returns the result.
/// 	</description>
/// 	<param name="str" type="string"> String to decode </param>
/// </function>
SMStringUtilities.UnicodeDecode = function(str)
{
	return str.replace(/&#\d+;/g, function(entity) // Match &# followed by one or more digits followed by semicolon.  (?<=&#)\d+(?=;)  would be better, but JS only supports Look Ahead, so let's just substring &# and the semicolon away.
	{
		/*console.log("Decoding: " + entity);*/
		return String.fromCharCode(entity.substring(2, entity.length - 1));
	});
}

// SMColor

function SMColor()
{
}

/// <function container="client/SMColor" name="RgbToHex" access="public" static="true" returns="string">
/// 	<description> Convert RGB colors into HEX color string - returns null in case of invalid RGB values </description>
/// 	<param name="r" type="integer"> Color index for red </param>
/// 	<param name="g" type="integer"> Color index for green </param>
/// 	<param name="b" type="integer"> Color index for blue </param>
/// </function>
SMColor.RgbToHex = function(r, g, b)
{
	if (typeof(r) !== "number" || typeof(g) !== "number" || typeof(b) !== "number")
		return null;

	if (r < 0 || r > 255 || g < 0 || g > 255 || b < 0 || b > 255)
		return null;

    var rHex = r.toString(16);
    var gHex = g.toString(16);
    var bHex = b.toString(16);

    return ("#" + ((rHex.length === 1) ? "0" : "") + rHex + ((gHex.length === 1) ? "0" : "") + gHex + ((bHex.length === 1) ? "0" : "") + bHex).toUpperCase();
}

/// <function container="client/SMColor" name="ParseHex" access="public" static="true" returns="object">
/// 	<description> Convert HEX color string into RGB color object, e.g. { Red: 150, Green: 30, Blue: 185 } - returns null in case of invalid HEX value </description>
/// 	<param name="hex" type="string"> HEX color string, e.g. #C0C0C0 (hash symbol is optional) </param>
/// </function>
SMColor.ParseHex = function(hex)
{
	var result = hex.match(/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i);

	if (result !== null)
		return { Red: parseInt(result[1], 16), Green: parseInt(result[2], 16), Blue: parseInt(result[3], 16) };

	return null;
}

/// <function container="client/SMColor" name="ParseRgb" access="public" static="true" returns="object">
/// 	<description>
/// 		Parses RGB(A) from string and turns result into RGB(A) color object, e.g.
/// 		{ Red: 100, Green: 100, Blue: 100, Alpha: 0.3 } - returns null in case of invalid value.
/// 	</description>
/// 	<param name="val" type="string"> RGB(A) color string, e.g. rgba(100, 100, 100, 0.3) or simply 100,100,200,0.3 </param>
/// </function>
SMColor.ParseRgb = function(val)
{
	// Parse colors from rgb[a](r, g, b[, a]) string
	var result = val.match(/\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(\s*,\s*(\d*.*\d+))*/); // http://regex101.com/r/rZ7rO2/9

	if (result === null)
		return null;

	var c = {};
	c.Red = parseInt(result[1]);
	c.Green = parseInt(result[2]);
	c.Blue = parseInt(result[3]);
	c.Alpha = ((result[5] !== undefined) ? parseFloat(result[5]) : 1.00);

	return c;
}

// SMCore

/// <container name="client/SMCore">
/// 	Features extending the capabilities of native JavaScript
/// </container>
function SMCore()
{
}

/// <function container="client/SMCore" name="ForEach" access="public" static="true">
/// 	<description>
/// 		Iterates through elements in array and passes each value to the provided callback function.
/// 	</description>
/// 	<param name="arr" type="array"> Array containing values to iterate through </param>
/// 	<param name="callback" type="delegate">
/// 		Callback function accepting values from the array, passed in turn.
/// 		Return False from callback to break loop.
/// 	</param>
/// </function>
/// <function container="client/SMCore" name="ForEach" access="public" static="true">
/// 	<description>
/// 		Iterates through object properties and passes each property name to the provided callback function.
/// 	</description>
/// 	<param name="obj" type="object"> Object containing properties to iterate through </param>
/// 	<param name="callback" type="delegate">
/// 		Callback function accepting properties from the object, passed in turn.
/// 		Return False from callback to break loop.
/// 	</param>
/// </function>
SMCore.ForEach = function(obj, callback)
{
	if (obj instanceof Array || typeof(obj.length) === "number") // Array or DOMNodeList
	{
		for (var i = 0 ; i < obj.length ; i++)
			if (callback(obj[i]) === false)
				break;
	}
	else // Object
	{
		for (var i in obj)
			if (callback(i) === false)
				break;
	}
}

/// <function container="client/SMCore" name="GetIndex" access="public" static="true">
/// 	<description>
/// 		Iterates through given array to find specified object or
/// 		value, and return its index. Returns -1 if entry could not be found.
/// 	</description>
/// 	<param name="arr" type="array"> Array to search for given value or object </param>
/// 	<param name="obj" type="object"> Object or value to find index for </param>
/// </function>
SMCore.GetIndex = function(arr, obj)
{
	if (arr instanceof Array)
	{
		for (var i = 0 ; i < arr.length ; i++)
			if (SMCore.IsEqual(arr[i], obj) === true)
				return i;
	}

	return -1;
}

/// <function container="client/SMCore" name="Clone" access="public" static="true" returns="object">
/// 	<description>
/// 		Clone JavaScript object. Supported object types and values:
/// 		String, Number, Boolean, Date, Array, (JSON) Object, Function, Undefined, Null, NaN.
/// 		Variables defined as undefined are left out of clone,
/// 		since an undefined variable is equal to a variable defined as undefined.
/// 		Notice that Arrays and Objects can contain supported object types and values only.
/// 		Functions are considered references, and as such the cloned object will reference
/// 		the same functions.
/// 		Custom properties set on native JS objects (e.g. Array.XYZ) are not cloned, only
/// 		values are. Naturally custom (JSON) objects will be fully cloned, including all
/// 		properties. Both arrays and custom (JSON) objects are cloned recursively.
/// 		Be aware of self referencing variables and circular structures, which
/// 		will cause an infinite loop, and eventually a stack overflow exception.
/// 		DOM objects and window/frame instances are not supported.
/// 	</description>
/// 	<param name="obj" type="object"> JS object to clone </param>
/// </function>
SMCore.Clone = function(obj)
{
	// TODO - Known problem:
	// var a = new SomeClass();
	// var b = (a instanceOf SomeClass);
	// var c = (SMCore.Clone(a) instanceOf SomeClass);
	// Variable b is True as expected, while variable c is False!
	// TODO: Restore/preserve support for instanceof!

	// TEST CASE: Example below is supposed to return: TRUE!
	/*var f1 = function() { alert("Hello"); }
	var x =
	{
		str: "Hello world",
		num: 123,
		dec: 123.321,
		date: new Date("2014-12-01 13:02:23"),
		bool: true,
		bool2: false,
		arr: [100, 200, 250, 400],
		arr2: ["Hello", "world"],
		arr3: [123, "hello", true, false, new Date("1990-01-20"), [1,2,3], { x: { "hapsen": f1, "hello": new Array(1,2,3) } }],
		obj: { a: 123, b: 123.321, c: true, d: false, e: new Date("1993-06-25"), f: "hello", g: null, h: undefined }
	};
	var y = SMCore.Clone(x);
	console.log("Is equal: " + SMCore.IsEqual(x, y));*/

	// Clone object by serializing it into a JSON string, and parse it back into a JS object

	var serialized = JSON.stringify(obj); // Returns undefined if obj is either undefined or a function (these are not serialized)
	var clone = ((serialized !== undefined) ? JSON.parse(serialized) : serialized); // parse(..) throws error if argument is undefined

	// Fixes
	//  - Dates are serialized into strings - turn back into Date instances.
	//  - Functions are not serialized (discarded) - add function reference to clone
	//  - Number variables with a value of NaN is serialized into Null - convert to NaN

	var fixClone = null;
	fixClone = function(org, clo)
	{
		if (org instanceof Date) // Dates are turned into string representations - turn back into Date instances
		{
			return new Date(org.getTime());
		}
		else if (typeof(org) === "function") // Functions are not serialized - use same reference as original object
		{
			return org;
		}
		else if (typeof(org) === "number" && isNaN(org) === true) // NaN is turned into Null - turn back into NaN
		{
			return parseInt("");
		}
		else if (org && typeof(org) === "object") // Recursively fix children (object/array)
		{
			for (var p in org)
				clo[p] = fixClone(org[p], clo[p]);
		}

		return clo;
	};

	clone = fixClone(obj, clone);

	// Done, clone is now identical to original object - SMCore.IsEqual(obj, clone) should return True

	return clone;
}

/// <function container="client/SMCore" name="IsEqual" access="public" static="true" returns="boolean">
/// 	<description>
/// 		Compare two JavaScript objects to determine whether they are identical.
/// 		Returns True if objects are identical (equal), otherwise False.
/// 		Supported object types and values:
/// 		String, Number, Boolean, Date, Array, (JSON) Object, Function, Undefined, Null, NaN.
/// 		Notice that Arrays and Objects can contain supported object types and values only.
/// 		Functions are compared by reference, not by value.
/// 		Custom properties set on native JS objects (e.g. Array.XYZ) are not compared, only
/// 		values are. Naturally custom (JSON) objects will be fully compared, including all
/// 		properties. Both arrays and custom (JSON) objects are compared recursively.
/// 		Be aware of self referencing variables and circular structures, which
/// 		will cause an infinite loop, and eventually a stack overflow exception.
/// 		DOM objects and window/frame instances are not comparable.
/// 	</description>
/// 	<param name="jsObj1" type="object"> JS object to compare agains second JS object </param>
/// 	<param name="jsObj2" type="object"> JS object to compare agains first JS object </param>
/// </function>
SMCore.IsEqual = function(jsObj1, jsObj2)
{

	// TEST CASE: Example below is supposed to return: TRUE!
	/*var f1 = function() { alert("Hello"); }
	var f2 = f1;
	SMCore.IsEqual(
	{
		str: "Hello world",
		num: 123,
		dec: 123.321,
		date: new Date("2014-12-01 13:02:23"),
		bool: true,
		bool2: false,
		arr: [100, 200, 250, 400],
		arr2: ["Hello", "world"],
		arr3: [123, "hello", true, false, new Date("1990-01-20"), [1,2,3], { x: { "hapsen": f1, "hello": new Array(1,2,3) } }],
		obj: { a: 123, b: 123.321, c: true, d: false, e: new Date("1993-06-25"), f: "hello", g: null, h: undefined }
	},
	{
		str: "Hello world",
		num: 123,
		dec: 123.321,
		date: new Date("2014-12-01 13:02:23"),
		bool: true,
		bool2: false,
		arr: [100, 200, 250, 400],
		arr2: ["Hello", "world"],
		arr3: [123, "hello", true, false, new Date("1990-01-20"), [1,2,3], { x: { "hapsen": f2, "hello": new Array(1,2,3) } }],
		obj: { a: 123, b: 123.321, c: true, d: false, e: new Date("1993-06-25"), f: "hello", g: null, h: undefined }
	});*/

	if (typeof(jsObj1) !== typeof(jsObj2))
		return false;

	if ((jsObj1 === undefined && jsObj2 === undefined) || (jsObj1 === null && jsObj2 === null))
	{
		return true;
	}
	else if (typeof(jsObj1) === "string" || typeof(jsObj1) === "boolean")
	{
		return (jsObj1 === jsObj2);
	}
	else if (typeof(jsObj1) === "number")
	{
		if (isNaN(jsObj1) === true && isNaN(jsObj2) === true) // NaN variables are not comparable!
			return true;
		else
			return (jsObj1 === jsObj2);
	}
	else if (jsObj1 instanceof Date && jsObj2 instanceof Date)
	{
		return (jsObj1.getTime() === jsObj2.getTime());
	}
	else if (jsObj1 instanceof Array && jsObj2 instanceof Array)
	{
		if (jsObj1.length !== jsObj2.length)
			return false;

		for (var i = 0 ; i < jsObj1.length ; i++)
		{
			if (SMCore.IsEqual(jsObj1[i], jsObj2[i]) === false)
				return false;
		}

		return true;
	}
	else if (typeof(jsObj1) === "object" && typeof(jsObj2) === "object" && jsObj1 !== null && jsObj2 !== null) // typeof(null) returns "object"
	{
		for (var k in jsObj1)
			if (SMCore.IsEqual(jsObj1[k], jsObj2[k]) === false)
				return false;

		return true;
	}
	else if (typeof(jsObj1) === "function" && typeof(jsObj2) === "function")
	{
		// Returns True in the following situation:
		// var f1 = function() { alert("Hello"); }
		// var f2 = f1;
		// SMCore.IsEqual(f1, f2);

		// Returns False in the following situation:
		// var f1 = function() { alert("Hello"); }
		// var f2 = function() { alert("Hello"); }
		// SMCore.IsEqual(f1, f2);

		return (jsObj1 === jsObj2);
	}

	return false;
}

// SMDom

/// <container name="client/SMDom">
/// 	DOM (Document Object Model) manipulation and helper functionality
/// </container>
function SMDom()
{
}

/// <function container="client/SMDom" name="AddClass" access="public" static="true">
/// 	<description> Add CSS class to element if not already found </description>
/// 	<param name="elm" type="DOMElement"> Element on which CSS class is to be added </param>
/// 	<param name="cls" type="string"> CSS class name </param>
/// </function>
SMDom.AddClass = function(elm, cls)
{
	if (SMDom.HasClass(elm, cls) === false)
		elm.className += ((elm.className !== "") ? " " : "") + cls;
}

/// <function container="client/SMDom" name="RemoveClass" access="public" static="true">
/// 	<description> Remove CSS class from element if found </description>
/// 	<param name="elm" type="DOMElement"> Element from which CSS class is to be removed </param>
/// 	<param name="cls" type="string"> CSS class name </param>
/// </function>
SMDom.RemoveClass = function(elm, cls)
{
    var arr = elm.className.split(" ");
    var newCls = "";

    SMCore.ForEach(arr, function(item)
    {
        if (item !== cls)
            newCls += ((newCls !== "") ? " " : "") + item;
    });

    elm.className = newCls;
}

/// <function container="client/SMDom" name="HasClass" access="public" static="true" returns="boolean">
/// 	<description> Check whether given DOMElement has specified CSS class registered - returns True if found, otherwise False </description>
/// 	<param name="elm" type="DOMElement"> Element for which CSS class may be registered </param>
/// 	<param name="cls" type="string"> CSS class name </param>
/// </function>
SMDom.HasClass = function(elm, cls)
{
    var arr = elm.className.split(" ");
	var found = false;

    SMCore.ForEach(arr, function(item)
    {
        if (item === cls)
		{
			found = true;
			return false; // Stop loop
		}
    });

    return found;
}

/// <function container="client/SMDom" name="GetComputedStyle" access="public" static="true" returns="string">
/// 	<description>
/// 		Get style value applied after stylesheets have been loaded.
/// 		An empty string may be returned if style has not been defined, or Null if style does not exist. </description>
/// 	<param name="elm" type="DOMElement"> Element which contains desired CSS style value </param>
/// 	<param name="style" type="string"> CSS style property name </param>
/// </function>
SMDom.GetComputedStyle = function(elm, style)
{
	var res = null;

    if (window.getComputedStyle)
	{
		res = window.getComputedStyle(elm)[style];
	}
    else if (elm.currentStyle)
	{
        res = elm.currentStyle[style];
	}

    return (res !== undefined ? res : null);
}

/// <function container="client/SMDom" name="GetInnerValue" access="public" static="true" returns="string">
/// 	<description> Returns inner HTML value of DOM element with specified ID - Null if not found </description>
/// 	<param name="elmId" type="string"> Unique element ID </param>
/// </function>
SMDom.GetInnerValue = function(elmId)
{
	var elm = this.GetElement(elmId);

	if (elm === null)
		return null;

	return elm.innerHTML;
}

/// <function container="client/SMDom" name="SetAttribute" access="public" static="true" returns="boolean">
/// 	<description>
/// 		Set attribute value on specified DOM element.
/// 		This function is used to ensure that attributes are handled identically by different browsers.
/// 		Returns True on success, otherwise False.
/// 	</description>
/// 	<param name="elmId" type="string"> Unique element ID </param>
/// 	<param name="attr" type="string"> Attribute name </param>
/// 	<param name="value" type="string"> Attribute value </param>
/// </function>
SMDom.SetAttribute = function(elmId, attr, value)
{
	var elm = this.GetElement(elmId);

	if (elm === null)
		return false;

	// Firefox/Safari changes the value of the 'value' and 'checked' attributes, not the visible
	// values which IE does. This makes sure the actual values are changed for all browsers.
	if (attr.toLowerCase() === "value")
		elm.value = value;
	else if (attr.toLowerCase() === "checked")
		elm.checked = ((value === "true") ? true : false);
	else
		elm.setAttribute(attr, value);

	return true;
}

/// <function container="client/SMDom" name="GetAttribute" access="public" static="true" returns="string">
/// 	<description>
/// 		Returns given attribute value for specified DOM element.
/// 		This function is used to ensure that attributes are handled identically
/// 		by different browsers. Returns attribute value on success, otherwise Null.
/// 	</description>
/// 	<param name="elmId" type="string"> Unique element ID </param>
/// 	<param name="attr" type="string"> Attribute name </param>
/// </function>
SMDom.GetAttribute = function(elmId, attr)
{
	var elm = this.GetElement(elmId);

	if (elm === null)
		return null;

	// Firefox/Safari returns the value of the 'value' and 'checked' attributes, not the visible
	// values which IE does. This makes sure the actual values are returned for all browsers.
	if (attr.toLowerCase() === "value")
		return elm.value;
	else if (attr.toLowerCase() === "checked")
		return ((elm.checked === true) ? "true" : "false");

	return elm.getAttribute(attr);
}

/// <function container="client/SMDom" name="SetStyle" access="public" static="true" returns="boolean">
/// 	<description>
/// 		Set style property on specified DOM element.
/// 		This function is used to ensure that style properties are properly applied
/// 		by different browsers. Returns True on success, otherwise False.
/// 	</description>
/// 	<param name="elmId" type="string"> Unique element ID </param>
/// 	<param name="property" type="string"> Style property name </param>
/// 	<param name="value" type="string"> Property value </param>
/// </function>
SMDom.SetStyle = function(elmId, property, value)
{
	var elm = this.GetElement(elmId);

	if (elm === null)
		return false;

	if (SMBrowser.GetBrowser() === "MSIE" && SMBrowser.GetVersion <= 7) // IE8 properly supports the display property if a DocType is set, which we assume it is
	{
		if (property.toLowerCase() === "display" && (value === "inherit" || value === "inline-table" || value === "run-in" || value === "table" || value === "table-caption" || value === "table-cell" || value === "table-column" || value === "table-column-group" || value === "table-row" || value === "table-row-group"))
			value = "block";
	}

	elm.style[property] = value;

	return true;
}

/// <function container="client/SMDom" name="GetStyle" access="public" static="true" returns="string">
/// 	<description> Returns given style property for specified DOM element if found, otherwise Null </description>
/// 	<param name="elmId" type="string"> Unique element ID </param>
/// 	<param name="property" type="string"> Style property name </param>
/// </function>
SMDom.GetStyle = function(elmId, property)
{
	var elm = this.GetElement(elmId);

	if (elm === null)
		return null;

	return elm.style[property];
}

/// <function container="client/SMDom" name="ElementExists" access="public" static="true" returns="boolean">
/// 	<description> Returns True if specified DOM element exists, otherwise False </description>
/// 	<param name="id" type="string"> Unique element ID </param>
/// </function>
SMDom.ElementExists = function(id)
{
	var elm = document.getElementById(id);

	if (elm !== null)
		return true;
	else
		return false;
}

/// <function container="client/SMDom" name="GetElement" access="public" static="true" returns="DOMElement">
/// 	<description> Returns specified DOM element if found, otherwise returns Null </description>
/// 	<param name="id" type="string"> Unique element ID </param>
/// </function>
SMDom.GetElement = function(id)
{
	var elm = document.getElementById(id);

	if (elm === null)
	{
		//alert("Unable to get element '" + id + "' - not found");
		return null;
	}

	return elm;
}

/// <function container="client/SMDom" name="WrapElement" access="public" static="true">
/// 	<description> Wraps element in container element while preserving position in DOM </description>
/// 	<param name="elementToWrap" type="DOMElement"> Element to wrap </param>
/// 	<param name="container" type="DOMElement"> Container to wrap element within </param>
/// </function>
SMDom.WrapElement = function(elementToWrap, container)
{
	var parent = elementToWrap.parentNode;
	var nextSibling = elementToWrap.nextSibling;

	container.appendChild(elementToWrap); // Causes elementToWrap to be removed from existing container

	if (nextSibling === null)
		parent.appendChild(container);
	else
		parent.insertBefore(container, nextSibling);
}

// SMEventHandler

/// <container name="client/SMEventHandler">
/// 	Event handler functionality
/// </container>
function SMEventHandler()
{
}

SMEventHandler.Internal = {};
SMEventHandler.Internal.PageLoaded = false;

/// <function container="client/SMEventHandler" name="AddEventHandler" access="public" static="true">
/// 	<description> Registers handler for specified event on given DOMElement </description>
/// 	<param name="element" type="DOMElement"> DOMElement on to which event handler is registered </param>
/// 	<param name="event" type="string"> Event name without 'on' prefix (e.g. 'load', 'mouseover', 'click' etc.) </param>
/// 	<param name="eventFunction" type="delegate"> JavaScript function delegate </param>
/// </function>
SMEventHandler.AddEventHandler = function(element, event, eventFunction)
{
	if (element.addEventListener) // W3C
	{
		element.addEventListener(event, eventFunction, false); // false = event bubbling (reverse of event capturing)
	}
	else if (element.attachEvent) // IE
	{
		if (event.toLowerCase() === "domcontentloaded" && SMBrowser.GetBrowser() === "MSIE" && SMBrowser.GetVersion() <= 8)
		{
			// DOMContentLoaded not supported on IE8.
			// Using OnReadyStateChange to achieve similar behaviour.

			element.attachEvent("onreadystatechange", function(e)
			{
				if (element.readyState === "complete")
				{
					eventFunction(e); // NOTICE: Event argument not identical to argument passed to modern browsers using the real DOMContentLoaded event!
				}
			});
		}
		else
		{
			element.attachEvent("on" + event, eventFunction);
		}
	}

	// Fire event function for onload event if document in window/iframe has already been loaded.
	// Notice that no event argument is passed to function since we don't have one.
	if (event.toLowerCase() === "load" && element.nodeType === 9 && element.readyState === "complete") // Element is a Document (window.document or iframe.contentDocument)
		eventFunction();
	else if (event.toLowerCase() === "load" && element.contentDocument && element.contentDocument.readyState === "complete") // Element is an iFrame
		eventFunction();
	else if (event.toLowerCase() === "load" && element === window && SMEventHandler.Internal.PageLoaded === true) // Element is the current Window instance
		eventFunction();
}

;(function()
{
	SMEventHandler.AddEventHandler(window, "load", function()
	{
		SMEventHandler.Internal.PageLoaded = true;
	});
})();


// SMCookie

/// <container name="client/SMCookie">
/// 	Cookie functionality
/// </container>
function SMCookie()
{
}

/// <function container="client/SMCookie" name="SetCookie" access="public" static="true" returns="boolean">
/// 	<description> Create or update cookie - returns True on success, otherwise False </description>
/// 	<param name="name" type="string"> Unique cookie name </param>
/// 	<param name="value" type="string"> Cookie value (cannot contain semi colon!) </param>
/// 	<param name="seconds" type="integer"> Expiration time in seconds </param>
/// </function>
SMCookie.SetCookie = function(name, value, seconds)
{
	if (value.indexOf(';') > -1)
	{
		//alert("Unable to set cookie - value contains illegal character: ';'");
		return false;
	}

	var date = new Date();
	date.setTime(date.getTime() + (seconds * 1000));

	var path = location.pathname.match(/^.*\//)[0]; // Examples: / OR /Sitemagic/ OR /Sitemagic/sites/demo/ - https://regex101.com/r/aU8iW6/1

	if (SMStringUtilities.EndsWith(path, "/shop/") === true) // Special /shop/ directory reserved for e-commerce extensions
	{
		path = path.substring(0, path.length - "shop/".length);
	}

	if (SMEnvironment.IsSubSite() === false)
	{
		// Unfortunately cookies on main site will be accessible by subsites, and also cause naming conflicts.
		// Therefore a prefix is made part of the cookie key for the main site.
		// This is not necessary for subsites since the cookie path prevent cookies from being shared.
		// Example:
		//  - /Sitemagic: Cookies are available to every sub folder
		//  - /Sitemagic/sites/demo: Cookies are available to every sub folder, but not parent folders, and therefore not to e.g. /Sitemagic/sites/example
		// NOTICE: "SM#/#" prefix MUST be identical to prefix used in SMEnvironment server side!
		name = "SM#/#" + name;
	}

	document.cookie = name + "=" + value + "; expires=" + date.toGMTString() + "; path=" + path;

	return true;
}

/// <function container="client/SMCookie" name="GetCookie" access="public" static="true" returns="string">
/// 	<description> Returns cookie value if found, otherwise Null </description>
/// 	<param name="name" type="string"> Unique cookie name </param>
/// </function>
SMCookie.GetCookie = function(name)
{
	if (SMEnvironment.IsSubSite() === false)
	{
		// Use cookie prefix for main site to prevent conflicts with cookies on subsites.
		// NOTICE: "SM#/#" prefix MUST be identical to prefix used in SMEnvironment server side!
		name = "SM#/#" + name;
	}

	var name = name + "=";
	var cookies = document.cookie.split(";");
	var cookie = null;

	for (i = 0 ; i < cookies.length ; i++)
	{
		cookie = cookies[i];

		while (cookie.charAt(0) === " ")
			cookie = cookie.substring(1, cookie.length);

		if (cookie.indexOf(name) === 0)
			return cookie.substring(name.length, cookie.length);
	}

	return null;
}

/// <function container="client/SMCookie" name="GetCookies" access="public" static="true" returns="string[]">
/// 	<description> Return names of all cookies </description>
/// </function>
SMCookie.GetCookies = function()
{
	var cookies = document.cookie.split(";");
	var cookie = null;
	var info = null;
	var names = [];

	for (i = 0 ; i < cookies.length ; i++)
	{
		cookie = cookies[i];

		while (cookie.charAt(0) === " ")
			cookie = cookie.substring(1, cookie.length);

		info = cookie.split("=");

		if (SMEnvironment.IsSubSite() === true && info[0].indexOf("SM#/#") === 0) // Exclude main site cookies on subsites
			continue;

		names.push(((info[0].indexOf("SM#/#") === 0 ? info[0].substring(5) : info[0])));
	}

	return names;
}

/// <function container="client/SMCookie" name="RemoveCookie" access="public" static="true" returns="boolean">
/// 	<description> Remove cookie - returns True on success, otherwise False </description>
/// 	<param name="name" type="string"> Unique cookie name </param>
/// </function>
SMCookie.RemoveCookie = function(name)
{
	return this.SetCookie(name, "", -1);
}

// SMMessageDialog

/// <container name="client/SMMessageDialog">
/// 	Message and dialog functionality
/// </container>
function SMMessageDialog()
{
}

/// <function container="client/SMMessageDialog" name="ShowMessageDialog" access="public" static="true">
/// 	<description> Display message dialog </description>
/// 	<param name="content" type="string"> Content of message dialog </param>
/// </function>
SMMessageDialog.ShowMessageDialog = function(content)
{
	alert(content);
}

/// <function container="client/SMMessageDialog" name="ShowMessageDialogOnLoad" access="public" static="true">
/// 	<description> Display message dialog - postpone until onload event is fired </description>
/// 	<param name="content" type="string"> Content of message dialog </param>
/// </function>
SMMessageDialog.ShowMessageDialogOnLoad = function(content)
{
	SMEventHandler.AddEventHandler(window, "load", function() { alert(content); });
}

/// <function container="client/SMMessageDialog" name="ShowConfirmDialog" access="public" static="true" returns="boolean">
/// 	<description> Display confirmation dialog. Returns True if user clicks the OK button, False otherwise. </description>
/// 	<param name="content" type="string"> Content of confirmation dialog </param>
/// </function>
SMMessageDialog.ShowConfirmDialog = function(content)
{
	return confirm(content);
}

/// <function container="client/SMMessageDialog" name="ShowInputDialog" access="public" static="true" returns="string">
/// 	<description> Display input dialog. Returns user input as string. Null is returned if user cancels input dialog. </description>
/// 	<param name="content" type="string"> Content of confirmation dialog </param>
/// 	<param name="value" type="string"> Initial value in input field </param>
/// </function>
SMMessageDialog.ShowInputDialog = function(content, value)
{
	return prompt(content, value);
}

/// <function container="client/SMMessageDialog" name="ShowPasswordDialog" access="public" static="true">
/// 	<description> Display password dialog. Password entered is passed to callback function. </description>
/// 	<param name="callback" type="delegate">
/// 		JavaScript function delegate fired when user sends password or closes password dialog.
/// 		Function must take one arguments (string) which is the password entered.
/// 		The argument will be Null if the password dialog was canceled.
/// 	</param>
/// 	<param name="caption" type="string" default="String.Empty"> Optional password dialog title </param>
/// 	<param name="description" type="string" default="String.Empty"> Optional password dialog description </param>
/// </function>
SMMessageDialog.ShowPasswordDialog = function(callback, caption, description)
{
	var title = (caption !== undefined ? caption : SMLanguageHandler.GetTranslation("EnterPassword"));
	var msg = (description !== undefined ? description : "");

	// Dialog markup

	var content = "";
	content += "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">";
	content += "<html>";
	content += "<head>";
	content += "	<title>Password</title>";
	content += "	<style type=\"text/css\">";
	content += "		html, body { margin: 0px; padding: 0px; }";
	content += "		body { padding: 10px; }";
	content += "		body, input { font-family: verdana; font-size: 12px; color: #333333; }";
	content += "		h1 { font-size: 14px; font-weight: bold; }";
	content += "		input { border: 1px solid #808080; margin: 2px; padding: 4px; }";
	content += "		input[type='password'] { outline: none; }";
	content += "		div { margin-top: 15px; }";
	content += "	</style>";
	content += "</head>";
	content += "<body>";
	content += "	<h1>Password</h1>";
	content += "	<input type=\"password\" style=\"width: 200px;\"><input type=\"button\" value=\"OK\"><input type=\"button\" value=\"Cancel\">";
	content += "	<div>&nbsp;</div>";
	content += "</body>";
	content += "</html>";

	var smwin = new SMWindow(SMRandom.CreateGuid());
	smwin.SetSize(400, ((msg !== "") ? 160 : 125));
	smwin.SetContent(content);
	smwin.SetOnShowCallback(function()
	{
		// Get elements

		var win = smwin.GetInstance();

		var lblHeadline = win.document.getElementsByTagName("h1")[0];
		var lblMsg = win.document.getElementsByTagName("div")[0];

		var txtPass = win.document.getElementsByTagName("input")[0];
		var cmdOk = win.document.getElementsByTagName("input")[1];
		var cmdCancel = win.document.getElementsByTagName("input")[2];

		// Assign labels (language)

		win.document.title = title;

		lblHeadline.innerHTML = title;
		lblHeadline.style.marginBottom = ((msg !== "") ? "15px" : "20px");
		lblMsg.innerHTML = SMStringUtilities.ReplaceAll(SMStringUtilities.ReplaceAll(msg, "\r", ""), "\n", "<br>");

		cmdOk.value = SMLanguageHandler.GetTranslation("Ok");
		cmdCancel.value = SMLanguageHandler.GetTranslation("Cancel");

		// Event listeners

		SMEventHandler.AddEventHandler(cmdOk, "click", function()
		{
			callback(txtPass.value);
			smwin.Close();
		});

		SMEventHandler.AddEventHandler(cmdCancel, "click", function()
		{
			callback(null);
			smwin.Close();
		});

		SMEventHandler.AddEventHandler(txtPass, "keydown", function(e)
		{
			e = (win.event ? win.event : e);

			if (e.keyCode === 13)
			{
				callback(txtPass.value);
				smwin.Close();
			}
			else if (e.keyCode === 27)
			{
				callback(null);
				smwin.Close();
			}
		});

		SMEventHandler.AddEventHandler(window, "beforeunload", function()
		{
			smwin.Close();
		});

		txtPass.focus();
	});
	smwin.SetOnCloseCallback(function()
	{
		var win = smwin.GetInstance(); // null if already closed, e.g. by using OK/Cancel buttons or ENTER/ESC keys

		if (win !== null)
			callback(null);
	});
	smwin.Show();
}

// SMWindow

/// <container name="client/SMWindow">
/// 	Window/dialog functionality.
///
/// 	// Example of opening a picture in a window
/// 	var smw = new SMWindow(&quot;MyPictureViewer&quot;);
/// 	smw.SetUrl(&quot;files/images/Harbour.jpg&quot;);
/// 	smw.SetSize(400, 300);
/// 	smw.SetOnShowCallback(function() { alert(&quot;Window visible&quot;); });
/// 	smw.SetOnCloseCallback(function() { alert(&quot;Window closed&quot;); });
/// 	smw.Show();
///
/// 	NOTICE: Sitemagic 2015 introduced some changes to the SMWindow
/// 	class which might require developers to adjust existing code.
///
/// 	The new version uses dialogs (inline pop ups) rather than
/// 	old browser pop ups which are often being blocked, and are
/// 	broken in many respects. Also, old browser windows are implemented
/// 	very differently across different browsers, so some options will work
/// 	on one browser, and not on other browsers.
/// 	The new Dialog Mode (which is default) is cross browser compatible and
/// 	will not be blocked.
///
/// 	It is possible to enable Legacy Mode to keep using the old browser windows, and by
/// 	that either avoid or minimize changes to existing code. Using Legacy Mode is not
/// 	recommended though.
/// 	Legacy Mode can be enabled globally using the config.xml.php configuration file,
/// 	or per SMWindow instance with a small change to existing code.
///
/// 	How to globally enable Legacy Mode (always prefer old browser windows):
/// 	1) Open config.xml.php for editing
/// 	2) Add the following entry and save the file:
/// 	   &lt;entry key=&quot;SMWindowLegacyMode&quot; value=&quot;True&quot; /&gt;
///
/// 	How to enable Legacy Mode per SMWindow instance in code (use old browser window):
/// 	 - Replace smw.Show() with smw.Show(true) - smw being an instance of SMWindow.
///
/// 	Code changes that might be required to fully support the new SMWindow class
/// 	in both Legacy Mode and Dialog Mode:
///
/// 	- Rather than using window.opener to reference the parent window, use the following
/// 	  snippet to support both the new Dialog Mode and Legacy Mode:
/// 	  var parentWindow = window.opener || window.top;
///
/// 	- Calling window.close() will not work in Dialog Mode. To work properly in both
/// 	  Dialog Mode and Legacy Mode use the following approach instead:
/// 	  var parentWindow = window.opener || window.top;
/// 	  var smwin = parentWindow.SMWindow.GetInstance(window.name); // SMWindow instance
/// 	  smwin.Close();
/// 	  Be aware that this only works for pages loaded from the same domain (Same-Origin Policy).
/// 	  If the page is loaded from a foreign domain and it needs to be able to close itself, Legacy Mode
/// 	  in conjunction with window.close() must be used.
///
/// 	- Pages loaded inside an instance of SMWindow cannot reliably use the OnBeforeUnload event since it is only fully
/// 	  supported in Legacy Mode. A page displayed in Dialog Mode can be unloaded by either navigating within the dialog,
/// 	  by closing the dialog (which does not fire OnBeforeUnload), or by reloading the page containing the dialog. This
/// 	  does not map well to the old OnBeforeUnload event. Also the OnBeforeUnload event is not widely and consistently
/// 	  supported by all browsers, and should only be used to ask the user to consider staying on the page. Alternative
/// 	  mechanisms such as persisting data on the fly might be a better solution to prevent users from loosing data when
/// 	  a window is closed. If the OnBeforeUnload event is required to solve a specific problem, forcing Legacy Mode will
/// 	  be necessary.
///
/// 	- Calling smwin.GetInstance() immediately after calling smwin.Show() is no longer reliable since the window
/// 	  instance may not be immediately ready in Dialog Mode. Instead use the following approach:
/// 	  smwin.SetOnShowCallback(function() { var win = smwin.GetInstance(); /* ...... */ });
/// 	  Use SetOnLoadCallback(..) instead if the DOM document needs to be manipulated.
///
/// 	Also be aware that the following options are only being used in Legacy Mode:
/// 	SetDisplayToolBar, SetDisplayLocation, SetDisplayMenuBar, SetDisplayStatusBar.
/// 	Many browsers, however, do not honor these options anymore. It is recommended to
/// 	simply avoid using them. Some browsers do not honor these options either in
/// 	Legacy Mode (e.g. Chrome): SetResizable, SetDisplayScrollBars. They work as
/// 	expected in Dialog Mode.
/// </container>

/// <function container="client/SMWindow" name="SMWindow" access="public">
/// 	<description> Constructor - creates instance of SMWindow </description>
/// 	<param name="identifier" type="string" default="undefined"> Unique instance ID </param>
/// </function>
function SMWindow(identifier)
{
	// Properties

	this.id = (identifier ? identifier : SMRandom.CreateGuid());
	this.url = "";
	this.content = "";
	this.width = 320;
	this.height = 240;
	this.displayToolBar = false;		// Legacy Mode only, not honored by all browsers
	this.displayLocation = false;		// Legacy Mode only, not honored by all browsers
	this.displayMenuBar = false;		// Legacy Mode only, not honored by all browsers
	this.displayStatusBar = false;		// Legacy Mode only, not honored by all browsers
	this.displayScrollBars = true;		// Not honored by all browsers
	this.resizable = true;				// Not honored by all browsers
	this.positionLeft = 0;
	this.positionTop = 0;
	this.centerWindow = true;
	this.preferModal = false;

	this.showCallback = null;
	this.closeCallback = null;
	this.loadCallback = null;
	this.loadCallbackFired = false;

	this.instance = null; 	// Browser window / iFrame Content Window
	this.dialog = null;		// jQuery dialog (null in Legacy Mode)

	// Close existing instance if opened with same identifier
	if (SMWindow.instances[this.id] !== undefined)
		SMWindow.instances[this.id].Close();

	// Add instance to static container which allows us to
	// resolve it using the static SMWindow.GetInstance(..) function
	SMWindow.instances[this.id] = this;

	// Functions

	/// <function container="client/SMWindow" name="GetId" access="public" returns="string">
	/// 	<description> Get instance ID </description>
	/// </function>
	this.GetId = function()
	{
		return this.id;
	}

	/// <function container="client/SMWindow" name="SetUrl" access="public">
	/// 	<description>
	/// 		Point browser window to specified URL.
	/// 		Specifying a URL to an image (jpg, jpeg, png, or gif) turns the SMWindow instance into
	/// 		an Image Preview Mode which makes the image stretch to the size of the window, and disable scrollbars.
	/// 	</description>
	/// 	<param name="url" type="string"> Valid URL </param>
	/// </function>
	this.SetUrl = function(url)
	{
		if (SMStringUtilities.EndsWith(url.toLowerCase(), ".jpg") === true || SMStringUtilities.EndsWith(url.toLowerCase(), ".jpeg") === true || SMStringUtilities.EndsWith(url.toLowerCase(), ".png") === true || SMStringUtilities.EndsWith(url.toLowerCase(), ".gif") === true)
		{
			// Make an image fit within window/dialog, disable scroll, and have image scale when resizing
			this.content = "<html><body style=\"margin: 0px; padding: 0px\"><img src=\"" + url + "\" style=\"width: 100%; height: 100%\"></body></html>";
			this.displayScrollBars = false;
		}
		else
		{
			this.url = url;
		}
	}

	/// <function container="client/SMWindow" name="SetContent" access="public">
	/// 	<description> Set browser window content </description>
	/// 	<param name="content" type="string"> Browser window content (can be HTML) </param>
	/// </function>
	this.SetContent = function(content)
	{
		this.content = content;
	}

	/// <function container="client/SMWindow" name="SetSize" access="public">
	/// 	<description> Set browser window dimensions (width and height) </description>
	/// 	<param name="width" type="integer"> Width in pixels </param>
	/// 	<param name="height" type="integer"> Height in pixels </param>
	/// </function>
	this.SetSize = function(width, height)
	{
		this.width = width;
		this.height = height;
	}

	/// <function container="client/SMWindow" name="SetDisplayToolBar" access="public">
	/// 	<description>
	/// 		Determines whether to display browser toolbar or not.
	/// 		Only supported in Legacy Mode, but not by all browsers.
	/// 		Relying on this feature is not recommended.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable toolbar, False not to </param>
	/// </function>
	this.SetDisplayToolBar = function(value)
	{
		this.displayToolBar = value;
	}

	/// <function container="client/SMWindow" name="SetDisplayLocation" access="public">
	/// 	<description>
	/// 		Determines whether to display browser location bar or not.
	/// 		Only supported in Legacy Mode, but some browsers will not
	/// 		allow you to disable this for security reasons.
	/// 		Relying on this feature is not recommended.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable location bar, False not to </param>
	/// </function>
	this.SetDisplayLocation = function(value)
	{
		this.displayLocation = value;
	}

	/// <function container="client/SMWindow" name="SetDisplayMenuBar" access="public">
	/// 	<description>
	/// 		Determines whether to display browser menu or not.
	/// 		Only supported in Legacy Mode, but not by all browsers.
	/// 		Relying on this feature is not recommended.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable menu, False not to </param>
	/// </function>
	this.SetDisplayMenuBar = function(value)
	{
		this.displayMenuBar = value;
	}

	/// <function container="client/SMWindow" name="SetDisplayStatusbar" access="public">
	/// 	<description>
	/// 		Determines whether to display browser status bar or not.
	/// 		Only supported in Legacy Mode, but some browsers will not
	/// 		allow you to disable this for security reasons.
	/// 		Relying on this feature is not recommended.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable status bar, False not to </param>
	/// </function>
	this.SetDisplayStatusbar = function(value)
	{
		this.displayStatusbar = value;
	}

	/// <function container="client/SMWindow" name="SetDisplayScrollBars" access="public">
	/// 	<description>
	/// 		Determines whether to display scrollbars or not.
	/// 		Some browsers will not allow you to disable this in Legacy Mode.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable scrollbars, False not to </param>
	/// </function>
	this.SetDisplayScrollBars = function(value)
	{
		this.displayScrollBars = value;
	}

	/// <function container="client/SMWindow" name="SetResizable" access="public">
	/// 	<description>
	/// 		Determines whether user will be able to change size of browser window or not.
	/// 		Some browsers will not allow you to disable this in Legacy Mode.
	/// 	</description>
	/// 	<param name="value" type="boolean"> Set True to enable resizing, False not to </param>
	/// </function>
	this.SetResizable = function(value)
	{
		this.resizable = value;
	}

	/// <function container="client/SMWindow" name="SetPosition" access="public">
	/// 	<description> Determines X,Y position of browser window when opened </description>
	/// 	<param name="pixelsLeft" type="integer"> Pixels from left </param>
	/// 	<param name="pixelsTop" type="integer"> Pixels from top </param>
	/// </function>
	this.SetPosition = function(pixelsLeft, pixelsTop)
	{
		this.positionLeft = pixelsLeft;
		this.positionTop = pixelsTop;

		this.centerWindow = false;
	}

	/// <function container="client/SMWindow" name="SetCenterWindow" access="public">
	/// 	<description> Determines whether to center browser window or not </description>
	/// 	<param name="value" type="boolean"> Set True to center window, False not to </param>
	/// </function>
	this.SetCenterWindow = function(value)
	{
		this.centerWindow = value;
	}

	/// <function container="client/SMWindow" name="SetModal" access="public">
	/// 	<description> Determines whether to make dialog modal or not - this is ignored in Legacy Mode </description>
	/// 	<param name="value" type="boolean"> Set True to make dialog modal, False not to </param>
	/// </function>
	this.SetModal = function(value)
	{
		this.preferModal = value;
	}

	/// <function container="client/SMWindow" name="SetOnShowCallback" access="public">
	/// 	<description> Set OnShow callback function to be executed when window is opened </description>
	/// 	<param name="cb" type="delegate"> Event handler function to execute - takes no arguments </param>
	/// </function>
	this.SetOnShowCallback = function(cb)
	{
		if (typeof(cb) !== "function")
			throw "Callback must be a function";

		this.showCallback = cb;
	}

	/// <function container="client/SMWindow" name="SetOnLoadCallback" access="public">
	/// 	<description>
	/// 		Set OnLoad callback function to be executed when page has been loaded.
	/// 		Notice that this does not work for foreign domains in Legacy Mode (Same-Origin Policy).
	/// 	</description>
	/// 	<param name="cb" type="delegate"> Event handler function to execute - takes no arguments </param>
	/// </function>
	this.SetOnLoadCallback = function(cb)
	{
		// Browser bug: Will not fire in IE (Legacy Mode) if page is being redirected (e.g. domain.com/demo => domain.com/demo/ or index.php => / or index.html => /).

		if (typeof(cb) !== "function")
			throw "Callback must be a function";

		this.loadCallback = cb;
	}

	/// <function container="client/SMWindow" name="SetOnCloseCallback" access="public">
	/// 	<description> Set OnClose callback function to be executed when window is closed </description>
	/// 	<param name="cb" type="delegate"> Event handler function to execute - takes no arguments </param>
	/// </function>
	this.SetOnCloseCallback = function(cb)
	{
		if (typeof(cb) !== "function")
			throw "Callback must be a function";

		this.closeCallback = cb;
	}

	/// <function container="client/SMWindow" name="Show" access="public">
	/// 	<description>
	/// 		Open browser window/dialog.
	///
	/// 		Pages opened within the window can use the following approach to access the parent window:
	/// 		var parentWindow = window.opener || window.top; // Browser window instance
	/// 		The page can also access its own instance of SMWindow like so:
	/// 		var smwin = parentWindow.SMWindow.GetInstance(window.name); // SMWindow instance
	/// 		This is only possible for pages loaded on the same domain due to the Same-Origin Policy.
	/// 	</description>
	/// 	<param name="legacyMode" type="boolean">
	/// 		Set True to force use of native browser window (likely to initially be blocked by browser) - not recommended.
	/// 		Set False to force use of Dialog Mode, in case Legacy Mode has been globally enabled, but is not desired for this specific instance.
	/// 		In general it is not recommended to force either Dialog Mode or Legacy Mode.
	/// 	</param>
	/// </function>
	this.Show = function(legacyMode)
	{
		// Close window if already opened (prevent same instance from being opened in both Dialog Mode and Legacy Mode
		// if switching legacyMode flag in Show(..) function), and makes sure window always open on top - if window has
		// already been opened in Legacy Mode, the window will simply just reload and not emerge on the screen (gain focus).
		this.Close();

		this.loadCallbackFired = false;
		var me = this; // Make "this" scope available to callbacks

		// ---------------------------------------
		// jQuery UI dialog
		// ---------------------------------------

		if (legacyMode === false || (legacyMode !== true && SMWindow.LegacyMode !== true)) // Use jQuery dialog unless Legacy Mode is enabled for this window or globally in config.xml.php
		{
			// Load jQuery

			SMResourceManager.Jquery.LoadInternal(function($)
			{
				// Create dialog

				var dialog = null;
				var iframe = null;

				dialog = $("<div></div>").html("<iframe name=\"" + me.id + "\" src=\"\" width=\"100%\" height=\"100%\" style=\"border: 0px\"" + ((me.displayScrollBars === false) ? " scrolling=\"no\"" : "") + " frameBorder=\"0\" allowTransparency=\"true\"></iframe>").dialog(
				{
					dialogClass: "Sitemagic SMWindow", /* jQuery UI theme uses the Sitemagic class as a scope for styling */
					autoOpen: false,
					modal: (me.preferModal === true),
					title: "",
					width: me.width,
					height: me.height + 28, // Add 28px which is the height of the title panel (not very flexibile - might be changed by custom CSS!)
					position: ((me.centerWindow === false) ? [me.positionLeft, me.positionTop] : null), // null = centered
					resizable: me.resizable,
					closeOnEscape: false,
					open: function()
					{
						if (me.showCallback !== null)
							fireEvent("OnShow", me.showCallback);
					},
					close: function(event, ui)
					{
						if (me.closeCallback !== null)
							fireEvent("OnClose", me.closeCallback);

						me.instance = null;
						me.dialog = null;
						dialog.dialog("destroy").remove();
					},
					resizeStart: function(ev, ui) { $(ev.target.parentNode).addClass("SMWindowTransparent"); },
					resizeStop: function(ev, ui) { $(ev.target.parentNode).removeClass("SMWindowTransparent"); },
					dragStart: function(ev, ui) { $(ev.target.parentNode).addClass("SMWindowTransparent"); },
					dragStop: function(ev, ui) { $(ev.target.parentNode).removeClass("SMWindowTransparent"); }
				});

				iframe = dialog[0].firstChild;
				me.instance = iframe.contentWindow;

				// Register OnLoad handler

				SMEventHandler.AddEventHandler(iframe, "load", function()
				{
					if (me.loadCallback !== null && me.loadCallbackFired === false)
					{
						me.loadCallbackFired = true; // Only fire first time a page is loaded, to mimic behaviour of browser pop up (not fired when navigating)
						fireEvent("OnLoad", me.loadCallback);
					}
				});

				// Load content

				if (me.content !== "")
				{
					iframe.contentWindow.document.write(me.content);
					iframe.contentWindow.document.close();
				}
				else
				{
					iframe.src = me.url;
				}

				// Open dialog

				dialog.dialog("open");
				me.dialog = dialog; // Make dialog available to SMWindow.Close()
			});

			return;
		}

		// ---------------------------------------
		// LEGACY MODE - Old browser pop up window
		// ---------------------------------------

		if (this.centerWindow === true)
		{
			this.positionLeft = Math.floor((screen.width / 2) - (this.width / 2));
			this.positionTop = Math.floor((screen.height / 2) - (this.height / 2));
		}

		var options = "width=" + this.width + ",height=" + this.height;
		options += ",toolbar=" + ((this.displayToolBar === true) ? "yes" : "no");
		options += ",location=" + ((this.displayLocation === true) ? "yes" : "no");
		options += ",menubar=" + ((this.displayMenuBar === true) ? "yes" : "no");
		options += ",status=" + ((this.displayStatusBar === true) ? "yes" : "no");
		options += ",scrollbars=" + ((this.displayScrollBars === true) ? "yes" : "no");
		options += ",resizable=" + ((this.resizable === true) ? "yes" : "no");
		options += ",left=" + this.positionLeft;
		options += ",top=" + this.positionTop;

		this.instance = window.open(((this.content === "") ? this.url : ""), this.id, options);

		if (this.instance === null || this.instance === undefined)
		{
			alert("Your browser prevented this website from opening a window - please enable pop up windows");
			this.instance = null;
			return;
		}

		if (this.content !== "")
		{
			this.instance.document.write(this.content);
			this.instance.document.close();
		}

		// Event handlers

		// Notice:
		// OnLoad is a bit tricky. For most browsers it fires, but not always for earlier versions of IE.
		// If static content is set, it doesn't fire, unless it contains references to external resources
		// such as images. That causes OnLoad to be triggered.
		// Therefore OnLoad is registered but also triggered manually in case the browser doesn't trigger it.
		// A simple boolean flag ensure that the event is only fired once.
		// It seems reasonable to let the OnLoad event fire immediately (when done manually) since the
		// document content has already been set above and the document instance closed.

		try // Browser might throw Access Denied error for foreign domains (e.g. Safari and IE), while other browsers just ignore this (e.g. Chrome)
		{
			SMEventHandler.AddEventHandler(this.instance, "load", function()
			{
				if (me.loadCallback !== null && me.loadCallbackFired === false)
				{
					me.loadCallbackFired = true;
					fireEvent("OnLoad", me.loadCallback);
				}
			});
		}
		catch (err)
		{
			if (window.console)
			{
				console.log(err.message);
				console.log(err.stack);
				console.log(err);

				if (this.loadCallback !== null)
					console.log("Unable to register OnLoad event handler - access denied - most likely due to Same-Origin Policy");
			}
		}

		// OnLoad not always fired when static content is set (see explaination above) - make sure it fires by doing so manually
		if (this.content !== "" && this.loadCallback !== null && this.loadCallbackFired === false)
		{
			this.loadCallbackFired = true;
			fireEvent("OnLoad", this.loadCallback);
		}

		// Using Interval to support OnClose event - OnBeforeUnload only works if page is loaded from the same domain (Same-Origin Policy)
		var iId = null;
		iId = setInterval(function()
		{
			if (me.instance.closed === true) /*me.instance === null*/
			{
				// Known limitation in JS: Callback handlers set from within dialog window will not work in Legacy Mode. Example:
				// (window.opener || window.top).SMWindow.GetInstance(window.name).SetOnCloseCallback(function() { /* ... */ });
				// Callback must be defined in window which created dialog window. This is most likely due to the
				// fact that the callback's execution context (scope/closure) is destroyed once the window no longer exists,
				// which prevents the callback from working.
				// Registering the callback on the parent window is not sufficient either since the execution context still belongs
				// to the dialog window. Therefore the example below won't work:
				// var parentWin = (window.opener || window.top);
				// parentWin.MyCallback = function() { /* ... */ };
				// parentWin.SMWindow.GetInstance(window.name).SetOnCloseCallback(parentWin.MyCallback);
				// BUT, it IS possible to have the parent window register a callback defined by the dialog window by using the
				// eval() function of the parent window (at least if the two pages are hosted on the same domain (Same-Origin)).
				// Example:
				// var parentWin = (window.opener || window.top);
				// parentWin.eval("SMWindow.GetInstance('" + window.name + "').SetOnCloseCallback(function() { location.href = location.href; });");
				// It's a bit messy, but the best we can do to work around this limitation in JavaScript. The problem is not related to SMWindow.
				// If the solution above is not acceptable, avoid using Legacy Mode - use Dialog Mode instead!

				if (me.closeCallback !== null)
					fireEvent("OnClose", me.closeCallback);

				me.instance = null;
				clearInterval(iId);
			}
		}, 200);

		if (this.showCallback !== null)
			fireEvent("OnShow", this.showCallback);
	}

	/// <function container="client/SMWindow" name="Close" access="public">
	/// 	<description>
	/// 		Close window/dialog.
	/// 		This is also possible from within the window using the following approach:
	/// 		(window.opener || window.top).SMWindow.GetInstance(window.name).Close();
	/// 	</description>
	/// </function>
	this.Close = function()
	{
		// NOTICE: Do not pass Close function directly as callback to e.g. setTimeout as it changes 'this'
		// to the browser window instance (Legacy Mode) or the iFrame content window instance (Dialog Mode).
		// Instead wrap it in an anonymous function like so: setTimeout(function() { smwin.Close(); }, 2000);

		if (this.instance === null)
			return;

		if (this.dialog !== null)
		{
			this.dialog.dialog("close");
		}
		else
		{
			this.instance.close();
		}
	}

	/// <function container="client/SMWindow" name="GetInstance" access="public" returns="window">
	/// 	<description>
	/// 		Returns internal browser window instance if open, otherwise Null.
	/// 		The instance provides access to e.g. the window document:
	/// 		var doc = smwin.GetInstance().document;
	/// 		Notice: This is only possible with pages loaded from the same domain (Same-Origin Policy).
	/// 		Also be aware that manipulating the document instance should not take place until during
	/// 		or after OnLoad (see SetOnLoadCallback(..)).
	/// 	</description>
	/// </function>
	this.GetInstance = function()
	{
		return this.instance; // Do NOT do console.log(smwin.GetInstance())! It sometimes crashes Chrome when debugging
	}

	function fireEvent(eventName, cb) // Fires event with error handling
	{
		try
		{
			cb();
		}
		catch (err)
		{
			if (window.console)
			{
				console.log("Error occurred executing " + eventName + " event handler");
				console.log(err.message);
				console.log(err.stack);
				console.log(err);
			}
		}
	}
}
SMWindow.instances = {};

/// <function container="client/SMWindow" name="GetInstance" access="public" static="true" returns="SMWindow">
/// 	<description> Returns SMWindow instance by ID if found, otherwise Null </description>
/// 	<param name="id" type="string"> SMWindow instance ID </param>
/// </function>
SMWindow.GetInstance = function(id)
{
	return ((SMWindow.instances[id] !== undefined) ? SMWindow.instances[id] : null);
}

// SMHttpRequest

/// <container name="client/SMHttpRequest">
/// 	Asynchronous HTTP request functionality (AJAX).
///
/// 	// Example code
///
/// 	var http = new SMHttpRequest(&quot;CreateUser.php&quot;, true);
///
/// 	http.SetData(&quot;username=Jack&amp;password=Secret&quot;);
/// 	http.SetStateListener(function()
/// 	{
/// 		&#160;&#160;&#160;&#160; if (this.GetCurrentState() === 4 &amp;&amp; this.GetHttpStatus() === 200)
/// 		&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; alert(&quot;User created - server said: &quot; + this.GetResponseText());
/// 	});
///
/// 	http.Start();
/// </container>

/// <function container="client/SMHttpRequest" name="SMHttpRequest" access="public">
/// 	<description> Constructor - creates instance of SMHttpRequest </description>
/// 	<param name="url" type="string"> URL to request </param>
/// 	<param name="async" type="boolean"> Value indicating whether to perform request asynchronous or not </param>
/// </function>
function SMHttpRequest(url, async) // url, true|false
{
	this.url = url;
	this.async = async;

	this.httpRequest = getHttpRequestObject();
	this.customHeaders = {};
	this.data = null;

	/// <function container="client/SMHttpRequest" name="AddHeader" access="public">
	/// 	<description>
	/// 		Add header to request.
	/// 		Manually adding headers will prevent the SMHttpRequest instance from
	/// 		manipulating headers. This is done to provide full control with the headers.
	/// 		You will in this case most likely need to add the following header for a POST request:
	/// 		Content-type : application/x-www-form-urlencoded
	/// 	</description>
	/// 	<param name="key" type="string"> Header key </param>
	/// 	<param name="value" type="string"> Header value </param>
	/// </function>
	this.AddHeader = function(key, value)
	{
		this.customHeaders[key] = value;
	}

	/// <function container="client/SMHttpRequest" name="SetData" access="public">
	/// 	<description> Set data to post - this will change the request method from GET to POST </description>
	/// 	<param name="data" type="string"> Data to send </param>
	/// </function>
	this.SetData = function(data)
	{
		this.data = data;
	}

	/// <function container="client/SMHttpRequest" name="Start" access="public">
	/// 	<description> Invoke request </description>
	/// </function>
	this.Start = function()
	{
		var method = ((this.data === null || this.data === "") ? "GET" : "POST");
		this.httpRequest.open(method, this.url, this.async);

		var usingCustomHeaders = false;
		for (var header in this.customHeaders)
		{
			this.httpRequest.setRequestHeader(header, this.customHeaders[header]);
			usingCustomHeaders = true;
		}

		if (method === "POST" && usingCustomHeaders === false)
			this.httpRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

		this.httpRequest.send(this.data);
	}

	/// <function container="client/SMHttpRequest" name="GetResponseXml" access="public" returns="Document">
	/// 	<description>
	/// 		Returns result from request as XML or HTML document.
	/// 		Return value will only be as expected if GetCurrentState() returns a value of 4
	/// 		(request done) and GetHttpStatus() returns a value of 200 (request successful).
	/// 	</description>
	/// </function>
	this.GetResponseXml = function()
	{
		return this.httpRequest.responseXML;
	}

	/// <function container="client/SMHttpRequest" name="GetResponseText" access="public" returns="string">
	/// 	<description>
	/// 		Returns text result from request.
	/// 		Return value will only be as expected if GetCurrentState() returns a value of 4
	/// 		(request done) and GetHttpStatus() returns a value of 200 (request successful).
	/// 	</description>
	/// </function>
	this.GetResponseText = function()
	{
		return this.httpRequest.responseText;
	}

	/// <function container="client/SMHttpRequest" name="GetResponseJson" access="public" returns="object">
	/// 	<description>
	/// 		Returns result from request as JSON object, Null if no response was returned.
	/// 		Return value will only be as expected if GetCurrentState() returns a value of 4
	/// 		(request done) and GetHttpStatus() returns a value of 200 (request successful).
	/// 	</description>
	/// </function>
	this.GetResponseJson = function()
	{
		return ((this.httpRequest.responseText !== "") ? JSON.parse(this.httpRequest.responseText) : null);
	}

	/// <function container="client/SMHttpRequest" name="SetStateListener" access="public">
	/// 	<description>
	/// 		Set delegate to invoke when request state is changed.
	/// 		Use GetCurrentState() to read the state at the given time.
	/// 	</description>
	/// 	<param name="func" type="delegate"> JavaScript function invoked when state changes </param>
	/// </function>
	this.SetStateListener = function(func)
	{
		this.httpRequest.onreadystatechange = func;
	}

	/// <function container="client/SMHttpRequest" name="GetCurrentState" access="public" returns="integer">
	/// 	<description>
	/// 		Get current request state.
	/// 		0 = Unsent
	/// 		1 = Opened
	/// 		2 = Headers received
	/// 		3 = Loading
	/// 		4 = Done (response is ready for processing)
	/// 	</description>
	/// </function>
	this.GetCurrentState = function() // 0 = unsent, 1 = opened, 2 = headers received, 3 = loading, 4 = done
	{
		return this.httpRequest.readyState;
	}

	/// <function container="client/SMHttpRequest" name="GetHttpStatus" access="public" returns="integer">
	/// 	<description>
	/// 		Returns HTTP status. Common return values are:
	/// 		200 = OK (successful request)
	/// 		304 = Forbidden (access denied)
	/// 		404 = Not found
	/// 		408 = Request time out
	/// 		500 = Internal server error
	/// 		503 = Service unavailable
	/// 	</description>
	/// </function>
	this.GetHttpStatus = function()
	{
		return this.httpRequest.status;
	}

	function getHttpRequestObject()
	{
		if (window.XMLHttpRequest) // Firefox, IE7, Chrome, Opera, Safari
			return new XMLHttpRequest();
		else if (window.ActiveXObject) // IE5, IE6
			return new ActiveXObject("Microsoft.XMLHTTP");
		else
		{
			//alert("Http Request object not supported");
			return null;
		}
	}
}

// SMBrowser

/// <container name="client/SMBrowser">
/// 	Provides access to various browser information.
///
/// 	// Example code
///
/// 	var browserName = SMBrowser.GetBrowser();
/// 	var browserVersion = SMBrowser.GetVersion();
/// 	var browserLanguage = SMBrowser.GetLanguage();
///
/// 	if (browserName === &quot;MSIE&quot; &amp;&amp; browserVersion &lt; 7)
/// 	{
/// 		&#160;&#160;&#160;&#160; if (browserLanguage === &quot;da&quot;)
/// 		&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; alert(&quot;Opgrader venligst til IE7 eller nyere&quot;);
/// 		&#160;&#160;&#160;&#160; else
/// 		&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; alert(&quot;Please upgrade to IE7 or newer&quot;);
/// 	}
/// </container>
function SMBrowser()
{
}

/// <function container="client/SMBrowser" name="GetBrowser" access="public" static="true" returns="string">
/// 	<description> Returns browser name. Possible values are: Chrome, Safari, MSIE, Firefox, Opera, Unknown </description>
/// </function>
SMBrowser.GetBrowser = function()
{
	var agent = navigator.userAgent;

	if (agent.indexOf("Chrome") > -1)
		return "Chrome";
	if (agent.indexOf("Safari") > -1)
		return "Safari";
	if (agent.indexOf("MSIE") > -1 || agent.indexOf("Trident") > -1)
		return "MSIE";
	if (agent.indexOf("Firefox") > -1)
		return "Firefox";
	if (agent.indexOf("Opera") > -1)
		return "Opera";

	return "Unknown";
}

/// <function container="client/SMBrowser" name="GetVersion" access="public" static="true" returns="integer">
/// 	<description> Returns major version number for known browsers, -1 for unknown browsers </description>
/// </function>
SMBrowser.GetVersion = function()
{
	var start = 0;
	var end = 0;
	var agent = navigator.userAgent;

	if (SMBrowser.GetBrowser() === "Chrome")
	{
		start = agent.indexOf("Chrome/");
		start = (start !== -1 ? start + 7 : 0);
		end = agent.indexOf(".", start);
		end = (end !== -1 ? end : 0);
	}
	if (SMBrowser.GetBrowser() === "Safari")
	{
		start = agent.indexOf("Version/");
		start = (start !== -1 ? start + 8 : 0);
		end = agent.indexOf(".", start);
		end = (end !== -1 ? end : 0);
	}
	if (SMBrowser.GetBrowser() === "MSIE")
	{
		if (agent.indexOf("MSIE") > -1)
		{
			start = agent.indexOf("MSIE ");
			start = (start !== -1 ? start + 5 : 0);
			end = agent.indexOf(".", start);
			end = (end !== -1 ? end : 0);
		}
		else if (agent.indexOf("Trident") > -1) // IE11+
		{
			start = agent.indexOf("rv:");
			start = (start !== -1 ? start + 3 : 0);
			end = agent.indexOf(".", start);
			end = (end !== -1 ? end : 0);
		}
	}
	if (SMBrowser.GetBrowser() === "Firefox")
	{
		start = agent.indexOf("Firefox/");
		start = (start !== -1 ? start + 8 : 0);
		end = agent.indexOf(".", start);
		end = (end !== -1 ? end : 0);
	}
	if (SMBrowser.GetBrowser() === "Opera")
	{
		start = agent.indexOf("Version/");
		start = (start !== -1 ? start + 8 : -1);

		if (start === -1)
		{
			start = agent.indexOf("Opera/");
			start = (start !== -1 ? start + 6 : -1);
		}

		if (start === -1)
		{
			start = agent.indexOf("Opera ");
			start = (start !== -1 ? start + 6 : -1);
		}

		end = agent.indexOf(".", start);
		end = (end !== -1 ? end : 0);
	}

	if (start !== 0 && start !== 0)
		return parseInt(agent.substring(start, end));

	return -1;
}

/// <function container="client/SMBrowser" name="GetLanguage" access="public" static="true" returns="string">
/// 	<description> Returns browser language - e.g. &quot;da&quot; (Danish), &quot;en&quot; (English) etc. </description>
/// </function>
SMBrowser.GetLanguage = function()
{
	var lang = null;

	if (navigator.language)
		lang = navigator.language.toLowerCase();
	else if (navigator.browserLanguage)
		lang = navigator.browserLanguage.toLowerCase();

	if (lang === null || lang === "")
		return "en";

	if (lang.length === 2)
		return lang;

	if (lang.length === 5)
		return lang.substring(0, 2);

	return "en";
}

/// <function container="client/SMBrowser" name="GetPageWidth" access="public" static="true" returns="integer">
/// 	<description> Returns page width in pixels on succes, -1 on failure </description>
/// </function>
SMBrowser.GetPageWidth = function()
{
	var w = -1;

	if (window.innerWidth) // W3C
		w = window.innerWidth;
	else if (document.documentElement && document.documentElement.clientWidth) // IE 6-8 (not quirks mode)
		w = document.documentElement.clientWidth;

	return w;
}

/// <function container="client/SMBrowser" name="GetPageHeight" access="public" static="true" returns="integer">
/// 	<description> Returns page height in pixels on succes, -1 on failure </description>
/// </function>
SMBrowser.GetPageHeight = function()
{
	var h = -1;

	if (window.innerHeight) // W3C
		h = window.innerHeight;
	else if (document.documentElement && document.documentElement.clientHeight) // IE 6-8 (not quirks mode)
		h = document.documentElement.clientHeight;

	return h;
}

/// <function container="client/SMBrowser" name="GetScreenWidth" access="public" static="true" returns="integer">
/// 	<description> Get screen width </description>
/// 	<param name="onlyAvailable" type="boolean" default="false"> Set True to return only available space </param>
/// </function>
SMBrowser.GetScreenWidth = function(onlyAvailable)
{
	if (onlyAvailable === true)
		return window.screen.availWidth;

	return window.screen.width;
}

/// <function container="client/SMBrowser" name="GetScreenHeight" access="public" static="true" returns="integer">
/// 	<description> Get screen height </description>
/// 	<param name="onlyAvailable" type="boolean" default="false"> Set True to return only available space </param>
/// </function>
SMBrowser.GetScreenHeight = function(onlyAvailable)
{
	if (onlyAvailable === true)
		return window.screen.availHeight;

	return window.screen.height;
}

/// <function container="client/SMBrowser" name="CssSupported" access="public" static="true" returns="boolean">
/// 	<description>
/// 		Check whether specified CSS property and CSS value is supported by browser.
/// 		Returns True if supported, otherwise False.
/// 	</description>
/// 	<param name="property" type="string"> CSS property name </param>
/// 	<param name="value" type="string"> CSS property value </param>
/// </function>
SMBrowser.CssSupported = function(property, value)
{
	var div = document.createElement("div");
	var cssText = div.style.cssText;

	try
	{
		div.style[property] = value;
	}
	catch (err)
	{
		return false;
	}

	return (cssText !== div.style.cssText);
}

// SMResourceManager

// The order of processing scripts and stylesheets:
// http://www.html5rocks.com/en/tutorials/internals/howbrowserswork/#The_order_of_processing_scripts_and_style_sheets

/// <container name="client/SMResourceManager">
/// 	The Resource Manager is a useful mechanism for loading styleheets and JavaScript on demand in a non blocking manner.
/// </container>
function SMResourceManager()
{
}
SMResourceManager.Jquery = {};
SMResourceManager.Jquery.Loading = false;
SMResourceManager.Jquery.Loaded = {};
SMResourceManager.Jquery.Internal = null;

/// <function container="client/SMResourceManager" name="LoadScript" access="public" static="true">
/// 	<description>
/// 		Load client script on demand in a non-blocking manner.
///
/// 		// Example of loading a JavaScript file
///
/// 		SMResourceManager.LoadScript(&quot;extensions/test/test.js&quot;, function(src)
/// 		{
/// 			&#160;&#160;&#160;&#160; alert(&quot;JavaScript &quot; + src + &quot; loaded and ready to be used!&quot;);
/// 		});
/// 	</description>
/// 	<param name="src" type="string"> Script source (path or URL) </param>
/// 	<param name="callback" type="delegate" default="undefined">
/// 		Callback function fired when script loading is complete - takes the script source requested as an argument.
/// 		Be aware that a load error will also trigger the callback to make sure control is always returned.
/// 		Consider using feature detection within callback function for super reliable execution - example:
/// 		if (expectedObjectOrFunction) { /* Successfully loaded, continue.. */ }
/// 	</param>
/// </function>
SMResourceManager.LoadScript = function(src, callback)
{
	var script = document.createElement("script");
	script.type = "text/javascript";

	if (callback !== undefined && (SMBrowser.GetBrowser() !== "MSIE" || (SMBrowser.GetBrowser() === "MSIE" && SMBrowser.GetVersion() >= 9)))
	{
		script.onload = function() { callback(src); };

		// Terrible, but we need same behaviour for all browsers, and IE8 (and below) does not distinguish between success and failure.
		// Also, we need to make sure control is returned no matter what - just like using ordinary <script src=".."> elements which
		// doesn't halt execution on 404 or syntax errors.
		script.onerror = function() { callback(src); };
	}
	else if (callback !== undefined && SMBrowser.GetBrowser() === "MSIE" && SMBrowser.GetVersion() <= 8)
	{
		script.onreadystatechange = function()
		{
			if (this.readyState === "complete" || this.readyState === "loaded") // loaded = initial load, complete = from cache
				callback(src);
		}
	}

	script.src = src;
	document.getElementsByTagName("head")[0].appendChild(script);
}

/// <function container="client/SMResourceManager" name="LoadScripts" access="public" static="true">
/// 	<description>
/// 		Chain load multiple client scripts on demand in a non-blocking manner.
///
/// 		// Example of loading multiple JavaScript files in serial:
///
/// 		SMResourceManager.LoadScripts(
/// 		[
/// 			&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160; &#160;&#160;&#160;&#160; source: &quot;extensions/test/menu.js&quot;,
/// 			&#160;&#160;&#160;&#160; &#160;&#160;&#160;&#160; loaded: function(cfg) { alert(&quot;JavaScript &quot; + cfg.source + &quot; loaded&quot;); }
/// 			&#160;&#160;&#160;&#160; },
/// 			&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160; &#160;&#160;&#160;&#160; source: &quot;http://cdn.domain.com/chat.js&quot;,
/// 			&#160;&#160;&#160;&#160; &#160;&#160;&#160;&#160; loaded: function(cfg) { alert(&quot;JavaScript &quot; + cfg.source + &quot; loaded&quot;); }
/// 			&#160;&#160;&#160;&#160; }
/// 		],
/// 		function(cfgs)
/// 		{
/// 			&#160;&#160;&#160;&#160; alert(&quot;All files loaded&quot;);
/// 		});
///
/// 		First argument is an array of script configurations:
/// 		source:string (required): Script source (path or URL)
/// 		loaded:function (optional): Callback function to execute when file has loaded (takes file configuration as argument)
/// 		Be aware that loaded callback is invoked even if a load error occures, to make sure control is returned to your code.
///
/// 		Second argument is the callback function fired when all files have finished loading - takes configuration array as argument.
/// 		This too may be invoked even if a load error occured, to make sure control is returned to your code.
///
/// 		Consider using feature detection within callback functions for super reliable execution - example:
/// 		if (expectedObjectOrFunction) { /* Successfully loaded, continue.. */ }
/// 	</description>
/// 	<param name="cfg" type="array"> Configuration array (see function description for details) </param>
/// 	<param name="callback" type="delegate" default="undefined"> Callback function fired when all scripts have finished loading (see function description for details) </param>
/// </function>
SMResourceManager.LoadScripts = function(cfg, callback, skipValidation)
{
	// Verify configuration

	if (skipValidation !== true)
	{
		for (var i = 0 ; i < cfg.length ; i++)
			if (cfg[i].source === undefined)
				throw "Unable to load script with source property undefined";
	}

	// Find next unhandled script to load

	var toLoad = null;

	for (var i = 0 ; i < cfg.length ; i++)
	{
		if (cfg[i].handled !== true)
		{
			toLoad = cfg[i];
			break;
		}
	}

	// Break out if no more scripts need handling

	if (toLoad === null)
	{
		if (callback !== undefined)
			callback(cfg);

		return;
	}

	// Load script

	toLoad.handled = true;

	SMResourceManager.LoadScript(toLoad.source, function()
	{
		if (toLoad.loaded !== undefined)
		{
			try // Use try/catch to prevent buggy code from stopping the chain
			{
				toLoad.loaded(toLoad);
			}
			catch (err)
			{
				if (window.console)
				{
					console.log(err.message);
					console.log(err.stack);
					console.log(err);
				}
			}
		}

		// Continue chain - load next script from configuration

		SMResourceManager.LoadScripts(cfg, callback, true);
	});
}

/// <function container="client/SMResourceManager" name="LoadStyleSheet" access="public" static="true">
/// 	<description>
/// 		Load CSS stylesheet on demand in a non-blocking manner.
/// 		It is recommended to load stylesheets before rendering items using
/// 		the CSS classes to avoid FOUC (Flash Of Unstyled Content).
///
/// 		// Example of loading a CSS file
///
/// 		SMResourceManager.LoadStyleSheet(&quot;extensions/test/layout.css&quot;, function(src)
/// 		{
/// 			&#160;&#160;&#160;&#160; alert(&quot;CSS file &quot; + src + &quot; loaded!&quot;);
/// 		});
/// 	</description>
/// 	<param name="src" type="string"> CSS file source (path or URL) </param>
/// 	<param name="callback" type="delegate" default="undefined">
/// 		Callback function fired when CSS file loading is complete - takes the file source requested as an argument.
/// 		Be aware that a load error will also trigger the callback to make sure control is always returned.
/// 	</param>
/// </function>
SMResourceManager.LoadStyleSheet = function(src, callback)
{
	// OnError event could likely be supported using the following
	// lines of code which allows us to check the number of loaded CSS rules:
	// W3C browsers: var success = (cssLinkNode.sheet.cssRules.length > 0);
	// Internet Explorer: var success = (cssLinkNode.styleSheet.rules.length > 0);
	// For consistency this approach is not currently being used - we need same
	// behaviour for both LoadStyleSheet(..), and LoadScript(..) which doesn't support OnError.

	var link = document.createElement("link");
	link.type = "text/css";
	link.rel = "stylesheet";

	if (callback !== undefined && (SMBrowser.GetBrowser() !== "MSIE" || (SMBrowser.GetBrowser() === "MSIE" && SMBrowser.GetVersion() >= 9)))
	{
		link.onload = function() { callback(src); };
		link.onerror = function() { callback(src); }; // Same behaviour as LoadScript(..)
	}
	else if (callback !== undefined && SMBrowser.GetBrowser() === "MSIE" && SMBrowser.GetVersion() <= 8)
	{
		link.onreadystatechange = function()
		{
			if (this.readyState === "complete" || this.readyState === "loaded") // loaded = initial load, complete = from cache
				callback(src);
		}
	}

	link.href = src;
	document.getElementsByTagName("head")[0].appendChild(link);
}

/// <function container="client/SMResourceManager" name="LoadStyleSheets" access="public" static="true">
/// 	<description>
/// 		Load multiple stylesheets in parrallel in a non-blocking manner.
///
/// 		// Example of loading multiple CSS files:
///
/// 		SMResourceManager.LoadStyleSheets(
/// 		[
/// 			&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160; &#160;&#160;&#160;&#160; source: &quot;extensions/test/menu.css&quot;,
/// 			&#160;&#160;&#160;&#160; &#160;&#160;&#160;&#160; loaded: function(cfg) { alert(&quot;Stylesheet &quot; + cfg.source + &quot; loaded&quot;); }
/// 			&#160;&#160;&#160;&#160; },
/// 			&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160; &#160;&#160;&#160;&#160; source: &quot;http://cdn.domain.com/chat.css&quot;,
/// 			&#160;&#160;&#160;&#160; &#160;&#160;&#160;&#160; loaded: function(cfg) { alert(&quot;Stylesheet &quot; + cfg.source + &quot; loaded&quot;); }
/// 			&#160;&#160;&#160;&#160; }
/// 		],
/// 		function(cfgs)
/// 		{
/// 			&#160;&#160;&#160;&#160; alert(&quot;All stylesheets loaded&quot;);
/// 		});
///
/// 		First argument is an array of stylesheet configurations:
/// 		source:string (required): Stylesheet source (path or URL)
/// 		loaded:function (optional): Callback function to execute when stylesheet has loaded (takes stylesheet configuration as argument)
/// 		Be aware that loaded callback is invoked even if a load error occures, to make sure control is returned to your code.
///
/// 		Second argument is the callback function fired when all stylesheets have finished loading - takes configuration array as argument.
/// 		This too may be invoked even if a load error occured, to make sure control is returned to your code.
/// 	</description>
/// 	<param name="cfg" type="array"> Configuration array (see function description for details) </param>
/// 	<param name="callback" type="delegate" default="undefined"> Callback function fired when all stylesheets have finished loading (see function description for details) </param>
/// </function>
SMResourceManager.LoadStyleSheets = function(cfg, callback)
{
	// Verify configuration

	for (var i = 0 ; i < cfg.length ; i++)
		if (cfg[i].source === undefined)
			throw "Unable to load stylesheet with source property undefined";

	// Invoke callback if nothing to load

	if (cfg.length === 0)
	{
		if (callback !== undefined)
			callback(cfg);

		return;
	}

	// Batch load all stylesheets

	for (var i = 0 ; i < cfg.length ; i++)
	{
		// Load stylesheet

		SMResourceManager.LoadStyleSheet(cfg[i].source, function(src)
		{
			// Fire stylesheet callback function when completed

			for (var j = 0 ; j < cfg.length ; j++)
			{
				if (cfg[j].source === src)
				{
					cfg[j].handled = true;

					if (cfg[j].loaded !== undefined)
					{
						try // Use try/catch to make sure a buggy callback function does not prevent "all completed" callback to be reached
						{
							cfg[j].loaded(cfg[j]);
						}
						catch (err)
						{
							if (window.console)
							{
								console.log(err.message);
								console.log(err.stack);
								console.log(err);
							}
						}
					}

					break;
				}
			}

			// Fire "all completed" callback if all stylesheets have finished loading

			for (var j = 0 ; j < cfg.length ; j++)
			{
				if (cfg[j].handled !== true)
					return;
			}

			if (callback !== undefined)
				callback(cfg);
		});
	}
}

/// <function container="client/SMResourceManager" name="Jquery.Load" access="public" static="true">
/// 	<description>
/// 		Load new jQuery instance and optionally jQuery UI, plugins, and styles on demand in a non-blocking manner.
///
/// 		Consider using the simpler SMResourceManager.Jquery.Run(version, callback)
/// 		function to execute pure jQuery code if plugins are not required. It automatically
/// 		loads jQuery if not already loaded, and keeps it in a locale cache until the
/// 		page is reloaded, for better performance.
///
/// 		Sitemagic CMS allows for multiple instances and multiple versions of jQuery
/// 		to run at the same time, completely isolated from one another. This approach ensure that jQuery
/// 		based extensions do not break when new versions of Sitemagic CMS or jQuery
/// 		is released - they keep running on the version of jQuery they were designed for, and
/// 		without interfearing with one another.
///
/// 		jQuery is by default loaded from Google CDN which means outstanding performance and
/// 		improved caching capabilities. It also automatically ensure access to new versions
/// 		of jQuery when released.
///
/// 		Both jQuery and plugins are chain-loaded in a non-blocking manner which
/// 		improves performance and responsiveness.
///
/// 		Loading an alternative version of jQuery (e.g. a locale copy or from an alternative CDN) is also supported.
///
/// 		Below is an example of jQuery being loaded from CDN including a locale plugin and the popular jQuery UI plugin:
///
/// 		SMResourceManager.Jquery.Load(
/// 		{
/// 			&#160;&#160;&#160;&#160; version: &quot;1.10.2&quot;,
/// 			&#160;&#160;&#160;&#160; uiversion: &quot;1.10.3&quot;,
/// 			&#160;&#160;&#160;&#160; loaded: function($, cfg)
/// 			&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; alert(&quot;jQuery version &quot; + $.fn.jquery + &quot; loaded!&quot;);
/// 			&#160;&#160;&#160;&#160; },
/// 			&#160;&#160;&#160;&#160; styles:
/// 			&#160;&#160;&#160;&#160; [
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; source: &quot;extensions/test/tabs.jquery.css&quot;,
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; loaded: function($, style) { alert(&quot;CSS file &quot; + style.source + &quot; loaded!&quot;); }
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; },
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; source: &quot;extensions/test/ui/jquery-ui-1.10.3.custom.css&quot;,
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; loaded: function($, style) { alert(&quot;CSS file &quot; + style.source + &quot; loaded!&quot;); }
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; }
/// 			&#160;&#160;&#160;&#160; ],
/// 			&#160;&#160;&#160;&#160; plugins:
/// 			&#160;&#160;&#160;&#160; [
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; source: &quot;extensions/test/tabs.jquery.js&quot;,
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; loaded: function($, plugin) { alert(&quot;Plugin &quot; + plugin.source + &quot; loaded!&quot;); }
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; }
/// 			&#160;&#160;&#160;&#160; ],
/// 			&#160;&#160;&#160;&#160; complete: function($, cfg)
/// 			&#160;&#160;&#160;&#160; {
/// 			&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160; alert(&quot;jQuery version &quot; + $.fn.jquery + &quot;, jQuery UI, and all plugins loaded!&quot;);
/// 			&#160;&#160;&#160;&#160; }
/// 		});
///
/// 		Supported configuration properties:
///
/// 		version:string (required unless source property is defined):
/// 		Load specified version of jQuery - using the most recent version of the jQuery 1.x branch is recommended to ensure support for legacy browsers.
/// 		A list of available versions is available here: https://developers.google.com/speed/libraries/devguide?csw=1#jquery
/// 		Example value: &quot;1.10.2&quot;
///
/// 		uiversion:string (optional):
/// 		Load specified version of jQuery UI (make sure it is compatible with specified version of jQuery).
/// 		A list of available versions is available here: https://developers.google.com/speed/libraries/devguide?csw=1#jquery-ui
/// 		jQuery UI will be available when the complete callback handler is invoked.
/// 		Example value: &quot;1.10.3&quot;
/// 		IMPORTANT: jQuery UI requires a CSS Theme to be loaded.
/// 		All themes define the same set of CSS rules which will cause conflicts if multiple themes are loaded. To avoid this
/// 		create a custom theme using the Theme Roller at http://jqueryui.com/themeroller/
/// 		When downloading, make sure to specify a unique CSS Scope (e.g. &quot;.MyTheme&quot;) to prevent conflicts with other themes.
/// 		Load your unique theme using the styles property.
/// 		Now make sure to wrap all jQuery UI elements in a container to create the CSS scope like so:
/// 		&lt;div class=&quot;MyTheme&quot;&gt;&lt;input name=&quot;date&quot; id=&quot;jQueryDatePicker&quot;&gt;&lt;/div&gt;
/// 		This can also be achieved using jQuery: $(&quot;#jQueryDatePicker&quot;).wrap(&quot;&lt;div class='MyTheme'&gt;&lt;div&gt;&quot;);
///
/// 		source:string (required unless version property is defined):
/// 		Alternative jQuery source (e.g. a locale copy or jQuery from an alternative CDN).
/// 		Property is ignored if version property is set.
/// 		Example value: &quot;extensions/test/jquery-1.10.2.min.js&quot;
///
/// 		loaded:function (optional)
/// 		Callback function to execute when jQuery is done loading (accept two arguments - jQuery instance and configuration object).
///
/// 		complete:function (optional)
/// 		Callback function to execute when jQuery, jQuery UI, styles, and all plugins are done loading (accept two arguments - jQuery instance and configuration object).
///
/// 		styles (optional):
/// 		Array of style configuration objects representing CSS files to load. Each style configuration contains the following properties:
/// 		source (required): Path or URL to CSS file.
/// 		loaded (optional): Callback function to be invoked when CSS file is done loading (accept two arguments - jQuery instance and the style configuration object).
/// 		Be aware that loaded callback is invoked even if a load error occures, to make sure control is returned to your code.
/// 		StyleSheets are loaded simultaneously. Plugins will not be loaded until after CSS files have completed loading in order to avoid FOUC (Flash Of Unstyled Content).
///
/// 		plugins (optional):
/// 		Array of plugin configuration objects representing jQuery plugins to load. Each plugin configuration contains the following properties:
/// 		source (required): Path or URL to jQuery plugin.
/// 		loaded (optional): Callback function to be invoked when jQuery plugin is done loading (accept two arguments - jQuery instance and the plugin configuration object).
/// 		Be aware that loaded callback is invoked even if a load error occures, to make sure control is returned to your code. For super reliable code execution use feature
/// 		detection to ensure plugin (and dependencies) was properly loaded. Example: if ($.PluginName || $.fn.PluginName) { /* Plugin loaded - do your magic */ }
/// 		Plugins are chain loaded in the order defined to make sure dependencies are loaded first.
///
/// 		If a plugin is not working:
///
/// 		If you find that a jQuery plugin does not work when loaded using SMResourceManager, it's most likely because the plugin is poorly written.
/// 		In most cases a plugin breaks because it refers to window.jQuery or window.$ rather than the instance passed to the anonymous plugin function.
/// 		Example of simple jQuery plugin wrapper:
///
/// 		(function($)
/// 		{
/// 		&#160;&#160;&#160;&#160; // All of these lines are wrong and unfortunately common mistakes:
/// 		&#160;&#160;&#160;&#160; console.log(&quot;jQuery version &quot; + window.jQuery.fn.jquery + &quot; loaded&quot;);
/// 		&#160;&#160;&#160;&#160; console.log(&quot;jQuery version &quot; + window.$.fn.jquery + &quot; loaded&quot;);
/// 		&#160;&#160;&#160;&#160; console.log(&quot;jQuery version &quot; + jQuery.fn.jquery + &quot; loaded&quot;);
/// 		})(jQuery);
///
/// 		Solution: In most cases you can simply search and replace the references to window.jQuery, jQuery, and window.$ with $.
/// 		Example above after fixing it:
///
/// 		(function($)
/// 		{
/// 		&#160;&#160;&#160;&#160; console.log(&quot;jQuery version &quot; + $.fn.jquery + &quot; loaded&quot;);
/// 		&#160;&#160;&#160;&#160; console.log(&quot;jQuery version &quot; + $.fn.jquery + &quot; loaded&quot;);
/// 		&#160;&#160;&#160;&#160; console.log(&quot;jQuery version &quot; + $.fn.jquery + &quot; loaded&quot;);
/// 		})(jQuery);
/// 	</description>
/// 	<param name="cfg" type="object"> Configuration object - see function description for details </param>
/// </function>
SMResourceManager.Jquery.Load = function(cfg)
{
	// Postpone if loading in progress (to avoid conflicts between multiple versions
	// fighting for control over window.jQuery which is required when loading plugins).

	if (SMResourceManager.Jquery.Loading === true)
	{
		setTimeout(function() { SMResourceManager.Jquery.Load(cfg); }, 100);
		return;
	}

	// Make sure SMResourceManager has control over window.jQuery and window.$

	if (window.jQuery)
		throw "An instance of jQuery has already been loaded using an unsupport approach (most likely by an extension loading jQuery using an old fashion <script> block) - SMResourceManager must be used to load jQuery.";
	if (window.$)
		throw "Another JavaScript library have already defined window.$ which is not supported - please uninstall library to enable jQuery";

	// Validate configuration

	if (cfg === undefined)
		throw "Unable to load jQuery - configuration undefined";
	if (cfg.version === undefined && cfg.source === undefined)
		throw "Unable to load jQuery - both version and source property undefined";

	if (cfg.styles !== undefined)
		for (var i = 0 ; i < cfg.styles.length ; i++)
			if (cfg.styles[i].source === undefined)
				throw "Unable to load stylesheet with source property undefined";

	if (cfg.plugins !== undefined)
		for (var i = 0 ; i < cfg.plugins.length ; i++)
			if (cfg.plugins[i].source === undefined)
				throw "Unable to load plugin with source property undefined";

	// Load jQuery (either from CDN or alternative source)

	// Styles and Plugins are loaded async. Prevent other versions of jQuery being loaded
	// between styles and plugins which would cause window.$ and window.jQuery to change,
	// hence causing plugins to be registered to the wrong instance of jQuery.
	SMResourceManager.Jquery.Loading = true;

	try
	{
		var url = ((cfg.version !== undefined) ? "//ajax.googleapis.com/ajax/libs/jquery/" + cfg.version + "/jquery.min.js" : cfg.source);
		SMResourceManager.LoadScript(url, function(src)
		{
			try
			{
				// Make sure jQuery got loaded and that window.$ is in fact jQuery

				if (window.$ === undefined || window.$.fn === undefined || window.$.fn.jquery === undefined)
					throw "Error loading jQuery: " + src;

				var jq = window.$;

				// Fire loaded event handler

				if (cfg.loaded !== undefined)
					cfg.loaded(jq, cfg);

				// Helper function used to create closure.
				// This is necessary when creating callbacks with arguments within a loop.
				// The index will change, hence causing reference to change (see usage).

				var createClosure = function(originalCallback, jq, originalCfg)
				{
					return function(cfg) { originalCallback(jq, originalCfg); };
				}

				// Load styles

				// Cloning as callback handlers are modified which should not be exposed to callback function receiving configuration object
				var styles = ((cfg.styles !== undefined) ? SMJson.CloneObject(cfg.styles) : []);

				// Create callback functions compatible with LoadStyleSheets(..).
				// This will be responsible for calling the original callback functions defined on configuration objects.
				// Wrap in closure to make sure objects from loop are available in execution context.
				for (var i = 0 ; i < styles.length ; i++)
					if (styles[i].loaded !== undefined)
						styles[i].loaded = createClosure(styles[i].loaded, jq, cfg.styles[i]);

				SMResourceManager.LoadStyleSheets(styles, function(stylesCfg)
				{
					try
					{
						// Load plugins (all styles have been loaded).

						// Cloning configuration objects and creating closures as we did with
						// styles configurations. See comments above for more details.

						var plugins = ((cfg.plugins !== undefined) ? SMJson.CloneObject(cfg.plugins) : []);

						if (cfg.uiversion !== undefined)
							plugins.push({ source: "//ajax.googleapis.com/ajax/libs/jqueryui/" + cfg.uiversion + "/jquery-ui.min.js" });

						for (var i = 0 ; i < plugins.length ; i++)
							if (plugins[i].loaded !== undefined)
								plugins[i].loaded = createClosure(plugins[i].loaded, jq, cfg.plugins[i]);

						SMResourceManager.LoadScripts(plugins, function(pluginsCfg)
						{
							try
							{
								window.$.noConflict(true); // Release control over window.$ and window.jQuery - no longer needed

								// Fire complete handler
								if (cfg.complete !== undefined)
									cfg.complete(jq, cfg);
							}
							catch (err)
							{
								if (window.console)
								{
									console.log(err.message);
									console.log(err.stack);
									console.log(err);
								}
							}
							finally
							{
								window.$ = undefined;
								window.jQuery = undefined;

								SMResourceManager.Jquery.Loading = false;
							}
						});
					}
					catch (err)
					{
						window.$ = undefined;
						window.jQuery = undefined;

						SMResourceManager.Jquery.Loading = false;

						if (window.console)
						{
							console.log(err.message);
							console.log(err.stack);
							console.log(err);
						}
					}
				});
			}
			catch (err)
			{
				window.$ = undefined;
				window.jQuery = undefined;

				SMResourceManager.Jquery.Loading = false;

				if (window.console)
				{
					console.log(err.message);
					console.log(err.stack);
					console.log(err);
				}
			}
		});
	}
	catch (err)
	{
		SMResourceManager.Jquery.Loading = false;

		if (window.console)
		{
			console.log(err.message);
			console.log(err.stack);
			console.log(err);
		}
	}
}

/// <function container="client/SMResourceManager" name="Jquery.Run" access="public" static="true">
/// 	<description>
/// 		Run code with a specific version of jQuery which is automatically loaded from CDN in a non-blocking manner.
/// 		If jQuery instance has previously been loaded, a cached copy is used for improved performance.
///
/// 		Use SMResourceManager.Jquery.Load(..) instead if jQuery plugins are required (e.g. jQuery UI).
///
/// 		Example:
///
/// 		SMResourceManager.Jquery.Run(&quot;1.10.2&quot;, function($)
/// 		{
/// 		&#160;&#160;&#160;&#160; alert(&quot;jQuery version &quot; + $.fn.jquery + &quot; loaded!&quot;);
/// 		});
/// 	</description>
/// 	<param name="version" type="string">
/// 		Specify desired jQuery version. A list of available versions is available here:
/// 		https://developers.google.com/speed/libraries/devguide?csw=1#jquery
/// 		Using the most recent version of the jQuery 1.x branch is recommended to ensure support for legacy browsers.
/// 	</param>
/// 	<param name="callback" type="delegate" default="undefined"> Callback function to execute when jQuery is ready (takes jQuery instance as an argument) </param>
/// </function>
SMResourceManager.Jquery.Run = function(version, callback)
{
	if (SMResourceManager.Jquery.Loaded[version] !== undefined)
	{
		callback(SMResourceManager.Jquery.Loaded[version]);
	}
	else
	{
		SMResourceManager.Jquery.Load({ version: version, loaded: function($)
		{
			SMResourceManager.Jquery.Loaded[version] = $;
			callback($);
		}});
	}
}

// Internal function, should only be used by Sitemagic!
// Internal copy of jQuery might be modified or upgraded at any time!
SMResourceManager.Jquery.LoadInternal = function(callback, additionalPlugins, additionalStyles)
{
	// Load jQuery and UI with additional plugins if specified

	if (additionalPlugins !== undefined)
	{
		var plugins = [ { source: "base/gui/jQuery/jquery-ui-1.10.4.custom/js/jquery-ui-1.10.4.custom.min.js" } ];
		var styles = [ { source: "base/gui/jQuery/jquery-ui-1.10.4.custom/css/Sitemagic/jquery-ui-1.10.4.custom.min.css" } ];

		SMCore.ForEach(additionalPlugins, function(plugin)
		{
			plugins.push( { source: plugin } );
		});

		if (additionalStyles !== undefined)
		{
			SMCore.ForEach(additionalStyles, function(style)
			{
				styles.push( { source: style } );
			});
		}

		SMResourceManager.Jquery.Load(
		{
			source: "base/gui/jQuery/jquery-1.11.0.min.js",
			plugins: plugins,
			styles: styles,
			complete: function($) { callback($); }
		});

		return; // Return, we don't cache instances with additional plugins loaded!
	}

	// Load pure jQuery instance and UI if additional plugins are not specified. This instance
	// is safe to be reused later since it is not polluted with various plugins, so we cache it.

	if (SMResourceManager.Jquery.Internal !== null)
	{
		callback(SMResourceManager.Jquery.Internal);
		return;
	}

	SMResourceManager.Jquery.Load(
	{
		source: "base/gui/jQuery/jquery-1.11.0.min.js",
		plugins: [ { source: "base/gui/jQuery/jquery-ui-1.10.4.custom/js/jquery-ui-1.10.4.custom.min.js" } ],
		styles: [ { source: "base/gui/jQuery/jquery-ui-1.10.4.custom/css/Sitemagic/jquery-ui-1.10.4.custom.min.css" } ],
		complete: function($) { SMResourceManager.Jquery.Internal = $; callback($); }
	});
}

// SMJson

function SMJson()
{
}

SMJson.CloneObject = function(obj)
{
	if (obj === null || typeof(obj) !== "object")
		return obj;

	var clone = obj.constructor();

	for (var i in obj)
		clone[i] = SMJson.CloneObject(obj[i]);

	return clone;
}

// SMRandom

/// <container name="client/SMRandom">
/// 	Random value generator.
///
/// 	// Example code
///
/// 	var guid = SMRandom.CreateGuid();
/// </container>
function SMRandom()
{
}

/// <function container="client/SMRandom" name="CreateGuid" access="public" static="true" returns="string">
/// 	<description> Creates and returns 32 bits unique ID </description>
/// </function>
SMRandom.CreateGuid = function()
{
	var chars = "0123456789abcdef".split("");

	var uuid = new Array();
	var rnd = Math.random;
	var r = -1;

	// Excluding dashes - not used by SMRandom::CreateGuid() either (server side)
	//uuid[8] = "-";
	//uuid[13] = "-";
	uuid[14] = "4"; // version 4 complient
	//uuid[18] = "-";
	//uuid[23] = "-";

	for (var i = 0 ; i < 32 ; i++)
	{
		if (uuid[i] !== undefined)
			continue;

		r = 0 | rnd() * 16;

		uuid[i] = chars[((i === 19) ? (r & 0x3) | 0x8 : r & 0xf)];
	}

	return uuid.join("");
}

// SMForm

function SMForm()
{
}
SMForm.Internal = {};

/// <function container="client/SMForm" name="PostBack" access="public" static="true">
/// 	<description> Programmatically post back form </description>
/// </function>
SMForm.PostBack = function()
{
	SMForm.Internal.OnPostBackInitiated(); // Also registered as OnSubmit handler, but it doesn't fire when form is submitted manually as below

	try
	{
		document.getElementById('SMForm').submit();
	}
	catch (err)
	{
		// IE throws an error (Unknown exception / Unspecified error) when canceling navigation caused by JavaScript during OnBeforeUnload
		if (window.console)
		{
			console.log(err.message);
			console.log(err.stack);
			console.log(err);
			console.log("Error is most likely caused by misbehavior in IE7/IE8 when canceling post back using OnBeforeUnload.");
		}
	}
}
function smFormPostBack() { SMForm.PostBack(); } // Backward compatibility

// Encodes Unicode characters on post back
SMForm.Internal.OnPostBackInitiated = function()
{
	SMForm.Internal.TransformData(SMStringUtilities.UnicodeEncode);
	setTimeout(function() { SMForm.Internal.TransformData(SMStringUtilities.UnicodeDecode); }, 0); // Undo in case postback is canceled
}

// Transform values within inputs/textareas/buttons using Encoder/Decoder callback (transformer argument)
SMForm.Internal.TransformData = function(transformer, includeButtons)
{
	var inputs = document.getElementsByTagName("input");
	for (var i = 0 ; i < inputs.length ; i++)
		if (inputs[i].type === "text" || inputs[i].type === "password" || inputs[i].type === "search" || inputs[i].type === "email" || inputs[i].type === "url" || inputs[i].type === "hidden" || (includeButtons === true && (inputs[i].type === "button" || inputs[i].type === "submit" || inputs[i].type === "reset"))) // search, email, and url are HTML5 specific
			inputs[i].value = transformer(inputs[i].value);

	var textareas = document.getElementsByTagName("textarea");
	for (var i = 0 ; i < textareas.length ; i++)
		textareas[i].value = transformer(textareas[i].value);

	var selects = document.getElementsByTagName("select");
	for (var i = 0 ; i < selects.length ; i++)
		selects[i].options[selects[i].selectedIndex].value = transformer(selects[i].options[selects[i].selectedIndex].value);
		// for (var j = 0 ; j < selects[i].options.length ; j++)
		// 	selects[i].options[j].value = transformer(selects[i].options[j].value);
}

// Configure inputs/textareas with new maxlength handler that supports Unicode Characters (HEX entities)
SMForm.Internal.ConfigureMaxLength = function()
{
	var types = [ document.getElementsByTagName("input"), document.getElementsByTagName("textarea") ];
	var inputs = null;
	var max = -1;
	var div = null;

	for (var x = 0 ; x < types.length ; x++)
	{
		inputs = types[x];

		for (var i = 0 ; i < inputs.length ; i++)
		{
			if (inputs[i].className.indexOf("SMInput") === -1)
				continue;

			// Skip if maxlength has not been configured, to prevent performance penalty.
			// 2147483647 is the default value for several versions of IE browsers
			// while 524288 is the default for Opera, Safari, and Chrome (-1 for textareas).
			// Sometimes the defaults apply to <input>, sometimes to <textarea>, sometimes to both.

			max = inputs[i].maxLength;

			if (max === null || max === undefined || max === -1 || max === 524288 || max === 2147483647)
				continue;

			// Wrap element in div with relative positioning to allow absolute positioning of warning indicator within

			div = document.createElement("div");
			div.className = "SMFormInputWrapper";

			SMDom.WrapElement(inputs[i], div);

			// Register handler responsible for checking max length

			SMEventHandler.AddEventHandler(inputs[i], "keyup", SMForm.Internal.MaxLengthChecker);

			// Run handler manually in case a value has been set server side which is too long

			SMForm.Internal.MaxLengthChecker({ target: inputs[i] });
		}
	}
}

// Event handler responsible for checking input length
SMForm.Internal.MaxLengthChecker = function(e)
{
	var ev = e || window.event;
	var input = ev.target || ev.srcElement; // input or textarea

	// Skip if a check has already been scheduled (runs every X ms while typing)

	if (input.smFormMaxLengthTimer !== undefined)
		return;

	// Schedule length check

	input.smFormMaxLengthTimer = setTimeout(function()
	{
		if (SMStringUtilities.UnicodeEncode(input.value).length > input.maxLength)
		{
			SMForm.Internal.SetMaxLengthWarning(input, true);
		}
		else
		{
			SMForm.Internal.SetMaxLengthWarning(input, false);
		}

		input.smFormMaxLengthTimer = undefined;
	}, 250);
}

// Function renders or removes MaxLength warning symbol
SMForm.Internal.SetMaxLengthWarning = function(elm, enable)
{
	if (enable === false)
	{
		if (elm.smFormMaxLengthWarning !== undefined) // Warning symbol may not have been added yet if number of characters is still below maxlength
		{
			elm.parentNode.removeChild(elm.smFormMaxLengthWarning);
			elm.smFormMaxLengthWarning = undefined;
		}

		return;
	}

	if (elm.smFormMaxLengthWarning !== undefined) // Cancel out, warning symbol already added
		return;

	// Render warning symbol

	var warning = document.createElement("div");
	warning.className = "SMFormMaxLengthWarning fa fa-exclamation-circle"; // IE7 doesn't show icon since it is added using the :before pseudo class which is not supported in IE7 - too bad!
	warning.title = SMLanguageHandler.GetTranslation("MaxLengthExceededWarning");
	warning.onclick = function() { SMMessageDialog.ShowMessageDialog(this.title); };

	// Adjust position for better alignment
	if (elm.tagName.toLowerCase() === "input")
		warning.style.top = "0px";
	else // textarea
		warning.style.marginTop = "3px";

	elm.parentNode.insertBefore(warning, elm);
	elm.smFormMaxLengthWarning = warning;
}

// Wire it all up when page is ready
SMEventHandler.AddEventHandler(document, "DOMContentLoaded", function() // //SMEventHandler.AddEventHandler(window, "load", function()
{
	SMForm.Internal.TransformData(SMStringUtilities.UnicodeDecode, true);
	SMForm.Internal.ConfigureMaxLength();

	SMEventHandler.AddEventHandler(document.getElementById("SMForm"), "submit", SMForm.Internal.OnPostBackInitiated);
});
