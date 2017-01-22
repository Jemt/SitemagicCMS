// http://javascriptcompressor.com/

(function() {
	tinymce.PluginManager.requireLangPack('smextensions');

	tinymce.create('tinymce.plugins.SMExtensionsPlugin', {
		init : function(ed, url) { // ed = Editor instance, url = Absolute URL to where plugin is located
			ed.addCommand('mceSMExtensions', function() { // Register command so that it can be invoked using tinyMCE.activeEditor.execCommand('mceSMExtensions');
				ed.windowManager.open({
					file : url + '/dialog.htm',
					width : 300 + parseInt(ed.getLang('smextensions.delta_width', 0)),
					height : 100 + parseInt(ed.getLang('smextensions.delta_height', 0)),
					inline : 1
				},
				{
					plugin_url : url
				});
			});

			ed.addButton('smextensions', { // Register SMExtension button
				title : 'smextensions.desc',
				cmd : 'mceSMExtensions',
				image : url + '/img/button.gif'
			});

			// Add a node change handler - selects the SMExtension button when an image is selected
			/*ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('smextensions', n.nodeName == 'IMG');
			});*/
		},
		
		getInfo : function() {
			return {
				longname : 'SMExtensions plugin',
				author : 'Sitemagic CMS',
				authorurl : 'http://sitemagic.dk',
				infourl : 'http://sitemagic.dk',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('smextensions', tinymce.plugins.SMExtensionsPlugin);
})();
