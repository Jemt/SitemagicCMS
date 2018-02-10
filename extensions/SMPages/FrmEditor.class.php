<?php

class SMPagesFrmEditor implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $error;

	private $page;
	private $txtContent;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMPages");
		$this->error = "";

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtContent = new SMInput("SMPagesContent", SMInputType::$Textarea);
		$this->loadPage();

		//if ($this->context->GetForm()->PostBack() === false)
		//{
			$this->createLinkList();
			$this->createFileList("images");
			$this->createFileList("media");
			$this->createExtensionList();
		//}
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true && SMEnvironment::GetPostValue("SMPagesDisableSave") === null)
			$this->savePage();

		$this->txtContent->SetValue(str_replace("{[", "{##[", str_replace("]}", "]##}", $this->txtContent->GetValue()))); // Preserve placeholders
	}

	private function savePage()
	{
		$pageGuid = SMEnvironment::GetQueryValue("SMPagesId", SMValueRestriction::$Guid);
		$page = SMPagesPage::GetPersistentByGuid((($pageGuid !== null) ? $pageGuid : ""));

		if ($page === null)
		{
			$this->error = $this->lang->GetTranslation("PageMissing");
			return;
		}

		$page->SetContent($this->txtContent->GetValue());
		$page->CommitPersistent();
	}

	private function loadPage()
	{
		$pageGuid = SMEnvironment::GetQueryValue("SMPagesId", SMValueRestriction::$Guid);
		$this->page = SMPagesPage::GetPersistentByGuid((($pageGuid !== null) ? $pageGuid : ""));

		if ($this->page === null)
		{
			$this->error = $this->lang->GetTranslation("PageMissing");
			$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", "404"));
			return;
		}

		if ($this->context->GetForm()->PostBack() === false)
			$this->txtContent->SetValue($this->page->GetContent());

		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->page->GetFilename()));
	}

	private function createLinkList()
	{
		$editorListDir = SMEnvironment::GetFilesDirectory() . "/editor";

		if (SMFileSystem::FolderExists($editorListDir) === false)
		{
			$result = SMFileSystem::CreateFolder($editorListDir);

			if ($result === false)
				return;
		}

		$links = SMPagesLinkList::GetInstance()->GetLinkCollection();
		$linksStr = "";
		$category = "";

		foreach ($links as $link)
		{
			if ($linksStr !== "")
				$linksStr .= ",";

			if ($category !== $link["category"])
			{
				$category = $link["category"];
				$linksStr .= "[\"\", \"\"],";
				$linksStr .= "[\"" . str_replace("\"", "\\\"", $link["category"]) . "\", \"\"],";
			}

			$linksStr .= "[\" - " . str_replace("\"", "\\\"", $link["title"]) . "\", \"" . $link["url"] . "\"]";
		}

		$linksStr = "var tinyMCELinkList = new Array(" . $linksStr . ");";

		$writer = new SMTextFileWriter($editorListDir . "/links.js", SMTextFileWriteMode::$Overwrite);
		$writer->Write($linksStr);
		$writer->Close();
	}

	public function createFileList($type)
	{
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		// createLinkList() should be executed before this function, which have ensured the existence of the editor folder
		$editorListDir = SMEnvironment::GetFilesDirectory() . "/editor";
		$fileDirectory = SMEnvironment::GetFilesDirectory() . "/" . $type;

		if (SMFileSystem::FolderExists($fileDirectory) === false)
			return;

		$filesStr = "";
		$filesStr = $this->createFileListRecursively($fileDirectory, $filesStr, $type);
		$filesStr = "var tinyMCE" . (($type === "images") ? "Image" : "Media") . "List = new Array(" . $filesStr . ");";

		$writer = new SMTextFileWriter($editorListDir . "/" . $type . ".js", SMTextFileWriteMode::$Overwrite);
		$writer->Write($filesStr);
		$writer->Close();
	}

	public function createFileListRecursively($folder, $list, $type)
	{
		SMTypeCheck::CheckObject(__METHOD__, "folder", $folder, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "list", $list, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		$files = SMFileSystem::GetFiles($folder);

		foreach ($files as $file)
		{
			if ($list !== "")
				$list .= ",";

			$list .= "[\"" . str_replace(SMEnvironment::GetFilesDirectory() . "/" . $type, "", $folder) . "/" . $file . "\", \"" . $folder . "/" . $file . "\"]";
		}

		$subfolders = SMFileSystem::GetFolders($folder);

		foreach ($subfolders as $subfolder)
			$list = $this->createFileListRecursively($folder . "/" . $subfolder, $list, $type);

		return $list;
	}

	public function createExtensionList()
	{
		// createLinkList() should be executed before this function, which have ensured the existence of the editor folder
		$editorListDir = SMEnvironment::GetFilesDirectory() . "/editor";

		$extensions = SMPagesExtensionList::GetInstance()->GetExtensionCollection();
		$extensionsStr = "";

		$category = "";
		foreach ($extensions as $ext)
		{
			if ($extensionsStr !== "")
				$extensionsStr .= ",";

			if ($ext["category"] !== $category)
			{
				$category = $ext["category"];
				$extensionsStr .= "[\"\", \"\", \"\", \"\", \"\"],";
				$extensionsStr .= "[\"" . str_replace("\"", "\\\"", $ext["category"]) . "\", \"" . str_replace("\"", "\\\"", $ext["category"]) . "\", \"\", \"\", \"\"],";
			}

			$extensionsStr .= "[\"" . str_replace("\"", "\\\"", $ext["category"]) . "\", \" - " . str_replace("\"", "\\\"", $ext["title"]) . "\", \"" . $ext["extension"] . "\", \"" . $ext["file"] . "\", \"" . $ext["class"] . "\", \"" . str_replace("\"", "\\\"", $ext["argument"]) . "\", \"" . $ext["width"] . "\", \"" . $ext["height"] . "\"]";
		}

		$extensionsStr = "var smextensionsExtensionList = new Array(" . $extensionsStr . ");";

		$writer = new SMTextFileWriter($editorListDir . "/extensions.js", SMTextFileWriteMode::$Overwrite);
		$writer->Write($extensionsStr);
		$writer->Close();
	}

	public function Render()
	{
		// Force IE11 into IE10 Document Mode since it is not fully compatible with TinyMCE 3.5.7 (e.g. HTML Source plugin is not working)
		// NOTICE: Same code found in FrmPages.class.php since Document Mode cannot be changed for iFrames individually. Also changed here
		// in case Legacy Mode is enabled for pop ups, which spawns a new browser window.
		// TODO: Upgrade to 3.5.11 and later switch to TinyMCE 4 when we are ready for it.
		//header("X-UA-Compatible: IE=10,chrome=1");

		$template = ((SMTemplateInfo::GetTemplateOverridden() === true) ? SMTemplateInfo::GetCurrentTemplate() : SMTemplateInfo::GetPublicTemplate()); // Avoid use of AdminTemplate - we want to see what the users will see (PublicTemplate)
		$basicCssFile = SMTemplateInfo::GetBasicCssFile($template);
		$basicCssFile = (($basicCssFile !== null) ? $basicCssFile . "?v=" . SMEnvironment::GetVersion() : null);
		$overrideCssFile = SMTemplateInfo::GetOverrideCssFile($template);
		$overrideCssFile = (($overrideCssFile !== null) ? $overrideCssFile . "?v=" . SMEnvironment::GetVersion() . "&c=" . SMEnvironment::GetClientCacheKey() : null);

		$language = ((SMFileSystem::FileExists(SMExtensionManager::GetExtensionPath($this->context->GetExtensionName()) . "/editor/langs/" . SMLanguageHandler::GetSystemLanguage() . ".js") === true) ? SMLanguageHandler::GetSystemLanguage() : "en");
		$listdir = SMEnvironment::GetFilesDirectory() . "/editor";

		$cfg = SMEnvironment::GetConfiguration();
		$legacyTables = ($cfg->GetEntry("SMPagesLegacyTables") !== null && strtolower($cfg->GetEntry("SMPagesLegacyTables")) === "true");

		$output = "";

		if ($this->error !== "")
			$output .= "<script type=\"text/javascript\">SMMessageDialog.ShowMessageDialogOnLoad(\"" . $this->error . "\");</script>";

		$output .= "
		<script type=\"text/javascript\" src=\"" . SMExtensionManager::GetExtensionPath("SMPages") . "/editor/tiny_mce.js?ver=" . SMEnvironment::GetVersion() . "\"></script>
		<script type=\"text/javascript\">
		window.SMPagesLegacyTables = " . ($legacyTables ? "true" : "false") . ";
		if (window.SMPagesLegacyTables) SMDom.AddClass(document.body.parentElement, \"SMPagesEditorEnableHiddenOptions\");

		var smPagesResizeTimer = null;
		var smPagesPageSize = SMBrowser.GetPageWidth() + \"x\" + SMBrowser.GetPageHeight();

		SMEventHandler.AddEventHandler(window, \"resize\", function()
		{
			// TinyMCE 3.5b3: Word wrapping does not work properly in FullScreen mode.
			// In older versions of IE the word wrapping is broken on initial load, while
			// on more recent versions it breaks when pop up window is being resized.
			// The solution is to avoid use of FullScreen mode. Instead we have the editor
			// adjust to the size of the window using the width and height properties set
			// during init. The page is being reloaded on resize to have the editor re-initialize
			// with the width and height. Unfortunately these cannot be changed after initialization.

			// IE fix: IE versions prior to IE 9 fires the window resize event if DOM elements are
			// resized, which happens when the editor is initialized.
			// Make sure the size of the page has actually changed before proceeding.
			if (smPagesPageSize === SMBrowser.GetPageWidth() + \"x\" + SMBrowser.GetPageHeight())
				return;

			// Cancel timer job responsible for reloading page.
			// Resize event may be triggered multiple times while resizing.
			if (smPagesResizeTimer !== null)
				clearTimeout(smPagesResizeTimer);

			// Add layer informing user that content is being adjusted
			// according to new window size (only done once).
			if (smPagesResizeTimer === null)
			{
				window.smPagesEditorSaving = true; // Prevent saving layer from being displayed
				smPagesCreateLayer(SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("Resizing") . "\"), SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("ResizingDescription") . "\"));
			}

			// Create timer job responsible for reloading page
			smPagesResizeTimer = setTimeout(function()
			{
				SMCookie.SetCookie(\"SMPagesEditorSize\", SMBrowser.GetPageWidth() + \"x\" + SMBrowser.GetPageHeight(), 365 * 24 * 60 * 60);

				// Add field used to disable save operation server side.
				// We only want to preserve data client side on post back.
				// See handlePostBack() function - search for SMPagesDisableSave.

				var txt = document.createElement(\"input\");
				txt.name = \"SMPagesDisableSave\";
				txt.value = \"true\";
				document.getElementById(\"SMForm\").appendChild(txt);

				// Invoke the save command to reload the page.
				// Data is not actually saved thanks to the
				// SMPagesDisableSave input field registered above.

				tinyMCE.activeEditor.execCommand(\"mceSave\");
			}, 500);
		});

		SMEventHandler.AddEventHandler(window, \"load\", function()
		{
		document.getElementsByTagName(\"body\")[0].style.margin = \"0px\";
		document.getElementsByTagName(\"body\")[0].style.overflow = \"hidden\";

		document.body.style.display = \"none\"; // Hide editor until various CSS classes has been registered to avoid Flash of Unstyled Content (see OnInit event handler)

		var txt = document.getElementById(\"" . $this->txtContent->GetClientId() . "\");
		txt.value = txt.value.replace(/{##\[/g, \"{\" + \"[\").replace(/\]##}/g, \"]\" + \"}\"); // Restore placeholders

		tinyMCE.init({
			// General options
			mode : \"exact\",
			elements : \"" . $this->txtContent->GetClientId() . "\",
			theme : \"advanced\",
			plugins : \"safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras\" + ((SMBrowser.GetBrowser() === \"MSIE\" && SMBrowser.GetVersion() === 7) ? \"\" : \",inlinepopups\") + \",smextensions\",

			// Theme options
			theme_advanced_buttons1 : \"save,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect\",
			theme_advanced_buttons2 : \"smextensions,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor\",
			theme_advanced_buttons3 : \"table,delete_table,|,row_props,cell_props,|,delete_col,delete_row,col_before,col_after,row_before,row_after" . (($legacyTables === true) ? ",|,split_cells,merge_cells" : "") . ",|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl\",
			theme_advanced_buttons4 : \"insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,pagebreak\",
			theme_advanced_toolbar_location : \"top\",
			theme_advanced_toolbar_align : \"left\",
			theme_advanced_statusbar_location : \"bottom\",

			// Clear lists with CSS classes (must have a value to prevent TinyMCE from detecting available classes)
			theme_advanced_styles : \" \",
			" . (($legacyTables === false) ? "
			table_styles : \"Data table=SMPagesDataTable;-----= ;Fluid Grid=SMPagesFluidGrid;Fluid Grid (stack below 900px)=SMPagesFluidGridStack900;Fluid Grid (stack below 700px)=SMPagesFluidGridStack700;Fluid Grid (stack below 500px)=SMPagesFluidGridStack500;-----= ;Fluid Grid Cards=SMPagesFluidGrid SMPagesGridCards;Fluid Grid Cards (stack below 900px)=SMPagesFluidGridStack900 SMPagesGridCards;Fluid Grid Cards (stack below 700px)=SMPagesFluidGridStack700 SMPagesGridCards;Fluid Grid Cards (stack below 500px)=SMPagesFluidGridStack500 SMPagesGridCards\",
			table_cell_styles : \"Spacing=SMPagesTableCellSpacing\",
			table_row_styles : \" \"," : "") . "
			advlink_styles: \"Action button (primary)=SMPagesActionButton SMPagesActionButtonPrimary;Action button (secondary)=SMPagesActionButton SMPagesActionButtonSecondary\",

			content_css : \"" . SMEnvironment::GetExternalUrl() . "/" . SMExtensionManager::GetExtensionPath("SMPages") . "/editor.css?ver=" . SMEnvironment::GetVersion() . (($basicCssFile !== null) ? "," . SMEnvironment::GetExternalUrl() . "/" . $basicCssFile : "") . (($overrideCssFile !== null) ? "," . SMEnvironment::GetExternalUrl() . "/" . $overrideCssFile : "") . "\",

			style_formats:
			[
				/* Use block property to create a new outer element or the inline property to create new element within current element */

				{ title: SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("CardSmall") . "\"), block: \"div\", attributes: {\"class\": \"SMPagesCard SMPagesCardSmall\"} },
				{ title: SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("CardMedium") . "\"), block: \"div\", attributes: {\"class\": \"SMPagesCard SMPagesCardMedium\"} },
				{ title: SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("CardLarge") . "\"), block: \"div\", attributes: {\"class\": \"SMPagesCard SMPagesCardLarge\"} },
				{ title: SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("CardHidden") . "\"), block: \"div\", attributes: {\"class\": \"SMPagesCard SMPagesCardHidden\"} },
				{ title: SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("CardHeader") . "\"), inline: \"span\", attributes: {\"class\": \"SMPagesCardHeader\" } },
				{ title: SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("CardFooter") . "\"), inline: \"span\", attributes: {\"class\": \"SMPagesCardFooter\" } }
			],

			language : \"" . $language . "\",
			element_format : \"html\",
			entity_encoding : \"numeric\",
			doctype : \"<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01//EN' 'http://www.w3.org/TR/html4/strict.dtd'>\",
			/*oninit : function() { tinyMCE.get(\"" . $this->txtContent->GetClientId() . "\").execCommand(\"mceFullScreen\"); },*/
			width: \"100%\",
			height: SMBrowser.GetPageHeight() + \"px\",
			dialog_type : \"modal\",

			external_link_list_url : \"" . $listdir . "/links.js?ran=" . SMRandom::CreateGuid() . "\",
			external_image_list_url : \"" . $listdir . "/images.js?ran=" . SMRandom::CreateGuid() . "\",
			media_external_list_url : \"" . $listdir . "/media.js?ran=" . SMRandom::CreateGuid() . "\",
			smextensions_extension_list_url : \"" . $listdir . "/extensions.js?ran=" . SMRandom::CreateGuid() . "\",

			setup : function(ed)
			{
				ed.onSaveContent.add(function(ed, o) // Fires twice
				{
					if (window.smPagesEditorSaving === undefined)
					{
						window.smPagesEditorSaving = true;
						smPagesCreateLayer(SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("Saving") . "\"), SMStringUtilities.UnicodeDecode(\"" . $this->lang->GetTranslation("SavingDescription") . "\"));
					}
				});

				ed.onInit.add(function(ed)
				{
					var ifr = document.getElementById(\"SMInputSMPagesContent_ifr\");
					var htmlElement = ifr.contentWindow.document.body.parentElement;

					// Set classes on html element within editable iFrame
					SMDom.AddClass(htmlElement, \"SMPagesEditor\");
					SMDom.AddClass(htmlElement, \"SMPages" . (($this->page !== null && strpos($this->page->GetFilename(), "#") === 0) ? "SystemPage" : "ContentPage") . "\");
					SMDom.AddClass(htmlElement, \"SMPagesFilename" . (($this->page !== null) ? str_replace("#", "", $this->page->GetFilename()) : "_NOT_FOUND") . "\");
					SMDom.AddClass(htmlElement, \"SMPagesPageId" . (($this->page !== null) ? $this->page->GetId() : "_NOT_FOUND") . "\");
					smPagesSetPageLayout(ed, htmlElement); // Make sure page is initially set to correct Page Layout (Classic or Card)

					document.body.style.display = \"\"; // Page is ready, make it visible again by removing display:none
				});
			},

			onchange_callback: \"smPagesEditorChangeCallback\"
		});
		});

		function smPagesEditorChangeCallback(editor)
		{
			smPagesSetPageLayout(editor);
		}

		function smPagesSetPageLayout(editor, htmlElm)
		{
			var htmlElement = null;

			if (htmlElm === undefined)
			{
				var ifr = document.getElementById(\"SMInputSMPagesContent_ifr\");
				htmlElement = ifr.contentWindow.document.body.parentElement;
			}
			else
			{
				htmlElement = htmlElm;
			}

			if (editor.getBody().innerHTML.toLowerCase().indexOf('<div class=\"smpagescard') > -1) // toLowerCase() since IE7 upper cases tag name
			{
				SMDom.RemoveClass(htmlElement, \"SMPagesClassicLayout\");
				SMDom.AddClass(htmlElement, \"SMPagesCardLayout\");
			}
			else
			{
				SMDom.RemoveClass(htmlElement, \"SMPagesCardLayout\");
				SMDom.AddClass(htmlElement, \"SMPagesClassicLayout\");
			}
		}

		function smPagesCreateLayer(title, msg)
		{
			var layer = document.createElement(\"div\");
			layer.style.position = \"absolute\";
			layer.style.zIndex = \"999999\";
			layer.style.top = \"0\";
			layer.style.left = \"0\";
			layer.style.width = \"5000px\";
			layer.style.height = \"5000px\";
			layer.style.padding = \"20px\";
			layer.style.backgroundColor = \"white\";

			var h1 = document.createElement(\"h1\");
			h1.appendChild(document.createTextNode(title));
			h1.style.color = \"black\";
			layer.appendChild(h1);

			var p = document.createElement(\"p\");
			p.appendChild(document.createTextNode(msg));
			p.style.color = \"black\";
			layer.appendChild(p);

			document.body.appendChild(layer);
		}
		</script>
		" . $this->txtContent->Render();

		return $output;
	}
}

?>
