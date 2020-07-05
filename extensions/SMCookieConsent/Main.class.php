<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/SMCookieConsentHelper.class.php");
require_once(dirname(__FILE__) . "/FrmConfig.class.php");

class SMCookieConsent extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;
	private $smPagesExists = false;

	public function Init()
	{
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu");	// False if not installed or not enabled
		$this->smPagesExists = SMExtensionManager::ExtensionEnabled("SMPages");	// False if not installed or not enabled
	}

	public function InitComplete()
	{
		if ($this->smMenuExists === true)
		{
			if (SMMenuLinkList::GetInstance()->GetReadyState() === true)
			{
				$menuLinkList = SMMenuLinkList::GetInstance();
				$menuLinkList->AddLink($this->getTranslation("Title"), $this->getTranslation("WithdrawCookieConsent"), "javascript:SMCookie.RemoveCookie('SMCookieConsentAllowed');location.href=location.href;");
			}
		}

		if ($this->smPagesExists === true)
		{
			if (SMPagesLinkList::GetInstance()->GetReadyState() === true)
			{
				$pagesLinkList = SMPagesLinkList::GetInstance();
				$pagesLinkList->AddLink($this->getTranslation("Title"), $this->getTranslation("WithdrawCookieConsent"), "javascript:SMCookie.RemoveCookie('SMCookieConsentAllowed');location.href=location.href;");
			}
		}
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$this->SetIsIntegrated(true);

		$form = new SMCookieConsentFrmConfig($this->context);
		return $form->Render();
	}

	public function PreTemplateUpdate()
	{
		// Add extension to admin menu item

		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuAdmin");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMCookieConsent", $this->getTranslation("Title"), SMExtensionManager::GetExtensionUrl($this->context->GetExtensionName())));
		}

		// Register cookie panel if consent has not been given, or register modules for which user has given consent

		$cs = new SMCookieConsentHelper();

		$position = $cs->GetDialogPosition();
		$text = $cs->GetDialogPageContent();

		if ($position === "disabled" || $text === "")
		{
			return;
		}

		$modules = array();
		foreach ($cs->GetModules() as $module)
		{
			$modules[] = array("Name" => $module, "Description" => $cs->GetModuleDescription($module), "Code" => $cs->GetModuleCode($module));
		}

		$allowed = SMEnvironment::GetCookieValue("SMCookieConsentAllowed"); // Null if user has not yet either denied or accepted cookies

		if ($allowed === null)
		{
			$deny = $cs->GetDenyText();
			$accept = $cs->GetAcceptText();
			$hours = $cs->GetConsentDuration();

			$template = $this->context->GetTemplate();

			$template->RegisterResource(SMTemplateResource::$StyleSheet, SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/CookieDialog.css");
			$template->RegisterResource(SMTemplateResource::$JavaScript, SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/CookieDialog.js");

			$template->AddToHeadSection("
			<script>
				var cs = new SMCookieConsent();
				cs.Text = '" . $text . "';
				cs.Deny = '" . ($deny !== "" ? $deny : "Deny") . "';
				cs.Accept = '" . ($accept !== "" ? $accept : "Accept") . "';
				cs.HideHours = " . $hours . ";
				cs.Position = '" . $position . "';
				cs.Modules = " . json_encode($modules) . ";
				SMEventHandler.AddEventHandler(document, 'DOMContentLoaded', function() { cs.Render(); });
			</script>");
		}
		else
		{
			// Load modules to which user has given consent

			$allowedArray = explode("|#|", urldecode($allowed)); // Cookie value is encoded to allow use of semicolon which is used in unicode encoding (e.g. &#1234;)

			$js = "";
			foreach ($cs->GetModules() as $module)
			{
				if (in_array($module, $allowedArray, true) === true)
				{
					$js .= "(function(){" . $cs->GetModuleCode($module) . "})();";
				}
			}

			$template = $this->context->GetTemplate();
			$template->AddToHeadSection("<script>" . $js . "</script>");
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler($this->context->GetExtensionName());

		return $this->lang->GetTranslation($key);
	}
}

?>
