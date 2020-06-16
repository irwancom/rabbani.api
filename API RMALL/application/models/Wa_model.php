<?php

class Wa_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
        $this->load->helper(array('form', 'url'));
    }

    public function verfyPhone($phone = '') {
        //$this->db->call_function('some_function', $param1, $param2, etc..);
        $query = $this->db->get_where('apiauth_user', array('hp' => $phone, 's' => 0))->result();
//        print_r($query);
//        exit;
        if (!empty($query)) {
            $this->db->set('s', '1');
            $this->db->where('idauthuser', $query[0]->idauthuser);
            $this->db->update('apiauth_user');
            $msg = 'Voucher untuk no wa ' . $phone . ' ini masih berlaku, dan telah di kunci.';
        } else {
            $query1 = $this->db->get_where('apiauth_user', array('hp' => $phone))->result();
            if (empty($query1)) {
                $msg = 'Maaf no wa ' . $phone . ' belum melakukan penginputan di katalog.rmall.id';
            } else {
                $msg = 'Maaf no wa ' . $phone . ' sudah tidak berlaku karena sudah melakukan penukaran voucher.';
            }
        }
        return $msg;
    }

    public function verfyCashierPhone($phone = '') {
        //$this->db->call_function('some_function', $param1, $param2, etc..);
        $query = $this->db->get_where('store_cashier', array('phone' => $phone, 's' => 0))->result();

        return $query;
    }

    public function joinCashierPhone($name = '', $phone = '', $codeRshare = '') {
//        echo $name.'-'.$phone;
//        exit;
        $query = $this->db->get_where('store_cashier', array('phone' => $phone, 's' => 0))->result();
        if (empty($query)) {
            $data = array(
                'name' => strtoupper($name),
                'phone' => $phone,
                'codeRshare' => strtoupper($codeRshare)
            );

            $this->db->insert('store_cashier', $data);
        }
        return $query;
    }

    public function checkStore($codeRshare = '') {
//        echo $name.'-'.$phone;
//        exit;
        $query = $this->db->get_where('store', array('idquantum' => strtoupper($codeRshare)))->result();

        return $query;
    }

    public function dataCashier() {
//        echo $name.'-'.$phone;
//        exit;
        $this->db->join('store as b', 'b.idquantum = a.codeRshare', 'left');
        $query = $this->db->get('store_cashier as a')->result();

        return $query;
    }

    public function updateCashier($name = '', $phone = '', $codeRshare = '') {
        $this->db->set('name', strtoupper($name));
        $this->db->set('codeRshare', strtoupper($codeRshare));
        $this->db->where('phone', $phone);
        $this->db->update('store_cashier');
    }

    public function chatFunction($param = '') {
        $data = array(
            'message_id' => $param['message_id'],
            'fromphone' => $param['fromphone'],
            'to' => $param['to'],
            'message' => $param['message'],
            'pushName' => $param['pushName'],
            'groupId' => $param['groupId'],
            'timestamp' => $param['timestamp']
        );

        $this->db->insert('chat', $data);
    }

}
