var SMExtensionDialog = {
	preInit : function() {
		tinyMCEPopup.requireLangPack();

		var url = null;
		if (url = tinyMCEPopup.getParam("smextensions_extension_list_url"))
			document.write('<script language="javascript" type="text/javascript" src="' + tinyMCEPopup.editor.documentBaseURI.toAbsolute(url) + '"></script>');
	},

	init : function(ed) {
		this.fillExtensionList();
	},

	fillExtensionList : function() {
		var dom = tinyMCEPopup.dom;
		var extensionList = dom.get("SMExtensionExtension");
		var extensions = window["smextensionsExtensionList"]; // string array: 0 = category, 1 = title, 2 = extension name, 3 = file name, 4 = class, 5 = argument, 6 = width, 7 = height

		if (extensions && extensions.length > 0) {
			tinymce.each(extensions, function(extension) {
				extensionList.options[extensionList.options.length] = new Option();

				if (extension[2] !== "") {
					extensionList.options[extensionList.options.length - 1].innerHTML = extension[1]; // Using .innerHTML to have HTML/HEX entities translated into symbols
					extensionList.options[extensionList.options.length - 1].value = extension[2] + "|" + extension[3] + "|" + extension[4] + "|" + extension[5] + "|" + extension[1] + "|" + extension[6] + "|" + extension[7];
					//extensionList.options[extensionList.options.length] = new Option(extension[1], extension[2] + "|" + extension[3] + "|" + extension[4] + "|" + extension[5] + "|" + extension[1] + "|" + extension[6] + "|" + extension[7]);
					//extensionList.options[extensionList.options.length] = new Option(extension[1], extension[2] + "|" + extension[4]);
				}
				else {
					extensionList.options[extensionList.options.length - 1].innerHTML = extension[1]; // Using .innerHTML to have HTML/HEX entities translated into symbols
					extensionList.options[extensionList.options.length - 1].value = "";
					//extensionList.options[extensionList.options.length] = new Option(extension[1], "");
				}
			});
		}
	},

	insert : function() {
		var dom = tinyMCEPopup.dom;
		var extensionList = dom.get("SMExtensionExtension");
		var extensions = window["smextensionsExtensionList"];

		if (extensionList.value === "")
			return;

		var selectedExtensionData = extensionList.value.split("|");
		var instanceId = Math.floor(Math.random()*9999999);
		/*var width = "150px";
		var height = "150px";
		var title = "";

		if (extensions && extensions.length > 0) {
			tinymce.each(extensions, function(extension) {

				if (extension[2] + "|" + extension[4] === selectedExtensionData[0] + "|" + selectedExtensionData[1])
				{
					width = extension[4];
					height = extension[5];
					title = extension[0] + extension[1];
				}
			});
		}*/

		var src = tinyMCEPopup.getWindowArg("plugin_url") + "/img/placeholder.gif";
		var alt = selectedExtensionData[0] + "|" + selectedExtensionData[1] + "|" + selectedExtensionData[2] + "|" + selectedExtensionData[3] + "|" + instanceId;
		var img = "<img src=\"" + src + "\" alt=\"" + alt + "\" title=\"" + selectedExtensionData[4] + "\" width=\"" + selectedExtensionData[5] + "\" height=\"" + selectedExtensionData[6] + "\">";

		tinyMCEPopup.restoreSelection(); // To allow insertion into div in Internet Explorer (found in advimage extension)

		tinyMCEPopup.editor.execCommand('mceInsertContent', false, img);
		tinyMCEPopup.close();
	}
};

SMExtensionDialog.preInit();
tinyMCEPopup.onInit.add(SMExtensionDialog.init, SMExtensionDialog);
