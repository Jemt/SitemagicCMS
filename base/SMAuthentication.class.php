<?php

/// <container name="base/SMAuthentication">
/// 	Class provides functionality used to switch between administration mode
/// 	and ordinary mode. Extensions may use this class to check whether user
/// 	is authorized or not, to determine what functionality to enable.
/// 	User credentials are stored in config.xml.php.
///
/// 	$msg = "User logged in: " . ((SMAuthentication::Authorized() === true) ? "Yes" : "No");
///
/// 	SMAuthentication::Login("Admin", "MyPassword"); // Authorize as "Admin"
/// 	SMAuthentication::Logout(); // Revoke authorization
/// </container>
class SMAuthentication
{
	private function __construct()
	{
	}

	/// <function container="base/SMAuthentication" name="Login" access="public" static="true" returns="boolean">
	/// 	<description> Authorize user. Returns True on success, otherwise False. </description>
	/// 	<param name="username" type="string"> Username </param>
	/// 	<param name="password" type="string"> Password </param>
	/// </function>
	public static function Login($username, $password)
	{
		SMTypeCheck::CheckObject(__METHOD__, "username", $username, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "password", $password, SMTypeCheckType::$String);

		$userInfo = self::getUserInfo();
		$usr = $userInfo["username"];
		$psw = $userInfo["password"];

		if ($username === $usr && $password === $psw)
		{
			SMEnvironment::SetSession("SitemagicLogin", $username . ";" . $password);
			return true;
		}
		else
		{
			SMEnvironment::DestroySession("SitemagicLogin");
			return false;
		}
	}

	/// <function container="base/SMAuthentication" name="Logout" access="public" static="true">
	/// 	<description> Revoke authorization </description>
	/// </function>
	public static function Logout()
	{
		SMEnvironment::DestroySession("SitemagicLogin");
	}

	/// <function container="base/SMAuthentication" name="Authorized" access="public" static="true" returns="boolean">
	/// 	<description> Check whether user is authorized. Returns True if authorized, otherwise False </description>
	/// </function>
	public static function Authorized()
	{
		if (SMEnvironment::GetSessionValue("SitemagicLogin") === null)
			return false;

		$userInfoSession = explode(";", SMEnvironment::GetSessionValue("SitemagicLogin"));
		$userInfo = self::getUserInfo();

		if ($userInfoSession[0] !== $userInfo["username"] || $userInfoSession[1] !== $userInfo["password"])
			return false;

		return true;
	}

	private static function getUserInfo()
	{
		$config = SMEnvironment::GetConfiguration();
		$usr = $config->GetEntry("Username");
		$psw = $config->GetEntry("Password");

		if ($usr === null || $psw === null)
			throw new Exception("Username and password must both be specified in configuration file");

		return array("username" => $usr, "password" => $psw);
	}
}

?>
