<?php

class SMContactFrmContactForm implements SMIExtensionForm
{
	private $context;
	private $instanceId;
	private $lang;
	private $manager;
	private $message;

	private $controls; // Array[x] = array("title" => $title, "control" => $control)
	private $cmdSend;

	public function __construct(SMContext $context, $instanceId, $formId)
	{
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "formId", $formId, SMTypeCheckType::$String);

		$this->context = $context;
		$this->instanceId = $instanceId;
		$this->lang = new SMLanguageHandler("SMContact");
		$this->manager = new SMContactFields();
		$this->manager->SetAlternativeInstanceId($formId);
		$this->manager->LoadPersistentFields();
		$this->message = "";

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->controls = array();
		$fields = $this->manager->GetFields();

		$title = null;
		$type = null;
		$control = null;

		$count = -1;
		foreach ($fields as $field)
		{
			$count++;

			$title = $field->GetTitle();
			$type = $field->GetType();

			if ($type === SMContactFieldTypes::$Checkbox)
			{
				$control = new SMInput("SMContactField" . (string)($count . "_" . $this->instanceId), SMInputType::$Checkbox);
				$control->SetAttribute(SMInputAttributeCheckbox::$Value, "X");
			}
			else if ($type === SMContactFieldTypes::$Textbox)
			{
				$control = new SMInput("SMContactField" . (string)($count . "_" . $this->instanceId), SMInputType::$Textarea);
				$control->SetAttribute(SMInputAttributeTextarea::$Cols, "1");
				$control->SetAttribute(SMInputAttributeTextarea::$Rows, "1");
				$control->SetAttribute(SMInputAttributeTextarea::$Style, "width: 250px; height: 100px");
			}
			else if ($type === SMContactFieldTypes::$Attachment)
			{
				$this->context->GetForm()->SetContentType(SMFormContentType::$MultiPart);

				$control = new SMInput("SMContactField" . (string)($count . "_" . $this->instanceId), SMInputType::$File);
				$control->SetAttribute(SMInputAttributeFile::$OnChange, "smContactUploadSizeCheck" . $this->instanceId . "(this);");
				$control->SetAttribute(SMInputAttributeFile::$Style, "width: 250px");
			}
			else
			{
				$control = new SMInput("SMContactField" . (string)($count . "_" . $this->instanceId), SMInputType::$Text);
				$control->SetAttribute(SMInputAttributeText::$Style, "width: 250px");
			}

			$this->controls[] = array(
				"title"		=> $title,
				"control"	=> $control,
				"type"		=> $type
			);
		}

		$this->cmdSend = new SMLinkButton("SMContactSend" . (string)$this->instanceId);
		$this->cmdSend->SetIcon(SMImageProvider::GetImage(SMImageType::$Mail));
		$this->cmdSend->SetTitle($this->lang->GetTranslation("Send"));
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdSend->PerformedPostBack() === true)
				$this->sendMail();
		}
	}

	private function sendMail()
	{
		$recipients = SMContactSettings::GetRecipients();

		if ($recipients === "")
		{
			$this->message = $this->lang->GetTranslation("ErrorNoRecipients");
			return;
		}

		// Get data from contact form

		$contentSet = false;
		$value = null;
		$body = "";
		$copyTo = "";
		$attachments = array();
		$count = -1;

		$body .= "<table cellspacing=\"0\" cellpadding=\"10\" border=\"0\" style=\"padding: 10px 5px 10px 5px;\">";

		foreach ($this->controls as $control)
		{
			// Get value from control

			if ($control["type"] === SMContactFieldTypes::$Checkbox)
			{
				$value = (($control["control"]->GetChecked() === true) ? "X" : "");
			}
			else if ($control["type"] === SMContactFieldTypes::$Email)
			{
				$value = "";

				if (SMStringUtilities::Validate($control["control"]->GetValue(), SMValueRestriction::$EmailAddress))
				{
					$copyTo .= (($copyTo !== "") ? "," : "") . $control["control"]->GetValue();
					$value = $control["control"]->GetValue();
				}
				else if ($control["control"]->GetValue() !== "")
				{
					$value = $control["control"]->GetValue() . " (INVALID)";
				}
			}
			else if ($control["type"] === SMContactFieldTypes::$Attachment)
			{
				// Attachment is currently located in PHP's tmp directory - will be removed automatically when request is complete.
				// http://www.php.net/manual/en/features.file-upload.post-method.php
				// "The file will be deleted from the temporary directory at the end of the request if it has not been moved away or renamed."

				// Control may return Null if File Uploads have been disabled (file_uploads = off).

				$value = "";

				if ($control["control"]->GetValue() !== null && strlen($control["control"]->GetValue()) > 0) // IE10-11 fix: using strlen(..) to check value - IE10 and IE11 returns an empty string that is not comparable with ""
				{
					/*if (SMStringUtilities::Validate($control["control"]->GetValue(), SMValueRestriction::$Filename) === false)
					{
						$this->message = $this->lang->GetTranslation("ErrorInvalidFilename");
						return;
					}*/

					$value = SMStringUtilities::RemoveInvalidCharacters($control["control"]->GetValue(), SMValueRestriction::$Filename);

					// Remove multiple periods following each other. This may happen if
					// filename contained multiple periods with no valid characters in between.
					while (strpos($value, "..") !== false)
						$value = str_replace("..", ".", $value);

					if ($value === "") // Filename contained no valid characters and had no file extension
					{
						$value = "File"; // Multiple files with identical names are handled further down
					}
					else if (strpos($value, ".") !== false)
					{
						if ($value === ".")
						{
							// No valid characters in filename or extension
							$value = "File"; // Multiple files with identical names are handled further down
						}
						else if (strpos($value, ".") === 0) //else if (substr($value, 0, strrpos($value, ".")) === "")
						{
							// Filename contained no valid characters but file extension is present (e.g. .txt).
							// If filename contained multiple periods, it could also be something like: .my demo.txt.
							// This will prevent filenames such as ".htaccess", but that is acceptable.
							$value = "File" . $value; // Multiple files with identical names are handled further down
						}
						else if (strrpos($value, ".") === strlen($value) - 1)
						{
							// Filename contained no extension after period (e.g. Test.) - unlikely
							$value = substr($value, 0, strrpos($value, ".")); // Remove period
						}
					}

					// Make sure filenames are unique (multiple different files with identical filenames could have been added from different locations)

					if (isset($attachments[$value]) === true)
					{
						$count = 2;
						while (isset($attachments[$count . "-" . $value]) === true)
						{
							$count++;

							if ($count === 100)
								break; // Give up (unlikely)
						}

						if ($count < 100)
							$value = $count . "-" . $value;
						else
							$value = ""; // Skip file (unlikely - this should not happen - see above)
					}

					if ($value !== "")
						$attachments[$value] = SMFileSystem::GetUploadPath($control["control"]->GetClientId());
				}
			}
			else
			{
				$value = $control["control"]->GetValue();
			}

			// Cancel out if required and not set

			if ($value === "" && strpos($control["title"], "*") !== false)
			{
				$this->message = "<span class=\"SMContactRequired\">*</span> " . $this->lang->GetTranslation("RequiredError");
				return;
			}

			// Add content to mail message

			if ($value !== "")
				$contentSet = true;

			$body .= "<tr>";
			$body .= "<td style=\"white-space: nowrap; padding-right: 25px; vertical-align: top; border-bottom: 1px solid silver;\">" . $control["title"] . "</td>";
			$body .= "<td style=\"border-bottom: 1px solid silver;\">" . SMStringUtilities::NewLineToHtmlLineBreak(str_replace("&amp;#", "&#", SMStringUtilities::HtmlEncode($value))) . "</td>"; // Using str_replace(..) to fix HTML HEX entities after htmlspecialchars(..) - Unicode support
			$body .= "</tr>";
		}

		$body .= "</table>";

		// Send e-mail

		if ($contentSet === true)
		{
			// Construct mail

			$mail = new SMMail(SMMailType::$Html);
			$mail->SetRecipients(explode(",", $recipients));
			$mail->SetRecipients((($copyTo !== "") ? explode(",", $copyTo) : array()), SMMailRecipientType::$Cc);
			$mail->SetSubject(SMContactSettings::GetSubject());
			$mail->SetContent($body);

			// Add attachments

			foreach ($attachments as $filename => $path)
				$mail->AddAttachment($filename, $path);

			// Send and check for errors

			$result = $mail->Send();

			if ($result === true)
				$this->message = SMContactSettings::GetSuccessMessage();
			else
				$this->message = $this->lang->GetTranslation("ErrorSending");

			// Clear controls on success

			if ($result === true)
			{
				foreach ($this->controls as $control)
				{
					if ($control["type"] === SMContactFieldTypes::$Checkbox)
						$control["control"]->SetChecked(false);
					else if ($control["type"] === SMContactFieldTypes::$Email)
						$control["control"]->SetValue("");
					else
						$control["control"]->SetValue("");
				}
			}
		}
	}

	public function Render()
	{
		if (count($this->controls) === 0)
			return SMNotify::Render($this->lang->GetTranslation("FieldsNotDefined"));

		$output = "";

		if ($this->message !== "")
			$output .= "<i>" . $this->message . "</i><br><br>";

		$output .= "
		<table id=\"smContactForm" . $this->instanceId . "\">
		";

		foreach ($this->controls as $control)
		{
			$output .= "
			<tr>
				<td style=\"width: 130px\">" . str_replace("*", "<span class=\"SMContactRequired\">*</span>", $control["title"]) . "</td>
				<td style=\"width: 250px\">" . $control["control"]->Render() . "</td>
			</tr>
			";
		}

		$output .= "
			<tr>
				<td style=\"width: 130px\">&nbsp;</td>
				<td style=\"width: 250px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 130px\">&nbsp;</td>
				<td style=\"width: 250px\"><div style=\"text-align: right\">" . $this->cmdSend->Render() . "</div></td>
			</tr>
		</table>
		";

		$output .= "
		<script type=\"text/javascript\">
		function smContactUploadSizeCheck" . $this->instanceId . "(currentUploadField)
		{
			var inputs = document.getElementById(\"smContactForm" . $this->instanceId . "\").getElementsByTagName(\"input\");
			var size = 0;

			for (var i = 0 ; i < inputs.length ; i++)
			{
				if (inputs[i].type !== \"file\" || inputs[i].files === undefined)
					continue;

				size += ((inputs[i].files.length > 0) ? inputs[i].files[0].size : 0);
			}

			if (size > " . SMEnvironment::GetMaxUploadSize() . ")
			{
				currentUploadField.value = \"\";
				alert('" . str_replace("2048", (string)(SMEnvironment::GetMaxUploadSize() / 1024), $this->lang->GetTranslation("FileSizeLimit", true)) . "');
			}
		}
		</script>
		";

		$fieldSet = new SMFieldset("SMContact" . (string)$this->instanceId);
		$fieldSet->SetAttribute(SMFieldsetAttribute::$Style, "width: 380px");
		$fieldSet->SetDisplayFrame(false);
		$fieldSet->SetContent($output);
		$fieldSet->SetPostBackControl($this->cmdSend->GetClientId());
		return $fieldSet->Render();
	}
}

?>
