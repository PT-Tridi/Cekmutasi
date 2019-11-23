<?php

namespace PTTridi\Cekmutasi\Services;

use PTTridi\Cekmutasi\BaseClass;
use PTTridi\Cekmutasi\Support\Constant;

class PayPal extends BaseClass
{
	private $config = [];

	public function __construct($configs = [])
	{
		parent::__construct();

		$this->config = $configs;
	}

	public function search($options = [])
	{
		return $this->request('/paypal/search', Constant::HTTP_POST, [
			'search'	=> $options
		]);
	}

	public function detail($username, $transactionid)
	{
		return $this->request('/paypal/detail', Constant::HTTP_POST, [
			'username'	=> $username,
			'transactionid' => $transactionid
		]);
	}
}