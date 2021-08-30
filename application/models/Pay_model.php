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
               $getva = $this->xendit->createVa(json_encode($data));
            //test
            }
        }
        if ($check) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $getva;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            return $response;
        }
    }

    public function payHistories($external_id = '', $datax = '') {

        $dataCallBack = json_decode($datax);
        //print_r($dataCallBack);
        //exit;
//        $query = $this->db->get_where('pay_histories', array('external_id' => $external_id))->result();
        $this->db->select('a.orderBy, a.idtransaction, a.noInvoice, a.idauthuser, a.totalpay, b.hp');
        $this->db->join('apiauth_user as b', 'b.idauthuser = a.idauthuser', 'left');
        $queryx = $this->db->get_where('transaction as a', array('a.noInvoice' => $external_id))->result();
        if (!empty($queryx)) {

            if (!empty($queryx[0]->totalpay)) {
                $totalpay = $queryx[0]->totalpay;
            } else {
                $totalpay = 0;
            }

            if (!empty($queryx[0]->hp)) {
                $hp = $queryx[0]->hp;
            } else {
                $hp = '08986002287';
            }

//            $massage = 'rmall.id : Silahkan Transfer Rp ' . $totalpay . ', melalui ' . $dataCallBack->bank_code . ' Virtual Account ' . $dataCallBack->account_number . ' a.n ' . $dataCallBack->name . ', Batas Pembayaran 1x24 Jam';
//            $this->sms->SendSms($hp, $massage);

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
        if (!empty($queryx)) {
              if ($queryx[0]->orderBy == 0) {
                  $massage = 'rabbani.id : Silahkan Transfer Rp ' . $totalpay . ', melalui ' . $dataCallBack->bank_code . ' Virtual Account ' . $dataCallBack->account_number . ' a.n ' . $dataCallBack->name . ', Batas Pembayaran 1x24 Jam';
                $this->sms->SendSms($hp, $massage);
                  
              } else {
            $massage = 'tailordigital.id : Silahkan Transfer Rp ' . $totalpay . ', melalui ' . $dataCallBack->bank_code . ' Virtual Account ' . $dataCallBack->account_number . ' a.n ' . $dataCallBack->name . ', Batas Pembayaran 1x24 Jam';
            $this->sms->SendSms($hp, $massage);
              }

            $response['status'] = 200;
            $response['error'] = false;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            return $response;
        }
    }
    
     public function payHistoriesdt($external_id = '', $datax = '') {

        $dataCallBack = json_decode($datax);
        //print_r($dataCallBack);
        //exit;
//        $query = $this->db->get_where('pay_histories', array('external_id' => $external_id))->result();
        $this->db->select('a.idtransaction, a.noInvoice, a.idauthuser, a.totalpay, b.hp');
        $this->db->join('apiauth_user as b', 'b.idauthuser = a.idauthuser', 'left');
        $queryx = $this->db->get_where('transaction as a', array('a.noInvoice' => $external_id))->result();
        if (!empty($queryx)) {

            if (!empty($queryx[0]->totalpay)) {
                $totalpay = $queryx[0]->totalpay;
            } else {
                $totalpay = 0;
            }

            if (!empty($queryx[0]->hp)) {
                $hp = $queryx[0]->hp;
            } else {
                $hp = '08986002287';
            }

//            $massage = 'rmall.id : Silahkan Transfer Rp ' . $totalpay . ', melalui ' . $dataCallBack->bank_code . ' Virtual Account ' . $dataCallBack->account_number . ' a.n ' . $dataCallBack->name . ', Batas Pembayaran 1x24 Jam';
//            $this->sms->SendSms($hp, $massage);

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
        if (!empty($queryx)) {
            $massage = 'tailordigital.id : Silahkan Transfer Rp ' . $totalpay . ', melalui ' . $dataCallBack->bank_code . ' Virtual Account ' . $dataCallBack->account_number . ' a.n ' . $dataCallBack->name . ', Batas Pembayaran 1x24 Jam';
            $this->sms->SendSms($hp, $massage);

            $response['status'] = 200;
            $response['error'] = false;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            return $response;
        }
    }

    public function payPaidHistories($external_id = '', $datax = '') {
        $dataCallBack = json_decode($datax);
//        print_r($dataCallBack);
//        exit;
        $query = $this->db->get_where('pay_histories', array('external_id' => $external_id))->result();
        $this->db->select('a.idtransaction, a.noInvoice, a.idauthuser, a.totalpay, b.hp');
        $this->db->join('apiauth_user as b', 'b.idauthuser = a.idauthuser', 'left');
        $queryx = $this->db->get_where('transaction as a', array('a.noInvoice' => $external_id))->result();
        if (!empty($query)) {

            if (!empty($queryx[0]->totalpay)) {
                $totalpay = $queryx[0]->totalpay;
            } else {
                $totalpay = 0;
            }

            if (!empty($queryx[0]->hp)) {
                $hp = $queryx[0]->hp;
            } else {
                $hp = '08986002287';
            }

//            $massage = 'rmall.id : Pembayaran Sebesar Rp ' . $dataCallBack->amount . ' Dari Tagihan Rp ' . $totalpay . ' Melalui ' . $dataCallBack->bank_code . ' Virtual Account Telah Di Terima, Info Pengiriman Slahkan Cek Di Menu Transaksi.';
//            $this->sms->SendSms($hp, $massage);
            $data = array(
                'timePay' => date('Y-m-d H:i:s'),
                'statusPay' => $datax
            );
            $this->db->set($data);
            $this->db->where('external_id', $external_id);
            $this->db->update('pay_histories');

            if (!empty($totalpay)) {

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
            }
        }
        if (!empty($query)) {
            
            if ($queryx[0]->orderBy=='tailordigital') {
                  $massage = 'digitaltailor.id : Pembayaran Sebesar Rp ' . $dataCallBack->amount . ' Dari Tagihan Rp ' . $totalpay . ' Melalui ' . $dataCallBack->bank_code . ' Virtual Account Telah Di Terima, Info Pengiriman Slahkan Cek Di Menu Transaksi.' ;
            $this->sms->SendSms($hp, $massage);
                  
              }else {
            $massage = 'rabbani.id : Pembayaran Sebesar Rp ' . $dataCallBack->amount . ' Dari Tagihan Rp ' . $totalpay . ' Melalui ' . $dataCallBack->bank_code . ' Virtual Account Telah Di Terima, Info Pengiriman Slahkan Cek Di Menu Transaksi.';
            $this->sms->SendSms($hp, $massage);
              }

            $response['status'] = 200;
            $response['error'] = false;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            return $response;
        }
    }
    
    public function payPaidHistoriesdt($external_id = '', $datax = '') {
        $dataCallBack = json_decode($datax);
//        print_r($dataCallBack);
//        exit;
        $query = $this->db->get_where('pay_histories', array('external_id' => $external_id))->result();
        $this->db->select('a.idtransaction, a.noInvoice, a.idauthuser, a.totalpay, b.hp');
        $this->db->join('apiauth_user as b', 'b.idauthuser = a.idauthuser', 'left');
        $queryx = $this->db->get_where('transaction as a', array('a.noInvoice' => $external_id))->result();
        if (!empty($query)) {

            if (!empty($queryx[0]->totalpay)) {
                $totalpay = $queryx[0]->totalpay;
            } else {
                $totalpay = 0;
            }

            if (!empty($queryx[0]->hp)) {
                $hp = $queryx[0]->hp;
            } else {
                $hp = '08986002287';
            }

//            $massage = 'rmall.id : Pembayaran Sebesar Rp ' . $dataCallBack->amount . ' Dari Tagihan Rp ' . $totalpay . ' Melalui ' . $dataCallBack->bank_code . ' Virtual Account Telah Di Terima, Info Pengiriman Slahkan Cek Di Menu Transaksi.';
//            $this->sms->SendSms($hp, $massage);
            $data = array(
                'timePay' => date('Y-m-d H:i:s'),
                'statusPay' => $datax
            );
            $this->db->set($data);
            $this->db->where('external_id', $external_id);
            $this->db->update('pay_histories');

            if (!empty($totalpay)) {

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
            }
        }
        if (!empty($query)) {
            $massage = 'tailordigital.id : Pembayaran Sebesar Rp ' . $dataCallBack->amount . ' Dari Tagihan Rp ' . $totalpay . ' Melalui ' . $dataCallBack->bank_code . ' Virtual Account Telah Di Terima, Info Pengiriman Slahkan Cek Di Menu Transaksi.';
            $this->sms->SendSms($hp, $massage);

            $response['status'] = 200;
            $response['error'] = false;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            return $response;
        }
    }

    public function payPaidHistoriesMoota($totalpay = '', $rawData = '') {
//        $dataCallBack = json_decode($datax);
//        print_r($dataCallBack);
//        exit;
        $this->db->join('apiauth_user as b', 'b.idauthuser = a.idauthuser', 'left');
        $queryx = $this->db->get_where('transaction as a', array('a.totalpay' => $totalpay, 'a.statusPay' => 0))->result();
//        print_r($queryx[0]->hp);
//        exit;
        if (!empty($queryx)) {

            $this->db->set('statusPay', 1);
            $this->db->where('totalpay', $totalpay);
            $this->db->update('transaction');
        }
        if (!empty($queryx)) {
            $massage = 'rabbani.id : Pembayaran Sebesar Rp ' . $totalpay . ' Dari Tagihan Rp ' . $totalpay . ' Melalui Transfer Bank Manual Telah Di Terima, Info Pengiriman Slahkan Cek Di Menu Transaksi.';
            $this->sms->SendSms($queryx[0]->hp, $massage);

            $response['status'] = 200;
            $response['error'] = false;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            return $response;
        }
    }

}
