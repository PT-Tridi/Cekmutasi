<?php

namespace PTTridi\Cekmutasi;

use PTTridi\Cekmutasi\Services\Bank;
use PTTridi\Cekmutasi\Services\PayPal;
use PTTridi\Cekmutasi\Services\OVO;
use PTTridi\Cekmutasi\Services\GoPay;
use PTTridi\Cekmutasi\Support\Constant;

class Cekmutasi extends BaseClass
{
	use Contstant;

    public function __construct()
    {
        parent::__construct();
    }
	
	/**
	*	Load bank service
	*
	*	@param Array $configs
	*
	*	@return PTTridi\Cekmutasi\Services\Bank
	*
	**/

    public function bank($configs = [])
    {
        return (new Bank($configs));
    }

    /**
	*	Load PayPal service
	*
	*	@param Array $configs
	*
	*	@return PTTridi\Cekmutasi\Services\PayPal
	*
	**/

    public function paypal($configs = [])
    {
        return (new PayPal($configs));
    }

    /**
	*	Load OVO service
	*
	*	@param Array $configs
	*
	*	@return PTTridi\Cekmutasi\Services\OVO
	*
	**/

    public function gopay($configs = [])
    {
    	return (new GoPay($configs));
    }

    /**
	*	Load GoPay service
	*
	*	@param Array $configs
	*
	*	@return PTTridi\Cekmutasi\Services\GoPay
	*
	**/

    public function ovo($configs = [])
    {
    	return (new OVO($configs));
    }

    /**
	*	Check your IP
	*
	*	@return Object PTTridi\Cekmutasi\BaseClass::request()
	*
	**/

    public function checkIP()
    {
    	return $this->request('/myip', Constant::HTTP_POST);
    }

    /**
	*	Check your cekmutasi balance
	*
	*	@return Object PTTridi\Cekmutasi\BaseClass::request()
	*
	**/

    public function balance()
    {
    	return $this->request('/balance', Constant::HTTP_POST);
    }

    /**
	*	Handle incoming IPN/Callback data
	*
	*	@param Illuminate\Http\Request $request
	*
	*	@return Object
	*
	**/

    public function catchIPN(\Illuminate\Http\Request $request)
    {
        $incomingSignature = $request->server('HTTP_API_SIGNATURE', '');

        if( version_compare(PHP_VERSION, '5.6.0', '>=') )
        {
            if( !hash_equals($this->apiSignature, $incomingSignature) ) {
                \Log::info(get_class($this).': Invalid Signature, ' . $this->apiSignature . ' vs ' . $incomingSignature);
                exit("Invalid signature!");
            }
        }
        else
        {
            if( $apiSignature != $incomingSignature ) {
                \Log::info(get_class($this).': Invalid Signature, ' . $this->apiSignature . ' vs ' . $incomingSignature);
                exit("Invalid signature!");
            }
        }

        $json = $request->getContent();
        $decoded = json_decode($json);

        if( json_last_error() !== JSON_ERROR_NONE ) {
            \Log::info(get_class($this).': Invalid JSON, ' . $json);
            exit("Invalid JSON!");
        }

        return $decoded;
    }
}
