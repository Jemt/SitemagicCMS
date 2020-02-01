<?php

class Cash implements PSPI
{
	protected function GetName()
	{
		return "Cash";
	}

	public function RedirectToPaymentForm($orderId, $amount, $currency, $continueUrl = null, $callbackUrl = null)
	{
		// Mark payment as authorized to allow shop owner to capture it to mark it as withdrawn
		PSP::InvokeCallback($callbackUrl, $orderId, $orderId, $amount, $currency);

		// Redirect to receipt page
		header("location: " . $continueUrl);
		exit;
	}

	public function CapturePayment($transactionId, $amount)
	{
		return true;
	}

	public function CancelPayment($transactionId)
	{
		return true;
	}
}

?>
