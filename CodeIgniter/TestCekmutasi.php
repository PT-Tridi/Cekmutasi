<?php

class TestCekmutasi extends CI_Controller
{
	public function mutasiBank()
	{
		$this->load->library('cekmutasi');

		$mutasi = $this->cekmutasi->bank()->search([
			'date'		=> [
				'from'	=> date('Y-m-d') . ' 00:00:00',
				'to'	=> date('Y-m-d') . ' 23:59:59'
			]
		]);

		print_r($mutasi);
	}

	public function mutasiPayPal()
	{
		$this->load->library('cekmutasi');

		$mutasi = $this->cekmutasi->paypal()->search([
			'date'		=> [
				'from'	=> date('Y-m-d') . ' 00:00:00',
				'to'	=> date('Y-m-d') . ' 23:59:59'
			]
		]);

		print_r($mutasi);
	}

	public function mutasiOVO()
	{
		$this->load->library('cekmutasi');

		$mutasi = $this->cekmutasi->ovo()->search([
			'date'		=> [
				'from'	=> date('Y-m-d') . ' 00:00:00',
				'to'	=> date('Y-m-d') . ' 23:59:59'
			]
		]);

		print_r($mutasi);
	}

	public function mutasiGoPay()
	{
		$this->load->library('cekmutasi');

		$mutasi = $this->cekmutasi->gopay()->search([
			'date'		=> [
				'from'	=> date('Y-m-d') . ' 00:00:00',
				'to'	=> date('Y-m-d') . ' 23:59:59'
			]
		]);

		print_r($mutasi);
	}

	public function balance()
	{
		$this->load->library('cekmutasi');

		$balance = $this->cekmutasi->balance();

		print_r($balance);
	}

	public function handleCallback()
	{
		$this->load->library('cekmutasi');

		$ipn = $this->cekmutasi->catchIPN();

		print_r($ipn);
	}
}