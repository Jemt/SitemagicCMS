<?php

SMExtensionManager::Import("SMPages", "SMPagesExtension.class.php", true);
class SMImageMontagePageExtension extends SMPagesExtension
{
	public function Render()
	{
		$ext = $this->argument;
		$ext = $ext . ".pageextension.php";
		$file = dirname(__FILE__) . "/" . $ext;

		if (SMStringUtilities::Validate($ext, SMValueRestriction::$SafePath) === false || SMFileSystem::FileExists($file) === false)
			throw new Exception("Page extension file '" . $ext . "' does not exist!");

		$cfg = array();
		$cfg["Title"] = "";
		$cfg["Category"] = "";
		$cfg["Width"] = "100";
		$cfg["Height"] = "100";
		$cfg["FormType"] = "Default";
		$render = true;

		ob_start();
		require($file);
		$output = ob_get_contents();
		ob_end_clean();

		if (empty($cfg["FormType"]) === false && strtolower($cfg["FormType"]) === "multipart")
			$this->context->GetForm()->SetContentType(SMFormContentType::$MultiPart);

		return $output;
    }
}

?>
