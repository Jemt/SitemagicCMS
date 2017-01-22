<?php

SMExtensionManager::Import("SMPages", "SMPagesExtension.class.php", true);
require_once(dirname(__FILE__) . "/SMContact.classes.php");
require_once(dirname(__FILE__) . "/FrmContactForm.class.php");

class SMContactContentPageExtension extends SMPagesExtension
{
	public function Render()
	{
		$this->SetIsIntegrated(true);

		$frm = new SMContactFrmContactForm($this->context, $this->instanceId, $this->argument);
		return $frm->Render();
	}
}

?>
