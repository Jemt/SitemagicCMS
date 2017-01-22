<?php

class SMMenuFrmLinkList implements SMIExtensionForm
{
	private $context;
	private $lang;

	private $lstLinks;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMMenu");

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Links")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->lstLinks = new SMOptionList("SMMenuLinkList");
		$this->lstLinks->SetAttribute(SMOptionListAttribute::$OnChange, "smMenuCloseWindow(this)");

		$links = SMMenuLinkList::GetInstance()->GetLinkCollection();

		$category = "";
		foreach ($links as $link)
		{
			if ($category !== $link["category"])
			{
				$category = $link["category"];
				$this->lstLinks->AddOption(new SMOptionListItem(SMRandom::CreateGuid(), "", ""));
				$this->lstLinks->AddOption(new SMOptionListItem(SMRandom::CreateGuid(), $category, ""));
			}

			$this->lstLinks->AddOption(new SMOptionListItem(SMRandom::CreateGuid(), " - " . $link["title"], $link["url"]));
		}

		if ($this->context->GetForm()->PostBack() === false)
		{
			$selected = SMEnvironment::GetQueryValue("SMMenuLinkSelected", SMValueRestriction::$None); // No value restriction - user may have entered all sorts of invalid values

			if ($selected !== null)
				$this->lstLinks->SetSelectedValue($selected);
		}
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
		}
	}

	public function Render()
	{
		$receiverControl = SMEnvironment::GetQueryValue("SMMenuLinkReceiver", SMValueRestriction::$Alpha);

		$output = "<h1>" . $this->lang->GetTranslation("Links") . "</h1>";

		$output .= "
		<script type=\"text/javascript\">
		function smMenuCloseWindow(menu)
		{
			var parentWindow = window.opener || window.top;
			var smwin = parentWindow.SMWindow.GetInstance(window.name);

			parentWindow.SMDom.SetAttribute(\"" . (($receiverControl !== null) ? $receiverControl : "") . "\", \"value\", menu.options[menu.selectedIndex].value);
			smwin.Close();
		}
		</script>

		<table>
			<tr>
				<td style=\"width: 100px\">" . $this->lang->GetTranslation("Links") . "</td>
				<td style=\"width: 150px\">" . $this->lstLinks->Render() . "</td>
			</tr>
		</table>
		";

		return $output;
	}
}

?>
