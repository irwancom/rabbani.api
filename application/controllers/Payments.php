<?php

defined('BASEPATH') OR exit('No direct script access allowed');

date_default_timezone_set('Asia/Jakarta');

class Payments extends CI_Controller {

    const DOKU_SHARED_KEY = 'rwEodxWeA2c4';
    const DOKU_STORE_ID = '11631072';
    const DOKU_INVOICE_PREFIX = 'AB_';

    private $payment_channels = array(
        '02' => 'Mandiri Clickpay',
        '04' => 'DOKU Wallet',
        '07' => 'Permata VA',
        '14' => 'Alfa Group',
        '15' => 'Credit Card Visa/Master',
        '16' => 'Credit Card Tokenization',
        '17' => 'Recurring Payment',
        '22' => 'Sinarmas VA',
        '23' => 'MOTO',
        '31' => 'Indomaret',
        '32' => 'CIMB VA',
        '34' => 'BRI VA',
        '38' => 'BNI VA',
        '41' => 'Mandiri VA',
        '42' => 'QNB VA'
    );
    private $ip_range = '103.10.128.';

    /**
     * Constructor
     * 
     * @access public
     * @return void
     */
    public function __construct() {
        parent::__construct();

        $this->load->library('session');
        $this->load->model('payment_model');
        $this->load->helper(array('form', 'url'));

        require_once(APPPATH . 'libraries/doku/Doku.php');

        Doku_Initiate::$sharedKey = self::DOKU_SHARED_KEY;
        Doku_Initiate::$mallId = self::DOKU_STORE_ID;

        //$this->payment_model->delete_table();
        //$this->payment_model->create_table();
    }

    /**
     * Format number
     * 
     * @access private
     * @param int $number
     * @return string
     */
    private function _format_number($number) {
        return number_format($number, 2, '.', '');
    }

    public function get_tables() {
        //$data = $this->db->get('transaction')->result_array();
        $data = $this->db->list_tables();
        $fields = [];
        foreach ($data as $d) {
            $fields[$d][] = $this->db->list_fields($d);
        }

        $data = $this->db->get('transaction')->result_array();
        print_r($data);
    }

    /**
     * Index
     * 
     * @access public
     * @return void
     */
    public function index() {
        $idtransaction = $this->input->get('idtransaction');
        $payment_channel = $this->input->get('payment_channel');

        if (!isset($this->payment_channels[$payment_channel])) {
            show_404();
        }

        if (in_array($payment_channel, array('07', '14', '22', '31', '32', '34', '38', '41', '42'))) {
            return $this->_charge_va($idtransaction, $payment_channel);
        }

        if ($data = $this->payment_model->get_transaction($idtransaction)) {
            $invoice = self::DOKU_INVOICE_PREFIX . $idtransaction;

            $params = array(
                'amount' => $this->_format_number($data['totalPay']),
                'invoice' => $invoice,
                'currency' => '360'
            );

            $words = Doku_Library::doCreateWords($params);

            $data['payment_channel'] = $payment_channel;
            $data['store_id'] = self::DOKU_STORE_ID;
            $data['invoice'] = $params['invoice'];
            $data['currency'] = $params['currency'];
            $data['amount'] = $params['amount'];
            $data['words'] = $words;

            $trx['amount'] = $data['amount'];
            $trx['words'] = $data['words'];
            $trx['idtransaction'] = $idtransaction;
            $trx['ip_address'] = $this->input->ip_address();
            $trx['process_type'] = 'REQUEST';
            $trx['process_datetime'] = date('YmdHis');
            $trx['payment_channel'] = $this->payment_channels[$payment_channel];
            $trx['transidmerchant'] = $invoice;

            $this->payment_model->add_transaction($trx);

            $this->load->view('payment_form', $data);
        } else {
            show_404();
        }
    }

    /**
     * Charge for Credit Card
     * 
     * @access public
     * @return void
     */
    public function charge_cc() {
        $token = $this->input->post('doku-token');
        $pairing_code = $this->input->post('doku-pairing-code');
        $invoice_no = $this->input->post('doku-invoice-no');
        $idtransaction = str_replace(self::DOKU_INVOICE_PREFIX, '', $invoice_no);

        if ($transaction = $this->payment_model->get_transaction($idtransaction)) {
            $params = array(
                'amount' => $this->_format_number($transaction['totalPay']),
                'invoice' => $invoice_no,
                'currency' => '360',
                'pairing_code' => $pairing_code,
                'token' => $token
            );

            $words = Doku_Library::doCreateWords($params);

            $dataPayment = array(
                'req_mall_id' => self::DOKU_STORE_ID,
                'req_chain_merchant' => 'NA',
                'req_amount' => $params['amount'],
                'req_words' => $words,
                'req_purchase_amount' => $params['amount'],
                'req_trans_id_merchant' => $invoice_no,
                'req_request_date_time' => date('YmdHis'),
                'req_currency' => '360',
                'req_purchase_currency' => '360',
                'req_session_id' => sha1(date('YmdHis')),
                'req_name' => $transaction['customer_name'],
                'req_payment_channel' => '15',
                'req_basket' => $this->_generate_basket($transaction),
                'req_email' => $transaction['customer_email'],
                'req_mobile_phone' => $transaction['customer_phone'],
                'req_address' => $transaction['customer_address'],
                'req_token_id' => $token
            );

            $result = Doku_Api::doPayment($dataPayment);

            if ($result->res_response_code == '0000') {
                echo 'SUCCESS';
            } else {
                echo 'FAILED';
            }
        } else {
            show_404();
        }
    }

    /**
     * Charge Mandiri Clickpay
     * 
     * @access public
     * @return void
     */
    public function charge_mandiri_clickpay() {
        $invoice_no = $this->input->post('invoice_no');
        $idtransaction = str_replace(self::DOKU_INVOICE_PREFIX, '', $invoice_no);
        $cc = str_replace(' - ', '', $this->input->post('cc_number'));

        if ($transaction = $this->payment_model->get_transaction($idtransaction)) {
            $params = array(
                'amount' => $this->_format_number($transaction['totalPay']),
                'invoice' => $invoice_no,
                'currency' => '360'
            );

            $words = Doku_Library::doCreateWords($params);

            $dataPayment = array(
                'req_mall_id' => self::DOKU_STORE_ID,
                'req_chain_merchant' => 'NA',
                'req_amount' => $params['amount'],
                'req_words' => $words,
                'req_purchase_amount' => $params['amount'],
                'req_trans_id_merchant' => $invoice_no,
                'req_request_date_time' => date('YmdHis'),
                'req_currency' => '360',
                'req_purchase_currency' => '360',
                'req_session_id' => sha1(date('YmdHis')),
                'req_name' => $transaction['customer_name'],
                'req_payment_channel' => '02',
                'req_email' => $transaction['customer_email'],
                'req_card_number' => $cc,
                'req_basket' => $this->_generate_basket($transaction),
                'req_challenge_code_1' => $this->input->post('CHALLENGE_CODE_1'),
                'req_challenge_code_2' => $this->input->post('CHALLENGE_CODE_2'),
                'req_challenge_code_3' => $this->input->post('CHALLENGE_CODE_3'),
                'req_response_token' => $this->input->post('response_token'),
                'req_mobile_phone' => $transaction['customer_phone'],
                'req_address' => $transaction['customer_address']
            );

            print_r($dataPayment);

            $response = Doku_Api::doDirectPayment($dataPayment);

            if ($response->res_response_code == '0000') {
                echo 'PAYMENT SUCCESS';
            } else {
                echo 'PAYMENT FAILED';
            }

            var_dump($response);
        } else {
            show_404();
        }
    }

    /**
     * Charge VA
     * 
     * @access private
     * @param int $idtransaction
     * @param string $payment_channel
     * @return void
     */
    private function _charge_va($idtransaction, $payment_channel) {
        if ($transaction = $this->payment_model->get_transaction($idtransaction)) {
            $invoice_no = self::DOKU_INVOICE_PREFIX . $idtransaction;

            $params = array(
                'amount' => $this->_format_number($transaction['totalPay']),
                'invoice' => $invoice_no,
                'currency' => '360'
            );

            $words = Doku_Library::doCreateWords($params);

            $dataPayment = array(
                'req_mall_id' => self::DOKU_STORE_ID,
                'req_chain_merchant' => 'NA',
                'req_amount' => $params['amount'],
                'req_words' => $words,
                'req_trans_id_merchant' => $invoice_no,
                'req_purchase_amount' => $params['amount'],
                'req_request_date_time' => date('YmdHis'),
                'req_session_id' => sha1(date('YmdHis')),
                'req_payment_channel' => $payment_channel,
                'req_email' => $transaction['customer_email'],
                'req_name' => $transaction['customer_name'],
                'req_basket' => $this->_generate_basket($transaction),
                'req_address' => $transaction['customer_address'],
                'req_mobile_phone' => $transaction['customer_phone']
            );

            $response = Doku_Api::doGeneratePaycode($dataPayment);

            if ($response->res_response_code == '0000') {
                $trx['amount'] = $params['amount'];
                $trx['words'] = $words;
                $trx['idtransaction'] = $idtransaction;
                $trx['ip_address'] = $this->input->ip_address();
                $trx['process_type'] = 'REQUEST';
                $trx['process_datetime'] = date('YmdHis');
                $trx['payment_channel'] = $this->payment_channels[$payment_channel];
                $trx['transidmerchant'] = $invoice_no;

                $this->payment_model->add_transaction($trx);
            }

            print_r($response);
        } else {
            show_404();
        }
    }

    /**
     * Redirect Page
     * 
     * @access public
     * @return void
     */
    public function status() {
        print_r($this->input->get(null, true));
        print_r($this->input->post(null, true));

        $data = array();

        $this->load->view('payment_status', $data);
    }

    /**
     * Verify Callback
     * 
     * @access public
     * @return void
     */
    public function verify() {
        if (!$this->input->post()) {
            $this->output->set_output('Stop : Access Not Valid');
            log_message('error', 'Doku verify not in correct format - IP logged ' . $this->input->ip_address());
            die;
        }

        if (substr($this->input->ip_address(), 0, strlen($this->ip_range)) !== $this->ip_range) {
            $this->output->set_output('Stop : IP Not Allowed');
            log_message('error', 'Doku verify from IP not allowed - IP logged ' . $this->input->ip_address());
        } else {
            $trx = array();
            $idtransaction = explode('_', $this->input->post('TRANSIDMERCHANT'));

            $trx['words'] = $this->input->post('WORDS');
            $trx['amount'] = $this->input->post('AMOUNT');
            $trx['idtransaction'] = $transidmerchant[1];
            $trx['transidmerchant'] = $this->input->post('TRANSIDMERCHANT');

            $words = sha1(trim($trx['amount']) . trim($this->config->item('msc_sharedkey')) . trim($trx['msc_transidmerchant']));

            if ($trx['words'] == $words) {
                $trx['ip_address'] = $this->input->ip_address();
                $trx['process_datetime'] = date("Y-m-d H:i:s");
                $trx['process_type'] = 'VERIFY';

                $result = $this->payment_model->check_transaction($trx);

                if ($result < 1) {
                    echo "Stop : Transaction Not Found";
                    log_message('error', "Doku verify can not find transactions - IP logged " . $this->input->ip_address());
                    die;
                } else {
                    $this->payment_model->add_transaction($trx);

                    // update transaction status here...

                    echo "Continue";
                }
            } else {
                echo "Stop : Request Not Valid";
                log_message('error', "Doku verify words not correct - IP logged " . $this->input->ip_address());
                die;
            }
        }
    }

    /**
     * Notify Callback
     * 
     * @access public
     * @return void
     */
    public function notify() {
        if (!$this->input->post()) {
            $this->output->set_output('Stop : Access Not Valid');
            log_message('error', 'Doku notify not in correct format - IP logged ' . $this->input->ip_address());
            die;
        }

        if (substr($this->input->ip_address(), 0, strlen($this->ip_range)) !== $this->ip_range) {
            $this->output->set_output('Stop : IP Not Allowed');
            log_message('error', 'Doku notify from IP not allowed - IP logged ' . $this->input->ip_address());
        } else {
            $trx = array();

            $transidmerchant = explode('_', $this->input->post('TRANSIDMERCHANT'));

            $trx['idtransaction'] = $transidmerchant[1];
            $trx['ip_address'] = $this->input->ip_address();
            $trx['process_type'] = 'NOTIFY';
            $trx['process_datetime'] = date('YmdHis');
            $trx['payment_datetime'] = $this->input->post('PAYMENTDATETIME');
            $trx['payment_channel'] = $this->input->post('PAYMENTCHANNEL');
            $trx['payment_code'] = $this->input->post('PAYMENTCODE');
            $trx['amount'] = $this->input->post('AMOUNT');
            $trx['words'] = $this->input->post('WORDS');
            $trx['result_msg'] = $this->input->post('RESULTMSG');
            $trx['transidmerchant'] = $this->input->post('TRANSIDMERCHANT');
            $trx['bank'] = $this->input->post('BANK');
            $trx['status_type'] = $this->input->post('STATUSTYPE');
            $trx['approval_code'] = $this->input->post('APPROVALCODE');
            $trx['response_code'] = $this->input->post('RESPONSECODE');
            $trx['session_id'] = $this->input->post('SESSIONID');

            $result = $this->payment_model->check_transaction($trx);

            if ($result < 1) {
                $this->output->set_output('Stop : Transaction Not Found');
                log_message('error', 'Doku notify can not find transactions - IP logged ' . $this->input->ip_address());
                die;
            } else {
                $this->payment_model->add_transaction($trx);

                if ($trx['result_msg'] == 'SUCCESS') {
                    $this->payment_model->set_paid($trx['idtransaction']);
                }

                $this->output->set_output('Continue');
            }
        }
    }

    /**
     * Review Page
     * 
     * @access public
     * @return void
     */
    public function review() {
        $this->load->view('payment_review');
    }

    /**
     * Generate basket
     * 
     * @access private
     * @param array $transaction
     * @return string
     */
    private function _generate_basket($transaction = array()) {
        $total = 0;
        $basket = '';

        foreach ($transaction['details'] as $detail) {
            $basket .= $detail['productName'] . ',';
            $basket .= $this->_format_number($detail['price']) . ',';
            $basket .= $detail['qty'] . ',';
            $basket .= $this->_format_number($detail['subtotal']) . ';';

            $total += (float) $detail['subtotal'];
        }

        if ($transaction['discount']) {
            $basket .= 'Discount,';
            $basket .= '-' . $this->_format_number($transaction['discount']) . ',';
            $basket .= '1,';
            $basket .= '-' . $this->_format_number($transaction['discount']) . ';';

            $total -= (float) $transaction['discount'];
        }

        if ($transaction['shippingprice']) {
            $basket .= 'Biaya Pengiriman,';
            $basket .= $this->_format_number($transaction['shippingprice']) . ',';
            $basket .= '1,';
            $basket .= $this->_format_number($transaction['shippingprice']) . ';';

            $total += (float) $transaction['shippingprice'];
        }

        if ($transaction['cost']) {
            $basket .= 'Biaya,';
            $basket .= $this->_format_number($transaction['cost']) . ',';
            $basket .= '1,';
            $basket .= $this->_format_number($transaction['cost']) . ';';

            $total += (float) $transaction['cost'];
        }

        // DEV ONLY - dikarenakan masih ada selisih data total di database

        if ((float) $transaction['totalPay'] != $total) {
            $selisih = (float) $transaction['totalPay'] - (float) $total;

            $basket .= 'Adjusted Amount,';
            $basket .= $this->_format_number($selisih) . ',';
            $basket .= '1,';
            $basket .= $this->_format_number($selisih) . ';';
        }

        // END OF DEV

        return $basket;
    }

}
