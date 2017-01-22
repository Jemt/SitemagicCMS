<?php

class SMMenuFrmMenu implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $manager;

	private $messageForm;
	private $messageTree;

	private $txtItemId;
	private $txtTitle;
	private $txtUrl;
	private $cmdCreate;
	private $cmdClear;
	private $cmdSave;
	private $cmdBrowseLinks;

	private $treeMenu;
	private $cmdEdit;
	private $cmdMoveUp;
	private $cmdMoveDown;
	private $cmdDelete;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMMenu");
		$this->manager = SMMenuManager::GetInstance();

		$this->messageForm = "";
		$this->messageTree = "";

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("MenuTitle")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtItemId = new SMInput("SMMenuItemId", SMInputType::$Hidden);

		$this->txtTitle = new SMInput("SMMenuTitle", SMInputType::$Text);
		$this->txtTitle->SetAttribute(SMInputAttributeText::$Style, "width: 200px");
		$this->txtTitle->SetAttribute(SMInputAttributeText::$MaxLength, "255");

		$this->txtUrl = new SMInput("SMMenuUrl", SMInputType::$Text);
		$this->txtUrl->SetAttribute(SMInputAttributeText::$Style, "width: 200px");
		$this->txtUrl->SetAttribute(SMInputAttributeText::$MaxLength, "255");
		if ($this->context->GetForm()->PostBack() === false)
			$this->txtUrl->SetValue("http://");

		$this->cmdCreate = new SMLinkButton("SMMenuCreate");
		$this->cmdCreate->SetTitle($this->lang->GetTranslation("CmdCreate"));
		$this->cmdCreate->SetIcon(SMImageProvider::GetImage(SMImageType::$Create));

		$this->cmdClear = new SMLinkButton("SMMenuCreateNew");
		$this->cmdClear->SetTitle($this->lang->GetTranslation("CmdClear"));
		$this->cmdClear->SetIcon(SMImageProvider::GetImage(SMImageType::$Clear));

		$this->cmdSave = new SMLinkButton("SMMenuUpdate");
		$this->cmdSave->SetTitle($this->lang->GetTranslation("CmdSave"));
		$this->cmdSave->SetIcon(SMImageProvider::GetImage(SMImageType::$Save));

		$this->cmdBrowseLinks = new SMLinkButton("SMMenuBrowseLinks");
		$this->cmdBrowseLinks->SetIcon(SMImageProvider::GetImage(SMImageType::$Browse));
		$this->cmdBrowseLinks->SetTitle($this->lang->GetTranslation("CmdSelect"));
		$this->cmdBrowseLinks->SetPostBack(false);
		$this->cmdBrowseLinks->SetOnclick("smMenuOpenLinkList()");

		$this->createTreeMenu();

		$this->cmdEdit = new SMLinkButton("SMMenuEdit");
		$this->cmdEdit->SetTitle($this->lang->GetTranslation("CmdEdit"));
		$this->cmdEdit->SetIcon(SMImageProvider::GetImage(SMImageType::$Properties));

		$this->cmdDelete = new SMLinkButton("SMMenuDelete");
		$this->cmdDelete->SetTitle($this->lang->GetTranslation("CmdDelete"));
		$this->cmdDelete->SetIcon(SMImageProvider::GetImage(SMImageType::$Delete));
		$this->cmdDelete->SetOnclick("if (SMMessageDialog.ShowConfirmDialog('" . $this->lang->GetTranslation("DeleteWarning", true) . "') === false) { return false; }");

		$this->cmdMoveUp = new SMLinkButton("SMMenuMoveUp");
		$this->cmdMoveUp->SetIcon(SMImageProvider::GetImage(SMImageType::$Up));

		$this->cmdMoveDown = new SMLinkButton("SMMenuMoveDown");
		$this->cmdMoveDown->SetIcon(SMImageProvider::GetImage(SMImageType::$Down));
	}

	private function createTreeMenu()
	{
		$top = new SMTreeMenuItem("SMMenuRootItem", $this->lang->GetTranslation("Root"), "");
		$this->populateTreeMenuItem($top);

		$this->treeMenu = new SMTreeMenu("SMMenuTree");
		$this->treeMenu->AddChild($top);
	}

	private function populateTreeMenuItem(SMTreeMenuItem $top)
	{
		$treeItem = null;
		$children = $this->manager->GetChildren();

		foreach ($children as $child)
		{
			$treeItem = new SMTreeMenuItem($child->GetId(), $child->GetTitle(), $child->GetId());
			$this->populateTreeMenuItemChildren($treeItem, $child);
			$top->AddChild($treeItem);
		}
	}

	private function populateTreeMenuItemChildren(SMTreeMenuItem $root, SMMenuItem $item)
	{
		$children = $item->GetChildren();
		$treeItem = null;

		foreach ($children as $child)
		{
			$treeItem = new SMTreeMenuItem($child->GetId(), $child->GetTitle(), $child->GetId());
			$this->populateTreeMenuItemChildren($treeItem, $child);
			$root->AddChild($treeItem);
		}
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdCreate->PerformedPostBack() === true)
				$this->createItem();
			else if ($this->cmdSave->PerformedPostBack() === true)
				$this->updateItem();
			else if ($this->cmdDelete->PerformedPostBack() === true)
				$this->deleteItem();
			else if ($this->cmdMoveUp->PerformedPostBack() === true || $this->cmdMoveDown->PerformedPostBack() === true)
				$this->moveItem();
			else if ($this->cmdEdit->PerformedPostBack() === true)
				$this->setEditMode();
			else if ($this->cmdClear->PerformedPostBack() === true)
				$this->clearForm();
		}
	}

	private function createItem()
	{
		if ($this->txtTitle->GetValue() === "")
		{
			$this->messageForm = $this->lang->GetTranslation("SetTitle");
			return;
		}

		if ($this->treeMenu->GetSelectionMade() === true && $this->treeMenu->GetSelectedValue() === null)
		{
			$this->messageForm = $this->lang->GetTranslation("SelectedMissing");
			return;
		}

		$parentId = $this->treeMenu->GetSelectedValue();
		$newItem = new SMMenuItem(SMRandom::CreateGuid(), $this->txtTitle->GetValue(), $this->txtUrl->GetValue());

		if ($parentId !== null && $parentId !== "")
		{
			$parent = $this->manager->GetChild($parentId, true);
			$newItem = $parent->AddChild($newItem);
			$newItem->CommitPersistent();
		}
		else
		{
			$newItem = $this->manager->AddChild($newItem);
			$newItem->CommitPersistent();
		}

		$this->createTreeMenu();
		$this->clearForm();
	}

	private function clearForm()
	{
		$this->txtItemId->SetValue("");
		$this->txtTitle->SetValue("");
		$this->txtUrl->SetValue("http://");
	}

	private function updateItem()
	{
		if ($this->txtTitle->GetValue() === "")
		{
			$this->messageForm = $this->lang->GetTranslation("SetTitle");
			return;
		}

		$item = $this->manager->GetChild($this->txtItemId->GetValue(), true);

		if ($item === null)
		{
			$this->messageForm = $this->lang->GetTranslation("SelectedMissing");
			return;
		}

		$item->SetTitle($this->txtTitle->GetValue());
		$item->SetUrl($this->txtUrl->GetValue());

		$item->CommitPersistent();
		$this->createTreeMenu();

		$this->clearForm();
	}

	private function deleteItem()
	{
		if ($this->treeMenu->GetSelectionMade() === false)
		{
			$this->messageTree = $this->lang->GetTranslation("MakeSelection");
			return;
		}

		$id = $this->treeMenu->GetSelectedValue();

		if ($id === null)
		{
			$this->messageTree = $this->lang->GetTranslation("SelectedMissing");
			return;
		}

		if ($id === "")
		{
			$this->messageTree = $this->lang->GetTranslation("RootItemError");
			return;
		}

		// We trust that $item will not be null, if $id is not, as
		// the item has just been read, and added to the tree a while ago.
		$item = $this->manager->GetChild($id, true);

		$parentId = $item->GetParentId();

		if ($parentId !== "")
		{
			$parent = $this->manager->GetChild($parentId, true);
			$parent->RemoveChild($item->GetId(), true);
		}
		else
		{
			$this->manager->RemoveChild($item->GetId(), true);
		}

		$this->createTreeMenu();
		$this->treeMenu->SetRestoreSelection(false);
	}

	private function moveItem()
	{
		if ($this->treeMenu->GetSelectionMade() === false)
		{
			$this->messageTree = $this->lang->GetTranslation("MakeSelection");
			return;
		}

		$id = $this->treeMenu->GetSelectedValue();

		if ($id === null)
		{
			$this->messageTree = $this->lang->GetTranslation("SelectedMissing");
			return;
		}

		if ($id === "")
		{
			$this->messageTree = $this->lang->GetTranslation("RootItemError");
			return;
		}

		if ($this->cmdMoveUp->PerformedPostBack() === true)
			$this->manager->MoveChildUp($id, true);
		else
			$this->manager->MoveChildDown($id, true);

		$this->createTreeMenu();
	}

	private function setEditMode()
	{
		if ($this->treeMenu->GetSelectionMade() === false)
		{
			$this->messageTree = $this->lang->GetTranslation("MakeSelection");
			return;
		}

		$id = $this->treeMenu->GetSelectedValue();

		if ($id === null)
		{
			$this->messageTree = $this->lang->GetTranslation("SelectedMissing");
			return;
		}

		if ($id === "")
		{
			$this->messageTree = $this->lang->GetTranslation("RootItemError");
			return;
		}

		// We trust that $item will not be null, if $id is not, as
		// the item has just been read, and added to the tree a while ago.
		$item = $this->manager->GetChild($id, true);

		$this->txtItemId->SetValue($item->GetId());
		$this->txtTitle->SetValue($item->GetTitle());
		$this->txtUrl->SetValue($item->GetUrl());
	}

	private function inEditMode()
	{
		$val = $this->txtItemId->GetValue();
		return ($val !== null && $val !== "");
	}

	public function Render()
	{
		$output = "";

		$output .= $this->txtItemId->Render();

		$output .= "
		<script type=\"text/javascript\">
		var smMenuPopup = null;

		SMEventHandler.AddEventHandler(window, \"unload\", smMenuCloseLinkList);

		function smMenuCloseLinkList()
		{
			if (smMenuPopup !== null)
				smMenuPopup.Close();
		}

		function smMenuOpenLinkList()
		{
			smMenuCloseLinkList();

			var url = SMDom.GetAttribute(\"" . $this->txtUrl->GetClientId() . "\", \"value\");
			url = encodeURIComponent(url);

			smMenuPopup = new SMWindow(\"SMMenuLinkList\");
			smMenuPopup.SetSize(320, 100);
			smMenuPopup.SetCenterWindow(true);
			smMenuPopup.SetUrl(\"" . SMExtensionManager::GetExtensionUrl("SMMenu", SMTemplateType::$Basic) . "&SMMenuLinkList&SMMenuLinkSelected=\" + url + \"&SMMenuLinkReceiver=" . $this->txtUrl->GetClientId() . "\");
			smMenuPopup.Show();
		}
		</script>

		<table style=\"width: 100%\">
			<tr>
				<td style=\"vertical-align: top; width: 400px\">
					" . $this->renderForm() . "
				</td>
				<td style=\"vertical-align: top; width: 50px\">&nbsp;</td>
				<td style=\"vertical-align: top\">
					" . $this->renderTree() . "
				</td>
			</tr>
		</table>
		";

		return $output;
	}

	private function renderForm()
	{
		$output = "";

		if ($this->messageForm !== "")
			$output .= SMNotify::Render($this->messageForm);

		$output .= "
		<table>
			<tr>
				<td style=\"width: 100px\">" . $this->lang->GetTranslation("Title") . "</td>
				<td>" . $this->txtTitle->Render() . "</td>
				<td style=\"width: 80px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">" . $this->lang->GetTranslation("Url") . "</td>
				<td>" . $this->txtUrl->Render() . "</td>
				<td style=\"width: 80px\">&nbsp;" . $this->cmdBrowseLinks->Render() . "</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">&nbsp;</td>
				<td>&nbsp;</td>
				<td style=\"width: 80px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 100px\">&nbsp;</td>
				<td style=\"text-align: right\">" . (($this->inEditMode() === true) ? $this->cmdClear->Render() : "") . " " . $this->cmdCreate->Render() . " " . (($this->inEditMode() === true) ? $this->cmdSave->Render() : "") . "</td>
				<td style=\"width: 80px\"></td>
			</tr>
		</table>
		";

		$fieldset = new SMFieldset("SMMenuForm");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("LinkDetails"));
		$fieldset->SetPostBackControl((($this->inEditMode() === false) ? $this->cmdCreate->GetClientId() : $this->cmdSave->GetClientId()));

		return $fieldset->Render();
	}

	private function renderTree()
	{
		$output = "";

		if ($this->messageTree !== "")
			$output .= SMNotify::Render($this->messageTree);

		$output .= "
		" . $this->treeMenu->Render() . "
		<br>
		" . $this->cmdEdit->Render() . "
		" . $this->cmdDelete->Render() . "
		" . $this->cmdMoveUp->Render() . "
		" . $this->cmdMoveDown->Render() . "
		";

		$fieldset = new SMFieldset("SMMenuTree");
		$fieldset->SetAttribute(SMFieldsetAttribute::$Style, "max-width: 500px");
		$fieldset->SetContent($output);
		$fieldset->SetLegend($this->lang->GetTranslation("MenuStructure"));

		return $fieldset->Render();
	}
}

?>
