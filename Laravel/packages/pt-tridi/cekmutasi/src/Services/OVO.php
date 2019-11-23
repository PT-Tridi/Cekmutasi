<?php

namespace PTTridi\Cekmutasi\Services;

use PTTridi\Cekmutasi\BaseClass;
use PTTridi\Cekmutasi\Support\Constant;

class OVO extends BaseClass
{
	private $config = [];

	public function __construct($configs = [])
	{
		parent::__construct();

		$this->config = $configs;
	}

	public function search($options = [])
	{
		return $this->request('/ovo/search', Constant::HTTP_POST, [
			'search'	=> $options
		]);
	}

	public function transferBankList($sourceNumber)
	{
		return $this->request('/ovo/transfer/bank-list', Constant::HTTP_POST, [
			'source_number'	=> $sourceNumber
		]);
	}

	public function transferBankInquiry($sourceNumber, $bankCode, $destinationNumber)
	{
		return $this->request('/ovo/transfer/inquiry', Constant::HTTP_POST, [
			'source_number'	=> $sourceNumber,
			'bank_code'	=> $bankCode,
			'destination_number'	=> $destinationNumber
		]);
	}

	public function transferBank($uuid, $token, $amount, $note = '')
	{
		return $this->request('/ovo/transfer/send', Constant::HTTP_POST, [
			'uuid'	=> $uuid,
			'token'	=> $token,
			'amount'	=> $amount,
			'note'	=> $note
		]);
	}

	public function transferBankDetail($uuid)
	{
		return $this->request('/ovo/transfer/detail', Constant::HTTP_GET, [
			'uuid'	=> $uuid
		]);
	}

	public function transferOVOInquiry($sourceNumber, $destinationNumber)
	{
		return $this->request('/ovo/transfer/send', Constant::HTTP_POST, [
			'source_number'	=> $sourceNumber,
			'phone'	=> $destinationNumber
		]);
	}

	public function transferOVO($sourceNumber, $destinationNumber, $amount)
	{
		return $this->request('/ovo/transfer/send', Constant::HTTP_POST, [
			'source_number'	=> $sourceNumber,
			'phone'	=> $destinationNumber,
			'amount'	=> $amount
		]);
	}
}