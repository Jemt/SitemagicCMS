<?php

function SMErrorHandler($errNo, $errMsg, $errFile, $errLine)
{
	SMTypeCheck::CheckObject(__METHOD__, "errNo", $errNo, SMTypeCheckType::$Integer);
	SMTypeCheck::CheckObject(__METHOD__, "errMsg", $errMsg, SMTypeCheckType::$String);
	SMTypeCheck::CheckObject(__METHOD__, "errFile", $errFile, SMTypeCheckType::$String);
	SMTypeCheck::CheckObject(__METHOD__, "errLine", $errLine, SMTypeCheckType::$Integer);

	// Data is passed to error handler by PHP in UTF-8 encoding!
	$errMsg = SMStringUtilities::Utf8Decode($errMsg);
	$errFile = SMStringUtilities::Utf8Decode($errFile);

	SMLog::Log($errFile, $errLine, "Error " . $errNo . ": " . $errMsg);
	return true; // Tell PHP that the error has been handled - execution will proceed
}

function SMExceptionHandler($exception) // The $exception argument may be of type Exception or Error on PHP 7 (both implements Throwable) while only of type Exception prior to PHP 7 (http://php.net/manual/en/function.set-exception-handler.php)
{
	header("HTTP/1.1 500 Internal Server Error");
	header("Content-Type: text/html; charset=ISO-8859-1");

	echo "<b>An unhandled error occured</b><br><br>";
	echo SMStringUtilities::HtmlEncode($exception->getMessage());
	echo "<br><br><b>Stack trace</b><br><pre>";
	echo SMStringUtilities::HtmlEncode($exception->getTraceAsString());
	echo "</pre>";

	// Make sure all file locks have been released.
	// This is no longer done automatically by fclose(..),
	// which previously got fired when script ended.

	try
	{
		// SMDataSourceCache implements interface SMIDataSourceCache
		$dataSourceNames = SMDataSourceCache::GetInstance()->GetDataSourceNames();
		$dataSource = null;

		// Make sure all data sources has been unlocked
		foreach ($dataSourceNames as $dataSourceName)
		{
			$dataSource = new SMDataSource($dataSourceName);
			$dataSource->Unlock();
		}
	}
	catch (Exception $ex)
	{
		echo "<br>";
		echo "<b>WARNING</b>: Unable to release data source file locks during exception handling.";
		echo "<br><br>";

		echo "<b>An unhandled error occured</b><br><br>";
		echo SMStringUtilities::HtmlEncode($ex->getMessage());
		echo "<br><br><b>Stack trace</b><br><pre>";
		echo SMStringUtilities::HtmlEncode($ex->getTraceAsString());
		echo "</pre>";

		echo "<br>";
		echo "It may be necessary to delete all *.lock files from the data folder on the server.";
	}
}

/// <container name="base/SMLog">
/// 	Class useful for logging warnings and errors.
/// 	Log entries are written to the SMLog data source.
///
/// 	SMLog::Log(__FILE__, __LINE__, &quot;Unable to load data - invalid user account&quot;);
/// </container>
class SMLog
{
	/// <function container="base/SMLog" name="Log" access="public" static="true">
	/// 	<description> Log message og error </description>
	/// 	<param name="errFile" type="string">
	/// 		Specify name of file from where message originates.
	/// 		It is recommended to simply specify __FILE__ which
	/// 		holdes the name of the current file being interpreted.
	/// 	</param>
	/// 	<param name="errLine" type="integer">
	/// 		Specify line number from where message originates.
	/// 		It is recommended to simply specify __LINE__ which
	/// 		holdes the number of the line currently being interpreted.
	/// 	</param>
	/// 	<param name="errMsg" type="string"> Message or error to log </param>
	/// </function>
	public static function Log($errFile, $errLine, $errMsg)
	{
		SMTypeCheck::CheckObject(__METHOD__, "errFile", $errFile, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "errLine", $errLine, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "errMsg", $errMsg, SMTypeCheckType::$String);

		$data = new SMKeyValueCollection();
		$data["datetime"] = date("Y-m-d H:i:s");
		$data["file"] = $errFile;
		$data["line"] = (string)$errLine;
		$data["message"] = $errMsg;

		$ds = new SMDataSource("SMLog");

		if (SMDataSource::GetDataSourceType() === SMDataSourceType::$Xml)
			$ds->Lock();

		$ds->Insert($data);
		$ds->Commit();
	}

	/// <function container="base/SMLog" name="LogDeprecation" access="public" static="true">
	/// 	<description> Log use of deprecated function </description>
	/// 	<param name="depClass" type="string">
	/// 		Specify name of class containing deprecated function.
	/// 		It is recommended to simply specify __CLASS__ which
	/// 		holdes the name of the current class having a function executed.
	/// 	</param>
	/// 	<param name="depFunc" type="string">
	/// 		Specify name of function that has been deprecated.
	/// 		It is recommended to simply specify __FUNCTION__ which
	/// 		holdes the name of the function currently being executed.
	/// 	</param>
	/// 	<param name="newClass" type="string" default="String.Empty">
	/// 		Optionally specify name of class containing replacement function
	/// 	</param>
	/// 	<param name="newFunc" type="string" default="String.Empty">
	/// 		Optionally specify name of replacement function
	/// 	</param>
	/// </function>
	public static function LogDeprecation($depClass, $depFunc, $newClass = "", $newFunc = "")
	{
		SMTypeCheck::CheckObject(__METHOD__, "depClass", $depClass, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "depFunc", $depFunc, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "newClass", $newClass, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "newFunc", $newFunc, SMTypeCheckType::$String);

		// Only log once per session
		if (SMEnvironment::GetSessionValue(md5($depClass . $depFunc . $newClass . $newFunc)) === null)
		{
			$msg = "Function '" . $depFunc . "' on class '" . $depClass . "' is deprecated";
			$msg .= (($newClass !== "" && $newFunc !== "") ? " - use function '" . $newFunc . "' on class '" . $newClass . "' instead" : "");

			self::Log(__FILE__, __LINE__, $msg);
			SMEnvironment::SetSession(md5($depClass . $depFunc . $newClass . $newFunc), "true");
		}
	}
}

?>
