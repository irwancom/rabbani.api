<?php

class Call extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function callbackXENDIT($data = '') {
        $data = array(
            'fromcall' => 'XENDIT',
            'dataJson' => $data,
            'dateTime' => date('Y-m-d H:i:s')
        );
        $this->db->insert('logcallback', $data);
//        return $data;
        if (!empty($data)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function callbackJUBELIO($data = '') {
        $data = array(
            'fromcall' => 'JUBELIO',
            'dataJson' => $data,
            'dateTime' => date('Y-m-d H:i:s')
        );
        $this->db->insert('logcallback', $data);
//        return $data;
        if (!empty($data)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function tripayPpob($datax = '') {
        $data = GuzzleHttp\json_decode($datax);
        $query = $this->db->get_where('transaction_ppob', array('idtrxppob' => $data[0]->api_trxid))->result();
//        print_r($query);
        if (!empty($query)) {
            if ($data[0]->status == 1) {
                if ($query[0]->typeOrder != 1) {
                    $typeFunction = 2;
                } else {
                    $typeFunction = 3;
                }
                $this->MainModel->comitionUpdate($query[0]->idUser, $typeFunction);
            }

            $this->db->set('priceSell', $data[0]->harga);
            $this->db->set('dataJason', $datax);
            $this->db->set('status', $data[0]->status);
            $this->db->where('idtrxppob', $data[0]->api_trxid);
            $this->db->update('transaction_ppob');

//            if ($data[0]->status == 1) {
//                $this->MainModel->transactionBonus($query[0]->idUser, $query[0]->typeOrder);
//            }

            $response['status'] = 200;
            $response['error'] = FALSE;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
        }
        return $response;
    }

}
