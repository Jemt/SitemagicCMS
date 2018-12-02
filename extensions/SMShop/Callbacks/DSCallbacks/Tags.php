<?php

// Functions invoked from DataSource.callback.php (in the context of Sitemagic)

function SMShopProcessNewTag(SMKeyValueCollection $tag)
{
	// Obtain new tag ID (tag was created with a temporary ID (GUID) generated client side)

	$state = new SMDataSource("SMShopState"); // IMPORTANT: Always lock on this DS when writing data (and always AFTER locking on the DS required by an operation to prevent dead locks if Xml Archiving runs at the same time)! It contains important data such as NextOrderId and NextInvoiceId! ONLY lock for a very small amount of time - it is constantly being used!
	$state->Lock();

	$items = $state->Select("*", "key = 'NextTagId'");
	$tagId = ((count($items) > 0) ? (int)$items[0]["value"] : 1);

	$nextTagId = new SMKeyValueCollection();
	$nextTagId["key"] = "NextTagId";
	$nextTagId["value"] = (string)($tagId + 1);

	if (count($items) === 0)
		$state->Insert($nextTagId);
	else
		$state->Update($nextTagId, "key = '" . $nextTagId["key"] . "'");

	$state->Commit();

	// Update Tag ID

	$tag["Id"] = (string)$tagId;
}

?>