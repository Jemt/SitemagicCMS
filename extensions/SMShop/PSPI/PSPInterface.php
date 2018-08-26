<?php

// ======================================================================================
// Payment Service Provider Interface
// ======================================================================================

/// <container name="PSPI">
/// 	Interface exposing payment functionality
/// </container>
interface PSPI
{
	/// <function container="PSPI" name="RedirectToPaymentForm" access="public">
	/// 	<description> Redirects user to payment form/window </description>
	/// 	<param name="orderId" type="string"> Unique ID identifying payment </param>
	/// 	<param name="amount" type="integer"> Order amount in smallest possible unit (e.g. Cents for USD) </param>
	/// 	<param name="currency" type="string"> Currency in the format defined by ISO 4217 (e.g. USD, GBP, or USD) </param>
	/// 	<param name="continueUrl" type="string"> URL to which user is redirected after completing payment - e.g. a receipt </param>
	/// 	<param name="callbackUrl" type="string">
	/// 		URL called asynchronously when payment is successfully carried through.
	/// 		Use PSP::GetCallbackData() to obtain OrderId, TransactionId, Amount, and Currency.
	/// 	</param>
	/// </function>
	public function RedirectToPaymentForm($orderId, $amount, $currency, $continueUrl = null, $callbackUrl = null);

	/// <function container="PSPI" name="CapturePayment" access="public" returns="boolean">
	/// 	<description> Capture payment previously authorized using payment form - returns True on success, otherwise False </description>
	/// 	<param name="transactionId" type="string"> Unique ID identifying transaction </param>
	/// 	<param name="amount" type="integer"> Amount to withdraw in smallest possible unit (e.g. Cents for USD) </param>
	/// </function>
	public function CapturePayment($transactionId, $amount);

	/// <function container="PSPI" name="CancelPayment" access="public" returns="boolean">
	/// 	<description> Cancel payment previously authorized using payment form - returns True on success, otherwise False </description>
	/// 	<param name="transactionId" type="string"> Unique ID identifying transaction </param>
	/// </function>
	public function CancelPayment($transactionId);
}

// ======================================================================================
// Payment Service Provider Wrapper (PSPW) - wraps PSPM
// ======================================================================================

class PSPW implements PSPI
{
	private $pspm = null;

	public function __construct(PSPI $pspModule)
	{
		$this->pspm = $pspModule;
	}

	public function RedirectToPaymentForm($orderId, $amount, $currency, $continueUrl = null, $callbackUrl = null)
	{
		if (is_string($orderId) === false || is_integer($amount) === false || is_string($currency) === false || ($continueUrl !== null && is_string($continueUrl) === false) || ($callbackUrl !== null && is_string($callbackUrl) === false))
			throw new Exception("Invalid argument(s) passed to RedirectToPaymentForm(string, integer, string[, string[, string]])");

		//if (strpos($continueUrl, "?") !== false || strpos($callbackUrl, "?") !== false)
		//	throw new Exception("Invalid callback URL(s) passed - URL parameters are not allowed");

		$this->pspm->RedirectToPaymentForm($orderId, $amount, $currency, $continueUrl, $callbackUrl);
	}

	public function CapturePayment($transactionId, $amount)
	{
		if (is_string($transactionId) === false || is_int($amount) === false)
			throw new Exception("Invalid argument(s) passed to CapturePayment(string, integer)");

		return $this->pspm->CapturePayment($transactionId, $amount);
	}

	public function CancelPayment($transactionId)
	{
		if (is_string($transactionId) === false)
			throw new Exception("Invalid argument passed to CancelPayment(string)");

		return $this->pspm->CancelPayment($transactionId);
	}
}

// ======================================================================================
// PSP Helper class
// ======================================================================================

/// <container name="PSP">
/// 	Class exposing functionality useful to Payment Service Provider Modules
/// </container>
class PSP
{
	private static $baseConfig = null;
	private static $configurations = array();
	private static $currencies = null;
	private static $numCurrencies = null;

	// Factory

	/// <function container="PSP" name="GetPaymentProvider" access="public" static="true" returns="PSPI">
	/// 	<description> Get instance of Payment Service Provider Module which implements the PSPI interface </description>
	/// 	<param name="provider" type="string"> Name of Payment Service Provider Module </param>
	/// </function>
	public static function GetPaymentProvider($provider)
	{
		if (is_string($provider) === false)
			throw new Exception("Invalid argument passed to GetPaymentProvider(string)");

		$path = dirname(__FILE__) . "/" . $provider . "/PSPM.php";

		if (is_file($path) === false)
			throw new Exception("Unable to load PSPM from '" . $path . "' - not found");

		require_once($path);

		if (class_exists($provider, false) === false)
			throw new Exception("Unable to create instance of PSPM class '" . $provider . "' - not defined");

		return new PSPW(new $provider()); // Both PSPM and PSPW implements PSPI interface
	}

	// Communication

	/// <function container="PSP" name="Post" access="public" static="true" returns="string">
	/// 	<description> Post data to given URL </description>
	/// 	<param name="url" type="string"> Target URL </param>
	/// 	<param name="data" type="string[]" default="null"> Associative array contain data as key/value pairs </param>
	/// 	<param name="headers" type="string[]" default="null"> Optional associative array contain headers as key/value pairs </param>
	/// </function>
	public static function Post($url, $data = null, $headers = null)
	{
		if (is_string($url) === false)
			throw new Exception("Invalid argument(s) passed to Post(string, string[], string[]");

		foreach ((($data !== null) ? $data : array()) as $key => $value)
		{
			if (is_string($key) === false || is_string($value) === false)
				throw new Exception("Invalid argument(s) passed to Post(string, string[], string[])");
		}

		foreach ((($headers !== null) ? $headers : array()) as $key => $value)
		{
			if (is_string($key) === false || is_string($value) === false)
				throw new Exception("Invalid argument(s) passed to Post(string, string[], string[])");
		}

		// Prepare header(s)

		$header = "";
		$contentTypeSet = false;

		if ($headers !== null)
		{
			foreach ($headers as $key => $value)
			{
				if (strtolower($key) === "content-type")
					$contentTypeSet = true;

				$header .= (($header !== "") ? "\r\n" : "") . $key . ": " . $value;
			}
		}

		if ($contentTypeSet === false) // Avoid error: Content-type not specified assuming application/x-www-form-urlencoded
		{
			$header .= (($header !== "") ? "\r\n" : "") . "Content-Type: application/x-www-form-urlencoded";
		}

		// Create stream context

		$sc = stream_context_create(
			array(
				"http" => array(
					"method" => "POST",
					"header" => $header,
					"content" => http_build_query((($data !== null) ? $data : array())),
					"ignore_errors" => true // Prevent errors caused by HTTP status codes such as "201 Created" on older versions of PHP (found with PHP 5.2.17 on MAMP Pro) - this will make file_get_contents() return error message (e.g. '404 Not Found') rather than False, but strangely return the actual response for "201 Created" which previously caused an error
				)
			)
		);

		// Perform request

		$response = file_get_contents($url, false, $sc); // https:// requires openssl to be enabled (http://php.net/manual/en/wrappers.http.php)

		if ($response === false)
			throw new Exception("Request to URL '" . $url . "' failed");

		// Check response (compensate for ignore_errors=true in stream context above)

		$statusCode = -1;
		$matches = array();

		foreach ($http_response_header as $responseHeader) // $http_response_header is "magically" created by PHP when file_get_contents(..) is invoked
		{
			if (preg_match('/HTTP\/.*? (.*) .*/i', $responseHeader, $matches, PREG_OFFSET_CAPTURE) === 1) // $matches[0][0] = full match, $matches[0][1] = full match position, $matches[1][0] = capture group (status code), $matches[1][1] = capture group position
			{
				$statusCode = (int)$matches[1][0];
				break;
			}
		}

		if ($statusCode < 200 || $statusCode > 299)
		{
			throw new Exception("Request to URL '" . $url . "' failed - HTTP Status Code: '" . (string)$statusCode . "'");
		}

		// Return response

		return $response; //return array("Headers" => $http_response_header, "Response" => $response);
	}

	/// <function container="PSP" name="RedirectToContinueUrl" access="public" static="true">
	/// 	<description> Redirect user to Continue URL passed to RedirectToPaymentForm(..) </description>
	/// 	<param name="url" type="string"> Continue URL </param>
	/// </function>
	public static function RedirectToContinueUrl($url)
	{
		if (is_string($url) === false)
			throw new Exception("Invalid argument passed to RedirectToContinueUrl(string)");

		//if (strpos($url, "?") !== false) // Prevent PSPM from appending arguments
			//throw new Exception("Invalid Continue URL passed - URL parameters are not allowed");

		header("location: " . $url);
		exit;
	}

	/// <function container="PSP" name="InvokeCallback" access="public" static="true" returns="string">
	/// 	<description> Invoke callback - used by PSPM to invoke callbacks passed to RedirectToPaymentForm(..) </description>
	/// 	<param name="callbackUrl" type="string"> Callback URL </param>
	/// 	<param name="transactionId" type="string">
	/// 		Transaction ID used for further processing (e.g. Capture/Cancel).
	/// 		Pass empty string if further processing is not supported.
	/// 	</param>
	/// 	<param name="orderId" type="string"> Order ID </param>
	/// 	<param name="amount" type="integer"> Order amount in smallest possible unit (e.g. Cents for USD) </param>
	/// 	<param name="currency" type="string"> Currency in the format defined by ISO 4217 (e.g. USD, GBP, or USD) </param>
	/// </function>
	public static function InvokeCallback($callbackUrl, $transactionId, $orderId, $amount, $currency)
	{
		if (is_string($callbackUrl) === false || is_string($transactionId) === false || is_string($orderId) === false || is_int($amount) === false || is_string($currency) === false)
			throw new Exception("Invalid argument(s) passed to InvokeCallback(string, string, string, integer, string)");

		if (is_numeric($currency) === true)
			$currency = self::NumericValueToCurrencyCode($currency); // Ensure consistency: Always pass currency name (e.g. USD) rather than numeric value (e.g. 840)

		$data = array();
		$data["TransactionId"] = $transactionId;
		$data["OrderId"] = $orderId;
		$data["Amount"] = (string)$amount;
		$data["Currency"] = $currency;
		$data["Checksum"] = md5(self::getEncryptionKey() . $transactionId . $orderId . $amount . $currency);

		return self::Post($callbackUrl, $data);
	}

	/// <function container="PSP" name="GetCallbackData" access="public" static="true" returns="object[]">
	/// 	<description>
	/// 		Securely obtain data sent to application callback specified in RedirectToPaymentForm(..).
	/// 		This function takes care of ensuring data integrity - an exception is thrown
	/// 		if data has been tampered with.
	/// 		Data is returned in an associative array containing the following keys:
	/// 		 - TransactionId (string value): Used to capture or cancel payment - empty string if not supported.
	/// 		 - OrderId (string value).
	/// 		 - Amount (integer value).
	/// 		 - Currency (string value): ISO 4217 (e.g. USD, GBP, or USD).
	/// 	</description>
	/// </function>
	public static function GetCallbackData()
	{
		$transactionId = (isset($_POST["TransactionId"]) ? $_POST["TransactionId"] : null);
		$orderId = (isset($_POST["OrderId"]) ? $_POST["OrderId"] : null);
		$amount = (isset($_POST["Amount"]) ? (int)$_POST["Amount"] : -1);
		$currency = (isset($_POST["Currency"]) ? $_POST["Currency"] : null);
		$checksum = (isset($_POST["Checksum"]) ? $_POST["Checksum"] : null);
		$newChecksum = md5(self::getEncryptionKey() . $transactionId . $orderId . $amount . $currency);

		if ($newChecksum !== $checksum)
			throw new Exception("SecurityException: Integrity check failed - mismatching checksums");

		return array("TransactionId" => $transactionId, "OrderId" => $orderId, "Amount" => (int)$amount, "Currency" => $currency);
	}

	// Configuration

	/// <function container="PSP" name="GetConfig" access="public" static="true" returns="string[]">
	/// 	<description>
	/// 		Used by PSPM to obtain associative configuration array
	/// 		defined in Config.php with key/value pairs </description>
	/// 	<param name="provider" type="string"> Name of PSPM </param>
	/// </function>
	public static function GetConfig($provider)
	{
		if (is_string($provider) === false)
			throw new Exception("Invalid argument passed to GetConfig(string)");

		self::ensureProviderConfig($provider);
		return self::$configurations[$provider];
	}

	/// <function container="PSP" name="GetProviderUrl" access="public" static="true" returns="string">
	/// 	<description> Returns external URL to folder containing PSPM </description>
	/// 	<param name="provider" type="string"> Name of PSPM </param>
	/// </function>
	public static function GetProviderUrl($provider)
	{
		if (is_string($provider) === false)
			throw new Exception("Invalid argument passed to GetProviderUrl(string)");

		self::ensureBaseConfig();
		return self::$baseConfig["BaseUrl"] . "/" . $provider;
	}

	// Conversion

	/// <function container="PSP" name="CurrencyCodeToNumericValue" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Converts a currency code (e.g. USD) to its numeric equivalent (e.g. 840).
	/// 		Value is returned as string to preserve any leading zeros.
	/// 	</description>
	/// 	<param name="currencyCode" type="string"> Alphabetical currency code as defined by ISO 4217 </param>
	/// </function>
	public static function CurrencyCodeToNumericValue($currencyCode)
	{
		if (is_string($currencyCode) === false)
			throw new Exception("Invalid argument passed to CurrencyCodeToNumericValue(string)");

		self::ensureCurrencies();

		if (isset(self::$currencies[$currencyCode]) === false)
			throw new Exception("No numeric equivalent to '" . $currencyCode . "' found - pass a valid value such as USD, EUR, GBP, etc.");

		return self::$currencies[$currencyCode]; // Numeric values are stored as strings to preserve any leading zeros (e.g. ALL = 008)
	}

	/// <function container="PSP" name="NumericValueToCurrencyCode" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Converts a numeric concurrency representation (e.g. 840) to its alphabetical equivalent (e.g. USD)
	/// 	</description>
	/// 	<param name="numericCurrencyValue" type="string"> Numeric currency representation as defined by ISO 4217 </param>
	/// </function>
	public static function NumericValueToCurrencyCode($numericCurrencyValue)
	{
		if (is_string($numericCurrencyValue) === false)
			throw new Exception("Invalid argument passed to NumericValueToCurrencyCode(string)");

		self::ensureCurrencies();

		if (isset(self::$numCurrencies[$numericCurrencyValue]) === false)
			throw new Exception("No currency name equivalent to '" . $numericCurrencyValue . "' found - pass a valid numeric currency value such as 840 for USD, 978 for EUR, 826 for GBP, etc.");

		return self::$numCurrencies[$numericCurrencyValue]; // Numeric values are stored as strings to preserve any leading zeros (e.g. ALL = 008)
	}

	// Logging

	/// <function container="PSP" name="IsLoggingEnabled" access="public" static="true" returns="boolean">
	/// 	<description> Returns a value indicating whether logging is enabled or not </description>
	/// </function>
	public static function IsLoggingEnabled()
	{
		self::ensureBaseConfig();
		return (self::$baseConfig["LogFile"] !== "" && self::$baseConfig["LogMode"] !== "Disabled");
	}

	/// <function container="PSP" name="GetLogMode" access="public" static="true" returns="string">
	/// 	<description> Returns a value indicating the current logging mode (Disabled, Simple, or Full) </description>
	/// </function>
	public static function GetLogMode()
	{
		self::ensureBaseConfig();
		return self::$baseConfig["LogMode"];
	}

	/// <function container="PSP" name="Log" access="public" static="true">
	/// 	<description> Log message to log file - nothing will be logged if logging has been disabled </description>
	/// 	<param name="msg" type="string"> Message to log </param>
	/// </function>
	public static function Log($msg)
	{
		self::ensureBaseConfig();

		if (self::IsLoggingEnabled() === true)
		{
			$path = self::$baseConfig["LogFile"];

			// Make sure log file can be written

			$match = array(); // 0 = full match, 1 = first capture group (folder path), 2 = second capture group (filename)
			preg_match("/(.*)\\/(.*)/", $path, $match);

			$folderPath = "";
			$filename = "";

			if (count($match) === 0) // E.g. $path = PSPI_Log.txt
			{
				$folderPath = dirname(__FILE__);
				$filename = $path;
			}
			else // E.g. $path = path/to/logs/PSPI_Log.txt or $path = path/to/logs/
			{
				$folderPath = $match[1];
				$filename = ($match[2] !== "" ? $match[2] : "PSPI.log");
			}

			if ($folderPath[0] !== "/") // Path relative to PSPI package (considered absolute if starting with a slash)
				$folderPath = dirname(__FILE__) . "/" . $folderPath;

			$path = $folderPath . "/" . $filename;

			if (file_exists($path) === true && is_writable($path) === false)
				throw new Exception("Log file '" . $path . "' is not writable");
			else if (is_writable($folderPath) === false)
				throw new Exception("Log file cannot be created - folder '" . $folderPath . "' is not writable");

			// Create log entry

			// Prevent annoying warning:
			// Strict Standards: date() [function.date]: It is not safe to rely on the system's timezone settings
			date_default_timezone_set("UTC");

			$log = "";
			$log .= "\n====================================";
			$log .= "\nTime: " . date("Y-m-d H:i:s");
			$log .= "\n====================================";
			$log .= "\n" . $msg;

			if (self::$baseConfig["LogMode"] === "Full")
			{
				$log .= "\n";
				$log .= "\$_GET:" . print_r($_GET, true);
				$log .= "\$_POST:" . print_r($_POST, true);
				//$log .= "\$_SERVER:" . print_r($_SERVER, true);
			}
			else
			{
				$log .= "\n";
			}

			// Save log entry

			@file_put_contents($path, $log, FILE_APPEND);
		}
	}

	/// <function container="PSP" name="GetTestMode" access="public" static="true" returns="boolean">
	/// 	<description>
	/// 		Returns a value indicating whether transactions should be carried
	/// 		out in test mode, meaning no money should be charged when testing.
	/// 	</description>
	/// </function>
	public static function GetTestMode()
	{
		self::ensureBaseConfig();
		return self::$baseConfig["TestMode"];
	}

	// Private

	private static function ensureBaseConfig()
	{
		if (self::$baseConfig === null)
		{
			require_once(dirname(__FILE__) . "/Config.php");

			if (isset($config["ConfigPath"]) && $config["ConfigPath"] !== "") // Handle alternative configuration folder
			{
				$configPath = $config["ConfigPath"];
				$configPath = (($configPath[0] !== "/") ? dirname(__FILE__) . "/" : "") . $configPath; // Turn into absolute path if relative path was specified
				$configPath = (($configPath[strlen($configPath) - 1] === "/") ? substr($configPath, 0, -1) : $configPath); // Remove trailing slash if defined
				$configFile = $configPath . "/Config.php";

				if (file_exists($configFile) === false)
					throw new Exception("PSPI configuration file '" . $configFile . "' not found");

				require_once($configFile);
				$config["ConfigPath"] = $configPath;
			}

			self::$baseConfig = $config;
		}
	}

	private static function ensureProviderConfig($provider)
	{
		if (is_string($provider) === false)
			throw new Exception("Invalid argument passed to ensureProviderConfig(string)");

		if (isset(self::$configurations[$provider]) === false)
		{
			self::ensureBaseConfig();

			if (isset(self::$baseConfig["ConfigPath"]) && self::$baseConfig["ConfigPath"] !== "")
				require_once(self::$baseConfig["ConfigPath"] . "/" . $provider . "/Config.php");
			else
				require_once(dirname(__FILE__) . "/" . $provider . "/Config.php");

			self::$configurations[$provider] = $config;
		}
	}

	private static function ensureCurrencies()
	{
		if (self::$currencies === null)
		{
			require_once(dirname(__FILE__) . "/Currencies.php");

			self::$currencies = $currencies;

			self::$numCurrencies = array();
			foreach ($currencies as $key => $value)
				self::$numCurrencies[$value] = $key;
		}
	}

	private static function getEncryptionKey()
	{
		self::ensureBaseConfig();
		return self::$baseConfig["EncryptionKey"];
	}
}

// ======================================================================================
// Error handling - only in effect if logging has been configured
// ======================================================================================

function PSPErrorHandler($errNo, $errMsg, $errFile, $errLine)
{
	PSP::Log("PSP - unhandled error occurred:\nError ID: " . $errNo . "\nError message: " . $errMsg . "\nFile: " . $errFile . "\nLine: " . $errLine);
	return false; // Return control to PHP's error handler
}

function PSPExceptionHandler($ex) // The $ex argument may be of type Exception or Error on PHP 7 (both implements Throwable) while only of type Exception prior to PHP 7 (http://php.net/manual/en/function.set-exception-handler.php)
{
	$errNo = $ex->getCode();
	$errMsg = $ex->getMessage();
	$errFile = $ex->getFile();
	$errLine = $ex->getLine();

	try
	{
		PSP::Log("PSP - unhandled exception occurred:\nError ID: " . $errNo . "\nError message: " . $errMsg . "\nFile: " . $errFile . "\nLine: " . $errLine . (PSP::GetLogMode() === "Full" ? "\nStackTrace: " . $ex->getTraceAsString() : ""));
	}
	catch (Exception $excp)
	{
	}

	header("HTTP/1.1 500 Internal Server Error");
	//header("Content-Type: text/html; charset=ISO-8859-1");

	echo "<b>An unhandled exception occurred</b><br><br>";
	echo $ex->getMessage();
	echo "<br><br><b>Stack trace</b><br><pre>";
	echo $ex->getTraceAsString();
	echo "</pre>";
}

if (PSP::IsLoggingEnabled() === true)
{
	error_reporting(E_ALL | E_STRICT);
	ini_set("display_errors", 1);

	set_error_handler("PSPErrorHandler");
	set_exception_handler("PSPExceptionHandler");
}

?>
