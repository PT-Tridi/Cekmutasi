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
	
    public function bank($configs = [])
    {
        return (new Bank($configs));
    }

    public function paypal($configs = [])
    {
        return (new PayPal($configs));
    }

    public function gopay($configs = [])
    {
    	return (new GoPay($configs));
    }

    public function ovo($configs = [])
    {
    	return (new OVO($configs));
    }

    public function checkIP()
    {
    	return $this->request('/myip', Constant::HTTP_POST);
    }

    public function balance()
    {
    	return $this->request('/balance', Constant::HTTP_POST);
    }

    public function catchIPN(\Illuminate\Http\Request $request)
    {
        $apiSignature = env('CEKMUTASI_API_SIGNATURE', '');
        $incomingSignature = $request->server('HTTP_API_SIGNATURE', '');

        if( version_compare(PHP_VERSION, '5.6.0', '>=') )
        {
            if( !hash_equals($apiSignature, $incomingSignature) ) {
                \Log::info(get_class($this).': Invalid Signature, ' . $apiSignature . ' vs ' . $incomingSignature);
                exit("Invalid signature!");
            }
        }
        else
        {
            if( $apiSignature != $incomingSignature ) {
                \Log::info(get_class($this).': Invalid Signature, ' . $apiSignature . ' vs ' . $incomingSignature);
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
