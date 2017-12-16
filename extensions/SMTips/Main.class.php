<?php

class SMTips extends SMExtension
{
	private $lang = null;

	public function PreTemplateUpdate()
	{
		if (SMAuthentication::Authorized() === false && SMExtensionManager::GetExecutingExtension() !== "SMLogin")
			return;

		$lang = ucfirst(SMLanguageHandler::GetSystemLanguage());
		$ext = SMExtensionManager::GetExecutingExtension();
		$messages = array();
		$js = "";

		// Login

		if ($ext === "SMLogin")
		{
			$messages[] = array(
				"id"		=> $lang . $ext . "Form",
				"target"	=> "#SMInputSMLoginUsername",
				"message"	=> "
					<b>" . $this->getTranslation("SMLoginTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMLoginDescription") . "
				"
			);
		}

		// Announcements (after login)

		if ($ext === "SMAnnouncements")
		{
			$messages[] = array(
				"id"		=> $lang . $ext . "Welcome",
				"target"	=> "li.SMMenuSMMenuAdmin",
				"message"	=> "
					<b>" . $this->getTranslation("SMAnnouncementsWelcomeTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMAnnouncementsWelcomeThanks") . "
					<br><br>
					" . $this->getTranslation("SMAnnouncementsWelcomeTips") . "
					<br><br>
					<b>" . $this->getTranslation("SMAnnouncementsWelcomeNotice") . "</b>
					<br><br>
					" . $this->getTranslation("SMAnnouncementsWelcomeButtonClose") . "
					<br>
					" . $this->getTranslation("SMAnnouncementsWelcomeButtonOk") . "
					<br>
					" . $this->getTranslation("SMAnnouncementsWelcomeButtonDisable") . "
					<br><br>
					" . $this->getTranslation("SMAnnouncementsWelcomeEnjoy") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "ContentMenu",
				"target"	=> "li.SMMenuSMMenuContent",
				"message"	=> "
					<b>" . $this->getTranslation("SMAnnouncementsContentMenuTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMAnnouncementsContentMenuDescription") . "
				"
			);$messages[] = array(
				"id"		=> $lang . $ext . "AdminMenu",
				"target"	=> "li.SMMenuSMMenuAdmin",
				"message"	=> "
					<b>" . $this->getTranslation("SMAnnouncementsAdminMenuTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMAnnouncementsAdminMenuDescription") . "
				"
			);
		}

		// Files

		if ($ext === "SMFiles")
		{
			$messages[] = array(
				"id"		=> $lang . $ext . "Folders",
				"target"	=> "#SMFieldsetSMFilesFolders > legend",
				"message"	=> "
					<b>" . $this->getTranslation("SMFilesFoldersTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMFilesFoldersDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Files",
				"target"	=> "#SMFieldsetSMFilesFiles > legend",
				"message"	=> "
					<b>" . $this->getTranslation("SMFilesFilesTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMFilesFilesDescription") . "
				"
			);
		}

		// Menu

		if ($ext === "SMMenu")
		{
			$messages[] = array(
				"id"		=> $lang . $ext . "Form",
				"target"	=> "#SMInputSMMenuTitle",
				"message"	=> "
					<b>" . $this->getTranslation("SMMenuFormTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMMenuFormDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Select",
				"target"	=> "#SMLinkButtonSMMenuBrowseLinks",
				"message"	=> "
					<b>" . $this->getTranslation("SMMenuSelectTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMMenuSelectDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Structure",
				"target"	=> "#SMTreeMenuItemSMMenuRootItem",
				"message"	=> "
					<b>" . $this->getTranslation("SMMenuStructureTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMMenuStructureDescription") . "
				"
			);
		}

		// Pages

		if ($ext === "SMPages")
		{
			if (SMEnvironment::GetQueryValue("SMPagesEditor") === null)
			{
				// Page manager

				$messages[] = array(
					"id"		=> $lang . $ext . "Form",
					"target"	=> "#SMInputSMPagesFilename",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesFormTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesFormDescription") . "
					"
				);
				$messages[] = array(
					"id"		=> $lang . $ext . "List",
					"target"	=> "#SMFieldsetSMPagesList > legend",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesListTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesListDescription") . "
					"
				);
				$messages[] = array(
					"id"		=> $lang . $ext . "HeaderFooter",
					"target"	=> "#SMFieldsetSMPagesHeaderFooter > legend",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesHeaderFooterTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesHeaderFooterDescription") . "
					"
				);
			}
			else
			{
				// Page editor

				$messages[] = array(
					"id"		=> $lang . $ext . "EditorSave",
					"target"	=> "#SMInputSMPagesContent_save",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesEditorButtonSaveTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesEditorButtonSaveDescription") . "
					"
				);
				$messages[] = array(
					"id"		=> $lang . $ext . "EditorLinks",
					"target"	=> "#SMInputSMPagesContent_link",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesEditorButtonLinksTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesEditorButtonLinksDescription") . "
					"
				);
				$messages[] = array(
					"id"		=> $lang . $ext . "EditorImages",
					"target"	=> "#SMInputSMPagesContent_image",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesEditorButtonImagesTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesEditorButtonImagesDescription") . "
					"
				);
				/*$messages[] = array(
					"id"		=> $lang . $ext . "EditorCards",
					"target"	=> "#SMInputSMPagesContent_styleselect_text",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesEditorButtonCardsTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesEditorButtonCardsDescription") . "
					"
				);*/
				$messages[] = array(
					"id"		=> $lang . $ext . "EditorFluidGrids",
					"target"	=> "#SMInputSMPagesContent_table",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesEditorButtonFluidGridsTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesEditorButtonFluidGridsDescription") . "
					"
				);
				$messages[] = array(
					"id"		=> $lang . $ext . "EditorExtensions",
					"target"	=> "img.mceIcon[src*='SMPages/editor/plugins/smextensions/img/button.gif']",
					"message"	=> "
						<b>" . $this->getTranslation("SMPagesEditorButtonExtensionsTitle") . "</b>
						<br><br>
						" . $this->getTranslation("SMPagesEditorButtonExtensionsDescription") . "
					"
				);

				// Tips in TinyMCE dialogs (Links and Images).
				// Listener (setInterval) creates a tip when a dialog is opened.

				$js .= "
				var startDialogListener = function(dialogSelector, elementSelector, id, msg)
				{
					if (!document.querySelector)
						return;

					var sbElm = null;
					var interval = null;

					interval = setInterval(function()
					{
						// Check if dialog is open

						var dialog = document.querySelector(dialogSelector);

						if (dialog === null)
							return;

						// Dialog is open, show tip if content has initialized

						var elm = dialog.contentDocument.querySelector(elementSelector);

						if (elm === null)
							return; // Dialog content not initialized yet

						// Skip if already being displayed.
						// Notice that sbElm.parentElement may be null if tip was displayed but removed from DOM.
						// This happens if tip is associated with an element within an iframe which has now been closed.

						if (sbElm !== null && sbElm.parentElement !== null)
							return;

						sbElm = SMTips.CreateSpeechBubble(id, elm, msg, function() { clearInterval(interval); }, function() { clearInterval(interval); });
					}, 1000);
				}

				startDialogListener(\"iframe[src*='editor/plugins/advlink/link.htm']\", \"#linklisthref\", \"" . $lang . $ext . "EditorLinksDialog\", \"" . $this->getTranslation("SMPagesEditorButtonLinksDialog") . "\");
				startDialogListener(\"iframe[src*='editor/plugins/advimage/image.htm']\", \"#src_list\", \"" . $lang . $ext . "EditorImagesDialog\", \"" . $this->getTranslation("SMPagesEditorButtonImagesDialog") . "\");
				";
			}
		}

		// Designer

		$js .= "
		if (document.querySelector)
		{
			var link = document.querySelector(\"li.SMMenuSMDesigner > a\");

			if (link !== null)
			{
				SMEventHandler.AddEventHandler(link, \"click\", function()
				{
					setTimeout(function() { SMTips.CreateSpeechBubble(\"" . $lang . $ext . "Intro\", \"li.SMMenuSMMenuContent\", \"<b>" . $this->getTranslation("SMDesignerTitle") . "</b><br><br>" . $this->getTranslation("SMDesignerDescription") . "\"); }, 2000);
				});
			}
		}";

		// Contact forms

		if ($ext === "SMContact")
		{
			$messages[] = array(
				"id"		=> $lang . $ext . "Intro",
				"target"	=> "#SMFieldsetSMContactSettings > legend",
				"message"	=> "
					<b>" . $this->getTranslation("SMContactIntroTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMContactIntroDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "List",
				"target"	=> "#SMOptionListSMContactConfig",
				"message"	=> "
					<b>" . $this->getTranslation("SMContactExistingTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMContactExistingDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Config",
				"target"	=> "#SMInputSMContactRecipients",
				"message"	=> "
					<b>" . $this->getTranslation("SMContactBasicConfigTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMContactBasicConfigDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Properties",
				"target"	=> "#SMInputSMContactFieldTitle",
				"message"	=> "
					<b>" . $this->getTranslation("SMContactFormTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMContactFormDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Fields",
				"target"	=> "#SMFieldsetSMContactFieldList > legend",
				"message"	=> "
					<b>" . $this->getTranslation("SMContactFieldsTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMContactFieldsDescription") . "
				"
			);
		}

		// External Modules

		if ($ext === "SMExternalModules")
		{
			$messages[] = array(
				"id"		=> $lang . $ext . "Intro",
				"target"	=> "#SMFieldsetSMExternalModules > legend",
				"message"	=> "
					<b>" . $this->getTranslation("SMExternalModulesIntroTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMExternalModulesIntroDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "List",
				"target"	=> "#SMOptionListSMExternalModulesList",
				"message"	=> "
					<b>" . $this->getTranslation("SMExternalModulesListTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMExternalModulesListDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Config",
				"target"	=> "#SMInputSMExternalModulesName",
				"message"	=> "
					<b>" . $this->getTranslation("SMExternalModulesConfigTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMExternalModulesConfigDescription") . "
					<br>
					<ol>
					 <li>" . $this->getTranslation("SMExternalModulesConfigStep1") . "</li>
					 <li>" . $this->getTranslation("SMExternalModulesConfigStep2") . "</li>
					 <li>" . $this->getTranslation("SMExternalModulesConfigStep3") . "</li>
					 <li>" . $this->getTranslation("SMExternalModulesConfigStep4") . "</li>
					 <li>" . $this->getTranslation("SMExternalModulesConfigStep5") . "</li>
					 <li>" . $this->getTranslation("SMExternalModulesConfigStep6") . "</li>
					</ol>
				"
			);
		}

		// Image Montage

		if ($ext === "SMImageMontage")
		{
			$messages[] = array(
				"id"		=> $lang . $ext . "Intro",
				"target"	=> "#SMInputSMImageMontageMinHeight",
				"message"	=> "
					<b>" . $this->getTranslation("SMImageMontageTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMImageMontageDescription") . "
					<br>
					<ol>
					 <li>" . $this->getTranslation("SMImageMontageStep1") . "</li>
					 <li>" . $this->getTranslation("SMImageMontageStep2") . "</li>
					 <li>" . $this->getTranslation("SMImageMontageStep3") . "</li>
					</ol>
				"
			);
		}

		// Settings

		if ($ext === "SMConfig")
		{
			$messages[] = array(
				"id"		=> $lang . $ext . "Login",
				"target"	=> "#SMInputSMConfigUsername",
				"message"	=> "
					<b>" . $this->getTranslation("SMConfigLoginTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMConfigLoginDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Appearance",
				"target"	=> "#SMOptionListSMConfigImageThemes",
				"message"	=> "
					<b>" . $this->getTranslation("SMConfigLookTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMConfigLookDescription") . "
				"
			);
			$messages[] = array(
				"id"		=> $lang . $ext . "Extensions",
				"target"	=> "#SMFieldsetSMConfigExtensions > legend",
				"message"	=> "
					<b>" . $this->getTranslation("SMConfigExtensionsTitle") . "</b>
					<br><br>
					" . $this->getTranslation("SMConfigExtensionsDescription") . "
				"
			);
		}

		// Register JS

		if (count($messages) > 0 || $js !== "")
		{
			$tpl = SMEnvironment::GetMasterTemplate();
			$tpl->RegisterResource(SMTemplateResource::$JavaScript, SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/Resources/SpeechBubble.js");
			$tpl->RegisterResource(SMTemplateResource::$StyleSheet, SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/Resources/SpeechBubble.css");

			$body = "
			<script type=\"text/javascript\">
			SMTips.CallbackUrl = " . ((SMAuthentication::Authorized() === true) ? "\"" . SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "Callbacks/disable") . "\"" : "null") . ";
			SMTips.Language.Ok = \"" . $this->getTranslation("SpeechBubbleButtonOk") . "\";
			SMTips.Language.Disable = \"" . $this->getTranslation("SpeechBubbleButtonDisable") . "\";
			SMTips.Language.Confirm = \"" . $this->getTranslation("SpeechBubbleDisableWarning", false) . "\";

			setTimeout(" . $this->generate($messages) . ", 1500);
			" . $js . "

			</script>
			";

			$tpl->SetBodyContent($tpl->GetBodyContent() . $body);
		}
	}

	public function Disabled() // Invoked by Sitemagic when extension is disabled
	{
		$cookies = SMEnvironment::GetCookieKeys();

		foreach ($cookies as $cookie)
			if (strpos($cookie, "SMTips") === 0)
				SMEnvironment::DestroyCookie($cookie);
	}

	private function generate($messages, $idx = 0)
	{
		if (count($messages) <= $idx)
			return "function() {}";

		return "function() { SMTips.CreateSpeechBubble(\"" . $messages[$idx]["id"] . "\", \"" . $messages[$idx]["target"] . "\", \"" . str_replace("\n", "", str_replace("\r", "", $messages[$idx]["message"])) . "\", " . $this->generate($messages, $idx + 1) . "); }";
	}

	private function getTranslation($key, $htmlEncode = true)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "htmlEncode", $htmlEncode, SMTypeCheckType::$Boolean);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler($this->context->GetExtensionName());

		$val = $this->lang->GetTranslation($key);

		if ($htmlEncode === true)
		{
			$val = str_replace(" > ", "&nbsp;>&nbsp;", $val);
			$val = str_replace("\"", "&quot;", $val);
			$val = str_replace("'", "&apos;", $val);
		}
		else
		{
			$val = str_replace("\"", "\\\"", $val);
		}

		return $val;
	}
}

?>
