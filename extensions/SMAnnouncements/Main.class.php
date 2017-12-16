<?php

class SMAnnouncements extends SMExtension
{
	private $lang = null;

	public function GetExecutionModes()
	{
		return array(SMExecutionMode::$Shared, SMExecutionMode::$Dedicated);
	}

	public function Init()
	{
		// Dedicated execution mode used when requesting announcement server (through AJAX)
		if ($this->context->GetExecutionMode() === SMExecutionMode::$Dedicated)
			return;
	}

	public function Render()
	{
		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->getTranslation("Title")));

		if (SMEnvironment::GetQueryValue("SMAnnouncementsFetch") === null)
		{
			$this->SetIsIntegrated(true);
			return $this->renderScript();
		}
		else
		{
			$this->context->GetTemplate()->SetContent($this->request());
			return "";
		}
	}

	private function renderScript()
	{
		return "
		<h1>" . $this->getTranslation("Title") . "</h1>
		<div id=\"SMAnnouncementsContent\">" . $this->getTranslation("Loading") . "</div>

		<script type=\"text/javascript\">
		var request = null;
		SMEventHandler.AddEventHandler(window, \"load\", smAnnouncementsStart)

		function smAnnouncementsStart()
		{
			request = new SMHttpRequest('" . SMExtensionManager::GetExtensionUrl("SMAnnouncements", SMTemplateType::$Basic, SMExecutionMode::$Dedicated) . "&SMAnnouncementsFetch', true);
			request.SetStateListener(smAnnouncementsReceiver);
			request.Start();
		}

		function smAnnouncementsReceiver()
		{
			if (request.GetCurrentState() === 4)
				document.getElementById(\"SMAnnouncementsContent\").innerHTML = request.GetResponseText();
		}
		</script>
		";
	}

	private function request()
	{
		$url = "http://sitemagic.org/external/announcements.php";
		$license = null; //SMLicenseHandler::GetLicenseKey();

		// Get general information

		$metaData = SMEnvironment::GetMetaData();

		$arguments = new SMKeyValueCollection();
		$arguments["license"] = (($license !== null) ? $license : "");
		$arguments["address"] = SMEnvironment::GetExternalUrl();
		$arguments["version"] = $metaData["Version"];
		$arguments["language"] = SMLanguageHandler::GetSystemLanguage();
		$arguments["datasourcetype"] = SMDataSource::GetDataSourceType();
		$arguments["datasourceversion"] = SMDataSource::GetDataSourceVersion();

		// Get extension information

		$extensionsStr = "";
		$extensionMetaData = null;
		$extensions = SMExtensionManager::GetExtensions(true);

		foreach ($extensions as $extension)
		{
			$extensionMetaData = SMExtensionManager::GetMetaData($extension);

			if ($extensionsStr !== "")
				$extensionsStr .= ";";

			$extensionsStr .= $extension . "=" . $extensionMetaData["Version"] . "," . ((SMExtensionManager::ExtensionEnabled($extension) === true) ? "true" : "false");
		}

		$arguments["extensions"] = $extensionsStr;

		// Request announcements and return result

		try
		{
			$result = SMRequest::Get($url, $arguments, 10);
		}
		catch (Exception $ex)
		{
			return "Unable to connect to Sitemagic announcement server";
		}

		return $result;
	}

	public function PreTemplateUpdate()
	{
		if ($this->context->GetExecutionMode() === SMExecutionMode::$Dedicated)
			return;
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMAnnouncements");

		return $this->lang->GetTranslation($key);
	}
}

?>
