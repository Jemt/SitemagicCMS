<?php

class SMLanguageEditorFrmEditor implements SMIExtensionForm
{
	private $context;
	private $msg;
	private $validation;

	private $lstLangs;
	private $txtLang;
	private $lstExtensions;
	private $txtExtension;

	private $cmdCreate;
	private $cmdDelete;
	private $cmdValidate;
	private $cmdDownload;
	private $cmdEdit;
	private $cmdSave;

	private $txtNewLangCode;
	private $arrTranslations;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->msg = "";
		$this->validation = "";

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", "Language editor"));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		// Language selector

		$smCfg = SMEnvironment::GetConfiguration();
		$langs = explode(";", $smCfg->GetEntry("Languages"));

		$this->lstLangs = new SMOptionList("SMLanguageEditorLanguages");
		$this->lstLangs->SetAttribute(SMOptionListAttribute::$Style, "width: 200px");

		foreach ($langs as $lang)
			$this->lstLangs->AddOption(new SMOptionListItem("SMLanguageEditorLanguages" . $lang, $lang . ((SMLanguageHandler::GetSystemLanguage() === $lang) ? " (active)" : ""), $lang));

		if ($this->context->GetForm()->PostBack() === false)
			$this->lstLangs->SetSelectedValue(SMLanguageHandler::GetSystemLanguage()); // Will not select anything if active language (system language) has been removed

		$this->txtLang = new SMInput("SMLanguageEditorSelectedLanguage", SMInputType::$Hidden);

		// Extension selector

		$exts = SMExtensionManager::GetExtensions(true);
		$extInfo = null;

		$this->lstExtensions = new SMOptionList("SMLanguageEditorExtensions");
		$this->lstExtensions->SetAttribute(SMOptionListAttribute::$Style, "width: 200px");
		$this->lstExtensions->AddOption(new SMOptionListItem("SMLanguageEditorExtensionsSitemagicFramework", "Sitemagic", ""));

		foreach ($exts as $ext)
		{
			if (SMLanguageHandler::HasTranslations($ext, "en") === false) // Include only if English translations exist (required, and used as template when creating new language packages)
				continue;

			$extInfo = SMExtensionManager::GetMetaData($ext);
			$this->lstExtensions->AddOption(new SMOptionListItem("SMLanguageEditorExtensions" . $ext, $extInfo["Title"], $ext));
		}

		$this->txtExtension = new SMInput("SMLanguageEditorSelectedExtension", SMInputType::$Hidden);

		// Create button

		$this->txtNewLangCode = new SMInput("SMLanguageEditorNewLanguageCode", SMInputType::$Hidden);

		$this->cmdCreate = new SMLinkButton("SMLanguageEditorCreate");
		$this->cmdCreate->SetTitle("New");
		$this->cmdCreate->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));
		$this->cmdCreate->SetOnclick("var l = SMMessageDialog.ShowInputDialog('Please enter language code for new language package (e.g. de for German, it for Italian, etc.)', ''); if (l !== null && l !== '') { document.getElementById('" . $this->txtNewLangCode->GetClientId() . "').value = l; } else { return; }");

		// Delete button

		$this->cmdDelete = new SMLinkButton("SMLanguageEditorDelete");
		$this->cmdDelete->SetTitle("Delete");
		$this->cmdDelete->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDelete->SetOnclick("var res = SMMessageDialog.ShowConfirmDialog('Remove selected language package?'); if (res === false) return;");

		// Validation button

		$this->cmdValidate = new SMLinkButton("SMLanguageEditorValidate");
		$this->cmdValidate->SetTitle("Validate");
		$this->cmdValidate->SetIcon(SMImageProvider::GetImage(SMImageType::$Display));

		// Download button

		$this->cmdDownload = new SMLinkButton("SMLanguageEditorDownload");
		$this->cmdDownload->SetTitle("Download");
		$this->cmdDownload->SetIcon(SMImageProvider::GetImage(SMImageType::$Down));

		// Edit button

		$this->cmdEdit = new SMLinkButton("SMLanguageEditorEdit");
		$this->cmdEdit->SetTitle("Edit");
		$this->cmdEdit->SetIcon(SMImageProvider::GetImage(SMImageType::$Modify));

		// Save button

		$this->cmdSave = new SMLinkButton("SMLanguageEditorSave");
		$this->cmdSave->SetTitle("Save");
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));

		// Translation controls

		$this->arrTranslations = array();

		if ($this->cmdEdit->PerformedPostBack() === true || $this->cmdSave->PerformedPostBack() === true)
		{
			// Determine language and extension to edit

			$lang = "";
			$ext = "";

			if ($this->cmdEdit->PerformedPostBack() === true)
			{
				$lang = $this->lstLangs->GetSelectedValue();
				$ext = $this->lstExtensions->GetSelectedValue();

				$this->txtLang->SetValue($lang);
				$this->txtExtension->SetValue($ext);
			}
			else // save
			{
				// Do not use values from option lists when saving - user might have changed
				// their values so they are now different from the translations being edited.
				$lang = $this->txtLang->GetValue();
				$ext = $this->txtExtension->GetValue();
			}

			// Make sure translation file is writable or can be created if not already found

			if (SMLanguageHandler::HasTranslations($ext, $lang) === true && SMLanguageHandler::CanUpdateTranslations($ext, $lang) === false)
			{
				$this->msg = "Translations cannot be edited due to insufficient write access";
				return;
			}

			if (SMLanguageHandler::HasTranslations($ext, $lang) === false && SMLanguageHandler::CanAddTranslations($ext) === false)
			{
				$this->msg = "New Translations cannot be added due to insufficient write access";
				return;
			}

			// Read translations into edit controls

			$english = new SMLanguageHandler($ext, "en");
			$translations = ((SMLanguageHandler::HasTranslations($ext, $lang) === true) ? new SMLanguageHandler($ext, $lang) : null);

			if ($translations !== null)
				$translations->SetFallbackEnabled(false);

			foreach ($english->GetTranslationKeys() as $key)
			{
				$this->arrTranslations[$key] = new SMInput("SMLanguageEditorTranslation" . $key, SMInputType::$Text);
				$this->arrTranslations[$key]->SetAttribute(SMInputAttribute::$Style, "width: 100%");

				if ($this->cmdEdit->PerformedPostBack() === true)
					$this->arrTranslations[$key]->SetValue((($translations !== null) ? $translations->GetTranslation($key) : ""));
			}
		}
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdSave->PerformedPostBack() === true)
			{
				$lang = $this->txtLang->GetValue();
				$ext = $this->txtExtension->GetValue();

				// No need to check file permissions during save
				// operation, as it was done when edit controls were created.
				// We made sure an existing translation file is writable,
				// and that the folder is writable if no translation file is found.

				$translations = new SMLanguageHandler($ext, $lang, true);

				// Remove all translations to make sure we only include
				// those found in English translation. Also ensures same order.

				foreach ($translations->GetTranslationKeys() as $key)
					$translations->RemoveTranslation($key);

				// Update or add new translations

				foreach ($this->arrTranslations as $key => $input)
					$translations->SetTranslation($key, $input->GetValue());

				// Save changes (updates existing file or creates new file if missing)

				$translations->Commit();

				// User might have changed selections before saving - restore values in option lists

				$this->lstLangs->SetSelectedValue($lang);
				$this->lstExtensions->SetSelectedValue($ext);
			}
			else if ($this->cmdCreate->PerformedPostBack() === true)
			{
				$lang = $this->txtNewLangCode->GetValue();

				// Make sure language package does not already exist

				if (SMLanguageHandler::HasTranslations("", $lang) === true)
				{
					$this->msg = "Language package already exists!";
					return;
				}

				// First make sure we can create new translation files for all extensions

				foreach ($this->lstExtensions->GetOptions() as $o)
				{
					if (SMLanguageHandler::CanAddTranslations($o->GetValue()) === false)
					{
						$this->msg = "Language package not created - insufficient write access for '" . $o->GetTitle() . "'";
						return;
					}
				}

				// Copy English language package (used as template for new language package)

				$result = false;

				foreach ($this->lstExtensions->GetOptions() as $o)
				{
					// Skip if an extension already contains translations for language X.
					// The developer might have added translations for a language not yet found in Sitemagic CMS.
					if (SMLanguageHandler::HasTranslations($o->GetValue(), $lang) === true)
						continue;

					$result = SMLanguageHandler::CreateTranslations($o->GetValue(), $lang, true); // true argument = copy English

					if ($result === false)
					{
						// Should not happen unless English translation file is missing.
						// But $this->lstExtensions only lists extensions that contain English translations, so that wouldn't be the cause.
						$this->msg = "Unexpected error occured - unable to copy English translations to new language package for '" . $o->GetTitle() . "'";
						return;
					}
				}

				// Make new language package available to Sitemagic (add to config.xml.php)

				$cfg = SMEnvironment::GetConfiguration(true);
				$cfg->SetEntry("Languages", $cfg->GetEntry("Languages") . ";" . $lang);
				$cfg->Commit();

				// Add new language to Language Editor and select it

				$this->lstLangs->AddOption(new SMOptionListItem("SMLanguageEditorLanguage" . $lang, $lang, $lang));

				$this->lstLangs->SetSelectedValue($lang);
				$this->lstExtensions->ResetSelectedValue();
			}
			else if ($this->cmdDelete->PerformedPostBack() === true)
			{
				$lang = $this->lstLangs->GetSelectedValue();

				if ($lang === null) // Null if user re-posted form after removing language - language is no longer found in picker
					return;

				// Prevent user from removing English language package

				if ($lang === "en")
				{
					$this->msg = "The default language (English) cannot be removed";
					return;
				}

				// Make sure all translation files can be removed

				foreach ($this->lstExtensions->GetOptions() as $o)
				{
					if (SMLanguageHandler::HasTranslations($o->GetValue(), $lang) === true && SMLanguageHandler::CanUpdateTranslations($o->GetValue(), $lang) === false)
					{
						$this->msg = "Language package cannot be removed - insufficient write access to translation file for '" . $o->GetTitle() . "'";
						return;
					}
				}

				// Remove language package

				$result = false;

				foreach ($this->lstExtensions->GetOptions() as $o)
				{
					if (SMLanguageHandler::HasTranslations($o->GetValue(), $lang) === true)
					{
						$result = SMLanguageHandler::RemoveTranslations($o->GetValue(), $lang);

						if ($result === false)
						{
							// This should not happen - we made sure permissions were alright above
							$this->msg = "Unexpected error occured - Unable to remove translations for '" . $o->GetTitle() . "'";
							return;
						}
					}
				}

				// Update available languages in Sitemagic

				$cfg = SMEnvironment::GetConfiguration(true);
				$langsArr = explode(";", $cfg->GetEntry("Languages"));

				$newLangs = "";
				foreach ($langsArr as $l)
					if ($l !== $lang)
						$newLangs .= (($newLangs !== "") ? ";" : "") . $l;

				$cfg->SetEntry("Languages", $newLangs);
				$cfg->Commit();

				// Update available languages in Language Editor and select active language

				$this->lstLangs->RemoveOption("SMLanguageEditorLanguages" . $lang);
				$this->lstLangs->SetSelectedValue(SMLanguageHandler::GetSystemLanguage()); // Will not select anything if active language (system language) has been removed
				$this->lstExtensions->ResetSelectedValue();
			}
			else if ($this->cmdValidate->PerformedPostBack() === true)
			{
				$langCode = $this->lstLangs->GetSelectedValue();

				if ($langCode === "en")
				{
					$this->msg = "Unable to validate English agains English, please choose another language package to validate";
					return;
				}

				$langEnglish = null;
				$langCurrent = null;

				foreach ($this->lstExtensions->GetOptions() as $o)
				{
					if (SMLanguageHandler::HasTranslations($o->GetValue(), "en") === false)
						continue;

					if (SMLanguageHandler::HasTranslations($o->GetValue(), $langCode) === false)
					{
						$this->validation .= (($this->validation !== "") ? "<br><br>" : "") . "<b>Error</b>: Extension '" . $o->GetTitle() . "' contains no translations for language '" . $langCode . "'";
						continue;
					}

					$langEnglish = new SMLanguageHandler($o->GetValue(), "en");
					$langCurrent = new SMLanguageHandler($o->GetValue(), $langCode);
					$langCurrent->SetFallbackEnabled(false);

					foreach ($langEnglish->GetTranslationKeys() as $key)
					{
						if ($langCurrent->GetTranslation($key) === $langEnglish->GetTranslation($key) && strpos($langEnglish->GetTranslation($key), "Y-m-d") !== 0)
						{
							$this->validation .= (($this->validation !== "") ? "<br><br>" : "") . "<i>Warning</i>:";
							$this->validation .= "<br>Extension: '" . $o->GetTitle() . "'";
							$this->validation .= "<br>Translation (en) '" . $key . "' = '" . $langEnglish->GetTranslation($key) . "'";
							$this->validation .= "<br>Translation (" . $langCode . ") '" . $key . "' = '" . $langCurrent->GetTranslation($key) . "'";
							$this->validation .= "<br><i>Translations are identical!</i>";
						}
						else if ($langCurrent->HasTranslation($key) === false || $langCurrent->GetTranslation($key) === "")
						{
							$this->validation .= (($this->validation !== "") ? "<br><br>" : "") . "<b>Error</b>:";
							$this->validation .= "<br>Extension: '" . $o->GetTitle() . "'";
							$this->validation .= "<br>Translation (en) '" . $key . "' = '" . $langEnglish->GetTranslation($key) . "'";
							$this->validation .= "<br>Translation (" . $langCode . ") '" . $key . "' = " . (($langCurrent->HasTranslation($key) === true) ? "''" : "<i>Missing</i>");
							$this->validation .= "<br><i>No translation found!</i>";
						}
					}

					foreach ($langCurrent->GetTranslationKeys() as $key)
					{
						if ($langEnglish->HasTranslation($key) === false)
						{
							$this->validation .= (($this->validation !== "") ? "<br><br>" : "") . "<i>Warning</i>:";
							$this->validation .= "<br>Extension: '" . $o->GetTitle() . "'";
							$this->validation .= "<br>Translation (" . $langCode . ") '" . $key . "' = '" . $langCurrent->GetTranslation($key) . "'";
							$this->validation .= "<br><i>Unused translation found - edit and save translations to do automatic clean up</i>";
						}
					}
				}

				if ($this->validation === "")
					$this->msg = "Language package '" . $langCode . "' is valid and fully translated";
			}
			else if ($this->cmdDownload->PerformedPostBack() === true)
			{
				$lang = $this->lstLangs->GetSelectedValue();

				// Check permissions

				if (SMFileSystem::FolderIsWritable(SMEnvironment::GetFilesDirectory()) === false)
				{
					$this->msg = "Unable to download language package due to insufficient write access to '" . SMEnvironment::GetFilesDirectory() . "' directory";
					return;
				}

				// Create language package as ZIP archive

				$zip = new ZipArchive();
				$res = $zip->open(SMEnvironment::GetFilesDirectory() . "/" . $lang . ".zip", ZipArchive::CREATE);

				if ($res !== true)
				{
					$this->msg = "Unable to create temporary ZIP file";
					return;
				}

				// Add translation files to ZIP archive

				foreach ($this->lstExtensions->GetOptions() as $o)
				{
					if (SMLanguageHandler::HasTranslations($o->GetValue(), $lang) === true)
					{
						if ($o->GetValue() === "")
							$zip->addFile("base/Languages/" . $lang . ".xml");
						else
							$zip->addFile(SMExtensionManager::GetExtensionPath($o->GetValue()) . "/Languages/" . $lang . ".xml");
					}
				}

				$res = $zip->close();

				if ($res !== true)
				{
					$this->msg = "Unable to save files to temporary ZIP file";
					return;
				}

				// Download language package

				SMFileSystem::DownloadFileToClient(SMEnvironment::GetFilesDirectory() . "/" . $lang . ".zip", true);
				SMFileSystem::Delete(SMEnvironment::GetFilesDirectory() . "/" . $lang . ".zip");
				exit; // Stop further processing after sending file to client
			}
		}
	}

	public function Render()
	{
		$output = "";

		// Error message
		if ($this->msg !== "")
			$output .= SMNotify::Render($this->msg);

		// Option lists and buttons
		$output .= "Language: " . $this->lstLangs->Render() . " " . $this->cmdCreate->Render() . " " . $this->cmdDelete->Render() . " " . $this->cmdValidate->Render() . ((class_exists("ZipArchive") === true) ? " " . $this->cmdDownload->Render() : "");
		$output .= "<br>Extension: " . $this->lstExtensions->Render() . " " . $this->cmdEdit->Render() . " " . ((($this->cmdEdit->PerformedPostBack() === true && count($this->arrTranslations) > 0) || $this->cmdSave->PerformedPostBack() === true) ? $this->cmdSave->Render() : "");

		// Translation edit controls
		if (count($this->arrTranslations) > 0)
		{
			$output .= "<br>";
			$english = new SMLanguageHandler($this->lstExtensions->GetSelectedValue(), "en");

			foreach ($this->arrTranslations as $key => $input)
				$output .= "<br><br>" . $english->GetTranslation($key) . " (<i>" . $key . ", " . count(explode(" ", $english->GetTranslation($key))) . " word(s)</i>)<br>" . $input->Render();
		}

		// Hidden input fields
		$output .= $this->txtLang->Render();
		$output .= $this->txtExtension->Render();
		$output .= $this->txtNewLangCode->Render();

		// Validation message
		if ($this->validation !== "")
			$output .= "<br><br>" . $this->validation;

		// Fieldset
		$fieldset = new SMFieldset("SMLanguageEditor");
		$fieldset->SetContent($output);
		$fieldset->SetLegend("Language editor");
		$fieldset->SetPostBackControl($this->cmdSave->GetClientId());

		return $fieldset->Render();
	}
}

?>
