<?php

class SMAutoSeoUrls extends SMExtension // Supplementary extension to SMPages and SMMenu
{
	public function Init()
	{
		if (SMEnvironment::IsSubSite() === true) // SEO URL configuration on main site also applies to subsites
			return;

		if (SMExtensionManager::ExtensionEnabled("SMPages") === false) // Make sure SMPages exists and is enabled
			return;

		// Install .htaccess or upgrade existing .htaccess if newer version is available

		// Update version number below to have .htaccess installed again with new version of Sitemagic CMS.
		// Set it to SMEnvironment::GetVersion() to always deploy a new version of the .htaccess with a new version
		// of Sitemagic CMS. Notice, however, that this will cause modifications to be lost with every new version!
		//$vers = 20140720; // Not necessarily the same as Sitemagic version - it simply represents .htaccess version!
		//$vers = 20160228; // Adds support for web shop
		$vers = 20160828; // Adds Cache Control

		if (SMAttributes::GetAttribute("SMPagesAutoSeoUrlsVersion") !== (string)$vers)
		{
			SMAttributes::SetAttribute("SMPagesAutoSeoUrlsVersion", (string)$vers);
			SMAttributes::Commit(); // Immediately commit to prevent multiple upgrades

			if ($this->baseHtAccessExists() === false)
			{
				SMLog::Log(__FILE__, __LINE__, "AutoSeoUrls: Failed, htaccess template not accessible");
				return;
			}

			$htRes = $this->installHtAccess();
			if ($htRes === false)
			{
				SMLog::Log(__FILE__, __LINE__, "AutoSeoUrls: Failed, htaccess installation failed");
				return;
			}
		}

		// Auto configure SEO friendly URLs

		if ($this->manuallyConfigured() === true || $this->autoConfigured() === true || $this->htAccessExists() === false) // Cancel if already configured or misconfigured
			return;

		// Prevent more than one attempt to auto configure SEO URLs.
		// Immediately commit attribute - checkAccessUsingSeoUrl()
		// function will otherwise cause an infinite loop since
		// Web Application is requested again.
		SMAttributes::SetAttribute("SMAutoSeoUrlsCompleted", "true");
		SMAttributes::Commit();

		// Set SMPages to use SEO URLs if Web Application responds when requested using
		// index.html, and auto update existing menu items to point to new SEO friendly URLs.
		if ($this->checkAccessUsingSeoUrl() === true)
		{
			$pages = array();
			$updateMenu = SMExtensionManager::ExtensionEnabled("SMMenu"); // Make sure SMMenu exists and is enabled

			if ($updateMenu === true)
				$pages = $this->getPages(); // Load pages BEFORE enabling SEO URLs to get old URLs

			$this->enableSeoUrls();

			if ($updateMenu === true)
				$this->updateMenu($pages);
		}
		else
		{
			SMLog::Log(__FILE__, __LINE__, "AutoSeoUrls: Failed, pages not accessible using SEO friendly URLs");
		}
	}

	// Helper functions

	private function manuallyConfigured() // Check whether SEO URLs have been manually configured
	{
		return SMAttributes::AttributeExists("SMPagesSettingsSeoUrls");
	}

	private function autoConfigured() // Check whether SEO URLs have been automatically configured
	{
		return SMAttributes::AttributeExists("SMAutoSeoUrlsCompleted");
	}

	private function htAccessExists() // Check whether .htaccess file exists
	{
		return SMFileSystem::FileExists(".htaccess");
	}

	private function baseHtAccessExists() // Check whether original .htaccess file exists
	{
		return SMFileSystem::FileExists(SMEnvironment::GetExtensionsDirectory() .  "/SMPages/htaccess");
	}

	private function installHtAccess() // Install .htaccess file
	{
		return SMFileSystem::Copy(SMEnvironment::GetExtensionsDirectory() . "/SMPages/htaccess", ".htaccess");
	}

	private function checkAccessUsingSeoUrl() // Check whether front page is accessible using SEO URL
	{
		$res = "";

		try
		{
			$res = SMRequest::Get(SMEnvironment::GetExternalUrl() . "/index.html");
		}
		catch (Exception $ex)
		{
			return false;
		}

		return (strpos($res, "<meta name=\"generator\" content=\"Sitemagic CMS\">") !== false);
	}

	private function enableSeoUrls() // Enable SEO URLs in configuration
	{
		SMAttributes::SetAttribute("SMPagesSettingsUrlType", "Filename");
		SMAttributes::SetAttribute("SMPagesSettingsSeoUrls", "true");
	}

	private function getPages() // Get pages array (key = Page Url, value = Page ID)
	{
		$pages = SMPagesLoader::GetPages();
		$arr = array();

		for ($i = 0 ; $i < count($pages) ; $i++)
			$arr[$pages[$i]->GetUrl()] = $pages[$i]->GetId();

		return $arr;
	}

	private function updateMenu($pages) // Update existing menu items to point to new SEO URLs
	{
		$links = SMMenuLoader::GetMenuItems();
		$pageId = null;
		$resetUrls = true;

		for ($i = 0 ; $i < count($links) ; $i++)
		{
			$pageId = ((isset($pages[$links[$i]->GetUrl()]) === true) ? $pages[$links[$i]->GetUrl()] : null);

			if ($pageId === null)
				continue;

			$links[$i]->SetUrl(SMPagesPage::GetPersistentByGuid($pageId)->GetUrl($resetUrls));
			$links[$i]->CommitPersistent();

			$resetUrls = false;
		}
	}


}

?>
