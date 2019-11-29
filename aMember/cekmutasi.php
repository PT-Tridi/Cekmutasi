<?php

class Am_Paysystem_Cekmutasi extends Am_Paysystem_Abstract
{
    const PLUGIN_STATUS = self::STATUS_PRODUCTION;
    const PLUGIN_REVISION = '1.0.0';

    public function __construct(Am_Di $di, array $config)
    {
        $this->defaultTitle = ___("Transfer Bank, GoPay & OVO");
        $this->defaultDescription = ___("Transfer bank, GoPay & OVO dengan validasi otomatis");
        parent::__construct($di, $config);
        $this->addUniqueNumber();
    }

    public function getRecurringType()
    {
        return self::REPORTS_REBILL;
    }

    public function addUniqueNumber()
    {
        $uniqueNumber = $this->getConfig('unique_number');
        $uniqueNumberType = $this->getConfig('unique_number_type');


        if ($uniqueNumber == 'yes')
        {
            Am_Di::getInstance()->hook->add(Am_Event::INVOICE_GET_CALCULATORS, function (Am_Event $e) use ($uniqueNumberType) {
                if (@$GLOBALS['add_fraction']++) {
                    return;
                
                $invoice = $e->getInvoice();
                $item = $invoice->getItem(0);
                if ($item->data()->get('orig_first_price') <= 0) {
                    return;
                }

                $id = $invoice->pk() ?: $e->getDi()->db->selectCell("SELECT MAX(invoice_id)+1 FROM ?_invoice;");

                if ($item && !$item->data()->get('add_fraction')) {
                    $item->data()->set('add_fraction', 1);

                    // append custom calculator 
                    $calculators = $e->getReturn();
                    $calculators[] = new CekmutasiCalculator($e->getInvoice(), $uniqueNumberType, $id);
                                        
                    $e->setReturn($calculators);
                    
                    
                }
                
                unset($GLOBALS['add_fraction']);
            });
        }
    }

    public function getSupportedCurrencies()
    {
        return array('IDR');
    }

    public function _initSetupForm(Am_Form_Setup $form)
    {
        $form->addHtml()
            ->setHtml('Cekmutasi.co.id v.' . self::PLUGIN_REVISION);

        $form->addText('api_key', array('class' => 'el-wide'))
            ->setLabel('API Key')
            ->addRule('required');

        $form->addText('api_signature', array('class' => 'el-wide'))
            ->setLabel('API Signature')
            ->addRule('required');

        $label = Am_Html::escape(___('di sini'));
        $url = 'https://cekmutasi.co.id/app/integration';
        $text = ___('API Key & Signature dapat dilihat ');
        $form->addHtml()
            ->setHtml(<<<CUT
$text <a href="$url" class="link">$label</a>.
CUT
            );

        $unique_number = $form->addSelect('unique_number')->setLabel(___('Tambah kode unik?'));
        $unique_number->addRule('required');
        $unique_number->addOption('*** ' . ___('Pilih') . ' ***', '');
        $unique_number->addOption('Ya', 'yes');
        $unique_number->addOption('Tidak', 'no');

        $unique_number_type = $form->addSelect('unique_number_type')->setLabel(___('Tipe kode unik'));
        $unique_number_type->addRule('required');
        $unique_number_type->addOption('*** ' . ___('Pilih') . ' ***', '');
        $unique_number_type->addOption('Tambahkan', 'increase');
        $unique_number_type->addOption('Kurangi', 'decrease');

        $form->addTextarea("html", array('class' => 'el-wide', "rows"=>20))->setLabel(
                ___("Instruksi pembayaran untuk konsumen\n".
                "Anda bisa menggunakan kode HTML di form ini,\n".
                "dan akan tampil di konsumen Anda ketika memilih metode pembayaran ini\n".
                "Berikut tag yang bisa Anda gunakan untuk kontennya:\n".
                "%s - Struk tagihan HTML\n".
                "%s - Judul Produk\n".
                "%s - Nomor Invoice\n".
                "%s - Total pembayaran", '%receipt_html%', '%invoice_title%', '%invoice.public_id%', '%invoice.first_total%'))
            ->setValue(<<<CUT
%receipt_html%

Pembayaran bisa melalui 2 rekening di bawah ini : 


BANK BCA 

1234567890
A.n. Nama Anda


BANK MANDIRI 

1234567890
A.n. Nama Anda

---------------------------------------------------------------------
Jangan lupa masukan Invoice ID >> %invoice.public_id% << pada kolom keterangan saat transfer 

Konfirmasi pembayaran silahkan klik link dibawah:
(silahkan cantumkan link konfirmasi bila ada).
CUT
            );

        $label = Am_Html::escape(___('Cekmutasi.co.id'));
        $url = 'https://cekmutasi.co.id';
        $text = ___('Didukung oleh ');
        $form->addHtml()
            ->setHtml(<<<CUT
$text <a href="$url" class="link">$label</a>.
CUT
                );
    }

    public function _process($invoice, $request, $result)
    {
        unset($this->getDi()->session->cart);
        if ((float)$invoice->first_total == 0) {
            $invoice->addAccessPeriod(new Am_Paysystem_Transaction_Free($this));
        }
        $result->setAction(
            new Am_Paysystem_Action_Redirect(
                $this->getDi()->url("payment/".$this->getId()."/instructions",
                    array('id'=>$invoice->getSecureId($this->getId())), false)
            )
        );
    }
    public function directAction($request, $response, $invokeArgs)
    {
        $actionName = $request->getActionName();
        $invoiceLog = $this->_logDirectAction($request, $response, $invokeArgs);
        switch ($actionName)
        {
            case 'instructions':
                $invoice = $this->getDi()->invoiceTable->findBySecureId($request->getFiltered('id'), $this->getId());
                if (!$invoice) {
                    throw new Am_Exception_InputError(___("Sorry, seems you have used wrong link"));
                }
                $view = new Am_View;
                $html = $this->getConfig('html', 'Instruksi untuk pembayaran ini belum ada.');

                $tpl = new Am_SimpleTemplate;
                $tpl->receipt_html = $view->partial('_receipt.phtml', array('invoice' => $invoice, 'di' => $this->getDi()));
                $tpl->invoice = $invoice;
                $tpl->user = $this->getDi()->userTable->load($invoice->user_id);
                $tpl->invoice_id = $invoice->invoice_id;
                $tpl->cancel_url = $this->getDi()->url('cancel', array('id'=>$invoice->getSecureId('CANCEL')), false);
                $tpl->invoice_title = $invoice->getLineDescription();

                $view->invoice = $invoice;
                $view->content = $tpl->render($html) . $view->blocks('payment/offline/bottom');
                $view->title = $this->getTitle();
                $response->setBody($view->render("layout.phtml"));
                break;

            case "verify":
                $ipnUrl = $this->getPluginUrl('callback');
                $apiKey = $this->getConfig('api_key');
                $apiSignature = $this->getConfig('api_signature');
                $resultsTransactions = array();

                /** Check if push notification has authorize header */
                if (!$this->cekmutasi_check_authorize()) {
                    die("Unauthorized!");
                    return;
                }

                $ipn = json_decode(file_get_contents("php://input"), true);

                if( $ipn['action'] == 'payment_report' )
                {
                    foreach( $ipn['content']['data'] as $mutasi )
                    {
                        $token = hash_hmac('sha256', $this->getConfig('api_key'), $this->getConfig('api_signature'));

                        $request = new Am_HttpRequest($ipnUrl."?_token=".$token), Am_HttpRequest::METHOD_POST);
                        $request->addPostParameter(
                            array_merge(
                                array_diff_key($ipn['content'], array_flip(['data'])
                            ), $mutasi)
                        );

                        $log = $this->logRequest($request);
                        $response = $request->send();
                        $log->add($response);

                        if ($response->getStatus() == 200) {
                            $r = json_decode($response->getBody(), true);
                            if (!empty($r)) {
                                $resultsTransactions[] = $r;
                            }
                        }
                    }
                }

                header('Content-Type: application/json');
                echo json_encode($resultsTransactions);
                exit;
                break;

            case 'callback':

                /** Check if push notification has authorize header */
                if (!$this->cekmutasi_check_authorize()) {
                    die("Unauthorized!");
                    return;
                }
                
                $data = array();

                try
                {
                    $transaction = $this->createTransaction($request, $response, $invokeArgs);

                    if (!$transaction) {
                        throw new Am_Exception_InputError("Request not handled - createTransaction() returned null");
                    }

                    $invoice_id = $transaction->findInvoiceId();

                    if ($invoice_id == false)
                    {
                        $data = array(
                            'status' => 'duplicate-order',
                            'amount' => $request->get('amount'),
                            'service_code' => $request->get('service_code'),
                        );

                        header('Content-Type: application/json');
                        echo json_encode($data);
                        exit;
                        break;
                    }
                    elseif($invoice_id == null)
                    {
                        $data = array(
                            'status' => 'not-found',
                            'amount' => $request->get('amount'),
                            'service_code' => $request->get('service_code'),
                        );

                        header('Content-Type: application/json');
                        echo json_encode($data);
                        exit;
                        break;
                    }

                    $transaction->setInvoiceLog($invoiceLog);

                    try
                    {
                        $transaction->process();
                    }
                    catch (Exception $e)
                    {
                        if ($invoiceLog) {
                            $invoiceLog->add($e);
                        }
                        throw $e;
                    }
                    
                    if ($invoiceLog) {
                        $invoiceLog->setProcessed();
                    }
                    
                    $data = array(
                        'invoice_id' => $invoice_id,
                        'status' => 'completed',
                        'amount' => $request->get('amount'),
                        'service_code' => $request->get('service_code'),
                    );
                }
                catch (Exception $e)
                {
                    $data = array();
                }

                header('Content-Type: application/json');
                echo json_encode($data);
                exit;
                break;
            default:
                return parent::directAction($request, $response, $invokeArgs);
        }
    }

    public function createTransaction(Am_Mvc_Request $request, Am_Mvc_Response $response, array $invokeArgs)
    {
        return new Am_Paysystem_Transaction_Cekmutasi($this, $request, $response, $invokeArgs);
    }

    public function cancelAction(Invoice $invoice, $actionName, Am_Paysystem_Result $result)
    {
        $result->setSuccess();
        $invoice->setCancelled(true);
    }

    public function getReadme()
    {
        $verify = $this->getPluginUrl('verify');
        return <<<CUT
<strong>Tutorial cara integrasi dengan Cekmutasi.co.id:</strong>
- Salin link berikut ini - <strong>$verify</strong>.
- Lalu login ke website Cekmutasi.co.id (Pastikan Anda sudah mempunyai rekening yang sudah didaftarkan).
- Edit rekening, dan masuk ke kolom "IPN URL/Callback".
- Simpan.
CUT;
    }
    /**
     * Check Moota Authorize
     * @return bool
     */
    public function cekmutasi_check_authorize()
    {
        if (isset($_SERVER['HTTP_API_SIGNATURE']))
        {
            if( version_compare(PHP_VERSION, '5.6.0', '>=') )
            {
                if( hash_equals($_SERVER['HTTP_API_SIGNATURE'], $this->getConfig('api_signature')) ) {
                    return true;
                }
            }
            else
            {
                if( $_SERVER['HTTP_API_SIGNATURE'] === $this->getConfig('api_signature') ) {
                    return true;
                }
            }
        }
        return false;
    }
}



class Am_Paysystem_Transaction_Cekmutasi extends Am_Paysystem_Transaction_Incoming
{
    public function getUniqId()
    {
        $service_code = $this->request->get('service_code');
        $transaction_id = $this->findInvoiceId();
        return "{$service_code}.{$transaction_id}";
    }

    public function validateSource()
    {
        return true; //@see findInvoiceId
    }

    public function validateStatus()
    {
        return true;
    }

    public function validateTerms()
    {
        return true;
    }

    public function findInvoiceId()
    {
        $amount = $this->request->get('amount');
        $invoice = Am_Di::getInstance()->db->select("SELECT * FROM ?_invoice WHERE status = 0 AND first_total = ?d AND tm_added > NOW() - INTERVAL 1 WEEK", $amount);
        if (count($invoice) > 1) {
            return false;
        }
        return !empty($invoice) ? $invoice[0]['public_id'] : null;
    }
}



/**
 * @author Muhammad Azamuddin <mas.azamuddin@gmail.com> 
 * @class
 */

class CekmutasiCalculator extends Am_Invoice_Calc{
    /** @var Coupon */
    protected $coupon;
    protected $user;

    public $uniqueNumberType;
    public $id;

    public function __construct($invoice, $uniqueNumberType, $id){
        $this->uniqueNumberType = $uniqueNumberType;
        $this->id = $id;
    }
    

    public function calculate(Invoice $invoiceBill)
    {
        $this->coupon = $invoiceBill->getCoupon();
        $this->user = $invoiceBill->getUser();
        $isFirstPayment = $invoiceBill->isFirstPayment();

        $uniqueNumberType = $this->uniqueNumberType;
        $id = $this->id;

        $unique_code = $id % 999;

        foreach ($invoiceBill->getItems() as $item) {
            $item->first_discount = $item->second_discount = 0;
            if(!$this->coupon){
                /** PENTING INI */
                if($uniqueNumberType == 'increase'){
                    $item->first_price += $unique_code;
                } else {
                    $item->first_price -= $unique_code;
                }

                if($uniqueNumberType == 'increase'){
                    $item->second_price += $unique_code;
                } else {
                    $item->second_price -= $unique_code;
                }
            }
            $item->_calculateTotal();
        }

        if (!$this->coupon) return;

        if ($this->coupon->getBatch()->discount_type == Coupon::DISCOUNT_PERCENT){
            foreach ($invoiceBill->getItems() as $item) {
                if ($this->coupon->isApplicable($item, $isFirstPayment))
                    $item->first_discount = $item->qty * moneyRound($item->first_price * $this->coupon->getBatch()->discount / 100);

                    /** PENTING INI */
                    if($uniqueNumberType == 'increase'){
                        $item->first_discount -= $unique_code;
                    } else {
                        $item->first_discount += $unique_code;
                    }
                if ($this->coupon->isApplicable($item, false))
                    $item->second_discount = $item->qty * moneyRound($item->second_price * $this->coupon->getBatch()->discount / 100);


                    /** PENTING INI */
                    if($uniqueNumberType == 'increase'){
                        $item->second_discount -= $unique_code;
                    } else {
                        $item->second_discount += $unique_code;
                    }
            }
        } else { // absolute discount
            $discountFirst = $this->coupon->getBatch()->discount;
            $discountSecond = $this->coupon->getBatch()->discount;

            $first_discountable = $second_discountable = array();
            $first_total = $second_total = 0;
            $second_total = array_reduce($second_discountable, function($s, $item) {return $s+=$item->second_total;}, 0);
            foreach ($invoiceBill->getItems() as $item) {
                if ($this->coupon->isApplicable($item, $isFirstPayment)) {
                    $first_total += $item->first_total;
                    $first_discountable[] = $item;
                }
                if ($this->coupon->isApplicable($item, false)) {
                    $second_total += $item->second_total;
                    $second_discountable[] = $item;
                }
            }
            if ($first_total) {
                $k = max(0,min($discountFirst / $first_total, 1)); // between 0 and 1!
                foreach ($first_discountable as $item) {
                    $item->first_discount = moneyRound($item->first_total * $k);
                    /** PENTING INI */
                    if($uniqueNumberType == 'increase'){
                        $item->first_discount -= $unique_code;
                    } else {
                        $item->first_discount += $unique_code;
                    }
                }
            }
            if ($second_total) {
                $k = max(0,min($discountSecond / $second_total, 1)); // between 0 and 1!
                foreach ($second_discountable as $item) {
                    $item->second_discount = moneyRound($item->second_total * $k);
                    /** PENTING INI */
                    if($uniqueNumberType == 'increase'){
                        $item->second_discount -= $unique_code;
                    } else {
                        $item->second_discount += $unique_code;
                    }
                }
            }
        }

        foreach ($invoiceBill->getItems() as $item) {
            $item->_calculateTotal();
        }
    }
}