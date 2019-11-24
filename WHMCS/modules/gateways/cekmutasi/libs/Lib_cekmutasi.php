<?php

if( !defined('WHMCS') ) {
	exit('No direct script access allowed');
}

class Lib_cekmutasi
{
	protected $api_endpoint = 'https://api.cekmutasi.co.id/v1';
	protected $api_key = null;
	protected $api_secret = null;
	public $base_config;
	public $cekmutasi_headers = array();

	function __construct($config = array())
	{
		if (isset($config['api_key'])) {
			$this->api_key = $config['api_key'];
		}

		if (isset($config['api_secret'])) {
			$this->api_secret = $config['api_secret'];
		}

		$this->base_config = $config;
		$this->cekmutasi_headers = $this->create_cekmutasi_headers();
	}
	
	private function create_cekmutasi_headers()
	{
		$cekmutasi_headers = array(
			'Api-Key'		=> (isset($this->api_key) ? $this->api_key : ''),
			'Accept'		=> 'application/json',
		);
		return $cekmutasi_headers;
	}
	
	function generate_search_params($transaction_data = null)
	{
		$query_params = array(
			'search' => array(),
		);

		if (isset($transaction_data->payment_method))
		{
			switch ($transaction_data->payment_method)
			{
				case 'all':
					$query_params['search']['service_code'] = '';
					break;

				default:
					$query_params['search']['service_code'] = $transaction_data->payment_method;
					break;
			}
		}

		if (isset($transaction_data->order_total))
		{
			$transaction_data->order_total = sprintf("%.02f", $transaction_data->order_total);
			$query_params['search']['amount'] = sprintf("%s", $transaction_data->order_total);
		}

		if (isset($transaction_data->payment_insert))
		{
			try
			{
				$payment_insert = new DateTime($transaction_data->payment_insert);
			}
			catch (Exception $ex)
			{
				throw $ex;
				$payment_insert = false;
			}

			if ($payment_insert != false)
			{
				$query_params['search']['date'] = array(
					'from'			=> $payment_insert->format('Y-m-d H:i:s'),
				);

				switch (strtolower($transaction_data->payment_cekmutasi_durasi_unit))
				{
					case 'week':
						$payment_cekmutasi_durasi_amount = ($transaction_data->payment_cekmutasi_durasi_amount * 7);
						break;

					case 'day':
					default:
						$payment_cekmutasi_durasi_amount = $transaction_data->payment_cekmutasi_durasi_amount;
						break;
				}

				$payment_insert->add(new DateInterval("P{$payment_cekmutasi_durasi_amount}D"));
				$query_params['search']['date']['to'] = $payment_insert->format('Y-m-d H:i:s');
			}

			$transaction_data->order_total = sprintf("%.02f", $transaction_data->order_total);
			$query_params['search']['amount'] = sprintf("%s", $transaction_data->order_total);
		}

		return $query_params;
	}

	//------------------------------------------------------------------
	// API Action
	function get_api_url($method)
	{
		$url = '';

		switch($method)
		{
			case 'ovo':
				$url = sprintf("%s%s", $this->api_endpoint, '/ovo/search');
				break;

			case 'gopay':
				$url = sprintf("%s%s", $this->api_endpoint, '/ovo/search');
				break;

			default:
				$url = sprintf("%s%s", $this->api_endpoint, '/bank/search');
				break;
		}

		return $url;
	}

	function get_search_api($type, $input_params)
	{
		$collect = array();
		$type = (is_string($type) ? strtolower($type) : 'get');
		switch ($type)
		{
			case 'get':
			default:
				break;
		}
	}
}