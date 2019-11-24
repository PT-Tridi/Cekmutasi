<?php

/**
 * WHMCS Cekmutasi Payment Gateway Module
 *
 * Cekmutasi Payment Gateway Module allow you to integrate Cekmutasi with the
 * WHMCS platform.
 *
 * This Module Information:
 --------------------------
 https://cekmutasi.co.id
 Version: 1.0
 Released: 2018-07-01
 --------------------------
 *
 * For more information, about this modules payment please kindly visit our website at cekmutasi.co.id
 *
 */
 
if( !defined("WHMCS") ) {
    die("This file cannot be accessed directly");
}

$configfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cekmutasi' . DIRECTORY_SEPARATOR . 'cekmutasi-config.php';

require_once $configfile;

if (!isset($configs)) {
	exit("There is no configs of included config file.");
}

//=============================================================
// Cekmutasi Unique Number Class
//=============================================================

class cekmutasi_unique
{
	protected $settings;

	function __construct($settings = null)
	{
		$this->initialize($settings);
	}

	function initialize($settings = array())
	{
		$this->settings = $settings;
	}
	
	function cekmutasi_get_new_unique_number()
	{
		$int_random = mt_rand($this->settings['unique_starting'], $this->settings['unique_stopping']);

		return (int) $int_random;
	}

	private function cekmutasi_check_new_unique($unique_number, $sql_string, $sql_table)
	{
		$rows = 0;
		$unique_number = (int) $unique_number;
		$sql = is_string($sql_string) ? $sql_string : '';

		if (strlen($sql) > 0)
		{
			$sql .= sprintf(" AND (unique_amount = '%d')", $unique_number);
			$sql .= " AND (trans_user != 0)";
		}

		$sql = str_replace('[#TABLE_UNIQUE#]', $sql_table, $sql);
		
		try
		{
			$sql_query = full_query($sql);
		}
		catch (Exception $ex)
		{
			throw $ex;
			return false;
		}

		$row_data = mysqli_fetch_assoc($sql_query);
		$rows = isset($row_data['value']) ? $row_data['value'] : 0;
		return $rows;
	}

	function cekmutasi_calculate_unique($sql_table, $timezone)
	{
		if ( strtolower($this->settings['unique_status']) != 'on' )
		{
			return;
		}
		
		$unique_params = array();

		$Datezone = new DateTime();
		$Datezone->setTimezone(new DateTimeZone($timezone));
		
		$unique_params['unique_amount'] = $this->cekmutasi_generate_new_unique($this->settings['unique_range_unit'], $this->settings['unique_range_amount'], $sql_table, $timezone);

		if ($this->settings['unique_type'] == 'decrease')
		{
			$unique_params['unique_amount'] = (int) -$unique_params['unique_amount'];
		}

		// Generate Insert Params
		$insert_params = array(
			'trans_seq'					=> $this->settings['invoicenum'],
			'trans_user'				=> (isset($this->settings['clientdetails']['userid']) ? $this->settings['clientdetails']['userid'] : 0),
			'trans_invoiceid'			=> $this->settings['invoiceid'],
			'unique_payment_gateway'	=> $this->settings['paymentmethod'],
			'unique_unit_name'			=> $this->settings['unique_range_unit'],
			'unique_unit_amount'		=> $this->settings['unique_range_amount'],
			'unique_label'				=> $this->settings['unique_label'],
			'unique_amount'				=> $unique_params['unique_amount'],
			'unique_date'				=> $Datezone->format('Y-m-d'),
			'unique_datetime'			=> $Datezone->format('Y-m-d H:i:s'),
		);

		return $insert_params;
	}

	private function cekmutasi_generate_new_unique($string_unit = 'day', $int_amount = 0, $sql_table, $timezone)
	{
		$string_unit = is_string($string_unit) ? strtolower($string_unit) : 'day';

		if ( !in_array($string_unit, array('minute', 'hour', 'day')) )
		{
			$string_unit = 'day';
		}

		$int_amount = (int) $int_amount;

		// Include libraries
		include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cekmutasi' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Lib_datezone.php';

		$Lib_datezone = new Lib_datezone();
		$date_stopping = new DateTime();
		$date_stopping->setTimezone(new DateTimeZone($timezone));

		switch (strtolower($string_unit))
		{
			case 'minute':
				$date_starting = $Lib_datezone->reduce_date_by('MINUTE', $int_amount, $date_stopping);
				break;

			case 'hour':
				$date_starting = $Lib_datezone->reduce_date_by('HOUR', $int_amount, $date_stopping);
				break;

			case 'day':
			default:
				$date_starting = $Lib_datezone->reduce_date_by('DAY', $int_amount, $date_stopping);
				break;
		}

		$sql = sprintf("SELECT COUNT(seq) AS value FROM %s WHERE (unique_unit_name = '%s' AND unique_unit_amount = '%d')",
			'[#TABLE_UNIQUE#]',
			$string_unit,
			$int_amount
		);

		switch (strtolower($string_unit))
		{
			case 'minute':
				$sql .= sprintf(" AND (unique_datetime BETWEEN '%s' AND '%s')",
					$date_starting->format('Y-m-d H:i'),
					$date_stopping->format('Y-m-d H:i')
				);
				break;

			case 'hour':
				$sql .= sprintf(" AND (unique_datetime BETWEEN '%s' AND '%s')",
					$date_starting->format('Y-m-d H'),
					$date_stopping->format('Y-m-d H')
				);
				break;

			case 'day':
			default:
				$sql .= sprintf(" AND (DATE(unique_datetime) BETWEEN '%s' AND '%s')",
					$date_starting->format('Y-m-d'),
					$date_stopping->format('Y-m-d')
				);
				break;
		}

		do
		{
			$unique_number = $this->cekmutasi_get_new_unique_number();
			$rows = $this->cekmutasi_check_new_unique($unique_number, $sql, $sql_table);
		} while ($rows > 0);

		return $unique_number;
	}
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function cekmutasi_MetaData()
{
    return array(
        'DisplayName' => 'Cekmutasi.co.id',
        'APIVersion' => '1.2', // Use API Version 1.2
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

//------------------------
// Include Cekmutasi Admin Lib
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cekmutasi' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Lib_adminconstant.php';

if (!file_exists($configfile)) {
	Exit("Required configs file does not exists.");
}

$CekmutasiAdmin = new Lib_adminconstant($CekmutasiConfigs);

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
function cekmutasi_config()
{
    $configs = array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Cekmutasi WHMCS',
			'Description' => 'Sistem Validasi Pembayaran Bank Otomatis dan Pengelolaan Rekening Terintegrasi',
        ),
		'Pembatas-Description-Payment-Gateway' => array(
			'FriendlyName' => '',
			'Type' => 'hidden',
			'Size' => '72',
            'Default' => '',
			'Description' => '<img src="https://cekmutasi.co.id/logo-dark.png" align="left" style="padding-right:12px;" /> Sistem Validasi Pembayaran Bank Otomatis dan Pengelolaan Rekening Terintegrasi',
		),
		'Description-Payment-Gateway' => array(
			'FriendlyName' => '<span style="color:red;font-weight:bold;">Description<span>',
			'Type' => 'textarea',
			'Rows' => '8',
            'Cols' => '72',
			'Default' => 'Sistem Validasi Pembayaran Bank Otomatis dan Pengelolaan Rekening Terintegrasi',
			'Description' => '',
		),
        // a text field type allows for single line text input
		# Development
		//-------------
		'Pembatas-Sandbox' => array(
			"FriendlyName" => "(*)", 
			"Type" => 'hidden',
			"Size" => "64",
			"Default" => '',
			"Description" => '<span style="color:green;font-weight:bold;">Parameter Development</span><br/>Dapatkan API Key dan Signature melalui : <a href="https://cekmutasi.co.id/app/integration" target="_blank">https://cekmutasi.co.id/app/integration</a>',
		),
		//-------------
        'cekmutasi_api_key_dev' => array(
            'FriendlyName' => '<span style="color:red;font-weight:bold;">Development: Api Key</span>',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Masukkan API Key Anda',
        ),
		'cekmutasi_api_secret_dev' => array(
			'FriendlyName' => '<span style="color:red;font-weight:bold;">Development: API Signature</span>',
			'Type' => 'text',
			'Size' => '25',
			'Default' => '',
			'Description' => 'Masukkan API Signature Anda',
		),
		//-------------
		'Pembatas-Live' => array(
			"FriendlyName" => "(*)", 
			"Type" => 'hidden',
			"Size" => "64",
			"Default" => '',
			"Description" => '<span style="color:red;font-weight:bold;">Parameter Production</span><br/>Dapatkan API Key dan Signature melalui : <a href="https://cekmutasi.co.id/app/integration" target="_blank">https://cekmutasi.co.id/app/integration</a>',
		),
		//-------------
		# Live
		'cekmutasi_api_key_live' => array(
            'FriendlyName' => '<span style="color:red;font-weight:bold;">(*)</span>Production: API Key',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Masukkan API Key Anda',
        ),
		'cekmutasi_api_secret_live' => array(
			'FriendlyName' => '<span style="color:red;font-weight:bold;">(*)</span>Production: API Signature',
			'Type' => 'text',
			'Size' => '25',
			'Default' => '',
			'Description' => 'Masukkan API Signature Anda',
		),
		//-------------
		'Pembatas-Global-Params' => array(
			"FriendlyName" => "", 
			"Type" => 'hidden',
			"Size" => "64",
			"Default" => '',
			"Description" => '<span style="color:blue;font-weight:bold;">Global Params</span>',
		),
		//-------------
		# GLOBAL
		// the dropdown field type renders a select menu of options (LOGGER)
        'Log-Enabled' => array(
            'FriendlyName' => "<span style='font-weight:bold;'>Aktifkan log transaksi?</span>", 
			'Type' => "yesno",
			'Description' => "Centang untuk menampilkan log", 
        ),
        // the dropdown field type renders a select menu of options
        'Environment' => array(
            'FriendlyName' => '<span style="font-weight:bold;">Mode Environment</span>',
            'Type' => 'dropdown',
            'Options' => array(
                'sandbox' => 'Development',
                'live' => 'Production',
            ),
            'Description' => 'Pilih mode',
        ),
		//-------------
		// Unique Number
		"unique_status" => array(
			'FriendlyName' => "<span style='font-weight:bold;'>Aktifkan Nominal Unik?</span>", 
			'Type' => "yesno",
			'Description' => "Centang, untuk mengaktifkan fitur penambahan 3 angka unik di setiap akhir pesanan / order. Sebagai pembeda dari order satu dengan yang lainnya.", 
		),
		'unique_label' => array(
			'FriendlyName'	=> '<span style="font-weight:bold;">Label Kode Unik<span>',
			'Type'			=> 'text',
			'Size'			=> '22',
			'Default'		=> 'Kode Unik',
			'Description'	=> 'Label yang akan muncul di form checkout',
		),
		'unique_starting' => array(
			'FriendlyName'	=> '<span style="font-weight:bold;">Batas Awal Angka Unik</span>',
			'Type'			=> 'text',
			'Size'			=> '22',
			'Default'		=> '10',
			'Description'	=> 'Masukan batas awal angka unik',
		),
		'unique_stopping' => array(
			'FriendlyName'	=> '<span style="font-weight:bold;">Batas Akhir Angka Unik</span>',
			'Type'			=> 'text',
			'Size'			=> '22',
			'Default'		=> '999',
			'Description'	=> 'Masukan batas akhir angka unik',
		),
		'unique_type' => array(
			'FriendlyName' => '<span style="font-weight:bold;">Tipe Kalkulasi</span>',
            'Type' => 'dropdown',
            'Options' => array(
                'increase'      => 'Tambahkan',
                'decrease'      => 'Kurangi',
            ),
            'Description' => '<br/>Increase = Menambah angka unik ke total harga<br/>Decrease = Mengurangi total harga dengan angka unik',
		),
		'unique_range_unit' => array(
			'FriendlyName' => '<span style="font-weight:bold;">Satuan unit masa aktif nominal unik</span>',
            'Type' => 'dropdown',
            'Options' => array(
                'day'			=> 'Hari',
				'hour'			=> 'Jam',
				'minute'		=> 'Menit',
            ),
            'Description' => '<br/>Batas perhitungan nomor unik, default menggunakan satuan hari',
		),
		'unique_range_amount' => array(
			'FriendlyName'	=> '<span style="font-weight:bold;">Masa berlaku nominal unik sesuai satuan unit diatas</span>',
			'Type'			=> 'text',
			'Size'			=> '22',
			'Default'		=> '1',
			'Description'	=> '<br/>Jumlah berapa kali didalam unit untuk perhitungan nomor unik, jika 1 hari berarti nominal unik valid selama 1 hari penuh',
		),

		'change_day' => array(
			'FriendlyName' => '<span style="font-weight:bold;">Perubahan status di hari ke?</span>',
            'Type' => 'dropdown',
            'Options' => array(
                '1'				=> 'H+1',
                '2'      		=> 'H+2',
                '3'      		=> 'H+3',
                '4'      		=> 'H+4',
                '5'      		=> 'H+5',
                '6'      		=> 'H+6',
                '7'      		=> 'H+7',
            ),
            'Description' => '<br/>Setelah konsumen checkout dan belum bayar, pilih hari ke berapa status order berubah otomatis dari ON-HOLD ke PENDING',
		),

		"PaymentCheck-Enabled" => array(
			'FriendlyName' => "<span style='font-weight:bold;'>Aktifkan verifikasi IPN</span>", 
			'Type' => "yesno",
			'Description' => "Centang untuk mengaktifkan dan cek verifikasi IPN yang masuk",
		),
		//----------------------------------------------------------------------------
    );

	//------------------------------------------------------------------------------------
	// LOCAL API ADMIN USERNAME
	//------------------------------------------------------------------------------------

	$configs["Local-Api-Admin-Username"] = array(
		'FriendlyName'	=> '<span style="color:red;font-weight:bold;">(*) WHMCS Admin Username for using WHMCS::localAPI().</span>',
		'Type'			=> 'text',
		'Size'			=> '22',
		'Default'		=> '',
		'Description'	=> '<br/>Masukkan username Admin WHMCS untuk menggunakan WHMCS::localAPI().',
	);

	//------------------------------------------------------
	$configs['URL-Notify'] = array(
		"FriendlyName"	=> "Masukkan URL IPN/Callback IPN ini ke dalam rekening yang terdaftar di Cekmutasi.co.id", 
		"Type" 			=> 'hidden',
		"Size"			=> "64",
		"Value"			=> Lib_adminconstant::$notify,
		"Default"		=> Lib_adminconstant::$notify,
		"Description"	=> Lib_adminconstant::$notify,
	);

	//====================================================
	// Query to create table
	//====================================================
	$sql_table_ipn = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
		`seq` int(11) NOT NULL AUTO_INCREMENT,
		`payment_method` varchar(50) NOT NULL,
		`input_data` text NOT NULL,
		`input_datetime` datetime NOT NULL,
		PRIMARY KEY (`seq`),
		KEY `payment_method` (`payment_method`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
		CEKMUTASI_TABLE_TRANSACTION_IPN
	);

	try
	{
		$sql_query = full_query($sql_table_ipn);
	}
	catch (Exception $ex)
	{
		throw $ex;
		exit("Cannot query for table ipn");
	}

	$sql_table_unique = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
		`seq` int(11) NOT NULL AUTO_INCREMENT,
		`trans_seq` int(11) NOT NULL,
		`trans_user` int(11) NOT NULL DEFAULT '0',
		`trans_invoiceid` int(11) NOT NULL DEFAULT '0',
		`unique_payment_gateway` varchar(64) NOT NULL DEFAULT 'cekmutasi',
		`unique_unit_name` enum('day','hour','minute') NOT NULL DEFAULT 'day',
		`unique_unit_amount` smallint(4) NOT NULL,
		`unique_label` tinytext NOT NULL,
		`unique_amount` smallint(4) NOT NULL,
		`unique_date` date DEFAULT NULL,
		`unique_datetime` datetime DEFAULT NULL,
		PRIMARY KEY (`seq`),
		KEY `trans_seq` (`trans_seq`),
		KEY `trans_seq_trans_user` (`trans_seq`,`trans_user`),
		KEY `unique_datetime` (`unique_datetime`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
		CEKMUTASI_TABLE_TRANSACTION_UNIQUE
	);

	try
	{
		$sql_query = full_query($sql_table_unique);
	}
	catch (Exception $ex)
	{
		throw $ex;
		exit("Cannot query for table unique");
	}

	//====================================================
	# return configs
	return $configs;
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function cekmutasi_link($params)
{
	$htmlPrint = "";
	$sql_where = array(
		'trans_seq'						=> (isset($params['invoicenum']) ? $params['invoicenum'] : 0),
		'trans_user'					=> (isset($params['clientdetails']['userid']) ? $params['clientdetails']['userid'] : 0),
		'trans_invoiceid'				=> $params['invoiceid'],
		'unique_payment_gateway'		=> $params['paymentmethod'],
	);

	try
	{
		$sql_result = select_query(CEKMUTASI_TABLE_TRANSACTION_UNIQUE, '*', $sql_where, 'unique_datetime', 'DESC', '0,1');
	}
	catch (Exception $ex)
	{
		throw $ex;
		return false;
	}

	$unique_data = mysqli_fetch_assoc($sql_result);

	if (!isset($unique_data['seq']))
	{
		$insert_unique_seq = cekmutasi_cekmutasi_calculate_unique($params);

		if ( intval($insert_unique_seq) > 0 )
		{
			$sql_where = array("seq" => $insert_unique_seq);
			$sql_result = select_query(CEKMUTASI_TABLE_TRANSACTION_UNIQUE, '*', $sql_where);
			$unique_data = mysqli_fetch_assoc($sql_result);
		}
		else
		{
			$unique_data = false;
		}

		// Add Unique Data to Update Invoice - AS PG FEE
		if ($unique_data != false)
		{
			$unique_data_as_items = array(
				'invoiceid'				=> $params['invoiceid'],
				'userid'				=> $params['clientdetails']['userid'],
				'type'					=> (isset($params['unique_label']) ? $params['unique_label'] : 'Fee'),
				'relid'					=> '0', // relid?
				'description'			=> (isset($params['description']) ? $params['description'] : '-'),
				'amount'				=> $unique_data['unique_amount'],
				'taxed'					=> '0',
				'duedate'				=> (isset($params['dueDate']) ? $params['dueDate'] : date('Y-m-d')),
				'paymentmethod'			=> $params['paymentmethod'],
				'notes'					=> (isset($params['unique_type']) ? $params['unique_type'] : 'gateway_unique'),
			);

			$unique_data_as_items['description'] .= " " . (isset($params['unique_label']) ? $params['unique_label'] : 'Fee');

			if (strtolower($params['unique_label']) === 'decrease')
			{
				$unique_data_as_items['amount'] = (int) -$unique_data_as_items['amount'];
			}

			insert_query("tblinvoiceitems", $unique_data_as_items);
			updateInvoiceTotal($params['invoiceid']);
		}
	}

	//================
	// Get InvoiceData
	try
	{
		$invoiceData = localAPI('GetInvoice', array('invoiceid' => $params['invoiceid']), $params['Local-Api-Admin-Username']);
	}
	catch (Exception $ex)
	{
		throw $ex;
		exit("Cannot get invoices data.");
	}

	if (!isset($invoiceData['result'])) {
		exit("Invoice data not-found.");
	}

	//---- Build Config For Lib_cekmutasi
	$cekmutasi_configs = array();
	if (strtolower($params['Environment']) === 'live')
	{
		$cekmutasi_configs['api_key'] = $params['cekmutasi_api_key_live'];
		$cekmutasi_configs['api_secret'] = $params['cekmutasi_api_secret_live'];
	}
	else
	{
		$cekmutasi_configs['api_key'] = $params['cekmutasi_api_key_dev'];
		$cekmutasi_configs['api_secret'] = $params['cekmutasi_api_secret_dev'];
	}

	$cekmutasi_configs['change_day'] = (int) $params['change_day'];
	
	$htmlPrint .= '<form method="post" action="' . $params['systemurl'] . '/modules/gateways/callback/cekmutasi.php?page=order">';
	$htmlPrint .= '<input type="hidden" name="invoice_id" value="' . $params['invoiceid'] . '" />';
    $htmlPrint .= '<input type="submit" value="Konfirmasi Pembayaran" />';
    $htmlPrint .= '</form>';
	
	$htmlPrint .= "<div class='row'>";
	$htmlPrint .= "<div class='col-md-12'>";
	$htmlPrint .= $params['Description-Payment-Gateway'];
	$htmlPrint .= "</div>";
	$htmlPrint .= "</div>";

	return $htmlPrint;
}

function cekmutasi_cekmutasi_calculate_unique($params)
{
	$cekmutasi_unique = new cekmutasi_unique($params);
	$insert_params = $cekmutasi_unique->cekmutasi_calculate_unique(CEKMUTASI_TABLE_TRANSACTION_UNIQUE, CEKMUTASI_TIMEZONE);
	try
	{
		$new_unique_seq = insert_query(CEKMUTASI_TABLE_TRANSACTION_UNIQUE, $insert_params);
	}
	catch (Exception $ex)
	{
		throw $ex;
		return false;
	}

	return $new_unique_seq;
}
/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @return array Transaction response status
 */
function cekmutasi_refund($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to initiate refund and interpret result

    return array(
        // 'success' if successful, otherwise 'declined', 'error' for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
        // Unique Transaction ID for the refund transaction
        'transid' => $refundTransactionId,
        // Optional fee amount for the fee value refunded
        'fees' => $feeAmount,
    );
}

/**
 * Cancel subscription.
 *
 * If the payment gateway creates subscriptions and stores the subscription
 * ID in tblhosting.subscriptionid, this function is called upon cancellation
 * or request by an admin user.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/subscription-management/
 *
 * @return array Transaction response status
 */
function cekmutasi_cancelSubscription($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Subscription Parameters
    $subscriptionIdToCancel = $params['subscriptionID'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to cancel subscription and interpret result

    return array(
        // 'success' if successful, any other value for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
    );
}
























