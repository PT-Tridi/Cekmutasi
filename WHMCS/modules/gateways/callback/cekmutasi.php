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
// Require libraries needed for gateway module functions.
require_once(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'init.php');
require_once(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'gatewayfunctions.php');
require_once(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'invoicefunctions.php');

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);
$PaymentCheck_Enabled = (isset($gatewayParams['PaymentCheck-Enabled']) ? $gatewayParams['PaymentCheck-Enabled'] : '');
$PaymentCheck_Enabled = ((strtolower($PaymentCheck_Enabled) === strtolower('on')) ? TRUE : FALSE);
$LocalApiAdminUsername = (isset($gatewayParams['Local-Api-Admin-Username']) ? $gatewayParams['Local-Api-Admin-Username'] : '');
$Log_Enabled = FALSE;

if (isset($gatewayParams['Log-Enabled']))
{
	$Log_Enabled = ((strtolower($gatewayParams['Log-Enabled']) == 'on') ? TRUE : FALSE);
}

// Require Configs of Cekmutasi
require(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cekmutasi' . DIRECTORY_SEPARATOR . 'cekmutasi-config.php');

if (!isset($CekmutasiConfigs)) {
	exit("No cekmutasi configs retrieved");
}

// Include Library Curl and Cekmutasi
include_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cekmutasi' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Lib_imzerscurl.php');
include_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cekmutasi' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Lib_cekmutasi.php');
// Get Instance Initialized

$curl = new Lib_imzerscurl();

//------ Build Config For Lib_cekmutasi
if (strtolower($gatewayParams['Environment']) === 'live')
{
	$CekmutasiConfigs['api_key'] = $gatewayParams['cekmutasi_api_key_live'];
	$CekmutasiConfigs['api_secret'] = $gatewayParams['cekmutasi_api_secret_live'];
}
else
{
	$CekmutasiConfigs['api_key'] = $gatewayParams['cekmutasi_api_key_dev'];
	$CekmutasiConfigs['api_secret'] = $gatewayParams['cekmutasi_api_secret_dev'];
}

$cekmutasi = new Lib_cekmutasi($CekmutasiConfigs);

// GET headers
if (isset($cekmutasi->cekmutasi_headers))
{
	if (is_array($cekmutasi->cekmutasi_headers) && (count($cekmutasi->cekmutasi_headers) > 0))
	{
		foreach ($cekmutasi->cekmutasi_headers as $headKey => $headVal) {
			$curl->add_headers($headKey, $headVal);
		}
	}
}

$CurrentDatezone = new DateTime();
$CurrentDatezone->setTimezone(new DateTimeZone(CEKMUTASI_TIMEZONE));

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$REQUEST_METHOD = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'HEAD');
$REQUEST_METHOD_MSG = "";

//------------------------------------------------------------------------------------------------
$input_request = array(
	'input_params'			=> $curl->php_input_request(),
	'query_string'			=> $curl->php_input_querystring(),
);

$response_request = array();
$bank_local = array();

if (isset($cekmutasi->base_config['banks']) && (count($cekmutasi->base_config['banks']) > 0)) {
	foreach ($cekmutasi->base_config['banks'] as $bank) {
		$bank_local[] = $bank['code'];
	}
}

if (isset($input_request['query_string']['page']))
{
	if ($input_request['query_string']['page'] != FALSE)
	{
		$input_request['query_string']['page'] = (is_string($input_request['query_string']['page']) ? strtolower($input_request['query_string']['page']) : '');

		// Cek if page = notify
		$Datezone = new DateTime();
		$Datezone->setTimezone(new DateTimeZone(CEKMUTASI_TIMEZONE));
		$Datetime_Range = array(
			'current'		=> $Datezone->format('Y-m-d H:i:s'),
		);

		if ($input_request['query_string']['page'] === 'notify')
		{
			$incomingSignature = isset($_SERVER['HTTP_API_SIGNATURE']) ? $_SERVER['HTTP_API_SIGNATURE'] : '';

			if( version_compare(PHP_VERSION, '5.6.0', '>=') )
			{
				if( !hash_equals($incomingSignature, $CekmutasiConfigs['api_secret']) ) {
					exit("Invalid signature!");
				}
			}
			else
			{
				if( $incomingSignature !== $CekmutasiConfigs['api_secret'] ) {
					exit("Invalid signature!");
				}
			}

			$insert_ipn_params = array(
				'payment_method'		=> $input_request['query_string']['page'],
				'input_data'			=> json_encode($input_request['input_params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
				'input_datetime'		=> $Datetime_Range['current'],
			);
			
			$new_ipn_seq = insert_query(CEKMUTASI_TABLE_TRANSACTION_IPN, $insert_ipn_params);
			$sql_where = array(
				'seq'		=> $new_ipn_seq,
			);

			try
			{
				$sql_result = select_query(CEKMUTASI_TABLE_TRANSACTION_IPN, '*', $sql_where);
			}
			catch (Exception $ex)
			{
				throw $ex;
				return false;
			}

			$ipn_data = mysqli_fetch_object($sql_result);
			
			if (isset($ipn_data->input_data))
			{
				try
				{
					$ipn_input_data = json_decode($ipn_data->input_data);
				}
				catch (Exception $ex)
				{
					exit("Cannot json decoded input IPN: {$ex->getMessage()}");
				}

				//=======================
				// Chek if payment_report
				
				$mutasi_data = array();
				if (isset($ipn_input_data->body->action) && isset($ipn_input_data->body->content->data))
				{
					$ipn_input_data->body->action = (is_string($ipn_input_data->body->action) ? strtolower($ipn_input_data->body->action) : '');

					if ($ipn_input_data->body->action === strtolower('payment_report'))
					{
						if (is_array($ipn_input_data->body->content->data) && (count($ipn_input_data->body->content->data) > 0))
						{
							foreach ($ipn_input_data->body->content->data as $content_data)
							{
								if (isset($content_data->type) && isset($content_data->amount) && isset($content_data->description))
								{
									if( strtolower($content_data->type) === 'credit' )
									{
										$CekmutasiDatezone = new DateTime();
										$CekmutasiDatezone->setTimestamp($content_data->unix_timestamp);
										$CekmutasiDatezone->setTimezone(new DateTimeZone(CEKMUTASI_TIMEZONE));

										$mutasi_data[] = array(
											'payment_method'			=> $ipn_input_data->body->content->service_code,
											'payment_amount'			=> sprintf('%.02f', $content_data->amount),
											'payment_description'		=> sprintf("%s", $content_data->description),
											'payment_datetime'			=> $CekmutasiDatezone->format('Y-m-d H:i:s'),
											'payment_type'				=> sprintf("%s", $content_data->type),
											'payment_identification'	=> sprintf("%s-%s", $ipn_input_data->body->content->account_number, $content_data->unix_timestamp),
										);
									}
								}
							}
						}
					}
				}

				//-----------------------------------------------
				// Set payment expired datetime
				# Default Unit: day
				// Query select from mutasi data
				//-----------------------------------------------
				$Datetime_Range['payment_expired'] = '';
				if (count($mutasi_data) > 0)
				{
					foreach ($mutasi_data as $mutasi)
					{
						$sql = sprintf("SELECT * FROM %s WHERE (paymentmethod = '%s' AND total = '%.02f' AND status IN('Created', 'Unprocess', 'Unpaid')) AND (DATE('%s') BETWEEN `date` AND DATE_ADD(`date`, INTERVAL %d DAY)) ORDER BY `date` DESC LIMIT 1",
							'tblinvoices',
							$gatewayParams['paymentmethod'],
							$mutasi['payment_amount'],
							$mutasi['payment_datetime'],
							$gatewayParams['change_day']
						);

						try
						{
							$sql_query = full_query($sql);
						}
						catch (Exception $ex)
						{
							throw $ex;
							return false;
						}

						$invoices_data = mysqli_fetch_object($sql_query);

						if( isset($invoices_data->id) )
						{
							// Generate Invoice Transaction Data
							$invoices_data->transaction_data = array(
								'payment_method'					=> $mutasi['payment_method'],
								'order_total'						=> $invoices_data->total,
								'payment_insert'					=> $mutasi['payment_datetime'],
								'payment_cekmutasi_durasi_unit'		=> $gatewayParams['unique_range_unit'],
								'payment_cekmutasi_durasi_amount'	=> $gatewayParams['unique_range_amount'],
								'order_status'						=> $invoices_data->status,
								'payment_identification'			=> $mutasi['payment_identification'],
							);

							//========================
							if ($Log_Enabled) {
								logTransaction($gatewayParams['name'], $invoices_data->transaction_data, 'notify');
							}

							//========================
							if ($PaymentCheck_Enabled !== FALSE)
							{
								try
								{
									$response_request[] = set_cekmutasi_payment_status($curl, $cekmutasi, $invoices_data->transaction_data);
								}
								catch (Exception $ex)
								{
									throw $ex;
									return false;
								}

								if (count($response_request) > 0)
								{
									echo "SUCCESS [Check-API Enabled]";
									print_r($response_request);
								}
							}
							else
							{
								$invoiceId = checkCbInvoiceID($invoices_data->id, $gatewayParams['name']);
								checkCbTransID($mutasi['payment_identification']);
								// Set Payment Success
								addInvoicePayment(
									$invoiceId,
									$mutasi['payment_identification'],
									$mutasi['payment_amount'],
									0,
									$gatewayModuleName
								);
								echo "SUCCESS [Check-API Disabled]";
							}
						}
					}
				}
				//-----------------------------------------------
			}
			else
			{
				exit("No input data from ipn logs db.");
			}
		}
		elseif(strtolower($input_request['query_string']['page']) === 'order')
		{
			$invoice_id = 0;
			$redirect_url = $gatewayParams['systemurl'] . 'viewinvoice.php?id=';
			if (isset($input_request['input_params']['body']['invoice_id']))
			{
				if (is_numeric($input_request['input_params']['body']['invoice_id']))
				{
					$invoice_id = (int)$input_request['input_params']['body']['invoice_id'];
					$redirect_url .= sprintf("%d", $input_request['input_params']['body']['invoice_id']);
				}
				else
				{
					$redirect_url = $gatewayParams['systemurl'];
				}
			}

			// Get InvoiceData
			try
			{
				$invoiceData = localAPI('GetInvoice', array('invoiceid' => $invoice_id), $gatewayParams['Local-Api-Admin-Username']);
			}
			catch (Exception $ex)
			{
				throw $ex;
				exit("Cannot get invoices data.");
			}

			if (!isset($invoiceData['result'])) {
				exit("Invoice data not-found.");
			}

			// Get Data From Cekmutasi.co.id
			if (strtolower($invoiceData['result']) === strtolower('success'))
			{
				$order_invoices_data = array(
					'payment_method'						=> (isset($input_request['input_params']['body']['payment_method']) ? (is_string($input_request['input_params']['body']['payment_method']) ? strtolower($input_request['input_params']['body']['payment_method']) : 'all') : 'all'),
					'order_total'						=> (isset($invoiceData['total']) ? $invoiceData['total'] : 0),
					'payment_insert'					=> (isset($invoiceData['date']) ? $invoiceData['date'] : $CurrentDatezone->format('Y-m-d H:i:s')),
					'payment_cekmutasi_durasi_unit'		=> $gatewayParams['unique_range_unit'],
					'payment_cekmutasi_durasi_amount'	=> $gatewayParams['unique_range_amount'],
					'order_status'						=> (isset($invoiceData['status']) ? $invoiceData['status'] : ''),
					'payment_identification'			=> '',
				);
				
				try
				{
					$payment_status = set_cekmutasi_payment_status($curl, $cekmutasi, $order_invoices_data);
				}
				catch (Exception $ex)
				{
					exit("Cannot get payment-status : {$ex->getMessage()}.");
				}

				if ($payment_status != FALSE)
				{
					if ($Log_Enabled) {
						logTransaction($gatewayParams['name'], $payment_status, 'order');
					}

					if (isset($payment_status['cekmutasi']['tmp_data']->error_message))
					{
						$payment_status['cekmutasi']['tmp_data']->error_message = sprintf("%s", $payment_status['cekmutasi']['tmp_data']->error_message);
						if (strlen($payment_status['cekmutasi']['tmp_data']->error_message) > 0)
						{
							?>
							<script type="text/javascript">
								alert('<?= $payment_status['cekmutasi']['tmp_data']->error_message;?>');
								window.location.href = '<?=$redirect_url;?>';
							</script>
							<?php
							exit;
						}
					}
				}
				
			}
			// Redirecting page to invoice page
			header("Location: {$redirect_url}");
			exit;
		}
		else
		{
			exit("Undefined method!");
		}
	}
	else
	{
		exit("No type params (should be notify) and bank params.");
	}
}
else
{
	exit("Required query-string not accepted.");
}

//----------------------------------------------
################################################
function set_cekmutasi_payment_status($curl, $cekmutasi, $order_invoices_data, $gatewayParams = null)
{
	if (!isset($gatewayParams)) {
		global $gatewayParams;
	}

	$collect = array(
		'cekmutasi'				=> array(),
	);

	// Get Transaction Data
	$Datezone = new DateTime();
	$Datezone->setTimezone(new DateTimeZone(CEKMUTASI_TIMEZONE));
	$Datetime_Range = array(
		'current'		=> $Datezone->format('Y-m-d H:i:s'),
	);
	$collect['transaction_data'] = (object)$order_invoices_data;
	
	if ($collect['transaction_data']->order_status != 'Pending')
	{
		//--------------------------------------
		// Generate Search Params
		$collect['cekmutasi']['input_params'] = $cekmutasi->generate_search_params($collect['transaction_data']);
		//--------------------------------------
		try
		{
			$collect['cekmutasi']['api'] = $curl->create_curl_request('POST', $cekmutasi->get_api_url($order_invoices_data['payment_method']), $curl->UA, $curl->generate_curl_headers(), $collect['cekmutasi']['input_params']);
		}
		catch (Exception $ex)
		{
			throw $ex;
			$collect['cekmutasi']['api'] = false;
		}

		if (isset($collect['cekmutasi']['api']['response']['body']))
		{
			try
			{
				$collect['cekmutasi']['tmp_data'] = json_decode($collect['cekmutasi']['api']['response']['body']);
			}
			catch (Exception $ex)
			{
				throw $ex;
				$collect['cekmutasi']['tmp_data'] = false;
			}

			$collect['mutasi_data'] = array();

			if ( $collect['cekmutasi']['tmp_data']->success === true )
			{
				if (isset($collect['cekmutasi']['tmp_data']->response))
				{
					if (is_array($collect['cekmutasi']['tmp_data']->response) && (count($collect['cekmutasi']['tmp_data']->response) > 0))
					{
						foreach ($collect['cekmutasi']['tmp_data']->response as $response)
						{
							if (strtolower($response->type) === 'credit')
							{
								$CekmutasiDatezone = new DateTime();
								$CekmutasiDatezone->setTimestamp($response->unix_timestamp);
								$CekmutasiDatezone->setTimezone(new DateTimeZone(CEKMUTASI_TIMEZONE));
								$collect['mutasi_data'][] = array(
									'payment_method'			=> (isset($response->service_code) ? $response->service_code : ''),
									'payment_amount'			=> sprintf('%.02f', $response->amount),
									'payment_description'		=> sprintf("%s", $response->description),
									'payment_datetime'			=> $CekmutasiDatezone->format('Y-m-d H:i:s'),
									'payment_type'				=> sprintf("%s", $response->type),
									'order_status'				=> $invoices_data->status,
									'payment_identification'	=> sprintf("%s-%s", $response->account_number, $response->unix_timestamp),
								);
							}
						}
					}
				}
			}
			
			//-------------------------------------------
			// Set If Success Payment
			if (count($collect['mutasi_data']) > 0)
			{
				foreach ($collect['mutasi_data'] as $mutasi)
				{
					$sql = sprintf("SELECT * FROM %s WHERE (paymentmethod = '%s' AND total = '%.02f' AND status IN('Created', 'Unprocess', 'Unpaid')) AND (DATE('%s') BETWEEN `date` AND DATE_ADD(`date`, INTERVAL %d DAY)) ORDER BY `date` DESC LIMIT 1",
						'tblinvoices',
						$gatewayParams['paymentmethod'],
						$mutasi['payment_amount'],
						$mutasi['payment_datetime'],
						$gatewayParams['change_day']
					);

					try
					{
						$sql_query = full_query($sql);
					}
					catch (Exception $ex)
					{
						throw $ex;
						return false;
					}

					$invoices_data = mysqli_fetch_object($sql_query);

					if (isset($invoices_data->id))
					{
						$invoiceId = checkCbInvoiceID($invoices_data->id, $gatewayParams['name']);
						checkCbTransID($mutasi['payment_identification']);
						// Set Payment Success
						addInvoicePayment(
							$invoiceId,
							$mutasi['payment_identification'],
							$mutasi['payment_amount'],
							0,
							$gatewayModuleName
						);
					}
				}
			}
		}
	}
	return $collect;
}