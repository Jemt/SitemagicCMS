<?php

SMExtensionManager::Import("SMPages", "SMPagesExtension.class.php", true);
require_once(dirname(__FILE__) . "/FrmViewer.class.php");

class SMGalleryContentPageExtension extends SMPagesExtension
{
	public function Render()
	{
		$frm = new SMGalleryFrmViewer($this->context, $this->instanceId, $this->argument);
		return $frm->Render();
	}
}

?>
