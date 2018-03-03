<?php

//require_once(dirname(__FILE__) . "/phpmailer/PHPMailerAutoload.php"); // Does not work with PHP 7.2.x (https://github.com/Jemt/SitemagicCMS/issues/24)
require_once(dirname(__FILE__) . "/phpmailer/class.smtp.php");
require_once(dirname(__FILE__) . "/phpmailer/class.phpmailer.php");

/// <container name="base/SMMailType">
/// 	Enum defining type of e-mail
/// </container>
class SMMailType
{
	/// <member container="base/SMMailType" name="Text" access="public" static="true" type="string" default="Text" />
	public static $Text = "Text";
	/// <member container="base/SMMailType" name="Html" access="public" static="true" type="string" default="Html" />
	public static $Html = "Html";
}

/// <container name="base/SMMailRecipientType">
/// 	Enum defining type of e-mail recipient
/// </container>
class SMMailRecipientType
{
	/// <member container="base/SMMailRecipientType" name="To" access="public" static="true" type="string" default="To" />
	public static $To = "To";
	/// <member container="base/SMMailRecipientType" name="Cc" access="public" static="true" type="string" default="Cc" />
	public static $Cc = "Cc";
	/// <member container="base/SMMailRecipientType" name="Bcc" access="public" static="true" type="string" default="Bcc" />
	public static $Bcc = "Bcc";
}

/// <container name="base/SMMail">
/// 	Class represents an e-mail which may be sent using the locale SMTP server if configured.
///
/// 	$mail = new SMMail();
/// 	$mail->AddRecipient(&quot;test@domain.com&quot;);
/// 	$mail->SetSubject(&quot;My first e-mail&quot;);
/// 	$mail->SetContent(&quot;&lt;b&gt;Hi Casper&lt;/b&gt;&lt;br&gt;Thank you for trying out Sitemagic CMS&quot;);
/// 	$mail->Send();
/// </container>
class SMMail
{
	private $type;				// SMMailType
	private $recipients;		// string[]
	private $recipientsCc;		// string[]
	private $recipientsBcc;		// string[]
	private $attachments;		// string[]
	private $subject;			// string
	private $content;			// string
	private $sender;			// string

	/// <function container="base/SMMail" name="__construct" access="public">
	/// 	<description> Create instance of SMMail </description>
	/// 	<param name="mailType" type="SMMailType" default="SMMailType::$Html"> Type of e-mail (Text or HTML) </param>
	/// </function>
	public function __construct($mailType = "Html")
	{
		SMTypeCheck::CheckObject(__METHOD__, "mailType", $mailType, SMTypeCheckType::$String);

		if (property_exists("SMMailType", $mailType) === false)
			throw new Exception("Invalid mail type '" . $mailType . "' specified - use SMMailType::Type");

		$this->type = $mailType;
		$this->recipients = array();
		$this->recipientsCc = array();
		$this->recipientsBcc = array();
		$this->attachments = array();
		$this->subject = "";
		$this->content = "";
		$this->sender = "";
	}

	/// <function container="base/SMMail" name="SetRecipients" access="public">
	/// 	<description> Set internal array of recipients of specified type </description>
	/// 	<param name="recipientsArray" type="string[]"> Array of valid e-mail addresses </param>
	/// 	<param name="type" type="SMMailRecipientType" default="SMMailRecipientType::$To"> Optionally specify type of recipients </param>
	/// </function>
	public function SetRecipients($recipientsArray, $type = "To")
	{
		SMTypeCheck::CheckArray(__METHOD__, "recipientsArray", $recipientsArray, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMMailRecipientType", $type) === false)
			throw new Exception("Invalid recipient type '" . $type . "' specified - use SMMailRecipientType::Type");

		if ($type === SMMailRecipientType::$To)
			$this->recipients = $recipientsArray;
		else if ($type === SMMailRecipientType::$Cc)
			$this->recipientsCc = $recipientsArray;
		else
			$this->recipientsBcc = $recipientsArray;
	}

	/// <function container="base/SMMail" name="GetRecipients" access="public" returns="string[]">
	/// 	<description> Get internal array of recipients of specified type </description>
	/// 	<param name="type" type="SMMailRecipientType" default="SMMailRecipientType::$To"> Optionally specify type of recipients to get </param>
	/// </function>
	public function GetRecipients($type = "To")
	{
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMMailRecipientType", $type) === false)
			throw new Exception("Invalid recipient type '" . $type . "' specified - use SMMailRecipientType::Type");

		if ($type === SMMailRecipientType::$To)
			return $this->recipients;
		else if ($type === SMMailRecipientType::$Cc)
			return $this->recipientsCc;
		else
			return $this->recipientsBcc;
	}

	/// <function container="base/SMMail" name="AddRecipient" access="public">
	/// 	<description> Add recipient to existing collection of recipients </description>
	/// 	<param name="recipient" type="string"> Valid e-mail address </param>
	/// 	<param name="type" type="SMMailRecipientType" default="SMMailRecipientType::$To"> Optionally specify type of recipient </param>
	/// </function>
	public function AddRecipient($recipient, $type = "To")
	{
		SMTypeCheck::CheckObject(__METHOD__, "recipient", $recipient, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMMailRecipientType", $type) === false)
			throw new Exception("Invalid recipient type '" . $type . "' specified - use SMMailRecipientType::Type");

		if ($type === SMMailRecipientType::$To)
			$this->recipients[] = $recipient;
		else if ($type === SMMailRecipientType::$Cc)
			$this->recipientsCc[] = $recipient;
		else
			$this->recipientsBcc[] = $recipient;
	}

	/// <function container="base/SMMail" name="RemoveRecipient" access="public" returns="boolean">
	/// 	<description> Remove recipient from specified collection of recipients. Returns True if found and removed, otherwise False. </description>
	/// 	<param name="recipient" type="string"> Valid e-mail address previously added to collection of recipients </param>
	/// 	<param name="type" type="SMMailRecipientType" default="SMMailRecipientType::$To"> Optionally specify type of recipient collection to remove recipient from </param>
	/// </function>
	public function RemoveRecipient($recipient, $type = "To")
	{
		SMTypeCheck::CheckObject(__METHOD__, "recipient", $recipient, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMMailRecipientType", $type) === false)
			throw new Exception("Invalid recipient type '" . $type . "' specified - use SMMailRecipientType::Type");

		$found = false;
		$newRecipients = array();

		$recipients = null;

		if ($type === SMMailRecipientType::$To)
			$recipients = $this->recipients;
		else if ($type === SMMailRecipientType::$Cc)
			$recipients = $this->recipientsCc;
		else
			$recipients = $this->recipientsBcc;

		foreach ($recipients as $rec)
		{
			if ($rec !== $recipient)
				$newRecipients[] = $rec;
			else
				$found = true;
		}

		if ($type === SMMailRecipientType::$To)
			$this->recipients = $newRecipients;
		else if ($type === SMMailRecipientType::$Cc)
			$this->recipientsCc = $newRecipients;
		else
			$this->recipientsBcc = $newRecipients;

		return $found;
	}

	/// <function container="base/SMMail" name="AddAttachment" access="public">
	/// 	<description> Add reference to file to attach to e-mail </description>
	/// 	<param name="fileName" type="string"> Filename (must be unique) as displayed in e-mail </param>
	/// 	<param name="filePath" type="string"> Path to file attachment </param>
	/// </function>
	public function AddAttachment($fileName, $filePath)
	{
		SMTypeCheck::CheckObject(__METHOD__, "fileName", $fileName, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "filePath", $filePath, SMTypeCheckType::$String);

		$this->attachments[$fileName] = $filePath;
	}

	/// <function container="base/SMMail" name="RemoveAttachment" access="public">
	/// 	<description> Remove file attachment previously added </description>
	/// 	<param name="fileName" type="string"> Unique filename </param>
	/// </function>
	public function RemoveAttachment($fileName)
	{
		SMTypeCheck::CheckObject(__METHOD__, "fileName", $fileName, SMTypeCheckType::$String);
		unset($this->attachments[$fileName]);
	}

	/// <function container="base/SMMail" name="SetSubject" access="public">
	/// 	<description> Set e-mail subject </description>
	/// 	<param name="value" type="string"> Subject </param>
	/// </function>
	public function SetSubject($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->subject = $value;
	}

	/// <function container="base/SMMail" name="GetSubject" access="public" returns="string">
	/// 	<description> Get e-mail subject </description>
	/// </function>
	public function GetSubject()
	{
		return $this->subject;
	}

	/// <function container="base/SMMail" name="SetContent" access="public">
	/// 	<description> Set e-mail content (body) </description>
	/// 	<param name="value" type="string"> Content (body) </param>
	/// </function>
	public function SetContent($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->content = $value;
	}

	/// <function container="base/SMMail" name="GetContent" access="public" returns="string">
	/// 	<description> Get e-mail content (body) </description>
	/// </function>
	public function GetContent()
	{
		return $this->content;
	}

	/// <function container="base/SMMail" name="SetSender" access="public">
	/// 	<description> Set e-mail sender (reply-to e-mail address) </description>
	/// 	<param name="value" type="string"> Valid reply-to e-mail address </param>
	/// </function>
	public function SetSender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->sender = $value;
	}

	/// <function container="base/SMMail" name="GetSender" access="public" returns="string">
	/// 	<description> Get e-mail sender (reply-to e-mail address) </description>
	/// </function>
	public function GetSender()
	{
		return $this->sender;
	}

	/// <function container="base/SMMail" name="Send" access="public" returns="boolean">
	/// 	<description> Send e-mail - returns True on success, otherwise False </description>
	/// </function>
	public function Send()
	{
		// Read configuration

		$cfg = SMEnvironment::GetConfiguration();

		$host		= $cfg->GetEntry("SMTPHost");
		$port		= $cfg->GetEntry("SMTPPort");
		$atyp		= $cfg->GetEntry("SMTPAuthType");
		$enc		= $cfg->GetEntry("SMTPEncryption");
		$usr		= $cfg->GetEntry("SMTPUser");
		$psw		= $cfg->GetEntry("SMTPPass");
		$sender		= $cfg->GetEntry("SMTPSender");
		$headers	= $cfg->GetEntry("SMTPHeaders");
		$dkimDomain	= $cfg->GetEntry("SMTPDKIMDomain");
		$dkimKey	= $cfg->GetEntry("SMTPDKIMPrivateKeyPath");
		$dkimSel	= $cfg->GetEntry("SMTPDKIMSelector");
		$selfSignOk	= $cfg->GetEntry("SMTPAllowSelfSignedSSL");
		$debug		= $cfg->GetEntry("SMTPDebug");

		// Send mail

		if (false) //if ($host === null || $host === "") // Use PHP standard mail if SMTP has not been configured (does not support file attachments!)
		{
			// Construct headers

			$sender = (($this->sender !== "") ? $this->sender : "no-reply@localhost");

			$headers = "";
			$headers .= (($headers !== "") ? "\r\n" : "") . "from: " . $sender;
			$headers .= (($headers !== "") ? "\r\n" : "") . "reply-to: " . $sender;

			if ($this->type === SMMailType::$Html)
			{
				$headers .= (($headers !== "") ? "\r\n" : "") . "content-type: text/html; charset=\"ISO-8859-1\"";
				$headers .= (($headers !== "") ? "\r\n" : "") . "MIME-Version: 1.0";
			}

			if (count($this->recipientsCc) > 0)
			{
				$headers .= (($headers !== "") ? "\r\n" : "") . "cc: " . implode(",", $this->recipientsCc);
			}

			if (count($this->recipientsBcc) > 0)
			{
				$headers .= (($headers !== "") ? "\r\n" : "") . "bcc: " . implode(",", $this->recipientsBcc);
			}

			// Send mail

			return mail(implode(",", $this->recipients), $this->subject, $this->content, $headers);
		}
		else // Send mail through using PHPMailer
		{
			$mail = new PHPMailer(); // Debugging: Add True argument to have PHPMailer throw exceptions on errors
			$options = array();

			// Enable debugging

			if ($debug !== null && strtolower($debug) === "true")
			{
				ob_start(); // Catch output using output buffer
				$mail->SMTPDebug = true;
			}

			// Configure SMTP

			if ($host !== null && $host !== "")
			{
				$mail->isSMTP();
				$mail->Host = $host;

				if ($usr !== null && $usr !== "")
					$mail->SMTPAuth = true;
				if ($atyp !== null)
					$mail->AuthType = strtoupper($atyp);	// LOGIN (default when string is empty), PLAIN, NTLM, CRAM-MD5
				if ($enc !== null)
					$mail->SMTPSecure = strtolower($enc);	// empty string, tls, or ssl
				if ($port !== null && SMStringUtilities::Validate($port, SMValueRestriction::$Numeric) === true)
					$mail->Port = (int)$port;
				if ($usr !== null)
					$mail->Username = $usr;
				if ($psw !== null)
					$mail->Password = $psw;
			}

			// Add custom headers

			if ($headers !== null && $headers !== "")
			{
				// Example configuration - notice how special characters are encoded within the XML file.
				// <entry key="SMTPHeaders" value="List-Unsubscribe:&lt;http://my-domain.com/?unsubscribe&gt;,&lt;mailto:unsubscribe@cms.company-domain.com&gt;&#xA;Another-header:value" />
				// Less-than: < becomes &lt;
				// Greater-than: > becomes &gt;
				// Line break: \n becomes &#xA;
				// The example above adds the following two headers:
				// List-Unsubscribe:<http://my-domain.com/?unsubscribe>,<mailto:unsubscribe@cms.powerzone.dk>
				// Another-header:value

				$hds = explode("\n", str_replace("\r", "", $headers));

				foreach ($hds as $h)
				{
					$mail->addCustomHeader($h); // Formatted as name:value
				}
			}

			// DKIM configuration

			if ($dkimDomain !== null && $dkimDomain !== "" && $dkimKey !== null && $dkimKey !== "" && $dkimSel !== null && $dkimSel !== "")
			{
				$mail->DKIM_domain = $dkimDomain;
				$mail->DKIM_private = $dkimKey;
				$mail->DKIM_selector = $dkimSel;
			}

			// Allow self-signed SSL certificate

			if ($selfSignOk !== null && strtolower($selfSignOk) === "true")
			{
				$options["ssl"] = array(
					"verify_peer"		=>	false,
					"verify_peer_name"	=>	false,
					"allow_self_signed"	=>	true
				);
			}

			// Sender and reply-to

			$sender = (($sender !== null) ? $sender : "");

			// NOTICE: Some mail servers refuse to send e-mails if spoofing incorrect sender information!
			// In that case make sure Sender, From, and FromName is set to an empty string - PHPMailer assign
			// default values to From and FromName which might cause the mail server to refuse delivery.
			$mail->Sender = $sender;
			$mail->From = $sender;
			$mail->FromName = $sender;

			if ($this->sender !== "")
			{
				// Setting reply-to is not recommended as it may trigger FREEMAIL_FORGED_REPLYTO in SpamAssassing
				// for free e-mail accounts such as Gmail and Yahoo, when From is not set to an identical value.
				// But From should not be set to an address not identical to the actual sender as this is violating
				// SPF (Sender Policy Framework) which will cause the e-mail to be considered spam if SPF is configured.
				$mail->addReplyTo($this->sender);
			}

			// Add recipients

			foreach ($this->recipients as $r)
				$mail->addAddress($r);
			foreach ($this->recipientsCc as $r)
				$mail->addCC($r);
			foreach ($this->recipientsBcc as $r)
				$mail->addBCC($r);

			// Add attachments

			foreach ($this->attachments as $fileName => $filePath)
			{
				if ($mail->AddAttachment($filePath, $fileName) === false) // Returns False on error - e.g. if file does not exist
					throw new Exception("Unable to attach file '" . $filePath . "'");
			}

			// Set content format

			$mail->isHTML($this->type === SMMailType::$Html);
			$mail->CharSet = "ISO-8859-1";

			if ($this->type === SMMailType::$Html)
			{
				$mail->Encoding = "base64"; // Encode e-mail as Base64 to prevent large HTML strings from being broken up, causing corrupted output
				$mail->AltBody = "Please use a modern e-mail client to read this message";
			}

			// Set content

			$mail->Subject = $this->subject;
			$mail->Body = (($this->type === SMMailType::$Html && strpos(strtolower($this->content), "<html>") === false) ? "<html>" . $this->content . "</html>" : $this->content);

			// Apply additional options

			if (count($options) > 0)
				$mail->SMTPOptions = $options;

			// Send mail

			$res = $mail->send();

			// Write debug information to log

			if ($debug !== null && strtolower($debug) === "true")
			{
				$log = ob_get_contents();
				ob_end_clean();

				if ($log !== "")
					SMLog::Log(__FILE__, __LINE__, $log);
			}

			// Done

			return ($res === true ? true : false); // Make sure a boolean is returned in case future versions of $mail->send() returns mixed types
		}
	}
}

?>
