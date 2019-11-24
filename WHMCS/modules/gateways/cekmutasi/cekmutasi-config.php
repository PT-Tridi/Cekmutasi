<?php

defined('CEKMUTASI_TIMEZONE') OR define('CEKMUTASI_TIMEZONE', 'Asia/Jakarta');
defined('CEKMUTASI_TABLE_TRANSACTION_IPN') OR define('CEKMUTASI_TABLE_TRANSACTION_IPN', 'cekmutasi_transactions_ipn');
defined('CEKMUTASI_TABLE_TRANSACTION_UNIQUE') OR define('CEKMUTASI_TABLE_TRANSACTION_UNIQUE', 'cekmutasi_transactions_unique');

$CekmutasiConfigs = array(
	'banks' => array(
		array('code' => 'bri', 'name' => 'Bank BRI'),
		array('code' => 'bni', 'name' => 'Bank BNI'),
		array('code' => 'mandiri_online', 'name' => 'Bank Mandiri'),
		array('code' => 'bca', 'name' => 'Bank BCA'),
		array('code' => 'ovo', 'name' => 'OVO'),
		array('code' => 'gopay', 'name' => 'GoPay'),

		//---
		array('code' => 'notify', 'name' => 'ALL'),
	),
);