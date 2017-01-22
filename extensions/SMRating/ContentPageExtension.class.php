<?php

SMExtensionManager::Import("SMPages", "SMPagesExtension.class.php", true);
require_once(dirname(__FILE__) . "/SMRating.class.php");

class SMRatingContentPageExtension extends SMPagesExtension
{
	private $lang = null;
	private $ajaxRating = false;

	public function Init()
	{
		// Handle rating invoked through AJAX

		$pageId = SMEnvironment::GetPostValue("SMRatingPageId", SMValueRestriction::$Guid);
		$instanceId = SMEnvironment::GetPostValue("SMRatingInstanceId", SMValueRestriction::$Numeric);
		$maxValue = SMEnvironment::GetPostValue("SMRatingMaxValue", SMValueRestriction::$Numeric);
		$rating = SMEnvironment::GetPostValue("SMRatingRating", SMValueRestriction::$Numeric);

		if ($pageId !== null && $instanceId !== null && $maxValue !== null && $rating !== null && SMEnvironment::GetSessionValue("SMRating" . $pageId . $instanceId) === null)
		{
			$this->ajaxRating = true;

			$this->context->GetTemplate()->SetContent("");	// Clear template to avoid transfering data back to client (AJAX request)
			$this->context->GetTemplate()->Close();			// Avoid further data being added and transfered back to client (AJAX request)

			// Get rating item

			$ratingItem = SMRatingItem::GetPersistent($pageId, (int)$instanceId);

			if ($ratingItem === null)
				$ratingItem = new SMRatingItem($pageId, (int)$instanceId, (int)$maxValue);

			// Assign rating and commit

			$ratingItem->Rate((int)$rating);
			$ratingItem->CommitPersistent();

			// Make sure rating can only be applied once (this is no secure solution as cookies can always be removed client side)
			SMEnvironment::SetCookie("SMRating" . $pageId . $instanceId, "true", 365 * 24 * 60 * 60);

			// Make sure rating is only performed once if multiple instance of the content page extension is registered on the same page
			SMEnvironment::SetSession("SMRating" . $pageId . $instanceId, "true");
		}
	}

	public function Render()
	{
		if ($this->ajaxRating === true)
			return "";

		// Get rating item

		$ratingItem = SMRatingItem::GetPersistent($this->pageId, $this->instanceId);

		if ($ratingItem === null)
			$ratingItem = new SMRatingItem($this->pageId, $this->instanceId, (int)$this->argument);

		$output = "";

		// Construct rating stars

		// Construct view mode if rating has been given (no more ratings allowed)
		if (SMEnvironment::GetCookieValue("SMRating" . $this->pageId . $this->instanceId) !== null)
		{
			$ratingValue = round($ratingItem->GetRatingValue());

			for ($i = 1 ; $i <= $ratingItem->GetMaxValue() ; $i++)
			{
				if ($ratingValue >= $i)
					$output .= "<img src=\"" . SMExtensionManager::GetExtensionPath("SMRating") . "/images/ratingselection.png" . "\" alt=\"\" title=\"" . $this->getTranslation("NumberOfRatings") . ": " . $ratingItem->GetRatingCount() . " - " . $this->getTranslation("Average") . ": " . round($ratingItem->GetRatingValue(), 2) . "\">";
				else
					$output .= "<img src=\"" . SMExtensionManager::GetExtensionPath("SMRating") . "/images/ratingunselected.png" . "\" alt=\"\" title=\"" . $this->getTranslation("NumberOfRatings") . ": " . $ratingItem->GetRatingCount() . " - " . $this->getTranslation("Average") . ": " . round($ratingItem->GetRatingValue(), 2) . "\">";
			}
		}
		else // Construct rating mode (no rating given yet)
		{
			// Create X unselected stars (selection is made using JavaScript during onload)

			for ($i = 1 ; $i <= $ratingItem->GetMaxValue() ; $i++)
				$output .= "<img id=\"SMRatingImage" . $this->instanceId . "_" . $i . "\" src=\"" . SMExtensionManager::GetExtensionPath("SMRating") . "/images/ratingunselected.png" . "\" alt=\"\" title=\"" . $this->getTranslation("NumberOfRatings") . ": " . $ratingItem->GetRatingCount() . " - " . $this->getTranslation("Average") . ": " . round($ratingItem->GetRatingValue(), 2) . "\" onmouseover=\"smRatingHighlight" . $this->instanceId . "(" . $i . ")\" onmouseout=\"smRatingRemoveHighlight" . $this->instanceId . "()\" onclick=\"smRatingRate" . $this->instanceId . "(" . $i . ")\">";

			// Construct client side logic

			$output .= "
			<script type=\"text/javascript\">
			var smRatingRequest" . $this->instanceId . " = null;
			var smRatingLastStarHighlighted" . $this->instanceId . " = -1;
			var smRatingRated" . $this->instanceId . " = false;

			function smRatingRate" . $this->instanceId . "(rating)
			{
				// Abort if rating has already been given
				if (smRatingRated" . $this->instanceId . " === true)
					return;

				smRatingRequest" . $this->instanceId . " = new SMHttpRequest(\"" . SMEnvironment::GetCurrentUrl() . "\", true);
				smRatingRequest" . $this->instanceId . ".SetData(\"SMRatingPageId=" . $this->pageId . "&SMRatingInstanceId=" . $this->instanceId . "&SMRatingMaxValue=" . $this->argument . "&SMRatingRating=\" + rating);
				smRatingRequest" . $this->instanceId . ".Start();

				// Setting variable True disables mouse over highlighting on stars and the ability to perform more ratings
				smRatingRated" . $this->instanceId . " = true;
			}

			function smRatingInitializeStars" . $this->instanceId . "()
			{
				// Select number of stars representing average rating

				var ratingValue = " . round($ratingItem->GetRatingValue()) . ";
				var img = \"\";

				for (var i = 1 ; i <= " . $ratingItem->GetMaxValue() . " ; i++)
				{
					if (ratingValue >= i)
						img = \"" . SMExtensionManager::GetExtensionPath("SMRating") . "/images/rating.png\";
					else
						img = \"" . SMExtensionManager::GetExtensionPath("SMRating") . "/images/ratingunselected.png\";

					document.getElementById(\"SMRatingImage" . $this->instanceId . "_\" + i).src = img;
				}
			}

			function smRatingHighlight" . $this->instanceId . "(rating)
			{
				// Make sure highligting does not occure when user has rated an item
				if (smRatingRated" . $this->instanceId . " === true)
					return;

				// OnMouseOver keeps getting fired - this check makes sure images are only updated once, when mouse is held over a star
				if (rating === smRatingLastStarHighlighted" . $this->instanceId . ")
					return;

				// Update images - hightlight image on which mouse is held over, and all stars before that
				for (var i = 1 ; i <= rating ; i++)
					document.getElementById(\"SMRatingImage" . $this->instanceId . "_\" + i).src = \"" . SMExtensionManager::GetExtensionPath("SMRating") . "/images/ratingselection.png\"" . ";

				// Remember last star highlighted
				smRatingLastStarHighlighted" . $this->instanceId . " = rating;
			}

			function smRatingRemoveHighlight" . $this->instanceId . "()
			{
				// Make sure selection is kept intact if user moves mouse over and away from a star
				if (smRatingRated" . $this->instanceId . " === true)
					return;

				// Remove highlighting by re-initializing stars
				smRatingInitializeStars" . $this->instanceId . "();

				// Make sure any star can be highlighted again
				smRatingLastStarHighlighted" . $this->instanceId . " = -1;
			}

			SMEventHandler.AddEventHandler(window, \"load\", smRatingInitializeStars" . $this->instanceId . ");
			</script>
			";
		}

		return $output;
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMRating");

		return $this->lang->GetTranslation($key);
	}
}

?>
