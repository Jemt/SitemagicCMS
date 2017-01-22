<?php

SMExtensionManager::Import("SMPages", "SMPagesExtension.class.php", true);
require_once(dirname(__FILE__) . "/FrmSearch.class.php");

class SMSearchContentPageExtension extends SMPagesExtension
{
	public function Render()
	{
		$this->SetIsIntegrated(true);

		$frm = new SMSearchFrmSearch($this->context, $this->instanceId, $this->argument);
		return $frm->Render();
	}
}

?>
