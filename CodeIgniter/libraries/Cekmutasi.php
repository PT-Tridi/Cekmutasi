<?php

defined("BASEPATH") or exit("No direct script access allowed");

class Cekmutasi
{
	// place your API Key here
	private $apiKey = "";
	private $apiSignature = "";
	private $apiUrl = "https://api.cekmutasi.co.id/v1";
	public $CI;
	private $service = null;

	const VERSION = '1.0';
	const BANK = 1;
	const PAYPAL = 2;
	const OVO = 3;
	const GOPAY = 4;

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

	public function ovo()
	{
		$this->service = self::OVO;
		return $this;
	}

	public function gopay()
	{
		$this->service = self::GOPAY;
		return $this;
	}

	private function request($endpoint, $method = 'GET', $params = [])
    {
    	$url = $this->apiUrl . $endpoint;

    	$ch = curl_init();

    	if( strtoupper($method) == 'GET' )
    	{
    		$url .= '?'.http_build_query($params);
    	}
    	else
    	{
    		curl_setopt($ch, CURLOPT_POST, true);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    	}

        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            	'Api-Key: ' . $this->apiKey,
            	'Accept: application/json',
                'User-Agent: Cekmutasi CodeIgniter/' . self::VERSION
            ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if( $errno )
        {
            $result = json_encode([
                'success'     	=> false,
                'error_message'	=> $error
            ]);
        }

        return json_decode($result);
    }

	public function search(array $options = [])
	{
		switch($this->service)
		{
			case self::BANK:
				$endpoint = '/bank/search';
				break;

			case self::PAYPAL:
				$endpoint = '/paypal/search';
				break;

			case self::OVO:
				$endpoint = '/ovo/search';
				break;

			case self::GOPAY:
				$endpoint = '/gopay/search';
				break;

			default:
				throw new \Exception(get_class($this).": Undefined service");
				break;
		}

		return $this->request($endpoint, 'POST', $options);
	}

	public function transferBankList($sourceNumber)
	{
		if( $this->service != self::OVO ) {
			throw new \Exception(get_class($this).": " . __FUNCTION__ . "() method is only available for OVO");
		}

		return $this->request('/ovo/transfer/bank-list', 'POST', [
			'source_number'	=> $sourceNumber
		]);
	}

	public function transferBankInquiry($sourceNumber, $bankCode, $destinationNumber)
	{
		if( $this->service != self::OVO ) {
			throw new \Exception(get_class($this).": " . __FUNCTION__ . "() method is only available for OVO");
		}

		return $this->request('/ovo/transfer/inquiry', 'POST', [
			'source_number'	=> $sourceNumber,
			'bank_code'	=> $bankCode,
			'destination_number'	=> $destinationNumber
		]);
	}

	public function transferBank($uuid, $token, $amount, $note = '')
	{
		if( $this->service != self::OVO ) {
			throw new \Exception(get_class($this).": " . __FUNCTION__ . "() method is only available for OVO");
		}

		return $this->request('/ovo/transfer/send', 'POST', [
			'uuid'	=> $uuid,
			'token'	=> $token,
			'amount'	=> $amount,
			'note'	=> $note
		]);
	}

	public function transferBankDetail($uuid)
	{
		if( $this->service != self::OVO ) {
			throw new \Exception(get_class($this).": " . __FUNCTION__ . "() method is only available for OVO");
		}

		return $this->request('/ovo/transfer/detail', 'GET', [
			'uuid'	=> $uuid
		]);
	}

	public function transferOVOInquiry($sourceNumber, $destinationNumber)
	{
		if( $this->service != self::OVO ) {
			throw new \Exception(get_class($this).": " . __FUNCTION__ . "() method is only available for OVO");
		}

		return $this->request('/ovo/transfer/send', 'POST', [
			'source_number'	=> $sourceNumber,
			'phone'	=> $destinationNumber
		]);
	}

	public function transferOVO($sourceNumber, $destinationNumber, $amount)
	{
		if( $this->service != self::OVO ) {
			throw new \Exception(get_class($this).": " . __FUNCTION__ . "() method is only available for OVO");
		}

		return $this->request('/ovo/transfer/send', 'POST', [
			'source_number'	=> $sourceNumber,
			'phone'	=> $destinationNumber,
			'amount'	=> $amount
		]);
	}

	public function checkIP()
	{
		return $this->request('/myip', 'POST');
	}

	public function balance()
	{
		return $this->request('/balance', 'POST');
	}

	public function catchIPN()
    {
        $incomingSignature = $this->CI->input->server('HTTP_API_SIGNATURE') ?: '';

        if( version_compare(PHP_VERSION, '5.6.0', '>=') )
        {
            if( !hash_equals($this->apiSignature, $incomingSignature) ) {
                log_message('info', get_class($this).': Invalid Signature, ' . $this->apiSignature . ' vs ' . $incomingSignature);
                exit("Invalid signature!");
            }
        }
        else
        {
            if( $apiSignature != $incomingSignature ) {
                log_message('info', get_class($this).': Invalid Signature, ' . $this->apiSignature . ' vs ' . $incomingSignature);
                exit("Invalid signature!");
            }
        }

        $json = $request->getContent();
        $decoded = json_decode($json);

        if( json_last_error() !== JSON_ERROR_NONE ) {
            log_message('info', get_class($this).': Invalid JSON, ' . $json);
            exit("Invalid JSON!");
        }

        return $decoded;
    }
}
