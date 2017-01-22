<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/FrmSettings.class.php");

class SMGoogleAnalytics extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;

	public function Init()
	{
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu"); // False if not installed or not enabled
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		$frm = new SMGoogleAnalyticsSettings($this->context);
		return $frm->Render();
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMGoogleAnalytics", $this->getTranslation("Title"), SMExtensionManager::GetExtensionUrl("SMGoogleAnalytics")));
		}
	}

	public function PreOutput()
	{
		// Do not report anything when authorized - visitor/user is an administrator
		if (SMAuthentication::Authorized() === true)
			return;

		$trackerId = SMAttributes::GetAttribute("SMGoogleAnalyticsTrackerId");

		if ($trackerId === null)
			return;

		$reportMode = SMAttributes::GetAttribute("SMGoogleAnalyticsReportMode"); // Set if trackerId is set

		if ($reportMode === "Nothing")
			return;
		if ($reportMode === "ContentPages" && SMExtensionManager::GetExecutingExtension() !== "SMPages")
			return;

		$template = $this->context->GetTemplate();
		$body = $template->GetBodyContent();

		$body .= "
		<script type=\"text/javascript\">
		var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
		document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
		</script>

		<script type=\"text/javascript\">
		try
		{
			var pageTracker = _gat._getTracker(\"" . $trackerId . "\");
			pageTracker._trackPageview();
		}
		catch(err) {}
		</script>
		";

		$template->SetBodyContent($body);
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMGoogleAnalytics");

		return $this->lang->GetTranslation($key);
	}
}

?>
