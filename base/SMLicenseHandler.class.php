<?php

class SMLicenseHandler
{
	public static function GetLicenseKey()
	{
		$cfg = SMEnvironment::GetConfiguration();
		return $cfg->GetEntry("LicenseKey");
	}

	public static function LicenseValid()
	{
		// WARNING!
		// Tampering with the license validation in any way is illegal,
		// and will result in legal prosecution! Please contact us at
		// Sitemagic if you have any wishes to use Sitemagic without
		// the license validation.

		// A license must be available for each domain (and subdomain)
		// the system is used on. Two license keys are usually available,
		// one for the primary domain (domain.com/xyz) and one for the
		// www subdomain (www.domain.com/xyz). Separated by semi colon (;).
		$license = self::GetLicenseKey();

		if ($license === null)
			return false;

		$licenses = explode(";", $license);
		$smPath = strtolower(SMEnvironment::GetExternalUrl());
		$token = "SitemagicCMS2012";

		foreach ($licenses as $license)
			if ((md5($smPath . $token) === $license))
				return true;

		return false;
	}
}

?>
