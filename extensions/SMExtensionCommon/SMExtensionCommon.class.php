<?php

interface SMIExtensionForm
{
	public function Render();
}

class SMExtensionCommonUtilities
{
	private static $cssEnsured = false;
	public static function EnsureStyles()
	{
		if (self::$cssEnsured === true)
			return;

		$path = SMExtensionManager::GetExtensionPath("SMExtensionCommon") . "/common.css?ver=" . SMEnvironment::GetVersion();
		$tpl = SMEnvironment::GetMasterTemplate();
		$tpl->RegisterResource(SMTemplateResource::$StyleSheet, $path, true);

		self::$cssEnsured = true;
	}
}

?>
