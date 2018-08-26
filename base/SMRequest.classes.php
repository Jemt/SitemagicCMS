<?php

/// <container name="base/SMRequest">
/// 	Class used to send and receive data through GET and POST.
///
/// 	// Get data from website
/// 	$response = SMRequest::Get("http://domain.com/feed/TvChannels.html");
///
/// 	// Send data to website (create new user account)
/// 	$kvc = new SMKeyValueCollection();
/// 	$kvc["mail"] = "casper@domain.com";
/// 	$kvc["username"] = "Casper";
/// 	$kvc["password"] = "MySecretPassword"; // Encryption seems like a good idea
/// 	$response = SMRequest::Post("http://domain.com/signup.php", $kvc);
/// </container>
class SMRequest
{
	/// <function container="base/SMRequest" name="Post" access="public" static="true" returns="string">
	/// 	<description> Post data to specified URL. Response is returned. </description>
	/// 	<param name="url" type="string"> URL </param>
	/// 	<param name="data" type="SMKeyValueCollection" default="null"> Optionally specify data to send </param>
	/// 	<param name="headers" type="SMKeyValueCollection" default="null"> Optionally specify custom headers </param>
	/// </function>
	public static function Post($url, SMKeyValueCollection $data = null, SMKeyValueCollection $headers = null)
	{
		SMTypeCheck::CheckObject(__METHOD__, "url", $url, SMTypeCheckType::$String);

		// Prepare data

		$data = (($data !== null) ? $data : new SMKeyValueCollection());
		$arr = array();

		foreach ($data as $key => $value)
			$arr[$key] = $value;

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
					"content" => http_build_query((($arr !== null) ? $arr : array())),
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

	/// <function container="base/SMRequest" name="Get" access="public" static="true" returns="string">
	/// 	<description> Get data from specified URL </description>
	/// 	<param name="url" type="string"> URL </param>
	/// 	<param name="data" type="SMKeyValueCollection" default="null"> Optionally specify query string parameters </param>
	/// </function>
	public static function Get($url, SMKeyValueCollection $data = null)
	{
		SMTypeCheck::CheckObject(__METHOD__, "url", $url, SMTypeCheckType::$String);

		// Prepare data

		$data = (($data !== null) ? $data : new SMKeyValueCollection());
		$arr = array();

		foreach ($data as $key => $value)
			$arr[$key] = $value;

		$urlArgs = http_build_query($arr);

		// Perform request

		$response = file_get_contents($url . (($urlArgs !== "") ? "?" : "") . $urlArgs);

		if ($response === false)
			throw new Exception("Unable to perform request to URL '" . $url . "'");

		// Return response

		return $response;
	}
}

/// <container name="base/SMRedirect">
/// 	Class used to redirect user to a different page or website.
///
/// 	SMRedirect::Redirect("http://domain.com/help");
/// </container>
class SMRedirect
{
	/// <function container="base/SMRedirect" name="Redirect" access="public" static="true">
	/// 	<description> Redirect user to specified URL. Invoking this function stops further code execution. </description>
	/// 	<param name="url" type="string"> URL </param>
	/// </function>
	public static function Redirect($url)
	{
		SMTypeCheck::CheckObject(__METHOD__, "url", $url, SMTypeCheckType::$String);

		// Commit cached data that has not yet been committed

		if (SMAttributes::CollectionChanged() === true)
			SMAttributes::Commit();

		// SMDataSourceCache implements interface SMIDataSourceCache
		$dataSourceNames = SMDataSourceCache::GetInstance()->GetDataSourceNames();
		$dataSource = null;

		foreach ($dataSourceNames as $dataSourceName)
		{
			$dataSource = new SMDataSource($dataSourceName);
			$dataSource->Commit();
		}

		// Redirect

		header("location: " . $url);
		exit;
	}
}

?>
