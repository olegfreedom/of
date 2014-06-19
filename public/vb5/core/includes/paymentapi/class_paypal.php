<?php if(!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
|| # ---------------------------------------------------------------- # ||
|| # Copyright �2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

/**
* Class that provides payment verification and form generation functions
*
* @package	vBulletin
* @version	$Revision: 75544 $
* @date		$Date: 2013-06-05 12:10:43 -0700 (Wed, 05 Jun 2013) $
*/
class vB_PaidSubscriptionMethod_paypal extends vB_PaidSubscriptionMethod
{
	/**
	* The variable indicating if this payment provider supports recurring transactions
	*
	* @var	bool
	*/
	var $supports_recurring = true;

	/**
	* Perform verification of the payment, this is called from the payment gateway
	*
	* @return	bool	Whether the payment is valid
	*/
	function verify_payment()
	{
		// Leave all of these values as vB_Cleaner::TYPE_STR since we have to send them back to paypal exactly how we received them!
		$this->registry->input->clean_array_gpc('p', array(
			'item_number'    => vB_Cleaner::TYPE_STR,
			'business'       => vB_Cleaner::TYPE_STR,
			'receiver_email' => vB_Cleaner::TYPE_STR,
			'tax'            => vB_Cleaner::TYPE_STR,
			'txn_type'       => vB_Cleaner::TYPE_STR,
			'payment_status' => vB_Cleaner::TYPE_STR,
			'mc_currency'    => vB_Cleaner::TYPE_STR,
			'mc_gross'       => vB_Cleaner::TYPE_STR,
			'txn_id'         => vB_Cleaner::TYPE_STR
		));

		$this->transaction_id = $this->registry->GPC['txn_id'];

		$mc_gross = doubleval($this->registry->GPC['mc_gross']);
		$tax = doubleval($this->registry->GPC['tax']);

		$query[] = 'cmd=_notify-validate';
		foreach($_POST AS $key => $val)
		{
			$query[] = $key . '=' . urlencode ($val);
		}
		$query = implode('&', $query);

		$used_curl = false;

		if (function_exists('curl_init') AND $ch = curl_init())
		{
			curl_setopt($ch, CURLOPT_URL, 'https://www.paypal.com/cgi-bin/webscr');
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'vBulletin via cURL/PHP');

			$result = curl_exec($ch);
			curl_close($ch);
			if ($result !== false)
			{
				$used_curl = true;
			}
		}
		if (!$used_curl)
		{
			$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Host: www.paypal.com\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($query) . "\r\n\r\n";
			if ($fp = fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 15))
			{
				socket_set_timeout($fp, 15);
				fwrite($fp, $header . $query);
				while (!feof($fp))
				{
					$result = fgets($fp, 1024);
					if (strcmp($result, 'VERIFIED') == 0)
					{
						break;
					}
				}
				fclose($fp);
			}
		}

		if (!empty($this->settings['ppemail']) AND $result == 'VERIFIED' AND (strtolower($this->registry->GPC['business']) == strtolower($this->settings['ppemail']) OR strtolower($this->registry->GPC['receiver_email']) == strtolower($this->settings['primaryemail'])))
		{
			$this->paymentinfo = vB::getDbAssertor()->getRow('vBForum:getPaymentinfo', array('hash' => $this->registry->GPC['item_number']));

			// lets check the values
			if (!empty($this->paymentinfo))
			{
				$this->paymentinfo['currency'] = strtolower($this->registry->GPC['mc_currency']);
				$this->paymentinfo['amount'] = floatval($this->registry->GPC['mc_gross']);
				//its a paypal payment and we have some valid ids
				$sub = vB::getDbAssertor()->getRow('vBForum:subscription', array('subscriptionid' => $this->paymentinfo['subscriptionid']));
				$cost = unserialize($sub['cost']);
				if ($tax > 0)
				{
					$mc_gross -= $tax;
				}

				// Check if its a payment or if its a reversal
				if (($this->registry->GPC['txn_type'] == 'web_accept' OR $this->registry->GPC['txn_type'] == 'subscr_payment') AND $this->registry->GPC['payment_status'] == 'Completed')
				{
					if ($mc_gross == doubleval($cost["{$this->paymentinfo[subscriptionsubid]}"]['cost'][strtolower($this->registry->GPC['mc_currency'])]))
					{
						$this->type = 1;
					}
					else
					{
						$this->error_code = 'invalid_payment_amount';
					}
				}
				else if ($this->registry->GPC['payment_status'] == 'Reversed' OR $this->registry->GPC['payment_status'] == 'Refunded')
				{
					$this->type = 2;
				}
				else
				{
					$this->error_code = 'unhandled_payment_status_or_type';
				}
			}
			else
			{
				$this->error_code = 'invalid_subscriptionid';
			}

			$status_code = '200 OK';

			// Paypal likes to get told its message has been received
			if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
			{
				header('Status: ' . $status_code);
			}
			else
			{
				header('HTTP/1.1 ' . $status_code);
			}
			return ($this->type > 0);
		}
		else
		{
			$this->error_code = 'authentication_failure';
			$this->error = 'Invalid Request';
		}

		$status_code = '503 Service Unavailable';
		// Paypal likes to get told its message has been received
		if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
		{
			header('Status: ' . $status_code);
		}
		else
		{
			header('HTTP/1.1 ' . $status_code);
		}
		return false;
	}

	/**
	* Test that required settings are available, and if we can communicate with the server (if required)
	*
	* @return	bool	If the vBulletin has all the information required to accept payments
	*/
	function test()
	{
		$communication = false;
		$query = 'cmd=_notify-validate';

		if (function_exists('curl_init') AND $ch = curl_init())
		{
			curl_setopt($ch, CURLOPT_URL, 'http://www.paypal.com/cgi-bin/webscr');
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'vBulletin via cURL/PHP');

			$result = curl_exec($ch);
			curl_close($ch);
			if ($result !== false)
			{
				$communication = true;
			}
		}
		if (!$communication)
		{
			$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Host: www.paypal.com\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($query) . "\r\n\r\n";
			if ($fp = fsockopen('www.paypal.com', 80, $errno, $errstr, 15))
			{
				socket_set_timeout($fp, 15);
				fwrite($fp, $header . $query);
				while (!feof($fp))
				{
					$result = fgets($fp, 1024);
					if (strcmp($result, 'INVALID') == 0)
					{
						$communication = true;
						break;
					}
				}
				fclose($fp);
			}
		}
		return (!empty($this->settings['ppemail']) AND $communication);
	}

	/**
	* Generates HTML for the subscription form page
	*
	* @param	string		Hash used to indicate the transaction within vBulletin
	* @param	string		The cost of this payment
	* @param	string		The currency of this payment
	* @param	array		Information regarding the subscription that is being purchased
	* @param	array		Information about the user who is purchasing this subscription
	* @param	array		Array containing specific data about the cost and time for the specific subscription period
	*
	* @return	array		Compiled form information
	*/
	function generate_form_html($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo)
	{
		$item = $hash;
		$currency = strtoupper($currency);

		$show['notax'] = ($subinfo['newoptions']['api']['paypal']['tax']) ? false : true;
		$show['recurring'] = ($this->supports_recurring AND $timeinfo['recurring']) ? true : false;
		$no_shipping = '1';
		switch ($subinfo['newoptions']['api']['paypal']['shipping_address'])
		{
			case 'none':
				$no_shipping = '1';
				break;
			case 'optional':
				$no_shipping = '0';
				break;
			case 'required':
				$no_shipping = '2';
				break;
		}

		$form['action'] = 'https://www.paypal.com/cgi-bin/webscr';
		$form['method'] = 'post';

		$vbphrase = vB_Api::instanceInternal('phrase')->fetch('sub' . $subinfo['subscriptionid'] . '_title');
		$subinfo['title'] = $vbphrase['sub' . $subinfo['subscriptionid'] . '_title'];

		// load settings into array so the template system can access them
		$settings =& $this->settings;

		$templater = new vB5_Template('subscription_payment_paypal');
			$templater->register('cost', $cost);
			$templater->register('currency', $currency);
			$templater->register('item', $item);
			$templater->register('no_shipping', $no_shipping);
			$templater->register('settings', $settings);
			$templater->register('subinfo', $subinfo);
			$templater->register('timeinfo', $timeinfo);
			$templater->register('userinfo', $userinfo);
			$templater->register('show', $show);
		$form['hiddenfields'] .= $templater->render();
		return $form;
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 75544 $
|| ####################################################################
\*======================================================================*/
?>