<?php

namespace App\Http\Controllers;

use PTTridi\Cekmutasi\Cekmutasi;
use Illuminate\Http\Request;

class CekmutasiController extends Controller
{
	public function mutasiBank()
	{
		$mutasi = Cekmutasi::bank()->search([
				'date'		=> [
					'from'	=> date('Y-m-d') . ' 00:00:00',
					'to'	=> date('Y-m-d') . ' 23:59:59'
				]
			]);

		dd($mutasi);
	}

	public function mutasiPayPal()
	{
		$mutasi = Cekmutasi::paypal()->search([
				'date'		=> [
					'from'	=> date('Y-m-d') . ' 00:00:00',
					'to'	=> date('Y-m-d') . ' 23:59:59'
				]
			]);

		dd($mutasi);
	}

	public function mutasiGoPay()
	{
		$mutasi = Cekmutasi::gopay()->search([
				'date'		=> [
					'from'	=> date('Y-m-d') . ' 00:00:00',
					'to'	=> date('Y-m-d') . ' 23:59:59'
				]
			]);

		dd($mutasi);
	}

	public function mutasiOVO()
	{
		$mutasi = Cekmutasi::ovo()->search([
				'date'		=> [
					'from'	=> date('Y-m-d') . ' 00:00:00',
					'to'	=> date('Y-m-d') . ' 23:59:59'
				]
			]);

		dd($mutasi);
	}

	public function balance()
	{
		$result = Cekmutasi::balance();

		dd($result);
	}

	public function ip()
	{
		$result = Cekmutasi::checkIP();

		dd($result);
	}

	public function handleCallback(Request $request)
	{
		$ipn = Cekmutasi::catchIPN($request);

		dd($ipn);
	}
}
