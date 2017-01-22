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
	/// </function>
	public static function Post($url, SMKeyValueCollection $data = null)
	{
		SMTypeCheck::CheckObject(__METHOD__, "url", $url, SMTypeCheckType::$String);

		// Prepare data

		$data = (($data !== null) ? $data : new SMKeyValueCollection());
		$arr = array();

		foreach ($data as $key => $value)
			$arr[$key] = $value;

		$sc = stream_context_create(
			array(
				"http" => array(
					"method" => "POST",
					"header" => "Content-Type: application/x-www-form-urlencoded",
					"content" => http_build_query($arr)
				)
			)
		);

		// Perform request

		$response = file_get_contents($url, false, $sc);

		if ($response === false)
			throw new Exception("Unable to perform request to URL '" . $url . "'");

		// Return response

		return $response;
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
