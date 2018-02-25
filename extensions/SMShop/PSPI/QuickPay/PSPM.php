<?php

// PSPM - Payment Service Provider Module.
// Must define a class with the same name as the folder in which
// this file is located, and implement the PSPI interface.
// Do not include PSPInterface.php - PSPM is instantiated
// from within the PSP system, so all necessary resources
// will be loaded at run time.

class QuickPay implements PSPI
{
	protected function GetName()
	{
		return "QuickPay";
	}

	public function RedirectToPaymentForm($orderId, $amount, $currency, $continueUrl = null, $callbackUrl = null)
	{
		$cfg = PSP::GetConfig($this->GetName());

		// Prepare data

		$params = array(
			"version"      => "v10",
			"merchant_id"  => ((isset($cfg["Merchant ID"]) === true) ? $cfg["Merchant ID"] : ""),
			"agreement_id" => ((isset($cfg["Agreement ID"]) === true) ? $cfg["Agreement ID"] : ""),
			"order_id"     => $orderId,
			"amount"       => $amount,
			"currency"     => $currency,
			"continueurl"  => (($continueUrl !== null) ? $continueUrl : ""),
			"cancelurl"    => ((isset($cfg["Cancel URL"]) === true) ? $cfg["Cancel URL"] : ""), /* TODO: Make this part of JSShop and PSPI rather than custom config (?) */
			"callbackurl"  => PSP::GetProviderUrl($this->GetName()) . "/Callback.php",
			"variables"	   => array(
				"CUSTOM_Callback" => (($callbackUrl !== null) ? $callbackUrl : "")
			)
		);

		// Create checksum (if keys are configured)

		$checksum = "";

		if (isset($cfg["Payment Window API key"]) === true && $cfg["Payment Window API key"] !== "")
		{
			$checksum = $this->sign($params, $cfg["Payment Window API key"]);
		}

		// Output

		// TIP: Append "/framed" to the action URL if you want to show the payment window in an iframe:
		// <form method="POST" action="https://payment.quickpay.net/framed">

		echo '<form id="PaymentForm" method="POST" action="https://payment.quickpay.net">';
		foreach ($params as $key => $value)
		{
			if ($key !== "variables")
			{
				echo '<input type="hidden" name="' . $key . '" value="' . $value . '">';
			}
			else
			{
				foreach ($value as $varKey => $varVal)
				{
					echo '<input type="hidden" name="variables[' . $varKey . ']" value="' . $varVal . '">';
				}
			}
		}
		echo '<input type="hidden" name="checksum" value="' . $checksum . '">';
		//echo '<input type="submit" value="Continue to payment...">';
		echo '</form>';
		echo '
		<script type="text/javascript">
			setTimeout(function() { document.getElementById("PaymentForm").submit(); }, 100);
		</script>
		';

		exit;
	}

	// QuickPay functions - start

	// https://learn.quickpay.net/tech-talk/payments/form/#checksum
	// Code has been reformated, turned into class functions, and anonymous function passed to array_map(..)
	// has been changed to a string reference (QuickPayPSPMArrayMapCallBack) to support older versions of PHP.

	private function sign($params, $api_key)
	{
		$flattened_params = $this->flatten_params($params);
		ksort($flattened_params);
		$base = implode(" ", $flattened_params);

		return hash_hmac("sha256", $base, $api_key);
	}

	private function flatten_params($obj, $result = array(), $path = array())
	{
		if (is_array($obj))
		{
			foreach ($obj as $k => $v)
			{
				$result = array_merge($result, $this->flatten_params($v, $result, array_merge($path, array($k))));
			}
		}
		else
		{
			$result[implode("", array_map("QuickPayPSPMArrayMapCallBack", $path))] = $obj;
		}

		return $result;
	}

	// QuickPay functions - end

	public function CapturePayment($transactionId, $amount)
	{
		return $this->apiCall("capture", $transactionId, $amount);
	}

	public function CancelPayment($transactionId)
	{
		return $this->apiCall("cancel", $transactionId);
	}

	private function apiCall($type, $transactionId, $amount = -1)
	{
		// https://learn.quickpay.net/tech-talk/api/services/

		$cfg = PSP::GetConfig($this->GetName());

		$data = array();

		if ($type === "capture")
			$data["amount"] = (string)$amount;

		try
		{
			$response = PSP::Post("https://api.quickpay.net/payments/" . $transactionId . "/" . $type . "?synchronized", $data, array(
				"Accept-Version" => "v10",
				"Authorization" => "Basic " . base64_encode(":" . ((isset($cfg["API user key"]) === true) ? $cfg["API user key"] : "")),
			));

			PSP::Log($this->GetName() . " - API call result: " . "\nType: " . $type . "\nResponse: " . $response);

			$data = json_decode($response, true);
			$operation = $data["operations"][count($data["operations"]) - 1]; // Full history - last entry is the most recent operation that just happend

			if ($operation["qp_status_code"] === "20000") // Approved (https://learn.quickpay.net/tech-talk/appendixes/errors/)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		catch (Exception $ex)
		{
			PSP::Log($this->GetName() . " - API call failed: " . "\nType: " . $type . "\nException: " . $ex->getMessage());

			return false;
		}
	}
}

function QuickPayPSPMArrayMapCallBack($p)
{
	return "[{$p}]";
}

?>
