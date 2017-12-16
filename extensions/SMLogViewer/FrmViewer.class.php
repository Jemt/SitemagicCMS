<?php

class SMLogViewerFrmViewer implements SMIExtensionForm
{
	private $context;
	private $lang;

	private $grid;
	private $cmdRefresh;
	private $cmdClear;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMLogViewer");

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("Title")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$ds = new SMDataSource("SMLog");
		$log = $ds->Select();

		$this->grid = new SMGrid("SMLogViewer");
		$this->grid->SetNewLineToLineBreak(true);
		$this->grid->SetData($log);

		$this->cmdRefresh = new SMLinkButton("SMLogViewerRefresh");
		$this->cmdRefresh->SetTitle($this->lang->GetTranslation("Refresh"));
		$this->cmdRefresh->SetIcon(SMImageProvider::GetImage(SMImageType::$Update));

		$this->cmdClear = new SMLinkButton("SMLogViewerClear");
		$this->cmdClear->SetTitle($this->lang->GetTranslation("Clear"));
		$this->cmdClear->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdClear->SetOnclick(" if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("ClearWarning") . "') === false) { return; } ");
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdRefresh->PerformedPostBack() === true)
			{
				// Do nothing, post back caused data to be reloaded
			}
			else if ($this->cmdClear->PerformedPostBack() === true)
			{
				$ds = new SMDataSource("SMLog");
				$ds->Delete();

				// Commit immediately in case errors needs to be logged after this stage.
				// If changes are not committed, the SMErrorHandler will not be able to
				// place a lock on the Data Source due to the change:
				// Unable to lock data source 'SMLog' - uncommitted data found
				$ds->Commit();

				$this->grid->SetData(array());
			}
		}
	}

	public function Render()
	{
		// Register CSS

		$css = "";
		$css .= "\t<style type=\"text/css\">";
		$css .= "\n		fieldset.SMLogViewer table { border-collapse: collapse; }";
		$css .= "\n		fieldset.SMLogViewer td { border: 1px solid #333333; padding: 3px; white-space: nowrap; vertical-align: top; }";
		$css .= "\n\t</style>\n";

		$this->context->GetTemplate()->AddToHeadSection($css);

		// Generate output

		$output = "";
		$output .= $this->cmdRefresh->Render();
		$output .= " ";
		$output .= $this->cmdClear->Render();
		$output .= "<br><br>";

		if (count($this->grid->GetData()) > 0)
			$output .= $this->grid->Render();
		else
			$output .= $this->lang->GetTranslation("NoEntries");

		$fieldset = new SMFieldset("SMLogViewer");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Class, "SMLogViewer");
		$fieldset->SetDisplayFrame(false);
		$fieldset->SetContent($output);

		return "<h1>" . $this->lang->GetTranslation("Title") . "</h1>" . $fieldset->Render();
	}
}

?>
