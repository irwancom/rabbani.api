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
		$this->db->insert('payment', $data);
		
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
		->where('check_flag', 0)
		->count_all_results('payment');
	}
	
	/**
	 * Update transaction
	 * 
	 * @access public
	 * @param array $data
	 * @return mixed
	 */
	public function update_transaction($data)
	{
		$this->db
		->where('process_type', 'REQUEST')
		->where('transidmerchant', $data['transidmerchant'])
		->where('amount', (float)$data['amount'])
		->set('check_flag', 1)
		->update('payment');
					
		return $this->db->affected_rows();
	}
}
