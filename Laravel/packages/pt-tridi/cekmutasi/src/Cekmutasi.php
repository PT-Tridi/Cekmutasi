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
}
