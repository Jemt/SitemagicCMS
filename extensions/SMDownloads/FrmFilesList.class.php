<?php

class SMDownloadsFrmFilesList implements SMIExtensionForm
{
	private $context;
	private $lang;

	private $list;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMDownloads");

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$files = SMFileSystem::GetFiles(SMEnvironment::GetFilesDirectory() . "/downloads");
		$data = array();

		foreach ($files as $file)
			$data[] = array(
				$this->lang->GetTranslation("Files")	=> $file,
				$this->lang->GetTranslation("Fetched")	=> $this->getDownloadCount($file)
			);

		$this->list = new SMGrid("SMDownloadsFilesList");
		$this->list->SetData($data);
	}

	private function getDownloadCount($file)
	{
		SMTypeCheck::CheckObject(__METHOD__, "file", $file, SMTypeCheckType::$String);

		$ds = new SMDataSource("SMDownloads");
		$records = $ds->Select("count", "file = '" . $ds->Escape($file) . "'");

		if (count($records) === 0)
			return "0";

		return $records[0]["count"];
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
		}
	}

	public function Render()
	{
		$output = $this->list->Render();

		$fieldset = new SMFieldset("SMDownloads");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "width: 400px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("Files"));

		return $fieldset->Render();
	}
}

?>
