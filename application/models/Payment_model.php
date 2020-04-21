<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_model extends CI_Model		
{
	/**
	 * Constructor
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->load->database();
	}
	
	/**
	 * Create table
	 * 
	 * @access public
	 * @return void
	 */
	public function create_table()
	{
		$this->db->query("CREATE TABLE IF NOT EXISTS `doku_payment` (
		  `trx_id` int(11) NOT NULL AUTO_INCREMENT,
		  `idtransaction` int(11) NOT NULL DEFAULT '0',
		  `ip_address` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `process_type` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `process_datetime` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `payment_datetime` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `payment_channel` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `payment_code` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `amount` decimal(20,2) NOT NULL DEFAULT '0.00',
		  `words` varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `response_code` varchar(4) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `result_msg` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `transidmerchant` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `bank` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `status_type` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `approval_code` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `session_id` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  PRIMARY KEY (`trx_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
	}
	
	/**
	 * Delete table
	 * 
	 * @access public
	 * @return void
	 */
	public function delete_table()
	{
		$this->db->query("DROP TABLE IF EXISTS `doku_payment`");
	}
	
	/**
	 * Get transaction
	 *
	 * Only unpaid transaction
	 * 
	 * @access public
	 * @param int $idtransaction
	 * @return array
	 */
	public function get_transaction($idtransaction = null)
	{
		$transaction = $this->db
		->select('t.idtransaction, t.timeCreate, t.dateCreate, t.noInvoice, t.shippingprice, t.subtotal, t.cost, t.discount, t.totalPay, t.payment, t.status, sp.name as customer_name, sp.phone as customer_phone, sp.email as customer_email, sp.address as customer_address')
		->join('sensus_people sp', 'sp.idpeople = t.idpeople', 'left')
		->where('t.idtransaction', (int)$idtransaction)
		->where('t.statusPay', 0)
		->get('transaction t')
		->row_array();
		
		if ($transaction) {
			$transaction['details'] = $this->db
			->select('productName, skuPditails, price, voucher, disc, qty, subtotal')
			->where('idtransaction', (int)$transaction['idtransaction'])
			->get('transaction_details')
			->result_array();
		}
		
		return $transaction;
	}
	
	/**
	 * Set paid
	 * 
	 * @access public
	 * @param int $idtransaction (default: null)
	 * @return void
	 */
	public function set_paid($idtransaction = null)
	{
		$this->db
		->where('idtransaction', (int)$idtransaction)
		->set('statusPay', 1)
		->update('transaction');
	}
	
	/**
	 * Add transaction
	 * 
	 * @access public
	 * @param array $data
	 * @return int
	 */
	public function add_transaction($data)
	{
		$this->db->insert('doku_payment', $data);
		
		return $this->db->insert_id();
	}
	
	/**
	 * Check transaction
	 * 
	 * @access public
	 * @param array $data
	 * @return boolean
	 */
	public function check_transaction($data)
	{
		return (bool)$this->db
		->where('process_type', 'REQUEST')
		->where('transidmerchant', $data['transidmerchant'])
		->where('amount', (float)$data['amount'])
		->where('words', $data['words'])
		->count_all_results('doku_payment');
	}
}
