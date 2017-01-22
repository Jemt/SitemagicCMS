<?php

SMExtensionManager::Import("SMPages", "SMPagesExtension.class.php", true);
require_once(dirname(__FILE__) . "/SMComments.classes.php");

class SMCommentsContentPageExtension extends SMPagesExtension
{
	private $lang;
	private $msg;

	private $uid;

	private $txtName;
	private $txtComment;
	private $cmdSubmit;

	private $comments;

	public function __construct(SMContext $context, $pageId, $instanceId, $arg)
	{
		SMTypeCheck::CheckObject(__METHOD__, "pageId", $pageId, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "arg", $arg, SMTypeCheckType::$String);

		$this->context = $context;
		$this->pageId = $pageId;
		$this->instanceId = $instanceId;
		$this->argument = $arg;

		$this->SetIsIntegrated(true);

		$this->lang = new SMLanguageHandler("SMComments");
		$this->msg = "";

		$this->uid = "SMComments" . $this->instanceId;

		$this->comments = null;

		$this->createControls();
		$this->handlePostBack();
		$this->loadData();
	}

	private function createControls()
	{
		$this->txtName = new SMInput($this->uid . "Name", SMInputType::$Text);
		$this->txtName->SetAttribute(SMInputAttributeText::$Style, "width: 150px");
		$this->txtName->SetAttribute(SMInputAttributeText::$MaxLength, "255");

		$this->txtComment = new SMInput($this->uid . "Comment", SMInputType::$Textarea);
		$this->txtComment->SetAttribute(SMInputAttributeText::$Style, "width: 250px; height: 70px");
		$this->txtComment->SetAttribute(SMInputAttributeTextarea::$MaxLength, "1000");

		$this->cmdSubmit = new SMLinkButton($this->uid . "Submit");
		$this->cmdSubmit->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));
		$this->cmdSubmit->SetTitle($this->lang->GetTranslation("Submit"));
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdSubmit->PerformedPostBack() === true)
			{
				if ($this->txtName->GetValue() === "" || $this->txtComment->GetValue() === "")
				{
					$this->msg = $this->lang->GetTranslation("MissingData");
					return;
				}

				if (strlen($this->txtName->GetValue()) > 30)
				{
					$this->msg = $this->lang->GetTranslation("NameLengthExceeded");
					return;
				}

				if (strlen($this->txtComment->GetValue()) > 1000)
				{
					$this->msg = $this->lang->GetTranslation("CommentLengthExceeded");
					return;
				}

				$comment = new SMCommentsItem($this->pageId, $this->instanceId, SMRandom::CreateGuid(), $this->txtName->GetValue(), $this->txtComment->GetValue());
				$result = $comment->CommitPersistent();

				if ($result === false)
				{
					$this->msg = $this->lang->GetTranslation("UnknownError");
					return;
				}

				$this->msg = $this->lang->GetTranslation("CommentSaved");
				$this->clearForm();
			}
			else
			{
				if (SMAuthentication::Authorized() === true)
				{
					$this->loadData();

					$comment = null;
					for ($i = 0 ; $i < count($this->comments) ; $i++)
					{
						$comment = $this->comments[$i];

						if ($comment["cmdDelete"]->PerformedPostBack() === true)
						{
							$comment["comment"]->DeletePersistent();
							unset($this->comments[$i]);
							break;
						}
					}
				}
			}
		}
	}

	private function loadData()
	{
		if ($this->comments !== null)
			return;

		$comments = SMCommentsLoader::GetComments($this->pageId, $this->instanceId);

		$this->comments = array();
		$cmdDelete = null;

		foreach ($comments as $comment)
		{
			if (SMAuthentication::Authorized() === true)
			{
				$cmdDelete = new SMLinkButton($this->uid . "Delete" . $comment->GetCommentId());
				$cmdDelete->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
				$cmdDelete->SetTitle($this->lang->GetTranslation("Delete"));
				$cmdDelete->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteWarning", true) . "') === false) { return false; }");
			}

			$this->comments[] = array(
				"comment"	=> $comment,
				"cmdDelete"	=> $cmdDelete
			);
		}
	}

	private function clearForm()
	{
		$this->txtName->SetValue("");
		$this->txtComment->SetValue("");
	}

	public function Render()
	{
		$output = "";

		if ($this->msg !== "")
			$output .= SMNotify::Render($this->msg);

		$output .= "
		<table>
			<tr>
				<td style=\"width: 100px\">" . $this->lang->GetTranslation("Name") . "</td>
				<td style=\"width: 250px\">" . $this->txtName->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">" . $this->lang->GetTranslation("Comment") . "</td>
				<td style=\"width: 250px\">" . $this->txtComment->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">&nbsp;</td>
				<td style=\"width: 250px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">&nbsp;</td>
				<td style=\"width: 250px\"><div style=\"text-align: right\">" . $this->cmdSubmit->Render() . "</div></td>
			</tr>
		</table>
		";

		$fieldSet = new SMFieldset("SMComments" . (string)$this->instanceId);
		$fieldSet->SetDisplayFrame(false);
		$fieldSet->SetContent($output);
		$fieldSet->SetPostBackControl($this->cmdSubmit->GetClientId());
		$output = $fieldSet->Render();

		foreach ($this->comments as $comment)
		{
			$output .= "
			<br>
			<hr style=\"height: 1px; border-bottom-style: none\">
			" . (($comment["cmdDelete"] !== null) ? $comment["cmdDelete"]->Render() . "&nbsp;&nbsp;" : "") . "
			<b>" . SMStringUtilities::HtmlEncode($comment["comment"]->GetName()) . "</b> (" . date($this->lang->GetTranslation("DateTimeFormat"), $comment["comment"]->GetTimeStamp()) . ")
			<hr style=\"height: 1px; border-bottom-style: none\">
			" . nl2br(SMStringUtilities::HtmlEncode($comment["comment"]->GetComment())) . "
			<br>
			";
		}

		return $output;
	}
}

?>
