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

	/**
	*	Search PayPal mutation
	*
	*	@param Array $options
	*
	*	@return Object PTTridi\Cekmutasi\BaseClass::request()
	*
	**/

	public function search($options = [])
	{
		return $this->request('/paypal/search', Constant::HTTP_POST, [
			'search'	=> $options
		]);
	}

	/**
	*	Get payment detail
	*
	*	@param String $username
	*
	*	@param String $transactionid
	*
	*	@return Object PTTridi\Cekmutasi\BaseClass::request()
	*
	**/

	public function detail($username, $transactionid)
	{
		return $this->request('/paypal/detail', Constant::HTTP_POST, [
			'username'	=> $username,
			'transactionid' => $transactionid
		]);
	}
}