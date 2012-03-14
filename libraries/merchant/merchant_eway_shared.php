<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * CI-Merchant Library
 *
 * Copyright (c) 2011-2012 Crescendo Multimedia Ltd
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Merchant eWAY External Class
 *
 * Payment processing using eWAY's Secure Hosted Page
 * Documentation: http://www.eway.com.au/_files/documentation/HostedPaymentPageDoc.pdf
 */

class Merchant_eway_shared extends Merchant_driver
{
	const PROCESS_URL = 'https://au.ewaygateway.com/Request/';
	const PROCESS_RETURN_URL = 'https://au.ewaygateway.com/Result/';

	public function default_settings()
	{
		return array(
			'customer_id' => '',
			'username' => '',
			'company_name' => '',
			'company_logo' => '',
			'page_title' => '',
			'page_banner' => '',
			'page_description' => '',
			'page_footer' => '',
		);
	}

	public function purchase()
	{
		$this->require_params('return_url', 'cancel_url');

		$data = array(
			'CustomerID' => $this->setting('customer_id'),
			'UserName' => $this->setting('username'),
			'Amount' => sprintf('%01.2f', $this->param('amount')),
			'Currency' => $this->param('currency'),
			'PageTitle' => $this->setting('page_title'),
			'PageDescription' => $this->setting('page_description'),
			'PageFooter' => $this->setting('page_footer'),
			'PageBanner' => $this->setting('page_banner'),
			'Language' => 'EN',
			'CompanyName' => $this->setting('company_name'),
			'CompanyLogo' => $this->setting('company_logo'),
			'CancelUrl' => $this->param('cancel_url'),
			'ReturnUrl' => $this->param('return_url'),
			'MerchantReference' => $this->param('description'),
		);

		$response = $this->get_request(self::PROCESS_URL.'?'.http_build_query($data));
		$xml = simplexml_load_string($response);

		if ((string)$xml->Result == 'True')
		{
			$this->redirect((string)$xml->URI);
		}

		return new Merchant_response(Merchant_response::FAILED, (string)$xml->Error);
	}

	public function purchase_return()
	{
		$payment_code = $this->CI->input->get_post('AccessPaymentCode');
		if (empty($payment_code))
		{
			return new Merchant_response(Merchant_response::FAILED, 'invalid_response');
		}

		$data = array(
			'CustomerID' => $this->setting('customer_id'),
			'UserName' => $this->setting('username'),
			'AccessPaymentCode' => $_REQUEST['AccessPaymentCode'],
		);

		$response = $this->get_request(self::PROCESS_RETURN_URL.'?'.http_build_query($data));
		$xml = simplexml_load_string($response);

		if ((string)$xml->TrxnStatus == 'True')
		{
			return new Merchant_response(Merchant_response::COMPLETED, NULL, (string)$xml->TrxnNumber);
		}

		return new Merchant_response(Merchant_response::FAILED,
			(string)$xml->TrxnResponseMessage,
			(string)$xml->TrxnNumber);
	}
}

/* End of file ./libraries/merchant/drivers/merchant_eway_shared.php */