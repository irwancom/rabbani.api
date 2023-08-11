<?php

class Transaction extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function insert_or_update($data) {
        unset($data->items);

        $exists = $this->db->query("select * from transaction where salesorder_id = ". $data->salesorder_id);

        if (empty($exists->row())) {
            $this->db->insert('transaction', $data);
        } else {
            $this->db->where('salesorder_id', $data->salesorder_id);
            $this->db->update('transaction', $data);
        }
    }

}
