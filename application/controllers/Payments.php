<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller		
{
	const DOKU_SHARED_KEY = 'rwEodxWeA2c4';
	const DOKU_STORE_ID = '11631072';
	const DOKU_INVOICE_PREFIX = 'INV_';
		
	/**
	 * Constructor
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->load->model('payment_model');
		
		$this->load->helper(array('form', 'url'));
	}
	
	public function get_tables()
	{
		$this->load->database('db2');
		
		$data = $this->db->get('transaction_details')->result_array();
		print_r($data);
	}
	
	/**
	 * Payment Page
	 * 
	 * @access public
	 * @return void
	 */
	public function index()
	{
		require_once(APPPATH.'libraries/doku/Doku.php');
		
		Doku_Initiate::$sharedKey = self::DOKU_SHARED_KEY;
		Doku_Initiate::$mallId = self::DOKU_STORE_ID;
		
		$invoice = self::DOKU_INVOICE_PREFIX.'124782368';
		
		$params = array(
			'amount' => '10000.00',
			'invoice' => $invoice,
			'currency' => '360'
		);
		
		$words = Doku_Library::doCreateWords($params);

		$data['store_id'] = self::DOKU_STORE_ID;
		$data['invoice'] = $params['invoice'];
		$data['currency'] = $params['currency'];
		$data['amount'] = $params['amount'];
		$data['words'] = $words;
		
		$this->load->view('payment_form', $data);
	}
	
	public function charge()
	{
		require_once(APPPATH.'libraries/doku/Doku.php');
		
		Doku_Initiate::$sharedKey = self::DOKU_SHARED_KEY;
		Doku_Initiate::$mallId = self::DOKU_STORE_ID;
		
		$token = $this->input->post('doku-token');
		$pairing_code = $this->input->post('doku-pairing-code');
		$invoice_no = $this->input->post('doku-invoice-no');
		
		$params = array(
			'amount' => '10000.00',
			'invoice' => $invoice_no,
			'currency' => '360',
			'pairing_code' => $pairing_code,
			'token' => $token
		);
		
		$words = Doku_Library::doCreateWords($params);
		
		$basket[] = array(
			'name' => 'sayur',
			'amount' => '10000.00',
			'quantity' => '1',
			'subtotal' => '10000.00'
		);
		
		$customer = array(
			'name' => 'TEST NAME',
			'data_phone' => '08121111111', 'data_email' => 'test@test.com', 'data_address' => 'bojong gede #1 08/01'
		);
		
		$dataPayment = array(
			'req_mall_id' => Doku_Initiate::$mallId, 'req_chain_merchant' => 'NA',
			'req_amount' => '10000.00',
			'req_words' => $words,
			'req_purchase_amount' => '10000.00',
			'req_trans_id_merchant' => $invoice_no,
			'req_request_date_time' => date('YmdHis'),
			'req_currency' => '360',
			'req_purchase_currency' => '360',
			'req_session_id' => sha1(date('YmdHis')),
			'req_name' => $customer['name'],
			'req_payment_channel' => 15,
			'req_basket' => $basket,
			'req_email' => $customer['data_email'],
			'req_mobile_phone' => $customer['data_phone'],
			'req_token_id' => $token
		);
		
		$result = Doku_Api::doPayment($dataPayment);
		
		if ($result->res_response_code == '0000') {
			echo 'SUCCESS'; //success transaction
		} else {
			echo 'FAILED'; //failed transaction
		}
	}
	
	/**
	 * Redirect Page
	 * 
	 * @access public
	 * @return void
	 */
	public function status()
	{
		$data= array('user_id' => '', 'name' => '', 'email' => '', 'image' => '', 'gender' => '', 'telephone' => '', 'date_added' => '');
		
		$data['title'] = 'Transaction Success';
		$data['content'] = 'Thank you. Your payment transaction is being processed';
		
		if($this->session->userdata('user_id')){
			$user = $this->user_model->get_user($this->session->userdata('user_id'));

			foreach ($user as $key => $value) {
				if($key == 'date_added'){
					$data[$key] = str_replace(' ', '_', $value);
					$data[$key] = str_replace(':', '.', $data[$key]);

				}elseif($key == 'telephone'){
					$data[$key] = str_replace('+', '', $value);
					if(substr($value, 0, 1) == '0'){
						$data[$key] = '62'.ltrim($value, '0');
					}
				}else{
					$data[$key] = rawurlencode($value);
				}
				
			}
		}
		$this->_clear_session();
		
		$this->load->view('payment_status', $data);
	}
	
	/**
	 * Verify Callback
	 * 
	 * @access public
	 * @return void
	 */
	public function verify()
	{
		if ( ! $this->input->post()) {
			$this->output->set_output('Stop : Access Not Valid');
			log_message('error', 'Doku MSC verify not in correct format - IP logged '.$this->input->ip_address());	
			die;
		}
		
		if (substr($this->input->ip_address(),0,strlen($this->ip_range)) !== $this->ip_range) {
			echo "Stop : IP Not Allowed";
			log_message('error', "Doku MSC verify from IP not allowed - IP logged ".$this->input->ip_address());
		} else {	
			$trx = array();
			$transidmerchant = explode("_", $this->input->post('TRANSIDMERCHANT'));
						
			$trx['words'] = $this->input->post('WORDS');
			$trx['amount'] = $this->input->post('AMOUNT');
			$trx['transidmerchant'] = $transidmerchant[1];
			$trx['msc_transidmerchant'] = $this->input->post('TRANSIDMERCHANT');

			$words = sha1(trim($trx['amount']).trim($this->config->item('msc_sharedkey')).trim($trx['msc_transidmerchant']));
	
			if ($trx['words'] == $words) {
				$trx['ip_address'] = $this->input->ip_address();
				$trx['process_datetime'] = date("Y-m-d H:i:s");
				$trx['process_type'] = 'VERIFY';
				$result = $this->payment_model->check_transaction($trx);
		
				if ($result < 1) {
					echo "Stop : Transaction Not Found";
					log_message('error', "Doku MSC verify can not find transactions - IP logged ".$this->input->ip_address());
					die;
				} else {
					$this->payment_model->add_transaction($trx);

					$this->order_model->update_order_status($trx['transidmerchant'], $this->config->item('order_status_id'), 'Transaction Verify');
					echo "Continue";
				}
			} else {
				echo "Stop : Request Not Valid";
				log_message('error', "Doku MSC verify words not correct - IP logged ".$this->input->ip_address());
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
	public function notify()
	{
		if ( ! $this->input->post()) {
			echo "Stop : Access Not Valid";
			log_message('error', "Doku MSC notify not in correct format - IP logged ".$this->input->ip_address());	
			die;
		}

		if (substr($this->input->ip_address(),0,strlen($this->ip_range)) !== $this->ip_range) {
			echo "Stop : IP Not Allowed";
			log_message('error', "Doku MSC notify from IP not allowed - IP logged ".$this->input->ip_address());
		} else {	
			$trx = array();
			$transidmerchant = explode("_", $this->input->post('TRANSIDMERCHANT'));
			
			$trx['amount'] = $this->input->post('AMOUNT');
			$trx['transidmerchant'] = $transidmerchant[1];
			$trx['msc_transidmerchant'] = $this->input->post('TRANSIDMERCHANT');
			$trx['result_msg'] = $this->input->post('RESULT');
			$trx['ip_address'] = $this->input->ip_address();
			$trx['process_datetime'] = date("Y-m-d H:i:s");
			$trx['process_type'] = 'NOTIFY';
			
			$result = $this->payment_model->check_transaction($trx);
			
			if ($result < 1) {
				echo "Stop : Transaction Not Found";
				log_message('error', "Doku MSC notify can not find transactions - IP logged ".$this->input->ip_address());
				die;
			} else {
				$this->payment_model->add_transaction($trx);

				if (strtolower($trx['result_msg']) == "success") {
					$email_data = array();
								
					$email_data['dp'] = false;
					
					// update order status here...
					//$this->order_model->update_order_status($trx['transidmerchant'], $this->config->item('order_paid_status_id'), 'Payment Success', false);
					
					$order_id = $trx['transidmerchant'];
							
					$email_data['services'] = array();
								
					$order_info = $this->order_model->get_order($order_id);
										
					foreach ($this->order_model->get_order_services($order_id) as $order_service) {
						$email_data['services'][] = array(
							'name' => $order_service['name'],
							'quantity' => $order_service['quantity'],
							'price' => format_money($order_service['price'], 0),
							'total'	=> format_money($order_service['total'], 0)
						);
					}
										
					$email_data['order_id'] = $order_id;
					$email_data['invoice'] = $order_info['invoice_prefix'].$order_info['invoice_no'];
					$email_data['name'] = $order_info['name'];
					$email_data['vendor_name'] = $order_info['vendor_name'];
					$email_data['telephone'] = $order_info['telephone'];
					$email_data['email'] = $order_info['email'];
					$email_data['date_added'] = date('d/m/Y', strtotime($order_info['date_added']));
					$email_data['status'] = $order_info['status'];
					$email_data['comment'] = nl2br($order_info['comment']);
					$email_data['ip_address'] = $order_info['ip'];
											
					$email_data['address'] = format_address(array(
						'name' => '',
						'address' => $order_info['address'],
						'telephone' => '',
						'city' => $order_info['city'],
						'province' => $order_info['province'],
					));
								
					$email_data['payment_method'] = $order_info['payment_method'];
					$email_data['services'] = $this->order_model->get_order_services($order_info['order_id']);
					$email_data['totals'] = $this->order_model->get_order_totals($order_id);
								
					$this->load->model('user_model');
										
					if ($vendor = $this->user_model->get($order_info['vendor_id'])) {
						$email_data['vendor_telephone'] = $vendor['telephone'];
					} else {
						$email_data['vendor_telephone'] = false;
					}
								
					$email_data['logo'] = $this->image->resize($this->config->item('logo'), 210);
								
					$this->load->model('order_payment_model');
					
					$email_data['payments'] = $this->order_payment_model->get_paid_payments($order_id);
								
					$this->load->library('email');
					
					$email_data['recipient'] = 'client';
					$email_data['recipient_name'] = $order_info['name'];
					
					$this->email->from('noreply', $this->config->item('company'));
					$this->email->to($order_info['email']);
					$this->email->subject('Pembayaran Diterima');
					$this->email->message($this->load->layout(false)->view('emails/payment_paid', $email_data, true));
					$this->email->send();
								
					$email_data['recipient'] = 'vendor';
					$email_data['recipient_name'] = $order_info['vendor_name'];
					
					$this->email->from('noreply', $this->config->item('company'));
					$this->email->to($order_info['vendor_email']);
					$this->email->subject('Pembayaran Diterima');
					$this->email->message($this->load->layout(false)->view('emails/payment_paid', $email_data, true));
					$this->email->send();
				} else {
					// update order status here...
					//$this->order_model->update_order_status($trx['transidmerchant'], $this->config->item('order_cancel_status_id'), 'Payment Failed', true);
				}
										 
				$this->payment_model->update_transaction($trx);										 
				echo "Continue";
			}
		}
	}
	
	/**
	 * Review Page
	 * 
	 * @access public
	 * @return void
	 */
	public function review()
	{
		$this->load->view('payment_review');
	}
}
