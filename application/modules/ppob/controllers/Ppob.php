<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Ppob extends REST_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('MainModel');
        $this->load->model('PpobModel');
        $this->load->library('tripay');
        $this->load->library('sms');
    }

    private function verify($type = '') {
        $token = $this->input->get_request_header('X-Token-Secret');
        if (!empty($type)) {
            $dataVerify = $this->PpobModel->getSetting($token);
        } else {
            $dataVerify = $this->MainModel->verfyUser($token);
        }
        return $dataVerify;
    }

    public function index_get() {
        $dataVerify = $this->verify();
        print_r($dataVerify);
        echo 'tripay get ';
    }

    public function index_post() {
        $token = $this->input->get_request_header('X-Token-Secret');
        echo 'tripay post ' . $token;
    }

    public function categories_get() {
//        $resp['data'] = $this->tripay->kategoriProdukPembayaran();
//        if (!empty($resp['data'])) {
//            foreach ($resp['data']->data as $dd) {
//                print_r($dd);
//                $data = array(
//                    'idApi' => $dd->id,
//                    'productName' => $dd->name,
//                    'typeProduct' => 'PAYMENT'
//                );
//                print_r($data);
//                $this->PpobModel->addCat($data);
//            }
//        }
//        exit;
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;
            $resp['status'] = 200;
//            $resp['data'] = $this->tripay->kategoriProdukPembelian();
//            if (!empty($resp['data'])) {
//                foreach ($resp['data']->data as $dd) {
//                    print_r($dd);
//                    $data = array(
//                        'idApi' => $dd->id,
//                        'productName' => $dd->product_name,
//                        'typeProduct' => $dd->type
//                    );
//                    print_r($data);
//                    $this->PpobModel->addCat($data);
//                }
//            }
//            exit;
            $resp['data'] = $this->PpobModel->getCat();
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function purchasingProductOperator_get($id = '') {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;
            $resp['status'] = 200;
//            $resp['data'] = $this->tripay->operatorProdukPembelian();
//            if (!empty($resp['data'])) {
//                foreach ($resp['data']->data as $dd) {
//                    print_r($dd);
//                    $data = array(
//                        'idCatApi' => $dd->pembeliankategori_id,
//                        'product_code' => $dd->product_id,
//                        'product_name' => $dd->product_name,
//                        'prefix' => $dd->prefix
//                    );
//                    print_r($data);
//                    $this->PpobModel->addProPrx($data);
//                }
//            }
//            exit;
            $resp['data'] = $this->PpobModel->getPrx($id);
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function productPurchase_get($id = '') {

        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            //add on price
            if (!empty($dataVerify['dataSetupMlm'][1])) {
                $addPrice = $dataVerify['dataSetupMlm'][1]->total;
            } else {
                if (!empty($dataVerify['dataSetupPpob'][0]->total)) {
                    $addPrice = $dataVerify['dataSetupPpob'][0]->total;
                } else {
                    $addPrice = 0;
                }
            }
            //update price
            $resp['data'] = $this->tripay->produkPembelian();
            if (!empty($resp['data'])) {
                foreach ($resp['data']->data as $dd) {
//                print_r($dd);
                    $data = array(
                        'idApi' => $dd->id,
                        'idCatApi' => $dd->pembeliankategori_id,
                        'idOperator' => $dd->pembelianoperator_id,
                        'code' => $dd->code,
                        'product_name' => $dd->product_name,
                        'price' => $dd->price+$addPrice,
                        'lastUpdate' => date('Y-m-d H:i:s'),
                        'status' => $dd->status
                    );
//                print_r($data);
                    $this->PpobModel->addProPpob($data);
                }
            }
            //end add in price
            $resp = null;
            $resp['status'] = 200;
//            $resp['cost'] = array(
//                'service' => $addPrice,
//                'tax' => 0
//            );
            $resp['data'] = $this->PpobModel->getPpob($id);

            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function detailsProductPurchase_post() {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            $code = $this->input->post('code');
            $resp = null;
            $resp['status'] = 200;
            $resp['data'] = $this->PpobModel->getPpobDetail($code);
            //add on price
            if (!empty($dataVerify['dataSetupMlm'][1])) {
                $addPrice = $dataVerify['dataSetupMlm'][1]->total;
            } else {
                if (!empty($dataVerify['dataSetupPpob'][0]->total)) {
                    $addPrice = $dataVerify['dataSetupPpob'][0]->total;
                } else {
                    $addPrice = 0;
                }
            }
            //end add in price
            $resp['cost'] = array(
                'service' => $addPrice,
                'tax' => 0
            );

            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function productPayment_get($id = '') {
//        $resp['data'] = $this->tripay->produkPembayaran();
//        if (!empty($resp['data'])) {
//            foreach ($resp['data']->data as $dd) {
//                print_r($dd);
//                $data = array(
//                    'idAPi' => $dd->id,
//                    'idCatAPi' => $dd->pembayaranoperator_id,
//                    'code' => $dd->code,
//                    'product_name' => $dd->product_name,
//                    'fee' => $dd->biaya_admin,
//                    'status' => $dd->status
//                );
//                print_r($data);
//                $this->PpobModel->addProPay($data);
//            }
//        }
//        exit;
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            //add on price
            if (!empty($dataVerify['dataSetupMlm'][2])) {
                $addPrice = $dataVerify['dataSetupMlm'][2]->total;
            } else {
                $addPrice = $dataVerify['dataSetupPpob'][1]->total;
            }
            //end add in price
            $resp = null;
            $resp['status'] = 200;
            $resp['cost'] = array(
                'service' => $addPrice,
                'tax' => 0
            );
            $resp['data'] = $this->PpobModel->getPayment($id);

            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function detailProductPayment_post() {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            $code = $this->input->post('code');
            //add on price
            if (!empty($dataVerify['dataSetupMlm'][2])) {
                $addPrice = $dataVerify['dataSetupMlm'][2]->total;
            } else {
                $addPrice = $dataVerify['dataSetupPpob'][1]->total;
            }
            //end add in price
            $resp = null;
            $resp['status'] = 200;
            $resp['data'] = $this->PpobModel->getPaymentDetail($code);
            $resp['cost'] = array(
                'service' => $addPrice,
                'tax' => 0
            );

            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function ordersPurchase_post() {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
//            print_r($dataVerify);
//            exit;
            if (!empty($dataVerify['dataSetupMlm'][1])) {
                $addPrice = $dataVerify['dataSetupMlm'][1]->total;
            } else {
                if (!empty($dataVerify['dataSetupPpob'][0]->total)) {
                    $addPrice = $dataVerify['dataSetupPpob'][0]->total;
                } else {
                    $addPrice = 0;
                }
            }
            $resp = $this->PpobModel->addOrder(
                    $dataVerify['dataSecret'][0]->id, $this->input->post('typeOrder'), $this->input->post('code'), $this->input->post('phone'), $this->input->post('cosNumber'), $this->input->post('pin'), $addPrice
            );
            //respond
//            if ($this->input->post('typeOrder') == 0) {
//                $jsonRespond = '[{"trxid":22499,"api_trxid":"' . $resp['transactionID'] . '","via":"API","code":"S100","produk":"Telkomsel 100","harga":"97765","target":"081290338745","mtrpln":"-","note":"Trx S100 081290338745 SUKSES. SN: 6408921795434446","token":"6408921795434446","status":0,"saldo_before_trx":100000,"saldo_after_trx":2235,"created_at":"2020-11-19 18:46:50","updated_at":"2020-11-19 18:46:50","tagihan":null}]';
//            } else {
//                $jsonRespond = '[{"trxid":82847,"api_trxid":"' . $resp['transactionID'] . '","via":"API","code":"PLNPASCH","produk":"PLN Pascabayar","harga":"1501250","target":"081241447283","mtrpln":"2785554916","note":"Pembayaran PLN Pascabayar 2785554916 a\/n Nama Pelanggan BERHASIL. SN\/Ref: 1836236942451196","token":"1836236942451196","status":1,"saldo_before_trx":2000000,"saldo_after_trx":498750,"created_at":"2020-11-19 19:03:10","updated_at":"2020-11-19 19:03:10","tagihan":{"id":24059,"nama":"Nama Pelanggan","periode":"202008","jumlah_tagihan":1500000,"admin":1250,"jumlah_bayar":1501250}}]';
//            }
//            $jsonRespond = GuzzleHttp\json_decode($jsonRespond);
////            print_r($jsonRespond->data->tagihan_id);
//            $code = array(
//                'idUser' => $dataVerify['dataSecret'][0]->id,
//                'idtrxppob' => $jsonRespond[0]->api_trxid,
//                'status' => $jsonRespond[0]->status,
//                'dataJason' => json_encode(array(
//                    'idBill' => $jsonRespond[0]->tagihan->id,
//                    'code' => $jsonRespond[0]->code,
//                    'phone' => $jsonRespond[0]->phone,
//                    'cosNumber' => $jsonRespond[0]->no_pelanggan,
//                    'name' => $jsonRespond[0]->nama,
//                    'periode' => $jsonRespond[0]->periode,
//                    'totalBill' => $jsonRespond[0]->jumlah_tagihan,
//                    'adminFee' => $jsonRespond[0]->biaya_admin,
//                    'totalPay' => $jsonRespond[0]->jumlah_bayar))
//            );
//            print_r($code);
//            $this->PpobModel->callBack($code);
//            exit;
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function history_get($pg = '', $typeOrder = '') {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            if ($pg == 'transaction') {
                $resp = $this->PpobModel->transaction($dataVerify['dataSecret'][0]->id, $typeOrder);
                if ($resp) {
                    $this->response($resp, 200);
                } else {
                    $this->response(array('status' => 'fail', 502));
                }
            } elseif ($pg == 'bonus') {
                $resp = $this->PpobModel->dataBonus($dataVerify['dataSecret'][0]->id);
                if ($resp) {
                    $this->response($resp, 200);
                } else {
                    $this->response(array('status' => 'data empty', 502));
                }
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function joinNewMembers_post() {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            $this->PpobModel->comitionUpdate($dataVerify['dataSecret'][0]->id, 1);
            $resp = $this->PpobModel->addMembers($dataVerify['dataSecret'][0]->id_auth, $dataVerify['dataSecret'][0]->id, $this->input->post('ipAddress'), $this->input->post('nameNewMembers'), $this->input->post('phone'), $this->input->post('codeActivation'), $this->input->post('pin'));
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function dataMembers_get() {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            $resp = $this->PpobModel->getMembers($dataVerify['dataSecret'][0]->id);
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function redemptionBonus_post() {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            $resp = $this->PpobModel->redemptionBonus($dataVerify['dataSecret'][0]->id, $this->input->post('pin'));
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function transferBalance_post() {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            $resp = $this->PpobModel->transferBalance($dataVerify['dataSecret'][0]->id, $this->input->post('nominal'), $this->input->post('sendTo'), $this->input->post('pin'));
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function payBill_post() {
        $dataVerify = $this->verify(1);
        if ($dataVerify['status'] == 200) {
            $resp = $this->PpobModel->payBill($dataVerify['dataSecret'][0]->id, $this->input->post('idtrxppob'), $this->input->post('pin'));
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

//    public function comition_get() {
//        $resp = $this->PpobModel->comitionUpdate(3, 1);
//        print_r($resp);
//    }

    public function generateCode_get() {
        $x = 1;

        while ($x <= 5) {
            $code = generateRandomString(6);
            $this->PpobModel->addCode($code);
            $x++;
        }
    }

//    public function callback_post() {
//        $payload = file_get_contents("php://input");
//        $payload = json_decode($payload, true);
//
//        $data = [
//            'fromcall' => 'TRIPAY',
//            'dataJson' => json_encode($payload),
//            'dateTime' => date('Y-m-d H:i:s')
//        ];
//        $action = $this->MainModel->insert('logcallback', $data);
//        return $this->returnJSON($payload);
//    }
}
