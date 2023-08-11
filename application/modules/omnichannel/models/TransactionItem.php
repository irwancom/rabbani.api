<?php

class TransactionItem extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function insert_or_update($data) {
        $exists = $this->db->query("select * from transaction_item where salesorder_detail_id = ". $data->salesorder_detail_id);

        if (empty($exists->row())) {
            $this->db->insert('transaction_item', $data);
        } else {
            $this->db->where('salesorder_detail_id', $data->salesorder_detail_id);
            $this->db->update('transaction_item', $data);
        }
    }

}
