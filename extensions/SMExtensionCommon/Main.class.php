<?php

require_once(dirname(__FILE__) . "/SMExtensionCommon.class.php");

class SMExtensionCommon extends SMExtension
{
	public function InitComplete()
	{
		// Minor overhead since common.css is always loaded, even when not needed - but it's a small file and it gets cached.
		// NOTICE: Styles will NOT be loaded when extensions run in Dedicated Execution Mode! In this case the styles
		// must be ensured manually with the function call below!
		SMExtensionCommonUtilities::EnsureStyles();
	}
}

?>
