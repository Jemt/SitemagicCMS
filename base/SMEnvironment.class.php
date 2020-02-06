<?php

/// <container name="base/SMEnvironment">
/// 	Static functions provide access to the $_SERVER array, query string
/// 	parameters, form data after post back, the cookie store and session store.
/// 	With PHP these resources will throw warnings if non existing values
/// 	are queried. This does not happen with the SMEnvironment class, which
/// 	instead returns Null if a given value is not found.
///
/// 	System information is also available, such as names
/// 	of system directories, system meta data, and installation path and URL.
/// </container>
class SMEnvironment
{
	private static $masterTemplate = null;
	private static $formInstance = null;
	private static $sessionClosed = false;

	public static function Initialize() // Invoked by SMController
	{
		self::initializeCookies();
		self::initializeSessions();
	}

	/// <function container="base/SMEnvironment" name="GetEnvironmentValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get value from $_SERVER array - returns Null if not found.
	/// 		Most commonly a string is returned.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying value in array </param>
	/// </function>
	public static function GetEnvironmentValue($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		return ((isset($_SERVER[$key]) === true) ? $_SERVER[$key] : null);
	}

	public static function GetEnvironmentData()
	{
		return $_SERVER;
	}

	// POST data (form)

	/// <function container="base/SMEnvironment" name="GetPostValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get value from form data after post back - returns Null if not found.
	/// 		Most commonly a string is returned. An array may be returned for e.g.
	/// 		an option list allowing for multiple selections.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying form element </param>
	/// 	<param name="strValueRestriction" type="SMValueRestriction" default="SMValueRestriction::$None">
	/// 		The resource being queried is considered insecure as content can be manipulated externally.
	/// 		A value restriction ensure that the value being queried is in the specified format.
	/// 		A security exception is thrown if value is in conflict with value restriction.
	/// 		See base/SMValueRestriction for more information.
	/// 	</param>
	/// 	<param name="exceptions" type="string[]" default="string[0]">
	/// 		Values defined in array will be allowed despite of value restriction.
	/// 		See base/SMStringUtilities::Validate(..) for important information.
	/// 		Some value restrictions should not be accompanied by a list of exception values.
	/// 	</param>
	/// </function>
	public static function GetPostValue($key, $strValueRestriction = "None", $exceptions = array())
	{
		// Arguments validated in getValidatedValue(..).
		// Values are not to be trusted - may have been modified client side.
		return self::getValidatedValue("\$_POST", $_POST, $key, $strValueRestriction, $exceptions);
	}

	/// <function container="base/SMEnvironment" name="GetPostKeys" access="public" static="true" returns="string[]">
	/// 	<description> Get all keys in post back data </description>
	/// </function>
	public static function GetPostKeys()
	{
		return array_keys($_POST);
	}

	public static function GetPostData()
	{
		return $_POST;
	}

	/// <function container="base/SMEnvironment" name="GetJsonData" access="public" static="true" returns="array">
	/// 	<description>
	/// 		Get raw JSON data sent to server via POST without a POST collection key.
	/// 		Data is returned as a multi dimentional array. Unicode specific characters
	/// 		are converted into HEX entities to represent data using ISO-8859-1 encoding.
	/// 		SMStringUtilities::UnicodeEncode(..) and SMStringUtilities::UnicodeDecode(..)
	/// 		can be used to transform data back and forth between ISO-8859-1 and Unicode.
	/// 	</description>
	/// </function>
	public static function GetJsonData()
	{
		// NOTICE: The client sends data as UTF-8, but Sitemagic uses ISO-8859-1 due to limitations in earlier versions of PHP.
		// Data is therefore transformed into ISO-8859-1. Unicode characters are converted into HEX entities.

		// Read JSON data sent to server without a POST key
		$data = file_get_contents("php://input");

		// Prevent invalid byte sequences which may lead to Invalid Encoding Attacks
		if (mb_check_encoding($data, "UTF-8") === false)
			throw new Exception("Invalid byte sequence detected");

		// Convert JSON to associative array.
		// XMLHttpRequest always sends UTF-8 which is also what json_decode(..) expects.
		$jsonArray = json_decode($data, true);

		// Return null if no data, null, or invalid JSON was provided
		if ($jsonArray === null)
			return null;

		// Mixed return types not supported even though json_decode(..) allows it
		if (is_bool($jsonArray) === true)
			return array("value" => $jsonArray);

		// Convert data to ISO-8859-1 - unicode characters are turned into HEX entities
		$jsonArray = self::decodeArrayFromUtf8ToLatin1($jsonArray);

		return $jsonArray;
	}

	private static function decodeArrayFromUtf8ToLatin1($arr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "arr", $arr, SMTypeCheckType::$Array);

		foreach ($arr as $key => $value)
		{
			if (is_array($value) === true)
			{
				$arr[$key] = self::decodeArrayFromUtf8ToLatin1($value);
			}
			else if (is_string($value) === true)
			{
				$arr[$key] = SMStringUtilities::UnicodeEncode($value); //utf8_decode($value);
			}
		}

		return $arr;
	}

	// GET data (query string)

	/// <function container="base/SMEnvironment" name="GetQueryValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get value from query string parameter - returns Null if not found.
	/// 		Most commonly a string is returned. Values are URL decoded.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying query string parameter </param>
	/// 	<param name="strValueRestriction" type="SMValueRestriction" default="SMValueRestriction::$None"> See GetPostValue(..) function for description </param>
	/// 	<param name="exceptions" type="string[]" default="string[0]"> See GetPostValue(..) function for description </param>
	/// </function>
	public static function GetQueryValue($key, $strValueRestriction = "None", $exceptions = array())
	{
		// Arguments validated in getValidatedValue(..).
		// Values are not to be trusted - may have been modified client side.
		return self::getValidatedValue("\$_GET", $_GET, $key, $strValueRestriction, $exceptions);
	}

	/// <function container="base/SMEnvironment" name="GetQueryKeys" access="public" static="true" returns="string[]">
	/// 	<description> Get all keys from query string parameters </description>
	/// </function>
	public static function GetQueryKeys()
	{
		return array_keys($_GET);
	}

	public static function GetQueryData()
	{
		return $_GET;
	}

	// REQUEST data

	public static function GetRequestValue($key, $strValueRestriction = "None", $exceptions = array())
	{
		// Arguments validated in getValidatedValue(..).
		// Values are not to be trusted - may have been modified client side.
		return self::getValidatedValue("\$_REQUEST", $_REQUEST, $key, $strValueRestriction, $exceptions);
	}

	public static function GetRequestData()
	{
		return $_REQUEST;
	}

	// Sessions

	private static function initializeSessions()
	{
		// Log warning if session.auto_start is enabled

		if (isset($_SESSION) === true)
		{
			// Unable to establish separate session for current installation since session
			// has already been started, most likely because session.auto_start is enabled.
			// This will cause sites to share session data. Changes on one site might influence
			// data or behaviour on another site.
			// Theoretically it also poses a security problem if one site is supposed to be
			// completely isolated from another site.
			// If a user logges into Site A and Site B, and Site B contains malicious code,
			// it will be able to steal session data from Site A.
			// However, this is not a problem if two different computers (or even browsers)
			// are used, since sessions are separated by (contained in) browsers.
			// It would be a problem if the session ID was exposed though, since that particular
			// session ID would then grant access to session data from multiple sites.

			if (SMAttributes::GetAttribute("SMEnvironmentSessionAutoStartWarning") === null) // Make sure warning is only written to log once
			{
				SMAttributes::SetAttribute("SMEnvironmentSessionAutoStartWarning", "true");
				SMLog::Log(__FILE__, __LINE__, "WARNING: session.auto_start is enabled, preventing Sitemagic from isolating session data between sites and subsites");
			}

			return;
		}

		// Establishing a separete session for the given site.
		// This requires that either a unique session name (session_name($name)) or session ID (session_id($id))
		// is set. The given value must be static to allow the session to be restored between postbacks.
		// Assigning a new session ID has the drawback of restoring the session, even if the browser has been fully restarted,
		// at least for the life time of the session. The session ID is simply a pointer to a session file on the server.
		// Setting the session ID will instruct PHP to load any data from the given session file if it still exists.
		// Assigning a new session name is what we want since it will cause PHP to generate a new unique session ID,
		// if the session cookie with the specified session name is not yet available, which it will not be on the
		// first initial page load.
		//
		// Basically, what PHP does on page load to establish the session, is something like this:
		//
		// if (!isset($_COOKIE["PHPSESSID"])
		// {
		//     // No session cookie is available, so this is the initial page load.
		//     // Generate new session ID and store it in a browser cookie. Cookies are
		//     // automatically sent to the server on requests, and PHP exposes them using the $_COOKIE array.
		//
		//     $id = generateSomeId();
		//     setcookie("PHPSESSID", $id, 0, "/"); // Timeout of 0 = session cookie that expires when browser is closed
		//     $_COOKIE["PHPSESSID"] = $id;
		// }
		//
		// session_id($_COOKIE["PHPSESSID"]);
		//
		// We will let PHP work its magic, and simply make sure the site is given a unique session name (PHPSESSID is replaced with another name).
		// That way we get a site specific session that is automatically renewed if the browser is restarted.

		$path = md5(self::GetDocumentRoot());
		$sessionName = SMAttributes::GetAttribute("SMEnvironmentSessionName" . $path); // Path is part of key to make sure a new unique session name is generated if site is copied/moved/renamed

		if ($sessionName === null)
		{
			// Generate random session name.
			// Theoretically (but extremely unlikely) two sites could potentially
			// be assigned the same session name. However, that would only be a problem
			// if the two sites were accessed from the same browser, which is even more unlikely.
			// Sessions are bound to browsers, as long as the session ID is not exposed
			// and used in session hijacking.

			$sessionName = "SMSESSION" . SMRandom::CreateText(16);
			SMAttributes::SetAttribute("SMEnvironmentSessionName" . $path, $sessionName);
		}

		session_name($sessionName);
		session_start(); // Start session - make $_SESSION available
	}

	/// <function container="base/SMEnvironment" name="CloseSession" access="public" static="true">
	/// 	<description>
	/// 		Closes session. This will prevent additional data from being persisted to session
	/// 		state, but will allow another concurrent request to be performed from the same session.
	/// 	</description>
	/// </function>
	public static function CloseSession()
	{
		// From the PHP manual - http://php.net/manual/en/function.session-write-close.php
		// "Session data is usually stored after your script terminated without the
		// need to call session_write_close(), but as session data is locked to prevent
		// concurrent writes only one script may operate on a session at any time".
		session_write_close();
		self::$sessionClosed = true;
	}

	/// <function container="base/SMEnvironment" name="GetSessionValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get session value - returns Null if not found.
	/// 		Most commonly a string is returned.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying value in session store </param>
	/// 	<param name="strValueRestriction" type="SMValueRestriction" default="SMValueRestriction::$None"> See GetPostValue(..) function for description </param>
	/// 	<param name="exceptions" type="string[]" default="string[0]"> See GetPostValue(..) function for description </param>
	/// </function>
	public static function GetSessionValue($key, $strValueRestriction = "None", $exceptions = array()) // Supports Value Restriction for consistency - sessions are not externally alterable, hence more secure
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		return self::getValidatedValue("\$_SESSION", $_SESSION, $key, $strValueRestriction, $exceptions);
	}

	/// <function container="base/SMEnvironment" name="GetSessionKeys" access="public" static="true" returns="string[]">
	/// 	<description> Get all keys from session data </description>
	/// </function>
	public static function GetSessionKeys()
	{
		return array_keys($_SESSION);
	}

	// DEPRECATED - use SetSession instead
	public static function SetSessionValue(SMKeyValue $data)
	{
		if (self::$sessionClosed === true)
			throw new Exception("Session state has been disabled for this request");

		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "SetSession");
		$_SESSION[$data->GetKey()] = $data->GetValue();
	}

	// DEPRECATED - use DestroySession instead
	public static function DestroySessionValue($key)
	{
		if (self::$sessionClosed === true)
			throw new Exception("Session state has been disabled for this request");

		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "DestroySession");
		unset($_SESSION[$key]);
	}

	public static function GetSessionData()
	{
		return $_SESSION;
	}

	/// <function container="base/SMEnvironment" name="SetSession" access="public" static="true">
	/// 	<description> Store value in session store </description>
	/// 	<param name="key" type="string"> Unique key identifying value </param>
	/// 	<param name="value" type="string"> Value to store with specified key </param>
	/// </function>
	public static function SetSession($key, $value) // Replaces SetSessionValue
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		if (self::$sessionClosed === true)
			throw new Exception("Session state has been disabled for this request");

		$_SESSION[$key] = $value;
	}

	/// <function container="base/SMEnvironment" name="DestroySession" access="public" static="true">
	/// 	<description> Remove value from session store </description>
	/// 	<param name="key" type="string"> Unique key identifying value to remove </param>
	/// </function>
	public static function DestroySession($key) // Replaces DestroySessionValue
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if (self::$sessionClosed === true)
			throw new Exception("Session state has been disabled for this request");

		unset($_SESSION[$key]);
	}

	/// <function container="base/SMEnvironment" name="GetRequestToken" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Get request token unique to the current session.
	/// 		This can be used as a CSRF (Cross-Site Request Forgery)
	/// 		token to prevent cross-site requests.
	/// 	</description>
	/// </function>
	public static function GetRequestToken()
	{
		$token = self::GetSessionValue("SMCSRFToken");

		if ($token === null)
		{
			$token = SMRandom::CreateText(32);
			self::SetSession("SMCSRFToken", $token);
		}

		return $token;
	}

	// Cookies

	private static function initializeCookies()
	{
		// Remove cookie prefix from $_COOKIE array.
		// Prefix is used to encapsulate cookies on a root site,
		// preventing them from becoming accessible on subsites,
		// and cause naming conflicts.

		// Example:
		// "SM#/#Name" => "James Jackson"
		// "SM#/#ViewMode" => "Normal"
		// Becomes:
		// "Name" => "Sames Jackson"
		// "ViewMode" => "Normal"
		if (self::IsSubSite() === false)
		{
			$internalKeyPrefix = "SM#/#";

			foreach ($_COOKIE as $key => $value)
			{
				if (strpos($key, $internalKeyPrefix) === 0)
				{
					// Remove cookie with a key such as "SM#/#Name" and re-add it with a key such as "Name".
					// Notice: This change remains on the server and is not pushed to the client since setcookie(..) is never called.
					unset($_COOKIE[$key]);
					$_COOKIE[substr($key, strlen($internalKeyPrefix))] = $value;
				}
			}
		}
	}

	/// <function container="base/SMEnvironment" name="GetCookieValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get cookie value - returns Null if not found.
	/// 		Most commonly a string is returned.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying value in cookie store </param>
	/// 	<param name="strValueRestriction" type="SMValueRestriction" default="SMValueRestriction::$None"> See GetPostValue(..) function for description </param>
	/// 	<param name="exceptions" type="string[]" default="string[0]"> See GetPostValue(..) function for description </param>
	/// </function>
	public static function GetCookieValue($key, $strValueRestriction = "None", $exceptions = array())
	{
		// Arguments validated in getValidatedValue(..).
		// Values are not to be trusted - may have been modified client side.
		return self::getValidatedValue("\$_COOKIE", $_COOKIE, $key, $strValueRestriction, $exceptions);
	}

	/// <function container="base/SMEnvironment" name="GetCookieKeys" access="public" static="true" returns="string[]">
	/// 	<description> Get all keys from cookie data </description>
	/// </function>
	public static function GetCookieKeys()
	{
		return array_keys($_COOKIE);
	}

	// DEPRECATED - use SetCookie instead
	public static function SetCookieValue(SMKeyValue $data)
	{
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "SetCookie");
		self::SetCookie($data->GetKey(), $data->GetValue(), 0); // Notice expiration of 0 = expires when session ends
	}

	// DEPRECATED - use DestoryCookie instead
	public static function DestroyCookieValue($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "DestroyCookie");
		self::DestroyCookie($key);
	}

	public static function GetCookieData()
	{
		return $_COOKIE;
	}

	/// <function container="base/SMEnvironment" name="SetCookie" access="public" static="true">
	/// 	<description> Store value in cookie store </description>
	/// 	<param name="key" type="string"> Unique key identifying value </param>
	/// 	<param name="value" type="string"> Value to store with specified key </param>
	/// 	<param name="expireSeconds" type="integer">
	/// 		Expiration time in seconds. A value of 0 makes it
	/// 		a session cookie, a value of -1 removes the cookie.
	/// 	</param>
	/// 	<param name="path" type="string" default="null"> Path to which cookie is associated </param>
	/// </function>
	public static function SetCookie($key, $value, $expireSeconds, $path = null) // Replaces SetCookieValue
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "expireSeconds", $expireSeconds, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "path", (($path !== null) ? $path : ""), SMTypeCheckType::$String);

		if ($path === null)
			$path = self::GetRequestPath();

		$internalKey = $key;

		if (self::IsSubSite() === false)
			$internalKey = "SM#/#" . $key; // Prevent conflicts with cookies on subsites - NOTICE: prefix MUST be identical to prefix used in SMClient.js client side!

		if ($path !== "/" && SMStringUtilities::EndsWith($path, "/") === false) // e.g. /SitemagicDemo
			$path .= "/";  // Must end with a slash to be compatible with path set by SMClient.js

		// Set cookie client side (here we need the special internal key for cookies on root site to avoid conflicts with subsite cookies)
		setcookie($internalKey, $value, (($expireSeconds > 0) ? time() + $expireSeconds : $expireSeconds), $path);

		// Set cookie server side
		if ($expireSeconds >= 0)
			$_COOKIE[$key] = $value;
		else
			unset($_COOKIE[$key]);
	}

	/// <function container="base/SMEnvironment" name="DestroyCookie" access="public" static="true">
	/// 	<description> Remove value from cookie store </description>
	/// 	<param name="key" type="string"> Unique key identifying value to remove </param>
	/// </function>
	public static function DestroyCookie($key) // Replaces DestroyCookieValue
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		self::SetCookie($key, "", -1);
	}

	// Other functions

	/// <function container="base/SMEnvironment" name="GetExternalUrl" access="public" static="true" returns="string">
	/// 	<description> Get URL to web application (e.g. http://domain.com/demo/cms) </description>
	/// </function>
	public static function GetExternalUrl()
	{
		$url = "";
		$url .= "http";
		$url .= ((isset($_SERVER["HTTPS"]) === true && $_SERVER["HTTPS"] !== "off") ? "s://" : "://");
		$url .= $_SERVER["SERVER_NAME"];
		$url .= (($_SERVER["SERVER_PORT"] !== "80" && $_SERVER["SERVER_PORT"] !== "443") ? ":" . $_SERVER["SERVER_PORT"] : "");

		$rp = SMEnvironment::GetRequestPath();
		$url .= ($rp !== "/" ? $rp : "");

		/* // DISABLED - now using new approach above which exclude virtual directories
		$ruri = self::GetEnvironmentValue("REQUEST_URI");
		$ruri = (strpos($ruri, "?") !== false ? substr($ruri, 0, strpos($ruri, "?")) : $ruri); // Remove query string parameters which may contain a slash (e.g. https://localhost/demo/?SMExt=SMDesigner&SMCallback=callbacks/test)
		$ruri = substr($ruri, 0, strrpos($ruri, "/"));

		$url = "";
		$url .= "http";
		$url .= ((isset($_SERVER["HTTPS"]) === true && $_SERVER["HTTPS"] !== "off") ? "s://" : "://");
		$url .= $_SERVER["SERVER_NAME"];
		$url .= (($_SERVER["SERVER_PORT"] !== "80" && $_SERVER["SERVER_PORT"] !== "443") ? ":" . $_SERVER["SERVER_PORT"] : "");
		$url .= $ruri;
		//$url .= substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/"));
		//$url .= substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], "/")); // Not reliable when URL Rewriting is used (e.g. sub.domain.com => domain.com/sites/sub)*/

		return $url; // e.g. http://www.domain.com/demo/cms
	}

	/// <function container="base/SMEnvironment" name="GetInstallationPath" access="public" static="true" returns="string">
	/// 	<description>
	/// 		DEPRECATED - use GetRequestPath() instead. This function
	/// 		might return incorrect results when URL Rewriting is in use.
	/// 		Get path to installation on server - e.g. / if installed in root, or /demo if installed to a sub folder.
	/// 		For a subsite called Blog, installed in /Sitemagic, the path returned would be /Sitemagic/sites/Blog.
	/// 	</description>
	/// </function>
	public static function GetInstallationPath()
	{
		// Function has been marked as deprecated - it produces unreliable results
		// when URL rewriting is used to map e.g. test.domain.com to domain.com/sites/test.
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "GetRequestPath");

		// Returns "/" if installed in root of web host.
		// Returns "/folder/subfolder" if installed in /folder/subfolder.
		// Returns "/folder/subfolder/sites/demo" if subsite is installed in /folder/subfolder/sites/demo.

		// PHP_SELF is unreliable when URL Rewriting is used,
		// especially with subdomains where e.g. test.domain.com maps to domain.com/test.
		// Requesting test.domain.com results in PHP_SELF returning /index.php while requesting
		// a page such as test.domain.com/webcome.html results in PHP_SELF returning /test/index.php.
		// Use GetRequestPath() instead for reliable results !

		$lastIndex = strrpos($_SERVER["PHP_SELF"], "/");
		return (($lastIndex > 0) ? substr($_SERVER["PHP_SELF"], 0, $lastIndex) : "/");
	}

	/// <function container="base/SMEnvironment" name="GetRequestPath" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Get path under which application is requested, e.g. / if running from the root, or /demo if running from a folder.
	/// 		Virtual directories part of a request is stripped away from returned value. The value returned is the actual path
	/// 		under which the web application is running.
	/// 	</description>
	/// </function>
	private static $requestPath = null;
	public static function GetRequestPath() // A better name would probably have been GetInstallationPath, but it is already defined and kept for backward compatibility
	{
		if (self::$requestPath === null)
		{
			// Returns "/" if installed in root of web host.
			// Returns "/folder/subfolder" if installed in /folder/subfolder.
			// Returns "/folder/subfolder/sites/demo" if subsite is installed in /folder/subfolder/sites/demo.
			// Returns "/" if installed to /sites/test but accessed using test.domain.com subdomain.

			$path = self::GetEnvironmentValue("REQUEST_URI"); // E.g. / or /index.php[?..] or /Test.html or /demo/Test.html or /demo/shop/phones (/shop/phones is a virtual directory path)
			$path = ((strpos($path, "?") !== false) ? substr($path, 0, strpos($path, "?")) : $path); // Remove URL arguments if defined
			$path = substr($path, 0, strrpos($path, "/"));	// Remove last slash and everything after it, resulting in e.g. "" (empty) or / or /sites/demo
			$path = (($path !== "") ? $path : "/");

			// Remove virtual directory portion from URL (remove e.g. /shop/phones used by SMShop extension)

			if ($path !== "/")
			{
				$realPath = realpath(dirname(__FILE__) . "/.."); // E.g. /var/www/domain.com/web/demo

				$dirs = explode("/", $path); // Path could be something like /demo/shop/phones
				$concat = "";

				foreach ($dirs as $dir)
				{
					// As long as $dir (and directories previously gathered in $concat) is
					// contained in $realPath, then the given directory is not a virtual directory.
					// Examples values:
					// $realPath = /var/www/demo.com/web/demo
					// $dir      =                      /demo/shop/phones
					//                                       ^^^^^^^^^^^^
					// Marked part of URL above is excluded as it is not contained in $realPath.
					// It is a virtual directory (a result of URL rewriting).
					// One might worry that having a folder with the same name
					// as the domain in the example above will cause a problem.
					// Fortunately this is not the case. In the example below
					// we have the website hosted in a folder hierarchy containing
					// two folders with the same name.
					// /var/www/demo.com/web is the document root, and
					// /var/www/demo.com/web/demo.com/test is the folder in which the
					// web application is installed. So we expect the match
					// to look like this:
					// $realPath = /var/www/demo.com/web/demo.com/test
					// $dir      =                      /demo.com/test/shop/phones
					// But instead demo.com from $dir will match the first demo.com
					// folder in $realPath:
					// $realPath = /var/www/demo.com/web/demo.com/test
					//                      ^^^^^^^^
					// However, when the next folder (test) is matched against
					// $realPath, we will get a match like this:
					// $realPath = /var/www/demo.com/web/demo.com/test
					//                                   ^^^^^^^^^^^^^
					// And when the third folder is matched against $realPath,
					// it fails because "shop" is not part of the path, so we know
					// it's a virtual directory, and we exclude it and everything
					// that follows.
					//
					// If the web application is hosted in a single folder called "demo.com",
					// it will obviously match the incorrect folder in $realPath. But if it
					// is followed by a virtual directory, it will not match, and will be
					// stripped away which will leave us with just /demo.com which is as expected.
					// If a virtual directory is not part of $dir, then there will be nothing to
					// exclude, which will also result in /demo.com being returned which is as expected.
					//
					// There is one real problem though, which is when the web application is
					// hosted in the document root with a folder path containing the name
					// of a virtual directory. So for instance the following document root would
					// cause this function to produce an incorrect result:
					// $realPath = /var/www/shop/web    (document root)
					// $dir      =         /shop/phones (function will incorrectly return /shop instead of /)
					// We accept this edge case though.
					if (strpos($realPath, "/" . ($concat !== "" ? $concat . "/" : "") . $dir) !== false)
					{
						$concat .= ($concat !== "" ? "/" : "") . $dir;
					}
					else
					{
						break; // Reached non-existing folder - skip this and remaining path as it must be based on URL rewriting (virtual directory)
					}
				}

				$path = "/" . $concat;
			}

			// Append subsite portion for a subsite

			$subSite = self::getSubSite();
			$path = $path . (($subSite !== null) ? (($path !== "/") ? "/" : "") . "sites/" . $subSite : "");

			// Cache result - this function is likely called many times

			self::$requestPath = $path;
		}

		return self::$requestPath;
	}

	/// <function container="base/SMEnvironment" name="GetDocumentRoot" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Get path to document root, e.g. /var/www/domain.com/web (installed to root)
	/// 		or /var/www/domain.com/web/Sitemagic/sites/demo (demo subsite).
	/// 	</description>
	/// </function>
	public static function GetDocumentRoot()
	{
		// NOTICE: $_SERVER["DOCUMENT_ROOT"] seems to be unreliable! On some servers (e.g. ISPConfig 3 on Debian 8)
		// a sub domain such as smcms.domain.com mapped to a folder like SMCMS in /var/www/codemagic.dk/web,
		// DOCUMENT_ROOT is returned as /var/www/codemagic.dk/web. On other servers (e.g. MAMP Pro on macOS)
		// the same setup results in DOCUMENT_ROOT being reported as /var/www/codemagic.dk/web/SMCMS.
		// However, we want Document Root to be the root of the folder containing the running web application.
		// For a sub site it would be the sub site folder rather than the main site's root folder.

		$root = $_SERVER["SCRIPT_FILENAME"];			// E.g. /var/www/domain.com/web/Sitemagic/index.php - opposite to DOCUMENT_ROOT this value will always contain the name of the (sub-) folder containing the file
		$root = str_replace("\\", "/", $root);			// In case backslashes are used on Windows Server
		$root = substr($root, 0, strrpos($root, "/"));	// Remove last slash and filename (e.g. /index.php)

		return $root;
	}

	/// <function container="base/SMEnvironment" name="GetCurrentUrl" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Get current URL including query string parameters. Returned value is configurable - examples:
	/// 		 - http://domain.com/Sitemagic/index.php?SMExt=SMLogin (default)
	/// 		 - /Sitemagic/index.php?SMExt=SMLogin (result from GetCurrentUrl(true))
	/// 		 - index.php?SMExt=SMLogin (result from GetCurrentUrl(true, true))
	/// 	</description>
	/// 	<param name="excludeDomain" type="boolean" default="false"> Set True to return URL without domain </param>
	/// 	<param name="asRelative" type="boolean" default="false"> Set True to return relative URL without domain and folder(s) portion </param>
	/// </function>
	public static function GetCurrentUrl($excludeDomain = false, $asRelative = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "excludeDomain", $excludeDomain, SMTypeCheckType::$Boolean);
		SMTypeCheck::CheckObject(__METHOD__, "asRelative", $asRelative, SMTypeCheckType::$Boolean);

		if ($excludeDomain === false && $asRelative === false)
		{
			// Example: http://domain.com/Sitemagic/index.php?SMExt=SMLogin
			return ((self::GetEnvironmentValue("HTTPS") !== null) ? "https://" : "http://") . self::GetEnvironmentValue("SERVER_NAME") . self::GetEnvironmentValue("REQUEST_URI");
		}
		else if ($excludeDomain === true && $asRelative === false)
		{
			// Examples: /Sitemagic/index.php?SMExt=SMLogin
			return self::GetEnvironmentValue("REQUEST_URI");
		}
		else if ($excludeDomain === true && $asRelative === true)
		{
			// Example: index.php?SMExt=SMLogin

			$path = SMEnvironment::GetRequestPath(); // Example: / for root, /demo/cms for sub folders
			$uri = SMEnvironment::GetEnvironmentValue("REQUEST_URI"); // Example: / or /index.php?SMExt=SMLogin or /demo/cms/index.php?SMExt=SMLogin

			// Append slash (/) to path if contained in sub folder to match URI format
			// Installed to root:
			//   Path = /
			//   URI  = /  or  /index.php?..
			// Installed to folder:
			//   Path = /Sitemagic   <== Missing slash
			//   URI  = /Sitemagic/  or  /Sitemagic/index.php?...
			$path .= (($path !== "/") ? "/" : "");

			if ($path === $uri)
				return "index.php";
			else
				return substr($uri, strlen($path));
		}
		else // $excludeDomain === false && $asRelative === true
		{
			throw new Exception("Invalid argument combination - domain cannot be included while also expecting path to be relative");
		}
	}

	/// <function container="base/SMEnvironment" name="GetMetaData" access="public" static="true" returns="SMKeyValueCollection">
	/// 	<description>
	/// 		Returns instance of SMKeyValueCollection containing meta
	/// 		data from metadata.xml, found in the root of web application folder.
	/// 	</description>
	/// </function>
	public static function GetMetaData()
	{
		$cfg = new SMConfiguration(dirname(__FILE__) . "/../metadata.xml");

		$data = new SMKeyValueCollection();
		$data["Title"] = $cfg->GetEntry("Title");
		$data["Description"] = $cfg->GetEntry("Description");
		$data["Author"] = $cfg->GetEntry("Author");
		$data["Company"] = $cfg->GetEntry("Company");
		$data["Website"] = $cfg->GetEntry("Website");
		$data["Email"] = $cfg->GetEntry("Email");
		$data["Version"] = $cfg->GetEntry("Version");
		$data["Dependencies"] = $cfg->GetEntry("Dependencies");
		$data["Notes"] = $cfg->GetEntry("Notes");

		return $data;
	}

	/// <function container="base/SMEnvironment" name="GetVersion" access="public" static="true" returns="integer">
	/// 	<description> Returns platform version number </description>
	/// </function>
	private static $version = -1;
	public static function GetVersion()
	{
		if (self::$version === -1)
		{
			$md = self::GetMetaData();
			self::$version = (int)$md["Version"];
		}

		return self::$version;
	}

	/// <function container="base/SMEnvironment" name="GetClientCacheKey" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Returns client cache key useful for forcing browser to reload CSS and JavaScript
	/// 		when cache has been invalidated using SMEnvironment::UpdateClientCacheKey().
	/// 		Usage example:
	/// 		$js = &quot;style.css?cacheKey=&quot; . SMEnvironment::GetClientCacheKey();
	/// 	</description>
	/// </function>
	private static $cacheKey = null;
	public static function GetClientCacheKey()
	{
		if (self::$cacheKey === null)
		{
			$ck = SMAttributes::GetAttribute("SMClientCacheKey");

			if ($ck === null)
			{
				$ck = SMRandom::CreateGuid();
				SMAttributes::SetAttribute("SMClientCacheKey", $ck);
			}

			self::$cacheKey = $ck;
		}

		return self::$cacheKey;
	}

	/// <function container="base/SMEnvironment" name="UpdateClientCacheKey" access="public" static="true">
	/// 	<description> Update client cache key to force client resources to load latest version </description>
	/// </function>
	public static function UpdateClientCacheKey()
	{
		$ck = SMRandom::CreateGuid();
		SMAttributes::SetAttribute("SMClientCacheKey", $ck);
		self::$cacheKey = $ck;
	}

	/// <function container="base/SMEnvironment" name="GetDebugEnabled" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if Debug Mode has been enabled, otherwise False </description>
	/// </function>
	public static function GetDebugEnabled()
	{
		$cfg = self::GetConfiguration();
		return ($cfg->GetEntry("Debug") !== null && strtolower($cfg->GetEntry("Debug")) === "true");
	}

	/// <function container="base/SMEnvironment" name="GetCloudEnabled" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if Cloud Mode has been enabled, otherwise False </description>
	/// </function>
	public static function GetCloudEnabled()
	{
		$cfg = self::GetConfiguration();
		return ($cfg->GetEntry("CloudMode") !== null && strtolower($cfg->GetEntry("CloudMode")) === "true");
	}

	/// <function container="base/SMEnvironment" name="GetConfiguration" access="public" static="true" returns="SMConfiguration">
	/// 	<description> Returns system configuration (config.xml.php) </description>
	/// 	<param name="writable" type="boolean" default="false"> Set True to have writable configuration returned </param>
	/// </function>
	public static function GetConfiguration($writable = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "writable", $writable, SMTypeCheckType::$Boolean);

		$root = self::GetSubsiteDirectory();
		return new SMConfiguration(dirname(__FILE__) . "/../" . (($root !== null) ? $root . "/" : "") . "config.xml.php", $writable);
	}

	// Directory information

	// NOTICE: The following directories does not have getter functions, since
	// they are only referenced by the framework and GUI controls: /base, /sites

	// To rename a folder (data, extensions, files, images, or templates), update the
	// relevant getter function below, and make appropriate updates to .htaccess files,
	// which reference these folders by their names (hardcoded).
	// It will also be neccessary to map requests to old folder names to new folder names
	// to ensure backward compatibility. See example in root .htaccess file.
	// Also CSS files (e.g. SMPages/editor.css and _BaseGeneric/mobile.css) are
	// likely to depend on images/fonts being accessible from hardcoded folders.

	/// <function container="base/SMEnvironment" name="GetFilesDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of files folder found in root of web application folder </description>
	/// </function>
	public static function GetFilesDirectory()
	{
		if (self::$filesDir === null)
		{
			$subSite = self::getSubSite();

			if ($subSite !== null && SMFileSystem::FileExists("sites/" . $subSite . "/files/.htaccess") === false)
				self::$filesDir = "sites/" . $subSite . "/files";
			else
				self::$filesDir = "files";

		}

		return self::$filesDir;
	}
	private static $filesDir = null;

	/// <function container="base/SMEnvironment" name="GetExtensionsDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of extensions folder found in root of web application folder </description>
	/// </function>
	public static function GetExtensionsDirectory()
	{
		return "extensions";
	}

	/// <function container="base/SMEnvironment" name="GetDataDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of data folder found in root of web application folder </description>
	/// </function>
	public static function GetDataDirectory()
	{
		$subSite = self::getSubSite();
		return (($subSite !== null) ? "sites/" . $subSite . "/" : "") . "data";
	}

	/// <function container="base/SMEnvironment" name="GetImagesDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of images folder found in root of web application folder </description>
	/// </function>
	public static function GetImagesDirectory()
	{
		return "images";
	}

	/// <function container="base/SMEnvironment" name="GetTemplatesDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of templates folder found in root of web application folder </description>
	/// </function>
	public static function GetTemplatesDirectory()
	{
		if (self::$templatesDir === null)
		{
			$subSite = self::getSubSite();

			if ($subSite !== null && count(SMFileSystem::GetFolders(dirname(__FILE__) . "/../sites/" . $subSite . "/templates")) > 0)
				self::$templatesDir = "sites/" . $subSite . "/templates";
			else
				self::$templatesDir = "templates";
		}

		return self::$templatesDir;
	}
	private static $templatesDir = null;

	/// <function container="base/SMEnvironment" name="GetSubsiteDirectory" access="public" static="true" returns="string">
	/// 	<description> Get path to subsite directory (e.g. sites/demo) - returns Null if application is not running under a subsite </description>
	/// </function>
	public static function GetSubsiteDirectory()
	{
		$subSite = self::getSubSite();
		return (($subSite !== null) ? "sites/" . $subSite : null);
	}

	/// <function container="base/SMEnvironment" name="GetSubSites" access="public" static="true" returns="string[]">
	/// 	<description> Get names of all subsites </description>
	/// </function>
	public static function GetSubSites()
	{
		return SMFileSystem::GetFolders(dirname(__FILE__) . "/../sites");
	}

	// Template

	/// <function container="base/SMEnvironment" name="GetMasterTemplate" access="public" static="true" returns="SMTemplate">
	/// 	<description> Get master template </description>
	/// </function>
	public static function GetMasterTemplate()
	{
		return self::$masterTemplate;
	}

	public static function SetMasterTemplate(SMTemplate $tpl)
	{
		self::$masterTemplate = $tpl;
	}

	// Form

	/// <function container="base/SMEnvironment" name="GetFormInstance" access="public" static="true" returns="SMForm">
	/// 	<description> Get form element instance </description>
	/// </function>
	public static function GetFormInstance()
	{
		return self::$formInstance;
	}

	public static function SetFormInstance(SMForm $form)
	{
		self::$formInstance = $form;
	}

	public static function IsSubSite()
	{
		return (self::getSubSite() !== null);
	}

	// Server configuration

	/// <function container="base/SMEnvironment" name="GetMaxUploadSize" access="public" static="true" returns="integer">
	/// 	<description> Get maximum file upload size in bytes - a value of 0 indicates that file uploading is not possible </description>
	/// </function>
	public static function GetMaxUploadSize()
	{
		// NOTICE: Upload capabilities is also determined by max_file_uploads (number of file uploads allowed).
		// Also notice that post_max_size should always be greater than upload_max_filesize.
		// Otherwise a file with a size of exactly 2 MB would leave no space for additional POST data,
		// potentially preventing data from being processed.

		$enabledStr = self::getPhpConfig("file_uploads");

		if ($enabledStr === null || ($enabledStr !== "1" && strtolower($enabledStr) !== "on"))
			return 0; // Uploads not allowed

		// Upload is enabled - max size can now be determined

		$maxUploadSizeStr = self::getPhpConfig("upload_max_filesize");
		$maxPostSizeStr = self::getPhpConfig("post_max_size");

		if ($maxUploadSizeStr === null || $maxPostSizeStr === null)
			throw new Exception("Unable to determine Max Upload Size");

		$maxUploadSize = self::getBytesFromPhpConfig($maxUploadSizeStr);
		$maxPostSize = self::getBytesFromPhpConfig($maxPostSizeStr);

		return (($maxPostSize < $maxUploadSize) ? $maxPostSize : $maxUploadSize);
	}

	// Helper functions

	private static function getValidatedValue($arrName, $arr, $key, $restriction, $exceptions)
	{
		SMTypeCheck::CheckObject(__METHOD__, "arrName", $arrName, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "arr", $arr, SMTypeCheckType::$Array);
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "restriction", $restriction, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "exceptions", $exceptions, SMTypeCheckType::$String);

		if (property_exists("SMValueRestriction", $restriction) === false)
			throw new Exception("Specified value restriction does not exist - use SMValueRestriction::Restriction");

		$val = ((isset($arr[$key]) === true) ? $arr[$key] : null);

		if ($val !== null && is_string($val) === true && SMStringUtilities::Validate($val, $restriction, $exceptions) === false)
			throw new Exception("Security exception - value of " . $arrName . "['" . $key . "'] = '" . $val . "' is in conflict with value restriction '" . $restriction . "'" . ((count($exceptions) > 0) ? " and the following characters: " . implode("", $exceptions) : ""));

		// Encode unicode characters into HEX entities for callbacks and make sure data is returned as ISO-8859-1

		self::$isCallback = ((self::$isCallback !== -1) ? self::$isCallback : (isset($_GET["SMCallback"]) ? 1 : 0));

		if ($val !== null && self::$isCallback) // Only callbacks pass data as Unicode - the code below would corrupt ISO-8859-1 strings
		{
			if (is_string($val) === true)
			{
				return SMStringUtilities::UnicodeEncode($val);
			}
			else if (is_array($val) === true)
			{
				return self::decodeArrayFromUtf8ToLatin1($val);
			}
		}

		return $val;
	}
	private static $isCallback = -1; // -1 = Undetermined, 0 = No, 1 = Yes

	private static function getSubSite()
	{
		if (self::$subSite === null)
		{
			$docRoot = self::GetDocumentRoot(); // E.g. /var/www/domain.com/web or /var/www/domain.com/web/sites/demo

			if (SMFileSystem::FolderExists($docRoot . "/base") === false) // Subsite, if base folder is not found in installation path
				self::$subSite = substr($docRoot, strrpos($docRoot, "/") + 1);
			else // Main site
				self::$subSite = "";
		}

		return ((self::$subSite !== "") ? self::$subSite : null);
	}
	private static $subSite = null;

	private static function getPhpConfig($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		$res = ini_get($key); // Returns string value on success or an empty string for Null values - returns False if the option does not exist

		if ($res === false)
			throw new Exception("PHP server configuration '" . $key . "' does not exist");

		return (($res !== "") ? $res : null);
	}

	private static function getBytesFromPhpConfig($sizeStr) // e.g. 1024 or 1KB or 4MB or 1G
	{
		SMTypeCheck::CheckObject(__METHOD__, "sizeStr", $sizeStr, SMTypeCheckType::$String);

		switch (substr($sizeStr, -1))
		{
			case 'K': case 'k': return (int)substr($sizeStr, 0, -1) * 1024;
			case 'M': case 'm': return (int)substr($sizeStr, 0, -1) * 1048576;
			case 'G': case 'g': return (int)substr($sizeStr, 0, -1) * 1073741824;
			default: return (int)$sizeStr;
		}
	}
}

?>
