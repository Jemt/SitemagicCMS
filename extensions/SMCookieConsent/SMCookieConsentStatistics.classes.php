<?php

class SMCookieConsentStatistics
{
	public static function UpdateModuleStats($moduleName, $accepted)
	{
		SMTypeCheck::CheckObject(__METHOD__, "moduleName", $moduleName, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "accepted", $accepted, SMTypeCheckType::$Boolean);

		$db = new SMDataSource("SMCookieConsent");
		$db->Lock();

		$modules = $db->Select("*", "name = '" . $db->Escape($moduleName) . "'");

		if (count($modules) === 1)
		{
			$module = $modules[0];
			$module["acceptedall"] = (string)((int)$module["acceptedall"] + ($accepted === true ? 1 : 0));
			$module["rejectedall"] = (string)((int)$module["rejectedall"] + ($accepted === false ? 1 : 0));
			$module["acceptedperiod"] = (string)((int)$module["acceptedperiod"] + ($accepted === true ? 1 : 0));
			$module["rejectedperiod"] = (string)((int)$module["rejectedperiod"] + ($accepted === false ? 1 : 0));

			$db->Update($module, "name = '" . $db->Escape($moduleName) . "'");
			$db->Commit();
		}
		else
		{
			$db->Unlock();
		}
	}

	public static function ClearModuleStatsForCurrentPeriod($moduleName)
	{
		SMTypeCheck::CheckObject(__METHOD__, "moduleName", $moduleName, SMTypeCheckType::$String);

		$db = new SMDataSource("SMCookieConsent");
		$db->Lock();

		$modules = $db->Select("*", "name = '" . $db->Escape($moduleName) . "'");

		if (count($modules) === 1)
		{
			$module = $modules[0];
			$module["acceptedperiod"] = "0";
			$module["rejectedperiod"] = "0";

			$db->Update($module, "name = '" . $db->Escape($moduleName) . "'");
			$db->Commit();
		}
		else
		{
			$db->Unlock();
		}
	}

	public static function GetStatistics($moduleName)
	{
		SMTypeCheck::CheckObject(__METHOD__, "moduleName", $moduleName, SMTypeCheckType::$String);

		$db = new SMDataSource("SMCookieConsent");

		$modules = $db->Select("*", "name = '" . $db->Escape($moduleName) . "'");

		if (count($modules) === 1)
		{
			$module = $modules[0];
			return new SMCookieConsentModuleStats((int)$module["acceptedperiod"], (int)$module["rejectedperiod"]);
		}

		return null;
	}
}

class SMCookieConsentModuleStats
{
	private $accepted = 0;
	private $rejected = 0;

	public function __construct($accepted, $rejected)
	{
		SMTypeCheck::CheckObject(__METHOD__, "accepted", $accepted, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "rejected", $rejected, SMTypeCheckType::$Integer);

		$this->accepted = $accepted;
		$this->rejected = $rejected;
	}

	public function GetAccepted()
	{
		return $this->accepted;
	}

	public function GetRejected()
	{
		return $this->rejected;
	}
}

?>