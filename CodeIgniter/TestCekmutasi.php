<?php

class TestCekmutasi extends CI_Controller
{
	public function index()
	{
		$this->load->library('cekmutasi');

		$mutasi = $this->cekmutasi->bank()->mutation([
			'date'		=> [
				'from'	=> date('Y-m-d') . ' 00:00:00',
				'to'	=> date('Y-m-d') . ' 23:59:59'
			]
		]);

		print_r(json_decode($mutasi));
	}
}