<?php

defined("BASEPATH") or exit("No direct script access allowed");

class Cekmutasi
{
	// place your API Key here
	private $apiKey = "";

	private $apiUrl = "https://cekmutasi.co.id/v1";

	public $CI;

	private $service;

	const BANK = 1;
	const PAYPAL = 2;

	public function __construct()
	{
		// incluce CodeIgniter class instance,
		// you can use it to load any model, libraries, and all of CodeIgniter parts
		// ex: $this->CI->load->model('ModelName');

		$this->CI =& get_instance();
	}

	public function bank()
	{
		$this->service = self::BANK;
		return $this;
	}

	public function paypal()
	{
		$this->service = self::PAYPAL;
		return $this;
	}

	private function request($endpoint, $params = [])
	{
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_FRESH_CONNECT	=> true,
			CURLOPT_URL				=> $this->apiUrl . $endpoint,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_POST			=> true,
			CURLOPT_POSTFIELDS		=> http_build_query($params),
			CURLOPT_HEADER			=> false,
			CURLOPT_HTTPHEADER		=> ['API-KEY: ' . $this->apiKey, 'Accept: application/json'],
			CURLOPT_SSL_VERIFYHOST	=> 0,
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_CONNECTTIMEOUT	=> 10,
			CURLOPT_TIMEOUT 		=> 120,
			CURLOPT_FAILONERROR		=> true
		]);

		$result = curl_exec($ch);
		$errno = curl_errno($ch);

		if( $errno )
		{
			return $this->printOut([
				'success'			=> false,
				'error_message'		=> curl_error($ch),
				'response'			=> [] 
			]);
		}

		return $this->printOut(json_decode($result, true));
	}

	private function printOut($array)
	{
		return json_encode($array);
	}

	public function mutation($searchOptions = [])
	{
		$search = [
			'search'	=> $searchOptions
		];

		if( $this->service == self::BANK ) {
			$endpoint = "/bank/search";
		}
		elseif( $this->service == self::PAYPAL ) {
			$endpoint = "/paypal/search";
		}
		else {
			return $this->printOut([
				'success'		=> false,
				'error_message'	=> 'Undefined service',
				'response'		=> []
			]);
		}

		return $this->request($endpoint, $search);
	}
}