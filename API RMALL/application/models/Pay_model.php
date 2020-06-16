<?php

class Pay_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
        $this->load->helper(array('form', 'url'));
    }

    public function verfyAccount($keyCode = '') {
        $data = array(
            "keyCode" => $keyCode
        );
        $query = $this->db->get_where('apiauth_user', $data)->result();
        return $query;
    }

    public function getPayType($keyCode = '') {
        $check = $this->verfyAccount($keyCode);
        if (!empty($check)) {
            $query = $this->db->get_where('pay_method', array('is_enabled' => 0))->result();
            if ($query) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                return $response;
            }
        }
    }

    public function createVa($external_id = '', $keyCode = '') {
        $check = $this->verfyAccount($keyCode);
        if (!empty($check)) {
            $this->db->select('a.noInvoice, b.channel_code, c.name, a.totalpay');
            $this->db->join('sensus_people as c', 'c.idpeople = a.idpeople', 'left');
            $this->db->join('pay_method as b', 'b.idpaymethode = a.payment', 'left');
            $query = $this->db->get_where('transaction as a', array('a.noInvoice' => $external_id))->result();
//        print_r($query);
//            exit;
            if (!empty($query)) {
                $data = array(
                    'external_id' => $external_id,
                    'bank_code' => $query[0]->channel_code,
//                    'name' => $query[0]->name,
//                    'name' => 'RMALL ID',
                    'name' => 'RABBANI ASYSA',
//                'virtual_account_number'=> $external_id,
                    'is_closed' => true,
                    'expected_amount' => $query[0]->totalpay
                );
                $this->xendit->createVa(json_encode($data));
            }
        }
        if ($check) {
            $response['status'] = 200;
            $response['error'] = false;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            return $response;
        }
    }

    public function payHistories($external_id = '', $datax = '') {
        $dataCallBack = json_decode($datax);
        $query = $this->db->get_where('pay_histories', array('external_id' => $external_id))->result();
        $this->db->select('a.idtransaction, a.noInvoice, a.idauthuser, a.totalpay, b.hp');
        $this->db->join('apiauth_user as b', 'b.idauthuser = a.idauthuser', 'left');
        $queryx = $this->db->get_where('transaction as a', array('a.noInvoice' => $external_id))->result();
        if (!empty($query)) {

            $massage = 'rmall.id : Pembayaran Sebesar Rp ' . $dataCallBack->amount . ' Dari Tagihan Rp ' . $queryx[0]->totalpay . ' Melalui ' . $dataCallBack->bank_code . ' Virtual Account Telah Di Terima, Info Pengiriman Slahkan Cek Di Menu Transaksi.';
            $this->sms->SendSms($queryx[0]->hp, $massage);
            $data = array(
                'timePay' => date('Y-m-d H:i:s'),
                'statusPay' => $datax
            );
            $this->db->set($data);
            $this->db->where('external_id', $external_id);
            $this->db->update('pay_histories');

            if ($queryx[0]->totalpay == $dataCallBack->amount) {
                $data = array(
                    'statusPay' => 1
                );
            } else {
                $data = array(
                    'statusPay' => 99
                );
            }
            $this->db->set($data);
            $this->db->where('noInvoice', $external_id);
            $this->db->update('transaction');
        } else {

            $massage = 'rmall.id : Silahkan Transfer Rp ' . $queryx[0]->totalpay . ', melalui ' . $dataCallBack->bank_code . ' Virtual Account ' . $dataCallBack->account_number . ' a.n ' . $dataCallBack->name . ', Batas Pembayaran 1x24 Jam';
            $this->sms->SendSms($queryx[0]->hp, $massage);

            if (!empty($queryx)) {
                $data = array(
                    'paymentNote' => $datax
                );
                $this->db->set($data);
                $this->db->where('noInvoice', $external_id);
                $this->db->update('transaction');
            }
            $addPrice = $this->xendit->addBalanceToVa($external_id, $queryx[0]->totalpay);
            $data = array(
                'external_id' => $external_id,
                'timeCreate' => date('Y-m-d H:i:s'),
                'dataCreateVa' => $datax,
                'dataAddPrice' => $addPrice
            );
            $this->db->insert('pay_histories', $data);
        }
    }

}
