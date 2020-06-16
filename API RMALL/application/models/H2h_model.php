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

    public function inputJne($data) {
        $dataOrders = $this->db->get_where('sensus', $data)->result();
        if (empty($dataOrders)) {
            echo 'in';
            $this->db->insert('sensus', $data);
        } else {
            echo 'exist';
        }
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
            $checkDataStore = $this->db->get_where('transaction', array('readData' => 0, 'statuspay' => 1, 'status' => 1))->result();
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

    public function short($url = '') {
        $dataShort = $this->db->get_where('short_url', array('urlname' => $url))->result();
        if ($dataShort) {
            $this->db->set('hitcount', 'hitcount+1', FALSE);
            $this->db->where('idshort', $dataShort[0]->idshort);
            $this->db->update('short_url');

            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $dataShort;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function syncStock($data = '') {
        if (!empty($data)) {
            $pg = $data * 20;
        } else {
            $pg = 20;
        }
        $dataSku = $this->db->get_where('product_ditails', array('delproductditails' => 0), 20, $pg)->result();
        if (!empty($dataSku)) {
            foreach ($dataSku as $dS) {
//                print_r($dS);
                $data = $this->quantum->callAPi($dS->skuPditails, 3);
                if (!empty($data[0]->s)) {
//                    $du = $this->quantum->callAPi($dS->skuPditails, 3, 'M001-O0001');
//                    if (!empty($du[0]->s)) {
//                        $du = $du[0]->s;
//                    } else {
//                        $du = 0;
//                    }
////                    $kopo = $this->quantum->callAPi($dS->skuPditails, 3,'M001-O0004');
//                    if (!empty($kopo[0]->s)) {
//                        $kopo = $kopo[0]->s;
//                    } else {
//                        $kopo = 0;
//                    }
////                    $nangor = $this->quantum->callAPi($dS->skuPditails, 3,'M001-O0006');
//                    if (!empty($nangor[0]->s)) {
//                        $nangor = $nangor[0]->s;
//                    } else {
//                        $nangor = 0;
//                    }
//                    $cimahi = $this->quantum->callAPi($dS->skuPditails, 3, 'M001-O0029');
//                    if (!empty($cimahi[0]->s)) {
//                        $cimahi = $cimahi[0]->s;
//                    } else {
//                        $cimahi = 0;
//                    }
//                    $bubat = $this->quantum->callAPi($dS->skuPditails, 3, 'M001-O0041');
//                    if (!empty($bubat[0]->s)) {
//                        $bubat = $bubat[0]->s;
//                    } else {
//                        $bubat = 0;
//                    }
//                    $bubat2 = $this->quantum->callAPi($dS->skuPditails, 3, 'M001-O0043');
//                    if (!empty($bubat2[0]->s)) {
//                        $bubat2 = $bubat2[0]->s;
//                    } else {
//                        $bubat2 = 0;
//                    }
//                    $stockAllBandung = $du + $kopo + $nangor + $cimahi + $bubat + $bubat2;
//                    $stockAllBandung = 0;
//                    print_r($stockAllBandung);
//                    exit;
                    $this->db->set('stock', $data[0]->s);
                    $this->db->set('stockRmall', $data[0]->s);
                    $this->db->set('stockAllBandung', 0);
                    $this->db->set('lastUpdate', date('Y-m-d H:i:s'));
                    $this->db->where('skuPditails', $dS->skuPditails);
                    $this->db->update('product_ditails');
                } else {
                    $this->db->set('stock', 0);
                    $this->db->set('delproductditails', 1);
                    $this->db->set('stockAllBandung', 0);
                    $this->db->set('lastUpdate', date('Y-m-d H:i:s'));
                    $this->db->where('skuPditails', $dS->skuPditails);
                    $this->db->update('product_ditails');
                }
                $this->db->set('valuePrice', 'stock*price', FALSE);
                $this->db->set('valuePriceAllBandung', 'stockAllBandung*price', FALSE);
                $this->db->where('skuPditails', $dS->skuPditails);
                $this->db->update('product_ditails');
                $data[] = $data;
            }
        }
//        print_r($data);
        if (!empty($dataSku)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $data;
            $response['message'] = 'Synce stock success.';
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function cron() {
//        $dataOrders = $this->db->get_where('transaction', array('statusPay' => 1, 'trackingCode' => ''), 1)->result();
//        if (!empty($dataOrders)) {
//            foreach ($dataOrders as $dO) {
//                $curl = curl_init();
//
//                curl_setopt_array($curl, array(
//                    CURLOPT_URL => "https://api.rmall.id/artificial/getAwb",
//                    CURLOPT_RETURNTRANSFER => true,
//                    CURLOPT_ENCODING => "",
//                    CURLOPT_MAXREDIRS => 10,
//                    CURLOPT_TIMEOUT => 0,
//                    CURLOPT_FOLLOWLOCATION => true,
//                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                    CURLOPT_CUSTOMREQUEST => "POST",
//                    CURLOPT_POSTFIELDS => array('keyCodeStaff' => 'dd14f7b6bc8c82ef660131c0e0c12c2d', 'secret' => 'da7e2e436c7e761229bad7a7417ef4f255d2905d04da77325f9ecd8bc6acfe50', 'noInvoice' => $dO->noInvoice),
//                ));
//
//                $response = curl_exec($curl);
//
//                curl_close($curl);
//                $data = json_decode($response);
//                print_r($data);
//            }
//        }
//        exit;
//        $this->db->set('realprice', 'price*0.9',FALSE);
//        $this->db->set('priceDiscount','price*0.1',FALSE);
//        $this->db->where('idproduct', '1507');
//        $this->db->where('idproduct', '1505');
//        $this->db->where('idproduct', '130');
//        $this->db->update('product_ditails');
//        
//        $this->db->set('status', 0);
//        $this->db->where('statusPay', 1);
//        $this->db->update('transaction');
//        exit;
        /* CHECK TRANSACTION AUTO CANCLE */
        $dataOrders = $this->db->get_where('transaction', array('statusPay' => 0), 100)->result();
        if (!empty($dataOrders)) {
            foreach ($dataOrders as $dO) {
                echo $dO->dateCreate . ' ' . $dO->timeCreate . '<br>';
                $awal = date_create($dO->dateCreate . ' ' . $dO->timeCreate);
                $akhir = date_create();
                $diff = date_diff($awal, $akhir);
                if ($diff->d >= 1) {
                    $this->db->set('statusPay', 2);
                    $this->db->where('idtransaction', $dO->idtransaction);
                    $this->db->update('transaction');
                }
            }
        }
        /* END CHECK */
        /* TRACKING POSITION */
        $where = array(
            'statusPay' => 1
        );
        $this->db->where('trackingCode!=""');
        $this->db->where('status!=2');
        $this->db->order_by('rand()');
        $dataTracking = $this->db->get_where('transaction', $where, 25)->result();
        if (!empty($dataTracking)) {
            foreach ($dataTracking as $dT) {
                echo $dT->trackingCode . '<br>';
                $data = $this->courir->jne(2, $dT->trackingCode);
                $data = json_decode($data);
//                print_r($data);
                if ((!empty($data->cnote->pod_status)) && ($data->cnote->pod_status == 'DELIVERED')) {
                    $this->db->set('status', 2);
                    $this->db->where('idtransaction', $dT->idtransaction);
                    $this->db->update('transaction');
                } elseif ((!empty($data->cnote->pod_status)) && ($data->cnote->pod_status == 'ON PROCESS')) {
                    $this->db->set('status', 1);
                    $this->db->where('idtransaction', $dT->idtransaction);
                    $this->db->update('transaction');
                }
            }
        }
        /* END TRACKING */
        /* SYNCE DATA STOCK QUANTUM */
//        $last_min = date('Y-m-d H:i:s', strtotime('-15 minutes'));
//        $this->db->order_by('rand()');
//        $dataSync = $this->db->get_where('product_ditails', array('delproductditails' => 0, 'lastUpdate<' => $last_min), 50)->result();
//
//        if (!empty($dataSync)) {
//            foreach ($dataSync as $dS) {
//                echo $dS->skuPditails . '<br>';
//                $sync = $this->quantum->callAPi($dS->skuPditails, 2);
//                $this->db->set('stock', $sync->ts);
//                $this->db->set('stockRmall', $sync->ts);
//                $this->db->set('valuePrice', $dS->price * $sync->ts);
//                $this->db->set('lastUpdate', date('Y-m-d H:i:s'));
//                if (empty($sync->ts)) {
//                    $this->db->set('delproductditails', 1);
//                }
//                $this->db->set('stockAllBandung', 0);
//                $this->db->set('valuePriceAllBandung', 0);
//                $this->db->set('stockAllJabar', 0);
//                $this->db->set('valuePriceAllJabar', 0);
//                $this->db->where('skuPditails', $dS->skuPditails);
//                $this->db->update('product_ditails');
//            }
//        }
        /* END SYNCE STOCK */
        /* ADD NEW PRODUCT BY QUANTUM */
//        exit;
        $newProduct = $this->db->get_where('product_sku', array('ssku' => 1), 25)->result();
        if (!empty($newProduct)) {
            foreach ($newProduct as $nP) {
                print_r($nP);
                $dataQuantum = $this->quantum->callAPi($nP->skuDitails, 3);
                print_r($dataQuantum);
                if (!empty($dataQuantum)) {
                    $checkProduct = $this->db->get_where('product', array('skuProduct' => $nP->sku))->result();
                    $insert_id = $checkProduct[0]->idproduct;
                    if (empty($checkProduct)) {
                        $data = array(
                            'skuProduct' => $nP->sku,
                            'timeCreate' => date('H:i:s'),
                            'dateCreate' => date('Y-m-d'),
                            'productName' => $nP->productName,
                            'delproduct' => 1
                        );

                        $this->db->insert('product', $data);
                        $insert_id = $this->db->insert_id();
                    }
                    $checkProductDetails = $this->db->get_where('product_ditails', array('skuPditails' => $nP->skuDitails))->result();
                    if (!empty($checkProductDetails)) {
                        $data = array(
                            'idproduct' => $insert_id,
                            'skuPditails' => $nP->skuDitails,
                            'collor' => $nP->collor,
                            'size' => $nP->size,
                            'price' => $nP->price,
                            'stock' => $dataQuantum[0]->s
                        );
                        $this->db->set($data);
                        $this->db->where('skuPditails', $nP->skuDitails);
                        $this->db->update('product_ditails');
                    } else {
                        $data = array(
                            'idproduct' => $insert_id,
                            'skuPditails' => $nP->skuDitails,
                            'collor' => $nP->collor,
                            'size' => $nP->size,
                            'price' => $nP->price,
                            'stock' => $dataQuantum[0]->s
                        );

                        $this->db->insert('product_ditails', $data);
                    }
                }
                $this->db->set('ssku', 0);
                $this->db->where('skuDitails', $nP->skuDitails);
                $this->db->update('product_sku');
            }
        }
        /* END NEW PRODUCT BY QUANTUM */
    }

}
