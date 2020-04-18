<?php

class H2h_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
        $this->load->helper(array('form', 'url'));
    }

    public function verfyAccount($keyCode = '', $secret = '') {
        $data = array(
            "keyCode" => $keyCode,
            "secret" => $secret
        );
        $query = $this->db->get_where('apiauth', $data)->result();
        return $query;
    }

    public function store($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            $checkDataStore = $this->db->get_where('store', array('idquantum' => $data['idquantum']))->result();
            $data = array(
                'idquantum' => $data['idquantum'],
                'namestore' => $data['namestore'],
                'phonestore' => $data['phonestore'],
                'addrstore' => $data['addrstore'],
                'typeStore' => $data['typeStore']
            );
            if (empty($checkDataStore)) {
                $this->db->insert('store', $data);
                $msg = 'Entry Data Successful';
            } else {
                $this->db->set($data);
                $this->db->where('idquantum', $data['idquantum']);
                $this->db->update('store');
                $msg = 'Update Data Successful';
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['msg'] = $msg;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function product($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            $checkDataStore = $this->db->get_where('product_stockStore', array('idstorequantum' => $data['idstorequantum'], 'skuProduct' => $data['skuProduct']))->result();
            $data = array(
                'idstorequantum' => $data['idstorequantum'],
                'skuProduct' => $data['skuProduct'],
                'nameProduct' => $data['nameProduct'],
                'collor' => $data['collor'],
                'priceProduct' => $data['priceProduct'],
                'stockStore' => $data['stockStore']
            );
            if (empty($checkDataStore)) {
                $this->db->insert('product_stockStore', $data);
                $msg = 'Entry Data Successful';
            } else {
                $this->db->set($data);
                $this->db->where('idstorequantum', $data['idstorequantum']);
                $this->db->where('skuProduct', $data['skuProduct']);
                $this->db->update('product_stockStore');
                $msg = 'Update Data Successful';
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['msg'] = $msg;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function transaction($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            $this->db->select('idtransaction, timeCreate, dateCreate, orderBy, noInvoice, shipping, shippingprice, trackingCode, subtotal, discount');
            $checkDataStore = $this->db->get_where('transaction', array('readData' => 0, 'statuspay' => 1, 'status' => 2))->result();
            if (!empty($checkDataStore)) {
                foreach ($checkDataStore as $cDS) {
//                    $data[] = $cDS;
                    $this->db->select('skuPditails, collor, size, price, disc, qty, subtotal');
                    $ditailsDataTransaction = $this->db->get_where('transaction_details', array('idtransaction' => $cDS->idtransaction))->result();
                    if (!empty($ditailsDataTransaction)) {
                        foreach ($ditailsDataTransaction as $dDT) {
//                            $dataDitails[] = $dDT;
                            $data[] = array(
                                'transaction' => $cDS,
                                'transactionDetails' => $dDT
                            );
                        }
                    }
                }
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function transactionUpdate($data = '') {
//        print_r($data);
//        exit;
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            $checkDataTransaction = $this->db->get_where('transaction', array('noInvoice' => $data['noInvoice']))->result();
            if (!empty($checkDataTransaction)) {
                $dataUpdate = array(
                    'readData' => 1
                );
                $this->db->set($dataUpdate);
                $this->db->where('noInvoice', $data['noInvoice']);
                $this->db->update('transaction');
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function listData($data = '') {
//        print_r($data);
//        exit;
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            if ($data['page'] == 'store') {
                $this->db->select('idquantum, namestore');
                $data = $this->db->get('store')->result();
            } else {
                $this->db->select('idstorequantum, skuProduct, nameProduct, collor, priceProduct, stockStore, idquantum, namestore');
                $this->db->join('store as b', 'b.idquantum = a.idstorequantum', 'left');
                $data = $this->db->get('product_stockStore as a')->result();
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($data);
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function dataSku($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            $checkDataSku = $this->db->get_where('product_sku', array('skuDitails' => $data['skuDitails']))->result();
            $dataQuery = array(
                'yearSku' => $data['yearSku'],
                'sku' => $data['sku'],
                'productName' => strtoupper($data['productName']),
                'skuDitails' => $data['skuDitails'],
                'collor' => $data['collor'],
                'size' => $data['size'],
                'price' => $data['price'],
                'weight' => $data['weight']
            );
            if (empty($checkDataSku)) {
                $this->db->insert('product_sku', $dataQuery);
                $msg = 'Sucess full insert data.';
            } else {
                $this->db->set($dataQuery);
                $this->db->where('skuDitails', $data['skuDitails']);
                $this->db->update('product_sku');
                $msg = 'Sucess full update data.';
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = $msg;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function entryDataSensus($param = '') {
        if (!empty($param)) {
            foreach ($param as $pm) {
                $checkData = $this->db->get_where('sensus_province', array('nameProv' => $pm->province))->result();
                $data = array(
                    'nameProv' => $pm->province
                );
                if (empty($checkData)) {
                    $this->db->insert('sensus_province', $data);
                    $msg = 'Entry Data Successful';
                } else {
                    $this->db->set($data);
                    $this->db->where('id_prov', $checkData[0]->id_prov);
                    $this->db->update('sensus_province');
                    $msg = 'Update Data Successful';
                }
            }
        }
    }

    public function dataPrint($param = '') {
        if (!empty($param)) {
            $transactionData = $this->db->get_where('transaction', array('idstore' => $param, 'status' => 1))->result();
//            print_r($transactionData[0]->idtransaction);
//            exit;
            if (!empty($transactionData)) {
                foreach ($transactionData as $tD) {
                    $transactionDataDitails = $this->db->get_where('transaction_details', array('idtransaction' => $tD->idtransaction))->result();
                    $data[] = array(
                        'dataTransaksi' => $tD,
                        'ditailtranskasi' => $transactionDataDitails
                    );
                }
            }
            if ($transactionData) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $data;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function cron($param = '') {
        if (!empty($param)) {
//            $datax = array(
//                'delproduct' => 0
//            );
//            $this->db->set($datax);
//            $this->db->update('product');
//            exit;
//            $data = $this->db->get_where('product', array('delproduct' => 1))->result();
//            if (!empty($data)) {
//                foreach ($data as $dt) {
//                    $this->db->set('delproductditails', '1');
//                    $this->db->where('idproduct', $dt->idproduct);
//                    $this->db->update('product_ditails');
//                }
//            }
//            exit;
//            $param1 = $param*10;
//            $param2 = $param*20;
            $this->db->where('`idSkuProd` BETWEEN 32001 AND 33000');
            $data = $this->db->get('product_sku')->result();
//            print_r($data);
            exit;
            if (!empty($data)) {
                foreach ($data as $dd) {
//                    print_r($dd->skuDitails);
//                    exit;
                    $aut2 = $this->quantum->callAPi($dd->skuDitails, 3);
                    print_r($aut2);
//                    exit;
                    if (!empty($aut2)) {
                        $data = $this->db->get_where('product', array('skuProduct' => $dd->sku))->result();
                        if (!empty($data)) {
//                            $datax = array(
//                                'skuProduct' => $dd->sku,
//                                'productName' => $dd->productName,
//                                'delproduct' => 1
//                            );
//                            $this->db->set($datax);
//                            $this->db->where('skuProduct', $dd->sku);
//                            $this->db->update('product');

                            $dataPditail = $this->db->get_where('product_ditails', array('skuPditails' => $dd->skuDitails))->result();
                            if (!empty($dataPditail)) {
                                $datax = array(
                                    'idproduct' => $data[0]->idproduct,
                                    'skuPditails' => $dd->skuDitails,
                                    'collor' => strtoupper($dd->collor),
                                    'size' => $dd->size,
                                    'price' => $dd->price,
                                    'realprice' => $dd->price,
                                    'stock' => $aut2[0]->s
                                );
                                $this->db->set($datax);
                                $this->db->where('skuPditails', $dd->skuDitails);
                                $this->db->update('product_ditails');
                            } else {
                                $datas = array(
                                    'idproduct' => $data[0]->idproduct,
                                    'skuPditails' => $dd->skuDitails,
                                    'collor' => strtoupper($dd->collor),
                                    'size' => $dd->size,
                                    'price' => $dd->price,
                                    'realprice' => $dd->price,
                                    'stock' => $aut2[0]->s
                                );

                                $this->db->insert('product_ditails', $datas);
                            }
                        } else {
                            $data = array(
                                'skuProduct' => $dd->sku,
                                'productName' => strtoupper($dd->productName),
                                'delproduct' => 1
                            );

                            $this->db->insert('product', $data);
                            $id = $this->db->insert_id();

                            $data = $this->db->get_where('product_ditails', array('skuPditails' => $dd->skuDitails))->result();
                            if (!empty($data)) {
                                $data = array(
                                    'idproduct' => $id,
                                    'skuPditails' => $dd->skuDitails,
                                    'collor' => strtoupper($dd->collor),
                                    'size' => $dd->size,
                                    'price' => $dd->price,
                                    'realprice' => $dd->price,
                                    'stock' => $aut2[0]->s
                                );
                                $this->db->set($data);
                                $this->db->where('skuPditails', $dd->skuDitails);
                                $this->db->update('product_ditails');
                            } else {
                                $data = array(
                                    'idproduct' => $id,
                                    'skuPditails' => $dd->skuDitails,
                                    'collor' => strtoupper($dd->collor),
                                    'size' => $dd->size,
                                    'price' => $dd->price,
                                    'realprice' => $dd->price,
                                    'stock' => $aut2[0]->s
                                );

                                $this->db->insert('product_ditails', $data);
                            }
                        }
                    }
                }
            }
            exit();

            $aut2 = $this->quantum->callAPi('BBA0DA19241A700', 3);
//            $data = $this->db->get_where('product_ditails',array('delproductditails'=>0));
            $data = $this->db->get_where('product_ditails', array('delproductditails' => 0))->result();
//            print_r($data);
            if (!empty($data)) {
                foreach ($data as $dd) {
//                    print_r($dd);
                    $aut2 = $this->quantum->callAPi($dd->skuPditails, 2);
                    $this->db->set('stock', $aut2->ts);
                    $this->db->where('idpditails', $dd->idpditails);
                    $this->db->update('product_ditails');
                }
            }
            exit;
            $aut2 = $this->quantum->callAPi($param, 2);
            print_r($aut2->ts);
            exit;
            if ($transactionData) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $data;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

}
