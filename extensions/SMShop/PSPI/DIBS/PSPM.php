<?php

// PSPM - Payment Service Provider Module.
// Must define a class with the same name as the folder in which
// this file is located, and implement the PSPI interface.
// Do not include PSPInterface.php - PSPM is instantiated
// from within the PSP system, so all necessary resources
// will be loaded at run time.

class DIBS implements PSPI
{
	protected function GetName()
	{
		return "DIBS";
	}

	public function RedirectToPaymentForm($orderId, $amount, $currency, $continueUrl = null, $callbackUrl = null)
	{
		$cfg = PSP::GetConfig($this->GetName());

		// Currency - make sure numeric value is used to work around checksum bug

		if (is_numeric($currency) === false)
			$currency = PSP::CurrencyCodeToNumericValue($currency);

		// Create checksum (if keys are configured)

		$checksum = "";

		if (isset($cfg["Encryption Key 1"]) && $cfg["Encryption Key 1"] !== "" && isset($cfg["Encryption Key 2"]) && $cfg["Encryption Key 2"] !== "")
		{
			$check = "";
			$check .= "merchant=" . $cfg["Merchant ID"];
			$check .= "&orderid=" . $orderId;
			$check .= "&currency=" . $currency;
			$check .= "&amount=" . $amount;

			$checksum = md5($cfg["Encryption Key 2"] . md5($cfg["Encryption Key 1"] . $check));
		}

		// Output

		echo '
		<form id="DIBS" method="POST" action="https://payment.architrade.com/paymentweb/start.action">
			<input type="hidden" name="decorator" value="responsive">
			<input type="hidden" name="merchant" value="' . $cfg["Merchant ID"] . '">
			<input type="hidden" name="callbackurl" value="' . PSP::GetProviderUrl($this->GetName()) . "/Callback.php" . '">
			<input type="hidden" name="accepturl" value="' . PSP::GetProviderUrl($this->GetName()) . "/Callback.php" . '">
			<input type="hidden" name="orderid" value="' . $orderId . '">
			<input type="hidden" name="amount" value="' . $amount . '">
			<input type="hidden" name="currency" value="' . $currency . '">
			<input type="hidden" name="md5key" value="' . $checksum . '">
			' . ((PSP::GetTestMode() === true) ? '<input type="hidden" name="test" value="true">' : '') . '
			<input type="hidden" name="CUSTOM_Callback" value="' . (($callbackUrl !== null) ? $callbackUrl : "") . '">
			<input type="hidden" name="CUSTOM_ContinueUrl" value="' . (($continueUrl !== null) ? $continueUrl : "") . '">
		</form>

		<script type="text/javascript">
			setTimeout(function() { document.getElementById("DIBS").submit(); }, 100);
		</script>
		';

		exit;
	}

	public function CapturePayment($transactionId, $amount)
	{
		return $this->apiCall("Capture", $transactionId, $amount);
	}

	public function CancelPayment($transactionId)
	{
		return $this->apiCall("Cancel", $transactionId);
	}

	private function apiCall($type, $transactionId, $amount = -1)
	{
		$transactionInfo = explode(";", $transactionId);
		$transactionId = $transactionInfo[0];
		$orderId = $transactionInfo[1];

		$cfg = PSP::GetConfig($this->GetName());

		$checksum = "";
		if (isset($cfg["Encryption Key 1"]) && $cfg["Encryption Key 1"] !== "" && isset($cfg["Encryption Key 2"]) && $cfg["Encryption Key 2"] !== "")
		{
			$check = "";
			$check .= "merchant=" . $cfg["Merchant ID"];
			$check .= "&orderid=" . $orderId;
			$check .= "&transact=" . $transactionId;

			if ($type === "Capture")
				$check .= "&amount=" . $amount;

			$checksum = md5($cfg["Encryption Key 2"] . md5($cfg["Encryption Key 1"] . $check));
		}

		$data = array
		(
			"merchant"		=> $cfg["Merchant ID"],
			"transact"		=> $transactionId,
			"orderid"		=> $orderId,
			"md5key"		=> $checksum
		);

		if ($type === "Capture")
			$data["amount"] = (string)$amount;

		$url = null;
		if ($type === "Capture")
			$url = "https://payment.architrade.com/cgi-bin/capture.cgi";
		else if ($type === "Cancel")
			$url = "https://" . $cfg["API User: Username"] . ":" . $cfg["API User: Password"] . "@payment.architrade.com/cgi-adm/cancel.cgi";

		$response = PSP::Post($url, $data);
		$result = (strpos($response, "status=ACCEPTED") !== false);

		PSP::Log($this->GetName() . " - API call result: " . "\nType: " . $type . "\nSuccess: " . ($result === true ? "true" : "false") . "\nResponse: " . $response);

		return $result;
	}
}

?>
